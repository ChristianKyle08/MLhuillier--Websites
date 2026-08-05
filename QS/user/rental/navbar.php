<?php
$userName = $_SESSION['user_email'] ?? '';
$user_mainzoen = $_SESSION['mainzone'] ?? '';
$user_region = $_SESSION['region'] ?? '';
$user_area =$_SESSION['area'] ?? '';

$row = ['roles' => '', 'region' => '', 'area' => ''];
$branches = [];
$branchCount = 0;
$seenBranchIds = []; // Track unique branch IDs

// -----------------------------
// Fetch user role, region, and area
// -----------------------------
if (!empty($userName)) {
    $stmt = $conn->prepare("
        SELECT roles, region, area 
        FROM user_form 
        WHERE username = ? or email = ?
    ");
    $stmt->bind_param("ss", $userName, $userName);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $f = $res->fetch_assoc()) {
        $row = $f;
    }
    $stmt->close();
}

// -----------------------------
// Only proceed if user is Am-Creator
// -----------------------------
if (($row['roles'] ?? '') === 'Am-Creator') {

    // Fetch branches belonging to user's region & area
    $stmt = $conn->prepare("
        SELECT branch_id, branch_name 
        FROM branch_insurance 
        WHERE region = ? AND area = ? AND ml_matic_status = 'Active'
        ORDER BY branch_name ASC
    ");
    $stmt->bind_param("ss", $row['region'], $row['area']);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($r = $res->fetch_assoc()) {
        $branchId = $r['branch_id'];
        $branchName = $r['branch_name'];

        // Skip duplicate branch_id
        if (isset($seenBranchIds[$branchId])) {
            continue;
        }
        $seenBranchIds[$branchId] = true;

        $contracts = [];

        // Fetch contracts for the branch
        $check = $conn->prepare("
            SELECT contract_number, contract_start, contract_end
            FROM create_contract 
            WHERE branch = ?
        ");
        $check->bind_param("s", $branchName);
        $check->execute();
        $resultContracts = $check->get_result();

        while ($rowC = $resultContracts->fetch_assoc()) {
            $contractNumber = $rowC['contract_number'];

            // ✅ Skip VOID contracts so they are not stored, displayed, or counted
            if (strtoupper(trim($contractNumber ?? '')) === 'VOID') {
                continue;
            }

            $start = $rowC['contract_start'];
            $end = $rowC['contract_end'];

            // Check if contract is terminated
            $tstmt = $conn->prepare("
                SELECT 1 
                FROM transactional 
                WHERE contract_number = ? AND status = 'Terminated' 
                LIMIT 1
            ");
            $tstmt->bind_param("s", $contractNumber);
            $tstmt->execute();
            $tstmt->store_result();
            $isTerminated = $tstmt->num_rows > 0;
            $tstmt->close();

            // Calculate remaining months (if active)
            $remainingMonths = null;
            if (!$isTerminated && !empty($end)) {
                $today = new DateTime();
                $endDate = new DateTime($end);
                $remainingMonths = ($endDate > $today)
                    ? ($today->diff($endDate)->y * 12 + $today->diff($endDate)->m)
                    : 0;
            }

            $contracts[] = [
                'number' => $contractNumber,
                'start' => $start,
                'end' => $end,
                'remaining_months' => $remainingMonths,
                'terminated' => $isTerminated
            ];
        }
        $check->close();

        // Check if branch has any active (non-terminated) contracts
        $hasActive = count(array_filter($contracts, fn($c) => !$c['terminated'])) > 0;

        $branches[] = [
            'id' => $branchId,
            'name' => $branchName,
            'contracts' => $contracts,
            'has_active' => $hasActive
        ];
    }

    // ✅ Sort branches: Active first, then alphabetical (ASC)
    usort($branches, function ($a, $b) {
        if ($a['has_active'] !== $b['has_active']) {
            return $a['has_active'] ? -1 : 1; // Active branches first
        }
        return strcasecmp($a['name'], $b['name']); // Then alphabetical
    });

    $branchCount = count($branches);
    $stmt->close();
}
?>
<style>
  /* Scrollbar styling for sidebar - works in Chrome, Edge, Firefox */
#sidebarMenu {
  overflow-y: auto;
  scrollbar-width: thin;               /* Firefox */
  scrollbar-color: #888 #f0f0f0;       /* Firefox */
}

/* WebKit browsers (Chrome, Edge, Safari) */
#sidebarMenu::-webkit-scrollbar {
  width: 6px;
}

#sidebarMenu::-webkit-scrollbar-track {
  background: #f0f0f0;
  border-radius: 10px;
}

