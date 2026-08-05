<?php
ob_start(); // 1. Trap any accidental output from included files
session_start(); // 2. Start session BEFORE including config
    include '../../config/config.php';
    $conn = mysqli_connect($host, $username, $password, $database);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    if (!isset($_SESSION['admin_name'])) {
        header('location:../login_form.php');
    }
    require '../../vendor/autoload.php';
   echo '
     <link rel="icon" href="../../assets/images/ml_logo.png" type="image/png">
    <!-- ✅ Local Google Font -->
    <link href="../../assets/css/poppins.css" rel="stylesheet">
    
    <!-- ✅ Local Bootstrap CSS -->
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ Local Bootstrap Icons -->
    <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">

    <!-- ✅ Local SweetAlert2 -->
    <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
   ';
   if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_branchProfile"])) {
    if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['csvFile']['tmp_name'];
        $file = fopen($fileTmpPath, "r");

        if ($file !== false) {
            fgetcsv($file); // Skip header row

            mysqli_autocommit($conn, false);
            $updateCount = 0;
            $newInsertCount = 0;

            // 1. Prepare Check Statement
            $checkSql = "SELECT branch_id FROM branch_insurance WHERE branch_id = ? LIMIT 1";
            $checkStmt = mysqli_prepare($conn, $checkSql);

            // 2. Prepare Update Statement
            $updateSql = "UPDATE branch_insurance SET 
                            alias = ?, mainzone = ?, zone = ?, code = ?, kpx_code = ?, 
                            branch_name = ?, corporate_name = ?, region = ?, gl_region = ?, 
                            maa_region = ?, para_region = ?, area = ?, maa = ?, para = ?, 
                            cost_center = ?, smart_service_1 = ?, smart_service_2 = ?, 
                            bir_rdo = ?, kp_code = ?, ml_matic_region = ?, ml_matic_status = ?, 
                            oldregularloan_region = ? 
                          WHERE branch_id = ?";
            $updateStmt = mysqli_prepare($conn, $updateSql);

            // 3. Prepare Insert Statement
            $insertSql = "INSERT INTO branch_insurance (branch_id, alias, mainzone, zone, code, kpx_code, branch_name, corporate_name, region, gl_region, maa_region, para_region, area, maa, para, cost_center, smart_service_1, smart_service_2, bir_rdo, kp_code, ml_matic_region, ml_matic_status, oldregularloan_region) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insertStmt = mysqli_prepare($conn, $insertSql);

            while (($row = fgetcsv($file)) !== false) {
                if (!isset($row[5]) || empty($row[5])) continue; 

                foreach ($row as &$value) {
                    $value = mb_convert_encoding($value, "UTF-8", "auto");
                }

                $branch_id = intval($row[5]);

                // STEP 1: Check if it exists
                mysqli_stmt_bind_param($checkStmt, "i", $branch_id);
                mysqli_stmt_execute($checkStmt);
                mysqli_stmt_store_result($checkStmt);

                if (mysqli_stmt_num_rows($checkStmt) > 0) {
                    // STEP 2: It exists, so UPDATE
                    mysqli_stmt_bind_param($updateStmt, "ssssssssssssssssssssssi", 
                        $row[0], $row[1], $row[2], $row[3], $row[4], 
                        $row[6], $row[7], $row[8], $row[9], 
                        $row[10], $row[11], $row[12], $row[13], $row[14], 
                        $row[15], $row[16], $row[17], 
                        $row[18], $row[19], $row[20], $row[21], 
                        $row[22], $branch_id
                    );
                    mysqli_stmt_execute($updateStmt);
                    $updateCount++;
                } else {
                    // STEP 3: It's missing, so INSERT
                    mysqli_stmt_bind_param($insertStmt, "issssssssssssssssssssss", 
                        $branch_id, $row[0], $row[1], $row[2], $row[3], $row[4], 
                        $row[6], $row[7], $row[8], $row[9], 
                        $row[10], $row[11], $row[12], $row[13], $row[14], 
                        $row[15], $row[16], $row[17], 
                        $row[18], $row[19], $row[20], $row[21], $row[22]
                    );
                    mysqli_stmt_execute($insertStmt);
                    $newInsertCount++;
                }
            }

            mysqli_commit($conn);
            mysqli_autocommit($conn, true);
            
            mysqli_stmt_close($updateStmt);
            mysqli_stmt_close($checkStmt);
            mysqli_stmt_close($insertStmt);
            fclose($file);

            echo "<script>
                Swal.fire('Process Complete', 'Updated: $updateCount records. \\n New Records Inserted: $newInsertCount.', 'success');
            </script>";
        }
    }
}
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_createTable"])) {    
        if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['csvFile']['tmp_name'];
            $file = fopen($fileTmpPath, "r");
    
            if ($file !== false) {
                fgetcsv($file); // Skip header row
    
                // Start transaction
                mysqli_begin_transaction($conn);
                try {
                    $updateCasesMainzone = "";
                    $updateCasesZone = "";
                    $updateCasesRegion = "";
                    $updateCasesArea = "";
                    $updateCasesBranchCode = "";
                    $updateCasesBranch = "";
                    $updateCasesCorporateName = "";
                    $branchIds = [];
    
                    while (($row = fgetcsv($file)) !== false) {
                        $branch_id = intval($row[5]); // Ensure it's an integer
                        $mainzone = mysqli_real_escape_string($conn, $row[1]);
                        $zone = mysqli_real_escape_string($conn, $row[2]);
                        $region = mysqli_real_escape_string($conn, $row[8]);
                        $area = mysqli_real_escape_string($conn, $row[12]);
                        $branch_code = mysqli_real_escape_string($conn, $row[3]);
                        $branch = mysqli_real_escape_string($conn, $row[6]);
                        $corporate_name = mysqli_real_escape_string($conn, $row[7]);
    
                        // Store CASE conditions
                        $updateCasesMainzone .= "WHEN $branch_id THEN '$mainzone' ";
                        $updateCasesZone .= "WHEN $branch_id THEN '$zone' ";
                        $updateCasesRegion .= "WHEN $branch_id THEN '$region' ";
                        $updateCasesArea .= "WHEN $branch_id THEN '$area' ";
                        $updateCasesBranchCode .= "WHEN $branch_id THEN '$branch_code' ";
                        $updateCasesBranch .= "WHEN $branch_id THEN '$branch' ";
                        $updateCasesCorporateName .= "WHEN $branch_id THEN '$corporate_name' ";
    
                        $branchIds[] = $branch_id;
                    }
                    fclose($file);
    
                    $affectedCount = 0;
                    if (!empty($branchIds)) {
                        $branchIdList = implode(",", $branchIds);
    
                        // Execute a single bulk UPDATE query using CASE statements
                        $updateQuery = "
                            UPDATE create_contract SET 
                                mainzone = CASE branch_id $updateCasesMainzone ELSE mainzone END,
                                zone = CASE branch_id $updateCasesZone ELSE zone END,
                                region = CASE branch_id $updateCasesRegion ELSE region END,
                                area = CASE branch_id $updateCasesArea ELSE area END,
                                branch_code = CASE branch_id $updateCasesBranchCode ELSE branch_code END,
                                branch = CASE branch_id $updateCasesBranch ELSE branch END,
                                corporate_name = CASE branch_id $updateCasesCorporateName ELSE corporate_name END
                            WHERE branch_id IN ($branchIdList)
                        ";
                        
                        mysqli_query($conn, $updateQuery);
                        $affectedCount = mysqli_affected_rows($conn);
                    }
    
                    // Commit transaction
                    mysqli_commit($conn);
                    $processedCount = count($branchIds);
                    echo "<script>Swal.fire('Success', 'Create Contract table updated successfully.\\nProcessed: $processedCount records (Updated: $affectedCount).', 'success');</script>";
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    echo "<script>Swal.fire('Error', 'Failed to update Create Contract table.', 'error');</script>";
                }
            } else {
                echo "<script>Swal.fire('Error', 'Failed to open file.', 'error');</script>";
            }
        } else {
            echo "<script>Swal.fire('Error', 'No file uploaded or error in file upload.', 'error');</script>";
        }
    }
    
    

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_transactionalTable"])) {
        if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['csvFile']['tmp_name'];
            $file = fopen($fileTmpPath, "r");
    
            if ($file !== false) {
                fgetcsv($file); // Skip header row
    
                // Start transaction
                mysqli_begin_transaction($conn);
                try {
                    $updateCasesMainzone = "";
                    $updateCasesZone = "";
                    $updateCasesRegion = "";
                    $updateCasesArea = "";
                    $updateCasesBranchCode = "";
                    $updateCasesBranch = "";
                    $updateCasesCorporateName = "";
                    $branchIds = [];
    
                    while (($row = fgetcsv($file)) !== false) {
                        foreach ($row as &$value) {
                            $value = mb_convert_encoding($value, "UTF-8", "auto");
                        }
    
                        $branch_id = intval($row[5]); // Ensure integer type
                        $mainzone = mysqli_real_escape_string($conn, $row[1]);
                        $zone = mysqli_real_escape_string($conn, $row[2]);
                        $region = mysqli_real_escape_string($conn, $row[8]);
                        $area = mysqli_real_escape_string($conn, $row[12]);
                        $branch_code = mysqli_real_escape_string($conn, $row[3]);
                        $branch = mysqli_real_escape_string($conn, $row[6]);
                        $corporate_name = mysqli_real_escape_string($conn, $row[7]);
    
                        // Build CASE conditions
                        $updateCasesMainzone .= "WHEN $branch_id THEN '$mainzone' ";
                        $updateCasesZone .= "WHEN $branch_id THEN '$zone' ";
                        $updateCasesRegion .= "WHEN $branch_id THEN '$region' ";
                        $updateCasesArea .= "WHEN $branch_id THEN '$area' ";
                        $updateCasesBranchCode .= "WHEN $branch_id THEN '$branch_code' ";
                        $updateCasesBranch .= "WHEN $branch_id THEN '$branch' ";
                        $updateCasesCorporateName .= "WHEN $branch_id THEN '$corporate_name' ";
    
                        $branchIds[] = $branch_id;
                    }
                    fclose($file);
    
                    $affectedCount = 0;
                    if (!empty($branchIds)) {
                        $branchIdList = implode(",", $branchIds);
    
                        // Execute a single bulk UPDATE query
                        $updateQuery = "
                            UPDATE transactional SET 
                                mainzone = CASE branch_id $updateCasesMainzone ELSE mainzone END,
                                zone = CASE branch_id $updateCasesZone ELSE zone END,
                                region = CASE branch_id $updateCasesRegion ELSE region END,
                                area = CASE branch_id $updateCasesArea ELSE area END,
                                branch_code = CASE branch_id $updateCasesBranchCode ELSE branch_code END,
                                branch = CASE branch_id $updateCasesBranch ELSE branch END,
                                corporate_name = CASE branch_id $updateCasesCorporateName ELSE corporate_name END
                            WHERE branch_id IN ($branchIdList)
                        ";
                        
                        mysqli_query($conn, $updateQuery);
                        $affectedCount = mysqli_affected_rows($conn);
                    }
    
                    // Commit transaction
                    mysqli_commit($conn);
                    $processedCount = count($branchIds);
                    echo "<script>Swal.fire('Success', 'Transactional table updated successfully.\\nProcessed: $processedCount records (Updated: $affectedCount).', 'success');</script>";
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    echo "<script>Swal.fire('Error', 'Failed to update transactional table.', 'error');</script>";
                }
            } else {
                echo "<script>Swal.fire('Error', 'Failed to open file.', 'error');</script>";
            }
        } else {
            echo "<script>Swal.fire('Error', 'No file uploaded or error in file upload.', 'error');</script>";
        }
    }
    
    
    
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_payoutTable"])) {
        if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['csvFile']['tmp_name'];
            $file = fopen($fileTmpPath, "r");
    
            if ($file !== false) {
                fgetcsv($file); // Skip header row
                
                // Start transaction
                mysqli_begin_transaction($conn);
                try {
                    $processedCount = 0;
                    while (($row = fgetcsv($file)) !== false) {
                        foreach ($row as &$value) {
                            $value = mb_convert_encoding($value, "UTF-8", "auto");
                        }
    
                        // Escaping & Formatting
                        $branch_id = intval($row[5]);
                        $region = mysqli_real_escape_string($conn, $row[8]);
                        $branch = mysqli_real_escape_string($conn, $row[6]);
    
                        // Update payout table
                        $updatePayout = "UPDATE payout SET region = '$region', payout_branch = '$branch' WHERE payout_branch_id = '$branch_id'";
                        $updateSendout = "UPDATE payout SET sendout_branch = '$branch' WHERE sendout_branch_id = '$branch_id'";
                        
                        mysqli_query($conn, $updatePayout);
                        mysqli_query($conn, $updateSendout);
                        $processedCount++;
                    }
                    fclose($file);
                    
                    // Commit transaction
                    mysqli_commit($conn);
                    echo "<script>Swal.fire('Success', 'Payout table updated successfully.\\nProcessed: $processedCount records.', 'success');</script>";
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    echo "<script>Swal.fire('Error', 'Failed to update payout table.', 'error');</script>";
                }
            } else {
                echo "<script>Swal.fire('Error', 'Failed to open file.', 'error');</script>";
            }
        } else {
            echo "<script>Swal.fire('Error', 'No file uploaded or error in file upload.', 'error');</script>";
        }
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_sendoutTable"])) {
        if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['csvFile']['tmp_name'];
            $file = fopen($fileTmpPath, "r");
    
            if ($file !== false) {
                fgetcsv($file); // Skip header row
                
                // Start transaction
                mysqli_begin_transaction($conn);
                try {
                    $processedCount = 0;
                    while (($row = fgetcsv($file)) !== false) {
                        foreach ($row as &$value) {
                            $value = mb_convert_encoding($value, "UTF-8", "auto");
                        }
    
                        // Escaping & Formatting
                        $branch_id = intval($row[5]);
                        $region = mysqli_real_escape_string($conn, $row[8]);
                        $branch = mysqli_real_escape_string($conn, $row[6]);
    
                        // Update payout table
                        $updateSendout = "UPDATE sendout SET region = '$region', sendout_branch = '$branch' WHERE branch_id = '$branch_id'";
                        
                        mysqli_query($conn, $updateSendout);
                        $processedCount++;
                    }
                    fclose($file);
                    
                    // Commit transaction
                    mysqli_commit($conn);
                    echo "<script>Swal.fire('Success', 'Sendout table updated successfully.\\nProcessed: $processedCount records.', 'success');</script>";
                } catch (Exception $e) {
                    mysqli_rollback($conn);
                    echo "<script>Swal.fire('Error', 'Failed to update payout table.', 'error');</script>";
                }
            } else {
                echo "<script>Swal.fire('Error', 'Failed to open file.', 'error');</script>";
            }
        } else {
            echo "<script>Swal.fire('Error', 'No file uploaded or error in file upload.', 'error');</script>";
        }
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ML Rental - Update Profile</title>
    <link rel="icon" href="../../assets/images/ml_logo.png" type="image/png">
    <!-- ✅ Local Google Font -->
    <link href="../../assets/css/poppins.css" rel="stylesheet">
    
    <!-- ✅ Local Bootstrap CSS -->
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ Local Bootstrap Icons -->
    <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">

    <!-- ✅ Local SweetAlert2 -->
    <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
