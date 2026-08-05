<?php
session_start();
include '../../config/config.php';

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit;
}

$mainzone = $region = $area = '';
$alertType = '';
$alertData = [];

// Fetch user's zone
if (!empty($_SESSION['user_email'])) {
    $email = $_SESSION['user_email'];
    $sql = "SELECT mainzone, region, area FROM user_form WHERE username = '$email' OR email = '$email'";
    $res = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($res)) {
        $mainzone = $row['mainzone'];
        $region = $row['region'];
        $area = $row['area'];
    }
}

if (isset($_POST['profileSave']) || isset($_POST['confirmed_save'])) {
    $lessorType = trim($_POST['lessorType'] ?? '');
    $firstName  = mysqli_real_escape_string($conn, trim($_POST['firstName'] ?? ''));
    $middleName = mysqli_real_escape_string($conn, trim($_POST['middleName'] ?? ''));
    $lastName   = mysqli_real_escape_string($conn, trim($_POST['lastName'] ?? ''));
    $gender     = mysqli_real_escape_string($conn, trim($_POST['gender'] ?? ''));
    $corporate  = mysqli_real_escape_string($conn, trim($_POST['corporate'] ?? ''));
    $lgu        = mysqli_real_escape_string($conn, trim($_POST['lgu'] ?? ''));
    $street     = mysqli_real_escape_string($conn, trim($_POST['street'] ?? ''));
    $city       = mysqli_real_escape_string($conn, trim($_POST['city'] ?? ''));
    $province   = mysqli_real_escape_string($conn, trim($_POST['province'] ?? ''));
    $mobile     = mysqli_real_escape_string($conn, trim($_POST['mobile_number'] ?? ''));
    $mainzone   = mysqli_real_escape_string($conn, trim($_POST['main_zone'] ?? ''));
    $region     = mysqli_real_escape_string($conn, trim($_POST['region'] ?? ''));
    $area       = mysqli_real_escape_string($conn, trim($_POST['area'] ?? ''));
    $status     = 'Active';
    $address    = "$street, $city, $province";

    if ($lessorType === 'LGU') {
        $corporate = $lgu;
    }

    $isDuplicate = false;

    if (!isset($_POST['confirmed_save'])) {
        $checkQuery = "";

        if (in_array($lessorType, ['Individual', 'Partnership']) && $firstName && $lastName) {
            $checkQuery = "SELECT 1 FROM lessor_profile WHERE first_name = '$firstName' AND last_name = '$lastName'";
        } elseif (in_array($lessorType, ['Corporate', 'LGU']) && $corporate) {
            $checkQuery = "SELECT 1 FROM lessor_profile WHERE corporate_name = '$corporate'";
        }

        if ($checkQuery) {
            $result = mysqli_query($conn, $checkQuery);
            if (mysqli_num_rows($result) > 0) {
                $alertType = 'duplicate';
                $alertData = [
                    'message' => in_array($lessorType, ['Individual', 'Partnership'])
                        ? "A(n) " . strtolower($lessorType) . " lessor named <strong>" . htmlspecialchars($firstName) . " " . htmlspecialchars($lastName) . "</strong> already exists."
                        : "A corporate/LGU lessor named <strong>" . htmlspecialchars($corporate) . "</strong> already exists.",
                    'postData' => $_POST
                ];
            }
        }
    }

    if ($alertType !== 'duplicate') {
        $insertQuery = "
            INSERT INTO lessor_profile (
                lessor_type, first_name, middle_name, last_name, gender,
                corporate_name, address, mobile_number, status,
                main_zone, region, area
            ) VALUES (
                '$lessorType',
                " . (in_array($lessorType, ['Individual', 'Partnership']) ? "'$firstName'" : "NULL") . ",
                " . (in_array($lessorType, ['Individual', 'Partnership']) ? "'$middleName'" : "NULL") . ",
                " . (in_array($lessorType, ['Individual', 'Partnership']) ? "'$lastName'" : "NULL") . ",
                " . (in_array($lessorType, ['Individual', 'Partnership']) ? "'$gender'" : "NULL") . ",
                " . (in_array($lessorType, ['Corporate', 'LGU']) ? "'$corporate'" : "NULL") . ",
                '$address',
                '$mobile',
                '$status',
                '$mainzone',
                '$region',
                '$area'
            )
        ";

        if (mysqli_query($conn, $insertQuery)) {
            $alertType = 'success';
        } else {
            $alertType = 'error';
            $alertData = ['message' => 'Failed to save lessor profile. Please try again.'];
        }
    }
}

