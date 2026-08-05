<?php
session_start();
include '../../config/config.php';

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit;
}

$contractId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$branchId = isset($_GET['branch_id']) ? $_GET['branch_id'] : '';
$branch = isset($_GET['branch']) ? $_GET['branch'] : '';
$contractNumber_get = isset($_GET['contract_number']) ? $_GET['contract_number'] : '';

$contract = [];
if ($contractId > 0) {
    $stmt = $conn->prepare("SELECT * FROM create_contract WHERE id = ?");
    $stmt->bind_param("i", $contractId);
    $stmt->execute();
    $result = $stmt->get_result();
    $contract = $result->fetch_assoc();
}

// Fetch contracts dynamically by branch ID
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_contracts') {
    $branchId = $_POST['branch_id'] ?? '';
    $stmt = $conn->prepare("SELECT contract_number FROM create_contract WHERE branch_id = ?");
    $stmt->bind_param("s", $branchId);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        echo '<option disabled selected>-- Choose contract --</option>';
        while ($row = $res->fetch_assoc()) {
            echo '<option value="' . htmlspecialchars($row['contract_number']) . '">' . htmlspecialchars($row['contract_number']) . '</option>';
        }
    } else {
        echo '<option disabled selected>No contracts found</option>';
    }
    exit;
}

// Fetch branch list based on session's region and area
$branches = [];
$region = $_SESSION['region'] ?? '';
$area = $_SESSION['area'] ?? '';
$query = $conn->prepare("SELECT DISTINCT branch_id, branch FROM create_contract WHERE region = ? AND area = ?");
$query->bind_param("ss", $region, $area);
$query->execute();
$res = $query->get_result();
while ($row = $res->fetch_assoc()) {
    $branches[] = $row;
}
$updateMessage = '';
$updateSuccessful = false;

function ordinal($number) {
    $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
    if ((($number % 100) >= 11) && (($number % 100) <= 13))
        return $number . 'th';
    else
        return $number . $ends[$number % 10];
}

