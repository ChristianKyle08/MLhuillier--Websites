<?php
ob_start();
session_start();

if (!isset($_SESSION['user_email']) || empty($_SESSION['user_email'])) {
    header('Location: login_form.php');
    exit();
}

include '../../config/config.php';

$user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);
$userRole = $_SESSION['user_role'] ?? '';

// Get user info
$userQuery = "SELECT roles, region, area, mainzone FROM user_form WHERE username = '$user_email' OR email = '$user_email'";
$userResult = mysqli_query($conn, $userQuery);

$userRegion = $userArea = $userMainZone = '';
if ($userResult && mysqli_num_rows($userResult) > 0) {
  $userRow = mysqli_fetch_assoc($userResult);
  $userRole = $userRow['roles'];
  $userRegion = $userRow['region'];
  $userArea = $userRow['area'];
  $userMainZone = $userRow['mainzone'];
}

// 1. CAPTURE FILTER INPUTS
$filterMainzone = $_GET['f_mainzone'] ?? '';
$filterRegion   = $_GET['f_region'] ?? '';
$filterArea     = $_GET['f_area'] ?? '';

// Check if any filter is actually active
$isFiltered = (!empty($filterMainzone) || !empty($filterRegion) || !empty($filterArea));

$withoutRFPCount = 0;
$createdContracts = [];

// DEFAULT IS NOTHING SHOWS UNLESS FILTERED
if ($isFiltered) {

    // Count Query Modification
    $withoutRFP_countQuery = "
        SELECT COUNT(DISTINCT branch_id) AS total
        FROM create_contract
        WHERE rfp_status = 'Reviewed'
          AND status = 'Active'
          AND status != 'Terminated'
          AND (request_status = 'Ready' OR request_status = 'Approved')
          AND contract_number != 'VOID'
         AND NOT (
          YEAR(contract_end) = YEAR(end_date)
          AND MONTH(contract_end) = MONTH(end_date)
          AND request_status = 'Approved'
        )
    ";

    // Build the filtering string based on inputs
    $filterSql = "";
    if (!empty($filterMainzone)) $filterSql .= " AND mainzone = '" . mysqli_real_escape_string($conn, $filterMainzone) . "'";
    if (!empty($filterRegion))   $filterSql .= " AND region = '" . mysqli_real_escape_string($conn, $filterRegion) . "'";
    if (!empty($filterArea))     $filterSql .= " AND area = '" . mysqli_real_escape_string($conn, $filterArea) . "'";

    $withoutRFP_countQuery .= $filterSql;

    // Apply role-based constraints (User cannot see outside their assigned zone)
    if ($userRole === 'Am-Creator') {
      $withoutRFP_countQuery .= " AND mainzone = '$userMainZone' AND region = '$userRegion' AND area = '$userArea'";
    } elseif ($userRole === 'Rm-Reviewer') {
      $withoutRFP_countQuery .= " AND region = '$userRegion'";
    } elseif (in_array($userRole, ['Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver'])) {
      $withoutRFP_countQuery .= " AND mainzone = '$userMainZone'";
    }

    $resultWithoutRFP = mysqli_query($conn, $withoutRFP_countQuery);
    if ($resultWithoutRFP && $row = mysqli_fetch_assoc($resultWithoutRFP)) {
      $withoutRFPCount = $row['total'];
    }

    // MAIN CONTRACTS QUERY
    $queryContracts = "SELECT * FROM create_contract WHERE status = 'Active'";
    $queryContracts .= $filterSql; // Apply the user's manual filters

    // Add logic constraints per role
    switch ($userRole) {
      case 'Am-Creator':
        $queryContracts .= " AND ( (rfp_status = 'Reviewed' AND request_status = 'Ready') OR (rfp_status = 'Reviewed' AND request_status = 'Approved') )
        AND mainzone = '$userMainZone' AND region = '$userRegion' AND area = '$userArea'";
        break;

      case 'Rm-Reviewer':
        $queryContracts .= " AND ( (rfp_status = 'Reviewed' AND request_status = 'Ready') OR (rfp_status = 'Reviewed' AND request_status = 'Approved') )
        AND region = '$userRegion'";
        break;

      default: // VPO Roles
        $queryContracts .= " AND ( (rfp_status = 'Reviewed' AND request_status = 'Ready') OR (rfp_status = 'Reviewed' AND request_status = 'Approved') )
        AND mainzone = '$userMainZone'";
        break;
    }

    // Exclude finished contracts
    $queryContracts .= " AND contract_number NOT IN (
        SELECT contract_number 
        FROM create_contract 
        WHERE DATE_FORMAT(contract_end, '%Y-%m') = DATE_FORMAT(end_date, '%Y-%m')
    ) ORDER BY created_date DESC";

    $resultContracts = mysqli_query($conn, $queryContracts);
    if ($resultContracts) {
        while ($row = mysqli_fetch_assoc($resultContracts)) {
          $createdContracts[] = $row;
        }
    }
}

