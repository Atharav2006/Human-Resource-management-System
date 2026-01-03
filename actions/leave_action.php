<?php
session_start();
require_once '../config/auth.php';
require_once '../config/db.php';

$user_id = $_SESSION['user_id'];

// Get emp_id
$stmt = $pdo->prepare("SELECT emp_id FROM employees WHERE user_id = ?");
$stmt->execute([$user_id]);
$emp = $stmt->fetch();
if(!$emp){
    $_SESSION['error'] = "Employee profile not found.";
    header("Location: ../employee/apply_leave.php");
    exit;
}
$emp_id = $emp['emp_id'];

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    if(isset($_POST['action']) && $_POST['action'] === 'apply_leave'){
        $start_date = $_POST['from_date'];
        $end_date = $_POST['to_date'];
        $reason = trim($_POST['reason']);

        if(empty($start_date) || empty($end_date) || empty($reason)){
            $_SESSION['error'] = "All fields are required.";
            header("Location: ../employee/apply_leave.php");
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO leave_requests (emp_id, leave_type_id, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$emp_id, 1, $start_date, $end_date, $reason]); // leave_type_id=1 default Paid Leave

        $_SESSION['success'] = "Leave request submitted.";
        header("Location: ../employee/apply_leave.php");
        exit;
    }

    elseif(isset($_POST['action']) && $_POST['action'] === 'update_status' && isset($_POST['leave_id'])){
        define('REQUIRED_ROLE','ADMIN');
        require_once '../config/auth.php';

        $leave_id = $_POST['leave_id'];
        $status = strtoupper($_POST['status']); // PENDING / APPROVED / REJECTED

        $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE leave_id = ?");
        $stmt->execute([$status, $leave_id]);

        $_SESSION['success'] = "Leave status updated.";
        header("Location: ../admin/leave_requests.php");
        exit;
    }

} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: ../employee/dashboard.php");
    exit;
}
