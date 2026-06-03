<?php
require_once 'includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = isset($_POST['code']) ? $_POST['code'] : '';

    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => 'No QR code data received.']);
        exit;
    }

    // Decode the Base64 string back into: PREFIX-000-MEAL-RATE
    $decoded = base64_decode($code);
    $parts = explode('-', $decoded);
    $rate = 0.00;

    if (count($parts) >= 3) {

        // BULLETPROOF EXTRACTION LOGIC
        $lastPart = end($parts);
        if (is_numeric($lastPart)) {
            // It has a rate at the end (e.g. "50")
            $rate = floatval(array_pop($parts));
            $couponType = strtoupper(array_pop($parts)); // Gets the Meal Type (e.g. "LUNCH")
        } else {
            // Old format without rate
            $couponType = strtoupper(array_pop($parts));
        }

        $couponNumber = implode('-', $parts);

        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'];

        // CHECK: Has this specific coupon been used already?
        $checkStmt = $conn->prepare("SELECT used_at FROM qr_code_coupon WHERE coupon_code = ?");
        $checkStmt->bind_param("s", $decoded);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $timeUsed = date('h:i A (M d)', strtotime($row['used_at']));

            $logStmt = $conn->prepare("INSERT INTO qr_scan_logs_coupon (coupon_code, status, user_agent, ip_address) VALUES (?, 'ALREADY_USED', ?, ?)");
            $logStmt->bind_param("sss", $decoded, $ua, $ip);
            $logStmt->execute();

            echo json_encode([
                'success' => false,
                'title' => 'Access Denied!',
                'message' => "This $couponType coupon ($couponNumber) was ALREADY USED at $timeUsed."
            ]);
        } else {
            // VALID COUPON: Mark as used AND SAVE THE RATE CORRECTLY
            $insertStmt = $conn->prepare("INSERT INTO qr_code_coupon (coupon_code, coupon_type, rate) VALUES (?, ?, ?)");
            $insertStmt->bind_param("ssd", $decoded, $couponType, $rate);

            if ($insertStmt->execute()) {
                $logStmt = $conn->prepare("INSERT INTO qr_scan_logs_coupon (coupon_code, status, user_agent, ip_address) VALUES (?, 'SUCCESSFULLY_USED', ?, ?)");
                $logStmt->bind_param("sss", $decoded, $ua, $ip);
                $logStmt->execute();

                echo json_encode([
                    'success' => true,
                    'title' => 'Access Granted!',
                    'message' => "$couponType coupon verified! (Rate: ?$rate)"
                ]);
            } else {
                echo json_encode(['success' => false, 'title' => 'Error', 'message' => 'Database error while saving.']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'title' => 'Invalid Code', 'message' => 'This QR code does not belong to the IGIPESS system.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>