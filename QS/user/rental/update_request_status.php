<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include '../../config/config.php';

    if (!$conn) {
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
        exit;
    }

    // Collect and sanitize POST data
    $selected_id = mysqli_real_escape_string($conn, $_POST['selected_id']);
    $action = mysqli_real_escape_string($conn, $_POST['action']);
    $reviewed_by = mysqli_real_escape_string($conn, $_POST['reviewed_by']);

    // Initialize response
    $response = ['status' => 'error', 'message' => 'Invalid action'];

    if ($action === 'review') {
        // Only update `rfp_status`, but do not confirm the transaction
        $query = "UPDATE create_contract SET request_status = 'Ready', rfp_status = 'Reviewed', reviewed_by = '$reviewed_by' WHERE id = '$selected_id' AND request_status = 'Prepared'";
        if (mysqli_query($conn, $query)) {
            $response = ['status' => 'success', 'message' => 'The contract has been reviewed and is ready for RFP!'];
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to update review status'];
        }
    } elseif ($action === 'confirm') {
        // Confirm the contract (must be reviewed first)
        $query = "UPDATE create_contract SET request_status = 'Received', reviewed_by = '$reviewed_by' WHERE id = '$selected_id'";
        if (mysqli_query($conn, $query)) {
            $response = ['status' => 'success', 'message' => 'Contract has been confirmed!'];
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to confirm contract'];
        }
    }

    // Close connection and return JSON response
    mysqli_close($conn);
    echo json_encode($response);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
