<?php 
ob_start(); // 1. Trap any accidental output from included files
session_start(); // 2. Start session BEFORE including config

include '../../config/config.php';

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit;
}

require '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_file'])) {
    $userName = $_SESSION['user_name'];

    // Create a new PhpSpreadsheet instance
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set column headers
    $sheet->setCellValue('A1', 'No');
    $sheet->setCellValue('B1', 'First_Name');
    $sheet->setCellValue('C1', 'Middle_Name');
    $sheet->setCellValue('D1', 'Last_Name');
    $sheet->setCellValue('E1', 'Gender');
    $sheet->setCellValue('F1', 'Branch_Name');
    $sheet->setCellValue('G1', 'Branch_ID');
    $sheet->setCellValue('H1', 'Mobile_Number');
    $sheet->setCellValue('I1', 'Monthly_Due');
    $sheet->setCellValue('J1', 'Amount');

    $query = "SELECT * FROM transactional WHERE extract_request_status = '' AND status != 'Terminated'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $extraction_series);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Set row counter and initial number
    $row = 2;
    $number = 1;

    // Loop through each row of data
    while ($row_data = mysqli_fetch_assoc($result)) {
        $auth_first_name = strtoupper($row_data['authorize_firstName']);
        $auth_middle_name = strtoupper($row_data['authorize_middleName']);
        $auth_last_name = strtoupper($row_data['authorize_lastName']);
        $first_name = strtoupper($row_data['l1_firstname']);
        $middle_name = strtoupper($row_data['l1_middlename']);
        $last_name = strtoupper($row_data['l1_lastname']);
        $gender = strtoupper($row_data['l1_gender']);
        $authorize_gender = strtoupper($row_data['authorize_gender']);
        $branch_name = strtoupper($row_data['branch']);
        $branch_code = strtoupper($row_data['branch_id']);
        $mobile_number = strtoupper($row_data['mobile_number_l1']);
        $auth_mobile_number = strtoupper($row_data['authorize_mobileNumber']);
        $edit_amount_lessor = $row_data['edit_amount_lessor'];

        // Populate Excel rows with data
        $sheet->setCellValue('A' . $row, $number); // Set the row number

        if (empty($auth_first_name) && empty($auth_last_name)) {
            $sheet->setCellValue('B' . $row, $first_name);
            $sheet->setCellValue('C' . $row, $middle_name); // Display l1_middlename
            $sheet->setCellValue('D' . $row, $last_name);
            $sheet->setCellValue('E' . $row, $gender);
            $sheet->setCellValue('H' . $row, $auth_mobile_number);
        } else {
            $sheet->setCellValue('B' . $row, $auth_first_name);
            $sheet->setCellValue('C' . $row, $auth_middle_name); // Display authorize_middleName or empty if it's empty
            $sheet->setCellValue('D' . $row, $auth_last_name);
            $sheet->setCellValue('E' . $row, $authorize_gender);
            $sheet->setCellValue('H' . $row, $auth_mobile_number);
        }
        $sheet->setCellValue('F' . $row, $branch_name);
        $sheet->setCellValue('G' . $row, $branch_code);
        $sheet->setCellValue('H' . $row, $mobile_number);
        
        // Format the start date (assuming it's in 'Y-m-d H:i:s' format)
        $monthlyDue = strtoupper(date('m/d/Y', strtotime($row_data['transaction_date'])));
        $sheet->setCellValue('I' . $row, $monthlyDue);
        $sheet->setCellValue('J' . $row, $edit_amount_lessor);

        // Increment row counter and number 
        $row++;
        $number++;
    }

    $extract_update = "UPDATE transactional SET extract_request_status = 'Extracted', exported_by = ? WHERE extraction_series = ''";
    $stmt_update = mysqli_prepare($conn, $extract_update);
    mysqli_stmt_bind_param($stmt_update, "ss", $userName, $extraction_series);
    mysqli_stmt_execute($stmt_update);

    // Prepare CSV file for download
    $writer = new Csv($spreadsheet);
    $writer->setEnclosure(''); // Disable quoting
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="VPO_FILE_UPLOAD_' . date("Ymd") . '.csv"');
    $writer->save('php://output');

    // Close connection
    mysqli_close($conn);
    exit(); // Ensure script ends after sending the file
}
?>
