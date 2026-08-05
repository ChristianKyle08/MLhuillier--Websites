<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}

if (isset($_GET['contract_number'])) {
    $contractNumber = $_GET['contract_number'];

    $sql = "SELECT MIN(effectivity_date) AS start_date, MAX(expiry_date) AS end_date 
            FROM escalation 
            WHERE contract_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $contractNumber);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = [];

    if ($row = $result->fetch_assoc()) {
        $response['start_date'] = $row['start_date'];
        $response['end_date'] = $row['end_date'];
    }

    echo json_encode($response);
}
?>