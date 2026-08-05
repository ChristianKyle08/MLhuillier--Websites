<?php
session_start();
   include '../../config/config.php';
   if (!isset($_SESSION['user_name'])) {
        header('location:login_form.php');
    }

require '../../vendor/autoload.php'; // Include PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
if (isset($_POST['export_sendout'])) {

    /* ===============================
       SANITIZE INPUTS
    =============================== */
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date'] ?? '');
    $end_date   = mysqli_real_escape_string($conn, $_POST['end_date'] ?? '');
    $branch     = mysqli_real_escape_string($conn, $_POST['branch'] ?? '');
    $status     = mysqli_real_escape_string($conn, $_POST['status'] ?? '');

    // REGION VALUES FROM SELECTED HIDDEN FIELDS
    $region_desc = trim($_POST['region_description'] ?? '');
    $region_code = trim($_POST['region_code'] ?? '');
    $gl_region   = trim($_POST['gl_region'] ?? '');
    $kp7_region  = trim($_POST['region_desc_kp7'] ?? '');
    $kpx_region  = trim($_POST['region_desc_kpx'] ?? '');

    $formatted_start_date = date('m/d/Y', strtotime($start_date));
    $formatted_end_date   = date('m/d/Y', strtotime($end_date));

    /* ===============================
       BASE QUERY
    =============================== */
    $sql = "
        SELECT 
            receiver_name, sender_customerID, sender_name, charge_to, kptn,
            contact_number, sendout_datetime, or_number, principal, charge, 
            commission, so_operator, region, sendout_branch, branch_id,
            status, imported_by, imported_datetime
        FROM sendout
        WHERE STR_TO_DATE(sendout_datetime, '%c/%e/%Y, %l:%i %p')
        BETWEEN STR_TO_DATE('$formatted_start_date', '%m/%d/%Y')
        AND STR_TO_DATE('$formatted_end_date', '%m/%d/%Y')
    ";

    /* ===============================
       REGION MATCHING: based on selected regions ONLY
       Ignore spaces and case
    =============================== */
    $regionMatches = [];
    foreach ([$region_desc, $region_code, $gl_region, $kp7_region, $kpx_region] as $r) {
        if ($r !== '') {
            $regionMatches[] = "'" . mysqli_real_escape_string($conn, strtolower(str_replace(' ', '', $r))) . "'";
        }
    }

    if (!empty($regionMatches)) {
        $sql .= "
            AND REPLACE(LOWER(region), ' ', '') IN (" . implode(',', $regionMatches) . ")
        ";
    }

    /* ===============================
       OPTIONAL FILTERS
    =============================== */
    if (!empty($branch)) {
        $sql .= " AND sendout_branch = '$branch'";
    }

    if (!empty($status)) {
        $sql .= " AND status = '$status'";
    }

    $sql .= " ORDER BY STR_TO_DATE(sendout_datetime, '%c/%e/%Y, %l:%i %p') DESC";

    $result = mysqli_query($conn, $sql);

    /* ===============================
       EXPORT TO EXCEL
    =============================== */
    if ($result && mysqli_num_rows($result) > 0) {

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // HEADERS
        $headers = [
            'Receiver Name','Sender Customer ID','Sender Name','Charge To','KPTN','Sendout Date/Time',
            'Principal','Charge','Commission','SO Operator','Sendout Branch','Region','Branch ID',
            'Status','Imported By','Imported Date/Time'
        ];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col.'1', $header);
            $col++;
        }

        // TOTALS
        $totalPrincipal = 0;
        $totalCharge = 0;
        $totalCommission = 0;

        $rowCount = 2;
        while ($row = mysqli_fetch_assoc($result)) {
            $principal = (float)$row['principal'];
            $charge = (float)$row['charge'];
            $commission = (float)$row['commission'];

            $totalPrincipal += $principal;
            $totalCharge += $charge;
            $totalCommission += $commission;

            $sheet->setCellValue('A'.$rowCount, $row['receiver_name'])
                  ->setCellValue('B'.$rowCount, $row['sender_customerID'])
                  ->setCellValue('C'.$rowCount, $row['sender_name'])
                  ->setCellValue('D'.$rowCount, $row['charge_to'])
                  ->setCellValue('E'.$rowCount, $row['kptn'])
                  ->setCellValue('F'.$rowCount, $row['sendout_datetime'])
                  ->setCellValue('G'.$rowCount, number_format($principal, 2))
                  ->setCellValue('H'.$rowCount, number_format($charge, 2))
                  ->setCellValue('I'.$rowCount, number_format($commission, 2))
                  ->setCellValue('J'.$rowCount, $row['so_operator'])
                  ->setCellValue('K'.$rowCount, $row['sendout_branch'])
                  ->setCellValue('L'.$rowCount, $row['region'])
                  ->setCellValue('M'.$rowCount, $row['branch_id'])
                  ->setCellValue('N'.$rowCount, $row['status'])
                  ->setCellValue('O'.$rowCount, $row['imported_by'])
                  ->setCellValue('P'.$rowCount, $row['imported_datetime']);

            $rowCount++;
        }

        // TOTALS ROW
        $sheet->setCellValue('F'.$rowCount, 'Totals:')
              ->setCellValue('G'.$rowCount, number_format($totalPrincipal, 2))
              ->setCellValue('H'.$rowCount, number_format($totalCharge, 2))
              ->setCellValue('I'.$rowCount, number_format($totalCommission, 2));

        // DOWNLOAD HEADERS
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="sendout_report.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit();

    } else {
        echo '<p>No transactions found for the selected region and filters.</p>';
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
  
  <title>ML Rental - Sendout Report</title>
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
        <span class="fw-semibold">Filter Sendout Transactions</span>
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
            <div class="col-md-2">
                <label for="branch" class="form-label fw-semibold">
                    <i class="bi bi-building me-1 text-danger"></i> Branch
                </label>
                <select name="branch" id="branch" class="form-select">
                    <option value="">-- Select --</option>
                    <?php
                    // If user selected a branch, keep it
                    if (isset($_POST['branch']) && $_POST['branch'] != '') {
                        echo '<option value="' . htmlspecialchars($_POST['branch']) . '" selected>'
                             . htmlspecialchars($_POST['branch']) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <!-- Status -->
            <div class="col-md-2">
                <label for="status" class="form-label fw-semibold">
                    <i class="bi bi-flag me-1 text-danger"></i> Status
                </label>
                <select name="status" id="status" class="form-select">
                    <option value="">-- Select --</option>
                    <?php
                    $statusSql = "SELECT DISTINCT status FROM sendout WHERE status != '' ORDER BY status ASC";
                    $resultStatus = mysqli_query($conn, $statusSql);
                    if ($resultStatus) {
                        while ($rowStatus = mysqli_fetch_assoc($resultStatus)) {
                            $selected = (isset($_POST['status']) && $_POST['status'] == $rowStatus['status']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($rowStatus['status']) . "' $selected>"
                                 . htmlspecialchars($rowStatus['status']) . "</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="col-12 d-flex justify-content-end mt-3">
                <button type="submit" name="export_sendout" id="export_sendout" class="btn btn-outline-success me-2">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export
                </button>
                <button type="submit" name="filter_sendout" id="filter_sendout" class="btn btn-danger">
                    <i class="bi bi-search me-1"></i> Display
                </button>
            </div>
        </form>
    </div>
</div>

       <!-- Transaction List -->
<div class="transaction_list">
<?php
if (isset($_POST['filter_sendout'])) {

    /* ===============================
       SANITIZE INPUTS
    =============================== */
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date'] ?? '');
    $end_date   = mysqli_real_escape_string($conn, $_POST['end_date'] ?? '');
    $branch     = mysqli_real_escape_string($conn, $_POST['branch'] ?? '');
    $status     = mysqli_real_escape_string($conn, $_POST['status'] ?? '');

    // REGION VALUES (ALL POSSIBLE MATCHES)
    $region_desc = trim($_POST['region_description'] ?? '');
    $region_code = trim($_POST['region_code'] ?? '');
    $gl_region   = trim($_POST['gl_region'] ?? '');
    $kp7_region  = trim($_POST['region_desc_kp7'] ?? '');
    $kpx_region  = trim($_POST['region_desc_kpx'] ?? '');
    

    $formatted_start_date = date('m/d/Y', strtotime($start_date));
    $formatted_end_date   = date('m/d/Y', strtotime($end_date));

    /* ===============================
       BASE QUERY
    =============================== */
    $sql = "
        SELECT 
            receiver_name, sender_customerID, sender_name, charge_to, 
            kptn, contact_number, sendout_datetime, or_number, 
            principal, charge, commission, so_operator, region, 
            sendout_branch, branch_id, status, imported_by, imported_datetime
        FROM sendout
        WHERE STR_TO_DATE(sendout_datetime, '%c/%e/%Y, %l:%i %p')
        BETWEEN STR_TO_DATE('$formatted_start_date', '%m/%d/%Y')
        AND STR_TO_DATE('$formatted_end_date', '%m/%d/%Y')
    ";

    /* ===============================
       REGION MATCHING (IMPORTANT PART)
       sendout.region MUST MATCH ANY
    =============================== */
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
    

    /* ===============================
       OPTIONAL FILTERS
    =============================== */
    if (!empty($branch)) {
        $sql .= " AND sendout_branch = '$branch'";
    }

    if (!empty($status)) {
        $sql .= " AND status = '$status'";
    }

    $sql .= " ORDER BY STR_TO_DATE(sendout_datetime, '%c/%e/%Y, %l:%i %p') DESC";

    $result = mysqli_query($conn, $sql);

    /* ===============================
       DISPLAY RESULTS
    =============================== */
    if ($result && mysqli_num_rows($result) > 0) {

        echo '<div class="table-responsive shadow-sm">';
        echo '<table class="table table-striped table-hover align-middle text-center"
                style="white-space: nowrap;">';

        echo '<thead class="table-danger text-dark">
                <tr>
                    <th>Receiver</th>
                    <th>Sender ID</th>
                    <th>Sender Name</th>
                    <th>Charge To</th>
                    <th>KPTN</th>
                    <th>Sendout Date</th>
                    <th>Principal</th>
                    <th>Charge</th>
                    <th>Commission</th>
                    <th>SO Operator</th>
                    <th>Branch</th>
                    <th>Region</th>
                    <th>Branch ID</th>
                    <th>Status</th>
                    <th>Imported By</th>
                    <th>Imported Date</th>
                </tr>
              </thead>
              <tbody>';

        while ($row = mysqli_fetch_assoc($result)) {

            echo '<tr>';
            echo '<td>' . htmlspecialchars($row['receiver_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['sender_customerID']) . '</td>';
            echo '<td>' . htmlspecialchars($row['sender_name']) . '</td>';
            echo '<td>' . htmlspecialchars($row['charge_to']) . '</td>';
            echo '<td>' . htmlspecialchars($row['kptn']) . '</td>';
            echo '<td>' . htmlspecialchars($row['sendout_datetime']) . '</td>';
            echo '<td class="fw-bold text-success">₱' . number_format($row['principal'], 2) . '</td>';
            echo '<td class="fw-bold text-danger">₱' . number_format($row['charge'], 2) . '</td>';
            echo '<td>₱' . number_format($row['commission'], 2) . '</td>';
            echo '<td>' . htmlspecialchars($row['so_operator']) . '</td>';
            echo '<td>' . htmlspecialchars($row['sendout_branch']) . '</td>';
            echo '<td>' . htmlspecialchars($row['region']) . '</td>';
            echo '<td>' . htmlspecialchars($row['branch_id']) . '</td>';

            // STATUS BADGE
            switch ($row['status']) {
                case 'CLAIMED':
                    echo '<td><span class="badge bg-success">CLAIMED</span></td>';
                    break;
                case 'UNPAID':
                    echo '<td><span class="badge bg-warning text-dark">UNPAID</span></td>';
                    break;
                case 'CANCELLED':
                    echo '<td><span class="badge bg-danger">CANCELLED</span></td>';
                    break;
                case 'PENDING':
                    echo '<td><span class="badge bg-primary">PENDING</span></td>';
                    break;
                default:
                    echo '<td><span class="badge bg-secondary">' . htmlspecialchars($row['status']) . '</span></td>';
            }

            echo '<td>' . htmlspecialchars($row['imported_by']) . '</td>';
            echo '<td>' . htmlspecialchars($row['imported_datetime']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';

    } else {
        echo '<div class="alert alert-warning d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                No transactions found for the selected filters.
              </div>';
    }
}
?>
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
        xhr.open('POST', 'fetch_sendout_branches.php', true);
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