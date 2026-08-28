<?php
session_start();
include('../../config/config.php');

// Redirect if not logged in
if (!isset($_SESSION['user_name']) && !isset($_SESSION['admin_name'])) {
    header('Location: login_form.php');
    exit();
}

$mainzone = $_SESSION['mainzone'] ?? '';
$region   = $_SESSION['region'] ?? '';
$area     = $_SESSION['area'] ?? '';

/* ============================================================
 * ROLE ACCESS GATE
 * ------------------------------------------------------------
 * Assumes the login script stores the user's role/position in
 * $_SESSION['role']. If your login sets it under a different
 * key (e.g. $_SESSION['user_role'], $_SESSION['position']),
 * this is the only line you need to change.
 * ============================================================ */
$user_role      = $_SESSION['role'] ?? '';
$is_vpo_checker = (strcasecmp(trim($user_role), 'Vpo-Checker') === 0);
$current_user   = $_SESSION['user_name'] ?? $_SESSION['admin_name'];

/* ============================================================
 * FILTER INPUT (Locked Mainzone to Logged-in User & Manual Sub-filtering)
 * ============================================================ */
// Mainzone is strictly locked to the logged-in user's assigned Mainzone
$f_mainzone = $mainzone;

// Region, Area, and Branch values are manually selected by the user via GET (not auto-filled from session)
$f_region   = isset($_GET['region']) ? trim($_GET['region']) : '';
$f_area     = isset($_GET['area'])   ? trim($_GET['area'])   : '';
$f_branch   = isset($_GET['branch']) ? trim($_GET['branch']) : '';

$sort_dir = (strtoupper($_GET['sort'] ?? 'ASC') === 'DESC') ? 'DESC' : 'ASC';

// Added transaction_date <= CURDATE() to restrict data up to the current date
$where  = ["status = 'Unpaid'", "transaction_date <= CURDATE()"];
$params = [];
$types  = '';
$ready  = false;   // becomes true only after the user manually selects at least one filter
$notice = '';       // message shown instead of results when not ready

// Require the user to manually select at least one filter (Region, Area, or Branch) to display data
if ($f_region !== '' || $f_area !== '' || $f_branch !== '') {
    $ready = true;
    if ($f_mainzone !== '') { $where[] = 'mainzone = ?'; $params[] = $f_mainzone; $types .= 's'; }
    if ($f_region   !== '') { $where[] = 'region = ?';   $params[] = $f_region;   $types .= 's'; }
    if ($f_area     !== '') { $where[] = 'area = ?';     $params[] = $f_area;     $types .= 's'; }
    if ($f_branch   !== '') { $where[] = 'branch = ?';   $params[] = $f_branch;   $types .= 's'; }
} else {
    $notice = 'Please select at least one filter (Region, Area, or Branch) to view unpaid transactions.';
}

/* ============================================================
 * PAGINATION
 * ============================================================ */
$per_page = 25;
$page     = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page - 1) * $per_page;

$rows       = [];
$total_rows = 0;
$summary    = ['count' => 0, 'total_amount' => 0.0, 'total_payable' => 0.0, 'oldest_date' => null];

