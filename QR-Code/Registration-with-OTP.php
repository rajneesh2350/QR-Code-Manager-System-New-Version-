<?php
session_start();
require_once 'includes/config.php';

$action = isset($_GET['action']) ? $_GET['action'] : '';
$message = '';
$status = '';

// Helper function to send robust emails
function send_robust_mail($to, $subject, $htmlMessage) {
    $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'igipess.edu.in';
    $fromEmail = "no-reply@" . $domain;

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: IGIPESS Canteen <" . $fromEmail . ">\r\n";
    $headers .= "Reply-To: " . $fromEmail . "\r\n";
    $headers .= "Return-Path: " . $fromEmail . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    return @mail($to, $subject, $htmlMessage, $headers, "-f " . $fromEmail);
}

// ==========================================
// ACTION 1: SEND OTP
// ==========================================
if ($action == 'send_otp' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);

    $check = $conn->query("SELECT * FROM student_registrations WHERE email = '$email'");
    if ($check->num_rows > 0) {
        $row = $check->fetch_assoc();
        if ($row['is_verified'] == 1) {
            echo json_encode(['success' => false, 'message' => 'This email has already claimed a coupon!']);
            exit;
        } else {
            $student_id = $row['id'];
        }
    }

    $otp = rand(100000, 999999);

    if (!isset($student_id)) {
        $conn->query("INSERT INTO student_registrations (name, email, phone, otp) VALUES ('$name', '$email', '$phone', '$otp')");
    } else {
        $conn->query("UPDATE student_registrations SET otp = '$otp', name = '$name', phone = '$phone' WHERE id = $student_id");
    }

    $subject = "IGIPESS Canteen - Your Verification OTP";
    $msg = "
    <div style='font-family: Arial, sans-serif; padding: 20px; max-width: 500px; margin: auto; border: 1px solid #ddd; border-radius: 8px;'>
        <h2 style='color: #0f172a;'>OTP Verification</h2>
        <p>Hello <strong>$name</strong>,</p>
        <p>Your One-Time Password (OTP) for the IGIPESS Meal Coupon is:</p>
        <h1 style='color: #3b82f6; letter-spacing: 5px; text-align: center; background: #f1f5f9; padding: 10px; border-radius: 8px;'>$otp</h1>
        <p style='color: #64748b; font-size: 12px;'>Please enter this on the screen to generate your coupon. Do not share this code.</p>
    </div>";

    if (send_robust_mail($email, $subject, $msg)) {
        $_SESSION['verify_email'] = $email;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP email. Please check server mail settings.']);
    }
    exit;
}

