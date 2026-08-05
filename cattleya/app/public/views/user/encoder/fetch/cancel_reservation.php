<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../../config/database.php'; 

try {
    $saleId   = $_POST['sales_id'] ?? null;
    $prodName = $_POST['product_name'] ?? null;
    $block    = $_POST['block_number'] ?? null;
    $lot      = $_POST['lot_number'] ?? null;
    $remarks  = $_POST['remarks'] ?? 'No reason provided';
    
    // Capture the user from the session
    $cancelBy = $_SESSION['user_name'] ?? 'System Admin';

    if (!$saleId || !$prodName) {
        throw new Exception("Missing required transaction data.");
    }

    $pdo->beginTransaction();

    // 1. Revert the Product back to 'available'
    $stmt1 = $pdo->prepare("UPDATE product 
                            SET status = 'available' 
                            WHERE product_name = ? AND block_number = ? AND lot_number = ?");
    $stmt1->execute([$prodName, $block, $lot]);

    // 2. Mark the specific Sales record as 'cancelled'
    // We update by sale_id for 100% accuracy
    $stmt2 = $pdo->prepare("UPDATE sales 
                            SET sales_status = 'cancelled',
                                cancel_by = ?, 
                                cancel_remarks = ?,
                                cancelled_at = NOW() 
                            WHERE sale_id = ?");
    $stmt2->execute([$cancelBy, $remarks, $saleId]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}