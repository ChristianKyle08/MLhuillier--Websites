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
// Check if extract_file is set in the POST request
if (isset($_POST['extract_file'])) {
    if (isset($_POST['startDate'], $_POST['endDate'])) {
        $start_date = $_POST['startDate'];
        $end_date = $_POST['endDate'];
        $branch = $_POST['branch'];
        $region = $_POST['region'];
        $userName = $_SESSION['user_name'];

        $query = "SELECT * FROM transactional 
                  WHERE (extract_request_status IS NULL OR extract_request_status = '')
                  AND (status != 'Terminated' AND status != 'Cancelled')
                  AND DATE_FORMAT(transaction_date, '%Y-%m') >= '$start_date'
                  AND DATE_FORMAT(transaction_date, '%Y-%m') <= '$end_date'
                  AND mode_of_payment = 'PAYMENT SOLUTION'";

        if ($branch !== "ALL" && empty($region)) {
            $query .= " AND branch = '$branch'";
        } elseif ($branch === "ALL" && !empty($region)) {
            $query .= " AND region = '$region'";
        } elseif (!empty($branch) && !empty($region)) {
            $query .= " AND region = '$region' AND branch = '$branch'";
        }

        $result = mysqli_query($conn, $query);
        if (!$result) {
            die('Query failed: ' . mysqli_error($conn));
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="VPO_FILE_UPLOAD_' . date("Ymd") . '.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'No', 'First_Name', 'Middle_Name', 'Last_Name', 
            'Gender', 'Branch_Name', 'Branch_ID', 'Mobile_Number', 
            'Monthly_Due', 'Amount'
        ]);

        $row_number = 1;

        while ($row_data = mysqli_fetch_assoc($result)) {
            // === Authorized Representative name (new → recent → original) ===
            $first_name = !empty($row_data['new_authorize_firstname']) ? $row_data['new_authorize_firstname']
                         : (!empty($row_data['authorize_firstName']) ? $row_data['authorize_firstName'] : ($row_data['authorize_firstName'] ?? $row_data['l1_firstname']));
            $middle_name = !empty($row_data['new_authorize_middlename']) ? $row_data['new_authorize_middlename']
                          : (!empty($row_data['authorize_middleName']) ? $row_data['authorize_middleName'] : ($row_data['authorize_middleName'] ?? $row_data['l1_middlename']));
            $last_name = !empty($row_data['new_authorize_lastname']) ? $row_data['new_authorize_lastname']
                        : (!empty($row_data['authorize_lastName']) ? $row_data['authorize_lastName'] : ($row_data['authorize_lastName'] ?? $row_data['l1_lastname']));

            // === Fallback to lessor if authorize completely empty ===
            if (empty($first_name) && empty($last_name)) {
                $first_name  = !empty($row_data['new_l1_firstname']) ? $row_data['new_l1_firstname'] 
                              : (!empty($row_data['l1_firstname']) ? $row_data['l1_firstname'] : ($row_data['l1_firstname'] ?? ''));
                $middle_name = !empty($row_data['new_l1_middlename']) ? $row_data['new_l1_middlename'] 
                              : (!empty($row_data['l1_middlename']) ? $row_data['l1_middlename'] : ($row_data['l1_middlename'] ?? ''));
                $last_name   = !empty($row_data['new_l1_lastname']) ? $row_data['new_l1_lastname'] 
                              : (!empty($row_data['l1_lastname']) ? $row_data['l1_lastname'] : ($row_data['l1_lastname'] ?? ''));
            }

            // Clean spaces and uppercase
            $first_name  = strtoupper(trim($first_name));
            $middle_name = strtoupper(trim($middle_name));
            $last_name   = strtoupper(trim($last_name));

            // Amount formatting
            $amount_lessor = str_replace(',', '', strtoupper($row_data['edit_amount_lessor']));

            // Due date logic
            $due_date = (!empty($row_data['new_due_date']) && $row_data['dueDate_request_status'] === 'Approved') 
                        ? $row_data['new_due_date'] 
                        : $row_data['transaction_date'];

            // Mobile number logic
            if (!empty($row_data['authorize_mobileNumber'])) {
                $mobile_number = strtoupper($row_data['authorize_mobileNumber']);
            } elseif (!empty($row_data['new_mobile_number_l1']) && $row_data['mobile_request_status'] === 'Approved') {
                $mobile_number = strtoupper($row_data['new_mobile_number_l1']);
            } else {
                $mobile_number = strtoupper($row_data['mobile_number_l1']);
            }

            fputcsv($output, [
                $row_number++, 
                $first_name, 
                $middle_name,
                $last_name, 
                strtoupper($row_data['l1_gender']), 
                strtoupper($row_data['branch']), 
                strtoupper($row_data['branch_id']), 
                $mobile_number, 
                strtoupper(date('m/d/Y', strtotime($due_date))), 
                $amount_lessor
            ]);
        }

        $extract_update = "UPDATE transactional 
                           SET extract_request_status = 'Extracted', 
                               exported_by = '$userName',
                               extraction_date = CURDATE()
                           WHERE (extract_request_status IS NULL OR extract_request_status = '')
                           AND (status != 'Terminated' AND status != 'Cancelled')
                           AND DATE_FORMAT(transaction_date, '%Y-%m') >= '$start_date'
                           AND DATE_FORMAT(transaction_date, '%Y-%m') <= '$end_date'
                           AND mode_of_payment = 'PAYMENT SOLUTION'";

        if ($branch !== "ALL" && empty($region)) {
            $extract_update .= " AND branch = '$branch'";
        } elseif ($branch === "ALL" && !empty($region)) {
            $extract_update .= " AND region = '$region'";
        } elseif (!empty($branch) && !empty($region)) {
            $extract_update .= " AND region = '$region' AND branch = '$branch'";
        }

        if (!mysqli_query($conn, $extract_update)) {
            die('Update query failed: ' . mysqli_error($conn));
        }

        fclose($output);
        mysqli_close($conn);
        exit();
    }
}
$userMainzone = mysqli_real_escape_string($conn, $_SESSION['mainzone'] ?? '');
$sql = "
    SELECT COUNT(*) AS pending_count
    FROM (
        SELECT 
            t.branch_id,
            MIN(t.transaction_date) AS next_pending_date
        FROM transactional t
        LEFT JOIN (
            SELECT 
                branch_id, 
                MAX(transaction_date) AS last_extracted_date
            FROM transactional
            WHERE 
                extract_request_status = 'extracted'
                AND mode_of_payment = 'PAYMENT SOLUTION'
            GROUP BY branch_id
        ) e ON e.branch_id = t.branch_id
        WHERE 
            t.mode_of_payment = 'PAYMENT SOLUTION'
            AND (t.extract_request_status IS NULL OR t.extract_request_status = '')
            AND t.mainzone = '$userMainzone' /* <--- ADDED CONDITION HERE */
            AND (
                e.last_extracted_date IS NULL 
                OR t.transaction_date > e.last_extracted_date
            )
        GROUP BY t.branch_id
        HAVING next_pending_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH)
    ) x
