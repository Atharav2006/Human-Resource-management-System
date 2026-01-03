<?php
// Start session
session_start();

// Include DB connection
require_once 'config/db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = trim($_POST['employee_id']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Basic validation
    if (empty($employee_id) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email or employee_id already exists
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email OR employee_id = :employee_id LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':employee_id', $employee_id);
        $stmt->execute();

        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            $error = "Email or Employee ID already exists.";
        } else {
            // Hash the password
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);

            // Insert into users table
            $insert = $conn->prepare("INSERT INTO users (employee_id, email, password, role) VALUES (:employee_id, :email, :password, :role)");
            $insert->bindParam(':employee_id', $employee_id);
            $insert->bindParam(':email', $email);
            $insert->bindParam(':password', $password_hashed);
            $insert->bindParam(':role', $role);

            if ($insert->execute()) {
                $_SESSION['success'] = "User registered successfully. You can now login.";
                header("Location: index.php");
                exit();
            } else {
                $error = "Failed to register user. Try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dayflow HRMS - Register</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== ODOO THEME VARIABLES ===== */
        :root {
            --odoo-primary: #714B67;       /* Odoo Purple */
            --odoo-secondary: #00A09D;     /* Odoo Teal */
            --odoo-success: #28a745;       /* Green for success */
            --odoo-accent: #F0F0F0;        /* Light Gray */
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
            --odoo-gradient-success: linear-gradient(135deg, #28a745 0%, #20c997 100%);
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

        /* ===== ODOO REGISTER BOX ===== */
        .register-box {
            max-width: 520px;
            width: 100%;
            margin: 0 auto;
            padding: 45px 40px;
            background: var(--odoo-card-bg);
            border-radius: var(--odoo-radius-lg);
            box-shadow: 0 15px 50px rgba(113, 75, 103, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.5s ease;
        }

        .register-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--odoo-gradient);
        }

        /* ===== ODOO HEADER STYLING ===== */
        .register-box h3 {
            font-weight: 700;
            color: var(--odoo-primary);
            font-size: 28px;
            text-align: center;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 15px;
        }

        .register-box h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--odoo-secondary);
            border-radius: 2px;
        }

        .register-subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 15px;
            line-height: 1.5;
        }

        /* ===== ODOO MESSAGE STYLING ===== */
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

        .success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(32, 201, 151, 0.05) 100%);
            color: var(--odoo-success);
            padding: 15px 18px;
            border-radius: var(--odoo-radius-sm);
            margin-bottom: 25px;
            font-size: 14px;
            border-left: 4px solid var(--odoo-success);
            border: 1px solid rgba(40, 167, 69, 0.2);
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success::before {
            content: '✓';
            font-size: 16px;
            font-weight: bold;
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
            transition: color 0.3s ease;
        }

        label:hover {
            color: var(--odoo-primary);
        }

        .form-control, .form-select {
            border-radius: var(--odoo-radius-sm);
            padding: 14px 16px;
            border: 1px solid var(--odoo-border);
            font-size: 15px;
            transition: all 0.3s ease;
            background-color: var(--odoo-light);
        }

        .form-control:focus, .form-select:focus {
            box-shadow: 0 0 0 3px rgba(113, 75, 103, 0.15);
            border-color: var(--odoo-secondary);
            background-color: white;
            transform: translateY(-1px);
        }

        .form-select {
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23714B67' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 16px 12px;
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
        .btn-success {
            background: var(--odoo-gradient-success);
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

        .btn-success:hover {
            background: linear-gradient(135deg, #218838 0%, #1ba87e 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.25);
        }

        .btn-success:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(40, 167, 69, 0.2);
        }

        .btn-success::after {
            content: '✓';
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .btn-success:hover::after {
            opacity: 1;
            right: 15px;
        }

        /* ===== BACK TO LOGIN BUTTON ===== */
        .btn-link {
            color: var(--odoo-secondary) !important;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 12px;
            border-radius: var(--odoo-radius-sm);
            background: rgba(0, 160, 157, 0.1);
        }

        .btn-link:hover {
            color: var(--odoo-primary) !important;
            background: rgba(113, 75, 103, 0.1);
            transform: translateY(-1px);
            text-decoration: none;
        }

        /* ===== FORM LAYOUT ===== */
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 24px;
        }

        .form-row .mb-3 {
            flex: 1;
            margin-bottom: 0 !important;
        }

        /* ===== PASSWORD STRENGTH ===== */
        .password-strength {
            margin-top: 8px;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            background: #dc3545;
            border-radius: 3px;
            transition: width 0.3s ease, background 0.3s ease;
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
        @media (max-width: 576px) {
            .register-box {
                padding: 35px 25px;
                margin: 15px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 24px;
            }
            
            body {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .register-box {
                padding: 30px 20px;
            }
            
            .register-box h3 {
                font-size: 24px;
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

        .mt-2 {
            margin-top: 0.5rem !important;
        }
    </style>
</head>
<body>

<div class="register-box">
    <h3 class="text-center">Register New User</h3>
    <p class="register-subtitle">Create a new account for Dayflow HRMS</p>

    <?php if (isset($error)): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php elseif (isset($_SESSION['success'])): ?>
        <div class="success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3 input-icon">
            <label><i class="fas fa-id-card"></i> Employee ID</label>
            <i class="fas fa-id-badge"></i>
            <input type="text" name="employee_id" class="form-control" required placeholder="EMP001234">
        </div>
        
        <div class="mb-3 input-icon">
            <label><i class="fas fa-envelope"></i> Email Address</label>
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" class="form-control" required placeholder="employee@company.com">
        </div>
        
        <div class="mb-3 input-icon">
            <label><i class="fas fa-lock"></i> Password</label>
            <i class="fas fa-lock"></i>
            <input type="password" name="password" class="form-control" required placeholder="Create a secure password">
            <div class="password-strength">
                <div class="strength-bar" id="passwordStrength"></div>
            </div>
        </div>
        
        <div class="mb-3">
            <label><i class="fas fa-user-tag"></i> Role</label>
            <select name="role" class="form-select" required>
                <option value="">Select Role</option>
                <option value="ADMIN">ADMIN/HR</option>
                <option value="EMPLOYEE">EMPLOYEE</option>
            </select>
        </div>
        
        <button type="submit" class="btn btn-success w-100 mb-3">
            <i class="fas fa-user-plus"></i> Register User
        </button>
        
        <a href="index.php" class="btn btn-link w-100">
            <i class="fas fa-arrow-left"></i> Back to Login
        </a>
    </form>
</div>

<script>
    // Password strength indicator
    document.querySelector('input[name="password"]').addEventListener('input', function(e) {
        const password = e.target.value;
        const strengthBar = document.getElementById('passwordStrength');
        let strength = 0;
        
        if (password.length >= 8) strength += 25;
        if (/[A-Z]/.test(password)) strength += 25;
        if (/[0-9]/.test(password)) strength += 25;
        if (/[^A-Za-z0-9]/.test(password)) strength += 25;
        
        strengthBar.style.width = strength + '%';
        
        // Change color based on strength
        if (strength < 50) {
            strengthBar.style.background = '#dc3545'; // Red
        } else if (strength < 75) {
            strengthBar.style.background = '#ffc107'; // Yellow
        } else {
            strengthBar.style.background = '#28a745'; // Green
        }
    });
</script>

</body>
</html>