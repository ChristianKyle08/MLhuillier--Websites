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
  <!-- Site made with Mobirise Website Builder v5.9.13, https://mobirise.com -->
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
  <link rel="shortcut icon" href="../../assets/images/mlw-logo-96x96.png" type="image/x-icon">
  <meta name="description" content="">
  
  <title>HOME PAGE</title>
    <link rel="stylesheet" href="../../boxicons/css/boxicons.min.css">
  <link rel="preload" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap"></noscript>
    <link rel="preload" as="style" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB"><link rel="stylesheet" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB" type="text/css">
  <!-- custom CSS file link  -->
   <link rel="stylesheet" href="../../css/user_page.css?v=<?php echo time(); ?>">
   <link rel="stylesheet" href="../../css/responsive.css?v=<?php echo time(); ?>">

   <script src="https://kit.fontawesome.com/30b908cc5a.js" crossorigin="anonymous"></script>
   <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
   <style>
/* Bell icon */
.bell-icon {
  font-size: 34px;
  cursor: pointer;
  position: fixed;
  top: 20px;
  right: 50px;
  z-index: 1000;
}

.notification-count {
  font-size: 10px;
  color: white;
  background-color: red;
  border-radius: 50%;
  padding: 2px 8px;
  position: absolute;
  top: 8px;
  left: -5px;
}

/* Container for the notification */
.notif-container {
  position: relative;
  margin: 20px;
  padding: 20px;
  overflow: auto;
  background-color: #d70c0c; /* Background color for the warning notification */
  border: 1px solid #d70c0c;
  border-radius: 5px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

/* Notification text */
.notif {
  font-size: 20px;
  color: #fff;
}

/* Table styles */
.table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
}

.table th, .table td {
  padding: 10px;
  font-size: 14px;
  text-align: left;
  border-bottom: 1px solid #ddd;
}

.table th {
    color: #333;
  background-color: #fff;
}

/* Pop-up details */
.table tbody tr {
  position: relative;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.table tbody tr:hover {
    color: #333;
  background-color: #f1f1f1;
}

.table tbody tr:hover .popup-details {
  display: block;
}

.popup-details {
  display: none;
  position: absolute;
  top: 100%;
  left: 0;
  width: 100%;
  padding: 10px;
  background-color: #fff;
  border: 1px solid #ddd;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  z-index: 10;
  box-sizing: border-box;
}

#count{
    background-color: #d70c0c;
    padding: 2px 8px 2px 8px;
    color: yellow;
    font-weight: 700;
    margin-left: 10px;
    font-style:normal;
    border-radius: 25px;
}

.pull-left, .pull-right{
    color:#333;
}
   </style>
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
                  $rolesQuery = "SELECT roles, mainzone FROM user_form WHERE email = '$userName'";
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
        <?php if($row['roles'] == 'Am-Creator') { ?>
        <li class="nav-item">
            <a href="rfp_page.php" class="nav-link"><span>Request For Payment</span></a>
        </li> 
        <?php } ?> 
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
                    <li class="submenu-item"><a href="view_contracts.php" class="submenu-link">View Contract</a></li>
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
            // Check if the user role is Auditor or HO
            if ($row['roles'] == 'Auditor' || $row['roles'] == 'HO') { 
                ?>
                <a href="#" style="display:none;" class="nav-link"><span>Manage COL</span></a>
                <nav class="submenu">
                    <ul class="submenu-items">
                        <?php if ($row['roles'] != 'Auditor' && $row['roles'] != 'HO') { ?>
                            <li class="submenu-item"><a href="terminate_contract.php" class="submenu-link">Request Terminate</a></li>
                        <?php } ?>
                        <?php if ($row['roles'] == 'Am-Creator' || $row['roles'] == 'Vpo-Checker') { ?>
                            <li class="submenu-item"><a href="modify_contract.php" class="submenu-link">Request Edit COL</a></li>
                        <?php } ?>
                    </ul>
                </nav>
            <?php
            } else { // For all other roles
                ?>
                <a href="#" class="nav-link"><span>Manage COL</span></a>
                <nav class="submenu">
                    <ul class="submenu-items">
                        <?php if ($row['roles'] != 'Auditor' && $row['roles'] != 'HO') { ?>
                            <li class="submenu-item"><a href="terminate_contract.php" class="submenu-link">Request Terminate</a></li>
                        <?php } ?>
                        <?php if ($row['roles'] == 'Am-Creator' || $row['roles'] == 'Vpo-Checker') { ?>
                            <li class="submenu-item"><a href="modify_contract.php" class="submenu-link">Request Edit COL</a></li>
                        <?php } ?>
                        <?php if ($row['roles'] == 'Am-Creator') { ?>
                            <li class="submenu-item"><a href="add_notarized_col.php" class="submenu-link">Add Notarized COL</a></li>
                        <?php } ?>
                    </ul>
                </nav>
            <?php 
            }
        ?>

        </li> 
        <li class="nav-item">
            <a href="../../logout.php" class="nav-link" id="logout"><span>Logout</span>
            <i class='bx bx-log-in'></i>
        </a>
        </li> 
       <b style="font-weight:700; color:#333;"><?php echo strtoupper($_SESSION['user_email']); ?></b>
    </ul>
    
</nav>
<script>
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
        el.classList.add('show-submenu');
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
<?php
// Retrieve the user's email from the session
$user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);

// Query to get the user's roles, region, area, and mainzone from the database
$userQuery = "SELECT roles, region, area, mainzone FROM user_form WHERE email = '$user_email'";
$userResult = mysqli_query($conn, $userQuery);

// Check if the query returned any rows
if (mysqli_num_rows($userResult) > 0) {
    $userRow = mysqli_fetch_assoc($userResult);
    $userRole = $userRow['roles'];
}

?>

<?php if ($userRole != 'Auditor' && $userRole != 'Finance') : ?>

    <!-- <div class="dash-count">
    <div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                    <i class="fa-solid fa-money-check-dollar fa-2x"></i>
                    </div>
                    <div class="col-xs-9 text-right">
    <div class='huge'>
        <?php
        // Retrieve the user's email from the session
        $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);

        // Initialize the created count to zero
        $createdCount = 0;

        // Query to get the user's roles, region, and area from the database
        $userQuery = "SELECT roles, region, area, mainzone FROM user_form WHERE email = '$user_email'";
        $userResult = mysqli_query($conn, $userQuery);

        // Check if the query returned any rows
        if (mysqli_num_rows($userResult) > 0) {
            $userRow = mysqli_fetch_assoc($userResult);

            // Check if the user's role is 'Am-Creator'
            if ($userRow['roles'] == 'Am-Creator') {
                $userRegion = $userRow['region'];
                $userArea = $userRow['area'];
            
                // Query to get the count of created contracts based on the user's region and area
                $sql = "SELECT COUNT(*) AS rfp_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Ready' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea'";
                $result = $conn->query($sql);
        
                // Check if the first query returned any rows
                if ($result && $result->num_rows > 0) {
                    // Fetch the created contract count
                    $rfpCount = $result->fetch_assoc()['rfp_count'];
                } else {
                    $rfpCount = 0; // Default to 0 if no rows are returned
                }
            
            }
            elseif($userRow['roles'] == 'Rm-Reviewer') {
                $userRegion = $userRow['region'];
                $sql = "SELECT COUNT(*) AS rfp_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Ready' AND request_status != 'Terminated' AND region = '$userRegion'";
                $result = $conn->query($sql);

                // Check if the query returned any rows
                if ($result->num_rows > 0) {
                    // Fetch the created contract count
                    $rfpCount = $result->fetch_assoc()['rfp_count'];
                }else {
                    $rfpCount = 0; // Default to 0 if no rows are returned
                }

            }elseif($userRow['roles'] == 'Vpo-Checker' || $userRow['roles'] == 'Vpo-Reviewer' || $userRow['roles'] == 'Vpo-Approver') {
                $userMainzone = $userRow['mainzone'];
                $sql = "SELECT COUNT(*) AS rfp_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Ready' AND request_status != 'Terminated' AND mainzone = '$userMainzone'";
                $result = $conn->query($sql);

                // Check if the query returned any rows
                if ($result->num_rows > 0) {
                    // Fetch the created contract count
                    $rfpCount = $result->fetch_assoc()['rfp_count'];
                }

            }elseif($userRow['roles'] == 'HO') {
                $sql = "SELECT COUNT(*) AS rfp_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Ready' AND request_status != 'Terminated'";
                $result = $conn->query($sql);

                // Check if the query returned any rows
                if ($result->num_rows > 0) {
                    // Fetch the created contract count
                    $rfpCount = $result->fetch_assoc()['rfp_count'];
                }
            }
        }
        ?>
    </div>
    <div class="under-number">REQUEST FOR PAYMENT (AM)</div>
</div>

                </div>
            </div>
            <?php if($row['roles'] == 'HO'){ ?>
            <a href="rfp_page.php" >
                <div class="panel-footer">
                    <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $rfpCount; ?></i>RFP</span>
                    <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                    <div class="clearfix"></div>
                </div>
            </a>
            <?php }else{?>
            <a href="rfp_page.php">
                <div class="panel-footer">
                    <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $rfpCount; ?></i>RFP</span>
                    <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                    <div class="clearfix"></div>
                </div>
            </a>
            <?php } ?>
        </div>
    </div> -->


