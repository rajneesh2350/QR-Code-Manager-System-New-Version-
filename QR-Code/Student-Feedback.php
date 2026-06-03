<?php
// student_registration.php - Professional Student Registration with MySQL Database

require_once 'config.php';

// Create students table if not exists
$students_table_sql = "CREATE TABLE IF NOT EXISTS `students` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `registration_number` VARCHAR(20) UNIQUE NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `whatsapp` VARCHAR(20) NOT NULL,
    `course_id` INT(11) NOT NULL,
    `course_name` VARCHAR(100) NOT NULL,
    `semester` INT(11) DEFAULT 1,
    `remarks` TEXT,
    `image_path` VARCHAR(500),
    `image_url` VARCHAR(500),
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `submission_id` VARCHAR(50) UNIQUE,
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_email` (`email`),
    INDEX `idx_registration_number` (`registration_number`),
    INDEX `idx_course_id` (`course_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$conn->query($students_table_sql)) {
    error_log("Error creating students table: " . $conn->error);
}

// Handle the AJAX request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    $response = ['status' => 'error', 'message' => 'Unknown error occurred'];

    // Validation
    $errors = [];

    // Full Name validation
    $full_name = trim($_POST['full_name'] ?? '');
    if (empty($full_name)) {
        $errors['full_name'] = 'Full name is required';
    } elseif (strlen($full_name) < 3) {
        $errors['full_name'] = 'Full name must be at least 3 characters';
    } elseif (strlen($full_name) > 100) {
        $errors['full_name'] = 'Full name must not exceed 100 characters';
    } elseif (!preg_match('/^[a-zA-Z\s\.\-\']+$/', $full_name)) {
        $errors['full_name'] = 'Full name can only contain letters, spaces, dots, hyphens, and apostrophes';
    }

    // Email validation
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $errors['email'] = 'Email address is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } elseif (strlen($email) > 100) {
        $errors['email'] = 'Email must not exceed 100 characters';
    } else {
        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT id FROM students WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            $errors['email'] = 'This email is already registered';
        }
        $check_stmt->close();
    }

    // WhatsApp validation
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    if (empty($whatsapp)) {
        $errors['whatsapp'] = 'WhatsApp number is required';
    } elseif (!preg_match('/^[\+\d][\d\s\-\(\)]{8,20}$/', $whatsapp)) {
        $errors['whatsapp'] = 'Please enter a valid WhatsApp number (10-15 digits with optional country code)';
    } elseif (strlen($whatsapp) > 20) {
        $errors['whatsapp'] = 'WhatsApp number must not exceed 20 characters';
    }

    // Course validation
    $course_id = intval($_POST['course_id'] ?? 0);
    $course_name = '';
    $semester = 1;
    if ($course_id <= 0) {
        $errors['course'] = 'Please select a valid course';
    } else {
        // Verify course exists in twl_course table
        $course_stmt = $conn->prepare("SELECT course_name, semesters FROM twl_course WHERE id = ?");
        $course_stmt->bind_param("i", $course_id);
        $course_stmt->execute();
        $course_stmt->bind_result($course_name, $semester);
        if (!$course_stmt->fetch()) {
            $errors['course'] = 'Selected course is invalid';
        }
        $course_stmt->close();
    }

    // Semester validation
    $semester = intval($_POST['semester'] ?? 1);
    if ($semester < 1 || $semester > 8) {
        $semester = 1;
    }

    // Remarks validation (optional)
    $remarks = trim($_POST['remarks'] ?? '');
    if (strlen($remarks) > 1000) {
        $errors['remarks'] = 'Remarks must not exceed 1000 characters';
    }

    // Image upload handling
    $image_path = '';
    $image_url = '';
    $uploadDir = 'uploads/';

    if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] == 0) {
        // Create uploads directory if not exists
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file = $_FILES['student_image'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB

        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedTypes)) {
            $errors['image'] = 'Only JPG, PNG, GIF, and WEBP images are allowed';
        } elseif ($file['size'] > $maxFileSize) {
            $errors['image'] = 'Image size must be less than 5MB';
        } else {
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'student_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $uploadPath = $uploadDir . $filename;

            // Compress and save image
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $image_path = $uploadPath;
                $image_url = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/' . $uploadPath;

                // Compress image if it's large
                if ($file['size'] > 1024 * 1024) { // If > 1MB
                    compressImage($uploadPath, $uploadPath, 80);
                }
            } else {
                $errors['image'] = 'Failed to upload image';
            }
        }
    }

    // If there are validation errors, return them
    if (!empty($errors)) {
        $response['status'] = 'error';
        $response['message'] = 'Please fix the following errors';
        $response['errors'] = $errors;
        echo json_encode($response);
        exit;
    }

    // Generate unique registration number and submission ID
    $registration_number = 'IGIPESS/' . date('Y') . '/' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $submission_id = 'SUB_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));

    // Get IP address and user agent
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    // Insert into database
    $insert_sql = "INSERT INTO students (registration_number, full_name, email, whatsapp, course_id, course_name, semester, remarks, image_path, image_url, ip_address, user_agent, submission_id, status)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ssssissssssss",
        $registration_number, $full_name, $email, $whatsapp,
        $course_id, $course_name, $semester, $remarks,
        $image_path, $image_url, $ip_address, $user_agent, $submission_id
    );

    if ($stmt->execute()) {
        $student_id = $stmt->insert_id;
        $response['status'] = 'success';
        $response['message'] = 'Registration submitted successfully!';
        $response['registration_number'] = $registration_number;
        $response['submission_id'] = $submission_id;
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Database error: ' . $conn->error;
    }

    $stmt->close();
    echo json_encode($response);
    exit;
}

