<?php
session_start();
include '../../config/config.php';

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}

// Include SweetAlert and jQuery
echo '<script src="../../sweetalert2/dist/sweetalert2.all.min.js"></script>';
echo '<link rel="stylesheet" href="../../sweetalert2/dist/sweetalert2.min.css">';
echo '<script src="../../jquery-3.7.1.js"></script>';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_request'])) {
    // Sanitize and fetch the submitted values
    $contract_number = htmlspecialchars(trim($_POST['contract_number']));
    $due_date = htmlspecialchars(trim($_POST['due_date']));
    $new_due_date = htmlspecialchars(trim($_POST['new_due_date']));
    $new_mobile_number_l1 = htmlspecialchars(trim($_POST['new_mobile_number_l1']));

    // Check if the contract number and due date are provided
    if (!empty($contract_number) && !empty($due_date)) {
        // Prepare the base SQL query
        $sql = "UPDATE transactional SET modify_request_status = 'Rm-Pending'";

        // Prepare dynamic parts of the query based on provided inputs
        if (!empty($new_due_date)) {
            $sql .= ", new_due_date = ?";
        }
        if (!empty($new_mobile_number_l1)) {
            $sql .= ", mobile_number_l1 = ?";
        }

        // Complete the query with WHERE clause
        $sql .= " WHERE contract_number = ? AND transaction_date = ? OR new_due_date = ? ";

        // Initialize and prepare the MySQLi statement
        if ($stmt = $conn->prepare($sql)) {
            // Initialize types string and params array
            $types = '';
            $params = [];

            // Add new_due_date if it's provided
            if (!empty($new_due_date)) {
                $types .= 's'; // 's' for string (date)
                $params[] = $new_due_date;
            }
            // Add new_mobile_number_l1 if it's provided
            if (!empty($new_mobile_number_l1)) {
                $types .= 's'; // 's' for string
                $params[] = $new_mobile_number_l1;
            }

            // Add contract_number and due_date to the params array
            $types .= 'sss'; // Adding types for contract_number and due_date
            $params[] = $contract_number;
            $params[] = $due_date;
            $params[] = $due_date;

            // Bind the parameters dynamically
            $stmt->bind_param($types, ...$params); // Use the spread operator

            // Execute the query
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    // Use JavaScript to trigger SweetAlert success message
                    echo "<script>
                        $(document).ready(function() {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Request updated successfully!'
                            });
                        });
                    </script>";
                } else {
                    // Use JavaScript to trigger SweetAlert info message (no rows affected)
                    echo "<script>
                        $(document).ready(function() {
                            Swal.fire({
                                icon: 'info',
                                title: 'Info!',
                                text: 'No changes made to the request.'
                            });
                        });
                    </script>";
                }
            } else {
                // Use JavaScript to trigger SweetAlert error message
                echo "<script>
                    $(document).ready(function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Error updating request: " . $stmt->error . "'
                        });
                    });
                </script>";
            }

            // Close the statement
            $stmt->close();
        } else {
            // Use JavaScript to trigger SweetAlert error message for statement preparation error
            echo "<script>
                $(document).ready(function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Error preparing statement: " . $conn->error . "'
                    });
                });
            </script>";
        }
    } else {
        // Use JavaScript to trigger SweetAlert error message for missing fields
        echo "<script>
            $(document).ready(function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Please fill in all required fields.'
                });
            });
        </script>";
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
            <title>QS - Request Edit Transaction</title>
            <link rel="stylesheet" href="../../boxicons/css/boxicons.min.css">
            <link rel="preload" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
            <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap"></noscript>
            <link rel="preload" as="style" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB"><link rel="stylesheet" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB" type="text/css">
            <!-- custom CSS file link  -->
            <link rel="stylesheet" href="../../css/request_modify.css?v=<?php echo time(); ?>">
            <link rel="stylesheet" href="../../css/responsive.css?v=<?php echo time(); ?>">
            <script src="https://kit.fontawesome.com/30b908cc5a.js" crossorigin="anonymous"></script>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
        </head>
    <body>

    <nav role="navigation" class="nav">
    <ul class="nav-items">
        <li class="nav-item">
            <a href="user_page.php" class="nav-link"><span>Home</span></a>
        </li> 
         <?php
                  $userName = $_SESSION['user_email'];
                  $rolesQuery = "SELECT roles FROM user_form WHERE email = '$userName'";
                  $rolesStmtResult = mysqli_query($conn, $rolesQuery);

                  if (mysqli_num_rows($rolesStmtResult) > 0) {
                     $row = mysqli_fetch_assoc($rolesStmtResult);
                     
                     if ($row['roles'] == 'Am-Creator') {
                  ?>
            <li class="nav-item dropdown">
                <a href="#" class="nav-link"><span>Lessor Profile</span></a>
                <nav class="submenu">
                    <ul class="submenu-items">
                        <li class="submenu-item"><a href="create_lessor_profile.php" class="submenu-link">Add</a></li>
                        <li class="submenu-item"><a href="edit_lessor_profile.php" class="submenu-link">Edit</a></li>
                    </ul>
                </nav>
            </li> 
            <?php
                }else if ($row['roles'] == 'HO') {
                        ?>
                <li class="nav-item">
                    <a href="post_payment.php" class="nav-link"><span>Post Payment</span></a>
                </li>
              <li class="nav-item dropdown">
                  <a href="#" class="nav-link"><span>Lessor Profile</span></a>
                  <nav class="submenu">
                      <ul class="submenu-items">
                          <li class="submenu-item" style="display:none;"><a href="create_lessor_profile.php" class="submenu-link">Add</a></li>
                          <li class="submenu-item"><a href="edit_lessor_profile.php" class="submenu-link">View Lessor Profile</a></li>
                      </ul>
                  </nav>
              </li> 
               <?php
                    }
                }
               ?>
        <li class="nav-item dropdown">
            <?php if($row['roles'] == 'BOOKKEEPER'  || $row['roles'] == 'Finance' || $row['roles'] == 'Auditor' || $row['roles'] == 'HO') { ?>
            <a href="#" style="display:none;" class="nav-link"><span>Contract of Lease</span></a>
            <?php }else{ ?>
            <a href="#" class="nav-link"><span>Contract of Lease</span></a>
            <?php } ?>

            <nav class="submenu">
                <ul class="submenu-items">
                 <?php
                  $userName = $_SESSION['user_email'];
                  $rolesQuery = "SELECT roles FROM user_form WHERE email = '$userName'";
                  $rolesStmtResult = mysqli_query($conn, $rolesQuery);

                  if (mysqli_num_rows($rolesStmtResult) > 0) {
                     $row = mysqli_fetch_assoc($rolesStmtResult);
                     if ($row['roles'] == 'Am-Creator') {
                  ?>
                    <li class="submenu-item"><a href="create_contract.php" class="submenu-link">Create Contract</a></li>
                    <li class="submenu-item"><a href="renew_contract.php" class="submenu-link">Renew Contract</a></li>
                    <li class="submenu-item"><hr class="submenu-seperator" /></li>
                    <li class="submenu-item"><a href="created_contract.php" class="submenu-link">Created Contract</a></li>
                    <li class="submenu-item"><a href="review_contract.php" class="submenu-link">Review Contract</a></li>
                    <li class="submenu-item"><a href="vpo_checker.php" class="submenu-link">Receive Contract</a></li>
                    <li class="submenu-item"><a href="vpo_reviewer.php" class="submenu-link">Check Contract</a></li>
                    <li class="submenu-item"><a href="audit_contract.php" class="submenu-link">Approve Contract</a></li>
                     <?php
                     }elseif($row['roles'] == 'Rm-Reviewer' || $row['roles'] == 'Vpo-Checker' || $row['roles'] == 'Vpo-Approver' || $row['roles'] == 'Vpo-Reviewer'){
                     ?>
                      <li class="submenu-item"><a href="created_contract.php" class="submenu-link">Created Contract</a></li>
                    <li class="submenu-item"><a href="review_contract.php" class="submenu-link">Review Contract</a></li>
                    <li class="submenu-item"><a href="vpo_checker.php" class="submenu-link">Receive Contract</a></li>
                    <li class="submenu-item"><a href="vpo_reviewer.php" class="submenu-link">Check Contract</a></li>
                    <li class="submenu-item"><a href="audit_contract.php" class="submenu-link">Approve Contract</a></li>
                     <?php
                     }elseif($row['roles'] == 'HO'){
                        ?>
                        <li class="submenu-item"><a href="created_contract.php" class="submenu-link">Created Contract</a></li>
                        <li class="submenu-item"><a href="review_contract.php" class="submenu-link">Review Contract</a></li>
                        <li class="submenu-item"><a href="vpo_checker.php" class="submenu-link">Receive Contract</a></li>
                        <li class="submenu-item"><a href="vpo_reviewer.php" class="submenu-link">Check Contract</a></li>
                        <li class="submenu-item"><a href="audit_contract.php" class="submenu-link">Approve Contract</a></li>
                        <?php
                    }
                  }
                  
               ?>   
                </ul>
            </nav>
        </li> 
        <li class="nav-item dropdown">
            <?php if($row['roles'] == 'Finance') { ?>
                <a style="display:none;" href="#" class="nav-link"><span>Reports</span></a>
            <?php }else{ ?>
                <a href="#" class="nav-link"><span>Reports</span></a>
            <?php } ?>

            <nav class="submenu">
                <ul class="submenu-items">
                    <li class="submenu-item"><a href="lease_contract.php" class="submenu-link">Contract of Lease</a></li>
                    <li class="submenu-item"><a href="contract_ledger.php" class="submenu-link">COL - Payment Ledger</a></li>
                    <?php
                    if($row['roles'] == 'BOOKKEEPER'){ 
                    ?>
                    <li class="submenu-item"><a href="edi_extraction.php" class="submenu-link">EDI Extraction</a></li>
                    <?php } ?>
                    <?php
                    if($row['roles'] == 'HO' || $row['roles'] == 'Vpo-Checker'){ 
                    ?>
                    <li class="submenu-item"><a href="ho_page.php" class="submenu-link">Head Office</a></li>
                    <?php } ?>
                    <?php
                    if($row['roles'] == 'HO'){ 
                    ?>
                    <li class="submenu-item"><a href="corporate_report.php" class="submenu-link">By Corporate</a></li>
                    <li class="submenu-item"><a href="payout_report.php" class="submenu-link">Payout Report</a></li>
                    <li class="submenu-item"><a href="sendout_report.php" class="submenu-link">Sendout Report</a></li>
                    <?php } ?>
                </ul>
            </nav>
        </li> 
        <li class="nav-item dropdown">
            <?php
                if($row['roles'] == 'Vpo-Approver' || $row['roles'] == 'Vpo-Reviewer' || $row['roles'] == 'Am-Creator' || $row['roles'] == 'Rm-Reviewer' || $row['roles'] == 'Auditor' || $row['roles'] == 'HO'){ 
            ?>
            <a style="display:none;" href="#" class="nav-link"><span>Data Extraction</span></a>
            <?php }else{ ?>
            <a href="#" class="nav-link"><span>Data Extraction</span></a>
            <?php } ?>
            <nav class="submenu">
                <ul class="submenu-items">
                    <?php
                       if($row['roles'] == 'Vpo-Checker' || $row['roles'] == 'HO'){ 
                    ?>
                    <li class="submenu-item"><a href="request_data_extraction.php" class="submenu-link">Create Data</a></li>
                    <?php
                    }
                    ?>
                    <?php
                       if($row['roles'] == 'Finance'){ 
                    ?>
                    <li class="submenu-item"><a href="extract_request_finance.php" class="submenu-link">Batch Upload</a></li>
                     <?php } ?>
                </ul>
            </nav>
        </li> 
        <li class="nav-item dropdown">
        <?php
            if( $row['roles'] == 'Auditor' || $row['roles'] == 'HO'){ 
            ?>
            <a href="#" style="display:none;" class="nav-link"><span>Manage COL</span></a>
            <nav class="submenu">
                <ul class="submenu-items">
                    <li class="submenu-item"><a href="terminate_contract.php" class="submenu-link">Request Terminate</a></li>
                    <li class="submenu-item"><a href="modify_contract.php" class="submenu-link">Request Edit COL</a></li>
                </ul>
            </nav>
            <?php
            }else{
            ?>
            <a href="#" class="nav-link"><span>Manage COL</span></a>
            <nav class="submenu">
                <ul class="submenu-items">
                    <li class="submenu-item"><a href="terminate_contract.php" class="submenu-link">Request Terminate</a></li>
                    <li class="submenu-item"><a href="modify_contract.php" class="submenu-link">Request Edit COL</a></li>
                </ul>
            </nav>
            <?php } ?>
        </li> 
        <li class="nav-item">
            <a href="../../logout.php" class="nav-link" id="logout"><span>Logout</span>
            <i class='bx bx-log-in'></i>
        </a>
        </li> 
       <b style="font-weight:700; color:#333;"><?php echo strtoupper($_SESSION['user_email']); ?></b>
    </ul>
    