// Fetch distinct values for dropdowns based on user permissions
$filterOptions = [
  'mainzone' => [],
  'region' => [],
  'area' => []
];

// Base constraint for dropdowns (so they don't see options they can't filter)
$dropdownConstraint = " WHERE 1=1";
if ($userRole === 'Am-Creator') {
  $dropdownConstraint .= " AND mainzone = '$userMainZone' AND region = '$userRegion' AND area = '$userArea'";
} elseif ($userRole === 'Rm-Reviewer') {
  $dropdownConstraint .= " AND region = '$userRegion'";
} elseif (in_array($userRole, ['Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver'])) {
  $dropdownConstraint .= " AND mainzone = '$userMainZone'";
}

// Populate Mainzones
$mzRes = mysqli_query($conn, "SELECT DISTINCT mainzone FROM create_contract $dropdownConstraint ORDER BY mainzone ASC");
while($row = mysqli_fetch_assoc($mzRes)) $filterOptions['mainzone'][] = $row['mainzone'];

// Populate Regions
$regRes = mysqli_query($conn, "SELECT DISTINCT region FROM create_contract $dropdownConstraint ORDER BY region ASC");
while($row = mysqli_fetch_assoc($regRes)) $filterOptions['region'][] = $row['region'];

// Populate Areas
$areaRes = mysqli_query($conn, "SELECT DISTINCT area FROM create_contract $dropdownConstraint ORDER BY area ASC");
while($row = mysqli_fetch_assoc($areaRes)) $filterOptions['area'][] = $row['area'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Request For Payment - ML Rentals</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="../../assets/css/poppins.css" rel="stylesheet">
  <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">
  <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/sidebar.css">
  <link rel="stylesheet" href="../../assets/css/scrollbar.css">
  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Poppins', sans-serif;
      color: #333;
    }

    /* ========== Card Styles ========== */
.card {
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 1rem;
  transition: transform 0.3s ease-in-out;
}

.card:hover {
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
}

/* ========== Table Styles ========== */

.table thead {
  background-color: #d70c0c;
  color: #fff;
}
thead th {
  padding: 0.75rem 0.75rem !important;
}

.table-hover tbody tr:hover {
  background-color: #f9f9f9;
}

.badge {
  font-size: 0.75rem;
}

.card-body h5 {
  font-family: 'Poppins', sans-serif;
}
/* ========== Badges ========== */
.badge.bg-warning {
  background-color: #ffc107 !important;
  color: #333;
}

.badge.bg-success {
  background-color: #28a745 !important;
}

.badge.bg-danger {
  background-color: #d70c0c !important;
}

/* ========== Buttons ========== */
.btn-outline-primary,
.btn-outline-success {
  border-color: #d70c0c;
  color: #d70c0c;
}

.btn-outline-primary:hover,
.btn-outline-success:hover {
  background-color: #d70c0c;
  color: #fff;
}

