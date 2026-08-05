<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}

$messageType = null;
$messageText = null;
$alertScript = "";

// Get contract ID
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Fetch current contract
$sql = "SELECT * FROM create_contract WHERE id = $id LIMIT 1";
$result = mysqli_query($conn, $sql);
$contract = mysqli_fetch_assoc($result);

if (!$contract) {
    die("Contract not found.");
}

// Proceed only if form is submitted and update button is clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_btn'])) {
    
    $contractNumber = '';
    $contractQuery = mysqli_query($conn, "SELECT contract_number FROM create_contract WHERE id = $id");
    if ($row = mysqli_fetch_assoc($contractQuery)) {
        $contractNumber = $row['contract_number'];
    }

    $effectiveDate = $_POST['contract_start'] ?? null;
    $expiryDate    = $_POST['contract_end'] ?? null;
    $isDateUpdate  = !empty($effectiveDate) || !empty($expiryDate);

    // Fields
    $fields = [
        'lessor_type','l1_firstname','l1_middlename','l1_lastname',
        'l2_firstname','l2_middlename','l2_lastname',
        'authorize_firstname','authorize_middlename','authorize_lastname',
        'contract_number','zone','branch_id','branch','region','area',
        'l1_gender','l2_gender','corporate_name',
        'contract_start','contract_end',
        'payment_due_date','notarized','status','created_by',
        'authorize_gender','authorize_mobileNumber',
        'mobile_number_l1','mobile_number_l2','created_date',
        'amount','vat_type','net_of_vat','vat_amount','wtax',
        'amount_lessor','advanceRental_amount','advanceRental_from',
        'advanceRental_to','securityDeposit_amount','security_type',
        'consumable_from','consumable_to'
    ];

    $numericFields = ['branch_id','amount','net_of_vat','vat_amount','wtax',
                      'amount_lessor','advanceRental_amount','securityDeposit_amount'];

    $dateFields = ['contract_start','contract_end','payment_due_date',
                   'advanceRental_from','advanceRental_to','consumable_from','consumable_to'];

    $preserveFieldsOnDateUpdate = ['amount','vat_type','net_of_vat','vat_amount','wtax',
                                   'amount_lessor','advanceRental_amount','securityDeposit_amount'];

    // Security type logic
    if (isset($_POST['security_type']) && strtolower($_POST['security_type']) === 'refundable') {
        $_POST['consumable_from'] = null;
        $_POST['consumable_to']   = null;
    }

    // Payment due date update (day only)
    if (isset($_POST['payment_due_date_day'], $_POST['payment_due_date_original'])) {
        $newDay   = (int) $_POST['payment_due_date_day'];
        $original = $_POST['payment_due_date_original'];

        if ($newDay >= 1 && $newDay <= 31 && !empty($original)) {
            $monthYear = date('Y-m', strtotime($original));
            $_POST['payment_due_date'] = $monthYear . '-' . str_pad($newDay, 2, '0', STR_PAD_LEFT);

            if (!empty($contractNumber)) {
                $sqlEsc = "
                    UPDATE escalation
                    SET monthly_due_date = STR_TO_DATE(
                        CONCAT(YEAR(monthly_due_date), '-', LPAD(MONTH(monthly_due_date),2,'0'), '-', $newDay),
                        '%Y-%m-%d'
                    )
                    WHERE col_number = '" . mysqli_real_escape_string($conn, $contractNumber) . "'
                ";
                mysqli_query($conn, $sqlEsc);
            }
        }
    }

    // Update escalation dates only
    if (!empty($contractNumber) && $isDateUpdate) {
        mysqli_query($conn, "
            UPDATE escalation
            SET effectivity_date = '" . mysqli_real_escape_string($conn, $effectiveDate) . "',
                expiry_date     = '" . mysqli_real_escape_string($conn, $expiryDate) . "'
            WHERE col_number = '" . mysqli_real_escape_string($conn, $contractNumber) . "'
        ");
    }

    // Build update query
    $updateFields = [];
    foreach ($fields as $field) {
        if (!array_key_exists($field, $_POST)) continue;
        $rawValue = $_POST[$field];

        if ($isDateUpdate && in_array($field, $preserveFieldsOnDateUpdate, true) && ($rawValue === '' || $rawValue === null)) {
            continue; // preserve finance fields
        }

        if (in_array($field, $numericFields, true)) {
            $updateFields[] = ($rawValue === '' || $rawValue === null)
                ? "$field = NULL"
                : "$field = '" . number_format((float)$rawValue, 2, '.', '') . "'";
        } elseif (in_array($field, $dateFields, true)) {
            $escaped = mysqli_real_escape_string($conn, trim((string)$rawValue));
            $updateFields[] = $escaped === '' ? "$field = NULL" : "$field = '$escaped'";
        } else {
            $escaped = mysqli_real_escape_string($conn, (string)$rawValue);
            $updateFields[] = "$field = '$escaped'";
        }
    }

    // File upload handler
    function handleFileUpload($input, $column, &$updateFields, $conn) {
        if (!isset($_FILES[$input]) || $_FILES[$input]['error'] !== 0) return;
        $filename = mysqli_real_escape_string($conn, $_FILES[$input]['name']);
        $mime     = mysqli_real_escape_string($conn, $_FILES[$input]['type']);
        $data     = mysqli_real_escape_string($conn, file_get_contents($_FILES[$input]['tmp_name']));
        $updateFields[] = "$column = '$data'";
        preg_match('/(\d+)/', $column, $m);
        $i = $m[1] ?? '';
        $updateFields[] = "contractFilename$i = '$filename'";
        $updateFields[] = "mimeType$i = '$mime'";
    }

    handleFileUpload('contract_file',  'contract_file',  $updateFields, $conn);
    handleFileUpload('contract_file2', 'contract_file2', $updateFields, $conn);
    handleFileUpload('contract_file3', 'contract_file3', $updateFields, $conn);
    handleFileUpload('contract_file4', 'contract_file4', $updateFields, $conn);
    handleFileUpload('contract_file5', 'contract_file5', $updateFields, $conn);
    for ($i=6; $i<=15; $i++) {
        handleFileUpload("attachment_$i", "contract_file$i", $updateFields, $conn);
    }

    $sql = "UPDATE create_contract SET " . implode(", ", $updateFields) . " WHERE id = $id";

    if (mysqli_query($conn, $sql)) {
        $alertScript = "
            Swal.fire({
                icon: 'success',
                title: 'Contract Updated',
                html: `
                    <div class='fs-6 text-muted'>
                        All contract details were saved successfully.
                    </div>
                `,
                background: '#ffffff',
                color: '#212529',
                confirmButtonText: 'Back to Contracts',
                confirmButtonColor: '#198754',
                customClass: {
                    popup: 'rounded-4 shadow-lg px-4 py-4'
                }
            }).then(() => {
                window.location.href = 'user_page.php?update=success';
            });
        ";
    } else {
        $error = htmlspecialchars(mysqli_error($conn), ENT_QUOTES);
        $alertScript = "
            Swal.fire({
                icon: 'error',
                title: 'Update Failed',
                html: `
                    <div class='fs-6 text-muted'>
                        An error occurred while saving the contract.
                        <br><code>{$error}</code>
                    </div>
                `,
                background: '#fff',
                color: '#333',
                confirmButtonText: 'Close',
                confirmButtonColor: '#dc3545',
                customClass: {
                    popup: 'rounded-4 shadow px-4 py-4'
                }
            });
        ";
    }
    
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Contract</title>
    <!-- External styles -->
    <link href="../../assets/css/poppins.css" rel="stylesheet">
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
</head>
<style>
    .form-label {
        font-weight: 500;
        color: #444;
    }

    .card-header h5 {
        font-weight: 600;
    }

    .btn i {
        vertical-align: middle;
    }
</style>

<body>
<?php include('navbar.php'); ?>

<div id="mainContent">
<button id="toggleSidebar" class="toggle-btn mb-4">
    <i class="bi bi-list"></i> <span class="ms-1 text-muted">Menu</span>
  </button>
    <div class="container my-4">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">
                <h4 class="mb-0 text-white"><i class="bi bi-pencil-square me-2"></i> Edit Contract</h4>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($contract['id']) ?>">

                    <!-- Lessor Information -->
                    <div class="card mb-4">
                        <div class="card-header bg-light border-bottom">
                            <h5 class="mb-0 text-secondary"><i class="bi bi-person-vcard me-2"></i> Lessor Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Lessor Type</label>
                                    <input type="text" name="lessor_type" class="form-control" value="<?= htmlspecialchars($contract['lessor_type'] ?? '') ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lessor Representatives -->
                    <div class="card mb-4">
                        <div class="card-header bg-light border-bottom">
                            <h5 class="mb-0 text-secondary">
                                <i class="bi bi-people me-2"></i> Lessor Representatives
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php
            
                               $lessor_fields = [
                                   ['l1_firstname', 'Lessor 1 First Name', 'text'],
                                   ['l1_middlename', 'Lessor 1 Middle Name', 'text'],
                                   ['l1_lastname', 'Lessor 1 Last Name', 'text'],
                                   ['l1_gender', 'Lessor 1 Gender', 'select'],
                                   ['mobile_number_l1', 'Mobile Number (Lessor 1)', 'mobile'],
                                   ['l2_firstname', 'Lessor 2 First Name', 'text'],
                                   ['l2_middlename', 'Lessor 2 Middle Name', 'text'],
                                   ['l2_lastname', 'Lessor 2 Last Name', 'text'],
                                   ['l2_gender', 'Lessor 2 Gender', 'select'],
                                   ['mobile_number_l2', 'Mobile Number (Lessor 2)', 'mobile'],
                                   ['authorize_firstname', 'Authorized First Name', 'text'],
                                   ['authorize_middlename', 'Authorized Middle Name', 'text'],
                                   ['authorize_lastname', 'Authorized Last Name', 'text'],
                                   ['authorize_gender', 'Authorize Gender', 'select'],
                                   ['authorize_mobileNumber', 'Authorized Mobile Number', 'mobile']
                               ];
                               
                               foreach ($lessor_fields as $field) {
                                   $name = $field[0];
                                   $label = $field[1];
                                   $type = $field[2];
                                   $value = htmlspecialchars($contract[$name] ?? '');
                               
                                   echo '<div class="col-md-4">';
                                   echo '<label for="'.$name.'" class="form-label">'.$label.'</label>';
                               
                                   if ($type === 'select') {
                                       echo '<select name="'.$name.'" id="'.$name.'" class="form-select" autocomplete="off">
                                               <option value="">-- Select --</option>
                                               <option value="Male" '.($value === 'Male' ? 'selected' : '').'>Male</option>
                                               <option value="Female" '.($value === 'Female' ? 'selected' : '').'>Female</option>
                                             </select>';
                                   } elseif ($type === 'mobile') {
                                       echo '<input type="text" name="'.$name.'" id="'.$name.'" class="form-control mobile-input"
                                               value="'.$value.'" inputmode="numeric" pattern="^[0-9]{11}$" maxlength="11"
                                               title="Enter exactly 11 digits (e.g. 09123456789)" autocomplete="off">';
                                   } else {
                                       echo '<input type="text" name="'.$name.'" id="'.$name.'" class="form-control"
                                               value="'.$value.'" autocomplete="off">';
                                   }
                               
                                   echo '</div>';
                               }
                               ?>
                               
                               <script>
                               // Enforce numeric-only & max 11 digits in real-time
                               document.querySelectorAll('.mobile-input').forEach(input => {
                                   input.addEventListener('input', function() {
                                       this.value = this.value.replace(/\D/g, '').slice(0, 11);
                                   });
                               });
                               </script>
                        
                            </div>
                        </div>
                    </div>

                    <!-- Contract Details -->
                    <div class="card mb-4">
                        <div class="card-header bg-light border-bottom">
                            <h5 class="mb-0 text-secondary">
                                <i class="bi bi-file-earmark-text me-2"></i> Contract Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php
                                $details = [
                                    ['contract_number', 'Contract Number'],
                                    ['zone', 'Zone'],
                                    ['branch_id', 'Branch ID'],
                                    ['branch', 'Branch'],
                                    ['region', 'Region'],
                                    ['area', 'Area'],
                                    ['corporate_name', 'Corporate Name'],
                                    ['contract_start', 'Effectivity Date', 'date'],
                                    ['contract_end', 'Expiry Date', 'date'],
                                    ['start_date', 'RFP Start Date'],
                                    ['end_date', 'RFP End Date'],
                                    ['payment_due_date', 'Payment Due Date', 'date'],
                                    ['notarized', 'Notarized'],
                                    ['status', 'Status'],
                                    ['created_by', 'Created By'],
                                    ['created_date', 'Created Date', 'date'],
                                ];

                                $readonlyFields = [
                                    'contract_number',
                                    'zone',
                                    'branch_id',
                                    'branch',
                                    'region',
                                    'area',
                                    'status',
                                    'created_by',
                                    'created_date'
                                ];

                                // Get corporate_name options from branch_insurance
                                $corporateOptions = [];
                                $insuranceQuery = "SELECT DISTINCT corporate_name FROM branch_insurance WHERE corporate_name IS NOT NULL AND corporate_name != '' ORDER BY corporate_name ASC";
                                $insuranceResult = mysqli_query($conn, $insuranceQuery);
                                while ($row = mysqli_fetch_assoc($insuranceResult)) {
                                    $corporateOptions[] = $row['corporate_name'];
                                }

                                foreach ($details as $field) {
                                    $name = $field[0];
                                    $label = $field[1];
                                    $type = $field[2] ?? 'text';
                                    $value = htmlspecialchars($contract[$name] ?? '');
                                    $readonly = in_array($name, $readonlyFields) ? 'readonly' : '';
                                
                                    // Handle special fields
                                    if ($name === 'corporate_name') {
                                        $input = '<select name="corporate_name" class="form-control" autocomplete="off">';
                                        $input .= '<option value="">Select Corporate Name</option>';
                                        foreach ($corporateOptions as $option) {
                                            $selected = ($option === $value) ? 'selected' : '';
                                            $input .= '<option value="' . htmlspecialchars($option) . '" ' . $selected . '>' . htmlspecialchars($option) . '</option>';
                                        }
                                        $input .= '</select>';
                                    } elseif ($name === 'notarized') {
                                        $input = '<select name="notarized" class="form-control" autocomplete="off">';
                                        foreach (['Yes', 'No'] as $option) {
                                            $selected = ($option === $value) ? 'selected' : '';
                                            $input .= '<option value="' . $option . '" ' . $selected . '>' . $option . '</option>';
                                        }
                                        $input .= '</select>';
                                    } elseif ($name === 'authorize_mobileNumber' || $name === 'mobile_number_l1') {
                                        $input = '<input type="text" name="' . $name . '" class="form-control" maxlength="11" pattern="\d{11}" inputmode="numeric" title="Enter exactly 11 digits" value="' . $value . '" ' . $readonly . ' autocomplete="off">';
                                    } elseif ($name === 'payment_due_date') {
                                        // Extract current day
                                        $currentDate = !empty($value) ? strtotime($value) : time();
                                        $currentDay = (int)date('j', $currentDate);
                                
                                        $input = '<select name="payment_due_date_day" class="form-select">';
                                        $input .= '<option value="">Select Day</option>';
                                        for ($i = 1; $i <= 31; $i++) {
                                            // Add suffix for display
                                            if ($i == 1 || $i == 21 || $i == 31) $suffix = 'st';
                                            elseif ($i == 2 || $i == 22) $suffix = 'nd';
                                            elseif ($i == 3 || $i == 23) $suffix = 'rd';
                                            else $suffix = 'th';
                                
                                            $selected = ($i == $currentDay) ? 'selected' : '';
                                            $input .= '<option value="' . $i . '" ' . $selected . '>Every ' . $i . $suffix . ' day of the month</option>';
                                        }
                                        $input .= '</select>';
                                        // Keep original month and year in hidden input
                                        $input .= '<input type="hidden" name="payment_due_date_original" value="' . date('Y-m-d', $currentDate) . '">';
                                    } else {
                                        $input = '<input type="' . $type . '" name="' . $name . '" class="form-control" value="' . $value . '" ' . $readonly . ' autocomplete="off">';
                                    }
                                
                                    echo '
                                    <div class="col-md-6">
                                        <label class="form-label">' . $label . '</label>
                                        ' . $input . '
                                    </div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php if (isset($contract['rfp_status' === 'NULL' || $contract['rfp_status'] === ''])) { ?>
                    <!-- Amount & Computations -->
                    <div class="card mb-4">
                        <div class="card-header bg-light border-bottom">
                            <h5 class="mb-0 text-secondary">
                                <i class="bi bi-cash-coin me-2"></i> Amount & Computations
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                            <?php
                                $amountFields = [
                                    ['amount', 'Contract Amount', 'number', 'step="0.01" min="0"'],
                                    ['vat_type', 'VAT Type'],
                                    ['net_of_vat', 'Net of VAT', 'number', 'step="0.01" min="0"'],
                                    ['vat_amount', 'VAT Amount', 'number', 'step="0.01" min="0"'],
                                    ['wtax', 'WTax', 'number', 'step="0.01" min="0"'],
                                    ['amount_lessor', 'Amount to Lessor', 'number', 'step="0.01" min="0"'],

                                    // Advance Rental Section
                                    ['advanceRental_amount', 'Advance Rental Amount', 'number', 'step="0.01" min="0"'],
                                    ['advanceRental_from', 'Advance Rental From', 'date'],
                                    ['advanceRental_to', 'Advance Rental To', 'date'],

                                    // Security Deposit Section
                                    ['securityDeposit_amount', 'Security Deposit Amount', 'number', 'step="0.01" min="0"'],
                                    ['security_type', 'Security Type'],
                                    ['consumable_from', 'Consumable From', 'date'],
                                    ['consumable_to', 'Consumable To', 'date'],
                                ];

                                foreach ($amountFields as $field) {
                                    $name = $field[0];
                                    $label = $field[1];
                                    $type = $field[2] ?? 'text';
                                    $extra = $field[3] ?? '';

                                    $rawValue = $contract[$name] ?? '';
                                    $value = is_numeric($rawValue) ? number_format((float)$rawValue, 2, '.', '') : htmlspecialchars($rawValue);

                                    // Determine if consumable fields should be hidden initially
                                    $hiddenClass = '';
                                    $requiredAttr = '';
                                    if (in_array($name, ['consumable_from', 'consumable_to'])) {
                                        $securityType = strtolower($contract['security_type'] ?? '');
                                        if ($securityType === 'refundable') {
                                            $hiddenClass = 'd-none';
                                        } elseif ($securityType === 'consumable') {
                                            $requiredAttr = 'required';
                                        }
                                    }

                                    echo '<div class="col-md-6 ' . $hiddenClass . '" id="' . $name . '_field">';
                                    echo '<label class="form-label">' . $label . '</label>';

                                    if ($name === 'vat_type') {
                                        // Make VAT Type dropdown readonly (disabled)
                                        echo '<select name="vat_type" id="vat_type" class="form-select" disabled>';
                                        $options = ['Vatable', 'Non-Vatable', 'VAT Exempt'];
                                        foreach ($options as $option) {
                                            $selected = strtolower($value) === strtolower($option) ? 'selected' : '';
                                            echo '<option value="' . $option . '" ' . $selected . '>' . $option . '</option>';
                                        }
                                        echo '</select>';
                                    } elseif ($name === 'security_type') {
                                        echo '<select name="security_type" id="security_type" class="form-select">';
                                        $securityOptions = ['refundable', 'consumable'];
                                        foreach ($securityOptions as $option) {
                                            $selected = strtolower($value) === strtolower($option) ? 'selected' : '';
                                            echo '<option value="' . $option . '" ' . $selected . '>' . $option . '</option>';
                                        }
                                        echo '</select>';
                                    } else {
                                        // Apply Bootstrap success class with 25% opacity for amount_lessor
                                        $inputClass = 'form-control';
                                        if ($name === 'amount_lessor') {
                                            $inputClass .= ' bg-success bg-opacity-25 text-success border-success text-dark';
                                            $value = number_format((float)$value, 2, '.', ''); // ✅ force 2 decimals
                                        }

                                        // Make all amount & computation fields readonly
                                        if (in_array($name, ['amount', 'net_of_vat', 'vat_amount', 'wtax', 'amount_lessor'])) {
                                            $extra .= ' readonly';
                                        }

                                        echo '<input 
                                                type="' . htmlspecialchars($type) . '" 
                                                name="' . htmlspecialchars($name) . '" 
                                                id="' . htmlspecialchars($name) . '"
                                                value="' . htmlspecialchars($value) . '" 
                                                class="' . $inputClass . '" ' . $extra . ' ' . $requiredAttr . ' 
                                            />';
                                    }

                                    echo '</div>';
                                }
                                ?>

                            </div>
                        </div>
                    </div>
                    <?php } ?>
                    <!-- Contract Files -->
                    <div class="card mb-4">
                        <div class="card-header bg-light border-bottom">
                            <h5 class="mb-0 text-secondary"><i class="bi bi-folder2-open me-2"></i> Contract Files (View & Update)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">

                                <?php
                                // Contract File 1 (special case)
                                $currentFile1 = $contract['contractFilename'] ?? '';
                                $highlightClass = !empty($currentFile1) ? 'border-success bg-success bg-opacity-25' : '';
                                echo '<div class="col-md-6">
                                    <label class="form-label">
                                        Contract File 1' . 
                                        (!empty($currentFile1) 
                                            ? ' (<strong><span class="text-success">Current: ' . htmlspecialchars($currentFile1) . '</span></strong>)' 
                                            : '') . 
                                    '</label>
                                    <input type="file" name="contract_file" class="form-control ' . $highlightClass . '">
                                </div>';
                        
                                // Contract Files 2–5
                                for ($i = 2; $i <= 5; $i++) {
                                    $nameAttr = 'contract_file' . $i;
                                    $filenameKey = 'contractFilename' . $i;
                                    $currentFile = $contract[$filenameKey] ?? '';
                                    $highlightClass = !empty($currentFile) ? 'border-success bg-success bg-opacity-25' : '';

                                    echo '<div class="col-md-6">
                                            <label class="form-label">Contract File ' . $i . (!empty($currentFile) ? ' (<strong><span class="text-success">Current: ' . htmlspecialchars($currentFile) . ' </span></strong>)' : '') . '</label>
                                            <input type="file" name="' . $nameAttr . '" class="form-control ' . $highlightClass . '">
                                        </div>';
                                }

                                // Contract File 6 (stored in contractFilename16)
                                $currentFile6 = $contract['contractFilename16'] ?? '';
                                $highlightClass = !empty($currentFile6) ? 'border-success bg-success bg-opacity-25' : '';
                                echo '<div class="col-md-6">
                                        <label class="form-label">Contract File 6' . (!empty($currentFile6) ? ' (<strong><span class="text-success">Current: ' . htmlspecialchars($currentFile6) . ' </span></strong>)' : '') . '</label>
                                        <input type="file" name="contract_file16" class="form-control ' . $highlightClass . '">
                                    </div>';

                                // Contract Files 7–16 (attachment_6_filename to attachment_15_filename)
                                for ($i = 6; $i <= 15; $i++) {
                                    $nameAttr = 'attachment_' . $i;
                                    $filenameKey = $nameAttr . '_filename';
                                    $currentFile = $contract[$filenameKey] ?? '';
                                    $highlightClass = !empty($currentFile) ? 'border-success bg-success bg-opacity-25' : '';
                                    $labelIndex = $i + 1;

                                    echo '<div class="col-md-6">
                                            <label class="form-label">Contract File ' . $labelIndex . (!empty($currentFile) ? ' (<strong><span class="text-success">Current: ' . htmlspecialchars($currentFile) . ' </span></strong>)' : '') . '</label>
                                            <input type="file" name="' . $nameAttr . '" class="form-control ' . $highlightClass . '">
                                        </div>';
                                }
                                ?>
                                
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-end mt-4">
                        <button type="submit" name="update_btn" class="btn btn-success px-4">
                            <i class="bi bi-save2-fill me-1"></i> Update
                        </button>

                        <a href="user_page.php" class="btn btn-outline-secondary ms-2">
                            <i class="bi bi-arrow-left me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="../../assets/js/jquery-3.7.1.min.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/sweetalert2/dist/sweetalert2.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const securityTypeSelect = document.getElementById('security_type');
    const consumableFields = ['consumable_from_field', 'consumable_to_field'];

    function toggleConsumableFields() {
        consumableFields.forEach(id => {
            const fieldDiv = document.getElementById(id);
            const input = fieldDiv.querySelector('input');
            if (securityTypeSelect.value.toLowerCase() === 'consumable') {
                fieldDiv.classList.remove('d-none');
                input.setAttribute('required', 'required');
            } else {
                fieldDiv.classList.add('d-none');
                input.removeAttribute('required');
            }
        });
    }

    securityTypeSelect.addEventListener('change', toggleConsumableFields);

    // Initial check
    toggleConsumableFields();
});
<?php if (!empty($alertScript)) echo $alertScript; ?>
document.querySelector('form').addEventListener('submit', function (e) {
    const firstName = document.getElementById('authorize_firstname').value.trim();
    const lastName = document.getElementById('authorize_lastname').value.trim();
    const mobile = document.getElementById('authorize_mobileNumber').value.trim();
    const gender = document.getElementById('authorize_gender').value.trim();

    if (firstName !== '' || lastName !== '' || mobile !== '' || gender !== '') {
        let error = '';
        if (firstName === '') error += 'Authorized First Name is required.\n';
        if (lastName === '') error += 'Authorized Last Name is required.\n';
        if (mobile === '') error += 'Authorized Mobile Number is required.\n';
        if (gender === '') error += 'Authorized Gender is required.\n';

        if (error !== '') {
            e.preventDefault();
            alert(error);
        }
    }
});

