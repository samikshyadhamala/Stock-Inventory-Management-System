<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "25123833_SMS"; 

function getDBConnection() {
    global $servername, $username, $password, $database;

    $conn = new mysqli($servername, $username, $password, $database);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
// Close connection
function closeDBConnection($conn) {
    if ($conn && !$conn->connect_error) {
        $conn->close();
    }
}

// Check if user is logged in
function checkLogin() {
    session_start();
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Format currency
function formatCurrency($amount) {
    return 'Rs. ' . number_format($amount, 2);
}

function formatDate($date) {
    if (empty($date) || $date === '0000-00-00') {
        return '-'; // or "N/A" if you prefer
    }
    return date('d M Y', strtotime($date));
}


// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function sanitizeInputforpurchase($data) {
    if ($data === null || $data === '') {
        return null;
    }
    return trim($data);
}

