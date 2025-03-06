<?php
// Path: views/user/instruktur/kursus/edit.php

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
if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . '/user/instruktur/kursus');
    exit;
}

$kursusId = $_GET['id'];

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

// Ambil kategori kursus saat ini
$queryKategoriKursus = "SELECT kategori_id FROM kursus_kategori WHERE kursus_id = :kursus_id";
$stmtKategoriKursus = $db->prepare($queryKategoriKursus);
$stmtKategoriKursus->bindParam(':kursus_id', $kursusId);
$stmtKategoriKursus->execute();
$kategoriKursus = $stmtKategoriKursus->fetchAll(PDO::FETCH_COLUMN);

// Ambil semua kategori
$queryKategori = "SELECT id, nama FROM kategori ORDER BY nama ASC";
$stmtKategori = $db->prepare($queryKategori);
$stmtKategori->execute();
$kategori = $stmtKategori->fetchAll();

// Ambil modul kursus
$queryModul = "SELECT id, judul, urutan FROM modul WHERE kursus_id = :kursus_id ORDER BY urutan ASC";
$stmtModul = $db->prepare($queryModul);
$stmtModul->bindParam(':kursus_id', $kursusId);
$stmtModul->execute();
$modulDaftar = $stmtModul->fetchAll();

// Proses form update informasi kursus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_kursus') {
    $judul = isset($_POST['judul']) ? trim($_POST['judul']) : '';
    $deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
    $level = isset($_POST['level']) ? $_POST['level'] : 'semua level';
    $harga = isset($_POST['harga']) ? $_POST['harga'] : 0;
    $durasi = isset($_POST['durasi']) ? $_POST['durasi'] : null;
    $status = isset($_POST['status']) ? $_POST['status'] : 'draf';
    $kategoriIds = isset($_POST['kategori']) ? $_POST['kategori'] : [];

    $errors = [];

    // Validasi input
    if (empty($judul)) {
        $errors[] = "Judul kursus tidak boleh kosong";
    } elseif (strlen($judul) < 5) {
        $errors[] = "Judul kursus minimal 5 karakter";
    }

    if (empty($deskripsi)) {
        $errors[] = "Deskripsi kursus tidak boleh kosong";
    } elseif (strlen($deskripsi) < 20) {
        $errors[] = "Deskripsi kursus minimal 20 karakter";
    }

    if (!in_array($level, ['pemula', 'menengah', 'mahir', 'semua level'])) {
        $errors[] = "Level kursus tidak valid";
    }

    if (!is_numeric($harga) || $harga < 0) {
        $errors[] = "Harga kursus tidak valid";
    }

    if ($durasi !== null && (!is_numeric($durasi) || $durasi < 0)) {
        $errors[] = "Durasi kursus tidak valid";
    }

    if (!in_array($status, ['draf', 'publikasi', 'arsip'])) {
        $errors[] = "Status kursus tidak valid";
    }

    if (empty($kategoriIds)) {
        $errors[] = "Pilih minimal satu kategori";
    }

    // Upload gambar sampul jika ada
    $gambarSampul = $kursus['gambar_sampul'];
    if (isset($_FILES['gambar_sampul']) && $_FILES['gambar_sampul']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['gambar_sampul']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            $errors[] = "Format gambar tidak valid. Format yang diizinkan: " . implode(', ', $allowed);
        } elseif ($_FILES['gambar_sampul']['size'] > 2 * 1024 * 1024) {
            $errors[] = "Ukuran gambar maksimal 2MB";
        } else {
            $uploadDir = BASE_PATH . '/uploads/kursus/';

            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newFilename = uniqid() . '.' . $ext;
            $uploadFile = $uploadDir . $newFilename;

            if (move_uploaded_file($_FILES['gambar_sampul']['tmp_name'], $uploadFile)) {
                // Hapus gambar lama jika ada
                if ($gambarSampul && file_exists($uploadDir . $gambarSampul)) {
                    unlink($uploadDir . $gambarSampul);
                }

                $gambarSampul = $newFilename;
            } else {
                $errors[] = "Gagal mengupload gambar";
            }
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Update data kursus
            $queryUpdate = "UPDATE kursus SET 
                           judul = :judul, 
                           deskripsi = :deskripsi, 
                           gambar_sampul = :gambar_sampul, 
                           durasi_menit = :durasi_menit, 
                           level = :level, 
                           harga = :harga, 
                           status = :status 
                           WHERE id = :id AND pembuat_id = :pembuat_id";

            $stmtUpdate = $db->prepare($queryUpdate);
            $stmtUpdate->bindParam(':judul', $judul);
            $stmtUpdate->bindParam(':deskripsi', $deskripsi);
            $stmtUpdate->bindParam(':gambar_sampul', $gambarSampul);
            $stmtUpdate->bindParam(':durasi_menit', $durasi);
            $stmtUpdate->bindParam(':level', $level);
            $stmtUpdate->bindParam(':harga', $harga);
            $stmtUpdate->bindParam(':status', $status);
            $stmtUpdate->bindParam(':id', $kursusId);
            $stmtUpdate->bindParam(':pembuat_id', $userId);

            $stmtUpdate->execute();

            // Hapus kategori yang ada
            $queryDeleteKategori = "DELETE FROM kursus_kategori WHERE kursus_id = :kursus_id";
            $stmtDeleteKategori = $db->prepare($queryDeleteKategori);
            $stmtDeleteKategori->bindParam(':kursus_id', $kursusId);
            $stmtDeleteKategori->execute();

            // Tambah kategori baru
            foreach ($kategoriIds as $kategoriId) {
                $kursusKategoriId = generate_uuid();

                $queryTambahKategori = "INSERT INTO kursus_kategori (id, kursus_id, kategori_id) 
                                       VALUES (:id, :kursus_id, :kategori_id)";

                $stmtTambahKategori = $db->prepare($queryTambahKategori);
                $stmtTambahKategori->bindParam(':id', $kursusKategoriId);
                $stmtTambahKategori->bindParam(':kursus_id', $kursusId);
                $stmtTambahKategori->bindParam(':kategori_id', $kategoriId);
                $stmtTambahKategori->execute();
            }

            $db->commit();

            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Kursus berhasil diperbarui'
            ];

            // Redirect ke halaman yang sama untuk refresh data
            header('Location: ' . BASE_URL . '/user/instruktur/kursus/edit?id=' . $kursusId);
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();

            // Kembalikan gambar lama jika upload gagal
            $gambarSampul = $kursus['gambar_sampul'];
        }
    }
}

