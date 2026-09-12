<?php
session_start();
include '../../config/config.php';

// Check for login session
if (!isset($_SESSION['user_email'])) {
    header('location:login_form.php');
    exit();
}

// -----------------------------
// LOGIC: Use Sessions for location data
// -----------------------------
$user_email    = mysqli_real_escape_string($conn, $_SESSION['user_email']);
$userRegion    = $conn->real_escape_string($_SESSION['region'] ?? '');
$userArea      = $conn->real_escape_string($_SESSION['area'] ?? '');
$userMainzone  = $conn->real_escape_string($_SESSION['mainzone'] ?? '');
$userRole      = $_SESSION['user_role'] ?? ''; // From login session

// Error fallback if session data is missing
if (empty($userRegion) && $userRole !== 'HO') {
    die("<div class='alert alert-danger'>Error: User region and area not found in session.</div>");
}

// -----------------------------
// Lessor Names Query (Logic Preserved)
// -----------------------------
if ($userRole == 'HO') {
    $lessorNamesQuery = "
        SELECT 
            id, 
            TRIM(
                CONCAT_WS(' ',
                    UPPER(lessor_type), '|',
                    COALESCE(corporate_name, ''),
                    COALESCE(first_name, ''),
                    COALESCE(middle_name, ''),
                    COALESCE(last_name, ''),
                    COALESCE(address, ''),
                    COALESCE(gender, ''),
                    COALESCE(region, ''),
                    COALESCE(area, '')
                )
            ) AS display_name
        FROM lessor_profile";
} else {
    $lessorNamesQuery = "
        SELECT 
            id, 
            TRIM(
                CONCAT_WS(' ',
                    UPPER(lessor_type), '|',
                    COALESCE(corporate_name, ''),
                    COALESCE(first_name, ''),
                    COALESCE(middle_name, ''),
                    COALESCE(last_name, ''),
                    COALESCE(address, ''),
                    COALESCE(gender, ''),
                    COALESCE(region, ''),
                    COALESCE(area, '')
                )
            ) AS display_name
        FROM lessor_profile
        WHERE region = '$userRegion' AND area = '$userArea'";
}

$result = $conn->query($lessorNamesQuery);
$lessorOptions = "";
$allLessorList = "";

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $displayName = htmlspecialchars($row['display_name'] ?? '');
        $lessorOptions .= '<option value="' . $displayName . '" data-id="' . $row['id'] . '"></option>';
        $allLessorList .= '<li class="list-group-item list-group-item-action">' . $displayName . '</li>';
    }
}

$editData = array_fill_keys([
    'id', 'first_name', 'middle_name', 'last_name', 'gender',
    'address', 'region', 'area', 'corporate_name',
    'mobile_number', 'lessor_type', 'main_zone'
], '');

// -----------------------------
// Search Logic (Logic Preserved)
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_btn'])) {
    $searchLessor = trim($_POST['search_lessor']);

    if (!empty($searchLessor)) {
        $stmt = $conn->prepare("
            SELECT * FROM lessor_profile 
            WHERE TRIM(CONCAT_WS(' ',
                UPPER(lessor_type), '|',
                COALESCE(corporate_name, ''),
                COALESCE(first_name, ''),
                COALESCE(middle_name, ''),
                COALESCE(last_name, ''),
                COALESCE(address, ''),
                COALESCE(gender, ''),
                COALESCE(region, ''),
                COALESCE(area, '')
            )) = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $searchLessor);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $editData = $result->fetch_assoc();
        } else {
            echo "<script>document.addEventListener('DOMContentLoaded', function () { Swal.fire({ icon: 'warning', title: 'Not Found', text: 'No matching record found!', confirmButtonColor: '#d70c0c' }); });</script>";
        }
        $stmt->close();
    }
}

