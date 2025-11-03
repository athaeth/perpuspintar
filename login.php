<?php
// Panggil db.php untuk memulai session
include __DIR__ . '/config/db.php';

// Definisikan $base_url
$base_url = '/perpuspintar/'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Perpustakaan Pintar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="login-register">

    <div class="login-container">
        <div class="card shadow-lg p-4 p-md-5">
            <div class="card-body">
                
                <div class="text-center mb-4">
                    <i class="bi bi-book-half" style="font-size: 3rem; color: var(--bs-primary);"></i>
                    <h2 class="mt-2">Login PerpusPintar</h2>
                    <p class="text-muted">Selamat datang kembali!</p>
                </div>

                <?php
                
                if (isset($_GET['status']) && $_GET['status'] == 'register_success') {
                    echo '<div class="alert alert-success">Registrasi berhasil! Silakan login.</div>';
                }
                
                if (isset($_GET['error']) && $_GET['error'] == '1') {
                    echo '<div class="alert alert-danger">Username atau password salah.</div>';
                }
                ?>

                <form action="auth/login_process.php" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Login</button>
                    </div>
                </form>
                
                <p class="mt-4 text-center">
                    Belum punya akun? <a href="register.php">Daftar di sini</a>
                </p>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>