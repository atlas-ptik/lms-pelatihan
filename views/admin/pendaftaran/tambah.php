<?php
// Path: views/admin/pendaftaran/tambah.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Tambah Pendaftaran", "Tambah pendaftaran kursus baru");

$db = dbConnect();
$pesan = '';
$tipe = '';

// Ambil daftar kursus
$stmt = $db->prepare("SELECT id, judul, harga FROM kursus WHERE status = 'publikasi' ORDER BY judul ASC");
$stmt->execute();
$daftarKursus = $stmt->fetchAll();

// Ambil daftar pengguna dengan role siswa
$stmt = $db->prepare("
    SELECT p.id, p.nama_lengkap, p.email, p.username, r.nama as role_name 
    FROM pengguna p
    JOIN role r ON p.role_id = r.id
    WHERE r.nama = 'siswa' AND p.status = 'aktif'
    ORDER BY p.nama_lengkap ASC
");
$stmt->execute();
$daftarSiswa = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pengguna_id = trim($_POST['pengguna_id'] ?? '');
    $kursus_id = trim($_POST['kursus_id'] ?? '');
    $status = trim($_POST['status'] ?? 'aktif');
    $tanggal_daftar = trim($_POST['tanggal_daftar'] ?? date('Y-m-d'));

    $errors = [];

    if (empty($pengguna_id)) {
        $errors[] = "Siswa harus dipilih";
    }

    if (empty($kursus_id)) {
        $errors[] = "Kursus harus dipilih";
    }

    if (empty($tanggal_daftar)) {
        $errors[] = "Tanggal pendaftaran harus diisi";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_daftar)) {
        $errors[] = "Format tanggal pendaftaran tidak valid. Gunakan format YYYY-MM-DD";
    }

    // Cek apakah pendaftaran sudah ada
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM pendaftaran WHERE pengguna_id = ? AND kursus_id = ?");
        $stmt->execute([$pengguna_id, $kursus_id]);
        $result = $stmt->fetch();

        if ($result['jumlah'] > 0) {
            $errors[] = "Siswa ini sudah terdaftar di kursus yang dipilih";
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Generate UUID
            $id = generate_uuid();

            // Insert pendaftaran
            $stmt = $db->prepare("
                INSERT INTO pendaftaran (id, pengguna_id, kursus_id, tanggal_daftar, status, progres_persen, waktu_dibuat, waktu_diperbarui) 
                VALUES (?, ?, ?, ?, ?, 0, NOW(), NOW())
            ");
            $stmt->execute([$id, $pengguna_id, $kursus_id, $tanggal_daftar . ' ' . date('H:i:s'), $status]);

            // Jika status selesai, update tanggal selesai
            if ($status === 'selesai') {
                $tanggal_selesai = trim($_POST['tanggal_selesai'] ?? date('Y-m-d'));

                $stmt = $db->prepare("UPDATE pendaftaran SET tanggal_selesai = ?, progres_persen = 100 WHERE id = ?");
                $stmt->execute([$tanggal_selesai . ' ' . date('H:i:s'), $id]);
            }

            // Buat entri progres_materi untuk semua materi dalam kursus
            $stmt = $db->prepare("
                SELECT m.id 
                FROM materi m
                JOIN modul md ON m.modul_id = md.id
                WHERE md.kursus_id = ?
            ");
            $stmt->execute([$kursus_id]);
            $materiList = $stmt->fetchAll();

            foreach ($materiList as $materi) {
                $progresId = generate_uuid();
                $stmt = $db->prepare("
                    INSERT INTO progres_materi (id, pendaftaran_id, materi_id, status, waktu_dibuat, waktu_diperbarui)
                    VALUES (?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$progresId, $id, $materi['id'], $status === 'selesai' ? 'selesai' : 'belum mulai']);
            }

            $db->commit();

            // Redirect ke halaman detail pendaftaran
            header("Location: " . BASE_URL . "/admin/pendaftaran/detail?id=" . $id . "&pesan=Pendaftaran berhasil ditambahkan&tipe=success");
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            $pesan = "Gagal menambahkan pendaftaran: " . $e->getMessage();
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
            <h1 class="page-title">Tambah Pendaftaran</h1>
            <p class="text-muted">Tambahkan pendaftaran kursus baru untuk siswa</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?= BASE_URL ?>/admin/pendaftaran" class="btn btn-outline-secondary">
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
        <div class="card-header">
            <h5 class="mb-0">Form Pendaftaran Kursus</h5>
        </div>
        <div class="card-body">
            <?php if (empty($daftarSiswa)): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Belum ada siswa yang terdaftar. Tambahkan pengguna dengan role siswa terlebih dahulu.
                </div>
                <a href="<?= BASE_URL ?>/admin/pengguna/tambah" class="btn btn-primary">
                    <i class="bi bi-person-plus"></i> Tambah Pengguna
                </a>
            <?php elseif (empty($daftarKursus)): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Belum ada kursus yang tersedia. Tambahkan dan publikasikan kursus terlebih dahulu.
                </div>
                <a href="<?= BASE_URL ?>/admin/kursus/tambah" class="btn btn-primary">
                    <i class="bi bi-book"></i> Tambah Kursus
                </a>
            <?php else: ?>
                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="pengguna_id" class="form-label">Siswa <span class="text-danger">*</span></label>
                            <select class="form-select" id="pengguna_id" name="pengguna_id" required>
                                <option value="">Pilih Siswa</option>
                                <?php foreach ($daftarSiswa as $siswa): ?>
                                    <option value="<?= $siswa['id'] ?>" <?= (isset($_POST['pengguna_id']) && $_POST['pengguna_id'] === $siswa['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($siswa['nama_lengkap']) ?> (<?= htmlspecialchars($siswa['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="kursus_id" class="form-label">Kursus <span class="text-danger">*</span></label>
                            <select class="form-select" id="kursus_id" name="kursus_id" required>
                                <option value="">Pilih Kursus</option>
                                <?php foreach ($daftarKursus as $kursus): ?>
                                    <option value="<?= $kursus['id'] ?>" <?= (isset($_POST['kursus_id']) && $_POST['kursus_id'] === $kursus['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kursus['judul']) ?> - <?= $kursus['harga'] > 0 ? 'Rp ' . number_format($kursus['harga'], 0, ',', '.') : 'Gratis' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="tanggal_daftar" class="form-label">Tanggal Pendaftaran <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal_daftar" name="tanggal_daftar" value="<?= $_POST['tanggal_daftar'] ?? date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="status" class="form-label">Status Pendaftaran</label>
                            <select class="form-select" id="status" name="status">
                                <option value="aktif" <?= (!isset($_POST['status']) || $_POST['status'] === 'aktif') ? 'selected' : '' ?>>Aktif</option>
                                <option value="selesai" <?= (isset($_POST['status']) && $_POST['status'] === 'selesai') ? 'selected' : '' ?>>Selesai</option>
                                <option value="dibatalkan" <?= (isset($_POST['status']) && $_POST['status'] === 'dibatalkan') ? 'selected' : '' ?>>Dibatalkan</option>
                            </select>
                        </div>
                    </div>

                    <div id="tanggal-selesai-container" class="row mb-3" style="display: none;">
                        <div class="col-md-6">
                            <label for="tanggal_selesai" class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" value="<?= $_POST['tanggal_selesai'] ?? date('Y-m-d') ?>">
                            <div class="form-text">Tanggal siswa menyelesaikan kursus ini.</div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Catatan:</strong> Pendaftaran ini akan membuat entri progres belajar untuk siswa pada kursus yang dipilih.
                        <?php if (isset($_POST['status']) && $_POST['status'] === 'selesai'): ?>
                            Jika status "Selesai" dipilih, semua materi akan ditandai sebagai selesai dan progres akan ditetapkan 100%.
                        <?php endif; ?>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Simpan Pendaftaran
                        </button>
                        <a href="<?= BASE_URL ?>/admin/pendaftaran" class="btn btn-outline-secondary ms-2">
                            Batal
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusSelect = document.getElementById('status');
        const tanggalSelesaiContainer = document.getElementById('tanggal-selesai-container');

        // Tampilkan/sembunyikan tanggal selesai berdasarkan status
        function toggleTanggalSelesai() {
            if (statusSelect.value === 'selesai') {
                tanggalSelesaiContainer.style.display = 'block';
                document.getElementById('tanggal_selesai').required = true;
            } else {
                tanggalSelesaiContainer.style.display = 'none';
                document.getElementById('tanggal_selesai').required = false;
            }
        }

        // Panggil fungsi pada load
        toggleTanggalSelesai();

        // Panggil fungsi saat status berubah
        statusSelect.addEventListener('change', toggleTanggalSelesai);
    });
</script>

<?php adminFooter(); ?>