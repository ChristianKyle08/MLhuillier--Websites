<?php
session_start();
include '../../config/config.php';

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit;
}

// Load required scripts
echo '<script src="../../sweetalert2/dist/sweetalert2.all.min.js"></script>';
echo '<link rel="stylesheet" href="../../sweetalert2/dist/sweetalert2.min.css">';
echo '<script src="../../jquery-3.7.1.js"></script>';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle submit termination request button
    $(document).on('click', '#submitTerminate', function() {
        let region = $('input[name="region"]').val();
        let contractNumber = $('input[name="contract_number"]').val();
        let remarks = $('#remarks').val().trim();

        if (!remarks) {
            Swal.fire('Error!', 'Please provide a reason for termination.', 'error');
            return;
        }

        // Close the modal and remove overlay
        let modalEl = document.getElementById('terminateModal');
        let modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) {
            modalInstance.hide();
        }
        // Ensure overlay/backdrop is removed
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');

        // Ask for confirmation
        Swal.fire({
            title: 'Forward the request to the RM for review?',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Confirm',
            cancelButtonText: 'Cancel',
            allowOutsideClick: false
        }).then((result) => {
            if (result.isConfirmed) {
                // Send AJAX request
                $.ajax({
                    type: 'POST',
                    url: 'terminate_request_status.php',
                    data: {
                        region: region,
                        contract_number: contractNumber,
                        remarks: remarks
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                title: 'Success!',
                                html: response.message,
                                icon: 'success',
                                showConfirmButton: true,
                                allowOutsideClick: false
                            }).then(() => {
                                window.location.href = 'terminate_contract.php';
                            });
                        } else {
                            Swal.fire('Error!', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error!', 'Failed to update contract. Please try again.', 'error');
                    }
                });
            }
        });
    });
});
</script>


