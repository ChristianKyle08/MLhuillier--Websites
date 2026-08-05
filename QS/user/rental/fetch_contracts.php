<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}

$branch_id = $_GET['branch_id'] ?? null;

if ($branch_id) {
    $sql = "SELECT contract_number, mode_of_payment FROM create_contract WHERE branch_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $contracts = [];
    while ($row = $result->fetch_assoc()) {
        $contracts[] = $row;
    }

    echo json_encode($contracts);
}

$conn->close();
?>
