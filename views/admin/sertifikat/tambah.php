<?php
// Path: views/admin/sertifikat/tambah.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

$db = dbConnect();
$error = '';
$success = '';

// Ambil daftar pendaftaran kursus yang belum memiliki sertifikat dan sudah selesai
$pendaftaran_query = "
    SELECT p.id, u.nama_lengkap as nama_pengguna, k.judul as judul_kursus,
           p.tanggal_daftar, p.progres_persen, p.status
    FROM pendaftaran p
    JOIN pengguna u ON p.pengguna_id = u.id
    JOIN kursus k ON p.kursus_id = k.id
    LEFT JOIN sertifikat s ON p.id = s.pendaftaran_id
    WHERE s.id IS NULL AND p.status = 'selesai' AND p.progres_persen >= 100
    ORDER BY p.tanggal_daftar DESC
";

$pendaftaran_stmt = $db->query($pendaftaran_query);
$pendaftaran_list = $pendaftaran_stmt->fetchAll();

// Proses form tambah sertifikat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_sertifikat'])) {
    $pendaftaran_id = $_POST['pendaftaran_id'] ?? '';
    $tanggal_kedaluwarsa = $_POST['tanggal_kedaluwarsa'] ?? '';

    // Validasi input
    if (empty($pendaftaran_id)) {
        $error = 'Pilih pendaftaran kursus terlebih dahulu';
    } else {
        // Generate nomor sertifikat
        $nomor_sertifikat = 'CERT-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));

        // Generate UUID untuk sertifikat
        $sertifikat_id = generate_uuid();

        // Simpan data sertifikat
        $query = "
            INSERT INTO sertifikat (id, pendaftaran_id, nomor_sertifikat, tanggal_terbit, tanggal_kedaluwarsa, status)
            VALUES (?, ?, ?, NOW(), ?, 'aktif')
        ";

        $stmt = $db->prepare($query);
        $stmt->execute([
            $sertifikat_id,
            $pendaftaran_id,
            $nomor_sertifikat,
            !empty($tanggal_kedaluwarsa) ? $tanggal_kedaluwarsa : null
        ]);

        // Redirect ke halaman daftar sertifikat
        header('Location: ' . BASE_URL . '/admin/sertifikat?added=1');
        exit;
    }
}

adminHeader("Tambah Sertifikat", "Tambah sertifikat baru pada sistem Atlas LMS");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tambah Sertifikat</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/sertifikat">Sertifikat</a></li>
            <li class="breadcrumb-item active">Tambah</li>
        </ol>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Form Tambah Sertifikat</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= $error ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($pendaftaran_list)): ?>
                        <div class="alert alert-info" role="alert">
                            <i class="bi bi-info-circle me-2"></i>
                            Tidak ada peserta kursus yang memenuhi syarat untuk mendapatkan sertifikat.
                            Pastikan ada peserta yang telah menyelesaikan kursus dengan progres 100%.
                        </div>

                        <div class="text-center mt-4">
                            <a href="<?= BASE_URL ?>/admin/sertifikat" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Kembali
                            </a>
                        </div>
                    <?php else: ?>
                        <form action="" method="post">
                            <div class="mb-3">
                                <label for="pendaftaran_id" class="form-label">Pilih Peserta & Kursus</label>
                                <select class="form-select" id="pendaftaran_id" name="pendaftaran_id" required>
                                    <option value="">Pilih Peserta & Kursus</option>
                                    <?php foreach ($pendaftaran_list as $pendaftaran): ?>
                                        <option value="<?= $pendaftaran['id'] ?>">
                                            <?= htmlspecialchars($pendaftaran['nama_pengguna']) ?> -
                                            <?= htmlspecialchars($pendaftaran['judul_kursus']) ?>
                                            (Progres: <?= $pendaftaran['progres_persen'] ?>%)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Hanya menampilkan peserta yang telah menyelesaikan kursus (progres 100%)</small>
                            </div>

                            <div class="mb-3">
                                <label for="tanggal_kedaluwarsa" class="form-label">Tanggal Kedaluwarsa (Opsional)</label>
                                <input type="date" class="form-control" id="tanggal_kedaluwarsa" name="tanggal_kedaluwarsa"
                                    min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                <small class="text-muted">Biarkan kosong jika sertifikat tidak memiliki tanggal kedaluwarsa</small>
                            </div>

                            <div class="mt-4">
                                <div class="d-flex justify-content-between">
                                    <a href="<?= BASE_URL ?>/admin/sertifikat" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left me-1"></i> Kembali
                                    </a>
                                    <button type="submit" name="tambah_sertifikat" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i> Simpan Sertifikat
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php adminFooter(); ?>