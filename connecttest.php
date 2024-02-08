clear<?php
// Replace the values below with your actual database credentials
$dbHost = '192.168.1.105';
$dbUsername = 'quickparkdb';
$dbPassword = 'Mafra#2023';
$dbName = 'db_quickpark';

// Create database connection
$conn = new mysqli($dbHost, $dbUsername, $dbPassword, $dbName);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully";
?>
