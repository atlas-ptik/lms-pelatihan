<?php
// Path: views/user/belajar/index.php

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/user/login');
    exit;
}

if (!isset($_GET['kursus_id'])) {
    header('Location: ' . BASE_URL . '/user/kursus');
    exit;
}

$kursus_id = $_GET['kursus_id'];
$user_id = $_SESSION['user']['id'];

$db = dbConnect();

$sql_kursus = "SELECT k.*, p.status, p.progres_persen, p.id as pendaftaran_id 
               FROM kursus k 
               JOIN pendaftaran p ON k.id = p.kursus_id 
               WHERE k.id = ? AND p.pengguna_id = ?";
$stmt_kursus = $db->prepare($sql_kursus);
$stmt_kursus->execute([$kursus_id, $user_id]);
$kursus = $stmt_kursus->fetch();

if (!$kursus) {
    header('Location: ' . BASE_URL . '/user/kursus');
    exit;
}

$sql_modul = "SELECT * FROM modul WHERE kursus_id = ? ORDER BY urutan ASC";
$stmt_modul = $db->prepare($sql_modul);
$stmt_modul->execute([$kursus_id]);
$moduls = $stmt_modul->fetchAll();

ob_start();
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title"><?= htmlspecialchars($kursus['judul']) ?></h4>
                <div class="progress mb-3">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $kursus['progres_persen'] ?>%;" aria-valuenow="<?= $kursus['progres_persen'] ?>" aria-valuemin="0" aria-valuemax="100"><?= number_format($kursus['progres_persen'], 0) ?>%</div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="badge rounded-pill bg-<?= ($kursus['status'] == 'aktif') ? 'success' : (($kursus['status'] == 'selesai') ? 'primary' : 'secondary') ?>">
                        <?= ($kursus['status'] == 'aktif') ? 'Sedang Berlangsung' : (($kursus['status'] == 'selesai') ? 'Selesai' : 'Dibatalkan') ?>
                    </span>
                    <a href="<?= BASE_URL ?>/user/kursus/detail?id=<?= $kursus_id ?>" class="btn btn-sm btn-outline-primary">Kembali ke Detail Kursus</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="accordion" id="moduleAccordion">
            <?php foreach ($moduls as $index => $modul):
                $sql_materi = "SELECT m.*, 
                               (SELECT pm.status 
                                FROM progres_materi pm 
                                JOIN pendaftaran p ON pm.pendaftaran_id = p.id 
                                WHERE pm.materi_id = m.id AND p.pengguna_id = ? AND p.kursus_id = ?) as status_materi
                               FROM materi m 
                               WHERE m.modul_id = ? 
                               ORDER BY m.urutan ASC";
                $stmt_materi = $db->prepare($sql_materi);
                $stmt_materi->execute([$user_id, $kursus_id, $modul['id']]);
                $materis = $stmt_materi->fetchAll();

                $total_materi = count($materis);
                $selesai = 0;
                foreach ($materis as $materi) {
                    if ($materi['status_materi'] == 'selesai') {
                        $selesai++;
                    }
                }
                $progress = ($total_materi > 0) ? ($selesai / $total_materi) * 100 : 0;
            ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?= $index ?>">
                        <button class="accordion-button <?= ($index > 0) ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="<?= ($index == 0) ? 'true' : 'false' ?>" aria-controls="collapse<?= $index ?>">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <span><?= htmlspecialchars($modul['judul']) ?></span>
                                <div class="d-flex align-items-center">
                                    <div class="progress me-2" style="width: 100px; height: 10px;">
                                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $progress ?>%;" aria-valuenow="<?= $progress ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="badge rounded-pill bg-light text-dark"><?= $selesai ?>/<?= $total_materi ?></span>
                                </div>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse<?= $index ?>" class="accordion-collapse collapse <?= ($index == 0) ? 'show' : '' ?>" aria-labelledby="heading<?= $index ?>" data-bs-parent="#moduleAccordion">
                        <div class="accordion-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($materis as $materi):
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

                                    $status_class = '';
                                    $status_icon = '';

                                    switch ($materi['status_materi']) {
                                        case 'selesai':
                                            $status_class = 'text-success';
                                            $status_icon = 'bi-check-circle-fill';
                                            break;
                                        case 'sedang dipelajari':
                                            $status_class = 'text-primary';
                                            $status_icon = 'bi-play-fill';
                                            break;
                                        default:
                                            $status_class = 'text-secondary';
                                            $status_icon = 'bi-circle';
                                            break;
                                    }
                                ?>
                                    <a href="<?= BASE_URL ?>/user/belajar/materi?id=<?= $materi['id'] ?>&kursus_id=<?= $kursus_id ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="bi <?= $icon ?> me-2"></i>
                                            <?= htmlspecialchars($materi['judul']) ?>
                                            <span class="badge rounded-pill bg-light text-dark ms-2">
                                                <i class="bi <?= $icon ?> me-1"></i> <?= $label ?>
                                            </span>
                                            <?php if ($materi['durasi_menit']): ?>
                                                <span class="badge rounded-pill bg-light text-dark ms-2">
                                                    <i class="bi bi-clock me-1"></i> <?= $materi['durasi_menit'] ?> menit
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <i class="bi <?= $status_icon ?> <?= $status_class ?>"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/layouts/user/user-layout.php';
userLayout('Belajar - ' . htmlspecialchars($kursus['judul']), $content, 'kursus');
?>