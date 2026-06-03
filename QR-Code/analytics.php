<?php include 'header.php'; ?>
<?php
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
$records_per_page = 15;
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
if ($filter_qr > 0) $where_conditions[] = "qr_id = $filter_qr";
if (!empty($filter_device)) $where_conditions[] = "device_type = '" . $conn->real_escape_string($filter_device) . "'";
if (!empty($filter_country)) $where_conditions[] = "country = '" . $conn->real_escape_string($filter_country) . "'";
if (!empty($filter_os)) $where_conditions[] = "device_os = '" . $conn->real_escape_string($filter_os) . "'";
if (!empty($filter_browser)) $where_conditions[] = "browser = '" . $conn->real_escape_string($filter_browser) . "'";
$where_clause = implode(" AND ", $where_conditions);

// Build WHERE clause for table search
$table_conditions = ["1=1"];
if (!empty($search_qr)) $table_conditions[] = "qr_name LIKE '%" . $conn->real_escape_string($search_qr) . "%'";
if (!empty($search_device)) $table_conditions[] = "device_type = '" . $conn->real_escape_string($search_device) . "'";
if (!empty($search_country)) $table_conditions[] = "country LIKE '%" . $conn->real_escape_string($search_country) . "%'";
if (!empty($search_city)) $table_conditions[] = "city LIKE '%" . $conn->real_escape_string($search_city) . "%'";
if (!empty($search_ip)) $table_conditions[] = "ip_address LIKE '%" . $conn->real_escape_string($search_ip) . "%'";
if (!empty($search_os)) $table_conditions[] = "device_os LIKE '%" . $conn->real_escape_string($search_os) . "%'";
if (!empty($search_browser)) $table_conditions[] = "browser LIKE '%" . $conn->real_escape_string($search_browser) . "%'";
if (!empty($search_date)) $table_conditions[] = "DATE(scan_time) = '" . $conn->real_escape_string($search_date) . "'";
$table_where_clause = implode(" AND ", $table_conditions);

// Get dropdown values
$qr_codes = $conn->query("SELECT id, qrname FROM qr_codes ORDER BY qrname");
$device_types = $conn->query("SELECT DISTINCT device_type FROM qr_scan_logs WHERE device_type != 'Unknown' ORDER BY device_type");
$countries = $conn->query("SELECT DISTINCT country FROM qr_scan_logs WHERE country != 'Unknown' AND country != 'Local' ORDER BY country");
$oses = $conn->query("SELECT DISTINCT device_os FROM qr_scan_logs WHERE device_os != 'Unknown' ORDER BY device_os");
$browsers = $conn->query("SELECT DISTINCT browser FROM qr_scan_logs WHERE browser != 'Unknown' ORDER BY browser");

// Summary Statistics
$stats = [];
$stats['total_scans'] = $conn->query("SELECT COUNT(*) as total FROM qr_scan_logs WHERE $where_clause")->fetch_assoc()['total'] ?? 0;
$stats['unique_visitors'] = $conn->query("SELECT COUNT(DISTINCT ip_address) as unique_visitors FROM qr_scan_logs WHERE $where_clause AND ip_address NOT IN ('::1', '127.0.0.1', 'UNKNOWN')")->fetch_assoc()['unique_visitors'] ?? 0;
$stats['avg_duration'] = $conn->query("SELECT AVG(scan_duration) as avg_duration FROM qr_scan_logs WHERE $where_clause AND scan_duration > 0")->fetch_assoc()['avg_duration'] ?? 0;

$bounce_res = $conn->query("SELECT COUNT(CASE WHEN scan_duration < 2 AND scan_duration > 0 THEN 1 END) as fast_scans, COUNT(CASE WHEN scan_duration > 0 THEN 1 END) as total_with_duration FROM qr_scan_logs WHERE $where_clause")->fetch_assoc();
$stats['bounce_rate'] = ($bounce_res['total_with_duration'] > 0) ? round(($bounce_res['fast_scans'] / $bounce_res['total_with_duration']) * 100, 1) : 0;

// Pagination
$total_records = $conn->query("SELECT COUNT(*) as total FROM qr_scan_logs WHERE $table_where_clause")->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$recent_scans = $conn->query("SELECT * FROM qr_scan_logs WHERE $table_where_clause ORDER BY scan_time DESC LIMIT $offset, $records_per_page");

