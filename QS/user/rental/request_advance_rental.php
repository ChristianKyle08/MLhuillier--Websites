<?php
ob_start();
session_start();

if (!isset($_SESSION['user_email']) || empty($_SESSION['user_email'])) {
    header('Location: login_form.php');
    exit();
}

include '../../config/config.php';

$user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);
$userRole = $_SESSION['user_role'] ?? 'Am-Creator'; // Defaulting for testing

// ==========================================
// 1. HANDLE FORM SUBMISSIONS & STATUS UPDATES
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $response = ['status' => 'error', 'message' => 'Invalid Request'];

    try {
        // Am-Creator: Process the request (Batch processing for selected dates)
        if ($action === 'request_advance' && $userRole === 'Am-Creator') {
            $mop = mysqli_real_escape_string($conn, $_POST['mode_of_payment']);
            $transaction_ids_json = $_POST['transaction_ids'] ?? '[]';
            $transaction_ids = json_decode($transaction_ids_json, true);
            
            if (!empty($transaction_ids) && is_array($transaction_ids)) {
                // Sanitize IDs
                $ids = implode(',', array_map('intval', $transaction_ids));
                
                // Updates tag to Requested, saves MOP, and sets the advance rental amount to the row's original amount
                $sql = "UPDATE transactional SET 
                        advance_tag = 'Requested', 
                        mode_of_payment = '$mop', 
                        advance_rental_amount = edit_amount_lessor 
                        WHERE id IN ($ids)";
                        
                if(mysqli_query($conn, $sql)) {
                    $response = ['status' => 'success', 'message' => 'Advance rental requested successfully for selected dates. Forwarded to RM-Reviewer.'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'No transaction dates selected.'];
            }
        } 
        // Rm-Reviewer: Reviews and forwards to VPO-Checker
        elseif ($action === 'forward_vpoc' && $userRole === 'Rm-Reviewer') {
            $transaction_id = intval($_POST['transaction_id']);
            $sql = "UPDATE transactional SET advance_tag = 'Reviewed' WHERE id = $transaction_id AND advance_tag = 'Requested'";
            if(mysqli_query($conn, $sql)) $response = ['status' => 'success', 'message' => 'Request Reviewed. Forwarded to VPO-Checker.'];
        }
        // Vpo-Checker: Checks and forwards to Vpo-Approver
        elseif ($action === 'forward_vpoa' && $userRole === 'Vpo-Checker') {
            $transaction_id = intval($_POST['transaction_id']);
            $sql = "UPDATE transactional SET advance_tag = 'Checked' WHERE id = $transaction_id AND advance_tag = 'Reviewed'";
            if(mysqli_query($conn, $sql)) $response = ['status' => 'success', 'message' => 'Request Checked. Forwarded to VPO-Approver.'];
        }
        // Vpo-Approver: Final Approval
        elseif ($action === 'approve_advance' && $userRole === 'Vpo-Approver') {
            $transaction_id = intval($_POST['transaction_id']);
            $sql = "UPDATE transactional SET advance_tag = 'Approved' WHERE id = $transaction_id AND advance_tag = 'Checked'";
            if(mysqli_query($conn, $sql)) $response = ['status' => 'success', 'message' => 'Advance Rental Approved.'];
        }
        // Return Workflow for Reviewers/Checkers/Approvers
        elseif ($action === 'return_request' && in_array($userRole, ['Rm-Reviewer', 'Vpo-Checker', 'Vpo-Approver'])) {
            $transaction_id = intval($_POST['transaction_id']);
            $sql = "UPDATE transactional SET advance_tag = 'Returned' WHERE id = $transaction_id";
            if(mysqli_query($conn, $sql)) $response = ['status' => 'success', 'message' => 'Request returned to AM-Creator.'];
        }

        echo json_encode($response);
        exit();
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit();
    }
}

// ==========================================
// 2. FETCH DATA & ROLE-BASED FILTER LOGIC
// ==========================================
$region_filter = isset($_GET['region_filter']) ? mysqli_real_escape_string($conn, $_GET['region_filter']) : '';
$area_filter   = isset($_GET['area_filter']) ? mysqli_real_escape_string($conn, $_GET['area_filter']) : '';
$branch_filter = isset($_GET['branch_filter']) ? mysqli_real_escape_string($conn, $_GET['branch_filter']) : '';

