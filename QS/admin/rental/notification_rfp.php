<?php
ob_start(); // 1. Trap any accidental output from included files
session_start(); // 2. Start session BEFORE including config
include '../../config/config.php';

// ✅ Give the script enough headroom to finish building large notification
// batches instead of getting cut off mid-way, which is what typically shows
// up to the browser as a "Bad Gateway" (the web server gives up waiting on PHP).
if (function_exists('set_time_limit')) {
    set_time_limit(120);
}

$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['admin_name']) || !isset($_SESSION['admin_email'])) {
    header('location:../../admin/rental/login_form.php');
    exit;
}

$adminEmail = $_SESSION['admin_email'];
$isMlhuillierEmail = preg_match('/@mlhuillier\.com$/i', $adminEmail); // ✅ check domain

date_default_timezone_set('Asia/Manila');
$currentDate = date('Y-m-d');

// ✅ Convert current date to the first day of the current month
$currentMonthStart = date('Y-m-01');

// ✅ Compute 5 months ahead (first day of that month)
$fiveMonthsLater = date('Y-m-01', strtotime('+5 months', strtotime($currentMonthStart)));

// ✅ Exclusive upper bound: first day of the month AFTER $fiveMonthsLater.
// Used below instead of wrapping c.end_date in DATE_FORMAT(), because a
// function-wrapped column can't use an index — that mismatch is the single
// biggest reason this query slows down (and can time out) as the contracts
// table grows. This produces the exact same month range as before.
$rangeEndExclusive = date('Y-m-01', strtotime('+1 month', strtotime($fiveMonthsLater)));

