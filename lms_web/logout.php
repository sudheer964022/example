<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: SignIn.php");
    exit();
}

// Set timezone to Indian Standard Time (IST)
date_default_timezone_set('Asia/Kolkata');  // Ensures timestamps are in IST

// Database connection
$conn = new mysqli('localhost', 'root', '', 'lms_web');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$current_timestamp = date('Y-m-d H:i:s'); // Gets current time in IST

// Check if the user is an admin
$stmt = $conn->prepare("SELECT id FROM admin WHERE id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->store_result();

// Prepare the correct SQL statement based on user role
if ($stmt->num_rows > 0) {
    // User is an admin, update "latest_logout" in the admin table
    $update_stmt = $conn->prepare("UPDATE admin SET latest_logout = ? WHERE id = ?");
} else {
    // User is a normal user, update "latest_logout" in the users table
    $update_stmt = $conn->prepare("UPDATE users SET latest_logout = ? WHERE id = ?");
}

// Execute the update query
if ($update_stmt) {
    $update_stmt->bind_param("ss", $current_timestamp, $user_id);
    $update_stmt->execute();
    $update_stmt->close();
}

$stmt->close();
$conn->close();

// Destroy the session and redirect to login page
session_unset();
session_destroy();
header("Location: SignIn.php");
exit();
?>
