<?php
require_once __DIR__ . '/../../../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $product_name = trim($_POST['product_name']);
    $blocks = intval($_POST['number_of_blocks']);
    $address = trim($_POST['address']);
    $owner = trim($_POST['owner']);
    $created_by = $_SESSION['user_name'];

    $sql = "INSERT INTO product_profile
            (product_name, number_of_blocks, address, owner, created_by)
            VALUES
            (:product_name, :blocks, :address, :owner, :created_by)";

    $stmt = $pdo->prepare($sql);

    $success = $stmt->execute([
        ':product_name' => $product_name,
        ':blocks' => $blocks,
        ':address' => $address,
        ':owner' => $owner,
        ':created_by' => $created_by
    ]);

    if ($success) {
        header("Location: /cattleya/user/encoder/product?success=Product added successfully");
    exit;
    } else {
        header("Location: /cattleya/user/encoder/product?error=Failed to add product");
    exit;
    }

}