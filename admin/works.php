<?php
require_once '../config/database.php';

// Check if user is admin
if (!is_logged_in() || !is_admin()) {
    redirect('../auth/login.php');
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $work_id = (int)$_POST['work_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        $sql = "UPDATE works SET status = 'approved' WHERE id = ?";
    } elseif ($action === 'reject') {
        $sql = "UPDATE works SET status = 'rejected' WHERE id = ?";
    } elseif ($action === 'delete') {
        $sql = "DELETE FROM works WHERE id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $work_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = $action === 'delete' ? 'Karya berhasil dihapus!' : 'Status karya berhasil diperbarui!';
    } else {
        $_SESSION['error'] = 'Gagal memperbarui status karya!';
    }
    
    redirect('works.php');
}

// Get filter parameters
 $status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
 $type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
 $search_filter = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
 $sql = "SELECT w.*, u.full_name, u.kelas FROM works w 
        JOIN users u ON w.user_id = u.id 
        WHERE 1=1";
 $params = [];
 $types = '';

if ($status_filter !== 'all') {
    $sql .= " AND w.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($type_filter !== 'all') {
    $sql .= " AND w.type = ?";
    $params[] = $type_filter;
    $types .= 's';
}

if (!empty($search_filter)) {
    $sql .= " AND (w.title LIKE ? OR u.full_name LIKE ? OR u.kelas LIKE ?)";
    $search_term = "%$search_filter%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

 $sql .= " ORDER BY w.created_at DESC";

 $stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
 $stmt->execute();
 $result = $stmt->get_result();

 $works = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $works[] = $row;
    }
}