/* ========== Modal ========== */
.modal-content {
  background: #fff;
  color: #333;
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

.btn-outline-edit {
    border: 1px solid #fd7e14; /* Orange */
    color: #fd7e14;
    background-color: rgba(253, 126, 20, 0.1);
    transition: all 0.3s;
}

.btn-outline-edit:hover {
    background-color: rgba(253, 126, 20, 0.1);
}


.btn-outline-submit {
    border: 1px solid #198754; /* Bootstrap Success Green */
    color: #198754;
    background-color: rgba(25, 135, 84, 0.1);
    transition: all 0.3s;
}

.btn-outline-submit:hover {
    background-color: rgba(25, 135, 84, 0.1);
}
.contract-file-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 4px 0;
    color: #6c757d;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.2s;
}

.contract-file-link:hover {
    color: #212529;
    text-decoration: underline;
}

.contract-file-link i {
    color: #dc3545;
    font-size: 1.2rem;
}
  /* Custom 5-column layout */
  @media (min-width: 768px) {
    .col-md-5th {
      flex: 0 0 20%;
      max-width: 20%;
    }
  }
 /* 🔴 Highlight empty required fields */
 .is-invalid {
    border: 2px solid #dc3545 !important; /* Red border */
    box-shadow: 0 0 5px rgba(220, 53, 69, 0.5);
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
  }

   /* Fix table header when scrolling */
   .table-responsive thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #ffcccc; /* same as header color */
  }

  /* Optional: smooth scrolling and nice look */
  .table-responsive {
    scrollbar-width: thin;
    scrollbar-color: #dc3545 #f8f9fa;
  }

  .table-responsive::-webkit-scrollbar {
    width: 8px;
  }
  .table-responsive::-webkit-scrollbar-thumb {
    background-color: #dc3545;
    border-radius: 4px;
  }
    /* Reusable class for disabled buttons */
    .disabled-btn {
    border: 1px solid lightgray !important;
    background-color: transparent !important;
    color: gray !important;
    cursor: not-allowed !important;
    opacity: 0.6 !important;
  }
  .status-badge {
    border: 1px solid;
    background-color: transparent !important;
    display: inline-flex;
    align-items: center;
    gap: 6px;
  }
  </style>
</head>

<body>
<?php include('navbar.php'); ?>

<div id="mainContent">
  <button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
    <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
      <span class="fw-normal">Menu</span>
  </button>
  <div class="container py-1">
  <div class="mb-3 text-center">
        <h4 class="fw-semibold text-dark">
          <i class="bi bi-check-circle text-danger me-2"></i>SELECT BRANCH TO MAKE RFP
        </h4>
      </div>
