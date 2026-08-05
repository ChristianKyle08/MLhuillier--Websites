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
            <title>QS - Edit Lessor</title>
            <link rel="stylesheet" href="../../boxicons/css/boxicons.min.css">
            <link rel="preload" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
            <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter+Tight:100,200,300,400,500,600,700,800,900,100i,200i,300i,400i,500i,600i,700i,800i,900i&display=swap"></noscript>
            <link rel="preload" as="style" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB"><link rel="stylesheet" href="../../assets/mobirise/css/mbr-additional.css?v=5XoWyB" type="text/css">
            <!-- custom CSS file link  -->
            <link rel="stylesheet" href="../../css/edit_page.css?v=<?php echo time(); ?>">
            <link rel="stylesheet" href="../../css/responsive.css?v=<?php echo time(); ?>">
            <script src="https://kit.fontawesome.com/30b908cc5a.js" crossorigin="anonymous"></script>
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
            <script src="../../sweetalert2/dist/sweetalert2.all.min.js"></script>
            <link rel="stylesheet" href="../../sweetalert2/dist/sweetalert2.min.css">
            <script src="../../jquery-3.7.1.js"></script>
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
<div class="profile-container">
<form method="POST" action="">
    <input type="search" name="search_lessor" id="search_lessor" value="" placeholder="SEARCH A LESSOR..." autocomplete="off">
    <button type="submit" name="search_btn" id="search_btn">SEARCH</button>

    <?php
    // Get the logged-in user's email from the session
    $user_email = $_SESSION['user_email']; 

    // Fetch the logged-in user's region and area from the user_form table
    $userQuery = "SELECT region, area FROM user_form WHERE email = ?";
    $stmt = $conn->prepare($userQuery);
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $stmt->bind_result($region, $area);
    $stmt->fetch();
    $stmt->close();

    // Pagination setup
    $limit = 10; // Limit of rows per page
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1; // Get current page or default to 1
    $offset = ($page - 1) * $limit; // Calculate offset

    // Check if search button is clicked
    if (isset($_POST['search_btn'])) {
        $lessor = mysqli_real_escape_string($conn, $_POST['search_lessor']);
        if ($row['roles'] == 'HO') {
            // Allow HO to search all data
            $search_query = "SELECT * FROM lessor_profile WHERE first_name LIKE '%$lessor%' 
                             OR middle_name LIKE '%$lessor%' 
                             OR last_name LIKE '%$lessor%' 
                             LIMIT $limit OFFSET $offset";
        } else {
            // Regular users can only search within their region and area
            $search_query = "SELECT * FROM lessor_profile WHERE (first_name LIKE '%$lessor%' 
                             OR middle_name LIKE '%$lessor%' 
                             OR last_name LIKE '%$lessor%') 
                             AND region = '$region' 
                             AND area = '$area' 
                             LIMIT $limit OFFSET $offset";
        }
    } else {
        if ($row['roles'] == 'HO') {
            // Default query for HO to view all data
            $search_query = "SELECT * FROM lessor_profile LIMIT $limit OFFSET $offset";
        } else {
            // Default query for regular users
            $search_query = "SELECT * FROM lessor_profile WHERE region = '$region' 
                             AND area = '$area' 
                             LIMIT $limit OFFSET $offset";
        }
    }    

    $search_result = mysqli_query($conn, $search_query);

    if ($search_result) {
        if (mysqli_num_rows($search_result) > 0) {
            echo '<div class="edit-tbl">';

            while ($row = mysqli_fetch_assoc($search_result)) {
                // Check the user's role
                $userName = $_SESSION['user_email'];
                $rolesQuery = "SELECT roles FROM user_form WHERE email = '$userName'";
                $rolesResult = mysqli_query($conn, $rolesQuery);
            
                if (mysqli_num_rows($rolesResult) > 0) {
                    $rolesRow = mysqli_fetch_assoc($rolesResult); // Different variable for roles
                    if ($rolesRow['roles'] === 'HO') {
                        echo '<div class="profile-row" id="row_' . $row['id'] . '" onclick="highlightRow(' . $row['id'] . ')">';
                        echo '<input type="hidden" name="id" value="' . $row['id'] . '">';
                        echo '<input type="text" name="firstName_' . $row['id'] . '" id="firstName_' . $row['id'] . '" value="' . htmlspecialchars($row['first_name'], ENT_QUOTES, 'UTF-8') . '" autocomplete="off" readonly>';
                        echo '<input type="text" name="middleName_' . $row['id'] . '" id="middleName_' . $row['id'] . '" value="' . htmlspecialchars($row['middle_name'], ENT_QUOTES, 'UTF-8') . '" autocomplete="off" readonly>';
                        echo '<input type="text" name="lastName_' . $row['id'] . '" id="lastName_' . $row['id'] . '" value="' . htmlspecialchars($row['last_name'], ENT_QUOTES, 'UTF-8') . '" autocomplete="off" readonly>';
                        echo '<input type="text" name="gender_' . $row['id'] . '" id="gender_' . $row['id'] . '" value="' . htmlspecialchars($row['gender'], ENT_QUOTES, 'UTF-8') . '" autocomplete="off" readonly>';
                        echo '<input type="text" name="address_' . $row['id'] . '" id="address_' . $row['id'] . '" value="' . htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8') . '" autocomplete="off" readonly>';
                        echo '<input type="text" name="mobile_' . $row['id'] . '" id="mobile_' . $row['id'] . '" value="' . htmlspecialchars($row['mobile_number'], ENT_QUOTES, 'UTF-8') . '" autocomplete="off" readonly>';
                        // Hide the update button for 'HO' role
                        echo '<button type="submit" style="display:none;" name="update_btn" id="update_btn_' . $row['id'] . '" class="update-btn" data-row-id="' . $row['id'] . '">Update</button>';
                    } else {
                        echo '<div class="profile-row" id="row_' . $row['id'] . '" onclick="highlightRow(' . $row['id'] . ')">';
                        echo '<input type="hidden" name="id" value="' . $row['id'] . '">';
                        echo '<input type="text" name="firstName_' . $row['id'] . '" id="firstName_' . $row['id'] . '" value="' . htmlspecialchars($row['first_name'], ENT_QUOTES, 'UTF-8') . '" autocomplete="off" required>';
                        echo '<input type="text" name="middleName_' . $row['id'] . '" id="middleName_' . $row['id'] . '" value="' . htmlspecialchars($row['middle_name'], ENT_QUOTES, 'UTF-8') . '" autocomplete="off" required>';
                        echo '<input type="text" name="lastName_' . $row['id'] . '" id="lastName_' . $row['id'] . '" value="' . htmlspecialchars($row['last_name'], ENT_QUOTES, 'UTF-8') . '" autocomplete="off" required>';
                        echo '<input type="text" name="gender_' . $row['id'] . '" id="gender_' . $row['id'] . '" value="' . htmlspecialchars($row['gender'], ENT_QUOTES, 'UTF-8') . '" autocomplete="off" required>';
                        echo '<input type="text" name="address_' . $row['id'] . '" id="address_' . $row['id'] . '" value="' . htmlspecialchars($row['address'], ENT_QUOTES, 'UTF-8') . '" autocomplete="off" required>';
                        echo '<input type="text" name="mobile_' . $row['id'] . '" id="mobile_' . $row['id'] . '" value="' . htmlspecialchars($row['mobile_number'], ENT_QUOTES, 'UTF-8') . '" autocomplete="off" readonly>';
                        // Show the update button for other roles
                        echo '<button type="submit" name="update_btn" id="update_btn_' . $row['id'] . '" class="update-btn" data-row-id="' . $row['id'] . '" onclick="return validateInputs(' . $row['id'] . ')">Update</button>';
                    }
                }
                echo '</div>';
            }

            echo '</div>';
            echo '<input type="hidden" name="selected_row" id="selected_row" value="' . (isset($_POST['selected_row']) ? htmlspecialchars($_POST['selected_row']) : '') . '">';
            $userName = $_SESSION['user_email'];
            $rolesQuery = "SELECT roles FROM user_form WHERE email = '$userName'";
            $rolesResult = mysqli_query($conn, $rolesQuery);

            if (mysqli_num_rows($rolesResult) > 0) {
                $rolesRow = mysqli_fetch_assoc($rolesResult); // Correctly using $rolesRow for roles
                if ($rolesRow['roles'] == 'HO') {
                    // Pagination controls for HO role: no region or area filtering
                    $total_query = isset($_POST['search_btn']) 
                        ? "SELECT COUNT(*) AS total FROM lessor_profile WHERE (first_name LIKE '%$lessor%' OR middle_name LIKE '%$lessor%' OR last_name LIKE '%$lessor%')"
                        : "SELECT COUNT(*) AS total FROM lessor_profile";
                } else {
                    // Pagination controls for non-HO role: filtering by region and area
                    $total_query = isset($_POST['search_btn']) 
                        ? "SELECT COUNT(*) AS total FROM lessor_profile WHERE (first_name LIKE '%$lessor%' OR middle_name LIKE '%$lessor%' OR last_name LIKE '%$lessor%') AND region = '$region' AND area = '$area'"
                        : "SELECT COUNT(*) AS total FROM lessor_profile WHERE region = '$region' AND area = '$area'";
                }

                // Execute the total query
                $total_result = mysqli_query($conn, $total_query);
                if ($total_result) {
                    $total_row = mysqli_fetch_assoc($total_result);
                    $total_records = $total_row['total'];
                    $total_pages = ceil($total_records / $limit);
                } else {
                    // Handle query failure (optional)
                    echo "Error executing query: " . mysqli_error($conn);
                }
            }

            echo '<div class="pagination">';

            // Previous button
            if ($page > 1) {
                echo '<button type="submit" name="page" value="' . ($page - 1) . '">Previous</button>';
            }

            // Page numbers
            $start = max(1, $page - 5);
            $end = min($total_pages, $page + 4);

            if ($start > 1) {
                echo '<button type="submit" name="page" value="1">1</button>';
                if ($start > 2) {
                    echo '...';
                }
            }

            for ($i = $start; $i <= $end; $i++) {
                echo '<button type="submit" name="page" value="' . $i . '" ' . ($i == $page ? 'class="active"' : '') . '>' . $i . '</button>';
            }

            if ($end < $total_pages) {
                if ($end < $total_pages - 1) {
                    echo '...';
                }
                echo '<button type="submit" name="page" value="' . $total_pages . '">' . $total_pages . '</button>';
            }

            // Next button
            if ($page < $total_pages) {
                echo '<button type="submit" name="page" value="' . ($page + 1) . '">Next</button>';
            }

            echo '</div>';
        } else {
            echo 'No results found.';
        }
    } else {
        echo 'Error executing the search query: ' . mysqli_error($conn);
    }

    // Update functionality
    if (isset($_POST['update_btn'])) {
        $selected_row = mysqli_real_escape_string($conn, $_POST['selected_row']);
        $id = mysqli_real_escape_string($conn, $_POST['id']);
        $first_name = mysqli_real_escape_string($conn, $_POST['firstName_' . $selected_row]);
        $middle_name = mysqli_real_escape_string($conn, $_POST['middleName_' . $selected_row]);
        $last_name = mysqli_real_escape_string($conn, $_POST['lastName_' . $selected_row]);
        $gender = mysqli_real_escape_string($conn, $_POST['gender_' . $selected_row]);
        $address = mysqli_real_escape_string($conn, $_POST['address_' . $selected_row]);
    
        $update_query = "UPDATE lessor_profile SET
                        first_name = '$first_name',
                        middle_name = '$middle_name',
                        last_name = '$last_name',
                        gender = '$gender',
                        address = '$address'
                        WHERE id = '$selected_row'";
    
        $update_result = mysqli_query($conn, $update_query);
    
        if ($update_result) {
            echo '<script>
                    Swal.fire({
                        icon: "success",
                        title: "Record Updated",
                        text: "The record has been updated successfully.",
                        confirmButtonText: "OK"
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "edit_lessor_profile.php";  // Redirect to edit_lessor_profile.php
                        }
                    });
                </script>';
        } else {
            echo '<script>
                    Swal.fire({
                        icon: "error",
                        title: "Error Updating Record",
                        text: "' . mysqli_error($conn) . '",
                    });
                </script>';
        }
    }
    
    ?>
