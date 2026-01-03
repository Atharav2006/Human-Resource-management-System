<?php
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (!empty($email)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->fetch()) {
            $success = "If this email exists, password reset instructions will be provided by admin.";
        } else {
            $error = "Email not found.";
        }
    } else {
        $error = "Please enter your email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password - Dayflow HRMS</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    /* ===== ODOO THEME VARIABLES ===== */
    :root {
        --odoo-primary: #714B67;       /* Odoo Purple */
        --odoo-secondary: #00A09D;     /* Odoo Teal */
        --odoo-success: #28a745;
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
        --odoo-gradient-success: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        --odoo-gradient-danger: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        --odoo-radius: 12px;
        --odoo-radius-sm: 6px;
        --odoo-radius-lg: 16px;
    }

    /* ===== ODOO BODY STYLING ===== */
    body {
        background-color: var(--odoo-bg);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        color: #333;
        line-height: 1.6;
        margin: 0;
        padding: 20px;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* ===== LOGO STYLING ===== */
    .logo-container {
        text-align: center;
        margin-bottom: 30px;
    }

    .logo {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 80px;
        height: 80px;
        background: var(--odoo-gradient);
        border-radius: 20px;
        margin-bottom: 20px;
        box-shadow: 0 10px 20px rgba(113, 75, 103, 0.2);
    }

    .logo i {
        font-size: 36px;
        color: white;
    }

    .brand-name {
        font-size: 28px;
        font-weight: 700;
        color: var(--odoo-primary);
        letter-spacing: 1px;
        margin-bottom: 5px;
    }

    .brand-tagline {
        color: #666;
        font-size: 14px;
        letter-spacing: 0.5px;
    }

    /* ===== CARD STYLING ===== */
    .card {
        background: var(--odoo-card-bg);
        border-radius: var(--odoo-radius-lg);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        padding: 40px;
        width: 100%;
        max-width: 480px;
        border: 1px solid var(--odoo-border);
        animation: fadeInUp 0.6s ease;
        position: relative;
        overflow: hidden;
    }

    .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--odoo-gradient);
    }

    .card-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .card-icon {
        width: 70px;
        height: 70px;
        background: rgba(113, 75, 103, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }

    .card-icon i {
        font-size: 32px;
        color: var(--odoo-primary);
    }

    .card-title {
        font-weight: 700;
        font-size: 24px;
        color: var(--odoo-primary);
        margin-bottom: 10px;
    }

    .card-subtitle {
        color: #666;
        font-size: 15px;
        line-height: 1.5;
        margin-bottom: 0;
    }

    /* ===== ODOO ALERTS ===== */
    .alert {
        border-radius: var(--odoo-radius-sm);
        padding: 16px 20px;
        margin-bottom: 25px;
        border: none;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        animation: slideIn 0.3s ease;
    }

    .alert-success {
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.1) 0%, rgba(32, 201, 151, 0.05) 100%);
        color: var(--odoo-success);
        border-left: 4px solid var(--odoo-success);
    }

    .alert-danger {
        background: linear-gradient(135deg, rgba(217, 83, 79, 0.1) 0%, rgba(217, 83, 79, 0.05) 100%);
        color: var(--odoo-danger);
        border-left: 4px solid var(--odoo-danger);
    }

    .alert i {
        font-size: 18px;
        margin-top: 2px;
    }

    .alert-content {
        flex: 1;
    }

    /* ===== FORM STYLING ===== */
    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        margin-bottom: 10px;
        color: var(--odoo-primary);
        font-size: 15px;
    }

    .form-label i {
        color: var(--odoo-secondary);
        font-size: 14px;
    }

    .input-group {
        position: relative;
    }

    .form-control {
        padding: 16px 20px;
        padding-left: 50px;
        border: 1px solid var(--odoo-border);
        border-radius: var(--odoo-radius-sm);
        font-size: 15px;
        transition: all 0.3s ease;
        background: var(--odoo-light);
        width: 100%;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--odoo-secondary);
        box-shadow: 0 0 0 3px rgba(0, 160, 157, 0.1);
        background: white;
    }

    .input-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--odoo-secondary);
        font-size: 18px;
        z-index: 1;
    }

    /* ===== BUTTON STYLING ===== */
    .btn-primary {
        background: var(--odoo-gradient);
        color: white;
        border: none;
        padding: 16px 30px;
        border-radius: var(--odoo-radius-sm);
        font-weight: 600;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        text-decoration: none;
        width: 100%;
        margin-top: 10px;
    }

    .btn-primary:hover {
        background: var(--odoo-gradient-light);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(113, 75, 103, 0.3);
        color: white;
        text-decoration: none;
    }

    .btn-primary:active {
        transform: translateY(0);
    }

    /* ===== FOOTER LINKS ===== */
    .footer-links {
        text-align: center;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid var(--odoo-border);
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: var(--odoo-secondary);
        text-decoration: none;
        font-weight: 500;
        font-size: 15px;
        transition: all 0.3s ease;
    }

    .back-link:hover {
        color: var(--odoo-primary);
        gap: 10px;
        text-decoration: none;
    }

    .back-link i {
        font-size: 14px;
    }

    /* ===== ANIMATIONS ===== */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

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

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    /* ===== HELP TEXT ===== */
    .help-text {
        background: linear-gradient(135deg, rgba(113, 75, 103, 0.05) 0%, rgba(0, 160, 157, 0.05) 100%);
        border-radius: var(--odoo-radius-sm);
        padding: 15px;
        margin-top: 20px;
        border: 1px solid var(--odoo-border);
    }

    .help-text p {
        margin: 0;
        font-size: 14px;
        color: #666;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .help-text i {
        color: var(--odoo-secondary);
        font-size: 16px;
        margin-top: 2px;
    }

    /* ===== RESPONSIVE DESIGN ===== */
    @media (max-width: 768px) {
        body {
            padding: 15px;
        }
        
        .card {
            padding: 30px 25px;
            max-width: 100%;
        }
        
        .logo {
            width: 70px;
            height: 70px;
        }
        
        .logo i {
            font-size: 32px;
        }
        
        .brand-name {
            font-size: 24px;
        }
        
        .card-title {
            font-size: 22px;
        }
    }

    @media (max-width: 480px) {
        .card {
            padding: 25px 20px;
        }
        
        .logo {
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
        }
        
        .logo i {
            font-size: 28px;
        }
        
        .brand-name {
            font-size: 22px;
        }
        
        .card-title {
            font-size: 20px;
        }
        
        .card-icon {
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
        }
        
        .card-icon i {
            font-size: 28px;
        }
        
        .form-control {
            padding: 14px 16px;
            padding-left: 45px;
        }
        
        .input-icon {
            left: 16px;
            font-size: 16px;
        }
        
        .btn-primary {
            padding: 14px 20px;
        }
    }
