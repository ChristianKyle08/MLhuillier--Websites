<?php
require __DIR__ . '/../../../../../config/database.php';

$product_name = $_GET['product_name'] ?? '';
$block_filter = $_GET['block_number'] ?? ''; // New filter

$response = [
    'success' => true,
    'blocks' => [],
    'lots' => [],
    'niches' => []
];

// 1. Always get Blocks for the product
$stmt = $pdo->prepare("SELECT DISTINCT block_number FROM product WHERE product_name = ? ORDER BY block_number ASC");
$stmt->execute([$product_name]);
$response['blocks'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 2. Get Lots filtered by Block (if block is provided)
if (!empty($block_filter)) {
    $stmt = $pdo->prepare("SELECT DISTINCT lot_number FROM product WHERE product_name = ? AND block_number = ? ORDER BY CAST(lot_number AS UNSIGNED) ASC");
    $stmt->execute([$product_name, $block_filter]);
} else {
    // Default: Get all lots for this product if no block selected
    $stmt = $pdo->prepare("SELECT DISTINCT lot_number FROM product WHERE product_name = ? ORDER BY lot_number ASC");
    $stmt->execute([$product_name]);
}
$response['lots'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 3. Get Unique Niche Types
$stmt = $pdo->prepare("SELECT DISTINCT niche_type FROM product WHERE niche_type IS NOT NULL ORDER BY niche_type ASC");
$stmt->execute();
$response['niches'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode($response);