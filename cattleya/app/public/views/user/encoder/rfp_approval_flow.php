<?php

require_once __DIR__ . '/../../../../config/database.php';
require __DIR__ . '/../../includes/session_check.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success_msg = '';
$error_msg   = '';

$logged_in_user_id =
    $_SESSION['user_id']
    ?? $_SESSION['id']
    ?? null;

$logged_in_role =
    strtolower(
        trim(
            $_SESSION['role'] ?? 'encoder'
        )
    );

if (!isset($_SESSION['rfp_sigs'])) {
    $_SESSION['rfp_sigs'] = [];
}


/**
 * ============================================================
 * EXACT PAYMENT SIGNATURE COLUMN MAP
 * ============================================================
 *
 * These are the ONLY columns allowed.
 *
 * No:
 *
 * encoder_id
 * reviewer_id
 * auditor_id
 * cfo_id
 *
 * will ever be generated.
 */
function getPaymentSignatureColumns(
    $role,
    $stage
) {

    $role =
        strtolower(
            trim(
                (string)$role
            )
        );

    $stage =
        strtolower(
            trim(
                (string)$stage
            )
        );

    $column_map = [

        'broker' => [

            'encoder' => [
                'id'  => 'broker_encoder_id',
                'sig' => 'broker_encoder_sig'
            ],

            'reviewer' => [
                'id'  => 'broker_reviewer_id',
                'sig' => 'broker_reviewer_sig'
            ],

            'auditor' => [
                'id'  => 'broker_auditor_id',
                'sig' => 'broker_auditor_sig'
            ],

            'cfo' => [
                'id'  => 'broker_cfo_id',
                'sig' => 'broker_cfo_sig'
            ]
        ],

        'um' => [

            'encoder' => [
                'id'  => 'um_encoder_id',
                'sig' => 'um_encoder_sig'
            ],

            'reviewer' => [
                'id'  => 'um_reviewer_id',
                'sig' => 'um_reviewer_sig'
            ],

            'auditor' => [
                'id'  => 'um_auditor_id',
                'sig' => 'um_auditor_sig'
            ],

            'cfo' => [
                'id'  => 'um_cfo_id',
                'sig' => 'um_cfo_sig'
            ]
        ],

        'agent' => [

            'encoder' => [
                'id'  => 'agent_encoder_id',
                'sig' => 'agent_encoder_sig'
            ],

            'reviewer' => [
                'id'  => 'agent_reviewer_id',
                'sig' => 'agent_reviewer_sig'
            ],

            'auditor' => [
                'id'  => 'agent_auditor_id',
                'sig' => 'agent_auditor_sig'
            ],

            'cfo' => [
                'id'  => 'agent_cfo_id',
                'sig' => 'agent_cfo_sig'
            ]
        ]
    ];

    return
        $column_map[$role][$stage]
        ?? null;
}


/**
 * ============================================================
 * PARSE COMMISSION KEY
 * ============================================================
 *
 * Supported:
 *
 * payment_id|customer_id|due_date|role
 *
 * customer_id|due_date|role
 */
function parseCommissionKey($key)
{
    $parts =
        explode(
            '|',
            trim(
                (string)$key
            )
        );

    $count =
        count($parts);

    if ($count === 4) {

        return [

            'payment_id' =>
                is_numeric($parts[0])
                    ? (int)$parts[0]
                    : null,

            'customer_id' =>
                trim($parts[1]),

            'due_date' =>
                trim($parts[2]),

            'role' =>
                strtolower(
                    trim($parts[3])
                )
        ];
    }

    if ($count === 3) {

        return [

            'payment_id' =>
                null,

            'customer_id' =>
                trim($parts[0]),

            'due_date' =>
                trim($parts[1]),

            'role' =>
                strtolower(
                    trim($parts[2])
                )
        ];
    }

    return null;
}


/**
 * ============================================================
 * USER DETAILS + SIGNATURE
 * ============================================================
 */
function getUserDetailsById(
    $pdo,
    $user_id
) {

    if (empty($user_id)) {
        return null;
    }

    try {

        $stmt =
            $pdo->prepare("
                SELECT
                    id,
                    first_name,
                    last_name,
                    signature,
                    signature_type,
                    role
                FROM users
                WHERE id = ?
                LIMIT 1
            ");

        $stmt->execute([
            $user_id
        ]);

        $user =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$user) {
            return null;
        }

        $fullname =
            strtoupper(
                trim(
                    ($user['first_name'] ?? '') .
                    ' ' .
                    ($user['last_name'] ?? '')
                )
            );

        $sig_data_uri = '';

        if (!empty($user['signature'])) {

            $sig =
                $user['signature'];

            if (
                strpos(
                    $sig,
                    'data:image/'
                ) === 0
            ) {

                $sig_data_uri =
                    $sig;

            } else {

                $mime =
                    !empty(
                        $user['signature_type']
                    )
                        ? trim(
                            $user['signature_type']
                        )
                        : 'image/png';

                $sig_data_uri =
                    'data:' .
                    $mime .
                    ';base64,' .
                    base64_encode($sig);
            }
        }

        return [

            'user_id' =>
                $user['id'],

            'name' =>
                $fullname,

            'sig_src' =>
                $sig_data_uri,

            'role' =>
                strtoupper(
                    str_replace(
                        '_',
                        ' ',
                        $user['role'] ?? ''
                    )
                ),

            'raw_role' =>
                strtolower(
                    $user['role'] ?? ''
                )
        ];

    } catch (PDOException $e) {

        error_log(
            'Signature fetch error: ' .
            $e->getMessage()
        );

        return null;
    }
}


/**
 * ============================================================
 * UPDATE PAYMENT SIGNATURE
 * ============================================================
 */
