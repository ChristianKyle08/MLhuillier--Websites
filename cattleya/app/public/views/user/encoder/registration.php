<?php
require_once __DIR__ . '/../../../../config/database.php';
require __DIR__ . '/../../includes/session_check.php';

// Login guard
if (!isset($_SESSION['user_id'])) {
    header("Location: /cattleya/login");
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// 1. INITIAL FETCH & EDIT LOGIC
$isEdit = false;
$editData = [
    'id' => '', 'custom_id' => '', 'firstname' => '', 'middlename' => '', 'lastname' => '', 
    'gender' => '', 'address' => '', 'contact_number' => '', 'email_address' => '',
    'broker_id' => '', 'um_id' => '', 'role_type' => 'broker'
];

if (isset($_GET['id']) && isset($_GET['role'])) {
    $id = $_GET['id'];
    $role = $_GET['role'];
    $roleMap = [
        'broker'       => ['table' => 'brokers',       'id_col' => 'broker_id'],
        'unit_manager' => ['table' => 'unit_managers', 'id_col' => 'um_id'],
        'agent'        => ['table' => 'agents',        'id_col' => 'agent_id']
    ];

    if (array_key_exists($role, $roleMap)) {
        $isEdit = true;
        $target = $roleMap[$role];
        $stmt = $pdo->prepare("SELECT * FROM {$target['table']} WHERE id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            $editData = array_merge($editData, $result);
            $editData['role_type'] = $role;
            $editData['custom_id'] = $result[$target['id_col']] ?? '';
        }
    }
}

// 2. DATA FETCHING FOR DROPDOWNS
$brokers = $pdo->query("SELECT id, broker_id, firstname, lastname FROM brokers ORDER BY lastname ASC")->fetchAll(PDO::FETCH_ASSOC);
$unit_managers = $pdo->query("SELECT id, um_id, broker_id, firstname, lastname FROM unit_managers ORDER BY lastname ASC")->fetchAll(PDO::FETCH_ASSOC);

// 3. DIRECTORY QUERY (Defines $allPersonnel)
$directorySql = "
    SELECT b.id, b.broker_id as display_id, b.firstname, b.lastname, b.email_address, b.contact_number, 'broker' as role 
    FROM brokers b
    UNION
    SELECT um.id, um.um_id as display_id, um.firstname, um.lastname, um.email_address, um.contact_number, 'unit_manager' as role 
    FROM unit_managers um
    UNION
    SELECT a.id, a.agent_id as display_id, a.firstname, a.lastname, a.email_address, a.contact_number, 'agent' as role 
    FROM agents a
    ORDER BY lastname ASC";
$allPersonnel = $pdo->query($directorySql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personnel | Cattleya Real Estate</title>
    <link rel="icon" href="../../../assets/icons/favicon/cattleya_favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
    :root { 
        /* Updated brand colors to Cattleya Navy and Green */
        --brand: #2a6279; 
        --brand-accent: #96c93d;
        --brand-light: rgba(42, 98, 121, 0.08); 
        --text-main: #1A1A1A; 
        --text-muted: #64748B; 
        --border: #E2E8F0; 
        --surface: #FFFFFF;
        --sidebar-width: 280px; 
    }

    body { 
        font-family: 'Plus Jakarta Sans', sans-serif; 
        background-color: #F8F9FC; 
        color: var(--text-main); 
        margin: 0;
    }

    /* FIX SIDEBAR OVERLAP */
    .main-layout {
        margin-left: 10px;
        min-height: 100vh;
        transition: all 0.3s ease;
    }

    .content-container { 
        padding: 2.5rem; 
        max-width: 1600px;
        margin: 0 auto;
    }

    /* MODERN CARDS */
    .white-card { 
        background: var(--surface); 
        border: 1px solid var(--border); 
        border-radius: 24px; 
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        height: 100%;
    }

    /* TYPOGRAPHY */
    .section-title { font-weight: 800; font-size: 1.25rem; letter-spacing: -0.02em; }
    .input-group-label { 
        font-size: 0.7rem; 
        font-weight: 800; 
        text-transform: uppercase; 
        color: var(--brand); 
        letter-spacing: 0.05em;
        margin-bottom: 0.8rem; 
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .input-group-label::after { content: ""; flex: 1; height: 1px; background: var(--border); }

    /* INPUTS */
    .custom-input { 
        border: 1.5px solid var(--border); 
        border-radius: 12px; 
        padding: 0.8rem 1rem; 
        font-size: 0.9rem; 
        width: 100%; 
        background: #FDFDFD; 
        margin-bottom: 1.2rem;
        transition: all 0.2s;
    }
    .custom-input:focus { 
        border-color: var(--brand); 
        outline: none; 
        /* Updated RGBA to Cattleya Navy */
        box-shadow: 0 0 0 4px rgba(42, 98, 121, 0.1);
        background: #fff;
    }

    /* ROLE SWITCHER */
    .role-switcher { 
        display: flex; 
        background: #F1F5F9; 
        padding: 6px; 
        border-radius: 16px; 
        margin-bottom: 2rem; 
    }
    .role-btn { 
        flex: 1; border: none; background: transparent; 
        padding: 12px; font-size: 0.85rem; font-weight: 700; 
        color: var(--text-muted); border-radius: 12px; 
        transition: 0.3s;
    }
    .role-btn.active { background: white; color: var(--brand); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }

    /* BUTTONS */
    .btn-brand { 
        background: var(--brand); color: white; border: none; 
        padding: 1.1rem; border-radius: 16px; font-weight: 700; 
        width: 100%; transition: all 0.3s;
        box-shadow: 0 8px 20px rgba(42, 98, 121, 0.2);
    }
    .btn-brand:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(42, 98, 121, 0.3); color: #fff; background: #1e4b5d; }

    .btn-reset { 
        background: var(--brand-light); color: var(--brand); 
        border: none; padding: 0.6rem 1.2rem; border-radius: 12px; 
        font-weight: 700; font-size: 0.75rem; text-decoration: none; 
    }

    /* DATA TABLE STYLING */
    #personnelTable thead th { 
        background: #F8F9FC; border-bottom: 2px solid var(--border);
        text-transform: uppercase; font-size: 0.7rem; font-weight: 800; color: var(--text-muted);
        padding: 1.2rem 1rem;
    }
    #personnelTable tbody td { padding: 1.2rem 1rem; border-bottom: 1px solid #F1F5F9; }

    /* ROLE BADGES */
    .badge-role {
        padding: 6px 12px; font-weight: 700; font-size: 0.65rem; border-radius: 8px; border: 1px solid transparent;
    }
    .role-broker { background: #E0F2FE; color: #0369A1; border-color: #BAE6FD; }
    .role-unit_manager { background: #F0FDF4; color: #15803D; border-color: #DCFCE7; }
    .role-agent { background: #FEF3C7; color: #92400E; border-color: #FDE68A; }

    @media (max-width: 992px) {
        .main-layout { margin-left: 0; }
    }
    /* Custom SweetAlert2 Styling */
    .swal2-popup {
        border-radius: 24px !important;
        padding: 2rem !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        box-shadow: 0 20px 60px rgba(0,0,0,0.1) !important;
    }

    .swal2-title {
        font-weight: 800 !important;
        color: var(--text-main) !important;
        letter-spacing: -0.02em !important;
    }

    .swal2-html-container {
        color: var(--text-muted) !important;
        font-weight: 500 !important;
        line-height: 1.6 !important;
    }

    .swal2-styled.swal2-confirm {
        background-color: var(--brand) !important;
        border-radius: 14px !important;
        padding: 0.8rem 2rem !important;
        font-weight: 700 !important;
        font-size: 0.9rem !important;
        box-shadow: 0 8px 15px rgba(42, 98, 121, 0.2) !important;
    }

    .swal2-icon {
        border-width: 3px !important;
    }

    /* Success icon color fix */
    .swal2-icon.swal2-success {
        border-color: #96c93d !important;
        color: #96c93d !important;
    }
    .swal2-icon.swal2-success [class^='swal2-success-line'] {
        background-color: #96c93d !important;
    }
    .swal2-icon.swal2-success .swal2-success-ring {
        border: .25em solid rgba(150, 201, 61, .3) !important;
    }
  /* Table row hover elevation */
  #personnelCount {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    min-width: 65px;
    text-align: center;
}

/* Subtle pulse animation updated to Cattleya Green */
.search-active {
    box-shadow: 0 0 0 0 rgba(150, 201, 61, 0.4);
    animation: pulse-green 2s infinite;
}

@keyframes pulse-green {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(150, 201, 61, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(150, 201, 61, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(150, 201, 61, 0); }
}
.personnel-row {
    transition: all 0.2s ease-in-out;
}

.personnel-row:hover {
    background-color: #f7faf3 !important;
    transform: scale(1.002);
    box-shadow: inset 4px 0 0 var(--brand-accent);
}

/* Modernizing the search input focus shadow */
#personnelSearch:focus {
    background: #ffffff !important;
    box-shadow: 0 10px 25px -5px rgba(42, 98, 121, 0.15) !important;
    transform: translateY(-1px);
}

/* Action button hover effect updated to Cattleya Navy */
.btn-icon-modern:hover {
    background: var(--brand) !important;
    transform: translateY(-2px);
}

.btn-icon-modern:hover i {
    color: white !important;
}

/* Softening the Role Badges on hover - using Green for focus */
.badge-role {
    border: 1px solid transparent;
    transition: all 0.3s ease;
}

.personnel-row:hover .badge-role {
    background: var(--brand-accent) !important;
    color: white !important;
}

/* Scrollbar styling */
.table-responsive::-webkit-scrollbar {
    height: 6px;
}
.table-responsive::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
#noResults {
    animation: fadeIn 0.4s ease-out;
}

#personnelTable thead {
    transition: opacity 0.3s ease;
}

/* Modern Focus Ring for Search updated to brand primary */
#personnelSearch:focus {
    border: 1px solid var(--brand) !important;
    background-color: #fff !important;
    box-shadow: 0 0 0 4px rgba(42, 98, 121, 0.1) !important;
}
</style>
</head>
<body>

<?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>
<div class="main-layout">
    <div class="content-container">
        <div class="row g-4">
        <div class="col-xl-4">
                <div class="white-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="section-title m-0" style="color: #2a6279;"><?= $isEdit ? 'Edit Personnel' : 'Quick Registration' ?></h6>
                        <?php if ($isEdit): ?>
                            <a href="?" class="btn-reset" style="background: rgba(42, 98, 121, 0.1); color: #2a6279;"><i class="bi bi-plus-lg me-1"></i> NEW</a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!$isEdit): ?>
                    <div class="role-switcher" style="background: #f1f5f9; padding: 6px; border-radius: 16px; display: flex;">
                        <button type="button" class="role-btn active" data-role="broker">Broker</button>
                        <button type="button" class="role-btn" data-role="unit_manager">UM</button>
                        <button type="button" class="role-btn" data-role="agent">Agent</button>
                    </div>
                    <?php endif; ?>

                    <form action="../encoder/fetch/process-registration" method="POST">
                        <input type="hidden" name="id" value="<?= $editData['id'] ?>" autocomplete="off">
                        <input type="hidden" name="role_type" id="role_type" value="<?= $editData['role_type'] ?>" autocomplete="off">

                        <div class="input-group-label" style="color: #2a6279;">Organizational Info</div>
                            <input type="text" class="custom-input" name="custom_id" id="idInput" 
                                value="<?= htmlspecialchars($editData['custom_id']) ?>" 
                                placeholder="Broker ID (e.g. BRK-000001)" required autocomplete="off">

                        <div id="hierarchyBox" class="<?= ($editData['role_type'] === 'broker') ? 'd-none' : '' ?>">
                            
                            <div id="brokerSelectContainer" class="mb-2">
                                <label class="input-group-label d-flex justify-content-between align-items-center" style="color: #2a6279;">
                                    Broker Assignment
                                    <span id="badge_broker" class="badge bg-light text-dark border" style="font-size: 0.65rem; border-color: #2a6279 !important;">ID: ---</span>
                                </label>
                                <select class="custom-input form-select" name="broker_id_val" id="broker_id_val" autocomplete="off">
                                    <option value="" disabled <?= !$isEdit ? 'selected' : '' ?>>Select Broker...</option>
                                    <?php foreach($brokers as $b): ?>
                                        <option value="<?= $b['id'] ?>" 
                                                data-code="<?= htmlspecialchars($b['broker_id']) ?>" 
                                                <?php 
                                                    if ($editData['broker_id'] == $b['id'] || $editData['broker_id'] == $b['broker_id']) echo 'selected'; 
                                                ?>>
                                            <?= htmlspecialchars($b['lastname'] . ', ' . $b['firstname']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div id="umSelectContainer" class="mb-2 <?= ($editData['role_type'] !== 'agent') ? 'd-none' : '' ?>">
                                <label class="input-group-label d-flex justify-content-between align-items-center" style="color: #2a6279;">
                                    Unit Manager Assignment
                                    <span id="badge_um" class="badge bg-light text-dark border" style="font-size: 0.65rem; border-color: #96c93d !important;">ID: ---</span>
                                </label>
                                <select class="custom-input form-select" name="um_id_val" id="um_id_val" autocomplete="off">
                                    <option value="" disabled <?= !$isEdit ? 'selected' : '' ?>>Select Manager...</option>
                                    <?php foreach($unit_managers as $um): ?>
                                        <option value="<?= $um['id'] ?>" 
                                                data-code="<?= htmlspecialchars($um['um_id']) ?>"
                                                data-parent-broker="<?= $um['broker_id'] ?>"
                                                <?php 
                                                    if ($editData['um_id'] == $um['id'] || $editData['um_id'] == $um['um_id']) echo 'selected'; 
                                                ?>>
                                            <?= htmlspecialchars($um['lastname'] . ', ' . $um['firstname']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="input-group-label" style="color: #2a6279;">Personal Identity</div>
                        <div class="row g-2">
                            <div class="col-8">
                                <input type="text" class="custom-input" name="firstname" value="<?= htmlspecialchars($editData['firstname']) ?>" placeholder="First Name" required autocomplete="off">
                            </div>
                            <div class="col-4">
                                <select class="custom-input form-select" name="gender" required>
                                    <option value="" disabled <?= empty($editData['gender']) ? 'selected' : '' ?>>Sex</option>
                                    <option value="Male" <?= $editData['gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $editData['gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                        </div>
                        <input type="text" class="custom-input" name="middlename" value="<?= htmlspecialchars($editData['middlename']) ?>" placeholder="Middle Name" autocomplete="off">
                        <input type="text" class="custom-input" name="lastname" value="<?= htmlspecialchars($editData['lastname']) ?>" placeholder="Last Name" required autocomplete="off">
                        
                        <div class="input-group-label" style="color: #2a6279;">Contact Details</div>
                        <textarea class="custom-input" name="address" rows="2" placeholder="Complete Address" required autocomplete="off"><?= htmlspecialchars($editData['address']) ?></textarea>
                        <input type="email" class="custom-input" name="email_address" value="<?= htmlspecialchars($editData['email_address']) ?>" placeholder="Email Address" required autocomplete="off">
                        <input type="tel" class="custom-input" name="contact_number" value="<?= htmlspecialchars($editData['contact_number']) ?>" placeholder="Contact Number" required autocomplete="off">

                        <button type="submit" class="btn-brand mt-2" style="background: #2a6279; color: white; border-radius: 16px;"><?= $isEdit ? 'Save Profile Changes' : 'Register New Member' ?></button>
                    </form>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="white-card shadow-sm border-0" style="border-radius: 20px; overflow: hidden; background: #ffffff;">
                    <div class="card-header border-0 py-4 px-4" style="background: linear-gradient(to right, #ffffff, #fafafa);">
                        <div class="row align-items-center g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-2">
                                    <h6 class="mb-0" style="color: #2a6279; font-weight: 800; font-size: 1.1rem; letter-spacing: -0.02em;">
                                        Personnel Directory
                                    </h6>
                                    <span id="personnelCount" class="badge rounded-pill px-2 py-1" 
                                        style="background: rgba(42, 98, 121, 0.1); color: #2a6279; font-size: 0.7rem; border: 1px solid rgba(42, 98, 121, 0.1);">
                                        <?= count($allPersonnel) ?> Total
                                    </span>
                                </div>
                                <p class="text-muted small mb-0">Manage and view all registered personnel members</p>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="search-wrapper" style="position: relative;">
                                    <i class="bi bi-search" style="position: absolute; left: 18px; top: 50%; transform: translateY(-50%); color: #2a6279; opacity: 0.6;"></i>
                                    <input type="text" id="personnelSearch" class="form-control border-0 shadow-sm ps-5" 
                                        placeholder="Type to search personnel..." 
                                        style="background: #f8f9fa; border-radius: 15px; height: 48px; font-size: 0.9rem; padding-left: 50px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="personnelTable" class="table align-middle table-borderless px-3">
                            <thead>
                                <tr style="border-bottom: 2px solid #f8f9fa;">
                                    <th class="text-uppercase small fw-bold text-secondary ps-4" style="font-size: 0.65rem; letter-spacing: 0.1em;">ID Reference</th>
                                    <th class="text-uppercase small fw-bold text-secondary" style="font-size: 0.65rem; letter-spacing: 0.1em;">Member Details</th>
                                    <th class="text-uppercase small fw-bold text-secondary text-center" style="font-size: 0.65rem; letter-spacing: 0.1em;">Role Authority</th>
                                    <th class="text-uppercase small fw-bold text-secondary" style="font-size: 0.65rem; letter-spacing: 0.1em;">Contact Info</th>
                                    <th class="text-uppercase small fw-bold text-secondary text-end pe-4" style="font-size: 0.65rem; letter-spacing: 0.1em;">Options</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($allPersonnel as $row): ?>
                                <tr class="personnel-row transition-all" style="border-bottom: 1px solid #fcfcfc;">
                                    <td class="ps-4">
                                        <div class="d-inline-block px-2 py-1 bg-light text-secondary border-0 small fw-bold" style="border-radius: 6px; font-size: 0.75rem;">
                                            #<?= $row['display_id'] ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-box me-3 d-flex align-items-center justify-content-center shadow-sm" 
                                                style="width: 42px; height: 42px; background: linear-gradient(135deg, #2a6279, #96c93d); border-radius: 12px; color: white; font-size: 0.85rem; font-weight: 600;">
                                                <?= substr($row['firstname'], 0, 1) . substr($row['lastname'], 0, 1) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 0.95rem; line-height: 1.2;"><?= htmlspecialchars($row['lastname'] . ', ' . $row['firstname']) ?></div>
                                                <div class="text-muted" style="font-size: 0.75rem;"><?= $row['email_address'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge-role role-<?= $row['role'] ?> px-3 py-2 fw-bold" 
                                            style="border-radius: 10px; font-size: 0.65rem; background: rgba(42, 98, 121, 0.08); color: #2a6279;">
                                            <?= strtoupper(str_replace('_', ' ', $row['role'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-dark small mb-0"><i class="bi bi-phone-fill me-1 opacity-50" style="color: #96c93d;"></i><?= $row['contact_number'] ?></span>
                                        </div>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="dropdown">
                                            <a href="?id=<?= $row['id'] ?>&role=<?= $row['role'] ?>" 
                                            class="btn btn-icon-modern" 
                                            style="width: 38px; height: 38px; border-radius: 12px; background: #f8f9fa; display: inline-flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                                                <i class="bi bi-pencil-fill" style="color: #2a6279; font-size: 0.9rem;"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div id="noResults" class="text-center py-5" style="display: none;">
                            <div class="mb-3">
                                <i class="bi bi-person-x" style="font-size: 3rem; color: #e2e8f0;"></i>
                            </div>
                            <h6 class="fw-bold" style="color: #2a6279;">No members found</h6>
                            <p class="text-muted small">Try adjusting your search keywords or filters.</p>
                            <button type="button" class="btn btn-sm btn-light border-0 px-3" 
                                    onclick="clearSearch()" style="border-radius: 8px; color: #2a6279;">
                                Clear Search
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // 1. REFLECTION LOGIC: Updates the badges next to the labels
    function reflectSelectedIDs() {
        const brokerOption = $('#broker_id_val').find(':selected');
        const umOption = $('#um_id_val').find(':selected');

        const bCode = brokerOption.data('code');
        const uCode = umOption.data('code');

        $('#badge_broker').text(bCode ? 'ID: ' + bCode : 'ID: ---');
        $('#badge_um').text(uCode ? 'ID: ' + uCode : 'ID: ---');
    }

    // 2. FORM VISIBILITY LOGIC
    function updateFormVisibility(role) {
        const idInput = $('#idInput');
        const hierarchyBox = $('#hierarchyBox');
        const umContainer = $('#umSelectContainer');

        if (role === 'broker') {
            idInput.attr('placeholder', 'Broker ID (e.g. BRK-001)');
            hierarchyBox.addClass('d-none');
        } else if (role === 'unit_manager') {
            idInput.attr('placeholder', 'UM ID (e.g. UM-001)');
            hierarchyBox.removeClass('d-none');
            umContainer.addClass('d-none');
        } else if (role === 'agent') {
            idInput.attr('placeholder', 'Agent ID (e.g. AGT-001)');
            hierarchyBox.removeClass('d-none');
            umContainer.removeClass('d-none');
        }
        reflectSelectedIDs();
    }

    // Event: Role Switcher
    $('.role-btn').click(function() {
        $('.role-btn').removeClass('active');
        $(this).addClass('active');
        const role = $(this).data('role');
        $('#role_type').val(role);
        updateFormVisibility(role);
    });

    // Event: Dropdown Changes
    $('#broker_id_val, #um_id_val').on('change', function() {
        reflectSelectedIDs();
    });

    // Auto-Select Broker if UM is chosen (Optional Quality of Life)
    $('#um_id_val').on('change', function() {
        const parentBroker = $(this).find(':selected').data('parent-broker');
        if (parentBroker) {
            // Check if we should auto-select based on PK or code
            $('#broker_id_val option').each(function() {
                if ($(this).val() == parentBroker || $(this).data('code') == parentBroker) {
                    $('#broker_id_val').val($(this).val()).trigger('change');
                }
            });
        }
    });

    // AJAX Form Submission
    $('form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalBtnText = submitBtn.text();

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    Swal.fire({ title: 'Success!', text: res.message, icon: 'success' })
                        .then(() => window.location.href = '/cattleya/user/encoder/registration');
                } else {
                    Swal.fire({ title: 'Error', text: res.message, icon: 'error' });
                }
            },
            error: function() {
                Swal.fire({ title: 'System Error', text: 'Please try again later.', icon: 'warning' });
            },
            complete: function() {
                submitBtn.prop('disabled', false).text(originalBtnText);
            }
        });
    });

    // --- INITIALIZATION FOR EDIT MODE ---
    const currentRole = $('#role_type').val();
    if (currentRole) {
        updateFormVisibility(currentRole);
        // Ensure badges show the IDs of already-selected items in Edit Mode
        reflectSelectedIDs(); 
    }
});