#sidebarMenu::-webkit-scrollbar-thumb {
  background-color: #888;
  border-radius: 10px;
  border: 2px solid transparent;
  background-clip: content-box;
}

#sidebarMenu::-webkit-scrollbar-thumb:hover {
  background-color: #555;
}
/* ===== Dropdown Icon Styling ===== */
.nav-link .dropdown-icon {
  font-size: 0.7rem;             /* smaller icon size */
  opacity: 0.7;                  /* softer appearance */
  margin-left: 6px;              /* spacing from text */
  transition: transform 0.25s ease, opacity 0.25s ease;
  float: right;
}

.nav-link:hover .dropdown-icon {
  opacity: 1;                    /* clearer on hover */
}

.nav-link[aria-expanded="true"] .dropdown-icon {
  transform: rotate(90deg);
  opacity: 1;
}

/* ====== Trigger Button ====== */
.branch-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border: 1px solid #d70c0c;
  background: transparent;
  color: #d70c0c;
  border-radius: 6px;
  cursor: pointer;
  transition: 0.3s;
}
.branch-btn:hover { background: #d70c0c; color: #fff; }
/* =========================================================
   Modern Modal Styling – Clean, Consistent & Responsive
   ========================================================= */

/* ===== Theme Variables ===== */
:root {
  --color-bg: #ffffff;
  --color-overlay: rgba(0, 0, 0, 0.55);
  --color-header-bg: #f8f9fa;
  --color-border: #e0e0e0;
  --color-text: #222;
  --color-subtext: #555;
  --color-primary: #0d6efd;
  --color-success: #28a745;
  --color-warning: #ffc107;
  --color-danger: #dc3545;
  --color-muted-bg: #fafafa;
  --color-hover: #eef5ff;
  --radius: 12px;
  --shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
  --transition: all 0.25s ease;
}

/* ===== Modal Overlay ===== */
.custom-modal {
  display: none;
  position: fixed;
  inset: 0;
  z-index: 10000;
  background: var(--color-overlay);
  backdrop-filter: blur(6px);
  animation: fadeIn 0.25s ease-in-out;
}

/* ===== Modal Container ===== */
.custom-modal-content {
  background: var(--color-bg);
  margin: 4% auto;
  border-radius: var(--radius);
  width: 50%;
  max-width: 700px;
  box-shadow: var(--shadow);
  overflow: hidden;
  animation: slideUp 0.3s ease;
}

/* ===== Modal Header ===== */
.custom-modal-header {
  background: var(--color-header-bg);
  color: var(--color-text);
  padding: 16px 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--color-border);
}

.custom-modal-header h3 {
  font-size: 1.1rem;
  margin: 0;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--color-text);
}

/* ===== Close Icon ===== */
.custom-close {
  cursor: pointer;
  font-size: 1.6rem;
  color: var(--color-subtext);
  transition: transform 0.2s ease, color 0.2s ease;
}
.custom-close:hover {
  color: var(--color-danger);
  transform: rotate(90deg);
}

/* ===== Modal Body ===== */
.custom-modal-body {
  padding: 24px;
  max-height: 450px;
  overflow-y: auto;
  color: var(--color-text);
  background-color: var(--color-muted-bg);
  font-size: 0.95rem;
}

/* ===== Modal Footer ===== */
.custom-modal-footer {
  display: flex;
  justify-content: flex-end;
  padding: 14px 20px;
  background: #f5f5f5;
  border-top: 1px solid var(--color-border);
}