function updatePaymentSignatureRecord(
    $pdo,
    $rfp_data,
    $stage,
    $user_id,
    $sig_src
) {

    if (
        !$rfp_data ||
        empty(
            $rfp_data['selected_keys']
        )
    ) {

        throw new RuntimeException(
            'No selected commission/payment records were found.'
        );
    }

    $stage =
        strtolower(
            trim(
                (string)$stage
            )
        );

    if (!in_array(
        $stage,
        [
            'encoder',
            'reviewer',
            'auditor',
            'cfo'
        ],
        true
    )) {

        throw new RuntimeException(
            'Invalid approval stage.'
        );
    }

    if (empty($user_id)) {

        throw new RuntimeException(
            'Invalid user ID.'
        );
    }

    if (empty($sig_src)) {

        throw new RuntimeException(
            'The user does not have a valid signature image.'
        );
    }

    foreach (
        $rfp_data['selected_keys']
        as $key
    ) {

        $parsed =
            parseCommissionKey(
                $key
            );

        if (!$parsed) {

            throw new RuntimeException(
                'Invalid commission reference: ' .
                $key
            );
        }

        $role =
            $parsed['role'];

        $payment_id =
            $parsed['payment_id'];

        $customer_id =
            $parsed['customer_id'];

        $due_date =
            $parsed['due_date'];

        if (!in_array(
            $role,
            [
                'agent',
                'broker',
                'um'
            ],
            true
        )) {

            throw new RuntimeException(
                'Invalid commission role: ' .
                $role
            );
        }

        $columns =
            getPaymentSignatureColumns(
                $role,
                $stage
            );

        if (!$columns) {

            throw new RuntimeException(
                "Invalid role/stage combination: {$role}/{$stage}"
            );
        }

        $id_col =
            $columns['id'];

        $sig_col =
            $columns['sig'];


        /**
         * ======================================================
         * UPDATE BY PAYMENT ID
         * ======================================================
         */
        if (!empty($payment_id)) {

            $sql = "
                UPDATE payments
                SET
                    `{$id_col}` = ?,
                    `{$sig_col}` = ?
                WHERE id = ?
                LIMIT 1
            ";

            $stmt =
                $pdo->prepare($sql);

            $stmt->execute([
                $user_id,
                $sig_src,
                $payment_id
            ]);

            $check =
                $pdo->prepare("
                    SELECT id
                    FROM payments
                    WHERE id = ?
                    LIMIT 1
                ");

            $check->execute([
                $payment_id
            ]);

            if (!$check->fetchColumn()) {

                throw new RuntimeException(
                    "Payment ID {$payment_id} was not found."
                );
            }

        } else {

            /**
             * ==================================================
             * FALLBACK CUSTOMER + DUE DATE
             * ==================================================
             */
            $sql = "
                UPDATE payments
                SET
                    `{$id_col}` = ?,
                    `{$sig_col}` = ?
                WHERE customer_id = ?
                  AND due_date = ?
                LIMIT 1
            ";

            $stmt =
                $pdo->prepare($sql);

            $stmt->execute([
                $user_id,
                $sig_src,
                $customer_id,
                $due_date
            ]);

            $check =
                $pdo->prepare("
                    SELECT id
                    FROM payments
                    WHERE customer_id = ?
                      AND due_date = ?
                    LIMIT 1
                ");

            $check->execute([
                $customer_id,
                $due_date
            ]);

            if (!$check->fetchColumn()) {

                throw new RuntimeException(
                    "No payment record found for customer {$customer_id}."
                );
            }
        }
    }
}


if (!function_exists(
    'updateRoleSignature'
)) {

    function updateRoleSignature(
        $pdo,
        $rfp_data,
        $stage,
        $user_id,
        $sig_src
    ) {

        updatePaymentSignatureRecord(
            $pdo,
            $rfp_data,
            $stage,
            $user_id,
            $sig_src
        );
    }
}


/**
 * ============================================================
 * SEGREGATION OF DUTIES
 * ============================================================
 */
function hasUserAlreadySigned(
    $user_id,
    $sigs
) {

    if (
        empty($user_id) ||
        empty($sigs)
    ) {

        return false;
    }

    foreach (
        $sigs
        as $stage =>
        $sig_info
    ) {

        if (
            isset(
                $sig_info['user_id']
            ) &&
            (string)$sig_info['user_id'] ===
            (string)$user_id
        ) {

            return true;
        }
    }

    return false;
}


/**
 * ============================================================
 * UPDATE SELECTED RFP REQUEST STATUS
 * ============================================================
 *
 * Only rr.id and rr.status are used.
 */
function updateRfpRequestStatuses(
    $pdo,
    $rfp_ids,
    $new_status
) {

    if (empty($rfp_ids)) {
        return;
    }

    $rfp_ids =
        array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $rfp_ids
                    ),
                    function ($id) {
                        return $id > 0;
                    }
                )
            )
        );

    if (empty($rfp_ids)) {
        return;
    }

    $allowed_statuses = [
        'pending_review',
        'pending_audit',
        'pending_cfo',
        'released'
    ];

    if (!in_array(
        $new_status,
        $allowed_statuses,
        true
    )) {

        throw new RuntimeException(
            'Invalid RFP status.'
        );
    }

    $placeholders =
        implode(
            ',',
            array_fill(
                0,
                count($rfp_ids),
                '?'
            )
        );

    $sql = "
        UPDATE rfp_requests
        SET status = ?
        WHERE id IN ({$placeholders})
    ";

    $params =
        array_merge(
            [$new_status],
            $rfp_ids
        );

    $stmt =
        $pdo->prepare($sql);

    $stmt->execute(
        $params
    );
}


/**
 * ============================================================
 * 1. GENERATE FLEXIBLE RFP
 * ============================================================
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'generate_flexible_rfp'
) {

    $selected_commissions =
        $_POST['selected_commissions']
        ?? [];

    if (
        empty(
            $selected_commissions
        )
    ) {

        header(
            'Location: commission_release.php'
        );

        exit;
    }

    $total_amount = 0;

    $items = [];

    $payees = [];

    $item_no = 1;

    $valid_keys = [];

    foreach (
        $selected_commissions
        as $key
    ) {

        $parsed =
            parseCommissionKey(
                $key
            );

        if (!$parsed) {
            continue;
        }

        $role =
            $parsed['role'];

        $payment_id =
            $parsed['payment_id'];

        $customer_id =
            $parsed['customer_id'];

        $due_date =
            $parsed['due_date'];

        if (!in_array(
            $role,
            [
                'agent',
                'broker',
                'um'
            ],
            true
        )) {

            continue;
        }

        $amount_col =
            $role .
            '_commission_amount';

        $status_col =
            $role .
            '_commission_status';

        $name_col =
            $role .
            '_fullname';

        try {

            /**
             * ==================================================
             * ALWAYS USE PAYMENT ID WHEN AVAILABLE
             * ==================================================
             */
            if (!empty($payment_id)) {

                $stmt =
                    $pdo->prepare("
                        SELECT
                            p.id AS payment_id,
                            p.customer_id,
                            s.customer_fullname,
                            p.due_date,
                            s.{$name_col} AS payee_person,
                            p.{$amount_col} AS comm_amount

                        FROM payments p

                        INNER JOIN sales s
                            ON p.sale_id = s.sale_id

                        WHERE p.id = ?
                          AND COALESCE(
                              p.{$status_col},
                              ''
                          ) != 'Released'

                        LIMIT 1
                    ");

                $stmt->execute([
                    $payment_id
                ]);

            } else {

                $stmt =
                    $pdo->prepare("
                        SELECT
                            p.id AS payment_id,
                            p.customer_id,
                            s.customer_fullname,
                            p.due_date,
                            s.{$name_col} AS payee_person,
                            p.{$amount_col} AS comm_amount

                        FROM payments p

                        INNER JOIN sales s
                            ON p.sale_id = s.sale_id

                        WHERE p.customer_id = ?
                          AND p.due_date = ?
                          AND COALESCE(
                              p.{$status_col},
                              ''
                          ) != 'Released'

                        LIMIT 1
                    ");

                $stmt->execute([
                    $customer_id,
                    $due_date
                ]);
            }

            $row =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );

            if (
                $row &&
                (float)$row['comm_amount'] > 0
            ) {

                $role_label =
                    strtoupper(
                        $role === 'um'
                            ? 'UNIT MANAGER'
                            : $role
                    );

                $payee_individual =
                    strtoupper(
                        $row['payee_person']
                        ?? 'N/A'
                    );

                $payees[
                    $payee_individual
                ] = true;

                $desc =
                    "{$role_label} COMMISSION RELEASE - " .
                    "BENEFICIARY: {$payee_individual} " .
                    "(CLIENT: " .
                    strtoupper(
                        $row['customer_fullname']
                        ?? 'N/A'
                    ) .
                    " | DUE: " .
                    date(
                        'M d, Y',
                        strtotime(
                            $row['due_date']
                        )
                    ) .
                    ")";

                $amount =
                    (float)$row['comm_amount'];

                $items[] = [

                    'item_no' =>
                        $item_no++,

                    'qty' =>
                        '1',

                    'unit' =>
                        'LOT',

                    'description' =>
                        $desc,

                    'unit_price' =>
                        number_format(
                            $amount,
                            2
                        ),

                    'total_amount' =>
                        number_format(
                            $amount,
                            2
                        ),

                    'raw_amount' =>
                        $amount
                ];

                $total_amount +=
                    $amount;

                /*
                 * Store the actual payment_id in the key.
                 *
                 * This makes future updates target the exact
                 * payment row.
                 */
                $valid_keys[] =
                    $row['payment_id'] .
                    '|' .
                    $row['customer_id'] .
                    '|' .
                    $row['due_date'] .
                    '|' .
                    $role;
            }

        } catch (PDOException $e) {

            error_log(
                'Generate RFP query error: ' .
                $e->getMessage()
            );

            $error_msg =
                'Database query error: ' .
                $e->getMessage();
        }
    }

    if (count($payees) === 1) {

        $payee_name =
            array_key_first(
                $payees
            );

    } elseif (count($payees) > 1) {

        $payee_name =
            implode(
                ', ',
                array_keys(
                    $payees
                )
            );

    } else {

        $payee_name =
            'MULTIPLE BENEFICIARIES';
    }

    $slip_no =
        'REQ-COM-' .
        date(
            'Ymd-His'
        );

    $_SESSION['rfp_data'] = [

        'payee_name' =>
            $payee_name,

        'selected_keys' =>
            $valid_keys,

        'amount' =>
            number_format(
                $total_amount,
                2
            ),

        'raw_total' =>
            $total_amount,

        'date_requested' =>
            date(
                'd-M-y'
            ),

        'slip_no' =>
            $slip_no,

        'description' =>
            'COMMISSION REQUISITION FOR ' .
            $payee_name,

        'items' =>
            $items,

        'is_grouped_rfp' =>
            false,

        'rfp_ids' =>
            []
    ];

    $_SESSION['rfp_status'] =
        'draft';

    $_SESSION['rfp_sigs'] =
        [];
}


