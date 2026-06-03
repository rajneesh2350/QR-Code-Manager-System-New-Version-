<?php
require_once 'includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Empty both tables permanently
    $success1 = $conn->query("TRUNCATE TABLE qr_code_coupon");
    $success2 = $conn->query("TRUNCATE TABLE qr_scan_logs_coupon");

    if ($success1 && $success2) {
        echo json_encode(['success' => true, 'message' => 'All coupon scan records have been perfectly reset!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to reset tables: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
}
?>