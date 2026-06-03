<?php
require_once 'config.php';
require_once 'encryption.php';

/**
 * Generate QR code using QR Server API (most reliable)
 * @param string $url URL to encode in QR
 * @param string $filename Filename for the QR code
 * @param string $logoPath Path to logo file (optional)
 * @return string Path to generated QR code
 */
function generateQRCode($url, $filename, $logoPath = null) {
    $qrCodeDir = 'assets/qrcodes/';

    // Create directory if it doesn't exist
    if (!file_exists($qrCodeDir)) {
        if (!mkdir($qrCodeDir, 0755, true)) {
            error_log("Failed to create QR code directory: " . $qrCodeDir);
            return false;
        }
    }

    // Check if directory is writable
    if (!is_writable($qrCodeDir)) {
        error_log("QR code directory not writable: " . $qrCodeDir);
        @chmod($qrCodeDir, 0755);
        if (!is_writable($qrCodeDir)) {
            return false;
        }
    }

    $filePath = $qrCodeDir . $filename . '.png';

    // First generate the QR code
    if (generateQRCodeWithAPI($url, $filePath)) {

        // If a logo is provided, process it safely
        if ($logoPath) {
            $localLogo = $logoPath;

            // If the logo is an external URL, we MUST download it locally first
            if (filter_var($logoPath, FILTER_VALIDATE_URL)) {
                $cacheDir = 'assets/logos/';

                // Create a cache directory for logos to speed up future generations
                if (!file_exists($cacheDir)) {
                    @mkdir($cacheDir, 0755, true);
                }

                $localLogo = $cacheDir . md5($logoPath) . '.png';

                // Download and cache the logo if we haven't already done it recently
                if (!file_exists($localLogo) || (time() - filemtime($localLogo) > 86400)) {
                    $logoData = downloadFile($logoPath);
                    if ($logoData) {
                        file_put_contents($localLogo, $logoData);
                    } else {
                        $localLogo = null; // Fallback to no logo if download fails
                    }
                }
            }

            // Apply the properly cached local logo file
            if ($localLogo && file_exists($localLogo)) {
                addLogoToQR($filePath, $localLogo);
            }
        }

        return 'assets/qrcodes/' . $filename . '.png';
    }

    return false;
}

/**
 * Generate QR code with logo
 */
function generateQRCodeWithLogo($url, $filename, $logoPath) {
    return generateQRCode($url, $filename, $logoPath);
}

/**
 * Generate QR code using API
 */
function generateQRCodeWithAPI($url, $filePath) {
    // Try multiple APIs in order of reliability

    // 1. QR Server API (most reliable with good quality)
    $apis = [
        [
            'url' => 'https://api.qrserver.com/v1/create-qr-code/?size=500x500&ecc=H&margin=10&data=' . urlencode($url),
            'min_size' => 1000
        ],
        [
            'url' => 'https://chart.googleapis.com/chart?chs=500x500&cht=qr&chl=' . urlencode($url) . '&choe=UTF-8&chld=H|4',
            'min_size' => 500
        ],
        [
            'url' => 'https://quickchart.io/qr?text=' . urlencode($url) . '&size=500&ecLevel=H&margin=1',
            'min_size' => 500
        ]
    ];

    foreach ($apis as $api) {
        $qrImage = downloadFile($api['url']);
        if ($qrImage && strlen($qrImage) > $api['min_size']) {
            if (file_put_contents($filePath, $qrImage) !== false) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Download file using cURL or file_get_contents
 */
function downloadFile($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 && $data) {
            return $data;
        }
    }

    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        return @file_get_contents($url, false, $context);
    }

    return false;
}

/**
 * Add logo to QR code
 */
