<?php
session_start();
include('../../config/config.php');
header('Content-Type: application/json');

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // ✅ Logged in user
    if (!isset($_SESSION['user_name'])) {
        throw new Exception('User not logged in.');
    }
    $prepared_by = $_SESSION['user_name'];

    // ✅ Current date
    $prepared_date = date('Y-m-d');

    $contract_number = trim($_POST['contract_number'] ?? '');
    $check_date_raw  = trim($_POST['check_date'] ?? '');
    $check_number    = trim($_POST['check_number'] ?? '');
    $bank_name        = trim($_POST['bank_name'] ?? '');
    $bank_account     = trim($_POST['bank_account'] ?? '');

    if (
        empty($contract_number) ||
        empty($check_date_raw) ||
        empty($check_number) ||
        empty($bank_name) ||
        empty($bank_account)
    ) {
        throw new Exception('Missing required fields');
    }

    /**
     * Convert "January 15, 2025" → Date object safely
     */
    $dateObj = DateTime::createFromFormat('F d, Y', $check_date_raw);
    if (!$dateObj) {
        throw new Exception('Invalid check date format');
    }

    $check_date_db = $dateObj->format('Y-m-d');
    $month = (int)$dateObj->format('m');
    $year  = (int)$dateObj->format('Y');

    /**
     * ✅ Update query
     */
    $sql = "
        UPDATE transactional
        SET
            check_date = ?,
            check_number = ?,
            bank_name = ?,
            bank_accNumber = ?,
            pdc_status = 'Ready for pickup',
            pdc_prepared_by = ?,
            pdc_prepared_date = ?
        WHERE contract_number = ?
          AND MONTH(transaction_date) = ?
          AND YEAR(transaction_date) = ?
    ";

    $stmt = $conn->prepare($sql);

    // ✅ Correct bind_param (NO SPACES)
    $stmt->bind_param(
        "sssssssii",
        $check_date_db,
        $check_number,
        $bank_name,
        $bank_account,
        $prepared_by,
        $prepared_date,
        $contract_number,
        $month,
        $year
    );

    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('No record was updated. Check matching conditions.');
    }

    echo json_encode(['success' => true]);

    $stmt->close();
    $conn->close();

} catch (Throwable $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