// Fetch user's zone (only if user is logged in)
if (!empty($_SESSION['user_email'])) {
    $email = $_SESSION['user_email'];
    $sql = "SELECT mainzone, region, area FROM user_form WHERE username = '$email'";
    $res = mysqli_query($conn, $sql);
    if ($row = mysqli_fetch_assoc($res)) {
        $mainzone = $row['mainzone'];
        $region = $row['region'];
        $area = $row['area'];
    }
}
?>
<!DOCTYPE html>
  <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
      <link rel="shortcut icon" href="../../assets/images/mlw-logo-96x96.png" type="image/x-icon">
      <meta name="description" content="">
      <title>ML Rental - Create a lessor</title>
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
        #lessorType, #gender{
          font-size:12px;
        }
        #stepTabs .nav-link {
          background-color: transparent;
          color: #333;
          border-radius: 2rem;
          margin-right: 0.5rem;
        }
        #stepTabs .nav-link.active {
          background-color: #b50909; /* darker red for active step */
          color: #fff;
        }
        #stepTabs .nav-link:hover {
          background-color: #bb1b1b;
          color: #fff;
        }
        .swal2-popup {
          font-family: 'Poppins', sans-serif !important;
        }
        #nextBtn{
          background-color: #d70c0c;
          color: #fff;
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
    <div class="container my-1">
      <div class="card border-0 shadow-sm rounded-4" style="background-color: #fff;">
        <div class="card-body p-5">
          <h4 class="mb-4 fw-bold" style="color: #d70c0c;">
            <i class="bi bi-file-earmark-text me-2"></i>Contract of Lease Information
          </h4>
          <form id="leaseForm" method="POST">
            <!-- Hidden Inputs -->
            <input type="hidden" name="main_zone" value="<?= htmlspecialchars($mainzone) ?>">
            <input type="hidden" name="region" value="<?= htmlspecialchars($region) ?>">
            <input type="hidden" name="area" value="<?= htmlspecialchars($area) ?>">
            <!-- Step Navigation -->
            <ul class="nav nav-pills mb-4" id="stepTabs">
              <li class="nav-item">
                <button class="nav-link active" type="button" disabled>1. Lessor Info</button>
              </li>
              <li class="nav-item">
                <button class="nav-link" type="button" disabled>2. Address & Contact</button>
              </li>
              <li class="nav-item">
                <button class="nav-link" type="button" disabled>3. Review</button>
              </li>
            </ul>
            <!-- Step 1 -->
            <div id="step1" class="step">
              <div class="mb-4">
                <label for="lessorType" class="form-label">
                  <i class="bi bi-person-vcard me-1"></i> Lessor Type <span class="text-danger">*</span>
                </label>
                <select class="form-select" id="lessorType" name="lessorType" onchange="toggleLessorFields()" autocomplete="off">
                  <option value="" selected disabled>SELECT LESSOR TYPE</option>
                  <option value="Individual">SOLE PROPRIETORSHIP</option>
                  <option value="Partnership">PARTNERSHIP</option>
                  <option value="Corporate">CORPORATE</option>
                  <option value="LGU">LOCAL GOVERNMENT UNIT</option>
                </select>
              </div>
              <div id="individualFields" class="mb-4" style="display: none;">
                
                <div class="alert alert-warning py-2 mb-3 shadow-sm border-warning border-start border-4" role="alert">
                  <i class="bi bi-info-circle-fill text-warning me-2"></i>
                  <small><strong>Important:</strong> If there is no middle name, please type <strong>NM</strong>.</small>
                </div>

                <input type="text" class="form-control mb-2" id="firstName" name="firstName" placeholder="First Name" autocomplete="off" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                <input type="text" class="form-control mb-2" id="middleName" name="middleName" placeholder="Middle Name (Not allowed initial)" autocomplete="off" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
                <input type="text" class="form-control mb-2" id="lastName" name="lastName" placeholder="Last Name" autocomplete="off" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">

                <select class="form-select" id="gender" name="gender" autocomplete="off">
                  <option value="" disabled selected>SELECT GENDER</option>
                  <option value="Male">MALE</option>
                  <option value="Female">FEMALE</option>
                </select>
              </div>
              <div id="corporateField" class="mb-4" style="display: none;">
                <input type="text" class="form-control" id="corporate_name" name="corporate" placeholder="Corporate Name" autocomplete="off" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
              </div>
              <div id="lguField" class="mb-4" style="display: none;">
                <input type="text" class="form-control" id="lgu" name="lgu" placeholder="Local Government Unit" autocomplete="off" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
              </div>
              <button type="button" class="btn mt-3" id="nextBtn" onclick="goToStep(2)">
                Next <i class="bi bi-arrow-right"></i>
              </button>
            </div>
            <!-- Step 2 -->
            <div id="step2" class="step" style="display: none;">
            <input type="text" class="form-control mb-3" id="street" name="street" placeholder="Street/Barangay" autocomplete="off" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
              <input type="text" class="form-control mb-3" id="city" name="city" placeholder="City" autocomplete="off" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
              <input type="text" class="form-control mb-3" id="province" name="province" placeholder="Province" autocomplete="off" style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase()">
              <input type="tel" class="form-control mb-3" id="mobileNumber" name="mobile_number" placeholder="09XXXXXXXXX" oninput="limitMobileNumber(this)" autocomplete="off">
              <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" onclick="goToStep(1)">
                  <i class="bi bi-arrow-left"></i> Back
                </button>
                <button type="button" class="btn btn-danger" onclick="goToStep(3)">
                  <i class="bi bi-eye-fill me-1"></i> Review
                </button>
              </div>
            </div>
            <!-- Step 3: Review -->
            <div id="step3" class="step" style="display: none;">
              <h5 class="fw-bold mb-3 text-center text-danger">Review Details Before Saving</h5>
              <ul class="list-group mb-4 mx-auto" id="reviewList" style="width: 75%;">
                <!-- Filled by JS -->
              </ul>
              <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" onclick="goToStep(2)">
                  <i class="bi bi-arrow-left"></i> Back
                </button>
                <button type="submit" name="profileSave" id="saveBtn" class="btn btn-danger">
                  <i class="bi bi-check-circle-fill me-1"></i> Confirm & Save
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
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
<script>window.LAST_ONLINE_ENDPOINT = '../../fetch/last_online.php';</script>
<script src="../../assets/js/last-online-tracker.js"></script>
<script src="../../assets/sweetalert2/dist/sweetalert2.min.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>

