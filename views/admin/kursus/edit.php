<?php
// Path: views/admin/kursus/edit.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Edit Kursus", "Edit kursus di Atlas LMS");

$db = dbConnect();
$pesan = '';
$tipe = '';

// Cek parameter id
if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "/admin/kursus");
    exit;
}

$id = $_GET['id'];

// Ambil data kursus
$stmt = $db->prepare("SELECT * FROM kursus WHERE id = ?");
$stmt->execute([$id]);
$kursus = $stmt->fetch();

if (!$kursus) {
    header("Location: " . BASE_URL . "/admin/kursus");
    exit;
}

// Ambil data kategori kursus
$stmt = $db->prepare("SELECT kategori_id FROM kursus_kategori WHERE kursus_id = ?");
$stmt->execute([$id]);
$kursusKategori = $stmt->fetchAll(PDO::FETCH_COLUMN);

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

// Pesan sukses jika dari halaman tambah kursus
if (isset($_GET['success']) && $_GET['success'] === 'created') {
    $pesan = "Kursus berhasil ditambahkan. Anda dapat melanjutkan untuk mengedit kursus.";
    $tipe = "success";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $level = $_POST['level'] ?? 'semua level';
    $harga = floatval($_POST['harga'] ?? 0);
    $durasi = !empty($_POST['durasi_menit']) ? intval($_POST['durasi_menit']) : null;
    $status = $_POST['status'] ?? 'draf';
    $pembuat_id = $_POST['pembuat_id'] ?? $kursus['pembuat_id'];
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
    $gambar_sampul = $kursus['gambar_sampul']; // Default ke gambar yang sudah ada

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

            // Hapus gambar lama jika ada
            if (!empty($kursus['gambar_sampul']) && file_exists($upload_dir . $kursus['gambar_sampul'])) {
                unlink($upload_dir . $kursus['gambar_sampul']);
            }

            $gambar_sampul = uniqid() . '.' . $file_ext;
            move_uploaded_file($file_tmp, $upload_dir . $gambar_sampul);
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Update data kursus
            $stmt = $db->prepare("UPDATE kursus SET 
                                judul = ?, 
                                deskripsi = ?, 
                                gambar_sampul = ?, 
                                durasi_menit = ?, 
                                level = ?, 
                                harga = ?, 
                                status = ?, 
                                pembuat_id = ?, 
                                waktu_diperbarui = NOW() 
                                WHERE id = ?");
            $stmt->execute([$judul, $deskripsi, $gambar_sampul, $durasi, $level, $harga, $status, $pembuat_id, $id]);

            // Update kategori kursus
            // Hapus kategori lama
            $stmt = $db->prepare("DELETE FROM kursus_kategori WHERE kursus_id = ?");
            $stmt->execute([$id]);

            // Tambahkan kategori baru
            if (!empty($kategori_ids)) {
                foreach ($kategori_ids as $kategori_id) {
                    $kk_id = generate_uuid();
                    $stmt = $db->prepare("INSERT INTO kursus_kategori (id, kursus_id, kategori_id, waktu_dibuat) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$kk_id, $id, $kategori_id]);
                }
            }

            $db->commit();

            $pesan = "Kursus berhasil diperbarui";
            $tipe = "success";

            // Refresh data kursus
            $stmt = $db->prepare("SELECT * FROM kursus WHERE id = ?");
            $stmt->execute([$id]);
            $kursus = $stmt->fetch();

            // Refresh data kategori kursus
            $stmt = $db->prepare("SELECT kategori_id FROM kursus_kategori WHERE kursus_id = ?");
            $stmt->execute([$id]);
            $kursusKategori = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $db->rollBack();
            $pesan = "Gagal memperbarui kursus: " . $e->getMessage();
            $tipe = "danger";
        }
    } else {
        $pesan = implode("<br>", $errors);
        $tipe = "danger";
    }
}

// Ambil data statistik
// Jumlah modul
$stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM modul WHERE kursus_id = ?");
$stmt->execute([$id]);
$jumlahModul = $stmt->fetch()['jumlah'];

