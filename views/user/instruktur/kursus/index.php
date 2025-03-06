<?php
// Path: views/user/instruktur/kursus/index.php

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

// Ambil daftar kursus yang dibuat oleh instruktur
$query = "SELECT k.*, 
          (SELECT COUNT(*) FROM pendaftaran WHERE kursus_id = k.id) as jumlah_peserta,
          (SELECT COUNT(*) FROM modul WHERE kursus_id = k.id) as jumlah_modul
          FROM kursus k
          WHERE k.pembuat_id = :user_id
          ORDER BY k.waktu_dibuat DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$kursus = $stmt->fetchAll();

$content = function () use ($kursus) {
    $baseUrl = BASE_URL;
?>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Kursus Saya</h2>
                <a href="<?= $baseUrl ?>/user/instruktur/kursus/tambah" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Buat Kursus Baru
                </a>
            </div>

            <?php if (isset($_SESSION['alert'])): ?>
                <div class="alert alert-<?= $_SESSION['alert']['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['alert']['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['alert']); ?>
            <?php endif; ?>

            <?php if (empty($kursus)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-book" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="mt-3">Anda belum membuat kursus apapun.</p>
                    <a href="<?= $baseUrl ?>/user/instruktur/kursus/tambah" class="btn btn-primary mt-2">Buat Kursus Pertama Anda</a>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach ($kursus as $item): ?>
                        <div class="col">
                            <div class="card h-100">
                                <?php if (!empty($item['gambar_sampul'])): ?>
                                    <img src="<?= $baseUrl ?>/uploads/kursus/<?= $item['gambar_sampul'] ?>"
                                        class="card-img-top" alt="<?= htmlspecialchars($item['judul']) ?>"
                                        style="height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-secondary text-white d-flex justify-content-center align-items-center"
                                        style="height: 150px;">
                                        <i class="bi bi-image" style="font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="card-title text-truncate mb-0"><?= htmlspecialchars($item['judul']) ?></h5>
                                        <span class="badge <?= $item['status'] === 'publikasi' ? 'bg-success' : ($item['status'] === 'draf' ? 'bg-secondary' : 'bg-danger') ?>">
                                            <?= ucfirst($item['status']) ?>
                                        </span>
                                    </div>

                                    <p class="card-text text-truncate mb-3">
                                        <?= htmlspecialchars(substr(strip_tags($item['deskripsi']), 0, 100)) ?>...
                                    </p>

                                    <div class="row mb-2">
                                        <div class="col-6">
                                            <small class="text-muted">
                                                <i class="bi bi-people"></i> <?= $item['jumlah_peserta'] ?> peserta
                                            </small>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">
                                                <i class="bi bi-collection"></i> <?= $item['jumlah_modul'] ?> modul
                                            </small>
                                        </div>
                                    </div>

                                    <div class="d-flex">
                                        <span class="badge bg-info me-2"><?= ucfirst($item['level']) ?></span>
                                        <?php if ($item['harga'] > 0): ?>
                                            <span class="badge bg-warning">Rp <?= number_format($item['harga'], 0, ',', '.') ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Gratis</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="card-footer bg-white border-0">
                                    <div class="d-flex justify-content-between">
                                        <a href="<?= $baseUrl ?>/user/instruktur/kursus/edit?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>

                                        <div class="btn-group" role="group">
                                            <a href="<?= $baseUrl ?>/user/instruktur/peserta?kursus_id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-people"></i> Peserta
                                            </a>

                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="confirmDelete('<?= $item['id'] ?>', '<?= htmlspecialchars($item['judul']) ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus kursus "<span id="kursusTitle"></span>"?
                    <p class="text-danger mt-2">Tindakan ini tidak dapat dibatalkan dan akan menghapus semua data terkait!</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form id="deleteForm" method="POST" action="<?= $baseUrl ?>/user/instruktur/kursus/hapus">
                        <input type="hidden" name="kursus_id" id="kursusIdInput">
                        <button type="submit" class="btn btn-danger">Hapus</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(kursusId, kursusTitle) {
            document.getElementById('kursusTitle').textContent = kursusTitle;
            document.getElementById('kursusIdInput').value = kursusId;

            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
    </script>
<?php
};

userLayout("Kursus Saya - Instruktur", $content(), "instruktur");
?>