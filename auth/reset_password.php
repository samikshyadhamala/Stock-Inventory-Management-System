<?php
session_start();
require "../config/db_config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =====================
   BLOCK DIRECT ACCESS
===================== */
if (!isset($_SESSION['reset_user_id'])) {
    header("Location: login.php");
    exit;
}

$conn    = getDBConnection();
$user_id = $_SESSION['reset_user_id'];
$msg     = "";

/* =====================
   RESET PASSWORD
===================== */
if (isset($_POST['reset'])) {

    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if ($password !== $confirm) {
        $msg = "Passwords do not match";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "UPDATE user_owner SET password=? WHERE user_id=?"
        );
        $stmt->bind_param("si", $hashed, $user_id);

        if ($stmt->execute()) {
            unset($_SESSION['reset_user_id']);
            header("Location: login.php?reset=success");
            exit;
        } else {
            $msg = "Password reset failed";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="login-page">

<div class="login-wrapper">
    <div class="login-card">

        <div class="login-header">
            <h1>Reset Password</h1>
            <p>Create a new password</p>
        </div>

        <div class="login-body">
            <form method="post">

                <div class="form-group password-wrapper">
                    <label class="login-label">New Password</label>
                    <input type="password" id="password"
                           name="password"
                           class="form-control login-input"
                           required>
                    <i class="bi bi-eye password-toggle"
                       onclick="togglePassword('password', this)"></i>
                </div>

                <div class="form-group password-wrapper">
                    <label class="login-label">Confirm Password</label>
                    <input type="password" id="confirm_password"
                           name="confirm_password"
                           class="form-control login-input"
                           required>
                    <i class="bi bi-eye password-toggle"
                       onclick="togglePassword('confirm_password', this)"></i>
                </div>

                <button type="submit" name="reset" class="login-btn">
                    Reset Password
                </button>

                <?php if ($msg): ?>
                    <p class="error" style="margin-top:10px;"><?= htmlspecialchars($msg) ?></p>
                <?php endif; ?>

            </form>
        </div>

    </div>
</div>

<script>
function togglePassword(id, icon) {
    const input = document.getElementById(id);
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("bi-eye", "bi-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("bi-eye-slash", "bi-eye");
    }
}
</script>

</body>
</html>