<?php if ($alertType === 'duplicate'): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
        icon: 'warning',
        title: 'Duplicate Entry Found',
        html: `<?= $alertData['message'] ?><br><br>Would you like to proceed anyway?`,
        showCancelButton: true,
        confirmButtonText: '<i class="bi bi-save"></i> Save Anyway',
        cancelButtonText: '<i class="bi bi-plus-circle"></i> Create Another',
        customClass: {
            popup: 'rounded-4 shadow',
            title: 'fw-bold text-danger',
            confirmButton: 'btn btn-danger px-4 rounded-pill me-2',
            cancelButton: 'btn btn-outline-secondary px-4 rounded-pill'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';

            const confirmed = document.createElement('input');
            confirmed.type = 'hidden';
            confirmed.name = 'confirmed_save';
            confirmed.value = '1';
            form.appendChild(confirmed);

            const data = <?= json_encode($alertData['postData']) ?>;
            for (const key in data) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = data[key];
                form.appendChild(input);
            }

            document.body.appendChild(form);
            form.submit();
        } else {
            window.location.href = 'create_lessor_profile.php';
        }
    });
});
</script>

<?php elseif ($alertType === 'success'): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Profile Saved',
    html: `The lessor profile has been added successfully!<br><br>What would you like to do next?`,
    showDenyButton: true,
    confirmButtonText: '<i class="bi bi-arrow-right-circle"></i> Proceed to Contract',
    denyButtonText: '<i class="bi bi-plus-circle"></i> Add Another Profile',
    customClass: {
        popup: 'rounded-4 shadow-lg',
        title: 'fw-bold text-success',
        confirmButton: 'btn btn-success px-4 rounded-pill me-2',
        denyButton: 'btn btn-outline-secondary px-4 rounded-pill'
    }
}).then((result) => {
    if (result.isConfirmed) {
        window.location.href = 'select_contract_type.php';
    } else {
        window.location.href = 'create_lessor_profile.php';
    }
});
</script>