/**
 * ============================================================
 * 1.5 LOAD EXISTING SINGLE RFP
 * ============================================================
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'load_existing_rfp'
) {

    $selected_key =
        trim(
            $_POST['selected_key']
            ?? ''
        );

    $parsed =
        parseCommissionKey(
            $selected_key
        );

    if (
        $parsed &&
        in_array(
            $parsed['role'],
            [
                'agent',
                'broker',
                'um'
            ],
            true
        )
    ) {

        $role =
            $parsed['role'];

        $payment_id =
            $parsed['payment_id'];

        $customer_id =
            $parsed['customer_id'];

        $due_date =
            $parsed['due_date'];

        $amount_col =
            $role .
            '_commission_amount';

        $name_col =
            $role .
            '_fullname';

        try {

            if (!empty($payment_id)) {

                $stmt =
                    $pdo->prepare("
                        SELECT
                            p.*,
                            s.customer_fullname,
                            s.{$name_col} AS payee_person

                        FROM payments p

                        INNER JOIN sales s
                            ON p.sale_id = s.sale_id

                        WHERE p.id = ?

                        LIMIT 1
                    ");

                $stmt->execute([
                    $payment_id
                ]);

            } else {

                $stmt =
                    $pdo->prepare("
                        SELECT
                            p.*,
                            s.customer_fullname,
                            s.{$name_col} AS payee_person

                        FROM payments p

                        INNER JOIN sales s
                            ON p.sale_id = s.sale_id

                        WHERE p.customer_id = ?
                          AND p.due_date = ?

                        LIMIT 1
                    ");

                $stmt->execute([
                    $customer_id,
                    $due_date
                ]);
            }

            $row =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );

            if ($row) {

                $amount =
                    (float)(
                        $row[$amount_col]
                        ?? 0
                    );

                $payee_name =
                    strtoupper(
                        $row['payee_person']
                        ?? 'N/A'
                    );

                $role_label =
                    strtoupper(
                        $role === 'um'
                            ? 'UNIT MANAGER'
                            : $role
                    );

                $_SESSION['rfp_data'] = [

                    'payee_name' =>
                        $payee_name,

                    'selected_keys' =>
                        [$selected_key],

                    'amount' =>
                        number_format(
                            $amount,
                            2
                        ),

                    'raw_total' =>
                        $amount,

                    'date_requested' =>
                        date('d-M-y'),

                    'slip_no' =>
                        'REQ-COM-' .
                        strtoupper($role) .
                        '-' .
                        date(
                            'Ymd-His',
                            strtotime(
                                $row['due_date']
                            )
                        ),

                    'description' =>
                        'COMMISSION REQUISITION FOR ' .
                        $payee_name,

                    'items' => [[

                        'item_no' =>
                            1,

                        'qty' =>
                            '1',

                        'unit' =>
                            'LOT',

                        'description' =>
                            "{$role_label} COMMISSION RELEASE - " .
                            "BENEFICIARY: {$payee_name}",

                        'unit_price' =>
                            number_format(
                                $amount,
                                2
                            ),

                        'total_amount' =>
                            number_format(
                                $amount,
                                2
                            ),

                        'raw_amount' =>
                            $amount
                    ]],

                    'is_grouped_rfp' =>
                        false,

                    'rfp_ids' =>
                        []
                ];

                $_SESSION['rfp_sigs'] =
                    [];

                $_SESSION['rfp_status'] =
                    'pending_review';

                /*
                 * ==================================================
                 * LOAD EACH ROLE-SPECIFIC SIGNATURE
                 * ==================================================
                 */
                $stages = [
                    'encoder',
                    'reviewer',
                    'auditor',
                    'cfo'
                ];

                foreach (
                    $stages
                    as $stage
                ) {

                    $columns =
                        getPaymentSignatureColumns(
                            $role,
                            $stage
                        );

                    if (
                        !$columns ||
                        empty(
                            $row[
                                $columns['id']
                            ]
                        )
                    ) {
                        continue;
                    }

                    $user =
                        getUserDetailsById(
                            $pdo,
                            $row[
                                $columns['id']
                            ]
                        );

                    if (!$user) {
                        continue;
                    }

                    if (
                        !empty(
                            $row[
                                $columns['sig']
                            ]
                        )
                    ) {

                        $user['sig_src'] =
                            $row[
                                $columns['sig']
                            ];
                    }

                    $_SESSION['rfp_sigs'][
                        $stage
                    ] = $user;

                    if ($stage === 'reviewer') {

                        $_SESSION['rfp_status'] =
                            'pending_audit';

                    } elseif ($stage === 'auditor') {

                        $_SESSION['rfp_status'] =
                            'pending_cfo';

                    } elseif ($stage === 'cfo') {

                        $_SESSION['rfp_status'] =
                            'released';
                    }
                }

            } else {

                unset(
                    $_SESSION['rfp_data'],
                    $_SESSION['rfp_status'],
                    $_SESSION['rfp_sigs']
                );

                $error_msg =
                    'That commission line could not be found.';
            }

        } catch (PDOException $e) {

            error_log(
                'Load existing RFP error: ' .
                $e->getMessage()
            );

            $error_msg =
                'Database error while loading RFP: ' .
                $e->getMessage();
        }

    } else {

        unset(
            $_SESSION['rfp_data'],
            $_SESSION['rfp_status'],
            $_SESSION['rfp_sigs']
        );

        $error_msg =
            'Invalid RFP reference.';
    }
}


