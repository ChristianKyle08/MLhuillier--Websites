<?php
ob_start(); // 1. Trap any accidental output from included files
session_start(); // 2. Start session BEFORE including config
require '../../config/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contract Transactions</title>
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
<body class="bg-light">
<?php include ('navbar.php'); ?>
<div id="mainContent">
<button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
            <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
            <span class="fw-normal">Menu</span>
        </button>
<div class="container py-1">
  <h2 class="mb-2">View Contracts</h2>
  <?php
// ============================
// 🧭 Session Variables
// ============================
$userName   = $_SESSION['user_email']  ?? '';
$userRole   = $_SESSION['user_role']   ?? '';
$userZone   = $_SESSION['mainzone']    ?? '';
$userRegion = $_SESSION['region']      ?? '';
$userArea   = $_SESSION['area']        ?? '';

// ============================
// 🧮 Filter Inputs (GET)
// ============================
$mainzone = trim($_GET['mainzone'] ?? '');
$region   = trim($_GET['region'] ?? '');
$area     = trim($_GET['area'] ?? '');

// ============================
// 📋 Fetch Datalist Options
// ============================
$mainzones = [];
$regions   = [];
$areas     = [];

$mainzoneQuery = $conn->query("SELECT DISTINCT mainzone FROM create_contract WHERE mainzone <> '' ORDER BY mainzone");
while ($r = $mainzoneQuery->fetch_assoc()) {
    $mainzones[] = $r['mainzone'];
}

$regionQuery = $conn->query("SELECT DISTINCT region FROM create_contract WHERE region <> '' ORDER BY region");
while ($r = $regionQuery->fetch_assoc()) {
    $regions[] = $r['region'];
}

$areaQuery = $conn->query("SELECT DISTINCT area FROM create_contract WHERE area <> '' ORDER BY area");
while ($r = $areaQuery->fetch_assoc()) {
    $areas[] = $r['area'];
}

// ============================
// ⚙️ Build Base Query
// ============================
$sql = "SELECT * FROM create_contract WHERE 1=1";
$conditions = [];
// Filter out VOID contracts
$conditions[] = "UPPER(TRIM(contract_number)) <> 'VOID'";

$params = [];
$types = "";

// ============================
// 🎯 Role-Based Restrictions
// ============================
switch ($userRole) {
    case 'Am-Creator':
        // Am-Creator always sees their own region + area data automatically
        $conditions[] = "region = ?";
        $params[] = $userRegion;
        $types .= "s";

        $conditions[] = "area = ?";
        $params[] = $userArea;
        $types .= "s";
        break;

    case 'Rm-Reviewer':
        // Restricted to their region
        $conditions[] = "region = ?";
        $params[] = $userRegion;
        $types .= "s";
        break;

    case 'Vpo-Checker':
    case 'Vpo-Reviewer':
    case 'Vpo-Approver':
        // Restricted to their mainzone
        $conditions[] = "mainzone = ?";
        $params[] = $userZone;
        $types .= "s";
        break;

    case 'Finance':
    case 'HO':
    case 'Auditor':
        // Global access, no base restrictions
        break;

    default:
        // Unknown roles get no data
        $contracts = null;
        exit;
}

// ============================
// 🔍 Apply Manual Filters (only for non-Am-Creator)
// ============================
if ($userRole !== 'Am-Creator') {
    if (!empty($mainzone)) {
        $conditions[] = "mainzone = ?";
        $params[] = $mainzone;
        $types .= "s";
    }
    if (!empty($region)) {
        $conditions[] = "region = ?";
        $params[] = $region;
        $types .= "s";
    }
    if (!empty($area)) {
        $conditions[] = "area = ?";
        $params[] = $area;
        $types .= "s";
    }

    // ✅ Require at least one filter to be selected before showing transactions
    if (empty($mainzone) && empty($region) && empty($area)) {
        $contracts = null;
        $showTransactions = false;
    } else {
        $showTransactions = true;
    }
} else {
    // Am-Creator can always view their transactions
    $showTransactions = true;
}

// ============================
// 🧩 Finalize Query
// ============================
if ($showTransactions) {
    if (!empty($conditions)) {
        $sql .= " AND " . implode(" AND ", $conditions);
    }
    $sql .= " ORDER BY id DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $contracts = $stmt->get_result();
} else {
    $contracts = null;
}

