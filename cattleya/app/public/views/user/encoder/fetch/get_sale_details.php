<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../../config/database.php'; 
// get-sale-details.php snippet
$block = $_GET['block'] ?? '';
$lot = $_GET['lot'] ?? '';
$product = $_GET['product'] ?? '';

// Fetch the LATEST sale record for this specific lot
$query = "SELECT * FROM sales 
          WHERE block_number = ? 
          AND lot_number = ? 
          AND product_name = ? 
          ORDER BY sale_id DESC LIMIT 1";

$stmt = $pdo->prepare($query);
$stmt->execute([$block, $lot, $product]);
$sale = $stmt->fetch(PDO::FETCH_ASSOC);

if ($sale) {
    echo json_encode(['success' => true, 'data' => $sale]);
} else {
    echo json_encode(['success' => false, 'message' => 'No record found']);
}