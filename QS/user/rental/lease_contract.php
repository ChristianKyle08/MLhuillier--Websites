<?php
session_start();
include '../../config/config.php';

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_pbb_btn'])) {
    if (!empty($_POST['contract_number']) && !empty($_POST['transaction_date']) && !empty($_POST['transaction_id'])) {
        $contractNumber   = mysqli_real_escape_string($conn, $_POST['contract_number']);
        $transactionDate  = mysqli_real_escape_string($conn, $_POST['transaction_date']);
        $transactionId    = (int) $_POST['transaction_id']; // integer for safety

        // Extract month and year
        $month = date('m', strtotime($transactionDate));
        $year  = date('Y', strtotime($transactionDate));

        // Update query
        $updateQuery = "
            UPDATE transactional 
            SET status = 'PBB' 
            WHERE contract_number = '$contractNumber'
              AND MONTH(transaction_date) = '$month'
              AND YEAR(transaction_date)  = '$year'
              AND id = $transactionId
        ";

        if (mysqli_query($conn, $updateQuery)) {
            // Success → Update status cell dynamically
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    var modal = new bootstrap.Modal(document.getElementById('pbbModal'));
                    document.getElementById('pbbModalBody').innerText = '✅ Transaction #{$transactionId} updated to Paid by Branch';
                    modal.show();

                    // Update the status cell text in table
                    let statusCell = document.querySelector('#status-{$transactionId}');
                    if (statusCell) {
                        statusCell.innerHTML = '<span class=\"badge bg-success\">PBB</span>';
                    }

                    // Close modal automatically after 2s
                    setTimeout(() => { modal.hide(); }, 2000);
                });
            </script>";
        } else {
            // Error Modal (stays open)
            $errorMsg = addslashes(mysqli_error($conn));
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    var modal = new bootstrap.Modal(document.getElementById('pbbModal'));
                    document.getElementById('pbbModalBody').innerText = '❌ Error updating record: {$errorMsg}';
                    modal.show();
                });
            </script>";
        }
    }
}
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
            <title>ML Rental - Lease of Contract</title>
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
            <div class="container py-1">
                <!-- Title -->
                <div class="text-center mb-3">
                    <h2 class="fw-bold text-danger">
                        <i class="bi bi-file-earmark-text me-2"></i> CONTRACT OF LEASE
                    </h2>
                </div>

                <div class="card shadow-sm border-0 rounded-4 p-4">
                    <div class="row g-3 align-items-end">
                        <!-- Branch Select -->
                        <div class="col-md-5">
                            <label for="branch" class="form-label fw-semibold">
                                <i class="bi bi-building me-1"></i> Select Branch
                            </label>
                            <input list="branchList" name="branch" id="branch"
                                class="form-control rounded-pill"
                                autocomplete="off" required onchange="updateKpxCode(this)"
                                value="<?php echo isset($_POST['branch']) ? htmlspecialchars($_POST['branch']) : ''; ?>">

                            <datalist id="branchList">
                                <?php
                                $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);
                                $userQuery = "
                                    SELECT roles, mainzone, area, region 
                                    FROM user_form 
                                    WHERE username = '$user_email' OR email = '$user_email'
                                ";
                                $resultUser = mysqli_query($conn, $userQuery);
                                $user = mysqli_fetch_assoc($resultUser);

                                $userRole     = $user['roles'] ?? '';
                                $userMainzone = $user['mainzone'] ?? '';
                                $userArea     = $user['area'] ?? '';
                                $userRegion   = $user['region'] ?? '';

                                // Common exclusion condition
                                $excludeCondition = "
                                    NOT EXISTS (
                                        SELECT 1 
                                        FROM transactional t2 
                                        WHERE t2.branch_id = t1.branch_id
                                        AND t2.status IN ('Termination Requested','Termination Reviewed','Termination Checked','Terminated')
                                    )
                                ";

                                // Build query depending on user role
                                if ($userRole === 'HO') {
                                    $transactional = "
                                        SELECT DISTINCT t1.branch, t1.region, t1.area, t1.kpx_code, t1.branch_id 
                                        FROM transactional t1
                                        WHERE t1.branch != '' 
                                        AND $excludeCondition
                                        ORDER BY t1.branch ASC
                                    ";
                                } elseif ($userRole === 'Am-Creator') {
                                    $transactional = "
                                        SELECT DISTINCT t1.branch, t1.region, t1.area, t1.kpx_code, t1.branch_id 
                                        FROM transactional t1
                                        WHERE t1.branch != '' 
                                        AND $excludeCondition
                                        AND t1.region = '$userRegion' 
                                        AND t1.area   = '$userArea'
                                        ORDER BY t1.branch ASC
                                    ";
                                } elseif ($userRole === 'Rm-Reviewer') {
                                    $transactional = "
                                        SELECT DISTINCT t1.branch, t1.region, t1.area, t1.kpx_code, t1.branch_id 
                                        FROM transactional t1
                                        WHERE t1.branch != '' 
                                        AND $excludeCondition
                                        AND t1.region = '$userRegion'
                                        ORDER BY t1.branch ASC
                                    ";
                                } elseif ($userRole === 'Auditor') {
                                    $transactional = "
                                        SELECT DISTINCT t1.branch, t1.region, t1.area, t1.kpx_code, t1.branch_id 
                                        FROM transactional t1
                                        WHERE t1.branch != '' 
                                        AND $excludeCondition
                                        ORDER BY t1.branch ASC
                                    ";
                                }
                                elseif ($userRole === 'Finance') {
                                    $transactional = "
                                        SELECT DISTINCT t1.branch, t1.region, t1.area, t1.kpx_code, t1.branch_id 
                                        FROM transactional t1
                                        WHERE t1.branch != '' 
                                        AND $excludeCondition
                                        AND t1.mode_of_payment = 'PDC'
                                        ORDER BY t1.branch ASC
                                    ";
                                } else {
                                    $transactional = "
                                        SELECT DISTINCT t1.branch, t1.region, t1.area, t1.kpx_code, t1.branch_id 
                                        FROM transactional t1
                                        WHERE t1.branch != '' 
                                        AND $excludeCondition
                                        AND t1.mainzone = '$userMainzone'
                                        ORDER BY t1.branch ASC
                                    ";
                                }

                                $resultBranch = mysqli_query($conn, $transactional);

                                if ($resultBranch && mysqli_num_rows($resultBranch) > 0) {
                                    while ($rowBranch = mysqli_fetch_assoc($resultBranch)) {
                                        $branchName  = htmlspecialchars($rowBranch['branch']);
                                        $branchRegion = htmlspecialchars($rowBranch['region']);
                                        $branchArea   = htmlspecialchars($rowBranch['area']);
                                        $kpxCode     = htmlspecialchars($rowBranch['kpx_code']);
                                        $branchId    = htmlspecialchars($rowBranch['branch_id']);

                                        echo "<option 
                                                value='{$branchName}' 
                                                data-kpx-code='{$kpxCode}' 
                                                data-branch-id='{$branchId}'>
                                                {$branchName} ({$branchRegion}, Area {$branchArea})
                                            </option>";
                                    }
                                }
                                ?>
                            </datalist>
                        </div>

                        <!-- Contract Number -->
                        <div class="col-md-5">
                            <label for="contractNumber" class="form-label fw-semibold">
                                <i class="bi bi-file-earmark-check me-1"></i> Contract Number
                            </label>
                            <select name="contractNumber" id="contractNumber" 
                                class="form-select rounded-pill" required>
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
                        <div class="col-md-2 col-auto">
                            <button type="submit" name="proceed_btn" id="proceed_btn" 
                                    class="btn btn-danger w-100 rounded-pill">
                                <i class="bi bi-arrow-right-circle me-1"></i> Proceed
                            </button>
                        </div>
                    </div>

                    <!-- Hidden Inputs -->
                    <input type="hidden" name="lessor_name" id="lessor_name"
                        value="<?php echo isset($_POST['lessor_name']) ? $_POST['lessor_name'] : '' ?>" readonly>
                    <input type="hidden" name="kpxCode" id="kpxCode" 
                        value="<?php echo isset($_POST['kpxCode']) ? $_POST['kpxCode'] : '' ?>">
                    <input type="hidden" name="branchId" id="branchId" 
                        value="<?php echo isset($_POST['branchId']) ? $_POST['branchId'] : '' ?>">
                </div>
                <?php
                if (isset($_POST['proceed_btn'])) {
                    $userRole     = $_SESSION['user_role']   ?? '';
                    $userRegion   = $_SESSION['region']      ?? '';
                    $userArea     = $_SESSION['area']        ?? '';
                    $userMainzone = $_SESSION['mainzone']    ?? '';

                    $branch = mysqli_real_escape_string($conn, $_POST['branch']);
                    $contractNumber = mysqli_real_escape_string($conn, $_POST['contractNumber']);

                    // ✅ Check if the selected contract is cancelled
                    $isCancelled = false;
                    $cancelReason = '';

                    $checkStatusQuery = "
                        SELECT status, cancel_request_reason 
                        FROM transactional 
                        WHERE contract_number = '$contractNumber' 
                        LIMIT 1";
                    $resultStatus = mysqli_query($conn, $checkStatusQuery);

                    if ($resultStatus && $rowStatus = mysqli_fetch_assoc($resultStatus)) {
                        if (strtolower($rowStatus['status']) === 'cancelled') {
                            $isCancelled = true;
                            $cancelReason = $rowStatus['cancel_request_reason'] ?? '';
                        }
                    }

                    // ✅ Fetch transactional details
                    $detailsQuery = "
                        SELECT 
                            id,
                            contract_number, 
                            branch, 
                            transaction_date,
                            CONCAT_WS(' ', l1_firstname, l1_middlename, l1_lastname) AS lessor1_fullname,
                            CONCAT_WS(' ', new_l1_firstname, new_l1_middlename, new_l1_lastname) AS new_lessor1_fullname,
                            CONCAT_WS(' ', l2_firstname, l2_middlename, l2_lastname) AS lessor2_fullname,
                            CONCAT_WS(' ', authorize_firstName, authorize_middleName, authorize_lastName) AS authorize_fullname,
                            CONCAT_WS(' ', new_authorize_firstName, new_authorize_middleName, new_authorize_lastName) AS new_authorize_fullname,
                            kpx_code, 
                            branch_id, 
                            start_date, 
                            end_date, 
                            amount, 
                            vat_type,
                            vat_amount,
                            wtax,
                            amount_lessor,
                            edit_amount_lessor,
                            mode_of_payment,
                            lessor_request_status,
                            cancel_request_reason,
                            rfp_by,
                            rfp_date,
                            status 
                        FROM transactional 
                        WHERE branch = '$branch' 
                        AND contract_number = '$contractNumber'
                        ORDER BY transaction_date ASC";

                    $resultDetails = mysqli_query($conn, $detailsQuery);

                    if ($resultDetails && mysqli_num_rows($resultDetails) > 0) {
                        $showAuthorizeColumn = false;
                        $showPaidByBranchCol = false;
                        $rows = [];

                        $currentMonthYear = date('Y-m');

                        while ($row = mysqli_fetch_assoc($resultDetails)) {
                            if (!empty(trim($row['authorize_fullname']))) {
                                $showAuthorizeColumn = true;
                            }

                            // ✅ Check if "Paid by Branch" should show
                            $transactionDate = !empty($row['transaction_date']) ? strtotime($row['transaction_date']) : null;
                            $rowMonthYear = $transactionDate ? date('Y-m', $transactionDate) : null;

                            if ($userRole === 'HO' && $row['status'] === 'Unpaid' && $transactionDate && $rowMonthYear < $currentMonthYear) {
                                $showPaidByBranchCol = true;
                            }

                            $rows[] = $row;
                        }
                    ?>
                    <div class="card shadow-sm border-0 rounded-4 mt-4 overflow-hidden" style="background-color: #ffffff;">
    <div class="card-header bg-white border-bottom-0 pb-3 pt-4 px-4 d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #f1f5f9 !important;">
        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center" style="letter-spacing: -0.3px;">
            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 me-3 d-flex align-items-center justify-content-center">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            Contract Ledger
        </h5>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
            <table class="table table-hover align-middle mb-0" style="white-space: nowrap;">
                <thead style="background-color: #f8fafc; position: sticky; top: 0; z-index: 10;">
                    <tr class="text-secondary" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 600;">
                        <th class="ps-4 py-3 border-bottom"><i class="bi bi-calendar-event me-1"></i> Transaction Date</th>
                        <th class="py-3 border-bottom"><i class="bi bi-person-vcard me-1"></i> Lessor Name</th>
                        
                        <?php if ($showAuthorizeColumn): ?>
                            <th class="py-3 border-bottom"><i class="bi bi-person-badge me-1"></i> Authorized to Claim</th>
                        <?php endif; ?>
                        
                        <th class="py-3 border-bottom text-end"><i class="bi bi-cash-stack me-1"></i> Monthly Rental</th>
                        <th class="py-3 border-bottom text-center"><i class="bi bi-tags me-1"></i> VAT Type</th>
                        <th class="py-3 border-bottom text-end"><i class="bi bi-percent me-1"></i> VAT Amt</th>
                        <th class="py-3 border-bottom text-end"><i class="bi bi-bank me-1"></i> WTax Amt</th>
                        <th class="py-3 border-bottom text-end"><i class="bi bi-wallet2 me-1"></i> Net to Lessor</th>
                        <th class="py-3 border-bottom text-center"><i class="bi bi-credit-card me-1"></i> Mode of Payment</th>
                        <th class="py-3 border-bottom text-center"><i class="bi bi-person text-danger me-1"></i> RFP By</th>
                        <th class="py-3 border-bottom text-center"><i class="bi bi-calendar-date text-danger me-1"></i> RFP Date</th>
                        <th class="py-3 border-bottom text-center"><i class="bi bi-circle-half me-1"></i> Status</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    <?php 
                        // ==========================================
                        // PRE-PROCESSING: Fill missing months
                        // ==========================================
                        
                        usort($rows, function($a, $b) {
                            return strtotime($a['transaction_date']) <=> strtotime($b['transaction_date']);
                        });

                        $completeRows = [];
                        $previousRow = null;

                        foreach ($rows as $row) {
                            if ($previousRow !== null && !empty($row['transaction_date']) && !empty($previousRow['transaction_date'])) {
                                $prevDate = new DateTime($previousRow['transaction_date']);
                                $currDate = new DateTime($row['transaction_date']);

                                $prevMonthStart = (clone $prevDate)->modify('first day of this month');
                                $currMonthStart = (clone $currDate)->modify('first day of this month');
                                $expectedNextMonth = (clone $prevMonthStart)->modify('+1 month');

                                while ($expectedNextMonth < $currMonthStart) {
                                    $fillerRow = $previousRow; 
                                    $fillerRow['transaction_date'] = $expectedNextMonth->format('Y-m-d');
                                    $fillerRow['status'] = 'Unpaid'; 
                                    $fillerRow['paid_by_branch'] = '';
                                    $fillerRow['rfp_by'] = ''; 
                                    $fillerRow['rfp_date'] = '';
                                    
                                    // ✅ Flag to highlight this row in the UI
                                    $fillerRow['is_missing_month'] = true; 
                                    
                                    $completeRows[] = $fillerRow;
                                    $expectedNextMonth->modify('+1 month');
                                }
                            }
                            $row['is_missing_month'] = false; // standard rows
                            $completeRows[] = $row;
                            $previousRow = $row;
                        }
                        
                        // ==========================================
                        // RENDER TABLE
                        // ==========================================
                        
                        $currentTimestamp = time();

                        foreach ($completeRows as $rowDetails): 
                            // ✅ Handle lessor name
                            $isApproved = (($rowDetails['lessor_request_status'] ?? '') === 'Approved');
                            
                            if ($isApproved && !empty(trim($rowDetails['new_lessor1_fullname'] ?? ''))) {
                                $lessorFullName = trim($rowDetails['new_lessor1_fullname']);
                            } else {
                                $lessorFullName = trim($rowDetails['lessor1_fullname'] ?? '');
                            }

                            if (!empty(trim($rowDetails['lessor2_fullname'] ?? ''))) {
                                $lessorFullName .= " & " . trim($rowDetails['lessor2_fullname']);
                            }

                            // ✅ Handle authorize name
                            $authorizeNameHtml = "<span class='text-muted fst-italic'>N/A</span>";
                            if ($isApproved) {
                                if (!empty(trim($rowDetails['new_authorize_fullname'] ?? ''))) {
                                    $authorizeNameHtml = htmlspecialchars(trim($rowDetails['new_authorize_fullname']));
                                } elseif (!empty(trim($rowDetails['authorize_fullname'] ?? ''))) {
                                    $authorizeNameHtml = htmlspecialchars(trim($rowDetails['authorize_fullname']));
                                }
                            } else {
                                if (!empty(trim($rowDetails['authorize_fullname'] ?? ''))) {
                                    $authorizeNameHtml = htmlspecialchars(trim($rowDetails['authorize_fullname']));
                                }
                            }

                            // ✅ Determine Row Status & Late Payment Logic
                            $status = $rowDetails['status'] ?? 'Unpaid';
                            $isUnpaidOrLate = false;
                            
                            if (in_array($status, ['Unpaid', 'Pending', '']) && !empty($rowDetails['transaction_date'])) {
                                if (strtotime($rowDetails['transaction_date']) < $currentTimestamp) {
                                    $isUnpaidOrLate = true;
                                }
                            }

                            // ✅ Styling for Missing/Skipped Months
                            $isMissing = $rowDetails['is_missing_month'];
                            
                            // Premium styling for missing row (subtle amber tone)
                            $rowClass = $isMissing ? 'bg-warning bg-opacity-10 border-start border-4 border-warning border-opacity-50' : '';
                    ?>
                        <tr class="<?= $rowClass ?> border-bottom" style="transition: background-color 0.2s ease;">
                            <td class="ps-4 text-dark align-middle">
                                <span class="fw-medium text-dark d-block">
                                    <?= !empty($rowDetails['transaction_date']) ? date('F d, Y', strtotime($rowDetails['transaction_date'])) : '' ?>
                                </span>
                                <?php if ($isMissing): ?>
                                    <span class="badge bg-warning bg-opacity-25 text-dark mt-1 px-2 py-1 border border-warning border-opacity-25 shadow-sm" style="font-size: 0.65rem; font-weight: 600; letter-spacing: 0.3px;">
                                        <i class="bi bi-exclamation-triangle-fill me-1 text-warning"></i> Missed Entry
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="align-middle">
                                <span class="text-dark fw-medium <?= $isMissing ? 'opacity-75' : '' ?>"><?= htmlspecialchars($lessorFullName) ?></span>
                            </td>

                            <?php if ($showAuthorizeColumn): ?>
                                <td class="align-middle <?= $isMissing ? 'opacity-75' : '' ?>"><?= $authorizeNameHtml ?></td>
                            <?php endif; ?>

                            <td class="text-end fw-semibold text-dark align-middle">
                                <?php if($isMissing): ?>
                                    <span class="text-muted opacity-50 fw-light"> - </span>
                                <?php else: ?>
                                    <span class="text-muted fw-normal me-1">₱</span><?= number_format($rowDetails['amount'] ?? 0, 2) ?>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-center align-middle">
                                <?php if($isMissing): ?>
                                    <span class="text-muted opacity-50 fw-light"> - </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border px-2 py-1 fw-medium" style="font-size: 0.75rem;"><?= htmlspecialchars($rowDetails['vat_type'] ?? 'N/A') ?></span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-end text-dark align-middle">
                                <?php if($isMissing): ?>
                                    <span class="text-muted opacity-50 fw-light"> - </span>
                                <?php else: ?>
                                    <span class="text-muted me-1">₱</span><?= number_format($rowDetails['vat_amount'] ?? 0, 2) ?>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-end text-dark align-middle">
                                <?php if($isMissing): ?>
                                    <span class="text-muted opacity-50 fw-light"> - </span>
                                <?php else: ?>
                                    <span class="text-muted me-1">₱</span><?= number_format($rowDetails['wtax'] ?? 0, 2) ?>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-end fw-bold text-success align-middle">
                                <?php if($isMissing): ?>
                                    <span class="text-muted opacity-50 fw-light"> - </span>
                                <?php else: ?>
                                    <span class="text-success text-opacity-50 fw-normal me-1">₱</span><?= number_format($rowDetails['edit_amount_lessor'] ?? 0, 2) ?>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-center text-muted align-middle">
                                <?php if($isMissing): ?>
                                    <span class="text-muted opacity-50 fw-light"> - </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border-0 px-2 py-1 rounded-pill" style="font-weight: 500; font-size: 0.75rem;">
                                        <?= !empty($rowDetails['mode_of_payment']) ? htmlspecialchars($rowDetails['mode_of_payment']) : '—' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-center text-muted align-middle" style="font-size: 0.85rem;">
                                <?php if($isMissing): ?>
                                    <span class="text-muted opacity-50 fw-light"> - </span>
                                <?php else: ?>
                                    <?= !empty($rowDetails['rfp_by']) ? htmlspecialchars($rowDetails['rfp_by']) : '—' ?>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-center text-muted align-middle" style="font-size: 0.85rem;">
                                <?php if($isMissing): ?>
                                    <span class="text-muted opacity-50 fw-light"> - </span>
                                <?php else: ?>
                                    <?= !empty($rowDetails['rfp_date']) ? htmlspecialchars($rowDetails['rfp_date']) : '—' ?>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-center align-middle">
                                <?php if ($status === 'Cancelled'): ?>
                                    <span class='badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-2 fw-medium shadow-sm'>
                                        <i class='bi bi-x-octagon me-1'></i> Cancelled
                                        <i class='bi bi-info-circle-fill ms-2 opacity-75' style='cursor:pointer;' data-bs-toggle='modal' data-bs-target='#reasonModal<?= $rowDetails['id'] ?? '0' ?>'></i>
                                    </span>

                                    <div class='modal fade text-start' id='reasonModal<?= $rowDetails['id'] ?? '0' ?>' tabindex='-1' aria-hidden='true'>
                                        <div class='modal-dialog modal-dialog-centered'>
                                            <div class='modal-content border-0 shadow-lg rounded-4'>
                                                <div class='modal-header bg-danger bg-opacity-10 border-0'>
                                                    <h5 class='modal-title text-danger fw-bold fs-6'><i class="bi bi-exclamation-circle-fill me-2"></i>Cancellation Reason</h5>
                                                    <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                                                </div>
                                                <div class='modal-body text-dark p-4 text-wrap'>
                                                    <p class='mb-0 text-muted' style="line-height: 1.6;"><?= htmlspecialchars($rowDetails['cancel_request_reason'] ?? 'No reason provided') ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php elseif ($isMissing): ?>
                                    <span> - </span>
                                <?php elseif ($status === 'Paid'): ?>
                                    <span class='badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2 fw-medium shadow-sm'><i class='bi bi-check-circle-fill me-1'></i> Paid</span>
                                <?php elseif ($status === 'PBB'): ?>
                                    <span class='badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2 fw-medium shadow-sm'><i class='bi bi-check-circle-fill me-1'></i> Paid by Branch</span>
                                <?php elseif ($status === 'Terminated'): ?>
                                    <span class='badge bg-dark bg-opacity-10 text-dark border border-dark border-opacity-25 rounded-pill px-3 py-2 fw-medium shadow-sm'><i class='bi bi-slash-circle me-1'></i> Terminated</span>
                                <?php elseif (in_array($status, ['Unpaid', 'Pending'])): ?>
                                    <span class='badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-50 rounded-pill px-3 py-2 fw-medium shadow-sm'><i class='bi bi-hourglass-split me-1'></i> <?= htmlspecialchars($status) ?></span>
                                <?php else: ?>
                                    <span class='badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-3 py-2 fw-medium shadow-sm'><i class='bi bi-info-circle me-1'></i> <?= htmlspecialchars($status) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
                    <?php
                        } else {
                            echo "<div class='alert alert-warning mt-4'>
                                    <i class='bi bi-exclamation-triangle me-2'></i>
                                    No contract details found for the selected branch and contract number.
                                </div>";
                        }
                    }
                ?>
            </div>
        </form>
    </div>
    <!-- PBB Update Modal -->
    <div class="modal fade" id="pbbModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title text-white"><i class="bi bi-check-circle me-2"></i> Update</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="pbbModalBody">
                <!-- Message will be injected here -->
            </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmPbbModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title text-white"><i class="bi bi-question-circle me-2"></i> Confirm Action</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p class="fw-semibold fs-6">Are you sure this transaction is paid by branch?</p>
                <p id="transactionIdDisplay" class="text-danger fw-bold" style="display: none;"></p>
            </div>
            <div class="modal-footer justify-content-center">
                <form method="POST" id="confirmPbbForm">
                <input type="hidden" name="contract_number" id="confirmContractNumber">
                <input type="hidden" name="transaction_date" id="confirmTransactionDate">
                <input type="hidden" name="transaction_id" id="confirmTransactionId">
                <button type="submit" name="confirm_pbb_btn" class="btn btn-danger rounded-pill">
                    <i class="bi bi-check-circle me-1"></i> Yes
                </button>
                <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">
                    Cancel
                </button>
                </form>
            </div>
            </div>
        </div>
    </div>

    <!-- Modal Structure -->
    <div id="fileModal" class="file-modal" style="display:none;">
        <div class="file-modal-content">
            <span class="file-modal-close">&times;</span>
            <div id="filePreview"></div>
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
        function confirmPbb(id, contractNumber, transactionDate) {
            document.getElementById('transactionIdDisplay').innerText = "Transaction ID: " + id;
            document.getElementById('confirmTransactionId').value = id;
            document.getElementById('confirmContractNumber').value = contractNumber;
            document.getElementById('confirmTransactionDate').value = transactionDate;

            var modal = new bootstrap.Modal(document.getElementById('confirmPbbModal'));
            modal.show();
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Handle single file click (open directly in new tab)
            document.querySelectorAll('.view-single-file').forEach(function (element) {
                element.addEventListener('click', function (event) {
                    event.preventDefault();
                    const fileContent = event.target.dataset.fileContent;
                    const mimeType = event.target.dataset.mimeType;
                    const fileName = event.target.dataset.fileName;

                    // Convert base64 to binary string
                    const binaryString = atob(fileContent);

                    // Create a Uint8Array from the binary string
                    const uint8Array = new Uint8Array(binaryString.length);
                    for (let i = 0; i < binaryString.length; i++) {
                        uint8Array[i] = binaryString.charCodeAt(i);
                    }

                    // Create a Blob from the Uint8Array
                    const blob = new Blob([uint8Array], { type: mimeType });

                    // Create a URL for the Blob and use it to open in a new tab
                    const blobUrl = URL.createObjectURL(blob);

                    // Open the blob URL in a new tab
                    window.open(blobUrl, '_blank');
                });
            });

            // Handle multiple files (display in modal)
            document.querySelectorAll('.view-contracts').forEach(function (element) {
                element.addEventListener('click', function (event) {
                    event.preventDefault();
                    const files = JSON.parse(event.target.dataset.contractFiles);
                    const filePreview = document.getElementById('filePreview');

                    // Clear previous file links
                    filePreview.innerHTML = '';

                    // Add each file link to the modal
                    files.forEach(function (file) {
                        const linkElement = document.createElement('a');
                        linkElement.href = '#';
                        linkElement.innerHTML = file.icon + ' ' + file.file;
                        linkElement.style.display = 'block';
                        linkElement.dataset.fileContent = file.content;
                        linkElement.dataset.mimeType = file.mimeType;
                        linkElement.dataset.fileName = file.file;

                        linkElement.addEventListener('click', function (event) {
                            event.preventDefault();
                            const fileContent = event.target.dataset.fileContent;
                            const mimeType = event.target.dataset.mimeType;
                            const fileName = event.target.dataset.fileName;

                            // Convert base64 to binary string
                            const binaryString = atob(fileContent);

                            // Create a Uint8Array from the binary string
                            const uint8Array = new Uint8Array(binaryString.length);
                            for (let i = 0; i < binaryString.length; i++) {
                                uint8Array[i] = binaryString.charCodeAt(i);
                            }

                            // Create a Blob from the Uint8Array
                            const blob = new Blob([uint8Array], { type: mimeType });

                            // Create a URL for the Blob and use it to open in a new tab
                            const blobUrl = URL.createObjectURL(blob);

                            // Open the blob URL in a new tab
                            window.open(blobUrl, '_blank');
                        });

                        filePreview.appendChild(linkElement);
                    });

                    // Show the modal
                    document.getElementById('fileModal').style.display = 'block';
                });
            });

            // Close modal when the close button is clicked
            document.querySelector('.file-modal-close').addEventListener('click', function () {
                document.getElementById('fileModal').style.display = 'none';
            });

            // Close modal when clicking outside of the modal content
            window.addEventListener('click', function (event) {
                const modal = document.getElementById('fileModal');
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
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
        function updateKpxCode(branchInput) {
            // Get the selected branch value
            const selectedBranch = branchInput.value;

            // Fetch contract numbers for the selected branch using AJAX
            fetch(`get_contracts.php?branch=${encodeURIComponent(selectedBranch)}`)
                .then(response => response.json())
                .then(data => {
                    const contractSelect = document.getElementById('contractNumber');
                    contractSelect.innerHTML = '';  // Clear the contract dropdown
                    
                    // Add an empty option
                    const emptyOption = document.createElement('option');
                    emptyOption.value = '';
                    contractSelect.appendChild(emptyOption);

                    // Loop through the contracts and add options
                    data.contracts.forEach(contract => {
                        const option = document.createElement('option');
                        option.value = contract.contract_number;
                        option.textContent = contract.contract_number;
                        contractSelect.appendChild(option);
                    });

                    // If a contract number was previously selected, re-select it
                    const previousContractNumber = "<?php echo isset($_POST['contractNumber']) ? $_POST['contractNumber'] : ''; ?>";
                    if (previousContractNumber) {
                        const existingOption = Array.from(contractSelect.options).find(option => option.value === previousContractNumber);
                        if (existingOption) {
                            contractSelect.value = existingOption.value;
                        }
                    }

                    // Update hidden inputs with corresponding data attributes
                    const kpxCode = branchInput.selectedOptions[0].dataset.kpxCode;
                    const branchId = branchInput.selectedOptions[0].dataset.branchId;
                    document.getElementById('kpxCode').value = kpxCode;
                    document.getElementById('branchId').value = branchId;
                })
            .catch(error => console.error('Error fetching contracts:', error));
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
