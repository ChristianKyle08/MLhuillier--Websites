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
            --bg-main: #f0f4f5;
            --c-teal-light: #2e8089;
            --c-lime-deep: #6c8625;
            --c-lime-light: #d9ea9e;
            --c-ink: #0d1e21;
            --c-sage: #eef5f3;
            --input-border: #d1dbe0;
            --card-bg: #ffffff;
        }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: 'Manrope', system-ui, -apple-system, sans-serif;
            letter-spacing: -0.015em;
            color: var(--c-ink);
            background: var(--bg-main);
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* ============================================================
           CANVAS SHELL & BACKGROUND MESH
        ============================================================ */
        .auth-shell {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
        }

        .ambient-background {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
            background: 
                radial-gradient(circle at 50% 0%, rgba(28, 95, 102, 0.15) 0%, transparent 60%),
                radial-gradient(circle at 85% 90%, rgba(166, 206, 57, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 10% 80%, rgba(17, 65, 70, 0.08) 0%, transparent 45%);
        }

        .bg-shape {
            position: absolute;
            border-radius: 50%;
            will-change: transform;
        }

        .shape-1 { 
            width: 650px; height: 650px; 
            background: radial-gradient(circle, rgba(28, 95, 102, 0.18) 0%, rgba(28, 95, 102, 0) 70%); 
            top: -15%; left: 50%; 
            transform: translateX(-50%);
            animation: pulseGlow 14s infinite alternate ease-in-out;
        }
        .shape-2 { 
            width: 500px; height: 500px; 
            background: radial-gradient(circle, rgba(166, 206, 57, 0.2) 0%, rgba(166, 206, 57, 0) 70%); 
            bottom: -10%; right: -5%; 
            animation: floatGlow 18s infinite alternate ease-in-out; 
        }

        @keyframes pulseGlow {
            0% { transform: translateX(-50%) scale(1); }
            100% { transform: translateX(-50%) scale(1.12); }
        }
        @keyframes floatGlow {
            0% { transform: translate(0, 0); }
            100% { transform: translate(-40px, -30px); }
        }

        /* Cadastral Blueprint Grid Overlay */
        .brand-plotgrid {
            position: fixed;
            inset: 0;
            background-image: 
                linear-gradient(rgba(28, 95, 102, 0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(28, 95, 102, 0.05) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }

        .plot-highlight {
            position: fixed;
            border-radius: 6px;
            pointer-events: none;
            z-index: 0;
            background: rgba(166, 206, 57, 0.12);
            border: 1px dashed rgba(166, 206, 57, 0.4);
        }
        .plot-highlight.plot-a { top: 15%; left: 12%; width: 120px; height: 80px; }
        .plot-highlight.plot-b { bottom: 18%; right: 14%; width: 160px; height: 120px; }

        /* ============================================================
           CENTERED BENTO WRAPPER
        ============================================================ */
        .bento-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 980px;
            display: grid;
            grid-template-columns: 1fr 1.15fr;
            gap: 0;
            background: #ffffff;
            border-radius: 28px;
            box-shadow: 
                0 30px 60px -12px rgba(13, 30, 33, 0.12),
                0 0 0 1px rgba(28, 95, 102, 0.08);
            overflow: hidden;
        }

        /* ============================================================
           BRAND PANEL (LEFT BENTO DECK)
        ============================================================ */
        .brand-panel {
            background: linear-gradient(160deg, var(--c-teal-dark) 0%, var(--c-teal) 100%);
            padding: 3.5rem 3rem;
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: 0; right: 0; width: 100%; height: 100%;
            background: radial-gradient(circle at 100% 0%, rgba(166, 206, 57, 0.25) 0%, transparent 55%);
            pointer-events: none;
        }

        .brand-panel-inner {
            position: relative;
            z-index: 2;
        }

        .brand-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.85rem;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--c-lime-light);
            margin-bottom: 2rem;
        }

        .brand-script {
            display: block;
            font-family: 'DancingScript', cursive;
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--c-lime-light);
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .brand-heading {
            font-size: 1.85rem;
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: -0.02em;
            margin-bottom: 1rem;
            color: #ffffff;
        }

        .brand-copy {
            font-size: 0.925rem;
            line-height: 1.65;
            color: rgba(255, 255, 255, 0.78);
            margin-bottom: 2.5rem;
        }

        .brand-points {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .brand-points li {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.92);
        }

        .brand-points i {
            width: 32px;
            height: 32px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 8px;
            color: var(--c-lime);
            font-size: 0.95rem;
        }

        /* ============================================================
           FORM PANEL (RIGHT BENTO DECK)
        ============================================================ */
        .form-panel {
            padding: 3.5rem 3.25rem;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .login-card {
            width: 100%;
        }

        .logo-emblem-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            background: var(--c-sage);
            border: 1px solid rgba(28, 95, 102, 0.1);
            border-radius: 14px;
            margin-bottom: 1.25rem;
        }

        .subtitle {
            font-size: 0.75rem;
            font-weight: 800;
            color: var(--c-teal);
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        /* Input Custom Styling */
        .input-group-wrapper {
            position: relative;
            margin-bottom: 1.25rem;
        }

        .form-control {
            border-radius: 12px;
            height: 52px;
            font-size: 0.925rem;
            padding-left: 3.25rem;
            padding-right: 1.25rem;
            background: #f8fafc;
            border: 1.5px solid var(--input-border);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            color: var(--c-ink);
            font-weight: 500;
        }

        .form-control::placeholder {
            color: #a0aec0;
            font-weight: 400;
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(28, 95, 102, 0.12);
            border-color: var(--c-teal);
            background: #ffffff;
            outline: none;
        }

        .icon-input {
            position: absolute;
            top: 50%;
            left: 1.15rem;
            transform: translateY(-50%);
            color: var(--c-teal);
            opacity: 0.5;
            font-size: 1.1rem;
            z-index: 5;
            transition: opacity 0.25s ease, color 0.25s ease;
            pointer-events: none;
        }

        .form-control:focus + .icon-input {
            opacity: 1;
            color: var(--c-teal);
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 1.15rem;
            transform: translateY(-50%);
            cursor: pointer;
            color: #a0aec0;
            z-index: 5;
            transition: color 0.2s ease;
            font-size: 1.1rem;
        }
        
        .toggle-password:hover { 
            color: var(--c-teal); 
        }

        /* Button Styling */
        .btn-login {
            background: var(--c-teal);
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 0.95rem;
            color: #ffffff;
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 8px 20px -4px rgba(28, 95, 102, 0.3);
        }

        .btn-login:hover {
            background: var(--c-teal-dark);
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -4px rgba(17, 65, 70, 0.4);
            color: #ffffff;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Credential Banner */
        .default-password-hint {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            margin-top: 0;
            padding: 0 16px;
            border-radius: 10px;
            background: var(--c-sage);
            border: 1px solid rgba(28, 95, 102, 0.15);
            font-size: 0.8rem;
            color: var(--c-teal-dark);
        }

        .default-password-hint.show {
            max-height: 100px;
            opacity: 1;
            margin-top: 10px;
            padding: 12px 16px;
        }

        /* Progress Bar */
        .login-progress {
            height: 4px;
            background: linear-gradient(90deg, var(--c-teal), var(--c-lime));
            width: 0%;
            transition: width 0.35s ease;
            border-radius: 4px;
            margin-bottom: 22px;
        }

        .login-progress.submitting {
            background: linear-gradient(90deg, var(--c-teal), var(--c-lime), var(--c-teal));
            background-size: 200% 100%;
            animation: moveGradient 1.2s linear infinite;
        }

        @keyframes moveGradient {
            0% { background-position: 0% 0; }
            100% { background-position: 200% 0; }
        }

        /* Checkbox & Links */
        .remember-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .remember-check .form-check-input {
            width: 1.1rem;
            height: 1.1rem;
            margin-top: 0;
            border: 1.5px solid var(--input-border);
            border-radius: 4px;
            cursor: pointer;
        }

        .remember-check .form-check-input:checked {
            background-color: var(--c-teal);
            border-color: var(--c-teal);
        }

        .remember-check .form-check-label {
            font-size: 0.825rem;
            font-weight: 600;
            color: var(--c-ink);
            opacity: 0.8;
            cursor: pointer;
        }

        .premium-link {
            color: var(--c-teal);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .premium-link:hover {
            color: var(--c-teal-light);
            text-decoration: underline;
        }

        .alert-danger {
            background: #fff5f5;
            border: 1px solid #feb2b2;
            color: #c53030;
            font-size: 0.85rem;
            border-radius: 10px;
        }

        .fade-out-element {
            opacity: 0 !important;
            transform: translateY(-10px) !important;
            pointer-events: none;
        }

        /* Responsive collapse for bento layout */
        @media (max-width: 860px) {
            .bento-container {
                grid-template-columns: 1fr;
                max-width: 460px;
            }
            .brand-panel {
                display: none;
            }
            .form-panel {
                padding: 2.75rem 2rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .bg-shape, .login-progress.submitting { animation: none !important; }
            .form-control, .btn-login, .default-password-hint { transition: none !important; }
        }
    </style>
</head>
<body>

<div class="auth-shell">

    <div class="ambient-background" aria-hidden="true">
        <div class="bg-shape shape-1"></div>
        <div class="bg-shape shape-2"></div>
    </div>

    <!-- Cadastral Grid Layer -->
    <div class="brand-plotgrid" aria-hidden="true"></div>
    <div class="plot-highlight plot-a" aria-hidden="true"></div>
    <div class="plot-highlight plot-b" aria-hidden="true"></div>

    <!-- Centered Bento Architecture Container -->
    <div class="bento-container">

        <!-- Left Brand Deck -->
        <aside class="brand-panel" aria-hidden="true">
            <div class="brand-panel-inner">
                <div class="brand-badge-pill">
                    <i class="bi bi-shield-lock-fill"></i> Secure Portal
                </div>
                <span class="brand-script">Cattleya</span>
                <h2 class="brand-heading">One portal for every block, lot, and ledger.</h2>
                <p class="brand-copy">Sign in to manage records, payments, and client accounts &mdash; all built around your role and the features you've been granted.</p>
                
                <ul class="brand-points">
                    <li><i class="bi bi-shield-check"></i> Scoped to your role, down to the feature</li>
                    <li><i class="bi bi-diagram-3"></i> One account across every department</li>
                    <li><i class="bi bi-grid-3x3-gap"></i> Built around blocks, lots, and ledgers</li>
                </ul>
            </div>
        </aside>

        <!-- Right Form Deck -->
        <main class="form-panel">
            <section class="login-card" id="main-login-card" aria-label="Login Form">
                <header class="text-center mb-4">
                    <div class="logo-emblem-container">
                        <img src="../../assets/image/Cattleya.png" alt="Cattleya Corporate Logo" class="cattleya-logo" fetchpriority="high" style="max-width: 110px; height: auto;">        
                    </div>
                    <h1 class="subtitle mb-0">Secure Access Portal</h1>
                </header>

                <div id="alert-container" aria-live="polite">
                    <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger text-center alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="font-size: 0.75rem; top: 50%; transform: translateY(-50%);"></button>
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
                            <i class="bi bi-magic me-1" style="color: var(--c-lime-deep);" aria-hidden="true"></i>
                            Detected default credentials:
                            <br><strong style="letter-spacing: 0.5px; color: var(--c-teal-dark); font-size: 0.85rem;">MLINC12345@</strong>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4 anim-element">
                        <div class="remember-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="rememberInput">
                            <label class="form-check-label" for="rememberInput">Remember me</label>
                        </div>
                        <a href="/cattleya/forgot-password" class="small premium-link">Forgot Password?</a>
                    </div>

                    <button type="submit" class="btn btn-login w-100 mb-4 d-flex align-items-center justify-content-center gap-2" id="login-btn">
                        <span>Sign In</span> <i class="bi bi-arrow-right fs-6" aria-hidden="true"></i>
                    </button>

                    <footer class="text-center small text-muted mb-0 anim-element">
                        New here? <a href="/cattleya/register" class="premium-link" style="color: var(--c-lime-deep)">Request Access</a>
                    </footer>
                </form>
            </section>
        </main>

    </div>
</div>

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