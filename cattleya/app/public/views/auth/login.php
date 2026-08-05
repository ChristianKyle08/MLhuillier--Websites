<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cattleya | Secure Login Portal</title>
    
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Secure login portal for Cattleya. Access your premium dashboard and manage your account.">
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="Cattleya | Secure Login Portal">
    <meta property="og:description" content="Securely log in to access your Cattleya account and dashboard.">
    <meta property="og:type" content="website">

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" media="print" onload="this.media='all'">
    <noscript>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    </noscript>

    <style>
        /* Fonts */
        @font-face {
            font-family: 'Manrope';
            src: url('../../assets/fonts/Manrope-VariableFont_wght.ttf') format('truetype');
            font-weight: 200 800;
            font-display: swap; 
        }
        @font-face {
            font-family: 'DancingScript';
            src: url('../../assets/fonts/DancingScript-VariableFont_wght.ttf') format('truetype');
            font-weight: 100 900;
            font-display: swap;
        }

        :root {
            --c-teal: #1c5f66;
            --c-teal-dark: #114146;
            --c-lime: #a6ce39;
            --bg-main: #f8fafc;
            /* extended tokens, derived from the palette above */
            --c-teal-light: #2e8089;
            --c-lime-deep: #6c8625;
            --c-lime-light: #d9ea9e;
            --c-ink: #10262a;
            --c-sage: #eef5f3;
            
            /* Structural mapping wrappers for consistency */
            --input-border: #e2e8f0;
            --card-bg: rgba(255, 255, 255, 0.82);
        }

        body {
            min-height: 100vh;
            background: var(--bg-main); 
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Manrope', system-ui, sans-serif;
            margin: 0;
            overflow: hidden;
            position: relative;
            letter-spacing: -0.01em;
            color: var(--c-ink);
        }

        /* Ambient Premium Glow Gradients */
        .ambient-background {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            overflow: hidden;
            z-index: -1;
            pointer-events: none;
        }

        .bg-shape {
            position: absolute;
            border-radius: 50%;
            will-change: transform;
        }

        .shape-1 { 
            width: 900px; height: 900px; 
            background: radial-gradient(circle, rgba(28, 95, 102, 0.12) 0%, rgba(28, 95, 102, 0) 70%); 
            top: -25%; left: -15%; 
            animation: float1 22s infinite alternate ease-in-out;
        }
        .shape-2 { 
            width: 800px; height: 800px; 
            background: radial-gradient(circle, rgba(166, 206, 57, 0.14) 0%, rgba(166, 206, 57, 0) 70%); 
            bottom: -25%; right: -15%; 
            animation: float2 26s infinite alternate ease-in-out; 
        }

        @keyframes float1 {
            0% { transform: translate3d(0, 0, 0) rotate(0deg); }
            100% { transform: translate3d(60px, -40px, 0) rotate(10deg); }
        }
        @keyframes float2 {
            0% { transform: translate3d(0, 0, 0) rotate(0deg); }
            100% { transform: translate3d(-50px, 30px, 0) rotate(-10deg); }
        }

        /* Modernized Glassmorphism Card Style */
        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 28px;
            padding: 4rem 3.25rem;
            width: 100%;
            max-width: 440px;
            border: 1px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 20px 50px -12px rgba(17, 65, 70, 0.09);
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1), padding-bottom 0.4s ease;
        }

        .login-card:hover {
            transform: translate3d(0, -5px, 0);
            box-shadow: 0 30px 60px -15px rgba(17, 65, 70, 0.14);
        }

        .login-card > * {
            animation: premiumFadeInUp 0.7s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .login-card > *:nth-child(1) { animation-delay: 0.05s; }
        .login-card > *:nth-child(2) { animation-delay: 0.1s; }
        .login-card > *:nth-child(3) { animation-delay: 0.15s; }

        @keyframes premiumFadeInUp {
            from { opacity: 0; transform: translate3d(0, 20px, 0); }
            to { opacity: 1; transform: translate3d(0, 0, 0); }
        }

        /* Premium Minimalistic Logo Holder */
        .logo-emblem-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            background: #ffffff;
            border: 1px solid rgba(28, 95, 102, 0.08);
            border-radius: 18px;
            box-shadow: 0 8px 20px -6px rgba(17, 65, 70, 0.05);
            margin-bottom: 1.5rem;
            transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .login-card:hover .logo-emblem-container {
            transform: translate3d(0, -3px, 0) scale(1.03);
            box-shadow: 0 12px 24px -8px rgba(17, 65, 70, 0.1);
        }

        .subtitle {
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--c-teal);
            letter-spacing: 4px;
            text-transform: uppercase;
            opacity: 0.9;
            margin-top: 0.5rem;
        }

        .input-group-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
        }

        /* Sophisticated Inputs */
        .form-control {
            border-radius: 14px;
            height: 56px;
            font-size: 0.95rem;
            padding-left: 3.4rem;
            padding-right: 1.2rem;
            background: rgba(255, 255, 255, 0.85);
            border: 1.5px solid var(--input-border);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            color: var(--c-ink);
            font-weight: 500;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.005);
        }

        .form-control::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .form-control:focus {
            box-shadow: 0 8px 20px -4px rgba(28, 95, 102, 0.08), 0 0 0 4px rgba(28, 95, 102, 0.06);
            border-color: var(--c-teal);
            background: #ffffff;
            outline: none;
        }

        .icon-input {
            position: absolute;
            top: 50%;
            left: 1.35rem;
            transform: translate3d(0, -50%, 0);
            color: var(--c-teal);
            opacity: 0.45;
            font-size: 1.2rem;
            z-index: 5;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            pointer-events: none;
        }

        .form-control:focus + .icon-input {
            opacity: 1;
            color: var(--c-teal-light);
            transform: translate3d(0, -50%, 0) scale(1.05);
        }

        /* Bold High-Contrast Interaction Button */
        .btn-login {
            background: var(--c-teal);
            border: none;
            border-radius: 14px;
            font-weight: 600;
            font-size: 1rem;
            padding: 1.1rem;
            color: #fff;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 10px 24px -6px rgba(28, 95, 102, 0.25);
        }

        .btn-login:hover {
            background: var(--c-teal-dark);
            transform: translate3d(0, -2px, 0);
            box-shadow: 0 14px 28px -6px rgba(17, 65, 70, 0.35);
            color: #fff;
        }

        .btn-login:active {
            transform: scale(0.97);
        }

        /* Enhanced Microcard for Default Password Hint */
        .default-password-hint {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            margin-top: 0;
            padding: 0 18px;
            border-radius: 12px;
            background: var(--c-sage);
            border: 1px solid rgba(28, 95, 102, 0.12);
            font-size: 0.82rem;
            color: var(--c-teal-dark);
            line-height: 1.6;
            box-shadow: 0 4px 12px rgba(28, 95, 102, 0.03);
        }

        .default-password-hint.show {
            max-height: 110px;
            opacity: 1;
            margin-top: 14px;
            padding: 14px 18px;
        }

        /* Modern Gradient Linear Progress Indicator */
        .login-progress {
            height: 4px;
            background: linear-gradient(90deg, var(--c-teal), var(--c-lime));
            width: 0%;
            transition: width 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            border-radius: 8px;
            margin-bottom: 28px;
            position: relative;
        }

        .login-progress.submitting {
            background: linear-gradient(90deg, var(--c-teal), var(--c-lime), var(--c-teal));
            background-size: 200% 100%;
            animation: simpleGradientMove 1.5s linear infinite;
        }

        @keyframes simpleGradientMove {
            0% { background-position: 0% 0; }
            100% { background-position: 200% 0; }
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 1.35rem;
            transform: translate3d(0, -50%, 0);
            cursor: pointer;
            color: #94a3b8;
            z-index: 5;
            transition: color 0.25s ease, transform 0.25s ease;
            font-size: 1.15rem;
        }
        
        .toggle-password:hover { 
            color: var(--c-teal); 
            transform: translate3d(0, -50%, 0) scale(1.08);
        }
        
        .premium-link {
            color: var(--c-teal);
            transition: all 0.25s ease;
            font-weight: 600;
            text-decoration: none !important;
        }
        
        .premium-link:hover {
            color: var(--c-teal-light);
            text-decoration: underline !important;
            text-decoration-color: var(--c-lime) !important;
            text-decoration-thickness: 2px !important;
        }

        /* Premium Structured Alerts */
        .alert-danger {
            background: rgba(254, 242, 242, 0.95);
            border: 1px solid rgba(239, 68, 68, 0.25);
            color: #991b1b;
            font-size: 0.88rem;
            font-weight: 500;
            border-radius: 12px;
            padding: 0.9rem 1.2rem;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.04);
        }

        .fade-out-element {
            opacity: 0 !important;
            transform: translate3d(0, -15px, 0) !important;
            pointer-events: none;
        }
    </style>