<?php elseif ($alertType === 'error'): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Saving Failed',
    html: `<?= $alertData['message'] ?>`,
    confirmButtonText: '<i class="bi bi-arrow-repeat"></i> Try Again',
    customClass: {
        popup: 'rounded-4 shadow',
        title: 'fw-bold text-danger',
        confirmButton: 'btn btn-outline-dark px-4 rounded-pill'
    }
});
</script>
<?php endif; ?>

<script>
  // Optional: Run once on page load in case the select is prefilled
document.addEventListener('DOMContentLoaded', toggleLessorFields);

document.getElementById('lastName').addEventListener('input', function () {
  const input = this.value.trim();
  
  // Guard clause: do nothing if the input is empty
  if (!input) return;

  const suffixes = ['jr', 'sr', 'ii', 'iii', 'iv', 'v', 'jr.', 'sr.', 'ii.', 'iii.', 'iv.', 'v.'];

  const parts = input.split(' ');
  const lastPart = parts[parts.length - 1].toLowerCase();

  // FIX: Ensure there is more than one word before checking for a suffix.
  // This prevents deleting the first letter "V" when typing names like "Velarde".
  if (parts.length > 1 && suffixes.includes(lastPart)) {
    Swal.fire({
      icon: 'warning',
      title: 'Invalid Last Name',
      text: 'Please do not include suffixes like "Jr.", "Sr.", "III", etc. in the Last Name field.',
      confirmButtonColor: '#d33'
    });

    // Remove the suffix and update the input
    this.value = parts.slice(0, -1).join(' ');
  }
});
document.addEventListener('DOMContentLoaded', function () {
  const inputs = document.querySelectorAll('#leaseForm input, #leaseForm select');
  inputs.forEach(input => {
    input.addEventListener('input', () => {
      validateStep1();
      validateForm();
    });
    input.addEventListener('change', () => {
      validateStep1();
      validateForm();
    });
  });

  // Initially validate on load
  validateStep1();
  validateForm();
});

function validateStep1() {
  const type = document.getElementById('lessorType').value;
  const nextBtn = document.querySelector('#step1 button[type="button"]');

  let isValid = false;

  if (type === 'Individual' || type === 'Partnership') {
    const first = document.getElementById('firstName')?.value.trim() || '';
    const last = document.getElementById('lastName')?.value.trim() || '';
    const middle = document.getElementById('middleName')?.value.trim() || '';
    const gender = document.getElementById('gender')?.value || '';
    isValid = first && middle && last && gender;
  } else if (type === 'Corporate') {
    const corp = document.getElementById('corporate_name')?.value.trim() || '';
    isValid = corp;
  } else if (type === 'LGU') {
    const lgu = document.getElementById('lgu')?.value.trim() || '';
    isValid = lgu;
  }

  // Enable or disable "Next" button based on validation
  nextBtn.disabled = !isValid;
}


