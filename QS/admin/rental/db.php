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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ML Rental - Admin DB</title>
    <link rel="icon" href="../../assets/images/ml_logo.png" type="image/png">
    <!-- ✅ Local Google Font -->
    <link href="../../assets/css/poppins.css" rel="stylesheet">
    
    <!-- ✅ Local Bootstrap CSS -->
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ Local Bootstrap Icons -->
    <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">

    <!-- ✅ Local SweetAlert2 -->
    <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">

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
/* === Container Styling === */
.container-db {
  max-width: 100%;
  margin: 15px;
  padding: 1.5rem;
  background-color: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
  overflow-x: auto;
  font-size: 12px;
}

.container-db form {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
  margin-bottom: 1rem;
}

.container-db label {
  font-weight: 500;
  font-size: 12px;
  color: #333;
  display: flex;
  align-items: center;
  gap: 6px;
}

.container-db label i {
  color: #d70c0c;
  font-size: 14px;
}

/* === Select & Submit Inputs === */
.container-db select,
.container-db input[type="submit"] {
  width: 180px;
  padding: 0.5rem 0.75rem;
  font-size: 12px;
  border: 1px solid #ccc;
  border-radius: 6px;
  background-color: #fff;
  color: #333;
  transition: all 0.2s ease-in-out;
}

.container-db select:focus,
.container-db input[type="submit"]:focus {
  border-color: #d70c0c;
  box-shadow: 0 0 0 0.15rem rgba(215, 12, 12, 0.25);
  outline: none;
}

/* === Submit Button === */
.container-db input[type="submit"],
.submit-proceed {
  background-color: #d70c0c;
  color: #fff;
  font-weight: 500;
  border: none;
  cursor: pointer;
}

.container-db input[type="submit"]:hover,
.submit-proceed:hover {
  background-color: #b80000;
}

/* === Truncate Form Alignment === */
.truncate-form {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 1rem;
}

/* === Custom Checkbox Styling === */
.custom-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 500;
  color: #333;
  cursor: pointer;
  user-select: none;
}

.custom-checkbox i {
  color: #d70c0c;
  font-size: 13px;
}

.custom-checkbox input[type="checkbox"] {
  appearance: none;
  width: 16px;
  height: 16px;
  border: 2px solid #999;
  border-radius: 4px;
  position: relative;
  cursor: pointer;
  transition: 0.2s;
}

.custom-checkbox input[type="checkbox"]:checked {
  background-color: #d70c0c;
  border-color: #d70c0c;
}

.custom-checkbox input[type="checkbox"]:checked::after {
  content: "✔";
  color: #fff;
  font-size: 10px;
  position: absolute;
  left: 2px;
  top: 0px;
}

/* === Truncate Button === */
#truncateButton {
  padding: 0.5rem 1rem;
  font-size: 12px;
  font-weight: 500;
  color: #fff;
  background-color: #d70c0c;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  display: none;
  transition: background-color 0.2s ease;
}

#truncateButton:hover {
  background-color: #b80000;
}

/* === Responsive === */
@media (max-width: 768px) {
  .container-db form {
    flex-direction: column;
    align-items: flex-start;
  }

  .container-db select,
  .container-db input[type="submit"] {
    width: 100%;
  }

  .truncate-form {
    justify-content: flex-start;
  }
}


/* === Table Styles === */
.db-container {
  padding: 15px;
  background-color: #fff;
  border-radius: 12px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
  margin: 10px 15px;
  overflow-x: auto;
}

.db-table {
  width: 100%;
  border-collapse: collapse;
  background-color: #fff;
  border: 1px solid #ddd;
  font-size: 12px;
}

.db-table th,
.db-table td {
  padding: 5px;
  border: 1px solid #eee;
  text-align: center;
  white-space: nowrap;
}

.db-table th {
  background-color: #d70c0c;
  color: #fff;
  font-weight: 600;
}

.db-table tr:hover {
  background-color: #f5f5f5;
}

.db-table td:first-child {
  white-space: nowrap;
}