";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$pendingCount = (int)($row['pending_count'] ?? 0);

// ================================
// FETCH PENDING TRANSACTIONS LIST
// ================================
// ================================
// FETCH PENDING TRANSACTIONS LIST
// ================================
$pendingList = [];

$listSql = "
    SELECT 
        t.branch_id,
        t.branch,
        MAX(e.last_extracted_date) AS last_extracted_date,
        MIN(t.transaction_date) AS next_pending_date
    FROM transactional t
    LEFT JOIN (
        SELECT branch_id, MAX(transaction_date) AS last_extracted_date
        FROM transactional
        WHERE extract_request_status = 'extracted'
          AND mode_of_payment = 'PAYMENT SOLUTION'
        GROUP BY branch_id
    ) e ON e.branch_id = t.branch_id
    WHERE 
        t.mode_of_payment = 'PAYMENT SOLUTION'
        AND (t.extract_request_status IS NULL OR t.extract_request_status = '')
        AND t.mainzone = '$userMainzone' /* <--- ADDED CONDITION HERE */
        AND (
            e.last_extracted_date IS NULL 
            OR t.transaction_date > e.last_extracted_date
        )
    GROUP BY t.branch_id, t.branch
    -- Ensures consistency with your pending_count query
    HAVING next_pending_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH)
    ORDER BY next_pending_date ASC
