<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Coupon Generator - Tiled Watermark</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #e9ecef; margin: 0; padding: 20px; }

        /* Control Panel */
        .controls {
            background: white; padding: 20px; border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px;
            max-width: 950px; margin-left: auto; margin-right: auto;
            display: flex; flex-wrap: wrap; justify-content: center; gap: 15px;
            position: sticky; top: 10px; z-index: 100;
        }

        .config-group { border: 1px solid #ddd; padding: 10px; border-radius: 5px; display: flex; flex-direction: column; gap: 5px; }
        .config-group label { font-weight: bold; font-size: 12px; color: #555; }

        .btn-container { display: flex; flex-direction: column; gap: 8px; justify-content: center; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; transition: opacity 0.2s; }
        .btn-preview { background-color: #6c757d; color: white; }
        .btn-print { background-color: #28a745; color: white; }
        .btn:hover { opacity: 0.9; }

        /* Layout Grid */
        #print-area {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 15px; background: white; padding: 10mm;
            width: 210mm; margin: auto; box-sizing: border-box;
        }

        /* Coupon Design */
        .coupon {
            border: 2px dashed #444; padding: 15px 10px; text-align: center;
            display: flex; flex-direction: column; align-items: center;
            justify-content: center; height: 170px; page-break-inside: avoid;
            position: relative; overflow: hidden; background: white;
        }

        /* Tiled Watermark Style */
        .watermark-container {
            position: absolute; top: -20%; left: -20%; width: 140%; height: 140%;
            display: flex; flex-wrap: wrap; justify-content: space-around;
            align-content: space-around; gap: 12px; transform: rotate(-25deg);
            z-index: 0; opacity: 0.20; pointer-events: none; user-select: none;
        }
        .watermark-item { font-size: 9px; font-weight: bold; white-space: nowrap; }

        .coupon img { max-height: 60px; margin-bottom: 5px; z-index: 1; position: relative; }
        .coupon-no { font-weight: bold; font-size: 13px; margin-bottom: 10px; z-index: 1; color: #000; position: relative; }
        .meal-type {
            font-size: 16px; font-weight: bold; border: 2px solid #000;
            padding: 4px 12px; border-radius: 4px; background: rgba(255,255,255,0.9);
            z-index: 1; position: relative;
        }

        @media print {
            @page { size: A4 portrait; margin: 0; }
            .no-print { display: none !important; }
            body { background: white; padding: 0; }
            #print-area { box-shadow: none; width: 100%; padding: 10mm; }
            .coupon { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="controls no-print">
        <div class="config-group">
            <label>Prefix:</label>
            <input type="text" id="prefix" value="" size="20">
        </div>

        <div class="config-group">
            <label>Range (Start - End):</label>
            <div>
                <input type="number" id="startFrom" value="1" style="width: 50px;"> to
                <input type="number" id="endAt" value="15" style="width: 50px;">
            </div>
        </div>

        <div class="config-group">
            <label>Numbering Logic:</label>
            <div style="font-size: 12px;">
                <input type="radio" name="mode" id="modeRow" value="row" checked>
                <label for="modeRow">One Row 1 Number</label><br>
                <input type="radio" name="mode" id="modeCont" value="cont">
                <label for="modeCont">Continuous Numbering</label>
            </div>
        </div>

        <div class="config-group">
            <label>Meals (Col 1, 2, 3):</label>
            <div>
                <input type="text" id="m1" value="TEA" style="width: 55px;">
                <input type="text" id="m2" value="LUNCH" style="width: 55px;">
                <input type="text" id="m3" value="TEA" style="width: 55px;">
            </div>
        </div>

        <div class="btn-container">
            <button class="btn btn-preview" onclick="updateUI()">Update Preview</button>
            <button class="btn btn-print" onclick="prepareAndPrint()">Print Now</button>
        </div>
    </div>

    <div id="print-area"></div>

    <script>
        // Set prefix to IGIPESS/DDMMYYYY/ on load
        function setDefaultPrefix() {
            const now = new Date();
            const day = String(now.getDate()).padStart(2, '0');
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const year = now.getFullYear();
            document.getElementById('prefix').value = `IGIPESS/${day}${month}${year}/`;
        }

        function generateHTML() {
            const start = parseInt(document.getElementById('startFrom').value) || 1;
            const end = parseInt(document.getElementById('endAt').value) || 1;
            const prefix = document.getElementById('prefix').value;
            const isContinuous = document.getElementById('modeCont').checked;
            const meals = [
                document.getElementById('m1').value || "TEA",
                document.getElementById('m2').value || "LUNCH",
                document.getElementById('m3').value || "TEA"
            ];

            let htmlContent = '';

            if (isContinuous) {
                let currentNumber = start;
                while (currentNumber <= end) {
                    for (let j = 0; j < 3; j++) {
                        if (currentNumber > end) break;
                        htmlContent += renderCouponHTML(prefix, currentNumber, meals[j]);
                        currentNumber++;
                    }
                }
            } else {
                for (let i = start; i <= end; i++) {
                    for (let j = 0; j < 3; j++) {
                        htmlContent += renderCouponHTML(prefix, i, meals[j]);
                    }
                }
            }
            return htmlContent;
        }

        function renderCouponHTML(prefix, num, meal) {
            let paddedNum = num.toString().padStart(3, '0');

            // Create the repeated watermark items
            let watermarkItems = '';
            for (let i = 0; i < 60; i++) {
                watermarkItems += `<div class="watermark-item">IGIPESS</div>`;
            }

            return `
                <div class="coupon">
                    <div class="watermark-container">${watermarkItems}</div>
                    <img src="igipesslogo1.png" alt="Logo">
                    <div class="coupon-no">COUPON NO. ${prefix}${paddedNum}</div>
                    <div class="meal-type">${meal.toUpperCase()}</div>
                </div>`;
        }

        function updateUI() {
            document.getElementById('print-area').innerHTML = generateHTML();
        }

        function prepareAndPrint() {
            updateUI();
            setTimeout(() => { window.print(); }, 300);
        }

        window.onload = () => {
            setDefaultPrefix();
            updateUI();
        };
    </script>
</body>
</html>