
<?php
require_once "config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

$message = "";

if (isset($_GET["registered"])) {
    $message = "Account created successfully. Please login.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email === "" || $password === "") {
        $message = "Please enter your email and password.";
    } else {
        $stmt = $conn->prepare(
            "SELECT user_id, full_name, email, password FROM users WHERE email = ?"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user["password"])) {
            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["email"] = $user["email"];

            header("Location: dashboard.php");
            exit;
        } else {
            $message = "Incorrect email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - GTS Transport</title>

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

.login-box {
    background: white;
    width: 100%;
    max-width: 420px;
    padding: 35px;
    border-radius: 14px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

.school-name {
    text-align: center;
    color: #f4b400;
    font-weight: bold;
    margin-bottom: 15px;
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
    background: #e0f2fe;
    color: #075985;
}

.error {
    background: #fee2e2;
    color: #991b1b;
}

.register-link {
    text-align: center;
    margin-top: 20px;
    color: #64748b;
}

.register-link a {
    color: #073b74;
    font-weight: bold;
    text-decoration: none;
}
</style>
</head>

<body>

<div class="login-box">
    <div class="school-name">GIKONDO TECHNICAL SECONDARY SCHOOL</div>

    <h1>Welcome Back</h1>
    <p class="subtitle">Login to School Transport System</p>

    <?php if ($message !== ""): ?>
        <div class="message <?= isset($_GET["registered"]) ? "" : "error" ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <label>Email Address</label>
        <input type="email" name="email"
               placeholder="Enter your email"
               value="<?= htmlspecialchars($_POST["email"] ?? "", ENT_QUOTES, "UTF-8") ?>"
               required>

        <label>Password</label>
        <input type="password" name="password"
               placeholder="Enter your password"
               required>

        <button type="submit">Login</button>
    </form>

    <div class="register-link">
        Don't have an account?
        <a href="register.php">Register here</a>
    </div>
</div>

</body>
</html>
