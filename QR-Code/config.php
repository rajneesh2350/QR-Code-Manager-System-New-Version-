<?php
// config.php
error_reporting(0);
ini_set('display_errors', 0);
$servername = "localhost";
$username = "domain-username";
$password = "domian-password";
$dbname = "domain-database-name";
// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
}
?>
