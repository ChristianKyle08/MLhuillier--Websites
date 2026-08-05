<?php
// Start session at the absolute top
session_start();

// Standardize error reporting for debugging (Remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); 

include('../../config/config.php');

header('Content-Type: application/json');

// 1. Fix Session Key: Your login script sets 'user_email'
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired.']);
    exit();
}

// 2. Database Connection (Ensure $conn is available from config)
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

if ($branch_id > 0) {
    // 3. Query the MAX series
    $stmt = $conn->prepare("SELECT MAX(series) AS max_series FROM create_contract WHERE branch_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $branch_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // If no records found, start at 1
        $next_series = ($row && $row['max_series']) ? intval($row['max_series']) + 1 : 1;

        echo json_encode([
            'success' => true, 
            'next_series' => $next_series
        ]);
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Query preparation failed.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Branch ID.']);
}
exit();