</nav>
<?php
$current_due_date = '';
$mobile_numbers = [
    'l1' => '',
    'l2' => '',
    'l3' => '',
    'l4' => '',
    'l5' => ''
];
$new_due_date = ''; // Initialize new_due_date

if (isset($_POST['proceed_display'])) {
    // Fetching form data
    $contract_number = $_POST['contract_number'];
    $due_date = $_POST['due_date'];

    // Query the database for the matching transaction and mobile numbers
    $sql = "SELECT transaction_date, mobile_number_l1, mobile_number_l2, mobile_number_l3, mobile_number_l4, mobile_number_l5 
            FROM transactional 
            WHERE contract_number = ? AND (transaction_date = ? OR new_due_date = ?)";
    
    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters to avoid SQL injection
        $stmt->bind_param("sss", $contract_number, $due_date, $due_date);
        $stmt->execute();
        $stmt->bind_result($transaction_date, $mobile_number_l1, $mobile_number_l2, $mobile_number_l3, $mobile_number_l4, $mobile_number_l5);

        // Fetch the result and store in $current_due_date and $mobile_numbers
        if ($stmt->fetch()) {
            $current_due_date = $transaction_date; // Set current_due_date
            $new_due_date = $due_date; // Set new_due_date from selected due_date
            $mobile_numbers['l1'] = $mobile_number_l1;
            $mobile_numbers['l2'] = $mobile_number_l2;
            $mobile_numbers['l3'] = $mobile_number_l3;
            $mobile_numbers['l4'] = $mobile_number_l4;
            $mobile_numbers['l5'] = $mobile_number_l5;
        } else {
            $current_due_date = 'No matching transaction date found.';
        }
        $stmt->close();
    }
}
?>
<?php
$current_due_date = '';
$mobile_numbers = [
    'l1' => '',
    'l2' => '',
    'l3' => '',
    'l4' => '',
    'l5' => ''
];
$new_due_date = ''; // Initialize new_due_date
$branch_info = ''; // Initialize branch_info
$region_info = ''; // Initialize region_info
$current_amount = ''; // Initializcurrent

