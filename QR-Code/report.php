<!--Need to change some logic in the report printing only with some addon
But don't change other logic and UI, navigation, etc.
1.  Need to select more headers. Selection of Header (id="includeHeader")
2. Selection among two option buttons, like a. https://igipess.du.ac.in/QR-Code/header-image.jpg and b. https://igipess.du.ac.in/QR-Code/header-image2.jpg as a header
3.  And also have a  browse .jpeg files for print as a header
4. Input some text in a text area (lines) as a part of the header (if the user adds some text, then it is to be printed immediately under the header image)
5. If the selection of QR Code is Less than 6, then the arrangement on the A4 Page is to be like
 (option buttons, show some graphical presentation on an A4 Page so the user can easily see/understand the following arrangements, by default, the Default option will be selected)
   a. Default
   b. Top Left, Top Right, Centred, Bottom Left and Bottom Right (for 5 Qr Prints)
   c.  Top Left, Top Right (for 2 Qr Prints)
   d.  Bottom Left, Bottom Right (for 2 Qr Prints)
   e.  Top Left, Bottom Right (for 2 Qr Prints)
   d.  Top Right, Bottom Left (for 2 Qr Prints)
   e.  Centered ((for 1 Qr Print)
   Perfect, just check the logic, after the selections of QR
We have 7  Specific Page Layouts
Add two more layout selections
f.  Top Left, Top Right and Centred (for 3 Qr Prints)
g. Top Left, Top Right, Bottom Left and Bottom Right (for 4 Qr Prints)
If 1 QR is selected, then only the 7th option will be available for selection; the rest are non-selectable.
If 2 QR is selected, then only 3rd,4th,5th and 6th will be selectable; the rest are non-selectable.
If 3 QR is selected, then only 3 printing layouts will be selected; the rest are non-selectable.
The above conditions are more reliable for printing accurately as per the logic
Perfect, but in case I selected 5 QR in the printing preview, it showed only 3 checked in every layout under Specific Page Layout (Dynamic Selection based on count), check the logic between them, "Specific Page Layout" vs generateAndPrintPDF()
-->
<?php
header('Content-Type: text/html; charset=utf-8');
include 'header.php';
?>
<?php
$query = "SELECT * FROM qr_codes ORDER BY created_at ASC";
$result = $conn->query($query);
$qrCodes = [];
while ($row = $result->fetch_assoc()) {
    $qrCodes[] = $row;
}

$qrDir = 'assets/qrcodes/';
if (!file_exists($qrDir)) mkdir($qrDir, 0755, true);

function ensureQRImage($qr) {
    global $qrDir, $conn;
    if (!empty($qr['qrimage']) && file_exists($qr['qrimage'])) return $qr['qrimage'];

    $encryptedData = Encryption::encryptUrl($qr['id']);
    $baseUrl = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';
    $qrUrl = $baseUrl . 'qr-page.php?data=' . urlencode($encryptedData);
    $filename = 'qr_' . $qr['id'] . '_' . time() . '.png';
    $filePath = $qrDir . $filename;

    $apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&ecc=H&margin=0&data=' . urlencode($qrUrl);
    if (function_exists('curl_init')) {
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => false]);
        $qrImage = curl_exec($ch); curl_close($ch);
        if ($qrImage && strlen($qrImage) > 500) file_put_contents($filePath, $qrImage);
    }

    $relativePath = 'assets/qrcodes/' . $filename;
    $conn->query("UPDATE qr_codes SET qrimage = '$relativePath' WHERE id = " . $qr['id']);
    return $relativePath;
}
?>

<meta charset="UTF-8">