<?php

    if (isset($_POST['review_terminate'])) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Send the request to VPO',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Confirm',
                cancelButtonText: 'Cancel',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // User confirmed, proceed with update
                    $.ajax({
                        type: 'POST',
                        url: 'terminate_review_status.php',
                        data: {
                            region: '<?php echo $_POST['region']; ?>',
                            contract_number: '<?php echo $_POST['contract_number']; ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Success!',
                                    html: response.message,
                                    icon: 'success',
                                    showConfirmButton: true,
                                    allowOutsideClick: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Reload the page if the user clicks "OK"
                                        window.location.href = 'terminate_contract.php';
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message,
                                    icon: 'error',
                                    showConfirmButton: true,
                                    allowOutsideClick: false
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to update contract. Please try again.',
                                icon: 'error',
                                showConfirmButton: true,
                                allowOutsideClick: false
                            });
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    if (isset($_POST['rm_disapprove_terminate'])) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Disapprove this termination request?',
                text: "This action will mark the request as disapproved.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Disapprove',
                cancelButtonText: 'Cancel',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: 'terminate_rm_disapprove_status.php',
                        data: {
                            region: '<?php echo $_POST['region']; ?>',
                            contract_number: '<?php echo $_POST['contract_number']; ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Disapproved!',
                                    html: response.message,
                                    icon: 'success',
                                    showConfirmButton: true,
                                    allowOutsideClick: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = 'terminate_contract.php';
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message,
                                    icon: 'error',
                                    showConfirmButton: true,
                                    allowOutsideClick: false
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to update contract. Please try again.',
                                icon: 'error',
                                showConfirmButton: true,
                                allowOutsideClick: false
                            });
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    if (isset($_POST['check_terminate'])) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Send request to VP',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Confirm',
                cancelButtonText: 'Cancel',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // User confirmed, proceed with update
                    $.ajax({
                        type: 'POST',
                        url: 'terminate_check_status.php',
                        data: {
                            region: '<?php echo $_POST['region']; ?>',
                            contract_number: '<?php echo $_POST['contract_number']; ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Success!',
                                    html: response.message,
                                    icon: 'success',
                                    showConfirmButton: true,
                                    allowOutsideClick: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Reload the page if the user clicks "OK"
                                        window.location.href = 'terminate_contract.php';
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message,
                                    icon: 'error',
                                    showConfirmButton: true,
                                    allowOutsideClick: false
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to update contract. Please try again.',
                                icon: 'error',
                                showConfirmButton: true,
                                allowOutsideClick: false
                            });
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }

    
    if (isset($_POST['approve_terminate'])) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Terminate Contract',
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Terminate',
                cancelButtonText: 'Cancel',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // User confirmed, proceed with update
                    $.ajax({
                        type: 'POST',
                        url: 'terminate_approve_status.php',
                        data: {
                            region: '<?php echo $_POST['region']; ?>',
                            contract_number: '<?php echo $_POST['contract_number']; ?>',
                            terminated_by: '<?php echo $_SESSION['user_name']; ?>' // Assuming the username is stored in the session
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Success!',
                                    html: response.message,
                                    icon: 'success',
                                    showConfirmButton: true,
                                    allowOutsideClick: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Reload the page if the user clicks "OK"
                                        window.location.href = 'terminate_contract.php';
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message,
                                    icon: 'error',
                                    showConfirmButton: true,
                                    allowOutsideClick: false
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to update contract. Please try again.',
                                icon: 'error',
                                showConfirmButton: true,
                                allowOutsideClick: false
                            });
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }

    if (isset($_POST['vpo_disapprove_terminate'])) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                title: 'Disapprove this termination request?',
                text: "This action will mark the request as disapproved.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Disapprove',
                cancelButtonText: 'Cancel',
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        type: 'POST',
                        url: 'terminate_vpo_disapprove_status.php',
                        data: {
                            region: '<?php echo $_POST['region']; ?>',
                            contract_number: '<?php echo $_POST['contract_number']; ?>'
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Disapproved!',
                                    html: response.message,
                                    icon: 'success',
                                    showConfirmButton: true,
                                    allowOutsideClick: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = 'terminate_contract.php';
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: response.message,
                                    icon: 'error',
                                    showConfirmButton: true,
                                    allowOutsideClick: false
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                title: 'Error!',
                                text: 'Failed to update contract. Please try again.',
                                icon: 'error',
                                showConfirmButton: true,
                                allowOutsideClick: false
                            });
                        }
                    });
                }
            });
        });
        </script>
        <?php
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
            <title>ML Rental - Terminate Request</title>
            <!-- ✅ Local Google Font -->
            <link href="../../assets/css/poppins.css" rel="stylesheet">

            <!-- ✅ Local Bootstrap CSS -->
            <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">

            <!-- ✅ Local Bootstrap Icons -->
            <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">

            <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
            <!-- ✅ Your custom CSS should come AFTER font import -->
            <link rel="stylesheet" href="../../assets/css/sidebar.css">
            <link rel="stylesheet" href="../../assets/css/scrollbar.css">
        </head>
    <body>
    <?php include ('navbar.php'); ?>
    <div id="mainContent">
        <button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
            <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
            <span class="fw-normal">Menu</span>
        </button>
        <div class="container py-3">
    <h2 class="fw-bold text-danger mb-4">TERMINATE REQUEST</h2>

    <?php
    $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);
    $userQuery = "SELECT roles, mainzone, area, region FROM user_form WHERE username = '$user_email' OR email = '$user_email'";
    $resultUser = mysqli_query($conn, $userQuery);
    $user = mysqli_fetch_assoc($resultUser);
    $userRole = $user['roles'];
    $userRegion = $user['region'] ?? '';
    $userArea = $user['area'] ?? '';
    $userMainZone = $user['mainzone'] ?? '';

    if($userRole == 'Am-Creator') { ?>

    <!-- Select Region and Contract -->
    <form method="POST">
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label for="region" class="form-label fw-semibold">Region</label>
                <select name="region" id="region" class="form-select" onchange="this.form.submit()" required>
                    <option value="">Select Region</option>
                    <?php
                    $regionQuery = "SELECT DISTINCT region FROM transactional 
                                    WHERE status NOT IN ('Terminated','Paid') 
                                    AND area = '$userArea' 
                                    ORDER BY region ASC";
                    $resultRegion = mysqli_query($conn, $regionQuery);
                    while($rowRegion = mysqli_fetch_assoc($resultRegion)){
                        $selected = (isset($_POST['region']) && $_POST['region']==$rowRegion['region'])?'selected':'';
                        echo "<option value='{$rowRegion['region']}' $selected>{$rowRegion['region']}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="contractNumber" class="form-label fw-semibold">Contract Number</label>
                <select name="contract_number" id="contractNumber" class="form-select" required>
                    <option value="">Select Contract</option>
                    <?php
                    if(isset($_POST['region'])){
                        $region = mysqli_real_escape_string($conn, $_POST['region']);
                        $contractQuery = "SELECT contract_number, branch, mode_of_payment
                                          FROM transactional 
                                          WHERE status='Unpaid' AND region='$region'
                                          GROUP BY contract_number, branch, mode_of_payment
                                          ORDER BY contract_number DESC";
                        $resultContract = mysqli_query($conn, $contractQuery);
                        while($rowContract = mysqli_fetch_assoc($resultContract)){
                            $selected = (isset($_POST['contract_number']) && $_POST['contract_number']==$rowContract['contract_number']) ? 'selected' : '';
                            $displayText = $rowContract['contract_number'] . " | Branch: " . $rowContract['branch'] . " | Payment: " . $rowContract['mode_of_payment'];
                            echo "<option value='{$rowContract['contract_number']}' $selected>$displayText</option>";
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="proceed" class="btn btn-danger w-100">
                    <i class="bi bi-arrow-right-circle me-1"></i>Proceed
                </button>
            </div>
        </div>
    </form>

    <?php
        $pendingQuery = "SELECT DISTINCT contract_number, region, branch, status, reason_termination 
        FROM transactional 
        WHERE status IN ('Termination Requested','Termination Reviewed','Termination Checked')
        AND area = '$userArea'
        ORDER BY status ASC, contract_number DESC";
    
    $pendingResult = mysqli_query($conn, $pendingQuery);
    
    if ($pendingResult && mysqli_num_rows($pendingResult) > 0) {
        echo "<h4 class='fw-bold text-secondary mb-3'>Pending Termination Requests</h4>
        <div class='row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3'>";
    
        while ($pending = mysqli_fetch_assoc($pendingResult)) {
            // Status badge setup
            $displayStatus = '';
            $badgeClass = '';
            if ($pending['status'] == 'Termination Requested') {
                $displayStatus = 'Pending Approval from RM';
                $badgeClass = 'bg-warning text-dark';
            } elseif ($pending['status'] == 'Termination Reviewed') {
                $displayStatus = 'Pending Approval from VPO';
                $badgeClass = 'bg-info text-dark';
            } elseif ($pending['status'] == 'Termination Checked') {
                $displayStatus = 'Pending Approval from VPO Approver';
                $badgeClass = 'bg-info text-dark';
            }
    
            // Escape reason for modal
            $reason = htmlspecialchars($pending['reason_termination'] ?? 'No reason provided');
    
            // Unique modal ID per contract
            $modalId = "reasonModal_" . $pending['contract_number'];
            
            echo "<div class='col'>
                <div class='card shadow-sm border-0 h-100'>
                    <div class='card-body'>
                        <h6 class='card-title text-danger fw-bold'>Contract: {$pending['contract_number']}</h6>
                        <p class='mb-1'><i class='bi bi-geo-alt me-1'></i>Region: {$pending['region']}</p>
                        <p class='mb-1'><i class='bi bi-building me-1'></i>Branch: {$pending['branch']}</p>
                        
                        <!-- Remark Icon -->
                        <button type='button' class='btn btn-outline-danger btn-sm me-2' data-bs-toggle='modal' data-bs-target='#$modalId'>
                            <i class='bi bi-chat-left-text'></i>
                        </button><span class='text-danger me-4'>Reason</span>
                        
                        <span class='badge $badgeClass'>$displayStatus</span>
                    </div>
                </div>
            </div>
    
            <!-- Modal -->
            <div class='modal fade' id='$modalId' tabindex='-1' aria-labelledby='{$modalId}Label' aria-hidden='true'>
              <div class='modal-dialog modal-dialog-centered'>
                <div class='modal-content'>
                  <div class='modal-header'>
                    <h5 class='modal-title text-danger' id='{$modalId}Label'>
                      <i class='bi bi-chat-left-text me-2'></i>Reason for Termination
                    </h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                  </div>
                  <div class='modal-body'>
                    <p>$reason</p>
                  </div>
                  <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                  </div>
                </div>
              </div>
            </div>";
        }
    
        echo "</div>";
    }
     else {
        echo "<div class='alert alert-info text-center'>No pending termination requests.</div>";
    }

    // Existing logic for proceed button selection
    if(isset($_POST['proceed'])){
        $region = $_POST['region'];
        $contractNumber = $_POST['contract_number'];

        $transactions = mysqli_query($conn,"SELECT * FROM transactional WHERE region='$region' AND contract_number='$contractNumber'");
        if($transactions && mysqli_num_rows($transactions) > 0){
            $hasUnpaid = false;
            $branches = [];
            $contractStart = '';
            $contractEnd = '';
            while($row = mysqli_fetch_assoc($transactions)){
                if($row['status']=='Unpaid') $hasUnpaid = true;
                $branches[] = $row['branch'];
                if(!empty($row['contract_start']) && (empty($contractStart) || strtotime($row['contract_start'])<strtotime($contractStart))) $contractStart=$row['contract_start'];
                if(!empty($row['contract_end']) && (empty($contractEnd) || strtotime($row['contract_end'])>strtotime($contractEnd))) $contractEnd=$row['contract_end'];
            }

            if ($hasUnpaid) {
                echo "
                <div class='card shadow-sm mt-4 mb-3 border-0'>
                    <div class='card-body'>
                        <h5 class='card-title text-danger'>Contract: $contractNumber</h5>
                        <p class='mb-1'><i class='bi bi-geo-alt me-1'></i>Region: $region</p>
                        <p class='mb-1'><i class='bi bi-building me-1'></i>Branch: " . implode(', ', array_unique($branches)) . "</p>
                        <p class='mb-1'><strong>Contract Start:</strong> " . date('F d, Y', strtotime($contractStart)) . " | 
                           <strong>Contract End:</strong> " . date('F d, Y', strtotime($contractEnd)) . "</p>
            
                        <!-- Trigger Modal -->
                        <button type='button' class='btn btn-danger mt-3' data-bs-toggle='modal' data-bs-target='#terminateModal'>
                            <i class='bi bi-arrow-right-circle me-1'></i>Send Request
                        </button>
                    </div>
                </div>
            
                <!-- Termination Modal -->
                <div class='modal fade' id='terminateModal' tabindex='-1' aria-labelledby='terminateModalLabel' aria-hidden='true'>
                  <div class='modal-dialog'>
                    <div class='modal-content'>
                      <div class='modal-header'>
                        <h5 class='modal-title text-danger' id='terminateModalLabel'>
                            <i class='bi bi-exclamation-triangle me-2'></i>Termination Request
                        </h5>
                        <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                      </div>
                      <form method='post'>
                        <div class='modal-body'>
                            <input type='hidden' name='region' value='$region'>
                            <input type='hidden' name='contract_number' value='$contractNumber'>
                            <div class='mb-3'>
                            <label for='remarks' class='form-label fw-semibold'>Reason for Termination</label>
                            <textarea class='form-control' name='remarks' id='remarks' rows='4' required></textarea>
                            </div>
                        </div>
                        <div class='modal-footer'>
                            <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                            <button type='button' id='submitTerminate' class='btn btn-danger'>
                            <i class='bi bi-send me-1'></i>Submit Request
                            </button>
                        </div>
                        </form>
                    </div>
                  </div>
                </div>
                ";
            } else {
                echo "<div class='alert alert-warning text-center'>No Unpaid transactions to request termination.</div>";
            }
        } else {
            echo "<div class='alert alert-warning text-center'>No transactions found for this contract.</div>";
        }
    }
}
 elseif($userRole=='Rm-Reviewer') {
    
    // Rm-Reviewer: show all pending requests
$pendingRequests = mysqli_query($conn,"
SELECT DISTINCT contract_number, region 
FROM transactional 
WHERE status='Termination Requested' AND region='$userRegion' 
ORDER BY contract_number DESC
");

if($pendingRequests && mysqli_num_rows($pendingRequests) > 0){
echo "<div class='row row-cols-1 row-cols-md-2 g-3'>";
while($row = mysqli_fetch_assoc($pendingRequests)){
    $contractNumber = $row['contract_number'];
    $region = $row['region'];

    // Fetch branches, contract dates, and reason
    $contractData = mysqli_query($conn,"
        SELECT * FROM transactional 
        WHERE region='$region' AND contract_number='$contractNumber'
    ");

    $branches = [];
    $contractStart = '';
    $contractEnd = '';
    $reasonTermination = '';

    while($cRow = mysqli_fetch_assoc($contractData)){
        $branches[] = $cRow['branch'];

        if(!empty($cRow['contract_start']) && (empty($contractStart) || strtotime($cRow['contract_start']) < strtotime($contractStart))) {
            $contractStart = $cRow['contract_start'];
        }
        if(!empty($cRow['contract_end']) && (empty($contractEnd) || strtotime($cRow['contract_end']) > strtotime($contractEnd))) {
            $contractEnd = $cRow['contract_end'];
        }

        if(!empty($cRow['reason_termination'])){
            $reasonTermination = $cRow['reason_termination'];
        }
    }

    // Unique modal ID per contract
    $modalId = "reasonModal_" . $contractNumber;

    echo "<div class='col'>
            <div class='card shadow-sm'>
                <div class='card-body'>
                    <h5 class='card-title text-danger'>Contract: $contractNumber</h5>
                    <p class='mb-1'><i class='bi bi-building me-1'></i>Branch: ".implode(', ', array_unique($branches))."</p>
                    <p class='mb-1'><strong>Contract Start:</strong> ".date('F d, Y', strtotime($contractStart))." | 
                        <strong>Contract End:</strong> ".date('F d, Y', strtotime($contractEnd))."</p>
                    <p><i class='bi bi-geo-alt me-1'></i>Region: $region</p>

                    <!-- Remark Icon -->
                    <button type='button' class='btn btn-outline-danger btn-sm me-2' data-bs-toggle='modal' data-bs-target='#$modalId'>
                        <i class='bi bi-chat-left-text'></i>
                    </button><span class='text-danger'>Reason for Termination</span>

                    <form method='post' class='mt-3' onsubmit='return confirm(\"Accept request for termination?\");'>
                        <input type='hidden' name='region' value='$region'>
                        <input type='hidden' name='contract_number' value='$contractNumber'>
                        <button type='submit' name='review_terminate' class='btn btn-success'>
                            <i class='bi bi-check-circle me-1'></i>Accept Request
                        </button>
                         <!-- Disapprove -->
                        <button type='submit' name='rm_disapprove_terminate' class='btn btn-danger'>
                            <i class='bi bi-x-circle me-1'></i>Disapprove
                        </button>
                    </form>
                </div>
            </div>
          </div>";

    // Modal for reason
    echo "<div class='modal fade' id='$modalId' tabindex='-1'>
            <div class='modal-dialog modal-dialog-centered'>
                <div class='modal-content'>
                    <div class='modal-header'>
                        <h5 class='modal-title text-danger'>Reason for Termination</h5>
                        <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body'>
                        <p>".(!empty($reasonTermination) ? htmlspecialchars($reasonTermination) : "<em>No reason provided</em>")."</p>
                    </div>
                    <div class='modal-footer'>
                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                    </div>
                </div>
            </div>
          </div>";
}
echo "</div>";
    } else {
        $pendingQuery = "SELECT DISTINCT contract_number, region, branch, status, reason_termination 
        FROM transactional 
        WHERE status IN ('Termination Requested','Termination Reviewed','Termination Checked')
        AND region = '$userRegion'
        ORDER BY status ASC, contract_number DESC";
    
    $pendingResult = mysqli_query($conn, $pendingQuery);
    
    if ($pendingResult && mysqli_num_rows($pendingResult) > 0) {
        echo "<h4 class='fw-bold text-secondary mb-3'>Pending Termination Requests</h4>
        <div class='row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3'>";
    
        while ($pending = mysqli_fetch_assoc($pendingResult)) {
            // Status badge setup
            $displayStatus = '';
            $badgeClass = '';
            if ($pending['status'] == 'Termination Requested') {
                $displayStatus = 'Pending Approval from RM';
                $badgeClass = 'bg-warning text-dark';
            } elseif ($pending['status'] == 'Termination Reviewed') {
                $displayStatus = 'Pending Approval from VPO';
                $badgeClass = 'bg-info text-dark';
            } elseif ($pending['status'] == 'Termination Checked') {
                $displayStatus = 'Pending Approval from VPO Approver';
                $badgeClass = 'bg-info text-dark';
            }
    
            // Escape reason for modal
            $reason = htmlspecialchars($pending['reason_termination'] ?? 'No reason provided');
    
            // Unique modal ID per contract
            $modalId = "reasonModal_" . $pending['contract_number'];
            
            echo "<div class='col'>
                <div class='card shadow-sm border-0 h-100'>
                    <div class='card-body'>
                        <h6 class='card-title text-danger fw-bold'>Contract: {$pending['contract_number']}</h6>
                        <p class='mb-1'><i class='bi bi-geo-alt me-1'></i>Region: {$pending['region']}</p>
                        <p class='mb-1'><i class='bi bi-building me-1'></i>Branch: {$pending['branch']}</p>
                        
                        <!-- Remark Icon -->
                        <button type='button' class='btn btn-outline-danger btn-sm me-2' data-bs-toggle='modal' data-bs-target='#$modalId'>
                            <i class='bi bi-chat-left-text'></i>
                        </button><span class='text-danger me-2'>Reason</span>
                        
                        <span class='badge $badgeClass'>$displayStatus</span>
                    </div>
                </div>
            </div>
    
            <!-- Modal -->
            <div class='modal fade' id='$modalId' tabindex='-1' aria-labelledby='{$modalId}Label' aria-hidden='true'>
              <div class='modal-dialog modal-dialog-centered'>
                <div class='modal-content'>
                  <div class='modal-header'>
                    <h5 class='modal-title text-danger' id='{$modalId}Label'>
                      <i class='bi bi-chat-left-text me-2'></i>Reason
                    </h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                  </div>
                  <div class='modal-body'>
                    <p>$reason</p>
                  </div>
                  <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                  </div>
                </div>
              </div>
            </div>";
        }
    
        echo "</div>";
    } else {
        echo "<div class='alert alert-info text-center'>No pending termination requests.</div>";
    }
    }
}elseif ($userRole == 'Vpo-Checker' || $userRole == 'Vpo-Reviewer') {
    // VPO Checker/Reviewer: show contracts already reviewed by RM
    $pendingRequests = mysqli_query($conn,"
SELECT DISTINCT contract_number, region 
FROM transactional 
WHERE status='Termination Reviewed' AND mainzone='$userMainZone' 
ORDER BY contract_number DESC
");

if($pendingRequests && mysqli_num_rows($pendingRequests) > 0){
echo "<div class='row row-cols-1 row-cols-md-2 g-3'>";
while($row = mysqli_fetch_assoc($pendingRequests)){
    $contractNumber = $row['contract_number'];
    $region = $row['region'];

    // Fetch branches, contract dates, and reason
    $contractData = mysqli_query($conn,"
        SELECT * FROM transactional 
        WHERE region='$region' AND contract_number='$contractNumber'
    ");

    $branches = [];
    $contractStart = '';
    $contractEnd = '';
    $reasonTermination = '';

    while($cRow = mysqli_fetch_assoc($contractData)){
        $branches[] = $cRow['branch'];

        if(!empty($cRow['contract_start']) && (empty($contractStart) || strtotime($cRow['contract_start']) < strtotime($contractStart))) {
            $contractStart = $cRow['contract_start'];
        }
        if(!empty($cRow['contract_end']) && (empty($contractEnd) || strtotime($cRow['contract_end']) > strtotime($contractEnd))) {
            $contractEnd = $cRow['contract_end'];
        }

        if(!empty($cRow['reason_termination'])){
            $reasonTermination = $cRow['reason_termination'];
        }
    }

    // Unique modal ID per contract
    $modalId = "reasonModal_" . $contractNumber;

    echo "<div class='col'>
            <div class='card shadow-sm'>
                <div class='card-body'>
                    <h5 class='card-title text-danger'>Contract: $contractNumber</h5>
                    <p class='mb-1'><i class='bi bi-building me-1'></i>Branch: ".implode(', ', array_unique($branches))."</p>
                    <p class='mb-1'><strong>Contract Start:</strong> ".date('F d, Y', strtotime($contractStart))." | 
                        <strong>Contract End:</strong> ".date('F d, Y', strtotime($contractEnd))."</p>
                    <p><i class='bi bi-geo-alt me-1'></i>Region: $region</p>

                    <!-- Remark Icon -->
                    <button type='button' class='btn btn-outline-danger btn-sm me-2' data-bs-toggle='modal' data-bs-target='#$modalId'>
                        <i class='bi bi-chat-left-text'></i>
                    </button><span class='text-danger'>Reason for Termination</span>

                    <form method='post' class='mt-3' onsubmit='return confirm(\"Accept request for termination?\");'>
                        <input type='hidden' name='region' value='$region'>
                        <input type='hidden' name='contract_number' value='$contractNumber'>
                        <button type='submit' name='check_terminate' class='btn btn-success'>
                            <i class='bi bi-check-circle me-1'></i>Accept Request
                        </button>
                         <!-- Disapprove -->
                        <button type='submit' name='vpo_disapprove_terminate' class='btn btn-danger'>
                            <i class='bi bi-x-circle me-1'></i>Disapprove
                        </button>
                    </form>
                </div>
            </div>
          </div>";

    // Modal for reason
    echo "<div class='modal fade' id='$modalId' tabindex='-1'>
            <div class='modal-dialog modal-dialog-centered'>
                <div class='modal-content'>
                    <div class='modal-header'>
                        <h5 class='modal-title text-danger'>Reason for Termination</h5>
                        <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                    </div>
                    <div class='modal-body'>
                        <p>".(!empty($reasonTermination) ? htmlspecialchars($reasonTermination) : "<em>No reason provided</em>")."</p>
                    </div>
                    <div class='modal-footer'>
                        <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                    </div>
                </div>
            </div>
          </div>";
}
echo "</div>";
    } else {
        $pendingQuery = "SELECT DISTINCT contract_number, region, branch, status, reason_termination 
        FROM transactional 
        WHERE status IN ('Termination Requested','Termination Reviewed','Termination Checked')
        AND mainzone = '$userMainZone'
        ORDER BY status ASC, contract_number DESC";
    
    $pendingResult = mysqli_query($conn, $pendingQuery);
    
    if ($pendingResult && mysqli_num_rows($pendingResult) > 0) {
        echo "<h4 class='fw-bold text-secondary mb-3'>Pending Termination Requests</h4>
        <div class='row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3'>";
    
        while ($pending = mysqli_fetch_assoc($pendingResult)) {
            // Status badge setup
            $displayStatus = '';
            $badgeClass = '';
            if ($pending['status'] == 'Termination Requested') {
                $displayStatus = 'Pending Approval from RM';
                $badgeClass = 'bg-warning text-dark';
            } elseif ($pending['status'] == 'Termination Reviewed') {
                $displayStatus = 'Pending Approval from VPO';
                $badgeClass = 'bg-info text-dark';
            } elseif ($pending['status'] == 'Termination Checked') {
                $displayStatus = 'Pending Approval from VPO Approver';
                $badgeClass = 'bg-info text-dark';
            }
    
            // Escape reason for modal
            $reason = htmlspecialchars($pending['reason_termination'] ?? 'No reason provided');
    
            // Unique modal ID per contract
            $modalId = "reasonModal_" . $pending['contract_number'];
            
            echo "<div class='col'>
                <div class='card shadow-sm border-0 h-100'>
                    <div class='card-body'>
                        <h6 class='card-title text-danger fw-bold'>Contract: {$pending['contract_number']}</h6>
                        <p class='mb-1'><i class='bi bi-geo-alt me-1'></i>Region: {$pending['region']}</p>
                        <p class='mb-1'><i class='bi bi-building me-1'></i>Branch: {$pending['branch']}</p>
                        
                        <!-- Remark Icon -->
                        <button type='button' class='btn btn-outline-danger btn-sm me-2' data-bs-toggle='modal' data-bs-target='#$modalId'>
                            <i class='bi bi-chat-left-text'></i>
                        </button><span class='text-danger me-2'>Reason</span>
                        
                        <span class='badge $badgeClass'>$displayStatus</span>
                    </div>
                </div>
            </div>
    
            <!-- Modal -->
            <div class='modal fade' id='$modalId' tabindex='-1' aria-labelledby='{$modalId}Label' aria-hidden='true'>
              <div class='modal-dialog modal-dialog-centered'>
                <div class='modal-content'>
                  <div class='modal-header'>
                    <h5 class='modal-title text-danger' id='{$modalId}Label'>
                      <i class='bi bi-chat-left-text me-2'></i>Reason for Termination
                    </h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                  </div>
                  <div class='modal-body'>
                    <p>$reason</p>
                  </div>
                  <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                  </div>
                </div>
              </div>
            </div>";
        }
    
        echo "</div>";
        } else {
            echo "<div class='alert alert-info text-center'>No pending termination requests.</div>";
        }
    }
}

elseif($userRole=='Vpo-Approver') {
    // Rm-Reviewer: show all pending requests
    $pendingRequests = mysqli_query($conn,"
    SELECT DISTINCT contract_number, region 
    FROM transactional 
    WHERE status='Termination Checked' AND mainzone='$userMainZone' 
    ORDER BY contract_number DESC
    ");
    
    if($pendingRequests && mysqli_num_rows($pendingRequests) > 0){
    echo "<div class='row row-cols-1 row-cols-md-2 g-3'>";
    while($row = mysqli_fetch_assoc($pendingRequests)){
        $contractNumber = $row['contract_number'];
        $region = $row['region'];
    
        // Fetch branches, contract dates, and reason
        $contractData = mysqli_query($conn,"
            SELECT * FROM transactional 
            WHERE region='$region' AND contract_number='$contractNumber'
        ");
    
        $branches = [];
        $contractStart = '';
        $contractEnd = '';
        $reasonTermination = '';
    
        while($cRow = mysqli_fetch_assoc($contractData)){
            $branches[] = $cRow['branch'];
    
            if(!empty($cRow['contract_start']) && (empty($contractStart) || strtotime($cRow['contract_start']) < strtotime($contractStart))) {
                $contractStart = $cRow['contract_start'];
            }
            if(!empty($cRow['contract_end']) && (empty($contractEnd) || strtotime($cRow['contract_end']) > strtotime($contractEnd))) {
                $contractEnd = $cRow['contract_end'];
            }
    
            if(!empty($cRow['reason_termination'])){
                $reasonTermination = $cRow['reason_termination'];
            }
        }
    
        // Unique modal ID per contract
        $modalId = "reasonModal_" . $contractNumber;
    
        echo "<div class='col'>
                <div class='card shadow-sm'>
                    <div class='card-body'>
                        <h5 class='card-title text-danger'>Contract: $contractNumber</h5>
                        <p class='mb-1'><i class='bi bi-building me-1'></i>Branch: ".implode(', ', array_unique($branches))."</p>
                        <p class='mb-1'><strong>Contract Start:</strong> ".date('F d, Y', strtotime($contractStart))." | 
                            <strong>Contract End:</strong> ".date('F d, Y', strtotime($contractEnd))."</p>
                        <p><i class='bi bi-geo-alt me-1'></i>Region: $region</p>
    
                        <!-- Remark Icon -->
                        <button type='button' class='btn btn-outline-danger btn-sm me-2' data-bs-toggle='modal' data-bs-target='#$modalId'>
                            <i class='bi bi-chat-left-text'></i>
                        </button><span class='text-danger'>Reason for Termination</span>
    
                        <form method='post' class='mt-3' onsubmit='return confirm(\"Accept request for termination?\");'>
                            <input type='hidden' name='region' value='$region'>
                            <input type='hidden' name='contract_number' value='$contractNumber'>
                            <button type='submit' name='approve_terminate' class='btn btn-success'>
                                <i class='bi bi-check-circle me-2'></i>Approve Request
                            </button>
                             <!-- Disapprove -->
                        <button type='submit' name='vpo_disapprove_terminate' class='btn btn-danger'>
                            <i class='bi bi-x-circle me-1'></i>Disapprove
                        </button>
                        </form>
                    </div>
                </div>
              </div>";
    
        // Modal for reason
        echo "<div class='modal fade' id='$modalId' tabindex='-1'>
                <div class='modal-dialog modal-dialog-centered'>
                    <div class='modal-content'>
                        <div class='modal-header'>
                            <h5 class='modal-title text-danger'>Reason for Termination</h5>
                            <button type='button' class='btn-close' data-bs-dismiss='modal'></button>
                        </div>
                        <div class='modal-body'>
                            <p>".(!empty($reasonTermination) ? htmlspecialchars($reasonTermination) : "<em>No reason provided</em>")."</p>
                        </div>
                        <div class='modal-footer'>
                            <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                        </div>
                    </div>
                </div>
              </div>";
    }
    echo "</div>";  
    } else {
        $pendingQuery = "SELECT DISTINCT contract_number, region, branch, status, reason_termination 
        FROM transactional 
        WHERE status IN ('Termination Requested','Termination Reviewed','Termination Checked')
        AND mainzone = '$userMainZone'
        ORDER BY status ASC, contract_number DESC";
    
    $pendingResult = mysqli_query($conn, $pendingQuery);
    
    if ($pendingResult && mysqli_num_rows($pendingResult) > 0) {
        echo "<h4 class='fw-bold text-secondary mb-3'>Pending Termination Requests</h4>
        <div class='row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3'>";
    
        while ($pending = mysqli_fetch_assoc($pendingResult)) {
            // Status badge setup
            $displayStatus = '';
            $badgeClass = '';
            if ($pending['status'] == 'Termination Requested') {
                $displayStatus = 'Pending Approval from RM';
                $badgeClass = 'bg-warning text-dark';
            } elseif ($pending['status'] == 'Termination Reviewed') {
                $displayStatus = 'Pending Approval from VPO';
                $badgeClass = 'bg-info text-dark';
            } elseif ($pending['status'] == 'Termination Checked') {
                $displayStatus = 'Pending Approval from VPO Approver';
                $badgeClass = 'bg-info text-dark';
            }
    
            // Escape reason for modal
            $reason = htmlspecialchars($pending['reason_termination'] ?? 'No reason provided');
    
            // Unique modal ID per contract
            $modalId = "reasonModal_" . $pending['contract_number'];
            
            echo "<div class='col'>
                <div class='card shadow-sm border-0 h-100'>
                    <div class='card-body'>
                        <h6 class='card-title text-danger fw-bold'>Contract: {$pending['contract_number']}</h6>
                        <p class='mb-1'><i class='bi bi-geo-alt me-1'></i>Region: {$pending['region']}</p>
                        <p class='mb-1'><i class='bi bi-building me-1'></i>Branch: {$pending['branch']}</p>
                        
                        <!-- Remark Icon -->
                        <button type='button' class='btn btn-outline-danger btn-sm me-2' data-bs-toggle='modal' data-bs-target='#$modalId'>
                            <i class='bi bi-chat-left-text'></i>
                        </button><span class='text-danger me-1'>Reason</span>
                        
                        <span class='badge $badgeClass'>$displayStatus</span>
                    </div>
                </div>
            </div>
    
            <!-- Modal -->
            <div class='modal fade' id='$modalId' tabindex='-1' aria-labelledby='{$modalId}Label' aria-hidden='true'>
              <div class='modal-dialog modal-dialog-centered'>
                <div class='modal-content'>
                  <div class='modal-header'>
                    <h5 class='modal-title text-danger' id='{$modalId}Label'>
                      <i class='bi bi-chat-left-text me-2'></i>Reason for Termination
                    </h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                  </div>
                  <div class='modal-body'>
                    <p>$reason</p>
                  </div>
                  <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
                  </div>
                </div>
              </div>
            </div>";
        }
    
        echo "</div>";
        } else {
            echo "<div class='alert alert-info text-center'>No pending termination requests.</div>";
        }
    }
}else {
    $pendingQuery = "SELECT DISTINCT contract_number, region, branch, status 
        FROM transactional 
        WHERE status IN ('Termination Requested','Termination Reviewed','Termination Checked')
        ORDER BY status ASC, contract_number DESC";
            $pendingResult = mysqli_query($conn, $pendingQuery);

            if($pendingResult && mysqli_num_rows($pendingResult) > 0) {
            echo "<h4 class='fw-bold text-secondary mb-3'>Pending Termination Requests</h4>
            <div class='row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3'>";

            while($pending = mysqli_fetch_assoc($pendingResult)){
            // Determine contextual status
            $displayStatus = '';
            $badgeClass = '';
            if($pending['status'] == 'Termination Requested'){
                $displayStatus = 'Pending Approval from RM';
                $badgeClass = 'bg-warning text-dark';
                } elseif(in_array($pending['status'], ['Termination Reviewed'])){
                $displayStatus = 'Pending Approval from VPO';
                $badgeClass = 'bg-info text-dark';
                }
                elseif(in_array($pending['status'], ['Termination Checked'])){
                    $displayStatus = 'Pending Approval from VPO Approver';
                    $badgeClass = 'bg-info text-dark';
                    }


            echo "<div class='col'>
            <div class='card shadow-sm border-0 h-100'>
                <div class='card-body'>
                    <h6 class='card-title text-danger'>Contract: {$pending['contract_number']}</h6>
                    <p class='mb-1'><i class='bi bi-geo-alt me-1'></i>Region: {$pending['region']}</p>
                    <p class='mb-1'><i class='bi bi-building me-1'></i>Branch: {$pending['branch']}</p>
                    <span class='badge $badgeClass'>$displayStatus</span>
                </div>
            </div>
            </div>";
            }
            echo "</div>";
        } else {
            echo "<div class='alert alert-info text-center'>No pending termination requests.</div>";
        }
}
?>
</div>

<!-- Enhanced Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4" style="background-color: #fefefe;">
      <div class="modal-body">
        <div class="text-center mb-3">
          <i class="bi bi-box-arrow-right text-danger" style="font-size: 3rem;"></i>
        </div>
        <h5 class="mb-2 text-dark">Logging Out</h5>
        <p class="text-muted mb-3">Please wait while we securely log you out...</p>
        <div class="progress" style="height: 8px;">
          <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 100%;"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.getElementById('logoutLink').addEventListener('click', function (e) {
    e.preventDefault();

    const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'), {
        backdrop: 'static',
        keyboard: false
    });

    logoutModal.show();

    // Simulate logout delay
    setTimeout(() => {
        window.location.href = '../../logout.php';
    }, 2500);
});
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
    </script>
    </body>
</html>
