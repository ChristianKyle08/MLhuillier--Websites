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
    // Collect form data and prepare variables
    $zone = $_POST['zone'];
    $mainzone = $_POST['mainzone'];
    $branch_code = $_POST['branch_code'];
    $branch = mysqli_real_escape_string($conn, $_POST['branch']);
    $region = mysqli_real_escape_string($conn, $_POST['region']);
    $area = mysqli_real_escape_string($conn, $_POST['area']);
    $corporateName = mysqli_real_escape_string($conn, $_POST['corporate_name']);
    $rdo = mysqli_real_escape_string($conn, $_POST['rdo']);
    $lessor_type = mysqli_real_escape_string($conn, $_POST['lessor_type']);
    $lessor_corporateName = mysqli_real_escape_string($conn, $_POST['corporateName']);
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

    $contract_start = mysqli_real_escape_string($conn, $_POST['startDate']);
    $contract_end = mysqli_real_escape_string($conn, $_POST['endDate']);
    $paymentDueDate = mysqli_real_escape_string($conn, $_POST['paymentDueDate']);
    $created_date = mysqli_real_escape_string($conn, $_POST['created_date']);

    // $securtiyDeposit = mysqli_real_escape_string($conn, $_POST['securityDep']);
    // $depositAmount = isset($_POST['depositAmount']) ? $_POST['depositAmount'] : '';
    // $advanceRental_months = mysqli_real_escape_string($conn, $_POST['advanceRental_months']);
    // $advanceAmount = isset($_POST['advanceAmount']) ? $_POST['advanceAmount'] : '';

    $amount = $_POST['rental_amount'];
    $vat_type = mysqli_real_escape_string($conn, $_POST['select_vat']);
    $inputted_amount = mysqli_real_escape_string($conn, $_POST['amountComp']);
    $netOfVat = $_POST['net_of_vat'];
    $vat_amount = isset($_POST['vat']) ? $_POST['vat'] : '';
    $wtax = isset($_POST['w-tax']) ? $_POST['w-tax'] : '';
    $gross_amount = isset($_POST['gross_amount']) ? $_POST['gross_amount'] : '';
    $amount_lessor = isset($_POST['amount_lessor']) ? $_POST['amount_lessor'] : '';
    $edit_amount_lessor = isset($_POST['edit_amount_lessor']) ? $_POST['edit_amount_lessor'] : '';

    // $mode_of_payment = mysqli_real_escape_string($conn, $_POST['modeOfPayment']);
    $bank_name = mysqli_real_escape_string($conn, $_POST['bankName']);
    $bank_acc_number = mysqli_real_escape_string($conn, $_POST['bank_acc_number']);

    $wallet_number = $_POST['walletNumber'];
    $status = 'Active';
    $request_status = 'Created';
    $created_by =  $_SESSION['user_name'];
    $authorize_firstname = mysqli_real_escape_string($conn, $_POST['authorize_firstname']);
    $authorize_middlename = mysqli_real_escape_string($conn, $_POST['authorize_middlename']);
    $authorize_lastname = mysqli_real_escape_string($conn, $_POST['authorize_lastname']);
    $authorize_gender = mysqli_real_escape_string($conn, $_POST['authorize_gender']);
    $authorize_mobileNumber = mysqli_real_escape_string($conn, $_POST['authorize_mobileNumber']);

    $notarized = mysqli_real_escape_string($conn, $_POST['notarized']);
    $created_ctr = 1;

    // Initialize file variables
    $fileContent = $mimeType = $fileName = '';
    if (isset($_FILES['fileUpload']) && $_FILES['fileUpload']['error'] === UPLOAD_ERR_OK) {
        $fileTmpName = $_FILES['fileUpload']['tmp_name'];
        $fileName = mysqli_real_escape_string($conn, $_FILES['fileUpload']['name']);
        $fileContent = mysqli_real_escape_string($conn, file_get_contents($fileTmpName));
        $mimeType = mysqli_real_escape_string($conn, mime_content_type($fileTmpName));
    }

    // Initialize second file variables
    $fileContent2 = $mimeType2 = $fileName2 = '';
    if (isset($_FILES['fileUpload2']) && $_FILES['fileUpload2']['error'] === UPLOAD_ERR_OK) {
        $fileTmpName2 = $_FILES['fileUpload2']['tmp_name'];
        $fileName2 = mysqli_real_escape_string($conn, $_FILES['fileUpload2']['name']);
        $fileContent2 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName2));
        $mimeType2 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName2)); 
    }

    // Initialize second file variables
    $fileContent3 = $mimeType3 = $fileName3 = '';
    if (isset($_FILES['fileUpload3']) && $_FILES['fileUpload3']['error'] === UPLOAD_ERR_OK) {
        $fileTmpName3 = $_FILES['fileUpload3']['tmp_name'];
        $fileName3 = mysqli_real_escape_string($conn, $_FILES['fileUpload3']['name']);
        $fileContent3 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName3));
        $mimeType3 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName3)); 
    }
    
    // Initialize second file variables
    $fileContent4 = $mimeType4 = $fileName4 = '';
    if (isset($_FILES['fileUpload4']) && $_FILES['fileUpload4']['error'] === UPLOAD_ERR_OK) {
        $fileTmpName4 = $_FILES['fileUpload4']['tmp_name'];
        $fileName4 = mysqli_real_escape_string($conn, $_FILES['fileUpload4']['name']);
        $fileContent4 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName4));
        $mimeType4 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName4)); 
    }

    // Initialize second file variables
    $fileContent5 = $mimeType5 = $fileName5 = '';
    if (isset($_FILES['fileUpload5']) && $_FILES['fileUpload5']['error'] === UPLOAD_ERR_OK) {
        $fileTmpName5 = $_FILES['fileUpload5']['tmp_name'];
        $fileName5 = mysqli_real_escape_string($conn, $_FILES['fileUpload5']['name']);
        $fileContent5 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName5));
        $mimeType5 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName5)); 
    }

    // Initialize second file variables
    $fileContent6 = $mimeType6 = $fileName6 = '';
    if (isset($_FILES['fileUpload6']) && $_FILES['fileUpload6']['error'] === UPLOAD_ERR_OK) {
        $fileTmpName6 = $_FILES['fileUpload6']['tmp_name'];
        $fileName6 = mysqli_real_escape_string($conn, $_FILES['fileUpload6']['name']);
        $fileContent6 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName6));
        $mimeType6 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName6)); 
    }

    // Initialize second file variables
    $fileContent7 = $mimeType7 = $fileName7 = '';
    if (isset($_FILES['fileUpload7']) && $_FILES['fileUpload7']['error'] === UPLOAD_ERR_OK) {
        $fileTmpName7 = $_FILES['fileUpload7']['tmp_name'];
        $fileName7 = mysqli_real_escape_string($conn, $_FILES['fileUpload7']['name']);
        $fileContent7 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName7));
        $mimeType7 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName7));
    }

    // Initialize second file variables
    $fileContent8 = $mimeType8 = $fileName8 = '';
    if (isset($_FILES['fileUpload8']) && $_FILES['fileUpload8']['error'] === UPLOAD_ERR_OK) {
        $fileTmpName8 = $_FILES['fileUpload8']['tmp_name'];
        $fileName8 = mysqli_real_escape_string($conn, $_FILES['fileUpload8']['name']);
        $fileContent8 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName8));
        $mimeType8 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName8));
    }
    
    // Initialize second file variables
    $fileContent9 = $mimeType9 = $fileName9 = '';
    if (isset($_FILES['fileUpload9']) && $_FILES['fileUpload9']['error'] === UPLOAD_ERR_OK) {
        $fileTmpName9 = $_FILES['fileUpload9']['tmp_name'];
        $fileName9 = mysqli_real_escape_string($conn, $_FILES['fileUpload9']['name']);
        $fileContent9 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName9));
        $mimeType9 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName9));
    }

    // Initialize second file variables
    $fileContent10 = $mimeType10 = $fileName10 = '';
    if (isset($_FILES['fileUpload10']) && $_FILES['fileUpload10']['error'] === UPLOAD_ERR_OK) {
        $fileTmpName10 = $_FILES['fileUpload10']['tmp_name'];
        $fileName10 = mysqli_real_escape_string($conn, $_FILES['fileUpload10']['name']);
        $fileContent10 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName10));
        $mimeType10 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName10));
    }

     // Initialize second file variables
     $fileContent11 = $mimeType11 = $fileName11 = '';
     if (isset($_FILES['fileUpload11']) && $_FILES['fileUpload11']['error'] === UPLOAD_ERR_OK) {
         $fileTmpName11 = $_FILES['fileUpload11']['tmp_name'];
         $fileName11 = mysqli_real_escape_string($conn, $_FILES['fileUpload11']['name']);
         $fileContent11 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName11));
         $mimeType11 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName11));
     }

      // Initialize second file variables
    $fileContent12 = $mimeType12 = $fileName12 = '';
    if (isset($_FILES['fileUpload12']) && $_FILES['fileUpload12']['error'] === UPLOAD_ERR_OK) {
        $fileTmpName12 = $_FILES['fileUpload12']['tmp_name'];
        $fileName12 = mysqli_real_escape_string($conn, $_FILES['fileUpload12']['name']);
        $fileContent12 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName12));
        $mimeType12 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName12));
    }

     // Initialize second file variables
     $fileContent13 = $mimeType13 = $fileName13 = '';
     if (isset($_FILES['fileUpload13']) && $_FILES['fileUpload13']['error'] === UPLOAD_ERR_OK) {
         $fileTmpName13 = $_FILES['fileUpload13']['tmp_name'];
         $fileName13 = mysqli_real_escape_string($conn, $_FILES['fileUpload13']['name']);
         $fileContent13 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName13));
         $mimeType13 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName13));
     }

      // Initialize second file variables
    $fileContent14 = $mimeType14 = $fileName14 = '';
    if (isset($_FILES['fileUpload14']) && $_FILES['fileUpload14']['error'] === UPLOAD_ERR_OK) {
        $fileTmpName14 = $_FILES['fileUpload14']['tmp_name'];
        $fileName14 = mysqli_real_escape_string($conn, $_FILES['fileUpload14']['name']);
        $fileContent14 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName14));
        $mimeType14 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName14));
    }

     // Initialize second file variables
     $fileContent15 = $mimeType15 = $fileName15 = '';
     if (isset($_FILES['fileUpload15']) && $_FILES['fileUpload15']['error'] === UPLOAD_ERR_OK) {
         $fileTmpName15 = $_FILES['fileUpload15']['tmp_name'];
         $fileName15 = mysqli_real_escape_string($conn, $_FILES['fileUpload15']['name']);
         $fileContent15 = mysqli_real_escape_string($conn, file_get_contents($fileTmpName15));
         $mimeType15 = mysqli_real_escape_string($conn, mime_content_type($fileTmpName15));
     }

    $kpxCode = htmlspecialchars(trim($_POST['kpxCode']));
    $branchId = htmlspecialchars(trim($_POST['branchId']));

    // Set a default value if kpxCode is empty
    if (empty($kpxCode)) {
        $kpxCode = 0; // or another default value suitable for your use case
    }

    // Get the latest series number for the branch
    $latestSeriesQuery = "SELECT MAX(series) AS latest_series
                          FROM create_contract
                          WHERE branch_id = '$branchId'";
    $latestSeriesResult = mysqli_query($conn, $latestSeriesQuery);

    // Generate contract number if necessary
    if ($latestSeriesResult && mysqli_num_rows($latestSeriesResult) > 0) {
        $latestSeriesRow = mysqli_fetch_assoc($latestSeriesResult);
        $latestSeries = (int) $latestSeriesRow['latest_series'] + 1;
    } else {
        $latestSeries = 1;
    }
    $contractNumber = "COL-" . $branchId . "-" . $latestSeries;

    $format_start_date= date('Y-m-d H:i:s', strtotime(date('Y-m-d', strtotime($contract_end)) . ' 00:00:00'));
    $format_end_date= date('Y-m-d H:i:s', strtotime(date('Y-m-d', strtotime($contract_end)) . ' 23:59:59'));

    // Check if the contract already exists
    $existingContractQuery = "SELECT *
                               FROM create_contract
                               WHERE branch_id = '$branchId' 
                               AND request_status != 'Terminated'
                               AND (
                                    (start_date <= '$format_end_date' AND end_date >= '$format_start_date') OR
                                    (start_date >= '$contract_start' AND end_date <= '$contract_end')
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
                    cancelButtonText: "Close",
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
        $query = "INSERT INTO create_contract (kpx_code, branch_id, contract_number, series, mainzone, zone, branch_code, branch, region, area, corporate_lessor,
        l1_firstname, l1_middlename, l1_lastname, l1_gender, l2_firstname, l2_middlename, l2_lastname, l2_gender, l3_firstname, l3_middlename, l3_lastname, l3_gender,
        l4_firstname, l4_middlename, l4_lastname, l4_gender, l5_firstname, l5_middlename, l5_lastname, l5_gender, lessor_type, corporate_name, rdo, contract_start, contract_end, payment_due_date, amount, vat_type, inputted_amount, net_of_vat, vat_amount, wtax, total_month_rental, amount_lessor,
        edit_amount_lessor, bank_name, bank_accNumber, wallet_number, authorize_firstname, authorize_middlename, authorize_lastname, authorize_gender,
        authorize_mobileNumber, notarized, contract_file, mimeType, contractFilename, contract_file2, mimeType2, contractFilename2, contract_file3, mimeType3,
        contractFilename3, contract_file4, mimeType4, contractFilename4, contract_file5, mimeType5, contractFilename5, attachment_6, mimeType6,
        attachment_6_filename, attachment_7, mimeType7, attachment_7_filename, attachment_8, mimeType8, attachment_8_filename, attachment_9, mimeType9, attachment_9_filename,
        attachment_10, mimeType10, attachment_10_filename, attachment_11, mimeType11, attachment_11_filename, attachment_12, mimeType12, attachment_12_filename, attachment_13, mimeType13, attachment_13_filename, attachment_14, mimeType14, attachment_14_filename, attachment_15, mimeType15, attachment_15_filename, status, request_status,
        created_by, mobile_number_l1, mobile_number_l2, mobile_number_l3, mobile_number_l4, mobile_number_l5, created_date)
        VALUES ('$kpxCode', '$branchId', '$contractNumber', '$latestSeries', '$mainzone', '$zone', '$branch_code', '$branch', '$region', '$area', '$lessor_corporateName',
        '$l1_firstname', '$l1_middlename', '$l1_lastname', '$l1_gender', '$l2_firstname', '$l2_middlename', '$l2_lastname', '$l2_gender', '$l3_firstname', '$l3_middlename', '$l3_lastname', '$l3_gender',
        '$l4_firstname', '$l4_middlename', '$l4_lastname', '$l4_gender', '$l5_firstname', '$l5_middlename', '$l5_lastname', '$l5_gender',
        '$lessor_type', '$corporateName', '$rdo', '$contract_start', '$contract_end', '$paymentDueDate', '$amount', '$vat_type','$inputted_amount', '$netOfVat', '$vat_amount', '$wtax',
        '$gross_amount', '$amount_lessor', '$edit_amount_lessor', '$bank_name', '$bank_acc_number', '$wallet_number', '$authorize_firstname', '$authorize_middlename',
        '$authorize_lastname', '$authorize_gender', '$authorize_mobileNumber', '$notarized', '$fileContent', '$mimeType', '$fileName', '$fileContent2', '$mimeType2',
        '$fileName2', '$fileContent3', '$mimeType3', '$fileName3', '$fileContent4', '$mimeType4', '$fileName4', '$fileContent5', '$mimeType5', '$fileName5',
        '$fileContent6', '$mimeType6', '$fileName6', '$fileContent7', '$mimeType7', '$fileName7', '$fileContent8', '$mimeType8', '$fileName8', 
        '$fileContent9', '$mimeType9', '$fileName9', '$fileContent10', '$mimeType10', '$fileName10', '$fileContent11', '$mimeType11', '$fileName11', '$fileContent12', '$mimeType12', '$fileName12', '$fileContent13', '$mimeType13', '$fileName13', '$fileContent14', '$mimeType14', '$fileName14', '$fileContent15', '$mimeType15', '$fileName15', '$status',
        '$request_status', '$created_by', '$l1_mobileNumber', '$l2_mobileNumber', '$l3_mobileNumber', '$l4_mobileNumber', '$l5_mobileNumber', '$created_date')";

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
            <title>ML Rental -Create Contract</title>
            <link rel="stylesheet" href="../../boxicons/css/boxicons.min.css">
            <link rel="preload" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
            <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap"></noscript>
            <link rel="preload" as="style" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB"><link rel="stylesheet" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB" type="text/css">
            <!-- custom CSS file link  -->
            <link rel="stylesheet" href="../../css/contract_page.css?v=<?php echo time(); ?>">
            <link rel="stylesheet" href="../../css/responsive.css?v=<?php echo time(); ?>">
            <script src="https://kit.fontawesome.com/30b908cc5a.js" crossorigin="anonymous"></script>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
            
        </head>
    <body>

    <nav role="navigation" class="nav">
    <ul class="nav-items">
        <li class="nav-item">
            <a href="user_page.php" class="nav-link"><span>Home</span></a>
        </li> 
         <?php
                  $userName = $_SESSION['user_email'];
                  $rolesQuery = "SELECT roles FROM user_form WHERE email = '$userName'";
                  $rolesStmtResult = mysqli_query($conn, $rolesQuery);

                  if (mysqli_num_rows($rolesStmtResult) > 0) {
                     $row = mysqli_fetch_assoc($rolesStmtResult);
                     
                     if ($row['roles'] == 'Am-Creator') {
                  ?>
            <li class="nav-item dropdown">
                <a href="#" class="nav-link"><span>Lessor Profile</span></a>
                <nav class="submenu">
                    <ul class="submenu-items">
                        <li class="submenu-item"><a href="create_lessor_profile.php" class="submenu-link">Add</a></li>
                        <li class="submenu-item"><a href="edit_lessor_profile.php" class="submenu-link">Edit</a></li>
                    </ul>
                </nav>
            </li> 
            <?php
                }else if ($row['roles'] == 'HO') {
                ?>
                <li class="nav-item">
                    <a href="post_payment.php" class="nav-link"><span>Post Payment</span></a>
                </li>
              <li class="nav-item dropdown">
                  <a href="#" class="nav-link"><span>Lessor Profile</span></a>
                  <nav class="submenu">
                      <ul class="submenu-items">
                          <li class="submenu-item" style="display:none;"><a href="create_lessor_profile.php" class="submenu-link">Add</a></li>
                          <li class="submenu-item"><a href="edit_lessor_profile.php" class="submenu-link">View Lessor Profile</a></li>
                      </ul>
                  </nav>
              </li> 
               <?php
                    }
                }
               ?>
        <li class="nav-item dropdown">
            <?php if($row['roles'] == 'BOOKKEEPER'  || $row['roles'] == 'Finance' || $row['roles'] == 'Auditor' || $row['roles'] == 'HO') { ?>
            <a href="#" style="display:none;" class="nav-link"><span>Contract of Lease</span></a>
            <?php }else{ ?>
            <a href="#" class="nav-link"><span>Contract of Lease</span></a>
            <?php } ?>

            <nav class="submenu">
                <ul class="submenu-items">
                 <?php
                  $userName = $_SESSION['user_email'];
                  $rolesQuery = "SELECT roles FROM user_form WHERE email = '$userName'";
                  $rolesStmtResult = mysqli_query($conn, $rolesQuery);

                  if (mysqli_num_rows($rolesStmtResult) > 0) {
                     $row = mysqli_fetch_assoc($rolesStmtResult);
                     if ($row['roles'] == 'Am-Creator') {
                  ?>
                    <li class="submenu-item"><a href="create_contract.php" class="submenu-link">Create Contract</a></li>
                    <li class="submenu-item"><a href="renew_contract.php" class="submenu-link">Renew Contract</a></li>
                    <li class="submenu-item"><hr class="submenu-seperator" /></li>
                    <li class="submenu-item"><a href="created_contract.php" class="submenu-link">Created Contract</a></li>
                    <li class="submenu-item"><hr class="submenu-seperator" /></li>
                    <h5 style="color:#d70c0c;">RFP</h5>
                    <li class="submenu-item"><a href="review_contract.php" class="submenu-link">Review Contract</a></li>
                    <!-- <li class="submenu-item"><a href="vpo_checker.php" class="submenu-link">Receive Contract</a></li> -->
                    <li class="submenu-item"><a href="vpo_reviewer.php" class="submenu-link">Check Contract</a></li>
                    <li class="submenu-item"><a href="audit_contract.php" class="submenu-link">Approve Contract</a></li>
                    <li class="submenu-item"><hr class="submenu-seperator" /></li>
                    <h5 style="color:#d70c0c;">DATA ARCHIVING</h5>
                    <li class="submenu-item"><a href="for_review_col.php" class="submenu-link">For review by RM</a></li>
                    <li class="submenu-item"><a href="reviewed_col.php" class="submenu-link">Reviewed by RM</a></li>
                     <?php
                     }elseif($row['roles'] == 'Rm-Reviewer' || $row['roles'] == 'Vpo-Checker' || $row['roles'] == 'Vpo-Approver' || $row['roles'] == 'Vpo-Reviewer'){
                     ?>
                    <li class="submenu-item"><a href="created_contract.php" class="submenu-link">Created Contract</a></li>
                    <li class="submenu-item"><hr class="submenu-seperator" /></li>
                    <h5 style="color:#d70c0c;">RFP</h5>
                    <li class="submenu-item"><a href="review_contract.php" class="submenu-link">Review Contract</a></li>
                    <!-- <li class="submenu-item"><a href="vpo_checker.php" class="submenu-link">Receive Contract</a></li> -->
                    <li class="submenu-item"><a href="vpo_reviewer.php" class="submenu-link">Check Contract</a></li>
                    <li class="submenu-item"><a href="audit_contract.php" class="submenu-link">Approve Contract</a></li>
                    <li class="submenu-item"><hr class="submenu-seperator" /></li>
                    <h5 style="color:#d70c0c;">DATA ARCHIVING</h5>
                    <li class="submenu-item"><a href="for_review_col.php" class="submenu-link">For review by RM</a></li>
                    <li class="submenu-item"><a href="reviewed_col.php" class="submenu-link">Reviewed by RM</a></li>
                     <?php
                     }elseif($row['roles'] == 'HO'){
                        ?>
                        <h5 style="color:#d70c0c;">RFP</h5>
                        <li class="submenu-item"><a href="created_contract.php" class="submenu-link">Created Contract</a></li>
                        <li class="submenu-item"><a href="review_contract.php" class="submenu-link">Review Contract</a></li>
                        <!-- <li class="submenu-item"><a href="vpo_checker.php" class="submenu-link">Receive Contract</a></li> -->
                        <li class="submenu-item"><a href="vpo_reviewer.php" class="submenu-link">Check Contract</a></li>
                        <li class="submenu-item"><a href="audit_contract.php" class="submenu-link">Approve Contract</a></li>
                        <li class="submenu-item"><hr class="submenu-seperator" /></li>
                        <h5 style="color:#d70c0c;">DATA ARCHIVING</h5>
                        <li class="submenu-item"><a href="for_review_col.php" class="submenu-link">For review by RM</a></li>
                        <li class="submenu-item"><a href="reviewed_col.php" class="submenu-link">Reviewed by RM</a></li>
                        <?php
                    }
                  }
                  
               ?>   
                </ul>
            </nav>
        </li> 
        <?php if($row['roles'] == 'Am-Creator') { ?>
        <li class="nav-item">
            <a href="rfp_page.php" class="nav-link"><span>Request For Payment</span></a>
        </li> 
        <?php } ?>
        <li class="nav-item dropdown">
            <?php if($row['roles'] == 'Finance') { ?>
                <a style="display:none;" href="#" class="nav-link"><span>Reports</span></a>
            <?php }else{ ?>
                <a href="#" class="nav-link"><span>Reports</span></a>
            <?php } ?>

            <nav class="submenu">
                <ul class="submenu-items">
                    <li class="submenu-item"><a href="lease_contract.php" class="submenu-link">Contract of Lease</a></li>
                    <li class="submenu-item"><a href="contract_ledger.php" class="submenu-link">COL - Payment Ledger</a></li>
                    <?php
                    if($row['roles'] == 'BOOKKEEPER'){ 
                    ?>
                    <li class="submenu-item"><a href="edi_extraction.php" class="submenu-link">EDI Extraction</a></li>
                    <?php } ?>
                    <?php
                    if($row['roles'] == 'HO' || $row['roles'] == 'Vpo-Checker'){ 
                    ?>
                    <li class="submenu-item"><a href="ho_page.php" class="submenu-link">Head Office</a></li>
                    <?php } ?>
                    <?php
                    if($row['roles'] == 'HO'){ 
                    ?>
                    <li class="submenu-item"><a href="corporate_report.php" class="submenu-link">By Corporate</a></li>
                    <li class="submenu-item"><a href="payout_report.php" class="submenu-link">Payout Report</a></li>
                    <li class="submenu-item"><a href="sendout_report.php" class="submenu-link">Sendout Report</a></li>
                    <?php } ?>
                    <li class="submenu-item"><a href="view_contracts.php" class="submenu-link">View Contract</a></li>
                </ul>
            </nav>
        </li> 
        <li class="nav-item dropdown">
            <?php
                if($row['roles'] == 'Vpo-Approver' || $row['roles'] == 'Vpo-Reviewer' || $row['roles'] == 'Am-Creator' || $row['roles'] == 'Rm-Reviewer' || $row['roles'] == 'Auditor' || $row['roles'] == 'HO'){ 
            ?>
            <a style="display:none;" href="#" class="nav-link"><span>Data Extraction</span></a>
            <?php }else{ ?>
            <a href="#" class="nav-link"><span>Data Extraction</span></a>
            <?php } ?>
            <nav class="submenu">
                <ul class="submenu-items">
                    <?php
                       if($row['roles'] == 'Vpo-Checker' || $row['roles'] == 'HO'){ 
                    ?>
                    <li class="submenu-item"><a href="request_data_extraction.php" class="submenu-link">Create Data</a></li>
                    <?php
                    }
                    ?>
                    <?php
                       if($row['roles'] == 'Finance'){ 
                    ?>
                    <li class="submenu-item"><a href="extract_request_finance.php" class="submenu-link">Batch Upload</a></li>
                     <?php } ?>
                </ul>
            </nav>
        </li> 
        <li class="nav-item dropdown">
        <?php
            // Check if the user role is Auditor or HO
            if ($row['roles'] == 'Auditor' || $row['roles'] == 'HO') { 
                ?>
                <a href="#" style="display:none;" class="nav-link"><span>Manage COL</span></a>
                <nav class="submenu">
                    <ul class="submenu-items">
                        <?php if ($row['roles'] != 'Auditor' && $row['roles'] != 'HO') { ?>
                            <li class="submenu-item"><a href="terminate_contract.php" class="submenu-link">Request Terminate</a></li>
                        <?php } ?>
                        <?php if ($row['roles'] == 'Am-Creator' || $row['roles'] == 'Vpo-Checker' || $row['roles'] == 'Vpo-Reviewer') { ?>
                            <li class="submenu-item"><a href="modify_contract.php" class="submenu-link">Request Edit COL</a></li>
                        <?php } ?>
                    </ul>
                </nav>
            <?php
            } else { // For all other roles
                ?>
                <a href="#" class="nav-link"><span>Manage COL</span></a>
                <nav class="submenu">
                    <ul class="submenu-items">
                        <?php if ($row['roles'] != 'Auditor' && $row['roles'] != 'HO') { ?>
                            <li class="submenu-item"><a href="terminate_contract.php" class="submenu-link">Request Terminate</a></li>
                        <?php } ?>
                        <?php if ($row['roles'] == 'Am-Creator' || $row['roles'] == 'Vpo-Checker' || $row['roles'] == 'Vpo-Reviewer') { ?>
                            <li class="submenu-item"><a href="modify_contract.php" class="submenu-link">Request Edit COL</a></li>
                        <?php } ?>
                        <?php if ($row['roles'] == 'Am-Creator') { ?>
                            <li class="submenu-item"><a href="add_notarized_col.php" class="submenu-link">Add Notarized COL</a></li>
                        <?php } ?>
                    </ul>
                </nav>
            <?php 
            }
        ?>
        </li> 
        <li class="nav-item">
            <a href="../../logout.php" class="nav-link" id="logout"><span>Logout</span>
            <i class='bx bx-log-in'></i>
        </a>
        </li> 
       <b style="font-weight:700; color:#333;"><?php echo strtoupper($_SESSION['user_email']); ?></b>
    </ul>
