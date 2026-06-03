<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)$_POST['id'];
    $qrname = sanitizeInput($_POST['qrname']);
    $finalurl = sanitizeInput($_POST['finalurl']);

    if (empty($qrname) || empty($finalurl)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    if (!validateUrl($finalurl)) {
        echo json_encode(['success' => false, 'message' => 'Invalid URL format']);
        exit;
    }

    // Check if QR name exists for other records
    $checkStmt = $conn->prepare("SELECT id FROM qr_codes WHERE qrname = ? AND id != ?");
    $checkStmt->bind_param("si", $qrname, $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'QR name already exists']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE qr_codes SET qrname = ?, finalurl = ? WHERE id = ?");
    $stmt->bind_param("ssi", $qrname, $finalurl, $id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'QR code updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update QR code']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM qr_codes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        echo json_encode(['error' => 'QR code not found']);
    }
}
?>