</head>
<style>
    * {
  font-family: 'Poppins', sans-serif;
  font-size: 12px;
  margin: 0;
  padding: 0;
}

/* Navbar */
.navbar {
  background-color: #fff;
  border-bottom: 3px solid #d70c0c;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.nav-link {
  color: #333 !important;
  font-weight: 500;
  font-size: 12px;
}

.nav-link:hover,
.dropdown-item:hover {
  color: #d70c0c !important;
}

.dropdown-menu {
  border-radius: 10px;
  border: 1px solid #eee;
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
}

.dropdown-item {
  font-size: 12px;
}

/* Admin Info */
.admin-info {
  display: flex;
  align-items: center;
  gap: 15px;
  font-size: 12px;
}

.admin-info i {
  color: #d70c0c;
}
.upload-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  padding: 2rem;
}

.upload-card {
  background-color: #fff;
  border-radius: 12px;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
  padding: 1.5rem;
  transition: transform 0.2s ease;
  border-top: 4px solid #d70c0c;
}

.upload-card:hover {
  transform: translateY(-4px);
}

.upload-card label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.75rem;
  font-size: 14px;
  color: #333;
}

.upload-card label i {
  margin-right: 6px;
  color: #d70c0c;
}

.upload-card input[type="file"] {
  width: 100%;
  padding: 8px 10px;
  font-size: 13px;
  border: 1px solid #ccc;
  border-radius: 6px;
  margin-bottom: 1rem;
  background-color: #f9f9f9;
}

