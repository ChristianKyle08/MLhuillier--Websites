<?php
// get-product-details.php
require __DIR__ . '/../../../../../config/database.php';

// Set header to JSON so JavaScript can read it
header('Content-Type: application/json');

$product_name = $_GET['name'] ?? '';

if (empty($product_name)) {
    echo json_encode([]);
    exit;
}

try {
    // We fetch from the 'product' table where you saved the block and lot
    $stmt = $pdo->prepare("SELECT product_id, lot_number, block_number,niche_type, block_description, lot_price, tcp, cash_price, status FROM product WHERE product_name = ?");
    $stmt->execute([$product_name]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($results);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}