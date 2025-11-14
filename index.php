<?php
require_once 'config/database.php';

// Get filter parameters
 $type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
 $search_filter = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
 $sql = "SELECT w.*, u.full_name, u.kelas FROM works w 
        JOIN users u ON w.user_id = u.id 
        WHERE w.status = 'approved'";
 $params = [];
 $types = '';

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

// Get site settings
 $site_name = get_setting('site_name');
 $teacher_name = get_setting('teacher_name');
 $school_name = get_setting('school_name');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_name; ?> - <?php echo $teacher_name; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-book-half me-2"></i><?php echo $site_name; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#gallery">Galeri</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#submit">Unggah Karya</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">Tentang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Kontak</a>
                    </li>
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i><?php echo get_user_name(); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <?php if (is_admin()): ?>
                                    <li><a class="dropdown-item" href="admin/">Dashboard Admin</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="siswa/">Dashboard Siswa</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="auth/logout.php">Keluar</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="auth/login.php">Masuk</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="teacher-info">
                        <h1 class="display-4 fw-bold mb-3"><?php echo $site_name; ?></h1>
                       
                       
                    </div>
                    <p class="lead">Platform kreatif untuk menampilkan hasil karya literasi siswa <?php echo $school_name; ?>. Jelajahi puisi, cerpen, opini, resensi, pantun, visual poster, dan banyak lagi karya kreatif dari siswa-siswi berbakat kami.</p>
                    <div class="mt-4">
                        <a href="#gallery" class="btn btn-light btn-lg me-3">
                            <i class="bi bi-collection me-2"></i>Jelajahi Galeri
                        </a>
                        <a href="<?php echo is_logged_in() ? 'siswa/submit.php' : 'auth/login.php'; ?>" class="btn btn-outline-light btn-lg">
                            <i class="bi bi-cloud-upload me-2"></i>Unggah Karya
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <img src="https://picsum.photos/seed/literacy2/600/400.jpg" alt="Literasi Digital" class="img-fluid rounded-4 shadow-lg">
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section id="gallery" class="py-5">
        <div class="container">
            <h2 class="section-title">Galeri Karya Siswa</h2>
            
            <!-- Filter Buttons -->
            <div class="text-center mb-5">
                <form method="GET" action="index.php#gallery" class="d-inline-block">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_filter); ?>">
                    <button type="submit" name="type" value="all" class="btn <?php echo $type_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?> filter-btn">Semua</button>
                    <button type="submit" name="type" value="puisi" class="btn <?php echo $type_filter === 'puisi' ? 'btn-primary' : 'btn-outline-primary'; ?> filter-btn">Puisi</button>
                    <button type="submit" name="type" value="cerpen" class="btn <?php echo $type_filter === 'cerpen' ? 'btn-primary' : 'btn-outline-primary'; ?> filter-btn">Cerpen</button>
                    <button type="submit" name="type" value="opini" class="btn <?php echo $type_filter === 'opini' ? 'btn-primary' : 'btn-outline-primary'; ?> filter-btn">Opini</button>
                    <button type="submit" name="type" value="resensi" class="btn <?php echo $type_filter === 'resensi' ? 'btn-primary' : 'btn-outline-primary'; ?> filter-btn">Resensi</button>
                    <button type="submit" name="type" value="pantun" class="btn <?php echo $type_filter === 'pantun' ? 'btn-primary' : 'btn-outline-primary'; ?> filter-btn">Pantun</button>
                    <button type="submit" name="type" value="poster" class="btn <?php echo $type_filter === 'poster' ? 'btn-primary' : 'btn-outline-primary'; ?> filter-btn">Visual Poster</button>
                </form>
            </div>
            
            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-md-6 mx-auto">
                    <form method="GET" action="index.php#gallery">
                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_filter); ?>">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Cari judul, penulis, atau kelas..." value="<?php echo htmlspecialchars($search_filter); ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Works Grid -->
            <div class="row g-4" id="worksContainer">
                <?php if (count($works) > 0): ?>
                    <?php foreach ($works as $work): ?>
                    <div class="col-md-6 col-lg-4 work-item" data-type="<?php echo $work['type']; ?>">
                        <div class="card work-card h-100">
                            <div class="position-relative">
                                <img src="<?php echo $work['image_url'] ?: 'https://picsum.photos/seed/' . $work['id'] . '/600/400.jpg'; ?>" class="card-img-top work-card-img" alt="<?php echo $work['title']; ?>">
                                <span class="position-absolute top-0 end-0 m-2 badge bg-<?php echo get_type_color($work['type']); ?>"><?php echo ucfirst($work['type']); ?></span>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo $work['title']; ?></h5>
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted"><i class="bi bi-person me-1"></i><?php echo $work['full_name']; ?></small>
                                    <small class="text-muted"><i class="bi bi-building me-1"></i><?php echo $work['kelas']; ?></small>
                                </div>
                                <p class="card-text flex-grow-1"><?php echo substr(strip_tags($work['content']), 0, 100) . '...'; ?></p>
                                <div class="mt-auto">
                                    <button class="btn btn-sm btn-outline-primary w-100" 
                                            type="button" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#workModal" 
                                            onclick="loadWorkDetail(<?php echo $work['id']; ?>)">
                                        <i class="bi bi-eye me-1"></i>Lihat Detail
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="bi bi-book text-muted" style="font-size: 4rem;"></i>
                            <h4 class="mt-3">Tidak ada karya ditemukan</h4>
                            <p class="text-muted">Coba ubah filter atau kata kunci pencarian Anda.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-5">
        <div class="container">
            <h2 class="section-title">Tentang <?php echo $site_name; ?></h2>
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="https://picsum.photos/seed/classroom/600/400.jpg" alt="Tentang Kami" class="img-fluid rounded-4 shadow">
                </div>
                <div class="col-lg-6">
                    <h3 class="mb-3">Mendorong Kreativitas dan Literasi Digital</h3>
                    <p>Platform ini dirancang untuk:</p>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Menampilkan karya literasi siswa kepada publik</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Mendorong pengembangan kemampuan menulis dan berpikir kritis</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Membangun portofolio digital bagi siswa</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Meningkatkan literasi digital di kalangan siswa</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title">Hubungi Kami</h2>
            <div class="row">
               
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="bi bi-geo-alt-fill text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Lokasi</h5>
                            <p class="card-text"><?php echo $school_name; ?><br><?php echo get_setting('school_address'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="bi bi-envelope-fill text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h5 class="card-title">Kontak</h5>
                            <p class="card-text">Email: info@literasidigital.com<br>Telepon: (0358) 321456</p>
                            <div class="social-icons mt-3">
                                <a href="#"><i class="bi bi-facebook"></i></a>
                                <a href="#"><i class="bi bi-instagram"></i></a>
                                <a href="#"><i class="bi bi-twitter"></i></a>
                                <a href="#"><i class="bi bi-youtube"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="mb-3"><?php echo $site_name; ?></h5>
                    <p>Platform kreatif untuk menampilkan hasil karya literasi siswa <?php echo $school_name; ?>.</p>
                </div>
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h5 class="mb-3">Tautan Cepat</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#home" class="text-white-50 text-decoration-none">Beranda</a></li>
                        <li class="mb-2"><a href="#gallery" class="text-white-50 text-decoration-none">Galeri</a></li>
                        <li class="mb-2"><a href="#submit" class="text-white-50 text-decoration-none">Unggah Karya</a></li>
                        <li class="mb-2"><a href="#about" class="text-white-50 text-decoration-none">Tentang</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h5 class="mb-3">Newsletter</h5>
                    <p>Dapatkan pembaruan terbaru tentang karya siswa kami.</p>
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" placeholder="Email Anda">
                        <button class="btn btn-primary" type="button">Berlangganan</button>
                    </div>
                </div>
            </div>
            <hr class="my-4 bg-white-50">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo $site_name; ?> - <?php echo $school_name; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Work Detail Modal -->
    <div class="modal fade" id="workModal" tabindex="-1" aria-labelledby="workModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="workModalTitle">Judul Karya</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="workModalContent">
                        <!-- Work details will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" onclick="shareWork()">
                        <i class="bi bi-share me-2"></i>Bagikan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Global variable to store current work data
        let currentWork = null;
        
        // Load work detail function
        function loadWorkDetail(id) {
            const modalContent = document.getElementById('workModalContent');
            const modalTitle = document.getElementById('workModalTitle');
            
            // Show loading state
            modalContent.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Memuat...</span>
                    </div>
                    <p class="mt-3">Memuat detail karya...</p>
                </div>
            `;
            
            // Fetch work detail from API
            fetch(`api/works.php?id=${id}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        currentWork = data.work;
                        modalTitle.textContent = data.work.title;
                        
                        modalContent.innerHTML = `
                            <div class="row">
                                <div class="col-md-4">
                                    <img src="${data.work.image_url || 'https://picsum.photos/seed/' + data.work.id + '/400/300.jpg'}" 
                                         class="img-fluid rounded" alt="${data.work.title}" 
                                         onerror="this.src='https://picsum.photos/seed/default/400/300.jpg'">
                                </div>
                                <div class="col-md-8">
                                    <h4>${data.work.title}</h4>
                                    <div class="row mb-3">
                                        <div class="col-sm-6">
                                            <p><strong>Penulis:</strong> ${data.work.full_name}</p>
                                            <p><strong>Kelas:</strong> ${data.work.kelas}</p>
                                            <p><strong>Tanggal:</strong> ${new Date(data.work.created_at).toLocaleDateString('id-ID')}</p>
                                        </div>
                                        <div class="col-sm-6">
                                            <p><strong>Tipe Karya:</strong> ${data.work.type.charAt(0).toUpperCase() + data.work.type.slice(1)}</p>
                                            <p><strong>Sumber/URL:</strong> ${data.work.source_url ? '<a href="' + data.work.source_url + '" target="_blank">Lihat Sumber</a>' : '-'}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <h5>Konten Karya</h5>
                                <div style="white-space: pre-line; background-color: #f8f9fa; padding: 15px; border-radius: 8px; max-height: 300px; overflow-y: auto;">
                                    ${data.work.content || '-'}
                                </div>
                            </div>
                            
                            <div class="star-method">
                                <h5>Metode STAR (Situasi, Tantangan, Aksi, Refleksi)</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <strong>(S) Situasi:</strong>
                                            <div style="white-space: pre-line; background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 5px;">
                                                ${data.work.situation || '-'}
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <strong>(T) Tantangan:</strong>
                                            <div style="white-space: pre-line; background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 5px;">
                                                ${data.work.challenge || '-'}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <strong>(A) Aksi:</strong>
                                            <div style="white-space: pre-line; background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 5px;">
                                                ${data.work.action || '-'}
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <strong>(R) Refleksi:</strong>
                                            <div style="white-space: pre-line; background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 5px;">
                                                ${data.work.reflection || '-'}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        modalContent.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                ${data.message || 'Gagal memuat detail karya'}
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalContent.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Terjadi kesalahan saat memuat detail karya. Silakan coba lagi.
                        </div>
                    `;
                });
        }
        
        // Share work function
        function shareWork() {
            if (currentWork) {
                const shareUrl = window.location.href + '#work-' + currentWork.id;
                const shareText = `Lihat karya "${currentWork.title}" oleh ${currentWork.full_name} di ${currentWork.kelas}`;
                
                if (navigator.share) {
                    navigator.share({
                        title: currentWork.title,
                        text: shareText,
                        url: shareUrl
                    }).catch(err => console.log('Error sharing:', err));
                } else {
                    // Fallback: Copy to clipboard
                    const textArea = document.createElement('textarea');
                    textArea.value = shareText + '\n' + shareUrl;
                    document.body.appendChild(textArea);
                    textArea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textArea);
                    
                    // Show toast notification
                    showToast('Link berhasil disalin!');
                }
            }
        }
        
        // Show toast notification
        function showToast(message) {
            const toastHtml = `
                <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;
            
            const toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.innerHTML = toastHtml;
            document.body.appendChild(toastContainer);
            
            const toast = new bootstrap.Toast(toastContainer.querySelector('.toast'));
            toast.show();
            
            // Remove toast after it's hidden
            toastContainer.querySelector('.toast').addEventListener('hidden.bs.toast', () => {
                document.body.removeChild(toastContainer);
            });
        }
        
        // Initialize modal event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const workModal = document.getElementById('workModal');
            
            // Reset modal content when it's hidden
            workModal.addEventListener('hidden.bs.modal', function () {
                document.getElementById('workModalTitle').textContent = 'Judul Karya';
                document.getElementById('workModalContent').innerHTML = '';
                currentWork = null;
            });
            
            // Handle scroll to gallery section with filter
            if (window.location.hash === '#gallery') {
                setTimeout(() => {
                    const gallerySection = document.getElementById('gallery');
                    if (gallerySection) {
                        gallerySection.scrollIntoView({ behavior: 'smooth' });
                    }
                }, 100);
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