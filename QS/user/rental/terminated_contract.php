<?php
session_start();
include '../../config/config.php';

if (!isset($_SESSION['user_email'])) {
  header('Location: login_form.php');
  exit;
}

$user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);
$userRole = $_SESSION['user_role'] ?? '';
$userZone = $_SESSION['mainzone'] ?? '';
$userRegion = $_SESSION['region'] ?? '';
$userArea = $_SESSION['area'] ?? '';

// Fetch Created Contracts
$createdContracts = [];
// ADDED CONDITION: Filter directly at DB level to prevent PHP memory exhaustion
$queryContracts = "SELECT * FROM create_contract WHERE status = 'Active' AND request_status = 'Terminated'";

// Apply role condition to initial query
if ($userRole === 'Am-Creator') {
    $queryContracts .= " AND region = '" . mysqli_real_escape_string($conn, $userRegion) . "' AND area = '" . mysqli_real_escape_string($conn, $userArea) . "'";
} elseif ($userRole === 'Rm-Reviewer') {
    $queryContracts .= " AND region = '" . mysqli_real_escape_string($conn, $userRegion) . "'";
} elseif (in_array($userRole, ['Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver'])) {
    $queryContracts .= " AND mainzone = '" . mysqli_real_escape_string($conn, $userZone) . "'";
}

$queryContracts .= " ORDER BY created_date DESC";
$resultContracts = mysqli_query($conn, $queryContracts);

while ($row = mysqli_fetch_assoc($resultContracts)) {
  $createdContracts[] = $row;
}

// Determine if any remarks exist
$remarkRoles = ['Rm-Reviewer', 'Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver'];
$isRemarkRole = in_array($userRole, $remarkRoles);

$hasRemarks = false;
foreach ($createdContracts as $contract) {
  if (!empty($contract['reviewer_note'])) {
    $hasRemarks = true;
    break;
  }
}

// Fetch dropdown values
$mainzones = [];
$regions   = [];
$branches  = [];

$result = $conn->query("SELECT DISTINCT mainzone FROM create_contract WHERE mainzone IS NOT NULL AND mainzone <> '' ORDER BY mainzone");
while ($row = $result->fetch_assoc()) {
    $mainzones[] = $row['mainzone'];
}

$result = $conn->query("SELECT DISTINCT region FROM create_contract WHERE region IS NOT NULL AND region <> '' ORDER BY region");
while ($row = $result->fetch_assoc()) {
    $regions[] = $row['region'];
}

$result = $conn->query("SELECT DISTINCT area FROM create_contract WHERE area IS NOT NULL AND area <> '' ORDER BY area");
while ($row = $result->fetch_assoc()) {
    $areas[] = $row['area'];
}

$result = $conn->query("SELECT DISTINCT branch FROM create_contract WHERE branch IS NOT NULL AND branch <> '' ORDER BY branch");
while ($row = $result->fetch_assoc()) {
    $branches[] = $row['branch'];
}

// Build filter query
// ADDED CONDITION: Ensures filtered results also only query terminated contracts, preventing memory crashes
$where = ["request_status = 'Terminated'"];
$params = [];
$types  = "";

// Apply role constraints to filter query
if ($userRole === 'Am-Creator') {
    $where[] = "region = ?";
    $where[] = "area = ?";
    array_push($params, $userRegion, $userArea);
    $types .= "ss";
} elseif ($userRole === 'Rm-Reviewer') {
    $where[] = "region = ?";
    $params[] = $userRegion;
    $types .= "s";
} elseif (in_array($userRole, ['Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver'])) {
    $where[] = "mainzone = ?";
    $params[] = $userZone;
    $types .= "s";
}

