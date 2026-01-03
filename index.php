<?php
// Start session
session_start();

// Include DB connection
require_once 'config/db.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        // Prepare query to fetch user by email
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Check if account is ACTIVE
            if ($user['status'] != 'ACTIVE') {
                $error = "Your account is inactive. Contact admin.";
            } elseif (password_verify($password, $user['password'])) {
                // Login success, set session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['employee_id'] = $user['employee_id'];
                $_SESSION['email'] = $user['email'];

                // Redirect based on role
                if ($user['role'] == 'ADMIN') {
                    header("Location: admin/dashboard.php");
                } elseif ($user['role'] == 'HR') {
                    header("Location: hr/dashboard.php");
                } else {
                    header("Location: employee/dashboard.php");
                }
                exit();
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "Email not registered.";
        }
    } else {
        $error = "Please enter both email and password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dayflow HRMS - Login</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== ODOO THEME VARIABLES ===== */
        :root {
            --odoo-primary: #714B67;       /* Odoo Purple */
            --odoo-secondary: #00A09D;     /* Odoo Teal */
            --odoo-accent: #F0F0F0;        /* Light Gray */
            --odoo-success: #5cb85c;
            --odoo-warning: #f0ad4e;
            --odoo-danger: #d9534f;
            --odoo-info: #5bc0de;
            --odoo-light: #f8f9fa;
            --odoo-dark: #343a40;
            --odoo-bg: #F5F5F5;
            --odoo-card-bg: #ffffff;
            --odoo-border: #e6e6e6;
            --odoo-shadow: rgba(0, 0, 0, 0.1);
            --odoo-gradient: linear-gradient(135deg, var(--odoo-primary) 0%, var(--odoo-secondary) 100%);
            --odoo-gradient-light: linear-gradient(135deg, #8A5C7C 0%, #00B3B0 100%);
            --odoo-radius: 12px;
            --odoo-radius-sm: 6px;
            --odoo-radius-lg: 16px;
        }

        /* ===== ODOO BODY STYLING ===== */
        body {
            background: linear-gradient(135deg, var(--odoo-primary) 0%, var(--odoo-secondary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            padding: 20px;
            margin: 0;
        }

        /* ===== ODOO LOGIN BOX ===== */
        .login-box {
            max-width: 420px;
            width: 100%;
            margin: 0 auto;
            padding: 45px 40px;
            background: var(--odoo-card-bg);
            border-radius: var(--odoo-radius-lg);
            box-shadow: 0 15px 50px rgba(113, 75, 103, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .login-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--odoo-gradient);
        }

        /* ===== ODOO HEADER STYLING ===== */
        .login-box h3 {
            font-weight: 700;
            color: var(--odoo-primary);
            font-size: 28px;
            text-align: center;
            margin-bottom: 10px;
            position: relative;
            padding-bottom: 15px;
        }

        .login-box h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--odoo-secondary);
            border-radius: 2px;
        }

        .login-subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 35px;
            font-size: 15px;
            line-height: 1.5;
        }

        /* ===== ODOO ERROR STYLING ===== */
        .error {
            background: linear-gradient(135deg, rgba(217, 83, 79, 0.1) 0%, rgba(217, 83, 79, 0.05) 100%);
            color: var(--odoo-danger);
            padding: 15px 18px;
            border-radius: var(--odoo-radius-sm);
            margin-bottom: 25px;
            font-size: 14px;
            border-left: 4px solid var(--odoo-danger);
            border: 1px solid rgba(217, 83, 79, 0.2);
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error::before {
            content: '⚠️';
            font-size: 16px;
        }

        /* ===== ODOO FORM STYLING ===== */
        .mb-3 {
            margin-bottom: 24px !important;
        }

        label {
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            display: block;
            font-size: 14px;
        }

        .form-control {
            border-radius: var(--odoo-radius-sm);
            padding: 14px 16px;
            border: 1px solid var(--odoo-border);
            font-size: 15px;
            transition: all 0.3s ease;
            background-color: var(--odoo-light);
        }

        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(113, 75, 103, 0.15);
            border-color: var(--odoo-secondary);
            background-color: white;
            transform: translateY(-1px);
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--odoo-secondary);
            font-size: 18px;
        }

        .input-icon .form-control {
            padding-left: 45px;
        }

        /* ===== ODOO BUTTON STYLING ===== */
        .btn-primary {
            background: var(--odoo-gradient);
            border: none;
            border-radius: var(--odoo-radius-sm);
            padding: 15px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
            font-size: 16px;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            background: var(--odoo-gradient-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(113, 75, 103, 0.25);
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(113, 75, 103, 0.2);
        }

        .btn-primary::after {
            content: '→';
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .btn-primary:hover::after {
            opacity: 1;
            right: 15px;
        }

        /* ===== ODOO LINKS STYLING ===== */
        .login-links {
            margin-top: 25px;
            text-align: center;
            font-size: 14px;
            color: #666;
            line-height: 1.6;
        }

        .login-links a {
            color: var(--odoo-secondary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .login-links a:hover {
            color: var(--odoo-primary);
            text-decoration: underline;
        }

        .separator {
            margin: 0 8px;
            color: #ccc;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-box {
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 480px) {
            .login-box {
                padding: 35px 25px;
                margin: 15px;
            }
            
            body {
                padding: 15px;
            }
        }

        /* ===== UTILITY CLASSES ===== */
        .text-center {
            text-align: center !important;
        }

        .w-100 {
            width: 100% !important;
        }

        .mb-4 {
            margin-bottom: 1.5rem !important;
        }
    </style>
</head>
<body>

<div class="login-box">
    <h3 class="text-center">Dayflow HRMS</h3>
    <p class="login-subtitle">Sign in to your HR management system</p>

    <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3 input-icon">
            <label>Email Address</label>
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" class="form-control" required placeholder="you@company.com">
        </div>
        
        <div class="mb-3 input-icon">
            <label>Password</label>
            <i class="fas fa-lock"></i>
            <input type="password" name="password" class="form-control" required placeholder="Enter your password">
        </div>

        <button type="submit" class="btn btn-primary w-100">Sign In</button>
        
        <div class="login-links">
            <a href="forgot_password.php"><i class="fas fa-key"></i> Forgot Password?</a>
            <span class="separator">|</span>
            <a href="register.php"><i class="fas fa-user-plus"></i> Create Account</a>
        </div>
    </form>
</div>

</body>
</html>