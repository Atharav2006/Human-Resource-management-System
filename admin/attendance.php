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

// Fetch all employees
$employees_stmt = $conn->prepare("SELECT e.emp_id, u.employee_id, u.email, e.first_name, e.last_name 
                                  FROM employees e 
                                  JOIN users u ON e.user_id = u.user_id
                                  ORDER BY e.emp_id ASC");
$employees_stmt->execute();
$employees = $employees_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mark Attendance - Dayflow HRMS</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .container { max-width: 900px; margin: auto; }
        .error { color: red; margin-bottom: 15px; }
        .success { color: green; margin-bottom: 15px; }
    </style>
</head>
<body>
<div class="container">
    <h3 class="mb-4">Mark Attendance</h3>

    <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
    <?php if (isset($success)) echo "<div class='success'>$success</div>"; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label>Select Employee</label>
            <select name="emp_id" class="form-control" required>
                <option value="">-- Select Employee --</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['emp_id'] ?>">
                        <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?> 
                        (<?= htmlspecialchars($emp['employee_id']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label>Date</label>
            <input type="date" name="attendance_date" class="form-control" required value="<?= date('Y-m-d') ?>">
        </div>

        <div class="mb-3">
            <label>Check In</label>
            <input type="time" name="check_in" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Check Out</label>
            <input type="time" name="check_out" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Status</label>
            <select name="status" class="form-control" required>
                <option value="PRESENT">Present</option>
                <option value="ABSENT">Absent</option>
                <option value="HALF-DAY">Half-Day</option>
                <option value="LEAVE">Leave</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Mark Attendance</button>
    </form>
</div>
</body>
</html>