// Proses tambah modul baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_modul') {
    $judulModul = isset($_POST['judul_modul']) ? trim($_POST['judul_modul']) : '';
    $deskripsiModul = isset($_POST['deskripsi_modul']) ? trim($_POST['deskripsi_modul']) : '';

    $errorsModul = [];

    if (empty($judulModul)) {
        $errorsModul[] = "Judul modul tidak boleh kosong";
    }

    if (empty($errorsModul)) {
        try {
            // Hitung urutan terakhir
            $queryUrutan = "SELECT MAX(urutan) as max_urutan FROM modul WHERE kursus_id = :kursus_id";
            $stmtUrutan = $db->prepare($queryUrutan);
            $stmtUrutan->bindParam(':kursus_id', $kursusId);
            $stmtUrutan->execute();
            $urutan = $stmtUrutan->fetch()['max_urutan'] ?? 0;
            $urutan++;

            // Tambah modul baru
            $modulId = generate_uuid();

            $queryTambahModul = "INSERT INTO modul (id, kursus_id, judul, deskripsi, urutan) 
                                VALUES (:id, :kursus_id, :judul, :deskripsi, :urutan)";

            $stmtTambahModul = $db->prepare($queryTambahModul);
            $stmtTambahModul->bindParam(':id', $modulId);
            $stmtTambahModul->bindParam(':kursus_id', $kursusId);
            $stmtTambahModul->bindParam(':judul', $judulModul);
            $stmtTambahModul->bindParam(':deskripsi', $deskripsiModul);
            $stmtTambahModul->bindParam(':urutan', $urutan);

            if ($stmtTambahModul->execute()) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'message' => 'Modul berhasil ditambahkan'
                ];

                header('Location: ' . BASE_URL . '/user/instruktur/kursus/edit?id=' . $kursusId . '#modul');
                exit;
            } else {
                $errorsModul[] = "Gagal menambahkan modul";
            }
        } catch (Exception $e) {
            $errorsModul[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Proses hapus modul
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_modul') {
    $modulId = isset($_POST['modul_id']) ? $_POST['modul_id'] : '';

    if (empty($modulId)) {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'ID modul tidak valid'
        ];
    } else {
        try {
            // Periksa apakah modul milik kursus ini
            $queryCheckModul = "SELECT COUNT(*) as count FROM modul 
                              WHERE id = :modul_id AND kursus_id = :kursus_id";
            $stmtCheckModul = $db->prepare($queryCheckModul);
            $stmtCheckModul->bindParam(':modul_id', $modulId);
            $stmtCheckModul->bindParam(':kursus_id', $kursusId);
            $stmtCheckModul->execute();

            if ($stmtCheckModul->fetch()['count'] > 0) {
                // Cek apakah ada materi dalam modul ini
                $queryCheckMateri = "SELECT COUNT(*) as count FROM materi WHERE modul_id = :modul_id";
                $stmtCheckMateri = $db->prepare($queryCheckMateri);
                $stmtCheckMateri->bindParam(':modul_id', $modulId);
                $stmtCheckMateri->execute();

                if ($stmtCheckMateri->fetch()['count'] > 0) {
                    $_SESSION['alert'] = [
                        'type' => 'warning',
                        'message' => 'Modul tidak dapat dihapus karena masih memiliki materi'
                    ];
                } else {
                    // Hapus modul
                    $queryHapusModul = "DELETE FROM modul WHERE id = :modul_id";
                    $stmtHapusModul = $db->prepare($queryHapusModul);
                    $stmtHapusModul->bindParam(':modul_id', $modulId);

                    if ($stmtHapusModul->execute()) {
                        $_SESSION['alert'] = [
                            'type' => 'success',
                            'message' => 'Modul berhasil dihapus'
                        ];
                    } else {
                        $_SESSION['alert'] = [
                            'type' => 'danger',
                            'message' => 'Gagal menghapus modul'
                        ];
                    }
                }
            } else {
                $_SESSION['alert'] = [
                    'type' => 'danger',
                    'message' => 'Modul tidak ditemukan'
                ];
            }
        } catch (Exception $e) {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ];
        }

        header('Location: ' . BASE_URL . '/user/instruktur/kursus/edit?id=' . $kursusId . '#modul');
        exit;
    }
}