/* ===== Close Button ===== */
.close-btn {
  background: var(--color-danger);
  border: none;
  color: #fff;
  border-radius: 8px;
  padding: 8px 18px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
}
.close-btn:hover,
.close-btn:focus {
  background: #b30808;
  transform: translateY(-2px);
}

/* ===== Branch List ===== */
.branch-list {
  list-style: none;
  margin: 0;
  padding: 0;
}
.branch-item {
  background: #fff;
  margin-bottom: 12px;
  padding: 14px 16px;
  border-radius: 10px;
  border: 1px solid #eaeaea;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  transition: var(--transition);
}
.branch-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
  background: var(--color-hover);
}

/* ===== Contract Indicators (Has / No Contract) ===== */
.with-contract {
  border-left: 5px solid var(--color-success);
}
.without-contract {
  border-left: 5px solid var(--color-danger);
}

/* ===== Contract Item ===== */
.contract-list {
  list-style: none;
  padding-left: 1.25rem;
  margin-top: 8px;
}

.contract-item {
  padding: 10px 12px;
  border-radius: 8px;
  margin-bottom: 8px;
  font-size: 0.9rem;
  transition: var(--transition);
}

/* Running */
.contract-item.running {
  background: #e9f9ef;
  border-left: 4px solid var(--color-success);
  color: #155724;
}
.contract-item.running:hover {
  background: #d4f5dd;
}

/* Expired */
.contract-item.expired {
  background: #fff3cd;
  border-left: 4px solid var(--color-warning);
  color: #856404;
}
.contract-item.expired:hover {
  background: #ffe8a1;
}

/* Terminated */
.contract-item.terminated {
  background: #fdecea;
  border-left: 4px solid var(--color-danger);
  color: #721c24;
}
.contract-item.terminated:hover {
  background: #f8d7da;
}

