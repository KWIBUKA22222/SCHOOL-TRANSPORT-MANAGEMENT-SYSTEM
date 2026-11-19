
<?php
require_once "config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = "";
$messageType = "error";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST["full_name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if ($full_name === "" || $email === "" || $password === "" || $confirm_password === "") {
        $message = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $message = "Password must contain at least 8 characters.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();

        if ($exists) {
            $message = "This email is already registered.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)"
            );
            $stmt->bind_param("sss", $full_name, $email, $hashedPassword);

            if ($stmt->execute()) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $message = "Registration failed. Please try again.";
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - GTS Transport</title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #073b74, #0b659b);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.register-box {
    background: white;
    width: 100%;
    max-width: 450px;
    padding: 35px;
    border-radius: 14px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

h1 {
    text-align: center;
    color: #073b74;
    margin-bottom: 8px;
}

.subtitle {
    text-align: center;
    color: #64748b;
    margin-bottom: 25px;
}

label {
    display: block;
    margin: 15px 0 7px;
    font-weight: bold;
    color: #334155;
}

input {
    width: 100%;
    padding: 13px;
    border: 1px solid #cbd5e1;
    border-radius: 7px;
    font-size: 15px;
}

input:focus {
    outline: none;
    border-color: #073b74;
    box-shadow: 0 0 0 3px rgba(7,59,116,0.1);
}

button {
    width: 100%;
    margin-top: 22px;
    padding: 13px;
    background: #073b74;
    color: white;
    border: none;
    border-radius: 7px;
    font-size: 16px;
    cursor: pointer;
}

button:hover {
    background: #05284f;
}

.message {
    padding: 12px;
    border-radius: 7px;
    margin-bottom: 15px;
    background: #fee2e2;
    color: #991b1b;
}

.login-link {
    text-align: center;
    margin-top: 20px;
    color: #64748b;
}

.login-link a {
    color: #073b74;
    font-weight: bold;
    text-decoration: none;
}

.school-name {
    text-align: center;
    color: #f4b400;
    font-weight: bold;
    margin-bottom: 15px;
}
</style>
</head>

<body>

<div class="register-box">
    <div class="school-name">GIKONDO TECHNICAL SECONDARY SCHOOL</div>

    <h1>Create Account</h1>
    <p class="subtitle">School Transport Management System</p>

    <?php if ($message !== ""): ?>
        <div class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="register.php">
        <label>Full Name</label>
        <input type="text" name="full_name"
               placeholder="Enter your full name"
               value="<?= htmlspecialchars($_POST["full_name"] ?? "", ENT_QUOTES, "UTF-8") ?>"
               required>

        <label>Email Address</label>
        <input type="email" name="email"
               placeholder="Enter your email"
               value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>"
               required>

        <label>Password</label>
        <input type="password" name="password"
               placeholder="At least 8 characters"
               minlength="8" required>

        <label>Confirm Password</label>
        <input type="password" name="confirm_password"
               placeholder="Confirm your password"
               minlength="8" required>

        <button type="submit">Register</button>
    </form>

    <div class="login-link">
        Already have an account?
        <a href="login.php">Login here</a>
    </div>
</div>

</body>
</html>
