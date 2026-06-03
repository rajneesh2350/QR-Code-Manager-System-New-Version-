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

        /* Left Side: Title & Preview */
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

        /* Selection Controls */
        .selection-group { display: flex; align-items: center; gap: 10px; }
        .btn-sm-custom { padding: 6px 14px; font-size: 13px; font-weight: 600; border-radius: 6px; transition: 0.2s; }

        /* Search Styling */
        .search-container { position: relative; flex-grow: 1; max-width: 350px; }
        .search-container i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .search-input { width: 100%; padding: 8px 15px 8px 40px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; outline: none; transition: border 0.2s; }
        .search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

        .stats-badge { background: #eff6ff; color: #1d4ed8; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: 700; }

        /* Selection Grid */
        .selection-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 15px; }
        .select-card { background: white; border-radius: 10px; padding: 12px; border: 2px solid #f1f5f9; cursor: pointer; transition: 0.2s; position: relative; text-align: center; }
        .select-card:hover { border-color: #cbd5e1; transform: translateY(-2px); }
        .select-card.selected { border-color: #3b82f6; background: #f0f7ff; }
        .select-card input[type="checkbox"] { position: absolute; top: 10px; right: 10px; scale: 1.3; cursor: pointer; }
        .select-card img { width: 100%; height: 120px; object-fit: contain; margin-bottom: 8px; }
        .select-card .name { font-size: 11px; font-weight: 800; background: #334155; color: white; padding: 5px; border-radius: 4px; word-break: break-all; }

        #print-container { display: none; }
    }

    /* =========================================
       PRINT STYLES (Legal Portrait)
       ========================================= */
    @media print {
        html, body { margin: 0 !important; padding: 0 !important; }
        body * { visibility: hidden; }
        .no-print { display: none !important; }
        #print-container, #print-container * { visibility: visible !important; }
        #print-container { display: block !important; position: absolute; left: 0; top: 0; width: 100%; }
        @page { size: legal portrait; margin: 0; }
        .print-page { width: 21.59cm; height: 35.56cm; padding: 0.5cm 1cm; box-sizing: border-box; page-break-after: always; display: flex; flex-direction: column; background: white; }
        .header-image-print { width: 100%; height: auto; margin-bottom: 15px; display: block; }
        .print-grid { display: grid; gap: 0.2cm; grid-template-columns: repeat(3, 1fr); grid-template-rows: repeat(5, 6.4cm); }
        .has-header .print-grid { grid-template-rows: repeat(4, 6.4cm); }
        .print-cell { display: flex; flex-direction: column; align-items: center; }
        .qr-wrapper img { width: 170px; height: 170px; }
        .qr-label { text-align: center; font-size: 14px; font-weight: bold; color: white; background: black; padding: 5px 8px; border-radius: 4px; width: 85%; -webkit-print-color-adjust: exact; }
    }
</style>

<div class="report-toolbar no-print">
    <div class="t-row">
        <div class="toolbar-brand">
            <h2 class="toolbar-title"><i class="fas fa-file-invoice text-primary"></i> Report Manager</h2>
            <div class="header-preview-pill">
                <span class="preview-label">Active Header</span>
                <img src="https://igipess.du.ac.in/QR-Code/header-image.jpg" class="header-thumbnail" alt="Preview">
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-dark btn-sm-custom" onclick="printHeaderOnly()"><i class="fas fa-image"></i> Only Header</button>
            <button class="btn btn-primary btn-sm-custom shadow-sm" onclick="generateAndPrintPDF()"><i class="fas fa-print"></i> Print Selected QRs</button>
        </div>
    </div>

    <div class="t-row">
        <div class="selection-group">
            <button class="btn btn-light btn-sm-custom border" onclick="selectAll()">Select All</button>
            <button class="btn btn-light btn-sm-custom border" onclick="deselectAll()">Clear</button>

            <div style="width: 1px; height: 20px; background: #e2e8f0; margin: 0 10px;"></div>

            <label class="d-flex align-items-center gap-2 mb-0" style="cursor: pointer; font-size: 14px; font-weight: 600; color: #475569;">
                <input type="checkbox" id="includeHeader" checked style="width: 16px; height: 16px; accent-color: #2563eb;"> Include Header on Pages
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
    const headerImgUrl = "https://igipess.du.ac.in/QR-Code/header-image.jpg";

    function updateCount() {
        let count = 0;
        document.querySelectorAll('.qr-checkbox').forEach(cb => {
            if(cb.checked) { count++; cb.closest('.select-card').classList.add('selected'); }
            else { cb.closest('.select-card').classList.remove('selected'); }
        });
        document.getElementById('selectedCount').textContent = count;
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

    function printHeaderOnly() {
        const container = document.getElementById('print-container');
        container.innerHTML = `<div class="print-page"><img src="${headerImgUrl}" class="header-image-print"></div>`;
        setTimeout(() => window.print(), 300);
    }

    function generateAndPrintPDF() {
        const selected = Array.from(document.querySelectorAll('.qr-checkbox:checked')).map(cb => qrCodes[cb.value]);
        if (!selected.length) return alert('Select QR codes first');

        const useHeader = document.getElementById('includeHeader').checked;
        const container = document.getElementById('print-container');
        container.innerHTML = '';

        let idx = 0;
        while (idx < selected.length) {
            const pageDiv = document.createElement('div');
            pageDiv.className = 'print-page';
            let cap = useHeader ? 12 : 15;
            if (useHeader) {
                pageDiv.classList.add('has-header');
                pageDiv.innerHTML = `<img src="${headerImgUrl}" class="header-image-print">`;
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
        setTimeout(() => window.print(), 300);
    }
    document.addEventListener('DOMContentLoaded', updateCount);
</script>