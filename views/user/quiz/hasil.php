<?php
// Path: views/user/quiz/hasil.php

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/user/login');
    exit;
}

if (!isset($_GET['percobaan_id']) || !isset($_GET['kursus_id'])) {
    header('Location: ' . BASE_URL . '/user/kursus');
    exit;
}

$percobaan_id = $_GET['percobaan_id'];
$kursus_id = $_GET['kursus_id'];
$user_id = $_SESSION['user']['id'];

$db = dbConnect();

$sql_percobaan = "SELECT pq.*, q.id as quiz_id, q.materi_id, q.nilai_lulus, q.judul
                 FROM percobaan_quiz pq 
                 JOIN quiz q ON pq.quiz_id = q.id 
                 JOIN pendaftaran p ON pq.pendaftaran_id = p.id
                 WHERE pq.id = ? AND p.pengguna_id = ? AND p.kursus_id = ?";
$stmt_percobaan = $db->prepare($sql_percobaan);
$stmt_percobaan->execute([$percobaan_id, $user_id, $kursus_id]);
$percobaan = $stmt_percobaan->fetch();

if (!$percobaan) {
    header('Location: ' . BASE_URL . '/user/kursus');
    exit;
}

$sql_materi = "SELECT m.*, mo.judul as modul_judul, k.judul as kursus_judul 
              FROM materi m 
              JOIN modul mo ON m.modul_id = mo.id 
              JOIN kursus k ON mo.kursus_id = k.id 
              WHERE m.id = ?";
$stmt_materi = $db->prepare($sql_materi);
$stmt_materi->execute([$percobaan['materi_id']]);
$materi = $stmt_materi->fetch();

$sql_pertanyaan = "SELECT pq.*, jp.pilihan_jawaban_id, jp.teks_jawaban, jp.benar, jp.nilai as nilai_jawaban
                  FROM pertanyaan_quiz pq
                  LEFT JOIN jawaban_percobaan jp ON pq.id = jp.pertanyaan_id AND jp.percobaan_quiz_id = ?
                  WHERE pq.quiz_id = ?
                  ORDER BY pq.urutan ASC";
$stmt_pertanyaan = $db->prepare($sql_pertanyaan);
$stmt_pertanyaan->execute([$percobaan_id, $percobaan['quiz_id']]);
$pertanyaans = $stmt_pertanyaan->fetchAll();

// Jika status masih sedang dikerjakan tetapi waktu sudah habis
if ($percobaan['status'] === 'sedang dikerjakan' && $percobaan['waktu_selesai'] === null) {
    $sql_update_percobaan = "UPDATE percobaan_quiz 
                            SET waktu_selesai = NOW(), 
                                durasi_detik = TIMESTAMPDIFF(SECOND, waktu_mulai, NOW()), 
                                status = 'waktu habis' 
                            WHERE id = ?";
    $stmt_update_percobaan = $db->prepare($sql_update_percobaan);
    $stmt_update_percobaan->execute([$percobaan_id]);

    // Refresh data percobaan
    $stmt_percobaan->execute([$percobaan_id, $user_id, $kursus_id]);
    $percobaan = $stmt_percobaan->fetch();
}

$total_pertanyaan = count($pertanyaans);
$total_dijawab = 0;
$total_benar = 0;
$total_nilai = 0;
$total_bobot = 0;

foreach ($pertanyaans as $pertanyaan) {
    if ($pertanyaan['pilihan_jawaban_id'] !== null || $pertanyaan['teks_jawaban'] !== null) {
        $total_dijawab++;
    }

    if ($pertanyaan['benar']) {
        $total_benar++;
    }

    $total_nilai += $pertanyaan['nilai_jawaban'] ?? 0;
    $total_bobot += $pertanyaan['bobot_nilai'];
}

$persentase_jawaban = ($total_pertanyaan > 0) ? ($total_dijawab / $total_pertanyaan) * 100 : 0;
$persentase_benar = ($total_dijawab > 0) ? ($total_benar / $total_dijawab) * 100 : 0;
$nilai_akhir = ($total_bobot > 0) ? ($total_nilai / $total_bobot) * 100 : 0;

$lulus = $nilai_akhir >= $percobaan['nilai_lulus'];

// Cek progress materi
$sql_progres = "SELECT * FROM progres_materi 
               WHERE pendaftaran_id = ? AND materi_id = ?";
$stmt_progres = $db->prepare($sql_progres);
$stmt_progres->execute([$percobaan['pendaftaran_id'], $percobaan['materi_id']]);
$progres = $stmt_progres->fetch();

