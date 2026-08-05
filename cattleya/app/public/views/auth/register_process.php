<?php
require __DIR__ . '/../../../config/database.php';
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: /register");
    exit;
}

// Required fields
$required = ['first_name','last_name','username','email','password','confirm_password'];

foreach ($required as $field) {
    if (empty($_POST[$field])) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: /register");
        exit;
    }
}

// Sanitize
$first = trim($_POST['first_name']);
$last = trim($_POST['last_name']);
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password'];
$confirm = $_POST['confirm_password'];

// Password check
if ($password !== $confirm) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: /register");
    exit;
}

// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email address.";
    header("Location: /register");
    exit;
}

try {
    // Check duplicates
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
    $check->execute([$email, $username]);

    if ($check->rowCount() > 0) {
        $_SESSION['error'] = "Email or username already exists.";
        header("Location: /register");
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert user with status = pending
    $stmt = $pdo->prepare("
        INSERT INTO users (first_name, last_name, username, email, password, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");

    $stmt->execute([$first, $last, $username, $email, $hashedPassword]);

    $_SESSION['success'] = "Registration successful. Your account is pending admin approval.";
    header("Location: /login");
    exit;

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: /register");
    exit;
}
?>