#modifyBtn {
  background-color: #d70c0c;
  color: white;
  padding: 5px 10px;
  font-size: 12px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: background-color 0.2s ease-in-out;
}

#modifyBtn:hover {
  background-color: #157347;
}
.updateModal {
  display: none;
  position: fixed;
  z-index: 1050;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow-y: auto;
  background-color: rgba(0, 0, 0, 0.6);
  animation: fadeIn 0.3s ease-in-out;
}

.updatemodal-content {
  background-color: #fff;
  margin: 5% auto;
  padding: 2rem;
  border-radius: 16px;
  max-width: 480px;
  width: 90%;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  animation: scaleUp 0.3s ease-in-out;
  position: relative;
}

.updatemodal-content h3 {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 1.5rem;
  color: #d70c0c;
  display: flex;
  align-items: center;
  justify-content: center;
}

.updatemodal-content h3 i {
  margin-right: 0.5rem;
  color: #d70c0c;
}

.updatemodal-content .close {
  position: absolute;
  top: 14px;
  right: 20px;
  font-size: 22px;
  color: #aaa;
  cursor: pointer;
  transition: color 0.2s;
}

.updatemodal-content .close:hover {
  color: #d70c0c;
}

.db-form label {
  display: block;
  font-weight: 500;
  font-size: 12px;
  margin-bottom: 0.25rem;
  color: #333;
}

.db-form label i {
  margin-right: 6px;
  color: #d70c0c;
}

.input-modal {
  width: 100%;
  padding: 0.5rem;
  margin-bottom: 1rem;
  font-size: 12px;
  border: 1px solid #ccc;
  border-radius: 6px;
  transition: 0.2s;
}

.input-modal:focus {
  border-color: #d70c0c;
  outline: none;
  box-shadow: 0 0 0 2px rgba(215, 12, 12, 0.2);
}

.input-modal[type="submit"] {
  background-color: #d70c0c;
  color: #fff;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: background-color 0.2s;
}

.input-modal[type="submit"]:hover {
  background-color: #b30000;
}


@keyframes fadeIn {
  from {opacity: 0;}
  to {opacity: 1;}
}

@keyframes scaleUp {
  from {
    transform: scale(0.9);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}


.pagination {
  margin-top: 1.5rem;
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 6px;
}

.page-link {
  display: inline-block;
  padding: 0.4rem 0.75rem;
  font-size: 12px;
  color: #333;
  background-color: #fff;
  border: 1px solid #ccc;
  border-radius: 6px;
  text-decoration: none;
  transition: all 0.2s ease;
}

.page-link:hover {
  background-color: #d70c0c;
  color: #fff;
  border-color: #d70c0c;
}

.page-link.active {
  background-color: #d70c0c;
  color: #fff;
  font-weight: 600;
  pointer-events: none;
  border-color: #d70c0c;
}


/* Responsive Fixes */
@media (max-width: 768px) {
  .container-db form {
    flex-direction: column;
  }

  .container-db select,
  .container-db input[type="submit"] {
    width: 100%;
  }

  .db-table th,
  .db-table td {
    font-size: 12px;
    padding: 0.5rem;
  }

  #modifyBtn {
    font-size: 12px;
    padding: 0.3rem 0.6rem;
  }
}

</style>
</head>
<body>
<?php include ('navbar_admin.php'); ?>