.upload-card button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background-color: #d70c0c;
  color: white;
  border: none;
  padding: 10px 16px;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s ease-in-out;
}

.upload-card button:hover {
  background-color: #b80000;
}

</style>
<body>
<?php include ('navbar_admin.php'); ?>
<div class="upload-grid">
  <div class="upload-card">
    <form action="" method="POST" enctype="multipart/form-data">
      <label for="csvFile1"><i class="bi bi-database me-2"></i> 1. Branch Profile Upload CSV:</label>
      <input type="file" name="csvFile" id="csvFile1" accept=".csv">
      <button type="submit" name="update_branchProfile"><i class="bi bi-upload"></i> Update Branch Profile</button>
    </form>
  </div>

  <div class="upload-card">
    <form action="" method="POST" enctype="multipart/form-data">
      <label for="csvFile2"><i class="bi bi-file-earmark-ruled me-2"></i> 2. Create Contract Upload CSV:</label>
      <input type="file" name="csvFile" id="csvFile2" accept=".csv">
      <button type="submit" name="update_createTable"><i class="bi bi-upload"></i> Update Create Contract</button>
    </form>
  </div>

  <div class="upload-card">
    <form action="" method="POST" enctype="multipart/form-data">
      <label for="csvFile3"><i class="bi bi-repeat me-2"></i> 3. Transactional Upload CSV:</label>
      <input type="file" name="csvFile" id="csvFile3" accept=".csv">
      <button type="submit" name="update_transactionalTable"><i class="bi bi-upload"></i> Update Transactional</button>
    </form>
  </div>

  <div class="upload-card">
    <form action="" method="POST" enctype="multipart/form-data">
      <label for="csvFile4"><i class="bi bi-cash me-2"></i> 4. Payout Upload CSV:</label>
      <input type="file" name="csvFile" id="csvFile4" accept=".csv">
      <button type="submit" name="update_payoutTable"><i class="bi bi-upload"></i> Update Payout</button>
    </form>
  </div>

  <div class="upload-card">
    <form action="" method="POST" enctype="multipart/form-data">
      <label for="csvFile5"><i class="bi bi-send me-2"></i> 5. Sendout Upload CSV:</label>
      <input type="file" name="csvFile" id="csvFile5" accept=".csv">
      <button type="submit" name="update_sendoutTable"><i class="bi bi-upload"></i> Update Sendout</button>
    </form>
  </div>
