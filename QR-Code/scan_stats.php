<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Get summary statistics
$stats = [];

// Total scans
$result = $conn->query("SELECT COUNT(*) as total FROM qr_scan_logs");
$stats['total_scans'] = $result->fetch_assoc()['total'];

// Scans today
$result = $conn->query("SELECT COUNT(*) as today FROM qr_scan_logs WHERE DATE(scan_time) = CURDATE()");
$stats['scans_today'] = $result->fetch_assoc()['today'];

// Unique devices
$result = $conn->query("SELECT COUNT(DISTINCT CONCAT(device_type, device_os, browser)) as unique_devices FROM qr_scan_logs");
$stats['unique_devices'] = $result->fetch_assoc()['unique_devices'];

// Top QR codes
$topQrs = $conn->query("
    SELECT qr_name, COUNT(*) as scan_count
    FROM qr_scan_logs
    GROUP BY qr_id, qr_name
    ORDER BY scan_count DESC
    LIMIT 5
");

// Device breakdown
$devices = $conn->query("
    SELECT
        device_type,
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM qr_scan_logs), 1) as percentage
    FROM qr_scan_logs
    GROUP BY device_type
    ORDER BY count DESC
");

// OS breakdown
$os = $conn->query("
    SELECT
        device_os,
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM qr_scan_logs), 1) as percentage
    FROM qr_scan_logs
    WHERE device_os != 'Unknown'
    GROUP BY device_os
    ORDER BY count DESC
");

// Browser breakdown
$browsers = $conn->query("
    SELECT
        browser,
        COUNT(*) as count,
        ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM qr_scan_logs), 1) as percentage
    FROM qr_scan_logs
    WHERE browser != 'Unknown'
    GROUP BY browser
    ORDER BY count DESC
");

