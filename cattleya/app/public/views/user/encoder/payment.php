<?php
require_once __DIR__ . '/../../../../config/database.php'; 
require __DIR__ . '/../../includes/session_check.php';

try {
    // 1. Get unique customers who have at least one sold lot
    $customer_stmt = $pdo->query("SELECT DISTINCT customer_id, customer_fullname FROM sales WHERE sales_status = 'sold' ORDER BY customer_fullname ASC");
    $customer_list = $customer_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Query failed: " . $e->getMessage());
}

$selected_id = isset($_GET['customer_id']) ? $_GET['customer_id'] : null;
$selected_sale_id = isset($_GET['sale_id']) ? $_GET['sale_id'] : null;

$payments = [];
$sale_info = null;
$customer_lots = [];
$total_paid = $total_due = $remaining_balance = $progress_percent = 0;
$total_penalty = $total_overpayment = 0; 
$ending_receivable_amount = 0; // Added variable initialization
$is_account_cleared = false;
$payment_division = 1; // Default fallback
$commission_info = 'FULLCOMM';

// Initialize percentage variables to avoid undefined variable errors
$agent_pct = $broker_pct = $um_pct = 0;

if ($selected_id) {
    // 2. Fetch all lots owned by this specific customer
    $lots_stmt = $pdo->prepare("SELECT sale_id, product_name, block_number, lot_number FROM sales WHERE customer_id = ? AND sales_status = 'sold'");
    $lots_stmt->execute([$selected_id]);
    $customer_lots = $lots_stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($selected_sale_id) {
        $sale_stmt = $pdo->prepare("SELECT * FROM sales WHERE sale_id = ? AND customer_id = ?");
        $sale_stmt->execute([$selected_sale_id, $selected_id]);
        $sale_info = $sale_stmt->fetch(PDO::FETCH_ASSOC);

        if ($sale_info) {
            // 1. Get the total count of installments
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE sale_id = ?");
            $count_stmt->execute([$selected_sale_id]);
            $total_months = (int)$count_stmt->fetchColumn();

            // 2. FIXED: Improved Lookup Logic
            $raw_release_day = strtoupper($sale_info['release_day'] ?? ''); 

            if ($raw_release_day === 'OTS' || $raw_release_day === 'AT NEED BUYER') {
                $lookup_release_day = $raw_release_day; 
            } else {
                $years = $total_months / 12;
                $lookup_release_day = ($years <= 1) ? "1 year" : round($years) . " years";
            }

            // 3. Fetch from commission_profiles
            $comm_stmt = $pdo->prepare("
                SELECT payment_division, duration, agent_pct, broker_pct, um_pct 
                FROM commission_profiles 
                WHERE release_day = ? 
                AND is_active = 1 
                LIMIT 1
            ");
            $comm_stmt->execute([$lookup_release_day]);
            $comm_data = $comm_stmt->fetch(PDO::FETCH_ASSOC);

            if ($comm_data) {
                $payment_division = $comm_data['payment_division'];
                $commission_info = $comm_data['duration']; 
                $agent_pct = (float)$comm_data['agent_pct'];
                $broker_pct = (float)$comm_data['broker_pct'];
                $um_pct = (float)$comm_data['um_pct'];
            }

            $stmt = $pdo->prepare("SELECT * FROM payments WHERE sale_id = ? ORDER BY due_date ASC");
            $stmt->execute([$selected_sale_id]);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach($payments as $p) {
                if (strtolower($p['status']) === 'fully paid') {
                    $is_account_cleared = true;
                    break;
                }
            }
        
            $summary_stmt = $pdo->prepare("
                SELECT 
                    SUM(amount_due) as overall_total,
                    SUM(CASE WHEN LOWER(status) IN ('paid', 'fully paid') THEN amount_paid ELSE 0 END) as raw_total_paid,
                    COUNT(*) as total_months,
                    COUNT(CASE WHEN LOWER(status) IN ('paid', 'fully paid') THEN 1 END) as paid_months
                FROM payments WHERE sale_id = ?
            ");
            $summary_stmt->execute([$selected_sale_id]);
            $summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);

            // --- REVISED TOTAL OVERPAYMENT CALCULATION ---
            // Fetches the sum of the overpayment column based on specific account identifiers
            $ovp_stmt = $pdo->prepare("
                SELECT SUM(overpayment) 
                FROM payments 
                WHERE customer_id = ? 
                  AND block_number = ? 
                  AND lot_number = ?
            ");
            $ovp_stmt->execute([
                $selected_id, 
                $sale_info['block_number'], 
                $sale_info['lot_number']
            ]);
            $total_overpayment = (float)$ovp_stmt->fetchColumn() ?: 0;
            // ----------------------------------------------
        
            // --- ADDED: TOTAL PENALTY PAID CALCULATION ---
            $pen_paid_stmt = $pdo->prepare("
            SELECT SUM(penalty_paid) 
            FROM payments 
            WHERE sale_id = ? AND penalty_status = 'Paid'
            ");
            $pen_paid_stmt->execute([$selected_sale_id]);
            $total_penalty_paid = (float)$pen_paid_stmt->fetchColumn() ?: 0;
            // ----------------------------------------------

            $total_due = $sale_info['tcp'] ?? $sale_info['total_contract_price'] ?? ($summary['overall_total'] ?? 0);
            $total_months = $summary['total_months'] ?? 0;
            $paid_months = $summary['paid_months'] ?? 0;
            $total_paid = $summary['raw_total_paid'] ?? 0;
        
            // --- ADDED: Ending Receivable Calculation ---
            // Formula: tcp - total amount_paid
            $ending_receivable_amount = $total_due - $total_paid;
            // --------------------------------------------

            if (!$is_account_cleared) {
                foreach($payments as $p) {
                    // Updated condition: Only compute if due date is BEFORE today AND status is strictly 'unpaid'
                    if (!in_array(strtolower($p['status']), ['paid', 'fully paid']) && strtolower($p['status']) === 'unpaid' && strtotime($p['due_date']) < strtotime(date('Y-m-d'))) {
                        $total_penalty += ($p['amount_due'] * 0.02);
                    }
                }
            }
        
            if ($is_account_cleared) {
                $remaining_balance = 0;
                $progress_percent = 100;
                $total_penalty = 0;
            } else {
                $remaining_balance = ($total_due + $total_penalty) - $total_paid;
                $remaining_balance = max(0, $remaining_balance);
                $progress_percent = ($total_months > 0) ? ($paid_months / $total_months) * 100 : 0;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cattleya | Payments & Ledger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    
    <style>
            :root { 
                --c-teal: #1c5f66; 
                --c-teal-dark: #114146;
                --c-lime: #a6ce39; 
                --bg-main: #f8fafc; 
            }
            
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: var(--bg-main); 
            color: #0f172a; 
            font-size: 0.8rem; 
            overflow-x: hidden; 
        }

        .content-wrapper { 
            margin-left: 15px; 
            width: calc(100% - 240px);
            padding: 1.5rem; 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
            min-height: 100vh;
        }

        .glass-card { 
            background: #ffffff; 
            border: 1px solid #f1f5f9; 
            border-radius: 16px; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
        }

        @keyframes growWidth { from { width: 0; } to { width: var(--p-width); } }
        .animate-grow { animation: growWidth 1.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }

        .custom-select { 
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3Cpath%3E%3C/svg%3E"); 
            background-position: right 0.75rem center; 
            background-repeat: no-repeat; 
            background-size: 1em; 
        }

        /* Custom Modern Styles */
    .premium-gradient {
        background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);
    }
    
    .payment-checkbox:checked + .payment-label {
        border-color: #14b8a6;
        background-color: #f0fdfa;
        box-shadow: 0 10px 30px -10px rgba(20, 184, 166, 0.15);
    }

    .payment-checkbox:checked + .payment-label .icon-box {
        background-color: #14b8a6;
        color: white;
        transform: scale(1.1);
    }

    @keyframes grow {
        from { width: 0; }
        to { width: var(--p-width); }
    }
    .animate-grow {
        animation: grow 1.5s ease-out forwards;
    }

    /* Hide arrow in number input */
    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

        /* PRINT STYLES - FORMAL BLACK AND WHITE LEDGER */
        .only-print { display: none; }
        @media print {
            nav, .no-print, .sticky, .content-wrapper, button, form { display: none !important; }
            body { background: white !important; color: black !important; margin: 0; padding: 0; }
            .only-print { display: block !important; width: 100%; padding: 20px; }
            
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th { background-color: #000 !important; color: #fff !important; text-transform: uppercase; font-size: 10px; padding: 8px; border: 1px solid #000; }
            td { border: 1px solid #000; padding: 8px; font-size: 10px; }
            .text-right { text-align: right; }
            .font-bold { font-weight: bold; }
            .border-double { border-top: 3px double #000; }
        }

        /* Styling for the Screen View (The "Face" of the ledger) */
    .ledger-container {
        background-color: #fff;
        width: 210mm; /* A4 Width */
        min-height: 297mm;
        padding: 20mm;
        margin: 20px auto;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
        color: #000;
    }

    /* Table styling for both view and print */
    .ledger-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .ledger-table th {
        text-align: left;
        font-size: 11px;
        padding: 10px 5px;
        border-bottom: 2px solid #000;
        text-transform: uppercase;
    }
    .ledger-table td {
        padding: 8px 5px;
        font-size: 11px;
        border-bottom: 1px solid #eee;
    }

    /* Print Logic */
    @media print {
        body * {
            visibility: hidden; /* Hide everything else */
        }
        .only-print, .only-print * {
            visibility: visible; /* Show only the ledger */
        }
        .only-print {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            box-shadow: none;
        }
        tfoot { display: table-row-group; } /* Footer on last page only */
        tr { break-inside: avoid; }
    }
    
    /* Hide the ledger on the web page until a customer is selected */
    <?php if (!$selected_sale_id): ?>
    .ledger-container { display: none; }
    <?php endif; ?>

    /* Custom SweetAlert Styling to match your theme */
        .swal2-popup {
            border-radius: 20px !important;
            padding: 2rem !important;
            font-family: 'Inter', sans-serif !important;
        }

        .swal2-title {
            color: #1c5f66 !important; /* Teal */
            font-weight: 700 !important;
        }

        .swal2-confirm {
            padding: 0.8rem 2rem !important;
            font-weight: 600 !important;
            border-radius: 12px !important;
        }

        .swal2-icon.swal2-success {
            border-color: #a6ce39 !important; /* Lime */
        }

        .swal2-icon.swal2-success [class^='swal2-success-line'] {
            background-color: #a6ce39 !important;
        }

        .swal2-icon.swal2-success .swal2-success-ring {
            border: .25em solid rgba(166, 206, 57, .3) !important;
        }
    </style>
</head>
<body class="antialiased">
    <div class="ledger-container only-print">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #000; padding-bottom: 10px;">
            <div>
                <h1 style="font-size: 26px; font-weight: 900; margin: 0; letter-spacing: -1px;">CATTLEYA FINANCE</h1>
                <p style="font-size: 11px; margin: 2px 0; color: #333;">Official Payment Ledger Statement</p>
            </div>
            <div style="text-align: right;">
                <p style="font-size: 10px; margin: 0;">Date Generated: <strong><?= date('F d, Y h:i A') ?></strong></p>
                <p style="font-size: 10px; margin: 0;">Customer ID: <strong> <?= $selected_id ?></strong></p>
                <button onclick="window.print()" class="btn-print-hide" style="margin-top: 10px; cursor: pointer; padding: 5px 15px; background: #000; color: #fff; border: none; border-radius: 4px; font-size: 10px;">🖨️ PRINT LEDGER</button>
            </div>
        </div>
        <div style="margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            <div>
                <p style="font-size: 10px; font-weight: bold; color: #666; margin-bottom: 2px;">CUSTOMER NAME</p>
                <p style="font-size: 16px; font-weight: 800;"><?= htmlspecialchars($sale_info['customer_fullname'] ?? 'N/A') ?></p>
                <p style="font-size: 11px; margin-top: 5px;">Property: <strong><?= $sale_info['product_name'] ?> - Blk <?= $sale_info['block_number'] ?> Lot <?= $sale_info['lot_number'] ?></strong></p>
            </div>
            <div style="text-align: right;">
                <p style="font-size: 10px; font-weight: bold; color: #666; margin-bottom: 2px;">CONTRACT STATUS</p>
                <p style="font-size: 18px; font-weight: 800; color: <?= $is_account_cleared ? '#28a745' : '#000' ?>;">
                    <?= $is_account_cleared ? 'FULLY PAID' : round($progress_percent) . '% AMORTIZED' ?>
                </p>
            </div>
        </div>
        <table class="ledger-table">
            <thead>
                <tr>
                    <th>Due Date</th>
                    <th>
                        <?php
                        $paymentMethod = $row['payment_method'] ?? '';

                        if ($paymentMethod === 'PDC') {
                            echo 'Check #';
                        } elseif ($paymentMethod === 'billspayment') {
                            echo 'Ref #';
                        } else {
                            echo 'Ref / Check #'; // Fallback just in case it's empty
                        }
                        ?>
                    </th>
                    <th style="text-align: center;">Payment Method</th>
                    <th style="text-align: right;">Amount Due</th>
                    <th style="text-align: right;">Penalty (2%)</th>
                    <th style="text-align: right;">Amount Paid</th>
                    <th style="text-align: right;">Overpayment</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($payments as $row): 
                    $status_clean = strtolower($row['status']);
                    $is_row_paid = in_array($status_clean, ['paid', 'fully paid']);
                    
                    // Calculate penalty for unpaid rows
                    $p_amt = (!$is_account_cleared && !$is_row_paid && strtotime($row['due_date']) < time()) 
                            ? ($row['amount_due'] * 0.02) 
                            : 0;
                            
                    // Logic: amount_paid should at least show the amount_due if paid, 
                    // but we use the actual database value to calculate the overpayment.
                    $actual_paid = $row['amount_paid'] ?? 0;
                    
                    // Check if penalty was waived
                    $p_status = strtolower($row['penalty_status'] ?? '');
                    $is_waived = ($p_status === 'waived');
                    
                    // Fetch the recorded penalty paid from the database
                    $actual_penalty_paid = $row['penalty_paid'] ?? 0;
                    
                    // If waived, the required amount is just the amount_due. 
                    // If not waived, it's amount_due + penalty_paid.
                    $required_amount = $row['amount_due'] + ($is_waived ? 0 : $actual_penalty_paid);
                    
                    // Overpayment calculation: Only applies if the row is paid and actual paid > required amount
                    $row_overpayment = ($is_row_paid && $actual_paid > $required_amount) 
                                    ? ($actual_paid - $required_amount) 
                                    : 0;
                ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($row['due_date'])) ?></td>
                    <td style="color: #666;">
                        <?php
                        $paymentMethod = $row['payment_method'] ?? '';

                        if ($paymentMethod === 'PDC') {
                            echo $row['pdc_check_number'] ?? '';
                        } elseif ($paymentMethod === 'billspayment') {
                            echo $row['reference_no'] ?? '';
                        }
                        ?>
                    </td>
                    <td style="text-align: center;"><?= strtoupper($row['payment_method'] ?? '') ?></td>
                    <td style="text-align: right;">₱<?= number_format($row['amount_due'], 2) ?></td>
                    <td style="text-align: right; color: <?= $p_amt > 0 ? '#dc3545' : '#000' ?>;">
                        <?= $p_amt > 0 ? '₱' . number_format($p_amt, 2) : '—' ?>
                    </td>
                    <td style="text-align: right; font-weight: bold;">₱<?= number_format($actual_paid, 2) ?></td>
                    <td style="text-align: right; color: #28a745;">
                        <?= $row_overpayment > 0 ? '₱' . number_format($row_overpayment, 2) : '—' ?>
                    </td>
                    <td>
                        <span style="font-weight: bold; color: <?= $is_row_paid ? '#28a745' : ($p_amt > 0 ? '#dc3545' : '#666') ?>;">
                            <?= strtoupper($row['status']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" rowspan="5" style="border: none; vertical-align: top; padding-top: 30px;">
                        <div style="border-left: 3px solid #eee; padding-left: 15px;">
                            <p style="font-size: 10px; font-weight: bold; margin: 0;">OFFICIAL NOTES:</p>
                            <p style="font-size: 10px; font-style: italic; color: #666; margin: 5px 0;">
                                This document is a system-generated record of all payments and penalties. 
                                Overpayments are credited toward the principal balance.
                            </p>
                        </div>
                    </td>
                    <td colspan="2" style="font-size: 11px; font-weight: bold; padding-top: 20px;">Total Contract Price:</td>
                    <td style="text-align: right; font-size: 11px; font-weight: bold; padding-top: 20px;">₱<?= number_format($total_due, 2) ?></td>
                </tr>
                <tr>
                    <td colspan="2" style="font-size: 11px; font-weight: bold;">Total Paid to Date:</td>
                    <td style="text-align: right; font-size: 11px; font-weight: bold;">₱<?= number_format($total_paid, 2) ?></td>
                </tr>
                <tr>
                    <td colspan="2" style="font-size: 11px; font-weight: bold; color: #28a745;">Total Overpayments:</td>
                    <td style="text-align: right; font-size: 11px; font-weight: bold; color: #28a745;">₱<?= number_format($total_overpayment, 2) ?></td>
                </tr>
                <tr>
                    <td colspan="2" style="font-size: 11px; font-weight: bold;">Total Penalties:</td>
                    <td style="text-align: right; font-size: 11px; font-weight: bold;">₱<?= number_format($total_penalty, 2) ?></td>
                </tr>
                <tr>
                    <td colspan="2" style="font-size: 13px; font-weight: 900; background: #f8f9fa; padding: 10px;">OUTSTANDING:</td>
                    <td style="text-align: right; font-size: 13px; font-weight: 900; background: #f8f9fa; padding: 10px;">₱<?= number_format($remaining_balance, 2) ?></td>
                </tr>
            </tfoot>
        </table>
        <div style="margin-top: 60px; display: flex; justify-content: space-between; break-inside: avoid;">
            <div style="width: 220px; border-top: 1.5px solid #000; text-align: center; padding-top: 8px;">
                <p style="font-size: 10px; font-weight: 900; margin: 0;">ADMINISTRATOR</p>
                <p style="font-size: 9px; color: #666;">Cattleya Finance Department</p>
            </div>
            <div style="width: 220px; border-top: 1.5px solid #000; text-align: center; padding-top: 8px;">
                <p style="font-size: 10px; font-weight: 900; margin: 0;"><?= strtoupper($sale_info['customer_fullname'] ?? 'CUSTOMER') ?></p>
                <p style="font-size: 9px; color: #666;">Customer Signature over Printed Name</p>
            </div>
        </div>
    </div>

    <?php if(!$selected_id || !isset($_GET['print'])): ?>
        <?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>
    <?php endif; ?>

    <div class="content-wrapper">
        <main class="max-w-6xl mx-auto">
        <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-6 mb-8 animate__animated animate__fadeIn">
                <div class="space-y-3">
                    <div class="space-y-1">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-teal-50 text-teal-700 text-[10px] font-bold uppercase tracking-widest rounded-lg border border-teal-100 no-print">
                            <span class="w-1.5 h-1.5 bg-teal-500 rounded-full animate-pulse"></span> Finance Ledger
                        </span>
                        <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight leading-none">
                            <?= $sale_info ? htmlspecialchars($sale_info['customer_fullname']) : 'Ledger Overview' ?>
                        </h1>
                    </div>

                    <?php if($selected_id): ?>
                        <div class="flex flex-wrap items-center gap-3 no-print">
                            <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-100 text-slate-600 rounded-lg text-[11px] font-bold border border-slate-200">
                                <i class="fa-solid fa-user-tag text-teal-600"></i>
                                ID: <?= htmlspecialchars($selected_id) ?>
                            </div>
                            <?php if($sale_info): ?>
                                <div class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-[11px] font-bold border border-blue-100">
                                    <i class="fa-solid fa-location-dot"></i>
                                    Blk <?= htmlspecialchars($sale_info['block_number']) ?> Lot <?= htmlspecialchars($sale_info['lot_number']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-slate-400 text-xs font-medium italic">Please select a customer to view transaction history.</p>
                    <?php endif; ?>
                </div>

                <div class="flex flex-col md:flex-row items-stretch md:items-center gap-3 no-print">
                    <form action="" method="GET" id="ledgerForm" class="flex flex-col md:flex-row items-center gap-3">
                        
                        <div class="relative group w-full md:w-64">
                            <label class="absolute -top-2 left-3 px-1 bg-white text-[9px] font-black uppercase tracking-tighter text-slate-400 z-10 group-focus-within:text-blue-500 transition-colors">Step 1: Customer</label>
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i class="fa-solid fa-magnifying-glass text-slate-400 text-[10px]"></i>
                            </div>
                            <input list="customer_datalist" name="customer_id" id="customer_input"
                                value="<?= htmlspecialchars($_GET['customer_id'] ?? '') ?>"
                                autocomplete="off"
                                placeholder="Search name..."
                                class="w-full bg-white border-2 border-slate-200 rounded-xl pl-10 pr-4 py-3 font-bold text-slate-700 text-[12px] shadow-sm outline-none focus:border-blue-500 transition-all placeholder:text-slate-300 placeholder:font-normal"
                                onchange="this.form.submit()">
                            
                            <datalist id="customer_datalist">
                                <?php foreach($customer_list as $c): ?>
                                    <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['customer_fullname']) ?></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <?php if ($selected_id): ?>
                            <div class="text-slate-300 hidden md:block">
                                <i class="fa-solid fa-arrow-right-long"></i>
                            </div>

                            <div class="relative group w-full md:w-72">
                                <label class="absolute -top-2 left-3 px-1 bg-white text-[9px] font-black uppercase tracking-tighter text-slate-400 z-10 group-focus-within:text-blue-500 transition-colors">Step 2: Property</label>
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <i class="fa-solid fa-house-chimney text-slate-400 text-[10px]"></i>
                                </div>
                                <select name="sale_id" onchange="this.form.submit()"
                                    class="w-full bg-white border-2 border-slate-200 rounded-xl pl-10 pr-10 py-3 font-bold text-slate-700 text-[12px] shadow-sm outline-none focus:border-blue-500 appearance-none transition-all cursor-pointer">
                                    <option value="">Select Lot/Account...</option>
                                    
                                    <?php 
                                    // Dynamically group lots by product name to keep all properties intact
                                    $grouped_lots = [];
                                    foreach ($customer_lots as $lot) {
                                        $grouped_lots[$lot['product_name']][] = $lot;
                                    }
                                    ?>

                                    <?php foreach ($grouped_lots as $product_name => $lots): ?>
                                        <optgroup label="<?= htmlspecialchars($product_name) ?>">
                                            <?php foreach ($lots as $lot): ?>
                                                <option value="<?= $lot['sale_id'] ?>" <?= ($selected_sale_id == $lot['sale_id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($lot['product_name']) ?> [B<?= $lot['block_number'] ?> L<?= $lot['lot_number'] ?>]
                                                </option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>

                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-slate-400 text-[10px]">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                            </div>
                        <?php endif; ?>
                    </form>

                    <div class="flex items-center gap-2 h-full">
                        <?php if($selected_sale_id): ?>
                            <div class="w-px h-8 bg-slate-200 mx-1 hidden md:block"></div>
                            
                            <button onclick="openLedgerModal()" 
                                class="flex-1 md:flex-none flex items-center justify-center gap-2.5 px-6 py-3.5 bg-blue-600 text-white rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-blue-700 transition-all shadow-xl shadow-blue-200 active:scale-95">
                                <i class="fa-solid fa-eye"></i> 
                                View
                            </button>

                            <button onclick="window.print()" 
                                class="flex-1 md:flex-none flex items-center justify-center gap-2.5 px-6 py-3.5 bg-slate-900 text-white rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-slate-800 transition-all shadow-xl shadow-slate-200 active:scale-95">
                                <i class="fa-solid fa-print"></i> 
                                Print
                            </button>
                            
                            <a href="../encoder/payment" 
                                class="flex items-center justify-center px-4 py-3.5 bg-white border-2 border-slate-200 text-slate-400 rounded-xl hover:text-red-500 hover:border-red-100 hover:bg-red-50 transition-all shadow-sm"
                                title="Reset View">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div id="ledgerModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm overflow-y-auto w-full h-full p-4 md:p-8 no-print">
    
                <div class="absolute inset-0" onclick="closeLedgerModal()"></div>

                <div class="relative bg-white mx-auto shadow-2xl rounded-sm max-w-[800px] w-full min-h-screen my-auto">

                    <div id="modalLedgerContent">
                        </div>
                </div>
            </div>
            <?php if ($selected_id && $sale_info): ?>
                <form id="bulkPaymentForm" method="POST">
                    <input type="hidden" name="customer_id" value="<?= $selected_id ?>">

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        
                        <div class="lg:col-span-8 space-y-6">
                            
                            <div class="premium-gradient p-8 text-white shadow-2xl rounded-3xl relative overflow-hidden group">
                                <div class="absolute -top-24 -right-24 w-64 h-64 bg-white/10 rounded-full blur-3xl group-hover:bg-white/20 transition-all duration-700"></div>
                                
                                <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                                    <div>
                                        <div class="flex items-center gap-3 mb-3">
                                            <div class="p-1.5 bg-white/20 backdrop-blur-md rounded-lg">
                                                <i class="fa-solid fa-wallet text-[10px]"></i>
                                            </div>
                                            <p class="text-white/70 text-[10px] font-black uppercase tracking-[0.2em]">Current Remaining Balance</p>
                                            <?php if ($is_account_cleared): ?>
                                                <span class="px-3 py-1 bg-blue-500/20 backdrop-blur-md text-white border border-white/20 rounded-full text-[9px] font-bold uppercase tracking-wider">Fully Paid</span>
                                            <?php endif; ?>
                                        </div>
                                        <h5 class="text-5xl font-black tracking-tighter text-white drop-shadow-sm">
                                            ₱<?= number_format(max(0, $remaining_balance), 2) ?>
                                        </h5>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4 w-full md:w-auto">
                                        <div class="bg-white/10 backdrop-blur-xl p-4 rounded-2xl border border-white/20 shadow-inner">
                                            <p class="text-[9px] font-bold text-white/50 uppercase mb-1 tracking-widest">Total Paid</p>
                                            <p class="text-lg font-black <?= $is_account_cleared ? 'text-blue-300' : 'text-lime-400' ?>">
                                                ₱<?= number_format($total_paid, 2) ?>
                                            </p>

                                            <div class="mt-2 pt-2 border-t border-white/10">
                                                <p class="text-[9px] font-bold text-white/40 uppercase mb-0.5 tracking-widest">Total Penalty Paid</p>
                                                <p class="text-sm font-bold text-emerald-400">
                                                    ₱<?= number_format($total_penalty_paid, 2) ?>
                                                </p>
                                            </div>
                                        </div>

                                        <div class="bg-white/10 backdrop-blur-xl p-4 rounded-2xl border border-white/20 shadow-inner">
                                            <p class="text-[9px] font-bold text-white/50 uppercase mb-1 tracking-widest">Total Contract</p>
                                            <p class="text-lg font-black text-white">
                                                ₱<?= number_format($sale_info['tcp'] ?? $total_due, 2) ?>
                                            </p>

                                            <?php if (!$is_account_cleared): ?>
                                            <hr class="border-white/10 my-2">
                                            <p class="text-[9px] font-bold text-white/50 uppercase mb-1 mt-2 tracking-widest">Ending Receivable</p>
                                            <p class="text-base font-bold text-emerald-300">
                                                ₱<?= number_format($ending_receivable_amount, 2) ?>
                                            </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 space-y-3">
                                    <div class="flex justify-between items-end">
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] uppercase tracking-[0.15em] font-black text-white/60">Payment Journey</span>
                                            <span class="text-[10px] text-lime-400 font-bold bg-lime-400/10 px-2 py-0.5 rounded-full"><?= round($progress_percent) ?>% Complete</span>
                                        </div>
                                    </div>
                                    <div class="h-4 bg-black/20 rounded-full p-1 border border-white/10 shadow-inner">
                                        <div class="h-full bg-gradient-to-r from-lime-400 via-emerald-400 to-teal-400 rounded-full shadow-[0_0_15px_rgba(163,230,53,0.5)] transition-all duration-1000 relative overflow-hidden" 
                                            style="width: <?= $progress_percent ?>%">
                                            <div class="absolute inset-0 bg-white/20 animate-pulse"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white/95 backdrop-blur-md rounded-[2rem] p-6 md:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-300 border border-slate-100">
                                
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-5 mb-8">
                                    <div>
                                        <h3 class="text-2xl font-black text-slate-900 tracking-tight mb-3">Payment Schedule</h3>
                                        <div class="flex flex-wrap gap-2.5">
                                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-indigo-50/80 text-indigo-700 text-[10px] font-black uppercase tracking-wider border border-indigo-200/60 shadow-sm backdrop-blur-sm transition-colors hover:bg-indigo-100/80">
                                                <i class="fa-solid fa-circle-info"></i>
                                                Commission Terms: 
                                                <?php 
                                                    if ($is_account_cleared || $commission_info === 'FULLCOMM') {
                                                        echo "FULLCOMM";
                                                    } else {
                                                        echo htmlspecialchars($commission_info);
                                                    }
                                                ?>
                                            </span>
                                            
                                            <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-teal-50/80 text-teal-700 text-[10px] font-black uppercase tracking-wider border border-teal-200/60 shadow-sm backdrop-blur-sm transition-colors hover:bg-teal-100/80">
                                                <i class="fa-solid fa-split"></i>
                                                Comm. Starts at Payment #<?php 
                                                    if ($is_account_cleared || $commission_info === 'FULLCOMM') {
                                                        echo "1";
                                                    } else {
                                                        echo htmlspecialchars($payment_division);
                                                    }
                                                ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="w-full grid grid-cols-2 sm:grid-cols-4 gap-2 p-1.5 bg-slate-50/60 rounded-2xl border border-slate-200/60 shadow-[inset_0_2px_4px_rgba(0,0,0,0.02)] backdrop-blur-sm">
                                        <span class="text-center px-3 py-1.5 bg-blue-100/70 text-blue-800 rounded-xl text-[9px] font-black uppercase tracking-wider transition-colors hover:bg-blue-200/70">Fully Paid</span>
                                        <span class="text-center px-3 py-1.5 bg-emerald-100/70 text-emerald-800 rounded-xl text-[9px] font-black uppercase tracking-wider transition-colors hover:bg-emerald-200/70">Paid</span>
                                        <span class="text-center px-3 py-1.5 bg-purple-100/70 text-purple-800 rounded-xl text-[9px] font-black uppercase tracking-wider transition-colors hover:bg-purple-200/70">Pending</span>
                                        <span class="text-center px-3 py-1.5 bg-orange-100/70 text-orange-800 rounded-xl text-[9px] font-black uppercase tracking-wider transition-colors hover:bg-orange-200/70">Unpaid</span>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <?php 
                                        $payment_counter = 1; 
                                        $start_at        = (int)$payment_division; 
                                        $actual_duration = ($commission_info === 'FULLCOMM') ? 1 : (int)$commission_info;
                                        $end_at          = $start_at + $actual_duration - 1;

                                        foreach($payments as $row): 
                                            // Status checks
                                            $status_lower      = strtolower($row['status'] ?? '');
                                            $is_fully_paid_row = ($status_lower === 'fully paid');
                                            $is_paid           = ($status_lower === 'paid' || $is_fully_paid_row);
                                            $is_processing     = ($status_lower === 'pending'); // Track if row is currently a processing PDC
                                            
                                            // Calculations
                                            $row_overpayment = 0;
                                            if ($is_paid && (float)$row['amount_paid'] > (float)$row['amount_due']) {
                                                $row_overpayment = (float)$row['amount_paid'] - (float)$row['amount_due'];
                                            }

                                            // Compare against today's date only, ignoring current time. Added strictly 'unpaid' check
                                            $is_late     = (!$is_account_cleared && !$is_paid && strtolower($row['status'] ?? '') === 'unpaid' && strtotime($row['due_date']) < strtotime(date('Y-m-d')));
                                            $penalty_amt = $is_late ? ($row['amount_due'] * 0.02) : 0;

                                            // Commission checks
                                            $show_commission = ($payment_counter >= $start_at && $payment_counter <= $end_at) || $is_fully_paid_row;

                                            $b_pct = (float)($broker_pct ?? 0);
                                            $u_pct = (float)($um_pct ?? 0);
                                            $a_pct = (float)($agent_pct ?? 0);

                                            $base_amount = (float)$sale_info['tcp'];
                                            
                                            // Condition added to check if the status is fully paid
                                            if ($is_fully_paid_row) {
                                                $base_amount = (float)($sale_info['cash_price'] ?? 0);
                                            }
                                            
                                            $comm_broker = $show_commission ? ($base_amount * ($b_pct / 100)) : 0; 
                                            $comm_um     = $show_commission ? ($base_amount * ($u_pct / 100)) : 0; 
                                            $comm_agent  = $show_commission ? ($base_amount * ($a_pct / 100)) : 0;
                                    ?>
                                    <div class="group">
                                        <?php if (!$is_paid && !$is_processing): ?>
                                            <input type="checkbox" 
                                                name="payment_ids[]" 
                                                value="<?= $row['id'] ?>" 
                                                id="pay_<?= $row['id'] ?>"
                                                class="payment-checkbox hidden no-print peer"
                                                onchange="updateAmountToPay(); checkWaiverStatus(this);" 
                                                data-customer-id="<?= $row['customer_id'] ?>"
                                                data-block="<?= $row['block_number'] ?>"
                                                data-lot="<?= $row['lot_number'] ?>"
                                                data-status="<?= $row['status'] ?>" 
                                                data-due-raw="<?= $row['due_date'] ?>" 
                                                data-request-waive="<?= htmlspecialchars($row['request_waive'] ?? '') ?>"
                                                data-due-date="<?= date('F d, Y', strtotime($row['due_date'])) ?>"
                                                data-amount="<?= $row['amount_due'] ?>"
                                                data-overpayment="<?= $row_overpayment ?>"
                                                data-penalty="<?= $penalty_amt ?>"
                                                data-comm-broker="<?= $comm_broker ?>"
                                                data-comm-um="<?= $comm_um ?>"
                                                data-comm-agent="<?= $comm_agent ?>">
                                        <?php endif; ?>
                                        
                                        <label for="pay_<?= $row['id'] ?>" 
                                            class="payment-label relative flex items-center justify-between p-4 md:p-5 rounded-[1.25rem] border-2 transition-all duration-300 ease-out
                                                    <?= $is_paid ? 'bg-slate-50/50 border-transparent shadow-[0_2px_10px_rgb(0,0,0,0.02)]' : 
                                                    ($is_processing ? 'bg-purple-50/30 border-purple-100 shadow-[0_2px_10px_rgb(0,0,0,0.02)]' : 
                                                    'bg-white border-transparent shadow-[0_4px_20px_rgb(0,0,0,0.04)] cursor-pointer hover:-translate-y-0.5 hover:shadow-[0_8px_24px_rgb(0,0,0,0.08)] hover:border-teal-200 peer-checked:bg-teal-50/40 peer-checked:border-teal-400 peer-checked:shadow-[0_8px_24px_rgba(20,184,166,0.15)] peer-checked:[&_.due-date-text]:text-teal-800') ?>">
                                            
                                            <div class="flex items-center gap-4 md:gap-5">
                                                
                                                <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 shadow-[inset_0_2px_4px_rgba(255,255,255,0.4),0_4px_10px_rgba(0,0,0,0.05)] transition-all duration-300
                                                            <?= $is_fully_paid_row ? 'bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-blue-200/50' : 
                                                            ($is_paid ? 'bg-gradient-to-br from-emerald-400 to-emerald-500 text-white shadow-emerald-200/50' : 
                                                            ($is_processing ? 'bg-gradient-to-br from-purple-400 to-purple-500 text-white shadow-purple-200/50' : 
                                                            'bg-gradient-to-br from-slate-100 to-slate-200 text-slate-500 border border-white group-hover:from-teal-100 group-hover:to-teal-200 group-hover:text-teal-600 peer-checked:from-teal-400 peer-checked:to-teal-500 peer-checked:text-white peer-checked:shadow-teal-200/50')) ?>">
                                                    <div class="text-center">
                                                        <span class="text-[10px] font-black block leading-tight tracking-tight"><?= $payment_counter ?></span>
                                                        <i class="fa-solid <?= $is_paid ? 'fa-check-double' : ($is_processing ? 'fa-spinner fa-spin' : 'fa-calendar-check') ?> text-[8px] mt-0.5 opacity-90"></i>
                                                    </div>
                                                </div>
                                                
                                                <div class="flex flex-col justify-center">
                                                    <div class="flex items-center gap-3 mb-0.5">
                                                        <p class="due-date-text text-[10px] md:text-[12px] font-black text-slate-900 transition-colors"><?= date('F d, Y', strtotime($row['due_date'])) ?></p>
                                                        <span class="inline-flex items-center text-[7px] font-black px-2.5 py-0.5 rounded-full border tracking-wide shadow-sm backdrop-blur-sm
                                                                    <?= $is_fully_paid_row ? 'bg-blue-50/80 text-blue-700 border-blue-200/60' : 
                                                                    ($is_paid ? 'bg-emerald-50/80 text-emerald-700 border-emerald-200/60' : 
                                                                    ($is_processing ? 'bg-purple-50/80 text-purple-700 border-purple-200/60' : 
                                                                    'bg-orange-50/80 text-orange-700 border-orange-200/60')) ?>">
                                                            <?= strtoupper($row['status']) ?>
                                                        </span>
                                                    </div>
                                                    <span class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">Due Date</span>
                                                    
                                                    <?php 
                                                    $current_method = strtolower(trim($row['payment_method'] ?? ''));
                                                    if (!empty($current_method)): ?>
                                                        <div class="mt-2.5">
                                                            <?php if (in_array($current_method, ['cash', 'billspayment'])): ?>
                                                                <details class="group/details relative">
                                                                    <summary onclick="event.preventDefault(); event.stopPropagation(); this.parentNode.open = !this.parentNode.open;" 
                                                                        class="text-[8px] font-bold text-slate-500 cursor-pointer list-none [&::-webkit-details-marker]:hidden flex items-center gap-2 select-none w-fit hover:text-slate-800 transition-colors <?= $current_method === 'cash' ? 'pointer-events-none' : '' ?>">
                                                                        
                                                                        <?php if ($current_method === 'cash'): ?>
                                                                            <span class="uppercase">PAYMENT METHOD: <span class="text-slate-900"><?= strtoupper(htmlspecialchars($row['payment_method'])) ?></span></span>
                                                                        <?php else: ?>
                                                                            <span>PAYMENT METHOD: <span class="text-slate-900"><?= strtoupper(htmlspecialchars($row['payment_method'])) ?></span></span>
                                                                        <?php endif; ?>
                                                                        
                                                                        <?php if ($current_method !== 'cash'): ?>
                                                                            <span class="text-indigo-600 hover:text-indigo-800 hover:bg-indigo-100 text-[7px] bg-indigo-50/80 px-2 py-0.5 rounded-md border border-indigo-200/60 transition-all duration-200 backdrop-blur-sm shadow-sm">See details</span>
                                                                        <?php endif; ?>
                                                                    </summary>
                                                                    <div class="mt-2.5 flex flex-col gap-1 pl-3 border-l-2 border-indigo-200/60 cursor-default animate-fade-in-down" onclick="event.stopPropagation();">
                                                                        <span class="text-[7px] font-black text-slate-400 uppercase tracking-widest"><?= strtoupper(htmlspecialchars($row['payment_method'])) ?> Details</span>
                                                                       <span class="text-[8px] text-slate-700 bg-white/60 backdrop-blur-sm px-3 py-1.5 rounded-lg border border-slate-200/60 inline-block w-fit font-medium shadow-[0_2px_8px_rgb(0,0,0,0.04)]">
                                                                            Ref No: <span class="font-black text-slate-900"><?= htmlspecialchars($row['reference_no'] ?? 'N/A') ?></span>
                                                                            0.5 rounded-md border border-purple-200/60 transition-all duration-200 backdrop-blur-sm shadow-sm">See details</span>
                                                                        </span>
                                                                    </div>
                                                                </details>

                                                            <?php elseif ($current_method === 'pdc'): ?>
                                                                <details class="group/details relative">
                                                                    <summary onclick="event.preventDefault(); event.stopPropagation(); this.parentNode.open = !this.parentNode.open;" 
                                                                        class="text-[8px] font-bold text-slate-500 cursor-pointer list-none [&::-webkit-details-marker]:hidden flex items-center gap-2 select-none w-fit hover:text-slate-800 transition-colors">
                                                                        <span>PAYMENT METHOD: <span class="text-slate-900"><?= strtoupper(htmlspecialchars($row['payment_method'])) ?></span></span>
                                                                        <span class="text-purple-600 hover:text-purple-800 hover:bg-purple-100 text-[7px] bg-purple-50/80 px-2 py-0.5 rounded-md border border-purple-200/60 transition-all duration-200 backdrop-blur-sm shadow-sm">See details</span>
                                                                    </summary>
                                                                    <div class="mt-2.5 p-4 bg-purple-50/50 backdrop-blur-sm rounded-xl border border-purple-100/80 shadow-[0_4px_12px_rgb(0,0,0,0.03)] text-[8px] text-purple-950 flex flex-col gap-1.5 min-w-[220px] cursor-default animate-fade-in-down" onclick="event.stopPropagation();">
                                                                        <span class="text-[7px] font-black text-purple-700 uppercase tracking-widest mb-1.5 flex items-center gap-1.5 border-b border-purple-100/80 pb-1.5">
                                                                            <i class="fa-solid fa-money-check-dollar text-[8px]"></i> PDC Metadata
                                                                        </span>
                                                                        <div class="flex justify-between gap-4"><span class="font-bold text-purple-600/70">Check #:</span> <span class="font-black"><?= htmlspecialchars($row['pdc_check_number'] ?? 'N/A') ?></span></div>
                                                                       <div class="flex justify-between gap-4"><span class="font-bold text-purple-600/70">Check Date:</span> <span class="font-black"><?= !empty($row['pdc_check_date']) ? date('F d, Y', strtotime($row['pdc_check_date'])) : 'N/A' ?></span></div>
                                                                       <div class="flex justify-between gap-4"><span class="font-bold text-purple-600/70">Bank:</span> <span class="font-black"><?= htmlspecialchars($row['pdc_bank_name'] ?? 'N/A') ?></span></div>
                                                                        <div class="flex justify-between gap-4"><span class="font-bold text-purple-600/70">Account #:</span> <span class="font-black"><?= htmlspecialchars($row['pdc_bank_number'] ?? 'N/A') ?></span></div>
                                                                    </div>
                                                                </details>
                                                                
                                                            <?php else: ?>
                                                                <div class="text-[8px] font-bold text-slate-500">
                                                                    PAYMENT METHOD: <span class="text-slate-900 font-black"><?= strtoupper(htmlspecialchars($row['payment_method'])) ?></span>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="text-right flex flex-col items-end">
                                                <p class="text-[16px] font-black text-slate-900 tracking-tight">₱<?= number_format($row['amount_due'], 2) ?></p>
                                                
                                                <?php 
                                                // ADDED CONDITION: Only show Amount Paid if it's > 0
                                                $amountPaidValue = (float)($row['amount_paid'] ?? 0);
                                                if ($amountPaidValue > 0): 
                                                ?>
                                                    <div class="0/80 border border-slate-100 rounded-lg px-2.5 shadow-sm w-fit self-end backdrop-blur-sm">
                                                        <span class="text-[8px] font-bold uppercase tracking-widest text-slate-400">Amount Paid:</span>
                                                        <span class="text-[11px] font-black text-teal-600">₱<?= number_format($amountPaidValue, 2) ?></span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php 
                                                $p_status = strtolower($row['penalty_status'] ?? '');
                                                if ($p_status === 'waived'): ?>
                                                    <div class="mt-1.5">
                                                        <span class="inline-flex items-center gap-1.5 text-[7px] font-black text-amber-700 bg-amber-50/80 backdrop-blur-sm px-2.5 py-1 rounded-lg border border-amber-200/60 uppercase shadow-sm transition-colors">
                                                           <i class="fa-solid fa-shield-heart text-[8px]"></i>
                                                            Penalty Waived: ₱<?= number_format((float)($row['penalty_paid'] ?? 0), 2) ?>
                                                        </span>
                                                    </div>
                                                <?php elseif ($p_status === 'paid'): ?>
                                                    <div class="mt-1.5">
                                                        <span class="inline-flex items-center gap-1.5 text-[7px] font-black text-emerald-700 bg-emerald-50/80 backdrop-blur-sm px-2.5 py-1 rounded-lg border border-emerald-200/60 uppercase shadow-sm transition-colors">
                                                            <i class="fa-solid fa-circle-check text-[8px]"></i>
                                                            Penalty Paid: ₱<?= number_format((float)($row['penalty_paid'] ?? 0), 2) ?>
                                                        </span>
                                                    </div>
                                               <?php elseif ($p_status === 'pending'): ?>
                                                    <div class="mt-1.5">
                                                        <span class="inline-flex items-center gap-1.5 text-[7px] font-black text-purple-700 bg-purple-50/80 backdrop-blur-sm px-2.5 py-1 rounded-lg border border-purple-200/60 uppercase shadow-sm transition-colors">
                                                            <i class="fa-solid fa-clock-rotate-left text-[8px]"></i>
                                                            Penalty Pending: ₱<?= number_format((float)($row['penalty_paid'] ?? 0), 2) ?>
                                                        </span>
                                                    </div>
                                                <?php elseif ($penalty_amt > 0): ?>
                                                    <div class="mt-1.5">
                                                        <span class="inline-flex items-center gap-1.5 text-[7px] font-black text-rose-700 bg-rose-50/80 backdrop-blur-sm px-2.5 py-1 rounded-lg border border-rose-200/60 uppercase shadow-sm transition-colors">
                                                            <i class="fa-solid fa-triangle-exclamation text-[8px]"></i>
                                                            + Penalty: ₱<?= number_format($penalty_amt, 2) ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if (!empty($row['ar_number']) || !empty($row['or_number'])): ?>
                                                    <div class="mt-3 flex justify-end gap-2">
                                                        <?php if (!empty($row['ar_number'])): ?>
                                                            <a href="/cattleya/user/encoder/fetch/view-receipt?id=<?= $row['id'] ?>&type=AR" target="_blank" 
                                                           class="inline-flex items-center gap-1.5 text-[7px] font-black text-cyan-700 bg-cyan-50/80 backdrop-blur-sm px-3 py-1 rounded-lg border border-cyan-200/60 uppercase tracking-widest hover:bg-cyan-100 hover:shadow-md hover:-translate-y-0.5 transition-all cursor-pointer">
                                                               <i class="fa-solid fa-receipt text-[8px]"></i>
                                                                AR: <?= htmlspecialchars($row['ar_number']) ?>
                                                           </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if (!empty($row['or_number'])): ?>
                                                           <a href="/cattleya/user/encoder/fetch/view-receipt?id=<?= $row['id'] ?>&type=OR" target="_blank" 
                                                            class="inline-flex items-center gap-1.5 text-[7px] font-black text-fuchsia-700 bg-fuchsia-50/80 backdrop-blur-sm px-3 py-1 rounded-lg border border-fuchsia-200/60 uppercase tracking-widest hover:bg-fuchsia-100 hover:shadow-md hover:-translate-y-0.5 transition-all cursor-pointer">
                                                               <i class="fa-solid fa-file-invoice text-[8px]"></i>
                                                               OR: <?= htmlspecialchars($row['or_number']) ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($is_processing): ?>
                                                   <div class="mt-3.5 flex gap-2 justify-end no-print">
                                                        <button type="button" onclick="clearCheck(<?= $row['id'] ?>, <?= (float)$penalty_amt ?>)" 
                                                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-[8px] font-black text-white bg-gradient-to-r from-emerald-400 to-emerald-500 rounded-lg shadow-[0_2px_8px_rgba(16,185,129,0.3)] hover:shadow-[0_4px_12px_rgba(16,185,129,0.4)] hover:-translate-y-0.5 transition-all cursor-pointer">
                                                           <i class="fa-solid fa-circle-check text-[8px]"></i> Cleared
                                                        </button>
                                                       <button type="button" onclick="bounceCheck(<?= $row['id'] ?>)" 
                                                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-[8px] font-black text-white bg-gradient-to-r from-rose-400 to-rose-500 rounded-lg shadow-[0_2px_8px_rgba(244,63,94,0.3)] hover:shadow-[0_4px_12px_rgba(244,63,94,0.4)] hover:-translate-y-0.5 transition-all cursor-pointer">
                                                            <i class="fa-solid fa-triangle-exclamation text-[8px]"></i> Bounce Check
                                                        </button>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if($show_commission): 
                                                    $label = ($is_fully_paid_row) ? "Full Commission (on row amount)" : "Commission Breakdown";
                                                ?>
                                                   <div class="mt-4 pt-3 border-t border-slate-200/60 flex flex-col items-end gap-2 w-full max-w-full">
                                                        <span class="text-[6px] font-black text-indigo-500 uppercase tracking-widest bg-indigo-50/80 backdrop-blur-sm px-2.5 py-0.5 rounded-full border border-indigo-100">
                                                            <?= $label ?>
                                                        </span>
                                                       <div class="flex gap-3 text-[8px] font-bold text-slate-500">
                                                           <span class="flex items-center gap-1.5">Broker (<?= $b_pct ?>%): <span class="text-slate-900 font-black">₱<?= number_format($comm_broker, 2) ?></span></span>
                                                            <span class="flex items-center gap-1.5">UM (<?= $u_pct ?>%): <span class="text-slate-900 font-black">₱<?= number_format($comm_um, 2) ?></span></span>
                                                            <span class="flex items-center gap-1.5">Agent (<?= $a_pct ?>%): <span class="text-slate-900 font-black">₱<?= number_format($comm_agent, 2) ?></span></span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                    </div>
                                    <?php 
                                        $payment_counter++; 
                                        endforeach; 
                                    ?>
                                </div>
                            </div>
                        </div>

                        <div class="lg:col-span-4 space-y-6">
                            <div class="lg:sticky lg:top-5 space-y-6">
                                
                                <div id="checkoutCard" style="background: linear-gradient(135deg, #064e3b 0%, #065f46 100%);" class="rounded-[2rem] p-4 text-white shadow-2xl relative overflow-hidden transition-all duration-500 border-4 border-transparent">
                                    <div class="relative z-10">
                                        <div class="flex items-center gap-4 mb-4 pb-2 border-b border-white/10">
                                            <div class="w-10 h-10 rounded-xl bg-teal-500 flex items-center justify-center text-white shadow-[0_0_20px_rgba(20,184,166,0.4)]">
                                                <i class="fa-solid fa-file-invoice-dollar text-lg"></i>
                                            </div>
                                            <h3 class="font-black text-white text-xs uppercase tracking-[0.2em]">Transaction Summary</h3>
                                        </div>
                            
                                        <div class="space-y-4 mb-2">

                                            <div class="flex justify-between items-center bg-white/5 p-3 rounded-2xl">
                                                <span class="text-teal-400 text-[10px] font-black uppercase tracking-widest">Installments</span>
                                                <span class="font-black text-sm"><?= $paid_months ?> <span class="text-white/30 mx-1">/</span> <?= $total_months ?></span>
                                            </div>

                                            <div class="flex justify-between items-center">
                                                <span class="text-emerald-400 text-[10px] font-bold uppercase tracking-widest">Total Overpayment</span>
                                                <span class="font-black text-sm">₱<?= number_format($total_overpayment, 2) ?></span>
                                            </div>
                                            <div class="flex justify-between items-center">
                                                <span class="text-red-400 text-[10px] font-bold uppercase tracking-widest">Total Penalty</span>
                                                <span class="font-black text-sm">₱<?= number_format($total_penalty, 2) ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="pt-2 mb-4 no-print">
    <div class="flex justify-between pt-2 border-t border-white/10 items-center mb-2">
        <p class="text-[10px] font-black text-white/40 uppercase tracking-[0.2em]">Amount to Settle</p>
        <span id="selectedCount" class="bg-teal-500 text-white px-2 py-0.5 rounded text-[10px] font-black shadow-lg shadow-teal-500/20">0 Selected</span>
    </div>
    <div class="relative group">
        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-2xl font-black text-teal-500 group-focus-within:scale-110 transition-transform">₱</span>
        
        <input 
            type="hidden" 
            id="totalDisplay" 
            name="amount_to_settle" 
            step="0.01"
        >
        
        <input 
            type="text" 
            id="totalDisplayVisual" 
            class="w-full pl-12 pr-4 py-2 bg-white/5 border-2 border-white/10 rounded-2xl focus:outline-none focus:border-teal-500 focus:bg-white/10 font-black text-2xl text-white tracking-tighter transition-all placeholder:text-white/10" 
            placeholder="0.00" 
            oninput="handleAmountFormatting(this)"
            onblur="formatOnBlur(this)"
        >
    </div>
    <div id="overpaymentIndicator" class="flex justify-between items-center mt-2 hidden">
        <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest"><i class="fa-solid fa-arrow-trend-up mr-1"></i>Computed Overpayment</span>
        <span id="computedOverpaymentAmount" class="font-black text-sm text-emerald-400">₱0.00</span>
    </div>
    <p id="amountError" class="text-[11px] font-bold text-red-400 mt-3 hidden bg-red-400/10 p-3 rounded-xl border border-red-400/20"><i class="fa-solid fa-circle-exclamation mr-2"></i>Amount is below the required selection.</p>
</div>
                                        <div class="flex flex-col w-full max-w-sm border border-white/10 bg-white/5 rounded-3xl no-print backdrop-blur-md overflow-hidden">
                                            <div class="p-3 flex items-center justify-between border-b border-white/5 bg-white/[0.02]">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-8 h-8 rounded-xl bg-indigo-500/10 flex items-center justify-center border border-indigo-500/20">
                                                        <i class="fa-solid fa-shield-halved text-indigo-400 text-xs"></i>
                                                    </div>
                                                    <div class="flex flex-col">
                                                        <h4 class="text-[10px] font-black text-white uppercase tracking-wider">Waiver Control</h4>
                                                        <div class="flex items-center gap-1.5">
                                                            <span id="statusDot" class="w-1.5 h-1.5 rounded-full bg-white/20"></span>
                                                            <span id="waiverStatusText" class="text-[9px] text-white/40 font-bold uppercase tracking-widest">Approval Required</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <i class="fa-solid fa-lock text-white/10 text-xs"></i>
                                            </div>
                                            <div class="p-2 flex flex-col items-center justify-center bg-gradient-to-b from-transparent to-white/[0.02]">
                                                <span class="text-[9px] font-bold text-white/30 uppercase tracking-[0.2em] mb-2">Total Accrued Penalty</span>
                                                <div class="flex items-baseline gap-2">
                                                    <span id="displayAccruedPenalty" class="text-3xl font-black text-rose-400 tracking-tighter">₱0.00</span>
                                                    <span class="text-[10px] font-black text-rose-400/40">PHP</span>
                                                </div>
                                            </div>
                                            <div class="p-3 bg-white/[0.03] border-t border-white/5 flex flex-col gap-4">
                                                <button 
                                                    type="button" 
                                                    id="btnRequestWaiver" 
                                                    onclick="openWaiverModal()" 
                                                    class="w-full py-3.5 bg-[#1c5f66] hover:bg-[#114146] text-white text-[11px] font-black rounded-2xl transition-all shadow-xl shadow-[#1c5f66]/20 uppercase tracking-[0.2em] active:scale-[0.96] flex items-center justify-center gap-3"
                                                >
                                                    <!-- Icon in Lime for that sharp brand contrast -->
                                                    <i class="fa-solid fa-paper-plane text-[#a6ce39] text-[12px]"></i>
                                                    <span>Request to Waive</span>
                                                </button>

                                                <div id="toggleWrapper" class="flex items-center justify-between px-4 py-2 bg-white/5 rounded-2xl opacity-20 grayscale pointer-events-none transition-all duration-700 border border-transparent">
                                                    <span class="text-[10px] font-black text-white/30 uppercase tracking-wider">Apply Approved Waiver</span>
                                                    
                                                    <label class="relative inline-flex items-center cursor-pointer">
                                                        <input 
                                                            type="checkbox" 
                                                            id="waivePenalty" 
                                                            name="waive_penalty" 
                                                            value="1" 
                                                            class="sr-only peer" 
                                                            onchange="updateAmountToPay()" 
                                                            disabled
                                                        >
                                                        <div class="w-11 h-6 bg-white/10 rounded-full border border-white/10 
                                                                    peer-focus:outline-none transition-all
                                                                    peer-checked:bg-teal-500 peer-checked:border-teal-400/30
                                                                    after:content-[''] after:absolute after:top-[2px] after:left-[2px] 
                                                                    after:bg-white after:rounded-full after:h-5 after:w-5 
                                                                    after:transition-all peer-checked:after:translate-x-5">
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="paymentDetails" class="hidden space-y-4 mt-3 pt-4 border-t border-white/10 no-print">
                                            <div>
                                                <p class="text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest">Mode of Payment</p>
                                                <select id="modeOfPayment" class="w-full bg-white/5 border border-white/10 rounded-xl py-2 px-3 text-xs font-bold text-white focus:outline-none focus:border-teal-500 transition-all duration-300 backdrop-blur-md">
                                                    <option value="" class="bg-slate-900">Select Mode</option>
                                                    <option value="cash" class="bg-slate-900">Cash</option>
                                                    <option value="PDC" class="bg-slate-900">Post Dated Check (PDC)</option>
                                                    <option value="billspayment" class="bg-slate-900">Online Bills Payment</option>
                                                </select>
                                            </div>

                                            <div id="referenceFields" class="hidden transition-all duration-300">
                                                <p class="text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest">Reference Number</p>
                                                <div class="relative flex items-center input-overflow-wrapper">
                                                    <input type="text" id="referenceNumber" placeholder="REF-XXXXX" 
                                                        class="overflow-target w-full bg-white/5 border border-white/10 rounded-xl py-2 pl-4 pr-9 text-xs font-bold text-white focus:outline-none focus:border-teal-500 transition-all duration-300 placeholder:text-white/20">
                                                    
                                                    <button type="button" class="view-icon-btn absolute right-3 text-white/40 hover:text-teal-400 opacity-0 pointer-events-none transition-all duration-300 transform scale-90">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                                        </svg>
                                                    </button>
                                                    <div class="popover-preview absolute bottom-full mb-2 left-1/2 -translate-x-1/2 bg-slate-950/95 border border-white/10 text-white text-[11px] font-medium px-3 py-1.5 rounded-lg shadow-2xl backdrop-blur-md opacity-0 pointer-events-none transition-all duration-200 transform translate-y-1 scale-95 z-50 max-w-xs break-all"></div>
                                                </div>
                                            </div>

                                            <div id="pdcFields" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-4 transition-all duration-300">
                                                <div>
                                                    <p class="text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest">Bank Name</p>
                                                    <div class="relative flex items-center input-overflow-wrapper">
                                                        <input type="text" id="bankName" placeholder="e.g., Chase, BDO" 
                                                            class="overflow-target w-full bg-white/5 border border-white/10 rounded-xl py-2 pl-4 pr-9 text-xs font-bold text-white focus:outline-none focus:border-teal-500 transition-all duration-300 placeholder:text-white/20">
                                                        <button type="button" class="view-icon-btn absolute right-3 text-white/40 hover:text-teal-400 opacity-0 pointer-events-none transition-all duration-300 transform scale-90">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                                        </button>
                                                        <div class="popover-preview absolute bottom-full mb-2 left-1/2 -translate-x-1/2 bg-slate-950/95 border border-white/10 text-white text-[11px] font-medium px-3 py-1.5 rounded-lg shadow-2xl backdrop-blur-md opacity-0 pointer-events-none transition-all duration-200 transform translate-y-1 scale-95 z-50 max-w-xs break-all"></div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest">Bank Number</p>
                                                    <div class="relative flex items-center input-overflow-wrapper">
                                                        <input type="text" id="bankNumber" placeholder="Enter Bank Code/No." 
                                                            class="overflow-target w-full bg-white/5 border border-white/10 rounded-xl py-2 pl-4 pr-9 text-xs font-bold text-white focus:outline-none focus:border-teal-500 transition-all duration-300 placeholder:text-white/20">
                                                        <button type="button" class="view-icon-btn absolute right-3 text-white/40 hover:text-teal-400 opacity-0 pointer-events-none transition-all duration-300 transform scale-90">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                                        </button>
                                                        <div class="popover-preview absolute bottom-full mb-2 left-1/2 -translate-x-1/2 bg-slate-950/95 border border-white/10 text-white text-[11px] font-medium px-3 py-1.5 rounded-lg shadow-2xl backdrop-blur-md opacity-0 pointer-events-none transition-all duration-200 transform translate-y-1 scale-95 z-50 max-w-xs break-all"></div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest">Check Number</p>
                                                    <div class="relative flex items-center input-overflow-wrapper">
                                                        <input type="text" id="checkNumber" placeholder="0000XXXXXX" 
                                                            class="overflow-target w-full bg-white/5 border border-white/10 rounded-xl py-2 pl-4 pr-9 text-xs font-bold text-white focus:outline-none focus:border-teal-500 transition-all duration-300 placeholder:text-white/20">
                                                        <button type="button" class="view-icon-btn absolute right-3 text-white/40 hover:text-teal-400 opacity-0 pointer-events-none transition-all duration-300 transform scale-90">
                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                                                        </button>
                                                        <div class="popover-preview absolute bottom-full mb-2 left-1/2 -translate-x-1/2 bg-slate-950/95 border border-white/10 text-white text-[11px] font-medium px-3 py-1.5 rounded-lg shadow-2xl backdrop-blur-md opacity-0 pointer-events-none transition-all duration-200 transform translate-y-1 scale-95 z-50 max-w-xs break-all"></div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <p class="text-[9px] font-black text-white/40 uppercase mb-2 tracking-widest">Check Date</p>
                                                    <input type="date" id="checkDate" style="color-scheme: dark;"
                                                        class="w-full bg-white/5 border border-white/10 rounded-xl py-2 px-4 text-xs font-bold text-white focus:outline-none focus:border-teal-500 transition-all duration-300">
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" id="payButton" onclick="showPaymentModal()"
                                            class="w-full mt-3 bg-slate-800 py-2 rounded-2xl font-black text-sm uppercase tracking-widest transition-all duration-500 no-print transform active:scale-95 shadow-2xl">
                                            Confirm and Process
                                        </button>
                                    </div>
                                    
                                    <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-teal-500/10 rounded-full blur-3xl"></div>
                                </div>

                                <?php if ($sale_info): ?>
                                <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 mb-5">
                                    <div class="flex items-center gap-3 mb-6">
                                        <div class="w-8 h-8 rounded-xl bg-teal-600/10 flex items-center justify-center text-teal-600">
                                            <i class="fa-solid fa-users-gear text-sm"></i>
                                        </div>
                                        <h3 class="font-black text-slate-900 text-[10px] uppercase tracking-[0.2em]">Assignment Details</h3>
                                    </div>

                                    <div class="space-y-3">
                                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center font-black text-teal-600 text-[10px]">SA</div>
                                            <div>
                                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Sales Agent</p>
                                                <p class="text-xs font-black text-slate-900 leading-tight"><?= htmlspecialchars($sale_info['agent_fullname'] ?? 'Not Assigned') ?></p>
                                                <p class="text-[9px] text-slate-400 font-bold mt-1">ID: <?= htmlspecialchars($sale_info['agent_id'] ?? 'N/A') ?></p>
                                            </div>
                                        </div>

                                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center font-black text-teal-600 text-[10px]">UM</div>
                                            <div>
                                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Unit Manager</p>
                                                <p class="text-xs font-black text-slate-900 leading-tight"><?= htmlspecialchars($sale_info['um_fullname'] ?? 'Not Assigned') ?></p>
                                                <p class="text-[9px] text-slate-400 font-bold mt-1">ID: <?= htmlspecialchars($sale_info['um_id'] ?? 'N/A') ?></p>
                                            </div>
                                        </div>

                                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-full bg-white border border-slate-200 flex items-center justify-center font-black text-teal-600 text-[10px]">BK</div>
                                            <div>
                                                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Broker</p>
                                                <p class="text-xs font-black text-slate-900 leading-tight"><?= htmlspecialchars($sale_info['broker_fullname'] ?? 'Not Assigned') ?></p>
                                                <p class="text-[9px] text-slate-400 font-bold mt-1">ID: <?= htmlspecialchars($sale_info['broker_id'] ?? 'N/A') ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>
                <div id="paymentModal" class="fixed inset-0 z-[100] hidden overflow-y-auto no-print">
                    <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl transition-opacity"></div>
                    
                    <div class="flex min-h-full items-center justify-center p-4">
                        <div class="relative w-full max-w-lg transform overflow-hidden rounded-[2.5rem] bg-[#0f172a] border border-white/10 p-8 shadow-[0_30px_60px_-15px_rgba(0,0,0,1)] ring-1 ring-white/5 transition-all">
                            
                            <div class="text-center mb-8">
                                <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-gradient-to-br from-teal-500/20 to-teal-500/5 text-teal-400 mb-4 ring-1 ring-teal-500/30 shadow-[0_0_30px_rgba(20,184,166,0.15)]">
                                    <i class="fa-solid fa-receipt text-3xl drop-shadow-md"></i>
                                </div>
                                <h3 class="text-2xl font-black text-white uppercase tracking-widest drop-shadow-sm">Review Transaction</h3>
                                <p class="text-white/50 text-[10px] font-bold uppercase tracking-[0.3em] mt-2">Verify details before processing</p>
                            </div>

                            <div class="space-y-6">
                                
                                <div class="bg-gradient-to-b from-white/[0.07] to-white/[0.03] rounded-3xl p-5 border border-white/10 shadow-inner shadow-white/5">
                                    <div class="flex items-center gap-3 mb-4 pb-3 border-b border-white/5">
                                        <i class="fa-solid fa-map-location-dot text-teal-400 drop-shadow-sm"></i>
                                        <span class="text-[10px] font-black text-white uppercase tracking-widest">Inventory Details</span>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-white/40 text-[9px] font-bold uppercase tracking-wider mb-1">Account Name</p>
                                            <p class="text-white/90 text-xs font-black truncate drop-shadow-sm"><?= htmlspecialchars($sale_info['customer_fullname'] ?? 'N/A') ?></p>
                                        </div>
                                        <div>
                                            <p class="text-white/40 text-[9px] font-bold uppercase tracking-wider mb-1">Lot Info</p>
                                            <p class="text-white/90 text-xs font-black drop-shadow-sm">B<?= $sale_info['block_number'] ?> L<?= $sale_info['lot_number'] ?></p>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-b from-white/[0.07] to-white/[0.03] rounded-3xl p-5 border border-white/10 shadow-inner shadow-white/5 space-y-3.5">
                                    <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-white/50">
                                        <span>Contract Price</span>
                                        <span class="text-white/90 font-black">₱<?= number_format($sale_info['tcp'] ?? 0, 2) ?></span>
                                    </div>
                                    <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-emerald-400/80">
                                        <span>Total Paid to Date</span>
                                        <span class="text-emerald-400 font-black">₱<?= number_format($total_paid, 2) ?></span>
                                    </div>
                                    <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-orange-400/80">
                                        <span>Remaining Balance</span>
                                        <span class="text-orange-400 font-black">₱<?= number_format($remaining_balance, 2) ?></span>
                                    </div>
                                    <div class="h-px bg-gradient-to-r from-transparent via-white/10 to-transparent my-3"></div>
                                    <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-red-400/80">
                                        <span>System Penalty</span>
                                        <span id="modalPenaltyDisplay" class="text-red-400 font-black">₱0.00</span>
                                    </div>
                                    <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-blue-400/80">
                                        <span>Overpayment</span>
                                        <span id="modalOverpaymentDisplay" class="text-blue-400 font-black">₱0.00</span>
                                    </div>
                                    <div class="flex justify-between items-center text-[10px] font-bold uppercase tracking-widest text-white/50">
                                        <span>Status</span>
                                        <span id="modalWaiveStatus" class="px-2.5 py-1 rounded-md bg-white/10 text-[9px] font-black border border-white/5 shadow-sm">Penalty Applied</span>
                                    </div>
                                </div>

                                <div class="bg-gradient-to-b from-white/[0.07] to-white/[0.03] rounded-3xl p-5 border border-white/10 shadow-inner shadow-white/5 space-y-3">
                                    <div class="flex items-center gap-3 mb-2 pb-2 border-b border-white/5">
                                        <i class="fa-solid fa-calendar-check text-indigo-400 drop-shadow-sm"></i>
                                        <span class="text-[10px] font-black text-white uppercase tracking-widest">Selected Due Dates</span>
                                    </div>
                                    <div id="modalSelectedDates" class="text-xs font-bold text-white/80 leading-relaxed mb-4"></div>

                                    <div id="commissionSectionHeader" class="flex items-center gap-3 mb-2 pb-2 border-b border-white/5 pt-2" style="display: none;">
                                        <i class="fa-solid fa-hand-holding-dollar text-emerald-400 drop-shadow-sm"></i>
                                        <span class="text-[10px] font-black text-white uppercase tracking-widest">Commission Release (Optional)</span>
                                    </div>
                                    <div class="space-y-3 pt-1" style="display: none;">
                                        <label id="rowCommBroker" class="flex justify-between items-center cursor-pointer group hover:bg-white/5 p-2 -mx-2 rounded-xl transition-all">
                                            <div class="flex items-center gap-3">
                                                <input type="checkbox" name="release_comm_broker" value="1" class="rounded border-white/20 bg-[#020617] text-teal-500 focus:ring-teal-500/50 w-4 h-4 transition-all shadow-inner">
                                                <div class="flex flex-col">
                                                    <span class="text-[11px] font-bold uppercase tracking-widest text-white/60 group-hover:text-teal-300 transition-colors">Broker</span>
                                                    <span class="text-[9px] text-white/40 font-medium italic truncate max-w-[180px]">
                                                        <?= htmlspecialchars($sale_info['broker_fullname'] ?? 'Not Assigned') ?>
                                                        <span class="ml-1 not-italic text-[8px] font-bold text-white/20 tracking-tighter">ID: <?= htmlspecialchars($sale_info['broker_id'] ?? 'N/A') ?></span>
                                                    </span>
                                                </div>
                                            </div>
                                            <span id="modalCommBroker" class="text-teal-400 text-xs font-black">₱0.00</span>
                                        </label>

                                        <label id="rowCommUM" class="flex justify-between items-center cursor-pointer group hover:bg-white/5 p-2 -mx-2 rounded-xl transition-all">
                                            <div class="flex items-center gap-3">
                                                <input type="checkbox" name="release_comm_um" value="1" class="rounded border-white/20 bg-[#020617] text-teal-500 focus:ring-teal-500/50 w-4 h-4 transition-all shadow-inner">
                                                <div class="flex flex-col">
                                                    <span class="text-[11px] font-bold uppercase tracking-widest text-white/60 group-hover:text-teal-300 transition-colors">Unit Manager</span>
                                                    <span class="text-[9px] text-white/40 font-medium italic truncate max-w-[180px]">
                                                        <?= htmlspecialchars($sale_info['um_fullname'] ?? 'Not Assigned') ?>
                                                        <span class="ml-1 not-italic text-[8px] font-bold text-white/20 tracking-tighter">ID: <?= htmlspecialchars($sale_info['um_id'] ?? 'N/A') ?></span>
                                                    </span>
                                                </div>
                                            </div>
                                            <span id="modalCommUM" class="text-teal-400 text-xs font-black">₱0.00</span>
                                        </label>

                                        <label id="rowCommAgent" class="flex justify-between items-center cursor-pointer group hover:bg-white/5 p-2 -mx-2 rounded-xl transition-all">
                                            <div class="flex items-center gap-3">
                                                <input type="checkbox" name="release_comm_agent" value="1" class="rounded border-white/20 bg-[#020617] text-teal-500 focus:ring-teal-500/50 w-4 h-4 transition-all shadow-inner">
                                                <div class="flex flex-col">
                                                    <span class="text-[11px] font-bold uppercase tracking-widest text-white/60 group-hover:text-teal-300 transition-colors">Agent</span>
                                                    <span class="text-[9px] text-white/40 font-medium italic truncate max-w-[180px]">
                                                        <?= htmlspecialchars($sale_info['agent_fullname'] ?? 'Not Assigned') ?>
                                                        <span class="ml-1 not-italic text-[8px] font-bold text-white/20 tracking-tighter">ID: <?= htmlspecialchars($sale_info['agent_id'] ?? 'N/A') ?></span>
                                                    </span>
                                                </div>
                                            </div>
                                            <span id="modalCommAgent" class="text-teal-400 text-xs font-black">₱0.00</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div class="relative group">
                                        <label class="text-teal-400 text-[10px] font-black uppercase tracking-widest mb-1.5 block">
                                            AR Number <span class="text-red-400 font-black ml-0.5 drop-shadow-md">*</span>
                                        </label>
                                        <input type="text" id="ar_number" class="w-full bg-[#020617]/50 border border-white/10 rounded-xl px-4 py-3 text-white font-bold tracking-wider focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 transition-all placeholder:text-white/20 shadow-inner" placeholder="Enter AR#">
                                    </div>
                                    <div class="relative group">
                                        <label class="text-teal-400 text-[10px] font-black uppercase tracking-widest mb-1.5 block">
                                            OR Number <span class="text-red-400 font-black ml-0.5 drop-shadow-md">*</span>
                                        </label>
                                        <input type="text" id="or_number" class="w-full bg-[#020617]/50 border border-white/10 rounded-xl px-4 py-3 text-white font-bold tracking-wider focus:outline-none focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 transition-all placeholder:text-white/20 shadow-inner" placeholder="Enter OR#">
                                    </div>
                                </div>

                                <div class="bg-gradient-to-br from-teal-500/15 to-teal-900/20 rounded-3xl p-6 border-2 border-teal-500/30 backdrop-blur-md shadow-[0_10px_30px_rgba(20,184,166,0.1)] relative overflow-hidden">
                                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-teal-400/20 rounded-full blur-3xl pointer-events-none"></div>
                                    
                                    <div class="flex justify-between items-end relative z-10">
                                        <div>
                                            <p class="text-teal-300 text-[10px] font-black uppercase tracking-[0.2em] mb-1 drop-shadow-sm">Total Settlement</p>
                                            <p id="modalFinalAmount" class="text-3xl font-black text-white tracking-tighter drop-shadow-md">₱0.00</p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-white/60 text-[9px] font-black uppercase tracking-widest mb-1" id="modalModeLabel">PDC</p>
                                            <div id="modalRefWrapper">
                                                <p id="modalRefDisplay" class="text-white text-xs font-black tracking-widest drop-shadow-sm">REF-XXXXX</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="modalPdcDetails" class="hidden mt-4 pt-4 border-t border-teal-500/20 grid grid-cols-2 gap-x-4 gap-y-3 text-left transition-all duration-300 relative z-10">
                                        <div>
                                            <p class="text-[9px] text-teal-200/50 font-black uppercase tracking-widest mb-0.5">Bank Name</p>
                                            <p id="modalBankName" class="text-white/90 text-xs font-bold truncate">--</p>
                                        </div>
                                        <div>
                                            <p class="text-[9px] text-teal-200/50 font-black uppercase tracking-widest mb-0.5">Check Number</p>
                                            <p id="modalCheckNumber" class="text-white/90 text-xs font-bold tracking-wider">--</p>
                                        </div>
                                        <div>
                                            <p class="text-[9px] text-teal-200/50 font-black uppercase tracking-widest mb-0.5">Bank Number / Branch</p>
                                            <p id="modalBankNumber" class="text-white/90 text-xs font-bold truncate">--</p>
                                        </div>
                                        <div>
                                            <p class="text-[9px] text-teal-200/50 font-black uppercase tracking-widest mb-0.5">Check Date</p>
                                            <p id="modalCheckDate" class="text-teal-400 text-xs font-black drop-shadow-sm">--</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 mt-8">
                                <button type="button" onclick="closePaymentModal()" 
                                    class="group px-6 py-4 rounded-2xl bg-white/5 border border-white/5 text-white/60 text-xs font-black uppercase tracking-widest hover:bg-white/10 hover:text-white hover:border-white/10 transition-all duration-300 shadow-inner">
                                    Go Back
                                </button>
                                <button type="button" onclick="submitFinalPayment()" id="finalConfirmBtn" disabled
                                    class="px-6 py-4 rounded-2xl bg-teal-500 text-white text-xs font-black uppercase tracking-widest opacity-50 cursor-not-allowed transition-all duration-300">
                                    Confirm Payment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Waiver Request Modal -->
                <div id="waiverModal" class="fixed inset-0 z-50 hidden bg-[#114146]/40 backdrop-blur-md flex items-center justify-center p-4 transition-all duration-300">
                    <div class="bg-white rounded-[2.5rem] shadow-[0_25px_60px_rgba(28,95,102,0.2)] w-full max-w-md overflow-hidden transform transition-all border border-slate-100">
                        
                        <!-- Header: Clean & Minimal -->
                        <div class="px-9 pt-9 pb-4 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <!-- Icon with Lime Accent -->
                                <div class="w-12 h-12 bg-[#1c5f66]/10 rounded-2xl flex items-center justify-center text-[#1c5f66] border-b-2 border-[#a6ce39]">
                                    <i class="fa-solid fa-file-invoice-dollar text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-[#114146] font-extrabold text-xl tracking-tight">Waive Penalty</h3>
                                    <p class="text-slate-400 text-[11px] uppercase font-bold tracking-[0.1em]">Request Process</p>
                                </div>
                            </div>
                            <button onclick="closeWaiverModal()" class="w-9 h-9 flex items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-[#1c5f66] transition-all">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="px-9 pb-9 pt-4">
                            <!-- Penalty Card: Brand Themed Gradient -->
                            <div class="relative mb-8 p-7 bg-gradient-to-br from-[#1c5f66] to-[#114146] rounded-[2rem] overflow-hidden shadow-xl shadow-[#1c5f66]/20">
                                <!-- Abstract Lime glow decoration -->
                                <div class="absolute -right-6 -top-6 w-24 h-24 bg-[#a6ce39]/20 rounded-full blur-2xl"></div>
                                
                                <p class="relative z-10 text-teal-100/80 text-[10px] font-bold uppercase tracking-[0.2em] mb-1">Penalty Amount</p>
                                <div class="relative z-10 flex items-baseline gap-1">
                                    <p id="modalTotalPenalty" class="text-white text-4xl font-black tracking-tight">₱ 0.00</p>
                                    <div class="w-2 h-2 bg-[#a6ce39] rounded-full animate-pulse"></div>
                                </div>
                            </div>

                            <!-- Reason Field -->
                            <div class="space-y-3">
                                <label for="waiverReason" class="block text-sm font-bold text-[#114146] ml-1">
                                    Reason for Request <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <textarea 
                                        id="waiverReason" 
                                        rows="4" 
                                        class="w-full border-2 border-slate-100 bg-[#f8fafc] rounded-2xl p-4 text-sm text-[#114146] placeholder:text-slate-400 focus:bg-white focus:border-[#1c5f66] focus:ring-4 focus:ring-[#1c5f66]/5 transition-all resize-none outline-none"
                                        placeholder="Please provide a valid reason for this request..."
                                    ></textarea>
                                </div>
                                
                                <!-- Error Message -->
                                <div id="reasonError" class="hidden flex items-center gap-2 text-rose-500 px-1">
                                    <i class="fa-solid fa-circle-exclamation text-[10px]"></i>
                                    <p class="text-[11px] font-bold uppercase tracking-wide">Detailed reason is required</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-9 py-7 bg-[#f8fafc] border-t border-slate-100 flex gap-4">
                            <button 
                                type="button" 
                                onclick="closeWaiverModal()"
                                class="flex-1 py-4 text-xs font-bold text-slate-500 hover:text-[#114146] hover:bg-slate-200/50 rounded-2xl transition-all uppercase tracking-widest"
                            >
                                Cancel
                            </button>
                            <button 
                                type="button" 
                                id="modalConfirmBtn"
                                onclick="submitWaiverRequest()"
                                class="flex-[1.8] py-4 bg-[#1c5f66] hover:bg-[#114146] text-white text-xs font-bold rounded-2xl transition-all shadow-lg shadow-[#1c5f66]/30 uppercase tracking-[0.2em] flex items-center justify-center gap-3 active:scale-[0.96]"
                            >
                                <span>Submit Request</span>
                                <i class="fa-solid fa-paper-plane text-[#a6ce39] text-[10px]"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div id="pendingNoticeModal" class="fixed inset-0 z-[100] hidden">
                    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
                    
                    <div class="absolute inset-0 flex items-center justify-center p-4">
                        <div class="bg-white rounded-[2.5rem] w-full max-w-md overflow-hidden shadow-2xl border border-slate-100 transform transition-all">
                            <div class="p-8 text-center">
                                <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-6 border-4 border-white shadow-inner">
                                    <i class="fa-solid fa-clock-rotate-left text-amber-500 text-3xl"></i>
                                </div>
                                
                                <h3 class="text-xl font-black text-slate-900 mb-2 tracking-tight">Waiver Request Pending</h3>
                                <p class="text-slate-500 text-sm leading-relaxed mb-8">
                                    Some selected items have a pending waiver. Since they aren't approved yet, <span class="font-bold text-slate-900">penalties are still included</span> in your total. Do you want to proceed with payment anyway?
                                </p>

                                <div class="flex flex-col gap-3">
                                    <button type="button" onclick="proceedAfterNotice()" 
                                        class="w-full py-4 bg-slate-900 hover:bg-slate-800 text-white rounded-2xl font-black text-xs uppercase tracking-[0.2em] transition-all shadow-lg active:scale-95">
                                        Proceed Anyway
                                    </button>
                                    <button type="button" onclick="closeNoticeModal()" 
                                        class="w-full py-4 bg-white hover:bg-slate-50 text-slate-400 rounded-2xl font-black text-xs uppercase tracking-[0.2em] transition-all border border-slate-100">
                                        Wait for Approval
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                    <div class="animate__animated animate__zoomIn">
                        <div class="glass-card py-16 text-center bg-white border-dashed border-2 border-slate-200">
                            <div class="inline-flex items-center justify-center bg-slate-50 rounded-2xl mb-6 w-16 h-16 shadow-inner">
                                <i class="fa-solid fa-user-magnifying text-slate-300 text-2xl"></i>
                            </div>
                            <h2 class="text-lg font-black text-slate-900 mb-1">No Account Selected</h2>
                            <p class="text-slate-500 max-w-xs mx-auto text-xs leading-relaxed px-4">Choose a customer from the dropdown to view details.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
        <!-- Modern Alert Modal for Zero Penalty Error -->
        <div id="errorModal" class="fixed inset-0 z-[100] flex items-center justify-center hidden">
            <!-- Backdrop with blur effect -->
            <div class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm" onclick="closeErrorModal()"></div>
            
            <!-- Modal Content -->
            <div class="relative w-full max-w-sm p-6 overflow-hidden transition-all transform bg-white shadow-2xl rounded-2xl sm:p-8">
                <div class="flex flex-col items-center text-center">
                    <!-- Warning Icon -->
                    <div class="flex items-center justify-center w-16 h-16 mb-5 rounded-full bg-red-50 text-red-500 ring-8 ring-red-50/50">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    
                    <!-- Text Content -->
                    <h3 class="mb-2 text-xl font-bold tracking-tight text-slate-800">Cannot Process Request</h3>
                    <p class="mb-6 text-sm leading-relaxed text-slate-500">
                        Please make sure to request a waiver only for due dates that have an accrued penalty.
                    </p>
                    
                    <!-- Button -->
                    <button onclick="closeErrorModal()" class="w-full px-5 py-3.5 text-sm font-semibold text-white transition-all duration-200 bg-red-500 rounded-xl hover:bg-red-600 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 shadow-sm">
                        Okay
                    </button>
                </div>
            </div>
        </div>
    <script>
        function handleAmountFormatting(visualInput) {
    const rawInput = document.getElementById('totalDisplay');
    
    // Remove everything except digits and a single decimal point
    let value = visualInput.value.replace(/[^\d.]/g, '');
    
    // Split integer and decimal parts to apply commas smoothly while typing
    let parts = value.split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    
    if (parts[1] !== undefined) {
        parts[1] = parts[1].slice(0, 2); // Limit to 2 decimal places
        visualInput.value = parts[0] + '.' + parts[1];
    } else {
        visualInput.value = parts[0];
    }
    
    // Push the clean database-friendly decimal value to the original element
    rawInput.value = value;
    
    // Fire your original validation logic seamlessly
    if (typeof validateAmount === 'function') {
        validateAmount();
    }
}

function formatOnBlur(visualInput) {
    // Adds trailing .00 formatting when the user clicks away
    const rawValue = document.getElementById('totalDisplay').value;
    if (rawValue && !isNaN(rawValue)) {
        visualInput.value = parseFloat(rawValue).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
}

// Watcher loop: If your checkbox script changes 'totalDisplay.value' programmatically, 
// this automatically updates the visual layout with commas instantly.
setInterval(() => {
    const rawInput = document.getElementById('totalDisplay');
    const visualInput = document.getElementById('totalDisplayVisual');
    if (rawInput && visualInput && document.activeElement !== visualInput) {
        const rawVal = rawInput.value;
        if (rawVal && !isNaN(rawVal)) {
            const formatted = parseFloat(rawVal).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            if (visualInput.value !== formatted) {
                visualInput.value = formatted;
            }
        } else if (!rawVal) {
            visualInput.value = '';
        }
    }
}, 100);
       // --- 1. The Modern Modal Generator ---
        function modernConfirm(title, message, confirmText, type = 'success') {
            return new Promise((resolve) => {
                const overlay = document.createElement('div');
                overlay.className = "fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm opacity-0 transition-opacity duration-300";
                
                // Dynamic styles based on action type
                const btnColor = type === 'success' ? 'bg-emerald-500 hover:bg-emerald-600 shadow-emerald-200' : 'bg-rose-500 hover:bg-rose-600 shadow-rose-200';
                const iconColor = type === 'success' ? 'text-emerald-600 bg-emerald-100' : 'text-rose-600 bg-rose-100';
                const icon = type === 'success' ? '<i class="fa-solid fa-money-bill-wave text-2xl"></i>' : '<i class="fa-solid fa-triangle-exclamation text-2xl"></i>';

                overlay.innerHTML = `
                    <div class="bg-white rounded-3xl p-6 md:p-8 max-w-sm w-full mx-4 shadow-2xl transform scale-95 opacity-0 transition-all duration-300 flex flex-col items-center text-center border border-slate-100">
                        <div class="w-16 h-16 rounded-full ${iconColor} flex items-center justify-center mb-5 shadow-inner">
                            ${icon}
                        </div>
                        <h3 class="text-xl font-black text-slate-900 mb-2">${title}</h3>
                        <p class="text-sm font-medium text-slate-500 mb-8">${message}</p>
                        <div class="flex gap-3 w-full">
                            <button id="cancelModalBtn" class="flex-1 py-3 rounded-xl text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">Cancel</button>
                            <button id="confirmModalBtn" class="flex-1 py-3 rounded-xl text-sm font-bold text-white ${btnColor} shadow-md transition-colors">${confirmText}</button>
                        </div>
                    </div>
                `;

                document.body.appendChild(overlay);

                // Trigger animations
                requestAnimationFrame(() => {
                    overlay.classList.remove('opacity-0');
                    overlay.firstElementChild.classList.remove('scale-95', 'opacity-0');
                });

                // Close function
                const close = (result) => {
                    overlay.classList.add('opacity-0');
                    overlay.firstElementChild.classList.add('scale-95', 'opacity-0');
                    setTimeout(() => overlay.remove(), 300);
                    resolve(result);
                };

                overlay.querySelector('#cancelModalBtn').onclick = () => close(false);
                overlay.querySelector('#confirmModalBtn').onclick = () => close(true);
            });
        }

      // --- 2. The Clear Check Process ---
async function clearCheck(paymentId, penaltyAmount) {
    // Elegant, modern UI styling injected directly into the modal markup
    let msgHtml = `
    <style>
        .pm-wrapper {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            text-align: left;
            line-height: 1.5;
        }
        .pm-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .pm-icon-box {
            background-color: #f0fdf4;
            color: #16a34a;
            padding: 10px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #bbf7d0;
        }
        .pm-title-area h4 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: #0f172a;
        }
        .pm-title-area p {
            margin: 2px 0 0 0;
            font-size: 13px;
            color: #64748b;
        }
        .pm-alert-box {
            background: #fffbeb;
            border: 1px solid #fef3c7;
            border-left: 4px solid #d97706;
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #92400e;
            display: flex;
            gap: 8px;
        }
        .pm-input-container {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 16px;
            border-radius: 12px;
        }
        .pm-label {
            display: block;
            margin-bottom: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .pm-input {
            width: 100%;
            padding: 10px 14px;
            font-size: 14px;
            color: #0f172a;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            outline: none;
            box-sizing: border-box;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }
        .pm-input:focus {
            border-color: #4f46e5;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.15);
        }
        .pm-input::placeholder {
            color: #94a3b8;
        }
    </style>

    <div class="pm-wrapper">
        <div class="pm-header">
            <div class="pm-icon-box">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            </div>
            <div class="pm-title-area">
                <h4>Ready to clear payment?</h4>
                <p>This action updates the check status permanently to 'Paid'.</p>
            </div>
        </div>`;
    
    // Dynamic Penalty UI Block
    if (penaltyAmount > 0) {
        msgHtml += `
        <div class="pm-alert-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top:1px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <div>
                <strong>Penalty Notice:</strong> A computed penalty of <strong>₱${penaltyAmount.toLocaleString('en-US', {minimumFractionDigits: 2})}</strong> will automatically be cleared alongside this transaction.
            </div>
        </div>`;
    }
    
    // Premium Interactive Form Field Block
    msgHtml += `
        <div class="pm-input-container">
            <label for="modalDepositSlip" class="pm-label">Deposit Slip Number</label>
            <input type="text" id="modalDepositSlip" placeholder="e.g., DSC-884920" autocomplete="off" class="pm-input">
        </div>
    </div>`;

    const isConfirmed = await modernConfirm("Clear PDC Payment", msgHtml, "Yes, Clear It", "success");

    if (isConfirmed) {
        const depositSlipNumber = document.getElementById('modalDepositSlip')?.value || '';

        // Clean validation gate
        if (!depositSlipNumber.trim()) {
            alert("A valid Deposit Slip Number is required to clear this payment.");
            return; 
        }

        fetch('/cattleya/user/encoder/fetch/process-clear-check', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `payment_id=${encodeURIComponent(paymentId)}&penalty_amount=${encodeURIComponent(penaltyAmount)}&deposit_slip_number=${encodeURIComponent(depositSlipNumber)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload(); 
            } else {
                alert("Failed to update: " + data.message);
            }
        }).catch(err => console.error('Error:', err));
    }
}
        // --- 3. The Bounced Check Process (Updated to use modern modal) ---
        async function bounceCheck(paymentId) {
            const msg = "This will reset the payment status to 'Unpaid' and clear all PDC details. This action cannot be undone.";
            
            const isConfirmed = await modernConfirm("Bounce Check?", msg, "Yes, Bounce It", "danger");

            if (isConfirmed) {
                fetch('/cattleya/user/encoder/fetch/process-bounce-check', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `payment_id=${encodeURIComponent(paymentId)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload(); 
                    } else {
                        alert("Failed to update: " + data.message);
                    }
                }).catch(err => console.error('Error:', err));
            }
        }


        document.addEventListener('DOMContentLoaded', () => {
    const modeOfPayment = document.getElementById('modeOfPayment');
    const referenceFields = document.getElementById('referenceFields');
    const pdcFields = document.getElementById('pdcFields');
    const checkDateInput = document.getElementById('checkDate');

    // 1. Set current system date
    if (checkDateInput) {
        checkDateInput.value = new Date().toISOString().split('T')[0];
    }

    // 2. Mode of Payment Conditional Field Toggling
    modeOfPayment.addEventListener('change', function() {
        referenceFields.classList.add('hidden');
        pdcFields.classList.add('hidden');

        // Note: 'cash' logic removed here so it keeps both fields hidden
        if (this.value === 'billspayment') {
            referenceFields.classList.remove('hidden');
        } else if (this.value === 'PDC') {
            pdcFields.classList.remove('hidden');
        }
        // Recalculate overflow on visible switch changes
        evaluateAllOverflows();
    });

    // 3. Dynamic Text Width / Overflow Monitoring Function
    function checkOverflow(inputElement) {
        const wrapper = inputElement.closest('.input-overflow-wrapper');
        if (!wrapper) return;

        const viewBtn = wrapper.querySelector('.view-icon-btn');
        
        // Native width measurement calculation logic
        const isOverflowing = inputElement.scrollWidth > inputElement.clientWidth;

        if (isOverflowing && inputElement.value.trim() !== "") {
            viewBtn.classList.remove('opacity-0', 'pointer-events-none', 'scale-90');
            viewBtn.classList.add('opacity-100', 'pointer-events-auto', 'scale-100');
        } else {
            viewBtn.classList.add('opacity-0', 'pointer-events-none', 'scale-90');
            viewBtn.classList.remove('opacity-100', 'pointer-events-auto', 'scale-100');
            // Hide preview window if text shrunk back down
            const preview = wrapper.querySelector('.popover-preview');
            if (preview) preview.classList.add('opacity-0', 'pointer-events-none', 'translate-y-1', 'scale-95');
        }
    }

    // Attach Event Trackers to target inputs
    const overflowInputs = document.querySelectorAll('.overflow-target');
    overflowInputs.forEach(input => {
        input.addEventListener('input', () => checkOverflow(input));
        // Handle window resizing edge cases
        window.addEventListener('resize', () => checkOverflow(input));
    });

    function evaluateAllOverflows() {
        overflowInputs.forEach(input => checkOverflow(input));
    }

    // 4. Premium Popover Micro-Interaction Engine
    document.querySelectorAll('.view-icon-btn').forEach(btn => {
        const wrapper = btn.closest('.input-overflow-wrapper');
        const input = wrapper.querySelector('.overflow-target');
        const preview = wrapper.querySelector('.popover-preview');

        // Mouse hover interaction track
        btn.addEventListener('mouseenter', () => {
            preview.textContent = input.value;
            preview.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-1', 'scale-95');
            preview.classList.add('opacity-100', 'translate-y-0', 'scale-100');
        });

        btn.addEventListener('mouseleave', () => {
            preview.classList.add('opacity-0', 'pointer-events-none', 'translate-y-1', 'scale-95');
            preview.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
        });

        // Click interaction focus fallback
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            input.focus();
            input.setSelectionRange(0, input.value.length); // Highlight context
        });
    });
});
  /**
 * INITIALIZATION: Runs when the page opens
 * Automatically selects Approved payments and turns the toggle ON
 */
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.payment-checkbox');
    let hasApproved = false;

    checkboxes.forEach(cb => {
        // If the backend says this specific row is already Approved
        if (cb.dataset.requestWaive === 'Approved') {
            cb.checked = true; // Auto-select the due date
            hasApproved = true;
        }
    });

    if (hasApproved) {
        // 1. Update the UI to show the Approved state
        handleWaiverApproval();
        
        // 2. Auto-ON: Turn the toggle switch ON
        const toggle = document.getElementById('waivePenalty');
        if (toggle) {
            toggle.checked = true;
        }

        // 3. Refresh totals (Penalty calculation and Amount to Pay)
        if (typeof updateAmountToPay === "function") {
            updateAmountToPay();
        }
    }
    
    // Also run the visibility check for the buttons
    checkWaiverStatus();
});

/**
 * Updated handleWaiverApproval
 * Ensures the toggle is unlocked and visually active
 */
function handleWaiverApproval() {
    const btn = document.getElementById('btnRequestWaiver');
    const toggle = document.getElementById('waivePenalty');
    const wrapper = document.getElementById('toggleWrapper');
    const statusText = document.getElementById('waiverStatusText');
    const dot = document.getElementById('statusDot');

    // 1. Hide the request button entirely
    if (btn) btn.classList.add('hidden');

    // 2. Unlock the Toggle Wrapper (remove grayscale and opacity)
    if (wrapper) {
        wrapper.classList.remove('opacity-20', 'grayscale', 'pointer-events-none');
        wrapper.classList.add('border-teal-500/20', 'bg-teal-500/5', 'shadow-[0_0_20px_rgba(20,184,166,0.1)]');
    }

    // 3. Enable the actual checkbox input
    if (toggle) {
        toggle.disabled = false;
    }
    
    // 4. Update Status Labels to Teal (Success Color)
    if (statusText) {
        statusText.innerText = "Approved & Ready";
        statusText.className = "text-[9px] text-teal-400 font-bold uppercase tracking-widest";
    }
    
    if (dot) {
        dot.className = "w-1.5 h-1.5 rounded-full bg-teal-400 animate-pulse";
    }
}

/**
 * The Button Visibility Logic
 */
function checkWaiverStatus() {
    // --- START: ADDED CONDITION TO RESTRICT OTHER SCHEDULES ---
    const allCheckboxes = document.querySelectorAll('.payment-checkbox');
    
    // --- START: NEW SEQUENTIAL PAYMENT ENFORCEMENT ---
    let hasSequentialError = false;
    let firstUncheckedIndex = -1;
    const checkboxesArray = Array.from(allCheckboxes);

    for (let i = 0; i < checkboxesArray.length; i++) {
        if (!checkboxesArray[i].checked) {
            if (firstUncheckedIndex === -1) {
                firstUncheckedIndex = i; // Record the first unpaid & unchecked month
            }
        } else {
            // This box is checked. If there is a prior unchecked box, it's out of sequence.
            if (firstUncheckedIndex !== -1 && firstUncheckedIndex < i) {
                checkboxesArray[i].checked = false; // Uncheck the invalid selection
                hasSequentialError = true;
            }
        }
    }

    if (hasSequentialError) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Payment Restricted',
                text: 'You cannot select this payment schedule because there is a unpaid payment in the previous months. Please select the prior months first.',
                icon: 'warning',
                confirmButtonColor: '#1c5f66',
                borderRadius: '20px',
                didOpen: (popup) => {
                    popup.style.fontFamily = "'Plus Jakarta Sans', sans-serif";
                }
            });
        } else {
            alert('Payment Restricted: You cannot select this payment schedule because there is a unpaid payment in the previous months. Please select the prior months first.');
        }

        if (typeof updateAmountToPay === "function") {
            updateAmountToPay();
        }
    }
    // --- END: NEW SEQUENTIAL PAYMENT ENFORCEMENT ---

    // Find if there is any unsettled payment with an Approved waiver
    const priorityRow = Array.from(allCheckboxes).find(cb => 
        cb.dataset.requestWaive === 'Approved' && 
        (cb.dataset.status || '').toLowerCase() === 'unpaid'
    );

    if (priorityRow) {
        let blockedSelection = false;
        const currentlyChecked = document.querySelectorAll('.payment-checkbox:checked');
        
        // If user tries to check any box other than the approved unsettled one, uncheck it
        currentlyChecked.forEach(box => {
            if (box !== priorityRow) {
                box.checked = false;
                blockedSelection = true;
            }
        });

        // If a restriction trigger fired, alert the user and block further logic
        if (blockedSelection) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Payment Restricted',
                    html: `
                        <div style="text-align: left; font-size: 0.9rem; font-family: 'Plus Jakarta Sans', sans-serif;">
                            <p>You cannot select another payment schedule because there is an unsettled payment with an <b>Approved Waiver</b>.</p>
                            <ul style="margin-top: 10px; margin-bottom: 10px; list-style-type: disc; padding-left: 20px;">
                                <li><b>Customer ID:</b> ${priorityRow.dataset.customerId}</li>
                                <li><b>Block:</b> ${priorityRow.dataset.block} | <b>Lot:</b> ${priorityRow.dataset.lot}</li>
                                <li><b>Due Date:</b> <span class="text-teal-600 font-bold">${priorityRow.dataset.dueDate}</span></li>
                            </ul>
                            <p>Please settle this specific payment first.</p>
                        </div>
                    `,
                    icon: 'warning',
                    confirmButtonColor: '#1c5f66',
                    borderRadius: '20px',
                    // --- ADDED LOGIC FOR FONT FAMILY ---
                    didOpen: (popup) => {
                        popup.style.fontFamily = "'Plus Jakarta Sans', sans-serif";
                    }
                });
            } else {
                alert(`Restricted: You must first settle the Approved Waiver for Due Date: ${priorityRow.dataset.dueDate} (Block ${priorityRow.dataset.block}, Lot ${priorityRow.dataset.lot}) before selecting other schedules.`);
            }


            if (typeof updateAmountToPay === "function") {
                updateAmountToPay(); // Sync the total balance correctly
            }
        }
    }
    // --- END: ADDED CONDITION TO RESTRICT OTHER SCHEDULES ---


    const selectedBoxes = document.querySelectorAll('.payment-checkbox:checked');
    const btn = document.getElementById('btnRequestWaiver');
    const toggle = document.getElementById('waivePenalty'); // Reference the toggle
    
    if (selectedBoxes.length === 0) {
        resetWaiverUI();
        btn.classList.add('hidden'); 
        return;
    }

    let totalPenalty = 0;
    const statuses = [];

    selectedBoxes.forEach(cb => {
        totalPenalty += parseFloat(cb.dataset.penalty) || 0;
        statuses.push(cb.dataset.requestWaive || '');
    });

    // If Penalty is 0, we don't need a waiver button
    if (totalPenalty <= 0) {
        btn.classList.add('hidden');
        return;
    }

    const isApproved = statuses.length > 0 && statuses.every(status => status === 'Approved');
    const isPending = statuses.includes('Requested');

    if (isApproved) {
        handleWaiverApproval();
        
        // --- ADDED CONDITION: Automatically ON the toggle if all selected are Approved ---
        if (toggle && !toggle.checked) {
            toggle.checked = true;
            if (typeof updateAmountToPay === "function") {
                updateAmountToPay();
            }
        }
    } else if (isPending) {
        setWaiverPendingUI();
    } else {
        resetWaiverUI();
    }
}

