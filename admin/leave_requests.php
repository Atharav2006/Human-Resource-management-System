<?php
require_once '../config/db.php';
require_once '../config/auth.php';

// Only ADMIN can access
if ($_SESSION['role'] != 'ADMIN') {
    header("Location: ../index.php");
    exit();
}

// Handle approve/reject actions
if (isset($_POST['action']) && isset($_POST['leave_id'])) {
    $leave_id = $_POST['leave_id'];
    $status = ($_POST['action'] == 'approve') ? 'APPROVED' : 'REJECTED';
    $admin_comment = trim($_POST['admin_comment']);

    $stmt = $conn->prepare("
        UPDATE leave_requests 
        SET status = :status, admin_comment = :admin_comment 
        WHERE leave_id = :leave_id
    ");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':admin_comment', $admin_comment);
    $stmt->bindParam(':leave_id', $leave_id);
    $stmt->execute();

    $success = "Leave request has been $status successfully.";
}

// Fetch all leave requests with employee name and leave type
$stmt = $conn->prepare("
    SELECT lr.*, e.first_name, e.last_name, lt.leave_name 
    FROM leave_requests lr
    JOIN employees e ON lr.emp_id = e.emp_id
    JOIN leave_types lt ON lr.leave_type_id = lt.leave_type_id
    ORDER BY lr.applied_at DESC
");
$stmt->execute();
$leave_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Requests - Dayflow HRMS</title>
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

        .leave-count {
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

        /* ===== LEAVE TABLE CARD ===== */
        .table-card {
            background: var(--odoo-card-bg);
            border-radius: var(--odoo-radius-lg);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid var(--odoo-border);
            overflow: hidden;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .table-header h5 {
            margin: 0;
            color: var(--odoo-primary);
            font-weight: 600;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-header h5 i {
            color: var(--odoo-secondary);
        }

        /* ===== TABLE CONTROLS ===== */
        .table-controls {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 12px 15px;
            border: 1px solid var(--odoo-border);
            border-radius: var(--odoo-radius-sm);
            background: var(--odoo-light);
            color: #333;
            font-size: 14px;
            cursor: pointer;
            min-width: 150px;
        }

        .search-box {
            position: relative;
            min-width: 250px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--odoo-secondary);
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid var(--odoo-border);
            border-radius: var(--odoo-radius-sm);
            font-size: 14px;
            transition: all 0.3s ease;
            background: var(--odoo-light);
        }

        /* ===== ODOO TABLE STYLING ===== */
        .table-container {
            overflow-x: auto;
            border-radius: var(--odoo-radius-sm);
            border: 1px solid var(--odoo-border);
        }

        .table {
            margin-bottom: 0;
            min-width: 1200px;
        }

        .table thead {
            background: linear-gradient(135deg, var(--odoo-primary) 0%, var(--odoo-secondary) 100%);
        }

        .table thead th {
            color: white;
            font-weight: 600;
            padding: 18px 15px;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
            border: none;
            white-space: nowrap;
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
            padding: 18px 15px;
            vertical-align: middle;
            border-color: var(--odoo-border);
            white-space: nowrap;
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

        /* ===== EMPLOYEE INFO ===== */
        .employee-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .employee-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--odoo-primary) 0%, var(--odoo-secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 16px;
        }

        .employee-name {
            font-weight: 600;
            color: var(--odoo-primary);
        }

        /* ===== DATE STYLING ===== */
        .date-cell {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }

        .date-cell i {
            color: var(--odoo-secondary);
        }

        /* ===== LEAVE TYPE ===== */
        .leave-type-cell {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: rgba(113, 75, 103, 0.1);
            border-radius: var(--odoo-radius-sm);
            font-weight: 600;
            color: var(--odoo-primary);
            font-size: 13px;
        }

        /* ===== REASON CELL ===== */
        .reason-cell {
            max-width: 250px;
            white-space: normal;
            word-wrap: break-word;
            color: #666;
            font-size: 14px;
            line-height: 1.4;
        }

        /* ===== COMMENT CELL ===== */
        .comment-cell {
            max-width: 200px;
            white-space: normal;
            word-wrap: break-word;
            color: #666;
            font-size: 13px;
            font-style: italic;
        }

        /* ===== ACTION FORM ===== */
        .action-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-width: 250px;
        }

        .comment-textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--odoo-border);
            border-radius: var(--odoo-radius-sm);
            font-size: 13px;
            resize: vertical;
            min-height: 80px;
            transition: all 0.3s ease;
            background: var(--odoo-light);
        }

        .comment-textarea:focus {
            outline: none;
            border-color: var(--odoo-secondary);
            box-shadow: 0 0 0 3px rgba(0, 160, 157, 0.1);
            background: white;
        }

        .form-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-approve {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--odoo-gradient-success);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: var(--odoo-radius-sm);
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-approve:hover {
            background: linear-gradient(135deg, #218838 0%, #1ba87e 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(40, 167, 69, 0.2);
        }

        .btn-reject {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: var(--odoo-gradient-danger);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: var(--odoo-radius-sm);
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-reject:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.2);
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h4 {
            color: var(--odoo-primary);
            margin-bottom: 10px;
        }

        .empty-state p {
            max-width: 400px;
            margin: 0 auto;
            line-height: 1.6;
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
            
            .leave-count {
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
            
            .table-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .table-controls {
                flex-direction: column;
            }
            
            .search-box, .filter-select {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .stats-bar {
                grid-template-columns: 1fr;
            }
            
            .table-card {
                padding: 20px;
            }
            
            .form-buttons {
                flex-direction: column;
            }
            
            .stat-card {
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
                <i class="fas fa-file-alt"></i>
                Leave Requests
            </h3>
            <p class="leave-count">
                Total Requests: <strong><?= count($leave_requests) ?></strong>
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
        <?php
        // Calculate statistics
        $pending_count = 0;
        $approved_count = 0;
        $rejected_count = 0;
        $today = date('Y-m-d');
        $upcoming_leave = 0;
        
        foreach ($leave_requests as $leave) {
            if ($leave['status'] == 'PENDING') $pending_count++;
            if ($leave['status'] == 'APPROVED') $approved_count++;
            if ($leave['status'] == 'REJECTED') $rejected_count++;
            
            if ($leave['status'] == 'APPROVED' && $leave['start_date'] >= $today) {
                $upcoming_leave++;
            }
        }
        ?>
        
        <div class="stat-card" onclick="filterByStatus('PENDING')">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f0ad4e 0%, #f5c469 100%);">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $pending_count ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
        </div>
        
        <div class="stat-card" onclick="filterByStatus('APPROVED')">
            <div class="stat-icon" style="background: var(--odoo-gradient-success);">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $approved_count ?></div>
                <div class="stat-label">Approved Leaves</div>
            </div>
        </div>
        
        <div class="stat-card" onclick="filterByStatus('REJECTED')">
            <div class="stat-icon" style="background: var(--odoo-gradient-danger);">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $rejected_count ?></div>
                <div class="stat-label">Rejected Leaves</div>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--odoo-gradient);">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $upcoming_leave ?></div>
                <div class="stat-label">Upcoming Leaves</div>
            </div>
        </div>
    </div>

    <!-- Leave Table Card -->
    <div class="table-card">
        <div class="table-header">
            <h5><i class="fas fa-list"></i> All Leave Requests</h5>
            <div class="table-controls">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search employees or reason...">
                </div>
                <select class="filter-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="PENDING">Pending</option>
                    <option value="APPROVED">Approved</option>
                    <option value="REJECTED">Rejected</option>
                </select>
                <select class="filter-select" id="leaveTypeFilter">
                    <option value="">All Leave Types</option>
                    <?php
                    $leave_types = array_unique(array_column($leave_requests, 'leave_name'));
                    sort($leave_types);
                    foreach ($leave_types as $type):
                    ?>
                        <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="table-container">
            <table class="table" id="leaveTable">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> ID</th>
                        <th><i class="fas fa-user"></i> Employee</th>
                        <th><i class="fas fa-tag"></i> Leave Type</th>
                        <th><i class="fas fa-calendar-day"></i> Start Date</th>
                        <th><i class="fas fa-calendar-week"></i> End Date</th>
                        <th><i class="fas fa-comment"></i> Reason</th>
                        <th><i class="fas fa-info-circle"></i> Status</th>
                        <th><i class="fas fa-comment-dots"></i> Admin Comment</th>
                        <th><i class="fas fa-cog"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($leave_requests): ?>
                        <?php foreach ($leave_requests as $leave): ?>
                            <tr data-status="<?= $leave['status'] ?>" data-leave-type="<?= htmlspecialchars($leave['leave_name']) ?>">
                                <td><?= $leave['leave_id'] ?></td>
                                <td>
                                    <div class="employee-cell">
                                        <div class="employee-avatar">
                                            <?= substr($leave['first_name'], 0, 1) . substr($leave['last_name'], 0, 1) ?>
                                        </div>
                                        <div class="employee-name">
                                            <?= htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']) ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="leave-type-cell">
                                        <i class="fas fa-tag"></i>
                                        <?= htmlspecialchars($leave['leave_name']) ?>
                                    </span>
                                </td>
                                <td class="date-cell">
                                    <i class="fas fa-calendar-day"></i>
                                    <?= date('M d, Y', strtotime($leave['start_date'])) ?>
                                </td>
                                <td class="date-cell">
                                    <i class="fas fa-calendar-week"></i>
                                    <?= date('M d, Y', strtotime($leave['end_date'])) ?>
                                </td>
                                <td class="reason-cell"><?= htmlspecialchars($leave['reason']) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($leave['status']) ?>">
                                        <i class="fas fa-<?= 
                                            $leave['status'] == 'PENDING' ? 'clock' : 
                                            ($leave['status'] == 'APPROVED' ? 'check' : 'times') 
                                        ?>"></i>
                                        <?= $leave['status'] ?>
                                    </span>
                                </td>
                                <td class="comment-cell"><?= htmlspecialchars($leave['admin_comment']) ?></td>
                                <td>
                                    <?php if($leave['status'] == 'PENDING'): ?>
                                    <form method="POST" class="action-form">
                                        <input type="hidden" name="leave_id" value="<?= $leave['leave_id'] ?>">
                                        <textarea name="admin_comment" 
                                                  class="comment-textarea" 
                                                  placeholder="Add a comment (optional)"
                                                  maxlength="500"></textarea>
                                        <div class="form-buttons">
                                            <button type="submit" 
                                                    name="action" 
                                                    value="approve" 
                                                    class="btn-approve"
                                                    onclick="return confirmApprove()">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button type="submit" 
                                                    name="action" 
                                                    value="reject" 
                                                    class="btn-reject"
                                                    onclick="return confirmReject()">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </div>
                                    </form>
                                    <?php else: ?>
                                        <span style="color: #666; font-size: 13px; font-style: italic;">
                                            <i class="fas fa-check-circle"></i> Processed
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No Leave Requests</h4>
                                    <p>There are no leave requests to display at the moment.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Confirmation functions
    function confirmApprove() {
        const comment = event.target.closest('form').querySelector('textarea[name="admin_comment"]').value;
        const message = comment ? 
            "Approve this leave request with your comment?" : 
            "Approve this leave request without a comment?";
        return confirm(message);
    }

    function confirmReject() {
        const comment = event.target.closest('form').querySelector('textarea[name="admin_comment"]').value;
        const message = comment ? 
            "Reject this leave request with your comment?" : 
            "Reject this leave request without a comment?\n\nConsider adding a reason for rejection.";
        return confirm(message);
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#leaveTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Status filter
    document.getElementById('statusFilter').addEventListener('change', function() {
        filterTable();
    });

    // Leave type filter
    document.getElementById('leaveTypeFilter').addEventListener('change', function() {
        filterTable();
    });

    function filterTable() {
        const statusFilter = document.getElementById('statusFilter').value;
        const typeFilter = document.getElementById('leaveTypeFilter').value;
        const rows = document.querySelectorAll('#leaveTable tbody tr');
        
        rows.forEach(row => {
            const status = row.getAttribute('data-status');
            const leaveType = row.getAttribute('data-leave-type');
            
            const statusMatch = !statusFilter || status === statusFilter;
            const typeMatch = !typeFilter || leaveType === typeFilter;
            
            row.style.display = (statusMatch && typeMatch) ? '' : 'none';
        });
    }

    // Click on stat cards to filter
    function filterByStatus(status) {
        document.getElementById('statusFilter').value = status;
        filterTable();
        
        // Highlight the filtered status
        const statusFilter = document.getElementById('statusFilter');
        statusFilter.style.borderColor = status === 'PENDING' ? '#f0ad4e' : 
                                        status === 'APPROVED' ? '#28a745' : '#dc3545';
        statusFilter.style.boxShadow = `0 0 0 3px ${status === 'PENDING' ? 'rgba(240, 173, 78, 0.2)' : 
                                            status === 'APPROVED' ? 'rgba(40, 167, 69, 0.2)' : 'rgba(220, 53, 69, 0.2)'}`;
        
        // Reset after 2 seconds
        setTimeout(() => {
            statusFilter.style.borderColor = '';
            statusFilter.style.boxShadow = '';
        }, 2000);
    }

    // Add character counter to comment textareas
    document.addEventListener('DOMContentLoaded', function() {
        const textareas = document.querySelectorAll('.comment-textarea');
        textareas.forEach(textarea => {
            const counter = document.createElement('div');
            counter.className = 'char-counter';
            counter.style.fontSize = '11px';
            counter.style.color = '#666';
            counter.style.textAlign = 'right';
            counter.style.marginTop = '5px';
            textarea.parentNode.appendChild(counter);
            
            function updateCounter() {
                const length = textarea.value.length;
                counter.textContent = `${length}/500 characters`;
                if (length > 450) {
                    counter.style.color = '#f0ad4e';
                } else if (length > 490) {
                    counter.style.color = '#dc3545';
                } else {
                    counter.style.color = '#666';
                }
            }
            
            textarea.addEventListener('input', updateCounter);
            updateCounter();
        });
        
        // Add sort functionality
        const tableHeaders = document.querySelectorAll('#leaveTable thead th');
        tableHeaders.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                sortTable(index);
            });
        });
    });

    function sortTable(columnIndex) {
        const table = document.getElementById('leaveTable');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr:not([style*="display: none"])'));
        
        // Determine sort order
        const isAscending = table.getAttribute('data-sort-column') == columnIndex && 
                           table.getAttribute('data-sort-order') != 'desc';
        const newSortOrder = isAscending ? 'desc' : 'asc';
        
        // Sort rows
        rows.sort((a, b) => {
            const aCell = a.cells[columnIndex];
            const bCell = b.cells[columnIndex];
            
            let aValue = aCell.textContent.trim();
            let bValue = bCell.textContent.trim();
            
            // Handle dates
            if (columnIndex === 3 || columnIndex === 4) {
                aValue = new Date(aCell.querySelector('.fa-calendar')?.parentNode.textContent.trim() || aValue);
                bValue = new Date(bCell.querySelector('.fa-calendar')?.parentNode.textContent.trim() || bValue);
            }
            // Handle IDs
            else if (columnIndex === 0) {
                aValue = parseInt(aValue);
                bValue = parseInt(bValue);
            }
            // Handle status
            else if (columnIndex === 6) {
                aValue = aCell.querySelector('.status-badge').textContent.trim();
                bValue = bCell.querySelector('.status-badge').textContent.trim();
            }
            
            // Compare values
            if (aValue < bValue) return newSortOrder === 'asc' ? -1 : 1;
            if (aValue > bValue) return newSortOrder === 'asc' ? 1 : -1;
            return 0;
        });
        
        // Reorder rows
        rows.forEach(row => tbody.appendChild(row));
        
        // Update sort indicators
        table.setAttribute('data-sort-column', columnIndex);
        table.setAttribute('data-sort-order', newSortOrder);
        
        // Remove existing sort indicators
        table.querySelectorAll('th i.fa-sort').forEach(icon => icon.remove());
        
        // Add new sort indicator
        const targetHeader = tableHeaders[columnIndex];
        const sortIcon = document.createElement('i');
        sortIcon.className = 'fas fa-sort-' + (newSortOrder === 'asc' ? 'up' : 'down');
        sortIcon.style.marginLeft = '5px';
        targetHeader.appendChild(sortIcon);
    }
</script>
</body>
</html>