// ==========================================
// ACTION 2: VERIFY OTP & GENERATE COUPON
// ==========================================
if ($action == 'verify_otp' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_SESSION['verify_email'];
    $otp_entered = $conn->real_escape_string($_POST['otp']);

    $query = $conn->query("SELECT * FROM student_registrations WHERE email = '$email' AND otp = '$otp_entered'");

    if ($query->num_rows > 0) {
        $student = $query->fetch_assoc();
        $student_id = $student['id'];
        $name = $student['name'];

        $padded_id = str_pad($student_id, 3, '0', STR_PAD_LEFT);
        $raw_coupon = "IGIPESS-STU" . $padded_id . "-LUNCH";
        $encoded_coupon = base64_encode($raw_coupon);

        $conn->query("UPDATE student_registrations SET is_verified = 1, otp = NULL, coupon_code = '$encoded_coupon' WHERE id = $student_id");

        $subject = "Your IGIPESS Meal Coupon is Ready!";
        $htmlMsg = "
        <div style='text-align:center; font-family: Arial, sans-serif; padding: 20px; max-width: 500px; margin: auto; border: 1px solid #ddd; border-radius: 10px;'>
            <h2 style='color: #0f172a;'>Hello $name!</h2>
            <p style='color: #475569;'>Your registration is successful. Present the QR code below at the canteen.</p>
            <img src='https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($encoded_coupon) . "' alt='QR Code' style='border: 4px solid #fff; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 8px;'>
            <br><br>
            <h3 style='background: #f1f5f9; display: inline-block; padding: 10px 20px; border-radius: 5px; border: 1px solid #cbd5e1;'>COUPON: IGIPESS-STU$padded_id</h3>
        </div>";

        send_robust_mail($email, $subject, $htmlMsg);

        echo json_encode([
            'success' => true,
            'qr_url' => "https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=2&ecc=L&data=" . urlencode($encoded_coupon),
            'coupon_text' => "IGIPESS-STU$padded_id"
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>IGIPESS Meal Coupon Registration</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .app-container { background: white; width: 100%; max-width: 400px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: #0f172a; color: white; text-align: center; padding: 25px 20px; }
        .header img { max-width: 60px; margin-bottom: 10px; }
        .header h2 { margin: 0; font-size: 20px; color: #38bdf8; }
        .content { padding: 30px 25px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: bold; color: #475569; margin-bottom: 8px; }
        .form-group input { width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 15px; box-sizing: border-box; outline: none; transition: border 0.3s; }
        .form-group input:focus { border-color: #3b82f6; }
        .btn { width: 100%; padding: 14px; background: #3b82f6; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #2563eb; }

        #step2, #step3 { display: none; }

        /* Final Coupon Card Styles */
        .final-coupon { text-align: center; border: 2px dashed #cbd5e1; padding: 20px; border-radius: 10px; margin-top: 10px; }
        .final-coupon img { max-width: 200px; border-radius: 8px; margin-bottom: 15px; }
        .final-coupon h3 { margin: 0; color: #0f172a; }
        .success-msg { color: #10b981; font-weight: bold; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>

<div class="app-container">
    <div class="header">
        <img src="igipesslogo1.png" alt="IGIPESS Logo" onerror="this.style.display='none'">
        <h2>Meal Coupon Portal</h2>
        <p style="margin: 5px 0 0 0; font-size: 13px; color: #cbd5e1;">Register to claim your canteen coupon</p>
    </div>

    <div class="content">
        <div id="step1">
            <form id="regForm" onsubmit="sendOTP(event)">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="name" required placeholder="Enter your name">
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" id="email" required placeholder="student@example.com">
                </div>
                <div class="form-group">
                    <label>Mobile Number</label>
                    <input type="tel" id="phone" required placeholder="10-digit mobile number">
                </div>
                <button type="submit" class="btn">Get Verification Code</button>
            </form>
        </div>

        <div id="step2">
            <div style="text-align: center; margin-bottom: 20px;">
                <h3 style="margin-top:0;">Verify Your Email</h3>
                <p style="font-size: 14px; color: #64748b;">We sent a 6-digit code to your email. Enter it below.</p>
            </div>
            <form id="otpForm" onsubmit="verifyOTP(event)">
                <div class="form-group">
                    <input type="text" id="otp_code" required placeholder="Enter 6-digit OTP" style="text-align: center; font-size: 20px; letter-spacing: 5px;" maxlength="6">
                </div>
                <button type="submit" class="btn" style="background: #10b981;">Verify & Generate Coupon</button>
                <div style="text-align: center; margin-top: 15px;">
                    <a href="#" onclick="goBackToStep1(event)" style="font-size: 13px; color: #ef4444; text-decoration: none;"><i class="fas fa-edit"></i> Incorrect Email? Go back.</a>
                </div>
            </form>
        </div>

        <div id="step3">
            <div class="success-msg">
                ✓ Verification Successful!
            </div>
            <p style="text-align: center; font-size: 14px; color: #64748b; margin-top: 0;">Please take a screenshot of this coupon or check your email.</p>

            <div class="final-coupon">
                <img id="final_qr" src="" alt="Your Coupon">
                <h3 id="final_code"></h3>
                <p style="margin-top: 5px; font-weight: bold; color: #3b82f6;">LUNCH</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Smooth transition back to Step 1
    function goBackToStep1(e) {
        e.preventDefault();
        document.getElementById('step2').style.display = 'none';
        document.getElementById('step1').style.display = 'block';
    }

    function sendOTP(e) {
        e.preventDefault();

        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const phone = document.getElementById('phone').value;

        Swal.fire({ title: 'Sending OTP...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        const fd = new FormData();
        fd.append('name', name); fd.append('email', email); fd.append('phone', phone);

        fetch('student-register.php?action=send_otp', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                Swal.close();
                document.getElementById('step1').style.display = 'none';
                document.getElementById('step2').style.display = 'block';
                document.getElementById('otp_code').value = ''; // Clear old OTP input
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(err => Swal.fire('Error', 'Network error. Please try again.', 'error'));
    }

    function verifyOTP(e) {
        e.preventDefault();
        const otp = document.getElementById('otp_code').value;

        Swal.fire({ title: 'Verifying & Generating...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });

        const fd = new FormData();
        fd.append('otp', otp);

        fetch('student-register.php?action=verify_otp', { method: 'POST', body: fd })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                Swal.close();
                document.getElementById('step2').style.display = 'none';
                document.getElementById('step3').style.display = 'block';

                // Show the QR code
                document.getElementById('final_qr').src = data.qr_url;
                document.getElementById('final_code').innerText = data.coupon_text;
            } else {
                Swal.fire('Error', data.message, 'error');
            }
        })
        .catch(err => Swal.fire('Error', 'Network error. Please try again.', 'error'));
    }
</script>

</body>
</html>