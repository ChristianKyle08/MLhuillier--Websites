<?php
session_start();
    include '../../config/config.php';

    if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    }
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Include the PhpSpreadsheet autoloader
require '../../vendor/autoload.php';

if(isset($_POST['download'])){
    // Fetch data from database based on $_POST variables
    $status = 'Unpaid';
    $userName = $_SESSION['user_name'];
    $transaction_startDate = $_POST['extract_startDate'];
    $transaction_endDate = $_POST['extract_endDate'];
    $extracted_status = 'Extracted';
    $request_status = 'Requested';

    // Check if there are rows within the selected date range where export_status is empty
    $query_check_exported = "SELECT * FROM transactional WHERE status = ? AND (export_status = '' OR export_status IS NULL) AND DATE_FORMAT(transaction_date, '%Y-%m') >= ? AND DATE_FORMAT(transaction_date, '%Y-%m') <= ?";
    $stmt_check_exported = mysqli_prepare($conn, $query_check_exported);
    mysqli_stmt_bind_param($stmt_check_exported, "sss", $status, $transaction_startDate, $transaction_endDate);
    mysqli_stmt_execute($stmt_check_exported);
    $result_check_exported = mysqli_stmt_get_result($stmt_check_exported);

    if(mysqli_num_rows($result_check_exported) > 0) {
        // At least one row where export_status is empty, export the specific rows

        // Create a new PhpSpreadsheet instance
         $spreadsheet = new Spreadsheet();

        // Create a new worksheet
        $sheet = $spreadsheet->getActiveSheet();

        // Set column headers
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'First_Name');
        $sheet->setCellValue('C1', 'Middle_Name');
        $sheet->setCellValue('D1', 'Last_Name');
        $sheet->setCellValue('E1', 'Gender');
        $sheet->setCellValue('F1', 'Branch_Code');
        $sheet->setCellValue('G1', 'Mobile_No');
        $sheet->setCellValue('H1', 'Start_Date');
        $sheet->setCellValue('I1', 'End_Date');
        $sheet->setCellValue('J1', 'Amount');

        // Fetch data from database
        $query = "SELECT * FROM transactional WHERE status = ? AND (export_status = '' OR export_status IS NULL) AND DATE_FORMAT(transaction_date, '%Y-%m') >= ? AND DATE_FORMAT(transaction_date, '%Y-%m') <= ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sss", $status, $transaction_startDate, $transaction_endDate);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Set row counter and initial number
        $row = 2;
        $number = 1;

        // Loop through each row of data
        while ($row_data = mysqli_fetch_assoc($result)) {
            // Determine which name fields to use
            $first_name = !empty($row_data['authorize_firstName']) ? $row_data['authorize_firstName'] : $row_data['l1_firstname'];
            $middle_name = !empty($row_data['authorize_middleName']) ? $row_data['authorize_middleName'] : $row_data['l1_middlename'];
            $last_name = !empty($row_data['authorize_lastName']) ? $row_data['authorize_lastName'] : $row_data['l1_lastname'];

            // Populate Excel rows with data
            $sheet->setCellValue('A' . $row, $number); // Set the row number
            $sheet->setCellValue('B' . $row, $first_name);
            $sheet->setCellValue('C' . $row, $middle_name);
            $sheet->setCellValue('D' . $row, $last_name);
            $sheet->setCellValue('E' . $row, $row_data['l1_gender']);
            $sheet->setCellValue('F' . $row, $row_data['branch_code']);
            $sheet->setCellValue('G' . $row, $row_data['mobile_number_l1']);
            $sheet->setCellValue('H' . $row, $row_data['start_date']);
            $sheet->setCellValue('I' . $row, $row_data['end_date']);
            $sheet->setCellValue('J' . $row, '₱ ' . number_format($row_data['amount'], 2));

            // Increment row counter and number
            $row++;
            $number++;
        }


        // Update export status for the exported data
        $extract_update = "UPDATE transactional SET export_status = '$extracted_status', exported_by = '$userName' WHERE status = '$status' AND (export_status = '' OR export_status IS NULL) AND DATE_FORMAT(transaction_date, '%Y-%m') >= '$transaction_startDate' AND DATE_FORMAT(transaction_date, '%Y-%m') <= '$transaction_endDate'";
        $extractRes = mysqli_query($conn, $extract_update);

        // Create a writer instance for Xlsx format
        $writer = new Xlsx($spreadsheet);

        // Set headers for Excel file download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="contract_ledger.xlsx"');
        header('Cache-Control: max-age=0');

        // Save Excel file to output stream
        $writer->save('php://output');

        // Terminate script execution
        exit;
    } else {
        // No rows where export_status is empty within the selected date range, show warning modal
        echo '<script src="https://kit.fontawesome.com/30b908cc5a.js" crossorigin="anonymous"></script>';
        echo '<div id="myModal" class="modal">';
        echo '<form action="export_request.php" method="POST"><div class="modal-content">';
        echo '<center><i class="fa-solid fa-triangle-exclamation"></i></center>';
        echo '<h3>Warning</h3><br>';
        echo '<p>Data already exported or requested!</p><br>';
        echo '<input type="hidden" name="start" value="' . $transaction_startDate . '">';
        echo '<input type="hidden" name="end" value="' . $transaction_endDate . '">';
        echo '<center><button type="submit" name="request_export" id="request_export">Send Request</button><br><button type="button" class="close" onclick="redirectToContractLedger()">Close</button></center>';
        echo '</div></form></div>';
        echo '<style>@import url(\'https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600&display=swap\');*{font-family: \'Poppins\', sans-serif;margin:0;padding:0;box-sizing:border-box;outline:none;border:none;text-decoration:none;}.modal{font-family:Poppins,sans-serif;display:block;position:fixed;z-index:1000;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgba(0,0,0,0.4);}.modal-content{background-color:#fefefe;border-radius:10px;margin:5% auto;padding:10px 35px 50px 35px;width:fit-content;}.close{font-weight:500;text-align:center;font-size:14px;color:#fff;padding:10px 25px;border-radius:50px;border:1px solid #d70c0c;background-color:#d70c0c;cursor:pointer;}.close:hover,.close:focus{color:white;text-decoration:none;cursor:pointer;}p{text-align:center;font-weight:300;font-size:18px;color:#333;line-height:25px;}i{font-weight:700;font-size:100px;text-align:center;color:darkorange;}h3{font-weight:700;font-size:24px;text-align:center;color:#333;}#request_export{font-weight:500;text-align:center;font-size:14px;color:#fff;padding:10px 25px;border-radius:50px;border:1px solid #31c45d;background-color:#31c45d;cursor:pointer;margin-bottom:10px;}</style>';
        echo '<script>var modal = document.getElementById("myModal");var span = document.getElementsByClassName("close")[0];span.onclick = function() { window.location.href = "contract_ledger.php";  };window.onclick = function(event) { if (event.target == modal) { modal.style.display = "none"; } };</script>';
        exit;
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
            <title>ML Rental - For Review Data</title>
            <link rel="stylesheet" href="../../boxicons/css/boxicons.min.css">
            <link rel="preload" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
            <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap"></noscript>
            <link rel="preload" as="style" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB"><link rel="stylesheet" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB" type="text/css">
            <!-- custom CSS file link  -->
            <link rel="stylesheet" href="../../css/contract_ledger.css?v=<?php echo time(); ?>">
            <link rel="stylesheet" href="../../css/responsive.css?v=<?php echo time(); ?>">
            <script src="https://kit.fontawesome.com/30b908cc5a.js" crossorigin="anonymous"></script>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

            <script src="../../sweetalert2/dist/sweetalert2.all.min.js"></script>
            <link rel="stylesheet" href="../sweetalert2/dist/sweetalert2.min.css">
            <script src="../../jquery-3.7.1.js"></script>
        </head>
    <body>
    <?php include ('navbar.php'); ?>
<form action="" method="POST" id="ledger_form">
<div class="contract_lg_container">
    <div class="wrapper">
        <div class="search_div">
            <!-- <span><i class="fa-regular fa-building"></i></span>
            <select name="branch" id="branch" class="branch_select" required onchange="updateKpxCode(this)">
    <option value="ALL">ALL BRANCHES</option>
    <?php
    // Use prepared statements to prevent SQL injection
    $transactional = "SELECT DISTINCT branch, kpx_code FROM transactional WHERE branch != '' AND (extract_request_status IS NULL OR extract_request_status = '') ORDER BY branch ASC";
    $stmt = mysqli_prepare($conn, $transactional);

    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $resultBranch = mysqli_stmt_get_result($stmt);

        if ($resultBranch) {
            while ($rowBranch = mysqli_fetch_assoc($resultBranch)) {
                $selected = (isset($_POST['branch']) && $_POST['branch'] == $rowBranch['branch']) ? 'selected' : '';
                echo "<option value='" . $rowBranch['branch'] . "' data-kpx-code='" . $rowBranch['kpx_code'] . "' $selected>" . $rowBranch['branch'] . "</option>";
            }
        } else {
            echo "No branches found.";
        }
        
        mysqli_stmt_close($stmt);
    } else {
        echo "Error in preparing statement: " . mysqli_error($conn);
    }
    ?>
