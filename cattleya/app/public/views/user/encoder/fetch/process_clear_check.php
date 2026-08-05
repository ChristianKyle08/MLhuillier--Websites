<?php
// Include your database connection here
require_once __DIR__ . '/../../../../../config/database.php'; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'])) {
    
    $payment_id = intval($_POST['payment_id']);
    // Retrieve the penalty amount passed from JS, default to 0 if missing
    $penalty_amount = isset($_POST['penalty_amount']) ? floatval($_POST['penalty_amount']) : 0;
    
    // Retrieve the deposit slip number from the POST request
    $deposit_slip_number = isset($_POST['deposit_slip_number']) ? trim($_POST['deposit_slip_number']) : '';

    try {
        // We use a CASE statement to only update the penalty_status if there is a penalty.
        // It updates `penalty_paid` to the exact amount if greater than 0.
        $query = "
            UPDATE payments 
            SET 
                status = 'Paid',
                pdc_deposit_number = :deposit_slip_number,
                penalty_status = CASE WHEN :penalty_amount > 0 THEN 'Paid' ELSE penalty_status END,
                penalty_paid = CASE WHEN :penalty_amount > 0 THEN :penalty_amount ELSE penalty_paid END,
                payment_date = NOW()
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':id' => $payment_id,
            ':penalty_amount' => $penalty_amount,
            ':deposit_slip_number' => $deposit_slip_number
        ]);

        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'Check successfully cleared.'
            ]);
        } else {
            // --- NEW CONDITION ADDED HERE: Verify if the fields are already updated successfully ---
            $verifyQuery = "SELECT id FROM payments WHERE id = :id AND status = 'Paid' AND pdc_desposit_number = :verify_slip";
            $verifyStmt = $pdo->prepare($verifyQuery);
            $verifyStmt->execute([
                ':id' => $payment_id,
                ':verify_slip' => $deposit_slip_number
            ]);

            if ($verifyStmt->rowCount() > 0) {
                // Return success if the database already matches the desired output
                echo json_encode([
                    'success' => true, 
                    'message' => 'Check is successfully cleared and updated.'
                ]);
            } else {
                // Keep original fallback code intact
                echo json_encode([
                    'success' => false, 
                    'message' => 'No changes made. The payment may already be marked as Paid.'
                ]);
            }
            // --- END NEW CONDITION ---
        }

    } catch (PDOException $e) {
        error_log("Clear Check Error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'message' => 'A database error occurred while processing your request.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method or missing parameters.'
    ]);
}