<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require "../config/db_config.php";

// If user already logged in, do not allow register page
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/dashboard.php");
    exit;
}

$conn = getDBConnection();

$error = "";
$username = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username  = trim($_POST["username"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $password  = $_POST["password"] ?? "";
    $password1 = $_POST["password1"] ?? "";

    // ===== VALIDATION =====
    if ($username === "" || $email === "" || $password === "" || $password1 === "") {
        $error = "All fields are required";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email";
    }
    elseif ($password !== $password1) {
        $error = "Passwords do not match";
    }
    elseif (!preg_match("/^[A-Z]/", $password)) {
        $error = "Password must start with a capital letter";
    }
    elseif (!preg_match("/[\W_]/", $password)) {
        $error = "Password must include at least one special character";
    }
    elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters";
    }

    if ($error === "") {

        // Check duplicate username or email
        $check = $conn->prepare(
            "SELECT user_id FROM user_owner WHERE email = ? OR username = ?"
        );
        $check->bind_param("ss", $email, $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Username or Email already registered";
        } 
        else {

            // Insert user
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = "user";
            $status = "inactive";

            $stmt = $conn->prepare(
                "INSERT INTO user_owner (username, password, email, role, status)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("sssss", $username, $hash, $email, $role, $status);
            $stmt->execute();

            $user_id = $stmt->insert_id;

            // Generate OTP
            $otp = random_int(100000, 999999);
            $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));

            $otp_stmt = $conn->prepare(
                "INSERT INTO user_otp (user_id, otp, expires_at, is_used)
                 VALUES (?, ?, ?, 0)"
            );
            $otp_stmt->bind_param("iss", $user_id, $otp, $expiry);
            $otp_stmt->execute();

            // Save OTP user session
            $_SESSION['otp_user_id'] = $user_id;

            header("Location: verify_otp.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register – StockFlow IMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- SAME CSS AS LOGIN -->
    <link rel="stylesheet" href="../assets/style.css">
</head>

<body class="login-page">

<div class="login-wrapper">
    <div class="login-card">

        <!-- HEADER -->
        <div class="login-header">
            <i class="bi bi-box-seam fs-1"></i>
            <h1>StockFlow IMS</h1>
            <small>Inventory Management System</small>
        </div>

        <!-- BODY -->
        <div class="login-body">
            <h4 class="text-center mb-4">Create Account</h4>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <!-- USERNAME -->
                <div class="mb-3">
                    <label class="login-label">Username</label>
                    <input type="text"
                           name="username"
                           class="form-control login-input"
                           value="<?php echo htmlspecialchars($username); ?>"
                           required>
                </div>

                <!-- EMAIL -->
                <div class="mb-3">
                    <label class="login-label">Email Address</label>
                    <input type="email"
                           name="email"
                           class="form-control login-input"
                           value="<?php echo htmlspecialchars($email); ?>"
                           required>
                </div>

                <!-- PASSWORD -->
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

                <!-- CONFIRM PASSWORD -->
                <div class="mb-4">
                    <label class="login-label">Confirm Password</label>
                    <div class="input-group">
                        <input type="password"
                               name="password1"
                               id="password1"
                               class="form-control login-input"
                               required>
                        <span class="input-group-text password-toggle" id="togglePassword2">
                            <i class="bi bi-eye"></i>
                        </span>
                    </div>
                </div>

                <!-- BUTTON -->
                <button type="submit" class="login-btn">
                    <i class="bi bi-person-plus me-2"></i> Register
                </button>
            </form>

            <!-- FOOTER -->
            <div class="login-footer">
                Already registered? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script>
    function toggleEye(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        const icon = toggle.querySelector('i');

        toggle.addEventListener('click', () => {
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    }

    toggleEye('togglePassword', 'password');
    toggleEye('togglePassword2', 'password1');
</script>

</body>
</html>

