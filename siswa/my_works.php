<?php
require_once '../config/database.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('../auth/login.php');
}

// Get user's works
 $user_id = get_user_id();
 $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
 $type_filter = isset($_GET['type']) ? $_GET['type'] : '';

// Build query
 $sql = "SELECT * FROM works WHERE user_id = ?";
 $params = [$user_id];
 $types = "i";

if ($status_filter) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($type_filter) {
    $sql .= " AND type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

 $sql .= " ORDER BY created_at DESC";

 $stmt = $conn->prepare($sql);
 $stmt->bind_param($types, ...$params);
 $stmt->execute();
 $result = $stmt->get_result();
 $works = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $works[] = $row;
    }
}

// Get statistics
 $total_karya = count($works);
 $karya_approved = 0;
 $karya_pending = 0;
 $karya_rejected = 0;

foreach ($works as $work) {
    if ($work['status'] === 'approved') {
        $karya_approved++;
    } elseif ($work['status'] === 'pending') {
        $karya_pending++;
    } else {
        $karya_rejected++;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Karya Saya - <?php echo get_setting('site_name'); ?></title>
    
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
            <h4 class="mb-4">Siswa Panel</h4>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">
                        <i class="bi bi-speedometer2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="submit.php">
                        <i class="bi bi-cloud-upload"></i>
                        <span>Unggah Karya</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="my_works.php">
                        <i class="bi bi-book"></i>
                        <span>Karya Saya</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="bi bi-person"></i>
                        <span>Profil</span>
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
            <h2 class="mb-0">Karya Saya</h2>
           
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="bi bi-book-fill"></i>
                    </div>
                    <h5 class="mb-1"><?php echo $total_karya; ?></h5>
                    <p class="text-muted mb-0">Total Karya</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h5 class="mb-1"><?php echo $karya_approved; ?></h5>
                    <p class="text-muted mb-0">Disetujui</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                    <h5 class="mb-1"><?php echo $karya_pending; ?></h5>
                    <p class="text-muted mb-0">Menunggu</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-icon red">
                        <i class="bi bi-x-circle-fill"></i>
                    </div>
                    <h5 class="mb-1"><?php echo $karya_rejected; ?></h5>
                    <p class="text-muted mb-0">Ditolak</p>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-3">Filter Status:</h6>
                        <div class="btn-group" role="group">
                            <a href="my_works.php" class="filter-btn <?php echo !$status_filter ? 'active' : ''; ?>">Semua</a>
                            <a href="?status=pending" class="filter-btn <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                                <i class="bi bi-clock me-1"></i>Menunggu
                            </a>
                            <a href="?status=approved" class="filter-btn <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                                <i class="bi bi-check-circle me-1"></i>Disetujui
                            </a>
                            <a href="?status=rejected" class="filter-btn <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                                <i class="bi bi-x-circle me-1"></i>Ditolak
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-3">Filter Tipe:</h6>
                        <div class="btn-group" role="group">
                            <a href="my_works.php" class="filter-btn <?php echo !$type_filter ? 'active' : ''; ?>">Semua</a>
                            <a href="?type=puisi" class="filter-btn <?php echo $type_filter === 'puisi' ? 'active' : ''; ?>">Puisi</a>
                            <a href="?type=cerpen" class="filter-btn <?php echo $type_filter === 'cerpen' ? 'active' : ''; ?>">Cerpen</a>
                            <a href="?type=opini" class="filter-btn <?php echo $type_filter === 'opini' ? 'active' : ''; ?>">Opini</a>
                            <a href="?type=resensi" class="filter-btn <?php echo $type_filter === 'resensi' ? 'active' : ''; ?>">Resensi</a>
                            <a href="?type=pantun" class="filter-btn <?php echo $type_filter === 'pantun' ? 'active' : ''; ?>">Pantun</a>
                            <a href="?type=poster" class="filter-btn <?php echo $type_filter === 'poster' ? 'active' : ''; ?>">Poster</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Works List -->
        <?php if (count($works) > 0): ?>
            <?php foreach ($works as $work): ?>
                <div class="work-card <?php echo $work['status']; ?>">
                    <div class="d-flex">
                        <?php if ($work['image_url']): ?>
                            <img src="../<?php echo $work['image_url']; ?>" class="work-thumbnail" alt="<?php echo $work['title']; ?>">
                        <?php else: ?>
                            <img src="https://picsum.photos/seed/<?php echo $work['id']; ?>/100/100.jpg" class="work-thumbnail" alt="<?php echo $work['title']; ?>">
                        <?php endif; ?>
                        
                        <div class="work-content flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="mb-0"><?php echo $work['title']; ?></h5>
                                <div>
                                    <?php if ($work['status'] === 'approved'): ?>
                                        <span class="badge bg-success">Disetujui</span>
                                    <?php elseif ($work['status'] === 'pending'): ?>
                                        <span class="badge bg-warning">Menunggu Persetujuan</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Ditolak</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="work-meta mb-2">
                                <i class="bi bi-tag me-1"></i><?php echo ucfirst($work['type']); ?>
                                <span class="mx-2">|</span>
                                <i class="bi bi-calendar me-1"></i><?php echo date('d/m/Y', strtotime($work['created_at'])); ?>
                            </div>
                            
                            <p class="mb-2"><?php echo substr(strip_tags($work['content']), 0, 150) . '...'; ?></p>
                            
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="viewWorkDetail(<?php echo $work['id']; ?>)">
                                    <i class="bi bi-eye me-1"></i>Lihat Detail
                                </button>
                                <?php if ($work['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-outline-warning" onclick="editWork(<?php echo $work['id']; ?>)">
                                        <i class="bi bi-pencil me-1"></i>Edit
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteWork(<?php echo $work['id']; ?>)">
                                    <i class="bi bi-trash me-1"></i>Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-book text-muted" style="font-size: 4rem;"></i>
                    <h5 class="mt-3">Belum ada karya</h5>
                    <p class="text-muted">Anda belum mengunggah karya apapun. Mulai unggah karya literasi Anda sekarang!</p>
                    <a href="submit.php" class="btn btn-primary">
                        <i class="bi bi-cloud-upload me-2"></i>Unggah Karya
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Work Detail Modal -->
    <div class="modal fade" id="workDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="workDetailTitle">Detail Karya</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="workDetailContent">
                    <!-- Work details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="shareBtn">
                        <i class="bi bi-share me-2"></i>Bagikan
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // View work detail
        function viewWorkDetail(id) {
            fetch(`../api/works.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const work = data.work;
                        document.getElementById('workDetailTitle').textContent = work.title;
                        
                        let content = `
                            <div class="mb-3">
                                ${work.image_url ? `<img src="../${work.image_url}" class="img-fluid rounded mb-3" alt="${work.title}">` : ''}
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong>Tipe:</strong> ${work.type.charAt(0).toUpperCase() + work.type.slice(1)}</p>
                                        <p><strong>Status:</strong> 
                                            ${work.status === 'approved' ? '<span class="badge bg-success">Disetujui</span>' : 
                                              work.status === 'pending' ? '<span class="badge bg-warning">Menunggu</span>' : 
                                              '<span class="badge bg-danger">Ditolak</span>'}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Tanggal Unggah:</strong> ${new Date(work.created_at).toLocaleDateString('id-ID')}</p>
                                        <p><strong>Sumber:</strong> ${work.source_url || '-'}</p>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <h6>Konten Karya:</h6>
                                    <div style="white-space: pre-wrap;">${work.content}</div>
                                </div>
                        `;
                        
                        if (work.situation || work.challenge || work.action || work.reflection) {
                            content += `
                                <div class="star-method">
                                    <h6>Metode STAR:</h6>
                                    ${work.situation ? `<p><strong>Situasi:</strong> ${work.situation}</p>` : ''}
                                    ${work.challenge ? `<p><strong>Tantangan:</strong> ${work.challenge}</p>` : ''}
                                    ${work.action ? `<p><strong>Aksi:</strong> ${work.action}</p>` : ''}
                                    ${work.reflection ? `<p><strong>Refleksi:</strong> ${work.reflection}</p>` : ''}
                                </div>
                            `;
                        }
                        
                        document.getElementById('workDetailContent').innerHTML = content;
                        new bootstrap.Modal(document.getElementById('workDetailModal')).show();
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Edit work
        function editWork(id) {
            // Redirect to edit page or open edit modal
            window.location.href = `edit_work.php?id=${id}`;
        }
        
        // Delete work
        function deleteWork(id) {
            if (confirm('Apakah Anda yakin ingin menghapus karya ini?')) {
                fetch(`delete_work.php?id=${id}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Gagal menghapus karya. Silakan coba lagi.');
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }
        
        // Share work
        document.getElementById('shareBtn').addEventListener('click', function() {
            if (navigator.share) {
                navigator.share({
                    title: document.getElementById('workDetailTitle').textContent,
                    text: 'Lihat karya literasi saya di Galeri Literasi Digital',
                    url: window.location.href
                });
            } else {
                // Fallback - copy to clipboard
                const dummy = document.createElement('input');
                document.body.appendChild(dummy);
                dummy.value = window.location.href;
                dummy.select();
                document.execCommand('copy');
                document.body.removeChild(dummy);
                alert('Link telah disalin ke clipboard!');
            }
        });
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