// Get user's scope assuming standard columns
$userScopeQuery = "SELECT * FROM user_form WHERE email = '$user_email' LIMIT 1";
$userScopeResult = mysqli_query($conn, $userScopeQuery);
$userData = $userScopeResult ? mysqli_fetch_assoc($userScopeResult) : ['area' => '', 'region' => '', 'mainzone' => ''];
$userArea = $userData['area'] ?? '';
$userRegion = $userData['region'] ?? '';
$userMainzone = $userData['mainzone'] ?? '';

$baseWhere = "1=1";
$dropdownQuery = "";

// Filter trapping based on user roles and requested viewing conditions
if ($userRole === 'Am-Creator') {
    $baseWhere .= " AND region = '$userRegion' AND area = '$userArea'";
    $dropdownQuery = "SELECT DISTINCT branch FROM transactional WHERE area = '$userArea' ORDER BY branch ASC";
} elseif ($userRole === 'Rm-Reviewer') {
    // Displays ALL transaction requests where advance_tag is 'Requested'
    $baseWhere .= " AND advance_tag = 'Requested'";
    $dropdownQuery = "SELECT DISTINCT region, area, branch FROM transactional WHERE region = '$userRegion' AND advance_tag = 'Requested' ORDER BY region ASC, area ASC, branch ASC";
} elseif ($userRole === 'Vpo-Checker') {
    // Displays all transaction requests where advance_tag is 'Reviewed'
    $baseWhere .= " AND advance_tag = 'Reviewed'";
    $dropdownQuery = "SELECT DISTINCT region, area, branch FROM transactional WHERE mainzone = '$userMainzone' AND advance_tag = 'Reviewed' ORDER BY region ASC, area ASC, branch ASC";
} elseif ($userRole === 'Vpo-Approver') {
    // Displays all transaction requests where advance_tag is 'Checked'
    $baseWhere .= " AND advance_tag = 'Checked'";
    $dropdownQuery = "SELECT DISTINCT region, area, branch FROM transactional WHERE mainzone = '$userMainzone' AND advance_tag = 'Checked' ORDER BY region ASC, area ASC, branch ASC";
}

// Optional Filters across roles
if (!empty($region_filter)) {
    $baseWhere .= " AND region = '$region_filter'";
}
if (!empty($area_filter)) {
    $baseWhere .= " AND area = '$area_filter'";
}
if (!empty($branch_filter)) {
    $baseWhere .= " AND branch = '$branch_filter'";
}

// Transaction Query
$transactionalQuery = "SELECT * FROM transactional WHERE $baseWhere ORDER BY transaction_date ASC, id ASC";
$transactionalResult = mysqli_query($conn, $transactionalQuery);

// ==========================================
// 3. SUMMARY STATS (powers the header stat chips)
// ==========================================
$statsQuery = "SELECT COUNT(*) AS total_count, COALESCE(SUM(edit_amount_lessor), 0) AS total_amount FROM transactional WHERE $baseWhere";
$statsResult = mysqli_query($conn, $statsQuery);
$statsRow = $statsResult ? mysqli_fetch_assoc($statsResult) : ['total_count' => 0, 'total_amount' => 0];
$recordCount = (int)($statsRow['total_count'] ?? 0);
$recordTotalAmount = (float)($statsRow['total_amount'] ?? 0);

