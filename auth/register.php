<?php
require_once '../config/database.php';

// If already logged in, redirect to appropriate dashboard
if (is_logged_in()) {
    if (is_admin()) {
        redirect('../admin/');
    } else {
        redirect('../siswa/');
    }
}

 $error = '';
 $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = escape($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = escape($_POST['full_name']);
    $email = escape($_POST['email']);
    $kelas = escape($_POST['kelas']);
    
    // Validation
    if (empty($username) || empty($password) || empty($confirm_password) || empty($full_name) || empty($email) || empty($kelas)) {
        $error = 'Semua field harus diisi!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok!';
    } elseif (strlen($password) < 6) {
        $error = 'Password harus minimal 6 karakter!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        // Check if username already exists
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            // Check if email already exists
            $sql = "SELECT id FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Email sudah digunakan!';
            } else {
                // Insert new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'siswa';
                
                $sql = "INSERT INTO users (username, password, full_name, email, role, kelas) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $username, $hashed_password, $full_name, $email, $role, $kelas);
                
                if ($stmt->execute()) {
                    $success = 'Pendaftaran berhasil! Silakan login dengan akun Anda.';
                } else {
                    $error = 'Terjadi kesalahan. Silakan coba lagi.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - <?php echo get_setting('site_name'); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #4361ee, #3f37c9);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
        }
        
        .register-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 900px;
            max-width: 90%;
            min-height: 600px;
            display: flex;
        }
        
        .register-info {
            background: linear-gradient(135deg, #4361ee, #3f37c9);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex: 1;
        }
        
        .register-form {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex: 1;
            overflow-y: auto;
            max-height: 90vh;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .btn-primary {
            background-color: #4361ee;
            border-color: #4361ee;
            border-radius: 10px;
            padding: 10px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background-color: #3f37c9;
            border-color: #3f37c9;
            transform: translateY(-3px);
        }
        
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        
        .strength-weak {
            background-color: #dc3545;
            width: 33%;
        }
        
        .strength-medium {
            background-color: #ffc107;
            width: 66%;
        }
        
        .strength-strong {
            background-color: #28a745;
            width: 100%;
        }
        
        @media (max-width: 768px) {
            .register-container {
                flex-direction: column;
                width: 100%;
                max-width: 100%;
                min-height: auto;
            }
            
            .register-info {
                padding: 20px;
            }
            
            .register-form {
                padding: 20px;
                max-height: none;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-info">
            <h2 class="mb-4">Bergabung dengan <?php echo get_setting('site_name'); ?></h2>
            <p class="mb-4">Daftar sekarang untuk mulai berbagi karya literasi Anda. Jadilah bagian dari komunitas kreatif <?php echo get_setting('school_name'); ?>.</p>
            <div class="mb-4">
                <h5 class="mb-3">Keuntungan Mendaftar:</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="bi bi-check-circle me-2"></i>Unggah karya literasi Anda</li>
                    <li class="mb-2"><i class="bi bi-check-circle me-2"></i>Dapatkan feedback dari guru</li>
                    <li class="mb-2"><i class="bi bi-check-circle me-2"></i>Bangun portofolio digital</li>
                    <li class="mb-2"><i class="bi bi-check-circle me-2"></i>Terhubung dengan siswa lain</li>
                </ul>
            </div>
            <div class="mt-auto">
                <img src="https://picsum.photos/seed/literacy4/400/200.jpg" alt="Literasi Digital" class="img-fluid rounded">
            </div>
        </div>
        <div class="register-form">
            <h3 class="mb-4 text-center">Daftar Akun Siswa</h3>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-primary">Masuk Sekarang</a>
                </div>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="full_name" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="kelas" class="form-label">Kelas</label>
                            <select class="form-select" id="kelas" name="kelas" required>
                                <option value="" selected disabled>Pilih kelas</option>
                                <option value="X IPA 1">X IPA 1</option>
                                <option value="X IPA 2">X IPA 2</option>
                                <option value="X IPA 3">X IPA 3</option>
                                <option value="X IPS 1">X IPS 1</option>
                                <option value="X IPS 2">X IPS 2</option>
                                <option value="XI IPA 1">XI IPA 1</option>
                                <option value="XI IPA 2">XI IPA 2</option>
                                <option value="XI IPA 3">XI IPA 3</option>
                                <option value="XI IPS 1">XI IPS 1</option>
                                <option value="XI IPS 2">XI IPS 2</option>
                                <option value="XII IPA 1">XII IPA 1</option>
                                <option value="XII IPA 2">XII IPA 2</option>
                                <option value="XII IPA 3">XII IPA 3</option>
                                <option value="XII IPS 1">XII IPS 1</option>
                                <option value="XII IPS 2">XII IPS 2</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                        <div class="form-text">Username akan digunakan untuk login.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="password-strength" id="passwordStrength"></div>
                        <div class="form-text">Minimal 6 karakter.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" required>
                        <label class="form-check-label" for="terms">
                            Saya menyetujui <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">syarat dan ketentuan</a> yang berlaku
                        </label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Daftar Sekarang</button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <p>Sudah punya akun? <a href="login.php">Masuk di sini</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Syarat dan Ketentuan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Penggunaan Platform</h6>
                    <p>Platform Galeri Literasi Digital disediakan untuk siswa <?php echo get_setting('school_name'); ?> sebagai sarana mengekspresikan kreativitas dalam bentuk karya literasi.</p>
                    
                    <h6>2. Konten yang Diunggah</h6>
                    <p>Siswa bertanggung jawab penuh atas konten yang diunggah. Konten harus:</p>
                    <ul>
                        <li>Asli dan bukan hasil plagiat</li>
                        <li>Tidak mengandung SARA, pornografi, atau konten yang melanggar hukum</li>
                        <li>Sesuai dengan norma dan etika yang berlaku di sekolah</li>
                    </ul>
                    
                    <h6>3. Hak Cipta</h6>
                    <p>Siswa memegang hak cipta atas karya yang diunggah. Dengan mendaftar, siswa memberikan izin kepada sekolah untuk menampilkan karya di platform ini.</p>
                    
                    <h6>4. Privasi</h6>
                    <p>Data pribadi siswa akan dilindungi sesuai dengan kebijakan privasi sekolah dan tidak akan dibagikan kepada pihak ketiga tanpa izin.</p>
                    
                    <h6>5. Sanksi</h6>
                    <p>Pelanggaran terhadap syarat dan ketentuan dapat mengakibatkan:</p>
                    <ul>
                        <li>Penghapusan konten yang melanggar</li>
                        <li>Suspensi akun sementara</li>
                        <li>Pemblokiran akun permanen</li>
                        <li>Sanksi sekolah sesuai aturan yang berlaku</li>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.getElementById('passwordStrength');
            
            if (password.length < 6) {
                strengthIndicator.className = 'password-strength';
            } else if (password.length < 10 || !/[A-Z]/.test(password) || !/[0-9]/.test(password)) {
                strengthIndicator.className = 'password-strength strength-weak';
            } else if (password.length < 12 || !/[!@#$%^&*]/.test(password)) {
                strengthIndicator.className = 'password-strength strength-medium';
            } else {
                strengthIndicator.className = 'password-strength strength-strong';
            }
        });
        
        // Confirm password validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Password tidak cocok!');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>