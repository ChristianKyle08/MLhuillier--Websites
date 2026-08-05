<?php
session_start();
    include('../../config/config.php');
    if (!isset($_SESSION['user_name'])) {
        header('location:login_form.php');
        exit();
    }
    echo '<script src="../../sweetalert2/dist/sweetalert2.all.min.js"></script>';
    echo '<link rel="stylesheet" href="../../sweetalert2/dist/sweetalert2.min.css">';
    echo '<script src="../../jquery-3.7.1.js"></script>';
    if (isset($_POST['createCn_save'])) {
        $zone = $_POST['zone'];
        $mainzone = $_POST['mainzone'];
        $branch_code = $_POST['branch_code'];
        $branch = mysqli_real_escape_string($conn, $_POST['branch']);
        $region = mysqli_real_escape_string($conn, $_POST['region']);
        $area = mysqli_real_escape_string($conn, $_POST['area']);
        $corporateName = mysqli_real_escape_string($conn, $_POST['corporate_name']);
        $lessor_type = mysqli_real_escape_string($conn, $_POST['lessor_type']);
        $l1_firstname = mysqli_real_escape_string($conn, $_POST['l1_firstname']);
        $l1_middlename = mysqli_real_escape_string($conn, $_POST['l1_middlename']);
        $l1_lastname = mysqli_real_escape_string($conn, $_POST['l1_lastname']);
        $l1_gender = mysqli_real_escape_string($conn, $_POST['l1_gender']);
        $l1_mobileNumber = mysqli_real_escape_string($conn, $_POST['l1_mobileNumber']);
        $l2_mobileNumber = mysqli_real_escape_string($conn, $_POST['l2_mobileNumber']);
        $l2_firstname = mysqli_real_escape_string($conn, $_POST['l2_firstname']);
        $l2_middlename = mysqli_real_escape_string($conn, $_POST['l2_middlename']);
        $l2_lastname = mysqli_real_escape_string($conn, $_POST['l2_lastname']);
        $l2_gender = mysqli_real_escape_string($conn, $_POST['l2_gender']);
        $l3_firstname = mysqli_real_escape_string($conn, $_POST['l3_firstname']);
        $l3_middlename = mysqli_real_escape_string($conn, $_POST['l3_middlename']);
        $l3_lastname = mysqli_real_escape_string($conn, $_POST['l3_lastname']);
        $l3_gender = mysqli_real_escape_string($conn, $_POST['l3_gender']);
        $l3_mobileNumber = mysqli_real_escape_string($conn, $_POST['l3_mobileNumber']);
        $l4_firstname = mysqli_real_escape_string($conn, $_POST['l4_firstname']);
        $l4_middlename = mysqli_real_escape_string($conn, $_POST['l4_middlename']);
        $l4_lastname = mysqli_real_escape_string($conn, $_POST['l4_lastname']);
        $l4_gender = mysqli_real_escape_string($conn, $_POST['l4_gender']);
        $l4_mobileNumber = mysqli_real_escape_string($conn, $_POST['l4_mobileNumber']);
        $l5_firstname = mysqli_real_escape_string($conn, $_POST['l5_firstname']);
        $l5_middlename = mysqli_real_escape_string($conn, $_POST['l5_middlename']);
        $l5_lastname = mysqli_real_escape_string($conn, $_POST['l5_lastname']);
        $l5_gender = mysqli_real_escape_string($conn, $_POST['l5_gender']);
        $l5_mobileNumber = mysqli_real_escape_string($conn, $_POST['l5_mobileNumber']);

        $start_date = mysqli_real_escape_string($conn, $_POST['startDate']);
        $end_date = mysqli_real_escape_string($conn, $_POST['endDate']);
        $paymentDueDate = mysqli_real_escape_string($conn, $_POST['paymentDueDate']);
        $amount = $_POST['amount'];
        $inputted_amount = mysqli_real_escape_string($conn, $_POST['amountComp']);
        $netOfVat = $_POST['net_of_vat'];
        $vat_type = mysqli_real_escape_string($conn, $_POST['select_vat']);
        $vat_amount = isset($_POST['vat']) ? $_POST['vat'] : '';
        $wtax = isset($_POST['w-tax']) ? $_POST['w-tax'] : '';
        $gross_amount = isset($_POST['gross_amount']) ? $_POST['gross_amount'] : '';
        $amount_lessor = isset($_POST['amount_lessor']) ? $_POST['amount_lessor'] : '';
        $edit_amount_lessor = isset($_POST['edit_amount_lessor']) ? $_POST['edit_amount_lessor'] : '';

        $mode_of_payment = mysqli_real_escape_string($conn, $_POST['modeOfPayment']);
        $wallet_number = $_POST['walletNumber'];
        $status = 'Active';
        $request_status = 'Created';
        $created_by =  $_SESSION['user_name'];
        $authorize_firstname = mysqli_real_escape_string($conn, $_POST['authorize_firstname']);
        $authorize_middlename = mysqli_real_escape_string($conn, $_POST['authorize_middlename']);
        $authorize_lastname = mysqli_real_escape_string($conn, $_POST['authorize_lastname']);
        $authorize_gender = mysqli_real_escape_string($conn, $_POST['authorize_gender']);
        $authorize_mobileNumber = mysqli_real_escape_string($conn, $_POST['authorize_mobileNumber']);

        $fileContent = $mimeType = $fileName = '';
        if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName = $_FILES['fileUpload']['tmp_name'];
            $fileName = mysqli_real_escape_string($conn, $_FILES['fileUpload']['name']);
            $fileContent = mysqli_real_escape_string($conn, file_get_contents($fileTmpName));
            $mimeType = mysqli_real_escape_string($conn, mime_content_type($fileTmpName));
        }

        $fileContent2 = $mimeType2 = $fileName2 = '';
        if (isset($_FILES['fileUpload2']) && $_FILES['fileUpload2']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName2 = $_FILES['fileUpload2']['tmp_name'];
            $fileName2 = mysqli_real_escape_string($conn, $_FILES['fileUpload2']['name']);
            $fileContent2 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName2));
            $mimeType2 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName2)); 
        }

        $fileContent3 = $mimeType3 = $fileName3 = '';
        if (isset($_FILES['fileUpload3']) && $_FILES['fileUpload3']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName3 = $_FILES['fileUpload3']['tmp_name'];
            $fileName3 = mysqli_real_escape_string($conn, $_FILES['fileUpload3']['name']);
            $fileContent3 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName3));
            $mimeType3 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName3)); 
        }

        $fileContent4 = $mimeType4 = $fileName4 = '';
        if (isset($_FILES['fileUpload4']) && $_FILES['fileUpload4']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName4 = $_FILES['fileUpload4']['tmp_name'];
            $fileName4 = mysqli_real_escape_string($conn, $_FILES['fileUpload4']['name']);
            $fileContent4 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName4));
            $mimeType4 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName4)); 
        }

        $fileContent5 = $mimeType5 = $fileName5 = '';
        if (isset($_FILES['fileUpload5']) && $_FILES['fileUpload5']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName5 = $_FILES['fileUpload5']['tmp_name'];
            $fileName5 = mysqli_real_escape_string($conn, $_FILES['fileUpload5']['name']);
            $fileContent5 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName5));
            $mimeType5 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName5)); 
        }

        $fileContent6 = $mimeType6 = $fileName6 = '';
        if (isset($_FILES['fileUpload6']) && $_FILES['fileUpload6']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName6 = $_FILES['fileUpload6']['tmp_name'];
            $fileName6 = mysqli_real_escape_string($conn, $_FILES['fileUpload6']['name']);
            $fileContent6 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName6));
            $mimeType6 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName6)); 
        }

        $fileContent7 = $mimeType7 = $fileName7 = '';
        if (isset($_FILES['fileUpload7']) && $_FILES['fileUpload7']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName7 = $_FILES['fileUpload7']['tmp_name'];
            $fileName7 = mysqli_real_escape_string($conn, $_FILES['fileUpload7']['name']);
            $fileContent7 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName7));
            $mimeType7 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName7));
        }

        $fileContent8 = $mimeType8 = $fileName8 = '';
        if (isset($_FILES['fileUpload8']) && $_FILES['fileUpload8']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName8 = $_FILES['fileUpload8']['tmp_name'];
            $fileName8 = mysqli_real_escape_string($conn, $_FILES['fileUpload8']['name']);
            $fileContent8 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName8));
            $mimeType8 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName8));
        }
        
        $fileContent9 = $mimeType9 = $fileName9 = '';
        if (isset($_FILES['fileUpload9']) && $_FILES['fileUpload9']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName9 = $_FILES['fileUpload9']['tmp_name'];
            $fileName9 = mysqli_real_escape_string($conn, $_FILES['fileUpload9']['name']);
            $fileContent9 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName9));
            $mimeType9 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName9));
        }

        $fileContent10 = $mimeType10 = $fileName10 = '';
        if (isset($_FILES['fileUpload10']) && $_FILES['fileUpload10']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName10 = $_FILES['fileUpload10']['tmp_name'];
            $fileName10 = mysqli_real_escape_string($conn, $_FILES['fileUpload10']['name']);
            $fileContent10 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName10));
            $mimeType10 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName10));
        }

        $fileContent11 = $mimeType11 = $fileName11 = '';
        if (isset($_FILES['fileUpload11']) && $_FILES['fileUpload11']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName11 = $_FILES['fileUpload11']['tmp_name'];
            $fileName11 = mysqli_real_escape_string($conn, $_FILES['fileUpload11']['name']);
            $fileContent11 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName11));
            $mimeType11 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName11));
        }

        $fileContent12 = $mimeType12 = $fileName12 = '';
        if (isset($_FILES['fileUpload12']) && $_FILES['fileUpload12']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName12 = $_FILES['fileUpload12']['tmp_name'];
            $fileName12 = mysqli_real_escape_string($conn, $_FILES['fileUpload12']['name']);
            $fileContent12 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName12));
            $mimeType12 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName12));
        }

        $fileContent13 = $mimeType13 = $fileName13 = '';
        if (isset($_FILES['fileUpload13']) && $_FILES['fileUpload13']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName13 = $_FILES['fileUpload13']['tmp_name'];
            $fileName13 = mysqli_real_escape_string($conn, $_FILES['fileUpload13']['name']);
            $fileContent13 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName13));
            $mimeType13 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName13));
        }

        $fileContent14 = $mimeType14 = $fileName14 = '';
        if (isset($_FILES['fileUpload14']) && $_FILES['fileUpload14']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName14 = $_FILES['fileUpload14']['tmp_name'];
            $fileName14 = mysqli_real_escape_string($conn, $_FILES['fileUpload14']['name']);
            $fileContent14 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName14));
            $mimeType14 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName14));
        }

        $fileContent15 = $mimeType15 = $fileName15 = '';
        if (isset($_FILES['fileUpload15']) && $_FILES['fileUpload15']['error'] === UPLOAD_ERR_OK) {
            $fileTmpName15 = $_FILES['fileUpload15']['tmp_name'];
            $fileName15 = mysqli_real_escape_string($conn, $_FILES['fileUpload15']['name']);
            $fileContent15 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName15));
            $mimeType15 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName15));
        }

        $kpxCode = htmlspecialchars(trim($_POST['kpxCode']));
        $branchId = htmlspecialchars(trim($_POST['branchId']));


        if (empty($kpxCode)) {
            $kpxCode = 0; 
        }

        $latestSeriesQuery = "SELECT MAX(series) AS latest_series
                            FROM create_contract
                            WHERE branch_id = '$branchId'";
        $latestSeriesResult = mysqli_query($conn, $latestSeriesQuery);

        if ($latestSeriesResult && mysqli_num_rows($latestSeriesResult) > 0) {
            $latestSeriesRow = mysqli_fetch_assoc($latestSeriesResult);
            $latestSeries = (int) $latestSeriesRow['latest_series'] + 1;
        } else {
            $latestSeries = 1;
        }
        $contractNumber = "COL-" . $branchId . "-" . $latestSeries;

        $format_start_date= date('Y-m-d H:i:s', strtotime(date('Y-m-d', strtotime($end_date)) . ' 00:00:00'));
        $format_end_date= date('Y-m-d H:i:s', strtotime(date('Y-m-d', strtotime($end_date)) . ' 23:59:59'));

        $existingContractQuery = "SELECT *
                                FROM create_contract
                                WHERE branch_id = '$branchId' 
                                AND request_status != 'Terminated'
                                AND (
                                        (start_date <= '$format_end_date' AND end_date >= '$format_start_date') OR
                                        (start_date >= '$start_date' AND end_date <= '$end_date')
                                    )";
        $existingContractResult = mysqli_query($conn, $existingContractQuery);

        if (mysqli_num_rows($existingContractResult) > 0) {
            $contract = mysqli_fetch_assoc($existingContractResult);
            $contractFilename = $contract['contractFilename'];

            echo '<script>
                $(document).ready(function() {
                    Swal.fire({
                        title: "Contract Already Exists",
                        text: "A contract for this branch and its contract period already exists.",
                        icon: "info",
                        showCancelButton: true,
                        confirmButtonText: "View Contract",
                        cancelButtonText: "Cancel",
                        customClass: {
                            confirmButton: "btn btn-primary",
                            cancelButton: "btn btn-secondary"
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: "Existing Contract",
                                html: `<iframe src="data:' . $contract['mimeType'] . ';base64,' . base64_encode($contract['contract_file']) . '" style="width:100%; height:400px;"></iframe>`,
                                width: 600,
                                padding: "3em",
                                showCloseButton: true,
                                showConfirmButton: false,
                            });
                        }
                    });
                });
            </script>';
        } else {
            // Insert into the database
            $query = "INSERT INTO create_contract (kpx_code, branch_id, contract_number, series, mainzone, zone, branch_code, branch, region, area,
            l1_firstname, l1_middlename, l1_lastname, l1_gender, l2_firstname, l2_middlename, l2_lastname, l2_gender, l3_firstname, l3_middlename, l3_lastname, l3_gender,
            l4_firstname, l4_middlename, l4_lastname, l4_gender, l5_firstname, l5_middlename, l5_lastname, l5_gender, lessor_type, corporate_name, start_date, end_date, payment_due_date, amount, vat_type, inputted_amount, net_of_vat, vat_amount, wtax, total_month_rental, amount_lessor,
            edit_amount_lessor, mode_of_payment, wallet_number, authorize_firstname, authorize_middlename, authorize_lastname, authorize_gender,
            authorize_mobileNumber, contract_file, mimeType, contractFilename, contract_file2, mimeType2, contractFilename2, contract_file3, mimeType3,
            contractFilename3, contract_file4, mimeType4, contractFilename4, contract_file5, mimeType5, contractFilename5, attachment_6, mimeType6,
            attachment_6_filename, attachment_7, mimeType7, attachment_7_filename, attachment_8, mimeType8, attachment_8_filename, attachment_9, mimeType9, attachment_9_filename,
            attachment_10, mimeType10, attachment_10_filename, attachment_11, mimeType11, attachment_11_filename, attachment_12, mimeType12, attachment_12_filename, attachment_13, mimeType13, attachment_13_filename, attachment_14, mimeType14, attachment_14_filename, attachment_15, mimeType15, attachment_15_filename, status, request_status,
            created_by, mobile_number_l1, mobile_number_l2, mobile_number_l3, mobile_number_l4, mobile_number_l5)
            VALUES ('$kpxCode', '$branchId', '$contractNumber', '$latestSeries', '$mainzone', '$zone', '$branch_code', '$branch', '$region', '$area',
            '$l1_firstname', '$l1_middlename', '$l1_lastname', '$l1_gender', '$l2_firstname', '$l2_middlename', '$l2_lastname', '$l2_gender', '$l3_firstname', '$l3_middlename', '$l3_lastname', '$l3_gender',
            '$l4_firstname', '$l4_middlename', '$l4_lastname', '$l4_gender', '$l5_firstname', '$l5_middlename', '$l5_lastname', '$l5_gender',
            '$lessor_type', '$corporateName', '$start_date', '$end_date', '$paymentDueDate', '$amount', '$vat_type','$inputted_amount', '$netOfVat', '$vat_amount', '$wtax',
            '$gross_amount', '$amount_lessor', '$edit_amount_lessor', '$mode_of_payment', '$wallet_number', '$authorize_firstname', '$authorize_middlename',
            '$authorize_lastname', '$authorize_gender', '$authorize_mobileNumber', '$fileContent', '$mimeType', '$fileName', '$fileContent2', '$mimeType2',
            '$fileName2', '$fileContent3', '$mimeType3', '$fileName3', '$fileContent4', '$mimeType4', '$fileName4', '$fileContent5', '$mimeType5', '$fileName5',
            '$fileContent6', '$mimeType6', '$fileName6', '$fileContent7', '$mimeType7', '$fileName7', '$fileContent8', '$mimeType8', '$fileName8', 
            '$fileContent9', '$mimeType9', '$fileName9', '$fileContent10', '$mimeType10', '$fileName10', '$fileContent11', '$mimeType11', '$fileName11', '$fileContent12', '$mimeType12', '$fileName12', '$fileContent13', '$mimeType13', '$fileName13', '$fileContent14', '$mimeType14', '$fileName14', '$fileContent15', '$mimeType15', '$fileName15', '$status',
            '$request_status', '$created_by', '$l1_mobileNumber', '$l2_mobileNumber', '$l3_mobileNumber', '$l4_mobileNumber', '$l5_mobileNumber')";
            if (mysqli_query($conn, $query)) {
                echo "<script>
                    window.onload = function() {
                        Swal.fire({
                            title: 'Success!',
                            text: 'Contract created successfully!',
                            icon: 'success'
                        }).then(function() {
                            window.location.href = 'create_contract.php';
                        });
                    };
                </script>";
            } else {
                $error = mysqli_error($conn);
                echo "<script>
                    window.onload = function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Something went wrong: " . addslashes($error) . "',
                            icon: 'error'
                        });
                    };
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
            <title>ML Rental -Renew Contract</title>
            <link rel="stylesheet" href="../../boxicons/css/boxicons.min.css">
            <link rel="preload" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
            <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap"></noscript>
            <link rel="preload" as="style" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB"><link rel="stylesheet" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB" type="text/css">
            <!-- custom CSS file link  -->
            <link rel="stylesheet" href="../../css/renew.css?v=<?php echo time(); ?>">
            <link rel="stylesheet" href="../../css/responsive.css?v=<?php echo time(); ?>">
            <script src="https://kit.fontawesome.com/30b908cc5a.js" crossorigin="anonymous"></script>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
        </head>
    <body>
    <?php include ('navbar.php'); ?>
