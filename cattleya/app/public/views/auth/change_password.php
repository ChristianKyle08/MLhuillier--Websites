<?php
require __DIR__ . '/../../../config/database.php';

// --- Backend Logic Preserved ---
if (!isset($_SESSION['change_password_required']) || !$_SESSION['user_id']) {
    header("Location: /login");
    exit;
}

$error = '';
$success = '';

// Handle AJAX Request for the smooth transition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$newPassword || !$confirmPassword) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    } elseif ($newPassword !== $confirmPassword) {
        echo json_encode(['status' => 'error', 'message' => 'Passwords do not match.']);
    } else {
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $_SESSION['user_id']]);

        unset($_SESSION['change_password_required']);
        echo json_encode(['status' => 'success', 'redirect' => '/login']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Password - Cattleya</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        :root {
            /* Cattleya Color Theme */
            --brand-color: #2a6279; /* Deep Teal */
            --brand-hover: #1e4a5c;
            --brand-accent: #9dc44d; /* Lime Green */
            --brand-soft: rgba(42, 98, 121, 0.08);
            --brand-gradient: linear-gradient(135deg, #2a6279 0%, #448098 100%);
            --text-dark: #2a3b42;
            --text-muted: #64748b;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Manrope', sans-serif;
            background-color: #fdfdfb;
            margin: 0;
            padding: 20px;
            overflow: hidden;
            position: relative;
        }

        #bg-canvas {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            z-index: -1;
            background: radial-gradient(circle at center, #ffffff 0%, #f1f5f9 100%);
        }

        .bg-blob {
            position: absolute;
            width: 600px; height: 600px;
            background: rgba(157, 196, 77, 0.1); /* Cattleya Green Soft */
            filter: blur(100px);
            border-radius: 50%;
            z-index: -1;
            animation: floatBlob 25s infinite alternate;
        }

        @keyframes floatBlob {
            from { transform: translate(-20%, -20%) rotate(0deg); }
            to { transform: translate(20%, 20%) rotate(360deg); }
        }

        .login-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px) saturate(160%);
            -webkit-backdrop-filter: blur(20px) saturate(160%);
            border-radius: 32px;
            padding: 3.5rem 2.5rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 30px 60px -12px rgba(42, 98, 121, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
            z-index: 10;
            animation: zoomInCard 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes zoomInCard {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .brand-icon-wrapper {
            width: 80px; height: 80px;
            background: var(--brand-gradient);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            border-radius: 24px;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            box-shadow: 0 10px 25px rgba(42, 98, 121, 0.3);
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        /* --- SMOOTH STRENGTH BAR --- */
        .strength-container {
            height: 6px;
            width: 100%;
            background: #e2e8f0;
            border-radius: 10px;
            margin: -5px 0 20px;
            overflow: hidden;
            display: block; 
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.6s cubic-bezier(0.65, 0, 0.35, 1), background-color 0.4s ease;
            background: var(--brand-gradient);
            position: relative;
        }

        /* --- FORM STYLING --- */
        .form-label {
            font-weight: 700; font-size: 0.75rem;
            text-transform: uppercase; letter-spacing: 1px;
            color: var(--text-muted); margin-bottom: 0.8rem;
        }

        .input-group-custom { position: relative; margin-bottom: 1.25rem; }

        .input-group-custom i.icon-left {
            position: absolute; left: 20px; top: 50%;
            transform: translateY(-50%); color: #94a3b8;
            font-size: 1.2rem; z-index: 10; transition: all 0.3s;
        }

        .form-control {
            border-radius: 18px; padding: 16px 16px 16px 56px;
            height: auto; border: 2px solid #f1f5f9;
            background: rgba(248, 250, 252, 0.8); font-weight: 600;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-control:focus {
            background: #fff; border-color: var(--brand-accent);
            box-shadow: 0 10px 25px -5px rgba(157, 196, 77, 0.15);
            transform: translateY(-2px); outline: none;
        }

        .btn-primary {
            background: var(--brand-gradient); border: none;
            border-radius: 18px; padding: 18px; font-weight: 800;
            color: #fff; width: 100%; transition: all 0.4s ease;
            margin-top: 1rem;
        }

        .btn-primary:hover {
            background: var(--brand-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(42, 98, 121, 0.2);
        }

        .btn-primary:disabled { opacity: 0.7; transform: scale(0.98); }

        .anim-element { animation: fadeInBlur 0.6s ease both; }
        @keyframes fadeInBlur {
            from { opacity: 0; filter: blur(10px); transform: translateY(10px); }
            to { opacity: 1; filter: blur(0); transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="bg-blob"></div>
<canvas id="bg-canvas"></canvas>

<div class="login-card">
    <div class="brand-icon-wrapper anim-element">
        <i class="bi bi-shield-lock-fill"></i>
    </div>
    
    <h4 class="anim-element text-center">Update Password</h4>
    <p class="instruction anim-element text-center">Set a secure password to continue.</p>

    <div id="alertContainer"></div>

    <form method="POST" id="updateForm">
        <div class="mb-3 anim-element">
            <label class="form-label" for="new_password">New Password</label>
            <div class="input-group-custom">
                <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Create new password" required>
                <i class="bi bi-lock icon-left"></i>
            </div>
            <div class="strength-container" id="strengthContainer">
                <div class="strength-bar" id="strengthBar"></div>
            </div>
        </div>

        <div class="mb-4 anim-element">
            <label class="form-label" for="confirm_password">Confirm Password</label>
            <div class="input-group-custom">
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat password" required>
                <i class="bi bi-shield-check icon-left"></i>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 anim-element" id="submitBtn">
            <span id="btnText">Update Account</span>
        </button>
    </form>
</div>

<script>
    /* --- PARTICLE ANIMATION --- */
    const canvas = document.getElementById('bg-canvas');
    const ctx = canvas.getContext('2d');
    let particles = [];
    function resize() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
    window.addEventListener('resize', resize);
    resize();

    class Particle {
        constructor() { this.init(); }
        init() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.speedX = (Math.random() - 0.5) * 0.4;
            this.speedY = (Math.random() - 0.5) * 0.4;
            this.size = Math.random() * 2 + 1;
        }
        update() {
            this.x += this.speedX; this.y += this.speedY;
            if (this.x > canvas.width || this.x < 0) this.speedX *= -1;
            if (this.y > canvas.height || this.y < 0) this.speedY *= -1;
        }
        draw() {
            ctx.fillStyle = 'rgba(42, 98, 121, 0.1)'; // Teal particles
            ctx.beginPath(); ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2); ctx.fill();
        }
    }
    for (let i = 0; i < 40; i++) particles.push(new Particle());
    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        particles.forEach(p => { p.update(); p.draw(); });
        requestAnimationFrame(animate);
    }
    animate();

    /* --- SMOOTH PASSWORD STRENGTH --- */
    const passInput = document.getElementById('new_password');
    const strengthBar = document.getElementById('strengthBar');
    const strengthContainer = document.getElementById('strengthContainer');

    passInput.addEventListener('input', () => {
        const val = passInput.value;
        if (val.length > 0) {
            strengthContainer.style.opacity = '1';
            let score = 0;
            if (val.length > 7) score += 25;
            if (/[A-Z]/.test(val)) score += 25;
            if (/[0-9]/.test(val)) score += 25;
            if (/[^A-Za-z0-9]/.test(val)) score += 25;
            
            strengthBar.style.width = score + '%';
            
            if (score <= 25) strengthBar.style.backgroundColor = '#ef4444';
            else if (score <= 75) strengthBar.style.backgroundColor = '#f59e0b';
            else strengthBar.style.backgroundColor = '#2a6279'; // Cattleya Teal
        } else {
            strengthContainer.style.opacity = '0';
            strengthBar.style.width = '0%';
        }
    });

    /* --- AJAX SUBMISSION WITH SYNCED REDIRECT --- */
    const form = document.getElementById('updateForm');
    const btn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        btn.disabled = true;
        btnText.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Processing...`;
        
        const formData = new FormData(this);

        fetch(window.location.href + '?ajax=1', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                strengthBar.style.transition = 'width 1s cubic-bezier(0.65, 0, 0.35, 1), background-color 0.5s ease';
                strengthBar.style.width = '100%';
                strengthBar.style.backgroundColor = '#9dc44d'; // Success Green
                
                btnText.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i>Success!`;
                
                setTimeout(() => {
                    window.location.href = data.redirect;
                }, 1200);
            } else {
                btn.disabled = false;
                btnText.innerText = "Update Account";
                document.getElementById('alertContainer').innerHTML = `
                    <div class="alert alert-danger animate__animated animate__shakeX" style="border-radius: 15px;">
                        <i class="bi bi-exclamation-circle me-2"></i> ${data.message}
                    </div>`;
            }
        })
        .catch(error => {
            btn.disabled = false;
            console.error('Error:', error);
        });
    });
</script>
</body>
</html>