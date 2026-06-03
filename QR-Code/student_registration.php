<?php
// student_registration.php - With Image Upload

// Handle the AJAX request to Google Sheets
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    $googleWebAppUrl = 'https://script.google.com/macros/s/AKfycbwFNN-TDDg2eQZ80cFAyhZhV1S_SzjhYBJpk3w-LRPXKBrRkeoCmdeRD2Ub8L_ZE0W0/exec';

    // Handle file upload
    $imageUrl = '';
    if (isset($_FILES['student_image']) && $_FILES['student_image']['error'] == 0) {
        $uploadDir = 'uploads/';

        // Create uploads directory if not exists
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Generate unique filename
        $extension = pathinfo($_FILES['student_image']['name'], PATHINFO_EXTENSION);
        $filename = 'student_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $uploadPath = $uploadDir . $filename;

        // Move uploaded file
        if (move_uploaded_file($_FILES['student_image']['tmp_name'], $uploadPath)) {
            $imageUrl = 'https://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/' . $uploadPath;
        }
    }

    // Get form data with additional fields
    $formData = [
        'full_name' => $_POST['full_name'] ?? '',
        'email' => $_POST['email'] ?? '',
        'whatsapp' => $_POST['whatsapp'] ?? '',
        'course' => $_POST['course'] ?? '',
        'remarks' => $_POST['remarks'] ?? '',
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'submission_id' => 'SUB_' . time() . '_' . rand(1000, 9999),
        'image_url' => $imageUrl  // Add image URL to Google Sheet
    ];

    // Send to Google Sheets using cURL
    $ch = curl_init($googleWebAppUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($formData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo json_encode(['status' => 'error', 'message' => 'CURL Error: ' . $curlError]);
    } elseif ($httpCode == 200) {
        echo json_encode(['status' => 'success', 'message' => 'Registration submitted successfully with image']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'HTTP Error: ' . $httpCode]);
    }
    exit;
}

$courses = [
    ['course_name' => 'BPED'],
    ['course_name' => 'BSc (PE,HE&S)'],
    ['course_name' => 'MPED'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IGIPESS - Student Registration with Photo</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Previous styles remain the same, add these new styles */
        .image-preview {
            margin-top: 10px;
            text-align: center;
        }
        .image-preview img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            padding: 3px;
        }
        .camera-preview {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .camera-preview video, .camera-preview canvas {
            width: 90%;
            max-width: 500px;
            border-radius: 10px;
        }
        .camera-controls {
            margin-top: 20px;
            display: flex;
            gap: 15px;
        }
        .btn-camera {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-capture {
            background: #10b981;
        }
        .btn-close {
            background: #ef4444;
        }
        .file-info {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
        }
    </style>
        <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            margin: 0;
        }
        .form-container {
            background: white;
            padding: 0;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 800px;
            position: relative;
            overflow: hidden;
            margin-bottom: 40px;
        }
        .brand-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: white;
            padding: 30px 20px;
            text-align: center;
            border-bottom: 4px solid #3b82f6;
        }
        .brand-header img {
            max-width: 90px;
            height: auto;
            margin-bottom: 15px;
            background: white;
            padding: 5px;
            border-radius: 50%;
        }
        .brand-header h2 {
            margin: 0 0 10px 0;
            font-size: 22px;
            line-height: 1.4;
        }
        .brand-header p {
            margin: 0;
            font-size: 14px;
            color: #cbd5e1;
            line-height: 1.5;
        }
        .form-body {
            padding: 30px;
        }
        .section-card {
            background: #fcfcfc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 18px;
            color: #3b82f6;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #475569;
            font-size: 15px;
        }
        .help-text {
            font-size: 12px;
            color: #64748b;
            margin-top: 5px;
            display: block;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 15px;
            transition: all 0.3s;
            background-color: #fff;
            outline: none;
        }
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        textarea.form-control {
            min-height: 80px;
            resize: vertical;
        }
        .input-icon-wrapper {
            position: relative;
        }
        .input-icon-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 16px;
        }
        .input-icon-wrapper .form-control {
            padding-left: 40px;
        }
        .btn-submit {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 16px;
            width: 100%;
            border-radius: 6px;
            font-weight: bold;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        .btn-submit:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.95);
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 10;
        }
        .progress-container {
            width: 80%;
            background-color: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 15px;
            height: 12px;
        }
        .progress-bar {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            border-radius: 10px;
            transition: width 0.4s ease;
        }
        .required-star {
            color: #ef4444;
        }
        .info-banner {
            background: #eef2ff;
            border-radius: 8px;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-left: 4px solid #3b82f6;
            font-size: 13px;
            color: #475569;
        }
    </style>
</head>
<body>