// Function to compress image
function compressImage($source, $destination, $quality) {
    $info = getimagesize($source);
    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
        imagejpeg($image, $destination, $quality);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
        imagepng($image, $destination, 8);
    } elseif ($info['mime'] == 'image/webp') {
        $image = imagecreatefromwebp($source);
        imagewebp($image, $destination, $quality);
    } elseif ($info['mime'] == 'image/gif') {
        $image = imagecreatefromgif($source);
        imagegif($image, $destination);
    }
    imagedestroy($image);
    return true;
}

// Fetch courses from twl_course table
$courses = [];
$course_query = "SELECT id, course_name, semesters FROM twl_course ORDER BY course_name";
$course_result = $conn->query($course_query);
if ($course_result && $course_result->num_rows > 0) {
    while ($row = $course_result->fetch_assoc()) {
        $courses[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>IGIPESS - Student Registration System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            min-height: 100vh;
        }

        .form-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            overflow: hidden;
            position: relative;
        }

        /* Brand Header */
        .brand-header {
            background: linear-gradient(135deg, #1a237e, #0d47a1);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .brand-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.05)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,154.7C960,171,1056,181,1152,165.3C1248,149,1344,107,1392,85.3L1440,64L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom;
            background-size: cover;
            opacity: 0.1;
        }

        .brand-header img {
            width: 90px;
            height: auto;
            background: white;
            border-radius: 50%;
            padding: 8px;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }

        .brand-header h2 {
            font-size: 22px;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }

        .brand-header p {
            font-size: 14px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        /* Form Body */
        .form-body {
            padding: 35px;
        }

        .info-banner {
            background: #e3f2fd;
            border-left: 4px solid #1e88e5;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #1565c0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-banner i {
            font-size: 20px;
        }

        .section-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            transition: all 0.3s ease;
        }

        .section-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #1e88e5;
            font-size: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }

        .required-star {
            color: #ef4444;
            margin-left: 3px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
            pointer-events: none;
        }

        .input-wrapper textarea ~ i {
            top: 18px;
            transform: none;
        }

        .form-control {
            width: 100%;
            padding: 12px 12px 12px 42px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        textarea.form-control {
            padding: 12px 12px 12px 42px;
            resize: vertical;
            min-height: 90px;
        }

        .form-control:focus {
            outline: none;
            border-color: #1e88e5;
            box-shadow: 0 0 0 3px rgba(30, 136, 229, 0.1);
        }

        .form-control.error {
            border-color: #ef4444;
            background-color: #fef2f2;
        }

        .error-message {
            color: #ef4444;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }

        .help-text {
            font-size: 12px;
            color: #64748b;
            margin-top: 6px;
            display: block;
        }

        /* Image Upload Styles */
        .image-upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f1f5f9;
        }

        .image-upload-area:hover {
            border-color: #1e88e5;
            background: #e3f2fd;
        }

        .image-upload-area i {
            font-size: 48px;
            color: #94a3b8;
            margin-bottom: 10px;
        }

        .image-upload-area p {
            color: #64748b;
            font-size: 14px;
        }

        .image-preview {
            margin-top: 15px;
            text-align: center;
            display: none;
        }

        .image-preview img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 4px;
            background: white;
        }

        .btn-remove-image {
            background: #ef4444;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            margin-top: 8px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #1e88e5, #1565c0);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(30, 136, 229, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Loading Overlay */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.97);
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #e2e8f0;
            border-top-color: #1e88e5;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            margin-top: 20px;
            color: #334155;
            font-weight: 500;
        }

        .progress-container {
            width: 250px;
            background: #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            margin-top: 15px;
        }

        .progress-bar {
            width: 0%;
            height: 6px;
            background: linear-gradient(90deg, #1e88e5, #43a047);
            transition: width 0.3s ease;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-body {
                padding: 20px;
            }
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .section-card {
                padding: 18px;
            }
            .brand-header h2 {
                font-size: 18px;
            }
        }

        /* Success Animation */
        @keyframes checkmark {
            0% { transform: scale(0); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body>

<div class="form-container">
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text" id="loadingText">Processing your registration...</div>
        <div class="progress-container">
            <div class="progress-bar" id="progressBar"></div>
        </div>
    </div>

    <div class="brand-header">
        <img src="https://igipess.du.ac.in/images/igipesslogo1.png" alt="IGIPESS Logo" onerror="this.src='https://placehold.co/90x90?text=IGIPESS'">
        <h2>Indira Gandhi Institute of Physical Education and Sports Sciences</h2>
        <p>Student Registration System | University of Delhi</p>
    </div>

    <div class="form-body">
        <div class="info-banner">
            <i class="fas fa-info-circle"></i>
            <span>All fields marked with <strong style="color:#ef4444">*</strong> are mandatory. Your information is secure with us.</span>
        </div>

        <form id="studentForm" enctype="multipart/form-data">
            <!-- Personal Details Section -->
            <div class="section-card">
                <div class="section-title">
                    <i class="fas fa-user-graduate"></i>
                    <span>1. Personal Details</span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name <span class="required-star">*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" name="full_name" id="full_name" class="form-control" placeholder="Enter your full name" autocomplete="off">
                        </div>
                        <span class="error-message" id="error-full_name"></span>
                    </div>

                    <div class="form-group">
                        <label>Email Address <span class="required-star">*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" id="email" class="form-control" placeholder="your.email@example.com" autocomplete="off">
                        </div>
                        <span class="error-message" id="error-email"></span>
                        <div class="help-text"><i class="fas fa-lock"></i> We'll never share your email</div>
                    </div>

                    <div class="form-group">
                        <label>WhatsApp Number <span class="required-star">*</span></label>
                        <div class="input-wrapper">
                            <i class="fab fa-whatsapp"></i>
                            <input type="tel" name="whatsapp" id="whatsapp" class="form-control" placeholder="+91 9876543210" autocomplete="off">
                        </div>
                        <span class="error-message" id="error-whatsapp"></span>
                        <div class="help-text">Include country code (e.g., +91 for India)</div>
                    </div>

                    <div class="form-group">
                        <label>Select Course <span class="required-star">*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-graduation-cap"></i>
                            <select name="course_id" id="course_id" class="form-control">
                                <option value="">-- Select Course --</option>
                                <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>" data-semesters="<?php echo $course['semesters']; ?>">
                                    <?php echo htmlspecialchars($course['course_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <span class="error-message" id="error-course"></span>
                    </div>
                </div>
            </div>

            <!-- Academic Information Section -->
            <div class="section-card">
                <div class="section-title">
                    <i class="fas fa-chalkboard-user"></i>
                    <span>2. Academic Information</span>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Current Semester <span class="required-star">*</span></label>
                        <div class="input-wrapper">
                            <i class="fas fa-layer-group"></i>
                            <select name="semester" id="semester" class="form-control">
                                <option value="1">Semester 1</option>
                                <option value="2">Semester 2</option>
                                <option value="3">Semester 3</option>
                                <option value="4">Semester 4</option>
                                <option value="5">Semester 5</option>
                                <option value="6">Semester 6</option>
                                <option value="7">Semester 7</option>
                                <option value="8">Semester 8</option>
                            </select>
                        </div>
                        <span class="error-message" id="error-semester"></span>
                    </div>

                    <div class="form-group">
                        <label>Registration Year</label>
                        <div class="input-wrapper">
                            <i class="fas fa-calendar-alt"></i>
                            <input type="text" class="form-control" value="<?php echo date('Y'); ?>" disabled style="background:#f1f5f9">
                        </div>
                        <div class="help-text">Registration year will be <?php echo date('Y'); ?></div>
                    </div>
                </div>
            </div>

            <!-- Photo Upload Section -->
            <div class="section-card">
                <div class="section-title">
                    <i class="fas fa-camera-retro"></i>
                    <span>3. Student Photo</span>
                </div>

                <div class="form-group">
                    <label>Upload Passport Size Photo</label>
                    <div class="image-upload-area" id="imageUploadArea">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click or drag to upload photo</p>
                        <p style="font-size: 11px; margin-top: 5px;">JPG, PNG, GIF, WEBP (Max 5MB)</p>
                        <input type="file" name="student_image" id="student_image" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                    </div>
                    <div class="image-preview" id="imagePreview">
                        <img id="previewImg" src="" alt="Preview">
                        <button type="button" class="btn-remove-image" id="removeImageBtn">
                            <i class="fas fa-trash-alt"></i> Remove Photo
                        </button>
                    </div>
                    <span class="error-message" id="error-image"></span>
                    <div class="help-text"><i class="fas fa-info-circle"></i> Upload a clear, recent passport-size photograph</div>
                </div>
            </div>

            <!-- Feedback Section -->
            <div class="section-card">
                <div class="section-title">
                    <i class="fas fa-comment-dots"></i>
                    <span>4. Additional Information</span>
                </div>

                <div class="form-group">
                    <label>Remarks / Suggestions (Optional)</label>
                    <div class="input-wrapper">
                        <i class="fas fa-pen-fancy"></i>
                        <textarea name="remarks" id="remarks" class="form-control" placeholder="Any additional information, feedback, or suggestions..."></textarea>
                    </div>
                    <span class="error-message" id="error-remarks"></span>
                    <div class="help-text">Your feedback helps us improve our programs</div>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="fas fa-paper-plane"></i> Submit Registration
            </button>
        </form>
    </div>
</div>

<script>
// DOM Elements
const form = document.getElementById('studentForm');
const loadingOverlay = document.getElementById('loadingOverlay');
const progressBar = document.getElementById('progressBar');
const loadingText = document.getElementById('loadingText');
const submitBtn = document.getElementById('submitBtn');

// Image upload elements
const imageUploadArea = document.getElementById('imageUploadArea');
const imageInput = document.getElementById('student_image');
const imagePreview = document.getElementById('imagePreview');
const previewImg = document.getElementById('previewImg');
const removeImageBtn = document.getElementById('removeImageBtn');

// Course semester updater
const courseSelect = document.getElementById('course_id');
const semesterSelect = document.getElementById('semester');

courseSelect.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const maxSemesters = selectedOption.getAttribute('data-semesters');

    if (maxSemesters) {
        // Clear existing options
        semesterSelect.innerHTML = '';
        for (let i = 1; i <= maxSemesters; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = `Semester ${i}`;
            semesterSelect.appendChild(option);
        }
    }
});

// Image upload area click
imageUploadArea.addEventListener('click', () => {
    imageInput.click();
});

// Image input change
imageInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validate file size
        if (file.size > 5 * 1024 * 1024) {
            showFieldError('image', 'File size must be less than 5MB');
            this.value = '';
            return;
        }

        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            showFieldError('image', 'Only JPG, PNG, GIF, and WEBP images are allowed');
            this.value = '';
            return;
        }

        clearFieldError('image');

        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            imagePreview.style.display = 'block';
            imageUploadArea.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
});

// Remove image
removeImageBtn.addEventListener('click', () => {
    imageInput.value = '';
    imagePreview.style.display = 'none';
    imageUploadArea.style.display = 'block';
    previewImg.src = '';
});

// Helper functions for error display
function showFieldError(field, message) {
    const errorSpan = document.getElementById(`error-${field}`);
    if (errorSpan) {
        errorSpan.textContent = message;
        const input = document.getElementById(field);
        if (input) input.classList.add('error');
    }
}

function clearFieldError(field) {
    const errorSpan = document.getElementById(`error-${field}`);
    if (errorSpan) {
        errorSpan.textContent = '';
        const input = document.getElementById(field);
        if (input) input.classList.remove('error');
    }
}

function clearAllErrors() {
    const errorSpans = document.querySelectorAll('.error-message');
    errorSpans.forEach(span => span.textContent = '');
    const inputs = document.querySelectorAll('.form-control');
    inputs.forEach(input => input.classList.remove('error'));
}

// Real-time validation
const fullNameInput = document.getElementById('full_name');
const emailInput = document.getElementById('email');
const whatsappInput = document.getElementById('whatsapp');

fullNameInput.addEventListener('input', function() {
    const value = this.value.trim();
    if (value.length > 0 && value.length < 3) {
        showFieldError('full_name', 'Name must be at least 3 characters');
    } else if (value.length > 100) {
        showFieldError('full_name', 'Name must not exceed 100 characters');
    } else if (value && !/^[a-zA-Z\s\.\-\']+$/.test(value)) {
        showFieldError('full_name', 'Only letters, spaces, dots, hyphens allowed');
    } else {
        clearFieldError('full_name');
    }
});

emailInput.addEventListener('blur', function() {
    const value = this.value.trim();
    if (value && !/^[^\s@]+@([^\s@.,]+\.)+[^\s@.,]{2,}$/.test(value)) {
        showFieldError('email', 'Please enter a valid email address');
    } else {
        clearFieldError('email');
    }
});

whatsappInput.addEventListener('input', function() {
    const value = this.value.trim();
    if (value && !/^[\+\d][\d\s\-\(\)]{8,20}$/.test(value)) {
        showFieldError('whatsapp', 'Enter a valid WhatsApp number (10-15 digits)');
    } else if (value && value.length > 20) {
        showFieldError('whatsapp', 'Number must not exceed 20 characters');
    } else {
        clearFieldError('whatsapp');
    }
});

// Form submission
form.addEventListener('submit', async function(e) {
    e.preventDefault();

    clearAllErrors();

    // Basic validation before submission
    let hasError = false;

    const fullName = fullNameInput.value.trim();
    if (!fullName) {
        showFieldError('full_name', 'Full name is required');
        hasError = true;
    } else if (fullName.length < 3) {
        showFieldError('full_name', 'Name must be at least 3 characters');
        hasError = true;
    }

    const email = emailInput.value.trim();
    if (!email) {
        showFieldError('email', 'Email is required');
        hasError = true;
    } else if (!/^[^\s@]+@([^\s@.,]+\.)+[^\s@.,]{2,}$/.test(email)) {
        showFieldError('email', 'Valid email required');
        hasError = true;
    }

    const whatsapp = whatsappInput.value.trim();
    if (!whatsapp) {
        showFieldError('whatsapp', 'WhatsApp number is required');
        hasError = true;
    } else if (!/^[\+\d][\d\s\-\(\)]{8,20}$/.test(whatsapp)) {
        showFieldError('whatsapp', 'Valid WhatsApp number required');
        hasError = true;
    }

    const courseId = courseSelect.value;
    if (!courseId) {
        showFieldError('course', 'Please select a course');
        hasError = true;
    }

    if (hasError) {
        Swal.fire({
            title: 'Validation Error',
            text: 'Please fix the errors in the form before submitting.',
            icon: 'error',
            confirmButtonColor: '#1e88e5'
        });
        return;
    }

    // Show loading overlay with progress animation
    loadingOverlay.style.display = 'flex';
    progressBar.style.width = '0%';

    let progress = 0;
    const progressInterval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 90) progress = 90;
        progressBar.style.width = progress + '%';

        if (progress < 30) {
            loadingText.textContent = 'Validating information...';
        } else if (progress < 60) {
            loadingText.textContent = 'Uploading photo...';
        } else {
            loadingText.textContent = 'Saving to database...';
        }
    }, 300);

    const formData = new FormData(form);
    formData.append('ajax', '1');

    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        clearInterval(progressInterval);
        progressBar.style.width = '100%';
        loadingText.textContent = 'Complete!';

        setTimeout(() => {
            loadingOverlay.style.display = 'none';

            if (data.status === 'success') {
                Swal.fire({
                    title: 'Registration Successful!',
                    html: `
                        <div style="text-align: left">
                            <p>${data.message}</p>
                            <hr style="margin: 15px 0">
                            <p><strong>Registration Number:</strong><br>${data.registration_number}</p>
                            <p><strong>Submission ID:</strong><br>${data.submission_id}</p>
                            <p style="font-size: 12px; color: #666; margin-top: 10px">Please save these details for future reference.</p>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonColor: '#1e88e5',
                    confirmButtonText: 'OK'
                }).then(() => {
                    form.reset();
                    imagePreview.style.display = 'none';
                    imageUploadArea.style.display = 'block';
                    previewImg.src = '';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            } else {
                let errorMessage = data.message || 'Submission failed. Please try again.';
                if (data.errors) {
                    errorMessage = Object.values(data.errors).join('<br>');
                    // Display field-specific errors
                    for (const [field, message] of Object.entries(data.errors)) {
                        showFieldError(field, message);
                    }
                }
                Swal.fire({
                    title: 'Submission Failed',
                    html: errorMessage,
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            }
        }, 500);

    } catch (error) {
        clearInterval(progressInterval);
        loadingOverlay.style.display = 'none';
        console.error('Fetch error:', error);
        Swal.fire({
            title: 'Network Error',
            text: 'Could not connect to server. Please check your internet connection.',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
    }
});

// Prevent accidental form submission on enter
document.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
    }
});
</script>
</body>
</html>