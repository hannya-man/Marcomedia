<?php
// Start output buffering at the very beginning
ob_start();

// Use a unique session name for website
session_name('website_session');
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Clean buffer before redirect
    ob_end_clean();
    header("Location: index.php");
    exit;
}

require 'db.php';

$error = '';
$success = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    // Validate all fields
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        if ($stmt === false) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email address is already registered";
            } else {
                // Hash the password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Generate a username from email (first part before @)
                $username = strtolower(explode('@', $email)[0]);
                
                // Insert new user with 'customer' role
                $stmt = $conn->prepare("INSERT INTO users (fullname, username, email, password_hash, role) VALUES (?, ?, ?, ?, 'customer')");
                if ($stmt === false) {
                    $error = "Database error: " . $conn->error;
                } else {
                    $stmt->bind_param("ssss", $name, $username, $email, $password_hash);
                    
                    if ($stmt->execute()) {
                        // Set session data for success page
                        $_SESSION['registration_success'] = true;
                        $_SESSION['registered_user_name'] = $name;
                        $_SESSION['registered_user_email'] = $email;
                        
                        // Registration successful - clean buffer and redirect
                        ob_end_clean();
                        header("Location: register_success.php");
                        exit;
                    } else {
                        $error = "Registration failed: " . $stmt->error;
                    }
                }
            }
            $stmt->close();
        }
    }
}

