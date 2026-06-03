<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=qr_scans_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add headers
fputcsv($output, [
    'Scan Time',
    'QR Name',
    'Device Type',
    'OS',
    'OS Version',
    'Browser',
    'Browser Version',
    'Device Model',
    'Screen Resolution',
    'Language',
    'Country',
    'City',
    'Region',
    'ISP',
    'IP Address',
    'Referer',
    'Scan Duration (s)',
    'Is Mobile',
    'Is Tablet',
    'Is Desktop',
    'Is Robot',
    'Session ID'
]);

// Fetch all scan logs
$result = $conn->query("
    SELECT * FROM qr_scan_logs
    ORDER BY scan_time DESC
");

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['scan_time'],
        $row['qr_name'],
        $row['device_type'],
        $row['device_os'],
        $row['os_version'],
        $row['browser'],
        $row['browser_version'],
        $row['device_model'],
        $row['screen_resolution'],
        $row['language'],
        $row['country'],
        $row['city'],
        $row['region'],
        $row['isp'],
        $row['ip_address'],
        $row['referer'],
        $row['scan_duration'],
        $row['is_mobile'] ? 'Yes' : 'No',
        $row['is_tablet'] ? 'Yes' : 'No',
        $row['is_desktop'] ? 'Yes' : 'No',
        $row['is_robot'] ? 'Yes' : 'No',
        $row['session_id']
    ]);
}

fclose($output);
exit;
?>