<div class="dash-count">
    <div class="row">
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa-solid fa-file-circle-check fa-2x" style="color: #d70c0c;"></i>
                    </div>
                    <div class="col-xs-9 text-right">
    <div class='huge'>
        <?php
        // Retrieve the user's email from the session
        $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);

        // Initialize the created count to zero
        $createdCount = 0;

        // Query to get the user's roles, region, and area from the database
        $userQuery = "SELECT roles, region, area, mainzone FROM user_form WHERE email = '$user_email'";
        $userResult = mysqli_query($conn, $userQuery);

        // Check if the query returned any rows
        if (mysqli_num_rows($userResult) > 0) {
            $userRow = mysqli_fetch_assoc($userResult);

            // Check if the user's role is 'Am-Creator'
            if ($userRow['roles'] == 'Am-Creator') {
                $userRegion = $userRow['region'];
                $userArea = $userRow['area'];
            
                // Query to get the count of created contracts based on the user's region and area
                $sql = "SELECT COUNT(*) AS created_count FROM create_contract WHERE request_status = 'Created' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea'";
                $result = $conn->query($sql);
            
                $sql2 = "SELECT COUNT(*) AS paymentDueDate_pending_count FROM transactional WHERE region = '$userRegion' AND area = '$userArea' AND dueDate_request_status = 'Rm-Pending' AND dueDate_request_type = 'payment_due_date'";
                $result2 = $conn->query($sql2);

                $sql3 = "SELECT COUNT(*) AS mobileNumber_pending_count FROM transactional WHERE region = '$userRegion' AND area = '$userArea' AND mobile_request_status = 'Rm-Pending' AND mobile_request_type = 'change_mobileNumber'";
                $result3 = $conn->query($sql3);

                $sql4 = "SELECT COUNT(*) AS cancelTransaction_pending_count FROM transactional WHERE region = '$userRegion' AND area = '$userArea' AND cancel_request_status = 'Rm-Pending' AND cancel_request_type = 'cancel_payment_transaction'";
                $result4 = $conn->query($sql4);

                $sql5 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE request_status = 'Created' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'PAYMENT SOLUTION'";
                $result5 = $conn->query($sql5);

                $sql6 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE request_status = 'Created' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'PDC'";
                $result6 = $conn->query($sql6);

                $sql7 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE request_status = 'Created' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'RTA'";
                $result7 = $conn->query($sql7);

                $sql8 = "SELECT COUNT(*) AS not_reviewed_count FROM create_contract WHERE (rfp_status = '' OR rfp_status IS NULL) AND request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea'";
                $result8 = $conn->query($sql8);

                $sql9 = "SELECT COUNT(*) AS reviewed_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea'";
                $result9 = $conn->query($sql9);

                $sql10 = "SELECT COUNT(*) AS payment_solution_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'PAYMENT SOLUTION'";
                $result10 = $conn->query($sql10);

                $sql11 = "SELECT COUNT(*) AS pdc_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'PDC'";
                $result11 = $conn->query($sql11);
                
                $sql12 = "SELECT COUNT(*) AS rta_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'RTA'";
                $result12 = $conn->query($sql12);
            
                // Check if the first query returned any rows
                if ($result && $result->num_rows > 0) {
                    // Fetch the created contract count
                    $createdCount = $result->fetch_assoc()['created_count'];
                } else {
                    $createdCount = 0; // Default to 0 if no rows are returned
                }
            
                // Check if the second query returned any rows
                if ($result2 && $result2->num_rows > 0) {
                    // Fetch the pending contract count
                    $paymentDueDateRequestCount = $result2->fetch_assoc()['paymentDueDate_pending_count'];
                } else {
                    $paymentDueDateRequestCount = 0; // Default to 0 if no rows are returned
                }

                if ($result3 && $result3->num_rows > 0) {
                    // Fetch the pending contract count
                    $mobileNumberRequestCount = $result3->fetch_assoc()['mobileNumber_pending_count'];
                } else {
                    $mobileNumberRequestCount = 0; // Default to 0 if no rows are returned
                }

                if ($result4 && $result4->num_rows > 0) {
                    // Fetch the pending contract count
                    $cancelTransctionRequestCount = $result4->fetch_assoc()['cancelTransaction_pending_count'];
                } else {
                    $cancelTransctionRequestCount = 0; // Default to 0 if no rows are returned
                }

                if ($result5 && $result5->num_rows > 0) {
                    // Fetch the pending contract count
                    $paymentSolutionCount = $result5->fetch_assoc()['payment_solution_count'];
                } else {
                    $paymentSolutionCount = 0; // Default to 0 if no rows are returned
                }

                if ($result6 && $result6->num_rows > 0) {
                    // Fetch the pending contract count
                    $pdcCount = $result6->fetch_assoc()['pdc_count'];
                } else {
                    $pdcCount = 0; // Default to 0 if no rows are returned
                }

                if ($result7 && $result7->num_rows > 0) {
                    // Fetch the pending contract count
                    $rtaCount = $result7->fetch_assoc()['rta_count'];
                } else {
                    $rtaCount = 0; // Default to 0 if no rows are returned
                }

                if ($result8 && $result8->num_rows > 0) {
                    // Fetch the pending contract count
                    $notReviewed = $result8->fetch_assoc()['not_reviewed_count'];
                } else {
                    $notReviewed = 0; // Default to 0 if no rows are returned
                }

                if ($result9 && $result9->num_rows > 0) {
                    // Fetch the pending contract count
                    $rfpReviewed = $result9->fetch_assoc()['reviewed_count'];
                } else {
                    $rfpReviewed = 0; // Default to 0 if no rows are returned
                }

                if ($result10 && $result10->num_rows > 0) {
                    // Fetch the pending contract count
                    $paymentSolutionCount_rfp = $result10->fetch_assoc()['payment_solution_count_rfp'];
                } else {
                    $paymentSolutionCount_rfp = 0; // Default to 0 if no rows are returned
                }

                if ($result11 && $result11->num_rows > 0) {
                    // Fetch the pending contract count
                    $pdcCount_rfp = $result11->fetch_assoc()['pdc_count_rfp'];
                } else {
                    $pdcCount_rfp = 0; // Default to 0 if no rows are returned
                }

                if ($result12 && $result12->num_rows > 0) {
                    // Fetch the pending contract count
                    $rtaCount_rfp = $result12->fetch_assoc()['rta_count_rfp'];
                } else {
                    $rtaCount_rfp = 0; // Default to 0 if no rows are returned
                }
            }
            elseif($userRow['roles'] == 'Rm-Reviewer') {
                $userRegion = $userRow['region'];
                $sql = "SELECT COUNT(*) AS created_count FROM create_contract WHERE request_status = 'Created' AND request_status != 'Terminated' AND region = '$userRegion'";
                $result = $conn->query($sql);

                $sql2 = "SELECT COUNT(*) AS paymentDueDate_pending_count FROM transactional WHERE region = '$userRegion' AND dueDate_request_status = 'Rm-Pending' AND dueDate_request_type = 'payment_due_date'";
                $result2 = $conn->query($sql2);

                $sql3 = "SELECT COUNT(*) AS mobileNumber_pending_count FROM transactional WHERE region = '$userRegion' AND mobile_request_status = 'Rm-Pending' AND mobile_request_type = 'change_mobileNumber'";
                $result3 = $conn->query($sql3);

                $sql4 = "SELECT COUNT(*) AS cancelTransaction_pending_count FROM transactional WHERE region = '$userRegion' AND cancel_request_status = 'Rm-Pending' AND cancel_request_type = 'cancel_payment_transaction'";
                $result4 = $conn->query($sql4);

                $sql5 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE request_status = 'Created' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'PAYMENT SOLUTION'";
                $result5 = $conn->query($sql5);

                $sql6 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE request_status = 'Created' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'PDC'";
                $result6 = $conn->query($sql6);

                $sql7 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE request_status = 'Created' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'RTA'";
                $result7 = $conn->query($sql7);

                $sql8 = "SELECT COUNT(*) AS not_reviewed_count FROM create_contract WHERE (rfp_status = '' OR rfp_status IS NULL) AND request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion'";
                $result8 = $conn->query($sql8);

                $sql9 = "SELECT COUNT(*) AS reviewed_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status != 'Terminated' AND region = '$userRegion'";
                $result9 = $conn->query($sql9);

                $sql10 = "SELECT COUNT(*) AS payment_solution_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'PAYMENT SOLUTION'";
                $result10 = $conn->query($sql10);

                $sql11 = "SELECT COUNT(*) AS pdc_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'PDC'";
                $result11 = $conn->query($sql11);
                
                $sql12 = "SELECT COUNT(*) AS rta_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'RTA'";
                $result12 = $conn->query($sql12);

                // Check if the query returned any rows
                if ($result->num_rows > 0) {
                    // Fetch the created contract count
                    $createdCount = $result->fetch_assoc()['created_count'];
                }

                if ($result2 && $result2->num_rows > 0) {
                    // Fetch the pending contract count
                    $paymentDueDateRequestCount = $result2->fetch_assoc()['paymentDueDate_pending_count'];
                } else {
                    $paymentDueDateRequestCount = 0; // Default to 0 if no rows are returned
                }

                if ($result3 && $result3->num_rows > 0) {
                    // Fetch the pending contract count
                    $mobileNumberRequestCount = $result3->fetch_assoc()['mobileNumber_pending_count'];
                } else {
                    $mobileNumberRequestCount = 0; // Default to 0 if no rows are returned
                }

                if ($result4 && $result4->num_rows > 0) {
                    // Fetch the pending contract count
                    $cancelTransctionRequestCount = $result4->fetch_assoc()['cancelTransaction_pending_count'];
                } else {
                    $cancelTransctionRequestCount = 0; // Default to 0 if no rows are returned
                }

                if ($result5 && $result5->num_rows > 0) {
                    // Fetch the pending contract count
                    $paymentSolutionCount = $result5->fetch_assoc()['payment_solution_count'];
                } else {
                    $paymentSolutionCount = 0; // Default to 0 if no rows are returned
                }

                if ($result6 && $result6->num_rows > 0) {
                    // Fetch the pending contract count
                    $pdcCount = $result6->fetch_assoc()['pdc_count'];
                } else {
                    $pdcCount = 0; // Default to 0 if no rows are returned
                }

                if ($result7 && $result7->num_rows > 0) {
                    // Fetch the pending contract count
                    $rtaCount = $result7->fetch_assoc()['rta_count'];
                } else {
                    $rtaCount = 0; // Default to 0 if no rows are returned
                }

                if ($result8 && $result8->num_rows > 0) {
                    // Fetch the pending contract count
                    $notReviewed = $result8->fetch_assoc()['not_reviewed_count'];
                } else {
                    $notReviewed = 0; // Default to 0 if no rows are returned
                }

                if ($result9 && $result9->num_rows > 0) {
                    // Fetch the pending contract count
                    $rfpReviewed = $result9->fetch_assoc()['reviewed_count'];
                } else {
                    $rfpReviewed = 0; // Default to 0 if no rows are returned 
                }

                if ($result10 && $result10->num_rows > 0) {
                    // Fetch the pending contract count
                    $paymentSolutionCount_rfp = $result10->fetch_assoc()['payment_solution_count_rfp'];
                } else {
                    $paymentSolutionCount_rfp = 0; // Default to 0 if no rows are returned
                }

                if ($result11 && $result11->num_rows > 0) {
                    // Fetch the pending contract count
                    $pdcCount_rfp = $result11->fetch_assoc()['pdc_count_rfp'];
                } else {
                    $pdcCount_rfp = 0; // Default to 0 if no rows are returned
                }

                if ($result12 && $result12->num_rows > 0) {
                    // Fetch the pending contract count
                    $rtaCount_rfp = $result12->fetch_assoc()['rta_count_rfp'];
                } else {
                    $rtaCount_rfp = 0; // Default to 0 if no rows are returned
                }

            }elseif($userRow['roles'] == 'Vpo-Checker' || $userRow['roles'] == 'Vpo-Reviewer' || $userRow['roles'] == 'Vpo-Approver') {
                $userMainzone = $userRow['mainzone'];
                $sql = "SELECT COUNT(*) AS created_count FROM create_contract WHERE request_status = 'Created' AND request_status != 'Terminated' AND mainzone = '$userMainzone'";
                $result = $conn->query($sql);

                $sql2 = "SELECT COUNT(*) AS paymentDueDate_pending_count FROM transactional WHERE mainzone = '$userMainzone' AND dueDate_request_status = 'Rm-Pending' AND dueDate_request_type = 'payment_due_date'";
                $result2 = $conn->query($sql2);

                $sql3 = "SELECT COUNT(*) AS mobileNumber_pending_count FROM transactional WHERE mainzone = '$userMainzone' AND mobile_request_status = 'Rm-Pending' AND mobile_request_type = 'change_mobileNumber'";
                $result3 = $conn->query($sql3);

                $sql4 = "SELECT COUNT(*) AS cancelTransaction_pending_count FROM transactional WHERE mainzone = '$userMainzone' AND cancel_request_status = 'Rm-Pending' AND cancel_request_type = 'cancel_payment_transaction'";
                $result4 = $conn->query($sql4);

                $sql5 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE request_status = 'Created' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'PAYMENT SOLUTION'";
                $result5 = $conn->query($sql5);

                $sql6 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE request_status = 'Created' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'PDC'";
                $result6 = $conn->query($sql6);

                $sql7 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE request_status = 'Created' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'RTA'";
                $result7 = $conn->query($sql7);

                $sql8 = "SELECT COUNT(*) AS not_reviewed_count FROM create_contract WHERE (rfp_status = '' OR rfp_status IS NULL) AND request_status = 'Prepared' AND request_status != 'Terminated' AND mainzone = '$userMainzone'";
                $result8 = $conn->query($sql8);

                $sql9 = "SELECT COUNT(*) AS reviewed_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status != 'Terminated' AND mainzone = '$userMainzone'";
                $result9 = $conn->query($sql9);

                $sql10 = "SELECT COUNT(*) AS payment_solution_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'PAYMENT SOLUTION'";
                $result10 = $conn->query($sql10);

                $sql11 = "SELECT COUNT(*) AS pdc_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'PDC'";
                $result11 = $conn->query($sql11);
                
                $sql12 = "SELECT COUNT(*) AS rta_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'RTA'";
                $result12 = $conn->query($sql12);

                // Check if the query returned any rows
                if ($result->num_rows > 0) {
                    // Fetch the created contract count
                    $createdCount = $result->fetch_assoc()['created_count'];
                }

                if ($result2 && $result2->num_rows > 0) {
                    // Fetch the pending contract count
                    $paymentDueDateRequestCount = $result2->fetch_assoc()['paymentDueDate_pending_count'];
                } else {
                    $paymentDueDateRequestCount = 0; // Default to 0 if no rows are returned
                }

                if ($result3 && $result3->num_rows > 0) {
                    // Fetch the pending contract count
                    $mobileNumberRequestCount = $result3->fetch_assoc()['mobileNumber_pending_count'];
                } else {
                    $mobileNumberRequestCount = 0; // Default to 0 if no rows are returned
                }

                if ($result4 && $result4->num_rows > 0) {
                    // Fetch the pending contract count
                    $cancelTransctionRequestCount = $result4->fetch_assoc()['cancelTransaction_pending_count'];
                } else {
                    $cancelTransctionRequestCount = 0; // Default to 0 if no rows are returned
                }

                if ($result5 && $result5->num_rows > 0) {
                    // Fetch the pending contract count
                    $paymentSolutionCount = $result5->fetch_assoc()['payment_solution_count'];
                } else {
                    $paymentSolutionCount = 0; // Default to 0 if no rows are returned
                }

                if ($result6 && $result6->num_rows > 0) {
                    // Fetch the pending contract count
                    $pdcCount = $result6->fetch_assoc()['pdc_count'];
                } else {
                    $pdcCount = 0; // Default to 0 if no rows are returned
                }

                if ($result7 && $result7->num_rows > 0) {
                    // Fetch the pending contract count
                    $rtaCount = $result7->fetch_assoc()['rta_count'];
                } else {
                    $rtaCount = 0; // Default to 0 if no rows are returned
                }

                if ($result8 && $result8->num_rows > 0) {
                    // Fetch the pending contract count
                    $notReviewed = $result8->fetch_assoc()['not_reviewed_count'];
                } else {
                    $notReviewed = 0; // Default to 0 if no rows are returned
                }

                if ($result9 && $result9->num_rows > 0) {
                    // Fetch the pending contract count
                    $rfpReviewed = $result9->fetch_assoc()['reviewed_count'];
                } else {
                    $rfpReviewed = 0; // Default to 0 if no rows are returned
                }

                if ($result10 && $result10->num_rows > 0) {
                    // Fetch the pending contract count
                    $paymentSolutionCount_rfp = $result10->fetch_assoc()['payment_solution_count_rfp'];
                } else {
                    $paymentSolutionCount_rfp = 0; // Default to 0 if no rows are returned
                }

                if ($result11 && $result11->num_rows > 0) {
                    // Fetch the pending contract count
                    $pdcCount_rfp = $result11->fetch_assoc()['pdc_count_rfp'];
                } else {
                    $pdcCount_rfp = 0; // Default to 0 if no rows are returned
                }

                if ($result12 && $result12->num_rows > 0) {
                    // Fetch the pending contract count
                    $rtaCount_rfp = $result12->fetch_assoc()['rta_count_rfp'];
                } else {
                    $rtaCount_rfp = 0; // Default to 0 if no rows are returned
                }

            }elseif($userRow['roles'] == 'HO') {
                $sql = "SELECT COUNT(*) AS created_count FROM create_contract WHERE request_status = 'Created' AND request_status != 'Terminated'";
                $result = $conn->query($sql);

                $sql2 = "SELECT COUNT(*) AS paymentDueDate_pending_count FROM transactional WHERE dueDate_request_status = 'Rm-Pending' AND dueDate_request_type = 'payment_due_date'";
                $result2 = $conn->query($sql2);

                $sql3 = "SELECT COUNT(*) AS mobileNumber_pending_count FROM transactional WHERE mobile_request_status = 'Rm-Pending' AND mobile_request_type = 'change_mobileNumber'";
                $result3 = $conn->query($sql3);

                $sql4 = "SELECT COUNT(*) AS cancelTransaction_pending_count FROM transactional WHERE cancel_request_status = 'Rm-Pending' AND cancel_request_type = 'cancel_payment_transaction'";
                $result4 = $conn->query($sql4);

                $sql5 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE request_status = 'Created' AND request_status != 'Terminated' AND mode_of_payment = 'PAYMENT SOLUTION'";
                $result5 = $conn->query($sql5);

                $sql6 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE request_status = 'Created' AND request_status != 'Terminated' AND mode_of_payment = 'PDC'";
                $result6 = $conn->query($sql6);
                
                $sql7 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE request_status = 'Created' AND request_status != 'Terminated' AND mode_of_payment = 'RTA'";
                $result7 = $conn->query($sql7);

                $sql8 = "SELECT COUNT(*) AS not_reviewed_count FROM create_contract WHERE (rfp_status = '' OR rfp_status IS NULL) AND request_status = 'Prepared' AND request_status != 'Terminated'";
                $result8 = $conn->query($sql8);

                $sql9 = "SELECT COUNT(*) AS reviewed_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status != 'Terminated'";
                $result9 = $conn->query($sql9);

                $sql10 = "SELECT COUNT(*) AS payment_solution_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND mode_of_payment = 'PAYMENT SOLUTION'";
                $result10 = $conn->query($sql10);

                $sql11 = "SELECT COUNT(*) AS pdc_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND mode_of_payment = 'PDC'";
                $result11 = $conn->query($sql11);
                
                $sql12 = "SELECT COUNT(*) AS rta_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND mode_of_payment = 'RTA'";
                $result12 = $conn->query($sql12);

                // Check if the query returned any rows
                if ($result->num_rows > 0) {
                    // Fetch the created contract count
                    $createdCount = $result->fetch_assoc()['created_count'];
                }

                if ($result2 && $result2->num_rows > 0) {
                    // Fetch the pending contract count
                    $paymentDueDateRequestCount = $result2->fetch_assoc()['paymentDueDate_pending_count'];
                } else {
                    $paymentDueDateRequestCount = 0; // Default to 0 if no rows are returned
                }

                if ($result3 && $result3->num_rows > 0) {
                    // Fetch the pending contract count
                    $mobileNumberRequestCount = $result3->fetch_assoc()['mobileNumber_pending_count'];
                } else {
                    $mobileNumberRequestCount = 0; // Default to 0 if no rows are returned
                }

                if ($result4 && $result4->num_rows > 0) {
                    // Fetch the pending contract count
                    $cancelTransctionRequestCount = $result4->fetch_assoc()['cancelTransaction_pending_count'];
                } else {
                    $cancelTransctionRequestCount = 0; // Default to 0 if no rows are returned
                }

                if ($result5 && $result5->num_rows > 0) {
                    // Fetch the pending contract count
                    $paymentSolutionCount = $result5->fetch_assoc()['payment_solution_count'];
                } else {
                    $paymentSolutionCount = 0; // Default to 0 if no rows are returned
                }

                if ($result6 && $result6->num_rows > 0) {
                    // Fetch the pending contract count
                    $pdcCount = $result6->fetch_assoc()['pdc_count'];
                } else {
                    $pdcCount = 0; // Default to 0 if no rows are returned
                }

                if ($result7 && $result7->num_rows > 0) {
                    // Fetch the pending contract count
                    $rtaCount = $result7->fetch_assoc()['rta_count'];
                } else {
                    $rtaCount = 0; // Default to 0 if no rows are returned
                }

                if ($result8 && $result8->num_rows > 0) {
                    // Fetch the pending contract count
                    $notReviewed = $result8->fetch_assoc()['not_reviewed_count'];
                } else {
                    $notReviewed = 0; // Default to 0 if no rows are returned
                }

                if ($result9 && $result9->num_rows > 0) {
                    // Fetch the pending contract count
                    $rfpReviewed = $result9->fetch_assoc()['reviewed_count'];
                } else {
                    $rfpReviewed = 0; // Default to 0 if no rows are returned
                }

                if ($result10 && $result10->num_rows > 0) {
                    // Fetch the pending contract count
                    $paymentSolutionCount_rfp = $result10->fetch_assoc()['payment_solution_count_rfp'];
                } else {
                    $paymentSolutionCount_rfp = 0; // Default to 0 if no rows are returned
                }

                if ($result11 && $result11->num_rows > 0) {
                    // Fetch the pending contract count
                    $pdcCount_rfp = $result11->fetch_assoc()['pdc_count_rfp'];
                } else {
                    $pdcCount_rfp = 0; // Default to 0 if no rows are returned
                }

                if ($result12 && $result12->num_rows > 0) {
                    // Fetch the pending contract count
                    $rtaCount_rfp = $result12->fetch_assoc()['rta_count_rfp'];
                } else {
                    $rtaCount_rfp = 0; // Default to 0 if no rows are returned
                }
            }
        }
        ?>
    </div>
    <div class="under-number">Created Contract (AM)</div>
