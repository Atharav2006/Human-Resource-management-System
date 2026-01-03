<?php
require_once '../config/db.php';
require_once '../config/auth.php';

// Set default page title if not set
if (!isset($page_title)) {
    $page_title = "Dayflow HRMS";
}

// Determine user info if logged in
$user_name = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT first_name, last_name, role FROM employees WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($employee) {
        $user_name = $employee['first_name'] . ' ' . $employee['last_name'];
    } else if ($_SESSION['role'] == 'ADMIN' || $_SESSION['role'] == 'HR') {
        $user_name = $_SESSION['role'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { padding-top: 70px; background: #f8f9fa; }
    </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
    <div class="container">
        <a class="navbar-brand" href="<?= ($_SESSION['role'] == 'EMPLOYEE') ? '../employee/dashboard.php' : 'dashboard.php' ?>">Dayflow HRMS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if ($_SESSION['role'] == 'ADMIN' || $_SESSION['role'] == 'HR'): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="employee_list.php">Employees</a></li>
                        <li class="nav-item"><a class="nav-link" href="attendance.php">Attendance</a></li>
                        <li class="nav-item"><a class="nav-link" href="payroll.php">Payroll</a></li>
                        <li class="nav-item"><a class="nav-link" href="leave_request.php">Leave Requests</a></li>
                        <li class="nav-item"><a class="nav-link" href="reports.php">Reports</a></li>
                    <?php elseif ($_SESSION['role'] == 'EMPLOYEE'): ?>
                        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="apply_leave.php">Apply Leave</a></li>
                        <li class="nav-item"><a class="nav-link" href="attendance_view.php">Attendance</a></li>
                        <li class="nav-item"><a class="nav-link" href="payroll_view.php">Payroll</a></li>
                        <li class="nav-item"><a class="nav-link" href="profile.php">Profile</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Logout (<?= htmlspecialchars($user_name) ?>)</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</nav>
