<?php
session_start();
include('../../config/config.php');

// Redirect if not logged in
if (!isset($_SESSION['user_name']) && !isset($_SESSION['admin_name'])) {
    header('Location: login_form.php');
    exit();
}
$mainzone = $_SESSION['mainzone'] ?? '';
$region = $_SESSION['region'] ?? '';
$area = $_SESSION['area'] ?? '';

// Fetch branches matching region and area
$branchOptions = [];
$sql = "SELECT branch_id, branch_name, corporate_name, bir_rdo, kpx_code, code AS branch_code, zone 
        FROM branch_insurance 
        WHERE region = ? AND area = ? 
        ORDER BY branch_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $region, $area);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $branchOptions[] = $row;
}
$lessorOptions = [];
$sql = "SELECT id, first_name, middle_name, last_name, gender, mobile_number, corporate_name, area, lessor_type 
        FROM lessor_profile 
        WHERE main_zone = ? AND region = ? AND area = ?
        ORDER BY lessor_type ASC, first_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $mainzone, $region, $area);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $fullName = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
    $displayText = $fullName;
    if (!empty($row['corporate_name'])) {
        $displayText = $row['corporate_name'];  // Only show corporate name for corporate/LGU
    }

    $lessorOptions[$row['lessor_type']][] = [
        'id' => $row['id'],
        'text' => $displayText,
        'first_name' => $row['first_name'],
        'middle_name' => $row['middle_name'],
        'last_name' => $row['last_name'],
        'gender' => $row['gender'],
        'mobile_number' => $row['mobile_number'],
        'corporate_name' => $row['corporate_name'], // Add this
        'lessor_type' => $row['lessor_type']
    ];
}

$contractNumber = '';
$branch_id = $_SESSION['branch_id'] ?? null;

if ($branch_id) {
    // Get the max series for this branch_id
    $stmt = $conn->prepare("SELECT MAX(series) AS max_series FROM create_contract WHERE branch_id = ?");
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $nextSeries = ($row && $row['max_series']) ? ($row['max_series'] + 1) : 1;
    $contractNumber = "COL-{$branch_id}-{$nextSeries}";
}

?>

    <!DOCTYPE html>
    <html lang="en">
        <head>
           <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="generator" content="Mobirise v5.9.13, mobirise.com">
            <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
            <link rel="shortcut icon" href="../../assets/images/mlw-logo-96x96.png" type="image/x-icon">
            <meta name="description" content="">
            <title>ML Rental -Create Contract</title>
            <!-- ✅ Local Google Font -->
            <link href="../../assets/css/poppins.css" rel="stylesheet">

            <!-- ✅ Local Bootstrap CSS -->
            <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">

            <!-- ✅ Local Bootstrap Icons -->
            <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">

            <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
            <!-- ✅ Your custom CSS should come AFTER font import -->
            <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <style>
    .contract-container {
  background: #fff;
  padding: 8px 40px;
  border-radius: 1rem;
  box-shadow: 0 0 20px rgba(0,0,0,0.05);
  color: #333;
  position: relative;
}

.step-indicator {
  display: flex;
  justify-content: center;
  gap: 15px;
}

.step {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background-color: #eee;
  color: #333;
  font-weight: 600;
  font-size: 1.1rem;
  display: flex;
  justify-content: center;
  align-items: center;
  border: 2px solid transparent;
  transition: all 0.3s ease;
  position: relative;
}

.step::after {
  content: "";
  position: absolute;
  height: 4px;
  width: 100%;
  background: #ccc;
  top: 50%;
  left: 50%;
  z-index: -1;
  transform: translate(-50%, -50%);
}

.step:first-child::after {
  content: none;
}

.step.active {
  background-color: #d70c0c;
  color: #fff;
  border-color: #d70c0c;
}

.form-page {
  display: none;
  animation: fadeIn 0.5s ease;
}

.form-page.active {
  display: block;
}

.form-label {
  font-weight: 500;
}

.form-select, .form-control {
  border-radius: 0.5rem;
  border: 1px solid #ccc;
  padding: 10px 14px;
}

.form-navigation {
  text-align: center;
  margin-top: 30px;
}

#prevBtn, #nextBtn {
  min-width: 120px;
  padding: 10px 24px;
  font-weight: 500;
  border-radius: 2rem;
}

#prevBtn {
  background: transparent;
  color: #333;
  border: 2px solid #ccc;
}

#prevBtn:hover {
  background-color: #ccc;
}

#nextBtn {
  background-color: #d70c0c;
  color: #fff;
  border: none;
}

#nextBtn:hover {
  background-color: #b80909;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}
.btn-close {
  filter: invert(33%) sepia(95%) saturate(6385%) hue-rotate(349deg) brightness(95%) contrast(111%);
}
select.form-select optgroup {
  background-color: #f8f9fa;
  font-weight: 600;
  color: #333;
  padding: 5px;
}
.is-invalid {
  border-color: #dc3545;
  background-color: #ffe6e6;
}
table input.form-control {
  min-width: 80px;
  font-size: 10px;
  padding: 0.25rem 0.4rem;
  box-sizing: border-box;
}
@media (max-width: 768px) {
  table input.form-control {
    font-size: 9px;
    padding: 0.2rem 0.3rem;
  }
}
.modern-modal {
    border-radius: 1.25rem;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
  }

  .modal-header .modal-title {
    font-size: 1.25rem;
  }

  .modal-footer .btn {
    min-width: 120px;
    font-weight: 500;
  }

  .btn-primary {
    background-color: #4e73df;
    border-color: #4e73df;
  }

  .btn-primary:hover {
    background-color: #3a5ecb;
    border-color: #3a5ecb;
  }

  .btn-outline-secondary {
    border-color: #ced4da;
    color: #6c757d;
  }

  .btn-outline-secondary:hover {
    background-color: #f8f9fa;
    border-color: #ced4da;
    color: #495057;
  }
  .border-danger {
    border: 1px solid #d70c0c !important;
}
.swal2-container {
    z-index: 2000 !important;
}
.active-lang-pill {
    background: #0f172a !important;
    color: #ffffff !important;
    box-shadow: 0 2px 4px rgba(15, 23, 42, 0.15);
  }
  .wrapper-checkbox:hover {
    border-color: #94a3b8 !important;
    background: #f8fafc !important;
  }
  #proceedToEscalationBtn:not([disabled]):hover {
    background-color: #dc2626 !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2) !important;
  }
</style>    
</head>
<body>
<?php include ('navbar.php'); ?>
<div id="mainContent">
<button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
            <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
            <span class="fw-normal">Menu</span>
        </button>
