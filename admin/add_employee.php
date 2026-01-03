<?php
require_once '../config/db.php';
require_once '../config/auth.php';

if (!in_array($_SESSION['role'], ['ADMIN', 'HR'])) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id     = $_POST['user_id'];
    $first_name  = trim($_POST['first_name']);
    $last_name   = trim($_POST['last_name']);
    $phone       = trim($_POST['phone']);
    $address     = trim($_POST['address']);
    $department  = trim($_POST['department']);
    $designation = trim($_POST['designation']);
    $joining_date = $_POST['joining_date'];

    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $profile_picture = uniqid() . "." . $ext;
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $profile_picture);
    }

    $stmt = $conn->prepare("
        INSERT INTO employees 
        (user_id, first_name, last_name, phone, address, department, designation, joining_date, profile_picture)
        VALUES 
        (:user_id, :first_name, :last_name, :phone, :address, :department, :designation, :joining_date, :profile_picture)
    ");

    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':first_name', $first_name);
    $stmt->bindParam(':last_name', $last_name);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':department', $department);
    $stmt->bindParam(':designation', $designation);
    $stmt->bindParam(':joining_date', $joining_date);
    $stmt->bindParam(':profile_picture', $profile_picture);

    if ($stmt->execute()) {
        $success = "Employee added successfully.";
    } else {
        $error = "Failed to add employee. Try again.";
    }
}

