<?php
ob_start(); // 1. Trap any accidental output from included files
session_start(); // 2. Start session BEFORE including config
   include '../../config/config.php';
   // config.php already opened $conn above, and — if $_SESSION['admin_id']
   // or $_SESSION['user_id'] is set — already stamped last_online for this
   // request (see the "every page" hook at the bottom of config.php). The
   // line that used to reconnect to the DB here was opening a second,
   // unused connection on every single request, so it has been removed.
   if (!$conn) {
      die("Connection failed: " . mysqli_connect_error());
   }

   if (!isset($_SESSION['admin_name'])) {
      header('location:../../login_form.php');
      exit; // was missing: without this, the rest of the page kept running
            // for a logged-out visitor even after the redirect was sent
   }
   $errors = array();
   $successMessages = array();
   
   // ✅ Create new user
   if (isset($_POST['submit'])) {
       $status = 'Active';
       $idNumber = mysqli_real_escape_string($conn, $_POST['idNum']);
       $mainzone = mysqli_real_escape_string($conn, $_POST['zone']);
       $region   = mysqli_real_escape_string($conn, $_POST['region']);
       $area     = mysqli_real_escape_string($conn, $_POST['area']);
       $fname    = mysqli_real_escape_string($conn, $_POST['fname']);
       $mname    = mysqli_real_escape_string($conn, $_POST['mname']);
       $lname    = mysqli_real_escape_string($conn, $_POST['lname']);
       $email    = mysqli_real_escape_string($conn, $_POST['email']);
       $ml_email = mysqli_real_escape_string($conn, $_POST['ml_email']);
       $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
       
       // Capture raw passwords for comparison before hashing
       $passwordInput = isset($_POST['password']) ? $_POST['password'] : 'Mlinc1234';
       $cpasswordInput = isset($_POST['cpassword']) ? $_POST['cpassword'] : 'Mlinc1234';
       
       $user_type = $_POST['user_type'];
       $user_roles = $_POST['role'];
   
       // ✅ check if user already exists
       $select = "SELECT * FROM user_form WHERE username = '$email' OR id_number = '$idNumber'";
       $result = mysqli_query($conn, $select);
       
       if (mysqli_num_rows($result) > 0) {
           $errors[] = 'User already exists!';
           $_SESSION['error_messages'] = $errors;
           header("Location: userLog.php");
           exit();
       } else {
           // ✅ Validate if passwords match using raw strings
           if ($passwordInput !== $cpasswordInput) {
               $errors[] = 'Passwords do not match!';
               $_SESSION['error_messages'] = $errors;
               header("Location: userLog.php");
               exit();
           } else {
               // ✅ Hashing the password with MD5
               $pass = md5($passwordInput);
   
               $insert = "INSERT INTO user_form
                   (id_number, mainzone, region, area, first_name, middle_name, last_name, username, email, contact_number, password, user_type, status, roles) 
                   VALUES 
                   ('$idNumber','$mainzone', '$region', '$area', '$fname', '$mname', '$lname', '$email', '$ml_email', '$contact_number', '$pass', '$user_type','$status', '$user_roles')";
               
               if(mysqli_query($conn, $insert)) {
                   $successMessages[] = 'User successfully created!';
                   $_SESSION['success_messages'] = $successMessages;
               } else {
                   $errors[] = 'Registration failed: ' . mysqli_error($conn);
                   $_SESSION['error_messages'] = $errors;
               }
               
               header("Location: userLog.php");
               exit();
           }
       }
   }

// ✅ keep success/error messages in session
if (isset($_SESSION['success_messages'])) {
    $successMessages = $_SESSION['success_messages'];
    unset($_SESSION['success_messages']);
} else if (isset($_SESSION['error_messages'])) {
    $errors = $_SESSION['error_messages'];
    unset($_SESSION['error_messages']);
}

// ✅ user search
if (isset($_POST['search'])) {
    $search = $_POST['search-input'];
    if (!empty($search)) {
        $query = "SELECT * FROM user_form 
                  WHERE id_number LIKE '%$search%' OR username LIKE '%$search%' OR mainzone LIKE '%$search%' 
                  OR region LIKE '%$search%' OR area LIKE '%$search%' 
                  OR first_name LIKE '%$search%' OR last_name LIKE '%$search%' 
                  OR middle_name LIKE '%$search%' OR email LIKE '%$search%' OR contact_number LIKE '%$search%'
                  OR user_type LIKE '%$search%' OR status LIKE '%$search%' 
                  OR roles LIKE '%$search%'";
        $result = mysqli_query($conn, $query);
        if ($result) {
            $searchResults = mysqli_fetch_all($result, MYSQLI_ASSOC);
        } else {
            $searchResults = array();
            $errors[] = "Error: " . mysqli_error($conn);
        }
    } else {
        $searchResults = array();
    }
} else {
    $searchResults = array();
}

// ✅ user update (Correctly mapped to hidden Database ID so ID Number and Username can be freely changed)
if (isset($_POST['update-user'])) {
   $edit_user_id = mysqli_real_escape_string($conn, $_POST['edit_user_id']); // Database Unique ID
   $edit_idNum = mysqli_real_escape_string($conn, $_POST['edit_idNum']); // Now safe to update
   $edit_user_type = mysqli_real_escape_string($conn, $_POST['edit_user_type'] ?? '');
   $edit_email = mysqli_real_escape_string($conn, $_POST['edit_email']); // This maps to Username
   $edit_mainzone = mysqli_real_escape_string($conn, $_POST['edit_zone']);
   $edit_region = mysqli_real_escape_string($conn, $_POST['edit_region']);
   $edit_area = mysqli_real_escape_string($conn, $_POST['edit_area']);
   $edit_first_name = mysqli_real_escape_string($conn, $_POST['edit_first_name']);
   $edit_middle_name = mysqli_real_escape_string($conn, $_POST['edit_middle_name']);
   $edit_last_name = mysqli_real_escape_string($conn, $_POST['edit_last_name']);
   $edit_ml_email = mysqli_real_escape_string($conn, $_POST['edit_ml_email']); // Maps to actual email
   $edit_contact_number = mysqli_real_escape_string($conn, $_POST['edit_contact_number']);
   $edit_roles = mysqli_real_escape_string($conn, $_POST['edit_role']);

   $sql = "UPDATE user_form SET 
               id_number = '$edit_idNum',
               user_type='$edit_user_type',
               mainzone = '$edit_mainzone',
               region = '$edit_region',
               area = '$edit_area',
               first_name='$edit_first_name',
               last_name='$edit_last_name',
               middle_name='$edit_middle_name',
               username='$edit_email',
               email = '$edit_ml_email',
               contact_number = '$edit_contact_number',
               roles = '$edit_roles'";

   if (!empty($_POST['password'])) {
       $edit_password = md5($_POST['password']);
       $sql .= ", password = '$edit_password'";
   }

   // Key update: Match the user by their unchangeable DB ID rather than their editable id_number
   $sql .= " WHERE id = '$edit_user_id'";

   if (mysqli_query($conn, $sql)) {
       $successMessages = array('User updated successfully!');
       $_SESSION['success_messages'] = $successMessages;
       header("Location: userLog.php");
       exit();
   } else {
       $errors[] = "Update failed: " . mysqli_error($conn);
       $_SESSION['error_messages'] = $errors;
       header("Location: userLog.php");
       exit();
   }
}