if (isset($_POST['proceed_display'])) {
    // Fetching form data
    $contract_number = $_POST['contract_number'];
    $due_date = $_POST['due_date'];

    // Query the database for the matching transaction, mobile numbers, branch, and region
    $sql = "SELECT transaction_date, edit_amount_lessor, mobile_number_l1, mobile_number_l2, mobile_number_l3, mobile_number_l4, mobile_number_l5, branch, region 
            FROM transactional 
            WHERE contract_number = ? AND (transaction_date = ? OR new_due_date = ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters to avoid SQL injection
        $stmt->bind_param("sss", $contract_number, $due_date, $due_date);
        $stmt->execute();
        $stmt->bind_result($transaction_date, $edit_amount_lessor, $mobile_number_l1, $mobile_number_l2, $mobile_number_l3, $mobile_number_l4, $mobile_number_l5, $branch, $region);

        // Fetch the result and store in variables
        if ($stmt->fetch()) {
            $current_due_date = $transaction_date; // Set current_due_date
            $new_due_date = $due_date; // Set new_due_date from selected due_date
            $current_amount = $edit_amount_lessor; // Set new_amount from edit_amount_lessor
            $mobile_numbers['l1'] = $mobile_number_l1;
            $mobile_numbers['l2'] = $mobile_number_l2;
            $mobile_numbers['l3'] = $mobile_number_l3;
            $mobile_numbers['l4'] = $mobile_number_l4;
            $mobile_numbers['l5'] = $mobile_number_l5;
            $branch_info = $branch; // Set branch_info
            $region_info = $region; // Set region_info
        } else {
            $current_due_date = 'No matching transaction date found.';
        }
        $stmt->close();
    }
}
?>
<!-- HTML Form -->
<form action="" class="form-modify" method="POST">
    <h3 class="mbr-section-title mbr-fonts-style align-center mb-0 display-2">
        <strong style="font-size:35px;">REQUEST FORM</strong>
    </h3>
    <div class="prepared_container">
        <div class="container">

            <div id="branchRegionDisplay" style="display:block;">
                <p style="margin-right: 15px; text-transform: uppercase; color: #d70c0c; font-weight:700;">
                    <strong style="font-size: 14px;">Branch:</strong>
                    <input type="text" name="branch_info" id="branch" value="<?php echo htmlspecialchars($branch_info); ?>" readonly style="width: 80%; border: none; background: transparent; color: #555; font-weight: 700;" />
                </p>
                <p style="text-transform: uppercase; color: #d70c0c; font-weight:700;">
                    <strong style="font-size: 14px;">Region:</strong>
                    <input type="text" name="region_info" id="region" value="<?php echo htmlspecialchars($region_info); ?>" readonly style="width: 80%; border: none; background: transparent; color: #555; font-weight: 700;" />
                </p>
            </div>

            <!-- Contract Selection -->
            <label for="contract_number">Contract Number:</label>
            <input type="text" name="contract_number" id="contract_number" class="form-control" list="contract" placeholder="Select Contract Number"
                value="<?php echo isset($_POST['contract_number']) ? htmlspecialchars($_POST['contract_number']) : ''; ?>"
                oninput="updateContractInfo(this.value)">

            <datalist id="contract">
                <?php
                $contracts = [];
                $sql = "SELECT DISTINCT contract_number, branch, region FROM transactional ORDER BY contract_number DESC";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $contract_number = htmlspecialchars($row['contract_number']);
                        $branch_name = htmlspecialchars($row['branch']);
                        $region = htmlspecialchars($row['region']);
                        // Store contract data in JavaScript-compatible format
                        $contracts[] = [
                            'contract_number' => $contract_number,
                            'branch' => $branch_name,
                            'region' => $region
                        ];
                        // Display options in the datalist
                        echo "<option value='" . $contract_number . "'>" . $contract_number . " - " . $branch_name . " (" . $region . ")</option>";
                    }
                } else {
                    echo "<option value=''>No contracts available</option>";
                }
                ?>
            </datalist>

            <!-- Payment Due Date Selection -->
            <label for="due_date">Payment Due Date:</label>
            <select name="due_date" id="due_date" class="form-control" required>
                <option value="" <?php echo (!isset($_POST['due_date']) || $_POST['due_date'] == '') ? 'selected' : ''; ?>></option>
                <?php
                if (isset($_POST['contract_number'])) {
                    $contract_number = $_POST['contract_number'];
                    $sql = "SELECT transaction_date, COALESCE(new_due_date, transaction_date) AS due_date
                            FROM transactional
                            WHERE contract_number = ?
                            ORDER BY transaction_date";
                    if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("s", $contract_number);
                        $stmt->execute();
                        $stmt->bind_result($transaction_date, $due_date);
                        while ($stmt->fetch()) {
                            $selected = ($due_date == $_POST['due_date']) ? 'selected' : '';
                            echo "<option value='" . htmlspecialchars($due_date) . "' $selected>" . htmlspecialchars($due_date) . "</option>";
                        }
                        $stmt->close();
                    }
                }
                ?>
            </select>

            <script>
          // JavaScript contract data (generated from PHP)
