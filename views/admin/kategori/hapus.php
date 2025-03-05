<?php
// Path: views/admin/kategori/hapus.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Hapus Kategori", "Hapus kategori kursus");

$db = dbConnect();
$pesan = '';
$tipe = '';

// Validasi parameter ID
if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "/admin/kategori");
    exit;
}

$id = $_GET['id'];

// Cek apakah kategori ada
$stmt = $db->prepare("SELECT * FROM kategori WHERE id = ?");
$stmt->execute([$id]);
$kategori = $stmt->fetch();

if (!$kategori) {
    header("Location: " . BASE_URL . "/admin/kategori");
    exit;
}

// Hitung jumlah kursus terkait
$stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM kursus_kategori WHERE kategori_id = ?");
$stmt->execute([$id]);
$jumlahKursus = $stmt->fetch()['jumlah'];

// Proses hapus jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi_hapus'])) {
    try {
        $db->beginTransaction();
        
        // Hapus relasi kursus_kategori terlebih dahulu
        $stmt = $db->prepare("DELETE FROM kursus_kategori WHERE kategori_id = ?");
        $stmt->execute([$id]);
        
        // Hapus kategori
        $stmt = $db->prepare("DELETE FROM kategori WHERE id = ?");
        $stmt->execute([$id]);
        
        $db->commit();
        
        // Redirect ke halaman kategori dengan pesan sukses
        header("Location: " . BASE_URL . "/admin/kategori?pesan=Kategori berhasil dihapus&tipe=success");
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        $pesan = "Gagal menghapus kategori: " . $e->getMessage();
        $tipe = "danger";
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="page-title">Hapus Kategori</h1>
            <p class="text-muted">Konfirmasi penghapusan kategori</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?= BASE_URL ?>/admin/kategori" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <?php if (!empty($pesan)): ?>
    <div class="alert alert-<?= $tipe ?> alert-dismissible fade show" role="alert">
        <?= $pesan ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Konfirmasi Penghapusan</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                            </div>
                            <div>
                                <h5>Perhatian!</h5>
                                <p>Anda akan menghapus kategori <strong><?= htmlspecialchars($kategori['nama']) ?></strong>. Tindakan ini tidak dapat dibatalkan.</p>
                                
                                <?php if ($jumlahKursus > 0): ?>
                                <div class="mt-2 mb-0">
                                    <p><strong>Kategori ini terhubung dengan <?= $jumlahKursus ?> kursus.</strong> Menghapus kategori ini akan menghapus hubungan kategori dengan kursus-kursus tersebut, namun tidak akan menghapus kursusnya.</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <form action="" method="POST">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="konfirmasi_hapus" name="konfirmasi_hapus" value="1" required>
                                <label class="form-check-label" for="konfirmasi_hapus">
                                    Saya mengerti dan ingin menghapus kategori ini
                                </label>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="<?= BASE_URL ?>/admin/kategori" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-danger" id="btn-hapus" disabled>
                                    <i class="bi bi-trash"></i> Hapus Kategori
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Kategori</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Nama Kategori:</span>
                            <span><?= htmlspecialchars($kategori['nama']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Ikon:</span>
                            <span>
                                <?php if (!empty($kategori['ikon'])): ?>
                                <i class="bi bi-<?= htmlspecialchars($kategori['ikon']) ?>"></i> <?= htmlspecialchars($kategori['ikon']) ?>
                                <?php else: ?>
                                <span class="text-muted">Tidak ada ikon</span>
                                <?php endif; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Urutan:</span>
                            <span><?= $kategori['urutan'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Jumlah Kursus:</span>
                            <span class="badge bg-primary rounded-pill"><?= $jumlahKursus ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Dibuat pada:</span>
                            <span><?= date('d/m/Y H:i', strtotime($kategori['waktu_dibuat'])) ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkKonfirmasi = document.getElementById('konfirmasi_hapus');
    const btnHapus = document.getElementById('btn-hapus');
    
    checkKonfirmasi.addEventListener('change', function() {
        btnHapus.disabled = !this.checked;
    });
});
</script>

<?php adminFooter(); ?>