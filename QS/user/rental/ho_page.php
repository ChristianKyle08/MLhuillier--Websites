<?php
session_start();
include '../../config/config.php';

if (!isset($_SESSION['user_name'])) {
   header('location:login_form.php');
}
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require '../../vendor/autoload.php'; // Include PhpSpreadsheet library

if (isset($_POST['export'])) {
    // Get the filter values from the form
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '';
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '';
    $mainzone = isset($_POST['mainzone']) ? $_POST['mainzone'] : '';
    $region = isset($_POST['region']) ? $_POST['region'] : '';
    $area = isset($_POST['area']) ? $_POST['area'] : '';
    $branch = isset($_POST['branch']) ? $_POST['branch'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : '';

    // Initialize the base query
    $query = "SELECT * FROM transactional 
              WHERE status != 'Terminated'";

    // Initialize an array to hold the parameters
    $params = [];
    $param_types = '';

    // Add filters to the query
    if (!empty($start_date) && !empty($end_date)) {
        $query .= " AND DATE_FORMAT(transaction_date, '%Y-%m') BETWEEN ? AND ?";
        $params[] = $start_date; 
        $params[] = $end_date;
        $param_types .= 'ss';
    }
    if (!empty($mainzone)) {
        $query .= " AND mainzone = ?";
        $params[] = $mainzone;
        $param_types .= 's';
    }
    if (!empty($region)) {
        $query .= " AND region = ?";
        $params[] = $region;
        $param_types .= 's';
    }
    if (!empty($area)) {
        $query .= " AND area = ?";
        $params[] = $area;
        $param_types .= 's';
    }
    if (!empty($branch)) {
        $query .= " AND branch = ?";
        $params[] = $branch;
        $param_types .= 's';
    }
    if (!empty($status)) {
        $query .= " AND status = ?";
        $params[] = $status;
        $param_types .= 's';
    }

    // Prepare the statement
    $stmt = mysqli_prepare($conn, $query);

    // Bind parameters
    if (!empty($param_types)) {
        mysqli_stmt_bind_param($stmt, $param_types, ...$params);
    }

    // Execute the statement
    mysqli_stmt_execute($stmt);

    // Get the result
    $result = mysqli_stmt_get_result($stmt);

    // Create a new Spreadsheet object
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Set header names
    $sheet->setCellValue('A1', 'REGION');
    $sheet->setCellValue('B1', 'AREA');
    $sheet->setCellValue('C1', 'BRANCH');
    $sheet->setCellValue('D1', 'LESSOR NAME');
    
    // Check if 2nd LESSOR NAME column is needed
    if (isset($_POST['branchId']) && isset($_POST['contractNumber'])) {
        $branchId = $_POST['branchId'];
        $contract_number = $_POST['contractNumber'];

        // Query to check if l2_firstname, l2_middlename, or l2_lastname are not empty
        $checkQuery = "SELECT l2_firstname, l2_middlename, l2_lastname 
                       FROM transactional 
                       WHERE (l2_firstname != '' OR l2_middlename != '' OR l2_lastname != '') 
                       AND branch_id = ? 
                       AND contract_number = ? 
                       LIMIT 1";

        $checkStmt = mysqli_prepare($conn, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "ss", $branchId, $contract_number);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        // If a second lessor is present, add the column
        if (mysqli_num_rows($checkResult) > 0) {
            $sheet->setCellValue('E1', '2nd LESSOR NAME');
        }
    }
    $sheet->setCellValue('F1', 'START DATE');
    $sheet->setCellValue('G1', 'END DATE');
    
    $sheet->setCellValue('H1', 'DUE DATE');
    $sheet->setCellValue('I1', 'GROSS RENTAL');
    $sheet->setCellValue('J1', 'VAT TYPE');
    $sheet->setCellValue('K1', 'NET OF VAT');
    $sheet->setCellValue('L1', 'VAT AMOUNT');
    $sheet->setCellValue('M1', 'W-TAX');
    $sheet->setCellValue('N1', 'AMOUNT TO LESSOR');
    $sheet->setCellValue('O1', 'TOTAL MONTHLY RENTAL');
    $sheet->setCellValue('P1', 'MODE OF PAYMENT');
    $sheet->setCellValue('Q1', 'STATUS');

    // Initialize totals
    $total_amount = 0;
    $total_vat_amount = 0;
    $total_wtax = 0;
    $total_amount_lessor = 0;
    $total_total_month_rental = 0;

    // Start outputting data from row 2
    $rowIndex = 2;
    while ($row = mysqli_fetch_assoc($result)) {
        $sheet->setCellValue('A' . $rowIndex, $row['region']);
        $sheet->setCellValue('B' . $rowIndex, $row['area']);
        $sheet->setCellValue('C' . $rowIndex, $row['branch']);
        $sheet->setCellValue('D' . $rowIndex, $row['l1_firstname'] . ' ' . $row['l1_middlename'] . ' ' . $row['l1_lastname']);
        
        // If second lessor name exists, add it
        if (!empty($row['l2_firstname']) || !empty($row['l2_middlename']) || !empty($row['l2_lastname'])) {
            $sheet->setCellValue('E' . $rowIndex, $row['l2_firstname'] . ' ' . $row['l2_middlename'] . ' ' . $row['l2_lastname']);
        }
        $sheet->setCellValue('F' . $rowIndex, date('F d, Y', strtotime($row['start_date'])));
        $sheet->setCellValue('G' . $rowIndex, date('F d, Y', strtotime($row['end_date'])));

        // Conditional logic for due date
        if ($row['dueDate_request_status'] === 'Approved' && !empty($row['new_due_date'])) {
            // If modify_request_status is "Approved" and new_due_date is not empty
            $sheet->setCellValue('H' . $rowIndex, date('F d, Y', strtotime($row['new_due_date'])));
        } else {
            // Else, display transaction_date if new_due_date is empty or status is not "Approved"
            $sheet->setCellValue('H' . $rowIndex, date('F d, Y', strtotime($row['transaction_date'])));
        }
    
        // Other columns
       

        $sheet->setCellValue('I' . $rowIndex, $row['amount']);
        $sheet->setCellValue('J' . $rowIndex, $row['vat_type']);
        $sheet->setCellValue('K' . $rowIndex, $row['net_of_vat']);
        $sheet->setCellValue('L' . $rowIndex, $row['vat_amount']);
        $sheet->setCellValue('M' . $rowIndex, $row['wtax']);
        $sheet->setCellValue('N' . $rowIndex, $row['edit_amount_lessor']);
        $sheet->setCellValue('O' . $rowIndex, $row['total_month_rental']);
        $sheet->setCellValue('P' . $rowIndex, $row['mode_of_payment']);
        $sheet->setCellValue('Q' . $rowIndex, $row['status']);
    
        // Sum totals
        $total_amount += $row['amount'];
        $total_vat_amount += $row['vat_amount'];
        $total_wtax += $row['wtax'];
        $total_amount_lessor += $row['edit_amount_lessor'];
        $total_total_month_rental += $row['total_month_rental'];
        
        $rowIndex++;
    }    

    // Output totals in the next row
    $sheet->setCellValue('H' . $rowIndex, 'TOTALS');
    $sheet->setCellValue('I' . $rowIndex, $total_amount);
    $sheet->setCellValue('L' . $rowIndex, $total_vat_amount);
    $sheet->setCellValue('M' . $rowIndex, $total_wtax);
    $sheet->setCellValue('N' . $rowIndex, $total_amount_lessor);
    $sheet->setCellValue('O' . $rowIndex, $total_total_month_rental);

    // Create the writer and save the file
    $writer = new Xlsx($spreadsheet);
    $filename = 'exported_data_' . date('Y-m-d_H-i-s') . '.xlsx';

    // Send headers and output the file to the browser
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $writer->save('php://output');

    exit(); // Exit to ensure no further output is sent
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
            <title>ML Rental - Contract Ledger</title>
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
        <style>
            .nowrap-table {
            border-collapse: collapse !important;
            white-space: nowrap;
            }
            .custom-table {
            border-collapse: collapse !important;
            white-space: nowrap;
            border-radius: 12px;
            overflow: hidden;
            }

            /* Light Red Gradient Header */
            .custom-table thead {
            background: linear-gradient(135deg, #ff6b6b, #ff9e9e);
            color: #fff;
            }

            /* Zebra striping */
            .custom-table tbody tr:nth-child(odd) {
            background-color: #fff5f5;
            }
            .custom-table tbody tr:nth-child(even) {
            background-color: #ffeaea;
            }

            /* Hover effect */
            .custom-table tbody tr:hover {
            background-color: #ffd6d6 !important;
            transition: background-color 0.2s ease-in-out;
            }
        </style>
    <body>
    <?php include ('navbar.php'); ?>
    <div id="mainContent">
        <button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
            <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
            <span class="fw-normal">Menu</span>
        </button>
        <form action="" method="POST" id="transaction_form">
  <div class="container py-1">
    <div class="card shadow-sm border-0 rounded-4">
      <div class="card-header bg-danger text-white rounded-top-4">
        <h5 class="mb-0 text-white">
          <i class="bi bi-funnel-fill me-2 "></i>Transaction Filters
        </h5>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <!-- Start Date -->
          <div class="col-md-3">
            <label for="start_date" class="form-label fw-semibold">
              <i class="bi bi-calendar-date me-1 text-danger"></i> Start Date
            </label>
            <input type="month" 
                   name="start_date" 
                   id="start_date" 
                   class="form-control"
                   value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>" 
                   required>
          </div>

          <!-- End Date -->
          <div class="col-md-3">
            <label for="end_date" class="form-label fw-semibold">
              <i class="bi bi-calendar-x me-1 text-danger"></i> End Date
            </label>
            <input type="month" 
                   name="end_date" 
                   id="end_date" 
                   class="form-control"
                   value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>" 
                   required>
          </div>

          <!-- Zone -->
          <div class="col-md-3">
            <label for="mainzone" class="form-label fw-semibold">
              <i class="bi bi-globe2 me-1 text-danger"></i> Zone
            </label>
            <select name="mainzone" id="mainzone" class="form-select">
              <option value="">-- Select Zone --</option>
              <?php
              $mainzoneSql = "SELECT DISTINCT mainzone FROM branch_insurance WHERE mainzone != '' ORDER BY mainzone ASC";
              $resultMainzone = mysqli_query($conn, $mainzoneSql);
              if ($resultMainzone) {
                  while ($rowMainzone = mysqli_fetch_assoc($resultMainzone)) {
                      $selected = (isset($_POST['mainzone']) && $_POST['mainzone'] == $rowMainzone['mainzone']) ? 'selected' : '';
                      echo "<option value='" . htmlspecialchars($rowMainzone['mainzone']) . "' $selected>" . htmlspecialchars($rowMainzone['mainzone']) . "</option>";
                  }
              }
              ?>
            </select>
          </div>

          <!-- Region -->
          <div class="col-md-3">
            <label for="region" class="form-label fw-semibold">
              <i class="bi bi-map me-1 text-danger"></i> Region
            </label>
            <select name="region" id="region" class="form-select">
              <option value="">-- Select Region --</option>
              <?php
              $regionSql = "SELECT DISTINCT region FROM branch_insurance WHERE region != '' ORDER BY region ASC";
              $resultRegion = mysqli_query($conn, $regionSql);
              if ($resultRegion) {
                  while ($rowRegion = mysqli_fetch_assoc($resultRegion)) {
                      $selected = (isset($_POST['region']) && $_POST['region'] == $rowRegion['region']) ? 'selected' : '';
                      echo "<option value='" . htmlspecialchars($rowRegion['region']) . "' $selected>" . htmlspecialchars($rowRegion['region']) . "</option>";
                  }
              }
              ?>
            </select>
          </div>

          <!-- Area -->
          <div class="col-md-3">
            <label for="area" class="form-label fw-semibold">
              <i class="bi bi-diagram-3 me-1 text-danger"></i> Area
            </label>
            <select name="area" id="area" class="form-select">
              <option value="">-- Select Area --</option>
              <?php
              $areaSql = "SELECT DISTINCT area FROM branch_insurance WHERE area != '' ORDER BY area ASC";
              $resultArea = mysqli_query($conn, $areaSql);
              if ($resultArea) {
                  while ($rowArea = mysqli_fetch_assoc($resultArea)) {
                      $selected = (isset($_POST['area']) && $_POST['area'] == $rowArea['area']) ? 'selected' : '';
                      echo "<option value='" . htmlspecialchars($rowArea['area']) . "' $selected>" . htmlspecialchars($rowArea['area']) . "</option>";
                  }
              }
              ?>
            </select>
          </div>

          <!-- Branch -->
          <div class="col-md-3">
            <label for="branch" class="form-label fw-semibold">
              <i class="bi bi-building me-1 text-danger"></i> Branch
            </label>
            <select name="branch" id="branch" class="form-select">
              <option value="">-- Select Branch --</option>
              <?php
              $branchSql = "SELECT DISTINCT branch_name FROM branch_insurance WHERE branch_name != '' ORDER BY branch_name ASC";
              $resultBranch = mysqli_query($conn, $branchSql);
              if ($resultBranch) {
                  while ($rowBranch = mysqli_fetch_assoc($resultBranch)) {
                      $selected = (isset($_POST['branch']) && $_POST['branch'] == $rowBranch['branch_name']) ? 'selected' : '';
                      echo "<option value='" . htmlspecialchars($rowBranch['branch_name']) . "' $selected>" . htmlspecialchars($rowBranch['branch_name']) . "</option>";
                  }
              }
              ?>
            </select>
          </div>

          <!-- Status -->
          <div class="col-md-3">
            <label for="status" class="form-label fw-semibold">
              <i class="bi bi-info-circle me-1 text-danger"></i> Status
            </label>
            <select name="status" id="status" class="form-select">
              <option value="">-- Select Status --</option>
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
        </div>

        <!-- Action Buttons -->
        <div class="d-flex justify-content-end gap-2 mt-4">
          <button type="submit" name="filter" id="filter" class="btn btn-danger rounded-pill px-4">
            <i class="bi bi-search me-1"></i> Display
          </button>
          <button type="submit" name="export" id="export" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-file-earmark-excel me-1"></i> Export
          </button>
        </div>
      </div>
    </div>

    <div class="display_res mt-4">
    <?php 
    if (isset($_POST['filter'])) {
        // Get the filter values from the form
        $start_date = $_POST['start_date'] ?? '';
        $end_date   = $_POST['end_date'] ?? '';
        $mainzone   = $_POST['mainzone'] ?? '';
        $region     = $_POST['region'] ?? '';
        $area       = $_POST['area'] ?? '';
        $branch     = $_POST['branch'] ?? '';
        $status     = $_POST['status'] ?? '';

        // Base query
        $query = "SELECT * FROM transactional WHERE status != 'Terminated'";

        $params = [];
        $param_types = '';

        if (!empty($start_date) && !empty($end_date)) {
            $query .= " AND DATE_FORMAT(transaction_date, '%Y-%m') BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
            $param_types .= 'ss';
        }
        if (!empty($mainzone)) { $query .= " AND mainzone = ?"; $params[] = $mainzone; $param_types .= 's'; }
        if (!empty($region))   { $query .= " AND region = ?";   $params[] = $region;   $param_types .= 's'; }
        if (!empty($area))     { $query .= " AND area = ?";     $params[] = $area;     $param_types .= 's'; }
        if (!empty($branch))   { $query .= " AND branch = ?";   $params[] = $branch;   $param_types .= 's'; }
        if (!empty($status))   { $query .= " AND status = ?";   $params[] = $status;   $param_types .= 's'; }

        $query .= " ORDER BY branch";
        $stmt = mysqli_prepare($conn, $query);

        if (!empty($param_types)) {
            mysqli_stmt_bind_param($stmt, $param_types, ...$params);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Totals
        $total_amount = $total_net_of_vat = $total_vat_amount = $total_wtax = $total_amount_lessor = 0;

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $total_amount        += $row['amount'];
                $total_net_of_vat    += $row['net_of_vat'];
                $total_vat_amount    += $row['vat_amount'];
                $total_wtax          += $row['wtax'];
                $total_amount_lessor += $row['edit_amount_lessor'];
            }

            // Totals Card
            echo "
            <div class='row text-center mb-4'>
                <div class='col-md-3'>
                    <div class='card shadow-sm border-0 bg-light'>
                        <div class='card-body'>
                            <i class='bi bi-cash-stack fs-3 text-success'></i>
                            <h6 class='mt-2'>Total Gross Rental</h6>
                            <p class='fw-bold text-dark'>₱ " . number_format($total_amount, 2) . "</p>
                        </div>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='card shadow-sm border-0 bg-light'>
                        <div class='card-body'>
                            <i class='bi bi-receipt fs-3 text-primary'></i>
                            <h6 class='mt-2'>Total VAT Amount</h6>
                            <p class='fw-bold text-dark'>₱ " . number_format($total_vat_amount, 2) . "</p>
                        </div>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='card shadow-sm border-0 bg-light'>
                        <div class='card-body'>
                            <i class='bi bi-percent fs-3 text-warning'></i>
                            <h6 class='mt-2'>Total W-Tax</h6>
                            <p class='fw-bold text-dark'>₱ " . number_format($total_wtax, 2) . "</p>
                        </div>
                    </div>
                </div>
                <div class='col-md-3'>
                    <div class='card shadow-sm border-0 bg-light'>
                        <div class='card-body'>
                            <i class='bi bi-wallet2 fs-3 text-danger'></i>
                            <h6 class='mt-2'>Total Amount to Lessor</h6>
                            <p class='fw-bold text-dark'>₱ " . number_format($total_amount_lessor, 2) . "</p>
                        </div>
                    </div>
                </div>
            </div>";

            mysqli_data_seek($result, 0);

            // Transactions Table
            echo "<div class='table-responsive'>
            <table class='table table-sm table-bordered table-striped table-hover align-middle shadow-sm nowrap-table custom-table'>
                <thead class='text-white text-center'>
                    <tr>
                        <th>Region</th>
                        <th>Area</th>
                        <th>Branch</th>
                        <th>Lessor Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Due Date</th>
                        <th>Gross Rental</th>
                        <th>VAT Type</th>
                        <th style='display:none;'>Net of VAT</th>
                        <th>VAT Amount</th>
                        <th>W-Tax</th>
                        <th>Amount to Lessor</th>
                        <th>Mode of Payment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>";

            while ($row = mysqli_fetch_assoc($result)) {
            $dueDate = (!empty($row['new_due_date']) && $row['dueDate_request_status'] === 'Approved') 
                    ? $row['new_due_date'] 
                    : $row['transaction_date'];

            $statusText = htmlspecialchars($row['status'] ?? '', ENT_QUOTES, 'UTF-8');

            // Status badge with icons & colors
            switch ($statusText) {
            case 'Paid':
                $statusBadge = '<span class="badge" style="background:#28a745;">
                                    <i class="bi bi-check-circle me-1"></i> Paid
                                </span>';
                break;
            case 'Unpaid':
                $statusBadge = '<span class="badge" style="background:#ffc107; color:#000;">
                                    <i class="bi bi-exclamation-circle me-1"></i> Unpaid
                                </span>';
                break;
            case 'PBB':
                $statusBadge = '<span class="badge" style="background:#007bff;">
                                    <i class="bi bi-building me-1"></i> Paid by Branch
                                </span>';
                break;
            case 'Cancelled':
                $statusBadge = '<span class="badge" style="background:#dc3545;">
                                    <i class="bi bi-x-circle me-1"></i> Cancelled
                                </span>';
                break;
            default:
                $statusBadge = '<span class="badge" style="background:#6c757d;">
                                    <i class="bi bi-question-circle me-1"></i> ' . $statusText . '
                                </span>';
                break;
            }

            echo "<tr>
                <td>" . htmlspecialchars($row['region']) . "</td>
                <td>" . htmlspecialchars($row['area']) . "</td>
                <td>" . htmlspecialchars($row['branch']) . "</td>
                <td>" . htmlspecialchars(trim($row['l1_firstname'] . ' ' . $row['l1_middlename'] . ' ' . $row['l1_lastname'])) . "</td>
                <td>" . date('M d, Y', strtotime($row['start_date'])) . "</td>
                <td>" . date('M d, Y', strtotime($row['end_date'])) . "</td>
                <td>" . date('M d, Y', strtotime($dueDate)) . "</td>
                <td class='fw-bold text-success'>₱ " . number_format($row['amount'], 2) . "</td>
                <td>" . htmlspecialchars($row['vat_type']) . "</td>
                <td style='display:none;'>₱ " . number_format($row['net_of_vat'], 2) . "</td>
                <td>₱ " . number_format($row['vat_amount'], 2) . "</td>
                <td>₱ " . number_format($row['wtax'], 2) . "</td>
                <td class='fw-bold text-danger'>₱ " . number_format($row['edit_amount_lessor'], 2) . "</td>
                <td>" . htmlspecialchars($row['mode_of_payment']) . "</td>
                <td class='fw-bold text-center'>" . $statusBadge . "</td>
            </tr>";
            }

            echo "</tbody></table></div>";

        } else {
            echo "<div class='alert alert-warning text-center shadow-sm'>
                    <i class='bi bi-exclamation-triangle-fill me-2'></i>No transactions found for the selected filters.
                  </div>";
        }

        mysqli_stmt_close($stmt);
    }
    ?>
</div>
        </form>
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
// Get the modal
var modal = document.getElementById("myModal");

// Get the button that opens the modal
var btn = document.getElementById("export_ledger");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks the button, open the modal 
btn.onclick = function() {
    modal.style.display = "block";
}

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
    modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

function updateKpxCode(branchSelect) {
        const selectedBranch = branchSelect.selectedOptions[0];
        const kpxCode = selectedBranch.dataset.kpxCode;
        const branchId = selectedBranch.dataset.branchId;
        const lessor_name = selectedBranch.dataset.lessorName; // Corrected the property name here
        const kpxCodeInput = document.getElementById('kpxCode');
        const branchIdInput = document.getElementById('branchId');
        const lessorInput = document.getElementById('lessor_name');
        kpxCodeInput.value = kpxCode;
        branchIdInput.value = branchId;
        lessorInput.value = lessor_name;

        // Assuming your form has an ID 'yourFormId', adjust it accordingly
        const form = document.getElementById('ledger_form');

        // Submit the form
        form.submit();
}

function highlightRow(row) {
  // Remove any existing highlights
  var table = row.closest('table');
  var rows = table.querySelectorAll('tr');
  for (var i = 0; i < rows.length; i++) {
    rows[i].style.backgroundColor = '';
  }

  // Highlight the clicked row
  row.style.backgroundColor = 'transparent';

  // Get and display the ID
  var selectedId = row.querySelector('td:first-child').innerText;
  document.getElementById('selected_id_display').value = selectedId; // Use 'value' instead of 'textContent'
}


    </script>
    </body>
</html>
