<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

include '../../config/config.php';

// Basic checks
if (!isset($conn) || !$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection not available.']);
    exit;
}
if (empty($_POST['region']) || empty($_POST['contract_number'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
    exit;
}

$region = mysqli_real_escape_string($conn, $_POST['region']);
$contractNumber = mysqli_real_escape_string($conn, $_POST['contract_number']);

// Step: Rollback (set status to Unpaid and clear reason)
$updateQuery = "
    UPDATE transactional
    SET status = 'Unpaid',
        reason_termination = NULL
    WHERE region = '$region'
      AND contract_number = '$contractNumber'
      AND status = 'Termination Requested'
";
$updateResult = mysqli_query($conn, $updateQuery);

if ($updateResult === false) {
    $err = mysqli_error($conn);
    echo json_encode(['status' => 'error', 'message' => "Failed to update status: $err"]);
    mysqli_close($conn);
    exit;
}

$affected = mysqli_affected_rows($conn);

if ($affected > 0) {
    echo json_encode([
        'status' => 'success',
        'message' => "Termination request disapproved. Status set to <b>Unpaid</b> for $affected record(s)."
    ]);
} else {
    echo json_encode([
        'status' => 'warning',
        'message' => 'No records updated. Either there were no entries with status = "Termination Requested" or they were already changed.'
    ]);
}

mysqli_close($conn);
exit;
