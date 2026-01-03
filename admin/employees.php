<?php
require_once '../config/db.php';
require_once '../config/auth.php';

// Only ADMIN can access
if ($_SESSION['role'] != 'ADMIN') {
    header("Location: ../index.php");
    exit();
}

// Handle optional deletion via GET
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM employees WHERE emp_id = :emp_id");
    $stmt->bindParam(':emp_id', $delete_id);
    $stmt->execute();
    header("Location: employee.php");
    exit();
}

// Fetch all employees
$stmt = $conn->prepare("SELECT e.*, u.email, u.employee_id 
                        FROM employees e 
                        JOIN users u ON e.user_id = u.user_id
                        ORDER BY e.emp_id DESC");
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Employees - Admin Dashboard</title>
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
            max-width: 1300px;
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

        .employee-count {
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

        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--odoo-gradient-success);
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 14px 28px;
            border-radius: var(--odoo-radius-sm);
            transition: all 0.3s ease;
            border: none;
        }

        .btn-add:hover {
            background: linear-gradient(135deg, #218838 0%, #1ba87e 100%);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.25);
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

        /* ===== EMPLOYEE TABLE CARD ===== */
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

        /* ===== SEARCH AND FILTER ===== */
        .table-controls {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
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

        .search-box input:focus {
            outline: none;
            border-color: var(--odoo-secondary);
            box-shadow: 0 0 0 3px rgba(0, 160, 157, 0.1);
            background: white;
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

        /* ===== ODOO TABLE STYLING ===== */
        .table-container {
            overflow-x: auto;
            border-radius: var(--odoo-radius-sm);
            border: 1px solid var(--odoo-border);
        }

        .table {
            margin-bottom: 0;
            min-width: 1000px;
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

        /* ===== PROFILE PICTURE CELL ===== */
        .profile-cell {
            text-align: center;
        }

        .profile-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--odoo-border);
            transition: all 0.3s ease;
        }

        .profile-img:hover {
            transform: scale(1.1);
            border-color: var(--odoo-secondary);
        }

        .default-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--odoo-primary) 0%, var(--odoo-secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 18px;
            border: 2px solid var(--odoo-border);
            margin: 0 auto;
        }

        /* ===== EMPLOYEE INFO CELLS ===== */
        .employee-id {
            font-family: 'Courier New', monospace;
            font-weight: 600;
            color: var(--odoo-primary);
        }

        .email-cell {
            color: var(--odoo-secondary);
            font-size: 14px;
        }

        .name-cell {
            font-weight: 600;
            color: var(--odoo-primary);
        }

        .department-cell {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            background: rgba(113, 75, 103, 0.1);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: var(--odoo-primary);
        }

        .designation-cell {
            color: #666;
            font-size: 14px;
        }

        .date-cell {
            color: #666;
            font-size: 14px;
        }

        /* ===== ACTION BUTTONS CELL ===== */
        .action-buttons-cell {
            display: flex;
            gap: 8px;
            flex-wrap: nowrap;
        }

        .btn-action {
            padding: 8px 15px;
            border-radius: var(--odoo-radius-sm);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: none;
            cursor: pointer;
            min-width: 80px;
            justify-content: center;
        }

        .btn-edit {
            background: rgba(0, 160, 157, 0.1);
            color: var(--odoo-secondary);
            border: 1px solid rgba(0, 160, 157, 0.3);
        }

        .btn-edit:hover {
            background: var(--odoo-secondary);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 160, 157, 0.2);
        }

        .btn-delete {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.3);
        }

        .btn-delete:hover {
            background: #dc3545;
            color: white;
            text-decoration: none;
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
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 1px solid var(--odoo-border);
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: var(--odoo-radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .stat-content {
            flex: 1;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--odoo-primary);
            line-height: 1;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            
            .employee-count {
                padding-left: 0;
                text-align: center;
            }
            
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-add, .btn-back {
                width: 100%;
                justify-content: center;
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
            
            .stats-bar {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 576px) {
            .stats-bar {
                grid-template-columns: 1fr;
            }
            
            .table-card {
                padding: 20px;
            }
            
            .action-buttons-cell {
                flex-direction: column;
            }
            
            .btn-action {
                width: 100%;
            }
        }

        /* ===== UTILITY CLASSES ===== */
        .mb-4 {
            margin-bottom: 1.5rem !important;
        }

        .mt-3 {
            margin-top: 1rem !important;
        }

        .text-center {
            text-align: center !important;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h3>
                <i class="fas fa-users-cog"></i>
                Manage Employees
            </h3>
            <p class="employee-count">
                Total Employees: <strong><?= count($employees) ?></strong>
            </p>
        </div>
        <div class="action-buttons">
            <a href="../admin/add_employee.php" class="btn-add">
                <i class="fas fa-user-plus"></i>
                Add New Employee
            </a>
            <a href="dashboard.php" class="btn-back">
                <i class="fas fa-arrow-left"></i>
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="stats-bar">
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--odoo-gradient);">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= count($employees) ?></div>
                <div class="stat-label">Total Employees</div>
            </div>
        </div>
        
        <?php
        // Count departments
        $departments = array_column($employees, 'department');
        $department_count = array_count_values($departments);
        $unique_departments = count($department_count);
        ?>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--odoo-secondary);">
                <i class="fas fa-building"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $unique_departments ?></div>
                <div class="stat-label">Departments</div>
            </div>
        </div>
        
        <?php
        // Count recent hires (last 30 days)
        $recent_hires = 0;
        $thirty_days_ago = date('Y-m-d', strtotime('-30 days'));
        foreach ($employees as $emp) {
            if ($emp['joining_date'] >= $thirty_days_ago) {
                $recent_hires++;
            }
        }
        ?>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--odoo-success);">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $recent_hires ?></div>
                <div class="stat-label">Recent Hires</div>
            </div>
        </div>
        
        <?php
        // Count employees with profile pictures
        $with_photos = 0;
        foreach ($employees as $emp) {
            if (!empty($emp['profile_picture'])) {
                $with_photos++;
            }
        }
        $photo_percentage = count($employees) > 0 ? round(($with_photos / count($employees)) * 100) : 0;
        ?>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--odoo-info);">
                <i class="fas fa-camera"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $photo_percentage ?>%</div>
                <div class="stat-label">Profile Completion</div>
            </div>
        </div>
    </div>

    <!-- Employee Table Card -->
    <div class="table-card">
        <div class="table-header">
            <h5><i class="fas fa-list"></i> Employee Directory</h5>
            <div class="table-controls">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Search employees...">
                </div>
                <select class="filter-select" id="departmentFilter">
                    <option value="">All Departments</option>
                    <?php
                    $departments = array_unique(array_column($employees, 'department'));
                    sort($departments);
                    foreach ($departments as $dept):
                        if (!empty($dept)):
                    ?>
                        <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                    <?php endif; endforeach; ?>
                </select>
            </div>
        </div>

        <div class="table-container">
            <table class="table" id="employeeTable">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> #</th>
                        <th><i class="fas fa-image"></i> Profile</th>
                        <th><i class="fas fa-id-card"></i> Employee ID</th>
                        <th><i class="fas fa-envelope"></i> Email</th>
                        <th><i class="fas fa-user"></i> Name</th>
                        <th><i class="fas fa-phone"></i> Phone</th>
                        <th><i class="fas fa-building"></i> Department</th>
                        <th><i class="fas fa-user-tie"></i> Designation</th>
                        <th><i class="fas fa-calendar-alt"></i> Joining Date</th>
                        <th><i class="fas fa-cog"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($employees): ?>
                        <?php foreach ($employees as $index => $emp): ?>
                            <tr data-department="<?= htmlspecialchars($emp['department']) ?>">
                                <td><?= $index + 1 ?></td>
                                <td class="profile-cell">
                                    <?php if ($emp['profile_picture']): ?>
                                        <img src="../uploads/<?= htmlspecialchars($emp['profile_picture']) ?>" 
                                             class="profile-img" 
                                             alt="Profile"
                                             onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"50\" height=\"50\" viewBox=\"0 0 50 50\"><rect width=\"50\" height=\"50\" fill=\"%23714B67\"/><text x=\"50%\" y=\"50%\" font-family=\"Arial\" font-size=\"20\" fill=\"white\" text-anchor=\"middle\" dy=\".3em\"><?= substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1) ?></text></svg>'">
                                    <?php else: ?>
                                        <div class="default-avatar">
                                            <?= substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="employee-id"><?= htmlspecialchars($emp['employee_id']) ?></td>
                                <td class="email-cell"><?= htmlspecialchars($emp['email']) ?></td>
                                <td class="name-cell"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?></td>
                                <td class="phone-cell"><?= htmlspecialchars($emp['phone']) ?></td>
                                <td>
                                    <span class="department-cell">
                                        <i class="fas fa-building"></i>
                                        <?= htmlspecialchars($emp['department']) ?>
                                    </span>
                                </td>
                                <td class="designation-cell"><?= htmlspecialchars($emp['designation']) ?></td>
                                <td class="date-cell">
                                    <i class="fas fa-calendar"></i>
                                    <?= date('M d, Y', strtotime($emp['joining_date'])) ?>
                                </td>
                                <td class="action-buttons-cell">
                                    <a href="edit_employee.php?emp_id=<?= $emp['emp_id'] ?>" class="btn-action btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="employee.php?delete_id=<?= $emp['emp_id'] ?>" 
                                       class="btn-action btn-delete" 
                                       onclick="return confirmDelete('<?= htmlspecialchars(addslashes($emp['first_name'] . ' ' . $emp['last_name'])) ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10">
                                <div class="empty-state">
                                    <i class="fas fa-users-slash"></i>
                                    <h4>No Employees Found</h4>
                                    <p>You haven't added any employees yet. Click "Add New Employee" to get started.</p>
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
    // Enhanced delete confirmation
    function confirmDelete(employeeName) {
        return confirm(`Are you sure you want to delete "${employeeName}"?\n\nThis action cannot be undone and will permanently remove all employee data.`);
    }

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#employeeTable tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchTerm) ? '' : 'none';
        });
    });

    // Department filter
    document.getElementById('departmentFilter').addEventListener('change', function() {
        const selectedDept = this.value;
        const rows = document.querySelectorAll('#employeeTable tbody tr');
        
        rows.forEach(row => {
            const dept = row.getAttribute('data-department');
            if (!selectedDept || dept === selectedDept) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Add sort functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tableHeaders = document.querySelectorAll('#employeeTable thead th');
        tableHeaders.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                sortTable(index);
            });
        });
        
        // Add click event to profile images to view larger
        const profileImages = document.querySelectorAll('.profile-img, .default-avatar');
        profileImages.forEach(img => {
            img.style.cursor = 'pointer';
            img.addEventListener('click', function(e) {
                e.stopPropagation();
                const src = this.src || this.style.background;
                if (src) {
                    const modal = document.createElement('div');
                    modal.style.cssText = `
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0,0,0,0.8);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 1000;
                        cursor: pointer;
                    `;
                    
                    const modalImg = document.createElement('div');
                    modalImg.style.cssText = `
                        max-width: 80%;
                        max-height: 80%;
                        border-radius: 50%;
                        overflow: hidden;
                        border: 5px solid white;
                        box-shadow: 0 0 30px rgba(0,0,0,0.5);
                    `;
                    
                    const imgElement = document.createElement('img');
                    imgElement.src = src;
                    imgElement.style.cssText = `
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                        display: block;
                    `;
                    
                    modalImg.appendChild(imgElement);
                    modal.appendChild(modalImg);
                    document.body.appendChild(modal);
                    
                    modal.addEventListener('click', function() {
                        document.body.removeChild(modal);
                    });
                }
            });
        });
    });

    function sortTable(columnIndex) {
        const table = document.getElementById('employeeTable');
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
            
            // Handle numbers
            if (columnIndex === 0) {
                aValue = parseInt(aValue);
                bValue = parseInt(bValue);
            }
            // Handle dates
            else if (columnIndex === 8) {
                aValue = new Date(aCell.querySelector('.fa-calendar')?.parentNode.textContent.trim() || aValue);
                bValue = new Date(bCell.querySelector('.fa-calendar')?.parentNode.textContent.trim() || bValue);
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