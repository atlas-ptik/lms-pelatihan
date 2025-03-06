<?php
// Path: views/user/quiz/index.php

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

$sql_materi = "SELECT m.*, mo.judul as modul_judul, k.judul as kursus_judul 
               FROM materi m 
               JOIN modul mo ON m.modul_id = mo.id 
               JOIN kursus k ON mo.kursus_id = k.id 
               WHERE m.id = ? AND mo.kursus_id = ? AND m.tipe = 'quiz'";
$stmt_materi = $db->prepare($sql_materi);
$stmt_materi->execute([$materi_id, $kursus_id]);
$materi = $stmt_materi->fetch();

if (!$materi) {
    header('Location: ' . BASE_URL . '/user/belajar?kursus_id=' . $kursus_id);
    exit;
}

$sql_quiz = "SELECT * FROM quiz WHERE materi_id = ?";
$stmt_quiz = $db->prepare($sql_quiz);
$stmt_quiz->execute([$materi_id]);
$quiz = $stmt_quiz->fetch();

if (!$quiz) {
    header('Location: ' . BASE_URL . '/user/belajar/materi?id=' . $materi_id . '&kursus_id=' . $kursus_id);
    exit;
}

$sql_percobaan = "SELECT * FROM percobaan_quiz WHERE pendaftaran_id = ? AND quiz_id = ? ORDER BY waktu_mulai DESC";
$stmt_percobaan = $db->prepare($sql_percobaan);
$stmt_percobaan->execute([$pendaftaran_id, $quiz['id']]);
$percobaanList = $stmt_percobaan->fetchAll();

$sql_pertanyaan = "SELECT COUNT(*) as total FROM pertanyaan_quiz WHERE quiz_id = ?";
$stmt_pertanyaan = $db->prepare($sql_pertanyaan);
$stmt_pertanyaan->execute([$quiz['id']]);
$total_pertanyaan = $stmt_pertanyaan->fetch()['total'];

