<?php
// Prevent accidental whitespace/errors from breaking JSON format
ob_start();
include '../../config/config.php';

header('Content-Type: application/json');
ob_clean(); // Clears any output buffer (like spaces in config.php)

if (!isset($_GET['branch_id']) || empty($_GET['branch_id'])) {
    echo json_encode(['error' => 'Missing ID']);
    exit;
}

$branchId = $_GET['branch_id'];

$stmt = $conn->prepare("SELECT * FROM branch_insurance WHERE branch_id = ?");
$stmt->bind_param("s", $branchId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Ensure numeric values or nulls don't break the response
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Not found']);
}

$stmt->close();
$conn->close();