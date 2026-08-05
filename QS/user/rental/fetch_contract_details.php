<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM create_contract WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($contract = mysqli_fetch_assoc($result)) {
        // Contract data found
    } else {
        http_response_code(404);
        echo "<div class='alert alert-danger'>Contract not found.</div>";
        exit();
    }

    mysqli_stmt_close($stmt);
} else {
    http_response_code(400);
    echo "<div class='alert alert-warning'>Invalid contract ID.</div>";
    exit();
}
?>

<style>
  .section-title {
    color: #d70c0c;
    font-weight: 600;
    margin: 1.5rem 0 1rem;
    font-size: 1.1rem;
    border-bottom: 2px solid #d70c0c;
    padding-bottom: 4px;
  }
  .info-block {
    background-color: #fff;
    border: 1px solid #ddd;
    border-left: 4px solid #d70c0c;
    padding: 1rem 1.25rem;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    margin-bottom: 1rem;
  }
  .info-label {
    color: #333;
    font-weight: 400;
  }
  .info-value {
    color: #333;
  }
</style>

<div class="container-fluid">
  <div class="row info-block gy-3">
    <?php
    function displayField($label, $value) {
        echo '<div class="col-md-4">';
        echo "<div><strong>{$label}:</strong><br><span class='info-value'>" . htmlspecialchars($value) . "</span></div>";
        echo '</div>';
    }

    $labelOverrides = [
        'authorize_mobilenumber' => 'Authorize Mobile Number',
        'advanceRental_amount' => 'Advance Rental Amount',
        'securityDeposit_amount' => 'Security Deposit Amount',
        'advanceRental_from' => 'Advance Rental From',
        'advanceRental_to' => 'Advance Rental To',
        'net_of_vat' => 'Net of VAT',
        'vat_amount' => 'VAT Amount',
        'wtax' => 'WTax',
        'amount_lessor' => 'Amount to Lessor',
        'payment_due_date' => 'Payment Due Date',
        'contract_start' => 'Effectivity Date',
        'contract_end' => 'Expiry Date',
        'start_date' => 'Start Date (RFP)',
        'end_date' => 'End Date (RFP)',
        'consumable_from' => 'Consumable From',
        'consumable_to' => 'Consumable To',
        'created_date' => 'Created Date',
    ];

    $dateFields = [
        'start_date', 'end_date', 'contract_start', 'contract_end', 'payment_due_date', 'created_date'
    ];

    $amountFields = [
        'amount', 'vat_type', 'net_of_vat','vat_amount', 'wtax', 'amount_lessor', 'advanceRental_amount', 'advanceRental_from', 'advanceRental_to', 'securityDeposit_amount'
        ,'security_type' , 'consumable_from', 'consumable_to'
    ];

    $skipFields = [
        'id', 'created_at', 'updated_at', 'kpx_code', 'series', 'branch_code', 'total_month_rental', 
        'lessor_first_name', 'lessor_middle_name', 'lessor_last_name',
        'l1_firstname', 'l1_middlename', 'l1_lastname', 'l1_gender', 'mobile_number_l1',
        'l2_firstname', 'l2_middlename', 'l2_lastname', 'l2_gender', 'mobile_number_l2',
        'l3_firstname', 'l3_middlename', 'l3_lastname', 'l3_gender', 'mobile_number_l3',
        'l4_firstname', 'l4_middlename', 'l4_lastname', 'l4_gender', 'mobile_number_l4',
        'l5_firstname', 'l5_middlename', 'l5_lastname', 'l5_gender', 'mobile_number_l5',
        'authorize_firstname', 'authorize_middlename', 'authorize_lastname', 'authorize_gender', 'authorize_mobileNumber',
        'edit_amount_lessor',
        'contract_file', 'request_status', 'status', 'lessor_type',
        'contract_file2', 'contract_file3', 'contract_file4', 'contract_file5',
        'contract_file16', 'attachment_6', 'attachment_7', 'attachment_8',
        'attachment_9', 'attachment_10', 'attachment_11', 'attachment_12',
        'attachment_13', 'attachment_14', 'attachment_15',
        'mimeType', 'mimeType2', 'mimeType3', 'mimeType4', 'mimeType5',
        'mimeType6', 'mimeType7', 'mimeType8', 'mimeType9', 'mimeType10',
        'mimeType11', 'mimeType12', 'mimeType13', 'mimeType14', 'mimeType15'
    ];

    // === Section: Lessor Information ===
    echo "<div class='col-12'><h5 class='section-title'>Lessor Information</h5></div>";

    $lessorName = trim(($contract['lessor_first_name'] ?? '') . ' ' . ($contract['lessor_middle_name'] ?? '') . ' ' . ($contract['lessor_last_name'] ?? ''));
    if ($lessorName) displayField("Lessor Name", $lessorName);

    if (!empty($contract['lessor_type'])) {
        $type = strtolower($contract['lessor_type']) === 'individual' ? 'Sole Proprietorship' : $contract['lessor_type'];
        displayField("Lessor Type", $type);
    }

// === Section: Lessor Representatives ===
$hasLessorData = false;

