<?php
// Path: views/user/belajar/materi.php

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/user/login');
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['kursus_id'])) {
    header('Location: ' . BASE_URL . '/user/kursus');
    exit;
}

$materi_id = $_GET['id'];
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

$sql_materi = "SELECT m.*, mo.judul as modul_judul, mo.id as modul_id, k.judul as kursus_judul 
               FROM materi m 
               JOIN modul mo ON m.modul_id = mo.id 
               JOIN kursus k ON mo.kursus_id = k.id 
               WHERE m.id = ? AND mo.kursus_id = ?";
$stmt_materi = $db->prepare($sql_materi);
$stmt_materi->execute([$materi_id, $kursus_id]);
$materi = $stmt_materi->fetch();

if (!$materi) {
    header('Location: ' . BASE_URL . '/user/belajar?kursus_id=' . $kursus_id);
    exit;
}

$sql_progres = "SELECT * FROM progres_materi WHERE pendaftaran_id = ? AND materi_id = ?";
$stmt_progres = $db->prepare($sql_progres);
$stmt_progres->execute([$pendaftaran_id, $materi_id]);
$progres = $stmt_progres->fetch();

if (!$progres) {
    $sql_insert_progres = "INSERT INTO progres_materi (id, pendaftaran_id, materi_id, status, waktu_mulai) 
                           VALUES (?, ?, ?, 'sedang dipelajari', NOW())";
    $stmt_insert_progres = $db->prepare($sql_insert_progres);
    $stmt_insert_progres->execute([generate_uuid(), $pendaftaran_id, $materi_id]);

    $sql_progres = "SELECT * FROM progres_materi WHERE pendaftaran_id = ? AND materi_id = ?";
    $stmt_progres = $db->prepare($sql_progres);
    $stmt_progres->execute([$pendaftaran_id, $materi_id]);
    $progres = $stmt_progres->fetch();
}

$sql_next = "SELECT m.id, m.judul 
             FROM materi m 
             JOIN modul mo ON m.modul_id = mo.id 
             WHERE mo.kursus_id = ? AND m.urutan > ? AND mo.id = ? 
             ORDER BY m.urutan ASC LIMIT 1";
$stmt_next = $db->prepare($sql_next);
$stmt_next->execute([$kursus_id, $materi['urutan'], $materi['modul_id']]);
$next_materi = $stmt_next->fetch();

if (!$next_materi) {
    $sql_next_modul = "SELECT m.id, m.judul 
                       FROM materi m 
                       JOIN modul mo ON m.modul_id = mo.id 
                       WHERE mo.kursus_id = ? AND mo.urutan > ? 
                       ORDER BY mo.urutan ASC, m.urutan ASC LIMIT 1";
    $stmt_next_modul = $db->prepare($sql_next_modul);
    $stmt_next_modul->execute([$kursus_id, $materi['urutan']]);
    $next_materi = $stmt_next_modul->fetch();
}

ob_start();
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/kursus">Kursus Saya</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/belajar?kursus_id=<?= $kursus_id ?>"><?= htmlspecialchars($materi['kursus_judul']) ?></a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/belajar?kursus_id=<?= $kursus_id ?>"><?= htmlspecialchars($materi['modul_judul']) ?></a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($materi['judul']) ?></li>
    </ol>
</nav>

