<?php
session_start();
include '../../config/config.php';

if (!isset($_SESSION['user_name'])) {
   header('location:login_form.php');
}
$userName   = $_SESSION['user_email']  ?? '';
$userRole   = $_SESSION['user_role']   ?? '';
$userZone   = $_SESSION['mainzone']    ?? '';
$userRegion = $_SESSION['region']      ?? '';
$userArea   = $_SESSION['area']        ?? '';
?>
    <!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="generator" content="Mobirise v5.9.13, mobirise.com">
            <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
            <link rel="shortcut icon" href="../../assets/images/mlw-logo-96x96.png" type="image/x-icon">
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
    <body>
    <?php include ('navbar.php'); ?>
    <div id="mainContent">
    <button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
        <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
        <span class="fw-normal">Menu</span>
    </button>

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

    <form action="" method="POST" id="ledger_form">
        <div class="contract_lg_container container py-1">
        <div class="card shadow-sm border-0 rounded-4">
    <div class="card-body">
        <!-- Search Section -->
        <div class="row g-3 align-items-end"><!-- changed align-items-center → align-items-end -->
            <!-- Branch -->
            <div class="col-md-4">
                <label for="branch" class="form-label fw-semibold">
                    <i class="fa-regular fa-building me-2 text-danger"></i>Branch
                </label>
                <input list="branchList" name="branch" id="branch" class="form-control" 
                    autocomplete="off" required onchange="updateKpxCode(this)" 
                    value="<?php echo isset($_POST['branch']) ? htmlspecialchars($_POST['branch']) : ''; ?>">

                <datalist id="branchList">
                    <?php
                    $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);
                    $userQuery = "SELECT roles, mainzone, area, region FROM user_form WHERE username = '$user_email' OR email = '$user_email'";
                    $resultUser = mysqli_query($conn, $userQuery);
                    $user = mysqli_fetch_assoc($resultUser);

                    $userRole     = $user['roles'] ?? '';
                    $userMainzone = $user['mainzone'] ?? '';
                    $userArea     = $user['area'] ?? '';
                    $userRegion   = $user['region'] ?? '';

                    // Exclude branches with at least one row having termination-related statuses
                    $excludeCondition = "
                        NOT EXISTS (
                            SELECT 1 
                            FROM transactional t2 
                            WHERE t2.branch_id = t1.branch_id
                            AND t2.status IN ('Termination Requested','Termination Reviewed','Termination Checked','Terminated')
                        )
                    ";

                    if ($userRole === 'HO') {
                        $transactional = "
                            SELECT DISTINCT t1.branch, t1.region, t1.area, t1.kpx_code, t1.branch_id 
                            FROM transactional t1
                            WHERE t1.branch != '' 
                            AND $excludeCondition
                            ORDER BY t1.branch ASC";
                    } elseif ($userRole === 'Am-Creator') {
                        $transactional = "
                            SELECT DISTINCT t1.branch, t1.region, t1.area, t1.kpx_code, t1.branch_id 
                            FROM transactional t1
                            WHERE t1.branch != '' 
                            AND $excludeCondition
                            AND t1.region = '$userRegion'
                            AND t1.area   = '$userArea'
                            ORDER BY t1.branch ASC";
                    } elseif ($userRole === 'Rm-Reviewer') {
                        $transactional = "
                            SELECT DISTINCT t1.branch, t1.region, t1.area, t1.kpx_code, t1.branch_id
                            FROM transactional t1
                            WHERE t1.branch != '' 
                            AND $excludeCondition
                            AND t1.region = '$userRegion'
                            ORDER BY t1.branch ASC";
                    } elseif ($userRole === 'Auditor') {
                        $transactional = "
                            SELECT DISTINCT t1.branch, t1.region, t1.area, t1.kpx_code, t1.branch_id
                            FROM transactional t1
                            WHERE t1.branch != '' 
                            AND $excludeCondition
                            ORDER BY t1.branch ASC";
                    } elseif ($userRole === 'Finance') {
                        $transactional = "
                            SELECT DISTINCT t1.branch, t1.region, t1.area, t1.kpx_code, t1.branch_id
                            FROM transactional t1
                            WHERE t1.branch != '' 
                            AND $excludeCondition
                            AND t1.mode_of_payment = 'PDC'
                            ORDER BY t1.branch ASC";
                    } else {
                        $transactional = "
                            SELECT DISTINCT t1.branch, t1.region, t1.area, t1.kpx_code, t1.branch_id
                            FROM transactional t1
                            WHERE t1.branch != '' 
                            AND $excludeCondition
                            AND t1.mainzone = '$userMainzone'
                            ORDER BY t1.branch ASC";
                    }

                    $resultBranch = mysqli_query($conn, $transactional);
                    if ($resultBranch && mysqli_num_rows($resultBranch) > 0) {
                        while ($rowBranch = mysqli_fetch_assoc($resultBranch)) {
                            echo "<option 
                                    value='" . htmlspecialchars($rowBranch['branch']) . "' 
                                    data-kpx-code='" . htmlspecialchars($rowBranch['kpx_code']) . "' 
                                    data-branch-id='" . htmlspecialchars($rowBranch['branch_id']) . "'>
                                    " . htmlspecialchars($rowBranch['branch']) . " (" . htmlspecialchars($rowBranch['region']) . ", Area " . htmlspecialchars($rowBranch['area']) . ")
                                </option>";
                        }
                    }
                    ?>
                </datalist>
            </div>

            <!-- Contract Number -->
            <div class="col-md-4">
                <label for="contractNumber" class="form-label fw-semibold">
                    <i class="fa-solid fa-file-signature me-2 text-danger"></i>Contract Number
                </label>
                <select name="contractNumber" id="contractNumber" class="form-select" required>
                    <option value="">-- Select Contract --</option>
                    <?php
                        if (isset($_POST['branch'])) {
                            $branch = mysqli_real_escape_string($conn, $_POST['branch']);
                            $contractQuery = "SELECT DISTINCT contract_number FROM transactional WHERE branch = '$branch'";
                            $resultContracts = mysqli_query($conn, $contractQuery);
                            while ($rowContract = mysqli_fetch_assoc($resultContracts)) {
                                $selected = (isset($_POST['contractNumber']) && $_POST['contractNumber'] == $rowContract['contract_number']) ? 'selected' : '';
                                echo "<option value='" . $rowContract['contract_number'] . "' $selected>" . $rowContract['contract_number'] . "</option>";
                            }
                        }
                    ?>
                </select>
            </div>

            <!-- Proceed Button -->
            <div class="col-md-4">
                <input type="hidden" name="lessor_name" id="lessor_name" value="<?php echo isset($_POST['lessor_name']) ? $_POST['lessor_name'] : '' ?>" readonly>
                <input type="hidden" name="kpxCode" id="kpxCode" value="<?php echo isset($_POST['kpxCode']) ? $_POST['kpxCode'] : '' ?>">
                <input type="hidden" name="branchId" id="branchId" value="<?php echo isset($_POST['branchId']) ? $_POST['branchId'] : '' ?>">
                <button type="submit" name="proceed_btn" id="proceed_btn" class="btn btn-danger w-100 fw-semibold shadow-sm">
                    <i class="bi bi-arrow-right-circle me-2"></i> Proceed
                </button>
            </div>

        </div>
    </div>
