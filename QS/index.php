<?php include 'config/config.php'; ?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ML Rental Management System | Enterprise Console</title>
<link rel="shortcut icon" href="assets/images/mlw-logo-96x96.png" type="image/x-icon">

<!-- Core Fonts & Stylesheets -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="assets/css/poppins.css" rel="stylesheet">
<link href="assets/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/icons/bootstrap-icons.css" rel="stylesheet">
<link href="assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">

<!-- Custom Enterprise CSS -->
<link rel="stylesheet" href="index.css?v=<?= time(); ?>">
<link rel="stylesheet" href="assets/css/loading.css?v=<?= time(); ?>">

<!-- Immediate Theme Loader (Prevent Flash of Unstyled Theme) -->
<script>
  (function() {
    const savedTheme = localStorage.getItem('ml_theme') || 'dark';
    document.documentElement.setAttribute('data-theme', savedTheme);
  })();
</script>
</head>
<body>

<!-- Header Command Navigation -->
<header class="header-navigation">
  <div class="container-fluid max-ww-1200 d-flex align-items-center justify-content-between">
    <a href="#" class="d-flex align-items-center text-decoration-none">
      <img src="assets/images/ml_logo.png" alt="ML Logo" class="navbar-brand-logo">
    </a>

    <div class="d-flex align-items-center gap-3">
      <div class="status-pill d-none d-sm-inline-flex">
        <span class="status-dot"></span> System Operational
      </div>
      
      <!-- Theme Switch Button -->
      <button class="btn-theme-toggle" id="themeToggleBtn" aria-label="Toggle theme" title="Switch Day/Night Mode">
        <i class="bi bi-moon-stars-fill" id="themeToggleIcon"></i>
      </button>

      <a href="#" class="btn-header-action" id="branchProfileLink">
        <i class="bi bi-shield-lock-fill text-danger"></i>
        <span>Branch Profile</span>
      </a>
    </div>
  </div>
</header>

<!-- Main Command Hero Section -->
<main class="hero-wrapper">
  <div class="hero-glow-backdrop"></div>
  
  <!-- Interactive Particle Grid Background -->
  <canvas id="heroCanvas" style="position:absolute; inset:0; width:100%; height:100%; z-index:0; pointer-events:auto;"></canvas>

  <div class="container position-relative" style="z-index: 1;">
    
    <!-- Hero Headline (Centered SaaS Architecture) -->
    <div class="text-center max-w-800 mx-auto mb-5">
      <div class="hero-tag mb-3">
        <i class="bi bi-cpu-fill me-1"></i> Enterprise Edition 6.1
      </div>
      
      <h1 class="hero-heading mb-3">
        Centralized Platform for <br>
        <span class="text-gradient">ML Rental Management System</span>
      </h1>
      
      <p class="hero-description mx-auto mb-4">
        Engineered for real-time lease tracking, branch property management, automated tax engine workflows, and executive analytics.
      </p>

      <div class="d-flex justify-content-center gap-3">
        <a href="user/rental/login_form.php" class="btn-launch">
          <span>Login to System</span>
          <i class="bi bi-arrow-right"></i>
        </a>
      </div>
    </div>

    <!-- Live Preview Console Mockup -->
    <div class="row justify-content-center mb-5">
      <div class="col-lg-10">
        <div class="console-frame">
          <div class="console-header">
            <div class="console-dots">
              <span class="console-dot"></span>
              <span class="console-dot"></span>
              <span class="console-dot"></span>
            </div>
            <div class="console-title-text">ml_rental_console_v6.1 // system_active</div>
            <div class="text-muted small"><i class="bi bi-wifi text-success me-1"></i> Connected</div>
          </div>

          <div class="console-metrics-grid">
            <div class="metric-card">
              <div class="metric-val text-danger">100%</div>
              <div class="metric-lbl">Automated WTax</div>
            </div>
            <div class="metric-card">
              <div class="metric-val">Real-Time</div>
              <div class="metric-lbl">Audit Logs</div>
            </div>
            <div class="metric-card">
              <div class="metric-val">24/7</div>
              <div class="metric-lbl">High-Availability</div>
            </div>
            <div class="metric-card">
              <div class="metric-val text-success">Active</div>
              <div class="metric-lbl">Multi-Branch Sync</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 4-Column Horizontal Feature Row -->
    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="feature-card-item">
          <div class="feature-icon-wrapper">
            <i class="bi bi-buildings-fill"></i>
          </div>
          <h6>Branch Management</h6>
          <p>Centralized database to control, update, and manage lease assets across regions.</p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="feature-card-item">
          <div class="feature-icon-wrapper">
            <i class="bi bi-file-earmark-code-fill"></i>
          </div>
          <h6>Contract Lifecycle</h6>
          <p>Track lease expiration dates, renewal timelines, and agreement terms effortlessly.</p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="feature-card-item">
          <div class="feature-icon-wrapper">
            <i class="bi bi-calculator-fill"></i>
          </div>
          <h6>Payment Engine</h6>
          <p>Automatic calculation of VAT, withholding taxes, escalations, and net payables.</p>
        </div>
      </div>

      <div class="col-md-6 col-lg-3">
        <div class="feature-card-item">
          <div class="feature-icon-wrapper">
            <i class="bi bi-bar-chart-line-fill"></i>
          </div>
          <h6>Audit & Analytics</h6>
          <p>Generate high-level summaries and comprehensive audit reports with a single click.</p>
        </div>
      </div>
    </div>

  </div>