if ($lulus && $progres && $progres['status'] !== 'selesai') {
    $sql_update_progres = "UPDATE progres_materi 
                          SET status = 'selesai', waktu_selesai = NOW() 
                          WHERE id = ?";
    $stmt_update_progres = $db->prepare($sql_update_progres);
    $stmt_update_progres->execute([$progres['id']]);

    // Update progres kursus
    $sql_count_materi = "SELECT COUNT(*) as total FROM materi m 
                         JOIN modul mo ON m.modul_id = mo.id 
                         WHERE mo.kursus_id = ?";
    $stmt_count_materi = $db->prepare($sql_count_materi);
    $stmt_count_materi->execute([$kursus_id]);
    $total_materi = $stmt_count_materi->fetch()['total'];

    $sql_count_selesai = "SELECT COUNT(*) as selesai 
                          FROM progres_materi pm 
                          JOIN pendaftaran p ON pm.pendaftaran_id = p.id 
                          JOIN materi m ON pm.materi_id = m.id 
                          JOIN modul mo ON m.modul_id = mo.id 
                          WHERE p.pengguna_id = ? AND mo.kursus_id = ? AND pm.status = 'selesai'";
    $stmt_count_selesai = $db->prepare($sql_count_selesai);
    $stmt_count_selesai->execute([$user_id, $kursus_id]);
    $total_selesai = $stmt_count_selesai->fetch()['selesai'];

    $progres_persen = ($total_materi > 0) ? ($total_selesai / $total_materi) * 100 : 0;

    $sql_update_pendaftaran = "UPDATE pendaftaran 
                              SET progres_persen = ?, 
                              status = CASE WHEN ? = 100 THEN 'selesai' ELSE status END 
                              WHERE id = ?";
    $stmt_update_pendaftaran = $db->prepare($sql_update_pendaftaran);
    $stmt_update_pendaftaran->execute([$progres_persen, $progres_persen, $percobaan['pendaftaran_id']]);
}