// Inisialisasi errors dan errorsModul jika belum ada
if (!isset($errors)) {
    $errors = [];
}
if (!isset($errorsModul)) {
    $errorsModul = [];
}

$content = function() use ($kursus, $kategori, $kategoriKursus, $modulDaftar, $errors, $errorsModul, $db) {    $baseUrl = BASE_URL;
?>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Edit Kursus</h2>
                <a href="<?= $baseUrl ?>/user/instruktur/kursus" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <?php if (isset($_SESSION['alert'])): ?>
                <div class="alert alert-<?= $_SESSION['alert']['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['alert']['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['alert']); ?>
            <?php endif; ?>

            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">
                        <i class="bi bi-info-circle"></i> Informasi Kursus
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="modul-tab" data-bs-toggle="tab" data-bs-target="#modul" type="button" role="tab" aria-controls="modul" aria-selected="false">
                        <i class="bi bi-collection"></i> Modul & Materi
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="pengaturan-tab" data-bs-toggle="tab" data-bs-target="#pengaturan" type="button" role="tab" aria-controls="pengaturan" aria-selected="false">
                        <i class="bi bi-gear"></i> Pengaturan
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <!-- Tab Informasi Kursus -->
                <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                    <div class="card">
                        <div class="card-body">
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?= $error ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="<?= $baseUrl ?>/user/instruktur/kursus/edit?id=<?= $kursus['id'] ?>" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_kursus">

                                <div class="mb-3">
                                    <label for="judul" class="form-label">Judul Kursus <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="judul" name="judul"
                                        value="<?= htmlspecialchars(isset($_POST['judul']) ? $_POST['judul'] : $kursus['judul']) ?>" required minlength="5">
                                    <div class="form-text">Minimal 5 karakter</div>
                                </div>

                                <div class="mb-3">
                                    <label for="deskripsi" class="form-label">Deskripsi Kursus <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" required minlength="20"><?= htmlspecialchars(isset($_POST['deskripsi']) ? $_POST['deskripsi'] : $kursus['deskripsi']) ?></textarea>
                                    <div class="form-text">Minimal 20 karakter. Jelaskan apa yang akan dipelajari peserta dalam kursus ini.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="gambar_sampul" class="form-label">Gambar Sampul</label>
                                    <?php if (!empty($kursus['gambar_sampul'])): ?>
                                        <div class="mb-2">
                                            <img src="<?= $baseUrl ?>/uploads/kursus/<?= $kursus['gambar_sampul'] ?>"
                                                class="img-thumbnail" alt="Gambar Sampul" style="max-height: 200px;">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control" id="gambar_sampul" name="gambar_sampul" accept="image/jpeg,image/png,image/webp">
                                    <div class="form-text">Format: JPG, JPEG, PNG, WEBP. Maksimal 2MB. Ukuran optimal: 1280×720 piksel.</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="level" class="form-label">Level Kursus <span class="text-danger">*</span></label>
                                        <select class="form-select" id="level" name="level" required>
                                            <option value="pemula" <?= (isset($_POST['level']) && $_POST['level'] == 'pemula') || $kursus['level'] == 'pemula' ? 'selected' : '' ?>>Pemula</option>
                                            <option value="menengah" <?= (isset($_POST['level']) && $_POST['level'] == 'menengah') || $kursus['level'] == 'menengah' ? 'selected' : '' ?>>Menengah</option>
                                            <option value="mahir" <?= (isset($_POST['level']) && $_POST['level'] == 'mahir') || $kursus['level'] == 'mahir' ? 'selected' : '' ?>>Mahir</option>
                                            <option value="semua level" <?= (isset($_POST['level']) && $_POST['level'] == 'semua level') || $kursus['level'] == 'semua level' ? 'selected' : '' ?>>Semua Level</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="durasi" class="form-label">Durasi (menit)</label>
                                        <input type="number" class="form-control" id="durasi" name="durasi" min="0"
                                            value="<?= htmlspecialchars(isset($_POST['durasi']) ? $_POST['durasi'] : $kursus['durasi_menit']) ?>">
                                        <div class="form-text">Estimasi total durasi kursus dalam menit</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="harga" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="harga" name="harga" min="0"
                                            value="<?= htmlspecialchars(isset($_POST['harga']) ? $_POST['harga'] : $kursus['harga']) ?>" required>
                                        <div class="form-text">Masukkan 0 untuk kursus gratis</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="draf" <?= (isset($_POST['status']) && $_POST['status'] == 'draf') || $kursus['status'] == 'draf' ? 'selected' : '' ?>>Draf</option>
                                            <option value="publikasi" <?= (isset($_POST['status']) && $_POST['status'] == 'publikasi') || $kursus['status'] == 'publikasi' ? 'selected' : '' ?>>Publikasi</option>
                                            <option value="arsip" <?= (isset($_POST['status']) && $_POST['status'] == 'arsip') || $kursus['status'] == 'arsip' ? 'selected' : '' ?>>Arsip</option>
                                        </select>
                                        <div class="form-text">Pilih "Draf" jika belum siap dipublikasikan</div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                    <div class="row">
                                        <?php foreach ($kategori as $item): ?>
                                            <div class="col-md-4 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="kategori[]"
                                                        id="kategori-<?= $item['id'] ?>" value="<?= $item['id'] ?>"
                                                        <?= (isset($_POST['kategori']) && in_array($item['id'], $_POST['kategori'])) ||
                                                            in_array($item['id'], $kategoriKursus) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="kategori-<?= $item['id'] ?>">
                                                        <?= htmlspecialchars($item['nama']) ?>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="form-text">Pilih minimal satu kategori</div>
                                </div>

                                <div class="mb-3">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-save"></i> Simpan Perubahan
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tab Modul & Materi -->
                <div class="tab-pane fade" id="modul" role="tabpanel" aria-labelledby="modul-tab">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Tambah Modul Baru</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($errorsModul)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <strong>Error!</strong>
                                    <ul class="mb-0">
                                        <?php foreach ($errorsModul as $error): ?>
                                            <li><?= $error ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="<?= $baseUrl ?>/user/instruktur/kursus/edit?id=<?= $kursus['id'] ?>#modul">
                                <input type="hidden" name="action" value="tambah_modul">

                                <div class="mb-3">
                                    <label for="judul_modul" class="form-label">Judul Modul <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="judul_modul" name="judul_modul" required>
                                </div>

                                <div class="mb-3">
                                    <label for="deskripsi_modul" class="form-label">Deskripsi Modul</label>
                                    <textarea class="form-control" id="deskripsi_modul" name="deskripsi_modul" rows="3"></textarea>
                                </div>

                                <div class="mb-3">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-plus-circle"></i> Tambah Modul
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Daftar Modul</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($modulDaftar)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-collection" style="font-size: 3rem; color: #ccc;"></i>
                                    <p class="mt-3">Belum ada modul. Buat modul pertama untuk kursus Anda.</p>
                                </div>
                            <?php else: ?>
                                <div class="accordion" id="accordionModul">
                                    <?php foreach ($modulDaftar as $index => $modul): ?>
                                        <div class="accordion-item mb-3 border">
                                            <h2 class="accordion-header" id="heading-<?= $modul['id'] ?>">
                                                <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#collapse-<?= $modul['id'] ?>"
                                                    aria-expanded="false" aria-controls="collapse-<?= $modul['id'] ?>">
                                                    <span class="badge bg-secondary me-2"><?= $modul['urutan'] ?></span>
                                                    <?= htmlspecialchars($modul['judul']) ?>
                                                </button>
                                            </h2>
                                            <div id="collapse-<?= $modul['id'] ?>" class="accordion-collapse collapse"
                                                aria-labelledby="heading-<?= $modul['id'] ?>" data-bs-parent="#accordionModul">
                                                <div class="accordion-body">
                                                    <div class="d-flex justify-content-between mb-3">
                                                        <a href="<?= $baseUrl ?>/user/instruktur/modul/edit?id=<?= $modul['id'] ?>" class="btn btn-sm btn-primary">
                                                            <i class="bi bi-pencil"></i> Edit Modul
                                                        </a>
                                                        <form method="POST" action="<?= $baseUrl ?>/user/instruktur/kursus/edit?id=<?= $kursus['id'] ?>#modul"
                                                            onsubmit="return confirm('Yakin ingin menghapus modul ini?');">
                                                            <input type="hidden" name="action" value="hapus_modul">
                                                            <input type="hidden" name="modul_id" value="<?= $modul['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="bi bi-trash"></i> Hapus
                                                            </button>
                                                        </form>
                                                    </div>

                                                    <div class="d-grid gap-2 mb-3">
                                                        <a href="<?= $baseUrl ?>/user/instruktur/materi/tambah?modul_id=<?= $modul['id'] ?>" class="btn btn-success btn-sm">
                                                            <i class="bi bi-plus-circle"></i> Tambah Materi
                                                        </a>
                                                    </div>

                                                    <?php
                                                    // Ambil materi dalam modul
                                                    $queryMateri = "SELECT id, judul, tipe, urutan FROM materi 
                                                                  WHERE modul_id = :modul_id ORDER BY urutan ASC";
                                                    $stmtMateri = $db->prepare($queryMateri);
                                                    $stmtMateri->bindParam(':modul_id', $modul['id']);
                                                    $stmtMateri->execute();
                                                    $materiList = $stmtMateri->fetchAll();
                                                    ?>

                                                    <?php if (empty($materiList)): ?>
                                                        <div class="alert alert-info">
                                                            Belum ada materi dalam modul ini. Tambahkan materi sekarang.
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="list-group">
                                                            <?php foreach ($materiList as $materi): ?>
                                                                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                                    <div>
                                                                        <span class="badge 
                                                                            <?= $materi['tipe'] === 'video' ? 'bg-danger' : ($materi['tipe'] === 'artikel' ? 'bg-primary' : ($materi['tipe'] === 'dokumen' ? 'bg-info' : ($materi['tipe'] === 'quiz' ? 'bg-warning' : 'bg-secondary'))) ?> 
                                                                            me-2">
                                                                            <?= ucfirst($materi['tipe']) ?>
                                                                        </span>
                                                                        <?= htmlspecialchars($materi['judul']) ?>
                                                                    </div>
                                                                    <div>
                                                                        <a href="<?= $baseUrl ?>/user/instruktur/materi/edit?id=<?= $materi['id'] ?>"
                                                                            class="btn btn-sm btn-outline-primary me-1">
                                                                            <i class="bi bi-pencil"></i>
                                                                        </a>
                                                                        <a href="<?= $baseUrl ?>/user/instruktur/materi/hapus?id=<?= $materi['id'] ?>"
                                                                            class="btn btn-sm btn-outline-danger"
                                                                            onclick="return confirm('Yakin ingin menghapus materi ini?');">
                                                                            <i class="bi bi-trash"></i>
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tab Pengaturan -->
                <div class="tab-pane fade" id="pengaturan" role="tabpanel" aria-labelledby="pengaturan-tab">
                    <div class="card">
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                Pengaturan kursus akan segera tersedia.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
};

userLayout("Edit Kursus - " . $kursus['judul'], $content(), "instruktur");
?>