<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Handle Logout
if (isset($_GET['logout']) && $_GET['logout'] == 'true') {
    session_destroy();
    header('Location: https://igipess.du.ac.in/dashboard.php');
    exit;
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['username']);

// Handle Login AJAX Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');

    $username = $conn->real_escape_string($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter username and password.']);
        exit;
    }

    $query = "SELECT id, username, password, fullname, email, role_id FROM users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role_id'] = $user['role_id'];
            echo json_encode(['success' => true, 'message' => 'Login successful!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid password.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Username not found.']);
    }
    $stmt->close();
    exit;
}

// If not logged in, show only login page without dashboard
if (!$isLoggedIn):
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | QR Code Generator</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 35px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            margin: 20px;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .login-header i {
            font-size: 60px;
            margin-bottom: 15px;
        }

        .login-header h2 {
            margin: 0;
            font-weight: 600;
        }

        .login-header p {
            margin: 5px 0 0;
            opacity: 0.9;
        }

        .login-body {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .input-group-text {
            background: #f8f9fa;
            border-right: none;
        }

        .form-control {
            border-left: none;
            padding: 12px 15px;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: none;
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            font-size: 16px;
            border-radius: 10px;
            transition: transform 0.2s, box-shadow 0.2s;
            color: white;
            width: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .login-footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="fas fa-qrcode"></i>
            <h2>QR Code Generator</h2>
            <p>Sign in to access your dashboard</p>
        </div>
        <div class="login-body">
            <form id="loginForm">
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required autofocus>
                    </div>
                </div>
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    </div>
                </div>
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i> Login
                </button>
            </form>
        </div>
        <div class="login-footer">
            <i class="fas fa-shield-alt"></i> Secure Access | QR Code Management System
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();

            Swal.fire({
                title: 'Logging in...',
                text: 'Please wait while we verify your credentials.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData(this);

            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Login Failed',
                        text: data.message,
                        icon: 'error',
                        confirmButtonColor: '#667eea'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Network error occurred. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#667eea'
                });
            });
        });
    </script>
</body>
</html>
<?php
    exit;
endif;

// ==============================================
// LOGGED IN USER - DISPLAY ORIGINAL DASHBOARD
// ==============================================
?>

<?php include 'header.php'; ?>

<?php
// Get all QR codes
$query = "SELECT * FROM qr_codes ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<style>
    /* Dashboard Specific Styles */
    .search-wrapper { position: relative; max-width: 400px; margin-bottom: 25px; }
    .search-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: var(--gray); }
    .search-input { width: 100%; padding: 12px 15px 12px 40px; border: 1px solid var(--border); border-radius: 30px; outline: none; transition: all 0.3s ease; }
    .search-input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15); }
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; margin-bottom: 30px; }
    .stat-card { background: var(--white); padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 20px; border: 1px solid var(--border); }
    .stat-icon { width: 60px; height: 60px; border-radius: 12px; background: rgba(102, 126, 234, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 24px; }
    .stat-info h3 { font-size: 14px; color: var(--gray); margin-bottom: 5px; font-weight: 500; }
    .stat-info p { font-size: 24px; font-weight: 700; color: var(--dark); }
    .chart-section { background: var(--white); padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); margin-bottom: 30px; border: 1px solid var(--border); height: 350px; }
    .section-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; color: var(--dark); }
    .qr-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
    .qr-card { background: var(--white); border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.02); border: 1px solid var(--border); transition: transform 0.2s; }
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
</style>

<div class="search-wrapper">
    <i class="fas fa-search"></i>
    <input type="text" id="searchInput" class="search-input" placeholder="Search QR codes..." onkeyup="filterQRs()">
</div>

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

<?php include 'footer.php'; ?>