function validateForm() {
  const type = document.getElementById('lessorType').value;
  const street = document.getElementById('street').value.trim();
  const city = document.getElementById('city').value.trim();
  const province = document.getElementById('province').value.trim();
  const mobile = document.getElementById('mobileNumber').value.trim();

  let isValid = false;

  if (type === 'Individual' || type === 'Partnership') {
    const first = document.getElementById('firstName').value.trim();
    const last = document.getElementById('lastName').value.trim();
    const middle = document.getElementById('middleName')?.value.trim() || '';
    const gender = document.getElementById('gender').value;
    isValid = first && middle && last && gender && street && city && province && mobile;
  } else if (type === 'Corporate' || type === 'LGU') {
    const corp = document.getElementById('corporate_name').value.trim();
    isValid = corp && street && city && province && mobile;
  }

  document.getElementById('saveBtn').disabled = !isValid;
}

function validateForm() {
  const type = document.getElementById('lessorType').value;
  const street = document.getElementById('street').value.trim();
  const city = document.getElementById('city').value.trim();
  const province = document.getElementById('province').value.trim();
  const mobile = document.getElementById('mobileNumber').value.trim();

  let isValid = false;

  if (type === 'Individual' || type === 'Partnership') {
    const first = document.getElementById('firstName').value.trim();
    const last = document.getElementById('lastName').value.trim();
    const gender = document.getElementById('gender').value;
    isValid = first && last && gender && street && city && province && mobile;
  } else if (type === 'Corporate' || type === 'LGU') {
    const corp = document.getElementById('corporate_name').value.trim();
    isValid = corp && street && city && province && mobile;
  }

  document.getElementById('saveBtn').disabled = !isValid;
}
function goToStep(stepNumber) {
  // Hide all steps
  document.querySelectorAll('.step').forEach(step => step.style.display = 'none');

  // Show selected step
  document.getElementById(`step${stepNumber}`).style.display = 'block';

  // Update nav pills
  const navLinks = document.querySelectorAll('#stepTabs .nav-link');
  navLinks.forEach((link, index) => {
    link.classList.toggle('active', index === stepNumber - 1);
  });

  // If step 3, populate review
  if (stepNumber === 3) {
    populateReview();
  }
}
function populateReview() {
  const reviewList = document.getElementById('reviewList');
  reviewList.innerHTML = '';

  const fields = {
    'Lessor Type': document.getElementById('lessorType').value,
    'First Name': document.getElementById('firstName')?.value || '',
    'Middle Name': document.getElementById('middleName')?.value || '',
    'Last Name': document.getElementById('lastName')?.value || '',
    'Gender': document.getElementById('gender')?.value || '',
    'Corporate Name': document.getElementById('corporate_name')?.value || '',
    'LGU': document.getElementById('lgu')?.value || '',
    'Street/Barangay': document.getElementById('street')?.value || '',
    'City': document.getElementById('city')?.value || '',
    'Province': document.getElementById('province')?.value || '',
    'Mobile Number': document.getElementById('mobileNumber')?.value || '',
  };

  for (const [label, value] of Object.entries(fields)) {
    if (value) {
      const item = document.createElement('li');
      item.className = 'list-group-item d-flex justify-content-between align-items-center';
      item.innerHTML = `<strong>${label}</strong> <span>${value}</span>`;
      reviewList.appendChild(item);
    }
  }
}

function toggleLessorFields() {
  const type = document.getElementById('lessorType').value;
  const individualFields = document.getElementById('individualFields');
  const corporateField = document.getElementById('corporateField');
  const lguField = document.getElementById('lguField');

  // Hide all by default
  individualFields.style.display = 'none';
  corporateField.style.display = 'none';
  lguField.style.display = 'none';

  // Clear all required fields first
  clearRequiredFields();

  // Set common required fields
  const commonRequired = ['street', 'city', 'province', 'mobileNumber'];
  commonRequired.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.required = true;
  });

  // Show relevant section and set required fields
  if (type === 'Individual' || type === 'Partnership') {
    individualFields.style.display = 'block';
    ['firstName', 'middleName', 'lastName', 'gender'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.required = true;
    });
  } else if (type === 'Corporate') {
    corporateField.style.display = 'block';
    const corp = document.getElementById('corporate_name');
    if (corp) corp.required = true;
  } else if (type === 'LGU') {
    lguField.style.display = 'block';
    const lgu = document.getElementById('lgu');
    if (lgu) lgu.required = true;
  }

  // Validate the form
  validateForm();
}

