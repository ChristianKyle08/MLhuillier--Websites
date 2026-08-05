<?php
session_start();
include '../../config/config.php';

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}
$message = "";
$dueData = null;

$userRole     = $_SESSION['user_role']   ?? '';
$userRegion   = $_SESSION['region']     ?? '';
$userArea     = $_SESSION['area']       ?? '';
$userMainzone = $_SESSION['mainzone']   ?? '';

$contractNumber   = $_POST['contract_number'] ?? null;
$transactionDates = $_POST['transaction_date'] ?? [];

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
        WHERE contract_number = ? 
          AND status = 'Unpaid' $whereRole
        ORDER BY transaction_date ASC
    ");
    $stmt->bind_param("s", $contractNumber);
    $stmt->execute();
    $dates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Step 4: Load current due dates for selected unpaid rows
$dueData = [];
if ($contractNumber && !empty($transactionDates)) {
    $placeholders = implode(',', array_fill(0, count($transactionDates), '?'));
    $types        = str_repeat("s", count($transactionDates) + 1);
    $params       = array_merge([$contractNumber], $transactionDates);

    $sql = "
        SELECT transaction_date, payment_due_date 
        FROM transactional 
        WHERE contract_number = ? 
          AND transaction_date IN ($placeholders)
          AND status = 'Unpaid' $whereRole
        ORDER BY transaction_date ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $dueData = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Step 5: Handle update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_due_date'])) {
    $reason = trim($_POST['lessor_request_reason'] ?? '');
    $newDue = $_POST['new_due_date'] ?? []; // array keyed by transaction_date

    if (empty($newDue)) {
        $message = "❌ Please select a new due date.";
    } elseif (empty($reason)) {
        $message = "❌ Please provide a reason.";
    } else {
        $sql = "
            UPDATE transactional 
            SET new_due_date = ?, 
                dueDate_request_type = 'change_due_date', 
                dueDate_request_status = 'Pending RM', 
                dueDate_request_reason = ?
            WHERE contract_number = ? 
              AND transaction_date = ? 
              AND status = 'Unpaid' $whereRole
        ";
        $stmt = $conn->prepare($sql);

        $success = true;
        foreach ($transactionDates as $tDate) {
            if (!empty($newDue[$tDate])) {
                $newDateVal = $newDue[$tDate];
                $stmt->bind_param("ssss", $newDateVal, $reason, $contractNumber, $tDate);
                if (!$stmt->execute()) {
                    $success = false;
                }
            }
        }

        $message = $success 
            ? "✅ Due date change request submitted successfully." 
            : "❌ Error updating due dates: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ML Rental - Request Change Due Date</title>
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
            <i class="bi bi-calendar-date me-2 fs-5"></i>
            <h5 class="mb-0 text-white">Request Change of Due Date</h5>
        </div>
        <div class="card-body">

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

            <?php if ($dueData): ?>
                <form method="POST">
                    <input type="hidden" name="contract_number" value="<?= htmlspecialchars($contractNumber) ?>">
                    <?php foreach ($transactionDates as $tDate): ?>
                        <input type="hidden" name="transaction_date[]" value="<?= htmlspecialchars($tDate) ?>">
                    <?php endforeach; ?>

                    <div class="section-box mb-3">
                        <h6 class="fw-bold text-primary"><i class="bi bi-clock-history me-1"></i> Due Dates</h6>

                        <?php foreach ($dueData as $row): ?>
                            <div class="row g-2 align-items-end mb-2">
                                <div class="col-md-4">
                                    <label class="form-label">Transaction Date</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($row['transaction_date']) ?>" readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">New Due Date</label>
                                    <input type="date" name="new_due_date[<?= htmlspecialchars($row['transaction_date']) ?>]" class="form-control" required>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-danger">Reason for Change Request</label>
                        <textarea name="lessor_request_reason" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="modify_contract.php" class="btn btn-outline-secondary me-2">Back</a>
                        <button type="submit" name="update_due_date" class="btn btn-primary">
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