/**
 * State 1: Default / Null
 */
function resetWaiverUI() {
    const btn = document.getElementById('btnRequestWaiver');
    const toggle = document.getElementById('waivePenalty');
    const wrapper = document.getElementById('toggleWrapper');
    const statusText = document.getElementById('waiverStatusText');
    const dot = document.getElementById('statusDot');

    // Reset Button
    btn.classList.remove('hidden');
    btn.disabled = false;
    btn.innerHTML = `<i class="fa-solid fa-paper-plane text-[10px]"></i> Request to Waive`;
    btn.className = "w-full py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-[11px] font-black rounded-2xl transition-all shadow-xl shadow-indigo-600/20 uppercase tracking-widest active:scale-[0.98] flex items-center justify-center gap-2";

    // Lock Toggle
    wrapper.classList.add('opacity-20', 'grayscale', 'pointer-events-none');
    wrapper.classList.remove('border-teal-500/20', 'bg-teal-500/5');
    toggle.disabled = true;
    toggle.checked = false;

    // Reset Labels
    statusText.innerText = "Approval Required";
    statusText.className = "text-[9px] text-white/40 font-bold uppercase tracking-widest";
    dot.className = "w-1.5 h-1.5 rounded-full bg-white/20";
}

/**
 * State 2: Requested / Pending
 */