<div class="contract-container mx-auto" style="max-width: 700px;">
    <!-- Back Button -->
    <div class="mb-3">
        <a href="select_contract_type.php" class="btn btn-outline-danger d-inline-flex align-items-center rounded-pill px-2 py-2">
            <i class="bi bi-arrow-left me-2"></i> Back to selection of Contract Type
        </a>
    </div>
  <h3 class="text-center fw-bold mb-4">Registration of Contract of Lease</h3>

  <!-- Step Indicator -->
  <div class="step-indicator mb-4">
    <div class="step active" id="step1"><i class="bi bi-geo-alt"></i></div>
    <div class="step" id="step2"><i class="bi bi-person"></i></div>
    <div class="step" id="step3"><i class="bi-calculator"></i></div>
    <div class="step" id="step4"><i class="bi bi-graph-up"></i></div>
    <div class="step" id="step5"><i class="bi bi-cloud-upload"></i></div>
  </div>

  <form id="contractForm">
   <!-- Page 1 -->
    <div class="form-page active" id="page1">
      <div class="mb-3">
          <label for="mainzone" class="form-label">
              <i class="bi-grid-1x2 me-1 text-danger"></i> Main Zone <span class="text-danger">*</span>
          </label>
          <input type="text" class="form-control" id="mainzone" name="mainzone" value="<?= htmlspecialchars($mainzone) ?>" readonly>
      </div>
      <div class="mb-3">
          <label for="region" class="form-label">
              <i class="bi bi-globe-asia-australia me-1 text-danger"></i> Region <span class="text-danger">*</span>
          </label>
          <input type="text" class="form-control" id="region" name="region" value="<?= htmlspecialchars($region) ?>" readonly>
      </div>

      <div class="mb-3">
          <label for="area" class="form-label">
              <i class="bi bi-map me-1 text-danger"></i> Area <span class="text-danger">*</span>
          </label>
          <input type="text" class="form-control" id="area" name="area" value="<?= htmlspecialchars($area) ?>" readonly>
      </div>

      <div class="mb-3">
          <label for="branch" class="form-label">
              <i class="bi bi-building me-1 text-danger"></i> Branch <span class="text-danger">*</span>
          </label>
          <select class="form-select" id="branch" name="branch" required>
              <option selected disabled>Select Branch</option>
              <?php
              $sql = "SELECT branch_id, branch_name, corporate_name, bir_rdo, kpx_code, code AS branch_code, zone 
                      FROM branch_insurance 
                      WHERE region = ? AND area = ? AND ml_matic_status = 'Active'
                      ORDER BY branch_name ASC";
              $stmt = $conn->prepare($sql);
              $stmt->bind_param("ss", $region, $area);
              $stmt->execute();
              $result = $stmt->get_result();

              while ($row = $result->fetch_assoc()):
                  // Use fallback value 0 for empty/null fields
                  $kpxCode = !empty($row['kpx_code']) ? $row['kpx_code'] : '0';
                  $branchCode = !empty($row['branch_code']) ? $row['branch_code'] : '0';
                  $zone = !empty($row['zone']) ? $row['zone'] : '0';
                  $corporateName = !empty($row['corporate_name']) ? $row['corporate_name'] : '0';
                  $rdo = !empty($row['bir_rdo']) ? $row['bir_rdo'] : '';
                  $branchName = !empty($row['branch_name']) ? $row['branch_name'] : '0';
              ?>
                  <option 
                      value="<?= htmlspecialchars($row['branch_id']) ?>"
                      data-branch-name="<?= htmlspecialchars($branchName) ?>"
                      data-corporate="<?= htmlspecialchars($corporateName) ?>"
                      data-rdo="<?= htmlspecialchars($rdo) ?>"
                      data-kpx-code="<?= htmlspecialchars($kpxCode) ?>"
                      data-branch-code="<?= htmlspecialchars($branchCode) ?>"
                      data-zone="<?= htmlspecialchars($zone) ?>"
                  >
                      <?= htmlspecialchars($branchName) ?>
                  </option>
              <?php endwhile; ?>

          </select>
      </div>
      <input type="hidden" name="kpx_code" id="kpx_code" value="<?= htmlspecialchars($_POST['kpx_code'] ?? '') ?>">
      <input type="hidden" name="branch_code" id="branch_code" value="<?= htmlspecialchars($_POST['branch_code'] ?? '') ?>">
      <input type="hidden" name="branch_id" id="branch_id" value="<?= htmlspecialchars($_POST['branch_id'] ?? '') ?>">
      <input type="hidden" name="zone" id="zone" value="<?= htmlspecialchars($_POST['zone'] ?? '') ?>">
      <input type="hidden" name="branch_name" id="branch_name" value="<?= htmlspecialchars($_POST['branch_name'] ?? '') ?>">

      <div class="mb-3">
          <label for="corporate_name" class="form-label">
              <i class="bi bi-bank me-1 text-danger"></i> ML Corporate Name <span class="text-danger">*</span>
          </label>
          <input type="text" class="form-control" id="corporate_name" name="corporate_name" readonly placeholder="Autofilled">
      </div>

      <div class="mb-3">
          <label for="rdo" class="form-label">
              <i class="bi bi-person-badge me-1 text-danger"></i> RDO 
          </label>
          <input type="text" class="form-control" id="rdo" name="rdo" placeholder="Autofilled" autocomplete="off">
      </div>

      <div class="mb-3">
        <label for="contract_number" class="form-label">
            <i class="bi bi-file-earmark-text me-1 text-danger"></i> Contract Number <span class="text-danger">*</span>
        </label>
        <input type="text" class="form-control" id="contract_number" name="contract_number" placeholder="Autofilled" readonly value="<?= htmlspecialchars($contractNumber ?? '') ?>">
      </div>
    </div>
    <div class="form-page" id="page2">
      <!-- ADDED: Lessor Type Dropdown -->
      <div class="mb-4">
          <label for="lessorType" class="form-label">
              <i class="bi bi-building me-1 text-danger"></i> Lessor Type <span class="text-danger">*</span>
          </label>
          <select class="form-select border-danger border-2" id="lessorType" name="lessor_type" onchange="toggleLessorFields()" autocomplete="off" required>
              <option value="" selected disabled>SELECT LESSOR TYPE</option>
              <option value="Individual">SOLE PROPRIETORSHIP</option>
              <option value="Partnership">PARTNERSHIP</option>
              <option value="Corporate">CORPORATE</option>
              <option value="LGU">LOCAL GOVERNMENT UNIT</option>
          </select>
      </div>
      <!-- END ADDED CODE -->

      <div id="lessorContainer">
          <div class="mb-3 lessor-group" id="lessor-group-1">
              <label for="lessor1" class="form-label">
                  <i class="bi bi-person-lines-fill me-1 text-danger"></i> Lessor Name <span class="text-danger">*</span>
              </label>
              <select class="form-select" id="lessor1" name="lessor1" required>
                  <option selected disabled>Select Lessor</option>
                  <?php foreach ($lessorOptions as $type => $group): ?>
                      <optgroup label="<?= htmlspecialchars(strtoupper($type)) ?>">
                          <?php foreach ($group as $lessor): ?>
                            <option
                              value="<?= htmlspecialchars($lessor['id']) ?>"
                              data-firstname="<?= htmlspecialchars($lessor['first_name']) ?>"
                              data-middlename="<?= htmlspecialchars($lessor['middle_name']) ?>"
                              data-lastname="<?= htmlspecialchars($lessor['last_name']) ?>"
                              data-gender="<?= htmlspecialchars($lessor['gender']) ?>"
                              data-mobile="<?= htmlspecialchars($lessor['mobile_number']) ?>"
                              data-type="<?= htmlspecialchars($lessor['lessor_type']) ?>"
                              data-corporate="<?= htmlspecialchars($lessor['corporate_name']) ?>"
                          >
                              <?= htmlspecialchars($lessor['text']) ?>
                          </option>

                          <?php endforeach; ?>
                      </optgroup>
                  <?php endforeach; ?>
              </select>

              <input type="hidden" name="lessors[0][firstname]" id="lessor1_firstname">
              <input type="hidden" name="lessors[0][middlename]" id="lessor1_middlename">
              <input type="hidden" name="lessors[0][lastname]" id="lessor1_lastname">
              <input type="hidden" name="lessors[0][gender]" id="lessor1_gender">
              <input type="hidden" name="lessors[0][mobile_number]" id="lessor1_mobile">
              <input type="hidden" name="corporate_lessor" id="corporate_lessor">

          </div>
      </div>


      <div class="text-end">
          <button type="button" id="addLessorBtn" class="btn btn-outline-primary rounded-pill px-4">
              <i class="bi bi-person-plus-fill me-1"></i> Add Another Lessor
          </button>
      </div>
      <hr>
      <div class="text-end">
    <button type="button" id="addAuthorizeToClaim" class="btn btn-outline-primary rounded-pill px-4">
        <i class="bi bi-person-plus-fill me-1"></i> Add Authorize to Claim
    </button>
</div>

<div id="authorizeContainer" class="mt-4 d-none">
    <h6 class="fw-bold mb-3">
        <i class="bi bi-person-bounding-box me-1 text-danger"></i> Authorized to Claim
    </h6>

    <div class="mb-3">
        <label class="form-label">First Name <span class="text-danger">*</span></label>
        <input type="text" name="authorize_firstname" class="form-control" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Middle Name</label>
        <input type="text" name="authorize_middlename" class="form-control">
    </div>

    <div class="mb-3">
        <label class="form-label">Last Name <span class="text-danger">*</span></label>
        <input type="text" name="authorize_lastname" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Gender</label>
      <select name="authorize_gender" class="form-select">
          <option value="">-- Select --</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Mobile Number</label>
      <input type="text" 
            name="authorize_mobileNumber" 
            class="form-control"
            inputmode="numeric"
            pattern="^[0-9]{11}$"
            maxlength="11"
            placeholder="09XXXXXXXXX"
            title="Enter exactly 11 digits (e.g. 09123456789)"
            autocomplete="off"
            oninput="this.value = this.value.replace(/[^0-9]/g, '');">
    </div>


    <script>
      // Enforce numeric-only & max 11 digits in real-time
      document.querySelector('input[name="authorize_mobileNumber"]').addEventListener('input', function() {
          this.value = this.value.replace(/\D/g, '').slice(0, 11);
      });
   </script>

    <div class="text-end">
        <button type="button" id="cancelAuthorizeToClaim" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-x-circle me-1"></i> Cancel
        </button>
    </div>
</div>

  </div>

 <!-- Page 3 -->
<div class="form-page" id="page3">
  <div class="mb-3">
      <label for="effectivity_date" class="form-label">
          <i class="bi bi-calendar-check me-1 text-danger"></i> Effectivity Date <span class="text-danger">*</span>
      </label>
      <input type="date" class="form-control" id="effectivity_date" name="effectivity_date" required>
  </div>

  <div class="mb-3">
      <label for="expiry_date" class="form-label">
          <i class="bi bi-calendar-x me-1 text-danger"></i> Expiry Date <span class="text-danger">*</span>
      </label>
      <input type="date" class="form-control" id="expiry_date" name="expiry_date" required>
  </div>

  <div class="mb-3">
      <label for="monthly_due_display" class="form-label">
          <i class="bi bi-calendar2-week me-1 text-danger"></i> Monthly Due Date 
          <small class="text-muted">(Auto from Effectivity Date)</small>
      </label>
      <input type="text" class="form-control" id="monthly_due_display" disabled readonly>
      <input type="hidden" id="payment_due_date" name="payment_due_date">
  </div>

  <div class="mb-3">
  <label for="monthly_rental" class="form-label">
    <i class="bi bi-currency-dollar me-1 text-danger"></i>
    Monthly Rental <span class="text-danger">*</span>
    <small class="text-muted">(Triggers VAT/WTax computation)</small>
  </label>
  <input type="number" class="form-control border-danger border-2" id="monthly_rental" name="monthly_rental" step="0.01" min="0" required>
  
  <!-- Added Specification Note -->
  <div class="form-text text-muted mt-2">
    <i class="bi bi-info-circle-fill text-info me-1"></i>
    <strong>Input Guide:</strong> 
    <ul class="mb-0 mt-1 ps-3">
      <li>If <strong>Vatable (Inclusive)</strong>: Enter the <em>Gross Amount</em> (VAT is already included in this figure).</li>
      <li>If <strong>Vatable (Exclusive)</strong>: Enter the <em>Net Amount</em> (VAT will be added on top of this figure).</li>
    </ul>
  </div>
</div>

  <div class="mb-3">
    <label for="vat_type" class="form-label">
      <i class="bi bi-receipt-cutoff me-1 text-danger"></i>
      VAT Type <span class="text-danger">*</span>
      <small class="text-muted">(Triggers VAT computation)</small>
    </label>
    <select class="form-select border-danger border-2" id="vat_type" name="vat_type" required>
      <option selected disabled>Select VAT Type</option>
      <option value="Vatable">Vatable</option>
      <option value="Non-Vatable">Non-Vatable</option>
      <option value="Vat-Exempt">VAT Exempt</option>
    </select>
  </div>

  <div class="mb-3">
    <label for="net_vat_amount" class="form-label">
      <i class="bi bi-cash-coin me-1 text-danger"></i> Net of VAT Amount
    </label>
    <input type="number" class="form-control" id="net_vat_amount" name="net_vat_amount" step="0.01" min="0" readonly>
  </div>

  <div class="mb-3">
    <label for="vat_amount" class="form-label">
      <i class="bi bi-percent me-1 text-danger"></i> VAT Amount (12%)
    </label>
    <input type="number" class="form-control" id="vat_amount" name="vat_amount" readonly>
  </div>

  <div class="mb-3">
  <label for="wtax_type" class="form-label">
    <i class="bi bi-cash-stack me-1 text-danger"></i>
    WTax Type <small class="text-muted">(Triggers WTax computation)</small>
  </label>
  <select class="form-select border-danger border-2" id="wtax_type" name="wtax_type">
    <option selected disabled>Select WTax Type</option>
    <option value="less_wtax">Less WTax</option>
    <option value="net_wtax">Net WTax</option>
  </select>
</div>

