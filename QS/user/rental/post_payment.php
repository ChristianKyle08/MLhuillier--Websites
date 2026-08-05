<?php 
session_start();
include '../../config/config.php';

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}

require '../../vendor/autoload.php'; // Including vendor for any dependencies

echo '<script src="../../assets/sweetalert2/dist/sweetalert2.all.min.js"></script>';
echo '<link rel="stylesheet" href="../../assets/sweetalert2/dist/sweetalert2.min.css">';
echo '<script src="../../assets/js/jquery-3.7.1.js"></script>';

$successUpdates = 0;
$successInserts = 0; // Count successful inserts
$errors = []; // Initialize an error tracking array
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["payout_submit"])) {
    if (isset($_FILES["csvFile"]) && $_FILES["csvFile"]["error"] === 0) {
        $csvFile = fopen($_FILES["csvFile"]["tmp_name"], "r");

        // Skip the first 5 lines (headers)
        for ($i = 0; $i < 5; $i++) {
            fgetcsv($csvFile);
        }

        $importSuccess = true;
        $duplicateTransactions = []; // Array to hold duplicate transactions
        $successfulImports = 0; // Counter for successful imports
        $updatedRecords = 0; // Counter for updated records
        $hasValidData = false; // Flag to check if any valid row exists
        $stopImport = false; // Flag to stop import if an all-empty row is found

        while (($row = fgetcsv($csvFile)) !== false) {
            // Ensure there are at least 20 columns in the row
            if (count($row) < 20) {
                continue;
            }

            // Check if all required columns are non-empty
            $allEmpty = true;
            for ($i = 1; $i <= 19; $i++) {
                if (!empty(trim($row[$i]))) {
                    $allEmpty = false;
                    break;
                }
            }

            if ($allEmpty) {
                $stopImport = true;
                break;
            }

            $hasValidData = true;

            // Sanitize inputs
            $control_number = $conn->real_escape_string($row[1]);
            $receiver_customerID = intval($row[2]);
            $receiver_kyc = $conn->real_escape_string($row[3]);
            $receiver_name = $conn->real_escape_string($row[4]);
            $sender_customerID = intval($row[5]);
            $sender_name = $conn->real_escape_string($row[6]);
            $charge_to = $conn->real_escape_string($row[7]);
            $kptn = $conn->real_escape_string($row[8]);
            $sendout_datetime = $conn->real_escape_string($row[9]);
            $payout_datetime = $conn->real_escape_string($row[10]);
            $principal = number_format(floatval(str_replace(',', '', $row[11])), 2, '.', '');
            $payout_amount = number_format(floatval(str_replace(',', '', $row[12])), 2, '.', '');
            $service_charge = number_format(floatval(str_replace(',', '', $row[13])), 2, '.', '');
            $commission = number_format(floatval(str_replace(',', '', $row[14])), 2, '.', '');
            $so_operator = $conn->real_escape_string($row[15]);
            $sendout_branch = $conn->real_escape_string($row[16]);
            $sendout_branch_id = intval($row[17]);
            $payout_branch = $conn->real_escape_string($row[18]);
            $payout_branch_id = intval($row[19]);
            $region = $conn->real_escape_string($row[20]);
            $remote_operator = $conn->real_escape_string($row[21]);
            $remote_branch = $conn->real_escape_string($row[22]);
            // Safely get column 23 value (status)
            $status = isset($row[23]) ? $conn->real_escape_string($row[23]) : null;


            // Validate transaction_date before processing
            $transaction_month = date('m', strtotime($sendout_datetime));
            $transaction_year = date('Y', strtotime($sendout_datetime));

            // Check if record with the same kptn exists
            $checkKptnSql = "SELECT COUNT(*) as count FROM payout WHERE kptn = '$kptn'";
            $result = $conn->query($checkKptnSql);
            $rowCount = $result->fetch_assoc()['count'];

            if ($rowCount > 0) {
                // Update existing record
                $updateSql = "UPDATE payout SET 
                    control_number = '$control_number',
                    receiver_customerID = $receiver_customerID,
                    receiver_kyc = '$receiver_kyc',
                    receiver_name = '$receiver_name',
                    sender_customerID = $sender_customerID,
                    sender_name = '$sender_name',
                    charge_to = '$charge_to',
                    sendout_datetime = '$sendout_datetime',
                    payout_datetime = '$payout_datetime',
                    principal = $principal,
                    payout_amount = $payout_amount,
                    service_charge = $service_charge,
                    commission = $commission,
                    so_operator = '$so_operator',
                    sendout_branch = '$sendout_branch',
                    sendout_branch_id = $sendout_branch_id,
                    payout_branch = '$payout_branch',
                    payout_branch_id = $payout_branch_id,
                    region = '$region',
                    remote_operator = '$remote_operator',
                    remote_branch = '$remote_branch',
                    status = '$status',
                    posted_by = '" . $_SESSION['user_name'] . "',
                    posted_datetime = NOW()
                    WHERE kptn = '$kptn'";
                
                if ($conn->query($updateSql)) {
                    $updatedRecords++;
                    // Update transactional table
                    $updateTransactionalSql = "UPDATE transactional 
                        SET status = 'Paid', 
                        kptn = '$kptn',
                        payout_branch = '$payout_branch',
                        payout_status = '$status'
                        WHERE branch_id = '$sendout_branch_id' 
                        AND MONTH(transaction_date) = '$transaction_month' 
                        AND YEAR(transaction_date) = '$transaction_year'
                        AND status != 'Cancelled'";
                    
                    $conn->query($updateTransactionalSql); // Ignoring error for this
                } else {
                    $importSuccess = false;
                    break;
                }

            } else {
                // Insert new record
                $insertSql = "INSERT INTO payout 
                    (control_number, receiver_customerID, receiver_kyc, receiver_name, sender_customerID, sender_name, charge_to, kptn, sendout_datetime, payout_datetime, principal, payout_amount, service_charge, commission, so_operator, sendout_branch, sendout_branch_id, payout_branch, payout_branch_id, region, remote_operator, remote_branch, status, posted_by, posted_datetime)
                    VALUES 
                    ('$control_number', $receiver_customerID, '$receiver_kyc', '$receiver_name', $sender_customerID, '$sender_name', '$charge_to', '$kptn', '$sendout_datetime', '$payout_datetime', $principal, $payout_amount, $service_charge, $commission, '$so_operator', '$sendout_branch', '$sendout_branch_id', '$payout_branch', $payout_branch_id, '$region', '$remote_operator', '$remote_branch', '$status', '" . $_SESSION['user_name'] . "', NOW())";
                
                if ($conn->query($insertSql)) {
                    $successfulImports++;

                    // Update transactional table
                    $updateTransactionalSql = "UPDATE transactional 
                        SET status = 'Paid', 
                        kptn = '$kptn',
                        payout_branch = '$payout_branch',
                        payout_status = '$status'
                        WHERE branch_id = '$sendout_branch_id' 
                        AND kptn = '$kptn'
                        AND status != 'Cancelled'";
                    
                    $conn->query($updateTransactionalSql); // Ignoring error for this
                } else {
                    $importSuccess = false;
                    break;
                }
            }
        }

        fclose($csvFile);

        // Prepare the message for the modal
        $message = '';
        if ($updatedRecords > 0) {
            $message .= "$updatedRecords records were updated.<br>";
        }
        if ($successfulImports > 0) {
            $message .= "$successfulImports new records were imported successfully.<br>";
        }
        if ($message === '') {
            $message = "No valid data was found to import.";
        }

        $modalTitle = "Import Summary";

        // Ensure special characters are properly encoded for JavaScript
        $modalTitle = addslashes($modalTitle);
        $message = addslashes($message);

        // Trigger modal display
        echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    var title = "' . $modalTitle . '";
                    var message = "' . $message . '";
                    var buttonText = "OK";
                    showModal(title, message, buttonText);
                });
              </script>';
    } else {
        echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    showModal("Error!", "There was an error uploading the file.", "OK");
                });
              </script>';
    }
}


