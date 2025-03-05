<?php
// Path: views/admin/modul/tambah.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Tambah Modul", "Tambah modul baru untuk kursus");

$db = dbConnect();
$pesan = '';
$tipe = '';

// Cek parameter kursus_id
if (!isset($_GET['kursus_id'])) {
    header("Location: " . BASE_URL . "/admin/modul");
    exit;
}

$kursus_id = $_GET['kursus_id'];

// Cek apakah kursus ada
$stmt = $db->prepare("SELECT * FROM kursus WHERE id = ?");
$stmt->execute([$kursus_id]);
$kursus = $stmt->fetch();

if (!$kursus) {
    header("Location: " . BASE_URL . "/admin/modul");
    exit;
}

// Cek urutan terakhir untuk modul pada kursus ini
$stmt = $db->prepare("SELECT MAX(urutan) as max_urutan FROM modul WHERE kursus_id = ?");
$stmt->execute([$kursus_id]);
$result = $stmt->fetch();
$nextUrutan = ($result['max_urutan'] ?? 0) + 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $urutan = intval($_POST['urutan'] ?? $nextUrutan);

    $errors = [];

    if (empty($judul)) {
        $errors[] = "Judul modul harus diisi";
    }

    if (empty($errors)) {
        try {
            $id = generate_uuid();
            $stmt = $db->prepare("INSERT INTO modul (id, kursus_id, judul, deskripsi, urutan, waktu_dibuat, waktu_diperbarui) 
                                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$id, $kursus_id, $judul, $deskripsi, $urutan]);

            $pesan = "Modul berhasil ditambahkan";
            $tipe = "success";

            header("Location: " . BASE_URL . "/admin/modul?kursus_id=" . $kursus_id);
            exit;
        } catch (PDOException $e) {
            $pesan = "Gagal menambahkan modul: " . $e->getMessage();
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
        <div class="col">
            <h1 class="page-title">Tambah Modul</h1>
            <p class="text-muted">Tambahkan modul baru untuk kursus "<?= htmlspecialchars($kursus['judul']) ?>"</p>
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
                            <input type="text" class="form-control" id="judul" name="judul" value="<?= $_POST['judul'] ?? '' ?>" required>
                            <div class="form-text">Contoh: Modul 1 - Pengenalan, Dasar-dasar, Studi Kasus, dll.</div>
                        </div>

                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi Modul</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?= $_POST['deskripsi'] ?? '' ?></textarea>
                            <div class="form-text">Jelaskan secara singkat tentang modul ini dan apa yang akan dipelajari.</div>
                        </div>

                        <div class="mb-3">
                            <label for="urutan" class="form-label">Urutan</label>
                            <input type="number" class="form-control" id="urutan" name="urutan" value="<?= $_POST['urutan'] ?? $nextUrutan ?>" min="1">
                            <div class="form-text">Urutan modul dalam kursus. Urutan modul terakhir: <?= $nextUrutan - 1 ?></div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= BASE_URL ?>/admin/modul?kursus_id=<?= $kursus_id ?>" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Modul</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Kursus</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">Judul Kursus</label>
                        <p class="fw-semibold"><?= htmlspecialchars($kursus['judul']) ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Status Kursus</label>
                        <p>
                            <span class="badge <?= $kursus['status'] === 'publikasi' ? 'bg-success' : ($kursus['status'] === 'draf' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                <?= ucfirst($kursus['status']) ?>
                            </span>
                        </p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Level Kursus</label>
                        <p>
                            <span class="badge bg-secondary"><?= ucfirst($kursus['level']) ?></span>
                        </p>
                    </div>

                    <div class="alert alert-info mb-0">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="bi bi-info-circle-fill fs-4"></i>
                            </div>
                            <div>
                                <h6>Tips Membuat Modul</h6>
                                <ul class="mb-0 ps-3">
                                    <li>Buat judul modul yang jelas dan deskriptif</li>
                                    <li>Kelompokkan materi berdasarkan topik yang berhubungan</li>
                                    <li>Susun modul secara logis dan berurutan</li>
                                    <li>Gunakan deskripsi untuk menjelaskan apa yang akan dipelajari</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php adminFooter(); ?>