</div>

                </div>
            </div>
            <?php if($row['roles'] == 'HO'){ ?>
                <div class="panel-footer">
                    <span class="pull-left blue" style="font-weight: bold; font-size:16px;">RFP</i></span><br>
                    <a href="review_contract.php">
                        <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $paymentSolutionCount_rfp; ?></i>PAYMENT SOLUTION</span><br>
                        <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $pdcCount_rfp; ?></i>PDC</span><br>
                        <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $rtaCount_rfp; ?></i>RTA</span>
                        <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <hr class="submenu-seperator" />
                        <span class="pull-left blue" style="font-weight: bold; font-size:16px;">CONTRACT</i></span><br>
                    <a href="for_review_col.php" >
                        <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $notReviewed; ?></i>FOR REVIEW BY RM</span>
                        <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <a href="reviewed_col.php" >
                        <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $rfpReviewed; ?></i>REVIEWED CONTRACT BY RM</span>
                        <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <hr class="submenu-seperator" />
                    <a href="created_contract.php" >
                        <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $createdCount; ?></i>CREATED CONTRACT</span>
                        <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                </div>
            <?php }else{?>
                <div class="panel-footer">
                    <!-- <span class="pull-left blue">AREA MANAGER <i>(Click here)</i></span><br> -->
                    <span class="pull-left blue" style="font-weight: bold; font-size:16px;">RFP</i></span><br>
                    <a href="review_contract.php">
                        <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $paymentSolutionCount_rfp; ?></i>PAYMENT SOLUTION</span><br>
                        <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $pdcCount_rfp; ?></i>PDC</span><br>
                        <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $rtaCount_rfp; ?></i>RTA</span>
                        <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <hr class="submenu-seperator" />
                        <span class="pull-left blue" style="font-weight: bold; font-size:16px;">CONTRACT</i></span><br>
                        <a href="for_review_col.php" >
                            <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $notReviewed; ?></i>FOR REVIEW BY RM</span>
                            <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </a>
                        <a href="reviewed_col.php" >
                            <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $rfpReviewed; ?></i>REVIEWED CONTRACT BY RM</span>
                            <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </a>
                        <hr class="submenu-seperator" />
                        <a href="created_contract.php">
                            <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $createdCount; ?></i>CREATED CONTRACT</span>
                            <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </a>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- ********************************************************************************************************* -->

    <div class="col-lg-3 col-md-6">
        <div class="panel panel-green">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                       <i class="fa-solid fa-hourglass-half fa-2x" style="color: #d70c0c;"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                     <div class='huge'>
                        <?php
                        // Retrieve the user's email from the session
                        $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);

                        // Initialize the created count to zero
                        $preparedCount = 0;

                        // Query to get the user's roles, region, and area from the database
                        $userQuery = "SELECT roles, region, area, mainzone FROM user_form WHERE email = '$user_email'";
                        $userResult = mysqli_query($conn, $userQuery);

                        // Check if the query returned any rows
                        if (mysqli_num_rows($userResult) > 0) {
                            $userRow = mysqli_fetch_assoc($userResult);

                            // Check if the user's role is 'Am-Creator'
                            if ($userRow['roles'] == 'Am-Creator') {
                                $userRegion = $userRow['region'];
                                $userArea = $userRow['area'];

                                // Query to get the count of created contracts based on the user's region and area
                                $sql = "SELECT COUNT(*) AS prepared_count FROM create_contract WHERE request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea'";
                                $result = $conn->query($sql);

                                $sql2 = "SELECT COUNT(*) AS paymentDueDate_pending_count, dueDate_request_status FROM transactional WHERE region = '$userRegion' AND area = '$userArea' AND dueDate_request_status = 'Rm-Pending' AND dueDate_request_type = 'payment_due_date'";
                                $result2 = $conn->query($sql2);

                                $sql3 = "SELECT COUNT(*) AS mobileNumber_pending_count, mobile_request_status FROM transactional WHERE region = '$userRegion' AND area = '$userArea' AND mobile_request_status = 'Rm-Pending' AND mobile_request_type = 'change_mobileNumber'";
                                $result3 = $conn->query($sql3);

                                $sql4 = "SELECT COUNT(*) AS cancelTransaction_pending_count, cancel_request_status FROM transactional WHERE region = '$userRegion' AND area = '$userArea' AND cancel_request_status = 'Rm-Pending' AND cancel_request_type = 'cancel_payment_transaction'";
                                $result4 = $conn->query($sql4);

                                $sql5 = "
                                    SELECT 
                                        contract_number, 
                                        COUNT(DISTINCT contract_number) AS terminate_request_count 
                                    FROM 
                                        transactional 
                                    WHERE 
                                        region = '$userRegion' 
                                        AND area = '$userArea' 
                                        AND status = 'Requested' 
                                    GROUP BY 
                                        contract_number
                                ";

                                $result5 = $conn->query($sql5);

                                $sql6 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result6 = $conn->query($sql6);

                                $sql7 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'PDC'";
                                $result7 = $conn->query($sql7);

                                $sql8 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'RTA'";
                                $result8 = $conn->query($sql8);

                                $sql9 = "SELECT COUNT(*) AS payment_solution_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result9 = $conn->query($sql9);

                                $sql10 = "SELECT COUNT(*) AS pdc_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'PDC'";
                                $result10 = $conn->query($sql10);

                                $sql11 = "SELECT COUNT(*) AS rta_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'RTA'";
                                $result11 = $conn->query($sql11);


                                // Initialize counts to avoid undefined variable errors
                                $preparedCount = 0;
                                $paymentDueDateRequestCount = 0;
                                $mobileNumberRequestCount = 0;
                                $cancelTransactionRequestCount = 0;
                                $terminateRequestCount = 0;
                                $paymentSolutionCount = 0;
                                $pdcCount = 0;
                                $rtaCount = 0;
                                $paymentSolutionCount_rfp = 0;
                                $pdcCount_rfp = 0;
                                $rtaCount_rfp = 0;
                                $modifyStatus = null;

                                // Check if each query returned any rows and fetch the counts
                                if ($result && $result->num_rows > 0) {
                                    $preparedCount = $result->fetch_assoc()['prepared_count'];
                                }

                                if ($result2 && $result2->num_rows > 0) {
                                    $row2 = $result2->fetch_assoc();
                                    $paymentDueDateRequestCount = $row2['paymentDueDate_pending_count'];
                                    $modifyStatus = $row2['dueDate_request_status'];
                                }

                                if ($result3 && $result3->num_rows > 0) {
                                    $row3 = $result3->fetch_assoc();
                                    $mobileNumberRequestCount = $row3['mobileNumber_pending_count'];
                                    $modifyStatus = $row3['mobile_request_status'];
                                }

                                if ($result4 && $result4->num_rows > 0) {
                                    $row4 = $result4->fetch_assoc();
                                    $cancelTransactionRequestCount = $row4['cancelTransaction_pending_count'];
                                    $modifyStatus = $row4['cancel_request_status'];
                                }

                                if ($result5 && $result5->num_rows > 0) {
                                    $row5 = $result5->fetch_assoc();
                                    $terminateRequestCount = $row5['terminate_request_count'];
                                }

                                if ($result6 && $result6->num_rows > 0) {
                                    $row6 = $result6->fetch_assoc();
                                    $paymentSolutionCount = $row6['payment_solution_count'];
                                }

                                if ($result7 && $result7->num_rows > 0) {
                                    $row7 = $result7->fetch_assoc();
                                    $pdcCount = $row7['pdc_count'];
                                }

                                if ($result8 && $result8->num_rows > 0) {
                                    $row8 = $result8->fetch_assoc();
                                    $rtaCount = $row8['rta_count'];
                                }

                                if ($result9 && $result9->num_rows > 0) {
                                    $row9 = $result9->fetch_assoc();
                                    $paymentSolutionCount_rfp = $row9['payment_solution_count_rfp'];
                                }

                                if ($result10 && $result10->num_rows > 0) {
                                    $row10 = $result10->fetch_assoc();
                                    $pdcCount_rfp = $row10['pdc_count_rfp'];
                                }

                                if ($result11 && $result11->num_rows > 0) {
                                    $row11 = $result11->fetch_assoc();
                                    $rtaCount_rfp = $row11['rta_count_rfp'];
                                }
                
                            }elseif($userRow['roles'] == 'Rm-Reviewer') {
                                $userRegion = $userRow['region'];
                                $sql = "SELECT COUNT(*) AS prepared_count FROM create_contract WHERE request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion'";
                                $result = $conn->query($sql);

                                $sql2 = "SELECT COUNT(*) AS paymentDueDate_pending_count, dueDate_request_status FROM transactional WHERE region = '$userRegion' AND dueDate_request_status = 'Rm-Pending' AND dueDate_request_type = 'payment_due_date'";
                                $result2 = $conn->query($sql2);

                                $sql3 = "SELECT COUNT(*) AS mobileNumber_pending_count, mobile_request_status FROM transactional WHERE region = '$userRegion' AND mobile_request_status = 'Rm-Pending' AND mobile_request_type = 'change_mobileNumber'";
                                $result3 = $conn->query($sql3);

                                $sql4 = "SELECT COUNT(*) AS cancelTransaction_pending_count, cancel_request_status FROM transactional WHERE region = '$userRegion' AND cancel_request_status = 'Rm-Pending' AND cancel_request_type = 'cancel_payment_transaction'";
                                $result4 = $conn->query($sql4);

                                $sql5 = "
                                        SELECT 
                                            contract_number, 
                                            COUNT(DISTINCT contract_number) AS terminate_request_count 
                                        FROM 
                                            transactional 
                                        WHERE 
                                            region = '$userRegion' 
                                            AND status = 'Requested' 
                                        GROUP BY 
                                            contract_number
                                    ";

                                $result5 = $conn->query($sql5);

                                $sql6 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result6 = $conn->query($sql6);

                                $sql7 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'PDC'";
                                $result7 = $conn->query($sql7);

                                $sql8 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'RTA'";
                                $result8 = $conn->query($sql8);

                                $sql9 = "SELECT COUNT(*) AS payment_solution_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result9 = $conn->query($sql9);

                                $sql10 = "SELECT COUNT(*) AS pdc_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'PDC'";
                                $result10 = $conn->query($sql10);

                                $sql11 = "SELECT COUNT(*) AS rta_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'RTA'";
                                $result11 = $conn->query($sql11);

                                // Initialize counts to avoid undefined variable errors
                                $preparedCount = 0;
                                $paymentDueDateRequestCount = 0;
                                $mobileNumberRequestCount = 0;
                                $cancelTransactionRequestCount = 0;
                                $terminateRequestCount = 0;
                                $paymentSolutionCount = 0;
                                $pdcCount = 0;
                                $rtaCount = 0;
                                $paymentSolutionCount_rfp = 0;
                                $pdcCount_rfp = 0;
                                $rtaCount_rfp = 0;
                                $modifyStatus = null;

                                // Check if each query returned any rows and fetch the counts
                                if ($result && $result->num_rows > 0) {
                                    $preparedCount = $result->fetch_assoc()['prepared_count'];
                                }

                                if ($result2 && $result2->num_rows > 0) {
                                    $row2 = $result2->fetch_assoc();
                                    $paymentDueDateRequestCount = $row2['paymentDueDate_pending_count'];
                                    $modifyStatus = $row2['dueDate_request_status'];
                                }

                                if ($result3 && $result3->num_rows > 0) {
                                    $row3 = $result3->fetch_assoc();
                                    $mobileNumberRequestCount = $row3['mobileNumber_pending_count'];
                                    $modifyStatus = $row3['mobile_request_status'];
                                }

                                if ($result4 && $result4->num_rows > 0) {
                                    $row4 = $result4->fetch_assoc();
                                    $cancelTransactionRequestCount = $row4['cancelTransaction_pending_count'];
                                    $modifyStatus = $row4['cancel_request_status'];
                                }

                                if ($result5 && $result5->num_rows > 0) {
                                    $row5 = $result5->fetch_assoc();
                                    $terminateRequestCount = $row5['terminate_request_count'];
                                }

                                if ($result6 && $result6->num_rows > 0) {
                                    $row6 = $result6->fetch_assoc();
                                    $paymentSolutionCount = $row6['payment_solution_count'];
                                }

                                if ($result7 && $result7->num_rows > 0) {
                                    $row7 = $result7->fetch_assoc();
                                    $pdcCount = $row7['pdc_count'];
                                }

                                if ($result8 && $result8->num_rows > 0) {
                                    $row8 = $result8->fetch_assoc();
                                    $rtaCount = $row8['rta_count'];
                                }

                                if ($result9 && $result9->num_rows > 0) {
                                    $row9 = $result9->fetch_assoc();
                                    $paymentSolutionCount_rfp = $row9['payment_solution_count_rfp'];
                                }

                                if ($result10 && $result10->num_rows > 0) {
                                    $row10 = $result10->fetch_assoc();
                                    $pdcCount_rfp = $row10['pdc_count_rfp'];
                                }

                                if ($result11 && $result11->num_rows > 0) {
                                    $row11 = $result11->fetch_assoc();
                                    $rtaCount_rfp = $row11['rta_count_rfp'];
                                }
                            }elseif($userRow['roles'] == 'Vpo-Checker' || $userRow['roles'] == 'Vpo-Reviewer' || $userRow['roles'] == 'Vpo-Approver') {
                                $userMainzone = $userRow['mainzone'];
                                $sql = "SELECT COUNT(*) AS prepared_count FROM create_contract WHERE request_status = 'Prepared' AND request_status != 'Terminated' AND mainzone = '$userMainzone'";
                                $result = $conn->query($sql);

                                $sql2 = "SELECT COUNT(*) AS paymentDueDate_pending_count, dueDate_request_status FROM transactional WHERE mainzone = '$userMainzone' AND dueDate_request_status = 'Rm-Pending' AND dueDate_request_type = 'payment_due_date'";
                                $result2 = $conn->query($sql2);

                                $sql3 = "SELECT COUNT(*) AS mobileNumber_pending_count, mobile_request_status FROM transactional WHERE mainzone = '$userMainzone' AND mobile_request_status = 'Rm-Pending' AND mobile_request_type = 'change_mobileNumber'";
                                $result3 = $conn->query($sql3);

                                $sql4 = "SELECT COUNT(*) AS cancelTransaction_pending_count, cancel_request_status FROM transactional WHERE mainzone = '$userMainzone' AND cancel_request_status = 'Rm-Pending' AND cancel_request_type = 'cancel_payment_transaction'";
                                $result4 = $conn->query($sql4);

                                $sql5 = "
                                        SELECT 
                                            contract_number, 
                                            COUNT(DISTINCT contract_number) AS terminate_request_count 
                                        FROM 
                                            transactional 
                                        WHERE 
                                            mainzone = '$userMainzone' 
                                            AND status = 'Requested' 
                                        GROUP BY 
                                            contract_number
                                    ";

                                $result5 = $conn->query($sql5);

                                $sql6 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE request_status = 'Prepared' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result6 = $conn->query($sql6);

                                $sql7 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE request_status = 'Prepared' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'PDC'";
                                $result7 = $conn->query($sql7);

                                $sql8 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE request_status = 'Prepared' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'RTA'";
                                $result8 = $conn->query($sql8);

                                $sql9 = "SELECT COUNT(*) AS payment_solution_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result9 = $conn->query($sql9);

                                $sql10 = "SELECT COUNT(*) AS pdc_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'PDC'";
                                $result10 = $conn->query($sql10);

                                $sql11 = "SELECT COUNT(*) AS rta_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'RTA'";
                                $result11 = $conn->query($sql11);

                                // Initialize counts to avoid undefined variable errors
                                $preparedCount = 0;
                                $paymentDueDateRequestCount = 0;
                                $mobileNumberRequestCount = 0;
                                $cancelTransactionRequestCount = 0;
                                $terminateRequestCount = 0;
                                $paymentSolutionCount = 0;
                                $pdcCount = 0;
                                $rtaCount = 0;
                                $paymentSolutionCount_rfp = 0;
                                $pdcCount_rfp = 0;
                                $rtaCount_rfp = 0;
                                $modifyStatus = null;

                                // Check if each query returned any rows and fetch the counts
                                if ($result && $result->num_rows > 0) {
                                    $preparedCount = $result->fetch_assoc()['prepared_count'];
                                }

                                if ($result2 && $result2->num_rows > 0) {
                                    $row2 = $result2->fetch_assoc();
                                    $paymentDueDateRequestCount = $row2['paymentDueDate_pending_count'];
                                    $modifyStatus = $row2['dueDate_request_status'];
                                }

                                if ($result3 && $result3->num_rows > 0) {
                                    $row3 = $result3->fetch_assoc();
                                    $mobileNumberRequestCount = $row3['mobileNumber_pending_count'];
                                    $modifyStatus = $row3['mobile_request_status'];
                                }

                                if ($result4 && $result4->num_rows > 0) {
                                    $row4 = $result4->fetch_assoc();
                                    $cancelTransactionRequestCount = $row4['cancelTransaction_pending_count'];
                                    $modifyStatus = $row4['cancel_request_status'];
                                }

                                if ($result5 && $result5->num_rows > 0) {
                                    $row5 = $result5->fetch_assoc();
                                    $terminateRequestCount = $row5['terminate_request_count'];
                                }

                                if ($result6 && $result6->num_rows > 0) {
                                    $row6 = $result6->fetch_assoc();
                                    $paymentSolutionCount = $row6['payment_solution_count'];
                                }

                                if ($result7 && $result7->num_rows > 0) {
                                    $row7 = $result7->fetch_assoc();
                                    $pdcCount = $row7['pdc_count'];
                                }

                                if ($result8 && $result8->num_rows > 0) {
                                    $row8 = $result8->fetch_assoc();
                                    $rtaCount = $row8['rta_count'];
                                }

                                if ($result9 && $result9->num_rows > 0) {
                                    $row9 = $result9->fetch_assoc();
                                    $paymentSolutionCount_rfp = $row9['payment_solution_count_rfp'];
                                }

                                if ($result10 && $result10->num_rows > 0) {
                                    $row10 = $result10->fetch_assoc();
                                    $pdcCount_rfp = $row10['pdc_count_rfp'];
                                }

                                if ($result11 && $result11->num_rows > 0) {
                                    $row11 = $result11->fetch_assoc();
                                    $rtaCount_rfp = $row11['rta_count_rfp'];
                                }
                            }elseif($userRow['roles'] == 'HO') {
                                $sql = "SELECT COUNT(*) AS prepared_count FROM create_contract WHERE request_status = 'Prepared' AND request_status != 'Terminated'";
                                $result = $conn->query($sql);
                                
                                $sql2 = "SELECT COUNT(*) AS paymentDueDate_pending_count, dueDate_request_status FROM transactional WHERE dueDate_request_status = 'Rm-Pending' AND dueDate_request_type = 'payment_due_date'";
                                $result2 = $conn->query($sql2);

                                $sql3 = "SELECT COUNT(*) AS mobileNumber_pending_count, mobile_request_status FROM transactional WHERE mobile_request_status = 'Rm-Pending' AND mobile_request_type = 'change_mobileNumber'";
                                $result3 = $conn->query($sql3);

                                $sql4 = "SELECT COUNT(*) AS cancelTransaction_pending_count, cancel_request_status FROM transactional WHERE cancel_request_status = 'Rm-Pending' AND cancel_request_type = 'cancel_payment_transaction'";
                                $result4 = $conn->query($sql4);

                                $sql5 = "
                                        SELECT 
                                            contract_number, 
                                            COUNT(DISTINCT contract_number) AS terminate_request_count 
                                        FROM 
                                            transactional 
                                        WHERE status = 'Requested' 
                                        GROUP BY 
                                            contract_number
                                    ";

                                $result5 = $conn->query($sql5);

                                $sql6 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE request_status = 'Prepared' AND request_status != 'Terminated' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result6 = $conn->query($sql6);

                                $sql7 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE request_status = 'Prepared' AND request_status != 'Terminated' AND mode_of_payment = 'PDC'";
                                $result7 = $conn->query($sql7);

                                $sql8 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE request_status = 'Prepared' AND request_status != 'Terminated' AND mode_of_payment = 'RTA'";
                                $result8 = $conn->query($sql8);

                                $sql9 = "SELECT COUNT(*) AS payment_solution_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result9 = $conn->query($sql9);

                                $sql10 = "SELECT COUNT(*) AS pdc_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND mode_of_payment = 'PDC'";
                                $result10 = $conn->query($sql10);

                                $sql11 = "SELECT COUNT(*) AS rta_count_rfp FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Prepared' AND request_status != 'Terminated' AND mode_of_payment = 'RTA'";
                                $result11 = $conn->query($sql11);

                                // Initialize counts to avoid undefined variable errors
                                $preparedCount = 0;
                                $paymentDueDateRequestCount = 0;
                                $mobileNumberRequestCount = 0;
                                $cancelTransactionRequestCount = 0;
                                $terminateRequestCount = 0;
                                $paymentSolutionCount = 0;
                                $pdcCount = 0;
                                $rtaCount = 0;
                                $paymentSolutionCount_rfp = 0;
                                $pdcCount_rfp = 0;
                                $rtaCount_rfp = 0;
                                $modifyStatus = null;

                                // Check if each query returned any rows and fetch the counts
                                if ($result && $result->num_rows > 0) {
                                    $preparedCount = $result->fetch_assoc()['prepared_count'];
                                }

                                if ($result2 && $result2->num_rows > 0) {
                                    $row2 = $result2->fetch_assoc();
                                    $paymentDueDateRequestCount = $row2['paymentDueDate_pending_count'];
                                    $modifyStatus = $row2['dueDate_request_status'];
                                }

                                if ($result3 && $result3->num_rows > 0) {
                                    $row3 = $result3->fetch_assoc();
                                    $mobileNumberRequestCount = $row3['mobileNumber_pending_count'];
                                    $modifyStatus = $row3['mobile_request_status'];
                                }

                                if ($result4 && $result4->num_rows > 0) {
                                    $row4 = $result4->fetch_assoc();
                                    $cancelTransactionRequestCount = $row4['cancelTransaction_pending_count'];
                                    $modifyStatus = $row4['cancel_request_status'];
                                }

                                if ($result5 && $result5->num_rows > 0) {
                                    $row5 = $result5->fetch_assoc();
                                    $terminateRequestCount = $row5['terminate_request_count'];
                                }

                                if ($result6 && $result6->num_rows > 0) {
                                    $row6 = $result6->fetch_assoc();
                                    $paymentSolutionCount = $row6['payment_solution_count'];
                                }

                                if ($result7 && $result7->num_rows > 0) {
                                    $row7 = $result7->fetch_assoc();
                                    $pdcCount = $row7['pdc_count'];
                                }

                                if ($result8 && $result8->num_rows > 0) {
                                    $row8 = $result8->fetch_assoc();
                                    $rtaCount = $row8['rta_count'];
                                }
                                if ($result9 && $result9->num_rows > 0) {
                                    $row9 = $result9->fetch_assoc();
                                    $paymentSolutionCount_rfp = $row9['payment_solution_count_rfp'];
                                }

                                if ($result10 && $result10->num_rows > 0) {
                                    $row10 = $result10->fetch_assoc();
                                    $pdcCount_rfp = $row10['pdc_count_rfp'];
                                }

                                if ($result11 && $result11->num_rows > 0) {
                                    $row11 = $result11->fetch_assoc();
                                    $rtaCount_rfp = $row11['rta_count_rfp'];
                                }
                            }
                        }
                        ?>
                     </div>
                      <div class="under-number">For Review (RM)</div>
                    </div>
                </div>
            </div>
            <?php if ($row['roles'] == 'HO') { ?>
                    <div class="panel-footer">
                        <a href="review_contract.php">
                        <!-- <span class="pull-left green">REGIONAL MANAGER <i>(Click here)</i></span><br> -->
                            <span class="pull-left green" style="font-weight: bold; font-size:16px;">RFP</i></span><br>
                            <span class="pull-left green"><i id="count" style="margin-right:15px;"><?php echo $paymentSolutionCount_rfp; ?></i>PAYMENT SOLUTION</span><br>
                            <span class="pull-left green"><i id="count" style="margin-right:15px;"><?php echo $pdcCount_rfp; ?></i>PDC</span><br>
                            <span class="pull-left green"><i id="count" style="margin-right:15px;"><?php echo $rtaCount_rfp; ?></i>RTA</span>
                            <!-- <span class="pull-left green"><i id="count" style="margin-right:15px;"><?php echo $preparedCount; ?></i>FOR REVIEW</span> -->
                            <span class="pull-right green"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </a>
                        <hr class="submenu-seperator" />
                            <span class="pull-left blue" style="font-weight: bold; font-size:16px;">CONTRACT</i></span><br>
                        <a href="for_review_col.php">
                            <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $notReviewed; ?></i>FOR REVIEW BY RM</span>
                            <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </a>
                        <a href="reviewed_col.php">
                            <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $rfpReviewed; ?></i>REVIEWED CONTRACT BY RM</span>
                            <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span><br><br><br>
                            <div class="clearfix"></div>
                        </a>
                    </div>
            <?php } else { ?>
                    <div class="panel-footer">
                        <a href="review_contract.php">
                            <!-- <span class="pull-left green">REGIONAL MANAGER <i>(Click here)</i></span><br> -->
                            <span class="pull-left green" style="font-weight: bold; font-size:16px;">RFP</i></span><br>
                            <span class="pull-left green"><i id="count" style="margin-right:15px;"><?php echo $paymentSolutionCount_rfp; ?></i>PAYMENT SOLUTION</span><br>
                            <span class="pull-left green"><i id="count" style="margin-right:15px;"><?php echo $pdcCount_rfp; ?></i>PDC</span><br>
                            <span class="pull-left green"><i id="count" style="margin-right:15px;"><?php echo $rtaCount_rfp; ?></i>RTA</span>
                            <!-- <span class="pull-left green"><i id="count" style="margin-right:15px;"><?php echo $preparedCount; ?></i>FOR REVIEW</span> -->
                            <span class="pull-right green"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </a>
                        <hr class="submenu-seperator" />
                            <span class="pull-left green" style="font-weight: bold; font-size:16px;">CONTRACT</i></span><br>
                        <a href="for_review_col.php">
                            <span class="pull-left green"><i id="count" style="margin-right:15px;"><?php echo $notReviewed; ?></i>FOR REVIEW BY RM</span>
                            <span class="pull-right green"><i class="fa fa-arrow-circle-right"></i></span><br>
                            <div class="clearfix"></div>
                        </a>
                        <a href="reviewed_col.php">
                            <span class="pull-left green"><i id="count" style="margin-right:15px;"><?php echo $rfpReviewed; ?></i>REVIEWED CONTRACT BY RM</span>
                            <span class="pull-right green"><i class="fa fa-arrow-circle-right"></i></span><br><br><br>
                            <div class="clearfix"></div>
                        </a>
                    </div>
            <?php } ?>
            <?php 
                if ($row['roles'] != 'HO' && $row['roles'] != 'Auditor') { 
                    if ($terminateRequestCount > 0) { ?>
                        <a href="terminate_contract.php">
                            <div class="panel-footer">
                                <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $terminateRequestCount; ?></i>Terminate contract request</span>
                                <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                    <?php } 
                } 
                if ($row['roles'] != 'HO' && $row['roles'] != 'Auditor' && $row['roles'] != 'Vpo-Approver') { 
                    if ($paymentDueDateRequestCount > 0) { ?>
                        <a href="change_payment_date.php">
                            <div class="panel-footer">
                                <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $paymentDueDateRequestCount; ?></i>Change payment due date request</span>
                                <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                    <?php } 
                    if ($mobileNumberRequestCount > 0) { ?>
                        <a href="change_mobile_number.php">
                            <div class="panel-footer">
                                <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $mobileNumberRequestCount; ?></i>Change mobile number request</span>
                                <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                    <?php } 
                    if ($cancelTransactionRequestCount > 0) { ?>
                        <a href="cancel_payment_transaction.php">
                            <div class="panel-footer">
                                <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $cancelTransactionRequestCount; ?></i>Cancel payment transaction request</span>
                                <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                    <?php } 
                } ?>
        </div>
    </div>

