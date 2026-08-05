<?php
// request_waiver.php
header('Content-Type: application/json');

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!isset($data['requests']) || empty($data['requests'])) {
    echo json_encode(['success' => false, 'message' => 'No payment data received.']);
    exit;
}

require_once __DIR__ . '/../../../../../config/database.php'; 

try {
    $pdo->beginTransaction(); 

    // Prepared statement including penalty_paid
    $stmt = $pdo->prepare("
        UPDATE payments 
        SET request_waive = 'Requested',
            waive_reason = :waive_reason,
            penalty_paid = :penalty_paid
        WHERE customer_id = :customer_id 
        AND due_date = :due_date 
        AND block_number = :block_number 
        AND lot_number = :lot_number
    ");

    foreach ($data['requests'] as $req) {
        // We cast to float to ensure numeric consistency in the DB
        $penaltyValue = (float)($req['penalty'] ?? 0);

        $stmt->execute([
            ':waive_reason'  => $req['reason'], 
            ':penalty_paid'  => $penaltyValue, 
            ':customer_id'   => $req['customer_id'],
            ':due_date'      => $req['due_date'],
            ':block_number'  => $req['block_number'],
            ':lot_number'    => $req['lot_number']
        ]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Waivers requested and penalty recorded.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>