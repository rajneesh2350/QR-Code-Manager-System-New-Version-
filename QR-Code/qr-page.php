<?php
require_once 'includes/config.php';
require_once 'includes/encryption.php';
require_once 'includes/functions.php';
require_once 'includes/device_detector.php';
require_once 'includes/geo_location.php';

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session for tracking
session_start();

// Log function for debugging
function logQRScan($message) {
    $logFile = 'qr_scans.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

logQRScan("=== New QR Scan ===");

// Check database connection
if (!$conn || $conn->connect_error) {
    logQRScan("Database connection failed: " . ($conn->connect_error ?? 'No connection'));
    die("Database connection error. Please try again later.");
}

// Check if data parameter exists
if (!isset($_GET['data']) && !isset($_GET['qr'])) {
    logQRScan("Error: No data parameter found");
    die("Invalid QR Code: No data parameter");
}

// Get encrypted data from URL
$encryptedData = isset($_GET['data']) ? $_GET['data'] : $_GET['qr'];
logQRScan("Encrypted data received: " . substr($encryptedData, 0, 50) . "...");

try {
    // Decrypt the data
    $decryptedData = Encryption::decrypt($encryptedData);
    logQRScan("Decrypted data: " . $decryptedData);

    if (!$decryptedData) {
        logQRScan("Error: Decryption failed");
        die("Invalid QR Code: Decryption failed");
    }

    // Parse decrypted data
    parse_str($decryptedData, $params);
    logQRScan("Parsed params: " . print_r($params, true));

    if (!isset($params['id'])) {
        logQRScan("Error: No ID in decrypted data");
        die("Invalid QR Code: No ID found");
    }

    $id = (int)$params['id'];
    logQRScan("QR ID: " . $id);

    // Get QR code details from database
    $stmt = $conn->prepare("SELECT * FROM qr_codes WHERE id = ?");
    if (!$stmt) {
        logQRScan("Prepare failed for SELECT: " . $conn->error);
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        logQRScan("Error: QR Code not found in database for ID: " . $id);
        die("QR Code not found in database");
    }

    $qrData = $result->fetch_assoc();
    logQRScan("QR Data found: " . $qrData['qrname'] . " - Final URL: " . $qrData['finalurl']);

    // Detect device information
    $detector = new DeviceDetector();
    $deviceInfo = $detector->getDeviceInfo();
    $clientInfo = $detector->getAllInfo();

    // Get geolocation info
    $geoInfo = GeoLocation::getLocationInfo($clientInfo['ip_address']);

    // Get screen resolution if available (from JavaScript)
    $screenResolution = $_POST['screen_resolution'] ?? $_GET['screen'] ?? 'Unknown';

    // Prepare scan data for logging
    $scanTime = time();
    $scanData = [
        'qr_id' => $id,
        'qr_name' => $qrData['qrname'],
        'ip_address' => $clientInfo['ip_address'],
        'device_type' => $deviceInfo['device_type'],
        'device_os' => $deviceInfo['device_os'],
        'os_version' => $deviceInfo['os_version'],
        'browser' => $deviceInfo['browser'],
        'browser_version' => $deviceInfo['browser_version'],
        'user_agent' => $clientInfo['raw_user_agent'],
        'device_model' => $deviceInfo['device_model'],
        'screen_resolution' => $screenResolution,
        'language' => $clientInfo['language'],
        'country' => $geoInfo['country'],
        'city' => $geoInfo['city'],
        'region' => $geoInfo['region'],
        'isp' => $geoInfo['isp'],
        'timezone' => $geoInfo['timezone'],
        'referer' => $clientInfo['referer'],
        'scan_time' => date('Y-m-d H:i:s', $scanTime),
        'day_of_week' => date('l', $scanTime),
        'hour_of_day' => (int)date('H', $scanTime),
        'is_mobile' => $deviceInfo['is_mobile'] ? 1 : 0,
        'is_tablet' => $deviceInfo['is_tablet'] ? 1 : 0,
        'is_desktop' => $deviceInfo['is_desktop'] ? 1 : 0,
        'is_robot' => $deviceInfo['is_robot'] ? 1 : 0,
        'session_id' => session_id()
    ];

    logQRScan("Scan details: " . print_r($scanData, true));

    // FIXED: Correct number of placeholders (25) and parameters
    $insertStmt = $conn->prepare("
        INSERT INTO qr_scan_logs (
            qr_id, qr_name, ip_address, device_type, device_os, os_version,
            browser, browser_version, user_agent, device_model, screen_resolution,
            language, country, city, region, isp, timezone, referer,
            scan_time, day_of_week, hour_of_day, is_mobile, is_tablet,
            is_desktop, is_robot, session_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$insertStmt) {
        logQRScan("Prepare failed for INSERT: " . $conn->error);
        die("Database error preparing insert: " . $conn->error);
    }

    // FIXED: Correct number of parameters (26) matching the 26 placeholders
    $insertStmt->bind_param(
        "isssssssssssssssssssssssss", // 26 's' characters
        $scanData['qr_id'],
        $scanData['qr_name'],
        $scanData['ip_address'],
        $scanData['device_type'],
        $scanData['device_os'],
        $scanData['os_version'],
        $scanData['browser'],
        $scanData['browser_version'],
        $scanData['user_agent'],
        $scanData['device_model'],
        $scanData['screen_resolution'],
        $scanData['language'],
        $scanData['country'],
        $scanData['city'],
        $scanData['region'],
        $scanData['isp'],
        $scanData['timezone'],
        $scanData['referer'],
        $scanData['scan_time'],
        $scanData['day_of_week'],
        $scanData['hour_of_day'],
        $scanData['is_mobile'],
        $scanData['is_tablet'],
        $scanData['is_desktop'],
        $scanData['is_robot'],
        $scanData['session_id']
    );

    if ($insertStmt->execute()) {
        $logId = $conn->insert_id;
        logQRScan("Scan log inserted successfully with ID: " . $logId);
    } else {
        logQRScan("Error inserting scan log: " . $insertStmt->error);
        $logId = 0; // Set to 0 if insert failed
    }
    $insertStmt->close();

    // Update scan count in qr_codes table
    $updateStmt = $conn->prepare("UPDATE qr_codes SET count = count + 1 WHERE id = ?");
    if (!$updateStmt) {
        logQRScan("Prepare failed for UPDATE count: " . $conn->error);
    } else {
        $updateStmt->bind_param("i", $id);
        if ($updateStmt->execute()) {
            logQRScan("Scan count updated successfully");
        } else {
            logQRScan("Error updating scan count: " . $updateStmt->error);
        }
        $updateStmt->close();
    }

    // Check if final URL is valid
    if (empty($qrData['finalurl'])) {
        logQRScan("Error: Final URL is empty for QR ID: " . $id);
        die("QR Code has no destination URL configured");
    }

    if (!filter_var($qrData['finalurl'], FILTER_VALIDATE_URL)) {
        logQRScan("Error: Invalid final URL format: " . $qrData['finalurl']);
        die("Invalid destination URL format");
    }

    // Perform the redirect with JavaScript to capture screen resolution and duration
    logQRScan("Redirecting to: " . $qrData['finalurl']);

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Redirecting...</title>
        <script>
            // Capture screen resolution and scan duration
            const startTime = Date.now();
            const screenRes = `${screen.width}x${screen.height}`;

            // Send data to server before redirect
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_scan_log.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                // Redirect after data is sent
                window.location.href = '<?php echo htmlspecialchars($qrData['finalurl']); ?>';
            };
            xhr.onerror = function() {
                // Redirect even if data sending fails
                window.location.href = '<?php echo htmlspecialchars($qrData['finalurl']); ?>';
            };

            // Calculate duration and send
            const duration = (Date.now() - startTime) / 1000;
            xhr.send(`log_id=<?php echo $logId ?? 0; ?>&duration=${duration}&screen=${screenRes}`);

            // Fallback redirect
            setTimeout(function() {
                window.location.href = '<?php echo htmlspecialchars($qrData['finalurl']); ?>';
            }, 1000);
        </script>
        <meta http-equiv="refresh" content="1;url=<?php echo htmlspecialchars($qrData['finalurl']); ?>">
    </head>
    <body>
        <p>Redirecting to <a href="<?php echo htmlspecialchars($qrData['finalurl']); ?>"><?php echo htmlspecialchars($qrData['finalurl']); ?></a></p>
        <p>Capturing device information... Please wait.</p>
    </body>
    </html>
    <?php
    exit;

} catch (Exception $e) {
    logQRScan("Exception: " . $e->getMessage());
    die("Error processing QR code: " . $e->getMessage());
}
?>