function setWaiverPendingUI() {
    const btn = document.getElementById('btnRequestWaiver');
    const toggle = document.getElementById('waivePenalty');
    const wrapper = document.getElementById('toggleWrapper');
    const statusText = document.getElementById('waiverStatusText');
    const dot = document.getElementById('statusDot');

    // Set Button to Pending
    btn.classList.remove('hidden');
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-clock text-[10px]"></i> Pending for Approval`;
    btn.className = "w-full py-2 bg-amber-500 text-white text-[11px] font-black rounded-2xl transition-all shadow-xl shadow-amber-500/20 uppercase tracking-widest flex items-center justify-center gap-2 cursor-not-allowed";

    // Lock Toggle
    wrapper.classList.add('opacity-20', 'grayscale', 'pointer-events-none');
    toggle.disabled = true;
    toggle.checked = false;

    // Update Labels
    statusText.innerText = "Awaiting Approval";
    statusText.className = "text-[9px] text-amber-400 font-bold uppercase tracking-widest";
    dot.className = "w-1.5 h-1.5 rounded-full bg-amber-400";
}
/**
 * Opens the Waiver Modal and calculates the total penalty.
 */
function openWaiverModal() {
    const selectedBoxes = document.querySelectorAll('.payment-checkbox:checked');
    if (selectedBoxes.length === 0) return;

    let totalPenalty = 0;
    let hasZeroPenalty = false;
    
    // Calculate total penalty from selected checkboxes
    selectedBoxes.forEach(cb => {
        // Change 'dataset.penalty' to match the actual data attribute on your checkbox (e.g., data-penalty)
        const penaltyAmount = parseFloat(cb.dataset.penalty) || 0;
        
        // Add condition to check if any selected item has no penalty
        if (penaltyAmount <= 0) {
            hasZeroPenalty = true;
        }

        totalPenalty += penaltyAmount;
    });

    // If any checked box doesn't have a penalty, show popup and prevent modal opening
    if (hasZeroPenalty) {
        // Replaced alert with the modern UI modal
        document.getElementById('errorModal').classList.remove('hidden');
        return;
    }

    // Update modal UI
    document.getElementById('modalTotalPenalty').innerText = '₱ ' + totalPenalty.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    
    // Reset inputs
    document.getElementById('waiverReason').value = '';
    document.getElementById('reasonError').classList.add('hidden');

    // Show modal
    document.getElementById('waiverModal').classList.remove('hidden');
}

