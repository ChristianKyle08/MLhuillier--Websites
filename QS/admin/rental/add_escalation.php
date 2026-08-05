<?php
ob_start(); // 1. Trap any accidental output from included files
session_start(); // 2. Start session BEFORE including config
   include '../../config/config.php';
   $conn = mysqli_connect($host, $username, $password, $database); 
   if (!$conn) {
      die("Connection failed: " . mysqli_connect_error());
   }

   if (!isset($_SESSION['admin_name'])) {
      header('location:../login_form.php');
   }
   if (isset($_POST['add_escalation'])) {
    $contractNumber = mysqli_real_escape_string($conn, $_POST['contract_number']);

    // Fetch contract details
    $query = "SELECT * FROM create_contract WHERE contract_number = '$contractNumber'";
    $result = $conn->query($query);
    $contract = $result->fetch_assoc();

    if ($contract) {
        $start = new DateTime($contract['contract_start']);
        $end = new DateTime($contract['contract_end']);

        // Other contract fields
        $mainzone = mysqli_real_escape_string($conn, $contract['mainzone'] ?? '');
        $zone = mysqli_real_escape_string($conn, $contract['zone'] ?? '');
        $region = mysqli_real_escape_string($conn, $contract['region'] ?? '');
        $area = mysqli_real_escape_string($conn, $contract['area'] ?? '');
        $branch_id = mysqli_real_escape_string($conn, $contract['branch_id'] ?? '');
        $branch = mysqli_real_escape_string($conn, $contract['branch'] ?? '');

        // Rental values
        $paymentDueDate = mysqli_real_escape_string($conn, $contract['payment_due_date'] ?? '');
        $monthlyRental = (float) ($contract['amount'] ?? 0);
        $vatType = mysqli_real_escape_string($conn, $contract['vat_type'] ?? '');
        $vatPercentage = 12.00;
        $netOfVat = (float) ($contract['net_of_vat'] ?? 0);
        $vatAmount = (float) ($contract['vat_amount'] ?? 0);
        $wtaxPercent = 5.00;
        $wtaxAmount = (float) ($contract['wtax'] ?? 0);
        $amountToLessor = (float) ($contract['edit_amount_lessor'] ?? 0);

        $expectedAmountToLessor = $monthlyRental - $wtaxAmount;
        $wtaxType = abs($expectedAmountToLessor - $amountToLessor) < 0.01 ? 'less_wtax' : 'net_wtax';

        $escalationPercent = 0.00;
        $fixedAmount = 0.00;
        $increase = 0.00;
        $yearlyAmount = $monthlyRental * 12;

        $effectivity_date = $contract['contract_start'];
        $expiry_date = $contract['contract_end'];
        $created_by = mysqli_real_escape_string($conn, $contract['created_by'] ?? '');

        $iterationStart = clone $start;
        $created_date = date('Y-m-d H:i:s');
        $currentMonthStart = new DateTime(date('Y-m-01')); // First day of the current month
        $iterationStart = clone $start;
        $rowCount = 0;

        while ($iterationStart < $end && $rowCount < 10) {
            $escalationStart = clone $iterationStart;
            $escalationEnd = (clone $iterationStart)->modify('+1 year');

            if ($escalationEnd > $end) {
                $escalationEnd = clone $end;
            }

            $start_date = $escalationStart->format('Y-m-d');
            $end_date = $escalationEnd->format('Y-m-d');

            // ✅ Status is Approved only if end_date is before the first of the current month
            $status = ($escalationEnd < $currentMonthStart) ? 'Approved' : '';

            $created_date = date('Y-m-d H:i:s');

            $insertQuery = "
                INSERT INTO escalation (
                    col_number, mainzone, zone, region, area, branch_id, branch,
                    effectivity_date, expiry_date, start_date, end_date, monthly_due_date,
                    monthly_rental, vat_type, vat_percent, 
                    net_of_vat, vat, wtax_type, wtax_percent, wtax, 
                    amount_to_lessor, escalation_percent, fixed_amount, increase, 
                    yearly_amount, created_date, created_by, status
                ) VALUES (
                    '$contractNumber', '$mainzone', '$zone', '$region', '$area', '$branch_id', '$branch',
                    '$effectivity_date', '$expiry_date', '$start_date', '$end_date', '$paymentDueDate',
                    '$monthlyRental', '$vatType', '$vatPercentage',
                    '$netOfVat', '$vatAmount', '$wtaxType', '$wtaxPercent', '$wtaxAmount',
                    '$amountToLessor', '$escalationPercent', '$fixedAmount', '$increase',
                    '$yearlyAmount', '$created_date', '$created_by', '$status'
                )
            ";

            if (!$conn->query($insertQuery)) {
                echo "<script>
                document.addEventListener('DOMContentLoaded', function () {
                    Swal.fire({
                        icon: 'error',
                        title: 'Insertion Failed!',
                        html: 'Error: " . addslashes($conn->error) . "',
                        confirmButtonColor: '#d70c0c',
                        confirmButtonText: '<i class=\"fa fa-times\"></i> Close'
                    }).then(() => {
                        window.location.href = 'add_escalation.php';
                    });
                });
                </script>";
                exit;
            }

            $iterationStart = clone $escalationEnd;
            $rowCount++;
        }

        echo "<script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Escalation Added!',
                    html: 'Escalation rows have been inserted successfully.',
                    confirmButtonColor: '#d70c0c',
                    confirmButtonText: '<i class=\"fa fa-check\"></i> OK'
                }).then(() => {
                    window.location.href = 'add_escalation.php';
                });
            });
        </script>";

    } else {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Contract Found',
                    text: 'Please select a valid contract.',
                    confirmButtonColor: '#d70c0c'
                }).then(() => {
                    window.location.href = 'add_escalation.php';
                });
            });
        </script>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>ML Rental - Add Escalation</title>
   <link rel="icon" href="../../assets/images/ml_logo.png" type="image/png">
    <!-- ✅ Local Google Font -->
    <link href="../../assets/css/poppins.css" rel="stylesheet">
    
    <!-- ✅ Local Bootstrap CSS -->
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ Local Bootstrap Icons -->
    <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">

    <!-- ✅ Local SweetAlert2 -->
    <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
  *{
           font-family: 'Poppins', sans-serif; 
           font-size:12px;
        }
