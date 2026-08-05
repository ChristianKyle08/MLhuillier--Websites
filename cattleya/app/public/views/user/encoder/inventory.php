<?php
require __DIR__ . '/../../../../config/database.php';
require __DIR__ . '/../../includes/session_check.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /cattleya/login");
    exit;
}

$user_id    = $_SESSION['user_id'];
$user_name  = $_SESSION['user_name'];

/** * FINAL FIXED QUERY
 * Filtered for Active status in product_profile
 */
$query = "
    SELECT 
        pp.*,
        pp.id AS product_id,
        COUNT(CASE WHEN p.status = 'available' THEN 1 END) as available_count,
        COUNT(CASE WHEN p.status = 'reserved' THEN 1 END) as reserved_count,
        COUNT(CASE WHEN p.status = 'sold' THEN 1 END) as sold_count,
        COUNT(CASE WHEN p.status = 'inactive' THEN 1 END) as inactive_count
    FROM product_profile pp
    LEFT JOIN product p ON pp.product_name = p.product_name
    WHERE pp.status = 'active'  -- This line filters the profile table
    GROUP BY pp.id
    ORDER BY pp.product_name ASC
";

$stmt = $pdo->query($query);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Manager | Cattleya</title>
    <link rel="icon" href="../../../assets/icons/favicon/cattleya_favicon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
   :root {
        --primary-color: #6366f1; /* Indigo */
        --primary-hover: #4f46e5;
        --bg-soft: #f8fafc;
        --border-color: #e2e8f0;
        --text-main: #1e293b;
        --text-muted: #64748b;
    }

    body { 
        font-family: 'Plus Jakarta Sans', sans-serif; 
        background-color: var(--bg); 
        /* Updated gradient to use your primary color */
        background-image: radial-gradient(at 0% 0%, rgba(103, 61, 230, 0.03) 0px, transparent 50%);
        min-height: 100vh;
    }

    .main-content { margin-left: 15px; padding: 3rem; transition: all 0.3s ease; }
    @media (max-width: 991px) { .main-content { margin-left: 0; padding: 1.5rem; } }

    .header-section h2 { font-weight: 800; letter-spacing: -0.02em; color: var(--text-main); }

    .product-card {
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        background: white;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        border-color: var(--primary-light);
    }

    .project-icon-wrapper {
        width: 48px; 
        height: 48px;
        display: flex; 
        align-items: center; 
        justify-content: center;
        background: var(--primary);
        color: white; 
        border-radius: 12px;
        position: relative;
        overflow: hidden; 
        transition: transform 0.3s ease;
    }

    .project-icon-wrapper::after {
        content: "MAXIMIZE";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        /* Updated to #673de6 with opacity */
        background: rgba(103, 61, 230, 0.85); 
        color: white;
        font-size: 0.65rem;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transform: translateY(10px);
        transition: all 0.3s ease-in-out;
        pointer-events: none; 
        z-index: 2; 
    }

    .project-icon-wrapper:hover::after {
        opacity: 1;
        transform: translateY(0);
    }

    .project-icon-wrapper:hover img {
        transform: scale(1.1);
        transition: transform 0.4s ease;
    }

    .project-icon-wrapper:has(.bi-houses-fill)::after {
        display: none;
    }

    .status-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin: 1.25rem 0; }

    .status-mini-card {
        padding: 12px; 
        border-radius: 12px; 
        background: #ffffff;
        border: 1px solid #f1f2f6; 
        text-align: center;
        display: flex;
        flex-direction: column;
        transition: all 0.2s ease;
    }

    .text-avail { color: #00b894; }
    .text-res { color: #fdcb6e; }
    .text-sold { color: #d63031; }
    .text-inactive { color: #636e72; opacity: 0.8; }

    .status-label {
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .status-count {
        font-size: 1.1rem;
        font-weight: 700;
        color: #2d3436;
    }

    .lot-tile {
        padding: 8px 12px; border-radius: 10px; font-weight: 700; font-size: 0.75rem;
        margin: 4px; display: inline-flex; align-items: center; gap: 6px; border: 1px solid transparent;
    }
    .status-available { background: #ecfdf5; color: #065f46; border-color: #d1fae5; }
    .status-reserved { background: #fffbeb; color: #1e40af; border-color: #fef3c7;  }
    .status-sold { background: #fef2f2; color: #991b1b; border-color: #fee2e2; }

    #updateStatusModal .modal-content {
        border-radius: 28px !important;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .status-selection-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .status-radio { display: none; }
    
    .status-card {
        padding: 12px; border-radius: 14px; background: #fff; border: 1.5px solid #f1f5f9;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        cursor: pointer; transition: all 0.25s ease; color: #64748b; font-weight: 700; font-size: 0.75rem;
    }
    .status-card .dot { width: 8px; height: 8px; border-radius: 50%; background: #cbd5e1; }

    .status-radio:checked + .avail { border-color: #10b981; background: #ecfdf5; color: #065f46; }
    .status-radio:checked + .avail .dot { background: #10b981; box-shadow: 0 0 8px #10b981; }

    .status-radio:checked + .res { border-color: #3b82f6; background: #eff6ff; color: #1e40af; }
    .status-radio:checked + .res .dot { background: #3b82f6; box-shadow: 0 0 8px #3b82f6; }

    .status-radio:checked + .sold { border-color: #ef4444; background: #fef2f2; color: #991b1b; }
    .status-radio:checked + .sold .dot { background: #ef4444; box-shadow: 0 0 8px #ef4444; }
    
    .status-radio:checked + .card-inactive { background: #f8fafc; border-color: #64748b; color: #1e293b; }

    .status-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    .btn-update-confirm {
        background: #1a1a1a;
        color: white;
        border-radius: 14px;
        padding: 14px;
        font-weight: 700;
        border: none;
        transition: all 0.3s;
    }

    .btn-update-confirm:hover {
        background: var(--primary-dark);
        transform: translateY(-1px);
    }

  

    .lot-tile {
        position: relative;
        overflow: hidden;
        background: #ffffff;
        border: 1px solid #eee;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); 
        animation: breathing-glow 3s infinite ease-in-out;
        z-index: 1;
    }

    /* Base configuration for the hover slide-up overlay (now includes inactive status) */
    .lot-tile::after {
        content: "CLICK ME";
        position: absolute;
        bottom: -100%; 
        left: 0;
        width: 100%;
        height: 100%;
        backdrop-filter: blur(4px); 
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 1px;
        transition: bottom 0.3s ease;
        z-index: 2;
    }

    /* Background overlays based on matching parent status classes */
    .lot-tile.status-available::after {
        background: rgba(4, 120, 87, 0.92); /* Luxury Translucent Emerald */
    }

    .lot-tile.status-sold::after {
        background: rgba(190, 18, 60, 0.92); /* Luxury Translucent Crimson */
    }

    .lot-tile.status-reserved::after {
        background: rgba(55, 48, 163, 0.92); /* Luxury Translucent Indigo */
    }

    .lot-tile.status-inactive::after {
        background: rgba(71, 85, 105, 0.92); /* Luxury Translucent Slate */
    }

    /* Global hover triggers across all tiles */
    .lot-tile:hover {
        transform: translateY(-5px) scale(1.05);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15) !important;
        animation: none; 
    }

    .lot-tile:hover::after {
        bottom: 0; 
    }

    .lot-tile .lot-number-text {
        transition: opacity 0.3s ease;
    }

    .lot-tile:hover .lot-number-text {
        opacity: 0.2; 
    }

    .segmented-control {
        display: flex;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 12px;
        margin-bottom: 20px;
        border: 1px solid #e2e8f0;
    }
    .segmented-control input[type="radio"] {
        display: none;
    }
    .segmented-control label {
        flex: 1;
        text-align: center;
        padding: 8px 12px;
        border-radius: 9px;
        cursor: pointer;
        font-size: 0.85rem;
        font-weight: 600;
        color: #64748b;
        transition: all 0.2s ease;
        margin-bottom: 0;
    }
    .segmented-control input[type="radio"]:checked + label {
        background: #ffffff;
        color: var(--primary);
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    }

    .modern-field {
        position: relative;
        margin-bottom: 12px;
    }
    .modern-field i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 0.9rem;
        pointer-events: none;
    }
    .modern-field .form-control, 
    .modern-field .form-select {
        padding-left: 38px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        font-size: 0.9rem;
        transition: all 0.2s;
    }
    .modern-field .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(103, 61, 230, 0.1);
    }

    .fw-800 { font-weight: 800; }
    .info-label { font-size: 0.62rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.04em; display: block; margin-bottom: 2px; }
    .asset-info-box { background: #f8fafc; border: 1.5px solid #eef2f6; }

    .price-summary-card {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        position: relative; overflow: hidden;
    }
    .niche-badge { background: rgba(255,255,255,0.1); padding: 4px 12px; border-radius: 20px; font-size: 0.65rem; }
    .price-icon-bg { 
        width: 32px; height: 32px; background: rgba(16, 185, 129, 0.2); 
        border-radius: 50%; color: #10b981; display: flex; align-items: center; justify-content: center;
    }

    .client-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 16px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
    .profile-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 15px;
    }
    .avatar-circle {
        width: 40px;
        height: 40px;
        background: rgba(103, 61, 230, 0.1);
        color: var(--primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    .client-info-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 8px;
        font-size: 0.85rem;
        color: #475569;
    }
    .client-info-item i { color: #94a3b8; margin-top: 3px; }
    
    .swal2-confirm, .swal2-cancel {
        transition: transform 0.2s ease, box-shadow 0.2s ease !important;
    }
    .swal2-confirm:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(25, 135, 84, 0.3) !important;
    }
    .ls-2 { letter-spacing: 2px; }
    .custom-tcp-input:focus { border-bottom: 2px solid var(--primary) !important; }
    .transition-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .transition-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }

    .shake-animation {
        animation: shake 0.2s ease-in-out 0s 2;
    }

    .modern-input:valid:not(:placeholder-shown) {
        border-color: rgba(46, 204, 113, 0.4) !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%232ecc71' class='bi bi-check-circle-fill' viewBox='0 0 16 16'%3E%3Cpath d='M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
    }

    .insta-note-right {
        background: #fdfdfd; 
        color: #444; 
        font-size: 10px;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 18px; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        white-space: nowrap;
        position: relative;
        margin-left: 10px; 
        pointer-events: none; 
        animation: noteFadeIn 1s ease-out; 
    }

    .insta-note-right::before {
        content: '';
        position: absolute;
        left: -6px; 
        top: 50%;
        transform: translateY(-50%); 
        width: 0;
        height: 0;
        border-top: 6px solid transparent;
        border-bottom: 6px solid transparent;
        border-right: 7px solid #fdfdfd; 
    }

    @keyframes noteFadeIn {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
    }

    .insta-note {
        position: absolute;
        top: -30px; 
        right: -10px;
        /* Updated note background to use your primary color at low opacity */
        background: rgba(103, 61, 230, 0.1);
        color: #333;
        font-size: 10px;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 15px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        white-space: nowrap;
        pointer-events: none; 
        animation: floatNote 3s ease-in-out infinite;
    }

    .insta-note::after {
        content: '';
        position: absolute;
        bottom: -5px;
        right: 15px;
        width: 0;
        height: 0;
        border-left: 6px solid transparent;
        border-right: 6px solid transparent;
        border-top: 6px solid white;
    }

    /* Ensures SweetAlert matches your 28px-32px modal style */
.premium-modal-radius {
    border-radius: 32px !important;
    font-family: 'Plus Jakarta Sans', sans-serif !important;
}

/* Styles the progress bar at the bottom of the success modal */
.swal2-timer-progress-bar {
    background: #673de6 !important;
}

/* Smoother icon animation */
.swal2-icon.swal2-success [class^='swal2-success-line'] {
    background-color: #10b981 !important;
}
.swal2-icon.swal2-success {
    border-color: #10b981 !important;
}
    @keyframes floatNote {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-4px); }
    }

    
    .calculator-container {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        border: 1px solid var(--border-color);
    }

    .modern-field label {
        font-size: 0.85rem;
        letter-spacing: 0.025em;
        color: var(--text-muted);
        margin-bottom: 6px;
        transition: color 0.2s;
    }

    .form-select, .form-control {
        border: 2px solid var(--border-color) !important;
        border-radius: 12px !important;
        transition: all 0.2s ease-in-out !important;
        font-weight: 500;
        color: var(--text-main);
    }

    .form-select:focus, .form-control:focus {
        border-color: var(--primary-color) !important;
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1) !important;
        outline: none;
    }

    .input-group-text {
        background-color: var(--bg-soft) !important;
        border: 2px solid var(--border-color) !important;
        border-right: none !important;
        border-radius: 12px 0 0 12px !important;
        color: var(--primary-color) !important;
    }

    .payment-input {
        border-radius: 0 12px 12px 0 !important;
    }

    .btn-reset {
        background-color: var(--bg-soft);
        color: var(--text-main);
        border: 2px solid var(--border-color);
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.2s;
        height: 52px;
    }

    .btn-reset:hover {
        background-color: #f1f5f9;
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }

    .btn-reset:active {
        transform: translateY(0px);
    }
    
    /* Animation for auto-filled values */
    @keyframes highlight {
        0% { background-color: rgba(99, 102, 241, 0.1); }
        100% { background-color: transparent; }
    }
    .value-updated {
        animation: highlight 1s ease-out;
    }
</style>
</head>

<body>
<?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>

<div class="main-content">
    <div class="header-section mb-5 d-flex justify-content-between align-items-center">
        <div>
            <h2 style="color:#2a6279;">Portfolio Assets</h2>
            <p class="text-muted mb-0">Manage inventory and monitor unit status in real-time.</p>
        </div>
        <div class="text-end">
            <span class="badge bg-white border text-dark p-2 rounded-3 shadow-sm px-3 fw-bold">
                <i class="bi bi-clock me-2 text-primary"></i> <?= date('M d, Y') ?>
            </span>
        </div>
    </div>

    <div class="row g-4">
    <?php foreach($products as $product): ?>
        <div class="col-xl-4 col-md-6">
            <div class="card product-card p-4 shadow-sm border-0 position-relative" style="border-radius: 24px;">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="product-img-group position-relative">
                        <div class="project-icon-wrapper z-1 overflow-hidden shadow-sm border d-flex align-items-center justify-content-center position-relative" 
                            id="container_<?= $product['product_id'] ?>"
                            title="Click to maximize image"
                            style="width: 70px; height: 70px; cursor: pointer; background-color: #2a6279; border-radius: 16px; transition: 0.3s;"
                            onclick="maximizeImage('<?= $product['product_id'] ?>')">
                    
                            <?php if (!empty($product['product_image'])): ?>
                                <img src="data:image/jpeg;base64,<?= base64_encode($product['product_image']) ?>" 
                                    id="img_prev_<?= $product['product_id'] ?>" 
                                    class="w-100 h-100 object-fit-cover" alt="Product">
                            <?php else: ?>
                                <i class="bi bi-houses-fill fs-2 opacity-50" id="icon_<?= $product['product_id'] ?>" style="color: #ffffff;"></i>
                                <img src="" id="img_prev_<?= $product['product_id'] ?>" class="w-100 h-100 object-fit-cover d-none">
                            <?php endif; ?>
                            
                            <div id="loader_<?= $product['product_id'] ?>" class="position-absolute w-100 h-100 d-none align-items-center justify-content-center" style="background: rgba(255,255,255,0.7); backdrop-filter: blur(4px);">
                                <div class="spinner-border spinner-border-sm" style="color: #2a6279;"></div>
                            </div>
                        </div>

                        <div class="position-absolute" style="bottom: -5px; right: -5px; z-index: 10;">
                            <div class="insta-note">
                                Change photo?
                            </div>

                            <button class="btn btn-sm rounded-circle shadow" 
                                    style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border: 2px solid white; background: #96c93d; color: white;"
                                    onclick="event.stopPropagation(); document.getElementById('file_<?= $product['product_id'] ?>').click()">
                                <i class="bi bi-camera-fill" style="font-size: 12px;"></i>
                            </button>
                        </div>
                        <input type="file" id="file_<?= $product['product_id'] ?>" class="d-none" accept="image/*" 
                                onchange="prepareImageUpload(this, '<?= $product['product_id'] ?>')">
                    </div>

                    <div id="actions_<?= $product['product_id'] ?>" class="d-none animate__animated animate__fadeIn">
                        <button class="btn btn-success btn-sm rounded-pill px-3 me-1 shadow-sm" style="background-color: #96c93d; border: none;" onclick="saveImage('<?= $product['product_id'] ?>')">
                            <i class="bi bi-check-lg me-1"></i>Save
                        </button>
                        <button class="btn btn-light btn-sm rounded-pill border shadow-sm" onclick="cancelUpload('<?= $product['product_id'] ?>')">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>

                    <span class="badge bg-white text-muted border px-3 py-2 rounded-pill small shadow-sm">
                        <i class="bi bi-stack me-1" style="color: #2a6279;"></i> 
                        <?= number_format($product['available_count'] + $product['reserved_count'] + $product['sold_count'] + $product['inactive_count']) ?> Units
                    </span>
                </div>
        
                <h4 class="fw-bold mb-1" style="color:#2a6279;"><?= htmlspecialchars($product['product_name']) ?></h4>
                <div class="text-muted small mb-2"><i class="bi bi-geo-alt me-1" style="color:#96c93d;"></i><?= htmlspecialchars($product['address'] ?? 'Not set') ?></div>
                <div class="text-muted mb-2" style="font-size: 0.85rem;"><i class="bi bi-person me-1" style="color:#96c93d;"></i>Owner: <span class="fw-semibold" style="color: #2a6279;"><?= htmlspecialchars($product['owner'] ?? 'Cattleya') ?></span></div>

                <div class="row g-0 border rounded-4 overflow-hidden mb-4">
                    <div class="col-6 border-end border-bottom p-3">
                        <span class="d-block text-muted text-uppercase mb-1" style="font-size: 0.55rem; font-weight: 700;">Available</span>
                        <span class="h6 fw-bold mb-0" style="color: #96c93d;"><?= number_format($product['available_count']) ?></span>
                    </div>
                    <div class="col-6 border-bottom p-3">
                        <span class="d-block text-muted text-uppercase mb-1" style="font-size: 0.55rem; font-weight: 700;">Reserved</span>
                        <span class="h6 fw-bold mb-0" style="color: #2a6279;"><?= number_format($product['reserved_count']) ?></span>
                    </div>
                    <div class="col-6 border-end p-3">
                        <span class="d-block text-muted text-uppercase mb-1" style="font-size: 0.55rem; font-weight: 700;">Sold Out</span>
                        <span class="h6 fw-bold text-danger mb-0"><?= number_format($product['sold_count']) ?></span>
                    </div>
                    <div class="col-6 p-3">
                        <span class="d-block text-muted text-uppercase mb-1" style="font-size: 0.55rem; font-weight: 700;">Inactive</span>
                        <span class="h6 fw-bold text-secondary mb-0"><?= number_format($product['inactive_count']) ?></span>
                    </div>
                </div>
                <?php
                // Check if any commission profile exists
                $check_comm = $pdo->query("SELECT COUNT(*) FROM commission_profiles");
                $has_commission = $check_comm->fetchColumn() > 0;
                ?>

                <div class="mt-auto d-flex gap-2">
                    <button class="btn flex-grow-1 rounded-3 py-2 fw-semibold text-white shadow-sm" 
                            style="background: #2a6279; border: none;" 
                            onclick="openAddForm('<?= htmlspecialchars($product['product_name']) ?>')">
                        <i class="bi bi-plus-lg me-2"></i>Add Unit
                    </button>

                    <?php if ($has_commission): ?>
                        <button class="btn rounded-3 px-3 shadow-sm" 
                                style="border-color: #2a6279; color: #2a6279;" 
                                onclick="openViewModal('<?= htmlspecialchars($product['product_name']) ?>')">
                            <i class="bi bi-cart-plus me-1"></i>Add Sales
                        </button>
                    <?php else: ?>
                        <button class="btn rounded-3 px-3 shadow-sm" 
                                style="border-color: #cbd5e1; color: #94a3b8; background-color: #f8fafc; cursor: help;" 
                                onclick="showCommError()">
                            <i class="bi bi-cart-plus me-1"></i>Add Sales
                        </button>
                    <?php endif; ?>
                </div>

                <script>
                function showCommError() {
                    Swal.fire({
                        title: '<span style="font-family: \'Plus Jakarta Sans\', sans-serif; font-weight: 800; color: #1e293b;">Configuration Required</span>',
                        html: '<p style="font-family: \'Plus Jakarta Sans\', sans-serif; font-size: 14px; color: #64748b;">The system cannot process sales without a commission profile. Please contact your <b>Administrator or Manager</b> to setup the distribution registry.</p>',
                        icon: 'warning',
                        iconColor: '#2a6279',
                        confirmButtonColor: '#2a6279',
                        confirmButtonText: 'CLOSE',
                        buttonsStyling: true,
                        customClass: {
                            popup: 'rounded-4 shadow-xl',
                            confirmButton: 'rounded-3 px-4 py-2 fw-bold text-uppercase tracking-wider'
                        },
                        showClass: {
                            popup: 'animate__animated animate__fadeInUp animate__faster'
                        }
                    });
                }
                </script>
            </div>
        </div>
    <?php endforeach; ?>
</div>
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body p-0 position-relative">
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                    <img src="" id="maximizedImage" class="img-fluid rounded shadow-lg w-100" style="max-height: 90vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 px-4 pt-4">
                    <h4 class="fw-bold" id="viewModalTitle">Inventory Map</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4"><div id="inventoryContainer" class="row g-3"></div></div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width: 60%;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 px-4 pt-4 pb-0">
                <div class="d-flex align-items-center">
                    <div class="icon-box me-3 shadow-sm d-flex align-items-center justify-content-center" 
                        style="background: #2a6279; width: 45px; height: 45px; border-radius: 12px;">
                        <i class="bi bi-building text-white fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color: #2a6279;">Register New Asset</h5>
                        <p class="text-muted small mb-0">Adding unit to: <span id="displayProductName" style="color: #96c93d; font-weight: 700;"></span></p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addLotForm">
                <div class="modal-body px-4 py-4">
                    <input type="hidden" name="product_name" id="formProductName">
                    <input type="hidden" name="created_by" value="<?= htmlspecialchars($user_name) ?>">
                    
                    <div class="section-title mb-3">
                        <span class="badge rounded-pill px-3 py-2" style="background-color: rgba(42, 98, 121, 0.1); color: #2a6279;">
                            1. Basic Specifications
                        </span>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label-custom">Block / Section</label>
                            <input type="text" class="form-control modern-input" name="block_number" id="input_block" list="block_list" placeholder="e.g. B12" required autocomplete="off">
                            <datalist id="block_list"></datalist>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-custom">Lot Number</label>
                            <input type="text" class="form-control modern-input" name="lot_number" id="input_lot" list="lot_list" placeholder="e.g. 05" required autocomplete="off">
                            <datalist id="lot_list"></datalist>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label-custom">Niche Type</label>
                            <input type="text" class="form-control modern-input" name="niche_type" id="input_niche" list="niche_list" placeholder="Lawn / Terrace" autocomplete="off">
                            <datalist id="niche_list"></datalist>
                        </div>
                        <div class="col-12">
                            <label class="form-label-custom">Location Description</label>
                            <textarea class="form-control modern-input" name="block_description" rows="2" placeholder="Describe the location or features..."></textarea>
                        </div>
                    </div>

                    <div class="section-title mb-3">
                        <span class="badge rounded-pill px-3 py-2" style="background-color: rgba(42, 98, 121, 0.1); color: #2a6279;">2. Financial Breakdown</span>
                    </div>

                    <div class="card border-0 rounded-4 shadow-sm overflow-hidden mb-4">
                        <div style="height: 5px; background: linear-gradient(90deg, #2a6279, #96c93d, #2a6279);"></div>
                        
                        <div class="card-body p-4">
                            <div class="row mb-4">
                                <div class="col-md-6 text-center border-end">
                                    <label class="form-label text-uppercase fw-bold text-muted ls-2 small mb-2">Total Contract Price (TCP)</label>
                                    <div class="d-flex justify-content-center">
                                        <div class="input-group input-group-lg" style="max-width: 250px;">
                                            <span class="input-group-text bg-transparent border-0 fs-3 fw-light text-secondary">₱</span>
                                            <input type="number" 
                                                class="form-control border-0 border-bottom rounded-0 fs-5 fw-bold text-center px-0 custom-tcp-input" 
                                                id="main_tcp" 
                                                name="main_tcp" 
                                                placeholder="0" 
                                                style="box-shadow: none; border-bottom: 2px solid #eee !important;"
                                                autocomplete="off">
                                        </div>
                                    </div>
                                    <p class="text-muted small mt-2"><i class="bi bi-magic me-1" style="color:#96c93d;"></i> Smart breakdown scales as you type</p>
                                </div>
                                
                                <div class="col-md-6 text-center">
                                    <label class="form-label text-uppercase fw-bold text-muted ls-2 small mb-2">Cash Price</label>
                                    <div class="d-flex justify-content-center">
                                        <div class="input-group input-group-lg" style="max-width: 250px;">
                                            <span class="input-group-text bg-transparent border-0 fs-3 fw-light text-secondary">₱</span>
                                            <input type="number" 
                                                class="form-control border-0 border-bottom rounded-0 fs-5 fw-bold text-center px-0 custom-cash-input" 
                                                id="cash_price" 
                                                name="cash_price" 
                                                placeholder="0" 
                                                style="box-shadow: none; border-bottom: 2px solid #eee !important;"
                                                autocomplete="off">
                                        </div>
                                    </div>
                                    <p class="text-muted small mt-2"><i class="bi bi-cash-coin me-1" style="color:#2a6279;"></i> For spot cash payments</p>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <div class="p-2 rounded-4 h-100 transition-hover" style="background: #f4f8f9; border: 1px solid #e9f1f3;">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="rounded-circle p-2 me-2" style="background-color: rgba(42, 98, 121, 0.1);">
                                                <i class="bi bi-map small" style="color: #2a6279;"></i>
                                            </div>
                                            <span class="text-muted fw-bold text-uppercase" style="font-size: 14px; letter-spacing: 0.5px;">Lot Price</span>
                                        </div>
                                        <div class="h5 mb-0 fw-bold text-dark" style="font-size: 13px;">₱<span id="out_lot_price">---</span></div>
                                    </div>
                                </div>

                                <div class="col-6 col-md-3">
                                    <div class="p-2 rounded-4 h-100 transition-hover" style="background: #fff8f8; border: 1px solid #fdf2f2;">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="rounded-circle bg-danger-subtle p-2 me-2">
                                                <i class="bi bi-receipt text-danger small"></i>
                                            </div>
                                            <span class="text-muted fw-bold text-uppercase" style="font-size: 14px; letter-spacing: 0.5px;">VAT (12%)</span>
                                        </div>
                                        <div class="h5 mb-0 fw-bold text-dark" style="font-size: 13px;">₱<span id="out_vat">---</span></div>
                                    </div>
                                </div>

                                <div class="col-6 col-md-3">
                                    <div class="p-2 rounded-4 h-100 transition-hover" style="background: #f8fff9; border: 1px solid #f0fdf4;">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="rounded-circle bg-success-subtle p-2 me-2">
                                                <i class="bi bi-shield-check text-success small"></i>
                                            </div>
                                            <span class="text-muted fw-bold text-uppercase" style="font-size: 14px; letter-spacing: 0.5px;">Care Fund</span>
                                        </div>
                                        <div class="mb-0 fw-bold text-dark" style="font-size: 13px;">₱<span id="out_care_fund">---</span></div>
                                    </div>
                                </div>

                                <div class="col-6 col-md-3">
                                    <div class="p-2 rounded-4 h-100 transition-hover" style="background: #f4f8f9; border: 1px solid #e9f1f3;">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="rounded-circle p-2 me-2" style="background-color: rgba(150, 201, 61, 0.1);">
                                                <i class="bi bi-megaphone small" style="color: #96c93d;"></i>
                                            </div>
                                            <span class="text-muted fw-bold text-uppercase" style="font-size: 14px; letter-spacing: 0.5px;">Marketing</span>
                                        </div>
                                        <div class="mb-0 fw-bold text-dark" style="font-size: 13px;">₱<span id="out_marketing">---</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="lot_price" id="hid_lot_price">
                    <input type="hidden" name="vat" id="hid_vat">
                    <input type="hidden" name="care_fund" id="hid_care_fund">
                    <input type="hidden" name="marketing_budget" id="hid_marketing">

                    <div class="col-12">
                        <label class="form-label-custom">Initial Unit Status</label>
                        <select class="form-select modern-input" name="status">
                            <option value="available">🟢 Available for Sale</option>
                        </select>
                    </div>

                    <div class="modal-footer border-0 px-4 pb-1 pt-4">
                        <button type="button" class="btn btn-light px-4 py-3 rounded-3 text-muted fw-bold me-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn shadow-sm px-4 py-3 rounded-3 fw-bold text-white flex-grow-1" 
                                style="background: #2a6279; border: none;">
                            <i class="bi bi-plus-lg me-2"></i>Confirm Registration
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light border-0 px-4 py-3">
                <h5 class="fw-bold mb-0">Add / View Sales</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <form id="updateStatusForm">
                <input type="hidden" name="product_id" id="update_product_id">
                <input type="hidden" id="active_sales_id" name="sales_id">
                <input type="hidden" name="selected_customer_name" id="hidden_customer_name">
                
                <div class="modal-body p-4">
                    <div class="row g-4">
                        
                        <div class="col-lg-4 border-end pe-lg-4">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div>
                                    <h6 class="fw-800 mb-1 small text-uppercase tracking-tighter" style="color: #2a6279;">
                                        <i class="bi bi-geo-alt-fill me-2"></i>Unit Occupancy
                                    </h6>
                                    <p class="text-muted mb-0" style="font-size: 0.7rem;">Manage availability & unit details</p>
                                </div>
                                <span class="badge rounded-pill bg-opacity-10 border-0 px-3 py-2 fw-bold" style="font-size: 0.65rem; background-color: rgba(42, 98, 121, 0.1); color: #2a6279;">ID: #LOT-ACTIVE</span>
                            </div>

                            <div class="status-selection-grid mb-4">
                                <label class="status-option">
                                    <input type="radio" name="status" value="available" class="status-radio">
                                    <div class="status-card avail">
                                        <div class="dot"></div>
                                        <span>Available</span>
                                    </div>
                                </label>
                                <label class="status-option">
                                    <input type="radio" name="status" value="reserved" class="status-radio">
                                    <div class="status-card res">
                                        <div class="dot"></div>
                                        <span>Reserved</span>
                                    </div>
                                </label>
                                <label class="status-option">
                                    <input type="radio" name="status" value="sold" class="status-radio">
                                    <div class="status-card sold">
                                        <div class="dot"></div>
                                        <span>Sold</span>
                                    </div>
                                </label>
                                <label class="status-option">
                                    <input type="radio" name="status" value="inactive" class="status-radio">
                                    <div class="status-card inactive">
                                        <div class="dot"></div>
                                        <span>Inactive</span>
                                    </div>
                                </label>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="asset-info-box p-3 rounded-4">
                                        <div class="row g-2">
                                            <div class="col-12 border-bottom pb-2 mb-2">
                                                <label class="info-label">Current Product</label>
                                                <input type="text" id="disp_product_name" name="product_name" 
                                                    class="form-control-plaintext p-0 fw-800" style="color:#2a6279;" readonly placeholder="Select a unit...">
                                            </div>
                                            <div class="col-6 border-end">
                                                <label class="info-label">Block No.</label>
                                                <input type="text" id="disp_block_no" name="block_number" 
                                                    class="form-control-plaintext p-0 fw-bold" style="color:#2a6279;" readonly placeholder="---">
                                            </div>
                                            <div class="col-6 ps-3">
                                                <label class="info-label">Lot No.</label>
                                                <input type="text" id="disp_lot_no" name="lot_number" 
                                                    class="form-control-plaintext p-0 fw-bold" style="color:#2a6279;" readonly placeholder="---">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="price-summary-card p-4 rounded-4 shadow-lg text-white" style="background: linear-gradient(135deg, #2a6279 0%, #1e4a5c 100%);">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="niche-badge" style="background: rgba(150, 201, 61, 0.2); color: #96c93d; border: 1px solid rgba(150, 201, 61, 0.3);">
                                                <i class="bi bi-layers-half me-1"></i>
                                                <input type="text" id="disp_niche" name="niche_type" class="bg-transparent border-0 text-white fw-bold d-inline w-auto" readonly placeholder="Type" style="color: #96c93d !important;">
                                            </div>
                                            <div class="price-icon-bg" style="background: rgba(255,255,255,0.1); color: #96c93d;">₱</div>
                                        </div>
                                        
                                        <div class="mb-1">
                                            <label class="info-label text-white-50">Total Contract Price (TCP)</label>
                                            <input type="hidden" id="raw_price" name="tcp_value"> 
                                            <input type="text" id="disp_price" class="form-control-plaintext py-0 text-white fw-800 fs-3" readonly placeholder="₱ 0.00">
                                        </div>

                                        <div class="mb-1">
                                            <label class="info-label text-white-50">Cash Price (Discounted)</label>
                                            <input type="hidden" id="raw_cash_price" name="cash_price_value"> 
                                            <input type="text" id="disp_cash_price" class="form-control-plaintext py-0 text-white fw-700 fs-4" readonly placeholder="₱ 0.00" style="color: #96c93d !important;">
                                        </div>
                                        
                                        <input type="text" id="disp_desc" name="block_description" class="form-control-plaintext py-0 text-white-50 text-truncate" style="font-size: 0.7rem;" readonly placeholder="Product description will appear here...">
                                    </div>
                                </div>
                            </div>

                            <select id="sel_lot" style="display:none;"></select>
                            <select id="sel_block" style="display:none;"></select>
                            <select id="sel_product" style="display:none;"></select>
                        </div>

                        <div class="col-lg-4 border-end px-4 customer-section">
                            <div id="customerInputGroup">
                                <h6 class="fw-bold mb-4 small text-uppercase tracking-wider" style="color: #2a6279;">
                                    <i class="bi bi-person-badge me-2"></i>Customer Information
                                </h6>

                                <div class="segmented-control mb-3">
                                    <input type="radio" name="customer_type" id="typeNew" value="new" class="customer-type-radio" checked>
                                    <label for="typeNew">New Client</label>
                                    
                                    <input type="radio" name="customer_type" id="typeExisting" value="existing" class="customer-type-radio">
                                    <label for="typeExisting">Existing</label>
                                </div>

                                <div id="existingCustomerFields" class="row g-2 d-none mb-3">
                                    <div class="col-12 modern-field">
                                        <i class="bi bi-search"></i>
                                        <select class="form-select" name="existing_customer_id" id="sel_existing_customer" style="padding-left: 38px;">
                                            <option value="">Search Customer ID...</option>
                                        </select>
                                    </div>
                                </div>

                                <div id="newCustomerFields" class="row g-2">
                                    <div class="col-12 modern-field">
                                        <i class="bi bi-hash"></i>
                                        <input type="text" class="form-control" id="new_customer_id" name="customer_id" placeholder="Generating ID..." readonly>
                                    </div>
                                    <div class="col-12 modern-field">
                                        <i class="bi bi-person"></i>
                                        <input type="text" class="form-control" placeholder="First Name" name="fname">
                                    </div>
                                    <div class="col-6 modern-field">
                                        <i class="bi bi-person-text"></i>
                                        <input type="text" class="form-control" placeholder="Middle" name="mname">
                                    </div>
                                    <div class="col-6 modern-field">
                                        <i class="bi bi-person-text"></i>
                                        <input type="text" class="form-control" placeholder="Last Name" name="lname">
                                    </div>
                                    <div class="col-12 modern-field">
                                        <i class="bi bi-telephone"></i>
                                        <input type="tel" class="form-control" placeholder="Mobile Number" name="mobile">
                                    </div>
                                    <div class="col-12 modern-field">
                                        <i class="bi bi-envelope"></i>
                                        <input type="email" class="form-control" placeholder="Email Address" name="email">
                                    </div>
                                    <div class="col-12 modern-field">
                                        <i class="bi bi-geo-alt" style="top: 25px;"></i>
                                        <textarea class="form-control" rows="2" style="padding-left: 38px;" placeholder="Complete Address" name="address"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div id="customerDetailGroup" class="d-none animate__animated animate__fadeIn mb-4">
                                <h6 class="fw-bold mb-4 small text-uppercase tracking-wider" style="color: #2a6279;">
                                    <i class="bi bi-person-check me-2"></i>Customer History
                                </h6>
                                <div class="p-3 rounded-4 shadow-sm" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                    <div class="mb-2">
                                        <label class="d-block small text-muted fw-semibold mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Full Name</label>
                                        <span id="det_customer_name" class="fw-bold text-dark">---</span>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <label class="d-block small text-muted fw-semibold mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Customer ID</label>
                                            <span id="det_customer_id" class="text-dark small">---</span>
                                        </div>
                                        <div class="col-6">
                                            <label class="d-block small text-muted fw-semibold mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Contact Number</label>
                                            <span id="det_mobile" class="text-dark small">---</span>
                                        </div>
                                    </div>
                                    <div class="row pt-4 pb-4 border-top">
                                        <div class="col-6">
                                            <label class="d-block small text-muted fw-semibold mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Lot Status</label>
                                            <span id="det_is_assumed" class="fw-bold text-dark small">---</span>
                                        </div>
                                        <div class="col-6">
                                            <label class="d-block small text-muted fw-semibold mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Payment Type</label>
                                            <span id="det_payment_type" class="fw-bold text-dark small">---</span>
                                        </div>
                                    </div>
                                    <div class="row pt-2 border-top">
                                        <div class="col-6 pb-2">
                                            <label id="lbl_terms" class="d-block small text-muted fw-semibold mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Installment Terms</label>
                                            <span id="det_installment_terms" class="fw-bold text-dark small">---</span>
                                        </div>
                                        <div class="col-6 pb-2">
                                            <label id="lbl_start_date" class="d-block small text-muted fw-semibold mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Installment Start Date</label>
                                            <span id="det_installment_startDate" class="fw-bold text-dark small">---</span>
                                        </div>
                                        <div class="col-6 pb-2">
                                            <label id="lbl_end_date" class="d-block small text-muted fw-semibold mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Installment End Date</label>
                                            <span id="det_installment_endDate" class="fw-bold text-dark small">---</span>
                                        </div>
                                        <div class="col-6 pb-2">
                                            <label id="lbl_monthly" class="d-block small text-muted fw-semibold mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Installment Monthly Payment</label>
                                            <span id="det_installment_monthlyPayment" class="fw-bold text-dark small">---</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        $query = "SELECT 
                                    a.id, a.agent_id, a.firstname, a.lastname, 
                                    um.firstname AS um_fname, um.lastname AS um_lname, um.um_id AS um_code,
                                    b.firstname AS b_fname, b.lastname AS b_lname, b.broker_id AS b_code
                                FROM agents a
                                LEFT JOIN unit_managers um ON a.um_id = um.um_id
                                LEFT JOIN brokers b ON a.broker_id = b.broker_id
                                ORDER BY a.lastname ASC";
                        $agents = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <div class="col-lg-4">
                            <div id="agentInputGroup" class="h-100">
                                <h6 class="fw-bold mb-4 small text-uppercase tracking-wider d-flex align-items-center" style="color: #2a6279;">
                                    <i class="bi bi-briefcase me-2"></i>Agent & Transaction
                                </h6>

                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="modern-field">
                                            <label class="small fw-bold text-secondary mb-1 d-block">Primary Agent</label>
                                            <div class="position-relative">
                                                <i class="bi bi-person-badge position-absolute top-50 translate-middle-y ms-3 text-muted"></i>
                                                <select class="form-select ps-5 shadow-sm border-2" id="sel_agent" name="agent_id" 
                                                        style="border-color: #f1f5f9; border-radius: 12px; height: 50px;">
                                                    <option value="" data-name="---" data-um="---" data-broker="---" data-agent-id="---" data-um-id="---" data-broker-id="---">Select Agent...</option>
                                                    <?php foreach($agents as $agent): 
                                                        $fullName   = htmlspecialchars($agent['lastname'] . ', ' . $agent['firstname']);
                                                        $umName     = htmlspecialchars(($agent['um_lname'] ?? 'N/A') . ', ' . ($agent['um_fname'] ?? ''));
                                                        $brokerName = htmlspecialchars(($agent['b_lname'] ?? 'N/A') . ', ' . ($agent['b_fname'] ?? ''));
                                                    ?>
                                                        <option value="<?= $agent['id'] ?>" 
                                                                data-name="<?= $fullName ?>"
                                                                data-agent-id="<?= htmlspecialchars($agent['agent_id']) ?>"
                                                                data-um="<?= $umName ?>" 
                                                                data-um-id="<?= htmlspecialchars($agent['um_code'] ?? '---') ?>"
                                                                data-broker="<?= $brokerName ?>"
                                                                data-broker-id="<?= htmlspecialchars($agent['b_code'] ?? '---') ?>">
                                                            <?= $fullName ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <input type="hidden" name="agent_fullname" id="hidden_agent_name" class="form-control mt-2" readonly placeholder="Agent Full Name">
                                    <input type="hidden" name="agent_id"       id="hidden_agent_id"> <input type="hidden" name="um_id"          id="hidden_um_id">
                                    <input type="hidden" name="um_name"        id="hidden_um_name">
                                    <input type="hidden" name="broker_id"      id="hidden_broker_id">
                                    <input type="hidden" name="broker_name"    id="hidden_broker_name">
                                    <div class="col-12">
                                        <div class="p-3 rounded-4 border-0 shadow-sm" style="background: linear-gradient(145deg, #ffffff, #f8fafc); border: 1px solid #f1f5f9 !important;">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="flex-shrink-0 bg-light rounded-circle p-2 me-3">
                                                    <i class="bi bi-diagram-3" style="color: #2a6279;"></i>
                                                </div>
                                                <span class="small fw-bold text-uppercase tracking-tight" style="color:#2a6279;">Assignment Details</span>
                                            </div>

                                            <div class="mb-3 ps-3 border-start border-4 shadow-none" style="border-color: #2a6279 !important; border-radius: 0;">
                                                <label class="d-block small text-muted fw-semibold mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Selected Agent ID</label>
                                                <div id="disp_agent_id" class="fw-bold text-dark small">---</div>
                                            </div>

                                            <div class="mb-3 ps-2 border-start border-2" style="border-color: #e2e8f0 !important;">
                                                <label class="d-block small text-muted fw-semibold mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Unit Manager</label>
                                                <input type="text" name="um_name" class="form-control-plaintext py-0 fw-bold text-dark mb-0" id="disp_manager" readonly value="---"> 
                                                <span class="text-muted" style="font-size: 0.7rem;">ID: <span id="disp_um_id" class="fw-bold">---</span></span>
                                            </div>

                                            <div class="ps-2 border-start border-2" style="border-color: #e2e8f0 !important;">
                                                <label class="d-block small text-muted fw-semibold mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Broker-in-Charge</label>
                                                <input type="text" name="broker_name" class="form-control-plaintext py-0 fw-bold text-dark mb-0" id="disp_broker" readonly value="---"> 
                                                <span class="text-muted" style="font-size: 0.7rem;">ID: <span id="disp_broker_id" class="fw-bold">---</span></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="modern-field">
                                            <label class="small fw-bold text-secondary mb-1 d-block">Lot Status</label>
                                            <select class="form-select shadow-sm border-2" name="is_assumed" id="is_assumed" 
                                                    style="border-color: #f1f5f9; border-radius: 12px; height: 50px;">
                                                <option value="No">Original Sale</option>
                                                <option value="Yes">Assumed Lot</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="modern-field mb-1">
                                            <label class="small fw-bold text-secondary mb-1 d-block">Payment Type</label>
                                            <select class="form-select shadow-sm border-2" name="payment_type" id="payment_type" 
                                                    style="border-color: #f1f5f9; border-radius: 12px; height: 50px; cursor: pointer;">
                                                <option value="One-time">One-time</option>
                                                <option value="Installment">Installment</option>
                                            </select>
                                        </div>
                                    </div>
                                    <?php
                                    // Fetch distinct, active release_day values from the commission_profiles table
                                    try {
                                        $stmt = $pdo->query("SELECT DISTINCT release_day FROM commission_profiles WHERE is_active = 1 ORDER BY release_day ASC");
                                        $releaseTerms = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                    } catch (PDOException $e) {
                                        // Fallback empty array if query fails
                                        $releaseTerms = []; 
                                    }
                                    ?>
                                    <div class="col-12">
                                        <div class="col-12">
                                            <div id="onetimeFields" class="p-4 bg-light shadow-sm mb-4" style="border-left: 4px solid #3d8bc9; border-radius: 0 16px 16px 0;">
                                                <div class="row">
                                                    <div class="col-12">
                                                        <div class="modern-field">
                                                            <label class="small fw-bold text-dark mb-1 d-block">Cash Price (Discounted)</label>
                                                            <div class="input-group">
                                                                <span class="input-group-text border-2 bg-white text-muted px-3" style="border-radius: 10px 0 0 10px; border-color: #e2e8f0; font-weight: bold;">₱</span>
                                                                <input type="hidden" id="raw_cash_price_onetime" name="cash_price_value_onetime"> 
                                                                <input type="text" id="disp_cash_price_onetime" name="cash_price_onetime" class="form-control border-2 shadow-sm bg-white" readonly style="border-color: #e2e8f0; border-radius: 0 10px 10px 0; height: 48px; font-weight: 600;">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="installmentFields" class="p-4 bg-light shadow-sm mb-4" 
                                            style="display: none; border-left: 4px solid #96c93d; border-radius: 0 16px 16px 0; transition: all 0.3s ease-in-out;">
                                                <div class="row">
                                                <?php
                                                // 1. Fetch unique terms from the table
                                                $stmt = $pdo->query("SELECT DISTINCT release_day FROM commission_profiles WHERE is_active = 1");
                                                $terms = $stmt->fetchAll();
                                                ?>
                                                <div class="calculator-container">
                                                    <div class="row">
                                                        <div class="col-12 mb-4">
                                                            <div class="modern-field">
                                                                <label class="fw-bold text-uppercase d-block">Terms (Months)</label>
                                                                <select name="release_day" id="term_select" class="form-select w-100" style="height: 52px;">
                                                                    <option value="" selected disabled>Select Payment Term</option>
                                                                    <?php foreach ($terms as $row): 
                                                                        $dbValue = trim($row['release_day']);
                                                                        $upperVal = strtoupper($dbValue);
                                                                        
                                                                        // Logical Check: OTS and AT NEED are technically a 1-month term (One-time)
                                                                        if ($upperVal === "OTS" || $upperVal === "AT NEED" || $upperVal === "AT NEED BUYER") {
                                                                            $finalValue = 1;
                                                                            $displayLabel = ($upperVal === "OTS") ? "OTS" : "AT NEED BUYER";
                                                                        } else {
                                                                            $onlyNumbers = preg_replace('/[^0-9]/', '', $dbValue);
                                                                            $finalValue = !empty($onlyNumbers) ? (int)$onlyNumbers * 12 : 0;
                                                                            $displayLabel = $finalValue; // Display the clean number of months
                                                                        }
                                                                    ?>
                                                                        <option value="<?= htmlspecialchars($finalValue) ?>" data-display="<?= htmlspecialchars($displayLabel) ?>">
                                                                            <?= htmlspecialchars($displayLabel) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="col-12 mb-4">
                                                            <div class="modern-field">
                                                                <label class="fw-bold text-uppercase d-block">Start Date</label>
                                                                <input type="date" name="start_date" id="start_date" class="form-control w-100" style="height: 52px;">
                                                            </div>
                                                        </div>

                                                        <div class="col-12 mb-4">
                                                            <div class="modern-field">
                                                                <label class="fw-bold text-uppercase d-block text-muted">End Date (Automatic)</label>
                                                                <input type="date" name="end_date" id="end_date" class="form-control w-100 bg-light" style="height: 52px;" readonly>
                                                            </div>
                                                        </div>

                                                        <div class="col-12 mb-4">
                                                            <div class="modern-field">
                                                                <label id="payment_label" class="fw-bold text-uppercase d-block">Monthly Payment</label>
                                                                <div class="input-group">
                                                                    <span class="input-group-text px-3">₱</span>
                                                                    <input type="number" step="0.01" name="monthly_payment" id="monthly_payment" 
                                                                        class="form-control payment-input" placeholder="0.00" style="height: 52px;">
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="col-12">
                                                            <button type="button" onclick="resetForm()" class="btn btn-reset w-100">
                                                                <i class="fas fa-undo-alt me-2"></i> Reset Selection
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            <div id="agentDetailGroup" class="d-none h-100 animate__animated animate__fadeIn">
                                <h6 class="fw-bold mb-4 small text-uppercase tracking-wider d-flex align-items-center" style="color: #2a6279;">
                                    <i class="bi bi-diagram-3-fill me-2"></i>Sales Network
                                </h6>
                                
                                <div class="client-card p-3 rounded-4 shadow-sm" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                    <div class="mb-3">
                                        <label class="d-block small text-muted fw-semibold mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Primary Agent</label>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span id="det_agent" class="fw-bold text-dark small">---</span>
                                            <span class="badge bg-secondary text-white" style="font-size: 0.6rem;">ID: <span id="det_agent_id">---</span></span>
                                        </div>
                                    </div>
                                    <hr class="text-muted opacity-25">
                                    
                                    <div class="mb-3">
                                        <label class="d-block small text-muted fw-semibold mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Unit Manager</label>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span id="det_manager" class="fw-bold text-dark small">---</span>
                                            <span class="badge bg-secondary text-white" style="font-size: 0.6rem;">ID: <span id="det_um_id">---</span></span>
                                        </div>
                                    </div>
                                    <hr class="text-muted opacity-25">

                                    <div class="mb-1">
                                        <label class="d-block small text-muted fw-semibold mb-0" style="font-size: 0.65rem; text-transform: uppercase;">Broker-in-Charge</label>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span id="det_broker" class="fw-bold text-dark small">---</span>
                                            <span class="badge bg-secondary text-white" style="font-size: 0.6rem;">ID: <span id="det_broker_id">---</span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>

                    </div>
                </div>
                
                <div class="modal-footer bg-light border-0 px-4 py-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex gap-2">
                        <button type="button" id="btnCancelReservation" 
                                class="btn btn-outline-danger rounded-pill px-3 fw-bold d-none shadow-sm transition-all" 
                                onclick="openCancelModal()">
                            <i class="bi bi-x-circle me-1"></i> Cancel Reservation
                        </button>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" id="btnMarkAsSold" 
                                class="btn rounded-pill px-4 fw-bold d-none shadow-sm border-0 transition-all text-white"
                                style="background: linear-gradient(45deg, #96c93d, #7eb02a);">
                                <i class="bi bi-check-circle-fill me-1"></i> Mark as Sold
                        </button>

                        <button type="submit" id="submitProcess" 
                                class="btn px-4 rounded-pill fw-bold shadow-sm transition-all text-white"
                                style="background-color: #2a6279; border: none;">
                                <i class="bi bi-check2-all me-1"></i> Process Transaction
                        </button>
                    </div>
             </div>
</form>
        </div>
    </div>
</div>
<div class="modal fade" id="cancelRemarksModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pt-4 px-4 pb-2" style="background: #fff5f5;">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 bg-white rounded-circle shadow-sm d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px;">
                        <i class="bi bi-exclamation-triangle-fill text-danger fs-4"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-800 text-dark mb-0">Cancel Reservation</h5>
                        <small class="text-danger fw-semibold" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">Action Required</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="cancelReservationForm">
                <div class="modal-body p-4">
                    <div class="p-3 rounded-4 mb-4" style="background: #fdf2f2; border: 1px solid #fbd5d5;">
                        <p class="mb-0 text-dark-emphasis small">
                            Are you sure you want to cancel this reservation? By confirming, the unit will be immediately marked as <span class="badge bg-success bg-opacity-10 text-success fw-bold">Available</span> for new customers.
                        </p>
                    </div>

                    <div class="modern-field">
                        <label class="small fw-bold text-secondary mb-2 d-block">
                            <i class="bi bi-chat-left-text me-1"></i> Cancellation Remarks
                        </label>
                        <div class="position-relative">
                             <textarea 
                                class="form-control shadow-sm border-2" 
                                name="remarks" 
                                rows="4" 
                                style="border-color: #f1f5f9; border-radius: 12px; padding: 12px; resize: none;" 
                                placeholder="State the reason for cancellation (e.g., Client backed out, payment failure...)" 
                                required></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-muted border-0 shadow-sm" data-bs-dismiss="modal">
                        Discard
                    </button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm transition-all" style="background: linear-gradient(45deg, #dc3545, #b02a37);">
                        Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
   document.addEventListener('DOMContentLoaded', function() {
    const termSelect = document.getElementById('term_select');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    function calculateEndDate() {
        const termMonths = parseInt(termSelect.value);
        const startDateValue = startDateInput.value;

        if (termMonths && startDateValue) {
            let date = new Date(startDateValue);
            const originalDay = date.getDate();

            // Subtract 1 from terms because the start_date is the first month
            // Example: March 2026 + (36 - 1) months = February 2029
            date.setMonth(date.getMonth() + (termMonths - 1));

            // Snap to the last day of the month if the day overflows 
            // (e.g., Jan 31 -> Feb 28)
            if (date.getDate() !== originalDay) {
                date.setDate(0);
            }
            
            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const dd = String(date.getDate()).padStart(2, '0');
            
            endDateInput.value = `${yyyy}-${mm}-${dd}`;
        } else {
            endDateInput.value = '';
        }
    }

    termSelect.addEventListener('change', calculateEndDate);
    startDateInput.addEventListener('change', calculateEndDate);
});

// Update your resetForm function to include clearing the End Date
function resetForm() {
    document.getElementById('term_select').value = "";
    document.getElementById('start_date').value = "";
    document.getElementById('end_date').value = "";
    document.getElementById('monthly_payment').value = "";
    // Add any other fields you need to clear
}
  $(document).ready(function() {

// --- 1. TOGGLE LOGIC: NEW VS EXISTING ---
$('.customer-type-radio').on('change', function() {
    if ($(this).val() === 'new') {
        // New Client: Hide search, make fields editable
        $('#existingCustomerFields').addClass('d-none');
        $('#customerDetailGroup').addClass('d-none');
        $('#newCustomerFields').removeClass('d-none'); 
        
        resetCustomerForm(false); 
        if (typeof generateNextCustomerId === "function") generateNextCustomerId();

    } else {
        // Existing Client: Show search on top, lock input fields below
        $('#existingCustomerFields').removeClass('d-none');
        $('#newCustomerFields').removeClass('d-none');
        
        resetCustomerForm(true); // Clear inputs and make readonly until selected

        // Load customer list if dropdown is empty
        if ($('#sel_existing_customer option').length <= 1) {
            loadCustomerList();
        }
    }
});

// --- HELPER FUNCTIONS ---
function resetCustomerForm(isReadonly) {
    const textInputs = ['fname', 'mname', 'lname', 'mobile', 'email'];
    $('#new_customer_id').val('');
    $('textarea[name="address"]').val('').prop('readonly', isReadonly);
    textInputs.forEach(name => {
        $(`input[name="${name}"]`).val('').prop('readonly', isReadonly);
    });
}

function toggleInputsReadonly(status) {
    const textInputs = ['fname', 'mname', 'lname', 'mobile', 'email'];
    $('textarea[name="address"]').prop('readonly', status);
    textInputs.forEach(name => {
        $(`input[name="${name}"]`).prop('readonly', status);
    });
}

});
 // Global object to hold file data before saving
let pendingFiles = {};

/**
 * PREPARE: Called when user selects a file.
 */
function prepareImageUpload(input, productId) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        pendingFiles[productId] = file;

        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImg = document.getElementById(`img_prev_${productId}`);
            const houseIcon = document.getElementById(`icon_${productId}`);
            
            if (houseIcon) houseIcon.classList.add('d-none');
            
            previewImg.src = e.target.result;
            previewImg.classList.remove('d-none');
            
            document.getElementById(`actions_${productId}`).classList.remove('d-none');
            document.getElementById(`actions_${productId}`).classList.add('d-flex');
        };
        reader.readAsDataURL(file);
    }
}

/**
 * SAVE: Sends the file to the PHP backend.
 */
async function saveImage(productId) {
    const file = pendingFiles[productId];
    if (!file) return;

    const loader = document.getElementById(`loader_${productId}`);
    const actionButtons = document.getElementById(`actions_${productId}`);

    loader.classList.remove('d-none');
    loader.classList.add('d-flex');
    actionButtons.classList.add('d-none');

    const formData = new FormData();
    formData.append('product_photo', file);
    formData.append('product_id', productId);

    try {
        const response = await fetch('/cattleya/user/encoder/fetch/upload-photo', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            delete pendingFiles[productId];
            // Alert or Toast success here
        } else {
            alert("Error: " + result.message);
            cancelUpload(productId);
        }
    } catch (error) {
        console.error("Upload failed", error);
        cancelUpload(productId);
    } finally {
        loader.classList.add('d-none');
        loader.classList.remove('d-flex');
    }
}

/**
 * CANCEL: Resets the UI
 */
function cancelUpload(productId) {
    location.reload(); 
}
/**
 * MAXIMIZE: Opens the image in its own dedicated Modal
 */
function maximizeImage(productId) {
    // 1. Check if we are currently editing/uploading (actions visible)
    // If buttons are visible, we probably don't want to maximize.
    const actions = document.getElementById(`actions_${productId}`);
    if (actions && !actions.classList.contains('d-none')) {
        return; 
    }

    const imgElement = document.getElementById(`img_prev_${productId}`);
    
    // 2. Check if the image exists and isn't a hidden placeholder
    if (imgElement && imgElement.src && !imgElement.classList.contains('d-none') && imgElement.src !== window.location.href) {
        const modalImg = document.getElementById('maximizedImage');
        modalImg.src = imgElement.src;
        
        // 3. Trigger the dedicated image modal
        const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
        imageModal.show();
    }
}
// Initialize Bootstrap Modals
const viewModal = new bootstrap.Modal(document.getElementById('viewModal'));
const addModal = new bootstrap.Modal(document.getElementById('addModal')); 
const updateModal = new bootstrap.Modal(document.getElementById('updateStatusModal'));

async function openViewModal(productName) {
    document.getElementById('viewModalTitle').innerText = productName;
    const container = document.getElementById('inventoryContainer');
    
    // Minimalist premium layout loader with refined spacing
    container.innerHTML = `
        <div class="col-12 text-center py-5" style="min-height: 200px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
            <div class="spinner-border text-dark mb-2" style="width: 2.2rem; height: 2.2rem; border-width: 0.18rem; color: #0f172a !important;" role="status"></div>
            <div class="text-uppercase" style="font-size: 0.75rem; font-weight: 800; letter-spacing: 3px; color: #64748b; animation: pulseOpacity 1.5s infinite;">Loading Architecture Grid</div>
        </div>
        <style>
            @keyframes pulseOpacity { 0%, 100% { opacity: 0.5; } 50% { opacity: 1; } }
        </style>
    `;
    viewModal.show();

    try {
        const res = await fetch(`/cattleya/user/encoder/fetch/get-product-details?name=${encodeURIComponent(productName)}`);
        const activeLots = await res.json();
        console.log("Database Response:", activeLots);
        
        // --- INJECTED CSS: Enhanced Architectural UI design framework ---
        container.innerHTML = `
            <style>
                .lot-wrapper {
                    transition: opacity 0.3s ease, transform 0.3s ease;
                    animation: fadeInUp 0.4s ease-out forwards;
                    opacity: 0;
                }
                
                @keyframes fadeInUp {
                    from { opacity: 0; transform: translateY(10px); }
                    to { opacity: 1; transform: translateY(0); }
                }

                .premium-lot-tile {
                    transition: all 0.35s cubic-bezier(0.2, 1, 0.2, 1);
                    border-radius: 8px !important;
                    background: #ffffff;
                    position: relative;
                }
                
                .premium-lot-tile:not(.inactive-tile):hover {
                    transform: translateY(-3px) scale(1.02);
                    box-shadow: 0 8px 16px -4px rgba(15, 23, 42, 0.12), 0 4px 6px -2px rgba(15, 23, 42, 0.05) !important;
                    z-index: 2;
                }
                
                .premium-lot-tile:not(.inactive-tile):active {
                    transform: translateY(0) scale(0.98);
                }
                
                .inventory-block-card {
                    background: linear-gradient(145deg, #ffffff, #f8fafc);
                    border: 1px solid rgba(226, 232, 240, 0.8);
                    border-radius: 24px;
                    box-shadow: 0 4px 20px -10px rgba(15, 23, 42, 0.05);
                    transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s ease;
                }
                
                .inventory-block-card:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.08);
                    border-color: #cbd5e1;
                }
                
                /* Detached status dot styles with subtle pulse */
                .status-dot {
                    width: 6px;
                    height: 6px;
                    border-radius: 50%;
                    display: inline-block;
                    animation: statusPulse 2.5s infinite;
                }
                
                @keyframes statusPulse {
                    0% { transform: scale(0.95); opacity: 0.85; }
                    50% { transform: scale(1.15); opacity: 1; }
                    100% { transform: scale(0.95); opacity: 0.85; }
                }
                
                .dot-available { background-color: #10b981; box-shadow: 0 0 8px rgba(16, 185, 129, 0.7); }
                .dot-sold { background-color: #f43f5e; box-shadow: 0 0 8px rgba(244, 63, 94, 0.7); }
                .dot-reserved { background-color: #4f46e5; box-shadow: 0 0 8px rgba(79, 70, 229, 0.7); }
                .dot-inactive { background-color: #94a3b8; animation: none; }
                
                .lot-label {
                    font-size: 0.85rem;
                    font-weight: 800;
                    color: #0f172a;
                    letter-spacing: -0.2px;
                }
                
                /* Micro-typography for detached status */
                .status-text-micro {
                    font-size: 9px;
                    font-weight: 800;
                    text-transform: uppercase;
                    letter-spacing: 1.2px;
                    color: #64748b;
                }
                
                /* Enhanced Search Bar */
                .search-container input {
                    transition: all 0.3s ease;
                }
                
                .search-container input:focus {
                    box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.05);
                    border-color: #94a3b8 !important;
                    background: #ffffff !important;
                    outline: none;
                }
                
                /* Class for performant searching */
                .hidden-element {
                    display: none !important;
                }
            </style>
        `;
        
        // --- SEPARATED GLOBAL BLOCK SEARCH ---
        const globalSearchContainer = document.createElement("div");
        globalSearchContainer.className = "col-12 mb-2";
        globalSearchContainer.innerHTML = `
            <div class="position-relative search-container mx-auto" style="max-width: 450px;">
                <input type="text" id="globalBlockSearch" class="form-control shadow-sm" placeholder="Search by Block Number..." style="border-radius: 14px; font-size: 0.95rem; padding: 14px 14px 14px 44px; background: #ffffff; border: 1px solid #cbd5e1; font-weight: 600; color: #0f172a;">
                <i class="bi bi-building position-absolute" style="left: 18px; top: 50%; transform: translateY(-50%); font-size: 1.1rem; color: #64748b;"></i>
            </div>
        `;
        container.appendChild(globalSearchContainer);

        const groups = activeLots.reduce((acc, lot) => {
            (acc[lot.block_number] = acc[lot.block_number] || []).push(lot);
            return acc;
        }, {});

        Object.keys(groups).forEach((block, index) => {
            const col = document.createElement("div");
            // Added data-block-number and class for the global search filter to target
            col.className = "col-12 mb-4 block-card-wrapper";
            col.setAttribute("data-block-number", block);
            
            col.innerHTML = `
                <div class="p-4 inventory-block-card" style="animation: fadeInUp 0.5s ease-out ${index * 0.1}s both;">
                    <div class="d-flex align-items-center justify-content-between mb-2 pb-3 border-bottom border-light flex-wrap gap-3">
                        <div class="d-flex align-items-center">
                            <span class="fw-bold text-uppercase" style="color: #0f172a; letter-spacing: 1.5px; font-size: 0.9rem;">Block ${block}</span>
                            <span class="badge ms-3 px-2 py-1 shadow-sm" style="font-size: 10px; font-weight: 700; background: #ffffff; color: #475569; border: 1px solid #e2e8f0; border-radius: 8px;">${groups[block].length} Units</span>
                        </div>
                        
                        <div class="position-relative search-container" style="flex: 1; max-width: 280px;">
                            <input type="text" class="form-control form-control-sm block-lot-search shadow-sm" data-block="${block}" placeholder="Search Lot No..." style="border-radius: 10px; font-size: 0.85rem; padding-left: 36px; padding-top: 8px; padding-bottom: 8px; background: #f8fafc; border: 1px solid #cbd5e1; font-weight: 500;">
                            <i class="bi bi-search position-absolute" style="left: 14px; top: 50%; transform: translateY(-50%); font-size: 0.85rem; color: #64748b;"></i>
                        </div>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-3" id="block-container-${block}">
                        ${groups[block].map((l, lotIndex) => {
                            const isInactive = l.status === 'inactive';
                            const clickEvent = `onclick="triggerStatusUpdate('${l.product_id}', '${productName}', '${l.block_number}', '${l.lot_number}', '${l.niche_type}', '${l.block_description}', '${l.lot_price}', '${l.tcp}', '${l.cash_price}', '${l.status}')"`;
                            
                            const cursorStyle = "cursor: pointer;";
                            const titleAttr = isInactive ? 'title="Unit Unavailable"' : "";

                            let bgStyle = '';
                            let dotClass = '';
                            
                            if (l.status === 'sold') {
                                bgStyle = 'border: 1px solid #fecdd3; background: linear-gradient(135deg, #ffffff, #fff1f2);';
                                dotClass = 'dot-sold';
                            } else if (l.status === 'available') {
                                bgStyle = 'border: 1px solid #a7f3d0; background: linear-gradient(135deg, #ffffff, #ecfdf5);';
                                dotClass = 'dot-available';
                            } else if (l.status === 'reserved') {
                                bgStyle = 'border: 1px solid #c7d2fe; background: linear-gradient(135deg, #ffffff, #eef2ff);';
                                dotClass = 'dot-reserved';
                            } else if (l.status === 'inactive') {
                                bgStyle = 'border: 1px dashed #cbd5e1; background: #f1f5f9;';
                                dotClass = 'dot-inactive';
                            }
                            const statusColorStyle = bgStyle;

                            return `
                                <div class="lot-wrapper lot-item-${block}" data-lot="${l.lot_number}" style="width: max-content; flex: 0 0 auto; display: flex; flex-direction: column; align-items: center; gap: 6px; animation-delay: ${(lotIndex * 0.02)}s;">
                                    
                                    <div class="d-flex align-items-center" style="line-height: 1; opacity: 0.9;">
                                        <span class="status-dot ${dotClass} me-2"></span>
                                        <span class="status-text-micro">${l.status}</span>
                                        ${isInactive ? '<span style="font-size: 8px; font-weight: 800; color: #94a3b8; margin-left: 4px;">(HOLD)</span>' : ''}
                                    </div>
                                    
                                    <div class="lot-tile premium-lot-tile ${isInactive ? 'inactive-tile' : ''}" 
                                        ${clickEvent}
                                        ${titleAttr}
                                        style="${statusColorStyle} ${cursorStyle} padding: 4px 6px; width: max-content; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                        
                                        <span class="lot-label">Lot ${l.lot_number}</span>
                                        
                                    </div>
                                    
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>`;
            container.appendChild(col);
        });

        // --- 1. GLOBAL BLOCK SEARCH LOGIC ---
        const globalBlockSearch = document.getElementById('globalBlockSearch');
        if (globalBlockSearch) {
            globalBlockSearch.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase().trim();
                const blockWrappers = document.querySelectorAll('.block-card-wrapper');
                
                blockWrappers.forEach(wrapper => {
                    const blockNumber = wrapper.getAttribute('data-block-number').toLowerCase();
                    // Show or hide the entire block card based on the search
                    if (blockNumber.includes(query) || query === '') {
                        wrapper.classList.remove('hidden-element');
                    } else {
                        wrapper.classList.add('hidden-element');
                    }
                });
            });
        }

        // --- 2. LOCAL LOT SEARCH LOGIC (Reverted to specific lot targeting) ---
        const searchInputs = document.querySelectorAll('.block-lot-search');
        searchInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                const query = e.target.value.toLowerCase().trim();
                const targetBlock = e.target.getAttribute('data-block');
                const tiles = document.querySelectorAll(`.lot-item-${targetBlock}`);
                
                for (let i = 0; i < tiles.length; i++) {
                    const tile = tiles[i];
                    const lotNumber = tile.getAttribute('data-lot').toLowerCase();
                    
                    if (lotNumber.includes(query)) {
                        tile.classList.remove('hidden-element');
                    } else {
                        tile.classList.add('hidden-element');
                    }
                }
            });
        });

    } catch (err) { 
        container.innerHTML = `
            <div class="alert text-center py-4 border-0 shadow-sm" style="border-radius: 16px; background: #fef2f2; color: #b91c1c; font-size: 0.9rem; font-weight: 600;">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <span style="letter-spacing: 0.3px;">The inventory dashboard layout could not be initialized smoothly.</span>
            </div>
        `; 
    }
}
/**
 * Fetches the next Customer ID from the server and updates the input
 */
async function generateNextCustomerId() {
    const custInput = document.getElementById('new_customer_id');
    if (!custInput) return;

    custInput.value = "Generating...";

    try {
        // FIXED URL: Added underscores and .php extension
        const response = await fetch('/cattleya/user/encoder/fetch/get-next-customer-id'); 
        
        if (!response.ok) {
            throw new Error(`Server returned ${response.status} ${response.statusText}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            custInput.value = result.next_id;
        } else {
            // Check your F12 Console to see the error from result.error
            console.error("PHP Script Error:", result.error);
            custInput.value = "CUST-2026-FAILED";
        }
    } catch (e) {
        console.error("JavaScript Fetch Error:", e);
        custInput.value = "CUST-2026-ERROR";
    }
}


// Your existing status radio button logic
document.addEventListener('change', function (e) {
    if (e.target.classList.contains('status-radio')) {
        const submitBtn = document.getElementById('submitProcess');
        const form = document.getElementById('updateStatusForm');
        if (!form) return;
        
        const originalStatus = form.dataset.originalStatus;
        const currentSelectedStatus = e.target.value;

        if (submitBtn) {
            if (currentSelectedStatus !== originalStatus) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50');
            }
        }
    }
});
/**
 * 2. TRIGGER MODAL
 */
async function triggerStatusUpdate(id, prodName, block, lot, niche, block_description, lot_price, tcp, cash_price, currentStatus) {
    const status = currentStatus ? currentStatus.toLowerCase().trim() : '';
    const form = document.getElementById('updateStatusForm');
    const submitBtn = document.getElementById('submitProcess');
    const cancelBtn = document.getElementById('btnCancelReservation');
    const markAsSoldBtn = document.getElementById('btnMarkAsSold');
    const activeSalesIdInput = document.getElementById('active_sales_id');
    const availableRadio = document.querySelector('.status-radio[value="available"]');
    const soldRadio = document.querySelector('.status-radio[value="sold"]');
    const inactiveRadio = document.querySelector('.status-radio[value="inactive"]');
    const reservedRadio = document.querySelector('.status-radio[value="reserved"]');
    const agentDetailGroup = document.getElementById('agentDetailGroup');
    
    // Select price-related elements
    // Main Display Elements
    const dispPriceEl = document.getElementById('disp_price'); 
    const dispCashPriceMain = document.getElementById('disp_cash_price');
    const rawCashPriceMain = document.getElementById('raw_cash_price');

    // One-time Field Container Elements
    const rawCashPriceOnetime = document.getElementById('raw_cash_price_onetime');
    const dispCashPriceOnetime = document.getElementById('disp_cash_price_onetime');

    if (agentDetailGroup) {
        agentDetailGroup.classList.add('d-none');
        agentDetailGroup.style.display = 'none'; 
    }

    if (form) form.reset();
    $('#typeNew').prop('checked', true).trigger('change');
    if (activeSalesIdInput) activeSalesIdInput.value = ""; 

    const setVal = (id, val) => { const el = document.getElementById(id); if(el) el.value = val; };
    setVal('disp_manager_input', '---');
    setVal('disp_broker_input', '---');

    setVal('update_product_id', id);
    setVal('disp_product_name', prodName);
    setVal('disp_block_no', block);
    setVal('disp_lot_no', lot);
    setVal('disp_niche', niche);
    setVal('disp_desc', block_description);

    // --- PRICING LOGIC ---
    
    const phpCurrency = new Intl.NumberFormat('en-PH', { 
        style: 'currency', 
        currency: 'PHP',
        minimumFractionDigits: 2 
    });

    // 1. Total Contract Price (TCP)
    const tcpValue = parseFloat(tcp) || 0;
    setVal('raw_price', tcpValue.toFixed(2)); 
    if (dispPriceEl) {
        const formattedTCP = phpCurrency.format(tcpValue);
        dispPriceEl.tagName === 'INPUT' ? dispPriceEl.value = formattedTCP : dispPriceEl.innerText = formattedTCP;
    }

    // 2. Cash Price Processing
    const cashValue = parseFloat(cash_price) || 0;
    const formattedCash = phpCurrency.format(cashValue);

    // Update Main Display
    if (rawCashPriceMain) rawCashPriceMain.value = cashValue.toFixed(2);
    if (dispCashPriceMain) {
        dispCashPriceMain.tagName === 'INPUT' ? dispCashPriceMain.value = formattedCash : dispCashPriceMain.innerText = formattedCash;
    }

    // Update One-time Fields (Inside the blue-bordered box)
    if (rawCashPriceOnetime) rawCashPriceOnetime.value = cashValue.toFixed(2);
    if (dispCashPriceOnetime) {
        dispCashPriceOnetime.value = formattedCash;
    }

    // --- END OF PRICING ---

    const initialStatus = currentStatus ? currentStatus.toLowerCase().trim() : '';
    if (form) form.dataset.originalStatus = initialStatus; 

    if (submitBtn) {
        submitBtn.disabled = true;
    }
    const radio = document.querySelector(`.status-radio[value="${status}"]`);
    if (radio) radio.checked = true;

    const inputGroups = ['customerInputGroup', 'agentInputGroup', 'transactionInputGroup']
                         .map(id => document.getElementById(id));
    const detailGroups = ['customerDetailGroup', 'agentDetailGroup', 'transactionDetailGroup']
                         .map(id => document.getElementById(id));

    const isOccupied = (status === 'reserved' || status === 'sold');
    const isInactive = (status === 'inactive');
    
    if (!isOccupied) {
        $('#typeNew').prop('checked', true).trigger('change');
        if (typeof generateNextCustomerId === "function") await generateNextCustomerId(); 
    }

    if (isOccupied) {
        inputGroups.forEach(g => g?.classList.add('d-none'));
        detailGroups.forEach(g => g?.classList.remove('d-none')); 
        if (agentDetailGroup) agentDetailGroup.style.display = '';

        if (status === 'reserved') {
            [availableRadio, soldRadio, inactiveRadio].forEach(el => {
                if (el) {
                    el.disabled = true;
                    el.parentElement.classList.add('text-muted', 'opacity-50');
                }
            });
            if (submitBtn) submitBtn.style.display = 'none';
            if (cancelBtn) cancelBtn.classList.remove('d-none');
            if (markAsSoldBtn) {
                markAsSoldBtn.classList.remove('d-none');
                markAsSoldBtn.style.display = 'block';
            }
        } else if (status === 'sold') {
            [availableRadio, reservedRadio, inactiveRadio].forEach(el => {
                if (el) {
                    el.disabled = true;
                    el.parentElement.classList.add('text-muted', 'opacity-50');
                }
            });
            if (submitBtn) submitBtn.style.display = 'none';
            cancelBtn?.classList.add('d-none');
            markAsSoldBtn?.classList.add('d-none');
        }

        try {
            const url = `/cattleya/user/encoder/fetch/get-sale-details?block=${block}&lot=${lot}&product=${encodeURIComponent(prodName)}`;
            const response = await fetch(url);
            const result = await response.json();

            if (result.success) {
                const s = result.data;
                const setTxt = (id, val) => { 
                    const el = document.getElementById(id); 
                    if(el) el.innerText = val || 'N/A'; 
                };

                setTxt('det_customer_name', s.customer_fullname);
                setTxt('det_customer_id', s.customer_id);
                setTxt('det_mobile', s.mobile_number);
                setTxt('det_agent', s.agent_fullname);
                setTxt('det_agent_id', s.agent_id);
                setTxt('det_manager', s.um_fullname);
                setTxt('det_um_id', s.um_id);
                setTxt('det_broker', s.broker_fullname);
                setTxt('det_broker_id', s.broker_id);
                
                const saleTCP = parseFloat(s.tcp) || 0;
                setVal('raw_price', saleTCP.toFixed(2)); 
                if (dispPriceEl) {
                    const formattedSaleTCP = phpCurrency.format(saleTCP);
                    dispPriceEl.tagName === 'INPUT' ? dispPriceEl.value = formattedSaleTCP : dispPriceEl.innerText = formattedTCP;
                }
                
                const assumedValue = s.lot_assume_type;
                setTxt('det_is_assumed', assumedValue === 'Yes' ? 'Assumed Lot' : 'Original Sale');
                setTxt('det_payment_type', s.payment_method);

                // --- ADDED: LABEL CHANGING LOGIC FOR FETCHED DETAILS ---
                const lblTerms = document.getElementById('lbl_terms');
                const lblStartDate = document.getElementById('lbl_start_date');
                const lblMonthly = document.getElementById('lbl_monthly');

                if (s.payment_method === 'One-time') {
                    if (lblTerms) lblTerms.innerText = 'Term(s)';
                    if (lblStartDate) lblStartDate.innerText = 'Due Date';
                    if (lblMonthly) lblMonthly.innerText = 'Cash Amount';
                    setTxt('det_installment_terms', '1 Month'); // As per your requirement
                } else {
                    if (lblTerms) lblTerms.innerText = 'Installment Terms';
                    if (lblStartDate) lblStartDate.innerText = 'Installment Start Date';
                    if (lblMonthly) lblMonthly.innerText = 'Installment Monthly Payment';
                    setTxt('det_installment_terms', s.installment_terms ? s.installment_terms + ' Months' : '---');
                }
                // --- END LABEL CHANGING ---

                setTxt('det_installment_startDate', s.installment_start_date ? new Date(s.installment_start_date).toLocaleDateString('en-US', { month: 'long', day: '2-digit', year: 'numeric' }) : '---');
                setTxt('det_installment_endDate', s.installment_end_date ? new Date(s.installment_end_date).toLocaleDateString('en-US', { month: 'long', day: '2-digit', year: 'numeric' }) : '---');


                const monthlyVal = s.installment_monthly_payment || 0;
                const formattedMonthly = parseFloat(monthlyVal).toLocaleString(undefined, { 
                    minimumFractionDigits: 2, 
                    maximumFractionDigits: 2 
                });
                setTxt('det_installment_monthlyPayment', monthlyVal > 0 ? formattedMonthly : '---');

                if(document.getElementById('det_date') && s.created_at) {
                    document.getElementById('det_date').innerText = new Date(s.created_at).toLocaleDateString();
                }
                if (activeSalesIdInput) activeSalesIdInput.value = s.sale_id;
            }
        } catch (e) { 
            console.error("Fetch error:", e); 
        }
    } else {
        [availableRadio, soldRadio, inactiveRadio, reservedRadio].forEach(el => {
            if (el) {
                el.disabled = false;
                el.parentElement.classList.remove('text-muted', 'opacity-50');
            }
        });

        inputGroups.forEach(g => {
            if (g) {
                g.classList.remove('d-none');
                g.querySelectorAll('input, select, textarea').forEach(el => el.disabled = isInactive);
            }
        });

        detailGroups.forEach(g => g?.classList.add('d-none'));
        if (agentDetailGroup) {
            agentDetailGroup.classList.add('d-none');
            agentDetailGroup.style.display = 'none'; 
        }

        if (submitBtn) {
            submitBtn.style.display = 'block';
            submitBtn.innerHTML = isInactive 
                ? '<i class="bi bi-save me-1"></i> Update Status' 
                : '<i class="bi bi-check2-all me-1"></i> Process Transaction';
        }
        cancelBtn?.classList.add('d-none');
        markAsSoldBtn?.classList.add('d-none');
    }

    if (typeof populateLotsAndReflect === "function") await populateLotsAndReflect(id, block, lot);
    if (typeof updateModal !== 'undefined') updateModal.show();
}

document.addEventListener('DOMContentLoaded', function() {
    const paymentType = document.getElementById('payment_type');
    const installmentFields = document.getElementById('installmentFields');
    const onetimeFields = document.getElementById('onetimeFields'); 

    // --- ADDED: LABEL ELEMENTS FOR INPUT FORM ---
    const lblTerms = document.getElementById('lbl_terms');
    const lblStartDate = document.getElementById('lbl_start_date');
    const lblMonthly = document.getElementById('lbl_monthly');

    function togglePaymentDisplay() {
        const isInstallment = paymentType.value === 'Installment';
        
        // 1. Smoothly toggle visibility and add a 'focus' class to the active section
        if (isInstallment) {
            $(installmentFields).fadeIn(300).addClass('payment-active');
            $(onetimeFields).hide().removeClass('payment-active');
            
            // Update Labels with icons (optional via innerHTML)
            if (lblTerms) lblTerms.innerHTML = '<i class="bi bi-calendar3 me-1"></i> Installment Terms';
            if (lblStartDate) lblStartDate.innerHTML = '<i class="bi bi-clock-history me-1"></i> Installment Start Date';
            if (lblMonthly) lblMonthly.innerHTML = '<i class="bi bi-cash-stack me-1"></i> Monthly Payment';
        } else {
            $(onetimeFields).fadeIn(300).addClass('payment-active');
            $(installmentFields).hide().removeClass('payment-active');

            if (lblTerms) lblTerms.innerHTML = '<i class="bi bi-check-circle me-1"></i> Term(s)';
            if (lblStartDate) lblStartDate.innerHTML = '<i class="bi bi-calendar-check me-1"></i> Due Date';
            if (lblMonthly) lblMonthly.innerHTML = '<i class="bi bi-wallet2 me-1"></i> Cash Amount (Fully Paid)';
        }
    }

    paymentType.addEventListener('change', togglePaymentDisplay);
    togglePaymentDisplay();
});

/**
 * LIVE CHANGE LISTENER
 * Updates input state immediately when user clicks radio buttons inside the modal
 */
document.querySelectorAll('.status-radio').forEach(radio => {
    radio.addEventListener('change', function() {
        const selectedStatus = this.value;
        const inputGroups = [
            document.getElementById('customerInputGroup'),
            document.getElementById('agentInputGroup'),
            document.getElementById('transactionInputGroup')
        ];

        if (selectedStatus === 'inactive') {
            // Disable everything
            inputGroups.forEach(group => {
                group?.querySelectorAll('input, select, textarea').forEach(el => el.disabled = true);
            });
        } else {
            // Enable everything for Reserved, Sold, or Available
            inputGroups.forEach(group => {
                group?.querySelectorAll('input, select, textarea').forEach(el => el.disabled = false);
            });
        }
    });
});
document.getElementById('btnMarkAsSold').addEventListener('click', async function() {
    const prodName = document.getElementById('disp_product_name').value;
    const block = document.getElementById('disp_block_no').value;
    const lot = document.getElementById('disp_lot_no').value;
    const salesId = document.getElementById('active_sales_id').value;
    const selectedStatus = 'SOLD';

    // 1. Modern Confirmation Modal
    const confirm = await Swal.fire({
        title: '<span style="font-weight:700;">Confirm Status Change</span>',
        html: `Are you sure you want to mark <b>${prodName} (Block ${block}, Lot ${lot})</b> as <span class="badge bg-success">SOLD</span>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Mark as Sold',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#198754', // Success Green
        cancelButtonColor: '#6e7881',
        reverseButtons: true, // Puts confirm button on the right (standard UX)
        borderRadius: '15px',
        customClass: {
            popup: 'shadow-lg border-0',
            confirmButton: 'rounded-pill px-4 fw-bold',
            cancelButton: 'rounded-pill px-4'
        },
        showClass: { popup: 'animate__animated animate__fadeInDown' },
        hideClass: { popup: 'animate__animated animate__fadeOutUp' }
    });

    if (confirm.isConfirmed) {
        // Show loading state to prevent double-clicks
        Swal.fire({
            title: 'Processing...',
            text: 'Please wait while we update the record.',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        try {
            const formData = new FormData();
            formData.append('product_name', prodName);
            formData.append('block_number', block);
            formData.append('lot_number', lot);
            formData.append('status', selectedStatus);
            formData.append('sales_id', salesId);

            const res = await fetch("/cattleya/user/encoder/fetch/update-sale-status", {
                method: "POST",
                body: formData
            });

            const data = await res.json();
            
            if (data.success) {
                // 2. SUCCESS RELOAD LOGIC
                // We use await here so the page only reloads AFTER they click "OK"
                await Swal.fire({
                    icon: 'success',
                    title: 'Record Updated!',
                    text: `Unit status has been successfully changed to ${selectedStatus}.`,
                    confirmButtonColor: '#0d6efd',
                    borderRadius: '15px',
                    confirmButtonText: 'Great!'
                });

                // RELOAD THE PAGE
                location.reload(); 
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Update Failed',
                    text: data.message || 'Something went wrong.',
                    borderRadius: '15px'
                });
            }
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Connection Error',
                text: 'Could not reach the server. Please check your internet.',
                borderRadius: '15px'
            });
        }
    }
});
const cancelModal = new bootstrap.Modal(document.getElementById('cancelRemarksModal'));

function openCancelModal() {
    cancelModal.show();
}


document.getElementById('cancelReservationForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // 1. UI Feedback: Disable submit button to prevent double-clicks
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Processing...';

    // 2. Collect identifiers and form data
    const prodName = document.getElementById('disp_product_name').value;
    const block = document.getElementById('disp_block_no').value;
    const lot = document.getElementById('disp_lot_no').value;
    const salesId = document.getElementById('active_sales_id').value; 
    const remarks = this.remarks.value;

    const formData = new FormData();
    formData.append('sales_id', salesId);
    formData.append('product_name', prodName);
    formData.append('block_number', block);
    formData.append('lot_number', lot);
    formData.append('remarks', remarks);

    try {
        // 3. Perform the fetch (Added .php extension for accuracy)
        const res = await fetch("/cattleya/user/encoder/fetch/cancel-reservation", {
            method: "POST",
            body: formData 
        });

        // 4. Handle potential server errors before parsing JSON
        if (!res.ok) throw new Error(`Server returned status ${res.status}`);

        const data = await res.json();

        if (data.success) {
            // Hide Modals immediately
            if (typeof cancelModal !== 'undefined') cancelModal.hide();
            if (typeof updateModal !== 'undefined') updateModal.hide();
            
            // 5. Success Notification with Page Reload
            Swal.fire({
                title: 'Cancelled',
                text: 'Reservation has been successfully removed.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                // Refresh the whole page to sync all UI states
                location.reload(); 
            });

        } else {
            throw new Error(data.message || 'Operation failed');
        }

    } catch (err) {
        console.error("Cancellation Error:", err);
        Swal.fire({
            title: 'Error',
            text: err.message || 'Could not connect to the server.',
            icon: 'error'
        });
    } finally {
        // 6. Reset button state if it didn't reload (e.g., on error)
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    }
});
    /**
     * DATA FETCHING & DROPDOWN POPULATION
     */
    async function populateBlocks(productId, selectedBlock = null) {
        const blockSel = document.getElementById('sel_block');
        if (!productId) return;
        try {
            const res = await fetch(`/cattleya/user/encoder/fetch/get-blocks?product_id=${productId}`);
            const blocks = await res.json();
            blockSel.innerHTML = '<option value="">Select Block</option>' + 
                blocks.map(b => `<option value="${b}" ${b == selectedBlock ? 'selected' : ''}>${b}</option>`).join('');
        } catch (e) { console.error("Block fetch error", e); }
    }

/**
 * REVISED UNIT DISPLAY
 * Handles both visual formatting and raw value preservation
 */
function updateUnitDisplay(option) {
    const n = document.getElementById('disp_niche');
    const d = document.getElementById('disp_desc');
    const p = document.getElementById('disp_price');   // The Display (Formatted)
    const r = document.getElementById('raw_price');    // The Hidden (Database)
    const h = document.getElementById('update_product_id');

    // Reset if no option is selected
    if (!option || !option.value) {
        if(n) n.value = "-"; 
        if(d) d.value = "Select a lot to see details..."; 
        if(p) p.value = "₱ 0.00"; 
        if(r) r.value = "0.00";
        if(h) h.value = "";
        return;
    }

    // Update Text Fields
    n.value = option.getAttribute('data-niche') || "Type";
    d.value = option.getAttribute('data-desc') || "No description available";
    h.value = option.getAttribute('data-id') || "";

    // Handle Pricing Logic
    const price = parseFloat(option.getAttribute('data-price')) || 0;

    // 1. Update the Hidden Raw Value (Crucial for saving to DB)
    if(r) r.value = price.toFixed(2);

    // 2. Update the Visible Display (Formatted for User)
    if(p) {
        p.value = new Intl.NumberFormat('en-PH', { 
            style: 'currency', 
            currency: 'PHP',
            minimumFractionDigits: 2 
        }).format(price);
    }
}
document.getElementById('sel_agent')?.addEventListener('change', function() {
    // Get the selected option
    const selected = this.options[this.selectedIndex];
    
    // 1. Extract values from data attributes
    // Use '---' as fallback so the input isn't just empty
    const agentName  = selected.getAttribute('data-name') || '';
    const agentCode  = selected.getAttribute('data-agent-id') || '---';
    const umName     = selected.getAttribute('data-um') || '---';
    const umCode     = selected.getAttribute('data-um-id') || '---';
    const brokerName = selected.getAttribute('data-broker') || '---';
    const brokerCode = selected.getAttribute('data-broker-id') || '---';
    
    // 2. Reflect the Full Name into the input field
    const nameInput = document.getElementById('hidden_agent_name');
    if (nameInput) {
        nameInput.value = agentName;
    }

    // 3. Update other UI Display Labels
    if(document.getElementById('disp_agent_id'))   document.getElementById('disp_agent_id').innerText = agentCode;
    if(document.getElementById('disp_manager'))    document.getElementById('disp_manager').value      = umName;
    if(document.getElementById('disp_um_id'))      document.getElementById('disp_um_id').innerText    = umCode;
    if(document.getElementById('disp_broker'))     document.getElementById('disp_broker').value       = brokerName;
    if(document.getElementById('disp_broker_id'))  document.getElementById('disp_broker_id').innerText = brokerCode;

    // 4. Update Hidden IDs for POST (if they exist in your DOM)
    if(document.getElementById('hidden_agent_id'))  document.getElementById('hidden_agent_id').value  = agentCode;
    if(document.getElementById('hidden_um_name'))   document.getElementById('hidden_um_name').value   = umName;
    if(document.getElementById('hidden_um_id'))     document.getElementById('hidden_um_id').value     = umCode;
    if(document.getElementById('hidden_broker_name')) document.getElementById('hidden_broker_name').value = brokerName;
    if(document.getElementById('hidden_broker_id')) document.getElementById('hidden_broker_id').value = brokerCode;
});
// Submit Logic
document.getElementById('updateStatusForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    try {
        const submitBtn = document.getElementById('submitProcess');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';

        const res = await fetch("/cattleya/user/encoder/fetch/add-sales", {
            method: "POST",
            body: formData
        });
        const data = await res.json();
        
        if (data.success) {
            Swal.fire('Success', 'Transaction saved!', 'success').then(() => location.reload());
        } else {
            throw new Error(data.message);
        }
    } catch (err) {
        Swal.fire('Error', err.message, 'error');
        document.getElementById('submitProcess').disabled = false;
        document.getElementById('submitProcess').innerHTML = 'Process Transaction';
    }
});
  /**
 * 1. TRIGGER ADD MODAL
 */
async function openAddForm(productName) {
    const form = document.getElementById('addLotForm');
    if (form) {
        form.reset();
        // Clear previous suggestions data
        delete form.dataset.suggestions;
    }

    const displaySpan = document.getElementById('displayProductName');
    const hiddenInput = document.getElementById('formProductName');
    if (displaySpan) displaySpan.innerText = productName;
    if (hiddenInput) hiddenInput.value = productName;

    // Initial Fetch: Get Blocks, All Lots (for safety), and Niches
    try {
        const response = await fetch(`/cattleya/user/encoder/fetch/get-suggestions?product_name=${encodeURIComponent(productName)}`);
        const data = await response.json();

        if (data.success) {
            updateDatalist('block_list', data.blocks);
            updateDatalist('lot_list', data.lots); // Initial load of all lots
            updateDatalist('niche_list', data.niches);
            
            // Store financial info by block for auto-filling
            form.dataset.suggestions = JSON.stringify(data.financialsByBlock || {});
        }
    } catch (error) {
        console.error("Error fetching initial suggestions:", error);
    }

    addModal.show();
}

/**
 * 2. HELPER: POPULATE DATALISTS
 */
function updateDatalist(id, values) {
    const list = document.getElementById(id);
    if (!list) return;
    list.innerHTML = "";
    if (Array.isArray(values) && values.length > 0) {
        values.forEach(val => {
            if (val) {
                const option = document.createElement('option');
                option.value = val;
                list.appendChild(option);
            }
        });
    }
}

/**
 * 3. LIVE CALCULATOR & DYNAMIC FILTERING
 */
const addLotForm = document.getElementById('addLotForm');

addLotForm.addEventListener('input', async function(e) {
    const target = e.target;

    // A. Financial Calculation
    const price = parseFloat(this.lot_price.value) || 0;
    const vat = parseFloat(this.vat.value) || 0;
    const careFund = parseFloat(this.care_fund.value) || 0;
    const marketing = parseFloat(this.marketing_budget.value) || 0;
    
    const total = price + vat + careFund + marketing;
    this.tcp.value = total.toFixed(2);

    // B. Handle Inactive Status Toggle
    if (target.name === 'status') {
        const isInactive = target.value === 'inactive';
        const fields = ['block_number', 'lot_number', 'niche_type', 'block_description', 'lot_price', 'vat', 'care_fund', 'marketing_budget'];
        fields.forEach(name => {
            if(this[name]) this[name].disabled = isInactive;
        });
    }

    // C. Dynamic Lot Filtering & Financial Auto-fill when Block changes
    if (target.name === 'block_number') {
        const blockVal = target.value;
        const productName = this.product_name.value;

        // 1. Filter Lot Numbers based on Block
        if (blockVal.trim() !== "") {
            try {
                const res = await fetch(`/cattleya/user/encoder/fetch/get-suggestions?product_name=${encodeURIComponent(productName)}&block_number=${encodeURIComponent(blockVal)}`);
                const data = await res.json();
                if (data.success) {
                    updateDatalist('lot_list', data.lots);
                }
            } catch (err) { console.error("Filtering error:", err); }
        }

        // 2. Auto-fill Financials from stored data
        if (this.dataset.suggestions) {
            const suggestions = JSON.parse(this.dataset.suggestions);
            if (suggestions[blockVal]) {
                const s = suggestions[blockVal];
                this.lot_price.value = s.lot_price || 0;
                this.vat.value = s.vat || 0;
                this.care_fund.value = s.care_fund || 0;
                this.marketing_budget.value = s.marketing_budget || 0;
                // Re-trigger calculation
                this.dispatchEvent(new Event('input'));
            }
        }
    }
});
/**
 * 4. FORM SUBMISSION
 */
addLotForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalContent = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Registering...`;

    const formData = new FormData(this);

    try {
        const response = await fetch('/cattleya/user/encoder/fetch/save-lot', { 
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            // Get the modal instance to hide it properly
            const modalElement = document.getElementById('addModal');
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) modalInstance.hide();

            // Modernized Success Modal
            Swal.fire({
                icon: 'success',
                title: '<span class="fw-800" style="color: #0f172a;">Asset Registered</span>',
                text: 'The unit was successfully added. Refreshing inventory...',
                timer: 1800,
                timerProgressBar: true,
                showConfirmButton: false,
                background: '#ffffff',
                iconColor: '#10b981',
                padding: '2rem',
                customClass: {
                    popup: 'premium-modal-radius'
                },
                // Triggered when the alert closes (either via timer or manual close)
                willClose: () => {
                    location.reload();
                }
            });
            
        } else {
            // Modernized Error Modal
            Swal.fire({
                icon: 'error',
                title: '<span class="fw-800">Submission Failed</span>',
                text: result.message || 'Failed to save asset',
                confirmButtonColor: '#673de6',
                confirmButtonText: 'Try Again',
                background: '#ffffff',
                padding: '2rem',
                customClass: {
                    popup: 'premium-modal-radius',
                    confirmButton: 'rounded-3 px-4 py-2 fw-bold'
                }
            });
        }
    } catch (error) {
        console.error("Submission error:", error);
        Swal.fire({
            icon: 'warning',
            title: '<span class="fw-800">Connection Error</span>',
            text: 'Could not connect to the server. Please check your network.',
            confirmButtonColor: '#673de6',
            customClass: {
                popup: 'premium-modal-radius'
            }
        });
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalContent;
    }
});
// 1. Toggle Logic: New vs Existing
document.querySelectorAll('.customer-type-radio').forEach(radio => {
    radio.addEventListener('change', function() {
        const newFields = document.getElementById('newCustomerFields');
        const existingFields = document.getElementById('existingCustomerFields');
        const summaryBox = document.getElementById('customerDetailGroup');

        if (this.value === 'existing') {
            existingFields.classList.remove('d-none');
            // We keep newFields visible but we will fill them automatically
            newFields.classList.remove('d-none'); 
            loadCustomerList();
        } else {
            existingFields.classList.add('d-none');
            summaryBox.classList.add('d-none');
            resetCustomerForm(false); // Clear and make editable
            if (typeof generateNextCustomerId === "function") generateNextCustomerId();
        }
    });
});

// 2. Load Customer List
async function loadCustomerList() {
    try {
        const res = await fetch('/cattleya/user/encoder/fetch/get-customers?action=list');
        const result = await res.json();
        const select = document.getElementById('sel_existing_customer');
        
        select.innerHTML = '<option value="">Search Customer ID...</option>';
        result.data.forEach(c => {
            let opt = new Option(`${c.lastname}, ${c.firstname} [${c.customer_id}]`, c.customer_id);
            select.add(opt);
        });
    } catch (e) { console.error("Load failed", e); }
}

// 3. Auto-fill inputs and Summary Box
document.getElementById('sel_existing_customer').addEventListener('change', async function() {
    const customerId = this.value;
    const summaryBox = document.getElementById('customerDetailGroup');

    if (!customerId) {
        summaryBox.classList.add('d-none');
        return;
    }

    try {
        const res = await fetch(`/cattleya/user/encoder/fetch/get-customers?action=details&id=${customerId}`);
        const result = await res.json();
        const c = result.data;

        if (c) {
            // A. Fill the Form Inputs
            document.getElementById('new_customer_id').value = c.customer_id;
            document.querySelector('input[name="fname"]').value = c.firstname;
            document.querySelector('input[name="mname"]').value = c.middlename;
            document.querySelector('input[name="lname"]').value = c.lastname;
            document.querySelector('input[name="mobile"]').value = c.mobile_number;
            document.querySelector('input[name="email"]').value = c.email_address;
            document.querySelector('textarea[name="address"]').value = c.complete_address;

            // B. Fill the Summary Box (Visual)
            document.getElementById('det_customer_name').textContent = `${c.lastname}, ${c.firstname} ${c.middlename}`;
            document.getElementById('det_customer_id').textContent = c.customer_id;
            document.getElementById('det_mobile').textContent = c.mobile_number;

            // C. UI Polish: Show Summary and Make Inputs Read-Only
            summaryBox.classList.remove('d-none');
            toggleInputsReadonly(true);
        }
    } catch (e) { console.error("Fetch failed", e); }
});

// Helper: Reset Form
function resetCustomerForm(isReadonly) {
    const fields = ['fname', 'mname', 'lname', 'mobile', 'email', 'address'];
    document.getElementById('new_customer_id').value = '';
    fields.forEach(name => {
        const el = document.querySelector(`[name="${name}"]`);
        el.value = '';
        el.readOnly = isReadonly;
    });
}

// Helper: Toggle Readonly status
function toggleInputsReadonly(status) {
    const fields = ['fname', 'mname', 'lname', 'mobile', 'email', 'address'];
    fields.forEach(name => {
        document.querySelector(`[name="${name}"]`).readOnly = status;
    });
}
document.addEventListener('DOMContentLoaded', function() {
    // Input Fields
    const tcpInput = document.getElementById('main_tcp');
    const cashPriceInput = document.getElementById('cash_price');
    const rawPriceInput = document.getElementById('raw_price'); 
    const rawCashPriceInput = document.getElementById('raw_cash_price'); 
    
    // Term/Payment Fields
    const termSelect = document.getElementById('term_select');
    const startDateInput = document.getElementById('start_date');
    const monthlyPaymentInput = document.getElementById('monthly_payment');
    const paymentLabel = document.getElementById('payment_label');

    // Breakdown Spans
    const outLotPrice = document.getElementById('out_lot_price');
    const outVat = document.getElementById('out_vat');
    const outCareFund = document.getElementById('out_care_fund');
    const outMarketing = document.getElementById('out_marketing');

    const formatPHP = (num) => {
        return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    function updateBreakdown(tcpValue) {
        if (!outLotPrice) return;

        // CRITICAL CHECK: Only compute if TCP is greater than zero
        if (tcpValue > 0) {
            const marketing = tcpValue * 0.10; 
            const careFund = tcpValue * 0.30;  
            const remainder = tcpValue - (marketing + careFund);
            const lotPrice = remainder / 1.12;
            const vat = remainder - lotPrice;

            outLotPrice.innerText = formatPHP(lotPrice);
            outVat.innerText = formatPHP(vat);
            outCareFund.innerText = formatPHP(careFund);
            outMarketing.innerText = formatPHP(marketing);
        } else {
            // Reset to default if input is empty or zero
            outLotPrice.innerText = "---";
            outVat.innerText = "---";
            outCareFund.innerText = "---";
            outMarketing.innerText = "---";
        }
    }

    function calculateAll() {
        let tcpValue = 0;
        if (document.activeElement === tcpInput) {
            tcpValue = parseFloat(tcpInput.value) || 0;
            if (rawPriceInput) rawPriceInput.value = tcpValue > 0 ? tcpValue.toFixed(2) : '';
        } else {
            tcpValue = parseFloat(rawPriceInput?.value) || parseFloat(tcpInput?.value) || 0;
            if (tcpInput && !tcpInput.value && tcpValue > 0) tcpInput.value = tcpValue;
        }

        const cashPriceValue = parseFloat(cashPriceInput?.value) || parseFloat(rawCashPriceInput?.value) || 0;

        // 1. Update Breakdown (Handles the "---" reset internally)
        updateBreakdown(tcpValue);

        // 2. Handle Monthly Payment / Terms Logic
        const selectedOption = termSelect?.options[termSelect.selectedIndex];
        if (!selectedOption || selectedOption.disabled) return;

        const rawTermValue = selectedOption.value.toUpperCase();
        const displayTermValue = selectedOption.getAttribute('data-display');

        if (monthlyPaymentInput) {
            monthlyPaymentInput.classList.remove('value-updated');
            void monthlyPaymentInput.offsetWidth; 
            monthlyPaymentInput.classList.add('value-updated');
        }

        if (rawTermValue === "1") {
            if (paymentLabel) paymentLabel.innerText = "Cash Amount";
            if (startDateInput) startDateInput.value = new Date().toISOString().split('T')[0];
            
            // Only show cash price if TCP > 0
            if (monthlyPaymentInput) {
                // Changed Math.round to .toFixed(2)
                monthlyPaymentInput.value = (tcpValue > 0 && cashPriceValue > 0) ? cashPriceValue.toFixed(2) : '';
            }
        } 
        else {
            if (paymentLabel) paymentLabel.innerText = "Monthly Payment";
            const totalMonths = parseInt(displayTermValue) || 0;

            // CRITICAL CHECK: Clear monthly payment if TCP is empty or zero
            if (tcpValue > 0 && totalMonths > 0) {
                const exactMonthly = tcpValue / totalMonths;
                // Changed Math.round to .toFixed(2)
                monthlyPaymentInput.value = exactMonthly.toFixed(2);
            } else {
                if (monthlyPaymentInput) monthlyPaymentInput.value = '';
            }
        }
    }

    // Listeners
    if (tcpInput) tcpInput.addEventListener('input', calculateAll);
    if (cashPriceInput) cashPriceInput.addEventListener('input', calculateAll);
    if (termSelect) termSelect.addEventListener('change', calculateAll);

    const observer = new MutationObserver(() => {
        if (document.activeElement !== tcpInput) calculateAll();
    });

    if (rawPriceInput) observer.observe(rawPriceInput, { attributes: true });
    if (rawCashPriceInput) observer.observe(rawCashPriceInput, { attributes: true });
});

// Modal reset logic remains the same (ensures clean state on close)
document.addEventListener('DOMContentLoaded', function() {
    const addModal = document.getElementById('addModal');
    const addLotForm = document.getElementById('addLotForm');
    const tcpInput = document.getElementById('main_tcp');
    const cashInput = document.getElementById('cash_price');
    const outSpans = ['out_lot_price', 'out_vat', 'out_care_fund', 'out_marketing'];

    if (addModal) {
        addModal.addEventListener('hidden.bs.modal', function () {
            if (addLotForm) addLotForm.reset();
            if (tcpInput) tcpInput.value = '';
            if (cashInput) cashInput.value = '';
            outSpans.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerText = '---';
            });
            const hiddenFields = ['hid_lot_price', 'hid_vat', 'hid_care_fund', 'hid_marketing'];
            hiddenFields.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.value = '';
            });
            const desc = addLotForm.querySelector('textarea[name="block_description"]');
            if (desc) desc.value = '';
        });
    } 
});

document.addEventListener('DOMContentLoaded', function() {
    // Select the modal element
    const updateModalEl = document.getElementById('updateStatusModal');

    if (updateModalEl) {
        // This event fires when the modal is completely hidden from the user
        updateModalEl.addEventListener('hidden.bs.modal', function () {
            
            // Define the IDs of the out-spans to be cleared
            const outSpans = ['out_lot_price', 'out_vat', 'out_care_fund', 'out_marketing'];
            
            outSpans.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.innerText = '---';
                }
            });

            console.log("Modal closed: Financial breakdown reset to default.");
        });
    }
});
/**
 * 5. EXTERNAL TRIGGER (Modal Opener)
 * Use this to populate data when a user clicks 'Edit' or 'Update'
 */
function openUpdateModal(data) {
    const rawPrice = document.getElementById('raw_price');
    const dispPrice = document.getElementById('disp_price');

    if (rawPrice) {
        // setAttribute is required to trigger the MutationObserver
        rawPrice.setAttribute('value', data.price);
        rawPrice.value = data.price;
    }
    
    if (dispPrice) {
        // Formats display for the user (e.g., ₱ 1,000,000.00)
        dispPrice.value = new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(data.price);
    }
    
    // Small delay to ensure DOM is ready, then force a calculation
    setTimeout(() => {
        const termsInput = document.querySelector('input[name="terms"]');
        if (termsInput && termsInput.value) {
            termsInput.dispatchEvent(new Event('input'));
        }
    }, 100);
}
</script>
</body>
</html>