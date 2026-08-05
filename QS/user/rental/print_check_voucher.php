<?php
require '../../config/config.php';

$contract      = $_GET['contract'] ?? '';
$date          = $_GET['date'] ?? '';
$branch        = $_GET['branch'] ?? '';
$region        = $_GET['region'] ?? '';
$amount_lessor = $_GET['amount_lessor'] ?? '';
$rfp_number    = $_GET['rfp_number'] ?? '';

// Format transaction date safely (match DB format)
$transaction_date = $date ? date('Y-m-d', strtotime($date)) : '';

// Get contract details
$stmt = $conn->prepare("SELECT * FROM create_contract WHERE contract_number = ?");
$stmt->bind_param("s", $contract);
$stmt->execute();
$data_contract = $stmt->get_result()->fetch_assoc();
$rfp_number = $data_contract['rfp_number'] ?? ''; // get rfp_number from create_contract table
// Get check details based on contract_number AND transaction_date
$stmt2 = $conn->prepare("
    SELECT 
        check_number, check_date, branch, region, amount, vat_amount, wtax, contract_start, contract_end,
        bank_name, bank_accNumber, pdc_prepared_by,

        l1_firstname, l1_middlename, l1_lastname, l1_gender,
        new_l1_firstname, new_l1_middlename, new_l1_lastname, new_l1_gender,

        l2_firstname, l2_middlename, l2_lastname, l2_gender,
        new_l2_firstname, new_l2_middlename, new_l2_lastname, new_l2_gender,

        authorize_firstName, authorize_middleName, authorize_lastName, authorize_gender, authorize_mobileNumber,
        new_authorize_firstname, new_authorize_middlename, new_authorize_lastname, new_authorize_gender, new_authorize_mobileNumber

    FROM transactional 
    WHERE contract_number = ? AND transaction_date = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmt2->bind_param("ss", $contract, $transaction_date);
$stmt2->execute();
$data_trans = $stmt2->get_result()->fetch_assoc();

$l1_firstname  = $data_trans['l1_firstname'] ?? '';
$l1_middlename = $data_trans['l1_middlename'] ?? '';
$l1_lastname   = $data_trans['l1_lastname'] ?? '';

$new_l1_firstname  = $data_trans['new_l1_firstname'] ?? '';
$new_l1_middlename = $data_trans['new_l1_middlename'] ?? '';
$new_l1_lastname   = $data_trans['new_l1_lastname'] ?? '';

$l2_firstname  = $data_trans['l2_firstname'] ?? '';
$l2_middlename = $data_trans['l2_middlename'] ?? '';
$l2_lastname   = $data_trans['l2_lastname'] ?? '';

$new_l2_firstname  = $data_trans['new_l2_firstname'] ?? '';
$new_l2_middlename = $data_trans['new_l2_middlename'] ?? '';
$new_l2_lastname   = $data_trans['new_l2_lastname'] ?? '';

$authorize_firstname  = $data_trans['authorize_firstName'] ?? '';
$authorize_middlename = $data_trans['authorize_middleName'] ?? '';
$authorize_lastname   = $data_trans['authorize_lastName'] ?? '';

$new_authorize_firstname  = $data_trans['new_authorize_firstname'] ?? '';
$new_authorize_middlename = $data_trans['new_authorize_middlename'] ?? '';
$new_authorize_lastname   = $data_trans['new_authorize_lastname'] ?? '';

$contract_start = isset($data_trans['contract_start']) 
    ? date('F d, Y', strtotime($data_trans['contract_start'])) 
    : '';

$contract_end = isset($data_trans['contract_end']) 
    ? date('F d, Y', strtotime($data_trans['contract_end'])) 
    : '';
/* =========================================================
   NAME DISPLAY LOGIC (INSERTED HERE)
   ========================================================= */

function buildFullName($new_first, $new_middle, $new_last, $first, $middle, $last) {
    $firstname  = $new_first  ?: $first;
    $middlename = $new_middle ?: $middle;
    $lastname   = $new_last   ?: $last;

    return trim("$firstname $middlename $lastname");
}

$l1_full = buildFullName(
    $new_l1_firstname,
    $new_l1_middlename,
    $new_l1_lastname,
    $l1_firstname,
    $l1_middlename,
    $l1_lastname
);

$l2_full = buildFullName(
    $new_l2_firstname,
    $new_l2_middlename,
    $new_l2_lastname,
    $l2_firstname,
    $l2_middlename,
    $l2_lastname
);

$authorize_full = buildFullName(
    $new_authorize_firstname,
    $new_authorize_middlename,
    $new_authorize_lastname,
    $authorize_firstname,
    $authorize_middlename,
    $authorize_lastname
);

// If authorize exists → show ONLY authorize
if (!empty($authorize_full)) {
    $final_name = $authorize_full;
} else {
    // Else show L1 and L2 (if not empty)
    $names = array_filter([$l1_full, $l2_full]);
    $final_name = implode(', ', $names);
}

/* ========================================================= */

// Use DB values if available, fallback to GET
$bank_name     = $data_trans['bank_name'] ?? $bank_name;
$bank_accNumber = $data_trans['bank_accNumber'] ?? $bank_accNumber;
$check_number  = $data_trans['check_number'] ?? '__________';
$check_date    = $data_trans['check_date'] ?? '__________';
$branch        = $data_trans['branch'] ?? $branch;
$region        = $data_trans['region'] ?? $region;
$amount_lessor = $data_trans['amount'] ?? $amount_lessor;
$amount        = $data_trans['amount'] ?? $amount;
$vat          = $data_trans['vat_amount'] ?? $vat;
$wtax          = $data_trans['wtax'] ?? $wtax;
$pdc_prepared_by = $data_trans['pdc_prepared_by'] ?? $pdc_prepared_by;
$current_date = date('F d, Y');

// ----------------------------
// FUNCTION TO CONVERT NUMBER TO WORDS
// ----------------------------
function numberToWords($number) {
    $ones = [
        0 => '', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four',
        5 => 'five', 6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine',
        10 => 'ten', 11 => 'eleven', 12 => 'twelve', 13 => 'thirteen',
        14 => 'fourteen', 15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen',
        18 => 'eighteen', 19 => 'nineteen'
    ];

    $tens = [
        2 => 'twenty', 3 => 'thirty', 4 => 'forty', 5 => 'fifty',
        6 => 'sixty', 7 => 'seventy', 8 => 'eighty', 9 => 'ninety'
    ];

    if ($number < 20) return $ones[$number];
    if ($number < 100) return $tens[floor($number/10)] . ($number%10 ? '-' . $ones[$number%10] : '');
    if ($number < 1000) return $ones[floor($number/100)] . ' hundred' . ($number%100 ? ' ' . numberToWords($number%100) : '');
    if ($number < 1000000) return numberToWords(floor($number/1000)) . ' thousand' . ($number%1000 ? ' ' . numberToWords($number%1000) : '');
    if ($number < 1000000000) return numberToWords(floor($number/1000000)) . ' million' . ($number%1000000 ? ' ' . numberToWords($number%1000000) : '');
    return '';
}

// ----------------------------
// PREPARE AMOUNT IN WORDS
// ----------------------------
$amount_numeric = floatval(str_replace(',', '', $amount_lessor));
$whole = floor($amount_numeric);
$decimal = round(($amount_numeric - $whole) * 100);
$amount_words = ucfirst(numberToWords($whole)) . ' pesos' . 
                ($decimal > 0 ? ' and ' . $decimal . '/100' : '') . ' only';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Check Voucher</title>
<link href="../../assets/css/poppins.css" rel="stylesheet">
</head>
<style>

/* Page Setup */
@page {
    size: A4;
    margin: 0; /* Remove default browser margins */
}

/* Screen Styles */
body {
    margin: 0;
    font-size: 13px;
    font-family: 'Poppins', Arial, sans-serif;
    background: #f2f2f2;
}

/* Print Button */
.print-button-wrapper {
    text-align: center;
    margin: 20px 0;
}

.print-button-wrapper button {
    padding: 8px 20px;
    font-size: 12px;
    cursor: pointer;
}

/* A4 Paper Layout */
.paper {
    width: 210mm;
    min-height: 297mm;
    margin: 0 auto;
    background: #fff;
    padding: 15mm;
    box-sizing: border-box;
}

/* Voucher Content */
.voucher {
    width: 100%;
}

/* Top Right Numbers */
.top-right {
    text-align: right;
    font-weight: 400;
    margin-top: 12px;
    margin-bottom: 25px;
    line-height: 15px;
    margin-right: 110px;
}

/* Header Row */
.header-row{
    display: flex;
    justify-content: space-between;
    font-weight: 400;
    margin-bottom: 15px;
}
.section1{
    display: flex;
    justify-content: space-between;
    font-weight: 400;
    margin-bottom: 12px;
}
.amount_details {
    display: flex;
    justify-content: space-between;
    font-weight: 400;
    margin-bottom: 10px;
}

.section2 { margin-bottom: 10px; }
.section3 {
    display: flex;
    justify-content: space-between;
    font-weight: 400;
    margin-bottom: 57px;
 }

.section4 { margin-bottom: 35px; }
.section5 { margin-bottom: 55px; }
.section6{
    margin-bottom: 85px;
}
.section7{
    display: flex;
    justify-content: space-between;
    margin-bottom: 45px;
}
.section8{
    margin-bottom: 57px;
}
.section9{
    display: flex;
    justify-content: right;
    margin-left: 15px;
    margin-bottom: 12px;
}
.section10{
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}
.section11{
    margin-left: 55px;
}
.amount { margin-right: 28px; }

.section {
    margin-bottom: 15px;
}

/* PRINT STYLES */
@media print {

    /* Remove background */
    body {
        background: #fff;
        margin: 0;
    }

    /* Hide buttons */
    .no-print,
    .print-button-wrapper {
        display: none !important;
    }

    /* Remove paper spacing */
    .paper {
        margin: 0;
        padding: 10mm;
        width: 100%;
        min-height: auto;
        box-shadow: none;
    }
}

</style>
<body>

<div class="no-print print-button-wrapper">
    <button onclick="window.print()">Print Voucher</button>
</div>

<div class="paper">

    <div class="voucher">

        <div class="top-right">
            <div>5000461288</div>
            <div>530158</div>
        </div>

        <div class="header-row">
            <span style="margin-left: 18px;"><?= htmlspecialchars($current_date) ?></span>
            <span style="margin-left: 235px">Finance Cebu</span>
            <span style="margin-right:25px;">
            <?= !empty($transaction_date) 
                ? date('m/d/Y', strtotime($transaction_date)) 
                : ''; ?>
            </span>
        </div>

        <div class="section1 fw-semibold">
            <span class="payee" style="margin-left: 25px;">
                <?= htmlspecialchars($final_name) ?>
            </span>

            <span class="amount">
                <?= number_format($amount_numeric, 2) ?>
            </span>
        </div>

        <div class="section2 fw-semibold">
            <span class="amount_words" style="margin-left: 100px;">
                <?= htmlspecialchars($amount_words) ?>
            </span>
        </div>

        <div class="section3">
            <span style="margin-left: 45px;"><?= htmlspecialchars($bank_name) ?></span>
            <span style="margin-left: 215px"><?= htmlspecialchars($bank_accNumber) ?></span>
            <span style="margin-right: 30px; font-weight: semibold;">
                <?= htmlspecialchars($check_number) ?>
            </span>
        </div>

        <div class="section4">
            <div style="margin-left: 25px; font-weight: semibold; display: flex; justify-content: space-between;">
                <span>Contract Number: <?= htmlspecialchars($contract) ?></span><span style="margin-right: 30px;"><?= number_format($amount, 2) ?></span>
            </div>
            <span class="payee" style="margin-left: 25px; font-weight: semibold;">
                <?= htmlspecialchars($branch) . ', ' . htmlspecialchars($region) ?> RENTAL FOR
                
            </span>
            <div style="margin-left: 25px; font-weight: semibold;">
                <?= htmlspecialchars($contract_start) . ' TO ' . htmlspecialchars($contract_end) ?>
            </div>
        </div>

        <div class="section5">
            <div class="amount_details" style="margin-left: 25px; font-weight: semibold;">
                <span>AMOUNT</span><span style='margin-right: 30px;'><?= number_format($amount, 2) ?></span>
            </div>
            <div class="amount_details" style="margin-left: 25px; font-weight: semibold;">
                <span>VAT</span><span style='margin-right: 30px;'><?= number_format($vat, 2) ?></span>
            </div>
            <div class="amount_details" style="margin-left: 25px; font-weight: semibold;">
                <span>WTAX</span><span style='margin-right: 30px;'><?= number_format($wtax, 2) ?></span>
            </div>
            <div class="amount_details" style="margin-left: 25px; font-weight: semibold;">
                <span>TOTAL</span><span style='margin-right: 30px;'><?= number_format($amount_lessor, 2) ?></span>
            </div>
        </div>
        <div class="section6">
            <div class="rfp_number" style="margin-left: 25px; font-weight: semibold;">
                <span>RFP#<?= htmlspecialchars($rfp_number) ?></span>
            </div>
        </div>
        <div class="section7">
            <div class="prepared_by" style="margin-left: 15px; font-weight: semibold;">
                <span><?= htmlspecialchars($pdc_prepared_by) ?></span>
                <div class="prepared_des" style="margin-top:10px; font-weight: semibold;">
                    <span>Finance Division Staff</span>
                </div>
            </div>
            <div class="noted_by" style="margin-right: 30px; font-weight: semibold;">
                <span>SHEILA LYNN CORDERO</span>
                <div class="noted_des" style="margin-top:10px; font-weight: semibold;">
                    <span>Asst. Chief Finance Officer</span>
                </div>
            </div>
        </div>
        <div class="section8">
            <div class="approved_by" style="font-weight: semibold;">
                <span>MICHEL & OR MICHAEL JAMES LHUILLIER</span>
                <div class="prepared_des" style="margin-top:10px; font-weight: semibold;">
                    <span>Chairman/President & CEO/CFO</span>
                </div>
            </div>
        </div>
        <div class="section9">
            <div class="due_date" style="font-weight: semibold; margin-right: 3px;">
                <span style="letter-spacing: 10px;">
                    <?= htmlspecialchars(date('m d  Y', strtotime($transaction_date))); ?>
                </span>
            </div>
        </div>
        <div class="section10">
            <span class="amount_words" style="margin-left: 70px;">
                <?= htmlspecialchars($final_name) ?>
            </span>
            <div style="margin-right: 120px;">
                <?= number_format($amount_numeric, 2) ?>
            </div>
        </div>
        <div class="section11">
            <div style="margin-right: 100px;">
                <?= htmlspecialchars($amount_words) ?>
            </div>
        </div>
    </div>

</div>

<script>
window.onload = function() {
    window.print();
    window.onafterprint = function() {
        window.close();
    };
};
</script>

</body>
</html>