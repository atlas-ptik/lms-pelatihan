<?php
// Path: views/user/profil/index.php

require_once BASE_PATH . '/layouts/user/user-layout.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/user/login');
    exit;
}

$db = dbConnect();
$userId = $_SESSION['user']['id'];

$stmt = $db->prepare("
    SELECT p.*, r.nama as role_nama
    FROM pengguna p
    JOIN role r ON p.role_id = r.id
    WHERE p.id = :id
");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

$content = '
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>Profil Saya</h3>
    <a href="' . BASE_URL . '/user/profil/edit" class="btn btn-primary"><i class="bi bi-pencil-square"></i> Edit Profil</a>
</div>

<div class="card mb-4">
    <div class="card-body text-center p-4">
        <div class="mb-3">';

if ($user['foto_profil']) {
    $content .= '<img src="' . BASE_URL . '/uploads/profil/' . $user['foto_profil'] . '" alt="Foto Profil" 
             class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">';
} else {
    $content .= '<div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto" 
             style="width: 150px; height: 150px;">
            <span class="display-4 text-white">' . strtoupper(substr($user['nama_lengkap'], 0, 1)) . '</span>
        </div>';
}

$content .= '
        </div>
        <h4>' . htmlspecialchars($user['nama_lengkap']) . '</h4>
        <p class="text-muted">' . htmlspecialchars($user['role_nama']) . '</p>
        <div class="d-flex justify-content-center gap-2">
            <a href="' . BASE_URL . '/user/profil/edit" class="btn btn-primary">
                <i class="bi bi-pencil-square"></i> Edit Profil
            </a>
            <a href="' . BASE_URL . '/user/profil/password" class="btn btn-outline-primary">
                <i class="bi bi-key"></i> Ubah Password
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white">
        <h5 class="m-0">Informasi Akun</h5>
    </div>
    <div class="card-body">
        <div class="row mb-3 py-2 border-bottom">
            <div class="col-md-4 fw-bold">Username</div>
            <div class="col-md-8">' . htmlspecialchars($user['username']) . '</div>
        </div>
        
        <div class="row mb-3 py-2 border-bottom">
            <div class="col-md-4 fw-bold">Email</div>
            <div class="col-md-8">' . htmlspecialchars($user['email']) . '</div>
        </div>
        
        <div class="row mb-3 py-2 border-bottom">
            <div class="col-md-4 fw-bold">Nomor Telepon</div>
            <div class="col-md-8">' . htmlspecialchars($user['nomor_telepon'] ?? '-') . '</div>
        </div>
        
        <div class="row mb-3 py-2 border-bottom">
            <div class="col-md-4 fw-bold">Status</div>
            <div class="col-md-8">
                <span class="badge bg-' . ($user['status'] === 'aktif' ? 'success' : 'danger') . '">
                    ' . ucfirst(htmlspecialchars($user['status'])) . '
                </span>
            </div>
        </div>
        
        <div class="row mb-3 py-2 border-bottom">
            <div class="col-md-4 fw-bold">Bergabung Sejak</div>
            <div class="col-md-8">
                ' . date('d F Y', strtotime($user['waktu_dibuat'])) . '
            </div>
        </div>
        
        <div class="row mb-3 py-2">
            <div class="col-md-4 fw-bold">Login Terakhir</div>
            <div class="col-md-8">
                ' . ($user['terakhir_login'] ? date('d F Y H:i', strtotime($user['terakhir_login'])) : '-') . '
            </div>
        </div>';

if ($user['bio']) {
    $content .= '
        <div class="row pt-3 mt-3 border-top">
            <div class="col-12">
                <div class="fw-bold mb-2">Bio</div>
                <p>' . nl2br(htmlspecialchars($user['bio'])) . '</p>
            </div>
        </div>';
}

$content .= '
    </div>
</div>';

userLayout('Profil Saya', $content, 'profil');
