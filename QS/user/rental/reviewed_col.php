<?php
session_start();
include '../../config/config.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Include the PhpSpreadsheet autoloader
require '../../vendor/autoload.php';

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
}
$userRole     = $_SESSION['user_role']   ?? '';
$userRegion   = $_SESSION['region']      ?? '';
$userArea     = $_SESSION['area']        ?? '';
$userMainzone = $_SESSION['mainzone']    ?? '';
$selectedRegion = $_POST['region'] ?? '';

$regionSql = "SELECT DISTINCT region FROM create_contract WHERE rfp_status = 'Reviewed' AND contract_number != 'VOID'";
$regionParams = [];
$regionTypes  = "";

// ROLE CONDITIONS
if ($userRole === 'Am-Creator') {
    $regionSql .= " AND region = ? AND area = ?";
    $regionTypes = "ss";
    $regionParams = [$userRegion, $userArea];

} elseif ($userRole === 'Rm-Reviewer') {
    $regionSql .= " AND region = ?";
    $regionTypes = "s";
    $regionParams = [$userRegion];

} elseif (in_array($userRole, ['Vpo-Reviewer', 'Vpo-Checker', 'Vpo-Approver'])) {
    $regionSql .= " AND mainzone = ?";
    $regionTypes = "s";
    $regionParams = [$userMainzone];

} elseif (in_array($userRole, ['Finance', 'Auditor', 'HO'])) {
    // NO FILTER – SEE ALL
}

$regionSql .= " ORDER BY region ASC";

$regionStmt = $conn->prepare($regionSql);

if (!empty($regionParams)) {
    $regionStmt->bind_param($regionTypes, ...$regionParams);
}

$regionStmt->execute();
$regionResult = $regionStmt->get_result();

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
            <title>ML Rental - Reviewed Contract By RM</title>
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
    <body>
    <?php include ('navbar.php'); ?>
    <div id="mainContent" class="bg-light min-vh-100">

    <!-- Top Bar -->
    <div class="d-flex align-items-center justify-content-between px-4 py-1 border-bottom bg-white shadow-sm">
        <button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
            <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
            <span class="fw-normal">Menu</span>
        </button>

        <h5 class="mb-0 fw-semibold text-secondary">Reviewed Contracts by RM</h5>
    </div>
    <!-- Filter Card -->
    <form action="" method="POST" id="ledger_form">
    <div class="container-fluid mt-4">
    <div class="card shadow-sm border-0 rounded-4">
    <div class="card-body">

        <!-- Top-right button -->
        <div class="d-flex justify-content-end mb-1">
            <button type="submit"
                    formaction="export_reviewed_contracts.php"
                    class="btn btn-success">
                Export to Excel
            </button>
            <input type="hidden"
           name="selectedRegion"
           value="<?= htmlspecialchars($_POST['region'] ?? '') ?>">
        </div>

        <div class="row align-items-end g-3">

            <div class="col-md-4">
                <label class="form-label fw-semibold text-muted">
                    Filter by Region
                </label>
                <select name="region"
                        class="form-select rounded-3"
                        onchange="this.form.submit()">
                    <option value="">All Regions</option>

                    <?php while ($row = $regionResult->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($row['region']) ?>"
                            <?= $selectedRegion === $row['region'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($row['region']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-8 text-end">
                <?php if (!empty($selectedRegion)): ?>
                    <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill">
                        Showing results for:
                        <strong><?= htmlspecialchars($selectedRegion) ?></strong>
                    </span>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>

    </div>
</form>
<?php
    $sql = "
    SELECT mainzone, region, contract_number, branch_id, branch, area
    FROM create_contract
    WHERE rfp_status = 'Reviewed'
";

$params = [];
$types  = "";

// ROLE CONDITIONS
if ($userRole === 'Am-Creator') {
    $sql .= " AND region = ? AND area = ?";
    $types .= "ss";
    $params[] = $userRegion;
    $params[] = $userArea;

} elseif ($userRole === 'Rm-Reviewer') {
    $sql .= " AND region = ?";
    $types .= "s";
    $params[] = $userRegion;

} elseif (in_array($userRole, ['Vpo-Reviewer', 'Vpo-Checker', 'Vpo-Approver'])) {
    $sql .= " AND mainzone = ?";
    $types .= "s";
    $params[] = $userMainzone;

} elseif (in_array($userRole, ['Finance', 'Auditor', 'HO'])) {
    // SEE ALL
}

// REGION FILTER FROM DROPDOWN (APPLIES TO ALL ROLES)
if (!empty($selectedRegion)) {
    $sql .= " AND region = ?";
    $types .= "s";
    $params[] = $selectedRegion;
}

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

?>
    <!-- Results Table -->
    <div class="container-fluid mt-4">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="text-muted">
                                <th>Main Zone</th>
                                <th>Region</th>
                                <th>Contract Number</th>
                                <th>Branch ID</th>
                                <th>Branch</th>
                                <th>Area</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['mainzone']) ?></td>
                                        <td><?= htmlspecialchars($row['region']) ?></td>
                                        <td><?= htmlspecialchars($row['contract_number']) ?></td>
                                        <td><?= htmlspecialchars($row['branch_id']) ?></td>
                                        <td><?= htmlspecialchars($row['branch']) ?></td>
                                        <td><?= htmlspecialchars($row['area']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No records found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
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