/* ===== Contract Status Text ===== */
.status {
  font-weight: 600;
}
.running-text { color: var(--color-success); }
.expired-text { color: #d39e00; }
.terminated-text { color: var(--color-danger); }

/* ===== No Contracts / No Branches (Danger Style) ===== */
.no-contracts,
.no-branches {
  background: #fdecea; /* light red background */
  padding: 12px 14px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #b02a37; /* deep red text */
  font-weight: 600;
  border: 1px solid #f5c2c7; /* soft red border */
  box-shadow: 0 2px 6px rgba(220, 53, 69, 0.1);
  font-style: italic;
  transition: all 0.25s ease;
}

.no-contracts:hover,
.no-branches:hover {
  background: #f8d7da;
  border-color: #dc3545;
  color: #a71d2a;
  transform: translateY(-1px);
}


/* ===== Scrollbar Aesthetics ===== */
.custom-modal-body::-webkit-scrollbar {
  width: 8px;
}
.custom-modal-body::-webkit-scrollbar-thumb {
  background: #ccc;
  border-radius: 8px;
}
.custom-modal-body::-webkit-scrollbar-thumb:hover {
  background: #aaa;
}

/* ===== Animations ===== */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
@keyframes slideUp {
  from {
    transform: translateY(40px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

/* ===== Responsive Design ===== */
@media (max-width: 992px) {
  .custom-modal-content {
    width: 70%;
    margin: 8% auto;
  }
}
@media (max-width: 768px) {
  .custom-modal-content {
    width: 90%;
    margin: 12% auto;
  }
  .custom-modal-body {
    padding: 16px;
  }
}


</style>
<style>
/* Rotate dropdown icon smoothly when expanded */
.nav-link .dropdown-icon {
  transition: transform 0.3s ease;
  float: right;
}

.nav-link[aria-expanded="true"] .dropdown-icon {
  transform: rotate(90deg);
}
</style>

<nav id="sidebarMenu" class="sidebar bg-light">
<div class="p-3 text-center border-bottom">
  <i class="bi bi-person" style="font-size: 2rem;"></i>
  <div class="username mb-2"><strong><?= strtoupper($userName); ?></strong></div>
  <?php if (($row['roles'] ?? '') === 'Am-Creator'): ?>
  <div class="username mb-2 text-muted"><strong><?= strtoupper($user_region); ?> / AREA: <?= strtoupper($user_area); ?></strong></div>
    <button class="branch-btn" onclick="openBranchesModal()">
      <i class="bi bi-building-check-fill"></i>
      &nbsp; Branches (<?= $branchCount ?>)
    </button>
  <?php endif; ?>
</div>
      
    <ul class="nav flex-column mt-2">
      <!-- Home -->
      <li class="nav-item">
        <a class="nav-link" href="user_page.php">
          <i class="bi bi-house-door me-2"></i> Home
        </a>
      </li>

      <!-- Lessor Profile -->
      <?php if ($row['roles'] == 'Am-Creator') { ?>
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-toggle="collapse" href="#lessorProfile" aria-expanded="false">
          <i class="bi bi-person-vcard me-2"></i> Lessor Profile
          <i class="bi bi-chevron-right dropdown-icon"></i>
        </a>
        <div class="collapse" id="lessorProfile">
          <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
            <li><a href="create_lessor_profile.php" class="nav-link">Add</a></li>
            <li><a href="edit_lessor_profile.php" class="nav-link">Edit</a></li>
          </ul>
        </div>
      </li>
      <?php } else if ($row['roles'] == 'HO') { ?>
      <li class="nav-item">
        <a class="nav-link" href="post_payment.php">
          <i class="bi bi-credit-card-2-back me-2"></i> Post Payment
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link collapsed" data-bs-toggle="collapse" href="#lessorProfile" aria-expanded="false">
          <i class="bi bi-person-vcard me-2"></i> Lessor Profile
          <i class="bi bi-chevron-right dropdown-icon"></i>
        </a>
        <div class="collapse" id="lessorProfile">
          <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
            <li><a href="edit_lessor_profile.php" class="nav-link">View Lessor Profile</a></li>
          </ul>
        </div>
      </li>
      <?php } ?>

   <!-- Contract of Lease -->
<?php if (!in_array($row['roles'], ['BOOKKEEPER', 'Finance', 'Auditor', 'HO'])) { ?>
<li class="nav-item">
  <a class="nav-link collapsed" data-bs-toggle="collapse" href="#contractLease" aria-expanded="false">
    <i class="bi bi-journal-text me-2"></i> Contract of Lease
    <i class="bi bi-chevron-right dropdown-icon"></i>
  </a>
  <div class="collapse" id="contractLease">
    <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
      <?php if ($row['roles'] == 'Am-Creator') { ?>
        <li><a href="select_contract_type.php" class="nav-link">Create Contract</a></li>
        <li><hr></li>
        <li><strong>RFP</strong></li>
        <li><a href="rfp_returned_contracts.php" class="nav-link">Returned Contracts</a></li>
        <li><a href="payment_solution.php" class="nav-link">Payment Solution</a></li>
        <li><a href="pdc.php" class="nav-link">PDC</a></li>
        <li>
          <span class="nav-link">
            <span style="background-color:whitesmoke; color:#000;" class="rounded px-2 py-1 fw-semibold" data-bs-toggle="tooltip" title="Coming Soon">
              <i class="bi bi-tools text-warning" style="font-size:16px;"></i>
            </span>
            RTA 
          </span>
        </li>
        <li>
          <span class="nav-link">
            <span style="background-color:whitesmoke; color:#000;" class="rounded px-2 py-1 fw-semibold" data-bs-toggle="tooltip" title="Coming Soon">
              <i class="bi bi-tools text-warning" style="font-size:16px;"></i>
            </span>
            MCASH 
          </span>
        </li>
        <li><hr></li>
        <li><strong>DATA ARCHIVING</strong></li>
        <li><a href="returned_contract.php" class="nav-link">Returned Contracts</a></li>
        <li><a href="created_contract.php" class="nav-link">Created Contract</a></li>
        <li><a href="for_review_col.php" class="nav-link">For review by RM</a></li>
        <li><a href="approved_contract.php" class="nav-link">Approved Contracts</a></li>
      <?php } else if (in_array($row['roles'], ['Rm-Reviewer', 'Vpo-Checker', 'Vpo-Approver', 'Vpo-Reviewer'])) { ?>
        <li><strong>RFP</strong></li>
        <li><a href="rfp_returned_contracts.php" class="nav-link">Returned Contracts</a></li>
        <li><a href="payment_solution.php" class="nav-link">Payment Solution</a></li>
        <li><a href="pdc.php" class="nav-link">PDC</a></li>
        <li>
          <span class="nav-link">
            <span style="background-color:whitesmoke; color:#000;" class="rounded px-2 py-1 fw-semibold" data-bs-toggle="tooltip" title="Coming Soon">
              <i class="bi bi-tools text-warning" style="font-size:16px;"></i>
            </span>
            RTA 
          </span>
        </li>
        <li>
          <span class="nav-link">
            <span style="background-color:whitesmoke; color:#000;" class="rounded px-2 py-1 fw-semibold" data-bs-toggle="tooltip" title="Coming Soon">
              <i class="bi bi-tools text-warning" style="font-size:16px;"></i>
            </span>
            MCASH 
          </span>
        </li>
        <li><hr></li>
        <li><strong>DATA ARCHIVING</strong></li>
        <li><a href="returned_contract.php" class="nav-link">Returned Contracts</a></li>
        <li><a href="created_contract.php" class="nav-link">Created Contract</a></li>
        <li><a href="for_review_col.php" class="nav-link">For review by RM</a></li>
        <li><a href="rfp_page_menu.php" class="nav-link">Ready For RFP</a></li>
        <li><a href="approved_contract.php" class="nav-link">Approved Contracts</a></li>
      <?php } ?>
    </ul>
  </div>
</li>
    <?php } ?>
      <!-- Request for Payment -->
      <?php if ($row['roles'] == 'Am-Creator') { ?>
      <li class="nav-item">
        <a class="nav-link" href="rfp_page_menu.php">
          <i class="bi bi-receipt me-2"></i> Request For Payment
        </a>
      </li>
      <?php } ?>

        <!-- 📊 Reports Section -->
        <?php if (!in_array($row['roles'], ['BOOKKEEPER'])): ?>
          <li class="nav-item">
            <a class="nav-link collapsed" data-bs-toggle="collapse" href="#reports" aria-expanded="false">
              <i class="bi bi-bar-chart-line me-2"></i> Reports
              <i class="bi bi-chevron-right dropdown-icon"></i>
            </a>
            <div class="collapse" id="reports">
              <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">

                <!-- Always visible -->
                <li><a href="lease_contract.php" class="nav-link">Contract of Lease</a></li>
                <li><a href="contract_ledger.php" class="nav-link">COL - Payment Ledger</a></li>

                <!-- Bookkeeper only -->
                <?php if ($row['roles'] == 'BOOKKEEPER'): ?>
                  <li><a href="edi_extraction.php" class="nav-link">EDI Extraction</a></li>
                <?php endif; ?>

                <!-- HO and VPO Checker -->
                <?php if (in_array($row['roles'], ['HO', 'Vpo-Checker', 'Bookkeeper'])): ?>
                  <li><a href="ho_page.php" class="nav-link">Head Office</a></li>
                  <li><a href="extract_history.php" class="nav-link">Extraction History</a></li>
                <?php endif; ?>

                <!-- HO only -->
                <?php if ($row['roles'] == 'HO'): ?>
                  <li><a href="corporate_report.php" class="nav-link">By Corporate</a></li>
                  <li><a href="payout_report.php" class="nav-link">Payout Report</a></li>
                  <li><a href="sendout_report.php" class="nav-link">Sendout Report</a></li>
                <?php endif; ?>

                <!-- Allow Finance and Auditor to see View Contracts -->
                <?php if (in_array($row['roles'], ['Finance', 'Auditor', 'HO', 'Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver', 'Am-Creator', 'Rm-Reviewer'])): ?>
                  <li><a href="view_contracts.php" class="nav-link">View Contract</a></li>
                  <li><a href="view_escalation.php" class="nav-link">View Escalation Table</a></li>
                  <li><a href="ho_report.php" class="nav-link">Summary Report</a></li>
                  <li><a href="reviewed_col.php" class="nav-link">Reviewed Contract By RM</a></li>
                <?php endif; ?>

              </ul>
            </div>
          </li>
        <?php endif; ?>
        <!-- Data Extraction -->
        <?php if (!in_array($row['roles'], ['Vpo-Approver', 'Vpo-Reviewer', 'Am-Creator', 'Rm-Reviewer', 'Auditor', 'HO', 'Finance'])) { ?>
        <li class="nav-item">
          <a class="nav-link collapsed" data-bs-toggle="collapse" href="#dataExtraction" aria-expanded="false">
            <i class="bi bi-database me-2"></i> Data Extraction
            <i class="bi bi-chevron-right dropdown-icon"></i>
          </a>
          <div class="collapse" id="dataExtraction">
            <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
              <?php if (in_array($row['roles'], ['Vpo-Checker', 'HO'])) { ?><li><a href="request_data_extraction.php" class="nav-link">Create Data</a></li><?php } ?>
              <?php if ($row['roles'] == 'Finance') { ?><li><a href="extract_request_finance.php" class="nav-link">Batch Upload</a></li><?php } ?>
            </ul>
          </div>
        </li>
        <?php } ?>

      <!-- Manage COL -->
      <?php if (!in_array($row['roles'], ['Auditor', 'HO', 'Finance'])) { ?>
        <li class="nav-item">
          <a class="nav-link collapsed" data-bs-toggle="collapse" href="#manageCol" aria-expanded="false">
            <i class="bi bi-archive me-2"></i> Manage Contract of Lease
            <i class="bi bi-chevron-right dropdown-icon"></i>
          </a>
          <div class="collapse" id="manageCol">
            <ul class="btn-toggle-nav list-unstyled fw-normal pb-1 small">
            <?php if($row['roles'] == 'Vpo-Checker') { ?>
              
              <li><a href="edit_escalation.php" class="nav-link">Edit Escalation Table</a></li>
            
            <?php } ?>
              <!-- Am-Creator and Rm-Reviewer can see all -->
              <?php if ($row['roles'] == 'Am-Creator') { ?>
                <li><a href="add_notarized_col.php" class="nav-link">Add Notarized Contract of Lease</a></li>
                <li><a href="add_spa.php" class="nav-link">Add SPA/Attachments</a></li>
                <li><a href="modify_contract.php" class="nav-link">Request For Change</a></li>
                <li><a href="terminate_contract.php" class="nav-link">Request Termination</a></li>

                <li>
                  <span class="nav-link">
                    <span style="background-color: whitesmoke; color: #000;" 
                          class="rounded px-2 py-1 fw-semibold" 
                          data-bs-toggle="tooltip" 
                          title="Coming Soon">
                      <i class="bi bi-tools text-warning" style="font-size: 10px;"></i>
                    </span>
                    Request Advance Rental
                  </span>
                </li>
              <?php }else if($row['roles'] == 'Rm-Reviewer') {?>
                <li><a href="modify_contract.php" class="nav-link">Request For Change</a></li>
                <li><a href="terminate_contract.php" class="nav-link">Request Termination</a></li>

                <li>
                  <span class="nav-link">
                    <span style="background-color: whitesmoke; color: #000;" 
                          class="rounded px-2 py-1 fw-semibold" 
                          data-bs-toggle="tooltip" 
                          title="Coming Soon">
                      <i class="bi bi-tools text-warning" style="font-size: 10px;"></i>
                    </span>
                    Request Advance Rental
                  </span>
                </li>
              <?php }else if($row['roles'] == 'Vpo-Approver' || $row['roles'] == 'Vpo-Reviewer' || $row['roles'] == 'Vpo-Checker') {?>
                <li><a href="modify_contract.php" class="nav-link">Request For Change</a></li>
                <li><a href="terminate_contract.php" class="nav-link">Request Termination</a></li>

                <li>
                  <span class="nav-link">
                    <span style="background-color: whitesmoke; color: #000;" 
                          class="rounded px-2 py-1 fw-semibold" 
                          data-bs-toggle="tooltip" 
                          title="Coming Soon">
                      <i class="bi bi-tools text-warning" style="font-size: 10px;"></i>
                    </span>
                    Request Advance Rental
                  </span>
                </li>
              <?php } ?>
            </ul>
          </div>
        </li>
      <?php } ?>
    </ul>
  </div>
  <!-- Sidebar Footer -->
<div class="sidebar-footer mt-2 border-top pt-3">
  <!-- System Guide -->
  <a class="nav-link mb-2" href="../../assets/Rental_system_guide.pdf" target="_blank">
    <i class="bi bi-journal-richtext me-2"></i> System Guide
  </a>
<!-- Contact Us -->
<a class="nav-link mb-2" href="#" data-bs-toggle="modal" data-bs-target="#contactModal">
  <i class="bi bi-envelope-at me-2"></i> Contact Us
</a>

<!-- System Version -->
<a class="nav-link disabled mb-2" href="#">
  <i class="bbi bi-info-circle me-2"></i> Version 6.0
</a>

  <a href="#" class="nav-link logout-link mb-2" id="logoutLink">
    <i class="bi bi-box-arrow-right me-2"></i><span>Logout</span>
  </a>
</div>
</div>
</nav>

<!-- Contact Us Modal -->
<div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow-lg">
      <div class="modal-header text-white rounded-top-4" style="background-color: #d70c0c;">
        <h5 class="modal-title text-white" id="contactModalLabel">Need Help?</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center p-4">
        <i class="bi bi-envelope-paper-fill display-4 mb-3" style="color: #d70c0c;"></i>
        
        <p class="fs-5 mb-2">If you need assistance, you can reach us at:</p>
        <p class="fw-bold mb-3" style="color: #d70c0c;">christian.autida@mlhuillier.com</p>

        <!-- ✅ Added note -->
        <p class="text-muted small fst-italic mb-3">
          <i class="bi bi-info-circle me-1"></i>
          You must use your <strong>@mlhuillier.com</strong> email address to send your concern.
        </p>

        <a href="#" id="sendEmailBtn" class="btn mt-2" style="background-color: #d70c0c; border-color: #d70c0c; color: #fff;">
          Send Email
        </a>
      </div>
    </div>
  </div>
</div>
<!-- ===== Branches Modal ===== -->
<div id="branchesModal" class="custom-modal" aria-hidden="true">
  <div class="custom-modal-content">
    <div class="custom-modal-header">
      <h3 id="branchesModalTitle">
        <i class="bi bi-building-check-fill"></i>
        Branches in <?= htmlspecialchars($row['region'] ?? 'N/A') ?> / <?= htmlspecialchars($row['area'] ?? 'N/A') ?>
      </h3>
    </div>

    <div class="custom-modal-body">
      <?php if ($branchCount > 0): ?>
        <ul class="branch-list">
          <?php foreach ($branches as $b): ?>
            <li class="branch-item <?= $b['has_active'] ? 'with-contract' : 'without-contract' ?>">
              <i class="bi bi-shop"></i> <?= htmlspecialchars($b['name']) ?>

              <ul class="contract-list">
                <?php if (count($b['contracts']) > 0): ?>
                  <?php foreach ($b['contracts'] as $c): ?>
                    <?php
                      $startFormatted = date('F d, Y', strtotime($c['start'] ?? ''));
                      $endFormatted   = date('F d, Y', strtotime($c['end'] ?? ''));
                    ?>
                    <li class="contract-item 
                        <?= $c['terminated'] ? 'terminated' : ($c['remaining_months'] > 0 ? 'running' : 'expired') ?>">
                      <i class="bi 
                        <?= $c['terminated'] ? 'bi-x-circle-fill' : ($c['remaining_months'] > 0 ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill') ?>">
                      </i>
                      <strong><?= htmlspecialchars($c['number']) ?></strong><br>
                      <small>
                        Start: <?= $startFormatted ?> | End: <?= $endFormatted ?> |
                        <?php if ($c['terminated']): ?>
                          <span class="status terminated-text">Terminated</span>
                        <?php elseif ($c['remaining_months'] > 0): ?>
                          <span class="status running-text">
                            <?= $c['remaining_months'] ?> month<?= $c['remaining_months'] > 1 ? 's' : '' ?> remaining
                          </span>
                        <?php else: ?>
                          <span class="status expired-text">Expired</span>
                        <?php endif; ?>
                      </small>
                    </li>
                  <?php endforeach; ?>
                <?php else: ?>
                  <li class="no-contracts">
                    <i class="bi bi-info-circle"></i> No Contracts Found
                  </li>
                <?php endif; ?>
              </ul>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <div class="no-branches">
          <i class="bi bi-info-circle"></i> No branches found in this region/area.
        </div>
      <?php endif; ?>
    </div>

    <div class="custom-modal-footer">
      <button class="close-btn" type="button" onclick="closeBranchesModal()">
        <i class="bi bi-x-circle me-1"></i> Close
      </button>
    </div>
  </div>
</div>

<!-- Sidebar End -->
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/sweetalert2/dist/sweetalert2.all.min.js"></script>
<script>
function openBranchesModal() {
  document.getElementById('branchesModal').style.display = 'block';
  document.getElementById('branchesModal').setAttribute('aria-hidden','false');
  document.body.style.overflow = 'hidden';
}
function closeBranchesModal() {
  document.getElementById('branchesModal').style.display = 'none';
  document.getElementById('branchesModal').setAttribute('aria-hidden','true');
  document.body.style.overflow = '';
}
document.addEventListener('click', function(e){
  const modal = document.getElementById('branchesModal');
  if (!modal) return;
  if (e.target === modal) closeBranchesModal();
});
document.addEventListener('keydown', function(e){
  if (e.key === 'Escape') closeBranchesModal();
});
  // Trigger logic from the "Send Email" button
  document.getElementById('sendEmailBtn').addEventListener('click', function (e) {
    e.preventDefault();

    const userEmail = localStorage.getItem('user_email'); // example: "test@mlhuillier.com"

    if (userEmail && userEmail.endsWith('@mlhuillier.com')) {
      const mailURL = 'https://mail.google.com/mail/?view=cm&fs=1&to=christian.autida@mlhuillier.com&su=ML Rental Request&body=Hi CAD IT Team,%0A%0A';
      window.open(mailURL, '_blank');
    } else {
      openCustomModal();
    }
  });
  document.getElementById('sendEmailBtn').addEventListener('click', function (e) {
    e.preventDefault();

    // Compose message URL
    const gmailComposeURL = "https://mail.google.com/mail/?view=cm&fs=1&to=christian.autida@mlhuillier.com&su=ML%20Rental%20Request&body=Hi%20CAD%20IT%20Team,%0A%0A";

    // Gmail login with continue URL
    const loginURL = "https://accounts.google.com/AccountChooser?continue=" + encodeURIComponent(gmailComposeURL);

    // Optional check for a stored email (requires you to set this from backend or login)
    const userEmail = localStorage.getItem('user_email');

    if (userEmail && userEmail.endsWith('@mlhuillier.com')) {
      // ✅ Already logged in with @mlhuillier.com
      window.open(gmailComposeURL, '_blank');
    } else {
      // ❌ Not logged in or not corporate email, redirect to Gmail login first
      window.open(loginURL, '_blank');
    }
  });
</script>