<!-- ********************************************************************************************************* -->

    <div class="col-lg-3 col-md-6">
        <div class="panel panel-yellow">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa-solid fa-clock-rotate-left fa-2x" style="color: #d70c0c;"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                    <div class='huge'>
                     <?php
                        // Retrieve the user's email from the session
                        $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);

                        // Initialize the created count to zero
                        $reviewedCount = 0;

                        // Query to get the user's roles, region, and area from the database
                        $userQuery = "SELECT roles, region, area, mainzone FROM user_form WHERE email = '$user_email'";
                        $userResult = mysqli_query($conn, $userQuery);

                        // Check if the query returned any rows
                        if (mysqli_num_rows($userResult) > 0) {
                            $userRow = mysqli_fetch_assoc($userResult);

                            // Check if the user's role is 'Am-Creator'
                            if ($userRow['roles'] == 'Am-Creator') {
                                $userRegion = $userRow['region'];
                                $userArea = $userRow['area'];

                                // Query to get the count of created contracts based on the user's region and area
                                $sql = "SELECT COUNT(*) AS reviewed_count FROM create_contract WHERE request_status = 'Reviewed' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea'";
                                $result = $conn->query($sql);

                                $sql2 = "SELECT COUNT(*) AS paymentDueDate_pending_count, dueDate_request_status FROM transactional WHERE region = '$userRegion' AND area = '$userArea' AND dueDate_request_status = 'Vpo-Pending' AND dueDate_request_type = 'payment_due_date'";
                                $result2 = $conn->query($sql2);

                                $sql3 = "SELECT COUNT(*) AS mobileNumber_pending_count, mobile_request_status FROM transactional WHERE region = '$userRegion' AND area = '$userArea' AND mobile_request_status = 'Vpo-Pending' AND mobile_request_type = 'change_mobileNumber'";
                                $result3 = $conn->query($sql3);

                                $sql4 = "SELECT COUNT(*) AS cancelTransaction_pending_count, cancel_request_status FROM transactional WHERE region = '$userRegion' AND area = '$userArea' AND cancel_request_status = 'Vpo-Pending' AND cancel_request_type = 'cancel_payment_transaction'";
                                $result4 = $conn->query($sql4);

                                $sql5 = "
                                        SELECT 
                                            contract_number, 
                                            COUNT(DISTINCT contract_number) AS terminate_request_count 
                                        FROM 
                                            transactional 
                                        WHERE 
                                            region = '$userRegion' 
                                            AND area = '$userArea'
                                            AND status = 'Reviewed' 
                                        GROUP BY 
                                            contract_number
                                    ";

                                $result5 = $conn->query($sql5);

                                $sql6 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Reviewed' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result6 = $conn->query($sql6);

                                $sql7 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Reviewed' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'PDC'";
                                $result7 = $conn->query($sql7);

                                $sql8 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Reviewed' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'RTA'";
                                $result8 = $conn->query($sql8);

                                // Initialize counts to avoid undefined variable errors
                                $reviewedCount = 0;
                                $paymentDueDateRequestCount = 0;
                                $mobileNumberRequestCount = 0;
                                $cancelTransactionRequestCount = 0;
                                $terminateRequestCount = 0;
                                $paymentSolutionCount = 0;
                                $pdcCount = 0;
                                $rtaCount = 0;
                                $modifyStatus = null;

                                // Check if each query returned any rows and fetch the counts
                                if ($result && $result->num_rows > 0) {
                                    $reviewedCount = $result->fetch_assoc()['reviewed_count'];
                                }

                                if ($result2 && $result2->num_rows > 0) {
                                    $row2 = $result2->fetch_assoc();
                                    $paymentDueDateRequestCount = $row2['paymentDueDate_pending_count'];
                                    $modifyStatus = $row2['dueDate_request_status'];
                                }

                                if ($result3 && $result3->num_rows > 0) {
                                    $row3 = $result3->fetch_assoc();
                                    $mobileNumberRequestCount = $row3['mobileNumber_pending_count'];
                                    $modifyStatus = $row3['mobile_request_status'];
                                }

                                if ($result4 && $result4->num_rows > 0) {
                                    $row4 = $result4->fetch_assoc();
                                    $cancelTransactionRequestCount = $row4['cancelTransaction_pending_count'];
                                    $modifyStatus = $row4['cancel_request_status'];
                                }

                                if ($result5 && $result5->num_rows > 0) {
                                    $row5 = $result5->fetch_assoc();
                                    $terminateRequestCount = $row5['terminate_request_count'];
                                }

                                if ($result6 && $result6->num_rows > 0) {
                                    $row6 = $result6->fetch_assoc();
                                    $paymentSolutionCount = $row6['payment_solution_count'];
                                }

                                if ($result7 && $result7->num_rows > 0) {
                                    $row7 = $result7->fetch_assoc();
                                    $pdcCount = $row7['pdc_count'];
                                }

                                if ($result8 && $result8->num_rows > 0) {
                                    $row8 = $result8->fetch_assoc();
                                    $rtaCount = $row8['rta_count'];
                                }

                            }elseif($userRow['roles'] == 'Rm-Reviewer') {
                                $userRegion = $userRow['region'];
                                $sql = "SELECT COUNT(*) AS reviewed_count FROM create_contract WHERE request_status = 'Reviewed' AND request_status != 'Terminated' AND region = '$userRegion'";
                                $result = $conn->query($sql);

                                  $sql2 = "SELECT COUNT(*) AS paymentDueDate_pending_count, dueDate_request_status FROM transactional WHERE region = '$userRegion' AND dueDate_request_status = 'Vpo-Pending' AND dueDate_request_type = 'payment_due_date'";
                                $result2 = $conn->query($sql2);

                                $sql3 = "SELECT COUNT(*) AS mobileNumber_pending_count, mobile_request_status FROM transactional WHERE region = '$userRegion' AND mobile_request_status = 'Vpo-Pending' AND mobile_request_type = 'change_mobileNumber'";
                                $result3 = $conn->query($sql3);

                                $sql4 = "SELECT COUNT(*) AS cancelTransaction_pending_count, cancel_request_status FROM transactional WHERE region = '$userRegion' AND cancel_request_status = 'Vpo-Pending' AND cancel_request_type = 'cancel_payment_transaction'";
                                $result4 = $conn->query($sql4);

                                $sql5 = "
                                SELECT 
                                    contract_number, 
                                    COUNT(DISTINCT contract_number) AS terminate_request_count 
                                FROM 
                                    transactional 
                                WHERE 
                                    region = '$userRegion' 
                                    AND status = 'Reviewed' 
                                GROUP BY 
                                    contract_number
                            ";

                                $result5 = $conn->query($sql5);

                                $sql6 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Reviewed' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result6 = $conn->query($sql6);

                                $sql7 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Reviewed' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'PDC'";
                                $result7 = $conn->query($sql7);

                                $sql8 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Reviewed' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'RTA'";
                                $result8 = $conn->query($sql8);

                                // Initialize counts to avoid undefined variable errors
                                $reviewedCount = 0;
                                $paymentDueDateRequestCount = 0;
                                $mobileNumberRequestCount = 0;
                                $cancelTransactionRequestCount = 0;
                                $terminateRequestCount = 0;
                                $paymentSolutionCount = 0;
                                $pdcCount = 0;
                                $rtaCount = 0;
                                $modifyStatus = null;

                                // Check if each query returned any rows and fetch the counts
                                if ($result && $result->num_rows > 0) {
                                    $reviewedCount = $result->fetch_assoc()['reviewed_count'];
                                }

                                if ($result2 && $result2->num_rows > 0) {
                                    $row2 = $result2->fetch_assoc();
                                    $paymentDueDateRequestCount = $row2['paymentDueDate_pending_count'];
                                    $modifyStatus = $row2['dueDate_request_status'];
                                }

                                if ($result3 && $result3->num_rows > 0) {
                                    $row3 = $result3->fetch_assoc();
                                    $mobileNumberRequestCount = $row3['mobileNumber_pending_count'];
                                    $modifyStatus = $row3['mobile_request_status'];
                                }

                                if ($result4 && $result4->num_rows > 0) {
                                    $row4 = $result4->fetch_assoc();
                                    $cancelTransactionRequestCount = $row4['cancelTransaction_pending_count'];
                                    $modifyStatus = $row4['cancel_request_status'];
                                }

                                if ($result5 && $result5->num_rows > 0) {
                                    $row5 = $result5->fetch_assoc();
                                    $terminateRequestCount = $row5['terminate_request_count'];
                                }

                                if ($result6 && $result6->num_rows > 0) {
                                    $row6 = $result6->fetch_assoc();
                                    $paymentSolutionCount = $row6['payment_solution_count'];
                                }

                                if ($result7 && $result7->num_rows > 0) {
                                    $row7 = $result7->fetch_assoc();
                                    $pdcCount = $row7['pdc_count'];
                                }

                                if ($result8 && $result8->num_rows > 0) {
                                    $row8 = $result8->fetch_assoc();
                                    $rtaCount = $row8['rta_count'];
                                }
                                
                            }elseif($userRow['roles'] == 'Vpo-Checker' || $userRow['roles'] == 'Vpo-Reviewer' || $userRow['roles'] == 'Vpo-Approver') {
                                $userMainzone = $userRow['mainzone'];
                                $sql = "SELECT COUNT(*) AS reviewed_count FROM create_contract WHERE request_status = 'Reviewed' AND request_status != 'Terminated' AND mainzone = '$userMainzone'";
                                $result = $conn->query($sql);

                                $sql2 = "SELECT COUNT(*) AS paymentDueDate_pending_count, dueDate_request_status FROM transactional WHERE mainzone = '$userMainzone' AND dueDate_request_status = 'Vpo-Pending' AND dueDate_request_type = 'payment_due_date'";
                                $result2 = $conn->query($sql2);

                                $sql3 = "SELECT COUNT(*) AS mobileNumber_pending_count, mobile_request_status FROM transactional WHERE mainzone = '$userMainzone' AND mobile_request_status = 'Vpo-Pending' AND mobile_request_type = 'change_mobileNumber'";
                                $result3 = $conn->query($sql3);

                                $sql4 = "SELECT COUNT(*) AS cancelTransaction_pending_count, cancel_request_status FROM transactional WHERE mainzone = '$userMainzone' AND cancel_request_status = 'Vpo-Pending' AND cancel_request_type = 'cancel_payment_transaction'";
                                $result4 = $conn->query($sql4);

                                $sql5 = "
                                        SELECT 
                                            contract_number, 
                                            COUNT(DISTINCT contract_number) AS terminate_request_count 
                                        FROM 
                                            transactional 
                                        WHERE mainzone = '$userMainzone'
                                            AND status = 'Reviewed' 
                                        GROUP BY 
                                            contract_number
                                    ";

                                $result5 = $conn->query($sql5);

                                $sql6 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Reviewed' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result6 = $conn->query($sql6);

                                $sql7 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Reviewed' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'PDC'";
                                $result7 = $conn->query($sql7);

                                $sql8 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Reviewed' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'RTA'";
                                $result8 = $conn->query($sql8);

                                // Initialize counts to avoid undefined variable errors
                                $reviewedCount = 0;
                                $paymentDueDateRequestCount = 0;
                                $mobileNumberRequestCount = 0;
                                $cancelTransactionRequestCount = 0;
                                $terminateRequestCount = 0;
                                $paymentSolutionCount = 0;
                                $pdcCount = 0;
                                $rtaCount = 0;
                                $modifyStatus = null;

                                // Check if each query returned any rows and fetch the counts
                                if ($result && $result->num_rows > 0) {
                                    $reviewedCount = $result->fetch_assoc()['reviewed_count'];
                                }

                                if ($result2 && $result2->num_rows > 0) {
                                    $row2 = $result2->fetch_assoc();
                                    $paymentDueDateRequestCount = $row2['paymentDueDate_pending_count'];
                                    $modifyStatus = $row2['dueDate_request_status'];
                                }

                                if ($result3 && $result3->num_rows > 0) {
                                    $row3 = $result3->fetch_assoc();
                                    $mobileNumberRequestCount = $row3['mobileNumber_pending_count'];
                                    $modifyStatus = $row3['mobile_request_status'];
                                }

                                if ($result4 && $result4->num_rows > 0) {
                                    $row4 = $result4->fetch_assoc();
                                    $cancelTransactionRequestCount = $row4['cancelTransaction_pending_count'];
                                    $modifyStatus = $row4['cancel_request_status'];
                                }
                                
                                if ($result5 && $result5->num_rows > 0) {
                                    $row5 = $result5->fetch_assoc();
                                    $terminateRequestCount = $row5['terminate_request_count'];
                                }

                                if ($result6 && $result6->num_rows > 0) {
                                    $row6 = $result6->fetch_assoc();
                                    $paymentSolutionCount = $row6['payment_solution_count'];
                                }

                                if ($result7 && $result7->num_rows > 0) {
                                    $row7 = $result7->fetch_assoc();
                                    $pdcCount = $row7['pdc_count'];
                                }

                                if ($result8 && $result8->num_rows > 0) {
                                    $row8 = $result8->fetch_assoc();
                                    $rtaCount = $row8['rta_count'];
                                }
                            }elseif($userRow['roles'] == 'HO') {
                                $sql = "SELECT COUNT(*) AS reviewed_count FROM create_contract WHERE request_status = 'Reviewed' AND request_status != 'Terminated'";
                                $result = $conn->query($sql);

                                $sql2 = "SELECT COUNT(*) AS paymentDueDate_pending_count, dueDate_request_status FROM transactional WHERE dueDate_request_status = 'Vpo-Pending' AND dueDate_request_type = 'payment_due_date'";
                                $result2 = $conn->query($sql2);

                                $sql3 = "SELECT COUNT(*) AS mobileNumber_pending_count, mobile_request_status FROM transactional WHERE mobile_request_status = 'Vpo-Pending' AND mobile_request_type = 'change_mobileNumber'";
                                $result3 = $conn->query($sql3);

                                $sql4 = "SELECT COUNT(*) AS cancelTransaction_pending_count, cancel_request_status FROM transactional WHERE cancel_request_status = 'Vpo-Pending' AND cancel_request_type = 'cancel_payment_transaction'";
                                $result4 = $conn->query($sql4);

                                $sql5 = "
                                        SELECT 
                                            contract_number, 
                                            COUNT(DISTINCT contract_number) AS terminate_request_count 
                                        FROM 
                                            transactional 
                                        WHERE status = 'Reviewed' 
                                        GROUP BY 
                                            contract_number
                                    ";

                                $result5 = $conn->query($sql5);

                                $sql6 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Reviewed' AND request_status != 'Terminated' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result6 = $conn->query($sql6);

                                $sql7 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Reviewed' AND request_status != 'Terminated' AND mode_of_payment = 'PDC'";
                                $result7 = $conn->query($sql7);

                                $sql8 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Reviewed' AND request_status != 'Terminated' AND mode_of_payment = 'RTA'";
                                $result8 = $conn->query($sql8);

                                  // Initialize counts to avoid undefined variable errors
                                  $reviewedCount = 0;
                                  $paymentDueDateRequestCount = 0;
                                  $mobileNumberRequestCount = 0;
                                  $cancelTransactionRequestCount = 0;
                                  $terminateRequestCount = 0;
                                  $paymentSolutionCount = 0;
                                  $pdcCount = 0;
                                  $rtaCount = 0;
                                  $modifyStatus = null;
  
                                  // Check if each query returned any rows and fetch the counts
                                  if ($result && $result->num_rows > 0) {
                                      $reviewedCount = $result->fetch_assoc()['reviewed_count'];
                                  }
  
                                  if ($result2 && $result2->num_rows > 0) {
                                      $row2 = $result2->fetch_assoc();
                                      $paymentDueDateRequestCount = $row2['paymentDueDate_pending_count'];
                                      $modifyStatus = $row2['dueDate_request_status'];
                                  }
  
                                  if ($result3 && $result3->num_rows > 0) {
                                      $row3 = $result3->fetch_assoc();
                                      $mobileNumberRequestCount = $row3['mobileNumber_pending_count'];
                                      $modifyStatus = $row3['mobile_request_status'];
                                  }
  
                                  if ($result4 && $result4->num_rows > 0) {
                                      $row4 = $result4->fetch_assoc();
                                      $cancelTransactionRequestCount = $row4['cancelTransaction_pending_count'];
                                      $modifyStatus = $row4['cancel_request_status'];
                                  }

                                  if ($result5 && $result5->num_rows > 0) {
                                    $row5 = $result5->fetch_assoc();
                                    $terminateRequestCount = $row5['terminate_request_count'];
                                }
                                if ($result6 && $result6->num_rows > 0) {
                                    $row6 = $result6->fetch_assoc();
                                    $paymentSolutionCount = $row6['payment_solution_count'];
                                }

                                if ($result7 && $result7->num_rows > 0) {
                                    $row7 = $result7->fetch_assoc();
                                    $pdcCount = $row7['pdc_count'];
                                }

                                if ($result8 && $result8->num_rows > 0) {
                                    $row8 = $result8->fetch_assoc();
                                    $rtaCount = $row8['rta_count'];
                                }
                            }
                        }
                        // echo $reviewedCount;
                        ?>
                    </div>
                        <div class="under-number">VP admin assistant</div>
                    </div>
                </div>
            </div>
            <?php if($row['roles'] == 'HO'){ ?>
                <div class="panel-footer">
                    <a href="vpo_checker.php">
                        <!-- <span class="pull-left yellow">VP ADMIN SUPPORT</span><br> -->
                        <span class="pull-left yellow" style="font-weight: bold; font-size:16px;">RFP</i></span><br>
                        <span class="pull-left yellow"><i id="count" style="margin-right:15px;"><?php echo $paymentSolutionCount; ?></i>PAYMENT SOLUTION</span><br>
                        <span class="pull-left yellow"><i id="count" style="margin-right:15px;"><?php echo $pdcCount; ?></i>PDC</span><br>
                        <span class="pull-left yellow"><i id="count" style="margin-right:15px;"><?php echo $rtaCount; ?></i>RTA</span>
                        <span class="pull-right yellow"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <hr class="submenu-seperator" />
                        <span class="pull-left yellow" style="font-weight: bold; font-size:16px;">CONTRACT</i></span><br>
                    <a href="for_review_col.php">
                        <span class="pull-left yellow"><i id="count" style="margin-right:15px;"><?php echo $notReviewed; ?></i>FOR REVIEW BY RM</span>
                        <span class="pull-right yellow"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <a href="reviewed_col.php">
                        <span class="pull-left yellow"><i id="count" style="margin-right:15px;"><?php echo $rfpReviewed; ?></i>REVIEWED CONTRACT BY RM</span>
                        <span class="pull-right yellow"><i class="fa fa-arrow-circle-right"></i></span><br><br><br>
                        <div class="clearfix"></div>
                    </a>
                </div>
                <?php } elseif($row['mainzone'] == 'VISMIN'){ ?>
                    <div class="panel-footer">
                        <a href="vpo_checker.php">
                            <!-- <span class="pull-left yellow">VP ADMIN SUPPORT</span><br> -->
                            <span class="pull-left yellow" style="font-weight: bold; font-size:16px;">RFP</i></span><br>
                            <span class="pull-left yellow"><i id="count" style="margin-right:15px;"><?php echo $paymentSolutionCount; ?></i>PAYMENT SOLUTION</span><br>
                            <span class="pull-left yellow"><i id="count" style="margin-right:15px;"><?php echo $pdcCount; ?></i>PDC</span><br>
                            <span class="pull-left yellow"><i id="count" style="margin-right:15px;"><?php echo $rtaCount; ?></i>RTA</span>
                            <span class="pull-right yellow"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </a>
                        <hr class="submenu-seperator" />
                            <span class="pull-left yellow" style="font-weight: bold; font-size:16px;">CONTRACT</i></span><br>
                        <a href="for_review_col.php">
                            <span class="pull-left yellow"><i id="count" style="margin-right:15px;"><?php echo $notReviewed; ?></i>FOR REVIEW BY RM</span>
                            <span class="pull-right yellow"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </a>
                        <a href="reviewed_col.php">
                            <span class="pull-left yellow"><i id="count" style="margin-right:15px;"><?php echo $rfpReviewed; ?></i>REVIEWED CONTRACT BY RM</span>
                            <span class="pull-right yellow"><i class="fa fa-arrow-circle-right"></i></span><br><br><br>
                            <div class="clearfix"></div>
                        </a>
                    </div>
                <?php } elseif($row['mainzone'] == 'LNCR') { ?>
                    <div class="panel-footer">
                        <a href="vpo_checker.php">
                            <!-- <span class="pull-left yellow">VP ADMIN SUPPORT</span><br> -->
                            <span class="pull-left yellow" style="font-weight: bold; font-size:16px;">RFP</i></span><br>
                            <span class="pull-left yellow"><i id="count" style="margin-right:15px;"><?php echo $paymentSolutionCount; ?></i>PAYMENT SOLUTION</span><br>
                            <span class="pull-left yellow"><i id="count" style="margin-right:15px;"><?php echo $pdcCount; ?></i>PDC</span><br>
                            <span class="pull-left yellow"><i id="count" style="margin-right:15px;"><?php echo $rtaCount; ?></i>RTA</span>
                            <span class="pull-right yellow"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </a>
                        <hr class="submenu-seperator" />
                            <span class="pull-left yellow" style="font-weight: bold; font-size:16px;">CONTRACT</i></span><br>
                        <a href="for_review_col.php">
                            <span class="pull-left yellow"><i id="count" style="margin-right:15px;"><?php echo $notReviewed; ?></i>FOR REVIEW BY RM</span>
                            <span class="pull-right yellow"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </a>
                        <a href="reviewed_col.php">
                            <span class="pull-left yellow"><i id="count" style="margin-right:15px;"><?php echo $rfpReviewed; ?></i>REVIEWED CONTRACT BY RM</span>
                            <span class="pull-right yellow"><i class="fa fa-arrow-circle-right"></i></span><br><br><br>
                            <div class="clearfix"></div>
                        </a>
                    </div>
            <?php } ?>

            <?php 
                if ($row['roles'] != 'HO' && $row['roles'] != 'Auditor') { 
                    if ($terminateRequestCount > 0) { ?>
                        <a href="terminate_contract.php">
                            <div class="panel-footer">
                                <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $terminateRequestCount; ?></i>Terminate contract request</span>
                                <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                    <?php } 
                } if ($row['roles'] != 'HO' && $row['roles'] != 'Auditor' && $row['roles'] != 'Vpo-Approver') { 
                    if ($paymentDueDateRequestCount > 0) { ?>
                        <a href="change_payment_date_vpo.php">
                            <div class="panel-footer">
                                <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $paymentDueDateRequestCount; ?></i>Change payment due date request</span>
                                <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                    <?php } 
                    if ($mobileNumberRequestCount > 0) { ?>
                        <a href="change_mobile_number_vpo.php">
                            <div class="panel-footer">
                                <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $mobileNumberRequestCount; ?></i>Change mobile number request</span>
                                <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                    <?php } 
                    if ($cancelTransactionRequestCount > 0) { ?>
                        <a href="cancel_payment_transaction_vpo.php">
                            <div class="panel-footer">
                                <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $cancelTransactionRequestCount; ?></i>Cancel payment transaction request</span>
                                <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                    <?php } 
                } ?>

        </div>
    </div>