if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["sendout_submit"])) {
    if (isset($_FILES["csvFile"]) && $_FILES["csvFile"]["error"] === 0) {
        $csvFile = fopen($_FILES["csvFile"]["tmp_name"], "r");

        // Skip the first 5 lines (headers)
        for ($i = 0; $i < 5; $i++) {
            fgetcsv($csvFile);
        }

        $importSuccess = true;
        $duplicateTransactions = []; // Array to hold duplicate transactions
        $successfulImports = 0; // Counter for successful imports
        $updatedRecords = 0; // Counter for updated records
        $hasValidData = false; // Flag to check if any valid row exists

        $stopImport = false; // Flag to stop import if an all-empty row is found

        while (($row = fgetcsv($csvFile)) !== false) {
            // Ensure there are at least 20 columns in the row
            if (count($row) < 20) {
                continue;
            }

            // Check if all required columns are non-empty
            $allEmpty = true;
            for ($i = 1; $i <= 19; $i++) {
                if (!empty(trim($row[$i]))) {
                    $allEmpty = false;
                    break;
                }
            }

            if ($allEmpty) {
                // Set flag to stop import process
                $stopImport = true;
                break;
            }

            $hasValidData = true;

            // Continue processing only if the row has valid data
            $control_number = $conn->real_escape_string($row[1]);
            $sender_customerID = intval($row[2]);
            $sender_name = $conn->real_escape_string($row[3]);
            $receiver_name = $conn->real_escape_string($row[4]);
            $charge_to = $conn->real_escape_string($row[5]);
            $kptn = $conn->real_escape_string($row[6]);
            $contact_number = $conn->real_escape_string($row[7]);
            $sendout_datetime = $conn->real_escape_string($row[8]);
            $or_number = $conn->real_escape_string($row[9]);
            $principal = number_format(floatval(str_replace(',', '', $row[10])), 2, '.', '');
            $charge = number_format(floatval(str_replace(',', '', $row[11])), 2, '.', '');
            $commission = number_format(floatval(str_replace(',', '', $row[12])), 2, '.', '');
            $so_operator = $conn->real_escape_string($row[13]);
            $region = $conn->real_escape_string($row[14]);
            $sendout_branch = $conn->real_escape_string($row[15]);
            $branch_id = intval($row[16]);
            $remote_operator = $conn->real_escape_string($row[17]);
            $remote_branch = $conn->real_escape_string($row[18]);
            $status = $conn->real_escape_string($row[19]);

             // Validate transaction_date before processing
             $transaction_month = date('m', strtotime($sendout_datetime));
             $transaction_year = date('Y', strtotime($sendout_datetime));

            // Check if the record with the same kptn exists
            $checkKptnSql = "SELECT COUNT(*) as count FROM sendout WHERE kptn = '$kptn'";
            $result = $conn->query($checkKptnSql);
            $rowCount = $result->fetch_assoc()['count'];

            if ($rowCount > 0) {
                // Update existing record if kptn exists
                $updateSql = "UPDATE sendout SET 
                    control_number = '$control_number',
                    sender_customerID = $sender_customerID,
                    sender_name = '$sender_name',
                    receiver_name = '$receiver_name',
                    charge_to = '$charge_to',
                    contact_number = '$contact_number',
                    sendout_datetime = '$sendout_datetime',
                    or_number = '$or_number',
                    principal = $principal,
                    charge = $charge,
                    commission = $commission,
                    so_operator = '$so_operator',
                    region = '$region',
                    sendout_branch = '$sendout_branch',
                    branch_id = $branch_id,
                    remote_operator = '$remote_operator',
                    remote_branch = '$remote_branch',
                    status = '$status',
                    imported_by = '" . $_SESSION['user_name'] . "',
                    imported_datetime = NOW()
                    WHERE kptn = '$kptn'";
                
                if ($conn->query($updateSql)) {
                    $updatedRecords++;
                     // Update transactional table
                     $updateTransactionalSql = "UPDATE transactional 
                     SET status = 'Paid', 
                     kptn = '$kptn',
                     payout_status = '$status'
                     WHERE branch_id = '$branch_id' 
                     AND MONTH(transaction_date) = '$transaction_month' 
                     AND YEAR(transaction_date) = '$transaction_year'
                     AND status != 'Cancelled'";
                 
                 $conn->query($updateTransactionalSql); // Ignoring error for this
                } else {
                    $importSuccess = false;
                    break;
                }
            } else {
                // Insert new record if kptn does not exist
                $insertSql = "INSERT INTO sendout 
                    (control_number, sender_customerID, sender_name, receiver_name, charge_to, kptn, contact_number, sendout_datetime, or_number, principal, charge, commission, so_operator, region, sendout_branch, branch_id, remote_operator, remote_branch, status, imported_by, imported_datetime)
                    VALUES 
                    ('$control_number', $sender_customerID, '$sender_name', '$receiver_name', '$charge_to', '$kptn', '$contact_number', '$sendout_datetime', '$or_number', $principal, $charge, $commission, '$so_operator', '$region', '$sendout_branch', $branch_id, '$remote_operator', '$remote_branch', '$status', '" . $_SESSION['user_name'] . "', NOW())";
                
                if ($conn->query($insertSql)) {
                    $successfulImports++;

                     // Update transactional table
                     $updateTransactionalSql = "UPDATE transactional 
                     SET status = 'Paid', 
                     kptn = '$kptn',
                     payout_status = '$status'
                     WHERE branch_id = '$branch_id' 
                     AND MONTH(transaction_date) = '$transaction_month' 
                     AND YEAR(transaction_date) = '$transaction_year'
                     AND status != 'Cancelled'";
                 
                 $conn->query($updateTransactionalSql); // Ignoring error for this
                } else {
                    $importSuccess = false;
                    break;
                }
            }
        }

        fclose($csvFile);

        // Prepare the message for the modal
        $message = '';
        if ($updatedRecords > 0) {
            $message .= "$updatedRecords records were updated.<br>";
        }
        if ($successfulImports > 0) {
            $message .= "$successfulImports new records were imported successfully.<br>";
        }
        if ($message === '') {
            $message = "No valid data was found to import.";
        }

        $modalTitle = "Import Summary";

        // Ensure special characters are properly encoded for JavaScript
        $modalTitle = addslashes($modalTitle);
        $message = addslashes($message);

        // Trigger modal display
        echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    var title = "' . $modalTitle . '";
                    var message = "' . $message . '";
                    var buttonText = "OK";
                    showModal(title, message, buttonText);
                });
              </script>';
    } else {
        echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    showModal("Error!", "There was an error uploading the file.", "OK");
                });
              </script>';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
    <head>
    <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="generator" content="Mobirise v5.9.13, mobirise.com">
        <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
        <link rel="shortcut icon" href="../../assets/images/rental_logo.png" type="image/x-icon">
        <meta name="description" content="">
        <title>ML Rental - Post Payment</title>
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
    </head>
