<?php
   session_start();
   include '../../config/config.php';
   if (!isset($_SESSION['user_name'])) {
        header('location:login_form.php');
    }
    require '../../vendor/autoload.php'; // Make sure to include the autoload file from Composer

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (isset($_POST['export'])) {
    // Retrieve form input
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $corporate = $_POST['corporate'] ?? '';
    $status = $_POST['status'] ?? '';

    // Base query
    $query = "SELECT transaction_date, branch_id, branch, zone, region, amount, vat_type, vat_amount, net_of_vat, wtax, edit_amount_lessor, status, new_due_date, dueDate_request_status 
              FROM transactional 
              WHERE 1"; 
    if (!empty($start_date)) {
        $start_date = mysqli_real_escape_string($conn, $start_date); 
        $query .= " AND transaction_date >= '$start_date'";
    }
    if (!empty($end_date)) {
        $end_date = mysqli_real_escape_string($conn, $end_date); 
        $query .= " AND transaction_date <= '$end_date'";
    }
    if (!empty($corporate)) {
        $corporate = mysqli_real_escape_string($conn, $corporate); 
        $query .= " AND corporate_name = '$corporate'";
    }
    if (!empty($status)) {
        $status = mysqli_real_escape_string($conn, $status); 
        $query .= " AND status = '$status'";
    }

    // Execute query
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        // Initialize spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Payment Due');
        $sheet->setCellValue('B1', 'Branch ID');
        $sheet->setCellValue('C1', 'Branch');
        $sheet->setCellValue('D1', 'Zone');
        $sheet->setCellValue('E1', 'Region');
        $sheet->setCellValue('F1', 'Amount');
        $sheet->setCellValue('G1', 'Vat Type');
        $sheet->setCellValue('H1', 'Vat');
        $sheet->setCellValue('I1', 'Net of Vat');
        $sheet->setCellValue('J1', 'Wtax');
        $sheet->setCellValue('K1', 'Amount to Lessor');
        $sheet->setCellValue('L1', 'Status');

        $totalAmount = $totalVat = $totalNetOfVat = $totalWtax = $totalEditAmountLessor = 0;
        $rowNumber = 2; 
        while ($row = mysqli_fetch_assoc($result)) {

            $transactionDate = (!empty($row['new_due_date']) && $row['dueDate_request_status'] === 'Approved') 
                               ? $row['new_due_date'] 
                               : $row['transaction_date'];

            $sheet->setCellValue('A' . $rowNumber, htmlspecialchars(date("F j, Y", strtotime($transactionDate))));
            $sheet->setCellValue('B' . $rowNumber, $row['branch_id']);
            $sheet->setCellValue('C' . $rowNumber, htmlspecialchars($row['branch']));
            $sheet->setCellValue('D' . $rowNumber, htmlspecialchars($row['zone']));
            $sheet->setCellValue('E' . $rowNumber, htmlspecialchars($row['region']));
            $sheet->setCellValue('F' . $rowNumber, number_format($row['amount'], 2));
            $sheet->setCellValue('G' . $rowNumber, htmlspecialchars($row['vat_type']));
            $sheet->setCellValue('H' . $rowNumber, number_format($row['vat_amount'], 2));
            $sheet->setCellValue('I' . $rowNumber, number_format($row['net_of_vat'], 2));
            $sheet->setCellValue('J' . $rowNumber, number_format($row['wtax'], 2));
            $sheet->setCellValue('K' . $rowNumber, number_format($row['edit_amount_lessor'], 2));
            $sheet->setCellValue('L' . $rowNumber, htmlspecialchars($row['status']));

            $totalAmount += $row['amount'];
            $totalVat += $row['vat_amount'];
            $totalNetOfVat += $row['net_of_vat'];
            $totalWtax += $row['wtax'];
            $totalEditAmountLessor += $row['edit_amount_lessor'];

            $rowNumber++;
        }

        // Add totals row at the end
        $sheet->setCellValue('E' . $rowNumber, 'Totals:');
        $sheet->setCellValue('F' . $rowNumber, number_format($totalAmount, 2));
        $sheet->setCellValue('H' . $rowNumber, number_format($totalVat, 2));
        $sheet->setCellValue('I' . $rowNumber, number_format($totalNetOfVat, 2));
        $sheet->setCellValue('J' . $rowNumber, number_format($totalWtax, 2));
        $sheet->setCellValue('K' . $rowNumber, number_format($totalEditAmountLessor, 2));

        // Set headers for file download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Corporate_Rentals.xlsx"');
        header('Cache-Control: max-age=0');

        // Output the file
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit; // Ensure no further output is sent
    } else {
        echo "<p>No records found.</p>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Site made with Mobirise Website Builder v5.9.13, https://mobirise.com -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <link rel="shortcut icon" href="../../assets/images/rental_logo.png" type="image/x-icon">
    <meta name="description" content="">
    
    <title>ML Rental - Corporate Report</title>
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
<div id="mainContent">
        <button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
            <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
            <span class="fw-normal">Menu</span>
        </button>
        <div class="container py-4">
    <!-- Filter Card -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-header bg-danger text-white d-flex align-items-center">
            <i class="bi bi-funnel-fill me-2"></i>
            <h5 class="mb-0 text-white">Filter Transactions</h5>
        </div>
        <div class="card-body">
            <form method="post" class="row g-3 align-items-end">
                <!-- Start Date -->
                <div class="col-md-3">
                    <label for="start_date" class="form-label fw-bold">
                        <i class="bi bi-calendar-event me-1"></i> Start Date
                    </label>
                    <input type="date" name="start_date" id="start_date"
                           class="form-control"
                           value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>" required>
                </div>

                <!-- End Date -->
                <div class="col-md-3">
                    <label for="end_date" class="form-label fw-bold">
                        <i class="bi bi-calendar-event me-1"></i> End Date
                    </label>
                    <input type="date" name="end_date" id="end_date"
                           class="form-control"
                           value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>" required>
                </div>

                <!-- Corporate -->
                <div class="col-md-3">
                    <label for="corporate" class="form-label fw-bold">
                        <i class="bi bi-building me-1"></i> Corporate
                    </label>
                    <select name="corporate" id="corporate" class="form-select">
                        <option value="">-- Select --</option>
                        <?php
                        $corporateSql = "SELECT DISTINCT corporate_name FROM transactional WHERE corporate_name != '' ORDER BY corporate_name ASC";
                        $resultCorporate = mysqli_query($conn, $corporateSql);
                        if ($resultCorporate) {
                            while ($rowCorporate = mysqli_fetch_assoc($resultCorporate)) {
                                $selected = (isset($_POST['corporate']) && $_POST['corporate'] == $rowCorporate['corporate_name']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($rowCorporate['corporate_name']) . "' $selected>" . htmlspecialchars($rowCorporate['corporate_name']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Status -->
                <div class="col-md-3">
                    <label for="status" class="form-label fw-bold">
                        <i class="bi bi-flag-fill me-1"></i> Status
                    </label>
                    <select name="status" id="status" class="form-select">
                        <option value="">-- Select --</option>
                        <?php
                        $statusSql = "SELECT DISTINCT status FROM transactional WHERE status != '' ORDER BY status ASC";
                        $resultStatus = mysqli_query($conn, $statusSql);
                        if ($resultStatus) {
                            while ($rowStatus = mysqli_fetch_assoc($resultStatus)) {
                                $selected = (isset($_POST['status']) && $_POST['status'] == $rowStatus['status']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($rowStatus['status']) . "' $selected>" . htmlspecialchars($rowStatus['status']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <!-- Action Buttons -->
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button type="submit" name="export" id="export" class="btn btn-success">
                        <i class="bi bi-download me-1"></i> Export
                    </button>
                    <button type="submit" name="proceed" id="proceed" class="btn btn-danger">
                        <i class="bi bi-search me-1"></i> Proceed
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Section -->
    <?php
    if (isset($_POST['proceed'])) {
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $corporate = $_POST['corporate'];
        $status = $_POST['status'];

        $query = "SELECT * FROM transactional WHERE 1";
        if (!empty($start_date)) {
            $query .= " AND transaction_date >= '" . mysqli_real_escape_string($conn, $start_date) . "'";
        }
        if (!empty($end_date)) {
            $query .= " AND transaction_date <= '" . mysqli_real_escape_string($conn, $end_date) . "'";
        }
        if (!empty($corporate)) {
            $query .= " AND corporate_name = '" . mysqli_real_escape_string($conn, $corporate) . "'";
        }
        if (!empty($status)) {
            $query .= " AND status = '" . mysqli_real_escape_string($conn, $status) . "'";
        }

        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $totalAmount = $totalVat = $totalNetOfVat = $totalWtax = $totalEditAmountLessor = 0;

            echo "<div class='table-responsive shadow-sm'>
                    <table class='table table-sm table-bordered table-hover align-middle text-center'>
                        <thead class='table-danger text-white'>
                            <tr>
                                <th><i class='bi bi-calendar-day me-1'></i> Payment Due</th>
                                <th><i class='bi bi-hash me-1'></i> Branch ID</th>
                                <th><i class='bi bi-shop me-1'></i> Branch</th>
                                <th><i class='bi bi-geo-alt-fill me-1'></i> Zone</th>
                                <th><i class='bi bi-globe me-1'></i> Region</th>
                                <th><i class='bi bi-cash-coin me-1'></i> Amount</th>
                                <th><i class='bi bi-percent me-1'></i> VAT Type</th>
                                <th><i class='bi bi-receipt me-1'></i> VAT</th>
                                <th><i class='bi bi-cash me-1'></i> Net of VAT</th>
                                <th><i class='bi bi-cash-stack me-1'></i> W-Tax</th>
                                <th><i class='bi bi-wallet2 me-1'></i> Amount to Lessor</th>
                                <th><i class='bi bi-flag me-1'></i> Status</th>
                            </tr>
                        </thead>
                        <tbody>";
            while ($row = mysqli_fetch_assoc($result)) {
                $transactionDate = (!empty($row['new_due_date']) && $row['dueDate_request_status'] === 'Approved')
                    ? $row['new_due_date']
                    : $row['transaction_date'];

                echo "<tr>
                        <td>" . htmlspecialchars(date("M d, Y", strtotime($transactionDate))) . "</td>
                        <td>" . htmlspecialchars($row['branch_id']) . "</td>
                        <td>" . htmlspecialchars($row['branch']) . "</td>
                        <td>" . htmlspecialchars($row['mainzone']) . "</td>
                        <td>" . htmlspecialchars($row['region']) . "</td>
                        <td class='fw-bold text-success'>₱ " . number_format($row['amount'], 2) . "</td>
                        <td>" . htmlspecialchars($row['vat_type']) . "</td>
                        <td>₱ " . number_format($row['vat_amount'], 2) . "</td>
                        <td>₱ " . number_format($row['net_of_vat'], 2) . "</td>
                        <td>₱ " . number_format($row['wtax'], 2) . "</td>
                        <td class='fw-bold text-danger'>₱ " . number_format($row['edit_amount_lessor'], 2) . "</td>";

                // Modern Status Badge
                $statusText = htmlspecialchars($row['status']);
                switch ($statusText) {
                    case 'Paid':
                        $statusBadge = "<span class='badge bg-success'><i class='bi bi-check-circle me-1'></i> Paid</span>";
                        break;
                    case 'Unpaid':
                        $statusBadge = "<span class='badge bg-warning text-dark'><i class='bi bi-exclamation-circle me-1'></i> Unpaid</span>";
                        break;
                    case 'PBB':
                        $statusBadge = "<span class='badge bg-primary'><i class='bi bi-building me-1'></i> Paid by Branch</span>";
                        break;
                    case 'Cancelled':
                        $statusBadge = "<span class='badge bg-danger'><i class='bi bi-x-circle me-1'></i> Cancelled</span>";
                        break;
                    default:
                        $statusBadge = "<span class='badge bg-secondary'><i class='bi bi-question-circle me-1'></i> $statusText</span>";
                        break;
                }
                echo "<td>$statusBadge</td></tr>";

                $totalAmount += $row['amount'];
                $totalVat += $row['vat_amount'];
                $totalNetOfVat += $row['net_of_vat'];
                $totalWtax += $row['wtax'];
                $totalEditAmountLessor += $row['edit_amount_lessor'];
            }

            echo "<tr class='fw-bold bg-light'>
                    <td colspan='5' class='text-end'>Totals:</td>
                    <td>₱ " . number_format($totalAmount, 2) . "</td>
                    <td></td>
                    <td>₱ " . number_format($totalVat, 2) . "</td>
                    <td>₱ " . number_format($totalNetOfVat, 2) . "</td>
                    <td>₱ " . number_format($totalWtax, 2) . "</td>
                    <td>₱ " . number_format($totalEditAmountLessor, 2) . "</td>
                    <td></td>
                </tr>";

            echo "</tbody></table></div>";
        } else {
            echo "<div class='alert alert-warning d-flex align-items-center'>
                    <i class='bi bi-info-circle me-2'></i> No records found.
                  </div>";
        }
    }
    ?>
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
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/sweetalert2/dist/sweetalert2.all.min.js"></script>
<script>
const toggleBtn = document.getElementById('toggleSidebar');
  const sidebar = document.getElementById('sidebarMenu');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
  });
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
