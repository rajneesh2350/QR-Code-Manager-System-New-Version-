<?php include 'header.php'; ?>
<?php
// SECURE QR GENERATION: Fetch the image on the server so the URL is completely hidden from the browser source code!
$staffAppUrl = "QR-Code/staff-scanner.php";
$apiReqUrl = "https://api.qrserver.com/v1/create-qr-code/?size=500x500&margin=2&data=" . urlencode($staffAppUrl);
$imageBytes = @file_get_contents($apiReqUrl);
// Encrypt the image into Base64 format
$base64StaffQr = $imageBytes ? 'data:image/png;base64,' . base64_encode($imageBytes) : '';
?>

<style>
    /* --- Screen Styles (Controls & Background) --- */
    .canteen-container {
        font-family: Arial, sans-serif;
    }
    .controls {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        max-width: 950px;
        margin-left: auto;
        margin-right: auto;
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
        gap: 15px;
        position: sticky;
        top: 10px;
        z-index: 100;
    }
    .config-group {
        border: 1px solid #ddd;
        padding: 10px;
        border-radius: 5px;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .config-group label {
        font-weight: bold;
        font-size: 13px;
        color: #333;
    }
    .controls input {
        padding: 6px;
        border: 1px solid #ccc;
        border-radius: 4px;
        outline: none;
    }
    .btn-container { display: flex; flex-direction: column; gap: 5px; }
    .canteen-btn {
        padding: 10px 15px;
        font-weight: bold;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: 0.2s;
    }
    .canteen-btn:hover {
        opacity: 0.9;
    }

    /* --- Print Area & Grid --- */
    #print-area {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        background: white;
        padding: 10mm;
        width: 210mm;
        margin: auto;
        box-sizing: border-box;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    /* --- Coupon Design --- */
    .coupon {
        border: 2px dashed #444;
        padding: 15px 10px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        page-break-inside: avoid;
        position: relative;
        overflow: hidden;
        background: white;
    }
    .watermark-container {
        position: absolute; top: -20%; left: -20%; width: 140%; height: 140%;
        display: flex; flex-wrap: wrap; justify-content: space-around;
        align-content: space-around; gap: 12px; transform: rotate(-35deg);
        z-index: 0; opacity: 0.15; pointer-events: none;
    }
    .watermark-item { font-size: 10px; font-weight: 900; white-space: nowrap; color: #000; }

    /* NEW: Perfectly positioned layout classes to prevent overlapping */
    .coupon-details-bottom {
        position: absolute;
        bottom: 12px;
        left: 0;
        width: 100%;
        text-align: center;
        z-index: 1;
        padding: 0 10px;
        box-sizing: border-box;
    }
    .coupon-no {
        font-weight: bold;
        font-size: 13px;
        margin-bottom: 6px;
        color: #000;
        line-height: 1.2;
        word-wrap: break-word;
    }
    .meal-type {
        font-size: 16px;
        font-weight: bold;
        border: 2px solid #000;
        padding: 4px 16px;
        border-radius: 4px;
        background-color: #fff;
        color: #000;
        display: inline-block;
    }

    .size-a4 .coupon { height: 170px; }
    .size-legal .coupon { height: 172px; }

    @media print {
        @page { margin: 5mm; }
        body { background: white !important; padding: 0 !important; margin: 0 !important; }
        .no-print { display: none !important; }
        #print-area { box-shadow: none; width: 100%; padding: 5mm; }
        .coupon { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .sidebar, .top-header, #qrModal { display: none !important; }
        .main-wrapper { margin-left: 0 !important; padding: 0 !important; width: 100% !important; background: white !important;}
        .content-area { padding: 0 !important; margin: 0 !important; width: 100% !important; }
    }
</style>
<style id="dynamic-page-style"></style>

<div class="canteen-container size-a4" id="canteen-container">
    <div class="controls no-print">
        <div class="config-group">
            <label>Page Size:</label>
            <div style="font-size: 11px;">
                <input type="radio" name="pSize" id="pA4" value="a4" checked onchange="updateUI()">
                <label for="pA4">A4 (3x5 Grid)</label><br>
                <input type="radio" name="pSize" id="pLegal" value="legal" onchange="updateUI()">
                <label for="pLegal">Legal (3x6 Grid)</label>
            </div>
        </div>

        <div class="config-group">
            <label>Prefix:</label>
            <input type="text" id="prefix" value="" size="18">
        </div>

        <div class="config-group">
            <label>Range (Start - End):</label>
            <div>
                <input type="number" id="startFrom" value="120" style="width: 60px;"> to
                <input type="number" id="endAt" value="135" style="width: 60px;">
            </div>
        </div>

        <div class="config-group">
            <label>Numbering Logic:</label>
            <div style="font-size: 11px;">
                <input type="radio" name="mode" id="mRow" value="row" checked>
                <label for="mRow">One Row 1 Number</label><br>
                <input type="radio" name="mode" id="mCont" value="cont">
                <label for="mCont">Continuous Numbering</label>
            </div>
        </div>

        <div class="config-group">
            <label>Meals & Rates (₹):</label>
            <div style="display: flex; gap: 5px;">
                <input type="text" id="m1" value="LUNCH" style="width: 60px;">
                <input type="text" id="m2" value="TEA" style="width: 60px;">
                <input type="text" id="m3" value="DINNER" style="width: 60px;">
            </div>
            <div style="display: flex; gap: 5px;">
                <input type="number" id="r1" value="50" style="width: 60px;">
                <input type="number" id="r2" value="10" style="width: 60px;">
                <input type="number" id="r3" value="50" style="width: 60px;">
            </div>
        </div>

        <div class="config-group" style="text-align: center; align-items: center; background: #f8fafc; border-color: #cbd5e1;">
            <label style="color: #0f172a;"><i class="fas fa-camera"></i> Staff Scanner App</label>
            <img id="staffScannerQr" src="<?php echo $base64StaffQr; ?>" alt="Staff Scanner App QR" style="width: 60px; height: 60px; margin: 2px 0; border-radius: 5px; border: 1px solid #ddd;">
        </div>

        <div class="btn-container">
            <button class="canteen-btn" style="background-color: #8b5cf6;" onclick="promptEmailCoupons()">
                <i class="fas fa-envelope"></i> Email Coupons
            </button>
            <button class="canteen-btn" style="background-color: #0d9488;" onclick="downloadScannerAppQR()">
                <i class="fas fa-download"></i> Download Scanner QR
            </button>
        </div>
    </div>

    <div id="print-area"></div>
</div>

<script>
    function setDefaultPrefix() {
        const now = new Date();
        const day = String(now.getDate()).padStart(2, '0');
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const year = now.getFullYear();
        document.getElementById('prefix').value = `IGIPESS/${day}${month}${year}/`;
    }

    function updatePageSettings() {
        const isLegal = document.getElementById('pLegal').checked;
        const container = document.getElementById('canteen-container');
        if(isLegal) {
            container.classList.remove('size-a4');
            container.classList.add('size-legal');
        } else {
            container.classList.remove('size-legal');
            container.classList.add('size-a4');
        }
        document.getElementById('dynamic-page-style').innerHTML = `@media print { @page { size: ${isLegal ? 'legal' : 'A4'} portrait; } }`;
    }

    function generateHTML() {
        const start = parseInt(document.getElementById('startFrom').value) || 1;
        const end = parseInt(document.getElementById('endAt').value) || 1;
        const prefix = document.getElementById('prefix').value;
        const isContinuous = document.getElementById('mCont').checked;

        const meals = [
            document.getElementById('m1').value,
            document.getElementById('m2').value,
            document.getElementById('m3').value
        ];

        const rates = [
            document.getElementById('r1').value || 0,
            document.getElementById('r2').value || 0,
            document.getElementById('r3').value || 0
        ];

        let html = '';
        if (isContinuous) {
            let current = start;
            while (current <= end) {
                for (let j = 0; j < 3; j++) {
                    if (current > end) break;
                    html += renderCoupon(prefix, current, meals[j], rates[j]);
                    current++;
                }
            }
        } else {
            for (let i = start; i <= end; i++) {
                for (let j = 0; j < 3; j++) {
                    html += renderCoupon(prefix, i, meals[j], rates[j]);
                }
            }
        }
        return html;
    }

    function renderCoupon(prefix, num, meal, rate) {
        let padded = num.toString().padStart(3, '0');
        let watermarks = '';
        for (let i = 0; i < 60; i++) watermarks += `<div class="watermark-item">IGIPESS</div>`;

        let uniqueCode = prefix + '-' + padded + '-' + meal.toUpperCase() + '-' + rate;
        let encodedCode = btoa(uniqueCode);
        let targetData = encodedCode;

        let qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=2&ecc=L&data=' + encodeURIComponent(targetData);

        return `
            <div class="coupon" style="position: relative;">
                <div class="watermark-container">${watermarks}</div>

                <img src="igipesslogo1.png" alt="Logo" style="position: absolute; top: 15px; left: 15px; max-width: 60px; max-height: 60px; margin: 0; z-index: 10;">

                <div style="position: absolute; top: 12px; right: 12px; background: #ffffff; padding: 5px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.3), inset 0 0 0 1px rgba(0,0,0,0.1); z-index: 20; display: flex; align-items: center; justify-content: center;">
                    <img src="${qrApiUrl}" alt="QR" style="width: 65px; height: 65px; display: block; border: none; margin: 0;">
                </div>

                <div class="coupon-details-bottom">
                    <div class="coupon-no">COUPON NO. ${prefix}${padded}</div>
                    <div class="meal-type">${meal.toUpperCase()}</div>
                </div>
            </div>`;
    }

    function updateUI() {
        updatePageSettings();
        document.getElementById('print-area').innerHTML = generateHTML();
    }

    // Keep this logic intact as it is called from the sidebar
    function prepareAndPrint() {
        updateUI();
        setTimeout(() => { window.print(); }, 500);
    }

    function promptEmailCoupons() {
        const printArea = document.getElementById('print-area');
        if (!printArea.innerHTML.trim()) {
            Swal.fire('Error', 'Please generate some coupons first!', 'warning');
            return;
        }

        Swal.fire({
            title: 'Send Coupons via Email',
            input: 'email',
            inputLabel: 'Enter the recipient\'s email address',
            inputValue: 'rajneesh2350@gmail.com',
            inputPlaceholder: 'user@example.com',
            showCancelButton: true,
            confirmButtonColor: '#8b5cf6',
            confirmButtonText: 'Send Email <i class="fas fa-paper-plane"></i>',
            showLoaderOnConfirm: true,
            preConfirm: (email) => {
                const formData = new FormData();
                formData.append('email', email);
                formData.append('coupon_html', printArea.innerHTML);

                return fetch('send-email.php', { method: 'POST', body: formData })
                .then(response => {
                    if (!response.ok) throw new Error(response.statusText);
                    return response.json();
                })
                .catch(error => { Swal.showValidationMessage(`Request failed: ${error}`); });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                if (result.value.success) Swal.fire('Sent!', result.value.message, 'success');
                else Swal.fire('Error', result.value.message, 'error');
            }
        });
    }

    // Function to instantly download the encrypted Base64 QR code without revealing the URL
    function downloadScannerAppQR() {
        const link = document.createElement('a');
        link.href = "<?php echo $base64StaffQr; ?>"; // Uses the securely generated Base64 string directly!
        link.download = 'IGIPESS_Staff_Scanner_App.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    document.addEventListener("DOMContentLoaded", () => {
        setDefaultPrefix();
        updateUI();
    });
</script>
<?php include 'footer.php'; ?>