function clearRequiredFields() {
  const allFieldIds = [
    'firstName', 'middleName', 'lastName', 'gender',
    'corporate_name', 'lgu',
    'street', 'city', 'province', 'mobileNumber'
  ];
  allFieldIds.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.required = false;
  });
}

function validateForm() {
  const type = document.getElementById('lessorType').value;
  const street = document.getElementById('street')?.value.trim() || '';
  const city = document.getElementById('city')?.value.trim() || '';
  const province = document.getElementById('province')?.value.trim() || '';
  const mobile = document.getElementById('mobileNumber')?.value.trim() || '';
  let isValid = false;

  if (type === 'Individual' || tpye === 'Partnership') {
    const first = document.getElementById('firstName')?.value.trim() || '';
    const last = document.getElementById('lastName')?.value.trim() || '';
    const middle = document.getElementById('middleName')?.value.trim() || '';
    const gender = document.getElementById('gender')?.value || '';
    isValid = first && middle && last && gender && street && city && province && mobile;
  } else if (type === 'Corporate') {
    const corp = document.getElementById('corporate_name')?.value.trim() || '';
    isValid = corp && street && city && province && mobile;
  } else if (type === 'LGU') {
    const lgu = document.getElementById('lgu')?.value.trim() || '';
    isValid = lgu && street && city && province && mobile;
  }

  const saveBtn = document.getElementById('saveBtn');
  if (saveBtn) saveBtn.disabled = !isValid;
}

// Call this on DOM load in case of pre-filled values
document.addEventListener('DOMContentLoaded', toggleLessorFields);


function limitMobileNumber(input) {
  input.value = input.value.replace(/[^\d]/g, '').slice(0, 11);
  validateForm();
}

// Revalidate form in real-time
document.querySelectorAll('input, select').forEach(el => {
  el.addEventListener('input', validateForm);
  el.addEventListener('change', validateForm);
});
     const toggleBtn = document.getElementById('toggleSidebar');
  const sidebar = document.getElementById('sidebarMenu');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
  });
  
  document.querySelector('form').addEventListener('submit', function (e) {
    const requiredFields = this.querySelectorAll('input[required], select[required]');
    let valid = true;

    requiredFields.forEach(field => {
      if (!field.value.trim()) {
        field.classList.add('is-invalid');
        valid = false;
      } else {
        field.classList.remove('is-invalid');
      }
    });

    if (!valid) {
      e.preventDefault(); // Stop form submission
      alert("Please fill in all required fields before saving.");
    }
  });


    function validateLastName() {
        let lastNameInput = document.getElementById("lastName");
        let warningMessage = document.getElementById("warningMessage");
        let suffixes = [" jr", " sr", " ii", " iii", " iv", " v", " vi"];
        let value = lastNameInput.value.toLowerCase();

        suffixes.forEach(suffix => {
            if (value.endsWith(suffix)) {
                warningMessage.classList.remove("d-none");
                lastNameInput.value = value.replace(new RegExp(suffix + "$", "i"), "").trim();
            }
        });
    }

    function limitMobileNumber(input) {
    // Allow backspace and delete keys
    if (event.keyCode === 8 || event.keyCode === 46) {
        return;
    }

    // Replace any non-numeric characters except "+"
    input.value = input.value.replace(/[^\d+]/g, '');

    // Limit length to 13 characters (including "+" and 10 digits)
        if (input.value.length > 11) {
            input.value = input.value.slice(0, 11);
        }
    }
    document.getElementById('logoutLink').addEventListener('click', function (e) {
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

    
    </script>
  </body>
</html>
