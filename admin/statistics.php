<?php
require_once '../config/database.php';

// Check if user is admin
if (!is_logged_in() || !is_admin()) {
    redirect('../auth/login.php');
}

// Get statistics
 $total_siswa = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'siswa'")->fetch_assoc()['count'];
 $total_karya = $conn->query("SELECT COUNT(*) as count FROM works")->fetch_assoc()['count'];
 $karya_pending = $conn->query("SELECT COUNT(*) as count FROM works WHERE status = 'pending'")->fetch_assoc()['count'];
 $karya_approved = $conn->query("SELECT COUNT(*) as count FROM works WHERE status = 'approved'")->fetch_assoc()['count'];
 $karya_rejected = $conn->query("SELECT COUNT(*) as count FROM works WHERE status = 'rejected'")->fetch_assoc()['count'];

// Get monthly statistics for chart (REAL DATA)
 $monthly_uploaded = [];
 $monthly_approved = [];
 $monthly_rejected = [];
 $month_labels = [];

for ($i = 1; $i <= 12; $i++) {
    // Get month name in Indonesian
    $month_labels[] = date('M', mktime(0, 0, 0, $i, 1));
    
    // Get uploaded works count for this month
    $sql = "SELECT COUNT(*) as count FROM works WHERE MONTH(created_at) = ? AND YEAR(created_at) = YEAR(CURRENT_DATE())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $i);
    $stmt->execute();
    $result = $stmt->get_result();
    $monthly_uploaded[] = $result->fetch_assoc()['count'];
    
    // Get approved works count for this month
    $sql = "SELECT COUNT(*) as count FROM works WHERE MONTH(created_at) = ? AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND status = 'approved'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $i);
    $stmt->execute();
    $result = $stmt->get_result();
    $monthly_approved[] = $result->fetch_assoc()['count'];
    
    // Get rejected works count for this month
    $sql = "SELECT COUNT(*) as count FROM works WHERE MONTH(created_at) = ? AND YEAR(created_at) = YEAR(CURRENT_DATE()) AND status = 'rejected'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $i);
    $stmt->execute();
    $result = $stmt->get_result();
    $monthly_rejected[] = $result->fetch_assoc()['count'];
}

// Get works by type
 $sql = "SELECT type, COUNT(*) as count FROM works GROUP BY type";
 $result = $conn->query($sql);
 $works_by_type = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $works_by_type[] = $row;
    }
}

// Get top contributors
 $sql = "SELECT u.full_name, u.kelas, COUNT(w.id) as work_count 
        FROM users u 
        JOIN works w ON u.id = w.user_id 
        WHERE w.status = 'approved' 
        GROUP BY u.id 
        ORDER BY work_count DESC 
        LIMIT 10";
 $result = $conn->query($sql);
 $top_contributors = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $top_contributors[] = $row;
    }
}

// Get recent activities
 $sql = "SELECT w.*, u.full_name, u.kelas 
        FROM works w 
        JOIN users u ON w.user_id = u.id 
        ORDER BY w.updated_at DESC 
        LIMIT 10";
 $result = $conn->query($sql);
 $recent_activities = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $recent_activities[] = $row;
    }
}

// Get weekly statistics for alternative view
 $weekly_stats = [];
for ($i = 0; $i < 4; $i++) {
    $week_start = date('Y-m-d', strtotime("-" . ($i * 7) . " days sunday"));
    $week_end = date('Y-m-d', strtotime("-" . (($i * 7) - 6) . " days sunday"));
    
    $sql = "SELECT COUNT(*) as count FROM works WHERE DATE(created_at) BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $week_start, $week_end);
    $stmt->execute();
    $result = $stmt->get_result();
    $weekly_stats[] = $result->fetch_assoc()['count'];
}
 $weekly_stats = array_reverse($weekly_stats);