const contractData = <?php echo json_encode($contracts); ?>;

// Function to update both branch/region and due dates based on contract number
function updateContractInfo(contractNumber) {
    // Update branch and region only if contract number is not empty
    if (contractNumber) {
        // Find the selected contract in the contractData array
        const contract = contractData.find(c => c.contract_number === contractNumber);

        if (contract) {
            // Update branch and region input values if the contract is found
            document.getElementById('branch').value = contract.branch;
            document.getElementById('region').value = contract.region;
        }
        // If contract not found, retain the existing values
    } else {
        // If contract number is empty, clear the branch and region inputs
        document.getElementById('branch').value = '';
        document.getElementById('region').value = '';
    }

    // Update due date based on contract number
    if (contractNumber) {
        const xhr = new XMLHttpRequest();
        xhr.open("GET", "fetch_transaction_dates.php?contract_number=" + encodeURIComponent(contractNumber), true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const dueDateSelect = document.getElementById("due_date");
                dueDateSelect.innerHTML = ''; // Clear previous options

                // Add default option
                dueDateSelect.innerHTML += "<option value=''></option>";

                const response = JSON.parse(xhr.responseText);
                if (response.due_dates.length > 0) {
                    response.due_dates.forEach(date => {
                        const selected = (date === "<?php echo isset($_POST['due_date']) ? htmlspecialchars($_POST['due_date']) : ''; ?>") ? 'selected' : '';
                        dueDateSelect.innerHTML += `<option value="${date}" ${selected}>${date}</option>`;
                    });
                }
            }
        };
        xhr.send();
    } else {
        document.getElementById("due_date").innerHTML = "<option value=''></option>";
    }
}