$sedang_mengerjakan = false;
foreach ($percobaanList as $percobaan) {
    if ($percobaan['status'] === 'sedang dikerjakan') {
        $sedang_mengerjakan = true;
        break;
    }
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
                <i class="bi bi-question-circle-fill me-1"></i> Quiz
                <?php if ($materi['durasi_menit']): ?>
                    <span class="ms-2">
                        <i class="bi bi-clock me-1"></i> <?= $materi['durasi_menit'] ?> menit
                    </span>
                <?php endif; ?>
            </span>
        </div>
    </div>
    <div class="card-body">
        <div class="quiz-details mb-4">
            <div class="row">
                <div class="col-md-6">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><i class="bi bi-question-circle me-2"></i>Jumlah Pertanyaan:</span>
                            <strong><?= $total_pertanyaan ?> pertanyaan</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><i class="bi bi-clock me-2"></i>Durasi:</span>
                            <strong><?= $quiz['durasi_menit'] ?? 'Tidak dibatasi' ?> <?= $quiz['durasi_menit'] ? 'menit' : '' ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><i class="bi bi-award me-2"></i>Nilai Kelulusan:</span>
                            <strong><?= $quiz['nilai_lulus'] ?>%</strong>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><i class="bi bi-shuffle me-2"></i>Pertanyaan Acak:</span>
                            <strong><?= $quiz['acak_pertanyaan'] ? 'Ya' : 'Tidak' ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><i class="bi bi-arrow-repeat me-2"></i>Maksimal Percobaan:</span>
                            <strong><?= $quiz['maksimal_percobaan'] ?? 'Tidak terbatas' ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><i class="bi bi-file-text me-2"></i>Deskripsi:</span>
                            <strong><?= $quiz['deskripsi'] ? 'Tersedia' : 'Tidak tersedia' ?></strong>
                        </li>
                    </ul>
                </div>
            </div>

            <?php if (!empty($quiz['deskripsi'])): ?>
                <div class="alert alert-light mt-3">
                    <h6><i class="bi bi-info-circle me-2"></i>Deskripsi:</h6>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($quiz['deskripsi'])) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <?php if (count($percobaanList) > 0): ?>
            <div class="quiz-history mb-4">
                <h5 class="mb-3">Riwayat Percobaan</h5>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Waktu Mulai</th>
                                <th>Waktu Selesai</th>
                                <th>Durasi</th>
                                <th>Nilai</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($percobaanList as $index => $percobaan):
                                $status_class = '';
                                $status_text = '';

                                switch ($percobaan['status']) {
                                    case 'sedang dikerjakan':
                                        $status_class = 'primary';
                                        $status_text = 'Sedang Dikerjakan';
                                        break;
                                    case 'selesai':
                                        if ($percobaan['nilai'] >= $quiz['nilai_lulus']) {
                                            $status_class = 'success';
                                            $status_text = 'Lulus';
                                        } else {
                                            $status_class = 'danger';
                                            $status_text = 'Tidak Lulus';
                                        }
                                        break;
                                    case 'waktu habis':
                                        $status_class = 'warning';
                                        $status_text = 'Waktu Habis';
                                        break;
                                    case 'dibatalkan':
                                        $status_class = 'secondary';
                                        $status_text = 'Dibatalkan';
                                        break;
                                }
                            ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= date('d M Y, H:i', strtotime($percobaan['waktu_mulai'])) ?></td>
                                    <td><?= $percobaan['waktu_selesai'] ? date('d M Y, H:i', strtotime($percobaan['waktu_selesai'])) : '-' ?></td>
                                    <td><?= $percobaan['durasi_detik'] ? gmdate('H:i:s', $percobaan['durasi_detik']) : '-' ?></td>
                                    <td><?= $percobaan['nilai'] !== null ? number_format($percobaan['nilai'], 1) : '-' ?></td>
                                    <td><span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span></td>
                                    <td>
                                        <?php if ($percobaan['status'] === 'sedang dikerjakan'): ?>
                                            <a href="<?= BASE_URL ?>/user/quiz/jawab?percobaan_id=<?= $percobaan['id'] ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil me-1"></i>Lanjutkan
                                            </a>
                                        <?php elseif ($percobaan['status'] === 'selesai' || $percobaan['status'] === 'waktu habis'): ?>
                                            <a href="<?= BASE_URL ?>/user/quiz/hasil?percobaan_id=<?= $percobaan['id'] ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye me-1"></i>Lihat Hasil
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Tidak tersedia</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="text-center">
            <?php if ($sedang_mengerjakan): ?>
                <?php foreach ($percobaanList as $percobaan): ?>
                    <?php if ($percobaan['status'] === 'sedang dikerjakan'): ?>
                        <a href="<?= BASE_URL ?>/user/quiz/jawab?percobaan_id=<?= $percobaan['id'] ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-pencil me-2"></i>Lanjutkan Quiz
                        </a>
                        <div class="mt-2 text-muted">
                            Anda masih memiliki quiz yang sedang dikerjakan
                        </div>
                        <?php break; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <?php
                $percobaan_count = count($percobaanList);
                $can_attempt = true;

                if ($quiz['maksimal_percobaan'] !== null && $percobaan_count >= $quiz['maksimal_percobaan']) {
                    $can_attempt = false;
                }

                $percobaan_lulus = false;
                foreach ($percobaanList as $percobaan) {
                    if ($percobaan['status'] === 'selesai' && $percobaan['nilai'] >= $quiz['nilai_lulus']) {
                        $percobaan_lulus = true;
                        break;
                    }
                }
                ?>

                <?php if ($can_attempt): ?>
                    <a href="<?= BASE_URL ?>/user/quiz/mulai?id=<?= $quiz['id'] ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-primary btn-lg">
                        <i class="bi bi-play-circle me-2"></i>Mulai Quiz
                    </a>

                    <?php if ($quiz['maksimal_percobaan'] !== null): ?>
                        <div class="mt-2 text-muted">
                            Percobaan: <?= $percobaan_count ?>/<?= $quiz['maksimal_percobaan'] ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Anda telah mencapai batas maksimal percobaan (<?= $quiz['maksimal_percobaan'] ?> kali).
                    </div>
                <?php endif; ?>

                <?php if ($percobaan_lulus): ?>
                    <div class="alert alert-success mt-3">
                        <i class="bi bi-check-circle me-2"></i>
                        Anda telah lulus quiz ini!
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-footer bg-white">
        <a href="<?= BASE_URL ?>/user/belajar/materi?id=<?= $materi_id ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left me-2"></i>Kembali ke Materi
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/layouts/user/user-layout.php';
userLayout('Quiz: ' . htmlspecialchars($materi['judul']), $content, 'kursus');
?>