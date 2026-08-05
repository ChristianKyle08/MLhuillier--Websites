<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include '../../config/config.php';

    // Collect data from the POST request
    $selected_id = $_POST['selected_id'];

    // Prepare and execute the SQL query to fetch contract details
    $selectQuery = "
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
        WHERE cc.id = '$selected_id'
        LIMIT 1
    ";

    $result = mysqli_query($conn, $selectQuery);
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
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $response = array(
            'status' => 'success',
            'contract_number' => $row['contract_number'],
            'corporate_lessor' => $row['corporate_lessor'],
            'l1_firstname' => $row['l1_firstname'],
            'l1_middlename' => $row['l1_middlename'],
            'l1_lastname' => $row['l1_lastname'],
            'l1_gender' => $row['l1_gender'],
            'mobile_number_l1' => $row['mobile_number_l1'],
            'l2_firstname' => $row['l2_firstname'],
            'l2_middlename' => $row['l2_middlename'],
            'l2_lastname' => $row['l2_lastname'],
            'l2_gender' => $row['l2_gender'],
            'mobile_number_l2' => $row['mobile_number_l2'],
            'l3_firstname' => $row['l3_firstname'],
            'l3_middlename' => $row['l3_middlename'],
            'l3_lastname' => $row['l3_lastname'],
            'l3_gender' => $row['l3_gender'],
            'mobile_number_l3' => $row['mobile_number_l3'],
            'l4_firstname' => $row['l4_firstname'],
            'l4_middlename' => $row['l4_middlename'],
            'l4_lastname' => $row['l4_lastname'],
            'l4_gender' => $row['l4_gender'],
            'mobile_number_l4' => $row['mobile_number_l4'],
            'l5_firstname' => $row['l5_firstname'],
            'l5_middlename' => $row['l5_middlename'],
            'l5_lastname' => $row['l5_lastname'],
            'l5_gender' => $row['l5_gender'],
            'mobile_number_l5' => $row['mobile_number_l5'],

            'authorize_firstname' => $row['authorize_firstname'],
            'authorize_middlename' => $row['authorize_middlename'],
            'authorize_lastname' => $row['authorize_lastname'],
            'authorize_gender' => $row['authorize_gender'],
            'authorize_mobileNumber' => $row['authorize_mobileNumber'],
            'notarized' => $row['notarized'],

            'zone' => $row['zone'],
            'branch' => $row['branch'],
            'contract_start' => date('F d, Y', strtotime($row['contract_start'])),
            'contract_end' => date('F d, Y', strtotime($row['contract_end'])),
            'start_date' => !empty($row['start_date']) ? date('F d, Y', strtotime($row['start_date'])) : 'N/A',
            'end_date' => !empty($row['end_date']) ? date('F d, Y', strtotime($row['end_date'])) : 'N/A',
            'payment_due_date' => !empty($row['payment_due_date']) 
                ? ('Every ' . date('j', strtotime($row['payment_due_date'])) . 
                    getOrdinalSuffix(date('j', strtotime($row['payment_due_date']))) . 
                    ' day of the month') 
                : '',


            'vat_type' => $row['vat_type'],
            'inputted_amount' => $row['inputted_amount'],
            'amount' => number_format($row['amount'],2),
            'net_of_vat' => number_format($row['net_of_vat'],2),
            'vat_amount' => number_format($row['vat_amount'],2),
            'wtax' => number_format($row['wtax'],2),
            'total_month_rental' => number_format($row['total_month_rental'],2),
            'amount_lessor' => number_format($row['amount_lessor'],2),
            'edit_amount_lessor' => number_format($row['edit_amount_lessor'],2),
            'mode_of_payment' => $row['mode_of_payment'],
            'bank_name' => $row['bank_name'],
            'bank_accNumber' => $row['bank_accNumber'],
            'wallet_number' => $row['wallet_number'],
            'region' => $row['region'],
            'lessor_type' => $row['lessor_type'],
            'corporate_name' => $row['corporate_name'],
            'rdo' => $row['rdo'],
            'area' => $row['area'],
            'created_date' =>$row['created_date'],

            'escalated_monthly_rental' => isset($row['monthly_rental']) ? number_format($row['monthly_rental'], 2) : '0.00',
            'escalated_net_of_vat' => isset($row['net_of_vat']) ? number_format($row['net_of_vat'], 2) : '0.00',
            'escalated_vat' => isset($row['vat']) ? number_format($row['vat'], 2) : '0.00',
            'escalated_wtax' => isset($row['wtax']) ? number_format($row['wtax'], 2) : '0.00',
            'escalated_amount_to_lessor' => isset($row['amount_to_lessor']) ? number_format($row['amount_to_lessor'], 2) : '0.00'
        );
    } else {
        $response = array('status' => 'error');
    }

    // Close the database connection
    mysqli_close($conn);

    // Return the JSON response
    echo json_encode($response);
} else {
    // Handle invalid request method
    $response = array('status' => 'error');
    echo json_encode($response);
}