// Get daily statistics for last 7 days
 $daily_stats = [];
 $daily_labels = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $daily_labels[] = date('D', strtotime($date));
    
    $sql = "SELECT COUNT(*) as count FROM works WHERE DATE(created_at) = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $daily_stats[] = $result->fetch_assoc()['count'];
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan & Statistik - Admin Panel</title>
    
   <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }
        
        /* Mobile Navigation Bar */
        .mobile-navbar {
            display: none;
            background: linear-gradient(135deg, #4361ee, #3f37c9);
            color: white;
            padding: 15px 20px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .mobile-navbar .navbar-brand {
            color: white !important;
            font-weight: 600;
            font-size: 1.2rem;
            text-decoration: none;
        }
        
        .mobile-menu-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .mobile-menu-toggle:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .mobile-user-dropdown {
            position: relative;
        }
        
        .mobile-user-dropdown .dropdown-toggle {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .mobile-user-dropdown .dropdown-toggle:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .mobile-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            min-width: 200px;
            display: none;
            z-index: 1031;
        }
        
        .mobile-dropdown-menu.show {
            display: block;
        }
        
        .mobile-dropdown-menu .dropdown-item {
            color: #333;
            padding: 10px 15px;
            transition: background-color 0.3s;
        }
        
        .mobile-dropdown-menu .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #4361ee;
        }
        
        /* Sidebar Styles */
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #4361ee, #3f37c9);
            color: white;
            position: fixed;
            width: 250px;
            z-index: 1020;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 0;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .sidebar .nav-link span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }
        
        /* Desktop Header */
        .desktop-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding: 0 5px;
        }
        
        .desktop-header h2 {
            margin: 0;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .user-dropdown {
            position: relative;
        }
        
        .user-dropdown .dropdown-toggle {
            background-color: white;
            border: 1px solid #e0e0e0;
            color: #333;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .user-dropdown .dropdown-toggle:hover {
            border-color: #4361ee;
            box-shadow: 0 3px 10px rgba(67, 97, 238, 0.2);
        }
        
        /* Stat Cards */
        .stat-card {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover .stat-icon {
            transform: scale(1.1);
        }
        
        .stat-icon.blue { background-color: rgba(67, 97, 238, 0.1); color: #4361ee; }
        .stat-icon.green { background-color: rgba(40, 167, 69, 0.1); color: #28a745; }
        .stat-icon.orange { background-color: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .stat-icon.red { background-color: rgba(220, 53, 69, 0.1); color: #dc3545; }
        
        /* Cards */
        .card {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        }
        
        .card-header {
            background-color: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 15px 20px;
            font-weight: 600;
            border-radius: 15px 15px 0 0 !important;
        }
        
        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        
        /* Table */
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #6c757d;
            font-size: 0.85rem;
            text-transform: uppercase;
            background-color: #f8f9fa;
        }
        
        .badge {
            font-size: 0.75rem;
            padding: 5px 10px;
            border-radius: 6px;
        }
        
        /* Overlay for mobile sidebar */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1015;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }
        
        /* Responsive Breakpoints */
        @media (min-width: 992px) {
            .mobile-navbar {
                display: none !important;
            }
            
            .sidebar {
                transform: translateX(0) !important;
            }
            
            .main-content {
                margin-left: 250px;
                padding-top: 20px;
            }
            
            .sidebar-overlay {
                display: none !important;
            }
        }
        
        @media (max-width: 991px) {
            .mobile-navbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            
            .sidebar {
                transform: translateX(-100%);
                z-index: 1025;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding-top: 80px;
            }
            
            .desktop-header {
                display: none;
            }
            
            .stat-card {
                margin-bottom: 15px;
                padding: 15px;
            }
            
            .chart-container {
                height: 250px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 15px;
                padding-top: 80px;
            }
            
            .stat-card {
                padding: 12px;
                margin-bottom: 12px;
            }
            
            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 20px;
                margin-bottom: 10px;
            }
            
            .stat-card h3 {
                font-size: 1.5rem;
                margin-bottom: 2px;
            }
            
            .stat-card p {
                font-size: 0.85rem;
                margin-bottom: 0;
            }
            
            .card {
                margin-bottom: 15px;
            }
            
            .card-header {
                padding: 12px 15px;
                font-size: 1rem;
            }
            
            .chart-container {
                height: 200px;
            }
            
            .table {
                font-size: 0.85rem;
            }
            
            .table th {
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 10px;
                padding-top: 80px;
            }
            
            .stat-card {
                padding: 10px;
                margin-bottom: 10px;
            }
            
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 18px;
                margin-bottom: 8px;
            }
            
            .stat-card h3 {
                font-size: 1.25rem;
            }
            
            .stat-card p {
                font-size: 0.8rem;
            }
            
            .card {
                margin-bottom: 12px;
            }
            
            .card-header {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
            
            .chart-container {
                height: 180px;
            }
            
            .mobile-navbar {
                padding: 12px 15px;
            }
            
            .mobile-navbar .navbar-brand {
                font-size: 1rem;
            }
            
            .mobile-menu-toggle {
                width: 35px;
                height: 35px;
                font-size: 1.2rem;
            }
            
            .mobile-user-dropdown .dropdown-toggle {
                font-size: 0.8rem;
                padding: 5px 10px;
            }
        }
        
        /* Smooth transitions */
        * {
            -webkit-tap-highlight-color: transparent;
        }
        
        /* Touch-friendly */
        @media (max-width: 991px) {
            .sidebar .nav-link {
                padding: 15px 20px;
                font-size: 1rem;
            }
            
            .sidebar .nav-link i {
                width: 24px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Navigation Bar -->
    <nav class="mobile-navbar">
        <div class="d-flex align-items-center">
            <button class="mobile-menu-toggle me-3" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-speedometer2 me-2"></i>Dashboard
            </a>
        </div>
        <div class="mobile-user-dropdown">
            <button class="dropdown-toggle" type="button" onclick="toggleMobileDropdown()">
                <i class="bi bi-person-circle me-1"></i>
                <span><?php echo substr(get_user_name(), 0, 12); ?></span>
                <i class="bi bi-chevron-down ms-1"></i>
            </button>
            <div class="mobile-dropdown-menu" id="mobileDropdownMenu">
                <a class="dropdown-item" href="#">
                    <i class="bi bi-person me-2"></i>Profil
                </a>
                <a class="dropdown-item" href="../auth/logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i>Keluar
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" onclick="closeSidebar()"></div>
    
    <!-- Desktop Sidebar -->
    <div class="sidebar" id="sidebar">
          <div class="p-3">
            <h4 class="mb-4">Admin Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
               
                <li class="nav-item">
                    <a class="nav-link" href="works.php">
                        <i class="bi bi-file-earmark-text"></i>
                        <span>Kelola Literasi</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="bi bi-people"></i>
                        <span>Kelola Siswa</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="statistics.php">
                        <i class="bi bi-bar-chart"></i>
                        <span>Statistik</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <i class="bi bi-gear"></i>
                        <span>Pengaturan</span>
                    </a>
                </li>
               
                <li class="nav-item mt-4">
                    <a class="nav-link" href="../index.php">
                        <i class="bi bi-house"></i>
                        <span>Lihat Situs</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../auth/logout.php">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Keluar</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Laporan & Statistik</h2>
            
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $total_siswa; ?></h3>
                    <p class="text-muted mb-0">Total Siswa</p>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> Terdaftar</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="bi bi-book-fill"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $total_karya; ?></h3>
                    <p class="text-muted mb-0">Total Literasi</p>
                    <small class="text-success"><i class="bi bi-arrow-up"></i> Diunggah</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $karya_pending; ?></h3>
                    <p class="text-muted mb-0">Menunggu Persetujuan</p>
                    <small class="text-muted"><i class="bi bi-clock"></i> Perlu dicek</small>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <h3 class="mb-1"><?php echo $karya_rejected; ?></h3>
                    <p class="text-muted mb-0">Ditolak</p>
                    <small class="text-danger"><i class="bi bi-arrow-down"></i> Tidak disetujui</small>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-3">
                <div class="chart-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5>Statistik Pengunggahan Literasi</h5>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary active" id="monthlyBtn">Bulanan</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="weeklyBtn">Mingguan</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="dailyBtn">Harian</button>
                        </div>
                    </div>
                    <canvas id="submissionChart"></canvas>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="chart-container">
                    <h5 class="mb-3">Distribusi Jenis Literasi</h5>
                    <canvas id="typeChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Tables Row -->
        <div class="row">
            <div class="col-lg-8 mb-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Kontributor Teratas</h5>
                        <button class="btn export-btn btn-sm" onclick="exportTopContributors()">
                            <i class="bi bi-download me-1"></i>Export
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Kelas</th>
                                        <th>Jumlah Literasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; ?>
                                    <?php foreach ($top_contributors as $contributor): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td><?php echo $contributor['full_name']; ?></td>
                                        <td><?php echo $contributor['kelas']; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="me-2"><?php echo $contributor['work_count']; ?></span>
                                                <div class="progress" style="width: 100px; height: 10px;">
                                                    <div class="progress-bar" role="progressbar" style="width: <?php echo round(($contributor['work_count'] / $total_karya) * 100); ?>%; background-color: #4361ee;" aria-valuenow="<?php echo $contributor['work_count']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $total_karya; ?>"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Aktivitas Terbaru</h5>
                        <button class="btn export-btn btn-sm" onclick="exportActivities()">
                            <i class="bi bi-download me-1"></i>Export
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <?php foreach ($recent_activities as $activity): ?>
                            <div class="activity-item">
                                <div class="d-flex align-items-center">
                                    <span class="activity-badge <?php echo $activity['status']; ?>"></span>
                                    <div class="flex-grow-1">
                                        <p class="mb-1"><?php echo $activity['title']; ?></p>
                                        <small class="text-muted">
                                            <?php echo $activity['full_name']; ?> - <?php echo $activity['kelas']; ?>
                                        </small>
                                    </div>
                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($activity['updated_at'])); ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Export Options -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Export Data</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <h6>Export Data Siswa</h6>
                        <p class="text-muted small">Unduh data semua siswa yang terdaftar</p>
                        <button class="btn export-btn" onclick="exportUsers()">
                            <i class="bi bi-download me-1"></i>Export Siswa
                        </button>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6>Export Data Literasi</h6>
                        <p class="text-muted small">Unduh data semua Literasi yang telah diunggah</p>
                        <button class="btn export-btn" onclick="exportWorks()">
                            <i class="bi bi-download me-1"></i>Export Literasi
                        </button>
                    </div>
                    <div class="col-md-4 mb-3">
                        <h6>Export Laporan Bulanan</h6>
                        <p class="text-muted small">Unduh laporan statistik bulanan</p>
                        <button class="btn export-btn" onclick="exportMonthlyReport()">
                            <i class="bi bi-download me-1"></i>Export Laporan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Data from PHP
        const monthLabels = <?php echo json_encode($month_labels); ?>;
        const monthlyUploaded = <?php echo json_encode($monthly_uploaded); ?>;
        const monthlyApproved = <?php echo json_encode($monthly_approved); ?>;
        const monthlyRejected = <?php echo json_encode($monthly_rejected); ?>;
        const weeklyStats = <?php echo json_encode($weekly_stats); ?>;
        const dailyLabels = <?php echo json_encode($daily_labels); ?>;
        const dailyStats = <?php echo json_encode($daily_stats); ?>;
        
        // Monthly submission chart
        const ctx = document.getElementById('submissionChart').getContext('2d');
        let submissionChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthLabels,
                datasets: [{
                    label: 'Literasi Diunggah',
                    data: monthlyUploaded,
                    borderColor: '#4361ee',
                    backgroundColor: 'rgba(67, 97, 238, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Literasi Disetujui',
                    data: monthlyApproved,
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Literasi Ditolak',
                    data: monthlyRejected,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        
        // Type distribution chart
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        const typeChart = new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map('ucfirst', array_column($works_by_type, 'type'))); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($works_by_type, 'count')); ?>,
                    backgroundColor: [
                        '#4361ee',
                        '#f72585',
                        '#4cc9f0',
                        '#7209b7',
                        '#3a0ca3',
                        '#f72585'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Export functions
        function exportUsers() {
            window.location.href = 'export.php?type=users';
        }
        
        function exportWorks() {
            window.location.href = 'export.php?type=works';
        }
        
        function exportMonthlyReport() {
            window.location.href = 'export.php?type=monthly_report';
        }
        
        function exportTopContributors() {
            window.location.href = 'export.php?type=top_contributors';
        }
        
        function exportActivities() {
            window.location.href = 'export.php?type=activities';
        }
        
        // Chart period buttons
        document.getElementById('monthlyBtn').addEventListener('click', function() {
            updateChartPeriod('monthly');
        });
        
        document.getElementById('weeklyBtn').addEventListener('click', function() {
            updateChartPeriod('weekly');
        });
        
        document.getElementById('dailyBtn').addEventListener('click', function() {
            updateChartPeriod('daily');
        });
        
        function updateChartPeriod(period) {
            // Update active button
            document.querySelectorAll('.btn-group .btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById(period + 'Btn').classList.add('active');
            
            // Update chart data based on period
            let newLabels, newData1, newData2, newData3;
            
            switch(period) {
                case 'weekly':
                    newLabels = ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'];
                    newData1 = weeklyStats;
                    newData2 = weeklyStats.map(v => Math.round(v * 0.8)); // Approximate approved
                    newData3 = weeklyStats.map(v => Math.round(v * 0.1)); // Approximate rejected
                    break;
                case 'daily':
                    newLabels = dailyLabels;
                    newData1 = dailyStats;
                    newData2 = dailyStats.map(v => Math.round(v * 0.8)); // Approximate approved
                    newData3 = dailyStats.map(v => Math.round(v * 0.1)); // Approximate rejected
                    break;
                default: // monthly
                    newLabels = monthLabels;
                    newData1 = monthlyUploaded;
                    newData2 = monthlyApproved;
                    newData3 = monthlyRejected;
            }
            
            // Update chart
            submissionChart.data.labels = newLabels;
            submissionChart.data.datasets[0].data = newData1;
            submissionChart.data.datasets[1].data = newData2;
            submissionChart.data.datasets[2].data = newData3;
            submissionChart.update();
        }
    </script>
    
     <script>
        // Mobile sidebar functions
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            
            // Prevent body scroll when sidebar is open
            if (sidebar.classList.contains('show')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }
        
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
        
        // Mobile dropdown function
        function toggleMobileDropdown() {
            const dropdown = document.getElementById('mobileDropdownMenu');
            dropdown.classList.toggle('show');
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function closeDropdown(e) {
                if (!e.target.closest('.mobile-user-dropdown')) {
                    dropdown.classList.remove('show');
                    document.removeEventListener('click', closeDropdown);
                }
            });
        }
        
        // Initialize charts
        document.addEventListener('DOMContentLoaded', function() {
            const isMobile = window.innerWidth < 992;
            
            // Monthly Uploads Chart
            const uploadsCtx = document.getElementById('uploadsChart').getContext('2d');
            const uploadsData = <?php echo json_encode($monthly_uploads); ?>;
            
            new Chart(uploadsCtx, {
                type: 'line',
                data: {
                    labels: uploadsData.map(item => item.month),
                    datasets: [{
                        label: 'Jumlah Pengunggahan',
                        data: uploadsData.map(item => item.count),
                        borderColor: '#4361ee',
                        backgroundColor: 'rgba(67, 97, 238, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#4361ee',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: isMobile ? 4 : 6,
                        pointHoverRadius: isMobile ? 6 : 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: { size: isMobile ? 12 : 14 },
                            bodyFont: { size: isMobile ? 11 : 13 },
                            callbacks: {
                                label: function(context) {
                                    return 'Pengunggahan: ' + context.parsed.y + ' karya';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            ticks: {
                                stepSize: 1,
                                font: { size: isMobile ? 10 : 12 }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { size: isMobile ? 10 : 12 },
                                maxRotation: isMobile ? 45 : 0,
                                minRotation: isMobile ? 45 : 0
                            }
                        }
                    }
                }
            });
            
            // Status Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusData = <?php echo json_encode($status_data); ?>;
            
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: statusData.map(item => item.status),
                    datasets: [{
                        data: statusData.map(item => item.count),
                        backgroundColor: statusData.map(item => item.color),
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: isMobile ? 10 : 15,
                                font: { size: isMobile ? 10 : 12 }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: isMobile ? 8 : 12,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
            
            // Type Chart
            const typeCtx = document.getElementById('typeChart').getContext('2d');
            const typeData = <?php echo json_encode($works_by_type); ?>;
            const typeColors = ['#4361ee', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14'];
            
            new Chart(typeCtx, {
                type: 'bar',
                data: {
                    labels: typeData.map(item => item.type.charAt(0).toUpperCase() + item.type.slice(1)),
                    datasets: [{
                        label: 'Jumlah Karya',
                        data: typeData.map(item => item.count),
                        backgroundColor: typeColors,
                        borderRadius: 8,
                        barThickness: isMobile ? 20 : 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: isMobile ? 8 : 12,
                            callbacks: {
                                label: function(context) {
                                    return 'Jumlah: ' + context.parsed.y + ' karya';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            ticks: {
                                stepSize: 1,
                                font: { size: isMobile ? 10 : 12 }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: {
                                font: { size: isMobile ? 10 : 12 },
                                maxRotation: isMobile ? 45 : 0,
                                minRotation: isMobile ? 45 : 0
                            }
                        }
                    }
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                closeSidebar();
            }
        });
        
        // Handle escape key to close sidebar
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSidebar();
                const dropdown = document.getElementById('mobileDropdownMenu');
                if (dropdown) {
                    dropdown.classList.remove('show');
                }
            }
        });
    </script>
</body>
</html>