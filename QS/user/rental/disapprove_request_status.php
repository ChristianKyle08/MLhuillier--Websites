<?php
session_start();
header('Content-Type: application/json');
// Include database configuration
include_once '../../config/config.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve POST data
$selected_id = mysqli_real_escape_string($conn, $_POST['selected_id']);
$disapproved_by = mysqli_real_escape_string($conn, $_POST['disapproved_by']);
$audit_remarks = mysqli_real_escape_string($conn, $_POST['auditRemarks']); // Use 'auditRemarks' consistent with JavaScript key

// Prepare and execute the SQL update query
$updateQuery = "UPDATE create_contract SET request_status = 'Created', audit_note = '$audit_remarks' WHERE id = '$selected_id' AND request_status = 'Checked'";
$result = mysqli_query($conn, $updateQuery);

// Check the result of the query
if ($result) {
    $response = array('status' => 'success', 'message' => 'Contract return to creator successfully!');
} else {
    $response = array('status' => 'error', 'message' => 'Failed to update contract. Please try again.');
}

// Close the database connection
mysqli_close($conn);

// Return the JSON response
echo json_encode($response);
}