// ✅ Fetch expiring contracts within 5 months (month/year only basis)
// Added c.mainzone to the SELECT statement to allow grouping
// 📌 For best performance on large tables, make sure these indexes exist:
//    ALTER TABLE create_contract ADD INDEX idx_branch_enddate (branch, end_date);
//    ALTER TABLE user_form ADD INDEX idx_region_area (region, area);
$query = $conn->prepare("
    SELECT 
        c.id, c.contract_number, c.branch, c.mainzone, c.region, c.area,
        c.contract_start, c.contract_end, c.start_date, c.end_date,
        u.id_number, u.first_name, u.middle_name, u.last_name, u.email
    FROM create_contract c
    INNER JOIN (
        SELECT branch, MAX(end_date) AS latest_end_date
        FROM create_contract
        GROUP BY branch
    ) latest 
        ON c.branch = latest.branch AND c.end_date = latest.latest_end_date
    LEFT JOIN user_form u 
        ON c.region = u.region AND c.area = u.area
    WHERE c.end_date >= ? AND c.end_date < ?
    ORDER BY c.mainzone ASC, c.end_date ASC
");
if ($query === false) {
    die("Query preparation failed: " . htmlspecialchars($conn->error));
}
$query->bind_param("ss", $currentMonthStart, $rangeEndExclusive);
if (!$query->execute()) {
    die("Query execution failed: " . htmlspecialchars($query->error));
}
$result = $query->get_result();
$notifCount = $result->num_rows;

// ✅ Group contracts and emails by Mainzone
$groupedData = [];
$visibleCount = 0; // ✅ counts entries actually shown/notified (VOID entries excluded below)

while ($row = $result->fetch_assoc()) {
    // ✅ Skip voided entries — they must never appear in the listings or notifications
    if (isset($row['contract_number']) && strtoupper(trim($row['contract_number'])) === 'VOID') {
        continue;
    }

    $mainzone = !empty($row['mainzone']) ? $row['mainzone'] : 'Unassigned Zone';
    
    if (!isset($groupedData[$mainzone])) {
        $groupedData[$mainzone] = [
            'emails' => [],
            'emailLookup' => [], // ✅ hash lookup so duplicate checks stay O(1) even with 500+ recipients
            'contracts' => []
        ];
    }
    
    if (!empty($row['email']) && preg_match('/@mlhuillier\.com$/i', trim($row['email']))) {
        $cleanEmail = trim($row['email']);
        // Prevent duplicate emails in the To: field for the same mainzone
        if (!isset($groupedData[$mainzone]['emailLookup'][$cleanEmail])) {
            $groupedData[$mainzone]['emailLookup'][$cleanEmail] = true;
            $groupedData[$mainzone]['emails'][] = $cleanEmail;
        }
    }
    
    $groupedData[$mainzone]['contracts'][] = $row;
    $visibleCount++;
}
$query->close(); // ✅ free the statement/result now that everything needed is in $groupedData

// ✅ Summary stats for the dashboard header
$totalZones = count($groupedData);
$criticalCount = 0;
foreach ($groupedData as $zoneData) {
    foreach ($zoneData['contracts'] as $c) {
        $ml = (date('Y', strtotime($c['end_date'])) - date('Y')) * 12 +
              (date('m', strtotime($c['end_date'])) - date('m'));
        if ($ml <= 2) {
            $criticalCount++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Monthly Rental RFP Notifications</title>
<link rel="icon" href="../../assets/images/ml_logo.png" type="image/png">
<link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
<link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">
<link href="../../assets/css/poppins.css" rel="stylesheet">
<style>
:root {
    --brand: #dc2626;
    --brand-dark: #b91c1c;
    --brand-darker: #991b1b;
    --brand-tint: #fef2f2;
    --ink-900: #1e293b;
    --ink-700: #334155;
    --ink-600: #475569;
    --ink-500: #64748b;
    --border-soft: #e2e8f0;
    --surface: #ffffff;
    --excel-border: #cbd5e1;
    --excel-header: #f1f5f9;
}
body {
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
    font-family: 'Poppins', sans-serif;
    color: var(--ink-700);
}
.page-header {
    background: var(--surface);
    padding: 22px 30px;
    border-radius: 16px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
    margin-bottom: 22px;
    border: 1px solid var(--border-soft);
}
.page-title {
    color: var(--ink-900);
    letter-spacing: -0.01em;
}
.page-subtitle {
    font-size: 13px;
    color: var(--ink-500);
    margin-top: 2px;
}
.stat-card {
    background: var(--surface);
    border: 1px solid var(--border-soft);
    border-radius: 14px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    height: 100%;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    transition: transform .2s ease, box-shadow .2s ease;
}
.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
}
.stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: var(--brand-tint);
    color: var(--brand);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}
.stat-card-critical .stat-icon {
    background: #fffbeb;
    color: #b45309;
}
.stat-value {
    font-size: 22px;
    font-weight: 700;
    color: var(--ink-900);
    line-height: 1.15;
}
.stat-label {
    font-size: 12px;
    color: var(--ink-500);
    font-weight: 500;
}
.security-alert {
    font-size: 13.5px;
    background-color: #fffbeb;
    color: #92400e;
    border-radius: 14px;
}
.zone-card {
    border: 1px solid var(--border-soft);
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(15, 23, 42, 0.04);
    margin-bottom: 26px;
    overflow: hidden;
    background: var(--surface);
    transition: box-shadow .2s ease;
}
.zone-card:hover {
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.07);
}
.zone-card-header {
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    border-bottom: 1px solid var(--border-soft);
    padding: 18px 24px;
}
.zone-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--brand), var(--brand-dark));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 10px rgba(220, 38, 38, 0.25);
}
.zone-title {
    font-size: 1.05rem;
    font-weight: 600;
    color: var(--ink-900);
    margin: 0;
}

