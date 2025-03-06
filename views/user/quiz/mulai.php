<?php
// Path: views/user/quiz/mulai.php

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/user/login');
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['kursus_id'])) {
    header('Location: ' . BASE_URL . '/user/kursus');
    exit;
}

$quiz_id = $_GET['id'];
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

$sql_quiz = "SELECT q.*, m.judul as materi_judul, m.id as materi_id 
             FROM quiz q 
             JOIN materi m ON q.materi_id = m.id 
             WHERE q.id = ?";
$stmt_quiz = $db->prepare($sql_quiz);
$stmt_quiz->execute([$quiz_id]);
$quiz = $stmt_quiz->fetch();

if (!$quiz) {
    header('Location: ' . BASE_URL . '/user/belajar?kursus_id=' . $kursus_id);
    exit;
}

$sql_sedang_mengerjakan = "SELECT * FROM percobaan_quiz 
                           WHERE pendaftaran_id = ? AND quiz_id = ? AND status = 'sedang dikerjakan'";
$stmt_sedang_mengerjakan = $db->prepare($sql_sedang_mengerjakan);
$stmt_sedang_mengerjakan->execute([$pendaftaran_id, $quiz_id]);
$sedang_mengerjakan = $stmt_sedang_mengerjakan->fetch();

if ($sedang_mengerjakan) {
    header('Location: ' . BASE_URL . '/user/quiz/jawab?percobaan_id=' . $sedang_mengerjakan['id'] . '&kursus_id=' . $kursus_id);
    exit;
}

$sql_percobaan = "SELECT COUNT(*) as total FROM percobaan_quiz 
                 WHERE pendaftaran_id = ? AND quiz_id = ?";
$stmt_percobaan = $db->prepare($sql_percobaan);
$stmt_percobaan->execute([$pendaftaran_id, $quiz_id]);
$total_percobaan = $stmt_percobaan->fetch()['total'];

if ($quiz['maksimal_percobaan'] !== null && $total_percobaan >= $quiz['maksimal_percobaan']) {
    header('Location: ' . BASE_URL . '/user/quiz?id=' . $quiz['materi_id'] . '&kursus_id=' . $kursus_id);
    exit;
}

$sql_pertanyaan = "SELECT COUNT(*) as total FROM pertanyaan_quiz WHERE quiz_id = ?";
$stmt_pertanyaan = $db->prepare($sql_pertanyaan);
$stmt_pertanyaan->execute([$quiz_id]);
$total_pertanyaan = $stmt_pertanyaan->fetch()['total'];

if (isset($_POST['mulai'])) {
    $percobaan_id = generate_uuid();
    $sql_insert = "INSERT INTO percobaan_quiz (id, pendaftaran_id, quiz_id, waktu_mulai, status) 
                  VALUES (?, ?, ?, NOW(), 'sedang dikerjakan')";
    $stmt_insert = $db->prepare($sql_insert);
    $stmt_insert->execute([$percobaan_id, $pendaftaran_id, $quiz_id]);

    header('Location: ' . BASE_URL . '/user/quiz/jawab?percobaan_id=' . $percobaan_id . '&kursus_id=' . $kursus_id);
    exit;
}

ob_start();
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h4 class="card-title mb-0">Persiapan Quiz: <?= htmlspecialchars($quiz['materi_judul']) ?></h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5 class="alert-heading"><i class="bi bi-info-circle me-2"></i>Informasi Quiz</h5>
                        <ul class="mb-0">
                            <li>Quiz ini berisi <strong><?= $total_pertanyaan ?> pertanyaan</strong>.</li>
                            <li>Durasi waktu pengerjaan: <strong><?= $quiz['durasi_menit'] ?? 'Tidak dibatasi' ?> <?= $quiz['durasi_menit'] ? 'menit' : '' ?></strong>.</li>
                            <li>Nilai minimal kelulusan: <strong><?= $quiz['nilai_lulus'] ?>%</strong>.</li>
                            <?php if ($quiz['maksimal_percobaan']): ?>
                                <li>Maksimal percobaan: <strong><?= $quiz['maksimal_percobaan'] ?> kali</strong> (Anda telah mencoba <strong><?= $total_percobaan ?></strong> kali).</li>
                            <?php endif; ?>
                            <?php if ($quiz['acak_pertanyaan']): ?>
                                <li>Pertanyaan akan ditampilkan secara acak.</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <?php if (!empty($quiz['deskripsi'])): ?>
                        <div class="mb-4">
                            <h5>Deskripsi Quiz</h5>
                            <p><?= nl2br(htmlspecialchars($quiz['deskripsi'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-warning">
                        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Perhatian</h5>
                        <ul class="mb-0">
                            <li>Pastikan koneksi internet Anda stabil selama mengerjakan quiz.</li>
                            <li>Jangan menutup halaman browser saat quiz sedang berlangsung.</li>
                            <li>Jawaban akan otomatis tersimpan setiap kali Anda berpindah ke pertanyaan lain.</li>
                            <?php if ($quiz['durasi_menit']): ?>
                                <li>Jawaban akan otomatis dikumpulkan saat waktu habis.</li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <form method="post" class="text-center mt-4">
                        <div class="d-grid gap-2">
                            <button type="submit" name="mulai" class="btn btn-primary btn-lg">
                                <i class="bi bi-play-circle me-2"></i>Mulai Quiz Sekarang
                            </button>
                            <a href="<?= BASE_URL ?>/user/quiz?id=<?= $quiz['materi_id'] ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/layouts/user/user-layout.php';
userLayout('Mulai Quiz - ' . htmlspecialchars($quiz['materi_judul']), $content, 'kursus');
?>