<body>
<?php include ('navbar.php'); ?>
<!-- Your Bootstrap Modal HTML -->
<div class="modal fade" id="customModal" tabindex="-1" aria-labelledby="custom_modal-title" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg rounded-3 border-0">
      <div class="modal-header bg-danger text-white">
      <h5 class="modal-title text-white" id="custom_modal-title"></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p id="custom_modal-message" class="fs-6"></p>
      </div>
      <div class="modal-footer justify-content-center">
        <button id="custom_modal-button" type="button" class="btn btn-danger px-4" data-bs-dismiss="modal">
          <i class="bi bi-check2-circle me-2"></i> OK
        </button>
      </div>
    </div>  
  </div>
</div>
<div id="mainContent">
    <!-- Sidebar Toggle -->
    <button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
        <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
        <span class="fw-normal">Menu</span>
    </button>
    <div class="container py-3">
    <!-- Import Type Selection -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
      <div class="card-body">
        <label for="importType" class="form-label fw-semibold">
          <i class="bi bi-folder2-open text-danger me-2"></i> Select Import Type 
          <em class="text-danger fw-normal">(Upload first the KPX SENDOUT transactions reports)</em>
        </label>
        <select id="importType" class="form-select shadow-sm" onchange="showForm()">
          <option value="">-- Select Option --</option>
          <option value="sendout">KPX Sendout (Rental)</option>
          <option value="payout">KPX Payout (Rental)</option>
        </select>
      </div>
    </div>

    <!-- Sendout Form -->
    <div id="sendoutForm" class="card shadow-sm border-0 rounded-3 mb-4" style="display:none;">
      <div class="card-header bg-danger text-white d-flex align-items-center">
        <i class="bi bi-send-check me-2"></i>
        <h6 class="mb-0 text-white">Upload KPX Sendout Transactions (.csv)</h6>
      </div>
      <div class="card-body">
        <form action="" method="POST" enctype="multipart/form-data" class="d-flex flex-column gap-3">
          <div>
            <label for="sendoutFile" class="form-label fw-semibold">
              <i class="bi bi-upload me-2 text-danger"></i> Choose KPX Sendout File
            </label>
            <input type="file" name="csvFile" id="sendoutFile" class="form-control shadow-sm" accept=".csv" required>
          </div>
          <button type="submit" name="sendout_submit" class="btn btn-danger w-100 d-flex align-items-center justify-content-center">
            <i class="bi bi-cloud-arrow-up me-2"></i>  POST KPX SENDOUT PAYMENT
          </button>
        </form>
      </div>
    </div>

    <!-- Payout Form -->
    <div id="payoutForm" class="card shadow-sm border-0 rounded-3 mb-4" style="display:none;">
      <div class="card-header bg-danger text-white d-flex align-items-center">
        <i class="bi bi-cash-coin me-2"></i>
        <h6 class="mb-0 text-white">Upload KPX Payout Transactions (.csv)</h6>
      </div>
      <div class="card-body">
        <form id="payoutFormElement" action="" method="POST" enctype="multipart/form-data" class="d-flex flex-column gap-3">
          <div>
            <label for="payoutFile" class="form-label fw-semibold">
              <i class="bi bi-upload me-2 text-danger"></i> Choose KPX Payout File
            </label>
            <input type="file" name="csvFile" id="payoutFile" class="form-control shadow-sm" accept=".csv" required>
          </div>
          <button type="submit" name="payout_submit" class="btn btn-danger w-100 d-flex align-items-center justify-content-center">
            <i class="bi bi-cloud-arrow-up me-2"></i> POST KPX PAYOUT PAYMENT
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
let sendoutUploaded = false;