if (isset($_POST['apply_changes'])) {
    $newDay = isset($_POST['payment_due_day']) ? intval($_POST['payment_due_day']) : null;

    if ($newDay >= 1 && $newDay <= 31) {
        $query = "SELECT payment_due_date FROM create_contract WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $contractId);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $currentDate);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($currentDate) {
            $dateParts = explode('-', $currentDate);
            $year = $dateParts[0];
            $month = $dateParts[1];

            $newDate = date('Y-m-d', strtotime("$year-$month-$newDay"));

            if (date('j', strtotime($newDate)) == $newDay) {
                $updateSql = "UPDATE create_contract SET payment_due_date = ? WHERE id = ?";
                $updateStmt = mysqli_prepare($conn, $updateSql);
                mysqli_stmt_bind_param($updateStmt, "si", $newDate, $contractId);

                if (mysqli_stmt_execute($updateStmt)) {
                    $ordinalDay = ordinal($newDay);
                    $updateSuccessful = true;

                    $updateMessage = "
                        <div id='updateMessage' class='alert alert-success alert-dismissible fade show mt-2' role='alert'>
                            Payment due date updated to <strong>Every {$ordinalDay} day of the month</strong> successfully!
                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                        </div>
                    ";
                } else {
                    $updateMessage = "<div class='alert alert-danger mt-2'>Update failed: " . mysqli_error($conn) . "</div>";
                }

                mysqli_stmt_close($updateStmt);
            } else {
                $updateMessage = "<div class='alert alert-warning mt-2'>Invalid day for the selected month.</div>";
            }
        } else {
            $updateMessage = "<div class='alert alert-danger mt-2'>Current payment due date not found.</div>";
        }
    } else {
        $updateMessage = "<div class='alert alert-warning mt-2'>Invalid day selected. Must be between 1 and 31.</div>";
    }
}
// Initialize modal variables
$showModal   = false;
$modalTitle  = '';
$modalBody   = '';
$modalClass  = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rfp_btn'])) {

  // Collect inputs safely
  $startDate      = !empty($_POST['start_date']) ? date('Y-m-d', strtotime($_POST['start_date'])) : null;
  $endDate        = !empty($_POST['end_date']) ? date('Y-m-d', strtotime($_POST['end_date'])) : null;
  $paymentDueDay  = $_POST['payment_due_date'] ?? null;
  $contractNumber = $_POST['contract_number'] ?? null;
  $branchId       = $_POST['branch_id'] ?? null;
  
  // Correctly target the 'name' attribute from your HTML select
  $modeOfPayment  = $_POST['mode_of_payment'] ?? ''; 

  // Financial fields (force numeric values)
  $amount        = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
  $vatType       = $_POST['vat_type'] ?? null;
  $netOfVat      = isset($_POST['net_of_vat']) ? (float) $_POST['net_of_vat'] : 0;
  $vatAmount     = isset($_POST['vat_amount']) ? (float) $_POST['vat_amount'] : 0;
  $wtax          = isset($_POST['wtax']) ? (float) $_POST['wtax'] : 0;
  $amountLessor  = isset($_POST['amount_lessor']) ? (float) $_POST['amount_lessor'] : 0;

  // Convert payment due day into full date (YYYY-MM-DD)
  $paymentDueDate = null;
  if (!empty($paymentDueDay)) {
      $baseDate  = $startDate ?? date('Y-m-d');
      $monthYear = date('Y-m', strtotime($baseDate));
      $paymentDueDate = $monthYear . '-' . str_pad($paymentDueDay, 2, '0', STR_PAD_LEFT);
  }

  // 1. Check if Mode of Payment is empty FIRST
  if (empty($modeOfPayment)) {
      $modalTitle = "Error ❌";
      $modalBody  = "Please select a valid Mode of Payment.";
      $modalClass = "modal-danger";
  } 
  // 2. Check if other required fields are missing
  elseif (!$startDate || !$endDate || !$contractNumber || !$branchId) {
      $modalTitle = "Warning ⚠️";
      $modalBody  = "Missing required fields for RFP submission.";
      $modalClass = "modal-warning";
  } 
  // 3. If everything is valid, proceed with the database update
  else {
    $stmt = $conn->prepare("
    UPDATE create_contract 
    SET 
        start_date         = ?, 
        end_date           = ?, 
        mode_of_payment    = ?, 
        payment_due_date   = ?, 
        amount             = ?, 
        vat_type           = ?, 
        net_of_vat         = ?, 
        vat_amount         = ?, 
        wtax               = ?, 
        amount_lessor      = ?, 
        edit_amount_lessor  = ?,
        request_status     = 'Prepared', -- Added comma here
        prepared_by        = ?,          -- Added comma here
        rfp_date           = CURRENT_TIMESTAMP
    WHERE contract_number = ? 
    AND branch_id = ?
");

if ($stmt) {
    $stmt->bind_param(
        "ssssdsdddddsss", // 14 characters for 14 placeholders
        $startDate,
        $endDate,
        $modeOfPayment,
        $paymentDueDate,
        $amount,
        $vatType,
        $netOfVat,
        $vatAmount,
        $wtax,
        $amountLessor,
        $amountLessor,
        $_SESSION['user_name'],
        $contractNumber,
        $branchId
    );

          if ($stmt->execute()) {
              if ($stmt->affected_rows > 0) {
                  $modalTitle = "Success ✅";
                  $modalBody  = "RFP submitted successfully! Pending for approval.";
                  $modalClass = "modal-success";
              } else {
                  $modalTitle = "Notice ℹ️";
                  $modalBody  = "No changes were made. Please check the contract number and branch.";
                  $modalClass = "modal-warning";
              }
          } else {
              $modalTitle = "Error ❌";
              $modalBody  = "Execution failed: " . $stmt->error;
              $modalClass = "modal-danger";
          }
          $stmt->close();
      } else {
          $modalTitle = "Error ❌";
          $modalBody  = "Query preparation failed: " . $conn->error;
          $modalClass = "modal-danger";
      }
  }

  $showModal = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Request For Payment - ML Rentals</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="../../assets/css/poppins.css" rel="stylesheet">
  <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/sidebar.css">
  <link rel="stylesheet" href="../../assets/css/scrollbar.css">

  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Poppins', sans-serif;
      color: #333;
    }

    .card {
      border: none;
      border-radius: 1rem;
      box-shadow: 0 0.75rem 2rem rgba(0, 0, 0, 0.05);
      background-color: #fff;
    }

    .form-label {
      font-weight: 600;
      font-size: 0.95rem;
      color: #333;
    }

    .form-label i {
      color: #d70c0c;
    }

    .form-select, .form-control {
      border-radius: 0.5rem;
      border: 1px solid #ced4da;
      transition: 0.2s ease;
    }

    .form-select:focus, .form-control:focus {
      border-color: #d70c0c;
      box-shadow: 0 0 0 0.2rem rgba(215, 12, 12, 0.2);
    }

    .btn-primary {
      background-color: #d70c0c;
      border-color: #d70c0c;
      font-weight: 600;
      border-radius: 0.5rem;
    }

    .btn-primary:hover {
      background-color: #b60909;
      border-color: #b60909;
    }

    .btn-light {
      border-radius: 0.5rem;
    }

    #toggleSidebar i {
      color: #d70c0c;
    }

    @media (max-width: 576px) {
      .btn-lg {
        font-size: 1rem;
        padding: 0.75rem 1.25rem;
      }
    }
    #applyChangesBtn{
        color: #d70c0c;
        font-size: 8px;
        width: 150px;
        font-weight: 500;
        border:none;
        background-color: transparent;
        border-radius: 0.5rem;
        border: none;
        transition: 0.2s ease;
    }
  </style>
