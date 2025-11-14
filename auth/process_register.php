<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = escape($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = escape($_POST['full_name']);
    $email = escape($_POST['email']);
    $kelas = escape($_POST['kelas']);
    
    // Validation
    if (empty($username) || empty($password) || empty($confirm_password) || empty($full_name) || empty($email) || empty($kelas)) {
        echo json_encode(['success' => false, 'message' => 'Semua field harus diisi!']);
        exit;
    }
    
    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'Password dan konfirmasi password tidak cocok!']);
        exit;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password harus minimal 6 karakter!']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Format email tidak valid!']);
        exit;
    }
    
    // Check if username already exists
    $sql = "SELECT id FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username sudah digunakan!']);
        exit;
    }
    
    // Check if email already exists
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email sudah digunakan!']);
        exit;
    }
    
    // Insert new user
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'siswa';
    
    $sql = "INSERT INTO users (username, password, full_name, email, role, kelas) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $username, $hashed_password, $full_name, $email, $role, $kelas);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Pendaftaran berhasil! Silakan login dengan akun Anda.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan. Silakan coba lagi.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>