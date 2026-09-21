<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve role and user details from session with fallbacks
    $role       = $_SESSION['role'] ?? 'encoder';
    $created_by = $_SESSION['user_name'] ?? 'System';

    // Sanitize input values
    $product_name = trim($_POST['product_name'] ?? '');
    $blocks       = intval($_POST['number_of_blocks'] ?? 0);
    $address      = trim($_POST['address'] ?? '');
    $owner        = trim($_POST['owner'] ?? '');

    $sql = "INSERT INTO product_profile
            (product_name, number_of_blocks, address, owner, created_by)
            VALUES
            (:product_name, :blocks, :address, :owner, :created_by)";

    $stmt = $pdo->prepare($sql);

    $success = $stmt->execute([
        ':product_name' => $product_name,
        ':blocks'       => $blocks,
        ':address'      => $address,
        ':owner'        => $owner,
        ':created_by'   => $created_by
    ]);

    // Build the dynamic route using the user's role
    $redirectUrl = "/cattleya/user/{$role}/product";

    if ($success) {
        $msg = urlencode("Product added successfully");
        header("Location: {$redirectUrl}?success={$msg}");
    } else {
        $msg = urlencode("Failed to add product");
        header("Location: {$redirectUrl}?error={$msg}");
    }
    exit;
}