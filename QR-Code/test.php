<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
// Include encryption if it's not already inside functions.php
if(file_exists('includes/encryption.php')) {
    require_once 'includes/encryption.php';
}

// Get all QR codes
$query = "SELECT * FROM qr_codes ORDER BY created_at DESC";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Modern CSS Variables */
        :root {
            --primary: #667eea;
            --primary-dark: #5a6cd6;
            --secondary: #764ba2;
            --dark: #1e293b;
            --light: #f8fafc;
            --gray: #64748b;
            --bg-body: #f1f5f9;
            --white: #ffffff;
            --danger: #ef4444;
            --success: #10b981;
            --border: #e2e8f0;
            --sidebar-width: 260px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        body { background-color: var(--bg-body); color: var(--dark); overflow: hidden; height: 100vh; display: flex; }

        /* Sidebar Styling */
        .sidebar {
            width: var(--sidebar-width); background: var(--dark); color: var(--white);
            height: 100vh; display: flex; flex-direction: column; transition: transform 0.3s ease;
            z-index: 1000; position: fixed; left: 0; top: 0;
        }
        .sidebar-header {
            padding: 25px 20px; font-size: 22px; font-weight: bold; color: var(--white);
            border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 15px;
        }
        .sidebar-header i { color: var(--primary); font-size: 26px; }
        .sidebar-nav { padding: 20px 0; flex-grow: 1; display: flex; flex-direction: column;}

        .nav-item {
            padding: 15px 25px; display: flex; align-items: center; gap: 15px;
            color: #cbd5e1; text-decoration: none; transition: all 0.2s; cursor: pointer; border-left: 4px solid transparent;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.05); color: var(--white); border-left-color: var(--primary);
        }

        /* Special Styling for Generate Button in Sidebar */
        .nav-action-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            margin: 0 15px 15px 15px;
            border-radius: 8px;
            color: white !important;
            justify-content: center;
            font-weight: 600;
            border-left: none !important;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
            padding: 12px;
        }
        .nav-action-btn:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, var(--primary-dark), var(--secondary));
            box-shadow: 0 6px 15px rgba(102, 126, 234, 0.4);
        }

        /* Main Layout */
        .main-wrapper {
            flex-grow: 1; margin-left: var(--sidebar-width); display: flex; flex-direction: column; height: 100vh; transition: margin-left 0.3s ease; width: calc(100% - var(--sidebar-width));
        }

        /* Top Header */
        .top-header {
            height: 70px; background: var(--white); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between; padding: 0 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02); z-index: 100;
        }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .menu-toggle { display: none; background: none; border: none; font-size: 24px; color: var(--dark); cursor: pointer; }
        .search-box { position: relative; width: 300px; }
        .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--gray); }
        .search-box input {
            width: 100%; padding: 10px 15px 10px 40px; border: 1px solid var(--border); border-radius: 30px;
            outline: none; background: var(--light); transition: all 0.3s;
        }
        .search-box input:focus { background: var(--white); border-color: var(--primary); box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }

        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; font-size: 14px; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: var(--white); }

        /* Content Area */
        .content-area { flex-grow: 1; overflow-y: auto; padding: 30px; position: relative; background: var(--bg-body); }

        /* Dashboard Container */
        #dashboard-container { display: block; animation: fadeIn 0.3s ease-in-out; }

        /* Stats Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 30px; }
        .stat-card {
            background: var(--white); padding: 25px; border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 20px; border: 1px solid var(--border);
        }
        .stat-icon { width: 60px; height: 60px; border-radius: 12px; background: rgba(102, 126, 234, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .stat-info h3 { font-size: 14px; color: var(--gray); margin-bottom: 5px; font-weight: 500; }
        .stat-info p { font-size: 24px; font-weight: 700; color: var(--dark); }

        /* Chart Section */
        .chart-section { background: var(--white); padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 30px; border: 1px solid var(--border); height: 350px; }

        /* QR Grid */
        .section-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; color: var(--dark); }
        .qr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
        .qr-card { background: var(--white); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid var(--border); transition: transform 0.2s, box-shadow 0.2s; }
        .qr-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .qr-header { padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; background: #fafbfc; }
        .qr-header h3 { font-size: 15px; color: var(--dark); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; }
        .actions button { background: none; border: none; color: var(--gray); cursor: pointer; padding: 5px; transition: color 0.2s; }
        .actions button:hover { color: var(--primary); }
        .actions button.delete:hover { color: var(--danger); }
        .qr-image { padding: 20px; text-align: center; background: var(--white); }
        .qr-image img { width: 140px; height: 140px; object-fit: contain; }
        .qr-stats { padding: 15px 20px; display: flex; justify-content: space-between; font-size: 13px; color: var(--gray); border-top: 1px solid var(--border); }
        .qr-url { padding: 10px 20px 20px; font-size: 12px; color: var(--gray); background: var(--white); word-break: break-all; }

        .no-data { text-align: center; padding: 50px 20px; color: var(--gray); grid-column: 1 / -1; }
        .no-data i { font-size: 48px; margin-bottom: 15px; color: #cbd5e1; }

        /* Iframe Containers for Report and Analytics */
        #report-container, #analytics-container { display: none; width: 100%; height: 100%; animation: fadeIn 0.3s ease-in-out; }
        .app-iframe { width: 100%; height: 100%; border: none; border-radius: 12px; background: #fff; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }

        /* Modal Styles */
        .modal { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; }
        .modal-content { background-color: #fefefe; padding: 30px; border-radius: 16px; width: 90%; max-width: 500px; position: relative; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: modalSlide 0.3s ease-out; }
        .close { position: absolute; right: 25px; top: 25px; color: #94a3b8; font-size: 24px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .close:hover { color: var(--danger); }
        .modal h2 { margin-bottom: 25px; font-size: 22px; color: var(--dark); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; color: var(--dark); }
        .form-group input[type="text"], .form-group input[type="url"] { width: 100%; padding: 12px 15px; border: 1px solid var(--border); border-radius: 8px; font-size: 15px; outline: none; transition: 0.3s; }
        .form-group input[type="text"]:focus, .form-group input[type="url"]:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .form-group .btn { width: 100%; justify-content: center; padding: 14px; font-size: 16px; margin-top: 10px; }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes modalSlide { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-wrapper { margin-left: 0; width: 100%; }
            .menu-toggle { display: block; }
            .search-box { width: 200px; }
        }
        @media (max-width: 576px) {
            .top-header { padding: 0 15px; }
            .search-box { display: none; }
            .content-area { padding: 15px; }
            .stats-grid { grid-template-columns: 1fr; }
            .qr-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-qrcode"></i> QR Manager
        </div>
        <div class="sidebar-nav">
            <a class="nav-item nav-action-btn" onclick="openAddModal()">
                <i class="fas fa-plus-circle"></i> Generate New
            </a>

            <a class="nav-item active" id="nav-dashboard" onclick="switchView('dashboard')">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-item" id="nav-report" onclick="switchView('report')">
                <i class="fas fa-file-alt"></i> Print Report
            </a>
            <a class="nav-item" id="nav-analytics" onclick="switchView('analytics')">
                <i class="fas fa-chart-line"></i> Analytics
            </a>
            <a href="#" class="nav-item">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>
    </div>

    <div class="main-wrapper" id="mainWrapper">
        <header class="top-header">
            <div class="header-left">
                <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <div class="search-box" id="topSearchBox">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" onkeyup="filterQRs()" placeholder="Search QR codes...">
                </div>
            </div>
        </header>

        <div class="content-area">

            <div id="dashboard-container">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-qrcode"></i></div>
                        <div class="stat-info">
                            <h3>Total Generated</h3>
                            <p><?php echo $result->num_rows; ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);"><i class="fas fa-eye"></i></div>
                        <div class="stat-info">
                            <h3>Total Scans</h3>
                            <p><?php
                                $sumQuery = "SELECT SUM(count) as total FROM qr_codes";
                                $sumResult = $conn->query($sumQuery);
                                echo $sumResult->fetch_assoc()['total'] ?? 0;
                            ?></p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(118, 75, 162, 0.1); color: var(--secondary);"><i class="fas fa-calendar-check"></i></div>
                        <div class="stat-info">
                            <h3>This Month</h3>
                            <p><?php
                                $monthQuery = "SELECT SUM(count) as monthly FROM qr_codes WHERE MONTH(created_at) = MONTH(CURRENT_DATE())";
                                $monthResult = $conn->query($monthQuery);
                                echo $monthResult->fetch_assoc()['monthly'] ?? 0;
                            ?></p>
                        </div>
                    </div>
                </div>

                <div class="chart-section">
                    <canvas id="scanChart"></canvas>
                </div>

                <h2 class="section-title">Recent QR Codes</h2>
                <div class="qr-grid" id="qrGrid">
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $encryptedUrl = class_exists('Encryption') ? Encryption::encryptUrl($row['id']) : $row['id'];
                            $qrUrl = (function_exists('getBaseUrl') ? getBaseUrl() : '') . "./qr-page.php?data=" . urlencode($encryptedUrl);
                    ?>
                    <div class="qr-card qr-item" data-name="<?php echo strtolower($row['qrname']); ?>">
                        <div class="qr-header">
                            <h3 title="<?php echo htmlspecialchars($row['qrname']); ?>"><?php echo htmlspecialchars($row['qrname']); ?></h3>
                            <div class="actions">
                                <button onclick="editQR(<?php echo $row['id']; ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                <button onclick="deleteQR(<?php echo $row['id']; ?>)" class="delete" title="Delete"><i class="fas fa-trash"></i></button>
                                <button onclick="downloadQR('<?php echo htmlspecialchars($row['qrimage']); ?>')" title="Download"><i class="fas fa-download"></i></button>
                            </div>
                        </div>
                        <div class="qr-image">
                            <img src="<?php echo htmlspecialchars($row['qrimage']); ?>" alt="QR">
                        </div>
                        <div class="qr-stats">
                            <span><i class="fas fa-eye"></i> <?php echo $row['count']; ?> scans</span>
                            <span><i class="fas fa-clock"></i> <?php echo function_exists('getTimeAgo') ? getTimeAgo($row['created_at']) : date('M d, Y', strtotime($row['created_at'])); ?></span>
                        </div>
                        <div class="qr-url">
                            Target: <a href="<?php echo htmlspecialchars($row['finalurl'] ?? '#'); ?>" target="_blank" style="color: var(--primary); text-decoration:none;"><?php echo htmlspecialchars(substr($row['finalurl'] ?? 'Link', 0, 30)); ?>...</a>
                        </div>
                    </div>
                    <?php
                        }
                    } else {
                    ?>
                    <div class="no-data">
                        <i class="fas fa-box-open"></i>
                        <p>No QR codes found. Click "Generate New" to start!</p>
                    </div>
                    <?php } ?>
                </div>
            </div>

            <div id="report-container">
                <iframe id="reportFrame" class="app-iframe" src="" title="QR Print Report"></iframe>
            </div>

            <div id="analytics-container">
                <iframe id="analyticsFrame" class="app-iframe" src="" title="QR Analytics"></iframe>
            </div>

        </div>
    </div>

    <div id="qrModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Generate New QR Code</h2>
            <form id="qrForm" method="POST" action="add-qr.php">
                <input type="hidden" id="qrId" name="id">
                <div class="form-group">
                    <label for="qrname">QR Name Reference:</label>
                    <input type="text" id="qrname" name="qrname" required placeholder="e.g., ATTENDANCE, PRODUCT123">
                </div>
                <div class="form-group">
                    <label for="finalurl">Destination URL:</label>
                    <input type="url" id="finalurl" name="finalurl" required placeholder="https://example.com/landing-page">
                </div>
                <div class="form-group" style="display: flex; align-items: center; gap: 10px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <input type="checkbox" id="add_logo" name="add_logo" value="1" checked style="width: 18px; height: 18px; accent-color: var(--primary);">
                    <label for="add_logo" style="margin: 0; cursor: pointer;">
                        <i class="fas fa-image" style="color: var(--primary);"></i> Add Organization Logo to QR
                    </label>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-qrcode"></i> Generate QR Code</button>
            </form>
        </div>
    </div>

    <script>
        // ==========================================
        // UI & Layout Interactions
        // ==========================================

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        document.querySelector('.content-area').addEventListener('click', function() {
            if (window.innerWidth <= 992) {
                document.getElementById('sidebar').classList.remove('active');
            }
        });

        // Tab Switching Logic
        function switchView(view) {
            const dashContainer = document.getElementById('dashboard-container');
            const reportContainer = document.getElementById('report-container');
            const analyticsContainer = document.getElementById('analytics-container');

            const reportIframe = document.getElementById('reportFrame');
            const analyticsIframe = document.getElementById('analyticsFrame');

            const searchBox = document.getElementById('topSearchBox');

            // Reset active nav state
            document.querySelectorAll('.nav-item').forEach(el => {
                if(!el.classList.contains('nav-action-btn')) {
                    el.classList.remove('active');
                }
            });

            // Set active class to clicked item
            const targetNav = document.getElementById('nav-' + view);
            if(targetNav) targetNav.classList.add('active');

            // Hide all containers
            dashContainer.style.display = 'none';
            reportContainer.style.display = 'none';
            analyticsContainer.style.display = 'none';

            // Show selected view and load iframe if necessary
            if (view === 'report') {
                reportContainer.style.display = 'block';
                searchBox.style.visibility = 'hidden';

                if(reportIframe.src === "" || reportIframe.src === window.location.href) {
                    reportIframe.src = "report.php";
                }
            } else if (view === 'analytics') {
                analyticsContainer.style.display = 'block';
                searchBox.style.visibility = 'hidden';

                if(analyticsIframe.src === "" || analyticsIframe.src === window.location.href) {
                    analyticsIframe.src = "analytics.php";
                }
            } else {
                dashContainer.style.display = 'block';
                searchBox.style.visibility = 'visible';
            }

            // Auto-close sidebar on mobile
            if (window.innerWidth <= 992) toggleSidebar();
        }

        // Live Search Filter for Dashboard
        function filterQRs() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.qr-item');
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                if (name.includes(input)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // ==========================================
        // Modal & Form Logic
        // ==========================================
        const modal = document.getElementById("qrModal");

        function openAddModal() {
            document.getElementById("modalTitle").innerText = "Generate New QR Code";
            document.getElementById("qrForm").reset();
            document.getElementById("qrId").value = "";
            modal.style.display = "flex";
            if (window.innerWidth <= 992) toggleSidebar(); // Close sidebar on mobile
        }

        function closeModal() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }

        // AJAX Form Submission (Intercept the JSON output)
        document.getElementById('qrForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Stop standard form submission

            const formData = new FormData(this);

            // Show loading alert
            Swal.fire({
                title: 'Generating...',
                text: 'Please wait while we generate your QR code.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send via AJAX fetch
            fetch('add-qr.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message || 'QR code generated successfully.',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload(); // Reload dashboard to show new QR
                    });
                } else {
                    Swal.fire('Error', data.message || data.error || 'Failed to generate QR code.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'An unexpected network error occurred.', 'error');
            });
        });

        // ==========================================
        // Action Placeholders
        // ==========================================
        function editQR(id) {
            Swal.fire({
                title: 'Edit QR',
                text: 'Feature coming soon! (Wire this up to your fetch endpoint)',
                icon: 'info'
            });
        }

        function deleteQR(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire('Deleted!', 'Your file has been deleted.', 'success');
                }
            })
        }

        function downloadQR(imgSrc) {
            const link = document.createElement('a');
            link.href = imgSrc;
            link.download = 'QR_Code.png';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // ==========================================
        // Chart.js Initialization
        // ==========================================
        <?php
        $chartQuery = "SELECT DATE(created_at) as date, COUNT(*) as count FROM qr_codes GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 7";
        $chartResult = $conn->query($chartQuery);
        $dates = [];
        $counts = [];

        if($chartResult) {
            while($row = $chartResult->fetch_assoc()) {
                $dates[] = date('M d', strtotime($row['date']));
                $counts[] = $row['count'];
            }
        }
        ?>

        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('scanChart');
            if(ctx) {
                new Chart(ctx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode(array_reverse($dates)); ?>,
                        datasets: [{
                            label: 'QR Codes Generated',
                            data: <?php echo json_encode(array_reverse($counts)); ?>,
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.15)',
                            borderWidth: 3,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#667eea',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1 } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>