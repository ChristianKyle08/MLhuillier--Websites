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
                overflow-x: auto;    /* horizontal scroll for wide tables */
            }

            /* keep table header sticky */
            .table-container thead th {
                position: sticky;
                top: 0;
                z-index: 2;
                background: #f8d7da; /* same color as .table-danger */
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

$showResults = false; 
$query = "";

// --- Base query ---
$baseQuery = "
    SELECT id, branch, contract_number, l1_firstname, l1_lastname, request_status, 
           contract_start, contract_end, start_date, end_date, payment_due_date, 
           mainzone, region, area, rfp_status, request_status, prepared_by, rfp_date
    FROM create_contract
    WHERE rfp_status = 'Reviewed'
      AND request_status IN ('Created','Prepared','Reviewed','Checked','Approved')
      AND status = 'Active'
      AND mode_of_payment = 'PAYMENT SOLUTION'
      -- ✅ Exclude rows where contract_end and end_date are same month+year
      AND NOT (
          YEAR(contract_end) = YEAR(end_date)
          AND MONTH(contract_end) = MONTH(end_date)
          AND request_status = 'Approved'
      )
";

// --- Am-Creator: always show their assigned zone/region/area ---
if ($userRole === 'Am-Creator') {
    $showResults = true;
    $query = $baseQuery . "
      AND mainzone = '" . mysqli_real_escape_string($conn, $userZone) . "'
      AND region   = '" . mysqli_real_escape_string($conn, $userRegion) . "'
      AND area     = '" . mysqli_real_escape_string($conn, $userArea) . "'";
}

// --- Rm-Reviewer: needs to filter by Area ---
elseif ($userRole === 'Rm-Reviewer') {
    if (!empty($selectedArea)) {
        $showResults = true;
        $query = $baseQuery . "
          AND region = '" . mysqli_real_escape_string($conn, $userRegion) . "'
          AND area   = '" . mysqli_real_escape_string($conn, $selectedArea) . "'";
    }
}

// --- Vpo roles: must filter by Region/Area ---
elseif (in_array($userRole, ['Vpo-Checker','Vpo-Reviewer','Vpo-Approver'])) {
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

// --- HO & Audit: can filter by Mainzone, Region, Area ---
elseif (in_array($userRole, ['HO','Auditor'])) {
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
          <!-- Header with Title + Search -->
          <div class="card-header bg-white border-0 rounded-top-4 px-4 py-3 d-flex justify-content-between align-items-center">
              <h5 class="mb-0 fw-bold text-danger d-flex align-items-center">
                  <i class="bi bi-clock-history me-2"></i> For Review Contracts (PAYMENT SOLUTION RFP)
              </h5>
              <div class="d-flex gap-2">
                    <input type="text" id="searchInput" class="form-control rounded-pill px-3" placeholder="Search branch or contract #">
                    <button class="btn btn-danger rounded-pill px-1" style="width:140px;">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>

              </div>
          </div>

          <!-- Card Body -->
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

        // MAINZONE
        if (in_array($userRole, ['HO', 'Auditor'])) {
            $whereZone = ""; // Show all mainzones
        } else {
            $whereZone = "WHERE mainzone = '$userZone'";
        }

        // REGION
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

        // AREA
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
            case 'Finance':
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
  $whereZoneFinal   = buildWhereClause($whereZone, "mainzone <> '' AND mode_of_payment = 'PAYMENT SOLUTION'");
  $whereRegionFinal = buildWhereClause($whereRegion, "region <> '' AND mode_of_payment = 'PAYMENT SOLUTION'");
  $whereAreaFinal   = buildWhereClause($whereArea, "area <> '' AND mode_of_payment = 'PAYMENT SOLUTION'");

  // --- Final queries ---
  $zones   = mysqli_query($conn, "SELECT DISTINCT mainzone FROM create_contract $whereZoneFinal ORDER BY mainzone ASC");
  $regions = mysqli_query($conn, "SELECT DISTINCT region FROM create_contract $whereRegionFinal ORDER BY region ASC");
  $areas   = mysqli_query($conn, "SELECT DISTINCT area FROM create_contract $whereAreaFinal ORDER BY area ASC");
        ?>

        <!-- MAINZONE FILTER -->
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

        <!-- REGION FILTER -->
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

        <!-- AREA FILTER -->
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
        <div class="table-container table-responsive">
            <table class="table table-hover align-middle mb-0" 
                    style="border-collapse: collapse; white-space: nowrap;">
                <thead class="table-danger text-dark rounded-3">
                <tr>
                    <th class="fw-normal">
                    <i class="bi bi-building text-danger me-1"></i> Branch
                    </th>
                    <th class="fw-normal">
                    <i class="bi bi-file-earmark-pdf text-danger me-1"></i> Contract #
                    </th>
                    <th class="fw-normal">
                    <i class="bi bi-calendar-check text-danger me-1"></i> Effectivity Date
                    </th>
                    <th class="fw-normal">
                    <i class="bi bi-calendar-x text-danger me-1"></i> Expiry Date
                    </th>
                    <th class="fw-normal">
                    <i class="bi bi-calendar-check text-danger me-1"></i> RFP Start Date
                    </th>
                    <th class="fw-normal">
                    <i class="bi bi-calendar-x text-danger me-1"></i> RFP End Date
                    </th>
                    <th class="fw-normal">
                    <i class="bi bi-calendar-date me-1 text-danger"></i> Payment Due Date
                    </th>
                    <th class="fw-normal">
                    <i class="bi bi-person-badge text-danger me-1"></i> Lessor
                    </th>
                    <th class="text-center fw-normal">
                    <i class="bi bi-gear text-danger me-1"></i> View
                    </th>
                    <th class="fw-normal text-center">
                    <i class="bi bi-calendar-date text-danger me-1"></i> Category
                    </th>
                    <th class="fw-normal"><i class="bi bi-person me-1 text-danger"></i>RFP Requested By</th>
                    <th class="fw-normal"><i class="bi bi-calendar-date me-1 text-danger"></i>RFP Requested Date</th>
                    <th class="fw-normal text-center">
                        <i class="bi bi-geo-alt-fill text-danger me-1"></i>Location
                    </th>
                </tr>
                </thead>
                <tbody id="transactionTable" class="table-group-divider">
                <?php if ($showResults && $result && mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <?php
                        $lessorName = trim(($row['l1_firstname'] ?? '') . ' ' . ($row['l1_lastname'] ?? ''));

                        // Format dates
                        $contractStart = !empty($row['contract_start']) ? date('F j, Y', strtotime($row['contract_start'])) : '-';
                        $contractEnd   = !empty($row['contract_end'])   ? date('F j, Y', strtotime($row['contract_end']))   : '-';
                        $start_date    = !empty($row['start_date'])     ? date('F Y', strtotime($row['start_date']))       : '-';
                        $end_date      = !empty($row['end_date'])       ? date('F Y', strtotime($row['end_date']))         : '-';

                        // Format payment due date
                        if (!empty($row['payment_due_date']) && $row['payment_due_date'] !== '0000-00-00') {
                        $day = (int)date('j', strtotime($row['payment_due_date']));
                        $suffix = 'th';
                        if (!in_array($day % 100, [11,12,13])) {
                            switch ($day % 10) {
                            case 1: $suffix = 'st'; break;
                            case 2: $suffix = 'nd'; break;
                            case 3: $suffix = 'rd'; break;
                            }
                        }
                        $paymentDue = "Every {$day}{$suffix} day of the month";
                        } else {
                        $paymentDue = '—';
                        }

                        // Status label
                        $rfpStatus     = $row['rfp_status'] ?? null;
                        $requestStatus = $row['request_status'] ?? null;
                        $statusLabel   = '';
                        $statusClass   = '';

                        if (!empty($row['contract_end']) && !empty($row['end_date']) &&
                            date('Y-m', strtotime($row['contract_end'])) === date('Y-m', strtotime($row['end_date'])) && $requestStatus === 'Approved') {
                        $statusLabel = "Expired";
                        $statusClass = "text-danger fw-bold";
                        } else {
                        if (((is_null($rfpStatus) || $rfpStatus === '') && in_array($requestStatus, ['Prepared','Created']))
                            || ($rfpStatus === 'Reviewed' && $requestStatus === 'Ready')) {
                            $statusLabel = "DATA ARCHIVING";
                        } elseif ($rfpStatus === 'Reviewed' && in_array($requestStatus, ['Prepared','Created','Reviewed','Checked','Approved'])) {
                            $statusLabel = "RFP";
                        } else {
                            $statusLabel = htmlspecialchars($row['category'] ?? '');
                        }
                        $statusClass = "text-dark";
                        }

                        // Determine Location value
                        $locationValue = '';
                        if ($rfpStatus === 'Reviewed' && $requestStatus === 'Created') {
                            $locationValue = 'Area Manager';
                        } elseif ($rfpStatus === 'Reviewed' && $requestStatus === 'Prepared') {
                            $locationValue = 'Regional Manager';
                        } elseif ($rfpStatus === 'Reviewed' && in_array($requestStatus, ['Checked', 'Reviewed'])) {
                            $locationValue = 'VPO';
                        } elseif ($rfpStatus === 'Reviewed' && $requestStatus === 'Approved') {
                            $locationValue = 'AM - Ready for RFP';
                        }
                        else {
                            $locationValue = '-'; // default if conditions not met
                        }
                    ?>
                    <tr class="align-middle">
                        <td class="text-dark px-3"><?= htmlspecialchars($row['branch']) ?></td>
                        <td class="text-dark"><?= htmlspecialchars($row['contract_number']) ?></td>
                        <td class="text-dark"><?= $contractStart ?></td>
                        <td class="text-dark"><?= $contractEnd ?></td>
                        <td class="text-dark"><?= $start_date ?></td>
                        <td class="text-dark"><?= $end_date ?></td>
                        <td class="text-dark"><?= $paymentDue ?></td>
                        <td class="text-dark"><?= htmlspecialchars($lessorName) ?></td>
                        <td class="text-center">
                        <button class="btn btn-sm btn-outline-view rounded-pill px-3 viewBtn d-inline-flex align-items-center justify-content-center" 
                                data-id="<?= $row['id'] ?>">
                            <i class="bi bi-eye me-1 fs-6"></i>
                        </button>
                        </td>
                        <td class="text-center">
                        <span class="badge bg-info-subtle fw-semibold px-3 py-2 rounded-pill">
                            <i class="bi bi-clipboard-data me-1 text-dark"></i> 
                            <span class="<?= $statusClass ?>"><?= $statusLabel ?></span>
                        </span>
                        </td>
                        <td><?= !empty($row['prepared_by']) ? htmlspecialchars($row['prepared_by']) : '---'; ?></td>

              <td>
                  <i class="bi bi-calendar-event me-1 text-danger"></i>
                  <?= !empty($row['rfp_date']) ? date('F d, Y', strtotime($row['rfp_date'])) : '---'; ?>
              </td>
                        <td class="text-center text-dark fw-semibold"><?= $locationValue ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                    <td colspan="10" class="text-center text-muted py-5">
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
            document.getElementById('searchInput').addEventListener('keyup', function() {
                let filter = this.value.toLowerCase();
                let rows = document.querySelectorAll('#transactionTable tr');
                rows.forEach(row => {
                    let text = row.innerText.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });

            document.addEventListener("DOMContentLoaded", () => {
                const viewBtns = document.querySelectorAll(".viewBtn");

                viewBtns.forEach(btn => {
                    btn.addEventListener("click", () => {
                    const id = btn.getAttribute("data-id");

                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById("contractModal"));
                    modal.show();

                    // Load details with your fetch file
                    document.getElementById("contractDetails").innerHTML = "<p class='text-muted'>Loading...</p>";

                    fetch("fetch_contract_details.php?id=" + id)
                        .then(res => res.text())
                        .then(data => {
                        document.getElementById("contractDetails").innerHTML = data;
                        })
                        .catch(() => {
                        document.getElementById("contractDetails").innerHTML = "<div class='alert alert-danger'>Failed to load details.</div>";
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
