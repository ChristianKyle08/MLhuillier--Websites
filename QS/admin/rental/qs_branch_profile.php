<?php
ob_start(); // 1. Trap any accidental output from included files
session_start(); // 2. Start session BEFORE including config
include '../../config/config.php';

echo '
  <!-- ✅ Local Google Font -->
    <link href="../../assets/css/poppins.css" rel="stylesheet">
    
    <!-- ✅ Local Bootstrap CSS -->
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ Local Bootstrap Icons -->
    <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">

    <!-- ✅ Local SweetAlert2 -->
    <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
';

if (isset($_POST['add_branch_profile'])) {
  // Sanitize inputs
  $branch_id = htmlspecialchars(trim($_POST['add_branch_id']));
  $alias = htmlspecialchars(trim($_POST['add_alias']));
  $mainzone = htmlspecialchars(trim($_POST['add_mainzone']));
  $zone = htmlspecialchars(trim($_POST['add_zone']));
  $code = htmlspecialchars(trim($_POST['add_code']));
  $kpx_code = htmlspecialchars(trim($_POST['add_kpx_code']));
  $branch_name = htmlspecialchars(trim($_POST['add_branch_name']));
  $corporate_name = htmlspecialchars(trim($_POST['add_corporate_name']));
  $region = htmlspecialchars(trim($_POST['add_region']));
  $gl_region = htmlspecialchars(trim($_POST['add_gl_region']));
  $maa_region = htmlspecialchars(trim($_POST['add_maa_region']));
  $para_region = htmlspecialchars(trim($_POST['add_para_region']));
  $area = htmlspecialchars(trim($_POST['add_area']));
  $maa = htmlspecialchars(trim($_POST['add_maa']));
  $para = htmlspecialchars(trim($_POST['add_para']));
  $cost_center = htmlspecialchars(trim($_POST['add_cost_center']));
  $smart_service_1 = htmlspecialchars(trim($_POST['add_smart_service_1']));
  $smart_service_2 = htmlspecialchars(trim($_POST['add_smart_service_2']));
  $bir_rdo = htmlspecialchars(trim($_POST['add_bir_rdo']));
  $kp_code = htmlspecialchars(trim($_POST['add_kp_code']));
  $ml_matic_region = htmlspecialchars(trim($_POST['add_ml_matic_region']));
  $ml_matic_status = htmlspecialchars(trim($_POST['add_ml_matic_status']));
  $oldregularloan_region = htmlspecialchars(trim($_POST['add_oldregularloan_region']));

  // Check if branch_id exists
  $check = $conn->prepare("SELECT branch_id FROM branch_insurance WHERE branch_id = ?");
  $check->bind_param("s", $branch_id);
  $check->execute();
  $check->store_result();

  if ($check->num_rows > 0) {
    // Duplicate branch_id
    echo "
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: 'Oops!',
            html: '<strong>Branch ID already exists.</strong><br>Please use a unique Branch ID to continue.',
            confirmButtonText: 'Got it',
            confirmButtonColor: '#ff6b6b',
            customClass: {
              title: 'fs-4 fw-bold',
              popup: 'rounded-4 shadow-lg',
              confirmButton: 'px-4 py-2'
            }
          }).then(() => {
            window.location.href = 'qs_branch_profile.php';
          });
        }
      });
    </script>
  ";
  } else {
    // Insert new branch
    $stmt = $conn->prepare("INSERT INTO branch_insurance (
      alias, mainzone, zone, code, kpx_code, branch_id, branch_name, corporate_name,
      region, gl_region, maa_region, para_region, area, maa, para, cost_center,
      smart_service_1, smart_service_2, bir_rdo, kp_code, ml_matic_region,
      ml_matic_status, oldregularloan_region
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("sssssssssssssssssssssss",
      $alias, $mainzone, $zone, $code, $kpx_code, $branch_id, $branch_name, $corporate_name,
      $region, $gl_region, $maa_region, $para_region, $area, $maa, $para, $cost_center,
      $smart_service_1, $smart_service_2, $bir_rdo, $kp_code, $ml_matic_region,
      $ml_matic_status, $oldregularloan_region
    );

    if ($stmt->execute()) {
      echo "
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'success',
              title: 'Success 🎉',
              html: 'The <strong>Branch</strong> has been added successfully!',
              timer: 2000,
              showConfirmButton: false,
              timerProgressBar: true,
              customClass: {
                popup: 'rounded-4 shadow-lg'
              }
            }).then(() => {
              window.location.href = 'qs_branch_profile.php';
            });
          }
        });
      </script>
    ";
    } else {
      echo "
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Something went wrong!',
              html: '<strong>We couldn’t save the branch information.</strong><br>Please try again later.',
              confirmButtonText: 'Close',
              confirmButtonColor: '#d33',
              customClass: {
                title: 'fs-4 fw-bold',
                popup: 'rounded-4 shadow-lg',
                confirmButton: 'px-4 py-2'
              }
            });
          }
        });
      </script>
    ";
    }

    $stmt->close();
  }
}
function getOptions($conn, $column) {
  $options = [];
  $stmt = $conn->prepare("SELECT DISTINCT `$column` FROM branch_insurance WHERE `$column` IS NOT NULL AND `$column` != '' ORDER BY `$column` ASC");
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $options[] = htmlspecialchars($row[$column]);
  }
  return $options;
}