<div class="form-container">
    <div class="loading-overlay" id="loadingOverlay">
        <div class="progress-container">
            <div class="progress-bar" id="progressBar"></div>
        </div>
        <div style="color: #475569; font-weight: 600;" id="progressText">Submitting registration...</div>
    </div>

    <div class="brand-header">
        <img src="https://igipess.du.ac.in/images/igipesslogo1.png" alt="IGIPESS Logo">
        <h2>Indira Gandhi Institute of Physical Education and Sports Sciences</h2>
        <p>Student Registration & Feedback Form with Photo</p>
    </div>

    <div class="form-body">
        <div class="info-banner">
            <i class="fas fa-info-circle"></i> All fields marked with <span class="required-star">*</span> are mandatory.
        </div>

        <form id="studentForm" enctype="multipart/form-data">
            <div class="section-card">
                <div class="section-title">
                    <i class="fas fa-user-circle"></i> 1. Personal Details
                </div>

                <div class="form-group">
                    <label>Full Name <span class="required-star">*</span></label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="full_name" class="form-control" placeholder="Enter your full name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email Address <span class="required-star">*</span></label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" class="form-control" placeholder="your.email@example.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>WhatsApp Number <span class="required-star">*</span></label>
                    <div class="input-icon-wrapper">
                        <i class="fab fa-whatsapp"></i>
                        <input type="text" name="whatsapp" class="form-control" placeholder="Enter WhatsApp number" required>
                    </div>
                    <div class="help-text">Include country code (e.g., +91 9876543210)</div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title">
                    <i class="fas fa-graduation-cap"></i> 2. Academic Information
                </div>

                <div class="form-group">
                    <label>Select Course <span class="required-star">*</span></label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-book"></i>
                        <select name="course" class="form-control" required>
                            <option value="" disabled selected>Select your course...</option>
                            <?php foreach($courses as $course): ?>
                            <option value="<?php echo htmlspecialchars($course['course_name']); ?>">
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title">
                    <i class="fas fa-camera"></i> 3. Student Photo
                </div>

                <div class="form-group">
                    <label>Upload Student Photo</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-image"></i>
                        <input type="file" name="student_image" id="student_image" class="form-control" accept="image/*" capture="environment">
                    </div>
                    <div class="help-text">
                        <i class="fas fa-info-circle"></i> Upload a recent passport-size photo (JPG, PNG - Max 5MB)
                    </div>

                    <!-- Image Preview -->
                    <div class="image-preview" id="imagePreview" style="display: none;">
                        <img id="previewImg" src="" alt="Preview">
                        <button type="button" class="btn-submit" id="removeImage" style="margin-top: 10px; padding: 8px; font-size: 14px;">
                            <i class="fas fa-trash"></i> Remove Photo
                        </button>
                    </div>
                </div>
            </div>

            <div class="section-card">
                <div class="section-title">
                    <i class="fas fa-comment-dots"></i> 4. Feedback & Remarks
                </div>

                <div class="form-group">
                    <label>Additional Remarks / Suggestions</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-pen"></i>
                        <textarea name="remarks" class="form-control" placeholder="Please share any feedback, suggestions, or special requests..."></textarea>
                    </div>
                    <div class="help-text">Your feedback helps us improve our programs</div>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Submit Registration with Photo
            </button>
        </form>
    </div>
</div>

<script>
// Image preview functionality
const imageInput = document.getElementById('student_image');
const imagePreview = document.getElementById('imagePreview');
const previewImg = document.getElementById('previewImg');
const removeImageBtn = document.getElementById('removeImage');

imageInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            Swal.fire('Error', 'File size must be less than 5MB', 'error');
            this.value = '';
            imagePreview.style.display = 'none';
            return;
        }

        // Validate file type
        if (!file.type.match('image.*')) {
            Swal.fire('Error', 'Please select an image file (JPG, PNG)', 'error');
            this.value = '';
            imagePreview.style.display = 'none';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            imagePreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

removeImageBtn.addEventListener('click', function() {
    imageInput.value = '';
    imagePreview.style.display = 'none';
    previewImg.src = '';
});

// Form submission with file upload
document.getElementById('studentForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const overlay = document.getElementById('loadingOverlay');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    overlay.style.display = 'flex';
    progressBar.style.width = '0%';

    let progress = 0;
    let progressInterval = setInterval(() => {
        progress += Math.floor(Math.random() * 15) + 5;
        if (progress > 90) progress = 90;
        progressBar.style.width = progress + '%';

        if (progress < 30) {
            progressText.innerText = "Connecting to server...";
        } else if (progress < 60) {
            progressText.innerText = "Uploading image...";
        } else {
            progressText.innerText = "Saving data...";
        }
    }, 400);

    const formData = new FormData(this);
    formData.append('ajax', '1');

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        clearInterval(progressInterval);
        progressBar.style.width = '100%';
        progressText.innerText = "Complete!";

        setTimeout(() => {
            overlay.style.display = 'none';
            progressBar.style.width = '0%';

            if (data.status === 'success') {
                Swal.fire({
                    title: 'Success!',
                    text: data.message || 'Your registration has been submitted successfully.',
                    icon: 'success',
                    confirmButtonColor: '#3b82f6',
                    confirmButtonText: 'OK'
                }).then(() => {
                    document.getElementById('studentForm').reset();
                    imagePreview.style.display = 'none';
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.message || 'Submission failed. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#ef4444'
                });
            }
        }, 500);
    })
    .catch(error => {
        clearInterval(progressInterval);
        overlay.style.display = 'none';
        Swal.fire({
            title: 'Network Error',
            text: 'Could not connect to server. Please check your internet connection.',
            icon: 'error',
            confirmButtonColor: '#ef4444'
        });
        console.error('Fetch error:', error);
    });
});
</script>
</body>
</html>