// ✅ password reset (Correctly targeting DB ID)
if (isset($_POST['reset_pass'])) {
   $edit_user_id = mysqli_real_escape_string($conn, $_POST['edit_user_id']); // Using hidden ID
   $edit_password = md5("Mlinc1234");

   $reset_query = "UPDATE user_form SET password = '$edit_password' WHERE id = '$edit_user_id'";
   
   if (mysqli_query($conn, $reset_query)) {
       $successMessages = array('Password reset successfully to default!');
       $_SESSION['success_messages'] = $successMessages;
       header("Location: userLog.php");
       exit();
   } else {
       $errors[] = "Database Error: " . mysqli_error($conn);
       $_SESSION['error_messages'] = $errors;
       header("Location: userLog.php");
       exit();
   }
}

$message = "";

// Safe trim to avoid undefined index + encoding issues
function safeTrim($arr, $i) {
    if (!isset($arr[$i])) return "";
    return mb_convert_encoding(trim((string)$arr[$i]), "UTF-8", "auto");
}
// Handle CSV upload
if (isset($_POST['import'])) {
   if (isset($_FILES['csv_file']['tmp_name']) && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {

       $fileName = $_FILES['csv_file']['tmp_name'];

       if ($_FILES['csv_file']['size'] > 0) {
           $file = fopen($fileName, "r");
           fgetcsv($file); // Skip header row

           $importSuccess = true;
           $updatedCount = 0;
           $insertedCount = 0;

           while (($column = fgetcsv($file, 10000, ",")) !== FALSE) {
               // prevent missing column crash
               if (count($column) < 11) continue;

               $id_number  = safeTrim($column, 0);
               $mainzone   = safeTrim($column, 1);
               $region     = safeTrim($column, 2);
               $area       = safeTrim($column, 3);
               $firstname  = safeTrim($column, 4);
               $middlename = safeTrim($column, 5);
               $lastname   = safeTrim($column, 6);
               $roles      = safeTrim($column, 7);
               $email      = safeTrim($column, 8);
               $contact    = safeTrim($column, 9);
               $statusCSV  = strtolower(safeTrim($column, 10));

               // Added condition to auto-capitalize the name fields
               $firstname  = strtoupper($firstname);
               $middlename = strtoupper($middlename);
               $lastname   = strtoupper($lastname);

               if ($id_number === "") continue;

               // Contact cleanup
               if ($contact !== "") {
                   $contact = preg_replace('/\D/', '', $contact);
                   if (strlen($contact) === 10 && $contact[0] !== '0') {
                       $contact = "0" . $contact;
                   }
                   $contact = substr($contact, 0, 11);
                   if (strlen($contact) !== 11) { $contact = null; }
               }

               $status = ($statusCSV === "active") ? "Active" : ucfirst($statusCSV);

               // Remove all spaces from the last name first
               $clean_lastname = str_replace(' ', '', $lastname); 
               
               $username = substr(strtolower(substr($clean_lastname, 0, 4)) . $id_number, 0, 20);

               // Added condition to auto-capitalize the username value
               $username = strtoupper($username);

               // Check if id_number exists
               $check = $conn->prepare("SELECT id FROM user_form WHERE id_number = ?");
               $check->bind_param("s", $id_number);
               $check->execute();
               $result = $check->get_result();

               if ($result->num_rows > 0) {
                   // UPDATE: All fields EXCEPT password
                   $stmt = $conn->prepare("UPDATE user_form SET username=?, mainzone=?, region=?, area=?, first_name=?, middle_name=?, last_name=?, roles=?, email=?, contact_number=?, status=? WHERE id_number=?");
                   $stmt->bind_param("ssssssssssss", $username, $mainzone, $region, $area, $firstname, $middlename, $lastname, $roles, $email, $contact, $status, $id_number);
                   
                   if($stmt->execute()) {
                       $updatedCount++;
                   } else {
                       $importSuccess = false;
                   }
               } else {
                  // INSERT: New user with default password (MD5 Hashed)
                  $defaultPassword = md5("Mlinc1234");
                  $user_type = "user";
              
                  $stmt = $conn->prepare("INSERT INTO user_form (id_number, username, mainzone, region, area, first_name, middle_name, last_name, roles, email, password, contact_number, user_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                  
                  // The bind_param and execute logic remains exactly the same
                  $stmt->bind_param("ssssssssssssss", $id_number, $username, $mainzone, $region, $area, $firstname, $middlename, $lastname, $roles, $email, $defaultPassword, $contact, $user_type, $status);
                  
                  if($stmt->execute()) {
                      $insertedCount++;
                  } else {
                      $importSuccess = false;
                  }
              }
           }
           fclose($file);

           if ($importSuccess) {
               $successMessages[] = "Import complete: $insertedCount new users added, $updatedCount existing users updated.";
               $_SESSION['success_messages'] = $successMessages;
           } else {
               $errors[] = "Import finished with some errors. Please check your data formatting.";
               $_SESSION['error_messages'] = $errors;
           }
       } else {
           $errors[] = "The uploaded CSV file is empty.";
           $_SESSION['error_messages'] = $errors;
       }
   } else {
       $errors[] = "No file was uploaded or there was a file error.";
       $_SESSION['error_messages'] = $errors;
   }
   
   // Redirect to show the messages
   header("Location: userLog.php");
   exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <title>ML Rental - Admin Users </title>
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
           font-size:12px;
        }
        .navbar {
            background-color: #fff;
            border-bottom: 3px solid #d70c0c;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .nav-link {
            color: #333 !important;
            font-weight: 500;
        }
        .nav-link:hover,
        .dropdown-item:hover {
            color: #d70c0c !important;
        }
        .dropdown-menu {
            border-radius: 6px;
            border: 1px solid #eee;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
        }
        .dropdown-item {
            font-size: 13px;
        }
        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 13px;
        }
        .admin-info i {
            color: #d70c0c;
        }

        .dashboard-header {
            background-color: #fff;
            padding: 20px;
            border-bottom: 3px solid #d70c0c;
        }
        .table thead {
            background-color: #d70c0c;
            color: #fff;
        }
        .badge {
            font-size: 12px;
        }
        .modal-content {
            border-radius: 12px;
        }
        .btn-danger {
            background-color: #d70c0c;
            border-color: #d70c0c;
        }
        .btn-danger:hover {
            background-color: #b50a0a;
            border-color: #a00909;
        }
        /* Container */
.s-div {
  padding: 24px;
  background-color: #fff;
  color: #333;
}

/* Search panel */
#search-div {
  background: #fff;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
}

/* Form layout */
.form-group {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Left (search input + button) */
.left-div {
  display: flex;
  gap: 12px;
  width: 100%;
}

/* Search input */
#search-input {
  flex: 1;
  padding: 12px 20px;
  border-radius: 50px;
  border: 1px solid #ccc;
  font-size: 12px;
  background-color: #fff;
  transition: 0.3s ease;
  color: #333;
}

#search-input:focus {
  outline: none;
  border-color: #d70c0c;
  box-shadow: 0 0 0 3px rgba(215, 12, 12, 0.1);
}

/* Search button */
#search {
  background-color: #d70c0c;
  border: none;
  padding: 0 20px;
  height: 44px;
  border-radius: 50px;
  color: #fff;
  font-weight: bold;
  font-size: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.3s ease;
}

#search:hover {
  background-color: #b50909;
}

