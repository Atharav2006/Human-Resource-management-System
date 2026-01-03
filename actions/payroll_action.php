<?php
session_start();
require_once '../config/auth.php';
require_once '../config/db.php';

define('REQUIRED_ROLE','ADMIN');

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $month = $_POST['month']; // YYYY-MM
    $basic_salary = $_POST['basic_salary'];

    // Fetch all employees
    $stmt = $pdo->prepare("SELECT emp_id FROM employees");
    $stmt->execute();
    $employees = $stmt->fetchAll();

    foreach($employees as $emp){
        $emp_id = $emp['emp_id'];

        // Total working days
        $total_days = date('t', strtotime($month.'-01'));

        // Present days
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as present_days
            FROM attendance
            WHERE emp_id=? AND DATE_FORMAT(attendance_date,'%Y-%m') = ? AND check_in IS NOT NULL
        ");
        $stmt->execute([$emp_id, $month]);
        $present_days = $stmt->fetch()['present_days'];

        // Approved leave days
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as leave_days
            FROM leave_requests
            WHERE emp_id=? AND status='APPROVED' AND DATE_FORMAT(start_date,'%Y-%m') = ?
        ");
        $stmt->execute([$emp_id, $month]);
        $leave_days = $stmt->fetch()['leave_days'];

        // Deductions
        $absent_days = $total_days - ($present_days + $leave_days);
        $deductions = ($basic_salary / $total_days) * $absent_days;

        $net_salary = $basic_salary - $deductions;

        // Insert payroll
        $stmt = $pdo->prepare("
            INSERT INTO payroll (emp_id, basic_salary, net_salary, salary_month, generated_on)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$emp_id, $basic_salary, $net_salary, $month, date('Y-m-d')]);
    }

    $_SESSION['success'] = "Payroll generated for $month.";
    header("Location: ../admin/payroll.php");
    exit;

} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../admin/payroll.php");
    exit;
}
