<?php
include '../../config/config.php';

if (isset($_POST['apply_changes'])) {

    // =========================
    // INPUTS
    // =========================
    $newDay         = isset($_POST['payment_due_day']) ? intval($_POST['payment_due_day']) : 0;
    $contractId     = isset($_POST['contract_id']) ? intval($_POST['contract_id']) : 0;
    $contractNumber = isset($_POST['contract_number']) ? trim($_POST['contract_number']) : '';

    if ($contractId === 0) {
        echo "<div class='alert alert-danger mt-2'>Missing contract ID.</div>";
        exit;
    }

    if (empty($contractNumber)) {
        echo "<div class='alert alert-danger mt-2'>Missing contract number.</div>";
        exit;
    }

    if ($newDay < 1 || $newDay > 31) {
        echo "<div class='alert alert-warning mt-2'>Invalid day selected. Must be between 1 and 31.</div>";
        exit;
    }

    // =========================
    // FETCH CURRENT DATE
    // =========================
    $stmt = mysqli_prepare($conn, "
        SELECT payment_due_date 
        FROM create_contract 
        WHERE id = ?
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "i", $contractId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $currentDate);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (!$currentDate) {
        echo "<div class='alert alert-danger mt-2'>Current payment due date not found.</div>";
        exit;
    }

    // =========================
    // BUILD NEW DATE
    // =========================
    $year  = date('Y', strtotime($currentDate));
    $month = date('m', strtotime($currentDate));
    $newDate = date('Y-m-d', strtotime("$year-$month-$newDay"));

    // Validate actual calendar date
    if ((int)date('j', strtotime($newDate)) !== $newDay) {
        echo "<div class='alert alert-warning mt-2'>Invalid day for the selected month.</div>";
        exit;
    }

    // =========================
    // UPDATE create_contract
    // =========================
    $updateContract = mysqli_prepare($conn, "
        UPDATE create_contract 
        SET payment_due_date = ?
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($updateContract, "si", $newDate, $contractId);

    if (!mysqli_stmt_execute($updateContract)) {
        echo "<div class='alert alert-danger mt-2'>Failed to update contract.</div>";
        mysqli_stmt_close($updateContract);
        exit;
    }
    mysqli_stmt_close($updateContract);

   // =========================
// UPDATE escalation (correct DATE format)
// =========================
$escQuery = mysqli_query($conn, "
SELECT id, monthly_due_date 
FROM escalation 
WHERE col_number = '" . mysqli_real_escape_string($conn, $contractNumber) . "'
");

if ($escQuery && mysqli_num_rows($escQuery) > 0) {
while ($escRow = mysqli_fetch_assoc($escQuery)) {
    $escId = $escRow['id'];
    $currentMonthDue = $escRow['monthly_due_date'];

    if (!empty($currentMonthDue)) {
        $year  = date('Y', strtotime($currentMonthDue));
        $month = date('m', strtotime($currentMonthDue));
    } else {
        // fallback to current year/month
        $year  = date('Y');
        $month = date('m');
    }

    $newEscDate = date('Y-m-d', strtotime("$year-$month-$newDay"));

    // Update each escalation row
    $updateEsc = mysqli_prepare($conn, "
        UPDATE escalation 
        SET monthly_due_date = ? 
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($updateEsc, "si", $newEscDate, $escId);
    mysqli_stmt_execute($updateEsc);
    mysqli_stmt_close($updateEsc);
}
}


    // =========================
    // SUCCESS MESSAGE
    // =========================
    $ordinalDay = ordinal($newDay);

    echo "
        <div class='alert alert-success alert-dismissible fade show mt-2' role='alert'>
            Payment due date updated successfully:
            <ul class='mb-0'>
                <li><strong>Create Contract:</strong> Every {$ordinalDay} day</li>
                <li><strong>Escalation (Active Rows):</strong> Month due date updated</li>
            </ul>
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>
    ";

    exit;
}

// =========================
// ORDINAL FUNCTION
// =========================
function ordinal($number) {
    if (($number % 100) >= 11 && ($number % 100) <= 13) {
        return $number . 'th';
    }
    switch ($number % 10) {
        case 1: return $number . 'st';
        case 2: return $number . 'nd';
        case 3: return $number . 'rd';
        default: return $number . 'th';
    }
}
