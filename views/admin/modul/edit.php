<?php
// Path: views/admin/modul/edit.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Edit Modul", "Edit modul kursus");

$db = dbConnect();
$pesan = '';
$tipe = '';

// Cek parameter id
if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "/admin/modul");
    exit;
}

$id = $_GET['id'];

// Cek apakah modul ada
$stmt = $db->prepare("SELECT m.*, k.judul as kursus_judul, k.status as kursus_status, k.level as kursus_level 
                     FROM modul m 
                     JOIN kursus k ON m.kursus_id = k.id 
                     WHERE m.id = ?");
$stmt->execute([$id]);
$modul = $stmt->fetch();

if (!$modul) {
    header("Location: " . BASE_URL . "/admin/modul");
    exit;
}

// Hitung jumlah materi
$stmt = $db->prepare("SELECT COUNT(*) as jumlah_materi FROM materi WHERE modul_id = ?");
$stmt->execute([$id]);
$jumlahMateri = $stmt->fetch()['jumlah_materi'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $urutan = intval($_POST['urutan'] ?? $modul['urutan']);

    $errors = [];

    if (empty($judul)) {
        $errors[] = "Judul modul harus diisi";
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE modul SET judul = ?, deskripsi = ?, urutan = ?, waktu_diperbarui = NOW() WHERE id = ?");
            $stmt->execute([$judul, $deskripsi, $urutan, $id]);

            $pesan = "Modul berhasil diperbarui";
            $tipe = "success";

            // Refresh data modul
            $stmt = $db->prepare("SELECT m.*, k.judul as kursus_judul, k.status as kursus_status, k.level as kursus_level 
                                 FROM modul m 
                                 JOIN kursus k ON m.kursus_id = k.id 
                                 WHERE m.id = ?");
            $stmt->execute([$id]);
            $modul = $stmt->fetch();
        } catch (PDOException $e) {
            $pesan = "Gagal memperbarui modul: " . $e->getMessage();
            $tipe = "danger";
        }
    } else {
        $pesan = implode("<br>", $errors);
        $tipe = "danger";
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="page-title">Edit Modul</h1>
            <p class="text-muted">Edit modul "<?= htmlspecialchars($modul['judul']) ?>"</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?= BASE_URL ?>/admin/modul?kursus_id=<?= $modul['kursus_id'] ?>" class="btn btn-secondary">
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
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="judul" class="form-label">Judul Modul <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="judul" name="judul" value="<?= htmlspecialchars($modul['judul']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi Modul</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?= htmlspecialchars($modul['deskripsi'] ?? '') ?></textarea>
                            <div class="form-text">Jelaskan secara singkat tentang modul ini dan apa yang akan dipelajari.</div>
                        </div>

                        <div class="mb-3">
                            <label for="urutan" class="form-label">Urutan</label>
                            <input type="number" class="form-control" id="urutan" name="urutan" value="<?= $modul['urutan'] ?>" min="1">
                            <div class="form-text">Urutan modul dalam kursus. Ubah secara manual jika diperlukan.</div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= BASE_URL ?>/admin/modul?kursus_id=<?= $modul['kursus_id'] ?>" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Kursus</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">Judul Kursus</label>
                        <p class="fw-semibold"><?= htmlspecialchars($modul['kursus_judul']) ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Status Kursus</label>
                        <p>
                            <span class="badge <?= $modul['kursus_status'] === 'publikasi' ? 'bg-success' : ($modul['kursus_status'] === 'draf' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                <?= ucfirst($modul['kursus_status']) ?>
                            </span>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Level Kursus</label>
                        <p>
                            <span class="badge bg-secondary"><?= ucfirst($modul['kursus_level']) ?></span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Modul</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            ID Modul
                            <span class="text-muted small"><?= $id ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Jumlah Materi
                            <span class="badge bg-primary rounded-pill"><?= $jumlahMateri ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Dibuat Pada
                            <span class="text-muted small"><?= date('d/m/Y H:i', strtotime($modul['waktu_dibuat'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Terakhir Diperbarui
                            <span class="text-muted small"><?= date('d/m/Y H:i', strtotime($modul['waktu_diperbarui'])) ?></span>
                        </li>
                    </ul>

                    <div class="mt-3">
                        <a href="<?= BASE_URL ?>/admin/materi?modul_id=<?= $id ?>" class="btn btn-primary w-100">
                            <i class="bi bi-file-text"></i> Kelola Materi (<?= $jumlahMateri ?>)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php adminFooter(); ?>