// -----------------------------
// Update Logic (Logic Preserved)
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_btn'])) {
    foreach ($_POST as $key => $value) {
        $$key = $conn->real_escape_string(trim($value));
    }

    if (isset($lessor_type) && strtolower($lessor_type) === 'sole proprietorship') {
        $lessor_type = 'Individual';
    }

    $updateFields = [];

    if ($lessor_type === 'Individual') {
        $updateFields[] = "first_name = '$first_name'";
        $updateFields[] = "middle_name = '$middle_name'";
        $updateFields[] = "last_name = '$last_name'";
        $updateFields[] = "gender = '$gender'";
        $updateFields[] = "corporate_name = NULL";
    } elseif (in_array($lessor_type, ['Corporate', 'LGU'])) {
        $updateFields[] = "corporate_name = '$corporate_name'";
        $updateFields[] = "first_name = NULL";
        $updateFields[] = "middle_name = NULL";
        $updateFields[] = "last_name = NULL";
        $updateFields[] = "gender = NULL";
    }

    $updateFields[] = "address = '$address'";
    $updateFields[] = "region = '$region'";
    $updateFields[] = "area = '$area'";
    $updateFields[] = "mobile_number = '$mobile_number'";

    if (!empty($lessor_type)) { $updateFields[] = "lessor_type = '$lessor_type'"; }
    if (!empty($mainzone)) { $updateFields[] = "main_zone = '$mainzone'"; }

    $updateQuery = "UPDATE lessor_profile SET " . implode(', ', $updateFields) . " WHERE id = $id";

    if ($conn->query($updateQuery)) {
        $_SESSION['update_status'] = 'success';
    } else {
        $_SESSION['update_status'] = 'error';
        $_SESSION['update_message'] = $conn->error;
    }

  echo '<script>window.location.href="edit_lessor_profile.php";</script>';
    exit();
}

