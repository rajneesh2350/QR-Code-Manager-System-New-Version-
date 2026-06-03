<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h1>QR Code Generation Test</h1>";

// Test 1: Check GD library
echo "<h2>1. GD Library Check</h2>";
if (extension_loaded('gd')) {
    echo "✅ GD Library is loaded<br>";
    $gdInfo = gd_info();
    echo "GD Version: " . $gdInfo['GD Version'] . "<br>";
    echo "PNG Support: " . ($gdInfo['PNG Support'] ? '✅' : '❌') . "<br>";
    echo "JPEG Support: " . ($gdInfo['JPEG Support'] ? '✅' : '❌') . "<br>";
    echo "FreeType Support: " . ($gdInfo['FreeType Support'] ? '✅' : '❌') . "<br>";
} else {
    echo "❌ GD Library is NOT loaded<br>";
}

// Test 2: Directory permissions
echo "<h2>2. Directory Permissions</h2>";
$dirs = [
    'assets/qrcodes/' => 'QR Codes Directory',
    'assets/' => 'Assets Directory',
    '.' => 'Current Directory'
];

foreach ($dirs as $dir => $label) {
    if (!file_exists($dir)) {
        echo "❌ $label: Does not exist<br>";
        if ($dir == 'assets/qrcodes/') {
            if (mkdir($dir, 0755, true)) {
                echo "   Created successfully<br>";
            }
        }
    } else {
        echo "✅ $label: Exists<br>";
        echo "   Writable: " . (is_writable($dir) ? '✅' : '❌') . "<br>";
        echo "   Readable: " . (is_readable($dir) ? '✅' : '❌') . "<br>";
    }
}

// Test 3: Logo download
echo "<h2>3. Logo Download Test</h2>";
$logoPath = getLogoPath();
if ($logoPath && file_exists($logoPath)) {
    echo "✅ Logo downloaded successfully: $logoPath<br>";
    echo "File size: " . filesize($logoPath) . " bytes<br>";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($logoPath)) . "<br>";

    // Check if it's a valid image
    $imgInfo = getimagesize($logoPath);
    if ($imgInfo) {
        echo "Image dimensions: {$imgInfo[0]} x {$imgInfo[1]} pixels<br>";
        echo "MIME type: {$imgInfo['mime']}<br>";
    }
} else {
    echo "❌ Failed to download logo<br>";
}

// Test 4: Generate test QR
echo "<h2>4. Generate Test QR Code</h2>";
$testUrl = "https://example.com/test";
$testFilename = "test_" . time();

// Without logo
$qrWithoutLogo = generateQRCode($testUrl, $testFilename . "_nologo");
if ($qrWithoutLogo && file_exists($qrWithoutLogo)) {
    echo "✅ QR without logo generated: $qrWithoutLogo<br>";
    echo "<img src='$qrWithoutLogo' style='border:1px solid #ccc; margin:10px;' width='150'><br>";
} else {
    echo "❌ Failed to generate QR without logo<br>";
}

// With logo
if (extension_loaded('gd')) {
    $qrWithLogo = generateQRCode($testUrl, $testFilename . "_withlogo", $logoPath);
    if ($qrWithLogo && file_exists($qrWithLogo)) {
        echo "✅ QR with logo generated: $qrWithLogo<br>";
        echo "<img src='$qrWithLogo' style='border:1px solid #ccc; margin:10px;' width='150'><br>";
    } else {
        echo "❌ Failed to generate QR with logo<br>";
    }
} else {
    echo "⚠️ Skipping logo test because GD is not available<br>";
}

// Test 5: API connectivity
echo "<h2>5. API Connectivity Test</h2>";
$apis = [
    'QR Server' => 'https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=test',
    'Google Charts' => 'https://chart.googleapis.com/chart?chs=100x100&cht=qr&chl=test',
    'QuickChart' => 'https://quickchart.io/qr?text=test&size=100'
];

foreach ($apis as $name => $url) {
    $start = microtime(true);
    $data = downloadFile($url);
    $time = round((microtime(true) - $start) * 1000);

    if ($data && strlen($data) > 100) {
        echo "✅ $name: Working (response time: {$time}ms)<br>";
    } else {
        echo "❌ $name: Failed (response time: {$time}ms)<br>";
    }
}

// Test 6: Database connection
echo "<h2>6. Database Connection</h2>";
if ($conn && $conn->ping()) {
    echo "✅ Database connected successfully<br>";
    $result = $conn->query("SELECT COUNT(*) as count FROM qr_codes");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "Total QR codes in database: " . $row['count'] . "<br>";
    }
} else {
    echo "❌ Database connection failed<br>";
}
?>