// ============================
// 🧾 Role-based Datalist Options
// ============================
$userMainzone = $_SESSION['mainzone'] ?? '';
$userRegion   = $_SESSION['region'] ?? '';
$userArea     = $_SESSION['area'] ?? '';

if ($userRole === 'Am-Creator') {
    // Only show user's region and area
    $regions = [$userRegion];
    $areas   = [$userArea];

} elseif ($userRole === 'Rm-Reviewer') {
    // Regions and areas under same region
    $regionQuery = $conn->prepare("SELECT DISTINCT region FROM create_contract WHERE region = ? ORDER BY region");
    $regionQuery->bind_param("s", $userRegion);
    $regionQuery->execute();
    $regions = array_column($regionQuery->get_result()->fetch_all(MYSQLI_ASSOC), 'region');

    $areaQuery = $conn->prepare("SELECT DISTINCT area FROM create_contract WHERE region = ? ORDER BY area");
    $areaQuery->bind_param("s", $userRegion);
    $areaQuery->execute();
    $areas = array_column($areaQuery->get_result()->fetch_all(MYSQLI_ASSOC), 'area');

} elseif (in_array($userRole, ['Vpo-Checker','Vpo-Reviewer','Vpo-Approver'])) {
    $mainzoneQuery = $conn->prepare("SELECT DISTINCT mainzone FROM create_contract WHERE mainzone = ? ORDER BY mainzone");
    $mainzoneQuery->bind_param("s", $userMainzone);
    $mainzoneQuery->execute();
    $mainzones = array_column($mainzoneQuery->get_result()->fetch_all(MYSQLI_ASSOC), 'mainzone');

    $regionQuery = $conn->prepare("SELECT DISTINCT region FROM create_contract WHERE mainzone = ? ORDER BY region");
    $regionQuery->bind_param("s", $userMainzone);
    $regionQuery->execute();
    $regions = array_column($regionQuery->get_result()->fetch_all(MYSQLI_ASSOC), 'region');

    $areaQuery = $conn->prepare("SELECT DISTINCT area FROM create_contract WHERE mainzone = ? ORDER BY area");
    $areaQuery->bind_param("s", $userMainzone);
    $areaQuery->execute();
    $areas = array_column($areaQuery->get_result()->fetch_all(MYSQLI_ASSOC), 'area');

} elseif (in_array($userRole, ['Finance', 'HO', 'Auditor'])) {
    $mainzoneQuery = $conn->query("SELECT DISTINCT mainzone FROM create_contract WHERE mainzone != '' ORDER BY mainzone");
    $mainzones = array_column($mainzoneQuery->fetch_all(MYSQLI_ASSOC), 'mainzone');

    $regionQuery = $conn->query("SELECT DISTINCT region FROM create_contract WHERE region != '' ORDER BY region");
    $regions = array_column($regionQuery->fetch_all(MYSQLI_ASSOC), 'region');

    $areaQuery = $conn->query("SELECT DISTINCT area FROM create_contract WHERE area != '' ORDER BY area");
    $areas = array_column($areaQuery->fetch_all(MYSQLI_ASSOC), 'area');
}
?>

