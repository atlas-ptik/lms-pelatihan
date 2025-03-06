<?php
// Path: views/user/instruktur/peserta/index.php

require_once BASE_PATH . '/layouts/user/user-layout.php';

// Periksa apakah pengguna adalah instruktur
$userId = $_SESSION['user']['id'];
$db = dbConnect();

$queryRole = "SELECT role_id FROM pengguna WHERE id = :user_id";
$stmtRole = $db->prepare($queryRole);
$stmtRole->bindParam(':user_id', $userId);
$stmtRole->execute();
$role = $stmtRole->fetch();

$queryRoleInstruktur = "SELECT id FROM role WHERE nama = 'instruktur'";
$stmtRoleInstruktur = $db->prepare($queryRoleInstruktur);
$stmtRoleInstruktur->execute();
$roleInstruktur = $stmtRoleInstruktur->fetch();

if (!$role || $role['role_id'] != $roleInstruktur['id']) {
    header('Location: ' . BASE_URL . '/user/dashboard');
    exit;
}

// Periksa parameter id kursus
if (!isset($_GET['kursus_id'])) {
    header('Location: ' . BASE_URL . '/user/instruktur/kursus');
    exit;
}

$kursusId = $_GET['kursus_id'];

// Ambil data kursus
$queryKursus = "SELECT * FROM kursus WHERE id = :kursus_id AND pembuat_id = :user_id";
$stmtKursus = $db->prepare($queryKursus);
$stmtKursus->bindParam(':kursus_id', $kursusId);
$stmtKursus->bindParam(':user_id', $userId);
$stmtKursus->execute();
$kursus = $stmtKursus->fetch();

if (!$kursus) {
    header('Location: ' . BASE_URL . '/user/instruktur/kursus');
    exit;
}

// Ambil daftar peserta kursus
$queryPeserta = "SELECT p.id as pendaftaran_id, p.tanggal_daftar, p.status, p.progres_persen, 
                        pg.id as pengguna_id, pg.nama_lengkap, pg.email, pg.foto_profil,
                        (SELECT COUNT(*) FROM progres_materi pm JOIN materi m ON pm.materi_id = m.id
                         JOIN modul md ON m.modul_id = md.id 
                         WHERE pm.pendaftaran_id = p.id AND md.kursus_id = :kursus_id AND pm.status = 'selesai') as materi_selesai,
                        (SELECT COUNT(*) FROM materi m JOIN modul md ON m.modul_id = md.id 
                         WHERE md.kursus_id = :kursus_id) as total_materi  
                 FROM pendaftaran p 
                 JOIN pengguna pg ON p.pengguna_id = pg.id
                 WHERE p.kursus_id = :kursus_id
                 ORDER BY p.tanggal_daftar DESC";

$stmtPeserta = $db->prepare($queryPeserta);
$stmtPeserta->bindParam(':kursus_id', $kursusId);
$stmtPeserta->execute();
$peserta = $stmtPeserta->fetchAll();

// Proses untuk menghapus peserta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_peserta') {
    $pendaftaranId = $_POST['pendaftaran_id'] ?? '';

    if (empty($pendaftaranId)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'ID pendaftaran tidak valid'
        ];
    } else {
        try {
            // Periksa apakah pendaftaran milik kursus ini
            $queryCheckPendaftaran = "SELECT COUNT(*) as count FROM pendaftaran 
                                    WHERE id = :pendaftaran_id AND kursus_id = :kursus_id";
            $stmtCheckPendaftaran = $db->prepare($queryCheckPendaftaran);
            $stmtCheckPendaftaran->bindParam(':pendaftaran_id', $pendaftaranId);
            $stmtCheckPendaftaran->bindParam(':kursus_id', $kursusId);
            $stmtCheckPendaftaran->execute();

            if ($stmtCheckPendaftaran->fetch()['count'] > 0) {
                // Hapus pendaftaran
                $queryHapusPendaftaran = "UPDATE pendaftaran SET status = 'dibatalkan' WHERE id = :pendaftaran_id";
                $stmtHapusPendaftaran = $db->prepare($queryHapusPendaftaran);
                $stmtHapusPendaftaran->bindParam(':pendaftaran_id', $pendaftaranId);

                if ($stmtHapusPendaftaran->execute()) {
                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'message' => 'Peserta berhasil dikeluarkan dari kursus'
                    ];
                } else {
                    $_SESSION['alert'] = [
                        'type' => 'danger',
                        'message' => 'Gagal mengeluarkan peserta'
                    ];
                }
            } else {
                $_SESSION['alert'] = [
                    'type' => 'danger',
                    'message' => 'Pendaftaran tidak ditemukan'
                ];
            }
        } catch (Exception $e) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ];
        }

        header('Location: ' . BASE_URL . '/user/instruktur/peserta?kursus_id=' . $kursusId);
        exit;
    }
}