/**
 * ============================================================
 * 1.6 LOAD MULTIPLE GROUPED RFPs
 * ============================================================
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'load_multiple_rfps'
) {

    $selected_rfp_ids =
        $_POST['rfp_ids']
        ?? [];

    /*
     * Convert all selected IDs to integer and remove duplicates.
     */
    $selected_rfp_ids =
        array_values(
            array_unique(
                array_filter(
                    array_map(
                        'intval',
                        $selected_rfp_ids
                    ),
                    function ($id) {
                        return $id > 0;
                    }
                )
            )
        );

    if (empty($selected_rfp_ids)) {

        $error_msg =
            'Please select at least one RFP request.';

    } else {

        try {

            $placeholders =
                implode(
                    ',',
                    array_fill(
                        0,
                        count($selected_rfp_ids),
                        '?'
                    )
                );

            /**
             * Load every item belonging to every selected RFP.
             */
            $sql = "
                SELECT

                    rr.id AS rfp_id,
                    rr.status AS rfp_status,

                    ri.id AS item_id,
                    ri.payment_id,
                    ri.commission_role,

                    p.customer_id,
                    p.due_date,

                    p.broker_commission_amount,
                    p.um_commission_amount,
                    p.agent_commission_amount,

                    s.customer_fullname,

                    s.broker_fullname,
                    s.um_fullname,
                    s.agent_fullname

                FROM rfp_requests rr

                INNER JOIN rfp_request_items ri
                    ON ri.rfp_id = rr.id

                INNER JOIN payments p
                    ON p.id = ri.payment_id

                INNER JOIN sales s
                    ON s.sale_id = p.sale_id

                WHERE rr.id IN ({$placeholders})

                  AND LOWER(
                        TRIM(
                            COALESCE(
                                rr.status,
                                ''
                            )
                        )
                      ) NOT IN (
                          'released',
                          'rejected'
                      )

                ORDER BY
                    rr.id ASC,
                    ri.id ASC
            ";

            $stmt =
                $pdo->prepare(
                    $sql
                );

            $stmt->execute(
                $selected_rfp_ids
            );

            $rows =
                $stmt->fetchAll(
                    PDO::FETCH_ASSOC
                );

            if (empty($rows)) {

                throw new RuntimeException(
                    'No active RFP items were found for the selected requests.'
                );
            }


            /**
             * ==================================================
             * BUILD COMBINED APPROVAL SESSION
             * ==================================================
             */
            $selected_keys = [];

            $items = [];

            $total_amount = 0;

            $item_no = 1;

            $payees = [];


            foreach (
                $rows
                as $row
            ) {

                $role =
                    strtolower(
                        trim(
                            $row[
                                'commission_role'
                            ] ?? ''
                        )
                    );

                if (!in_array(
                    $role,
                    [
                        'broker',
                        'um',
                        'agent'
                    ],
                    true
                )) {

                    continue;
                }

                $amount_col =
                    $role .
                    '_commission_amount';

                $name_col =
                    $role .
                    '_fullname';

                $amount =
                    (float)(
                        $row[
                            $amount_col
                        ] ?? 0
                    );

                $payee =
                    strtoupper(
                        trim(
                            $row[
                                $name_col
                            ] ?? 'N/A'
                        )
                    );

                $customer =
                    strtoupper(
                        trim(
                            $row[
                                'customer_fullname'
                            ]
                            ?? 'UNKNOWN CLIENT'
                        )
                    );

                /*
                 * Exact payment-role key.
                 */
                $key =
                    $row['payment_id'] .
                    '|' .
                    $row['customer_id'] .
                    '|' .
                    $row['due_date'] .
                    '|' .
                    $role;

                /*
                 * Prevent duplicate payment-role entries.
                 */
                if (
                    in_array(
                        $key,
                        $selected_keys,
                        true
                    )
                ) {
                    continue;
                }

                $selected_keys[] =
                    $key;

                $payees[
                    $payee
                ] = true;

                $role_label =
                    strtoupper(
                        $role === 'um'
                            ? 'UNIT MANAGER'
                            : $role
                    );

                $description =
                    "{$role_label} COMMISSION RELEASE - " .
                    "BENEFICIARY: {$payee} " .
                    "(CLIENT: {$customer} | DUE: " .
                    date(
                        'M d, Y',
                        strtotime(
                            $row['due_date']
                        )
                    ) .
                    ")";

                $items[] = [

                    'item_no' =>
                        $item_no++,

                    'qty' =>
                        '1',

                    'unit' =>
                        'LOT',

                    'description' =>
                        $description,

                    'unit_price' =>
                        number_format(
                            $amount,
                            2
                        ),

                    'total_amount' =>
                        number_format(
                            $amount,
                            2
                        ),

                    'raw_amount' =>
                        $amount,

                    'rfp_id' =>
                        (int)$row['rfp_id']
                ];

                $total_amount +=
                    $amount;
            }


            if (empty($selected_keys)) {

                throw new RuntimeException(
                    'No valid commission items were found in the selected RFPs.'
                );
            }


            if (count($payees) === 1) {

                $payee_name =
                    array_key_first(
                        $payees
                    );

            } elseif (count($payees) > 1) {

                $payee_name =
                    implode(
                        ', ',
                        array_keys(
                            $payees
                        )
                    );

            } else {

                $payee_name =
                    'MULTIPLE BENEFICIARIES';
            }


            /*
             * IMPORTANT:
             *
             * This does NOT create another rfp_requests row.
             *
             * It only combines the already-generated RFPs into
             * one approval session for the current approver.
             */
            $_SESSION['rfp_data'] = [

                'payee_name' =>
                    $payee_name,

                'selected_keys' =>
                    $selected_keys,

                'amount' =>
                    number_format(
                        $total_amount,
                        2
                    ),

                'raw_total' =>
                    $total_amount,

                'date_requested' =>
                    date(
                        'd-M-y'
                    ),

                'slip_no' =>
                    'MULTI-RFP-' .
                    date(
                        'Ymd-His'
                    ),

                'description' =>
                    'GROUPED COMMISSION REQUISITION FOR ' .
                    $payee_name,

                'items' =>
                    $items,

                'rfp_ids' =>
                    $selected_rfp_ids,

                'is_grouped_rfp' =>
                    true
            ];

            /*
             * Grouped approval starts with reviewer.
             */
            $_SESSION['rfp_sigs'] =
                [];

            $_SESSION['rfp_status'] =
                'pending_review';


        } catch (
            PDOException |
            RuntimeException $e
        ) {

            error_log(
                'Multiple RFP load error: ' .
                $e->getMessage()
            );

            $error_msg =
                'Failed to load selected RFP requests: ' .
                $e->getMessage();
        }
    }
}