<?php if ($userRole !== 'Auditor' && $userRole !== 'Finance'): ?>
  <!-- All Requests Table -->
  <div class="card border-1 rounded-4 mt-4">
  <div class="card-body">
    <h5 class="mb-2 fw-normal" style="color: #d70c0c;">
      <i class="bi bi-table me-2"></i>READY FOR RFP BRANCHES
    </h5>
    <div class="card mb-3 border-0 shadow-sm">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="small fw-bold">Mainzone</label>
                <select name="f_mainzone" class="form-select form-select-sm">
                    <option value="">-- All Mainzones --</option>
                    <?php foreach ($filterOptions['mainzone'] as $mz): ?>
                        <option value="<?= htmlspecialchars($mz) ?>" <?= ($filterMainzone == $mz) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($mz) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="small fw-bold">Region</label>
                <select name="f_region" class="form-select form-select-sm">
                    <option value="">-- All Regions --</option>
                    <?php foreach ($filterOptions['region'] as $rg): ?>
                        <option value="<?= htmlspecialchars($rg) ?>" <?= ($filterRegion == $rg) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rg) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="small fw-bold">Area</label>
                <select name="f_area" class="form-select form-select-sm">
                    <option value="">-- All Areas --</option>
                    <?php foreach ($filterOptions['area'] as $ar): ?>
                        <option value="<?= htmlspecialchars($ar) ?>" <?= ($filterArea == $ar) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ar) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-danger flex-grow-1">
                    <i class="bi bi-filter me-1"></i> Filter
                </button>
                <a href="?" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 table-sm text-nowrap">
      <thead class="bg-light text-center">
        <tr class="align-middle">
          <th class="fw-normal"><i class="bi bi-hash text-danger"></i></th>
          <th class="fw-normal"><i class="bi bi-gear-wide-connected me-1 text-danger"></i>Actions</th>
          <th class="fw-normal"><i class="bi bi-file-earmark-pdf me-1 text-danger"></i>Contract File</th>
          <th class="fw-normal"><i class="bi bi-file-text me-1 text-danger"></i>COL #</th>
          <th class="fw-normal"><i class="bi bi-calendar-date me-1 text-danger"></i>Effectivity Date</th>
          <th class="fw-normal"><i class="bi bi-calendar-x me-1 text-danger"></i>Expiry Date</th>
          <th class="fw-normal"><i class="bi bi-calendar-date me-1 text-danger"></i>Payment Due Date</th>
          <th class="fw-normal"><i class="bi bi-building me-1 text-danger"></i>Branch</th>
          <th class="fw-normal"><i class="bi bi-person-badge me-1 text-danger"></i>Lessor Type</th>
          <th class="fw-normal"><i class="bi bi-file-earmark-text me-1 text-danger"></i>Mode of Payment</th>
          <th class="fw-normal"><i class="bi bi-list-ul me-1 text-danger"></i>Category</th>
          <th class="fw-normal"><i class="bi bi-info-circle me-1 text-danger"></i>Status</th>
          <th class="fw-normal"><i class="bi bi-person me-1 text-danger"></i>RFP Requested By</th>
          <th class="fw-normal"><i class="bi bi-calendar-date me-1 text-danger"></i>RFP Requested Date</th>
        </tr>
      </thead>

      <tbody class="text-center">
      <?php if (!$isFiltered): ?>
        <tr>
          <td colspan="14" class="text-muted py-5">
            <i class="bi bi-filter-left text-danger fs-2 d-block mb-2"></i>
            <strong>Please use the filter above to display results.</strong>
          </td>
        </tr>
      <?php elseif (empty($createdContracts)): ?>
        <tr>
          <td colspan="14" class="text-muted py-4">
            <i class="bi bi-exclamation-circle-fill text-danger me-2 fs-5"></i>
            <strong>No transaction found for this filter.</strong>
          </td>
        </tr>
      <?php else: ?>
          <?php $index = 1; ?>
          <?php foreach ($createdContracts as $contract): ?>
            <tr>
              <td><?= $index++; ?></td>
              <td>
                <div class="d-flex justify-content-center gap-2 fs-6">
                <?php
    $rfpStatus = $contract['rfp_status'];
    $requestStatus = $contract['request_status'];
    $contractId = $contract['id'];
?>

<button class="btn btn-sm btn-outline-view rounded-pill px-3 d-flex align-items-center view-btn"
        data-id="<?= $contractId; ?>" 
        data-bs-toggle="modal" 
        data-bs-target="#viewContractModal"
        title="Preview">
    <i class="bi bi-eye-fill me-1"></i>
</button>

<?php if ($userRole === 'Am-Creator'): ?>
    <a href="edit_contract.php?id=<?= $contractId; ?>" 
       class="btn btn-sm btn-outline-edit rounded-pill px-3 d-flex align-items-center"
       title="Edit">
        <i class="bi bi-pencil-fill me-1"></i>
    </a>
<?php endif; ?>

