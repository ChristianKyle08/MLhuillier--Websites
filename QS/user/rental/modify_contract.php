<?php
session_start();
include '../../config/config.php';

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}
$userRole     = $_SESSION['user_role']   ?? '';
$userRegion   = $_SESSION['region']      ?? '';
$userArea     = $_SESSION['area']        ?? '';
$userMainzone = $_SESSION['mainzone']    ?? '';

// Build user condition dynamically
$userCondition = "";
if ($userRole === 'Am-Creator') {
    $userCondition = "AND region = '$userRegion' AND area = '$userArea'";
} elseif ($userRole === 'Rm-Reviewer') {
    $userCondition = "AND region = '$userRegion'";
} elseif (in_array($userRole, ['Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver'])) {
    $userCondition = "AND mainzone = '$userMainzone'";
}

// Keep your full query, just add $userCondition to each SELECT
$stmt = $conn->prepare("
    SELECT contract_number, dueDate_request_type AS request_type, transaction_date, 
           dueDate_request_reason AS reason, 
           new_due_date,
           
           -- new mobile numbers
           NULL AS new_authorize_mobileNumber, NULL AS new_mobile_number_l1, NULL AS new_mobile_number_l2,
           NULL AS new_mobile_number_l3, NULL AS new_mobile_number_l4, NULL AS new_mobile_number_l5,
           
           -- old lessor
           NULL AS l1_firstname, NULL AS l1_middlename, NULL AS l1_lastname, NULL AS l1_gender,
           NULL AS authorize_firstname, NULL AS authorize_middlename, NULL AS authorize_lastname, NULL AS authorize_gender,
           
           -- new lessor
           NULL AS new_l1_firstname, NULL AS new_l1_middlename, NULL AS new_l1_lastname, NULL AS new_l1_gender,
           NULL AS new_authorize_firstname, NULL AS new_authorize_middlename, NULL AS new_authorize_lastname, NULL AS new_authorize_gender,
           
           -- old mobile numbers
           NULL AS authorize_mobileNumber, NULL AS mobile_number_l1, NULL AS mobile_number_l2, NULL AS mobile_number_l3, NULL AS mobile_number_l4, NULL AS mobile_number_l5,
           
           -- status columns
           dueDate_request_status, NULL AS mobile_request_status, NULL AS cancel_request_status, NULL AS lessor_request_status,

           -- location columns
           area, region, mainzone
    FROM transactional 
    WHERE dueDate_request_reason IS NOT NULL $userCondition

    UNION ALL

    SELECT contract_number, mobile_request_type AS request_type, transaction_date, 
           mobile_request_reason AS reason,
           NULL AS new_due_date,
           
           -- new mobile numbers
           new_authorize_mobileNumber, new_mobile_number_l1, new_mobile_number_l2,
           new_mobile_number_l3, new_mobile_number_l4, new_mobile_number_l5,
           
           -- old lessor
           NULL,NULL,NULL,NULL,
           NULL,NULL,NULL,NULL,
           
           -- new lessor
           NULL,NULL,NULL,NULL,
           NULL,NULL,NULL,NULL,
           
           -- old mobile numbers
           authorize_mobileNumber, mobile_number_l1, mobile_number_l2, mobile_number_l3, mobile_number_l4, mobile_number_l5,
           
           -- status columns
           NULL, mobile_request_status, NULL, NULL,

           -- location columns
           area, region, mainzone
    FROM transactional 
    WHERE mobile_request_reason IS NOT NULL $userCondition

    UNION ALL

    SELECT contract_number, cancel_request_type AS request_type, transaction_date, 
           cancel_request_reason AS reason,
           NULL AS new_due_date,
           
           -- new mobile numbers
           NULL,NULL,NULL,NULL,NULL,NULL,
           
           -- old lessor
           NULL,NULL,NULL,NULL,
           NULL,NULL,NULL,NULL,
           
           -- new lessor
           NULL,NULL,NULL,NULL,
           NULL,NULL,NULL,NULL,
           
           -- old mobile numbers
           NULL,NULL,NULL,NULL,NULL,NULL,
           
           -- status columns
           NULL, NULL, cancel_request_status, NULL,

           -- location columns
           area, region, mainzone
    FROM transactional 
    WHERE cancel_request_reason IS NOT NULL $userCondition

    UNION ALL

    SELECT contract_number, lessor_request_type AS request_type, transaction_date, 
           lessor_request_reason AS reason,
           NULL AS new_due_date,
           
           -- new mobile numbers
           NULL,NULL,NULL,NULL,NULL,NULL,
           
           -- old lessor
           l1_firstname, l1_middlename, l1_lastname, l1_gender,
           authorize_firstname, authorize_middlename, authorize_lastname, authorize_gender,
           
           -- new lessor
           new_l1_firstname, new_l1_middlename, new_l1_lastname, new_l1_gender,
           new_authorize_firstname, new_authorize_middlename, new_authorize_lastname, new_authorize_gender,
           
           -- old mobile numbers
           NULL,NULL,NULL,NULL,NULL,NULL,
           
           -- status columns
           NULL, NULL, NULL, lessor_request_status,

           -- location columns
           area, region, mainzone
    FROM transactional 
    WHERE lessor_request_reason IS NOT NULL $userCondition

    ORDER BY contract_number, request_type, transaction_date DESC
");

$stmt->execute();
$result = $stmt->get_result();

$requests = [];
while ($row = $result->fetch_assoc()) {
    $contract = $row['contract_number'];
    $rawType  = (string)($row['request_type'] ?? '');
    $low      = strtolower($rawType);

    // Normalize request type
    if (strpos($low, 'lessor') !== false) {
        $kind = 'lessor';
    } elseif (strpos($low, 'mobile') !== false) {
        $kind = 'mobile';
    } elseif (strpos($low, 'due') !== false) {
        $kind = 'dueDate';
    } elseif (strpos($low, 'cancel') !== false) {
        $kind = 'cancel';
    } else {
        $kind = 'other';
    }

    // Keep original type for display
    $row['request_type_raw'] = $rawType;

    $requests[$contract][$kind][] = $row;
}
$messageHtml = ''; // collect messages here

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userRole = $_SESSION['user_role'] ?? '';

    $contract_number  = $_POST['contract_number'] ?? '';
    $transaction_date = $_POST['transaction_date'] ?? '';
    $request_type     = $_POST['request_type'] ?? '';

    // make arrays from comma-separated inputs
    $transaction_dates = array_values(array_filter(array_map('trim', explode(',', $transaction_date))));
    $request_types     = array_values(array_filter(array_map('trim', explode(',', $request_type))));

    // categories handled
    $categories = ['dueDate','mobile','cancel','lessor'];
    $action = null;           
    $category_selected = null;

    foreach ($categories as $cat) {
        if (isset($_POST['accept_' . $cat])) {
            $action = 'accept';
            $category_selected = $cat;
            break;
        }
        if (isset($_POST['approve_' . $cat])) {
            $action = 'approve';
            $category_selected = $cat;
            break;
        }
        if (isset($_POST['disapprove_' . $cat])) {
            $action = 'disapprove';
            $category_selected = $cat;
            break;
        }
    }

    if (empty($action) || empty($category_selected)) {
        $messageHtml = "<div class='alert alert-warning'>No valid action submitted.</div>";
    } elseif ($action === 'accept' && $userRole !== 'Rm-Reviewer') {
        $messageHtml = "<div class='alert alert-danger'>You are not authorized to accept requests.</div>";
    } elseif ($action === 'approve' && !in_array($userRole, ['Vpo-Reviewer', 'Vpo-Approver'])) {
        $messageHtml = "<div class='alert alert-danger'>You are not authorized to approve requests.</div>";
    } else {
        // map category -> columns
        $colMap = [
            'dueDate' => ['status' => 'dueDate_request_status', 'type' => 'dueDate_request_type'],
            'mobile'  => ['status' => 'mobile_request_status',  'type' => 'mobile_request_type'],
            'cancel'  => ['status' => 'cancel_request_status',  'type' => 'cancel_request_type'],
            'lessor'  => ['status' => 'lessor_request_status',  'type' => 'lessor_request_type'],
        ];

        // map category -> which columns to reset on disapprove
        $resetCols = [
            'dueDate' => ['dueDate_request_status', 'dueDate_request_type', 'dueDate_request_reason', 'new_due_date'],
            'mobile'  => [
                'mobile_request_status', 'mobile_request_type', 'mobile_request_reason',
                'new_mobile_number_l1','new_authorize_mobileNumber'
            ],
            'cancel'  => ['cancel_request_status', 'cancel_request_type', 'cancel_request_reason'],
            'lessor'  => [
                'lessor_request_status', 'lessor_request_type', 'lessor_request_reason',
                'new_l1_firstname','new_l1_middlename','new_l1_lastname','new_l1_gender',
                'new_authorize_firstname','new_authorize_middlename','new_authorize_lastname','new_authorize_gender'
            ]
        ];

        if (!isset($colMap[$category_selected])) {
            $messageHtml = "<div class='alert alert-danger'>Invalid request category.</div>";
        } else {
            $statusColumn = $colMap[$category_selected]['status'];
            $typeColumn   = $colMap[$category_selected]['type'];

            // assign new status
            if ($action === 'disapprove') {
                $newStatus = 'Disapproved';
            } elseif ($action === 'accept') {
                $newStatus = 'Pending VPO';
            } elseif ($action === 'approve') {
                $newStatus = 'Approved';
            }

            if (empty($contract_number) || empty($transaction_dates)) {
                $messageHtml = "<div class='alert alert-warning'>Missing contract number or transaction date(s).</div>";
            } else {
                // normalize mapping transaction_date <-> request_type
                $pairs = [];
                if (count($request_types) === 1 && count($transaction_dates) >= 1) {
                    $type_single = $request_types[0];
                    foreach ($transaction_dates as $d) {
                        $pairs[] = [$d, $type_single];
                    }
                } elseif (count($request_types) === count($transaction_dates)) {
                    foreach ($transaction_dates as $i => $d) {
                        $pairs[] = [$d, $request_types[$i]];
                    }
                } elseif (empty($request_types)) {
                    foreach ($transaction_dates as $d) {
                        $pairs[] = [$d, null];
                    }
                } else {
                    $firstType = $request_types[0] ?? null;
                    foreach ($transaction_dates as $i => $d) {
                        $pairs[] = [$d, $request_types[$i] ?? $firstType];
                    }
                }

                $successCount = 0;
                $failCount = 0;
                $errors = [];

                foreach ($pairs as [$tDate, $rType]) {
                    $stmt = null;

                    if ($action === 'disapprove') {
                        // build SET clause dynamically
                        $columns = ["`$statusColumn` = NULL", "`$typeColumn` = NULL"];
                        foreach ($resetCols[$category_selected] as $col) {
                            $columns[] = "`$col` = NULL";
                        }
                        $setClause = implode(", ", $columns);

                        $sql = "UPDATE transactional SET $setClause
                                WHERE contract_number = ? AND transaction_date = ?"
                                . ($rType !== null ? " AND `$typeColumn` = ?" : "");
                        $stmt = $conn->prepare($sql);
                        if ($rType !== null) {
                            $stmt->bind_param("sss", $contract_number, $tDate, $rType);
                        } else {
                            $stmt->bind_param("ss", $contract_number, $tDate);
                        }
                    } else {
                        // approve / accept path
                        if ($rType === null) {
                            $sql = "UPDATE transactional SET `$statusColumn` = ? 
                                    WHERE contract_number = ? AND transaction_date = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("sss", $newStatus, $contract_number, $tDate);
                        } else {
                            $sql = "UPDATE transactional SET `$statusColumn` = ? 
                                    WHERE contract_number = ? AND transaction_date = ? AND `$typeColumn` = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("ssss", $newStatus, $contract_number, $tDate, $rType);
                        }
                    }

                    if ($stmt && $stmt->execute()) {
                        if ($stmt->affected_rows > 0) {
                            $successCount++;

                            if ($action === 'approve') {
                                // Rule 1: cancel_payment approved → set status = Cancelled
                                if ($category_selected === 'cancel' && $rType === 'cancel_payment') {
                                    $sql2 = "UPDATE transactional 
                                             SET status = 'Cancelled'
                                             WHERE contract_number = ? 
                                               AND transaction_date = ? 
                                               AND cancel_request_type = 'cancel_payment' 
                                               AND cancel_request_status = 'Approved'";
                                    $stmt2 = $conn->prepare($sql2);
                                    if ($stmt2) {
                                        $stmt2->bind_param("ss", $contract_number, $tDate);
                                        $stmt2->execute();
                                        $stmt2->close();
                                    }
                                }

                                // Rule 2: clear extract_request_status if any request approved
                                $sql3 = "UPDATE transactional 
                                         SET extract_request_status = NULL
                                         WHERE contract_number = ? 
                                           AND transaction_date = ? 
                                           AND (
                                               dueDate_request_status = 'Approved' 
                                               OR mobile_request_status = 'Approved'
                                               OR cancel_request_status = 'Approved'
                                               OR lessor_request_status = 'Approved'
                                           )";
                                if ($rType !== null) {
                                    $sql3 .= " AND (
                                                   dueDate_request_type = ? 
                                                   OR mobile_request_type = ? 
                                                   OR cancel_request_type = ? 
                                                   OR lessor_request_type = ?
                                               )";
                                    $stmt3 = $conn->prepare($sql3);
                                    if ($stmt3) {
                                        $stmt3->bind_param("ssssss", $contract_number, $tDate, $rType, $rType, $rType, $rType);
                                        $stmt3->execute();
                                        $stmt3->close();
                                    }
                                } else {
                                    $stmt3 = $conn->prepare($sql3);
                                    if ($stmt3) {
                                        $stmt3->bind_param("ss", $contract_number, $tDate);
                                        $stmt3->execute();
                                        $stmt3->close();
                                    }
                                }
                            }

                        } else {
                            $failCount++;
                            $errors[] = "No matching row for date={$tDate}, type=" . ($rType ?? '[any]');
                        }
                    } else {
                        $failCount++;
                        $errors[] = "SQL error for date={$tDate}, type=" . ($rType ?? '[any]');
                    }

                    if ($stmt) $stmt->close();
                }

                // Build message
                $messageHtml = '';
                if ($successCount > 0) {
                    $msg = ($action === 'disapprove') ? 'Disapproved and cleared' : "Updated to $newStatus";
                    $messageHtml .= "<div class='alert alert-success'>$msg $successCount request(s).</div>";
                }
                if ($failCount > 0) {
                    $messageHtml .= "<div class='alert alert-danger'>Failed to update $failCount request(s).</div>";
                    foreach ($errors as $e) {
                        $messageHtml .= "<div class='small text-danger'>" . htmlspecialchars($e) . "</div>";
                    }
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../../assets/images/rental_logo.png" type="image/x-icon">
    <title>ML Rental - Request Edit Transaction</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/poppins.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar.css">
    <style>
        .request-option { cursor: pointer; transition: transform .2s ease, box-shadow .2s ease; }
        .request-option:hover { transform: translateY(-4px); box-shadow: 0 6px 16px rgba(0,0,0,0.15); }
        .history-card { transition: transform .2s ease; cursor: pointer; }
        .history-card:hover { transform: scale(1.03); }
        .timeline .event { padding: .75rem 0; border-bottom: 1px solid #f1f1f1; }
        .timeline .event:last-child { border-bottom: 0; }
        .event-icon { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; }
    </style>
</head>
<body>
<?php include('navbar.php'); ?>

<div id="mainContent">
    <!-- Sidebar Toggle -->
    <button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
        <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
        <span class="fw-normal">Menu</span>
    </button>
    <div class="container py-3">

    <?php if (isset($userRole) && $userRole === 'Am-Creator'): ?>
    <!-- Heading -->
    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-4">
        <h2 class="fw-bold text-danger d-flex align-items-center mb-0">
            <i class="bi bi-pencil-square me-2"></i> Request Edit <span class="text-dark ms-1">COL</span>
        </h2>
        <button class="btn btn-danger btn-sm rounded-pill shadow-sm d-flex align-items-center"
                data-bs-toggle="collapse"
                data-bs-target="#requestOptions">
            <i class="bi bi-plus-lg me-1"></i> New Request
        </button>
    </div>

    <!-- Collapsible Request Options -->
    <div class="collapse" id="requestOptions">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card shadow-sm h-100 request-option" data-request="lessor">
                    <div class="card-body text-center">
                        <i class="bi bi-person-lines-fill text-primary fs-2 mb-2"></i>
                        <h6 class="fw-normal">Change Lessor Name</h6>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm h-100 request-option" data-request="mobile">
                    <div class="card-body text-center">
                        <i class="bi bi-phone text-info fs-2 mb-2"></i>
                        <h6 class="fw-normal">Change Mobile Number</h6>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm h-100 request-option" data-request="dueDate">
                    <div class="card-body text-center">
                        <i class="bi bi-calendar-event text-warning fs-2 mb-2"></i>
                        <h6 class="fw-normal">Change Payment Due Date</h6>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm h-100 request-option" data-request="cancel">
                    <div class="card-body text-center">
                        <i class="bi bi-x-circle text-danger fs-2 mb-2"></i>
                        <h6 class="fw-normal">Cancel Payment</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

        <!-- Request History -->
<div class="mt-5">
    <h4 class="fw-bold mb-3"><i class="bi bi-clock-history text-danger me-2"></i> Request Overview</h4>

    <?php if (!empty($requests)): ?>
        <div class="row g-3">
            <?php
            // Icon mapping for request types
            $icons = [
                "lessor"  => ["label" => "Lessor Change", "icon" => "person-lines-fill", "color" => "text-primary"],
                "mobile"  => ["label" => "Mobile Change", "icon" => "phone", "color" => "text-info"],
                "dueDate" => ["label" => "Due Date Change", "icon" => "calendar-event", "color" => "text-warning"],
                "cancel"  => ["label" => "Cancel Payment", "icon" => "x-circle", "color" => "text-danger"],
                "csv"     => ["label" => "Re-Extract CSV", "icon" => "file-earmark-spreadsheet", "color" => "text-success"],
                "other"   => ["label" => "Other", "icon" => "question-circle", "color" => "text-muted"]
            ];

            foreach ($requests as $contract => $types):
                foreach ($types as $kind => $rows):

                    // Determine the correct status column
                    $statusColumn = match($kind) {
                        'dueDate' => 'dueDate_request_status',
                        'mobile'  => 'mobile_request_status',
                        'cancel'  => 'cancel_request_status',
                        'lessor'  => 'lessor_request_status',
                        default   => ''
                    };

                    // Skip this request type if all rows are Approved
                    $hasPending = false;
                    foreach ($rows as $r) {
                        if (!in_array(($r[$statusColumn] ?? ''), ['Approved', '', null], true)) {
                            $hasPending = true;
                            break;
                        }
                    }
                    if (!$hasPending) continue;

                    // Safe modal ID
                    $safeContract = preg_replace('/[^a-zA-Z0-9_-]/', '_', $contract);
                    $modalId = $kind . "_Modal_" . $safeContract;

                    // Icon & background class
                    $iconData = $icons[$kind] ?? $icons['other'];
                    $bgClass = preg_replace('/^text-/', 'bg-', $iconData['color']);
                    if ($bgClass === $iconData['color']) $bgClass = 'bg-secondary';
            ?>
                    <!-- Card -->
                    <div class="col-md-6">
                        <div class="card shadow-sm history-card" data-bs-toggle="modal" data-bs-target="#<?= htmlspecialchars($modalId) ?>">
                            <div class="card-body">
                                <h6>
                                    <i class="bi bi-<?= htmlspecialchars($iconData['icon']) ?> <?= htmlspecialchars($iconData['color']) ?> me-2"></i>
                                    <?= htmlspecialchars($iconData['label']) ?>
                                </h6>
                                <p class="small mb-0"><strong>Contract #:</strong> <?= htmlspecialchars($contract) ?></p>
                                <p class="text-muted small mb-0">Click to view details</p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal -->
<div class="modal fade" id="<?= htmlspecialchars($modalId) ?>" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-<?= htmlspecialchars($iconData['icon']) ?> <?= htmlspecialchars($iconData['color']) ?> me-2"></i>
                    <?= htmlspecialchars($iconData['label']) ?> History
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="window.location.href='modify_contract.php'"></button>
            </div>
            <div class="modal-body">
                <p class="fw-bold mb-3">
                    <i class="bi bi-file-text me-2 text-secondary"></i>
                    Contract #: <?= htmlspecialchars($contract) ?>
                </p>

                <div class="timeline">
                    <?php
                    $latestPendingRow = null;

                    foreach ($rows as $r):
                        $status = $r[$statusColumn] ?? '';
                        if ($status === 'Approved') continue; // Skip Approved requests

                        $latestPendingRow = $r;
                    ?>
                        <!-- Timeline Event -->
                        <div class="event d-flex mb-3">
                            <div class="me-3">
                                <div class="event-icon <?= htmlspecialchars($bgClass) ?> text-white">
                                    <i class="bi bi-calendar-event"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1 text-danger"><?= htmlspecialchars($r['transaction_date']) ?></h6>

                                <?php if (!empty($r['request_type'])): ?>
                                    <p class="mb-1 small text-primary fw-bold">
                                        <i class="bi bi-tag me-1"></i>
                                        Request Type: <?= htmlspecialchars($r['request_type']) ?>
                                    </p>
                                <?php endif; ?>

                                <p class="mb-1 small text-danger fw-bold">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Status: <?= htmlspecialchars($status) ?>
                                </p>

                                <p class="mb-0 small text-muted">
                                    <i class="bi bi-chat-left-text me-1"></i>
                                    <?= nl2br(htmlspecialchars($r['reason'])) ?>
                                </p>

                                <?php if ($r['request_type'] === 'change_lessor_name'): ?>
                                    <!-- Recent Lessor -->
                                    <?php if (!empty($r['l1_firstname']) || !empty($r['l1_lastname'])): ?>
                                        <div class="mt-3 p-2 border rounded bg-light">
                                            <p class="fw-bold text-secondary mb-1">
                                                <i class="bi bi-person-vcard me-1"></i> Recent Lessor
                                            </p>
                                            <p class="mb-0 small">
                                                <?= htmlspecialchars(trim($r['l1_firstname'] . ' ' . $r['l1_middlename'] . ' ' . $r['l1_lastname'])) ?>
                                                (<?= htmlspecialchars($r['l1_gender']) ?>)
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- New Lessor -->
                                    <?php if (!empty($r['new_l1_firstname']) || !empty($r['new_l1_lastname'])): ?>
                                        <div class="mt-2 p-2 border rounded bg-light">
                                            <p class="fw-bold text-success mb-1">
                                                <i class="bi bi-person-plus me-1"></i> New Lessor
                                            </p>
                                            <p class="mb-0 small">
                                                <?= htmlspecialchars(trim($r['new_l1_firstname'] . ' ' . $r['new_l1_middlename'] . ' ' . $r['new_l1_lastname'])) ?>
                                                (<?= htmlspecialchars($r['new_l1_gender']) ?>)
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Authorized Person -->
                                    <?php if (!empty($r['authorize_firstname']) || !empty($r['authorize_lastname'])): ?>
                                        <div class="mt-3 p-2 border rounded bg-light">
                                            <p class="fw-bold text-secondary mb-1">
                                                <i class="bi bi-person-badge me-1"></i> Recent Authorized to Claim
                                            </p>
                                            <p class="mb-0 small">
                                                <?= htmlspecialchars(trim($r['authorize_firstname'] . ' ' . $r['authorize_middlename'] . ' ' . $r['authorize_lastname'])) ?>
                                                (<?= htmlspecialchars($r['authorize_gender']) ?>)
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($r['new_authorize_firstname']) || !empty($r['new_authorize_lastname'])): ?>
                                        <div class="mt-2 p-2 border rounded bg-light">
                                            <p class="fw-bold text-success mb-1">
                                                <i class="bi bi-person-badge-fill me-1"></i> New Authorized to Claim
                                            </p>
                                            <p class="mb-0 small">
                                                <?= htmlspecialchars(trim($r['new_authorize_firstname'] . ' ' . $r['new_authorize_middlename'] . ' ' . $r['new_authorize_lastname'])) ?>
                                                (<?= htmlspecialchars($r['new_authorize_gender']) ?>)
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($r['request_type'] === 'change_mobile'): ?>
                                    <!-- Recent Lessor Mobile -->
                                    <?php if (!empty($r['mobile_number_l1'])): ?>
                                        <div class="mt-3 p-2 border rounded bg-light">
                                            <p class="fw-bold text-secondary mb-1">
                                                <i class="bi bi-telephone me-1"></i> Recent Lessor 1 Mobile Number
                                            </p>
                                            <p class="mb-0 small"><?= htmlspecialchars($r['mobile_number_l1']) ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- New Lessor Mobile -->
                                    <?php if (!empty($r['new_mobile_number_l1'])): ?>
                                        <div class="mt-2 p-2 border rounded bg-light">
                                            <p class="fw-bold text-success mb-1">
                                                <i class="bi bi-telephone-plus me-1"></i> New Lessor 1 Mobile Number
                                            </p>
                                            <p class="mb-0 small"><?= htmlspecialchars($r['new_mobile_number_l1']) ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Recent Authorize Mobile -->
                                    <?php if (!empty($r['authorize_mobileNumber'])): ?>
                                        <div class="mt-3 p-2 border rounded bg-light">
                                            <p class="fw-bold text-secondary mb-1">
                                                <i class="bi bi-person-lines-fill me-1"></i> Recent Authorize Mobile Number
                                            </p>
                                            <p class="mb-0 small"><?= htmlspecialchars($r['authorize_mobileNumber']) ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- New Authorize Mobile -->
                                    <?php if (!empty($r['new_authorize_mobileNumber'])): ?>
                                        <div class="mt-2 p-2 border rounded bg-light">
                                            <p class="fw-bold text-success mb-1">
                                                <i class="bi bi-person-lines-fill me-1"></i> New Authorize Mobile Number
                                            </p>
                                            <p class="mb-0 small"><?= htmlspecialchars($r['new_authorize_mobileNumber']) ?></p>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($r['request_type'] === 'change_due_date'): ?>

                                    <!-- Recent Due Date -->
                                    <?php if (!empty($r['due_date'])): ?>
                                        <div class="mt-3 p-2 border rounded bg-light">
                                            <p class="fw-bold text-secondary mb-1">
                                                <i class="bi bi-calendar me-1"></i> Recent Due Date
                                            </p>
                                            <p class="mb-0 small">
                                                <?= htmlspecialchars(date('F d, Y', strtotime($r['due_date']))) ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <!-- New Due Date -->
                                    <?php if (!empty($r['new_due_date'])): ?>
                                        <div class="mt-2 p-2 border rounded bg-light">
                                            <p class="fw-bold text-success mb-1">
                                                <i class="bi bi-calendar-check me-1"></i> New Due Date
                                            </p>
                                            <p class="mb-0 small">
                                                <?= htmlspecialchars(date('F d, Y', strtotime($r['new_due_date']))) ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>

                                    <?php endif; ?>

                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Action Button for Pending Requests -->
<?php if ($latestPendingRow):
    $status = $latestPendingRow[$statusColumn] ?? '';
    $btns = []; // ✅ Support multiple buttons
    $showDisapprove = false; // ✅ Track if approve/accept is shown

    // Accept button (RM only)
    if ($status === 'Pending RM' && $userRole === 'Rm-Reviewer') {
        $btns[] = [
            'name'  => 'accept_' . $kind,
            'class' => 'btn btn-success me-2',
            'icon'  => 'bi-check-circle',
            'text'  => 'Accept ' . ucfirst($kind) . ' Request'
        ];
        $showDisapprove = true; // ✅ Allow Disapprove
    }

    // Approve button (VPO Reviewer or Approver)
    if (
        ($status === 'Pending VPO' && in_array($userRole, ['Vpo-Reviewer', 'Vpo-Approver']))
    ) {
        $btns[] = [
            'name'  => 'approve_' . $kind,
            'class' => 'btn btn-primary me-2',
            'icon'  => 'bi-hand-thumbs-up',
            'text'  => 'Approve ' . ucfirst($kind) . ' Request'
        ];
        $showDisapprove = true; // ✅ Allow Disapprove
    }

    // ✅ Disapprove button only if approve/accept is displayed
    if ($showDisapprove) {
        $btns[] = [
            'name'  => 'disapprove_' . $kind,
            'class' => 'btn btn-danger',
            'icon'  => 'bi-x-circle',
            'text'  => 'Disapprove ' . ucfirst($kind) . ' Request'
        ];
    }

    if ($btns):
                    ?>
    <form method="post" class="mt-3">
        <input type="hidden" name="contract_number" value="<?= htmlspecialchars($contract) ?>">

        <?php
            // Collect all transaction dates for this kind that are NOT Approved
            $allDates = [];
            $allTypes = [];
            foreach ($rows as $r) {
                $status = $r[$statusColumn] ?? '';
                if ($status === 'Approved') continue;
                $allDates[] = $r['transaction_date'];
                if (!empty($r['request_type'])) {
                    $allTypes[] = $r['request_type'];
                }
            }
        ?>
        
        <input type="hidden" name="transaction_date" value="<?= htmlspecialchars(implode(',', $allDates)) ?>">
        <input type="hidden" name="request_type" value="<?= htmlspecialchars(implode(',', $allTypes)) ?>">

        <?php foreach ($btns as $btn): ?>
        <button type="submit" name="<?= htmlspecialchars($btn['name']) ?>" class="<?= htmlspecialchars($btn['class']) ?>">
            <i class="bi <?= htmlspecialchars($btn['icon']) ?> me-1"></i>
            <?= htmlspecialchars($btn['text']) ?>
        </button>
    <?php endforeach; ?>
    </form>
<?php endif; endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>


            <?php
                endforeach;
            endforeach;
            ?>
        </div>
    <?php else: ?>
        <div class="alert alert-light border text-center">
            <i class="bi bi-exclamation-circle text-muted me-2"></i>
            No request history available.
        </div>
    <?php endif; ?>
</div>

    </div>
</div>
<!-- Bootstrap Modal -->
<div class="modal fade" id="resultModal" tabindex="-1" aria-labelledby="resultModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="resultModalLabel">Request Update Result</h5>
        <button type="button" class="btn-close" id="closeModalBtn" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?= $messageHtml ?>
      </div>
    </div>
  </div>
</div>

<?php if (!empty($messageHtml)): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var myModal = new bootstrap.Modal(document.getElementById('resultModal'));
        myModal.show();

        // redirect when close button clicked
        document.getElementById("closeModalBtn").addEventListener("click", function() {
            window.location.href = "modify_contract.php";
        });
    });
</script>
<?php endif; ?>


<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4">
      <div class="modal-body">
        <div class="text-center mb-3">
          <i class="bi bi-box-arrow-right text-danger" style="font-size: 3rem;"></i>
        </div>
        <h5 class="mb-2">Logging Out</h5>
        <p class="text-muted mb-3">Please wait while we securely log you out...</p>
        <div class="progress" style="height: 8px;">
          <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 100%;"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const redirects = {
        lessor: "change_lessor.php",
        mobile: "change_mobile.php",
        dueDate: "change_due_date.php",
        csv: "re_extract_csv.php",
        cancel: "cancel_payment.php"
    };
    document.querySelectorAll(".request-option").forEach(card => {
        card.addEventListener("click", function () {
            const requestType = this.dataset.request;
            if (redirects[requestType]) {
                window.location.href = redirects[requestType];
            }
        });
    });
});

const toggleBtn = document.getElementById('toggleSidebar');
  const sidebar = document.getElementById('sidebarMenu');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
  });
document.getElementById('logoutLink')?.addEventListener('click', function (e) {
  e.preventDefault();
  const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'), {
    backdrop: 'static',
    keyboard: false
  });
  logoutModal.show();
  setTimeout(() => window.location.href = '../../logout.php', 2500);
});
</script>
</body>
</html>