// Display SweetAlert status (Logic Preserved)
if (isset($_SESSION['update_status'])): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {
    Swal.fire({
        icon: '<?= $_SESSION['update_status'] === "success" ? "success" : "error" ?>',
        title: '<?= $_SESSION['update_status'] === "success" ? "Success!" : "Update Failed" ?>',
        text: '<?= $_SESSION['update_status'] === "success" ? "Record updated successfully." : "Error: " . $_SESSION["update_message"] ?>',
        confirmButtonColor: '#d70c0c'
    });
});
</script>
<?php unset($_SESSION['update_status'], $_SESSION['update_message']); ?>
<?php endif; ?>
<!DOCTYPE html>
    <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
            <link rel="shortcut icon" href="../../assets/images/mlw-logo-96x96.png" type="image/x-icon">
            <meta name="description" content="">
            <title>ML Rental - Edit Lessor</title>
            <!-- ✅ Local Google Font -->
            <link href="../../assets/css/poppins.css" rel="stylesheet">

            <!-- ✅ Local Bootstrap CSS -->
            <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">

            <!-- ✅ Local Bootstrap Icons -->
            <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">

            <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
            <!-- ✅ Your custom CSS should come AFTER font import -->
            <link rel="stylesheet" href="../../assets/css/sidebar.css">
            <style>
                body {
                    background-color: #f8f9fa; /* Light background for the whole page */
                }
                .btn-outline-gray {
                    color: #495057;
                    border: 1px solid #ced4da;
                    background-color: #fff;
                }
                .btn-outline-gray:hover {
                    background-color: #e9ecef;
                    color: #212529;
                }
                /* Visual distinction for readonly inputs */
                input[readonly] {
                    background-color: #e9ecef !important;
                    color: #6c757d;
                    cursor: not-allowed;
                }
                /* Soft shadows for form inputs */
                .form-control:focus {
                    box-shadow: 0 0 0 0.25rem rgba(215, 12, 12, 0.25);
                    border-color: #d70c0c;
                }
            </style>
        </head>
    <body>
    <?php include ('navbar.php'); ?>

    <div id="mainContent">
        <button id="toggleSidebar" class="btn btn-light border shadow-sm text-dark d-flex align-items-center mx-4 my-3 rounded-pill px-3 py-2">
            <i class="bi bi-list me-2 fs-5" style="color: #d70c0c;"></i>
            <span class="fw-semibold">Menu</span>
        </button>
        
        <div class="container pb-5">
            <div class="card shadow rounded-4 border-0 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 px-4">
                    <h4 class="card-title text-dark mb-0 fw-bold"><i class="bi bi-pencil-square text-danger me-2"></i> Edit Lessor Profile</h4>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    
                    <!-- Search Box Section -->
                    <div class="bg-light p-4 rounded-3 border mb-5 shadow-sm">
                        <form method="POST" class="m-0">
                            <label class="form-label fw-bold text-secondary mb-2"><i class="bi bi-search me-1"></i> Search Lessor to Edit</label>
                            <div class="input-group input-group-lg shadow-sm rounded">
                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                <input list="lessor_list" name="search_lessor" id="search_lessor" class="form-control border-start-0 ps-0" placeholder="Type or select a lessor..." autocomplete="off">
                                <datalist id="lessor_list">
                                    <?= $lessorOptions; ?>
                                </datalist>
                                <button type="submit" name="search_btn" class="btn btn-danger px-4 fw-semibold"><i class="bi bi-search me-1 d-none d-md-inline"></i> Search</button>
                                <button type="button" class="btn btn-outline-gray px-4 fw-semibold" data-bs-toggle="modal" data-bs-target="#lessorListModal">
                                    <i class="bi bi-list-ul me-1"></i> View All
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Edit Form Section -->
                    <?php if (!empty($editData['id'])): ?>
                    <div class="px-2">
                        <h5 class="fw-bold text-secondary border-bottom pb-2 mb-4"><i class="bi bi-person-lines-fill me-2"></i> Profile Details</h5>
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $editData['id']; ?>">
                            <?php $readonly = ($userRole == 'HO') ? 'readonly' : ''; ?>

                            <div class="row g-4">
                                <?php
                                $fieldIcons = [
                                    'first_name'     => 'bi-person',
                                    'middle_name'    => 'bi-person',
                                    'last_name'      => 'bi-person',
                                    'corporate_name' => 'bi-building',
                                    'gender'         => 'bi-gender-ambiguous',
                                    'address'        => 'bi-geo-alt',
                                    'main_zone'      => 'bi-diagram-3',
                                    'region'         => 'bi-globe',
                                    'area'           => 'bi-map',
                                    'mobile_number'  => 'bi-telephone',
                                    'lessor_type'    => 'bi-person-vcard'
                                ];

                                $lessorType = $editData['lessor_type'] ?? '';
                                $fields = [
                                    'lessor_type'    => 'Lessor Type',
                                    'first_name'     => 'First Name',
                                    'middle_name'    => 'Middle Name',
                                    'last_name'      => 'Last Name',
                                    'gender'         => 'Gender',
                                    'corporate_name' => 'Corporate Name',
                                    'address'        => 'Address',
                                    'main_zone'      => 'Main Zone',
                                    'region'         => 'Region',
                                    'area'           => 'Area',
                                    'mobile_number'  => 'Mobile Number'
                                ];

                                foreach ($fields as $field => $label):
                                    $value = $editData[$field] ?? '';
                                ?>
                                    <?php if ($field === 'lessor_type'): ?>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold text-muted">
                                                <i class="bi <?= $fieldIcons[$field] ?> me-1"></i> <?= $label ?>
                                            </label>
                                            <input 
                                                type="text" 
                                                id="lessorTypeInput"
                                                name="lessor_type"
                                                class="form-control form-control-lg shadow-sm" 
                                                value="<?= $lessorType === 'Individual' ? 'Sole Proprietorship' : htmlspecialchars($lessorType) ?>" 
                                                data-value="<?= htmlspecialchars($lessorType) ?>" 
                                                readonly>
                                        </div>
                                        <?php elseif ($field === 'corporate_name'): ?>
                                        <div class="col-md-6" id="corporateNameField">
                                            <?php
                                            $customLabel = ($lessorType === 'LGU') ? 'Local Government Unit' : 'Corporate Name';
                                            ?>
                                            <label class="form-label fw-semibold text-muted">
                                                <i class="bi <?= $fieldIcons[$field] ?> me-1"></i> <?= $customLabel ?>
                                            </label>
                                            <input 
                                                type="text" 
                                                name="<?= $field ?>" 
                                                class="form-control form-control-lg shadow-sm" 
                                                value="<?= htmlspecialchars($value) ?>" 
                                                <?= $readonly ?>>
                                        </div>

                                    <?php elseif (in_array($field, ['first_name', 'middle_name', 'last_name', 'gender'])): ?>
                                        <div class="col-md-6 personalField" id="<?= $field ?>Field">
                                            <label class="form-label fw-semibold text-muted">
                                                <i class="bi <?= $fieldIcons[$field] ?> me-1"></i> <?= $label ?>
                                            </label>
                                            <input 
                                                type="text" 
                                                name="<?= $field ?>" 
                                                class="form-control form-control-lg shadow-sm" 
                                                value="<?= htmlspecialchars($value) ?>" 
                                                <?= $readonly ?>>
                                        </div>
                                        <?php else: ?>
                                            <div class="col-md-6">
                                                <label class="form-label fw-semibold text-muted">
                                                    <i class="bi <?= $fieldIcons[$field] ?> me-1"></i> <?= $label ?>
                                                </label>

                                                <?php if ($field === 'mobile_number'): ?>
                                                    <input 
                                                        type="text" 
                                                        name="<?= $field ?>" 
                                                        class="form-control form-control-lg shadow-sm" 
                                                        value="<?= htmlspecialchars($value) ?>" 
                                                        <?= in_array($field, ['main_zone', 'region', 'area']) ? 'readonly' : $readonly; ?> 
                                                        autocomplete="off"
                                                        maxlength="11"
                                                        pattern="\d{11}"
                                                        title="Mobile number must be 11 digits"
                                                        placeholder="09XXXXXXXXX"
                                                        oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                                <?php else: ?>
                                                    <input 
                                                        type="text" 
                                                        name="<?= $field ?>" 
                                                        class="form-control form-control-lg shadow-sm" 
                                                        value="<?= htmlspecialchars($value) ?>" 
                                                        <?= in_array($field, ['main_zone', 'region', 'area']) ? 'readonly' : $readonly; ?> 
                                                        autocomplete="off">
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>

                                <?php if ($userRole != 'HO'): ?>
                                    <div class="d-flex justify-content-end mt-5 pt-3 border-top">
                                        <button type="submit" name="update_btn" class="btn btn-danger btn-lg px-5 shadow-sm fw-bold">
                                            <i class="bi bi-arrow-repeat me-2"></i> Save Changes
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>

                    <!-- JavaScript to dynamically show/hide fields based on lessor type (readonly input) -->
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const lessorTypeInput = document.getElementById('lessorTypeInput');
                            const corporateNameField = document.getElementById('corporateNameField');
                            const personalFields = document.querySelectorAll('.personalField');

                            function toggleFields() {
                                const type = lessorTypeInput.value.trim();

                                // Corporate Name: only visible if not Individual
                                if (type === 'Individual' || type === 'Sole Proprietorship') {
                                    corporateNameField.style.display = 'none';
                                } else {
                                    corporateNameField.style.display = 'block';
                                }

                                // Personal Fields: only visible if not Corporation or LGU
                                if (type === 'Corporate' || type === 'LGU') {
                                    personalFields.forEach(field => field.style.display = 'none');
                                } else {
                                    personalFields.forEach(field => field.style.display = 'block');
                                }
                            }

                            // Initial check
                            toggleFields();
                        });
                    </script>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

