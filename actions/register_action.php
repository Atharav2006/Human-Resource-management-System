<?php
// Start session
session_start();

// Include DB connection
require_once '../config/db.php';

// Only handle POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_id = trim($_POST['employee_id']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Basic validation
    if (empty($employee_id) || empty($email) || empty($password) || empty($role)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: ../register.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        header("Location: ../register.php");
        exit();
    }

    // Check if employee_id or email already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE employee_id = :employee_id OR email = :email LIMIT 1");
    $stmt->bindParam(':employee_id', $employee_id);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        $_SESSION['error'] = "Employee ID or email already exists.";
        header("Location: ../register.php");
        exit();
    }

    // Hash password
    $password_hashed = password_hash($password, PASSWORD_DEFAULT);

    // Insert into users table
    $insert = $conn->prepare("INSERT INTO users (employee_id, email, password, role) VALUES (:employee_id, :email, :password, :role)");
    $insert->bindParam(':employee_id', $employee_id);
    $insert->bindParam(':email', $email);
    $insert->bindParam(':password', $password_hashed);
    $insert->bindParam(':role', $role);

    if ($insert->execute()) {
        $_SESSION['success'] = "User registered successfully. You can now login.";
        header("Location: ../index.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to register user. Try again.";
        header("Location: ../register.php");
        exit();
    }
} else {
    // Redirect if accessed directly
    header("Location: ../register.php");
    exit();
}
