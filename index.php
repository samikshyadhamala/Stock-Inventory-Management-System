<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


?>


<!DOCTYPE html>
<html>
<head>
    <title>Welcome</title>
    <link rel="stylesheet" href="assets/style.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        document.getElementById("hamburger").onclick = function () {
            document.querySelector(".sidebar").classList.toggle("show");
        };
    </script>




</head>
<body class="login-page" style=");
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            margin: 0;">
    <div class="login-card">
        <div class="login-header">
            <p class="welcome-text">Welcome to</p>
            <h1>StockFlow IMS</h1>

        </div>
        <div class="login-body" style="padding: 35px 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;">
            <button class="login-btn" onclick="location.href='auth/register.php'">Register</button>
            <button class="login-btn" onclick="location.href='auth/login.php'">Login</button>
        </div>
        <div class="login-footer">
            &copy; 2026 StockFlow IMS
        </div>
    </div>
</body>
</html>



