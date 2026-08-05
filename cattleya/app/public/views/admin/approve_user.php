<?php
require __DIR__ . '/../../../config/database.php';
require __DIR__ . '/../includes/session_check.php'; // Updates last_active

// 1. Only allow if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: /login");
    exit;
}

// 2. Validate User ID and Role existence
if (!isset($_GET['id']) || !isset($_GET['role'])) {
    $_SESSION['error'] = "Missing user ID or assigned role.";
    header("Location: /admin/dashboard");
    exit;
}

$user_id = intval($_GET['id']);
$assigned_role = $_GET['role'];

// 3. Define allowed roles (Whitelist security)
// This prevents someone from manually typing ?role=hacker in the URL
$allowed_roles = [
    'encoder', 
    'admin', 
    'auditor', 
    'finance', 
    'cfo', 
    'cashier', 
    'operation_manager'
];

if (!in_array($assigned_role, $allowed_roles)) {
    $_SESSION['error'] = "Invalid role assignment.";
    header("Location: /admin/dashboard");
    exit;
}

try {
    // 4. Update both status AND role
    // Using named parameters for clarity
    $stmt = $pdo->prepare("UPDATE users SET status = 'active', role = :role WHERE id = :id");
    $stmt->execute([
        ':role' => $assigned_role,
        ':id'   => $user_id
    ]);

    if ($stmt->rowCount() > 0) {
        $friendly_role = ucfirst(str_replace('_', ' ', $assigned_role));
        $_SESSION['success'] = "User approved successfully as $friendly_role.";
    } else {
        $_SESSION['error'] = "User not found or already approved.";
    }

    header("Location: /admin/dashboard");
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: /admin/dashboard");
    exit;
}