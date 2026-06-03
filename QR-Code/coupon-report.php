<?php
require_once 'includes/config.php';

// --- PHP Function to Convert Number to Words (Indian Format) ---
function amountToWords($num) {
    $num = (int)$num;
    if ($num == 0) return 'Zero';

    $words = array(
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen',
        20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
    );

    $crore = floor($num / 10000000);
    $num -= $crore * 10000000;
    $lakh = floor($num / 100000);
    $num -= $lakh * 100000;
    $thousand = floor($num / 1000);
    $num -= $thousand * 1000;
    $hundred = floor($num / 100);
    $num -= $hundred * 100;
    $remainder = $num;

    $res = '';
    if ($crore > 0) { $res .= amountToWords($crore) . " Crore "; }
    if ($lakh > 0) { $res .= amountToWords($lakh) . " Lakh "; }
    if ($thousand > 0) { $res .= amountToWords($thousand) . " Thousand "; }
    if ($hundred > 0) { $res .= amountToWords($hundred) . " Hundred "; }
    if ($remainder > 0) {
        if ($remainder < 20) {
            $res .= $words[$remainder];
        } else {
            $tens = floor($remainder / 10) * 10;
            $ones = $remainder % 10;
            $res .= $words[$tens] . ($ones ? " " . $words[$ones] : "");
        }
    }
    return trim($res);
}

// Capture Filter Inputs for General Reports
$filterType = isset($_GET['type']) ? $_GET['type'] : '';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';

// Capture Filter Inputs for Invoice
$invStart = isset($_GET['inv_start']) ? $_GET['inv_start'] : date('Y-m-01');
$invEnd = isset($_GET['inv_end']) ? $_GET['inv_end'] : date('Y-m-t');

// Build Query Conditions for General Reports
$whereCoupon = "1=1";
$whereLog = "1=1";

if (!empty($filterType)) {
    $whereCoupon .= " AND coupon_type = '" . $conn->real_escape_string($filterType) . "'";
    $whereLog .= " AND coupon_code LIKE '%" . $conn->real_escape_string($filterType) . "'";
}
if (!empty($filterStatus)) {
    $whereLog .= " AND status = '" . $conn->real_escape_string($filterStatus) . "'";
}

// Fetch Stats
$statUsed = $conn->query("SELECT COUNT(*) as cnt FROM qr_code_coupon")->fetch_assoc()['cnt'];
$statLogs = $conn->query("SELECT COUNT(*) as cnt FROM qr_scan_logs_coupon")->fetch_assoc()['cnt'];
$statFailed = $conn->query("SELECT COUNT(*) as cnt FROM qr_scan_logs_coupon WHERE status = 'ALREADY_USED'")->fetch_assoc()['cnt'];

// Fetch Table Data for General Reports
$usedCoupons = $conn->query("SELECT * FROM qr_code_coupon WHERE $whereCoupon ORDER BY used_at DESC LIMIT 500");
$scanLogs = $conn->query("SELECT * FROM qr_scan_logs_coupon WHERE $whereLog ORDER BY scanned_at DESC LIMIT 500");

// Fetch Invoice Data (Date-Wise Clubbing)
$invQuery = "
    SELECT
        DATE(used_at) as scan_date,
        coupon_type,
        rate,
        COUNT(id) as total_coupons,
        (rate * COUNT(id)) as total_amount
    FROM qr_code_coupon
    WHERE DATE(used_at) BETWEEN '$invStart' AND '$invEnd'
    GROUP BY scan_date, coupon_type, rate
    ORDER BY scan_date ASC, coupon_type ASC
";
$invoiceData = $conn->query($invQuery);

// Fetch Summary Data (Overall Period Clubbing)
$summaryQuery = "
    SELECT
        coupon_type,
        rate,
        COUNT(id) as total_coupons,
        (rate * COUNT(id)) as total_amount
    FROM qr_code_coupon
    WHERE DATE(used_at) BETWEEN '$invStart' AND '$invEnd'
    GROUP BY coupon_type, rate
    ORDER BY rate DESC, coupon_type ASC
";
$summaryData = $conn->query($summaryQuery);
?>

<?php include 'header.php'; ?>

