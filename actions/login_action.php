<?php
// Start session
session_start();

// Include DB connection
require_once '../config/db.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        // Fetch user from DB
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['status'] != 'ACTIVE') {
                $_SESSION['error'] = "Your account is inactive. Contact admin.";
                header("Location: ../index.php");
                exit();
            }

            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['employee_id'] = $user['employee_id'];
                $_SESSION['email'] = $user['email'];

                // Redirect based on role
                if ($user['role'] == 'ADMIN') {
                    header("Location: ../admin/dashboard.php");
                } elseif ($user['role'] == 'HR') {
                    header("Location: ../hr/dashboard.php");
                } else {
                    header("Location: ../employee/dashboard.php");
                }
                exit();
            } else {
                $_SESSION['error'] = "Incorrect password.";
                header("Location: ../index.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Email not registered.";
            header("Location: ../index.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Please enter both email and password.";
        header("Location: ../index.php");
        exit();
    }
} else {
    // Redirect to login if accessed directly
    header("Location: ../index.php");
    exit();
}
