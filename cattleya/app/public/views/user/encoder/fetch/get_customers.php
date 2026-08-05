<?php
// 1. Force JSON header immediately
header('Content-Type: application/json');

// 2. Wrap everything in a try-block for error handling
try {
    // Check if the database config exists before requiring
    $configPath = __DIR__ . '/../../../../../config/database.php';
    if (!file_exists($configPath)) {
        throw new Exception("Database configuration file missing.");
    }
    require_once $configPath; 

    $action = $_GET['action'] ?? '';

    if ($action === 'list') {
        $stmt = $pdo->query("SELECT customer_id, firstname, lastname FROM customers ORDER BY customer_id ASC");
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } 
    elseif ($action === 'details' && !empty($_GET['id'])) {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
        $stmt->execute([$_GET['id']]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($customer) {
            echo json_encode(['success' => true, 'data' => $customer]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Customer not found']);
        }
    } else {
        // Handle cases where action is invalid or ID is missing
        echo json_encode(['success' => false, 'message' => 'Invalid action or missing ID']);
    }

} catch (Exception $e) {
    // Return a 500 error if the database connection or query fails
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server Error: ' . $e->getMessage()
    ]);
}