// Geographic distribution
$geo = $conn->query("
    SELECT
        country,
        COUNT(*) as count
    FROM qr_scan_logs
    WHERE country != 'Unknown' AND country != 'Local'
    GROUP BY country
    ORDER BY count DESC
    LIMIT 10
");

// Hourly distribution
$hourly = $conn->query("
    SELECT
        hour_of_day,
        COUNT(*) as count
    FROM qr_scan_logs
    GROUP BY hour_of_day
    ORDER BY hour_of_day
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scan Analytics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .analytics-container {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
        }

        .chart-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .table-container {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #667eea;
            color: white;
        }

        .percentage-bar {
            background: #e0e0e0;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
        }

        .percentage-fill {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            color: white;
            font-size: 12px;
            line-height: 20px;
            padding-left: 5px;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-qrcode"></i>
                <span>QR Manager</span>
            </div>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
                <a href="scan_stats.php" class="active"><i class="fas fa-chart-bar"></i> Analytics</a>
                <a href="#"><i class="fas fa-cog"></i> Settings</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <header>
                <h1>QR Scan Analytics</h1>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search scans...">
                </div>
                <button class="btn btn-primary" onclick="exportData()">
                    <i class="fas fa-download"></i> Export Data
                </button>
            </header>

            <div class="analytics-container">
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><i class="fas fa-eye"></i> Total Scans</h3>
                        <div class="number"><?php echo number_format($stats['total_scans']); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3><i class="fas fa-calendar-day"></i> Scans Today</h3>
                        <div class="number"><?php echo number_format($stats['scans_today']); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3><i class="fas fa-mobile-alt"></i> Unique Devices</h3>
                        <div class="number"><?php echo number_format($stats['unique_devices']); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3><i class="fas fa-clock"></i> Avg. Duration</h3>
                        <div class="number"><?php
                            $result = $conn->query("SELECT AVG(scan_duration) as avg FROM qr_scan_logs WHERE scan_duration > 0");
                            $avg = $result->fetch_assoc()['avg'] ?? 0;
                            echo number_format($avg, 2) . 's';
                        ?></div>
                    </div>
                </div>

                <!-- Charts Row 1 -->
                <div class="chart-row">
                    <!-- Device Type Chart -->
                    <div class="chart-container">
                        <h3>Device Type Distribution</h3>
                        <canvas id="deviceChart"></canvas>
                    </div>

                    <!-- Hourly Distribution Chart -->
                    <div class="chart-container">
                        <h3>Scans by Hour</h3>
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>

                <!-- Charts Row 2 -->
                <div class="chart-row">
                    <!-- Top QR Codes -->
                    <div class="table-container">
                        <h3>Top QR Codes</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>QR Name</th>
                                    <th>Scans</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $total = $stats['total_scans'];
                                while($row = $topQrs->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['qr_name']); ?></td>
                                    <td><?php echo number_format($row['scan_count']); ?></td>
                                    <td>
                                        <div class="percentage-bar">
                                            <div class="percentage-fill" style="width: <?php echo round(($row['scan_count']/$total)*100); ?>%">
                                                <?php echo round(($row['scan_count']/$total)*100, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Geographic Distribution -->
                    <div class="table-container">
                        <h3>Top Countries</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Country</th>
                                    <th>Scans</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $geo->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['country']); ?></td>
                                    <td><?php echo number_format($row['count']); ?></td>
                                    <td>
                                        <div class="percentage-bar">
                                            <div class="percentage-fill" style="width: <?php echo round(($row['count']/$total)*100); ?>%">
                                                <?php echo round(($row['count']/$total)*100, 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Detailed Tables -->
                <div class="chart-row">
                    <!-- OS Breakdown -->
                    <div class="table-container">
                        <h3>Operating Systems</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>OS</th>
                                    <th>Scans</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $os->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['device_os']); ?></td>
                                    <td><?php echo number_format($row['count']); ?></td>
                                    <td>
                                        <div class="percentage-bar">
                                            <div class="percentage-fill" style="width: <?php echo $row['percentage']; ?>%">
                                                <?php echo $row['percentage']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Browser Breakdown -->
                    <div class="table-container">
                        <h3>Browsers</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Browser</th>
                                    <th>Scans</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $browsers->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['browser']); ?></td>
                                    <td><?php echo number_format($row['count']); ?></td>
                                    <td>
                                        <div class="percentage-bar">
                                            <div class="percentage-fill" style="width: <?php echo $row['percentage']; ?>%">
                                                <?php echo $row['percentage']; ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Scans Table -->
                <div class="table-container" style="margin-top: 20px;">
                    <h3>Recent Scans</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>QR Name</th>
                                <th>Device</th>
                                <th>OS</th>
                                <th>Browser</th>
                                <th>Country</th>
                                <th>IP Address</th>
                                <th>Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recent = $conn->query("
                                SELECT * FROM qr_scan_logs
                                ORDER BY scan_time DESC
                                LIMIT 50
                            ");
                            while($row = $recent->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($row['scan_time'])); ?></td>
                                <td><?php echo htmlspecialchars($row['qr_name']); ?></td>
                                <td>
                                    <?php
                                    if($row['is_mobile']) echo '<i class="fas fa-mobile-alt"></i>';
                                    elseif($row['is_tablet']) echo '<i class="fas fa-tablet-alt"></i>';
                                    elseif($row['is_desktop']) echo '<i class="fas fa-desktop"></i>';
                                    elseif($row['is_robot']) echo '<i class="fas fa-robot"></i>';
                                    ?>
                                    <?php echo htmlspecialchars($row['device_type']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['device_os'] . ' ' . $row['os_version']); ?></td>
                                <td><?php echo htmlspecialchars($row['browser'] . ' ' . $row['browser_version']); ?></td>
                                <td><?php echo htmlspecialchars($row['country']); ?></td>
                                <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                                <td><?php echo $row['scan_duration'] ? number_format($row['scan_duration'], 2) . 's' : '-'; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Device Chart
    const deviceCtx = document.getElementById('deviceChart').getContext('2d');
    new Chart(deviceCtx, {
        type: 'doughnut',
        data: {
            labels: [
                <?php
                $devices->data_seek(0);
                $deviceLabels = [];
                $deviceCounts = [];
                while($row = $devices->fetch_assoc()) {
                    $deviceLabels[] = "'" . $row['device_type'] . "'";
                    $deviceCounts[] = $row['count'];
                }
                echo implode(',', $deviceLabels);
                ?>
            ],
            datasets: [{
                data: [<?php echo implode(',', $deviceCounts); ?>],
                backgroundColor: [
                    '#667eea',
                    '#764ba2',
                    '#f093fb',
                    '#f5576c',
                    '#4facfe'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Hourly Chart
    const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
    const hours = [];
    const hourCounts = [];
    <?php
    $hourly->data_seek(0);
    for($i = 0; $i < 24; $i++) {
        $found = false;
        $hourly->data_seek(0);
        while($row = $hourly->fetch_assoc()) {
            if($row['hour_of_day'] == $i) {
                echo "hours.push('" . sprintf("%02d:00", $i) . "');";
                echo "hourCounts.push(" . $row['count'] . ");";
                $found = true;
                break;
            }
        }
        if(!$found) {
            echo "hours.push('" . sprintf("%02d:00", $i) . "');";
            echo "hourCounts.push(0);";
        }
    }
    ?>

    new Chart(hourlyCtx, {
        type: 'line',
        data: {
            labels: hours,
            datasets: [{
                label: 'Scans',
                data: hourCounts,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    function exportData() {
        window.location.href = 'export_scans.php';
    }
    </script>
</body>
</html>