<div class="modal fade" id="dbModal" tabindex="-1" aria-labelledby="dbModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow border-0 rounded-4" style="background-color: #fff; color: #333;">
      
      <div class="modal-header border-0" style="background-color: #f8f9fa;">
        <h5 class="modal-title d-flex align-items-center" id="dbModalLabel">
          <i class="bi bi-shield-lock-fill me-2" style="color: #d70c0c;"></i> Secure Access
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

    <?php
    if (isset($_POST['truncate'])) {
        $truncate_table = $_POST['truncate_table'];
        if (in_array($truncate_table, ['transactional', 'create_contract', 'lessor_profile', 'branch_insurance', 'sendout', 'payout', 'escalation', 'region_masterfile'])) {
            $truncate_sql = "TRUNCATE TABLE $truncate_table";
            if ($conn->query($truncate_sql) === TRUE) {
                echo "<script>Swal.fire('Success!', 'Table $truncate_table truncated successfully.', 'success');</script>";
            } else {
                echo "<script>Swal.fire('Error!', 'Error truncating table: " . $conn->error . "', 'error');</script>";
            }
        } else {
            echo "<script>Swal.fire('Error!', 'Invalid table selected.', 'error');</script>";
        }
    }
    ?>
    <div class="container-db">
        <form method="get" action="">
            <label for="table"><i class="bi bi-table"></i>Table:</label>
            <select name="table" id="table">
                <option value=""></option>
                <option value="branch_insurance" <?php echo isset($_GET['table']) && $_GET['table'] == 'branch_insurance' ? 'selected' : ''; ?>>Branch Profile</option>
                <option value="lessor_profile" <?php echo isset($_GET['table']) && $_GET['table'] == 'lessor_profile' ? 'selected' : ''; ?>>Lessor Profile</option>
                <option value="create_contract" <?php echo isset($_GET['table']) && $_GET['table'] == 'create_contract' ? 'selected' : ''; ?>>Create Contract</option>
                <option value="escalation" <?php echo isset($_GET['table']) && $_GET['table'] == 'escalation' ? 'selected' : ''; ?>>Escalation</option>
                <option value="payout" <?php echo isset($_GET['table']) && $_GET['table'] == 'payout' ? 'selected' : ''; ?>>Payout</option>
                <option value="region_masterfile" <?php echo isset($_GET['table']) && $_GET['table'] == 'region_masterfile' ? 'selected' : ''; ?>>Region Masterfile</option>
                <option value="sendout" <?php echo isset($_GET['table']) && $_GET['table'] == 'sendout' ? 'selected' : ''; ?>>Sendout</option>
                <option value="transactional" <?php echo isset($_GET['table']) && $_GET['table'] == 'transactional' ? 'selected' : ''; ?>>Transactional</option>
            </select>

            <label for="mainzone"><i class="bi bi-globe"></i>Main Zone:</label>
            <select name="mainzone" id="mainzone" class="mainzone_select">
                <option value=""></option>
                <?php
                $mainzoneSql = "SELECT DISTINCT mainzone FROM branch_insurance WHERE mainzone != '' ORDER BY mainzone ASC";
                $resultMainzone = mysqli_query($conn, $mainzoneSql);

                if ($resultMainzone) {
                    while ($rowMainzone = mysqli_fetch_assoc($resultMainzone)) {
                        $selected = (isset($_GET['mainzone']) && $_GET['mainzone'] == $rowMainzone['mainzone']) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($rowMainzone['mainzone']) . "' $selected>" . htmlspecialchars($rowMainzone['mainzone']) . "</option>";
                    }
                }
                ?>
            </select>
            <label for="region"><i class="bi bi-map"></i>Region:</label>
            <select name="region" id="region" class="region_select">
                <option value=""></option>
                <?php
                $regionFilter = isset($_GET['region']) ? $_GET['region'] : '';
                $regionSql = "SELECT DISTINCT region FROM branch_insurance WHERE area != ''" . 
                            (!empty($_GET['mainzone']) ? " AND mainzone = '" . mysqli_real_escape_string($conn, $_GET['mainzone']) . "'" : "") . 
                            " ORDER BY region ASC";
                $resultRegion = mysqli_query($conn, $regionSql);

                if ($resultRegion) {
                    while ($rowRegion = mysqli_fetch_assoc($resultRegion)) {
                        $selected = ($rowRegion['region'] == $regionFilter) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($rowRegion['region']) . "' $selected>" . htmlspecialchars($rowRegion['region']) . "</option>";
                    }
                }
                ?>
            </select>
            <label for="area"><i class="bi bi-geo"></i>Area:</label>
            <select name="area" id="area" class="area_select">
                <option value=""></option>
                <?php
                // Retain selected area
                $areaFilter = isset($_GET['area']) ? $_GET['area'] : '';
                $areaSql = "SELECT DISTINCT area FROM branch_insurance WHERE area != ''" . 
                            (!empty($regionFilter) ? " AND region = '" . mysqli_real_escape_string($conn, $regionFilter) . "'" : "") . 
                            " ORDER BY area ASC";
                $resultArea = mysqli_query($conn, $areaSql);

                if ($resultArea) {
                    while ($rowArea = mysqli_fetch_assoc($resultArea)) {
                        $selected = ($rowArea['area'] == $areaFilter) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($rowArea['area']) . "' $selected>" . htmlspecialchars($rowArea['area']) . "</option>";
                    }
                }
                ?>
            </select>
            <label for="branch"><i class="bi bi-building"></i>Branch:</label>
            <select name="branch" id="branch" class="branch_select">
                <option value=""></option>
                <?php
                $branchFilter = isset($_GET['branch']) ? $_GET['branch'] : '';
                $branchSql = "SELECT DISTINCT branch_name FROM branch_insurance WHERE area != ''" . 
                            (!empty($areaFilter) ? " AND area = '" . mysqli_real_escape_string($conn, $areaFilter) . "'" : "") . 
                            " ORDER BY branch_name ASC";
                $resultBranch = mysqli_query($conn, $branchSql);

                if ($resultBranch) {
                    while ($rowBranch = mysqli_fetch_assoc($resultBranch)) {
                        $selected = ($rowBranch['branch_name'] == $branchFilter) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($rowBranch['branch_name']) . "' $selected>" . htmlspecialchars($rowBranch['branch_name']) . "</option>";
                    }
                }
                ?>
            </select>
            <script>
                document.getElementById('mainzone').addEventListener('change', function() {
                    var mainzone = this.value;
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'fetch_regions.php?mainzone=' + mainzone, true);
                    xhr.onload = function() {
                        if (this.status === 200) {
                            var regionSelect = document.getElementById('region');
                            regionSelect.innerHTML = this.responseText;
                            var selectedRegion = '<?php echo isset($_GET["region"]) ? $_GET["region"] : ""; ?>';
                            if (selectedRegion) {
                                for (var i = 0; i < regionSelect.options.length; i++) {
                                    if (regionSelect.options[i].value === selectedRegion) {
                                        regionSelect.selectedIndex = i;
                                        break;
                                    }
                                }
                            }
                            document.getElementById('area').innerHTML = "<option value=''></option>";
                            document.getElementById('branch').innerHTML = "<option value=''></option>";
                        } else {
                            console.error("Error fetching regions: " + this.statusText);
                        }
                    };
                    xhr.onerror = function() {
                        console.error("Request failed.");
                    };
                    xhr.send();
                });

                document.getElementById('region').addEventListener('change', function() {
                    var region = this.value;
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'fetch_areas.php?region=' + region, true);
                    xhr.onload = function() {
                        if (this.status === 200) {
                            var areaSelect = document.getElementById('area');
                            areaSelect.innerHTML = this.responseText;
                            var selectedArea = '<?php echo isset($_GET["area"]) ? $_GET["area"] : ""; ?>';
                            if (selectedArea) {
                                for (var i = 0; i < areaSelect.options.length; i++) {
                                    if (areaSelect.options[i].value === selectedArea) {
                                        areaSelect.selectedIndex = i;
                                        break;
                                    }
                                }
                            }
                            document.getElementById('branch').innerHTML = "<option value=''></option>"; // Reset branches
                        } else {
                            console.error("Error fetching areas: " + this.statusText);
                        }
                    };
                    xhr.onerror = function() {
                        console.error("Request failed.");
                    };
                    xhr.send();
                });
                document.getElementById('area').addEventListener('change', function() {
                    var area = this.value;
                    var region = document.getElementById('region').value; // Get selected region for the AJAX request
                    var xhr = new XMLHttpRequest();
                    xhr.open('GET', 'fetch_branches.php?area=' + area + '&region=' + region, true); // Pass region as well
                    xhr.onload = function() {
                        if (this.status === 200) {
                            var branchSelect = document.getElementById('branch');
                            branchSelect.innerHTML = this.responseText;

                            // Retain the selected branch if it matches
                            var selectedBranch = '<?php echo isset($_GET["branch"]) ? $_GET["branch"] : ""; ?>';
                            if (selectedBranch) {
                                for (var i = 0; i < branchSelect.options.length; i++) {
                                    if (branchSelect.options[i].value === selectedBranch) {
                                        branchSelect.selectedIndex = i;
                                        break;
                                    }
                                }
                            }
                        } else {
                            console.error("Error fetching branches: " + this.statusText);
                        }
                    };
                    xhr.onerror = function() {
                        console.error("Request failed.");
                    };
                    xhr.send();
                });
            </script>
            <input type="submit" value="Proceed">
        </form>
        <form action="" method="post" class="truncate-form">
            <input type="hidden" name="truncate_table" value="<?php echo htmlspecialchars(isset($_GET['table']) ? $_GET['table'] : ''); ?>">
            <label class="custom-checkbox">
                <input type="checkbox" id="showTruncate" onclick="toggleTruncateButton()">
                <span class="checkmark"></span> Truncate Button
            </label>
            <input type="submit" id="truncateButton" name="truncate" style="background-color: #d70c0c; margin-right: 15px; display: none;" value="Truncate Table" onclick="return confirm('Are you sure you want to truncate this table?');">
        </form>
        <script>
            function toggleTruncateButton() {
                var checkbox = document.getElementById('showTruncate');
                var truncateButton = document.getElementById('truncateButton');
                if (checkbox.checked) {
                    truncateButton.style.display = 'inline-block';
                } else {
                    truncateButton.style.display = 'none';
                }
            }
        </script>
    </div>

    <section class="db-container">
        <div class="db-content">
            <?php
                $results_per_page = 15;
                $mainzone = isset($_GET['mainzone']) ? $_GET['mainzone'] : '';
                $region = isset($_GET['region']) ? $_GET['region'] : '';
                $area = isset($_GET['area']) ? $_GET['area'] : '';
                $branch = isset($_GET['branch']) ? $_GET['branch'] : '';
                $table = isset($_GET['table']) ? $_GET['table'] : 'transactional';
                function updateRecord($conn, $table, $id, $column, $new_value) {
                    $sql = "UPDATE `$table` SET `$column` = ? WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    if ($stmt === false) {
                        die('Error preparing update statement: ' . htmlspecialchars($conn->error));
                    }
                
                    $stmt->bind_param("si", $new_value, $id);
                
                    if ($stmt->execute()) {
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                Swal.fire({
                                    icon: 'success',
                                    title: '✅ Success!',
                                    text: 'Record updated successfully.',
                                    showConfirmButton: false,
                                    timer: 1800
                                });
                            });
                        </script>";
                    } else {
                        echo "<script>
                            document.addEventListener('DOMContentLoaded', function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: '❌ Update Failed',
                                    text: 'Error updating record: " . addslashes($conn->error) . "',
                                    confirmButtonColor: '#d70c0c'
                                });
                            });
                        </script>";
                    }
                }
                
                if (isset($_POST['update'])) {
                    $table = $_POST['table'];
                    $id = $_POST['id'];
                    $column = $_POST['column'];
                    $new_value = $_POST['new_value'];

                    updateRecord($conn, $table, $id, $column, $new_value);
                }
                $valid_tables = ['transactional', 'create_contract', 'escalation', 'lessor_profile', 'branch_insurance', 'sendout', 'payout', 'region_masterfile'];
                if (in_array($table, $valid_tables)) {
                    $base_sql = "SELECT * FROM `$table`";
                    $count_sql = "SELECT COUNT(*) AS total FROM `$table`";
                    $conditions = [];
                    $params = [];
                    $types = '';

                    $branch_column = ($table === 'branch_insurance') ? 'branch_name' : 'branch';
                    if (!empty($region)) {
                        $conditions[] = "region = ?";
                        $params[] = $region;
                        $types .= "s";
                    }
                    if (!empty($area)) {
                        $conditions[] = "area = ?";
                        $params[] = $area;
                        $types .= "s";
                    }
                    if (!empty($branch)) {
                        $conditions[] = "$branch_column = ?";
                        $params[] = $branch;
                        $types .= "s";
                    }
                    if (!empty($mainzone)) {
                        $conditions[] = "mainzone = ?";
                        $params[] = $mainzone;
                        $types .= "s";
                    }
                    if (!empty($conditions)) {
                        $base_sql .= " WHERE " . implode(" AND ", $conditions);
                        $count_sql .= " WHERE " . implode(" AND ", $conditions);
                    }
                    $stmt_count = $conn->prepare($count_sql);
                    if ($stmt_count === false) {
                        die('Error preparing count statement: ' . htmlspecialchars($conn->error));
                    }
                    if (!empty($params)) {
                        $stmt_count->bind_param($types, ...$params);
                    }

                    $stmt_count->execute();
                    $result_count = $stmt_count->get_result();
                    $row = $result_count->fetch_assoc();
                    $total_results = $row["total"];
                    $total_pages = ceil($total_results / $results_per_page);

                    $page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
                    $page_first_result = ($page - 1) * $results_per_page;

                    $base_sql .= " LIMIT ?, ?";
                    $stmt = $conn->prepare($base_sql);
                    if ($stmt === false) {
                        die('Error preparing SQL statement: ' . htmlspecialchars($conn->error));
                    }
                    $params[] = $page_first_result;
                    $params[] = $results_per_page;
                    $types .= "ii";
        
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        echo "<table border='1' class='db-table'>";
                        echo "<tr>";
                        echo "<th>Actions</th>";
                            $fields = $result->fetch_fields();
                            $excluded_columns = [
                                'contract_file', 'mimeType', 'contractFilename', 'contract_file2', 'mimeType2', 'contractFilename2',
                                'contract_file3', 'mimeType3', 'contractFilename3', 'contract_file4', 'mimeType4', 'contractFilename4',
                                'contract_file5', 'mimeType5', 'contractFilename5', 'attachment_6', 'mimeType6', 'attachment_6_filename',
                                'attachment_7', 'mimeType7', 'attachment_7_filename', 'attachment_8', 'mimeType8', 'attachment_8_filename',
                                'attachment_9', 'mimeType9', 'attachment_9_filename', 'attachment_10', 'mimeType10', 'attachment_10_filename',
                                'attachment_11', 'mimeType11', 'attachment_11_filename', 'attachment_12', 'mimeType12', 'attachment_12_filename',
                                'attachment_13', 'mimeType13', 'attachment_13_filename', 'attachment_14', 'mimeType14', 'attachment_14_filename',
                                'attachment_15', 'mimeType15', 'attachment_15_filename'
                            ];

                        foreach ($fields as $field) {
                            if (!in_array($field->name, $excluded_columns)) {
                                echo "<th>" . htmlspecialchars($field->name ?? '', ENT_QUOTES, 'UTF-8') . "</th>";
                            }
                        }
                        echo "</tr>";
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td><button id='modifyBtn' onclick=\"openModal('" . htmlspecialchars($row["id"] ?? '', ENT_QUOTES, 'UTF-8') . "')\"><i class='fas fa-pen'></i> Modify</button></td>";

                            // Only display data for columns that are not in the excluded columns list
                            foreach ($row as $key => $value) {
                                if (!in_array($key, $excluded_columns)) {
                                    echo "<td>" . htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8') . "</td>";
                                }
                            }
                            echo "</tr>";
                        }
                        echo "</table>";
                        echo "<div class='pagination'>";

                        $maxPagesToShow = 10;
                        $half = floor($maxPagesToShow / 2);
                        
                        $start = max(1, $page - $half);
                        $end = min($total_pages, $start + $maxPagesToShow - 1);
                        $start = max(1, $end - $maxPagesToShow + 1);
                        
                        // Previous button
                        if ($page > 1) {
                            echo "<a class='page-link' href='?table=" . htmlspecialchars($table) . "&page=" . ($page - 1) .
                                (!empty($region) ? "&region=" . urlencode($region) : "") .
                                (!empty($area) ? "&area=" . urlencode($area) : "") .
                                (!empty($branch) ? "&branch=" . urlencode($branch) : "") .
                                (!empty($mainzone) ? "&mainzone=" . urlencode($mainzone) : "") .
                                "'>&laquo; Prev</a>";
                        }
                        
                        // Page numbers
                        for ($i = $start; $i <= $end; $i++) {
                            $isActive = ($i == $page) ? 'active' : '';
                            echo "<a class='page-link $isActive' href='?table=" . htmlspecialchars($table) . "&page=$i" .
                                (!empty($region) ? "&region=" . urlencode($region) : "") .
                                (!empty($area) ? "&area=" . urlencode($area) : "") .
                                (!empty($branch) ? "&branch=" . urlencode($branch) : "") .
                                (!empty($mainzone) ? "&mainzone=" . urlencode($mainzone) : "") .
                                "'>$i</a>";
                        }
                        
                        // Next button
                        if ($page < $total_pages) {
                            echo "<a class='page-link' href='?table=" . htmlspecialchars($table) . "&page=" . ($page + 1) .
                                (!empty($region) ? "&region=" . urlencode($region) : "") .
                                (!empty($area) ? "&area=" . urlencode($area) : "") .
                                (!empty($branch) ? "&branch=" . urlencode($branch) : "") .
                                (!empty($mainzone) ? "&mainzone=" . urlencode($mainzone) : "") .
                                "'>Next &raquo;</a>";
                        }
                        
                        echo "</div>";                        
                        
                    } else {
                    echo "<p>No results found.</p>";
                    }
                } else {
                    echo "<p>Invalid table selected.</p>";
                }
            ?>
            <script>
                document.querySelectorAll('.pagination-link').forEach(link => {
                    link.addEventListener('click', function(e) {
                        e.preventDefault();
                        const page = this.getAttribute('data-page');
                        const urlParams = new URLSearchParams(window.location.search);
                        urlParams.set('page', page);
                        window.location.search = urlParams.toString();
                    });
                });
            </script>
            <div id="updateModal" class="updateModal">
                <div class="updatemodal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h3><i class="bi bi-repeat me-2" style="margin-right: 8px; color: #d70c0c;"></i>Update Record</h3>
                    <form method="post" action="" class="db-form">
                        <label for="id"><i class="bi bi-pencil-square"></i> ID to update:</label>
                        <input type="text" id="id" name="id" class="input-modal" readonly>

                        <label for="column"><i class="bi bi-columns"></i> Column to update:</label>
                        <input type="text" id="column" name="column" class="input-modal" required>

                        <label for="new_value"><i class="bi bi-edit"></i> New value:</label>
                        <input type="text" id="new_value" name="new_value" class="input-modal">

                        <input type="hidden" name="table" id="hiddenTable" value="<?php echo htmlspecialchars($table); ?>">
                        <input type="submit" name="update" value="Update Record" class="input-modal">
                    </form>
                </div>
            </div>

            <script>
                function openModal(id) {
                    document.getElementById('id').value = id;
                    document.getElementById('updateModal').style.display = "block";
                }
                function closeModal() {
                    document.getElementById('updateModal').style.display = "none";
                }
                window.onclick = function(event) {
                    if (event.target == document.getElementById('updateModal')) {
                        closeModal();
                    }
                }
            </script>
        </div>
    </section>
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

<script src="../../assets/sweetalert2/dist/sweetalert2.min.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
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
