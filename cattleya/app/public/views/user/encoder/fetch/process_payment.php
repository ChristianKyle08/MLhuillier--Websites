<?php
ini_set('display_errors', 0); 
error_reporting(E_ALL);
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../../config/database.php'; 

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['payments'])) {
    echo json_encode(['success' => false, 'message' => 'No payment details received.']);
    exit;
}

$method = $data['payment_method'] ?? 'N/A';
$ref    = $data['reference_no'] ?? 'N/A';
$ar_no  = $data['ar_number'] ?? '';
$or_no  = $data['or_number'] ?? '';
date_default_timezone_set('Asia/Manila');
$now = date('Y-m-d H:i:s');

// Extract the total settle amount from the payload
$totalSettleAmount = (float)($data['total_settle_amount'] ?? 0); 

// Check if empty string and convert to null to prevent MySQL Invalid Datetime format error
$pdc_bank_name    = !empty($data['pdc_bank_name']) ? $data['pdc_bank_name'] : null;
$pdc_check_number = !empty($data['pdc_check_number']) ? $data['pdc_check_number'] : null;
$pdc_bank_number  = !empty($data['pdc_bank_number']) ? $data['pdc_bank_number'] : null;
$pdc_check_date   = !empty($data['pdc_check_date']) ? $data['pdc_check_date'] : null;

$wants_release_broker = !empty($data['release_broker']);
$wants_release_um     = !empty($data['release_um']);
$wants_release_agent  = !empty($data['release_agent']);

try {
    $pdo->beginTransaction();
    
    // Add an array to track the updated payment IDs
    $processed_ids = [];

    // --- ADDED: Prepare statements to fetch TCP and previous total paid ---
    // (Assumes your sales table uses 'tcp' for Total Contract Price. Adjust if your column name differs.)
    $sqlSalesInfo = "SELECT p.sale_id, s.tcp FROM payments p JOIN sales s ON p.sale_id = s.sale_id WHERE p.id = ?";
    $stmtSalesInfo = $pdo->prepare($sqlSalesInfo);

    $sqlSumPaid = "SELECT SUM(amount_paid) as total_paid FROM payments WHERE sale_id = ? AND id != ?";
    $stmtSumPaid = $pdo->prepare($sqlSumPaid);
    // ----------------------------------------------------------------------

    // Updated SQL query to include PDC columns AND the new receivable amount columns
    $sql = "UPDATE payments SET 
                amount_paid = ?, overpayment = ?, penalty_paid = ?,
                payment_date = ?, payment_method = ?, reference_no = ?, 
                ar_number = ?, or_number = ?,
                status = ?, broker_commission_amount = ?, um_commission_amount = ?,
                agent_commission_amount = ?, broker_commission_status = ?,
                um_commission_status = ?, agent_commission_status = ?, penalty_status = ?,
                pdc_bank_name = ?, pdc_check_number = ?, pdc_bank_number = ?, pdc_check_date = ?,
                ending_receivable_amount = ?
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);

    foreach ($data['payments'] as $p) {
        $paymentId   = (int)$p['id'];
        // Grab the calculated amount specifically for THIS row
        $rowAmountPaid = (float)($p['amount_paid'] ?? 0); 
        
        // --- ADDED: Compute Receivables ---
        $stmtSalesInfo->execute([$paymentId]);
        $saleData = $stmtSalesInfo->fetch(PDO::FETCH_ASSOC);
        $saleId = $saleData['sale_id'] ?? null;
        $tcp = (float)($saleData['tcp'] ?? 0); 
        
        $previousTotalPaid = 0;
        if ($saleId) {
            // Get all payments made on this sale EXCEPT the current one being processed
            $stmtSumPaid->execute([$saleId, $paymentId]);
            $sumData = $stmtSumPaid->fetch(PDO::FETCH_ASSOC);
            $previousTotalPaid = (float)($sumData['total_paid'] ?? 0);
        }

        $totalAmountPaid = $previousTotalPaid + $rowAmountPaid;
        
        // Apply the requested formulas
        $ending_receivable = $tcp - $totalAmountPaid;
        // ----------------------------------

        $overpayment = (float)($p['overpayment'] ?? 0);
        $penalty     = (float)($p['penalty'] ?? 0); 
        $isWaived    = !empty($p['is_waived']);
        $commBroker  = (float)($p['comm_broker'] ?? 0);
        $commUm      = (float)($p['comm_um'] ?? 0);
        $commAgent   = (float)($p['comm_agent'] ?? 0);

        // Condition to set status based on PDC
        $mainStatus = (trim(strtoupper($method)) === 'PDC') ? 'Pending' : 'Paid'; 
        
        // Added condition: If payment method is PDC, penalty status becomes 'Pending' instead of 'Paid'
        $penaltyStatus = $isWaived ? 'Waived' : ($penalty > 0 ? (trim(strtoupper($method)) === 'PDC' ? 'Pending' : 'Paid') : null);

        $row_b_stat = ($commBroker > 0) ? ($wants_release_broker ? 'Released' : 'Pending') : null;
        $row_u_stat = ($commUm > 0)     ? ($wants_release_um     ? 'Released' : 'Pending') : null;
        $row_a_stat = ($commAgent > 0)  ? ($wants_release_agent  ? 'Released' : 'Pending') : null;

        // Ensure PDC values are only inserted if the method is actually PDC
        $final_pdc_bank       = (trim(strtoupper($method)) === 'PDC') ? $pdc_bank_name : null;
        $final_pdc_check      = (trim(strtoupper($method)) === 'PDC') ? $pdc_check_number : null;
        $final_pdc_bankNumber = (trim(strtoupper($method)) === 'PDC') ? $pdc_bank_number : null;
        $final_pdc_date       = (trim(strtoupper($method)) === 'PDC') ? $pdc_check_date : null;

        $stmt->execute([
            $rowAmountPaid, $overpayment, $penalty, $now, $method, $ref, $ar_no, $or_no,
            $mainStatus, $commBroker, $commUm, $commAgent,
            $row_b_stat, $row_u_stat, $row_a_stat, $penaltyStatus,
            $final_pdc_bank, $final_pdc_check, $final_pdc_bankNumber, $final_pdc_date,
            $ending_receivable, // <-- Mapped to the new SQL placeholders
            $paymentId 
        ]);
        
        // Store the ID of the payment we just updated
        $processed_ids[] = $paymentId;
    }

    $pdo->commit();
    // Return the IDs as a comma-separated string in the JSON response
    echo json_encode(['success' => true, 'payment_ids' => implode(',', $processed_ids)]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 
?>