<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filename']) && isset($_POST['data'])) {
    $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_POST['filename']);
    $data = $_POST['data'];

    // Create directory if it doesn't exist
    $qrCodeDir = 'assets/qrcodes/';
    if (!file_exists($qrCodeDir)) {
        if (!mkdir($qrCodeDir, 0755, true)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create directory']);
            exit;
        }
    }

    // Extract base64 image data
    if (preg_match('/^data:image\/(\w+);base64,/', $data, $matches)) {
        $type = $matches[1];
        $data = substr($data, strpos($data, ',') + 1);
        $data = base64_decode($data);

        if ($data !== false) {
            $filePath = 'assets/qrcodes/' . $filename . '.' . $type;
            if (file_put_contents($filePath, $data) !== false) {
                echo json_encode(['success' => true, 'path' => $filePath]);
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save file']);
                exit;
            }
        }
    }

    echo json_encode(['success' => false, 'message' => 'Invalid image data']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>