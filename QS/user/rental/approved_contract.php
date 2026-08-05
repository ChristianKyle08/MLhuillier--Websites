<?php
session_start();
include '../../config/config.php';
if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
        <link rel="shortcut icon" href="../../assets/images/rental_logo.png" type="image/x-icon">
        <meta name="description" content="">
        <title>ML Rental - For Review Contract</title>
        <link rel="stylesheet" href="../../css/sidebar.css?v=<?php echo time(); ?>"> 
        <link href="../../assets/css/poppins.css" rel="stylesheet">
        <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
        <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">
        <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../../assets/css/sidebar.css">
        <link rel="stylesheet" href="../../assets/css/scrollbar.css">
        <style>
            .badge-light-orange {
                background-color: #ffcc80; /* light orange */
                color: #663c00; /* dark brown text for readability */
            }
            .btn-danger {
                background-color: #d70c0c !important;
                border: none;
            }
            .btn-danger:hover {
                background-color: #b50909 !important;
            }
            .form-control:focus {
                box-shadow: 0 0 0 0.2rem rgba(215, 12, 12, 0.25);
                border-color: #d70c0c;
            }    
            .btn-outline-view {
                border: 1px solid #0d6efd; /* Bootstrap Primary Blue */
                color: #0d6efd;
                background-color: rgba(13, 110, 253, 0.1);
                transition: all 0.3s;
            }

            .btn-outline-view:hover {
                background-color: rgba(13, 110, 253, 0.1);
            } 
            .table-container {
    max-height: 450px;   /* 👈 adjust height as needed */
    overflow-y: auto;    /* vertical scroll */
    overflow-x: auto;    /* keep horizontal scroll for wide tables */
  }

  /* optional: keep table header sticky */
  .table-container thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f8d7da; /* same as table-danger */
  }
        </style>
    </head>
    <body>
        <?php include ('navbar.php'); ?>
        <div id="mainContent" class="bg-light min-vh-100">
        <button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
            <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
            <span class="fw-normal">Menu</span>
        </button>

  <?php
// --- User role and location info ---
$userRole   = $_SESSION['user_role'] ?? '';
$userZone   = $_SESSION['mainzone'] ?? '';
$userRegion = $_SESSION['region'] ?? '';
$userArea   = $_SESSION['area'] ?? '';

// --- Handle filter input ---
$selectedZone   = $_GET['mainzone'] ?? '';
$selectedRegion = $_GET['region'] ?? '';
$selectedArea   = $_GET['area'] ?? '';

$showResults = false; // default hidden
$query = "";

// --- Base query ---
$baseQuery = "
    SELECT id, branch, contract_number, l1_firstname, l1_lastname, request_status, 
           contract_start, contract_end, start_date, end_date, payment_due_date, 
           mainzone, region, area, rfp_status, mode_of_payment
    FROM create_contract
    WHERE rfp_status = 'Reviewed'
      AND request_status = 'Approved'
      AND status = 'Active'
      AND mode_of_payment IN ('PAYMENT SOLUTION', 'PDC', 'WALLET', 'RTA')
";

// --- Am-Creator: auto-display transactions ---
if ($userRole === 'Am-Creator') {
    $showResults = true;
    $query = $baseQuery . "
      AND mainzone = '" . mysqli_real_escape_string($conn, $userZone) . "'
      AND region   = '" . mysqli_real_escape_string($conn, $userRegion) . "'
      AND area     = '" . mysqli_real_escape_string($conn, $userArea) . "'";
}

// --- Rm-Reviewer: filter by Area (optional) ---
elseif ($userRole === 'Rm-Reviewer') {
    if (!empty($selectedArea)) {
        $showResults = true;
        $query = $baseQuery . "
          AND region = '" . mysqli_real_escape_string($conn, $userRegion) . "'
          AND area   = '" . mysqli_real_escape_string($conn, $selectedArea) . "'";
    }
}

// --- Vpo roles: allow Region and/or Area ---
elseif (in_array($userRole, ['Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver'])) {
    $conditions = [];
    if (!empty($selectedRegion)) {
        $conditions[] = "region = '" . mysqli_real_escape_string($conn, $selectedRegion) . "'";
    }
    if (!empty($selectedArea)) {
        $conditions[] = "area = '" . mysqli_real_escape_string($conn, $selectedArea) . "'";
    }
    if (!empty($conditions)) {
        $showResults = true;
        $query = $baseQuery . "
          AND mainzone = '" . mysqli_real_escape_string($conn, $userZone) . "'
          AND " . implode(" AND ", $conditions);
    }
}

