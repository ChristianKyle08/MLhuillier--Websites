<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../../../config/database.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
    exit;
}

// Collect POST data
$product_name      = $_POST['product_name'] ?? null;
$block_number      = $_POST['block_number'] ?? null;
$lot_number         = $_POST['lot_number'] ?? null;
$niche_type        = $_POST['niche_type'] ?? null;
$block_description = $_POST['block_description'] ?? null;

// Ensure financial values are numeric (float)
$tcp               = floatval($_POST['main_tcp'] ?? 0);
$cash_price               = floatval($_POST['cash_price'] ?? 0);
$marketing_budget  = floatval($_POST['marketing_budget'] ?? 0);
$care_fund         = floatval($_POST['care_fund'] ?? 0);
$vat               = floatval($_POST['vat'] ?? 0);
$lot_price         = floatval($_POST['lot_price'] ?? 0);

$status            = $_POST['status'] ?? 'available';
$created_by        = $_POST['created_by'] ?? ($_SESSION['user_name'] ?? 'System');

// 1. VALIDATION: Check required fields
if (!$product_name || !$lot_number || !$block_number) {
    echo json_encode([
        "success" => false, 
        "message" => "Missing required fields: Project, Block, and Lot."
    ]);
    exit;
}

try {
    // 2. DUPLICATE CHECK: Prevent adding the same Lot/Block twice for the same product
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM product WHERE product_name = ? AND block_number = ? AND lot_number = ?");
    $checkStmt->execute([$product_name, $block_number, $lot_number]);
    
    if ($checkStmt->fetchColumn() > 0) {
        echo json_encode([
            "success" => false, 
            "message" => "Registration Failed: Block $block_number Lot $lot_number already exists for $product_name."
        ]);
        exit;
    }

    // 3. INSERTION
    $stmt = $pdo->prepare("
        INSERT INTO product 
        (product_name, block_number, lot_number, niche_type, block_description, tcp, cash_price, marketing_budget, care_fund, vat, lot_price, status, created_by)
        VALUES (:product_name, :block_number, :lot_number, :niche_type, :block_description, :tcp, :cash_price, :marketing_budget, :care_fund, :vat, :lot_price, :status, :created_by)
    ");

    $params = [
        ':product_name'      => $product_name,
        ':block_number'      => $block_number,
        ':lot_number'        => $lot_number,
        ':niche_type'        => $niche_type,
        ':block_description' => $block_description,
        ':tcp'               => $tcp,
        ':cash_price'        => $cash_price,
        ':marketing_budget'  => $marketing_budget,
        ':care_fund'         => $care_fund,
        ':vat'               => $vat,
        ':lot_price'         => $lot_price,
        ':status'            => $status,
        ':created_by'        => $created_by
    ];

    if ($stmt->execute($params)) {
        echo json_encode(["success" => true, "message" => "New asset registered successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to execute database query."]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database Error: " . $e->getMessage()]);
}
exit;