<!-- ********************************************************************************************************* -->

    <div class="col-lg-3 col-md-6">
        <div class="panel panel-red">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa-solid fa-stopwatch fa-2x" style="color: #d70c0c"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class='huge'>
                            <?php
                        // Retrieve the user's email from the session
                        $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);

                        // Initialize the created count to zero
                        $receivedCount = 0;

                        // Query to get the user's roles, region, and area from the database
                        $userQuery = "SELECT roles, region, area, mainzone FROM user_form WHERE email = '$user_email'";
                        $userResult = mysqli_query($conn, $userQuery);

                        // Check if the query returned any rows
                        if (mysqli_num_rows($userResult) > 0) {
                            $userRow = mysqli_fetch_assoc($userResult);

                            // Check if the user's role is 'Am-Creator'
                            if ($userRow['roles'] == 'Am-Creator') {
                                $userRegion = $userRow['region'];
                                $userArea = $userRow['area'];

                                // Query to get the count of created contracts based on the user's region and area
                                $sql = "SELECT COUNT(*) AS received_count FROM create_contract WHERE request_status = 'Received' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea'";
                                $result = $conn->query($sql);

                                $sql2 = "SELECT COUNT(*) AS paymentDueDate_pending_count, dueDate_request_status FROM transactional WHERE region = '$userRegion' AND area = '$userArea' AND dueDate_request_status = 'For-Approval' AND dueDate_request_type = 'payment_due_date'";
                                $result2 = $conn->query($sql2);

                                $sql3 = "SELECT COUNT(*) AS mobileNumber_pending_count, mobile_request_status FROM transactional WHERE region = '$userRegion' AND area = '$userArea' AND mobile_request_status = 'For-Approval' AND dueDate_request_type = 'change_mobileNumber'";
                                $result3 = $conn->query($sql3);

                                $sql4 = "SELECT COUNT(*) AS cancelTransaction_pending_count, cancel_request_status FROM transactional WHERE region = '$userRegion' AND area = '$userArea' AND cancel_request_status = 'For-Approval' AND dueDate_request_type = 'cancel_payment_transaction'";
                                $result4 = $conn->query($sql4);

                                $sql5 = "
                                        SELECT 
                                            contract_number, 
                                            COUNT(DISTINCT contract_number) AS terminate_request_count 
                                        FROM 
                                            transactional 
                                        WHERE 
                                            region = '$userRegion' 
                                            AND area = '$userArea'
                                            AND status = 'Received' 
                                        GROUP BY 
                                            contract_number
                                    ";

                                $result5 = $conn->query($sql5);

                                $sql6 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Received' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result6 = $conn->query($sql6);

                                $sql7 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Received' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'PDC'";
                                $result7 = $conn->query($sql7);

                                $sql8 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Received' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'RTA'";
                                $result8 = $conn->query($sql8);

                                // Initialize counts to avoid undefined variable errors
                                $receivedCount = 0;
                                $paymentDueDateRequestCount = 0;
                                $mobileNumberRequestCount = 0;
                                $cancelTransactionRequestCount = 0;
                                $terminateRequestCount = 0;
                                $paymentSolutionCount = 0;
                                $pdcCount = 0;
                                $rtaCount = 0;
                                $modifyStatus = null;

                                // Check if each query returned any rows and fetch the counts
                                if ($result && $result->num_rows > 0) {
                                    $receivedCount = $result->fetch_assoc()['received_count'];
                                }

                                if ($result2 && $result2->num_rows > 0) {
                                    $row2 = $result2->fetch_assoc();
                                    $paymentDueDateRequestCount = $row2['paymentDueDate_pending_count'];
                                    $modifyStatus = $row2['dueDate_request_status'];
                                }

                                if ($result3 && $result3->num_rows > 0) {
                                    $row3 = $result3->fetch_assoc();
                                    $mobileNumberRequestCount = $row3['mobileNumber_pending_count'];
                                    $modifyStatus = $row3['mobile_request_status'];
                                }

                                if ($result4 && $result4->num_rows > 0) {
                                    $row4 = $result4->fetch_assoc();
                                    $cancelTransactionRequestCount = $row4['cancelTransaction_pending_count'];
                                    $modifyStatus = $row4['cancel_request_status'];
                                }

                                if ($result5 && $result5->num_rows > 0) {
                                    $row5 = $result5->fetch_assoc();
                                    $terminateRequestCount = $row5['terminate_request_count'];
                                }

                                if ($result6 && $result6->num_rows > 0) {
                                    $row6 = $result6->fetch_assoc();
                                    $paymentSolutionCount = $row6['payment_solution_count'];
                                }

                                if ($result7 && $result7->num_rows > 0) {
                                    $row7 = $result7->fetch_assoc();
                                    $pdcCount = $row7['pdc_count'];
                                }

                                if ($result8 && $result8->num_rows > 0) {
                                    $row8 = $result8->fetch_assoc();
                                    $rtaCount = $row8['rta_count'];
                                }
                            }elseif($userRow['roles'] == 'Rm-Reviewer') {
                                $userRegion = $userRow['region'];
                                $sql = "SELECT COUNT(*) AS received_count FROM create_contract WHERE request_status = 'Received' AND request_status != 'Terminated' AND region = '$userRegion'";
                                $result = $conn->query($sql);

                                $sql2 = "SELECT COUNT(*) AS paymentDueDate_pending_count, dueDate_request_status FROM transactional WHERE region = '$userRegion' AND dueDate_request_status = 'For-Approval' AND dueDate_request_type = 'payment_due_date'";
                                $result2 = $conn->query($sql2);

                                $sql3 = "SELECT COUNT(*) AS mobileNumber_pending_count, mobile_request_status FROM transactional WHERE region = '$userRegion' AND mobile_request_status = 'For-Approval' AND mobile_request_type = 'change_mobileNumber'";
                                $result3 = $conn->query($sql3);

                                $sql4 = "SELECT COUNT(*) AS cancelTransaction_pending_count, cancel_request_status FROM transactional WHERE region = '$userRegion' AND cancel_request_status = 'For-Approval' AND cancel_request_type = 'cancel_payment_transaction'";
                                $result4 = $conn->query($sql4);

                                $sql5 = "
                                        SELECT 
                                            contract_number, 
                                            COUNT(DISTINCT contract_number) AS terminate_request_count 
                                        FROM 
                                            transactional 
                                        WHERE 
                                            region = '$userRegion' 
                                            AND status = 'Received' 
                                        GROUP BY 
                                            contract_number
                                    ";

                                $result5 = $conn->query($sql5);

                                $sql6 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Received' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result6 = $conn->query($sql6);

                                $sql7 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Received' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'PDC'";
                                $result7 = $conn->query($sql7);

                                $sql8 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Received' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'RTA'";
                                $result8 = $conn->query($sql8);

                                // Initialize counts to avoid undefined variable errors
                                $receivedCount = 0;
                                $paymentDueDateRequestCount = 0;
                                $mobileNumberRequestCount = 0;
                                $cancelTransactionRequestCount = 0;
                                $terminateRequestCount = 0;
                                $paymentSolutionCount = 0;
                                $pdcCount = 0;
                                $rtaCount = 0;
                                $modifyStatus = null;

                                // Check if each query returned any rows and fetch the counts
                                if ($result && $result->num_rows > 0) {
                                    $receivedCount = $result->fetch_assoc()['received_count'];
                                }

                                if ($result2 && $result2->num_rows > 0) {
                                    $row2 = $result2->fetch_assoc();
                                    $paymentDueDateRequestCount = $row2['paymentDueDate_pending_count'];
                                    $modifyStatus = $row2['dueDate_request_status'];
                                }

                                if ($result3 && $result3->num_rows > 0) {
                                    $row3 = $result3->fetch_assoc();
                                    $mobileNumberRequestCount = $row3['mobileNumber_pending_count'];
                                    $modifyStatus = $row3['mobile_request_status'];
                                }

                                if ($result4 && $result4->num_rows > 0) {
                                    $row4 = $result4->fetch_assoc();
                                    $cancelTransactionRequestCount = $row4['cancelTransaction_pending_count'];
                                    $modifyStatus = $row4['cancel_request_status'];
                                }

                                if ($result5 && $result5->num_rows > 0) {
                                    $row5 = $result5->fetch_assoc();
                                    $terminateRequestCount = $row5['terminate_request_count'];
                                }

                                if ($result6 && $result6->num_rows > 0) {
                                    $row6 = $result6->fetch_assoc();
                                    $paymentSolutionCount = $row6['payment_solution_count'];
                                }

                                if ($result7 && $result7->num_rows > 0) {
                                    $row7 = $result7->fetch_assoc();
                                    $pdcCount = $row7['pdc_count'];
                                }

                                if ($result8 && $result8->num_rows > 0) {
                                    $row8 = $result8->fetch_assoc();
                                    $rtaCount = $row8['rta_count'];
                                }

                            }elseif($userRow['roles'] == 'Vpo-Checker' || $userRow['roles'] == 'Vpo-Reviewer' || $userRow['roles'] == 'Vpo-Approver') {
                                $userMainzone = $userRow['mainzone'];
                                $sql = "SELECT COUNT(*) AS received_count FROM create_contract WHERE request_status = 'Received' AND request_status != 'Terminated' AND mainzone = '$userMainzone'";
                                $result = $conn->query($sql);

                                $sql2 = "SELECT COUNT(*) AS paymentDueDate_pending_count, dueDate_request_status FROM transactional WHERE mainzone = '$userMainzone' AND dueDate_request_status = 'For-Approval' AND dueDate_request_type = 'payment_due_date'";
                                $result2 = $conn->query($sql2);

                                $sql3 = "SELECT COUNT(*) AS mobileNumber_pending_count, mobile_request_status FROM transactional WHERE mainzone = '$userMainzone' AND mobile_request_status = 'For-Approval' AND mobile_request_type = 'change_mobileNumber'";
                                $result3 = $conn->query($sql3);

                                $sql4 = "SELECT COUNT(*) AS cancelTransaction_pending_count, cancel_request_status FROM transactional WHERE mainzone = '$userMainzone' AND cancel_request_status = 'For-Approval' AND cancel_request_type = 'cancel_payment_transaction'";
                                $result4 = $conn->query($sql4);

                                $sql5 = "
                                        SELECT 
                                            contract_number, 
                                            COUNT(DISTINCT contract_number) AS terminate_request_count 
                                        FROM 
                                            transactional 
                                        WHERE mainzone = '$userMainzone'
                                            AND status = 'Received' 
                                        GROUP BY 
                                            contract_number
                                    ";

                                $result5 = $conn->query($sql5);

                                $sql6 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Received' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result6 = $conn->query($sql6);

                                $sql7 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Received' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'PDC'";
                                $result7 = $conn->query($sql7);

                                $sql8 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Received' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'RTA'";
                                $result8 = $conn->query($sql8);

                                // Initialize counts to avoid undefined variable errors
                                $receivedCount = 0;
                                $paymentDueDateRequestCount = 0;
                                $mobileNumberRequestCount = 0;
                                $cancelTransactionRequestCount = 0;
                                $terminateRequestCount = 0;
                                $paymentSolutionCount = 0;
                                $pdcCount = 0;
                                $rtaCount = 0;
                                $modifyStatus = null;

                                // Check if each query returned any rows and fetch the counts
                                if ($result && $result->num_rows > 0) {
                                    $receivedCount = $result->fetch_assoc()['received_count'];
                                }

                                if ($result2 && $result2->num_rows > 0) {
                                    $row2 = $result2->fetch_assoc();
                                    $paymentDueDateRequestCount = $row2['paymentDueDate_pending_count'];
                                    $modifyStatus = $row2['dueDate_request_status'];
                                }

                                if ($result3 && $result3->num_rows > 0) {
                                    $row3 = $result3->fetch_assoc();
                                    $mobileNumberRequestCount = $row3['mobileNumber_pending_count'];
                                    $modifyStatus = $row3['mobile_request_status'];
                                }

                                if ($result4 && $result4->num_rows > 0) {
                                    $row4 = $result4->fetch_assoc();
                                    $cancelTransactionRequestCount = $row4['cancelTransaction_pending_count'];
                                    $modifyStatus = $row4['cancel_request_status'];
                                }

                                if ($result5 && $result5->num_rows > 0) {
                                    $row5 = $result5->fetch_assoc();
                                    $terminateRequestCount = $row5['terminate_request_count'];
                                }

                                if ($result6 && $result6->num_rows > 0) {
                                    $row6 = $result6->fetch_assoc();
                                    $paymentSolutionCount = $row6['payment_solution_count'];
                                }

                                if ($result7 && $result7->num_rows > 0) {
                                    $row7 = $result7->fetch_assoc();
                                    $pdcCount = $row7['pdc_count'];
                                }

                                if ($result8 && $result8->num_rows > 0) {
                                    $row8 = $result8->fetch_assoc();
                                    $rtaCount = $row8['rta_count'];
                                }

                            }elseif($userRow['roles'] == 'HO') {
                                $sql = "SELECT COUNT(*) AS received_count FROM create_contract WHERE request_status = 'Received' AND request_status != 'Terminated'";
                                $result = $conn->query($sql);

                                $sql2 = "SELECT COUNT(*) AS paymentDueDate_pending_count, dueDate_request_status FROM transactional WHERE dueDate_request_status = 'For-Approval' AND dueDate_request_type = 'payment_due_date'";
                                $result2 = $conn->query($sql2);

                                $sql3 = "SELECT COUNT(*) AS mobileNumber_pending_count, mobile_request_status FROM transactional WHERE mobile_request_status = 'For-Approval' AND mobile_request_type = 'change_mobileNumber'";
                                $result3 = $conn->query($sql3);

                                $sql4 = "SELECT COUNT(*) AS cancelTransaction_pending_count, cancel_request_status FROM transactional WHERE cancel_request_status = 'For-Approval' AND cancel_request_type = 'cancel_payment_transaction'";
                                $result4 = $conn->query($sql4);

                                $sql5 = "
                                        SELECT 
                                            contract_number, 
                                            COUNT(DISTINCT contract_number) AS terminate_request_count 
                                        FROM 
                                            transactional 
                                        WHERE status = 'Received' 
                                        GROUP BY 
                                            contract_number
                                    ";

                                $result5 = $conn->query($sql5);

                                $sql6 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Received' AND request_status != 'Terminated' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result6 = $conn->query($sql6);

                                $sql7 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Received' AND request_status != 'Terminated' AND mode_of_payment = 'PDC'";
                                $result7 = $conn->query($sql7);

                                $sql8 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Received' AND request_status != 'Terminated' AND mode_of_payment = 'RTA'";
                                $result8 = $conn->query($sql8);

                                // Initialize counts to avoid undefined variable errors
                                $receivedCount = 0;
                                $paymentDueDateRequestCount = 0;
                                $mobileNumberRequestCount = 0;
                                $cancelTransactionRequestCount = 0;
                                $terminateRequestCount = 0;
                                $paymentSolutionCount = 0;
                                $pdcCount = 0;
                                $rtaCount = 0;
                                $modifyStatus = null;

                                if ($result && $result->num_rows > 0) {
                                    $receivedCount = $result->fetch_assoc()['received_count'];
                                }

                                if ($result2 && $result2->num_rows > 0) {
                                    $row2 = $result2->fetch_assoc();
                                    $paymentDueDateRequestCount = $row2['paymentDueDate_pending_count'];
                                    $modifyStatus = $row2['dueDate_request_status'];
                                }

                                if ($result3 && $result3->num_rows > 0) {
                                    $row3 = $result3->fetch_assoc();
                                    $mobileNumberRequestCount = $row3['mobileNumber_pending_count'];
                                    $modifyStatus = $row3['mobile_request_status'];
                                }

                                if ($result4 && $result4->num_rows > 0) {
                                    $row4 = $result4->fetch_assoc();
                                    $cancelTransactionRequestCount = $row4['cancelTransaction_pending_count'];
                                    $modifyStatus = $row4['cancel_request_status'];
                                }

                                if ($result5 && $result5->num_rows > 0) {
                                    $row5 = $result5->fetch_assoc();
                                    $terminateRequestCount = $row5['terminate_request_count'];
                                }

                                if ($result6 && $result6->num_rows > 0) {
                                    $row6 = $result6->fetch_assoc();
                                    $paymentSolutionCount = $row6['payment_solution_count'];
                                }

                                if ($result7 && $result7->num_rows > 0) {
                                    $row7 = $result7->fetch_assoc();
                                    $pdcCount = $row7['pdc_count'];
                                }

                                if ($result8 && $result8->num_rows > 0) {
                                    $row8 = $result8->fetch_assoc();
                                    $rtaCount = $row8['rta_count'];
                                }
                            }
                        }
                        // echo $receivedCount;
                        ?>
                        </div>
                         <div class="under-number">VP admin officer</div>
                    </div>
                </div>
            </div> 
            <?php 
            // Determine VPO section based on roles and mainzone
            if ($row['roles'] == 'HO') { ?>
                <div class="panel-footer">
                    <a href="vpo_reviewer.php">
                        <!-- <span class="pull-left red">VP ADMIN OFFICER</span><br> -->
                        <span class="pull-left red" style="font-weight: bold; font-size:16px;">RFP</i></span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $paymentSolutionCount; ?></i>PAYMENT SOLUTION</span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $pdcCount; ?></i>PDC</span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $rtaCount; ?></i>RTA</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <hr class="submenu-seperator" />
                        <span class="pull-left red" style="font-weight: bold; font-size:16px;">CONTRACT</i></span><br>
                    <a href="for_review_col.php">
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $notReviewed; ?></i>FOR REVIEW BY RM</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <a href="reviewed_col.php">
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $rfpReviewed; ?></i>REVIEWED CONTRACT BY RM</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span><br><br><br>
                        <div class="clearfix"></div>
                    </a>
                </div>
            <?php } elseif ($row['mainzone'] == 'VISMIN') { ?>
                <div class="panel-footer">
                    <a href="vpo_reviewer.php">
                        <!-- <span class="pull-left red">VP ADMIN OFFICER</span><br> -->
                        <span class="pull-left red" style="font-weight: bold; font-size:16px;">RFP</i></span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $paymentSolutionCount; ?></i>PAYMENT SOLUTION</span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $pdcCount; ?></i>PDC</span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $rtaCount; ?></i>RTA</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <hr class="submenu-seperator" />
                        <span class="pull-left red" style="font-weight: bold; font-size:16px;">CONTRACT</i></span><br>
                    <a href="for_review_col.php">
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $notReviewed; ?></i>FOR REVIEW BY RM</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <a href="reviewed_col.php">
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $rfpReviewed; ?></i>REVIEWED CONTRACT BY RM</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span><br><br><br>
                        <div class="clearfix"></div>
                    </a>
                </div>
            <?php } elseif ($row['mainzone'] == 'LNCR') { ?>
                <div class="panel-footer">
                    <a href="vpo_reviewer.php">
                        <!-- <span class="pull-left red">VP ADMIN OFFICER</span><br> -->
                        <span class="pull-left red" style="font-weight: bold; font-size:16px;">RFP</i></span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $paymentSolutionCount; ?></i>PAYMENT SOLUTION</span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $pdcCount; ?></i>PDC</span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $rtaCount; ?></i>RTA</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <hr class="submenu-seperator" />
                        <span class="pull-left red" style="font-weight: bold; font-size:16px;">CONTRACT</i></span><br>
                    <a href="for_review_col.php">
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $notReviewed; ?></i>FOR REVIEW BY RM</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <a href="reviewed_col.php">
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $rfpReviewed; ?></i>REVIEWED CONTRACT BY RM</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span><br><br><br>
                        <div class="clearfix"></div>
                    </a>
                </div>
            <?php } ?>

            <?php 
                if ($row['roles'] != 'HO' && $row['roles'] != 'Auditor') { 
                    if ($terminateRequestCount > 0) { ?>
                        <a href="terminate_contract.php">
                            <div class="panel-footer">
                                <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $terminateRequestCount; ?></i>Terminate contract request</span>
                                <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                    <?php } 
                } if ($row['roles'] != 'HO' && $row['roles'] != 'Auditor' && $row['roles'] != 'Vpo-Approver') { 
                    if ($paymentDueDateRequestCount > 0) { ?>
                        <a href="change_payment_date_approve.php">
                            <div class="panel-footer">
                                <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $paymentDueDateRequestCount; ?></i>Change payment due date request</span>
                                <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                    <?php } 
                    if ($mobileNumberRequestCount > 0) { ?>
                        <a href="change_mobile_number_approve.php">
                            <div class="panel-footer">
                                <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $mobileNumberRequestCount; ?></i>Change mobile number request</span>
                                <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                    <?php } 
                    if ($cancelTransactionRequestCount > 0) { ?>
                        <a href="cancel_payment_transaction_approve.php">
                            <div class="panel-footer">
                                <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $cancelTransactionRequestCount; ?></i>Cancel payment transaction request</span>
                                <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                                <div class="clearfix"></div>
                            </div>
                        </a>
                    <?php } 
                } ?>
        </div>
    </div>

    <!-- ********************************************************************************************************* -->

    <div class="col-lg-3 col-md-6">
        <div class="panel panel-red">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa-solid fa-building-user fa-2x" style="color: #d70c0c"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class='huge'>
                              <?php
                        // Retrieve the user's email from the session
                        $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);

                        // Initialize the created count to zero
                        $checkedCount = 0;

                        // Query to get the user's roles, region, and area from the database
                        $userQuery = "SELECT roles, region, area, mainzone FROM user_form WHERE email = '$user_email'";
                        $userResult = mysqli_query($conn, $userQuery);

                        // Check if the query returned any rows
                        if (mysqli_num_rows($userResult) > 0) {
                            $userRow = mysqli_fetch_assoc($userResult);

                            // Check if the user's role is 'Am-Creator'
                            if ($userRow['roles'] == 'Am-Creator') {
                                $userRegion = $userRow['region'];
                                $userArea = $userRow['area'];

                                // Query to get the count of created contracts based on the user's region and area
                                $sql = "SELECT COUNT(*) AS checked_count FROM create_contract WHERE request_status = 'Checked' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea'";
                                $result = $conn->query($sql);

                                $sql1 = "
                                        SELECT 
                                            contract_number, 
                                            COUNT(DISTINCT contract_number) AS terminate_request_count 
                                        FROM 
                                            transactional 
                                        WHERE 
                                            region = '$userRegion' 
                                            AND area = '$userArea'
                                            AND status = 'Checked' 
                                        GROUP BY 
                                            contract_number
                                    ";

                                $result1 = $conn->query($sql1);

                                $sql2 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Checked' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result2 = $conn->query($sql2);

                                $sql3 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Checked' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'PDC'";
                                $result3 = $conn->query($sql3);

                                $sql4 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Checked' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea' AND mode_of_payment = 'RTA'";
                                $result4 = $conn->query($sql4);

                                $checkedCount = 0;
                                $paymentSolutionCount = 0;
                                $pdcCount = 0;
                                $rtaCount = 0;
                                $terminateRequestCount = 0;
                                // Check if the query returned any rows
                                if ($result->num_rows > 0) {
                                    // Fetch the created contract count
                                    $checkedCount = $result->fetch_assoc()['checked_count'];
                                }

                                if ($result1->num_rows > 0) {
                                    // Fetch the created contract count
                                    $terminateRequestCount = $result1->fetch_assoc()['terminate_request_count'];
                                }

                                if ($result2->num_rows > 0) {
                                    // Fetch the created contract count
                                    $paymentSolutionCount = $result2->fetch_assoc()['payment_solution_count'];
                                }

                                if ($result3->num_rows > 0) {
                                    // Fetch the created contract count
                                    $pdcCount = $result3->fetch_assoc()['pdc_count'];
                                }

                                if ($result4->num_rows > 0) {
                                    // Fetch the created contract count
                                    $rtaCount = $result4->fetch_assoc()['rta_count'];
                                }
                            }elseif($userRow['roles'] == 'Rm-Reviewer') {
                                $userRegion = $userRow['region'];
                                $sql = "SELECT COUNT(*) AS checked_count FROM create_contract WHERE request_status = 'Checked' AND request_status != 'Terminated' AND region = '$userRegion'";
                                $result = $conn->query($sql);

                                $sql1 = "
                                        SELECT 
                                            contract_number, 
                                            COUNT(DISTINCT contract_number) AS terminate_request_count 
                                        FROM 
                                            transactional 
                                        WHERE 
                                            region = '$userRegion' 
                                            AND status = 'Checked' 
                                        GROUP BY 
                                            contract_number
                                    ";

                                $result1 = $conn->query($sql1);

                                $sql2 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Checked' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result2 = $conn->query($sql2);

                                $sql3 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Checked' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'PDC'";
                                $result3 = $conn->query($sql3);

                                $sql4 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Checked' AND request_status != 'Terminated' AND region = '$userRegion' AND mode_of_payment = 'RTA'";
                                $result4 = $conn->query($sql4);

                                $checkedCount = 0;
                                $paymentSolutionCount = 0;
                                $pdcCount = 0;
                                $rtaCount = 0;
                                $terminateRequestCount = 0;
                                // Check if the query returned any rows
                                if ($result->num_rows > 0) {
                                    // Fetch the created contract count
                                    $checkedCount = $result->fetch_assoc()['checked_count'];
                                }

                                if ($result1->num_rows > 0) {
                                    // Fetch the created contract count
                                    $terminateRequestCount = $result1->fetch_assoc()['terminate_request_count'];
                                }

                                if ($result2->num_rows > 0) {
                                    // Fetch the created contract count
                                    $paymentSolutionCount = $result2->fetch_assoc()['payment_solution_count'];
                                }

                                if ($result3->num_rows > 0) {
                                    // Fetch the created contract count
                                    $pdcCount = $result3->fetch_assoc()['pdc_count'];
                                }

                                if ($result4->num_rows > 0) {
                                    // Fetch the created contract count
                                    $rtaCount = $result4->fetch_assoc()['rta_count'];
                                }

                            }elseif($userRow['roles'] == 'Vpo-Checker' || $userRow['roles'] == 'Vpo-Reviewer' || $userRow['roles'] == 'Vpo-Approver') {
                                $userMainzone = $userRow['mainzone'];
                                $sql = "SELECT COUNT(*) AS checked_count FROM create_contract WHERE request_status = 'Checked' AND request_status != 'Terminated' AND mainzone = '$userMainzone'";
                                $result = $conn->query($sql);

                                $sql1 = "
                                        SELECT 
                                            contract_number, 
                                            COUNT(DISTINCT contract_number) AS terminate_request_count 
                                        FROM 
                                            transactional 
                                        WHERE mainzone = '$userMainzone'
                                            AND status = 'Checked' 
                                        GROUP BY 
                                            contract_number
                                    ";

                                $result1 = $conn->query($sql1);

                                $sql2 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Checked' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result2 = $conn->query($sql2);

                                $sql3 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Checked' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'PDC'";
                                $result3 = $conn->query($sql3);

                                $sql4 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Checked' AND request_status != 'Terminated' AND mainzone = '$userMainzone' AND mode_of_payment = 'RTA'";
                                $result4 = $conn->query($sql4);

                                $checkedCount = 0;
                                $paymentSolutionCount = 0;
                                $pdcCount = 0;
                                $rtaCount = 0;
                                $terminateRequestCount = 0;
                                // Check if the query returned any rows
                                if ($result->num_rows > 0) {
                                    // Fetch the created contract count
                                    $checkedCount = $result->fetch_assoc()['checked_count'];
                                }

                                if ($result1->num_rows > 0) {
                                    // Fetch the created contract count
                                    $terminateRequestCount = $result1->fetch_assoc()['terminate_request_count'];
                                }

                                if ($result2->num_rows > 0) {
                                    // Fetch the created contract count
                                    $paymentSolutionCount = $result2->fetch_assoc()['payment_solution_count'];
                                }

                                if ($result3->num_rows > 0) {
                                    // Fetch the created contract count
                                    $pdcCount = $result3->fetch_assoc()['pdc_count'];
                                }

                                if ($result4->num_rows > 0) {
                                    // Fetch the created contract count
                                    $rtaCount = $result4->fetch_assoc()['rta_count'];
                                }

                            }elseif($userRow['roles'] == 'HO') {
                                $sql = "SELECT COUNT(*) AS checked_count FROM create_contract WHERE request_status = 'Checked' AND request_status != 'Terminated'";
                                $result = $conn->query($sql);

                                $sql1 = "
                                        SELECT 
                                            contract_number, 
                                            COUNT(DISTINCT contract_number) AS terminate_request_count 
                                        FROM 
                                            transactional 
                                        WHERE status = 'Checked' 
                                        GROUP BY 
                                            contract_number
                                    ";

                                $result1 = $conn->query($sql1);

                                $sql2 = "SELECT COUNT(*) AS payment_solution_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Checked' AND request_status != 'Terminated' AND mode_of_payment = 'PAYMENT SOLUTION'";
                                $result2 = $conn->query($sql2);

                                $sql3 = "SELECT COUNT(*) AS pdc_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Checked' AND request_status != 'Terminated' AND mode_of_payment = 'PDC'";
                                $result3 = $conn->query($sql3);

                                $sql4 = "SELECT COUNT(*) AS rta_count FROM create_contract WHERE rfp_status = 'Reviewed' AND request_status = 'Checked' AND request_status != 'Terminated' AND mode_of_payment = 'RTA'";
                                $result4 = $conn->query($sql4);

                                $checkedCount = 0;
                                $paymentSolutionCount = 0;
                                $pdcCount = 0;
                                $rtaCount = 0;
                                $terminateRequestCount = 0;
                                // Check if the query returned any rows
                                if ($result->num_rows > 0) {
                                    // Fetch the created contract count
                                    $checkedCount = $result->fetch_assoc()['checked_count'];
                                }

                                if ($result1->num_rows > 0) {
                                    // Fetch the created contract count
                                    $terminateRequestCount = $result1->fetch_assoc()['terminate_request_count'];
                                }

                                if ($result2->num_rows > 0) {
                                    // Fetch the created contract count
                                    $paymentSolutionCount = $result2->fetch_assoc()['payment_solution_count'];
                                }

                                if ($result3->num_rows > 0) {
                                    // Fetch the created contract count
                                    $pdcCount = $result3->fetch_assoc()['pdc_count'];
                                }

                                if ($result4->num_rows > 0) {
                                    // Fetch the created contract count
                                    $rtaCount = $result4->fetch_assoc()['rta_count'];
                                }
                            }
                        }
                        // echo $checkedCount;
                        ?>
                        </div>
                         <div class="under-number">VP Approval</div>
                    </div>
                </div>
            </div>
            <?php if($row['roles'] == 'HO'){ ?>
                <div class="panel-footer">
                    <a href="audit_contract.php">
                        <!-- <span class="pull-left red">VP DIVISION MANAGER </span><br> -->
                        <span class="pull-left red" style="font-weight: bold; font-size:16px;">RFP</i></span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $paymentSolutionCount; ?></i>PAYMENT SOLUTION</span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $pdcCount; ?></i>PDC</span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $rtaCount; ?></i>RTA</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <hr class="submenu-seperator" />
                        <span class="pull-left red" style="font-weight: bold; font-size:16px;">CONTRACT</i></span><br>
                    <a href="for_review_col.php">
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $notReviewed; ?></i>FOR REVIEW BY RM</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <a href="reviewed_col.php">
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $rfpReviewed; ?></i>REVIEWED CONTRACT BY RM</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span><br><br><br>
                        <div class="clearfix"></div>
                    </a>
                </div>
            <?php }elseif($row['mainzone'] == 'VISMIN'){ ?>
                <div class="panel-footer">
                    <a href="audit_contract.php">
                        <!-- <span class="pull-left red">VP DIVISION MANAGER </span><br> -->
                        <span class="pull-left red" style="font-weight: bold; font-size:16px;">RFP</i></span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $paymentSolutionCount; ?></i>PAYMENT SOLUTION</span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $pdcCount; ?></i>PDC</span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $rtaCount; ?></i>RTA</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <hr class="submenu-seperator" />
                        <span class="pull-left red" style="font-weight: bold; font-size:16px;">CONTRACT</i></span><br>
                    <a href="for_review_col.php">
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $notReviewed; ?></i>FOR REVIEW BY RM</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <a href="reviewed_col.php">
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $rfpReviewed; ?></i>REVIEWED CONTRACT BY RM</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span><br><br><br>
                        <div class="clearfix"></div>
                    </a>
                </div>
            <?php }elseif($row['mainzone'] == 'LNCR'){ ?>
                <div class="panel-footer">
                    <a href="audit_contract.php">
                        <!-- <span class="pull-left red">VP DIVISION MANAGER </span><br> -->
                        <span class="pull-left red" style="font-weight: bold; font-size:16px;">RFP</i></span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $paymentSolutionCount; ?></i>PAYMENT SOLUTION</span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $pdcCount; ?></i>PDC</span><br>
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $rtaCount; ?></i>RTA</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <hr class="submenu-seperator" />
                        <span class="pull-left red" style="font-weight: bold; font-size:16px;">CONTRACT</i></span><br>
                    <a href="for_review_col.php">
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $notReviewed; ?></i>FOR REVIEW BY RM</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span>
                        <div class="clearfix"></div>
                    </a>
                    <a href="reviewed_col.php">
                        <span class="pull-left red"><i id="count" style="margin-right:15px;"><?php echo $rfpReviewed; ?></i>REVIEWED CONTRACT BY RM</span>
                        <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span><br><br><br>
                        <div class="clearfix"></div>
                    </a>
                </div>
            <?php } ?>
            <?php 
            // Only display additional request sections if the user role is not HO, Auditor, or Vpo-Approver
            if (!in_array($row['roles'], ['HO', 'Auditor'])) { 
                 // Cancel Payment Transaction Request Section
                 if ($terminateRequestCount > 0) { ?>
                    <a href="terminate_contract.php">
                        <div class="panel-footer">
                            <span class="pull-left blue"><i id="count" style="margin-right:15px;"><?php echo $terminateRequestCount; ?></i>Terminate contract request</span>
                            <span class="pull-right blue"><i class="fa fa-arrow-circle-right"></i></span>
                            <div class="clearfix"></div>
                        </div>
                    </a>
                <?php } 
            } ?>
        </div>
    </div>
    <!-- ********************************************************************************************************* -->
    <?php if ($userRow['roles'] == 'HO') {?>
    <div class="col-lg-3 col-md-6">
        <div class="panel panel-red">
            <div class="panel-heading">
                <div class="row">
                    <div class="col-xs-3">
                        <i class="fa-regular fa-circle-check fa-2x" style="color: #d70c0c;"></i>
                    </div>
                    <div class="col-xs-9 text-right">
                        <div class='huge'>
                              <?php
                        // Retrieve the user's email from the session
                        $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);

                        // Initialize the created count to zero
                        $checkedApproved = 0;

                        // Query to get the user's roles, region, and area from the database
                        $userQuery = "SELECT roles, region, area, mainzone FROM user_form WHERE email = '$user_email'";
                        $userResult = mysqli_query($conn, $userQuery);

                        // Check if the query returned any rows
                        if (mysqli_num_rows($userResult) > 0) {
                            $userRow = mysqli_fetch_assoc($userResult);

                            // Check if the user's role is 'Am-Creator'
                            if ($userRow['roles'] == 'Am-Creator') {
                                $userRegion = $userRow['region'];
                                $userArea = $userRow['area'];

                                // Query to get the count of created contracts based on the user's region and area
                                $sql = "SELECT COUNT(*) AS approved_count FROM create_contract WHERE request_status = 'Approved' AND request_status != 'Terminated' AND region = '$userRegion' AND area = '$userArea'";
                                $result = $conn->query($sql);

                                // Check if the query returned any rows
                                if ($result->num_rows > 0) {
                                    // Fetch the created contract count
                                    $checkedApproved = $result->fetch_assoc()['approved_count'];
                                }
                            }elseif($userRow['roles'] == 'Rm-Reviewer') {
                                $userRegion = $userRow['region'];
                            $sql = "SELECT COUNT(*) AS approved_count FROM create_contract WHERE request_status = 'Approved' AND request_status != 'Terminated' AND region = '$userRegion'";
                                $result = $conn->query($sql);

                                // Check if the query returned any rows
                                if ($result->num_rows > 0) {
                                    // Fetch the created contract count
                                    $checkedApproved = $result->fetch_assoc()['approved_count'];
                                }
                            }elseif($userRow['roles'] == 'Vpo-Checker' || $userRow['roles'] == 'Vpo-Reviewer' || $userRow['roles'] == 'Vpo-Approver') {
                                $userMainzone = $userRow['mainzone'];
                                $sql = "SELECT COUNT(*) AS approved_count FROM create_contract WHERE request_status = 'Approved' AND request_status != 'Terminated' AND mainzone = '$userMainzone'";
                                $result = $conn->query($sql);

                                // Check if the query returned any rows
                                if ($result->num_rows > 0) {
                                    // Fetch the created contract count
                                    $checkedApproved = $result->fetch_assoc()['approved_count'];
                                }
                            }elseif($userRow['roles'] == 'HO') {
                                $sql = "SELECT COUNT(*) AS approved_count FROM create_contract WHERE request_status = 'Approved' AND request_status != 'Terminated'";
                                $result = $conn->query($sql);

                                // Check if the query returned any rows
                                if ($result->num_rows > 0) {
                                    // Fetch the created contract count
                                    $checkedApproved = $result->fetch_assoc()['approved_count'];
                                }
                            }
                        }
                        // echo $checkedApproved;
                        ?>
                        </div>
                         <div class="under-number">Approved</div>
                    </div>
                </div>
            </div>
            <?php if($row['roles'] == 'HO'){ ?>
            <a href="approved_contract.php">
                <div class="panel-footer">
                    <span class="pull-left red"><i id="count" style="margin-right: 15px;"><?php echo $checkedApproved; ?></i>HO</span>
                    <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span>
                    <div class="clearfix"></div>
                </div>
            </a>
            <?php }else{ ?>
            <a href="approved_contract.php">
                <div class="panel-footer">
                    <span class="pull-left red"><i id="count" style="margin-right: 15px;"><?php echo $checkedApproved; ?></i>HO</span>
                    <span class="pull-right red"><i class="fa fa-arrow-circle-right"></i></span>
                    <div class="clearfix"></div>
                </div>
            </a>
            <?php } 
            }?>
        </div>
    </div>
