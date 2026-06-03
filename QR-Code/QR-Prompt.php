make qr-code dashboard using, PHP,HTML,Mysqli, JS,CSS,Fafa Icon, Graph, Search,Sweet Alert wherever needed which organized the following :
create a mysql table where id (with auto increment Unique) ,qrname,url,finalurl,qrimage,count,created_at,updated_at

1. Make a QR-Code based on URL given (encrypt it, so no one know our domain url for sequirety reason)
2. When user Scan QR-Code (decrypt it) and redirect to the specific URL (given in the table field as finalurl)
3. logic :
QRName is the name which is readable by user and shown to user under QR-Image like ATTENDANCE,url like https://igipess.du.ac.in/QR-Page.php?id=1 or ?id=2 or ?id=3 ...... (system encrypt it, so no one know our domain url for security reason),now QR-Page.php find from qr-code table id=1 (before finding in table decrypt it, to find it correctly in table) and get all fields info and then work accordingly like how many time user scan a particular qr (?id=1)
4. Make full user friendly CRUD operation for the same, professional and perfect for handling all above

qr-dashboard/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── script.js
│   └── images/
├── includes/
│   ├── config.php
│   ├── encryption.php
│   └── functions.php
├── qr-page.php
├── index.php
├── add-qr.php
├── edit-qr.php
├── delete-qr.php
├── get-qr-stats.php
└── .htaccess

Report/Graph must be created from qr_scan_logs table structure,
it is shown blank update this analystics.php from all field use in table Google Graph dynamic etc...
Recent Scans (Last 100), This section should be appear as a search page with 1-10 paginations, for better user friendly and professional way

I'll provide the complete updated `analytics.php` file with all the requested changes, including the paginated search interface and all charts.