/* Right action buttons */
.right-div {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  justify-content: flex-end;
}

.right-div button {
  background-color: #f1f1f1;
  color: #333;
  padding: 10px 16px;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.right-div button:hover {
  background-color: #d70c0c;
  color: #fff;
}

/* Search Results + User Table */
#search-results,
.table-wrap {
  margin-top: 15px;
  padding: 10px;
  background-color: #fff;
  border-radius: 16px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
  overflow-x: auto;
}

#search-results h3,
.table-wrap h3 {
  color: #d70c0c;
  margin-bottom: 8px;
  font-size: 16px;
  font-weight: 700;
}

/* Table styling */
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 12px;
}

thead {
  background-color: #f9f9f9;
  color: #333;
  font-weight: 600;
}

th, td {
  padding: 8px 10px;
  text-align: left;
  border-bottom: 1px solid #eee;
  white-space: nowrap;
}

tbody tr:hover {
  background-color: #f2f2f2;
  cursor: pointer;
}

td i.fa-circle {
  font-size: 10px;
  margin-right: 6px;
  vertical-align: middle;
}

/* Status styling */
td[style*="color: green"],
td[style*="color: red"] {
  font-weight: 700;
}

/* Responsive layout */
@media (max-width: 768px) {
  .form-group {
    gap: 20px;
  }

  .left-div {
    flex-direction: column;
  }

  .right-div {
    flex-direction: column;
    align-items: stretch;
  }

  .right-div button,
  #search {
    width: 100%;
    justify-content: center;
  }
}
/* Modal background overlay */
.modal_popup {
  display: none;
  position: fixed;
  z-index: 1050;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow-y: auto;
  background-color: rgba(0, 0, 0, 0.6);
  padding: 40px 20px;
  font-size: 12px;
  color: #333;
}

/* Shared modal content */
.register_modal-content,
.edit_modal-content {
  background: #fff;
  margin: auto;
  max-width: 900px;
  border-radius: 12px;
  padding: 20px;
  position: relative;
  animation: slideIn 0.3s ease;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
  font-size: 12px;
}

/* Modal close button */
.modal_popup .close {
  position: absolute;
  top: 12px;
  right: 16px;
  font-size: 20px;
  font-weight: bold;
  color: #d70c0c;
  cursor: pointer;
  transition: 0.2s ease-in-out;
}

.modal_popup .close:hover {
  color: #a50b0b;
  transform: scale(1.1);
}

/* Logo */
.logo img {
  width: 80px;
  display: block;
  margin: 0 auto 12px;
}

/* Header */
.register_modal-content h3,
.edit_modal-content h3 {
  padding: 8px;
  border-radius: 6px;
  text-align: center;
  color: #29348e;
  font-size: 14px;
  margin-bottom: 16px;
}

/* Form layout */
.inputs {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 15px;
}

.l-c-i,
.r-c-i {
  flex: 1;
  min-width: 280px;
}

/* Input containers */
.input-container {
  display: flex;
  flex-direction: column;
  margin-bottom: 12px;
}

.input-container label {
  font-weight: 600;
  margin-bottom: 4px;
  color: #333;
  font-size: 12px;
}

.input-container input,
.input-container select {
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 12px;
  background-color: #fff;
  color: #333;
  transition: 0.3s;
}

.input-container input:focus,
.input-container select:focus {
  border-color: #d70c0c;
  outline: none;
  box-shadow: 0 0 0 1px rgba(215, 12, 12, 0.3);
}

/* Password validation message */
#message {
  background: #f5f5f5;
  padding: 8px;
  border-radius: 6px;
  margin-bottom: 12px;
  font-size: 12px;
}

#message h3 {
  margin-top: 0;
  font-size: 13px;
}

#message p.invalid {
  color: red;
}

#message p.valid {
  color: green;
}

/* Roles section */
.roles-c {
  margin: 12px 0;
}

.t-c-roles,
.edit_select-roles {
  padding: 8px;
  border-radius: 8px;
}

.select-roles h3,
.edit_select-roles h3 {
  font-size: 13px;
  color: #29348e;
  margin-bottom: 8px;
}

#roles_list,
#edit_roles_list {
  list-style: none;
  padding-left: 0;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
  gap: 6px;
}

.checkbox,
.list_r {
  background: #fff;
  border: 1px solid #ccc;
  padding: 6px;
  border-radius: 6px;
  font-size: 12px;
}

/* Buttons */
.form-btn,
#update-user,
#reset_pass {
  background-color: #d70c0c;
  color: #fff;
  border: none;
  padding: 8px 24px;
  font-size: 12px;
  font-weight: 600;
  border-radius: 6px;
  margin: 6px;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.form-btn:hover,
#update-user:hover,
#reset_pass:hover {
  background-color: #a50b0b;
}

/* Animation */
@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .register_modal-content,
  .edit_modal-content {
    padding: 16px;
  }

  .inputs {
    flex-direction: column;
  }

  #roles_list,
  #edit_roles_list {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  }
}

    </style>
