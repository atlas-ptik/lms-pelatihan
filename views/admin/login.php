<?php
// Path: views/admin/login.php

require_once BASE_PATH . '/layouts/admin/auth.php';

// Redirect jika sudah login sebagai admin
if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
    header('Location: ' . BASE_URL . '/admin');
    exit;
}

// Proses form login
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $db = dbConnect();
        $stmt = $db->prepare("
            SELECT p.id, p.username, p.password, p.nama_lengkap, p.email, p.foto_profil, r.nama as role
            FROM pengguna p
            JOIN role r ON p.role_id = r.id
            WHERE p.username = :username AND p.status = 'aktif' AND r.nama = 'admin'
        ");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $update_stmt = $db->prepare("
                UPDATE pengguna SET terakhir_login = NOW() WHERE id = :id
            ");
            $update_stmt->bindParam(':id', $user['id']);
            $update_stmt->execute();

            // Set session
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'nama_lengkap' => $user['nama_lengkap'],
                'email' => $user['email'],
                'foto_profil' => $user['foto_profil'],
                'role' => $user['role']
            ];

            // Redirect to admin dashboard
            header('Location: ' . BASE_URL . '/admin');
            exit;
        } else {
            $error = 'Username atau password salah';
        }
    }
}

adminAuthHeader("Login Admin", "Halaman login admin Atlas LMS");
?>

<h3 class="text-center fw-bold mb-4">Login Admin</h3>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
    </div>
<?php endif; ?>

<form method="POST" action="">
    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required>
        </div>
    </div>

    <div class="mb-4">
        <label for="password" class="form-label">Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-auth mb-4">Login</button>

    <p class="text-center mb-0">
        <a href="<?= BASE_URL ?>/admin/initial-register" class="text-decoration-none">Buat akun admin pertama</a>
    </p>
</form>

<script>
    document.getElementById('togglePassword').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    });
</script>

<?php adminAuthFooter(); ?>