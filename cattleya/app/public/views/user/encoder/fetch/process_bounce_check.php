<?php
// 1. Include your database connection here
require_once __DIR__ . '/../../../../../config/database.php'; 

header('Content-Type: application/json');

// 2. Verify it's a POST request and the payment_id is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'])) {
    
    $payment_id = intval($_POST['payment_id']);

    try {
        // Assuming $pdo is your database connection variable
        // 3. Prepare the UPDATE statement
        $query = "
            UPDATE payments 
            SET 
                status = 'Unpaid',
                pdc_check_number = NULL,
                pdc_check_date = NULL,
                pdc_bank_name = NULL,
                pdc_bank_number = NULL,
                penalty_status = CASE WHEN penalty_paid <> 0 THEN NULL ELSE penalty_status END,
                payment_method = NULL,
                ar_number = NULL,
                or_number = NULL
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($query);
        
        // 4. Execute the statement
        $stmt->execute([':id' => $payment_id]);

        // 5. Check if any row was actually updated
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true, 
                'message' => 'PDC details cleared and status set to Unpaid.'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'No changes made. The payment ID might not exist or is already Unpaid.'
            ]);
        }

    } catch (PDOException $e) {
        // Log the actual error internally (don't show raw DB errors to the user)
        error_log("Bounce Check Error: " . $e->getMessage());
        
        echo json_encode([
            'success' => false, 
            'message' => 'A database error occurred while processing your request.'
        ]);
    }
} else {
    // Reject invalid requests
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method or missing payment ID.'
    ]);
}