</select>

            <i class="fa-solid fa-file-signature"></i>
           <select name="contractNumber" id="contractNumber" class="contract_select" <?php echo ($_POST['branch'] == 'ALL') ? '' : 'required'; ?> onchange="this.form.submit()">
                    <option value=""></option>
                    <?php
                    $selected_branch = $_POST['branch'];
                    if ($selected_branch != 'ALL') {
                        $contract = "SELECT DISTINCT contract_number FROM transactional WHERE contract_number != '' AND branch = '$selected_branch' ORDER BY contract_number ASC";
                        $resultContract = mysqli_query($conn, $contract);
                        if ($resultContract) {
                            while ($rowContract = mysqli_fetch_assoc($resultContract)) {
                                $selected = (isset($_POST['contractNumber']) && $_POST['contractNumber'] == $rowContract['contract_number']) ? 'selected' : '';
                                echo "<option value='" . $rowContract['contract_number'] . "' $selected>" . $rowContract['contract_number'] . "</option>";
                            }
                        }
                    }
                    ?>
                </select>
            <input style="width:350px;" type="hidden" name="lessor_name" id="lessor_name" value="<?php echo isset($_POST['lessor_name']) ? $_POST['lessor_name'] : '' ?>" readonly>
            <input type="hidden" name="kpxCode" id="kpxCode" value="<?php echo isset($_POST['kpxCode']) ? $_POST['kpxCode'] : '' ?>"> -->
         <select name="seriesNumber" id="seriesNumber" class="series_select">
                    <option value=""></option>
                    <?php
                        $contract = "SELECT DISTINCT extraction_series FROM transactional WHERE extraction_series != '' AND extract_request_status = 'Requested' ORDER BY extraction_series ASC";
                        $resultContract = mysqli_query($conn, $contract);
                        if ($resultContract) {
                            while ($rowContract = mysqli_fetch_assoc($resultContract)) {
                                $selected = (isset($_POST['seriesNumber']) && $_POST['seriesNumber'] == $rowContract['extraction_series']) ? 'selected' : '';
                                echo "<option value='" . $rowContract['extraction_series'] . "' $selected>" . $rowContract['extraction_series'] . "</option>";
                            }
                        }
                    ?>
                </select>
        <button type="submit" name="proceed_btn" id="proceed_btn">FILTER</button>
        </div>

        <input type="hidden" id="selected_id_display" name="selectedID" value=""> 
    </div>
