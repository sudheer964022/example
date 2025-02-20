<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'lms_web');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id']; 
$full_name = 'Unknown User';
$initial = '?';
$is_admin = 0;
$is_host = 0;

// Check if user is an admin
if ($stmt = $conn->prepare("SELECT name FROM admin WHERE id = ?")) {
    $stmt->bind_param("s", $user_id); // Use 's' for string type
    $stmt->execute();
    $stmt->bind_result($admin_name);
    if ($stmt->fetch() && !empty($admin_name)) {
        $full_name = strtoupper(trim($admin_name));
        $initial = strtoupper(substr($full_name, 0, 1));
        $is_admin = 1;
        $user_id = 'ADM-' . $_SESSION['user_id']; // Prepend "ADM-"
    }
    $stmt->close();
}

// If not admin, check if user exists in the host table
if (!$is_admin) {
    if ($stmt = $conn->prepare("SELECT name FROM host WHERE id = ?")) {
        $stmt->bind_param("s", $user_id); // Use 's' for string type
        $stmt->execute();
        $stmt->bind_result($host_name);
        if ($stmt->fetch() && !empty($host_name)) {
            $full_name = strtoupper(trim($host_name));
            $initial = strtoupper(substr($full_name, 0, 1));
            $is_host = 1;
            $user_id = 'HST-' . $user_id; // Prepend "HST-"
        }
        $stmt->close();
    }
}

// If not host, check if user exists in the users table
if (!$is_admin && !$is_host) {
    if ($stmt = $conn->prepare("SELECT name FROM users WHERE id = ?")) {
        $stmt->bind_param("s", $user_id); // Use 's' for string type
        $stmt->execute();
        $stmt->bind_result($user_name);
        if ($stmt->fetch() && !empty($user_name)) {
            $full_name = strtoupper(trim($user_name));
            $initial = strtoupper(substr($full_name, 0, 1));
            $user_id = 'USR-' . $user_id; // Prepend "USR-"
        }
        $stmt->close();
    }
}

