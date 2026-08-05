<?php
session_start();
include '../../config/config.php';

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}
$message = "";
$lessor = null;

$userRole     = $_SESSION['user_role']   ?? '';
$userRegion   = $_SESSION['region']     ?? '';
$userArea     = $_SESSION['area']       ?? '';
$userMainzone = $_SESSION['mainzone']   ?? '';

$contractNumber   = $_POST['contract_number'] ?? null;
$transactionDates = $_POST['transaction_date'] ?? []; // multiple

// Step 1: Role-based WHERE clause
$whereRole = "";
switch ($userRole) {
    case 'Am-Creator':
        // base it on region and area
        $whereRole = "AND region = '". $conn->real_escape_string($userRegion) ."' AND area = '". $conn->real_escape_string($userArea) ."'";
        break;
    case 'Rm-Reviewer':
        // base it on region
        $whereRole = "AND region = '". $conn->real_escape_string($userRegion) ."'";
        break;
    case 'Vpo-Checker':
    case 'Vpo-Reviewer':
    case 'Vpo-Approver':
    case 'Auditor':
    case 'Finance':
        // base it on mainzone
        $whereRole = "AND mainzone = '". $conn->real_escape_string($userMainzone) ."'";
        break;
    case 'HO':
        // display all
        $whereRole = ""; 
        break;
    default:
        $whereRole = ""; // other roles see all
}