document.querySelector('form').addEventListener('submit', function (e) {
    const securityType = document.getElementById('security_type').value.trim().toLowerCase();
    const from = document.getElementById('consumable_from').value.trim();
    const to = document.getElementById('consumable_to').value.trim();
    
    if (securityType === 'consumable') {
        let error = '';
        if (from === '') error += 'Consumable From date is required.\n';
        if (to === '') error += 'Consumable To date is required.\n';

        if (error !== '') {
            e.preventDefault();
            alert(error);
        }
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const amountInput = document.getElementById('amount');
    const vatTypeInput = document.getElementById('vat_type');
    const netOfVatInput = document.getElementById('net_of_vat');
    const vatAmountInput = document.getElementById('vat_amount');
    const wtaxInput = document.getElementById('wtax');
    const amountLessorInput = document.getElementById('amount_lessor');

    function round2(num) {
        return (Math.round((num + Number.EPSILON) * 100) / 100).toFixed(2);
    }

    function computeFields() {
        const amount = parseFloat(amountInput.value) || 0;
        const vatType = vatTypeInput.value.trim().toLowerCase();

        let netOfVat = 0;
        let vatAmount = 0;

        // Compute based on vat_type
        if (vatType === 'vatable') {
            netOfVat = amount / 1.12;
            vatAmount = netOfVat * 0.12;
        } else if (vatType === 'vat-exempt' || vatType === 'non-vatable') {
            netOfVat = amount;
            vatAmount = 0;
        } else {
            netOfVat = amount;
            vatAmount = 0;
        }

        // ✅ Round everything to 2 decimals
        const netVatRounded = round2(netOfVat);
        const vatRounded = round2(vatAmount);
        const wtaxRounded = round2(netOfVat * 0.05);
        const amountToLessorRounded = round2(netOfVat - (netOfVat * 0.05));

        // Update inputs
        netOfVatInput.value = netVatRounded;
        vatAmountInput.value = vatRounded;
        wtaxInput.value = wtaxRounded;
        amountLessorInput.value = amountToLessorRounded;
    }

    amountInput.addEventListener('input', computeFields);
    vatTypeInput.addEventListener('change', computeFields);

    computeFields(); // run once on load
});

      const toggleBtn = document.getElementById('toggleSidebar');
  const sidebar = document.getElementById('sidebarMenu');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
  });
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