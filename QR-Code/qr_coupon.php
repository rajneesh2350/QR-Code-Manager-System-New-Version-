<?php
require_once 'includes/config.php';

// Automatically create the required DB tables if they don't exist yet
$conn->query("CREATE TABLE IF NOT EXISTS qr_code_coupon (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_code VARCHAR(100) UNIQUE NOT NULL,
    coupon_type VARCHAR(50),
    used_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS qr_scan_logs_coupon (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_code VARCHAR(100),
    scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50),
    user_agent TEXT,
    ip_address VARCHAR(50)
)");

// Initialize validation variables
$code = isset($_GET['code']) ? $_GET['code'] : '';
$status = '';
$message = '';
$couponType = 'UNKNOWN';
$couponNumber = 'UNKNOWN';

if ($code) {
    // Decode the Base64 string back into: PREFIX-000-MEAL
    $decoded = base64_decode($code);
    $parts = explode('-', $decoded);

    if (count($parts) >= 3) {
        $couponType = array_pop($parts); // Grabs the last part (LUNCH, TEA, etc.)
        $couponNumber = implode('-', $parts); // Recombines the rest (e.g., IGIPESS-001)

        // Grab security metadata
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'];

        // CHECK 1: Has this specific coupon been used already?
        $checkStmt = $conn->prepare("SELECT used_at FROM qr_code_coupon WHERE coupon_code = ?");
        $checkStmt->bind_param("s", $decoded);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            // ALREADY USED!
            $row = $result->fetch_assoc();
            $status = 'error';
            $message = 'Warning! This ' . $couponType . ' coupon (' . $couponNumber . ') was ALREADY USED on ' . date('M d, Y h:i A', strtotime($row['used_at'])) . '.';

            // Log the suspicious/failed scan attempt
            $logStmt = $conn->prepare("INSERT INTO qr_scan_logs_coupon (coupon_code, status, user_agent, ip_address) VALUES (?, 'ALREADY_USED', ?, ?)");
            $logStmt->bind_param("sss", $decoded, $ua, $ip);
            $logStmt->execute();
        } else {
            // VALID COUPON: Mark it as used permanently
            $insertStmt = $conn->prepare("INSERT INTO qr_code_coupon (coupon_code, coupon_type) VALUES (?, ?)");
            $insertStmt->bind_param("ss", $decoded, $couponType);

            if ($insertStmt->execute()) {
                $status = 'success';
                $message = $couponType . ' coupon (' . $couponNumber . ') successfully verified and marked as used!';

                // Log the successful usage
                $logStmt = $conn->prepare("INSERT INTO qr_scan_logs_coupon (coupon_code, status, user_agent, ip_address) VALUES (?, 'SUCCESSFULLY_USED', ?, ?)");
                $logStmt->bind_param("sss", $decoded, $ua, $ip);
                $logStmt->execute();
            } else {
                $status = 'error';
                $message = 'Database error while saving the coupon. Please try again.';
            }
        }
    } else {
        $status = 'error';
        $message = 'Invalid coupon barcode format detected.';
    }
} else {
    $status = 'error';
    $message = 'No coupon code provided in URL.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupon Scanner Verification</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8fafc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container { text-align: center; padding: 20px; color: #64748b; }
        .spinner {
            border: 4px solid #e2e8f0; border-top: 4px solid #3b82f6; border-radius: 50%;
            width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 15px auto;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h2>Validating Coupon...</h2>
        <p>Please wait while we check the database.</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Grab PHP variables
            let status = '<?php echo $status; ?>';
            let message = '<?php echo addslashes($message); ?>';

            // Format Alert beautifully based on outcome
            let title = status === 'success' ? 'Access Granted!' : 'Access Denied!';
            let icon = status === 'success' ? 'success' : 'error';
            let btnColor = status === 'success' ? '#10b981' : '#ef4444';

            // Pop the SweetAlert
            Swal.fire({
                title: title,
                text: message,
                icon: icon,
                confirmButtonColor: btnColor,
                confirmButtonText: 'Ready for Next Scan',
                timer: 2500, // Auto-closes after 2.5 seconds
                timerProgressBar: true,
                allowOutsideClick: false
            }).then((result) => {
                // Try to close the window (Works in most QR Scanner in-app browsers)
                window.close();

                // Fallback: forcefully go back in history if window.close() is blocked
                window.history.back();
            });
        });
    </script>
</body>
</html>