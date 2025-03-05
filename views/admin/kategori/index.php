<?php
// Path: views/admin/kategori/index.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Manajemen Kategori", "Kelola kategori kursus di Atlas LMS");

$db = dbConnect();

// Proses hapus kategori jika ada
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $db->prepare("DELETE FROM kategori WHERE id = ?");
        $stmt->execute([$id]);
        $pesan = "Kategori berhasil dihapus.";
        $tipe = "success";
    } catch (PDOException $e) {
        $pesan = "Gagal menghapus kategori. Error: " . $e->getMessage();
        $tipe = "danger";
    }
}

// Ambil semua kategori
$stmt = $db->prepare("SELECT k.*, (SELECT COUNT(*) FROM kursus_kategori WHERE kategori_id = k.id) as jumlah_kursus FROM kategori k ORDER BY k.urutan ASC");
$stmt->execute();
$kategori = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="page-title">Manajemen Kategori</h1>
            <p class="text-muted">Kelola kategori kursus di sistem</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?= BASE_URL ?>/admin/kategori/tambah" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah Kategori
            </a>
        </div>
    </div>

    <?php if (isset($pesan)): ?>
        <div class="alert alert-<?= $tipe ?> alert-dismissible fade show" role="alert">
            <?= $pesan ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th width="70">Ikon</th>
                            <th>Nama Kategori</th>
                            <th>Deskripsi</th>
                            <th>Jumlah Kursus</th>
                            <th>Urutan</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($kategori)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Belum ada data kategori</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1;
                            foreach ($kategori as $k): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($k['ikon'])): ?>
                                            <i class="bi bi-<?= htmlspecialchars($k['ikon']) ?> fs-4"></i>
                                        <?php else: ?>
                                            <i class="bi bi-tag fs-4"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($k['nama']) ?></td>
                                    <td>
                                        <?php
                                        echo !empty($k['deskripsi'])
                                            ? (strlen($k['deskripsi']) > 50
                                                ? htmlspecialchars(substr($k['deskripsi'], 0, 50)) . '...'
                                                : htmlspecialchars($k['deskripsi']))
                                            : '<span class="text-muted">Tidak ada deskripsi</span>';
                                        ?>
                                    </td>
                                    <td><?= $k['jumlah_kursus'] ?> kursus</td>
                                    <td><?= $k['urutan'] ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/admin/kategori/edit?id=<?= $k['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal" data-bs-target="#deleteModal<?= $k['id'] ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>

                                        <!-- Modal Konfirmasi Hapus -->
                                        <div class="modal fade" id="deleteModal<?= $k['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $k['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel<?= $k['id'] ?>">Konfirmasi Hapus</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Apakah Anda yakin ingin menghapus kategori <strong><?= htmlspecialchars($k['nama']) ?></strong>?
                                                        <?php if ($k['jumlah_kursus'] > 0): ?>
                                                            <div class="alert alert-warning mt-3">
                                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                                                Kategori ini memiliki <?= $k['jumlah_kursus'] ?> kursus terkait. Menghapus kategori ini akan menghapus hubungan dengan kursus.
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <a href="<?= BASE_URL ?>/admin/kategori?action=delete&id=<?= $k['id'] ?>" class="btn btn-danger">Hapus</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php adminFooter(); ?>