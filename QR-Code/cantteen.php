<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A4 Meal Coupon Generator - 3x5 Grid</title>
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
            text-align: center;
            margin-bottom: 20px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            gap: 15px;
        }

        .controls label {
            font-weight: bold;
        }

        .controls input, .controls button {
            padding: 8px 10px;
            font-size: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .controls button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
            padding: 10px 15px;
            margin-left: 10px;
        }

        .controls button:hover {
            background-color: #0056b3;
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

        /* --- Individual Coupon Styling --- */
        .coupon {
            border: 2px dashed #444;
            padding: 15px 10px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            /* 170px ensures exactly 5 rows fit on an A4 page */
            height: 170px;
            page-break-inside: avoid;
            position: relative;
            overflow: hidden;
        }

        /* --- Watermark Styling --- */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 42px;
            font-weight: 900;
            color: rgba(0, 0, 0, 0.08);
            white-space: nowrap;
            z-index: 0;
            pointer-events: none;
            user-select: none;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .coupon img, .coupon-no, .meal-type {
            position: relative;
            z-index: 1;
        }

        .coupon img {
            max-height: 65px;
            margin-bottom: 10px;
        }

        .coupon-no {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 15px;
            color: #333;
        }

        .meal-type {
            font-size: 18px;
            font-weight: bold;
            border: 2px solid #000;
            padding: 6px 16px;
            border-radius: 4px;
            letter-spacing: 1px;
            background-color: rgba(255, 255, 255, 0.85);
        }

        /* --- Print-Specific Styles --- */
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
            body {
                background: white;
                padding: 0;
            }
            .controls {
                display: none;
            }
            #print-area {
                box-shadow: none;
                width: 100%;
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <div class="controls no-print">
        <div>
            <label for="prefix">Prefix:</label>
            <input type="text" id="prefix" value="IGI/240326/" size="12">
        </div>

        <div>
            <label for="startFrom">Start from No:</label>
            <input type="number" id="startFrom" value="120" min="1">
        </div>

        <div>
            <label for="endAt">End upto (Rows):</label>
            <input type="number" id="endAt" value="200" min="1">
        </div>

        <button onclick="generateAndPrint()">Generate & Print</button>
    </div>

    <div id="print-area"></div>

    <script>
        function createCoupons(startFrom, endAt, prefix) {
            let htmlContent = '';
            // Hardcoded order for the 3 columns in every row
            const meals = ["LUNCH", "LUNCH", "LUNCH"];

            // Loop from the starting number to the ending number (inclusive)
            for (let i = startFrom; i <= endAt; i++) {
                let paddedNum = i.toString().padStart(3, '0');

                // Loop 3 times to create the 3 columns for the current row
                for (let j = 0; j < 3; j++) {
                    htmlContent += `
                        <div class="coupon">
                            <div class="watermark">IGIPESS</div>
                            <img src="igipesslogo1.png" alt="Logo">
                            <div class="coupon-no">COUPON NO. ${prefix}${paddedNum}</div>
                            <div class="meal-type">${meals[j]}</div>
                        </div>
                    `;
                }
            }
            return htmlContent;
        }

        function generateAndPrint() {
            const startFrom = parseInt(document.getElementById('startFrom').value);
            const endAt = parseInt(document.getElementById('endAt').value);
            const prefix = document.getElementById('prefix').value;
            const printArea = document.getElementById('print-area');

            // Safety check
            if (endAt < startFrom) {
                alert("The 'End upto' number must be greater than or equal to the 'Start from' number.");
                return;
            }

            printArea.innerHTML = createCoupons(startFrom, endAt, prefix);

            // Give it a half-second to load the images before prompting the print dialog
            setTimeout(() => {
                window.print();
            }, 500);
        }

        window.onload = () => {
            // Generate preview on load using the default values (120 to 200)
            const startFrom = parseInt(document.getElementById('startFrom').value);
            const endAt = parseInt(document.getElementById('endAt').value);
            const prefix = document.getElementById('prefix').value;

            document.getElementById('print-area').innerHTML = createCoupons(startFrom, endAt, prefix);
        };
    </script>

</body>
</html>