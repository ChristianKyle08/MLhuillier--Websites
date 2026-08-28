<?php
ob_start(); // 1. Trap any accidental output from included files
session_start(); // 2. Start session BEFORE including config
include '../../config/config.php';
$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (!isset($_SESSION['admin_name']) || !isset($_SESSION['admin_email'])) {
    header('location:../../admin/rental/login_form.php');
    exit;
}

$adminEmail = $_SESSION['admin_email'];
$isMlhuillierEmail = preg_match('/@mlhuillier\.com$/i', $adminEmail); // ✅ Check if ML email

date_default_timezone_set('Asia/Manila');
$currentDate = date('Y-m-d');
$currentMonthStart = date('Y-m-01');
$oneYearLater = date('Y-m-01', strtotime('+12 months', strtotime($currentMonthStart)));

// ✅ Defensive limits: with hundreds of personnel/contract rows joined together,
// a slow host or default shared-hosting limits can trip a mid-request timeout or
// memory error - which the browser/front-end proxy often just shows as a generic
// Bad Gateway/500 error. These are safe no-ops if limits are already higher or if
// the host disables changing them.
if (function_exists('set_time_limit')) {
    @set_time_limit(120);
}
$memoryLimitRaw = ini_get('memory_limit');
if ($memoryLimitRaw !== '-1' && (int) $memoryLimitRaw > 0 && (int) $memoryLimitRaw < 256) {
    @ini_set('memory_limit', '256M');
}

// ✅ Cache "now" once - avoids calling date('Y')/date('m') again for every single
// contract row when computing months-remaining below.
$currentYear  = (int) date('Y');
$currentMonth = (int) date('m');