</head>

<body>
<?php include('navbar.php'); ?>

<div id="mainContent">
  <button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
    <i class="bi bi-list me-2"></i>
    <span class="fw-normal">Menu</span>
  </button>

  <div class="container pb-5">
    <div class="card p-4 px-md-5 py-md-4">
      <div class="mb-3 text-center">
        <h4 class="fw-semibold text-dark">
          <i class="bi bi-credit-card text-danger me-2"></i>Request For Payment
        </h4>
      </div>
      <div id="update-message-container"></div>

      <form method="POST" class="row g-4">
      <?php
$contractId     = $_GET['id'] ?? '';
$branchId       = $_GET['branch_id'] ?? '';
$branchName     = $_GET['branch'] ?? '';

// Secure validation
if (empty($contractId) || empty($branchId) || empty($contractNumber_get)) {
    die("Invalid access. Missing contract data.");
}

$userRole   = $_SESSION['user_role'] ?? '';
$userZone   = $_SESSION['mainzone'] ?? '';
$userRegion = $_SESSION['region'] ?? '';
$userArea   = $_SESSION['area'] ?? '';


// =======================
// LABELS / ICONS
// =======================
$labelOverrides = [
  'advanceRental_amount' => 'Advance Rental Amount',
  'advanceRental_from' => 'Advance Rental From',
  'advanceRental_to' => 'Advance Rental To',
  'securityDeposit_amount' => 'Security Deposit Amount',
  'security_type' => 'Security Deposit Type',
  'authorize_mobilenumber' => 'Authorize Mobile Number',
  'contract_start' => 'Effectivity Date',
  'contract_end' => 'Expiry Date',
  'payment_due_date' => 'Payment Due Date',
  'consumable_from' => 'Consumable From',
  'consumable_to' => 'Consumable To',
  'amount' => 'Rental Amount',
  'vat_type' => 'VAT Type',
  'net_of_vat' => 'Net of VAT',
  'vat_amount' => 'VAT Amount',
  'wtax' => 'WTax',
  'amount_lessor' => 'Amount to Lessor',
  'created_date' => 'Created Date',
];

