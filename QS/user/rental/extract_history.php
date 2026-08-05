<?php
session_start();
include '../../config/config.php';

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
}
// Get user info from session
$user_role = $_SESSION['user_role'] ?? '';
$user_zone = $_SESSION['mainzone'] ?? '';
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
            <title>ML Rental - Extraction History</title>
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
        /* Wrapper to center cards */
.cards-wrapper {
    width: 100%;
    max-width: 80%; /* card width */
    margin: 1rem auto; /* center horizontally with top/bottom spacing */
    padding: 0 1rem;
}

/* Modern extraction card */
.extraction-card {
    border-radius: 1rem;
    overflow: hidden;
    margin-bottom: 1rem; /* spacing between cards */
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08);
    background-color: #fff;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

/* Hover effect */
.extraction-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 2rem rgba(0,0,0,0.15);
}

/* Card header with gradient and icon */
.toggle-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 1.5rem;
    background: whitesmoke;
    color: #333;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
}

/* Badge inside header */
.toggle-card .badge {
    font-size: 0.8rem;
    background-color: rgba(255,255,255,0.3);
    color: #333;
    padding: 0.35rem 0.6rem;
    border-radius: 0.5rem;
}

/* Toggle icon rotation */
.toggle-icon {
    transition: transform 0.3s ease;
    color: #333;
}
.toggle-icon.rotate {
    transform: rotate(180deg);
}

/* Table styling */
.table-responsive {
    border-top: 1px solid #dee2e6;
}
.table thead {
    background-color: #f8f9fa;
    font-weight: 600;
}
.table tbody tr:hover {
    background-color: #f1f3f5;
}
.table tbody td {
    vertical-align: middle;
}

/* Amount text highlight */
.text-success {
    font-weight: 600;
    color: #28a745 !important;
}