// ==========================================
// 4. WORKFLOW PIPELINE STAGES
// ==========================================
$workflowStages = [
    ['label' => 'Requested', 'role' => 'Am-Creator',    'icon' => 'bi-send-fill'],
    ['label' => 'Reviewed',  'role' => 'Rm-Reviewer',   'icon' => 'bi-search'],
    ['label' => 'Checked',   'role' => 'Vpo-Checker',   'icon' => 'bi-check2-square'],
    ['label' => 'Approved',  'role' => 'Vpo-Approver',  'icon' => 'bi-check-circle-fill'],
];
$roleStageIndex = ['Am-Creator' => 0, 'Rm-Reviewer' => 1, 'Vpo-Checker' => 2, 'Vpo-Approver' => 3];
$activeStageIndex = $roleStageIndex[$userRole] ?? -1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <link rel="shortcut icon" href="../../assets/images/rental_logo.png" type="image/x-icon">
    <title>Advance Rental Management</title>
    
    <link href="../../assets/css/poppins.css" rel="stylesheet">
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar.css">

    <style>
        :root {
            --theme-red: #d70c0c;
            --theme-red-dark: #a10909;
            --theme-red-soft: rgba(215, 12, 12, 0.09);
            --theme-ink: #1a1c22;
            --theme-ink-soft: #2b2e37;
            --theme-dark: #333333;
            --theme-light: #ffffff;
            --theme-gray: #f4f7f6;
            --surface-muted: #f8f9fb;
            --border-soft: #ececf0;
            --text-muted: #6b7280;
            --text-faint: #a1a5ac;
            --shadow-soft: 0 4px 24px rgba(20, 20, 30, 0.07);
            --shadow-lift: 0 16px 40px rgba(20, 20, 30, 0.16);
            --radius-lg: 16px;
            --radius-md: 10px;
            --radius-sm: 8px;
        }

        body { font-family: 'Poppins', sans-serif; background: linear-gradient(180deg, #f6f8f9 0%, #eef1f3 100%); color: var(--theme-dark); -webkit-font-smoothing: antialiased; }
        .main-content { padding: 30px; margin-left: 350px; transition: all 0.3s ease; }

        /* ---------- Page heading ---------- */
        .page-heading h2 { letter-spacing: -0.03em; }
        .page-heading p { font-size: 0.92rem; }

        .role-badge { display: inline-flex; align-items: center; gap: 7px; padding: 9px 16px; border-radius: 999px; font-weight: 500; font-size: 0.8rem; background: linear-gradient(135deg, var(--theme-ink), var(--theme-ink-soft)); color: var(--theme-light); box-shadow: var(--shadow-soft); letter-spacing: 0.01em; }
        .role-badge i { color: var(--theme-red); }

        /* ---------- Workflow stepper (pipeline overview) ---------- */
        .workflow-stepper { display: flex; align-items: flex-start; background: var(--theme-light); border-radius: var(--radius-lg); box-shadow: var(--shadow-soft); border: 1px solid var(--border-soft); padding: 26px 34px 22px; margin-bottom: 26px; }
        .stepper-step { display: flex; flex-direction: column; align-items: center; width: 130px; flex-shrink: 0; text-align: center; }
        .stepper-connector { flex: 1; height: 2px; background: var(--border-soft); margin-top: 23px; transition: background 0.3s ease; }
        .stepper-connector.completed { background: var(--theme-red); }
        .stepper-circle { width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: var(--surface-muted); border: 2px solid var(--border-soft); color: var(--text-faint); font-size: 1.05rem; margin-bottom: 10px; transition: all 0.3s ease; }
        .stepper-step.completed .stepper-circle { background: var(--theme-red); border-color: var(--theme-red); color: #fff; }
        .stepper-step.active .stepper-circle { background: var(--theme-ink); border-color: var(--theme-ink); color: #fff; box-shadow: 0 0 0 6px var(--theme-red-soft); transform: scale(1.08); }
        .stepper-label { font-size: 0.86rem; font-weight: 600; color: var(--theme-dark); }
        .stepper-step.upcoming .stepper-label { color: var(--text-faint); }
        .stepper-role { font-size: 0.7rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 2px; }
        .stepper-step.active .stepper-role { color: var(--theme-red); font-weight: 600; }

        .premium-card { border: none; border-radius: var(--radius-lg); box-shadow: var(--shadow-soft); background: var(--theme-light); overflow: hidden; margin-bottom: 30px; }
        .card-header-modern { background: linear-gradient(135deg, var(--theme-ink) 0%, var(--theme-ink-soft) 100%); color: var(--theme-light); padding: 24px 30px; border-top: 4px solid var(--theme-red); display: flex; justify-content: space-between; align-items: center; }
        .card-header-modern h4 { margin: 0; font-weight: 600; font-size: 1.15rem; letter-spacing: -0.01em; }

        .stat-chip { display: inline-flex; align-items: center; gap: 8px; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.16); padding: 8px 14px; border-radius: 999px; font-size: 0.8rem; color: #f1f1f2; white-space: nowrap; }
        .stat-chip strong { color: #fff; font-weight: 700; }
        .stat-chip .stat-divider { width: 1px; height: 12px; background: rgba(255, 255, 255, 0.22); }

        .clear-filter-link { font-size: 0.78rem; color: rgba(255, 255, 255, 0.75); text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: color 0.2s ease; }
        .clear-filter-link:hover { color: #fff; text-decoration: underline; }

        .table-modern { width: 100%; margin-top: 8px; }
        .table-modern thead th { border-bottom: 2px solid var(--border-soft); color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 0.74rem; letter-spacing: 0.04em; padding: 14px 15px; background: var(--surface-muted); }
        .table-modern tbody tr { border-bottom: 1px solid var(--border-soft); transition: background 0.2s ease; }
        .table-modern tbody tr:hover { background: #fdf5f5; }
        .table-modern tbody td { padding: 15px; vertical-align: middle; font-size: 0.9rem; }
        .table-modern .amount-cell { font-variant-numeric: tabular-nums; }
        .table-modern .badge.bg-secondary { background-color: #eef0f2 !important; color: #667085; font-weight: 600; border-radius: 999px; padding: 7px 14px; font-size: 0.76rem; }

        .badge-premium { display: inline-flex; align-items: center; padding: 7px 14px; border-radius: 999px; font-weight: 600; font-size: 0.76rem; letter-spacing: 0.01em; border: 1px solid transparent; }
        .badge-requested { background-color: #fff3cd; color: #8a6100; border-color: #ffe4a3; }
        .badge-reviewed { background-color: #e0f2fe; color: #075985; border-color: #bce4fb; }
        .badge-checked { background-color: #ede9fe; color: #5b21b6; border-color: #ddd3fc; }
        .badge-approved { background-color: #dcfce7; color: #166534; border-color: #bbf0cd; }
        .badge-returned { background-color: #fee2e2; color: #a91717; border-color: #fbcaca; }

        .btn-theme-red { background: linear-gradient(135deg, var(--theme-red), var(--theme-red-dark)); color: var(--theme-light); border: none; border-radius: var(--radius-sm); padding: 10px 22px; font-weight: 500; transition: all 0.25s ease; box-shadow: 0 4px 14px rgba(215, 12, 12, 0.22); }
        .btn-theme-red:hover { transform: translateY(-1px); color: var(--theme-light); box-shadow: 0 8px 20px rgba(215, 12, 12, 0.32); }
        .btn-theme-red:disabled { background: #e8b3b3; box-shadow: none; transform: none; cursor: not-allowed; }
        .btn-action { padding: 7px 14px; border-radius: var(--radius-sm); font-size: 0.82rem; margin-right: 5px; margin-bottom: 5px; font-weight: 500; transition: all 0.2s ease; }
        .btn-action:hover { transform: translateY(-1px); }

        .modal-content { border-radius: var(--radius-lg); border: none; box-shadow: var(--shadow-lift); }
        .modal-header { border-bottom: 1px solid var(--border-soft); padding: 22px 26px; background: linear-gradient(135deg, var(--theme-ink), var(--theme-ink-soft)); color: var(--theme-light); }
        .modal-header .btn-close { filter: invert(1); }
        .modal-body { padding: 26px; }
        .modal-footer { border-top: 1px solid var(--border-soft); padding: 18px 26px; }
        
        .form-control-premium { border-radius: var(--radius-sm); border: 1px solid #ddd; padding: 10px 15px; }
        .form-control-premium:focus { border-color: var(--theme-red); box-shadow: 0 0 0 0.2rem var(--theme-red-soft); }

        .details-box { background: var(--surface-muted); border-radius: var(--radius-md); padding: 20px; margin-top: 20px; border: 1px dashed #ddd; }
        .text-theme-red { color: var(--theme-red) !important; }

        /* ---------- Empty state ---------- */
        .empty-state i { font-size: 2.4rem; color: var(--text-faint); }
        .empty-state p { margin-top: 10px; font-size: 0.92rem; }

        /* ---------- Motion & accessibility ---------- */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
        .premium-card, .workflow-stepper { animation: fadeInUp 0.4s ease; }
        a:focus-visible, button:focus-visible, select:focus-visible, input:focus-visible { outline: 2px solid var(--theme-red); outline-offset: 2px; }

        @media (prefers-reduced-motion: reduce) {
            .premium-card, .workflow-stepper, .btn-theme-red, .btn-action, .stepper-circle, .stepper-connector { animation: none !important; transition: none !important; }
        }

        @media (max-width: 768px) {
            .workflow-stepper { flex-wrap: wrap; gap: 18px 8px; padding: 22px; }
            .stepper-connector { display: none; }
            .stepper-step { width: 42%; }
        }
    </style>
</head>
<body>
    <?php include ('navbar.php'); ?>
    <div class="main-content">
        <button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
            <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
            <span class="fw-normal">Menu</span>
        </button>
        <div class="container py-1"></div>
        <div class="d-flex justify-content-between align-items-center mb-4 page-heading">
            <div>
                <h2 class="fw-bold mb-0 text-theme-red">Advance Rental Pipeline</h2>
                <p class="text-muted mb-0">Process and review advance rental requests</p>
            </div>
            <div>
                <span class="role-badge">
                    <i class="bi bi-person-badge"></i> Role: <?php echo htmlspecialchars($userRole); ?>
                </span>
            </div>
        </div>

        <!-- Workflow Pipeline Overview -->
        <div class="workflow-stepper">
            <?php foreach ($workflowStages as $i => $stage): ?>
                <?php
                    $stepClass = 'upcoming';
                    if ($activeStageIndex >= 0) {
                        if ($i < $activeStageIndex) $stepClass = 'completed';
                        elseif ($i === $activeStageIndex) $stepClass = 'active';
                    }
                    $stepIcon = ($stepClass === 'completed') ? 'bi-check2' : $stage['icon'];
                ?>
                <div class="stepper-step <?php echo $stepClass; ?>">
                    <div class="stepper-circle"><i class="bi <?php echo $stepIcon; ?>"></i></div>
                    <div class="stepper-label"><?php echo htmlspecialchars($stage['label']); ?></div>
                    <div class="stepper-role"><?php echo htmlspecialchars($stage['role']); ?></div>
                </div>
                <?php if ($i < count($workflowStages) - 1): ?>
                    <div class="stepper-connector <?php echo ($stepClass === 'completed') ? 'completed' : ''; ?>"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>

        <div class="premium-card">
            <div class="card-header-modern flex-wrap gap-3">
                <div class="d-flex align-items-center flex-wrap gap-3 w-100 justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <h4 class="m-0"><i class="bi bi-building me-2"></i> Branch Records</h4>
                        
                        <!-- ROLE-BASED DYNAMIC FILTERS FORM -->
                        <form method="GET" class="m-0 d-flex align-items-center gap-2 flex-wrap">
                            
                            <!-- 1. REGION FILTER (For VPO-Checker and VPO-Approver) -->
                            <?php if (in_array($userRole, ['Vpo-Checker', 'Vpo-Approver'])): ?>
                                <select name="region_filter" class="form-select form-control-premium text-dark py-2" style="width: auto; min-width: 170px;" onchange="this.form.submit()">
                                    <option value="">-- All Regions --</option>
                                    <?php
                                    $regWhere = "mainzone = '$userMainzone' " . ($userRole === 'Vpo-Checker' ? "AND advance_tag = 'Reviewed'" : "AND advance_tag = 'Checked'");
                                    $regionSql = "SELECT DISTINCT region FROM transactional WHERE $regWhere AND region IS NOT NULL AND region != '' ORDER BY region ASC";
                                    $regRes = mysqli_query($conn, $regionSql);
                                    if ($regRes && mysqli_num_rows($regRes) > 0) {
                                        while ($rRow = mysqli_fetch_assoc($regRes)) {
                                            $rName = $rRow['region'];
                                            $selected = ($region_filter === $rName) ? 'selected' : '';
                                            echo "<option value='" . htmlspecialchars($rName) . "' $selected>" . htmlspecialchars($rName) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            <?php endif; ?>

                            <!-- 2. AREA FILTER (For RM-Reviewer, VPO-Checker, VPO-Approver) -->
                            <?php if (in_array($userRole, ['Rm-Reviewer', 'Vpo-Checker', 'Vpo-Approver'])): ?>
                                <select name="area_filter" class="form-select form-control-premium text-dark py-2" style="width: auto; min-width: 170px;" onchange="this.form.submit()">
                                    <option value="">-- All Areas --</option>
                                    <?php
                                    $areaWhere = "1=1";
                                    if ($userRole === 'Rm-Reviewer') {
                                        $areaWhere .= " AND region = '$userRegion' AND advance_tag = 'Requested'";
                                    } elseif ($userRole === 'Vpo-Checker') {
                                        $areaWhere .= " AND mainzone = '$userMainzone' AND advance_tag = 'Reviewed'";
                                    } elseif ($userRole === 'Vpo-Approver') {
                                        $areaWhere .= " AND mainzone = '$userMainzone' AND advance_tag = 'Checked'";
                                    }
                                    if (!empty($region_filter)) {
                                        $areaWhere .= " AND region = '$region_filter'";
                                    }
                                    $areaSql = "SELECT DISTINCT area FROM transactional WHERE $areaWhere AND area IS NOT NULL AND area != '' ORDER BY area ASC";
                                    $areaRes = mysqli_query($conn, $areaSql);
                                    if ($areaRes && mysqli_num_rows($areaRes) > 0) {
                                        while ($aRow = mysqli_fetch_assoc($areaRes)) {
                                            $aName = $aRow['area'];
                                            $selected = ($area_filter === $aName) ? 'selected' : '';
                                            echo "<option value='" . htmlspecialchars($aName) . "' $selected>" . htmlspecialchars($aName) . "</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            <?php endif; ?>

                            <!-- CLEAR FILTERS BUTTON -->
                            <?php if (!empty($region_filter) || !empty($area_filter) || !empty($branch_filter)): ?>
                                <a href="?" class="btn btn-outline-light btn-sm py-2 px-3" style="border-radius: 10px;">
                                    <i class="bi bi-x-circle me-1"></i> Clear
                                </a>
                            <?php endif; ?>

                        </form>

                        <?php if (!empty($branch_filter)): ?>
                            <a href="?" class="clear-filter-link"><i class="bi bi-x-circle"></i> Clear filter</a>
                        <?php endif; ?>

                        <span class="stat-chip">
                            <i class="bi bi-list-check"></i> <strong><?php echo number_format($recordCount); ?></strong> record<?php echo $recordCount === 1 ? '' : 's'; ?>
                            <span class="stat-divider"></span>
                            <i class="bi bi-cash-stack"></i> <strong>₱<?php echo number_format($recordTotalAmount, 2); ?></strong>
                        </span>
                    </div>

                    <!-- Single Process Button for AM-Creator -->
                    <?php if($userRole === 'Am-Creator'): ?>
                        <button class="btn btn-theme-red" id="globalProcessBtn" disabled>
                            <i class="bi bi-box-arrow-up-right me-1"></i> Process Request
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive p-4">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <?php if($userRole === 'Am-Creator'): ?>
                                    <th style="width: 50px;">
                                        <input type="checkbox" id="selectAllCheckbox" class="form-check-input">
                                    </th>
                                <?php endif; ?>
                                <th>Contract #</th>
                                <th>Branch</th>
                                <th>Transaction Date</th>
                                <th>Advance Total</th>
                                <th>Payment Method</th>
                                <th>Workflow Status</th>
                                <?php if($userRole !== 'Am-Creator'): ?>
                                    <th>Action</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($transactionalResult && mysqli_num_rows($transactionalResult) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($transactionalResult)): ?>
                                    <?php 
                                        $status = $row['advance_tag'] ?? ''; 
                                        $canSelect = ($userRole === 'Am-Creator' && (empty($status) || $status === 'Returned'));
                                    ?>
                                    <tr>
                                        <?php if($userRole === 'Am-Creator'): ?>
                                            <td>
                                                <?php if($canSelect): ?>
                                                    <input type="checkbox" class="form-check-input row-checkbox" 
                                                           value="<?php echo $row['id']; ?>" 
                                                           data-date="<?php echo htmlspecialchars(date('F d, Y', strtotime($row['transaction_date']))); ?>"
                                                           data-amount="<?php echo htmlspecialchars($row['edit_amount_lessor']); ?>"
                                                           data-contract="<?php echo htmlspecialchars($row['contract_number']); ?>">
                                                <?php else: ?>
                                                    <input type="checkbox" class="form-check-input" disabled>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                        <td><strong><?php echo htmlspecialchars($row['contract_number'] ?? 'N/A'); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['branch'] ?? 'N/A'); ?></td>
                                        <td><?php echo !empty($row['transaction_date']) ? htmlspecialchars(date('F d, Y', strtotime($row['transaction_date']))) : 'N/A'; ?></td>
                                        <td class="text-theme-red fw-bold amount-cell">₱<?php echo number_format($row['edit_amount_lessor'] ?? 0, 2); ?></td>
                                        <td><?php echo htmlspecialchars($row['mode_of_payment'] ?? '-'); ?></td>
                                        <td>
                                            <?php 
                                                if(empty($status)) echo '<span class="badge bg-secondary">Unprocessed</span>';
                                                elseif($status == 'Requested') echo '<span class="badge badge-premium badge-requested"><i class="bi bi-hourglass-split me-1"></i> Requested</span>';
                                                elseif($status == 'Reviewed') echo '<span class="badge badge-premium badge-reviewed"><i class="bi bi-search me-1"></i> Reviewed</span>';
                                                elseif($status == 'Checked') echo '<span class="badge badge-premium badge-checked"><i class="bi bi-check2-square me-1"></i> Checked</span>';
                                                elseif($status == 'Approved') echo '<span class="badge badge-premium badge-approved"><i class="bi bi-check-circle-fill me-1"></i> Approved</span>';
                                                elseif($status == 'Returned') echo '<span class="badge badge-premium badge-returned"><i class="bi bi-arrow-return-left me-1"></i> Returned</span>';
                                                else echo '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
                                            ?>
                                        </td>
                                        <?php if($userRole !== 'Am-Creator'): ?>
                                        <td>
                                            <?php if($userRole === 'Rm-Reviewer' && $status === 'Requested'): ?>
                                                <button class="btn btn-action btn-success" onclick="processWorkflow(<?php echo $row['id']; ?>, 'forward_vpoc', 'Mark as Reviewed & Forward to VPO-Checker?')">Review</button>
                                                <button class="btn btn-action btn-outline-danger" onclick="processWorkflow(<?php echo $row['id']; ?>, 'return_request', 'Return to AM-Creator?')">Return</button>
                                            
                                            <?php elseif($userRole === 'Vpo-Checker' && $status === 'Reviewed'): ?>
                                                <button class="btn btn-action btn-primary" onclick="processWorkflow(<?php echo $row['id']; ?>, 'forward_vpoa', 'Mark as Checked & Forward to VPO-Approver?')">Check</button>
                                                <button class="btn btn-action btn-outline-danger" onclick="processWorkflow(<?php echo $row['id']; ?>, 'return_request', 'Return to AM-Creator?')">Return</button>
                                            
                                            <?php elseif($userRole === 'Vpo-Approver' && $status === 'Checked'): ?>
                                                <button class="btn btn-action btn-success" onclick="processWorkflow(<?php echo $row['id']; ?>, 'approve_advance', 'Finalize and Approve Advance Rental?')">Approve</button>
                                                <button class="btn btn-action btn-outline-danger" onclick="processWorkflow(<?php echo $row['id']; ?>, 'return_request', 'Return to AM-Creator?')">Return</button>
                                            
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 0.85rem;"><i class="bi bi-lock-fill"></i> No Action Needed</span>
                                            <?php endif; ?>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo ($userRole === 'Am-Creator') ? '7' : '8'; ?>" class="text-center text-muted py-5">
                                        <div class="empty-state">
                                            <i class="bi bi-inbox d-block mb-2"></i>
                                            <p class="mb-0">
                                            <?php 
                                                if ($userRole === 'Am-Creator' && empty($branch_filter)) {
                                                    echo 'Please select a branch to view records.';
                                                } else {
                                                    echo 'No pending transaction requests found.';
                                                }
                                            ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- AM-CREATOR MODAL: Process Request -->
    <?php if($userRole === 'Am-Creator'): ?>
    <div class="modal fade" id="requestAdvanceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-send-fill me-2"></i>Process Advance Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="advanceRequestForm">
                        <input type="hidden" name="action" value="request_advance">
                        <input type="hidden" name="transaction_ids" id="hiddenTransactionIds" value="">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Contract Number</label>
                            <input type="text" class="form-control form-control-premium bg-light" id="modalContractNum" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Selected Transaction Dates</label>
                            <div class="border rounded p-3 bg-light" id="dynamicDatesContainer" style="max-height: 150px; overflow-y: auto;">
                                <!-- Dynamic items injected via Javascript -->
                            </div>
                            <small class="text-muted mt-2 d-block">These are the specific dates selected for processing.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Payment Method</label>
                            <select class="form-select form-control-premium" name="mode_of_payment" required>
                                <option value="" selected disabled>Select payment method</option>
                                <option value="Payment Solution">Payment Solution</option>
                                <option value="Mcash Wallet">Mcash Wallet</option>
                                <option value="PDC">PDC</option>
                                <option value="RTA">RTA</option>
                                <option value="Cash">Cash</option>
                            </select>
                        </div>

                        <div class="details-box" id="calculationBox">
                            <div class="d-flex justify-content-between border-top pt-2 mt-2">
                                <span class="fw-bold fs-5">Advance Request Total:</span>
                                <span class="fw-bold fs-5 text-theme-red" id="displayTotalAmount">₱0.00</span>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-theme-red" id="btnSubmitRequest">Confirm & Forward</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

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
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/sweetalert2/dist/sweetalert2.all.min.js"></script>

    <script>
        // ==========================================
        // UI INTERACTION: AM-CREATOR MULTIPLE SELECTION
        // ==========================================
        const globalProcessBtn = document.getElementById('globalProcessBtn');
        const selectAllCb = document.getElementById('selectAllCheckbox');
        const rowCbsArray = Array.from(document.querySelectorAll('.row-checkbox'));

        // Toggle button state based on selected checkboxes
        function updateGlobalButtonState() {
            if (!globalProcessBtn) return;
            const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
            globalProcessBtn.disabled = (checkedCount === 0);
        }

        // Allow selection of any month in any order
        rowCbsArray.forEach((cb) => {
            cb.addEventListener('change', function() {
                updateGlobalButtonState();
            });
        });

        // Select All Handler
        if (selectAllCb) {
            selectAllCb.addEventListener('change', function() {
                rowCbsArray.forEach(cb => {
                    if (!cb.disabled) cb.checked = selectAllCb.checked;
                });
                updateGlobalButtonState();
            });
        }

        // Open Modal and Populate dynamically
        if (globalProcessBtn) {
            globalProcessBtn.addEventListener('click', function() {
                const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
                let totalAmount = 0;
                let datesHtml = '';
                let transactionIds = [];
                let contractAssigned = '';

                checkedBoxes.forEach(cb => {
                    const id = cb.value;
                    const date = cb.getAttribute('data-date');
                    const amount = parseFloat(cb.getAttribute('data-amount')) || 0;
                    
                    if(contractAssigned === '') contractAssigned = cb.getAttribute('data-contract');

                    transactionIds.push(id);
                    totalAmount += amount;

                    datesHtml += `
                        <div class="d-flex justify-content-between mb-1 border-bottom pb-1">
                            <span><i class="bi bi-calendar-check me-2 text-success"></i>${date}</span>
                            <span class="fw-bold">₱${amount.toLocaleString(undefined, {minimumFractionDigits: 2})}</span>
                        </div>`;
                });

                document.getElementById('modalContractNum').value = contractAssigned;
                document.getElementById('dynamicDatesContainer').innerHTML = datesHtml;
                document.getElementById('displayTotalAmount').innerText = '₱' + totalAmount.toLocaleString(undefined, {minimumFractionDigits: 2});
                document.getElementById('hiddenTransactionIds').value = JSON.stringify(transactionIds);

                const modalElement = document.getElementById('requestAdvanceModal');
                const modalInstance = new bootstrap.Modal(modalElement);
                modalInstance.show();
            });
        }

        // ==========================================
        // FORM SUBMISSION: AM-CREATOR
        // ==========================================
        const btnSubmitRequest = document.getElementById('btnSubmitRequest');
        if(btnSubmitRequest) {
            btnSubmitRequest.addEventListener('click', function() {
                const form = document.getElementById('advanceRequestForm');
                if(!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                const formData = new FormData(form);

                Swal.fire({
                    title: 'Finalize Bulk Request?',
                    text: "This will process the selected request(s) and forward them to the RM-Reviewer.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#d70c0c',
                    cancelButtonColor: '#333',
                    confirmButtonText: 'Yes, Confirm'
                }).then((result) => {
                    if (result.isConfirmed) {
                        submitAction(formData);
                    }
                });
            });
        }

        // ==========================================
        // WORKFLOW PROCESSING: RM, VPO-C, VPO-A
        // ==========================================
        function processWorkflow(transactionId, actionName, confirmText) {
            Swal.fire({
                title: 'Confirm Workflow Action',
                text: confirmText,
                icon: actionName === 'return_request' ? 'warning' : 'question',
                showCancelButton: true,
                confirmButtonColor: actionName === 'return_request' ? '#d70c0c' : '#28a745',
                cancelButtonColor: '#333',
                confirmButtonText: 'Proceed'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('action', actionName);
                    formData.append('transaction_id', transactionId);
                    submitAction(formData);
                }
            });
        }

        // ==========================================
        // AJAX HANDLER
        // ==========================================
        function submitAction(formData) {
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    Swal.fire('Success!', data.message, 'success').then(() => {
                        location.reload(); 
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Something went wrong processing the request.', 'error');
            });
        }

        const toggleBtn = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebarMenu');

        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
            });
        }

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