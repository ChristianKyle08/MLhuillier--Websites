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
                            confirmButtonColor: '#f43f5e',
                            confirmButtonText: 'Contact Administrator',
                            background: '#0e1626',
                            color: '#f8fafc',
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
                              confirmButtonColor: '#f43f5e',
                              background: '#0e1626',
                              color: '#f8fafc',
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
                              confirmButtonColor: '#f43f5e',
                              background: '#0e1626',
                              color: '#f8fafc',
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
                    confirmButtonColor: '#f43f5e',
                    confirmButtonText: 'Understood',
                    backdrop: `rgba(6, 9, 19, 0.85)`,
                    background: '#0e1626',
                    color: '#f8fafc',
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
<html lang="en" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>ML Rental System - Secure Auth Terminal</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <link rel="icon" href="../../images/MLW logo.png" type="image/png" />
  
  <!-- Immediate Theme Loader -->
  <script>
    (function() {
      const savedTheme = localStorage.getItem('ml_theme') || 'dark';
      document.documentElement.setAttribute('data-theme', savedTheme);
    })();
  </script>

  <style>
    :root {
      /* Enterprise Dark Palette (Default) */
      --bg-dark-base: #060913;
      --bg-dark-surface: #0e1626;
      --bg-card-glass: rgba(15, 23, 42, 0.75);
      --bg-card-border: rgba(255, 255, 255, 0.08);
      --showcase-bg: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(6, 9, 19, 0.95) 100%);
      --input-bg: rgba(6, 9, 19, 0.6);

      --accent-red: #f43f5e;
      --accent-red-hover: #e11d48;
      --accent-red-glow: rgba(244, 63, 94, 0.35);
      --accent-red-subtle: rgba(244, 63, 94, 0.12);

      --text-primary: #f8fafc;
      --text-secondary: #94a3b8;
      --text-muted: #64748b;

      --radius-full: 9999px;
      --radius-2xl: 24px;
      --radius-xl: 16px;
      --radius-lg: 12px;

      --ease-out: cubic-bezier(0.16, 1, 0.3, 1);
    }

    /* Day Mode Overrides */
    [data-theme="light"] {
      --bg-dark-base: #f8fafc;
      --bg-dark-surface: #ffffff;
      --bg-card-glass: rgba(255, 255, 255, 0.85);
      --bg-card-border: rgba(15, 23, 42, 0.12);
      --showcase-bg: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
      --input-bg: #f1f5f9;

      --text-primary: #0f172a;
      --text-secondary: #475569;
      --text-muted: #64748b;
    }

    body, html {
      margin: 0; padding: 0; height: 100%;
      font-family: 'Plus Jakarta Sans', sans-serif;
      overflow-x: hidden;
      background-color: var(--bg-dark-base);
      color: var(--text-primary);
      transition: background-color 0.4s var(--ease-out), color 0.4s var(--ease-out);
    }

    #particles-js { 
      position: absolute; 
      inset: 0;
      z-index: 1; 
      pointer-events: auto;
    }

    .login-wrapper { 
      position: relative; 
      z-index: 2; 
      min-height: 100vh; 
      display: flex; 
      align-items: center; 
      justify-content: center;
      padding: 2rem 1.5rem;
    }

    /* Ambient Glowing Backdrop */
    .ambient-glow-center {
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
      background: var(--bg-dark-surface);
      border: 1px solid var(--bg-card-border);
      color: var(--text-primary);
      width: 44px;
      height: 44px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
      transition: all 0.3s var(--ease-out);
    }

    .btn-theme-float:hover {
      background: var(--accent-red-subtle);
      border-color: rgba(244, 63, 94, 0.4);
      color: var(--accent-red);
      transform: scale(1.08) rotate(15deg);
    }

    /* Split-Pane Container */
    .login-container-card {
      position: relative;
      width: 100%;
      max-width: 980px;
      background: var(--bg-dark-surface);
      border: 1px solid var(--bg-card-border);
      border-radius: 28px;
      box-shadow: 0 30px 80px -20px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      overflow: hidden;
      transition: background-color 0.4s var(--ease-out), border-color 0.4s var(--ease-out), box-shadow 0.4s var(--ease-out);
    }

    [data-theme="light"] .login-container-card {
      box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.08);
    }

    /* Left Side Feature Showcase */
    .auth-showcase {
      background: var(--showcase-bg);
      border-right: 1px solid var(--bg-card-border);
      padding: 3.5rem 3rem;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 100%;
      position: relative;
      transition: background 0.4s var(--ease-out), border-color 0.4s var(--ease-out);
    }

    .auth-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 0.4rem 0.95rem;
      border-radius: var(--radius-full);
      background: var(--accent-red-subtle);
      border: 1px solid rgba(244, 63, 94, 0.3);
      color: var(--accent-red);
      font-size: 0.775rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      width: fit-content;
    }

    .auth-showcase-title {
      font-family: 'Space Grotesk', sans-serif;
      font-weight: 700;
      font-size: 2.25rem;
      line-height: 1.15;
      letter-spacing: -0.02em;
      color: var(--text-primary);
      transition: color 0.4s var(--ease-out);
    }

    .text-gradient {
      background: linear-gradient(135deg, var(--text-primary) 30%, var(--accent-red) 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .security-pill-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .security-pill-item {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.875rem;
      color: var(--text-secondary);
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid var(--bg-card-border);
      padding: 0.75rem 1rem;
      border-radius: var(--radius-xl);
      transition: all 0.4s var(--ease-out);
    }

    [data-theme="light"] .security-pill-item {
      background: rgba(15, 23, 42, 0.03);
    }

    .security-pill-item i {
      color: var(--accent-red);
      font-size: 1.1rem;
    }

    /* Right Side Login Console */
    .login-card {
      padding: 3.5rem 3rem;
      position: relative;
      background: transparent;
    }

    .logo-container img {
      height: 48px;
      width: auto;
      filter: drop-shadow(0 4px 12px rgba(244, 63, 94, 0.25));
      transition: transform 0.4s var(--ease-out);
    }

    .logo-container img:hover {
      transform: scale(1.05);
    }

    .form-label-custom { 
      font-weight: 600; 
      font-size: 0.775rem; 
      text-transform: uppercase; 
      letter-spacing: 0.06em; 
      color: var(--text-secondary); 
      margin-bottom: 8px; 
      display: block;
      transition: color 0.4s var(--ease-out);
    }

    .input-group-custom {
      border: 1px solid var(--bg-card-border);
      border-radius: var(--radius-xl);
      background: var(--input-bg);
      transition: all 0.3s var(--ease-out);
      overflow: hidden;
      display: flex;
      align-items: center;
    }

    .input-group-custom:focus-within {
      border-color: var(--accent-red);
      background: var(--bg-dark-surface);
      box-shadow: 0 0 0 4px rgba(244, 63, 94, 0.15);
    }

    .input-group-text-custom { 
      background: transparent; 
      border: none; 
      color: var(--text-muted); 
      padding-left: 18px; 
      transition: color 0.3s ease;
    }

    .input-group-custom:focus-within .input-group-text-custom {
      color: var(--accent-red);
    }
    
    .form-control-custom { 
      border: none; 
      padding: 14px 14px 14px 10px; 
      font-size: 0.95rem; 
      background: transparent; 
      color: var(--text-primary); 
      font-weight: 500;
      width: 100%;
    }
    
    .form-control-custom:focus { 
      outline: none;
      box-shadow: none; 
      background: transparent; 
      color: var(--text-primary);
    }

    .form-control-custom::placeholder {
      color: #64748b;
    }

    .btn-brand-launch {
      background: linear-gradient(135deg, var(--accent-red) 0%, var(--accent-red-hover) 100%);
      color: white; 
      border: none; 
      padding: 16px;
      border-radius: var(--radius-xl); 
      font-weight: 700;
      letter-spacing: 0.03em; 
      transition: all 0.35s var(--ease-out);
      width: 100%;
      position: relative;
      overflow: hidden;
      box-shadow: 0 10px 25px -5px var(--accent-red-glow);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .btn-brand-launch:hover {
      background: linear-gradient(135deg, #ff4d6d 0%, #be123c 100%);
      transform: translateY(-2px);
      box-shadow: 0 15px 35px -5px rgba(244, 63, 94, 0.55);
      color: white;
    }

    .btn-brand-launch:active {
      transform: translateY(0);
    }

    .back-link-custom { 
      color: var(--text-secondary); 
      font-size: 0.85rem; 
      font-weight: 600; 
      text-decoration: none; 
      transition: all 0.3s var(--ease-out); 
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }
    
    .back-link-custom:hover { 
      color: var(--accent-red); 
      transform: translateX(-3px);
    }

    /* Password Toggle Icon */
    .toggle-pass-btn {
      background: none; 
      border: none; 
      color: var(--text-muted); 
      padding-right: 18px;
      transition: color 0.3s ease;
      cursor: pointer;
    }

    .toggle-pass-btn:hover {
      color: var(--accent-red);
    }

    /* Progress Overlay Bar */
    #progressOverlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 4px;
      background: rgba(244, 63, 94, 0.1);
      z-index: 20;
      display: none;
      overflow: hidden;
    }

    #progressBar {
      width: 0%;
      height: 100%;
      background: linear-gradient(90deg, var(--accent-red) 0%, #ff758f 50%, var(--accent-red) 100%);
      background-size: 200% 100%;
      animation: gradientGlow 1.5s infinite linear;
      transition: width 0.35s var(--ease-out);
      box-shadow: 0 0 12px var(--accent-red);
    }

    @keyframes gradientGlow {
      0% { background-position: 0% 0%; }
      100% { background-position: 200% 0%; }
    }

    /* Modal Aesthetic Override */
    .modal-content-custom { 
      border-radius: 28px; 
      border: 1px solid var(--bg-card-border); 
      box-shadow: 0 30px 70px -15px rgba(0, 0, 0, 0.8);
      background: var(--bg-dark-surface);
      color: var(--text-primary);
      overflow: hidden;
    }
    
    .modal-header-custom {
      background: linear-gradient(135deg, var(--accent-red) 0%, #9f1239 100%);
      color: white;
      padding: 28px 24px;
      border-bottom: none;
      position: relative;
    }

    .modal-icon-badge {
      width: 58px;
      height: 58px;
      background: rgba(255, 255, 255, 0.18);
      backdrop-filter: blur(10px);
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
      margin-bottom: 12px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }

    .password-input-alt { 
      background: var(--input-bg) !important; 
      border: 1px solid var(--bg-card-border) !important; 
      border-radius: 14px !important; 
      padding: 14px 16px !important;
      font-size: 0.95rem;
      color: var(--text-primary) !important;
      transition: all 0.25s ease;
    }

    .password-input-alt:focus {
      border-color: var(--accent-red) !important;
      box-shadow: 0 0 0 4px rgba(244, 63, 94, 0.15) !important;
      background: var(--bg-dark-surface) !important;
    }

    /* SweetAlert Override */
    .modern-swal-popup {
      font-family: 'Plus Jakarta Sans', sans-serif !important;
      border-radius: 24px !important;
      padding: 25px !important;
    }

    @media (max-width: 991.98px) {
      .auth-showcase {
        display: none;
      }
      .login-card {
        padding: 2.5rem 1.75rem;
      }
    }
  </style>
</head>

<body>
  <div id="particles-js"></div>
  <div class="ambient-glow-center"></div>

  <!-- Floating Theme Switcher -->
  <button class="btn-theme-float" id="themeToggleBtn" aria-label="Toggle theme" title="Switch Day/Night Mode">
    <i class="bi bi-moon-stars-fill" id="themeToggleIcon"></i>
  </button>

  <div class="login-wrapper">
    <div class="login-container-card">
      <div class="row g-0">
        
        <!-- Left Side Feature Column -->
        <div class="col-lg-6 d-none d-lg-block">
          <div class="auth-showcase">
            <div>
              <div class="auth-badge mb-4">
                <i class="bi bi-shield-check me-1"></i> Encrypted Terminal
              </div>
              <h2 class="auth-showcase-title mb-3">
                Access Centralized <br>
                <span class="text-gradient">ML Rental Management System</span>
              </h2>
              <p class="text-secondary small mb-4">
                Secure enterprise portal for real-time lease tracking, withholding tax automation, and property management.
              </p>
            </div>

            <div class="security-pill-list">
              <div class="security-pill-item">
                <i class="bi bi-lock-fill"></i>
                <span>MD5 Protected Authentication Framework</span>
              </div>
              <div class="security-pill-item">
                <i class="bi bi-speedometer2"></i>
                <span>Real-Time Branch Audit & Telemetry Log</span>
              </div>
              <div class="security-pill-item">
                <i class="bi bi-check-circle-fill"></i>
                <span>Multi-Zone Role-Based Access Control</span>
              </div>
            </div>

            <div class="pt-4 border-top border-secondary border-opacity-25 text-muted small">
              ML Rental Management System &copy; <?= date('Y'); ?>
            </div>
          </div>
        </div>

        <!-- Right Side Form Column -->
        <div class="col-lg-6">
          <div class="login-card">
            <!-- Dynamic Progress Indicator -->
            <div id="progressOverlay">
              <div id="progressBar"></div>
            </div>

            <div class="text-center mb-4">
              <div class="logo-container mb-3">
                <img src="../../assets/images/ml_logo.png" alt="ML Logo" />
              </div>
              <h3 class="fw-bold mb-1" style="font-family: 'Space Grotesk', sans-serif;">System Login</h3>
              <p class="text-secondary small">Enter your account credentials to proceed</p>
            </div>

            <form method="POST" id="loginForm" onsubmit="startLoginProgress()">
              <div class="mb-3">
                <label class="form-label-custom">Username or Email</label>
                <div class="input-group-custom">
                  <span class="input-group-text-custom"><i class="fa-solid fa-user-shield"></i></span>
                  <input type="text" name="email" class="form-control-custom" placeholder="Enter Username or Email" required autocomplete="username" />
                </div>
              </div>
              
              <div class="mb-4">
                <label class="form-label-custom">Password</label>
                <div class="input-group-custom">
                  <span class="input-group-text-custom"><i class="fa-solid fa-key"></i></span>
                  <input type="password" name="password" id="passInput" class="form-control-custom" placeholder="••••••••" required autocomplete="current-password" />
                  <button type="button" class="toggle-pass-btn" onclick="togglePassword()" aria-label="Toggle password visibility">
                    <i class="fa-solid fa-eye" id="eyeIcon"></i>
                  </button>
                </div>
              </div>

              <div class="mb-4">
                <button type="submit" name="submit" id="submitBtn" class="btn-brand-launch">
                  <span>AUTHENTICATE</span>
                  <i class="fa-solid fa-arrow-right-to-bracket ms-1 small"></i>
                </button>
              </div>

              <div class="text-center">
                <a href="../../index.php" class="back-link-custom">
                  <i class="fa-solid fa-chevron-left"></i> Return to Homepage
                </a>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>

<!-- Security Password Change Modal -->
<div class="modal fade" id="changePasswordModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-content-custom">
        <div class="modal-header-custom text-center">
          <div class="modal-icon-badge">
            <i class="fas fa-shield-alt"></i>
          </div>
          <h5 class="modal-title fw-bold w-100 mb-0">Security Requirement</h5>
          <p class="text-white-50 small mb-0 mt-1">Default password reset required</p>
        </div>
        <form action="change_password.php" method="post">
            <div class="modal-body p-4">
              <div class="text-center mb-4">
                  <h6 class="fw-bold text-light">Set New Password</h6>
                  <p class="text-secondary small mb-0">You are currently using a default account password. Please choose a new secure password to proceed.</p>
              </div>
              <div class="mb-3">
                  <label class="form-label-custom">New Password</label>
                  <input type="password" class="form-control password-input-alt" name="newPassword" placeholder="Enter new secure password" required>
              </div>
              <div class="mb-2">
                  <label class="form-label-custom">Confirm Password</label>
                  <input type="password" class="form-control password-input-alt" name="confirmPassword" placeholder="Re-enter password" required>
              </div>
            </div>
            <div class="modal-footer p-4 pt-0 border-0">
              <div class="w-100 d-flex gap-2">
                  <button type="button" class="btn btn-outline-secondary w-50 py-3 fw-semibold rounded-4" data-bs-dismiss="modal">Later</button>
                  <button type="submit" name="submitNewPass" class="btn btn-danger w-50 py-3 fw-bold rounded-4 shadow-sm" style="background: linear-gradient(135deg, var(--accent-red) 0%, #be123c 100%); border: none;">
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
  // Theme Toggle Handler
  const themeToggleBtn = document.getElementById('themeToggleBtn');
  const themeToggleIcon = document.getElementById('themeToggleIcon');

  function updateThemeIcon(theme) {
    if (theme === 'light') {
      themeToggleIcon.className = 'bi bi-sun-fill';
    } else {
      themeToggleIcon.className = 'bi bi-moon-stars-fill';
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

  // Smooth Dynamic Progress Bar Functionality
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
        const increment = Math.max(1, Math.floor((100 - currentWidth) * 0.15));
        currentWidth += increment;
        progressBar.style.width = currentWidth + '%';
      }
    }, 80);
  }

  // Modern Spider Web Particles Effect
  if (typeof particlesJS !== 'undefined') {
    particlesJS("particles-js", {
      "particles": {
        "number": { "value": 70, "density": { "enable": true, "value_area": 800 } },
        "color": { "value": "#f43f5e" },
        "shape": { "type": "circle" },
        "opacity": { "value": 0.4, "random": false },
        "size": { "value": 3, "random": true },
        "line_linked": {
          "enable": true,
          "distance": 140,
          "color": "#f43f5e",
          "opacity": 0.25,
          "width": 1
        },
        "move": {
          "enable": true,
          "speed": 1.8,
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
          "grab": { "distance": 160, "line_linked": { "opacity": 0.6 } },
          "push": { "particles_nb": 4 }
        }
      },
      "retina_detect": true
    });
  }
</script>
</body>
</html>