<?php
// Path: views/admin/kursus/tambah.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Tambah Kursus", "Tambah kursus baru di Atlas LMS");

$db = dbConnect();
$pesan = '';
$tipe = '';

// Ambil semua kategori untuk dipilih
$stmt = $db->prepare("SELECT id, nama FROM kategori ORDER BY nama ASC");
$stmt->execute();
$kategoriList = $stmt->fetchAll();

// Ambil semua pengajar/instruktur
$stmt = $db->prepare("SELECT p.id, p.nama_lengkap FROM pengguna p 
                     JOIN role r ON p.role_id = r.id
                     WHERE r.nama = 'instruktur' OR r.nama = 'admin'
                     ORDER BY p.nama_lengkap ASC");
$stmt->execute();
$pengajarList = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $level = $_POST['level'] ?? 'semua level';
    $harga = floatval($_POST['harga'] ?? 0);
    $durasi = intval($_POST['durasi_menit'] ?? null);
    $status = $_POST['status'] ?? 'draf';
    $pembuat_id = $_POST['pembuat_id'] ?? $_SESSION['user']['id'];
    $kategori_ids = $_POST['kategori'] ?? [];

    $errors = [];

    if (empty($judul)) {
        $errors[] = "Judul kursus harus diisi";
    }

    if (empty($deskripsi)) {
        $errors[] = "Deskripsi kursus harus diisi";
    }

    if (empty($pembuat_id)) {
        $errors[] = "Pembuat kursus harus dipilih";
    }

    // Pengecekan upload gambar sampul
    $gambar_sampul = null;
    if (!empty($_FILES['gambar_sampul']['name'])) {
        $file_name = $_FILES['gambar_sampul']['name'];
        $file_size = $_FILES['gambar_sampul']['size'];
        $file_tmp = $_FILES['gambar_sampul']['tmp_name'];
        $file_type = $_FILES['gambar_sampul']['type'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $extensions = ["jpeg", "jpg", "png", "webp"];

        if (!in_array($file_ext, $extensions)) {
            $errors[] = "Format file gambar tidak didukung. Gunakan format jpeg, jpg, png, atau webp.";
        }

        if ($file_size > 2097152) { // Maksimal 2MB
            $errors[] = "Ukuran file gambar tidak boleh lebih dari 2MB";
        }

        if (empty($errors)) {
            $upload_dir = BASE_PATH . '/uploads/kursus/';

            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $gambar_sampul = uniqid() . '.' . $file_ext;
            move_uploaded_file($file_tmp, $upload_dir . $gambar_sampul);
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Simpan data kursus
            $id = generate_uuid();
            $stmt = $db->prepare("INSERT INTO kursus (id, judul, deskripsi, gambar_sampul, durasi_menit, level, harga, status, pembuat_id, waktu_dibuat, waktu_diperbarui) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$id, $judul, $deskripsi, $gambar_sampul, $durasi, $level, $harga, $status, $pembuat_id]);

            // Simpan kategori kursus
            if (!empty($kategori_ids)) {
                foreach ($kategori_ids as $kategori_id) {
                    $kk_id = generate_uuid();
                    $stmt = $db->prepare("INSERT INTO kursus_kategori (id, kursus_id, kategori_id, waktu_dibuat) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$kk_id, $id, $kategori_id]);
                }
            }

            $db->commit();
            $pesan = "Kursus berhasil ditambahkan";
            $tipe = "success";

            // Redirect ke halaman edit kursus
            header("Location: " . BASE_URL . "/admin/kursus/edit?id=" . $id . "&success=created");
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            $pesan = "Gagal menambahkan kursus: " . $e->getMessage();
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
            <h1 class="page-title">Tambah Kursus</h1>
            <p class="text-muted">Tambahkan kursus baru ke dalam sistem</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?= BASE_URL ?>/admin/kursus" class="btn btn-secondary">
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

    <div class="card">
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="mb-3">
                            <label for="judul" class="form-label">Judul Kursus <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="judul" name="judul" value="<?= $_POST['judul'] ?? '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="6" required><?= $_POST['deskripsi'] ?? '' ?></textarea>
                            <div class="form-text">Jelaskan tentang kursus, apa yang akan dipelajari, dan target peserta.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="level" class="form-label">Level Kursus</label>
                                    <select class="form-select" id="level" name="level">
                                        <option value="pemula" <?= isset($_POST['level']) && $_POST['level'] === 'pemula' ? 'selected' : '' ?>>Pemula</option>
                                        <option value="menengah" <?= isset($_POST['level']) && $_POST['level'] === 'menengah' ? 'selected' : '' ?>>Menengah</option>
                                        <option value="mahir" <?= isset($_POST['level']) && $_POST['level'] === 'mahir' ? 'selected' : '' ?>>Mahir</option>
                                        <option value="semua level" <?= (!isset($_POST['level']) || $_POST['level'] === 'semua level') ? 'selected' : '' ?>>Semua Level</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="durasi_menit" class="form-label">Durasi Kursus (menit)</label>
                                    <input type="number" class="form-control" id="durasi_menit" name="durasi_menit" value="<?= $_POST['durasi_menit'] ?? '' ?>" min="0">
                                    <div class="form-text">Estimasi total durasi kursus dalam menit.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="harga" class="form-label">Harga (Rp)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" id="harga" name="harga" value="<?= $_POST['harga'] ?? '0' ?>" min="0">
                                    </div>
                                    <div class="form-text">Masukkan 0 untuk kursus gratis.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status Kursus</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="draf" <?= (!isset($_POST['status']) || $_POST['status'] === 'draf') ? 'selected' : '' ?>>Draf</option>
                                        <option value="publikasi" <?= isset($_POST['status']) && $_POST['status'] === 'publikasi' ? 'selected' : '' ?>>Publikasi</option>
                                        <option value="arsip" <?= isset($_POST['status']) && $_POST['status'] === 'arsip' ? 'selected' : '' ?>>Arsip</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="mb-3">
                            <label for="gambar_sampul" class="form-label">Gambar Sampul</label>
                            <input type="file" class="form-control" id="gambar_sampul" name="gambar_sampul" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Format: JPEG, PNG, WEBP. Maks: 2MB. Rekomendasi ukuran: 1280x720 pixel.</div>
                            <div class="mt-2 text-center">
                                <div id="gambar-preview" class="img-thumbnail d-none"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="pembuat_id" class="form-label">Pengajar/Instruktur <span class="text-danger">*</span></label>
                            <select class="form-select" id="pembuat_id" name="pembuat_id" required>
                                <?php foreach ($pengajarList as $pengajar): ?>
                                    <option value="<?= $pengajar['id'] ?>" <?= (isset($_POST['pembuat_id']) && $_POST['pembuat_id'] === $pengajar['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pengajar['nama_lengkap']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="kategori" class="form-label">Kategori Kursus</label>
                            <?php if (empty($kategoriList)): ?>
                                <div class="alert alert-warning">
                                    Belum ada kategori. <a href="<?= BASE_URL ?>/admin/kategori/tambah">Tambah kategori</a> terlebih dahulu.
                                </div>
                            <?php else: ?>
                                <select class="form-select" id="kategori" name="kategori[]" multiple size="6">
                                    <?php foreach ($kategoriList as $kategori): ?>
                                        <option value="<?= $kategori['id'] ?>"
                                            <?= (isset($_POST['kategori']) && in_array($kategori['id'], $_POST['kategori'])) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($kategori['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Tekan CTRL (Windows) atau CMD (Mac) untuk memilih lebih dari satu kategori.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-secondary" onclick="window.history.back()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Kursus</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const gambarInput = document.getElementById('gambar_sampul');
        const gambarPreview = document.getElementById('gambar-preview');

        gambarInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    gambarPreview.innerHTML = `<img src="${e.target.result}" class="img-fluid" style="max-height: 200px;">`;
                    gambarPreview.classList.remove('d-none');
                }

                reader.readAsDataURL(this.files[0]);
            } else {
                gambarPreview.innerHTML = '';
                gambarPreview.classList.add('d-none');
            }
        });
    });
</script>

<?php adminFooter(); ?>