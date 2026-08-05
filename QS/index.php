<?php include 'config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Welcome to ML Rental Management System</title>
<link rel="shortcut icon" href="assets/images/mlw-logo-96x96.png" type="image/x-icon">

<!-- Google Font & Bootstrap -->
<link href="assets/css/poppins.css" rel="stylesheet">
<link href="assets/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/icons/bootstrap-icons.css" rel="stylesheet">
<link href="assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">

<!-- Custom CSS -->
<link rel="stylesheet" href="index.css?v=<?= time(); ?>">
<link rel="stylesheet" href="assets/css/loading.css?v=<?= time(); ?>">

<style>
/* Navbar */
.navbar{
background: rgba(255,255,255,0.95);
backdrop-filter: blur(10px);
box-shadow:0 4px 25px rgba(0,0,0,0.05);
padding:12px 0;
}

.navbar .logo{
height:50px;
transition: transform .3s ease;
}
.navbar .logo:hover{
transform: scale(1.05);
}

.navbar-nav .nav-link{
font-weight:500;
transition: all .2s ease;
padding:8px 14px;
border-radius:6px;
}
.navbar-nav .nav-link:hover{
background:#fff3f3;
color:#d70c0c !important;
transform: translateY(-2px);
}

/* Hero Section */
.hero{
background: linear-gradient(135deg, #ffe3e3, #fff);
padding:100px 0;
min-height:85vh;
position: relative;
overflow: hidden;
}
.hero h1{
font-weight:700;
line-height:1.2;
}
.hero .btn-danger{
border-radius:50px;
transition: transform .2s ease;
}
.hero .btn-danger:hover{
transform: scale(1.05);
box-shadow: 0 8px 20px rgba(215,12,12,0.3);
}

/* Feature Cards */
.hero .feature-card{
background:#fff;
border-radius:20px;
box-shadow:0 10px 25px rgba(0,0,0,0.08);
padding:30px 20px;
transition: all .3s ease;
cursor:pointer;
}
.hero .feature-card:hover{
transform: translateY(-5px);
box-shadow:0 15px 35px rgba(0,0,0,0.12);
}
.hero .feature-card i{
transition: transform .3s ease, color .3s ease;
}
.hero .feature-card:hover i{
color:#d70c0c;
transform: scale(1.2) rotate(10deg);
}

/* Loading Modal */
.modal-backdrop-custom{
position:fixed;
top:0;left:0;width:100%;height:100%;
background:rgba(255,255,255,0.95);
display:flex;
align-items:center;
justify-content:center;
z-index:9999;
transition: opacity .4s ease;
}
.modal-hidden{opacity:0;}

/* Footer */
footer{
background:#fff;
color:#888;
border-top:1px solid #eee;
font-size:11px;
}

/* Responsive adjustments */
@media (max-width:768px){
.hero{padding:60px 15px;}
.hero .feature-card{margin-bottom:20px;}
}
</style>

</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-md px-4">
  <img src="assets/images/ml_logo.png" alt="logo" class="logo me-3">
  <div class="collapse navbar-collapse">
    <ul class="navbar-nav ms-auto">
      <li class="nav-item">
        <a class="nav-link fw-semibold" href="#" id="branchProfileLink">
          <i class="bi bi-shield-lock me-1"></i>Branch Profile
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#"><i class="bi bi-list"></i></a>
      </li>
    </ul>
  </div>
</nav>
<!-- Hero Section -->
<section class="hero position-relative">
  <!-- Canvas for animated background -->
  <canvas id="heroCanvas" style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:0;"></canvas>

  <div class="container position-relative" style="z-index:1;">
    <div class="row align-items-center justify-content-center">
      
      <!-- Text Content -->
      <div class="col-md-6 text-center text-md-start mb-5 mb-md-0 animate__animated animate__fadeInLeft">
        <h1 class="display-5 fw-bold">ML Rental Management System</h1>
        <p class="mt-2 mb-3"><span class="badge bg-dark">Version 6.0</span></p>
        <p class="lead text-muted fs-6">One-stop solution for managing branches, contracts, reports, and more — designed to scale with your business.</p>
        <div class="d-flex gap-3 mt-4">
          <a href="user/rental/login_form.php" class="btn btn-danger px-4 py-2">
            <i class="bi bi-box-arrow-in-right me-1"></i> Login to System
          </a>
        </div>
      </div>

      <!-- Feature Cards -->
      <div class="col-md-6 text-center animate__animated animate__fadeInRight">
        <div class="row g-3">
          <div class="col-6">
            <div class="feature-card">
              <i class="bi bi-buildings fs-1 text-danger"></i>
              <h6 class="mt-3 fw-semibold">Branch Management</h6>
              <p class="text-muted small">Manage all rental locations in one dashboard.</p>
            </div>
          </div>
          <div class="col-6">
            <div class="feature-card">
              <i class="bi bi-file-earmark-text fs-1 text-danger"></i>
              <h6 class="mt-3 fw-semibold">Contract Tracking</h6>
              <p class="text-muted small">Monitor lease terms, renewals, and history.</p>
            </div>
          </div>
          <div class="col-6">
            <div class="feature-card">
              <i class="bi bi-cash-coin fs-1 text-danger"></i>
              <h6 class="mt-3 fw-semibold">Payment Records</h6>
              <p class="text-muted small">Auto-calculate VAT, WTax, escalation and net payable.</p>
            </div>
          </div>
          <div class="col-6">
            <div class="feature-card">
              <i class="bi bi-graph-up-arrow fs-1 text-danger"></i>
              <h6 class="mt-3 fw-semibold">Reports & Analytics</h6>
              <p class="text-muted small">Generate detailed and summary reports.</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- Add this script at the end of the body -->
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

// Particle setup
const particles = [];
const particleCount = 80;

for(let i=0;i<particleCount;i++){
    particles.push({
        x: Math.random()*width,
        y: Math.random()*height,
        r: Math.random()*2 + 1,
        dx: (Math.random()-0.5)*0.5,
        dy: (Math.random()-0.5)*0.5
    });
}

function drawParticles(){
    ctx.clearRect(0,0,width,height);
    particles.forEach(p=>{
        // Draw particle
        ctx.beginPath();
        ctx.arc(p.x,p.y,p.r,0,Math.PI*2);
        ctx.fillStyle = 'rgba(215,12,12,0.7)';
        ctx.fill();
    });
    // Draw lines
    for(let i=0;i<particles.length;i++){
        for(let j=i+1;j<particles.length;j++){
            let dx = particles[i].x - particles[j].x;
            let dy = particles[i].y - particles[j].y;
            let dist = Math.sqrt(dx*dx + dy*dy);
            if(dist<100){
                ctx.beginPath();
                ctx.strokeStyle = `rgba(215,12,12,${1-dist/100})`;
                ctx.lineWidth = 0.5;
                ctx.moveTo(particles[i].x, particles[i].y);
                ctx.lineTo(particles[j].x, particles[j].y);
                ctx.stroke();
            }
        }
    }
}

function updateParticles(){
    particles.forEach(p=>{
        p.x += p.dx;
        p.y += p.dy;
        if(p.x<0 || p.x>width) p.dx*=-1;
        if(p.y<0 || p.y>height) p.dy*=-1;
    });
    drawParticles();
    requestAnimationFrame(updateParticles);
}
updateParticles();

// Optional: Mouse interaction
canvas.addEventListener('mousemove', e=>{
    const mouseX = e.offsetX;
    const mouseY = e.offsetY;
    particles.forEach(p=>{
        let dx = p.x - mouseX;
        let dy = p.y - mouseY;
        let dist = Math.sqrt(dx*dx + dy*dy);
        if(dist<80){
            p.dx += dx*0.0005;
            p.dy += dy*0.0005;
        }
    });
});
</script>
<!-- Loading Modal -->
<div id="loadingModal" class="modal-backdrop-custom">
  <div class="modal-box text-center">
    <div class="loading-icon mb-3">
      <i class="bi bi-arrow-repeat fs-1 text-danger spinner-border"></i>
    </div>
    <div class="loading-text mb-2">Please wait while we load your content...</div>
    <div class="brand text-muted">ML Rental System &copy; 2025</div>
  </div>
</div>

<!-- Footer -->
<footer class="text-center py-4">
  &copy; <?= date('Y'); ?> ML Rental Management System. All rights reserved.
</footer>

<!-- JS -->
<script src="assets/sweetalert2/dist/sweetalert2.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>

<script>
// Branch Profile Access Modal
document.getElementById('branchProfileLink').onclick = function (event) {
  event.preventDefault();
  Swal.fire({
    title: `<div class="fs-4 fw-semibold text-dark"><i class="bi bi-shield-lock text-danger me-2"></i>Secure Access</div>`,
    html: `<p class="mb-2 text-muted" style="font-size: 13px;">Please enter the password to access <strong>Branch Profile Management</strong>.</p>
           <input type="password" id="modalPassword" class="swal2-input" placeholder="Enter password" autocomplete="off" style="border-radius: 12px;">`,
    background: '#fff',
    color: '#333',
    showCancelButton: true,
    confirmButtonText: '<i class="bi bi-check-circle me-2"></i>Submit',
    cancelButtonText: '<i class="bi bi-x-circle me-2"></i>Cancel',
    confirmButtonColor: '#d70c0c',
    cancelButtonColor: '#6c757d',
    allowOutsideClick: false,
    customClass: {
      popup: 'rounded-4 shadow-sm p-3 animate__animated animate__fadeInDown',
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
      if (btoa(result.value) === 'Q0FETUxodWlsbGllckRCMjAyMw==') {
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
      }else {
        Swal.fire({
          html:`<div class="text-center animate__animated animate__shakeX">
                  <div class="bg-danger rounded-circle d-flex justify-content-center align-items-center mx-auto mb-3 shadow-sm" style="width: 70px; height: 70px;">
                    <i class="bi bi-x-lg text-white fs-2"></i>
                  </div>
                  <h5 class="text-danger fw-bold mb-2">Incorrect Password</h5>
                  <p class="text-muted mb-1">Please try again.</p>
                </div>`,
          confirmButtonText:'Try Again',
          confirmButtonColor:'#d70c0c',
          buttonsStyling:false,
          customClass:{popup:'rounded-4 shadow-sm p-3', confirmButton:'btn btn-danger rounded-pill px-4 fw-semibold'}
        });
      }
    }
  });
};

// Loader fade-out
window.onload = function () {
  const loader = document.getElementById('loadingModal');
  if (loader) {
    loader.classList.add('modal-hidden');
    setTimeout(()=>loader.style.display='none',400);
  }
};

// 1. Disable Right-Click (Context Menu)
document.addEventListener('contextmenu', (e) => e.preventDefault());

// 2. Disable Keyboard Shortcuts
document.onkeydown = function(e) {
    // F12
    if (e.keyCode == 123) return false;
    
    // Ctrl+Shift+I (Inspect)
    if (e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) return false;
    
    // Ctrl+Shift+J (Console)
    if (e.ctrlKey && e.shiftKey && e.keyCode == 'J'.charCodeAt(0)) return false;
    
    // Ctrl+Shift+C (Element Selector)
    if (e.ctrlKey && e.shiftKey && e.keyCode == 'C'.charCodeAt(0)) return false;
    
    // Ctrl+U (View Source)
    if (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false;
};

// 3. The Debugger Trap
// If the console is opened, this loop will trigger the debugger, 
// effectively "freezing" the user's ability to browse the code.
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