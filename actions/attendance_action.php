<?php
session_start();
define('REQUIRED_ROLE', 'EMPLOYEE'); // only employees can punch
require_once '../config/auth.php';
require_once '../config/db.php'; // $conn is defined here

$user_id = $_SESSION['user_id'];
$today = date('Y-m-d');

// Get emp_id from employees table
$stmt = $conn->prepare("SELECT emp_id FROM employees WHERE user_id = ?");
$stmt->execute([$user_id]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$emp) {
    $_SESSION['error'] = "Employee profile not found.";
    header("Location: ../employee/attendance.php");
    exit;
}
$emp_id = $emp['emp_id'];

// Check action: punch_in or punch_out
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // Check if attendance record exists today
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE emp_id = ? AND attendance_date = ?");
    $stmt->execute([$emp_id, $today]);
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($action === 'punch_in') {
        if ($attendance && $attendance['check_in']) {
            $_SESSION['error'] = "Already punched in today.";
        } else {
            if ($attendance) {
                $stmt = $conn->prepare("UPDATE attendance SET check_in = ? WHERE attendance_id = ?");
                $stmt->execute([date('H:i:s'), $attendance['attendance_id']]);
            } else {
                $stmt = $conn->prepare("INSERT INTO attendance (emp_id, attendance_date, check_in) VALUES (?, ?, ?)");
                $stmt->execute([$emp_id, $today, date('H:i:s')]);
            }
            $_SESSION['success'] = "Punch in recorded.";
        }
    } elseif ($action === 'punch_out') {
        if (!$attendance || !$attendance['check_in']) {
            $_SESSION['error'] = "You must punch in first.";
        } elseif ($attendance['check_out']) {
            $_SESSION['error'] = "Already punched out today.";
        } else {
            $stmt = $conn->prepare("UPDATE attendance SET check_out = ? WHERE attendance_id = ?");
            $stmt->execute([date('H:i:s'), $attendance['attendance_id']]);
            $_SESSION['success'] = "Punch out recorded.";
        }
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: ../employee/attendance.php");
exit;
