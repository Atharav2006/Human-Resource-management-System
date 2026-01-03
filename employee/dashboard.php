<?php
require_once '../config/db.php';
require_once '../config/auth.php';

// Only EMPLOYEE can access
if ($_SESSION['role'] != 'EMPLOYEE') {
    header("Location: ../index.php");
    exit();
}

// Get employee info
$stmt = $conn->prepare("SELECT * FROM employees WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    die("Employee profile not found. Contact admin.");
}

// Attendance summary
$attendance_stmt = $conn->prepare("
    SELECT status, COUNT(*) as count 
    FROM attendance 
    WHERE emp_id = :emp_id
    GROUP BY status
");
$attendance_stmt->bindParam(':emp_id', $employee['emp_id']);
$attendance_stmt->execute();
$attendance_summary = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);

// Leave summary
$leave_stmt = $conn->prepare("
    SELECT status, COUNT(*) as count 
    FROM leave_requests 
    WHERE emp_id = :emp_id
    GROUP BY status
");
$leave_stmt->bindParam(':emp_id', $employee['emp_id']);
$leave_stmt->execute();
$leave_summary = $leave_stmt->fetchAll(PDO::FETCH_ASSOC);

// Payroll summary (last 3 months)
$payroll_stmt = $conn->prepare("
    SELECT * FROM payroll 
    WHERE emp_id = :emp_id 
    ORDER BY generated_on DESC 
    LIMIT 3
");
$payroll_stmt->bindParam(':emp_id', $employee['emp_id']);
$payroll_stmt->execute();
$payroll_records = $payroll_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee Dashboard - Dayflow HRMS</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
        }

        /* ===== ODOO CONTAINER ===== */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
            animation: fadeIn 0.5s ease;
        }

        /* ===== ODOO HEADER ===== */
        .dashboard-header {
            background: var(--odoo-gradient);
            color: white;
            padding: 25px 30px;
            border-radius: var(--odoo-radius-lg);
            margin-bottom: 30px;
            box-shadow: 0 5px 20px rgba(113, 75, 103, 0.2);
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

        .dashboard-header h3 {
            font-weight: 700;
            margin: 0;
            font-size: 28px;
            position: relative;
            z-index: 1;
        }

        .welcome-text {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 8px;
            position: relative;
            z-index: 1;
        }

        /* ===== ODOO NAVIGATION CARDS ===== */
        .nav-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .nav-card {
            background: var(--odoo-card-bg);
            border-radius: var(--odoo-radius);
            padding: 25px;
            text-decoration: none;
            color: #333;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
            border: 1px solid var(--odoo-border);
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        .nav-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(113, 75, 103, 0.15);
            text-decoration: none;
            color: var(--odoo-primary);
            border-color: var(--odoo-secondary);
        }

        .nav-card i {
            font-size: 32px;
            color: var(--odoo-secondary);
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 160, 157, 0.1);
            border-radius: var(--odoo-radius);
        }

        .nav-card-content h5 {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--odoo-primary);
        }

        .nav-card-content p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }

        /* ===== ODOO CARDS ===== */
        .card {
            background: var(--odoo-card-bg);
            border: 1px solid var(--odoo-border);
            border-radius: var(--odoo-radius);
            margin-bottom: 25px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(113, 75, 103, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, var(--odoo-primary) 0%, var(--odoo-secondary) 100%);
            color: white;
            padding: 18px 25px;
            border-bottom: none;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 600;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h5 i {
            font-size: 20px;
        }

        .card-body {
            padding: 25px;
        }

        /* ===== ODOO TABLES ===== */
        .table {
            margin-bottom: 0;
            border: 1px solid var(--odoo-border);
        }

        .table thead th {
            background: rgba(113, 75, 103, 0.05);
            border-bottom: 2px solid var(--odoo-secondary);
            color: var(--odoo-primary);
            font-weight: 600;
            padding: 15px;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: var(--odoo-border);
        }

        .table tbody tr {
            transition: background-color 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(113, 75, 103, 0.03);
        }

        /* ===== STATUS BADGES ===== */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
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

        .status-late {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .status-pending {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            border: 1px solid rgba(108, 117, 125, 0.3);
        }

        .status-approved {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.3);
        }

        .status-rejected {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        /* ===== CURRENCY STYLING ===== */
        .currency {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--odoo-primary);
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

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .nav-cards {
                grid-template-columns: 1fr;
            }
            
            .container {
                margin: 15px auto;
                padding: 0 15px;
            }
            
            .dashboard-header {
                padding: 20px;
            }
            
            .dashboard-header h3 {
                font-size: 24px;
            }
            
            .table-responsive {
                margin: 0 -25px;
                padding: 0 25px;
            }
        }

        @media (max-width: 576px) {
            .nav-card {
                padding: 20px;
            }
            
            .nav-card i {
                width: 50px;
                height: 50px;
                font-size: 24px;
            }
            
            .card-body {
                padding: 20px;
            }
        }

        /* ===== UTILITY CLASSES ===== */
        .mb-4 {
            margin-bottom: 1.5rem !important;
        }

        .text-center {
            text-align: center !important;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <h3>Welcome back, <?= htmlspecialchars($employee['first_name']) ?>!</h3>
        <p class="welcome-text">Employee Dashboard - Dayflow HRMS</p>
    </div>

    <!-- Quick Actions Navigation -->
    <div class="nav-cards">
        <a href="../employee/profile.php" class="nav-card">
    <i class="fas fa-user"></i>
    <div class="nav-card-content">
        <h5>My Profile</h5>
        <p>View your personal details</p>
    </div>
</a>

<a href="../employee/edit_profile.php" class="nav-card">
    <i class="fas fa-user-edit"></i>
    <div class="nav-card-content">
        <h5>Edit Profile</h5>
        <p>Update your information</p>
    </div>
</a>
        <a href="../employee/apply_leave.php" class="nav-card">
            <i class="fas fa-calendar-plus"></i>
            <div class="nav-card-content">
                <h5>Apply Leave</h5>
                <p>Submit new leave requests</p>
            </div>
        </a>
        
        <a href="../employee/attendance.php" class="nav-card">
            <i class="fas fa-calendar-check"></i>
            <div class="nav-card-content">
                <h5>View Attendance</h5>
                <p>Check your attendance records</p>
            </div>
        </a>
        
        <a href="../employee/payroll.php" class="nav-card">
            <i class="fas fa-money-check-alt"></i>
            <div class="nav-card-content">
                <h5>View Payroll</h5>
                <p>Access salary details</p>
            </div>
        </a>
        
        <a href="../logout.php" class="nav-card">
            <i class="fas fa-sign-out-alt"></i>
            <div class="nav-card-content">
                <h5>Logout</h5>
                <p>Sign out from your account</p>
            </div>
        </a>
    </div>

    <!-- Attendance Summary Card -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-chart-bar"></i> Attendance Summary</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($attendance_summary): ?>
                            <?php foreach ($attendance_summary as $row): ?>
                                <tr>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= $row['count'] ?></strong> records
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center">
                                    <div class="empty-state">
                                        <i class="fas fa-chart-line"></i>
                                        <p>No attendance records found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Leave Summary Card -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-umbrella-beach"></i> Leave Requests Summary</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($leave_summary): ?>
                            <?php foreach ($leave_summary as $row): ?>
                                <tr>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= $row['count'] ?></strong> requests
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>No leave requests found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Payroll Card -->
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-file-invoice-dollar"></i> Recent Payroll (Last 3 Months)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Basic Salary</th>
                            <th>Allowances</th>
                            <th>Deductions</th>
                            <th>Net Salary</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($payroll_records): ?>
                            <?php foreach ($payroll_records as $pay): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($pay['salary_month']) ?></strong>
                                    </td>
                                    <td class="currency">
                                        $<?= number_format($pay['basic_salary'], 2) ?>
                                    </td>
                                    <td class="currency">
                                        $<?= number_format($pay['allowances'], 2) ?>
                                    </td>
                                    <td class="currency">
                                        $<?= number_format($pay['deductions'], 2) ?>
                                    </td>
                                    <td>
                                        <strong class="currency">
                                            $<?= number_format($pay['net_salary'], 2) ?>
                                        </strong>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">
                                    <div class="empty-state">
                                        <i class="fas fa-file-invoice"></i>
                                        <p>No payroll records found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>