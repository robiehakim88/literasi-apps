<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'ikak_umala');
define('DB_PASS', 'ikak_umala');
define('DB_NAME', 'ikak_umala');

// Create connection
 $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
 $conn->set_charset("utf8mb4");

// Start session
session_start();

// Helper functions
function escape($string) {
    global $conn;
    return $conn->real_escape_string($string);
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function is_siswa() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'siswa';
}

function get_user_id() {
    return $_SESSION['user_id'] ?? 0;
}

function get_user_name() {
    return $_SESSION['user_name'] ?? '';
}

function get_user_kelas() {
    return $_SESSION['user_kelas'] ?? '';
}

function get_setting($key) {
    global $conn;
    $sql = "SELECT setting_value FROM settings WHERE setting_key = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['setting_value'];
    }
    return '';
}
?>