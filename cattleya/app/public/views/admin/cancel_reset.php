<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../includes/session_check.php';

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("
        UPDATE users
        SET reset_token = NULL,
            reset_expires = NULL
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    $_SESSION['success'] = "Reset request cancelled.";
}

header("Location: /cattleya/admin/dashboard");
exit;