</main>

<!-- Dark Grid Canvas Animation -->
<script>
const canvas = document.getElementById('heroCanvas');
const ctx = canvas.getContext('2d');
let width, height;

function resizeCanvas() {
    width = canvas.width = canvas.offsetWidth;
    height = canvas.height = canvas.offsetHeight;
}
window.addEventListener('resize', resizeCanvas);
resizeCanvas();

const particles = [];
const particleCount = 55;

for(let i = 0; i < particleCount; i++) {
    particles.push({
        x: Math.random() * width,
        y: Math.random() * height,
        r: Math.random() * 2 + 1,
        dx: (Math.random() - 0.5) * 0.3,
        dy: (Math.random() - 0.5) * 0.3
    });
}

function drawParticles() {
    ctx.clearRect(0, 0, width, height);
    particles.forEach(p => {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(244, 63, 94, 0.4)';
        ctx.fill();
    });

    for(let i = 0; i < particles.length; i++) {
        for(let j = i + 1; j < particles.length; j++) {
            let dx = particles[i].x - particles[j].x;
            let dy = particles[i].y - particles[j].y;
            let dist = Math.sqrt(dx * dx + dy * dy);
            if(dist < 120) {
                ctx.beginPath();
                ctx.strokeStyle = `rgba(244, 63, 94, ${0.25 * (1 - dist / 120)})`;
                ctx.lineWidth = 0.5;
                ctx.moveTo(particles[i].x, particles[i].y);
                ctx.lineTo(particles[j].x, particles[j].y);
                ctx.stroke();
            }
        }
    }
}

function updateParticles() {
    particles.forEach(p => {
        p.x += p.dx;
        p.y += p.dy;
        if(p.x < 0 || p.x > width) p.dx *= -1;
        if(p.y < 0 || p.y > height) p.dy *= -1;
    });
    drawParticles();
    requestAnimationFrame(updateParticles);
}
updateParticles();

canvas.addEventListener('mousemove', e => {
    const mouseX = e.offsetX;
    const mouseY = e.offsetY;
    particles.forEach(p => {
        let dx = p.x - mouseX;
        let dy = p.y - mouseY;
        let dist = Math.sqrt(dx * dx + dy * dy);
        if(dist < 100) {
            p.dx += dx * 0.0004;
            p.dy += dy * 0.0004;
        }
    });
});
</script>

<!-- Dark Glass Loading Overlay -->
<div id="loadingModal" class="modal-backdrop-custom">
  <div class="modal-box text-center">
    <div class="mb-3">
      <div class="spinner-border text-danger" style="width: 2.75rem; height: 2.75rem;" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
    <div class="fw-bold text-light fs-5 mb-1" id="loaderText">Initializing Console</div>
    <div class="text-muted small">ML Rental System &copy; 2025</div>
  </div>
</div>

<!-- Footer -->
<footer class="text-center">
  <div class="container">
    <p class="mb-0">&copy; <?= date('Y'); ?> ML Rental Management System. All rights reserved.</p>
  </div>
</footer>

<!-- Core JS Imports -->
<script src="assets/sweetalert2/dist/sweetalert2.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>

<script>
// Day & Night Theme Toggle Logic
const themeToggleBtn = document.getElementById('themeToggleBtn');
const themeToggleIcon = document.getElementById('themeToggleIcon');

function updateThemeIcon(theme) {
  if (theme === 'light') {
    themeToggleIcon.classList.remove('bi-moon-stars-fill');
    themeToggleIcon.classList.add('bi-sun-fill');
  } else {
    themeToggleIcon.classList.remove('bi-sun-fill');
    themeToggleIcon.classList.add('bi-moon-stars-fill');
  }
}

// Set Icon on initial render
updateThemeIcon(document.documentElement.getAttribute('data-theme') || 'dark');

