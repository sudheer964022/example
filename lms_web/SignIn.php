<?php
session_start();
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST['email_or_reg']) && !empty($_POST['password'])) {
        $input = trim($_POST['email_or_reg']);
        $password = $_POST['password'];

        // Database connection
        $conn = new mysqli('localhost', 'root', '', 'lms_web');
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $row = null;
        $role = "";

        // Function to check user in a given table
        function checkUser($conn, $input, $table) {
            $column = filter_var($input, FILTER_VALIDATE_EMAIL) ? 'email' : 'reg_no';
            $sql = "SELECT id, name, password FROM $table WHERE $column = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $input);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        }

        // Check users table
        $row = checkUser($conn, $input, 'users');
        if ($row) {
            $role = "user";
        } else {
            // Check admin table
            $row = checkUser($conn, $input, 'admin');
            if ($row) {
                $role = "admin";
            } else {
                // Check host table
                $row = checkUser($conn, $input, 'host');
                if ($row) {
                    $role = "host";
                }
            }
        }

        // Verify user and password
        if ($row && password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['name'];
            $_SESSION['role'] = $role;

            header("Location: Dashboard.php");
            exit();
        } else {
            $message = $row ? "Invalid password." : "No account found with that email or registration number.";
        }

        $conn->close();
    } else {
        $message = "Please enter your email or registration number and password.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="images/pic.png" type="image/x-icon">
    <title>Sign In</title>
    <style>
        body { flex-direction: column; margin: 0; }
        .notification { padding: 10px; text-align: center; font-weight: bold; display: block; }
        .error { color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Sign In</h1>
        <form id="signInForm" method="POST" action="">
            <div class="form-group">
                <input type="text" id="email_or_reg" name="email_or_reg" placeholder="Email or Reg.No" required>
            </div>
            <div class="form-group">
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            <div class="form-group">
                <label for="showPassword">
                    <input type="checkbox" id="showPassword"> Show Password
                </label>
            </div>
            <button type="submit" class="btn">Sign In</button>
        </form>
        <div>
            <a href="SignUp.php" class="link"> I Don't Have Account ?, SignUp</a>
            <a href="password_reset.php" class="link">Forget Password</a>
        </div>
    </div>

    <script>
        document.getElementById('showPassword').addEventListener('change', function () {
            document.getElementById('password').type = this.checked ? 'text' : 'password';
        });
    </script>

    <?php if (!empty($message)): ?>
        <div class="notification error">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <script>
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
        });
    </script>
</body>
</html>