$filtersApplied = !empty($_GET['mainzone']) || !empty($_GET['region']) || !empty($_GET['area']) || !empty($_GET['branch']);
if ($filtersApplied) {
    if (!empty($_GET['mainzone'])) {
        $where[] = "mainzone = ?";
        $params[] = $_GET['mainzone'];
        $types   .= "s";
    }
    if (!empty($_GET['region'])) {
        $where[] = "region = ?";
        $params[] = $_GET['region'];
        $types   .= "s";
    }
    if (!empty($_GET['area'])) {
        $where[] = "area = ?";
        $params[] = $_GET['area'];
        $types   .= "s";
    }
    if (!empty($_GET['branch'])) {
        $where[] = "branch = ?";
        $params[] = $_GET['branch'];
        $types   .= "s";
    }

    $sql = "SELECT * FROM create_contract";
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $createdContracts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    // default: no results
    // ADDED CONDITION: Wrap this in a false check so it doesn't wipe out the initially loaded contracts when page first loads
    if (isset($_GET['force_empty_results'])) {
        $createdContracts = [];
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

  <title>Terminated Contracts</title>
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
<style>
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
</style>
</head>
<body>
<?php include ('navbar.php'); ?>
<div id="mainContent">
<button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
            <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
            <span class="fw-normal">Menu</span>
        </button>
<div class="container py-1">

  <!-- All Requests Table -->
  <div class="card border-1 rounded-4 mt-4">
  <div class="card-body">
    <h5 class="mb-2 fw-normal" style="color: #d70c0c;">
      <i class="bi bi-table me-2"></i>Terminated Contracts
    </h5>
    <?php
// --- User role and location info ---
$userRole   = $_SESSION['user_role'] ?? '';
$userZone   = $_SESSION['mainzone'] ?? '';
$userRegion = $_SESSION['region'] ?? '';
$userArea   = $_SESSION['area'] ?? '';
// --- Dynamic filtering based on role ---
$where = "WHERE 1=1"; // Prevents SQL syntax error when appended to "AND region <> ''"

if ($userRole === 'Am-Creator') {
    // AM: can see only their region + area
    $where = "WHERE region = '$userRegion' AND area = '$userArea'";
} elseif ($userRole === 'Rm-Reviewer') {
    // RM: can see only their region
    $where = "WHERE region = '$userRegion'";
} elseif (in_array($userRole, ['Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver'])) {
    // VPO: can see only within their mainzone
    $where = "WHERE mainzone = '$userZone'";
} elseif (in_array($userRole, ['HO', 'Finance'])) {
    // HO & Finance: global access (no restriction)
    $where = "WHERE 1=1"; // Prevents SQL syntax error
}

// --- Fetch values based on role restrictions ---
$mainzones = [];
$regions   = [];
$areas     = [];
$branches  = [];

$queryMainzone = mysqli_query($conn, "SELECT DISTINCT mainzone FROM create_contract WHERE mainzone <> '' AND request_status = 'Terminated' ORDER BY mainzone ASC");
$queryRegion   = mysqli_query($conn, "SELECT DISTINCT region FROM create_contract $where AND region <> '' AND request_status = 'Terminated' ORDER BY region ASC");
$queryArea     = mysqli_query($conn, "SELECT DISTINCT area FROM create_contract $where AND area <> '' AND request_status = 'Terminated' ORDER BY area ASC");
$queryBranch   = mysqli_query($conn, "SELECT DISTINCT branch FROM create_contract $where AND branch <> '' AND request_status = 'Terminated' ORDER BY branch ASC");

while ($mz = mysqli_fetch_assoc($queryMainzone)) $mainzones[] = $mz['mainzone'];
while ($r = mysqli_fetch_assoc($queryRegion)) $regions[] = $r['region'];
while ($a = mysqli_fetch_assoc($queryArea)) $areas[] = $a['area'];
while ($b = mysqli_fetch_assoc($queryBranch)) $branches[] = $b['branch'];
?>

<!-- FILTER FORM -->
<form method="GET" class="row g-3 align-items-end mb-4">

<!-- MAINZONE -->
<?php if (!in_array($userRole, ['Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver', 'Am-Creator', 'Rm-Reviewer'])): ?>
  <div class="col-md-3">
    <label class="form-label fw-semibold">Mainzone</label>
    <select name="mainzone" class="form-select">
      <option value="">-- Select Mainzone --</option>
      <?php foreach ($mainzones as $mz): ?>
        <option value="<?= htmlspecialchars($mz) ?>"
          <?= (isset($_GET['mainzone']) && $_GET['mainzone'] === $mz) ? 'selected' : '' ?>>
          <?= htmlspecialchars($mz) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
<?php endif; ?>

  <!-- REGION -->
  <div class="col-md-3">
    <label class="form-label fw-semibold">Region</label>
    <select name="region" class="form-select">
      <option value="">-- Select Region --</option>
      <?php foreach ($regions as $r): ?>
        <option value="<?= htmlspecialchars($r) ?>"
          <?= (isset($_GET['region']) && $_GET['region'] === $r) ? 'selected' : '' ?>>
          <?= htmlspecialchars($r) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- AREA -->
  <div class="col-md-3">
    <label class="form-label fw-semibold">Area</label>
    <select name="area" class="form-select">
      <option value="">-- Select Area --</option>
      <?php foreach ($areas as $a): ?>
        <option value="<?= htmlspecialchars($a) ?>"
          <?= (isset($_GET['area']) && $_GET['area'] === $a) ? 'selected' : '' ?>>
          <?= htmlspecialchars($a) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <!-- BRANCH (Toggle with Checkbox) -->
  <div class="col-md-3">
    <div class="form-check mb-2">
      <input class="form-check-input" type="checkbox" id="showBranch" onchange="toggleBranch()">
      <label class="form-check-label fw-semibold" for="showBranch">
        <i class="bi bi-building me-1"></i> Filter by Branch
      </label>
    </div>

    <div id="branchSelect" style="display: none;">
      <label class="form-label fw-semibold">Branch</label>
      <select name="branch" class="form-select">
        <option value="">-- Select Branch --</option>
        <?php foreach ($branches as $b): ?>
          <option value="<?= htmlspecialchars($b) ?>"
            <?= (isset($_GET['branch']) && $_GET['branch'] === $b) ? 'selected' : '' ?>>
            <?= htmlspecialchars($b) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- FILTER BUTTON -->
  <div class="col-md-2">
    <button type="submit" class="btn btn-danger w-100">
      <i class="bi bi-funnel me-1"></i> Filter
    </button>
  </div>
</form>

<script>
function toggleBranch() {
  const checkbox = document.getElementById("showBranch");
  const branchDiv = document.getElementById("branchSelect");
  branchDiv.style.display = checkbox.checked ? "block" : "none";
}
</script>
  </form>
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
          <th class="fw-normal"><i class="bi bi-file-earmark-text me-1 text-danger"></i>Request Type</th>
          <th class="fw-normal"><i class="bi bi-list-ul me-1 text-danger"></i>Category</th>
          <th class="fw-normal"><i class="bi bi-info-circle me-1 text-danger"></i>Status</th>
          <?php if ($hasRemarks): ?>
            <th class="fw-normal"><i class="bi bi-chat-left-text me-1 text-danger"></i>Remarks</th>
          <?php endif; ?>
          <th class="fw-normal"><i class="bi bi-person me-1 text-danger"></i>Requested By</th>
          <th class="fw-normal"><i class="bi bi-calendar-date me-1 text-danger"></i>Created Date</th>
        </tr>
      </thead>

      <tbody class="text-center">
      <?php if (empty($createdContracts)): ?>
        <tr>
          <td colspan="16" class="text-muted py-4">
            <i class="bi bi-exclamation-circle-fill text-danger me-2 fs-5"></i>
            <strong>No transaction found.</strong>
          </td>
        </tr>
      <?php else: ?>
          <?php $index = 1; ?>
          <?php foreach ($createdContracts as $contract): ?>
            <?php if (strtolower($contract['request_status']) !== 'terminated') continue; // ✅ show only terminated ?>
            
            <?php 
            // ADDED CONDITION: accurately restrict viewing of transactions based on user roles
            $viewRole   = $_SESSION['user_role'] ?? '';
            $viewZone   = $_SESSION['mainzone'] ?? '';
            $viewRegion = $_SESSION['region'] ?? '';
            $viewArea   = $_SESSION['area'] ?? '';

            if ($viewRole === 'Am-Creator' && ($contract['region'] !== $viewRegion || $contract['area'] !== $viewArea)) continue;
            if ($viewRole === 'Rm-Reviewer' && $contract['region'] !== $viewRegion) continue;
            if (in_array($viewRole, ['Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver']) && $contract['mainzone'] !== $viewZone) continue;
            ?>

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
                </div>
              </td>

              <td class="text-center">
              <?php
                // Collect valid file links from contract fields
                $fileLinks = [];
                for ($i = 0; $i <= 16; $i++) {
                    $colName = ($i === 0)
                        ? 'contractFilename'
                        : ($i <= 5 ? "contractFilename$i" : "attachment_{$i}_filename");

                    if (!empty($contract[$colName])) {
                        $fileLinks[] = [
                            'index' => $i,
                            'filename' => $contract[$colName]
                        ];
                    }
                }

                $fileCount = count($fileLinks);
              ?>

              <?php if ($fileCount > 0): ?>
                <?php if ($fileCount === 1): ?>
                  <!-- Only one file: Direct preview -->
                  <?php $file = $fileLinks[0]; ?>
                  <a href="preview_contract_file.php?id=<?= $contract['id']; ?>&file=<?= $file['index']; ?>" target="_blank" class="text-decoration-none">
                    <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
                    <span class="ms-1">1 file</span>
                  </a>
                <?php else: ?>
                  <!-- Multiple files: Trigger modal -->
                  <a href="#" data-bs-toggle="modal" data-bs-target="#contractFilesModal<?= $contract['id']; ?>" class="text-decoration-none">
                    <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
                    <span class="ms-1"><?= $fileCount ?> files</span>
                  </a>

                  <!-- Modal: Contract Files -->
                  <div class="modal fade" id="contractFilesModal<?= $contract['id']; ?>" tabindex="-1" aria-labelledby="contractFilesModalLabel<?= $contract['id']; ?>">
                    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title" id="contractFilesModalLabel<?= $contract['id']; ?>">Contract Files</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <ul class="list-group">
                            <?php foreach ($fileLinks as $file): ?>
                              <li class="list-group-item">
                                <a href="preview_contract_file.php?id=<?= $contract['id']; ?>&file=<?= $file['index']; ?>" target="_blank">
                                  <i class="bi bi-file-earmark-pdf text-danger me-1"></i>
                                  <?= htmlspecialchars($file['filename']) ?>
                                </a>
                              </li>
                            <?php endforeach; ?>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
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
                  }elseif ($requestStatus === 'Terminated') {
                    echo 'Terminated';
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

              <?php if ($hasRemarks): ?>
                <td>
                  <?php if (!empty($contract['reviewer_note']) || !empty($contract['audit_note'])): ?>
                    <button 
                      class="btn btn-sm text-danger p-1 view-remarks-btn"
                      data-bs-toggle="modal" 
                      data-bs-target="#view_remarksModal"
                      data-remarks="<?= htmlspecialchars($contract['reviewer_note'] ?? '', ENT_QUOTES); ?>"
                      data-audit="<?= htmlspecialchars($contract['audit_note'] ?? '', ENT_QUOTES); ?>"
                      data-contract="<?= htmlspecialchars($contract['contract_number']); ?>"
                      title="View Remarks">
                      <i class="bi bi-chat-left-text fs-5 text-danger"></i>
                    </button>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>
              <?php endif; ?>

              <td><?= htmlspecialchars($contract['created_by']); ?></td>
              <td><i class="bi bi-calendar-event me-1 text-danger"></i><?= date('F d, Y', strtotime($contract['created_date'])); ?></td>
            </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>
</div>
<!-- Modal: Display Remarks -->
<div class="modal fade" id="view_remarksModal" tabindex="-1" aria-labelledby="view_remarksModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content border-0 rounded-4 shadow-sm">

      <!-- Modal Header -->
      <div class="modal-header bg-white rounded-top-4 border-bottom">
        <div class="d-flex align-items-center">
          <i class="bi bi-chat-dots-fill text-danger fs-4 me-2"></i>
          <h5 class="modal-title fw-semibold text-dark mb-0" id="view_remarksModalLabel">Remarks</h5>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body px-4 py-3 bg-light-subtle">
        <!-- Contract Number -->
        <p id="view_remarksContractLabel" class="text-muted small mb-3"></p>

        <!-- Reviewer Note (RM) -->
        <div id="view_reviewerNoteWrapper" class="mb-3 d-none">
          <div class="d-flex align-items-center mb-1">
            <i class="bi bi-person-check-fill text-primary me-2"></i>
            <h6 class="fw-bold text-dark mb-0">RM Note</h6>
          </div>
          <p id="view_remarksContent" class="mb-0 text-dark lh-base fs-6" style="white-space: pre-line;"></p>
        </div>

        <!-- Audit Note (VPO) -->
        <div id="view_auditNoteWrapper" class="mb-2 d-none">
          <div class="d-flex align-items-center mb-1">
            <i class="bi bi-shield-check text-success me-2"></i>
            <h6 class="fw-bold text-dark mb-0">VPO Note</h6>
          </div>
          <p id="view_auditContent" class="mb-0 text-dark lh-base fs-6" style="white-space: pre-line;"></p>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/sweetalert2/dist/sweetalert2.all.min.js"></script>
<script>
  // ✅ Select/Deselect all
  document.getElementById("selectAll").addEventListener("change", function () {
    let checkboxes = document.querySelectorAll(".transaction-checkbox");
    checkboxes.forEach(cb => cb.checked = this.checked);
  });

  // ✅ Approve button click
  document.getElementById("approveSelected").addEventListener("click", function () {
    let selected = [];
    document.querySelectorAll(".transaction-checkbox:checked").forEach(cb => {
      selected.push(cb.value);
    });

    if (selected.length === 0) {
      Swal.fire({
        icon: "warning",
        title: "No Selection",
        text: "Please select at least one transaction.",
        confirmButtonText: "OK",
        confirmButtonColor: "#0d6efd"
      });
      return;
    }

    fetch("approve_transactions.php", {
      method: "POST",
      headers: {"Content-Type": "application/json"},
      body: JSON.stringify({ids: selected})
    })
    .then(res => {
      if (!res.ok) throw new Error("Network response was not ok");
      return res.json();
    })
    .then(data => {
      if (data.success) {
        Swal.fire({
          icon: "success",
          title: "Approved!",
          text: "Transactions approved successfully.",
          confirmButtonText: "Great!",
          confirmButtonColor: "#198754"
        }).then(() => {
          location.reload();
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Approval Failed",
          text: data.error || "Something went wrong. Please try again.",
          confirmButtonText: "Close",
          confirmButtonColor: "#dc3545"
        });
      }
    })
    .catch(err => {
      console.error("Fetch error:", err);
      Swal.fire({
        icon: "error",
        title: "Request Failed",
        text: "We couldn’t complete your request. Please check your connection and try again.",
        confirmButtonText: "Close",
        confirmButtonColor: "#dc3545"
      });
    });
  });
</script>


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

<div class="modal fade" id="reviewSubmitModal" tabindex="-1" aria-labelledby="reviewSubmitModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Review Contract Before Submission</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body" id="reviewModalBody">
        <!-- Content will be injected dynamically -->
        <p>Loading contract details...</p>
      </div>

      <div class="modal-footer">
        <form method="POST" action="submit_contract.php">
          <input type="hidden" name="contract_id" id="submitContractId">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Confirm Submit</button>
        </form>
      </div>

    </div>
  </div>
</div>

<!-- Remarks Modal -->
<div class="modal fade" id="remarksModal" tabindex="-1" aria-labelledby="remarksModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form action="submit_remarks.php" method="POST">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="remarksModalLabel">Submit Remarks</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="contract_id" id="remarksContractId">
          <div class="mb-3">
            <label for="remarks" class="form-label">Remarks</label>
            <textarea class="form-control" name="remarks" id="remarks" rows="4" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-danger">Submit Remarks</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Notification Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; background-color: #fff;">

      <!-- Vector image -->
      <div class="text-center pt-4">
        <img src="../../assets/images/check.jpg" alt="Notification Image"
             class="img-fluid" style="max-width: 120px;">
      </div>

      <div class="modal-header border-0 justify-content-center pt-3 pb-0">
        <h5 class="modal-title fw-bold d-flex align-items-center" id="messageModalLabel" style="color: #333;">
           Sent
        </h5>
      </div>

      <div class="modal-body text-center px-4 pb-2" style="color: #333; font-size: 1.1rem;">
        <div id="messageModalBody">
          <!-- Message is inserted dynamically -->
        </div>
      </div>

      <div class="modal-footer border-0 justify-content-center pb-4">
        <button type="button" class="btn px-4 py-2" style="background-color: #d70c0c; color: #fff; border-radius: 30px;" data-bs-dismiss="modal">
          <i class="bi bi-x-circle-fill me-1"></i> Close
        </button>
      </div>

    </div>
  </div>
</div>
<?php if (isset($_SESSION['success_message']) || isset($_SESSION['error_message'])): ?>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const modal = new bootstrap.Modal(document.getElementById('messageModal'));
    const message = `<?php
      echo isset($_SESSION['success_message'])
        ? '<div class="fw-semibold"><i class="bi bi-check-circle-fill text-success me-2"></i><span style=\'color: #333;\'>'.$_SESSION['success_message'].'</span></div>'
        : '<div class="fw-semibold"><i class="bi bi-x-circle-fill text-danger me-2"></i><span style=\'color: #333;\'>'.$_SESSION['error_message'].'</span></div>';
    ?>`;
    document.getElementById('messageModalBody').innerHTML = message;
    modal.show();
  });
