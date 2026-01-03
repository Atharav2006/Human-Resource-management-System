<?php
require_once '../config/db.php';
require_once '../config/auth.php';

// Only EMPLOYEE can access
if ($_SESSION['role'] != 'EMPLOYEE') {
    header("Location: ../index.php");
    exit();
}

// Fetch employee info
$stmt = $conn->prepare("
    SELECT e.*, u.email 
    FROM employees e
    JOIN users u ON e.user_id = u.user_id
    WHERE e.user_id = :user_id
");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    die("Employee profile not found. Contact admin.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - Dayflow HRMS</title>
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

        .page-subtitle {
            font-size: 16px;
            opacity: 0.9;
            margin-top: 8px;
            padding-left: 75px;
        }

        /* ===== PROFILE CONTAINER ===== */
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
        }

        /* ===== PROFILE CARD ===== */
        .profile-card {
            background: var(--odoo-card-bg);
            border-radius: var(--odoo-radius-lg);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 30px;
            border: 1px solid var(--odoo-border);
            height: 100%;
        }

        /* ===== PROFILE PICTURE SECTION ===== */
        .profile-picture-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-pic-container {
            position: relative;
            width: 200px;
            height: 200px;
            margin-bottom: 20px;
        }

        .profile-pic {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 6px solid white;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }

        .profile-pic:hover {
            transform: scale(1.03);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.2);
        }

        .profile-default-pic {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--odoo-primary) 0%, var(--odoo-secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 72px;
            font-weight: 700;
            border: 6px solid white;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .profile-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
            margin-top: 15px;
        }

        .profile-status i {
            font-size: 12px;
        }

        /* ===== EMPLOYEE INFO SECTION ===== */
        .employee-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: rgba(113, 75, 103, 0.05);
            border-radius: var(--odoo-radius);
            padding: 20px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .info-card:hover {
            background: rgba(113, 75, 103, 0.08);
            border-color: var(--odoo-border);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .info-icon {
            width: 50px;
            height: 50px;
            border-radius: var(--odoo-radius);
            background: linear-gradient(135deg, var(--odoo-primary) 0%, var(--odoo-secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin-bottom: 15px;
        }

        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .info-value {
            font-size: 16px;
            color: var(--odoo-primary);
            font-weight: 600;
            word-break: break-word;
        }

        .info-value.email {
            color: var(--odoo-secondary);
        }

        .info-value.phone {
            font-family: 'Courier New', monospace;
        }

        /* ===== DETAILED INFO SECTION ===== */
        .detailed-info {
            margin-top: 30px;
        }

        .detailed-info h5 {
            color: var(--odoo-primary);
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--odoo-border);
        }

        .detailed-info h5 i {
            color: var(--odoo-secondary);
        }

        .info-details {
            display: grid;
            gap: 15px;
        }

        .detail-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(113, 75, 103, 0.1);
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-icon {
            color: var(--odoo-secondary);
            font-size: 18px;
            width: 24px;
            margin-top: 2px;
        }

        .detail-content {
            flex: 1;
        }

        .detail-label {
            font-weight: 600;
            color: var(--odoo-primary);
            font-size: 14px;
            margin-bottom: 3px;
        }

        .detail-value {
            color: #666;
            font-size: 15px;
            line-height: 1.5;
        }

        /* ===== ACTION BUTTONS ===== */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: var(--odoo-gradient);
            border: none;
            border-radius: var(--odoo-radius-sm);
            padding: 15px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white;
            font-size: 16px;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: var(--odoo-gradient-light);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(113, 75, 103, 0.25);
            color: white;
            text-decoration: none;
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(113, 75, 103, 0.2);
        }

        .btn-secondary {
            background: rgba(113, 75, 103, 0.1);
            border: 1px solid var(--odoo-border);
            border-radius: var(--odoo-radius-sm);
            padding: 15px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: var(--odoo-primary);
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: var(--odoo-primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(113, 75, 103, 0.2);
            text-decoration: none;
        }

        /* ===== STATS SECTION ===== */
        .stats-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid var(--odoo-border);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: rgba(113, 75, 103, 0.05);
            border-radius: var(--odoo-radius);
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            background: rgba(113, 75, 103, 0.08);
            transform: translateY(-2px);
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--odoo-primary);
            margin: 5px 0;
            font-family: 'Courier New', monospace;
        }

        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            
            .profile-card {
                padding: 20px;
            }
            
            .profile-pic-container {
                width: 150px;
                height: 150px;
            }
            
            .profile-default-pic {
                font-size: 48px;
            }
            
            .employee-info-grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-primary, .btn-secondary {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .profile-pic-container {
                width: 120px;
                height: 120px;
            }
            
            .profile-default-pic {
                font-size: 36px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* ===== UTILITY CLASSES ===== */
        .mt-3 {
            margin-top: 1rem !important;
        }

        .text-center {
            text-align: center !important;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-content">
            <h3>
                <i class="fas fa-user-circle"></i>
                My Profile
            </h3>
            <p class="page-subtitle">View and manage your personal and professional information</p>
        </div>
        <a href="dashboard.php" class="btn-secondary" style="text-decoration: none;">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>
    </div>

    <!-- Profile Container -->
    <div class="profile-container">
        <!-- Left Column: Profile Picture & Basic Info -->
        <div class="profile-card">
            <div class="profile-picture-section">
                <div class="profile-pic-container">
                    <?php if (!empty($employee['profile_picture'])): ?>
                        <img src="../uploads/<?= htmlspecialchars($employee['profile_picture']) ?>" 
                             class="profile-pic" 
                             alt="Profile Picture"
                             onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"200\" height=\"200\" viewBox=\"0 0 200 200\"><rect width=\"200\" height=\"200\" fill=\"%23714B67\"/><text x=\"50%\" y=\"50%\" font-family=\"Arial\" font-size=\"60\" fill=\"white\" text-anchor=\"middle\" dy=\".3em\"><?= substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1) ?></text></svg>'">
                    <?php else: ?>
                        <div class="profile-default-pic">
                            <?= substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1) ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <h4><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></h4>
                <p style="color: #666; margin-bottom: 5px;"><?= htmlspecialchars($employee['designation']) ?></p>
                <p style="color: var(--odoo-secondary); margin-bottom: 15px;">
                    <i class="fas fa-building"></i> <?= htmlspecialchars($employee['department']) ?>
                </p>
                
                <div class="profile-status">
                    <i class="fas fa-circle"></i>
                    Active Employee
                </div>
            </div>

            <!-- Employee ID & Email -->
            <div class="employee-info-grid">
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div class="info-label">Employee ID</div>
                    <div class="info-value"><?= htmlspecialchars($employee['emp_id']) ?></div>
                </div>
                
                <div class="info-card">
                    <div class="info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info-label">Email Address</div>
                    <div class="info-value email"><?= htmlspecialchars($employee['email']) ?></div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="detailed-info">
                <h5><i class="fas fa-address-card"></i> Contact Information</h5>
                <div class="info-details">
                    <?php if (!empty($employee['phone'])): ?>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Phone Number</div>
                                <div class="detail-value phone"><?= htmlspecialchars($employee['phone']) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($employee['address'])): ?>
                        <div class="detail-item">
                            <div class="detail-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="detail-content">
                                <div class="detail-label">Address</div>
                                <div class="detail-value"><?= htmlspecialchars($employee['address']) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column: Detailed Information -->
        <div class="profile-card">
            <!-- Professional Information -->
            <div class="detailed-info">
                <h5><i class="fas fa-briefcase"></i> Professional Information</h5>
                <div class="info-details">
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Department</div>
                            <div class="detail-value"><?= htmlspecialchars($employee['department']) ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Designation</div>
                            <div class="detail-value"><?= htmlspecialchars($employee['designation']) ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Joining Date</div>
                            <div class="detail-value">
                                <?= date('F d, Y', strtotime($employee['joining_date'])) ?>
                                <span style="color: #999; font-size: 13px; margin-left: 10px;">
                                    (<?= date_diff(date_create($employee['joining_date']), date_create('today'))->y ?> years)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Employment Stats -->
            <div class="stats-section">
                <h5><i class="fas fa-chart-line"></i> Employment Statistics</h5>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value">
                            <?php 
                            $joining_date = new DateTime($employee['joining_date']);
                            $today = new DateTime('today');
                            $years = $today->diff($joining_date)->y;
                            echo $years;
                            ?>
                        </div>
                        <div class="stat-label">Years of Service</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-value"><?= date('M Y', strtotime($employee['joining_date'])) ?></div>
                        <div class="stat-label">Joined In</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-value">Full-time</div>
                        <div class="stat-label">Employment Type</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-value">Active</div>
                        <div class="stat-label">Current Status</div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="edit_profile.php" class="btn-primary">
                    <i class="fas fa-edit"></i>
                    Edit Profile
                </a>
                <a href="dashboard.php" class="btn-secondary">
                    <i class="fas fa-home"></i>
                    Go to Dashboard
                </a>
            </div>

            <!-- Quick Links -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--odoo-border);">
                <h6 style="color: var(--odoo-primary); margin-bottom: 15px; font-weight: 600;">
                    <i class="fas fa-link"></i> Quick Links
                </h6>
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <a href="attendance.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 15px; background: rgba(113, 75, 103, 0.05); border-radius: var(--odoo-radius-sm); color: var(--odoo-primary); text-decoration: none; font-size: 14px; transition: all 0.3s ease;">
                        <i class="fas fa-clock"></i> Attendance
                    </a>
                    <a href="payroll.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 15px; background: rgba(113, 75, 103, 0.05); border-radius: var(--odoo-radius-sm); color: var(--odoo-primary); text-decoration: none; font-size: 14px; transition: all 0.3s ease;">
                        <i class="fas fa-file-invoice-dollar"></i> Payroll
                    </a>
                    <a href="apply_leave.php" style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 15px; background: rgba(113, 75, 103, 0.05); border-radius: var(--odoo-radius-sm); color: var(--odoo-primary); text-decoration: none; font-size: 14px; transition: all 0.3s ease;">
                        <i class="fas fa-calendar-plus"></i> Apply Leave
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Calculate and display years of service
    document.addEventListener('DOMContentLoaded', function() {
        // Format phone number if it exists
        const phoneElement = document.querySelector('.detail-value.phone');
        if (phoneElement) {
            const phone = phoneElement.textContent.trim();
            if (phone) {
                // Simple phone formatting (XXX) XXX-XXXX
                const formatted = phone.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
                phoneElement.textContent = formatted;
            }
        }

        // Add hover effects to quick links
        const quickLinks = document.querySelectorAll('a[style*="background: rgba(113, 75, 103, 0.05)"]');
        quickLinks.forEach(link => {
            link.addEventListener('mouseenter', function() {
                this.style.background = 'rgba(113, 75, 103, 0.1)';
                this.style.transform = 'translateY(-2px)';
                this.style.boxShadow = '0 3px 10px rgba(0,0,0,0.1)';
            });
            
            link.addEventListener('mouseleave', function() {
                this.style.background = 'rgba(113, 75, 103, 0.05)';
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });

        // Add copy email functionality
        const emailElement = document.querySelector('.info-value.email');
        if (emailElement) {
            emailElement.style.cursor = 'pointer';
            emailElement.title = 'Click to copy email';
            emailElement.addEventListener('click', function() {
                const email = this.textContent;
                navigator.clipboard.writeText(email).then(() => {
                    const originalText = this.textContent;
                    this.textContent = 'Copied!';
                    this.style.color = '#28a745';
                    setTimeout(() => {
                        this.textContent = originalText;
                        this.style.color = 'var(--odoo-secondary)';
                    }, 2000);
                });
            });
        }
    });
</script>
</body>
</html>