$iconMap = [
  'authorize_mobilenumber' => 'bi-phone',
  'advanceRental_amount' => 'bi-cash-coin',
  'advanceRental_from' => 'bi-calendar-event',
  'advanceRental_to' => 'bi-calendar-check',
  'securityDeposit_amount' => 'bi-safe',
  'security_type' => 'bi-safe',
  'contract_start' => 'bi-calendar-plus',
  'contract_end' => 'bi-calendar-x',
  'payment_due_date' => 'bi-calendar-date',
  'consumable_from' => 'bi-hourglass-split',
  'consumable_to' => 'bi-hourglass-bottom',
  'amount' => 'bi-cash',
  'vat_type' => 'bi-percent',
  'net_of_vat' => 'bi-receipt',
  'vat_amount' => 'bi-percent',
  'wtax' => 'bi-clipboard2-data',
  'amount_lessor' => 'bi-wallet2',
  'created_date' => 'bi-clock-history',
];

$dateFields = [
  'contract_start', 'contract_end', 'created_date',
  'advanceRental_from', 'advanceRental_to'
];

$groups = [
  'Advance Rental' => ['advanceRental_amount', 'advanceRental_from', 'advanceRental_to'],
  'Security Deposit' => ['securityDeposit_amount', 'security_type'],
  'Contract Details' => ['contract_start', 'contract_end', 'payment_due_date', 'created_date', 'authorize_mobilenumber'],
  'Consumables' => ['consumable_from', 'consumable_to'],
  'Financials' => ['amount', 'vat_type', 'net_of_vat', 'vat_amount', 'wtax', 'amount_lessor'],
  'RFP Details' => ['start_date', 'end_date']
];

// =======================
// CONTRACT NUMBER (REQUIRED FIRST)
// =======================
$contractNumber = '';
$branchName = '';

if (!empty($branchId)) {
    $stmt = mysqli_prepare($conn, "
        SELECT contract_number, branch 
        FROM create_contract 
        WHERE branch_id = ? 
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $branchId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $contractNumber = $contractNumber_get;
        $branchName     = $row['branch'];
    }
    mysqli_stmt_close($stmt);
}
// =======================
// ESCALATION LOGIC (NEXT RFP PERIOD)
// =======================

$rfpStartSave = $rfpEndSave = $rfpStartDate = $rfpEndDate = '';
$effectiveStart = date('Y-m-d'); // Default to today
$foundNext = false;

