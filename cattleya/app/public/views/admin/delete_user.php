<?php
require __DIR__ . '/../../../config/database.php';
require __DIR__ . '/../includes/session_check.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: /login");
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: /admin/dashboard");
    exit;
}

$user_id = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND status = 'pending'");
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['success'] = "User deleted successfully.";
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
?>
