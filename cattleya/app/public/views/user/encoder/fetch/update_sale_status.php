<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../../config/database.php'; 

try {
    $prodName = $_POST['product_name'] ?? null;
    $block    = $_POST['block_number'] ?? null;
    $lot      = $_POST['lot_number'] ?? null;
    $status   = 'sold'; // Explicitly setting status

    if (!$prodName || !$block || !$lot || !$status) {
        throw new Exception("Missing required identifiers or status.");
    }

    $pdo->beginTransaction();

    // 1. Update Product status in inventory
    $stmt1 = $pdo->prepare("UPDATE product SET status = ? WHERE product_name = ? AND block_number = ? AND lot_number = ?");
    $stmt1->execute([$status, $prodName, $block, $lot]);

    // 2. Update the most recent transaction record for this lot
    $stmt2 = $pdo->prepare("UPDATE sales 
                            SET sales_status = ?
                            WHERE product_name = ? 
                              AND block_number = ? 
                              AND lot_number = ? 
                            ORDER BY sale_id DESC LIMIT 1");
    $stmt2->execute([$status, $prodName, $block, $lot]);

    // 3. Fetch the relevant details (INCLUDING agent_id, um_id, broker_id)
    $stmtFetch = $pdo->prepare("SELECT sale_id, customer_id, installment_terms, installment_start_date, installment_monthly_payment, payment_method, agent_id, um_id, broker_id 
                                FROM sales 
                                WHERE product_name = ? AND block_number = ? AND lot_number = ? 
                                ORDER BY sale_id DESC LIMIT 1");
    $stmtFetch->execute([$prodName, $block, $lot]);
    $saleData = $stmtFetch->fetch(PDO::FETCH_ASSOC);

    if ($saleData) {
        $saleId         = $saleData['sale_id'];
        $customerId     = $saleData['customer_id'];
        $terms          = (int)$saleData['installment_terms'];
        $startDate      = $saleData['installment_start_date'];
        $monthlyPayment = $saleData['installment_monthly_payment'];
        $paymentMethod  = $saleData['payment_method'];
        
        // Grab the agent/broker info directly from the sales record
        $agentId        = $saleData['agent_id'] ?? null;
        $umId           = $saleData['um_id'] ?? null;
        $brokerId       = $saleData['broker_id'] ?? null;

        // --- START OF PAYMENTS LOGIC ---
        // Condition: If One-time, set payment status to 'fully paid'
        if ($paymentMethod === 'One-time' || $terms === 1) {
            $sqlPayment = "INSERT INTO payments (
                sale_id,
                agent_id,
                um_id,
                broker_id, 
                customer_id,
                block_number,
                lot_number, 
                amount_due, 
                amount_paid,
                due_date, 
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'fully paid')";
            
            $stmtPayment = $pdo->prepare($sqlPayment);
            $stmtPayment->execute([
                $saleId,
                $agentId,          // Now using fetched DB variable
                $umId,             // Now using fetched DB variable
                $brokerId,         // Now using fetched DB variable
                $customerId,
                $block,
                $lot,
                $monthlyPayment,
                $monthlyPayment,   // Fully paid
                $startDate
            ]);
        } 
        // Otherwise, generate standard pending terms if they exist
        elseif ($terms > 0 && !empty($startDate)) {
            $currentDueDate = new DateTime($startDate);
            
            $sqlPayment = "INSERT INTO payments (
                sale_id,
                agent_id,
                um_id,
                broker_id, 
                customer_id,
                block_number,
                lot_number, 
                amount_due, 
                amount_paid,
                due_date, 
                status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            
            $stmtPayment = $pdo->prepare($sqlPayment);

            for ($i = 1; $i <= $terms; $i++) {
                $stmtPayment->execute([
                    $saleId,
                    $agentId,          // Now using fetched DB variable
                    $umId,             // Now using fetched DB variable
                    $brokerId,         // Now using fetched DB variable
                    $customerId,
                    $block,
                    $lot,
                    $monthlyPayment,
                    0,                 // Pending, so paid is 0
                    $currentDueDate->format('Y-m-d')
                ]);

                // Increment date by 1 month
                $currentDueDate->modify('+1 month');
            }
        }
        // --- END OF PAYMENTS LOGIC ---
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>