// Get counts for filters
 $total_count = $conn->query("SELECT COUNT(*) as count FROM works")->fetch_assoc()['count'];
 $pending_count = $conn->query("SELECT COUNT(*) as count FROM works WHERE status = 'pending'")->fetch_assoc()['count'];
 $approved_count = $conn->query("SELECT COUNT(*) as count FROM works WHERE status = 'approved'")->fetch_assoc()['count'];
 $rejected_count = $conn->query("SELECT COUNT(*) as count FROM works WHERE status = 'rejected'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Karya - Admin</title>
    
  
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
        <!-- Desktop Header -->
        <div class="desktop-header">
            <h2>Kelola Karya Siswa</h2>
            <div class="user-dropdown">
                <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i><?php echo get_user_name(); ?>
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">
                        <i class="bi bi-person me-2"></i>Profil
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../auth/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>Keluar
                    </a></li>
                </ul>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="works.php">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Menunggu (<?php echo $pending_count; ?>)</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Disetujui (<?php echo $approved_count; ?>)</option>
                            <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Ditolak (<?php echo $rejected_count; ?>)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Tipe Karya</label>
                        <select class="form-select" name="type">
                            <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>Semua Tipe</option>
                            <option value="puisi" <?php echo $type_filter === 'puisi' ? 'selected' : ''; ?>>Puisi</option>
                            <option value="cerpen" <?php echo $type_filter === 'cerpen' ? 'selected' : ''; ?>>Cerpen</option>
                            <option value="opini" <?php echo $type_filter === 'opini' ? 'selected' : ''; ?>>Opini</option>
                            <option value="resensi" <?php echo $type_filter === 'resensi' ? 'selected' : ''; ?>>Resensi</option>
                            <option value="pantun" <?php echo $type_filter === 'pantun' ? 'selected' : ''; ?>>Pantun</option>
                            <option value="poster" <?php echo $type_filter === 'poster' ? 'selected' : ''; ?>>Visual Poster</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Pencarian</label>
                        <input type="text" class="form-control" name="search" placeholder="Cari judul, penulis, atau kelas..." value="<?php echo htmlspecialchars($search_filter); ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i>Filter
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Works Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
                <h5 class="mb-0">Daftar Karya (<?php echo count($works); ?> karya)</h5>
                <div class="mt-2 mt-md-0">
                    <button class="btn btn-sm btn-success" onclick="bulkApprove()">
                        <i class="bi bi-check-circle me-1"></i>Setujui Terpilih
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="bulkReject()">
                        <i class="bi bi-x-circle me-1"></i>Tolak Terpilih
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (count($works) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" class="form-check-input" id="selectAll">
                                    </th>
                                    <th>Thumbnail</th>
                                    <th>Informasi Karya</th>
                                    <th>Penulis</th>
                                    <th>Tipe</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($works as $work): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input work-checkbox" value="<?php echo $work['id']; ?>">
                                    </td>
                                    <td>
                                        <img src="<?php echo $work['image_url'] ?: 'https://picsum.photos/seed/' . $work['id'] . '/100/100.jpg'; ?>" 
                                             alt="<?php echo $work['title']; ?>" 
                                             class="work-thumbnail">
                                    </td>
                                    <td>
                                        <strong><?php echo $work['title']; ?></strong>
                                        <?php if (!empty($work['source_url'])): ?>
                                            <br><small class="text-muted">
                                                <i class="bi bi-link-45deg"></i> 
                                                <a href="<?php echo $work['source_url']; ?>" target="_blank">Sumber</a>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" 
                                                 style="width: 30px; height: 30px; font-size: 12px;">
                                                <?php echo strtoupper(substr($work['full_name'], 0, 2)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-medium"><?php echo $work['full_name']; ?></div>
                                                <small class="text-muted d-none d-md-block"><?php echo $work['kelas']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo get_type_color($work['type']); ?>">
                                            <?php echo ucfirst($work['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($work['status'] === 'approved'): ?>
                                            <span class="badge bg-success">Disetujui</span>
                                        <?php elseif ($work['status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Menunggu</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Ditolak</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($work['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="viewWorkDetail(<?php echo $work['id']; ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if ($work['status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success" onclick="updateStatus(<?php echo $work['id']; ?>, 'approve')">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="updateStatus(<?php echo $work['id']; ?>, 'reject')">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteWork(<?php echo $work['id']; ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-book text-muted" style="font-size: 4rem;"></i>
                        <h5 class="mt-3">Tidak ada karya ditemukan</h5>
                        <p class="text-muted">Coba ubah filter atau kata kunci pencarian Anda.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Work Detail Modal -->
    <div class="modal fade" id="workDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Karya</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="workDetailContent">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Memuat...</span>
                            </div>
                            <p class="mt-3">Memuat detail karya...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <div id="modalActions"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
        
        // View work detail
        function viewWorkDetail(id) {
            const modal = new bootstrap.Modal(document.getElementById('workDetailModal'));
            const contentDiv = document.getElementById('workDetailContent');
            const actionsDiv = document.getElementById('modalActions');
            
            // Show loading state
            contentDiv.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Memuat...</span>
                    </div>
                    <p class="mt-3">Memuat detail karya...</p>
                </div>
            `;
            actionsDiv.innerHTML = '';
            
            modal.show();
            
            // Fetch work detail
            fetch(`../api/works.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const work = data.work;
                        
                        // Update modal content
                        contentDiv.innerHTML = `
                            <div class="row">
                                <div class="col-md-4">
                                    <img src="${work.image_url || 'https://picsum.photos/seed/' + work.id + '/400/300.jpg'}" 
                                         class="img-fluid rounded" alt="${work.title}">
                                </div>
                                <div class="col-md-8">
                                    <h4>${work.title}</h4>
                                    <div class="row mb-3">
                                        <div class="col-sm-6">
                                            <p><strong>Penulis:</strong> ${work.full_name}</p>
                                            <p><strong>Kelas:</strong> ${work.kelas}</p>
                                            <p><strong>Tipe:</strong> ${work.type.charAt(0).toUpperCase() + work.type.slice(1)}</p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Status:</strong> 
                                                <span class="badge bg-${getStatusColor(work.status)}">
                                                    ${work.status === 'approved' ? 'Disetujui' : work.status === 'pending' ? 'Menunggu' : 'Ditolak'}
                                                </span>
                                            </p>
                                            <p><strong>Tanggal:</strong> ${new Date(work.created_at).toLocaleDateString('id-ID')}</p>
                                            <p><strong>Sumber:</strong> ${work.source_url || '-'}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h5>Konten Karya</h5>
                                <div style="white-space: pre-line; background-color: #f8f9fa; padding: 15px; border-radius: 8px; max-height: 200px; overflow-y: auto;">
                                    ${work.content}
                                </div>
                            </div>
                            
                            <div class="star-method mt-4">
                                <h5>Metode STAR (Situasi, Tantangan, Aksi, Refleksi)</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <strong>(S) Situasi:</strong>
                                            <div style="white-space: pre-line; background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 5px;">
                                                ${work.situation || '-'}
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <strong>(T) Tantangan:</strong>
                                            <div style="white-space: pre-line; background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 5px;">
                                                ${work.challenge || '-'}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <strong>(A) Aksi:</strong>
                                            <div style="white-space: pre-line; background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 5px;">
                                                ${work.action || '-'}
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <strong>(R) Refleksi:</strong>
                                            <div style="white-space: pre-line; background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 5px;">
                                                ${work.reflection || '-'}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        
                        // Update modal actions based on status
                        if (work.status === 'pending') {
                            actionsDiv.innerHTML = `
                                <button type="button" class="btn btn-success" onclick="updateStatus(${work.id}, 'approve')">
                                    <i class="bi bi-check-circle me-1"></i>Setujui
                                </button>
                                <button type="button" class="btn btn-danger" onclick="updateStatus(${work.id}, 'reject')">
                                    <i class="bi bi-x-circle me-1"></i>Tolak
                                </button>
                            `;
                        } else {
                            actionsDiv.innerHTML = `
                                <button type="button" class="btn btn-warning" onclick="updateStatus(${work.id}, 'pending')">
                                    <i class="bi bi-clock me-1"></i>Kembalikan ke Menunggu
                                </button>
                            `;
                        }
                    } else {
                        contentDiv.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                ${data.message || 'Gagal memuat detail karya'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    contentDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Terjadi kesalahan saat memuat detail karya. Silakan coba lagi.
                        </div>
                    `;
                });
        }
        
        // Update work status
        function updateStatus(id, action) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'works.php';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = action;
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'work_id';
            idInput.value = id;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
        
        // Delete work
        function deleteWork(id) {
            if (confirm('Apakah Anda yakin ingin menghapus karya ini?')) {
                updateStatus(id, 'delete');
            }
        }
        
        // Select all checkboxes
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.work-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Bulk approve
        function bulkApprove() {
            const selected = getSelectedIds();
            if (selected.length === 0) {
                alert('Pilih minimal satu karya untuk disetujui.');
                return;
            }
            
            if (confirm(`Setujui ${selected.length} karya yang dipilih?`)) {
                updateStatus(selected[0], 'approve');
            }
        }
        
        // Bulk reject
        function bulkReject() {
            const selected = getSelectedIds();
            if (selected.length === 0) {
                alert('Pilih minimal satu karya untuk ditolak.');
                return;
            }
            
            if (confirm(`Tolak ${selected.length} karya yang dipilih?`)) {
                updateStatus(selected[0], 'reject');
            }
        }
        
        // Get selected checkbox IDs
        function getSelectedIds() {
            const checkboxes = document.querySelectorAll('.work-checkbox:checked');
            return Array.from(checkboxes).map(cb => parseInt(cb.value));
        }
        
        // Helper functions
        function getStatusColor(status) {
            const colors = {
                'approved': 'success',
                'pending': 'warning',
                'rejected': 'danger'
            };
            return colors[status] || 'secondary';
        }
        
        function get_type_color(type) {
            const colors = {
                'puisi': 'primary',
                'cerpen': 'success',
                'opini': 'info',
                'resensi': 'warning',
                'pantun': 'danger',
                'poster': 'secondary'
            };
            return colors[type] || 'primary';
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 992) {
                closeSidebar();
            }
        });
        
        // Handle escape key
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

<?php
// Helper function to get type color
function get_type_color($type) {
    $colors = [
        'puisi' => 'primary',
        'cerpen' => 'success',
        'opini' => 'info',
        'resensi' => 'warning',
        'pantun' => 'danger',
        'poster' => 'secondary'
    ];
    return $colors[$type] ?? 'primary';
}
?>