<div class="contract-container">
    <div class="contract-header">
        <h3>RENEW CONTRACT</h3>
    </div>
    <form class="contract-form" action="" method="POST" enctype="multipart/form-data">
        <div class="row">
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="contractNumber">Contract Number</label>
                    <span style="color:red;"> *</span>
                </div>
                <select name="contractNumber" id="contractNumber" class="contractNumber_select" required onchange="updateRegionAndArea()">
                    <option value="" <?php echo (!isset($_POST['contractNumber'])) ? 'selected' : ''; ?>></option>
                    <?php
                        // Escape user email to prevent SQL injection
                        $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']); 
                        // Query to fetch contract numbers based on user area
                        $contractNumberQuery = "
                            SELECT DISTINCT 
                            t.contract_number, t.mainzone, t.region, t.area, t.branch, 
                            t.kpx_code, t.branch_id, t.branch_code, 
                            CONCAT_WS(' ', t.l1_firstname, t.l1_middlename, t.l1_lastname) AS l1_fullname, t.l1_gender, t.mobile_number_l1, 
                            t.lessor_type, 
                            CONCAT_WS(' ', t.l2_firstname, t.l2_middlename, t.l2_lastname) AS l2_fullname, t.l2_gender, t.mobile_number_l2, 
                            CONCAT_WS(' ', t.l3_firstname, t.l3_middlename, t.l3_lastname) AS l3_fullname, t.l3_gender, t.mobile_number_l3, 
                            CONCAT_WS(' ', t.l4_firstname, t.l4_middlename, t.l4_lastname) AS l4_fullname, t.l4_gender, t.mobile_number_l4, 
                            CONCAT_WS(' ', t.l5_firstname, t.l5_middlename, t.l5_lastname) AS l5_fullname, t.l5_gender, t.mobile_number_l5, 
                            t.zone, t.amount, t.vat_type, t.inputted_amount, t.net_of_vat, t.vat_amount, t.wtax, t.total_month_rental, 
                            t.amount_lessor, t.edit_amount_lessor, t.mode_of_payment, 
                            CONCAT_WS(' ', t.authorize_firstName, t.authorize_middleName, t.authorize_lastName) AS authorize_fullname, 
                            t.authorize_gender, t.authorize_mobileNumber
                            FROM transactional t
                            WHERE t.contract_number != '' 
                            AND t.status != 'Terminated' 
                            AND t.area IN (
                                SELECT DISTINCT u.area 
                                FROM user_form u 
                                WHERE u.username = '$user_email'
                            )
                            AND t.region IN (
                                SELECT DISTINCT u.region 
                                FROM user_form u 
                                WHERE u.username = '$user_email'
                            )
                            ORDER BY t.contract_number DESC";
                            // Execute the query
                            $resultContract = mysqli_query($conn, $contractNumberQuery);
                            $contractData = [];
                            if ($resultContract) {
                                while ($rowContract = mysqli_fetch_assoc($resultContract)) {
                                    $selected = (isset($_POST['contractNumber']) && $_POST['contractNumber'] == $rowContract['contract_number']) ? 'selected' : '';
                                    echo "<option value='" . $rowContract['contract_number'] . "' $selected>" . $rowContract['contract_number'] . " (" . $rowContract['region'] . " - " . $rowContract['area'] . " - " . $rowContract['branch'] . ")</option>";
                                    $contractData[$rowContract['contract_number']] = [
                                    'region' => $rowContract['region'], 
                                    'area' => $rowContract['area'], 
                                    'branch' => $rowContract['branch'],
                                    'kpx_code' => $rowContract['kpx_code'],
                                    'branch_id' => $rowContract['branch_id'],
                                    'branch_code' => $rowContract['branch_code'],
                                    'zone' => $rowContract['zone'],
                                    'mainzone' => $rowContract['mainzone'],
                                    'l1_firstname' => $rowContract['l1_firstname'],
                                    'l1_middlename' => $rowContract['l1_middlename'],
                                    'l1_lastname' => $rowContract['l1_lastname'],
                                    'l1_gender' => $rowContract['l1_gender'],
                                    'l1_mobileNumber' => $rowContract['mobile_number_l1'],
                                    'lessor_type' => $rowContract['lessor_type'],
                                    'l2_firstname' => $rowContract['l2_firstname'],
                                    'l2_middlename' => $rowContract['l2_middlename'],
                                    'l2_lastname' => $rowContract['l2_lastname'],
                                    'l2_gender' => $rowContract['l2_gender'],
                                    'l2_mobileNumber' => $rowContract['mobile_number_l2'],
                                    'l3_firstname' => $rowContract['l3_firstname'],
                                    'l3_middlename' => $rowContract['l3_middlename'],
                                    'l3_lastname' => $rowContract['l3_lastname'],
                                    'l3_gender' => $rowContract['l3_gender'],
                                    'l3_mobileNumber' => $rowContract['mobile_number_l3'],
                                    'l4_firstname' => $rowContract['l4_firstname'],
                                    'l4_middlename' => $rowContract['l4_middlename'],
                                    'l4_lastname' => $rowContract['l4_lastname'],
                                    'l4_gender' => $rowContract['l4_gender'],
                                    'l4_mobileNumber' => $rowContract['mobile_number_l4'],
                                    'l5_firstname' => $rowContract['l5_firstname'],
                                    'l5_middlename' => $rowContract['l5_middlename'],
                                    'l5_lastname' => $rowContract['l5_lastname'],
                                    'l5_gender' => $rowContract['l5_gender'],
                                    'l5_mobileNumber' => $rowContract['mobile_number_l5'],
                                    'amount' => $rowContract['amount'],
                                    'vat_type' => $rowContract['vat_type'],
                                    'inputted_amount' => $rowContract['inputted_amount'],
                                    'net_of_vat' => $rowContract['net_of_vat'],
                                    'vat_amount' => $rowContract['vat_amount'],
                                    'wtax' => $rowContract['wtax'],
                                    'total_month_rental' => $rowContract['total_month_rental'],
                                    'amount_lessor' => $rowContract['amount_lessor'],
                                    'edit_amount_lessor' => $rowContract['edit_amount_lessor'],
                                    'mode_of_payment' => $rowContract['mode_of_payment'],
                                    'authorize_firstName' => $rowContract['authorize_firstName'],
                                    'authorize_middleName' => $rowContract['authorize_middleName'],
                                    'authorize_lastName' => $rowContract['authorize_lastName'],
                                    'authorize_gender' => $rowContract['authorize_gender'],
                                    'authorize_mobileNumber' => $rowContract['authorize_mobileNumber']
                                ];
                            }
                        }
                    ?>
                </select>
            </div>
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="region">Region</label>
                    <span style="color:red;"> *</span>
                </div>
                <input type="text" name="region" id="region" class="region_input" autocomplete="off" required value="<?php echo isset($_POST['region']) ? htmlspecialchars($_POST['region'], ENT_QUOTES) : ''; ?>">
            </div>
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="area">Area</label>
                    <span style="color:red;"> *</span>
                </div>
                <input type="text" name="area" id="area" class="area_input" autocomplete="off" required value="<?php echo isset($_POST['area']) ? htmlspecialchars($_POST['area'], ENT_QUOTES) : ''; ?>">
            </div>
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="branch">Branch</label>
                    <span style="color:red;"> *</span>
                </div>
                <input type="text" name="branch" id="branch" class="branch_input" autocomplete="off" required value="<?php echo isset($_POST['branch']) ? htmlspecialchars($_POST['branch'], ENT_QUOTES) : ''; ?>">
                <input type="hidden" name="kpxCode" id="kpxCode" autocomplete="off" value="<?php echo isset($_POST['kpxCode']) ? htmlspecialchars($_POST['kpxCode'], ENT_QUOTES) : ''; ?>">
                <input type="hidden" name="branchId" id="branchId" autocomplete="off" value="<?php echo isset($_POST['branchId']) ? htmlspecialchars($_POST['branchId'], ENT_QUOTES) : ''; ?>">
                <input type="hidden" name="branch_code" id="branch_code" autocomplete="off" value="<?php echo isset($_POST['branch_code']) ? htmlspecialchars($_POST['branch_code'], ENT_QUOTES) : ''; ?>">
                <input type="hidden" name="zone" id="zone" autocomplete="off" value="<?php echo isset($_POST['zone']) ? htmlspecialchars($_POST['zone'], ENT_QUOTES) : ''; ?>">
                <input type="hidden" name="mainzone" id="mainzone"  autocomplete="off" value="<?php echo isset($_POST['mainzone']) ? htmlspecialchars($_POST['mainzone'], ENT_QUOTES) : ''; ?>">
            </div>
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="area">Lessor Name</label>
                    <span style="color:red;"> *</span>
                </div>
                <input type="text" name="lessor_name" id="lessor_name" autocomplete="off" value="" >
                <input type="hidden" name="l1_firstname" id="l1_firstname"  autocomplete="off" value="">
                <input type="hidden" name="l1_middlename" id="l1_middlename"  autocomplete="off" value="">
                <input type="hidden" name="l1_lastname" id="l1_lastname"  autocomplete="off" value="">
                <input type="hidden" name="l1_gender" id="l1_gender"  autocomplete="off" value="">
                <input type="hidden" name="l1_mobileNumber" id="l1_mobileNumber"  autocomplete="off" value="">
                <input type="hidden" name="corporate_name" id="corporate_name"  autocomplete="off" value="">
                <input type="hidden" name="lessor_id" id="lessor_id"  autocomplete="off" value="">
                <input type="hidden" name="lessor_type" id="lessor_type"  autocomplete="off" value="">
            </div>  
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="area">2nd Lessor Name</label>
                </div>
                <input type="text" name="lessor_name2" id="lessor_name2"  autocomplete="off" value="" >
                <input type="hidden" name="l2_firstname" id="l2_firstname"  autocomplete="off" value="">
                <input type="hidden" name="l2_middlename" id="l2_middlename"  autocomplete="off" value="">
                <input type="hidden" name="l2_lastname" id="l2_lastname"  autocomplete="off" value="">
                <input type="hidden" name="l2_gender" id="l2_gender"  autocomplete="off" value="">
                <input type="hidden" name="l2_mobileNumber" id="l2_mobileNumber"  autocomplete="off" value="">
            </div>
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="area">3rd Lessor Name</label>
                </div>
                <input type="text" name="lessor_name3" id="lessor_name3" autocomplete="off" value="" >
                <input type="hidden" name="l3_firstname" id="l3_firstname" autocomplete="off" value="">
                <input type="hidden" name="l3_middlename" id="l3_middlename" autocomplete="off" value="">
                <input type="hidden" name="l3_lastname" id="l3_lastname" autocomplete="off" value="">
                <input type="hidden" name="l3_gender" id="l3_gender" autocomplete="off" value="">
                <input type="hidden" name="l3_mobileNumber" id="l3_mobileNumber" autocomplete="off" value="">
            </div>
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="area">4th Lessor Name</label>
                </div>
                <input type="text" name="lessor_name4" id="lessor_name4" autocomplete="off" value="">
                <input type="hidden" name="l4_firstname" id="l4_firstname" autocomplete="off" value="">
                <input type="hidden" name="l4_middlename" id="l4_middlename" autocomplete="off" value="">
                <input type="hidden" name="l4_lastname" id="l4_lastname" autocomplete="off" value="">
                <input type="hidden" name="l4_gender" id="l4_gender" autocomplete="off" value="">
                <input type="hidden" name="l4_mobileNumber" id="l4_mobileNumber" autocomplete="off" value="">
            </div>
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="area">5th Lessor Name</label>
                </div>
                <input type="text" name="lessor_name5" id="lessor_name5" autocomplete="off" value="">
                <input type="hidden" name="l5_firstname" id="l5_firstname" autocomplete="off" value="">
                <input type="hidden" name="l5_middlename" id="l5_middlename" autocomplete="off" value="">
                <input type="hidden" name="l5_lastname" id="l5_lastname" autocomplete="off" value="">
                <input type="hidden" name="l5_gender" id="l5_gender" autocomplete="off" value="">
                <input type="hidden" name="l5_mobileNumber" id="l5_mobileNumber" autocomplete="off" value="">
            </div>
        </div>
        <div class="row">
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="start">Effectivity Date</label>
                    <span style="color:red;"> *</span>
                </div>
                <input type="datetime-local" id="startDate" name="startDate" autocomplete="off" value="<?php echo $start_date; ?>" required>
            </div>
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="end">Expiry Date</label>
                    <span style="color:red;"> *</span>
                </div>
                <input type="datetime-local" id="endDate" name="endDate" autocomplete="off" value="<?php echo $end_date; ?>" required>
                </div>
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="paymentDueDate">Payment Due Date</label>
                    <span style="color:red;"> *</span>
                </div>
                <input type="date" id="paymentDueDate" name="paymentDueDate" autocomplete="off" value="<?php echo $paymentDueDate; ?>" required>
            </div>
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="amount">Monthly Rental</label>
                    <span style="color:red;"> *</span>
                </div>
                <input type="number" id="amount" name="amount" autocomplete="off" placeholder="0.00" required onchange="calculateVat()" step="0.01">
                <div class="lbl_span">
                    <label for="select_wtax">Vat Type</label>
                </div>
                <select id="select_vat" name="select_vat" autocomplete="off" placeholder="" required onchange="calculateVat()">
                    <option value="" disabled selected>Select VAT Option</option>
                    <option value="Vatable">Vatable</option>
                    <option value="Non Vatable">Non Vatable</option>
                    <option value="Vat Exempt">Vat Exempt</option>
                </select>
                <div class="lbl_span" id="amountComp_lbl">
                    <label for="amountComp">Amount Inputted</label>
                </div>
                <input type="text" id="amountComp" name="amountComp" autocomplete="off" value="" step="0.01" readonly>
                <div class="lbl_span" id="netOfVat_lbl">
                    <label for="net_of_vat">Net of Vat Amount</label>
                </div>
                <input type="number" id="net_of_vat" name="net_of_vat" autocomplete="off" value="" step="0.01" readonly> 
                <div class="lbl_span" id="vat_lbl">
                    <label for="vat">Vat Amount (12%)</label>
                </div>
                <input type="number" id="vat" name="vat" autocomplete="off" value="" step="0.01" readonly>
                <div class="lbl_span">
                    <label for="select_wtax" id="wtaxType_lbl">Wtax Type</label>
                </div>
                <select id="select_wtax" name="select_wtax" autocomplete="off" placeholder="" onchange="calculateWtax()" required disabled>
                    <option value="" disabled selected>Select Wtax Option</option>
                    <option value="less_wtax">Less Wtax</option>
                    <option value="net_wtax">Net Wtax</option>
                </select>
                <div class="lbl_span">
                    <label for="select_wtax" id="percent_lbl">Wtax Percentage(%)</label>
                </div>
                <select id="select_percent" name="select_percent" autocomplete="off" placeholder="" required onchange="calculateWtax()" disabled>
                    <option value="" disabled selected>Select Percentage</option>
                    <option value="5">5%</option>
                    <option value="4" disabled>4%</option>
                    <option value="3" disabled>3%</option>
                    <option value="2" disabled>2%</option>
                    <option value="1" disabled>1%</option>
                </select>
                <div class="lbl_span" id="wtax_lbl">
                    <label for="w-tax">Withholding Tax Amount</label>
                </div>
                <input type="number" id="w-tax" name="w-tax" autocomplete="off" value="<?php echo $wtax; ?>" step="0.01" readonly>
                <div class="lbl_span" id="gross_lbl">
                    <label for="gross_amount">Total Monthly Rental Expenses</label>
                </div>
                <input type="number" id="gross_amount" name="gross_amount" autocomplete="off" value="<?php echo $gross_amount; ?>" step="0.01" readonly>
                <div class="lbl_span" id="amount_lessor_lbl">
                    <label for="amount_lessor">Amount to lessor</label>
                </div>
                <input type="hidden" id="amount_lessor" name="amount_lessor" autocomplete="off" value="" step="0.01" readonly>
                <input type="number" id="edit_amount_lessor" name="edit_amount_lessor" autocomplete="off" value="" step="0.01">
            </div>
        </div>
        <div class="contract-field">
            <div class="lbl_span">
                <label for="modeOfPayment">Mode of Payment:</label>
            </div>
            <input type="text" id="modeOfPayment" name="modeOfPayment" autocomplete="off">
                <div class="lbl_span" style="display:none;">
                    <label for="modeOfPayment">Wallet Number:</label><span style="color:red;"> *</span>
                </div>
                <input type="text" name="walletNumber" id="walletNumber" autocomplete="off" value="" readonly  style="display:none;">
                <div class="lbl_span">
                    <label for="authorize_to_claim">Authorize First Name</label>
                </div>
                <input type="text" id="authorize_firstname" name="authorize_firstname" autocomplete="off" value="">
                <div class="lbl_span">
                    <label for="authorize_to_claim">Authorize Middle Name</label>
                </div>
                <input type="text" id="authorize_middlename" name="authorize_middlename" autocomplete="off" value="">
                <div class="lbl_span">
                    <label for="authorize_to_claim">Authorize Last Name</label>
                </div>
                <input type="text" id="authorize_lastname" name="authorize_lastname" autocomplete="off" value="">
                <div class="lbl_span">
                    <label for="authorize_to_claim">Authorize Gender</label>
                </div>
                <input type="text" id="authorize_gender" name="authorize_gender" autocomplete="off" placeholder="">
                <div class="lbl_span">
                    <label for="">Registered Mobile Number (Authorize to claim):</label>
                </div>
                <input type="text" name="authorize_mobileNumber" id="authorize_mobileNumber" autocomplete="off" value="" placeholder="Enter Mobile Number" oninput="limitMobileNumber(this)">
            </div>
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="fileUpload">Upload PDF file only:</label>
                    <span style="color:red;"> *</span>
                </div>
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="file" id="fileUpload" name="fileUpload" accept=".pdf" required>
                    <div id="filePreview" class="file-preview"></div>
                    <input type="file" id="fileUpload2" name="fileUpload2" accept=".pdf" >
                    <div id="filePreview2" class="file-preview"></div>

                    <input type="file" id="fileUpload3" name="fileUpload3" accept=".pdf" >
                    <div id="filePreview3" class="file-preview"></div>

                    <input type="file" id="fileUpload4" name="fileUpload4" accept=".pdf" >
                    <div id="filePreview4" class="file-preview"></div>

                    <input type="file" id="fileUpload5" name="fileUpload5" accept=".pdf" >
                    <div id="filePreview5" class="file-preview"></div>

                    <div class="sixToTen" id="sixToTen" style="display:none;">
                        <input type="file" id="fileUpload6" name="fileUpload6" accept=".pdf" >
                        <div id="filePreview6" class="file-preview"></div>
    
                        <input type="file" id="fileUpload7" name="fileUpload7" accept=".pdf" >
                        <div id="filePreview7" class="file-preview"></div>
    
                        <input type="file" id="fileUpload8" name="fileUpload8" accept=".pdf" >
                        <div id="filePreview8" class="file-preview"></div>
    
                        <input type="file" id="fileUpload9" name="fileUpload9" accept=".pdf" >
                        <div id="filePreview9" class="file-preview"></div>
        
                        <input type="file" id="fileUpload10" name="fileUpload10" accept=".pdf" >
                        <div id="filePreview10" class="file-preview"></div>
                    </div>
    
                    <div class="elevenTofifteen" id="elevenTofifteen" style="display:none;">
                        <input type="file" id="fileUpload11" name="fileUpload11" accept=".pdf" >
                        <div id="filePreview11" class="file-preview"></div>
    
                        <input type="file" id="fileUpload12" name="fileUpload12" accept=".pdf" >
                        <div id="filePreview12" class="file-preview"></div>
        
                        <input type="file" id="fileUpload13" name="fileUpload13" accept=".pdf" >
                        <div id="filePreview13" class="file-preview"></div>
        
                        <input type="file" id="fileUpload14" name="fileUpload14" accept=".pdf" >
                        <div id="filePreview14" class="file-preview"></div>
        
                        <input type="file" id="fileUpload15" name="fileUpload15" accept=".pdf" >
                        <div id="filePreview15" class="file-preview"></div>
                    </div><br>
                    <button type="button" name="add_attachment" id="add_attachment">Add more attachment</button>
                    <button type="button" name="add_attachment_second" id="add_attachment_second" style="display:none;">Add more attachment</button>

                    <div class="contract-actions">          
                        <button type="submit" id="save" name="createCn_save" >Create</button>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const addAttachment = document.getElementById('add_attachment');
                            const sixToTen_div = document.getElementById('sixToTen');
                            const addAttachment_second = document.getElementById('add_attachment_second');
                            const elevenTofifteen_div = document.getElementById('elevenTofifteen');

                            addAttachment.addEventListener('click', function() {
                                if (sixToTen_div.style.display === 'none') {
                                    sixToTen_div.style.display = 'block';
                                        addAttachment.style.display = 'none';
                                        addAttachment_second.style.display = 'block';
                                    } else {
                                        sixToTen_div.style.display = 'none';
                                        addAttachment.style.display = 'block';
                                    }
                                });
                            addAttachment_second.addEventListener('click', function() {
                                if (elevenTofifteen_div.style.display === 'none') {
                                    elevenTofifteen_div.style.display = 'block';
                                    addAttachment_second.style.display = 'none';
                                } else {
                                    elevenTofifteen_div.style.display = 'none';
                                }
                            });
                        });
                    </script>
                </form>
            </div>
        </div>
    </form>
