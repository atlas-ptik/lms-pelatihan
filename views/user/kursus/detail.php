<?php
// Path: views/user/kursus/detail.php

require_once BASE_PATH . '/layouts/user/user-layout.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/user/login');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . BASE_URL . '/user/kursus');
    exit;
}

$db = dbConnect();
$pendaftaranId = $_GET['id'];
$userId = $_SESSION['user']['id'];

$pendaftaranStmt = $db->prepare("
    SELECT p.*, k.judul, k.deskripsi, k.gambar_sampul, k.level, k.durasi_menit, k.status as kursus_status,
           u.nama_lengkap as nama_pembuat, u.foto_profil as foto_pembuat
    FROM pendaftaran p
    JOIN kursus k ON p.kursus_id = k.id
    JOIN pengguna u ON k.pembuat_id = u.id
    WHERE p.id = :id AND p.pengguna_id = :user_id
");
$pendaftaranStmt->execute([':id' => $pendaftaranId, ':user_id' => $userId]);
$pendaftaran = $pendaftaranStmt->fetch();

if (!$pendaftaran) {
    header('Location: ' . BASE_URL . '/user/kursus');
    exit;
}

$kursusId = $pendaftaran['kursus_id'];

$modulStmt = $db->prepare("
    SELECT m.*, 
           (SELECT COUNT(*) FROM materi mt WHERE mt.modul_id = m.id) as total_materi
    FROM modul m
    WHERE m.kursus_id = :kursus_id
    ORDER BY m.urutan
");
$modulStmt->execute([':kursus_id' => $kursusId]);
$moduls = $modulStmt->fetchAll();

$content = function () use ($pendaftaran, $moduls, $pendaftaranId, $db) {
    $baseUrl = BASE_URL;
?>
    <div class="card mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Detail Kursus</h5>
            <a href="<?= $baseUrl ?>/user/kursus" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <?php if ($pendaftaran['gambar_sampul']): ?>
                        <img src="<?= $baseUrl ?>/assets/img/kursus/<?= $pendaftaran['gambar_sampul'] ?>"
                            class="img-fluid rounded mb-3" alt="<?= htmlspecialchars($pendaftaran['judul']) ?>">
                    <?php else: ?>
                        <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="bi bi-book display-3 text-white"></i>
                        </div>
                    <?php endif; ?>

                    <div class="progress mb-2">
                        <div class="progress-bar" role="progressbar" style="width: <?= $pendaftaran['progres_persen'] ?>%;"
                            aria-valuenow="<?= $pendaftaran['progres_persen'] ?>" aria-valuemin="0" aria-valuemax="100">
                            <?= $pendaftaran['progres_persen'] ?>%
                        </div>
                    </div>
                    <p class="text-center small mb-3">Progres: <?= $pendaftaran['progres_persen'] ?>% selesai</p>

                    <div class="d-grid">
                        <?php if ($pendaftaran['status'] === 'aktif'): ?>
                            <a href="<?= $baseUrl ?>/user/belajar?id=<?= $pendaftaranId ?>" class="btn btn-primary">
                                <i class="bi bi-play-fill"></i> Lanjut Belajar
                            </a>
                        <?php else: ?>
                            <a href="<?= $baseUrl ?>/user/sertifikat?pendaftaran_id=<?= $pendaftaranId ?>" class="btn btn-success">
                                <i class="bi bi-award"></i> Lihat Sertifikat
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-8">
                    <span class="badge <?= $pendaftaran['status'] === 'aktif' ? 'bg-primary' : 'bg-success' ?> mb-2">
                        <?= ucfirst($pendaftaran['status']) ?>
                    </span>

                    <h2><?= htmlspecialchars($pendaftaran['judul']) ?></h2>

                    <div class="mb-3 d-flex align-items-center">
                        <?php if ($pendaftaran['foto_pembuat']): ?>
                            <img src="<?= $baseUrl ?>/assets/img/profile/<?= $pendaftaran['foto_pembuat'] ?>"
                                class="rounded-circle me-2" alt="Foto Instruktur" width="32" height="32"
                                style="object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-2"
                                style="width: 32px; height: 32px;">
                                <span class="text-white small">
                                    <?= strtoupper(substr($pendaftaran['nama_pembuat'], 0, 1)) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        <span>Instruktur: <?= htmlspecialchars($pendaftaran['nama_pembuat']) ?></span>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-6 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-bar-chart-fill me-2 text-primary"></i>
                                <span>Level: <?= ucfirst($pendaftaran['level']) ?></span>
                            </div>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-clock me-2 text-primary"></i>
                                <span>Durasi: <?= $pendaftaran['durasi_menit'] ? floor($pendaftaran['durasi_menit'] / 60) . ' jam ' . ($pendaftaran['durasi_menit'] % 60) . ' menit' : 'Tidak ditentukan' ?></span>
                            </div>
                        </div>
                        <div class="col-sm-6 mb-2">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-calendar me-2 text-primary"></i>
                                <span>Terdaftar: <?= date('d M Y', strtotime($pendaftaran['tanggal_daftar'])) ?></span>
                            </div>
                        </div>
                        <?php if ($pendaftaran['tanggal_selesai']): ?>
                            <div class="col-sm-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle me-2 text-success"></i>
                                    <span>Selesai: <?= date('d M Y', strtotime($pendaftaran['tanggal_selesai'])) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body">
                            <h5>Deskripsi Kursus</h5>
                            <p><?= nl2br(htmlspecialchars($pendaftaran['deskripsi'])) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Materi Kursus</h5>
        </div>
        <div class="card-body">
            <div class="accordion" id="accordionModul">
                <?php if (count($moduls) > 0): ?>
                    <?php foreach ($moduls as $index => $modul): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $index ?>">
                                <button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>">
                                    <div>
                                        <strong><?= htmlspecialchars($modul['judul']) ?></strong>
                                        <div class="text-muted small"><?= $modul['total_materi'] ?> materi</div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse<?= $index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
                                data-bs-parent="#accordionModul">
                                <div class="accordion-body">
                                    <?php
                                    $materiStmt = $db->prepare("
                                        SELECT m.*, 
                                               (SELECT pm.status FROM progres_materi pm 
                                                WHERE pm.pendaftaran_id = :pendaftaran_id AND pm.materi_id = m.id) as status_belajar
                                        FROM materi m
                                        WHERE m.modul_id = :modul_id
                                        ORDER BY m.urutan
                                    ");
                                    $materiStmt->execute([':modul_id' => $modul['id'], ':pendaftaran_id' => $pendaftaranId]);
                                    $materis = $materiStmt->fetchAll();
                                    ?>

                                    <?php if (count($materis) > 0): ?>
                                        <ul class="list-group list-group-flush">
                                            <?php foreach ($materis as $materi): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div class="d-flex align-items-center">
                                                        <?php
                                                        $icon = 'bi-circle';
                                                        $badgeClass = 'bg-secondary';
                                                        $statusText = 'Belum mulai';

                                                        if ($materi['status_belajar'] === 'sedang dipelajari') {
                                                            $icon = 'bi-play-circle-fill';
                                                            $badgeClass = 'bg-primary';
                                                            $statusText = 'Sedang dipelajari';
                                                        } elseif ($materi['status_belajar'] === 'selesai') {
                                                            $icon = 'bi-check-circle-fill';
                                                            $badgeClass = 'bg-success';
                                                            $statusText = 'Selesai';
                                                        }
                                                        ?>

                                                        <i class="bi <?= $icon ?> me-2"></i>

                                                        <div>
                                                            <span><?= htmlspecialchars($materi['judul']) ?></span>
                                                            <div class="d-flex align-items-center small text-muted">
                                                                <?php
                                                                switch ($materi['tipe']) {
                                                                    case 'video':
                                                                        echo '<i class="bi bi-camera-video me-1"></i> Video';
                                                                        break;
                                                                    case 'artikel':
                                                                        echo '<i class="bi bi-file-text me-1"></i> Artikel';
                                                                        break;
                                                                    case 'dokumen':
                                                                        echo '<i class="bi bi-file-earmark me-1"></i> Dokumen';
                                                                        break;
                                                                    case 'quiz':
                                                                        echo '<i class="bi bi-question-circle me-1"></i> Kuis';
                                                                        break;
                                                                    case 'tugas':
                                                                        echo '<i class="bi bi-clipboard-check me-1"></i> Tugas';
                                                                        break;
                                                                }
                                                                ?>

                                                                <?php if ($materi['durasi_menit']): ?>
                                                                    &bull; <?= $materi['durasi_menit'] ?> menit
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <span class="badge <?= $badgeClass ?>"><?= $statusText ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p class="text-muted">Tidak ada materi dalam modul ini</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Belum ada modul dalam kursus ini</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php
};

userLayout('Detail Kursus', $content(), 'kursus');
