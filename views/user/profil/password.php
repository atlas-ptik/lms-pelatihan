<?php
// Path: views/user/profil/password.php

require_once BASE_PATH . '/layouts/user/user-layout.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/user/login');
    exit;
}

$db = dbConnect();
$userId = $_SESSION['user']['id'];

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Semua kolom harus diisi';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Password baru dan konfirmasi password tidak sesuai';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password baru minimal 6 karakter';
    } else {
        $stmt = $db->prepare("SELECT password FROM pengguna WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!password_verify($current_password, $user['password'])) {
            $error = 'Password saat ini tidak benar';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $updateStmt = $db->prepare("UPDATE pengguna SET password = :password WHERE id = :id");
            $updateStmt->execute([
                ':password' => $hashed_password,
                ':id' => $userId
            ]);

            $success = true;
        }
    }
}

$content = function () use ($success, $error) {
    $baseUrl = BASE_URL;
?>
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Ubah Password</h5>
                    <a href="<?= $baseUrl ?>/user/profil" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            Password berhasil diubah!
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Password Saat Ini</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="new_password" name="new_password"
                                minlength="6" required>
                            <small class="text-muted">Minimal 6 karakter</small>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                minlength="6" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Ubah Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php
};

userLayout('Ubah Password', $content(), 'profil');
