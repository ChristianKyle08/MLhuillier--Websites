<?php
session_start();
include '../../config/config.php';

header('Content-Type: application/json');

if (!isset($_POST['contract_number']) || !isset($_POST['transaction_date'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit();
}

$contractNumber = $_POST['contract_number'];
$transactionDate = $_POST['transaction_date'];

// Prepared statement
$stmt = $conn->prepare("
    SELECT 
        l1_firstname, l1_middlename, l1_lastname, l1_gender,
        l2_firstname, l2_middlename, l2_lastname, l2_gender,
        l3_firstname, l3_middlename, l3_lastname, l3_gender,
        l4_firstname, l4_middlename, l4_lastname, l4_gender,
        l5_firstname, l5_middlename, l5_lastname, l5_gender
    FROM transactional
    WHERE contract_number = ? AND transaction_date = ?
    LIMIT 1
");
$stmt->bind_param("ss", $contractNumber, $transactionDate);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'data' => $row]);
} else {
    echo json_encode(['success' => false, 'message' => 'No lessor found']);
}

$stmt->close();
$conn->close();