ob_start();
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/kursus">Kursus Saya</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/belajar?kursus_id=<?= $kursus_id ?>"><?= htmlspecialchars($materi['kursus_judul']) ?></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/belajar/materi?id=<?= $materi['id'] ?>&kursus_id=<?= $kursus_id ?>"><?= htmlspecialchars($materi['judul']) ?></a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/quiz?id=<?= $materi['id'] ?>&kursus_id=<?= $kursus_id ?>">Quiz</a></li>
            <li class="breadcrumb-item active" aria-current="page">Hasil Quiz</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white text-center">
                    <h4 class="card-title mb-0">Hasil Quiz: <?= htmlspecialchars($materi['judul']) ?></h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="display-1 mb-3">
                            <?php if ($lulus): ?>
                                <i class="bi bi-check-circle-fill text-success"></i>
                            <?php else: ?>
                                <i class="bi bi-x-circle-fill text-danger"></i>
                            <?php endif; ?>
                        </div>
                        <h3 class="mb-1"><?= number_format($nilai_akhir, 1) ?>%</h3>
                        <p class="mb-3">
                            <?php if ($lulus): ?>
                                <span class="badge bg-success">Lulus</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Tidak Lulus</span>
                            <?php endif; ?>
                        </p>
                        <p class="text-muted">
                            Nilai kelulusan: <?= $percobaan['nilai_lulus'] ?>%
                        </p>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-center mb-3">Ringkasan Quiz</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between bg-transparent px-0">
                                            <span>Status:</span>
                                            <strong>
                                                <?php
                                                switch ($percobaan['status']) {
                                                    case 'selesai':
                                                        echo 'Selesai';
                                                        break;
                                                    case 'waktu habis':
                                                        echo 'Waktu Habis';
                                                        break;
                                                    case 'dibatalkan':
                                                        echo 'Dibatalkan';
                                                        break;
                                                    default:
                                                        echo 'Sedang Dikerjakan';
                                                }
                                                ?>
                                            </strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between bg-transparent px-0">
                                            <span>Waktu Mulai:</span>
                                            <strong><?= date('d M Y, H:i', strtotime($percobaan['waktu_mulai'])) ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between bg-transparent px-0">
                                            <span>Waktu Selesai:</span>
                                            <strong><?= $percobaan['waktu_selesai'] ? date('d M Y, H:i', strtotime($percobaan['waktu_selesai'])) : '-' ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between bg-transparent px-0">
                                            <span>Durasi:</span>
                                            <strong><?= $percobaan['durasi_detik'] ? gmdate('H:i:s', $percobaan['durasi_detik']) : '-' ?></strong>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-center mb-3">Statistik Pertanyaan</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between bg-transparent px-0">
                                            <span>Total Pertanyaan:</span>
                                            <strong><?= $total_pertanyaan ?></strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between bg-transparent px-0">
                                            <span>Dijawab:</span>
                                            <strong><?= $total_dijawab ?> (<?= number_format($persentase_jawaban, 0) ?>%)</strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between bg-transparent px-0">
                                            <span>Jawaban Benar:</span>
                                            <strong><?= $total_benar ?> (<?= number_format($persentase_benar, 0) ?>%)</strong>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between bg-transparent px-0">
                                            <span>Jawaban Salah:</span>
                                            <strong><?= $total_dijawab - $total_benar ?></strong>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="detail-jawaban mt-4">
                        <h5 class="mb-3">Detail Jawaban</h5>
                        <div class="accordion" id="accordionJawaban">
                            <?php foreach ($pertanyaans as $index => $pertanyaan):
                                $is_correct = $pertanyaan['benar'] ? true : false;
                                $is_answered = $pertanyaan['pilihan_jawaban_id'] !== null || $pertanyaan['teks_jawaban'] !== null;

                                $header_class = 'bg-secondary text-white';
                                if ($is_answered) {
                                    $header_class = $is_correct ? 'bg-success text-white' : 'bg-danger text-white';
                                }
                            ?>
                                <div class="accordion-item mb-3 border">
                                    <h2 class="accordion-header" id="heading<?= $index ?>">
                                        <button class="accordion-button collapsed <?= $header_class ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="false" aria-controls="collapse<?= $index ?>">
                                            <div class="d-flex justify-content-between w-100 align-items-center">
                                                <span>Pertanyaan <?= $index + 1 ?></span>
                                                <span>
                                                    <?php if ($is_answered): ?>
                                                        <?php if ($is_correct): ?>
                                                            <i class="bi bi-check-circle-fill me-1"></i> Benar
                                                        <?php else: ?>
                                                            <i class="bi bi-x-circle-fill me-1"></i> Salah
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <i class="bi bi-dash-circle-fill me-1"></i> Tidak Dijawab
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $index ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $index ?>" data-bs-parent="#accordionJawaban">
                                        <div class="accordion-body">
                                            <div class="mb-3">
                                                <h6>Pertanyaan:</h6>
                                                <p><?= nl2br(htmlspecialchars($pertanyaan['pertanyaan'])) ?></p>
                                                <?php if (!empty($pertanyaan['gambar'])): ?>
                                                    <div class="mt-2">
                                                        <img src="<?= BASE_URL ?>/uploads/quiz/<?= $pertanyaan['gambar'] ?>" class="img-fluid" alt="Gambar Pertanyaan">
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($pertanyaan['tipe'] === 'pilihan_ganda' || $pertanyaan['tipe'] === 'benar_salah'):
                                                $sql_pilihan = "SELECT * FROM pilihan_jawaban WHERE pertanyaan_id = ? ORDER BY urutan ASC";
                                                $stmt_pilihan = $db->prepare($sql_pilihan);
                                                $stmt_pilihan->execute([$pertanyaan['id']]);
                                                $pilihans = $stmt_pilihan->fetchAll();
                                            ?>
                                                <div class="mb-3">
                                                    <h6>Pilihan Jawaban:</h6>
                                                    <ul class="list-group">
                                                        <?php foreach ($pilihans as $pilihan):
                                                            $is_selected = $pertanyaan['pilihan_jawaban_id'] === $pilihan['id'];
                                                            $is_correct_choice = $pilihan['benar'];

                                                            $item_class = '';
                                                            if ($is_selected && $is_correct_choice) {
                                                                $item_class = 'list-group-item-success';
                                                            } elseif ($is_selected && !$is_correct_choice) {
                                                                $item_class = 'list-group-item-danger';
                                                            } elseif (!$is_selected && $is_correct_choice) {
                                                                $item_class = 'list-group-item-info';
                                                            }
                                                        ?>
                                                            <li class="list-group-item <?= $item_class ?>">
                                                                <div class="d-flex align-items-center">
                                                                    <?php if ($is_selected): ?>
                                                                        <i class="bi bi-check-circle-fill me-2 <?= $is_correct_choice ? 'text-success' : 'text-danger' ?>"></i>
                                                                    <?php elseif ($is_correct_choice): ?>
                                                                        <i class="bi bi-check-circle me-2 text-info"></i>
                                                                    <?php else: ?>
                                                                        <i class="bi bi-circle me-2"></i>
                                                                    <?php endif; ?>

                                                                    <?= htmlspecialchars($pilihan['teks_jawaban']) ?>

                                                                    <?php if ($is_correct_choice): ?>
                                                                        <span class="badge bg-success ms-2">Jawaban Benar</span>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php elseif ($pertanyaan['tipe'] === 'isian' || $pertanyaan['tipe'] === 'esai'): ?>
                                                <div class="mb-3">
                                                    <h6>Jawaban Anda:</h6>
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <?= $pertanyaan['teks_jawaban'] ? nl2br(htmlspecialchars($pertanyaan['teks_jawaban'])) : '<em class="text-muted">Tidak ada jawaban</em>' ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <div class="mt-3 d-flex justify-content-between">
                                                <span class="badge bg-secondary">Nilai: <?= $pertanyaan['nilai_jawaban'] ?? 0 ?> / <?= $pertanyaan['bobot_nilai'] ?></span>
                                                <span class="badge bg-primary">Bobot: <?= $pertanyaan['bobot_nilai'] ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/user/quiz?id=<?= $materi['id'] ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-2"></i>Kembali ke Quiz
                        </a>
                        <a href="<?= BASE_URL ?>/user/belajar?kursus_id=<?= $kursus_id ?>" class="btn btn-primary">
                            <i class="bi bi-journal-text me-2"></i>Lanjutkan Belajar
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
userLayout('Hasil Quiz - ' . htmlspecialchars($materi['judul']), $content, 'kursus');
?>