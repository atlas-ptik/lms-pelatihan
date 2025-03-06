<?php
// Path: views/user/belajar/selesai.php

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
    $progres_id = generate_uuid();
    $sql_insert_progres = "INSERT INTO progres_materi (id, pendaftaran_id, materi_id, status, waktu_mulai) 
                          VALUES (?, ?, ?, 'sedang dipelajari', NOW())";
    $stmt_insert_progres = $db->prepare($sql_insert_progres);
    $stmt_insert_progres->execute([$progres_id, $pendaftaran_id, $materi_id]);

    $sql_progres = "SELECT * FROM progres_materi WHERE id = ?";
    $stmt_progres = $db->prepare($sql_progres);
    $stmt_progres->execute([$progres_id]);
    $progres = $stmt_progres->fetch();
}

if ($progres['status'] !== 'selesai') {
    $sql_update_progres = "UPDATE progres_materi 
                           SET status = 'selesai', waktu_selesai = NOW(), 
                           durasi_belajar_detik = TIMESTAMPDIFF(SECOND, waktu_mulai, NOW()) 
                           WHERE id = ?";
    $stmt_update_progres = $db->prepare($sql_update_progres);
    $stmt_update_progres->execute([$progres['id']]);

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
    $stmt_update_pendaftaran->execute([$progres_persen, $progres_persen, $pendaftaran_id]);
}

$sql_next = "SELECT m.id, m.judul 
             FROM materi m 
             JOIN modul mo ON m.modul_id = mo.id 
             WHERE mo.kursus_id = ? AND 
             ((mo.id = ? AND m.urutan > ?) OR (mo.urutan > (SELECT urutan FROM modul WHERE id = ?))) 
             ORDER BY mo.urutan ASC, m.urutan ASC 
             LIMIT 1";
$stmt_next = $db->prepare($sql_next);
$stmt_next->execute([$kursus_id, $materi['modul_id'], $materi['urutan'], $materi['modul_id']]);
$next_materi = $stmt_next->fetch();

ob_start();
?>

<div class="text-center py-5">
    <div class="mb-4">
        <i class="bi bi-check-circle-fill text-success display-1"></i>
    </div>
    <h2 class="mb-3">Selamat!</h2>
    <p class="lead">Anda telah menyelesaikan materi <strong><?= htmlspecialchars($materi['judul']) ?></strong>.</p>

    <div class="card mt-4 mx-auto" style="max-width: 500px;">
        <div class="card-body">
            <h5 class="card-title">Ringkasan Progres</h5>

            <?php
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
            ?>

            <div class="progress mb-3">
                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $progres_persen ?>%;" aria-valuenow="<?= $progres_persen ?>" aria-valuemin="0" aria-valuemax="100"><?= number_format($progres_persen, 0) ?>%</div>
            </div>

            <p>Anda telah menyelesaikan <strong><?= $total_selesai ?></strong> dari <strong><?= $total_materi ?></strong> materi.</p>

            <?php if ($progres_persen == 100): ?>
                <div class="alert alert-success mb-3">
                    <i class="bi bi-trophy-fill me-2"></i>
                    Selamat! Anda telah menyelesaikan seluruh materi dalam kursus ini.
                </div>

                <?php
                $sql_check_sertifikat = "SELECT id FROM sertifikat WHERE pendaftaran_id = ?";
                $stmt_check_sertifikat = $db->prepare($sql_check_sertifikat);
                $stmt_check_sertifikat->execute([$pendaftaran_id]);
                $sertifikat = $stmt_check_sertifikat->fetch();

                if (!$sertifikat):
                ?>
                    <a href="<?= BASE_URL ?>/user/sertifikat" class="btn btn-success w-100">
                        <i class="bi bi-award me-2"></i>Lihat Sertifikat
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/user/kursus" class="btn btn-primary w-100">
                        <i class="bi bi-book me-2"></i>Jelajahi Kursus Lainnya
                    </a>
                <?php endif; ?>
            <?php elseif ($next_materi): ?>
                <a href="<?= BASE_URL ?>/user/belajar/materi?id=<?= $next_materi['id'] ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-primary w-100">
                    <i class="bi bi-arrow-right me-2"></i>Lanjutkan ke Materi Berikutnya
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/user/belajar?kursus_id=<?= $kursus_id ?>" class="btn btn-primary w-100">
                    <i class="bi bi-list-check me-2"></i>Kembali ke Daftar Materi
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4">
        <a href="<?= BASE_URL ?>/user/belajar?kursus_id=<?= $kursus_id ?>" class="btn btn-outline-primary">
            <i class="bi bi-list-check me-2"></i>Lihat Semua Materi
        </a>
        <a href="<?= BASE_URL ?>/user/kursus/detail?id=<?= $kursus_id ?>" class="btn btn-outline-primary ms-2">
            <i class="bi bi-info-circle me-2"></i>Detail Kursus
        </a>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/layouts/user/user-layout.php';
userLayout('Materi Selesai', $content, 'kursus');
?>