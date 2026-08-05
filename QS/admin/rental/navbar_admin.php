<?php
include '../../config/config.php';
$conn = mysqli_connect($host, $username, $password, $database); 
  if (!$conn) {
      die("Connection failed: " . mysqli_connect_error());
  }
  if (!isset($_SESSION['admin_name']) || !isset($_SESSION['admin_email'])) {
      header('location:../../admin/login_form.php');
      exit;
  }
?>

<style>

/* Navbar Modern Style */
.navbar{
  background: rgba(255,255,255,0.95);
  backdrop-filter: blur(10px);
  border-bottom:3px solid #d70c0c;
  box-shadow:0 6px 20px rgba(0,0,0,0.05);
  padding:12px 0;
}

/* Admin Info */
.admin-info{
  gap:10px;
}

.admin-avatar{
  width:40px;
  height:40px;
  border-radius:50%;
  background:#d70c0c;
  display:flex;
  align-items:center;
  justify-content:center;
  color:#fff;
  font-size:18px;
  box-shadow:0 4px 10px rgba(0,0,0,0.1);
}

/* Admin text */
.admin-info .fw-normal{
  font-size:13px;
  font-weight:600;
}

.admin-info .small{
  font-size:11px;
}

/* Navigation Links */
.nav-link{
  font-size:13px;
  font-weight:500;
  display:flex;
  align-items:center;
  gap:5px;
  padding:8px 12px;
  border-radius:6px;
  transition:all .2s ease;
}

/* Hover animation */
.nav-link:hover{
  background:#fff3f3;
  color:#d70c0c !important;
  transform:translateY(-1px);
}

/* Dropdown */
.dropdown-menu{
  border:none;
  border-radius:10px;
  box-shadow:0 10px 25px rgba(0,0,0,0.08);
  padding:6px 0;
  min-width:210px;
}

.dropdown-item{
  font-size:13px;
  padding:8px 16px;
  display:flex;
  align-items:center;
  gap:8px;
  transition:all .2s ease;
}

.dropdown-item:hover{
  background:#fff3f3;
  color:#d70c0c;
}

/* Logout button style */
.logout-link{
  background:#fff3f3;
  border-radius:8px;
  padding:8px 14px !important;
}

.logout-link:hover{
  background:#ffe3e3;
}

/* Mobile spacing */
@media (max-width:991px){

  .admin-info{
    margin-bottom:10px;
  }

}
</style>

<nav class="navbar navbar-expand-lg">
  <div class="container-fluid px-4">

    <!-- Admin Info -->
    <div class="admin-info me-auto d-flex align-items-center">
      <div class="admin-avatar">
        <i class="bi bi-shield-lock"></i>
      </div>
      <div>
        <div class="fw-normal text-dark">
          Fullname: <?= htmlspecialchars($_SESSION['admin_name']); ?>
        </div>

        <div class="text-muted small">
          Username: <?= htmlspecialchars($_SESSION['admin_email']) ?>
        </div>
      </div>
    </div>

    <!-- Toggler for mobile -->
    <button class="navbar-toggler" type="button"
      data-bs-toggle="collapse"
      data-bs-target="#navbarMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <!-- Navigation Links -->
    <div class="collapse navbar-collapse" id="navbarMenu">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-2">
        <!-- Home -->
        <li class="nav-item">
          <a class="nav-link" href="admin_page.php">
            <i class="bi bi-house-door text-danger"></i>
            Home
          </a>
        </li>
        <!-- Notifications -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle"
            href="#"
            id="notificationsDropdown"
            role="button"
            data-bs-toggle="dropdown">

            <i class="bi bi-bell text-danger"></i>
            Notifications
          </a>
          <ul class="dropdown-menu">
            <li>
              <a class="dropdown-item"
                href="notification_rfp.php">
                <i class="bi bi-file-earmark-text text-danger"></i>
                  Monthly Rental RFP

              </a>
            </li>
            <li>
              <a class="dropdown-item"
                href="notification_contract_expiry.php">
                <i class="bi bi-calendar-x text-danger"></i>
                  Contract Expiry
              </a>
            </li>
          </ul>
        </li>
        <!-- Maintenance -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle"
            href="#"
            id="maintenanceDropdown"
            role="button"
            data-bs-toggle="dropdown">
            <i class="bi bi-tools text-danger"></i>
              Maintenance
          </a>
          <ul class="dropdown-menu">
            <li>
              <a class="dropdown-item" href="userLog.php">
                <i class="bi bi-people text-danger"></i>
                User
              </a>
            </li>
            <li>
              <a class="dropdown-item"
                href="#"
                data-bs-toggle="modal"
                data-bs-target="#dbModal">
                <i class="bi bi-hdd-stack text-danger"></i>
                DB
              </a>
            </li>
            <li>
              <a class="dropdown-item"
                href="admin_branchProfile.php">
                <i class="bi bi-building text-danger"></i>
                Update Branch Profile
              </a>
            </li>
            <li>
              <a class="dropdown-item"
                href="add_escalation.php">
                <i class="bi bi-graph-up text-danger"></i>
                Add Escalation
              </a>
            </li>
          </ul>
        </li>
        <!-- Logout -->
        <li class="nav-item">
          <a href="#"
            class="nav-link logout-link"
            id="logoutLink">
            <i class="bi bi-box-arrow-right text-danger"></i>
            Logout
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