/**
 * ============================================================
 * 2. WORKFLOW SIGNING / APPROVAL
 * ============================================================
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    !in_array(
        $_POST['action'],
        [
            'generate_flexible_rfp',
            'load_existing_rfp',
            'load_multiple_rfps'
        ],
        true
    )
) {

    $action =
        $_POST['action']
        ?? '';

    $signer =
        getUserDetailsById(
            $pdo,
            $logged_in_user_id
        );

    if (!$signer) {

        $error_msg =
            'User signature details could not be found.';
    } else {

        $rfp_data =
            $_SESSION['rfp_data']
            ?? null;

        if (!$rfp_data) {

            $error_msg =
                'No active RFP data was found.';
        } else {

            $has_signed_already =
                hasUserAlreadySigned(
                    $signer['user_id'],
                    $_SESSION['rfp_sigs']
                    ?? []
                );


            /**
             * ==================================================
             * ENCODER
             * ==================================================
             */
            if (
                $action ===
                'submit_encoder'
            ) {

                try {

                    $pdo->beginTransaction();

                    updateRoleSignature(
                        $pdo,
                        $rfp_data,
                        'encoder',
                        $signer['user_id'],
                        $signer['sig_src']
                    );


                    /*
                     * Lock each selected commission.
                     */
                    foreach (
                        $rfp_data['selected_keys']
                        as $key
                    ) {

                        $parsed =
                            parseCommissionKey(
                                $key
                            );

                        if (
                            !$parsed ||
                            !in_array(
                                $parsed['role'],
                                [
                                    'agent',
                                    'broker',
                                    'um'
                                ],
                                true
                            )
                        ) {
                            continue;
                        }

                        $role =
                            $parsed['role'];

                        $payment_id =
                            $parsed['payment_id'];

                        $customer_id =
                            $parsed['customer_id'];

                        $due_date =
                            $parsed['due_date'];

                        $status_col =
                            $role .
                            '_commission_status';


                        if (!empty($payment_id)) {

                            $stmt =
                                $pdo->prepare("
                                    UPDATE payments
                                    SET `{$status_col}` =
                                        'Pending Approval'
                                    WHERE id = ?
                                    LIMIT 1
                                ");

                            $stmt->execute([
                                $payment_id
                            ]);

                        } else {

                            $stmt =
                                $pdo->prepare("
                                    UPDATE payments
                                    SET `{$status_col}` =
                                        'Pending Approval'
                                    WHERE customer_id = ?
                                      AND due_date = ?
                                    LIMIT 1
                                ");

                            $stmt->execute([
                                $customer_id,
                                $due_date
                            ]);
                        }
                    }


                    /*
                     * If an RFP ID is attached, update it.
                     */
                    if (
                        !empty(
                            $rfp_data['is_grouped_rfp']
                        ) &&
                        !empty(
                            $rfp_data['rfp_ids']
                        )
                    ) {

                        updateRfpRequestStatuses(
                            $pdo,
                            $rfp_data['rfp_ids'],
                            'pending_review'
                        );
                    }


                    $pdo->commit();

                    $_SESSION['rfp_status'] =
                        'pending_review';

                    $_SESSION['rfp_sigs']['encoder'] =
                        $signer;

                    $success_msg =
                        'RFP prepared and signed by Encoder. ' .
                        'Selected items are now pending approval.';


                } catch (
                    PDOException |
                    RuntimeException $e
                ) {

                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }

                    error_log(
                        'Encoder approval error: ' .
                        $e->getMessage()
                    );

                    $error_msg =
                        'Database Update Error: ' .
                        $e->getMessage();
                }


            /**
             * ==================================================
             * REVIEWER
             * ==================================================
             */
            } elseif (
                $action ===
                'approve_reviewer'
            ) {

                if ($has_signed_already) {

                    $error_msg =
                        'Segregation of Duties Conflict: ' .
                        'The reviewer must be a different user.';

                } else {

                    try {

                        $pdo->beginTransaction();


                        /*
                         * Save role-specific reviewer signatures.
                         */
                        updateRoleSignature(
                            $pdo,
                            $rfp_data,
                            'reviewer',
                            $signer['user_id'],
                            $signer['sig_src']
                        );


                        /*
                         * Update grouped RFP statuses.
                         */
                        if (
                            !empty(
                                $rfp_data['is_grouped_rfp']
                            ) &&
                            !empty(
                                $rfp_data['rfp_ids']
                            )
                        ) {

                            updateRfpRequestStatuses(
                                $pdo,
                                $rfp_data['rfp_ids'],
                                'pending_audit'
                            );
                        }


                        $pdo->commit();

                        $_SESSION['rfp_status'] =
                            'pending_audit';

                        $_SESSION['rfp_sigs']['reviewer'] =
                            $signer;

                        $success_msg =
                            'RFP request(s) reviewed successfully. ' .
                            'Reviewer signatures were saved to their correct role-specific columns.';

                    } catch (
                        PDOException |
                        RuntimeException $e
                    ) {

                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }

                        error_log(
                            'Reviewer approval error: ' .
                            $e->getMessage()
                        );

                        $error_msg =
                            'Database Update Error: ' .
                            $e->getMessage();
                    }
                }


            /**
             * ==================================================
             * AUDITOR
             * ==================================================
             */
            } elseif (
                $action ===
                'approve_auditor'
            ) {

                if ($has_signed_already) {

                    $error_msg =
                        'Segregation of Duties Conflict: ' .
                        'You cannot audit an RFP you previously handled.';

                } else {

                    try {

                        $pdo->beginTransaction();


                        updateRoleSignature(
                            $pdo,
                            $rfp_data,
                            'auditor',
                            $signer['user_id'],
                            $signer['sig_src']
                        );


                        if (
                            !empty(
                                $rfp_data['is_grouped_rfp']
                            ) &&
                            !empty(
                                $rfp_data['rfp_ids']
                            )
                        ) {

                            updateRfpRequestStatuses(
                                $pdo,
                                $rfp_data['rfp_ids'],
                                'pending_cfo'
                            );
                        }


                        $pdo->commit();

                        $_SESSION['rfp_status'] =
                            'pending_cfo';

                        $_SESSION['rfp_sigs']['auditor'] =
                            $signer;

                        $success_msg =
                            'RFP request(s) audited successfully.';

                    } catch (
                        PDOException |
                        RuntimeException $e
                    ) {

                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }

                        error_log(
                            'Auditor approval error: ' .
                            $e->getMessage()
                        );

                        $error_msg =
                            'Database Update Error: ' .
                            $e->getMessage();
                    }
                }


            /**
             * ==================================================
             * CFO
             * ==================================================
             */
            } elseif (
                $action ===
                'approve_cfo'
            ) {

                if ($has_signed_already) {

                    $error_msg =
                        'Segregation of Duties Conflict: ' .
                        'Final CFO approval must be performed by an independent approver.';

                } else {

                    try {

                        $pdo->beginTransaction();


                        /*
                         * ==================================================
                         * SAVE CFO SIGNATURE
                         * ==================================================
                         */
                        updateRoleSignature(
                            $pdo,
                            $rfp_data,
                            'cfo',
                            $signer['user_id'],
                            $signer['sig_src']
                        );


                        /*
                         * ==================================================
                         * RELEASE COMMISSIONS
                         * ==================================================
                         */
                        foreach (
                            $rfp_data['selected_keys']
                            as $key
                        ) {

                            $parsed =
                                parseCommissionKey(
                                    $key
                                );

                            if (
                                !$parsed ||
                                !in_array(
                                    $parsed['role'],
                                    [
                                        'agent',
                                        'broker',
                                        'um'
                                    ],
                                    true
                                )
                            ) {
                                continue;
                            }

                            $role =
                                $parsed['role'];

                            $payment_id =
                                $parsed['payment_id'];

                            $customer_id =
                                $parsed['customer_id'];

                            $due_date =
                                $parsed['due_date'];

                            $status_col =
                                $role .
                                '_commission_status';


                            if (!empty($payment_id)) {

                                $stmt =
                                    $pdo->prepare("
                                        UPDATE payments
                                        SET `{$status_col}` =
                                            'Released'
                                        WHERE id = ?
                                        LIMIT 1
                                    ");

                                $stmt->execute([
                                    $payment_id
                                ]);

                            } else {

                                $stmt =
                                    $pdo->prepare("
                                        UPDATE payments
                                        SET `{$status_col}` =
                                            'Released'
                                        WHERE customer_id = ?
                                          AND due_date = ?
                                        LIMIT 1
                                    ");

                                $stmt->execute([
                                    $customer_id,
                                    $due_date
                                ]);
                            }
                        }


                        /*
                         * ==================================================
                         * RELEASE GROUPED RFP REQUESTS
                         * ==================================================
                         */
                        if (
                            !empty(
                                $rfp_data['is_grouped_rfp']
                            ) &&
                            !empty(
                                $rfp_data['rfp_ids']
                            )
                        ) {

                            updateRfpRequestStatuses(
                                $pdo,
                                $rfp_data['rfp_ids'],
                                'released'
                            );
                        }


                        $pdo->commit();

                        $_SESSION['rfp_status'] =
                            'released';

                        $_SESSION['rfp_sigs']['cfo'] =
                            $signer;

                        $success_msg =
                            'RFP request(s) finalized successfully. ' .
                            'CFO signature saved and selected commissions released.';

                    } catch (
                        PDOException |
                        RuntimeException $e
                    ) {

                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }

                        error_log(
                            'CFO approval error: ' .
                            $e->getMessage()
                        );

                        $error_msg =
                            'Database Update Error: ' .
                            $e->getMessage();
                    }
                }
            }
        }
    }
}