$contracts = $conn->query("
    SELECT DISTINCT contract_number, branch, mode_of_payment
    FROM transactional 
    WHERE status = 'Unpaid' $whereRole
    ORDER BY contract_number ASC
");

// Step 2: Load unpaid transaction dates for selected contract, filtered by role
$dates = [];
if ($contractNumber) {
    $stmt = $conn->prepare("
        SELECT DISTINCT transaction_date 
        FROM transactional 
        WHERE contract_number = ? 
          AND status = 'Unpaid' $whereRole
        ORDER BY transaction_date ASC
    ");
    $stmt->bind_param("s", $contractNumber);
    $stmt->execute();
    $dates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Step 3: Load current lessor/authorized mobiles (first selected unpaid date)
if ($contractNumber && !empty($transactionDates)) {
    $stmt = $conn->prepare("
        SELECT * 
        FROM transactional 
        WHERE contract_number = ? 
          AND transaction_date = ? 
          AND status = 'Unpaid' $whereRole
    ");
    $stmt->bind_param("ss", $contractNumber, $transactionDates[0]);
    $stmt->execute();
    $lessor = $stmt->get_result()->fetch_assoc();
}

// Step 4: Handle update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_mobile'])) {
    $reason = trim($_POST['lessor_request_reason'] ?? '');

    if (empty($reason)) {
        $message = "❌ Please provide a reason.";
    } else {
        $updates = [
            "new_authorize_mobileNumber" => $_POST["new_authorize_mobileNumber"] ?? null,
            "new_mobile_number_l1"       => $_POST["new_mobile_number_l1"] ?? null,
            "new_mobile_number_l2"       => $_POST["new_mobile_number_l2"] ?? null,
            "new_mobile_number_l3"       => $_POST["new_mobile_number_l3"] ?? null,
            "new_mobile_number_l4"       => $_POST["new_mobile_number_l4"] ?? null,
            "new_mobile_number_l5"       => $_POST["new_mobile_number_l5"] ?? null,
            "mobile_request_type"        => "change_mobile",
            "mobile_request_status"      => "Pending RM",
            "mobile_request_reason"      => $reason
        ];

        $setParts = [];
        $values   = [];
        foreach ($updates as $col => $val) {
            $setParts[] = "$col = ?";
            $values[]   = $val;
        }

        $sql = "UPDATE transactional 
                SET " . implode(", ", $setParts) . " 
                WHERE contract_number = ? 
                  AND transaction_date = ? 
                  AND status = 'Unpaid' $whereRole";

        $stmt = $conn->prepare($sql);
        $types = str_repeat("s", count($values)) . "ss";

        $success = true;
        foreach ($transactionDates as $tDate) {
            $bindValues = $values;
            $bindValues[] = $contractNumber;
            $bindValues[] = $tDate;

            $stmt->bind_param($types, ...$bindValues);
            if (!$stmt->execute()) {
                $success = false;
            }
        }

        $message = $success 
            ? "✅ Mobile number change request submitted successfully." 
            : "❌ Error updating mobile numbers: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ML Rental - Request Change Mobile</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/poppins.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar.css">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .card { border-radius: 16px; }
        .card-header { border-radius: 16px 16px 0 0 !important; }
        .bg-gradient-primary { background: linear-gradient(45deg, #0d6efd, #0b5ed7); }
        .section-box { background: #f9f9f9; border: 1px solid #eee; border-radius: 12px; padding: 16px; }
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="card shadow border-0">
        <div class="card-header bg-gradient-primary text-white d-flex align-items-center">
        <a href="modify_contract.php" 
                class="btn me-4 text-white" 
                style="border: 1px solid white;">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <i class="bi bi-telephone me-2 fs-5"></i>
            <h5 class="mb-0 text-white">Request Change of Mobile Numbers</h5>
        </div>
        <div class="card-body">
        <!-- Big Notice -->
        <div class="alert alert-danger d-flex align-items-center border-0 shadow-sm rounded-3 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1">Important Reminder!</h5>
                    <p class="mb-0">Do not forget to update the lessor <strong>Mobile Number</strong> in the <strong>Edit Lessor Profile</strong> page after or before submitting this request.</p>
                </div>
                <a href="edit_lessor_profile.php" class="btn btn-danger ms-auto d-flex align-items-center">
                    <i class="bi bi-pencil-square me-1"></i> Edit Lessor Profile
                </a>
            </div>
            <?php if ($message): ?>
                <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <!-- Step 1: Select Contract and Transaction Date -->
            <form method="POST" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Contract Number</label>
                        <select name="contract_number" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Select Contract --</option>
                            <?php while ($row = $contracts->fetch_assoc()): ?>
                                <option value="<?= htmlspecialchars($row['contract_number']) ?>" 
                                    <?= ($row['contract_number'] == $contractNumber) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['contract_number']) ?> 
                                    - <?= htmlspecialchars($row['branch'] ?? 'N/A') ?> 
                                    - <?= htmlspecialchars($row['mode_of_payment'] ?? 'N/A') ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <?php if ($contractNumber): ?>
                        <div class="col-md-6">
                            <label class="form-label">Unpaid Transaction Dates</label>
                            <select name="transaction_date[]" class="form-select" multiple size="5" onchange="this.form.submit()">
                                <?php foreach ($dates as $d): ?>
                                    <option value="<?= $d['transaction_date'] ?>" <?= in_array($d['transaction_date'], $transactionDates) ? 'selected' : '' ?>>
                                        <?= $d['transaction_date'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Hold CTRL (CMD on Mac) to select multiple.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </form>

            <!-- Step 2: Show Update Form -->
            <?php if ($lessor): ?>
                <form method="POST">
                    <input type="hidden" name="contract_number" value="<?= htmlspecialchars($contractNumber) ?>">
                    <?php foreach ($transactionDates as $tDate): ?>
                        <input type="hidden" name="transaction_date[]" value="<?= htmlspecialchars($tDate) ?>">
                    <?php endforeach; ?>

                    <!-- Authorized Person Mobile -->
                    <div class="section-box mb-3">
                        <h6 class="fw-bold text-primary"><i class="bi bi-person-badge me-1"></i> Authorized Person</h6>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Current Mobile</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($lessor["authorize_mobileNumber"]) ?>" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">New Mobile</label>
                                <input type="text" name="new_authorize_mobileNumber" class="form-control" maxlength="11" pattern="\d{11}" placeholder="11 digits" title="Enter exactly 11 digits">
                            </div>
                        </div>
                    </div>

                    <!-- Lessor Mobiles -->
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php if (!empty($lessor["mobile_number_l{$i}"])): ?>
                            <div class="section-box mb-3">
                                <h6 class="fw-bold text-success"><i class="bi bi-person-lines-fill me-1"></i> Lessor <?= $i ?></h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Current Mobile</label>
                                        <input type="text" class="form-control" value="<?= htmlspecialchars($lessor["mobile_number_l{$i}"]) ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">New Mobile</label>
                                        <input type="text" name="new_mobile_number_l<?= $i ?>" class="form-control" maxlength="11" pattern="\d{11}" placeholder="11 digits" title="Enter exactly 11 digits">
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-danger">Reason for Change Request</label>
                        <textarea name="lessor_request_reason" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="modify_contract.php" class="btn btn-outline-secondary me-2">Back</a>
                        <button type="submit" name="update_mobile" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Submit Request
                        </button>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="../../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