</div>
</div>
<?php endif; ?>
<?php
// Get the current date
$currentDate = date("Y-m-d");

// Calculate the date 5 months later
$fiveMonthsLater = date("Y-m-d", strtotime("+5 months"));

// Retrieve the user's email from the session
$user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);

// Query to get the user's roles, region, and area from the database
$userQuery = "SELECT roles, region, area, mainzone FROM user_form WHERE email = '$user_email'";
$userResult = mysqli_query($conn, $userQuery);

// Initialize variables
$userRegion = '';
$userArea = '';
$userMainzone = '';

// Check if the query returned any rows
if (mysqli_num_rows($userResult) > 0) {
    $userRow = mysqli_fetch_assoc($userResult);

    // Determine user's role and set the region, area, or mainzone accordingly
    if ($userRow['roles'] == 'Am-Creator') {
        $userRegion = $userRow['region'];
        $userArea = $userRow['area'];
        $sql = "SELECT contract_number, region, area, branch, branch_code, start_date, end_date, edit_amount_lessor
                FROM create_contract 
                WHERE end_date >= '$currentDate'
                AND end_date <= '$fiveMonthsLater'
                AND request_status = 'Approved'
                AND region = '$userRegion'
                AND area = '$userArea'";
    } elseif ($userRow['roles'] == 'Rm-Reviewer') {
        $userRegion = $userRow['region'];
        $sql = "SELECT contract_number, region, area, branch, branch_code, start_date, end_date, edit_amount_lessor
                FROM create_contract 
                WHERE end_date >= '$currentDate'
                AND end_date <= '$fiveMonthsLater'
                AND request_status = 'Approved'
                AND region = '$userRegion'";
    } elseif ($userRow['roles'] == 'Vpo-Checker' || $userRow['roles'] == 'Vpo-Reviewer' || $userRow['roles'] == 'Vpo-Approver' || $userRow['roles'] == 'Finance' || $userRow['roles'] == 'Auditor') {
        $userMainzone = $userRow['mainzone'];
        $sql = "SELECT contract_number, region, area, branch, branch_code, start_date, end_date, edit_amount_lessor
                FROM create_contract 
                WHERE end_date >= '$currentDate'
                AND end_date <= '$fiveMonthsLater'
                AND request_status = 'Approved'
                AND mainzone = '$userMainzone'";
    } elseif ($userRow['roles'] == 'HO') {
        $sql = "SELECT contract_number, region, area, branch, branch_code, start_date, end_date, edit_amount_lessor
                FROM create_contract 
                WHERE end_date >= '$currentDate'
                AND end_date <= '$fiveMonthsLater'
                AND request_status != ''";
    }
}

