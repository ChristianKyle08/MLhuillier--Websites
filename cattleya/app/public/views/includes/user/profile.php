<?php
require __DIR__ . '/../../../../config/database.php';
require __DIR__ . '/../session_check.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /cattleya/login");
    exit;
}

// Fetch user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) { die("User not found."); }

$role_display = str_replace('_', ' ', $user['role']);

/** * PROFILE IMAGE LOGIC */
if (!empty($user['profile_image'])) {
    $profile_pic = "data:image/jpeg;base64," . base64_encode($user['profile_image']);
} else {
    $profile_pic = "https://ui-avatars.com/api/?name=" . urlencode($user['first_name'] . '+' . $user['last_name']) . "&background=2c5e7d&color=fff&size=300&bold=true";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile • @<?php echo htmlspecialchars($user['username']); ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --cattleya-blue: #2c5e7d;
            --cattleya-green: #a3c644;
            --sidebar-width: 245px;
            --sidebar-collapsed: 75px;
            --soft-shadow: 0 4px 20px rgba(0,0,0,0.08);
            --ig-gray: #8e8e8e;
            --border-color: #efefef;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            color: #000;
            margin: 0;
            display: flex;
            overflow-x: hidden;
        }

        .main-wrapper {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }

        .ig-profile-content {
            width: 100%;
            max-width: 935px;
            padding: 50px 20px;
        }

        /* --- Identity Section --- */
        .pf-identity-frame { display: flex; justify-content: center; position: relative; }
        .pf-dynamic-ring {
            width: 168px; height: 168px; border-radius: 50%; padding: 4px;
            background: linear-gradient(45deg, var(--cattleya-blue), var(--cattleya-green), #fff);
            background-size: 200% 200%; animation: ringGlow 4s linear infinite;
            transition: transform 0.3s ease;
        }
        .pf-main-image {
            width: 100%; height: 100%; border-radius: 50%; border: 4px solid #fff;
            object-fit: cover; background: #fff;
        }

        @keyframes ringGlow {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* --- Header Styling --- */
        .profile-header {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 50px;
            align-items: flex-start;
        }

        .username-row { display: flex; align-items: center; gap: 20px; margin-bottom: 25px; }
        .username-text { font-size: 28px; font-weight: 300; margin: 0; }
        .verified-badge { color: #0095f6; font-size: 1.4rem; }

        .stats-row { display: flex; gap: 30px; margin-bottom: 20px; list-style: none; padding: 0; }
        .stats-row li { font-size: 16px; }

        .bio-area b { font-size: 16px; display: block; margin-bottom: 4px; }
        .bio-text { font-size: 14px; line-height: 1.5; }

        /* --- Tabbed Content --- */
        .info-tabs {
            display: flex;
            justify-content: center;
            border-top: 1px solid var(--border-color);
            gap: 60px;
        }

        .tab-btn {
            text-transform: uppercase; font-size: 12px; font-weight: 600;
            letter-spacing: 1px; padding: 15px 0; color: var(--ig-gray);
            cursor: pointer; border-top: 1.5px solid transparent;
            display: flex; align-items: center; gap: 8px; transition: 0.3s;
        }

        .tab-btn.active { color: #000; border-top-color: #000; }
        .tab-pane { display: none; margin-top: 25px; animation: fadeIn 0.4s ease; }
        .tab-pane.active { display: grid; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        /* Information Grid */
        .content-grid { grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .grid-item {
            background: #fcfcfc; border: 1px solid var(--border-color);
            border-radius: 12px; padding: 20px; text-align: center; transition: 0.3s;
        }
        .grid-item:hover { background: #fff; box-shadow: var(--soft-shadow); }
        .item-label { font-size: 10px; color: var(--ig-gray); font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 5px; }
        .item-value { font-size: 15px; font-weight: 500; }

        /* Documents (Signature) */
        .documents-grid { grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .doc-card { border: 1px solid var(--border-color); border-radius: 12px; overflow: hidden; background: #fff; }
        .doc-preview-area {
            height: 220px; background: #fff; display: flex;
            align-items: center; justify-content: center; padding: 20px;
            position: relative;
        }
        .doc-preview-area img { 
            max-width: 100%; max-height: 100%; object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }
        .doc-info { padding: 15px; border-top: 1px solid var(--border-color); background: #fafafa; }

        @media (max-width: 768px) {
            .profile-header { grid-template-columns: 1fr; text-align: center; }
            .username-row, .stats-row { justify-content: center; }
            .content-grid, .documents-grid { grid-template-columns: 1fr; }
            .main-wrapper { margin-left: 0; width: 100%; }
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/navbar.php'; ?>

    <div class="main-wrapper">
        <div class="ig-profile-content">
            
            <header class="profile-header">
                <div class="pf-identity-frame" data-aos="fade-right">
                    <div class="pf-dynamic-ring">
                        <img src="<?php echo $profile_pic; ?>" class="pf-main-image" alt="User Identity">
                    </div>
                </div>

                <div class="info-container" data-aos="fade-left">
                    <div class="username-row">
                        <h2 class="username-text"><?php echo htmlspecialchars($user['username']); ?></h2>
                        <i class="bi bi-patch-check-fill verified-badge" title="Official Profile"></i>
                    </div>

                    <ul class="stats-row">
                        <li><b>Verified</b> Personnel</li>
                        <li><b><?php echo ucfirst($user['status']); ?></b> Account</li>
                        <li><b>Active</b> Session</li>
                    </ul>

                    <div class="bio-area">
                        <b><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></b>
                        <div class="bio-text">
                            <?php echo $role_display; ?> <br>
                            M Lhuillier • Cattleya Gardens Management <br>
                            <span class="text-muted"><i class="bi bi-geo-alt"></i> Cebu, Philippines</span>
                        </div>
                    </div>
                </div>
            </header>

            <div class="info-tabs">
                <div class="tab-btn active" onclick="switchTab('info', this)"><i class="bi bi-grid-3x3"></i> Information</div>
                <div class="tab-btn" onclick="switchTab('docs', this)"><i class="bi bi-bookmark"></i> Documents</div>
            </div>

            <div id="tab-info" class="tab-pane active content-grid">
                <div class="grid-item" data-aos="fade-up" data-aos-delay="100">
                    <span class="item-label">Full Legal Name</span>
                    <span class="item-value"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                </div>
                <div class="grid-item" data-aos="fade-up" data-aos-delay="200">
                    <span class="item-label">Corporate Email</span>
                    <span class="item-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="grid-item" data-aos="fade-up" data-aos-delay="300">
                    <span class="item-label">System Username</span>
                    <span class="item-value">@<?php echo htmlspecialchars($user['username']); ?></span>
                </div>
                <div class="grid-item" data-aos="fade-up" data-aos-delay="400">
                    <span class="item-label">Role & Access</span>
                    <span class="item-value"><?php echo strtoupper($user['role']); ?></span>
                </div>
                <div class="grid-item" data-aos="fade-up" data-aos-delay="500">
                    <span class="item-label">Member Since</span>
                    <span class="item-value"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></span>
                </div>
                <div class="grid-item" data-aos="fade-up" data-aos-delay="600">
                    <span class="item-label">Last Synchronization</span>
                    <span class="item-value small"><?php echo date('h:i A, d M Y', strtotime($user['last_active'])); ?></span>
                </div>
            </div>

            <div id="tab-docs" class="tab-pane documents-grid">
    <div id="signature-container"></div>
</div>

            <footer class="text-center mt-5 pt-5 border-top opacity-50">
                <p class="small mb-0">© 2026 CATTLEYA GARDENS • AN M LHUILLIER COMPANY</p>
            </footer>
        </div>
    </div>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    <?php
        $image_src = '';
        $sig_name = !empty($user['signature_name']) ? addslashes(htmlspecialchars($user['signature_name'])) : 'Official E-Signature';
        $display_format = 'IMAGE';

        if (!empty($user['signature'])) {
            $raw_sig = $user['signature'];
            
            // SCENARIO 1: The database already contains the full "data:image/..." string
            if (strpos(substr(trim($raw_sig), 0, 20), 'data:image') === 0) {
                $image_src = trim($raw_sig);
            } else {
                // Determine correct MIME type
                $raw_type = !empty($user['signature_type']) ? strtolower(trim($user['signature_type'])) : 'png';
                $mime_type = (strpos($raw_type, 'image/') === 0) ? $raw_type : 'image/' . ltrim($raw_type, '.');
                $display_format = strtoupper(str_replace('image/', '', $mime_type));
                
                // SCENARIO 2: Check if the data is ALREADY Base64 encoded in the database
                // We test this by decoding and re-encoding to see if it matches.
                $decoded = base64_decode($raw_sig, true);
                if ($decoded !== false && base64_encode($decoded) === trim($raw_sig)) {
                    $base64_sig = trim($raw_sig); // Use as-is, it's already encoded
                } else {
                    $base64_sig = base64_encode($raw_sig); // Encode the raw binary
                }
                
                // SCENARIO 3: Strip ANY newlines or carriage returns that break JS strings
                $base64_sig = str_replace(["\r", "\n", " "], "", $base64_sig);
                
                $image_src = "data:" . $mime_type . ";base64," . $base64_sig;
            }
        }
    ?>

    // Pass the perfectly formatted, safe string to JavaScript
    const imageSrc = "<?php echo $image_src; ?>";
    const signatureName = "<?php echo $sig_name; ?>";
    const displayFormat = "<?php echo $display_format; ?>";
    
    const container = document.getElementById('signature-container');

    if (imageSrc) {
        container.innerHTML = `
            <div class="doc-card" data-aos="zoom-in">
                <div class="doc-preview-area">
                    <img src="${imageSrc}" alt="${signatureName}" id="js-sig-img" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                </div>
                <div class="doc-info">
                    <div class="fw-bold small text-dark">${signatureName}</div>
                    <div class="text-muted" style="font-size: 11px;">
                        Format: ${displayFormat} • Digital Signature
                    </div>
                </div>
            </div>
        `;
    } else {
        container.innerHTML = `
            <div class="col-12 text-center py-5 text-muted">
                <i class="bi bi-file-earmark-lock fs-1"></i>
                <p class="mt-2">No digital signature found for this account.</p>
            </div>
        `;
    }
});

AOS.init({ duration: 800, once: true });

function switchTab(tabName, element) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));

    element.classList.add('active');
    document.getElementById('tab-' + tabName).classList.add('active');
    AOS.refresh();
}
</script>
</body>
</html>