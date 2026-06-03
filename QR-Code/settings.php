This is a fantastic addition. Centralizing your organization’s settings into the database and adding a secure password prompt for destructive actions like a database reset are absolute best practices for a dashboard.

Here is the complete guide and the code to make this work perfectly.

### Step 1: Create the Settings Table
First, run this SQL query in your database (via phpMyAdmin or your DB manager). It creates the `qr_system_setting` table and inserts your static default values.

```sql
CREATE TABLE IF NOT EXISTS `qr_system_setting` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `org_name` varchar(255) NOT NULL,
    `org_nickname` varchar(50) NOT NULL,
    `org_address` text NOT NULL,
    `org_url` varchar(255) NOT NULL,
    `logo_path` varchar(255) NOT NULL,
    `app_url` varchar(255) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `qr_system_setting` (`org_name`, `org_nickname`, `org_address`, `org_url`, `logo_path`, `app_url`) VALUES
('Indira Gandhi Institute of Physical Education and Sports Sciences (University of Delhi)', 'IGIPESS', 'B-Block Vikaspuri, New Delhi - 110018', 'https://igipess.du.ac.in/', 'igipesslogo1.png', 'https://igipess.du.ac.in/QR-Code/');
```

---

### Step 2: Update `header.php`
We need to update the sidebar links to make the **Settings** button active and add a secure SweetAlert2 password prompt for the **Reset Database** button.

Find the sidebar section in your `header.php` and replace the Reset Database, View Reports, Analytics, and Settings links with this code. I also added the necessary JavaScript at the bottom of the header.

```php
                                <a href="#" class="nav-item" style="font-size: 13px; padding: 8px 15px; color: #ef4444; border-left: none;" onclick="promptResetDatabase()">
                                        <i class="fas fa-trash-alt"></i> Reset Database
                                </a>
                                <a href="coupon-report.php" target="_blank" class="nav-item" style="font-size: 13px; padding: 8px 15px; color: #10b981; border-left: none;">
                                        <i class="fas fa-chart-pie"></i> View Reports
                                </a>
                        </div>
                        <a href="analytics.php" class="nav-item <?php echo ($currentPage == 'analytics.php') ? 'active' : ''; ?>">
                                <i class="fas fa-chart-line"></i> Analytics
                        </a>
                        <a href="settings.php" class="nav-item <?php echo ($currentPage == 'settings.php') ? 'active' : ''; ?>">
                                <i class="fas fa-cog"></i> Settings
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
                                                elseif ($currentPage == 'settings.php') echo 'System Settings'; // Added Settings Title
                                        ?>
                                </div>
                        </div>
                </header>

                <script>
                        function promptResetDatabase() {
                                Swal.fire({
                                        title: 'Are you absolutely sure?',
                                        text: "This will permanently delete ALL meal coupons and scan logs!",
                                        icon: 'warning',
                                        input: 'password',
                                        inputPlaceholder: 'Enter your password to confirm',
                                        inputAttributes: { autocapitalize: 'off', autocorrect: 'off' },
                                        showCancelButton: true,
                                        confirmButtonColor: '#ef4444',
                                        cancelButtonColor: '#6b7280',
                                        confirmButtonText: 'Yes, Reset Database',
                                        showLoaderOnConfirm: true,
                                        preConfirm: (password) => {
                                                if (!password) {
                                                        Swal.showValidationMessage('Password is required');
                                                        return false;
                                                }
                                                const formData = new FormData();
                                                formData.append('password', password);

                                                return fetch('ajax-reset-db.php', {
                                                        method: 'POST',
                                                        body: formData
                                                })
                                                .then(response => {
                                                        if (!response.ok) throw new Error(response.statusText);
                                                        return response.json();
                                                })
                                                .catch(error => { Swal.showValidationMessage(`Request failed: ${error}`); });
                                        },
                                        allowOutsideClick: () => !Swal.isLoading()
                                }).then((result) => {
                                        if (result.isConfirmed) {
                                                if (result.value.success) {
                                                        Swal.fire('Reset!', 'Database cleared successfully.', 'success').then(() => { window.location.reload(); });
                                                } else {
                                                        Swal.fire('Failed', result.value.message || 'Incorrect password.', 'error');
                                                }
                                        }
                                });
                        }
                </script>
```

---

### Step 3: Create `ajax-reset-db.php`
This file receives the password from the SweetAlert prompt, checks it against the `users` table, and safely clears the meal coupon database.

Create a new file named **`ajax-reset-db.php`** and paste this inside:

