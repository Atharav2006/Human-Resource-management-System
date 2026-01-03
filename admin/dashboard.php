<?php
session_start();
require_once '../config/db.php';
require_once '../config/auth.php';

// Only ADMIN can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'ADMIN') {
    header("Location: ../index.php");
    exit();
}

// Fetch dynamic statistics
try {
    // 1. Total Employees Count
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM employees");
    $stmt->execute();
    $total_employees = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // 2. Employees added this month
    $stmt = $conn->prepare("SELECT COUNT(*) as new_employees FROM employees 
                           WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                           AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stmt->execute();
    $new_employees = $stmt->fetch(PDO::FETCH_ASSOC)['new_employees'];

    // 3. Active Today (marked attendance)
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT emp_id) as active_today 
                           FROM attendance 
                           WHERE DATE(attendance_date) = :today 
                           AND status = 'PRESENT'");
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $active_today = $stmt->fetch(PDO::FETCH_ASSOC)['active_today'];

    // Calculate attendance percentage
    $attendance_percentage = $total_employees > 0 ? round(($active_today / $total_employees) * 100, 1) : 0;

    // 4. Pending Leave Requests
    $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM leave_requests WHERE status = 'PENDING'");
    $stmt->execute();
    $pending_requests = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];

    // Pending requests from yesterday comparison
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $stmt = $conn->prepare("SELECT COUNT(*) as yesterday_pending FROM leave_requests 
                           WHERE status = 'PENDING' 
                           AND DATE(applied_at) = :yesterday");
    $stmt->bindParam(':yesterday', $yesterday);
    $stmt->execute();
    $yesterday_pending = $stmt->fetch(PDO::FETCH_ASSOC)['yesterday_pending'];

    // 5. On Leave Today
    $stmt = $conn->prepare("SELECT COUNT(*) as on_leave FROM leave_requests 
                           WHERE status = 'APPROVED' 
                           AND :today BETWEEN start_date AND end_date");
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $on_leave_today = $stmt->fetch(PDO::FETCH_ASSOC)['on_leave'];

    // 6. Get last login time
    $admin_email = $_SESSION['email'];
    $stmt = $conn->prepare("SELECT last_login FROM users WHERE email = :email");
    $stmt->bindParam(':email', $admin_email);
    $stmt->execute();
    $last_login_row = $stmt->fetch(PDO::FETCH_ASSOC);
    $last_login = $last_login_row ? $last_login_row['last_login'] : date('Y-m-d H:i:s');
    $formatted_last_login = date('F d, Y \a\t h:i A', strtotime($last_login));

    // 7. Total attendance marked today
    $stmt = $conn->prepare("SELECT COUNT(*) as total_today FROM attendance 
                           WHERE DATE(attendance_date) = :today");
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $total_attendance_today = $stmt->fetch(PDO::FETCH_ASSOC)['total_today'];

    // 8. Half day count today
    $stmt = $conn->prepare("SELECT COUNT(*) as half_day_today FROM attendance 
                           WHERE DATE(attendance_date) = :today 
                           AND status = 'HALF-DAY'");
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $half_day_today = $stmt->fetch(PDO::FETCH_ASSOC)['half_day_today'];

    // 9. Absent today
    $stmt = $conn->prepare("SELECT COUNT(*) as absent_today FROM attendance 
                           WHERE DATE(attendance_date) = :today 
                           AND status = 'ABSENT'");
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    $absent_today = $stmt->fetch(PDO::FETCH_ASSOC)['absent_today'];

} catch (PDOException $e) {
    error_log("Dashboard statistics error: " . $e->getMessage());
    // Set default values in case of error
    $total_employees = 0;
    $new_employees = 0;
    $active_today = 0;
    $attendance_percentage = 0;
    $pending_requests = 0;
    $yesterday_pending = 0;
    $on_leave_today = 0;
    $formatted_last_login = 'Today at ' . date('h:i A');
    $total_attendance_today = 0;
    $half_day_today = 0;
    $absent_today = 0;
}

// Calculate trends
$pending_trend = $pending_requests - $yesterday_pending;
$pending_trend_class = $pending_trend > 0 ? 'trend-up' : ($pending_trend < 0 ? 'trend-down' : '');
$pending_trend_icon = $pending_trend > 0 ? 'fa-arrow-up' : ($pending_trend < 0 ? 'fa-arrow-down' : 'fa-minus');
$pending_trend_text = $pending_trend > 0 ? "+$pending_trend from yesterday" : 
                     ($pending_trend < 0 ? "$pending_trend from yesterday" : "Same as yesterday");

// Calculate attendance trend
$attendance_trend = $attendance_percentage >= 90 ? 'trend-up' : 
                   ($attendance_percentage >= 70 ? '' : 'trend-down');
$attendance_trend_icon = $attendance_percentage >= 90 ? 'fa-arrow-up' : 
                        ($attendance_percentage >= 70 ? 'fa-minus' : 'fa-arrow-down');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Dayflow HRMS</title>

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
            --odoo-gradient-success: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            --odoo-gradient-warning: linear-gradient(135deg, #f0ad4e 0%, #f5c469 100%);
            --odoo-gradient-info: linear-gradient(135deg, #5bc0de 0%, #6fd0f0 100%);
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
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
            animation: fadeIn 0.5s ease;
        }

        /* ===== ODOO HEADER ===== */
        .dashboard-header {
            background: var(--odoo-gradient);
            color: white;
            padding: 30px 35px;
            border-radius: var(--odoo-radius-lg);
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(113, 75, 103, 0.2);
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255,255,255,0.1) 100%);
        }

        .dashboard-header h2 {
            font-weight: 700;
            margin: 0;
            font-size: 32px;
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .dashboard-header h2 i {
            font-size: 36px;
            background: rgba(255, 255, 255, 0.2);
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .admin-info {
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }

        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            backdrop-filter: blur(10px);
        }

        .admin-badge i {
            font-size: 12px;
        }

        .admin-email {
            font-size: 16px;
            opacity: 0.9;
        }

        /* ===== STATS BAR ===== */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--odoo-card-bg);
            border-radius: var(--odoo-radius);
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
            border: 1px solid var(--odoo-border);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--odoo-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-content {
            flex: 1;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--odoo-primary);
            line-height: 1;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-trend {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-top: 5px;
        }

        .trend-up {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .trend-down {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .trend-neutral {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
        }

        /* ===== DASHBOARD CARDS ===== */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
        }

        .dashboard-card {
            background: var(--odoo-card-bg);
            border-radius: var(--odoo-radius-lg);
            padding: 30px;
            border: 1px solid var(--odoo-border);
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(113, 75, 103, 0.15);
            border-color: var(--odoo-secondary);
        }

        .dashboard-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--odoo-gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .dashboard-card:hover::before {
            opacity: 1;
        }

        .card-icon {
            width: 70px;
            height: 70px;
            border-radius: var(--odoo-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .dashboard-card:hover .card-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .card-title {
            font-weight: 700;
            color: var(--odoo-primary);
            font-size: 20px;
            margin-bottom: 12px;
        }

        .card-text {
            color: #666;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 25px;
            min-height: 48px;
        }

        /* ===== ACTION BUTTONS ===== */
        .btn-dashboard {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--odoo-gradient);
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 14px 28px;
            border-radius: var(--odoo-radius-sm);
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }

        .btn-dashboard:hover {
            background: var(--odoo-gradient-light);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(113, 75, 103, 0.2);
        }

        .btn-dashboard i {
            font-size: 18px;
        }

        .btn-dashboard::after {
            content: '→';
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            transition: all 0.3s ease;
        }

        .btn-dashboard:hover::after {
            opacity: 1;
            right: 15px;
        }

        /* ===== CARD COLORS ===== */
        .card-employee {
            background: linear-gradient(135deg, rgba(113, 75, 103, 0.1) 0%, rgba(113, 75, 103, 0.05) 100%);
        }

        .card-attendance {
            background: linear-gradient(135deg, rgba(0, 160, 157, 0.1) 0%, rgba(0, 160, 157, 0.05) 100%);
        }

        .card-leave {
            background: linear-gradient(135deg, rgba(91, 192, 222, 0.1) 0%, rgba(91, 192, 222, 0.05) 100%);
        }

        .card-payroll {
            background: linear-gradient(135deg, rgba(240, 173, 78, 0.1) 0%, rgba(240, 173, 78, 0.05) 100%);
        }

        /* ===== LOGOUT BUTTON ===== */
        .logout-section {
            text-align: center;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid var(--odoo-border);
        }

        .btn-logout {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 16px 40px;
            border-radius: var(--odoo-radius);
            transition: all 0.3s ease;
            border: none;
        }

        .btn-logout:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.2);
        }

        /* ===== QUICK ACTIONS ===== */
        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 30px;
        }

        .quick-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(113, 75, 103, 0.1);
            color: var(--odoo-primary);
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            border-radius: var(--odoo-radius-sm);
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .quick-action-btn:hover {
            background: var(--odoo-primary);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
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
            
            .dashboard-header {
                padding: 25px;
            }
            
            .dashboard-header h2 {
                font-size: 28px;
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .dashboard-header h2 i {
                width: 60px;
                height: 60px;
                font-size: 30px;
            }
            
            .admin-info {
                justify-content: center;
                text-align: center;
            }
            
            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .dashboard-cards {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                padding: 20px;
            }
        }

        @media (max-width: 576px) {
            .stats-bar {
                grid-template-columns: 1fr;
            }
            
            .dashboard-card {
                padding: 25px;
            }
            
            .card-icon {
                width: 60px;
                height: 60px;
                font-size: 24px;
            }
            
            .quick-actions {
                justify-content: center;
            }
            
            .btn-logout {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h2>
            <i class="fas fa-chart-line"></i>
            Admin Dashboard
        </h2>
        <div class="admin-info">
            <span class="admin-badge">
                <i class="fas fa-crown"></i>
                Administrator
            </span>
            <span class="admin-email">
                Logged in as <strong><?= htmlspecialchars($_SESSION['email']) ?></strong>
            </span>
            <span class="admin-badge" style="background: rgba(255, 255, 255, 0.15);">
                <i class="fas fa-calendar-alt"></i>
                <?= date('F d, Y') ?>
            </span>
        </div>
    </div>

    <!-- Stats Bar - NOW DYNAMIC -->
    <div class="stats-bar">
        <!-- Total Employees -->
        <div class="stat-card" onclick="window.location.href='employees.php'">
            <div class="stat-icon" style="background: var(--odoo-gradient);">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $total_employees ?></div>
                <div class="stat-label">Total Employees</div>
                <?php if($new_employees > 0): ?>
                <div class="stat-trend trend-up">
                    <i class="fas fa-arrow-up"></i> +<?= $new_employees ?> this month
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Active Today -->
        <div class="stat-card" onclick="window.location.href='attendance.php'">
            <div class="stat-icon" style="background: var(--odoo-gradient-success);">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $active_today ?></div>
                <div class="stat-label">Active Today</div>
                <div class="stat-trend <?= $attendance_trend ?>">
                    <i class="fas <?= $attendance_trend_icon ?>"></i>
                    <?= $attendance_percentage ?>% present
                </div>
            </div>
        </div>
        
        <!-- Pending Requests -->
        <div class="stat-card" onclick="window.location.href='leave_requests.php'">
            <div class="stat-icon" style="background: var(--odoo-gradient-warning);">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $pending_requests ?></div>
                <div class="stat-label">Pending Requests</div>
                <?php if($pending_trend != 0): ?>
                <div class="stat-trend <?= $pending_trend_class ?>">
                    <i class="fas <?= $pending_trend_icon ?>"></i>
                    <?= $pending_trend_text ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- On Leave Today -->
        <div class="stat-card" onclick="window.location.href='leave_requests.php'">
            <div class="stat-icon" style="background: var(--odoo-gradient-info);">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $on_leave_today ?></div>
                <div class="stat-label">On Leave Today</div>
                <div class="stat-trend <?= $on_leave_today > 0 ? 'trend-neutral' : '' ?>">
                    <i class="fas <?= $on_leave_today > 0 ? 'fa-minus' : 'fa-check' ?>"></i>
                    <?= $on_leave_today > 0 ? 'Active leaves' : 'No leaves' ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard Cards -->
    <div class="dashboard-cards">
        <!-- Add Employee -->
        <div class="dashboard-card card-employee">
            <div class="card-icon" style="background: var(--odoo-gradient);">
                <i class="fas fa-user-plus"></i>
            </div>
            <h5 class="card-title">Add Employee</h5>
            <p class="card-text">
                Create new employee profiles, assign system access, and set up initial credentials.
            </p>
            <a href="../admin/add_employee.php" class="btn-dashboard">
                <i class="fas fa-user-plus"></i>
                Add Employee
            </a>
        </div>

        <!-- Employee List -->
        <div class="dashboard-card card-employee">
            <div class="card-icon" style="background: var(--odoo-gradient-success);">
                <i class="fas fa-users"></i>
            </div>
            <h5 class="card-title">Employee List</h5>
            <p class="card-text">
                View, update, and manage all registered employees. Edit profiles and permissions.
            </p>
            <a href="../admin/employees.php" class="btn-dashboard">
                <i class="fas fa-list"></i>
                View Employees
            </a>
        </div>

        <!-- Attendance -->
        <div class="dashboard-card card-attendance">
            <div class="card-icon" style="background: var(--odoo-secondary);">
                <i class="fas fa-clock"></i>
            </div>
            <h5 class="card-title">Attendance Management</h5>
            <p class="card-text">
                Track daily attendance, working hours, and generate attendance reports.
            </p>
            <a href="../admin/attendance.php" class="btn-dashboard">
                <i class="fas fa-chart-bar"></i>
                Open Attendance
            </a>
        </div>

        <!-- Leave Requests -->
        <div class="dashboard-card card-leave">
            <div class="card-icon" style="background: var(--odoo-info);">
                <i class="fas fa-file-alt"></i>
            </div>
            <h5 class="card-title">Leave Requests</h5>
            <p class="card-text">
                Review, approve, or reject employee leave applications. Manage leave balances.
            </p>
            <a href="../admin/leave_requests.php" class="btn-dashboard">
                <i class="fas fa-check-circle"></i>
                View Requests
            </a>
        </div>

        <!-- Payroll -->
        <div class="dashboard-card card-payroll">
            <div class="card-icon" style="background: var(--odoo-warning);">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <h5 class="card-title">Payroll Management</h5>
            <p class="card-text">
                Manage salaries, generate payslips, deductions, and payroll history.
            </p>
            <a href="../admin/payroll.php" class="btn-dashboard">
                <i class="fas fa-calculator"></i>
                Open Payroll
            </a>
        </div>

        <!-- Reports -->
        <div class="dashboard-card" style="background: linear-gradient(135deg, rgba(108, 117, 125, 0.1) 0%, rgba(108, 117, 125, 0.05) 100%);">
            <div class="card-icon" style="background: linear-gradient(135deg, #6c757d 0%, #868e96 100%);">
                <i class="fas fa-chart-pie"></i>
            </div>
            <h5 class="card-title">Analytics & Reports</h5>
            <p class="card-text">
                View comprehensive reports, analytics, and insights about your workforce.
            </p>
            <a href="../admin/reports.php" class="btn-dashboard">
                <i class="fas fa-chart-line"></i>
                View Reports
            </a>
        </div>
    </div>

    <!-- Quick Actions -->
    <div style="margin-top: 30px; padding: 25px; background: var(--odoo-card-bg); border-radius: var(--odoo-radius); border: 1px solid var(--odoo-border);">
        <h5 style="color: var(--odoo-primary); margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-bolt"></i>
            Quick Actions
        </h5>
        <div class="quick-actions">
            <a href="../admin/add_employee.php" class="quick-action-btn">
                <i class="fas fa-user-plus"></i> New Employee
            </a>
            <a href="../admin/attendance.php" class="quick-action-btn">
                <i class="fas fa-clock"></i> Mark Attendance
            </a>
            <a href="../admin/payroll.php" class="quick-action-btn">
                <i class="fas fa-money-bill"></i> Generate Payroll
            </a>
            <a href="../admin/leave_requests.php" class="quick-action-btn">
                <i class="fas fa-calendar-check"></i> Review Leaves
            </a>
            <a href="../admin/reports.php" class="quick-action-btn">
                <i class="fas fa-file-export"></i> Export Data
            </a>
            <a href="../admin/settings.php" class="quick-action-btn">
                <i class="fas fa-cog"></i> System Settings
            </a>
        </div>
    </div>

    <!-- Logout Section -->
    <div class="logout-section">
        <a href="../logout.php" class="btn-logout pulse">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
        <p style="color: #666; margin-top: 15px; font-size: 14px;">
            Last login: <?= $formatted_last_login ?>
        </p>
    </div>

</div>

<script>
    // Update real-time statistics
    document.addEventListener('DOMContentLoaded', function() {
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            });
            const timeBadge = document.querySelector('.admin-badge .fa-calendar-alt');
            if (timeBadge) {
                timeBadge.parentNode.innerHTML = 
                    `<i class="fas fa-clock"></i> ${timeString}`;
            }
        }
        
        // Update every minute
        updateTime();
        setInterval(updateTime, 60000);
        
        // Add hover effects to quick action buttons
        const quickActions = document.querySelectorAll('.quick-action-btn');
        quickActions.forEach(btn => {
            btn.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px) scale(1.05)';
            });
            
            btn.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
        
        // Add notification badge for pending requests if any
        const pendingRequests = <?= $pending_requests ?>;
        if (pendingRequests > 0) {
            const pendingBadge = document.createElement('span');
            pendingBadge.className = 'badge bg-warning';
            pendingBadge.style.position = 'absolute';
            pendingBadge.style.top = '15px';
            pendingBadge.style.right = '15px';
            pendingBadge.style.padding = '5px 8px';
            pendingBadge.style.fontSize = '12px';
            pendingBadge.style.animation = 'pulse 1.5s infinite';
            pendingBadge.textContent = pendingRequests;
            
            const leaveCard = document.querySelector('.card-leave');
            if (leaveCard) {
                leaveCard.style.position = 'relative';
                leaveCard.appendChild(pendingBadge);
            }
        }
        
        // Make stats cards clickable
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.cursor = 'pointer';
            });
        });
        
        // Auto-refresh dashboard data every 5 minutes
        setTimeout(() => {
            location.reload();
        }, 300000); // 5 minutes
    });
</script>
</body>
</html>