</div>
            <!-- Contract Ledger -->
            <?php
            if(isset($_POST['proceed_btn'])){
                $contract_number = $_POST['contractNumber'];

                $filequery = "SELECT contract_file, contractFilename, mimeType,
                        contract_file2, contractFilename2, mimeType2,
                        contract_file3, contractFilename3, mimeType3,
                        contract_file4, contractFilename4, mimeType4,
                        contract_file5, contractFilename5, mimeType5,
                        contract_file16, contractFilename16, mimeType16,
                        notarized
                        FROM create_contract
                        WHERE contract_number = '" . $_POST['contractNumber'] . "'";

                $fileresult = mysqli_query($conn, $filequery);

                if ($row = mysqli_fetch_assoc($fileresult)) {
                    $notarizedStatus = ($row['notarized'] === 'Yes') 
                        ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Notarized</span>' 
                        : '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i> Not Notarized</span>';
                }
            ?>
            <div class="wrap-contract mt-4">
                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white text-dark text-center rounded-top-4">
                        <h4 class="mb-0 fw-bold text-dark"><i class="bi bi-file-earmark-text me-2"></i>Contract of Lease - Payment Ledger</h4>
                        <?php echo '<p class="mb-0 small mt-2"><strong>Contract Status:</strong> ' . $notarizedStatus . '</p>'; ?>
                    </div>
                    <div class="table-responsive">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden mx-4 my-4" style="background-color: #ffffff;">
    <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
       <table class="table table-hover align-middle text-center mb-0" style="border-collapse: separate; border-spacing: 0; white-space: nowrap;">
            <thead class="sticky-top shadow-sm" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #f8fafc; z-index: 5;">
                <tr>
                    <th class="px-4 py-3 fs-7 fw-semibold text-uppercase border-bottom-0" style="letter-spacing: 0.8px; font-size: 0.75rem;"><i class="bi bi-clipboard-check me-2 opacity-75"></i>Status</th>
                    <th class="px-4 py-3 fs-7 fw-semibold text-uppercase border-bottom-0" style="letter-spacing: 0.8px; font-size: 0.75rem;"><i class="bi bi-wallet2 me-2 opacity-75"></i>Payout Status</th>
                    <th class="px-4 py-3 fs-7 fw-semibold text-uppercase border-bottom-0" style="letter-spacing: 0.8px; font-size: 0.75rem;"><i class="bi bi-calendar-date me-2 opacity-75"></i>Date</th>
                    <th class="px-4 py-3 fs-7 fw-semibold text-uppercase border-bottom-0" style="letter-spacing: 0.8px; font-size: 0.75rem;"><i class="bi bi-upc-scan me-2 opacity-75"></i>Branch ID</th>
                    <th class="px-4 py-3 fs-7 fw-semibold text-uppercase border-bottom-0" style="letter-spacing: 0.8px; font-size: 0.75rem;"><i class="bi bi-building me-2 opacity-75"></i>Branch Name</th>
                    <th class="px-4 py-3 fs-7 fw-semibold text-uppercase border-bottom-0" style="letter-spacing: 0.8px; font-size: 0.75rem;"><i class="bi bi-cash-coin me-2 opacity-75"></i>Amount to Lessor</th>
                    <th class="px-4 py-3 fs-7 fw-semibold text-uppercase border-bottom-0" style="letter-spacing: 0.8px; font-size: 0.75rem;"><i class="bi bi-upc me-2 opacity-75"></i>KPTN</th>
                    <th class="px-4 py-3 fs-7 fw-semibold text-uppercase border-bottom-0" style="letter-spacing: 0.8px; font-size: 0.75rem;"><i class="bi bi-send me-2 opacity-75"></i>Sendout Branch</th>
                    <th class="px-4 py-3 fs-7 fw-semibold text-uppercase border-bottom-0" style="letter-spacing: 0.8px; font-size: 0.75rem;"><i class="bi bi-bank me-2 opacity-75"></i>Payout Branch</th>
                    <th class="px-4 py-3 fs-7 fw-semibold text-uppercase border-bottom-0" style="letter-spacing: 0.8px; font-size: 0.75rem;"><i class="bi bi-person-workspace me-2 opacity-75"></i>Sendout Operator</th>
                    <th class="px-4 py-3 fs-7 fw-semibold text-uppercase text-start border-bottom-0" style="letter-spacing: 0.8px; font-size: 0.75rem;"><i class="bi bi-person-badge me-2 opacity-75"></i>Lessor Name</th>
                    <?php
                    $hasLessor2Column = false; // Added to track if we need an empty <td> for injected rows
                    if (isset($_POST['contractNumber'])) {
                        $status = ''; 
                        $contract_number = $_POST['contractNumber'];
                        $checkQuery = "SELECT l2_firstname, l2_middlename, l2_lastname 
                                    FROM transactional 
                                    WHERE (l2_firstname != '' OR l2_middlename != '' OR l2_lastname != '') 
                                    AND status != ? AND contract_number = ? LIMIT 1";
                        $checkStmt = mysqli_prepare($conn, $checkQuery);
                        mysqli_stmt_bind_param($checkStmt, "ss", $status, $contract_number);
                        mysqli_stmt_execute($checkStmt);
                        $checkResult = mysqli_stmt_get_result($checkStmt);
                        if (mysqli_num_rows($checkResult) > 0) {
                            $hasLessor2Column = true;
                            echo "<th class='px-4 py-3 fs-7 fw-semibold text-uppercase border-bottom-0' style='letter-spacing: 0.8px; font-size: 0.75rem;'><i class='bi bi-person-lines-fill me-2 opacity-75'></i>2nd Lessor Name</th>";
                        }
                    }
                    ?>
                    <th class="px-4 py-3 fs-7 fw-semibold text-uppercase border-bottom-0" style="letter-spacing: 0.8px; font-size: 0.75rem;"><i class="bi bi-person me-2 opacity-75"></i>RFP Requested By</th>
                    <th class="px-4 py-3 fs-7 fw-semibold text-uppercase border-bottom-0" style="letter-spacing: 0.8px; font-size: 0.75rem;"><i class="bi bi-calendar-date me-2 opacity-75"></i>RFP Requested Date</th>
                </tr>
            </thead>
            <tbody class="text-secondary" style="font-size: 0.9rem;">
                <?php
                    if (isset($_POST['contractNumber'])) {
                        $selectQuery = "
                            SELECT t.*, s.so_operator AS sendout_operator, s.sendout_branch 
                            FROM transactional t 
                            LEFT JOIN sendout s ON t.kptn = s.kptn 
                            WHERE t.status != ? AND t.contract_number = ?
                            ORDER BY t.transaction_date ASC";
                        $stmt = mysqli_prepare($conn, $selectQuery);
                        mysqli_stmt_bind_param($stmt, "ss", $status, $contract_number);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);

                        // Capture today's date and define role
                        $currentDate = strtotime(date('Y-m-d'));
                        $isHOUser = (isset($userRole) && $userRole === 'HO'); // Ensure explicit HO role check
                        
                        // Tracker for the previous row's date to find gaps
                        $lastDateOb = null;

                        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                            
                            // ✅ Lessor 1 with authorize to claim (if any)
                            $lessor1 = 
                            (!empty($row['l1_firstname']) ? htmlspecialchars($row['l1_firstname'], ENT_QUOTES, 'UTF-8') : 'N/A') . " " .
                            (!empty($row['l1_middlename']) ? htmlspecialchars($row['l1_middlename'], ENT_QUOTES, 'UTF-8') : '') . " " .
                            (!empty($row['l1_lastname']) ? htmlspecialchars($row['l1_lastname'], ENT_QUOTES, 'UTF-8') : '');

                            // ✅ Authorize to claim (prefer new if available)
                            $recentAuthorize = trim(($row['authorize_firstName'] ?? '') . " " . ($row['authorize_middleName'] ?? '') . " " . ($row['authorize_lastName'] ?? ''));
                            $newAuthorize    = trim(($row['new_authorize_firstname'] ?? '') . " " . ($row['new_authorize_middlename'] ?? '') . " " . ($row['new_authorize_lastname'] ?? ''));

                            $authorizeDisplay = '';
                            if (!empty($newAuthorize)) {
                                $authorizeDisplay = " <br><span class='text-muted small fw-normal' style='font-size: 0.75rem;'><i class='bi bi-arrow-return-right me-1'></i>Authorize: <span class='fw-medium'>" . htmlspecialchars($newAuthorize, ENT_QUOTES, 'UTF-8') . "</span></span>";
                            } elseif (!empty($recentAuthorize)) {
                                $authorizeDisplay = " <br><span class='text-muted small fw-normal' style='font-size: 0.75rem;'><i class='bi bi-arrow-return-right me-1'></i>Authorize: <span class='fw-medium'>" . htmlspecialchars($recentAuthorize, ENT_QUOTES, 'UTF-8') . "</span></span>";
                            }

                            // Determine current Due Date
                            $dueDate = (!empty($row['new_due_date']) && $row['dueDate_request_status'] === 'Approved') 
                                        ? $row['new_due_date'] 
                                        : $row['transaction_date'];

                            // ==========================================
                            // ADDED LOGIC: Check for and inject missing months
                            // ==========================================
                                $currentDateOb = new DateTime($dueDate);
                                
                                if ($lastDateOb !== null) {
                                    // Start from the 1st of the month to safely add months
                                    $lastMonthStart = (clone $lastDateOb)->modify('first day of this month');
                                    $currentMonthStart = (clone $currentDateOb)->modify('first day of this month');
                                    
                                    $checkMonth = (clone $lastMonthStart)->modify('+1 month');
                                    
                                    // Loop through any gaps
                                    while ($checkMonth < $currentMonthStart) {
                                        // Dynamic premium aesthetic highlight style for the missing ledger rows
                                        echo '<tr style="background-color: #f8fafc; border-left: 4px solid #6366f1; transition: all 0.2s ease;">'; 
                                        echo '<td style="display:none;"></td>';
                                        echo '<td style="display:none;"></td>';
                                        
                                        // Missing Date formatting
                                        $missingDay = $lastDateOb->format('d');
                                        $missingDateDisplay = $checkMonth->format('F') . ' ' . $missingDay . ', ' . $checkMonth->format('Y');

                                        // Status
                                        echo '<td class="px-4 py-3 text-muted opacity-50">-</td>';
                                        // Payout Status
                                        echo '<td class="px-4 py-3 text-muted opacity-50">-</td>';
                                        // Date
                                        echo '<td class="px-4 py-3 fw-semibold text-muted">' . $missingDateDisplay . '</td>';
                                        // Branch ID
                                        echo '<td class="px-4 py-3 text-muted opacity-50">-</td>';
                                        // Branch Name
                                        echo '<td class="px-4 py-3 text-muted opacity-50">-</td>';
                                        // Amount to Lessor
                                        echo '<td class="px-4 py-3 text-muted opacity-50">-</td>';
                                        // KPTN
                                        echo '<td class="px-4 py-3 text-muted opacity-50">-</td>';
                                        // Sendout Branch
                                        echo '<td class="px-4 py-3 text-muted opacity-50">-</td>';
                                        // Payout Branch
                                        echo '<td class="px-4 py-3 text-muted opacity-50">-</td>';
                                        // Sendout Operator
                                        echo '<td class="px-4 py-3 text-muted opacity-50">-</td>';
                                        
                                        // Carry over Lessor Name
                                        echo '<td class="text-start fw-medium px-4 py-3" style="color: #475569;">' . $lessor1 . $authorizeDisplay . '</td>';
                                        
                                        // Carry over empty 2nd Lessor if column exists
                                        if ($hasLessor2Column) {
                                            echo '<td class="px-4 py-3 text-muted opacity-50">-</td>';
                                        }
                                        
                                        // RFP Requested By
                                        echo '<td class="px-4 py-3 text-muted opacity-50">-</td>';
                                        // RFP Requested Date
                                        echo '<td class="px-4 py-3 text-muted opacity-50">-</td>';
                                            
                                        if (!empty($row['export_status'])) {
                                            echo '<td class="px-4 py-3"></td>';
                                        }
                                        
                                        echo '</tr>';
                                        
                                        $checkMonth->modify('+1 month');
                                    }
                                }
                                $lastDateOb = clone $currentDateOb;

                            // ==========================================

                            // Variables for Status and Payout Status Column
                            $statusText = htmlspecialchars($row['status'] ?? '', ENT_QUOTES, 'UTF-8');
                            $cancelReason = htmlspecialchars($row['cancel_request_reason'] ?? 'No reason provided', ENT_QUOTES, 'UTF-8');

                            // Criteria Check: Check if payout_status is 'Pending' or 'Unclaimed'
                            $rawPayoutStatus = trim($row['payout_status'] ?? '');
                            $isUnclaimed = (strcasecmp($rawPayoutStatus, 'Processing') === 0 || strcasecmp($rawPayoutStatus, 'Unclaimed') === 0);
                            $displayPayoutStatus = $isUnclaimed ? 'Unclaimed' : htmlspecialchars($rawPayoutStatus, ENT_QUOTES, 'UTF-8');


                            // Render the actual fetched row
                            echo '<tr class="border-bottom" style="border-color: #f1f5f9; transition: all 0.2s ease;">';
                            echo '<td style="display:none;">' . htmlspecialchars($row['id'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                            echo '<td style="display:none;">' . htmlspecialchars($row['kpx_code'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                            
                            // 1. Status
                            switch ($statusText) {
                                case 'Paid':
                                    // If payout status is Unclaimed, apply Red styling for Paid status
                                    if ($isUnclaimed) {
                                        echo '<td class="px-4 py-3 align-middle">
                                                <span class="badge rounded-pill px-3 py-2 fw-bold d-inline-flex align-items-center shadow-sm" style="background-color: #fee2e2; color: #dc2626; font-size: 0.75rem; border: 1px solid #fecaca;">
                                                    <i class="bi bi-check-circle-fill me-1 fs-6"></i> Paid
                                                </span>
                                            </td>';
                                    } else {
                                        echo '<td class="px-4 py-3 align-middle">
                                                <span class="badge rounded-pill px-3 py-2 fw-bold d-inline-flex align-items-center shadow-sm" style="background-color: #dcfce7; color: #166534; font-size: 0.75rem; border: 1px solid #bbf7d0;">
                                                    <i class="bi bi-check-circle-fill me-1 fs-6"></i> Paid
                                                </span>
                                            </td>';
                                    }
                                    break;

                                case 'Unpaid':
                                    $isLateDate = (strtotime($dueDate) < $currentDate);
                                
                                    if ($isHOUser && $isLateDate) {
                                        // Safely extract the contract number
                                        $escContractNum = htmlspecialchars($contract_number, ENT_QUOTES, 'UTF-8');
                                        $escDueDate = htmlspecialchars($dueDate, ENT_QUOTES, 'UTF-8');
                                
                                        echo '<td class="px-4 py-3 align-middle">
                                                <button class="btn btn-sm rounded-pill fw-bold shadow-sm d-inline-flex align-items-center transition-all btn-pbb-trigger" 
                                                    style="background-color: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-size: 0.75rem; padding: 0.4rem 1rem; cursor: pointer;" 
                                                    data-contract="' . $escContractNum . '"
                                                    data-date="' . $escDueDate . '"
                                                    title="Mark as Paid by Branch">
                                                    <i class="bi bi-building-up me-1 fs-6"></i> Paid by branch?
                                                </button>
                                              </td>';
                                    } else {
                                        echo '<td class="px-4 py-3 align-middle">
                                                <span class="badge rounded-pill px-3 py-2 fw-bold d-inline-flex align-items-center shadow-sm" style="background-color: #fef3c7; color: #92400e; font-size: 0.75rem; border: 1px solid #fde68a;">
                                                    <i class="bi bi-exclamation-triangle-fill me-1 fs-6"></i> Unpaid
                                                </span>
                                              </td>';
                                    }
                                    break;

                                case 'PBB':
                                    echo '<td class="px-4 py-3 align-middle">
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2 fw-medium shadow-sm">
                                                <i class="bi bi-building-check me-1 fs-6"></i> Paid by Branch
                                            </span>
                                        </td>';
                                    break;

                                case 'Cancelled':
                                    echo '<td class="px-4 py-3 align-middle">
                                            <span class="badge rounded-pill px-3 py-2 fw-bold d-inline-flex align-items-center gap-1 shadow-sm" 
                                                style="background-color: #fee2e2; color: #991b1b; font-size: 0.75rem; border: 1px solid #fecaca;">
                                                <i class="bi bi-x-circle-fill fs-6"></i> Cancelled
                                                <i class="bi bi-info-circle-fill ms-1 text-danger opacity-75 hover-opacity-100 transition-all" role="button"
                                                data-bs-toggle="modal"
                                                data-bs-target="#cancelReasonModal"
                                                data-reason="' . $cancelReason . '"
                                                title="View Cancel Reason" style="font-size: 1rem;"></i>
                                            </span>
                                        </td>';
                                    break;

                                default:
                                    echo '<td class="px-4 py-3 align-middle">
                                            <span class="badge rounded-pill px-3 py-2 fw-bold d-inline-flex align-items-center shadow-sm" style="background-color: #f1f5f9; color: #475569; font-size: 0.75rem; border: 1px solid #e2e8f0;">
                                                <i class="bi bi-question-circle-fill me-1 fs-6"></i> ' . $statusText . '
                                            </span>
                                        </td>';
                                    break;
                            }

                            // 2. Payout Status (Criteria 1: Displays "Unclaimed" in red if Pending or Unclaimed)
                            if ($isUnclaimed) {
                                echo '<td class="px-4 py-3 text-danger fw-bold" style="font-size: 0.85rem;">' . $displayPayoutStatus . '</td>';
                            } else {
                                echo '<td class="px-4 py-3 text-secondary fw-medium" style="font-size: 0.85rem;">' . $displayPayoutStatus . '</td>';
                            }
                            
                            // 3. Date
                            echo '<td class="px-4 py-3 text-dark fw-semibold">' . date('F d, Y', strtotime($dueDate)) . '</td>';
                            
                            // 4. Branch ID
                            echo '<td class="px-4 py-3 fw-medium">' . htmlspecialchars($row['branch_id'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                            
                            // 5. Branch Name
                            echo '<td class="px-4 py-3 text-start fw-medium text-dark">' . htmlspecialchars($row['branch'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                            
                            // 6. Amount to Lessor
                            echo '<td class="px-4 py-3 text-dark fw-bold text-end" style="font-family: \'Roboto Mono\', monospace; font-size: 0.95rem;">₱ ' . number_format($row['edit_amount_lessor'] ?? 0, 2) . '</td>';
                            
                            // 7. KPTN
                            echo '<td class="px-4 py-3 font-monospace text-primary" style="font-size: 0.85rem;">' . htmlspecialchars($row['kptn'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                            
                            // 8. Sendout Branch
                            echo '<td class="px-4 py-3 text-start text-secondary" style="font-size: 0.85rem;">' . htmlspecialchars($row['sendout_branch'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                            
                            // 9. Payout Branch
                            echo '<td class="px-4 py-3 text-start text-secondary" style="font-size: 0.85rem;">' . htmlspecialchars($row['payout_branch'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                            
                            // 10. Sendout Operator
                            echo '<td class="px-4 py-3 text-start text-secondary" style="font-size:0.85rem;">' . htmlspecialchars($row['sendout_operator'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';

                            // 11. Lessor Name
                            echo '<td class="text-start text-dark fw-bold px-4 py-3">' . $lessor1 . $authorizeDisplay . '</td>';

                            // 12. 2nd Lessor Name (Conditional Check bound to headers)
                            if ($hasLessor2Column) {
                                if (!empty($row['l2_firstname']) || !empty($row['l2_middlename']) || !empty($row['l2_lastname'])) {
                                    echo '<td class="px-4 py-3 fw-medium text-dark">' . 
                                        (!empty($row['l2_firstname']) ? htmlspecialchars($row['l2_firstname'], ENT_QUOTES, 'UTF-8') : 'N/A') . " " . 
                                        (!empty($row['l2_middlename']) ? htmlspecialchars($row['l2_middlename'], ENT_QUOTES, 'UTF-8') : '') . " " . 
                                        (!empty($row['l2_lastname']) ? htmlspecialchars($row['l2_lastname'], ENT_QUOTES, 'UTF-8') : '') . 
                                    '</td>';
                                } else {
                                    echo '<td class="px-4 py-3 text-muted opacity-50">-</td>';
                                }
                            }

                            // 13. RFP Requested By
                            echo '<td class="px-4 py-3 text-secondary" style="font-size: 0.85rem;">' . htmlspecialchars($row['rfp_by'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
                            
                            // 14. RFP Requested Date
                            echo '<td class="px-4 py-3 text-muted" style="font-size: 0.85rem;">' . htmlspecialchars($row['rfp_date'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';

                            // Export Status (if available, left as the final column per original implementation)
                            if (!empty($row['export_status'])) {
                                echo '<td class="px-4 py-3 fw-bold text-dark">' . htmlspecialchars($row['export_status'], ENT_QUOTES, 'UTF-8') . '</td>';
                            }

                            echo '</tr>';
                        }
                    }
                    mysqli_close($conn);
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
                </div>
            </div>
        </div>
    </form>
</div>
<!-- Cancel Reason Modal -->
<div class="modal fade" id="cancelReasonModal" tabindex="-1" aria-labelledby="cancelReasonModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title text-white" id="cancelReasonModalLabel">
          <i class="bi bi-info-circle me-2"></i>Cancellation Reason
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="cancelReasonText">
        <!-- Reason will appear here dynamically -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Enhanced Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4" style="background-color: #fefefe;">
      <div class="modal-body">
        <div class="text-center mb-3">
          <i class="bi bi-box-arrow-right text-danger" style="font-size: 3rem;"></i>
        </div>
        <h5 class="mb-2 text-dark">Logging Out</h5>
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
    document.addEventListener('DOMContentLoaded', function () {
    // Attach listener to all premium PBB buttons
    document.querySelectorAll('.btn-pbb-trigger').forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            
            const contractNumber = this.getAttribute('data-contract');
            const TargetDate = this.getAttribute('data-date');
            const tableRow = this.closest('tr'); // Capture row context to change UI dynamically

            // Premium SweetAlert2 confirmation frame
            Swal.fire({
                title: 'Confirm Payment Status',
                text: `Are you completely sure contract ${contractNumber} was paid by the branch on ${TargetDate}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2563eb', // Matches premium blue branding
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Mark as Paid',
                cancelButtonText: 'Cancel',
                background: '#ffffff',
                customClass: {
                    popup: 'rounded-4 shadow-lg'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Initialize AJAX runtime execution
                    fetch('update_payment_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `contract_number=${encodeURIComponent(contractNumber)}&target_date=${encodeURIComponent(TargetDate)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Transaction status has been successfully updated.',
                                icon: 'success',
                                confirmButtonColor: '#2563eb'
                            });
                            
                            // Dynamically swap the action button out for the premium static tag without full page reload
                            this.parentElement.innerHTML = `
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2 fw-medium shadow-sm">
                                    <i class="bi bi-building-check me-1 fs-6"></i> Paid by Branch
                                </span>
                            `;
                        } else {
                            Swal.fire('Error', data.message || 'Failed to update transaction status.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'An unexpected connection error occurred.', 'error');
                    });
                }
            });
        });
    });
});

    document.addEventListener('DOMContentLoaded', function () {
        const cancelReasonModal = document.getElementById('cancelReasonModal');
        cancelReasonModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const reason = button.getAttribute('data-reason') || 'No reason provided';
            const modalBody = cancelReasonModal.querySelector('#cancelReasonText');
            modalBody.textContent = reason;
        });
    });

     document.getElementById('logoutLink').addEventListener('click', function (e) {
        e.preventDefault();
        const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'), {
            backdrop: 'static',
            keyboard: false
        });

        logoutModal.show();

            // Simulate logout delay
        setTimeout(() => {
            window.location.href = '../../logout.php';
        }, 2500);
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
    document.addEventListener('DOMContentLoaded', function() {
    // FIX FOR ERROR 1: Safety check for line 771
    // Replace 'yourButtonIdHere' with the actual ID causing the crash if known
    const someButton = document.getElementById('yourButtonIdHere'); 
    if (someButton) {
        someButton.onclick = function() {
            console.log("Button clicked");
        };
    }
});

function updateKpxCode(branchInput) {
    if (!branchInput) return;

    const selectedBranchName = branchInput.value;
    const contractSelect = document.getElementById('contractNumber');
    const branchList = document.getElementById('branchList');
    
    // Hidden Fields
    const kpxField = document.getElementById('kpxCode');
    const branchIdField = document.getElementById('branchId');

    // FIX FOR ERROR 2: How to get data attributes from a DATALIST
    let selectedOption = null;
    if (branchList && selectedBranchName) {
        // Loop through datalist options to find the matching branch name
        for (let i = 0; i < branchList.options.length; i++) {
            if (branchList.options[i].value === selectedBranchName) {
                selectedOption = branchList.options[i];
                break;
            }
        }
    }

    // Update hidden fields if a match was found in the datalist
    if (selectedOption) {
        if (kpxField) kpxField.value = selectedOption.dataset.kpxCode || '';
        if (branchIdField) branchIdField.value = selectedOption.dataset.branchId || '';
    } else {
        // Clear hidden fields if user types something invalid
        if (kpxField) kpxField.value = '';
        if (branchIdField) branchIdField.value = '';
    }

    // If branch is empty, reset contract dropdown and exit
    if (!selectedBranchName) {
        if (contractSelect) contractSelect.innerHTML = '<option value="">-- Select Contract --</option>';
        return;
    }

    if (contractSelect) contractSelect.innerHTML = '<option value="">-- Loading... --</option>';

    // Fetch Contracts based on branch name
    fetch(`get_contracts.php?branch=${encodeURIComponent(selectedBranchName)}`)
        .then(response => {
            if (!response.ok) throw new Error("Server Error");
            return response.json();
        })
        .then(data => {
            if (!contractSelect) return;
            
            contractSelect.innerHTML = '<option value="">-- Select Contract --</option>';
            
            if (data.contracts && data.contracts.length > 0) {
                data.contracts.forEach(contract => {
                    const option = document.createElement('option');
                    option.value = contract.contract_number;
                    option.textContent = contract.contract_number;
                    contractSelect.appendChild(option);
                });
            } else {
                contractSelect.innerHTML = '<option value="">No Approved Contracts</option>';
            }
        })
        .catch(error => {
            console.error('Error fetching contracts:', error);
            if (contractSelect) contractSelect.innerHTML = '<option value="">Error loading contracts</option>';
        });
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
