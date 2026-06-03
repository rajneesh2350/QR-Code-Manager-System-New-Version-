<?php
session_start();
require_once 'includes/config.php';

header('Content-Type: application/json');

// Ensure this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$password = $_POST['password'] ?? '';

// Assuming you store the logged-in user's username or ID in the session.
// Change 'username' below to whatever session variable you use for login (e.g., $_SESSION['user_id']).
$loggedInUser = $_SESSION['username'] ?? '';

if (empty($loggedInUser)) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
    exit;
}

// Fetch the user's password from the database
$stmt = $conn->prepare("SELECT password FROM users WHERE username = ? LIMIT 1");
$stmt->bind_param("s", $loggedInUser);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
    exit;
}

$user = $result->fetch_assoc();

// Verify the password (assuming you are using password_hash() to store passwords)
// If you are storing passwords in plain text or MD5, change this logic (though password_hash is highly recommended!)
if (password_verify($password, $user['password'])) {

    // Passwords match! Reset the databases
    $conn->query("TRUNCATE TABLE qr_code_coupon");
    $conn->query("TRUNCATE TABLE qr_scan_logs_coupon");

    echo json_encode(['success' => true, 'message' => 'Databases have been reset successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Incorrect password. Action aborted.']);
}
?>