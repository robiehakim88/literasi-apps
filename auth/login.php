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
            
            // Redirect to appropriate dashboard
            if ($user['role'] === 'admin') {
                redirect('../admin/');
            } else {
                redirect('../siswa/');
            }
        } else {
            $error = 'Password salah!';
        }
    } else {
        $error = 'Username tidak ditemukan!';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo get_setting('site_name'); ?></title>
    
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
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 900px;
            max-width: 90%;
            min-height: 500px;
            display: flex;
        }
        
        .login-info {
            background: linear-gradient(135deg, #4361ee, #3f37c9);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex: 1;
        }
        
        .login-form {
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex: 1;
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
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                width: 100%;
                max-width: 100%;
                min-height: auto;
            }
            
            .login-info {
                padding: 20px;
            }
            
            .login-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-info">
            <h2 class="mb-4">Selamat Datang di <?php echo get_setting('site_name'); ?></h2>
            <p class="mb-4">Platform kreatif untuk menampilkan hasil karya literasi siswa <?php echo get_setting('school_name'); ?>. Masuk untuk mengunggah karya atau mengelola konten.</p>
            <div class="mt-auto">
                <img src="https://picsum.photos/seed/literacy3/400/200.jpg" alt="Literasi Digital" class="img-fluid rounded">
            </div>
        </div>
        <div class="login-form">
            <h3 class="mb-4 text-center">Masuk</h3>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Ingat saya</label>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Masuk</button>
                </div>
            </form>
            
            <div class="text-center mt-3">
                <p>Belum punya akun? <a href="../auth/register.php">Daftar sebagai siswa</a></p>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>