<?php
session_start();
   include '../../config/config.php';
   if (!isset($_SESSION['user_name'])) {
        header('location:login_form.php');
    }
    require '../../vendor/autoload.php'; // Include PhpSpreadsheet

    use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Handle Export to Excel
if (isset($_POST['export_payout'])) {
    // --- Filter Values ---
    $start_date   = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date     = mysqli_real_escape_string($conn, $_POST['end_date']);
    $region_desc  = trim($_POST['region_description'] ?? '');
    $region_code  = trim($_POST['region_code'] ?? '');
    $gl_region    = trim($_POST['gl_region'] ?? '');
    $kp7_region   = trim($_POST['region_desc_kp7'] ?? '');
    $kpx_region   = trim($_POST['region_desc_kpx'] ?? '');
    $branch       = mysqli_real_escape_string($conn, $_POST['branch'] ?? '');
    $status       = mysqli_real_escape_string($conn, $_POST['status'] ?? '');

    // --- Format Dates ---
    $formatted_start_date = date('m/d/Y', strtotime($start_date));
    $formatted_end_date   = date('m/d/Y', strtotime($end_date));

    // --- Base SQL Query ---
    $sql = "SELECT 
                receiver_kyc, receiver_name, sender_customerID, sender_name, charge_to, 
                kptn, sendout_datetime, payout_datetime, principal, payout_amount, 
                service_charge, commission, so_operator, sendout_branch, payout_branch, 
                payout_branch_id, payout_branch_id, region, status, posted_by, posted_datetime
            FROM payout
            WHERE STR_TO_DATE(sendout_datetime, '%c/%e/%Y, %l:%i %p') 
                BETWEEN STR_TO_DATE('$formatted_start_date', '%m/%d/%Y') 
                AND STR_TO_DATE('$formatted_end_date', '%m/%d/%Y')";

    // --- Region Filtering (spaces ignored, case-insensitive) ---
    $regionMatches = [];
    foreach ([$region_desc, $region_code, $gl_region, $kp7_region, $kpx_region] as $r) {
        if ($r !== '') {
            $regionMatches[] = "'" . mysqli_real_escape_string($conn, strtolower(str_replace(' ', '', $r))) . "'";
        }
    }
    if (!empty($regionMatches)) {
        $sql .= " AND REPLACE(LOWER(region), ' ', '') IN (" . implode(',', $regionMatches) . ")";
    }

    // --- Branch Filter ---
    if (!empty($branch)) {
        $sql .= " AND sendout_branch = '$branch'";
    }

    // --- Status Filter ---
    if (!empty($status)) {
        $sql .= " AND status = '$status'";
    }

    // --- Order By ---
    $sql .= " ORDER BY STR_TO_DATE(sendout_datetime, '%c/%e/%Y, %l:%i %p') DESC";

    $result = mysqli_query($conn, $sql);

    // --- Create Spreadsheet ---
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // --- Column Headers ---
    $headers = [
        'A' => 'Receiver KYC', 'B' => 'Receiver Name', 'C' => 'Sender Customer ID',
        'D' => 'Sender Name', 'E' => 'Charge To', 'F' => 'KPTN', 'G' => 'Sendout Date/Time',
        'H' => 'Payout Date/Time', 'I' => 'Principal', 'J' => 'Payout Amount',
        'K' => 'Service Charge', 'L' => 'Commission', 'M' => 'SO Operator',
        'N' => 'Sendout Branch', 'O' => 'Payout Branch', 'P' => 'Region',
        'Q' => 'Branch ID', 'R' => 'Status', 'S' => 'Posted By', 'T' => 'Posted Date/Time'
    ];

    foreach ($headers as $col => $text) {
        $sheet->setCellValue($col . '1', $text);
    }

    // --- Totals ---
    $row = 2;
    $totalPrincipal = $totalPayoutAmount = $totalServiceCharge = $totalCommission = 0;

    if (mysqli_num_rows($result) > 0) {
        while ($data = mysqli_fetch_assoc($result)) {
            $sheet->setCellValue('A' . $row, $data['receiver_kyc']);
            $sheet->setCellValue('B' . $row, $data['receiver_name']);
            $sheet->setCellValue('C' . $row, $data['sender_customerID']);
            $sheet->setCellValue('D' . $row, $data['sender_name']);
            $sheet->setCellValue('E' . $row, $data['charge_to']);
            $sheet->setCellValue('F' . $row, $data['kptn']);
            $sheet->setCellValue('G' . $row, $data['sendout_datetime']);
            $sheet->setCellValue('H' . $row, $data['payout_datetime']);
            $sheet->setCellValue('I' . $row, $data['principal']);
            $sheet->setCellValue('J' . $row, $data['payout_amount']);
            $sheet->setCellValue('K' . $row, $data['service_charge']);
            $sheet->setCellValue('L' . $row, $data['commission']);
            $sheet->setCellValue('M' . $row, $data['so_operator']);
            $sheet->setCellValue('N' . $row, $data['sendout_branch']);
            $sheet->setCellValue('O' . $row, $data['payout_branch']);
            $sheet->setCellValue('P' . $row, $data['region']);
            $sheet->setCellValue('Q' . $row, $data['payout_branch_id']);
            $sheet->setCellValue('R' . $row, $data['status']);
            $sheet->setCellValue('S' . $row, $data['posted_by']);
            $sheet->setCellValue('T' . $row, $data['posted_datetime']);

            // --- Add to totals ---
            $totalPrincipal += $data['principal'];
            $totalPayoutAmount += $data['payout_amount'];
            $totalServiceCharge += $data['service_charge'];
            $totalCommission += $data['commission'];

            $row++;
        }

        // --- Totals Row ---
        $sheet->setCellValue('H' . $row, 'Totals:');
        $sheet->setCellValue('I' . $row, number_format($totalPrincipal, 2));
        $sheet->setCellValue('J' . $row, number_format($totalPayoutAmount, 2));
        $sheet->setCellValue('K' . $row, number_format($totalServiceCharge, 2));
        $sheet->setCellValue('L' . $row, number_format($totalCommission, 2));
    }

    // --- Download Excel File ---
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="payout_export.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit();
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
  
  <title>ML Rental - Payout Report</title>
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

    <div class="container py-3">
        <!-- Filter Form -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-danger text-white d-flex align-items-center">
                <i class="bi bi-funnel-fill me-2"></i>
                <span class="fw-semibold">Filter Transactions</span>
            </div>
            <div class="card-body">
                <form action="" method="POST" id="transaction_form" class="row g-3">
                    
                    <!-- Start Date -->
                    <div class="col-md-3">
                        <label for="start_date" class="form-label fw-semibold">
                            <i class="bi bi-calendar-event me-1 text-danger"></i> Start Date
                        </label>
                        <input type="date" name="start_date" id="start_date" 
                               value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>" 
                               class="form-control" required>
                    </div>

                    <!-- End Date -->
                    <div class="col-md-3">
                        <label for="end_date" class="form-label fw-semibold">
                            <i class="bi bi-calendar-check me-1 text-danger"></i> End Date
                        </label>
                        <input type="date" name="end_date" id="end_date" 
                               value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>" 
                               class="form-control" required>
                    </div>

                    <!-- Region -->
            <div class="col-md-2">
                <label for="region" class="form-label fw-semibold">
                    <i class="bi bi-geo-alt me-1 text-danger"></i> Region
                </label>
                <select name="region_description"
                        id="region"
                        class="form-select"
                        onchange="loadRegionDetails(this.value)">
                    <option value="">-- Select --</option>
                    <?php
                    $regionSql = "
                        SELECT DISTINCT region_description
                        FROM region_masterfile
                        WHERE region_description IS NOT NULL
                        AND region_description <> ''
                        ORDER BY region_description ASC
                    ";

                    $resultRegion = mysqli_query($conn, $regionSql);
                    while ($row = mysqli_fetch_assoc($resultRegion)) {
                        $selected = (isset($_POST['region_description']) && $_POST['region_description'] == $row['region_description']) ? 'selected' : '';
                        echo '<option value="' . htmlspecialchars($row['region_description']) . '" ' . $selected . '>'
                             . htmlspecialchars($row['region_description']) .
                             '</option>';
                    }
                    ?>
                </select>
            </div>

            <!-- Hidden Region Fields -->
            <input type="hidden" name="region_code" id="region_code" 
                   value="<?php echo isset($_POST['region_code']) ? htmlspecialchars($_POST['region_code']) : ''; ?>">
            <input type="hidden" name="gl_region" id="gl_region" 
                   value="<?php echo isset($_POST['gl_region']) ? htmlspecialchars($_POST['gl_region']) : ''; ?>">
            <input type="hidden" name="region_desc_kp7" id="region_desc_kp7" 
                   value="<?php echo isset($_POST['region_desc_kp7']) ? htmlspecialchars($_POST['region_desc_kp7']) : ''; ?>">
            <input type="hidden" name="region_desc_kpx" id="region_desc_kpx" 
                   value="<?php echo isset($_POST['region_desc_kpx']) ? htmlspecialchars($_POST['region_desc_kpx']) : ''; ?>">

                    <!-- Branch -->
                    <div class="col-md-3">
                        <label for="branch" class="form-label fw-semibold">
                            <i class="bi bi-building me-1 text-danger"></i> Branch
                        </label>
                        <select name="branch" id="branch" class="form-select">
                            <option value="">-- Select Branch --</option>
                            <!-- Branch options will be populated via JavaScript -->
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="col-12 d-flex justify-content-end mt-3">
                        <button type="submit" name="export_payout" id="export_payout" class="btn btn-outline-success me-2">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export
                        </button>
                        <button type="submit" name="filter_payout" id="filter_payout" class="btn btn-danger">
                            <i class="bi bi-search me-1"></i> Display
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transactions List -->
        <div class="transaction_list">
            <?php
            if (isset($_POST['filter_payout'])) {
                $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
                $end_date   = mysqli_real_escape_string($conn, $_POST['end_date']);
                $region_desc = trim($_POST['region_description'] ?? '');
                $region_code = trim($_POST['region_code'] ?? '');
                $gl_region   = trim($_POST['gl_region'] ?? '');
                $kp7_region  = trim($_POST['region_desc_kp7'] ?? '');
                $kpx_region  = trim($_POST['region_desc_kpx'] ?? '');
                $branch     = mysqli_real_escape_string($conn, $_POST['branch']);

                $formatted_start_date = date('m/d/Y', strtotime($start_date));
                $formatted_end_date   = date('m/d/Y', strtotime($end_date));

                $sql = "SELECT 
                            receiver_kyc, receiver_name, sender_customerID, sender_name, charge_to, 
                            kptn, sendout_datetime, payout_datetime, principal, payout_amount, 
                            service_charge, commission, so_operator, sendout_branch, sendout_branch_id, 
                            payout_branch, payout_branch_id, region, status, posted_by, posted_datetime 
                        FROM payout 
                        WHERE STR_TO_DATE(sendout_datetime, '%c/%e/%Y, %l:%i %p') 
                        BETWEEN STR_TO_DATE('$formatted_start_date', '%m/%d/%Y') 
                        AND STR_TO_DATE('$formatted_end_date', '%m/%d/%Y')";

                $regionMatches = [];

                foreach ([$region_desc, $region_code, $gl_region, $kp7_region, $kpx_region] as $r) {
                    if ($r !== '') {
                        // remove spaces and lowercase
                        $regionMatches[] = "'" . mysqli_real_escape_string($conn, strtolower(str_replace(' ', '', $r))) . "'";
                    }
                }

                if (!empty($regionMatches)) {
                    $sql .= "
                        AND REPLACE(LOWER(region), ' ', '') IN (" . implode(',', $regionMatches) . ")
                    ";
                }
                if (!empty($branch)) $sql .= " AND sendout_branch = '$branch'";
                $sql .= " ORDER BY STR_TO_DATE(sendout_datetime, '%c/%e/%Y, %l:%i %p') DESC";

                $result = mysqli_query($conn, $sql);

                if (mysqli_num_rows($result) > 0) {
                    echo '<div class="table-responsive shadow-sm">';
                    echo '<div class="table-responsive">';
                    echo '<table class="table table-striped table-hover align-middle text-center" 
                            style="border-collapse: collapse; white-space: nowrap;">';
                    echo '<thead class="table-danger text-dark">';
                    echo '<tr>
                            <th><i class="bi bi-person-vcard"></i> Receiver KYC</th>
                            <th><i class="bi bi-person"></i> Receiver Name</th>
                            <th><i class="bi bi-person-badge"></i> Sender Customer ID</th>
                            <th><i class="bi bi-person-fill"></i> Sender Name</th>
                            <th><i class="bi bi-cash-coin"></i> Charge To</th>
                            <th><i class="bi bi-upc-scan"></i> KPTN</th>
                            <th><i class="bi bi-calendar-event"></i> Sendout Date/Time</th>
                            <th><i class="bi bi-calendar-check"></i> Payout Date/Time</th>
                            <th><i class="bi bi-cash-stack"></i> Principal</th>
                            <th><i class="bi bi-currency-exchange"></i> Payout Amount</th>
                            <th><i class="bi bi-wallet2"></i> Service Charge</th>
                            <th><i class="bi bi-graph-up"></i> Commission</th>
                            <th><i class="bi bi-person-gear"></i> SO Operator</th>
                            <th><i class="bi bi-building"></i> Sendout Branch</th>
                            <th><i class="bi bi-hash"></i> Sendout Branch ID</th>
                            <th><i class="bi bi-building-check"></i> Payout Branch</th>
                            <th><i class="bi bi-hash"></i> Payout Branch ID</th>
                            <th><i class="bi bi-geo-alt"></i> Region</th>
                            <th><i class="bi bi-flag"></i> Status</th>
                            <th><i class="bi bi-person-badge"></i> Posted By</th>
                            <th><i class="bi bi-clock"></i> Posted Date/Time</th>
                        </tr>';
                    echo '</thead><tbody>';
                    
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($row['receiver_kyc'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['receiver_name'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['sender_customerID'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['sender_name'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['charge_to'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['kptn'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['sendout_datetime'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['payout_datetime'] ?? '') . '</td>';
                        echo '<td class="fw-bold text-success">₱' . number_format($row['principal'] ?? 0, 2) . '</td>';
                        echo '<td class="fw-bold text-danger">₱' . number_format($row['payout_amount'] ?? 0, 2) . '</td>';
                        echo '<td>₱' . number_format($row['service_charge'] ?? 0, 2) . '</td>';
                        echo '<td>₱' . number_format($row['commission'] ?? 0, 2) . '</td>';
                        echo '<td>' . htmlspecialchars($row['so_operator'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['sendout_branch'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['sendout_branch_id'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['payout_branch'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['payout_branch_id'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['region'] ?? '') . '</td>';
                        $statusText = htmlspecialchars($row['status'] ?? '');

                        switch ($statusText) {
                            case 'CLAIMED':
                                echo '<td><span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1 text-light"></i> CLAIMED
                                    </span></td>';
                                break;

                            case 'UNPAID':
                                echo '<td><span class="badge bg-warning text-dark">
                                        <i class="bi bi-exclamation-circle me-1"></i> UNPAID
                                    </span></td>';
                                break;

                            case 'CANCELLED':
                                echo '<td><span class="badge bg-danger">
                                        <i class="bi bi-x-circle me-1"></i> CANCELLED
                                    </span></td>';
                                break;

                            case 'PENDING':
                                echo '<td><span class="badge bg-primary">
                                        <i class="bi bi-hourglass-split me-1"></i> PENDING
                                    </span></td>';
                                break;

                            default:
                                echo '<td><span class="badge bg-secondary">
                                        <i class="bi bi-question-circle me-1"></i> ' . $statusText . '
                                    </span></td>';
                                break;
                        }

                        echo '<td>' . htmlspecialchars($row['posted_by'] ?? '') . '</td>';
                        echo '<td>' . htmlspecialchars($row['posted_datetime'] ?? '') . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table></div>';
                    
                } else {
                    echo '<div class="alert alert-warning d-flex align-items-center" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            No transactions found for the selected filters.
                          </div>';
                }
            }
            ?>
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
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/sweetalert2/dist/sweetalert2.all.min.js"></script>
<script>
      function loadRegionDetails(regionDescription) {

if (!regionDescription) {
    document.getElementById('region_code').value = '';
    document.getElementById('gl_region').value = '';
    document.getElementById('region_desc_kp7').value = '';
    document.getElementById('region_desc_kpx').value = '';
    return;
}

fetch('fetch_region_details.php?region_description=' + encodeURIComponent(regionDescription))
    .then(res => res.json())
    .then(data => {
        document.getElementById('region_code').value      = data.region_code ?? '';
        document.getElementById('gl_region').value        = data.gl_region ?? '';
        document.getElementById('region_desc_kp7').value  = data.region_desc_kp7 ?? '';
        document.getElementById('region_desc_kpx').value  = data.region_desc_kpx ?? '';
    });
}
    // When the region is changed, send an AJAX request to fetch branches
    document.getElementById('region').addEventListener('change', function() {
        var region = this.value;
        var branchSelect = document.getElementById('branch');
        branchSelect.innerHTML = '<option value="">Loading...</option>'; // Show loading while fetching

        // Create an AJAX request to fetch branches based on the selected region
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'fetch_payout_branches.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                branchSelect.innerHTML = xhr.responseText; // Populate branch options
            } else {
                branchSelect.innerHTML = '<option value="">Error loading branches</option>';
            }
        };
        xhr.send('region=' + encodeURIComponent(region));
    });

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