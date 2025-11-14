<?php
require_once '../config/database.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('../auth/login.php');
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $work_id = (int)$_GET['id'];
    $user_id = get_user_id();
    
    // Check if work belongs to user
    $sql = "SELECT id FROM works WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $work_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Delete work
        $sql = "DELETE FROM works WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $work_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Karya berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus karya']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Karya tidak ditemukan']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>