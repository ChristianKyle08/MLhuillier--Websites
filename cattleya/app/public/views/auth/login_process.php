<?php
require __DIR__ . '/../../../config/database.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /cattleya/login");
    exit;
}

// Get form values - Swapped email for username
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if ($username === '' || $password === '') {
    $_SESSION['error'] = "Username and password are required.";
    header("Location: /cattleya/login");
    exit;
}

try {
    // Modified query to fetch by username
    $stmt = $pdo->prepare("
        SELECT id, username, email, password, role, status, first_name, last_name
        FROM users
        WHERE username = ?
        LIMIT 1
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Update error message to reflect username login
    if (!$user || !password_verify($password, $user['password'])) {
        $_SESSION['error'] = "Invalid username or password.";
        header("Location: /cattleya/login");
        exit;
    }

    // Status checks
    if ($user['status'] === 'pending') {
        $_SESSION['error'] = "Your account is pending approval.";
        header("Location: /cattleya/login");
        exit;
    }

    if ($user['status'] === 'inactive') {
        $_SESSION['error'] = "Your account is inactive. Contact administrator.";
        header("Location: /cattleya/login");
        exit;
    }

    // Set Session Variables
    $_SESSION['user_id']       = $user['id'];
    $_SESSION['user_username'] = $user['username'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['user_name']     = $user['first_name'].' '.$user['last_name'];
    $_SESSION['role']          = $user['role'];

    // Force password change if default
    $defaultPassword = 'MLINC12345@';
    if ($password === $defaultPassword) {
        $_SESSION['change_password_required'] = true;
        header("Location: /cattleya/change-password");
        exit;
    }

    unset($_SESSION['change_password_required']);

    // Remember me logic
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
        $stmt->execute([$token, $user['id']]);
        setcookie('remember_token', $token, time() + (86400*30), '/', '', true, true);
    }

    // Role-based redirection
    switch ($user['role']) {
        case 'admin':
            header("Location: /cattleya/admin/dashboard");
            break;
    
        case 'finance':
            header("Location: /cattleya/finance/dashboard");
            break;
    
        case 'auditor':
            header("Location: /cattleya/auditor/dashboard");
            break;
    
        case 'encoder':
            // Specific check for encoder signature
            $stmt = $pdo->prepare("SELECT signature FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $sig = $stmt->fetchColumn();
    
            if (!$sig) {
                header("Location: /cattleya/user/signature");
            } else {
                header("Location: /cattleya/user/encoder/dashboard");
            }
            break;
    
        default:
            header("Location: /cattleya/login");
    }
    exit;

} catch (PDOException $e) {
    // It's good practice to log $e->getMessage() to a file, but keep it hidden from users
    $_SESSION['error'] = "A system error occurred. Please try again.";
    header("Location: /cattleya/login");
    exit;
}