<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

// Log the update attempt
error_log("Update scan log called with POST: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $logId = isset($_POST['log_id']) ? (int)$_POST['log_id'] : 0;
    $duration = isset($_POST['duration']) ? (float)$_POST['duration'] : 0;
    $screen = isset($_POST['screen']) ? sanitizeInput($_POST['screen']) : 'Unknown';

    if ($logId > 0) {
        // Check if connection exists
        if (!$conn) {
            error_log("Database connection failed in update_scan_log.php");
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }

        $updateStmt = $conn->prepare("UPDATE qr_scan_logs SET scan_duration = ?, screen_resolution = ? WHERE id = ?");

        if (!$updateStmt) {
            error_log("Error preparing update statement: " . $conn->error);
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }

        $updateStmt->bind_param("dsi", $duration, $screen, $logId);

        if ($updateStmt->execute()) {
            error_log("Scan log updated successfully for ID: " . $logId);
            echo json_encode(['success' => true, 'message' => 'Scan log updated']);
        } else {
            error_log("Error executing update: " . $updateStmt->error);
            echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $updateStmt->error]);
        }

        $updateStmt->close();
    } else {
        error_log("Invalid log ID received: " . $logId);
        echo json_encode(['success' => false, 'message' => 'Invalid log ID']);
    }
} else {
    error_log("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>