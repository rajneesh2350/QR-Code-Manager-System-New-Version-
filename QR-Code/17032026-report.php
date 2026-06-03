<?php
// Turn on error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to catch any errors
ob_start();

require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/encryption.php';

// Test database connection
if (!$conn || $conn->connect_error) {
    die("Database connection failed: " . ($conn->connect_error ?? 'Unknown error'));
}

// Get all QR codes from database
$query = "SELECT * FROM qr_codes ORDER BY created_at ASC";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

$qrCodes = [];
while ($row = $result->fetch_assoc()) {
    $qrCodes[] = $row;
}

// Ensure QR codes directory exists
$qrDir = 'assets/qrcodes/';
if (!file_exists($qrDir)) {
    mkdir($qrDir, 0755, true);
}

// Function to ensure QR image exists
function ensureQRImage($qr) {
    global $qrDir, $conn;

    // If image path exists and file exists, return it
    if (!empty($qr['qrimage']) && file_exists($qr['qrimage'])) {
        return $qr['qrimage'];
    }

    // Generate new QR code
    $encryptedData = Encryption::encryptUrl($qr['id']);
    $baseUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') .
               $_SERVER['HTTP_HOST'] .
               rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
    $qrUrl = $baseUrl . 'qr-page.php?data=' . urlencode($encryptedData);

    // Generate filename
    $filename = 'qr_' . $qr['id'] . '_' . time() . '.png';
    $filePath = $qrDir . $filename;

    // Try multiple QR generation methods
    $generated = false;

    // Method 1: QR Server API
    $apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&ecc=H&margin=10&data=' . urlencode($qrUrl);

    if (function_exists('curl_init')) {
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        $qrImage = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 && $qrImage && strlen($qrImage) > 500) {
            if (file_put_contents($filePath, $qrImage) !== false) {
                $generated = true;
            }
        }
    }

    // Method 2: Google Charts API (fallback)
    if (!$generated) {
        $apiUrl = 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode($qrUrl) . '&choe=UTF-8&chld=H|4';
        $qrImage = @file_get_contents($apiUrl);
        if ($qrImage && strlen($qrImage) > 500) {
            if (file_put_contents($filePath, $qrImage) !== false) {
                $generated = true;
            }
        }
    }

    if ($generated) {
        // Update database with new image path
        $updateStmt = $conn->prepare("UPDATE qr_codes SET qrimage = ? WHERE id = ?");
        $relativePath = 'assets/qrcodes/' . $filename;
        $updateStmt->bind_param("si", $relativePath, $qr['id']);
        $updateStmt->execute();
        $updateStmt->close();

        return $relativePath;
    }

    // Return a data URI with a simple QR code as last resort
    return 'data:image/svg+xml,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200"><rect width="200" height="200" fill="#f0f0f0"/><text x="50" y="100" font-family="Arial" font-size="14" fill="#666">QR Code</text><text x="30" y="130" font-family="Arial" font-size="14" fill="#666">' . substr($qr['qrname'], 0, 15) . '</text></svg>');
}