<!-- ============================
🧮 FILTER FORM
============================ -->
<form method="GET" class="row g-3 mb-4">
  <?php if ($userRole === 'Rm-Reviewer'): ?>
    <div class="col-md-4">
      <label class="form-label">Region</label>
      <input type="text" name="region" list="regionOptions" class="form-control" placeholder="Select Region" autocomplete="off">
      <datalist id="regionOptions">
        <?php foreach ($regions as $r): ?>
          <option value="<?= htmlspecialchars($r) ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </div>

    <div class="col-md-4">
      <label class="form-label">Area</label>
      <input type="text" name="area" list="areaOptions" class="form-control" placeholder="Select Area" autocomplete="off">
      <datalist id="areaOptions">
        <?php foreach ($areas as $a): ?>
          <option value="<?= htmlspecialchars($a) ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </div>

  <?php elseif (in_array($userRole, ['Vpo-Checker','Vpo-Reviewer','Vpo-Approver', 'Auditor', 'Finance', 'HO'])): ?>
    <div class="col-md-3">
      <label class="form-label">Mainzone</label>
      <input type="text" name="mainzone" list="mainzoneOptions" class="form-control" placeholder="Select Mainzone" autocomplete="off">
      <datalist id="mainzoneOptions">
        <?php foreach ($mainzones as $mz): ?>
          <option value="<?= htmlspecialchars($mz) ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </div>

    <div class="col-md-3">
      <label class="form-label">Region</label>
      <input type="text" name="region" list="regionOptions" class="form-control" placeholder="Select Region" autocomplete="off">
      <datalist id="regionOptions">
        <?php foreach ($regions as $r): ?>
          <option value="<?= htmlspecialchars($r) ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </div>

    <div class="col-md-3">
      <label class="form-label">Area</label>
      <input type="text" name="area" list="areaOptions" class="form-control" placeholder="Select Area" autocomplete="off">
      <datalist id="areaOptions">
        <?php foreach ($areas as $a): ?>
          <option value="<?= htmlspecialchars($a) ?>"></option>
        <?php endforeach; ?>
      </datalist>
    </div>
  <?php endif; ?>

  <?php if ($userRole !== 'Am-Creator'): ?>
    <div class="col-md-3 d-flex align-items-end gap-2">
      <button type="submit" class="btn btn-danger w-50">
        <i class="bi bi-funnel me-1"></i> Filter
      </button>
      <a href="view_contracts.php" class="btn btn-secondary w-50">
        <i class="bi bi-arrow-clockwise me-1"></i> Reset
      </a>
    </div>
  <?php endif; ?>
</form>