";

$listResult = mysqli_query($conn, $listSql);
if ($listResult) {
    while ($row = mysqli_fetch_assoc($listResult)) {
        $pendingList[] = $row;
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
            <link rel="shortcut icon" href="../../assets/images/rental_logo.png" type="image/x-icon">
            <meta name="description" content="">
            <title>ML Rental - Create Data</title>
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
        <style>
            /* Notification Wrapper */
.notif-wrapper {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 10px;
}

/* Card */
.notif-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 18px;
    background: #ffffff;
    border-radius: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    cursor: pointer;
    transition: all 0.25s ease;
}

.notif-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(0,0,0,0.12);
}

/* Icon */
.notif-icon {
    position: relative;
    font-size: 26px;
    color: #0d6efd;
}

/* Badge */
.notif-badge {
    position: absolute;
    top: -6px;
    right: -8px;
    background: #dc3545;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    padding: 3px 7px;
    border-radius: 20px;
    animation: pulse 1.4s infinite;
}

/* Text */
.notif-text {
    line-height: 1.2;
}

.notif-title {
    font-weight: 600;
    font-size: 14px;
    color: #212529;
}

.notif-subtitle {
    font-size: 12px;
    color: #6c757d;
}

/* Pulse Animation */
@keyframes pulse {
    0% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(220,53,69,0.6);
    }
    70% {
        transform: scale(1.1);
        box-shadow: 0 0 0 10px rgba(220,53,69,0);
    }
    100% {
        transform: scale(1);
    }
}

        </style>
    <body>
    <?php include ('navbar.php'); ?>
    <div id="mainContent">
        <button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
            <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
            <span class="fw-normal">Menu</span>
        </button>
<script>
    const toggleBtn = document.getElementById('toggleSidebar');
  const sidebar = document.getElementById('sidebarMenu');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
  });
document.getElementById('logoutLink')?.addEventListener('click', function (e) {
  e.preventDefault();
  const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'), {
    backdrop: 'static',
    keyboard: false
  });
  logoutModal.show();
  setTimeout(() => window.location.href = '../../logout.php', 2500);
});
</script>
        <div class="container py-1">
    <form action="" method="POST" id="ledger_form">
     <!-- Modern Notification -->
        <div class="notif-wrapper">

            <div class="notif-card" data-bs-toggle="modal" data-bs-target="#pendingModal">
                <div class="notif-icon">
                    <i class="bi bi-bell-fill"></i>

                    <?php if ($pendingCount > 0): ?>
                        <span class="notif-badge"><?= $pendingCount ?></span>
                    <?php endif; ?>
                </div>

                <div class="notif-text">
                    <?php if ($pendingCount > 0): ?>
                        <div class="notif-title">Pending Extraction</div>
                        <div class="notif-subtitle">
                            <?= $pendingCount ?> Branch<?= $pendingCount > 1 ? 'es' : '' ?> remaining
                        </div>
                    <?php else: ?>
                        <div class="notif-title text-success">All Clear 🎉</div>
                        <div class="notif-subtitle">No pending branches</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <div class="modal fade" id="pendingModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 shadow">

            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title">
                    <i class="bi bi-clock-history text-danger me-2"></i>
                    Extraction Timeline per Branch
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <?php if (!empty($pendingList)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Branch Name</th>
                                    <th>Extraction History</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingList as $row): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($row['branch']) ?></span>
                                            <div class="text-muted x-small">ID: <?= htmlspecialchars($row['branch_id']) ?></div>
                                        </td>
                                        <td>
                                            <div class="mb-1">
                                                <small class="text-muted uppercase fw-semibold" style="font-size: 10px;">
                                                    <i class="bi bi-arrow-left-circle me-1"></i>Last Extracted
                                                </small><br>
                                                <span class="text-secondary">
                                                    <?= !empty($row['last_extracted_date']) 
                                                        ? date('M d, Y', strtotime($row['last_extracted_date'])) 
                                                        : '<em class="text-muted">No prior records</em>' ?>
                                                </span>
                                            </div>

                                            <div>
                                                <small class="text-danger uppercase fw-semibold" style="font-size: 10px;">
                                                    <i class="bi bi-arrow-right-circle-fill me-1"></i>Next to Extract
                                                </small><br>
                                                <span class="fw-bold text-danger" style="font-size: 1.05rem;">
                                                    <?= date('M d, Y', strtotime($row['next_pending_date'])) ?>
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-check2-all fs-1 text-success"></i>
                        <p class="mt-2 fw-semibold">All branches are up to date!</p>
                        <small>No pending transactions found for the next 3 months.</small>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>

        </div>
    </div>