// Call the combined function when the contract number is input
document.getElementById('contract_number').addEventListener('input', function() {
    updateContractInfo(this.value);
});


            </script>

            <!-- Proceed Button -->
            <button type="submit" name="proceed_display" class="proceed_display" id="proceed_display" >PROCEED</button><br>

            <!-- Current Due Date -->
            <label for="current_due_date">Current Due Date:</label>
            <input type="text" name="current_due_date" id="current_due_date" class="form-control" readonly value="<?php echo !empty($new_due_date) ? htmlspecialchars($new_due_date) : ''; ?>">

            <!-- New Due Date -->
            <label for="new_due_date">New Due Date:</label>
            <input type="date" name="new_due_date" id="new_due_date" class="form-control" value="" autocomplete="off">

           <!-- Current and New Mobile Numbers -->
            <?php foreach (['l1', 'l2', 'l3', 'l4', 'l5'] as $level): ?>
                <?php if ($level === 'l1'): // Display only for L1 ?>
                    <label for="current_mobile_number_<?php echo $level; ?>">Current Mobile Number (L<?php echo strtoupper($level[1]); ?>):</label>
                    <input type="text" name="current_mobile_number_<?php echo $level; ?>" readonly id="current_mobile_number_<?php echo $level; ?>" class="form-control" value="<?php echo htmlspecialchars($mobile_numbers[$level]); ?>">

                    <label for="new_mobile_number_<?php echo $level; ?>">New Mobile Number (L<?php echo strtoupper($level[1]); ?>):</label>
                    <input type="text" name="new_mobile_number_<?php echo $level; ?>" 
                        id="new_mobile_number_<?php echo $level; ?>" 
                        class="form-control" 
                        value="" 
                        autocomplete="off"  
                        maxlength="11" 
                        pattern="\d{11}" 
                        title="Please enter an 11-digit phone number" 
                        placeholder="(Starts with 0)"
                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);">
                <?php else: // Hide for L2 to L5 ?>
                    <div style="display: none;">
                        <label for="current_mobile_number_<?php echo $level; ?>">Current Mobile Number (L<?php echo strtoupper($level[1]); ?>):</label>
                        <input type="text" name="current_mobile_number_<?php echo $level; ?>" readonly id="current_mobile_number_<?php echo $level; ?>" class="form-control" value="<?php echo htmlspecialchars($mobile_numbers[$level]); ?>">

                        <label for="new_mobile_number_<?php echo $level; ?>">New Mobile Number (L<?php echo strtoupper($level[1]); ?>):</label>
                        <input type="text" name="new_mobile_number_<?php echo $level; ?>" 
                            id="new_mobile_number_<?php echo $level; ?>" 
                            class="form-control" 
                            value="" 
                            autocomplete="off" 
                            maxlength="11" 
                            pattern="\d{11}" 
                            title="Please enter an 11-digit phone number" 
                            placeholder="(Starts with 0)"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11);">
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- New Amount Input -->
            <label for="current_amount">Current Amount:</label>
            <input type="text" name="current_amount" id="current_amount" class="form-control" value="<?php echo htmlspecialchars($current_amount); ?>" readonly>

            <!-- Send Request Button -->
            <button type="submit" name="send_request" class="send_request" id="send_request">SEND REQUEST</button>
        </div>
    </div>
