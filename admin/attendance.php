<?php
require_once '../config/db.php';
require_once '../config/auth.php';

// Only ADMIN or HR can access
if (!in_array($_SESSION['role'], ['ADMIN', 'HR'])) {
    header("Location: ../index.php");
    exit();
}

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $emp_id = $_POST['emp_id'];
    $attendance_date = $_POST['attendance_date'];
    $check_in = $_POST['check_in'];
    $check_out = $_POST['check_out'];
    $status = $_POST['status'];

    // Prevent duplicate entry
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE emp_id = :emp_id AND attendance_date = :attendance_date");
    $stmt->bindParam(':emp_id', $emp_id);
    $stmt->bindParam(':attendance_date', $attendance_date);
    $stmt->execute();

    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        $error = "Attendance for this employee on this date is already marked.";
    } else {
        $insert = $conn->prepare("INSERT INTO attendance (emp_id, attendance_date, check_in, check_out, status, marked_by) 
                                  VALUES (:emp_id, :attendance_date, :check_in, :check_out, :status, 'ADMIN')");
        $insert->bindParam(':emp_id', $emp_id);
        $insert->bindParam(':attendance_date', $attendance_date);
        $insert->bindParam(':check_in', $check_in);
        $insert->bindParam(':check_out', $check_out);
        $insert->bindParam(':status', $status);

        if ($insert->execute()) {
            $success = "Attendance marked successfully.";
        } else {
            $error = "Failed to mark attendance.";
        }
    }
}