</div>

<div class="modal fade" id="dbModal" tabindex="-1" aria-labelledby="dbModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow border-0 rounded-4" style="background-color: #fff; color: #333;">
      
      <div class="modal-header border-0" style="background-color: #f8f9fa;">
        <h5 class="modal-title d-flex align-items-center" id="dbModalLabel">
          <i class="bi-solid bi-lock me-2" style="color: #d70c0c;"></i> Secure Access
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body px-4 pt-3">
        <p class="mb-3">Please enter your password to proceed:</p>

        <div class="mb-3">
          <label for="passwordInput" class="form-label">Password</label>
          <input type="password" class="form-control rounded-pill border-secondary" id="passwordInput" placeholder="••••••••">
        </div>

        <div class="form-check mb-4">
          <input class="form-check-input" type="checkbox" id="showPassword" onchange="togglePasswordVisibility()">
          <label class="form-check-label" for="showPassword">Show Password</label>
        </div>

        <div class="d-grid">
          <button id="submitPassword" class="btn rounded-pill text-white" style="background-color: #d70c0c;">
            <i class="bi bi-box-arrow-in-right me-2"></i>Submit
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Enhanced Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4" style="background-color: #fefefe;">
      <div class="modal-body">
        <div class="text-center mb-3">
          <i class="bi bi-box-arrow-right me-1 text-danger" style="font-size: 3rem;"></i>
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