/**
 * Closes the modern Error Modal
 */
function closeErrorModal() {
    document.getElementById('errorModal').classList.add('hidden');
}
/**
 * Closes the Waiver Modal.
 */
function closeWaiverModal() {
    document.getElementById('waiverModal').classList.add('hidden');
}
/**
 * Triggers the AJAX request to update the database
 */
async function submitWaiverRequest() {
    const reasonInput = document.getElementById('waiverReason').value.trim();
    const errorMsg = document.getElementById('reasonError');

    // 1. Validation: Ensure reason is provided
    if (reasonInput === '') {
        if (errorMsg) errorMsg.classList.remove('hidden');
        return;
    } else {
        if (errorMsg) errorMsg.classList.add('hidden');
    }

    // 2. Collect Selected Payments
    const selectedBoxes = document.querySelectorAll('.payment-checkbox:checked');
    if (selectedBoxes.length === 0) {
        // Modernized selection error
        Swal.fire({
            icon: 'warning',
            title: 'No Selection',
            text: 'Please select at least one payment to waive.',
            confirmButtonColor: '#1c5f66',
            borderRadius: '20px'
        });
        return;
    }

    const waiverRequests = [];
    selectedBoxes.forEach(cb => {
        waiverRequests.push({
            customer_id: cb.dataset.customerId,
            due_date: cb.dataset.dueRaw, 
            block_number: cb.dataset.block,
            lot_number: cb.dataset.lot,
            reason: reasonInput,
            penalty: cb.dataset.penalty || 0 
        });
    });

    // 3. Handle UI Loading State
    const btn = document.getElementById('modalConfirmBtn');
    const originalContent = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin"></i> Processing...`;
    
    try {
        const response = await fetch('/cattleya/user/encoder/fetch/request-waiver', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ requests: waiverRequests })
        });

        if (!response.ok) throw new Error(`Server error: ${response.status}`);

        const data = await response.json();

        if (data.success) {
            closeWaiverModal(); 

            // Await the alert so the code pauses until the user clicks "Done"
            await Swal.fire({
                title: 'Success!',
                text: 'Your waiver request has been submitted for review.',
                icon: 'success',
                iconColor: '#a6ce39', 
                showConfirmButton: true,
                confirmButtonText: 'Done',
                confirmButtonColor: '#1c5f66', 
                background: '#ffffff',
                padding: '2rem',
                buttonsStyling: true,
                allowOutsideClick: false, // Prevents closing by clicking outside
                showClass: { popup: 'animate__animated animate__fadeInUp animate__faster' },
                hideClass: { popup: 'animate__animated animate__fadeOutDown animate__faster' }
            });

            // Reload the page immediately after they click "Done"
            location.reload();
            return; 
        } else {
            throw new Error(data.message || "Failed to update database.");
        }
    } catch (error) {
        console.error('Error:', error);
        
        // Modernized Error Message
        Swal.fire({
            icon: 'error',
            title: 'Submission Failed',
            text: error.message,
            confirmButtonColor: '#ef4444',
        });

        // Re-enable the button only if the submission failed
        btn.disabled = false;
        btn.innerHTML = originalContent;
        if (typeof checkWaiverStatus === "function") checkWaiverStatus();
    }
}
// Run the check on initial page load in case checkboxes are pre-selected
document.addEventListener('DOMContentLoaded', checkWaiverStatus);

        // Initialize Event Listeners
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.payment-checkbox');
        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                // Style toggle visually
                const label = document.querySelector(`label[for="${this.id}"]`);
                if (this.checked) {
                    label.classList.add('border-teal-500', 'bg-teal-50/10');
                    label.classList.remove('border-slate-100', 'bg-white');
                } else {
                    label.classList.remove('border-teal-500', 'bg-teal-50/10');
                    label.classList.add('border-slate-100', 'bg-white');
                }
                updateAmountToPay();
            });
        });
    });

    function getBaseRequiredAmount() {
        const checkboxes = document.querySelectorAll('.payment-checkbox:checked');
        const waivePenalty = document.getElementById('waivePenalty').checked;
        let totalRequired = 0;

        checkboxes.forEach(cb => {
            let amount = parseFloat(cb.dataset.amount) || 0;
            let penalty = parseFloat(cb.dataset.penalty) || 0;
            
            // Logic: If waived, the "Required" amount to proceed does not include the penalty
            if (waivePenalty) penalty = 0;
            totalRequired += amount + penalty;
        });
        
        return totalRequired;
    }
    
    function validateAmount() {
        const totalDisplay = document.getElementById('totalDisplay');
        const errorMsg = document.getElementById('amountError');
        const overpaymentIndicator = document.getElementById('overpaymentIndicator');
        const computedOverpaymentAmount = document.getElementById('computedOverpaymentAmount');
        const checkboxes = document.querySelectorAll('.payment-checkbox:checked');
        const isWaived = document.getElementById('waivePenalty').checked; // ADDED: Check waive status
        
        let requiredBase = getBaseRequiredAmount();
        let enteredAmount = parseFloat(totalDisplay.value) || 0;

        // NEW: Always calculate overpayment against (Principal + Penalty) as per instructions
        let principalTotal = 0;
        let penaltyTotal = 0;
        checkboxes.forEach(cb => {
            principalTotal += parseFloat(cb.dataset.amount) || 0;
            penaltyTotal += parseFloat(cb.dataset.penalty) || 0;
        });
        
        // ADDED CONDITION: Compute threshold based on whether penalty is waived
        const thresholdForOverpayment = isWaived ? principalTotal : (principalTotal + penaltyTotal);

        // Reset display states
        errorMsg.classList.add('hidden');
        overpaymentIndicator.classList.add('hidden');
        totalDisplay.classList.remove('border-red-500', 'text-red-400');
        totalDisplay.classList.add('border-white/10', 'text-white');

        if (checkboxes.length > 0) {
            if (enteredAmount < requiredBase) {
                errorMsg.classList.remove('hidden');
                totalDisplay.classList.add('border-red-500', 'text-red-400');
                totalDisplay.classList.remove('border-white/10', 'text-white');
            } else if (enteredAmount > thresholdForOverpayment) {
                // Logic: Only show overpayment if it exceeds the computed threshold
                let overpayment = enteredAmount - thresholdForOverpayment;
                computedOverpaymentAmount.textContent = '₱' + overpayment.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                overpaymentIndicator.classList.remove('hidden');
            }
        }
    }

   /**
 * STEP 1: The Gatekeeper
 * Intercepts the click to check for pending requests
 */
