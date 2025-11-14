<?php
require_once '../config/database.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in production

try {
    if (isset($_GET['id'])) {
        // Get single work
        $id = (int)$_GET['id'];
        
        if ($id <= 0) {
            throw new Exception('Invalid work ID');
        }
        
        $sql = "SELECT w.*, u.full_name, u.kelas FROM works w 
                JOIN users u ON w.user_id = u.id 
                WHERE w.id = ? AND w.status = 'approved'";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception('Execute error: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $work = $result->fetch_assoc();
            
            // Sanitize and format data
            $response = [
                'success' => true,
                'work' => [
                    'id' => (int)$work['id'],
                    'title' => htmlspecialchars($work['title'], ENT_QUOTES, 'UTF-8'),
                    'content' => htmlspecialchars($work['content'], ENT_QUOTES, 'UTF-8'),
                    'type' => htmlspecialchars($work['type'], ENT_QUOTES, 'UTF-8'),
                    'full_name' => htmlspecialchars($work['full_name'], ENT_QUOTES, 'UTF-8'),
                    'kelas' => htmlspecialchars($work['kelas'], ENT_QUOTES, 'UTF-8'),
                    'image_url' => htmlspecialchars($work['image_url'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'source_url' => htmlspecialchars($work['source_url'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'situation' => htmlspecialchars($work['situation'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'challenge' => htmlspecialchars($work['challenge'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'action' => htmlspecialchars($work['action'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'reflection' => htmlspecialchars($work['reflection'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'created_at' => $work['created_at']
                ]
            ];
            
            echo json_encode($response);
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
                WHERE w.status = 'approved'";
        
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
                $works[] = [
                    'id' => (int)$row['id'],
                    'title' => htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8'),
                    'content' => htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8'),
                    'type' => htmlspecialchars($row['type'], ENT_QUOTES, 'UTF-8'),
                    'full_name' => htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8'),
                    'kelas' => htmlspecialchars($row['kelas'], ENT_QUOTES, 'UTF-8'),
                    'image_url' => htmlspecialchars($row['image_url'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'created_at' => $row['created_at']
                ];
            }
        }
        
        echo json_encode(['success' => true, 'works' => $works]);
    }
} catch (Exception $e) {
    // Log error
    error_log("API Error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan server. Silakan coba lagi nanti.'
    ]);
} catch (Error $e) {
    // Log error
    error_log("API Error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Terjadi kesalahan server. Silakan coba lagi nanti.'
    ]);
}
?>