$result = $conn->query($sql);

$rowCount = 0;
if ($result && $result->num_rows > 0) {
    $rowCount = $result->num_rows;
    // Output the bell icon with the count and notification container
    echo '<div class="bell-icon" onclick="showNotifications()">';
    echo '<i class="fa-regular fa-bell"></i><span class="notification-count">' . $rowCount . '</span>';
    echo '</div>';
    echo '<section id="notification-container" class="notification" style="display: none;">';
    echo '<div class="notif-container">';
    echo '<div class="notif notif-warning" role="alert">';
    echo 'Some contracts are about to end within 5 months. Please review:';
    echo '<table class="table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Contract Number</th>';
    echo '<th>Region</th>';
    echo '<th>Area</th>';
    echo '<th>Branch</th>';
    echo '<th>Branch Code</th>';
    echo '<th>Start Date</th>';
    echo '<th>End Date</th>';
    echo '<th>Monthly Rental</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    while($row = $result->fetch_assoc()) {
        echo '<tr>';
        echo '<td>' . $row['contract_number'] . '</td>';
        echo '<td>' . $row['region'] . '</td>';
        echo '<td>' . $row['area'] . '</td>';
        echo '<td>' . $row['branch'] . '</td>';
        echo '<td>' . $row['branch_code'] . '</td>';
        echo '<td>' . $row['start_date'] . '</td>';
        echo '<td>' . $row['end_date'] . '</td>';
        echo '<td>' . $row['edit_amount_lessor'] . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    echo '</div>';
    echo '</section>';
}

?>

<script>
function showNotifications() {
  var notifContainer = document.getElementById('notification-container');
  if (notifContainer.style.display === 'none' || notifContainer.style.display === '') {
    notifContainer.style.display = 'block';
  } else {
    notifContainer.style.display = 'none';
  }
}
</script>
  </body>
</html>