function showPaymentModal() {
    const checkboxes = document.querySelectorAll('.payment-checkbox:checked');
    let hasPendingRequest = false;

    checkboxes.forEach(cb => {
        if (cb.dataset.requestWaive === 'Requested') {
            hasPendingRequest = true;
        }
    });

    if (hasPendingRequest) {
        // Show the modern notice modal instead of a browser popup
        document.getElementById('pendingNoticeModal').classList.remove('hidden');
    } else {
        // No pending requests, go straight to the payment modal
        renderPaymentDetails();
    }
}
/**
 * Monitors the Required AR and OR input fields and toggles 
 * the final payment confirmation action button states.
 */
function validateModalInputs() {
    const arInput = document.getElementById('ar_number');
    const orInput = document.getElementById('or_number');
    const confirmBtn = document.getElementById('finalConfirmBtn');

    if (!arInput || !orInput || !confirmBtn) return;

    const isArFilled = arInput.value.trim() !== "";
    const isOrFilled = orInput.value.trim() !== "";

    if (isArFilled && isOrFilled) {
        // Both fields are completed -> Enable Button and apply active premium states
        confirmBtn.disabled = false;
        confirmBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        // Upgraded active classes: Added glow shadow and smoother scaling
        confirmBtn.classList.add('hover:bg-teal-400', 'transform', 'active:scale-[0.98]', 'shadow-[0_15px_30px_-5px_rgba(20,184,166,0.4)]');
    } else {
        // Validation fails -> Securely disable and return to low-profile disabled state
        confirmBtn.disabled = true;
        confirmBtn.classList.add('opacity-50', 'cursor-not-allowed');
        confirmBtn.classList.remove('hover:bg-teal-400', 'transform', 'active:scale-[0.98]', 'shadow-[0_15px_30px_-5px_rgba(20,184,166,0.4)]');
    }
}

