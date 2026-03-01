<?php
session_start();
require "../config/db_config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);


$conn = getDBConnection(); 

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT user_id FROM user_owner WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        $_SESSION['otp_user_id'] = $row['user_id'];
        $_SESSION['otp_purpose'] = 'reset'; // 🔥 THIS IS THE KEY
        header("Location: verify_otp.php");
        exit;
    }

    // Do not reveal existence
    $success = "If this email exists, an OTP has been sent.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
      <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="login-page">
<div class="login_wrapper">
    <div class="login-card" >
        <div class="login-header">
            <h2>Forgot Password</h2>
        </div>
        <div class="login-body">
            <?php if ($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
            <?php if ($success): ?><p class="success"><?= $success ?></p><?php endif; ?>

            <form method="POST">
                <input type="email" name="email" placeholder="Enter your email" class="login-input" required>
                <button type="submit" class="login-btn">Send OTP</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
