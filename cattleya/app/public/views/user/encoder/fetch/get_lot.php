<?php
header('Content-Type: application/json');
require __DIR__ . '/../../../../../config/database.php';

try {
    $productId = $_GET['product_id'] ?? null;
    $block     = $_GET['block'] ?? null;

    if (!$productId || !$block) {
        throw new Exception('Product ID and Block are required.');
    }

    // 1. First, get the common Product Name for this ID
    $nameStmt = $pdo->prepare("SELECT product_name FROM product WHERE product_id = ?");
    $nameStmt->execute([$productId]);
    $productName = $nameStmt->fetchColumn();

    // 2. Now, fetch ALL lots in this Block under that Product Name 
    // This ensures we get the Niche, Description, and Price for every lot in the group
    $stmt = $pdo->prepare("
        SELECT 
            product_id, 
            lot_number, 
            niche_type, 
            block_description, 
            lot_price 
        FROM product 
        WHERE product_name = :pname
        AND block_number = :block 
        AND lot_number IS NOT NULL
        ORDER BY CAST(lot_number AS UNSIGNED) ASC
    ");
    
    $stmt->execute(['pname' => $productName, 'block' => $block]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}