// Clear output buffer
ob_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Report - Select QR Codes to Print</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f0f2f5;
            font-family: Arial, Helvetica, sans-serif;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .controls {
            text-align: center;
            margin-bottom: 20px;
            position: sticky;
            top: 20px;
            z-index: 1000;
            width: 100%;
            max-width: 21.59cm;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
            margin: 0 5px;
            border: 2px solid white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .btn i {
            margin-right: 8px;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #48bb78, #38a169);
        }

        .selection-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .selection-actions {
            display: flex;
            gap: 10px;
        }

        .selection-actions button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: all 0.2s ease;
        }

        .select-all-btn {
            background: #667eea;
            color: white;
        }

        .deselect-all-btn {
            background: #f56565;
            color: white;
        }

        .selected-count {
            font-size: 16px;
            font-weight: bold;
            color: #4a5568;
        }

        .selected-count span {
            color: #667eea;
            font-size: 20px;
            margin: 0 5px;
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
            padding: 10px;
        }

        .pagination button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            background: #667eea;
            color: white;
            cursor: pointer;
            font-weight: bold;
        }

        .pagination button:disabled {
            background: #cbd5e0;
            cursor: not-allowed;
        }

        .pagination span {
            font-size: 16px;
            font-weight: bold;
            color: #4a5568;
        }

        .page-indicator {
            text-align: center;
            margin: 10px 0;
            font-size: 14px;
            color: #667eea;
            font-weight: bold;
        }

        /* Report Container - Legal Size (21.59cm x 35.56cm) */
        .report-container {
            width: 21.59cm;
            min-height: 35.56cm;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 0.5cm;
            margin: 0 auto 20px auto;
            position: relative;
            display: none;
        }

        .report-container.active {
            display: block;
        }

        /* QR Grid - 5 Rows x 3 Columns */
        .qr-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(5, 1fr);
            gap: 0.3cm;
            height: calc(35.56cm - 3cm);
            min-height: 32cm;
        }

        /* QR Card */
        .qr-card {
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.2cm;
            display: flex;
            flex-direction: column;
            background: white;
            page-break-inside: avoid;
            break-inside: avoid;
            overflow: hidden;
            position: relative;
        }

        /* Checkbox for selection */
        .qr-select {
            position: absolute;
            top: 5px;
            right: 5px;
            z-index: 10;
        }

        .qr-select input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #667eea;
        }

        /* QR Image Container */
        .qr-image-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            border-radius: 5px;
            padding: 0.2cm;
            min-height: 4cm;
        }

        .qr-image-container img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        /* QR Name */
        .qr-name {
            text-align: center;
            margin-top: 0.2cm;
            padding: 0.2cm 0.1cm;
            background: #333;
            color: white;
            font-weight: bold;
            font-size: 12px;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            word-break: break-word;
            border-radius: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Empty Cell Styling */
        .qr-card.empty {
            border: 2px dashed #cbd5e0;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr-card.empty::after {
            content: "EMPTY SLOT";
            color: #a0aec0;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 2px;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }

            .controls, .selection-bar, .pagination, .page-indicator {
                display: none !important;
            }

            .report-container {
                display: block !important;
                box-shadow: none;
                margin: 0;
                padding: 0.5cm;
                page-break-after: always;
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .qr-card {
                border: 1px solid #ddd;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .qr-name {
                background: #333 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @page {
                size: Legal portrait;
                margin: 0.5cm;
            }
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border: 1px solid #faa;
        }
    </style>
</head>
<body>
    <div class="controls">
        <div class="selection-bar">
            <div class="selection-actions">
                <button class="select-all-btn" onclick="selectAll()">
                    <i class="fas fa-check-double"></i> Select All
                </button>
                <button class="deselect-all-btn" onclick="deselectAll()">
                    <i class="fas fa-times"></i> Deselect All
                </button>
            </div>
            <div class="selected-count">
                Selected: <span id="selectedCount">0</span> / <?php echo count($qrCodes); ?>
            </div>
        </div>

        <button class="btn" onclick="preparePrint()">
            <i class="fas fa-print"></i> Print Selected QR Codes
        </button>
        <button class="btn btn-secondary" onclick="window.location.href='index.php'">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </button>
    </div>

    <?php if (empty($qrCodes)): ?>
    <div class="error-message">
        <strong>No QR Codes Found!</strong> Please create some QR codes first.
    </div>
    <?php endif; ?>

    <!-- Preview Pages -->
    <div id="previewPages"></div>

    <!-- QR Codes Grid for Selection -->
    <div class="report-container active" id="selectionContainer">
        <div class="qr-grid">
            <?php
            $totalCells = 15; // 5 rows x 3 columns
            $qrCount = count($qrCodes);

            for ($i = 0; $i < $totalCells; $i++) {
                if ($i < $qrCount) {
                    // Display actual QR code
                    $qr = $qrCodes[$i];
                    $qrImagePath = ensureQRImage($qr);
            ?>
            <div class="qr-card" data-id="<?php echo $qr['id']; ?>" data-index="<?php echo $i; ?>">
                <div class="qr-select">
                    <input type="checkbox" class="qr-checkbox" value="<?php echo $i; ?>" data-qr='<?php echo json_encode($qr); ?>' onchange="updateSelectedCount()">
                </div>
                <div class="qr-image-container">
                    <img src="<?php echo htmlspecialchars($qrImagePath); ?>"
                         alt="<?php echo htmlspecialchars($qr['qrname']); ?>"
                         onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22200%22%20height%3D%22200%22%20viewBox%3D%220%200%20200%20200%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23f0f0f0%22%2F%3E%3Ctext%20x%3D%2250%22%20y%3D%22100%22%20font-family%3D%22Arial%22%20font-size%3D%2214%22%20fill%3D%22%23666%22%3EQR%20Code%3C%2Ftext%3E%3C%2Fsvg%3E';">
                </div>
                <div class="qr-name" title="<?php echo htmlspecialchars($qr['qrname']); ?>">
                    <?php echo strtoupper(htmlspecialchars($qr['qrname'])); ?>
                </div>
            </div>
            <?php
                } else {
                    // Empty cell
            ?>
            <div class="qr-card empty"></div>
            <?php
                }
            }
            ?>
        </div>
    </div>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        // Store QR codes data as JavaScript array
        const qrCodes = <?php echo json_encode($qrCodes); ?>;
        let currentPage = 1;
        let totalPages = 1;
        let selectedQRs = [];

        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.qr-checkbox:checked');
            document.getElementById('selectedCount').textContent = checkboxes.length;

            // Update selectedQRs array
            selectedQRs = [];
            checkboxes.forEach(cb => {
                const index = parseInt(cb.value);
                if (index < qrCodes.length) {
                    selectedQRs.push(qrCodes[index]);
                }
            });
        }

        function selectAll() {
            document.querySelectorAll('.qr-checkbox').forEach(cb => cb.checked = true);
            updateSelectedCount();
        }

        function deselectAll() {
            document.querySelectorAll('.qr-checkbox').forEach(cb => cb.checked = false);
            updateSelectedCount();
        }

        function preparePrint() {
            if (selectedQRs.length === 0) {
                alert('Please select at least one QR code to print.');
                return;
            }

            console.log('Selected QRs:', selectedQRs.length);

            // Calculate number of pages needed (15 per page)
            totalPages = Math.ceil(selectedQRs.length / 15);
            console.log('Total pages:', totalPages);

            // Create preview pages
            const previewPages = document.getElementById('previewPages');
            previewPages.innerHTML = '';

            for (let page = 0; page < totalPages; page++) {
                const startIdx = page * 15;
                const endIdx = Math.min(startIdx + 15, selectedQRs.length);
                const pageQRs = selectedQRs.slice(startIdx, endIdx);

                console.log(`Creating page ${page + 1} with ${pageQRs.length} QR codes`);

                // Create page container
                const pageDiv = document.createElement('div');
                pageDiv.className = 'report-container';
                pageDiv.id = `page-${page + 1}`;

                // Create grid
                const gridDiv = document.createElement('div');
                gridDiv.className = 'qr-grid';

                // Add QR codes to grid
                for (let i = 0; i < 15; i++) {
                    if (i < pageQRs.length) {
                        const qr = pageQRs[i];
                        const cardDiv = document.createElement('div');
                        cardDiv.className = 'qr-card';
                        cardDiv.innerHTML = `
                            <div class="qr-image-container">
                                <img src="${qr.qrimage || 'assets/img/placeholder.png'}"
                                     alt="${qr.qrname}"
                                     onerror="this.src='data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22200%22%20height%3D%22200%22%20viewBox%3D%220%200%20200%20200%22%3E%3Crect%20width%3D%22200%22%20height%3D%22200%22%20fill%3D%22%23f0f0f0%22%2F%3E%3Ctext%20x%3D%2250%22%20y%3D%22100%22%20font-family%3D%22Arial%22%20font-size%3D%2214%22%20fill%3D%22%23666%22%3EQR%20Code%3C%2Ftext%3E%3C%2Fsvg%3E';">
                            </div>
                            <div class="qr-name" title="${qr.qrname}">
                                ${qr.qrname.toUpperCase()}
                            </div>
                        `;
                        gridDiv.appendChild(cardDiv);
                    } else {
                        const emptyDiv = document.createElement('div');
                        emptyDiv.className = 'qr-card empty';
                        gridDiv.appendChild(emptyDiv);
                    }
                }

                pageDiv.appendChild(gridDiv);

                // Add page number
                const pageNum = document.createElement('div');
                pageNum.style.textAlign = 'center';
                pageNum.style.fontSize = '12px';
                pageNum.style.color = '#667eea';
                pageNum.style.fontWeight = 'bold';
                pageNum.style.marginTop = '10px';
                pageNum.textContent = `Page ${page + 1} of ${totalPages}`;
                pageDiv.appendChild(pageNum);

                previewPages.appendChild(pageDiv);
            }

            // Show first page
            showPage(1);

            // Create pagination controls
            createPagination();
        }

        function showPage(pageNum) {
            // Hide all pages
            document.querySelectorAll('.report-container').forEach(container => {
                container.classList.remove('active');
            });

            // Show selected page
            const pageToShow = document.getElementById(`page-${pageNum}`);
            if (pageToShow) {
                pageToShow.classList.add('active');
                currentPage = pageNum;
            }
        }

        function createPagination() {
            // Remove existing pagination
            const existingPagination = document.querySelector('.pagination');
            if (existingPagination) {
                existingPagination.remove();
            }

            // Create new pagination
            const paginationDiv = document.createElement('div');
            paginationDiv.className = 'pagination';
            paginationDiv.innerHTML = `
                <button onclick="changePage(-1)" ${currentPage === 1 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
                <span>Page ${currentPage} of ${totalPages}</span>
                <button onclick="changePage(1)" ${currentPage === totalPages ? 'disabled' : ''}>
                    Next <i class="fas fa-chevron-right"></i>
                </button>
                <button class="btn" onclick="printAllPages()" style="margin-left: 20px;">
                    <i class="fas fa-print"></i> Print All Pages
                </button>
            `;

            // Insert pagination after controls
            const controls = document.querySelector('.controls');
            controls.parentNode.insertBefore(paginationDiv, controls.nextSibling);
        }

        function changePage(direction) {
            const newPage = currentPage + direction;
            if (newPage >= 1 && newPage <= totalPages) {
                showPage(newPage);

                // Update pagination buttons
                const pagination = document.querySelector('.pagination');
                if (pagination) {
                    pagination.innerHTML = `
                        <button onclick="changePage(-1)" ${newPage === 1 ? 'disabled' : ''}>
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                        <span>Page ${newPage} of ${totalPages}</span>
                        <button onclick="changePage(1)" ${newPage === totalPages ? 'disabled' : ''}>
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                        <button class="btn" onclick="printAllPages()" style="margin-left: 20px;">
                            <i class="fas fa-print"></i> Print All Pages
                        </button>
                    `;
                }
            }
        }

        function printAllPages() {
            // Make all pages visible for printing
            document.querySelectorAll('.report-container').forEach(container => {
                container.style.display = 'block';
            });

            // Hide selection container
            document.getElementById('selectionContainer').style.display = 'none';

            // Trigger print
            setTimeout(() => {
                window.print();

                // Restore view after printing
                setTimeout(() => {
                    document.querySelectorAll('.report-container').forEach(container => {
                        container.style.display = '';
                    });
                    document.getElementById('selectionContainer').style.display = 'block';

                    // Show current page
                    showPage(currentPage);
                }, 1000);
            }, 500);
        }

        // Initialize selected count
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Report page loaded');
            console.log('Total QR codes:', qrCodes.length);
            updateSelectedCount();
        });
    </script>
</body>
</html>