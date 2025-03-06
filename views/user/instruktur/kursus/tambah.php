<?php
// Path: views/user/instruktur/kursus/tambah.php

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

// Ambil daftar kategori untuk dropdown
$queryKategori = "SELECT id, nama FROM kategori ORDER BY nama ASC";
$stmtKategori = $db->prepare($queryKategori);
$stmtKategori->execute();
$kategori = $stmtKategori->fetchAll();

// Proses form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    
    if (!in_array($status, ['draf', 'publikasi'])) {
        $errors[] = "Status kursus tidak valid";
    }
    
    if (empty($kategoriIds)) {
        $errors[] = "Pilih minimal satu kategori";
    }
    
    // Upload gambar sampul jika ada
    $gambarSampul = null;
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
                $gambarSampul = $newFilename;
            } else {
                $errors[] = "Gagal mengupload gambar";
            }
        }
    }
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Buat kursus baru
            $kursusId = generate_uuid();
            
            $queryTambah = "INSERT INTO kursus (id, judul, deskripsi, gambar_sampul, durasi_menit, level, harga, status, pembuat_id) 
                           VALUES (:id, :judul, :deskripsi, :gambar_sampul, :durasi_menit, :level, :harga, :status, :pembuat_id)";
            
            $stmtTambah = $db->prepare($queryTambah);
            $stmtTambah->bindParam(':id', $kursusId);
            $stmtTambah->bindParam(':judul', $judul);
            $stmtTambah->bindParam(':deskripsi', $deskripsi);
            $stmtTambah->bindParam(':gambar_sampul', $gambarSampul);
            $stmtTambah->bindParam(':durasi_menit', $durasi);
            $stmtTambah->bindParam(':level', $level);
            $stmtTambah->bindParam(':harga', $harga);
            $stmtTambah->bindParam(':status', $status);
            $stmtTambah->bindParam(':pembuat_id', $userId);
            
            $stmtTambah->execute();
            
            // Tambahkan kategori
            foreach ($kategoriIds as $kategoriId) {
                $kursusKategoriId = generate_uuid();
                
                $queryKategoriKursus = "INSERT INTO kursus_kategori (id, kursus_id, kategori_id) 
                                        VALUES (:id, :kursus_id, :kategori_id)";
                
                $stmtKategoriKursus = $db->prepare($queryKategoriKursus);
                $stmtKategoriKursus->bindParam(':id', $kursusKategoriId);
                $stmtKategoriKursus->bindParam(':kursus_id', $kursusId);
                $stmtKategoriKursus->bindParam(':kategori_id', $kategoriId);
                $stmtKategoriKursus->execute();
            }
            
            $db->commit();
            
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Kursus berhasil dibuat'
            ];
            
            header('Location: ' . BASE_URL . '/user/instruktur/kursus/edit?id=' . $kursusId);
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
            
            // Hapus gambar yang sudah diupload jika ada error
            if ($gambarSampul && file_exists(BASE_PATH . '/uploads/kursus/' . $gambarSampul)) {
                unlink(BASE_PATH . '/uploads/kursus/' . $gambarSampul);
            }
        }
    }
}

if (!isset($errors)) {
    $errors = [];
}

$content = function() use ($kategori, $errors) {
    $baseUrl = BASE_URL;
?>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Buat Kursus Baru</h2>
                <a href="<?= $baseUrl ?>/user/instruktur/kursus" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong>
                    <ul class="mb-0">
                        <?php foreach($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="<?= $baseUrl ?>/user/instruktur/kursus/tambah" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="judul" class="form-label">Judul Kursus <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="judul" name="judul" 
                                value="<?= htmlspecialchars(isset($_POST['judul']) ? $_POST['judul'] : '') ?>" required minlength="5">
                            <div class="form-text">Minimal 5 karakter</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi Kursus <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" required minlength="20"><?= htmlspecialchars(isset($_POST['deskripsi']) ? $_POST['deskripsi'] : '') ?></textarea>
                            <div class="form-text">Minimal 20 karakter. Jelaskan apa yang akan dipelajari peserta dalam kursus ini.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="gambar_sampul" class="form-label">Gambar Sampul</label>
                            <input type="file" class="form-control" id="gambar_sampul" name="gambar_sampul" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Format: JPG, JPEG, PNG, WEBP. Maksimal 2MB. Ukuran optimal: 1280×720 piksel.</div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="level" class="form-label">Level Kursus <span class="text-danger">*</span></label>
                                <select class="form-select" id="level" name="level" required>
                                    <option value="pemula" <?= isset($_POST['level']) && $_POST['level'] == 'pemula' ? 'selected' : '' ?>>Pemula</option>
                                    <option value="menengah" <?= isset($_POST['level']) && $_POST['level'] == 'menengah' ? 'selected' : '' ?>>Menengah</option>
                                    <option value="mahir" <?= isset($_POST['level']) && $_POST['level'] == 'mahir' ? 'selected' : '' ?>>Mahir</option>
                                    <option value="semua level" <?= isset($_POST['level']) && $_POST['level'] == 'semua level' ? 'selected' : (!isset($_POST['level']) ? 'selected' : '') ?>>Semua Level</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="durasi" class="form-label">Durasi (menit)</label>
                                <input type="number" class="form-control" id="durasi" name="durasi" min="0" 
                                    value="<?= htmlspecialchars(isset($_POST['durasi']) ? $_POST['durasi'] : '') ?>">
                                <div class="form-text">Estimasi total durasi kursus dalam menit</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="harga" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="harga" name="harga" min="0" 
                                    value="<?= htmlspecialchars(isset($_POST['harga']) ? $_POST['harga'] : '0') ?>" required>
                                <div class="form-text">Masukkan 0 untuk kursus gratis</div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="draf" <?= isset($_POST['status']) && $_POST['status'] == 'draf' ? 'selected' : (!isset($_POST['status']) ? 'selected' : '') ?>>Draf</option>
                                    <option value="publikasi" <?= isset($_POST['status']) && $_POST['status'] == 'publikasi' ? 'selected' : '' ?>>Publikasi</option>
                                </select>
                                <div class="form-text">Pilih "Draf" jika belum siap dipublikasikan</div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <div class="row">
                                <?php foreach($kategori as $item): ?>
                                <div class="col-md-4 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="kategori[]" 
                                            id="kategori-<?= $item['id'] ?>" value="<?= $item['id'] ?>"
                                            <?= isset($_POST['kategori']) && in_array($item['id'], $_POST['kategori']) ? 'checked' : '' ?>>
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
                                    <i class="bi bi-save"></i> Simpan Kursus
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php
};

userLayout("Buat Kursus Baru", $content(), "instruktur");
?>