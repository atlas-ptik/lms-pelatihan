<?php
// Path: views/user/kursus/index.php

require_once BASE_PATH . '/layouts/user/user-layout.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/user/login');
    exit;
}

$db = dbConnect();
$userId = $_SESSION['user']['id'];

$status = $_GET['status'] ?? 'semua';
$statusFilter = "";

if ($status === 'aktif') {
    $statusFilter = "AND p.status = 'aktif'";
} elseif ($status === 'selesai') {
    $statusFilter = "AND p.status = 'selesai'";
}

$stmt = $db->prepare("
    SELECT p.id, p.status, p.progres_persen, p.tanggal_daftar, 
           k.id as kursus_id, k.judul, k.deskripsi, k.gambar_sampul, k.level,
           (SELECT COUNT(*) FROM materi m JOIN modul md ON m.modul_id = md.id WHERE md.kursus_id = k.id) as total_materi
    FROM pendaftaran p
    JOIN kursus k ON p.kursus_id = k.id
    WHERE p.pengguna_id = :user_id $statusFilter
    ORDER BY p.tanggal_daftar DESC
");
$stmt->execute([':user_id' => $userId]);
$kursus = $stmt->fetchAll();

$content = function () use ($kursus, $status) {
    $baseUrl = BASE_URL;
?>
    <div class="d-sm-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-3 mb-sm-0">Kursus Saya</h3>
        <a href="<?= $baseUrl ?>/kursus" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Jelajahi Kursus Baru
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-light">
            <ul class="nav nav-pills nav-sm card-header-pills">
                <li class="nav-item">
                    <a class="nav-link <?= $status === 'semua' ? 'active' : '' ?>" href="<?= $baseUrl ?>/user/kursus">Semua</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $status === 'aktif' ? 'active' : '' ?>" href="<?= $baseUrl ?>/user/kursus?status=aktif">Aktif</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $status === 'selesai' ? 'active' : '' ?>" href="<?= $baseUrl ?>/user/kursus?status=selesai">Selesai</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <?php if (count($kursus) > 0): ?>
                <div class="row">
                    <?php foreach ($kursus as $item): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 course-card">
                                <?php if ($item['gambar_sampul']): ?>
                                    <img src="<?= $baseUrl ?>/assets/img/kursus/<?= $item['gambar_sampul'] ?>"
                                        class="card-img-top course-thumbnail" alt="<?= htmlspecialchars($item['judul']) ?>">
                                <?php else: ?>
                                    <div class="bg-secondary course-thumbnail d-flex align-items-center justify-content-center">
                                        <i class="bi bi-book display-4 text-white"></i>
                                    </div>
                                <?php endif; ?>

                                <span class="badge <?= $item['status'] === 'aktif' ? 'bg-primary' : 'bg-success' ?>">
                                    <?= ucfirst($item['status']) ?>
                                </span>

                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($item['judul']) ?></h5>
                                    <p class="text-muted small">
                                        <i class="bi bi-bar-chart-fill"></i> <?= ucfirst($item['level']) ?>
                                        &bull; <i class="bi bi-book"></i> <?= $item['total_materi'] ?> materi
                                    </p>
                                    <div class="progress mb-3">
                                        <div class="progress-bar" role="progressbar" style="width: <?= $item['progres_persen'] ?>%;"
                                            aria-valuenow="<?= $item['progres_persen'] ?>" aria-valuemin="0" aria-valuemax="100">
                                            <?= $item['progres_persen'] ?>%
                                        </div>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <a href="<?= $baseUrl ?>/user/kursus/detail?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-info-circle"></i> Detail
                                        </a>
                                        <?php if ($item['status'] === 'aktif'): ?>
                                            <a href="<?= $baseUrl ?>/user/belajar?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-play-fill"></i> Lanjut Belajar
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= $baseUrl ?>/user/sertifikat?pendaftaran_id=<?= $item['id'] ?>" class="btn btn-sm btn-success">
                                                <i class="bi bi-award"></i> Lihat Sertifikat
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-footer text-muted small">
                                    <i class="bi bi-calendar"></i> Terdaftar: <?= date('d M Y', strtotime($item['tanggal_daftar'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <div class="display-6 text-muted mb-3"><i class="bi bi-journal-x"></i></div>
                    <h4>Tidak ada kursus</h4>
                    <p>Anda belum mengikuti kursus apapun</p>
                    <a href="<?= $baseUrl ?>/kursus" class="btn btn-primary">Jelajahi Kursus</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php
};

userLayout('Kursus Saya', $content(), 'kursus');