/* Status badges with icons */
.status-paid {
    background-color: #28a745;
    color: #fff;
    font-size: 0.85rem;
    padding: 0.35rem 0.6rem;
    border-radius: 0.5rem;
}
.status-pending {
    background-color: #ffc107;
    color: #212529;
    font-size: 0.85rem;
    padding: 0.35rem 0.6rem;
    border-radius: 0.5rem;
}
.status-overdue {
    background-color: #dc3545;
    color: #fff;
    font-size: 0.85rem;
    padding: 0.35rem 0.6rem;
    border-radius: 0.5rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .toggle-card {
        flex-direction: column;
        align-items: flex-start;
    }
    .toggle-icon {
        margin-top: 0.5rem;
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

    <div class="cards-wrapper">
    <div class="text-center mb-3">
                    <h3 class="fw-bold text-danger">
                        <i class="bi bi-file-earmark-text me-2"></i> EXTRACTION HISTORY
                    </h3>
                </div>
    <?php
    // Base SQL
    $sql = "SELECT * FROM transactional WHERE extract_request_status = 'Extracted' ";

    // Filter based on user role
    if ($user_role === 'Vpo-Checker' && $user_zone) {
        $sql .= "AND mainzone = '".mysqli_real_escape_string($conn, $user_zone)."' ";
    }
    $sql .= "ORDER BY extraction_date ASC, transaction_date ASC";

    $result = mysqli_query($conn, $sql);

    // Group by month
    $monthGroups = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // If extraction_date is empty/null, group it under 'Unknown Month'
        $monthKey = !empty($row['extraction_date']) 
            ? date('Y-m', strtotime($row['extraction_date'])) 
            : 'Unknown Month';
            
        $monthGroups[$monthKey][] = $row;
    }

    $monthIndex = 0;
    foreach ($monthGroups as $month => $rowsInMonth):
        $formattedMonth = date('F Y', strtotime($month . '-01'));
        $monthCollapseId = "month{$monthIndex}";
    ?>

    <!-- Month Card -->
    <div class="card extraction-card mb-3">

        <!-- Month Header -->
        <div class="card-header bg-danger text-white fw-bold d-flex justify-content-between align-items-center toggle-month" 
             data-target="<?= $monthCollapseId ?>" style="cursor:pointer;">
            <div><i class="bi bi-calendar2-week me-2"></i> <?= $formattedMonth ?></div>
            <i class="bi bi-chevron-down toggle-icon fs-5 text-white"></i>
        </div>

        <!-- Collapsible Month Body (Extraction-Date Cards go here) -->
        <div id="<?= $monthCollapseId ?>" class="collapse">
            <div class="card-body p-2">

                <?php
                // Group by extraction date inside the month
                $dateGroups = [];
                foreach ($rowsInMonth as $row) {
                    // If extraction_date is empty/null, group it under 'Unknown Date'
                    $dateKey = !empty($row['extraction_date']) 
                        ? date('Y-m-d', strtotime($row['extraction_date'])) 
                        : 'Unknown Date';
                        
                    $dateGroups[$dateKey][] = $row;
                }

                $collapseIndex = 0;
                foreach ($dateGroups as $date => $rowsByDate):
                    $formattedDate = date('F d, Y', strtotime($date));
                    $collapseId = "month{$monthIndex}_date{$collapseIndex}";
                ?>
                
                <div class="card mb-2 shadow-sm">
                    <!-- Extraction Date Header -->
                    <div class="card-header toggle-card d-flex justify-content-between align-items-center" data-target="<?= $collapseId ?>" style="cursor:pointer;">
                        <div>
                            <i class="bi bi-box-seam me-2"></i> Extraction Date: <?= $formattedDate ?>
                            <span class="badge bg-dark-subtle text-dark ms-2"><?= count($rowsByDate) ?> transactions</span>
                        </div>
                        <i class="bi bi-chevron-down toggle-icon fs-5"></i>
                    </div>

                    <!-- Collapsible Body -->
                    <div id="<?= $collapseId ?>" class="collapse">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Lessor</th>
                                            <th>Authorized</th>
                                            <th>Amount</th>
                                            <th>Exported By</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rowsByDate as $row): ?>
                                        <tr>
                                            <td><?= date('M d, Y', strtotime($row['transaction_date'])) ?></td>
                                            <td><?= htmlspecialchars(trim(($row['l1_firstname'] ?? '') . ' ' . ($row['l1_middlename'] ?? '') . ' ' . ($row['l1_lastname'] ?? ''))) ?></td>
                                            <td><?= htmlspecialchars(trim(($row['authorize_firstName'] ?? '') . ' ' . ($row['authorize_middleName'] ?? '') . ' ' . ($row['authorize_lastName'] ?? ''))) ?></td>
                                            <td class="fw-semibold text-success">₱<?= number_format($row['amount'], 2) ?></td>
                                            <td><?= htmlspecialchars($row['exported_by'] ?? '') ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['status'] ?? '') ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>

                <?php $collapseIndex++; endforeach; ?>

            </div>
        </div>
    </div>

    <?php $monthIndex++; endforeach; ?>
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
    </div>
    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/sweetalert2/dist/sweetalert2.all.min.js"></script>
    <script>
         // Month card collapse toggle
    document.querySelectorAll('.toggle-month').forEach(btn => {
        const targetId = btn.getAttribute('data-target');
        const collapseEl = document.getElementById(targetId);
        const icon = btn.querySelector('.toggle-icon');

        btn.addEventListener('click', () => {
            const bsCollapse = new bootstrap.Collapse(collapseEl, { toggle: true });
            icon.classList.toggle('rotate');
        });
    });

    // Extraction-date card collapse toggle
    document.querySelectorAll('.toggle-card').forEach(btn => {
        const targetId = btn.getAttribute('data-target');
        const collapseEl = document.getElementById(targetId);
        const icon = btn.querySelector('.toggle-icon');

        btn.addEventListener('click', () => {
            const bsCollapse = new bootstrap.Collapse(collapseEl, { toggle: true });
            icon.classList.toggle('rotate');
        });
    });

    // Sidebar toggle
    document.getElementById('toggleSidebar')?.addEventListener('click', () => {
        document.getElementById('sidebarMenu')?.classList.toggle('collapsed');
    });
document.getElementById('logoutLink')?.addEventListener('click', e => {
    e.preventDefault();
    const modal = new bootstrap.Modal(document.getElementById('logoutModal'), {
        backdrop: 'static',
        keyboard: false
    });
    modal.show();
    setTimeout(() => window.location.href='../../logout.php', 2500);
});
    </script>
</body>
</html>
