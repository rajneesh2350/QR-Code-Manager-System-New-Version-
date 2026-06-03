<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Coupon Generator - Fixed UI</title>
    <style>
        /* --- Screen Styles (Controls & Background) --- */
        body {
            font-family: Arial, sans-serif;
            background-color: #e9ecef;
            margin: 0;
            padding: 20px;
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
        }

        .btn-container { display: flex; flex-direction: column; gap: 5px; }

        .btn {
            padding: 10px 15px;
            font-weight: bold;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: white;
        }

        .btn-preview { background-color: #6c757d; }
        .btn-print { background-color: #007bff; }

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

        /* Tiled Watermark Logic */
        .watermark-container {
            position: absolute; top: -20%; left: -20%; width: 140%; height: 140%;
            display: flex; flex-wrap: wrap; justify-content: space-around;
            align-content: space-around; gap: 12px; transform: rotate(-35deg);
            z-index: 0; opacity: 0.15; pointer-events: none;
        }
        .watermark-item { font-size: 10px; font-weight: 900; white-space: nowrap; }

        .coupon img {
            max-height: 65px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .coupon-no {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 15px;
            color: #333;
            position: relative;
            z-index: 1;
        }

        .meal-type {
            font-size: 18px;
            font-weight: bold;
            border: 2px solid #000;
            padding: 6px 16px;
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.85);
            position: relative;
            z-index: 1;
        }

        /* Fixed Heights per Page Size */
        .size-a4 .coupon { height: 170px; }
        .size-legal .coupon { height: 172px; }

        /* --- Print-Specific Styles --- */
        @media print {
            @page { margin: 5mm; }
            body { background: white; padding: 0; counter-reset: page; }
            .no-print { display: none !important; }
            #print-area { box-shadow: none; width: 100%; padding: 5mm; }
            .coupon { -webkit-print-color-adjust: exact; print-color-adjust: exact; }

            /* Page Numbering */

        }
    </style>
    <style id="dynamic-page-style"></style>
</head>
<body class="size-a4">

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
            <label>Meals (Col 1, 2, 3):</label>
            <div>
                <input type="text" id="m1" value="LUNCH" style="width: 60px;">
                <input type="text" id="m2" value="LUNCH" style="width: 60px;">
                <input type="text" id="m3" value="LUNCH" style="width: 60px;">
            </div>
        </div>

        <div class="btn-container">
            <button class="btn btn-preview" onclick="updateUI()">Update Preview</button>
            <button class="btn btn-print" onclick="prepareAndPrint()">Generate & Print</button>
        </div>
    </div>

    <div id="print-area"></div>

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
            document.body.className = isLegal ? 'size-legal' : 'size-a4';
            document.getElementById('dynamic-page-style').innerHTML =
                `@media print { @page { size: ${isLegal ? 'legal' : 'A4'} portrait; } }`;
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

            let html = '';
            if (isContinuous) {
                let current = start;
                while (current <= end) {
                    for (let j = 0; j < 3; j++) {
                        if (current > end) break;
                        html += renderCoupon(prefix, current, meals[j]);
                        current++;
                    }
                }
            } else {
                for (let i = start; i <= end; i++) {
                    for (let j = 0; j < 3; j++) {
                        html += renderCoupon(prefix, i, meals[j]);
                    }
                }
            }
            return html;
        }

        function renderCoupon(prefix, num, meal) {
            let padded = num.toString().padStart(3, '0');
            let watermarks = '';
            for (let i = 0; i < 60; i++) watermarks += `<div class="watermark-item">IGIPESS</div>`;

            return `
                <div class="coupon">
                    <div class="watermark-container">${watermarks}</div>
                    <img src="igipesslogo1.png" alt="Logo">
                    <div class="coupon-no">COUPON NO. ${prefix}${padded}</div>
                    <div class="meal-type">${meal.toUpperCase()}</div>
                </div>`;
        }

        function updateUI() {
            updatePageSettings();
            document.getElementById('print-area').innerHTML = generateHTML();
        }

        function prepareAndPrint() {
            updateUI();
            setTimeout(() => { window.print(); }, 500);
        }

        window.onload = () => {
            setDefaultPrefix();
            updateUI();
        };
    </script>
</body>
</html>