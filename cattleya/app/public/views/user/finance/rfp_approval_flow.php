<?php
require_once __DIR__ . '/../../../../config/database.php';
require __DIR__ . '/../../includes/session_check.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- PDF FILE INTERCEPTOR ---
// Serves the file directly from the database LONGBLOB
if (isset($_GET['action']) && $_GET['action'] === 'view_pdf' && !empty($_GET['id'])) {
    $pdf_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT file_name, file_type, file_data FROM rfp_requests WHERE id = ?");
    $stmt->execute([$pdf_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($file && !empty($file['file_data'])) {
        header("Content-Type: " . ($file['file_type'] ?: 'application/pdf'));
        header("Content-Disposition: inline; filename=\"" . $file['file_name'] . "\"");
        header("Content-Length: " . strlen($file['file_data']));
        echo $file['file_data'];
        exit;
    } else {
        die("File not found or no file attached.");
    }
}
// --- END PDF INTERCEPTOR ---

$success_msg = '';
$error_msg   = '';
$logged_in_user_id = $_SESSION['user_id'] ?? $_SESSION['id'] ?? null;
$logged_in_role = strtolower(trim($_SESSION['role'] ?? 'encoder'));

if (!isset($_SESSION['rfp_sigs'])) {
    $_SESSION['rfp_sigs'] = [];
}

/**
 * Fetch payment data joined with sales details by payment ID,
 * or fallback to customer_id and due_date.
 */
function fetchPaymentWithSalesData($pdo, $payment_id, $customer_id = null, $due_date = null) {
    $sql = "SELECT 
                p.id AS payment_id,
                p.sale_id,
                p.customer_id,
                p.due_date,
                p.amount_due,
                p.amount_paid,
                p.agent_id,
                p.um_id,
                p.broker_id,
                p.agent_commission_amount,
                p.um_commission_amount,
                p.broker_commission_amount,
                p.agent_commission_status,
                p.um_commission_status,
                p.broker_commission_status,
                p.broker_encoder_id, p.broker_encoder_sig,
                p.broker_auditor_id, p.broker_auditor_sig,
                p.broker_cfo_id,     p.broker_cfo_sig,
                p.um_encoder_id,     p.um_encoder_sig,
                p.um_auditor_id,     p.um_auditor_sig,
                p.um_cfo_id,         p.um_cfo_sig,
                p.agent_encoder_id,  p.agent_encoder_sig,
                p.agent_auditor_id,  p.agent_auditor_sig,
                p.agent_cfo_id,      p.agent_cfo_sig,
                s.agent_fullname,
                s.um_fullname,
                s.broker_fullname,
                s.customer_fullname
            FROM payments p
            INNER JOIN sales s ON p.sale_id = s.sale_id ";
            
    if (!empty($payment_id)) {
        $sql .= "WHERE p.id = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$payment_id]);
    } elseif (!empty($customer_id) && !empty($due_date)) {
        $sql .= "WHERE p.customer_id = ? AND p.due_date = ? LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$customer_id, $due_date]);
    } else {
        return [];
    }
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getPaymentSignatureColumns($role, $stage) {
    $role = strtolower(trim((string)$role));
    $stage = strtolower(trim((string)$stage));
    $column_map = [
        'broker' => [
            'encoder' => ['id' => 'broker_encoder_id', 'sig' => 'broker_encoder_sig'],
            'auditor' => ['id' => 'broker_auditor_id', 'sig' => 'broker_auditor_sig'],
            'cfo'     => ['id' => 'broker_cfo_id',     'sig' => 'broker_cfo_sig']
        ],
        'um' => [
            'encoder' => ['id' => 'um_encoder_id', 'sig' => 'um_encoder_sig'],
            'auditor' => ['id' => 'um_auditor_id', 'sig' => 'um_auditor_sig'],
            'cfo'     => ['id' => 'um_cfo_id',     'sig' => 'um_cfo_sig']
        ],
        'agent' => [
            'encoder' => ['id' => 'agent_encoder_id', 'sig' => 'agent_encoder_sig'],
            'auditor' => ['id' => 'agent_auditor_id', 'sig' => 'agent_auditor_sig'],
            'cfo'     => ['id' => 'agent_cfo_id',     'sig' => 'agent_cfo_sig']
        ]
    ];
    return $column_map[$role][$stage] ?? null;
}

function parseCommissionKey($key) {
    $parts = explode('|', trim((string)$key));
    $count = count($parts);
    if ($count === 4) {
        return [
            'payment_id'  => is_numeric($parts[0]) ? (int)$parts[0] : null,
            'customer_id' => trim($parts[1]),
            'due_date'    => trim($parts[2]),
            'role'        => strtolower(trim($parts[3]))
        ];
    }
    if ($count === 3) {
        return [
            'payment_id'  => null,
            'customer_id' => trim($parts[0]),
            'due_date'    => trim($parts[1]),
            'role'        => strtolower(trim($parts[2]))
        ];
    }
    return null;
}

function getUserDetailsById($pdo, $user_id) {
    if (empty($user_id)) return null;
    try {
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, signature, signature_type, role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) return null;
        
        $fullname = strtoupper(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')));
        $sig_data_uri = '';
        if (!empty($user['signature'])) {
            $sig = $user['signature'];
            if (strpos($sig, 'data:image/') === 0) {
                $sig_data_uri = $sig;
            } else {
                $mime = !empty($user['signature_type']) ? trim($user['signature_type']) : 'image/png';
                $sig_data_uri = 'data:' . $mime . ';base64,' . base64_encode($sig);
            }
        }
        return [
            'user_id'  => $user['id'],
            'name'     => $fullname,
            'sig_src'  => $sig_data_uri,
            'role'     => strtoupper(str_replace('_', ' ', $user['role'] ?? '')),
            'raw_role' => strtolower($user['role'] ?? '')
        ];
    } catch (PDOException $e) {
        error_log('Signature fetch error: ' . $e->getMessage());
        return null;
    }
}

function updatePaymentSignatureRecord($pdo, $rfp_data, $stage, $user_id, $sig_src) {
    if (!$rfp_data || empty($rfp_data['selected_keys'])) {
        throw new RuntimeException('No selected commission/payment records were found.');
    }
    $stage = strtolower(trim((string)$stage));
    
    if (!in_array($stage, ['encoder', 'auditor', 'cfo'], true)) {
        throw new RuntimeException('Invalid approval stage.');
    }
    if (empty($user_id)) throw new RuntimeException('Invalid user ID.');
    if (empty($sig_src)) throw new RuntimeException('The user does not have a valid signature image.');

    foreach ($rfp_data['selected_keys'] as $key) {
        $parsed = parseCommissionKey($key);
        if (!$parsed) throw new RuntimeException('Invalid commission reference: ' . $key);
        
        $role        = $parsed['role'];
        $payment_id  = $parsed['payment_id'];
        $customer_id = $parsed['customer_id'];
        $due_date    = $parsed['due_date'];
        
        if (!in_array($role, ['agent', 'broker', 'um'], true)) {
            throw new RuntimeException('Invalid commission role: ' . $role);
        }
        
        $columns = getPaymentSignatureColumns($role, $stage);
        if (!$columns) throw new RuntimeException("Invalid role/stage combination: {$role}/{$stage}");
        
        $id_col  = $columns['id'];
        $sig_col = $columns['sig'];
        
        if (!empty($payment_id)) {
            $sql = "UPDATE payments SET `{$id_col}` = ?, `{$sig_col}` = ? WHERE id = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $sig_src, $payment_id]);
        } else {
            $sql = "UPDATE payments SET `{$id_col}` = ?, `{$sig_col}` = ? WHERE customer_id = ? AND due_date = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $sig_src, $customer_id, $due_date]);
        }
    }
}

function updateRfpRequestStatuses($pdo, $rfp_ids, $new_status) {
    if (empty($rfp_ids)) return;
    $rfp_ids = array_values(array_unique(array_filter(array_map('intval', $rfp_ids), function ($id) { return $id > 0; })));
    if (empty($rfp_ids)) return;
    
    $allowed_statuses = ['pending_audit', 'pending_cfo', 'released'];
    if (!in_array($new_status, $allowed_statuses, true)) throw new RuntimeException('Invalid RFP status.');
    
    $placeholders = implode(',', array_fill(0, count($rfp_ids), '?'));
    $sql = "UPDATE rfp_requests SET status = ? WHERE id IN ({$placeholders})";
    $params = array_merge([$new_status], $rfp_ids);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function formatDueDateSummary($due_dates) {
    $unique = array_values(array_unique(array_filter((array)$due_dates)));
    sort($unique);

    if (empty($unique)) return '';

    $formatted = array_map(function ($d) {
        $ts = strtotime($d);
        return $ts ? date('F d, Y', $ts) : $d;
    }, $unique);

    if (count($formatted) === 1) return $formatted[0];
    if (count($formatted) <= 3) return implode(', ', $formatted);
    return $formatted[0] . ' to ' . end($formatted) . ' (' . count($formatted) . ' due dates)';
}

function resolveRequisitionSlipLabel($rfp_id, $master_requisition_slip = null) {
    $master_requisition_slip = trim((string)($master_requisition_slip ?? ''));
    if ($master_requisition_slip !== '') return $master_requisition_slip;
    return 'REQ-COM-' . str_pad((string)$rfp_id, 6, '0', STR_PAD_LEFT);
}

// Generate Session Token to prevent multi-tab leaks
if (empty($_SESSION['rfp_session_token'])) {
    $_SESSION['rfp_session_token'] = bin2hex(random_bytes(16));
}

$flow_action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($flow_action, ['generate_flexible_rfp', 'load_existing_rfp', 'load_multiple_rfps', 'group_multiple_rfps'], true)) {
    
    // Regenerate session token on new load/generation
    $_SESSION['rfp_session_token'] = bin2hex(random_bytes(16));

    if ($flow_action === 'generate_flexible_rfp') {
        $selected = $_POST['selected_commissions'] ?? [];
        $selected = is_array($selected) ? array_values(array_filter(array_map('trim', $selected))) : [];

        if (empty($selected)) {
            $error_msg = 'Please select at least one commission before generating an RFP.';
        } else {
            $items         = [];
            $selected_keys = [];
            $due_dates     = [];

            foreach ($selected as $key) {
                $parsed = parseCommissionKey($key);
                if (!$parsed || !in_array($parsed['role'], ['agent', 'broker', 'um'], true)) continue;

                $payment_row = fetchPaymentWithSalesData($pdo, $parsed['payment_id'], $parsed['customer_id'], $parsed['due_date']);
                if (!$payment_row) continue;

                $item_due_date = $payment_row['due_date'] ?? $parsed['due_date'];

                $items[] = [
                    'id'          => $payment_row['payment_id'],
                    'payment_id'  => $payment_row['payment_id'],
                    'customer_id' => $parsed['customer_id'],
                    'due_date'    => $item_due_date,
                    'role'        => $parsed['role']
                ];

                $selected_keys[] = $key;
                if (!empty($item_due_date)) $due_dates[] = $item_due_date;
            }

            if (empty($items)) {
                $error_msg = 'None of the selected commissions could be found. Please refresh and try again.';
            } else {
                $_SESSION['rfp_data'] = [
                    'items'          => $items,
                    'selected_keys'  => $selected_keys,
                    'rfp_ids'        => [],
                    'due_date'       => $due_dates[0] ?? null,
                    'due_dates'      => array_values(array_unique($due_dates)),
                    'due_date_label' => formatDueDateSummary($due_dates),
                    'date_requested' => date('m/d/Y'),
                    'slip_no'        => 'DRAFT-' . date('Ymd-His'),
                    'total_charge'   => 0,
                    'is_grouped_rfp' => false
                ];
                $_SESSION['rfp_status'] = 'draft';
                $_SESSION['rfp_sigs']   = [];
            }
        }
    } elseif ($flow_action === 'load_existing_rfp') {
        $selected_key  = trim($_POST['selected_key'] ?? '');
        $posted_rfp_id = (int)($_POST['rfp_id'] ?? 0);
        $parsed        = parseCommissionKey($selected_key);

        if (!$parsed || !in_array($parsed['role'], ['agent', 'broker', 'um'], true)) {
            $error_msg = 'Invalid commission reference.';
        } else {
            $payment_row = fetchPaymentWithSalesData($pdo, $parsed['payment_id'], $parsed['customer_id'], $parsed['due_date']);

            if (!$payment_row) {
                $error_msg = 'That commission record could not be found.';
            } else {
                $role = $parsed['role'];
                $rfp_row = null;
                
                if ($posted_rfp_id > 0) {
                    try {
                        $stmtRfp = $pdo->prepare("SELECT id, status, master_requisition_slip FROM rfp_requests WHERE id = ? LIMIT 1");
                        $stmtRfp->execute([$posted_rfp_id]);
                        $rfp_row = $stmtRfp->fetch(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        $rfp_row = null;
                    }
                }

                $sigs = [];
                foreach (['encoder', 'auditor', 'cfo'] as $stage) {
                    $cols = getPaymentSignatureColumns($role, $stage);
                    $uid  = $cols ? ($payment_row[$cols['id']] ?? null) : null;

                    if (!empty($uid)) {
                        $signer_info = getUserDetailsById($pdo, $uid);
                        if ($signer_info) {
                            $stored_sig = $payment_row[$cols['sig']] ?? '';
                            if (!empty($stored_sig)) {
                                $signer_info['sig_src'] = (strpos($stored_sig, 'data:image/') === 0)
                                    ? $stored_sig
                                    : 'data:image/png;base64,' . base64_encode($stored_sig);
                            }
                            $sigs[$stage] = $signer_info;
                        }
                    }
                }

                $due_date = $payment_row['due_date'] ?? $parsed['due_date'];

                $saved_charges = [];
                $saved_controls = [];
                if ($posted_rfp_id > 0) {
                    try {
                        $stmtItemData = $pdo->prepare("SELECT commission_role, charge, control_no FROM rfp_request_items WHERE rfp_id = ? AND payment_id = ?");
                        $stmtItemData->execute([$posted_rfp_id, $payment_row['payment_id']]);
                        while ($sRow = $stmtItemData->fetch(PDO::FETCH_ASSOC)) {
                            $rKey = strtolower($sRow['commission_role']);
                            $saved_charges[$rKey]  = (float)$sRow['charge'];
                            $saved_controls[$rKey] = $sRow['control_no'];
                        }
                    } catch (PDOException $e) {}
                }

                $_SESSION['rfp_data'] = [
                    'items' => [[
                        'id'          => $payment_row['payment_id'],
                        'payment_id'  => $payment_row['payment_id'],
                        'customer_id' => $parsed['customer_id'],
                        'due_date'    => $due_date,
                        'role'        => $role,
                        'charges'     => $saved_charges,
                        'controls'    => $saved_controls,
                        'charge'      => $saved_charges[$role] ?? 0,
                        'control_no'  => $saved_controls[$role] ?? ''
                    ]],
                    'selected_keys'  => [$selected_key],
                    'rfp_ids'        => $posted_rfp_id > 0 ? [$posted_rfp_id] : [],
                    'due_date'       => $due_date,
                    'due_dates'      => $due_date ? [$due_date] : [],
                    'due_date_label' => formatDueDateSummary([$due_date]),
                    'date_requested' => date('m/d/Y'),
                    'slip_no'        => $posted_rfp_id > 0
                        ? resolveRequisitionSlipLabel($posted_rfp_id, $rfp_row['master_requisition_slip'] ?? null)
                        : ('REQ-' . strtoupper($role) . '-' . date('Ymd', strtotime((string)$due_date))),
                    'total_charge'   => array_sum($saved_charges),
                    'is_grouped_rfp' => false
                ];

                $status_col = $role . '_commission_status';
                $raw_status = strtolower(trim((string)($payment_row[$status_col] ?? '')));

                if ($rfp_row && !empty($rfp_row['status'])) {
                    $_SESSION['rfp_status'] = $rfp_row['status'];
                } elseif ($raw_status === 'released') {
                    $_SESSION['rfp_status'] = 'released';
                } else {
                    $_SESSION['rfp_status'] = 'pending_audit';
                }

                $_SESSION['rfp_sigs'] = $sigs;
            }
        }
    } elseif ($flow_action === 'load_multiple_rfps') {
        $posted_ids = $_POST['rfp_ids'] ?? [];
        $rfp_ids = array_values(array_unique(array_filter(array_map('intval', (array)$posted_ids), function ($id) { return $id > 0; })));

        if (empty($rfp_ids)) {
            $error_msg = 'Please select at least one RFP request to process.';
        } else {
            try {
                $placeholders = implode(',', array_fill(0, count($rfp_ids), '?'));
                $stmt = $pdo->prepare("
                    SELECT rr.id AS rfp_id, rr.status AS rfp_status, rr.master_requisition_slip,
                           ri.payment_id, ri.commission_role, ri.charge, ri.control_no, 
                           p.customer_id, p.due_date
                    FROM rfp_requests rr
                    INNER JOIN rfp_request_items ri ON ri.rfp_id = rr.id
                    INNER JOIN payments p ON p.id = ri.payment_id
                    WHERE rr.id IN ({$placeholders})
                    ORDER BY rr.id ASC, ri.id ASC
                ");
                $stmt->execute($rfp_ids);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($rows)) {
                    $error_msg = 'The selected RFP request(s) could not be found.';
                } else {
                    $items = []; $selected_keys = []; $due_dates = [];
                    $statuses = []; $slip_labels = [];
                    $sigs = [];

                    foreach ($rows as $row) {
                        $role = strtolower(trim($row['commission_role'] ?? ''));
                        if (!in_array($role, ['agent', 'broker', 'um'], true)) continue;

                        $items[] = [
                            'id'          => $row['payment_id'],
                            'payment_id'  => $row['payment_id'],
                            'customer_id' => $row['customer_id'],
                            'due_date'    => $row['due_date'],
                            'role'        => $role,
                            'rfp_id'      => (int)$row['rfp_id'],
                            'charges'     => [$role => (float)($row['charge'] ?? 0)],
                            'controls'    => [$role => $row['control_no'] ?? ''],
                            'charge'      => (float)($row['charge'] ?? 0),
                            'control_no'  => $row['control_no'] ?? ''
                        ];
                        $selected_keys[] = $row['customer_id'] . '|' . $row['due_date'] . '|' . $role;
                        if (!empty($row['due_date'])) $due_dates[] = $row['due_date'];
                        if (!empty($row['rfp_status'])) $statuses[] = $row['rfp_status'];
                        $slip_labels[(int)$row['rfp_id']] = resolveRequisitionSlipLabel((int)$row['rfp_id'], $row['master_requisition_slip'] ?? null);
                        
                        $payment_row = fetchPaymentWithSalesData($pdo, $row['payment_id']);
                        if ($payment_row) {
                            foreach (['encoder', 'auditor', 'cfo'] as $stage) {
                                if (isset($sigs[$stage])) continue;
                                $cols = getPaymentSignatureColumns($role, $stage);
                                if ($cols && !empty($payment_row[$cols['id']])) {
                                    $signer_info = getUserDetailsById($pdo, $payment_row[$cols['id']]);
                                    if ($signer_info) {
                                        $stored_sig = $payment_row[$cols['sig']] ?? '';
                                        if (!empty($stored_sig)) {
                                            $signer_info['sig_src'] = (strpos($stored_sig, 'data:image/') === 0) ? $stored_sig : 'data:image/png;base64,' . base64_encode($stored_sig);
                                        }
                                        $sigs[$stage] = $signer_info;
                                    }
                                }
                            }
                        }
                    }

                    if (empty($items)) {
                        $error_msg = 'The selected RFP request(s) have no valid commission items.';
                    } else {
                        $status_levels_map = ['draft' => 1, 'pending_audit' => 2, 'pending_cfo' => 3, 'released' => 4];
                        $unique_statuses   = array_values(array_unique($statuses));
                        $effective_status  = 'pending_audit';

                        if (!empty($unique_statuses)) {
                            usort($unique_statuses, function ($a, $b) use ($status_levels_map) {
                                return ($status_levels_map[$a] ?? 1) <=> ($status_levels_map[$b] ?? 1);
                            });
                            $effective_status = $unique_statuses[0];
                        }

                        $unique_slip_labels = array_values(array_unique($slip_labels));

                        $_SESSION['rfp_data'] = [
                            'items'          => $items,
                            'selected_keys'  => $selected_keys,
                            'rfp_ids'        => $rfp_ids,
                            'due_date'       => $due_dates[0] ?? null,
                            'due_dates'      => array_values(array_unique($due_dates)),
                            'due_date_label' => formatDueDateSummary($due_dates),
                            'date_requested' => date('m/d/Y'),
                            'slip_no'        => count($unique_slip_labels) === 1 ? $unique_slip_labels[0] : ('MULTI: ' . implode(' + ', $unique_slip_labels)),
                            'total_charge'   => 0,
                            'is_grouped_rfp' => count($rfp_ids) > 1
                        ];

                        if (count($unique_statuses) > 1) {
                            $success_msg = 'Note: the selected RFPs were at different stages; showing the earliest pending stage ('
                                . ucwords(str_replace('_', ' ', $effective_status)) . ').';
                        }
                        
                        $_SESSION['rfp_status'] = $effective_status;
                        $_SESSION['rfp_sigs']   = $sigs;
                    }
                }
            } catch (PDOException $e) {
                $error_msg = 'Failed to load the selected RFP requests: ' . $e->getMessage();
            }
        }
    } elseif ($flow_action === 'group_multiple_rfps') {
        $posted_ids = $_POST['rfp_ids'] ?? [];
        $rfp_ids = array_values(array_unique(array_filter(array_map('intval', (array)$posted_ids), function ($id) { return $id > 0; })));

        if (count($rfp_ids) < 2) {
            header('Location: /user/finance/commission-release?error=' . urlencode('Select at least two RFP requests to group under one requisition slip.'));
            exit;
        }

        try {
            $master_slip  = 'MREQ-' . date('Ymd') . '-' . str_pad((string)min($rfp_ids), 6, '0', STR_PAD_LEFT);
            $placeholders = implode(',', array_fill(0, count($rfp_ids), '?'));
            $stmt = $pdo->prepare("UPDATE rfp_requests SET master_requisition_slip = ? WHERE id IN ({$placeholders})");
            $stmt->execute(array_merge([$master_slip], $rfp_ids));

            header('Location: /user/finance/commission-release?success=' . urlencode(count($rfp_ids) . ' RFP requests were grouped under requisition slip ' . $master_slip . '.'));
            exit;
        } catch (PDOException $e) {
            header('Location: /user/finance/commission-release?error=' . urlencode('Failed to group the selected RFP requests.'));
            exit;
        }
    }
}

// Action Handlers for Approvals
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && !in_array($_POST['action'], ['generate_flexible_rfp', 'load_existing_rfp', 'load_multiple_rfps', 'group_multiple_rfps'], true)) {
    $action = $_POST['action'] ?? '';
    
    // Multi-tab CSRF/State Verification
    $posted_token = $_POST['rfp_session_token'] ?? '';
    if (empty($posted_token) || $posted_token !== $_SESSION['rfp_session_token']) {
        $error_msg = 'Session state mismatch detected. You may have opened another RFP in a different tab. Please refresh and try again.';
    } else {
        $signer = getUserDetailsById($pdo, $logged_in_user_id);
        
        if (!$signer) {
            $error_msg = 'User signature details could not be found.';
        } else {
            $rfp_data = $_SESSION['rfp_data'] ?? null;
            if (!$rfp_data) {
                $error_msg = 'No active RFP data was found.';
            } else {
                $has_signed_already = false;
                foreach (($_SESSION['rfp_sigs'] ?? []) as $stage => $sig_info) {
                    if (isset($sig_info['user_id']) && (string)$sig_info['user_id'] === (string)$signer['user_id']) {
                        $has_signed_already = true;
                        break;
                    }
                }
                
                if ($action === 'submit_encoder') {
                    $rfp_number_input = trim($_POST['rfp_number'] ?? '');
                    
                    if (empty($rfp_number_input)) {
                        $error_msg = 'RFP Number is required.';
                    } elseif (empty($_FILES['rfp_pdf']['name']) || $_FILES['rfp_pdf']['error'] !== UPLOAD_ERR_OK) {
                        $error_msg = 'You must attach a valid PDF RFP form.';
                    } else {
                        try {
                            // Strict MIME-type validation for file uploads
                            $finfo = new finfo(FILEINFO_MIME_TYPE);
                            $mime_type = $finfo->file($_FILES['rfp_pdf']['tmp_name']);
                            if ($mime_type !== 'application/pdf') {
                                throw new RuntimeException("Security violation: Only authentic PDF files are allowed.");
                            }

                            $posted_charges  = $_POST['charges'] ?? [];
                            $posted_controls = $_POST['controls'] ?? [];
                            $total_charge    = 0;

                            if (isset($_SESSION['rfp_data']['items'])) {
                                foreach ($_SESSION['rfp_data']['items'] as $idx => &$item) {
                                    $item_id = $item['id'] ?? $item['payment_id'] ?? $idx;
                                    
                                    foreach (['broker', 'um', 'agent'] as $r) {
                                        $rk = $item_id . '_' . $r;
                                        if (isset($posted_charges[$rk])) {
                                            $cval = floatval($posted_charges[$rk]);
                                            $item['charges'][$r]  = $cval;
                                            $item['controls'][$r] = trim($posted_controls[$rk] ?? '');
                                            $total_charge += $cval;
                                        }
                                    }
                                    if (isset($posted_charges[$item_id])) {
                                        $charge_val = floatval($posted_charges[$item_id]);
                                        $item['charge'] = $charge_val;
                                        $total_charge += $charge_val;
                                    }
                                    if (isset($posted_controls[$item_id])) {
                                        $item['control_no'] = trim($posted_controls[$item_id]);
                                    }
                                }
                                unset($item);
                                $_SESSION['rfp_data']['total_charge'] = $total_charge;
                            }

                            // Fetch file contents directly for database storage
                            $filename = 'RFP_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $rfp_number_input) . '_' . time() . '.pdf';
                            $file_type = $mime_type;
                            $file_data = file_get_contents($_FILES['rfp_pdf']['tmp_name']);

                            if ($file_data === false) {
                                throw new RuntimeException("Failed to read the uploaded PDF file.");
                            }

                            $pdo->beginTransaction();
                            updatePaymentSignatureRecord($pdo, $rfp_data, 'encoder', $signer['user_id'], $signer['sig_src']);

                            $is_new_rfp = empty($rfp_data['rfp_ids']);
                            $new_rfp_id = null;

                            if ($is_new_rfp) {
                                // Store file directly in the LONGBLOB column
                                $stmtRfp = $pdo->prepare("INSERT INTO rfp_requests (requisition_slip_no, status, created_by, file_name, file_type, file_data) VALUES (?, ?, ?, ?, ?, ?)");
                                $stmtRfp->execute([$rfp_number_input, 'pending_audit', $signer['user_id'], $filename, $file_type, $file_data]);
                                $new_rfp_id = $pdo->lastInsertId();
                                
                                $_SESSION['rfp_data']['rfp_ids'] = [$new_rfp_id];
                                $rfp_data['rfp_ids'] = [$new_rfp_id];
                                $_SESSION['rfp_data']['slip_no'] = resolveRequisitionSlipLabel($new_rfp_id, null);
                            } else {
                                // Update file data for existing records
                                $placeholders = implode(',', array_fill(0, count($rfp_data['rfp_ids']), '?'));
                                $stmtUpdRfp = $pdo->prepare("UPDATE rfp_requests SET file_name = ?, file_type = ?, file_data = ? WHERE id IN ($placeholders)");
                                $stmtUpdRfp->execute(array_merge([$filename, $file_type, $file_data], $rfp_data['rfp_ids']));
                            }

                            foreach ($rfp_data['selected_keys'] as $key) {
                                $parsed = parseCommissionKey($key);
                                if (!$parsed || !in_array($parsed['role'], ['agent', 'broker', 'um'], true)) continue;
                                
                                $role        = $parsed['role'];
                                $payment_id  = $parsed['payment_id'];
                                $customer_id = $parsed['customer_id'];
                                $due_date    = $parsed['due_date'];
                                $status_col  = $role . '_commission_status';

                                if (empty($payment_id)) {
                                    $stmtGetPay = $pdo->prepare("SELECT id FROM payments WHERE customer_id = ? AND due_date = ? LIMIT 1");
                                    $stmtGetPay->execute([$customer_id, $due_date]);
                                    $payment_id = $stmtGetPay->fetchColumn();
                                }

                                if (!empty($payment_id)) {
                                    $stmt = $pdo->prepare("UPDATE payments SET `{$status_col}` = 'Pending Approval' WHERE id = ? LIMIT 1");
                                    $stmt->execute([$payment_id]);

                                    $item_input_key = $payment_id . '_' . $role;
                                    $item_charge    = floatval($posted_charges[$item_input_key] ?? $posted_charges[$payment_id] ?? 0);
                                    $item_control   = trim($posted_controls[$item_input_key] ?? $posted_controls[$payment_id] ?? '');
                                    
                                    // Set empty strings to NULL to respect DB unique index safely,
                                    // otherwise append the role to avoid DB duplicate validation conflicts.
                                    if ($item_control === '') {
                                        $item_control = null; 
                                    } else {
                                        $role_suffix = '-' . strtoupper($role);
                                        // Only append if it wasn't already appended on a previous save
                                        if (!str_ends_with(strtoupper($item_control), $role_suffix)) {
                                            $item_control .= $role_suffix;
                                        }
                                    }

                                    if ($is_new_rfp) {
                                        $stmtItem = $pdo->prepare("INSERT INTO rfp_request_items (rfp_id, payment_id, commission_role, charge, control_no) VALUES (?, ?, ?, ?, ?)");
                                        $stmtItem->execute([$new_rfp_id, $payment_id, $role, $item_charge, $item_control]);
                                    } else {
                                        $target_rfp_id = $rfp_data['rfp_ids'][0];
                                        $stmtItem = $pdo->prepare("UPDATE rfp_request_items SET charge = ?, control_no = ? WHERE rfp_id = ? AND payment_id = ? AND commission_role = ?");
                                        $stmtItem->execute([$item_charge, $item_control, $target_rfp_id, $payment_id, $role]);
                                    }
                                }
                            }

                            if (!empty($rfp_data['rfp_ids'])) {
                                updateRfpRequestStatuses($pdo, $rfp_data['rfp_ids'], 'pending_audit');
                            }
                            
                            $pdo->commit();
                            $_SESSION['rfp_status'] = 'pending_audit';
                            $_SESSION['rfp_sigs']['encoder'] = $signer;
                            $success_msg = 'RFP saved with PDF attachment and signed by Encoder. It is now pending Audit.';
                        } catch (Exception $e) {
                            if ($pdo->inTransaction()) $pdo->rollBack();
                            if ($e instanceof PDOException && $e->getCode() == 23000) {
                                $error_msg = 'Database Validation Error: One of the submitted Control Numbers is already in use by another personnel.';
                            } else {
                                $error_msg = 'Error: ' . $e->getMessage();
                            }
                        }
                    }
                } elseif ($action === 'approve_auditor') {
                    if ($has_signed_already) {
                        $error_msg = 'Segregation of Duties Conflict: You cannot audit an RFP you previously handled.';
                    } else {
                        try {
                            $pdo->beginTransaction();
                            updatePaymentSignatureRecord($pdo, $rfp_data, 'auditor', $signer['user_id'], $signer['sig_src']);
                            if (!empty($rfp_data['rfp_ids'])) {
                                updateRfpRequestStatuses($pdo, $rfp_data['rfp_ids'], 'pending_cfo');
                            }
                            $pdo->commit();
                            $_SESSION['rfp_status'] = 'pending_cfo';
                            $_SESSION['rfp_sigs']['auditor'] = $signer;
                            $success_msg = 'Commission summary audited successfully.';
                        } catch (Exception $e) {
                            if ($pdo->inTransaction()) $pdo->rollBack();
                            $error_msg = 'Database Update Error: ' . $e->getMessage();
                        }
                    }
                } elseif ($action === 'approve_cfo') {
                    if ($has_signed_already) {
                        $error_msg = 'Segregation of Duties Conflict: Final CFO approval must be performed by an independent approver.';
                    } else {
                        try {
                            $pdo->beginTransaction();
                            updatePaymentSignatureRecord($pdo, $rfp_data, 'cfo', $signer['user_id'], $signer['sig_src']);
                            foreach ($rfp_data['selected_keys'] as $key) {
                                $parsed = parseCommissionKey($key);
                                if (!$parsed || !in_array($parsed['role'], ['agent', 'broker', 'um'], true)) continue;
                                
                                $role        = $parsed['role'];
                                $payment_id  = $parsed['payment_id'];
                                $customer_id = $parsed['customer_id'];
                                $due_date    = $parsed['due_date'];
                                $status_col  = $role . '_commission_status';
                                
                                if (!empty($payment_id)) {
                                    $stmt = $pdo->prepare("UPDATE payments SET `{$status_col}` = 'Released' WHERE id = ? LIMIT 1");
                                    $stmt->execute([$payment_id]);
                                } else {
                                    $stmt = $pdo->prepare("UPDATE payments SET `{$status_col}` = 'Released' WHERE customer_id = ? AND due_date = ? LIMIT 1");
                                    $stmt->execute([$customer_id, $due_date]);
                                }
                            }
                            if (!empty($rfp_data['rfp_ids'])) {
                                updateRfpRequestStatuses($pdo, $rfp_data['rfp_ids'], 'released');
                            }
                            $pdo->commit();
                            $_SESSION['rfp_status'] = 'released';
                            $_SESSION['rfp_sigs']['cfo'] = $signer;
                            $success_msg = 'Commission summary finalized. CFO signature saved and commissions released.';
                        } catch (Exception $e) {
                            if ($pdo->inTransaction()) $pdo->rollBack();
                            $error_msg = 'Database Update Error: ' . $e->getMessage();
                        }
                    }
                }
            }
        }
    }
}

/**
 * LOAD SESSION DATA AND PREPROCESS DISPLAY ROWS
 */
$rfp    = $_SESSION['rfp_data'] ?? null;
$status = $_SESSION['rfp_status'] ?? 'draft';
$sigs   = $_SESSION['rfp_sigs'] ?? [];

if (!$rfp) {
    echo "<div style='min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: system-ui, -apple-system, sans-serif; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);'>
            <div style='text-align: center; background: #fff; padding: 48px 56px; border-radius: 20px; box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; max-width: 440px;'>
                <div style='width: 64px; height: 64px; background: #fef2f2; color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;'>
                    <svg width='32' height='32' fill='none' stroke='currentColor' stroke-width='2' viewBox='0 0 24 24'><circle cx='12' cy='12' r='10'/><line x1='12' y1='8' x2='12' y2='12'/><line x1='12' y1='16' x2='12.01' y2='16'/></svg>
                </div>
                <h2 style='margin: 0 0 10px; font-size: 22px; font-weight: 700; color: #0f172a;'>No Active RFP Session</h2>
                <p style='margin: 0 0 28px; font-size: 15px; color: #64748b;'>There is no active Request for Payment data to display right now.</p>
                <a href='/user/finance/commission-release' style='display: inline-block; width: 100%; padding: 14px; background: #0f766e; color: #fff; border-radius: 12px; font-weight: 600; text-decoration: none; font-size: 15px; transition: background 0.2s ease;'>Return to Commissions</a>
            </div>
          </div>";
    exit;
}

$display_rows = [];

if (!empty($rfp['items'])) {
    foreach ($rfp['items'] as $idx => $item) {
        $payment_id   = $item['id'] ?? $item['payment_id'] ?? null;
        $selected_key = $rfp['selected_keys'][$idx] ?? null;
        $parsed_key   = $selected_key ? parseCommissionKey($selected_key) : null;
        
        $customer_id  = $parsed_key['customer_id'] ?? $item['customer_id'] ?? null;
        $due_date     = $parsed_key['due_date'] ?? $item['due_date'] ?? null;
        
        $db_payment = isset($pdo) ? fetchPaymentWithSalesData($pdo, $payment_id, $customer_id, $due_date) : [];
        $merged_item = array_merge($db_payment ?: [], $item);

        $roles_to_expand = [];
        if ($parsed_key && !empty($parsed_key['role'])) {
            $roles_to_expand[] = $parsed_key['role'];
        } elseif (!empty($merged_item['role'])) {
            $roles_to_expand[] = strtolower($merged_item['role']);
        } else {
            if (!empty($merged_item['broker_fullname']) || !empty($merged_item['broker_name']) || (!empty($merged_item['broker_commission_amount']) && $merged_item['broker_commission_amount'] > 0)) {
                $roles_to_expand[] = 'broker';
            }
            if (!empty($merged_item['um_fullname']) || !empty($merged_item['um_name']) || (!empty($merged_item['um_commission_amount']) && $merged_item['um_commission_amount'] > 0)) {
                $roles_to_expand[] = 'um';
            }
            if (!empty($merged_item['agent_fullname']) || !empty($merged_item['agent_name']) || (!empty($merged_item['agent_commission_amount']) && $merged_item['agent_commission_amount'] > 0)) {
                $roles_to_expand[] = 'agent';
            }
            if (empty($roles_to_expand)) {
                $roles_to_expand[] = 'payee';
            }
        }

        foreach ($roles_to_expand as $role) {
            $payee_name  = '';
            $role_amount = 0;

            if ($role === 'broker') {
                $payee_name  = $merged_item['broker_fullname'] ?? $merged_item['broker_name'] ?? '';
                $role_amount = $merged_item['broker_commission_amount'] ?? $merged_item['broker_commission'] ?? 0;
            } elseif ($role === 'um') {
                $payee_name  = $merged_item['um_fullname'] ?? $merged_item['um_name'] ?? '';
                $role_amount = $merged_item['um_commission_amount'] ?? $merged_item['um_commission'] ?? 0;
            } elseif ($role === 'agent') {
                $payee_name  = $merged_item['agent_fullname'] ?? $merged_item['agent_name'] ?? '';
                $role_amount = $merged_item['agent_commission_amount'] ?? $merged_item['agent_commission'] ?? 0;
            } else {
                $payee_name  = $merged_item['customer_fullname'] ?? $merged_item['payee_name'] ?? '';
                $role_amount = $merged_item['amount_paid'] ?? $merged_item['amount'] ?? 0;
            }

            $unique_input_key = count($roles_to_expand) > 1 ? $payment_id . '_' . $role : $payment_id;

            $display_rows[] = [
                'input_key' => $unique_input_key,
                'role'      => strtoupper($role),
                'payee'     => $payee_name,
                'amount'    => (float)str_replace(',', '', (string)$role_amount),
                'charge'    => (float)($merged_item['charges'][$role] ?? $merged_item['charge'] ?? 0),
                'control'   => $merged_item['controls'][$role] ?? $merged_item['control_no'] ?? '',
                'due_date'  => $merged_item['due_date'] ?? $rfp['due_date'] ?? ($parsed_key['due_date'] ?? '')
            ];
        }
    }
}

$status_levels   = ['draft' => 1, 'pending_audit' => 2, 'pending_cfo' => 3, 'released' => 4];
$current_level   = $status_levels[$status] ?? 1;
$can_edit_inputs = ($status === 'draft' && $logged_in_role === 'encoder');

// Fetch all available attachments for the selected RFPs
$attachments = [];
if (!empty($rfp['rfp_ids']) && isset($pdo)) {
    try {
        $ids = array_values(array_filter(array_map('intval', $rfp['rfp_ids'])));
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmtAtt = $pdo->prepare("SELECT id, file_name, requisition_slip_no FROM rfp_requests WHERE id IN ($placeholders) AND file_name IS NOT NULL");
            $stmtAtt->execute($ids);
            $attachments = $stmtAtt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commissions Summary - <?= htmlspecialchars($rfp['slip_no'] ?? 'BATCH') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --bg-canvas: #f1f5f9;
            --surface-card: #ffffff;
            --brand-teal: #0f766e;
            --brand-teal-hover: #0d9488;
            --brand-teal-dark: #115e59;
            --brand-teal-light: #ccfbf1;
            --text-heading: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --border-hover: #cbd5e1;
            --border-focus: #0f766e;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.08), 0 2px 4px -2px rgb(0 0 0 / 0.04);
            --shadow-lg: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --shadow-inner: inset 0 2px 4px 0 rgb(0 0 0 / 0.06);
            --radius-md: 8px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            --success-bg: #f0fdf4;
            --success-border: #bbf7d0;
            --success-text: #15803d;
            --error-bg: #fef2f2;
            --error-border: #fecaca;
            --error-text: #b91c1c;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: var(--text-body);
            line-height: 1.6;
            padding-bottom: 100px;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
        }

        /* Layout Container */
        .page-container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 40px 24px;
        }

        /* Alerts */
        .alert-box {
            padding: 16px 20px;
            border-radius: var(--radius-md);
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: var(--shadow-sm);
            animation: slideInDown 0.4s ease-out;
        }
        @keyframes slideInDown {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .alert-success { background: var(--success-bg); border: 1px solid var(--success-border); color: var(--success-text); }
        .alert-error { background: var(--error-bg); border: 1px solid var(--error-border); color: var(--error-text); }

        /* Control Panel */
        .control-panel {
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            padding: 32px 36px;
            margin-bottom: 40px;
        }

        .cp-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid var(--border-color);
        }

        .cp-title-wrap {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .cp-icon-badge {
            width: 52px;
            height: 52px;
            background: linear-gradient(135deg, var(--brand-teal-light) 0%, #ffffff 100%);
            color: var(--brand-teal);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px -1px rgba(15, 118, 110, 0.1);
        }

        .cp-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .cp-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .role-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            font-weight: 700;
            color: var(--brand-teal-dark);
            background: var(--brand-teal-light);
            padding: 8px 16px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: var(--shadow-sm);
        }

        /* Stepper Workflow Visualizer */
        .workflow-stepper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            margin: 0 auto 40px;
            max-width: 850px;
        }

        .workflow-stepper::before {
            content: '';
            position: absolute;
            top: 24px;
            left: 10%;
            right: 10%;
            height: 4px;
            background: var(--border-color);
            z-index: 1;
            border-radius: 2px;
        }

        .step-node {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            background: var(--surface-card);
            padding: 0 16px;
            width: 140px;
            text-align: center;
        }

        .node-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: var(--surface-card);
            border: 3px solid #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 700;
            color: #94a3b8;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .step-node.completed .node-circle {
            background: var(--brand-teal);
            border-color: var(--brand-teal);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(15, 118, 110, 0.25);
        }

        .step-node.active .node-circle {
            border-color: var(--brand-teal);
            color: var(--brand-teal);
            box-shadow: 0 0 0 6px var(--brand-teal-light);
        }

        .step-label {
            font-size: 13px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: color 0.3s ease;
        }

        .step-node.active .step-label,
        .step-node.completed .step-label {
            color: var(--text-heading);
        }

        /* Actions Bar */
        .cp-actions-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
            background: #f8fafc;
            padding: 16px;
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
        }

        .action-group-left, .action-group-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
        }

        .btn:active { transform: translateY(1px); }

        .btn-primary { 
            background: var(--brand-teal); 
            color: #ffffff; 
            box-shadow: 0 2px 4px rgba(15, 118, 110, 0.2);
        }
        .btn-primary:hover { 
            background: var(--brand-teal-hover); 
            box-shadow: 0 4px 6px rgba(15, 118, 110, 0.3);
        }

        .btn-outline {
            background: var(--surface-card);
            border-color: #cbd5e1;
            color: var(--text-heading);
            box-shadow: var(--shadow-sm);
        }
        .btn-outline:hover { 
            background: #f8fafc; 
            border-color: #94a3b8; 
            color: var(--brand-teal);
        }

        .form-control {
            padding: 10px 16px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            font-family: inherit;
            font-size: 14px;
            outline: none;
            background: #ffffff;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-inner);
        }
        .form-control:focus {
            border-color: var(--brand-teal);
            box-shadow: 0 0 0 3px var(--brand-teal-light);
        }

        /* Printable Document Sheet */
        .paper-document {
            background: var(--surface-card);
            border-radius: 4px; /* Simulating real paper corners */
            border: 1px solid #e2e8f0;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0,0,0,0.02);
            padding: 72px 80px;
            margin: 0 auto;
            position: relative;
            color: #0f172a;
        }

        .doc-header {
            border-bottom: 3px solid #0f172a;
            padding-bottom: 24px;
            margin-bottom: 36px;
            text-align: center;
        }

        .company-title {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .doc-subtitle {
            font-size: 16px;
            font-weight: 700;
            color: var(--brand-teal);
            letter-spacing: 0.1em;
            margin-top: 6px;
            text-transform: uppercase;
        }

        /* Metadata Grid */
        .meta-card {
            background: #fafafa;
            border-radius: var(--radius-md);
            border: 1px solid #e5e5e5;
            padding: 24px 32px;
            margin-bottom: 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 16px 40px;
        }

        .meta-item {
            display: flex;
            align-items: baseline;
            font-size: 13px;
        }

        .meta-label {
            width: 180px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            flex-shrink: 0;
        }

        .meta-value {
            font-weight: 700;
            color: #0f172a;
            flex-grow: 1;
            font-size: 14px;
        }

        /* Modern High-Density Table */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 48px;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 12px;
            border: 1px solid #cbd5e1;
            border-radius: var(--radius-md);
            overflow: hidden;
            background: #ffffff;
        }

        .data-table th {
            background: #1e293b;
            color: #f8fafc;
            font-weight: 700;
            padding: 14px 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-right: 1px solid #334155;
            border-bottom: 1px solid #334155;
            font-size: 11px;
        }

        .data-table th:last-child { border-right: none; }

        .data-table .sub-th {
            background: #334155;
            color: #e2e8f0;
        }

        .data-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            vertical-align: middle;
            color: #334155;
            font-weight: 600;
            transition: background 0.2s ease;
        }

        .data-table tbody tr:hover td {
            background-color: #f8fafc;
        }

        .data-table td:last-child { border-right: none; }
        .data-table tr:last-child td { border-bottom: none; }

        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .text-left { text-align: left !important; }

        .table-input {
            width: 100%;
            padding: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            font-family: inherit;
            text-align: center;
            background: #ffffff;
            transition: all 0.2s ease;
            color: #0f172a;
        }
        .table-input:focus {
            border-color: var(--brand-teal);
            outline: none;
            box-shadow: 0 0 0 3px var(--brand-teal-light);
        }

        /* Signatures Section */
        .signatures-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            margin-top: 64px;
            page-break-inside: avoid;
        }

        .sig-card {
            border: 2px dashed #e2e8f0;
            border-radius: var(--radius-lg);
            padding: 24px;
            text-align: center;
            position: relative;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            min-height: 180px;
            transition: transform 0.2s ease, border-color 0.2s ease;
        }

        .sig-card:hover {
            border-color: #cbd5e1;
            transform: translateY(-2px);
        }

        .sig-image-holder {
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            position: relative;
        }

        .sig-img {
            max-height: 100%;
            max-width: 180px;
            object-fit: contain;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.05));
        }

        .sig-fallback-script {
            font-family: 'Dancing Script', cursive;
            font-size: 32px;
            color: #0f172a;
            opacity: 0.9;
        }

        .sig-divider {
            border-top: 2px solid #0f172a;
            margin-bottom: 12px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .sig-role-title {
            font-weight: 800;
            font-size: 12px;
            color: #0f172a;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .sig-name {
            font-size: 14px;
            font-weight: 700;
            color: var(--brand-teal-dark);
            margin-top: 4px;
        }

        .sig-caption {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
            font-weight: 500;
        }

        .audit-badge {
            position: absolute;
            top: -24px;
            left: 50%;
            transform: translateX(-50%) rotate(-4deg);
            background: #ffffff;
            border: 2px solid #0284c7;
            color: #0284c7;
            padding: 6px 16px;
            font-size: 11px;
            font-weight: 800;
            border-radius: 8px;
            text-transform: uppercase;
            box-shadow: 0 4px 6px -1px rgba(2, 132, 199, 0.15);
            white-space: nowrap;
        }

        /* Attachments */
        .pdf-attachments-section h3 {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-heading);
        }

        .pdf-viewer-wrapper {
            background: var(--surface-card);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            margin-bottom: 40px;
        }

        /* Print Optimization */
        @media print {
            body { background: #ffffff; padding: 0; min-height: 0; }
            .page-container { max-width: 100%; margin: 0; padding: 0; }
            .control-panel, .alert-box, .pdf-attachments-section { display: none !important; }
            .paper-document {
                border: none;
                box-shadow: none;
                padding: 0;
                margin: 0;
                width: 100%;
                border-radius: 0;
            }
            .sig-card { border: none; }
            .sig-divider { border-top: 1px solid #000; }
            .data-table { border: 1px solid #000; }
            .data-table th, .data-table td { border-color: #000; color: #000; }
            .data-table th, .data-table .sub-th { background: #f3f4f6; color: #000; }
            .meta-card { background: transparent; border: none; padding: 0; gap: 8px; }
        }
    </style>
</head>
<body>

<div class="page-container">

    <?php if ($success_msg): ?>
        <div class="alert-box alert-success">
            <i data-lucide="check-circle-2" size="20"></i>
            <span><?= htmlspecialchars($success_msg) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error_msg): ?>
        <div class="alert-box alert-error">
            <i data-lucide="alert-circle" size="20"></i>
            <span><?= htmlspecialchars($error_msg) ?></span>
        </div>
    <?php endif; ?>

    <!-- Control Panel -->
    <div class="control-panel">
        <div class="cp-top">
            <div class="cp-title-wrap">
                <div class="cp-icon-badge">
                    <i data-lucide="file-check-2" size="28"></i>
                </div>
                <div>
                    <h1 class="cp-title">Commission Release Workflow</h1>
                    <span class="cp-subtitle">Manage, audit, and finalize commission requests</span>
                </div>
            </div>
            <div class="role-pill">
                <i data-lucide="user-check" size="16"></i>
                Role: <?= htmlspecialchars($logged_in_role) ?>
            </div>
        </div>

        <!-- Progress Tracker -->
        <div class="workflow-stepper">
            <div class="step-node <?= $current_level > 1 ? 'completed' : ($current_level === 1 ? 'active' : '') ?>">
                <div class="node-circle">
                    <i data-lucide="<?= $current_level > 1 ? 'check' : 'pen-tool' ?>" size="20"></i>
                </div>
                <span class="step-label">1. Encoded</span>
            </div>
            <div class="step-node <?= $current_level > 2 ? 'completed' : ($current_level === 2 ? 'active' : '') ?>">
                <div class="node-circle">
                    <i data-lucide="<?= $current_level > 2 ? 'check' : 'stamp' ?>" size="20"></i>
                </div>
                <span class="step-label">2. Pre-Audit</span>
            </div>
            <div class="step-node <?= $current_level > 3 ? 'completed' : ($current_level === 3 ? 'active' : '') ?>">
                <div class="node-circle">
                    <i data-lucide="<?= $current_level > 3 ? 'check' : 'shield-check' ?>" size="20"></i>
                </div>
                <span class="step-label">3. CFO Approved</span>
            </div>
        </div>

        <!-- Actions Toolbar -->
        <form id="rfp-workflow-form" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="rfp_session_token" value="<?= htmlspecialchars($_SESSION['rfp_session_token']) ?>">
            <div class="cp-actions-bar">
                <div class="action-group-left">
                    <a href="/user/finance/commission-release" class="btn btn-outline">
                        <i data-lucide="arrow-left" size="18"></i> Return
                    </a>
                    <button type="button" onclick="window.print()" class="btn btn-outline">
                        <i data-lucide="printer" size="18"></i> Print Summary
                    </button>
                    
                    <?php if (!empty($attachments)): ?>
                        <?php foreach ($attachments as $att): ?>
                            <a href="?action=view_pdf&id=<?= htmlspecialchars($att['id']) ?>" target="_blank" class="btn btn-outline" style="color: var(--brand-teal); border-color: var(--brand-teal-light);" title="<?= htmlspecialchars($att['file_name']) ?>">
                                <i data-lucide="file-text" size="18"></i> View <?= htmlspecialchars($att['requisition_slip_no'] ?: 'PDF') ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="action-group-right">
                    <?php if ($status === 'draft' && $logged_in_role === 'encoder'): ?>
                        <input type="text" name="rfp_number" placeholder="RFP Number" required class="form-control">
                        <input type="file" name="rfp_pdf" accept="application/pdf" required class="form-control" style="max-width: 220px; padding: 7px 12px;">
                        <button type="submit" name="action" value="submit_encoder" class="btn btn-primary">
                            <i data-lucide="upload-cloud" size="18"></i> Upload & Submit
                        </button>
                    <?php elseif ($status === 'pending_audit' && $logged_in_role === 'auditor'): ?>
                        <button type="submit" name="action" value="approve_auditor" class="btn btn-primary">
                            <i data-lucide="stamp" size="18"></i> Audit & Confirm
                        </button>
                    <?php elseif ($status === 'pending_cfo' && $logged_in_role === 'cfo'): ?>
                        <button type="submit" name="action" value="approve_cfo" class="btn btn-primary" style="background: var(--brand-teal-dark);" onclick="return confirm('Confirm final CFO approval and commission release?');">
                            <i data-lucide="check-circle" size="18"></i> Final Approve & Release
                        </button>
                    <?php elseif ($status === 'released'): ?>
                        <div style="display: flex; align-items: center; gap: 8px; font-weight: 800; color: var(--brand-teal); font-size: 15px; background: var(--brand-teal-light); padding: 10px 20px; border-radius: 8px;">
                            <i data-lucide="check-circle-2" size="22"></i> APPROVED & RELEASED
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Document Sheet -->
    <div class="paper-document">
        
        <div class="doc-header">
            <div class="company-title">CATTLEYA GARDENS & MEMORIAL PARK INC.</div>
            <div class="doc-subtitle">MLHUILLIER TRANSACTION SUMMARY</div>
        </div>

        <?php
            $base_total_principal = 0;
            $total_charge = 0;
            foreach ($display_rows as $drow) {
                $base_total_principal += $drow['amount'];
                $total_charge += $drow['charge'];
            }
            $total_cheque = $base_total_principal + $total_charge;
        ?>

        <!-- Metadata Card -->
        <div class="meta-card">
            <div class="meta-item">
                <span class="meta-label">DATE:</span>
                <span class="meta-value"><?= htmlspecialchars($rfp['date_requested'] ?? date('m/d/Y')) ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">BATCH NO:</span>
                <span class="meta-value"><?= htmlspecialchars($rfp['slip_no'] ?? '2024 SALES COMMISSION') ?></span>
            </div>
            <?php if (!empty($rfp['due_date_label'])): ?>
            <div class="meta-item">
                <span class="meta-label">DUE DATE:</span>
                <span class="meta-value"><?= htmlspecialchars($rfp['due_date_label']) ?></span>
            </div>
            <?php endif; ?>
            <div class="meta-item">
                <span class="meta-label">TOTAL TRANSACTIONS:</span>
                <span class="meta-value"><?= count($display_rows) ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">TOTAL PRINCIPAL:</span>
                <span class="meta-value">₱<span id="meta-total-principal"><?= number_format($base_total_principal, 2) ?></span></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">TOTAL CHARGE:</span>
                <span class="meta-value">₱<span id="meta-total-charge"><?= number_format($total_charge, 2) ?></span></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">TOTAL CHEQUE:</span>
                <span class="meta-value" style="color: var(--brand-teal); font-size: 16px; font-weight: 800;">₱<span id="meta-total-cheque"><?= number_format($total_cheque, 2) ?></span></span>
            </div>
        </div>

        <!-- Table View -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th width="4%" rowspan="2" class="text-center">#</th>
                        <th width="14%" rowspan="2" class="text-left">SENDER</th>
                        <th width="24%" colspan="2" class="text-center">RECEIVERS</th>
                        <th width="18%" colspan="2" class="text-center">PRINCIPAL</th>
                        <th width="10%" rowspan="2" class="text-center">CHARGE</th>
                        <th width="12%" rowspan="2" class="text-center">CONTROL #</th>
                        <th width="18%" rowspan="2" class="text-center">MONTH SALES</th>
                    </tr>
                    <tr>
                        <th width="12%" class="text-center sub-th">LAST</th>
                        <th width="12%" class="text-center sub-th">FIRST</th>
                        <th width="9%" class="text-center sub-th">BASE</th>
                        <th width="9%" class="text-center sub-th">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $index_counter = 1;

                        foreach ($display_rows as $drow) {
                            $item_key   = $drow['input_key'];
                            $item_payee = $drow['payee'];

                            $last_name  = '';
                            $first_name = '';

                            if (!empty($item_payee)) {
                                if (strpos($item_payee, ',') !== false) {
                                    $payee_parts = explode(',', $item_payee, 2);
                                    $last_name   = trim($payee_parts[0]);
                                    $first_name  = trim($payee_parts[1]);
                                } else {
                                    $payee_parts = array_filter(explode(' ', trim($item_payee)));
                                    if (count($payee_parts) > 1) {
                                        $last_name  = array_pop($payee_parts);
                                        $first_name = implode(' ', $payee_parts);
                                    } else {
                                        $last_name  = $item_payee;
                                        $first_name = '';
                                    }
                                }
                            }

                            if (empty($last_name))  $last_name  = 'UNKNOWN';
                            if (empty($first_name)) $first_name = strtoupper($drow['role']);
                            
                            $display_n   = $index_counter++;
                            $base_amt    = $drow['amount'];
                            $charge_val  = $drow['charge'];
                            $total_amt   = $base_amt + $charge_val;
                            
                            // Strip off the '-ROLE' suffix for display purposes so the user sees their clean control number
                            $control_val = $drow['control'];
                            $display_control = $control_val;
                            if (!empty($display_control)) {
                                $role_suffix = '-' . strtoupper($drow['role']);
                                if (str_ends_with(strtoupper($display_control), $role_suffix)) {
                                    $display_control = substr($display_control, 0, -strlen($role_suffix));
                                }
                            }

                            $due_date    = !empty($drow['due_date']) ? date('M d, Y', strtotime($drow['due_date'])) : '';
                    ?>
                    <tr>
                        <td class="text-center" style="color: #94a3b8; font-weight: 700;"><?= $display_n ?></td>
                        <td class="text-left" style="font-weight: 800; color: #0f172a;">CATTLEYA</td>
                        <td class="text-left"><?= htmlspecialchars(strtoupper($last_name)) ?></td>
                        <td class="text-left"><?= htmlspecialchars(strtoupper($first_name)) ?></td>
                        <td class="text-right" data-base="<?= $base_amt ?>">₱<?= number_format($base_amt, 2) ?></td>
                        <td class="text-right calc-total-principal" style="font-weight: 800; color: var(--brand-teal);">₱<?= number_format($total_amt, 2) ?></td>
                        
                        <td class="text-center">
                            <?php if ($can_edit_inputs): ?>
                                <input type="number" step="0.01" min="0" name="charges[<?= $item_key ?>]" form="rfp-workflow-form" class="table-input charge-input" value="<?= $charge_val > 0 ? $charge_val : '' ?>" placeholder="0.00">
                            <?php else: ?>
                                <?= $charge_val > 0 ? number_format($charge_val, 2) : '-' ?>
                            <?php endif; ?>
                        </td>
                        
                        <td class="text-center">
                            <?php if ($can_edit_inputs): ?>
                                <input type="text" name="controls[<?= $item_key ?>]" form="rfp-workflow-form" class="table-input" value="<?= htmlspecialchars($display_control) ?>" placeholder="Control #">
                            <?php else: ?>
                                <span style="color: #64748b; font-weight: 700;"><?= htmlspecialchars($display_control) ?: '-' ?></span>
                            <?php endif; ?>
                        </td>
                        
                        <td class="text-center" style="color: #64748b; font-weight: 700;"><?= htmlspecialchars($due_date) ?></td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- Signatures Card Grid -->
        <div class="signatures-grid">
            
            <!-- Encoder -->
            <div class="sig-card">
                <div class="sig-image-holder">
                    <?php if (isset($sigs['encoder'])): ?>
                        <?php if (!empty($sigs['encoder']['sig_src'])): ?>
                            <img src="<?= $sigs['encoder']['sig_src'] ?>" class="sig-img" alt="Encoder Signature">
                        <?php else: ?>
                            <div class="sig-fallback-script"><?= htmlspecialchars($sigs['encoder']['name']) ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="sig-divider"></div>
                <div class="sig-role-title">PREPARED BY</div>
                <div class="sig-name"><?= htmlspecialchars($sigs['encoder']['name'] ?? '') ?></div>
                <div class="sig-caption"><?= htmlspecialchars($sigs['encoder']['role'] ?? 'Encoder / CAD') ?></div>
            </div>

            <!-- Auditor -->
            <div class="sig-card">
                <div class="sig-image-holder">
                    <?php if (isset($sigs['auditor'])): ?>
                        <div class="audit-badge">AUDITED • <?= date('M Y') ?></div>
                        <?php if (!empty($sigs['auditor']['sig_src'])): ?>
                            <img src="<?= $sigs['auditor']['sig_src'] ?>" class="sig-img" alt="Auditor Signature">
                        <?php else: ?>
                            <div class="sig-fallback-script"><?= htmlspecialchars($sigs['auditor']['name']) ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="sig-divider"></div>
                <div class="sig-role-title">AUDITED BY</div>
                <div class="sig-name"><?= htmlspecialchars($sigs['auditor']['name'] ?? '') ?></div>
                <div class="sig-caption">Internal Audit Division</div>
            </div>

            <!-- CFO -->
            <div class="sig-card">
                <div class="sig-image-holder">
                    <?php if (isset($sigs['cfo'])): ?>
                        <?php if (!empty($sigs['cfo']['sig_src'])): ?>
                            <img src="<?= $sigs['cfo']['sig_src'] ?>" class="sig-img" alt="CFO Signature">
                        <?php else: ?>
                            <div class="sig-fallback-script" style="color: var(--brand-teal-dark);"><?= htmlspecialchars($sigs['cfo']['name']) ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="sig-divider"></div>
                <div class="sig-role-title">APPROVED BY</div>
                <div class="sig-name"><?= htmlspecialchars($sigs['cfo']['name'] ?? '') ?></div>
                <div class="sig-caption">Date: <?= $status === 'released' ? date('F d, Y') : '_____________' ?></div>
            </div>

        </div>

    </div>

    <!-- Inline PDF Display Section -->
    <?php if (!empty($attachments)): ?>
        <div class="pdf-attachments-section" style="margin-top: 56px; page-break-before: always;">
            <h3 style="margin-bottom: 24px; font-size: 22px; color: var(--text-heading); display: flex; align-items: center; gap: 12px;">
                <i data-lucide="paperclip" size="24"></i> Attached RFP Documents
            </h3>
            <?php foreach ($attachments as $att): ?>
                <div class="pdf-viewer-wrapper">
                    <!-- Clickable header to toggle iframe visibility -->
                    <div onclick="togglePdfFrame('pdf-frame-<?= $att['id'] ?>')" 
                         style="cursor: pointer; padding: 16px 28px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f8fafc;"
                         onmouseover="this.style.background='#e2e8f0'" 
                         onmouseout="this.style.background='#f8fafc'"
                         title="Click to view/hide PDF details">
                        
                        <span style="font-weight: 800; color: var(--brand-teal); font-size: 15px; display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="mouse-pointer-click" size="18"></i>
                            RFP: <?= htmlspecialchars($att['requisition_slip_no'] ?: 'N/A') ?>
                        </span>
                        
                        <span style="font-size: 14px; font-weight: 600; color: var(--text-muted);">
                            File: <?= htmlspecialchars($att['file_name']) ?>
                        </span>
                    </div>
                    
                    <!-- Iframe hidden by default (display: none) -->
                    <iframe id="pdf-frame-<?= $att['id'] ?>" 
                            src="?action=view_pdf&id=<?= htmlspecialchars($att['id']) ?>" 
                            width="100%" height="800px" 
                            style="border: none; display: none; background: #525659;">
                    </iframe>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Toggle Script -->
        <script>
            function togglePdfFrame(frameId) {
                const frame = document.getElementById(frameId);
                if (frame) {
                    frame.style.display = frame.style.display === 'none' ? 'block' : 'none';
                }
            }
        </script>
    <?php endif; ?>

</div>

<script>
    lucide.createIcons();

    document.addEventListener('DOMContentLoaded', () => {
        const chargeInputs = document.querySelectorAll('.charge-input');

        const formatMoney = (num) => {
            return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };

        const updateTotals = () => {
            let totalCharge = 0;
            let baseTotalPrincipal = 0;

            document.querySelectorAll('td[data-base]').forEach(cell => {
                baseTotalPrincipal += parseFloat(cell.getAttribute('data-base')) || 0;
            });

            chargeInputs.forEach(input => {
                const chargeVal = parseFloat(input.value) || 0;
                totalCharge += chargeVal;

                const row = input.closest('tr');
                const baseCell = row.querySelector('td[data-base]');
                const totalCell = row.querySelector('.calc-total-principal');
                
                if (baseCell && totalCell) {
                    const baseAmt = parseFloat(baseCell.getAttribute('data-base')) || 0;
                    totalCell.textContent = '₱' + formatMoney(baseAmt + chargeVal);
                }
            });

            const metaPrincipal = document.getElementById('meta-total-principal');
            const metaCharge    = document.getElementById('meta-total-charge');
            const metaCheque    = document.getElementById('meta-total-cheque');

            if (metaPrincipal) metaPrincipal.textContent = formatMoney(baseTotalPrincipal);
            if (metaCharge)    metaCharge.textContent    = formatMoney(totalCharge);
            if (metaCheque)    metaCheque.textContent    = formatMoney(baseTotalPrincipal + totalCharge);
        };

        chargeInputs.forEach(input => {
            input.addEventListener('input', updateTotals);
        });
    });
</script>
</body>
</html>