$_SESSION['is_admin'] = $is_admin;
$_SESSION['is_host'] = $is_host;
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="icon" href="images/pic.png" type="image/x-icon">
    <title>User Dashboard</title>
    <script>
        function toggleDropdown() {
            document.getElementById("dropdown-menu").classList.toggle("show");
        }

        window.onclick = function(event) {
            if (!event.target.matches('.user-circle')) {
                let dropdown = document.getElementById("dropdown-menu");
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        }
    </script>
</head>
<body>
<div class="header">
    <div class="logo">
        <img src="images/pic.png" alt="Logo" class="logo-img">
    </div>
    <div class="nav">
        <img src="images/bell.png" alt="Notifications" class="bell-icon">
        <div class="padd">   
            <div class="user-circle" onclick="toggleDropdown()">
                <?php echo htmlspecialchars($initial); ?>
            </div>
        </div>
        <div class="dropdown" id="dropdown-menu">
            <div style="border:1px solid blue; border-radius: 5px;">
            <p>
                <?php echo htmlspecialchars($full_name); ?>
                <hr>
                <?php
if ($is_admin) {
    echo '<span class="admin-badge" style="user-select:none;"><svg xmlns="http://www.w3.org/2000/svg" height="10px" viewBox="0 -960 960 960" width="10px" fill="black"><path d="M480-440q-59 0-99.5-40.5T340-580q0-59 40.5-99.5T480-720q59 0 99.5 40.5T620-580q0 59-40.5 99.5T480-440Zm0-80q26 0 43-17t17-43q0-26-17-43t-43-17q-26 0-43 17t-17 43q0 26 17 43t43 17Zm0 440q-139-35-229.5-159.5T160-516v-244l320-120 320 120v244q0 152-90.5 276.5T480-80Zm0-400Zm0-315-240 90v189q0 54 15 105t41 96q42-21 88-33t96-12q50 0 96 12t88 33q26-45 41-96t15-105v-189l-240-90Zm0 515q-36 0-70 8t-65 22q29 30 63 52t72 34q38-12 72-34t63-52q-31-14-65-22t-70-8Z"/></svg>Admin</span>';
} elseif ($is_host) {
    echo '<span class="host-badge" style="font-size: 8px; background-color:rgb(232, 170, 61); color: black; padding: 4px 4px; border-radius: 4px; margin-left: 5px; border: 0.1px solid rgba(0, 0, 0, 0.834); user-select:none;"><svg xmlns="http://www.w3.org/2000/svg" height="10px" viewBox="0 -960 960 960" width="10px" fill="black"><path d="M240-80q-33 0-56.5-23.5T160-160v-640q0-33 23.5-56.5T240-880h480q33 0 56.5 23.5T800-800v640q0 33-23.5 56.5T720-80H600l-40-80H400l-40 80H240Zm0-80h70l40-80h260l40 80h70v-640H240v640Zm80-200h320v-22q0-52-50-75t-110-23q-60 0-110 23t-50 75v22Zm160-160q33 0 56.5-23.5T560-600q0-33-23.5-56.5T480-680q-33 0-56.5 23.5T400-600q0 33 23.5 56.5T480-520Zm0 40Z"/></svg>Host</span>';
} else {
    echo '<span class="user-badge" style="user-select:none;"><svg xmlns="http://www.w3.org/2000/svg" height="10px" viewBox="0 -960 960 960" width="10px" fill="black"><path d="M480-480q-60 0-102-42t-42-102q0-60 42-102t102-42q60 0 102 42t42 102q0 60-42 102t-102 42ZM192-192v-96q0-23 12.5-43.5T239-366q55-32 116.29-49 61.29-17 124.5-17t124.71 17Q666-398 721-366q22 13 34.5 34t12.5 44v96H192Zm72-72h432v-24q0-5.18-3.03-9.41-3.02-4.24-7.97-6.59-46-28-98-42t-107-14q-55 0-107 14t-98 42q-5 4-8 7.72-3 3.73-3 8.28v24Zm216.21-288Q510-552 531-573.21t21-51Q552-654 530.79-675t-51-21Q450-696 429-674.79t-21 51Q408-594 429.21-573t51 21Zm-.21-72Zm0 360Z"/></svg>User</span>';
}
?>

            </p>
            <a href="#" onclick="loadProfile(event)">
                <img src="images/person.png" alt="Profile" class="menu-icon"> My Profile
            </a> 
            <a href="#" onclick="delayedLogout()">
                <img src="images/logout.png" alt="Logout" class="menu-icon-logout"> Log Out
            </a>
            </div>
        </div>
    </div>
</div>

<div class="banner" style="width: 100%; padding-left: 30px;">
    <h2>Hi <?php echo htmlspecialchars($full_name); ?>,</h2>
    <h1>Welcome to SRVS Learning Hub</h1>
</div>

<div class="container" id="profile-container"></div>

<script>
   function loadProfile(event) {
    event.preventDefault();

    let profileContainer = document.getElementById("profile-container");
    let banner = document.querySelector(".banner");
    let header = document.querySelector(".header");

    fetch("profile.php")
        .then(response => {
            if (!response.ok) {
                throw new Error("Profile page not found!");
            }
            return response.text();
        })
        .then(data => {
            // Create the back button properly
            let backButton = document.createElement("button");
            backButton.className = "back-btn";
            backButton.innerText = "⬅ Back";
            backButton.onclick = goBack;

            // Clear previous content and add back button
            profileContainer.innerHTML = "";
            profileContainer.appendChild(backButton);

            // Insert the profile data
            let profileContent = document.createElement("div");
            profileContent.innerHTML = data;
            profileContainer.appendChild(profileContent);

            // Style the profile container
            profileContainer.style.display = "flex";
            profileContainer.style.flexDirection = "column";
            profileContainer.style.justifyContent = "center";
            profileContainer.style.alignItems = "center";
            profileContainer.style.height = "calc(100vh - 50px)";
            profileContainer.style.marginTop = "150px";

            // Hide the banner
            banner.style.display = "none";

            // Ensure header remains fixed
            header.style.position = "fixed";
            header.style.top = "0";
            header.style.width = "100%";
            header.style.zIndex = "1000";
        })
        .catch(error => console.error("Error fetching profile:", error));
}

// Function to go back to the original dashboard view
function goBack() {
    let profileContainer = document.getElementById("profile-container");
    let banner = document.querySelector(".banner");

    // Clear the profile container
    profileContainer.innerHTML = "";

    // Restore the banner
    banner.style.display = "block";
}
function delayedLogout() {
        document.getElementById("dropdown-menu").style.opacity = "0"; 
        setTimeout(function() {
            window.location.href = "logout.php";
        }, 1000);
    }

</script>
</body>
</html>