<?php if ($rfpStatus === 'Reviewed' && ($requestStatus === 'Ready' || $requestStatus === 'Approved')): ?>
    <a href="rfp_page.php?id=<?= $contractId; ?>&branch_id=<?= urlencode($contract['branch_id']); ?>&branch=<?= urlencode($contract['branch']); ?>&contract_number=<?= urlencode($contract['contract_number']); ?>"
       class="btn btn-sm btn-danger rounded-pill px-3 d-flex align-items-center"
       title="<?= ($userRole === 'Am-Creator') ? 'Make RFP' : 'Next RFP'; ?>">
        <i class="bi bi-card-checklist me-1"></i> 
        <?= ($userRole === 'Am-Creator') ? 'Make RFP' : 'Next RFP'; ?>
    </a>
<?php endif; ?>

<?php if (
    $userRole === 'Vpo-Checker' && 
    ($contract['mode_of_payment'] ?? '') === 'PDC' && 
    empty($contract['rfp_number'])
): ?>
    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 d-flex align-items-center"
            data-contract-number="<?= htmlspecialchars($contract['contract_number']); ?>"
            data-bs-toggle="modal"
            data-bs-target="#addRfpModal"
            title="Add RFP Number">
        <i class="bi bi-plus-circle me-1"></i> Add RFP
    </button>
<?php endif; ?>
                </div>
              </td>
              <!-- Add RFP Modal -->
              <div class="modal fade" id="addRfpModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                  <div class="modal-content">

                    <form method="POST" action="update_rfp_number.php">

                      <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title text-white">
                          <i class="bi bi-card-checklist me-2"></i>Add RFP Number
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                      </div>

                      <div class="modal-body">

                        <!-- Hidden Contract Number -->
                        <input type="hidden" name="contract_number" id="modal_contract_number">

                        <div class="mb-3">
                          <label class="form-label">RFP Number</label>
                          <input type="text"
                                name="rfp_number"
                                class="form-control"
                                placeholder="Enter RFP Number"
                                required>
                        </div>

                      </div>

                      <div class="modal-footer">
                        <button type="submit" class="btn btn-danger">
                          <i class="bi bi-save me-1"></i> Save
                        </button>
                      </div>

                    </form>

                  </div>
                </div>
              </div>
              <td>
    <?php
    $fileLinks = [];
    // Existing contract files & attachments
    for ($i = 0; $i <= 16; $i++) {
        $filenameCol = '';
        if ($i === 0) { $filenameCol = 'contractFilename'; }
        elseif ($i <= 5) { $filenameCol = "contractFilename$i"; }
        elseif ($i === 16) { $filenameCol = "contractFilename16"; }
        else { $filenameCol = "attachment_{$i}_filename"; }

        // Note: For preview_contract_file.php to work with your previous mapping, 
        // we pass the column name as the 'file' parameter.
        if (!empty($contract[$filenameCol])) {
            $fileLinks[] = [
                'column' => $filenameCol,
                'filename' => $contract[$filenameCol]
            ];
        }
    }
    $fileCount = count($fileLinks);
    ?>

    <?php if ($fileCount > 0): ?>
        <?php if ($fileCount === 1): ?>
            <?php $file = $fileLinks[0]; ?>
            <a href="javascript:void(0)" 
               onclick="viewPDF(<?= $contract['id']; ?>, '<?= $file['column']; ?>', '<?= htmlspecialchars($file['filename']); ?>')" 
               class="text-decoration-none fw-medium">
                <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
                <span class="ms-1">1 file</span>
            </a>
        <?php else: ?>
            <a href="#" data-bs-toggle="modal" data-bs-target="#contractFilesModal<?= $contract['id']; ?>" class="text-decoration-none">
                <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
                <span class="ms-1"><?= $fileCount ?> files</span>
            </a>

            <div class="modal fade" id="contractFilesModal<?= $contract['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title">Select File to Preview</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($fileLinks as $file): ?>
                                    <button type="button" 
                                            class="list-group-item list-group-item-action" 
                                            onclick="viewPDF(<?= $contract['id']; ?>, '<?= $file['column']; ?>', '<?= htmlspecialchars($file['filename']); ?>')">
                                        <i class="bi bi-file-earmark-pdf text-danger me-2"></i>
                                        <?= htmlspecialchars($file['filename']) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <span class="text-muted fst-italic">No file</span>
    <?php endif; ?>
