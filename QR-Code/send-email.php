<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '';
    $couponHtml = isset($_POST['coupon_html']) ? $_POST['coupon_html'] : '';

    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address provided.']);
        exit;
    }

    if (empty($couponHtml)) {
        echo json_encode(['success' => false, 'message' => 'No coupons found to send. Please generate them first.']);
        exit;
    }

    $subject = "Your IGIPESS Meal Coupons";

    $message = "
    <html>
    <head>
        <title>Meal Coupons</title>
    </head>
    <body style='font-family: Arial, sans-serif; background-color: #f1f5f9; padding: 20px;'>
        <div style='max-width: 800px; margin: 0 auto; background: #ffffff; padding: 20px; border-radius: 8px;'>
            <h2 style='text-align: center; color: #333;'>Your Meal Coupons</h2>
            <p style='text-align: center; color: #666;'>Please present these coupons at the canteen.</p>
            <hr style='border: 1px solid #eee; margin-bottom: 20px;'>
            <div style='text-align: center;'>
                " . $couponHtml . "
            </div>
        </div>
    </body>
    </html>
    ";

    // ROBUST EMAIL HEADERS (Prevents Gmail/Yahoo from blocking the email)
    $domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'igipess.edu.in';
    $fromEmail = "no-reply@" . $domain;

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: IGIPESS Canteen <" . $fromEmail . ">\r\n";
    $headers .= "Reply-To: " . $fromEmail . "\r\n";
    $headers .= "Return-Path: " . $fromEmail . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "Message-ID: <" . time() . "-" . uniqid() . "@" . $domain . ">\r\n";

    // The 5th parameter '-f' forces the return path, crucial for passing DMARC/SPF checks
    $mailSent = @mail($to, $subject, $message, $headers, "-f " . $fromEmail);

    if ($mailSent) {
        echo json_encode(['success' => true, 'message' => "Coupons successfully sent to $to"]);
    } else {
        $error = error_get_last();
        $errMsg = $error ? $error['message'] : 'Failed to send email. Check your PHP mail server configuration.';
        echo json_encode(['success' => false, 'message' => $errMsg]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
}
?>