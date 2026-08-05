<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../../../config/database.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        $status = $_POST['status'] ?? 'sold'; // This is the user's selected status
        $productId = $_POST['product_id'] ?? null;
        $customIdString = $_POST['customer_id'] ?? null;
        $customerType = $_POST['customer_type'] ?? 'new'; 
        $paymentType = isset($_POST['payment_type']) ? trim($_POST['payment_type']) : '';
        
        $fullname = null;

        if ($status !== 'inactive') {
            // 1. Prepare Customer Data
            $fname = trim($_POST['fname'] ?? '');
            $mname = trim($_POST['mname'] ?? '');
            $lname = trim($_POST['lname'] ?? '');
            $fullname = trim("$lname, $fname $mname");

            // ADDED CONDITION: Only insert into customers if status is NOT available
            if ($status !== 'available') {
                if ($customerType === 'new') {
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE firstname = ? AND lastname = ?");
                    $checkStmt->execute([$fname, $lname]);
                    if ($checkStmt->fetchColumn() > 0) {
                        throw new Exception("A customer with the name $fname $lname already exists.");
                    }

                    $sqlCust = "INSERT INTO customers (customer_id, firstname, middlename, lastname, mobile_number, email_address, complete_address) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $pdo->prepare($sqlCust)->execute([
                        $customIdString, $fname, $mname, $lname, 
                        $_POST['mobile'] ?? '', $_POST['email'] ?? '', $_POST['address'] ?? ''
                    ]);
                }
            } // END OF customer insert condition

            // 2. Handle Optional Installment Fields
            $onetime_amount = $_POST['cash_price_value_onetime'] ?? $_POST['cash_price'] ?? 0;
            $postedTerm = $_POST['release_day'] ?? ''; 

            if ($paymentType === 'One-time') {
                $terms = 1;
                $start_date = date('Y-m-d'); 
                $end_date = date('Y-m-d'); // Added this line to prevent 'Undefined variable' error
                $monthly_payment = $onetime_amount;
            } else {
                $terms           = !empty($postedTerm) ? (int)$postedTerm : 0;
                $start_date      = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
                $end_date        = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
                $monthly_payment = !empty($_POST['monthly_payment']) ? $_POST['monthly_payment'] : null;
            }

            // ADDED CONDITION: Only insert into sales and payments if status is NOT available
            if ($status !== 'available') {
                // 3. Insert into Sales Table
                $sqlSales = "INSERT INTO sales (
                    product_name, block_number, lot_number, niche_type, tcp, cash_price,
                    customer_id, customer_fullname, mobile_number, 
                    agent_id, agent_fullname, 
                    um_id, um_fullname, 
                    broker_id, broker_fullname, payment_method,
                    lot_assume_type, installment_terms, installment_start_date, installment_end_date,
                    installment_monthly_payment, sales_status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

                $stmtSales = $pdo->prepare($sqlSales);
                $stmtSales->execute([
                    $_POST['product_name'] ?? null, 
                    $_POST['block_number'] ?? null, 
                    $_POST['lot_number'] ?? null, 
                    $_POST['niche_type'] ?? null,
                    $_POST['tcp_value'] ?? null,
                    $onetime_amount,
                    $customIdString, 
                    $fullname, 
                    $_POST['mobile'] ?? null,
                    $_POST['agent_id'] ?? null, 
                    $_POST['agent_fullname'] ?? null,
                    $_POST['um_id'] ?? null, 
                    $_POST['um_name'] ?? null, 
                    $_POST['broker_id'] ?? null, 
                    $_POST['broker_name'] ?? null, 
                    $paymentType,
                    $_POST['is_assumed'] ?? null,
                    $terms,           
                    $start_date, 
                    $end_date,      
                    $monthly_payment, 
                    $status // Applies exactly what the user selected (e.g., 'sold')
                ]);

                // --- REVISED PAYMENT INSERT LOGIC ---
                // Only proceed with inserting into payments table if status is 'sold'
                if ($status === 'sold') {
                    $saleId = $pdo->lastInsertId();

                    // Condition: If One-time, set payment status to 'fully paid' (applies ONLY to payments table)
                    if ($paymentType === 'One-time' || $terms === 1) {
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
                            $_POST['agent_id'] ?? null, 
                            $_POST['um_id'] ?? null,
                            $_POST['broker_id'] ?? null,
                            $customIdString,
                            $_POST['block_number'] ?? null, 
                            $_POST['lot_number'] ?? null, 
                            $onetime_amount,
                            $onetime_amount,
                            $start_date 
                        ]);
                    } 
                    elseif ($terms > 0 && !empty($start_date)) {
                        $currentDueDate = new DateTime($start_date);
                        $sqlPayment = "INSERT INTO payments (sale_id, agent_id, um_id, broker_id, customer_id, block_number, lot_number, amount_due, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Unpaid')";
                        $stmtPayment = $pdo->prepare($sqlPayment);

                        for ($i = 1; $i <= $terms; $i++) {
                            $stmtPayment->execute([
                                $saleId,
                                $_POST['agent_id'] ?? null, 
                                $_POST['um_id'] ?? null,
                                $_POST['broker_id'] ?? null,
                                $customIdString,
                                $_POST['block_number'] ?? null, 
                                $_POST['lot_number'] ?? null, 
                                $monthly_payment,
                                $currentDueDate->format('Y-m-d')
                            ]);
                            $currentDueDate->modify('+1 month');
                        }
                    }
                }
            } // END OF $status !== 'available' condition
        }

        // Finalize: Update the inventory status using the user's selected status
        $pdo->prepare("UPDATE product SET status = ? WHERE product_id = ?")->execute([$status, $productId]);
        
        $pdo->commit();
        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } 
}