<?php
// Path: views/user/diskusi/tambah.php

require_once BASE_PATH . '/layouts/user/user-layout.php';

$userId = $_SESSION['user']['id'];
$db = dbConnect();

// Ambil daftar kursus yang diikuti oleh pengguna
$query = "SELECT k.id, k.judul 
          FROM kursus k
          JOIN pendaftaran p ON k.id = p.kursus_id
          WHERE p.pengguna_id = :user_id AND p.status = 'aktif'
          ORDER BY k.judul ASC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$kursus = $stmt->fetchAll();

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = isset($_POST['judul']) ? trim($_POST['judul']) : '';
    $isi = isset($_POST['isi']) ? trim($_POST['isi']) : '';
    $kursusId = isset($_POST['kursus_id']) ? $_POST['kursus_id'] : '';
    $materiId = isset($_POST['materi_id']) && !empty($_POST['materi_id']) ? $_POST['materi_id'] : null;
    
    $errors = [];
    
    if (empty($judul)) {
        $errors[] = "Judul diskusi tidak boleh kosong";
    } elseif (strlen($judul) < 5) {
        $errors[] = "Judul diskusi minimal 5 karakter";
    }
    
    if (empty($isi)) {
        $errors[] = "Isi diskusi tidak boleh kosong";
    } elseif (strlen($isi) < 10) {
        $errors[] = "Isi diskusi minimal 10 karakter";
    }
    
    if (empty($kursusId)) {
        $errors[] = "Kursus harus dipilih";
    }
    
    // Verifikasi kursus yang dipilih diikuti oleh pengguna
    if (!empty($kursusId)) {
        $queryVerifikasi = "SELECT COUNT(*) as terdaftar FROM pendaftaran 
                            WHERE pengguna_id = :user_id AND kursus_id = :kursus_id AND status = 'aktif'";
        $stmtVerifikasi = $db->prepare($queryVerifikasi);
        $stmtVerifikasi->bindParam(':user_id', $userId);
        $stmtVerifikasi->bindParam(':kursus_id', $kursusId);
        $stmtVerifikasi->execute();
        $terdaftar = $stmtVerifikasi->fetch()['terdaftar'];
        
        if (!$terdaftar) {
            $errors[] = "Anda tidak terdaftar pada kursus yang dipilih";
        }
    }
    
    if (empty($errors)) {
        $diskusiId = generate_uuid();
        
        $queryTambah = "INSERT INTO diskusi (id, kursus_id, materi_id, pengguna_id, judul, isi) 
                         VALUES (:id, :kursus_id, :materi_id, :pengguna_id, :judul, :isi)";
        $stmtTambah = $db->prepare($queryTambah);
        $stmtTambah->bindParam(':id', $diskusiId);
        $stmtTambah->bindParam(':kursus_id', $kursusId);
        $stmtTambah->bindParam(':materi_id', $materiId);
        $stmtTambah->bindParam(':pengguna_id', $userId);
        $stmtTambah->bindParam(':judul', $judul);
        $stmtTambah->bindParam(':isi', $isi);
        
        if ($stmtTambah->execute()) {
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Diskusi berhasil dibuat'
            ];
            header('Location: ' . BASE_URL . '/user/diskusi/detail?id=' . $diskusiId);
            exit;
        } else {
            $errors[] = "Gagal membuat diskusi: " . implode(", ", $stmtTambah->errorInfo());
        }
    }
}

// Inisialisasi errors jika belum ada
if (!isset($errors)) {
    $errors = [];
}

$content = function() use ($kursus, $errors) {
    $baseUrl = BASE_URL;
?>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">Buat Diskusi Baru</h2>
                <a href="<?= $baseUrl ?>/user/diskusi" class="btn btn-outline-secondary">
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

            <?php if (empty($kursus)): ?>
                <div class="alert alert-warning" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Anda belum terdaftar di kursus apapun. Silakan daftar kursus terlebih dahulu untuk memulai diskusi.
                </div>
                <div class="text-center py-5">
                    <a href="<?= $baseUrl ?>/kursus" class="btn btn-primary">Lihat Daftar Kursus</a>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="<?= $baseUrl ?>/user/diskusi/tambah">
                            <div class="mb-3">
                                <label for="kursus_id" class="form-label">Kursus</label>
                                <select class="form-select" id="kursus_id" name="kursus_id" required>
                                    <option value="">Pilih Kursus</option>
                                    <?php foreach($kursus as $k): ?>
                                        <option value="<?= $k['id'] ?>" <?= isset($_POST['kursus_id']) && $_POST['kursus_id'] == $k['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($k['judul']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="materi-container" style="display:none;">
                                <label for="materi_id" class="form-label">Materi (Opsional)</label>
                                <select class="form-select" id="materi_id" name="materi_id">
                                    <option value="">Pilih Materi (Opsional)</option>
                                </select>
                                <div class="form-text">Pilih materi yang terkait dengan diskusi ini (jika ada)</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="judul" class="form-label">Judul Diskusi</label>
                                <input type="text" class="form-control" id="judul" name="judul" 
                                    value="<?= htmlspecialchars(isset($_POST['judul']) ? $_POST['judul'] : '') ?>" required minlength="5">
                            </div>
                            
                            <div class="mb-3">
                                <label for="isi" class="form-label">Isi Diskusi</label>
                                <textarea class="form-control" id="isi" name="isi" rows="5" required minlength="10"><?= htmlspecialchars(isset($_POST['isi']) ? $_POST['isi'] : '') ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Buat Diskusi
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const kursusSelect = document.getElementById('kursus_id');
            const materiContainer = document.getElementById('materi-container');
            const materiSelect = document.getElementById('materi_id');
            
            kursusSelect.addEventListener('change', function() {
                const kursusId = this.value;
                
                if (kursusId) {
                    // Ambil daftar materi dari kursus yang dipilih
                    fetch(`${baseUrl}/api/materi?kursus_id=${kursusId}`)
                        .then(response => response.json())
                        .then(data => {
                            materiSelect.innerHTML = '<option value="">Pilih Materi (Opsional)</option>';
                            
                            if (data.length > 0) {
                                data.forEach(materi => {
                                    const option = document.createElement('option');
                                    option.value = materi.id;
                                    option.textContent = materi.judul;
                                    materiSelect.appendChild(option);
                                });
                                
                                materiContainer.style.display = 'block';
                            } else {
                                materiContainer.style.display = 'none';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            materiContainer.style.display = 'none';
                        });
                } else {
                    materiContainer.style.display = 'none';
                }
            });
            
            // Trigger change event if value exists (for form resubmission)
            if (kursusSelect.value) {
                kursusSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
<?php
};

userLayout("Buat Diskusi Baru", $content(), "diskusi");
?>