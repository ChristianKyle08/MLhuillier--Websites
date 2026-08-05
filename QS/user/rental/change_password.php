<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../../config/config.php';

if (!isset($_SESSION['user_email'])) {
    header("Location: login_form.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security | Update Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        :root {
            --hc-red: #d70c0c; 
            --hc-red-hover: #b50a0a;
            --bg-white: #ffffff;
            --text-dark: #1a1a1a;
            --text-muted: #757575;
            --input-bg: #f8f8fa;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-white);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden; 
        }

        @keyframes pageReveal {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes iconBounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @keyframes pulseRed {
            0% { box-shadow: 0 0 0 0 rgba(215, 12, 12, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(215, 12, 12, 0); }
            100% { box-shadow: 0 0 0 0 rgba(215, 12, 12, 0); }
        }

        .container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            animation: pageReveal 0.8s ease-out forwards;
        }

        .password-card {
            background: #ffffff;
            border-radius: 32px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 40px rgba(215, 12, 12, 0.08);
            border: 1px solid #f0f0f0;
        }

        .brand-icon {
            width: 70px;
            height: 70px;
            background: var(--hc-red);
            color: white;
            border-radius: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 20px;
            font-size: 28px;
            box-shadow: 0 8px 20px rgba(215, 12, 12, 0.3);
            animation: iconBounce 3s infinite ease-in-out;
        }

        h2 { font-weight: 700; color: var(--text-dark); margin-bottom: 8px; font-size: 1.6rem; }
        p.info-text { color: var(--text-muted); font-size: 0.9rem; margin-bottom: 30px; line-height: 1.5; }

        .input-group { position: relative; margin-bottom: 20px; text-align: left; }
        .input-group label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--hc-red); margin-bottom: 8px; margin-left: 4px; }
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-wrapper .main-icon { position: absolute; left: 18px; color: #ccc; transition: var(--transition); }
        .toggle-password { position: absolute; right: 18px; color: #bbb; cursor: pointer; transition: var(--transition); z-index: 10; }
        .toggle-password:hover { color: var(--hc-red); transform: scale(1.2); }

        input {
            width: 100%;
            padding: 16px 50px 16px 50px;
            border-radius: 16px;
            border: 2px solid transparent;
            background: var(--input-bg);
            color: var(--text-dark);
            font-size: 1rem;
            font-family: inherit;
            transition: var(--transition);
        }

        input:focus { outline: none; background: #ffffff; border-color: var(--hc-red); box-shadow: 0 4px 15px rgba(215, 12, 12, 0.05); }
        input:focus ~ .main-icon { color: var(--hc-red); transform: scale(1.1); }

        .btn-update {
            width: 100%;
            padding: 18px;
            background-color: var(--hc-red);
            color: white;
            border: none;
            border-radius: 18px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
        }

        .btn-update:hover { background-color: var(--hc-red-hover); transform: translateY(-2px); animation: pulseRed 1.5s infinite; }
        .btn-update i { transition: transform 0.3s ease; }
        .btn-update:hover i { transform: translateX(5px); }

        .swal2-popup { border-radius: 25px !important; font-family: 'Poppins', sans-serif; }
    </style>
</head>
<body>

<div class="container">
    <div class="password-card">
        <div class="brand-icon">
            <i class="fas fa-lock-open"></i>
        </div>
        <h2>New Password</h2>
        <p class="info-text">Create a strong password to ensure your account is safe and accessible only by you.</p>
        
        <form action="" method="POST">
            <div class="input-group">
                <label>New Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-key main-icon"></i>
                    <input type="password" name="newPassword" id="newPassword" placeholder="••••••••" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePass('newPassword', this)"></i>
                </div>
            </div>
            
            <div class="input-group">
                <label>Confirm Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-shield-check main-icon"></i>
                    <input type="password" name="confirmPassword" id="confirmPassword" placeholder="••••••••" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePass('confirmPassword', this)"></i>
                </div>
            </div>
            
            <button type="submit" class="btn-update">
                Secure Password <i class="fas fa-arrow-right"></i>
            </button>
        </form>
    </div>
</div>

<script>
    function togglePass(inputId, icon) {
        const input = document.getElementById(inputId);
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
</script>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword     = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $userEmail       = $_SESSION['user_email']; 

    if (!empty($newPassword) && $newPassword === $confirmPassword) {
        $hashedPassword = md5($newPassword);
        $stmt = $conn->prepare("UPDATE user_form SET password = ? WHERE username = ? OR email = ?");
        $stmt->bind_param("sss", $hashedPassword, $userEmail, $userEmail);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "<script>
                    let timerInterval;
                    Swal.fire({
                        icon: 'success',
                        title: 'Updated!',
                        html: 'Your password is now secure. Redirecting in <b></b> milliseconds.',
                        timer: 3000,
                        timerProgressBar: true,
                        confirmButtonColor: '#d70c0c',
                        didOpen: () => {
                            Swal.showLoading();
                            const b = Swal.getHtmlContainer().querySelector('b');
                            timerInterval = setInterval(() => {
                                b.textContent = Swal.getTimerLeft();
                            }, 100);
                        },
                        willClose: () => {
                            clearInterval(timerInterval);
                        }
                    }).then((result) => {
                        /* This block runs when timer finishes OR user clicks button */
                        window.location.href='login_form.php';
                    });
                </script>";
            } else {
                echo "<script>
                    Swal.fire({
                        icon: 'info',
                        title: 'No Changes',
                        text: 'This is already your current password.',
                        confirmButtonColor: '#6c757d'
                    });
                </script>";
            }
        } else {
            echo "<script>Swal.fire({ icon: 'error', title: 'System Error', text: 'Please try again later.', confirmButtonColor: '#d70c0c' });</script>";
        }
        $stmt->close();
    } else {
        echo "<script>Swal.fire({ icon: 'warning', title: 'Check Fields', text: 'Passwords do not match.', confirmButtonColor: '#d70c0c' });</script>";
    }
}
?>
</body>
</html>