</div>

        <div class="card shadow-sm border-0 rounded-3 mb-4">
            <div class="card-header bg-danger text-white d-flex align-items-center">
                <i class="bi bi-funnel-fill me-2"></i>
                <h5 class="mb-0 text-white">Filter Transactions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <!-- Region -->
                    <div class="col-md-3">
                        <label for="region" class="form-label">
                            <i class="bi bi-geo-alt-fill me-1 text-danger"></i> Region
                        </label>
                        <select name="region" id="region" class="form-select" onchange="this.form.submit()">
                            <option value="">Select Region</option>
                            <?php
                            $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);
                            $transactional = "SELECT DISTINCT region FROM transactional WHERE region != '' 
                                AND (extract_request_status IS NULL OR extract_request_status = '') 
                                AND mode_of_payment = 'PAYMENT SOLUTION'
                                AND mainzone IN (SELECT DISTINCT mainzone FROM user_form WHERE username = '$user_email' OR email = '$user_email') 
                                ORDER BY region ASC";
                            $stmt = mysqli_prepare($conn, $transactional);

                            if ($stmt) {
                                mysqli_stmt_execute($stmt);
                                $resultRegion = mysqli_stmt_get_result($stmt);
                                if ($resultRegion) {
                                    while ($rowRegion = mysqli_fetch_assoc($resultRegion)) {
                                        $selected = (isset($_POST['region']) && $_POST['region'] == $rowRegion['region']) ? 'selected' : '';
                                        echo "<option value='" . $rowRegion['region'] . "' $selected>" . $rowRegion['region'] . "</option>";
                                    }
                                } else {
                                    echo "No regions found.";
                                }
                                mysqli_stmt_close($stmt);
                            } else {
                                echo "Error in preparing statement: " . mysqli_error($conn);
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-md-3">
    <label for="branch" class="form-label">
        <i class="bi bi-building me-1 text-danger"></i> Branch
    </label>
    <select name="branch" id="branch" class="form-select" required onchange="updateKpxCode(this)">
        <option value="ALL">ALL BRANCHES</option>
        <?php
        // Base query always has 2 placeholders: username and email
        $params = "ss"; 
        $query = "SELECT DISTINCT branch, branch_id FROM transactional 
                WHERE branch != '' 
                AND (extract_request_status IS NULL OR extract_request_status = '') 
                AND mode_of_payment = 'PAYMENT SOLUTION'
                AND branch NOT IN (
                    SELECT DISTINCT branch 
                    FROM transactional 
                    WHERE status = 'Terminated'
                )
                AND mainzone IN (SELECT DISTINCT mainzone FROM user_form WHERE username = ? OR email = ?)";

        // If region is selected, add the 3rd placeholder
        if (isset($_POST['region']) && !empty($_POST['region'])) {
            $query .= " AND region = ? ";
            $params = "sss"; // Total: username, email, region
        }

        $query .= " ORDER BY branch ASC";

        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {
            if ($params == "sss") {
                // Match the 3 placeholders: 'sss'
                mysqli_stmt_bind_param($stmt, 'sss', $user_email, $user_email, $_POST['region']);
            } else {
                // Match the 2 placeholders: 'ss'
                mysqli_stmt_bind_param($stmt, 'ss', $user_email, $user_email);
            }

            mysqli_stmt_execute($stmt);
            $resultBranch = mysqli_stmt_get_result($stmt);

            if ($resultBranch && mysqli_num_rows($resultBranch) > 0) {
                while ($rowBranch = mysqli_fetch_assoc($resultBranch)) {
                    $selected = (isset($_POST['branch']) && $_POST['branch'] == $rowBranch['branch']) ? 'selected' : '';
                    echo "<option value='" . htmlspecialchars($rowBranch['branch']) . "' data-branch-id='" . htmlspecialchars($rowBranch['branch_id']) . "' $selected>" . htmlspecialchars($rowBranch['branch']) . "</option>";
                }
            } else {
                echo "<option disabled>No branches found</option>";
            }

            mysqli_stmt_close($stmt);
        } else {
            echo "<option disabled>SQL Error: " . mysqli_error($conn) . "</option>";
        }
        ?>
    </select>
</div>


                    <!-- Start Date -->
                    <div class="col-md-2">
                        <label for="startDate" class="form-label">
                            <i class="bi bi-calendar-event me-1 text-danger"></i> Start
                        </label>
                        <input type="month" name="startDate" id="startDate" class="form-control" 
                            value="<?php echo isset($_POST['startDate']) ? $_POST['startDate'] : '' ?>" required>
                    </div>

                    <!-- End Date -->
                    <div class="col-md-2">
                        <label for="endDate" class="form-label">
                            <i class="bi bi-calendar-event-fill me-1 text-danger"></i> End
                        </label>
                        <input type="month" name="endDate" id="endDate" class="form-control" 
                            value="<?php echo isset($_POST['endDate']) ? $_POST['endDate'] : '' ?>" required>
                    </div>

                    <!-- Button -->
                    <div class="col-md-2 text-end">
                        <button type="submit" name="proceed_btn" id="proceed_btn" class="btn btn-danger w-100">
                            <i class="bi bi-funnel-fill me-1"></i> Filter
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <input type="hidden" id="selected_id_display" name="selectedID" value="">

<?php
// Ensure that the form is submitted
if (isset($_POST['request_extract'])) {
    $userName = $_SESSION['user_name'];

    if (isset($_POST['startDate'], $_POST['endDate'])) {
        $start_date = $_POST['startDate'];
        $end_date = $_POST['endDate'];
        $branch = $_POST['branch'];
        $region = $_POST['region'];

        // Prepare the SQL query to fetch transactions
        $query = "SELECT * FROM transactional 
                  WHERE (extract_request_status IS NULL OR extract_request_status = '')
                  AND (status != 'Terminated' OR status != 'Cancelled') 
                  AND mode_of_payment = 'PAYMENT SOLUTION'
                  AND DATE_FORMAT(transaction_date, '%Y-%m') BETWEEN ? AND ?";

        // Append branch and region conditions based on selected options
         if ($branch !== "ALL" && empty($region)) {
            $query .= " AND branch = ?";
        }elseif ($branch === "ALL" && empty($region)) {
            $query .= "";
        }elseif ($branch === "ALL" && !empty($region)) {
            $query .= " AND region = ?";
        }elseif (!empty($branch) && !empty($region)) {
            $query .= " AND region = ? AND branch = ?";
        }

        // Prepare the statement
        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {
            // Bind parameters to the prepared statement based on conditions
            if ($branch !== "ALL" && empty($region)) {
                mysqli_stmt_bind_param($stmt, 'sss', $start_date, $end_date, $branch);
            } elseif ($branch === "ALL" && empty($region)) {
                mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
            } elseif ($branch === "ALL" && !empty($region)) {
                mysqli_stmt_bind_param($stmt, 'sss', $start_date, $end_date, $region);
            } elseif (!empty($branch) && !empty($region)) {
                mysqli_stmt_bind_param($stmt, 'ssss', $start_date, $end_date, $region, $branch);
            }

            // Execute the statement
            mysqli_stmt_execute($stmt);

            // Get result set
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $totalAmount = 0; // Initialize total amount variable
                $output = ''; // Initialize output variable

                // Start constructing the table output
                $output .= "
                    <div class='table_wrap' style='display:none;'>
                        <table class='contract_lg_table' id='contract_lg_table'>
                            <thead>
                                <tr>
                                    <th>Transaction Date</th>
                                    <th>Region</th>
                                    <th>Area</th>
                                    <th>Branch ID</th>
                                    <th>Branch</th>
                                    <th>Lessor Name</th>
                                    <th>Authorize to Claim</th>
                                    <th>Monthly Rental</th>
                                </tr>
                            </thead>
                            <tbody>";

                // Fetch and display each transaction row
                while ($data = mysqli_fetch_assoc($result)) {
                    // Use new_due_date if it exists; otherwise, use transaction_date
                    $dueDate = (!empty($row['new_due_date']) && $row['dueDate_request_status'] === 'Approved') 
                                       ? $row['new_due_date'] 
                                       : $row['transaction_date'];
                
                    $output .= "<tr>
                        <td>" . date('F j, Y', strtotime($dueDate)) . "</td>
                        <td>" . htmlspecialchars($data['region']) . "</td>
                        <td>" . htmlspecialchars($data['area']) . "</td>
                        <td>" . htmlspecialchars($data['branch_id']) . "</td>
                        <td>" . htmlspecialchars($data['branch']) . "</td>
                        <td>" . htmlspecialchars($data['l1_firstname']) . ' ' . htmlspecialchars($data['l1_middlename']) . ' ' . htmlspecialchars($data['l1_lastname']) . "</td>
                        <td>" . htmlspecialchars($data['authorize_firstName']) . ' ' . htmlspecialchars($data['authorize_middleName']) . ' ' . htmlspecialchars($data['authorize_lastName']) . "</td>
                        <td> ₱ " . number_format($data['edit_amount_lessor'], 2) . "</td>
                    </tr>";
                
                    // Accumulate the amount to calculate total
                    $totalAmount += $data['edit_amount_lessor'];
                }
                

                // Format the total amount
                $totalAmountFormatted = number_format($totalAmount, 2);

                // Close the table
                $output .= "</tbody></table></div>";

                // Display the table output
                echo $output;
            } else {
                echo "<div>No transactions found.</div>";
            }
        } else {
            echo "Error in preparing select statement: " . mysqli_error($conn);
        }

        // Close the prepared statement
        mysqli_stmt_close($stmt);

        // JavaScript for SweetAlert modal
echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
            title: "Confirm Details",
            html: `<input id="amountInput" name="amountInput" class="swal2-input" type="text" value="₱ ' . $totalAmountFormatted . '" readonly>
                   <input type="text" id="startDate" class="swal2-input" placeholder="Start Date" value="' . date('F j, Y', strtotime($start_date)) . '" readonly>
                   <input type="text" id="endDate" class="swal2-input" placeholder="End Date" value="' . date('F j, Y', strtotime($end_date)) . '" readonly>
                   <input type="text" id="region" class="swal2-input" placeholder="Region" value="' . $region . '" readonly>
                   <input type="text" id="branch" class="swal2-input" placeholder="Branch" value="' . $branch . '" readonly>
                   `,
            showCancelButton: true,
            confirmButtonText: "Download File",
            cancelButtonText: "Cancel",
            allowOutsideClick: false,
            preConfirm: () => {
                const amount = document.getElementById("amountInput").value;
                const startDate = document.getElementById("startDate").value;
                const endDate = document.getElementById("endDate").value;
                const region = document.getElementById("region").value;
                const branch = document.getElementById("branch").value;
                if (!amount) {
                    Swal.showValidationMessage("Please enter Total Amount");
                }
                return {amount: amount, startDate: startDate, endDate: endDate, region: region, branch: branch };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const {amount, startDate, endDate, region, branch } = result.value;
                const url = `download.php?start_date=${encodeURIComponent(startDate)}&end_date=${encodeURIComponent(endDate)}&amount=${encodeURIComponent(amount)}&region=${encodeURIComponent(region)}&branch=${encodeURIComponent(branch)}`;
                window.location.href = url;
            }
        });
    });
