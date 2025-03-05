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

$content = '
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>Ubah Password</h3>
    <a href="' . BASE_URL . '/user/profil" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>';

if ($success) {
    $content .= '
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>Password berhasil diubah!
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>';
}

if ($error) {
    $content .= '
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $error . '
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>';
}

$content .= '
<div class="card">
    <div class="card-body">
        <form method="post">
            <div class="mb-3">
                <label for="current_password" class="form-label">Password Saat Ini</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="new_password" class="form-label">Password Baru</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <input type="password" class="form-control" id="new_password" name="new_password" 
                           minlength="6" required>
                </div>
                <div class="form-text">Minimal 6 karakter</div>
            </div>
            
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-key"></i></span>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           minlength="6" required>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="' . BASE_URL . '/user/profil" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Ubah Password</button>
            </div>
        </form>
    </div>
</div>';

userLayout('Ubah Password', $content, 'profil');
