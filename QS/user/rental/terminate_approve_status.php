<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include '../../config/config.php';

    // Collect data from the POST request
    $region = mysqli_real_escape_string($conn, $_POST['region']);
    $contractNumber = mysqli_real_escape_string($conn, $_POST['contract_number']);
    $terminatedBy = mysqli_real_escape_string($conn, $_POST['terminated_by']); // Get the terminated_by value

    // Prepare and execute the SQL update query
    $updateQuery = "UPDATE transactional SET status = 'Terminated', terminated_by = '$terminatedBy' WHERE region = '$region' AND contract_number = '$contractNumber' AND status NOT IN ('Paid','PBB','Cancelled')";
    $result = mysqli_query($conn, $updateQuery);

    $updateQuery = "UPDATE create_contract SET request_status = 'Terminated' WHERE region = '$region' AND contract_number = '$contractNumber' AND status NOT IN ('Paid','PBB','Cancelled')";
    $result = mysqli_query($conn, $updateQuery);

    // Check the result of the query
    if ($result) {
        $response = array('status' => 'success', 'message' => 'Contract terminated successfully!');
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
?>