```php
<?php
session_start();
require_once 'includes/config.php';

header('Content-Type: application/json');

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        exit;
}

$password = $_POST['password'] ?? '';

// Assuming you store the logged-in user's username or ID in the session.
// Change 'username' below to whatever session variable you use for login (e.g., $_SESSION['user_id']).
$loggedInUser = $_SESSION['username'] ?? '';

if (empty($loggedInUser)) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
        exit;
}

// Fetch the user's password from the database
$stmt = $conn->prepare("SELECT password FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $loggedInUser);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
}

$user = $result->fetch_assoc();

// Verify the password (assuming you are using password_hash() to store passwords)
// If you are storing passwords in plain text or MD5, change this logic (though password_hash is highly recommended!)
if (password_verify($password, $user['password'])) {

        // Passwords match! Reset the databases
        $conn->query("TRUNCATE TABLE qr_code_coupon");
        $conn->query("TRUNCATE TABLE qr_scan_logs_coupon");

        echo json_encode(['success' => true, 'message' => 'Databases have been reset successfully.']);
} else {
        echo json_encode(['success' => false, 'message' => 'Incorrect password. Action aborted.']);
}
?>
```
*(Note: I assumed you use `password_verify()` for your login system. If your `users` table uses raw text or MD5 for passwords—which isn't recommended—let me know and we can adjust the `ajax-reset-db.php` check!)*

---

### Step 4: Create `settings.php`
This is the front-end page where admins can view and update the static variables in `qr_system_setting`.

Create a new file named **`settings.php`** and paste this code:

```php
<?php include 'header.php'; ?>
<?php
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
        $org_name = $conn->real_escape_string($_POST['org_name']);
        $org_nickname = $conn->real_escape_string($_POST['org_nickname']);
        $org_address = $conn->real_escape_string($_POST['org_address']);
        $org_url = $conn->real_escape_string($_POST['org_url']);
        $app_url = $conn->real_escape_string($_POST['app_url']);
        $logo_path = $conn->real_escape_string($_POST['logo_path']);

        $updateQuery = "UPDATE qr_system_setting SET
                                        org_name = '$org_name',
                                        org_nickname = '$org_nickname',
                                        org_address = '$org_address',
                                        org_url = '$org_url',
                                        app_url = '$app_url',
                                        logo_path = '$logo_path'
                                        WHERE id = 1";

        if ($conn->query($updateQuery)) {
                echo "<script>Swal.fire('Success', 'System settings updated successfully!', 'success');</script>";
        } else {
                echo "<script>Swal.fire('Error', 'Failed to update settings: " . $conn->error . "', 'error');</script>";
        }
}

// Fetch current settings
$settingsQuery = $conn->query("SELECT * FROM qr_system_setting LIMIT 1");
$settings = $settingsQuery->fetch_assoc();
?>

<style>
        .settings-container {
                background: #fff;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                max-width: 800px;
                margin: 20px auto;
                font-family: Arial, sans-serif;
        }
        .settings-container h2 {
                margin-top: 0;
                color: #1e293b;
                border-bottom: 2px solid #f1f5f9;
                padding-bottom: 10px;
                margin-bottom: 20px;
        }
        .form-group {
                margin-bottom: 15px;
        }
        .form-group label {
                display: block;
                font-weight: bold;
                margin-bottom: 5px;
                color: #334155;
        }
        .form-group input, .form-group textarea {
                width: 100%;
                padding: 10px;
                border: 1px solid #cbd5e1;
                border-radius: 4px;
                box-sizing: border-box;
                font-size: 14px;
        }
        .form-group input:focus, .form-group textarea:focus {
                border-color: #3b82f6;
                outline: none;
        }
        .btn-save {
                background-color: #3b82f6;
                color: white;
                border: none;
                padding: 12px 20px;
                font-size: 16px;
                border-radius: 4px;
                cursor: pointer;
                transition: 0.2s;
                width: 100%;
                font-weight: bold;
        }
        .btn-save:hover {
                background-color: #2563eb;
        }
</style>

<div class="content-area">
        <div class="settings-container">
                <h2><i class="fas fa-sliders-h"></i> System Configuration</h2>
                <form method="POST" action="">
                        <div class="form-group">
                                <label>Organization Name</label>
                                <input type="text" name="org_name" value="<?php echo htmlspecialchars($settings['org_name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                                <label>Nickname / Short Name</label>
                                <input type="text" name="org_nickname" value="<?php echo htmlspecialchars($settings['org_nickname'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                                <label>Organization Address</label>
                                <textarea name="org_address" rows="3" required><?php echo htmlspecialchars($settings['org_address'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                                <label>Main Website URL</label>
                                <input type="url" name="org_url" value="<?php echo htmlspecialchars($settings['org_url'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                                <label>QR App Base URL</label>
                                <input type="url" name="app_url" value="<?php echo htmlspecialchars($settings['app_url'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                                <label>Logo File Name (e.g., igipesslogo1.png)</label>
                                <input type="text" name="logo_path" value="<?php echo htmlspecialchars($settings['logo_path'] ?? ''); ?>" required>
                        </div>

                        <button type="submit" name="update_settings" class="btn-save"><i class="fas fa-save"></i> Save Settings</button>
                </form>
        </div>
</div>

<?php include 'footer.php'; ?>
```

Would you like me to show you how to pull these variables into your `Canteen-Coupon.php` file so the coupon generator pulls its text directly from this new settings table?