/* Modern Excel-like Table Styling */
.table-responsive {
    overflow-x: auto;
    border-bottom-left-radius: 16px;
    border-bottom-right-radius: 16px;
}
.table-modern {
    margin-bottom: 0;
    border-collapse: collapse;
    width: 100%;
}
.table-modern thead {
    background: var(--excel-header);
    position: relative;
    z-index: 1;
}
.table-modern th {
    font-weight: 600;
    font-size: 12.5px;
    padding: 14px 16px;
    border: 1px solid var(--excel-border);
    border-top: none;
    color: var(--ink-900);
    white-space: nowrap;
    text-align: left;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.table-modern td {
    padding: 12px 16px;
    font-size: 13.5px;
    vertical-align: middle;
    border: 1px solid var(--excel-border);
    color: var(--ink-700);
    background-color: #ffffff;
}
/* Subtle alternating row colors */
.table-modern tbody tr:nth-child(even) td {
    background-color: #fcfcfd;
}
.table-modern tbody tr {
    transition: all .15s ease;
}
.table-modern tbody tr:hover td {
    background-color: #f1f5f9; 
}
.table-modern tbody tr:last-child td {
    border-bottom: none;
}
.table-modern th:first-child, .table-modern td:first-child {
    border-left: none;
}
.table-modern th:last-child, .table-modern td:last-child {
    border-right: none;
}

.avatar-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #fecaca, #fca5a5);
    color: #7f1d1d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 11.5px;
    flex-shrink: 0;
}
.back-btn {
    border-radius: 50px;
    border: 1px solid var(--brand);
    transition: all .3s;
    color: var(--brand);
    font-size: 13px;
    font-weight: 500;
    padding: 8px 20px;
    background: var(--surface);
}
.back-btn:hover {
    background: var(--brand);
    color: #fff;
    transform: translateX(-3px);
}
.btn-send-email {
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    padding: 9px 18px;
    background: linear-gradient(135deg, #ef4444, var(--brand-dark));
    border: none;
    box-shadow: 0 4px 10px rgba(220, 38, 38, 0.22);
}
.btn-send-email:hover {
    background: linear-gradient(135deg, var(--brand-dark), var(--brand-darker));
    transform: translateY(-1px);
    box-shadow: 0 6px 14px rgba(220, 38, 38, 0.3);
    color: #fff;
}
.btn-copy-table {
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    padding: 9px 18px;
    background: #ffffff;
    color: var(--ink-700);
    border: 1px solid var(--excel-border);
    transition: all .2s;
    box-shadow: 0 2px 4px rgba(15,23,42,0.02);
}
.btn-copy-table:hover {
    background: #f8fafc;
    color: var(--ink-900);
    border-color: #94a3b8;
}
.btn-copy-table.copied {
    background: #10b981;
    color: white;
    border-color: #10b981;
}

.badge-status {
    padding: 6px 12px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 11.5px;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.empty-state-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: #ecfdf5;
    color: #10b981;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 2rem;
}
.contract-display {
    font-family: 'Consolas', 'Courier New', monospace;
    font-size: 13.5px;
    font-weight: 600;
    color: var(--ink-900);
}
</style>
</head>
<body>

<div class="container my-4">
  <!-- Page Header -->
  <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
    <a href="admin_page.php" class="btn back-btn">
      <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
    </a>
    <div class="text-end">
      <h4 class="m-0 fw-bold page-title"><i class="bi bi-bell-fill text-danger me-2"></i>Monthly Rental RFP Notifications</h4>
      <div class="page-subtitle">RFP(s) nearing depletion in the next 5 months, grouped by mainzone</div>
    </div>
  </div>

  <?php if ($visibleCount > 0): ?>

      <!-- Summary Stats -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
          <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-geo-alt-fill"></i></div>
            <div>
              <div class="stat-value"><?= $totalZones ?></div>
              <div class="stat-label">Zone(s) Involved</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-4">
          <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
            <div>
              <div class="stat-value"><?= $visibleCount ?></div>
              <div class="stat-label">Total RFP(s)</div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="stat-card stat-card-critical">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div>
              <div class="stat-value"><?= $criticalCount ?></div>
              <div class="stat-label">Critical (&le; 2 months)</div>
            </div>
          </div>
        </div>
      </div>

      <div class="alert alert-warning text-center shadow-sm security-alert mb-4 border-0">
        <i class="bi bi-shield-lock-fill me-2 fs-5 align-middle"></i>
        Security Notice: You must use your authorized <strong>@mlhuillier.com</strong> email account to send these official notifications.
      </div>

      <?php 
      // Initialize a lookup (not a plain list) so duplicate checks stay O(1)
      // instead of O(n) — matters once a zone has hundreds of rows.
      $seenContractNumbers = []; 

      // ✅ Gmail's "compose via URL" trick has a real, fairly tight practical limit...
      $maxLinkLength = 3800;
      ?>

     <!-- Loop through each Mainzone -->
      <?php foreach ($groupedData as $mainzone => $data): 
          
          $colWidth    = 20;
          $branchWidth = 40;
          $dateWidth   = 20;
          $totalWidth  = $colWidth + $branchWidth + $dateWidth;

          $uniqueContractsForEmail = [];
          $seenContractIdsForEmail = [];
          foreach ($data['contracts'] as $contract) {
              if (!isset($seenContractIdsForEmail[$contract['id']])) {
                  $seenContractIdsForEmail[$contract['id']] = true;
                  $uniqueContractsForEmail[] = $contract;
              }
          }

          // Construct the professional email body dynamically for this mainzone
          $defaultSubject = "Action Required: Monthly Rental RFP Nearing Depletion - " . $mainzone;

          $messageIntro = "Dear {$mainzone} Team,\r\n\r\n"
              . "This is an official notification regarding Rental Request For Payment (RFP) under your supervision that are nearing depletion within the next 1 - 5 months. Prompt action is required to ensure uninterrupted operations.\r\n\r\n"
              . "Please find below the list of RFP(s) requiring your attention:\r\n\r\n";
          $messageOutro = "\r\n"
              . "Kindly review the item(s) listed above and initiate the necessary steps for renewal, extension, or closure, as applicable. Please ensure that all corresponding documents are updated promptly in the system.\r\n\r\n"
              . "Should you have any questions or require assistance, please coordinate with the Rental Management Team.\r\n\r\n"
              . "Thank you for your immediate attention to this matter.\r\n\r\n"
              . "Best regards,\r\n\r\nML Rental Management Team\r\nMLhuillier Financial Services";

          $tableHeader  = str_repeat("=", $totalWidth) . "\r\n";
          $headerRow    = str_pad(" COL NUMBER", $colWidth) . str_pad(" BRANCH", $branchWidth) . str_pad(" DEPLETION DATE", $dateWidth);
          $tableHeader .= str_replace(" ", "\xC2\xA0", $headerRow) . "\r\n";
          $tableHeader .= str_repeat("=", $totalWidth) . "\r\n";

          $urlPrefix     = "https://mail.google.com/mail/?view=cm&fs=1&to=";
          $subjectParam  = "&su=" . urlencode($defaultSubject);
          $fixedOverhead = strlen($urlPrefix) + strlen($subjectParam) + strlen("&body=")
              + strlen(urlencode($messageIntro)) + strlen(urlencode($messageOutro));
          $sharedBudget  = max(0, $maxLinkLength - $fixedOverhead);

          $contractBudget = (int) round($sharedBudget * 0.6);

          $contractRows   = '';
          $contractsShown = 0;
          $runningLen     = strlen(urlencode($tableHeader));
          
          foreach ($uniqueContractsForEmail as $contract) {
              $colNumber  = !empty($contract['contract_number']) ? $contract['contract_number'] : 'N/A';
              $branchName = !empty($contract['branch']) ? $contract['branch'] : 'Unknown Branch';
              $expiryDate = date("M Y", strtotime($contract['end_date']));

              // Truncate to prevent long text from breaking the table alignment
              $colNumber  = substr($colNumber, 0, $colWidth - 2);
              $branchName = substr($branchName, 0, $branchWidth - 2);
              $expiryDate = substr($expiryDate, 0, $dateWidth - 2);

              // Table Rows
              $dataRow = str_pad(" " . $colNumber, $colWidth) . str_pad(" " . $branchName, $branchWidth) . str_pad(" " . $expiryDate, $dateWidth);
              // Excel-style row separator (Thin line)
              $rowText = str_replace(" ", "\xC2\xA0", $dataRow) . "\r\n" . str_repeat("-", $totalWidth) . "\r\n";

              $rowLen = strlen(urlencode($rowText));
              if ($runningLen + $rowLen > $contractBudget) {
                  break; // adding this row would risk breaking the link — stop here
              }
              $contractRows .= $rowText;
              $runningLen    += $rowLen;
              $contractsShown++;
          }

          $contractList = $tableHeader . $contractRows;
          $notShown = count($uniqueContractsForEmail) - $contractsShown;
          if ($notShown > 0) {
              $contractList .= "...and {$notShown} more RFP(s) not shown here to keep the email link working - see the full list in the table on this page.\r\n";
          }

          $defaultMessage = $messageIntro . $contractList . $messageOutro;

          $remainingForRecipients = max(0, $sharedBudget - strlen(urlencode($contractList)));
          $charsPerRecipient      = 35; 
          $recipientsPerBatch     = max(5, (int) floor($remainingForRecipients / $charsPerRecipient));

          $emailBatches = !empty($data['emails']) ? array_chunk($data['emails'], $recipientsPerBatch) : [[]];
          $gmailLinks = [];
          foreach ($emailBatches as $batchEmails) {
              $gmailLinks[] = $urlPrefix . urlencode(implode(',', $batchEmails))
                  . $subjectParam
                  . "&body=" . urlencode($defaultMessage);
          }

          // Safe ID for JavaScript to copy the specific table
          $tableId = 'table-' . md5($mainzone);
      ?>
          <div class="card zone-card">
              <!-- Zone Header -->
              <div class="zone-card-header d-flex justify-content-between align-items-center flex-wrap gap-3">
                  <div class="d-flex align-items-center">
                      <div class="zone-icon me-3">
                          <i class="bi bi-geo-alt-fill fs-5"></i>
                      </div>
                      <div>
                          <h5 class="zone-title"><?= htmlspecialchars($mainzone) ?></h5>
                          <small class="text-muted"><?= count($data['contracts']) ?> RFP(s) Requiring Action</small>
                      </div>
                  </div>
                  
                  <div class="d-flex align-items-center gap-2">
                      <!-- COPY TABLE BUTTON -->
                      <button class="btn btn-copy-table" onclick="copyTableForEmail(this, '<?= $tableId ?>')">
                          <i class="bi bi-clipboard me-1"></i> Copy for Email
                      </button>

                      <?php if (count($gmailLinks) <= 1): ?>
                      <a href="<?= htmlspecialchars($gmailLinks[0]) ?>" target="_blank" class="btn btn-primary btn-send-email text-white">
                          <i class="bi bi-envelope-paper-fill me-2"></i> Email <?= htmlspecialchars($mainzone) ?> Team
                      </a>
                      <?php else: ?>
                      <div class="dropdown">
                          <button class="btn btn-primary btn-send-email text-white dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                              <i class="bi bi-envelope-paper-fill me-2"></i> Email <?= htmlspecialchars($mainzone) ?> Team <span class="opacity-75">(<?= count($data['emails']) ?> recipients)</span>
                          </button>
                          <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                              <li><h6 class="dropdown-header">Send in <?= count($gmailLinks) ?> parts to reach all <?= count($data['emails']) ?> people</h6></li>
                              <?php foreach ($gmailLinks as $i => $link): ?>
                              <li>
                                  <a class="dropdown-item" href="<?= htmlspecialchars($link) ?>" target="_blank">
                                      <i class="bi bi-envelope-paper-fill me-2"></i>Batch <?= $i + 1 ?> of <?= count($gmailLinks) ?>
                                      <span class="text-muted">— <?= count($emailBatches[$i]) ?> recipient(s)</span>
                                  </a>
                              </li>
                              <?php endforeach; ?>
                          </ul>
                      </div>
                      <?php endif; ?>
                  </div>
              </div>

              <!-- Zone Data Table (Modern Excel Look) -->
              <div class="table-responsive">
                  <table id="<?= $tableId ?>" class="table table-modern align-middle mb-0">
                      <thead>
                          <tr>
                              <th>Contract No.</th>
                              <th>Personnel ID</th>
                              <th>Assigned To</th>
                              <th>Branch</th>
                              <th>Region</th>
                              <th>Area</th>
                              <th>RFP Start</th>
                              <th>RFP End</th>
                              <th>Status</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php foreach ($data['contracts'] as $row): 
                              // Contract uniqueness logic
                              $rawContractNum = trim($row['contract_number'] ?? '');
                              $displayContract = '';
                              if (!empty($rawContractNum) && !isset($seenContractNumbers[$rawContractNum])) {
                                  $displayContract = htmlspecialchars($rawContractNum);
                                  $seenContractNumbers[$rawContractNum] = true;
                              } elseif (empty($rawContractNum)) {
                                  $displayContract = '—';
                              } // If duplicate, remains empty for an Excel-merged look.

                              $monthsLeft = (date('Y', strtotime($row['end_date'])) - date('Y')) * 12 + 
                                            (date('m', strtotime($row['end_date'])) - date('m'));
                                            
                              if ($monthsLeft < 0) {
                                  $status = '<span class="badge-status bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="bi bi-x-circle-fill"></i> Expired</span>';
                              } elseif ($monthsLeft <= 2) {
                                  $status = '<span class="badge-status bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-50"><i class="bi bi-exclamation-triangle-fill"></i> Critical: '.$monthsLeft.' mo(s)</span>';
                              } else {
                                  $status = '<span class="badge-status bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25"><i class="bi bi-clock-fill"></i> In '.$monthsLeft.' mo(s)</span>';
                              }
                          ?>
                          <tr>
                              <td class="contract-display"><?= $displayContract ?></td>
                              <td><span class="text-muted fw-medium"><?= htmlspecialchars($row['id_number'] ?? '—') ?></span></td>
                              <td>
                                  <div class="d-flex align-items-center gap-2">
                                      <div class="avatar-circle"><?= htmlspecialchars(strtoupper(substr($row['first_name'] ?? '', 0, 1) . substr($row['last_name'] ?? '', 0, 1)) ?: '?') ?></div>
                                      <div>
                                          <div class="fw-semibold text-dark"><?= htmlspecialchars(trim($row['first_name'].' '.$row['last_name'])) ?></div>
                                          <small class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($row['email'] ?? 'No email assigned') ?></small>
                                      </div>
                                  </div>
                              </td>
                              <td class="fw-medium text-dark"><?= htmlspecialchars($row['branch'] ?? '—') ?></td>
                              <td><?= htmlspecialchars($row['region'] ?? '—') ?></td>
                              <td><?= htmlspecialchars($row['area'] ?? '—') ?></td>
                              <td><span class="text-muted"><?= date("M Y", strtotime($row['start_date'])) ?></span></td>
                              <td class="fw-bold text-dark"><?= date("M Y", strtotime($row['end_date'])) ?></td>
                              <td><?= $status ?></td>
                          </tr>
                          <?php endforeach; ?>
                      </tbody>
                  </table>
              </div>
          </div>
      <?php endforeach; ?>

  <?php else: ?>
    <!-- Empty State -->
    <div class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-body text-center p-5">
            <div class="empty-state-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h5 class="fw-bold text-dark">All Caught Up!</h5>
            <p class="text-muted mb-0">There are no RFP(s) nearing depletion within the next 5 months across any mainzone.</p>
        </div>
    </div>
  <?php endif; ?>
</div>

<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
/**
 * Copies the designated table to the clipboard with INLINE CSS injected. 
 * This ensures that when the user pastes into Gmail/Outlook, the borders 
 * and formatting are perfectly retained (email clients strip out class names).
 */
function copyTableForEmail(btnElement, tableId) {
    const originalTable = document.getElementById(tableId);
    if (!originalTable) return;

    // Clone the table so we can modify it strictly for copying without changing the UI
    const clone = originalTable.cloneNode(true);

    // Apply strict inline styles required by Email Clients
    clone.style.borderCollapse = 'collapse';
    clone.style.width = '100%';
    clone.style.fontFamily = 'Arial, Helvetica, sans-serif';
    clone.style.fontSize = '12px';

    // Style Headers
    const ths = clone.querySelectorAll('th');
    ths.forEach(th => {
        th.style.border = '1px solid #cccccc';
        th.style.padding = '8px 10px';
        th.style.backgroundColor = '#f1f5f9';
        th.style.fontWeight = 'bold';
        th.style.textAlign = 'left';
        th.style.color = '#1e293b';
    });

    // Style Data Cells
    const tds = clone.querySelectorAll('td');
    tds.forEach(td => {
        td.style.border = '1px solid #cccccc';
        td.style.padding = '8px 10px';
        td.style.color = '#334155';
    });

    // Fix badge visuals for email so they don't break format
    const badges = clone.querySelectorAll('.badge-status');
    badges.forEach(badge => {
        badge.style.display = 'inline-block';
        badge.style.fontWeight = 'bold';
        
        if(badge.classList.contains('text-danger')) {
            badge.style.color = '#dc2626'; // Red for expired
        } else if (badge.classList.contains('text-warning-emphasis')) {
            badge.style.color = '#b45309'; // Orange/Brown for Critical
        } else {
            badge.style.color = '#0284c7'; // Blue for pending
        }
    });

    // Create a hidden temporary container, append the cloned table
    const tempDiv = document.createElement('div');
    tempDiv.style.position = 'absolute';
    tempDiv.style.left = '-9999px';
    tempDiv.appendChild(clone);
    document.body.appendChild(tempDiv);

    // Select the table
    const range = document.createRange();
    range.selectNodeContents(tempDiv);
    const selection = window.getSelection();
    selection.removeAllRanges();
    selection.addRange(range);

    // Execute copy
    try {
        document.execCommand('copy');
        
        // Button Feedback
        const originalText = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="bi bi-check2-all me-1"></i> Copied!';
        btnElement.classList.add('copied');
        
        setTimeout(() => {
            btnElement.innerHTML = originalText;
            btnElement.classList.remove('copied');
        }, 2000);
    } catch (err) {
        console.error('Failed to copy table: ', err);
        alert('Failed to copy to clipboard.');
    }

    // Cleanup
    selection.removeAllRanges();
    document.body.removeChild(tempDiv);
}
</script>
</body>
</html>