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
$stmt = $conn->prepare("SELECT name, email, phone, dob, gender, tagline, about_me FROM admin WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("s", $original_user_id);
    $stmt->execute();
    $stmt->bind_result($full_name, $email, $mobile, $birth_date, $gender, $tagline, $about_me);
    if ($stmt->fetch()) {
        $is_admin = 1;
        $user_id = 'ADM-' . $original_user_id; // Prefix admin ID
    }
    $stmt->close();
}

// If not admin, fetch from `users` table
if (!$is_admin) {
    $stmt = $conn->prepare("SELECT name, email, phone, dob, gender, tagline, about_me FROM users WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("s", $original_user_id);
        $stmt->execute();
        $stmt->bind_result($full_name, $email, $mobile, $birth_date, $gender, $tagline, $about_me);
        if ($stmt->fetch()) {
            $user_id = 'USR-' . $original_user_id; // Prefix user ID
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="css/profile.css">
</head>
<body class="padding">
    <div class="profile-container">
        <div class="card">
            <ul class="menu">
                <li class="menu-item">Personal Details <img src="images/arrow_right.png" class="arrow"></li>
                <li class="menu-item">Address <img src="images/arrow_right.png" class="arrow"></li>
                <li class="menu-item">Education <img src="images/arrow_right.png" class="arrow"></li>
                <li class="menu-item">Work <img src="images/arrow_right.png" class="arrow"></li>
                <li class="menu-item">Skills <img src="images/arrow_right.png" class="arrow"></li>
                <li class="menu-item">Social Links <img src="images/arrow_right.png" class="arrow"></li>
            </ul>
            <div class="content personal-details">
                <p>Here are the personal details extended content.</p>
            </div>
        </div>
    </div>

    <script>
       document.addEventListener("DOMContentLoaded", () => {
            document.querySelectorAll('.menu-item').forEach(item => {
                item.addEventListener('click', () => {
                    // Toggle 'active' class on the clicked item
                    item.classList.toggle('active');

                    // Deselect all other items
                    document.querySelectorAll('.menu-item').forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('active');
                            const otherArrow = otherItem.querySelector('.arrow');
                            if (otherArrow) {
                                otherArrow.style.transform = 'rotate(0deg)';
                            }
                        }
                    });

                    // Select the arrow image inside the clicked item
                    const arrow = item.querySelector('.arrow');

                    // Apply rotation based on the active state
                    if (arrow) {
                        arrow.style.transform = item.classList.contains('active') ? 'rotate(90deg)' : 'rotate(0deg)';
                    }
                });
            });
        });
    </script>
</body>
</html>

<style>
body {
    display: flex;
    min-height: 100vh;
    margin: 0;
    background-color: #f0f0f0;
    font-family: Arial, sans-serif;
    overflow: scroll;
    margin-bottom: 10%;
}
/* Centering Profile Container */
.profile-container {
    width: 100vw;
    max-width: 800px;
    padding: 60px 50px;
    background: rgb(246, 246, 246);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.276);
    border-radius: 10px;
}
@media (max-width: 768px) {
    .profile-container {
        width: 80vw;
    }
}

@media (max-width: 520px) {
    .profile-container {
        width: 70vw;
        padding-left: 50px;
    }
}

@media (max-width: 400px) {
    .profile-container {
        width: 60vw;
        padding-left: 50px;
    }
}
@media (max-width: 300px) {
    .profile-container {
        width: 50vw;
        padding-left: 50px;
    }
}


.card {
    background: #ffffffea;
    border-radius: 20px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    padding: 20px;
    width: auto;
    height: auto;
    transition: width 0.3s;
    border: 1px solid rgba(0, 0, 0, 0.614);
}

.menu {
    list-style: none;
    padding: 0;
    margin: 0;
    border: 1px solid rgba(0, 0, 0, 0.614);
    border-radius: 20px;
}
.menu li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px;
    border-bottom: 1px solid #e0e0e0;
    font-size: 25px;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.3s;
}

.menu li:hover {
    background: #eeeeee;
    border-radius: 20px;
}

.menu li:last-child {
    border-bottom: none;
}

.arrow {
    width: 20px;
    height: 20px; 
    transition: transform 0.5s ease-in-out;
}

.menu li.active .arrow {
    transform: rotate(90deg);
}

/* Responsive Design */
@media (max-width: 600px) {
    .card {
        width: 95%;
    }

    .menu li {
        font-size: 16px;
        padding: 20px;
    }

    .arrow {
        font-size: 18px;
    }
}

@media (max-width: 400px) {
    .menu li {
        font-size: 14px;
        padding: 15px;
    }

    .arrow {
        font-size: 16px;
    }
}
</style>