</form>


<script>
function highlightRow(row) {
  // Remove any existing highlights
  var table = row.closest('table');
  var rows = table.querySelectorAll('tr');
  for (var i = 0; i < rows.length; i++) {
    rows[i].style.backgroundColor = '';
  }

  // Highlight the clicked row
  row.style.backgroundColor = 'skyblue';

  // Get and display the ID
  var selectedId = row.querySelector('td:first-child').innerText;
  document.getElementById('selected_id_display').value = selectedId; // Use 'value' instead of 'textContent'

  
}
document.addEventListener('DOMContentLoaded', function () {
    var dropdownLinks = document.querySelectorAll('.dropdown .nav-link');
    dropdownLinks.forEach(function (el) {
        el.addEventListener('click', onClick, false);
    });

    function onClick(e) {
        e.preventDefault();
        var el = this.parentNode;
        el.classList.contains('show-submenu') ? hideSubMenu(el) : showSubMenu(el);
    }

    function showSubMenu(el) {
        el.classList.add('show-submenu') ;
        document.addEventListener('click', onDocClick);

        function onDocClick(e) {
            if (el.contains(e.target)) {
                return;
            }
            document.removeEventListener('click', onDocClick);
            hideSubMenu(el);
        }
    }

    function hideSubMenu(el) {
        el.classList.remove('show-submenu');
    }
});
    </script>
    </body>
</html>
