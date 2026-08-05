<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}
$branch = $_GET['branch'] ?? null;
$contract_number = $_GET['contract_number'] ?? null;

if ($branch && $contract_number) {
    $sql = "SELECT * FROM create_contract WHERE branch = ? AND contract_number = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $branch, $contract_number);
    $stmt->execute();
    $result = $stmt->get_result();

    $details = $result->fetch_assoc();

    if ($details) {
        echo json_encode($details);
    } else {
        echo json_encode(null);
    }
}
$conn->close();
?>