// Attach listeners to update states automatically as users type inside the modal
document.addEventListener('DOMContentLoaded', () => {
    const arInput = document.getElementById('ar_number');
    const orInput = document.getElementById('or_number');

    if (arInput) arInput.addEventListener('input', validateModalInputs);
    if (orInput) orInput.addEventListener('input', validateModalInputs);
});

/**
 * Update your existing renderPaymentDetails() function to reset fields 
 * and clear states when the modal is freshly displayed.
 */
const originalRenderPaymentDetails = renderPaymentDetails;
renderPaymentDetails = function() {
    // 1. Run the original display/calculation engine context layout logic
    originalRenderPaymentDetails.apply(this, arguments);

    // 2. Clear old input entries from previous openings for security
    const arInput = document.getElementById('ar_number');
    const orInput = document.getElementById('or_number');
    if (arInput) arInput.value = '';
    if (orInput) orInput.value = '';

    // 3. Immediately evaluate button states
    validateModalInputs();
};
/**
 * Helper to close the notice
 */
function closeNoticeModal() {
    document.getElementById('pendingNoticeModal').classList.add('hidden');
}

/**
 * Helper to proceed from the notice
 */
function proceedAfterNotice() {
    closeNoticeModal();
    renderPaymentDetails();
}

/**
 * STEP 2: The Logic (Your Original Code)
 * This populates the actual payment modal
 */
