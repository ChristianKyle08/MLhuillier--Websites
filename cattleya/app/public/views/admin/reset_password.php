<?php
require_once __DIR__ . '/../../../config/database.php'; // your DB connection
require __DIR__ . '/../includes/session_check.php'; // <-- updates last_active

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $user_id = intval($_POST['user_id'] ?? 0);

    if (!$user_id) throw new Exception('Missing user ID.');

    // Default password
    $defaultPassword = 'MLINC12345@';
    $hashed = password_hash($defaultPassword, PASSWORD_BCRYPT);

    // Update user password
    $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
    $stmt->execute([$hashed, $user_id]);

    echo json_encode([
        'success' => true,
        'defaultPassword' => $defaultPassword
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
