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
        <title>ML Rental - For Review Contract</title>
        <link rel="stylesheet" href="../../css/sidebar.css?v=<?php echo time(); ?>"> 
        <link href="../../assets/css/poppins.css" rel="stylesheet">
        <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
        <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">
        <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../../assets/css/sidebar.css">
        <link rel="stylesheet" href="../../assets/css/scrollbar.css">
        <style>
            .badge-light-orange {
                background-color: #ffcc80; /* light orange */
                color: #663c00; /* dark brown text for readability */
            }
            .btn-danger {
                background-color: #d70c0c !important;
                border: none;
            }
            .btn-danger:hover {
                background-color: #b50909 !important;
            }
            .form-control:focus {
                box-shadow: 0 0 0 0.2rem rgba(215, 12, 12, 0.25);
                border-color: #d70c0c;
            }    
            .btn-outline-view {
                border: 1px solid #0d6efd; /* Bootstrap Primary Blue */
                color: #0d6efd;
                background-color: rgba(13, 110, 253, 0.1);
                transition: all 0.3s;
            }

            .btn-outline-view:hover {
                background-color: rgba(13, 110, 253, 0.1);
            } 
        </style>
    </head>
    <body>
        <?php include ('navbar.php'); ?>
        <div id="mainContent" class="bg-light min-vh-100">
        <button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
            <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
            <span class="fw-normal">Menu</span>
        </button>

  <!-- Main Container -->
  <div class="container pb-5">
      <div class="card shadow border-0 rounded-4">
          <!-- Header with Title + Search -->
          <div class="card-header bg-white border-0 rounded-top-4 px-4 py-3 d-flex justify-content-between align-items-center">
              <h5 class="mb-0 fw-bold text-danger d-flex align-items-center">
                  <i class="bi bi-clock-history me-2"></i> Returned Contracts (DATA ARCHIVING)
              </h5>
              <div class="d-flex gap-2">
                    <input type="text" id="searchInput" class="form-control rounded-pill px-3" placeholder="Search branch or contract #">
                    <button class="btn btn-danger rounded-pill px-1" style="width:140px;">
                        <i class="bi bi-funnel me-1"></i> Filter
                    </button>

              </div>
          </div>

          <!-- Card Body -->
          <div class="card-body px-4 py-0">
              <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                      <thead class="table-danger text-dark rounded-3">
                          <tr>
                            <th class="fw-normal">
                                <i class="bi bi-building text-danger me-1"></i> Branch
                            </th>
                            <th class="fw-normal">
                                <i class="bi bi-file-earmark-pdf text-danger me-1"></i> Contract #
                            </th>
                            <th class="fw-normal">
                                <i class="bi bi-calendar-check text-danger me-1"></i> Effectivity Date
                            </th>
                            <th class="fw-normal">
                                <i class="bi bi-calendar-x text-danger me-1"></i> Expiry Date
                            </th>
                            <th class="fw-normal">
                                <i class="bi bi-calendar-date me-1 text-danger"></i>Payment Due Date
                            </th>
                            <th class="fw-normal">
                                <i class="bi bi-person-badge text-danger me-1"></i> Lessor
                            </th>
                            <th class="text-center fw-normal">
                                <i class="bi bi-gear text-danger me-1"></i> Action
                            </th>
                            <th class="fw-normal"><i class="bi bi-person me-1 text-danger"></i>RFP Requested By</th>
                            <th class="fw-normal"><i class="bi bi-calendar-date me-1 text-danger"></i>RFP Requested Date</th>
                            <th class="fw-normal text-center">
                                <i class="bi bi-geo-alt-fill text-danger me-1"></i>Location
                            </th>
                          </tr>
                      </thead>
                      <tbody id="transactionTable" class="table-group-divider">
                      <?php
                        // Example: Assuming you store role and location info in session
                        $userRole   = $_SESSION['user_role'] ?? '';
                        $userZone   = $_SESSION['mainzone'] ?? '';
                        $userRegion = $_SESSION['region'] ?? '';
                        $userArea   = $_SESSION['area'] ?? '';

                        // Base query
                        $query = "
                            SELECT id, branch, contract_number, l1_firstname, l1_lastname, request_status, 
                                contract_start, contract_end, payment_due_date, mainzone, region, area
                            FROM create_contract
                            WHERE (rfp_status IS NULL OR rfp_status = '') 
                            AND request_status = 'Created'
                            AND (reviewer_note IS NOT NULL OR audit_note IS NOT NULL) 
                        ";

                        // Add role-based filters
                        if ($userRole === 'Am-Creator') {
                            $query .= " AND mainzone = '" . mysqli_real_escape_string($conn, $userZone) . "' 
                                        AND region = '" . mysqli_real_escape_string($conn, $userRegion) . "' 
                                        AND area = '" . mysqli_real_escape_string($conn, $userArea) . "'";
                        } elseif ($userRole === 'Rm-Reviewer') {
                            $query .= " AND region = '" . mysqli_real_escape_string($conn, $userRegion) . "'";
                        } elseif (in_array($userRole, ['Vpo-Checker', 'Vpo-Reviewer', 'Vpo-Approver'])) {
                            $query .= " AND mainzone = '" . mysqli_real_escape_string($conn, $userZone) . "'";
                        }

                        $result = mysqli_query($conn, $query);

                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) {
                                $lessorName = trim(($row['l1_firstname'] ?? '') . ' ' . ($row['l1_lastname'] ?? ''));
                                
                                // Format contract start and end dates
                                $contractStart = !empty($row['contract_start']) ? date('F j, Y', strtotime($row['contract_start'])) : '-';
                                $contractEnd   = !empty($row['contract_end']) ? date('F j, Y', strtotime($row['contract_end'])) : '-';

                                // Format payment due date with suffix
                                if (!empty($row['payment_due_date']) && $row['payment_due_date'] !== '0000-00-00') {
                                    $day = (int)date('j', strtotime($row['payment_due_date']));
                                    $suffix = 'th';
                                    if (!in_array($day % 100, [11, 12, 13])) {
                                        switch ($day % 10) {
                                            case 1: $suffix = 'st'; break;
                                            case 2: $suffix = 'nd'; break;
                                            case 3: $suffix = 'rd'; break;
                                        }
                                    }
                                    $paymentDue = "Every {$day}{$suffix} day of the month";
                                } else {
                                    $paymentDue = '—';
                                }
                                // 1. Prepare the values outside the echo
                                $preparedBy = !empty($row['prepared_by']) ? htmlspecialchars($row['prepared_by']) : '---';
                                $rfpFormattedDate = !empty($row['rfp_date']) ? date('F d, Y', strtotime($row['rfp_date'])) : '---';
                                
                                // 2. Now output the row
                                echo "
                                <tr class='align-middle'>
                                    <td class='text-dark px-3'>{$row['branch']}</td>
                                    <td class='text-dark'>{$row['contract_number']}</td>
                                    <td class='text-dark'>{$contractStart}</td>
                                    <td class='text-dark'>{$contractEnd}</td>
                                    <td class='text-dark'>{$paymentDue}</td>
                                    <td class='text-dark'>{$lessorName}</td>
                                    <td class='text-center'>
                                        <button class='btn btn-sm btn-outline-view rounded-pill px-3 viewBtn d-inline-flex align-items-center justify-content-center' 
                                                data-id='{$row['id']}'>
                                            <i class='bi bi-eye me-1 fs-6'></i>
                                        </button>
                                    </td>
                                    <td>{$preparedBy}</td>
                                    <td>
                                        <i class='bi bi-calendar-event me-1 text-danger'></i>
                                        {$rfpFormattedDate}
                                    </td>
                                    <td class='text-center'>
                                        <span class='fw-normal text-dark text-danger fw-semibold userLocation'>Area Manager</span>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr>
                                    <td colspan='8' class='text-center text-muted py-5'>
                                        <i class='bi bi-inbox fs-1 d-block mb-2 text-secondary'></i>
                                        <p class='mb-0 fw-semibold'>No pending transactions</p>
                                        <small class='text-muted'>New transactions will appear here once the RM or VPO return the contracts</small>
                                    </td>
                                </tr>";
                        }
                        ?>
                      </tbody>
                  </table>
              </div>
          </div>
      </div>
  </div>
</div>
      <!-- Contract Details Modal -->
    <div class="modal fade" id="contractModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title text-white fw-semibold">Contract Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contractDetails">
                <p class="text-muted">Loading...</p>
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
            document.getElementById('searchInput').addEventListener('keyup', function() {
                let filter = this.value.toLowerCase();
                let rows = document.querySelectorAll('#transactionTable tr');
                rows.forEach(row => {
                    let text = row.innerText.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            });

            document.addEventListener("DOMContentLoaded", () => {
                const viewBtns = document.querySelectorAll(".viewBtn");

                viewBtns.forEach(btn => {
                    btn.addEventListener("click", () => {
                    const id = btn.getAttribute("data-id");

                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById("contractModal"));
                    modal.show();

                    // Load details with your fetch file
                    document.getElementById("contractDetails").innerHTML = "<p class='text-muted'>Loading...</p>";

                    fetch("fetch_contract_details.php?id=" + id)
                        .then(res => res.text())
                        .then(data => {
                        document.getElementById("contractDetails").innerHTML = data;
                        })
                        .catch(() => {
                        document.getElementById("contractDetails").innerHTML = "<div class='alert alert-danger'>Failed to load details.</div>";
                        });
                    });
                });
            });
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
    </body>
</html>