function clearSearch() {
    const searchInput = document.getElementById('personnelSearch');
    searchInput.value = '';
    searchInput.dispatchEvent(new Event('keyup'));
    searchInput.focus();
}

document.getElementById('personnelSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('.personnel-row');
    let noResults = document.getElementById('noResults');
    let countBadge = document.getElementById('personnelCount');
    let tableHeader = document.querySelector('#personnelTable thead');
    let visibleCount = 0;

    rows.forEach(row => {
        let text = row.textContent.toLowerCase();
        if (text.includes(filter)) {
            row.style.display = "";
            visibleCount++;
        } else {
            row.style.display = "none";
        }
    });

    // Update the Badge Text - Updated to Cattleya Navy theme
    if (filter === "") {
        countBadge.innerText = visibleCount + " Total";
        countBadge.style.background = "rgba(42, 98, 121, 0.1)"; // Default soft Cattleya Navy
        countBadge.style.color = "#2a6279";
    } else {
        countBadge.innerText = visibleCount + " Found";
        countBadge.style.background = "#2a6279"; // Solid Cattleya Navy when filtering
        countBadge.style.color = "#ffffff";
    }

    // Toggle Empty State Visibility
    if (visibleCount === 0) {
        noResults.style.display = "block";
        if(tableHeader) tableHeader.style.opacity = "0";
        countBadge.style.opacity = "0"; // Hide badge if nothing found
    } else {
        noResults.style.display = "none";
        if(tableHeader) tableHeader.style.opacity = "1";
        countBadge.style.opacity = "1";
    }
});

// Function to pad numbers to 6 digits
function padId(num, size = 6) {
    let s = num + "";
    while (s.length < size) s = "0" + s;
    return s;
}

// Example logic for your Role Switcher
document.querySelectorAll('.role-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const role = this.dataset.role;
        const idInput = document.getElementById('idInput');
        
        // Update hidden role field
        document.getElementById('role_type').value = role;

        // Visual active state
        document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        // Logic to generate the prefix and 6-digit ID
        let prefix = "";
        let placeholderExample = "";

        if (role === 'broker') {
            prefix = "BRK-";
            placeholderExample = "BRK-000001";
            document.getElementById('hierarchyBox').classList.add('d-none');
        } else if (role === 'unit_manager') {
            prefix = "UM-";
            placeholderExample = "UM-000001";
            document.getElementById('hierarchyBox').classList.remove('d-none');
            document.getElementById('umSelectContainer').classList.add('d-none');
        } else {
            prefix = "AGT-";
            placeholderExample = "AGT-000001";
            document.getElementById('hierarchyBox').classList.remove('d-none');
            document.getElementById('umSelectContainer').classList.remove('d-none');
        }

        idInput.placeholder = placeholderExample;
    });
});
</script>
</body>
</html>