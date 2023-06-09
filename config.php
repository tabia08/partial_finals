<?php
// Database configuration
$host = ""; // Hostname
$username = "root"; // MySQL username
$password = ""; // MySQL password
$database = "spogify"; // Database name

// Create a database connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}