$users_stmt = $conn->prepare("
    SELECT u.user_id, u.employee_id, u.email 
    FROM users u 
    LEFT JOIN employees e ON u.user_id = e.user_id 
    WHERE e.user_id IS NULL
");
$users_stmt->execute();
$available_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Employee - Dayflow HRMS</title>

<link rel="stylesheet" href="../assets/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

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
        --odoo-radius: 12px;
        --odoo-radius-sm: 6px;
        --odoo-radius-lg: 16px;
    }

    /* ===== ODOO BODY STYLING ===== */
    body {
        background: var(--odoo-bg);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        color: #333;
        line-height: 1.6;
        margin: 0;
        padding: 0;
        min-height: 100vh;
    }

    /* ===== PAGE WRAPPER ===== */
    .page-wrapper {
        max-width: 1000px;
        margin: 50px auto;
        padding: 0 20px;
        animation: fadeIn 0.5s ease;
    }

    /* ===== FORM CARD ===== */
    .card-form {
        background: var(--odoo-card-bg);
        border-radius: var(--odoo-radius-lg);
        padding: 45px 50px;
        box-shadow: 0 15px 40px rgba(113, 75, 103, 0.12);
        border: 1px solid var(--odoo-border);
        position: relative;
        overflow: hidden;
    }

    .card-form::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: var(--odoo-gradient);
    }

    .card-form h3 {
        font-weight: 700;
        margin-bottom: 10px;
        color: var(--odoo-primary);
        font-size: 28px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .card-form h3 i {
        font-size: 32px;
        background: rgba(113, 75, 103, 0.1);
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--odoo-secondary);
    }

    .card-form p {
        color: #666;
        margin-bottom: 35px;
        font-size: 16px;
        line-height: 1.6;
        padding-left: 75px;
    }

    /* ===== FORM ELEMENTS ===== */
    .form-group {
        margin-bottom: 28px;
    }

    label {
        font-weight: 600;
        color: var(--odoo-primary);
        margin-bottom: 10px;
        display: block;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    label i {
        color: var(--odoo-secondary);
        font-size: 16px;
        width: 20px;
    }

    .form-control, select.form-control {
        border-radius: var(--odoo-radius-sm);
        padding: 14px 16px;
        border: 1px solid var(--odoo-border);
        font-size: 15px;
        transition: all 0.3s ease;
        background-color: var(--odoo-light);
    }

    .form-control:focus, select.form-control:focus {
        box-shadow: 0 0 0 3px rgba(113, 75, 103, 0.15);
        border-color: var(--odoo-secondary);
        background-color: white;
        transform: translateY(-1px);
    }

    select.form-control {
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23714B67' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 16px 12px;
    }

    textarea.form-control {
        resize: vertical;
        min-height: 100px;
    }

    /* ===== ROW LAYOUT ===== */
    .row {
        margin-left: -10px;
        margin-right: -10px;
    }

    .row > div {
        padding-left: 10px;
        padding-right: 10px;
    }

    /* ===== FILE UPLOAD ===== */
    .file-upload-wrapper {
        position: relative;
        overflow: hidden;
        margin-top: 8px;
    }

    .file-upload-wrapper input[type="file"] {
        position: absolute;
        width: 100%;
        height: 100%;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }

    .file-upload-label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        background: var(--odoo-gradient);
        color: white;
        padding: 14px 20px;
        border-radius: var(--odoo-radius-sm);
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }

    .file-upload-label:hover {
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

    /* ===== SUBMIT BUTTON ===== */
    .btn-submit {
        background: var(--odoo-gradient-success);
        border: none;
        border-radius: var(--odoo-radius-sm);
        padding: 16px 50px;
        font-weight: 600;
        transition: all 0.3s ease;
        color: white;
        font-size: 17px;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        min-width: 200px;
        justify-content: center;
        position: relative;
        overflow: hidden;
    }

    .btn-submit:hover {
        background: linear-gradient(135deg, #218838 0%, #1ba87e 100%);
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.25);
        color: white;
    }

    .btn-submit:active {
        transform: translateY(0);
        box-shadow: 0 3px 10px rgba(40, 167, 69, 0.2);
    }

    .btn-submit::after {
        content: '→';
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0;
        transition: all 0.3s ease;
    }

    .btn-submit:hover::after {
        opacity: 1;
        right: 15px;
    }

    /* ===== USER SELECT OPTIONS ===== */
    .user-option {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 12px 15px;
        border-radius: var(--odoo-radius-sm);
        border: 1px solid var(--odoo-border);
        margin-bottom: 8px;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .user-option:hover {
        background: rgba(113, 75, 103, 0.05);
        border-color: var(--odoo-secondary);
        transform: translateX(5px);
    }

    .user-option.selected {
        background: linear-gradient(135deg, rgba(113, 75, 103, 0.1) 0%, rgba(0, 160, 157, 0.05) 100%);
        border-color: var(--odoo-secondary);
    }

    .user-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--odoo-primary) 0%, var(--odoo-secondary) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 18px;
    }

    .user-info {
        flex: 1;
    }

    .user-email {
        font-weight: 600;
        color: var(--odoo-primary);
        margin-bottom: 3px;
    }

    .user-id {
        font-size: 12px;
        color: #666;
        font-family: 'Courier New', monospace;
    }

    /* ===== FORM HEADER INFO ===== */
    .form-header-info {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
        background: rgba(113, 75, 103, 0.05);
        padding: 20px;
        border-radius: var(--odoo-radius);
        border: 1px solid var(--odoo-border);
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .info-icon {
        color: var(--odoo-secondary);
        font-size: 20px;
        width: 30px;
    }

    .info-text {
        font-size: 14px;
    }

    .info-value {
        font-weight: 600;
        color: var(--odoo-primary);
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
        .page-wrapper {
            margin: 20px auto;
            padding: 0 15px;
        }
        
        .card-form {
            padding: 30px 25px;
        }
        
        .card-form h3 {
            font-size: 24px;
        }
        
        .card-form h3 i {
            width: 50px;
            height: 50px;
            font-size: 26px;
        }
        
        .card-form p {
            padding-left: 0;
            text-align: center;
        }
        
        .btn-submit {
            width: 100%;
        }
        
        .form-header-info {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 576px) {
        .card-form {
            padding: 25px 20px;
        }
        
        .row > div {
            margin-bottom: 15px;
        }
        
        .row > div:last-child {
            margin-bottom: 0;
        }
        
        .btn-submit {
            padding: 14px 20px;
        }
    }

    /* ===== UTILITY CLASSES ===== */
    .text-center {
        text-align: center !important;
    }

    .mt-4 {
        margin-top: 1.5rem !important;
    }

    .me-1 {
        margin-right: 0.25rem !important;
    }
</style>
</head>

<body>

<div class="page-wrapper">
<div class="card-form">

    <h3><i class="fas fa-user-plus"></i> Add New Employee</h3>
    <p>Create a complete employee profile and assign system details.</p>

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

    <!-- Form Header Info -->
    <div class="form-header-info">
        <div class="info-item">
            <div class="info-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="info-text">
                Available Users: <span class="info-value"><?= count($available_users) ?></span>
            </div>
        </div>
        <div class="info-item">
            <div class="info-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="info-text">
                Status: <span class="info-value">Creating New Employee</span>
            </div>
        </div>
        <div class="info-item">
            <div class="info-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="info-text">
                Today: <span class="info-value"><?= date('F d, Y') ?></span>
            </div>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data">

        <!-- User Selection -->
        <div class="form-group">
            <label><i class="fas fa-user-check"></i> Select User (Email)</label>
            <select name="user_id" class="form-control" required id="userSelect">
                <option value="">-- Select User to Assign as Employee --</option>
                <?php foreach ($available_users as $user): ?>
                    <option value="<?= $user['user_id'] ?>">
                        <?= htmlspecialchars($user['email']) ?> (ID: <?= htmlspecialchars($user['employee_id']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            
            <!-- User Preview -->
            <div id="userPreview" style="margin-top: 15px; display: none;">
                <div class="user-option selected">
                    <div class="user-icon" id="previewIcon">
                        ??
                    </div>
                    <div class="user-info">
                        <div class="user-email" id="previewEmail">Select a user to preview</div>
                        <div class="user-id" id="previewId">Employee ID: --</div>
                    </div>
                    <div style="color: var(--odoo-success);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal Information -->
        <h5 style="color: var(--odoo-primary); margin: 30px 0 20px 0; padding-bottom: 10px; border-bottom: 2px solid var(--odoo-border);">
            <i class="fas fa-user-circle"></i> Personal Information
        </h5>

        <div class="row">
            <div class="col-md-6 form-group">
                <label><i class="fas fa-user"></i> First Name</label>
                <input type="text" name="first_name" class="form-control" required 
                       placeholder="Enter first name">
            </div>

            <div class="col-md-6 form-group">
                <label><i class="fas fa-user"></i> Last Name</label>
                <input type="text" name="last_name" class="form-control" required 
                       placeholder="Enter last name">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 form-group">
                <label><i class="fas fa-phone"></i> Phone Number</label>
                <input type="text" name="phone" class="form-control" 
                       placeholder="Enter phone number">
            </div>

            <div class="col-md-6 form-group">
                <label><i class="fas fa-calendar-day"></i> Joining Date</label>
                <input type="date" name="joining_date" class="form-control" required 
                       value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <div class="form-group">
            <label><i class="fas fa-map-marker-alt"></i> Address</label>
            <textarea name="address" class="form-control" rows="3" 
                      placeholder="Enter complete address"></textarea>
        </div>

        <!-- Professional Information -->
        <h5 style="color: var(--odoo-primary); margin: 30px 0 20px 0; padding-bottom: 10px; border-bottom: 2px solid var(--odoo-border);">
            <i class="fas fa-briefcase"></i> Professional Information
        </h5>

        <div class="row">
            <div class="col-md-6 form-group">
                <label><i class="fas fa-building"></i> Department</label>
                <input type="text" name="department" class="form-control" 
                       placeholder="Enter department">
            </div>

            <div class="col-md-6 form-group">
                <label><i class="fas fa-user-tie"></i> Designation</label>
                <input type="text" name="designation" class="form-control" 
                       placeholder="Enter designation">
            </div>
        </div>

        <!-- Profile Picture -->
        <h5 style="color: var(--odoo-primary); margin: 30px 0 20px 0; padding-bottom: 10px; border-bottom: 2px solid var(--odoo-border);">
            <i class="fas fa-camera"></i> Profile Picture
        </h5>

        <div class="form-group">
            <label><i class="fas fa-image"></i> Upload Profile Picture</label>
            <div class="file-upload-wrapper">
                <input type="file" name="profile_picture" id="profile_picture" 
                       accept="image/*" onchange="updateFileName(this)">
                <label for="profile_picture" class="file-upload-label">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Choose Profile Picture
                </label>
                <div class="file-info" id="fileInfo">
                    Recommended: JPG, PNG up to 2MB
                </div>
            </div>
            
            <!-- Image Preview -->
            <div id="imagePreview" style="margin-top: 20px; text-align: center; display: none;">
                <div style="width: 150px; height: 150px; margin: 0 auto; border-radius: 50%; overflow: hidden; border: 3px solid var(--odoo-border);">
                    <img id="previewImage" src="" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                <p style="margin-top: 10px; color: #666; font-size: 14px;">Profile Picture Preview</p>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="text-center mt-4">
            <button type="submit" class="btn-submit">
                <i class="fas fa-user-plus"></i>
                Add Employee
            </button>
        </div>

    </form>

</div>
</div>

<script>
    // User selection preview
    document.getElementById('userSelect').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const userPreview = document.getElementById('userPreview');
        const previewIcon = document.getElementById('previewIcon');
        const previewEmail = document.getElementById('previewEmail');
        const previewId = document.getElementById('previewId');
        
        if (this.value) {
            const email = selectedOption.text.split('(')[0].trim();
            const id = selectedOption.text.match(/ID:\s*(\w+)/)?.[1] || '--';
            const initials = email.split('@')[0].substring(0, 2).toUpperCase();
            
            previewIcon.textContent = initials;
            previewEmail.textContent = email;
            previewId.textContent = `Employee ID: ${id}`;
            userPreview.style.display = 'block';
        } else {
            userPreview.style.display = 'none';
        }
    });

    // File upload preview
    function updateFileName(input) {
        const fileInfo = document.getElementById('fileInfo');
        const imagePreview = document.getElementById('imagePreview');
        const previewImage = document.getElementById('previewImage');
        
        if (input.files && input.files[0]) {
            const fileName = input.files[0].name;
            const fileSize = (input.files[0].size / 1024 / 1024).toFixed(2);
            
            // Validate file size (max 2MB)
            if (fileSize > 2) {
                alert('File size must be less than 2MB');
                input.value = '';
                fileInfo.innerHTML = 'Recommended: JPG, PNG up to 2MB';
                imagePreview.style.display = 'none';
                return;
            }
            
            // Validate file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            if (!validTypes.includes(input.files[0].type)) {
                alert('Please select a valid image file (JPG, PNG, GIF)');
                input.value = '';
                fileInfo.innerHTML = 'Recommended: JPG, PNG up to 2MB';
                imagePreview.style.display = 'none';
                return;
            }
            
            fileInfo.innerHTML = `<strong>${fileName}</strong> (${fileSize} MB)`;
            
            // Preview the image
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                imagePreview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            imagePreview.style.display = 'none';
            fileInfo.innerHTML = 'Recommended: JPG, PNG up to 2MB';
        }
    }

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const userSelect = document.getElementById('userSelect');
        const firstName = document.querySelector('input[name="first_name"]');
        const lastName = document.querySelector('input[name="last_name"]');
        const joiningDate = document.querySelector('input[name="joining_date"]');
        
        let isValid = true;
        let errorMessage = '';
        
        if (!userSelect.value) {
            isValid = false;
            errorMessage = 'Please select a user to assign as employee.';
        } else if (!firstName.value.trim()) {
            isValid = false;
            errorMessage = 'First name is required.';
        } else if (!lastName.value.trim()) {
            isValid = false;
            errorMessage = 'Last name is required.';
        } else if (!joiningDate.value) {
            isValid = false;
            errorMessage = 'Joining date is required.';
        }
        
        if (!isValid) {
            e.preventDefault();
            alert(errorMessage);
        }
    });

    // Initialize date input with today's date
    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.querySelector('input[name="joining_date"]');
        if (dateInput && !dateInput.value) {
            dateInput.valueAsDate = new Date();
        }
        
        // Add max date validation (cannot be in the future)
        dateInput.max = new Date().toISOString().split('T')[0];
        
        // Add department suggestions
        const departmentInput = document.querySelector('input[name="department"]');
        if (departmentInput) {
            const departments = ['Human Resources', 'Information Technology', 'Finance', 'Marketing', 'Sales', 'Operations', 'Customer Service'];
            departmentInput.setAttribute('list', 'departmentSuggestions');
            const datalist = document.createElement('datalist');
            datalist.id = 'departmentSuggestions';
            departments.forEach(dept => {
                const option = document.createElement('option');
                option.value = dept;
                datalist.appendChild(option);
            });
            departmentInput.parentNode.appendChild(datalist);
        }
        
        // Add designation suggestions
        const designationInput = document.querySelector('input[name="designation"]');
        if (designationInput) {
            const designations = ['Manager', 'Senior Developer', 'Developer', 'Analyst', 'Coordinator', 'Executive', 'Assistant'];
            designationInput.setAttribute('list', 'designationSuggestions');
            const datalist = document.createElement('datalist');
            datalist.id = 'designationSuggestions';
            designations.forEach(designation => {
                const option = document.createElement('option');
                option.value = designation;
                datalist.appendChild(option);
            });
            designationInput.parentNode.appendChild(datalist);
        }
    });
</script>
</body>
</html>