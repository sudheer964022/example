<?php
$host = "localhost"; // Change if using a different database server
$user = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password (empty)
$dbname = "lms_web"; // Replace with your actual database name

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