if ($ready) {
    $where_sql = implode(' AND ', $where);

    // Aggregate summary across the FULL filtered set (not just this page)
    $sql_summary = "SELECT COUNT(*) AS cnt,
                             COALESCE(SUM(amount),0) AS total_amount,
                             COALESCE(SUM(edit_amount_lessor),0) AS total_payable,
                             MIN(transaction_date) AS oldest_date
                      FROM transactional
                      WHERE $where_sql";
    $stmt = $conn->prepare($sql_summary);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $sres = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $summary['count']         = (int)$sres['cnt'];
    $summary['total_amount']  = (float)$sres['total_amount'];
    $summary['total_payable'] = (float)$sres['total_payable'];
    $summary['oldest_date']   = $sres['oldest_date'];
    $total_rows = $summary['count'];

    // Page of rows for the table
    $sql = "SELECT id, transaction_date, payment_due_date, contract_number,
                   mainzone, region, area, branch,
                   lessor_type, corporate_name,
                   l1_firstname, l1_middlename, l1_lastname,
                   amount, wtax, edit_amount_lessor,
                   mode_of_payment, status, extract_request_status
            FROM transactional
            WHERE $where_sql
            ORDER BY transaction_date $sort_dir
            LIMIT ? OFFSET ?";
    $detail_params   = $params;
    $detail_params[] = $per_page;
    $detail_params[] = $offset;
    $detail_types    = $types . 'ii';

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($detail_types, ...$detail_params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
}

$total_pages = $total_rows > 0 ? (int)ceil($total_rows / $per_page) : 1;

/* ============================================================
 * DROPDOWN SOURCES (Cascading logic based on selected filters)
 * ============================================================ */
$mainzone_options = $region_options = $area_options = $branch_options = [];

if ($f_mainzone !== '') {
    $mainzone_options[] = $f_mainzone;
}

// Base condition enforces status='Unpaid' and transaction_date <= CURDATE()
$dd_base = "WHERE status = 'Unpaid' AND transaction_date <= CURDATE()";

// 1. Region dropdown (displays regions belonging to the user's logged-in Mainzone where status is Unpaid)
$dd_region_cond = $dd_base;
if ($f_mainzone !== '') {
    $dd_region_cond .= " AND mainzone = '" . $conn->real_escape_string($f_mainzone) . "'";
}
if ($r = $conn->query("SELECT DISTINCT region FROM transactional $dd_region_cond AND region IS NOT NULL AND region <> '' ORDER BY region")) {
    while ($row = $r->fetch_assoc()) { $region_options[] = $row['region']; }
}

// 2. Area dropdown (displays area values ONLY belonging to the selected Region)
if ($f_region !== '') {
    $dd_area_cond = $dd_region_cond . " AND region = '" . $conn->real_escape_string($f_region) . "'";
    if ($a = $conn->query("SELECT DISTINCT area FROM transactional $dd_area_cond AND area IS NOT NULL AND area <> '' ORDER BY area")) {
        while ($row = $a->fetch_assoc()) { $area_options[] = $row['area']; }
    }
}

// 3. Branch dropdown (displays branch values ONLY belonging to the selected Area)
if ($f_area !== '') {
    $dd_branch_cond = $dd_region_cond;
    if ($f_region !== '') {
        $dd_branch_cond .= " AND region = '" . $conn->real_escape_string($f_region) . "'";
    }
    $dd_branch_cond .= " AND area = '" . $conn->real_escape_string($f_area) . "'";
    if ($b = $conn->query("SELECT DISTINCT branch FROM transactional $dd_branch_cond AND branch IS NOT NULL AND branch <> '' ORDER BY branch")) {
        while ($row = $b->fetch_assoc()) { $branch_options[] = $row['branch']; }
    }
}

/* ============================================================
 * HELPERS
 * ============================================================ */
function esc($v) {
    return htmlspecialchars($v ?? '', ENT_QUOTES, 'UTF-8');
}
function peso($v) {
    return '₱' . number_format((float)$v, 2);
}
function lessor_display($row) {
    if (!empty($row['corporate_name'])) return $row['corporate_name'];
    $name = trim(($row['l1_firstname'] ?? '') . ' ' . ($row['l1_middlename'] ?? '') . ' ' . ($row['l1_lastname'] ?? ''));
    return $name !== '' ? $name : '—';
}
function fmt_date($d) {
    if (empty($d)) return '—';
    $t = strtotime($d);
    return $t ? date('M d, Y', $t) : '—';
}
/** Returns [severity, label] where severity is one of: green, amber, red, none */
function aging_info($due_date, $transaction_date = null) {
    if (empty($transaction_date)) {
        if (empty($due_date)) return ['none', 'No date on file'];
        $transaction_date = $due_date;
    }
    
    // Calculate days elapsed from transaction_date to current date (now)
    $today = strtotime('today');
    $tx    = strtotime(date('Y-m-d', strtotime($transaction_date)));
    
    $diff  = (int)round(($today - $tx) / 86400);

    if ($diff <= 0) {
        $days = abs($diff);
        return ['green', $days === 0 ? '0 days old' : "In $days day" . ($days === 1 ? '' : 's')];
    } elseif ($diff <= 30) {
        return ['amber', "$diff day" . ($diff === 1 ? '' : 's') . " old"];
    } else {
        return ['red', "$diff days old"];
    }
}
function build_qs($overrides = []) {
    $base = $_GET;
    foreach ($overrides as $k => $v) { $base[$k] = $v; }
    return '?' . htmlspecialchars(http_build_query($base), ENT_QUOTES, 'UTF-8');
}

$export_params = $_GET;
unset($export_params['page']);
$export_url = 'export_unsettled_transaction.php?' . htmlspecialchars(http_build_query($export_params), ENT_QUOTES, 'UTF-8');

$scope_label = $is_vpo_checker
    ? trim(implode(' · ', array_filter([$f_mainzone, $f_region, $f_area, $f_branch])))
    : trim(implode(' · ', array_filter([$mainzone, $region, $area])));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="shortcut icon" href="../../assets/images/rental_logo.png" type="image/x-icon">
<title>Unpaid Transactions Report</title>
<!-- ✅ Local Google Font -->
  <link href="../../assets/css/poppins.css" rel="stylesheet">

  <!-- ✅ Local Bootstrap CSS -->
  <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">

  <!-- ✅ Local Bootstrap Icons -->
  <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">

  <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
  <!-- ✅ Your custom CSS should come AFTER font import -->
   <link rel="stylesheet" href="../../assets/css/sidebar.css">
  <link rel="stylesheet" href="../../assets/css/scrollbar.css">

<style>
  :root{
    --bg:#F4F5F7;
    --surface:#FFFFFF;
    --surface-alt:#FAFBFC;
    --ink:#1B2130;
    --ink-soft:#626B7A;
    --ink-faint:#9AA2AF;
    --border:#E1E4E9;
    --primary:#12594C;
    --primary-dark:#0B3F35;
    --primary-soft:#E4F0EE;
    --amber:#A15C09;
    --amber-soft:#FBEEDC;
    --red:#A62A21;
    --red-soft:#FBE7E5;
    --green:#276B45;
    --green-soft:#E6F2EA;
    --radius:10px;
    --shadow:0 1px 2px rgba(27,33,48,.05), 0 6px 16px rgba(27,33,48,.05);
  }
  *{ box-sizing:border-box; }
  html,body{ margin:0; padding:0; }
  body{
    background:var(--bg);
    color:var(--ink);
    font-family:'Poppins', sans-serif;
    font-size:14px;
    line-height:1.5;
  }
  
  /* Layout wrapper to avoid sidebar overlap */
  .main-content {
    margin-left: 260px; /* Adjust if your sidebar width differs */
    padding: 10px 32px 72px;
    transition: margin-left 0.3s ease;
  }

  h1,h2,h3{ font-family:'Poppins', sans-serif; margin:0; }
  a{ color:var(--primary); text-decoration:none; }
  .mono{ font-family:'JetBrains Mono','SFMono-Regular',Consolas,monospace; font-variant-numeric:tabular-nums; }
  :focus-visible{ outline:2px solid var(--primary); outline-offset:2px; }

  /* ---------- Topbar ---------- */
  .topbar{
    display:flex; justify-content:space-between; align-items:flex-end;
    flex-wrap:wrap; gap:16px; margin-bottom:22px;
  }
  .eyebrow{
    text-transform:uppercase; letter-spacing:.09em; font-size:11px;
    color:var(--primary); font-weight:600; margin:0 0 6px;
  }
  .topbar h1{ font-size:23px; font-weight:600; letter-spacing:-.01em; }
  .topbar .sub{ color:var(--ink-soft); font-size:13px; margin-top:4px; }
  .user-chip{
    display:flex; align-items:center; gap:9px;
    background:var(--surface); border:1px solid var(--border);
    padding:9px 14px; border-radius:999px; font-size:12.5px; color:var(--ink-soft);
    box-shadow:var(--shadow); white-space:nowrap;
  }
  .role-tag{
    background:var(--primary-soft); color:var(--primary-dark);
    padding:3px 10px; border-radius:999px; font-weight:600; font-size:10.5px;
    letter-spacing:.03em; text-transform:uppercase;
  }

  /* ---------- Filter panel ---------- */
  .panel{
    background:var(--surface); border:1px solid var(--border); border-radius:var(--radius);
    box-shadow:var(--shadow); padding:18px 20px; margin-bottom:20px;
  }
  .panel-title{ font-size:11px; text-transform:uppercase; letter-spacing:.07em; color:var(--ink-faint); font-weight:600; margin-bottom:12px; }
  .filter-form{ display:flex; flex-wrap:wrap; align-items:end; gap:14px; }
  .field{ display:flex; flex-direction:column; gap:6px; min-width:180px; flex: 1 1 auto; max-width: max-content; }
  .field label{ font-size:11.5px; color:var(--ink-soft); font-weight:600; }
  .field select{
    appearance:none; -webkit-appearance:none;
    background:var(--surface-alt) url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="10" height="6"><path d="M0 0l5 6 5-6z" fill="%23626B7A"/></svg>') no-repeat right 12px center;
    border:1px solid var(--border); border-radius:7px; padding:9px 32px 9px 11px;
    font-family:inherit; font-size:13.5px; color:var(--ink); min-width:180px; width: 100%;
  }
  .field select:focus{ border-color:var(--primary); }
  .btn{
    display:inline-flex; align-items:center; gap:6px; border:none; cursor:pointer;
    padding:10px 18px; border-radius:7px; font-family:inherit; font-size:13.5px; font-weight:600;
    background:var(--primary); color:#fff; white-space: nowrap;
  }
  .btn:hover{ background:var(--primary-dark); }
  .btn-ghost{
    background:transparent; color:var(--ink-soft); border:1px solid var(--border); font-weight:500;
  }
  .btn-ghost:hover{ border-color:var(--ink-faint); color:var(--ink); }
  .scope-line{ font-size:12.5px; color:var(--ink-soft); margin-bottom: 15px; }
  .scope-line b{ color:var(--ink); }

  /* ---------- Notice / empty state ---------- */
  .notice{
    background:var(--surface); border:1px dashed var(--border); border-radius:var(--radius);
    padding:40px 24px; text-align:center; color:var(--ink-soft); font-size:14px;
  }
  .notice strong{ display:block; color:var(--ink); font-size:15px; margin-bottom:6px; font-family:'Poppins', sans-serif; }

  /* ---------- Stat cards ---------- */
  .stats{ display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:20px; }
  .stat-card{
    background:var(--surface); border:1px solid var(--border); border-radius:var(--radius);
    box-shadow:var(--shadow); padding:16px 18px;
  }
  .stat-label{ font-size:11px; text-transform:uppercase; letter-spacing:.06em; color:var(--ink-faint); font-weight:600; margin-bottom:8px; }
  .stat-value{ font-family:'JetBrains Mono',monospace; font-size:21px; font-weight:600; color:var(--ink); font-variant-numeric:tabular-nums; }
  .stat-value.warn{ color:var(--red); }
  .stat-sub{ font-size:11.5px; color:var(--ink-soft); margin-top:4px; }

  /* ---------- Table ---------- */
  .table-wrap{
    background:var(--surface); border:1px solid var(--border); border-radius:var(--radius);
    box-shadow:var(--shadow); overflow:auto;
  }
  .results-line{ display:flex; justify-content:space-between; align-items:center; padding:14px 18px 0; font-size:12.5px; color:var(--ink-soft); }
  table{ width:100%; border-collapse:collapse; min-width:1080px; }
  thead th{
    position:sticky; top:0; background:var(--surface-alt); z-index:1;
    text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.05em;
    color:var(--ink-soft); font-weight:600; padding:12px 16px; border-bottom:1px solid var(--border);
    white-space:nowrap;
  }
  thead th a{ color:inherit; display:inline-flex; align-items:center; gap:4px; }
  thead th a:hover{ color:var(--primary); }
  tbody td{
    padding:13px 16px; border-bottom:1px solid var(--border); vertical-align:top; font-size:13.3px;
  }
  tbody tr:nth-child(even){ background:var(--surface-alt); }
  tbody tr:hover{ background:var(--primary-soft); }
  tbody tr.severe{ box-shadow:inset 3px 0 0 var(--red); }
  .num{ text-align:right; }
  .loc-top{ font-weight:500; }
  .loc-sep{ color:var(--ink-faint); margin:0 4px; }
  .loc-branch{ color:var(--ink-soft); font-size:12px; margin-top:2px; }
  .lessor-type{ display:block; color:var(--ink-faint); font-size:11px; margin-top:2px; text-transform:uppercase; letter-spacing:.03em; }
  .payable{ font-weight:600; }

  .dot{ display:inline-block; width:7px; height:7px; border-radius:50%; margin-right:6px; }
  .dot.green{ background:var(--green); }
  .dot.amber{ background:var(--amber); }
  .dot.red{ background:var(--red); }
  .dot.none{ background:var(--ink-faint); }
  .aging{ font-size:12.5px; }
  .aging.green{ color:var(--green); }
  .aging.amber{ color:var(--amber); }
  .aging.red{ color:var(--red); font-weight:600; }
  .aging.none{ color:var(--ink-faint); }
  .due-date{ font-size:11.5px; color:var(--ink-faint); display:block; margin-bottom:3px; }

  .pill{
    display:inline-block; padding:3px 10px; border-radius:999px; font-size:11px; font-weight:600;
    background:var(--red-soft); color:var(--red); letter-spacing:.02em;
  }

  /* ---------- Footer / pagination ---------- */
  .table-footer{
    display:flex; justify-content:space-between; align-items:center;
    padding:14px 18px; border-top:1px solid var(--border); flex-wrap:wrap; gap:10px;
  }
  .pager{ display:flex; align-items:center; gap:10px; font-size:12.5px; color:var(--ink-soft); }
  .pager a, .pager span.disabled{
    border:1px solid var(--border); border-radius:6px; padding:6px 11px; font-size:12.5px;
  }
  .pager a{ color:var(--ink); }
  .pager a:hover{ border-color:var(--primary); color:var(--primary); }
  .pager span.disabled{ color:var(--ink-faint); }

  /* ---------- RESPONSIVE QUERIES ---------- */
  @media (max-width: 1024px) {
    .stats { grid-template-columns: repeat(2, 1fr); }
  }

  @media (max-width: 768px){
    .main-content { margin-left: 0; padding: 18px 16px 48px; }
    
    .topbar { flex-direction: column; align-items: flex-start; gap: 12px; }
    
    .filter-form { flex-direction: column; align-items: stretch; gap: 10px; }
    .field { width: 100%; min-width: 100%; max-width: 100%; }
    .btn { width: 100%; justify-content: center; }
    
    .results-line { flex-direction: column; align-items: flex-start; gap: 12px; }
    .results-line .btn { width: 100%; text-align: center; justify-content: center; }
    
    .table-footer { flex-direction: column; align-items: center; }
  }

  @media (max-width: 480px) {
    .stats { grid-template-columns: 1fr; }
    .user-chip { width: 100%; justify-content: space-between; }
  }
</style>
</head>
<body>
<?php include ('navbar.php'); ?>
  <div class="main-content">
  <button id="toggleSidebar" class="btn-light border text-dark d-flex align-items my-3 py-1 px-2" style="border-radius: 5px;">
    <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
      <span class="fw-normal">Menu</span>
  </button>
    <div class="topbar">
      <div>
        <p class="eyebrow">Transactions Ledger</p>
        <h1>Unpaid Transactions</h1>
        <p class="sub">
          <?php if ($is_vpo_checker): ?>
            Org-wide view · filter by Region, Area, or Branch to begin.
          <?php else: ?>
            Scoped view · you can narrow down by Branch below.
          <?php endif; ?>
        </p>
      </div>
      <div class="user-chip">
        <span><?= esc($current_user) ?></span>
        <span class="role-tag"><?= $is_vpo_checker ? 'Vpo-Checker' : ($user_role !== '' ? esc($user_role) : 'Branch user') ?></span>
      </div>
    </div>

    <div class="panel">
      <?php if (!$is_vpo_checker): ?>
        <p class="scope-line">Showing base constraints for
          <b><?= esc($scope_label !== '' ? $scope_label : '—') ?></b>
          — (Assigned at login). Filter further using the options below.
        </p>
      <?php endif; ?>
      <p class="panel-title"><?= $is_vpo_checker ? 'Filter (select at least one)' : 'Filter Results' ?></p>
      <form class="filter-form" method="get">
        <div class="field">
          <label for="mainzone">Mainzone</label>
          <input type="hidden" name="mainzone" value="<?= esc($f_mainzone) ?>">
          <select id="mainzone" disabled style="background-color: #e9ecef; cursor: not-allowed;">
            <option value="<?= esc($f_mainzone) ?>"><?= esc($f_mainzone !== '' ? $f_mainzone : 'No Mainzone Assigned') ?></option>
          </select>
        </div>
        <div class="field">
          <label for="region">Region</label>
          <select name="region" id="region">
            <option value="">All regions</option>
            <?php foreach ($region_options as $opt): ?>
              <option value="<?= esc($opt) ?>" <?= $f_region === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="area">Area</label>
          <select name="area" id="area">
            <option value="">All areas</option>
            <?php foreach ($area_options as $opt): ?>
              <option value="<?= esc($opt) ?>" <?= $f_area === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="branch">Branch</label>
          <select name="branch" id="branch">
            <option value="">All branches</option>
            <?php foreach ($branch_options as $opt): ?>
              <option value="<?= esc($opt) ?>" <?= $f_branch === $opt ? 'selected' : '' ?>><?= esc($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn">Apply filter</button>
        <?php if ($ready): ?>
          <a href="<?= esc($_SERVER['PHP_SELF']) ?>" class="btn btn-ghost">Clear</a>
        <?php endif; ?>
      </form>
    </div>

    <?php if (!$ready): ?>

      <div class="notice">
        <strong>No data to show yet</strong>
        <?= esc($notice) ?>
      </div>

    <?php else: ?>

      <div class="stats">
        <div class="stat-card">
          <div class="stat-label">Unpaid Records</div>
          <div class="stat-value"><?= number_format($summary['count']) ?></div>
          <div class="stat-sub">matching current filter</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total Unpaid Amount</div>
          <div class="stat-value warn"><?= peso($summary['total_amount']) ?></div>
          <div class="stat-sub">sum of <span class="mono">amount</span></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Amount to Lessors</div>
          <div class="stat-value"><?= peso($summary['total_payable']) ?></div>
          <div class="stat-sub">sum of <span class="mono">amount_lessor</span></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Oldest Unpaid</div>
          <div class="stat-value" style="font-size:16px;"><?= $summary['oldest_date'] ? fmt_date($summary['oldest_date']) : '—' ?></div>
          <div class="stat-sub">earliest transaction date on file</div>
        </div>
      </div>

      <div class="table-wrap">
        <div class="results-line">
          <span>
            <?php if ($total_rows > 0): ?>
              Showing <b><?= number_format($offset + 1) ?>–<?= number_format(min($offset + $per_page, $total_rows)) ?></b>
              of <b><?= number_format($total_rows) ?></b> unpaid transactions
            <?php else: ?>
              No unpaid transactions match this filter
            <?php endif; ?>
          </span>
          <a href="<?= $export_url ?>" class="btn" style="padding:8px 14px;font-size:12.5px;">Export to Excel</a>
        </div>

        <?php if (empty($rows)): ?>
          <div class="notice" style="border:none;">
            <strong>All clear</strong>
            Nothing unpaid for this selection right now.
          </div>
        <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>
                <a href="<?= build_qs(['sort' => $sort_dir === 'ASC' ? 'DESC' : 'ASC']) ?>">
                  Transaction Date <?= $sort_dir === 'ASC' ? '↑' : '↓' ?>
                </a>
              </th>
              <th>Contract #</th>
              <th>Location</th>
              <th>Lessor / Payee</th>
              <th class="num">Amount</th>
              <th class="num">W/Tax</th>
              <th class="num">Amount to Lessor</th>
              <th>Mode of Payment</th>
              <th>Due Date / Aging</th>
              <th>Status</th>
              <th>Extraction Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <?php [$sev, $agingLabel] = aging_info($row['payment_due_date'], $row['transaction_date']); ?>
              <tr class="<?= $sev === 'red' ? 'severe' : '' ?>">
                <td class="mono"><?= fmt_date($row['transaction_date']) ?></td>
                <td class="mono"><?= esc($row['contract_number']) ?></td>
                <td>
                  <div class="loc-top"><?= esc($row['region']) ?><span class="loc-sep">›</span><?= esc($row['area']) ?></div>
                  <div class="loc-branch"><?= esc($row['branch']) ?></div>
                </td>
                <td>
                  <?= esc(lessor_display($row)) ?>
                  <span class="lessor-type"><?= esc($row['lessor_type'] ?: '—') ?></span>
                </td>
                <td class="num mono"><?= peso($row['amount']) ?></td>
                <td class="num mono"><?= peso($row['wtax']) ?></td>
                <td class="num mono payable"><?= peso($row['edit_amount_lessor']) ?></td>
                <td><?= esc($row['mode_of_payment'] ?: '—') ?></td>
                <td>
                  <span class="due-date">Due <?= fmt_date($row['payment_due_date']) ?></span>
                  <span class="aging <?= $sev ?>"><span class="dot <?= $sev ?>"></span><?= esc($agingLabel) ?></span>
                </td>
                <td><span class="pill"><?= esc($row['status']) ?></span></td>
                <td>
                    <span class="pill <?= (strcasecmp(empty($row['extract_request_status']) ? '' : $row['extract_request_status'], 'Extracted') === 0) ? 'bg-success text-white' : 'bg-secondary text-white' ?>">
                        <?= esc(empty($row['extract_request_status']) ? 'Unextracted' : $row['extract_request_status']) ?>
                    </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>

        <?php if ($total_pages > 1): ?>
        <div class="table-footer">
          <div class="pager">
            <?php if ($page > 1): ?>
              <a href="<?= build_qs(['page' => $page - 1]) ?>">← Prev</a>
            <?php else: ?>
              <span class="disabled">← Prev</span>
            <?php endif; ?>
            <span>Page <?= $page ?> of <?= $total_pages ?></span>
            <?php if ($page < $total_pages): ?>
              <a href="<?= build_qs(['page' => $page + 1]) ?>">Next →</a>
            <?php else: ?>
              <span class="disabled">Next →</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

    <?php endif; ?>
  </div>

  <!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4">
      <div class="modal-body">
        <div class="text-center mb-3">
          <i class="bi bi-box-arrow-right text-danger" style="font-size: 3rem;"></i>
        </div>
        <h5 class="mb-2">Logging Out</h5>
        <p class="text-muted mb-3">Please wait while we securely log you out...</p>
        <div class="progress" style="height: 8px;">
          <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 100%;"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const toggleBtn = document.getElementById('toggleSidebar');
  const sidebar = document.getElementById('sidebarMenu');

  if(toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('collapsed');
    });
  }

  document.getElementById('logoutLink')?.addEventListener('click', function (e) {
    e.preventDefault();
    const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'), {
      backdrop: 'static',
      keyboard: false
    });
    logoutModal.show();
    setTimeout(() => window.location.href = '../../logout.php', 2500);
  });

  // Dynamic / Real-time Form Cascading
  // This automatically resets child dropdowns and submits the form when a parent changes
  document.addEventListener('DOMContentLoaded', function() {
      const rg = document.getElementById('region');
      const ar = document.getElementById('area');
      const br = document.getElementById('branch');

      if(rg) rg.addEventListener('change', function() {
          if(ar) ar.value = '';
          if(br) br.value = '';
          this.form.submit();
      });

      if(ar) ar.addEventListener('change', function() {
          if(br) br.value = '';
          this.form.submit();
      });

      if(br) br.addEventListener('change', function() {
          this.form.submit();
      });
  });
</script>
</body>
</html>