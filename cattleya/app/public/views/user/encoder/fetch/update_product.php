<?php
require_once __DIR__ . '/../../../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = intval($_POST['id']);
    $product_name = trim($_POST['product_name']);
    $blocks = intval($_POST['number_of_blocks']);
    $address = trim($_POST['address']);
    $owner = trim($_POST['owner']);
    $status = $_POST['status'];
    $updated_by = $_SESSION['user_name'];

    $sql = "UPDATE product_profile SET
            product_name = :product_name,
            number_of_blocks = :blocks,
            address = :address,
            owner = :owner,
            updated_by = :updated_by,
            status = :status
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);

    $success = $stmt->execute([
        ':product_name'=>$product_name,
        ':blocks'=>$blocks,
        ':address'=>$address,
        ':owner'=>$owner,
        ':updated_by'=>$updated_by,
        ':status'=>$status,
        ':id'=>$id
    ]);

    if ($success) {
        header("Location: /cattleya/user/encoder/product?success=Product updated successfully");
    } else {
        header("Location: /cattleya/user/encoder/product?error=Failed to update product");
    }

}