// --- Field mapping: [column_name => input_name] ---
$fields = [
  "alias" => "add_alias",
  "mainzone" => "add_mainzone",
  "zone" => "add_zone",
  "code" => "add_code",
  "kpx_code" => "add_kpx_code",
  "branch_id" => "add_branch_id",
  "branch_name" => "add_branch_name",
  "corporate_name" => "add_corporate_name",
  "region" => "add_region",
  "gl_region" => "add_gl_region",
  "maa_region" => "add_maa_region",
  "para_region" => "add_para_region",
  "area" => "add_area",
  "maa" => "add_maa",
  "para" => "add_para",
  "cost_center" => "add_cost_center",
  "smart_service_1" => "add_smart_service_1",
  "smart_service_2" => "add_smart_service_2",
  "bir_rdo" => "add_bir_rdo",
  "kp_code" => "add_kp_code",
  "ml_matic_region" => "add_ml_matic_region",
  "ml_matic_status" => "add_ml_matic_status",
  "oldregularloan_region" => "add_oldregularloan_region"
];

// --- Fields that are required ---
$requiredFields = [
  "add_mainzone",
  "add_zone",
  "add_code",
  "add_kpx_code",
  "add_branch_id",
  "add_branch_name",
  "add_corporate_name",
  "add_region",
  "add_gl_region",
  "add_area",
  "add_ml_matic_status"
];

// --- Load options for each field ---
$optionsByField = [];
foreach ($fields as $dbColumn => $inputName) {
  $optionsByField[$inputName] = getOptions($conn, $dbColumn);
}

