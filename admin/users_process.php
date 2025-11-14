<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!is_logged_in() || !is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

 $action = $_POST['action'] ?? '';

if ($action === 'add') {
    $username = escape($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = escape($_POST['full_name']);
    $email = escape($_POST['email']);
    $kelas = escape($_POST['kelas']);
    
    // Check if username or email already exists
    $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username atau email sudah digunakan!']);
        exit;
    }
    
    $sql = "INSERT INTO users (username, password, full_name, email, role, kelas) VALUES (?, ?, ?, ?, 'siswa', ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $username, $password, $full_name, $email, $kelas);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambah pengguna!']);
    }
} elseif ($action === 'edit') {
    $id = (int)$_POST['id'];
    $username = escape($_POST['username']);
    $full_name = escape($_POST['full_name']);
    $email = escape($_POST['email']);
    $kelas = escape($_POST['kelas']);
    
    // Check if username or email already exists (excluding current user)
    $sql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $username, $email, $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Username atau email sudah digunakan!']);
        exit;
    }
    
    // Update user
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $sql = "UPDATE users SET username = ?, password = ?, full_name = ?, email = ?, kelas = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $username, $password, $full_name, $email, $kelas, $id);
    } else {
        $sql = "UPDATE users SET username = ?, full_name = ?, email = ?, kelas = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $username, $full_name, $email, $kelas, $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal mengupdate pengguna!']);
    }
} elseif ($action === 'get') {
    $id = (int)$_GET['id'];
    
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan!']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>