<?php
/**
 * process_waive.php
 * Handles the Approval or Disapproval of Penalty Waivers
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../../../../config/database.php'; 

// 1. Collect and validate input data
$paymentId    = $_POST['payment_id'] ?? null;
$customerId   = $_POST['customer_id'] ?? null;
$block        = $_POST['block_number'] ?? null;
$lot          = $_POST['lot_number'] ?? null;
$dueDate      = $_POST['due_date'] ?? null;
$actionStatus = $_POST['status'] ?? null; // 'Approved' or 'Rejected'

// Basic validation
if (!$paymentId || !$customerId || !$actionStatus) {
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required information to process this request.'
    ]);
    exit;
}

try {
    $pdo->beginTransaction();

    if ($actionStatus === 'Approved') {
        $sql = "UPDATE payments 
                SET request_waive = 'Approved',
                    penalty_status = 'Waived'
                WHERE customer_id = :cid 
                  AND block_number = :blk 
                  AND lot_number = :lot 
                  AND due_date = :due 
                  AND id = :pid";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cid' => $customerId,
            ':blk' => $block,
            ':lot' => $lot,
            ':due' => $dueDate,
            ':pid' => $paymentId
        ]);

    } elseif ($actionStatus === 'Rejected') {
        $sql = "UPDATE payments 
                SET request_waive = 'Declined',
                    penalty_paid = NULL,
                    penalty_status = NULL
                WHERE customer_id = :cid 
                  AND block_number = :blk 
                  AND lot_number = :lot 
                  AND due_date = :due 
                  AND id = :pid";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':cid' => $customerId,
            ':blk' => $block,
            ':lot' => $lot,
            ':due' => $dueDate,
            ':pid' => $paymentId
        ]);
    } else {
        throw new Exception("Invalid status action received.");
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Request has been successfully $actionStatus."]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'success' => false, 
        'message' => 'Database Error: ' . $e->getMessage()
    ]);
}
?>