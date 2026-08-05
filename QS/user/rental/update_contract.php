<?php
// Include your database connection file
include '../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $contract_number = $_POST['contract_number'];
    $branch = $_POST['branch'];
    $contract_start = $_POST['contract_start'];
    $contract_end = $_POST['contract_end'];

    $stmt = $pdo->prepare("UPDATE create_contract SET 
        contract_number = ?, 
        branch = ?, 
        contract_start = ?, 
        contract_end = ?
        WHERE id = ?");
    
    $success = $stmt->execute([
        $contract_number,
        $branch,
        $contract_start,
        $contract_end,
        $id
    ]);

    if ($success) {
        header("Location: user_page.php?updated=1");
        exit;
    } else {
        echo "Failed to update contract.";
    }
}
?>