<script>
    // Live Search Filter
    function filterQRs() {
        const input = document.getElementById('searchInput').value.toLowerCase();
        const cards = document.querySelectorAll('.qr-item');
        cards.forEach(card => {
            const name = card.getAttribute('data-name');
            card.style.display = name.includes(input) ? 'block' : 'none';
        });
    }

    // Chart.js Initialization
    <?php
    $chartQuery = "SELECT DATE(created_at) as date, COUNT(*) as count FROM qr_codes GROUP BY DATE(created_at) ORDER BY date DESC LIMIT 7";
    $chartResult = $conn->query($chartQuery);
    $dates = []; $counts = [];
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
                        borderColor: '#667eea', backgroundColor: 'rgba(102, 126, 234, 0.15)',
                        borderWidth: 3, pointBackgroundColor: '#fff', fill: true, tension: 0.4
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { grid: { display: false } } }
                }
            });
        }
    });

    // =========================================================================
    // BULLETPROOF GENERATE QR LOGIC (Overrides old script.js files)
    // =========================================================================
    document.addEventListener('DOMContentLoaded', function() {
        let originalForm = document.getElementById('qrForm');

        if (originalForm) {
            // Clone the form to strip any old Javascript event listeners attached by script.js
            let newForm = originalForm.cloneNode(true);
            originalForm.parentNode.replaceChild(newForm, originalForm);

            // Attach our new bulletproof SweetAlert listener
            newForm.addEventListener('submit', function(e) {
                e.preventDefault(); // Stop page reload

                // Show Loading
                Swal.fire({
                    title: 'Generating...',
                    text: 'Please wait while we generate your QR code.',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                const formData = new FormData(this);

                // Fetch data
                fetch('add-qr.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text()) // Read as raw text to avoid PHP warnings breaking JSON
                .then(text => {
                    try {
                        const data = JSON.parse(text);
                        if(data.success || data.success === 'true') {
                            Swal.fire({
                                title: 'Success!',
                                text: data.message || 'QR code generated successfully.',
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload(); // Refresh the grid
                            });
                        } else {
                            Swal.fire('Error', data.message || 'Failed to generate QR code.', 'error');
                        }
                    } catch (err) {
                        console.error("Raw response:", text);
                        Swal.fire('Error', 'Unexpected server response. Check console.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Network Error:', error);
                    Swal.fire('Error', 'A network error occurred.', 'error');
                });
            });
        }
    });

   function editQR(id) {
        // Show loading popup while fetching the data
        Swal.fire({
            title: 'Loading...',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        // Fetch the existing QR details from the database
        fetch('get-qr-stats.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                Swal.close();

                // Change Modal Title & Button Text for Editing
                document.getElementById("modalTitle").innerText = "Edit QR Code";
                const submitBtn = document.querySelector('#qrForm button[type="submit"]');
                if (submitBtn) submitBtn.innerText = "Update QR Code";

                // Populate the form fields with existing data
                document.getElementById("qrId").value = data.data.id;
                document.getElementById("qrname").value = data.data.qrname;
                document.getElementById("finalurl").value = data.data.finalurl;

                // Hide logo selection because editing just updates the destination URL
                const logoContainer = document.getElementById("logo_selection");
                if (logoContainer && logoContainer.parentElement) {
                    logoContainer.parentElement.style.display = 'none';
                }
                const previewContainer = document.getElementById("logo_preview_container");
                if (previewContainer) {
                    previewContainer.style.display = 'none';
                }

                // Open the modal
                document.getElementById("qrModal").style.display = "flex";
            } else {
                Swal.fire('Error', data.message || 'Failed to fetch QR details.', 'error');
            }
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Network error while loading data.', 'error');
        });
    }

    // =========================================================================
    // POST-BASED DELETE LOGIC
    // =========================================================================
    function deleteQR(id) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete the QR code and all its scan data!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {

                Swal.fire({
                    title: 'Deleting...',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                const formData = new FormData();
                formData.append('id', id);

                fetch('delete-qr.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: data.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message || 'Failed to delete the QR code.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'A network error occurred while deleting.', 'error');
                });
            }
        });
    }

    function downloadQR(imgSrc) {
        const link = document.createElement('a');
        link.href = imgSrc; link.download = 'QR_Code.png';
        document.body.appendChild(link); link.click(); document.body.removeChild(link);
    }
</script>