<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../../config/config.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: login_form.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security | Update Password</title>
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Immediate Theme Loader -->
    <script>
      (function() {
        const savedTheme = localStorage.getItem('ml_theme') || 'dark';
        document.documentElement.setAttribute('data-theme', savedTheme);
      })();
    </script>

    <style>
        :root {
            /* Matched to Enterprise Dark Theme */
            --brand-primary: #f43f5e;
            --brand-primary-hover: #e11d48;
            --brand-gradient: linear-gradient(135deg, #f43f5e 0%, #9f1239 100%);
            --bg-canvas: #060913;
            --card-bg: #0e1626;
            --text-dark: #f8fafc;
            --text-muted: #94a3b8;
            --input-bg: rgba(6, 9, 19, 0.6);
            --input-border: rgba(255, 255, 255, 0.08);
            --radius-xl: 24px;
            --radius-md: 14px;
            --transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }

        /* Day Mode Overrides */
        [data-theme="light"] {
            --bg-canvas: #f8fafc;
            --card-bg: #ffffff;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --input-bg: #f1f5f9;
            --input-border: rgba(15, 23, 42, 0.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-canvas);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 24px;
            position: relative;
            overflow-x: hidden;
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        /* Ambient background glow effects */
        body::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 650px;
            height: 450px;
            background: radial-gradient(circle, rgba(244, 63, 94, 0.15) 0%, rgba(6, 9, 19, 0) 70%);
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
        }

        /* Floating Theme Toggle Button */
        .btn-theme-float {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 1000;
            background: var(--card-bg);
            border: 1px solid var(--input-border);
            color: var(--text-dark);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
            transition: var(--transition);
        }

        .btn-theme-float:hover {
            background: rgba(244, 63, 94, 0.12);
            border-color: rgba(244, 63, 94, 0.4);
            color: var(--brand-primary);
            transform: scale(1.08) rotate(15deg);
        }

        .container {
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 1;
            animation: cardEntrance 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes cardEntrance {
            from { opacity: 0; transform: translateY(20px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .password-card {
            background: var(--card-bg);
            border-radius: 28px;
            padding: 40px 32px;
            border: 1px solid var(--input-border);
            box-shadow: 0 30px 80px -20px rgba(0, 0, 0, 0.4), 
                        0 0 0 1px rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            transition: background-color 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease;
        }

        [data-theme="light"] .password-card {
            box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.08);
        }

        .brand-icon {
            width: 64px;
            height: 64px;
            background: rgba(244, 63, 94, 0.1);
            color: var(--brand-primary);
            border: 1px solid rgba(244, 63, 94, 0.25);
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 24px;
            font-size: 24px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            transition: var(--transition);
        }

        .brand-icon:hover {
            transform: scale(1.05);
            color: var(--brand-primary-hover);
        }

        .header-text {
            text-align: center;
            margin-bottom: 28px;
        }

        h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            color: var(--text-dark);
            font-size: 1.65rem;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
            transition: color 0.4s ease;
        }

        p.info-text {
            color: var(--text-muted);
            font-size: 0.9rem;
            line-height: 1.5;
            font-weight: 400;
            transition: color 0.4s ease;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.775rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            transition: color 0.4s ease;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i.main-icon {
            position: absolute;
            left: 16px;
            color: var(--text-muted);
            font-size: 1.1rem;
            transition: var(--transition);
            pointer-events: none;
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            transition: var(--transition);
        }

        .toggle-password:hover {
            color: var(--brand-primary);
        }

        input {
            width: 100%;
            padding: 14px 44px;
            border-radius: var(--radius-xl);
            border: 1px solid var(--input-border);
            background: var(--input-bg);
            color: var(--text-dark);
            font-size: 0.95rem;
            font-weight: 500;
            font-family: inherit;
            transition: var(--transition);
        }

        input::placeholder {
            color: #64748b;
        }

        input:focus {
            outline: none;
            background: var(--card-bg);
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(244, 63, 94, 0.15);
        }

        input:focus ~ i.main-icon {
            color: var(--brand-primary);
        }

        /* Password Strength Indicator */
        .strength-container {
            margin-top: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .strength-bars {
            flex: 1;
            display: flex;
            gap: 4px;
            height: 4px;
        }

        .strength-bar {
            flex: 1;
            height: 100%;
            background: #cbd5e1;
            border-radius: 2px;
            transition: var(--transition);
        }

        [data-theme="dark"] .strength-bar {
            background: #1e293b;
        }

        .strength-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            min-width: 50px;
            text-align: right;
        }

        .match-feedback {
            font-size: 0.78rem;
            font-weight: 600;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
            min-height: 18px;
        }

        .match-feedback.valid { color: #10b981; }
        .match-feedback.invalid { color: var(--brand-primary); }

        .btn-update {
            width: 100%;
            padding: 16px;
            background: var(--brand-gradient);
            color: #ffffff;
            border: none;
            border-radius: var(--radius-xl);
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            box-shadow: 0 10px 25px -5px rgba(244, 63, 94, 0.35);
            letter-spacing: 0.03em;
        }

        .btn-update:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #ff4d6d 0%, #be123c 100%);
            box-shadow: 0 15px 35px -5px rgba(244, 63, 94, 0.55);
        }

        .btn-update:active {
            transform: translateY(0);
        }

        .btn-update i {
            transition: transform 0.2s ease;
        }

        .btn-update:hover i {
            transform: translateX(4px);
        }

        /* Customizing SweetAlert Appearance */
        .swal2-popup {
            background: var(--card-bg) !important;
            color: var(--text-dark) !important;
            border-radius: 24px !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            padding: 2rem !important;
            border: 1px solid var(--input-border) !important;
        }

        .swal2-title, .swal2-html-container {
            color: var(--text-dark) !important;
        }

        .swal2-styled.swal2-confirm {
            border-radius: 12px !important;
            font-weight: 600 !important;
            padding: 12px 28px !important;
        }
    </style>
</head>
<body>

<!-- Floating Theme Switcher -->
<button class="btn-theme-float" id="themeToggleBtn" aria-label="Toggle theme" title="Switch Day/Night Mode">
    <i class="fa-solid fa-moon" id="themeToggleIcon"></i>
</button>

<div class="container">
    <div class="password-card">
        <div class="brand-icon">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        
        <div class="header-text">
            <h2>New Password</h2>
            <p class="info-text">Create a strong password to ensure your account stays protected.</p>
        </div>
        
        <form action="" method="POST" id="passwordForm">
            <div class="form-group">
                <label for="newPassword">New Password</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock main-icon"></i>
                    <input type="password" name="newPassword" id="newPassword" placeholder="••••••••" required autocomplete="new-password">
                    <i class="fa-solid fa-eye toggle-password" onclick="togglePass('newPassword', this)"></i>
                </div>
                <div class="strength-container">
                    <div class="strength-bars">
                        <div class="strength-bar" id="bar1"></div>
                        <div class="strength-bar" id="bar2"></div>
                        <div class="strength-bar" id="bar3"></div>
                        <div class="strength-bar" id="bar4"></div>
                    </div>
                    <span class="strength-label" id="strengthText"></span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirmPassword">Confirm Password</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-circle-check main-icon"></i>
                    <input type="password" name="confirmPassword" id="confirmPassword" placeholder="••••••••" required autocomplete="new-password">
                    <i class="fa-solid fa-eye toggle-password" onclick="togglePass('confirmPassword', this)"></i>
                </div>
                <div class="match-feedback" id="matchFeedback"></div>
            </div>
            
            <button type="submit" class="btn-update">
                <span>UPDATE PASSWORD</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>
    </div>
</div>

<script>
    // Theme Toggle Handler
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeToggleIcon = document.getElementById('themeToggleIcon');

    function updateThemeIcon(theme) {
      if (theme === 'light') {
        themeToggleIcon.className = 'fa-solid fa-sun';
      } else {
        themeToggleIcon.className = 'fa-solid fa-moon';
      }
    }

    updateThemeIcon(document.documentElement.getAttribute('data-theme') || 'dark');

    themeToggleBtn.addEventListener('click', () => {
      const currentTheme = document.documentElement.getAttribute('data-theme');
      const newTheme = currentTheme === 'light' ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', newTheme);
      localStorage.setItem('ml_theme', newTheme);
      updateThemeIcon(newTheme);
    });

    function togglePass(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }

    // Real-time password strength and matching checks
    const newPass = document.getElementById('newPassword');
    const confirmPass = document.getElementById('confirmPassword');
    const strengthText = document.getElementById('strengthText');
    const matchFeedback = document.getElementById('matchFeedback');
    const bars = [
        document.getElementById('bar1'),
        document.getElementById('bar2'),
        document.getElementById('bar3'),
        document.getElementById('bar4')
    ];

    newPass.addEventListener('input', () => {
        const val = newPass.value;
        let score = 0;

        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        bars.forEach((bar, index) => {
            if (index < score && val.length > 0) {
                if (score <= 1) bar.style.background = '#f43f5e';
                else if (score === 2) bar.style.background = '#f59e0b';
                else if (score === 3) bar.style.background = '#3b82f6';
                else bar.style.background = '#10b981';
            } else {
                bar.style.background = document.documentElement.getAttribute('data-theme') === 'light' ? '#cbd5e1' : '#1e293b';
            }
        });

        const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
        strengthText.textContent = val.length > 0 ? labels[score] : '';
        checkMatch();
    });

    confirmPass.addEventListener('input', checkMatch);

    function checkMatch() {
        if (confirmPass.value.length === 0) {
            matchFeedback.innerHTML = '';
            return;
        }

        if (newPass.value === confirmPass.value) {
            matchFeedback.className = 'match-feedback valid';
            matchFeedback.innerHTML = '<i class="fa-solid fa-check"></i> Passwords match';
        } else {
            matchFeedback.className = 'match-feedback invalid';
            matchFeedback.innerHTML = '<i class="fa-solid fa-xmark"></i> Passwords do not match';
        }
    }
</script>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword     = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $userEmail       = $_SESSION['user_email']; 

    if (!empty($newPassword) && $newPassword === $confirmPassword) {
        $hashedPassword = md5($newPassword);
        $stmt = $conn->prepare("UPDATE user_form SET password = ? WHERE username = ? OR email = ?");
        $stmt->bind_param("sss", $hashedPassword, $userEmail, $userEmail);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "<script>
                    let timerInterval;
                    Swal.fire({
                        icon: 'success',
                        title: 'Password Updated!',
                        html: 'Your account is now secure. Redirecting in <b></b> ms.',
                        timer: 2500,
                        timerProgressBar: true,
                        confirmButtonColor: '#f43f5e',
                        background: '#0e1626',
                        color: '#f8fafc',
                        didOpen: () => {
                            Swal.showLoading();
                            const b = Swal.getHtmlContainer().querySelector('b');
                            timerInterval = setInterval(() => {
                                b.textContent = Swal.getTimerLeft();
                            }, 100);
                        },
                        willClose: () => {
                            clearInterval(timerInterval);
                        }
                    }).then(() => {
                        window.location.href='login_form.php';
                    });
                </script>";
            } else {
                echo "<script>
                    Swal.fire({
                        icon: 'info',
                        title: 'No Changes Made',
                        text: 'This password is identical to your current password.',
                        confirmButtonColor: '#64748b',
                        background: '#0e1626',
                        color: '#f8fafc'
                    });
                </script>";
            }
        } else {
            echo "<script>Swal.fire({ icon: 'error', title: 'System Error', text: 'Please try again later.', confirmButtonColor: '#f43f5e', background: '#0e1626', color: '#f8fafc' });</script>";
        }
        $stmt->close();
    } else {
        echo "<script>Swal.fire({ icon: 'warning', title: 'Check Fields', text: 'Passwords do not match.', confirmButtonColor: '#f43f5e', background: '#0e1626', color: '#f8fafc' });</script>";
    }
}
?>
</body>
</html>