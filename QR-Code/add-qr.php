<?php
require_once 'includes/config.php';
require_once 'includes/encryption.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qrname = sanitizeInput($_POST['qrname']);
    $finalurl = sanitizeInput($_POST['finalurl']);

    // Get the logo selection from the dropdown (defaults to 'none' if missing)
    $logoSelection = isset($_POST['logo_selection']) ? $_POST['logo_selection'] : 'none';

    // Log the request
    error_log("Adding QR code: name=$qrname, url=$finalurl, logoSelection=$logoSelection");

    // Validate inputs
    if (empty($qrname) || empty($finalurl)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit;
    }

    if (!validateUrl($finalurl)) {
        echo json_encode(['success' => false, 'message' => 'Invalid URL format']);
        exit;
    }

    // Check if QR name already exists
    $checkStmt = $conn->prepare("SELECT id FROM qr_codes WHERE qrname = ?");
    $checkStmt->bind_param("s", $qrname);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'QR name already exists. Please choose a different name.']);
        exit;
    }

    // FIXED: Insert the initial record with empty strings for url and qrimage to bypass strict database restrictions
    $stmt = $conn->prepare("INSERT INTO qr_codes (qrname, finalurl, count, url, qrimage) VALUES (?, ?, 0, '', '')");
    $stmt->bind_param("ss", $qrname, $finalurl);

    if ($stmt->execute()) {
        $id = $conn->insert_id;

        // Generate encrypted URL for the QR code tracking page
        $encryptedData = Encryption::encryptUrl($id);
        $baseUrl = getBaseUrl();
        $qrUrl = $baseUrl . "qr-page.php?data=" . urlencode($encryptedData);

        // Generate unique filename for the image
        $filename = 'qr_' . $id . '_' . time() . '.png';

        // Assign the correct logo path based on the user's dropdown selection
        $logoPath = null;
        if ($logoSelection !== 'none') {
            $availableLogos = [
                'igipess'   => 'https://igipess.du.ac.in/QR-Code/igipesslogo1.png',
                'youtube'   => 'https://igipess.du.ac.in/QR-Code/youtube.png',
                'facebook'  => 'https://igipess.du.ac.in/QR-Code/facebook.png',
                'twitter'   => 'https://igipess.du.ac.in/QR-Code/tweeter.png',
                'instagram' => 'https://igipess.du.ac.in/QR-Code/instagram.png'
            ];

            // If the selection exists in our array, grab the URL
            if (array_key_exists($logoSelection, $availableLogos)) {
                $logoPath = $availableLogos[$logoSelection];
            }
        }

        // Generate QR code with or without the selected logo
        $qrImagePath = generateQRCode($qrUrl, $filename, $logoPath);

        if ($qrImagePath && file_exists($qrImagePath) && filesize($qrImagePath) > 0) {
            // Update QR image path and tracking URL in database now that we have them
            $updateStmt = $conn->prepare("UPDATE qr_codes SET qrimage = ?, url = ? WHERE id = ?");
            $updateStmt->bind_param("ssi", $qrImagePath, $qrUrl, $id);

            if ($updateStmt->execute()) {
                $message = 'QR code generated successfully';
                if ($logoPath) {
                    $message .= ' with selected logo';
                }
                echo json_encode(['success' => true, 'message' => $message]);
            } else {
                echo json_encode(['success' => true, 'message' => 'QR code created but image path not updated in DB']);
            }
        } else {
            // Delete the database entry if QR physical image generation failed
            $conn->query("DELETE FROM qr_codes WHERE id = $id");
            echo json_encode(['success' => false, 'message' => 'Failed to generate QR code image. Please check server configuration.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create QR code: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Must be POST.']);
}
?>