// If we get here, either it's not a POST request or there was an error
// Continue with displaying the form
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Marcomedia POS</title>
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

        .register-container {
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

        .register-container::before {
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

        /* Enhanced Password Strength Indicator */
        .password-strength {
            margin-top: 15px;
            opacity: 0;
            transition: var(--transition);
            transform: translateY(-10px);
        }

        .password-strength.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .strength-meter {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .strength-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--light-text-color);
            transition: var(--transition);
        }

        .strength-value {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: var(--transition);
        }

        .strength-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            position: relative;
            margin-bottom: 12px;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            border-radius: 10px;
            transition: all 0.5s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            background: var(--gradient-primary);
            position: relative;
            overflow: hidden;
        }

        .strength-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .strength-fill.very-weak {
            width: 20%;
            background: linear-gradient(90deg, #ef4444, #f87171);
        }

        .strength-fill.weak {
            width: 40%;
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .strength-fill.fair {
            width: 60%;
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
        }

        .strength-fill.good {
            width: 80%;
            background: linear-gradient(90deg, #10b981, #34d399);
        }

        .strength-fill.strong {
            width: 100%;
            background: linear-gradient(90deg, #059669, #10b981);
        }

        .strength-fill.excellent {
            width: 100%;
            background: linear-gradient(90deg, #059669, #10b981, #34d399);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .strength-requirements {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            color: var(--light-text-color);
            transition: var(--transition);
        }

        .requirement.met {
            color: var(--success-color);
        }

        .requirement-icon {
            font-size: 0.7rem;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: transparent;
            transition: var(--transition);
        }

        .requirement.met .requirement-icon {
            background: var(--success-color);
            color: white;
        }

        .password-match {
            font-size: 0.9rem;
            margin-top: 6px;
            display: none;
            font-weight: 500;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }

        .match-success {
            color: var(--success-color);
        }

        .match-error {
            color: var(--error-color);
        }

        .terms {
            font-size: 0.9rem;
            color: var(--light-text-color);
            text-align: center;
            margin-top: 30px;
            line-height: 1.5;
        }

        .terms a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .terms a:hover {
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

        /* Features Section */
        .features {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 40px;
            padding: 30px;
            background: linear-gradient(135deg, #eff6ff, #f0f9ff);
            border-radius: var(--border-radius);
            border: 1px solid #dbeafe;
        }

        .feature {
            text-align: center;
        }

        .feature-icon {
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .feature-text {
            font-size: 0.85rem;
            color: var(--light-text-color);
            font-weight: 500;
            line-height: 1.4;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .register-container {
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

            .strength-requirements {
                grid-template-columns: 1fr;
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

            .register-container {
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
            .register-container {
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

            .features {
                background: #1e293b;
                border-color: #334155;
            }

            .strength-bar {
                background: #334155;
            }

            .requirement-icon {
                background: #475569;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <div class="logo-icon">
                <img src="images/rsz_logo.png" alt="Marcomedia Logo" class="logo-img">
            </div>
            <h1>Marcomedia</h1>
            <p>Create your account</p>
        </div>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle alert-icon"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle alert-icon"></i>
                <div><?php echo htmlspecialchars($success); ?></div>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" id="registerForm" novalidate>
            <div class="form-group">
                <label for="name" class="form-label">
                    <i class="fas fa-user"></i>Full Name
                </label>
                <div class="input-wrapper">
                    <input type="text" id="name" name="name" class="form-control" 
                           placeholder="Enter your full name" required 
                           value="<?php echo htmlspecialchars($name); ?>"
                           autocomplete="name">
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>
            
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
                           placeholder="Create a password" required
                           minlength="6"
                           autocomplete="new-password">
                    <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <!-- Enhanced Password Strength Indicator -->
                <div class="password-strength" id="passwordStrength">
                    <div class="strength-meter">
                        <span class="strength-label">Password Strength</span>
                        <span class="strength-value" id="strengthValue">None</span>
                    </div>
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                    <div class="strength-requirements">
                        <div class="requirement" id="req-length">
                            <span class="requirement-icon"><i class="fas fa-check"></i></span>
                            <span>At least 6 characters</span>
                        </div>
                        <div class="requirement" id="req-uppercase">
                            <span class="requirement-icon"><i class="fas fa-check"></i></span>
                            <span>Uppercase letter</span>
                        </div>
                        <div class="requirement" id="req-lowercase">
                            <span class="requirement-icon"><i class="fas fa-check"></i></span>
                            <span>Lowercase letter</span>
                        </div>
                        <div class="requirement" id="req-number">
                            <span class="requirement-icon"><i class="fas fa-check"></i></span>
                            <span>Number</span>
                        </div>
                        <div class="requirement" id="req-special">
                            <span class="requirement-icon"><i class="fas fa-check"></i></span>
                            <span>Special character</span>
                        </div>
                        <div class="requirement" id="req-length-8">
                            <span class="requirement-icon"><i class="fas fa-check"></i></span>
                            <span>8+ characters</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">
                    <i class="fas fa-lock"></i>Confirm Password
                </label>
                <div class="input-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                           placeholder="Confirm your password" required
                           minlength="6"
                           autocomplete="new-password">
                    <button type="button" class="password-toggle" id="confirmPasswordToggle" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-match" id="passwordMatch">
                    <i class="fas fa-check-circle"></i>
                    <span>Passwords match</span>
                </div>
            </div>
            
            <button type="submit" class="btn" id="submitBtn">
                <i class="fas fa-user-plus"></i>
                <span>Create Account</span>
                <div class="loading" id="loading">
                    <i class="fas fa-spinner spinner"></i>
                </div>
            </button>
        </form>

        <div class="terms">
            By creating an account, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
        </div>
        
        <div class="links">
            <a href="login.php">
                <i class="fas fa-sign-in-alt"></i>
                <span>Already have an account? Sign in</span>
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
        const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const registerForm = document.getElementById('registerForm');
        const submitBtn = document.getElementById('submitBtn');
        const loading = document.getElementById('loading');
        const passwordMatch = document.getElementById('passwordMatch');
        
        // Password strength elements
        const passwordStrength = document.getElementById('passwordStrength');
        const strengthFill = document.getElementById('strengthFill');
        const strengthValue = document.getElementById('strengthValue');
        
        // Requirement elements
        const reqLength = document.getElementById('req-length');
        const reqUppercase = document.getElementById('req-uppercase');
        const reqLowercase = document.getElementById('req-lowercase');
        const reqNumber = document.getElementById('req-number');
        const reqSpecial = document.getElementById('req-special');
        const reqLength8 = document.getElementById('req-length-8');

        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
            
            // Update aria-label for accessibility
            this.setAttribute('aria-label', 
                type === 'password' ? 'Show password' : 'Hide password');
        });

        confirmPasswordToggle.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            const icon = this.querySelector('i');
            icon.className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
            
            // Update aria-label for accessibility
            this.setAttribute('aria-label', 
                type === 'password' ? 'Show password' : 'Hide password');
        });

        // Password strength checker
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            updatePasswordStrength(password);
            checkPasswordMatch();
        });

        // Confirm password matching
        confirmPasswordInput.addEventListener('input', function() {
            checkPasswordMatch();
        });

        function updatePasswordStrength(password) {
            if (password.length === 0) {
                passwordStrength.classList.remove('visible');
                return;
            }
            
            // Show the strength indicator
            passwordStrength.classList.add('visible');
            
            let score = 0;
            let requirements = {
                length: false,
                uppercase: false,
                lowercase: false,
                number: false,
                special: false,
                length8: false
            };
            
            // Check requirements
            if (password.length >= 6) {
                score += 1;
                requirements.length = true;
                reqLength.classList.add('met');
            } else {
                reqLength.classList.remove('met');
            }
            
            if (/[A-Z]/.test(password)) {
                score += 1;
                requirements.uppercase = true;
                reqUppercase.classList.add('met');
            } else {
                reqUppercase.classList.remove('met');
            }
            
            if (/[a-z]/.test(password)) {
                score += 1;
                requirements.lowercase = true;
                reqLowercase.classList.add('met');
            } else {
                reqLowercase.classList.remove('met');
            }
            
            if (/[0-9]/.test(password)) {
                score += 1;
                requirements.number = true;
                reqNumber.classList.add('met');
            } else {
                reqNumber.classList.remove('met');
            }
            
            if (/[^A-Za-z0-9]/.test(password)) {
                score += 1;
                requirements.special = true;
                reqSpecial.classList.add('met');
            } else {
                reqSpecial.classList.remove('met');
            }
            
            if (password.length >= 8) {
                score += 1;
                requirements.length8 = true;
                reqLength8.classList.add('met');
            } else {
                reqLength8.classList.remove('met');
            }
            
            // Calculate strength level
            let strengthLevel = '';
            let strengthText = '';
            
            if (score <= 1) {
                strengthLevel = 'very-weak';
                strengthText = 'Very Weak';
            } else if (score <= 2) {
                strengthLevel = 'weak';
                strengthText = 'Weak';
            } else if (score <= 3) {
                strengthLevel = 'fair';
                strengthText = 'Fair';
            } else if (score <= 4) {
                strengthLevel = 'good';
                strengthText = 'Good';
            } else if (score <= 5) {
                strengthLevel = 'strong';
                strengthText = 'Strong';
            } else {
                strengthLevel = 'excellent';
                strengthText = 'Excellent!';
            }
            
            // Update UI
            strengthFill.className = 'strength-fill ' + strengthLevel;
            strengthValue.textContent = strengthText;
            
            // Update text color based on strength
            if (score <= 1) {
                strengthValue.style.color = '#ef4444';
            } else if (score <= 2) {
                strengthValue.style.color = '#f59e0b';
            } else if (score <= 3) {
                strengthValue.style.color = '#3b82f6';
            } else if (score <= 4) {
                strengthValue.style.color = '#10b981';
            } else {
                strengthValue.style.color = '#059669';
            }
        }

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (confirmPassword.length === 0) {
                passwordMatch.style.display = 'none';
                return;
            }
            
            if (password === confirmPassword) {
                passwordMatch.innerHTML = '<i class="fas fa-check-circle"></i><span>Passwords match</span>';
                passwordMatch.className = 'password-match match-success';
                passwordMatch.style.display = 'flex';
            } else {
                passwordMatch.innerHTML = '<i class="fas fa-times-circle"></i><span>Passwords do not match</span>';
                passwordMatch.className = 'password-match match-error';
                passwordMatch.style.display = 'flex';
            }
        }

        // Form submission with loading state
        registerForm.addEventListener('submit', function(e) {
            // Basic client-side validation
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (!name || !email || !password || !confirmPassword) {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                passwordMatch.innerHTML = '<i class="fas fa-times-circle"></i><span>Please fix password mismatch before submitting</span>';
                passwordMatch.className = 'password-match match-error';
                passwordMatch.style.display = 'flex';
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

        // Auto-focus name field on page load
        document.getElementById('name').focus();

        // Add enter key support for form submission
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const focused = document.activeElement;
                if (focused && (focused.type === 'text' || focused.type === 'email' || focused.type === 'password')) {
                    registerForm.requestSubmit();
                }
            }
        });
    </script>
</body>
</html>