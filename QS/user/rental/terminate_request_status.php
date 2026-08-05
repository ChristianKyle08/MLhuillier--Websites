<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include '../../config/config.php';

    $region = trim($_POST['region'] ?? '');
    $contractNumber = trim($_POST['contract_number'] ?? '');
    $reasonTermination = trim($_POST['remarks'] ?? '');
    
    if ($region === '' || $contractNumber === '' || $reasonTermination === '') {
        echo json_encode([
            'status' => 'error',
            'message' => 'All fields are required'
        ]);
        exit;
    }
    

    // Use prepared statement for safe update
    $updateQuery = "
        UPDATE transactional 
        SET status = 'Termination Requested', reason_termination = ? 
        WHERE region = ? 
          AND contract_number = ? 
          AND status NOT IN ('Paid','PBB','Cancelled')
    ";

    $stmt = $conn->prepare($updateQuery);
    if ($stmt) {
        $stmt->bind_param("sss", $reasonTermination, $region, $contractNumber);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $response = ['status' => 'success', 'message' => 'Request sent successfully!'];
        } else {
            $response = ['status' => 'error', 'message' => 'No rows updated. Please check contract status.'];
        }

        $stmt->close();
    } else {
        $response = ['status' => 'error', 'message' => 'Database error: ' . $conn->error];
    }

    $conn->close();
    echo json_encode($response);

} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method'
    ]);
}
?>
