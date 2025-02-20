<?php
session_start();

// Redirect if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Enable error reporting for debugging (Disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$conn = new mysqli('localhost', 'root', '', 'lms_web');

// Handle connection error
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$original_user_id = $_SESSION['user_id']; // Keep original user ID
$user_id = $original_user_id; // Variable to modify

// Initialize variables
$full_name = $email = $mobile = $birth_date = $gender = $tagline = $about_me = NULL; // Default to NULL
$is_admin = 0;

// Check if the user is an admin
$stmt = $conn->prepare("SELECT id FROM admin WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("s", $original_user_id);
    $stmt->execute();
    if ($stmt->fetch()) {
        $is_admin = 1;
    }
    $stmt->close();
}

// Set table and columns based on user type
$table = $is_admin ? 'admin' : 'users';
$id_prefix = $is_admin ? 'ADM-' : 'USR-';

// Collect form data
$full_name = $_POST['full_name'] ?? NULL;
$new_email = $_POST['new_email'] ?? NULL;
$mobile = $_POST['mobile'] ?? NULL;
$birth_date = $_POST['birth_date'] ?? NULL;
$gender = $_POST['gender'] ?? NULL;
$tagline = $_POST['tagline'] ?? NULL;
$about_me = $_POST['about_me'] ?? NULL;

// Initialize error variables
$errors = [];

// Validate phone number if provided
if ($mobile !== NULL && !preg_match('/^\d{10}$/', $mobile)) {
    $errors[] = "Phone number must be exactly 10 digits.";
} else if ($mobile !== NULL) {
    // Check if the phone number is already used by another user in both tables
    $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? AND id != ? UNION SELECT id FROM admin WHERE phone = ? AND id != ?");
    $stmt->bind_param("ssss", $mobile, $original_user_id, $mobile, $original_user_id);
    $stmt->execute();
    if ($stmt->fetch()) {
        $errors[] = "Phone number is already used by another user.";
    }
    $stmt->close();
}

// Validate birth date if provided
if ($birth_date !== NULL && date('Y', strtotime($birth_date)) >= date('Y')) {
    $errors[] = "Birth date cannot be in the current year.";
}

// Validate tagline if provided
if ($tagline !== NULL && preg_match('/[^a-zA-Z\s]/', $tagline)) {
    $errors[] = "Tagline cannot contain special characters or digits.";
}

// Validate about me if provided
if ($about_me !== NULL && preg_match('/[^a-zA-Z\s]/', $about_me)) {
    $errors[] = "About Me cannot contain special characters or digits.";
}

if (empty($errors)) {
    // Prepare SQL statement
    $stmt = $conn->prepare("UPDATE $table SET name = ?, email = COALESCE(?, email), phone = ?, dob = ?, gender = ?, tagline = ?, about_me = ? WHERE id = ?");
    $stmt->bind_param("ssssssss", $full_name, $new_email, $mobile, $birth_date, $gender, $tagline, $about_me, $original_user_id);

    // Execute SQL statement
    if ($stmt->execute()) {
        $notification = "Profile updated successfully. After 10 seconds, you will be redirected to the Dashboard.";
        $notification_type = "success";
    } else {
        $notification = "Error updating profile: " . $stmt->error;
        $notification_type = "error";
    }

    $stmt->close();
} else {
    // Concatenate error messages
    $notification = implode('<br>', $errors);
    $notification_type = "error";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Update</title>
    <link rel="stylesheet" href="css/profile.css">
    <style>
        .notification {
            font-family: "Algerian", serif;
            position: fixed;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #4caf50;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 1.0em;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .error {
            background-color: #f44336;
        }
    </style>
</head>
<body>

<div class="notification <?php echo $notification_type; ?>">
    <?php echo $notification; ?>
</div>

<script>
    setTimeout(function() {
    var notification = document.querySelector('.notification');
    if (notification) {
        notification.style.display = 'none';
    }
    // Redirect to Dashboard.php after 15 seconds
    window.location.href = 'Dashboard.php';
}, 10000);
</script>

</body>
</html>
