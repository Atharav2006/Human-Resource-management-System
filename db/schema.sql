-- ===============================
-- Dayflow HRMS Database
-- ===============================

CREATE DATABASE IF NOT EXISTS dayflow_hrms;
USE dayflow_hrms;

-- ===============================
-- 1. USERS TABLE (Authentication)
-- ===============================
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('ADMIN', 'HR', 'EMPLOYEE') DEFAULT 'EMPLOYEE',
    is_verified BOOLEAN DEFAULT 0,
    status ENUM('ACTIVE', 'INACTIVE') DEFAULT 'ACTIVE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===============================
-- 2. EMPLOYEES TABLE (Profile)
-- ===============================
CREATE TABLE employees (
    emp_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone VARCHAR(15),
    address TEXT,
    department VARCHAR(50),
    designation VARCHAR(50),
    joining_date DATE,
    profile_picture VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ===============================
-- 3. ATTENDANCE TABLE
-- ===============================
CREATE TABLE attendance (
    attendance_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    status ENUM('PRESENT', 'ABSENT', 'HALF-DAY', 'LEAVE') DEFAULT 'PRESENT',
    marked_by ENUM('SYSTEM', 'ADMIN') DEFAULT 'SYSTEM',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (emp_id) REFERENCES employees(emp_id) ON DELETE CASCADE,
    UNIQUE(emp_id, attendance_date)
);

-- ===============================
-- 4. LEAVE TYPES TABLE
-- ===============================
CREATE TABLE leave_types (
    leave_type_id INT AUTO_INCREMENT PRIMARY KEY,
    leave_name VARCHAR(50) NOT NULL,
    description TEXT
);

-- ===============================
-- 5. LEAVE REQUESTS TABLE
-- ===============================
CREATE TABLE leave_requests (
    leave_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    status ENUM('PENDING', 'APPROVED', 'REJECTED') DEFAULT 'PENDING',
    admin_comment TEXT,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (emp_id) REFERENCES employees(emp_id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(leave_type_id)
);

-- ===============================
-- 6. PAYROLL TABLE
-- ===============================
CREATE TABLE payroll (
    payroll_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    basic_salary DECIMAL(10,2) NOT NULL,
    allowances DECIMAL(10,2) DEFAULT 0.00,
    deductions DECIMAL(10,2) DEFAULT 0.00,
    net_salary DECIMAL(10,2) NOT NULL,
    salary_month VARCHAR(20) NOT NULL,
    generated_on DATE NOT NULL,
    FOREIGN KEY (emp_id) REFERENCES employees(emp_id) ON DELETE CASCADE
);

-- ===============================
-- 7. EMPLOYEE DOCUMENTS
-- ===============================
CREATE TABLE employee_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    emp_id INT NOT NULL,
    document_name VARCHAR(100),
    file_path VARCHAR(255),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (emp_id) REFERENCES employees(emp_id) ON DELETE CASCADE
);

-- ===============================
-- 8. NOTIFICATIONS TABLE
-- ===============================
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ===============================
-- 9. ACTIVITY LOGS (OPTIONAL)
-- ===============================
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity TEXT NOT NULL,
    log_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- ===============================
-- DEFAULT LEAVE TYPES DATA
-- ===============================
INSERT INTO leave_types (leave_name, description) VALUES
('Paid Leave', 'Company approved paid leave'),
('Sick Leave', 'Medical or health-related leave'),
('Unpaid Leave', 'Leave without salary deduction');

-- ===============================
-- END OF DATABASE CREATION
-- ===============================