/* Navbar */
.navbar {
  background-color: #fff;
  border-bottom: 3px solid #d70c0c;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.nav-link {
  color: #333 !important;
  font-weight: 500;
  font-size: 12px;
}

.nav-link:hover,
.dropdown-item:hover {
  color: #d70c0c !important;
}

.dropdown-menu {
  border-radius: 10px;
  border: 1px solid #eee;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
}

.dropdown-item {
  font-size: 12px;
}

/* Admin Info */
.admin-info {
  display: flex;
  align-items: center;
  gap: 15px;
  font-size: 12px;
}

.admin-info i {
  color: #d70c0c;
}
.escalation-form-container {
  max-width: 600px;
  margin: 2rem auto;
  padding: 2rem;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
}

.escalation-form h3 {
  color: #d70c0c;
  margin-bottom: 1rem;
  font-size: 20px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.escalation-form label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 14px;
  color: #333;
}

.escalation-form label i {
  color: #d70c0c;
  margin-right: 6px;
}

.escalation-form select {
  width: 100%;
  padding: 0.6rem;
  font-size: 14px;
  border: 1px solid #ccc;
  border-radius: 6px;
  margin-bottom: 1.5rem;
  background-color: #f9f9f9;
  transition: border-color 0.2s;
}

.escalation-form select:focus {
  border-color: #d70c0c;
  outline: none;
  box-shadow: 0 0 0 0.1rem rgba(215, 12, 12, 0.2);
}

.escalation-form button {
  background-color: #d70c0c;
  color: #fff;
  padding: 0.6rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: background-color 0.2s ease;
}

.escalation-form button:hover {
  background-color: #b80000;
}

.contract-info {
  margin-top: 2rem;
  background-color: #f9f9f9;
  padding: 1rem;
  border-radius: 8px;
  font-size: 14px;
}

.contract-info p {
  margin: 0.5rem 0;
  color: #333;
  display: flex;
  align-items: center;
  gap: 8px;
}

.contract-info i {
  color: #d70c0c;
  font-size: 14px;
}

    </style>
</head>
<body>
<?php include ('navbar_admin.php'); ?>
<div class="escalation-form-container">
  <form action="add_escalation.php" method="post" class="escalation-form">
    <h3><i class="bi bi-plus-circle me-2"></i> Add New Escalation</h3>

    <label for="contract_number">
    <i class="bi bi-file-earmark-text me-2"></i> Select Contract Number (Not exist in Escalation Table):
    </label>
    <select name="contract_number" id="contract_number" required>
      <option value="">-- Select Contract --</option>
      <?php
      $query = "
          SELECT contract_number, region, area, branch, contract_start, contract_end
          FROM create_contract 
          WHERE contract_number NOT IN (
              SELECT col_number FROM escalation WHERE col_number IS NOT NULL
          )
      ";
      $result = $conn->query($query);

      if ($result && $result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) {
              $contract_number = htmlspecialchars($row['contract_number']);
              $region = htmlspecialchars($row['region']);
              $area = htmlspecialchars($row['area']);
              $branch = htmlspecialchars($row['branch']);
              $start = htmlspecialchars($row['contract_start']);
              $end = htmlspecialchars($row['contract_end']);
              echo "<option value=\"$contract_number\" 
                      data-region=\"$region\" 
                      data-area=\"$area\" 
                      data-branch=\"$branch\" 
                      data-start=\"$start\" 
                      data-end=\"$end\">"
                      . "$contract_number | $region | $area | $branch | $start to $end"
                  . "</option>";
          }
      } else {
          echo '<option value="">No Available Contracts</option>';
      }
      ?>
    </select>

    <button type="submit" name="add_escalation">
      <i class="bi bi-check-circle"></i> Add Escalation
    </button>

    <div class="contract-info">
    <p><i class="bi bi-geo-alt-fill text-danger me-1"></i> Region: <span id="region_display"></span></p>
    <p><i class="bi bi-geo-fill text-danger me-1"></i> Area: <span id="area_display"></span></p>
    <p><i class="bi bi-building text-danger me-1"></i> Branch: <span id="branch_display"></span></p>
    <p><i class="bi bi-calendar-event text-danger me-1"></i> Effectivity Date: <span id="start_display"></span></p>
    <p><i class="bi bi-calendar-x text-danger me-1"></i> Expiry Date: <span id="end_display"></span></p>
    </div>
  </form>
