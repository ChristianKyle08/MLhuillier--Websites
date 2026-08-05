<?php
session_start();
    include '../../config/config.php';

    if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Printing Data</title>
    <link rel="stylesheet" href="../../css/print.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../css/responsive.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../boxicons/css/boxicons.min.css">
    <script src="https://kit.fontawesome.com/30b908cc5a.js" crossorigin="anonymous"></script>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>

    <div class="print_head">
        <h1>PRINT DATA</h1>
    </div>
    <div class="btn_back">
        <a href="extract_request_finance.php"><i class="fa-solid fa-arrow-left"></i> Back</a>
    </div>
<form action='' method='POST' id="ledger_form">
    <input type="hidden" name="controlNumber" id="controlNumber" value="">
    <div class="print_table">
        <?php
        // Initialize variables
        $extraction_series = '';
        $rfp_number = '';
        $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);

        // Get user role, area, and region
        $userQuery = "SELECT roles, mainzone, area, region FROM user_form WHERE username = '$user_email' OR email = '$user_email'";
        $resultUser = mysqli_query($conn, $userQuery);
        $user = mysqli_fetch_assoc($resultUser);
        $userRole = $user['roles'];
        $userMainzone = $user['mainzone'];
        $userArea = $user['area'];
        $userRegion = $user['region'];
        // Prepare the SQL query to fetch transactions with extract_request_status equal to 'Requested'
        $query = "SELECT * FROM transactional 
                  WHERE (extract_request_status = 'Requested' OR extract_request_status = 'Received' OR extract_request_status = 'Extracted') AND status != 'Terminated'";

         if (!empty($extraction_series)) {
        $query .= " AND extraction_series = ?";
        if ($userRole == 'Am-Creator') {
            $query .= " AND area = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $extraction_series, $userArea);
        } elseif ($userRole == 'Rm-Reviewer') {
            $query .= " AND region = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $extraction_series, $userRegion);
        } else {
            $query .= " AND mainzone = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $extraction_series, $userMainzone);
        }
    } else {
        if ($userRole == 'Am-Creator') {
            $query .= " AND area = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $userArea);
        } elseif ($userRole == 'Rm-Reviewer') {
            $query .= " AND region = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $userRegion);
        } else {
            $query .= " AND mainzone = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $userMainzone);
        }
    }

        // Execute the statement
        $stmt->execute();

        // Get result set
        $result = $stmt->get_result();
        if ($result) {
            $groupedRows = [];

            while ($row = mysqli_fetch_assoc($result)) {
                $extractionSeries = $row['extraction_series'];

                if (!isset($groupedRows[$extractionSeries])) {
                    $groupedRows[$extractionSeries] = [];
                }

                $lessor1_name = trim($row['l1_firstname'] . ' ' . $row['l1_middlename'] . ' ' . $row['l1_lastname']);
                $lessor2_name = trim($row['l2_firstname'] . ' ' . $row['l2_middlename'] . ' ' . $row['l2_lastname']);

                $groupedRows[$extractionSeries][] = [
                    'extraction_series' => $row['extraction_series'],
                    'rfp_number' => $row['rfp_number'],
                    'extract_requested_by' => $row['extract_requested_by'],
                    'amount_lessor' => $row['amount_lessor'],
                    'contract_number' => $row['contract_number'],
                    'start_date' => $row['start_date'],
                    'end_date' => $row['end_date'],
                    'region' => $row['region'],
                    'area' => $row['area'],
                    'branch_code' => $row['branch_code'],
                    'branch' => $row['branch'],
                    'l1_firstname' => $row['l1_firstname'],
                    'l1_middlename' => $row['l1_middlename'],
                    'l1_lastname' => $row['l1_lastname'],
                    'l1_gender' => $row['l1_gender'],
                    'kpx_code' => $row['kpx_code'],
                    'mobile_number_l1' => $row['mobile_number_l1'],
                    'lessor1_name' => $lessor1_name,
                    'lessor2_name' => $lessor2_name,
                    'transaction_date' => date('F j, Y', strtotime($row['transaction_date'])),
                    'extract_request_status' => $row['extract_request_status'],
                    'amount' => $row['amount']
                ];
            }

            if (!empty($groupedRows)) {
                echo "<div class='table_wrap'>
                    <table class='contract_lg_table' id='contract_lg_table'>
                        <thead>
                            <tr>
                               <th>Extraction Series</th>
                                <th>Total Amount</th>
                                <th>Branch Name</th>
                                <th>Lessor Name</th>
                                <th>Requested By</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>";

                foreach ($groupedRows as $extractionSeries => $data) {
                    $totalAmount = array_sum(array_column($data, 'amount_lessor'));

                    echo "<tr class='dataRow' data-extraction_series='$extractionSeries'>";
                    echo "<td>" . $extractionSeries . "</td>";
                    echo "<td>" . number_format($totalAmount, 2) . "</td>";
                    echo "<td>" . $data[0]['branch'] . "</td>";
                     echo "<td>";
                    $displayedNames = array(); // To keep track of displayed names

                    foreach ($data as $entry) {
                        // Check if the name has already been displayed
                        if (!in_array($entry['lessor1_name'], $displayedNames)) {
                            echo $entry['lessor1_name'] . "<br>";
                            // Add the name to the list of displayed names
                            $displayedNames[] = $entry['lessor1_name'];
                        }

                        // Check if the second name is different and has not been displayed
                        if ($entry['lessor2_name'] !== $entry['lessor1_name'] && !in_array($entry['lessor2_name'], $displayedNames)) {
                            echo $entry['lessor2_name'] . "<br>";
                            // Add the name to the list of displayed names
                            $displayedNames[] = $entry['lessor2_name'];
                        }
                    }

                    echo "</td>";
                    echo "<td>" . $data[0]['extract_requested_by'] . "</td>";
                    echo "<td>" . $data[0]['extract_request_status'] . "</td>";

                   
                   
                    echo "<td><button type='button' class='showBtn' id='show' data-extraction_series='$extractionSeries'>Show</button></td>";
                    
                    echo "</tr>";
                }

                echo "</tbody></table></div>";

                echo "<div id='myModal' class='modal'>
                          <div class='modal-content'>
                              <div id='modalContent' class='modal-table'></div>
                          </div>
                      </div>";

             echo "<script>
                const transactionData = " . json_encode($groupedRows) . ";

                document.querySelectorAll('.dataRow, .showBtn').forEach(row => {
                    row.addEventListener('click', () => {
                        const extractionSeries = row.getAttribute('data-extraction_series');
                        const modalContent = document.getElementById('modalContent');
                        let modalHTML = '';

                        transactionData[extractionSeries].forEach(entry => {
                            const branchCode = entry['branch_code'];
                            const branch = entry['branch'];
                            const lessor1_name = entry['lessor1_name'];
                            const lessor2_name = entry['lessor2_name'];
                            const transactionDate = entry['transaction_date'];

                            modalHTML += '<tr>' +
                                '<td>' + entry['contract_number'] + '</td>' +
                                '<td>' + entry['l1_firstname'] + '</td>' +
                                '<td>' + entry['l1_middlename'] + '</td>' +
                                '<td>' + entry['l1_lastname'] + '</td>' +
                                '<td>' + entry['l1_gender'] + '</td>' +
                                '<td>' + entry['region'] + '</td>' +
                                '<td>' + branch + '</td>' +
                                '<td>' + branchCode + '</td>' +
                                '<td>' + entry['mobile_number_l1'] + '</td>' +
                                '<td>' + transactionDate + '</td>' +
                                '<td>' + Number(entry['amount_lessor']).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>' +
                                '</tr>';
                        });

                        modalContent.innerHTML = '<style>' +
                            '@media print {' +
                            '    @page {' +
                            '        size: A4 landscape;' + 
                            '        margin: 0;' + 
                            '        padding: 0;' + 
                            '    }' +
                            '    .modal-table {' +
                            '        width: 100%;' +
                            '        border-collapse: collapse;' +
                            '        overflow:hidden;' +
                            '        font-size:10px;' +
                            '        margin: -2px;' + 
                            '    }' +
                            '    .modal-table th, .modal-table td {' +
                            '        padding: 3px;' +
                            '        text-align: center;' +
                            '        border-bottom: 1px solid #ddd;' +
                            '        color: black;' + // New styling: text color black for better readability
                            '    }' +
                            '    .modal-table th {' +
                            '        background-color: #333;' +
                            '    }' +
                            '    @page :left {' +
                            '        margin-left: 0;' + // Remove left margin
                            '    }' +
                            '    @page :right {' +
                            '        margin-right: 0;' + // Remove right margin
                            '    }' +
                            '}' +
                            '</style>' +
                            '<table class=\"modal-table\">' +
                            '<thead>' +
                            '<tr>' +
                            '<th>Contract Number</th>' +
                            '<th>First Name</th>' +
                            '<th>Middle Name</th>' +
                            '<th>Last Name</th>' +
                            '<th>Gender</th>' +
                            '<th>Region</th>' +
                            '<th>Branch</th>' +
                            '<th>Branch Code</th>' +
                            '<th>Mobile Number</th>' +
                            '<th>Transaction Date</th>' +
                            '<th>Monthly Rental</th>' +
                            '</tr>' +
                            '</thead>' +
                            '<tbody>' + modalHTML + '</tbody></table>' +
                            '<div class=\"modal-footer\">' +
                            '<button type=\"button\" class=\"printBtn\">Print</button>' +
                            '<button type=\"button\" class=\"closeBtn\">Close</button>' +
                            '</div>';

                        const modal = document.getElementById('myModal');
                        modal.style.display = 'block';

                        const closeButton = document.querySelector('.closeBtn');
                        closeButton.addEventListener('click', () => {
                            modal.style.display = 'none';
                        location.reload();

                        });


                        const printButton = document.querySelector('.printBtn');
                        printButton.addEventListener('click', () => {
                            // Hide print and close buttons for printing
                            const modalFooter = modalContent.querySelector('.modal-footer');
                            modalFooter.style.display = 'none';
                            window.print();
                            // Restore buttons visibility after printing
                            modalFooter.style.display = 'flex';
                        });
                    });
                });
            </script>";

            } else {
                echo "<div style='border:4px solid whitesmoke; text-align:center; font-weight:700; margin: 5px 100px; padding: 15px; border-radius:10px; font-size:20px; background-color:whitesmoke; color:#333;'>NO TRANSACTIONS FOUND!</div>";
            }

            $stmt->close();
            $conn->close();
        }
        ?>
    </div>
</form>


</body>
</html>