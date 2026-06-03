<?php
// 1. Include your config file for the Database Connection
require_once 'includes/config.php';

// 2. Handle the AJAX request to Google Sheets securely in the background
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    // Your specific Google Web App URL
    $googleWebAppUrl = 'https://script.google.com/macros/s/AKfycbwFZbzapfSrt_L_VRQtvAqts9sXLc1sPZiwiUWTl3s9YlOvG_ynz2UcFEhsFaEItA/exec';

    // Collect Form Data
    $formData = [
        'student_name'  => $_POST['student_name'],
        'roll_number'   => $_POST['roll_number'], // This now receives the merged "M-008/26"
        'mobile_number' => $_POST['mobile_number'],
        'email'         => $_POST['email']
    ];

    // Send Data to Google Sheets via cURL
    $ch = curl_init($googleWebAppUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($formData));

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    // Send response back to our JavaScript
    if ($error) {
        echo json_encode(['status' => 'error', 'message' => 'Connection error: ' . $error]);
    } else {
        $responseData = json_decode($response, true);
        if (isset($responseData['status']) && $responseData['status'] == 'success') {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save data.']);
        }
    }
    exit;
}

// 3. Fetch Courses for the Dropdown from your Database
$coursesResult = $conn->query("SELECT id, course_name FROM twl_course ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canteen QR Registration</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f1f5f9; padding: 20px; display: flex;
            justify-content: center; align-items: center; min-height: 100vh; margin: 0;
        }
        .form-container {
            background: white; padding: 30px; border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); width: 100%; max-width: 450px; position: relative; overflow: hidden;
        }
        .form-container h2 { text-align: center; color: #1e293b; margin-top: 0; margin-bottom: 25px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #475569; font-size: 14px; }

        /* Standard Inputs */
        .form-control {
            width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px;
            box-sizing: border-box; font-size: 15px; transition: border-color 0.2s; background-color: white; outline: none;
        }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

        /* Composite Roll Number Field */
        .roll-input-group { display: flex; align-items: stretch; }
        .prefix-box {
            background: #f1f5f9; border: 1px solid #cbd5e1; border-right: none;
            padding: 12px 15px; border-radius: 6px 0 0 6px; font-weight: bold; color: #3b82f6;
            display: flex; align-items: center; justify-content: center; min-width: 45px;
        }
        .roll-input-group input { border-radius: 0 6px 6px 0; border-left: 1px solid #cbd5e1; flex-grow: 1; }

        .btn-submit {
            background: #3b82f6; color: white; border: none; padding: 14px;
            width: 100%; border-radius: 6px; font-weight: bold; font-size: 16px; cursor: pointer; transition: background 0.2s;
        }
        .btn-submit:hover { background: #2563eb; }

        /* Loading Overlay Styles */
        .loading-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.95); display: none; flex-direction: column; justify-content: center; align-items: center; z-index: 10;
        }
        .progress-container { width: 80%; background-color: #e2e8f0; border-radius: 10px; overflow: hidden; margin-bottom: 15px; height: 12px; }
        .progress-bar { width: 0%; height: 100%; background: linear-gradient(90deg, #3b82f6, #8b5cf6); border-radius: 10px; transition: width 0.4s ease; }
        .progress-text { color: #475569; font-weight: 600; font-size: 15px; }
    </style>
</head>
<body>

    <div class="form-container">

        <div class="loading-overlay" id="loadingOverlay">
            <div class="progress-container"><div class="progress-bar" id="progressBar"></div></div>
            <div class="progress-text" id="progressText">Starting...</div>
        </div>

        <h2>Meal Coupon Registration</h2>

        <form id="registrationForm">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="student_name" class="form-control" placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label>Course Selection</label>
                <select id="courseSelect" class="form-control" required>
                    <option value="" disabled selected>-- Select your course --</option>
                    <?php
                    if ($coursesResult && $coursesResult->num_rows > 0) {
                        while($row = $coursesResult->fetch_assoc()) {
                            // Assign logic for D, B, M based on ID
                            $prefix = '';
                            if ($row['id'] == 2) $prefix = 'D';
                            elseif ($row['id'] == 3) $prefix = 'B';
                            elseif ($row['id'] == 4) $prefix = 'M';
                            else $prefix = 'X'; // Fallback if a new ID is added

                            echo "<option value='{$row['id']}' data-prefix='{$prefix}'>" . htmlspecialchars($row['course_name']) . "</option>";
                        }
                    } else {
                        echo "<option value='' disabled>No courses found in database.</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>Roll Number</label>
                <div class="roll-input-group">
                    <span class="prefix-box" id="rollPrefixDisplay">-</span>
                    <input type="text" id="rollInputNumeric" class="form-control" placeholder="008/26" required pattern="[0-9]+/[0-9]+" title="Format: Number/Year (e.g., 008/26)">
                </div>
                <input type="hidden" name="roll_number" id="finalRollNumber">
            </div>

            <div class="form-group">
                <label>WhatsApp Number</label>
                <input type="text" name="mobile_number" class="form-control" placeholder="e.g., 919876543210 (include country code)" required pattern="[0-9]+" title="Numbers only, including country code (e.g., 91 for India)">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="student@example.com" required>
            </div>
            <button type="submit" class="btn-submit">Submit Registration</button>
        </form>
    </div>

    <script>
        // 1. Handle dynamic Course Prefix updates
        const courseSelect = document.getElementById('courseSelect');
        const prefixDisplay = document.getElementById('rollPrefixDisplay');

        courseSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const prefix = selectedOption.getAttribute('data-prefix');

            if(prefix) {
                prefixDisplay.innerText = prefix + '-';
            } else {
                prefixDisplay.innerText = '-';
            }
        });

        // 2. Handle Form Submission
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // Validate Course Selection
            if(courseSelect.value === "") {
                Swal.fire('Error', 'Please select a course.', 'error');
                return;
            }

            // Combine Prefix and Numeric Input before sending
            const prefix = courseSelect.options[courseSelect.selectedIndex].getAttribute('data-prefix');
            const numericRoll = document.getElementById('rollInputNumeric').value;

            // Merge them (e.g., "M" + "-" + "008/26" = "M-008/26")
            document.getElementById('finalRollNumber').value = prefix + '-' + numericRoll;

            // Get UI elements for loading
            const overlay = document.getElementById('loadingOverlay');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');

            overlay.style.display = 'flex';

            let progress = 0;
            let progressInterval = setInterval(() => {
                progress += Math.floor(Math.random() * 15);
                if(progress > 85) progress = 85;
                progressBar.style.width = progress + '%';

                if(progress < 30) progressText.innerText = "Verifying details...";
                else if(progress < 60) progressText.innerText = "Connecting to secure server...";
                else progressText.innerText = "Saving registration data...";
            }, 600);

            // Send via AJAX
            const formData = new FormData(this);
            formData.append('ajax', '1');

            fetch('', { method: 'POST', body: formData })
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
                            title: 'Registration Successful!',
                            text: 'Your details have been securely saved.',
                            icon: 'success',
                            confirmButtonColor: '#3b82f6'
                        }).then(() => {
                            document.getElementById('registrationForm').reset();
                            prefixDisplay.innerText = '-'; // Reset prefix UI
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                }, 500);
            })
            .catch(error => {
                clearInterval(progressInterval);
                overlay.style.display = 'none';
                Swal.fire('Network Error', 'Could not connect to the server. Please check your internet and try again.', 'error');
            });
        });
    </script>
</body>
</html>