$content = function () use ($kursus, $peserta) {
    $baseUrl = BASE_URL;
?>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Daftar Peserta</h2>
                <a href="<?= $baseUrl ?>/user/instruktur/kursus" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar Kursus
                </a>
            </div>

            <?php if (isset($_SESSION['alert'])): ?>
                <div class="alert alert-<?= $_SESSION['alert']['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['alert']['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['alert']); ?>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($kursus['judul']) ?></h5>
                    <p class="text-muted">
                        <i class="bi bi-people"></i> Total Peserta: <?= count($peserta) ?>
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <?php if (empty($peserta)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-people" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="mt-3">Belum ada peserta terdaftar di kursus ini.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Peserta</th>
                                        <th>Email</th>
                                        <th>Tanggal Daftar</th>
                                        <th>Progres</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($peserta as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($item['foto_profil'])): ?>
                                                        <img src="<?= $baseUrl ?>/uploads/profil/<?= $item['foto_profil'] ?>"
                                                            class="rounded-circle me-2" width="32" height="32" alt="<?= htmlspecialchars($item['nama_lengkap']) ?>"
                                                            style="object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="rounded-circle bg-secondary text-white d-flex justify-content-center align-items-center me-2"
                                                            style="width: 32px; height: 32px;">
                                                            <i class="bi bi-person-fill"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div><?= htmlspecialchars($item['nama_lengkap']) ?></div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($item['email']) ?></td>
                                            <td><?= date('d M Y', strtotime($item['tanggal_daftar'])) ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1" style="height: 8px;">
                                                        <div class="progress-bar bg-primary" role="progressbar"
                                                            style="width: <?= $item['progres_persen'] ?>%;"
                                                            aria-valuenow="<?= $item['progres_persen'] ?>" aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    <span class="ms-2"><?= number_format($item['progres_persen'], 0) ?>%</span>
                                                </div>
                                                <small class="text-muted"><?= $item['materi_selesai'] ?>/<?= $item['total_materi'] ?> materi selesai</small>
                                            </td>
                                            <td>
                                                <?php if ($item['status'] === 'aktif'): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php elseif ($item['status'] === 'selesai'): ?>
                                                    <span class="badge bg-primary">Selesai</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Dibatalkan</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex">
                                                    <a href="<?= $baseUrl ?>/user/instruktur/peserta/detail?pendaftaran_id=<?= $item['pendaftaran_id'] ?>"
                                                        class="btn btn-sm btn-primary me-1">
                                                        <i class="bi bi-eye"></i> Detail
                                                    </a>

                                                    <?php if ($item['status'] === 'aktif'): ?>
                                                        <button type="button" class="btn btn-sm btn-danger"
                                                            onclick="confirmRemove('<?= $item['pendaftaran_id'] ?>', '<?= htmlspecialchars($item['nama_lengkap']) ?>')">
                                                            <i class="bi bi-person-x"></i> Keluarkan
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus Peserta -->
    <div class="modal fade" id="removeModal" tabindex="-1" aria-labelledby="removeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="removeModalLabel">Konfirmasi Keluarkan Peserta</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin mengeluarkan peserta <strong id="pesertaNama"></strong> dari kursus ini?
                    <p class="text-danger mt-2">Peserta akan kehilangan akses ke konten kursus.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form id="removeForm" method="POST" action="<?= $baseUrl ?>/user/instruktur/peserta?kursus_id=<?= $kursus['id'] ?>">
                        <input type="hidden" name="action" value="hapus_peserta">
                        <input type="hidden" name="pendaftaran_id" id="pendaftaranIdInput">
                        <button type="submit" class="btn btn-danger">Keluarkan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmRemove(pendaftaranId, nama) {
            document.getElementById('pesertaNama').textContent = nama;
            document.getElementById('pendaftaranIdInput').value = pendaftaranId;

            const removeModal = new bootstrap.Modal(document.getElementById('removeModal'));
            removeModal.show();
        }
    </script>
<?php
};

userLayout("Daftar Peserta - " . $kursus['judul'], $content(), "instruktur");
?>