// Check if any L1-L5 fields have values
for ($i = 1; $i <= 5; $i++) {
    if (
        !empty($contract["l{$i}_firstname"]) ||
        !empty($contract["l{$i}_middlename"]) ||
        !empty($contract["l{$i}_lastname"]) ||
        !empty($contract["l{$i}_gender"]) ||
        !empty($contract["mobile_number_l{$i}"])
    ) {
        $hasLessorData = true;
        break;
    }
}

$hasAuthorizeToClaim = !empty($contract['authorize_to_claim_lessor']);

if ($hasLessorData || $hasAuthorizeToClaim) {
    echo "<div class='col-12'><h5 class='section-title'>Lessor Representatives</h5></div>";

    for ($i = 1; $i <= 5; $i++) {
        $firstname = $contract["l{$i}_firstname"] ?? '';
        $middlename = $contract["l{$i}_middlename"] ?? '';
        $lastname = $contract["l{$i}_lastname"] ?? '';
        $gender = $contract["l{$i}_gender"] ?? '';
        $mobile = $contract["mobile_number_l{$i}"] ?? '';

        $hasThisLessor = $firstname || $middlename || $lastname || $gender || $mobile;

        if ($hasThisLessor) {
            echo "<div class='col-md-6'>";
            $name = trim("$firstname $middlename $lastname");
            if (!empty($name)) {
                displayField("Lessor {$i} Name", $name);
            }
            if (!empty($gender)) {
                displayField("Lessor {$i} Gender", $gender);
            }
            if (!empty($mobile)) {
                displayField("Lessor {$i} Mobile Number", $mobile);
            }
            echo "</div>";
        }
    }

    if ($hasAuthorizeToClaim) {
        echo "<div class='col-md-6'>";
        displayField("Authorize to Claim Lessor", $contract['authorize_to_claim_lessor']);
        echo "</div>";
    }
}



  // === Section: Authorized Representative ===
$hasAuthorizeRep = 
!empty($contract['authorize_firstname']) || 
!empty($contract['authorize_middlename']) || 
!empty($contract['authorize_lastname']) || 
!empty($contract['authorize_gender']) || 
!empty($contract['authorize_mobileNumber']);

if ($hasAuthorizeRep) {
echo "<div class='col-12'><h5 class='section-title'>Authorized Representative</h5></div>";
echo "<div class='col-md-6'>";

// Full Name
$authNameParts = array_filter([
    $contract['authorize_firstname'] ?? '',
    $contract['authorize_middlename'] ?? '',
    $contract['authorize_lastname'] ?? ''
]);
$authName = implode(' ', $authNameParts);
if (!empty($authName)) {
    displayField("Authorized Person", $authName);
}

// Gender
if (!empty($contract['authorize_gender'])) {
    displayField("Authorize Gender", $contract['authorize_gender']);
}

// Mobile Number
if (!empty($contract['authorize_mobileNumber'])) {
    displayField("Authorize Mobile Number", $contract['authorize_mobileNumber']);
}

echo "</div>";
}


   // === Section: Contract Dates ===
    echo "<div class='col-12'><h5 class='section-title'>Contract Dates</h5></div>";

    foreach ($dateFields as $field) {
        // Skip payment_due_date from normal loop
        if ($field === 'payment_due_date') continue;

        if (!empty($contract[$field]) && $contract[$field] !== '0000-00-00') {
            $label = $labelOverrides[$field] ?? ucwords(str_replace('_', ' ', $field));

            // Format start_date & end_date as "F Y", others as "F j, Y"
            if ($field === 'start_date' || $field === 'end_date') {
                $formatted = date('F Y', strtotime($contract[$field]));
            } else {
                $formatted = date('F j, Y', strtotime($contract[$field]));
            }

            displayField($label, $formatted);
        }
    }

    // Special formatting for payment_due_date
    if (!empty($contract['payment_due_date'])) {
        $day = (int)date('j', strtotime($contract['payment_due_date']));
        $suffix = 'th';
        if (!in_array($day % 100, [11, 12, 13])) {
            $suffix = ['st','nd','rd'][$day % 10 - 1] ?? 'th';
        }
        displayField("Payment Due Date", "Every {$day}{$suffix} day of the month");
    }

    if (isset($contract['rfp_status']) && trim($contract['rfp_status']) !== '') {

        // === Section: Amount Computation ===
        echo "<div class='col-12'><h5 class='section-title'>Amount Computation</h5></div>";
    
        foreach ($amountFields as $field) {
    
            if (isset($contract[$field]) && $contract[$field] !== '') {
    
                $label = $labelOverrides[$field] ?? ucwords(str_replace('_', ' ', $field));
    
                $formattedValue = is_numeric($contract[$field])
                    ? number_format((float)$contract[$field], 2)
                    : htmlspecialchars($contract[$field], ENT_QUOTES, 'UTF-8');
    
                displayField($label, $formattedValue);
            }
        }
    }
    
    
    // === Section: Other Contract Details ===
    echo "<div class='col-12'><h5 class='section-title'>Other Contract Details</h5></div>";
    foreach ($contract as $key => $value) {
        if (in_array($key, $skipFields) || in_array($key, $dateFields) || in_array($key, $amountFields) || $value === '' || $value === null) continue;

        $label = $labelOverrides[$key] ?? ucwords(str_replace('_', ' ', $key));
        displayField($label, $value);
    }
    ?>
  </div>
</div>
