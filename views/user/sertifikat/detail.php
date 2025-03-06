<?php
// Path: views/user/sertifikat/detail.php

require_once BASE_PATH . '/layouts/user/user-layout.php';

if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . '/user/sertifikat');
    exit;
}

$sertifikatId = $_GET['id'];
$userId = $_SESSION['user']['id'];
$db = dbConnect();

// Ambil data sertifikat
$query = "SELECT s.*, p.tanggal_daftar, p.tanggal_selesai, k.judul as judul_kursus, 
          k.level, k.deskripsi as deskripsi_kursus, k.durasi_menit,
          pg.nama_lengkap as nama_pengguna, pg.email
          FROM sertifikat s
          JOIN pendaftaran p ON s.pendaftaran_id = p.id
          JOIN kursus k ON p.kursus_id = k.id
          JOIN pengguna pg ON p.pengguna_id = pg.id
          WHERE s.id = :sertifikat_id AND pg.id = :user_id AND s.status = 'aktif'";

$stmt = $db->prepare($query);
$stmt->bindParam(':sertifikat_id', $sertifikatId);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$sertifikat = $stmt->fetch();

if (!$sertifikat) {
    header('Location: ' . BASE_URL . '/user/sertifikat');
    exit;
}

// Ambil data pengajar/instruktur
$queryInstruktur = "SELECT nama_lengkap 
                    FROM pengguna 
                    WHERE id = (SELECT pembuat_id FROM kursus WHERE id = 
                               (SELECT kursus_id FROM pendaftaran WHERE id = :pendaftaran_id))";
$stmtInstruktur = $db->prepare($queryInstruktur);
$stmtInstruktur->bindParam(':pendaftaran_id', $sertifikat['pendaftaran_id']);
$stmtInstruktur->execute();
$instruktur = $stmtInstruktur->fetch();

$content = function () use ($sertifikat, $instruktur) {
    $baseUrl = BASE_URL;
?>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Detail Sertifikat</h2>
                <a href="<?= $baseUrl ?>/user/sertifikat" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h3 class="fw-bold"><?= htmlspecialchars($sertifikat['judul_kursus']) ?></h3>
                        <p class="text-muted">Sertifikat Penyelesaian</p>
                    </div>

                    <?php if (!empty($sertifikat['file_sertifikat'])): ?>
                        <div class="text-center mb-4">
                            <img src="<?= $baseUrl ?>/uploads/sertifikat/<?= $sertifikat['file_sertifikat'] ?>"
                                class="img-fluid border" alt="Sertifikat" style="max-height: 400px;">
                        </div>
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Informasi Sertifikat</h5>
                            <table class="table">
                                <tr>
                                    <th>Nomor Sertifikat</th>
                                    <td><?= htmlspecialchars($sertifikat['nomor_sertifikat']) ?></td>
                                </tr>
                                <tr>
                                    <th>Tanggal Terbit</th>
                                    <td><?= date('d M Y', strtotime($sertifikat['tanggal_terbit'])) ?></td>
                                </tr>
                                <?php if (!empty($sertifikat['tanggal_kedaluwarsa'])): ?>
                                    <tr>
                                        <th>Tanggal Kedaluwarsa</th>
                                        <td><?= date('d M Y', strtotime($sertifikat['tanggal_kedaluwarsa'])) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <?php if ($sertifikat['status'] == 'aktif'): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Dicabut</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Informasi Kursus</h5>
                            <table class="table">
                                <tr>
                                    <th>Nama Kursus</th>
                                    <td><?= htmlspecialchars($sertifikat['judul_kursus']) ?></td>
                                </tr>
                                <tr>
                                    <th>Level</th>
                                    <td><?= ucfirst(htmlspecialchars($sertifikat['level'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Instruktur</th>
                                    <td><?= htmlspecialchars($instruktur['nama_lengkap'] ?? 'Tidak tersedia') ?></td>
                                </tr>
                                <tr>
                                    <th>Durasi</th>
                                    <td><?= $sertifikat['durasi_menit'] ? floor($sertifikat['durasi_menit'] / 60) . ' jam ' . ($sertifikat['durasi_menit'] % 60) . ' menit' : 'Tidak ditentukan' ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <h5>Informasi Penerima</h5>
                            <table class="table">
                                <tr>
                                    <th>Nama</th>
                                    <td><?= htmlspecialchars($sertifikat['nama_pengguna']) ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?= htmlspecialchars($sertifikat['email']) ?></td>
                                </tr>
                                <tr>
                                    <th>Tanggal Mulai Kursus</th>
                                    <td><?= date('d M Y', strtotime($sertifikat['tanggal_daftar'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Tanggal Selesai Kursus</th>
                                    <td><?= date('d M Y', strtotime($sertifikat['tanggal_selesai'])) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        <?php if (!empty($sertifikat['file_sertifikat'])): ?>
                            <a href="<?= $baseUrl ?>/uploads/sertifikat/<?= $sertifikat['file_sertifikat'] ?>"
                                class="btn btn-primary me-2" download>
                                <i class="bi bi-download"></i> Unduh Sertifikat
                            </a>
                        <?php endif; ?>

                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#shareSertifikatModal">
                            <i class="bi bi-share"></i> Bagikan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Bagikan -->
    <div class="modal fade" id="shareSertifikatModal" tabindex="-1" aria-labelledby="shareSertifikatModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareSertifikatModalLabel">Bagikan Sertifikat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Bagikan link verifikasi sertifikat ini:</p>
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="sertifikatLink" value="<?= $baseUrl ?>/sertifikat/verifikasi?nomor=<?= urlencode($sertifikat['nomor_sertifikat']) ?>" readonly>
                        <button class="btn btn-outline-primary" type="button" id="copyLinkBtn">
                            <i class="bi bi-clipboard"></i> Salin
                        </button>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        <a href="https://wa.me/?text=Verifikasi%20sertifikat%20saya%20dari%20Atlas%20LMS:%20<?= urlencode($baseUrl . '/sertifikat/verifikasi?nomor=' . $sertifikat['nomor_sertifikat']) ?>"
                            class="btn btn-success me-2" target="_blank">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>

                        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($baseUrl . '/sertifikat/verifikasi?nomor=' . $sertifikat['nomor_sertifikat']) ?>"
                            class="btn btn-primary me-2" target="_blank">
                            <i class="bi bi-linkedin"></i> LinkedIn
                        </a>

                        <a href="mailto:?subject=Sertifikat <?= urlencode($sertifikat['judul_kursus']) ?>&body=Verifikasi%20sertifikat%20saya%20dari%20Atlas%20LMS:%20<?= urlencode($baseUrl . '/sertifikat/verifikasi?nomor=' . $sertifikat['nomor_sertifikat']) ?>"
                            class="btn btn-secondary">
                            <i class="bi bi-envelope"></i> Email
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const copyLinkBtn = document.getElementById('copyLinkBtn');
            const sertifikatLink = document.getElementById('sertifikatLink');

            copyLinkBtn.addEventListener('click', function() {
                sertifikatLink.select();
                document.execCommand('copy');

                copyLinkBtn.innerHTML = '<i class="bi bi-check"></i> Tersalin!';
                setTimeout(function() {
                    copyLinkBtn.innerHTML = '<i class="bi bi-clipboard"></i> Salin';
                }, 2000);
            });
        });
    </script>
<?php
};

userLayout("Detail Sertifikat", $content(), "sertifikat");
?>