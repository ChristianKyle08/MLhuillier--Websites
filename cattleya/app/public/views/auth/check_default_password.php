<?php
require __DIR__ . '/../../../config/database.php';
header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Changed $email to $username
$username = trim($input['username'] ?? '');
$defaultPassword = 'MLINC12345@';

if (!$username) {
    echo json_encode(['is_default' => false]);
    exit;
}

try {
    // Modified query to check the username column
    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Verify if the current hashed password matches the system's default
        $isDefault = password_verify($defaultPassword, $user['password']);

        echo json_encode(['is_default' => $isDefault]);
    } else {
        echo json_encode(['is_default' => false]);
    }
} catch (PDOException $e) {
    // Silent fail for security; returns false on system error
    echo json_encode(['is_default' => false]);
}
?>