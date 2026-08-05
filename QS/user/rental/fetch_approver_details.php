<?php
ob_start(); // 1. Trap any accidental output from included files
session_start(); // 2. Start session BEFORE including config
include '../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];

    // Prepare the query
    $query = "
        SELECT 
            cc.*, 
            e.monthly_rental, 
            e.vat, 
            e.net_of_vat, 
            e.wtax, 
            e.amount_to_lessor 
        FROM create_contract cc
        LEFT JOIN escalation e 
            ON cc.contract_number = e.col_number 
            AND cc.end_date = e.end_date
        WHERE cc.id = ?
        LIMIT 1
    ";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    function getOrdinalSuffix($number) {
        if (!in_array(($number % 100), [11,12,13])) {
            switch ($number % 10) {
                case 1: return 'st';
                case 2: return 'nd';
                case 3: return 'rd';
            }
        }
        return 'th';
    }
    
    if ($row = mysqli_fetch_assoc($result)) {
        echo "<table border='1' cellpadding='10' cellspacing='0'>";    
        if(!empty($row['created_date'])){
            echo "<tr><td><strong>Created Date:</strong></td><td>" . htmlspecialchars(date("F d, Y", strtotime($row['created_date']))) . "</td></tr>";
        }   
        echo "<tr><td><strong>Contract Number:</strong></td><td>" . htmlspecialchars($row['contract_number']) . "</td></tr>";
        echo "<tr><td><strong>Notarized:</strong></td><td>" . htmlspecialchars($row['notarized']) . "</td></tr>";
        echo "<tr><td><strong>RDO Number:</strong></td><td>" . htmlspecialchars($row['rdo']) . "</td></tr>";
        echo "<tr><td><strong>ML Corporate Name:</strong></td><td>" . htmlspecialchars($row['corporate_name']) . "</td></tr>";
        echo "<tr><td><strong>Lease Term:</strong></td><td>" . htmlspecialchars(date('F d, Y', strtotime($row['contract_start']))) . " To " . htmlspecialchars(date('F d, Y', strtotime($row['contract_end']))) . "</td></tr>";
        echo "<tr><td><strong>RFP Period:</strong></td><td>" . htmlspecialchars(date('F Y', strtotime($row['start_date']))) . " To " . htmlspecialchars(date('F Y', strtotime($row['end_date']))) . "</td></tr>";
        echo "<tr><td><strong>Monthly Due Date:</strong></td><td>";

        if (!empty($row['payment_due_date'])) {
            $day = date('j', strtotime($row['payment_due_date']));
            
            // Determine ordinal suffix
            if (!in_array($day % 100, [11, 12, 13])) {
                switch ($day % 10) {
                    case 1:  $suffix = 'st'; break;
                    case 2:  $suffix = 'nd'; break;
                    case 3:  $suffix = 'rd'; break;
                    default: $suffix = 'th'; break;
                }
            } else {
                $suffix = 'th';
            }

            echo "Every " . htmlspecialchars($day . $suffix) . " day of the month";
        } else {
            echo "—"; // Placeholder if date is not set
        }

        echo "</td></tr>";

        echo "<tr><td><strong>Lessor Type:</strong></td><td>" . 
        ($row['lessor_type'] === "Individual" ? "Sole Proprietorship" : htmlspecialchars($row['lessor_type'])) . 
        "</td></tr>";   
        echo "<tr><td><strong>Region:</strong></td><td>" . htmlspecialchars($row['region']) . "</td></tr>";
        echo "<tr><td><strong>Area:</strong></td><td>" . htmlspecialchars($row['area']) . "</td></tr>";
        echo "<tr><td><strong>Branch:</strong></td><td>" . htmlspecialchars($row['branch']) . "</td></tr>";
        if(!empty($row['bank_name']) && !empty($row['bank_accNumber'])){
            echo "<tr><td><strong>Bank Name:</strong></td><td>" . htmlspecialchars($row['bank_name']) . "</td></tr>";
            echo "<tr><td><strong>Bank Account Number:</strong></td><td>" . htmlspecialchars($row['bank_accNumber']) . "</td></tr>";
        }
        // Determine Lessor or Authorize Name
        if (empty($row['authorize_firstname']) && empty($row['authorize_lastname'])) {
            echo "<tr><td><strong>Lessor Name:</strong></td><td>" . htmlspecialchars($row['l1_firstname']) . " " . htmlspecialchars($row['l1_middlename']) . " " . htmlspecialchars($row['l1_lastname']) . "</td></tr>";
            echo "<tr><td><strong>Lessor Mobile Number:</strong></td><td>" . htmlspecialchars($row['mobile_number_l1']) . "</td></tr>";
            if(!empty($row['l2_firstname']) && !empty($row['l2_lastname'])){
                echo "<tr><td><strong>Lessor 2 Name:</strong></td><td>" . htmlspecialchars($row['l2_firstname']) . " " . htmlspecialchars($row['l2_middlename']) . " " . htmlspecialchars($row['l2_lastname']) . "</td></tr>";
                echo "<tr><td><strong>Lessor 2 Mobile Number:</strong></td><td>" . htmlspecialchars($row['mobile_number_l2']) . "</td></tr>";
            }
            if(!empty($row['l3_firstname']) && !empty($row['l3_lastname'])){
                echo "<tr><td><strong>Lessor 3 Name:</strong></td><td>" . htmlspecialchars($row['l3_firstname']) . " " . htmlspecialchars($row['l3_middlename']) . " " . htmlspecialchars($row['l3_lastname']) . "</td></tr>";
                echo "<tr><td><strong>Lessor 3 Mobile Number:</strong></td><td>" . htmlspecialchars($row['mobile_number_l3']) . "</td></tr>";
            }
            if(!empty($row['l4_firstname']) && !empty($row['l4_lastname'])){
                echo "<tr><td><strong>Lessor 4 Name:</strong></td><td>" . htmlspecialchars($row['l4_firstname']) . " " . htmlspecialchars($row['l4_middlename']) . " " . htmlspecialchars($row['l4_lastname']) . "</td></tr>";
                echo "<tr><td><strong>Lessor 4 Mobile Number:</strong></td><td>" . htmlspecialchars($row['mobile_number_l4']) . "</td></tr>";
            }
            if(!empty($row['l5_firstname']) && !empty($row['l5_lastname'])){
                echo "<tr><td><strong>Lessor 5 Name:</strong></td><td>" . htmlspecialchars($row['l5_firstname']) . " " . htmlspecialchars($row['l5_middlename']) . " " . htmlspecialchars($row['l5_lastname']) . "</td></tr>";
                echo "<tr><td><strong>Lessor 5 Mobile Number:</strong></td><td>" . htmlspecialchars($row['mobile_number_l5']) . "</td></tr>";
            }
        }else{
            echo "<tr><td><strong>Authorize to Claim:</strong></td><td>" . htmlspecialchars($row['authorize_firstname']) . " " . htmlspecialchars($row['authorize_middlename']) . " " . htmlspecialchars($row['authorize_lastname']) . "</td></tr>";
            echo "<tr><td><strong>Authorize Mobile Number:</strong></td><td>" . htmlspecialchars($row['authorize_mobileNumber']) . "</td></tr>";
        }
    
        echo "<tr><td><strong>Gross Amount:</strong></td><td>₱ " . number_format($row['monthly_rental'], 2) . "</td></tr>";
        echo "<tr><td><strong>VAT Type:</strong></td><td>" . htmlspecialchars($row['vat_type']) . "</td></tr>";
        // echo "<tr><td><strong>Inputted Amount:</strong></td><td>" . htmlspecialchars($row['inputted_amount']) . "</td></tr>";
        // echo "<tr><td><strong>Net of VAT:</strong></td><td>₱ " . number_format($row['net_of_vat'], 2) . "</td></tr>";
        echo "<tr><td><strong>VAT Amount:</strong></td><td>₱ " . number_format($row['vat'], 2) . "</td></tr>";
        echo "<tr><td><strong>Withholding Tax:</strong></td><td>₱ " . number_format($row['wtax'], 2) . "</td></tr>";
        echo "<tr><td><strong>Amount to Lessor:</strong></td><td>₱ " . number_format($row['amount_to_lessor'], 2) . "</td></tr>";
        echo "<tr><td><strong>Mode of Payment:</strong></td><td>" . htmlspecialchars($row['mode_of_payment']) . "</td></tr>";
        echo "<tr><td><strong>Created By:</strong></td><td>" . htmlspecialchars($row['created_by']) . "</td></tr>";
        echo "<tr><td><strong>Reviewed By:</strong></td><td>" . htmlspecialchars($row['reviewed_by']) . "</td></tr>";
    
        // Add more details as needed
        echo "</table>";
    } else {
        echo "No details found for the selected contract.";
    }

    mysqli_close($conn);
}
?>
