<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli('localhost', 'root', '', 'lms_web');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $is_admin = isset($_POST['is_admin']) && $_POST['is_admin'] === '1';

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $reg_no = trim($_POST['reg_no']);
    $name = htmlspecialchars(trim($_POST['name']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']); // Always trim phone number

    // Validate name
    if (!empty($name) && !preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $error = "Name should only contain letters and spaces.";
    }

    // If reg_no is empty, generate a unique registration number
    if (empty($reg_no)) {
        $attempts = 0;
        $max_attempts = 10;

        do {
            // Generate a new 12-digit registration number
            $reg_no = str_pad(rand(0, 999999999999), 12, '0', STR_PAD_LEFT);

            // Check if the reg_no exists in either users or admin table
            $stmt = $conn->prepare("SELECT 'users' AS table_name FROM users WHERE reg_no = ? UNION SELECT 'admin' FROM admin WHERE reg_no = ?");
            $stmt->bind_param("ss", $reg_no, $reg_no);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = "Registration number already exists in: ";
                $used_tables = [];
                while ($row = $result->fetch_assoc()) {
                    $used_tables[] = $row['table_name'];
                }
                $error .= implode(" and ", $used_tables) . ".";
            }

            $stmt->close();
            $attempts++;

        } while ($result->num_rows > 0 && $attempts < $max_attempts);

        if ($attempts >= $max_attempts) {
            die("Error: Unable to generate a unique registration number after $max_attempts attempts.");
        }
    }

    // Validate each field individually
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!empty($phone) && !preg_match('/^\d{10}$/', $phone)) {
        $error = "Phone number must be exactly 10 digits.";
    } elseif (!empty($password) && !preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $error = "Password must be at least 8 characters, include a letter, a number, and a special character.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    }

    // Check for existing name in users or admin table
    if (empty($error)) {
        $stmt = $conn->prepare("SELECT 'users' AS table_name FROM users WHERE name = ? UNION SELECT 'admin' FROM admin WHERE name = ?");
        $stmt->bind_param("ss", $name, $name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = "Name already exists in: ";
            $used_tables = [];
            while ($row = $result->fetch_assoc()) {
                $used_tables[] = $row['table_name'];
            }
            $error .= implode(" and ", $used_tables) . ".";
        }
        $stmt->close();
    }

    // Check for existing email in users or admin table
    if (empty($error)) {
        $stmt = $conn->prepare("SELECT 'users' AS table_name FROM users WHERE email = ? UNION SELECT 'admin' FROM admin WHERE email = ?");
        $stmt->bind_param("ss", $email, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = "Email already exists in: ";
            $used_tables = [];
            while ($row = $result->fetch_assoc()) {
                $used_tables[] = $row['table_name'];
            }
            $error .= implode(" and ", $used_tables) . ".";
        }
        $stmt->close();
    }

    // Check for existing phone number in users or admin table
    if (empty($error) && !empty($phone)) {
        $stmt = $conn->prepare("SELECT 'users' AS table_name FROM users WHERE phone = ? UNION SELECT 'admin' FROM admin WHERE phone = ?");
        $stmt->bind_param("ss", $phone, $phone);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Phone number already exists in: ";
            $used_tables = [];
            $stmt->bind_result($table_name);
            while ($stmt->fetch()) {
                $used_tables[] = $table_name;
            }
            $error .= implode(" and ", $used_tables) . ".";
        }
        $stmt->close();
    }

    // If no errors, insert data into the database
    if (empty($error)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        if ($is_admin) {
            $new_id = "ADM-" . str_pad(rand(1, 9999999), 7, '0', STR_PAD_LEFT);
            $stmt = $conn->prepare("INSERT INTO admin (id, email, reg_no, name, phone, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $new_id, $email, $reg_no, $name, $phone, $hashed_password);
        } else {
            $new_id = "USR-" . str_pad(rand(1, 9999999), 7, '0', STR_PAD_LEFT);
            $stmt = $conn->prepare("INSERT INTO users (id, email, reg_no, name, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $new_id, $email, $reg_no, $name, $hashed_password);
        }

        if ($stmt->execute()) {
            $success_message = $is_admin ? "Admin account created successfully. After 10Sec redirected to SignIn Page" : "User account created successfully. Your Registration Number is: $reg_no  ,After 10Sec redirected to SignIn Page";
        } else {
            $error = "Error creating account: " . $stmt->error;
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
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="images/pic.png" type="image/x-icon">
    <title>Create Account</title>
    <style>
        .link-btn {
            font-family: "Algerian", serif;
            background: none;
            border: none;
            color: rgb(255, 94, 0);
            text-decoration: none;
            cursor: pointer;
            font-size: 1.1rem;
            margin-top: 20px; 
            text-align: center;
            font-weight: bold;
            justify-content: center;
        }
        .link-btn:hover {
            text-decoration: underline;
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create Account</h1>
        <form id="signupForm" method="POST" action="SignUp.php">
            <input type="hidden" id="is_admin" name="is_admin" value="0">
            
            <div class="form-group">
                <input type="text" id="name" name="name" placeholder="Name" required>
            </div>
            <div class="form-group">
                <input type="text" id="reg_no" name="reg_no" placeholder="Registration Number (Leave blank for auto-generation)">
            </div>
            <div class="form-group">
                <input type="email" id="email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group" id="phoneGroup" style="display: none;">
                <input type="text" id="phone" name="phone" placeholder="Phone Number (10 Digits)">
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            <div class="form-group">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="showPassword"> Show Password
                </label>
            </div>
            <button type="submit" class="btn">Create Account</button>
        </form>

        <?php if (!empty($error)): ?>
            <div class="notification error"> <?php echo $error; ?> </div>
        <?php elseif (!empty($success_message)): ?>
            <div class="notification success"> <?php echo $success_message; ?> </div>
        <?php endif; ?>
        
        <div style="display: block; justify-content: center; margin-top: 10px;"><button class="link-btn" onclick="redirectToLogin()">Already have an account ? Login</button></div>
        <button class="link-btn" onclick="enableAdminMode()">Create Admin Account</button>
    </div>

    <script>
        let manualRedirect = false;

function redirectToLogin() {
    manualRedirect = true;
    window.location.href = "SignIn.php";
}

document.getElementById('showPassword').addEventListener('change', function () {
    let type = this.checked ? 'text' : 'password';
    document.getElementById('password').type = type;
    document.getElementById('confirm_password').type = type;
});

function enableAdminMode() {
    document.getElementById('phoneGroup').style.display = "block";
    document.getElementById('is_admin').value = "1";
}

document.addEventListener('DOMContentLoaded', () => {
    const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');

    inputs.forEach(input => {
        const placeholder = input.placeholder;

        input.addEventListener('focus', () => {
            input.placeholder = '';
        });

        input.addEventListener('blur', () => {
            input.placeholder = placeholder;
        });
    });

    // Wait for 20 seconds before hiding notifications and redirecting
    setTimeout(() => {
        if (!manualRedirect) {
            // Hide all notifications
            document.querySelectorAll('.notification').forEach(el => el.style.display = 'none');

            // Check if there is a success notification
            const successNotification = document.querySelector('.notification.success');
            if (successNotification) {
                // Redirect to "SignIn.php"
                window.location.href = "SignIn.php";
            }
        }
    }, 20000);
});

    </script>
</body>
</html>