</style>
</head>

<body>

<div class="card">
    <div class="logo-container">
        <div class="logo">
            <i class="fas fa-user-lock"></i>
        </div>
        <div class="brand-name">Dayflow HRMS</div>
        <div class="brand-tagline">Human Resource Management System</div>
    </div>

    <div class="card-header">
        <div class="card-icon">
            <i class="fas fa-key"></i>
        </div>
        <h3 class="card-title">Reset Your Password</h3>
        <p class="card-subtitle">
            Enter your registered email address and we'll help you reset your password
        </p>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <div class="alert-content"><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <div class="alert-content"><?= htmlspecialchars($success) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" id="forgotPasswordForm">
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-envelope"></i>
                Email Address
            </label>
            <div class="input-group">
                <i class="fas fa-at input-icon"></i>
                <input type="email" name="email" class="form-control" 
                       placeholder="Enter your registered email address" required
                       oninput="validateEmail(this)">
            </div>
            <small class="text-muted" id="emailHelp">Enter the email you used during registration</small>
        </div>

        <button type="submit" class="btn-primary" id="submitBtn">
            <i class="fas fa-paper-plane"></i>
            Send Reset Instructions
        </button>
    </form>

    <div class="help-text">
        <p>
            <i class="fas fa-info-circle"></i>
            After submitting, the admin will verify your request and provide password reset instructions if the email exists in our system.
        </p>
    </div>

    <div class="footer-links">
        <a href="index.php" class="back-link">
            <i class="fas fa-arrow-left"></i>
            Back to Login
        </a>
    </div>
</div>

<script>
    // Email validation
    function validateEmail(input) {
        const email = input.value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (email && !emailRegex.test(email)) {
            input.style.borderColor = 'var(--odoo-danger)';
            input.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.1)';
            document.getElementById('emailHelp').style.color = 'var(--odoo-danger)';
            document.getElementById('emailHelp').textContent = 'Please enter a valid email address';
            return false;
        } else {
            input.style.borderColor = email ? 'var(--odoo-success)' : 'var(--odoo-border)';
            input.style.boxShadow = email ? '0 0 0 3px rgba(40, 167, 69, 0.1)' : 'none';
            document.getElementById('emailHelp').style.color = email ? 'var(--odoo-success)' : '#666';
            document.getElementById('emailHelp').textContent = email ? 'Valid email format' : 'Enter the email you used during registration';
            return true;
        }
    }

    // Form submission
    document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
        const emailInput = document.querySelector('input[name="email"]');
        const email = emailInput.value;
        
        if (!validateEmail(emailInput)) {
            e.preventDefault();
            emailInput.focus();
            emailInput.style.animation = 'shake 0.5s ease';
            setTimeout(() => {
                emailInput.style.animation = '';
            }, 500);
            return false;
        }
        
        // Change button state
        const submitBtn = document.getElementById('submitBtn');
        const originalHTML = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        submitBtn.disabled = true;
        
        // Revert after 3 seconds if form doesn't submit (for demo)
        setTimeout(() => {
            if (submitBtn.disabled) {
                submitBtn.innerHTML = originalHTML;
                submitBtn.disabled = false;
            }
        }, 3000);
        
        return true;
    });

    // Focus on email field on page load
    document.addEventListener('DOMContentLoaded', function() {
        const emailInput = document.querySelector('input[name="email"]');
        emailInput.focus();
        
        // Add enter key support
        emailInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('submitBtn').click();
            }
        });
    });

    // Add visual feedback for input focus
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'translateY(-2px)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'translateY(0)';
        });
    });
</script>
</body>
</html>