// ✅ Fetch contracts expiring within 1 year (not terminated, not void status,
// not void COL number, & not expired)
// Added c.mainzone to SELECT and ORDER BY clauses
$query = $conn->prepare("
    SELECT 
        c.id, c.contract_number, c.branch, c.mainzone, c.region, c.area, c.request_status,
        c.contract_start, c.contract_end,
        u.id_number, u.first_name, u.middle_name, u.last_name, u.email
    FROM create_contract c
    INNER JOIN (
        SELECT branch, MAX(contract_end) AS latest_contract_end
        FROM create_contract
        GROUP BY branch
    ) latest 
        ON c.branch = latest.branch AND c.contract_end = latest.latest_contract_end
    LEFT JOIN user_form u 
        ON c.region = u.region AND c.area = u.area
    WHERE 
        c.request_status NOT IN ('Terminated', 'Void')
        AND (c.contract_number IS NULL OR UPPER(TRIM(c.contract_number)) <> 'VOID')
        AND c.contract_end >= ?
        AND c.contract_end <= ?
    ORDER BY c.mainzone ASC, c.contract_end ASC
");

$query->bind_param("ss", $currentDate, $oneYearLater);
$query->execute();
$result = $query->get_result();
$notifCount = $result->num_rows;

// ✅ Group contracts and emails by Mainzone
$groupedData = [];

while ($row = $result->fetch_assoc()) {
    // ✅ Compute expiry timing ONCE per row right here, and reuse it everywhere
    // below (summary stats, email table, summary table, personnel table) instead
    // of re-parsing the same contract_end date 3-4 more times further down.
    $contractEndTs = strtotime($row['contract_end']);
    $row['contract_end_ts'] = $contractEndTs;
    $row['months_left'] = ((int) date('Y', $contractEndTs) - $currentYear) * 12 +
                          ((int) date('m', $contractEndTs) - $currentMonth);
    $row['is_critical'] = $row['months_left'] <= 3;

    $mainzone = !empty($row['mainzone']) ? $row['mainzone'] : 'Unassigned Zone';
    
    if (!isset($groupedData[$mainzone])) {
        $groupedData[$mainzone] = [
            'emails' => [],
            'contracts' => [],
            'unique_contracts' => [] // ✅ Deduplicated by contract id — the region/area join can match
                                     //    one contract to several personnel, which repeated the same
                                     //    COL number; this keeps each contract listed only once.
        ];
    }
    
    // Collect only valid ML email addresses without duplicates for the same zone.
    // ✅ Keyed by e-mail (instead of an in_array() scan over a growing plain list)
    // so de-duplication stays O(1) per row even once a zone's recipient list runs
    // into the hundreds.
    if (!empty($row['email']) && preg_match('/@mlhuillier\.com$/i', trim($row['email']))) {
        $groupedData[$mainzone]['emails'][trim($row['email'])] = true;
    }
    
    $groupedData[$mainzone]['contracts'][] = $row;

    if (!isset($groupedData[$mainzone]['unique_contracts'][$row['id']])) {
        $groupedData[$mainzone]['unique_contracts'][$row['id']] = $row;
    }
}
$result->free(); // ✅ release the result set's memory now that everything is in $groupedData

// ✅ Quick summary stats for the page header (counted from unique contracts, not personnel-matched rows)
$totalZones = count($groupedData);
$uniqueContractCount = 0;
$criticalContracts = 0;
foreach ($groupedData as $zoneData) {
    $uniqueContractCount += count($zoneData['unique_contracts']);
    foreach ($zoneData['unique_contracts'] as $c) {
        // ✅ Reuses the months_left/is_critical computed once per row above,
        // instead of parsing contract_end with strtotime()/date() again here.
        if ($c['is_critical']) {
            $criticalContracts++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Contract Expiry Notifications</title>
<link rel="icon" href="../../assets/images/ml_logo.png" type="image/png">
<link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
<link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">
<link href="../../assets/css/poppins.css" rel="stylesheet">
<style>
body {
    background: #f4f7f6;
    font-family: 'Poppins', sans-serif;
    color: #333;
}
.page-header {
    background: #fff;
    padding: 20px 30px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    margin-bottom: 25px;
}
.zone-card {
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.04);
    margin-bottom: 30px;
    overflow: hidden;
    background: #fff;
}
.zone-card-header {
    background: #fff;
    border-bottom: 2px solid #edf2f9;
    padding: 16px 24px;
}
.table-modern {
    margin-bottom: 0;
}
.table-modern thead {
    background: #f8fafc;
    color: #475569;
}
.table-modern th {
    font-weight: 600;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
    padding: 15px;
    border-bottom: 2px solid #edf2f9 !important;
}
.table-modern td {
    padding: 15px;
    font-size: 13px;
    vertical-align: middle;
    border-bottom: 1px solid #edf2f9;
    color: #4b5563;
}
.table-modern tbody tr:hover {
    background-color: #f1f5f9;
}
.table-modern tbody tr:last-child td {
    border-bottom: none;
}
.back-btn {
    border-radius: 50px;
    border: 1px solid #dc2626;
    transition: all .3s;
    color: #dc2626;
    font-size: 13px;
    font-weight: 500;
    padding: 8px 20px;
}
.back-btn:hover {
    background: #dc2626;
    color: #fff;
    transform: translateX(-3px);
}
.btn-send-email {
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    padding: 8px 16px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border: none;
    box-shadow: 0 4px 10px rgba(220, 38, 38, 0.2);
}
.btn-send-email:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    transform: translateY(-1px);
    box-shadow: 0 6px 12px rgba(220, 38, 38, 0.3);
}
.zone-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}
.badge-status {
    padding: 6px 12px;
    border-radius: 50px;
    font-weight: 500;
    font-size: 11px;
    display: inline-block;
}

/* ===== UI Enhancements ===== */
.stats-bar {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 25px;
}
.stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 18px 22px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 14px;
    border-left: 4px solid #dc2626;
}
.stat-card .stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #fff;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    flex-shrink: 0;
}
.stat-card.stat-zones { border-left-color: #4338ca; }
.stat-card.stat-zones .stat-icon { background: linear-gradient(135deg, #6366f1, #4338ca); }
.stat-card.stat-critical { border-left-color: #c2410c; }
.stat-card.stat-critical .stat-icon { background: linear-gradient(135deg, #f97316, #c2410c); }
.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.1;
}
.stat-label {
    font-size: 11px;
    color: #64748b;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.search-wrapper {
    position: relative;
    max-width: 320px;
    width: 100%;
}
.search-wrapper input {
    width: 100%;
    padding: 10px 14px 10px 38px;
    border-radius: 50px;
    border: 1px solid #e2e8f0;
    font-size: 13px;
    background: #f8fafc;
    transition: all .2s;
}
.search-wrapper input:focus {
    outline: none;
    border-color: #dc2626;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(220,38,38,0.08);
}
.search-wrapper i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 13px;
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
.zone-card {
    transition: box-shadow .25s ease, transform .25s ease;
    animation: fadeInUp .4s ease both;
}
.zone-card:hover {
    box-shadow: 0 10px 26px rgba(0,0,0,0.08);
}
.table-modern tbody tr:nth-child(even) {
    background-color: #fbfcfd;
}
.badge-status i {
    margin-right: 4px;
}
@media (max-width: 768px) {
    .stats-bar {
        grid-template-columns: 1fr;
    }
}

/* ===== Contract Summary Table + Copy-for-Email ===== */
.email-table-wrap {
    padding: 15px 24px 20px;
    background: #fafafa;
    border-bottom: 2px solid #edf2f9;
}
.email-table-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    flex-wrap: wrap;
    gap: 8px;
}
.email-table-label {
    font-size: 13px;
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.email-table-label i {
    margin-right: 5px;
    color: #dc2626;
}
.btn-copy-table {
    border: 1px solid #dc2626;
    background: #fff;
    color: #dc2626;
    font-size: 12px;
    font-weight: 500;
    padding: 6px 14px;
    border-radius: 50px;
    transition: all .2s;
    cursor: pointer;
}
.btn-copy-table:hover:not(:disabled) {
    background: #dc2626;
    color: #fff;
}
.btn-copy-table:disabled {
    opacity: .75;
    cursor: default;
}
.email-summary-table th,
.email-summary-table td {
    padding: 12px 15px;
}
.text-critical {
    color: #dc2626;
    font-weight: 700;
}
.text-upcoming {
    color: #b45309;
    font-weight: 600;
}
</style>
</head>
<body>

<div class="container my-4">
  <div class="page-header d-flex justify-content-between align-items-center">
    <a href="admin_page.php" class="btn back-btn">
      <i class="bi bi-arrow-left-circle-fill me-1"></i> Back to Dashboard
    </a>
    <h4 class="m-0 fw-bold"><i class="bi bi-bell-fill text-danger me-2"></i> Contract of lease Expiry Notifications (12 Months)</h4>
  </div>

  <?php if ($notifCount > 0): ?>
      <div class="stats-bar">
          <div class="stat-card">
              <div class="stat-icon"><i class="bi bi-file-earmark-text-fill"></i></div>
              <div>
                  <div class="stat-value"><?= $uniqueContractCount ?></div>
                  <div class="stat-label">Expiring Contracts</div>
              </div>
          </div>
          <div class="stat-card stat-zones">
              <div class="stat-icon"><i class="bi bi-geo-alt-fill"></i></div>
              <div>
                  <div class="stat-value"><?= $totalZones ?></div>
                  <div class="stat-label">Mainzones Affected</div>
              </div>
          </div>
          <div class="stat-card stat-critical">
              <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
              <div>
                  <div class="stat-value"><?= $criticalContracts ?></div>
                  <div class="stat-label">Critical (&le; 3 Months)</div>
              </div>
          </div>
      </div>

      <div class="d-flex justify-content-end mb-3">
          <div class="search-wrapper">
              <i class="bi bi-search"></i>
              <input type="text" id="zoneSearch" placeholder="Search branch or zone...">
          </div>
      </div>

      <?php if (!$isMlhuillierEmail): ?>
      <div class="alert alert-warning text-center shadow-sm rounded-3 mb-4 border-0" style="font-size: 14px; background-color: #fffbeb; color: #92400e;">
        <i class="bi bi-shield-lock-fill me-2 fs-5 align-middle"></i>
        Security Notice: You must use your authorized <strong>@mlhuillier.com</strong> email account to send these official notifications.
      </div>
      <?php endif; ?>

      <?php $zoneIndex = 0; foreach ($groupedData as $mainzone => $data): $zoneIndex++;

          // 1. Set explicit column headers
          $headerNo     = "No.";
          $headerCol    = "COL Number";
          $headerBranch = "Branch";
          $headerExpiry = "Expiry Date";
          $headerStatus = "Status";

          // Set baseline minimum widths so headers never overlap or feel cramped
          $maxNoLen     = max(strlen($headerNo), 5);      // Minimum 5 chars
          $maxColLen    = max(strlen($headerCol), 16);    // Minimum 16 chars
          $maxBranchLen = max(strlen($headerBranch), 24); // Minimum 24 chars
          $maxExpiryLen = max(strlen($headerExpiry), 14); // Minimum 14 chars
          $maxStatusLen = max(strlen($headerStatus), 18); // Minimum 18 chars

          $processedContracts = [];
          $rowNumber = 1;

          // 2. Process contract data and adjust widths dynamically if data exceeds baseline
          foreach ($data['unique_contracts'] as $contract) {
              $colNumber  = !empty($contract['contract_number']) ? $contract['contract_number'] : 'N/A';
              $branchName = !empty($contract['branch']) ? $contract['branch'] : 'Unknown Branch';
              // ✅ Reuse the timestamp/months-left computed once when the row was fetched,
              // instead of calling strtotime() on the same contract_end 3 more times.
              $expiryDate = date("M d, Y", $contract['contract_end_ts']);

              $emailMonthsLeft = $contract['months_left'];
              $urgencyLabel = ($contract['is_critical'] ? 'CRITICAL' : 'UPCOMING') . ' (' . $emailMonthsLeft . ' mo)';

              $noStr = $rowNumber . '.';

              // Expand column width if a row value is wider than the header/baseline
              $maxNoLen     = max($maxNoLen, strlen($noStr));
              $maxColLen    = max($maxColLen, strlen($colNumber));
              $maxBranchLen = max($maxBranchLen, strlen($branchName));
              $maxExpiryLen = max($maxExpiryLen, strlen($expiryDate));
              $maxStatusLen = max($maxStatusLen, strlen($urgencyLabel));

              $processedContracts[] = [
                  'no'         => $noStr,
                  'colNumber'  => $colNumber,
                  'branchName' => $branchName,
                  'expiryDate' => $expiryDate,
                  'status'     => $urgencyLabel
              ];

              $rowNumber++;
          }

          // 3. Define standard format string with generous 4-space gap separation
          $gap = "    "; // 4 spaces between columns
          $rowFormat = "%-{$maxNoLen}s" . $gap
                     . "%-{$maxColLen}s" . $gap
                     . "%-{$maxBranchLen}s" . $gap
                     . "%-{$maxExpiryLen}s" . $gap
                     . "%-{$maxStatusLen}s\r\n";

          // 4. Build aligned Header
          $tableHeader = sprintf(
              $rowFormat,
              $headerNo,
              $headerCol,
              $headerBranch,
              $headerExpiry,
              $headerStatus
          );

          // Calculate exact divider line length to match the header width perfectly
          $lineWidth = strlen(rtrim($tableHeader, "\r\n"));
          $topDivider = str_repeat("=", $lineWidth) . "\r\n";
          $midDivider = str_repeat("-", $lineWidth) . "\r\n";
          $botDivider = str_repeat("=", $lineWidth) . "\r\n";

          // 5. Build aligned Data Rows
          $contractRows = "";
          foreach ($processedContracts as $c) {
              $contractRows .= sprintf(
                  $rowFormat,
                  $c['no'],
                  $c['colNumber'],
                  $c['branchName'],
                  $c['expiryDate'],
                  $c['status']
              );
          }

          $formattedTable = $topDivider . $tableHeader . $midDivider . $contractRows . $botDivider;

          // Construct the professional email body dynamically for this mainzone
          $defaultSubject = "Action Required: Contract of Lease Nearing Expiry - " . $mainzone;
          
          $defaultMessage = "Dear {$mainzone} Team,\r\n\r\n"
              . "This is an official notification regarding the Contract of Lease under your supervision that are nearing expiry and require prompt action. Please treat this matter with urgency to avoid any lapse in lease coverage or disruption to branch operations.\r\n\r\n"
              . "Listed below are the contract(s) approaching expiration within the next 12 months or less from now:\r\n\r\n"
              . $formattedTable . "\r\n"
              . "Kindly review the contract(s) listed above and initiate the necessary steps for renewal, extension, or closure, as applicable. Please ensure that all corresponding documents are updated and submitted promptly.\r\n\r\n"
              . "Should you have any questions or require further assistance, please coordinate with the Rental Management Team at your earliest convenience.\r\n\r\n"
              . "Thank you for your immediate attention to this matter.\r\n\r\n"
              . "Best regards,\r\n\r\nML Rental Management Team\r\nMLhuillier Financial Services";

          // ✅ Build one or more Gmail compose links for this zone instead of a single
          // link, so a large recipient list (500+) never produces one oversized URL.
          // Web servers/proxies commonly cap a request line around 8,000 bytes, and a
          // single link for hundreds of addresses can otherwise balloon past 20,000+
          // characters once URL-encoded — that oversized request is what was producing
          // the Bad Gateway / broken compose window. Splitting into safely-sized
          // batches keeps every generated link comfortably inside that limit. The
          // message subject/body/table are completely unchanged - only how many "To"
          // addresses ride along in a single link differs.
          $mailBaseUrl    = "https://mail.google.com/mail/?view=cm&fs=1";
          $subjectEncoded = urlencode($defaultSubject);
          $bodyEncoded    = urlencode($defaultMessage);
          $fixedOverhead  = strlen($mailBaseUrl) + strlen("&su=") + strlen($subjectEncoded)
                          + strlen("&body=") + strlen($bodyEncoded) + strlen("&to=");

          $SAFE_URL_BUDGET  = 7000; // stay well under common ~8,000-byte server/proxy request-line limits
          $MIN_BATCH_BUDGET = 1000; // floor, in case an unusually large contract table eats most of the budget
          $recipientBudget  = max($MIN_BATCH_BUDGET, $SAFE_URL_BUDGET - $fixedOverhead);

          $zoneEmails   = array_keys($data['emails']); // de-duplicated recipient list for this zone
          $emailBatches = [];
          $batch        = [];
          $batchLen     = 0;

          foreach ($zoneEmails as $zoneEmail) {
              $need = strlen(urlencode($zoneEmail)) + 3; // +3 for the "%2C" separator between addresses
              if ($batch && ($batchLen + $need) > $recipientBudget) {
                  $emailBatches[] = $batch;
                  $batch = [];
                  $batchLen = 0;
              }
              $batch[] = $zoneEmail;
              $batchLen += $need;
          }
          if (!empty($batch) || empty($emailBatches)) {
              $emailBatches[] = $batch; // always keep at least one (possibly empty) link, same as before
          }

          $gmailLinks = [];
          foreach ($emailBatches as $batchEmails) {
              $gmailLinks[] = $mailBaseUrl
                  . "&to=" . urlencode(implode(',', $batchEmails))
                  . "&su=" . $subjectEncoded
                  . "&body=" . $bodyEncoded;
          }
          $totalBatches = count($gmailLinks);
      ?>

          <div class="card zone-card" data-zone-search="<?= htmlspecialchars(strtolower($mainzone . ' ' . implode(' ', array_column($data['contracts'], 'branch')))) ?>">
              <div class="zone-card-header d-flex justify-content-between align-items-center">
                  <div class="d-flex align-items-center">
                      <div class="bg-danger text-white rounded p-2 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                          <i class="bi bi-file-earmark-text-fill fs-5"></i>
                      </div>
                      <div>
                          <h5 class="zone-title"><?= htmlspecialchars($mainzone) ?></h5>
                          <small class="text-muted fw-medium"><?= count($data['unique_contracts']) ?> Expiring Contract(s)</small>
                      </div>
                  </div>
                  
                  <?php if ($totalBatches <= 1): ?>
                  <a href="<?= htmlspecialchars($gmailLinks[0]) ?>" target="_blank" class="btn btn-primary btn-send-email text-white">
                      <i class="bi bi-envelope-paper-fill me-2"></i> Email <?= htmlspecialchars($mainzone) ?> Team
                  </a>
                  <?php else: ?>
                  <div class="dropdown">
                      <button class="btn btn-primary btn-send-email text-white dropdown-toggle" type="button"
                              data-bs-toggle="dropdown" aria-expanded="false"
                              title="<?= count($zoneEmails) ?> recipients — split into <?= $totalBatches ?> emails so each link stays a safe size">
                          <i class="bi bi-envelope-paper-fill me-2"></i> Email <?= htmlspecialchars($mainzone) ?> Team
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="max-height: 320px; overflow-y: auto;">
                          <li><h6 class="dropdown-header"><?= count($zoneEmails) ?> recipients &middot; <?= $totalBatches ?> batches</h6></li>
                          <?php foreach ($gmailLinks as $batchIdx => $batchLink): ?>
                          <li>
                              <a class="dropdown-item" href="<?= htmlspecialchars($batchLink) ?>" target="_blank">
                                  <i class="bi bi-envelope-fill me-2 text-danger"></i>
                                  Batch <?= $batchIdx + 1 ?> of <?= $totalBatches ?>
                                  <span class="text-muted">(<?= count($emailBatches[$batchIdx]) ?>)</span>
                              </a>
                          </li>
                          <?php endforeach; ?>
                      </ul>
                  </div>
                  <?php endif; ?>
              </div>

              <!-- Unique Contracts / Email Summary Overview -->
              <div class="email-table-wrap">
                  <div class="email-table-toolbar">
                      <span class="email-table-label"><i class="bi bi-table"></i> Unique Contracts Summary</span>
                      <button type="button" class="btn-copy-table" data-copy-target="zoneTable_<?= $zoneIndex ?>" title="Copies a formatted table you can paste (Ctrl+V) into the Gmail compose window">
                          <i class="bi bi-clipboard-check"></i> Copy Table for Email
                      </button>
                  </div>
                  <div class="table-responsive bg-white rounded border">
                      <table class="table table-modern table-hover table-bordered email-summary-table mb-0" id="zoneTable_<?= $zoneIndex ?>">
                          <thead class="table-light">
                              <tr>
                                  <th class="text-center" style="width: 50px;">No.</th>
                                  <th>COL Number</th>
                                  <th>Branch</th>
                                  <th>Expiry Date</th>
                                  <th class="text-center">Status</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php $sn = 1; foreach ($data['unique_contracts'] as $uc):
                                  // ✅ Reuses months_left/is_critical/timestamp computed once per row above.
                                  $ucMonthsLeft = $uc['months_left'];
                                  $ucCritical = $uc['is_critical'];
                                  $ucColNumber = !empty($uc['contract_number']) ? $uc['contract_number'] : 'N/A';
                              ?>
                              <tr>
                                  <td class="text-center text-muted"><?= $sn++ ?></td>
                                  <td class="fw-bold text-dark"><?= htmlspecialchars($ucColNumber) ?></td>
                                  <td><?= htmlspecialchars($uc['branch'] ?? '—') ?></td>
                                  <td class="fw-medium"><?= date("M d, Y", $uc['contract_end_ts']) ?></td>
                                  <td class="text-center <?= $ucCritical ? 'text-critical bg-danger bg-opacity-10' : 'text-upcoming bg-warning bg-opacity-10' ?>">
                                      <?= $ucCritical ? 'CRITICAL' : 'UPCOMING' ?> (<?= $ucMonthsLeft ?> mo)
                                  </td>
                              </tr>
                              <?php endforeach; ?>
                          </tbody>
                      </table>
                  </div>
              </div>

              <!-- Personnel Listing Table -->
              <div class="p-4 pt-3">
                  <span class="email-table-label d-block mb-3"><i class="bi bi-people-fill"></i> Assigned Personnel Directory</span>
                  <div class="table-responsive rounded border">
                      <table class="table table-modern table-hover align-middle mb-0">
                          <thead class="table-light">
                              <tr>
                                  <th>Personnel ID</th>
                                  <th>Assigned To</th>
                                  <th>Branch</th>
                                  <th>Region</th>
                                  <th>Area</th>
                                  <th>Effectivity Date</th>
                                  <th>Expiry Date</th>
                                  <th class="text-center">Status</th>
                              </tr>
                          </thead>
                          <tbody>
                              <?php foreach ($data['contracts'] as $row): 
                                  // ✅ Reuses months_left/is_critical computed once per row above.
                                  $monthsLeft = $row['months_left'];

                                  if ($row['is_critical']) {
                                      $status = '<span class="badge-status bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25"><i class="bi bi-exclamation-triangle-fill"></i> Critical: '.$monthsLeft.' mo</span>';
                                  } else {
                                      $status = '<span class="badge-status bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-50"><i class="bi bi-hourglass-split"></i> Expiring: '.$monthsLeft.' mo</span>';
                                  }
                              ?>
                              <tr>
                                  <td><span class="text-muted fw-bold"><?= htmlspecialchars($row['id_number'] ?? '—') ?></span></td>
                                  <td>
                                      <div class="fw-bold text-dark"><?= htmlspecialchars(trim($row['first_name'].' '.$row['last_name'])) ?></div>
                                      <small class="text-muted"><?= htmlspecialchars($row['email'] ?? 'No email assigned') ?></small>
                                  </td>
                                  <td class="fw-medium"><?= htmlspecialchars($row['branch'] ?? '—') ?></td>
                                  <td><?= htmlspecialchars($row['region'] ?? '—') ?></td>
                                  <td><?= htmlspecialchars($row['area'] ?? '—') ?></td>
                                  <td><span class="text-muted"><?= date("M d, Y", strtotime($row['contract_start'])) ?></span></td>
                                  <td class="fw-bold text-dark"><?= date("M d, Y", $row['contract_end_ts']) ?></td>
                                  <td class="text-center"><?= $status ?></td>
                              </tr>
                              <?php endforeach; ?>
                          </tbody>
                      </table>
                  </div>
              </div>
          </div>
      <?php endforeach; ?>

  <?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-body text-center p-5">
            <div class="mb-3 text-success">
                <i class="bi bi-check-circle-fill" style="font-size: 3rem;"></i>
            </div>
            <h5 class="fw-bold text-dark">All Caught Up!</h5>
            <p class="text-muted mb-0">There are no active contracts expiring within the next 12 months.</p>
        </div>
    </div>
  <?php endif; ?>
</div>

<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var searchInput = document.getElementById('zoneSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            var term = this.value.trim().toLowerCase();
            document.querySelectorAll('.zone-card').forEach(function (card) {
                var haystack = card.getAttribute('data-zone-search') || '';
                card.style.display = haystack.indexOf(term) !== -1 ? '' : 'none';
            });
        });
    }

    // ✅ Copy a fully inline-styled HTML table to the clipboard so it pastes into
    // Gmail's compose window (or any rich-text editor) as a real formatted table,
    // not plain text.
    function buildEmailTableHtml(tableEl) {
        var headerHtml = '';
        tableEl.querySelectorAll('thead th').forEach(function (th) {
            headerHtml += '<th style="padding:10px 12px;text-align:left;border:1px solid #b91c1c;background:#dc2626;color:#ffffff;font-family:Arial,sans-serif;font-size:12px;text-transform:uppercase;letter-spacing:.3px;">' + th.textContent.trim() + '</th>';
        });

        var bodyHtml = '';
        tableEl.querySelectorAll('tbody tr').forEach(function (tr, idx) {
            var bg = idx % 2 === 0 ? '#ffffff' : '#f8fafc';
            bodyHtml += '<tr style="background:' + bg + ';">';
            tr.querySelectorAll('td').forEach(function (td) {
                var color = '#374151';
                var weight = 'normal';
                if (td.classList.contains('text-critical')) { color = '#dc2626'; weight = 'bold'; }
                if (td.classList.contains('text-upcoming')) { color = '#b45309'; weight = 'bold'; }
                bodyHtml += '<td style="padding:8px 12px;border:1px solid #e5e7eb;font-family:Arial,sans-serif;font-size:13px;color:' + color + ';font-weight:' + weight + ';">' + td.textContent.trim() + '</td>';
            });
            bodyHtml += '</tr>';
        });

        return '<table style="border-collapse:collapse;width:100%;">' +
               '<thead><tr>' + headerHtml + '</tr></thead>' +
               '<tbody>' + bodyHtml + '</tbody>' +
               '</table>';
    }

    async function copyTableForEmail(btn) {
        var table = document.getElementById(btn.getAttribute('data-copy-target'));
        if (!table) return;

        var html = buildEmailTableHtml(table);
        var text = table.innerText;
        var copied = false;

        try {
            if (navigator.clipboard && window.ClipboardItem) {
                var item = new ClipboardItem({
                    'text/html': new Blob([html], { type: 'text/html' }),
                    'text/plain': new Blob([text], { type: 'text/plain' })
                });
                await navigator.clipboard.write([item]);
                copied = true;
            }
        } catch (e) {
            copied = false;
        }

        if (!copied) {
            var temp = document.createElement('div');
            temp.setAttribute('contenteditable', 'true');
            temp.style.position = 'fixed';
            temp.style.left = '-9999px';
            temp.innerHTML = html;
            document.body.appendChild(temp);
            var range = document.createRange();
            range.selectNodeContents(temp);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
            try {
                document.execCommand('copy');
                copied = true;
            } catch (e) {
                copied = false;
            }
            sel.removeAllRanges();
            document.body.removeChild(temp);
        }

        var original = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2"></i> Copied!';
        btn.disabled = true;
        setTimeout(function () {
            btn.innerHTML = original;
            btn.disabled = false;
        }, 1800);
    }

    document.querySelectorAll('.btn-copy-table').forEach(function (btn) {
        btn.addEventListener('click', function () {
            copyTableForEmail(btn);
        });
    });
});
</script>
</body>
</html>