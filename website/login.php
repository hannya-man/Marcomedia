<?php
// Use a unique session name for website
session_name('website_session');
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$email = '';
require 'db.php';

// Debug Testing: Check if database connection is working
if (!$conn) {
    $error = "Database connection failed. Please try again later.";
    error_log("Database connection error: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } else {
        // Debug: Log the login attempt
        error_log("Login attempt for email: $email");
        
        // Check if user exists and is NOT admin
        $stmt = $conn->prepare("SELECT id, fullname, email, password_hash, role FROM users WHERE email = ? AND role != 'admin'");
        if ($stmt === false) {
            $error = "Database error: " . $conn->error;
            error_log("Prepare statement failed: " . $conn->error);
        } else {
            $stmt->bind_param("s", $email);
            
            if (!$stmt->execute()) {
                $error = "Database error: " . $stmt->error;
                error_log("Execute failed: " . $stmt->error);
            } else {
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    
                    // Debug: Log user data (without password)
                    error_log("User found - ID: " . $user['id'] . ", Role: " . $user['role']);
                    
                    // Try multiple password verification methods
                    $login_success = false;
                    
                    // Method 1: password_verify (recommended)
                    if (password_verify($password, $user['password_hash'])) {
                        $login_success = true;
                        error_log("Password verified using password_verify");
                    }
                    // Method 2: SHA256 (if passwords were stored this way)
                    else if (hash('sha256', $password) === $user['password_hash']) {
                        $login_success = true;
                        error_log("Password verified using SHA256");
                    }
                    // Method 3: Plain text (for testing only - remove in production)
                    else if ($password === $user['password_hash']) {
                        $login_success = true;
                        error_log("Password verified using plain text");
                    }
                    
                    if ($login_success) {
                        // Login successful - set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_name'] = $user['fullname'];
                        $_SESSION['user_role'] = $user['role'];
                        $_SESSION['login_time'] = time();
                        
                        // Debug: Log successful login
                        error_log("Login successful for user: " . $user['email']);
                        
                        $redirect = $_SESSION['redirect_url'] ?? 'index.php';
                        unset($_SESSION['redirect_url']);
                        
                        header("Location: " . $redirect);
                        exit;
                    } else {
                        $error = "Invalid email or password";
                        error_log("Password verification failed for user: $email");
                    }
                } else {
                    $error = "Invalid email or password";
                    error_log("No user found with email: $email");
                    
                    // Debug: Check if any users exist in database
                    $check_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role != 'admin'");
                    if ($check_users) {
                        $count = $check_users->fetch_assoc()['count'];
                        error_log("Total non-admin users in database: " . $count);
                    }
                }
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Marcomedia POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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

        .login-container {
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

        .login-container::before {
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
            margin-bottom: 50px;
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

        /* Alert Messages */
        .alert {
            padding: 20px 25px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: var(--shadow);
            border-left: 6px solid;
            animation: slideInDown 0.5s ease;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #ecfdf5);
            color: #065f46;
            border-left-color: #10b981;
        }
        .alert-error {
            background: linear-gradient(135deg, #fee2e2, #fef2f2);
            color: #991b1b;
            border-left-color: #ef4444;
        }
        .alert-warning {
            background: linear-gradient(135deg, #fef3c7, #fffbeb);
            color: #92400e;
            border-left-color: #f59e0b;
        }

        .alert-icon {
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .form-group {
            margin-bottom: 30px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 12px;
            color: var(--dark-text-color);
            font-weight: 600;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 18px 20px;
            border: 2px solid #e2e8f0;
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
            background: white;
            color: var(--dark-text-color);
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            transform: translateY(-2px);
        }

        .input-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text-color);
            transition: var(--transition);
            font-size: 1.1rem;
        }

        .form-control:focus + .input-icon {
            color: var(--primary-color);
        }

        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--light-text-color);
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: var(--transition);
            z-index: 2;
            font-size: 1.1rem;
        }

        .password-toggle:hover {
            background: var(--background-light);
            color: var(--primary-color);
            transform: translateY(-50%) scale(1.1);
        }

        .btn {
            width: 100%;
            padding: 20px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow);
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

        .btn:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn:disabled:hover::before {
            left: -100%;
        }

        .loading {
            display: none;
        }

        .loading .spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .forgot-password {
            text-align: right;
            margin-top: -20px;
            margin-bottom: 30px;
        }

        .forgot-password a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .forgot-password a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .links {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: center;
            gap: 30px;
        }

        .links a {
            color: var(--light-text-color);
            text-decoration: none;
            font-size: 1rem;
            font-weight: 600;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: var(--border-radius);
            background: var(--background-light);
        }

        .links a:hover {
            color: var(--primary-color);
            background: rgba(37, 99, 235, 0.1);
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-container {
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
                grid-template-columns: 1fr;
                gap: 15px;
                padding: 20px;
            }

            .links {
                flex-direction: column;
                gap: 15px;
            }

            .links a {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .login-container {
                padding: 30px 20px;
            }

            .logo h1 {
                font-size: 1.8rem;
            }

            .form-control {
                padding: 16px;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .login-container {
                background: rgba(30, 41, 59, 0.95);
                color: #f1f5f9;
            }

            .form-control {
                background: #1e293b;
                border-color: #334155;
                color: #f1f5f9;
            }

            .form-label {
                color: #f1f5f9;
            }

            .links a {
                background: #334155;
                color: #94a3b8;
            }

            .links a:hover {
                color: #60a5fa;
                background: rgba(96, 165, 250, 0.1);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">
                <img src="images/rsz_logo.png" alt="Marcomedia Logo" class="logo-img">
            </div>
            <h1>Marcomedia</h1>
            <p>Sign in to your account</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle alert-icon"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['registered']) && $_GET['registered'] === 'true'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle alert-icon"></i>
                <div>Registration successful! Please log in with your credentials.</div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['logout']) && $_GET['logout'] === 'true'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle alert-icon"></i>
                <div>You have been successfully logged out.</div>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="loginForm" novalidate>
            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fas fa-envelope"></i>Email Address
                </label>
                <div class="input-wrapper">
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="Enter your email address" required 
                           value="<?php echo htmlspecialchars($email); ?>"
                           autocomplete="email">
                    <i class="fas fa-envelope input-icon"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fas fa-lock"></i>Password
                </label>
                <div class="input-wrapper">
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Enter your password" required
                           autocomplete="current-password">
                    <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="forgot-password">
                <a href="forgot-password.php">
                    <i class="fas fa-key"></i>Forgot your password?
                </a>
            </div>
            
            <button type="submit" class="btn" id="submitBtn">
                <i class="fas fa-sign-in-alt"></i>
                <span>Sign In</span>
                <div class="loading" id="loading">
                    <i class="fas fa-spinner spinner"></i>
                </div>
            </button>
        </form>

        
        <div class="links">
            <a href="register.php">
                <i class="fas fa-user-plus"></i>
                <span>Create New Account</span>
            </a>
            <a href="index.php">
                <i class="fas fa-home"></i>
                <span>Back to Home</span>
            </a>
        </div>
    </div>

    <script>
        // Password toggle functionality
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordInput = document.getElementById('password');
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        const loading = document.getElementById('loading');

        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
            
            // Update aria-label for accessibility
            this.setAttribute('aria-label', 
                type === 'password' ? 'Show password' : 'Hide password');
        });

        // Form submission with loading state
        loginForm.addEventListener('submit', function(e) {
            // Basic client-side validation
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                return;
            }
            
            // Show loading state
            submitBtn.disabled = true;
            loading.style.display = 'inline-block';
            submitBtn.querySelector('span').style.display = 'none';
        });

        // Add modern input interactions
        document.querySelectorAll('.form-control').forEach(input => {
            // Add focus styles programmatically
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });

        // Auto-focus email field on page load
        document.getElementById('email').focus();

        // Add enter key support for form submission
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const focused = document.activeElement;
                if (focused && (focused.type === 'email' || focused.type === 'password')) {
                    loginForm.requestSubmit();
                }
            }
        });
    </script>
</body>
</html>