themeToggleBtn.addEventListener('click', () => {
  const currentTheme = document.documentElement.getAttribute('data-theme');
  const newTheme = currentTheme === 'light' ? 'dark' : 'light';
  
  document.documentElement.setAttribute('data-theme', newTheme);
  localStorage.setItem('ml_theme', newTheme);
  updateThemeIcon(newTheme);
});

// Secure Password Modal Logic for Branch Profile Link
document.getElementById('branchProfileLink').onclick = function (event) {
  event.preventDefault();
  Swal.fire({
    title: `<div class="fs-4 fw-bold text-dark"><i class="bi bi-shield-lock-fill text-danger me-2"></i>Secure Access</div>`,
    html: `<p class="mb-3 text-muted" style="font-size: 13px;">Please enter the password to access <strong>Branch Profile Management</strong>.</p>
           <input type="password" id="modalPassword" class="swal2-input" placeholder="Enter password" autocomplete="off" style="border-radius: 12px; border: 1px solid #cbd5e1; font-size: 14px;">`,
    background: '#fff',
    color: '#333',
    showCancelButton: true,
    confirmButtonText: '<i class="bi bi-check-circle me-2"></i>Submit',
    cancelButtonText: '<i class="bi bi-x-circle me-2"></i>Cancel',
    confirmButtonColor: '#f43f5e',
    cancelButtonColor: '#64748b',
    allowOutsideClick: false,
    customClass: {
      popup: 'rounded-4 shadow-lg p-4 animate__animated animate__fadeInDown',
      confirmButton: 'btn btn-danger rounded-pill px-4 fw-semibold',
      cancelButton: 'btn btn-secondary rounded-pill px-4 fw-semibold',
      htmlContainer: 'mb-2'
    },
    preConfirm: () => {
      const password = document.getElementById('modalPassword').value.trim();
      if (!password) Swal.showValidationMessage('Password is required');
      return password;
    }
  }).then((result) => {
    if (result.isConfirmed) {
      if (btoa(result.value) === 'Q0FEDUxodWlsbGllckRCMjAyMw==') {
          Swal.fire({
            html: `<div class="d-flex flex-column align-items-center animate__animated animate__fadeInDown">
                    <div class="bg-success rounded-circle d-flex justify-content-center align-items-center shadow-sm" style="width: 80px; height: 80px;">
                      <i class="bi bi-check-circle-fill text-white fs-2"></i>
                    </div>
                    <h4 class="fw-bold text-dark mt-3 mb-1">Access Granted!</h4>
                    <p class="text-muted mb-2">You’ve successfully entered the secure area.</p>
                  </div>`,
            showConfirmButton: false,
            timer: 1300,
            timerProgressBar: true,
            customClass: {popup: 'rounded-4 shadow-sm p-3 animate__animated animate__zoomIn'}
          });
          setTimeout(() => { window.location.href = 'admin/rental/qs_branch_profile.php'; }, 1400);
      } else {
        Swal.fire({
          html:`<div class="text-center animate__animated animate__shakeX">
                  <div class="bg-danger rounded-circle d-flex justify-content-center align-items-center mx-auto mb-3 shadow-sm" style="width: 70px; height: 70px;">
                    <i class="bi bi-x-lg text-white fs-2"></i>
                  </div>
                  <h5 class="text-danger fw-bold mb-2">Incorrect Password</h5>
                  <p class="text-muted mb-1">Please try again.</p>
                </div>`,
          confirmButtonText:'Try Again',
          confirmButtonColor:'#f43f5e',
          buttonsStyling:false,
          customClass:{popup:'rounded-4 shadow-sm p-3', confirmButton:'btn btn-danger rounded-pill px-4 fw-semibold'}
        });
      }
    }
  });
};

// Loader Fade Out Logic
window.onload = function () {
  const loader = document.getElementById('loadingModal');
  if (loader) {
    loader.classList.add('modal-hidden');
    setTimeout(() => loader.style.display = 'none', 400);
  }
};

// System Security Features
document.addEventListener('contextmenu', (e) => e.preventDefault());

document.onkeydown = function(e) {
    if (e.keyCode == 123) return false;
    if (e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) return false;
    if (e.ctrlKey && e.shiftKey && e.keyCode == 'J'.charCodeAt(0)) return false;
    if (e.ctrlKey && e.shiftKey && e.keyCode == 'C'.charCodeAt(0)) return false;
    if (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false;
};

(function() {
    var protect = function() {
        try {
            (function() {
                var handler = function() {
                    debugger;
                };
                setInterval(handler, 100);
            })();
        } catch (e) {}
    };
    protect();
})();
</script>

</body>
</html>