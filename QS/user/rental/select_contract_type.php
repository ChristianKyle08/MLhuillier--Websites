<?php
session_start();
include('../../config/config.php');

if (!isset($_SESSION['user_name'])) {
    header('location:login_form.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Select Contract Type</title>
  <!-- ✅ Local Google Font -->
  <link href="../../assets/css/poppins.css" rel="stylesheet">

  <!-- ✅ Local Bootstrap CSS -->
  <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">

  <!-- ✅ Local Bootstrap Icons -->
  <link href="../../assets/icons/bootstrap-icons.css" rel="stylesheet">

  <link href="../../assets/sweetalert2/dist/sweetalert2.min.css" rel="stylesheet">
  <!-- ✅ Your custom CSS should come AFTER font import -->
  <link rel="stylesheet" href="../../assets/css/sidebar.css">
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #fff;
      color: #333;
      min-height: 100vh;
    }
    .select_div {
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 7.5rem;
    }
    h2 {
      color: #333;
      font-weight: 600;
    }

    .btn-lg {
      padding: 14px 28px;
      font-size: 1.2rem;
      border-radius: 0.75rem;
      transition: all 0.3s ease-in-out;
    }

    .btn-outline-primary {
      color: #d70c0c;
      border-color: #d70c0c;
    }

    .btn-outline-primary:hover {
      background-color: #d70c0c;
      color: #fff;
      border-color: #d70c0c;
    }

    .btn-outline-dark:disabled {
      color: #aaa;
      border-color: #ccc;
      background-color: #f9f9f9;
    }
  </style>
</head>
<body>

<?php include('navbar.php'); ?>
<div id="mainContent">
<button id="toggleSidebar" class="btn btn-light border text-dark d-flex align-items-center mx-3 my-3">
            <i class="bi bi-list me-2" style="color: #d70c0c;"></i>
            <span class="fw-normal">Menu</span>
        </button>
<div class="select_div">
<div class="text-center">
  <h2 class="mb-4 animate__animated animate__fadeInDown">What type of contract are you registering?</h2>
  <div class="d-flex justify-content-center gap-4 animate__animated animate__fadeInUp">
    <button class="btn btn-outline-primary btn-lg" onclick="redirectTo('create_contract.php')">
      <i class="bi bi-file-earmark-plus me-1"></i> New Contract
    </button>
    <button class="btn btn-outline-dark btn-lg" disabled>
      <i class="bi bi-arrow-repeat me-1"></i> Renew Contract (Coming Soon)
    </button>
  </div>
</div>

<!-- Logout Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4" style="background-color: #fff;">
      <div class="modal-body">
        <div class="text-center mb-3">
          <i class="bi bi-box-arrow-right text-danger" style="font-size: 3rem;"></i>
        </div>
        <h5 class="mb-2 text-dark">Logging Out</h5>
        <p class="text-muted mb-3">Please wait while we securely log you out...</p>
        <div class="progress" style="height: 8px;">
          <div class="progress-bar progress-bar-striped progress-bar-animated bg-danger" style="width: 100%;"></div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>
</div>
<!-- Scripts -->
<script src="../../assets/sweetalert2/dist/sweetalert2.min.js"></script>
<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script>
  function redirectTo(page) {
    window.location.href = page;
  }

  document.getElementById('logoutLink')?.addEventListener('click', function (e) {
    e.preventDefault();
    const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'), {
      backdrop: 'static',
      keyboard: false
    });
    logoutModal.show();
    setTimeout(() => window.location.href = '../../logout.php', 2500);
  });

  const toggleBtn = document.getElementById('toggleSidebar');
  const sidebar = document.getElementById('sidebarMenu');

  toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
  });
</script>
</body>
</html>