</nav>
<div class="contract-container">
    <div class="contract-header">
        <h3>CREATE NEW CONTRACT</h3>
    </div>
    <form class="contract-form" id="createContractForm"  action="" method="POST" enctype="multipart/form-data">
        <div class="form-page" id="page1">
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="region">Region</label>
                    <span style="color:red;"> *</span>
                </div>
                <select name="region" id="region" class="region_select" onchange="this.form.submit()" required>
                    <option value="" <?php echo (!isset($_POST['region'])) ? 'selected' : ''; ?>></option>
                    <?php
                        $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);
                        $branch_insurance = "SELECT DISTINCT region FROM user_form WHERE region != '' AND email = '$user_email' ORDER BY region ASC";
                        $resultregion = mysqli_query($conn, $branch_insurance);
                        if ($resultregion) {
                            while ($rowregion = mysqli_fetch_assoc($resultregion)) {
                                $selected = (isset($_POST['region']) && $_POST['region'] == $rowregion['region']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($rowregion['region'], ENT_QUOTES, 'UTF-8') . "' $selected>" . $rowregion['region'] . "</option>";
                            }
                        }
                    ?>
                </select>
            </div>
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="area">Area</label>
                    <span style="color:red;"> *</span>
                </div>
                <select name="area" id="area" class="area_select" onchange="this.form.submit()" required>
                    <option value="" <?php echo (!isset($_POST['area'])) ? 'selected' : ''; ?>></option>
                    <?php
                        $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);
                        $branch_insurance = "SELECT DISTINCT area FROM user_form WHERE area != '' AND email = '$user_email' ORDER BY area ASC";
                        $resultarea = mysqli_query($conn, $branch_insurance);
                        if ($resultarea) {
                            while ($rowarea = mysqli_fetch_assoc($resultarea)) {
                                $selected = (isset($_POST['area']) && $_POST['area'] == $rowarea['area']) ? 'selected' : '';
                                echo "<option value='" . $rowarea['area'] . "' $selected>" . $rowarea['area'] ."</option>";
                            }
                        }
                    ?>
                </select>
            </div>
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="branch">Branch</label>
                    <span style="color:red;"> *</span>
                </div>
                <select name="branch" id="branch" class="branch_select" required onchange="updateBranchId(this)">
                    <option value=""></option>
                    <?php
                        $region = $_POST['region'] ?? '';
                        $area = $_POST['area'] ?? '';
                        $branch_insurance = "SELECT DISTINCT zone, mainzone ,branch_name, branch_id, kpx_code, code, corporate_name FROM branch_insurance WHERE branch_name != '' AND region = '$region' AND area = '$area' AND ml_matic_status = 'Active' ORDER BY branch_name ASC";
                        $resultBranch = mysqli_query($conn, $branch_insurance);
                        if ($resultBranch) {
                            while ($rowBranch = mysqli_fetch_assoc($resultBranch)) {
                                $selected = (isset($_POST['branch']) && $_POST['branch'] == $rowBranch['branch_name']) ? 'selected' : '';
                                echo "<option value='" . htmlspecialchars($rowBranch['branch_name'], ENT_QUOTES) . "' data-kpx-code='" . htmlspecialchars($rowBranch['kpx_code'], ENT_QUOTES) . "' data-branch-id='" . htmlspecialchars($rowBranch['branch_id'], ENT_QUOTES) .  "' data-branch-code='" . htmlspecialchars($rowBranch['code'], ENT_QUOTES) . "' data-zone='" . htmlspecialchars($rowBranch['zone'], ENT_QUOTES) . "' data-mainzone='" . htmlspecialchars($rowBranch['mainzone'], ENT_QUOTES) . "' data-corporate='" . htmlspecialchars($rowBranch['corporate_name'], ENT_QUOTES) . "'$selected>" . htmlspecialchars($rowBranch['branch_name'], ENT_QUOTES) . "</option>";
                            }
                        }
                    ?>
                </select>
                <input type="hidden" name="kpxCode" id="kpxCode" value="<?php echo isset($_POST['kpxCode']) ? htmlspecialchars($_POST['kpxCode'], ENT_QUOTES) : ''; ?>">
                <input type="hidden" name="branchId" id="branchId" value="<?php echo isset($_POST['branchId']) ? htmlspecialchars($_POST['branchId'], ENT_QUOTES) : ''; ?>">
                <input type="hidden" name="branch_code" id="branch_code" value="<?php echo isset($_POST['branch_code']) ? htmlspecialchars($_POST['branch_code'], ENT_QUOTES) : ''; ?>">
                <input type="hidden" name="zone" id="zone" value="<?php echo isset($_POST['zone']) ? htmlspecialchars($_POST['zone'], ENT_QUOTES) : ''; ?>">
                <input type="hidden" name="mainzone" id="mainzone" value="<?php echo isset($_POST['mainzone']) ? htmlspecialchars($_POST['mainzone'], ENT_QUOTES) : ''; ?>">
            </div>
            <script>
                function updateBranchId(branchSelect) {
                    const selectedBranch = branchSelect.selectedOptions[0];
                    const kpxCode = selectedBranch.getAttribute('data-kpx-code');
                    const branchId = selectedBranch.getAttribute('data-branch-id');
                    const branchCode = selectedBranch.getAttribute('data-branch-code');
                    const zone = selectedBranch.getAttribute('data-zone');
                    const mainzone = selectedBranch.getAttribute('data-mainzone');
                    const corporate_name = selectedBranch.getAttribute('data-corporate');
                    const branchName = selectedBranch.value;

                    document.getElementById('kpxCode').value = kpxCode;
                    document.getElementById('branchId').value = branchId;
                    document.getElementById('branch_code').value = branchCode;
                    document.getElementById('zone').value = zone;
                    document.getElementById('mainzone').value = mainzone;
                    document.getElementById('corporate_name').value = corporate_name;
                    document.getElementById('esc_branch').value = branchName;
                }
            </script>
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="corporate_name">ML Corporate Name</label>
                    <!-- <span style="color:red;"> *</span> -->
                </div>
                <input type="text" name="corporate_name" id="corporate_name" placeholder="Autofill" readonly>
            </div>
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="rdo">Region District Office (RDO)</label>
                    <!-- <span style="color:red;"> *</span> -->
                </div>
                <input type="text" name="rdo" id="rdo" required min="0" autocomplete="off" max="999" oninput="this.value = this.value.slice(0, 3);">
            </div>
        </div>
        <div class="form-page" id="page2" style="display:none;">
            <?php 
                $sql = "SELECT mainzone, region, area FROM user_form WHERE email = ?";
                $stmt = $conn->prepare($sql); // $mysqli is your MySQLi connection object
                $stmt->bind_param("s", $user_email);
                $stmt->execute();
                $stmt->bind_result($mainzone, $region, $area);
                $stmt->fetch();
                $stmt->close();
            ?>
            <input type="hidden" name="main_zone" id="main_zone" value="<?php echo htmlspecialchars($mainzone); ?>">
            <input type="hidden" name="region" id="region" value="<?php echo htmlspecialchars($region); ?>">
            <input type="hidden" name="area" id="area" value="<?php echo htmlspecialchars($area); ?>">
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="lessor">Lessor Name</label>
                    <span style="color:red;"> *</span>
                </div>
                <select name="lessor_name" id="lessor_name" class="lessor_name_input" required>
                    <option disabled selected>Select a Lessor</option>
                    <?php
                        $user_region = $_POST['region'];
                        $user_area = $_POST['area'];
                        $lessorQuery = "SELECT id, first_name, middle_name, last_name, mobile_number, gender, address, corporate_name, lessor_type 
                                        FROM lessor_profile 
                                        WHERE region = '$user_region' AND area = '$user_area' 
                                        ORDER BY first_name ASC";
                        $resultLessor = mysqli_query($conn, $lessorQuery);
                        if ($resultLessor) {
                            while ($rowLessor = mysqli_fetch_assoc($resultLessor)) {
                                $fullName = $rowLessor['first_name'] . ' ' . $rowLessor['middle_name'] . ' ' . $rowLessor['last_name'];
                                $corporateName = $rowLessor['corporate_name'];
                                $lessorId = $rowLessor['id'];
                                $lessorType = $rowLessor['lessor_type'];

                                if (empty($rowLessor['first_name']) && empty($rowLessor['middle_name']) && empty($rowLessor['last_name']) && !empty($corporateName)) {
                                    $selected = (isset($_POST['lessor_name']) && $_POST['lessor_name'] == $corporateName) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($corporateName) . "' data-id='" . htmlspecialchars($lessorId) . "' data-corporate-name='" . htmlspecialchars($corporateName) . "' data-lessor-type='" . htmlspecialchars($lessorType) . "' $selected>" . htmlspecialchars($corporateName) . ' - (' . htmlspecialchars($rowLessor['address']) . ')' . "</option>";
                                } else {
                                    $selected = (isset($_POST['lessor_name']) && $_POST['lessor_name'] == $fullName) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($fullName) . "' data-id='" . htmlspecialchars($lessorId) . "' data-first-name='" . htmlspecialchars($rowLessor['first_name']) . "' data-middle-name='" . htmlspecialchars($rowLessor['middle_name']) . "' data-last-name='" . htmlspecialchars($rowLessor['last_name']) . "' data-gender='" . htmlspecialchars($rowLessor['gender']) . "' data-mobile-number='" . htmlspecialchars($rowLessor['mobile_number']) . "' data-corporate-name='" . htmlspecialchars($corporateName) . "' data-lessor-type='" . htmlspecialchars($lessorType) . "' $selected>" . htmlspecialchars($fullName) . ' - (' . htmlspecialchars($rowLessor['address']) . ')' . "</option>";
                                }
                            }
                        } else {
                            echo "Error: " . mysqli_error($conn);
                        }
                    ?>
                </select>
                <input type="hidden" name="l1_firstname" id="l1_firstname" value="" required>
                <input type="hidden" name="l1_middlename" id="l1_middlename" value="">
                <input type="hidden" name="l1_lastname" id="l1_lastname" value="">
                <input type="hidden" name="l1_gender" id="l1_gender" value="">
                <input type="hidden" name="l1_mobileNumber" id="l1_mobileNumber" value="" required>
                <input type="hidden" name="corporateName" id="corporateName" value="">
                <input type="hidden" name="lessor_id" id="lessor_id" value="">
                <input type="hidden" name="lessor_type" id="lessor_type" value="">
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const lessorSelect = document.getElementById('lessor_name');
                        const firstNameInput = document.getElementById('l1_firstname');
                        const middleNameInput = document.getElementById('l1_middlename');
                        const lastNameInput = document.getElementById('l1_lastname');
                        const genderInput = document.getElementById('l1_gender');
                        const mobileNumberInput = document.getElementById('l1_mobileNumber');
                        const corporateInput = document.getElementById('corporateName');
                        const lessorIdInput = document.getElementById('lessor_id');
                        const lessorTypeInput = document.getElementById('lessor_type');

                        lessorSelect.addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            const firstName = selectedOption.dataset.firstName || '';
                            const middleName = selectedOption.dataset.middleName || '';
                            const lastName = selectedOption.dataset.lastName || '';
                            const gender = selectedOption.dataset.gender || '';
                            const mobileNumber = selectedOption.dataset.mobileNumber || '';
                            const corporateName = selectedOption.dataset.corporateName || '';
                            const lessorId = selectedOption.dataset.id || '';
                            const lessorType = selectedOption.dataset.lessorType || '';

                            firstNameInput.value = firstName;
                            middleNameInput.value = middleName;
                            lastNameInput.value = lastName;
                            genderInput.value = gender;
                            mobileNumberInput.value = mobileNumber;
                            corporateInput.value = corporateName;
                            lessorIdInput.value = lessorId;
                            lessorTypeInput.value = lessorType;
                        });
                    });
                </script>
            </div>
            <button type="button" name="add_LessorBtn" id="add_LessorBtn">Add Lessor Names</button>
            <div class="contract-field" id="lessors_div">
                <div class="lbl_span">
                    <label for="lessor">2nd Lessor Name</label>
                    <span style="color:lightgray; font-size:14px;">&nbsp;(Optional)</span>
                </div>
                <select name="lessor_name2" id="lessor_name2" class="lessor_name_input2" required>
                    <option disable selected>Select a Lessor</option> 
                    <?php
                        $lessorQuery = "SELECT id, first_name, middle_name, last_name, mobile_number, gender, address, corporate_name, lessor_type 
                                        FROM lessor_profile 
                                        WHERE region = '$user_region' AND area = '$user_area' 
                                        ORDER BY first_name ASC";
                        $resultLessor = mysqli_query($conn, $lessorQuery);
                        if ($resultLessor) {
                            while ($rowLessor = mysqli_fetch_assoc($resultLessor)) {
                                $fullName = $rowLessor['first_name'] . ' ' . $rowLessor['middle_name'] . ' ' . $rowLessor['last_name'];
                                $corporateName = $rowLessor['corporate_name'];
                                $lessorId = $rowLessor['id'];
                                $lessorType = $rowLessor['lessor_type'];
                                if (empty($rowLessor['first_name']) && empty($rowLessor['middle_name']) && empty($rowLessor['last_name']) && !empty($corporateName)) {
                                    $selected = (isset($_POST['lessor_name']) && $_POST['lessor_name'] == $corporateName) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($corporateName) . "' data-id='" . htmlspecialchars($lessorId) . "' data-corporate-name='" . htmlspecialchars($corporateName) . "' data-lessor-type='" . htmlspecialchars($lessorType) . "' $selected>" . htmlspecialchars($corporateName) . ' - (' . htmlspecialchars($rowLessor['address']) . ')' . "</option>";
                                } else {
                                    $selected = (isset($_POST['lessor_name']) && $_POST['lessor_name'] == $fullName) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($fullName) . "' data-id='" . htmlspecialchars($lessorId) . "' data-first-name='" . htmlspecialchars($rowLessor['first_name']) . "' data-middle-name='" . htmlspecialchars($rowLessor['middle_name']) . "' data-last-name='" . htmlspecialchars($rowLessor['last_name']) . "' data-gender='" . htmlspecialchars($rowLessor['gender']) . "' data-mobile-number='" . htmlspecialchars($rowLessor['mobile_number']) . "' data-corporate-name='" . htmlspecialchars($corporateName) . "' data-lessor-type='" . htmlspecialchars($lessorType) . "' $selected>" . htmlspecialchars($fullName) . ' - (' . htmlspecialchars($rowLessor['address']) . ')' . "</option>";
                                }
                            }
                        } else {
                            echo "Error: " . mysqli_error($conn);
                        }
                    ?>
                </select>
                <input type="hidden" name="l2_firstname" id="l2_firstname" value="" >
                <input type="hidden" name="l2_middlename" id="l2_middlename" value="" >
                <input type="hidden" name="l2_lastname" id="l2_lastname" value="" >
                <input type="hidden" name="l2_gender" id="l2_gender" value="" >
                <input type="hidden" name="l2_mobileNumber" id="l2_mobileNumber" value="" >
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const lessorSelect = document.getElementById('lessor_name2');
                        const firstNameInput = document.getElementById('l2_firstname');
                        const middleNameInput = document.getElementById('l2_middlename');
                        const lastNameInput = document.getElementById('l2_lastname');
                        const genderInput = document.getElementById('l2_gender');
                        const mobileNumberInput = document.getElementById('l2_mobileNumber');
                        lessorSelect.addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            const firstName = selectedOption.dataset.firstName || '';
                            const middleName = selectedOption.dataset.middleName || '';
                            const lastName = selectedOption.dataset.lastName || '';
                            const gender = selectedOption.dataset.gender || '';
                            const mobileNumber = selectedOption.dataset.mobileNumber || '';
                            firstNameInput.value = firstName;
                            middleNameInput.value = middleName;
                            lastNameInput.value = lastName;
                            genderInput.value = gender;
                            mobileNumberInput.value = mobileNumber;
                        });
                    });
                </script>
                <div class="lbl_span">
                    <label for="lessor">3rd Lessor Name</label>
                    <span style="color:lightgray; font-size:14px;">&nbsp;(Optional)</span>
                </div>
                <select name="lessor_name3" id="lessor_name3" class="lessor_name_input3" required>
                    <option disable selected>Select a Lessor</option> 
                    <?php
                        $lessorQuery = "SELECT id, first_name, middle_name, last_name, mobile_number, gender, address, corporate_name, lessor_type 
                                        FROM lessor_profile 
                                        WHERE region = '$user_region' AND area = '$user_area' 
                                        ORDER BY first_name ASC";
                        $resultLessor = mysqli_query($conn, $lessorQuery);
                        if ($resultLessor) {
                            while ($rowLessor = mysqli_fetch_assoc($resultLessor)) {
                                $fullName = $rowLessor['first_name'] . ' ' . $rowLessor['middle_name'] . ' ' . $rowLessor['last_name'];
                                $corporateName = $rowLessor['corporate_name'];
                                $lessorId = $rowLessor['id'];
                                $lessorType = $rowLessor['lessor_type'];

                                if (empty($rowLessor['first_name']) && empty($rowLessor['middle_name']) && empty($rowLessor['last_name']) && !empty($corporateName)) {
                                    $selected = (isset($_POST['lessor_name']) && $_POST['lessor_name'] == $corporateName) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($corporateName) . "' data-id='" . htmlspecialchars($lessorId) . "' data-corporate-name='" . htmlspecialchars($corporateName) . "' data-lessor-type='" . htmlspecialchars($lessorType) . "' $selected>" . htmlspecialchars($corporateName) . ' - (' . htmlspecialchars($rowLessor['address']) . ')' . "</option>";
                                } else {
                                    $selected = (isset($_POST['lessor_name']) && $_POST['lessor_name'] == $fullName) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($fullName) . "' data-id='" . htmlspecialchars($lessorId) . "' data-first-name='" . htmlspecialchars($rowLessor['first_name']) . "' data-middle-name='" . htmlspecialchars($rowLessor['middle_name']) . "' data-last-name='" . htmlspecialchars($rowLessor['last_name']) . "' data-gender='" . htmlspecialchars($rowLessor['gender']) . "' data-mobile-number='" . htmlspecialchars($rowLessor['mobile_number']) . "' data-corporate-name='" . htmlspecialchars($corporateName) . "' data-lessor-type='" . htmlspecialchars($lessorType) . "' $selected>" . htmlspecialchars($fullName) . ' - (' . htmlspecialchars($rowLessor['address']) . ')' . "</option>";
                                }
                            }
                        } else {
                            echo "Error: " . mysqli_error($conn);
                        }
                    ?>
                </select>
                <input type="hidden" name="l3_firstname" id="l3_firstname" value="" required>
                <input type="hidden" name="l3_middlename" id="l3_middlename" value="" >
                <input type="hidden" name="l3_lastname" id="l3_lastname" value="" required>
                <input type="hidden" name="l3_gender" id="l3_gender" value="" required>
                <input type="hidden" name="l3_mobileNumber" id="l3_mobileNumber" value="" required>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const lessorSelect = document.getElementById('lessor_name3');
                        const firstNameInput = document.getElementById('l3_firstname');
                        const middleNameInput = document.getElementById('l3_middlename');
                        const lastNameInput = document.getElementById('l3_lastname');
                        const genderInput = document.getElementById('l3_gender');
                        const mobileNumberInput = document.getElementById('l3_mobileNumber');
                        lessorSelect.addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            const firstName = selectedOption.dataset.firstName || '';
                            const middleName = selectedOption.dataset.middleName || '';
                            const lastName = selectedOption.dataset.lastName || '';
                            const gender = selectedOption.dataset.gender || '';
                            const mobileNumber = selectedOption.dataset.mobileNumber || '';
                            firstNameInput.value = firstName;
                            middleNameInput.value = middleName;
                            lastNameInput.value = lastName;
                            genderInput.value = gender;
                            mobileNumberInput.value = mobileNumber;
                        });
                    });
                </script>
                <div class="lbl_span">
                    <label for="lessor">4rth Lessor Name</label>
                    <span style="color:lightgray; font-size:14px;">&nbsp;(Optional)</span>
                </div>
                <select name="lessor_name4" id="lessor_name4" class="lessor_name_input4" required>
                    <option disable selected>Select a Lessor</option> 
                    <?php
                        $lessorQuery = "SELECT id, first_name, middle_name, last_name, mobile_number, gender, address, corporate_name, lessor_type 
                                        FROM lessor_profile 
                                        WHERE region = '$user_region' AND area = '$user_area' 
                                        ORDER BY first_name ASC";
                        $resultLessor = mysqli_query($conn, $lessorQuery);
                        if ($resultLessor) {
                            while ($rowLessor = mysqli_fetch_assoc($resultLessor)) {
                                $fullName = $rowLessor['first_name'] . ' ' . $rowLessor['middle_name'] . ' ' . $rowLessor['last_name'];
                                $corporateName = $rowLessor['corporate_name'];
                                $lessorId = $rowLessor['id'];
                                $lessorType = $rowLessor['lessor_type'];

                                if (empty($rowLessor['first_name']) && empty($rowLessor['middle_name']) && empty($rowLessor['last_name']) && !empty($corporateName)) {
                                    $selected = (isset($_POST['lessor_name']) && $_POST['lessor_name'] == $corporateName) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($corporateName) . "' data-id='" . htmlspecialchars($lessorId) . "' data-corporate-name='" . htmlspecialchars($corporateName) . "' data-lessor-type='" . htmlspecialchars($lessorType) . "' $selected>" . htmlspecialchars($corporateName) . ' - (' . htmlspecialchars($rowLessor['address']) . ')' . "</option>";
                                } else {
                                    $selected = (isset($_POST['lessor_name']) && $_POST['lessor_name'] == $fullName) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($fullName) . "' data-id='" . htmlspecialchars($lessorId) . "' data-first-name='" . htmlspecialchars($rowLessor['first_name']) . "' data-middle-name='" . htmlspecialchars($rowLessor['middle_name']) . "' data-last-name='" . htmlspecialchars($rowLessor['last_name']) . "' data-gender='" . htmlspecialchars($rowLessor['gender']) . "' data-mobile-number='" . htmlspecialchars($rowLessor['mobile_number']) . "' data-corporate-name='" . htmlspecialchars($corporateName) . "' data-lessor-type='" . htmlspecialchars($lessorType) . "' $selected>" . htmlspecialchars($fullName) . ' - (' . htmlspecialchars($rowLessor['address']) . ')' . "</option>";
                                }
                            }
                        } else {
                            echo "Error: " . mysqli_error($conn);
                        }
                    ?>
                </select>
                <input type="hidden" name="l4_firstname" id="l4_firstname" value="" required>
                <input type="hidden" name="l4_middlename" id="l4_middlename" value="" >
                <input type="hidden" name="l4_lastname" id="l4_lastname" value="" required>
                <input type="hidden" name="l4_gender" id="l4_gender" value="" required>
                <input type="hidden" name="l4_mobileNumber" id="l4_mobileNumber" value="" required>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const lessorSelect = document.getElementById('lessor_name4');
                        const firstNameInput = document.getElementById('l4_firstname');
                        const middleNameInput = document.getElementById('l4_middlename');
                        const lastNameInput = document.getElementById('l4_lastname');
                        const genderInput = document.getElementById('l4_gender');
                        const mobileNumberInput = document.getElementById('l4_mobileNumber');
                        lessorSelect.addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            const firstName = selectedOption.dataset.firstName || '';
                            const middleName = selectedOption.dataset.middleName || '';
                            const lastName = selectedOption.dataset.lastName || '';
                            const gender = selectedOption.dataset.gender || '';
                            const mobileNumber = selectedOption.dataset.mobileNumber || '';
                            firstNameInput.value = firstName;
                            middleNameInput.value = middleName;
                            lastNameInput.value = lastName;
                            genderInput.value = gender;
                            mobileNumberInput.value = mobileNumber;
                        });
                    });
                </script>
                <div class="lbl_span">
                    <label for="lessor">5th Lessor Name</label>
                    <span style="color:lightgray; font-size:14px;">&nbsp;(Optional)</span>
                </div>
                <select name="lessor_name5" id="lessor_name5" class="lessor_name_input5" required>
                    <option disable selected>Select a Lessor</option> 
                    <?php
                        $lessorQuery = "SELECT id, first_name, middle_name, last_name, mobile_number, gender, address, corporate_name, lessor_type 
                                        FROM lessor_profile 
                                        WHERE region = '$user_region' AND area = '$user_area' 
                                        ORDER BY first_name ASC";
                        $resultLessor = mysqli_query($conn, $lessorQuery);
                        if ($resultLessor) {
                            while ($rowLessor = mysqli_fetch_assoc($resultLessor)) {
                                $fullName = $rowLessor['first_name'] . ' ' . $rowLessor['middle_name'] . ' ' . $rowLessor['last_name'];
                                $corporateName = $rowLessor['corporate_name'];
                                $lessorId = $rowLessor['id'];
                                $lessorType = $rowLessor['lessor_type'];

                                if (empty($rowLessor['first_name']) && empty($rowLessor['middle_name']) && empty($rowLessor['last_name']) && !empty($corporateName)) {
                                    $selected = (isset($_POST['lessor_name']) && $_POST['lessor_name'] == $corporateName) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($corporateName) . "' data-id='" . htmlspecialchars($lessorId) . "' data-corporate-name='" . htmlspecialchars($corporateName) . "' data-lessor-type='" . htmlspecialchars($lessorType) . "' $selected>" . htmlspecialchars($corporateName) . ' - (' . htmlspecialchars($rowLessor['address']) . ')' . "</option>";
                                } else {
                                    $selected = (isset($_POST['lessor_name']) && $_POST['lessor_name'] == $fullName) ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($fullName) . "' data-id='" . htmlspecialchars($lessorId) . "' data-first-name='" . htmlspecialchars($rowLessor['first_name']) . "' data-middle-name='" . htmlspecialchars($rowLessor['middle_name']) . "' data-last-name='" . htmlspecialchars($rowLessor['last_name']) . "' data-gender='" . htmlspecialchars($rowLessor['gender']) . "' data-mobile-number='" . htmlspecialchars($rowLessor['mobile_number']) . "' data-corporate-name='" . htmlspecialchars($corporateName) . "' data-lessor-type='" . htmlspecialchars($lessorType) . "' $selected>" . htmlspecialchars($fullName) . ' - (' . htmlspecialchars($rowLessor['address']) . ')' . "</option>";
                                }
                            }
                        } else {
                            echo "Error: " . mysqli_error($conn);
                        }
                    ?>
                </select>
                <input type="hidden" name="l5_firstname" id="l5_firstname" value="" required>
                <input type="hidden" name="l5_middlename" id="l5_middlename" value="" >
                <input type="hidden" name="l5_lastname" id="l5_lastname" value="" required>
                <input type="hidden" name="l5_gender" id="l5_gender" value="" required>
                <input type="hidden" name="l5_mobileNumber" id="l5_mobileNumber" value="" required>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const lessorSelect = document.getElementById('lessor_name5');
                        const firstNameInput = document.getElementById('l5_firstname');
                        const middleNameInput = document.getElementById('l5_middlename');
                        const lastNameInput = document.getElementById('l5_lastname');
                        const genderInput = document.getElementById('l5_gender');
                        const mobileNumberInput = document.getElementById('l5_mobileNumber');
                        lessorSelect.addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            const firstName = selectedOption.dataset.firstName || '';
                            const middleName = selectedOption.dataset.middleName || '';
                            const lastName = selectedOption.dataset.lastName || '';
                            const gender = selectedOption.dataset.gender || '';
                            const mobileNumber = selectedOption.dataset.mobileNumber || '';
                            firstNameInput.value = firstName;
                            middleNameInput.value = middleName;
                            lastNameInput.value = lastName;
                            genderInput.value = gender;
                            mobileNumberInput.value = mobileNumber;
                        });
                    });
                    document.addEventListener('DOMContentLoaded', function() {
                        const authButton = document.getElementById('add_LessorBtn');
                        const lessorsDiv = document.getElementById('lessors_div');
                        authButton.addEventListener('click', function() {
                            // Toggle the visibility of lessors_div
                            if (lessorsDiv.style.display === 'none') {
                                lessorsDiv.style.display = 'block';
                            } else {
                                lessorsDiv.style.display = 'none';
                            }
                        });
                    });
                </script>
            </div>
        </div>
        <br>
        <div class="form-page" id="page3" style="display:none;">
            <div class="contract-field">
                <div class="lbl_span">
                    <label for="start">Contract Term</label>
                </div>
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
                    <label for="paymentDueDate">Monthly Due Date</label>
                    <span style="color:red;"> *</span>
                </div>
                <input type="hidden" id="paymentDueDate" name="paymentDueDate" value="<?php echo $paymentDueDate; ?>">
                <input type="text" id="displayDueDate" readonly style="border: none; background: transparent; font-weight: bold;">
                <select id="dueDaySelector">
                    <?php for ($i = 1; $i <= 31; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <!-- <div class="contract-field">
                <div class="lbl_span" id="securityDep_lbl">
                    <label for="securityDep">Security Deposit Amount</label>
                </div>
                <input type="text" id="securityDep" name="securityDep" autocomplete="off" value="" step="0.01">
                <div class="lbl_span" id="depositAmount_lbl">
                    <label for="depositAmount">Deposit Amount</label>
                </div>
                <input type="number" name="depositAmount" id="depositAmount" autocomplete="off" value="" step="0.01" value="">
                <div class="lbl_span" id="advanceRental_months_lbl">
                    <label for="advanceRental_months">Advance Rental (No. of Months)</label>
                </div>
                <input type="number" name="advanceRental_months" id="advanceRental_months" min="0" max="99" placeholder="number of months" oninput="this.value = this.value.slice(0, 2);">
                <div class="lbl_span" id="advanceAmount_lbl">
                    <label for="advanceAmount">Advance Rental Amount </label>
                </div>
                <input type="number" name="advanceAmount" id="advanceAmount" autocomplete="off" value="" step="0.01" value="">
            </div> -->
            <div class="monthly_comp">
                <div class="contract-field">
                    <div class="lbl_span">
                        <label for="amount">Monthly Rental</label>
                        <span style="color:red;"> *</span>
                    </div>
                    <input type="number" id="amount" name="amount" autocomplete="off" placeholder="0.00" required onchange="calculateVat()" step="0.01">
                    <input type="hidden" id="rental_amount" name="rental_amount" autocomplete="off" placeholder="0.00" required onchange="calculateWtax()" step="0.01">
                    <div class="lbl_span">
                        <label for="select_wtax">Vat Type</label><span style="color:red;"> *</span>
                    </div>
                    <select id="select_vat" name="select_vat" autocomplete="off" placeholder="" required onchange="calculateVat()" disabled>
                        <option value="" disabled selected>Select VAT Option</option>
                        <option value="Vatable">Vatable</option>
                        <option value="Non Vatable">Non Vatable</option>
                        <option value="Vat Exempt">Vat Exempt</option>
                    </select>
                    <div class="lbl_span" id="amountComp_lbl" style="display:none;">
                        <label for="amountComp">Amount Inputted</label>
                    </div>
                    <input type="text" style="display:none;" id="amountComp" name="amountComp" autocomplete="off" value="" step="0.01" readonly>
                    <div class="lbl_span" id="netOfVat_lbl" style="display:none;">
                        <label for="net_of_vat">Net of Vat Amount</label>
                    </div>
                    <input type="number" style="display:none;" id="net_of_vat" name="net_of_vat" autocomplete="off" value="" step="0.01" readonly>
                    <div class="lbl_span" id="vat_lbl" style="display:none;">
                        <label for="vat">Vat Amount (12%)</label>
                    </div>
                    <input type="number" style="display:none;" id="vat" name="vat" autocomplete="off" value="" step="0.01" readonly>
                    <div class="lbl_span">
                        <label for="select_wtax" id="wtaxType_lbl" style="display:none;">Wtax Type</label>
                    </div>
                    <select id="select_wtax" name="select_wtax" autocomplete="off" placeholder="" onchange="calculateWtax()" required disabled style="display:none;">
                        <option value="" disabled selected>Select Wtax Option</option>
                        <option value="less_wtax">Less Wtax</option>
                        <option value="net_wtax">Net Wtax</option>
                    </select>
                    <div class="lbl_span">
                        <label for="select_wtax" id="percent_lbl" style="display:none;">Wtax Percentage(%)</label>
                    </div>
                    <select id="select_percent" name="select_percent" autocomplete="off" placeholder="" required onchange="calculateWtax()" disabled style="display:none;">
                        <option value="" disabled selected>Select Percentage</option>
                        <option value="5">5%</option>
                        <option value="4" disabled>4%</option>
                        <option value="3" disabled>3%</option>
                        <option value="2" disabled>2%</option>
                        <option value="1" disabled>1%</option>
                    </select>
                   <div class="lbl_span" id="wtax_lbl" style="display:none;">
                        <label for="w-tax">Withholding Tax Amount</label>
                    </div>
                    <input type="number" style="display:none;" id="w-tax" name="w-tax" autocomplete="off" value="<?php echo $wtax; ?>" step="0.01" readonly>
                    <div class="lbl_span" id="gross_lbl" style="display:none;">
                        <label for="gross_amount">Total Monthly Rental Expenses</label>
                    </div>
                    <input type="number" style="display:none;" id="gross_amount" name="gross_amount" autocomplete="off" value="<?php echo $gross_amount; ?>" step="0.01" readonly>
                    <div class="lbl_span" id="amount_lessor_lbl" style="display:none;">
                        <label for="amount_lessor">Amount to lessor</label>
                    </div>
                    <input type="number" style="display:none;" id="amount_lessor" name="amount_lessor" autocomplete="off" value="" step="0.01" readonly>
                    <input type="number" style="display:none;" id="edit_amount_lessor" name="edit_amount_lessor" autocomplete="off" value="" step="0.01">
                    </div>
                </div>
            </div>
            <div class="form-page" id="page4" style="display:none;">
            <div class="contract-field" style="text-align: center;">
                <button type="button" name="add_esc" id="add_esc" style="background-color: transparent;">
                    <i class='bx bx-table' id="add_esc_icon" style="font-size: 50px; color: #d70C0C; cursor: pointer;"></i>
                </button>
                <label for="add_esc" id="add_esc_lbl">Click to Add Escalation</label>
            </div>
                <br>
                <!-- Modal -->
                <div id="escalationModal" class="escalation-modal" style="display: none;">
                    <div class="modal-content-esc">
                    <h3>Payment Rental Schedule</h3>
                    <br>
                        <table>
                        <thead>
                            <tr>
                            <th>Branch Name</th>
                            <th>Effectivity Date</th>
                            <th>Expiry Date</th>
                            <th>Monthly Due Date</th>
                            <th>Monthly Rental</th>
                            <th>Amount to Lessor (Monthly)</th>
                            <th>Escalation</th>
                            <th>Increase</th>
                            <th>Yearly (1-12)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                            <td><input type="text" name="esc_branch" id="esc_branch" value="<?php echo isset($_POST['branch']) ? htmlspecialchars($_POST['branch'], ENT_QUOTES, 'UTF-8') : ''; ?>" readonly></td>
                            <td><input type="text" name="modalEffectivity" id="modalEffectivity" readonly></td>
                            <td><input type="text" name="modalExpiry" id="modalExpiry" readonly></td>
                            <td><input type="text" name="modalDueDate" id="modalDueDate" readonly></td>
                            <td><input type="text" name="monthlyRental" id="monthlyRental" readonly></td>
                            <td><input type="text" name="amountToLessor" id="amountToLessor" readonly></td>
                            <td>
                                <select name="escalation" id="escalation">
                                    <option value="0">0%</option>
                                    <option value="1">1%</option>
                                    <option value="2">2%</option>
                                    <option value="3">3%</option>
                                    <option value="4">4%</option>
                                    <option value="5">5%</option>
                                    <option value="6">6%</option>
                                    <option value="7">7%</option>
                                    <option value="8">8%</option>
                                    <option value="9">9%</option>
                                    <option value="10">10%</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="increase" id="increase" readonly>
                            </td>
                            <td>
                                <input type="text" name="totalYearlyAmount" id="totalYearlyAmount" readonly>
                            </td>
                            </tr>
                            <tr>
                            <td colspan="8" style="text-align:right;"><strong>Grand Total:</strong></td>
                            <td><strong>360,000.00</strong></td>
                            </tr>
                        </tbody>
                        </table>
                        <div class="close_div">
                            <button class="close" name="esc_save" id="esc_save">Save</button>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="esc_save_count" id="esc_save_count" value="0"> 
            </div>

            <div class="form-page" id="page5" style="display:none;">
                <div class="contract-field">
                    <!-- <div class="lbl_span">
                        <label for="modeOfPayment">Mode of Payment:</label><span style="color:red;"> *</span>
                    </div>
                    <select id="modeOfPayment" name="modeOfPayment" autocomplete="off" placeholder="Select mode of payment" required onchange="toggleWalletInput()">
                        <option value="" disabled selected>Select method of payment</option>
                        <option value="PAYMENT SOLUTION">PAYMENT SOLUTION</option>
                        <option value="PDC" disabled>PDC</option>
                        <option value="RTA" disabled>RTA</option>
                    </select> -->
                    <input type="text" style="display:none;margin:15px 0px;" name="walletNumber" id="walletNumber" autocomplete="off" value="" placeholder="Enter Registered Wallet Number" oninput="limitMobileNumber(this)">
                </div>
                <div class="contract-field" id="rta_div" style="display:none;">
                    <div class="lbl_span" id="bank_lbl">
                        <label for="bankName">Bank name</label>
                    </div>
                    <input type="text" id="bankName" name="bankName" autocomplete="off" value="">
                    <div class="lbl_span" id="bank_lbl">
                        <label for="bank_acc_number">Bank account number</label>
                    </div>
                    <input type="text" id="bank_acc_number" name="bank_acc_number" autocomplete="off" value="">
                </div>
                <br>
                <button type="button" name="auth_btn" id="auth_btn">Add authorize to claim</button>
                <div class="contract-field" id="authorize_div">
                    <div class="lbl_span">
                        <label for="authorize_to_claim">Authorize to claim</label>
                        <span style="color:lightgray; font-size:14px;">&nbsp;(Optional)</span>
                    </div><br>
                    <label for="">First Name:</label>
                    <input type="text" id="authorize_firstname" name="authorize_firstname" autocomplete="off" value="">
                    <label for="">Middle Name:</label>
                    <input type="text" id="authorize_middlename" name="authorize_middlename" autocomplete="off" value="">
                    <label for="">Last Name:</label>
                    <input type="text" id="authorize_lastname" name="authorize_lastname" autocomplete="off" value="">
                    <label for="">Gender:</label>
                    <select id="authorize_gender" name="authorize_gender" autocomplete="off" placeholder="">
                        <option value=""></option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                    <label for="">Registered Mobile Number (Authorize to claim):</label>
                    <input type="text" name="authorize_mobileNumber" id="authorize_mobileNumber" autocomplete="off" value="" placeholder="Enter Mobile Number" oninput="limitMobileNumber(this)">
                </div>
                <br>
                <div class="contract-field">
                    <label for="notarized">Is the contract notarized or not?</label>
                    <select id="notarized" name="notarized" autocomplete="off" required>
                        <option value="" disabled selected>YES or NO</option>
                        <option value="Yes">YES</option>
                        <option value="No">NO</option>
                    </select>
                </div>
                <div class="contract-field">
                    <div class="lbl_span">
                        <label for="fileUpload">Upload PDF file only:</label>
                        <span style="color:red;"> *</span>
                    </div>
                    <form action="" class="file-upload-form" method="POST" enctype="multipart/form-data">
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
                        </div>
                        </div><br>
                        <button type="button" name="add_attachment" id="add_attachment">Add more attachment</button>
                        <button type="button" name="add_attachment_second" id="add_attachment_second" style="display:none;">Add more attachment</button>
                        <div class="contract-actions">
                        <input type="hidden" name="created_date" id="created_date" value="<?php echo date('Y-m-d'); ?>">
                        <button type="submit" id="save" name="createCn_save" >Create</button>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const addAttachment = document.getElementById('add_attachment');
                                const sixToTen_div = document.getElementById('sixToTen');
                                const addAttachment_second = document.getElementById('add_attachment_second');
                                const elevenTofifteen_div = document.getElementById('elevenTofifteen');
                                addAttachment.addEventListener('click', function() {
                                    // Toggle the visibility of authorize_div
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
                                    // Toggle the visibility of authorize_div
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
        </div>
        <div class="form-navigation" style="text-align:center;">
            <button type="button" id="recreateBtn" style="display:none;">Create new</button>
            <button type="button" id="prevBtn" onclick="prevPage()" style="display:none;">Previous</button>
            <button type="button" id="nextBtn" onclick="nextPage()">Next</button>
        </div>
    </form>
</div>

<script>
document.getElementById('esc_save').addEventListener('click', function () {
    const countInput = document.getElementById('esc_save_count');
    let currentCount = parseInt(countInput.value) || 0;
    currentCount += 1;
    countInput.value = currentCount;

    if (currentCount > 0) {
        // Change the icon to check-circle
        const icon = document.getElementById('add_esc_icon');
        icon.className = 'bx bx-check-circle';
        icon.style.color = 'green';

        // Change the label text to 'Done'
        const label = document.getElementById('add_esc_lbl');
        label.textContent = 'Done (click to review or edit)';

        // Hide the "Previous" button
        const prevBtn = document.getElementById('prevBtn');
        if (prevBtn) {
            prevBtn.style.display = 'none';
        }

        // Show the "Recreate Contract" button
        const recreateBtn = document.getElementById('recreateBtn');
        recreateBtn.style.display = 'inline-block';
    }
});

// Show or hide the "Recreate Contract" button based on esc_save_count
function checkRecreateButton() {
    const countInput = document.getElementById('esc_save_count');
    if (parseInt(countInput.value) > 0) {
        document.getElementById('recreateBtn').style.display = 'inline-block';
    } else {
        document.getElementById('recreateBtn').style.display = 'none';
    }
}

// Refresh the page when the "Recreate Contract" button is clicked
document.getElementById('recreateBtn').addEventListener('click', function () {
    location.reload();  // This will refresh the page
});

// Call the function to check if the "Recreate Contract" button should be displayed
checkRecreateButton();

// Pagination logic
let currentPage = 1;
const totalPages = 5;

function showPage(page) {
    for (let i = 1; i <= totalPages; i++) {
        document.getElementById('page' + i).style.display = (i === page) ? 'block' : 'none';
    }

    const escSaveCount = parseInt(document.getElementById('esc_save_count').value);

    // Always show "Previous" on the last page
    if (page === totalPages) {
        document.getElementById('prevBtn').style.display = 'inline-block';
    } else {
        document.getElementById('prevBtn').style.display = (page > 1 && escSaveCount === 0) ? 'inline-block' : 'none';
    }

    // Show or hide Next button
    document.getElementById('nextBtn').style.display = (page < totalPages) ? 'inline-block' : 'none';

    // Show/hide "Recreate Contract" button
    const recreateBtn = document.getElementById('recreateBtn');
    if (recreateBtn) {
        recreateBtn.style.display = (escSaveCount > 0 && page !== totalPages) ? 'inline-block' : 'none';
    }
}

function nextPage() {
    if (currentPage < totalPages) {
        currentPage++;
        showPage(currentPage);
    }
}

function prevPage() {
    if (currentPage > 1) {
        currentPage--;
        showPage(currentPage);
    }
}

// Initialize the first page view
showPage(currentPage);

document.getElementById('add_esc').addEventListener('click', function () {
    const escModal = document.getElementById('escalationModal');
    escModal.style.display = 'flex';

    const escBranch = document.getElementById('esc_branch').value;
    const effectivityDate = new Date(document.getElementById('startDate').value);
    const finalExpiryDate = new Date(document.getElementById('endDate').value);
    const dueDay = document.getElementById('dueDaySelector').value;
    const dueText = ordinalSuffixOf(dueDay) + " day of the month";
    const lessorMonthly = parseFloat(document.getElementById('amount_lessor').value) || 0;

    const tbody = document.querySelector('#escalationModal tbody');
    tbody.innerHTML = '';

    let year = effectivityDate.getFullYear();
    const finalYear = finalExpiryDate.getFullYear();

    while (true) {
        const start = new Date(`${year}-04-01`);
        const end = new Date(`${year + 1}-03-31`);

        if (start > finalExpiryDate) break;

        const row = document.createElement('tr');
        row.innerHTML = `
            <td><input type="text" value="${escBranch}" readonly></td>
            <td><input type="text" value="${formatMonthYear(start)}" class="start-display" readonly></td>
            <td><input type="month" value="${formatMonthInput(end)}" class="expiry-date" 
                       min="${formatMonthInput(effectivityDate)}" max="${formatMonthInput(finalExpiryDate)}"></td>
            <td><input type="text" value="${dueText}" readonly></td>
            <td><input type="text" class="base-monthly" readonly></td>
            <td><input type="text" class="new-monthly" readonly></td>
            <td>
                <select class="escalation-dropdown">
                    ${[...Array(11).keys()].map(i => `<option value="${i}">${i}%</option>`).join('')}
                </select>
            </td>
            <td><input type="text" class="increase-value" readonly></td>
            <td><input type="text" class="yearly-total" readonly></td>
        `;
        tbody.appendChild(row);
        year++;
    }

    initializeValues(lessorMonthly);
    attachDropdownListeners();
    attachAllExpiryListeners();
    updateAllRows();
});

function attachAllExpiryListeners() {
    const contractMax = new Date(document.getElementById('endDate').value);
    const contractMin = new Date(document.getElementById('startDate').value); // New variable for effectivity date
    const tbody = document.querySelector('#escalationModal tbody');

    document.querySelectorAll('.expiry-date').forEach((input, index) => {
        input.addEventListener('change', () => {
            const rows = Array.from(tbody.querySelectorAll('tr'));

            // Get the newly selected end date
            const newEndDate = new Date(input.value);
            newEndDate.setDate(1); // normalize date

            // Set current row's new end date (value already set by user)
            let prevEnd = new Date(newEndDate);

            // Start updating the rows after the current one
            for (let i = index + 1; i < rows.length; i++) {
                const nextRow = rows[i];

                // Start date is 1 month after the previous end
                const newStart = new Date(prevEnd);
                newStart.setMonth(newStart.getMonth() + 1);
                newStart.setDate(1);

                // End date is 11 months after new start (12-month total)
                const newEnd = new Date(newStart);
                newEnd.setMonth(newEnd.getMonth() + 11);

                // If the new start goes beyond contract, remove remaining rows
                if (newStart > contractMax) {
                    for (let j = rows.length - 1; j >= i; j--) {
                        rows[j].remove();
                    }
                    break;
                }

                // Update current row's start and end display values
                nextRow.querySelector('.start-display').value = formatMonthYear(newStart);
                nextRow.querySelector('.expiry-date').value = formatMonthInput(newEnd);

                prevEnd = newEnd;
            }

            // Append more rows if necessary to fill remaining contract period
            while (true) {
                const newStart = new Date(prevEnd);
                newStart.setMonth(newStart.getMonth() + 1);
                newStart.setDate(1);

                if (newStart > contractMax) break;

                const newEnd = new Date(newStart);
                newEnd.setMonth(newEnd.getMonth() + 11);

                if (newStart > contractMax) break;

                const escBranch = document.getElementById('esc_branch').value;
                const dueDay = document.getElementById('dueDaySelector').value;
                const dueText = ordinalSuffixOf(dueDay) + " day of the month";

                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td><input type="text" value="${escBranch}" readonly></td>
                    <td><input type="text" value="${formatMonthYear(newStart)}" class="start-display" readonly></td>
                    <td><input type="month" value="${formatMonthInput(newEnd)}" class="expiry-date"
                               min="${formatMonthInput(contractMin)}" max="${formatMonthInput(contractMax)}"></td>
                    <td><input type="text" value="${dueText}" readonly></td>
                    <td><input type="text" class="base-monthly" readonly></td>
                    <td><input type="text" class="new-monthly" readonly></td>
                    <td>
                        <select class="escalation-dropdown">
                            ${[...Array(11).keys()].map(i => `<option value="${i}">${i}%</option>`).join('')}
                        </select>
                    </td>
                    <td><input type="text" class="increase-value" readonly></td>
                    <td><input type="text" class="yearly-total" readonly></td>
                `;
                tbody.appendChild(newRow);

                prevEnd = newEnd;
            }

            attachDropdownListeners();
            attachAllExpiryListeners(); // reattach to any newly created inputs
            updateAllRows(); // re-calculate escalation values
        });
    });
}

function initializeValues(lessorMonthly) {
    const rows = document.querySelectorAll('#escalationModal tbody tr');
    if (!rows.length) return;

    rows.forEach((row, index) => {
        const baseInput = row.querySelector('.base-monthly');
        const newInput = row.querySelector('.new-monthly');
        const increaseInput = row.querySelector('.increase-value');
        const yearlyInput = row.querySelector('.yearly-total');

        if (index === 0) {
            baseInput.value = formatNumber(lessorMonthly);
            newInput.value = formatNumber(lessorMonthly);
            increaseInput.value = formatNumber(0);
            yearlyInput.value = formatNumber(lessorMonthly * 12);
        }
    });
}

function updateAllRows() {
    const rows = document.querySelectorAll('#escalationModal tbody tr');
    if (!rows.length) return;

    let currentLessor = parseFloat(document.getElementById('amount_lessor').value) || 0;

    rows.forEach((row, index) => {
        const dropdown = row.querySelector('.escalation-dropdown');
        const baseInput = row.querySelector('.base-monthly');
        const newInput = row.querySelector('.new-monthly');
        const increaseInput = row.querySelector('.increase-value');
        const yearlyInput = row.querySelector('.yearly-total');

        const escalation = parseFloat(dropdown.value) || 0;

        if (index === 0) {
            baseInput.value = formatNumber(currentLessor);
            newInput.value = formatNumber(currentLessor);
            increaseInput.value = formatNumber(0);
            yearlyInput.value = formatNumber(currentLessor * 12);
        } else {
            const increase = currentLessor * (escalation / 100);
            const newAmount = currentLessor + increase;

            baseInput.value = formatNumber(currentLessor);
            newInput.value = formatNumber(newAmount);
            increaseInput.value = formatNumber(increase * 12);
            yearlyInput.value = formatNumber(newAmount * 12);

            currentLessor = newAmount;
        }
    });

    updateGrandTotal();
}

function attachDropdownListeners() {
    document.querySelectorAll('.escalation-dropdown').forEach(dropdown => {
        dropdown.addEventListener('change', updateAllRows);
    });
}

function ordinalSuffixOf(i) {
    const j = i % 10, k = i % 100;
    if (j === 1 && k !== 11) return i + "st";
    if (j === 2 && k !== 12) return i + "nd";
    if (j === 3 && k !== 13) return i + "rd";
    return i + "th";
}

function formatMonthYear(date) {
    const d = new Date(date);
    return d.toLocaleString('default', { month: 'long', year: 'numeric' });
}

function formatMonthInput(date) {
    const d = new Date(date);
    const month = ("0" + (d.getMonth() + 1)).slice(-2);
    return `${d.getFullYear()}-${month}`;
}

function formatNumber(num) {
    return parseFloat(num).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function updateGrandTotal() {
    let total = 0;

    document.querySelectorAll('#escalationModal tbody tr').forEach(row => {
        const yearlyTotal = parseFloat(row.querySelector('.yearly-total')?.value.replace(/,/g, '')) || 0;
        total += yearlyTotal;
    });

    const existingFooter = document.querySelector('#escalationModal tfoot');
    if (existingFooter) existingFooter.remove();

    const tfoot = document.createElement('tfoot');
    tfoot.innerHTML = `
        <tr>
            <td colspan="8" style="text-align:right;"><strong>Grand Totals:</strong></td>
            <td><strong>${formatNumber(total)}</strong></td>
        </tr>
    `;
    document.querySelector('#escalationModal table').appendChild(tfoot);
}

document.querySelector('#escalationModal .close').addEventListener('click', function () {
    document.getElementById('escalationModal').style.display = 'none';
});

        // Initialize page 1 on load
        document.addEventListener('DOMContentLoaded', () => showPage(currentPage));
         document.getElementById('fileUpload').addEventListener('change', function(event) {
            const filePreview = document.getElementById('filePreview');
            filePreview.innerHTML = ''; // Clear previous preview

            const file = event.target.files[0];
            if (file) {
                const fileType = file.type;
                const validFileType = 'application/pdf';

                if (fileType === validFileType) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const arrayBuffer = e.target.result;

                        // Create Blob from the PDF data
                        const blob = new Blob([arrayBuffer], { type: validFileType });
                        const url = URL.createObjectURL(blob);

                        // Create an iframe to display the PDF
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        iframe.style.border = 'none';

                        filePreview.appendChild(iframe);

                        // Optional: Add a message or a link to download if preview fails
                        iframe.onerror = function() {
                            filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                        };
                    };
                    reader.readAsArrayBuffer(file); // Read the file as ArrayBuffer
                } else {
                    // Clear the file input and show an error message
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
            filePreview.innerHTML = ''; // Clear previous preview

            const file = event.target.files[0];
            if (file) {
                const fileType = file.type;
                const validFileType = 'application/pdf';

                if (fileType === validFileType) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const arrayBuffer = e.target.result;

                        // Create Blob from the PDF data
                        const blob = new Blob([arrayBuffer], { type: validFileType });
                        const url = URL.createObjectURL(blob);

                        // Create an iframe to display the PDF
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        iframe.style.border = 'none';

                        filePreview.appendChild(iframe);

                        // Optional: Add a message or a link to download if preview fails
                        iframe.onerror = function() {
                            filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                        };
                    };
                    reader.readAsArrayBuffer(file); // Read the file as ArrayBuffer
                } else {
                    // Clear the file input and show an error message
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
            filePreview.innerHTML = ''; // Clear previous preview

            const file = event.target.files[0];
            if (file) {
                const fileType = file.type;
                const validFileType = 'application/pdf';

                if (fileType === validFileType) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const arrayBuffer = e.target.result;

                        // Create Blob from the PDF data
                        const blob = new Blob([arrayBuffer], { type: validFileType });
                        const url = URL.createObjectURL(blob);

                        // Create an iframe to display the PDF
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        iframe.style.border = 'none';

                        filePreview.appendChild(iframe);

                        // Optional: Add a message or a link to download if preview fails
                        iframe.onerror = function() {
                            filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                        };
                    };
                    reader.readAsArrayBuffer(file); // Read the file as ArrayBuffer
                } else {
                    // Clear the file input and show an error message
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
            filePreview.innerHTML = ''; // Clear previous preview

            const file = event.target.files[0];
            if (file) {
                const fileType = file.type;
                const validFileType = 'application/pdf';

                if (fileType === validFileType) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const arrayBuffer = e.target.result;

                        // Create Blob from the PDF data
                        const blob = new Blob([arrayBuffer], { type: validFileType });
                        const url = URL.createObjectURL(blob);

                        // Create an iframe to display the PDF
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        iframe.style.border = 'none';

                        filePreview.appendChild(iframe);

                        // Optional: Add a message or a link to download if preview fails
                        iframe.onerror = function() {
                            filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                        };
                    };
                    reader.readAsArrayBuffer(file); // Read the file as ArrayBuffer
                } else {
                    // Clear the file input and show an error message
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
            filePreview.innerHTML = ''; // Clear previous preview

            const file = event.target.files[0];
            if (file) {
                const fileType = file.type;
                const validFileType = 'application/pdf';

                if (fileType === validFileType) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const arrayBuffer = e.target.result;

                        // Create Blob from the PDF data
                        const blob = new Blob([arrayBuffer], { type: validFileType });
                        const url = URL.createObjectURL(blob);

                        // Create an iframe to display the PDF
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        iframe.style.border = 'none';

                        filePreview.appendChild(iframe);

                        // Optional: Add a message or a link to download if preview fails
                        iframe.onerror = function() {
                            filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                        };
                    };
                    reader.readAsArrayBuffer(file); // Read the file as ArrayBuffer
                } else {
                    // Clear the file input and show an error message
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
            filePreview.innerHTML = ''; // Clear previous preview

            const file = event.target.files[0];
            if (file) {
                const fileType = file.type;
                const validFileType = 'application/pdf';

                if (fileType === validFileType) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const arrayBuffer = e.target.result;

                        // Create Blob from the PDF data
                        const blob = new Blob([arrayBuffer], { type: validFileType });
                        const url = URL.createObjectURL(blob);

                        // Create an iframe to display the PDF
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        iframe.style.border = 'none';

                        filePreview.appendChild(iframe);

                        // Optional: Add a message or a link to download if preview fails
                        iframe.onerror = function() {
                            filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                        };
                    };
                    reader.readAsArrayBuffer(file); // Read the file as ArrayBuffer
                } else {
                    // Clear the file input and show an error message
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
            filePreview.innerHTML = ''; // Clear previous preview

            const file = event.target.files[0];
            if (file) {
                const fileType = file.type;
                const validFileType = 'application/pdf';

                if (fileType === validFileType) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const arrayBuffer = e.target.result;

                        // Create Blob from the PDF data
                        const blob = new Blob([arrayBuffer], { type: validFileType });
                        const url = URL.createObjectURL(blob);

                        // Create an iframe to display the PDF
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        iframe.style.border = 'none';

                        filePreview.appendChild(iframe);

                        // Optional: Add a message or a link to download if preview fails
                        iframe.onerror = function() {
                            filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                        };
                    };
                    reader.readAsArrayBuffer(file); // Read the file as ArrayBuffer
                } else {
                    // Clear the file input and show an error message
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

                        // Create Blob from the PDF data
                        const blob = new Blob([arrayBuffer], { type: validFileType });
                        const url = URL.createObjectURL(blob);

                        // Create an iframe to display the PDF
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        iframe.style.border = 'none';

                        filePreview.appendChild(iframe);

                        // Optional: Add a message or a link to download if preview fails
                        iframe.onerror = function() {
                            filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                        };
                    };
                    reader.readAsArrayBuffer(file); // Read the file as ArrayBuffer
                } else {
                    // Clear the file input and show an error message
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
            filePreview.innerHTML = ''; // Clear previous preview

            const file = event.target.files[0];
            if (file) {
                const fileType = file.type;
                const validFileType = 'application/pdf';

                if (fileType === validFileType) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const arrayBuffer = e.target.result;

                        // Create Blob from the PDF data
                        const blob = new Blob([arrayBuffer], { type: validFileType });
                        const url = URL.createObjectURL(blob);

                        // Create an iframe to display the PDF
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        iframe.style.border = 'none';

                        filePreview.appendChild(iframe);

                        // Optional: Add a message or a link to download if preview fails
                        iframe.onerror = function() {
                            filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                        };
                    };
                    reader.readAsArrayBuffer(file); // Read the file as ArrayBuffer
                } else {
                    // Clear the file input and show an error message
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
            filePreview.innerHTML = ''; // Clear previous preview

            const file = event.target.files[0];
            if (file) {
                const fileType = file.type;
                const validFileType = 'application/pdf';

                if (fileType === validFileType) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const arrayBuffer = e.target.result;

                        // Create Blob from the PDF data
                        const blob = new Blob([arrayBuffer], { type: validFileType });
                        const url = URL.createObjectURL(blob);

                        // Create an iframe to display the PDF
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        iframe.style.border = 'none';

                        filePreview.appendChild(iframe);

                        // Optional: Add a message or a link to download if preview fails
                        iframe.onerror = function() {
                            filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                        };
                    };
                    reader.readAsArrayBuffer(file); // Read the file as ArrayBuffer
                } else {
                    // Clear the file input and show an error message
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

                        // Create Blob from the PDF data
                        const blob = new Blob([arrayBuffer], { type: validFileType });
                        const url = URL.createObjectURL(blob);

                        // Create an iframe to display the PDF
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        iframe.style.border = 'none';

                        filePreview.appendChild(iframe);

                        // Optional: Add a message or a link to download if preview fails
                        iframe.onerror = function() {
                            filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                        };
                    };
                    reader.readAsArrayBuffer(file); // Read the file as ArrayBuffer
                } else {
                    // Clear the file input and show an error message
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
            filePreview.innerHTML = ''; // Clear previous preview

            const file = event.target.files[0];
            if (file) {
                const fileType = file.type;
                const validFileType = 'application/pdf';

                if (fileType === validFileType) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const arrayBuffer = e.target.result;

                        // Create Blob from the PDF data
                        const blob = new Blob([arrayBuffer], { type: validFileType });
                        const url = URL.createObjectURL(blob);

                        // Create an iframe to display the PDF
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        iframe.style.border = 'none';

                        filePreview.appendChild(iframe);

                        // Optional: Add a message or a link to download if preview fails
                        iframe.onerror = function() {
                            filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                        };
                    };
                    reader.readAsArrayBuffer(file); // Read the file as ArrayBuffer
                } else {
                    // Clear the file input and show an error message
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
            filePreview.innerHTML = ''; // Clear previous preview

            const file = event.target.files[0];
            if (file) {
                const fileType = file.type;
                const validFileType = 'application/pdf';

                if (fileType === validFileType) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const arrayBuffer = e.target.result;

                        // Create Blob from the PDF data
                        const blob = new Blob([arrayBuffer], { type: validFileType });
                        const url = URL.createObjectURL(blob);

                        // Create an iframe to display the PDF
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        iframe.style.border = 'none';

                        filePreview.appendChild(iframe);

                        // Optional: Add a message or a link to download if preview fails
                        iframe.onerror = function() {
                            filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                        };
                    };
                    reader.readAsArrayBuffer(file); // Read the file as ArrayBuffer
                } else {
                    // Clear the file input and show an error message
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
            filePreview.innerHTML = ''; // Clear previous preview

            const file = event.target.files[0];
            if (file) {
                const fileType = file.type;
                const validFileType = 'application/pdf';

                if (fileType === validFileType) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const arrayBuffer = e.target.result;

                        // Create Blob from the PDF data
                        const blob = new Blob([arrayBuffer], { type: validFileType });
                        const url = URL.createObjectURL(blob);

                        // Create an iframe to display the PDF
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        iframe.style.border = 'none';

                        filePreview.appendChild(iframe);

                        // Optional: Add a message or a link to download if preview fails
                        iframe.onerror = function() {
                            filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                        };
                    };
                    reader.readAsArrayBuffer(file); // Read the file as ArrayBuffer
                } else {
                    // Clear the file input and show an error message
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
            filePreview.innerHTML = ''; // Clear previous preview

            const file = event.target.files[0];
            if (file) {
                const fileType = file.type;
                const validFileType = 'application/pdf';

                if (fileType === validFileType) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const arrayBuffer = e.target.result;

                        // Create Blob from the PDF data
                        const blob = new Blob([arrayBuffer], { type: validFileType });
                        const url = URL.createObjectURL(blob);

                        // Create an iframe to display the PDF
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        iframe.style.border = 'none';

                        filePreview.appendChild(iframe);

                        // Optional: Add a message or a link to download if preview fails
                        iframe.onerror = function() {
                            filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                        };
                    };
                    reader.readAsArrayBuffer(file); // Read the file as ArrayBuffer
                } else {
                    // Clear the file input and show an error message
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
        const authButton = document.getElementById('auth_btn');
        const authorizeDiv = document.getElementById('authorize_div');

        authButton.addEventListener('click', function() {
            // Toggle the visibility of authorize_div
            if (authorizeDiv.style.display === 'none') {
                authorizeDiv.style.display = 'block';
            } else {
                authorizeDiv.style.display = 'none';
            }
        });
    });


        function limitMobileNumber(input) {
            // Allow backspace and delete keys
            if (event.keyCode === 8 || event.keyCode === 46) {
                return;
            }

            // Replace any non-numeric characters except "+"
            input.value = input.value.replace(/[^\d+]/g, '');

            // Limit length to 13 characters (including "+" and 10 digits)
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
                        if (result.isConfirmed) { // With VAT
                            netOf_Vat = amount / 1.12;
                            vatAmount = amount - netOf_Vat;
                            selectWtax.style.display = 'block';
                            wTaxTypeLbl.style.display = 'block';
                            net_Of_Vat.value = netOf_Vat.toFixed(2);

                            // Display selected choice
                            amount_comp_lbl.style.display = 'block';
                            amount_comp.value = 'With VAT';

                        } else { // Without VAT
                            vatAmount = amount * 0.12;
                            netOf_Vat = amount;

                            selectWtax.style.display = 'block';
                            wTaxTypeLbl.style.display = 'block';
                            net_Of_Vat.value = netOf_Vat.toFixed(2);

                            // Display selected choice
                            amount_comp_lbl.style.display = 'block';
                            amount_comp.value = 'Without VAT';
                        }

                        netOfVat.value = vatAmount.toFixed(2); // VAT amount display

                        netOfVat.style.display = 'block';
                        vatLbl.style.display = 'block';

                        netOfVat_lbl.style.display = 'block';
                        net_Of_Vat.style.display = 'block';

                        amount_comp_lbl.style.display = 'block';
                        amount_comp.style.display = 'block';

                        // Proceed to calculate wtax based on VAT
                        calculateWtax(vatAmount);
                    });
                }else if(selectVat.value === "Non Vatable"){
                        vatAmount = 0;
                        netOf_Vat = amount;

                        selectWtax.style.display = 'block';
                        wTaxTypeLbl.style.display = 'block';
                        net_Of_Vat.value = netOf_Vat.toFixed(2);
                        netOfVat.value = vatAmount.toFixed(2);

                        netOfVat.style.display = 'block';
                        vatLbl.style.display = 'block';

                        netOfVat_lbl.style.display = 'block';
                        net_Of_Vat.style.display = 'block';

                        selectWtax.style.display = 'block';
                        wTaxTypeLbl.style.display = 'block';
                }
                else if(selectVat.value === "Vat Exempt"){
                        vatAmount = 0;
                        netOf_Vat = amount;

                        selectWtax.style.display = 'block';
                        wTaxTypeLbl.style.display = 'block';
                        net_Of_Vat.value = netOf_Vat.toFixed(2);
                        netOfVat.value = vatAmount.toFixed(2);

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
            var rental_amount = document.getElementById('rental_amount');
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
                    wtax = net_Of_Vat * 0.05; // Calculate wtax based on amount minus VAT
                    netOfWtax.value = wtax.toFixed(2);

                    rentAmount = amount;

                    netOfWtax.style.display = 'block';
                    wTaxLbl.style.display = 'block';
                    if(amount_comp.value === 'With VAT'){
                        var totalExp = net_Of_Vat + netOfVat + wtax;
                    }else{
                        var totalExp = net_Of_Vat + netOfVat + wtax;
                    }
                    grossAmountInput.value = totalExp.toFixed(2);
                    grossAmountInput.style.display = 'none';
                    grossLbl.style.display = 'none';

                    netAmount = amount - wtax;// amount to lessor display

                    amountToLessor.value = netAmount.toFixed(2);
                    editAmountToLessor.value = netAmount.toFixed(2);

                    rental_amount.value = rentAmount.toFixed(2);

                    amountToLessor.style.display = 'none';
                    editAmountToLessor.style.display = 'block';
                    amountToLessorLbl.style.display = 'block';
                    
                }else if(selectWtax.value === "net_wtax" && selectPercent.value === "5") {

                    wtax = net_Of_Vat * 0.05; // Calculate wtax based on amount minus VAT
                    netOfWtax.value = wtax.toFixed(2);

                    netOfWtax.style.display = 'block';
                    wTaxLbl.style.display = 'block';

                    if(amount_comp.value === 'With VAT'){
                        var totalExp = amount + wtax;
                    }else{
                        var totalExp = net_Of_Vat + netOfVat + wtax;
                    }
                    grossAmountInput.value = totalExp.toFixed(2);

                    grossAmountInput.style.display = 'none';
                    grossLbl.style.display = 'none';

                    rentAmount = amount + wtax;
                    rental_amount.value = rentAmount.toFixed(2);

                    if(amount_comp.value === 'Without VAT'){
                        netAmount = net_Of_Vat + netOfVat;
                        amountToLessor.value = netAmount.toFixed(2);
                        editAmountToLessor.value = netAmount.toFixed(2);
                    }else{
                        netAmount = amount;// amount to lessor display
                        amountToLessor.value = netAmount.toFixed(2);
                        editAmountToLessor.value = netAmount.toFixed(2);
                    }                    

                    amountToLessor.style.display = 'none';
                    editAmountToLessor.style.display = 'block';
                    amountToLessorLbl.style.display = 'block';
                }
                if(selectVat.value === "Non Vatable" && selectWtax.value === "less_wtax" && selectPercent.value === "5"){
                    rentAmount = amount;
                    wtax = amount * 0.05; // Calculate wtax based on amount minus VAT
                    netOfWtax.value = wtax.toFixed(2);

                    netOfWtax.style.display = 'block';
                    wTaxLbl.style.display = 'block';

                    netAmount = amount - wtax; // Net amount is gross amount minus withholding tax
                    var totalExp = netAmount + wtax;
                    grossAmountInput.value = totalExp.toFixed(2);
                    amountToLessor.value = netAmount.toFixed(2);
                    editAmountToLessor.value = netAmount.toFixed(2);

                    rental_amount.value = rentAmount.toFixed(2);
                }else if(selectVat.value === "Non Vatable" && selectWtax.value === "net_wtax" && selectPercent.value === "5"){

                    wtax_amount = amount / 0.95; // Calculate wtax based on amount minus VAT
                    wtax = wtax_amount * 0.05;
                    netOfWtax.value = wtax.toFixed(2);

                    netOfWtax.style.display = 'block';
                    wTaxLbl.style.display = 'block';

                    netAmount = amount;
                    var totalExp = net_Of_Vat + netOfVat + wtax;
                    rentAmount = amount + wtax;
                    grossAmountInput.value = totalExp.toFixed(2);
                    amountToLessor.value = netAmount.toFixed(2);
                    editAmountToLessor.value = netAmount.toFixed(2);
                    rental_amount.value = rentAmount.toFixed(2);
                }
                if(selectVat.value === "Vat Exempt" && selectWtax.value === "less_wtax" && selectPercent.value === "5"){
                    rentAmount = amount;
                    wtax = amount * 0.05; // Calculate wtax based on amount minus VAT
                    netOfWtax.value = wtax.toFixed(2);

                    netOfWtax.style.display = 'block';
                    wTaxLbl.style.display = 'block';

                    netAmount = amount - wtax; // Net amount is gross amount minus withholding tax
                    var totalExp = netAmount + wtax;
                    grossAmountInput.value = totalExp.toFixed(2);
                    amountToLessor.value = netAmount.toFixed(2);
                    editAmountToLessor.value = netAmount.toFixed(2);

                    rental_amount.value = rentAmount.toFixed(2);

                }else if(selectVat.value === "Vat Exempt" && selectWtax.value === "net_wtax" && selectPercent.value === "5"){

                    wtax_amount = amount / 0.95; // Calculate wtax based on amount minus VAT
                    wtax = wtax_amount * 0.05;
                    netOfWtax.value = wtax.toFixed(2);

                    netOfWtax.style.display = 'block';
                    wTaxLbl.style.display = 'block';

                    netAmount = amount;
                    var totalExp = netAmount + wtax;

                    rentAmount = amount + wtax;
                    rental_amount.value = rentAmount.toFixed(2);

                    grossAmountInput.value = totalExp.toFixed(2);
                    amountToLessor.value = netAmount.toFixed(2);
                    editAmountToLessor.value = netAmount.toFixed(2);
                }
            }
        }


        function toggleWalletInput() {
        var modeOfPayment = document.getElementById('modeOfPayment');
        var walletNumberInput = document.getElementById('walletNumber');
        var rta_div = document.getElementById('rta_div');

        if (modeOfPayment.value === 'WALLET') {
            walletNumberInput.style.display = 'block'; // Display walletNumber input
        } else {
            walletNumberInput.style.display = 'none'; // Hide walletNumber input
            walletNumberInput.value = ''; // Clear walletNumber value
        }

        if(modeOfPayment.value === 'RTA'){
            rta_div.style.display = 'block';
        }else{
            rta_div.style.display = 'none';
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    const paymentDueDateInput = document.getElementById('paymentDueDate'); // Hidden field for actual date storage
    const displayDueDate = document.getElementById('displayDueDate'); // Displayed formatted text
    const dueDaySelector = document.getElementById('dueDaySelector');

    function syncDates() {
        const startDateValue = new Date(startDateInput.value);
        const day = startDateValue.getDate(); // Default to effectivity date day
        dueDaySelector.value = day;
        updateDueDateText(day);
        updateHiddenDate(day);
    }

    function updateDueDateText(day) {
        const suffix = getDaySuffix(day);
        displayDueDate.value = `Every ${day}${suffix} of the month`;
    }

    function updateHiddenDate(day) {
        const currentDate = new Date(); // Use current month/year to construct a valid date
        const year = currentDate.getFullYear();
        const month = (currentDate.getMonth() + 1).toString().padStart(2, '0');
        const formattedDay = day.toString().padStart(2, '0');
        paymentDueDateInput.value = `${year}-${month}-${formattedDay}`; // Store in YYYY-MM-DD
    }

    function getDaySuffix(day) {
        if (day >= 11 && day <= 13) return "th";
        switch (day % 10) {
            case 1: return "st";
            case 2: return "nd";
            case 3: return "rd";
            default: return "th";
        }
    }

    // Sync start date on change
    startDateInput.addEventListener('change', syncDates);

    // Allow user to change the due day dynamically
    dueDaySelector.addEventListener('change', function () {
        let day = parseInt(this.value);
        updateDueDateText(day);
        updateHiddenDate(day);
    });

    // Initialize the fields on page load
    syncDates();
});

   
document.addEventListener('DOMContentLoaded', function () {
    var dropdownLinks = document.querySelectorAll('.dropdown .nav-link');
    dropdownLinks.forEach(function (el) {
        el.addEventListener('click', onClick, false);
    });

    function onClick(e) {
        e.preventDefault();
        var el = this.parentNode;
        el.classList.contains('show-submenu') ? hideSubMenu(el) : showSubMenu(el);
    }

    function showSubMenu(el) {
        el.classList.add('show-submenu') ;
        document.addEventListener('click', onDocClick);

        function onDocClick(e) {
            if (el.contains(e.target)) {
                return;
            }
            document.removeEventListener('click', onDocClick);
            hideSubMenu(el);
        }
    }

    function hideSubMenu(el) {
        el.classList.remove('show-submenu');
    }
});
        </script>
    </body>
</html>