<div class="card shadow-sm">
  <div class="card-body">
    <?php if (!empty($contracts) && $contracts instanceof mysqli_result && $contracts->num_rows > 0): ?>

      <!-- Scrollable Table Wrapper -->
      <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
        <table class="table table-hover align-middle mb-0 table-bordered"
               style="border-collapse: collapse; white-space: nowrap;">
          <thead class="bg-danger text-white sticky-top">
            <tr>
              <th scope="col"><i class="bi bi-gear-fill me-1"></i> Action</th>
              <th scope="col"><i class="bi bi-hash me-1"></i> Contract #</th>
              <th scope="col"><i class="bi bi-file-earmark-text me-1"></i> Contract File</th>
              <th scope="col"><i class="bi bi-building me-1"></i> Branch</th>
              <th scope="col"><i class="bi bi-geo-alt-fill me-1"></i> Region</th>
              <th scope="col"><i class="bi bi-diagram-3-fill me-1"></i> Area</th>
              <th scope="col"><i class="bi bi-calendar-check me-1"></i> Effectivity Date</th>
              <th scope="col"><i class="bi bi-calendar-x me-1"></i> Expiry Date</th>
              <th scope="col"><i class="bi bi-calendar-event me-1"></i> RFP Start Date</th>
              <th scope="col"><i class="bi bi-calendar-event-fill me-1"></i> RFP End Date</th>
              <th scope="col"><i class="bi bi-clock-history me-1"></i> Payment Due Date</th>
              <th scope="col"><i class="bi bi-cash-stack me-1"></i> Monthly Rental</th>
              <th scope="col"><i class="bi bi-cash me-1"></i> Net of Vat</th>
              <th scope="col"><i class="bi bi-receipt me-1"></i> Vat Amount</th>
              <th scope="col"><i class="bi bi-percent me-1"></i> Wtax</th>
              <th scope="col"><i class="bi bi-wallet2 me-1"></i> Amount to Lessor</th>
              <th scope="col"><i class="bi bi-person me-1"></i> RFP Requested By</th>
              <th scope="col"><i class="bi bi-calendar-event me-1"></i> RFP Requested Date</th>
            </tr>
          </thead>

          <tbody class="table-group-divider">
            <?php while ($c = $contracts->fetch_assoc()): ?>
              <tr>
                <td>
                  <button 
                    class="btn btn-sm btn-outline-danger rounded-pill px-3 view-transaction-btn"
                    data-bs-toggle="modal"
                    data-bs-target="#transactionModal"
                    data-contract="<?= htmlspecialchars($c['contract_number']) ?>"
                  >
                    <i class="bi bi-receipt"></i> View
                  </button>
                </td>

                <td class="fw-semibold text-dark"><?= htmlspecialchars($c['contract_number']) ?></td>

                <td>
    <?php
    $fileLinks = [];
    $mainFiles = ['contractFilename','contractFilename2','contractFilename3','contractFilename4','contractFilename5','contractFilename16'];

    foreach ($mainFiles as $col) {
        if (!empty($c[$col])) {
            $fileLinks[] = ['index' => $col, 'filename' => $c[$col]];
        }
    }

    for ($i = 6; $i <= 15; $i++) {
        $col = "attachment_{$i}_filename";
        if (!empty($c[$col])) {
            $fileLinks[] = ['index' => $col, 'filename' => $c[$col]];
        }
    }

    $fileCount = count($fileLinks);
    ?>

    <?php if ($fileCount > 0): ?>
        <?php if ($fileCount === 1): ?>
            <?php $file = $fileLinks[0]; ?>
            <a href="javascript:void(0)" 
               onclick="viewPDF(<?= $c['id']; ?>, '<?= urlencode($file['index']); ?>', '<?= htmlspecialchars($file['filename']); ?>')" 
               class="text-decoration-none fw-medium text-primary">
                <i class="bi bi-eye text-primary fs-5"></i>
                <span class="ms-1"><?= htmlspecialchars($file['filename']) ?></span>
            </a>
        <?php else: ?>
            <a href="#" data-bs-toggle="modal" data-bs-target="#contractFilesModal<?= $c['id']; ?>" class="text-decoration-none fw-medium">
                <i class="bi bi-folder2-open text-danger fs-5"></i>
                <span class="ms-1"><?= $fileCount ?> files (View)</span>
            </a>

            <div class="modal fade" id="contractFilesModal<?= $c['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title"><i class="bi bi-files me-2"></i>Select File to Preview</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($fileLinks as $file): ?>
                                    <button type="button" 
                                       onclick="viewPDF(<?= $c['id']; ?>, '<?= urlencode($file['index']); ?>', '<?= htmlspecialchars($file['filename']); ?>')" 
                                       class="list-group-item list-group-item-action d-flex align-items-center">
                                        <i class="bi bi-file-earmark-pdf text-danger fs-5 me-3"></i>
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

                <td><?= htmlspecialchars($c['branch']) ?></td>
                <td><?= htmlspecialchars($c['region']) ?></td>
                <td><?= htmlspecialchars($c['area']) ?></td>

                <td>
                  <?= !empty($c['contract_start']) 
                      ? date("F d, Y", strtotime($c['contract_start'])) 
                      : '<span class="text-muted fst-italic">No Start Date</span>' ?>
                </td>

                <td>
                  <?= !empty($c['contract_end']) 
                      ? date("F d, Y", strtotime($c['contract_end'])) 
                      : '<span class="text-muted fst-italic">No End Date</span>' ?>
                </td>

                <td>
                  <?= !empty($c['start_date']) 
                      ? date("F Y", strtotime($c['start_date'])) 
                      : '<span class="text-warning fw-semibold">No Request For Payment</span>' ?>
                </td>

                <td>
                  <?= !empty($c['end_date']) 
                      ? date("F Y", strtotime($c['end_date'])) 
                      : '<span class="text-warning fw-semibold">No Request For Payment</span>' ?>
                </td>

                <td>
                  <?php
                    if (!empty($c['payment_due_date'])) {
                      $day = date('j', strtotime($c['payment_due_date']));
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
                      echo '<span class="text-muted">—</span>';
                    }
                  ?>
                </td>

                <td><?php echo number_format($c['amount'], 2); ?></td>
                <td><?php echo number_format($c['net_of_vat'], 2); ?></td>
                <td><?php echo number_format($c['vat_amount'], 2); ?></td>
                <td><?php echo number_format($c['wtax'], 2); ?></td>
                <td><?php echo number_format($c['amount_lessor'], 2); ?></td>
                <td><?= htmlspecialchars($c['prepared_by'] ?? '') ?: '-' ?></td>
                <td>
                    <?= (!empty($c['rfp_date']) && $c['rfp_date'] !== '0000-00-00') 
                        ? date("F Y", strtotime($c['rfp_date'])) 
                        : '<span class="text-warning fw-semibold">No Request For Payment</span>' ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

    <?php else: ?>
      <div class="text-center py-5">
        <i class="bi bi-funnel text-danger display-3 mb-3"></i>
        <h5 class="fw-semibold">No contracts to display</h5>
        <p class="text-muted">Please select at least one filter above to view related contract transactions.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

