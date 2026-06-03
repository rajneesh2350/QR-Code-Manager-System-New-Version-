<?php
// Handle the AJAX request to Google Sheets securely in the background
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');

    $googleWebAppUrl = 'https://script.google.com/macros/s/AKfycbwFZbzapfSrt_L_VRQtvAqts9sXLc1sPZiwiUWTl3s9YlOvG_ynz2UcFEhsFaEItA/exec';

    // FormData will automatically capture all named inputs
    $formData = $_POST;
    unset($formData['ajax']);

    $ch = curl_init($googleWebAppUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($formData));

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo json_encode(['status' => 'error', 'message' => 'Connection error: ' . $error]);
    } else {
        echo json_encode(['status' => 'success']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IGIPESS Conference Feedback</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc; padding: 30px 20px; display: flex;
            justify-content: center; align-items: flex-start; min-height: 100vh; margin: 0;
        }
        .form-container {
            background: white; padding: 0; border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); width: 100%; max-width: 800px; position: relative; overflow: hidden;
            margin-bottom: 40px;
        }

        /* Branding Header */
        .brand-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: white; padding: 30px 20px; text-align: center;
            border-bottom: 4px solid #3b82f6;
        }
        .brand-header img {
            max-width: 90px; height: auto; margin-bottom: 15px;
            background: white; padding: 5px; border-radius: 50%;
        }
        .brand-header h2 { margin: 0 0 10px 0; font-size: 22px; line-height: 1.4; }
        .brand-header p { margin: 0; font-size: 14px; color: #cbd5e1; line-height: 1.5; }

        .form-body { padding: 30px; }

        .section-card {
            background: #fcfcfc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }

        .section-title {
            font-size: 18px; color: #3b82f6; border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px;
        }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #475569; font-size: 15px; }
        .help-text { font-size: 12px; color: #64748b; margin-top: -5px; margin-bottom: 8px; display: block; }

        .form-control {
            width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px;
            box-sizing: border-box; font-size: 15px; transition: all 0.3s; background-color: #fff; outline: none;
        }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15); }
        textarea.form-control { min-height: 80px; resize: vertical; }

        /* Input with Icons */
        .input-icon-wrapper { position: relative; }
        .input-icon-wrapper i {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: #94a3b8; font-size: 16px;
        }
        .input-icon-wrapper .form-control { padding-left: 40px; }

        /* --- GRAPHICAL STAR RATING SYSTEM --- */
        .rating-wrapper {
            display: flex; align-items: center; justify-content: space-between;
            background: #fff; padding: 10px 15px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 15px;
        }
        .rating-label { font-size: 14px; color: #334155; font-weight: 500; flex: 1; }
        .star-rating { display: flex; flex-direction: row-reverse; gap: 5px; }
        .star-rating input { display: none; }
        .star-rating label { cursor: pointer; color: #cbd5e1; font-size: 22px; transition: color 0.2s; }
        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label { color: #fbbf24; }

        /* --- GRAPHICAL RADIO BUTTONS (Yes/No) --- */
        .radio-group { display: flex; gap: 15px; }
        .radio-card {
            flex: 1; text-align: center; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px;
            cursor: pointer; transition: all 0.2s; background: white; font-weight: 600; color: #475569;
        }
        .radio-card input { display: none; }
        .radio-card:hover { border-color: #3b82f6; background: #eff6ff; }
        input[type="radio"]:checked + .radio-card { background: #3b82f6; color: white; border-color: #3b82f6; }

        .btn-submit {
            background: #3b82f6; color: white; border: none; padding: 16px;
            width: 100%; border-radius: 6px; font-weight: bold; font-size: 18px; cursor: pointer;
            transition: all 0.3s; display: flex; justify-content: center; align-items: center; gap: 10px;
        }
        .btn-submit:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); }

        /* Loading Overlay */
        .loading-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.95); display: none; flex-direction: column; justify-content: center; align-items: center; z-index: 10;
        }
        .progress-container { width: 80%; background-color: #e2e8f0; border-radius: 10px; overflow: hidden; margin-bottom: 15px; height: 12px; }
        .progress-bar { width: 0%; height: 100%; background: linear-gradient(90deg, #3b82f6, #8b5cf6); border-radius: 10px; transition: width 0.4s ease; }
    </style>
</head>
<body>

    <div class="form-container">
        <div class="loading-overlay" id="loadingOverlay">
            <div class="progress-container"><div class="progress-bar" id="progressBar"></div></div>
            <div style="color: #475569; font-weight: 600;" id="progressText">Saving feedback...</div>
        </div>

        <div class="brand-header">
            <img src="https://igipess.du.ac.in/images/igipesslogo1.png" alt="IGIPESS Logo">
            <h2>Indira Gandhi Institute of Physical Education and Sports Sciences</h2>
            <p>Comprehensive Event & Conference Feedback</p>
        </div>

        <div class="form-body">
            <form id="conferenceForm">

                <div style="text-align: center; color:#ef4444; font-weight: 600; margin-bottom: 20px; font-size: 14px;">
                    All fields marked with * are mandatory to complete the feedback.
                </div>

                <div class="section-card">
                    <div class="section-title"><i class="fas fa-user-circle"></i> 1. Personal Details</div>

                    <div class="form-group">
                        <label>Full Name <span style="color:#ef4444;">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" name="full_name" class="form-control" placeholder="Enter your full name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email Address <span style="color:#ef4444;">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" class="form-control" placeholder="your.email@example.com" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Phone / WhatsApp Number <span style="color:#ef4444;">*</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-phone-alt"></i>
                            <input type="text" name="phone" class="form-control" placeholder="Enter contact number" required>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-title"><i class="fas fa-id-badge"></i> 2. Participant Profile</div>
                    <div class="form-group">
                        <label>Category <span style="color:#ef4444;">*</span></label>
                        <select name="category" class="form-control" required>
                            <option value="" disabled selected>Select your profile...</option>
                            <option value="Student">Student</option>
                            <option value="Faculty">Faculty / Teacher</option>
                            <option value="Professional">Industry Professional</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>How did you hear about this conference? <span style="color:#ef4444;">*</span></label>
                        <select name="source" class="form-control" required>
                            <option value="" disabled selected>Select source...</option>
                            <option value="College Notice">College Notice Board</option>
                            <option value="Email">Email Invitation</option>
                            <option value="Social Media">Social Media</option>
                            <option value="Colleague">Colleague / Friend</option>
                        </select>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-title"><i class="fas fa-concierge-bell"></i> 3. Hospitality & Logistics</div>
                    <span class="help-text">Please rate on a scale of 1 to 5 stars (5 being excellent).</span>

                    <div class="rating-wrapper">
                        <span class="rating-label">Quality of Meals & Food <span style="color:#ef4444;">*</span></span>
                        <div class="star-rating">
                            <input type="radio" id="meal5" name="rate_meals" value="5" required><label for="meal5" class="fas fa-star"></label>
                            <input type="radio" id="meal4" name="rate_meals" value="4"><label for="meal4" class="fas fa-star"></label>
                            <input type="radio" id="meal3" name="rate_meals" value="3"><label for="meal3" class="fas fa-star"></label>
                            <input type="radio" id="meal2" name="rate_meals" value="2"><label for="meal2" class="fas fa-star"></label>
                            <input type="radio" id="meal1" name="rate_meals" value="1"><label for="meal1" class="fas fa-star"></label>
                        </div>
                    </div>

                    <div class="rating-wrapper">
                        <span class="rating-label">Overall Hospitality <span style="color:#ef4444;">*</span></span>
                        <div class="star-rating">
                            <input type="radio" id="hosp5" name="rate_hospitality" value="5" required><label for="hosp5" class="fas fa-star"></label>
                            <input type="radio" id="hosp4" name="rate_hospitality" value="4"><label for="hosp4" class="fas fa-star"></label>
                            <input type="radio" id="hosp3" name="rate_hospitality" value="3"><label for="hosp3" class="fas fa-star"></label>
                            <input type="radio" id="hosp2" name="rate_hospitality" value="2"><label for="hosp2" class="fas fa-star"></label>
                            <input type="radio" id="hosp1" name="rate_hospitality" value="1"><label for="hosp1" class="fas fa-star"></label>
                        </div>
                    </div>

                    <div class="rating-wrapper">
                        <span class="rating-label">Behavior & Treatment by Staff <span style="color:#ef4444;">*</span></span>
                        <div class="star-rating">
                            <input type="radio" id="beh5" name="rate_behavior" value="5" required><label for="beh5" class="fas fa-star"></label>
                            <input type="radio" id="beh4" name="rate_behavior" value="4"><label for="beh4" class="fas fa-star"></label>
                            <input type="radio" id="beh3" name="rate_behavior" value="3"><label for="beh3" class="fas fa-star"></label>
                            <input type="radio" id="beh2" name="rate_behavior" value="2"><label for="beh2" class="fas fa-star"></label>
                            <input type="radio" id="beh1" name="rate_behavior" value="1"><label for="beh1" class="fas fa-star"></label>
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 15px;">
                        <label>Did you utilize the QR-Code system frequently during the event? <span style="color:#ef4444;">*</span></label>
                        <div class="radio-group">
                            <label><input type="radio" name="used_qr" value="Yes" required checked><div class="radio-card"><i class="fas fa-qrcode"></i> Yes</div></label>
                            <label><input type="radio" name="used_qr" value="No"><div class="radio-card"><i class="fas fa-times-circle"></i> No</div></label>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-title"><i class="fas fa-chart-bar"></i> 4. Event Experience</div>

                    <div class="rating-wrapper">
                        <span class="rating-label">1. Overall Experience <span style="color:#ef4444;">*</span></span>
                        <div class="star-rating">
                            <input type="radio" id="ovr5" name="rate_overall" value="5" required><label for="ovr5" class="fas fa-star"></label>
                            <input type="radio" id="ovr4" name="rate_overall" value="4"><label for="ovr4" class="fas fa-star"></label>
                            <input type="radio" id="ovr3" name="rate_overall" value="3"><label for="ovr3" class="fas fa-star"></label>
                            <input type="radio" id="ovr2" name="rate_overall" value="2"><label for="ovr2" class="fas fa-star"></label>
                            <input type="radio" id="ovr1" name="rate_overall" value="1"><label for="ovr1" class="fas fa-star"></label>
                        </div>
                    </div>

                    <div class="rating-wrapper">
                        <span class="rating-label">2. Content & Session Relevance <span style="color:#ef4444;">*</span></span>
                        <div class="star-rating">
                            <input type="radio" id="cont5" name="rate_content" value="5" required><label for="cont5" class="fas fa-star"></label>
                            <input type="radio" id="cont4" name="rate_content" value="4"><label for="cont4" class="fas fa-star"></label>
                            <input type="radio" id="cont3" name="rate_content" value="3"><label for="cont3" class="fas fa-star"></label>
                            <input type="radio" id="cont2" name="rate_content" value="2"><label for="cont2" class="fas fa-star"></label>
                            <input type="radio" id="cont1" name="rate_content" value="1"><label for="cont1" class="fas fa-star"></label>
                        </div>
                    </div>

                    <div class="rating-wrapper">
                        <span class="rating-label">3. Speaker Delivery & Knowledge <span style="color:#ef4444;">*</span></span>
                        <div class="star-rating">
                            <input type="radio" id="spk5" name="rate_speakers" value="5" required><label for="spk5" class="fas fa-star"></label>
                            <input type="radio" id="spk4" name="rate_speakers" value="4"><label for="spk4" class="fas fa-star"></label>
                            <input type="radio" id="spk3" name="rate_speakers" value="3"><label for="spk3" class="fas fa-star"></label>
                            <input type="radio" id="spk2" name="rate_speakers" value="2"><label for="spk2" class="fas fa-star"></label>
                            <input type="radio" id="spk1" name="rate_speakers" value="1"><label for="spk1" class="fas fa-star"></label>
                        </div>
                    </div>

                    <div class="rating-wrapper">
                        <span class="rating-label">4. Organization, Management & Schedule <span style="color:#ef4444;">*</span></span>
                        <div class="star-rating">
                            <input type="radio" id="org5" name="rate_org" value="5" required><label for="org5" class="fas fa-star"></label>
                            <input type="radio" id="org4" name="rate_org" value="4"><label for="org4" class="fas fa-star"></label>
                            <input type="radio" id="org3" name="rate_org" value="3"><label for="org3" class="fas fa-star"></label>
                            <input type="radio" id="org2" name="rate_org" value="2"><label for="org2" class="fas fa-star"></label>
                            <input type="radio" id="org1" name="rate_org" value="1"><label for="org1" class="fas fa-star"></label>
                        </div>
                    </div>

                    <div class="rating-wrapper">
                        <span class="rating-label">5. Venue, Facilities & Audio/Visuals <span style="color:#ef4444;">*</span></span>
                        <div class="star-rating">
                            <input type="radio" id="ven5" name="rate_venue" value="5" required><label for="ven5" class="fas fa-star"></label>
                            <input type="radio" id="ven4" name="rate_venue" value="4"><label for="ven4" class="fas fa-star"></label>
                            <input type="radio" id="ven3" name="rate_venue" value="3"><label for="ven3" class="fas fa-star"></label>
                            <input type="radio" id="ven2" name="rate_venue" value="2"><label for="ven2" class="fas fa-star"></label>
                            <input type="radio" id="ven1" name="rate_venue" value="1"><label for="ven1" class="fas fa-star"></label>
                        </div>
                    </div>

                    <div class="rating-wrapper">
                        <span class="rating-label">6. Registration Process & Support Desk <span style="color:#ef4444;">*</span></span>
                        <div class="star-rating">
                            <input type="radio" id="reg5" name="rate_reg" value="5" required><label for="reg5" class="fas fa-star"></label>
                            <input type="radio" id="reg4" name="rate_reg" value="4"><label for="reg4" class="fas fa-star"></label>
                            <input type="radio" id="reg3" name="rate_reg" value="3"><label for="reg3" class="fas fa-star"></label>
                            <input type="radio" id="reg2" name="rate_reg" value="2"><label for="reg2" class="fas fa-star"></label>
                            <input type="radio" id="reg1" name="rate_reg" value="1"><label for="reg1" class="fas fa-star"></label>
                        </div>
                    </div>
                </div>

                <div class="section-card">
                    <div class="section-title"><i class="fas fa-comment-dots"></i> 5. Detailed Feedback</div>

                    <div class="form-group">
                        <label>Learning Outcomes</label>
                        <span class="help-text">Which session did you find most valuable and why?</span>
                        <textarea name="text_learning" class="form-control" placeholder="Type your thoughts here..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Suggestions & Improvements</label>
                        <span class="help-text">What can be improved in future events?</span>
                        <textarea name="text_suggestions" class="form-control" placeholder="Share your suggestions..."></textarea>
                    </div>

                    <div class="form-group">
                        <label>Recommendations</label>
                        <div class="radio-group" style="margin-bottom: 10px;">
                            <label style="flex:1"><input type="radio" name="will_recommend" value="Yes" checked><div class="radio-card">I would recommend to others</div></label>
                            <label style="flex:1"><input type="radio" name="will_recommend" value="No"><div class="radio-card">I would NOT recommend</div></label>
                        </div>
                        <div class="radio-group">
                            <label style="flex:1"><input type="radio" name="attend_future" value="Yes" checked><div class="radio-card">I will attend future events</div></label>
                            <label style="flex:1"><input type="radio" name="attend_future" value="No"><div class="radio-card">I will NOT attend future events</div></label>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Event Feedback
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('conferenceForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const overlay = document.getElementById('loadingOverlay');
            const progressBar = document.getElementById('progressBar');
            const progressText = document.getElementById('progressText');

            overlay.style.display = 'flex';

            let progress = 0;
            let progressInterval = setInterval(() => {
                progress += Math.floor(Math.random() * 20);
                if(progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
            }, 500);

            const formData = new FormData(this);
            formData.append('ajax', '1');

            fetch('', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                clearInterval(progressInterval);
                progressBar.style.width = '100%';
                progressText.innerText = "Submitted Successfully!";

                setTimeout(() => {
                    overlay.style.display = 'none';
                    progressBar.style.width = '0%';

                    if (data.status === 'success') {
                        Swal.fire({
                            title: 'Feedback Received!',
                            text: 'Thank you for taking the time to share your experience.',
                            icon: 'success',
                            confirmButtonColor: '#3b82f6'
                        }).then(() => {
                            document.getElementById('conferenceForm').reset();
                            window.scrollTo(0, 0);
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Submission failed.', 'error');
                    }
                }, 600);
            })
            .catch(error => {
                clearInterval(progressInterval);
                overlay.style.display = 'none';
                Swal.fire('Network Error', 'Could not connect. Please try again.', 'error');
            });
        });
    </script>
</body>
</html>