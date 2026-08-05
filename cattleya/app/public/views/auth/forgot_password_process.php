<?php
require __DIR__ . '/../../../config/database.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $_SESSION['error'] = "Username not found.";
            header("Location: /cattleya/forgot-password");
            exit;
        }

        // Generate reset token
        $token = bin2hex(random_bytes(16));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $stmt->execute([$token, $expires, $user['id']]);

        // Send email (example, adjust to your email system)
        $resetLink = "http://localhost:8081/cattleya/reset-password?token=$token";
        // mail($user['username'], "Reset Password", "Click this link: $resetLink");

        $_SESSION['success'] = "Password reset link sent to admin.";
        header("Location: /cattleya/forgot-password");
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: /cattleya/forgot-password");
        exit;
    }
}
?>