</script>';

        exit; // Stop further PHP execution
    }
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['proceed_btn'])) {

    if (isset($_POST['startDate'], $_POST['endDate'])) {
        $start_date = $_POST['startDate'];
        $end_date = $_POST['endDate'];
        $branch = $_POST['branch'];
        $region = $_POST['region'];

        $query = "SELECT * FROM transactional 
                  WHERE DATE_FORMAT(transaction_date, '%Y-%m') >= ?
                  AND DATE_FORMAT(transaction_date, '%Y-%m') <= ?
                  AND mode_of_payment = 'PAYMENT SOLUTION'";

        // Add conditions based on selected filters
        if ($branch !== "ALL" && empty($region)) {
            $query .= " AND branch = ?";
        } elseif ($branch === "ALL" && !empty($region)) {
            $query .= " AND region = ?";
        } elseif (!empty($branch) && !empty($region)) {
            $query .= " AND region = ? AND branch = ?";
        }

        $stmt = mysqli_prepare($conn, $query);

        if ($stmt) {
            if ($branch !== "ALL" && empty($region)) {
                mysqli_stmt_bind_param($stmt, 'sss', $start_date, $end_date, $branch);
            } elseif ($branch === "ALL" && empty($region)) {
                mysqli_stmt_bind_param($stmt, 'ss', $start_date, $end_date);
            } elseif ($branch === "ALL" && !empty($region)) {
                mysqli_stmt_bind_param($stmt, 'sss', $start_date, $end_date, $region);
            } elseif (!empty($branch) && !empty($region)) {
                mysqli_stmt_bind_param($stmt, 'ssss', $start_date, $end_date, $region, $branch);
            }

            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $totalAmount = 0;

                echo "<div class='card shadow-sm border-0 rounded-3 mt-4'>
                        <div class='card-header bg-light border-bottom'>
                            <h5 class='mb-0 text-danger'><i class='bi bi-table me-2'></i> Transaction Records</h5>
                        </div>
                        <div class='card-body'>";

                echo "<div class='d-flex justify-content-between align-items-center mb-3'>
                        <h6 class='fw-bold'>Total Amount: ₱ <span id='totalAmountDisplay'>0.00</span></h6>
                        <button type='submit' id='extract_file' name='extract_file' class='btn btn-sm btn-danger'>
                            <i class='bi bi-download me-1'></i> Download File
                        </button>
                    </div>";

                echo "<div class='table-responsive'>
                        <table class='table table-striped table-hover align-middle'>
                        <thead class='table-danger text-dark'>
                        <tr>
                            <th>Transaction Date</th>
                            <th>Region</th>
                            <th>Area</th>
                            <th>Branch ID</th>
                            <th>Branch Code</th>
                            <th>Branch Name</th>
                            <th>Lessor Name</th>
                            <th>Authorized to Claim</th>
                            <th>Monthly Rental</th>
                            <th>Billing Status</th>
                            <th>Extract Status</th>
                        </tr>
                        </thead>
                        <tbody>";

                while ($row = mysqli_fetch_assoc($result)) {

                    $billingStatus = htmlspecialchars($row['status'] ?? '');
                    $extractStatus = htmlspecialchars($row['extract_request_status'] ?? '');

                    // ✅ Row Highlight Logic
                    if ($billingStatus === 'Terminated') {
                        $rowClass = 'table-danger'; // 🔴
                    } elseif ($billingStatus === 'Cancelled') {
                        $rowClass = 'table-warning'; // 🟡
                    } elseif ($extractStatus === 'Extracted') {
                        $rowClass = 'table-success'; // ✅ 🟩
                    } else {
                        $rowClass = '';
                    }

                    // ✅ Due Date Logic
                    $dueDate = (!empty($row['new_due_date']) && ($row['dueDate_request_status'] ?? '') === 'Approved')
                                ? $row['new_due_date']
                                : ($row['transaction_date'] ?? '');
                    $formattedDueDate = (!empty($dueDate) && strtotime($dueDate))
                                ? date('F j, Y', strtotime($dueDate))
                                : '';

                    // ✅ Lessor & Authorized Name
                    $lessorName = trim(preg_replace('/\s+/', ' ', 
                        ($row['new_l1_firstname'] ?? $row['l1_firstname'] ?? '') . ' ' .
                        ($row['new_l1_middlename'] ?? $row['l1_middlename'] ?? '') . ' ' .
                        ($row['new_l1_lastname'] ?? $row['l1_lastname'] ?? '')
                    ));

                    $authorizeName = trim(preg_replace('/\s+/', ' ', 
                        ($row['new_authorize_firstname'] ?? $row['authorize_firstName'] ?? '') . ' ' .
                        ($row['new_authorize_middlename'] ?? $row['authorize_middleName'] ?? '') . ' ' .
                        ($row['new_authorize_lastname'] ?? $row['authorize_lastName'] ?? '')
                    ));

                    echo "<tr class='$rowClass'>
                            <td>{$formattedDueDate}</td>
                            <td>" . htmlspecialchars($row['region'] ?? '') . "</td>
                            <td>" . htmlspecialchars($row['area'] ?? '') . "</td>
                            <td>" . htmlspecialchars($row['branch_id'] ?? '') . "</td>
                            <td>" . htmlspecialchars($row['branch_code'] ?? '') . "</td>
                            <td>" . htmlspecialchars($row['branch'] ?? '') . "</td>
                            <td>{$lessorName}</td>
                            <td>{$authorizeName}</td>
                            <td>₱ " . number_format((float)($row['edit_amount_lessor'] ?? 0), 2) . "</td>
                            <td>{$billingStatus}</td>
                            <td>{$extractStatus}</td>
                        </tr>";

                    if ($extractStatus !== 'Extracted') {
                        $totalAmount += (float)($row['amount_lessor'] ?? 0);
                    }
                }

                echo "</tbody></table></div></div></div>";

                echo "<input type='hidden' name='total_amount' id='total_amount' value='" . $totalAmount . "'>";

                echo "<script>
                        document.getElementById('totalAmountDisplay').innerText = '" . number_format($totalAmount, 2) . "';
                      </script>";

            } else {
                echo "<div class='alert alert-warning text-center fw-bold mt-3'>NO TRANSACTIONS FOUND!</div>";
            }

            mysqli_stmt_close($stmt);
        } else {
            echo "<div class='alert alert-danger text-center'>Error in preparing statement.</div>";
        }
    }
    mysqli_close($conn);
}

?>
    </form>
</div>
<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4">
      <div class="modal-body">
        <div class="text-center mb-3">
          <i class="bi bi-box-arrow-right text-danger" style="font-size: 3rem;"></i>
        </div>
        <h5 class="mb-2">Logging Out</h5>
        <p class="text-muted mb-3">Please wait while we securely log you out...</p>
        <div class="progress" style="height: 8px;">
          <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 100%;"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/sweetalert2/dist/sweetalert2.all.min.js"></script>
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
        const branchId = selectedBranch.dataset.branchId;
        const lessor_name = selectedBranch.dataset.lessorName; // Corrected the property name here
        const kpxCodeInput = document.getElementById('kpxCode');
        const branchIdInput = document.getElementById('branchId');
        const lessorInput = document.getElementById('lessor_name');
        kpxCodeInput.value = kpxCode;
        branchIdInput.value = branchId;
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