<div class="mb-3">
  <label for="wtax_percentage" class="form-label">
    <i class="bi bi-123 me-1 text-danger"></i>
    WTax Percentage (%) <small>(1–5)</small>
    <small class="text-muted d-block">Triggers WTax computation</small>
  </label>
  <select class="form-select border-danger border-2" id="wtax_percentage" name="wtax_percentage">
    <option selected disabled>Select WTax %</option>
    <option value="1" disabled>1%</option>
    <option value="2" disabled>2%</option>
    <option value="3" disabled>3%</option>
    <option value="4" disabled>4%</option>
    <option value="5">5%</option>
  </select>
</div>

  <div class="mb-3">
    <label for="wtax_amount" class="form-label">
      <i class="bi bi-cash-stack me-1 text-danger"></i> Wtax Amount
    </label>
    <input type="number" class="form-control" id="wtax_amount" name="wtax_amount" step="0.01" min="0" readonly>
  </div>

  <div class="mb-3">
    <label for="total_amount" class="form-label">
      <i class="bi bi-cash-stack me-1 text-danger"></i> Total Amount
    </label>
    <input type="number" class="form-control" id="total_amount" name="total_amount" readonly>
  </div>

  <div class="mb-3">
    <label for="amount_to_lessor" class="form-label">
      <i class="bi bi-cash-stack me-1 text-danger"></i> Amount to Lessor
    </label>
    <input type="number" class="form-control" id="amount_to_lessor" name="amount_to_lessor" step="0.01" min="0" readonly>
  </div>
</div>


<!-- Page 4 -->
<div class="form-page" id="page4">
  <div class="d-flex flex-column align-items-center my-5">
    <!-- Instruction Text -->
    <p class="mb-3 text-muted fst-italic text-center">
      Click the icon below to process the escalation
    </p>

    <!-- Centered Icon Button -->
    <!-- Button with Icon -->
  <button type="button" 
          id="openEscalationModal"
          class="btn btn-white border border-2 shadow rounded-circle p-4 d-flex align-items-center justify-content-center"
          title="Process Escalation"
          style="width: 80px; height: 80px;">
    <i class="bi bi-graph-up-arrow fs-3 text-danger" id="escalationIcon"></i>
  </button>
    <!-- Advance Rental Block -->
    <div class="container mt-4 d-none" id="advanceRentalSection">
      <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-header bg-white border-bottom-0 rounded-top-4 d-flex align-items-center">
          <i class="bi bi-cash-coin text-primary fs-4 me-2"></i>
          <h6 class="mb-0 fw-semibold text-primary">Advance Rental</h6>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label for="advanceRental" class="form-label">Amount (₱)</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-currency-peso"></i></span>
                <input type="number" class="form-control" name="advanceRental" id="advanceRental" placeholder="0.00">
              </div>
            </div>
            <div class="col-md-4">
              <label for="advanceFrom" class="form-label">From</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                <input type="date" class="form-control" name="advanceFrom" id="advanceFrom">
              </div>
            </div>
            <div class="col-md-4">
              <label for="advanceTo" class="form-label">To</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                <input type="date" class="form-control" name="advanceTo" id="advanceTo">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Security Deposit Block -->
    <div class="container d-none" id="securityDepositSection">
      <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-header bg-white border-bottom-0 rounded-top-4 d-flex align-items-center">
          <i class="bi bi-shield-lock text-success fs-4 me-2"></i>
          <h6 class="mb-0 fw-semibold text-success">Security Deposit</h6>
        </div>
        <div class="card-body">
          <div class="row g-3 mb-2">
            <div class="col-md-4">
              <label for="securityDeposit" class="form-label">Amount (₱)</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-currency-peso"></i></span>
                <input type="number" class="form-control" name="securityDeposit" id="securityDeposit" placeholder="0.00">
              </div>
            </div>
            <div class="col-md-4">
              <label for="depositType" class="form-label">Deposit Type</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-ui-checks"></i></span>
                <select class="form-select" name="depositType" id="depositType">
                  <option value="">-- Select Type --</option>
                  <option value="refundable">Refundable</option>
                  <option value="consumable">Consumable</option>
                </select>
              </div>
            </div>
          </div>

          <div class="row g-3 d-none" id="consumableDates">
            <div class="col-md-4">
              <label for="depositFrom" class="form-label">From</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                <input type="date" class="form-control" name="depositFrom" id="depositFrom">
              </div>
            </div>
            <div class="col-md-4">
              <label for="depositTo" class="form-label">To</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                <input type="date" class="form-control" name="depositTo" id="depositTo">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>  
  </div>
</div>
<div class="modal fade"
     id="escalationAgreementModal"
     tabindex="-1"
     aria-labelledby="escalationAgreementModalLabel"
     aria-hidden="true"
     data-bs-backdrop="static"
     data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow-lg border-0" style="background: #ffffff; overflow: hidden;">
      
      <div class="modal-header rounded-top-4 py-3 px-4 border-bottom" style="background: #0f172a;">
        <h5 class="modal-title d-flex align-items-center fw-bold text-white" id="escalationAgreementModalLabel" style="font-size: 1.1rem; letter-spacing: 0.5px;">
          <i class="bi bi-file-earmark-text text-info me-2 fs-4"></i> <span id="lang-modal-title">Escalation Setup Instructions</span>
        </h5>
        <button type="button" class="btn-close btn-close-white opacity-75" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body px-4 py-4" style="background: #fdfdfd;">
        
        <div class="d-flex justify-content-center mb-4">
          <div class="p-1 bg-light rounded-pill d-inline-flex border" style="box-shadow: inset 0 2px 4px rgba(0,0,0,0.03);">
            <button type="button" class="btn btn-sm rounded-pill px-3 py-1 fw-bold active-lang-pill" id="btn-lang-en" onclick="switchLanguage('en')" style="font-size: 0.75rem; transition: all 0.2s ease;">English</button>
            <button type="button" class="btn btn-sm rounded-pill px-3 py-1 fw-bold text-secondary" id="btn-lang-tl" onclick="switchLanguage('tl')" style="font-size: 0.75rem; transition: all 0.2s ease;">Tagalog</button>
            <button type="button" class="btn btn-sm rounded-pill px-3 py-1 fw-bold text-secondary" id="btn-lang-by" onclick="switchLanguage('by')" style="font-size: 0.75rem; transition: all 0.2s ease;">Bisaya</button>
          </div>
        </div>

        <div class="mb-3 text-secondary fw-medium" id="lang-modal-sub" style="font-size: 0.9rem; line-height: 1.5;">
          Please read how the rental price increase works before starting.
        </div>
        
        <div class="p-4 mb-4 text-secondary" style="font-size: 0.85rem; max-height: 280px; overflow-y: auto; background: linear-gradient(145deg, #ffffff, #f8fafc); border: 1px solid #e2e8f0; border-radius: 16px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.02);">
          <h6 class="fw-bold text-dark mb-3 d-flex align-items-center" id="lang-rules-title" style="letter-spacing: 0.3px;">
            <i class="bi bi-shield-check text-success me-2 fs-5"></i> Important Rules:
          </h6>
          <ol class="mb-0 ps-3 d-flex flex-column gap-2" id="lang-rules-list" style="line-height: 1.6; color: #475569; font-weight: 500;">
            <li>The system automatically computes your yearly rent increase using either a percentage (%) or a fixed amount.</li>
            <li>Make sure the lease start date and end date are correct before creating the table.</li>
            <li>You can change the dates manually, but avoid overlapping dates to prevent wrong billing.</li>
            <li>Typing a percentage will auto-calculate the amount, and changing the amount will adjust the percentage.</li>
            <li>Double-check the computed VAT, Withholding Tax (WTax), and final amount to the owner for accuracy.</li>
            <li>Once saved, these amounts will be locked and used for future billing and contracts.</li>
          </ol>
        </div>

        <div class="form-check d-flex align-items-center p-3 border rounded-3 wrapper-checkbox shadow-sm" style="background: #ffffff; border-color: #cbd5e1; transition: all 0.3s ease;">
          <input class="form-check-input ms-1 me-3 fs-5 border-secondary" type="checkbox" id="agreeEscalationCheckbox" style="cursor: pointer; min-width: 1.25rem;">
          <label class="form-check-label fw-bold text-dark mt-0" for="agreeEscalationCheckbox" id="lang-checkbox-label" style="cursor: pointer; font-size: 0.85rem; line-height: 1.4;">
            I have read and agree to follow these configuration rules.
          </label>
        </div>
      </div>

      <div class="modal-footer px-4 py-3 d-flex justify-content-between border-top-0 pt-0" style="background: #fdfdfd;">
        <button type="button" class="btn btn-light rounded-pill px-4 fw-bold border text-secondary" data-bs-dismiss="modal" style="font-size: 0.85rem; transition: all 0.2s ease;">Cancel</button>
        <button type="button" id="proceedToEscalationBtn" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm" disabled style="font-size: 0.85rem; background-color: #ef4444; border-color: #ef4444; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
          <span>Proceed to Setup</span> <i class="bi bi-arrow-right ms-1"></i>
        </button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade"
     id="escalationModal"
     tabindex="-1"
     aria-labelledby="escalationModalLabel"
     aria-hidden="true"
     data-bs-backdrop="false"
     data-bs-keyboard="false">

  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 90%;">
    <div class="modal-content rounded-4 shadow-lg border-0">
      
      <div class="modal-header bg-danger text-white rounded-top-4 py-3 px-4">
        <h5 class="modal-title d-flex align-items-center text-white" id="escalationModalLabel">
          <i class="bi bi-graph-up-arrow me-2 fs-4"></i> Escalation Details Summary
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body px-4 py-3">
      <div class="table-responsive" style="max-height: 70vh; overflow-x: auto;">
          <table class="table table-hover align-middle text-center table-sm border rounded-3 overflow-hidden" 
                 style="font-size: 10px; border-collapse: collapse; white-space: nowrap;">
            <thead class="bg-danger text-white sticky-top">
              <tr>
                <th><i class="bi bi-calendar-range"></i> Year</th>
                <th><i class="bi bi-calendar-event"></i> Start</th>
                <th><i class="bi bi-calendar-x"></i> End</th>
                <th><i class="bi bi-bar-chart-line"></i> Escalation %</th>
                <th><i class="bi bi-cash-coin"></i> Fixed Amt</th>
                <th><i class="bi bi-arrow-up-right"></i> Increase</th>
                <th><i class="bi bi-currency-dollar"></i> Rental</th>
                <th><i class="bi bi-percent"></i> VAT</th>
                <th><i class="bi bi-receipt"></i> Net VAT</th>
                <th><i class="bi bi-tag"></i> WTax Type</th>
                <th><i class="bi bi-123"></i> WTax %</th>
                <th><i class="bi bi-cash-stack"></i> WTax Amt</th>
                <th><i class="bi bi-wallet2"></i> To Lessor</th>
                <th><i class="bi bi-calendar3-range"></i> Yearly Total</th>
                <th><i class="bi bi-gear"></i> Action</th>
              </tr>
            </thead>
            <tbody id="escalationTableBody">
              </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer px-4 py-3 d-flex justify-content-between">
        <button type="button" id="saveEscalations" class="btn btn-danger rounded-pill px-4">
          <i class="bi bi-save2 me-1"></i> Save Changes
        </button>
      </div>
    </div>
  </div>
