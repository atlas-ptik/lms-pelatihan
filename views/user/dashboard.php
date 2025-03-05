<?php
// Path: views/user/dashboard.php

require_once BASE_PATH . '/layouts/user/user-layout.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/user/login');
    exit;
}

$db = dbConnect();

$userId = $_SESSION['user']['id'];

$pendaftaranStmt = $db->prepare("SELECT COUNT(*) as total FROM pendaftaran WHERE pengguna_id = :user_id");
$pendaftaranStmt->execute([':user_id' => $userId]);
$totalKursus = $pendaftaranStmt->fetch()['total'];

$progresStmt = $db->prepare("
    SELECT k.judul, p.progres_persen, p.id as pendaftaran_id, p.status
    FROM pendaftaran p
    JOIN kursus k ON p.kursus_id = k.id
    WHERE p.pengguna_id = :user_id AND p.status = 'aktif'
    ORDER BY p.waktu_diperbarui DESC
    LIMIT 5
");
$progresStmt->execute([':user_id' => $userId]);
$progresKursus = $progresStmt->fetchAll();

$certificateStmt = $db->prepare("
    SELECT COUNT(*) as total 
    FROM sertifikat s
    JOIN pendaftaran p ON s.pendaftaran_id = p.id
    WHERE p.pengguna_id = :user_id AND s.status = 'aktif'
");
$certificateStmt->execute([':user_id' => $userId]);
$totalSertifikat = $certificateStmt->fetch()['total'];

$content = function () use ($totalKursus, $progresKursus, $totalSertifikat) {
    $baseUrl = BASE_URL;
?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Selamat Datang, <?= htmlspecialchars($_SESSION['user']['nama_lengkap']) ?>!</h3>
                    <p class="card-text">Lanjutkan belajar atau temukan kursus baru di Atlas LMS.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 col-lg-4 mb-3 mb-lg-0">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="display-4 mb-2"><i class="bi bi-book text-primary"></i></div>
                    <h5 class="card-title"><?= $totalKursus ?></h5>
                    <p class="card-text">Kursus Diikuti</p>
                    <a href="<?= $baseUrl ?>/user/kursus" class="btn btn-sm btn-outline-primary">Lihat Kursus</a>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-3 mb-lg-0">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="display-4 mb-2"><i class="bi bi-award text-primary"></i></div>
                    <h5 class="card-title"><?= $totalSertifikat ?></h5>
                    <p class="card-text">Sertifikat</p>
                    <a href="<?= $baseUrl ?>/user/sertifikat" class="btn btn-sm btn-outline-primary">Lihat Sertifikat</a>
                </div>
            </div>
        </div>

        <div class="col-md-12 col-lg-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="display-4 mb-2"><i class="bi bi-person-circle text-primary"></i></div>
                    <h5 class="card-title"><?= htmlspecialchars($_SESSION['user']['username']) ?></h5>
                    <p class="card-text">Profil Pengguna</p>
                    <a href="<?= $baseUrl ?>/user/profil" class="btn btn-sm btn-outline-primary">Edit Profil</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="m-0">Kursus Aktif</h5>
                    <a href="<?= $baseUrl ?>/user/kursus" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>
                <div class="card-body">
                    <?php if (count($progresKursus) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Kursus</th>
                                        <th>Progres</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($progresKursus as $kursus): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($kursus['judul']) ?></td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" style="width: <?= $kursus['progres_persen'] ?>%;"
                                                        aria-valuenow="<?= $kursus['progres_persen'] ?>" aria-valuemin="0" aria-valuemax="100">
                                                        <?= $kursus['progres_persen'] ?>%
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="<?= $baseUrl ?>/user/belajar?id=<?= $kursus['pendaftaran_id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-play-fill"></i> Lanjut Belajar
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p>Anda belum mengikuti kursus apapun</p>
                            <a href="<?= $baseUrl ?>/kursus" class="btn btn-primary">Jelajahi Kursus</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php
};

userLayout('Dashboard', $content(), 'dashboard');