<!-- Modal to show all lessors -->
<div class="modal fade" id="lessorListModal" tabindex="-1" aria-labelledby="lessorListModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-scrollable" style="max-width: 50%;">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-danger text-white border-bottom-0">
        <h5 class="modal-title fw-bold" id="lessorListModalLabel">
          <i class="bi bi-card-list me-2"></i> All Lessors
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <ul class="list-group list-group-flush rounded-bottom">
          <?= $allLessorList; ?>
        </ul>
      </div>
    </div>
  </div>
</div>


<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4">
      <div class="modal-body">
        <div class="text-center mb-3">
          <i class="bi bi-box-arrow-right text-danger" style="font-size: 3rem;"></i>
        </div>
        <h5 class="mb-2 fw-bold">Logging Out</h5>
        <p class="text-muted mb-4">Please wait while we securely log you out...</p>
        <div class="progress rounded-pill shadow-sm" style="height: 10px;">
          <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 100%;"></div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>window.LAST_ONLINE_ENDPOINT = '../../fetch/last_online.php';</script>
<script src="../../assets/js/last-online-tracker.js"></script>
<script src="../../assets/sweetalert2/dist/sweetalert2.min.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
    const toggleBtn = document.getElementById('toggleSidebar');
  const sidebar = document.getElementById('sidebarMenu');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
  });
