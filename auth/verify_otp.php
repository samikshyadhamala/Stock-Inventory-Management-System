<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "../config/db_config.php";
require __DIR__ . '/../PHPMailer-master/src/Exception.php';
require __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* =====================
   BASIC SESSION CHECK
===================== */
if (!isset($_SESSION['otp_user_id'], $_SESSION['otp_purpose'])) {
    header("Location: login.php");
    exit;
}

$conn    = getDBConnection();
$user_id = $_SESSION['otp_user_id'];
$purpose = $_SESSION['otp_purpose']; // register | reset
$error   = "";
$success = "";

/* =====================
   LOAD ENV
===================== */
function loadEnv($file) {
    if (!file_exists($file)) return;
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v);
    }
}
loadEnv(__DIR__ . '/../.env');

/* =====================
   MAIL FUNCTION
===================== */
function sendOtpEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['GMAIL_USERNAME'];
        $mail->Password   = $_ENV['GMAIL_PASSWORD'];
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        $mail->setFrom($_ENV['GMAIL_USERNAME'], $_ENV['GMAIL_FROM_NAME']);
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'OTP Verification';
        $mail->Body    = "<p>Your OTP is <b>$otp</b>. Valid for 10 minutes.</p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("MAIL ERROR: " . $mail->ErrorInfo);
        return false;
    }
}

/* =====================
   FETCH USER EMAIL
===================== */
$stmt = $conn->prepare("SELECT email FROM user_owner WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$email = $stmt->get_result()->fetch_assoc()['email'] ?? null;

if (!$email) {
    die("User email not found.");
}

/* =====================
   AUTO SEND OTP (ON PAGE LOAD)
===================== */
if (!isset($_SESSION['otp_sent'])) {
    $otp        = random_int(100000, 999999);
    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    $stmt = $conn->prepare(
        "INSERT INTO user_otp (user_id, otp, expires_at, no_of_attempts, is_used, purpose)
         VALUES (?, ?, ?, 0, 0, ?)"
    );
    $stmt->bind_param("isss", $user_id, $otp, $expires_at, $purpose);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        if (sendOtpEmail($email, $otp)) {
            $_SESSION['otp_sent'] = true;
            $success = "OTP sent to your email.";
        } else {
            $error = "Failed to send OTP email.";
        }
    } else {
        $error = "Failed to generate OTP.";
    }
}

/* =====================
   VERIFY OTP
===================== */
if (isset($_POST['verify_otp'])) {
    $entered_otp = trim($_POST['otp']);

    $stmt = $conn->prepare(
        "SELECT id, otp, expires_at, no_of_attempts
         FROM user_otp
         WHERE user_id=? AND is_used=0 AND purpose=?
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->bind_param("is", $user_id, $purpose);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        $error = "OTP not found. Please reload.";
    } elseif ($row['no_of_attempts'] >= 5) {
        $error = "OTP locked. Please request again.";
    } elseif (strtotime($row['expires_at']) < time()) {
        $error = "OTP expired.";
    } elseif ($entered_otp != $row['otp']) {
        $stmt = $conn->prepare(
            "UPDATE user_otp SET no_of_attempts=no_of_attempts+1 WHERE id=?"
        );
        $stmt->bind_param("i", $row['id']);
        $stmt->execute();
        $error = "Incorrect OTP.";
    } else {
        // mark used
        $stmt = $conn->prepare("UPDATE user_otp SET is_used=1 WHERE id=?");
        $stmt->bind_param("i", $row['id']);
        $stmt->execute();

        unset($_SESSION['otp_sent']);

        if ($purpose === 'reset') {
            $_SESSION['reset_user_id'] = $user_id;
            unset($_SESSION['otp_user_id'], $_SESSION['otp_purpose']);
            header("Location: reset_password.php");
            exit;
        } else {
            $stmt = $conn->prepare(
                "UPDATE user_owner SET status='active' WHERE user_id=?"
            );
            $stmt->bind_param("i", $user_id);
            $stmt->execute();

            unset($_SESSION['otp_user_id'], $_SESSION['otp_purpose']);
            header("Location: reset_password.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="login-page">
<div class="login-card">
    <div class="login-header">
        <h2>Verify OTP</h2>
    </div>

    <div class="login-body">
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <?php if ($success): ?><p class="success"><?= htmlspecialchars($success) ?></p><?php endif; ?>

        <form method="POST">
            <input type="text" name="otp" class="login-input" placeholder="Enter OTP" required>
            <button type="submit" name="verify_otp" class="login-btn">Verify OTP</button>
        </form>
    </div>

    <div class="login-footer">
        &copy; 2026 StockFlow IMS
    </div>
</div>
</body>
</html>
