<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cattleya | Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        /* Fonts - Preserving original structure */
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
            /* Cattleya Color Theme Tokens */
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

            /* Structural layout adapters */
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
            padding: 40px 20px;
            color: var(--c-ink);
            margin: 0;
            overflow-x: hidden;
            position: relative;
            letter-spacing: -0.01em;
        }

        /* Ambient Premium Glow Gradients for Visual Consistency */
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
            width: 950px; height: 950px; 
            background: radial-gradient(circle, rgba(28, 95, 102, 0.12) 0%, rgba(28, 95, 102, 0) 70%); 
            top: -20%; right: -15%; 
            animation: float1 24s infinite alternate ease-in-out;
        }
        .shape-2 { 
            width: 850px; height: 850px; 
            background: radial-gradient(circle, rgba(166, 206, 57, 0.13) 0%, rgba(166, 206, 57, 0) 70%); 
            bottom: -20%; left: -15%; 
            animation: float2 28s infinite alternate ease-in-out; 
        }

        @keyframes float1 {
            0% { transform: translate3d(0, 0, 0) rotate(0deg); }
            100% { transform: translate3d(-50px, 40px, 0) rotate(8deg); }
        }
        @keyframes float2 {
            0% { transform: translate3d(0, 0, 0) rotate(0deg); }
            100% { transform: translate3d(40px, -30px, 0) rotate(-8deg); }
        }

        /* Modern UI - Premium Glassmorphism Card */
        .register-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 32px;
            padding: 4rem 3.25rem;
            width: 100%;
            max-width: 540px;
            box-shadow: 0 25px 55px -12px rgba(17, 65, 70, 0.08);
            opacity: 0;
            transform: translate3d(0, 30px, 0);
            transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.5s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .register-card.reveal {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }

        .register-card:hover {
            box-shadow: 0 35px 70px -15px rgba(17, 65, 70, 0.13);
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

        /* Premium Minimalistic Logo Holder */
        .logo-emblem-container {
            display: inline-flex;
            aria-hidden: true;
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

        .register-card:hover .logo-emblem-container {
            transform: translate3d(0, -3px, 0) scale(1.03);
            box-shadow: 0 12px 24px -8px rgba(17, 65, 70, 0.1);
        }

        /* Cleaner Icon Placement and Form Controls */
        .input-group-custom {
            position: relative;
            margin-bottom: 1.35rem;
            opacity: 0;
            transform: translate3d(-15px, 0, 0);
            transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .register-card.reveal .input-group-custom {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }

        .form-control, .form-select {
            border-radius: 14px;
            height: 58px;
            padding-left: 3.4rem;
            padding-right: 1.2rem;
            font-size: 0.95rem;
            border: 1.5px solid var(--input-border);
            background: rgba(255, 255, 255, 0.85);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.005);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            color: var(--c-ink);
            font-weight: 500;
        }

        .form-control::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .form-control:focus, .form-select:focus {
            box-shadow: 0 8px 20px -4px rgba(28, 95, 102, 0.08), 0 0 0 4px rgba(28, 95, 102, 0.06);
            border-color: var(--c-teal);
            background-color: #fff;
            outline: none;
        }

        .icon-input {
            position: absolute;
            left: 1.35rem;
            top: 50%;
            transform: translate3d(0, -50%, 0);
            color: var(--c-teal);
            opacity: 0.45;
            font-size: 1.2rem;
            z-index: 5;
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .form-control:focus + .icon-input {
            opacity: 1;
            color: var(--c-teal-light);
            transform: translate3d(0, -50%, 0) scale(1.05);
        }

        .toggle-password {
            position: absolute;
            right: 1.35rem;
            top: 50%;
            transform: translate3d(0, -50%, 0);
            cursor: pointer;
            color: #94a3b8;
            z-index: 5;
            font-size: 1.15rem;
            transition: color 0.25s ease, transform 0.25s ease;
        }

        .toggle-password:hover {
            color: var(--c-teal);
            transform: translate3d(0, -50%, 0) scale(1.08);
        }

        .btn-register {
            background: var(--c-teal);
            border: none;
            border-radius: 14px;
            font-weight: 600;
            font-size: 1rem;
            padding: 1.1rem;
            color: #fff;
            width: 100%;
            text-transform: none;
            letter-spacing: normal;
            margin-top: 0.75rem;
            box-shadow: 0 10px 24px -6px rgba(28, 95, 102, 0.25);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            opacity: 0;
            transform: translate3d(0, 15px, 0);
        }

        .register-card.reveal .btn-register {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }

        .btn-register:hover:not(:disabled) {
            background: var(--c-teal-dark);
            transform: translate3d(0, -2px, 0);
            box-shadow: 0 14px 28px -6px rgba(17, 65, 70, 0.35);
            color: #fff;
        }

        .btn-register:active {
            transform: scale(0.97);
        }

        .link-brand {
            color: var(--c-teal);
            transition: all 0.25s ease;
            font-weight: 600;
            text-decoration: none !important;
        }
        
        .link-brand:hover {
            color: var(--c-teal-light);
            text-decoration: underline !important;
            text-decoration-color: var(--c-lime) !important;
            text-decoration-thickness: 2px !important;
        }

        /* Staggered Animation Delays */
        .stagger-1 { transition-delay: 0.04s; }
        .stagger-2 { transition-delay: 0.08s; }
        .stagger-3 { transition-delay: 0.12s; }
        .stagger-4 { transition-delay: 0.16s; }
        .stagger-5 { transition-delay: 0.20s; }
        .stagger-6 { transition-delay: 0.24s; }

        /* Progress Bar Overlay */
        #registrationProgressOverlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(248, 250, 252, 0.92);
            z-index: 9999;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .progress-container {
            width: 85%;
            max-width: 380px;
            height: 5px;
            background: #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 24px;
        }

        .progress-bar-fill {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, var(--c-teal), var(--c-lime));
            border-radius: 8px;
            transition: width 0.1s linear;
        }

        /* Requirements UI */
        .requirements-box {
            background: var(--c-sage);
            padding: 1rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1.35rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            border: 1px solid rgba(28, 95, 102, 0.08);
            box-shadow: 0 4px 12px rgba(28, 95, 102, 0.02);
            transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            opacity: 0;
            transform: translate3d(-15px, 0, 0);
        }

        .register-card.reveal .requirements-box {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }

        .requirement {
            font-size: 0.78rem;
            list-style: none;
            display: flex;
            align-items: center;
            color: #94a3b8;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .requirement i {
            font-size: 0.95rem;
            color: #cbd5e1;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275), color 0.3s ease;
        }

        .requirement.valid { 
            color: var(--c-teal-dark); 
            font-weight: 600;
        }
        
        .requirement.valid i { 
            color: var(--c-c-lime, #a6ce39); 
            transform: scale(1.1); 
        }

        /* Overriding bootstrap colors natively inside requirements logic */
        .requirement.valid i.bi-check-circle-fill {
            color: var(--c-lime-deep) !important;
        }

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

        @media(max-width: 540px) {
            .register-card { padding: 3rem 1.75rem; }
            .requirements-box { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="ambient-background" aria-hidden="true">
    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>
</div>

<div id="registrationProgressOverlay">
    <div class="text-center px-3">
        <div class="spinner-border mb-3" role="status" style="color: var(--c-teal); width: 2.5rem; height: 2.5rem; border-width: 0.22em;"></div>
        <h4 class="fw-bold" style="color: var(--c-ink); letter-spacing: -0.02em;">Creating Your Account</h4>
        <p class="text-muted small">Please wait while we establish your secure credentials.</p>
        <div class="progress-container">
            <div class="progress-bar-fill" id="progressBar"></div>
        </div>
    </div>
</div>

<div class="register-card" id="registerCard">
    <header class="text-center mb-4">
        <div class="logo-emblem-container">
            <img src="../../assets/image/Cattleya.png" alt="Cattleya Corporate Logo" class="cattleya-logo" fetchpriority="high" style="max-width: 130px; height: auto;">        
        </div>
        <h1 class="subtitle mb-0">Secure Registration</h1>
    </header>

    <?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger text-center alert-dismissible fade show border-0 shadow-sm mb-4">
        <i class="bi bi-exclamation-circle me-2"></i>
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" style="font-size: 0.75rem; top: 50%; transform: translate3d(0, -50%, 0);"></button>
    </div>
    <?php endif; ?>

    <form method="POST" action="/cattleya/register-process" id="registrationForm">
        <div class="row g-3">
            <div class="col-6 input-group-custom stagger-1">
                <input type="text" name="first_name" id="firstNameInput" class="form-control" placeholder="First Name" required>
                <i class="bi bi-person icon-input"></i>
            </div>
            <div class="col-6 input-group-custom stagger-1">
                <input type="text" name="last_name" id="lastNameInput" class="form-control" placeholder="Last Name" required>
                <i class="bi bi-person icon-input"></i>
            </div>
        </div>

        <div class="input-group-custom stagger-2">
            <input 
                type="text" 
                id="username"
                name="username" 
                class="form-control" 
                placeholder="Username" 
                required
                pattern="^[a-zA-Z0-9_]{5,15}$"
                autocomplete="off"
            >
            <i class="bi bi-person-badge icon-input"></i>
        </div>

        <div class="requirements-box stagger-2" id="usernameRequirements">
            <li class="requirement" id="u-length"><i class="bi bi-check-circle me-2"></i> 5-15 Characters</li>
            <li class="requirement" id="u-format"><i class="bi bi-check-circle me-2"></i> No spaces/special</li>
        </div>

        <div class="input-group-custom stagger-3">
            <input type="email" name="email" id="emailInput" class="form-control" placeholder="Email Address" required>
            <i class="bi bi-envelope icon-input"></i>
        </div>

        <div class="input-group-custom stagger-4">
            <input type="password" id="password" name="password" class="form-control" placeholder="Create Password" required>
            <i class="bi bi-key icon-input"></i>
            <i class="bi bi-eye toggle-password" data-target="#password"></i>
        </div>

        <div class="requirements-box stagger-4">
            <li class="requirement" id="length"><i class="bi bi-check-circle me-2"></i> 8+ Characters</li>
            <li class="requirement" id="uppercase"><i class="bi bi-check-circle me-2"></i> Uppercase</li>
            <li class="requirement" id="number"><i class="bi bi-check-circle me-2"></i> Number</li>
            <li class="requirement" id="special"><i class="bi bi-check-circle me-2"></i> Special Char</li>
        </div>

        <div class="input-group-custom stagger-5">
            <input type="password" id="confirm-password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
            <i class="bi bi-shield-check icon-input"></i>
            <i class="bi bi-eye toggle-password" data-target="#confirm-password"></i>
        </div>

        <button type="submit" class="btn btn-register stagger-6" id="submitBtn">
            <span>Register Account</span> <i class="bi bi-arrow-right fs-6 ms-1"></i>
        </button>

        <p class="text-center small mt-4 mb-0 text-muted stagger-6">
            Already have an account? 
            <a href="/login" class="link-brand fw-bold">Sign In</a>
        </p>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Reveal card animation
    window.addEventListener('load', () => {
        document.getElementById('registerCard').classList.add('reveal');
    });

    // --- Username Validation Logic ---
    const usernameInput = document.getElementById('username');
    const uRequirements = {
        length: document.getElementById('u-length'),
        format: document.getElementById('u-format')
    };

    usernameInput.addEventListener('input', function() {
        const val = this.value;
        const checks = {
            length: val.length >= 5 && val.length <= 15,
            format: /^[a-zA-Z0-9_]*$/.test(val) && val.length > 0
        };

        Object.keys(checks).forEach(key => {
            const el = uRequirements[key];
            el.classList.toggle('valid', checks[key]);
            const icon = el.querySelector('i');
            icon.classList.replace(checks[key] ? 'bi-check-circle' : 'bi-check-circle-fill', checks[key] ? 'bi-check-circle-fill' : 'bi-check-circle');
        });
    });

    // Password Validation Logic
    const passwordInput = document.getElementById('password');
    const pRequirements = {
        length: document.getElementById('length'),
        uppercase: document.getElementById('uppercase'),
        number: document.getElementById('number'),
        special: document.getElementById('special')
    };

    passwordInput.addEventListener('input', function() {
        const val = this.value;
        const checks = {
            length: val.length >= 8,
            uppercase: /[A-Z]/.test(val),
            number: /\d/.test(val),
            special: /[!@#$%^&*(),.?":{}|<>]/.test(val)
        };

        Object.keys(checks).forEach(key => {
            pRequirements[key].classList.toggle('valid', checks[key]);
            const icon = pRequirements[key].querySelector('i');
            icon.classList.replace(checks[key] ? 'bi-check-circle' : 'bi-check-circle-fill', checks[key] ? 'bi-check-circle-fill' : 'bi-check-circle');
        });
    });

    // Toggle Password Visibility Logic
    document.querySelectorAll('.toggle-password').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetQuery = this.getAttribute('data-target');
            const input = document.querySelector(targetQuery);
            const isPassword = input.type === "password";
            
            input.type = isPassword ? "text" : "password";
            this.classList.replace(isPassword ? 'bi-eye' : 'bi-eye-slash', isPassword ? 'bi-eye-slash' : 'bi-eye');
        });
    });

    // PROGRESS BAR LOGIC: Show progress then submit
    const regForm = document.getElementById('registrationForm');
    const overlay = document.getElementById('registrationProgressOverlay');
    const progressBar = document.getElementById('progressBar');

    regForm.addEventListener('submit', function(e) {
        // Final validations before showing progress
        if(passwordInput.value !== document.getElementById('confirm-password').value){
            e.preventDefault();
            alert("Passwords do not match!");
            return;
        }

        // Basic username pattern check
        if(!/^[a-zA-Z0-9_]{5,15}$/.test(usernameInput.value)){
            e.preventDefault();
            alert("Please follow the username requirements.");
            return;
        }

        e.preventDefault(); 
        overlay.style.display = 'flex';
        let width = 0;
        const interval = setInterval(() => {
            if (width >= 100) {
                clearInterval(interval);
                regForm.submit();
            } else {
                width += 2;
                progressBar.style.width = width + '%';
            }
        }, 30);
    });
</script>
</body>
</html>