<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require "../config/db_config.php";

$conn = getDBConnection();

// Redirect if already logged in
if (isset($_SESSION["username"])) {
    header("Location: ../dashboard/dashboard.php");
    exit();
}

$error = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        $error = "Email and password are required";
    } else {

        $stmt = $conn->prepare(
            "SELECT user_id, username, password, status
             FROM user_owner
             WHERE email = ?
             LIMIT 1"
        );

        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            if ($user['status'] !== 'active') {
                $error = "Account not active. Please verify OTP first.";
            } elseif (password_verify($password, $user["password"])) {
                $_SESSION["user_id"]  = $user["user_id"];
                $_SESSION["username"] = $user["username"];
                header("Location: ../dashboard/dashboard.php");
                exit();
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – StockFlow IMS</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- GLOBAL DASHBOARD CSS (safe now) -->
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body class="login-page">

<div class="login-wrapper">
    <div class="login-card">

        <div class="login-header">
            <i class="bi bi-box-seam fs-1"></i>
            <h1>StockFlow IMS</h1>
            <small>Inventory Management System</small>
        </div>

        <div class="login-body">

            <h4 class="text-center mb-4">Welcome Back!</h4>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" id="loginForm">

                <div class="mb-3">
                    <label class="login-label">Email Address</label>
                    <input type="email"
                           name="email"
                           class="form-control login-input"
                           value="<?php echo htmlspecialchars($email); ?>"
                           required>
                </div>

                <div class="mb-3">
                <label class="login-label">Password</label>

                <div class="input-group">
                    <input type="password"
                        name="password"
                        id="password"
                        class="form-control login-input"
                        required>

                    <span class="input-group-text password-toggle" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </span>
                </div>
            </div>


                <button type="submit" class="login-btn">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Login
                </button>
            </form>

            <div class="login-footer">
                Don’t have an account? <a href="register.php">Register here</a>
            </div>
            <div class="login-footer">
                 <a href="forgot_passwrord.php">Forget Password ?</a>
            </div>

        </div>
    </div>
</div>

<script>
    const toggle = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const icon = toggle.querySelector('i');

    toggle.addEventListener('click', () => {
        if (password.type === 'password') {
            password.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            password.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });
</script>



</body>
</html>
