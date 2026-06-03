<?php
// Google Sheets Data Viewer for PHP 7.4.33
// Using direct HTTP requests to avoid Composer dependency issues
// Configuration
//https://docs.google.com/spreadsheets/d/1opQXSz0pRk6653AISkOXj6JnjhkikP6oM349FTAwgdHuIg_w/edit?usp=sharing SAMPLE
//https://docs.google.com/spreadsheets/d/14WhTQFcq1LT7wEuVYAectQ8eD-SCCNRJ555D2RDMcjIjWE/edit?usp=sharing
$spreadsheetId = '1opQXSz0555pRk6653AISkOXj6JnP6oM340009FTAwgdHuIg_w';
$apiKey = 'AIzaSyA98uR7DPyY6R5TD5r666kWsBaKgZ48DBH9X0';
$range = 'Sheet1';
$checkatt = 50;
// Function to fetch data from Google Sheets using direct HTTP request
function getGoogleSheetsData($spreadsheetId, $range, $apiKey) {
    $url = "https://sheets.googleapis.com/v4/spreadsheets/$spreadsheetId/values/$range?key=$apiKey";
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    // Check for errors
    if ($error) {
        throw new Exception("cURL Error: " . $error);
    }
    if ($httpCode !== 200) {
        throw new Exception("HTTP Error: Code $httpCode");
    }
    // Decode response
    $data = json_decode($response, true);
    if (!isset($data['values'])) {
        throw new Exception("Invalid response format from Google Sheets API");
    }
    return $data['values'];
}
// Function to fetch spreadsheet properties including last modified date
function getGoogleSheetsProperties($spreadsheetId, $apiKey) {
    $url = "https://sheets.googleapis.com/v4/spreadsheets/$spreadsheetId?key=$apiKey&fields=properties";
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    // Check for errors
    if ($error) {
        throw new Exception("cURL Error: " . $error);
    }
    if ($httpCode !== 200) {
        throw new Exception("HTTP Error: Code $httpCode");
    }
    // Decode response
    return json_decode($response, true);
}
// Try to fetch data and properties
$values = [];
$lastModified = '';
$error = '';
try {
    $values = getGoogleSheetsData($spreadsheetId, $range, $apiKey);
    $properties = getGoogleSheetsProperties($spreadsheetId, $apiKey);
    $lastModified = isset($properties['properties']['modifiedTime']) ?
        date("d-m-Y", strtotime($properties['properties']['modifiedTime'])) :
        date("d-m-Y");
} catch (Exception $e) {
    $error = $e->getMessage();
}
// If it's an AJAX request, return JSON data
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    if (!empty($error)) {
        echo json_encode(['error' => $error]);
    } else {
        echo json_encode(['data' => $values, 'lastModified' => $lastModified]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Sheets ID Cards</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome CSS for icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-top: 2rem;
        }
        .card-container {
            gap: 1.5rem;
        }
        .id-card {
            width: 350px;
            padding: 1.5rem;
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #ffffff, #f9f9f9);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .id-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }
        .id-card::before {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 80px;
            height: 80px;
            background: #0d6efd;
            border-radius: 50%;
            opacity: 0.1;
            z-index: 0;
        }
        .id-card-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 1rem;
        }
        .id-card-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 0.5rem;
        }
        .id-card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
        }
        .id-card-body {
            font-size: 0.95rem;
        }
        .id-card-body p {
            margin-bottom: 0.5rem;
        }
        .id-card-label {
            font-weight: 600;
            color: #555;
            margin-right: 5px;
        }
        .search-container {
            max-width: 500px;
            margin-bottom: 2rem;
        }
        .loading, .error-message {
            text-align: center;
            font-size: 1.2rem;
            color: #6c757d;
            margin-top: 2rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="text-center mb-4 text-primary fw-bold">ID Card Viewer</h1>
        <p class="text-center text-muted">
            <i class="fas fa-database me-2"></i>Data last updated: <span id="last-modified"><?php echo htmlspecialchars($lastModified); ?></span>
        </p>
        <div class="search-container mx-auto">
            <div class="input-group mb-3 shadow-sm rounded-pill">
                <span class="input-group-text bg-white border-0 rounded-start-pill"><i class="fas fa-search"></i></span>
                <input type="text" id="searchInput" class="form-control border-0 rounded-end-pill" placeholder="Search by name, roll number, or batch...">
            </div>
        </div>

        <!-- ID Card Display Section -->
        <?php if (!empty($error)) { ?>
            <div id="error-message" class="error-message alert alert-danger rounded-3 shadow-sm" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($error); ?>. Please check the API key, Spreadsheet ID, and sheet name.
            </div>
        <?php } else { ?>
            <div id="loading" class="loading">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Fetching data...</p>
            </div>
            <div id="idCardsContainer" class="d-flex flex-wrap justify-content-center card-container">
                <!-- ID cards will be generated here by JavaScript -->
            </div>
        <?php } ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('searchInput');
            const idCardsContainer = document.getElementById('idCardsContainer');
            const loadingIndicator = document.getElementById('loading');

            // Initial data from PHP. The first row is the header.
            const allData = <?php echo json_encode(array_slice($values, 1)); ?>;
            const headers = <?php echo json_encode($values[0] ?? []); ?>;

            // Function to find the index of a column header, case-insensitive.
            const findColumnIndex = (targetHeader) => {
                return headers.findIndex(header => header.toLowerCase().trim() === targetHeader.toLowerCase().trim());
            };

            // Dynamically find indices based on the provided column headers
            const photoIndex = findColumnIndex('Picture');
            const nameIndex = findColumnIndex('NAME');
            const batchIndex = findColumnIndex('BATCH');
            const rollNoIndex = findColumnIndex('ROLLNO');
            const contactIndex = findColumnIndex('Contact');
            const addressIndex = findColumnIndex('Address');


            // Function to render a single ID card
            const createCard = (rowData) => {
                // Ensure rowData has enough columns to prevent errors
                if (rowData.length < Math.max(photoIndex, nameIndex, batchIndex, rollNoIndex, contactIndex, addressIndex)) {
                    return null; // Skip invalid rows
                }

                const photoUrl = rowData[photoIndex] || '';
                const name = rowData[nameIndex] || 'N/A';
                const batch = rowData[batchIndex] || 'N/A';
                const rollNo = rowData[rollNoIndex] || 'N/A';
                const contact = rowData[contactIndex] || 'N/A';
                const address = rowData[addressIndex] || 'N/A';

                const cardDiv = document.createElement('div');
                cardDiv.className = 'id-card m-3 d-flex flex-column shadow-lg';
                cardDiv.innerHTML = `
                    <div class="id-card-header">
                        <img src="${photoUrl}" alt="${name}" class="id-card-photo" onerror="this.onerror=null;this.src='https://placehold.co/120x120/E8E8E8/808080?text=No+Photo';">
                        <h5 class="id-card-title">${name}</h5>
                    </div>
                    <div class="id-card-body">
                        <p><i class="fas fa-id-card-alt text-primary me-2"></i><span class="id-card-label">Roll No:</span>${rollNo}</p>
                        <p><i class="fas fa-graduation-cap text-primary me-2"></i><span class="id-card-label">Batch:</span>${batch}</p>
                        <p><i class="fas fa-phone-alt text-primary me-2"></i><span class="id-card-label">Contact:</span>${contact}</p>
                        <p><i class="fas fa-home text-primary me-2"></i><span class="id-card-label">Address:</span>${address}</p>
                    </div>
                `;
                return cardDiv;
            };

            // Function to render all ID cards based on filtered data
            const renderCards = (data) => {
                idCardsContainer.innerHTML = '';
                if (data.length === 0) {
                    idCardsContainer.innerHTML = '<p class="text-center w-100 text-muted">No results found.</p>';
                } else {
                    data.forEach(rowData => {
                        const card = createCard(rowData);
                        if (card) {
                            idCardsContainer.appendChild(card);
                        }
                    });
                }
            };

            // Function to handle the search input
            const handleSearch = () => {
                const query = searchInput.value.toLowerCase().trim();
                const filteredData = allData.filter(row => {
                    return row.some(cell => typeof cell === 'string' && cell.toLowerCase().includes(query));
                });
                renderCards(filteredData);
            };

            // Initial rendering
            if (loadingIndicator) {
                loadingIndicator.style.display = 'none';
            }
            if (idCardsContainer) {
                 renderCards(allData);
            }

            // Event listener for "search on typing"
            searchInput.addEventListener('keyup', handleSearch);
        });
    </script>
</body>
</html>