document.addEventListener('DOMContentLoaded', function () {
    // Logout Modal Trigger
    const logoutLink = document.getElementById('logoutLink');
    const logoutModalElement = document.getElementById('logoutModal');

    if (logoutLink && logoutModalElement) {
        logoutLink.addEventListener('click', function (e) {
            e.preventDefault();

            const logoutModal = new bootstrap.Modal(logoutModalElement, {
                backdrop: 'static',
                keyboard: false
            });

            logoutModal.show();

            setTimeout(() => {
                window.location.href = '../../logout.php';
            }, 2500);
        });
    }

    // Lessor Modal Close
    const lessorModal = document.getElementById('lessorModal');
    const searchLessorInput = document.getElementById('searchLessorInput');
    const lessorModalClose = document.querySelector('#lessorModal .file-modal-close');

    if (lessorModal && lessorModalClose && searchLessorInput) {
        lessorModalClose.addEventListener('click', function () {
            searchLessorInput.value = '';
            lessorModal.style.display = 'none';
        });
    }

    // jQuery DOM Ready
    $(function () {
        let currentPage = 1;

        $('#view_lessors').click(function () {
            fetchLessors('', 1);
            $('#lessorModal').show();
        });

        $('#searchLessorInput').on('input', function () {
            const searchTerm = $(this).val();
            fetchLessors(searchTerm, 1);
        });

        $('#lessorList').on('click', '.page-btn', function () {
            const page = $(this).data('page');
            const searchTerm = $('#searchLessorInput').val();
            fetchLessors(searchTerm, page);
        });

        $('.file-modal-close').click(function () {
            $('#lessorModal').hide();
        });

        function fetchLessors(searchTerm, page) {
            $.ajax({
                url: 'fetch_lessors.php',
                method: 'POST',
                data: {
                    region: '<?= $userRegion ?>',
                    area: '<?= $userArea ?>',
                    role: '<?= $userRole ?>',
                    searchTerm: searchTerm,
                    page: page
                },
                success: function (response) {
                    $('#lessorList').html(response);
                },
                error: function () {
                    alert('Failed to fetch lessor data. Please try again.');
                }
            });
        }
    });

    // Highlight row selection
    window.highlightRow = function (rowId) {
        document.querySelectorAll('.profile-row').forEach(row => {
            row.classList.remove('selected-row');
        });

        const selectedRow = document.getElementById('row_' + rowId);
        if (selectedRow) {
            selectedRow.classList.add('selected-row');
        }

        const selectedInput = document.getElementById('selected_row');
        if (selectedInput) {
            selectedInput.value = rowId;
        }
    };

    // Mobile number limitation
    window.limitMobileNumber = function (input) {
        input.value = input.value.replace(/[^\d]/g, '');
        if (input.value.length > 11) {
            input.value = input.value.slice(0, 11);
        }
    };

    // --- NEW: AUTO-SEARCH TRIGGER ---
    const searchInput = document.getElementById('search_lessor');
    if(searchInput) {
        searchInput.addEventListener('input', function() {
            const val = this.value;
            const datalistOptions = document.getElementById('lessor_list').options;
            
            // Loop through options and check if the current input matches any datalist option
            for (let i = 0; i < datalistOptions.length; i++) {
                if (datalistOptions[i].value === val) {
                    // Automatically click the search button
                    document.querySelector('button[name="search_btn"]').click();
                    break;
                }
            }
        });
    }
    // ---------------------------------
});
</script>

</body>
</html>