<?php
// Path: views/user/tugas/index.php

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

$sql_pendaftaran = "SELECT id FROM pendaftaran WHERE pengguna_id = ? AND kursus_id = ?";
$stmt_pendaftaran = $db->prepare($sql_pendaftaran);
$stmt_pendaftaran->execute([$user_id, $kursus_id]);
$pendaftaran = $stmt_pendaftaran->fetch();

if (!$pendaftaran) {
    header('Location: ' . BASE_URL . '/user/kursus');
    exit;
}

$pendaftaran_id = $pendaftaran['id'];

$sql_kursus = "SELECT * FROM kursus WHERE id = ?";
$stmt_kursus = $db->prepare($sql_kursus);
$stmt_kursus->execute([$kursus_id]);
$kursus = $stmt_kursus->fetch();

if (!$kursus) {
    header('Location: ' . BASE_URL . '/user/kursus');
    exit;
}

$sql_tugas = "SELECT t.*, m.judul as materi_judul, m.id as materi_id, mo.judul as modul_judul,
             (SELECT pt.status FROM pengumpulan_tugas pt WHERE pt.tugas_id = t.id AND pt.pendaftaran_id = ?) as status_pengumpulan,
             (SELECT pt.nilai FROM pengumpulan_tugas pt WHERE pt.tugas_id = t.id AND pt.pendaftaran_id = ?) as nilai_tugas,
             (SELECT pt.waktu_pengumpulan FROM pengumpulan_tugas pt WHERE pt.tugas_id = t.id AND pt.pendaftaran_id = ?) as waktu_pengumpulan
             FROM tugas t
             JOIN materi m ON t.materi_id = m.id
             JOIN modul mo ON m.modul_id = mo.id
             WHERE mo.kursus_id = ?
             ORDER BY t.tenggat_waktu ASC";
$stmt_tugas = $db->prepare($sql_tugas);
$stmt_tugas->execute([$pendaftaran_id, $pendaftaran_id, $pendaftaran_id, $kursus_id]);
$tugasList = $stmt_tugas->fetchAll();

ob_start();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Daftar Tugas - <?= htmlspecialchars($kursus['judul']) ?></h3>
        <a href="<?= BASE_URL ?>/user/belajar?kursus_id=<?= $kursus_id ?>" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left me-2"></i>Kembali ke Materi
        </a>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if (count($tugasList) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Judul Tugas</th>
                                        <th>Modul / Materi</th>
                                        <th>Tenggat Waktu</th>
                                        <th>Status</th>
                                        <th>Nilai</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tugasList as $index => $tugas):
                                        $status_text = 'Belum Dikumpulkan';
                                        $status_class = 'warning';

                                        $sekarang = new DateTime();
                                        $tenggat = $tugas['tenggat_waktu'] ? new DateTime($tugas['tenggat_waktu']) : null;
                                        $terlambat = $tenggat && $sekarang > $tenggat;

                                        if ($tugas['status_pengumpulan']) {
                                            switch ($tugas['status_pengumpulan']) {
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
                                    ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($tugas['judul']) ?></td>
                                            <td>
                                                <div><?= htmlspecialchars($tugas['modul_judul']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($tugas['materi_judul']) ?></small>
                                            </td>
                                            <td>
                                                <?php if ($tugas['tenggat_waktu']): ?>
                                                    <div class="<?= $terlambat ? 'text-danger' : '' ?>">
                                                        <?= date('d M Y, H:i', strtotime($tugas['tenggat_waktu'])) ?>
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
                                                        <small class="text-muted">Sisa waktu: <?= $sisa_waktu ?></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Tidak ada tenggat</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $status_class ?>"><?= $status_text ?></span>

                                                <?php if ($tugas['waktu_pengumpulan']): ?>
                                                    <div>
                                                        <small class="text-muted">
                                                            <?= date('d M Y, H:i', strtotime($tugas['waktu_pengumpulan'])) ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($tugas['nilai_tugas'] !== null): ?>
                                                    <span class="badge bg-primary"><?= $tugas['nilai_tugas'] ?>/<?= $tugas['nilai_maksimal'] ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/user/tugas/detail?id=<?= $tugas['id'] ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye me-1"></i>Detail
                                                </a>

                                                <?php if (!$tugas['status_pengumpulan'] || $tugas['status_pengumpulan'] === 'revisi'): ?>
                                                    <?php if (!$terlambat || $tugas['tenggat_waktu'] === null): ?>
                                                        <a href="<?= BASE_URL ?>/user/tugas/kumpul?id=<?= $tugas['id'] ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-sm btn-success ms-1">
                                                            <i class="bi bi-upload me-1"></i>Kumpulkan
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="mb-3">
                                <i class="bi bi-clipboard-x display-1 text-muted"></i>
                            </div>
                            <h5>Belum Ada Tugas</h5>
                            <p class="text-muted">Kursus ini belum memiliki tugas yang perlu dikerjakan.</p>
                            <a href="<?= BASE_URL ?>/user/belajar?kursus_id=<?= $kursus_id ?>" class="btn btn-primary">
                                <i class="bi bi-book me-2"></i>Kembali Belajar
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/layouts/user/user-layout.php';
userLayout('Daftar Tugas - ' . htmlspecialchars($kursus['judul']), $content, 'kursus');
?>