</div>
<!-- Page 5 -->
<div class="form-page" id="page5">
  <div class="mb-3">
    <label class="form-label">Upload Contract Attachments (PDF only, Max: 15)</label>

    <div id="attachmentContainer">
      <div class="input-group mb-2 attachment-group">
        <input type="file" name="attachments[]" accept="application/pdf" class="form-control" required>
        <button type="button" class="btn btn-danger remove-attachment">Remove</button>
      </div>
    </div>

    <button type="button" id="addAttachmentBtn" class="btn btn-outline-primary">
      <i class="bi bi-plus-circle me-1"></i> Add Attachment
    </button>
    <div id="attachmentLimitMsg" class="form-text text-danger d-none">
      Maximum of 15 attachments allowed.
    </div>
  </div>

  <!-- ✅ Notarization Question -->
  <div class="mb-3">
    <label class="form-label">Was the contract notarized?</label>
    <div>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="notarized" id="notarizedYes" value="Yes" required>
        <label class="form-check-label" for="notarizedYes">Yes</label>
      </div>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="radio" name="notarized" id="notarizedNo" value="No">
        <label class="form-check-label" for="notarizedNo">No</label>
      </div>
    </div>
  </div>
  <div class="mb-3">
    <label for="modeOfPayment" class="form-label fw-bold">
        <i class="bi bi-wallet2 me-1 text-danger"></i> Select Mode of Payment
    </label>
    <select name="modeOfPayment" id="modeOfPayment" class="form-select" required>
        <option value="" selected disabled>-- Choose Payment Method --</option>
        
        <option value="PAYMENT SOLUTION">PAYMENT SOLUTION</option>
        <option value="CASH">CASH</option>

        <option value="PDC">PDC (Post-Dated Check)</option>
        
        <option value="WALLET" disabled>WALLET (Coming Soon)</option>
        <option value="RTA" disabled>RTA (Disabled)</option>
    </select>
    <div class="form-text">
        Please note: Wallet and RTA options are currently unavailable.
    </div>
</div>
</div>

    <!-- Navigation Buttons -->
    <div class="form-navigation mt-2">
      <button type="button" id="prevBtn" class="btn btn-outline-secondary me-2">Previous</button>
      <button type="button" id="nextBtn" class="btn btn-danger" disabled>Next</button>
      <input type="hidden" name="proceed_save" id="proceedSaveInput" value="">
    </div>
  </form>
</div>
</div>
<!-- Enhanced Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4" style="background-color: #fefefe;">
      <div class="modal-body">
        <div class="text-center mb-3">
          <i class="bi bi-box-arrow-right text-danger" style="font-size: 3rem;"></i>
        </div>
        <h5 class="mb-2 text-dark">Logging Out</h5>
        <p class="text-muted mb-3">Please wait while we securely log you out...</p>
        <div class="progress" style="height: 8px;">
          <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 100%;"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="confirmSubmitModal" tabindex="-1" aria-labelledby="confirmSubmitModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-sm rounded-4" style="background-color: #fff; color: #333;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-semibold" id="confirmSubmitModalLabel" style="color: #333;">
                    <i class="bi bi-question-circle-fill me-2 text-danger"></i> Confirm Save
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body text-center">
                <p class="fs-6" style="color: #555;">
                    Do you want to proceed with saving this contract?
                </p>
            </div>

            <div class="modal-footer border-0 justify-content-center pt-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 me-2"
                    data-bs-dismiss="modal" style="color: #333; border-color: #d70c0c;">
                    <i class="bi bi-x-circle me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger rounded-pill px-4 py-2" id="confirmSaveBtn"
                    style="background-color: #d70c0c;">
                    <i class="bi bi-check-circle me-1"></i> Yes, Save Contract
                </button>
            </div>
        </div>
    </div>
</div>
<script src="../../assets/sweetalert2/dist/sweetalert2.min.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
  const translations = {
    en: {
      title: "Escalation Setup Instructions",
      sub: "Please read how the rental price increase works before starting.",
      rulesTitle: "Important Rules:",
      rules: [
        "The system automatically computes your yearly rent increase using either a percentage (%) or a fixed amount.",
        "Make sure the lease start date and end date are correct before creating the table.",
        "You can change the dates manually, but avoid overlapping dates to prevent wrong billing.",
        "Typing a percentage will auto-calculate the amount, and changing the amount will adjust the percentage.",
        "Double-check the computed VAT, Withholding Tax (WTax), and final amount to the owner for accuracy.",
        "Once saved, these amounts will be locked and used for future billing and contracts."
      ],
      checkbox: "I have read and agree to follow these configuration rules."
    },
    tl: {
      title: "Mga Tagubilin sa Pag-setup",
      sub: "Paki-basa muna kung paano gagana ang taunang pagtaas ng upa bago magsimula.",
      rulesTitle: "Mahahalagang Tuntunin:",
      rules: [
        "Awtomatikong kukuwentahin ng system ang taunang pagtaas ng upa gamit ang porsyento (%) o permanenteng halaga (fixed amount).",
        "Siguraduhing tama ang panimula at huling petsa ng kontrata bago gawin ang table.",
        "Maaari mong baguhin ang mga petsa, ngunit iwasan ang magkakapatong na araw para hindi magkamali sa singilan.",
        "Kapag naglagay ka ng porsyento, kusa nitong kukuwentahin ang halaga. Kapag binago ang halaga, mag-aadjust din ang porsyento.",
        "Suriing mabuti ang kinuwentang VAT, Withholding Tax (WTax), at ang huling halagang ibabayad sa may-ari.",
        "Kapag nai-save na, ang mga halagang ito ay hindi na mababago at gagamitin para sa susunod na singilan at kontrata."
      ],
      checkbox: "Nabasa ko at sumasang-ayon ako sa mga tuntuning ito."
    },
    by: {
      title: "Mga Instruksyon sa Pag-setup",
      sub: "Palihug basaha kung giunsa ang tinuig nga pagsaka sa abang sa dili pa magsugod.",
      rulesTitle: "Importante nga mga Lagda:",
      rules: [
        "Awtomatiko nga kuwentahon sa system ang tinuig nga pagsaka sa abang gamit ang porsyento (%) o permanenteng kantidad (fixed amount).",
        "Siguroha nga husto ang sinugdanan ug katapusan nga petsa sa kontrata sa dili pa himoon ang table.",
        "Pwede nimo usbon ang mga petsa, apan likayi ang mag-abot nga mga adlaw aron dili masayop ang bayranan.",
        "Kung magbutang ka og porsyento, awtomatiko kining mokuwenta sa kantidad. Kung usbon ang kantidad, mausab sab ang porsyento.",
        "Susiha og maayo ang nakwenta nga VAT, Withholding Tax (WTax), ug ang final nga kantidad nga ibayad sa tag-iya.",
        "Kung ma-save na, dili na kini pwedeng usbon ug mao na kini ang gamiton para sa sunod nga bayranan ug kontrata."
      ],
      checkbox: "Nabasa nako ug uyon ko niining mga lagda."
    }
  };

  function switchLanguage(lang) {
    // UI Button state classes update
    ['en', 'tl', 'by'].forEach(l => {
      const btn = document.getElementById(`btn-lang-${l}`);
      if(btn) {
        btn.classList.remove('active-lang-pill', 'btn-light', 'text-dark');
        btn.classList.add('text-secondary');
      }
    });
    
    const targetBtn = document.getElementById(`btn-lang-${lang}`);
    if(targetBtn) {
      targetBtn.classList.add('active-lang-pill');
      targetBtn.classList.remove('text-secondary');
    }

    // Swapping Text content
    document.getElementById('lang-modal-title').innerText = translations[lang].title;
    document.getElementById('lang-modal-sub').innerText = translations[lang].sub;
    document.getElementById('lang-rules-title').innerHTML = `<i class="bi bi-shield-check text-success me-2 fs-5"></i> ${translations[lang].rulesTitle}`;
    document.getElementById('lang-checkbox-label').innerText = translations[lang].checkbox;
    
    const listContainer = document.getElementById('lang-rules-list');
    listContainer.innerHTML = '';
    translations[lang].rules.forEach(rule => {
      const li = document.createElement('li');
      li.innerText = rule;
      listContainer.appendChild(li);
    });
  }
document.getElementById('lessor1').addEventListener('change', function () {
    const selectedOption = this.options[this.selectedIndex];
    const lessorType = selectedOption.getAttribute('data-type');
    const corporateName = selectedOption.getAttribute('data-corporate') || '';

    document.getElementById('lessor1_type').value = lessorType;
    document.getElementById('corporate_lessor').value = (lessorType === 'Corporate' || lessorType === 'LGU') ? corporateName : '';
});

const addAuthorizeBtn = document.getElementById('addAuthorizeToClaim');
const authorizeContainer = document.getElementById('authorizeContainer');
const cancelAuthorizeBtn = document.getElementById('cancelAuthorizeToClaim');

addAuthorizeBtn.addEventListener('click', () => {
    authorizeContainer.classList.remove('d-none');
    addAuthorizeBtn.classList.add('d-none');
});

cancelAuthorizeBtn.addEventListener('click', () => {
    authorizeContainer.classList.add('d-none');
    addAuthorizeBtn.classList.remove('d-none');
    // Optional: Clear fields on cancel
    authorizeContainer.querySelectorAll('input').forEach(input => input.value = '');
});

const effectivityInput = document.getElementById('effectivity_date');
const expiryInput = document.getElementById('expiry_date');
const branchInput = document.getElementById('branch_id');

