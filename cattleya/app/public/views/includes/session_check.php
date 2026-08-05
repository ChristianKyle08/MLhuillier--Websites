<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) session_start();
require __DIR__ . '/../../../config/database.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Set PHP timezone to Philippines
    date_default_timezone_set('Asia/Manila');

    // Update last_active timestamp
    $stmt = $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
    $stmt->execute([$user_id]);
}