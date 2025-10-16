<?php // Database connection
$host = "localhost";
$user = "root"; // default in XAMPP
$pass = ""; // leave empty for XAMPP
$dbname = "user_db";

$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>