</form>

<script>
// JavaScript to validate inputs
function validateInputs(rowId) {
    const fields = [
        `firstName_${rowId}`,
        `middleName_${rowId}`,
        `lastName_${rowId}`,
        `gender_${rowId}`,
        `address_${rowId}`,
    ];

    for (let i = 0; i < fields.length; i++) {
        const input = document.getElementById(fields[i]);
        if (input && input.value.trim() === '') {
            alert('Please fill out all fields before updating.');
            input.focus();
            return false;
        }
    }

    return true; // Allow submission if all fields are valid
}

function highlightRow(rowId) {
    // Remove 'selected-row' class from all rows
    var rows = document.querySelectorAll('.profile-row');
    rows.forEach(function (row) {
        row.classList.remove('selected-row');
    });

    // Add 'selected-row' class to the clicked row
    var selectedRow = document.getElementById('row_' + rowId);
    if (selectedRow) {
        selectedRow.classList.add('selected-row');
    }

    // Set the selected row ID to the hidden input field
    document.getElementById('selected_row').value = rowId;
}

function limitMobileNumber(input) {
  // Allow backspace and delete keys
  if (event.keyCode === 8 || event.keyCode === 46) {
    return;
  }

  // Replace any non-numeric characters except "+"
  input.value = input.value.replace(/[^\d+]/g, '');

  // Limit length to 13 characters (including "+" and 10 digits)
  if (input.value.length > 11) {
    input.value = input.value.slice(0, 11);
  }
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
    </body>
</html>