</div>

<div class="modal fade" id="dbModal" tabindex="-1" aria-labelledby="dbModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow border-0 rounded-4" style="background-color: #fff; color: #333;">
      
      <div class="modal-header border-0" style="background-color: #f8f9fa;">
        <h5 class="modal-title d-flex align-items-center" id="dbModalLabel">
          <i class="bi bi-shield-lock me-2" style="color: #d70c0c;"></i> Secure Access
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body px-4 pt-3">
        <p class="mb-3">Please enter your password to proceed:</p>

        <div class="mb-3">
          <label for="passwordInput" class="form-label">Password</label>
          <input type="password" class="form-control rounded-pill border-secondary" id="passwordInput" placeholder="••••••••">
        </div>

        <div class="form-check mb-4">
          <input class="form-check-input" type="checkbox" id="showPassword" onchange="togglePasswordVisibility()">
          <label class="form-check-label" for="showPassword">Show Password</label>
        </div>

        <div class="d-grid">
          <button id="submitPassword" class="btn rounded-pill text-white" style="background-color: #d70c0c;">
            <i class="bi bi-box-arrow-in-right me-2"></i>Submit
          </button>
        </div>
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

<script src="../../assets/sweetalert2/dist/sweetalert2.min.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
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
document.getElementById('contract_number').addEventListener('change', function () {
  var selectedOption = this.options[this.selectedIndex];
  document.getElementById('region_display').textContent = selectedOption.getAttribute('data-region') || '';
  document.getElementById('area_display').textContent = selectedOption.getAttribute('data-area') || '';
  document.getElementById('branch_display').textContent = selectedOption.getAttribute('data-branch') || '';
  document.getElementById('start_display').textContent = selectedOption.getAttribute('data-start') || '';
  document.getElementById('end_display').textContent = selectedOption.getAttribute('data-end') || '';
});

const passwordInput = document.getElementById('passwordInput');
    const showPassword = document.getElementById('showPassword');
    const submitPassword = document.getElementById('submitPassword');
    const dbModalInstance = new bootstrap.Modal(document.getElementById('dbModal'));

    // Show modal when any .db-link is clicked
    document.querySelectorAll('.db-link').forEach(link => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            dbModalInstance.show();
        });
    });

    // Toggle show/hide password
    showPassword.addEventListener('change', () => {
        passwordInput.type = showPassword.checked ? 'text' : 'password';
    });

    // Password check
    submitPassword.addEventListener('click', () => {
        const correctPassword = 'CADMLhuillierDB2023';
        if (passwordInput.value === correctPassword) {
            window.location.href = 'db.php';
        } else {
            alert('Incorrect password. Access denied.');
        }
    });

    // 1. Disable Right-Click (Context Menu)
document.addEventListener('contextmenu', (e) => e.preventDefault());

// 2. Disable Keyboard Shortcuts
document.onkeydown = function(e) {
    // F12
    if (e.keyCode == 123) return false;
    
    // Ctrl+Shift+I (Inspect)
    if (e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) return false;
    
    // Ctrl+Shift+J (Console)
    if (e.ctrlKey && e.shiftKey && e.keyCode == 'J'.charCodeAt(0)) return false;
    
    // Ctrl+Shift+C (Element Selector)
    if (e.ctrlKey && e.shiftKey && e.keyCode == 'C'.charCodeAt(0)) return false;
    
    // Ctrl+U (View Source)
    if (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false;
};

// 3. The Debugger Trap
// If the console is opened, this loop will trigger the debugger, 
// effectively "freezing" the user's ability to browse the code.
(function() {
    var protect = function() {
        try {
            (function() {
                var handler = function() {
                    debugger;
                };
                setInterval(handler, 100);
            })();
        } catch (e) {}
    };
    protect();
})();

</script>

</body>
</html>