<style>
    .dashboard-header-report { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .dashboard-header-report h1 { margin: 0; color: #0f172a; font-size: 24px;}

    .stats-grid-report { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .stat-card-report { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-left: 5px solid #3b82f6; }
    .stat-card-report.success { border-left-color: #10b981; }
    .stat-card-report.danger { border-left-color: #ef4444; }
    .stat-card-report h3 { margin: 0 0 10px 0; font-size: 14px; text-transform: uppercase; color: #64748b; }
    .stat-card-report .value { font-size: 28px; font-weight: bold; color: #0f172a; }

    .filter-section { background: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; gap: 15px; align-items: center; flex-wrap: wrap; }
    .filter-section select, .filter-section input, .filter-section button { padding: 8px 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none;}
    .btn-filter { background: #3b82f6; color: white; border: none !important; cursor: pointer; font-weight: bold; transition: background 0.2s;}
    .btn-filter:hover { background: #2563eb; }
    .btn-clear { background: #e2e8f0; color: #475569; text-decoration: none; border: 1px solid #cbd5e1; padding: 8px 15px; border-radius: 6px; font-size: 14px; display: inline-block; transition: background 0.2s;}
    .btn-print-inv { background: #10b981; color: white; border: none !important; cursor: pointer; font-weight: bold; transition: background 0.2s; padding: 8px 15px; border-radius: 6px; font-size: 14px; display: inline-flex; align-items: center; gap: 5px;}

    .tabs { display: flex; gap: 10px; margin-bottom: 0; }
    .tab-btn { padding: 10px 20px; background: #e2e8f0; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: bold; color: #475569; transition: background 0.2s; }
    .tab-btn.active { background: white; color: #3b82f6; box-shadow: 0 -2px 5px rgba(0,0,0,0.02); border-top: 3px solid #3b82f6; }

    .tab-content { display: none; background: white; padding: 20px; border-radius: 0 8px 8px 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow-x: auto; }
    .tab-content.active { display: block; }
    .report-table { width: 100%; border-collapse: collapse; text-align: left; }
    .report-table th, .report-table td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
    .report-table th { background: #f8fafc; font-weight: 600; color: #475569; }

    .badge { padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; color: white; display: inline-block;}
    .badge.bg-success { background: #10b981; }
    .badge.bg-danger { background: #ef4444; }

    /* =========================================================
       INVOICE SPECIFIC STYLES
       ========================================================= */
    #invoice-print-area {
        background: white; padding: 20px; color: #000; font-family: 'Segoe UI', Arial, sans-serif;
    }
    .invoice-header {
        display: flex; align-items: center; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 20px;
    }
    .invoice-header img { width: 100px; height: auto; margin-right: 20px; }
    .invoice-title-block { flex-grow: 1; text-align: center; }
    .invoice-title-block h2 { margin: 0; font-size: 20px; font-weight: bold; text-transform: uppercase; color: #000; }
    .invoice-title-block p { margin: 5px 0 0 0; font-size: 14px; color: #333; }
    .invoice-title-block h3 { margin: 15px 0 0 0; font-size: 18px; font-weight: bold; text-decoration: underline; color: #000; }

    .invoice-meta { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; }

    .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    .invoice-table th, .invoice-table td { border: 1px solid #000; padding: 8px 12px; text-align: center; font-size: 14px; }
    .invoice-table th { background-color: #f1f5f9; font-weight: bold; color: #000; }
    .invoice-table tfoot th { text-align: right; font-size: 15px; padding-right: 15px; }
    .invoice-table tfoot td { font-weight: bold; font-size: 15px; background-color: #f8fafc; }

    .invoice-signatures { display: flex; justify-content: space-between; margin-top: 60px; padding: 0 20px; }
    .signature-line { text-align: center; width: 250px; border-top: 1px solid #000; padding-top: 5px; font-weight: bold; font-size: 14px; }

    /* Print Override */
    @media print {
        @page { size: A4 portrait; margin: 15mm; }
        body * { visibility: hidden; }
        .sidebar, .top-header, .tabs, .filter-section, .dashboard-header-report, .stats-grid-report, #usedCoupons, #scanLogs { display: none !important; }

        #canteenInvoice { display: block !important; visibility: visible; position: absolute; left: 0; top: 0; width: 100%; border: none; box-shadow: none; padding: 0; margin: 0; }
        #canteenInvoice * { visibility: visible; }
        .invoice-table th, .invoice-table td { border: 1px solid #000 !important; }
        .invoice-header { border-bottom: 2px solid #000 !important; }
    }
</style>

<div class="dashboard-header-report no-print">
    <h1>📊 Coupon Analytics Dashboard</h1>
    <button onclick="window.location.href='index.php'" style="padding: 10px 15px; background: #64748b; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">
        <i class="fas fa-arrow-left"></i> Back to Home
    </button>
</div>

<div class="stats-grid-report no-print">
    <div class="stat-card-report success">
        <h3>Total Unique Scans (Used)</h3>
        <div class="value"><?php echo $statUsed; ?></div>
    </div>
    <div class="stat-card-report">
        <h3>Total Scan Attempts</h3>
        <div class="value"><?php echo $statLogs; ?></div>
    </div>
    <div class="stat-card-report danger">
        <h3>Blocked/Duplicate Attempts</h3>
        <div class="value"><?php echo $statFailed; ?></div>
    </div>
</div>

<div class="tabs no-print">
    <button class="tab-btn active" onclick="switchTab(event, 'usedCoupons')">Verified Coupons</button>
    <button class="tab-btn" onclick="switchTab(event, 'scanLogs')">Raw Scan Logs</button>
    <button class="tab-btn" onclick="switchTab(event, 'canteenInvoice')" style="color: #059669;"><i class="fas fa-file-invoice-dollar"></i> Canteen Invoice</button>
</div>

<div id="usedCoupons" class="tab-content active no-print">
    <form class="filter-section" method="GET" action="coupon-report.php">
        <strong><i class="fas fa-filter"></i> Filters:</strong>
        <select name="type">
            <option value="">All Meal Types</option>
            <option value="LUNCH" <?php if($filterType == 'LUNCH') echo 'selected'; ?>>Lunch</option>
            <option value="TEA" <?php if($filterType == 'TEA') echo 'selected'; ?>>Tea</option>
            <option value="DINNER" <?php if($filterType == 'DINNER') echo 'selected'; ?>>Dinner</option>
        </select>
        <button type="submit" class="btn-filter">Apply Filters</button>
        <a href="coupon-report.php" class="btn-clear">Clear</a>
    </form>

    <table class="report-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Coupon Code</th>
                <th>Type</th>
                <th>Rate (₹)</th>
                <th>Time Used</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $usedCoupons->fetch_assoc()): ?>
            <tr>
                <td>#<?php echo $row['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($row['coupon_code']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['coupon_type']); ?></td>
                <td>₹<?php echo number_format((float)$row['rate'], 2); ?></td>
                <td><?php echo date('M d, Y h:i:s A', strtotime($row['used_at'])); ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if($usedCoupons->num_rows == 0) echo "<tr><td colspan='5' style='text-align:center;'>No records found.</td></tr>"; ?>
        </tbody>
    </table>
</div>

<div id="scanLogs" class="tab-content no-print">
    <table class="report-table">
        <thead>
            <tr>
                <th>Log ID</th>
                <th>Scanned Code</th>
                <th>Status</th>
                <th>Scan Time</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $scanLogs->fetch_assoc()): ?>
            <tr>
                <td>#<?php echo $row['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($row['coupon_code']); ?></strong></td>
                <td>
                    <?php if ($row['status'] == 'SUCCESSFULLY_USED'): ?>
                        <span class="badge bg-success">SUCCESS</span>
                    <?php else: ?>
                        <span class="badge bg-danger">BLOCKED</span>
                    <?php endif; ?>
                </td>
                <td><?php echo date('M d, Y h:i:s A', strtotime($row['scanned_at'])); ?></td>
                <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
            </tr>
            <?php endwhile; ?>
            <?php if($scanLogs->num_rows == 0) echo "<tr><td colspan='5' style='text-align:center;'>No logs found.</td></tr>"; ?>
        </tbody>
    </table>
</div>

<div id="canteenInvoice" class="tab-content">

    <div class="filter-section no-print" style="justify-content: space-between;">
        <form method="GET" action="coupon-report.php" style="display: flex; gap: 15px; align-items: center;">
            <strong><i class="fas fa-calendar-alt"></i> Select Billing Period:</strong>
            <input type="date" name="inv_start" value="<?php echo $invStart; ?>" required>
            <span>to</span>
            <input type="date" name="inv_end" value="<?php echo $invEnd; ?>" required>
            <button type="submit" class="btn-filter">Generate Bill</button>
        </form>
        <button class="btn-print-inv" onclick="window.print()"><i class="fas fa-print"></i> Print Official Invoice</button>
    </div>

    <div id="invoice-print-area">
        <div class="invoice-header">
            <img src="igipesslogo1.png" alt="IGIPESS Logo" onerror="this.style.display='none'">
            <div class="invoice-title-block">
                <h2>Indira Gandhi Institute of Physical Education and Sports Sciences</h2>
                <p>(University of Delhi), B-Block Vikaspuri, New Delhi - 110018<br>Govt. Of NCT of Delhi</p>
                <h3>CANTEEN BILL / INVOICE</h3>
            </div>
        </div>

        <div class="invoice-meta">
            <div>
                <strong>Billing Period:</strong> <?php echo date('d-M-Y', strtotime($invStart)); ?> &nbsp;to&nbsp; <?php echo date('d-M-Y', strtotime($invEnd)); ?>
            </div>
            <div>
                <strong>Date of Issue:</strong> <?php echo date('d-M-Y'); ?>
            </div>
        </div>

        <h4 style="margin-bottom: 5px; font-size: 14px; text-decoration: underline;">Date-wise Breakdown:</h4>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>S.No.</th>
                    <th>Date</th>
                    <th>Meal Type</th>
                    <th>Rate per Meal (₹)</th>
                    <th>Total Coupons Used</th>
                    <th>Total Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $grandTotal = 0;
                $counter = 1;
                if ($invoiceData->num_rows > 0):
                    while ($row = $invoiceData->fetch_assoc()):
                        $grandTotal += $row['total_amount'];
                ?>
                <tr>
                    <td><?php echo $counter++; ?></td>
                    <td><?php echo date('d-M-Y', strtotime($row['scan_date'])); ?></td>
                    <td><?php echo htmlspecialchars($row['coupon_type']); ?></td>
                    <td><?php echo number_format((float)$row['rate'], 2); ?></td>
                    <td><?php echo $row['total_coupons']; ?></td>
                    <td style="text-align: right; padding-right: 15px;"><?php echo number_format((float)$row['total_amount'], 2); ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="6" style="padding: 30px;">No scanned coupons found for this period.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h4 style="margin-bottom: 5px; font-size: 14px; text-decoration: underline;">Consolidated Summary (By Meal Type):</h4>
        <table class="invoice-table" style="width: 50%;">
            <thead>
                <tr>
                    <th>Meal Type</th>
                    <th>Rate (₹)</th>
                    <th>Total Quantity</th>
                    <th>Total Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($summaryData->num_rows > 0):
                    while ($sumRow = $summaryData->fetch_assoc()):
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($sumRow['coupon_type']); ?></strong></td>
                    <td><?php echo number_format((float)$sumRow['rate'], 2); ?></td>
                    <td><?php echo $sumRow['total_coupons']; ?></td>
                    <td style="text-align: right; padding-right: 15px;"><strong><?php echo number_format((float)$sumRow['total_amount'], 2); ?></strong></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4">No data available.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3">GRAND TOTAL AMOUNT:</th>
                    <td style="text-align: right; padding-right: 15px;">₹ <?php echo number_format((float)$grandTotal, 2); ?></td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-bottom: 40px; font-weight: bold; text-align: left;">
            Amount in words: <span style="font-weight: normal; font-style: italic;">
                Rupees <?php echo amountToWords($grandTotal); ?> Only.
            </span>
        </div>

        <div class="invoice-signatures">
            <div class="signature-line">
                Signature of Canteen Contractor<br>
                <span style="font-weight: normal; font-size: 12px;">(With Seal & Date)</span>
            </div>
            <div class="signature-line">
                Authorized Signatory<br>
                <span style="font-weight: normal; font-size: 12px;">IGIPESS Administration</span>
            </div>
        </div>
    </div>
</div>

<script>
    function switchTab(evt, tabName) {
        let i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].classList.remove("active");
        }
        tablinks = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].classList.remove("active");
        }
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
    }

    // Auto-open invoice tab if URL contains invoice date filters
    window.onload = function() {
        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.has('inv_start')) {
            document.querySelector('.tab-btn:nth-child(3)').click();
        }
    }
</script>

<?php include 'footer.php'; ?>