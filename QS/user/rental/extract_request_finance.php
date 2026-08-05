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
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="generator" content="Mobirise v5.9.13, mobirise.com">
            <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
            <link rel="shortcut icon" href="../../assets/images/mlw-logo-96x96.png" type="image/x-icon">
            <meta name="description" content="">
            <title>ML Rental - Download Data</title>
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
<form action='' method='POST' id="ledger_form">
    <input type="hidden" name="controlNumber" id="controlNumber" value="">
    <div class="printing_div">
        <a href="print.php" class="print">Go to Printing <i class="fa-solid fa-arrow-right"></i></a>
    </div>
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

    // Prepare the SQL query to fetch transactions with extract_request_status equal to 'Requested' or 'Received'
    $query = "SELECT * FROM transactional WHERE (extract_request_status = 'Requested' OR extract_request_status = 'Received') AND status != 'Terminated'";

    // If a series number is provided, include it in the query
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
                'edit_amount_lessor' => $row['edit_amount_lessor'],
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
                'branch_id' => $row['branch_id'],
                'mobile_number_l1' => $row['mobile_number_l1'],
                'lessor1_name' => $lessor1_name,
                'lessor2_name' => $lessor2_name,
                'transaction_date' => date('F j, Y', strtotime($row['transaction_date'])),
                'extract_request_status' => $row['extract_request_status'],
                'amount' => $row['amount']
            ];
        }
        // Fetch branches based on user role
        if ($userRole == 'Am-Creator') {
            $branchQuery = "SELECT DISTINCT area FROM user_form WHERE area = '$userArea'";
        } elseif ($userRole == 'Rm-Reviewer') {
            $branchQuery = "SELECT DISTINCT region FROM user_form WHERE region = '$userRegion'";
        } else {
            $branchQuery = "SELECT DISTINCT mainzone FROM user_form WHERE mainzone = '$userMainzone'";
        }
        $branchResult = mysqli_query($conn, $branchQuery);
        $branches = [];
        while ($branch = mysqli_fetch_assoc($branchResult)) {
            $branches[] = $branch;
        }
        if (!empty($groupedRows)) {
            echo "<div class='table_wrap'>
                <table class='contract_lg_table' id='contract_lg_table'>
                    <thead>
                        <tr>
                            <th>Extraction Series</th>
                            <th>Total Amount</th>
                            <th>Requested By</th>
                            <th>Branch Name</th>
                            <th>Lessor Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>";

            foreach ($groupedRows as $extractionSeries => $data) {
                $totalAmount = array_sum(array_column($data, 'edit_amount_lessor'));

                echo "<tr class='dataRow' data-extraction_series='$extractionSeries'>";
                echo "<td>" . $extractionSeries . "</td>";
                echo "<td>" . number_format($totalAmount, 2) . "</td>";
                echo "<td>" . $data[0]['extract_requested_by'] . "</td>";
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
                echo "<td>" . $data[0]['extract_request_status'] . "</td>";

                // Check extract_request_status and display appropriate button
                if ($data[0]['extract_request_status'] === 'Received') {
                    // Display download file button
                    echo "<td><button type='button' class='downloadBtn' data-extraction_series='$extractionSeries'>Open to Download</button></td>";
                } else {
                    // Display show button
                    echo "<td><button type='button' class='showBtn' id='show' data-extraction_series='$extractionSeries'>Show</button></td>";
                }
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

                    transactionData[extractionSeries].forEach((entry, index) => {
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
                                    '<td>' + entry['branch_id'] + '</td>' +
                                    '<td>' + entry['region'] + '</td>' +
                                    '<td>' + branch + '</td>' +
                                    '<td>' + branchCode + '</td>' +
                                    '<td>' + entry['mobile_number_l1'] + '</td>' +
                                    '<td>' + transactionDate + '</td>' +
                                    '<td>' + Number(entry['edit_amount_lessor']).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + '</td>' +
                                    '</tr>';
                    });

                    modalContent.innerHTML = '<table class=\"modal-table\">' +
                                            '<thead>' +
                                            '<tr>' +
                                            '<th>Contract Number</th>' +
                                            '<th>First Name</th>' +
                                            '<th>Middle Name</th>' +
                                            '<th>Last Name</th>' +
                                            '<th>Gender</th>' +
                                            '<th>Branch ID</th>' +
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
                                            '<button type=\"button\" class=\"receivedBtn\">Received</button>' +
                                            '<button type=\"button\" class=\"downloadFile\" name=\"download_file\">Download</button>' +
                                            '<button type=\"button\" class=\"closeBtn\">Close</button>' +
                                            '</div>';

                    const modal = document.getElementById('myModal');
                    modal.style.display = 'block';

                    const closeButton = document.querySelector('.closeBtn');
                    closeButton.addEventListener('click', () => {
                        modal.style.display = 'none';
                        location.reload();
                    });

                    const receivedButton = document.querySelector('.receivedBtn');
                    const downloadButton = document.querySelector('.downloadFile');
                    const extractionStatus = transactionData[extractionSeries][0]['extract_request_status']; // Assuming each extraction series has the same status
                    
                    if (extractionStatus === 'Received') {
                        receivedButton.style.display = 'none';
                        downloadButton.style.display = 'inline-block'; // Display the download button
                    } else if (extractionStatus === 'Requested') {
                        downloadButton.style.display = 'none'; // Hide the download button
                    
                        receivedButton.addEventListener('click', () => {
                            // Send an AJAX request to update_status.php
                            const xhr = new XMLHttpRequest();
                            xhr.open('POST', 'update_status.php', true);
                            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                            xhr.onload = function () {
                                if (xhr.status === 200) {
                                    const response = JSON.parse(xhr.responseText);
                                    if (response.status === 'success') {
                                        // Show SweetAlert modal upon successful update
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success!',
                                            text: response.message
                                        }).then(() => {
                                            // Redirect to extract_request_finance page upon successful update
                                            window.location.href = 'extract_request_finance.php';
                                        });
                                    } else {
                                        // Handle error response
                                        console.error(response.message);
                                    }
                                } else {
                                    console.error('Request failed. Status: ' + xhr.status);
                                }
                            };
                            xhr.onerror = function () {
                                console.error('Request failed. Network error.');
                            };
                            xhr.send('controlNumber=' + extractionSeries);
                        });
                    }

                   downloadButton.addEventListener('click', () => {
                        // Send an AJAX request to download.php
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', 'download.php', true);
                        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                        xhr.responseType = 'blob';
                        xhr.onload = function () {
                            if (xhr.status === 200) {
                                const blob = new Blob([xhr.response], { type: 'text/csv' });
                                const url = window.URL.createObjectURL(blob);
                                const a = document.createElement('a');
                                a.style.display = 'none';
                                a.href = url;
                                a.download = 'VPO_FILE_UPLOAD_' + new Date().toISOString().slice(0, 10) + '.csv';
                                document.body.appendChild(a);
                                a.click();
                                window.URL.revokeObjectURL(url);
                            } else {
                                console.error('Request failed. Status: ' + xhr.status);
                            }
                        };
                        xhr.onerror = function () {
                            console.error('Request failed. Network error.');
                        };
                        xhr.send('controlNumber=' + extractionSeries);
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
