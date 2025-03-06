<?php
// Path: views/user/tugas/detail.php

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/user/login');
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['kursus_id'])) {
    header('Location: ' . BASE_URL . '/user/kursus');
    exit;
}

$tugas_id = $_GET['id'];
$kursus_id = $_GET['kursus_id'];
$user_id = $_SESSION['user']['id'];

$db = dbConnect();

$sql_pendaftaran = "SELECT id FROM pendaftaran WHERE pengguna_id = ? AND kursus_id = ?";
$stmt_pendaftaran = $db->prepare($sql_pendaftaran);
$stmt_pendaftaran->execute([$user_id, $kursus_id]);
$pendaftaran = $stmt_pendaftaran->fetch();

if (!$pendaftaran) {
    header('Location: ' . BASE_URL . '/user/kursus');
    exit;
}

$pendaftaran_id = $pendaftaran['id'];

$sql_tugas = "SELECT t.*, m.judul as materi_judul, m.id as materi_id, mo.judul as modul_judul 
             FROM tugas t
             JOIN materi m ON t.materi_id = m.id
             JOIN modul mo ON m.modul_id = mo.id
             WHERE t.id = ? AND mo.kursus_id = ?";
$stmt_tugas = $db->prepare($sql_tugas);
$stmt_tugas->execute([$tugas_id, $kursus_id]);
$tugas = $stmt_tugas->fetch();

if (!$tugas) {
    header('Location: ' . BASE_URL . '/user/tugas?kursus_id=' . $kursus_id);
    exit;
}

$sql_pengumpulan = "SELECT * FROM pengumpulan_tugas WHERE tugas_id = ? AND pendaftaran_id = ?";
$stmt_pengumpulan = $db->prepare($sql_pengumpulan);
$stmt_pengumpulan->execute([$tugas_id, $pendaftaran_id]);
$pengumpulan = $stmt_pengumpulan->fetch();

$sekarang = new DateTime();
$tenggat = $tugas['tenggat_waktu'] ? new DateTime($tugas['tenggat_waktu']) : null;
$terlambat = $tenggat && $sekarang > $tenggat;

$status_text = 'Belum Dikumpulkan';
$status_class = 'warning';

if ($pengumpulan) {
    switch ($pengumpulan['status']) {
        case 'menunggu penilaian':
            $status_text = 'Menunggu Penilaian';
            $status_class = 'info';
            break;
        case 'dinilai':
            $status_text = 'Sudah Dinilai';
            $status_class = 'success';
            break;
        case 'revisi':
            $status_text = 'Perlu Revisi';
            $status_class = 'danger';
            break;
        case 'terlambat':
            $status_text = 'Terlambat';
            $status_class = 'danger';
            break;
    }
} elseif ($terlambat) {
    $status_text = 'Tenggat Terlewat';
    $status_class = 'danger';
}

