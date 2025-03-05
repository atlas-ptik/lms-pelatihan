<?php
// Path: views/auth/register.php

require_once BASE_PATH . '/layouts/auth.php';

// Redirect jika sudah login
if (isset($_SESSION['user'])) {
    $redirect_to = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin' ? '/admin' : '/user';
    header('Location: ' . BASE_URL . $redirect_to);
    exit;
}

// Ambil role siswa
$db = dbConnect();
$stmt = $db->prepare("SELECT id FROM role WHERE nama = 'siswa' LIMIT 1");
$stmt->execute();
$siswa_role = $stmt->fetch();

if (!$siswa_role) {
    die('Role siswa tidak ditemukan. Silakan jalankan migrasi database terlebih dahulu.');
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
    $terms = isset($_POST['terms']) ? true : false;

    // Validasi input
    if (empty($nama_lengkap) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Semua field harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok';
    } elseif (!$terms) {
        $error = 'Anda harus menyetujui syarat dan ketentuan';
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
                // Semua validasi passed, tambahkan user baru
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
                $stmt->bindParam(':role_id', $siswa_role['id']);
                $stmt->bindParam(':status', $status);

                if ($stmt->execute()) {
                    $success = 'Akun berhasil dibuat! Silakan login untuk melanjutkan.';

                    // Redirect ke login setelah beberapa detik
                    header('Refresh: 3; URL=' . BASE_URL . '/login');
                } else {
                    $error = 'Terjadi kesalahan. Silakan coba lagi.';
                }
            }
        }
    }
}

authHeader("Daftar", "Daftar Akun di Atlas LMS");
?>

<h3 class="text-center fw-bold mb-2">Daftar Akun Baru</h3>
<p class="text-center text-muted mb-4">Buat akun untuk memulai belajar di Atlas LMS</p>

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

    <div class="mb-3">
        <label for="confirm_password" class="form-label">Konfirmasi Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Masukkan konfirmasi password" required>
        </div>
    </div>

    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
        <label class="form-check-label" for="terms">
            Saya menyetujui <a href="<?= BASE_URL ?>/syarat-ketentuan" class="text-decoration-none" target="_blank">Syarat & Ketentuan</a> dan <a href="<?= BASE_URL ?>/kebijakan-privasi" class="text-decoration-none" target="_blank">Kebijakan Privasi</a>
        </label>
    </div>

    <button type="submit" class="btn btn-auth mb-3">Daftar Sekarang</button>

    <p class="text-center mb-0">
        Sudah memiliki akun? <a href="<?= BASE_URL ?>/login" class="text-decoration-none">Masuk sekarang</a>
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

<?php authFooter(); ?>