<script src="../../assets/sweetalert2/dist/sweetalert2.min.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
    // ✅ Progress bar indicator during table update submission
    document.querySelectorAll('.upload-card form').forEach(form => {
        form.addEventListener('submit', function (e) {
            const fileInput = this.querySelector('input[type="file"]');
            if (fileInput && fileInput.files.length > 0) {
                Swal.fire({
                    title: 'Updating Database...',
                    html: `
                        <div class="mb-3 text-muted">Please wait while your CSV file is being processed and updated into the table...</div>
                        <div class="progress" style="height: 14px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 100%;"></div>
                        </div>
                    `,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false
                });
            }
        });
    });

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
    const passwordInput = document.getElementById('passwordInput');
    const showPassword = document.getElementById('showPassword');
    const submitPassword = document.getElementById('submitPassword');
    const dbModalInstance = new bootstrap.Modal(document.getElementById('dbModal'));

    // Show modal when any .db-link is clicked
    document.querySelectorAll('.db-link').forEach(link => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            dbModalInstance.show();
        });
    });

    // Toggle show/hide password
    showPassword.addEventListener('change', () => {
        passwordInput.type = showPassword.checked ? 'text' : 'password';
    });

    // Password check
    submitPassword.addEventListener('click', () => {
        const correctPassword = 'CADMLhuillierDB2023';
        if (passwordInput.value === correctPassword) {
            window.location.href = 'db.php';
        } else {
            alert('Incorrect password. Access denied.');
        }
    });
    // 1. Disable Right-Click (Context Menu)
document.addEventListener('contextmenu', (e) => e.preventDefault());

// 2. Disable Keyboard Shortcuts
document.onkeydown = function(e) {
    // F12
    if (e.keyCode == 123) return false;
    
    // Ctrl+Shift+I (Inspect)
    if (e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) return false;
    
    // Ctrl+Shift+J (Console)
    if (e.ctrlKey && e.shiftKey && e.keyCode == 'J'.charCodeAt(0)) return false;
    
    // Ctrl+Shift+C (Element Selector)
    if (e.ctrlKey && e.shiftKey && e.keyCode == 'C'.charCodeAt(0)) return false;
    
    // Ctrl+U (View Source)
    if (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false;
};

// 3. The Debugger Trap
// If the console is opened, this loop will trigger the debugger, 
// effectively "freezing" the user's ability to browse the code.
(function() {
    var protect = function() {
        try {
            (function() {
                var handler = function() {
                    debugger;
                };
                setInterval(handler, 100);
            })();
        } catch (e) {}
    };
    protect();
})();
</script>
</body>  
</html>