ob_start();
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/kursus">Kursus Saya</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/belajar?kursus_id=<?= $kursus_id ?>">Materi</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/tugas?kursus_id=<?= $kursus_id ?>">Tugas</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($tugas['judul']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h4 class="card-title mb-0"><?= htmlspecialchars($tugas['judul']) ?></h4>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="mb-3">
                            <?php if ($tugas['tenggat_waktu']): ?>
                                <div class="badge bg-<?= $terlambat ? 'danger' : 'primary' ?> mb-2">
                                    <i class="bi bi-clock me-1"></i>
                                    Tenggat: <?= date('d M Y, H:i', strtotime($tugas['tenggat_waktu'])) ?>
                                </div>
                                <?php if (!$terlambat && $tenggat):
                                    $interval = $sekarang->diff($tenggat);
                                    $sisa_waktu = '';

                                    if ($interval->days > 0) {
                                        $sisa_waktu .= $interval->days . ' hari ';
                                    }

                                    if ($interval->h > 0) {
                                        $sisa_waktu .= $interval->h . ' jam';
                                    }

                                    if ($interval->days == 0 && $interval->h == 0) {
                                        $sisa_waktu = $interval->i . ' menit';
                                    }
                                ?>
                                    <div class="badge bg-info mb-2 ms-2">
                                        <i class="bi bi-hourglass-split me-1"></i>
                                        Sisa waktu: <?= $sisa_waktu ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>

                            <div class="badge bg-<?= $status_class ?> mb-2 <?= $tugas['tenggat_waktu'] ? 'ms-2' : '' ?>">
                                <i class="bi bi-check-circle me-1"></i>
                                Status: <?= $status_text ?>
                            </div>

                            <div class="badge bg-secondary mb-2 ms-2">
                                <i class="bi bi-award me-1"></i>
                                Nilai Maksimal: <?= $tugas['nilai_maksimal'] ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div><strong>Modul:</strong> <?= htmlspecialchars($tugas['modul_judul']) ?></div>
                            <div><strong>Materi:</strong> <?= htmlspecialchars($tugas['materi_judul']) ?></div>
                        </div>

                        <h5>Deskripsi Tugas</h5>
                        <div class="tugas-content mb-4">
                            <?= nl2br(htmlspecialchars($tugas['deskripsi'])) ?>
                        </div>

                        <?php if ($tugas['file_lampiran']): ?>
                            <div class="mb-4">
                                <h5>Lampiran</h5>
                                <a href="<?= BASE_URL ?>/uploads/tugas/<?= $tugas['file_lampiran'] ?>" class="btn btn-outline-primary" target="_blank">
                                    <i class="bi bi-file-earmark-arrow-down me-2"></i>Unduh Lampiran
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <h5>Jenis Pengumpulan</h5>
                            <ul class="list-group list-group-flush">
                                <?php if ($tugas['tipe_pengumpulan'] === 'file' || $tugas['tipe_pengumpulan'] === 'keduanya'): ?>
                                    <li class="list-group-item bg-light">
                                        <i class="bi bi-file-earmark me-2"></i>
                                        <strong>File</strong> - Anda dapat mengumpulkan tugas dalam bentuk file (dokumen, gambar, dll.)
                                    </li>
                                <?php endif; ?>

                                <?php if ($tugas['tipe_pengumpulan'] === 'teks' || $tugas['tipe_pengumpulan'] === 'keduanya'): ?>
                                    <li class="list-group-item bg-light">
                                        <i class="bi bi-text-paragraph me-2"></i>
                                        <strong>Teks</strong> - Anda dapat mengetikkan jawaban langsung dalam bentuk teks
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <?php if (!$pengumpulan || $pengumpulan['status'] === 'revisi'): ?>
                            <?php if (!$terlambat || $tugas['tenggat_waktu'] === null): ?>
                                <a href="<?= BASE_URL ?>/user/tugas/kumpul?id=<?= $tugas_id ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-primary">
                                    <i class="bi bi-upload me-2"></i>Kumpulkan Tugas
                                </a>
                            <?php else: ?>
                                <div class="alert alert-danger mb-0">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    Maaf, batas waktu pengumpulan tugas telah berakhir.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <a href="<?= BASE_URL ?>/user/tugas?kursus_id=<?= $kursus_id ?>" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-2"></i>Kembali ke Daftar Tugas
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <?php if ($pengumpulan): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">Pengumpulan Tugas</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="badge bg-<?= $status_class ?> mb-2">
                                <?= $status_text ?>
                            </div>

                            <div class="mb-2">
                                <strong>Waktu Pengumpulan:</strong>
                                <div><?= date('d M Y, H:i', strtotime($pengumpulan['waktu_pengumpulan'])) ?></div>
                            </div>

                            <?php if ($pengumpulan['nilai'] !== null): ?>
                                <div class="mb-3">
                                    <strong>Nilai:</strong>
                                    <div class="h3 text-primary"><?= $pengumpulan['nilai'] ?> / <?= $tugas['nilai_maksimal'] ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($pengumpulan['file_jawaban']): ?>
                            <div class="mb-3">
                                <h6>File Jawaban</h6>
                                <a href="<?= BASE_URL ?>/uploads/tugas/jawaban/<?= $pengumpulan['file_jawaban'] ?>" class="btn btn-sm btn-outline-primary d-block" target="_blank">
                                    <i class="bi bi-file-earmark me-2"></i>Lihat File
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($pengumpulan['teks_jawaban']): ?>
                            <div class="mb-3">
                                <h6>Jawaban Teks</h6>
                                <div class="card">
                                    <div class="card-body bg-light">
                                        <?= nl2br(htmlspecialchars($pengumpulan['teks_jawaban'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($pengumpulan['komentar_pengajar']): ?>
                            <div class="mb-3">
                                <h6>Komentar Pengajar</h6>
                                <div class="card">
                                    <div class="card-body bg-light">
                                        <?= nl2br(htmlspecialchars($pengumpulan['komentar_pengajar'])) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($pengumpulan['status'] === 'revisi'): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                Tugas Anda perlu direvisi sesuai komentar pengajar. Silakan kumpulkan kembali tugas yang sudah direvisi.
                            </div>

                            <a href="<?= BASE_URL ?>/user/tugas/kumpul?id=<?= $tugas_id ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-warning d-block">
                                <i class="bi bi-pencil-square me-2"></i>Revisi Tugas
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Info Materi</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Kursus:</span>
                            <strong><?= htmlspecialchars($tugas['modul_judul']) ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Materi:</span>
                            <strong><?= htmlspecialchars($tugas['materi_judul']) ?></strong>
                        </li>
                    </ul>

                    <div class="d-grid gap-2 mt-3">
                        <a href="<?= BASE_URL ?>/user/belajar/materi?id=<?= $tugas['materi_id'] ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-outline-primary">
                            <i class="bi bi-book me-2"></i>Lihat Materi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/layouts/user/user-layout.php';
userLayout('Detail Tugas - ' . htmlspecialchars($tugas['judul']), $content, 'kursus');
?>