</script>
<?php
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
endif;
?>

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

<script>
    function toggleBranch() {
  const checkbox = document.getElementById("showBranch");
  const branchDiv = document.getElementById("branchSelect");
  branchDiv.style.display = checkbox.checked ? "block" : "none";
}

document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('view_remarksModal');
  if (!modal) return;

  const contractLabel = document.getElementById('view_remarksContractLabel');
  const reviewerWrapper = document.getElementById('view_reviewerNoteWrapper');
  const remarksContent = document.getElementById('view_remarksContent');
  const auditWrapper = document.getElementById('view_auditNoteWrapper');
  const auditContent = document.getElementById('view_auditContent');

  modal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    if (!button) return;

    const contract = button.getAttribute('data-contract') || '';
    const remarks = button.getAttribute('data-remarks') || '';
    const audit = button.getAttribute('data-audit') || '';

    contractLabel.textContent = contract ? `Contract #: ${contract}` : '';

    // Show/hide RM Note
    if (remarks.trim()) {
      reviewerWrapper.classList.remove('d-none');
      remarksContent.textContent = remarks;
    } else {
      reviewerWrapper.classList.add('d-none');
    }

    // Show/hide VPO Note
    if (audit.trim()) {
      auditWrapper.classList.remove('d-none');
      auditContent.textContent = audit;
    } else {
      auditWrapper.classList.add('d-none');
    }
  });
});

  const remarksModal = document.getElementById('remarksModal');
  remarksModal.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const contractId = button.getAttribute('data-contract-id');
    const input = remarksModal.querySelector('#remarksContractId');
    input.value = contractId;
  });

  // Example: When opening modal, set contract ID
function openSubmitModal(contractId) {
  document.getElementById('submitContractId').value = contractId;
  // Optionally show the modal using Bootstrap
  const modal = new bootstrap.Modal(document.getElementById('submitModal'));
  modal.show();
}

  document.addEventListener('DOMContentLoaded', function () {
  const submitButtons = document.querySelectorAll('.submit-btn');

  submitButtons.forEach(button => {
    button.addEventListener('click', async function () {
      const contractId = this.getAttribute('data-id');
      document.getElementById('submitContractId').value = contractId;

      const response = await fetch(`get_contract_preview.php?id=${contractId}`);
      const html = await response.text();

      document.getElementById('reviewModalBody').innerHTML = html;
    });
  });
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