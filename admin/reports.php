<?php
session_start();

/* ===== FIXED PATHS ===== */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

/* ===== ACCESS CONTROL ===== */
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['ADMIN', 'HR'])) {
    header("Location: ../index.php");
    exit();
}

/* ===== TOTAL EMPLOYEES ===== */
$total_emp_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM employees");
$total_emp_stmt->execute();
$total_emp = $total_emp_stmt->fetch(PDO::FETCH_ASSOC)['total'];

/* ===== ATTENDANCE SUMMARY ===== */
$attendance_stmt = $conn->prepare("
    SELECT status, COUNT(*) AS count
    FROM attendance
    GROUP BY status
");
$attendance_stmt->execute();
$attendance_summary = $attendance_stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===== LEAVE SUMMARY ===== */
$leave_stmt = $conn->prepare("
    SELECT status, COUNT(*) AS count
    FROM leave_requests
    GROUP BY status
");
$leave_stmt->execute();
$leave_summary = $leave_stmt->fetchAll(PDO::FETCH_ASSOC);

/* ===== PAYROLL SUMMARY ===== */
$payroll_stmt = $conn->prepare("
    SELECT 
        COALESCE(SUM(net_salary), 0) AS total_payroll,
        COUNT(*) AS total_records
    FROM payroll
");
$payroll_stmt->execute();
$payroll_summary = $payroll_stmt->fetch(PDO::FETCH_ASSOC);

// Calculate totals for charts
$present_count = 0;
$absent_count = 0;
$late_count = 0;
foreach ($attendance_summary as $att) {
    if ($att['status'] == 'PRESENT') $present_count = $att['count'];
    if ($att['status'] == 'ABSENT') $absent_count = $att['count'];
    if ($att['status'] == 'LATE') $late_count = $att['count'];
}

$pending_leaves = 0;
$approved_leaves = 0;
$rejected_leaves = 0;
foreach ($leave_summary as $leave) {
    if ($leave['status'] == 'PENDING') $pending_leaves = $leave['count'];
    if ($leave['status'] == 'APPROVED') $approved_leaves = $leave['count'];
    if ($leave['status'] == 'REJECTED') $rejected_leaves = $leave['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports | Dayflow HRMS</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        }

        /* ===== ODOO CONTAINER ===== */
        .container {
            max-width: 1400px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
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

        .header-content {
            position: relative;
            z-index: 1;
        }

        .page-header h3 {
            font-weight: 700;
            margin: 0;
            font-size: 28px;
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

        .header-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 8px;
            position: relative;
            z-index: 1;
            padding-left: 75px;
        }

        /* ===== ACTION BUTTONS ===== */
        .action-buttons {
            display: flex;
            gap: 15px;
            position: relative;
            z-index: 1;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 12px 25px;
            border-radius: var(--odoo-radius-sm);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .btn-back:hover {
            background: white;
            color: var(--odoo-primary);
            text-decoration: none;
            transform: translateY(-2px);
        }

        /* ===== STATS OVERVIEW ===== */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
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
            width: 70px;
            height: 70px;
            border-radius: var(--odoo-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }

        .stat-content {
            flex: 1;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 700;
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

        .stat-change {
            font-size: 12px;
            color: #28a745;
            font-weight: 600;
        }

        /* ===== REPORT CARDS ===== */
        .report-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .report-card {
            background: var(--odoo-card-bg);
            border-radius: var(--odoo-radius-lg);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 25px;
            border: 1px solid var(--odoo-border);
            overflow: hidden;
        }

        .report-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--odoo-border);
        }

        .report-title {
            margin: 0;
            color: var(--odoo-primary);
            font-weight: 600;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .report-title i {
            color: var(--odoo-secondary);
        }

        .report-actions {
            display: flex;
            gap: 10px;
        }

        .btn-export {
            background: var(--odoo-gradient);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: var(--odoo-radius-sm);
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        .btn-export:hover {
            background: var(--odoo-gradient-light);
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }

        /* ===== CHART CONTAINER ===== */
        .chart-container {
            position: relative;
            height: 200px;
            margin-top: 15px;
        }

        /* ===== TABLE STYLING ===== */
        .table-container {
            overflow-x: auto;
            border-radius: var(--odoo-radius-sm);
            border: 1px solid var(--odoo-border);
        }

        .table {
            margin-bottom: 0;
            width: 100%;
        }

        .table thead {
            background: linear-gradient(135deg, var(--odoo-primary) 0%, var(--odoo-secondary) 100%);
        }

        .table thead th {
            color: white;
            font-weight: 600;
            padding: 15px;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
            border: none;
        }

        .table thead th i {
            margin-right: 8px;
            font-size: 14px;
        }

        .table tbody tr {
            transition: background-color 0.2s ease;
        }

        .table tbody tr:hover {
            background-color: rgba(113, 75, 103, 0.03);
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: var(--odoo-border);
        }

        /* ===== STATUS BADGES ===== */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
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

        /* ===== METRIC DISPLAY ===== */
        .metric-display {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(113, 75, 103, 0.05);
            border-radius: var(--odoo-radius-sm);
            margin-bottom: 15px;
        }

        .metric-label {
            font-weight: 600;
            color: var(--odoo-primary);
        }

        .metric-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--odoo-secondary);
        }

        .metric-subtext {
            font-size: 12px;
            color: #666;
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

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
                padding: 0 15px;
            }
            
            .page-header {
                padding: 20px;
                flex-direction: column;
                text-align: center;
            }
            
            .page-header h3 {
                font-size: 24px;
                justify-content: center;
            }
            
            .page-header h3 i {
                width: 50px;
                height: 50px;
                font-size: 26px;
            }
            
            .header-subtitle {
                padding-left: 0;
                text-align: center;
            }
            
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-back {
                width: 100%;
                justify-content: center;
            }
            
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .report-cards {
                grid-template-columns: 1fr;
            }
            
            .report-card {
                padding: 20px;
            }
        }

        @media (max-width: 576px) {
            .stats-overview {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                padding: 20px;
            }
            
            .report-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .report-actions {
                align-self: flex-end;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h3>
                <i class="fas fa-chart-line"></i>
                HR Analytics Dashboard
            </h3>
            <p class="header-subtitle">
                Comprehensive reports and analytics for <?= date('F Y') ?>
            </p>
        </div>
        <div class="action-buttons">
            <a href="dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Stats Overview -->
    <div class="stats-overview">
        <div class="stat-card" onclick="window.location='employees.php'">
            <div class="stat-icon" style="background: var(--odoo-gradient);">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $total_emp ?></div>
                <div class="stat-label">Total Employees</div>
                <div class="stat-change"><i class="fas fa-arrow-up"></i> Active workforce</div>
            </div>
        </div>
        
        <div class="stat-card" onclick="document.getElementById('attendanceChart').scrollIntoView()">
            <div class="stat-icon" style="background: var(--odoo-gradient-success);">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $present_count ?></div>
                <div class="stat-label">Present Today</div>
                <div class="stat-change">Attendance rate: <?= $total_emp > 0 ? round(($present_count/$total_emp)*100) : 0 ?>%</div>
            </div>
        </div>
        
        <div class="stat-card" onclick="document.getElementById('leaveChart').scrollIntoView()">
            <div class="stat-icon" style="background: var(--odoo-gradient-danger);">
                <i class="fas fa-calendar-times"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $pending_leaves ?></div>
                <div class="stat-label">Pending Leaves</div>
                <div class="stat-change">Total leaves: <?= $pending_leaves + $approved_leaves + $rejected_leaves ?></div>
            </div>
        </div>
        
        <div class="stat-card" onclick="document.getElementById('payrollCard').scrollIntoView()">
            <div class="stat-icon" style="background: linear-gradient(135deg, #17a2b8 0%, #5bc0de 100%);">
                <i class="fas fa-file-invoice-dollar"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value">₹<?= number_format($payroll_summary['total_payroll']/100000, 1) ?>L</div>
                <div class="stat-label">Total Payroll</div>
                <div class="stat-change"><?= $payroll_summary['total_records'] ?> records</div>
            </div>
        </div>
    </div>

    <!-- Report Cards -->
    <div class="report-cards">
        <!-- Attendance Report -->
        <div class="report-card">
            <div class="report-header">
                <h5 class="report-title"><i class="fas fa-calendar-alt"></i> Attendance Summary</h5>
                <div class="report-actions">
                    <button class="btn-export" onclick="exportAttendance()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-tag"></i> Status</th>
                            <th><i class="fas fa-hashtag"></i> Count</th>
                            <th><i class="fas fa-percentage"></i> Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($attendance_summary)): ?>
                            <?php 
                            $total_attendance = array_sum(array_column($attendance_summary, 'count'));
                            foreach ($attendance_summary as $row): 
                                $percentage = $total_attendance > 0 ? round(($row['count'] / $total_attendance) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                            <i class="fas fa-<?= 
                                                $row['status'] == 'PRESENT' ? 'check-circle' : 
                                                ($row['status'] == 'ABSENT' ? 'times-circle' : 'clock') 
                                            ?>"></i>
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $row['count'] ?></td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar" 
                                                 style="width: <?= $percentage ?>%;
                                                        background: <?= 
                                                            $row['status'] == 'PRESENT' ? 'var(--odoo-success)' : 
                                                            ($row['status'] == 'ABSENT' ? 'var(--odoo-danger)' : 'var(--odoo-warning)') 
                                                        ?>;">
                                            </div>
                                        </div>
                                        <small><?= $percentage ?>%</small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">No attendance data available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="chart-container">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>

        <!-- Leave Report -->
        <div class="report-card">
            <div class="report-header">
                <h5 class="report-title"><i class="fas fa-file-medical"></i> Leave Summary</h5>
                <div class="report-actions">
                    <button class="btn-export" onclick="exportLeaves()">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-tag"></i> Status</th>
                            <th><i class="fas fa-hashtag"></i> Count</th>
                            <th><i class="fas fa-percentage"></i> Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($leave_summary)): ?>
                            <?php 
                            $total_leaves = array_sum(array_column($leave_summary, 'count'));
                            foreach ($leave_summary as $row): 
                                $percentage = $total_leaves > 0 ? round(($row['count'] / $total_leaves) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td>
                                        <span class="status-badge status-<?= strtolower($row['status']) ?>">
                                            <i class="fas fa-<?= 
                                                $row['status'] == 'APPROVED' ? 'check' : 
                                                ($row['status'] == 'REJECTED' ? 'times' : 'clock') 
                                            ?>"></i>
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= $row['count'] ?></td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar" 
                                                 style="width: <?= $percentage ?>%;
                                                        background: <?= 
                                                            $row['status'] == 'APPROVED' ? 'var(--odoo-success)' : 
                                                            ($row['status'] == 'REJECTED' ? 'var(--odoo-danger)' : 'var(--odoo-warning)') 
                                                        ?>;">
                                            </div>
                                        </div>
                                        <small><?= $percentage ?>%</small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted">No leave records available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="chart-container">
                <canvas id="leaveChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Payroll Summary -->
    <div class="report-card" id="payrollCard">
        <div class="report-header">
            <h5 class="report-title"><i class="fas fa-money-bill-wave"></i> Payroll Summary</h5>
            <div class="report-actions">
                <button class="btn-export" onclick="exportPayroll()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
        
        <div class="metric-display">
            <div>
                <div class="metric-label">Total Payroll Paid</div>
                <div class="metric-value">₹<?= number_format($payroll_summary['total_payroll'], 2) ?></div>
                <div class="metric-subtext">All-time payroll expenditure</div>
            </div>
            <div>
                <div class="metric-label">Total Records</div>
                <div class="metric-value"><?= $payroll_summary['total_records'] ?></div>
                <div class="metric-subtext">Payroll entries in database</div>
            </div>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th><i class="fas fa-info-circle"></i> Metric</th>
                        <th><i class="fas fa-value"></i> Value</th>
                        <th><i class="fas fa-chart-bar"></i> Insights</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Average Salary per Employee</strong></td>
                        <td>₹<?= $total_emp > 0 ? number_format($payroll_summary['total_payroll'] / $total_emp, 2) : '0.00' ?></td>
                        <td><small>Based on total payroll divided by employee count</small></td>
                    </tr>
                    <tr>
                        <td><strong>Average Salary per Record</strong></td>
                        <td>₹<?= $payroll_summary['total_records'] > 0 ? number_format($payroll_summary['total_payroll'] / $payroll_summary['total_records'], 2) : '0.00' ?></td>
                        <td><small>Average net salary per payroll record</small></td>
                    </tr>
                    <tr>
                        <td><strong>Payroll Records per Employee</strong></td>
                        <td><?= $total_emp > 0 ? number_format($payroll_summary['total_records'] / $total_emp, 1) : '0.0' ?></td>
                        <td><small>Average payroll cycles per employee</small></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Initialize Attendance Chart
    const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
    const attendanceChart = new Chart(attendanceCtx, {
        type: 'doughnut',
        data: {
            labels: ['Present', 'Absent', 'Late'],
            datasets: [{
                data: [<?= $present_count ?>, <?= $absent_count ?>, <?= $late_count ?>],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(220, 53, 69, 0.8)',
                    'rgba(255, 193, 7, 0.8)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)',
                    'rgba(255, 193, 7, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const value = context.raw;
                            const percentage = Math.round((value / total) * 100);
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Initialize Leave Chart
    const leaveCtx = document.getElementById('leaveChart').getContext('2d');
    const leaveChart = new Chart(leaveCtx, {
        type: 'pie',
        data: {
            labels: ['Pending', 'Approved', 'Rejected'],
            datasets: [{
                data: [<?= $pending_leaves ?>, <?= $approved_leaves ?>, <?= $rejected_leaves ?>],
                backgroundColor: [
                    'rgba(255, 193, 7, 0.8)',
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(220, 53, 69, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 193, 7, 1)',
                    'rgba(40, 167, 69, 1)',
                    'rgba(220, 53, 69, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const value = context.raw;
                            const percentage = Math.round((value / total) * 100);
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Export functions
    function exportAttendance() {
        alert('Attendance report exported! (This would trigger a CSV/PDF download in a real application)');
    }

    function exportLeaves() {
        alert('Leave report exported! (This would trigger a CSV/PDF download in a real application)');
    }

    function exportPayroll() {
        alert('Payroll report exported! (This would trigger a CSV/PDF download in a real application)');
    }

    // Auto-refresh charts every 30 seconds
    setInterval(() => {
        attendanceChart.update();
        leaveChart.update();
    }, 30000);

    // Add animation to metric values
    document.addEventListener('DOMContentLoaded', function() {
        const metricValues = document.querySelectorAll('.metric-value');
        metricValues.forEach(value => {
            const originalValue = value.textContent;
            value.textContent = '0';
            
            setTimeout(() => {
                let current = 0;
                const target = parseFloat(originalValue.replace(/[^0-9.]/g, ''));
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    
                    if (originalValue.includes('₹')) {
                        value.textContent = '₹' + Math.round(current).toLocaleString();
                    } else {
                        value.textContent = Math.round(current).toLocaleString();
                    }
                }, 20);
            }, 500);
        });
    });
</script>
</body>
</html>