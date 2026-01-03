<?php
require_once '../config/db.php';
require_once '../config/auth.php';

// Only EMPLOYEE can access
if ($_SESSION['role'] != 'EMPLOYEE') {
    header("Location: ../index.php");
    exit();
}

// Get employee ID linked to logged-in user
$stmt = $conn->prepare("SELECT emp_id FROM employees WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    die("Employee profile not found. Contact admin.");
}

$emp_id = $employee['emp_id'];

// Fetch payroll records for this employee
$payroll_stmt = $conn->prepare("
    SELECT * FROM payroll 
    WHERE emp_id = :emp_id
    ORDER BY generated_on DESC
");
$payroll_stmt->bindParam(':emp_id', $emp_id);
$payroll_stmt->execute();
$payroll_records = $payroll_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Payroll - Dayflow HRMS</title>
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
            max-width: 1200px;
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

        .page-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 8px;
            padding-left: 75px;
        }

        /* ===== PAYROLL SUMMARY ===== */
        .payroll-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: var(--odoo-card-bg);
            border-radius: var(--odoo-radius);
            padding: 25px;
            text-align: center;
            border: 1px solid var(--odoo-border);
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .summary-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 15px;
            color: white;
        }

        .summary-icon.salary {
            background: linear-gradient(135deg, var(--odoo-primary) 0%, var(--odoo-secondary) 100%);
        }

        .summary-icon.allowance {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }

        .summary-icon.deduction {
            background: linear-gradient(135deg, #dc3545 0%, #e35d6a 100%);
        }

        .summary-icon.count {
            background: linear-gradient(135deg, #6f42c1 0%, #a370f7 100%);
        }

        .summary-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--odoo-primary);
            margin: 10px 0;
            font-family: 'Courier New', monospace;
        }

        .summary-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ===== PAYROLL TABLE CARD ===== */
        .payroll-card {
            background: var(--odoo-card-bg);
            border-radius: var(--odoo-radius-lg);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid var(--odoo-border);
            overflow: hidden;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .card-header h5 {
            margin: 0;
            color: var(--odoo-primary);
            font-weight: 600;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h5 i {
            color: var(--odoo-secondary);
        }

        /* ===== ODOO TABLE STYLING ===== */
        .table-container {
            overflow-x: auto;
            border-radius: var(--odoo-radius-sm);
            border: 1px solid var(--odoo-border);
        }

        .table {
            margin-bottom: 0;
            min-width: 800px;
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

        .table tbody td:first-child {
            font-weight: 600;
            color: var(--odoo-primary);
        }

        /* ===== CURRENCY STYLING ===== */
        .currency {
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }

        .currency-positive {
            color: #28a745;
        }

        .currency-negative {
            color: #dc3545;
        }

        .currency-total {
            color: var(--odoo-primary);
            font-size: 18px;
        }

        /* ===== PAYROLL MONTH BADGE ===== */
        .month-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background: rgba(113, 75, 103, 0.1);
            border-radius: 20px;
            color: var(--odoo-primary);
            font-weight: 600;
            font-size: 14px;
        }

        .month-badge i {
            color: var(--odoo-secondary);
        }

        /* ===== DATE STYLING ===== */
        .date-cell {
            color: #666;
            font-size: 14px;
        }

        .date-cell i {
            margin-right: 8px;
            color: var(--odoo-secondary);
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

        /* ===== BACK BUTTON ===== */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: var(--odoo-secondary);
            text-decoration: none;
            font-weight: 600;
            padding: 14px 28px;
            border-radius: var(--odoo-radius-sm);
            background: rgba(0, 160, 157, 0.1);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 160, 157, 0.2);
        }

        .btn-back:hover {
            background: var(--odoo-secondary);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 160, 157, 0.2);
            border-color: var(--odoo-secondary);
        }

        /* ===== DOWNLOAD BUTTON ===== */
        .btn-download {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--odoo-gradient);
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: var(--odoo-radius-sm);
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-download:hover {
            background: var(--odoo-gradient-light);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(113, 75, 103, 0.2);
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
            
            .page-subtitle {
                padding-left: 0;
                text-align: center;
            }
            
            .payroll-card {
                padding: 20px;
            }
            
            .card-header {
                flex-direction: column;
                align-items: stretch;
            }
            
            .table {
                font-size: 14px;
            }
            
            .table thead th, 
            .table tbody td {
                padding: 12px 10px;
            }
        }

        @media (max-width: 480px) {
            .payroll-summary {
                grid-template-columns: 1fr;
            }
            
            .summary-card {
                padding: 20px;
            }
            
            .summary-value {
                font-size: 24px;
            }
            
            .empty-state {
                padding: 40px 15px;
            }
            
            .empty-state i {
                font-size: 48px;
            }
        }

        /* ===== UTILITY CLASSES ===== */
        .mt-3 {
            margin-top: 1rem !important;
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
                My Payroll Records
            </h3>
            <p class="page-subtitle">View your salary details and payment history</p>
        </div>
        <a href="dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>
    </div>

    <!-- Payroll Summary Cards -->
    <div class="payroll-summary">
        <?php
        // Calculate summary statistics
        $total_salary = 0;
        $total_allowances = 0;
        $total_deductions = 0;
        $record_count = count($payroll_records);
        
        if ($record_count > 0) {
            foreach ($payroll_records as $pay) {
                $total_salary += $pay['net_salary'];
                $total_allowances += $pay['allowances'];
                $total_deductions += $pay['deductions'];
            }
            
        }
        ?>
        
        <div class="summary-card">
            <div class="summary-icon salary">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="summary-value">
                $<?= number_format($record_count > 0 ? $total_salary : 0, 2) ?>
            </div>
            <div class="summary-label">Total Salary</div>
        </div>
        
        <div class="summary-card">
            <div class="summary-icon allowance">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div class="summary-value">
                $<?= number_format($total_allowances, 2) ?>
            </div>
            <div class="summary-label">Total Allowances</div>
        </div>
        
        <div class="summary-card">
            <div class="summary-icon deduction">
                <i class="fas fa-minus-circle"></i>
            </div>
            <div class="summary-value currency-negative">
                $<?= number_format($total_deductions, 2) ?>
            </div>
            <div class="summary-label">Total Deductions</div>
        </div>
        
        <div class="summary-card">
            <div class="summary-icon count">
                <i class="fas fa-receipt"></i>
            </div>
            <div class="summary-value">
                <?= $record_count ?>
            </div>
            <div class="summary-label">Total Records</div>
        </div>
    </div>

    <!-- Payroll Table Card -->
    <div class="payroll-card">
        <div class="card-header">
            <h5>
                <i class="fas fa-list-alt"></i>
                Salary Payment History
            </h5>
            <?php if ($record_count > 0): ?>
                <button class="btn-download" onclick="downloadPayroll()">
                    <i class="fas fa-download"></i>
                    Download Report
                </button>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <?php if ($payroll_records): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-calendar-alt"></i> Salary Month</th>
                            <th><i class="fas fa-money-bill"></i> Basic Salary</th>
                            <th><i class="fas fa-plus-circle"></i> Allowances</th>
                            <th><i class="fas fa-minus-circle"></i> Deductions</th>
                            <th><i class="fas fa-calculator"></i> Net Salary</th>
                            <th><i class="fas fa-calendar-check"></i> Generated On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payroll_records as $pay): ?>
                            <tr>
                                <td>
                                    <div class="month-badge">
                                        <i class="fas fa-calendar"></i>
                                        <?= htmlspecialchars($pay['salary_month']) ?>
                                    </div>
                                </td>
                                <td class="currency">
                                    $<?= number_format($pay['basic_salary'], 2) ?>
                                </td>
                                <td class="currency currency-positive">
                                    +$<?= number_format($pay['allowances'], 2) ?>
                                </td>
                                <td class="currency currency-negative">
                                    -$<?= number_format($pay['deductions'], 2) ?>
                                </td>
                                <td class="currency currency-total">
                                    $<?= number_format($pay['net_salary'], 2) ?>
                                </td>
                                <td class="date-cell">
                                    <i class="fas fa-clock"></i>
                                    <?= date('M d, Y', strtotime($pay['generated_on'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-invoice"></i>
                    <h4>No Payroll Records Found</h4>
                    <p>You don't have any payroll records yet. Payroll records are typically generated at the end of each payment cycle.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function downloadPayroll() {
        // Create a simple CSV download
        const table = document.querySelector('.table');
        if (!table) return;
        
        let csv = [];
        const rows = table.querySelectorAll('tr');
        
        for (let i = 0; i < rows.length; i++) {
            const row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (let j = 0; j < cols.length; j++) {
                // Clean the text content (remove icons, format numbers)
                let text = cols[j].textContent.trim();
                text = text.replace(/[+$-]/g, '').trim();
                row.push('"' + text + '"');
            }
            
            csv.push(row.join(','));
        }
        
        // Download CSV file
        const csvContent = "data:text/csv;charset=utf-8," + csv.join('\n');
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement('a');
        link.setAttribute('href', encodedUri);
        link.setAttribute('download', 'payroll_records_<?= date('Y-m-d') ?>.csv');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Add print functionality
    function printPayroll() {
        window.print();
    }

    // Add year filter functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Add print button if there are records
        const cardHeader = document.querySelector('.card-header');
        if (cardHeader && <?= $record_count ?> > 0) {
            const printBtn = document.createElement('button');
            printBtn.className = 'btn-download';
            printBtn.style.marginLeft = '10px';
            printBtn.style.background = 'linear-gradient(135deg, #6c757d 0%, #868e96 100%)';
            printBtn.innerHTML = '<i class="fas fa-print"></i> Print';
            printBtn.onclick = printPayroll;
            cardHeader.appendChild(printBtn);
        }
        
        // Add sorting functionality
        const tableHeaders = document.querySelectorAll('.table thead th');
        tableHeaders.forEach((header, index) => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function() {
                sortTable(index);
            });
        });
    });

    function sortTable(columnIndex) {
        const table = document.querySelector('.table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
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
            
            // Handle currency values
            if (aValue.includes('$')) {
                aValue = parseFloat(aValue.replace(/[$,]/g, ''));
                bValue = parseFloat(bValue.replace(/[$,]/g, ''));
            }
            
            // Handle dates
            else if (aCell.querySelector('.date-cell')) {
                aValue = new Date(aValue.replace('AM', '').replace('PM', '').trim());
                bValue = new Date(bValue.replace('AM', '').replace('PM', '').trim());
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