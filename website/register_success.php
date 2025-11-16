<?php
session_name('website_session');
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Check if we have registration success data in session
if (!isset($_SESSION['registration_success']) || $_SESSION['registration_success'] !== true) {
    header("Location: register.php");
    exit;
}

// Get user data from session
$user_name = isset($_SESSION['registered_user_name']) ? $_SESSION['registered_user_name'] : 'User';
$user_email = isset($_SESSION['registered_user_email']) ? $_SESSION['registered_user_email'] : '';

// Clear the session data after use
unset($_SESSION['registration_success']);
unset($_SESSION['registered_user_name']);
unset($_SESSION['registered_user_email']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful - Marcomedia POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ----------------------------------- */
        /* ENHANCED COLOR PALETTE & VARIABLES */
        /* ----------------------------------- */
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --accent-color: #f59e0b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --dark-text-color: #1f2937;
            --light-text-color: #6b7280;
            --background-light: #f8fafc;
            --card-bg: #ffffff;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --gradient-accent: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --border-radius: 16px;
            --border-radius-lg: 24px;
            --shadow: 0 20px 40px rgba(0,0,0,0.1);
            --shadow-lg: 0 30px 60px rgba(0,0,0,0.15);
            --transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.7;
            color: var(--dark-text-color);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" fill="%23ffffff" opacity="0.05"><circle cx="50" cy="50" r="2"/></svg>') repeat;
        }

        .success-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 60px 50px;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 500px;
            border: 1px solid rgba(255,255,255,0.2);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            text-align: center;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .success-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--gradient-primary);
        }

        .logo {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 32px;
            box-shadow: var(--shadow);
        }

        .logo-img {
            width: 50px;
            height: 50px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }

        .logo h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .logo p {
            color: var(--light-text-color);
            font-size: 1.1rem;
            font-weight: 400;
        }

        .success-icon {
            font-size: 80px;
            color: var(--success-color);
            margin-bottom: 20px;
            animation: bounce 1s ease;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        .success-container h1 {
            color: var(--success-color);
            font-size: 2rem;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .success-container p {
            color: var(--light-text-color);
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .user-greeting {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.2rem;
        }

        .user-email {
            color: var(--light-text-color);
            font-size: 1rem;
            margin-bottom: 20px;
            background: var(--background-light);
            padding: 10px 15px;
            border-radius: var(--border-radius);
            display: inline-block;
        }

        .features {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            text-align: center;
            gap: 15px;
        }

        .feature {
            flex: 1;
            padding: 20px 10px;
            background: var(--background-light);
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .feature:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .feature i {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: block;
        }

        .feature p {
            font-size: 0.9rem;
            color: var(--light-text-color);
            margin: 0;
        }

        .countdown {
            font-size: 1rem;
            color: var(--primary-color);
            margin-bottom: 30px;
            font-weight: 600;
        }

        .btn {
            display: inline-block;
            padding: 18px 30px;
            background: var(--gradient-primary);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            font-weight: 700;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            width: 100%;
            text-align: center;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(37, 99, 235, 0.4);
        }

        .btn:active {
            transform: translateY(-1px);
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            margin-top: 20px;
            overflow: hidden;
        }

        .progress {
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            border-radius: 3px;
            animation: countdown 5s linear forwards;
            transform-origin: left;
        }

        @keyframes countdown {
            from {
                transform: scaleX(1);
            }
            to {
                transform: scaleX(0);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .success-container {
                padding: 40px 30px;
                margin: 20px;
            }

            .logo h1 {
                font-size: 2rem;
            }

            .logo-icon {
                width: 70px;
                height: 70px;
            }

            .logo-img {
                width: 40px;
                height: 40px;
            }

            .features {
                flex-direction: column;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .success-container {
                padding: 30px 20px;
            }

            .logo h1 {
                font-size: 1.8rem;
            }

            .success-icon {
                font-size: 60px;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .success-container {
                background: rgba(30, 41, 59, 0.95);
                color: #f1f5f9;
            }

            .feature {
                background: #334155;
            }

            .feature p {
                color: #94a3b8;
            }

            .user-email {
                background: #334155;
                color: #cbd5e1;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="logo">
            <div class="logo-icon">
                <img src="images/rsz_logo.png" alt="Marcomedia Logo" class="logo-img">
            </div>
            <h1>Marcomedia</h1>
            <p>Registration Successful</p>
        </div>
        
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h1>Registration Successful!</h1>
        <div class="user-greeting">Welcome, <?php echo htmlspecialchars($user_name); ?>!</div>
        <?php if (!empty($user_email)): ?>
            <div class="user-email">
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user_email); ?>
            </div>
        <?php endif; ?>
        <p>Your account has been created successfully. You can now access all features of our POS system.</p>
        
        <div class="features">
            <div class="feature">
                <i class="fas fa-shopping-cart"></i>
                <p>Manage Sales</p>
            </div>
            <div class="feature">
                <i class="fas fa-boxes"></i>
                <p>Track Inventory</p>
            </div>
            <div class="feature">
                <i class="fas fa-chart-bar"></i>
                <p>View Reports</p>
            </div>
        </div>
        
        <div class="countdown">
            Redirecting to login page in <span id="countdown">5</span> seconds...
        </div>
        
        <a href="login.php" class="btn">
            <i class="fas fa-sign-in-alt"></i> Go to Login Now
        </a>
        
        <div class="progress-bar">
            <div class="progress"></div>
        </div>
    </div>

    <script>
        let seconds = 5;
        const countdownElement = document.getElementById('countdown');
        
        const countdown = setInterval(function() {
            seconds--;
            countdownElement.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(countdown);
                window.location.href = 'login.php';
            }
        }, 1000);
        
        // Allow user to click anywhere to redirect immediately
        document.addEventListener('click', function() {
            clearInterval(countdown);
            window.location.href = 'login.php';
        });
        
        // Allow Enter key to redirect immediately
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                clearInterval(countdown);
                window.location.href = 'login.php';
            }
        });
    </script>
</body>
</html>