// Fetch all employees - REMOVED status column reference
$employees_stmt = $conn->prepare("SELECT e.emp_id, u.employee_id, u.email, e.first_name, e.last_name, e.department, e.designation 
                                  FROM employees e 
                                  JOIN users u ON e.user_id = u.user_id
                                  ORDER BY e.first_name ASC");
$employees_stmt->execute();
$employees = $employees_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's attendance summary
$today = date('Y-m-d');
$summary_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_attendance,
        SUM(CASE WHEN status = 'PRESENT' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'ABSENT' THEN 1 ELSE 0 END) as absent,
        SUM(CASE WHEN status = 'HALF-DAY' THEN 1 ELSE 0 END) as half_day,
        SUM(CASE WHEN status = 'LEAVE' THEN 1 ELSE 0 END) as leave_count
    FROM attendance 
    WHERE attendance_date = :today
");
$summary_stmt->bindParam(':today', $today);
$summary_stmt->execute();
$today_summary = $summary_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mark Attendance - Dayflow HRMS</title>
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

        .attendance-date {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 8px;
            padding-left: 75px;
            position: relative;
            z-index: 1;
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

        /* ===== STATS BAR ===== */
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

        /* ===== MAIN CONTENT ===== */
        .main-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        @media (max-width: 992px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }

        /* ===== FORM CARD ===== */
        .form-card {
            background: var(--odoo-card-bg);
            border-radius: var(--odoo-radius-lg);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            border: 1px solid var(--odoo-border);
            position: relative;
            overflow: hidden;
        }

        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--odoo-gradient);
        }

        .form-header {
            margin-bottom: 30px;
            text-align: center;
        }

        .form-header h5 {
            margin: 0;
            color: var(--odoo-primary);
            font-weight: 600;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .form-header h5 i {
            color: var(--odoo-secondary);
        }

        /* ===== FORM STYLING ===== */
        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: var(--odoo-primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .form-label i {
            color: var(--odoo-secondary);
            font-size: 16px;
            width: 20px;
        }

        .form-control {
            padding: 14px 16px;
            border: 1px solid var(--odoo-border);
            border-radius: var(--odoo-radius-sm);
            font-size: 15px;
            transition: all 0.3s ease;
            background: var(--odoo-light);
            width: 100%;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--odoo-secondary);
            box-shadow: 0 0 0 3px rgba(0, 160, 157, 0.1);
            background: white;
        }

        select.form-control {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23714B67' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
            padding-right: 45px;
        }

        /* ===== FORM ROWS ===== */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        /* ===== BUTTONS ===== */
        .btn-submit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: var(--odoo-gradient-success);
            color: white;
            border: none;
            padding: 16px 40px;
            border-radius: var(--odoo-radius-sm);
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: linear-gradient(135deg, #218838 0%, #1ba87e 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(40, 167, 69, 0.2);
        }

        .btn-submit i {
            font-size: 18px;
        }

        /* ===== SIDEBAR CARD ===== */
        .sidebar-card {
            background: var(--odoo-card-bg);
            border-radius: var(--odoo-radius-lg);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            border: 1px solid var(--odoo-border);
            height: fit-content;
        }

        .sidebar-header {
            margin-bottom: 25px;
            text-align: center;
        }

        .sidebar-header h5 {
            margin: 0;
            color: var(--odoo-primary);
            font-weight: 600;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .sidebar-header h5 i {
            color: var(--odoo-secondary);
        }

        /* ===== QUICK ACTIONS ===== */
        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .quick-action-btn {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(113, 75, 103, 0.08);
            color: var(--odoo-primary);
            text-decoration: none;
            font-weight: 600;
            padding: 15px 20px;
            border-radius: var(--odoo-radius-sm);
            transition: all 0.3s ease;
            font-size: 14px;
            border: 1px solid transparent;
        }

        .quick-action-btn:hover {
            background: var(--odoo-primary);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(113, 75, 103, 0.15);
            border-color: var(--odoo-primary);
        }

        .quick-action-btn i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        /* ===== EMPLOYEE PREVIEW ===== */
        .employee-preview {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 2px solid var(--odoo-border);
            display: none;
        }

        .employee-preview.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .preview-header {
            color: var(--odoo-primary);
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .employee-info {
            background: linear-gradient(135deg, rgba(113, 75, 103, 0.05) 0%, rgba(0, 160, 157, 0.05) 100%);
            border-radius: var(--odoo-radius);
            padding: 20px;
        }

        .employee-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--odoo-primary) 0%, var(--odoo-secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 20px;
            margin: 0 auto 15px;
        }

        .employee-name {
            text-align: center;
            font-weight: 700;
            color: var(--odoo-primary);
            font-size: 18px;
            margin-bottom: 5px;
        }

        .employee-details {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-top: 15px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(113, 75, 103, 0.1);
        }

        .detail-label {
            color: #666;
            font-size: 13px;
        }

        .detail-value {
            font-weight: 600;
            color: var(--odoo-primary);
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

        .status-half-day {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .status-leave {
            background: rgba(91, 192, 222, 0.1);
            color: #5bc0de;
            border: 1px solid rgba(91, 192, 222, 0.3);
        }

        /* ===== TIME INPUTS ===== */
        .time-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .time-input-group {
            position: relative;
        }

        .time-input-group .form-label {
            margin-bottom: 5px;
        }

        .time-display {
            margin-top: 8px;
            font-size: 13px;
            color: #666;
            font-weight: 500;
            text-align: center;
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
            
            .attendance-date {
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
            
            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-row, .time-inputs {
                grid-template-columns: 1fr;
            }
            
            .btn-submit {
                padding: 14px 30px;
            }
        }

        @media (max-width: 576px) {
            .stats-bar {
                grid-template-columns: 1fr;
            }
            
            .form-card, .sidebar-card {
                padding: 20px;
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
                <i class="fas fa-clock"></i>
                Mark Attendance
            </h3>
            <p class="attendance-date">
                Today: <strong><?= date('F d, Y') ?></strong>
            </p>
        </div>
        <div class="action-buttons">
            <a href="dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Messages -->
    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--odoo-gradient-success);">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $today_summary['present'] ?? 0 ?></div>
                <div class="stat-label">Present Today</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--odoo-gradient-danger);">
                <i class="fas fa-user-times"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $today_summary['absent'] ?? 0 ?></div>
                <div class="stat-label">Absent Today</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--odoo-gradient-warning);">
                <i class="fas fa-calendar-minus"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $today_summary['half_day'] ?? 0 ?></div>
                <div class="stat-label">Half Day</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--odoo-gradient-info);">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $today_summary['leave_count'] ?? 0 ?></div>
                <div class="stat-label">On Leave</div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Attendance Form -->
        <div class="form-card">
            <div class="form-header">
                <h5><i class="fas fa-edit"></i> Mark Employee Attendance</h5>
            </div>

            <form method="POST" action="" id="attendanceForm">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i>
                        Select Employee
                    </label>
                    <select name="emp_id" class="form-control" required id="employeeSelect">
                        <option value="">-- Select Employee --</option>
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['emp_id'] ?>" 
                                    data-firstname="<?= htmlspecialchars($emp['first_name']) ?>"
                                    data-lastname="<?= htmlspecialchars($emp['last_name']) ?>"
                                    data-email="<?= htmlspecialchars($emp['email']) ?>"
                                    data-employeeid="<?= htmlspecialchars($emp['employee_id']) ?>"
                                    data-department="<?= htmlspecialchars($emp['department']) ?>"
                                    data-designation="<?= htmlspecialchars($emp['designation']) ?>">
                                <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?> 
                                (<?= htmlspecialchars($emp['employee_id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar-day"></i>
                            Date
                        </label>
                        <input type="date" name="attendance_date" class="form-control" required 
                               value="<?= date('Y-m-d') ?>" id="attendanceDate">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-info-circle"></i>
                            Status
                        </label>
                        <select name="status" class="form-control" required id="statusSelect">
                            <option value="PRESENT">Present</option>
                            <option value="ABSENT">Absent</option>
                            <option value="HALF-DAY">Half-Day</option>
                            <option value="LEAVE">Leave</option>
                        </select>
                    </div>
                </div>

                <div class="time-inputs" id="timeInputs">
                    <div class="form-group time-input-group">
                        <label class="form-label">
                            <i class="fas fa-sign-in-alt"></i>
                            Check In
                        </label>
                        <input type="time" name="check_in" class="form-control" required id="checkInTime">
                        <div class="time-display" id="checkInDisplay">--:-- --</div>
                    </div>

                    <div class="form-group time-input-group">
                        <label class="form-label">
                            <i class="fas fa-sign-out-alt"></i>
                            Check Out
                        </label>
                        <input type="time" name="check_out" class="form-control" required id="checkOutTime">
                        <div class="time-display" id="checkOutDisplay">--:-- --</div>
                    </div>
                </div>

                <button type="submit" class="btn-submit pulse">
                    <i class="fas fa-save"></i>
                    Mark Attendance
                </button>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="sidebar-card">
            <div class="sidebar-header">
                <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>

            <div class="quick-actions">
                <a href="attendance_view.php" class="quick-action-btn">
                    <i class="fas fa-eye"></i>
                    View Attendance
                </a>
                <a href="attendance_report.php" class="quick-action-btn">
                    <i class="fas fa-chart-bar"></i>
                    Generate Report
                </a>
                <a href="attendance_bulk.php" class="quick-action-btn">
                    <i class="fas fa-users"></i>
                    Bulk Attendance
                </a>
                <a href="attendance_edit.php" class="quick-action-btn">
                    <i class="fas fa-edit"></i>
                    Edit Attendance
                </a>
            </div>

            <!-- Employee Preview -->
            <div class="employee-preview" id="employeePreview">
                <div class="preview-header">
                    <i class="fas fa-user-circle"></i>
                    Employee Details
                </div>
                <div class="employee-info">
                    <div class="employee-avatar" id="employeeAvatar">--</div>
                    <div class="employee-name" id="employeeName">Select Employee</div>
                    <div class="employee-details">
                        <div class="detail-item">
                            <span class="detail-label">Employee ID:</span>
                            <span class="detail-value" id="employeeId">--</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value" id="employeeEmail">--</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Department:</span>
                            <span class="detail-value" id="employeeDept">--</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Designation:</span>
                            <span class="detail-value" id="employeeDesignation">--</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Info -->
            <div style="margin-top: 25px; padding-top: 25px; border-top: 2px solid var(--odoo-border);">
                <div class="preview-header">
                    <i class="fas fa-info-circle"></i>
                    Status Guide
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="status-badge status-present">Present</span>
                        <span style="font-size: 13px; color: #666;">Regular working day</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="status-badge status-absent">Absent</span>
                        <span style="font-size: 13px; color: #666;">Did not attend</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="status-badge status-half-day">Half-Day</span>
                        <span style="font-size: 13px; color: #666;">Worked less than 4 hours</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="status-badge status-leave">Leave</span>
                        <span style="font-size: 13px; color: #666;">Approved leave</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize default values
    document.addEventListener('DOMContentLoaded', function() {
        // Set default times
        const now = new Date();
        const defaultCheckIn = new Date();
        defaultCheckIn.setHours(9, 0, 0); // 9:00 AM
        const defaultCheckOut = new Date();
        defaultCheckOut.setHours(17, 0, 0); // 5:00 PM

        document.getElementById('checkInTime').value = formatTime(defaultCheckIn);
        document.getElementById('checkOutTime').value = formatTime(defaultCheckOut);
        
        // Update time displays
        updateTimeDisplay('checkInTime', 'checkInDisplay');
        updateTimeDisplay('checkOutTime', 'checkOutDisplay');
        
        // Toggle time inputs based on status
        toggleTimeInputs();
    });

    // Format time for input fields
    function formatTime(date) {
        const hours = date.getHours().toString().padStart(2, '0');
        const minutes = date.getMinutes().toString().padStart(2, '0');
        return `${hours}:${minutes}`;
    }

    // Update time display in readable format
    function updateTimeDisplay(inputId, displayId) {
        const input = document.getElementById(inputId);
        const display = document.getElementById(displayId);
        
        if (input.value) {
            const [hours, minutes] = input.value.split(':');
            const date = new Date();
            date.setHours(hours, minutes);
            const timeString = date.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            });
            display.textContent = timeString;
        }
    }

    // Toggle time inputs based on selected status
    function toggleTimeInputs() {
        const status = document.getElementById('statusSelect').value;
        const timeInputs = document.getElementById('timeInputs');
        
        if (status === 'ABSENT' || status === 'LEAVE') {
            timeInputs.style.opacity = '0.5';
            timeInputs.style.pointerEvents = 'none';
            document.getElementById('checkInTime').required = false;
            document.getElementById('checkOutTime').required = false;
        } else {
            timeInputs.style.opacity = '1';
            timeInputs.style.pointerEvents = 'auto';
            document.getElementById('checkInTime').required = true;
            document.getElementById('checkOutTime').required = true;
        }
    }

    // Update employee preview
    document.getElementById('employeeSelect').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const preview = document.getElementById('employeePreview');
        
        if (this.value) {
            const firstName = selectedOption.getAttribute('data-firstname');
            const lastName = selectedOption.getAttribute('data-lastname');
            const email = selectedOption.getAttribute('data-email');
            const employeeId = selectedOption.getAttribute('data-employeeid');
            const department = selectedOption.getAttribute('data-department');
            const designation = selectedOption.getAttribute('data-designation');
            
            // Update avatar
            document.getElementById('employeeAvatar').textContent = 
                (firstName?.charAt(0) || '') + (lastName?.charAt(0) || '');
            
            // Update details
            document.getElementById('employeeName').textContent = `${firstName} ${lastName}`;
            document.getElementById('employeeId').textContent = employeeId;
            document.getElementById('employeeEmail').textContent = email;
            document.getElementById('employeeDept').textContent = department;
            document.getElementById('employeeDesignation').textContent = designation;
            
            // Show preview
            preview.classList.add('active');
        } else {
            preview.classList.remove('active');
        }
    });

    // Update time displays on input
    document.getElementById('checkInTime').addEventListener('input', function() {
        updateTimeDisplay('checkInTime', 'checkInDisplay');
        validateCheckOutTime();
    });
    
    document.getElementById('checkOutTime').addEventListener('input', function() {
        updateTimeDisplay('checkOutTime', 'checkOutDisplay');
        validateCheckOutTime();
    });

    // Validate check-out is after check-in
    function validateCheckOutTime() {
        const checkIn = document.getElementById('checkInTime').value;
        const checkOut = document.getElementById('checkOutTime').value;
        const checkOutDisplay = document.getElementById('checkOutDisplay');
        
        if (checkIn && checkOut) {
            const [inHours, inMinutes] = checkIn.split(':').map(Number);
            const [outHours, outMinutes] = checkOut.split(':').map(Number);
            
            const checkInTime = new Date();
            checkInTime.setHours(inHours, inMinutes, 0);
            
            const checkOutTime = new Date();
            checkOutTime.setHours(outHours, outMinutes, 0);
            
            if (checkOutTime <= checkInTime) {
                checkOutDisplay.style.color = 'var(--odoo-danger)';
                checkOutDisplay.innerHTML += ' <i class="fas fa-exclamation-triangle" style="color: var(--odoo-danger);"></i>';
            } else {
                checkOutDisplay.style.color = '#666';
            }
        }
    }

    // Update status select change
    document.getElementById('statusSelect').addEventListener('change', toggleTimeInputs);

    // Form validation
    document.getElementById('attendanceForm').addEventListener('submit', function(e) {
        const employeeSelect = document.getElementById('employeeSelect');
        const dateInput = document.getElementById('attendanceDate');
        const statusSelect = document.getElementById('statusSelect');
        const checkIn = document.getElementById('checkInTime').value;
        const checkOut = document.getElementById('checkOutTime').value;
        
        // Basic validation
        if (!employeeSelect.value) {
            e.preventDefault();
            showError('Please select an employee.');
            employeeSelect.focus();
            return;
        }
        
        if (!dateInput.value) {
            e.preventDefault();
            showError('Please select a date.');
            dateInput.focus();
            return;
        }
        
        if (statusSelect.value !== 'ABSENT' && statusSelect.value !== 'LEAVE') {
            if (!checkIn) {
                e.preventDefault();
                showError('Please enter check-in time.');
                document.getElementById('checkInTime').focus();
                return;
            }
            
            if (!checkOut) {
                e.preventDefault();
                showError('Please enter check-out time.');
                document.getElementById('checkOutTime').focus();
                return;
            }
            
            // Validate check-out is after check-in
            const [inHours, inMinutes] = checkIn.split(':').map(Number);
            const [outHours, outMinutes] = checkOut.split(':').map(Number);
            
            const checkInTime = new Date();
            checkInTime.setHours(inHours, inMinutes, 0);
            
            const checkOutTime = new Date();
            checkOutTime.setHours(outHours, outMinutes, 0);
            
            if (checkOutTime <= checkInTime) {
                e.preventDefault();
                showError('Check-out time must be after check-in time.');
                document.getElementById('checkOutTime').focus();
                return;
            }
        }
        
        // Confirm submission
        const employeeName = employeeSelect.options[employeeSelect.selectedIndex].text.split(' (')[0];
        const status = statusSelect.options[statusSelect.selectedIndex].text;
        const date = new Date(dateInput.value).toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        let message = `Mark attendance for ${employeeName}?\n\n`;
        message += `Date: ${date}\n`;
        message += `Status: ${status}\n`;
        
        if (statusSelect.value !== 'ABSENT' && statusSelect.value !== 'LEAVE') {
            message += `Check In: ${document.getElementById('checkInDisplay').textContent}\n`;
            message += `Check Out: ${document.getElementById('checkOutDisplay').textContent}`;
        }
        
        if (!confirm(message)) {
            e.preventDefault();
        }
    });

    // Show error message
    function showError(message) {
        // Create error alert if not exists
        let errorAlert = document.querySelector('.alert-danger:not(.permanent)');
        if (!errorAlert) {
            errorAlert = document.createElement('div');
            errorAlert.className = 'alert alert-danger';
            errorAlert.style.marginTop = '20px';
            const messagesContainer = document.querySelector('.alert-success, .alert-danger')?.parentElement || document.querySelector('.page-header')?.nextElementSibling || document.querySelector('.stats-bar');
            messagesContainer.parentNode.insertBefore(errorAlert, messagesContainer);
        }
        
        errorAlert.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
        
        // Remove after 5 seconds
        setTimeout(() => {
            if (errorAlert && errorAlert.parentNode) {
                errorAlert.remove();
            }
        }, 5000);
    }

    // Add date validation to prevent future dates
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('attendanceDate').max = today;

    // Add auto-fill for current time
    const nowBtn = document.createElement('button');
    nowBtn.type = 'button';
    nowBtn.className = 'quick-action-btn';
    nowBtn.style.marginTop = '10px';
    nowBtn.innerHTML = '<i class="fas fa-clock"></i> Set Current Time';
    nowBtn.addEventListener('click', function() {
        const now = new Date();
        const currentTime = formatTime(now);
        
        document.getElementById('checkInTime').value = currentTime;
        document.getElementById('checkOutTime').value = formatTime(new Date(now.getTime() + (8 * 60 * 60 * 1000))); // +8 hours
        
        updateTimeDisplay('checkInTime', 'checkInDisplay');
        updateTimeDisplay('checkOutTime', 'checkOutDisplay');
        validateCheckOutTime();
    });
    
    document.querySelector('.time-inputs').appendChild(nowBtn);
</script>
</body>
</html>