```php
<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get filter parameters for charts
$filter_qr = isset($_GET['qr_id']) ? (int)$_GET['qr_id'] : 0;
$filter_device = isset($_GET['device_type']) ? sanitizeInput($_GET['device_type']) : '';
$filter_country = isset($_GET['country']) ? sanitizeInput($_GET['country']) : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-30 days'));
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$filter_os = isset($_GET['os']) ? sanitizeInput($_GET['os']) : '';
$filter_browser = isset($_GET['browser']) ? sanitizeInput($_GET['browser']) : '';

// Pagination and search parameters for the scans table
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Search parameters for the scans table
$search_qr = isset($_GET['search_qr']) ? sanitizeInput($_GET['search_qr']) : '';
$search_device = isset($_GET['search_device']) ? sanitizeInput($_GET['search_device']) : '';
$search_country = isset($_GET['search_country']) ? sanitizeInput($_GET['search_country']) : '';
$search_city = isset($_GET['search_city']) ? sanitizeInput($_GET['search_city']) : '';
$search_ip = isset($_GET['search_ip']) ? sanitizeInput($_GET['search_ip']) : '';
$search_date = isset($_GET['search_date']) ? $_GET['search_date'] : '';
$search_os = isset($_GET['search_os']) ? sanitizeInput($_GET['search_os']) : '';
$search_browser = isset($_GET['search_browser']) ? sanitizeInput($_GET['search_browser']) : '';

// Build WHERE clause for charts
$where_conditions = ["scan_time BETWEEN '$filter_date_from 00:00:00' AND '$filter_date_to 23:59:59'"];

if ($filter_qr > 0) {
    $where_conditions[] = "qr_id = $filter_qr";
}
if (!empty($filter_device)) {
    $where_conditions[] = "device_type = '" . $conn->real_escape_string($filter_device) . "'";
}
if (!empty($filter_country)) {
    $where_conditions[] = "country = '" . $conn->real_escape_string($filter_country) . "'";
}
if (!empty($filter_os)) {
    $where_conditions[] = "device_os = '" . $conn->real_escape_string($filter_os) . "'";
}
if (!empty($filter_browser)) {
    $where_conditions[] = "browser = '" . $conn->real_escape_string($filter_browser) . "'";
}

$where_clause = implode(" AND ", $where_conditions);

// Build WHERE clause for table search
$table_conditions = ["1=1"];

if (!empty($search_qr)) {
    $table_conditions[] = "qr_name LIKE '%" . $conn->real_escape_string($search_qr) . "%'";
}
if (!empty($search_device)) {
    $table_conditions[] = "device_type = '" . $conn->real_escape_string($search_device) . "'";
}
if (!empty($search_country)) {
    $table_conditions[] = "country LIKE '%" . $conn->real_escape_string($search_country) . "%'";
}
if (!empty($search_city)) {
    $table_conditions[] = "city LIKE '%" . $conn->real_escape_string($search_city) . "%'";
}
if (!empty($search_ip)) {
    $table_conditions[] = "ip_address LIKE '%" . $conn->real_escape_string($search_ip) . "%'";
}
if (!empty($search_os)) {
    $table_conditions[] = "device_os LIKE '%" . $conn->real_escape_string($search_os) . "%'";
}
if (!empty($search_browser)) {
    $table_conditions[] = "browser LIKE '%" . $conn->real_escape_string($search_browser) . "%'";
}
if (!empty($search_date)) {
    $table_conditions[] = "DATE(scan_time) = '" . $conn->real_escape_string($search_date) . "'";
}

$table_where_clause = implode(" AND ", $table_conditions);

// Get all QR codes for filter dropdown
$qr_codes = $conn->query("SELECT id, qrname FROM qr_codes ORDER BY qrname");

// Get unique values for filters
$device_types = $conn->query("SELECT DISTINCT device_type FROM qr_scan_logs WHERE device_type != 'Unknown' ORDER BY device_type");
$countries = $conn->query("SELECT DISTINCT country FROM qr_scan_logs WHERE country != 'Unknown' AND country != 'Local' ORDER BY country");
$oses = $conn->query("SELECT DISTINCT device_os FROM qr_scan_logs WHERE device_os != 'Unknown' ORDER BY device_os");
$browsers = $conn->query("SELECT DISTINCT browser FROM qr_scan_logs WHERE browser != 'Unknown' ORDER BY browser");

// Get unique values for table search filters
$search_device_types = $conn->query("SELECT DISTINCT device_type FROM qr_scan_logs WHERE device_type != 'Unknown' ORDER BY device_type");
$search_countries = $conn->query("SELECT DISTINCT country FROM qr_scan_logs WHERE country != 'Unknown' AND country != 'Local' ORDER BY country");
$search_cities = $conn->query("SELECT DISTINCT city FROM qr_scan_logs WHERE city != 'Unknown' AND city != 'Local' ORDER BY city LIMIT 100");
$search_oses = $conn->query("SELECT DISTINCT device_os FROM qr_scan_logs WHERE device_os != 'Unknown' ORDER BY device_os");
$search_browsers = $conn->query("SELECT DISTINCT browser FROM qr_scan_logs WHERE browser != 'Unknown' ORDER BY browser");

// Summary Statistics
$stats = [];

// Total scans in period
$result = $conn->query("SELECT COUNT(*) as total FROM qr_scan_logs WHERE $where_clause");
$stats['total_scans'] = $result->fetch_assoc()['total'];

// Unique visitors (by IP)
$result = $conn->query("SELECT COUNT(DISTINCT ip_address) as unique_visitors FROM qr_scan_logs WHERE $where_clause AND ip_address NOT IN ('::1', '127.0.0.1', 'UNKNOWN')");
$stats['unique_visitors'] = $result->fetch_assoc()['unique_visitors'];

// Average scan duration
$result = $conn->query("SELECT AVG(scan_duration) as avg_duration FROM qr_scan_logs WHERE $where_clause AND scan_duration > 0");
$stats['avg_duration'] = $result->fetch_assoc()['avg_duration'] ?? 0;

// Bounce rate (scans with duration < 2 seconds)
$result = $conn->query("SELECT
    COUNT(CASE WHEN scan_duration < 2 AND scan_duration > 0 THEN 1 END) as fast_scans,
    COUNT(CASE WHEN scan_duration > 0 THEN 1 END) as total_with_duration
    FROM qr_scan_logs WHERE $where_clause");
$row = $result->fetch_assoc();
$stats['bounce_rate'] = ($row['total_with_duration'] > 0) ? round(($row['fast_scans'] / $row['total_with_duration']) * 100, 1) : 0;

// Get total records for pagination
$total_records_query = "SELECT COUNT(*) as total FROM qr_scan_logs WHERE $table_where_clause";
$total_records_result = $conn->query($total_records_query);
$total_records = $total_records_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get paginated scans
$recent_scans = $conn->query("
    SELECT * FROM qr_scan_logs
    WHERE $table_where_clause
    ORDER BY scan_time DESC
    LIMIT $offset, $records_per_page
");

// Get data for Google Charts

// 1. Scans over time
$trend_data = [];
$result = $conn->query("
    SELECT
        DATE(scan_time) as date,
        COUNT(*) as scans,
        COUNT(DISTINCT ip_address) as unique_visitors
    FROM qr_scan_logs
    WHERE $where_clause
    GROUP BY DATE(scan_time)
    ORDER BY date
");
while($row = $result->fetch_assoc()) {
    $trend_data[] = $row;
}

// 2. Device distribution
$device_data = [];
$result = $conn->query("
    SELECT
        device_type,
        COUNT(*) as count
    FROM qr_scan_logs
    WHERE $where_clause
    GROUP BY device_type
    ORDER BY count DESC
");
while($row = $result->fetch_assoc()) {
    $device_data[] = $row;
}

// 3. Country distribution
$country_data = [];
$result = $conn->query("
    SELECT
        country,
        COUNT(*) as count
    FROM qr_scan_logs
    WHERE $where_clause AND country != 'Unknown' AND country != 'Local'
    GROUP BY country
    ORDER BY count DESC
    LIMIT 15
");
while($row = $result->fetch_assoc()) {
    $country_data[] = $row;
}

// 4. OS distribution
$os_data = [];
$result = $conn->query("
    SELECT
        device_os,
        COUNT(*) as count
    FROM qr_scan_logs
    WHERE $where_clause AND device_os != 'Unknown'
    GROUP BY device_os
    ORDER BY count DESC
");
while($row = $result->fetch_assoc()) {
    $os_data[] = $row;
}

// 5. Browser distribution
$browser_data = [];
$result = $conn->query("
    SELECT
        browser,
        COUNT(*) as count
    FROM qr_scan_logs
    WHERE $where_clause AND browser != 'Unknown'
    GROUP BY browser
    ORDER BY count DESC
");
while($row = $result->fetch_assoc()) {
    $browser_data[] = $row;
}

// 6. Hourly distribution
$hourly_data = array_fill(0, 24, 0);
$result = $conn->query("
    SELECT
        hour_of_day,
        COUNT(*) as count
    FROM qr_scan_logs
    WHERE $where_clause AND hour_of_day IS NOT NULL
    GROUP BY hour_of_day
    ORDER BY hour_of_day
");
while($row = $result->fetch_assoc()) {
    $hourly_data[$row['hour_of_day']] = $row['count'];
}

// 7. QR code performance
$qr_performance = [];
$result = $conn->query("
    SELECT
        qr_name,
        COUNT(*) as count
    FROM qr_scan_logs
    WHERE $where_clause
    GROUP BY qr_id, qr_name
    ORDER BY count DESC
    LIMIT 15
");
while($row = $result->fetch_assoc()) {
    $qr_performance[] = $row;
}

// 8. Day of week distribution
$dow_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$dow_data = array_fill_keys($dow_order, 0);
$result = $conn->query("
    SELECT
        day_of_week,
        COUNT(*) as count
    FROM qr_scan_logs
    WHERE $where_clause AND day_of_week IS NOT NULL
    GROUP BY day_of_week
");
while($row = $result->fetch_assoc()) {
    $dow_data[$row['day_of_week']] = $row['count'];
}

// 9. City distribution
$city_data = [];
$result = $conn->query("
    SELECT
        city,
        country,
        COUNT(*) as count
    FROM qr_scan_logs
    WHERE $where_clause AND city != 'Unknown' AND city != 'Local'
    GROUP BY city, country
    ORDER BY count DESC
    LIMIT 15
");
while($row = $result->fetch_assoc()) {
    $city_data[] = $row;
}

// 10. ISP distribution
$isp_data = [];
$result = $conn->query("
    SELECT
        isp,
        COUNT(*) as count
    FROM qr_scan_logs
    WHERE $where_clause AND isp != 'Unknown' AND isp != 'Local'
    GROUP BY isp
    ORDER BY count DESC
    LIMIT 15
");
while($row = $result->fetch_assoc()) {
    $isp_data[] = $row;
}

// 11. Mobile vs Desktop
$mobile_desktop = [];
$result = $conn->query("
    SELECT
        SUM(is_mobile) as mobile,
        SUM(is_desktop) as desktop,
        SUM(is_tablet) as tablet,
        SUM(is_robot) as robot
    FROM qr_scan_logs
    WHERE $where_clause
");
$mobile_desktop = $result->fetch_assoc();

// 12. Timezone distribution
$timezone_data = [];
$result = $conn->query("
    SELECT
        timezone,
        COUNT(*) as count
    FROM qr_scan_logs
    WHERE $where_clause AND timezone != 'Unknown'
    GROUP BY timezone
    ORDER BY count DESC
    LIMIT 10
");
while($row = $result->fetch_assoc()) {
    $timezone_data[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scan Analytics Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header h1 {
            font-size: 28px;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header h1 i {
            color: #667eea;
        }

        .date-range {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .date-input {
            padding: 10px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
        }

        /* Filter Bar */
        .filter-bar {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-item {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .filter-item label {
            font-size: 13px;
            font-weight: 600;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-item select,
        .filter-item input {
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            background: white;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #edf2f7;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .btn-info {
            background: #4299e1;
            color: white;
        }

        .btn-info:hover {
            background: #3182ce;
        }

        .btn-success {
            background: #48bb78;
            color: white;
        }

        .btn-success:hover {
            background: #38a169;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .stat-icon.primary { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .stat-icon.success { background: linear-gradient(135deg, #48bb78, #2ecc71); color: white; }
        .stat-icon.warning { background: linear-gradient(135deg, #ed8936, #f39c12); color: white; }
        .stat-icon.info { background: linear-gradient(135deg, #4299e1, #3498db); color: white; }

        .stat-details h3 {
            font-size: 15px;
            color: #718096;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .stat-details .number {
            font-size: 32px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 5px;
        }

        /* Chart Grid */
        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .chart-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f2f5;
        }

        .chart-header h3 {
            font-size: 18px;
            color: #2d3748;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .chart-header h3 i {
            color: #667eea;
        }

        .chart-container {
            height: 400px;
            position: relative;
        }

        /* Table Search Section */
        .table-search-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-top: 30px;
            margin-bottom: 20px;
        }

        .table-search-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .table-search-header h3 {
            font-size: 20px;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-search-header h3 i {
            color: #667eea;
        }

        .search-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .search-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .search-item label {
            font-size: 12px;
            font-weight: 600;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .search-item input,
        .search-item select {
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }

        .search-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .table-header h3 {
            font-size: 20px;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-header h3 i {
            color: #667eea;
        }

        .table-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .records-info {
            background: #edf2f7;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            color: #4a5568;
        }

        .table-wrapper {
            overflow-x: auto;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 15px;
            background: #f8fafc;
            color: #4a5568;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #edf2f7;
            color: #2d3748;
            font-size: 14px;
        }

        tr:hover td {
            background: #f8fafc;
        }

        .device-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }

        .device-mobile { background: #e3f2fd; color: #1976d2; }
        .device-desktop { background: #e8f5e8; color: #2e7d32; }
        .device-tablet { background: #fff3e0; color: #f57c00; }
        .device-robot { background: #fce4e4; color: #c62828; }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .page-item {
            list-style: none;
        }

        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 10px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            color: #4a5568;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s;
        }

        .page-link:hover {
            background: #edf2f7;
            border-color: #cbd5e0;
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
        }

        .page-item.disabled .page-link {
            background: #f7fafc;
            color: #cbd5e0;
            cursor: not-allowed;
            pointer-events: none;
        }

        .export-btn {
            background: #edf2f7;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4a5568;
            font-size: 14px;
            transition: all 0.3s;
        }

        .export-btn:hover {
            background: #e2e8f0;
        }

        .text-muted {
            color: #a0aec0;
        }

        .badge {
            background: #edf2f7;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: #4a5568;
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .chart-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                text-align: center;
            }

            .filter-actions {
                justify-content: center;
            }

            .search-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-chart-pie"></i>
                QR Scan Analytics Dashboard
            </h1>
            <div class="date-range">
                <input type="text" id="dateRange" class="date-input" placeholder="Select date range">
                <button class="btn btn-primary" onclick="applyDateRange()">
                    <i class="fas fa-sync-alt"></i> Apply
                </button>
            </div>
        </div>

        <!-- Filter Bar for Charts -->
        <div class="filter-bar">
            <form method="GET" action="analytics.php" id="filterForm">
                <div class="filter-grid">
                    <div class="filter-item">
                        <label><i class="fas fa-qrcode"></i> QR Code</label>
                        <select name="qr_id">
                            <option value="0">All QR Codes</option>
                            <?php while($qr = $qr_codes->fetch_assoc()): ?>
                                <option value="<?php echo $qr['id']; ?>" <?php echo $filter_qr == $qr['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($qr['qrname']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label><i class="fas fa-mobile-alt"></i> Device Type</label>
                        <select name="device_type">
                            <option value="">All Devices</option>
                            <?php while($device = $device_types->fetch_assoc()): ?>
                                <option value="<?php echo $device['device_type']; ?>" <?php echo $filter_device == $device['device_type'] ? 'selected' : ''; ?>>
                                    <?php echo $device['device_type']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label><i class="fas fa-globe"></i> Country</label>
                        <select name="country">
                            <option value="">All Countries</option>
                            <?php while($country = $countries->fetch_assoc()): ?>
                                <option value="<?php echo $country['country']; ?>" <?php echo $filter_country == $country['country'] ? 'selected' : ''; ?>>
                                    <?php echo $country['country']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label><i class="fas fa-desktop"></i> OS</label>
                        <select name="os">
                            <option value="">All OS</option>
                            <?php while($os = $oses->fetch_assoc()): ?>
                                <option value="<?php echo $os['device_os']; ?>" <?php echo $filter_os == $os['device_os'] ? 'selected' : ''; ?>>
                                    <?php echo $os['device_os']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label><i class="fas fa-globe"></i> Browser</label>
                        <select name="browser">
                            <option value="">All Browsers</option>
                            <?php while($browser = $browsers->fetch_assoc()): ?>
                                <option value="<?php echo $browser['browser']; ?>" <?php echo $filter_browser == $browser['browser'] ? 'selected' : ''; ?>>
                                    <?php echo $browser['browser']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-item">
                        <label><i class="fas fa-calendar"></i> From</label>
                        <input type="date" name="date_from" value="<?php echo $filter_date_from; ?>">
                    </div>
                    <div class="filter-item">
                        <label><i class="fas fa-calendar"></i> To</label>
                        <input type="date" name="date_to" value="<?php echo $filter_date_to; ?>">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="button" class="btn btn-secondary" onclick="resetFilters()">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="fas fa-qrcode"></i>
                </div>
                <div class="stat-details">
                    <h3>Total Scans</h3>
                    <div class="number"><?php echo number_format($stats['total_scans']); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details">
                    <h3>Unique Visitors</h3>
                    <div class="number"><?php echo number_format($stats['unique_visitors']); ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-details">
                    <h3>Avg Duration</h3>
                    <div class="number"><?php echo number_format($stats['avg_duration'], 2); ?>s</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-details">
                    <h3>Bounce Rate</h3>
                    <div class="number"><?php echo $stats['bounce_rate']; ?>%</div>
                </div>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="chart-grid">
            <!-- Scans Over Time -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-line"></i> Scans Over Time</h3>
                </div>
                <div class="chart-container" id="trend_chart"></div>
            </div>

            <!-- Device Distribution -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-mobile-alt"></i> Device Distribution</h3>
                </div>
                <div class="chart-container" id="device_chart"></div>
            </div>
        </div>

        <div class="chart-grid">
            <!-- Top Countries -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-globe"></i> Top Countries</h3>
                </div>
                <div class="chart-container" id="country_chart"></div>
            </div>

            <!-- OS Distribution -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-desktop"></i> Operating Systems</h3>
                </div>
                <div class="chart-container" id="os_chart"></div>
            </div>
        </div>

        <div class="chart-grid">
            <!-- Browser Distribution -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-globe"></i> Browsers</h3>
                </div>
                <div class="chart-container" id="browser_chart"></div>
            </div>

            <!-- Hourly Distribution -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-clock"></i> Hourly Distribution</h3>
                </div>
                <div class="chart-container" id="hourly_chart"></div>
            </div>
        </div>

        <div class="chart-grid">
            <!-- QR Performance -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-qrcode"></i> QR Code Performance</h3>
                </div>
                <div class="chart-container" id="qr_chart"></div>
            </div>

            <!-- Day of Week -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-calendar-week"></i> Day of Week</h3>
                </div>
                <div class="chart-container" id="dow_chart"></div>
            </div>
        </div>

        <div class="chart-grid">
            <!-- Top Cities -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-city"></i> Top Cities</h3>
                </div>
                <div class="chart-container" id="city_chart"></div>
            </div>

            <!-- ISP Distribution -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-network-wired"></i> ISP Distribution</h3>
                </div>
                <div class="chart-container" id="isp_chart"></div>
            </div>
        </div>

        <div class="chart-grid">
            <!-- Mobile vs Desktop -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-pie"></i> Device Categories</h3>
                </div>
                <div class="chart-container" id="mobile_chart"></div>
            </div>

            <!-- Timezone Distribution -->
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-clock"></i> Timezone Distribution</h3>
                </div>
                <div class="chart-container" id="timezone_chart"></div>
            </div>
        </div>

        <!-- Geo Chart -->
        <div class="chart-grid">
            <div class="chart-card" style="grid-column: span 2;">
                <div class="chart-header">
                    <h3><i class="fas fa-map-marked-alt"></i> Geographic Distribution</h3>
                </div>
                <div class="chart-container" id="geo_chart"></div>
            </div>
        </div>

        <!-- Search Section for Scans Table -->
        <div class="table-search-section">
            <div class="table-search-header">
                <h3>
                    <i class="fas fa-search"></i>
                    Search Scans
                </h3>
                <div class="records-info">
                    <i class="fas fa-database"></i> Total Records: <?php echo number_format($total_records); ?>
                </div>
            </div>

            <form method="GET" action="analytics.php" id="searchForm">
                <!-- Preserve chart filters -->
                <input type="hidden" name="qr_id" value="<?php echo $filter_qr; ?>">
                <input type="hidden" name="device_type" value="<?php echo $filter_device; ?>">
                <input type="hidden" name="country" value="<?php echo $filter_country; ?>">
                <input type="hidden" name="os" value="<?php echo $filter_os; ?>">
                <input type="hidden" name="browser" value="<?php echo $filter_browser; ?>">
                <input type="hidden" name="date_from" value="<?php echo $filter_date_from; ?>">
                <input type="hidden" name="date_to" value="<?php echo $filter_date_to; ?>">

                <div class="search-grid">
                    <div class="search-item">
                        <label><i class="fas fa-qrcode"></i> QR Name</label>
                        <input type="text" name="search_qr" placeholder="Search QR..." value="<?php echo htmlspecialchars($search_qr); ?>">
                    </div>
                    <div class="search-item">
                        <label><i class="fas fa-mobile-alt"></i> Device</label>
                        <select name="search_device">
                            <option value="">All Devices</option>
                            <?php while($device = $search_device_types->fetch_assoc()): ?>
                                <option value="<?php echo $device['device_type']; ?>" <?php echo $search_device == $device['device_type'] ? 'selected' : ''; ?>>
                                    <?php echo $device['device_type']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="search-item">
                        <label><i class="fas fa-globe"></i> Country</label>
                        <select name="search_country">
                            <option value="">All Countries</option>
                            <?php while($country = $search_countries->fetch_assoc()): ?>
                                <option value="<?php echo $country['country']; ?>" <?php echo $search_country == $country['country'] ? 'selected' : ''; ?>>
                                    <?php echo $country['country']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="search-item">
                        <label><i class="fas fa-city"></i> City</label>
                        <input type="text" name="search_city" placeholder="Search city..." value="<?php echo htmlspecialchars($search_city); ?>">
                    </div>
                    <div class="search-item">
                        <label><i class="fas fa-desktop"></i> OS</label>
                        <input type="text" name="search_os" placeholder="Search OS..." value="<?php echo htmlspecialchars($search_os); ?>">
                    </div>
                    <div class="search-item">
                        <label><i class="fas fa-globe"></i> Browser</label>
                        <input type="text" name="search_browser" placeholder="Search browser..." value="<?php echo htmlspecialchars($search_browser); ?>">
                    </div>
                    <div class="search-item">
                        <label><i class="fas fa-network-wired"></i> IP Address</label>
                        <input type="text" name="search_ip" placeholder="Search IP..." value="<?php echo htmlspecialchars($search_ip); ?>">
                    </div>
                    <div class="search-item">
                        <label><i class="fas fa-calendar"></i> Date</label>
                        <input type="date" name="search_date" value="<?php echo $search_date; ?>">
                    </div>
                </div>
                <div class="search-actions">
                    <button type="button" class="btn btn-secondary" onclick="resetTableSearch()">
                        <i class="fas fa-undo"></i> Clear Search
                    </button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Scans Table with Pagination -->
        <div class="table-container">
            <div class="table-header">
                <h3>
                    <i class="fas fa-history"></i>
                    Scan History
                </h3>
                <div class="table-info">
                    <span class="records-info">
                        Showing <?php echo min($offset + 1, $total_records); ?> - <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo number_format($total_records); ?>
                    </span>
                    <button class="export-btn" onclick="exportToCSV()">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>QR Code</th>
                            <th>Device</th>
                            <th>OS</th>
                            <th>Browser</th>
                            <th>Location</th>
                            <th>IP Address</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_scans && $recent_scans->num_rows > 0): ?>
                            <?php while($scan = $recent_scans->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i:s', strtotime($scan['scan_time'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($scan['qr_name']); ?></strong>
                                </td>
                                <td>
                                    <span class="device-badge device-<?php
                                        echo strtolower($scan['device_type']);
                                    ?>">
                                        <i class="fas fa-<?php
                                            echo $scan['is_mobile'] ? 'mobile-alt' :
                                                ($scan['is_tablet'] ? 'tablet-alt' :
                                                ($scan['is_desktop'] ? 'desktop' : 'robot'));
                                        ?>"></i>
                                        <?php echo htmlspecialchars($scan['device_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($scan['device_os'])): ?>
                                        <i class="fas fa-<?php
                                            echo strpos(strtolower($scan['device_os']), 'android') !== false ? 'android' :
                                                (strpos(strtolower($scan['device_os']), 'ios') !== false ? 'apple' :
                                                (strpos(strtolower($scan['device_os']), 'windows') !== false ? 'windows' : 'desktop'));
                                        ?>"></i>
                                        <?php echo htmlspecialchars($scan['device_os'] . ' ' . $scan['os_version']); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($scan['browser'])): ?>
                                        <i class="fas fa-<?php
                                            echo strpos(strtolower($scan['browser']), 'chrome') !== false ? 'chrome' :
                                                (strpos(strtolower($scan['browser']), 'firefox') !== false ? 'firefox' :
                                                (strpos(strtolower($scan['browser']), 'safari') !== false ? 'safari' :
                                                (strpos(strtolower($scan['browser']), 'edge') !== false ? 'edge' : 'globe')));
                                        ?>"></i>
                                        <?php echo htmlspecialchars($scan['browser'] . ' ' . $scan['browser_version']); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($scan['country'] != 'Unknown' && $scan['country'] != 'Local'): ?>
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($scan['city'] . ', ' . $scan['country']); ?>
                                        <?php if (!empty($scan['timezone']) && $scan['timezone'] != 'Unknown'): ?>
                                            <br><small class="text-muted"><?php echo $scan['timezone']; ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Unknown</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $ip = $scan['ip_address'];
                                    if ($ip != '::1' && $ip != '127.0.0.1' && $ip != 'UNKNOWN') {
                                        echo htmlspecialchars($ip);
                                    } else {
                                        echo '<span class="text-muted">Local</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($scan['scan_duration'] > 0): ?>
                                        <?php echo number_format($scan['scan_duration'], 2); ?>s
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-inbox fa-3x" style="color: #cbd5e0; margin-bottom: 10px;"></i>
                                    <p style="color: #718096;">No scan data available for the selected filters</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <!-- Previous page -->
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo ($page - 1); ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="page-link">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php else: ?>
                    <span class="page-link disabled">
                        <i class="fas fa-chevron-left"></i>
                    </span>
                <?php endif; ?>

                <!-- Page numbers -->
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);

                if ($start_page > 1) {
                    echo '<a href="?page=1&' . http_build_query(array_merge($_GET, ['page' => 1])) . '" class="page-link">1</a>';
                    if ($start_page > 2) {
                        echo '<span class="page-link disabled">...</span>';
                    }
                }

                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"
                       class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span class="page-link disabled">...</span>
                    <?php endif; ?>
                    <a href="?page=<?php echo $total_pages; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" class="page-link">
                        <?php echo $total_pages; ?>
                    </a>
                <?php endif; ?>

                <!-- Next page -->
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo ($page + 1); ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="page-link">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php else: ?>
                    <span class="page-link disabled">
                        <i class="fas fa-chevron-right"></i>
                    </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Initialize date range picker
        flatpickr("#dateRange", {
            mode: "range",
            dateFormat: "Y-m-d",
            defaultDate: ["<?php echo $filter_date_from; ?>", "<?php echo $filter_date_to; ?>"],
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    document.querySelector('input[name="date_from"]').value = formatDate(selectedDates[0]);
                    document.querySelector('input[name="date_to"]').value = formatDate(selectedDates[1]);
                }
            }
        });

        function formatDate(date) {
            return date.getFullYear() + '-' +
                   String(date.getMonth() + 1).padStart(2, '0') + '-' +
                   String(date.getDate()).padStart(2, '0');
        }

        function applyDateRange() {
            document.getElementById('filterForm').submit();
        }

        function resetFilters() {
            window.location.href = 'analytics.php';
        }

        function resetTableSearch() {
            // Get current URL parameters
            const urlParams = new URLSearchParams(window.location.search);

            // Remove search parameters
            urlParams.delete('search_qr');
            urlParams.delete('search_device');
            urlParams.delete('search_country');
            urlParams.delete('search_city');
            urlParams.delete('search_os');
            urlParams.delete('search_browser');
            urlParams.delete('search_ip');
            urlParams.delete('search_date');
            urlParams.delete('page');

            // Redirect with remaining parameters
            window.location.href = 'analytics.php?' + urlParams.toString();
        }

        function exportToCSV() {
            // Build export URL with current search filters
            const urlParams = new URLSearchParams(window.location.search);
            window.location.href = 'export_scans.php?' + urlParams.toString();
        }

        // Load Google Charts
        google.charts.load('current', {
            packages: ['corechart', 'geochart'],
            mapsApiKey: 'YOUR_GOOGLE_MAPS_API_KEY'
        });
        google.charts.setOnLoadCallback(drawCharts);

        function drawCharts() {
            drawTrendChart();
            drawDeviceChart();
            drawCountryChart();
            drawOSChart();
            drawBrowserChart();
            drawHourlyChart();
            drawQRChart();
            drawDOWChart();
            drawCityChart();
            drawISPChart();
            drawMobileChart();
            drawTimezoneChart();
            drawGeoChart();
        }

        // 1. Scans Over Time
        function drawTrendChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('date', 'Date');
            data.addColumn('number', 'Scans');
            data.addColumn('number', 'Unique Visitors');

            <?php foreach($trend_data as $row): ?>
            data.addRow([new Date('<?php echo $row['date']; ?>'), <?php echo $row['scans']; ?>, <?php echo $row['unique_visitors']; ?>]);
            <?php endforeach; ?>

            var options = {
                title: 'Scans Over Time',
                curveType: 'function',
                legend: { position: 'bottom' },
                chartArea: { width: '80%', height: '70%' },
                colors: ['#667eea', '#48bb78'],
                hAxis: { format: 'MMM d' },
                vAxis: { minValue: 0 },
                pointSize: 5,
                animation: { startup: true, duration: 1000, easing: 'out' }
            };

            var chart = new google.visualization.LineChart(document.getElementById('trend_chart'));
            chart.draw(data, options);
        }

        // 2. Device Distribution
        function drawDeviceChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Device');
            data.addColumn('number', 'Scans');

            <?php foreach($device_data as $row): ?>
            data.addRow(['<?php echo addslashes($row['device_type']); ?>', <?php echo $row['count']; ?>]);
            <?php endforeach; ?>

            var options = {
                title: 'Device Distribution',
                pieHole: 0.4,
                legend: { position: 'bottom' },
                chartArea: { width: '80%', height: '70%' },
                colors: ['#667eea', '#48bb78', '#ed8936', '#9f7aea', '#f56565'],
                animation: { startup: true, duration: 1000, easing: 'out' }
            };

            var chart = new google.visualization.PieChart(document.getElementById('device_chart'));
            chart.draw(data, options);
        }

        // 3. Top Countries
        function drawCountryChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Country');
            data.addColumn('number', 'Scans');

            <?php foreach($country_data as $row): ?>
            data.addRow(['<?php echo addslashes($row['country']); ?>', <?php echo $row['count']; ?>]);
            <?php endforeach; ?>

            var options = {
                title: 'Top Countries',
                legend: { position: 'none' },
                chartArea: { width: '60%', height: '70%' },
                colors: ['#667eea'],
                hAxis: { title: 'Country' },
                vAxis: { title: 'Scans', minValue: 0 },
                animation: { startup: true, duration: 1000, easing: 'out' },
                bars: 'horizontal'
            };

            var chart = new google.visualization.BarChart(document.getElementById('country_chart'));
            chart.draw(data, options);
        }

        // 4. OS Distribution
        function drawOSChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'OS');
            data.addColumn('number', 'Scans');

            <?php foreach($os_data as $row): ?>
            data.addRow(['<?php echo addslashes($row['device_os']); ?>', <?php echo $row['count']; ?>]);
            <?php endforeach; ?>

            var options = {
                title: 'Operating Systems',
                legend: { position: 'bottom' },
                chartArea: { width: '80%', height: '70%' },
                colors: ['#4299e1', '#48bb78', '#ed8936', '#9f7aea', '#f56565'],
                is3D: true,
                animation: { startup: true, duration: 1000, easing: 'out' }
            };

            var chart = new google.visualization.PieChart(document.getElementById('os_chart'));
            chart.draw(data, options);
        }

        // 5. Browser Distribution
        function drawBrowserChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Browser');
            data.addColumn('number', 'Scans');

            <?php foreach($browser_data as $row): ?>
            data.addRow(['<?php echo addslashes($row['browser']); ?>', <?php echo $row['count']; ?>]);
            <?php endforeach; ?>

            var options = {
                title: 'Browser Distribution',
                legend: { position: 'bottom' },
                chartArea: { width: '80%', height: '70%' },
                colors: ['#4299e1', '#48bb78', '#ed8936', '#9f7aea', '#f56565'],
                pieSliceText: 'percentage',
                animation: { startup: true, duration: 1000, easing: 'out' }
            };

            var chart = new google.visualization.PieChart(document.getElementById('browser_chart'));
            chart.draw(data, options);
        }

        // 6. Hourly Distribution
        function drawHourlyChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('number', 'Hour');
            data.addColumn('number', 'Scans');

            <?php for($i = 0; $i < 24; $i++): ?>
            data.addRow([<?php echo $i; ?>, <?php echo $hourly_data[$i]; ?>]);
            <?php endfor; ?>

            var options = {
                title: 'Hourly Distribution',
                legend: { position: 'none' },
                chartArea: { width: '80%', height: '70%' },
                colors: ['#ed8936'],
                hAxis: { title: 'Hour of Day', format: '#' },
                vAxis: { title: 'Scans', minValue: 0 },
                histogram: { bucketSize: 1 },
                animation: { startup: true, duration: 1000, easing: 'out' }
            };

            var chart = new google.visualization.ColumnChart(document.getElementById('hourly_chart'));
            chart.draw(data, options);
        }

        // 7. QR Performance
        function drawQRChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'QR Code');
            data.addColumn('number', 'Scans');

            <?php foreach($qr_performance as $row): ?>
            data.addRow(['<?php echo addslashes($row['qr_name']); ?>', <?php echo $row['count']; ?>]);
            <?php endforeach; ?>

            var options = {
                title: 'QR Code Performance',
                legend: { position: 'none' },
                chartArea: { width: '70%', height: '70%' },
                colors: ['#48bb78'],
                hAxis: { title: 'Scans' },
                vAxis: { title: 'QR Code' },
                animation: { startup: true, duration: 1000, easing: 'out' },
                bars: 'horizontal'
            };

            var chart = new google.visualization.BarChart(document.getElementById('qr_chart'));
            chart.draw(data, options);
        }

        // 8. Day of Week
        function drawDOWChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Day');
            data.addColumn('number', 'Scans');

            <?php foreach($dow_data as $day => $count): ?>
            data.addRow(['<?php echo $day; ?>', <?php echo $count; ?>]);
            <?php endforeach; ?>

            var options = {
                title: 'Day of Week Distribution',
                legend: { position: 'none' },
                chartArea: { width: '80%', height: '70%' },
                colors: ['#667eea'],
                hAxis: { title: 'Day of Week' },
                vAxis: { title: 'Scans', minValue: 0 },
                animation: { startup: true, duration: 1000, easing: 'out' }
            };

            var chart = new google.visualization.ColumnChart(document.getElementById('dow_chart'));
            chart.draw(data, options);
        }

        // 9. Top Cities
        function drawCityChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'City');
            data.addColumn('number', 'Scans');

            <?php foreach($city_data as $row): ?>
            data.addRow(['<?php echo addslashes($row['city']); ?>', <?php echo $row['count']; ?>]);
            <?php endforeach; ?>

            var options = {
                title: 'Top Cities',
                legend: { position: 'none' },
                chartArea: { width: '60%', height: '70%' },
                colors: ['#ed8936'],
                hAxis: { title: 'City' },
                vAxis: { title: 'Scans', minValue: 0 },
                animation: { startup: true, duration: 1000, easing: 'out' },
                bars: 'horizontal'
            };

            var chart = new google.visualization.BarChart(document.getElementById('city_chart'));
            chart.draw(data, options);
        }

        // 10. ISP Distribution
        function drawISPChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'ISP');
            data.addColumn('number', 'Scans');

            <?php foreach($isp_data as $row): ?>
            data.addRow(['<?php echo addslashes($row['isp']); ?>', <?php echo $row['count']; ?>]);
            <?php endforeach; ?>

            var options = {
                title: 'ISP Distribution',
                legend: { position: 'none' },
                chartArea: { width: '70%', height: '70%' },
                colors: ['#9f7aea'],
                hAxis: { title: 'Scans' },
                vAxis: { title: 'ISP' },
                animation: { startup: true, duration: 1000, easing: 'out' },
                bars: 'horizontal'
            };

            var chart = new google.visualization.BarChart(document.getElementById('isp_chart'));
            chart.draw(data, options);
        }

        // 11. Mobile vs Desktop
        function drawMobileChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Category');
            data.addColumn('number', 'Count');

            data.addRow(['Mobile', <?php echo $mobile_desktop['mobile'] ?? 0; ?>]);
            data.addRow(['Desktop', <?php echo $mobile_desktop['desktop'] ?? 0; ?>]);
            data.addRow(['Tablet', <?php echo $mobile_desktop['tablet'] ?? 0; ?>]);
            data.addRow(['Robot', <?php echo $mobile_desktop['robot'] ?? 0; ?>]);

            var options = {
                title: 'Device Categories',
                pieHole: 0.4,
                legend: { position: 'bottom' },
                chartArea: { width: '80%', height: '70%' },
                colors: ['#48bb78', '#4299e1', '#ed8936', '#f56565'],
                animation: { startup: true, duration: 1000, easing: 'out' }
            };

            var chart = new google.visualization.PieChart(document.getElementById('mobile_chart'));
            chart.draw(data, options);
        }

        // 12. Timezone Distribution
        function drawTimezoneChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Timezone');
            data.addColumn('number', 'Scans');

            <?php foreach($timezone_data as $row): ?>
            data.addRow(['<?php echo addslashes($row['timezone']); ?>', <?php echo $row['count']; ?>]);
            <?php endforeach; ?>

            var options = {
                title: 'Timezone Distribution',
                legend: { position: 'none' },
                chartArea: { width: '70%', height: '70%' },
                colors: ['#4299e1'],
                hAxis: { title: 'Timezone' },
                vAxis: { title: 'Scans', minValue: 0 },
                animation: { startup: true, duration: 1000, easing: 'out' },
                bars: 'horizontal'
            };

            var chart = new google.visualization.BarChart(document.getElementById('timezone_chart'));
            chart.draw(data, options);
        }

        // 13. Geo Chart
        function drawGeoChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Country');
            data.addColumn('number', 'Scans');

            <?php foreach($country_data as $row): ?>
            data.addRow(['<?php echo addslashes($row['country']); ?>', <?php echo $row['count']; ?>]);
            <?php endforeach; ?>

            var options = {
                title: 'Geographic Distribution',
                colorAxis: { colors: ['#e0f2fe', '#667eea'] },
                backgroundColor: '#ffffff',
                datalessRegionColor: '#f5f5f5',
                defaultColor: '#f5f5f5',
                animation: { startup: true, duration: 1000, easing: 'out' }
            };

            var chart = new google.visualization.GeoChart(document.getElementById('geo_chart'));
            chart.draw(data, options);
        }

        // Handle window resize
        window.addEventListener('resize', function() {
            drawCharts();
        });

        // Auto-refresh every 60 seconds (optional)
        // setTimeout(function() {
        //     location.reload();
        // }, 60000);
    </script>
</body>
</html>
```

