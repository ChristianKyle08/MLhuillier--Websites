<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password | Cattleya</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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

        /* --- ENHANCED BACKGROUND --- */
        #bg-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: radial-gradient(circle at 20% 20%, #f1f5f9 0%, #ffffff 100%);
        }

        .bg-blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(157, 196, 77, 0.1); /* Cattleya Green Soft */
            filter: blur(80px);
            border-radius: 50%;
            z-index: -1;
            animation: move 20s infinite alternate;
        }

        @keyframes move {
            from { transform: translate(-10%, -10%) scale(1); }
            to { transform: translate(20%, 20%) scale(1.2); }
        }

        /* --- CARD DESIGN --- */
        .forgot-card {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-radius: 32px;
            padding: 3.5rem 2.5rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(42, 98, 121, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
            z-index: 10;
            animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .brand-icon-wrapper {
            width: 80px;
            height: 80px;
            background: var(--brand-gradient);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 24px;
            margin: 0 auto 1.5rem;
            font-size: 2.5rem;
            box-shadow: 0 10px 20px rgba(42, 98, 121, 0.3);
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .forgot-card:hover .brand-icon-wrapper {
            transform: scale(1.1) rotate(12deg);
            background: linear-gradient(135deg, #2a6279 0%, var(--brand-accent) 100%);
        }

        .forgot-card h4 {
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -1px;
            margin-bottom: 0.5rem;
        }

        /* --- STAGGERED ENTRANCE --- */
        .anim-group > * {
            animation: fadeInBlur 0.6s ease both;
        }
        .anim-group > *:nth-child(1) { animation-delay: 0.1s; }
        .anim-group > *:nth-child(2) { animation-delay: 0.2s; }
        .anim-group > *:nth-child(3) { animation-delay: 0.3s; }

        @keyframes fadeInBlur {
            from { opacity: 0; filter: blur(10px); transform: translateY(10px); }
            to { opacity: 1; filter: blur(0); transform: translateY(0); }
        }

        /* --- FORM STYLING --- */
        .form-label {
            font-weight: 700;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
            margin-bottom: 0.8rem;
            display: block;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group-custom i {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            z-index: 10;
        }

        .form-control {
            border-radius: 18px;
            padding: 16px 16px 16px 56px;
            height: auto;
            border: 2px solid #f1f5f9;
            background: rgba(248, 250, 252, 0.8);
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-control:focus {
            background: #fff;
            border-color: var(--brand-accent);
            box-shadow: 0 10px 25px -5px rgba(157, 196, 77, 0.15);
            transform: translateY(-2px);
        }

        .form-control:focus + i {
            color: var(--brand-color);
            transform: translateY(-50%) scale(1.1);
        }

        .btn-submit {
            background: var(--brand-gradient);
            border: none;
            border-radius: 18px;
            padding: 18px;
            font-weight: 800;
            color: #fff;
            width: 100%;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 1rem;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            top: 0; left: -100%; width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }

        .btn-submit:hover::after { left: 100%; }

        .btn-submit:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 30px rgba(42, 98, 121, 0.4);
            background: var(--brand-hover);
            color: #fff;
        }

        .btn-submit:active { transform: translateY(-1px); }

        /* --- ALERTS --- */
        .alert {
            border-radius: 20px;
            border: none;
            font-weight: 600;
            padding: 1.2rem;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
        }

        .footer-text a {
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 12px;
        }

        .footer-text a:hover {
            color: var(--brand-color);
            background: var(--brand-soft);
            transform: translateX(-5px);
        }

        @media (max-width: 480px) {
            .forgot-card { padding: 2.5rem 1.5rem; }
        }
    </style>
</head>
<body>

<div class="bg-blob"></div>
<canvas id="bg-canvas"></canvas>

<div class="forgot-card">
    <div class="text-center anim-group">
        <div class="brand-icon-wrapper">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h4>Forgot Password?</h4>
        <p class="instruction">No worries! Enter your <b>username</b> and we'll help you recover your account.</p>
    </div>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger text-center alert-dismissible fade show animate__animated animate__shakeX" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success text-center alert-dismissible fade show animate__animated animate__backInDown" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="/cattleya/forgot-password-process" id="forgotForm" class="anim-group">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-group-custom">
                <input type="text" 
                       name="username" 
                       id="usernameInput"
                       class="form-control" 
                       placeholder="Enter your username" 
                       required>
                <i class="bi bi-person-badge"></i>
            </div>
        </div>
        
        <button type="submit" class="btn btn-submit" id="submitBtn">
            <span id="btnText">Verify Identity</span>
        </button>
    </form>

    <div class="text-center mt-4 footer-text anim-group">
        <a href="/cattleya/login">
            <i class="bi bi-arrow-left me-2"></i> Back to Sign In
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    /* --- PARTICLE ANIMATION --- */
    const canvas = document.getElementById('bg-canvas');
    const ctx = canvas.getContext('2d');
    let particles = [];
    const particleCount = 40; 

    function resize() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }

    window.addEventListener('resize', resize);
    resize();

    class Particle {
        constructor() { this.init(); }
        init() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.speedX = (Math.random() - 0.5) * 0.3;
            this.speedY = (Math.random() - 0.5) * 0.3;
            this.size = Math.random() * 2 + 1;
        }
        update() {
            this.x += this.speedX;
            this.y += this.speedY;
            if (this.x > canvas.width || this.x < 0) this.speedX *= -1;
            if (this.y > canvas.height || this.y < 0) this.speedY *= -1;
        }
        draw() {
            ctx.fillStyle = 'rgba(42, 98, 121, 0.15)'; // Teal particles
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fill();
        }
    }

    for (let i = 0; i < particleCount; i++) particles.push(new Particle());

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        particles.forEach((p, index) => {
            p.update();
            p.draw();
            for (let j = index + 1; j < particles.length; j++) {
                const dx = p.x - particles[j].x;
                const dy = p.y - particles[j].y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist < 200) {
                    ctx.strokeStyle = `rgba(42, 98, 121, ${0.1 - dist/200})`;
                    ctx.lineWidth = 0.5;
                    ctx.beginPath();
                    ctx.moveTo(p.x, p.y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.stroke();
                }
            }
        });
        requestAnimationFrame(animate);
    }
    animate();

    /* --- ENHANCED FORM LOGIC --- */
    const form = document.getElementById('forgotForm');
    const btn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');

    form.addEventListener('submit', function(e) {
        btn.disabled = true;
        btn.style.transform = 'scale(0.98)';
        btnText.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Checking Username...`;
        
        form.style.opacity = '0.7';
        form.style.pointerEvents = 'none';
    });

    const input = document.getElementById('usernameInput');
    input.addEventListener('focus', () => {
        document.querySelector('.brand-icon-wrapper').style.transform = 'scale(1.05) rotate(-5deg)';
    });
    input.addEventListener('blur', () => {
        document.querySelector('.brand-icon-wrapper').style.transform = '';
    });
</script>
</body>
</html>