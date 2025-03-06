<?php
// Path: views/user/quiz/jawab.php

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

$sql_percobaan = "SELECT pq.*, q.materi_id, q.durasi_menit, q.acak_pertanyaan 
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

if ($percobaan['status'] !== 'sedang dikerjakan') {
    header('Location: ' . BASE_URL . '/user/quiz/hasil?percobaan_id=' . $percobaan_id . '&kursus_id=' . $kursus_id);
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

$sql_quiz = "SELECT * FROM quiz WHERE materi_id = ?";
$stmt_quiz = $db->prepare($sql_quiz);
$stmt_quiz->execute([$percobaan['materi_id']]);
$quiz = $stmt_quiz->fetch();

$sql_pertanyaan = "SELECT * FROM pertanyaan_quiz WHERE quiz_id = ? ORDER BY " .
    ($quiz['acak_pertanyaan'] ? "RAND()" : "urutan ASC");
$stmt_pertanyaan = $db->prepare($sql_pertanyaan);
$stmt_pertanyaan->execute([$quiz['id']]);
$pertanyaans = $stmt_pertanyaan->fetchAll();

$current_index = isset($_GET['q']) ? (int) $_GET['q'] : 0;
if ($current_index < 0 || $current_index >= count($pertanyaans)) {
    $current_index = 0;
}

$current_pertanyaan = $pertanyaans[$current_index];

$sql_jawaban = "SELECT * FROM jawaban_percobaan 
               WHERE percobaan_quiz_id = ? AND pertanyaan_id = ?";
$stmt_jawaban = $db->prepare($sql_jawaban);
$stmt_jawaban->execute([$percobaan_id, $current_pertanyaan['id']]);
$jawaban_user = $stmt_jawaban->fetch();

$sql_pilihan = "SELECT * FROM pilihan_jawaban 
               WHERE pertanyaan_id = ? 
               ORDER BY " . ($quiz['acak_pertanyaan'] ? "RAND()" : "urutan ASC");
$stmt_pilihan = $db->prepare($sql_pilihan);
$stmt_pilihan->execute([$current_pertanyaan['id']]);
$pilihans = $stmt_pilihan->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_jawaban'])) {
    $pilihan_id = isset($_POST['jawaban']) ? $_POST['jawaban'] : null;
    $teks_jawaban = isset($_POST['teks_jawaban']) ? $_POST['teks_jawaban'] : null;

    $benar = null;
    $nilai = null;

    if ($current_pertanyaan['tipe'] === 'pilihan_ganda' || $current_pertanyaan['tipe'] === 'benar_salah') {
        if ($pilihan_id) {
            $sql_cek_benar = "SELECT benar FROM pilihan_jawaban WHERE id = ?";
            $stmt_cek_benar = $db->prepare($sql_cek_benar);
            $stmt_cek_benar->execute([$pilihan_id]);
            $result = $stmt_cek_benar->fetch();

            if ($result) {
                $benar = $result['benar'] ? 1 : 0;
                $nilai = $benar ? $current_pertanyaan['bobot_nilai'] : 0;
            }
        }
    }

    if ($jawaban_user) {
        $sql_update = "UPDATE jawaban_percobaan 
                      SET pilihan_jawaban_id = ?, teks_jawaban = ?, benar = ?, nilai = ?, waktu_dijawab = NOW() 
                      WHERE id = ?";
        $stmt_update = $db->prepare($sql_update);
        $stmt_update->execute([$pilihan_id, $teks_jawaban, $benar, $nilai, $jawaban_user['id']]);
    } else {
        $jawaban_id = generate_uuid();
        $sql_insert = "INSERT INTO jawaban_percobaan 
                      (id, percobaan_quiz_id, pertanyaan_id, pilihan_jawaban_id, teks_jawaban, benar, nilai, waktu_dijawab) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt_insert = $db->prepare($sql_insert);
        $stmt_insert->execute([
            $jawaban_id,
            $percobaan_id,
            $current_pertanyaan['id'],
            $pilihan_id,
            $teks_jawaban,
            $benar,
            $nilai
        ]);
    }

    if (isset($_POST['next'])) {
        if ($current_index < count($pertanyaans) - 1) {
            header('Location: ' . BASE_URL . '/user/quiz/jawab?percobaan_id=' . $percobaan_id . '&kursus_id=' . $kursus_id . '&q=' . ($current_index + 1));
        } else {
            header('Location: ' . BASE_URL . '/user/quiz/jawab?percobaan_id=' . $percobaan_id . '&kursus_id=' . $kursus_id . '&q=' . $current_index);
        }
        exit;
    } elseif (isset($_POST['prev'])) {
        if ($current_index > 0) {
            header('Location: ' . BASE_URL . '/user/quiz/jawab?percobaan_id=' . $percobaan_id . '&kursus_id=' . $kursus_id . '&q=' . ($current_index - 1));
        } else {
            header('Location: ' . BASE_URL . '/user/quiz/jawab?percobaan_id=' . $percobaan_id . '&kursus_id=' . $kursus_id . '&q=' . $current_index);
        }
        exit;
    } elseif (isset($_POST['finish'])) {
        $sql_count_jawaban = "SELECT COUNT(*) as total FROM jawaban_percobaan 
                              WHERE percobaan_quiz_id = ?";
        $stmt_count_jawaban = $db->prepare($sql_count_jawaban);
        $stmt_count_jawaban->execute([$percobaan_id]);
        $total_jawaban = $stmt_count_jawaban->fetch()['total'];

        $sql_sum_nilai = "SELECT COALESCE(SUM(nilai), 0) as total_nilai FROM jawaban_percobaan 
                         WHERE percobaan_quiz_id = ?";
        $stmt_sum_nilai = $db->prepare($sql_sum_nilai);
        $stmt_sum_nilai->execute([$percobaan_id]);
        $total_nilai = $stmt_sum_nilai->fetch()['total_nilai'];

        $sql_sum_bobot = "SELECT COALESCE(SUM(pq.bobot_nilai), 0) as total_bobot 
                         FROM pertanyaan_quiz pq 
                         WHERE pq.quiz_id = ?";
        $stmt_sum_bobot = $db->prepare($sql_sum_bobot);
        $stmt_sum_bobot->execute([$quiz['id']]);
        $total_bobot = $stmt_sum_bobot->fetch()['total_bobot'];

        $nilai_akhir = ($total_bobot > 0) ? ($total_nilai / $total_bobot) * 100 : 0;

        $sql_update_percobaan = "UPDATE percobaan_quiz 
                                SET waktu_selesai = NOW(), 
                                    durasi_detik = TIMESTAMPDIFF(SECOND, waktu_mulai, NOW()), 
                                    nilai = ?, 
                                    status = 'selesai' 
                                WHERE id = ?";
        $stmt_update_percobaan = $db->prepare($sql_update_percobaan);
        $stmt_update_percobaan->execute([$nilai_akhir, $percobaan_id]);

        $sql_update_progress = "SELECT id FROM progres_materi 
                               WHERE pendaftaran_id = ? AND materi_id = ?";
        $stmt_update_progress = $db->prepare($sql_update_progress);
        $stmt_update_progress->execute([$percobaan['pendaftaran_id'], $percobaan['materi_id']]);
        $progress = $stmt_update_progress->fetch();

        if ($progress) {
            if ($nilai_akhir >= $quiz['nilai_lulus']) {
                $sql_update_progress = "UPDATE progres_materi 
                                       SET status = 'selesai', waktu_selesai = NOW() 
                                       WHERE id = ?";
                $stmt_update_progress = $db->prepare($sql_update_progress);
                $stmt_update_progress->execute([$progress['id']]);

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
        }

        header('Location: ' . BASE_URL . '/user/quiz/hasil?percobaan_id=' . $percobaan_id . '&kursus_id=' . $kursus_id);
        exit;
    }
}

$sql_count_jawaban = "SELECT COUNT(*) as total FROM jawaban_percobaan 
                      WHERE percobaan_quiz_id = ?";
$stmt_count_jawaban = $db->prepare($sql_count_jawaban);
$stmt_count_jawaban->execute([$percobaan_id]);
$total_jawaban = $stmt_count_jawaban->fetch()['total'];

$waktu_deadline = null;
if ($percobaan['durasi_menit']) {
    $waktu_mulai = new DateTime($percobaan['waktu_mulai']);
    $durasi_menit = $percobaan['durasi_menit'];
    $waktu_deadline = clone $waktu_mulai;
    $waktu_deadline->add(new DateInterval("PT{$durasi_menit}M"));
}

ob_start();
?>

<div class="container-fluid py-3">
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Pertanyaan <?= $current_index + 1 ?> dari <?= count($pertanyaans) ?></h5>
                    <?php if ($percobaan['durasi_menit']): ?>
                        <div class="timer d-flex align-items-center">
                            <i class="bi bi-clock me-2"></i>
                            <span id="countdown">Memuat...</span>
                            <input type="hidden" id="deadline" value="<?= $waktu_deadline ? $waktu_deadline->format('Y-m-d H:i:s') : '' ?>">
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="pertanyaan mb-4">
                            <h5><?= nl2br(htmlspecialchars($current_pertanyaan['pertanyaan'])) ?></h5>
                            <?php if (!empty($current_pertanyaan['gambar'])): ?>
                                <div class="mt-2">
                                    <img src="<?= BASE_URL ?>/uploads/quiz/<?= $current_pertanyaan['gambar'] ?>" class="img-fluid" alt="Gambar Pertanyaan">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="jawaban mb-4">
                            <?php if ($current_pertanyaan['tipe'] === 'pilihan_ganda'): ?>
                                <?php foreach ($pilihans as $pilihan): ?>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="jawaban" id="pilihan_<?= $pilihan['id'] ?>" value="<?= $pilihan['id'] ?>" <?= ($jawaban_user && $jawaban_user['pilihan_jawaban_id'] === $pilihan['id']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="pilihan_<?= $pilihan['id'] ?>">
                                            <?= htmlspecialchars($pilihan['teks_jawaban']) ?>
                                            <?php if (!empty($pilihan['gambar'])): ?>
                                                <div class="mt-2">
                                                    <img src="<?= BASE_URL ?>/uploads/quiz/<?= $pilihan['gambar'] ?>" class="img-fluid" style="max-width: 200px;" alt="Gambar Pilihan">
                                                </div>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php elseif ($current_pertanyaan['tipe'] === 'benar_salah'): ?>
                                <?php foreach ($pilihans as $pilihan): ?>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="jawaban" id="pilihan_<?= $pilihan['id'] ?>" value="<?= $pilihan['id'] ?>" <?= ($jawaban_user && $jawaban_user['pilihan_jawaban_id'] === $pilihan['id']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="pilihan_<?= $pilihan['id'] ?>">
                                            <?= htmlspecialchars($pilihan['teks_jawaban']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php elseif ($current_pertanyaan['tipe'] === 'isian' || $current_pertanyaan['tipe'] === 'esai'): ?>
                                <div class="form-group">
                                    <textarea class="form-control" name="teks_jawaban" rows="4" placeholder="Masukkan jawaban Anda di sini..."><?= $jawaban_user ? htmlspecialchars($jawaban_user['teks_jawaban']) : '' ?></textarea>
                                </div>
                            <?php elseif ($current_pertanyaan['tipe'] === 'menjodohkan'): ?>
                                <div class="alert alert-info">
                                    Jenis pertanyaan menjodohkan tidak tersedia saat ini.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" name="simpan_jawaban" class="btn btn-primary" value="simpan">
                                <i class="bi bi-save me-2"></i>Simpan Jawaban
                            </button>

                            <div>
                                <?php if ($current_index > 0): ?>
                                    <button type="submit" name="simpan_jawaban" class="btn btn-outline-primary" value="prev">
                                        <i class="bi bi-arrow-left me-2"></i>Sebelumnya
                                    </button>
                                <?php endif; ?>

                                <?php if ($current_index < count($pertanyaans) - 1): ?>
                                    <button type="submit" name="simpan_jawaban" class="btn btn-outline-primary ms-2" value="next">
                                        Selanjutnya<i class="bi bi-arrow-right ms-2"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" name="simpan_jawaban" class="btn btn-success" value="finish" onclick="return confirm('Apakah Anda yakin ingin menyelesaikan quiz ini? Pastikan semua pertanyaan telah dijawab.');">
                                <i class="bi bi-check-circle me-2"></i>Selesaikan Quiz
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Navigasi Pertanyaan</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <?php
                        for ($i = 0; $i < count($pertanyaans); $i++):
                            $sql_cek_jawaban = "SELECT * FROM jawaban_percobaan 
                                               WHERE percobaan_quiz_id = ? AND pertanyaan_id = ?";
                            $stmt_cek_jawaban = $db->prepare($sql_cek_jawaban);
                            $stmt_cek_jawaban->execute([$percobaan_id, $pertanyaans[$i]['id']]);
                            $sudah_dijawab = $stmt_cek_jawaban->fetch();

                            $btn_class = 'btn-outline-secondary';
                            if ($i === $current_index) {
                                $btn_class = 'btn-primary';
                            } elseif ($sudah_dijawab) {
                                $btn_class = 'btn-success';
                            }
                        ?>
                            <a href="<?= BASE_URL ?>/user/quiz/jawab?percobaan_id=<?= $percobaan_id ?>&kursus_id=<?= $kursus_id ?>&q=<?= $i ?>" class="btn <?= $btn_class ?> btn-sm" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <?= $i + 1 ?>
                            </a>
                        <?php endfor; ?>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <div>
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-success me-2" style="width: 20px; height: 20px;"></span>
                                <small>Sudah dijawab</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-primary me-2" style="width: 20px; height: 20px;"></span>
                                <small>Sedang dikerjakan</small>
                            </div>
                        </div>
                        <div>
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-outline-secondary border me-2" style="width: 20px; height: 20px;"></span>
                                <small>Belum dijawab</small>
                            </div>
                            <div class="d-flex align-items-center">
                                <small><strong>Dijawab:</strong> <?= $total_jawaban ?>/<?= count($pertanyaans) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white">
                    <button type="button" class="btn btn-success btn-block w-100" onclick="if(confirm('Apakah Anda yakin ingin menyelesaikan quiz ini? Pastikan semua pertanyaan telah dijawab.')) { document.querySelector('button[value=finish]').click(); }">
                        <i class="bi bi-check-circle me-2"></i>Selesaikan Quiz
                    </button>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Info Quiz</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Judul:</span>
                            <strong><?= htmlspecialchars($materi['judul']) ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Jumlah Pertanyaan:</span>
                            <strong><?= count($pertanyaans) ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Pertanyaan Dijawab:</span>
                            <strong><?= $total_jawaban ?>/<?= count($pertanyaans) ?></strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span>Nilai Kelulusan:</span>
                            <strong><?= $quiz['nilai_lulus'] ?>%</strong>
                        </li>
                        <?php if ($percobaan['durasi_menit']): ?>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span>Waktu Mulai:</span>
                                <strong><?= date('H:i:s', strtotime($percobaan['waktu_mulai'])) ?></strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span>Batas Waktu:</span>
                                <strong><?= $waktu_deadline ? $waktu_deadline->format('H:i:s') : '-' ?></strong>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($percobaan['durasi_menit']): ?>
            const deadlineStr = document.getElementById('deadline').value;
            if (deadlineStr) {
                const deadline = new Date(deadlineStr).getTime();

                const countdown = setInterval(function() {
                    const now = new Date().getTime();
                    const distance = deadline - now;

                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                    const countdownElement = document.getElementById('countdown');
                    if (countdownElement) {
                        if (distance < 0) {
                            clearInterval(countdown);
                            countdownElement.innerHTML = "Waktu Habis!";
                            document.querySelector('button[value=finish]').click();
                        } else {
                            countdownElement.innerHTML = (hours > 0 ? hours + ":" : "") +
                                (minutes < 10 ? "0" : "") + minutes + ":" +
                                (seconds < 10 ? "0" : "") + seconds;

                            if (distance < 60000) { // Less than 1 minute
                                countdownElement.classList.add('text-danger');
                                countdownElement.classList.add('fw-bold');
                            } else if (distance < 300000) { // Less than 5 minutes
                                countdownElement.classList.add('text-warning');
                            }
                        }
                    }
                }, 1000);
            }
        <?php endif; ?>
    });
</script>

<?php
$content = ob_get_clean();
require BASE_PATH . '/layouts/user/user-layout.php';
userLayout('Quiz: ' . htmlspecialchars($materi['judul']), $content, 'kursus');
?>