if (isset($_POST['update_branch_profile'])) {
  $selected_branch_id = $_POST['selected_branch_id'] ?? '';

  if (!empty($selected_branch_id)) {
      // Sanitize and assign fields
      $fieldsToUpdate = [
          "alias", "mainzone", "zone", "code", "kpx_code", "branch_name", "corporate_name",
          "region", "gl_region", "maa_region", "para_region", "area", "maa", "para",
          "cost_center", "smart_service_1", "smart_service_2", "bir_rdo", "kp_code",
          "ml_matic_region", "ml_matic_status", "oldregularloan_region"
      ];

      $updateData = [];
      $paramTypes = '';
      $paramValues = [];

      foreach ($fieldsToUpdate as $field) {
          $value = htmlspecialchars(trim($_POST[$field] ?? ''));
          $updateData[] = "$field = ?";
          $paramTypes .= 's';
          $paramValues[] = $value;
      }

      // Add WHERE id binding
      $paramTypes .= 's';
      $paramValues[] = $selected_branch_id;

      $sql = "UPDATE branch_insurance SET " . implode(', ', $updateData) . " WHERE branch_id = ?";
      $stmt = $conn->prepare($sql);

      if ($stmt) {
          $stmt->bind_param($paramTypes, ...$paramValues);

          if ($stmt->execute()) {
              echo "
              <script>
                  document.addEventListener('DOMContentLoaded', function () {
                      Swal.fire({
                          iconHtml: '<i class=\"bi bi-check-circle-fill text-success\" style=\"font-size: 2.5rem;\"></i>',
                          title: 'Branch Updated!',
                          html: 'The branch profile has been successfully updated.',
                          background: '#fff',
                          color: '#333',
                          timer: 2000,
                          showConfirmButton: false,
                          timerProgressBar: true,
                          customClass: {
                              popup: 'rounded-4 shadow-lg p-4',
                              title: 'fs-4 fw-bold',
                              htmlContainer: 'fs-6 text-center'
                          }
                      }).then(() => {
                          window.location.href = 'qs_branch_profile.php';
                      });
                  });
              </script>";
          } else {
              echo "
              <script>
                  Swal.fire({
                      iconHtml: '<i class=\"bi bi-x-circle-fill text-danger\" style=\"font-size: 2.5rem;\"></i>',
                      title: 'Update Failed',
                      html: 'An error occurred while updating the branch.',
                      background: '#fff',
                      color: '#333',
                      confirmButtonText: 'Close',
                      confirmButtonColor: '#d70c0c',
                      customClass: {
                          popup: 'rounded-4 shadow-lg p-4',
                          title: 'fs-4 fw-bold text-danger',
                          htmlContainer: 'fs-6 text-center'
                      }
                  });
              </script>";
          }

          $stmt->close();
      }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Branch Insurance Modal Form</title>
  <link rel="shortcut icon" href="../../../assets/images/mlw-logo-96x96.png" type="image/x-icon">
  <!-- ✅ Local Google Font -->
  <link href="../../../assets/css/poppins.css" rel="stylesheet">

  <!-- ✅ Local Bootstrap CSS -->
  <link href="../../../assets/css/bootstrap.min.css" rel="stylesheet">

  <!-- ✅ Local Bootstrap Icons -->
  <link href="../../../assets/icons/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/qs_branch_profile.css">
</head>
<body>
  <!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-header bg-success text-white rounded-top-4">
        <h5 class="modal-title" id="successModalLabel">Success</h5>
      </div>
      <div class="modal-body text-center">
        <i class="bi bi-check-circle-fill text-success fs-1 mb-3"></i>
        <p class="fs-5 mb-0">Branch Profile data inserted successfully.</p>
      </div>
    </div>
  </div>
</div>
<!-- Error Modal -->
<div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 shadow">
      <div class="modal-header bg-danger text-white rounded-top-4">
        <h5 class="modal-title" id="errorModalLabel">Error</h5>
      </div>
      <div class="modal-body text-center">
        <i class="bi bi-x-circle-fill text-danger fs-1 mb-3"></i>
        <p class="fs-5 mb-0">An error occurred while saving the data.</p>
      </div>
    </div>
  </div>
</div>

<div class="container mt-4">
  <a href="../../index.php" class="btn btn-light border shadow-sm rounded-pill d-inline-flex align-items-center px-4 py-2 back-btn">
    <i class="bi bi-arrow-left me-2"></i> Back
  </a>
</div>

<div class="container py-5">
  <div class="row g-4 justify-content-center">

    <!-- Add Card -->
    <div class="col-md-4">
      <div class="card card-hover shadow-sm rounded-4" data-bs-toggle="modal" data-bs-target="#addModal" onclick="openAddModal()">
        <div class="card-body text-center">
          <div class="mb-3"><i class="bi bi-plus-circle card-icon" style="color: #d70c0c;"></i></div>
          <h5 class="card-title">Add</h5>
          <p class="card-text text-muted">Click to add a new branch insurance record.</p>
        </div>
      </div>
    </div>

    <!-- Edit Card -->
    <div class="col-md-4">
      <div class="card card-hover shadow-sm rounded-4" data-bs-toggle="modal" data-bs-target="#editModal" onclick="openEditModal()">
        <div class="card-body text-center">
          <div class="mb-3"><i class="bi bi-pencil-square card-icon" style="color: #d70c0c;"></i></div>
          <h5 class="card-title">Edit</h5>
          <p class="card-text text-muted">Click to edit an existing branch record.</p>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content rounded-4">
      <div class="modal-header">
        <h5 class="modal-title" id="addModalLabel">Add Branch Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="addBranchForm" method="POST">
        <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
          <div class="row g-3">
            <?php foreach ($fields as $label => $name): ?>
              <div class="col-md-4">
                <label class="form-label"><?= ucwords(str_replace("_", " ", $label)) ?></label>
                <input type="text"
                      name="<?= $name ?>"
                      class="form-control"
                      list="<?= $name ?>_list"
                      autocomplete="off"
                      <?= in_array($name, $requiredFields) ? 'required' : '' ?> />
                <datalist id="<?= $name ?>_list">
                  <?php foreach ($optionsByField[$name] as $option): ?>
                    <option value="<?= $option ?>"></option>
                  <?php endforeach; ?>
                </datalist>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="modal-footer">
          <!-- ✅ Save button with Bootstrap icon -->
          <button type="submit" name="add_branch_profile" id="save_branch_profile" class="btn btn-success px-4">
            <i class="bi bi-check-circle me-2"></i>Save
          </button>

          <!-- ✅ Close button with Bootstrap icon -->
          <button type="button" class="btn btn-secondary" id="add_modalClose" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-2"></i>Close
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content rounded-4">
      <div class="modal-header">
        <h5 class="modal-title" id="editModalLabel">Edit Branch Profile</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="">
        <div class="modal-body" style="max-height: 60vh; overflow-y: auto;">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Branch ID</label>
              <select class="form-select" id="branch_id_select" name="branch_id">
                <option value="">Select Branch ID</option>
                <?php
                  if ($conn->connect_error) {
                    echo "<option disabled>Error connecting to database</option>";
                  } else {
                    $query = "SELECT branch_id, branch_name, region FROM branch_insurance ORDER BY branch_id ASC";
                    $result = $conn->query($query);

                    if ($result && $result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                        $branchId = htmlspecialchars($row['branch_id']);
                        $branchName = htmlspecialchars($row['branch_name']);
                        $region = htmlspecialchars($row['region']);
                        echo "<option value=\"$branchId\">$branchId - $branchName ($region)</option>";
                      }
                    } else {
                      echo "<option disabled>No branch records found</option>";
                    }
                    // REMOVED $conn->close() from here!
                  }
                ?>
              </select>
              <input type="hidden" name="selected_branch_id" id="selected_branch_id" />
            </div>

            <div class="col-md-4"><label class="form-label">Alias</label><input type="text" autocomplete="off" class="form-control" name="alias" /></div>
            <div class="col-md-4"><label class="form-label">Main Zone</label><input type="text" autocomplete="off" class="form-control" name="mainzone" /></div>
            <div class="col-md-4"><label class="form-label">Zone</label><input type="text" autocomplete="off" class="form-control" name="zone" /></div>

            <div class="col-md-4"><label class="form-label">Code</label><input type="text" autocomplete="off" class="form-control" name="code" /></div>
            <div class="col-md-4"><label class="form-label">KPX Code</label><input type="text" autocomplete="off" class="form-control" name="kpx_code" /></div>

            <div class="col-md-6"><label class="form-label">Branch Name</label><input type="text" autocomplete="off" class="form-control" name="branch_name" /></div>
            <div class="col-md-6"><label class="form-label">Corporate Name</label><input type="text" autocomplete="off" class="form-control" name="corporate_name" /></div>

            <div class="col-md-4"><label class="form-label">Region</label><input type="text" autocomplete="off" class="form-control" name="region" /></div>
            <div class="col-md-4"><label class="form-label">GL Region</label><input type="text" autocomplete="off" class="form-control" name="gl_region" /></div>
            <div class="col-md-4"><label class="form-label">MAA Region</label><input type="text" autocomplete="off" class="form-control" name="maa_region" /></div>

            <div class="col-md-4"><label class="form-label">PARA Region</label><input type="text" autocomplete="off" class="form-control" name="para_region" /></div>
            <div class="col-md-4"><label class="form-label">Area</label><input type="text" autocomplete="off" class="form-control" name="area" /></div>
            <div class="col-md-4"><label class="form-label">MAA</label><input type="text" autocomplete="off" class="form-control" name="maa" /></div>

            <div class="col-md-4"><label class="form-label">PARA</label><input type="text" autocomplete="off" class="form-control" name="para" /></div>
            <div class="col-md-4"><label class="form-label">Cost Center</label><input type="text" autocomplete="off" class="form-control" name="cost_center" /></div>
            <div class="col-md-4"><label class="form-label">Smart Service 1</label><input type="text" autocomplete="off" class="form-control" name="smart_service_1" /></div>

            <div class="col-md-4"><label class="form-label">Smart Service 2</label><input type="text" autocomplete="off" class="form-control" name="smart_service_2" /></div>
            <div class="col-md-4"><label class="form-label">BIR RDO</label><input type="text" autocomplete="off" class="form-control" name="bir_rdo" /></div>
            <div class="col-md-4"><label class="form-label">KP Code</label><input type="text" autocomplete="off" class="form-control" name="kp_code" /></div>

            <div class="col-md-6"><label class="form-label">ML Matic Region</label><input type="text" autocomplete="off" class="form-control" name="ml_matic_region" /></div>
            <div class="col-md-3"><label class="form-label">ML Matic Status</label><input type="text" autocomplete="off" class="form-control" name="ml_matic_status" /></div>
            <div class="col-md-12"><label class="form-label">Old Regular Loan Region</label><input type="text" autocomplete="off" class="form-control" name="oldregularloan_region" /></div>
          </div>
        </div>
        <div class="modal-footer">
          <!-- ✅ Update button with Bootstrap icon -->
          <button type="submit" id="updateButton" name="update_branch_profile" class="btn btn-primary px-4" disabled>
            <i class="bi bi-arrow-repeat me-2"></i>Update
          </button>

          <!-- ✅ Close button with Bootstrap icon -->
          <button type="button" id="edit_modalClose" class="btn btn-secondary" data-bs-dismiss="modal">
            <i class="bi bi-x-circle me-2"></i>Close
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="../../assets/sweetalert2/dist/sweetalert2.min.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('editModal');
  const inputs = modal.querySelectorAll('input[type="text"], select');
  const updateBtn = document.getElementById('updateButton');

  function checkInputs() {
    const hasValue = Array.from(inputs).some(input => input.value.trim() !== '');
    updateBtn.disabled = !hasValue;
  }

  // Run on input and select change
  inputs.forEach(input => {
    input.addEventListener('input', checkInputs);
    input.addEventListener('change', checkInputs);
  });

  // Recheck when modal is opened
  modal.addEventListener('shown.bs.modal', checkInputs);

  // Reset button when modal is closed
  modal.addEventListener('hidden.bs.modal', function () {
    updateBtn.disabled = true;
  });
});
  // This function runs when the Add card is clicked
  function openAddModal() {
    // Optional: Reset form fields in the Add Modal
    const addForm = document.querySelector('#addModal form');
    if (addForm) {
      addForm.reset();
    }

    // You can also log or run custom logic here if needed
    console.log('Add Modal opened');
  }

  // This function runs when the Edit card is clicked
  function openEditModal() {
    // Optional: Reset form fields in the Edit Modal or preload values
    const editForm = document.querySelector('#editModal form');
    if (editForm) {
      editForm.reset();
    }

    // Custom logic could go here, like loading selected branch data
    console.log('Edit Modal opened');
  }
  document.addEventListener("DOMContentLoaded", function () {
  const branchSelect = document.getElementById("branch_id_select");
  const updateBtn = document.getElementById("updateButton"); // Ensure your button has this ID

  if (branchSelect) {
    branchSelect.addEventListener("change", function () {
      const selectedBranchId = this.value;

      if (selectedBranchId !== "") {
        fetch(`../../user/rental/get_branch_by_id.php?branch_id=${encodeURIComponent(selectedBranchId)}`)
          .then(response => {
            if (!response.ok) throw new Error("Network error");
            return response.json();
          })
          .then(data => {
            if (data && !data.error) {
              // Loop through the returned database row
              Object.entries(data).forEach(([key, value]) => {
                // Find inputs by name inside the edit modal
                const input = document.querySelector(`#editModal [name="${key}"]`);
                if (input) {
                  input.value = (value !== null) ? value : "";
                }
              });

              // Set the hidden field for the WHERE clause in your update query
              document.getElementById("selected_branch_id").value = selectedBranchId;
              
              // Enable update button if it exists
              if (updateBtn) updateBtn.disabled = false;
            } else {
              alert(data.error || "No data found for this branch ID.");
            }
          })
          .catch(error => {
            console.error("Fetch error:", error);
            alert("An error occurred while fetching branch data. Check console for details.");
          });
      }
    });
  }
});
</script>

</body>
</html>
