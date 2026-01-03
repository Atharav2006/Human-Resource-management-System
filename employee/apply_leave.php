<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/db.php';
require_once '../config/auth.php';

if ($_SESSION['role'] != 'EMPLOYEE') {
    header("Location: ../index.php");
    exit();
}

$stmt = $conn->prepare("SELECT emp_id FROM employees WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    die("Employee profile not found. Contact admin.");
}

$emp_id = $employee['emp_id'];

// Handle leave application
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_type_id = $_POST['leave_type_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = trim($_POST['reason']);

    $stmt = $conn->prepare("INSERT INTO leave_requests (emp_id, leave_type_id, start_date, end_date, reason) 
                            VALUES (:emp_id, :leave_type_id, :start_date, :end_date, :reason)");
    $stmt->bindParam(':emp_id', $emp_id);
    $stmt->bindParam(':leave_type_id', $leave_type_id);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->bindParam(':reason', $reason);

    if ($stmt->execute()) {
        $success = "Your leave request has been submitted successfully.";
    } else {
        $errorInfo = $stmt->errorInfo();
        $error = "Failed to submit leave request. Error: " . $errorInfo[2];
    }
}

// Fetch leave types
$leave_stmt = $conn->prepare("SELECT * FROM leave_types");
$leave_stmt->execute();
$leave_types = $leave_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply Leave - Dayflow HRMS</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
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
            padding: 0;
            min-height: 100vh;
        }

        /* ===== ODOO CONTAINER ===== */
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
            animation: fadeIn 0.5s ease;
        }

        /* ===== ODOO HEADER ===== */
        .page-header {
            background: var(--odoo-gradient);
            color: white;
            padding: 25px 30px;
            border-radius: var(--odoo-radius-lg);
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(113, 75, 103, 0.2);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 100%);
        }

        .page-header h3 {
            font-weight: 700;
            margin: 0;
            font-size: 28px;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header h3 i {
            font-size: 32px;
            background: rgba(255, 255, 255, 0.2);
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .page-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 8px;
            position: relative;
            z-index: 1;
            padding-left: 75px;
        }

        /* ===== ODOO FORM CARD ===== */
        .form-card {
            background: var(--odoo-card-bg);
            border-radius: var(--odoo-radius-lg);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 35px;
            margin-bottom: 30px;
            border: 1px solid var(--odoo-border);
        }

        /* ===== ODOO FORM ELEMENTS ===== */
        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: var(--odoo-primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
        }

        .form-label i {
            color: var(--odoo-secondary);
            font-size: 18px;
        }

        .form-control, .form-select, .form-textarea {
            border-radius: var(--odoo-radius-sm);
            padding: 14px 16px;
            border: 1px solid var(--odoo-border);
            font-size: 15px;
            transition: all 0.3s ease;
            background-color: var(--odoo-light);
        }

        .form-control:focus, .form-select:focus, .form-textarea:focus {
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

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        /* ===== DATE INPUT STYLING ===== */
        .date-input-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 576px) {
            .date-input-group {
                grid-template-columns: 1fr;
            }
        }

        /* ===== ODOO BUTTONS ===== */
        .btn-primary {
            background: var(--odoo-gradient);
            border: none;
            border-radius: var(--odoo-radius-sm);
            padding: 15px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
            font-size: 16px;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-width: 200px;
            justify-content: center;
        }

        .btn-primary:hover {
            background: var(--odoo-gradient-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(113, 75, 103, 0.25);
            color: white;
            text-decoration: none;
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(113, 75, 103, 0.2);
        }

        .btn-secondary {
            background: rgba(113, 75, 103, 0.1);
            border: 1px solid var(--odoo-border);
            border-radius: var(--odoo-radius-sm);
            padding: 12px 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: var(--odoo-primary);
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: var(--odoo-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(113, 75, 103, 0.2);
            text-decoration: none;
        }

        /* ===== ODOO ALERTS ===== */
        .alert {
            border-radius: var(--odoo-radius-sm);
            padding: 18px 20px;
            margin-bottom: 25px;
            border: none;
            display: flex;
            align-items: center;
            gap: 15px;
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
            font-size: 20px;
        }

        /* ===== ACTION BUTTONS ===== */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        /* ===== LEAVE TYPE OPTIONS ===== */
        .leave-type-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: var(--odoo-radius-sm);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .leave-type-option:hover {
            background: rgba(113, 75, 103, 0.05);
        }

        .leave-type-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--odoo-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
        }

        .leave-type-icon.vacation {
            background: linear-gradient(135deg, #00A09D 0%, #00B3B0 100%);
        }

        .leave-type-icon.sick {
            background: linear-gradient(135deg, #f0ad4e 0%, #f5b759 100%);
        }

        .leave-type-icon.personal {
            background: linear-gradient(135deg, #5bc0de 0%, #6cccee 100%);
        }

        .leave-type-icon.other {
            background: linear-gradient(135deg, #6c757d 0%, #7d868d 100%);
        }

        /* ===== ANIMATIONS ===== */
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

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
                padding: 0 15px;
            }
            
            .page-header {
                padding: 20px;
            }
            
            .page-header h3 {
                font-size: 24px;
            }
            
            .page-header h3 i {
                width: 50px;
                height: 50px;
                font-size: 26px;
            }
            
            .form-card {
                padding: 25px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-primary, .btn-secondary {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .page-header h3 {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .page-subtitle {
                padding-left: 0;
                text-align: center;
            }
            
            .form-card {
                padding: 20px;
            }
        }

        /* ===== UTILITY CLASSES ===== */
        .mt-5 {
            margin-top: 3rem !important;
        }

        .mt-3 {
            margin-top: 1rem !important;
        }

        .mb-3 {
            margin-bottom: 1rem !important;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h3>
            <i class="fas fa-calendar-plus"></i>
            Apply for Leave
        </h3>
        <p class="page-subtitle">Submit your leave request for approval</p>
    </div>

    <!-- Messages -->
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- Leave Application Form -->
    <div class="form-card">
        <form method="POST" action="">
            <!-- Leave Type -->
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-tags"></i>
                    Leave Type
                </label>
                <select name="leave_type_id" class="form-select" required>
                    <option value="">-- Select Leave Type --</option>
                    <?php foreach ($leave_types as $type): ?>
                        <option value="<?= $type['leave_type_id'] ?>">
                            <?= htmlspecialchars($type['leave_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Date Range -->
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-calendar-alt"></i>
                    Date Range
                </label>
                <div class="date-input-group">
                    <div>
                        <label class="form-label" style="font-size: 14px;">
                            <i class="fas fa-calendar-day"></i>
                            Start Date
                        </label>
                        <input type="date" name="start_date" class="form-control" required>
                    </div>
                    <div>
                        <label class="form-label" style="font-size: 14px;">
                            <i class="fas fa-calendar-week"></i>
                            End Date
                        </label>
                        <input type="date" name="end_date" class="form-control" required>
                    </div>
                </div>
            </div>

            <!-- Reason -->
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-comment-dots"></i>
                    Reason for Leave
                </label>
                <textarea name="reason" class="form-control form-textarea" 
                          placeholder="Please provide a detailed reason for your leave request..." 
                          required></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Submit Leave Request
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </form>
    </div>

    <!-- Leave Types Information -->
    <div class="form-card">
        <h5 style="color: var(--odoo-primary); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-info-circle"></i>
            Available Leave Types
        </h5>
        <div class="row">
            <?php foreach ($leave_types as $type): ?>
                <div class="col-md-6 mb-3">
                    <div class="leave-type-option">
                        <?php 
                        $icon_class = 'other';
                        $leave_name = strtolower($type['leave_name']);
                        if (strpos($leave_name, 'vacation') !== false) $icon_class = 'vacation';
                        elseif (strpos($leave_name, 'sick') !== false) $icon_class = 'sick';
                        elseif (strpos($leave_name, 'personal') !== false) $icon_class = 'personal';
                        ?>
                        <div class="leave-type-icon <?= $icon_class ?>">
                            <i class="fas fa-<?= 
                                $icon_class == 'vacation' ? 'umbrella-beach' : 
                                ($icon_class == 'sick' ? 'heartbeat' : 
                                ($icon_class == 'personal' ? 'user' : 'calendar')) 
                            ?>"></i>
                        </div>
                        <div>
                            <strong style="color: var(--odoo-primary);"><?= htmlspecialchars($type['leave_name']) ?></strong>
                            <p style="margin: 5px 0 0 0; color: #666; font-size: 13px;">
                                <?= htmlspecialchars($type['description'] ?? 'No description available') ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    // Add today's date as minimum for date inputs
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        const startDateInput = document.querySelector('input[name="start_date"]');
        const endDateInput = document.querySelector('input[name="end_date"]');
        
        if (startDateInput) {
            startDateInput.min = today;
            startDateInput.addEventListener('change', function() {
                if (endDateInput) {
                    endDateInput.min = this.value;
                    if (endDateInput.value && endDateInput.value < this.value) {
                        endDateInput.value = this.value;
                    }
                }
            });
        }
        
        if (endDateInput) {
            endDateInput.min = today;
        }
        
        // Add character counter for reason textarea
        const reasonTextarea = document.querySelector('textarea[name="reason"]');
        if (reasonTextarea) {
            const counter = document.createElement('div');
            counter.className = 'text-muted';
            counter.style.fontSize = '12px';
            counter.style.marginTop = '5px';
            counter.style.textAlign = 'right';
            reasonTextarea.parentNode.appendChild(counter);
            
            function updateCounter() {
                const length = reasonTextarea.value.length;
                counter.textContent = `${length}/500 characters`;
                if (length > 450) {
                    counter.style.color = '#f0ad4e';
                } else if (length > 490) {
                    counter.style.color = '#d9534f';
                } else {
                    counter.style.color = '#6c757d';
                }
            }
            
            reasonTextarea.addEventListener('input', updateCounter);
            updateCounter();
        }
    });
</script>
</body>
</html>