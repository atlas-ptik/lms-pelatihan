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

$content = function() use ($user) {
    $baseUrl = BASE_URL;
    ?>
    <div class="row">
        <div class="col-md-4 mb-4 mb-md-0">
            <div class="card">
                <div class="card-body text-center">
                    <?php if ($user['foto_profil']): ?>
                        <img src="<?= $baseUrl ?>/assets/img/profile/<?= $user['foto_profil'] ?>" alt="Foto Profil" 
                             class="rounded-circle img-thumbnail mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto mb-3" 
                             style="width: 150px; height: 150px;">
                            <span class="display-4 text-white"><?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <h4><?= htmlspecialchars($user['nama_lengkap']) ?></h4>
                    <p class="text-muted"><?= htmlspecialchars($user['role_nama']) ?></p>
                    
                    <div class="d-grid gap-2">
                        <a href="<?= $baseUrl ?>/user/profil/edit" class="btn btn-primary">
                            <i class="bi bi-pencil-square"></i> Edit Profil
                        </a>
                        <a href="<?= $baseUrl ?>/user/profil/password" class="btn btn-outline-primary">
                            <i class="bi bi-key"></i> Ubah Password
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Informasi Pengguna</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Username</div>
                        <div class="col-md-8"><?= htmlspecialchars($user['username']) ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Email</div>
                        <div class="col-md-8"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Nomor Telepon</div>
                        <div class="col-md-8"><?= htmlspecialchars($user['nomor_telepon'] ?? '-') ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Status</div>
                        <div class="col-md-8">
                            <span class="badge bg-<?= $user['status'] === 'aktif' ? 'success' : 'danger' ?>">
                                <?= ucfirst(htmlspecialchars($user['status'])) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Bergabung Sejak</div>
                        <div class="col-md-8">
                            <?= date('d F Y', strtotime($user['waktu_dibuat'])) ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4 fw-bold">Login Terakhir</div>
                        <div class="col-md-8">
                            <?= $user['terakhir_login'] ? date('d F Y H:i', strtotime($user['terakhir_login'])) : '-' ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Bio</h5>
                </div>
                <div class="card-body">
                    <?php if ($user['bio']): ?>
                        <p><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
                    <?php else: ?>
                        <p class="text-muted">Belum ada bio.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
};

userLayout('Profil Saya', $content(), 'profil');