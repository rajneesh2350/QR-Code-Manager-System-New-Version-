<?php
require_once 'includes/config.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Safely grab the ID
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    if ($id > 0) {
        // Get image path before deleting so we can remove the actual file
        $stmt = $conn->prepare("SELECT qrimage FROM qr_codes WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // Delete from database
        $deleteStmt = $conn->prepare("DELETE FROM qr_codes WHERE id = ?");
        $deleteStmt->bind_param("i", $id);

        if ($deleteStmt->execute()) {
            // Delete QR image file from the server
            if ($row && !empty($row['qrimage']) && file_exists($row['qrimage'])) {
                unlink($row['qrimage']);
            }
            echo json_encode(['success' => true, 'message' => 'QR code deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete QR code from the database.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid QR Code ID provided.']);
    }
} else {
    // If someone tries to load this file directly in the browser (GET request)
    echo json_encode(['success' => false, 'message' => 'Invalid request method. POST required.']);
}
?>