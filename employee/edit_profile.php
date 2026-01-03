<?php
require_once '../config/db.php';
require_once '../config/auth.php';

// Only EMPLOYEE can access
if ($_SESSION['role'] != 'EMPLOYEE') {
    header("Location: ../index.php");
    exit();
}

// Get employee ID
$stmt = $conn->prepare("SELECT * FROM employees WHERE user_id = :user_id");
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    die("Employee profile not found. Contact admin.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $phone      = trim($_POST['phone']);
    $address    = trim($_POST['address']);

    $profile_picture = $employee['profile_picture']; // default existing picture

    // Handle profile picture upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $profile_picture = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $profile_picture);
    }

    // Update employee profile
    $stmt = $conn->prepare("
        UPDATE employees 
        SET first_name = :first_name, last_name = :last_name, phone = :phone, 
            address = :address, profile_picture = :profile_picture
        WHERE emp_id = :emp_id
    ");
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':profile_picture', $profile_picture);
    $stmt->bindParam(':emp_id', $employee['emp_id']);

    if ($stmt->execute()) {
        $success = "Profile updated successfully.";
        // Refresh employee data
        $employee['first_name'] = $first_name;
        $employee['last_name'] = $last_name;
        $employee['phone'] = $phone;
        $employee['address'] = $address;
        $employee['profile_picture'] = $profile_picture;
    } else {
        $error = "Failed to update profile.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile - Dayflow HRMS</title>
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
            max-width: 800px;
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

        .page-header h3 {
            font-weight: 700;
            margin: 0;
            font-size: 28px;
            position: relative;
            z-index: 1;
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
            position: relative;
            z-index: 1;
            padding-left: 75px;
        }

        /* ===== PROFILE FORM CARD ===== */
        .profile-form-card {
            background: var(--odoo-card-bg);
            border-radius: var(--odoo-radius-lg);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            padding: 40px;
            margin-bottom: 30px;
            border: 1px solid var(--odoo-border);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        @media (max-width: 768px) {
            .profile-form-card {
                grid-template-columns: 1fr;
                gap: 30px;
            }
        }

        /* ===== PROFILE PICTURE SECTION ===== */
        .profile-picture-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .profile-image-container {
            position: relative;
            width: 200px;
            height: 200px;
            margin-bottom: 20px;
        }

        .profile-image {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .profile-image:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .profile-image-default {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--odoo-primary) 0%, var(--odoo-secondary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 72px;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .profile-upload {
            position: relative;
            margin-top: 20px;
            width: 100%;
            max-width: 300px;
        }

        .profile-upload input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }

        .profile-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: var(--odoo-gradient);
            color: white;
            padding: 12px 20px;
            border-radius: var(--odoo-radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .profile-upload-label:hover {
            background: var(--odoo-gradient-light);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(113, 75, 103, 0.2);
        }

        .file-info {
            font-size: 12px;
            color: #666;
            margin-top: 8px;
            text-align: center;
        }

        /* ===== FORM SECTION ===== */
        .form-section {
            display: flex;
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            font-weight: 600;
            color: var(--odoo-primary);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
        }

        .form-label i {
            color: var(--odoo-secondary);
            font-size: 18px;
            width: 24px;
        }

        .form-control, .form-textarea {
            border-radius: var(--odoo-radius-sm);
            padding: 14px 16px;
            border: 1px solid var(--odoo-border);
            font-size: 15px;
            transition: all 0.3s ease;
            background-color: var(--odoo-light);
        }

        .form-control:focus, .form-textarea:focus {
            box-shadow: 0 0 0 3px rgba(113, 75, 103, 0.15);
            border-color: var(--odoo-secondary);
            background-color: white;
            transform: translateY(-1px);
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
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

        /* ===== EMPLOYEE INFO ===== */
        .employee-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 10px;
        }

        @media (max-width: 480px) {
            .employee-info {
                grid-template-columns: 1fr;
            }
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: var(--odoo-radius-sm);
            background: rgba(113, 75, 103, 0.05);
        }

        .info-icon {
            color: var(--odoo-secondary);
            font-size: 18px;
            width: 30px;
        }

        .info-label {
            font-weight: 600;
            color: var(--odoo-primary);
            font-size: 13px;
            margin-bottom: 2px;
        }

        .info-value {
            color: #666;
            font-size: 14px;
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

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
                padding: 0 15px;
            }
            
            .page-header {
                padding: 20px;
            }
            
            .page-header h3 {
                font-size: 24px;
            }
            
            .page-header h3 i {
                width: 50px;
                height: 50px;
                font-size: 26px;
            }
            
            .profile-form-card {
                padding: 25px;
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
            .page-header h3 {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .page-subtitle {
                padding-left: 0;
                text-align: center;
            }
            
            .profile-form-card {
                padding: 20px;
            }
            
            .profile-image-container {
                width: 150px;
                height: 150px;
            }
            
            .profile-image-default {
                font-size: 48px;
            }
        }

        /* ===== UTILITY CLASSES ===== */
        .mb-4 {
            margin-bottom: 1.5rem !important;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h3>
            <i class="fas fa-user-edit"></i>
            Edit Profile
        </h3>
        <p class="page-subtitle">Update your personal information and profile picture</p>
    </div>

    <!-- Messages -->
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <!-- Profile Form -->
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="profile-form-card">
            <!-- Left Column: Profile Picture -->
            <div class="profile-picture-section">
                <div class="profile-image-container">
                    <?php if (!empty($employee['profile_picture'])): ?>
                        <img src="../uploads/<?= htmlspecialchars($employee['profile_picture']) ?>" 
                             class="profile-image" 
                             alt="Profile Picture"
                             onerror="this.onerror=null; this.src='data:image/svg+xml;utf8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"200\" height=\"200\" viewBox=\"0 0 200 200\"><rect width=\"200\" height=\"200\" fill=\"%23714B67\"/><text x=\"50%\" y=\"50%\" font-family=\"Arial\" font-size=\"60\" fill=\"white\" text-anchor=\"middle\" dy=\".3em\"><?= substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1) ?></text></svg>'">
                    <?php else: ?>
                        <div class="profile-image-default">
                            <?= substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="profile-upload">
                    <input type="file" 
                           name="profile_picture" 
                           id="profile_picture" 
                           accept="image/*"
                           onchange="updateFileName(this)">
                    <label for="profile_picture" class="profile-upload-label">
                        <i class="fas fa-camera"></i>
                        Change Photo
                    </label>
                    <div class="file-info" id="fileInfo">
                        JPG, PNG up to 2MB
                    </div>
                </div>

                <!-- Employee Info -->
                <div class="employee-info">
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-id-card"></i>
                        </div>
                        <div>
                            <div class="info-label">Employee ID</div>
                            <div class="info-value"><?= htmlspecialchars($employee['emp_id']) ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <div class="info-label">Email</div>
                            <div class="info-value"><?= htmlspecialchars($_SESSION['email']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Form Fields -->
            <div class="form-section">
                <!-- First Name -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i>
                        First Name
                    </label>
                    <input type="text" 
                           name="first_name" 
                           class="form-control" 
                           value="<?= htmlspecialchars($employee['first_name']) ?>" 
                           required
                           placeholder="Enter your first name">
                </div>

                <!-- Last Name -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i>
                        Last Name
                    </label>
                    <input type="text" 
                           name="last_name" 
                           class="form-control" 
                           value="<?= htmlspecialchars($employee['last_name']) ?>" 
                           required
                           placeholder="Enter your last name">
                </div>

                <!-- Phone -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-phone"></i>
                        Phone Number
                    </label>
                    <input type="tel" 
                           name="phone" 
                           class="form-control" 
                           value="<?= htmlspecialchars($employee['phone']) ?>"
                           placeholder="Enter your phone number">
                </div>

                <!-- Address -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-home"></i>
                        Address
                    </label>
                    <textarea name="address" 
                              class="form-control form-textarea"
                              placeholder="Enter your complete address"><?= htmlspecialchars($employee['address']) ?></textarea>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i>
                        Update Profile
                    </button>
                    <a href="dashboard.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    // Update file name display
    function updateFileName(input) {
        const fileInfo = document.getElementById('fileInfo');
        if (input.files && input.files[0]) {
            const fileName = input.files[0].name;
            const fileSize = (input.files[0].size / 1024 / 1024).toFixed(2); // Convert to MB
            
            // Validate file size (max 2MB)
            if (fileSize > 2) {
                alert('File size must be less than 2MB');
                input.value = '';
                fileInfo.innerHTML = 'JPG, PNG up to 2MB';
                return;
            }
            
            // Validate file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!validTypes.includes(input.files[0].type)) {
                alert('Please select a valid image file (JPG, PNG, GIF)');
                input.value = '';
                fileInfo.innerHTML = 'JPG, PNG up to 2MB';
                return;
            }
            
            fileInfo.innerHTML = `<strong>${fileName}</strong> (${fileSize} MB)`;
            
            // Preview the image
            const reader = new FileReader();
            reader.onload = function(e) {
                const profileImage = document.querySelector('.profile-image');
                const profileDefault = document.querySelector('.profile-image-default');
                
                if (profileImage) {
                    profileImage.src = e.target.result;
                } else if (profileDefault) {
                    // Create new image element
                    const img = document.createElement('img');
                    img.className = 'profile-image';
                    img.src = e.target.result;
                    img.alt = 'Profile Preview';
                    profileDefault.parentNode.replaceChild(img, profileDefault);
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Character counter for address field
    document.addEventListener('DOMContentLoaded', function() {
        const addressTextarea = document.querySelector('textarea[name="address"]');
        if (addressTextarea) {
            const counter = document.createElement('div');
            counter.className = 'text-muted';
            counter.style.fontSize = '12px';
            counter.style.marginTop = '5px';
            counter.style.textAlign = 'right';
            addressTextarea.parentNode.appendChild(counter);
            
            function updateCounter() {
                const length = addressTextarea.value.length;
                counter.textContent = `${length}/500 characters`;
                if (length > 450) {
                    counter.style.color = '#f0ad4e';
                } else if (length > 490) {
                    counter.style.color = '#d9534f';
                } else {
                    counter.style.color = '#6c757d';
                }
            }
            
            addressTextarea.addEventListener('input', updateCounter);
            updateCounter();
        }
    });
</script>
</body>
</html>