function renderPaymentDetails() {
    const totalDisplayValue = document.getElementById('totalDisplay').value;
    const settleAmount = parseFloat(totalDisplayValue) || 0;
    
    // Core inputs from processing form
    const modeOfPaymentEl = document.getElementById('modeOfPayment');
    const mode = modeOfPaymentEl ? modeOfPaymentEl.value : '';
    const ref = document.getElementById('referenceNumber') ? document.getElementById('referenceNumber').value : 'NO REFERENCE';
    const isWaived = document.getElementById('waivePenalty').checked;

    const checkboxes = document.querySelectorAll('.payment-checkbox:checked');
    let selectedDates = [];
    let totalBrokerComm = 0;
    let totalUmComm = 0;
    let totalAgentComm = 0;
    let totalPenalty = 0;
    let principalTotal = 0;

    checkboxes.forEach(cb => {
        const dateValue = cb.getAttribute('data-due-date');
        if (dateValue) { selectedDates.push(dateValue); }
        
        totalBrokerComm += parseFloat(cb.dataset.commBroker || 0);
        totalUmComm += parseFloat(cb.dataset.commUm || 0);
        totalAgentComm += parseFloat(cb.dataset.commAgent || 0);
        totalPenalty += parseFloat(cb.dataset.penalty || 0);
        principalTotal += parseFloat(cb.dataset.amount || 0);
    });

    const formatter = new Intl.NumberFormat('en-PH', {
        style: 'currency', currency: 'PHP', minimumFractionDigits: 2
    });

    // Dates Display
    const datesContainer = document.getElementById('modalSelectedDates');
    if (datesContainer) {
        datesContainer.innerHTML = selectedDates.length > 0 ? 
            selectedDates.map(date => `<span class="inline-block bg-indigo-500/20 text-indigo-300 px-3 py-1 rounded-xl mb-1 mr-1 text-[10px] border border-indigo-500/30 font-black tracking-wider">${date}</span>`).join('') :
            '<span class="text-white/40 italic text-[11px]">No dates selected.</span>';
    }

    // Assign Core Financial Elements
    document.getElementById('modalFinalAmount').innerText = formatter.format(settleAmount);
    document.getElementById('modalModeLabel').innerText = mode ? mode.toUpperCase() : 'NO MODE SELECTED';
    
    // Toggle View State Context based on Mode of Payment Value
    const modalRefWrapper = document.getElementById('modalRefWrapper');
    const modalPdcDetails = document.getElementById('modalPdcDetails');

    if (mode === 'PDC') {
        // Hide standard text layout reference block
        if (modalRefWrapper) modalRefWrapper.classList.add('hidden');
        if (modalPdcDetails) modalPdcDetails.classList.remove('hidden');

        // Extract input values from the main form fields
        const inputBankName = document.getElementById('bankName')?.value || '--';
        const inputBankNumber = document.getElementById('bankNumber')?.value || '--';
        const inputCheckNumber = document.getElementById('checkNumber')?.value || '--';
        const inputCheckDate = document.getElementById('checkDate')?.value || '--';

        // Assign to Modal Elements
        document.getElementById('modalBankName').innerText = inputBankName;
        document.getElementById('modalBankNumber').innerText = inputBankNumber;
        document.getElementById('modalCheckNumber').innerText = inputCheckNumber;
        document.getElementById('modalCheckDate').innerText = inputCheckDate;
    } else {
        // Fallback context: Show reference number for Cash & Online Bills Payment
        if (modalPdcDetails) modalPdcDetails.classList.add('hidden');
        if (modalRefWrapper) modalRefWrapper.classList.remove('hidden');
        document.getElementById('modalRefDisplay').innerText = ref.toUpperCase();
    }
    
    // Remaining Original Calculation Elements Logic (Untouched)
    const modalPenalty = document.getElementById('modalPenaltyDisplay');
    if (modalPenalty) {
        modalPenalty.innerText = formatter.format(totalPenalty);
        isWaived ? modalPenalty.classList.add('opacity-40', 'line-through') 
                 : modalPenalty.classList.remove('opacity-40', 'line-through');
    }

    const modalOverpayment = document.getElementById('modalOverpaymentDisplay');
    if (modalOverpayment) {
        const overpaymentAmount = Math.max(0, settleAmount - (isWaived ? principalTotal : (principalTotal + totalPenalty)));
        modalOverpayment.innerText = formatter.format(overpaymentAmount);
        const overpaymentRow = modalOverpayment.closest('div');
        if (overpaymentRow) {
            overpaymentAmount > 0 ? overpaymentRow.classList.remove('hidden') : overpaymentRow.classList.add('hidden');
        }
    }

    document.getElementById('modalCommBroker').innerText = formatter.format(totalBrokerComm);
    document.getElementById('modalCommUM').innerText = formatter.format(totalUmComm);
    document.getElementById('modalCommAgent').innerText = formatter.format(totalAgentComm);

    const hasComm = (totalBrokerComm > 0 || totalUmComm > 0 || totalAgentComm > 0);
    document.getElementById('commissionSectionHeader').classList.toggle('hidden', !hasComm);
    document.getElementById('rowCommBroker').classList.toggle('hidden', totalBrokerComm <= 0);
    document.getElementById('rowCommUM').classList.toggle('hidden', totalUmComm <= 0);
    document.getElementById('rowCommAgent').classList.toggle('hidden', totalAgentComm <= 0);

    const waiveStatus = document.getElementById('modalWaiveStatus');
    if(isWaived) {
        waiveStatus.innerText = "PENALTY WAIVED";
        waiveStatus.classList.replace('text-white/60', 'text-teal-400');
    } else {
        waiveStatus.innerText = "PENALTY APPLIED";
        waiveStatus.classList.replace('text-teal-400', 'text-white/60');
    }

    document.getElementById('paymentModal').classList.remove('hidden');
}
function formatPHP(val) {
    return '₱' + val.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

// Optional: Close modal when clicking outside content
window.onclick = function(event) {
    const modal = document.getElementById('paymentModal');
    if (event.target == modal.firstElementChild) {
        closePaymentModal();
    }
}                                           

function openLedgerModal() {
    const modal = document.getElementById('ledgerModal');
    const modalContent = document.getElementById('modalLedgerContent');
    
    // Find the main print ledger container on the page
    const originalLedger = document.querySelector('.ledger-container');
    
    if (originalLedger) {
        // Clone the content into the modal
        modalContent.innerHTML = originalLedger.outerHTML;
        
        const clonedLedger = modalContent.querySelector('.ledger-container');
        if(clonedLedger) {
            clonedLedger.classList.remove('only-print', 'hidden');
            clonedLedger.style.display = 'block'; 
        }
    } else {
        modalContent.innerHTML = "<p class='text-center text-red-500'>Ledger content not found.</p>";
    }

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeLedgerModal() {
    const modal = document.getElementById('ledgerModal');
    modal.classList.add('hidden');
    document.body.style.overflow = 'auto';
    document.getElementById('modalLedgerContent').innerHTML = '';
}

document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        closeLedgerModal();
    }
});

