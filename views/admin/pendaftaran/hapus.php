<?php
// Path: views/admin/pendaftaran/hapus.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Hapus Pendaftaran", "Hapus pendaftaran kursus");

$db = dbConnect();
$pesan = '';
$tipe = '';

// Cek parameter id
if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "/admin/pendaftaran");
    exit;
}

$id = $_GET['id'];

// Ambil data pendaftaran
$stmt = $db->prepare("
    SELECT p.*, 
           pg.nama_lengkap as nama_pengguna, pg.email as email_pengguna,
           k.judul as judul_kursus
    FROM pendaftaran p
    JOIN pengguna pg ON p.pengguna_id = pg.id
    JOIN kursus k ON p.kursus_id = k.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$pendaftaran = $stmt->fetch();

if (!$pendaftaran) {
    header("Location: " . BASE_URL . "/admin/pendaftaran");
    exit;
}

// Hitung jumlah data terkait
$stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM progres_materi WHERE pendaftaran_id = ?");
$stmt->execute([$id]);
$jumlahProgresMateri = $stmt->fetch()['jumlah'];

$stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM percobaan_quiz WHERE pendaftaran_id = ?");
$stmt->execute([$id]);
$jumlahPercobaanQuiz = $stmt->fetch()['jumlah'];

$stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM pengumpulan_tugas WHERE pendaftaran_id = ?");
$stmt->execute([$id]);
$jumlahPengumpulanTugas = $stmt->fetch()['jumlah'];

$stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM sertifikat WHERE pendaftaran_id = ?");
$stmt->execute([$id]);
$jumlahSertifikat = $stmt->fetch()['jumlah'];

// Proses hapus
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi_hapus'])) {
    try {
        $db->beginTransaction();

        // Hapus progres materi
        $stmt = $db->prepare("DELETE FROM progres_materi WHERE pendaftaran_id = ?");
        $stmt->execute([$id]);

        // Hapus percobaan quiz dan jawaban
        $stmt = $db->prepare("
            DELETE FROM jawaban_percobaan 
            WHERE percobaan_quiz_id IN (SELECT id FROM percobaan_quiz WHERE pendaftaran_id = ?)
        ");
        $stmt->execute([$id]);

        $stmt = $db->prepare("DELETE FROM percobaan_quiz WHERE pendaftaran_id = ?");
        $stmt->execute([$id]);

        // Hapus pengumpulan tugas
        $stmt = $db->prepare("DELETE FROM pengumpulan_tugas WHERE pendaftaran_id = ?");
        $stmt->execute([$id]);

        // Hapus sertifikat
        $stmt = $db->prepare("DELETE FROM sertifikat WHERE pendaftaran_id = ?");
        $stmt->execute([$id]);

        // Hapus pendaftaran
        $stmt = $db->prepare("DELETE FROM pendaftaran WHERE id = ?");
        $stmt->execute([$id]);

        $db->commit();

        // Redirect ke halaman pendaftaran dengan pesan sukses
        header("Location: " . BASE_URL . "/admin/pendaftaran?pesan=Pendaftaran berhasil dihapus&tipe=success");
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        $pesan = "Gagal menghapus pendaftaran: " . $e->getMessage();
        $tipe = "danger";
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="page-title">Hapus Pendaftaran</h1>
            <p class="text-muted">Konfirmasi penghapusan pendaftaran kursus</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?= BASE_URL ?>/admin/pendaftaran" class="btn btn-secondary">
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
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Konfirmasi Penghapusan</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                            </div>
                            <div>
                                <h5>Perhatian!</h5>
                                <p>Anda akan menghapus pendaftaran <strong><?= htmlspecialchars($pendaftaran['nama_pengguna']) ?></strong> pada kursus <strong><?= htmlspecialchars($pendaftaran['judul_kursus']) ?></strong>.</p>
                                <p>Semua data terkait pendaftaran ini akan dihapus, termasuk:</p>
                                <ul>
                                    <li><?= $jumlahProgresMateri ?> data progres belajar</li>
                                    <li><?= $jumlahPercobaanQuiz ?> percobaan quiz</li>
                                    <li><?= $jumlahPengumpulanTugas ?> pengumpulan tugas</li>
                                    <?php if ($jumlahSertifikat > 0): ?>
                                        <li><?= $jumlahSertifikat ?> sertifikat</li>
                                    <?php endif; ?>
                                </ul>
                                <p class="mb-0 fw-bold">Tindakan ini tidak dapat dibatalkan!</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <form action="" method="POST">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="konfirmasi_hapus" name="konfirmasi_hapus" value="1" required>
                                <label class="form-check-label" for="konfirmasi_hapus">
                                    Saya mengerti dan ingin menghapus pendaftaran ini beserta seluruh data terkait
                                </label>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="<?= BASE_URL ?>/admin/pendaftaran" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-danger" id="btn-hapus" disabled>
                                    <i class="bi bi-trash"></i> Hapus Pendaftaran
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Pendaftaran</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Siswa:</span>
                            <span><?= htmlspecialchars($pendaftaran['nama_pengguna']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Email:</span>
                            <span><?= htmlspecialchars($pendaftaran['email_pengguna']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Kursus:</span>
                            <span><?= htmlspecialchars($pendaftaran['judul_kursus']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Tanggal Daftar:</span>
                            <span><?= date('d/m/Y', strtotime($pendaftaran['tanggal_daftar'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Status:</span>
                            <span class="badge <?php
                                                switch ($pendaftaran['status']) {
                                                    case 'aktif':
                                                        echo 'bg-success';
                                                        break;
                                                    case 'selesai':
                                                        echo 'bg-primary';
                                                        break;
                                                    case 'dibatalkan':
                                                        echo 'bg-danger';
                                                        break;
                                                }
                                                ?>"><?= ucfirst($pendaftaran['status']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Progres:</span>
                            <span><?= number_format($pendaftaran['progres_persen'], 1) ?>%</span>
                        </li>
                        <?php if ($pendaftaran['status'] === 'selesai'): ?>
                            <li class="list-group-item d-flex justify-content-between">
                                <span class="fw-semibold">Tanggal Selesai:</span>
                                <span><?= date('d/m/Y', strtotime($pendaftaran['tanggal_selesai'])) ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkKonfirmasi = document.getElementById('konfirmasi_hapus');
        const btnHapus = document.getElementById('btn-hapus');

        checkKonfirmasi.addEventListener('change', function() {
            btnHapus.disabled = !this.checked;
        });
    });
</script>

<?php adminFooter(); ?>