</div>
<form action='' method='POST'>
<?php
// Check if the request to update extract request status is submitted
if (isset($_POST['review_extract'])) {
    $seriesNumber = $_POST['seriesNumber'];
        // Prepare the SQL query to update extract request status
        $query1 = "UPDATE transactional 
                   SET extract_request_status = 'Reviewed'
                   WHERE extract_request_status = 'Requested'
                   AND extraction_series = ?";

        // Prepare the statement
        $stmt1 = mysqli_prepare($conn, $query1);

        if ($stmt1) {
            // Bind parameters to the prepared statement
            mysqli_stmt_bind_param($stmt1, 's', $seriesNumber);

            // Execute the statement
            if (mysqli_stmt_execute($stmt1)) {
                echo "<script>alert('Extract request status updated successfully!');</script>";
            } else {
                echo "<script>alert('Error updating extract request status!');</script>";
            }

            // Close the prepared statement
            mysqli_stmt_close($stmt1);
        } else {
            echo "Error in preparing update statement: " . mysqli_error($conn);
        }
    } 


// Check if the form is submitted to fetch transactions
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['proceed_btn'])) {
        $extraction_series = $_POST['seriesNumber'];
        $query = "SELECT DISTINCT rfp_number FROM transactional 
              WHERE extract_request_status = 'Requested' AND extraction_series = ?";

        // Prepare the statement with a parameterized query
        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {
            // Bind the extraction series parameter
            mysqli_stmt_bind_param($stmt, "s", $extraction_series);

            // Execute the statement
            mysqli_stmt_execute($stmt);

            // Get result set
            $result = mysqli_stmt_get_result($stmt);

            // Initialize variable to store RFP number
            $rfp_number = '';

            // Fetch the first distinct RFP number (if available)
            if ($row = mysqli_fetch_assoc($result)) {
                $rfp_number = $row['rfp_number'];
            }

            // Close the prepared statement
            mysqli_stmt_close($stmt);
        }
        // Prepare the SQL query to fetch transactions based on start and end dates
        $query = "SELECT * FROM transactional 
                  WHERE extract_request_status = 'Requested' AND extraction_series = '$extraction_series'";

        // Prepare the statement
        $stmt = mysqli_prepare($conn, $query);

            // Execute the statement
            mysqli_stmt_execute($stmt);

            // Get result set
            $result = mysqli_stmt_get_result($stmt);

            if ($result) {
                 $totalAmount = 0; // Initialize total amount variable
                    $groupedRows = [];

                    while ($row = mysqli_fetch_assoc($result)) {
                        $branch = $row['contract_number'];

                        if (!isset($groupedRows[$branch])) {
                            $groupedRows[$branch] = [
                                'contract_number' => $row['contract_number'],
                                'start_date' => $row['start_date'],
                                'end_date' => $row['end_date'],
                                'region' => $row['region'],
                                'area' => $row['area'],
                                'branch_code' => $row['branch_code'],
                                'branch' => $row['branch'],
                                'lessor_names' => [],
                                'transactions' => [],
                                'extract_request_status' => $row['extract_request_status'],
                                'amount' => $row['amount']
                            ];
                        }

                        $lessor1_name = trim($row['l1_firstname'] . ' ' . $row['l1_middlename'] . ' ' . $row['l1_lastname']);
                        $lessor2_name = trim($row['l2_firstname'] . ' ' . $row['l2_middlename'] . ' ' . $row['l2_lastname']);

                        $groupedRows[$branch]['lessor_names'][] = [$lessor1_name, $lessor2_name];
                        $groupedRows[$branch]['transactions'][] = date('F j, Y', strtotime($row['transaction_date']));
                    }

                    if (!empty($groupedRows)) {
                        echo "<div class='total_div'>
                        <span>Total Amount: </span>
                        <input type='text' name='total_amount' id='total_amount' value='' readonly>
                         <span>RFP Number: </span>
                        <input type='text' name='rfp_number' id='rfp_number' value='" . htmlspecialchars($rfp_number) . "' readonly>
                    </div>
                        <center><button type='submit' id='review_extract' name='review_extract'>Review File Extraction</button></center><br><div class='table_wrap'>
                            <table class='contract_lg_table' id='contract_lg_table'>
                                <thead>
                                    <tr>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Region</th>
                                        <th>Area</th>
                                        <th>Branch Code</th>
                                        <th>Branch</th>
                                        <th>Lessor Name</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>";

                        foreach ($groupedRows as $branch => $data) {
                            echo "<tr>";
                            echo "<td>" . date('F j, Y', strtotime($data['start_date'])) . "</td>";
                            echo "<td>" . date('F j, Y', strtotime($data['end_date'])) . "</td>";
                            echo "<td>" . $data['region'] . "</td>";
                            echo "<td>" . $data['area'] . "</td>";
                            echo "<td>" . $data['branch_code'] . "</td>";
                            echo "<td>" . $data['branch'] . "</td>";

                            echo "<td>";
                            if (isset($data['lessor_names'][0])) {
                                echo $data['lessor_names'][0][0] . "<br>";
                                echo $data['lessor_names'][0][1] . "<br>";
                            }
                            echo "</td>";

                            echo "<td><button type='button' class='showBtn' id='show' data-branch='$branch'>Show</button></td>";
                            echo "</tr>";
                            $totalAmount += $data['amount'];    
                        }

                        echo "</tbody></table></div>";
                        // End output buffering and store the output in a variable
                        $output = ob_get_clean();

                        // Output the buffered HTML
                        echo $output;

                        // Output JavaScript to set the value of total_amount input field
                        echo "<script>
                                document.getElementById('total_amount').value = '₱ " . number_format($totalAmount, 2) . "'; // Set the total amount value
                                document.getElementById('amountInput').value = '₱ " . number_format($totalAmount, 2) . "'; // Set the total amount value
                            </script>";
                        echo "<div id='myModal' class='modal'>
                                  <div class='modal-content'>
                                      <span class='close'>&times;</span>
                                      <div id='modalContent' class='modal-table'></div>
                                  </div>
                              </div>";

                        echo "<script>
                                const transactionData = " . json_encode($groupedRows) . ";
                                document.querySelectorAll('.showBtn').forEach(button => {
                                    button.addEventListener('click', () => {
                                        const branch = button.getAttribute('data-branch');
                                        const modalContent = document.getElementById('modalContent');
                                        let modalHTML = '<h2>' + transactionData[branch]['branch'] + '</h2>' +
                                                        '<table class=\"modal-table\">' +
                                                        '<thead>' +
                                                        '<tr>' +
                                                        '<th>Contract Number</th>' +
                                                        '<th>Region</th>' +
                                                        '<th>Area</th>' +
                                                        '<th>Branch Code</th>' +
                                                        '<th>Branch</th>' +
                                                        '<th>Lessor Name</th>' +
                                                        '<th>2nd Lessor Name</th>' +
                                                        '<th>Transaction Date</th>' +
                                                        '<th>Monthly Rental</th>' +
                                                        '<th>Export Status</th>' +
                                                        '</tr>' +
                                                        '</thead>' +
                                                        '<tbody>';

                                        transactionData[branch]['lessor_names'].forEach((lessorNames, index) => {
                                            const lessor1_name = lessorNames[0];
                                            const lessor2_name = lessorNames[1];
                                            const transactionDate = transactionData[branch]['transactions'][index];

                                            modalHTML += '<tr>' +
                                                '<td>' + transactionData[branch]['contract_number'] + '</td>' +
                                                '<td>' + transactionData[branch]['region'] + '</td>' +
                                                '<td>' + transactionData[branch]['area'] + '</td>' +
                                                '<td>' + transactionData[branch]['branch_code'] + '</td>' +
                                                '<td>' + transactionData[branch]['branch'] + '</td>' +
                                                '<td>' + lessor1_name + '</td>' +
                                                '<td>' + lessor2_name + '</td>' +
                                                '<td>' + transactionDate + '</td>' +
                                                '<td>' + Number(transactionData[branch]['amount']).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>' +
                                                '<td>' + (transactionData[branch]['extract_request_status'] || '') + '</td>' +
                                                '</tr>';
                                        });

                                        modalHTML += '</tbody></table>';
                                        modalContent.innerHTML = modalHTML;

                                        const modal = document.getElementById('myModal');
                                        modal.style.display = 'block';

                                        const closeButton = document.querySelector('.close');
                                        closeButton.addEventListener('click', () => {
                                            modal.style.display = 'none';
                                        });

                                        window.onclick = function(event) {
                                            if (event.target === modal) {
                                                modal.style.display = 'none';
                                            }
                                        };
                                    });
                                });
                            </script>";
                    } else {
                        echo "<div style='border:4px solid whitesmoke; text-align:center; font-weight:700; margin: 5px 100px; padding: 15px; border-radius:10px; font-size:20px; background-color:whitesmoke; color:#333;'>NO TRANSACTIONS FOUND!</div>";
                    }
                } else {
                    echo "Error in query: " . mysqli_error($conn);
                }

                mysqli_stmt_close($stmt);
            }
    mysqli_close($conn);