// Jumlah materi
$stmt = $db->prepare("
    SELECT COUNT(*) as jumlah FROM materi m
    JOIN modul mo ON m.modul_id = mo.id
    WHERE mo.kursus_id = ?
");
$stmt->execute([$id]);
$jumlahMateri = $stmt->fetch()['jumlah'];

// Jumlah pendaftaran
$stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM pendaftaran WHERE kursus_id = ?");
$stmt->execute([$id]);
$jumlahPendaftaran = $stmt->fetch()['jumlah'];

// Rata-rata progres
$stmt = $db->prepare("SELECT AVG(progres_persen) as rata_progres FROM pendaftaran WHERE kursus_id = ?");
$stmt->execute([$id]);
$rataProgres = $stmt->fetch()['rata_progres'] ?? 0;
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="page-title">Edit Kursus</h1>
            <p class="text-muted">Edit informasi kursus "<?= htmlspecialchars($kursus['judul']) ?>"</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?= BASE_URL ?>/admin/kursus" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <a href="<?= BASE_URL ?>/admin/modul?kursus_id=<?= $id ?>" class="btn btn-primary">
                <i class="bi bi-folder"></i> Kelola Modul
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
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="judul" class="form-label">Judul Kursus <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="judul" name="judul" value="<?= htmlspecialchars($kursus['judul']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="6" required><?= htmlspecialchars($kursus['deskripsi']) ?></textarea>
                            <div class="form-text">Jelaskan tentang kursus, apa yang akan dipelajari, dan target peserta.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="level" class="form-label">Level Kursus</label>
                                    <select class="form-select" id="level" name="level">
                                        <option value="pemula" <?= $kursus['level'] === 'pemula' ? 'selected' : '' ?>>Pemula</option>
                                        <option value="menengah" <?= $kursus['level'] === 'menengah' ? 'selected' : '' ?>>Menengah</option>
                                        <option value="mahir" <?= $kursus['level'] === 'mahir' ? 'selected' : '' ?>>Mahir</option>
                                        <option value="semua level" <?= $kursus['level'] === 'semua level' ? 'selected' : '' ?>>Semua Level</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="durasi_menit" class="form-label">Durasi Kursus (menit)</label>
                                    <input type="number" class="form-control" id="durasi_menit" name="durasi_menit" value="<?= $kursus['durasi_menit'] ?? '' ?>" min="0">
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
                                        <input type="number" class="form-control" id="harga" name="harga" value="<?= $kursus['harga'] ?>" min="0">
                                    </div>
                                    <div class="form-text">Masukkan 0 untuk kursus gratis.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status Kursus</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="draf" <?= $kursus['status'] === 'draf' ? 'selected' : '' ?>>Draf</option>
                                        <option value="publikasi" <?= $kursus['status'] === 'publikasi' ? 'selected' : '' ?>>Publikasi</option>
                                        <option value="arsip" <?= $kursus['status'] === 'arsip' ? 'selected' : '' ?>>Arsip</option>
                                    </select>
                                    <div class="form-text">Hanya kursus dengan status "Publikasi" yang akan terlihat oleh siswa.</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="pembuat_id" class="form-label">Pengajar/Instruktur <span class="text-danger">*</span></label>
                            <select class="form-select" id="pembuat_id" name="pembuat_id" required>
                                <?php foreach ($pengajarList as $pengajar): ?>
                                    <option value="<?= $pengajar['id'] ?>" <?= $kursus['pembuat_id'] === $pengajar['id'] ? 'selected' : '' ?>>
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
                                <select class="form-select" id="kategori" name="kategori[]" multiple size="5">
                                    <?php foreach ($kategoriList as $kategori): ?>
                                        <option value="<?= $kategori['id'] ?>" <?= in_array($kategori['id'], $kursusKategori) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($kategori['nama']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">Tekan CTRL (Windows) atau CMD (Mac) untuk memilih lebih dari satu kategori.</div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="gambar_sampul" class="form-label">Gambar Sampul</label>
                            <input type="file" class="form-control" id="gambar_sampul" name="gambar_sampul" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Format: JPEG, PNG, WEBP. Maks: 2MB. Rekomendasi ukuran: 1280x720 pixel. Kosongkan jika tidak ingin mengubah gambar.</div>

                            <?php if (!empty($kursus['gambar_sampul'])): ?>
                                <div class="mt-2">
                                    <label class="form-label">Gambar Saat Ini:</label>
                                    <div>
                                        <img src="<?= BASE_URL ?>/uploads/kursus/<?= $kursus['gambar_sampul'] ?>" alt="<?= htmlspecialchars($kursus['judul']) ?>" class="img-thumbnail" style="max-height: 150px;">
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="mt-2 text-center">
                                <div id="gambar-preview" class="img-thumbnail d-none"></div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= BASE_URL ?>/admin/kursus" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Statistik Kursus</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Jumlah Modul</span>
                            <span class="badge bg-primary rounded-pill"><?= $jumlahModul ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Jumlah Materi</span>
                            <span class="badge bg-info rounded-pill"><?= $jumlahMateri ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Jumlah Pendaftaran</span>
                            <span class="badge bg-success rounded-pill"><?= $jumlahPendaftaran ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>Rata-rata Progres</span>
                            <div class="progress" style="width: 100px; height: 10px;">
                                <div class="progress-bar" role="progressbar" style="width: <?= $rataProgres ?>%;" aria-valuenow="<?= $rataProgres ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span><?= number_format($rataProgres, 1) ?>%</span>
                        </li>
                    </ul>

                    <div class="mt-3">
                        <div class="d-grid gap-2">
                            <a href="<?= BASE_URL ?>/admin/modul?kursus_id=<?= $id ?>" class="btn btn-outline-primary">
                                <i class="bi bi-folder"></i> Kelola Modul
                            </a>
                            <a href="<?= BASE_URL ?>/admin/pendaftaran?kursus_id=<?= $id ?>" class="btn btn-outline-success">
                                <i class="bi bi-person-check"></i> Lihat Pendaftaran
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Lainnya</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">ID Kursus:</span>
                            <span class="text-muted"><?= $id ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Dibuat Pada:</span>
                            <span><?= date('d/m/Y H:i', strtotime($kursus['waktu_dibuat'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Terakhir Diperbarui:</span>
                            <span><?= date('d/m/Y H:i', strtotime($kursus['waktu_diperbarui'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Status:</span>
                            <span class="badge <?php
                                                switch ($kursus['status']) {
                                                    case 'publikasi':
                                                        echo 'bg-success';
                                                        break;
                                                    case 'draf':
                                                        echo 'bg-warning text-dark';
                                                        break;
                                                    case 'arsip':
                                                        echo 'bg-secondary';
                                                        break;
                                                }
                                                ?>"><?= ucfirst($kursus['status']) ?></span>
                        </li>
                    </ul>

                    <?php if ($jumlahPendaftaran == 0): ?>
                        <div class="mt-3">
                            <a href="<?= BASE_URL ?>/admin/kursus/hapus?id=<?= $id ?>" class="btn btn-outline-danger w-100">
                                <i class="bi bi-trash"></i> Hapus Kursus
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            Kursus ini memiliki <?= $jumlahPendaftaran ?> pendaftaran. Hapus semua pendaftaran terlebih dahulu sebelum menghapus kursus.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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