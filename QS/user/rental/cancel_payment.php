<?php
session_start();
include '../../config/config.php';

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}
$message = "";
$contractNumber   = $_POST['contract_number'] ?? null;
$transactionDates = $_POST['transaction_date'] ?? []; // multiple
$payment = null;

$userRole     = $_SESSION['user_role']   ?? '';
$userRegion   = $_SESSION['region']     ?? '';
$userArea     = $_SESSION['area']       ?? '';
$userMainzone = $_SESSION['mainzone']   ?? '';

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
// Step 2: Load contract numbers with at least one "Unpaid"
$contracts = $conn->query("
    SELECT DISTINCT contract_number, branch, mode_of_payment
    FROM transactional 
    WHERE status = 'Unpaid' $whereRole
    ORDER BY contract_number ASC
");

// Step 3: Load unpaid transaction dates for selected contract
$dates = [];
if ($contractNumber) {
    $stmt = $conn->prepare("
        SELECT DISTINCT transaction_date 
        FROM transactional 
        WHERE contract_number = ? $whereRole
        ORDER BY transaction_date ASC
    ");
    $stmt->bind_param("s", $contractNumber);
    $stmt->execute();
    $dates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Step 4: Load payment info for first selected date
if ($contractNumber && !empty($transactionDates)) {
    $stmt = $conn->prepare("
        SELECT * 
        FROM transactional 
        WHERE contract_number = ? AND transaction_date = ? $whereRole
    ");
    $stmt->bind_param("ss", $contractNumber, $transactionDates[0]);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
}

// Step 5: Handle cancel payment request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_payment'])) {
    $reason = trim($_POST['lessor_request_reason'] ?? '');

    if (empty($reason)) {
        $message = "❌ Please provide a reason for the cancel request.";
    } else {
        $updates = [
            "cancel_request_type"   => "cancel_payment",
            "cancel_request_status" => "Pending RM",
            "cancel_request_reason" => $reason
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
                  AND transaction_date = ? $whereRole";

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
            ? "✅ Cancel payment request submitted successfully for selected transaction dates."
            : "❌ Error submitting cancel request: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ML Rental - Cancel Payment Request</title>
   <!-- Bootstrap CSS -->
   <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/poppins.css">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar.css">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .card { border-radius: 16px; }
        .card-header { border-radius: 16px 16px 0 0 !important; }
        .bg-gradient-danger { background: linear-gradient(45deg, #dc3545, #b02a37); }
        .section-box { background: #f9f9f9; border: 1px solid #eee; border-radius: 12px; padding: 16px; }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="card shadow border-0">
        <div class="card-header bg-gradient-danger text-white d-flex align-items-center">
        <a href="modify_contract.php" 
                class="btn me-4 text-white" 
                style="border: 1px solid white;">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <i class="bi bi-x-circle me-2 fs-5"></i>
            <h5 class="mb-0 text-white">Request Cancel Payment</h5>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <!-- Step 1: Select Contract and Transaction Dates -->
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
                            <label class="form-label">Transaction Dates</label>
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
            <!-- Step 2: Show Payment Info -->
            <?php if ($payment): ?>
                <form method="POST">
                    <input type="hidden" name="contract_number" value="<?= htmlspecialchars($contractNumber) ?>">
                    <?php foreach ($transactionDates as $tDate): ?>
                        <input type="hidden" name="transaction_date[]" value="<?= htmlspecialchars($tDate) ?>">
                    <?php endforeach; ?>
                    <div class="section-box mb-3">
                        <h6 class="fw-bold text-primary"><i class="bi bi-credit-card-2-back me-1"></i> Current Payment Details</h6>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label">Amount</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($payment['amount_lessor'] ?? 'N/A') ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mode of Payment</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($payment['mode_of_payment'] ?? 'N/A') ?>" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Branch</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($payment['branch'] ?? 'N/A') ?>" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-danger">Reason for Cancel Payment Request</label>
                        <textarea name="lessor_request_reason" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="modify_contract.php" class="btn btn-outline-secondary me-2">Back</a>
                        <button type="submit" name="cancel_payment" class="btn btn-danger">
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