function addLogoToQR($qrPath, $logoPath) {
    if (!file_exists($qrPath) || !file_exists($logoPath)) {
        return $qrPath;
    }

    try {
        // Create image resources
        $qrImage = imagecreatefrompng($qrPath);
        if (!$qrImage) {
            return $qrPath;
        }

        // Get logo image based on file type
        $logoInfo = getimagesize($logoPath);
        $logoType = $logoInfo[2];

        switch ($logoType) {
            case IMAGETYPE_PNG:
                $logoImage = imagecreatefrompng($logoPath);
                break;
            case IMAGETYPE_JPEG:
                $logoImage = imagecreatefromjpeg($logoPath);
                break;
            case IMAGETYPE_GIF:
                $logoImage = imagecreatefromgif($logoPath);
                break;
            default:
                return $qrPath;
        }

        if (!$logoImage) {
            return $qrPath;
        }

        // Calculate dimensions
        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);
        $logoWidth = imagesx($logoImage);
        $logoHeight = imagesy($logoImage);

        // Calculate logo size (20% of QR code)
        $logoNewWidth = (int)($qrWidth * 0.2);
        $logoNewHeight = (int)($logoHeight * ($logoNewWidth / $logoWidth));

        // Create a true color image for the resized logo
        $resizedLogo = imagecreatetruecolor($logoNewWidth, $logoNewHeight);

        // Preserve transparency
        imagealphablending($resizedLogo, false);
        imagesavealpha($resizedLogo, true);
        $transparent = imagecolorallocatealpha($resizedLogo, 255, 255, 255, 127);
        imagefilledrectangle($resizedLogo, 0, 0, $logoNewWidth, $logoNewHeight, $transparent);

        // Resize logo
        imagecopyresampled($resizedLogo, $logoImage, 0, 0, 0, 0,
                          $logoNewWidth, $logoNewHeight, $logoWidth, $logoHeight);

        // Calculate position (center)
        $destX = (int)(($qrWidth - $logoNewWidth) / 2);
        $destY = (int)(($qrHeight - $logoNewHeight) / 2);

        // Merge logo onto QR code
        imagecopyresampled($qrImage, $resizedLogo, $destX, $destY, 0, 0,
                          $logoNewWidth, $logoNewHeight, $logoNewWidth, $logoNewHeight);

        // Save the result
        imagepng($qrImage, $qrPath);

        // Clean up
        imagedestroy($qrImage);
        imagedestroy($logoImage);
        imagedestroy($resizedLogo);

        return $qrPath;

    } catch (Exception $e) {
        error_log("Error adding logo to QR: " . $e->getMessage());
        return $qrPath;
    }
}

/**
 * Get base URL of the application
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    $baseUrl = rtrim($protocol . $host . $scriptName, '/');

    // Remove any trailing slashes and ensure proper format
    $baseUrl = str_replace('\\', '/', $baseUrl);

    // If we're in a subdirectory, make sure the path is correct
    if (substr($baseUrl, -1) != '/') {
        $baseUrl .= '/';
    }

    return $baseUrl;
}

/**
 * Validate URL format
 */
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    global $conn;
    return $conn->real_escape_string(trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8')));
}

/**
 * Get time ago string
 */
function getTimeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;

    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);

    if ($seconds <= 60) {
        return "Just Now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "yesterday" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "1 month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "1 year ago" : "$years years ago";
    }
}

/**
 * Generate unique filename
 */
function generateUniqueFilename($prefix = 'qr') {
    return $prefix . '_' . uniqid() . '_' . time();
}

/**
 * Check if GD library is available for image processing
 */
function isGDAvailable() {
    return extension_loaded('gd') && function_exists('imagecreatefrompng');
}

/**
 * Download and cache logo
 */
function getLogoPath() {
    $logoPath = 'assets/igipesslogo1.png';

    // If logo doesn't exist or is older than 24 hours, download it
    if (!file_exists($logoPath) || (time() - filemtime($logoPath) > 86400)) {
        $logoData = downloadFile('https://igipess.du.ac.in/QR-Code/igipesslogo1.png');
        if ($logoData) {
            file_put_contents($logoPath, $logoData);
        }
    }

    return file_exists($logoPath) ? $logoPath : null;
}
?>