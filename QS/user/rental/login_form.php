<?php
// 1. MUST BE THE VERY FIRST LINE
ob_start(); 
session_start();

// 2. Database Connection
include ('../../config/config.php');
date_default_timezone_set('Asia/Manila');
if (isset($_POST['submit'])) {
    // Sanitize input
    $login_input = mysqli_real_escape_string($conn, trim($_POST['email']));
    $passwordInput = $_POST['password'];

    // Search both columns to accommodate legacy (email) and new (username) data
    $query = "SELECT * FROM user_form WHERE username = '$login_input' OR email = '$login_input' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        
        // MD5 Hash Check (Case-insensitive comparison)
        if (md5($passwordInput) === strtolower($row['password'])) {
            
            // Check Account Status
            $status = strtolower($row['status'] ?? '');
            if ($status === 'inactive' || $status === 'retired') {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    window.onload = function() {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Account Inactive',
                            text: 'Your account is currently inactive or retired. Please contact the system administrator.',
                            confirmButtonColor: '#d70c0c',
                            confirmButtonText: 'Contact Administrator',
                            background: '#ffffff',
                            customClass: {
                                popup: 'rounded-4 shadow-lg border-0',
                                confirmButton: 'btn btn-danger px-4 py-2 rounded-3 fw-bold'
                            }
                        }).then(() => { window.location.href='login_form.php'; });
                    };
                </script>";
                exit();
            }
            // CRITERIA 1: Update last_online upon successful login
            $current_time = date('Y-m-d H:i:s');
            $user_db_id = $row['id'];
            mysqli_query($conn, "UPDATE user_form SET last_online = '$current_time' WHERE id = '$user_db_id'");
            // --- CRITICAL SESSION LOGIC FOR NAVBAR COMPATIBILITY ---
            
            /** * THE FIX: 
             * Your navbar queries 'WHERE username = ?'. 
             * If this is a legacy user, their "username" is actually in the 'email' column.
             * We must set the session to the value that exists in the DB so the Navbar query finds them.
             */
            // If new 'username' column is empty, use 'email' column as the session ID.
            $session_identifier = !empty($row['username']) ? $row['username'] : $row['email'];

            $_SESSION['user_email'] = $session_identifier; 
            $_SESSION['user_type']  = strtolower($row['user_type']);
            
            // Set these so they are available immediately even before navbar re-fetches
            $_SESSION['user_role']  = $row['roles']; 
            $_SESSION['mainzone']   = $row['mainzone'];
            $_SESSION['region']     = $row['region'];
            $_SESSION['area']       = $row['area'];
            
            $fullName = trim($row['first_name'] . " " . $row['middle_name'] . " " . $row['last_name']);

            // 3. REDIRECTS
            if ($_SESSION['user_type'] === 'admin') {
              $_SESSION['admin_name'] = $fullName;
              $_SESSION['admin_email'] = $session_identifier;
              
              // Added check for Admin default password as well
              if ($passwordInput === "Mlinc1234") {
                  echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                  <script>
                      window.onload = function() {
                          Swal.fire({
                              title: 'Security Requirement',
                              text: 'You are using a default password. You must change it to proceed.',
                              icon: 'warning',
                              confirmButtonColor: '#d70c0c',
                              customClass: {
                                  popup: 'rounded-4 shadow-lg border-0'
                              }
                          }).then(() => { window.location.href='change_password.php'; });
                      };
                  </script>";
                  exit();
              }

              header("Location: ../../admin/rental/admin_page.php");
              exit();
          } else {
              $_SESSION['user_name'] = $fullName;

              if ($passwordInput === "Mlinc1234") {
                  echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                  <script>
                      window.onload = function() {
                          Swal.fire({
                              title: 'Security Requirement',
                              text: 'You are using a default password. You must change it to proceed.',
                              icon: 'warning',
                              confirmButtonColor: '#d70c0c',
                              customClass: {
                                  popup: 'rounded-4 shadow-lg border-0'
                              }
                          }).then(() => { window.location.href='change_password.php'; });
                      };
                  </script>";
                  exit();
              } else {
                  header("Location: user_page.php");
                  exit();
              }
          }

        } else {
            $error_msg = "Incorrect password. Please try again.";
            $error_title = "Authentication Failed";
        }
    } else {
        // CONDITION 1: Explicit message and instruction to contact the administrator if user does not exist
        $error_title = "User Account Not Found";
        $error_msg = "This account does not exist in our system. Please check your username/email or contact the system administrator to request access.";
    }

    if (isset($error_msg)) {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
        <script>
            window.onload = function() {
                Swal.fire({
                    icon: 'error',
                    title: '" . ($error_title ?? "Access Denied") . "',
                    text: '$error_msg',
                    confirmButtonColor: '#d70c0c',
                    confirmButtonText: 'Understood',
                    backdrop: `rgba(15, 23, 42, 0.8)`,
                    customClass: {
                        popup: 'rounded-4 shadow-lg border-0 modern-swal-popup'
                    }
                });
            };
        </script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ML Rental System - Secure Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <link rel="icon" href="../../images/MLW logo.png" type="image/png" />
  
  <style>
    :root {
      --primary-brand: #d70c0c;
      --primary-hover: #b30a0a;
      --bg-dark: #0f172a;
      --card-bg: rgba(255, 255, 255, 0.96);
    }

    body, html {
      margin: 0; padding: 0; height: 100%;
      font-family: 'Poppins', sans-serif;
      overflow: hidden;
      background-color: var(--bg-dark);
    }

    #particles-js { 
      position: absolute; 
      width: 100%; 
      height: 100%; 
      z-index: 1; 
      background: radial-gradient(circle at 50% 50%, #1e293b 0%, #0f172a 100%);
    }

    .login-wrapper { 
      position: relative; 
      z-index: 2; 
      height: 100vh; 
      display: flex; 
      align-items: center; 
      justify-content: center;
      padding: 20px;
    }

    /* Ambient Glow behind login card */
    .login-card-container {
      position: relative;
      width: 100%;
      max-width: 450px;
    }

    .login-card-container::before {
      content: '';
      position: absolute;
      top: -10px; left: -10px; right: -10px; bottom: -10px;
      background: radial-gradient(circle, rgba(215, 12, 12, 0.25) 0%, transparent 70%);
      filter: blur(25px);
      z-index: -1;
      border-radius: 35px;
    }

    /* Modern Glassmorphism & Enhanced Aesthetic Card */
    .login-card {
      position: relative;
      background: var(--card-bg);
      backdrop-filter: blur(25px);
      -webkit-backdrop-filter: blur(25px);
      border-radius: 28px;
      padding: 45px 40px;
      width: 100%;
      box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(255, 255, 255, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.5);
      transition: all 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
      overflow: hidden;
    }

    .login-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 35px 70px -15px rgba(0, 0, 0, 0.75), 0 0 30px rgba(215, 12, 12, 0.2);
    }

    .logo-container img {
      transition: transform 0.4s ease;
      filter: drop-shadow(0 4px 10px rgba(0,0,0,0.15));
    }

    .logo-container img:hover {
      transform: scale(1.05) rotate(-2deg);
    }

    .form-label { 
      font-weight: 600; 
      font-size: 0.75rem; 
      text-transform: uppercase; 
      letter-spacing: 0.9px; 
      color: #475569; 
      margin-bottom: 8px; 
    }

    .input-group {
      border: 1.5px solid #cbd5e1;
      border-radius: 16px;
      background: rgba(248, 250, 252, 0.9);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: hidden;
    }

    .input-group:focus-within {
      border-color: var(--primary-brand);
      background: #ffffff;
      box-shadow: 0 0 0 4px rgba(215, 12, 12, 0.18);
    }

    .input-group-text { 
      background: transparent; 
      border: none; 
      color: #64748b; 
      padding-left: 18px; 
      transition: color 0.3s ease;
    }

    .input-group:focus-within .input-group-text {
      color: var(--primary-brand);
    }
    
    .form-control { 
      border: none; 
      padding: 14px 14px 14px 5px; 
      font-size: 0.95rem; 
      background: transparent; 
      color: #1e293b; 
      font-weight: 500;
    }
    
    .form-control:focus { 
      box-shadow: none; 
      background: transparent; 
    }

    .btn-brand {
      background: linear-gradient(135deg, var(--primary-brand) 0%, var(--primary-hover) 100%);
      color: white; 
      border: none; 
      padding: 16px;
      border-radius: 16px; 
      font-weight: 700;
      letter-spacing: 1px; 
      transition: all 0.35s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      margin-top: 10px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 10px 20px -5px rgba(215, 12, 12, 0.4);
    }

    .btn-brand:hover {
      background: linear-gradient(135deg, #f01414 0%, #990808 100%);
      transform: translateY(-2px);
      box-shadow: 0 15px 30px -5px rgba(215, 12, 12, 0.55);
      color: white;
    }

    .btn-brand:active {
      transform: translateY(0);
    }

    .back-link { 
      color: #64748b; 
      font-size: 0.85rem; 
      font-weight: 600; 
      text-decoration: none; 
      transition: all 0.3s ease; 
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    
    .back-link:hover { 
      color: var(--primary-brand); 
      transform: translateX(-3px);
    }

    /* Modern Password Eye Toggle Button */
    .toggle-pass-btn {
      background: none; 
      border: none; 
      color: #64748b; 
      padding-right: 18px;
      transition: color 0.3s ease;
      cursor: pointer;
    }

    .toggle-pass-btn:hover {
      color: var(--primary-brand);
    }

    /* Enhanced Modal Aesthetic */
    .modal-backdrop.show {
      backdrop-filter: blur(8px);
      background-color: rgba(15, 23, 42, 0.7);
    }

    .modal-content { 
      border-radius: 28px; 
      border: 1px solid rgba(255, 255, 255, 0.2); 
      box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.6);
      background: #ffffff;
      overflow: hidden;
    }
    
    .modal-header-custom {
      background: linear-gradient(135deg, var(--primary-brand) 0%, #990808 100%);
      color: white;
      padding: 28px 24px;
      border-bottom: none;
      position: relative;
    }

    .modal-header-custom::after {
      content: '';
      position: absolute;
      bottom: 0; left: 0; right: 0;
      height: 4px;
      background: rgba(255, 255, 255, 0.2);
    }

    .modal-icon-badge {
      width: 60px;
      height: 60px;
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(10px);
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 12px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
    }

    .password-input-alt { 
      background: #f8fafc !important; 
      border: 1.5px solid #e2e8f0 !important; 
      border-radius: 14px !important; 
      padding: 14px 16px !important;
      font-size: 0.95rem;
      transition: all 0.25s ease;
    }

    .password-input-alt:focus {
      border-color: var(--primary-brand) !important;
      box-shadow: 0 0 0 4px rgba(215, 12, 12, 0.15) !important;
      background: #ffffff !important;
    }

    /* Enhanced Modern Progress Bar Overlay during Login */
    #progressOverlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: rgba(215, 12, 12, 0.08);
      z-index: 20;
      display: none;
      overflow: hidden;
    }

    #progressBar {
      width: 0%;
      height: 100%;
      background: linear-gradient(90deg, var(--primary-brand) 0%, #ff5252 50%, var(--primary-brand) 100%);
      background-size: 200% 100%;
      animation: gradientGlow 1.5s infinite linear;
      transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 0 10px rgba(215, 12, 12, 0.8);
    }

    @keyframes gradientGlow {
      0% { background-position: 0% 0%; }
      100% { background-position: 200% 0%; }
    }

    /* Custom SweetAlert modern look matching theme */
    .modern-swal-popup {
      font-family: 'Poppins', sans-serif !important;
      border-radius: 24px !important;
      padding: 25px !important;
    }
  </style>
</head>

<body>
  <div id="particles-js"></div>

  <div class="login-wrapper">
    <div class="login-card-container">
      <div class="login-card animate__animated animate__zoomIn">
        <!-- Smooth Progress Bar Indicator -->
        <div id="progressOverlay">
          <div id="progressBar"></div>
        </div>

        <div class="text-center mb-4">
          <div class="logo-container mb-3 animate__animated animate__fadeInDown">
            <img src="../../assets/images/ml_logo.png" alt="Logo" width="105" />
          </div>
          <h3 class="fw-bold text-dark mb-1">Secure Login</h3>
          <p class="text-muted small">Enter your credentials to access the portal</p>
        </div>

        <form method="POST" id="loginForm" onsubmit="startLoginProgress()">
          <div class="mb-3">
            <label class="form-label">Username / Email</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-user-tag"></i></span>
              <input type="text" name="email" class="form-control" placeholder="Enter Username or Email" required autocomplete="username" />
            </div>
          </div>
          
          <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fa-solid fa-shield-halved"></i></span>
              <input type="password" name="password" id="passInput" class="form-control" placeholder="••••••••" required autocomplete="current-password" />
              <button type="button" class="toggle-pass-btn" onclick="togglePassword()" aria-label="Toggle password visibility">
                <i class="fa-solid fa-eye" id="eyeIcon"></i>
              </button>
            </div>
          </div>

          <div class="d-grid mb-4">
            <button type="submit" name="submit" id="submitBtn" class="btn btn-brand">
              <span>AUTHENTICATE</span> <i class="fa-solid fa-paper-plane ms-2 small"></i>
            </button>
          </div>

          <div class="text-center">
            <a href="../../index.php" class="back-link">
              <i class="fa-solid fa-chevron-left"></i> Exit to Homepage
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

<!-- Modern Password Change Modal -->
<div class="modal fade" id="changePasswordModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content animate__animated animate__bounceIn">
        <div class="modal-header-custom text-center">
          <div class="modal-icon-badge">
            <i class="fas fa-lock"></i>
          </div>
          <h5 class="modal-title fw-bold w-100 mb-0">Account Security Update</h5>
          <p class="text-white-50 small mb-0 mt-1">First-time login setup required</p>
        </div>
        <form action="change_password.php" method="post">
            <div class="modal-body p-4 p-lg-4">
              <div class="text-center mb-4">
                  <h6 class="fw-bold text-dark">Set a New Secure Password</h6>
                  <p class="text-muted small mb-0">Your account is using a default password. Please create a unique one to proceed securely.</p>
              </div>
              <div class="mb-3">
                  <label class="form-label text-dark">New Password</label>
                  <input type="password" class="form-control password-input-alt" name="newPassword" placeholder="Enter new secure password" required>
              </div>
              <div class="mb-2">
                  <label class="form-label text-dark">Confirm New Password</label>
                  <input type="password" class="form-control password-input-alt" name="confirmPassword" placeholder="Re-enter password" required>
              </div>
            </div>
            <div class="modal-footer p-4 pt-0 border-0">
              <div class="w-100 d-flex gap-2">
                  <button type="button" class="btn btn-light w-50 py-3 fw-semibold rounded-4" data-bs-dismiss="modal">Later</button>
                  <button type="submit" name="submitNewPass" class="btn btn-danger w-50 py-3 fw-bold rounded-4 shadow-sm" style="background: linear-gradient(135deg, var(--primary-brand) 0%, var(--primary-hover) 100%); border: none;">
                    Update Now
                  </button>
              </div>
            </div>
        </form>
    </div>
  </div>
</div>

<script>window.LAST_ONLINE_ENDPOINT = '../../fetch/last_online.php';</script>
<script src="../../assets/js/last-online-tracker.js"></script>
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  function togglePassword() {
    const input = document.getElementById('passInput');
    const icon = document.getElementById('eyeIcon');
    if (input.type === "password") {
      input.type = "text";
      icon.classList.replace("fa-eye", "fa-eye-slash");
    } else {
      input.type = "password";
      icon.classList.replace("fa-eye-slash", "fa-eye");
    }
  }

  // Smooth & Realistic Dynamic Progress Bar Functionality
  function startLoginProgress() {
    const progressOverlay = document.getElementById('progressOverlay');
    const progressBar = document.getElementById('progressBar');
    const submitBtn = document.getElementById('submitBtn');

    progressOverlay.style.display = 'block';
    submitBtn.style.opacity = '0.85';
    submitBtn.style.pointerEvents = 'none';
    submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>VERIFYING...`;

    let currentWidth = 0;
    progressBar.style.width = '0%';

    const interval = setInterval(() => {
      if (currentWidth >= 92) {
        clearInterval(interval);
      } else {
        // Smooth logarithmic step increment
        const increment = Math.max(1, Math.floor((100 - currentWidth) * 0.15));
        currentWidth += increment;
        progressBar.style.width = currentWidth + '%';
      }
    }, 80);
  }

  // --- Spider Web Animation (Particles.js) ---
  if (typeof particlesJS !== 'undefined') {
    particlesJS("particles-js", {
      "particles": {
        "number": { "value": 90, "density": { "enable": true, "value_area": 800 } },
        "color": { "value": "#d70c0c" },
        "shape": { "type": "circle" },
        "opacity": { "value": 0.5, "random": false },
        "size": { "value": 3, "random": true },
        "line_linked": {
          "enable": true,
          "distance": 150,
          "color": "#d70c0c",
          "opacity": 0.35,
          "width": 1.2
        },
        "move": {
          "enable": true,
          "speed": 2,
          "direction": "none",
          "random": false,
          "straight": false,
          "out_mode": "out",
          "bounce": false
        }
      },
      "interactivity": {
        "detect_on": "canvas",
        "events": {
          "onhover": { "enable": true, "mode": "grab" },
          "onclick": { "enable": true, "mode": "push" },
          "resize": true
        },
        "modes": {
          "grab": { "distance": 180, "line_linked": { "opacity": 0.8 } },
          "push": { "particles_nb": 4 }
        }
      },
      "retina_detect": true
    });
  }
</script>
</body>
</html>