/**
 * ============================================================
 * LOAD CURRENT SESSION
 * ============================================================
 */
$rfp =
    $_SESSION['rfp_data']
    ?? null;

$status =
    $_SESSION['rfp_status']
    ?? 'draft';

$sigs =
    $_SESSION['rfp_sigs']
    ?? [];


if (!$rfp) {

    echo "
        <div style='
            padding:60px;
            font-family:sans-serif;
            text-align:center;
        '>

            <h2>
                No active RFP session found.
            </h2>

            <a
                href='commission_release.php'
                style='
                    display:inline-block;
                    margin-top:15px;
                    padding:12px 24px;
                    background:#1c5f66;
                    color:#fff;
                    text-decoration:none;
                    border-radius:8px;
                    font-weight:bold;
                '
            >
                Go Back to Commissions
            </a>

        </div>
    ";

    exit;
}


$status_levels = [

    'draft' =>
        1,

    'pending_review' =>
        2,

    'pending_audit' =>
        3,

    'pending_cfo' =>
        4,

    'released' =>
        5
];

$current_level =
    $status_levels[$status]
    ?? 1;


$creator_id =
    $sigs['encoder']['user_id']
    ?? null;

$is_creator =
    (
        $creator_id &&
        (string)$creator_id ===
        (string)$logged_in_user_id
    );


$is_grouped_rfp =
    !empty(
        $rfp['is_grouped_rfp']
    );

