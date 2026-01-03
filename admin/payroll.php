<?php
require_once '../config/db.php';
require_once '../config/auth.php';

// Only ADMIN or HR can access
if (!in_array($_SESSION['role'], ['ADMIN', 'HR'])) {
    header("Location: ../index.php");
    exit();
}

// Handle payroll submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $emp_id = $_POST['emp_id'];
    $basic_salary = $_POST['basic_salary'];
    $allowances = $_POST['allowances'];
    $deductions = $_POST['deductions'];
    $salary_month = $_POST['salary_month'];
    $generated_on = date('Y-m-d');

    $net_salary = $basic_salary + $allowances - $deductions;

    $stmt = $conn->prepare("INSERT INTO payroll (emp_id, basic_salary, allowances, deductions, net_salary, salary_month, generated_on)
                            VALUES (:emp_id, :basic_salary, :allowances, :deductions, :net_salary, :salary_month, :generated_on)");
    $stmt->bindParam(':emp_id', $emp_id);
    $stmt->bindParam(':basic_salary', $basic_salary);
    $stmt->bindParam(':allowances', $allowances);
    $stmt->bindParam(':deductions', $deductions);
    $stmt->bindParam(':net_salary', $net_salary);
    $stmt->bindParam(':salary_month', $salary_month);
    $stmt->bindParam(':generated_on', $generated_on);

    if ($stmt->execute()) {
        $success = "Payroll generated successfully for employee ID $emp_id.";
    } else {
        $error = "Failed to generate payroll.";
    }
}