</td>
              <td><?= htmlspecialchars($contract['contract_number']); ?></td>
              <td>
                <?= !empty($contract['contract_start']) ? date('F d, Y', strtotime($contract['contract_start'])) : '—'; ?>
              </td>
              <td>
                <?= !empty($contract['contract_end']) ? date('F d, Y', strtotime($contract['contract_end'])) : '—'; ?>
              </td>
              <td>
              <?php
                if (!empty($contract['payment_due_date'])) {
                    $day = date('j', strtotime($contract['payment_due_date']));
                    $suffix = 'th';

                    if (!in_array(($day % 100), [11, 12, 13])) {
                        switch ($day % 10) {
                            case 1: $suffix = 'st'; break;
                            case 2: $suffix = 'nd'; break;
                            case 3: $suffix = 'rd'; break;
                        }
                    }

                    echo "Every {$day}{$suffix} day of the month";
                } else {
                    echo '—';
                }
              ?>
            </td>
              <td><?= !empty($contract['branch']) ? htmlspecialchars($contract['branch']) : '—'; ?></td>
              <td>
                <?= !empty($contract['lessor_type']) ? ($contract['lessor_type'] === 'Individual' ? 'Sole Proprietorship' : htmlspecialchars($contract['lessor_type'])) : '—'; ?>
              </td>
              <td><?= !empty($contract['mode_of_payment']) ? htmlspecialchars($contract['mode_of_payment']) : '—'; ?></td>
              <td>
                <span class="badge bg-info-subtle text-dark fw-semibold px-3 py-2 rounded-pill">
                  <i class="bi bi-clipboard-data me-1"></i>
                  <?php
                  $rfpStatus = $contract['rfp_status'];
                  $requestStatus = $contract['request_status'];
                  if (((is_null($rfpStatus) || $rfpStatus === '') && ($requestStatus === 'Prepared' || $requestStatus === 'Created')) || ($rfpStatus === 'Reviewed' && $requestStatus === 'Ready')) {
                      echo 'DATA ARCHIVING';
                  } elseif ($rfpStatus === 'Reviewed' && $requestStatus === 'Prepared' || $rfpStatus === 'Reviewed' && $requestStatus === 'Created' || $rfpStatus === 'Reviewed' && $requestStatus === 'Reviewed' || $rfpStatus === 'Reviewed' && $requestStatus === 'Checked' || $rfpStatus === 'Reviewed' && $requestStatus === 'Approved') {
                      echo 'RFP';
                  }
                  else {
                      echo htmlspecialchars($contract['category']);
                  }
                  ?>

                </span>
              </td>
              <td>
                <div class="badge bg-warning-subtle fw-semibold text-dark px-3 py-2 rounded-pill text-start">
                  <div class="d-flex flex-column lh-sm">
                    <span>
                    <i class="bi bi-hourglass-split me-1"></i>
                      <?php
                        $rfpStatus = $contract['rfp_status'];
                        $requestStatus = $contract['request_status'];
                        if (empty($rfpStatus) && $requestStatus === 'Prepared' || $rfpStatus === 'Reviewed' && $requestStatus === 'Prepared') {
                            echo "For Review By RM";
                        } elseif ($rfpStatus === 'Reviewed' && $requestStatus === 'Reviewed') {
                            echo "For Review By VPO";
                        }
                        elseif ($rfpStatus === 'Reviewed' && $requestStatus === 'Checked') {
                            echo "For Approval";
                        }
                        elseif ($rfpStatus === 'Reviewed' && $requestStatus === 'Ready' || $rfpStatus === 'Reviewed' && $requestStatus === 'Approved') {
                            echo "Ready for RFP";
                        } else {
                            echo htmlspecialchars($requestStatus);
                        }
                      ?>
                    </span>

                    <?php
                      $reviewerNote = $contract['reviewer_note'] ?? '';
                      $auditNote = $contract['audit_note'] ?? '';

                      if (!empty($reviewerNote) || !empty($auditNote)) {
                          echo '<small class="text-danger text-center" style="font-size: 0.5rem;">Returned</small>';
                      }
                    ?>
                  </div>
                </div>
              </td>
              <td>
                <?= !empty($contract['prepared_by']) ? htmlspecialchars($contract['prepared_by']) : '<span class="text-muted">N/A</span>'; ?>
              </td>

              <td>
                <?php if (!empty($contract['rfp_date'])): ?>
                    <i class="bi bi-calendar-event me-1 text-danger"></i>
                    <?= date('F d, Y', strtotime($contract['rfp_date'])); ?>
                <?php else: ?>
                    <span class="text-muted">N/A</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>
