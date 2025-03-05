<?php
// Path: views/admin/initial-register.php

require_once BASE_PATH . '/layouts/admin/auth.php';

// Cek apakah admin sudah terdaftar
$db = dbConnect();
$stmt = $db->prepare("
    SELECT COUNT(*) as total
    FROM pengguna p
    JOIN role r ON p.role_id = r.id
    WHERE r.nama = 'admin'
");
$stmt->execute();
$result = $stmt->fetch();

// Jika sudah ada admin, redirect ke halaman login
if ($result['total'] > 0) {
    header('Location: ' . BASE_URL . '/admin/login');
    exit;
}

// Ambil role admin
$stmt = $db->prepare("SELECT id FROM role WHERE nama = 'admin' LIMIT 1");
$stmt->execute();
$admin_role = $stmt->fetch();

if (!$admin_role) {
    die('Role admin tidak ditemukan. Silakan jalankan migrasi database terlebih dahulu.');
}

// Proses form pendaftaran
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = isset($_POST['nama_lengkap']) ? trim($_POST['nama_lengkap']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Validasi input
    if (empty($nama_lengkap) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Semua field harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username hanya boleh berisi huruf, angka, dan underscore';
    } else {
        // Cek username sudah digunakan atau belum
        $stmt = $db->prepare("SELECT id FROM pengguna WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $error = 'Username sudah digunakan';
        } else {
            // Cek email sudah digunakan atau belum
            $stmt = $db->prepare("SELECT id FROM pengguna WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = 'Email sudah terdaftar';
            } else {
                // Semua validasi passed, tambahkan admin baru
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $user_id = generate_uuid();
                $status = 'aktif';

                $stmt = $db->prepare("
                    INSERT INTO pengguna (id, username, password, email, nama_lengkap, role_id, status, waktu_dibuat)
                    VALUES (:id, :username, :password, :email, :nama_lengkap, :role_id, :status, NOW())
                ");

                $stmt->bindParam(':id', $user_id);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $hashed_password);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':nama_lengkap', $nama_lengkap);
                $stmt->bindParam(':role_id', $admin_role['id']);
                $stmt->bindParam(':status', $status);

                if ($stmt->execute()) {
                    $success = 'Akun admin berhasil dibuat! Silakan login untuk melanjutkan.';

                    // Redirect ke login setelah beberapa detik
                    header('Refresh: 3; URL=' . BASE_URL . '/admin/login');
                } else {
                    $error = 'Terjadi kesalahan. Silakan coba lagi.';
                }
            }
        }
    }
}

adminAuthHeader("Pendaftaran Admin", "Pendaftaran Admin Pertama Atlas LMS");
?>

<h3 class="text-center fw-bold mb-2">Pendaftaran Admin Pertama</h3>
<p class="text-center text-muted mb-4">Buat akun admin pertama untuk Atlas LMS</p>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill me-2"></i> <?= $success ?>
    </div>
<?php endif; ?>

<form method="POST" action="">
    <div class="mb-3">
        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" placeholder="Masukkan nama lengkap" value="<?= isset($_POST['nama_lengkap']) ? htmlspecialchars($_POST['nama_lengkap']) : '' ?>" required>
        </div>
    </div>

    <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" required>
        </div>
        <small class="text-muted">Username hanya boleh berisi huruf, angka, dan underscore</small>
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" placeholder="Masukkan email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
        </div>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                <i class="bi bi-eye"></i>
            </button>
        </div>
        <small class="text-muted">Password minimal 8 karakter</small>
    </div>

    <div class="mb-4">
        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Masukkan konfirmasi password" required>
        </div>
    </div>

    <button type="submit" class="btn btn-auth mb-3">Daftar Sebagai Admin</button>

    <p class="text-center mb-0">
        Sudah memiliki akun? <a href="<?= BASE_URL ?>/admin/login" class="text-decoration-none">Masuk sekarang</a>
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