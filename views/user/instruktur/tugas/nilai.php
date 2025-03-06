<?php
// Path: views/user/instruktur/tugas/nilai.php

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

// Periksa parameter id pengumpulan tugas
if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . '/user/instruktur/kursus');
    exit;
}

$pengumpulanId = $_GET['id'];

// Ambil data pengumpulan tugas
$queryPengumpulan = "SELECT pt.*, t.judul as judul_tugas, t.deskripsi as deskripsi_tugas, 
                     t.nilai_maksimal, t.file_lampiran, 
                     p.id as pendaftaran_id, k.id as kursus_id, k.judul as judul_kursus,
                     pg.id as pengguna_id, pg.nama_lengkap, pg.email, pg.foto_profil,
                     m.judul as judul_materi, md.judul as judul_modul
                     FROM pengumpulan_tugas pt
                     JOIN tugas t ON pt.tugas_id = t.id
                     JOIN pendaftaran p ON pt.pendaftaran_id = p.id
                     JOIN kursus k ON p.kursus_id = k.id
                     JOIN pengguna pg ON p.pengguna_id = pg.id
                     JOIN materi m ON t.materi_id = m.id
                     JOIN modul md ON m.modul_id = md.id
                     WHERE pt.id = :pengumpulan_id
                     AND k.pembuat_id = :user_id";

$stmtPengumpulan = $db->prepare($queryPengumpulan);
$stmtPengumpulan->bindParam(':pengumpulan_id', $pengumpulanId);
$stmtPengumpulan->bindParam(':user_id', $userId);
$stmtPengumpulan->execute();
$pengumpulan = $stmtPengumpulan->fetch();

if (!$pengumpulan) {
    header('Location: ' . BASE_URL . '/user/instruktur/kursus');
    exit;
}