</div>
<script>
    const contractData = <?php echo json_encode($contractData); ?>;
    function updateRegionAndArea() {
        const contractNumber = document.getElementById('contractNumber').value;
        const regionInput = document.getElementById('region');
        const areaInput = document.getElementById('area');
        const branchInput = document.getElementById('branch');
        const kpxCodeInput = document.getElementById('kpxCode');
        const branchIdInput = document.getElementById('branchId');
        const branchCodeInput = document.getElementById('branch_code');
        const zoneInput = document.getElementById('zone');
        const mainzoneInput = document.getElementById('mainzone');
        const l1_firstnameInput = document.getElementById('l1_firstname');
        const l1_middlenameInput = document.getElementById('l1_middlename');
        const l1_lastnameInput = document.getElementById('l1_lastname');
        const l1_genderInput = document.getElementById('l1_gender');
        const l1_mobilenumberInput = document.getElementById('l1_mobileNumber');
        const lessor_typeInput = document.getElementById('lessor_type');
        const lessor_nameInput = document.getElementById('lessor_name');
        const l2_firstnameInput = document.getElementById('l2_firstname');
        const l2_middlenameInput = document.getElementById('l2_middlename');
        const l2_lastnameInput = document.getElementById('l2_lastname');
        const l2_genderInput = document.getElementById('l2_gender');
        const l2_mobilenumberInput = document.getElementById('l2_mobileNumber');
        const lessor_name2Input = document.getElementById('lessor_name2');
        const l3_firstnameInput = document.getElementById('l3_firstname');
        const l3_middlenameInput = document.getElementById('l3_middlename');
        const l3_lastnameInput = document.getElementById('l3_lastname');
        const l3_genderInput = document.getElementById('l3_gender');
        const l3_mobilenumberInput = document.getElementById('l3_mobileNumber');
        const lessor_name3Input = document.getElementById('lessor_name3');
        const l4_firstnameInput = document.getElementById('l4_firstname');
        const l4_middlenameInput = document.getElementById('l4_middlename');
        const l4_lastnameInput = document.getElementById('l4_lastname');
        const l4_genderInput = document.getElementById('l4_gender');
        const l4_mobilenumberInput = document.getElementById('l4_mobileNumber');
        const lessor_name4Input = document.getElementById('lessor_name4');
        const l5_firstnameInput = document.getElementById('l5_firstname');
        const l5_middlenameInput = document.getElementById('l5_middlename');
        const l5_lastnameInput = document.getElementById('l5_lastname');
        const l5_genderInput = document.getElementById('l5_gender');
        const l5_mobilenumberInput = document.getElementById('l5_mobileNumber');
        const lessor_name5Input = document.getElementById('lessor_name5');
        const amountInput = document.getElementById('amount');
        const inputtedAmount = document.getElementById('amountComp');
        const netOfVatInput = document.getElementById('net_of_vat');
        const selectVatInput = document.getElementById('select_vat');
        const vatInput = document.getElementById('vat');
        const wtaxInput = document.getElementById('w-tax');
        const totalMonthRentalInput = document.getElementById('gross_amount');
        const amountLessorInput = document.getElementById('amount_lessor');
        const editAmountLessorInput = document.getElementById('edit_amount_lessor');
        const modeOfPaymentInput = document.getElementById('modeOfPayment');
        const authorizeFirstnameInput = document.getElementById('authorize_firstname');
        const authorizeMiddlenameInput = document.getElementById('authorize_middlename');
        const authorizeLastnameInput = document.getElementById('authorize_lastname');
        const authorizeGenderInput = document.getElementById('authorize_gender');
        const authorizeMobileNumberInput = document.getElementById('authorize_mobileNumber');

        regionInput.value = '';
        areaInput.value = '';
        branchInput.value = '';
        kpxCodeInput.value = '';
        branchIdInput.value = '';
        branchCodeInput.value = '';
        zoneInput.value = '';
        mainzoneInput.value = '';
        l1_firstnameInput.value = '';
        l1_middlenameInput.value = '';
        l1_lastnameInput.value = '';
        l1_genderInput.value = '';
        l1_mobilenumberInput.value = '';
        lessor_typeInput.value = '';
        lessor_nameInput.value = '';
        lessor_nameInput.value = '';
        l2_firstnameInput.value = '';
        l2_middlenameInput.value = '';
        l2_lastnameInput.value = '';
        l2_genderInput.value = '';
        l2_mobilenumberInput.value = '';
        lessor_name2Input.value = '';
        l3_firstnameInput.value = '';
        l3_middlenameInput.value = '';
        l3_lastnameInput.value = '';
        l3_genderInput.value = '';
        l3_mobilenumberInput.value = '';
        lessor_name3Input.value = '';
        l4_firstnameInput.value = '';
        l4_middlenameInput.value = '';
        l4_lastnameInput.value = '';
        l4_genderInput.value = '';
        l4_mobilenumberInput.value = '';
        lessor_name4Input.value = '';
         l5_firstnameInput.value = '';
        l5_middlenameInput.value = '';
        l5_lastnameInput.value = '';
        l5_genderInput.value = '';
        l5_mobilenumberInput.value = '';
        lessor_name5Input.value = '';
        amountInput.value = '';
        inputtedAmount.value = '';
        netOfVatInput.value = '';
        selectVatInput.value = '';
        vatInput.value = '';
        wtaxInput.value = '';
        totalMonthRentalInput.value = '';
        amountLessorInput.value = '';
        editAmountLessorInput.value = '';
        modeOfPaymentInput.value = '';
        authorizeFirstnameInput.value = '';
        authorizeMiddlenameInput.value = '';
        authorizeLastnameInput.value = '';
        authorizeGenderInput.value = '';
        authorizeMobileNumberInput.value = '';

        if (contractNumber && contractData[contractNumber]) {
            const region = contractData[contractNumber]['region'];
            const area = contractData[contractNumber]['area'];
            const branch = contractData[contractNumber]['branch'];
            const kpxCode = contractData[contractNumber]['kpx_code'];
            const branchId = contractData[contractNumber]['branch_id'];
            const branchCode = contractData[contractNumber]['branch_code'];
            const zone = contractData[contractNumber]['zone'];
            const mainzone = contractData[contractNumber]['mainzone'];
            const l1_firstname = contractData[contractNumber]['l1_firstname'];
            const l1_middlename = contractData[contractNumber]['l1_middlename'];
            const l1_lastname = contractData[contractNumber]['l1_lastname'];
            const l1_gender = contractData[contractNumber]['l1_gender'];
            const l1_mobileNumber = contractData[contractNumber]['l1_mobileNumber'];
            const lessor_type = contractData[contractNumber]['lessor_type'];
            const lessor_name = [contractData[contractNumber]['l1_firstname'], contractData[contractNumber]['l1_middlename'], contractData[contractNumber]['l1_lastname']].filter(Boolean).join(' ');
            const l2_firstname = contractData[contractNumber]['l2_firstname'];
            const l2_middlename = contractData[contractNumber]['l2_middlename'];
            const l2_lastname = contractData[contractNumber]['l2_lastname'];
            const l2_gender = contractData[contractNumber]['l2_gender'];
            const l2_mobileNumber = contractData[contractNumber]['l2_mobileNumber'];
            const lessor_name2 = [contractData[contractNumber]['l2_firstname'], contractData[contractNumber]['l2_middlename'], contractData[contractNumber]['l2_lastname']].filter(Boolean).join(' ');
            const l3_firstname = contractData[contractNumber]['l3_firstname'];
            const l3_middlename = contractData[contractNumber]['l3_middlename'];
            const l3_lastname = contractData[contractNumber]['l3_lastname'];
            const l3_gender = contractData[contractNumber]['l3_gender'];
            const l3_mobileNumber = contractData[contractNumber]['l3_mobileNumber'];
            const lessor_name3 = [contractData[contractNumber]['l3_firstname'], contractData[contractNumber]['l3_middlename'], contractData[contractNumber]['l3_lastname']].filter(Boolean).join(' ');
            const l4_firstname = contractData[contractNumber]['l4_firstname'];
            const l4_middlename = contractData[contractNumber]['l4_middlename'];
            const l4_lastname = contractData[contractNumber]['l4_lastname'];
            const l4_gender = contractData[contractNumber]['l4_gender'];
            const l4_mobileNumber = contractData[contractNumber]['l4_mobileNumber'];
            const lessor_name4 = [contractData[contractNumber]['l4_firstname'], contractData[contractNumber]['l4_middlename'], contractData[contractNumber]['l4_lastname']].filter(Boolean).join(' ');
            const l5_firstname = contractData[contractNumber]['l5_firstname'];
            const l5_middlename = contractData[contractNumber]['l5_middlename'];
            const l5_lastname = contractData[contractNumber]['l5_lastname'];
            const l5_gender = contractData[contractNumber]['l5_gender'];
            const l5_mobileNumber = contractData[contractNumber]['l5_mobileNumber'];
            const lessor_name5 = [contractData[contractNumber]['l5_firstname'], contractData[contractNumber]['l5_middlename'], contractData[contractNumber]['l5_lastname']].filter(Boolean).join(' ');
            const amount = contractData[contractNumber]['amount'];
            const inputted_amount = contractData[contractNumber]['inputted_amount'];
            const net_of_vat = contractData[contractNumber]['net_of_vat'];
            const vat_type = contractData[contractNumber]['vat_type'];
            const vat_amount = contractData[contractNumber]['vat_amount'];
            const wtax = contractData[contractNumber]['wtax'];
            const total_month_rental = contractData[contractNumber]['total_month_rental'];
            const amount_lessor = contractData[contractNumber]['amount_lessor'];
            const edit_amount_lessor = contractData[contractNumber]['edit_amount_lessor'];
            const mode_of_payment = contractData[contractNumber]['mode_of_payment'];
            const authorize_firstName = contractData[contractNumber]['authorize_firstName'];
            const authorize_middleName = contractData[contractNumber]['authorize_middleName'];
            const authorize_lastName = contractData[contractNumber]['authorize_lastName'];
            const authorize_gender = contractData[contractNumber]['authorize_gender'];
            const authorize_mobileNumber = contractData[contractNumber]['authorize_mobileNumber'];

            if (region) {
                regionInput.value = region;
            }
            if (area) {
                areaInput.value = area;
            }
            if (branch) {
                branchInput.value = branch;
            }
            if (kpxCode) {
                kpxCodeInput.value = kpxCode;
            }
            if (branchId) {
                branchIdInput.value = branchId;
            }
            if (branchCode) {
                branchCodeInput.value = branchCode;
            }
            if (zone) {
                zoneInput.value = zone;
            }
            if (mainzone) {
                mainzoneInput.value = mainzone;
            }
            if (l1_firstname) {
                l1_firstnameInput.value = l1_firstname;
            }
            if (l1_middlename) {
                l1_middlenameInput.value = l1_middlename;
            }
            if (l1_lastname) {
                l1_lastnameInput.value = l1_lastname;
            }
            if (l1_gender) {
                l1_genderInput.value = l1_gender;
            }
            if (l1_mobileNumber) {
                l1_mobilenumberInput.value = l1_mobileNumber;
            }
            if (lessor_type) {
                lessor_typeInput.value = lessor_type;
            }
            if (lessor_name) {
                lessor_nameInput.value = lessor_name;
            }
            if (l2_firstname) {
                l2_firstnameInput.value = l2_firstname;
            }
            if (l2_middlename) {
                l2_middlenameInput.value = l2_middlename;
            }
            if (l2_lastname) {
                l2_lastnameInput.value = l2_lastname;
            }
            if (l2_gender) {
                l2_genderInput.value = l2_gender;
            }
            if (l2_mobileNumber) {
                l2_mobilenumberInput.value = l2_mobileNumber;
            }
            if (lessor_name2) {
                lessor_name2Input.value = lessor_name2;
            }
            if (l3_firstname) {
                l3_firstnameInput.value = l3_firstname;
            }
            if (l3_middlename) {
                l3_middlenameInput.value = l3_middlename;
            }
            if (l3_lastname) {
                l3_lastnameInput.value = l3_lastname;
            }
            if (l3_gender) {
                l3_genderInput.value = l3_gender;
            }
            if (l3_mobileNumber) {
                l3_mobilenumberInput.value = l3_mobileNumber;
            }
            if (lessor_name3) {
                lessor_name3Input.value = lessor_name3;
            }
            if (l4_firstname) {
                l4_firstnameInput.value = l4_firstname;
            }
            if (l4_middlename) {
                l4_middlenameInput.value = l4_middlename;
            }
            if (l4_lastname) {
                l4_lastnameInput.value = l4_lastname;
            }
            if (l4_gender) {
                l4_genderInput.value = l4_gender;
            }
            if (l4_mobileNumber) {
                l4_mobilenumberInput.value = l4_mobileNumber;
            }
            if (lessor_name4) {
                lessor_name4Input.value = lessor_name4;
            }
            if (l5_firstname) {
                l5_firstnameInput.value = l5_firstname;
            }
            if (l5_middlename) {
                l5_middlenameInput.value = l5_middlename;
            }
            if (l5_lastname) {
                l5_lastnameInput.value = l5_lastname;
            }
            if (l5_gender) {
                l5_genderInput.value = l5_gender;
            }
            if (l5_mobileNumber) {
                l5_mobilenumberInput.value = l5_mobileNumber;
            }
            if (lessor_name5) {
                lessor_name5Input.value = lessor_name5;
            }
            if (amount) {
                amountInput.value = amount;
            }
            if (inputted_amount) {
                inputtedAmount.value = inputted_amount;
            }
            if (net_of_vat) {
                netOfVatInput.value = net_of_vat;
            }
            if (vat_type) {
               selectVatInput.value = vat_type;
            }
            if (vat_amount) {
               vatInput.value = vat_amount;
            }
            if (wtax) {
               wtaxInput.value = wtax;
            }
            if (total_month_rental) {
               totalMonthRentalInput.value = total_month_rental;
            }
            if (amount_lessor) {
              amountLessorInput.value = amount_lessor;
            }
             if (edit_amount_lessor) {
              editAmountLessorInput.value = edit_amount_lessor;
            }
            if (mode_of_payment) {
              modeOfPaymentInput.value = mode_of_payment;
            }
            if (authorize_firstName) {
              authorizeFirstnameInput.value = authorize_firstName;
            }
            if (authorize_middleName) {
              authorizeMiddlenameInput.value = authorize_middleName;
            }
            if (authorize_lastName) {
              authorizeLastnameInput.value = authorize_lastName;
            }
            if (authorize_gender) {
              authorizeGenderInput.value = authorize_gender;
            }
            if (authorize_mobileNumber) {
              authorizeMobileNumberInput.value = authorize_mobileNumber;
            }
        }
    }  
    document.getElementById('fileUpload').addEventListener('change', function(event) {
        const filePreview = document.getElementById('filePreview');
        filePreview.innerHTML = ''; 
        const file = event.target.files[0];
        if (file) {
            const fileType = file.type;
            const validFileType = 'application/pdf';
            if (fileType === validFileType) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const arrayBuffer = e.target.result;
                    const blob = new Blob([arrayBuffer], { type: validFileType });
                    const url = URL.createObjectURL(blob);
                    const iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.width = '100%';
                    iframe.height = '500px';
                    iframe.style.border = 'none';
                    filePreview.appendChild(iframe);
                    iframe.onerror = function() {
                        filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                    };
                };
                reader.readAsArrayBuffer(file);
            } else {
                event.target.value = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid file type',
                    text: 'Please upload a PDF file only.'
                });
            }
        }
    });
    document.getElementById('fileUpload2').addEventListener('change', function(event) {
        const filePreview = document.getElementById('filePreview2');
        filePreview.innerHTML = ''; 
        const file = event.target.files[0];
        if (file) {
            const fileType = file.type;
            const validFileType = 'application/pdf';
            if (fileType === validFileType) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const arrayBuffer = e.target.result;
                    const blob = new Blob([arrayBuffer], { type: validFileType });
                    const url = URL.createObjectURL(blob);
                    const iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.width = '100%';
                    iframe.height = '500px';
                    iframe.style.border = 'none';
                    filePreview.appendChild(iframe);
                    iframe.onerror = function() {
                        filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                    };
                };
                reader.readAsArrayBuffer(file);
            } else {
                event.target.value = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid file type',
                    text: 'Please upload a PDF file only.'
                });
            }
        }
    });
    document.getElementById('fileUpload3').addEventListener('change', function(event) {
        const filePreview = document.getElementById('filePreview3');
        filePreview.innerHTML = ''; 
        const file = event.target.files[0];
        if (file) {
            const fileType = file.type;
            const validFileType = 'application/pdf';
            if (fileType === validFileType) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const arrayBuffer = e.target.result;
                    const blob = new Blob([arrayBuffer], { type: validFileType });
                    const url = URL.createObjectURL(blob);
                    const iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.width = '100%';
                    iframe.height = '500px';
                    iframe.style.border = 'none';
                    filePreview.appendChild(iframe);
                    iframe.onerror = function() {
                        filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                    };
                };
                reader.readAsArrayBuffer(file);
            } else {
                event.target.value = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid file type',
                    text: 'Please upload a PDF file only.'
                });
            }
        }
    });

    document.getElementById('fileUpload4').addEventListener('change', function(event) {
        const filePreview = document.getElementById('filePreview4');
        filePreview.innerHTML = ''; 
        const file = event.target.files[0];
        if (file) {
            const fileType = file.type;
            const validFileType = 'application/pdf';
            if (fileType === validFileType) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const arrayBuffer = e.target.result;
                    const blob = new Blob([arrayBuffer], { type: validFileType });
                    const url = URL.createObjectURL(blob);
                    const iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.width = '100%';
                    iframe.height = '500px';
                    iframe.style.border = 'none';
                    filePreview.appendChild(iframe);
                    iframe.onerror = function() {
                        filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                    };
                };
                reader.readAsArrayBuffer(file);
            } else {
                event.target.value = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid file type',
                    text: 'Please upload a PDF file only.'
                });
            }
        }
    });
    document.getElementById('fileUpload5').addEventListener('change', function(event) {
        const filePreview = document.getElementById('filePreview5');
        filePreview.innerHTML = ''; 
        const file = event.target.files[0];
        if (file) {
            const fileType = file.type;
            const validFileType = 'application/pdf';
            if (fileType === validFileType) {
                const reader = new FileReader();
                reader.onload = function(e) {
                const arrayBuffer = e.target.result;
                const blob = new Blob([arrayBuffer], { type: validFileType });
                const url = URL.createObjectURL(blob);
                const iframe = document.createElement('iframe');
                iframe.src = url;
                iframe.width = '100%';
                iframe.height = '500px';
                iframe.style.border = 'none';
                filePreview.appendChild(iframe);
                iframe.onerror = function() {
                    filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                };
            };
            reader.readAsArrayBuffer(file); 
            } else {
                event.target.value = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid file type',
                    text: 'Please upload a PDF file only.'
                });
            }
        }
    });
    document.getElementById('fileUpload6').addEventListener('change', function(event) {
        const filePreview = document.getElementById('filePreview6');
        filePreview.innerHTML = '';
        const file = event.target.files[0];
        if (file) {
            const fileType = file.type;
            const validFileType = 'application/pdf';
            if (fileType === validFileType) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const arrayBuffer = e.target.result;
                    const blob = new Blob([arrayBuffer], { type: validFileType });
                    const url = URL.createObjectURL(blob);
                    const iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.width = '100%';
                    iframe.height = '500px';
                    iframe.style.border = 'none';
                    filePreview.appendChild(iframe);
                    iframe.onerror = function() {
                        filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                    };
                };
                reader.readAsArrayBuffer(file);
            } else {
                event.target.value = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid file type',
                    text: 'Please upload a PDF file only.'
                });
            }
        }
    });
    document.getElementById('fileUpload7').addEventListener('change', function(event) {
        const filePreview = document.getElementById('filePreview7');
        filePreview.innerHTML = '';
        const file = event.target.files[0];
        if (file) {
            const fileType = file.type;
            const validFileType = 'application/pdf';
            if (fileType === validFileType) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const arrayBuffer = e.target.result;
                    const blob = new Blob([arrayBuffer], { type: validFileType });
                    const url = URL.createObjectURL(blob);
                    const iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.width = '100%';
                    iframe.height = '500px';
                    iframe.style.border = 'none';
                    filePreview.appendChild(iframe);
                    iframe.onerror = function() {
                        filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                    };
                };
                reader.readAsArrayBuffer(file);
            } else {
                event.target.value = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid file type',
                    text: 'Please upload a PDF file only.'
                });
            }
        }
    });
    document.getElementById('fileUpload8').addEventListener('change', function(event) {
        const filePreview = document.getElementById('filePreview8');
        filePreview.innerHTML = ''; // Clear previous preview
        const file = event.target.files[0];
        if (file) {
            const fileType = file.type;
            const validFileType = 'application/pdf';
            if (fileType === validFileType) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const arrayBuffer = e.target.result;
                    const blob = new Blob([arrayBuffer], { type: validFileType });
                    const url = URL.createObjectURL(blob);
                    const iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.width = '100%';
                    iframe.height = '500px';
                    iframe.style.border = 'none';
                    filePreview.appendChild(iframe);
                    iframe.onerror = function() {
                        filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                    };
                };
                reader.readAsArrayBuffer(file);
            } else {
                event.target.value = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid file type',
                    text: 'Please upload a PDF file only.'
                });
            }
        }
    });
    document.getElementById('fileUpload9').addEventListener('change', function(event) {
        const filePreview = document.getElementById('filePreview9');
        filePreview.innerHTML = '';
        const file = event.target.files[0];
        if (file) {
            const fileType = file.type;
            const validFileType = 'application/pdf';
            if (fileType === validFileType) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const arrayBuffer = e.target.result;
                    const blob = new Blob([arrayBuffer], { type: validFileType });
                    const url = URL.createObjectURL(blob);
                    const iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.width = '100%';
                    iframe.height = '500px';
                    iframe.style.border = 'none';
                    filePreview.appendChild(iframe);
                    iframe.onerror = function() {
                        filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                    };
                };
                reader.readAsArrayBuffer(file);
            } else {
                event.target.value = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid file type',
                    text: 'Please upload a PDF file only.'
                });
            }
        }
    });
    document.getElementById('fileUpload10').addEventListener('change', function(event) {
        const filePreview = document.getElementById('filePreview10');
        filePreview.innerHTML = '';
        const file = event.target.files[0];
        if (file) {
            const fileType = file.type;
            const validFileType = 'application/pdf';
            if (fileType === validFileType) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const arrayBuffer = e.target.result;
                    const blob = new Blob([arrayBuffer], { type: validFileType });
                    const url = URL.createObjectURL(blob);
                    const iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.width = '100%';
                    iframe.height = '500px';
                    iframe.style.border = 'none';
                    filePreview.appendChild(iframe);
                    iframe.onerror = function() {
                        filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                    };
                };
                reader.readAsArrayBuffer(file);
            } else {
                event.target.value = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid file type',
                    text: 'Please upload a PDF file only.'
                });
            }
        }
    });
    document.getElementById('fileUpload11').addEventListener('change', function(event) {
        const filePreview = document.getElementById('filePreview11');
        filePreview.innerHTML = ''; // Clear previous preview
        const file = event.target.files[0];
        if (file) {
            const fileType = file.type;
            const validFileType = 'application/pdf';
            if (fileType === validFileType) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const arrayBuffer = e.target.result;
                    const blob = new Blob([arrayBuffer], { type: validFileType });
                    const url = URL.createObjectURL(blob);
                    const iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.width = '100%';
                    iframe.height = '500px';
                    iframe.style.border = 'none';
                    filePreview.appendChild(iframe);
                    iframe.onerror = function() {
                        filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                    };
                };
                reader.readAsArrayBuffer(file);
            } else {
                event.target.value = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid file type',
                    text: 'Please upload a PDF file only.'
                });
            }
        }
    });
    document.getElementById('fileUpload12').addEventListener('change', function(event) {
        const filePreview = document.getElementById('filePreview12');
        filePreview.innerHTML = '';
        const file = event.target.files[0];
        if (file) {
            const fileType = file.type;
            const validFileType = 'application/pdf';
            if (fileType === validFileType) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const arrayBuffer = e.target.result;
                    const blob = new Blob([arrayBuffer], { type: validFileType });
                    const url = URL.createObjectURL(blob);
                    const iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.width = '100%';
                    iframe.height = '500px';
                    iframe.style.border = 'none';
                    filePreview.appendChild(iframe);
                    iframe.onerror = function() {
                        filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                    };
                };
                reader.readAsArrayBuffer(file);
            } else {
                event.target.value = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid file type',
                    text: 'Please upload a PDF file only.'
                });
            }
        }
    });
    document.getElementById('fileUpload13').addEventListener('change', function(event) {
        const filePreview = document.getElementById('filePreview13');
        filePreview.innerHTML = '';
        const file = event.target.files[0];
        if (file) {
            const fileType = file.type;
            const validFileType = 'application/pdf';
            if (fileType === validFileType) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const arrayBuffer = e.target.result;
                    const blob = new Blob([arrayBuffer], { type: validFileType });
                    const url = URL.createObjectURL(blob);
                    const iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.width = '100%';
                    iframe.height = '500px';
                    iframe.style.border = 'none';
                    filePreview.appendChild(iframe);
                    iframe.onerror = function() {
                        filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                    };
                };
                reader.readAsArrayBuffer(file);
            } else {
                event.target.value = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid file type',
                    text: 'Please upload a PDF file only.'
                });
            }
        }
    });
    document.getElementById('fileUpload14').addEventListener('change', function(event) {
        const filePreview = document.getElementById('filePreview14');
        filePreview.innerHTML = '';
        const file = event.target.files[0];
        if (file) {
            const fileType = file.type;
            const validFileType = 'application/pdf';
            if (fileType === validFileType) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const arrayBuffer = e.target.result;
                    const blob = new Blob([arrayBuffer], { type: validFileType });
                    const url = URL.createObjectURL(blob);
                    const iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.width = '100%';
                    iframe.height = '500px';
                    iframe.style.border = 'none';
                    filePreview.appendChild(iframe);
                    iframe.onerror = function() {
                        filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                    };
                };
                reader.readAsArrayBuffer(file);
            } else {
                event.target.value = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid file type',
                    text: 'Please upload a PDF file only.'
                });
            }
        }
    });
    document.getElementById('fileUpload15').addEventListener('change', function(event) {
        const filePreview = document.getElementById('filePreview15');
        filePreview.innerHTML = '';
        const file = event.target.files[0];
        if (file) {
            const fileType = file.type;
            const validFileType = 'application/pdf';
            if (fileType === validFileType) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const arrayBuffer = e.target.result;
                    const blob = new Blob([arrayBuffer], { type: validFileType });
                    const url = URL.createObjectURL(blob);
                    const iframe = document.createElement('iframe');
                    iframe.src = url;
                    iframe.width = '100%';
                    iframe.height = '500px';
                    iframe.style.border = 'none';
                    filePreview.appendChild(iframe);
                    iframe.onerror = function() {
                        filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                    };
                };
                reader.readAsArrayBuffer(file); 
            } else {
                event.target.value = '';
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid file type',
                    text: 'Please upload a PDF file only.'
                });
            }
        }
    });
    document.addEventListener('DOMContentLoaded', function() {
        const authButton = document.getElementById('add_LessorBtn');
        const lessorsDiv = document.getElementById('lessors_div');
        authButton.addEventListener('click', function() {
            if (lessorsDiv.style.display === 'none') {
                lessorsDiv.style.display = 'block';
            } else {
                lessorsDiv.style.display = 'none';
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const authButton = document.getElementById('auth_btn');
        const authorizeDiv = document.getElementById('authorize_div');
        authButton.addEventListener('click', function() {
            if (authorizeDiv.style.display === 'none') {
                authorizeDiv.style.display = 'block';
            } else {
                authorizeDiv.style.display = 'none';
            }
        });
    });
    function limitMobileNumber(input) {
        if (event.keyCode === 8 || event.keyCode === 46) {
            return;
        }
        input.value = input.value.replace(/[^\d+]/g, '');
        if (input.value.length > 11) {
            input.value = input.value.slice(0, 11);
        }
    }
    function calculateVat() {
        var amount = parseFloat(document.getElementById('amount').value);
        var selectVat = document.getElementById('select_vat');
        var wTaxTypeLbl = document.getElementById('wtaxType_lbl');
        var selectWtax = document.getElementById('select_wtax');
        var selectPercent = document.getElementById('select_percent');
        var netOfVat_lbl = document.getElementById('netOfVat_lbl');
        var net_Of_Vat = document.getElementById('net_of_vat');
        var amount_comp_lbl = document.getElementById('amountComp_lbl');
        var amount_comp = document.getElementById('amountComp');
        var netOfVat = document.getElementById('vat');
        var vatLbl = document.getElementById('vat_lbl');
        var netOfWtax = document.getElementById('w-tax');
        var wTaxLbl = document.getElementById('wtax_lbl');
        var grossAmountInput = document.getElementById('gross_amount');
        var grossLbl = document.getElementById('gross_lbl');
        var amountToLessor = document.getElementById('amount_lessor');
        var editAmountToLessor = document.getElementById('edit_amount_lessor');
        var amountToLessorLbl = document.getElementById('amount_lessor_lbl');
        if (!isNaN(amount) && amount > 0) {
            selectVat.disabled = false;
            selectWtax.disabled = false;
            selectPercent.disabled = false;
            if (selectVat.value === "Vatable") {
                Swal.fire({
                    title: 'Is the amount inputted with VAT or without VAT?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'With VAT',
                    cancelButtonText: 'Without VAT',
                    reverseButtons: true
                }).then((result) => {
                    var vatAmount = 0, netOf_Vat = 0;
                    if (result.isConfirmed) {
                        netOf_Vat = amount / 1.12;
                        vatAmount = amount - netOf_Vat;
                        selectWtax.style.display = 'block';
                        wTaxTypeLbl.style.display = 'block';
                        net_Of_Vat.value = netOf_Vat.toFixed(2);
                        amount_comp_lbl.style.display = 'block';
                        amount_comp.value = 'With VAT';
                    } else { 
                        vatAmount = amount * 0.12;
                        netOf_Vat = amount;
                        selectWtax.style.display = 'block';
                        wTaxTypeLbl.style.display = 'block';
                        net_Of_Vat.value = netOf_Vat.toFixed(2);
                        amount_comp_lbl.style.display = 'block';
                        amount_comp.value = 'Without VAT';
                    }
                    netOfVat.value = vatAmount.toFixed(2); 
                    netOfVat.style.display = 'block';
                    vatLbl.style.display = 'block';

                    netOfVat_lbl.style.display = 'block';
                    net_Of_Vat.style.display = 'block';

                    amount_comp_lbl.style.display = 'block';
                    amount_comp.style.display = 'block';

                    calculateWtax(vatAmount);
                });
            }else if(selectVat.value === "Non Vatable"){
                vat = 0;
                netVat = 0;
                net_Of_Vat.value = vat.toFixed(2);
                netOfVat.value = vat.toFixed(2);
                netOfVat.style.display = 'block';
                vatLbl.style.display = 'block';
                netOfVat_lbl.style.display = 'block';
                net_Of_Vat.style.display = 'block';
                selectWtax.style.display = 'block';
                wTaxTypeLbl.style.display = 'block';
            }
            else if(selectVat.value === "Vat Exempt"){
                vat = 0;
                netVat = 0;
                net_Of_Vat.value = vat.toFixed(2);
                netOfVat.value = vat.toFixed(2);
                netOfVat.style.display = 'block';
                vatLbl.style.display = 'block';
                netOfVat_lbl.style.display = 'block';
                net_Of_Vat.style.display = 'block';
                selectWtax.style.display = 'block';
                wTaxTypeLbl.style.display = 'block';
            }
        }
    }

    function calculateWtax(vatAmount) {
        var amount = parseFloat(document.getElementById('amount').value);
        var selectVat = document.getElementById('select_vat');
        var selectWtax = document.getElementById('select_wtax');
        var percentLbl = document.getElementById('percent_lbl');
        var selectPercent = document.getElementById('select_percent');
        var amount_comp_lbl = document.getElementById('amountComp_lbl');
        var amount_comp = document.getElementById('amountComp');
        var netOfVat_lbl = document.getElementById('netOfVat_lbl');
        var net_Of_Vat = parseFloat(document.getElementById('net_of_vat').value);
        var netOfVat = parseFloat(document.getElementById('vat').value);
        var vatLbl = document.getElementById('vat_lbl');
        var netOfWtax = document.getElementById('w-tax');
        var wTaxLbl = document.getElementById('wtax_lbl');
        var grossAmountInput = document.getElementById('gross_amount');
        var grossLbl = document.getElementById('gross_lbl');
        var amountToLessor = document.getElementById('amount_lessor');
        var editAmountToLessor = document.getElementById('edit_amount_lessor');
        var amountToLessorLbl = document.getElementById('amount_lessor_lbl');
        if(selectWtax.value === 'less_wtax' || selectWtax.value === 'net_wtax'){
            selectPercent.style.display = 'block';
            percentLbl.style.display = 'block';
        }
        if (!isNaN(amount) && amount > 0) {
            var vatAmount = 0, wtax = 0, netAmount = 0, totalExp = 0, vatPlusAmount = 0;
            if (selectWtax.value === "less_wtax" && selectPercent.value === "5") {
                wtax = net_Of_Vat * 0.05; 
                netOfWtax.value = wtax.toFixed(2);
                netOfWtax.style.display = 'block';
                wTaxLbl.style.display = 'block';
                if(amount_comp.value === 'With VAT'){
                    var totalExp = amount + wtax;
                }else{
                    var totalExp = net_Of_Vat + netOfVat + wtax;
                }
                grossAmountInput.value = totalExp.toFixed(2);
                grossAmountInput.style.display = 'block';
                grossLbl.style.display = 'block';
                netAmount = amount - wtax;
                amountToLessor.value = netAmount.toFixed(2);
                editAmountToLessor.value = netAmount.toFixed(2);
                amountToLessor.style.display = 'none';
                editAmountToLessor.style.display = 'block';
                amountToLessorLbl.style.display = 'block'; 
            }else if(selectWtax.value === "net_wtax" && selectPercent.value === "5") {
                wtax = net_Of_Vat * 0.05; 
                netOfWtax.value = wtax.toFixed(2);
                netOfWtax.style.display = 'block';
                wTaxLbl.style.display = 'block';
                if(amount_comp.value === 'With VAT'){
                    var totalExp = amount + wtax;
                }else{
                    var totalExp = net_Of_Vat + netOfVat + wtax;
                }
                grossAmountInput.value = totalExp.toFixed(2);
                grossAmountInput.style.display = 'block';
                grossLbl.style.display = 'block';
                if(amount_comp.value === 'Without VAT'){
                    netAmount = net_Of_Vat + netOfVat;
                    amountToLessor.value = netAmount.toFixed(2);
                    editAmountToLessor.value = netAmount.toFixed(2);
                }else{
                    netAmount = amount;
                    amountToLessor.value = netAmount.toFixed(2);
                    editAmountToLessor.value = netAmount.toFixed(2);
                }        
                amountToLessor.style.display = 'none';
                editAmountToLessor.style.display = 'block';
                amountToLessorLbl.style.display = 'block';
            }
            if(selectVat.value === "Non Vatable" && selectWtax.value === "less_wtax" && selectPercent.value === "5"){
                wtax = amount * 0.05;
                netOfWtax.value = wtax.toFixed(2);
                netOfWtax.style.display = 'block';
                wTaxLbl.style.display = 'block';
                netAmount = amount - wtax;
                var totalExp = amount + wtax;
                grossAmountInput.value = totalExp.toFixed(2);
                amountToLessor.value = netAmount.toFixed(2);
                editAmountToLessor.value = netAmount.toFixed(2);
            }else if(selectVat.value === "Non Vatable" && selectWtax.value === "net_wtax" && selectPercent.value === "5"){
                wtax_amount = amount / 0.95;
                wtax = wtax_amount * 0.05;
                netOfWtax.value = wtax.toFixed(2);
                netOfWtax.style.display = 'block';
                wTaxLbl.style.display = 'block';
                netAmount = amount;
                var totalExp = amount + wtax;
                grossAmountInput.value = totalExp.toFixed(2);
                amountToLessor.value = netAmount.toFixed(2);
                editAmountToLessor.value = netAmount.toFixed(2);
            }
            if(selectVat.value === "Vat Exempt" && selectWtax.value === "less_wtax" && selectPercent.value === "5"){
                wtax = amount * 0.05;
                netOfWtax.value = wtax.toFixed(2);
                netOfWtax.style.display = 'block';
                wTaxLbl.style.display = 'block';
                netAmount = amount - wtax; 
                var totalExp = amount + wtax;
                grossAmountInput.value = totalExp.toFixed(2);
                amountToLessor.value = netAmount.toFixed(2);
                editAmountToLessor.value = netAmount.toFixed(2);
            }else if(selectVat.value === "Vat Exempt" && selectWtax.value === "net_wtax" && selectPercent.value === "5"){
                wtax_amount = amount / 0.95; 
                wtax = wtax_amount * 0.05;
                netOfWtax.value = wtax.toFixed(2);
                netOfWtax.style.display = 'block';
                wTaxLbl.style.display = 'block';
                netAmount = amount;
                var totalExp = amount + wtax;
                grossAmountInput.value = totalExp.toFixed(2);
                amountToLessor.value = netAmount.toFixed(2);
                editAmountToLessor.value = netAmount.toFixed(2);
            }
        }
    }

    function toggleWalletInput() {
        var modeOfPayment = document.getElementById('modeOfPayment');
        var walletNumberInput = document.getElementById('walletNumber');
        if (modeOfPayment.value === 'WALLET') {
            walletNumberInput.style.display = 'block'; // Display walletNumber input
        } else {
            walletNumberInput.style.display = 'none'; // Hide walletNumber input
            walletNumberInput.value = ''; // Clear walletNumber value
        }
    }
    document.addEventListener("DOMContentLoaded", function() {
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        const paymentDueDateInput = document.getElementById('paymentDueDate');
        function syncDates() {
            const startDateValue = new Date(startDateInput.value);
            endDateInput.value = startDateInput.value;
            paymentDueDateInput.value = formatDate(startDateValue);
        }
        function formatDate(date) {
            const month = (date.getMonth() + 1).toString().padStart(2, '0'); // Month is zero-indexed
            const day = date.getDate().toString().padStart(2, '0');
            const year = date.getFullYear();
            return `${year}-${month}-${day}`;
        }
        startDateInput.addEventListener('change', syncDates);
        syncDates();
        paymentDueDateInput.addEventListener('input', function() {
            const day = this.value.split('-')[2]; // Extract day part
            this.value = formatDate(new Date(startDateInput.value)).split('-')[0] + '-' + formatDate(new Date(startDateInput.value)).split('-')[1] + '-' + day;
        });
    });
</script>
</body>
</html>