// Fetch all employees
$stmt = $conn->prepare("SELECT e.emp_id, u.employee_id, u.email, e.first_name, e.last_name 
                        FROM employees e 
                        JOIN users u ON e.user_id = u.user_id
                        ORDER BY e.emp_id ASC");
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Generate Payroll - Dayflow HRMS</title>
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
            max-width: 1000px;
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

        /* ===== FORM CARD ===== */
        .form-card {
            background: var(--odoo-card-bg);
            border-radius: var(--odoo-radius-lg);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid var(--odoo-border);
            overflow: hidden;
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--odoo-border);
        }

        .form-header i {
            color: var(--odoo-secondary);
            font-size: 24px;
            background: rgba(0, 160, 157, 0.1);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .form-header h5 {
            margin: 0;
            color: var(--odoo-primary);
            font-weight: 600;
            font-size: 22px;
        }

        .form-header p {
            margin: 5px 0 0;
            color: #666;
            font-size: 14px;
        }

        /* ===== FORM STYLING ===== */
        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--odoo-primary);
        }

        .form-label i {
            color: var(--odoo-secondary);
            font-size: 14px;
        }

        .form-control {
            padding: 14px 18px;
            border: 1px solid var(--odoo-border);
            border-radius: var(--odoo-radius-sm);
            font-size: 15px;
            transition: all 0.3s ease;
            background: var(--odoo-light);
            width: 100%;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--odoo-secondary);
            box-shadow: 0 0 0 3px rgba(0, 160, 157, 0.1);
            background: white;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2300A09D' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 12px;
            padding-right: 40px;
        }

        /* ===== SALARY PREVIEW ===== */
        .salary-preview {
            background: linear-gradient(135deg, rgba(113, 75, 103, 0.05) 0%, rgba(0, 160, 157, 0.05) 100%);
            border-radius: var(--odoo-radius);
            padding: 25px;
            margin: 30px 0;
            border: 1px solid var(--odoo-border);
        }

        .salary-preview h6 {
            color: var(--odoo-primary);
            margin-bottom: 20px;
            font-weight: 600;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .salary-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .salary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: white;
            border-radius: var(--odoo-radius-sm);
            border: 1px solid var(--odoo-border);
        }

        .salary-label {
            font-weight: 600;
            color: #666;
            font-size: 14px;
        }

        .salary-value {
            font-weight: 700;
            font-size: 16px;
        }

        .basic-salary {
            color: var(--odoo-primary);
        }

        .allowances {
            color: var(--odoo-success);
        }

        .deductions {
            color: var(--odoo-danger);
        }

        .net-salary {
            background: var(--odoo-gradient);
            color: white;
            padding: 15px;
            border-radius: var(--odoo-radius);
            margin-top: 10px;
        }

        .net-salary .salary-label {
            color: rgba(255, 255, 255, 0.9);
        }

        .net-salary .salary-value {
            font-size: 24px;
        }

        /* ===== BUTTON STYLING ===== */
        .btn-primary {
            background: var(--odoo-gradient);
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: var(--odoo-radius-sm);
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            min-width: 200px;
            justify-content: center;
        }

        .btn-primary:hover {
            background: var(--odoo-gradient-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(113, 75, 103, 0.3);
            color: white;
            text-decoration: none;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: var(--odoo-radius-sm);
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
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

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
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
            
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-back {
                width: 100%;
                justify-content: center;
            }
            
            .form-card {
                padding: 20px;
            }
            
            .salary-items {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-primary, .btn-secondary {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .form-header {
                flex-direction: column;
                text-align: center;
            }
            
            .form-header i {
                margin-bottom: 10px;
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
                <i class="fas fa-file-invoice-dollar"></i>
                Generate Payroll
            </h3>
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

    <!-- Form Card -->
    <div class="form-card">
        <div class="form-header">
            <i class="fas fa-calculator"></i>
            <div>
                <h5>Payroll Information</h5>
                <p>Fill in the details below to generate payroll for an employee</p>
            </div>
        </div>

        <form method="POST" action="" id="payrollForm">
            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-user"></i>
                    Select Employee
                </label>
                <select name="emp_id" class="form-control" required onchange="updateEmployeePreview()">
                    <option value="">-- Select Employee --</option>
                    <?php foreach ($employees as $emp): ?>
                        <option value="<?= $emp['emp_id'] ?>" data-employee-id="<?= htmlspecialchars($emp['employee_id']) ?>" data-email="<?= htmlspecialchars($emp['email']) ?>">
                            <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?> 
                            (ID: <?= htmlspecialchars($emp['employee_id']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="employee-preview mt-2" id="employeePreview" style="display: none; padding: 10px; background: var(--odoo-light); border-radius: var(--odoo-radius-sm); font-size: 14px;">
                    <div><strong>Employee ID:</strong> <span id="previewEmployeeId"></span></div>
                    <div><strong>Email:</strong> <span id="previewEmail"></span></div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-calendar-alt"></i>
                    Salary Month
                </label>
                <input type="month" name="salary_month" class="form-control" required value="<?= date('Y-m') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-money-bill-wave"></i>
                    Basic Salary
                </label>
                <input type="number" step="0.01" name="basic_salary" class="form-control" required 
                       oninput="calculateNetSalary()" placeholder="0.00" min="0">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-plus-circle"></i>
                    Allowances
                </label>
                <input type="number" step="0.01" name="allowances" class="form-control" value="0.00" 
                       oninput="calculateNetSalary()" placeholder="0.00" min="0">
            </div>

            <div class="form-group">
                <label class="form-label">
                    <i class="fas fa-minus-circle"></i>
                    Deductions
                </label>
                <input type="number" step="0.01" name="deductions" class="form-control" value="0.00" 
                       oninput="calculateNetSalary()" placeholder="0.00" min="0">
            </div>

            <!-- Salary Preview -->
            <div class="salary-preview">
                <h6><i class="fas fa-eye"></i> Salary Preview</h6>
                <div class="salary-items">
                    <div class="salary-item">
                        <span class="salary-label">Basic Salary:</span>
                        <span class="salary-value basic-salary">$0.00</span>
                    </div>
                    <div class="salary-item">
                        <span class="salary-label">Allowances:</span>
                        <span class="salary-value allowances">+ $0.00</span>
                    </div>
                    <div class="salary-item">
                        <span class="salary-label">Deductions:</span>
                        <span class="salary-value deductions">- $0.00</span>
                    </div>
                </div>
                <div class="salary-item net-salary">
                    <span class="salary-label">Net Salary:</span>
                    <span class="salary-value">$0.00</span>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-file-export"></i>
                    Generate Payroll
                </button>
                <a href="dashboard.php" class="btn-secondary">
                    <i class="fas fa-times"></i>
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<script>
    // Format currency
    function formatCurrency(amount) {
        return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }

    // Update employee preview
    function updateEmployeePreview() {
        const select = document.querySelector('select[name="emp_id"]');
        const selectedOption = select.options[select.selectedIndex];
        const preview = document.getElementById('employeePreview');
        
        if (select.value) {
            const employeeId = selectedOption.getAttribute('data-employee-id');
            const email = selectedOption.getAttribute('data-email');
            
            document.getElementById('previewEmployeeId').textContent = employeeId;
            document.getElementById('previewEmail').textContent = email;
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    }

    // Calculate net salary
    function calculateNetSalary() {
        const basicSalary = parseFloat(document.querySelector('input[name="basic_salary"]').value) || 0;
        const allowances = parseFloat(document.querySelector('input[name="allowances"]').value) || 0;
        const deductions = parseFloat(document.querySelector('input[name="deductions"]').value) || 0;
        const netSalary = basicSalary + allowances - deductions;
        
        // Update preview
        document.querySelector('.basic-salary').textContent = formatCurrency(basicSalary);
        document.querySelector('.allowances').textContent = '+ ' + formatCurrency(allowances);
        document.querySelector('.deductions').textContent = '- ' + formatCurrency(deductions);
        document.querySelector('.net-salary .salary-value').textContent = formatCurrency(netSalary);
        
        // Animate net salary update
        const netSalaryElement = document.querySelector('.net-salary');
        netSalaryElement.style.animation = 'pulse 0.5s ease';
        setTimeout(() => {
            netSalaryElement.style.animation = '';
        }, 500);
    }

    // Form validation
    document.getElementById('payrollForm').addEventListener('submit', function(e) {
        const basicSalary = parseFloat(document.querySelector('input[name="basic_salary"]').value) || 0;
        const allowances = parseFloat(document.querySelector('input[name="allowances"]').value) || 0;
        const deductions = parseFloat(document.querySelector('input[name="deductions"]').value) || 0;
        const netSalary = basicSalary + allowances - deductions;
        
        if (netSalary < 0) {
            e.preventDefault();
            alert('Error: Net salary cannot be negative. Please check your inputs.');
            return false;
        }
        
        if (basicSalary <= 0) {
            e.preventDefault();
            alert('Error: Basic salary must be greater than 0.');
            return false;
        }
        
        return confirm(`Generate payroll with net salary of ${formatCurrency(netSalary)}?`);
    });

    // Initialize calculations on page load
    document.addEventListener('DOMContentLoaded', function() {
        calculateNetSalary();
        
        // Add currency formatting to inputs on blur
        const salaryInputs = document.querySelectorAll('input[type="number"]');
        salaryInputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value) {
                    this.value = parseFloat(this.value).toFixed(2);
                }
            });
        });
    });
</script>
</body>
</html>