// Data for Google Charts
$trend_data = []; $result = $conn->query("SELECT DATE(scan_time) as date, COUNT(*) as scans, COUNT(DISTINCT ip_address) as unique_visitors FROM qr_scan_logs WHERE $where_clause GROUP BY DATE(scan_time) ORDER BY date"); while($row = $result->fetch_assoc()) $trend_data[] = $row;
$device_data = []; $result = $conn->query("SELECT device_type, COUNT(*) as count FROM qr_scan_logs WHERE $where_clause GROUP BY device_type ORDER BY count DESC"); while($row = $result->fetch_assoc()) $device_data[] = $row;
$country_data = []; $result = $conn->query("SELECT country, COUNT(*) as count FROM qr_scan_logs WHERE $where_clause AND country != 'Unknown' AND country != 'Local' GROUP BY country ORDER BY count DESC LIMIT 15"); while($row = $result->fetch_assoc()) $country_data[] = $row;
$os_data = []; $result = $conn->query("SELECT device_os, COUNT(*) as count FROM qr_scan_logs WHERE $where_clause AND device_os != 'Unknown' GROUP BY device_os ORDER BY count DESC"); while($row = $result->fetch_assoc()) $os_data[] = $row;
$browser_data = []; $result = $conn->query("SELECT browser, COUNT(*) as count FROM qr_scan_logs WHERE $where_clause AND browser != 'Unknown' GROUP BY browser ORDER BY count DESC"); while($row = $result->fetch_assoc()) $browser_data[] = $row;
$hourly_data = array_fill(0, 24, 0); $result = $conn->query("SELECT hour_of_day, COUNT(*) as count FROM qr_scan_logs WHERE $where_clause AND hour_of_day IS NOT NULL GROUP BY hour_of_day"); while($row = $result->fetch_assoc()) $hourly_data[$row['hour_of_day']] = $row['count'];
$qr_performance = []; $result = $conn->query("SELECT qr_name, COUNT(*) as count FROM qr_scan_logs WHERE $where_clause GROUP BY qr_id, qr_name ORDER BY count DESC LIMIT 15"); while($row = $result->fetch_assoc()) $qr_performance[] = $row;
$dow_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']; $dow_data = array_fill_keys($dow_order, 0); $result = $conn->query("SELECT day_of_week, COUNT(*) as count FROM qr_scan_logs WHERE $where_clause AND day_of_week IS NOT NULL GROUP BY day_of_week"); while($row = $result->fetch_assoc()) $dow_data[$row['day_of_week']] = $row['count'];
$city_data = []; $result = $conn->query("SELECT city, country, COUNT(*) as count FROM qr_scan_logs WHERE $where_clause AND city != 'Unknown' AND city != 'Local' GROUP BY city, country ORDER BY count DESC LIMIT 15"); while($row = $result->fetch_assoc()) $city_data[] = $row;
$isp_data = []; $result = $conn->query("SELECT isp, COUNT(*) as count FROM qr_scan_logs WHERE $where_clause AND isp != 'Unknown' AND isp != 'Local' GROUP BY isp ORDER BY count DESC LIMIT 15"); while($row = $result->fetch_assoc()) $isp_data[] = $row;
$timezone_data = []; $result = $conn->query("SELECT timezone, COUNT(*) as count FROM qr_scan_logs WHERE $where_clause AND timezone != 'Unknown' GROUP BY timezone ORDER BY count DESC LIMIT 10"); while($row = $result->fetch_assoc()) $timezone_data[] = $row;
?>