</div>
<!-- 🌟 Transaction Modal -->
<div class="modal fade" id="transactionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg rounded-4">

      <!-- Header -->
      <div class="modal-header bg-gradient text-white" style="background: linear-gradient(90deg, #0d6efd, #6610f2);">
        <h5 class="modal-title fw-semibold">
          <i class="bi bi-receipt-cutoff me-2"></i> Transaction Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body p-4">
        <div id="transactionContent" class="d-flex flex-column align-items-center justify-content-center py-2">
          <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
          <p class="text-muted fw-semibold mb-0">Loading transactions...</p>
          <small class="text-secondary">Please wait while we fetch your data</small>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer bg-light border-0">
        <button type="button" class="btn btn-outline-danger rounded-pill px-4" data-bs-dismiss="modal">
          <i class="bi bi-x-circle me-1"></i> Close
        </button>
      </div>

    </div>
  </div>
</div>

<div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="height: 90vh;">
        <div class="modal-content h-100 shadow-lg">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title" id="pdfPreviewTitle">PDF Preview</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 h-100">
                <iframe id="pdfFrame" src="" width="100%" height="100%" frameborder="0"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
function viewPDF(id, fileIndex, filename) {
    // 1. Set the Title
    const titleElement = document.getElementById('pdfPreviewTitle');
    if (titleElement) {
        titleElement.innerText = 'Preview: ' + filename;
    }
    
    // 2. Set the Source for the Iframe
    const pdfFrame = document.getElementById('pdfFrame');
    if (pdfFrame) {
        const url = `preview_contract_file.php?id=${id}&file=${fileIndex}`;
        pdfFrame.src = url;
    }
    
    // 3. Close the file-selection modal if it's open (for multiple files)
    const openModalElement = document.querySelector('.modal.show');
    if (openModalElement && openModalElement.id !== 'pdfPreviewModal') {
        const listModal = bootstrap.Modal.getInstance(openModalElement);
        if (listModal) {
            listModal.hide();
        }
    }
    
    // 4. Open the Preview Modal
    const previewModalElement = document.getElementById('pdfPreviewModal');
    if (previewModalElement) {
        const previewModal = new bootstrap.Modal(previewModalElement);
        previewModal.show();
    }
}

// Handle Modal Close: Clear source AND Refresh page
const pdfPreviewModal = document.getElementById('pdfPreviewModal');
if (pdfPreviewModal) {
    pdfPreviewModal.addEventListener('hidden.bs.modal', function () {
        // Clear iframe source to stop any background loading
        const pdfFrame = document.getElementById('pdfFrame');
        if (pdfFrame) {
            pdfFrame.src = '';
        }

        // REFRESH THE PAGE
        window.location.reload();
    });
}
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
<script src="../../assets/sweetalert2/dist/sweetalert2.all.min.js"></script>
<script>
  document.querySelector("form")?.addEventListener("submit", function(e) {
  const role = "<?= $userRole ?>";
  const mainzone = this.querySelector("[name=mainzone]")?.value.trim() || "";
  const region   = this.querySelector("[name=region]")?.value.trim() || "";
  const area     = this.querySelector("[name=area]")?.value.trim() || "";

  if (role === "Rm-Reviewer" && !region && !area) {
    e.preventDefault();
    Swal.fire({
      icon: "warning",
      title: "Select at least one filter",
      text: "Please choose Region or Area.",
      confirmButtonColor: "#d70c0c"
    });
  }

  if (["Vpo-Checked","Vpo-Reviewer","Vpo-Approver"].includes(role) 
      && !mainzone && !region && !area) {
    e.preventDefault();
    Swal.fire({
      icon: "warning",
      title: "Select at least one filter",
      text: "Please choose Mainzone, Region, or Area.",
      confirmButtonColor: "#d70c0c"
    });
  }
});

document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("transactionModal");
  const content = document.getElementById("transactionContent");

  modal.addEventListener("show.bs.modal", event => {
    const button = event.relatedTarget;
    const contractNumber = button.getAttribute("data-contract");

    // Loading state
    content.innerHTML = `
      <div class="text-center">
        <div class="spinner-border text-primary"></div>
        <p class="mt-2">Fetching transactions...</p>
      </div>
    `;

    // Fetch data from backend
    fetch(`fetch_transactions.php?contract_number=${encodeURIComponent(contractNumber)}`)
      .then(res => res.text())
      .then(html => {
        content.innerHTML = html;
      })
      .catch(() => {
        content.innerHTML = `<div class="alert alert-danger">Error loading transaction data.</div>`;
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