// Automatic Submit for Datalist
document.getElementById('customer_input').addEventListener('input', function(e) {
    const input = e.target;
    const list = input.getAttribute('list');
    const options = document.getElementById(list).childNodes;
    for (let i = 0; i < options.length; i++) {
        if (options[i].value === input.value) {
            input.form.submit();
            break;
        }
    }
});
const checkboxes = document.querySelectorAll('.payment-checkbox');
const totalDisplay = document.getElementById('totalDisplay');
const selectedCount = document.getElementById('selectedCount');
const payButton = document.getElementById('payButton');
const checkoutCard = document.getElementById('checkoutCard');
const amountError = document.getElementById('amountError');

const paymentDetails = document.getElementById('paymentDetails');
const modeOfPayment = document.getElementById('modeOfPayment');
const referenceNumber = document.getElementById('referenceNumber');

let requiredAmount = 0;
function updateAmountToPay() {
    let principalTotal = 0;
    let penaltyTotal = 0;
    let count = 0;
    let hasApprovedWaiver = false; // Added to track waiver status

    const totalDisplay = document.getElementById('totalDisplay');
    const waiveCheckbox = document.getElementById('waivePenalty');
    const toggleWrapper = document.getElementById('toggleWrapper'); // Added reference to toggle wrapper
    const selectedBoxes = document.querySelectorAll('.payment-checkbox:checked');

    selectedBoxes.forEach(checkbox => {
        principalTotal += parseFloat(checkbox.dataset.amount) || 0;
        penaltyTotal += parseFloat(checkbox.dataset.penalty) || 0;
        
        // ADDED CONDITION: Check if the selected row has an Approved waiver
        if (checkbox.dataset.requestWaive && checkbox.dataset.requestWaive.toLowerCase() === 'approved') {
            hasApprovedWaiver = true;
        }
        
        count++;
    });

    // ADDED CONDITION: Turn ON the toggle, disable it, and remove grayscale if Approved
    if (waiveCheckbox && toggleWrapper) {
        if (hasApprovedWaiver) {
            waiveCheckbox.checked = true;
            waiveCheckbox.disabled = true; // Cannot be turned off
            toggleWrapper.classList.remove('opacity-20', 'grayscale', 'pointer-events-none');
        } else {
            waiveCheckbox.checked = false;
            waiveCheckbox.disabled = true;
            toggleWrapper.classList.add('opacity-20', 'grayscale', 'pointer-events-none');
        }
    }

    // Moved this line slightly down so it reads the updated state of the checkbox
    const isWaived = waiveCheckbox ? waiveCheckbox.checked : false;

    const requiredBase = isWaived ? principalTotal : (principalTotal + penaltyTotal);
    
    if (count > 0) {
        totalDisplay.value = requiredBase.toFixed(2);
    } else {
        totalDisplay.value = '';
    }

    // ADDED CONDITION: Compute threshold based on whether penalty is waived
    const thresholdForOverpayment = isWaived ? principalTotal : (principalTotal + penaltyTotal);
    let currentInput = parseFloat(totalDisplay.value) || 0;

    const overpaymentAmount = Math.max(0, currentInput - thresholdForOverpayment);

    const displayAccrued = document.getElementById('displayAccruedPenalty');
    if (displayAccrued) {
        displayAccrued.innerText = formatPHP(penaltyTotal);
        isWaived ? displayAccrued.classList.add('line-through', 'opacity-40') 
                 : displayAccrued.classList.remove('line-through', 'opacity-40');
    }

    const overpaymentIndicator = document.getElementById('overpaymentIndicator');
    const displayComputedOver = document.getElementById('computedOverpaymentAmount');
    if (overpaymentAmount > 0) {
        overpaymentIndicator?.classList.remove('hidden');
        if (displayComputedOver) displayComputedOver.innerText = formatPHP(overpaymentAmount);
    } else {
        overpaymentIndicator?.classList.add('hidden');
    }

    const selectedCount = document.getElementById('selectedCount');
    if (selectedCount) selectedCount.textContent = count + ' Selected';

    const modalPenalty = document.getElementById('modalPenaltyDisplay');
    if (modalPenalty) {
        modalPenalty.innerText = formatPHP(penaltyTotal);
        isWaived ? modalPenalty.classList.add('opacity-40', 'line-through') 
                 : modalPenalty.classList.remove('opacity-40', 'line-through');
    }

    const modalOverpayment = document.getElementById('modalOverpaymentDisplay');
    if (modalOverpayment) {
        modalOverpayment.innerText = formatPHP(overpaymentAmount);
        const overpaymentRow = modalOverpayment.closest('div');
        if (overpaymentRow) {
            overpaymentAmount > 0 ? overpaymentRow.classList.remove('hidden') 
                                : overpaymentRow.classList.add('hidden');
        }
    }

    const paymentDetails = document.getElementById('paymentDetails');
    if (paymentDetails) {
        count > 0 ? paymentDetails.classList.remove('hidden') : paymentDetails.classList.add('hidden');
    }

    const checkoutCard = document.getElementById('checkoutCard');
    if (requiredBase > 0 && checkoutCard) {
        checkoutCard.classList.replace('border-slate-100', 'border-teal-500');
    }

    if (typeof validatePaymentForm === 'function') {
        validatePaymentForm();
    }
}
/** 
 * Helper function to format numbers as Philippine Peso
 */
function formatPHP(val) {
    return '₱' + val.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
// --- REVISED VALIDATION FUNCTION ---
function validatePaymentForm() {
    // 1. Ensure we get the latest values from the DOM
    const modeOfPayment = document.getElementById('modeOfPayment');
    const totalDisplay = document.getElementById('totalDisplay');
    const payButton = document.getElementById('payButton'); 
    
    // Core conditional inputs
    const referenceNumber = document.getElementById('referenceNumber');
    const bankName = document.getElementById('bankName');
    const bankNumber = document.getElementById('bankNumber');
    const checkNumber = document.getElementById('checkNumber');
    const checkDate = document.getElementById('checkDate');

    // 2. Safeguard: Check if core engine elements exist
    if (!modeOfPayment || !totalDisplay || !payButton) return;

    const modeValue = modeOfPayment.value.trim();
    const currentAmount = parseFloat(totalDisplay.value) || 0;

    // Start by checking global rules: Mode can't be empty, Amount must be > 0
    let isInvalid = modeValue === "" || currentAmount <= 0;

    // 3. Dynamic Conditional Evaluation Strategy
    if (!isInvalid) {
        if (modeValue === 'cash' || modeValue === 'billspayment') {
            // For Cash and Bills Payment, only the reference number is required
            const refValue = referenceNumber ? referenceNumber.value.trim() : "";
            isInvalid = refValue === "";
        } else if (modeValue === 'PDC') {
            // For PDC, all 4 check details must be filled out completely
            const bName = bankName ? bankName.value.trim() : "";
            const bNum = bankNumber ? bankNumber.value.trim() : "";
            const cNum = checkNumber ? checkNumber.value.trim() : "";
            const cDate = checkDate ? checkDate.value.trim() : "";

            isInvalid = bName === "" || bNum === "" || cNum === "" || cDate === "";
        }
        
        // --- ADDED CONDITION START ---
        // If the payment method is Cash, bypass previous validations and enable the button
        if (modeValue === 'cash') {
            isInvalid = false;
        }
        // --- ADDED CONDITION END ---
    }

    // 4. Apply state
    payButton.disabled = isInvalid;

    // 5. Visual Feedback
    if (isInvalid) {
        payButton.classList.add('opacity-50', 'cursor-not-allowed');
        payButton.classList.remove('hover:bg-teal-400', 'active:scale-95');
    } else {
        payButton.classList.remove('opacity-50', 'cursor-not-allowed');
        payButton.classList.add('hover:bg-teal-400', 'active:scale-95');
    }
}

// Hook up real-time event listeners to every interactive field
const trackingInputs = [
    'modeOfPayment', 'referenceNumber', 'bankName', 
    'bankNumber', 'checkNumber', 'checkDate'
];

trackingInputs.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;
    
    // Listen to changes (for selects/dates) and inputs (for typing text)
    el.addEventListener('input', validatePaymentForm);
    el.addEventListener('change', validatePaymentForm);
});
// --- EVENT LISTENERS ---

// Listen for typing/selection changes in the payment fields
modeOfPayment.addEventListener('change', validatePaymentForm);
referenceNumber.addEventListener('input', validatePaymentForm);

document.getElementById('waivePenalty').addEventListener('change', updateAmountToPay);

document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('payment-checkbox')) {
        updateAmountToPay();
    }
});

checkboxes.forEach(box => {
    box.addEventListener('change', updateAmountToPay);
});

// Run once on load to ensure button starts in correct state
validatePaymentForm();
async function submitFinalPayment() {
    const btn = document.getElementById('finalConfirmBtn');
    const selectedCheckboxes = document.querySelectorAll('.payment-checkbox:checked');
    const isWaived = document.getElementById('waivePenalty').checked;
    
    // Capture AR and OR numbers
    const arNumber = document.getElementById('ar_number').value;
    const orNumber = document.getElementById('or_number').value;
    
    // ADDED: Capture PDC Details (Safely falls back to empty strings if elements don't exist)
    const pdcBankName = document.getElementById('bankName')?.value || '';
    const pdcCheckNumber = document.getElementById('checkNumber')?.value || '';
    const pdcBankNumber = document.getElementById('bankNumber')?.value || '';
    const pdcCheckDate = document.getElementById('checkDate')?.value || '';
    
    const amountToSettle = parseFloat(document.getElementById('totalDisplay').value) || 0;
    const overpaymentSpan = document.getElementById('computedOverpaymentAmount');
    const rawOverpaymentValue = overpaymentSpan.innerText.replace(/[^\d.]/g, '');
    const finalOverpayment = parseFloat(rawOverpaymentValue) || 0;

    if (selectedCheckboxes.length === 0) {
        alert("Please select at least one payment.");
        return;
    }

    const selectedPayments = Array.from(selectedCheckboxes).map(cb => ({
        // Identifiers for the WHERE clause
        customer_id: cb.dataset.customerId,
        block_number: cb.dataset.block,
        lot_number: cb.dataset.lot,
        due_date: cb.dataset.dueDate,
        
        // Data for update
        id: cb.value,
        base_amount: parseFloat(cb.dataset.amount) || 0,
        penalty: parseFloat(cb.dataset.penalty) || 0,
        is_waived: isWaived,
        overpayment: 0, 
        amount_paid: 0,
        comm_broker: parseFloat(cb.dataset.commBroker) || 0,
        comm_um: parseFloat(cb.dataset.commUm) || 0,
        comm_agent: parseFloat(cb.dataset.commAgent) || 0
    }));

    if (finalOverpayment > 0 && selectedPayments.length > 0) {
        selectedPayments[selectedPayments.length - 1].overpayment = finalOverpayment;
    }

    selectedPayments.forEach(p => {
        const activePenalty = p.is_waived ? 0 : p.penalty;
        p.amount_paid = p.base_amount + activePenalty + p.overpayment;
    });

    const paymentData = {
        payments: selectedPayments,
        ar_number: arNumber, // Sent to PHP
        or_number: orNumber, // Sent to PHP
        total_settle_amount: amountToSettle,
        payment_method: document.getElementById('modeOfPayment').value,
        reference_no: document.getElementById('referenceNumber').value,
        
        // ADDED: Include PDC variables in the data payload
        pdc_bank_name: pdcBankName,
        pdc_check_number: pdcCheckNumber,
        pdc_bank_number: pdcBankNumber,
        pdc_check_date: pdcCheckDate,

        release_broker: document.querySelector('input[name="release_comm_broker"]').checked,
        release_um: document.querySelector('input[name="release_comm_um"]').checked,
        release_agent: document.querySelector('input[name="release_comm_agent"]').checked
    };

    btn.disabled = true;
    btn.innerText = "PROCESSING...";

    try {
        const response = await fetch('/cattleya/user/encoder/fetch/process-payment', { 
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(paymentData)
        });

        const text = await response.text();
        
        try {
            const result = JSON.parse(text);
            if (result.success) {
                // ADDED: Redirect to payment.php if successfully processed
                window.location.href = 'payment.php';
                
                // Reload the parent window
                window.location.reload();
            } else {
                alert('Error: ' + result.message);
                btn.disabled = false;
                btn.innerText = "Confirm Payment";
            }
        } catch (jsonError) {
            console.error('Server Error:', text);
            alert('Server Error: Check console for details.');
            btn.disabled = false;
            btn.innerText = "Confirm Payment";
        }

    } catch (error) {
        console.error('Network Error:', error);
        alert('Could not reach the server.');
        btn.disabled = false;
        btn.innerText = "Confirm Payment";
    }
}
    </script>
</body>
</html>