if (!empty($contract['contract_number'])) {
    $safeContract = mysqli_real_escape_string($conn, $contract['contract_number']);

    /**
     * 1️⃣ PRIMARY LOGIC: Find the "Next" record.
     * Criteria: Status is NULL/Empty AND it starts after the last Approved period.
     */
    $nextPeriodQuery = mysqli_query($conn, "
        SELECT start_date, end_date, 
               monthly_rental, vat_type, net_of_vat, vat, wtax, amount_to_lessor 
        FROM escalation 
        WHERE col_number = '$safeContract' 
          AND (status IS NULL OR status = '') 
          AND start_date > (
              SELECT COALESCE(MAX(end_date), '1900-01-01') 
              FROM escalation 
              WHERE col_number = '$safeContract' AND status = 'Approved'
          )
        ORDER BY start_date ASC 
        LIMIT 1
    ");

    if ($row = mysqli_fetch_assoc($nextPeriodQuery)) {
        $foundNext = true;
    } else {
        /**
         * 2️⃣ FALLBACK LOGIC: If no upcoming period is found, 
         * pull the current/last Approved record.
         */
        $fallbackQuery = mysqli_query($conn, "
            SELECT start_date, end_date, 
                   monthly_rental, vat_type, net_of_vat, vat, wtax, amount_to_lessor 
            FROM escalation 
            WHERE col_number = '$safeContract' 
              AND status = 'Approved'
            ORDER BY end_date DESC 
            LIMIT 1
        ");
        if ($row = mysqli_fetch_assoc($fallbackQuery)) {
            $foundNext = true;
        }
    }

    // 3️⃣ DATA ASSIGNMENT (If either primary or fallback record was found)
    if ($foundNext) {
        $rfpStartSave   = date('Y-m-d', strtotime($row['start_date']));
        $rfpEndSave     = date('Y-m-d', strtotime($row['end_date']));
        $rfpStartDate   = date('F Y', strtotime($row['start_date']));
        $rfpEndDate     = date('F Y', strtotime($row['end_date']));
        
        $effectiveStart = $row['start_date'];

        // Sync contract financial data for display
        $contract['amount']        = $row['monthly_rental'];
        $contract['vat_type']      = $row['vat_type'];
        $contract['net_of_vat']    = $row['net_of_vat'];
        $contract['vat_amount']    = $row['vat'];
        $contract['wtax']          = $row['wtax'];
        $contract['amount_lessor'] = $row['amount_to_lessor'];
    } else {
        // 4️⃣ FINAL FALLBACK: Use original contract table data if escalation is empty
        $effectiveStart = !empty($contract['contract_start']) ? $contract['contract_start'] : date('Y-m-d');
        $rfpEndDate     = !empty($contract['end_date']) ? $contract['end_date'] : '';
        
        if (!empty($rfpEndDate)) {
             $rfpEndSave = date('Y-m-d', strtotime($rfpEndDate));
             $rfpEndDateDisplay = date('F Y', strtotime($rfpEndDate)); // For string display
        }
    }
}

// =======================
// UI & INPUT PREPARATION
// =======================

$today = date('Y-m-d');

/** * Logic: If the found period start date is in the past, 
 * use Today as the effective UI start date.
 */
$uiStart = (strtotime($effectiveStart) < strtotime($today)) ? $today : $effectiveStart;

// For HTML Input <input type="month"> (Format: YYYY-MM)
$rfpStartValue = date('Y-m', strtotime($uiStart));
$minStart      = date('Y-m', strtotime($uiStart)); 

// For HTML Input Max (End of current selected period)
$maxStart      = !empty($rfpEndSave) ? date('Y-m', strtotime($rfpEndSave)) : '';

// Final display strings
$rfpStartDisplay = date('Y-m-d', strtotime($uiStart));
$rfpEndDisplay   = !empty($rfpEndDate) ? ( (strlen($rfpEndDate) > 10) ? $rfpEndDate : date('F Y', strtotime($rfpEndDate)) ) : '';
?>

<div class="modal fade" id="rfpModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header <?php echo $modalClass; ?> text-white">
        <h5 class="modal-title"><?php echo $modalTitle; ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <?php echo $modalBody; ?>
      </div>
      <div class="modal-footer">
        <button id="modalOkBtn" type="button" class="btn btn-primary">OK</button>
      </div>
    </div>
  </div>
</div>

<?php if ($showModal): ?>
<script>
  var rfpModal = new bootstrap.Modal(document.getElementById('rfpModal'));
  rfpModal.show();

  // Redirect when OK is clicked
  document.getElementById('modalOkBtn').addEventListener('click', function() {
      window.location.href = 'user_page.php';
  });
</script>
<?php endif; ?>


<div class="col-md-6">
  <label class="form-label"><i class="bi bi-shop me-1 text-danger"></i>Branch</label>
  <input type="text" class="form-control" name="branch" value="<?= htmlspecialchars($branchName) ?>" readonly>
  <input type="hidden" name="branch_id" value="<?= htmlspecialchars($branchId) ?>">
</div>

<div class="col-md-6">
  <label class="form-label">
    <i class="bi bi-file-earmark-text me-1 text-danger"></i>Contract Number
  </label>
  <input type="text" class="form-control" name="contract_number" id="contract_number" 
  value="<?= htmlspecialchars($contractNumber_get) ?>" readonly>
</div>

<?php 
// ✅ Ensure $isRefundable is always defined
$isRefundable = false;

if (isset($contract) && isset($contract['security_type'])) {
    $isRefundable = strtolower($contract['security_type']) === 'refundable';
}

?>
<?php foreach ($groups as $sectionTitle => $fields): ?>
  
  <?php if ($isRefundable && $sectionTitle === 'Consumables') continue; ?>

  <div class="col-12 border-top pt-3 mt-3">
    <h6 class="text-uppercase text-danger fw-bold mb-3"><?= $sectionTitle ?></h6>
  </div>
  <?php if ($sectionTitle === 'RFP Details'): ?>
   
    <?php if (!empty($lessor1Full)): ?>
      <div class="mb-1">
        <label class="form-label fw-semibold">Lessor 1</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($lessor1Full) ?>" readonly>
      </div>
    <?php endif; ?>

    <?php if (!empty($lessor2Full)): ?>
      <div class="mb-1">
        <label class="form-label fw-semibold">Lessor 2</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($lessor2Full) ?>" readonly>
      </div>
    <?php endif; ?>

    <?php if (!empty($authorizeFull)): ?>
      <div class="mb-1">
        <label class="form-label fw-semibold">Authorized to Claim</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($authorizeFull) ?>" readonly>
      </div>
    <?php endif; ?>

    <div class="mb-1">
      <label class="form-label fw-semibold">Start Date (RFP)</label>
      
      <input type="month" 
             class="form-control mb-1" 
             name="start_month"
             id="start_month"
             value="<?= htmlspecialchars($rfpStartValue) ?>"
             min="<?= $minStart ?>"
             max="<?= $maxStart ?>">

      <input type="hidden" 
             id="start_date_display"
             class="form-control" 
             value="<?= htmlspecialchars($rfpStartDisplay) ?>" 
             readonly>

      <input type="hidden" 
             name="start_date" 
             id="start_date_hidden" 
             value="<?= htmlspecialchars(date('Y-m-d', strtotime($rfpStartValue . '-01'))) ?>">
    </div>

    <div class="mb-1">
      <label class="form-label fw-semibold">End Date (RFP)</label>

      <input type="text" 
             class="form-control mb-1" 
             value="<?= htmlspecialchars($rfpEndDisplay) ?>" 
             readonly>

      <input type="hidden" 
             name="end_date" 
             value="<?= htmlspecialchars(date('Y-m-d', strtotime($rfpEndDate))) ?>">
    </div>

    <div class="mb-1">
    <label class="form-label fw-semibold">Payment Method</label>
      <select name="mode_of_payment" id="modeOfPayment" class="form-select" required>
        <option value="" <?= (empty($contract['mode_of_payment'])) ? 'selected' : '' ?> disabled>
            -- Select Payment Method --
        </option>
        
        <option value="PAYMENT SOLUTION" 
            <?= (isset($contract['mode_of_payment']) && strtoupper(trim($contract['mode_of_payment'])) === 'PAYMENT SOLUTION') ? 'selected' : '' ?>>
            Payment Solution
        </option>
        
        <option value="PDC" 
            <?= (isset($contract['mode_of_payment']) && strtoupper(trim($contract['mode_of_payment'])) === 'PDC') ? 'selected' : '' ?>>
            PDC
        </option>
        
        <option value="RTA" 
            <?= (isset($contract['mode_of_payment']) && strtoupper(trim($contract['mode_of_payment'])) === 'RTA') ? 'selected' : '' ?> disabled>
            RTA
        </option>
        
        <option value="WALLET" 
            <?= (isset($contract['mode_of_payment']) && strtoupper(trim($contract['mode_of_payment'])) === 'WALLET') ? 'selected' : '' ?> disabled>
            Wallet
        </option>
    </select>
    <?php if (!empty($contract['mode_of_payment']) && in_array(strtoupper($contract['mode_of_payment']), ['RTA', 'WALLET'])): ?>
        <div class="form-text text-danger italic" style="font-size: 11px;">
            Note: The current method (<?= htmlspecialchars($contract['mode_of_payment']) ?>) is no longer available for selection.
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php foreach ($fields as $field): ?>
  <?php
    if (!isset($contract[$field])) continue;

    // 🚫 Skip start_date and end_date completely
    if (in_array($field, ['start_date', 'end_date'])) continue;

    $label = $labelOverrides[$field] ?? ucfirst(str_replace('_', ' ', $field));
    $icon = $iconMap[$field] ?? 'bi-dot';
    $value = $contract[$field];
    $originalDueDate = (int)date('j', strtotime($value));
  ?>
  
  <div class="col-md-6">
    <label class="form-label fw-semibold text-dark">
      <i class="bi <?= $icon ?> text-danger me-1"></i><?= htmlspecialchars($label) ?>
    </label>

    <?php if ($field === 'payment_due_date'): ?>
      <div class="d-flex align-items-center gap-2">
        <select name="payment_due_date" id="paymentDueDay" class="form-select w-80">
          <?php for ($i = 1; $i <= 31; $i++): ?>
            <option value="<?= $i ?>" <?= $i == $originalDueDate ? 'selected' : '' ?>>
              <?= $i . (in_array($i, [1, 21, 31]) ? 'st' : ($i == 2 ? 'nd' : ($i == 3 ? 'rd' : 'th'))) ?>
            </option>
          <?php endfor; ?>
        </select>

        <button type="submit" name="apply_changes" id="applyChangesBtn" class="btn btn-danger d-none">
          Apply Changes
        </button>
      </div>

      <small class="text-muted d-block mt-1" id="currentDueDateText">
        Current: <strong>Every <?= $originalDueDate . (in_array($originalDueDate, [1, 21, 31]) ? 'st' : ($originalDueDate == 2 ? 'nd' : ($originalDueDate == 3 ? 'rd' : 'th'))) ?> day</strong> of the month
      </small>

    <?php elseif (in_array($field, $dateFields)): ?>
      <div class="form-control bg-white border text-dark" style="font-size: 13px;">
        <?= date('F d, Y', strtotime($value)) ?>
      </div>

    <?php else: ?>
      <input type="text" class="form-control" name="<?= $field ?>" value="<?= htmlspecialchars($value) ?>">
    <?php endif; ?>
  </div>
<?php endforeach; ?>


<?php endforeach; ?>

<?php
$restrictedRoles = ['Rm-Reviewer', 'Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver', 'HO', 'Auditor'];
if (!in_array($userRole, $restrictedRoles)) :
?>
  <div class="col-12 d-flex justify-content-center mt-3 mb-3">
    <button 
        type="submit" 
        name="rfp_btn" 
        id="rfp_btn" 
        class="btn btn-danger btn-pill rounded-pill w-auto px-4 py-3">
      <i class="bi bi-send-fill me-2"></i>Submit RFP
    </button>
  </div>
<?php endif; ?>
</form>

    </div>
  </div>
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
<script src="../../assets/js/jquery-3.7.1.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
   const startMonthInput   = document.getElementById('start_month');
      const startHiddenInput  = document.getElementById('start_date_hidden');
      const startDisplayInput = document.getElementById('start_date_display');

      function updateStartDateHidden() {
        if (startMonthInput.value) {
          const [year, month] = startMonthInput.value.split('-');
          const ymd = `${year}-${month}-01`;

          // update hidden input
          startHiddenInput.value = ymd;

          // update visible text display
          const dateObj = new Date(year, month - 1, 1);
          const options = { year: 'numeric', month: 'long', day: '2-digit' };
          startDisplayInput.value = dateObj.toLocaleDateString('en-US', options);
        } else {
          startHiddenInput.value = '';
          startDisplayInput.value = '';
        }
      }

      // Initial sync
      updateStartDateHidden();

      // Sync on change
      startMonthInput.addEventListener('input', updateStartDateHidden);
      
    const modeInput = document.getElementById("modeOfPayment");
  const rfpBtn = document.getElementById("rfp_btn");

  function toggleRfpBtn() {
    if (modeInput.value.trim() !== "") {
      rfpBtn.disabled = false;
    } else {
      rfpBtn.disabled = true;
    }
  }

  // Run on load (in case input has pre-filled value)
  toggleRfpBtn();

  // Run on every input change
  modeInput.addEventListener("input", toggleRfpBtn);
  // Toggle Sidebar
  document.getElementById('toggleSidebar')?.addEventListener('click', function () {
    document.getElementById('sidebarMenu')?.classList.toggle('collapsed');
  });
  
    // Logout Handler
    document.getElementById('logoutLink')?.addEventListener('click', function (e) {
    e.preventDefault();
    const modal = new bootstrap.Modal(document.getElementById('logoutModal'), {
      backdrop: 'static',
      keyboard: false
    });
    modal.show();
    setTimeout(() => window.location.href = '../../logout.php', 2000);
  });
  document.addEventListener("DOMContentLoaded", function () {

const daySelect = document.getElementById("paymentDueDay");
const applyBtn  = document.getElementById("applyChangesBtn");
const currentText = document.getElementById("currentDueDateText");

// Safety check — stop if elements missing
if (!daySelect || !applyBtn) return;

const originalDay = daySelect.value;

// Show button only if changed
daySelect.addEventListener("change", function () {
  if (daySelect.value !== originalDay) {
    applyBtn.classList.remove("d-none");
  } else {
    applyBtn.classList.add("d-none");
  }
});

// Submit updated payment due date via AJAX
applyBtn.addEventListener("click", function (e) {
  e.preventDefault();

  const newDay = parseInt(daySelect.value, 10);
  const formData = new FormData();

  // Existing values
  formData.append("payment_due_day", newDay);
  formData.append("apply_changes", "1");

  // Used for create_contract update
  formData.append(
    "contract_id",
    <?= json_encode($contract['id'] ?? 0) ?>
  );

  // Used for escalation update
  formData.append(
    "contract_number",
    <?= json_encode($contract['contract_number'] ?? '') ?>
  );

  // Disable button while processing
  applyBtn.disabled = true;

  fetch("update_due_date.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.text())
  .then(responseHTML => {

    const container = document.getElementById("update-message-container");
    if (container) container.innerHTML = responseHTML;

    applyBtn.classList.add("d-none");
    applyBtn.disabled = false;

    if (currentText) {
      const suffix = ordinalSuffix(newDay);
      currentText.innerHTML =
        `Every <strong>${newDay}${suffix}</strong> day of the month`;
    }
  })
  .catch(error => {
    console.error("Update failed:", error);
    applyBtn.disabled = false;
  });
});

// Helper: ordinal suffix
function ordinalSuffix(n) {
  if (n % 100 >= 11 && n % 100 <= 13) return "th";
  switch (n % 10) {
    case 1: return "st";
    case 2: return "nd";
    case 3: return "rd";
    default: return "th";
  }
}
});

   document.addEventListener("DOMContentLoaded", function () {
    const select = document.getElementById("paymentDueDay");
    const button = document.getElementById("applyChangesBtn");

    const originalValue = select.value;

    select.addEventListener("change", function () {
      if (select.value !== originalValue) {
        button.classList.remove("d-none");
      } else {
        button.classList.add("d-none");
      }
    });
  });

document.getElementById('paymentDueDay').addEventListener('change', function () {
  document.getElementById('applyChangesBtn').classList.remove('d-none');
});
</script>
</body>
</html>
