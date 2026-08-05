<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include '../../config/config.php';

    // Collect data from the POST request
    $selected_id = $_POST['selected_id'];
    $reviewed_by = $_POST['reviewed_by'];

    // Prepare and execute the SQL update query
    $updateQuery = "UPDATE create_contract SET request_status = 'Checked', reviewed_by = '$reviewed_by' WHERE id = '$selected_id' AND request_status = 'Received'";
    $result = mysqli_query($conn, $updateQuery);

    // Check the result of the query
    if ($result) {
        $response = array('status' => 'success', 'message' => 'Contract is pending for Approve!');
    } else {
        $response = array('status' => 'error', 'message' => 'Failed to update contract. Please try again.');
    }

    // Close the database connection
    mysqli_close($conn);

    // Return the JSON response
    echo json_encode($response);
} else {
    // Handle invalid request method
    $response = array('status' => 'error', 'message' => 'Invalid request method');
    echo json_encode($response);
}