<style>
    /* =========================================
       SCREEN STYLES (Professional Compact UI)
       ========================================= */
    @media screen {
        .report-toolbar {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 15px 25px;
            margin-bottom: 25px;
        }

        .t-row { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .t-row:not(:last-child) { border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 12px; }

        .toolbar-brand { display: flex; align-items: center; gap: 20px; }
        .toolbar-title { font-size: 18px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 10px; }

        .header-preview-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            padding: 4px 12px;
            border-radius: 50px;
            border: 1px solid #e2e8f0;
        }
        .header-thumbnail { height: 32px; width: auto; border-radius: 4px; border: 1px solid #cbd5e1; }
        .preview-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }

        .selection-group { display: flex; align-items: center; gap: 10px; }
        .btn-sm-custom { padding: 6px 14px; font-size: 13px; font-weight: 600; border-radius: 6px; transition: 0.2s; }

        .search-container { position: relative; flex-grow: 1; max-width: 350px; }
        .search-container i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .search-input { width: 100%; padding: 8px 15px 8px 40px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; outline: none; transition: border 0.2s; }
        .search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

        .stats-badge { background: #eff6ff; color: #1d4ed8; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 700; }

        /* Advanced Layout Options Styles */
        .advanced-print-options { margin-top: 15px; border-top: 1px dashed #cbd5e1; padding-top: 15px; }
        .layout-btn { border: 2px solid #e2e8f0; border-radius: 6px; padding: 8px 5px; cursor: pointer; text-align: center; width: 80px; transition: 0.2s; background: white; user-select: none; }
        .layout-btn:hover:not(.disabled) { border-color: #cbd5e1; background: #f8fafc; }
        .layout-btn.active { border-color: #3b82f6; background: #eff6ff; }

        .layout-btn.disabled { opacity: 0.35; pointer-events: none; filter: grayscale(100%); background: #f1f5f9; }

        .layout-btn span { font-size: 10px; display: block; margin-top: 6px; color: #475569; font-weight: 600; line-height: 1.1; }
        .lb-box { width: 45px; height: 55px; border: 1px solid #cbd5e1; margin: 0 auto; background: #f8fafc; position: relative; }
        .lb-box.pos-preview div { position: absolute; width: 12px; height: 12px; background: #94a3b8; border-radius: 2px; }
        .p-tl { top: 3px; left: 3px; }
        .p-tr { top: 3px; right: 3px; }
        .p-bl { bottom: 3px; left: 3px; }
        .p-br { bottom: 3px; right: 3px; }
        .p-c { top: 50%; left: 50%; transform: translate(-50%, -50%); }

        .selection-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
        .select-card { background: white; border-radius: 10px; padding: 12px; border: 2px solid #f1f5f9; cursor: pointer; transition: 0.2s; position: relative; text-align: center; }
        .select-card:hover { border-color: #cbd5e1; transform: translateY(-2px); }
        .select-card.selected { border-color: #3b82f6; background: #f0f7ff; }
        .select-card input[type="checkbox"] { position: absolute; top: 10px; right: 10px; scale: 1.3; cursor: pointer; }
        .select-card img { width: 100%; height: 120px; object-fit: contain; margin-bottom: 8px; }
        .select-card .name { font-size: 11px; font-weight: 800; background: #334155; color: white; padding: 5px; border-radius: 4px; word-break: break-all; }

        .multiplier-box {
            display: inline-flex; align-items: center; gap: 10px; background: #fffbeb;
            border: 1px solid #fde68a; border-radius: 6px; padding: 8px 15px; margin-bottom: 12px;
        }

        #print-container { display: none; }
    }

    /* =========================================
       PRINT STYLES (A4, Bulletproof Margins)
       ========================================= */
    @media print {
        html, body { margin: 0 !important; padding: 0 !important; background: white; }
        body * { visibility: hidden; }
        .no-print { display: none !important; }
        #print-container, #print-container * { visibility: visible !important; }
        #print-container { display: block !important; position: absolute; left: 0; top: 0; width: 100%; }

        @page { size: A4 portrait; margin: 0; }

        .print-page {
            width: 21cm;
            height: 29.7cm;
            padding: 1cm 1.5cm 2cm 1.5cm;
            box-sizing: border-box;
            page-break-after: always;
            display: block;
            background: white;
            position: relative;
        }

        .header-image-print {
            width: 100%;
            height: 4.5cm;
            object-fit: fill;
            margin-bottom: 15px;
            display: block;
        }

        /* DOTTED UNDERLINE & SHADOW FOR CUSTOM TEXT */
        .header-custom-text-print {
            width: 100%;
            text-align: center;
            font-weight: bold;
            margin-bottom: 20px;
            white-space: pre-wrap;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .header-custom-text-print span {
            border-bottom: 3px dotted #1e293b;
            padding-bottom: 5px;
            display: inline-block;
            line-height: 1.2;
        }

        .print-grid { display: grid; gap: 0.2cm; grid-template-columns: repeat(3, 1fr); grid-template-rows: repeat(5, 5.2cm); }
        .has-header-content .print-grid { grid-template-rows: repeat(4, 5.2cm); }

        .print-cell { display: flex; flex-direction: column; align-items: center; page-break-inside: avoid; }
        .qr-wrapper img { width: 160px; height: 160px; }

        .qr-label {
            text-align: center; font-size: 13px; font-weight: bold; color: white; background: black;
            padding: 5px 8px; border-radius: 4px; width: 90%; -webkit-print-color-adjust: exact; print-color-adjust: exact;
            margin-bottom: 5px;
        }

        .custom-layout-wrapper { position: relative; width: 100%; height: 20cm; display: block; margin-top: 0.5cm; }
        .print-cell-absolute { position: absolute; display: flex; flex-direction: column; align-items: center; width: 180px; page-break-inside: avoid; }

        .pos-tl { top: 0; left: 0; }
        .pos-tr { top: 0; right: 0; }
        .pos-center { top: 8cm; left: 50%; margin-left: -90px; }
        .pos-bl { top: 16cm; left: 0; }
        .pos-br { top: 16cm; right: 0; }
    }
</style>

<div class="report-toolbar no-print">
    <div class="t-row">
        <div class="toolbar-brand">
            <h2 class="toolbar-title"><i class="fas fa-file-invoice text-primary"></i> Report Manager</h2>
            <div class="header-preview-pill">
                <span class="preview-label">Active Header</span>
                <img src="https://igipess.du.ac.in/QR-Code/header-image.jpg" class="header-thumbnail" id="activeThumbnailPreview" alt="Preview">
            </div>
        </div>
        <div class="d-flex gap-2" style="display:flex; gap: 10px;">
            <button class="btn btn-outline-dark btn-sm-custom border" style="background:#fff;" onclick="printHeaderOnly()"><i class="fas fa-image"></i> Only Header</button>
            <button class="btn btn-primary btn-sm-custom shadow-sm" style="background:#3b82f6; color:#fff; border:none;" onclick="generateAndPrintPDF()"><i class="fas fa-print"></i> Print Selected QRs</button>
        </div>
    </div>

    <div class="t-row">
        <div class="selection-group">
            <button class="btn btn-light btn-sm-custom border" style="background:#fff;" onclick="selectAll()">Select All</button>
            <button class="btn btn-light btn-sm-custom border" style="background:#fff;" onclick="deselectAll()">Clear</button>

            <div style="width: 1px; height: 20px; background: #e2e8f0; margin: 0 10px;"></div>

            <label class="d-flex align-items-center gap-2 mb-0" style="display:flex; cursor: pointer; font-size: 14px; font-weight: 600; color: #475569;">
                <input type="checkbox" id="includeHeader" checked style="width: 16px; height: 16px; accent-color: #2563eb;"> Include Header Image on Pages
            </label>
        </div>

        <div class="search-container">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInputReport" class="search-input" placeholder="Search QR code name..." onkeyup="filterReportQRs()">
        </div>

        <div class="stats-badge">
            Selected: <span id="selectedCount">0</span> / <?php echo count($qrCodes); ?>
        </div>
    </div>

    <div class="advanced-print-options">

        <div id="headerOptionsPanel" style="display:flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
            <div style="font-size:14px; font-weight:600; color:#475569;"><i class="fas fa-heading"></i> Custom Header Configuration:</div>

            <div class="d-flex gap-3 align-items-center" style="display:flex; gap:20px; align-items:center;">
                <label style="cursor:pointer; font-size:14px;"><input type="radio" name="headerSrc" value="url1" checked> Default Header 1</label>
                <label style="cursor:pointer; font-size:14px;"><input type="radio" name="headerSrc" value="url2"> Default Header 2</label>
                <label style="cursor:pointer; font-size:14px;"><input type="radio" name="headerSrc" value="custom"> Browse .jpeg</label>

                <input type="file" id="customHeaderFile" accept="image/jpeg, image/jpg" style="display:none; font-size:13px;">
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-top:5px; max-width:600px;">
                <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <span style="font-size:13px; font-weight:700; color:#475569;">Sub-Header Text (Prints even if image is hidden)</span>
                    <div style="display:flex; gap:8px;">
                        <select id="historySelect" onchange="if(this.value) document.getElementById('headerCustomText').value = this.value;" style="padding:4px 8px; font-size:12px; border-radius:4px; border:1px solid #cbd5e1; outline:none; cursor:pointer;">
                            <option value="">-- Saved History --</option>
                        </select>
                        <select id="headerFontSize" style="padding:4px 8px; font-size:12px; border-radius:4px; border:1px solid #cbd5e1; outline:none; cursor:pointer; font-weight:bold;">
                            <option value="20px">20px</option>
                            <option value="24px" selected>24px</option>
                            <option value="28px">28px</option>
                            <option value="32px">32px</option>
                            <option value="36px">36px</option>
                        </select>
                    </div>
                </div>
                <textarea id="headerCustomText" rows="2" placeholder="Enter optional text here..." style="width:100%; box-sizing:border-box; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-family:inherit; resize:vertical; outline:none;"></textarea>
            </div>
        </div>

        <div id="layoutOptionsPanel" style="display:none; flex-direction: column; gap: 10px;">

            <div style="font-size:14px; font-weight:600; color:#475569;"><i class="fas fa-border-all"></i> Specific Page Layout (Dynamic Selection based on count):</div>

            <div id="singleQrMultiplierPanel" style="display:none;">
                <div class="multiplier-box">
                    <label style="font-size:13px; font-weight:600; color:#b45309; margin-bottom:0; cursor:pointer;">
                        <i class="fas fa-copy"></i> Print selected QR(s) multiple times:
                        <select id="singleQrMultiplier" onchange="updateCount()" style="margin-left:8px; padding:4px 8px; border-radius:4px; border:1px solid #fcd34d; outline:none; cursor:pointer;">
                            <option value="1">1 Time</option>
                            <option value="2">2 Times</option>
                            <option value="3">3 Times</option>
                            <option value="4">4 Times</option>
                            <option value="5">5 Times</option>
                        </select>
                    </label>
                </div>
            </div>

            <div style="display:flex; gap: 10px; flex-wrap: wrap;">
                <div class="layout-btn" data-layout="default" data-valid="1,2,3,4,5" onclick="selectLayout(this)">
                    <div class="lb-box" style="display:grid; grid-template-columns:1fr 1fr; gap:2px; padding:4px;"><div style="background:#94a3b8; height:18px;"></div><div style="background:#94a3b8; height:18px;"></div><div style="background:#94a3b8; height:18px;"></div><div style="background:#94a3b8; height:18px;"></div></div>
                    <span>Default Flow</span>
                </div>

                <div class="layout-btn" data-layout="layout-5-corners" data-valid="5" onclick="selectLayout(this)">
                    <div class="lb-box pos-preview"><div class="p-tl"></div><div class="p-tr"></div><div class="p-c"></div><div class="p-bl"></div><div class="p-br"></div></div>
                    <span>5 Corners & Center</span>
                </div>

                <div class="layout-btn" data-layout="layout-4-corners" data-valid="4" onclick="selectLayout(this)">
                    <div class="lb-box pos-preview"><div class="p-tl"></div><div class="p-tr"></div><div class="p-bl"></div><div class="p-br"></div></div>
                    <span>4 Corners</span>
                </div>

                <div class="layout-btn" data-layout="layout-3-tl-tr-c" data-valid="3" onclick="selectLayout(this)">
                    <div class="lb-box pos-preview"><div class="p-tl"></div><div class="p-tr"></div><div class="p-c"></div></div>
                    <span>3 TL, TR, C</span>
                </div>

                <div class="layout-btn" data-layout="layout-2-top" data-valid="2" onclick="selectLayout(this)">
                    <div class="lb-box pos-preview"><div class="p-tl"></div><div class="p-tr"></div></div>
                    <span>2 Top<br>L & R</span>
                </div>
                <div class="layout-btn" data-layout="layout-2-bottom" data-valid="2" onclick="selectLayout(this)">
                    <div class="lb-box pos-preview"><div class="p-bl"></div><div class="p-br"></div></div>
                    <span>2 Bottom<br>L & R</span>
                </div>
                <div class="layout-btn" data-layout="layout-2-diag1" data-valid="2" onclick="selectLayout(this)">
                    <div class="lb-box pos-preview"><div class="p-tl"></div><div class="p-br"></div></div>
                    <span>2 Diag<br>TL & BR</span>
                </div>
                <div class="layout-btn" data-layout="layout-2-diag2" data-valid="2" onclick="selectLayout(this)">
                    <div class="lb-box pos-preview"><div class="p-tr"></div><div class="p-bl"></div></div>
                    <span>2 Diag<br>TR & BL</span>
                </div>

                <div class="layout-btn" data-layout="layout-1-center" data-valid="1" onclick="selectLayout(this)">
                    <div class="lb-box pos-preview"><div class="p-c"></div></div>
                    <span>1 Center</span>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="selection-grid no-print" id="qrGrid">
    <?php foreach($qrCodes as $index => $qr):
        $img = ensureQRImage($qr); ?>
        <div class="select-card qr-card-item" onclick="toggleCard(this, <?php echo $index; ?>)">
            <input type="checkbox" id="cb_<?php echo $index; ?>" class="qr-checkbox" value="<?php echo $index; ?>" onclick="event.stopPropagation(); updateCount();">
            <img src="<?php echo htmlspecialchars($img); ?>">
            <div class="name qr-name-text"><?php echo strtoupper(htmlspecialchars($qr['qrname'])); ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div id="print-container"></div>

<?php include 'footer.php'; ?>

<script>
    const qrCodes = <?php echo json_encode($qrCodes); ?>;
    const defaultHeader1Url = "https://igipess.du.ac.in/QR-Code/header-image.jpg";
    const defaultHeader2Url = "https://igipess.du.ac.in/QR-Code/header-image2.jpg";
    let customHeaderBase64 = null;

    // --- History Management (Local Storage) ---
    function saveTextHistory(text) {
        if (!text) return;
        let history = JSON.parse(localStorage.getItem('qrHeaderTexts') || '[]');
        history = history.filter(item => item !== text); // Remove duplicates
        history.unshift(text); // Add to front
        if (history.length > 10) history.pop(); // Keep last 10
        localStorage.setItem('qrHeaderTexts', JSON.stringify(history));
        loadTextHistory();
    }

    function loadTextHistory() {
        let history = JSON.parse(localStorage.getItem('qrHeaderTexts') || '[]');
        const select = document.getElementById('historySelect');
        select.innerHTML = '<option value="">-- Saved History --</option>';
        history.forEach(text => {
            let opt = document.createElement('option');
            opt.value = text;
            opt.textContent = text.length > 40 ? text.substring(0, 40) + '...' : text;
            select.appendChild(opt);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadTextHistory();
        // Auto-load the most recent text on page load
        let history = JSON.parse(localStorage.getItem('qrHeaderTexts') || '[]');
        if (history.length > 0) {
            document.getElementById('headerCustomText').value = history[0];
        }
        updateCount();
    });

    // --- UI Interactions ---
    document.querySelectorAll('input[name="headerSrc"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const fileInput = document.getElementById('customHeaderFile');
            const preview = document.getElementById('activeThumbnailPreview');

            if (this.value === 'custom') {
                fileInput.style.display = 'block';
                if(customHeaderBase64) preview.src = customHeaderBase64;
            } else {
                fileInput.style.display = 'none';
                preview.src = this.value === 'url1' ? defaultHeader1Url : defaultHeader2Url;
            }
        });
    });

    document.getElementById('customHeaderFile').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                customHeaderBase64 = event.target.result;
                document.getElementById('activeThumbnailPreview').src = customHeaderBase64;
            };
            reader.readAsDataURL(file);
        }
    });

    function selectLayout(element) {
        if (element.classList.contains('disabled')) return;
        document.querySelectorAll('.layout-btn').forEach(btn => btn.classList.remove('active'));
        element.classList.add('active');
    }

    function updateCount() {
        let actualCount = 0;
        document.querySelectorAll('.qr-checkbox').forEach(cb => {
            if(cb.checked) { actualCount++; cb.closest('.select-card').classList.add('selected'); }
            else { cb.closest('.select-card').classList.remove('selected'); }
        });
        document.getElementById('selectedCount').textContent = actualCount;

        const layoutPanel = document.getElementById('layoutOptionsPanel');
        const multiplierPanel = document.getElementById('singleQrMultiplierPanel');
        const multiplierSelect = document.getElementById('singleQrMultiplier');

        let effectiveCount = actualCount;

        // MULTIPLIER NOW WORKS FOR 1 OR 2 QRs
        if (actualCount === 1 || actualCount === 2) {
            multiplierPanel.style.display = 'block';
            effectiveCount = actualCount * parseInt(multiplierSelect.value);
        } else {
            multiplierPanel.style.display = 'none';
            multiplierSelect.value = "1";
        }

        if (effectiveCount > 0 && effectiveCount < 6) {
            layoutPanel.style.display = 'flex';

            let firstValidLayout = null;

            document.querySelectorAll('.layout-btn').forEach(btn => {
                const validCounts = btn.dataset.valid.split(',');
                if (validCounts.includes(effectiveCount.toString())) {
                    btn.classList.remove('disabled');
                    if (!firstValidLayout && btn.dataset.layout !== 'default') {
                        firstValidLayout = btn;
                    }
                } else {
                    btn.classList.add('disabled');
                }
            });

            const currentActive = document.querySelector('.layout-btn.active');
            if (!currentActive || currentActive.classList.contains('disabled')) {
                document.querySelectorAll('.layout-btn').forEach(b => b.classList.remove('active'));
                if (firstValidLayout) {
                    firstValidLayout.classList.add('active');
                } else {
                    document.querySelector('.layout-btn[data-layout="default"]').classList.add('active');
                }
            }
        } else {
            layoutPanel.style.display = 'none';
        }
    }

    function toggleCard(card, idx) { document.getElementById('cb_'+idx).click(); }
    function selectAll() { document.querySelectorAll('.qr-checkbox').forEach(cb => cb.checked = true); updateCount(); }
    function deselectAll() { document.querySelectorAll('.qr-checkbox').forEach(cb => cb.checked = false); updateCount(); }

    function filterReportQRs() {
        const input = document.getElementById('searchInputReport').value.toLowerCase();
        document.querySelectorAll('.qr-card-item').forEach(card => {
            const name = card.querySelector('.qr-name-text').textContent.toLowerCase();
            card.style.display = name.includes(input) ? 'block' : 'none';
        });
    }

    // --- Core Printing Logic ---
    function getActiveHeaderHTML() {
        // Image Header Toggle
        const useHeaderImg = document.getElementById('includeHeader').checked;
        let html = '';

        if (useHeaderImg) {
            let activeHeaderImg = defaultHeader1Url;
            const srcType = document.querySelector('input[name="headerSrc"]:checked').value;
            if (srcType === 'url2') activeHeaderImg = defaultHeader2Url;
            else if (srcType === 'custom') {
                if (!customHeaderBase64) return null;
                activeHeaderImg = customHeaderBase64;
            }
            html += `<img src="${activeHeaderImg}" class="header-image-print">`;
        }

        // Text Header Toggle (Independent of Image Toggle)
        const headerText = document.getElementById('headerCustomText').value.trim();
        if (headerText) {
            saveTextHistory(headerText); // Save to history
            const fontSize = document.getElementById('headerFontSize').value;
            html += `<div class="header-custom-text-print"><span style="font-size: ${fontSize};">${headerText}</span></div>`;
        }

        return html;
    }

    function printHeaderOnly() {
        const headerHTML = getActiveHeaderHTML();
        if (headerHTML === null) return alert('Please browse and select a custom JPEG image first.');
        if (headerHTML === '') return alert('You must either enable the Header Image or enter Custom Text to print a header.');

        const container = document.getElementById('print-container');
        container.innerHTML = `<div class="print-page has-header-content">${headerHTML}</div>`;
        setTimeout(() => window.print(), 300);
    }

    function generateAndPrintPDF() {
        let selected = Array.from(document.querySelectorAll('.qr-checkbox:checked')).map(cb => qrCodes[cb.value]);
        if (!selected.length) return alert('Select QR codes first');

        // Multiplier Expansion Logic
        if (selected.length === 1 || selected.length === 2) {
            const copies = parseInt(document.getElementById('singleQrMultiplier').value);
            if (copies > 1) {
                let expandedArray = [];
                for (let i = 0; i < copies; i++) {
                    expandedArray.push(...selected);
                }
                selected = expandedArray;
            }
        }

        const useHeaderImg = document.getElementById('includeHeader').checked;
        const headerHTML = getActiveHeaderHTML();
        if (useHeaderImg && headerHTML === null) return alert('Please browse and select a custom JPEG image first.');

        const hasHeaderContent = (headerHTML !== '');

        const container = document.getElementById('print-container');
        container.innerHTML = '';

        let layout = 'default';
        if (selected.length > 0 && selected.length < 6) {
            const activeBtn = document.querySelector('.layout-btn.active');
            if (activeBtn) layout = activeBtn.dataset.layout;
        }

        if (layout === 'default') {
            let idx = 0;
            while (idx < selected.length) {
                const pageDiv = document.createElement('div');
                pageDiv.className = 'print-page';
                let cap = hasHeaderContent ? 12 : 15;
                if (hasHeaderContent) {
                    pageDiv.classList.add('has-header-content');
                    pageDiv.innerHTML = headerHTML;
                }

                const grid = document.createElement('div');
                grid.className = 'print-grid';
                const chunk = selected.slice(idx, idx + cap);
                for (let i = 0; i < cap; i++) {
                    const cell = document.createElement('div');
                    cell.className = 'print-cell';
                    if (i < chunk.length) {
                        cell.innerHTML = `<div class="qr-wrapper"><img src="${chunk[i].qrimage}"></div><div class="qr-label">${chunk[i].qrname.toUpperCase()}</div>`;
                    }
                    grid.appendChild(cell);
                }
                pageDiv.appendChild(grid);
                container.appendChild(pageDiv);
                idx += cap;
            }
        } else {
            const pageDiv = document.createElement('div');
            pageDiv.className = 'print-page';
            if (hasHeaderContent) {
                pageDiv.classList.add('has-header-content');
                pageDiv.innerHTML = headerHTML;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'custom-layout-wrapper';

            let positions = [];
            if (layout === 'layout-5-corners') positions = ['pos-tl', 'pos-tr', 'pos-center', 'pos-bl', 'pos-br'];
            else if (layout === 'layout-4-corners') positions = ['pos-tl', 'pos-tr', 'pos-bl', 'pos-br'];
            else if (layout === 'layout-3-tl-tr-c') positions = ['pos-tl', 'pos-tr', 'pos-center'];
            else if (layout === 'layout-2-top') positions = ['pos-tl', 'pos-tr'];
            else if (layout === 'layout-2-bottom') positions = ['pos-bl', 'pos-br'];
            else if (layout === 'layout-2-diag1') positions = ['pos-tl', 'pos-br'];
            else if (layout === 'layout-2-diag2') positions = ['pos-tr', 'pos-bl'];
            else if (layout === 'layout-1-center') positions = ['pos-center'];

            selected.forEach((item, i) => {
                const posClass = positions[i] || 'pos-center';
                const cell = document.createElement('div');
                cell.className = `print-cell-absolute ${posClass}`;
                cell.innerHTML = `
                    <div class="qr-wrapper"><img src="${item.qrimage}"></div>
                    <div class="qr-label">
                        ${item.qrname.toUpperCase()}
                    </div>
                `;
                wrapper.appendChild(cell);
            });

            pageDiv.appendChild(wrapper);
            container.appendChild(pageDiv);
        }

        setTimeout(() => window.print(), 300);
    }
</script>