?>
<form method="post" action="">
    <input type="text" name="contract_id" id="contract_id" value="">
    <!-- Add other form fields and submit button as needed -->
</form>
<script>
// Get the modal
var modal = document.getElementById("myModal");

// Get the button that opens the modal
var btn = document.getElementById("export_ledger");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks the button, open the modal 
btn.onclick = function() {
    modal.style.display = "block";
}

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
    modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

function updateKpxCode(branchSelect) {
        const selectedBranch = branchSelect.selectedOptions[0];
        const kpxCode = selectedBranch.dataset.kpxCode;
        const lessor_name = selectedBranch.dataset.lessorName; // Corrected the property name here
        const kpxCodeInput = document.getElementById('kpxCode');
        const lessorInput = document.getElementById('lessor_name');
        kpxCodeInput.value = kpxCode;
        lessorInput.value = lessor_name;

        // Assuming your form has an ID 'yourFormId', adjust it accordingly
        const form = document.getElementById('ledger_form');

        // Submit the form
        form.submit();
}

function highlightRow(row) {
  // Remove any existing highlights
  var table = row.closest('table');
  var rows = table.querySelectorAll('tr');
  for (var i = 0; i < rows.length; i++) {
    rows[i].style.backgroundColor = '';
  }

  // Highlight the clicked row
  row.style.backgroundColor = 'transparent';

  // Get and display the ID
  var selectedId = row.querySelector('td:first-child').innerText;
  document.getElementById('selected_id_display').value = selectedId; // Use 'value' instead of 'textContent'
}
    </script>
    </body>
</html>
