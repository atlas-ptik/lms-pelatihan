<?php
// Path: views/auth/login.php

require_once BASE_PATH . '/layouts/auth.php';

// Redirect jika sudah login
if (isset($_SESSION['user'])) {
    $redirect_to = isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin' ? '/admin' : '/user';
    header('Location: ' . BASE_URL . $redirect_to);
    exit;
}

// Proses form login
$error = '';
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;

    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $db = dbConnect();
        $stmt = $db->prepare("
            SELECT p.id, p.username, p.password, p.nama_lengkap, p.email, p.foto_profil, r.nama as role
            FROM pengguna p
            JOIN role r ON p.role_id = r.id
            WHERE p.username = :username AND p.status = 'aktif'
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

            // Set cookie if remember me is checked
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (30 * 24 * 60 * 60); // 30 days

                $stmt = $db->prepare("
                    UPDATE pengguna 
                    SET token_reset = :token, token_expired = DATE_ADD(NOW(), INTERVAL 30 DAY)
                    WHERE id = :id
                ");
                $stmt->bindParam(':token', $token);
                $stmt->bindParam(':id', $user['id']);
                $stmt->execute();

                setcookie('remember_token', $token, $expires, '/', '', false, true);
            }

            // Redirect after login
            $redirect_to = $redirect ? $redirect : ($user['role'] === 'admin' ? '/admin' : '/user');
            header('Location: ' . BASE_URL . $redirect_to);
            exit;
        } else {
            $error = 'Username atau password salah';
        }
    }
}

// Check for remember token cookie
if (!isset($_SESSION['user']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];

    $db = dbConnect();
    $stmt = $db->prepare("
        SELECT p.id, p.username, p.nama_lengkap, p.email, p.foto_profil, r.nama as role
        FROM pengguna p
        JOIN role r ON p.role_id = r.id
        WHERE p.token_reset = :token 
        AND p.token_expired > NOW() 
        AND p.status = 'aktif'
    ");
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user) {
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

        // Redirect after auto login
        $redirect_to = $redirect ? $redirect : ($user['role'] === 'admin' ? '/admin' : '/user');
        header('Location: ' . BASE_URL . $redirect_to);
        exit;
    } else {
        // Invalid or expired token
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

authHeader("Masuk", "Masuk ke akun Atlas LMS Anda");
?>

<h3 class="text-center fw-bold mb-4">Masuk ke Akun Anda</h3>

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

    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember" name="remember">
            <label class="form-check-label" for="remember">Ingat saya</label>
        </div>
        <a href="<?= BASE_URL ?>/forgot-password" class="text-decoration-none">Lupa password?</a>
    </div>

    <?php if ($redirect): ?>
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
    <?php endif; ?>

    <button type="submit" class="btn btn-auth mb-4">Masuk</button>

    <p class="text-center mb-0">
        Belum memiliki akun? <a href="<?= BASE_URL ?>/register" class="text-decoration-none">Daftar sekarang</a>
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