<body>
<?php include ('navbar_admin.php'); ?>
<div class="modal fade" id="dbModal" tabindex="-1" aria-labelledby="dbModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow border-0 rounded-4" style="background-color: #fff; color: #333;">
      
      <div class="modal-header border-0" style="background-color: #f8f9fa;">
        <h5 class="modal-title d-flex align-items-center" id="dbModalLabel">
          <i class="bi bi-shield-lock-fill me-2 text-danger"></i> Secure Access
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

   <div class="s-div">
      <div id="search-div">
         <form method="POST" class="form-group">
            <div class="left-div">
            <input type="text" id="search-input" maxlength="40" name="search-input"
               value="<?php if (isset($_POST['search'])) echo $_POST['search']; ?>"
               placeholder=" Search..." autocomplete="off">
            <button type="submit" id="search" name="search">
               <i class="bi bi-search"></i>
            </button>
            </div>

            <div class="right-div">
            <button type="button" id="add" name="add" onclick="showModal('register-modal')">
               <i class="bi bi-person-plus me-1"></i>Add
            </button>

            <button type="button" id="edit" name="edit" onclick="showEditModal()">
               <i class="bi bi-pencil-square me-1"></i>Edit
            </button>

            <button type="button" id="delete" name="delete" onclick="deleteRow()">
               <i class="bi bi-person-dash me-1"></i>Delete
            </button>

            <button type="button" id="update" name="update" onclick="updateStatus()">
            <i class="bi bi-toggles me-1"></i>Change Status
            </button>
            <!-- ✅ Button to open modal -->
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-upload"></i> Import CSV
            </button>
            </div>
         </form>
         <?php if (!empty($searchResults) && isset($_POST['search']) && !empty($search)) : ?>
            <div id="search-results">
               <h3 style="color: #d70c0c;">SEARCH RESULT</h3>
                  <table>
                     <thead>
                        <tr>
                           <th style="display:none;"></th>
                           <th>User Type</th>
                           <th>ID Number</th>
                           <th>Username</th>
                           <th>Main Zone</th>
                           <th>Region</th>
                           <th>Area</th>
                           <th>First Name</th>
                           <th>Middle Name</th>
                           <th>Last Name</th>
                           <th>Email</th>
                           <th>Phone Number</th>
                           <th style="display:none;">Password</th>
                           <th>Roles</th>
                           <th>Last Online</th>
                           <th>Status</th>
                        </tr>
                     </thead>
                     <tbody>
                     <?php foreach ($searchResults as $result) : ?>
                        <tr onclick="selectRow(this)">
                           <td name="id" style="text-align:left; padding-left:10px; display:none;"><?php echo $result['id']; ?></td>
                           <td style="text-align:center; padding-left:10px; width:fit-content;"><?php echo $result['user_type']; ?></td>
                           <td style="text-align:left; padding-left:10px;"><?php echo $result['id_number']; ?></td>
                           <td style="text-align:left; padding-left:10px; "><?php echo $result['username']; ?></td>
                           <td style="text-align:left; padding-left:10px;"><?php echo $result['mainzone']; ?></td>
                           <td style="text-align:left; padding-left:10px;"><?php echo $result['region']; ?></td>
                           <td style="text-align:left; padding-left:10px;"><?php echo $result['area']; ?></td>
                           <td style="text-align:left; padding-left:10px;"><?php echo $result['first_name']; ?></td>
                           <td style="text-align:left; padding-left:10px;"><?php echo $result['middle_name']; ?></td>
                           <td style="text-align:left; padding-left:10px;"><?php echo $result['last_name']; ?></td>
                           <td style="text-align:left; padding-left:10px;"><?php echo $result['email']; ?></td>
                           <td style="text-align:left; padding-left:10px;"><?php echo $result['contact_number']; ?></td>
                           <td style="text-align:left; padding-left:10px; display:none;"><?php echo $result['password']; ?></td>
                           <td style="text-align:left; padding-left:10px;"><?php echo $result['roles']; ?></td>
                           <td style="text-align:left; padding-left:10px;"><?php echo isset($result['last_online']) ? date('F j, Y, g:i:s A', strtotime($result['last_online'])) : ''; ?></td>
                           <td style="text-align:center;width: 150px; font-weight:700; padding-left:10px; <?php echo $result['status'] === 'Active' ? 'color: green;' : ($result['status'] === 'Inactive' ? 'color: red;' : ''); ?>"><i style="font-size:10px; margin-right: 10px;" class="fa-solid fa-circle"></i><?php echo $result['status']; ?></td>
                        </tr>
                     <?php endforeach; ?>
                     </tbody>
                  </table>
               </div>
               <?php endif; ?>
            </div>
            <div class="table-wrap">
               <table id="users-table">
                  <h3 style="color: #d70c0c;">ALL USERS</h3>
                  <thead>
                     <tr>
                        <th style="display:none;"></th>
                        <th><i class="bi bi-person-badge me-1 text-secondary"></i>User Type</th>
                        <th>ID <i class="bi bi-hash me-1 text-secondary"></i></th>
                        <th><i class="bi bi-person-circle me-1 text-secondary"></i>Username</th>
                        <th><i class="bi bi-globe2 me-1 text-secondary"></i>Main Zone</th>
                        <th><i class="bi bi-geo-alt-fill me-1 text-secondary"></i>Region</th>
                        <th><i class="bi bi-map me-1 text-secondary"></i>Area</th>
                        <th><i class="bi bi-person-lines-fill me-1 text-secondary"></i>First Name</th>
                        <th><i class="bi bi-person-lines-fill me-1 text-secondary"></i>Middle Name</th>
                        <th><i class="bi bi-person-lines-fill me-1 text-secondary"></i>Last Name</th>
                        <th><i class="bi bi-envelope-fill me-1 text-secondary"></i>Email</th>
                        <th><i class="bi bi-telephone-fill me-1 text-secondary"></i>Phone Number</th>
                        <th style="display:none;"><i class="bi bi-shield-lock-fill me-1 text-secondary"></i>Password</th>
                        <th><i class="bi bi-person-gear me-1 text-secondary"></i>Roles</th>
                        <th><i class="bi bi-clock-history me-1 text-secondary"></i>Last Online</th>
                        <th><i class="bi bi-circle-fill me-1 text-secondary"></i>Status</th>
                     </tr>
                  </thead>
                  <tbody>
                  <?php
                  $query = "SELECT * FROM user_form ORDER BY region ASC";
                  $result = mysqli_query($conn, $query);
                     if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                        ?>
                        <tr onclick="selectRow(this)">
                           <td name="id" style="text-align:left; padding-left:10px; display:none;"><?php echo $row['id']; ?></td>
                           <td style="text-align:center; padding-left:10px; width:fit-content;"><?php echo $row['user_type']; ?></td>
                           <td style="text-align:left; padding-left:10px; width:fit-content;"><?php echo $row['id_number']; ?></td>
                           <td style="text-align:left; padding-left:10px; width:fit-content;"><?php echo $row['username']; ?></td>
                           <td style="text-align:left; padding-left:10px; width:fit-content;"><?php echo $row['mainzone']; ?></td>
                           <td style="text-align:left; padding-left:10px; width:fit-content;"><?php echo $row['region']; ?></td>
                           <td style="text-align:left; padding-left:10px; width:fit-content;"><?php echo $row['area']; ?></td>
                           <td style="text-align:left; padding-left:10px; width:fit-content;"><?php echo $row['first_name']; ?></td>
                           <td style="text-align:left; padding-left:10px; width:fit-content;"><?php echo $row['middle_name']; ?></td>
                           <td style="text-align:left; padding-left:10px; width:fit-content;"><?php echo $row['last_name']; ?></td>
                           <td style="text-align:left; padding-left:10px; width:fit-content;"><?php echo $row['email']; ?></td>
                           <td style="text-align:left; padding-left:10px; width:fit-content;"><?php echo $row['contact_number']; ?></td>
                           <td style="text-align:left; padding-left:10px; display:none; width:fit-content;"><?php echo $row['password']; ?></td>
                           <td style="text-align:left; padding-left:10px; width:fit-content;"><?php echo $row['roles']; ?></td>
                           <td style="text-align:left; padding-left:10px; width:fit-content;"><?php echo isset($row['last_online']) ? date('F j, Y, g:i:s A', strtotime($row['last_online'])) : ''; ?></td>
                           <td style="text-align:center;width: 150px; font-weight:700; padding-left:10px; width:fit-content; <?php echo $row['status'] === 'Active' ? 'color: green;' : ($row['status'] === 'Inactive' ? 'color: red;' : ''); ?>"><i style="font-size:10px; margin-right: 10px;" class="bi bi-toggles fa-fade"></i><?php echo $row['status']; ?></td>
                        </tr>
                        <?php
                        }
                     }
                  ?>
                  </tbody>
               </table>
            </div>
      </div>
   </div>

   <div id="register-modal" class="modal_popup">
      <div class="register_modal-content">
         <span class="close" onclick="hideModal('register-modal'); clearRegisterForm();">&times;</span>
         <form action="" method="post" class="register">
            <h3 style="color: #d70c0c; margin-top:10px; padding:5px; border-top-left-radius:5px; border-top-right-radius:5px;"><i class="bi bi-r-circle me-2"></i>Register</h3>
            <div class="inputs">
               <div class="l-c-i">
                  <div class="input-container">
                     <label for="user-type"><i class="bi bi-person-badge me-1 text-secondary"></i>User Type</label>
                     <select name="user_type" id="user-type">
                        <option value="" disabled selected></option>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                     </select>
                  </div>
                  <div class="input-container">
                     <label for="email"><i class="bi bi-person-circle me-1 text-secondary"></i>Username</label>
                     <input class="add_inp" type="text" name="email" id="email" required autocomplete="off">
                  </div>
                  <div class="input-container">
                     <label for="idNum"><i class="bi bi-hash me-1 text-secondary"></i>ID Number</label>
                     <input class="add_inp" type="text" name="idNum" id="idNum" required autocomplete="off">
                  </div>
                  <div class="input-container">
                     <label for="region"><i class="bi bi-geo-alt-fill me-1 text-secondary"></i>Region</label>
                     <select name="region" id="region" class="region_select" onchange="this.form.submit()" required>
                        <option value="" <?php echo (!isset($_POST['region'])) ? 'selected' : ''; ?>></option>
                        <option value="HO"?>HO</option>
                        <?php
                        $branch_insurance = "SELECT DISTINCT region FROM branch_insurance WHERE region != '' ORDER BY region ASC";
                        $resultregion = mysqli_query($conn, $branch_insurance);
                        if ($resultregion) {
                            while ($rowregion = mysqli_fetch_assoc($resultregion)) {
                                $selected = (isset($_POST['region']) && $_POST['region'] == $rowregion['region']) ? 'selected' : '';
                                echo "<option value='" . $rowregion['region'] . "' $selected>" . $rowregion['region'] ."</option>";
                            }
                        }
                        ?>
                     </select>
                  </div>
                  <div class="input-container">
                     <label for="area"><i class="bi bi-map-fill me-1 text-secondary"></i>Area</label>
                     <select name="area" id="area" class="area_select" required>
                        <option value="" <?php echo (!isset($_POST['area'])) ? 'selected' : ''; ?>></option>
                        <?php
                        $branch_insurance = "SELECT DISTINCT area FROM branch_insurance WHERE area != '' ORDER BY area ASC";
                        $resultarea = mysqli_query($conn, $branch_insurance);
                        if ($resultarea) {
                            while ($rowrarea = mysqli_fetch_assoc($resultarea)) {
                                $selected = (isset($_POST['area']) && $_POST['area'] == $rowrarea['area']) ? 'selected' : '';
                                echo "<option value='" . $rowrarea['area'] . "' $selected>" . $rowrarea['area'] ."</option>";
                            }
                        }
                        ?>
                     </select>
                  </div>
                  <div class="input-container" style="visibility:hidden;">
                     <label for="blank">blank</label>
                     <select name="blank" id="blank" class="blank_select">
                     </select>
                  </div>
               </div>
               <div class="r-c-i">
                  <div class="input-container">
                     <label for="zone"><i class="bi bi-globe2 me-1 text-secondary"></i>Main Zone</label>
                     <select name="zone" id="zone" class="zone_select" required>
                        <option value="" <?php echo (!isset($_POST['zone'])) ? 'selected' : ''; ?>></option>
                        <?php
                        $branch_insurance = "SELECT DISTINCT mainzone FROM branch_insurance WHERE mainzone != '' ORDER BY mainzone ASC";
                        $resultzone = mysqli_query($conn, $branch_insurance);
                        if ($resultzone) {
                            while ($rowzone = mysqli_fetch_assoc($resultzone)) {
                                $selected = (isset($_POST['zone']) && $_POST['zone'] == $rowzone['mainzone']) ? 'selected' : '';
                                echo "<option value='" . $rowzone['mainzone'] . "' $selected>" . $rowzone['mainzone'] ."</option>";
                            }
                        }
                        ?>
                     </select>
                  </div>
                  <div class="input-container">
                     <label for="fname"><i class="bi bi-person-lines-fill me-1 text-secondary"></i>Firstname</label>
                     <input class="add_inp" type="text" name="fname" id="fname" required autocomplete="off">
                  </div>
                  <div class="input-container">
                     <label for="mname"><i class="bi bi-person-lines-fill me-1 text-secondary"></i>Middle Name</label>
                     <input class="add_inp" type="text" name="mname" id="mname" autocomplete="off">
                  </div>
                  <div class="input-container">
                     <label for="lname"><i class="bi bi-person-lines-fill me-1 text-secondary"></i>Lastname</label>
                     <input class="add_inp" type="text" name="lname" id="lname" autocomplete="off">
                  </div>
                  <div class="input-container">
                     <label for="ml_email"><i class="bi bi-envelope-fill me-1 text-secondary"></i>Email</label>
                     <input class="add_inp" type="text" name="ml_email" id="ml_email" autocomplete="off">
                  </div>
                  <div class="input-container">
                     <label for="contact_number"><i class="bi bi-phone-fill me-1 text-secondary"></i>Phone Number</label>
                     <input class="add_inp" type="text" name="contact_number" id="contact_number" autocomplete="off">
                  </div>
                  <div class="input-container">
                     <label for="psw"><i class="bi bi-shield-lock-fill me-1 text-secondary"></i>Enter your password</label>
                     <input class="add_inp" id="psw" type="text" name="password" required
                        pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters"
                        autocomplete="off" value="Mlinc1234" readonly>
                  </div>
                  <div id="message" style="display: none;">
                     <h3>Password must contain the following:</h3>
                     <p id="letter" class="invalid">A <b>lowercase</b> letter</p>
                     <p id="capital" class="invalid">A <b>capital (uppercase)</b> letter</p>
                     <p id="number" class="invalid">A <b>number</b></p>
                     <p id="length" class="invalid">Minimum <b>8 characters</b></p>
                  </div>
                  <div class="input-container">
                     <label for="cpass"><i class="bi bi-shield-check me-1 text-secondary"></i>Confirm your password</label>
                     <input class="add_inp" type="text" name="cpassword" id="cpass" required autocomplete="off" value="Mlinc1234" readonly>
                  </div>
               </div>
            </div>
            <div class="roles-c">
               <div class="t-c-roles">
                  <div class="select-roles"> 
                     <h3 style="color: #d70c0c; text-align: left; margin-top:10px; padding:5px; border-top-left-radius:5px; border-top-right-radius:5px;"><i class="bi bi-person-fill-gear me-2"></i>Roles</h3>
                     <ul id="roles_list">
                        <li class="checkbox">
                           <input type="checkbox" id="cad_role" value="Am-Creator" onchange="displaySelectedValues()"> AM CREATOR
                        </li>
                        <li class="checkbox">
                           <input type="checkbox" id="finance_role" value="Rm-Reviewer" onchange="displaySelectedValues()"> RM REVIEWER
                        </li>
                        <li class="checkbox">
                           <input type="checkbox" id="telecoms_role" value="Vpo-Checker" onchange="displaySelectedValues()"> VPO CHECKER
                        </li>
                        <li class="checkbox">
                           <input type="checkbox" id="telecoms_role" value="Vpo-Reviewer" onchange="displaySelectedValues()"> VPO REVIEWER
                        </li>
                        <li class="checkbox">
                           <input type="checkbox" id="telecoms_role" value="Auditor" onchange="displaySelectedValues()"> AUDITOR
                        </li>
                        <li class="checkbox">
                           <input type="checkbox" id="telecoms_role" value="Finance" onchange="displaySelectedValues()"> FINANCE
                        </li>
                        <li class="checkbox">
                           <input type="checkbox" id="telecoms_role" value="Vpo-Approver" onchange="displaySelectedValues()"> VPO APPROVER
                        </li>
                        <li class="checkbox">
                           <input type="checkbox" id="telecoms_role" value="Cancellor" onchange="displaySelectedValues()"> CANCELLOR
                        </li>
                        <li class="checkbox">
                           <input type="checkbox" id="telecoms_role" value="HO" onchange="displaySelectedValues()"> HO
                        </li>
                     </ul>
                     <input type="hidden" id="input_id" name="role" value="">
                  </div>
               </div>
            </div>
            <center>
               <button type="submit" id="register" name="submit" class="form-btn" onclick="validateForm()" disabled>
                  <i class="bi bi-check-circle me-1"></i> REGISTER NOW
               </button>
            </center>
         </form>
      </div>
   </div>

   <div id="edit-modal" class="modal_popup" >
      <div class="edit_modal-content">
         <span class="close" id="close" onclick="hideModal('edit-modal')">&times;</span>
         <form method="POST" action="">
            <center>
               <h3 style="color: #d70c0c;"><i class="bi bi-pencil-square me-2"></i>Edit User</h3>
            </center>
            <div class="inputs-div">
               <div class="inputs">
                  <div class="l-c-i">
                     <div class="input-container">
                        <input type="hidden" id="edit_user_id" name="edit_user_id">
                     </div>
                     <div class="input-container">
                        <label for="edit_user_type"><i class="bi bi-person-badge me-2"></i>User Type</label>
                        <select id="edit_user_type" name="edit_user_type">
                           <option value="" disabled selected>Select User Type</option>
                           <option value="admin">Admin</option>
                           <option value="user">User</option>
                        </select>
                     </div>
                     <div class="input-container">
                        <label for="edit_email"><i class="bi bi-envelope-at me-2"></i>Username</label>
                        <input type="text" id="edit_email" name="edit_email" autocomplete="off">
                     </div>
                     <div class="input-container">
                        <label for="edit_idNum"><i class="bi bi-credit-card-2-front me-2"></i>ID Number</label>
                        <input type="text" name="edit_idNum" id="edit_idNum" autocomplete="off">
                     </div>
                     <div class="input-container">
                        <label for="edit_region"><i class="bi bi-globe me-2"></i>Region</label>
                        <select name="edit_region" id="edit_region" class="region_select" >
                           <option value="" <?php echo (!isset($_POST['edit_region'])) ? 'selected' : ''; ?>></option>
                           <option value="HO"?>HO</option>
                           <?php
                              $branch_insurance = "SELECT DISTINCT region FROM branch_insurance WHERE region != '' ORDER BY region ASC";
                              $resultregion = mysqli_query($conn, $branch_insurance);
                              if ($resultregion) {
                                 while ($rowregion = mysqli_fetch_assoc($resultregion)) {
                                    $selected = (isset($_POST['edit_region']) && $_POST['edit_region'] == $rowregion['region']) ? 'selected' : '';
                                    echo "<option value='" . $rowregion['region'] . "' $selected>" . $rowregion['region'] ."</option>";
                                 }
                              }
                           ?>
                        </select>
                     </div>
                     <div class="input-container">
                        <label for="edit_area"><i class="bi bi-geo-alt me-2"></i>Area</label>
                        <select name="edit_area" id="edit_area" class="area_select" >
                           <option value="" <?php echo (!isset($_POST['edit_area'])) ? 'selected' : ''; ?>></option>
                           <?php
                              $branch_insurance = "SELECT DISTINCT area FROM branch_insurance WHERE area != '' ORDER BY area ASC";
                              $resultarea = mysqli_query($conn, $branch_insurance);
                              if ($resultarea) {
                                 while ($rowrarea = mysqli_fetch_assoc($resultarea)) {
                                    $selected = (isset($_POST['edit_area']) && $_POST['edit_area'] == $rowrarea['area']) ? 'selected' : '';
                                    echo "<option value='" . $rowrarea['area'] . "' $selected>" . $rowrarea['area'] ."</option>";
                                 }
                              }
                           ?>
                        </select>
                     </div>
                     <div class="input-container">
                        <label for="edit_zone"><i class="bi bi-map me-2"></i>Main Zone</label>
                        <select name="edit_zone" id="edit_zone" class="zone_select" >
                           <option value="" <?php echo (!isset($_POST['edit_zone'])) ? 'selected' : ''; ?>></option>
                           <?php
                           $branch_insurance = "SELECT DISTINCT mainzone FROM branch_insurance WHERE mainzone != '' ORDER BY mainzone ASC";
                           $resultzone = mysqli_query($conn, $branch_insurance);
                           if ($resultzone) {
                              while ($rowzone = mysqli_fetch_assoc($resultzone)) {
                                 $selected = (isset($_POST['edit_zone']) && $_POST['edit_zone'] == $rowzone['mainzone']) ? 'selected' : '';
                                 echo "<option value='" . $rowzone['mainzone'] . "' $selected>" . $rowzone['mainzone'] ."</option>";
                              }
                           }
                           ?>
                        </select>
                     </div>
                  </div>

                  <div class="r-c-i">
                     <div class="input-container">
                        <label for="edit_first_name"><i class="bi bi-person me-2"></i>First Name</label>
                        <input type="text" id="edit_first_name" name="edit_first_name" autocomplete="off">
                     </div>
                     <div class="input-container">
                        <label for="edit_middle_name"><i class="bi bi-person me-2"></i>Middle Name</label>
                        <input type="text" id="edit_middle_name" name="edit_middle_name" autocomplete="off">
                     </div>
                     <div class="input-container">
                        <label for="edit_last_name"><i class="bi bi-person me-2"></i>Last Name</label>
                        <input type="text" id="edit_last_name" name="edit_last_name" autocomplete="off">
                     </div>
                     <div class="input-container">
                        <label for="edit_ml_email"><i class="bi bi-envelope me-2"></i>Email</label>
                        <input type="text" id="edit_ml_email" name="edit_ml_email" autocomplete="off">
                     </div>
                     <div class="input-container">
                        <label for="edit_contact_number"><i class="bi bi-phone me-2"></i>Phone Number</label>
                        <input type="text" id="edit_contact_number" name="edit_contact_number" autocomplete="off">
                     </div>
                     <div class="input-container">
                        <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                        <input id="password" type="password" value="" name="password" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters" autocomplete="off">
                     </div>
                     <div class="input-container">
                        <label for="edit_role"><i class="bi bi-briefcase me-2"></i>Roles</label>
                        <input  type="text" name="edit_role" id="edit_role" value="" readonly>
                     </div>
                  </div>
               </div>

               <div class="roles-c">
                  <div class="t-c-roles">
                     <div class="edit_select-roles"> 
                        <h3 style="color: #d70c0c; text-align: left; margin-top:10px; padding:5px; border-top-left-radius:5px; border-top-right-radius:5px;"><i class="bi bi-person-fill-gear me-2"></i>Roles</h3>
                        <ul id="edit_roles_list">
                           <li class="list_r">
                              <input type="checkbox" value="Am-Creator" id="edit_role_cad" onchange="edit_displaySelectedValues()"> AM CREATOR
                           </li>
                           <li class="list_r">
                              <input type="checkbox" value="Rm-Reviewer" id="edit_role_finance" onchange="edit_displaySelectedValues()"> RM REVIEWER
                           </li>
                           <li class="list_r">
                              <input type="checkbox" value="Vpo-Checker" id="edit_role_telecoms" onchange="edit_displaySelectedValues()"> VPO CHECKER
                           </li>
                           <li class="list_r">
                              <input type="checkbox" value="Vpo-Reviewer" id="edit_role_telecoms" onchange="edit_displaySelectedValues()"> VPO REVIEWER
                           </li>
                           <li class="list_r">
                              <input type="checkbox" value="Auditor" id="edit_role_telecoms" onchange="edit_displaySelectedValues()"> AUDITOR
                           </li>
                           <li class="list_r">
                              <input type="checkbox" value="Finance" id="edit_role_telecoms" onchange="edit_displaySelectedValues()"> FINANCE
                           </li>
                           <li class="list_r">
                              <input type="checkbox" value="Vpo-Approver" id="edit_role_telecoms" onchange="edit_displaySelectedValues()"> VPO APPROVER
                           </li>
                           <li class="list_r">
                              <input type="checkbox" value="Cancellor" id="edit_role_telecoms" onchange="edit_displaySelectedValues()"> CANCELLOR
                           </li>
                           <li class="list_r">
                              <input type="checkbox" value="HO" id="edit_role_telecoms" onchange="edit_displaySelectedValues()"> HO
                           </li>
                        </ul>
                     </div>
                  </div>
               </div>
               <center>
                  <button type="submit" id="update-user" name="update-user" onclick="edit_validateForm()"><i class="bi bi-check-circle me-2"></i>Update User</button>
                  <button type="submit" id="reset_pass" name="reset_pass"><i class="bi bi-arrow-clockwise me-2"></i>Reset Password</button>
               </center>
            </div>
         </form>
      </div>
   </div>
   <!-- ✅ Bootstrap Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content rounded-3 shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="importModalLabel"><i class="bi bi-file-earmark-spreadsheet"></i> Import Users CSV</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" enctype="multipart/form-data">
        <div class="modal-body">
          <p>Select a CSV file with columns:</p>
          <small><b>ID Number, Mainzone, Region, Area, Firstname, Middlename, Lastname, Roles, Email, Contact_number, Status</b></small>
          <div class="mt-3">
            <input type="file" name="csv_file" accept=".csv" class="form-control" required>
          </div>
          <?php if (!empty($message)): ?>
            <div class="alert alert-info mt-3"><?= htmlspecialchars($message) ?></div>
          <?php endif; ?>
        </div>
        <div class="modal-footer">
          <button type="submit" name="import" class="btn btn-success"><i class="bi bi-check-circle"></i> Import</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-circle"></i> Cancel</button>
        </div>
      </form>
    </div>
  </div>
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
<script src="../../assets/sweetalert2/dist/sweetalert2.min.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<!-- Keeps last_online fresh while this page is open and pings once more on close -->
<script>window.LAST_ONLINE_ENDPOINT = '../../fetch/last_online.php';</script>
<script src="../../assets/js/last-online-tracker.js"></script>
   <script> 
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
    
   function displaySelectedValues() {
      var selectedValues = [];
      var checkboxes = document.querySelectorAll('#roles_list input[type="checkbox"]:checked');
      for (var i = 0; i < checkboxes.length; i++) {
         selectedValues.push(checkboxes[i].value);
      }
      document.getElementById("input_id").value = selectedValues.join(", ");
      validateForm();
   }
   function validateForm() {
      var inputId = document.getElementById("input_id").value;
      var registerButton = document.getElementById("register");
      if (inputId === "") {
         registerButton.disabled = true;
      } else {
         registerButton.disabled = false;
      }
   }
   function edit_validateForm() {
      var inputId = document.getElementById("edit_role").value;
      var updateButton = document.getElementById("update-user");
      if (inputId === "") {
         updateButton.disabled = true;
      } else {
         updateButton.disabled = false;
      }
   }
   function edit_displaySelectedValues() {
      var selectedValues = [];
      var checkboxes = document.querySelectorAll('#edit_roles_list input[type="checkbox"]:checked');
      for (var i = 0; i < checkboxes.length; i++) {
         selectedValues.push(checkboxes[i].value);
      }
      document.getElementById("edit_role").value = selectedValues.join(", ");
      edit_validateForm();
   }

        document.getElementById('logoutLink').addEventListener('click', function (e) {
    e.preventDefault();

    // Criterion #3: record this moment as last_online before the session
    // is destroyed by logout.php. sendBeacon is used because it's designed
    // to reliably deliver a request even as the page is navigating away.
    if (navigator.sendBeacon) {
        navigator.sendBeacon('../../fetch/last_online.php', new Blob([], { type: 'text/plain' }));
    }

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
      <?php if(!empty($errors)): ?>
         Swal.fire({
            title: "Error!",
            html: "<?php echo implode('<br>', $errors); ?>",
            icon: "error",
            allowOutsideClick: false
         });
      <?php endif; ?>
      <?php if(!empty($successMessages)): ?>
         Swal.fire({
            title: "Success!",
            html: "<?php echo implode('<br>', $successMessages); ?>",
            icon: "success",
            allowOutsideClick: false
         });
      <?php endif; ?>
      function clearRegisterForm() {
         document.getElementById('user-type').selectedIndex = 0;
         document.getElementById('email').value = '';
         document.getElementById('idNum').value = '';
         document.getElementById('mainzone').value = '';
         document.getElementById('region').value = '';
         document.getElementById('area').value = '';
         document.getElementById('fname').value = '';
         document.getElementById('mname').value = '';
         document.getElementById('lname').value = '';
         document.getElementById('lname').value = '';
         document.getElementById('cad_role').checked = false;
         document.getElementById('finance_role').checked = false;
         document.getElementById('telecoms_role').checked = false;
         document.getElementById('input_id').value = '';

      }
      function showModal(modalId) {
         var modal = document.getElementById(modalId);
         modal.style.display = 'block';
      }
      function hideModal(modalId) {
         var modal = document.getElementById(modalId);
         modal.style.display = 'none';
      }

      function selectRow(row) {
         var selectedRow = document.querySelector('.selected');   
         if (selectedRow) {
            selectedRow.classList.remove('selected');
            selectedRow.style.backgroundColor = '';
            selectedRow.style.color = '';
         }
         if (selectedRow !== row) {
            row.classList.add('selected');
            row.style.backgroundColor = '#f2f2f2';
            row.style.color = 'black';
         }
      }
      function populateModalInputs() {
         var selectedRow = document.querySelector('.selected');
         if (selectedRow) {
            var inputs = document.getElementById('edit-modal').getElementsByTagName('input');
            var select = document.getElementById('edit_user_type');
            inputs[0].value = selectedRow.cells[0].textContent;
            inputs[1].value = selectedRow.cells[1].textContent;
            inputs[2].value = selectedRow.cells[2].textContent;
            inputs[3].value = selectedRow.cells[3].textContent;
            inputs[4].value = selectedRow.cells[4].textContent;
            inputs[5].value = selectedRow.cells[5].textContent;
            inputs[6].value = selectedRow.cells[6].textContent;
            inputs[7].value = selectedRow.cells[7].textContent;
            inputs[8].value = selectedRow.cells[8].textContent;
            inputs[9].value = selectedRow.cells[9].textContent;
            select.value = selectedRow.cells[9].textContent.toLowerCase();
            inputs[10].value = selectedRow.cells[10].textContent;
            inputs[11].value = selectedRow.cells[11].textContent;
            inputs[12].value = selectedRow.cells[12].textContent;
            inputs[13].value = selectedRow.cells[13].textContent;
            var editRole = selectedRow.cells[13].textContent.toLowerCase();
            var editRoles = editRole.split(',').map(function(role) {
               return role.trim();
            });
      
            var checkboxes = document.querySelectorAll('#edit_roles_list input[type="checkbox"]');
            checkboxes.forEach(function(checkbox) {
               if (editRoles.includes(checkbox.value.toLowerCase())) {
                  checkbox.checked = true;
               } else {
                  checkbox.checked = false;
               }
            });
            showModal('edit-modal');
         }
      }

      function showEditModal() {
   var selectedRow = document.querySelector('#users-table tr.selected');
   var selectedRow2 = document.querySelector('#search-results tr.selected');
   if (selectedRow || selectedRow2) {
      var cells = (selectedRow || selectedRow2).getElementsByTagName('td');

      // Fill inputs
      document.getElementById('edit_user_id').value = cells[0].innerText.trim();

      // User type dropdown (Admin/User)
      setSelectValue("edit_user_type", cells[1].innerText.trim());

      var idNumberValue = cells[2].innerText.trim(); 
      
      document.getElementById('edit_idNum').value = idNumberValue;
      
      document.getElementById('edit_email').value = cells[3].innerText.trim();

      // Select fields (zone, region, area)
      setSelectValue("edit_zone", cells[4].innerText.trim());
      setSelectValue("edit_region", cells[5].innerText.trim());
      setSelectValue("edit_area", cells[6].innerText.trim());

      // Other text inputs
      document.getElementById('edit_first_name').value = cells[7].innerText.trim();
      document.getElementById('edit_middle_name').value = cells[8].innerText.trim();
      document.getElementById('edit_last_name').value = cells[9].innerText.trim();
      document.getElementById('password').value = '';
      document.getElementById('edit_ml_email').value = cells[10].innerText.trim();
      document.getElementById('edit_contact_number').value = cells[11].innerText.trim();
      document.getElementById('edit_role').value = cells[13].innerText.trim();

      // Roles checkbox handling
      var editRole = cells[13].innerText.toLowerCase();
      var editRoles = editRole.split(',').map(function(role) {
         return role.trim();
      });

      var checkboxes = document.querySelectorAll('#edit_roles_list input[type="checkbox"]');
      checkboxes.forEach(function(checkbox) {
         if (editRoles.includes(checkbox.value.toLowerCase())) {
            checkbox.checked = true;
         } else {
            checkbox.checked = false;
         }
      });

      showModal('edit-modal');
   } else {
      Swal.fire({
         title: 'Warning',
         text: 'Please select a user to edit.',
         icon: 'warning',
         showConfirmButton: true,
         allowOutsideClick: true,
      });
   }
}

// Helper function for selects
function setSelectValue(selectId, value) {
   var select = document.getElementById(selectId);
   if (!select) return;

   var found = false;
   for (var i = 0; i < select.options.length; i++) {
      if (select.options[i].value.trim().toLowerCase() === value.toLowerCase()) {
         select.selectedIndex = i;
         found = true;
         break;
      }
   }
   if (!found && value !== "") {
      var opt = document.createElement("option");
      opt.value = value;
      opt.text = value;
      opt.selected = true;
      select.add(opt);
   }
}


      function updateStatus() {
   const selectedRow = document.querySelector('.selected');

   if (!selectedRow) {
      Swal.fire({
         title: 'No User Selected!',
         text: 'Please select a user to update status.',
         icon: 'warning',
         confirmButtonColor: '#d70c0c',
         background: '#fff',
         color: '#333'
      });
      return;
   }

   const idCell = selectedRow.querySelector('td[name="id"]');
   const statusCell = selectedRow.querySelector('td:last-child');

   if (!idCell || !statusCell) return;

   const id = idCell.textContent.trim();
   const currentStatus = statusCell.textContent.trim();
   const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
   const iconColor = newStatus === 'Active' ? 'green' : 'red';
   const iconHtml = `<i class="bi bi-toggles fade-pulse me-2" style="font-size: 10px;"></i> ${newStatus}`;

   Swal.fire({
      title: 'Change Status?',
      text: `Do you want to change this user's status to "${newStatus}"?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#d70c0c',
      cancelButtonColor: '#999',
      confirmButtonText: `<i class="bi bi-toggles me-2"></i> Confirm`,
      cancelButtonText: '<i class="bi bi-x-circle-fill me-2"></i>Cancel',
      background: '#fff',
      color: '#333'
   }).then((result) => {
      if (result.isConfirmed) {
         // Update visually first
         statusCell.innerHTML = iconHtml;
         statusCell.style.color = iconColor;

         // Send AJAX request
         const xhr = new XMLHttpRequest();
         xhr.open('POST', 'update_status.php', true);
         xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
         xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
               if (xhr.status === 200) {
                  if (xhr.responseText.trim() === 'success') {
                     Swal.fire({
                        title: 'Success!',
                        text: `User status updated to "${newStatus}".`,
                        icon: 'success',
                        confirmButtonColor: '#d70c0c',
                        background: '#fff',
                        color: '#333',
                        timer: 1800,
                        showConfirmButton: false
                     });
                  } else {
                     throw new Error();
                  }
               } else {
                  throw new Error();
               }
            }
         };

         xhr.onerror = function () {
            Swal.fire({
               title: 'Error!',
               text: 'Failed to update user status. Please try again.',
               icon: 'error',
               confirmButtonColor: '#d70c0c',
               background: '#fff',
               color: '#333'
            });
         };

         xhr.send(`id=${id}&status=${newStatus}`);
      }
   });
}

   function deleteRow() {
   const selectedRow = document.querySelector('.selected');

   if (!selectedRow) {
      Swal.fire({
         title: 'No Row Selected!',
         text: 'Please select a user to delete.',
         icon: 'warning',
         confirmButtonColor: '#d70c0c',
         confirmButtonText: '<i class="bi bi-check-circle-fill me-2"></i> Okay',
         background: '#fff',
         color: '#333'
      });
      return;
   }

   Swal.fire({
      title: 'Delete User?',
      html: '<strong>Are you sure you want to delete this user?</strong>',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d70c0c',
      cancelButtonColor: '#999',
      confirmButtonText: '<i class="bi bi-trash3 me-2"></i> Delete',
      cancelButtonText: '<i class="bi bi-x-circle-fill me-2"></i>Cancel',
      background: '#fff',
      color: '#333',
      focusCancel: true,
      allowOutsideClick: false
   }).then((result) => {
      if (result.isConfirmed) {
         const id = selectedRow.cells[0].textContent;
         const formData = new FormData();
         formData.append('id', id);

         fetch('delete.php', {
            method: 'POST',
            body: formData
         })
         .then(response => {
            if (!response.ok) throw new Error('Failed to delete user.');
            return response.text();
         })
         .then(data => {
            if (data === 'success') {
               selectedRow.remove();
               Swal.fire({
                  title: 'Deleted!',
                  text: 'User deleted successfully.',
                  icon: 'success',
                  confirmButtonColor: '#d70c0c',
                  background: '#fff',
                  color: '#333',
                  timer: 1800,
                  showConfirmButton: false
               });
            } else {
               throw new Error('Failed to delete user.');
            }
         })
         .catch(() => {
            Swal.fire({
               title: 'Error!',
               text: 'An error occurred. Please try again.',
               icon: 'error',
               confirmButtonColor: '#d70c0c',
               background: '#fff',
               color: '#333'
            });
         });
      }
   });
}

   function storeValues() {
      populateModalInputs();
      showModal('register-modal');
   }

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