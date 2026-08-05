<?php
include '../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $contractNumber = $_POST['contract_number'] ?? '';
    $rfpNumber      = $_POST['rfp_number'] ?? '';

    if (!empty($contractNumber) && !empty($rfpNumber)) {

        $stmt = mysqli_prepare($conn, "
            UPDATE create_contract
            SET rfp_number = ?
            WHERE contract_number = ?
        ");

        mysqli_stmt_bind_param($stmt, "ss", $rfpNumber, $contractNumber);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("Location: user_page.php");
    exit;
}