function checkContractOverlap() {
    const effectivity = effectivityInput.value;
    const expiry = expiryInput.value;
    const branchId = branchInput.value;

    if (!effectivity || !expiry || !branchId) return;

    fetch('check_date_overlap.php', {
        method: 'POST',
        body: new URLSearchParams({
            branch_id: branchId,
            effectivity_date: effectivity,
            expiry_date: expiry
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.overlap) {
            effectivityInput.classList.add('border', 'border-danger');
            expiryInput.classList.add('border', 'border-danger');

            // Clear invalid dates
            effectivityInput.value = '';
            expiryInput.value = '';

            Swal.fire({
                iconHtml: '<i class="bi bi-exclamation-triangle-fill" style="font-size: 3rem; color: #d70c0c;"></i>',
                title: '<span style="color: #333;">Overlapping Contract Detected</span>',
                html: `
                    <div style="color:#333; font-size: 14px;">
                        <strong>Existing Contract Period:</strong><br>
                        ${data.period}
                    </div>
                `,
                background: '#fff',
                color: '#333',
                confirmButtonColor: '#d70c0c',
                confirmButtonText: 'Okay, I understand',
                customClass: {
                    popup: 'rounded shadow-sm',
                    confirmButton: 'rounded-pill px-4'
                }
            });
        } else {
            effectivityInput.classList.remove('border', 'border-danger');
            expiryInput.classList.remove('border', 'border-danger');
        }
    })
    .catch(error => {
        console.error('Error checking overlap:', error);
    });
}

effectivityInput.addEventListener('change', checkContractOverlap);
expiryInput.addEventListener('change', checkContractOverlap);

effectivityInput.addEventListener('change', checkContractOverlap);
expiryInput.addEventListener('change', checkContractOverlap);

    const effectivityDateInput = document.getElementById('effectivity_date');
    const paymentDueDateInput = document.getElementById('payment_due_date');
    const monthlyDueDisplayInput = document.getElementById('monthly_due_display');

    effectivityDateInput.addEventListener('change', function() {
        const dateValue = this.value;
        if (dateValue) {
            const selectedDate = new Date(dateValue);
            const dayOfMonth = selectedDate.getDate();
            const suffix = (dayOfMonth === 1) ? 'st' :
                           (dayOfMonth === 2) ? 'nd' :
                           (dayOfMonth === 3) ? 'rd' : 'th';
            const displayText = `Every ${dayOfMonth}${suffix} day of the month`;
            
            monthlyDueDisplayInput.value = displayText;
            paymentDueDateInput.value = dayOfMonth; // Hidden input for backend
        }
    });

    document.getElementById('branch').addEventListener('change', function () {
    const selected = this.options[this.selectedIndex];
    const branchId = selected.value;
    const branchCode = selected.getAttribute('data-branch-code') || '0';

    // 1. Reset fields if no branch is selected
    if (!branchId || selected.disabled) {
        const fieldsToReset = ['contract_number', 'corporate_name', 'rdo', 'kpx_code', 'branch_id', 'branch_code', 'zone', 'branch_name'];
        fieldsToReset.forEach(id => document.getElementById(id).value = '');
        return;
    }

    // 2. Map Data Attributes to Inputs
    document.getElementById('corporate_name').value = selected.getAttribute('data-corporate') || '';
    document.getElementById('rdo').value = selected.getAttribute('data-rdo') || '';
    document.getElementById('kpx_code').value = selected.getAttribute('data-kpx-code') || '';
    document.getElementById('branch_id').value = branchId;
    document.getElementById('branch_code').value = branchCode;
    document.getElementById('zone').value = selected.getAttribute('data-zone') || '';
    document.getElementById('branch_name').value = selected.getAttribute('data-branch-name') || '';

    // 3. Fetch the Series
    // Ensure the path to get_next_series.php is correct relative to this file
    fetch(`get_next_series.php?branch_id=${branchId}`)
        .then(response => {
            if (!response.ok) throw new Error('Server returned ' + response.status);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Format: COL-BRANCHCODE-001 (Padded to 3 digits)
                const seriesPadded = data.next_series;
                document.getElementById('contract_number').value = `COL-${branchId}-${seriesPadded}`;
            } else {
                document.getElementById('contract_number').value = 'Error: ' + data.message;
            }
        })
        .catch(err => {
            console.error('Fetch Error:', err);
            document.getElementById('contract_number').value = 'Fetch error (Check Console)';
        });
});

  document.getElementById('confirmSaveBtn').addEventListener('click', function () {
    const form = document.getElementById('contractForm');
    const formData = new FormData(form);

   // Collect escalation rows from modal
    const escalationRows = [];
    document.querySelectorAll('#escalationTableBody tr').forEach(row => {
        escalationRows.push({
            id: row.dataset.id || '',   // hidden row id if exists
            year: row.querySelector('td:nth-child(1) input')?.value || '',
            start_date: row.querySelector('td:nth-child(2) input')?.value || '',
            end_date: row.querySelector('td:nth-child(3) input')?.value || '',
            escalation: row.querySelector('td:nth-child(4) input')?.value || '0',
            fixed_amount: row.querySelector('td:nth-child(5) input')?.value || '0',
            increase: row.querySelector('td:nth-child(6) input')?.value || '0',
            rental: row.querySelector('td:nth-child(7) input')?.value || '0',
            vat: row.querySelector('td:nth-child(8) input')?.value || '0',
            net_vat: row.querySelector('td:nth-child(9) input')?.value || '0',
            wtax_type: row.querySelector('td:nth-child(10) select')?.value || '',
            wtax_percent: row.querySelector('td:nth-child(11) input')?.value || '0',
            wtax: row.querySelector('td:nth-child(12) input')?.value || '0',
            amount_lessor: row.querySelector('td:nth-child(13) input')?.value || '0',
            yearly: row.querySelector('td:nth-child(14) input')?.value || '0'
        });
    });

    // Append escalation data as JSON string
    formData.append('escalations', JSON.stringify(escalationRows));

    // Close confirmation modal
    const confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmSubmitModal'));
    confirmModal.hide();

    // Submit via fetch
    fetch('save_contract.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                html: `
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 mb-1 fw-bold text-success">Success!</h4>
                        <p class="text-muted">Your contract has been saved successfully.</p>
                    </div>
                `,
                showConfirmButton: true,
                confirmButtonColor: '#d70c0c',
                confirmButtonText: 'Proceed to Request Overview',
                background: '#fff',
                color: '#333',
                customClass: {
                    popup: 'px-5 py-4 rounded-4',
                    confirmButton: 'rounded-pill px-4'
                }
            }).then(() => {
                window.location.href = 'user_page.php';
            });
        } else {
            Swal.fire({
                html: `
                    <div class="d-flex flex-column align-items-center">
                        <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 4rem;"></i>
                        <h4 class="mt-3 mb-1 fw-bold text-danger">Failed to Save</h4>
                        <p class="text-muted">${data.message}</p>
                    </div>
                `,
                confirmButtonColor: '#d70c0c',
                confirmButtonText: 'Try Again',
                background: '#fff',
                color: '#333',
                customClass: {
                    popup: 'px-5 py-4 rounded-4',
                    confirmButton: 'rounded-pill px-4'
                }
            });
        }
    })
    .catch(err => {
        Swal.fire({
            html: `
                <div class="d-flex flex-column align-items-center">
                    <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
                    <h4 class="mt-3 mb-1 fw-bold text-danger">Unexpected Error</h4>
                    <p class="text-muted">${err}</p>
                </div>
            `,
            confirmButtonColor: '#d70c0c',
            confirmButtonText: 'Close',
            background: '#fff',
            color: '#333',
            customClass: {
                popup: 'px-5 py-4 rounded-4',
                confirmButton: 'rounded-pill px-4'
            }
        });
    });
});


  document.addEventListener('DOMContentLoaded', function () {
    const maxAttachments = 15;
    const attachmentContainer = document.getElementById('attachmentContainer');
    const addAttachmentBtn = document.getElementById('addAttachmentBtn');
    const limitMsg = document.getElementById('attachmentLimitMsg');

    // Add new attachment field
    addAttachmentBtn.addEventListener('click', () => {
      const currentCount = attachmentContainer.querySelectorAll('.attachment-group').length;

      if (currentCount >= maxAttachments) {
        limitMsg.classList.remove('d-none');
        return;
      }

      limitMsg.classList.add('d-none');

      const div = document.createElement('div');
      div.className = 'input-group mb-2 attachment-group';
      div.innerHTML = `
        <input type="file" name="attachments[]" accept="application/pdf" class="form-control" required>
        <button type="button" class="btn btn-danger remove-attachment">Remove</button>
      `;

      attachmentContainer.appendChild(div);
    });

    // Remove individual attachment field
    attachmentContainer.addEventListener('click', function (e) {
      if (e.target.classList.contains('remove-attachment')) {
        const group = e.target.closest('.attachment-group');
        if (group) group.remove();

        // Recheck attachment count
        const currentCount = attachmentContainer.querySelectorAll('.attachment-group').length;
        if (currentCount < maxAttachments) {
          limitMsg.classList.add('d-none');
        }
      }
    });
  });

  document.addEventListener('DOMContentLoaded', function () {
  const triggerFields = [
    'monthly_rental',
    'vat_type',
    'wtax_type',
    'wtax_percentage'
  ];

  triggerFields.forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;

    const validateAndUpdate = () => {
      const value = el.value;
      const isValid = value && value !== 'Select VAT Type' && value !== 'Select WTax Type' && value !== 'Select WTax %';

      if (isValid) {
        el.classList.remove('border-danger', 'border-2');
      } else {
        el.classList.add('border-danger', 'border-2');
      }
    };

    // Run validation on input/change
    el.addEventListener('input', validateAndUpdate);
    el.addEventListener('change', validateAndUpdate);

    // Run on load in case values are prefilled
    validateAndUpdate();
  });
});

  document.addEventListener('DOMContentLoaded', function () {
  const monthlyRentalInput = document.getElementById('monthly_rental');
  const vatTypeSelect = document.getElementById('vat_type');
  const netVatInput = document.getElementById('net_vat_amount');
  const vatAmountInput = document.getElementById('vat_amount');
  const wtaxTypeSelect = document.getElementById('wtax_type');
  const wtaxPercentageSelect = document.getElementById('wtax_percentage');
  const wtaxAmountInput = document.getElementById('wtax_amount');
  const amountToLessorInput = document.getElementById('amount_to_lessor');
  const totalAmountInput = document.getElementById('total_amount');

  function computeValues() {
    const rental = parseFloat(monthlyRentalInput.value) || 0;
    const vatType = vatTypeSelect.value;
    const wtaxType = wtaxTypeSelect.value;
    const wtaxPercent = parseFloat(wtaxPercentageSelect.value) || 0;

    let netOfVAT = rental;
    let vatAmount = 0;

    // VAT computation (Corrected for Inclusive vs Exclusive)
    if (vatType === 'Vatable' || vatType === 'Vatable (Inclusive)') {
      netOfVAT = rental / 1.12;
      vatAmount = netOfVAT * 0.12;
    } else if (vatType === 'Vatable (Exclusive)') {
      netOfVAT = rental;
      vatAmount = netOfVAT * 0.12;
    } else if (vatType === 'Vat-Exempt' || vatType === 'Non-Vatable') {
      vatAmount = 0;
      netOfVAT = rental;
    }

    // WTax computation (Corrected: Amount to Lessor must include VAT minus WTax)
    let wtaxAmount = 0;
    let amountToLessor = netOfVAT + vatAmount; // Base Gross Amount
    if (wtaxType === 'less_wtax') {
      wtaxAmount = netOfVAT * (wtaxPercent / 100);
      amountToLessor = (netOfVAT + vatAmount) - wtaxAmount;
    } else if (wtaxType === 'net_wtax') {
      wtaxAmount = netOfVAT * (wtaxPercent / 100);
      amountToLessor = netOfVAT + vatAmount;
    }

    // Output results
    vatAmountInput.value = vatAmount.toFixed(2);
    netVatInput.value = netOfVAT.toFixed(2);
    wtaxAmountInput.value = wtaxAmount.toFixed(2);
    amountToLessorInput.value = amountToLessor.toFixed(2);
    totalAmountInput.value = (netOfVAT + vatAmount).toFixed(2); // Accurate Gross Total
  }

  // Event listeners
  [monthlyRentalInput, vatTypeSelect, wtaxTypeSelect, wtaxPercentageSelect].forEach(el => {
    el.addEventListener('input', computeValues);
    el.addEventListener('change', computeValues);
  });

  // Initial compute (in case form is prefilled)
  computeValues();
});

document.addEventListener("DOMContentLoaded", function () {
  const requiredFields = [
    'effectivity_date', 'expiry_date', 'monthly_due',
    'monthly_rental', 'vat_type', 'wtax_type', 'wtax_percentage'
  ];

  const nextBtn = document.getElementById('nextBtn');

  function validatePage3Fields() {
    const page3 = document.getElementById('page3');
    const isActive = page3.classList.contains('active');
    if (!isActive) return;

    let allFilled = true;
    for (let id of requiredFields) {
      const field = document.getElementById(id);
      if (!field) continue;
      if (field.tagName === 'SELECT') {
        if (!field.value || field.selectedIndex === 0) {
          allFilled = false;
          break;
        }
      } else if (!field.value.trim()) {
        allFilled = false;
        break;
      }
    }

    nextBtn.disabled = !allFilled;
  }

  // Attach listeners to relevant fields
  requiredFields.forEach(id => {
    const field = document.getElementById(id);
    if (field) {
      field.addEventListener('input', validatePage3Fields);
      field.addEventListener('change', validatePage3Fields);
    }
  });

  // Observe when page3 becomes active
  const observer = new MutationObserver(validatePage3Fields);
  observer.observe(document.getElementById('page3'), {
    attributes: true,
    attributeFilter: ['class']
  });

  // Initial validation
  validatePage3Fields();
});

document.addEventListener("DOMContentLoaded", function () {
  const nextBtn = document.getElementById('nextBtn');

  // 🔁 Validate Page 1 Fields
  function validatePage1() {
    const mainzone = document.getElementById('mainzone')?.value.trim();
    const region = document.getElementById('region')?.value.trim();
    const area = document.getElementById('area')?.value.trim();
    const branch = document.getElementById('branch')?.value;
    const corporate = document.getElementById('corporate_name')?.value.trim();
    const isValid = mainzone && region && area && branch && corporate;

    if (document.querySelector('.form-page.active')?.id === 'page1') {
      nextBtn.disabled = !isValid;
    }
  }

  // 🔁 Validate Page 2 Fields (UPDATED - EXPOSED GLOBALLY SO TOGGLE AND REMOVE CAN REVALIDATE)
  window.validatePage2 = function() {
    const lessorType = document.getElementById('lessorType')?.value;
    
    let isValid = false;
    let allSelected = true;

    // Check if all currently visible lessor dropdowns have a valid selection
    for (let i = 1; i <= lessorCount; i++) {
        const select = document.getElementById(`lessor${i}`);
        if (!select || !select.value || select.value === 'Select Lessor') {
            allSelected = false;
            break;
        }
    }

    if (lessorType === 'Individual') {
        // Must be exactly 1 lessor and it must be selected
        isValid = allSelected && lessorCount === 1;
    } else if (lessorType === 'Partnership') {
        // Enable next button only if at least 2 lessors are generated and all selected
        isValid = allSelected && lessorCount >= 2;
    } else if (lessorType) {
        isValid = allSelected && lessorCount >= 1;
    }

    if (document.querySelector('.form-page.active')?.id === 'page2') {
      nextBtn.disabled = !isValid;
    }
  }

  // 🔁 Revalidate when page becomes active again (for both page 1 and 2)
  const page1 = document.getElementById('page1');
  const page2 = document.getElementById('page2');

  const observer = new MutationObserver(() => {
    const activeId = document.querySelector('.form-page.active')?.id;
    if (activeId === 'page1') validatePage1();
    if (activeId === 'page2') window.validatePage2();
  });

  observer.observe(page1, { attributes: true, attributeFilter: ['class'] });
  observer.observe(page2, { attributes: true, attributeFilter: ['class'] });

  // 🔁 Event Listeners for field changes (Page 1)
  document.getElementById('branch')?.addEventListener('change', () => {
    const selectedOption = document.getElementById('branch').selectedOptions[0];
    const corp = selectedOption.getAttribute('data-corporate') || '';
    const rdo = selectedOption.getAttribute('data-rdo') || '';

    document.getElementById('corporate_name').value = corp;
    document.getElementById('rdo').value = rdo;

    validatePage1();
  });

  document.getElementById('corporate_name')?.addEventListener('input', validatePage1);
  document.getElementById('mainzone')?.addEventListener('input', validatePage1);
  document.getElementById('region')?.addEventListener('input', validatePage1);
  document.getElementById('area')?.addEventListener('input', validatePage1);

  // 🔁 Event Listener for Page 2 field
  document.getElementById('lessor1')?.addEventListener('change', window.validatePage2);
  document.getElementById('lessorType')?.addEventListener('change', window.validatePage2);
  
  // ✅ Initial validation
  validatePage1();
  window.validatePage2();
});

document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('escalationModal');
  const tableBody = document.getElementById('escalationTableBody');

  let escalationRowsGenerated = false;

  // Modified Event Listener: Open Agreement Modal First
  document.getElementById('openEscalationModal')?.addEventListener('click', () => {
    const agreementModal = new bootstrap.Modal(document.getElementById('escalationAgreementModal'), {
      backdrop: 'static',
      keyboard: false
    });
    
    // Reset the checkbox and button every time it's opened
    const checkbox = document.getElementById('agreeEscalationCheckbox');
    const proceedBtn = document.getElementById('proceedToEscalationBtn');
    if(checkbox && proceedBtn) {
        checkbox.checked = false;
        proceedBtn.disabled = true;
    }
    
    agreementModal.show();
  });

  // Checkbox Logic: Enable/Disable Proceed Button
  document.getElementById('agreeEscalationCheckbox')?.addEventListener('change', function() {
      const proceedBtn = document.getElementById('proceedToEscalationBtn');
      if (proceedBtn) {
          proceedBtn.disabled = !this.checked;
      }
  });

  // Proceed Button Logic: Open the actual Escalation Table
  document.getElementById('proceedToEscalationBtn')?.addEventListener('click', () => {
      // Hide the agreement modal
      const agreementModalEl = document.getElementById('escalationAgreementModal');
      const agreementModalInstance = bootstrap.Modal.getInstance(agreementModalEl);
      if (agreementModalInstance) {
          agreementModalInstance.hide();
      }

      // Generate rows if it hasn't been generated yet
      if (!escalationRowsGenerated) {
        generateEscalationRows();
        escalationRowsGenerated = true;
      }

      // Show the main escalation table modal
      const escalationModal = new bootstrap.Modal(document.getElementById('escalationModal'), {
        backdrop: 'static',
        keyboard: false
      });
      
      // Use setTimeout to allow the first modal's backdrop to fully clear before opening the second one
      setTimeout(() => {
          escalationModal.show();
      }, 300);
  });

document.getElementById('depositType')?.addEventListener('change', (e) => {
  const type = e.target.value;
  const consumableDates = document.getElementById('consumableDates');

  if (type === 'consumable') {
    consumableDates.classList.remove('d-none');
  } else {
    consumableDates.classList.add('d-none');
  }

  validatePage4Inputs(); // recheck validation
});


// Show Advance Rental & Security Deposit on Save
document.getElementById('saveEscalations')?.addEventListener('click', () => {
  const modalInstance = bootstrap.Modal.getInstance(document.getElementById('escalationModal'));
  modalInstance.hide();

  if (typeof escalationIcon !== 'undefined' && escalationIcon) {
    escalationIcon.classList.remove('bi-graph-up-arrow', 'text-danger');
    escalationIcon.classList.add('bi-check-circle-fill', 'text-success');
    escalationIcon.title = 'Escalation Saved';
  }

  // Show the styled blocks
  document.getElementById('advanceRentalSection')?.classList.remove('d-none');
  document.getElementById('securityDepositSection')?.classList.remove('d-none');

  markEscalationAsSaved();
});

// Show/hide date inputs based on deposit type
document.getElementById('depositType')?.addEventListener('change', function () {
  document.getElementById('consumableDates').classList.toggle('d-none', this.value !== 'consumable');
});

  function parseYearMonth(val) {
    const [year, month] = val.split('-').map(Number);
    return new Date(year, month - 1, 1);
  }

  function formatYearMonth(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
  }

  function addOneMonth(date) {
    const d = new Date(date);
    d.setMonth(d.getMonth() + 1);
    return d;
  }

  function addOneYearMinusOneMonth(date) {
    const d = new Date(date);
    d.setFullYear(d.getFullYear() + 1);
    d.setMonth(d.getMonth() - 1);
    return d;
  }

  function generateEscalationRows() {
    const effectivityDate = new Date(document.getElementById('effectivity_date').value);
    const expiryDate = new Date(document.getElementById('expiry_date').value);
    const baseRental = parseFloat(document.getElementById('monthly_rental').value) || 0;

    tableBody.innerHTML = '';
    let start = new Date(effectivityDate);
    let rental = baseRental;
    let year = 1;

    while (start <= expiryDate) {
      const end = addOneYearMinusOneMonth(start);
      const finalEnd = end > expiryDate ? new Date(expiryDate) : end;
      insertRow(year++, start, finalEnd, rental);
      start = addOneMonth(finalEnd);
    }

    attachDateHandlers();
    handleComputation();

  }

  function insertRow(year, startDate, endDate, rental) {
    const vatType = document.getElementById('vat_type')?.value || '';
    const wtaxPercent = parseFloat(document.getElementById('wtax_percentage')?.value) || 0;
    const wtaxType = document.getElementById('wtax_type')?.value || '';

    // VAT computation (Corrected for Escalation Inclusive vs Exclusive)
    const isInclusive = vatType === "Vatable" || vatType === "Vatable (Inclusive)";
    const isExclusive = vatType === "Vatable (Exclusive)";
    
    const netVat = isInclusive ? rental / 1.12 : rental;
    const vat = (isInclusive || isExclusive) ? netVat * 0.12 : 0;
    const wtax = wtaxType ? netVat * (wtaxPercent / 100) : 0;
    const amountToLessor = wtaxType === 'less_wtax' ? (netVat + vat - wtax) : (netVat + vat);
    const yearlyAmount = rental * 12;

    const row = document.createElement('tr');
    row.innerHTML = `
        <td>${year}</td>
        <td><input type="month" class="form-control form-control-sm w-100 start-date" value="${formatYearMonth(startDate)}" readonly></td>
        <td><input type="month" class="form-control form-control-sm w-100 end-date" value="${formatYearMonth(endDate)}"></td>
        <td>
            <input type="text" class="form-control form-control-sm w-100 escalation-input"
                   value="0.0"
                   placeholder="Enter escalation %"
                   pattern="^\\d{1,3}(\\.\\d?)?$"
                   title="Enter a valid percentage up to 3 digits and 1 decimal (e.g., 5.0, 10.5, 100.0)">
        </td>
        <td><input type="number" class="form-control form-control-sm w-100 fixed-amount" value="0.00" step="0.01"></td>
        <td><input type="number" class="form-control form-control-sm w-100 increase" value="0.00" readonly></td>
        <td><input type="number" class="form-control form-control-sm w-100 rental" value="${rental.toFixed(2)}" readonly></td>
        <td><input type="number" class="form-control form-control-sm w-100 vat" value="${vat.toFixed(2)}" readonly></td>
        <td><input type="number" class="form-control form-control-sm w-100 net-vat" value="${netVat.toFixed(2)}" readonly></td>
        <td><input type="text" class="form-control form-control-sm w-100 wtax-type" value="${wtaxType}" readonly></td>
        <td><input type="number" class="form-control form-control-sm w-100 wtax-percent" value="${wtaxPercent}" readonly></td>
        <td><input type="number" class="form-control form-control-sm w-100 wtax" value="${wtax.toFixed(2)}" readonly></td>
        <td><input type="number" class="form-control form-control-sm w-100 amount-lessor" value="${amountToLessor.toFixed(2)}" readonly></td>
        <td><input type="number" class="form-control form-control-sm w-100 yearly" value="${yearlyAmount.toFixed(2)}" readonly></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-row">Remove</button></td>
    `;

    tableBody.appendChild(row);

    // Escalation input: restrict to max 4 digits including decimal
    const escalationInput = row.querySelector('.escalation-input');
    escalationInput.addEventListener('input', () => {
        escalationInput.value = escalationInput.value
            .replace(/[^0-9.]/g, '')                // allow only digits and dot
            .replace(/^(\d{0,3})(\.\d?)?.*$/, '$1$2'); // max 3 digits + 1 decimal
        handleComputation();
    });

    // Fixed amount input event
    const fixedAmountInput = row.querySelector('.fixed-amount');
    fixedAmountInput.addEventListener('input', handleComputation);

    // Remove row button
    row.querySelector('.remove-row').addEventListener('click', () => row.remove());
}

  function attachDateHandlers() {
  // Event delegation: handle remove clicks and end-date changes from tableBody
  // Assumes tableBody is a reference to the <tbody> where rows are inserted.

  // Helper to get current rows array
  function getRows() {
    return [...tableBody.querySelectorAll('tr')];
  }

  // Recompute UI state (start/end sequence, duplicates, expiry checks, renumbering)
  function refreshHandlers() {
    const rows = getRows();
    const expiry = parseYearMonth(document.getElementById('expiry_date').value);
    const endDateMap = new Map();

    rows.forEach((row, index) => {
      const yearCell = row.querySelector('td'); // first td is year column
      const startInput = row.querySelector('.start-date');
      const endInput = row.querySelector('.end-date');
      const removeBtn = row.querySelector('.remove-row');

      // reset visuals
      endInput.classList.remove('is-invalid');
      row.style.backgroundColor = '';
      removeBtn.style.display = 'none';

      // renumber year column (optional — keeps year/row number in sync)
      if (yearCell) yearCell.textContent = (index + 1);

      // Force start = prev end + 1 month for index > 0
      if (index > 0) {
        const prevEnd = parseYearMonth(rows[index - 1].querySelector('.end-date').value);
        const expectedStart = addOneMonth(prevEnd);
        startInput.value = formatYearMonth(expectedStart);

        // ensure end is after start; if not, set end = start + 1 year - 1 month (capped to expiry)
        let curEnd = parseYearMonth(endInput.value);
        if (curEnd < expectedStart) {
          let fixedEnd = addOneYearMinusOneMonth(expectedStart);
          if (fixedEnd > expiry) fixedEnd = expiry;
          endInput.value = formatYearMonth(fixedEnd);
          curEnd = fixedEnd;
        }
      }

      // Duplicate end-date highlight
      const endVal = endInput.value;
      if (endDateMap.has(endVal)) {
        const otherRow = endDateMap.get(endVal);
        [otherRow, row].forEach(r => {
          r.querySelector('.end-date').classList.add('is-invalid');
          r.style.backgroundColor = '#ffe6e6';
          r.querySelector('.remove-row').style.display = 'inline-block';
        });
      } else {
        endDateMap.set(endVal, row);
      }

      // End beyond expiry highlight
      const endDate = parseYearMonth(endInput.value);
      if (endDate > expiry) {
        endInput.classList.add('is-invalid');
        row.style.backgroundColor = '#ffebcc';
        removeBtn.style.display = 'inline-block';
      }
    });
  }

  // Ensure the table covers upto expiry: auto-add rows as needed.
  function ensureCoverage() {
    let rows = getRows();
    if (rows.length === 0) return;
    const expiry = parseYearMonth(document.getElementById('expiry_date').value);
    let lastEnd = parseYearMonth(rows.at(-1).querySelector('.end-date').value);

    while (lastEnd < expiry) {
      const newStart = addOneMonth(lastEnd);
      let newEnd = addOneYearMinusOneMonth(newStart);
      if (newEnd > expiry) newEnd = expiry;

      // use rental from last row when inserting
      const lastRental = parseFloat(rows.at(-1).querySelector('.rental').value) || 0;
      insertRow(rows.length + 1, newStart, newEnd, lastRental);

      // refresh local rows and lastEnd for next loop
      rows = getRows();
      lastEnd = parseYearMonth(rows.at(-1).querySelector('.end-date').value);
    }
  }

  // Delegated click for remove buttons — works for dynamically added rows too
  tableBody.addEventListener('click', (ev) => {
    const btn = ev.target.closest && ev.target.closest('.remove-row');
    if (!btn) return;
    const row = btn.closest('tr');
    if (!row) return;

    // remove and then re-check coverage + visuals
    row.remove();
    ensureCoverage();
    refreshHandlers();
  });

  // Delegated change handler for end-date inputs — cascade following rows
  tableBody.addEventListener('change', (ev) => {
    const endInput = ev.target.closest && ev.target.closest('.end-date');
    if (!endInput) return;
    const rows = getRows();
    const row = endInput.closest('tr');
    const index = rows.indexOf(row);
    if (index === -1) return;

    const newEnd = parseYearMonth(endInput.value);
    // next row start must be newEnd + 1 month; cascade for remaining rows
    let currentStart = addOneMonth(newEnd);
    const expiry = parseYearMonth(document.getElementById('expiry_date').value);

    for (let i = index + 1; i < rows.length; i++) {
      const r = rows[i];
      r.querySelector('.start-date').value = formatYearMonth(currentStart);

      let newEndDate = addOneYearMinusOneMonth(currentStart);
      if (newEndDate > expiry) newEndDate = expiry;
      r.querySelector('.end-date').value = formatYearMonth(newEndDate);

      currentStart = addOneMonth(newEndDate);
    }

    // After cascading, ensure coverage (might need to add new rows) and refresh visual rules
    ensureCoverage();
    refreshHandlers();
  });

  // Initial coverage + UI setup
  ensureCoverage();
  refreshHandlers();
}

function handleComputation() {
  const rows = [...tableBody.querySelectorAll('tr')];
  const vatType = document.getElementById('vat_type').value;
  const wtaxType = document.getElementById('wtax_type').value;
  const wtaxPercent = parseFloat(document.getElementById('wtax_percentage').value) || 0;

  rows.forEach((row, index) => {
    const escInput = row.querySelector('.escalation-input');
    const fixedInput = row.querySelector('.fixed-amount');
    const increaseField = row.querySelector('.increase');
    const rentalField = row.querySelector('.rental');
    const vatField = row.querySelector('.vat');
    const netVatField = row.querySelector('.net-vat');
    const wtaxField = row.querySelector('.wtax');
    const amountLessorField = row.querySelector('.amount-lessor');
    const yearlyField = row.querySelector('.yearly');

    // Get previous monthly rental
    const prevRental = index === 0
      ? parseFloat(document.getElementById('monthly_rental').value)
      : parseFloat(rows[index - 1].querySelector('.rental').value);

    const esc = escInput ? parseFloat(escInput.value) || 0 : 0; // % escalation per year
    const fixed = parseFloat(fixedInput.value) || 0; // fixed monthly increase

    // ✅ Monthly increase
    const monthlyIncrease = esc > 0 ? prevRental * (esc / 100) : fixed;

    // ✅ New monthly rental
    const rental = prevRental + monthlyIncrease;

    // ✅ Yearly increase (NEW)
    const yearlyIncrease = monthlyIncrease * 12;

    // ✅ Tax Calculations (Corrected for Escalation Inclusive vs Exclusive)
    const isInclusive = vatType === "Vatable" || vatType === "Vatable (Inclusive)";
    const isExclusive = vatType === "Vatable (Exclusive)";
    
    const netVat = isInclusive ? rental / 1.12 : rental;
    const vat = (isInclusive || isExclusive) ? netVat * 0.12 : 0;
    const wtax = wtaxType ? netVat * (wtaxPercent / 100) : 0;
    const amountToLessor = wtaxType === "less_wtax" ? (netVat + vat - wtax) : (netVat + vat);

    // ✅ Yearly rental total (Base Rental based)
    const yearly = rental * 12;

    // ✅ Update fields
    increaseField.value = yearlyIncrease.toFixed(2); 
    rentalField.value = rental.toFixed(2);
    vatField.value = vat.toFixed(2);
    netVatField.value = netVat.toFixed(2);
    wtaxField.value = wtax.toFixed(2);
    amountLessorField.value = amountToLessor.toFixed(2);
    yearlyField.value = yearly.toFixed(2);
  });
}
});

let lessorCount = 1;
const maxLessors = 5;

function addLessorSelect(index) {
    const container = document.getElementById('lessorContainer');

    const div = document.createElement('div');
    div.className = 'mb-3 lessor-group position-relative';
    div.id = `lessor-group-${index}`;

    div.innerHTML = `
        <label for="lessor${index}" class="form-label">
            <i class="bi bi-person-lines-fill me-1 text-danger"></i> Additional Lessor #${index}
        </label>
        <select class="form-select" id="lessor${index}" name="lessor${index}" required>
            <option selected disabled>Select Lessor</option>
            <?php foreach ($lessorOptions as $type => $group): ?>
                <optgroup label="<?= htmlspecialchars(strtoupper($type)) ?>">
                    <?php foreach ($group as $lessor): ?>
                        <option 
                            value="<?= htmlspecialchars($lessor['id']) ?>"
                            data-firstname="<?= htmlspecialchars($lessor['first_name']) ?>"
                            data-middlename="<?= htmlspecialchars($lessor['middle_name']) ?>"
                            data-lastname="<?= htmlspecialchars($lessor['last_name']) ?>"
                            data-gender="<?= htmlspecialchars($lessor['gender']) ?>"
                            data-mobile="<?= htmlspecialchars($lessor['mobile_number']) ?>"
                        >
                            <?= htmlspecialchars($lessor['text']) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="lessors[${index - 1}][firstname]" id="lessor${index}_firstname">
        <input type="hidden" name="lessors[${index - 1}][middlename]" id="lessor${index}_middlename">
        <input type="hidden" name="lessors[${index - 1}][lastname]" id="lessor${index}_lastname">
        <input type="hidden" name="lessors[${index - 1}][gender]" id="lessor${index}_gender">
        <input type="hidden" name="lessors[${index - 1}][mobile_number]" id="lessor${index}_mobile">
        <button type="button" class="btn-close remove-lessor-btn position-absolute top-0 end-0 mt-2 me-2"
            aria-label="Remove" onclick="removeLessor(${index})"></button>
    `;

    container.appendChild(div);
    bindLessorChange(index);
}

// ADDED: Logic to handle Lessor Type conditions
function toggleLessorFields() {
    const type = document.getElementById('lessorType').value;
    const addBtn = document.getElementById('addLessorBtn');

    // Keep the element in DOM but toggle the disabled property based on your conditions
    addBtn.style.display = 'inline-block';

    if (type === 'Individual' || type === 'Corporate' || type === 'LGU') {
        // Sole Proprietorship: Max 1 lessor
        addBtn.disabled = true;
        
        // Remove extra lessors if they previously added them
        while (lessorCount > 1) {
            removeLessor(lessorCount);
        }
    } else if (type === 'Partnership') {
        // Partnership: Exactly 2 lessors initially required, but more can be added
        addBtn.disabled = lessorCount >= maxLessors;
        
        // Auto-add second lessor if there's only 1 to prompt the user
        if (lessorCount < 2) {
            addLessorSelect(2);
            lessorCount = 2;
        }
    } else {
        // Corporate / LGU: Standard behavior
        addBtn.disabled = lessorCount >= maxLessors;
    }

    // Re-validate page 2 dynamically
    if (typeof window.validatePage2 === 'function') window.validatePage2();
}

document.getElementById('addLessorBtn')?.addEventListener('click', () => {
    if (lessorCount >= maxLessors) return;
    lessorCount++;
    addLessorSelect(lessorCount);

    if (lessorCount === maxLessors) {
        const addBtn = document.getElementById('addLessorBtn');
        addBtn.disabled = true;
        addBtn.innerHTML = '<i class="bi bi-person-fill-x me-1"></i> Maximum Reached';
    }
    
    // Validate instantly to temporarily disable Next Button until new selection is made
    if (typeof window.validatePage2 === 'function') window.validatePage2();
});

function removeLessor(id) {
    const toRemove = document.getElementById(`lessor-group-${id}`);
    if (toRemove) {
        toRemove.remove();
        lessorCount--;

        const addBtn = document.getElementById('addLessorBtn');
        addBtn.disabled = false;
        addBtn.innerHTML = '<i class="bi bi-person-plus-fill me-1"></i> Add Another Lessor';
        
        const type = document.getElementById('lessorType')?.value;
        if (type === 'Individual') {
            addBtn.disabled = true;
        }
        
        // Re-validate rules
        if (typeof window.validatePage2 === 'function') window.validatePage2();
    }
}

function bindLessorChange(index) {
    const select = document.getElementById(`lessor${index}`);
    if(select) {
        select.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            document.getElementById(`lessor${index}_firstname`).value = selected.dataset.firstname || '';
            document.getElementById(`lessor${index}_middlename`).value = selected.dataset.middlename || '';
            document.getElementById(`lessor${index}_lastname`).value = selected.dataset.lastname || '';
            document.getElementById(`lessor${index}_gender`).value = selected.dataset.gender || '';
            document.getElementById(`lessor${index}_mobile`).value = selected.dataset.mobile || '';
            
            // ADDED: Validate page 2 whenever any lessor is changed
            if (typeof window.validatePage2 === 'function') {
                window.validatePage2();
            }
        });
    }
}

bindLessorChange(1); // Initial binding for Lessor 1

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

    // Simulate logout delay
    setTimeout(() => {
        window.location.href = '../../logout.php';
    }, 2500);
});

let currentPage = 1;
const totalPages = 5;
let escalationSaved = false; // 🔒 Remember if Save Changes was clicked

const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');

function validatePage1Inputs() {
  const branchSelect = document.getElementById('branchSelect');
  if (!branchSelect) return;

  // Disable next button if no value is selected
  nextBtn.disabled = branchSelect.value.trim() === '';
}

function showPage(page) {
  document.querySelectorAll('.form-page').forEach((pg, index) => {
    pg.classList.toggle('active', index === page - 1);
  });

  document.querySelectorAll('.step').forEach((step, index) => {
    step.classList.toggle('active', index === page - 1);
  });

  // Show/hide previous
  prevBtn.style.display = (page === 1) ? 'none' : 'inline-block';

  // Handle Page 4 "Create New" logic
  if (page === 4 && escalationSaved) {
    prevBtn.innerHTML = '<i class="bi bi-plus-circle me-1"></i> Create New';
    prevBtn.classList.add('create-new');
  } else {
    prevBtn.innerText = 'Previous';
    prevBtn.classList.remove('create-new');
  }

  // Update next button
  nextBtn.innerText = (page === totalPages) ? 'Submit' : 'Next';

  // 🔒 Trigger input validation based on current page
  if (page === 1) {
    validatePage1Inputs();
  } else if (page === 4) {
    validatePage4Inputs();
  } else if (page === 5) {
    validatePage5Inputs();
  } else {
    nextBtn.disabled = false;
  }
}

prevBtn?.addEventListener('click', () => {
  if (currentPage === 5) {
    currentPage = 4;
    showPage(currentPage);
    return;
  }

  if (currentPage === 4 && escalationSaved && prevBtn.classList.contains('create-new')) {
    window.location.href = 'create_contract.php';
    return;
  }

  if (currentPage > 1) {
    currentPage--;
    showPage(currentPage);
  }
});

nextBtn?.addEventListener('click', () => {
  if (currentPage < totalPages) {
    currentPage++;
    showPage(currentPage);
  } else {
    // Show confirmation modal instead of submitting
    const confirmationModal = new bootstrap.Modal(document.getElementById('confirmSubmitModal'));
    confirmationModal.show();
  }
});

function validatePage4Inputs() {
  // Only check if escalation is saved, ignore rental/deposit
  if(nextBtn) nextBtn.disabled = !escalationSaved;
}

function validatePage5Inputs() {
  if (currentPage !== 5) return;

  const attachmentInputs = document.querySelectorAll('#attachmentContainer input[type="file"]');
  const hasAttachment = Array.from(attachmentInputs).some(input => input.files.length > 0);

  const notarizedSelected = document.querySelector('input[name="notarized"]:checked');

  // Disable Submit unless both conditions are met
  if(nextBtn) nextBtn.disabled = !(hasAttachment && notarizedSelected);
}


// Attach event listeners
[
  'advanceRental', 'advanceFrom', 'advanceTo',
  'securityDeposit', 'depositType', 'depositFrom', 'depositTo'
].forEach(id => {
  const el = document.getElementById(id);
  if (el) el.addEventListener('input', validatePage4Inputs);
});


// Call this when Save Changes is clicked
function markEscalationAsSaved() {
  escalationSaved = true;
  if (currentPage === 4) {
    prevBtn.innerHTML = '<i class="bi bi-plus-circle me-1"></i> Create New';
    prevBtn.classList.add('create-new');
  }
  validatePage4Inputs();
}

document.getElementById('saveEscalations')?.addEventListener('click', markEscalationAsSaved);

document.addEventListener('DOMContentLoaded', () => {
  showPage(currentPage);

  // Page 5 logic
  const attachContainer = document.getElementById('attachmentContainer');
  if(attachContainer) attachContainer.addEventListener('change', validatePage5Inputs);

  document.querySelectorAll('input[name="notarized"]').forEach(radio => {
    radio.addEventListener('change', validatePage5Inputs);
  });
  
  const addAttachBtn = document.getElementById('addAttachmentBtn');
  if(addAttachBtn) addAttachBtn.addEventListener('click', () => {
    setTimeout(validatePage5Inputs, 100);
  });

  // ✅ Page 1 validation trigger
  const branchSelect = document.getElementById('branchSelect');
  if (branchSelect) {
    branchSelect.addEventListener('change', validatePage1Inputs);
  }
});

</script>
</body>
</html>