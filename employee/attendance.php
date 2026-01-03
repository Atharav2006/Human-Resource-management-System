<?php
require_once '../config/db.php';
require_once '../config/auth.php';

// Only EMPLOYEE can access
if ($_SESSION['role'] != 'EMPLOYEE') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get employee ID
$stmt = $conn->prepare("SELECT emp_id FROM employees WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    die("Employee profile not found. Contact admin.");
}

$emp_id = $employee['emp_id'];

// Fetch today's attendance
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT * FROM attendance WHERE emp_id = :emp_id AND attendance_date = :today");
$stmt->bindParam(':emp_id', $emp_id);
$stmt->bindParam(':today', $today);
$stmt->execute();
$attendance = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Attendance - Dayflow HRMS</title>
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
            max-width: 1000px;
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

        .page-header h2 {
            font-weight: 700;
            margin: 0;
            font-size: 28px;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-header h2 i {
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

        /* ===== ATTENDANCE CARD ===== */
        .attendance-card {
            background: var(--odoo-card-bg);
            border-radius: var(--odoo-radius-lg);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 40px;
            margin-bottom: 30px;
            border: 1px solid var(--odoo-border);
            text-align: center;
        }

        .today-date {
            font-size: 20px;
            color: var(--odoo-primary);
            font-weight: 600;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .today-date i {
            color: var(--odoo-secondary);
        }

        /* ===== ATTENDANCE STATUS ===== */
        .attendance-status {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
        }

        .time-display {
            background: rgba(113, 75, 103, 0.05);
            border-radius: var(--odoo-radius);
            padding: 20px;
            width: 100%;
            max-width: 400px;
        }

        .time-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid var(--odoo-border);
        }

        .time-item:last-child {
            border-bottom: none;
        }

        .time-label {
            color: var(--odoo-primary);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .time-value {
            font-family: 'Courier New', monospace;
            font-size: 20px;
            font-weight: 700;
            color: var(--odoo-secondary);
        }

        .time-value.pending {
            color: var(--odoo-warning);
            font-style: italic;
        }

        /* ===== ATTENDANCE BUTTONS ===== */
        .attendance-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }

        .btn-attendance {
            padding: 18px 40px;
            font-size: 18px;
            font-weight: 600;
            border: none;
            border-radius: var(--odoo-radius);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 200px;
            justify-content: center;
        }

        .btn-punch-in {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }

        .btn-punch-out {
            background: linear-gradient(135deg, var(--odoo-primary) 0%, var(--odoo-secondary) 100%);
            color: white;
        }

        .btn-attendance:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-attendance:active {
            transform: translateY(0);
        }

        .btn-attendance i {
            font-size: 22px;
        }

        .btn-attendance:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* ===== STATUS INDICATOR ===== */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            border-radius: 30px;
            font-weight: 600;
            margin: 20px 0;
        }

        .status-present {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .status-absent {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .status-incomplete {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        /* ===== ODOO MESSAGES ===== */
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

        /* ===== BACK BUTTON ===== */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--odoo-secondary);
            text-decoration: none;
            font-weight: 600;
            padding: 12px 25px;
            border-radius: var(--odoo-radius-sm);
            background: rgba(0, 160, 157, 0.1);
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: var(--odoo-secondary);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 160, 157, 0.2);
        }

        /* ===== WORKING HOURS ===== */
        .working-hours {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--odoo-border);
        }

        .hours-display {
            font-size: 24px;
            font-weight: 700;
            color: var(--odoo-primary);
            font-family: 'Courier New', monospace;
        }

        .hours-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
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

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .pulse {
            animation: pulse 2s infinite;
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
            
            .page-header h2 {
                font-size: 24px;
            }
            
            .page-header h2 i {
                width: 50px;
                height: 50px;
                font-size: 26px;
            }
            
            .attendance-card {
                padding: 25px;
            }
            
            .attendance-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-attendance {
                width: 100%;
                max-width: 300px;
            }
        }

        @media (max-width: 480px) {
            .page-header h2 {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .page-subtitle {
                padding-left: 0;
                text-align: center;
            }
            
            .attendance-card {
                padding: 20px;
            }
            
            .time-display {
                padding: 15px;
            }
            
            .time-value {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h2>
            <i class="fas fa-clock"></i>
            Attendance
        </h2>
        <p class="page-subtitle">Mark your daily attendance and track your working hours</p>
    </div>

    <!-- Messages -->
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <!-- Attendance Card -->
    <div class="attendance-card">
        <!-- Today's Date -->
        <div class="today-date">
            <i class="fas fa-calendar-day"></i>
            <?= date('l, F j, Y') ?>
        </div>

        <!-- Attendance Status -->
        <div class="attendance-status">
            <?php if($attendance): ?>
                <?php if($attendance['status'] == 'PRESENT'): ?>
                    <div class="status-indicator status-present">
                        <i class="fas fa-check-circle"></i>
                        Present Today
                    </div>
                <?php elseif($attendance['status'] == 'ABSENT'): ?>
                    <div class="status-indicator status-absent">
                        <i class="fas fa-times-circle"></i>
                        Absent Today
                    </div>
                <?php else: ?>
                    <div class="status-indicator status-incomplete">
                        <i class="fas fa-clock"></i>
                        Attendance Incomplete
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="status-indicator status-absent">
                    <i class="fas fa-clock"></i>
                    Not Checked In Yet
                </div>
            <?php endif; ?>

            <!-- Time Display -->
            <div class="time-display">
                <div class="time-item">
                    <span class="time-label">
                        <i class="fas fa-sign-in-alt"></i>
                        Check In
                    </span>
                    <span class="time-value <?= (!$attendance || !$attendance['check_in']) ? 'pending' : '' ?>">
                        <?= $attendance && $attendance['check_in'] ? date('h:i A', strtotime($attendance['check_in'])) : '--:--' ?>
                    </span>
                </div>
                <div class="time-item">
                    <span class="time-label">
                        <i class="fas fa-sign-out-alt"></i>
                        Check Out
                    </span>
                    <span class="time-value <?= (!$attendance || !$attendance['check_out']) ? 'pending' : '' ?>">
                        <?= $attendance && $attendance['check_out'] ? date('h:i A', strtotime($attendance['check_out'])) : '--:--' ?>
                    </span>
                </div>
            </div>

            <!-- Working Hours Calculation -->
            <?php if($attendance && $attendance['check_in'] && $attendance['check_out']): ?>
                <div class="working-hours">
                    <?php 
                    $check_in = strtotime($attendance['check_in']);
                    $check_out = strtotime($attendance['check_out']);
                    $hours_worked = round(($check_out - $check_in) / 3600, 2);
                    ?>
                    <div class="hours-display"><?= $hours_worked ?> hours</div>
                    <div class="hours-label">Total Working Hours Today</div>
                </div>
            <?php elseif($attendance && $attendance['check_in']): ?>
                <div class="working-hours">
                    <?php 
                    $check_in = strtotime($attendance['check_in']);
                    $current_time = time();
                    $hours_worked = round(($current_time - $check_in) / 3600, 2);
                    ?>
                    <div class="hours-display"><?= $hours_worked ?> hours</div>
                    <div class="hours-label">Currently Working</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Attendance Form -->
        <form action="../actions/attendance_action.php" method="POST">
            <div class="attendance-buttons">
                <?php if(!$attendance || !$attendance['check_in']): ?>
                    <button type="submit" name="action" value="punch_in" class="btn-attendance btn-punch-in pulse">
                        <i class="fas fa-fingerprint"></i>
                        Punch In
                    </button>
                <?php else: ?>
                    <?php if(!$attendance['check_out']): ?>
                        <button type="submit" name="action" value="punch_out" class="btn-attendance btn-punch-out">
                            <i class="fas fa-fingerprint"></i>
                            Punch Out
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn-attendance" disabled style="background: #6c757d;">
                            <i class="fas fa-check-circle"></i>
                            Attendance Complete
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <input type="hidden" name="emp_id" value="<?= $emp_id ?>">
        </form>
    </div>

    <!-- Back Button -->
    <a href="dashboard.php" class="btn-back">
        <i class="fas fa-arrow-left"></i>
        Back to Dashboard
    </a>
</div>

<script>
    // Update working hours in real-time for ongoing sessions
    document.addEventListener('DOMContentLoaded', function() {
        <?php if($attendance && $attendance['check_in'] && !$attendance['check_out']): ?>
            function updateWorkingHours() {
                const checkInTime = new Date('<?= $attendance['check_in'] ?>').getTime();
                const now = new Date().getTime();
                const hoursWorked = ((now - checkInTime) / (1000 * 60 * 60)).toFixed(2);
                
                const hoursDisplay = document.querySelector('.hours-display');
                if (hoursDisplay) {
                    hoursDisplay.textContent = hoursWorked + ' hours';
                }
            }
            
            // Update immediately
            updateWorkingHours();
            
            // Update every minute
            setInterval(updateWorkingHours, 60000);
        <?php endif; ?>
        
        // Add confirmation for punch out
        const punchOutButton = document.querySelector('button[value="punch_out"]');
        if (punchOutButton) {
            punchOutButton.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to punch out for the day?')) {
                    e.preventDefault();
                }
            });
        }
    });
</script>
</body>
</html>