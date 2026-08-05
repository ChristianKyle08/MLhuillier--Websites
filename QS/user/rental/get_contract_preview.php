<?php
session_start();
include '../../config/config.php';

if (!isset($_SESSION['user_email'])) {
    header('Location: login_form.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

$query = "SELECT * FROM create_contract WHERE id = $id";
$result = mysqli_query($conn, $query);

if ($row = mysqli_fetch_assoc($result)) {

    $labelOverrides = [
        // Authorized Person Details
        'l1_gender'        => ['label' => 'Lessor 1 Gender', 'icon' => 'gender-ambiguous'],
        'mobile_number_l1'  => ['label' => 'Lessor 1 Mobile Number', 'icon' => 'telephone'],
        'l2_gender'        => ['label' => 'Lessor 2 Gender', 'icon' => 'gender-ambiguous'],
        'mobile_number_l2'  => ['label' => 'Lessor 2 Mobile Number', 'icon' => 'telephone'],
        'authorize_gender'        => ['label' => 'Authorized Gender', 'icon' => 'gender-ambiguous'],
        'authorize_mobileNumber'  => ['label' => 'Authorized Mobile Number', 'icon' => 'telephone'],

        // Contract Information
        'contract_number'         => ['label' => 'Contract Number', 'icon' => 'file-earmark-text'],
        'notarized'               => ['label' => 'Notarized', 'icon' => 'check2-circle'],
        'rdo'                     => ['label' => 'RDO', 'icon' => 'check2-circle'],
        'branch_id'               => ['label' => 'Branch ID', 'icon' => 'hash'],
        'branch'                  => ['label' => 'Branch', 'icon' => 'geo-alt'],
        'created_date'            => ['label' => 'Created Date', 'icon' => 'clock-history'],

        // Contract Period
        'contract_start'          => ['label' => 'Effectivity Date', 'icon' => 'calendar'],
        'contract_end'            => ['label' => 'Expiry Date', 'icon' => 'calendar-x'],
        'start_date'              => ['label' => 'RFP Start Date', 'icon' => 'calendar'],
        'end_date'                => ['label' => 'RFP End Date', 'icon' => 'calendar-x'],

        // Payment & Amounts
        'amount'                  => ['label' => 'Amount', 'icon' => 'cash'],
        'vat_type'                => ['label' => 'VAT Type', 'icon' => 'percent'],
        'net_of_vat'              => ['label' => 'Net of VAT', 'icon' => 'cash-stack'],
        'vat_amount'              => ['label' => 'VAT Amount', 'icon' => 'percent'],
        'wtax'                    => ['label' => 'WTax', 'icon' => 'file-earmark-minus'],
        'amount_lessor'           => ['label' => 'Amount to Lessor', 'icon' => 'hand-index-thumb'],
        'payment_due_date'        => ['label' => 'Payment Due Date', 'icon' => 'calendar-event'],

        // Advance Rental & Deposit
        'advanceRental_amount'    => ['label' => 'Advance Rental Amount', 'icon' => 'wallet'],
        'advanceRental_from'      => ['label' => 'Advance Rental From', 'icon' => 'calendar-plus'],
        'advanceRental_to'        => ['label' => 'Advance Rental To', 'icon' => 'calendar-check'],
        'securityDeposit_amount'  => ['label' => 'Security Deposit Amount', 'icon' => 'shield-lock'],
        'security_type'           => ['label' => 'Security Type', 'icon' => 'key'],

        // Consumable Period
        'consumable_from'         => ['label' => 'Consumable From', 'icon' => 'calendar-plus'],
        'consumable_to'           => ['label' => 'Consumable To', 'icon' => 'calendar-check'],
    ];    

    $dateFields = [
        'contract_start', 'contract_end', 'start_date', 'end_date', 
        'payment_due_date', 'created_date', 
        'advanceRental_from', 'advanceRental_to', 
        'consumable_from', 'consumable_to'
    ];
    $amountFields = [
        'amount', 'net_of_vat', 'vat_amount', 'wtax', 
        'amount_lessor', 'advanceRental_amount', 'securityDeposit_amount'
    ];

    echo "<style>
        .contract-details {
            background-color: #fff;
            border-radius: 12px;
            padding: 20px;
            color: #333;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .contract-details h4 {
            color: #d70c0c;
            margin-bottom: 20px;
        }
        .list-group-item {
            background-color: #fff;
            border: none;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .icon {
            color: #d70c0c;
            margin-right: 10px;
        }
        .label {
            font-weight: 600;
        }
    </style>";

    echo "<div class='contract-details'>";
    echo "<h4><i class='bi bi-file-earmark-text icon'></i>Contract Summary</h4>";
    echo "<ul class='list-group'>";

    // Display Lessor full names
    for ($i = 1; $i <= 5; $i++) {
        $first = trim($row["l{$i}_firstname"] ?? '');
        $middle = trim($row["l{$i}_middlename"] ?? '');
        $last = trim($row["l{$i}_lastname"] ?? '');

        if (empty($first) && empty($middle) && empty($last)) continue;

        $fullName = htmlspecialchars("{$first} {$middle} {$last}");
        echo "<li class='list-group-item'>
                <i class='bi bi-person-badge icon'></i>
                <span class='label'>Lessor {$i}:</span> {$fullName}
              </li>";
    }

    // Display Authorized Person full name
    $afn = trim($row['authorize_firstname'] ?? '');
    $amn = trim($row['authorize_middlename'] ?? '');
    $aln = trim($row['authorize_lastname'] ?? '');
    if (!empty($afn) || !empty($amn) || !empty($aln)) {
        $authorizedName = htmlspecialchars("{$afn} {$amn} {$aln}");
        echo "<li class='list-group-item'>
                <i class='bi bi-person icon'></i>
                <span class='label'>Authorized Person:</span> {$authorizedName}
              </li>";
    }
// After fetching $row from the database
$showAmountDetails = false; // default to hide

// Show amount fields only if rfp_status is not empty or NULL
if (isset($row['rfp_status']) && trim($row['rfp_status']) !== '') {
    $showAmountDetails = true;
}
    foreach ($labelOverrides as $field => $meta) {

        // ❌ Hide payment & amount fields when rfp_status is NULL or empty
        if (
            !$showAmountDetails &&
            in_array($field, [
                'amount',
                'vat_type',
                'net_of_vat',
                'vat_amount',
                'wtax',
                'amount_lessor',
                'advanceRental_amount',
                'securityDeposit_amount'
            ], true)
        ) {
            continue;
        }
    
        if (!isset($row[$field]) || $row[$field] === '') continue;
    
        // Skip empty/null start_date and end_date
        if (in_array($field, ['start_date', 'end_date'], true) &&
            (empty($row[$field]) || $row[$field] === '0000-00-00')) {
            continue;
        }
    
        $label = $meta['label'];
        $icon  = $meta['icon'];
        $rawValue = $row[$field];
        $value = '';
    
        if ($field === 'payment_due_date') {
    
            $timestamp = strtotime($rawValue);
            $day = (int)date('j', $timestamp);
            $suffix = 'th';
    
            if (!in_array($day % 100, [11, 12, 13], true)) {
                $suffix = ['th','st','nd','rd'][$day % 10] ?? 'th';
            }
    
            $value = "Every {$day}{$suffix} day of the month";
    
        } elseif (in_array($field, ['start_date','end_date','contract_start','contract_end'], true)) {
    
            $value = date('F Y', strtotime($rawValue));
    
        } elseif (in_array($field, $dateFields, true)) {
    
            $value = date('F d, Y', strtotime($rawValue));
    
        } elseif (in_array($field, $amountFields, true)) {
    
            $value = "₱ " . number_format((float)$rawValue, 2);
    
        } else {
    
            $value = htmlspecialchars($rawValue, ENT_QUOTES, 'UTF-8');
        }
    
        echo "<li class='list-group-item'>
                <i class='bi bi-{$icon} icon'></i>
                <span class='label'>{$label}:</span> {$value}
              </li>";
    }
    

    echo "</ul>";
    echo "</div>";

} else {
    echo "<div class='alert alert-danger'>Contract not found.</div>";
}
?>
