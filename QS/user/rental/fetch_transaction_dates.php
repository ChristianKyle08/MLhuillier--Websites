<?php
session_start();
include '../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contractNumber   = $_POST['contract_number'] ?? '';
    $remarks          = $_POST['remarks'] ?? '';
    $transactionDates = $_POST['transaction_dates'] ?? '[]';

    // New lessor name inputs
    $newFirst  = $_POST['l1_firstname'] ?? null;
    $newMiddle = $_POST['l1_middlename'] ?? null;
    $newLast   = $_POST['l1_lastname'] ?? null;
    $newGender = $_POST['l1_gender'] ?? null;

    $dates = json_decode($transactionDates, true);

    if ($contractNumber && $remarks && is_array($dates) && count($dates) > 0) {
        $stmt = $conn->prepare("
            UPDATE transactional
            SET lessor_request_type   = 'change_lessor_name',
                lessor_request_status = 'Pending RM',
                lessor_request_reason = ?,
                new_l1_firstname      = ?,
                new_l1_middlename     = ?,
                new_l1_lastname       = ?,
                new_l1_gender         = ?
            WHERE contract_number     = ?
              AND transaction_date    = ?
        ");

        if ($stmt) {
            foreach ($dates as $date) {
                $stmt->bind_param(
                    "sssssss",
                    $remarks,
                    $newFirst,
                    $newMiddle,
                    $newLast,
                    $newGender,
                    $contractNumber,
                    $date
                );
                $stmt->execute();
            }
            $stmt->close();

            echo json_encode(["success" => true, "message" => "Request and new lessor name saved successfully!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error preparing statement: " . $conn->error]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Invalid data. Please try again."]);
    }
    exit;
}
?>