This complete updated `analytics.php` file includes:

## Key Features Added:

### 1. **Professional Pagination System**
- Shows 10 records per page
- Displays page numbers with current page highlighted
- Previous/Next navigation buttons
- Shows record range (e.g., "Showing 1-10 of 150")
- Ellipsis for many pages
- Preserves all filters during pagination

### 2. **Advanced Search Interface**
- Separate search section above the table
- Search by multiple fields:
  - QR Name (text search)
  - Device Type (dropdown)
  - Country (dropdown)
  - City (text search)
  - Operating System (text search)
  - Browser (text search)
  - IP Address (text search)
  - Date (date picker)
- Clear Search button to reset all search filters
- Preserves chart filters when searching

### 3. **Improved Table Display**
- Professional table styling with hover effects
- Device type badges with appropriate colors
- Icons for OS and browsers
- Formatted timestamps
- Location display with timezone
- Local IP handling
- Empty state message when no data

### 4. **Enhanced User Experience**
- Records count display
- Responsive design for mobile devices
- Loading animations
- Export to CSV functionality
- Date range picker for main filters
- Reset filters button

### 5. **Complete Chart Suite**
- 13 different charts using Google Charts
- All fields from `qr_scan_logs` table utilized
- Interactive and animated charts
- Responsive chart sizing

### 6. **URL Parameter Preservation**
- All filters maintained in URL
- Pagination state preserved
- Search parameters in URL for sharing/bookmarking

## How to Use:

1. **Filter Charts**: Use the top filter bar to filter all charts
2. **Search Records**: Use the search section above the table to find specific scans
3. **Navigate Pages**: Use pagination at the bottom of the table
4. **Export Data**: Click "Export CSV" to download filtered data
5. **Reset Search**: Click "Clear Search" to reset table filters
6. **Reset All**: Click "Reset" in the main filter bar to reset everything

The dashboard now provides a professional, user-friendly interface for analyzing QR code scan data with comprehensive filtering and pagination capabilities.