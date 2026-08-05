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
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <link rel="shortcut icon" href="../../assets/images/rental_logo.png" type="image/x-icon">
    <meta name="description" content="">
    <title>ML Rental - Add Notarized COL</title>
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">
    <link href="../../assets/css/poppins.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar.css">
    <link rel="stylesheet" href="../../assets/css/scrollbar.css">
</head>
<body>
<?php include ('navbar.php'); ?>
<div id="mainContent">
    <!-- Sidebar Toggle -->
    <button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
        <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
        <span class="fw-normal">Menu</span>
    </button>
    <div class="container py-3">
    <?php
if (isset($_POST['notarized_btn'])) {

    $contractNumber = mysqli_real_escape_string($conn, $_POST['contractNumber']);

    if (!isset($_FILES['fileUpload']) || $_FILES['fileUpload']['error'] != 0) {
        echo "<script>alert('Please select a file to upload');</script>";
        exit;
    }

    $fileTmpPath = $_FILES['fileUpload']['tmp_name'];
    $fileName = $_FILES['fileUpload']['name'];
    $fileSize = $_FILES['fileUpload']['size'];

    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // Allow only PDF
    if ($fileExt !== 'pdf') {
        echo "<script>alert('Only PDF files are allowed.');</script>";
        exit;
    }

    // Limit size (example: 5MB)
    if ($fileSize > 5 * 1024 * 1024) {
        echo "<script>alert('File must be less than 5MB');</script>";
        exit;
    }

    $fileContent = file_get_contents($fileTmpPath);

    $stmt = $conn->prepare("
        UPDATE create_contract 
        SET notarized = 'Yes',
            contract_file16 = ?, 
            mimeType16 = 'application/pdf', 
            contractFilename16 = ?
        WHERE contract_number = ?
    ");

    $stmt->bind_param("bss", $null, $fileName, $contractNumber);

    $stmt->send_long_data(0, $fileContent);

    if ($stmt->execute()) {
        echo "<script>alert('File uploaded successfully!');</script>";
    } else {
        echo "<script>alert('Upload failed: ".$stmt->error."');</script>";
    }

    $stmt->close();
}
?>
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-header bg-danger text-white d-flex align-items-center">
                <i class="bi bi-file-earmark-text me-2"></i>
                <h6 class="mb-0 text-white">Notarized Contract Upload</h6>
            </div>
            <div class="card-body">
                <form action="" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    
                    <!-- Branch Selector -->
                    <div class="mb-3">
                        <label for="branch" class="form-label fw-semibold">
                            <i class="bi bi-building me-1 text-danger"></i> Select Branch
                        </label>
                        <input list="branchList" name="branch" id="branch" 
                               class="form-control shadow-sm" autocomplete="off" required 
                               onchange="updateKpxCode(this)">
                        <datalist id="branchList">
                            <?php
                                $user_email = mysqli_real_escape_string($conn, $_SESSION['user_email']);
                                $userQuery = "SELECT roles, mainzone, area, region FROM user_form WHERE username = '$user_email' OR email = '$user_email'";
                                $resultUser = mysqli_query($conn, $userQuery);
                                $user = mysqli_fetch_assoc($resultUser);
                                $userRole = $user['roles'];
                                $userMainzone = $user['mainzone'];
                                $userArea = $user['area'];
                                $userRegion = $user['region'];

                                $transactional = "
                                    SELECT DISTINCT 
                                    IFNULL(t.branch, c.branch) AS branch, 
                                    IFNULL(t.region, c.region) AS region, 
                                    IFNULL(t.area, c.area) AS area, 
                                    IFNULL(t.kpx_code, c.kpx_code) AS kpx_code, 
                                    IFNULL(t.branch_id, c.branch_id) AS branch_id
                                    FROM create_contract c
                                    LEFT JOIN transactional t ON t.branch_id = c.branch_id
                                    WHERE c.branch != '' AND c.request_status != 'Terminated'";

                                if ($userRole == 'HO') {
                                } else if ($userRole == 'Am-Creator') {
                                    $transactional .= " AND c.region = '$userRegion' AND c.area = '$userArea'";
                                } elseif ($userRole == 'Rm-Reviewer') {
                                    $transactional .= " AND c.region = '$userRegion'";
                                } else if ($userRole == 'Finance' || $userRole == 'Auditor') {
                                } else {
                                    $transactional .= " AND c.mainzone = '$userMainzone'";
                                }

                                $transactional .= " ORDER BY branch ASC";
                                $resultBranch = mysqli_query($conn, $transactional);

                                if ($resultBranch) {
                                    while ($rowBranch = mysqli_fetch_assoc($resultBranch)) {
                                        echo "<option value='" . $rowBranch['branch'] . "' 
                                            data-kpx-code='" . $rowBranch['kpx_code'] . "' 
                                            data-branch-id='" . $rowBranch['branch_id'] . "' 
                                            data-notarized='" . $rowBranch['notarized'] . "'>" . 
                                            $rowBranch['branch'] . " (" . $rowBranch['region'] . ", Area " . $rowBranch['area'] . ")" .
                                        "</option>";
                                    }
                                }
                            ?>
                        </datalist>
                        <label id="notarizedLabel" class="fw-bold mt-2" style="display:none;"></label>
                    </div>

                    <!-- Contract Selector -->
                    <div class="mb-3">
                        <label for="contractNumber" class="form-label fw-semibold">
                            <i class="bi bi-file-text me-1 text-danger"></i> Select Contract
                        </label>
                        <select name="contractNumber" id="contractNumber" class="form-select shadow-sm" required>
                            <option value="">-- Select Contract --</option>
                        </select>
                        <label id="notarize_lbl" class="fw-bold mt-2" style="display:none;"></label>
                    </div>

                    <!-- File Upload -->
                    <div class="mb-3">
                        <label for="fileUpload" class="form-label fw-semibold">
                            <i class="bi bi-upload me-1 text-danger"></i> Upload Notarized PDF
                        </label>
                        <input type="file" id="fileUpload" name="fileUpload" accept=".pdf" 
                               class="form-control shadow-sm" required>
                        <div id="filePreview" class="form-text text-muted mt-1">Only PDF files allowed.</div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" name="notarized_btn" id="notarized_btn" 
                            class="btn btn-danger px-4 shadow-sm d-flex align-items-center">
                        <i class="bi bi-cloud-arrow-up me-2"></i> Upload File
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const contractSelect = document.getElementById("contractNumber");
    const notarizeLabel = document.getElementById("notarize_lbl");

    contractSelect.addEventListener("input", function () {
        let selectedOption = this.options[this.selectedIndex];
        if (selectedOption) {
            let notarized = selectedOption.getAttribute("data-notarized");
            notarizeLabel.style.display = "none";
            if (notarized) {
                notarizeLabel.textContent = notarized === "Yes" ? "Notarized ✅" : "Not Notarized ❌";
                notarizeLabel.style.color = notarized === "Yes" ? "green" : "red";
                notarizeLabel.style.display = "block";
            }
        }
    });
});
</script>

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
        document.getElementById('fileUpload').addEventListener('change', function(event) {
            const filePreview = document.getElementById('filePreview');
            filePreview.innerHTML = '';
            const file = event.target.files[0];
            if (file) {
                const fileType = file.type;
                const validFileType = 'application/pdf';
                if (fileType === validFileType) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const arrayBuffer = e.target.result;
                        const blob = new Blob([arrayBuffer], { type: validFileType });
                        const url = URL.createObjectURL(blob);
                        const iframe = document.createElement('iframe');
                        iframe.src = url;
                        iframe.width = '100%';
                        iframe.height = '500px';
                        iframe.style.border = 'none';
                        filePreview.appendChild(iframe);
                        iframe.onerror = function() {
                            filePreview.innerHTML = '<p>PDF preview not available. <a href="' + url + '" download="' + file.name + '">Download PDF</a></p>';
                        };
                    };
                    reader.readAsArrayBuffer(file);
                } else {
                    event.target.value = '';
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid file type',
                        text: 'Please upload a PDF file only.'
                    });
                }
            }
        });
        function updateKpxCode(branchInput) {
            const selectedBranch = branchInput.value;
            fetch(`get_contracts_notarized.php?branch=${encodeURIComponent(selectedBranch)}`)
                .then(response => response.json())
                .then(data => {
                    const contractSelect = document.getElementById('contractNumber');
                    contractSelect.innerHTML = '';
                    const emptyOption = document.createElement('option');
                    emptyOption.value = '';
                    emptyOption.textContent = 'Select Contract';
                    contractSelect.appendChild(emptyOption);

                    if (data.contracts && data.contracts.length > 0) {
                        data.contracts.forEach(contract => {
                            const option = document.createElement('option');
                            option.value = contract.contract_number;
                            option.textContent = contract.contract_number;
                            option.setAttribute('data-notarized', contract.notarized); // Add this
                            contractSelect.appendChild(option);
                        });
                    } else {
                        const noContractOption = document.createElement('option');
                        noContractOption.value = '';
                        noContractOption.textContent = 'No contracts available';
                        contractSelect.appendChild(noContractOption);
                    }

                    const previousContractNumber = "<?php echo isset($_POST['contractNumber']) ? $_POST['contractNumber'] : ''; ?>";
                    if (previousContractNumber) {
                        const existingOption = Array.from(contractSelect.options).find(option => option.value === previousContractNumber);
                        if (existingOption) {
                            contractSelect.value = existingOption.value;
                        }
                    }

                    const selectedOption = branchInput.selectedOptions?.[0];
                    if (selectedOption) {
                        const kpxCode = selectedOption.dataset.kpxCode;
                        const branchId = selectedOption.dataset.branchId;
                        if (kpxCode && branchId) {
                            document.getElementById('kpxCode').value = kpxCode;
                            document.getElementById('branchId').value = branchId;
                        } else {
                            console.error('Missing kpxCode or branchId data attributes.');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching contracts:', error);
                });
        }

        </script>
    </body>
</html>
