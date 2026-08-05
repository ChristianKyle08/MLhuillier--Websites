<?php
session_start();
include '../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Required top-level fields
$contractNumber = trim($_POST['contract_number'] ?? '');
$remarksRaw     = $_POST['remarks'] ?? '';
$remarks        = trim($remarksRaw);

// transaction_dates can be JSON array, comma-separated string, single date, or actual array (unlikely)
$transactionDatesRaw = $_POST['transaction_dates'] ?? '';

// parse transaction dates flexibly
$dates = [];

if (is_array($transactionDatesRaw)) {
    $dates = $transactionDatesRaw;
} else {
    $decoded = json_decode($transactionDatesRaw, true);
    if (is_array($decoded)) {
        $dates = $decoded;
    } else {
        $trim = trim($transactionDatesRaw);
        if ($trim !== '') {
            // comma-separated?
            if (strpos($trim, ',') !== false) {
                $parts = array_map('trim', explode(',', $trim));
                $dates = array_filter($parts, function($v){ return $v !== ''; });
            } else {
                // single date string
                $dates = [$trim];
            }
        }
    }
}

// basic validation with clear messages
if ($contractNumber === '') {
    echo json_encode(['success' => false, 'message' => 'Contract number missing.']);
    exit;
}

if ($remarks === '') {
    echo json_encode(['success' => false, 'message' => 'Remarks is required.']);
    exit;
}

if (!is_array($dates) || count($dates) === 0) {
    // include raw payload for debugging (safe-ish). Remove in production if desired.
    $received = is_string($transactionDatesRaw) ? $transactionDatesRaw : json_encode($transactionDatesRaw);
    echo json_encode(['success' => false, 'message' => 'Transaction dates missing or invalid.', 'received' => $received]);
    exit;
}

// Build update SQL with fields for up to 5 lessors
$setParts = [
    "lessor_request_type   = 'change_lessor_name'",
    "lessor_request_status = 'Pending RM'",
    "lessor_request_reason = ?"
];

$params = [];
$params[] = $remarks;

// collect l1..l5 values
for ($i = 1; $i <= 5; $i++) {
    $fname = trim($_POST["l{$i}_firstname"] ?? '');
    $mname = trim($_POST["l{$i}_middlename"] ?? '');
    $lname = trim($_POST["l{$i}_lastname"] ?? '');
    $gender = trim($_POST["l{$i}_gender"] ?? '');

    $setParts[] = "new_l{$i}_firstname  = ?";
    $setParts[] = "new_l{$i}_middlename = ?";
    $setParts[] = "new_l{$i}_lastname   = ?";
    $setParts[] = "new_l{$i}_gender     = ?";

    $params[] = $fname;
    $params[] = $mname;
    $params[] = $lname;
    $params[] = $gender;
}

$sql = "UPDATE transactional SET " . implode(", ", $setParts) . " WHERE contract_number = ? AND transaction_date = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'SQL prepare error: ' . $conn->error]);
    exit;
}

$successCount = 0;
$errors = [];

foreach ($dates as $date) {
    // build bind params for this run
    $bindParams = $params;
    $bindParams[] = $contractNumber;
    $bindParams[] = $date;

    $types = str_repeat('s', count($bindParams));

    // Modern PHP supports argument unpacking
    if (!@$stmt->bind_param($types, ...$bindParams)) {
        // fallback using call_user_func_array (compatibility)
        $refs = [];
        $refs[] = $types;
        foreach ($bindParams as $k => $v) {
            $refs[] = &$bindParams[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }

    if ($stmt->execute()) {
        $successCount++;
    } else {
        $errors[] = "Date {$date}: " . $stmt->error;
    }
}

$stmt->close();

if (count($errors) > 0) {
    echo json_encode(['success' => false, 'message' => 'Partial failure', 'updated' => $successCount, 'errors' => $errors]);
} else {
    echo json_encode(['success' => true, 'message' => "Successfully updated {$successCount} transaction(s)."]);
}
exit;
