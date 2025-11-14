<?php
require_once '../config/database.php';

// Check if user is admin
if (!is_logged_in() || !is_admin()) {
    redirect('../auth/login.php');
}

 $type = $_GET['type'] ?? '';

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="export_' . $type . '_' . date('Y-m-d') . '.csv"');

// Create a file pointer connected to the output stream
 $output = fopen('php://output', 'w');

switch ($type) {
    case 'users':
        // CSV header
        fputcsv($output, ['ID', 'Username', 'Nama Lengkap', 'Email', 'Kelas', 'Tanggal Daftar']);
        
        // Get users data
        $sql = "SELECT id, username, full_name, email, kelas, created_at FROM users WHERE role = 'siswa' ORDER BY created_at DESC";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['username'],
                $row['full_name'],
                $row['email'],
                $row['kelas'],
                date('d/m/Y', strtotime($row['created_at']))
            ]);
        }
        break;
        
    case 'works':
        // CSV header
        fputcsv($output, ['ID', 'Judul', 'Penulis', 'Kelas', 'Tipe', 'Status', 'Tanggal Unggah']);
        
        // Get works data
        $sql = "SELECT w.id, w.title, u.full_name, u.kelas, w.type, w.status, w.created_at 
                FROM works w 
                JOIN users u ON w.user_id = u.id 
                ORDER BY w.created_at DESC";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['id'],
                $row['title'],
                $row['full_name'],
                $row['kelas'],
                $row['type'],
                $row['status'],
                date('d/m/Y', strtotime($row['created_at']))
            ]);
        }
        break;
        
    case 'monthly_report':
        // CSV header
        fputcsv($output, ['Bulan', 'Jumlah Karya Diunggah', 'Jumlah Karya Disetujui', 'Jumlah Karya Ditolak']);
        
        // Get monthly statistics
        for ($i = 1; $i <= 12; $i++) {
            $monthName = date('F', mktime(0, 0, 0, $i, 1));
            
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                    FROM works 
                    WHERE MONTH(created_at) = ? AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $i);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            fputcsv($output, [
                $monthName,
                $row['total'],
                $row['approved'],
                $row['rejected']
            ]);
        }
        break;
        
    case 'top_contributors':
        // CSV header
        fputcsv($output, ['No', 'Nama', 'Kelas', 'Jumlah Karya']);
        
        // Get top contributors
        $sql = "SELECT u.full_name, u.kelas, COUNT(w.id) as work_count 
                FROM users u 
                JOIN works w ON u.id = w.user_id 
                WHERE w.status = 'approved' 
                GROUP BY u.id 
                ORDER BY work_count DESC 
                LIMIT 10";
        $result = $conn->query($sql);
        
        $no = 1;
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $no++,
                $row['full_name'],
                $row['kelas'],
                $row['work_count']
            ]);
        }
        break;
        
    case 'activities':
        // CSV header
        fputcsv($output, ['Judul', 'Penulis', 'Kelas', 'Status', 'Tanggal Update']);
        
        // Get recent activities
        $sql = "SELECT w.title, u.full_name, u.kelas, w.status, w.updated_at 
                FROM works w 
                JOIN users u ON w.user_id = u.id 
                ORDER BY w.updated_at DESC 
                LIMIT 10";
        $result = $conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            fputcsv($output, [
                $row['title'],
                $row['full_name'],
                $row['kelas'],
                $row['status'],
                date('d/m/Y H:i', strtotime($row['updated_at']))
            ]);
        }
        break;
        
    default:
        // Default export
        fputcsv($output, ['Error', 'Invalid export type']);
        break;
}

// Close the output stream
fclose($output);
exit;
?>