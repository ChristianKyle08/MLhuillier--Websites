<?php
session_start();
include('../../config/config.php');

// Redirect if not logged in
if (!isset($_SESSION['user_name']) && !isset($_SESSION['admin_name'])) {
    header('Location: login_form.php');
    exit();
}

/* ============================================================
 * ROLE & SESSION SETUP
 * ============================================================ */
$mainzone = $_SESSION['mainzone'] ?? '';
$user_role      = $_SESSION['role'] ?? '';
$is_vpo_checker = (strcasecmp(trim($user_role), 'Vpo-Checker') === 0);

/* ============================================================
 * FILTER INPUT (Matches the report page logic)
 * ============================================================ */
// Mainzone is strictly locked to the logged-in user's assigned Mainzone
$f_mainzone = $mainzone;

// Retrieve GET parameters sent by the export URL
$f_region = trim($_GET['region'] ?? '');
$f_area   = trim($_GET['area'] ?? '');
$f_branch = trim($_GET['branch'] ?? '');
$sort_dir = (strtoupper($_GET['sort'] ?? 'ASC') === 'DESC') ? 'DESC' : 'ASC';

// Base restriction: Unpaid status and up to the current date
$where  = ["status = 'Unpaid'", "transaction_date <= CURDATE()"];
$params = [];
$types  = '';
$ready  = false;

// The user must have manually selected at least one sub-filter to proceed
if ($f_region !== '' || $f_area !== '' || $f_branch !== '') {
    $ready = true;
    
    // Bind parameters dynamically based on what was selected
    if ($f_mainzone !== '') { 
        $where[] = 'mainzone = ?'; 
        $params[] = $f_mainzone; 
        $types .= 's'; 
    }
    if ($f_region !== '') { 
        $where[] = 'region = ?';   
        $params[] = $f_region;   
        $types .= 's'; 
    }
    if ($f_area !== '') { 
        $where[] = 'area = ?';     
        $params[] = $f_area;     
        $types .= 's'; 
    }
    if ($f_branch !== '') { 
        $where[] = 'branch = ?';   
        $params[] = $f_branch;   
        $types .= 's'; 
    }
}

// Block export if no manual filter is selected
if (!$ready) {
    http_response_code(400);
    echo '<div style="font-family:sans-serif; text-align:center; margin-top:50px;">';
    echo '<h2 style="color:#d70c0c;">Export Blocked</h2>';
    echo '<p>Please select at least one filter (Region, Area, or Branch) before exporting.</p>';
    echo '<a href="javascript:history.back()">Go Back</a>';
    echo '</div>';
    exit();
}

/* ============================================================
 * DATA RETRIEVAL & EXPORT
 * ============================================================ */
$where_sql = implode(' AND ', $where);
// ADDED: extract_request_status included in the SELECT query
$sql = "SELECT transaction_date, contract_number, mainzone, region, area, branch,
               lessor_type, corporate_name, l1_firstname, l1_middlename, l1_lastname,
               amount, net_of_vat, vat_amount, wtax, total_month_rental, amount_lessor, edit_amount_lessor,
               mode_of_payment, wallet_number, payment_due_date, status, extract_request_status
        FROM transactional
        WHERE $where_sql
        ORDER BY transaction_date $sort_dir";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$filename = 'unpaid_transactions_' . date('Ymd_His') . '.xls';
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// UTF-8 BOM ensures Excel renders peso signs (₱) and special characters (ñ) correctly
echo "\xEF\xBB\xBF";

// Output Formatting Helpers
function xesc($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
function xnum($v) {
    return number_format((float)$v, 2);
}

// Generate Excel Table
echo '<table border="1" cellpadding="4" cellspacing="0">';
// ADDED: <th>Extract Request Status</th> header
echo '<tr style="background:#12594C;color:#ffffff;font-weight:bold;">'
   . '<th>Transaction Date</th><th>Contract #</th><th>Main Zone</th><th>Region</th><th>Area</th><th>Branch</th>'
   . '<th>Lessor Type</th><th>Lessor / Payee</th>'
   . '<th>Amount</th><th>Net of VAT</th><th>VAT Amount</th><th>W/Tax</th><th>Payable to Lessor</th>'
   . '<th>Mode of Payment</th><th>Status</th><th>Extract Request Status</th>'
   . '</tr>';

while ($row = $result->fetch_assoc()) {
    // Resolve lessor name
    $lessor = !empty($row['corporate_name'])
        ? $row['corporate_name']
        : trim(($row['l1_firstname'] ?? '') . ' ' . ($row['l1_middlename'] ?? '') . ' ' . ($row['l1_lastname'] ?? ''));

    // ADDED: Logic for Extract Status text and inline CSS color for Excel
    $extractStatusText = empty($row['extract_request_status']) ? 'Pending for Extraction' : $row['extract_request_status'];
    $extractStatusStyle = (strcasecmp($extractStatusText, 'Extracted') === 0) 
        ? 'background-color:#198754; color:#ffffff; font-weight:bold;' 
        : '';

    echo '<tr>'
       . '<td>' . xesc($row['transaction_date']) . '</td>'
       . '<td>' . xesc($row['contract_number']) . '</td>'
       . '<td>' . xesc($row['mainzone']) . '</td>'
       . '<td>' . xesc($row['region']) . '</td>'
       . '<td>' . xesc($row['area']) . '</td>'
       . '<td>' . xesc($row['branch']) . '</td>'
       . '<td>' . xesc($row['lessor_type']) . '</td>'
       . '<td>' . xesc($lessor) . '</td>'
       . '<td>' . xnum($row['amount']) . '</td>'
       . '<td>' . xnum($row['net_of_vat']) . '</td>'
       . '<td>' . xnum($row['vat_amount']) . '</td>'
       . '<td>' . xnum($row['wtax']) . '</td>'
       . '<td>' . xnum($row['edit_amount_lessor']) . '</td>'
       . '<td>' . xesc($row['mode_of_payment']) . '</td>'
       . '<td>' . xesc($row['status']) . '</td>'
       . '<td style="' . $extractStatusStyle . '">' . xesc($extractStatusText) . '</td>' // ADDED: Status Output
       . '</tr>';
}
echo '</table>';
$stmt->close();
?>