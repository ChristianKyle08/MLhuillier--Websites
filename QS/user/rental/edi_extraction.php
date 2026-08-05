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

if(isset($_POST['export_edi'])){
    // Fetch data from database based on $_POST variables
        $status = '';
        $kpxCode = $_POST['kpxCode'];
        $region = $_POST['region'];
        $edi_date = $_POST['edi_date'];

        // Prepare and execute the SQL query
        $selectQuery = "SELECT * FROM transactional WHERE status != '$status'";
        if (!empty($region)) {
            $selectQuery .= " AND region = '$region'";
        }
        if (!empty($kpxCode)) {
            $selectQuery .= " AND kpx_code = '$kpxCode'";
        }
        if (!empty($edi_date)) {
            $selectQuery .= " AND DATE_FORMAT(transaction_date, '%Y-%m') = '$edi_date'";
        }

        $result = mysqli_query($conn, $selectQuery);

    if(mysqli_num_rows($result) > 0) {
        // At least one row where export_status is empty, export the specific rows

        // Create a new PhpSpreadsheet instance
         $spreadsheet = new Spreadsheet();

        // Create a new worksheet
        $sheet = $spreadsheet->getActiveSheet();

        // Set column headers
        $sheet->setCellValue('A1', 'Contract Number');
        $sheet->setCellValue('B1', 'Date');
        $sheet->setCellValue('C1', 'Zone');
        $sheet->setCellValue('D1', 'Region');
        $sheet->setCellValue('E1', 'Branch Code');
        $sheet->setCellValue('F1', 'KPX Code');
        $sheet->setCellValue('G1', 'Branch');
        $sheet->setCellValue('H1', 'Description');
        $sheet->setCellValue('I1', 'Amount');
        $sheet->setCellValue('J1', 'Category');
        $sheet->setCellValue('K1', 'Mode of Payment');
        // Set row counter
        $row = 2;

        // Loop through each row of data
        while ($row_data = mysqli_fetch_assoc($result)) {
            // Populate Excel rows with data
            $sheet->setCellValue('A' . $row, $row_data['contract_number']);
            $sheet->setCellValue('B' . $row, date('F j, Y', strtotime($row_data['transaction_date'])));
            $sheet->setCellValue('C' . $row, $row_data['zone']);
            $sheet->setCellValue('D' . $row, $row_data['region']);
            $sheet->setCellValue('E' . $row, $row_data['branch_code']);
            $sheet->setCellValue('F' . $row, $row_data['kpx_code']);
            $sheet->setCellValue('G' . $row, $row_data['branch']);
            $sheet->setCellValue('H' . $row, 'Monthly Rental');
            $sheet->setCellValue('J' . $row, '₱ ' . number_format($row_data['amount'], 2));
            $sheet->setCellValue('I' . $row, 'Adjusment');
            $sheet->setCellValue('K' . $row, $row_data['mode_of_payment']);

            // Increment row counter
            $row++;
        }

        // Create a writer instance for Xlsx format
        $writer = new Xlsx($spreadsheet);

        // Set headers for Excel file download
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="EDI RENTALS"' .date("Y-m_d"). '".xlsx"');
        header('Cache-Control: max-age=0');

        // Save Excel file to output stream
        $writer->save('php://output');

        // Terminate script execution
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
            <title>ML Rental - EDI Extraction</title>

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
            <span><i class="fa-regular fa-map"></i></span>
            <select name="region" id="region" class="region_select" >
                <option value=""></option>
                <?php
                    $transactional = "SELECT DISTINCT region FROM transactional WHERE region != '' ORDER BY region ASC";
                    $resultregion = mysqli_query($conn, $transactional);
                    if ($resultregion) {
                        while ($rowregion = mysqli_fetch_assoc($resultregion)) {
                            $selected = (isset($_POST['region']) && $_POST['region'] == $rowregion['region']) ? 'selected' : '';
                            echo "<option value='" . $rowregion['region'] . "'  $selected>" . $rowregion['region'] . "</option>";
                        }
                    }
                ?>
            </select>
            <span><i class="fa-regular fa-building"></i></span>
            <select name="branch" id="branch" class="branch_select" onchange="updateKpxCode(this)">
                <option value=""></option>
                <?php
                $region = $_POST['region'];
                $transactional = "SELECT DISTINCT branch, kpx_code FROM transactional WHERE branch != '' ORDER BY branch ASC";
                $resultBranch = mysqli_query($conn, $transactional);
                if ($resultBranch) {
                    while ($rowBranch = mysqli_fetch_assoc($resultBranch)) {
                        $selected = (isset($_POST['branch']) && $_POST['branch'] == $rowBranch['branch']) ? 'selected' : '';
                        echo "<option value='" . $rowBranch['branch'] . "' data-kpx-code='" . $rowBranch['kpx_code'] . "' data-lessor-name='" . $rowBranch['lessor_name'] . "' $selected>" . $rowBranch['branch'] . "</option>";
                    }
                }
                ?>
            </select>
            <i class="fa-regular fa-calendar-days"></i>
            <input type="month" name="edi_date" id="edi_date" value="<?php echo isset($_POST['edi_date']) ? $_POST['edi_date'] : '' ?>" required>
            <input type="hidden" name="kpxCode" id="kpxCode" value="<?php echo isset($_POST['kpxCode']) ? $_POST['kpxCode'] : '' ?>">
            <button type="submit" name="proceed" id="proceed">PROCEED</button>
            <button type="submit" name="export_edi" id="export_edi"><i class="fa-regular fa-file-excel"></i>&nbsp; Extract</button>
        </div>
    </div>
    <?php
    if(isset($_POST['proceed'])){
    ?>
    <div class="container">
		<div class="row justify-content-center">
			<div class="col-12 content-head">
				<div class="mbr-section-head mb-5">
					<h3 class="mbr-section-title mbr-fonts-style align-center mb-0 display-2">
						<strong style="font-size:35px;">EDI EXTRACTION</strong>
					</h3>
				</div>
			</div>
		</div>
	</div>
    
    <div class="table_wrap">
    <table class="contract_lg_table" id="contract_lg_table">
        <thead>
            <tr>
                <th>CONTRACT NUMBER</th>
                <th>DATE</th>
                <th>GL CODE</th>
                <th>ZONE</th>
                <th>REGION</th>
                <th>BRANCH CODE</th>
                <th>KPX CODE</th>
                <th>BRANCH</th>
                <th>DESCRIPTION</th>
                <th>AMOUNT</th>
                <th>CATEGORY</th>
                <th>MODE OF PAYMENT</th>
            </tr>
        </thead>
       <tbody>
        <?php
        $status = '';
        $kpxCode = $_POST['kpxCode'];
        $region = $_POST['region'];
        $edi_date = $_POST['edi_date'];

        // Prepare and execute the SQL query
        $selectQuery = "SELECT * FROM transactional WHERE status != '$status'";
        if (!empty($region)) {
            $selectQuery .= " AND region = '$region'";
        }
        if (!empty($kpxCode)) {
            $selectQuery .= " AND kpx_code = '$kpxCode'";
        }
        if (!empty($edi_date)) {
            $selectQuery .= " AND DATE_FORMAT(transaction_date, '%Y-%m') = '$edi_date'";
        }

        $result = mysqli_query($conn, $selectQuery);

        // Check if any rows are returned
        if (mysqli_num_rows($result) > 0) {
            // Fetch and display the data in the table
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                echo '<tr onclick="highlightRow(this)">';
                echo '<td style="display:none;">' . htmlspecialchars($row['id']) . '</td>';
                echo '<td>' . htmlspecialchars($row['contract_number']) . '</td>';
                echo '<td>' . date('F j, Y', strtotime($row['transaction_date'])) . '</td>';
                echo '<td>' . htmlspecialchars($row['gl_code']) . '</td>';
                echo '<td>' . htmlspecialchars($row['zone']) . '</td>';
                echo '<td>' . htmlspecialchars($row['region']) . '</td>';
                echo '<td>' . htmlspecialchars($row['branch_code']) . '</td>';
                echo '<td>' . htmlspecialchars($row['kpx_code']) . '</td>';
                echo '<td>' . htmlspecialchars($row['branch']) . '</td>';
                echo '<td>' . htmlspecialchars('MONTHLY RENTAL') . '</td>';
                echo '<td style="text-align:center;">₱ ' . number_format($row['amount'], 2) . '</td>';
                echo '<td>' . htmlspecialchars($row['category']) . '</td>';
                echo '<td>' . htmlspecialchars($row['mode_of_payment']) . '</td>';
                echo '</tr>';
            }
        } else {
            // If no transactions found, display a message
            echo '<tr><td colspan="12">No transactions found.</td></tr>';
        }
        // Close the database connection
        mysqli_close($conn);
    }
        ?>
    </tbody>
</table>
    <input type="hidden" id="selected_id_display" name="selectedID" value=""> 
    </div>
</div>
</form>
<script>

function updateKpxCode(select) {
    var kpxCodeInput = document.getElementById('kpxCode');
    if (select.value === "") {
        kpxCodeInput.value = ""; // If empty option is selected, set kpxCode to empty
    } else {
        var selectedOption = select.options[select.selectedIndex];
        var kpxCode = selectedOption.getAttribute('data-kpx-code');
        kpxCodeInput.value = kpxCode; // Set kpxCode to the selected option's data-kpx-code
    }
}

function highlightRow(row) {
  // Remove any existing highlights
  var table = row.closest('table');
  var rows = table.querySelectorAll('tr');
  for (var i = 0; i < rows.length; i++) {
    rows[i].style.backgroundColor = '';
  }

  // Highlight the clicked row
  row.style.backgroundColor = 'lightblue';

  // Get and display the ID
  var selectedId = row.querySelector('td:first-child').innerText;
  document.getElementById('selected_id_display').value = selectedId; // Use 'value' instead of 'textContent'
}
    </script>
    </body>
</html>
