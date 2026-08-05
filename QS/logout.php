<?php
/**
 * Logout Logic - Secure & Synchronized
 */
ob_start();
session_start();
include 'config/config.php';

// 1. Set the time zone and prepare timestamp
date_default_timezone_set('Asia/Manila');
$current_day_and_time = date('Y-m-d H:i:s');

// 2. Determine which identifier to log (handle both admin and user)
$identifier = '';
if (isset($_SESSION['admin_email'])) {
    $identifier = $_SESSION['admin_email'];
} elseif (isset($_SESSION['user_email'])) {
    $identifier = $_SESSION['user_email'];
}

// 3. Update 'Last Online' status in the database
if (!empty($identifier) && $conn) {
    $safe_id = mysqli_real_escape_string($conn, $identifier);
    
    // Updated query to check both email and username columns to match login logic
    $update_query = "UPDATE user_form 
                     SET last_online = '$current_day_and_time' 
                     WHERE email = '$safe_id' OR username = '$safe_id'";
    
    mysqli_query($conn, $update_query);
}

// 4. Securely clear all session data
$_SESSION = array(); // Wipe the session array

// If it's desired to kill the session, also delete the session cookie.
// This is a critical security step many developers skip!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_unset();
session_destroy();

// 5. Prevent Back-Button Caching
// This ensures that when a user logs out, they can't click "Back" to see the previous page
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// 6. Redirect to login or home
header('Location: index.php');

ob_end_flush();
exit();
?>