<?php
// Start output buffering and error reporting if needed
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/functions.php';
if(file_exists('includes/encryption.php')) {
    require_once 'includes/encryption.php';
}

// Get the current page name for active states
$currentPage = basename($_SERVER['PHP_SELF']);
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
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

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

        /* User Info in Sidebar Header */
        .sidebar-user-info {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 12px;
        }
        .sidebar-user-name {
            font-weight: 600;
            color: white;
            margin-bottom: 3px;
        }
        .sidebar-user-email {
            font-size: 10px;
            opacity: 0.7;
            word-break: break-all;
        }

        .sidebar-nav { padding: 20px 0; flex-grow: 1; display: flex; flex-direction: column; overflow-y: auto;}

        .nav-item {
            padding: 15px 25px; display: flex; align-items: center; gap: 15px;
            color: #cbd5e1; text-decoration: none; transition: all 0.2s; cursor: pointer; border-left: 4px solid transparent;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.05); color: var(--white); border-left-color: var(--primary);
        }

        /* Generate Button in Sidebar */
        .nav-action-btn {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            margin: 0 15px 15px 15px; border-radius: 8px; color: white !important;
            justify-content: center; font-weight: 600; border-left: none !important;
            box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3); padding: 12px;
        }
        .nav-action-btn:hover {
            transform: translateY(-2px); background: linear-gradient(135deg, var(--primary-dark), var(--secondary));
            box-shadow: 0 6px 15px rgba(102, 126, 234, 0.4);
        }

        /* Main Layout */
        .main-wrapper {
            flex-grow: 1; margin-left: var(--sidebar-width); display: flex; flex-direction: column;
            height: 100vh; transition: margin-left 0.3s ease; width: calc(100% - var(--sidebar-width));
        }

        /* Top Header */
        .top-header {
            height: 70px; background: var(--white); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between; padding: 0 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02); z-index: 100;
        }
        .header-left { display: flex; align-items: center; gap: 20px; }
        .menu-toggle { display: none; background: none; border: none; font-size: 24px; color: var(--dark); cursor: pointer; }
        .page-title { font-size: 18px; font-weight: 600; color: var(--dark); }

        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; font-size: 14px; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: var(--white); }
        .btn-success { background: var(--success); color: white; }
        .btn-outline { background: white; border: 1px solid #cbd5e0; color: var(--text-dark); }
        .btn-outline:hover { background: #f7fafc; border-color: var(--text-light); }

        /* Content Area */
        .content-area { flex-grow: 1; overflow-y: auto; padding: 30px; position: relative; background: var(--bg-body); }

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

        /* Settings Submenu Styles */
        .settings-submenu {
            display: none;
            flex-direction: column;
            gap: 2px;
            margin-bottom: 10px;
            padding-left: 20px;
        }

        .settings-submenu.show {
            display: flex;
        }

        .nav-item.logout-item {
            color: #ef4444;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 10px;
        }

        .nav-item.logout-item:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes modalSlide { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-wrapper { margin-left: 0; width: 100%; }
            .menu-toggle { display: block; }
        }
        @media (max-width: 576px) {
            .top-header { padding: 0 15px; }
            .content-area { padding: 15px; }
        }

        /* Print Overrides */
        @media print {
            body { background-color: white !important; overflow: visible !important; height: auto !important; display: block !important;}
            .sidebar, .top-header, .no-print { display: none !important; }
            .main-wrapper { margin-left: 0 !important; width: 100% !important; height: auto !important; overflow: visible !important; }
            .content-area { padding: 0 !important; overflow: visible !important; background: white !important; height: auto !important; }
        }
    </style>

    <script>
        // Parent Menu Toggle Script
        function toggleSubmenu(e, submenuId) {
            e.preventDefault();
            const submenu = document.getElementById(submenuId);
            const icon = e.currentTarget.querySelector('.submenu-icon');
            if (submenu.style.display === 'none' || submenu.style.display === '') {
                submenu.style.display = 'flex';
                if(icon) icon.style.transform = 'rotate(180deg)';
            } else {
                submenu.style.display = 'none';
                if(icon) icon.style.transform = 'rotate(0deg)';
            }
        }

        // Settings Submenu Toggle
        function toggleSettingsSubmenu(e) {
            e.preventDefault();
            const submenu = document.getElementById('settings-submenu');
            const icon = e.currentTarget.querySelector('.submenu-icon');
            if (submenu) {
                submenu.classList.toggle('show');
                if(icon) {
                    if(submenu.classList.contains('show')) {
                        icon.style.transform = 'rotate(180deg)';
                    } else {
                        icon.style.transform = 'rotate(0deg)';
                    }
                }
            }
        }

        // Logout Function with SweetAlert
        function logoutUser() {
            Swal.fire({
                title: 'Logout?',
                text: "Are you sure you want to logout?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, logout'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'index.php?logout=true';
                }
            });
        }
    </script>
</head>
<body>

    <div class="sidebar no-print" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-qrcode"></i> QR Manager
            <?php if(isset($_SESSION['user_id'])): ?>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name">
                    <i class="fas fa-user-circle" style="font-size: 12px;"></i> <?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']); ?>
                </div>
                <div class="sidebar-user-email">
                    <?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="sidebar-nav">
            <a class="nav-item nav-action-btn" onclick="openAddModal()">
                <i class="fas fa-plus-circle"></i> Generate New
            </a>
            <a href="index.php" class="nav-item <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="report.php" class="nav-item <?php echo ($currentPage == 'report.php') ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i> Print Report
            </a>

            <?php $isMealMenuOpen = in_array($currentPage, ['Canteen-Coupon.php', 'coupon-report.php']); ?>
            <a href="#" class="nav-item <?php echo $isMealMenuOpen ? 'active' : ''; ?>" onclick="toggleSubmenu(event, 'meal-coupon-submenu')">
                <i class="fas fa-ticket-alt"></i> Meal Coupons
                <i class="fas fa-chevron-down submenu-icon" style="margin-left:auto; transition: transform 0.3s; transform: <?php echo $isMealMenuOpen ? 'rotate(180deg)' : 'rotate(0deg)'; ?>;"></i>
            </a>

            <div class="submenu no-print" id="meal-coupon-submenu" style="display: <?php echo $isMealMenuOpen ? 'flex' : 'none'; ?>; flex-direction: column; gap: 2px; margin-bottom: 10px; padding-left: 20px;">

                <a href="Canteen-Coupon.php" class="nav-item <?php echo ($currentPage == 'Canteen-Coupon.php') ? 'active' : ''; ?>" style="font-size: 13px; padding: 8px 15px; border-left: none;">
                    <i class="fas fa-plus-square"></i> Generator Console
                </a>

                <?php if($currentPage == 'Canteen-Coupon.php'): ?>
                    <a href="#" onclick="updateUI(); return false;" class="nav-item" style="font-size: 13px; padding: 8px 15px; color: #cbd5e1; border-left: none;">
                        <i class="fas fa-sync-alt"></i> Update Preview
                    </a>
                    <a href="#" onclick="prepareAndPrint(); return false;" class="nav-item" style="font-size: 13px; padding: 8px 15px; color: #3b82f6; border-left: none;">
                        <i class="fas fa-print"></i> Generate & Print
                    </a>
                <?php endif; ?>

                <a href="coupon-report.php" class="nav-item <?php echo ($currentPage == 'coupon-report.php') ? 'active' : ''; ?>" style="font-size: 13px; padding: 8px 15px; color: #10b981; border-left: none;">
                    <i class="fas fa-chart-pie"></i> View Reports
                </a>

                <a href="#" onclick="resetCouponDatabase(); return false;" class="nav-item" style="font-size: 13px; padding: 8px 15px; color: #ef4444; border-left: none;">
                    <i class="fas fa-trash-alt"></i> Reset Database
                </a>
            </div>
            <a href="analytics.php" class="nav-item <?php echo ($currentPage == 'analytics.php') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i> Analytics
            </a>

            <!-- Settings with Submenu and Logout -->
            <a href="#" class="nav-item" onclick="toggleSettingsSubmenu(event)">
                <i class="fas fa-cog"></i> Settings
                <i class="fas fa-chevron-down submenu-icon" style="margin-left:auto; transition: transform 0.3s;"></i>
            </a>

            <div class="settings-submenu" id="settings-submenu">
                <a href="#" class="nav-item" style="font-size: 13px; padding: 8px 15px; border-left: none;" onclick="showProfileModal()">
                    <i class="fas fa-user-circle"></i> My Profile
                </a>
                <a href="#" class="nav-item" style="font-size: 13px; padding: 8px 15px; border-left: none;" onclick="showChangePasswordModal()">
                    <i class="fas fa-lock"></i> Change Password
                </a>
                <a href="#" class="nav-item" style="font-size: 13px; padding: 8px 15px; border-left: none;" onclick="showNotificationsSettings()">
                    <i class="fas fa-bell"></i> Notifications
                </a>
            </div>

            <!-- Logout Button outside settings submenu for better visibility -->
            <a href="#" class="nav-item logout-item" onclick="logoutUser()">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-wrapper" id="mainWrapper">
        <header class="top-header no-print">
            <div class="header-left">
                <button class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
                <div class="page-title">
                    <?php
                        if ($currentPage == 'index.php') echo 'Dashboard Overview';
                        elseif ($currentPage == 'report.php') echo 'Print Manager';
                        elseif ($currentPage == 'analytics.php') echo 'Advanced Analytics';
                        elseif ($currentPage == 'Canteen-Coupon.php') echo 'Meal Coupon Generator';
                        elseif ($currentPage == 'coupon-report.php') echo 'Coupon Scan Reports';
                    ?>
                </div>
            </div>
            <?php if(isset($_SESSION['user_id'])): ?>
            <div class="header-right">
                <span style="font-size: 14px; color: var(--gray);">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['fullname'] ?? $_SESSION['username']); ?>
                </span>
            </div>
            <?php endif; ?>
        </header>

        <div class="content-area">