// Proses form penilaian
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nilai = isset($_POST['nilai']) ? $_POST['nilai'] : null;
    $komentar = isset($_POST['komentar']) ? trim($_POST['komentar']) : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'dinilai';
    
    $errors = [];
    
    if ($nilai === null || !is_numeric($nilai) || $nilai < 0 || $nilai > $pengumpulan['nilai_maksimal']) {
        $errors[] = "Nilai tidak valid. Nilai harus antara 0 dan " . $pengumpulan['nilai_maksimal'];
    }
    
    if (empty($komentar)) {
        $errors[] = "Komentar tidak boleh kosong";
    }
    
    if (!in_array($status, ['dinilai', 'revisi'])) {
        $errors[] = "Status tidak valid";
    }
    
    if (empty($errors)) {
        try {
            $queryUpdateNilai = "UPDATE pengumpulan_tugas SET 
                               nilai = :nilai, 
                               komentar_pengajar = :komentar, 
                               status = :status,
                               waktu_penilaian = NOW()
                               WHERE id = :id";
            
            $stmtUpdateNilai = $db->prepare($queryUpdateNilai);
            $stmtUpdateNilai->bindParam(':nilai', $nilai);
            $stmtUpdateNilai->bindParam(':komentar', $komentar);
            $stmtUpdateNilai->bindParam(':status', $status);
            $stmtUpdateNilai->bindParam(':id', $pengumpulanId);
            
            if ($stmtUpdateNilai->execute()) {
                $_SESSION['alert'] = [
                    'type' => 'success',
                    'message' => 'Penilaian berhasil disimpan'
                ];
                
                // Buat notifikasi untuk peserta
                $notifikasiId = generate_uuid();
                $pesanNotifikasi = "Tugas '{$pengumpulan['judul_tugas']}' pada kursus '{$pengumpulan['judul_kursus']}' telah dinilai.";
                $judulNotifikasi = "Penilaian Tugas";
                $tipe = "info";
                $tautan = BASE_URL . "/user/tugas/detail?id=" . $pengumpulan['tugas_id'];
                
                $queryNotifikasi = "INSERT INTO notifikasi (id, pengguna_id, judul, pesan, tipe, tautan) 
                                   VALUES (:id, :pengguna_id, :judul, :pesan, :tipe, :tautan)";
                
                $stmtNotifikasi = $db->prepare($queryNotifikasi);
                $stmtNotifikasi->bindParam(':id', $notifikasiId);
                $stmtNotifikasi->bindParam(':pengguna_id', $pengumpulan['pengguna_id']);
                $stmtNotifikasi->bindParam(':judul', $judulNotifikasi);
                $stmtNotifikasi->bindParam(':pesan', $pesanNotifikasi);
                $stmtNotifikasi->bindParam(':tipe', $tipe);
                $stmtNotifikasi->bindParam(':tautan', $tautan);
                $stmtNotifikasi->execute();
                
                // Refresh data pengumpulan
                $stmtPengumpulan->execute();
                $pengumpulan = $stmtPengumpulan->fetch();
            } else {
                $errors[] = "Gagal menyimpan penilaian";
            }
        } catch (Exception $e) {
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

if (!isset($errors)) {
    $errors = [];
}

$content = function() use ($pengumpulan, $errors) {
    $baseUrl = BASE_URL;
?>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Nilai Tugas</h2>
                <a href="<?= $baseUrl ?>/user/instruktur/tugas?kursus_id=<?= $pengumpulan['kursus_id'] ?>" class="btn btn-outline-secondary">
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

            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Informasi Tugas</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Detail Kursus</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 150px;">Kursus</th>
                                    <td><?= htmlspecialchars($pengumpulan['judul_kursus']) ?></td>
                                </tr>
                                <tr>
                                    <th>Modul</th>
                                    <td><?= htmlspecialchars($pengumpulan['judul_modul']) ?></td>
                                </tr>
                                <tr>
                                    <th>Materi</th>
                                    <td><?= htmlspecialchars($pengumpulan['judul_materi']) ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Detail Peserta</h6>
                            <table class="table table-borderless">
                                <tr>
                                    <th style="width: 150px;">Nama</th>
                                    <td><?= htmlspecialchars($pengumpulan['nama_lengkap']) ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?= htmlspecialchars($pengumpulan['email']) ?></td>
                                </tr>
                                <tr>
                                    <th>Waktu Pengumpulan</th>
                                    <td><?= date('d M Y, H:i', strtotime($pengumpulan['waktu_pengumpulan'])) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Deskripsi Tugas</h5>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($pengumpulan['judul_tugas']) ?></h5>
                            <div class="mb-3">
                                <?= nl2br(htmlspecialchars($pengumpulan['deskripsi_tugas'])) ?>
                            </div>
                            <?php if (!empty($pengumpulan['file_lampiran'])): ?>
                                <div class="mb-3">
                                    <strong>File Lampiran:</strong>
                                    <a href="<?= $baseUrl ?>/uploads/tugas/<?= $pengumpulan['file_lampiran'] ?>" 
                                       class="btn btn-sm btn-outline-primary" download>
                                        <i class="bi bi-download"></i> Download Lampiran
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div>
                                <span class="badge bg-info">Nilai Maksimal: <?= $pengumpulan['nilai_maksimal'] ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Jawaban Peserta</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($pengumpulan['teks_jawaban'])): ?>
                                <div class="mb-3">
                                    <h6>Jawaban Teks:</h6>
                                    <div class="p-3 bg-light rounded">
                                        <?= nl2br(htmlspecialchars($pengumpulan['teks_jawaban'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($pengumpulan['file_jawaban'])): ?>
                                <div class="mb-3">
                                    <h6>File Jawaban:</h6>
                                    <a href="<?= $baseUrl ?>/uploads/tugas_jawaban/<?= $pengumpulan['file_jawaban'] ?>" 
                                       class="btn btn-primary" download>
                                        <i class="bi bi-download"></i> Download File Jawaban
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (empty($pengumpulan['teks_jawaban']) && empty($pengumpulan['file_jawaban'])): ?>
                                <div class="alert alert-warning">
                                    Peserta belum memberikan jawaban.
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <span class="badge <?= 
                                    $pengumpulan['status'] === 'menunggu penilaian' ? 'bg-warning' : 
                                    ($pengumpulan['status'] === 'dinilai' ? 'bg-success' : 
                                    ($pengumpulan['status'] === 'revisi' ? 'bg-info' : 'bg-danger')) 
                                ?>">
                                    Status: <?= ucfirst(str_replace('_', ' ', $pengumpulan['status'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Form Penilaian</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= $baseUrl ?>/user/instruktur/tugas/nilai?id=<?= $pengumpulan['id'] ?>">
                        <div class="mb-3">
                            <label for="nilai" class="form-label">Nilai (Maksimal: <?= $pengumpulan['nilai_maksimal'] ?>)</label>
                            <input type="number" class="form-control" id="nilai" name="nilai" 
                                min="0" max="<?= $pengumpulan['nilai_maksimal'] ?>" 
                                value="<?= isset($pengumpulan['nilai']) ? $pengumpulan['nilai'] : '' ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="dinilai" <?= $pengumpulan['status'] === 'dinilai' ? 'selected' : '' ?>>Dinilai</option>
                                <option value="revisi" <?= $pengumpulan['status'] === 'revisi' ? 'selected' : '' ?>>Perlu Revisi</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="komentar" class="form-label">Komentar/Feedback</label>
                            <textarea class="form-control" id="komentar" name="komentar" rows="5" required><?= htmlspecialchars(isset($pengumpulan['komentar_pengajar']) ? $pengumpulan['komentar_pengajar'] : '') ?></textarea>
                            <div class="form-text">Berikan feedback yang konstruktif untuk membantu peserta meningkatkan pemahaman.</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Simpan Penilaian
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <?php if (isset($pengumpulan['waktu_penilaian']) && $pengumpulan['waktu_penilaian']): ?>
                        <div class="alert alert-info">
                            <small>
                                <i class="bi bi-info-circle"></i> Terakhir dinilai pada: <?= date('d M Y, H:i', strtotime($pengumpulan['waktu_penilaian'])) ?>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php
};

userLayout("Nilai Tugas - " . $pengumpulan['judul_tugas'], $content(), "instruktur");
?>