function showForm() {
  document.getElementById('payoutForm').style.display = 'none';
  document.getElementById('sendoutForm').style.display = 'none';

  let type = document.getElementById('importType').value;

  if (type === 'sendout') {
    document.getElementById('sendoutForm').style.display = 'block';
  } else if (type === 'payout') {
    if (!sendoutUploaded) {
      // Show modal warning if payout selected before sendout
      let modal = new bootstrap.Modal(document.getElementById('customModal'));
      document.getElementById('custom_modal-message').innerText = "Please upload the SENDOUT file before importing Payout.";
      modal.show();
      document.getElementById('importType').value = "";
      return;
    }
    document.getElementById('payoutForm').style.display = 'block';
  }
}

// ✅ Simulate sendout upload success (should be set in PHP after processing sendout)
<?php if (isset($_POST['sendout_submit'])): ?>
sendoutUploaded = true;
<?php endif; ?>
</script>

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
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    // ✅ Bootstrap modal instance
    var modalElement = document.getElementById('customModal');
    var customModal = new bootstrap.Modal(modalElement);

    function showModal(title, message, buttonText) {
    document.getElementById("custom_modal-title").innerHTML = 
        '<span class="text-white"><i class="bi bi-info-circle me-2"></i> ' + title + '</span>';
    document.getElementById("custom_modal-message").innerHTML = message;
    document.getElementById("custom_modal-button").innerHTML = 
        '<i class="bi bi-check2-circle me-2"></i> ' + buttonText;

    var myModal = new bootstrap.Modal(document.getElementById("customModal"));
    myModal.show();
}


    // ✅ Define the hideModal function
    function hideModal() {
        customModal.hide();
    }

    // ✅ Close button listener (only needed if you manually bind)
    document.getElementById('custom_modal-button').addEventListener('click', hideModal);

    // ✅ Trigger modal based on PHP
    <?php if (isset($importSuccess)) { ?>
        var title = "<?php echo addslashes($modalTitle); ?>";
        var message = "<?php echo addslashes($message); ?>";
        var buttonText = "OK";
        showModal(title, message, buttonText);
    <?php } ?>
});

// ✅ Form toggle function
function showForm() {
    var importType = document.getElementById("importType").value;
    var payoutForm = document.getElementById("payoutForm");
    var sendoutForm = document.getElementById("sendoutForm");

    payoutForm.style.display = "none";
    sendoutForm.style.display = "none";

    if (importType === "payout") {
        payoutForm.style.display = "block";
    } else if (importType === "sendout") {
        sendoutForm.style.display = "block";
    }
}

// ✅ Sidebar toggle
const toggleBtn = document.getElementById('toggleSidebar');
const sidebar = document.getElementById('sidebarMenu');
toggleBtn?.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
});

// ✅ Logout modal
document.getElementById('logoutLink')?.addEventListener('click', function (e) {
    e.preventDefault();
    const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'), {
        backdrop: 'static',
        keyboard: false
    });
    logoutModal.show();
    setTimeout(() => window.location.href = '../../logout.php', 2500);
});
</script>

</body>
</html>