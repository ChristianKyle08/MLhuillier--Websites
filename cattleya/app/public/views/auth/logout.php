<?php
require __DIR__ . '/../../../config/database.php';

// 1. Update last logout if user is logged in BEFORE destroying the session
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET last_logout = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (PDOException $e) {
        // Silently fail or log error
    }
}

// 2. Clear all session variables
$_SESSION = [];

// 3. Destroy session cookie from browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 4. Finally, destroy the session on the server
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logging out... | Cattleya</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            /* Cattleya Color Theme */
            --brand-primary: #2a6279; /* Deep Teal */
            --brand-accent: #9dc44d;  /* Lime Green */
            --brand-soft: rgba(42, 98, 121, 0.1);
            --bg-gradient: radial-gradient(circle at center, #ffffff 0%, #f1f5f9 100%);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: var(--bg-gradient);
            overflow: hidden;
        }

        .logout-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 3.5rem 2.5rem;
            border-radius: 32px;
            width: 100%;
            max-width: 380px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 25px 50px -12px rgba(42, 98, 121, 0.15);
            animation: cardEntrance 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        .icon-box {
            width: 80px;
            height: 80px;
            background: var(--brand-soft);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            animation: pulseGlow 2s infinite ease-in-out;
        }

        .logout-icon {
            font-size: 2.5rem;
            color: var(--brand-primary);
        }

        h2 {
            font-size: 1.5rem;
            color: #2a3b42;
            margin-bottom: 0.5rem;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        p {
            font-size: 0.95rem;
            color: #64748b;
            margin-bottom: 2.5rem;
        }

        /* Modern Progress Bar */
        .progress-wrapper {
            width: 100%;
            height: 8px;
            background: rgba(42, 98, 121, 0.05);
            border-radius: 100px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, var(--brand-primary), var(--brand-accent));
            border-radius: 100px;
            animation: fillProgress 2.2s cubic-bezier(0.65, 0, 0.35, 1) forwards;
            box-shadow: 0 0 15px rgba(157, 196, 77, 0.4);
        }

        /* Animations */
        @keyframes cardEntrance {
            from { opacity: 0; transform: scale(0.9) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        @keyframes pulseGlow {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(42, 98, 121, 0.2); }
            50% { transform: scale(1.05); box-shadow: 0 0 20px 10px rgba(157, 196, 77, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(42, 98, 121, 0); }
        }

        @keyframes fillProgress {
            0% { width: 0%; }
            100% { width: 100%; }
        }
    </style>
</head>
<body>

<div class="logout-card">
    <div class="icon-box">
        <i class="bi bi-shield-lock-fill logout-icon"></i>
    </div>
    
    <h2>Securely Signing Out</h2>
    <p>We're finalizing your session and clearing security data...</p>
    
    <div class="progress-wrapper">
        <div class="progress-fill"></div>
    </div>
</div>

<script>
    // Redirect to login after the progress animation finishes
    setTimeout(() => {
        window.location.href = "/"; 
    }, 2400);
</script>

</body>
</html>