<div class="card mb-4">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0"><?= htmlspecialchars($materi['judul']) ?></h4>
            <span class="badge rounded-pill bg-light text-dark">
                <?php
                $icon = '';
                $label = '';

                switch ($materi['tipe']) {
                    case 'video':
                        $icon = 'bi-play-circle-fill';
                        $label = 'Video';
                        break;
                    case 'artikel':
                        $icon = 'bi-file-text-fill';
                        $label = 'Artikel';
                        break;
                    case 'dokumen':
                        $icon = 'bi-file-pdf-fill';
                        $label = 'Dokumen';
                        break;
                    case 'quiz':
                        $icon = 'bi-question-circle-fill';
                        $label = 'Quiz';
                        break;
                    case 'tugas':
                        $icon = 'bi-clipboard-check-fill';
                        $label = 'Tugas';
                        break;
                }
                ?>
                <i class="bi <?= $icon ?> me-1"></i> <?= $label ?>
                <?php if ($materi['durasi_menit']): ?>
                    <span class="ms-2">
                        <i class="bi bi-clock me-1"></i> <?= $materi['durasi_menit'] ?> menit
                    </span>
                <?php endif; ?>
            </span>
        </div>
    </div>
    <div class="card-body">
        <?php if ($materi['tipe'] == 'video' && !empty($materi['video_url'])): ?>
            <div class="ratio ratio-16x9 mb-4">
                <iframe src="<?= $materi['video_url'] ?>" title="<?= htmlspecialchars($materi['judul']) ?>" allowfullscreen></iframe>
            </div>
        <?php elseif ($materi['tipe'] == 'dokumen' && !empty($materi['file_path'])): ?>
            <div class="mb-4">
                <a href="<?= BASE_URL ?>/uploads/materi/<?= $materi['file_path'] ?>" class="btn btn-outline-primary" target="_blank">
                    <i class="bi bi-file-earmark-arrow-down me-2"></i>Unduh Dokumen
                </a>
            </div>
        <?php endif; ?>

        <div class="materi-content">
            <?= $materi['konten'] ?>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <div>
            <?php if ($progres['status'] === 'selesai'): ?>
                <span class="text-success"><i class="bi bi-check-circle-fill me-2"></i>Sudah Selesai</span>
            <?php else: ?>
                <span class="text-primary"><i class="bi bi-play-fill me-2"></i>Sedang Dipelajari</span>
            <?php endif; ?>
        </div>
        <div>
            <?php if ($materi['tipe'] == 'quiz'): ?>
                <a href="<?= BASE_URL ?>/user/quiz/mulai?id=<?= $materi_id ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-primary">
                    <i class="bi bi-question-circle me-2"></i>Mulai Quiz
                </a>
            <?php elseif ($materi['tipe'] == 'tugas'): ?>
                <a href="<?= BASE_URL ?>/user/tugas/detail?id=<?= $materi_id ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-primary">
                    <i class="bi bi-clipboard-check me-2"></i>Lihat Tugas
                </a>
            <?php elseif ($progres['status'] !== 'selesai'): ?>
                <a href="<?= BASE_URL ?>/user/belajar/selesai?id=<?= $materi_id ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Tandai Selesai
                </a>
            <?php endif; ?>

            <?php if ($next_materi): ?>
                <a href="<?= BASE_URL ?>/user/belajar/materi?id=<?= $next_materi['id'] ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-outline-primary ms-2">
                    Lanjut <i class="bi bi-arrow-right ms-2"></i>
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/user/belajar?kursus_id=<?= $kursus_id ?>" class="btn btn-outline-primary ms-2">
                    Kembali ke Daftar Materi
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($materi['tipe'] !== 'quiz' && $materi['tipe'] !== 'tugas'): ?>
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Diskusi</h5>
        </div>
        <div class="card-body">
            <?php
            $sql_diskusi = "SELECT d.*, p.nama_lengkap, p.foto_profil 
                        FROM diskusi d 
                        JOIN pengguna p ON d.pengguna_id = p.id 
                        WHERE d.materi_id = ? AND d.status = 'aktif' 
                        ORDER BY d.waktu_dibuat DESC";
            $stmt_diskusi = $db->prepare($sql_diskusi);
            $stmt_diskusi->execute([$materi_id]);
            $diskusis = $stmt_diskusi->fetchAll();
            ?>

            <?php if (count($diskusis) > 0): ?>
                <div class="list-group">
                    <?php foreach ($diskusis as $diskusi): ?>
                        <a href="<?= BASE_URL ?>/user/diskusi/detail?id=<?= $diskusi['id'] ?>&kursus_id=<?= $kursus_id ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><?= htmlspecialchars($diskusi['judul']) ?></h6>
                                <small><?= date('d M Y H:i', strtotime($diskusi['waktu_dibuat'])) ?></small>
                            </div>
                            <div class="d-flex align-items-center mt-2">
                                <?php if (!empty($diskusi['foto_profil'])): ?>
                                    <img src="<?= BASE_URL ?>/uploads/profil/<?= $diskusi['foto_profil'] ?>" alt="<?= htmlspecialchars($diskusi['nama_lengkap']) ?>" class="rounded-circle me-2" width="24" height="24" style="object-fit: cover;">
                                <?php else: ?>
                                    <i class="bi bi-person-circle me-2"></i>
                                <?php endif; ?>
                                <small><?= htmlspecialchars($diskusi['nama_lengkap']) ?></small>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-chat-left-text display-4 text-muted"></i>
                    <p class="mt-3">Belum ada diskusi. Mulai diskusi baru?</p>
                </div>
            <?php endif; ?>

            <div class="d-grid gap-2 mt-3">
                <a href="<?= BASE_URL ?>/user/diskusi/tambah?materi_id=<?= $materi_id ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Mulai Diskusi Baru
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require BASE_PATH . '/layouts/user/user-layout.php';
userLayout(htmlspecialchars($materi['judul']), $content, 'kursus');
?>