// --- HO & Audit: allow Mainzone, Region, Area (any combination) ---
elseif (in_array($userRole, ['HO', 'Auditor'])) {
    $conditions = [];
    if (!empty($selectedZone)) {
        $conditions[] = "mainzone = '" . mysqli_real_escape_string($conn, $selectedZone) . "'";
    }
    if (!empty($selectedRegion)) {
        $conditions[] = "region = '" . mysqli_real_escape_string($conn, $selectedRegion) . "'";
    }
    if (!empty($selectedArea)) {
        $conditions[] = "area = '" . mysqli_real_escape_string($conn, $selectedArea) . "'";
    }
    if (!empty($conditions)) {
        $showResults = true;
        $query = $baseQuery . " AND " . implode(" AND ", $conditions);
    }
}

$result = $showResults ? mysqli_query($conn, $query) : null;
?>


<!-- Main Container -->
<div class="container pb-5">
  <div class="card shadow border-0 rounded-4">
    <!-- Header -->
    <div class="text-center mt-3">
        <h3 class="fw-bold text-danger">
        <i class="bi bi-check-circle me-2 text-danger"></i> APPROVED CONTRACTS
        </h3>
    </div>
    <div class="card-header bg-white border-0 rounded-top-4 px-4 py-1 d-flex justify-content-between align-items-center">
      <h5 class="mb-0 fw-bold text-danger d-flex align-items-center">
        <i class="bi bi-clock-history me-2"></i> For Review Contracts (RFP)
      </h5>
    </div>

    <div class="card-body px-4 py-0">

        <!-- 🔎 Filter Form -->
        <?php if ($userRole !== 'Am-Creator'): ?>
        <!-- Info -->
        <div class="alert alert-info py-2 px-3 mb-3 rounded-3">
            <i class="bi bi-info-circle me-2"></i>
            Please use the filters below to view transactions.  
            <strong>At least one filter is required.</strong>
        </div>

        <form method="get" class="row g-2 mb-3">
            <?php
            // --- SQL filter conditions based on userRole ---
            $whereZone = $whereRegion = $whereArea = "";

            // MAINZONE FILTER LOGIC
            if (in_array($userRole, ['HO', 'Auditor'])) {
                $whereZone = ""; // show all mainzones
            } else {
                $whereZone = "WHERE mainzone = '$userZone'";
            }

            // REGION FILTER LOGIC
            switch ($userRole) {
                case 'Am-Creator':
                    $whereRegion = "WHERE region = '$userRegion' AND area = '$userArea'";
                    break;
                case 'Rm-Reviewer':
                    $whereRegion = "WHERE region = '$userRegion'";
                    break;
                case 'Vpo-Checker':
                case 'Vpo-Reviewer':
                case 'Vpo-Approver':
                    $whereRegion = "WHERE mainzone = '$userZone'";
                    break;
                case 'HO':
                case 'Auditor':
                    $whereRegion = "";
                    break;
            }

            // AREA FILTER LOGIC
            switch ($userRole) {
                case 'Am-Creator':
                    $whereArea = "WHERE region = '$userRegion' AND area = '$userArea'";
                    break;
                case 'Rm-Reviewer':
                    $whereArea = "WHERE region = '$userRegion'";
                    break;
                case 'Vpo-Checker':
                case 'Vpo-Reviewer':
                case 'Vpo-Approver':
                    $whereArea = "WHERE mainzone = '$userZone'";
                    break;
                case 'HO':
                case 'Auditor':
                    $whereArea = "";
                    break;
            }

            // --- Safely build WHERE clauses ---
        $whereZone   = trim($whereZone ?? '');
        $whereRegion = trim($whereRegion ?? '');
        $whereArea   = trim($whereArea ?? '');

        // Function to safely append filters
        function buildWhereClause($baseWhere, $extraCondition) {
            if ($baseWhere) {
                // If already has WHERE keyword, just append with AND
                return preg_match('/\bWHERE\b/i', $baseWhere)
                    ? "$baseWhere AND $extraCondition"
                    : "WHERE $baseWhere AND $extraCondition";
            } else {
                // No base where condition
                return "WHERE $extraCondition";
            }
        }

        // --- Apply safe filters ---
        $whereZoneFinal   = buildWhereClause($whereZone, "mainzone <> '' ");
        $whereRegionFinal = buildWhereClause($whereRegion, "region <> '' ");
        $whereAreaFinal   = buildWhereClause($whereArea, "area <> '' ");

        // --- Final queries ---
        $zones   = mysqli_query($conn, "SELECT DISTINCT mainzone FROM create_contract $whereZoneFinal ORDER BY mainzone ASC");
        $regions = mysqli_query($conn, "SELECT DISTINCT region FROM create_contract $whereRegionFinal ORDER BY region ASC");
        $areas   = mysqli_query($conn, "SELECT DISTINCT area FROM create_contract $whereAreaFinal ORDER BY area ASC");
            ?>

            <!-- MAINZONE -->
            <?php if (in_array($userRole, ['HO', 'Auditor'])): ?>
            <div class="col-md-3">
                <input list="mainzoneList" name="mainzone" class="form-control"
                    placeholder="Select Mainzone"
                    value="<?= htmlspecialchars($selectedZone ?? '') ?>" autocomplete="off">
                <datalist id="mainzoneList">
                    <?php if ($zones && mysqli_num_rows($zones) > 0): ?>
                        <?php while ($z = mysqli_fetch_assoc($zones)): ?>
                            <option value="<?= htmlspecialchars($z['mainzone']) ?>"></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </datalist>
            </div>
            <?php endif; ?>

            <!-- REGION -->
            <?php if (in_array($userRole, ['Am-Creator','Vpo-Checker','Vpo-Reviewer','Vpo-Approver','HO','Auditor'])): ?>
            <div class="col-md-3">
                <input list="regionList" name="region" class="form-control"
                    placeholder="Select Region"
                    value="<?= htmlspecialchars($selectedRegion ?? '') ?>" autocomplete="off">
                <datalist id="regionList">
                    <?php if ($regions && mysqli_num_rows($regions) > 0): ?>
                        <?php while ($r = mysqli_fetch_assoc($regions)): ?>
                            <option value="<?= htmlspecialchars($r['region']) ?>"></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </datalist>
            </div>
            <?php endif; ?>

            <!-- AREA -->
            <?php if (in_array($userRole, ['Rm-Reviewer','Vpo-Checker','Vpo-Reviewer','Vpo-Approver','HO','Auditor'])): ?>
            <div class="col-md-3">
                <input list="areaList" name="area" class="form-control"
                    placeholder="Select Area"
                    value="<?= htmlspecialchars($selectedArea ?? '') ?>" autocomplete="off">
                <datalist id="areaList">
                    <?php if ($areas && mysqli_num_rows($areas) > 0): ?>
                        <?php while ($a = mysqli_fetch_assoc($areas)): ?>
                            <option value="<?= htmlspecialchars($a['area']) ?>"></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </datalist>
            </div>
            <?php endif; ?>

            <!-- APPLY BUTTON -->
            <div class="col-md-2">
                <button type="submit" class="btn btn-danger w-100">
                    <i class="bi bi-funnel me-1"></i> Apply
                </button>
            </div>

            </form>

        <?php endif; ?>
      <!-- 📊 Transactions Table -->
      <div class="table-container table-responsive">
      <table class="table table-hover align-middle mb-0">
    <thead class="table-danger text-dark rounded-3">
    <tr>
        <th>Branch</th>
        <th>Contract #</th>
        <th>Effectivity Date</th>
        <th>Expiry Date</th>
        <th>RFP Start</th>
        <th>RFP End</th>
        <th>Payment Due Date</th>
        <th>Mode of Payment</th>
        <th>Lessor</th>
        <th class="text-center">View</th>
        <th class="text-center">RFP Requested By</th>
        <th class="text-center">RFP Requested Date</th>
        <th class="text-center">Category</th>
    </tr>
    </thead>
    <tbody>
    <?php if ($showResults && $result && mysqli_num_rows($result) > 0): ?>
        <?php 
        $lastMode = null; 
        while ($row = mysqli_fetch_assoc($result)): 
            $lessorName    = trim(($row['l1_firstname'] ?? '') . ' ' . ($row['l1_lastname'] ?? ''));
            $contractStart = !empty($row['contract_start']) ? date('F j, Y', strtotime($row['contract_start'])) : '-';
            $contractEnd   = !empty($row['contract_end']) ? date('F j, Y', strtotime($row['contract_end'])) : '-';
            $start_date    = !empty($row['start_date']) ? date('F Y', strtotime($row['start_date'])) : '-';
            $end_date      = !empty($row['end_date']) ? date('F Y', strtotime($row['end_date'])) : '-';

            $statusLabel = '';
            if (!empty($row['contract_end']) && !empty($row['end_date'])) {
                $contractEndCheck = date('Y-m', strtotime($row['contract_end']));
                $endDateCheck     = date('Y-m', strtotime($row['end_date']));
                if ($contractEndCheck === $endDateCheck) {
                    $statusLabel = "<span class='badge bg-danger'>Expired</span>";
                } else {
                    $statusLabel = "<span class='badge bg-warning text-dark'>Pending RFP</span>";
                }
            } else {
                $statusLabel = "<span class='badge bg-secondary'>N/A</span>";
            }

            $currentMode = $row['mode_of_payment'] ?? 'Unknown';

            if ($lastMode !== $currentMode): ?>
                <tr class="table-light fw-bold">
                    <td colspan="11" class="text-start">
                        Mode of Payment: <?= htmlspecialchars($currentMode) ?>
                    </td>
                </tr>
            <?php 
                $lastMode = $currentMode; 
            endif; 
            ?>
            
            <tr>
                <td><?= htmlspecialchars($row['branch']) ?></td>
                <td><?= htmlspecialchars($row['contract_number']) ?></td>
                <td><?= htmlspecialchars($contractStart) ?></td>
                <td><?= htmlspecialchars($contractEnd) ?></td>
                <td><?= htmlspecialchars($start_date) ?></td>
                <td><?= htmlspecialchars($end_date) ?></td>
                <td><?= htmlspecialchars($row['payment_due_date']) ?></td>
                <td><?= htmlspecialchars($row['mode_of_payment']) ?></td>
                <td><?= htmlspecialchars($lessorName) ?></td>
                <td class="text-center">
                    <button type="button"
                        class="btn btn-sm btn-outline-view rounded-pill px-3 viewBtn d-inline-flex align-items-center justify-content-center"
                        data-id="<?= htmlspecialchars($row['id']) ?>">
                        <i class="bi bi-eye me-1 fs-6"></i>
                    </button>
                </td>
                <td class="text-center"><?= htmlspecialchars($rowDetails['prepared_by'] ?? '') ?></td>
                <td class="text-center"><?= htmlspecialchars($rowDetails['rfp_date'] ?? '') ?></td>
                <td class="text-center"><?= $statusLabel ?></td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="11" class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                <p class="mb-0 fw-semibold">No transactions displayed</p>
                <small class="text-muted">Use the filter above to display contracts.</small>
            </td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

        </div>
    </div>
  </div>
</div>

</div>
      <!-- Contract Details Modal -->
    <div class="modal fade" id="contractModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title text-white fw-semibold">Contract Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contractDetails">
                <p class="text-muted">Loading...</p>
            </div>
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
           document.addEventListener("DOMContentLoaded", () => {

    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll('#transactionTable tbody tr');
            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }

    const viewBtns = document.querySelectorAll(".viewBtn");
    viewBtns.forEach(btn => {
        btn.addEventListener("click", () => {
            const id = btn.getAttribute("data-id");

            // Show modal
            const modal = new bootstrap.Modal(document.getElementById("contractModal"));
            modal.show();

            // Load details
            const detailsDiv = document.getElementById("contractDetails");
            detailsDiv.innerHTML = "<p class='text-muted'>Loading...</p>";

            fetch("fetch_contract_details.php?id=" + id)
                .then(res => res.text())
                .then(data => {
                    detailsDiv.innerHTML = data;
                })
                .catch(() => {
                    detailsDiv.innerHTML = "<div class='alert alert-danger'>Failed to load details.</div>";
                });
        });
    });
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
    </body>
</html>