<?php endif; ?>
</div>
<!-- Modal -->
<div class="modal fade" id="viewContractModal" tabindex="-1" aria-labelledby="viewContractModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content" style="background-color: #fff; color: #333; border-radius: 12px; border: none; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);">
      
      <div class="modal-header" style="background-color: #d70c0c; border-top-left-radius: 12px; border-top-right-radius: 12px;">
        <h5 class="modal-title text-white" id="viewContractModalLabel">📄 Contract Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      
      <div class="modal-body p-4" id="contractDetailsContent">
        <div class="text-center py-5">
          <div class="spinner-border text-danger" role="status" style="width: 3rem; height: 3rem;"></div>
          <p class="mt-3 fw-semibold" style="color: #555;">Loading contract details...</p>
        </div>
      </div>
      
      <div class="modal-footer" style="border-top: 1px solid #eee;">
        <button type="button" class="btn btn-outline-dark rounded-pill px-4" data-bs-dismiss="modal" style="font-weight: 500;">
          Close
        </button>
      </div>

    </div>
  </div>
</div>

<div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content" style="height: 90vh; border-radius: 12px; overflow: hidden;">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title" id="pdfPreviewTitle">📄 PDF Preview</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <iframe id="pdfFrame" src="" width="100%" height="100%" frameborder="0" style="min-height: 80vh;"></iframe>
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
<!-- Scripts -->
<script src="../../assets/js/jquery-3.7.1.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
  $(document).ready(function () {
    $('#branch_id').on('change', function () {
        const branchId = $(this).val();
        const $contractSelect = $('#contract_number');

        // Reset dropdown
        $contractSelect.html('<option value="">-- Select Contract Number --</option>');

        if (branchId) {
            $.ajax({
                url: 'get_rfp_contracts.php',
                type: 'GET',
                data: { branch_id: branchId },
                dataType: 'json',
                success: function (contracts) {
                    // DEBUG: See what the server sent back
                    console.log("Contracts received:", contracts);

                    if (Array.isArray(contracts) && contracts.length > 0) {
                        contracts.forEach(contract => {
                            $contractSelect.append(
                                $('<option>', { 
                                    value: contract.contract_number, 
                                    text: contract.contract_number 
                                })
                            );
                        });
                    } else {
                        $contractSelect.html('<option value="">No Active Contracts</option>');
                    }
                },
                error: function (xhr, status, error) {
                    // DEBUG: See why it failed
                    console.error("Status: " + status);
                    console.error("Error: " + error);
                    console.error("Response Text: " + xhr.responseText);
                    
                    $contractSelect.html('<option value="">Error loading contracts</option>');
                }
            });
        }
    });


  /** -------------------------------
   * Toggle Sidebar
   * ------------------------------- */
  $('#toggleSidebar').on('click', function () {
    $('#sidebarMenu').toggleClass('collapsed');
  });

  /** -------------------------------
   * Logout Handler
   * ------------------------------- */
  $('#logoutLink').on('click', function (e) {
    e.preventDefault();
    const modal = new bootstrap.Modal(document.getElementById('logoutModal'), {
      backdrop: 'static',
      keyboard: false
    });
    modal.show();
    setTimeout(() => window.location.href = '../../logout.php', 2000);
  });

  /** -------------------------------
   * Payment Due Date Update
   * ------------------------------- */
  const $daySelect = $('#paymentDueDay');
  const $applyBtn  = $('#applyChangesBtn');
  const originalDay = $daySelect.val();

  if ($daySelect.length && $applyBtn.length) {
    $daySelect.on('change', function () {
      if ($(this).val() !== originalDay) {
        $applyBtn.removeClass('d-none');
      } else {
        $applyBtn.addClass('d-none');
      }
    });

    $applyBtn.on('click', function (e) {
  e.preventDefault();

  const newDay = $daySelect.val();

  $.ajax({
    url: 'update_due_date.php',
    type: 'POST',
    data: {
      payment_due_day: newDay,
      apply_changes: true,
      contract_id: <?= json_encode($contract['id'] ?? 1) ?>,
      contract_number: <?= json_encode($contract['contract_number'] ?? '') ?>
    },
    success: function (responseHTML) {
      $('#update-message-container').html(responseHTML);
      $applyBtn.addClass('d-none');

      $('#currentDueDateText').html(`Every ${newDay}${ordinalSuffix(newDay)} day`);
    }
  });
});

  }

  // Helper for ordinal suffix (st, nd, rd, th)
  function ordinalSuffix(n) {
    if (n >= 11 && n <= 13) return 'th';
    switch (n % 10) {
      case 1: return 'st';
      case 2: return 'nd';
      case 3: return 'rd';
      default: return 'th';
    }
  }
});
document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("viewContractModal");
  const content = document.getElementById("contractDetailsContent");

  document.querySelectorAll(".view-btn").forEach(btn => {
    btn.addEventListener("click", () => {
      const id = btn.getAttribute("data-id");

      // Show loading state
      content.innerHTML = `
        <div class="text-center">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="mt-2">Loading contract details...</p>
        </div>
      `;

      // Fetch contract details
      fetch(`fetch_contract_details.php?id=${id}`)
        .then(res => res.text())
        .then(html => {
          content.innerHTML = html;
        })
        .catch(err => {
          content.innerHTML = `<div class="alert alert-danger">Error loading contract details.</div>`;
        });
    });
  });
});
function viewPDF(id, fileCol, filename) {
    const pdfFrame = document.getElementById('pdfFrame');
    const pdfTitle = document.getElementById('pdfPreviewTitle');
    
    if (pdfFrame && pdfTitle) {
        // Set the title and source
        pdfTitle.innerText = 'Preview: ' + filename;
        pdfFrame.src = `preview_contract_file.php?id=${id}&file=${fileCol}`;
        
        // Hide selection modal if it was open
        const openModal = document.querySelector('.modal.show');
        if (openModal && openModal.id !== 'pdfPreviewModal') {
            const bsModal = bootstrap.Modal.getInstance(openModal);
            if (bsModal) bsModal.hide();
        }

        // Show the Preview Modal
        const previewModal = new bootstrap.Modal(document.getElementById('pdfPreviewModal'));
        previewModal.show();
    }
}

// Handle Modal Close Event
document.addEventListener('DOMContentLoaded', () => {
    const pdfPreviewModal = document.getElementById('pdfPreviewModal');
    if (pdfPreviewModal) {
        pdfPreviewModal.addEventListener('hidden.bs.modal', function () {
            // Clear iframe to stop loading
            document.getElementById('pdfFrame').src = '';
            // Refresh the page
            window.location.reload();
        });
    }
});
</script>

</body>
</html>
