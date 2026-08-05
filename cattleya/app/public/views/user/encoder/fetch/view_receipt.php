<?php
require_once __DIR__ . '/../../../../../config/database.php'; // Update path as needed

$payment_id = $_GET['id'] ?? null;
$receipt_type = $_GET['type'] ?? null; // Fetch the type (OR or AR)

if (!$payment_id || !$receipt_type) {
    die("Invalid Request. Payment ID and Receipt Type are required.");
}

// Fetch Payment and Sales Data
$stmt = $pdo->prepare("
    SELECT p.*, s.customer_fullname, s.product_name, s.niche_type, s.tcp, s.payment_method as sales_payment_method
    FROM payments p
    JOIN sales s ON p.sale_id = s.sale_id
    WHERE p.id = ?
");
$stmt->execute([$payment_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Receipt data not found.");
}

// Prepare Data Variables
$customer_name = strtoupper($data['customer_fullname'] ?? 'EGGERT M. BUSTAMANTE');
$block = htmlspecialchars($data['block_number'] ?? '33');
$lot = htmlspecialchars($data['lot_number'] ?? 'A15');
$plot_type = htmlspecialchars($data['product_name'] ?? 'Lawn Lots');
$date = date('Y-m-d', strtotime($data['payment_date'] ?? $data['created_at'] ?? 'now'));
$or_number = htmlspecialchars($data['or_number'] ?? '123');
$ar_number = htmlspecialchars($data['ar_number'] ?? '123');
$amount_paid = (float)($data['amount_paid'] ?? 83.35);

// Standard Philippine VAT Calculation
$vat_rate = 0.12;
$vatable_sales = $amount_paid / (1 + $vat_rate);
$vat_amount = $amount_paid - $vatable_sales;
$total_due = $amount_paid;

// Payment Method Processing
$payment_method = $data['payment_method'] ?? $data['sales_payment_method'] ?? 'Cash';
$pm_lower = strtolower(trim($payment_method));

$is_cash = ($pm_lower === 'cash');
$is_pdc = ($pm_lower === 'pdc');
$is_bills = ($pm_lower === 'billspayment' || $pm_lower === 'bills payment');

// Fill-in check details if PDC
$bank_name = $is_pdc ? htmlspecialchars($data['pdc_bank_name'] ?? '') : '';
$check_number = $is_pdc ? htmlspecialchars($data['pdc_check_number'] ?? '') : '';
$check_date = $is_pdc ? htmlspecialchars($data['pdc_check_date'] ?? '') : '';

// Added Due Date Variable
$due_date = isset($data['due_date']) && !empty($data['due_date']) ? date('Y-m-d', strtotime($data['due_date'])) : 'N/A';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($receipt_type) ?> - <?= htmlspecialchars($receipt_type === 'OR' ? $or_number : $ar_number) ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f3f4f6;
        }
        .receipt-bg { background-color: #cdd2d5; }
        .border-black { border-color: #1a1a1a; }
        .text-blue-data { color: #1e3a8a; font-weight: 600; }
        .text-red-data { color: #dc2626; font-weight: 700; }
        
        table { border-collapse: collapse; }
        td, th { border: 1px solid #1a1a1a; padding: 2px 4px; }
        
        .logo-text {
            font-family: 'Brush Script MT', cursive;
            font-size: 2.5rem;
            line-height: 1;
            color: #0c4a6e;
            position: relative;
        }
        .logo-text span { color: #65a30d; font-size: 1.5rem; font-family: 'Arial', sans-serif; position: absolute; bottom: 0; right: -10px;}
        .logo-sub { font-size: 0.4rem; color: #4b5563; }
        
        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 0; }
            /* Force background colors to print */
            .receipt-bg { 
                background-color: #cdd2d5 !important; 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact; 
            }
        }
    </style>
</head>
<body class="p-4 md:p-8 flex justify-center">

    <div class="max-w-[750px] w-full">

        <?php if ($receipt_type === 'OR'): ?>
            <div class="flex gap-2 mb-2 no-print">
                <button onclick="window.print()" class="bg-[#b32d2e] text-white px-3 py-1.5 rounded-sm text-xs font-bold flex items-center gap-1.5 hover:bg-red-800 transition-colors shadow-sm">
                    <i class="fa-solid fa-print"></i> Print OR
                </button>
                <button onclick="downloadPDF('printable-receipt', 'OR-<?= $or_number ?>')" class="bg-[#b32d2e] text-white px-3 py-1.5 rounded-sm text-xs font-bold flex items-center gap-1.5 hover:bg-red-800 transition-colors shadow-sm">
                    <i class="fa-solid fa-download"></i> Download PDF
                </button>
            </div>

            <div id="printable-receipt" class="receipt-bg border-2 border-gray-400 p-6 shadow-sm flex flex-col h-[700px]">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-2 w-[45%]">
                        <div class="text-center shrink-0">
                            <div class="logo-text">Cattleya<span>&hearts;</span></div>
                            <div class="logo-sub tracking-widest mt-1">gardens & memorial park</div>
                        </div>
                        <div class="text-[9px] leading-tight text-black ml-2 font-medium">
                            <p class="font-bold text-[11px]">Cattleya Gardens & Memorial Park, Inc.</p>
                            <p>Dumaguete Dev. Bank Bldg., Hi-way, Pusok, Lapu-Lapu City</p>
                            <p>Tel. No. (032) 495-2644</p>
                            <p>Vat Reg. TIN: 418-924-882-000</p>
                        </div>
                    </div>

                    <div class="w-[50%] flex flex-col text-[10px]">
                        <p class="mb-2">THIS OFFICIAL RECEIPT SHALL BE VALID FOR FIVE (5) YEARS FROM THE DATE OF ATP</p>
                        <div class="flex items-end justify-between mb-2">
                            <div class="flex items-end gap-1">
                                <span class="font-bold">Date:</span>
                                <span class="text-blue-data border-b border-black w-24 text-center pb-0.5"><?= $date ?></span>
                            </div>
                            <div class="font-bold text-sm">
                                NO. <span class="text-red-data text-lg"><?= $or_number ?></span>
                            </div>
                        </div>
                        <div class="border border-black px-2 py-1 inline-block w-max self-end font-bold text-[11px]">
                            OFFICIAL RECEIPT
                        </div>
                    </div>
                </div>

                <table class="w-full text-[9px] mb-2">
                    <tr class="bg-transparent font-bold text-black uppercase">
                        <td class="w-[50%]">CUSTOMER NAME:</td>
                        <td class="w-[25%]">TIN:</td>
                        <td class="w-[25%]">ADDRESS:</td>
                    </tr>
                    <tr>
                        <td class="text-blue-data uppercase py-1.5"><?= $customer_name ?></td>
                        <td>-</td>
                        <td>-</td>
                    </tr>
                </table>

                <div class="flex border border-black border-b-0 text-[9px] flex-grow">
                    <div class="w-[60%] flex flex-col border-r border-black">
                        <table class="w-full border-none">
                            <tr class="font-bold">
                                <td class="border-t-0 border-l-0 w-1/3">BUSINESS STYLE</td>
                                <td class="border-t-0 w-1/3">OSCA/PWD NO.</td>
                                <td class="border-t-0 border-r-0 w-1/3">SIGNATURE</td>
                            </tr>
                            <tr>
                                <td class="border-l-0 text-blue-data">Product Type: <?= $plot_type ?></td>
                                <td>-</td>
                                <td class="border-r-0">-</td>
                            </tr>
                            <tr>
                                <td class="border-l-0 text-blue-data">Block Number: <?= $block ?></td>
                                <td>-</td>
                                <td class="border-r-0">-</td>
                            </tr>
                            <tr>
                                <td class="border-l-0 text-blue-data">Plot Number: <?= $lot ?></td>
                                <td>-</td>
                                <td class="border-r-0">-</td>
                            </tr>
                        </table>

                        <table class="w-full border-none">
                            <tr class="font-bold">
                                <td class="border-l-0 border-t-0">IN PAYMENT OF THE FOLLOWING<br>SERVICE/TRANSACTION/DESCRIPTION</td>
                                <td class="border-t-0 w-10 text-center">QTY.</td>
                                <td class="border-t-0 w-20 text-center">UNIT PRICE</td>
                                <td class="border-t-0 border-r-0 w-20 text-center">AMOUNT P</td>
                            </tr>
                            <tr class="h-6">
                                <td class="border-l-0 text-blue-data font-bold">Payment for Plot <span class="text-black font-normal text-[8px] ml-1">(Due: <?= !empty($due_date) ? date('F d, Y', strtotime($due_date)) : '' ?>)</span></td>
                                <td class="text-center text-blue-data">1</td>
                                <td class="text-blue-data text-right">₱ <?= number_format($amount_paid, 2) ?></td>
                                <td class="border-r-0 text-blue-data text-right">₱ <?= number_format($amount_paid, 2) ?></td>
                            </tr>
                            <tr class="h-6">
                                <td class="border-l-0 text-blue-data">-</td>
                                <td class="text-center">-</td>
                                <td class="text-right">-</td>
                                <td class="border-r-0 text-right">-</td>
                            </tr>
                            <tr class="h-6">
                                <td class="border-l-0">-</td>
                                <td class="text-center">-</td>
                                <td class="text-right">-</td>
                                <td class="border-r-0 text-right">-</td>
                            </tr>
                            <tr class="h-auto align-top flex-grow">
                                <td class="border-l-0 border-b-0 text-blue-data"></td>
                                <td class="text-center border-b-0"></td>
                                <td class="border-b-0 text-right"></td>
                                <td class="border-r-0 border-b-0 text-right"></td>
                            </tr>
                        </table>
                    </div>

                    <div class="w-[40%] flex flex-col">
                        <table class="w-full border-none">
                            <tr>
                                <td class="border-t-0 border-l-0 w-[60%]">TOTAL SALES</td>
                                <td class="border-t-0 border-r-0 text-blue-data font-bold text-right">₱ <?= number_format($total_due, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="border-l-0">LESS: 12% VAT</td>
                                <td class="border-r-0 text-blue-data text-right">₱ <?= number_format($vat_amount, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="border-l-0">NET OF VAT</td>
                                <td class="border-r-0 text-blue-data font-bold text-right">₱ <?= number_format($vatable_sales, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="border-l-0">LESS: SC/PWD DISC.</td>
                                <td class="border-r-0 text-right">-</td>
                            </tr>
                            <tr>
                                <td class="border-l-0">TOTAL DUE</td>
                                <td class="border-r-0 text-blue-data font-bold text-right">₱ <?= number_format($total_due, 2) ?></td>
                            </tr>
                            <tr>
                                <td class="border-l-0">LESS: WITHHOLDING</td>
                                <td class="border-r-0 text-right">-</td>
                            </tr>
                            <tr>
                                <td class="border-l-0 font-bold">TOTAL AMOUNT DUE</td>
                                <td class="border-r-0 text-blue-data font-bold text-right">₱ <?= number_format($total_due, 2) ?></td>
                            </tr>
                        </table>
                        
                        <table class="w-full border-none">
                            <tr class="align-top">
                                <td class="border-l-0 border-b-0 w-[60%] pb-8 leading-[1.6]">
                                    VATABLE (V)<br>
                                    VAT EXEMPT (E)<br>
                                    ZERO-RATED (Z)<br>
                                    VAT (12%)<br>
                                    <span class="font-bold">TOTAL</span>
                                </td>
                                <td class="border-r-0 border-b-0 text-blue-data text-right pr-2 leading-[1.6]">
                                    <?= number_format($vatable_sales, 2) ?><br>
                                    0.00<br>
                                    0.00<br>
                                    <?= number_format($vat_amount, 2) ?><br>
                                    <span class="font-bold"><?= number_format($total_due, 2) ?></span>
                                </td>
                            </tr>
                        </table>

                        <div class="border-t border-black p-2 flex flex-col items-center justify-between">
                            <div class="text-center w-full">
                                <p class="mb-1 font-bold">FORM OF PAYMENT</p>
                                <div class="flex justify-center gap-3 items-center mb-1">
                                    <label class="flex items-center gap-1"><input type="checkbox" <?= $is_cash ? 'checked' : '' ?> class="w-3 h-3 <?= $is_cash ? 'bg-blue-500' : '' ?>"> CASH</label>
                                    <label class="flex items-center gap-1"><input type="checkbox" <?= $is_pdc ? 'checked' : '' ?> class="w-3 h-3 <?= $is_pdc ? 'bg-blue-500' : '' ?>"> PDC</label>
                                    <label class="flex items-center gap-1"><input type="checkbox" <?= $is_bills ? 'checked' : '' ?> class="w-3 h-3 <?= $is_bills ? 'bg-blue-500' : '' ?>"> BILLS PAYMENT</label>
                                </div>
                                <p class="text-blue-data mb-4">GMO / RBR / ACT</p>
                            </div>
                            <div class="w-full text-center border-t border-black pt-1 font-bold">
                                CASHIER/AUTHORIZED PERSON
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php elseif ($receipt_type === 'AR'): ?>
            <div class="flex gap-2 mb-2 justify-end no-print">
                <button onclick="downloadPDF('printable-receipt', 'AR-<?= $ar_number ?>')" class="bg-[#b32d2e] text-white px-3 py-1.5 rounded-sm text-xs font-bold flex items-center gap-1.5 hover:bg-red-800 transition-colors shadow-sm">
                    <i class="fa-solid fa-download"></i> Download PDF
                </button>
                <button onclick="window.print()" class="bg-[#b32d2e] text-white px-3 py-1.5 rounded-sm text-xs font-bold flex items-center gap-1.5 hover:bg-red-800 transition-colors shadow-sm">
                    <i class="fa-solid fa-print"></i> Print AR
                </button>
            </div>

            <div id="printable-receipt" class="receipt-bg border-2 border-gray-400 p-6 shadow-sm flex flex-col h-[700px]">
                <div class="flex justify-between items-start mb-6">
                    <div class="text-center shrink-0">
                        <div class="logo-text mt-4">Cattleya<span>&hearts;</span></div>
                        <div class="logo-sub tracking-widest mt-1">gardens & memorial park</div>
                    </div>
                    <div class="text-[10px] leading-tight text-right text-black font-medium">
                        <p class="font-bold text-sm">Cattleya Gardens and Memorial Park Inc.,</p>
                        <p>Barangay San Miguel Cordova, Cebu</p>
                        <p>Contact Numbers: 0943-837-2824 / 0917-304-9490</p>
                        <p>Owned & Operated by: Cattleya Gardens and Memorial Park Inc.</p>
                        <p>Tin# 418-924-882-000 VAT</p>
                        <div class="mt-2 font-bold text-lg text-red-data">
                            <?= $ar_number ?>
                        </div>
                    </div>
                </div>

                <div class="text-[11px] uppercase font-bold mb-3">ACKNOWLEDGEMENT RECEIPT</div>

                <div class="flex justify-between items-end text-[11px] font-bold mb-3">
                    <div class="flex items-end flex-grow">
                        <span class="mr-2">Received from</span>
                        <span class="text-blue-data border-b border-black flex-grow pb-0.5 px-2"><?= $customer_name ?></span>
                    </div>
                    <div class="flex items-end ml-4">
                        <span class="mr-2">Date</span>
                        <span class="text-blue-data border-b border-black w-32 pb-0.5 text-center"><?= $date ?></span>
                    </div>
                </div>

                <div class="flex items-end text-[11px] font-bold mb-4 gap-4">
                    <div class="flex items-end">
                        <span class="mr-2">Block No.</span>
                        <span class="text-blue-data border-b border-black w-24 pb-0.5 text-center"><?= $block ?></span>
                    </div>
                    <div class="flex items-end flex-grow">
                        <span class="mr-2">Plot Number</span>
                        <span class="text-blue-data border-b border-black flex-grow pb-0.5 px-2"><?= $lot ?></span>
                    </div>
                    <div class="flex items-end">
                        <span class="mr-2">Plot Type</span>
                        <span class="text-blue-data border-b border-black w-32 pb-0.5 text-center"><?= $plot_type ?></span>
                    </div>
                </div>

                <table class="w-full text-[10px] flex-grow h-64 border-2 border-black">
                    <tr class="font-bold text-left bg-transparent">
                        <th class="p-2 border-b border-r border-black w-[75%]">Payment Details / Description</th>
                        <th class="p-2 border-b border-black w-[25%] text-center">Amount</th>
                    </tr>
                    <tr class="align-top">
                        <td class="p-2 border-r border-b-0 border-black font-semibold text-blue-data">
                            Payment for Plot 
                            <span class="text-[9px] text-gray-700 font-normal ml-1">
                                (Block <?= $block ?> - Plot <?= $lot ?> | Due: <?= !empty($due_date) ? date('F d, Y', strtotime($due_date)) : '' ?>)
                            </span>
                        </td>
                        <td class="p-2 border-b-0 border-black text-right text-blue-data font-semibold">₱ <?= number_format($amount_paid, 2) ?></td>
                    </tr>
                    <tr class="h-full align-top">
                        <td class="p-2 border-r border-t-0 border-b-0 border-black"></td>
                        <td class="p-2 border-t-0 border-b-0 border-black"></td>
                    </tr>
                    
                    <tr class="border-t border-black">
                        <td class="py-1 px-2 border-r border-black text-right font-semibold">Vatable Sales:</td>
                        <td class="py-1 px-2 border-black text-blue-data text-right">₱ <?= number_format($vatable_sales, 2) ?></td>
                    </tr>
                    <tr>
                        <td class="py-1 px-2 border-r border-black text-right font-semibold">VAT (12%):</td>
                        <td class="py-1 px-2 border-black text-blue-data text-right">₱ <?= number_format($vat_amount, 2) ?></td>
                    </tr>
                    <tr class="font-bold text-base border-t-2 border-black">
                        <td class="p-2 border-r border-black text-right">Total Payment:</td>
                        <td class="p-2 border-black text-blue-data text-right">₱ <?= number_format($amount_paid, 2) ?></td>
                    </tr>
                </table>
                <div class="flex flex-wrap gap-4 justify-center items-center mt-3 mb-6 text-[10px] font-medium">
                    <label class="flex items-center gap-1.5"><input type="checkbox" <?= $is_cash ? 'checked' : '' ?> class="w-3 h-3 bg-white border-black"> CASH</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" <?= $is_pdc ? 'checked' : '' ?> class="w-3 h-3 bg-white border-black"> PDC</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" <?= $is_bills ? 'checked' : '' ?> class="w-3 h-3 bg-white border-black"> Bills Payment</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" class="w-3 h-3 bg-white border-black"> At Need</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" class="w-3 h-3 bg-white border-black"> Internment</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" class="w-3 h-3 bg-white border-black"> Admin/Processing</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" class="w-3 h-3 bg-white border-black"> Others</label>
                </div>

                <div class="flex justify-between items-end mt-auto text-[11px] font-bold">
                    <div class="flex flex-col gap-2 w-[40%]">
                        <div class="flex items-end">
                            <span class="w-24 shrink-0">Bank Name</span>
                            <span class="border-b border-black flex-grow text-blue-data text-center pb-0.5"><?= $bank_name ?></span>
                        </div>
                        <div class="flex items-end">
                            <span class="w-24 shrink-0">Check Number</span>
                            <span class="border-b border-black flex-grow text-blue-data text-center pb-0.5"><?= $check_number ?></span>
                        </div>
                        <div class="flex items-end">
                            <span class="w-24 shrink-0">Date of Check</span>
                            <span class="border-b border-black flex-grow text-blue-data text-center pb-0.5"><?= $check_date ?></span>
                        </div>
                    </div>
                    
                    <div class="w-[40%] text-center pt-8">
                        <div class="text-blue-data mb-1">GMO / RBR / ACT</div>
                        <div class="border-t-2 border-black pt-1">Authorized Signature</div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="text-center p-10 bg-white border border-red-300 rounded shadow-sm">
                <h2 class="text-2xl font-bold text-red-600 mb-2">Error</h2>
                <p class="text-gray-700">Invalid receipt type requested. Please ensure the URL parameter <code>type</code> is either <strong>OR</strong> or <strong>AR</strong>.</p>
            </div>
        <?php endif; ?>

    </div>

    <script>
        function downloadPDF(elementId, filename) {
            const element = document.getElementById(elementId);
            const opt = {
                margin:       0.2,
                filename:     filename + '.pdf',
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>