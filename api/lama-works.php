<?php
require_once '../config/database.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_GET['id'])) {
    // Get single work
    $id = (int)$_GET['id'];
    
    // Modified query to include all works (not only approved) for admin
    $sql = "SELECT w.*, u.full_name, u.kelas FROM works w 
            JOIN users u ON w.user_id = u.id 
            WHERE w.id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Execute error: ' . $stmt->error]);
        exit;
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $work = $result->fetch_assoc();
        
        // Sanitize and format data
        $work['title'] = htmlspecialchars($work['title']);
        $work['content'] = htmlspecialchars($work['content']);
        $work['full_name'] = htmlspecialchars($work['full_name']);
        $work['kelas'] = htmlspecialchars($work['kelas']);
        $work['situation'] = htmlspecialchars($work['situation'] ?? '');
        $work['challenge'] = htmlspecialchars($work['challenge'] ?? '');
        $work['action'] = htmlspecialchars($work['action'] ?? '');
        $work['reflection'] = htmlspecialchars($work['reflection'] ?? '');
        $work['source_url'] = htmlspecialchars($work['source_url'] ?? '');
        
        echo json_encode(['success' => true, 'work' => $work]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Karya tidak ditemukan']);
    }
} else {
    // Get all works with optional filters
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 0;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $sql = "SELECT w.*, u.full_name, u.kelas FROM works w 
            JOIN users u ON w.user_id = u.id 
            WHERE 1=1";
    
    $params = [];
    $types = '';
    
    if (!empty($type) && $type !== 'all') {
        $sql .= " AND w.type = ?";
        $params[] = $type;
        $types .= 's';
    }
    
    if (!empty($search)) {
        $sql .= " AND (w.title LIKE ? OR u.full_name LIKE ? OR u.kelas LIKE ?)";
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= 'sss';
    }
    
    $sql .= " ORDER BY w.created_at DESC";
    
    if ($limit > 0) {
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $works = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Clean up content for display
            $row['content'] = htmlspecialchars($row['content']);
            $row['title'] = htmlspecialchars($row['title']);
            $row['full_name'] = htmlspecialchars($row['full_name']);
            $row['kelas'] = htmlspecialchars($row['kelas']);
            $works[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'works' => $works]);
}
?>