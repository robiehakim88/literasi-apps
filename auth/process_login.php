<?php
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = escape($_POST['username']);
    $password = $_POST['password'];
    
    // Check user
    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_kelas'] = $user['kelas'];
            $_SESSION['user_role'] = $user['role'];
            
            // Return success response
            echo json_encode(['success' => true, 'role' => $user['role']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Password salah!']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Username tidak ditemukan!']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>