<style>
    /* Analytics Specific Styling */
    .filter-bar { background: var(--white); padding: 20px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 15px; }
    .filter-item { display: flex; flex-direction: column; gap: 5px; }
    .filter-item label { font-size: 13px; font-weight: 600; color: var(--gray); text-transform: uppercase; }
    .filter-item select, .filter-item input { padding: 10px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; outline: none; }
    .filter-item select:focus, .filter-item input:focus { border-color: var(--primary); }
    .filter-actions { display: flex; justify-content: flex-end; }

    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card { background: var(--white); padding: 20px; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 20px; }
    .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; }
    .stat-icon.primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); }
    .stat-icon.success { background: linear-gradient(135deg, var(--success), #2ecc71); }
    .stat-icon.warning { background: linear-gradient(135deg, #ed8936, #f39c12); }
    .stat-icon.info { background: linear-gradient(135deg, #4299e1, #3498db); }

    .chart-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 30px; }
    @media (max-width: 768px) { .chart-grid { grid-template-columns: 1fr; } }
    .chart-card { background: var(--white); padding: 20px; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .chart-card.full-width { grid-column: 1 / -1; }
    .chart-header { margin-bottom: 15px; font-size: 16px; font-weight: 600; color: var(--dark); display: flex; align-items: center; gap: 8px; }
    .chart-container { height: 350px; width: 100%; }

    .table-section { background: var(--white); padding: 25px; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
    .table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
    .table-wrapper { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border); }
    th { background: #f8fafc; font-weight: 600; color: var(--gray); font-size: 13px; text-transform: uppercase; }
    td { font-size: 14px; color: var(--dark); }
    .pagination { display: flex; gap: 5px; margin-top: 25px; justify-content: center; flex-wrap: wrap;}
    .page-link { padding: 8px 14px; border: 1px solid var(--border); border-radius: 6px; text-decoration: none; color: var(--dark); font-weight: 500; background: white;}
    .page-link:hover { background: #f1f5f9; }
    .page-link.active { background: var(--primary); color: white; border-color: var(--primary); }
    .page-link.disabled { color: #cbd5e1; pointer-events: none; background: #f8fafc; }
</style>

<div class="filter-bar">
    <form method="GET" action="analytics.php">
        <div class="filter-grid">
            <div class="filter-item">
                <label><i class="fas fa-qrcode"></i> Target QR Code</label>
                <select name="qr_id">
                    <option value="0">All QR Codes</option>
                    <?php while($qr = $qr_codes->fetch_assoc()): ?>
                        <option value="<?php echo $qr['id']; ?>" <?php echo $filter_qr == $qr['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($qr['qrname']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-item">
                <label><i class="fas fa-mobile-alt"></i> Device Type</label>
                <select name="device_type">
                    <option value="">All Devices</option>
                    <?php while($d = $device_types->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($d['device_type']); ?>" <?php echo $filter_device == $d['device_type'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['device_type']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-item">
                <label><i class="fas fa-globe"></i> Country</label>
                <select name="country">
                    <option value="">All Countries</option>
                    <?php while($c = $countries->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($c['country']); ?>" <?php echo $filter_country == $c['country'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['country']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="filter-item">
                <label><i class="fas fa-calendar-alt"></i> From Date</label>
                <input type="date" name="date_from" value="<?php echo $filter_date_from; ?>">
            </div>
            <div class="filter-item">
                <label><i class="fas fa-calendar-check"></i> To Date</label>
                <input type="date" name="date_to" value="<?php echo $filter_date_to; ?>">
            </div>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply Analytics Filters</button>
        </div>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-qrcode"></i></div>
        <div>
            <h3 style="font-size: 14px; color: var(--gray); margin-bottom: 5px;">Total Scans</h3>
            <p style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['total_scans']); ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-users"></i></div>
        <div>
            <h3 style="font-size: 14px; color: var(--gray); margin-bottom: 5px;">Unique Visitors</h3>
            <p style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['unique_visitors']); ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
        <div>
            <h3 style="font-size: 14px; color: var(--gray); margin-bottom: 5px;">Avg Time on Page</h3>
            <p style="font-size: 24px; font-weight: bold;"><?php echo number_format($stats['avg_duration'], 2); ?>s</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-bolt"></i></div>
        <div>
            <h3 style="font-size: 14px; color: var(--gray); margin-bottom: 5px;">Bounce Rate</h3>
            <p style="font-size: 24px; font-weight: bold;"><?php echo $stats['bounce_rate']; ?>%</p>
        </div>
    </div>
</div>

<div class="chart-grid">
    <div class="chart-card full-width">
        <div class="chart-header"><i class="fas fa-chart-area" style="color: var(--primary);"></i> Scan Volume Over Time</div>
        <div class="chart-container" id="trend_chart"></div>
    </div>

    <div class="chart-card">
        <div class="chart-header"><i class="fas fa-map-marked-alt" style="color: var(--primary);"></i> Geographic Distribution</div>
        <div class="chart-container" id="geo_chart"></div>
    </div>
    <div class="chart-card">
        <div class="chart-header"><i class="fas fa-mobile-alt" style="color: var(--primary);"></i> Device Types</div>
        <div class="chart-container" id="device_chart"></div>
    </div>

    <div class="chart-card">
        <div class="chart-header"><i class="fas fa-laptop" style="color: var(--primary);"></i> Operating Systems</div>
        <div class="chart-container" id="os_chart"></div>
    </div>
    <div class="chart-card">
        <div class="chart-header"><i class="fas fa-globe" style="color: var(--primary);"></i> Browsers Used</div>
        <div class="chart-container" id="browser_chart"></div>
    </div>

    <div class="chart-card full-width">
        <div class="chart-header"><i class="fas fa-clock" style="color: var(--primary);"></i> Scans by Hour of Day</div>
        <div class="chart-container" id="hourly_chart"></div>
    </div>

    <div class="chart-card">
        <div class="chart-header"><i class="fas fa-calendar-day" style="color: var(--primary);"></i> Scans by Day of Week</div>
        <div class="chart-container" id="dow_chart"></div>
    </div>
    <div class="chart-card">
        <div class="chart-header"><i class="fas fa-trophy" style="color: var(--primary);"></i> Top Performing QR Codes</div>
        <div class="chart-container" id="qr_chart"></div>
    </div>
</div>

<div class="table-section">
    <div class="table-header">
        <h3 style="font-size: 18px; color: var(--dark); margin:0;"><i class="fas fa-list-ul" style="color: var(--primary);"></i> Raw Scan Logs</h3>
        <div style="background: #edf2f7; padding: 8px 15px; border-radius: 20px; font-size: 14px; font-weight: 600; color: var(--dark);">
            Total Records: <?php echo number_format($total_records); ?>
        </div>
    </div>

    <form method="GET" action="analytics.php" style="background: #f8fafc; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid var(--border);">
        <input type="hidden" name="qr_id" value="<?php echo $filter_qr; ?>">
        <input type="hidden" name="date_from" value="<?php echo $filter_date_from; ?>">
        <input type="hidden" name="date_to" value="<?php echo $filter_date_to; ?>">

        <div class="filter-grid" style="margin-bottom:0;">
            <div class="filter-item">
                <label>Search IP Address</label>
                <input type="text" name="search_ip" value="<?php echo htmlspecialchars($search_ip); ?>" placeholder="e.g. 192.168...">
            </div>
            <div class="filter-item">
                <label>Search City</label>
                <input type="text" name="search_city" value="<?php echo htmlspecialchars($search_city); ?>" placeholder="City Name">
            </div>
            <div class="filter-item" style="justify-content: flex-end;">
                <button type="submit" class="btn btn-primary" style="padding: 10px; width: 100%;"><i class="fas fa-search"></i> Search Logs</button>
            </div>
        </div>
    </form>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>QR Code Target</th>
                    <th>Device</th>
                    <th>OS & Browser</th>
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
                            <td><strong><?php echo htmlspecialchars($scan['qr_name']); ?></strong></td>
                            <td><i class="fas fa-mobile-alt" style="color: var(--gray);"></i> <?php echo htmlspecialchars($scan['device_type']); ?></td>
                            <td><?php echo htmlspecialchars($scan['device_os']); ?> / <?php echo htmlspecialchars($scan['browser']); ?></td>
                            <td><?php echo htmlspecialchars($scan['city'] . ', ' . $scan['country']); ?></td>
                            <td><code style="background:#f1f5f9; padding:3px 6px; border-radius:4px;"><?php echo htmlspecialchars($scan['ip_address']); ?></code></td>
                            <td><?php echo $scan['scan_duration'] > 0 ? $scan['scan_duration'] . 's' : '-'; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="no-data"><i class="fas fa-folder-open" style="font-size:30px; display:block; margin-bottom:10px; color:#cbd5e1;"></i> No scan data matches your search.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo ($page - 1); ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" class="page-link"><i class="fas fa-chevron-left"></i> Prev</a>
            <?php endif; ?>

            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            for ($i = $start_page; $i <= $end_page; $i++):
            ?>
                <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo ($page + 1); ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>" class="page-link">Next <i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<script>
    google.charts.load('current', { 'packages': ['corechart', 'geochart'] });
    google.charts.setOnLoadCallback(drawAllCharts);

    function drawAllCharts() {
        drawTrendChart();
        drawGeoChart();
        drawDeviceChart();
        drawOSChart();
        drawBrowserChart();
        drawHourlyChart();
        drawDOWChart();
        drawQRChart();
    }

    function drawTrendChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('date', 'Date');
        data.addColumn('number', 'Scans');
        data.addColumn('number', 'Unique Visitors');
        <?php foreach($trend_data as $row): ?>
            data.addRow([new Date('<?php echo $row['date']; ?>'), <?php echo $row['scans']; ?>, <?php echo $row['unique_visitors']; ?>]);
        <?php endforeach; ?>
        var options = {
            curveType: 'function', legend: { position: 'bottom' }, chartArea: { width: '85%', height: '70%' },
            colors: ['#667eea', '#48bb78'], hAxis: { format: 'MMM d' }, vAxis: { minValue: 0 }, pointSize: 5
        };
        new google.visualization.LineChart(document.getElementById('trend_chart')).draw(data, options);
    }

    function drawGeoChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Country');
        data.addColumn('number', 'Scans');
        <?php foreach($country_data as $row): ?>
            data.addRow(['<?php echo addslashes($row['country']); ?>', <?php echo $row['count']; ?>]);
        <?php endforeach; ?>
        var options = {
            colorAxis: { colors: ['#e0f2fe', '#667eea'] },
            datalessRegionColor: '#f8fafc', defaultColor: '#f8fafc'
        };
        new google.visualization.GeoChart(document.getElementById('geo_chart')).draw(data, options);
    }

    function drawDeviceChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Device'); data.addColumn('number', 'Scans');
        <?php foreach($device_data as $row): ?>
            data.addRow(['<?php echo addslashes($row['device_type']); ?>', <?php echo $row['count']; ?>]);
        <?php endforeach; ?>
        var options = { pieHole: 0.4, chartArea: { width: '90%', height: '80%' }, colors: ['#667eea', '#48bb78', '#ed8936'] };
        new google.visualization.PieChart(document.getElementById('device_chart')).draw(data, options);
    }

    function drawOSChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'OS'); data.addColumn('number', 'Scans');
        <?php foreach($os_data as $row): ?>
            data.addRow(['<?php echo addslashes($row['device_os']); ?>', <?php echo $row['count']; ?>]);
        <?php endforeach; ?>
        var options = { pieHole: 0.4, chartArea: { width: '90%', height: '80%' }, colors: ['#9f7aea', '#ed8936', '#4299e1', '#48bb78'] };
        new google.visualization.PieChart(document.getElementById('os_chart')).draw(data, options);
    }

    function drawBrowserChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Browser'); data.addColumn('number', 'Scans');
        <?php foreach($browser_data as $row): ?>
            data.addRow(['<?php echo addslashes($row['browser']); ?>', <?php echo $row['count']; ?>]);
        <?php endforeach; ?>
        var options = { is3D: true, chartArea: { width: '90%', height: '80%' }, colors: ['#f56565', '#48bb78', '#4299e1', '#ed8936'] };
        new google.visualization.PieChart(document.getElementById('browser_chart')).draw(data, options);
    }

    function drawHourlyChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('number', 'Hour'); data.addColumn('number', 'Scans');
        <?php for($i = 0; $i < 24; $i++): ?>
            data.addRow([<?php echo $i; ?>, <?php echo $hourly_data[$i]; ?>]);
        <?php endfor; ?>
        var options = {
            legend: { position: 'none' }, chartArea: { width: '85%', height: '70%' }, colors: ['#ed8936'],
            hAxis: { title: 'Hour of Day (0-23)', format: '#' }, vAxis: { minValue: 0 }
        };
        new google.visualization.ColumnChart(document.getElementById('hourly_chart')).draw(data, options);
    }

    function drawDOWChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Day'); data.addColumn('number', 'Scans');
        <?php foreach($dow_order as $day): ?>
            data.addRow(['<?php echo substr($day, 0, 3); ?>', <?php echo $dow_data[$day] ?? 0; ?>]);
        <?php endforeach; ?>
        var options = { legend: { position: 'none' }, chartArea: { width: '85%', height: '70%' }, colors: ['#48bb78'] };
        new google.visualization.ColumnChart(document.getElementById('dow_chart')).draw(data, options);
    }

    function drawQRChart() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'QR Name'); data.addColumn('number', 'Scans');
        <?php foreach($qr_performance as $row): ?>
            data.addRow(['<?php echo addslashes(substr($row['qr_name'], 0, 15)); ?>', <?php echo $row['count']; ?>]);
        <?php endforeach; ?>
        var options = { legend: { position: 'none' }, chartArea: { width: '70%', height: '80%' }, colors: ['#764ba2'], bars: 'horizontal' };
        new google.visualization.BarChart(document.getElementById('qr_chart')).draw(data, options);
    }

    window.addEventListener('resize', drawAllCharts);
</script>