</head>
<body>

<main>
    <div class="ambient-background" aria-hidden="true">
        <div class="bg-shape shape-1"></div>
        <div class="bg-shape shape-2"></div>
    </div>

    <section class="login-card" id="main-login-card" aria-label="Login Form">
        <header class="text-center mb-4">
            <div class="logo-emblem-container">
                <img src="../../assets/image/Cattleya.png" alt="Cattleya Corporate Logo" class="cattleya-logo" fetchpriority="high" style="max-width: 130px; height: auto;">        
            </div>
            <h1 class="subtitle mb-0">Secure Access Portal</h1>
        </header>

        <div id="alert-container" aria-live="polite">
            <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger text-center alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.75rem; top: 50%; transform: translate3d(0, -50%, 0);"></button>
            </div>
            <?php endif; ?>
        </div>

        <div class="login-progress" id="login-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100"></div>

        <form id="login-form" method="POST" action="/cattleya/login-process">
            <div class="input-group-wrapper anim-element">
                <label for="usernameInput" class="visually-hidden">Username</label>
                <input type="text" id="usernameInput" name="username" class="form-control" placeholder="Username" autocomplete="username" required>
                <i class="bi bi-person icon-input" aria-hidden="true"></i>
            </div>

            <div class="input-group-wrapper anim-element">
                <label for="passwordInput" class="visually-hidden">Password</label>
                <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Password" autocomplete="current-password" required>
                <i class="bi bi-shield-lock icon-input" aria-hidden="true"></i>
                <i class="bi bi-eye-fill toggle-password" aria-label="Toggle password visibility" role="button" tabindex="0"></i>

                <div id="defaultPasswordInfo" class="default-password-hint" aria-live="polite">
                    <i class="bi bi-magic me-2" style="color: var(--c-lime-deep);" aria-hidden="true"></i>
                    Detected default credentials:
                    <br><strong style="letter-spacing: 0.5px; color: var(--c-teal-dark); font-size: 0.85rem;">MLINC12345@</strong>
                </div>
            </div>

            <div class="d-flex justify-content-end mb-4 anim-element">
                <a href="/cattleya/forgot-password" class="small premium-link">Forgot Password?</a>
            </div>

            <button type="submit" class="btn btn-login w-100 mb-4 d-flex align-items-center justify-content-center gap-2" id="login-btn">
                <span>Sign In</span> <i class="bi bi-arrow-right fs-6" aria-hidden="true"></i>
            </button>

            <footer class="text-center small text-muted mb-0 anim-element">
                New here? <a href="/register" class="premium-link" style="color: var(--c-lime-deep)">Request Access</a>
            </footer>
        </form>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const usernameInput = document.getElementById('usernameInput');
    const defaultPasswordInfo = document.getElementById('defaultPasswordInfo');
    const progressBar = document.getElementById('login-progress');

    let typingTimer;
    const typingDelay = 500;

    usernameInput.addEventListener('input', () => {
        if(!progressBar.classList.contains('submitting')) {
            progressBar.style.width = usernameInput.value.length > 0 ? '40%' : '0%';
        }
        clearTimeout(typingTimer);
        typingTimer = setTimeout(checkDefaultPassword, typingDelay);
    });

    async function checkDefaultPassword() {
        const username = usernameInput.value.trim();
        if (!username) {
            defaultPasswordInfo.classList.remove('show');
            if(!progressBar.classList.contains('submitting')) progressBar.style.width = '0%';
            return;
        }
        try {
            const res = await fetch('/cattleya/check-default-password', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username })
            });
            const data = await res.json();
            if(data.is_default) {
                defaultPasswordInfo.classList.add('show'); 
                if(!progressBar.classList.contains('submitting')) progressBar.style.width = '100%';
            } else {
                defaultPasswordInfo.classList.remove('show');
                if(!progressBar.classList.contains('submitting')) progressBar.style.width = '70%';
            }
        } catch {
            defaultPasswordInfo.classList.remove('show');
        }
    }

    const togglePassword = document.querySelector('.toggle-password');
    const passwordInput = document.getElementById('passwordInput');

    const handleToggle = () => {
        const isHidden = passwordInput.type === 'password';
        passwordInput.type = isHidden ? 'text' : 'password';
        togglePassword.classList.toggle('bi-eye-fill', !isHidden);
        togglePassword.classList.toggle('bi-eye-slash-fill', isHidden);
    };

    togglePassword.addEventListener('click', handleToggle);
    togglePassword.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            handleToggle();
        }
    });

    document.getElementById('login-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        const btn = document.getElementById('login-btn');
        const elementsToHide = document.querySelectorAll('.anim-element, #alert-container');
        const loginCard = document.getElementById('main-login-card');
        
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Authenticating...';
        btn.style.pointerEvents = 'none'; 
        
        progressBar.classList.add('submitting');
        progressBar.style.width = '100%';
        
        elementsToHide.forEach((el, index) => {
            el.style.transition = `all 0.3s ease ${index * 0.05}s`;
            el.classList.add('fade-out-element');
        });
        
        loginCard.style.paddingBottom = '2rem';
        
        setTimeout(() => {
            form.submit();
        }, 800);
    });
});
</script>

</body>
</html>