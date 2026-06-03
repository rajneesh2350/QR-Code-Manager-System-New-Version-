<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>IGIPESS Canteen Pro</title>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --primary: #0ea5e9; --bg: #020617; --card: #1e293b; --success: #22c55e; }
        body {
            margin: 0; font-family: -apple-system, sans-serif;
            background: var(--bg); color: white; height: 100vh; display: flex; flex-direction: column; overflow: hidden;
        }

        .header { padding: 6px; background: #0f172a; border-bottom: 1px solid #334155; text-align: center; flex-shrink: 0; }
        .header h1 { margin: 0; font-size: 0.85rem; color: var(--primary); letter-spacing: 1px; }

        /* Dimmed Scanner Section with Cutout */
        .scanner-wrapper {
            position: relative; width: 100%; height: 200px; flex-shrink: 0; background: #000; overflow: hidden;
        }
        #reader { width: 100% !important; height: 100% !important; border: none !important; }
        #reader video { object-fit: cover !important; height: 200px !important; }

        /* The [ ] Frame & Dark Overlay */
        .overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 10; pointer-events: none;
        }
        .cutout {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 150px; height: 150px;
            background: transparent;
            box-shadow: 0 0 0 1000px rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
        }
        .corner { position: absolute; width: 15px; height: 15px; border: 3px solid var(--primary); }
        .tl { top: -2px; left: -2px; border-right: 0; border-bottom: 0; border-top-left-radius: 6px; }
        .tr { top: -2px; right: -2px; border-left: 0; border-bottom: 0; border-top-right-radius: 6px; }
        .bl { bottom: -2px; left: -2px; border-right: 0; border-top: 0; border-bottom-left-radius: 6px; }
        .br { bottom: -2px; right: -2px; border-left: 0; border-top: 0; border-bottom-right-radius: 6px; }

        /* Table Section */
        .history-container { flex: 1; overflow-y: auto; background: var(--bg); padding: 5px 10px; }
        .history-container h2 { font-size: 0.65rem; color: #94a3b8; margin: 5px 0; text-transform: uppercase; }

        table { width: 100%; border-collapse: collapse; font-size: 0.75rem; }
        th { text-align: left; padding: 6px; color: #64748b; border-bottom: 1px solid #334155; position: sticky; top: 0; background: var(--bg); }
        td { padding: 8px 6px; border-bottom: 1px solid #1e293b; }

        .badge-ok { color: var(--success); font-weight: bold; }
        .badge-fail { color: #f43f5e; font-weight: bold; }
        .rate-text { font-family: monospace; color: #e2e8f0; }

        .toolbar { display: flex; gap: 5px; padding: 5px 10px; background: #0f172a; flex-shrink: 0; }
        .btn-sm { flex: 1; padding: 6px; border-radius: 4px; border: 1px solid #334155; background: #1e293b; color: white; font-size: 0.65rem; font-weight: 600; }
    </style>
</head>
<body>

<div class="header"><h1>CANTEEN VALIDATOR</h1></div>

<div class="scanner-wrapper">
    <div id="reader"></div>
    <div class="overlay">
        <div class="cutout">
            <div class="corner tl"></div><div class="corner tr"></div>
            <div class="corner bl"></div><div class="corner br"></div>
        </div>
    </div>
</div>

<div class="toolbar">
    <button class="btn-sm" onclick="toggleTorch()">🔦 Flashlight</button>
    <button class="btn-sm" onclick="location.reload()">🔄 Refresh Cam</button>
</div>

<div class="history-container">
    <h2>Recent Orders</h2>
    <table>
        <thead>
            <tr><th>Time</th><th>Type</th><th>Rate</th><th>Status</th></tr>
        </thead>
        <tbody id="history-body">
            </tbody>
    </table>
</div>

<script>
    let html5QrCode;
    let isProcessing = false;
    let isTorchOn = false;

    async function startScanner() {
        html5QrCode = new Html5Qrcode("reader");
        try {
            await html5QrCode.start(
                { facingMode: "environment" },
                { fps: 25, qrbox: { width: 150, height: 150 } },
                onScanSuccess
            );
        } catch (err) { console.error("Camera fail"); }
    }

    async function onScanSuccess(decodedText) {
        if (isProcessing) return;
        isProcessing = true;
        if (navigator.vibrate) navigator.vibrate(40);

        const formData = new FormData();
        formData.append('code', decodedText);

        try {
            const response = await fetch('process-scan.php', { method: 'POST', body: formData });
            const data = await response.json();

            // Extract Type and Rate from response, or use defaults
            const couponType = data.coupon_type || "Unknown";
            const couponRate = data.rate || "0.00";

            updateTable(couponType, couponRate, data.success);

            await Swal.fire({
                icon: data.success ? 'success' : 'error',
                title: data.success ? 'VALID' : 'INVALID',
                text: data.success ? `${couponType} - ₹${couponRate}` : data.message,
                toast: true, position: 'top', showConfirmButton: false, timer: 1200,
                width: '240px', background: '#1e293b', color: '#fff'
            });
        } catch (err) {
            updateTable("Error", "---", false);
        } finally {
            isProcessing = false;
        }
    }

    function updateTable(type, rate, success) {
        const tbody = document.getElementById('history-body');
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });

        const row = `<tr>
            <td style="color:#64748b">${time}</td>
            <td style="font-weight:600">${type}</td>
            <td class="rate-text">₹${rate}</td>
            <td class="${success ? 'badge-ok' : 'badge-fail'}">${success ? '✔' : '✘'}</td>
        </tr>`;

        tbody.insertAdjacentHTML('afterbegin', row);
        if (tbody.rows.length > 8) tbody.deleteRow(8);
    }

    function toggleTorch() {
        isTorchOn = !isTorchOn;
        html5QrCode.applyVideoConstraints({ advanced: [{ torch: isTorchOn }] }).catch(() => {});
    }

    document.addEventListener('DOMContentLoaded', startScanner);
</script>
</body>
</html>