$selected_rfp_ids =
    $rfp['rfp_ids']
    ?? [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request for Payment - <?= htmlspecialchars($rfp['slip_no']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Cinzel:wght@700&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        :root {
            --bg-canvas: #f8fafc;
            --brand-primary: #1c5f66;
            --brand-accent: #a6ce39;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-light: #e2e8f0;
            --success: #10b981;
        }

        * { box-sizing: border-box; }
        body { margin: 0; background-color: var(--bg-canvas); font-family: 'Plus Jakarta Sans', sans-serif; color: var(--text-main); padding-bottom: 60px; }

        .rfp-control-wrapper { width: 100%; max-width: 920px; margin: 30px auto; padding: 0 16px; }
        .control-panel-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 24px 32px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03), 0 10px 15px -3px rgba(0,0,0,0.05);
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .cp-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .cp-title { font-size: 18px; font-weight: 800; margin: 0; color: #1e293b; display: flex; align-items: center; gap: 8px; }

        .stepper { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; position: relative; }
        .stepper::before {
            content: ''; position: absolute; top: 14px; left: 0; right: 0; height: 2px;
            background: var(--border-light); z-index: 1;
        }
        .step {
            position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 8px;
            width: 25%; text-align: center;
        }
        .step-icon {
            width: 30px; height: 30px; border-radius: 50%; background: #ffffff;
            border: 2px solid var(--border-light); display: flex; align-items: center; justify-content: center;
            font-size: 14px; font-weight: 700; color: var(--text-muted); transition: 0.3s;
        }
        .step-label { font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        
        .step.completed .step-icon { background: var(--brand-primary); border-color: var(--brand-primary); color: #ffffff; }
        .step.completed .step-label { color: var(--text-main); }
        .step.active .step-icon { border-color: var(--brand-primary); color: var(--brand-primary); box-shadow: 0 0 0 3px rgba(28, 95, 102, 0.15); }
        .step.active .step-label { color: var(--brand-primary); font-weight: 800; }

        .cp-actions { display: flex; align-items: center; justify-content: space-between; padding-top: 20px; border-top: 1px solid var(--border-light); }
        .btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px;
            border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer;
            border: none; text-decoration: none; transition: 0.2s ease;
        }
        .btn-primary { background: var(--brand-primary); color: #ffffff; }
        .btn-primary:hover { background: #154c52; transform: translateY(-1px); }
        .btn-success { background: var(--success); color: #ffffff; }
        .btn-success:hover { background: #059669; transform: translateY(-1px); }
        .btn-outline { background: #ffffff; border: 1.5px solid var(--border-light); color: var(--text-main); }
        .btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; }

        .alert { padding: 12px 16px; border-radius: 10px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 8px; margin-bottom: 20px; }
        .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        .paper {
            width: 850px; min-height: 1050px; margin: 0 auto; background: #ffffff;
            padding: 45px 50px; box-shadow: 0 20px 40px rgba(0,0,0,0.06);
            border: 1px solid #d1d5db; position: relative; color: #000;
        }

        .co-header { text-align: center; font-size: 20px; font-weight: 800; font-family: 'Times New Roman', serif; text-transform: uppercase; letter-spacing: 0.05em; }
        .doc-title { text-align: center; font-size: 28px; font-weight: 800; font-family: 'Cinzel', serif; margin: 10px 0 25px; }

        .meta-grid { display: flex; justify-content: space-between; font-size: 11px; font-weight: 800; margin-bottom: 12px; }
        .u-line { border-bottom: 1.5px solid #000; padding: 0 10px; display: inline-block; min-width: 150px; text-transform: uppercase; }

        .rfp-grid { width: 100%; border-collapse: collapse; border: 2px solid #000; font-size: 11px; }
        .rfp-grid th, .rfp-grid td { border: 1.5px solid #000; padding: 8px 10px; }
        .rfp-grid th { font-weight: 800; text-transform: uppercase; background: #fafafa; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .item-row { height: 34px; }
        
        .circle-total {
            display: inline-block; padding: 6px 18px; border: 2px solid #dc2626;
            border-radius: 50%; font-weight: 800; font-size: 14px; color: #000;
        }

        .sig-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 60px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
        .sig-box { position: relative; padding-top: 45px; text-align: center; }
        .sig-line { border-top: 1.5px solid #000; margin-bottom: 4px; }
        .role-text { font-size: 9px; font-weight: 600; color: #475569; }

        .db-sig-img {
            position: absolute; bottom: 25px; left: 50%; transform: translateX(-50%);
            max-height: 55px; max-width: 130px; object-fit: contain; z-index: 10;
        }
        .fallback-sig {
            position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); font-family: 'Dancing Script', cursive;
            font-size: 22px; color: #1e293b; text-transform: none; z-index: 10; white-space: nowrap;
        }

        .audit-stamp {
            position: absolute; top: -25px; left: 10px; border: 2px dashed #b91c1c; color: #b91c1c;
            padding: 3px 8px; font-size: 10px; font-weight: 800; transform: rotate(-5deg); background: rgba(255,255,255,0.9);
            text-align: center; z-index: 5;
        }
        .paid-stamp {
            position: absolute; top: 350px; left: 50%; transform: translateX(-50%) rotate(-15deg);
            border: 6px solid #16a34a; color: #16a34a; padding: 15px 40px; font-size: 56px;
            font-weight: 900; letter-spacing: 0.1em; opacity: 0.2; pointer-events: none; border-radius: 12px;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .rfp-control-wrapper { display: none !important; }
            .paper { box-shadow: none; border: none; width: 100%; padding: 0; margin: 0; }
        }
    </style>
</head>
<body>

<div class="rfp-control-wrapper">
    <?php if ($success_msg): ?>
        <div class="alert alert-success"><i data-lucide="check-circle-2" size="18"></i> <?= htmlspecialchars($success_msg) ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-error"><i data-lucide="alert-triangle" size="18"></i> <?= htmlspecialchars($error_msg) ?></div>
    <?php endif; ?>

    <div class="control-panel-card">
        <div class="cp-header">
            <h3 class="cp-title"><i data-lucide="layout-dashboard"></i> RFP Approval Flow</h3>
            <span style="font-size: 12px; font-weight: 600; color: var(--text-muted); background: #f1f5f9; padding: 4px 12px; border-radius: 20px;">
                Logged in as: <strong><?= strtoupper(htmlspecialchars($logged_in_role)) ?></strong>
            </span>
        </div>

        <div class="stepper">
            <div class="step <?= $current_level > 1 ? 'completed' : ($current_level === 1 ? 'active' : '') ?>">
                <div class="step-icon"><i data-lucide="<?= $current_level > 1 ? 'check' : 'pen-tool' ?>" size="16"></i></div>
                <div class="step-label">Encoded</div>
            </div>
            <div class="step <?= $current_level > 2 ? 'completed' : ($current_level === 2 ? 'active' : '') ?>">
                <div class="step-icon"><i data-lucide="<?= $current_level > 2 ? 'check' : 'search' ?>" size="16"></i></div>
                <div class="step-label">Reviewed</div>
            </div>
            <div class="step <?= $current_level > 3 ? 'completed' : ($current_level === 3 ? 'active' : '') ?>">
                <div class="step-icon"><i data-lucide="<?= $current_level > 3 ? 'check' : 'stamp' ?>" size="16"></i></div>
                <div class="step-label">Audited</div>
            </div>
            <div class="step <?= $current_level > 4 ? 'completed' : ($current_level === 4 ? 'active' : '') ?>">
                <div class="step-icon"><i data-lucide="<?= $current_level > 4 ? 'check' : 'shield-check' ?>" size="16"></i></div>
                <div class="step-label">CFO Approved</div>
            </div>
        </div>

        <div class="cp-actions">
            <div style="display: flex; gap: 12px;">
                <a href="/user/encoder/commission-release" class="btn btn-outline"><i data-lucide="arrow-left" size="16"></i> Back</a>
                <button type="button" onclick="window.print()" class="btn btn-outline"><i data-lucide="printer" size="16"></i> Print</button>
            </div>

            <form method="POST" style="margin: 0;">
                <?php if ($status === 'draft' && $logged_in_role === 'encoder'): ?>
                    <button type="submit" name="action" value="submit_encoder" class="btn btn-primary">
                        <i data-lucide="pen-tool" size="16"></i> Sign & Submit Draft
                    </button>
                <?php elseif ($status === 'pending_review' && $logged_in_role === 'encoder' && !$is_creator): ?>
                    <button type="submit" name="action" value="approve_reviewer" class="btn btn-primary">
                        <i data-lucide="check-square" size="16"></i> Review & Sign
                    </button>
                <?php elseif ($status === 'pending_audit' && $logged_in_role === 'auditor'): ?>
                    <button type="submit" name="action" value="approve_auditor" class="btn btn-primary">
                        <i data-lucide="stamp" size="16"></i> Audit & Stamp
                    </button>
                <?php elseif ($status === 'pending_cfo' && $logged_in_role === 'cfo'): ?>
                    <button type="submit" name="action" value="approve_cfo" class="btn btn-success" onclick="return confirm('Confirm release of funds? Selected commission statuses will be updated to Released.');">
                        <i data-lucide="badge-dollar-sign" size="16"></i> CFO Final Sign & Release
                    </button>
                <?php elseif ($status === 'released'): ?>
                    <span style="color: var(--success); font-weight: 800; display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="check-circle-2" size="20"></i> COMPLETED & RELEASED
                    </span>
                <?php else: ?>
                    <span style="color: var(--text-muted); font-size: 13px; font-weight: 500;">
                        <?php if ($status === 'pending_review' && $is_creator): ?>
                            <i data-lucide="lock" size="14" style="vertical-align: middle;"></i> Awaiting another Encoder to Review (You cannot review your own).
                        <?php else: ?>
                            <i data-lucide="lock" size="14" style="vertical-align: middle;"></i> Awaiting active step user approval...
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<div class="paper">
    <?php if ($status === 'released'): ?>
        <div class="paid-stamp">PAID & RELEASED</div>
    <?php endif; ?>

    <div class="co-header">MLHUILLIER GROUP OF COMPANIES</div>
    <div class="doc-title">Requisition Form</div>

    <div class="meta-grid">
        <div>DATE REQUESTED <span class="u-line"><?= htmlspecialchars($rfp['date_requested']) ?></span></div>
        <div>REQUISITION SLIP # <span class="u-line"><?= htmlspecialchars($rfp['slip_no']) ?></span></div>
    </div>

    <table class="rfp-grid">
        <tr>
            <td colspan="4">Requester's Name &nbsp;&nbsp; <strong><?= htmlspecialchars(strtoupper($sigs['encoder']['name'] ?? 'PENDING')) ?></strong></td>
            <td colspan="2">DATE NEEDED &nbsp;&nbsp; <strong>ASAP</strong></td>
        </tr>
        <tr>
            <td colspan="6">ADDRESS &nbsp;&nbsp; <strong>1082 J. PANIS ST. KALUBIHAN TALAMBAN CEBU</strong></td>
        </tr>
        <tr>
            <td colspan="3">DEPT. &nbsp;&nbsp; <strong>OPERATIONS</strong></td>
            <td colspan="3">BUDGET SOURCE &nbsp;&nbsp; <strong>COMMISSION FUND</strong></td>
        </tr>
        <tr>
            <th colspan="2" style="width: 20%; background: #f1f5f9;">Purpose</th>
            <th colspan="4" class="text-center" style="font-size: 14px; background: #f8fafc;">REQUEST FOR PAYMENT</th>
        </tr>
        <tr>
            <th width="8%" class="text-center">ITEM #</th>
            <th width="8%" class="text-center">QTY</th>
            <th width="12%" class="text-center">UNIT/SIZE</th>
            <th width="44%">MATERIALS DESCRIPTION</th>
            <th width="14%" class="text-center">UNIT PRICE</th>
            <th width="14%" class="text-center">TOTAL AMOUNT</th>
        </tr>
        
        <?php 
        $displayItems = $rfp['items'] ?? [];
        for ($i = 0; $i < max(6, count($displayItems)); $i++): 
            $item = $displayItems[$i] ?? null;
        ?>
            <tr class="item-row">
                <td class="text-center"><?= $item ? htmlspecialchars($item['item_no']) : '' ?></td>
                <td class="text-center"><?= $item ? htmlspecialchars($item['qty']) : '' ?></td>
                <td class="text-center"><?= $item ? htmlspecialchars($item['unit']) : '' ?></td>
                <td><strong><?= $item ? htmlspecialchars($item['description']) : ($i === 0 ? htmlspecialchars($rfp['description']) : '') ?></strong></td>
                <td class="text-right"><?= $item ? htmlspecialchars($item['unit_price']) : '' ?></td>
                <td class="text-right" style="font-weight:800;"><?= $item ? htmlspecialchars($item['total_amount']) : ($i === 0 ? htmlspecialchars($rfp['amount']) : '') ?></td>
            </tr>
        <?php endfor; ?>

        <tr>
            <td colspan="5" style="padding: 16px;">
                <strong>NOTE:</strong> <br><br>
                <span style="margin-left: 80px; font-weight:800;">MJLFSI - FLEXIBLE COMMISSION RELEASE (PAYEE: <?= htmlspecialchars(strtoupper($rfp['payee_name'])) ?>)</span>
            </td>
            <td class="text-center" style="vertical-align: middle;">
                <span class="circle-total">₱<?= htmlspecialchars($rfp['amount']) ?></span>
            </td>
        </tr>
    </table>

    <div class="sig-container">
        <!-- ENCODER -->
        <div class="sig-box">
            <?php if (isset($sigs['encoder'])): ?>
                <?php if (!empty($sigs['encoder']['sig_src'])): ?>
                    <img src="<?= $sigs['encoder']['sig_src'] ?>" class="db-sig-img" alt="Encoder Signature">
                <?php else: ?>
                    <div class="fallback-sig"><?= htmlspecialchars($sigs['encoder']['name']) ?></div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="sig-line"></div>
            <div>PREPARED BY:</div>
            <div style="margin-top: 4px;"><?= htmlspecialchars($sigs['encoder']['name'] ?? '') ?></div>
            <div class="role-text"><?= htmlspecialchars($sigs['encoder']['role'] ?? 'ENCODER') ?></div>
        </div>

        <!-- REVIEWER -->
        <div class="sig-box">
            <?php if (isset($sigs['reviewer'])): ?>
                <?php if (!empty($sigs['reviewer']['sig_src'])): ?>
                    <img src="<?= $sigs['reviewer']['sig_src'] ?>" class="db-sig-img" alt="Reviewer Signature">
                <?php else: ?>
                    <div class="fallback-sig"><?= htmlspecialchars($sigs['reviewer']['name']) ?></div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="sig-line"></div>
            <div>REVIEWED BY:</div>
            <div style="margin-top: 4px;"><?= htmlspecialchars($sigs['reviewer']['name'] ?? '') ?></div>
            <div class="role-text"><?= htmlspecialchars($sigs['reviewer']['role'] ?? 'CHECKER / REVIEWER') ?></div>
        </div>

        <!-- AUDITOR -->
        <div class="sig-box">
            <?php if (isset($sigs['auditor'])): ?>
                <div class="audit-stamp">AUDIT DIVISION<br><span style="font-size:8px;">VERIFIED & STAMPED<br><?= date('M Y') ?></span></div>
                <?php if (!empty($sigs['auditor']['sig_src'])): ?>
                    <img src="<?= $sigs['auditor']['sig_src'] ?>" class="db-sig-img" alt="Auditor Signature">
                <?php else: ?>
                    <div class="fallback-sig"><?= htmlspecialchars($sigs['auditor']['name']) ?></div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="sig-line"></div>
            <div>AUDITED BY:</div>
            <div style="margin-top: 4px;"><?= htmlspecialchars($sigs['auditor']['name'] ?? '') ?></div>
            <div class="role-text"><?= htmlspecialchars($sigs['auditor']['role'] ?? 'AUDIT DIVISION') ?></div>
        </div>

        <!-- CFO -->
        <div class="sig-box">
            <?php if (isset($sigs['cfo'])): ?>
                <?php if (!empty($sigs['cfo']['sig_src'])): ?>
                    <img src="<?= $sigs['cfo']['sig_src'] ?>" class="db-sig-img" alt="CFO Signature">
                <?php else: ?>
                    <div class="fallback-sig" style="color:#0284c7; font-size:24px;"><?= htmlspecialchars($sigs['cfo']['name']) ?></div>
                <?php endif; ?>
            <?php endif; ?>
            <div class="sig-line"></div>
            <div>APPROVED BY:</div>
            <div style="margin-top: 4px;"><?= htmlspecialchars($sigs['cfo']['name'] ?? '') ?></div>
            <div class="role-text"><?= htmlspecialchars($sigs['cfo']['role'] ?? 'CHIEF FINANCIAL OFFICER') ?></div>
        </div>
    </div>
</div>

<script>
    lucide.createIcons();
</script>
</body>
</html>