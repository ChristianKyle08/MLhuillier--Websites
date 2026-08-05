<?php
header('Content-Type: application/json');
require __DIR__ . '/../../../../../config/database.php';

try {
    // We check for product_id as the primary filter
    $productId = $_GET['product_id'] ?? '';

    if (empty($productId)) {
        throw new Exception('Product ID is required');
    }

    $stmt = $pdo->prepare("SELECT DISTINCT block_number FROM product WHERE product_id = ? AND block_number IS NOT NULL ORDER BY block_number ASC");
    $stmt->execute([$productId]);
    $blocks = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($blocks);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}