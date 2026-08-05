<?php
require_once __DIR__ . '/../../../../config/database.php';
require __DIR__ . '/../../includes/session_check.php';

// Login guard
if (!isset($_SESSION['user_id'])) {
    header("Location: /cattleya/login");
    exit;
}

$user_id    = $_SESSION['user_id'];
$user_name  = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management | Cattleya</title>
    <link rel="icon" href="../../../assets/icons/favicon/cattleya_favicon.png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
    :root { 
        /* Updated brand colors to Cattleya Theme */
        --brand: #2a6279; /* Cattleya Navy */
        --brand-accent: #96c93d; /* Cattleya Green */
        --brand-light: rgba(42, 98, 121, 0.08);
        --text-main: #1e293b; 
        --text-muted: #64748b; 
        --border: #f1f5f9; 
        --surface: #FFFFFF;
        --radius-lg: 24px;
        --radius-md: 14px;
    }

    body { 
        font-family: 'Plus Jakarta Sans', sans-serif; 
        background-color: #f8fafc; 
        color: var(--text-main); 
    }
    
    .main-wrapper { 
        margin-left: 10px; 
        padding: 2.5rem; 
        transition: all 0.3s ease; 
    }
    
    .glass-card { 
        background: var(--surface); 
        border: 1px solid rgba(0,0,0,0.03); 
        border-radius: var(--radius-lg); 
        padding: 2rem;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.02);
        margin-bottom: 2rem;
    }

    .section-header { 
        margin-bottom: 1.5rem; 
        border-bottom: 1px solid var(--border); 
        padding-bottom: 1rem; 
    }
    
    .form-label { 
        font-weight: 700; 
        font-size: 0.75rem; 
        text-transform: uppercase; 
        color: var(--text-muted); 
        letter-spacing: 0.05em; 
    }

    .custom-input-modern { 
        border: 2px solid var(--border); 
        border-radius: 12px; 
        padding: 0.7rem 1rem; 
        font-size: 0.9rem; 
        background: #f8fafc; 
        transition: all 0.2s;
    }

    /* Updated focus shadow to match Cattleya Navy */
    .custom-input-modern:focus { 
        border-color: var(--brand); 
        outline: none; 
        box-shadow: 0 0 0 4px rgba(42, 98, 121, 0.08); 
        background: #fff; 
    }

    .btn-brand-modern { 
        background: var(--brand); 
        color: white; 
        border: none; 
        padding: 0.7rem 1.5rem; 
        border-radius: 12px; 
        font-weight: 700; 
        transition: all 0.3s;
    }

    /* Updated hover shadow and background to match brand */
    .btn-brand-modern:hover { 
        background: #1e4b5d; /* Darker Navy */
        transform: translateY(-2px); 
        box-shadow: 0 5px 15px rgba(42, 98, 121, 0.2); 
        color: white;
    }

    /* Table Styling */
    .custom-table thead th { 
        background: #f8fafc; 
        font-size: 0.65rem; 
        font-weight: 800; 
        text-transform: uppercase; 
        color: var(--text-muted); 
        padding: 1.2rem; 
        border-bottom: 2px solid var(--border);
    }

    /* Product Row Hover now using Cattleya Green accent */
    .product-row:hover { 
        background-color: #f7faf3 !important; 
        box-shadow: inset 4px 0 0 var(--brand-accent); 
    }

    .status-pill { 
        padding: 5px 12px; 
        border-radius: 8px; 
        font-size: 0.7rem; 
        font-weight: 800; 
    }

    .bg-active { background: #DCFCE7; color: #15803D; }
    .bg-inactive { background: #F1F5F9; color: #64748B; }

    @media (max-width: 992px) { 
        .main-wrapper { margin-left: 0; padding: 1.5rem; } 
    }
</style>
</head>
<body>

<?php require_once __DIR__ . '../../../includes/user/navbar.php'; ?>

<div class="main-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-800 mb-1" style="letter-spacing: -0.03em; color: #1e293b;">Product Inventory</h2>
            <p class="text-muted small mb-0">Manage property listings and inventory details.</p>
        </div>
        <div class="d-flex gap-2">
            <input type="text" id="productSearch" class="custom-input-modern mb-0" placeholder="Search listing..." style="width: 250px;">
        </div>
    </div>

    <div class="glass-card">
        <div class="section-header d-flex align-items-center gap-2">
            <i class="bi bi-plus-circle-fill" style="color: var(--brand-accent);"></i>
            <h6 class="mb-0 fw-bold">Quick Registration</h6>
        </div>
        
        <form method="POST" action="../encoder/fetch/save-product">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Property Name</label>
                    <input type="text" name="product_name" class="custom-input-modern w-100" placeholder="e.g. Cattleya Heights" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Blocks</label>
                    <input type="number" name="number_of_blocks" class="custom-input-modern w-100" placeholder="0" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Location</label>
                    <input type="text" name="address" class="custom-input-modern w-100" placeholder="City, Region">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Developer/Owner</label>
                    <input type="text" name="owner" class="custom-input-modern w-100" placeholder="Full Name">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Save Product</label>
                    <button type="submit" class="btn-brand-modern w-100 mb-3">
                        <i class="bi bi-plus-lg me-1"></i> Register
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="glass-card p-0">
        <div class="p-4 d-flex justify-content-between align-items-center border-bottom">
            <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2"></i>Active Listings</h6>
            <span id="productCount" class="badge rounded-pill" style="background: var(--brand-light); color: var(--brand);">0 Total</span>
        </div>
        
        <div class="table-responsive">
            <table class="table custom-table mb-0" id="productTable">
                <thead>
                    <tr>
                        <th class="ps-4">#</th>
                        <th>Property Details</th>
                        <th class="text-center">Units/Blocks</th>
                        <th>Owner</th>
                        <th class="text-center">Status</th>
                        <th class="text-center pe-4">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $stmt = $pdo->query("SELECT * FROM product_profile ORDER BY id ASC");
                        $count = 1;
                        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    ?>
                    <tr class="product-row align-middle">
                        <td class="ps-4 text-muted fw-bold"><?= $count++ ?></td>
                        <td>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($row['product_name']) ?></div>
                            <div class="small text-muted"><i class="bi bi-geo-alt me-1" style="color: var(--brand-accent);"></i><?= htmlspecialchars($row['address']) ?></div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-3"><?= $row['number_of_blocks'] ?> Blocks</span>
                        </td>
                        <td>
                            <div class="small fw-semibold"><?= htmlspecialchars($row['owner']) ?></div>
                        </td>
                        <td class="text-center">
                            <span class="status-pill <?= $row['status'] == 'Active' ? 'bg-active' : 'bg-inactive' ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>
                        <td class="text-center pe-4">
                            <button class="btn btn-sm btn-light editBtn"
                                style="border-radius: 8px; color: var(--brand);"
                                data-id="<?= $row['id'] ?>"
                                data-name="<?= htmlspecialchars($row['product_name']) ?>"
                                data-blocks="<?= $row['number_of_blocks'] ?>"
                                data-address="<?= htmlspecialchars($row['address']) ?>"
                                data-owner="<?= htmlspecialchars($row['owner']) ?>"
                                data-status="<?= $row['status'] ?>">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editProductModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 p-4">
                <h5 class="fw-800 mb-0" style="color: var(--brand); letter-spacing: -0.02em;">Edit Listing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="../encoder/fetch/update-product">
                <div class="modal-body p-4 pt-0">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Property Name</label>
                        <input type="text" name="product_name" id="edit_name" class="custom-input-modern w-100">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Blocks</label>
                            <input type="number" name="number_of_blocks" id="edit_blocks" class="custom-input-modern w-100">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="custom-input-modern form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="address" id="edit_address" class="custom-input-modern w-100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Owner</label>
                        <input type="text" name="owner" id="edit_owner" class="custom-input-modern w-100">
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button type="submit" class="btn-brand-modern w-100 py-3">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Search Functionality
    document.getElementById('productSearch').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('.product-row');
        let count = 0;
        
        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            if(text.includes(filter)) {
                row.style.display = "";
                count++;
            } else {
                row.style.display = "none";
            }
        });
        document.getElementById('productCount').innerText = count + (filter ? " Found" : " Total");
    });

    // Initialize counts on load
    window.onload = () => {
        document.getElementById('productCount').innerText = document.querySelectorAll('.product-row').length + " Total";
    };

    // Modal Logic
    document.querySelectorAll('.editBtn').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.dataset.id;
            document.getElementById('edit_name').value = this.dataset.name;
            document.getElementById('edit_blocks').value = this.dataset.blocks;
            document.getElementById('edit_address').value = this.dataset.address;
            document.getElementById('edit_owner').value = this.dataset.owner;
            document.getElementById('edit_status').value = this.dataset.status;
            new bootstrap.Modal(document.getElementById('editProductModal')).show();
        });
    });
</script>
</body>
</html>