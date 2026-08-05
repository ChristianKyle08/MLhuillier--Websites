<?php
include '../../config/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect data from the POST request
    $kpx_code = mysqli_real_escape_string($conn, $_POST['kpx_code']);
    $branchId = mysqli_real_escape_string($conn, $_POST['branchId']);
    $contract_number = mysqli_real_escape_string($conn, $_POST['contract_number']);
    $series = mysqli_real_escape_string($conn, $_POST['series']);
    $mainzone = mysqli_real_escape_string($conn, $_POST['mainzone']);
    $zone = mysqli_real_escape_string($conn, $_POST['zone']);
    $branch_code = mysqli_real_escape_string($conn, $_POST['branch_code']);
    $branch = mysqli_real_escape_string($conn, $_POST['branch']);
    $region = mysqli_real_escape_string($conn, $_POST['region']);
    $area = mysqli_real_escape_string($conn, $_POST['area']);
    $corporateName = isset($_POST['corporateName']) ? mysqli_real_escape_string($conn, trim($_POST['corporateName'])) : '';
    $lessor_type = isset($_POST['lessor_type']) ? mysqli_real_escape_string($conn, trim($_POST['lessor_type'])) : '';
    $l1_firstname = isset($_POST['l1_firstname']) ? mysqli_real_escape_string($conn, trim($_POST['l1_firstname'])) : '';
    $l1_middlename = isset($_POST['l1_middlename']) ? mysqli_real_escape_string($conn, trim($_POST['l1_middlename'])) : '';
    $l1_lastname = isset($_POST['l1_lastname']) ? mysqli_real_escape_string($conn, trim($_POST['l1_lastname'])) : '';
    $l1_gender = isset($_POST['l1_gender']) ? mysqli_real_escape_string($conn, trim($_POST['l1_gender'])) : '';
    $l2_firstname = isset($_POST['l2_firstname']) ? mysqli_real_escape_string($conn, trim($_POST['l2_firstname'])) : '';
    $l2_middlename = isset($_POST['l2_middlename']) ? mysqli_real_escape_string($conn, trim($_POST['l2_middlename'])) : '';
    $l2_lastname = isset($_POST['l2_lastname']) ? mysqli_real_escape_string($conn, trim($_POST['l2_lastname'])) : '';
    $l2_gender = isset($_POST['l2_gender']) ? mysqli_real_escape_string($conn, trim($_POST['l2_gender'])) : '';
    $l3_firstname = isset($_POST['l3_firstname']) ? mysqli_real_escape_string($conn, trim($_POST['l3_firstname'])) : '';
    $l3_middlename = isset($_POST['l3_middlename']) ? mysqli_real_escape_string($conn, trim($_POST['l3_middlename'])) : '';
    $l3_lastname = isset($_POST['l3_lastname']) ? mysqli_real_escape_string($conn, trim($_POST['l3_lastname'])) : '';
    $l3_gender = isset($_POST['l3_gender']) ? mysqli_real_escape_string($conn, trim($_POST['l3_gender'])) : '';
    $l4_firstname = isset($_POST['l4_firstname']) ? mysqli_real_escape_string($conn, trim($_POST['l4_firstname'])) : '';
    $l4_middlename = isset($_POST['l4_middlename']) ? mysqli_real_escape_string($conn, trim($_POST['l4_middlename'])) : '';
    $l4_lastname = isset($_POST['l4_lastname']) ? mysqli_real_escape_string($conn, trim($_POST['l4_lastname'])) : '';
    $l4_gender = isset($_POST['l4_gender']) ? mysqli_real_escape_string($conn, trim($_POST['l4_gender'])) : '';
    $l5_firstname = isset($_POST['l5_firstname']) ? mysqli_real_escape_string($conn, trim($_POST['l5_firstname'])) : '';
    $l5_middlename = isset($_POST['l5_middlename']) ? mysqli_real_escape_string($conn, trim($_POST['l5_middlename'])) : '';
    $l5_lastname = isset($_POST['l5_lastname']) ? mysqli_real_escape_string($conn, trim($_POST['l5_lastname'])) : '';
    $l5_gender = isset($_POST['l5_gender']) ? mysqli_real_escape_string($conn, trim($_POST['l5_gender'])) : '';
    $start_date = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end_date = mysqli_real_escape_string($conn, $_POST['end_date']);
    $paymentDueDate = mysqli_real_escape_string($conn, $_POST['paymentDueDate']);
    $amount = mysqli_real_escape_string($conn, $_POST['amount']);
    $vat_type = mysqli_real_escape_string($conn, $_POST['vat_type']);
    $vat_amount = mysqli_real_escape_string($conn, $_POST['vat_amount']);
    $wtax = mysqli_real_escape_string($conn, $_POST['wtax']);
    $gross_amount = mysqli_real_escape_string($conn, $_POST['gross_amount']);
    $amount_lessor = mysqli_real_escape_string($conn, $_POST['amount_lessor']);
    $edit_amount_lessor = mysqli_real_escape_string($conn, $_POST['edit_amount_lessor']);
    $mode_of_payment = mysqli_real_escape_string($conn, $_POST['mode_of_payment']);
    $wallet_number = mysqli_real_escape_string($conn, $_POST['wallet_number']);
    $authorize_firstname = mysqli_real_escape_string($conn, $_POST['authorize_firstname']);
    $authorize_middlename = mysqli_real_escape_string($conn, $_POST['authorize_middlename']);
    $authorize_lastname = mysqli_real_escape_string($conn, $_POST['authorize_lastname']);
    $authorize_gender = mysqli_real_escape_string($conn, $_POST['authorize_gender']);
    $authorize_mobileNumber = mysqli_real_escape_string($conn, $_POST['authorize_mobileNumber']);
    $contract_file = isset($_POST['contract_file']) ? mysqli_real_escape_string($conn, $_POST['contract_file']) : '';
    $mime_type = mysqli_real_escape_string($conn, $_POST['mime_type']);
    $contract_file_name = mysqli_real_escape_string($conn, $_POST['contract_file_name']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $request_status = mysqli_real_escape_string($conn, $_POST['request_status']);
    $created_by = mysqli_real_escape_string($conn, $_POST['created_by']);
    $l1_mobileNumber = mysqli_real_escape_string($conn, $_POST['l1_mobileNumber']);
    $l2_mobileNumber = mysqli_real_escape_string($conn, $_POST['l2_mobileNumber']);
    $l3_mobileNumber = mysqli_real_escape_string($conn, $_POST['l3_mobileNumber']);
    $l4_mobileNumber = mysqli_real_escape_string($conn, $_POST['l4_mobileNumber']);
    $l5_mobileNumber = mysqli_real_escape_string($conn, $_POST['l5_mobileNumber']);

    // Prepare SQL statement
    $sql = "INSERT INTO create_contract (kpx_code, branch_id, contract_number, series, mainzone, zone, branch_code, branch, region, area, lessor_type, corporate_name, start_date, end_date, payment_due_date, amount, vat_type, vat_amount, wtax, total_month_rental, amount_lessor, edit_amount_lessor, mode_of_payment, wallet_number, authorize_firstname, authorize_middlename, authorize_lastname, authorize_gender, authorize_mobileNumber, contract_file, mimeType, contractFilename, status, request_status, created_by, mobile_number_l1, mobile_number_l2, mobile_number_l3, mobile_number_l4, mobile_number_l5)
            VALUES ('$kpx_code', '$branchId', '$contract_number', '$series', '$mainzone', '$zone', '$branch_code', '$branch', '$region', '$area', '$lessor_type', '$corporateName', '$start_date', '$end_date', '$paymentDueDate', '$amount', '$vat_type', '$vat_amount', '$wtax', '$gross_amount', '$amount_lessor', '$edit_amount_lessor', '$mode_of_payment', '$wallet_number', '$authorize_firstname', '$authorize_middlename', '$authorize_lastname', '$authorize_gender', '$authorize_mobileNumber', '$contract_file', '$mime_type', '$contract_file_name', '$status', '$request_status', '$created_by', '$l1_mobileNumber', '$l2_mobileNumber', '$l3_mobileNumber', '$l4_mobileNumber', '$l5_mobileNumber')";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Contract saved successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save contract: ' . mysqli_error($conn)]);
    }

    mysqli_close($conn);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
