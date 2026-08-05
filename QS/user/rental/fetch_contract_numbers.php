<?php
   session_start();

    include '../../config/config.php';

    if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    }

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch contract numbers where extract_request_status is not 'Requested'
$query = "SELECT contract_number FROM transactional WHERE extract_request_status != 'Requested'";
$result = mysqli_query($conn, $query);

$contractNumbers = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $contractNumbers[] = $row['contract_number'];
    }
}

// Return contract numbers as JSON
echo json_encode($contractNumbers);

mysqli_close($conn);
?>
