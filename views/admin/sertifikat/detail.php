<?php
// Path: views/admin/sertifikat/detail.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Cek id sertifikat
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . BASE_URL . '/admin/sertifikat');
    exit;
}

$sertifikat_id = $_GET['id'];
$db = dbConnect();

// Cek jika sertifikat ingin dicabut
if (isset($_GET['revoke']) && $_GET['revoke'] == 1) {
    $update_stmt = $db->prepare("UPDATE sertifikat SET status = 'dicabut' WHERE id = ?");
    $update_stmt->execute([$sertifikat_id]);

    header('Location: ' . BASE_URL . '/admin/sertifikat?deleted=1');
    exit;
}

// Ambil data sertifikat
$stmt = $db->prepare("
    SELECT s.*, p.nama_lengkap as nama_pengguna, p.email as email_pengguna, 
           k.judul as judul_kursus, k.level as level_kursus,
           pd.tanggal_daftar, pd.tanggal_selesai, pd.progres_persen
    FROM sertifikat s
    JOIN pendaftaran pd ON s.pendaftaran_id = pd.id
    JOIN pengguna p ON pd.pengguna_id = p.id
    JOIN kursus k ON pd.kursus_id = k.id
    WHERE s.id = ?
");
$stmt->execute([$sertifikat_id]);
$sertifikat = $stmt->fetch();

if (!$sertifikat) {
    header('Location: ' . BASE_URL . '/admin/sertifikat');
    exit;
}

// Update tanggal kedaluwarsa sertifikat
if (isset($_POST['update_expiry'])) {
    $tanggal_kedaluwarsa = $_POST['tanggal_kedaluwarsa'] ?? null;

    $update_stmt = $db->prepare("UPDATE sertifikat SET tanggal_kedaluwarsa = ? WHERE id = ?");
    $update_stmt->execute([
        !empty($tanggal_kedaluwarsa) ? $tanggal_kedaluwarsa : null,
        $sertifikat_id
    ]);

    header('Location: ' . BASE_URL . '/admin/sertifikat/detail?id=' . $sertifikat_id . '&updated=1');
    exit;
}

adminHeader("Detail Sertifikat", "Lihat detail sertifikat pada sistem Atlas LMS");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detail Sertifikat</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/sertifikat">Sertifikat</a></li>
            <li class="breadcrumb-item active">Detail</li>
        </ol>
    </div>

    <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Sertifikat berhasil diperbarui
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header py-3 bg-white">
                    <h5 class="card-title mb-0">Sertifikat</h5>
                </div>
                <div class="card-body">
                    <div class="sertifikat-preview border p-4 mb-4">
                        <div class="text-center mb-4">
                            <h2 class="mb-1">Sertifikat Penyelesaian</h2>
                            <p class="mb-0">Diberikan kepada:</p>
                            <h3 class="mb-3"><?= htmlspecialchars($sertifikat['nama_pengguna']) ?></h3>
                            <p class="mb-0">Atas keberhasilan menyelesaikan kursus:</p>
                            <h4 class="mb-3"><?= htmlspecialchars($sertifikat['judul_kursus']) ?></h4>
                            <p class="mb-0">Level: <?= ucfirst($sertifikat['level_kursus']) ?></p>
                            <p class="mb-4">Tanggal: <?= date('d F Y', strtotime($sertifikat['tanggal_terbit'])) ?></p>
                            <div class="sertifikat-nomor mb-2">
                                <strong>Nomor Sertifikat:</strong> <?= $sertifikat['nomor_sertifikat'] ?>
                            </div>
                            <div class="sertifikat-status">
                                <span class="badge <?= $sertifikat['status'] === 'aktif' ? 'bg-success' : 'bg-danger' ?> fs-6">
                                    <?= ucfirst($sertifikat['status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nomor Sertifikat</label>
                                <p class="mb-0"><?= $sertifikat['nomor_sertifikat'] ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Status</label>
                                <p class="mb-0">
                                    <span class="badge <?= $sertifikat['status'] === 'aktif' ? 'bg-success' : 'bg-danger' ?>">
                                        <?= ucfirst($sertifikat['status']) ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tanggal Terbit</label>
                                <p class="mb-0"><?= date('d F Y', strtotime($sertifikat['tanggal_terbit'])) ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tanggal Kedaluwarsa</label>
                                <p class="mb-0">
                                    <?= $sertifikat['tanggal_kedaluwarsa']
                                        ? date('d F Y', strtotime($sertifikat['tanggal_kedaluwarsa']))
                                        : '<span class="text-muted">Tidak ada</span>' ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header py-3 bg-white">
                    <h5 class="card-title mb-0">Informasi Peserta</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nama Peserta</label>
                                <p class="mb-0"><?= htmlspecialchars($sertifikat['nama_pengguna']) ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <p class="mb-0"><?= htmlspecialchars($sertifikat['email_pengguna']) ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tanggal Pendaftaran</label>
                                <p class="mb-0"><?= date('d F Y', strtotime($sertifikat['tanggal_daftar'])) ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tanggal Penyelesaian</label>
                                <p class="mb-0"><?= date('d F Y', strtotime($sertifikat['tanggal_selesai'])) ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Judul Kursus</label>
                                <p class="mb-0"><?= htmlspecialchars($sertifikat['judul_kursus']) ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Level Kursus</label>
                                <p class="mb-0"><?= ucfirst($sertifikat['level_kursus']) ?></p>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Progres Kursus</label>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $sertifikat['progres_persen'] ?>%;"
                                        aria-valuenow="<?= $sertifikat['progres_persen'] ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?= $sertifikat['progres_persen'] ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header py-3 bg-white">
                    <h5 class="card-title mb-0">Pengaturan Sertifikat</h5>
                </div>
                <div class="card-body">
                    <form action="" method="post">
                        <div class="mb-3">
                            <label for="tanggal_kedaluwarsa" class="form-label">Ubah Tanggal Kedaluwarsa</label>
                            <input type="date" class="form-control" id="tanggal_kedaluwarsa" name="tanggal_kedaluwarsa"
                                min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                                value="<?= $sertifikat['tanggal_kedaluwarsa'] ? date('Y-m-d', strtotime($sertifikat['tanggal_kedaluwarsa'])) : '' ?>">
                            <small class="text-muted">Biarkan kosong jika sertifikat tidak memiliki tanggal kedaluwarsa</small>
                        </div>
                        <button type="submit" name="update_expiry" class="btn btn-primary w-100 mb-3">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
                        </button>
                    </form>

                    <?php if ($sertifikat['status'] === 'aktif'): ?>
                        <a href="<?= BASE_URL ?>/admin/sertifikat/detail?id=<?= $sertifikat_id ?>&revoke=1"
                            class="btn btn-danger w-100 mb-3"
                            onclick="return confirm('Apakah Anda yakin ingin mencabut sertifikat ini? Tindakan ini tidak dapat dibatalkan.')">
                            <i class="bi bi-x-circle me-1"></i> Cabut Sertifikat
                        </a>
                    <?php endif; ?>

                    <a href="<?= BASE_URL ?>/admin/sertifikat" class="btn btn-secondary w-100">
                        <i class="bi bi-arrow-left me-1"></i> Kembali
                    </a>
                </div>
            </div>

            <div class="card">
                <div class="card-header py-3 bg-white">
                    <h5 class="card-title mb-0">Tindakan</h5>
                </div>
                <div class="card-body">
                    <a href="#" onclick="printCertificate(); return false;" class="btn btn-primary w-100 mb-3">
                        <i class="bi bi-printer me-1"></i> Cetak Sertifikat
                    </a>

                    <a href="#" onclick="downloadPDF(); return false;" class="btn btn-info w-100 mb-3">
                        <i class="bi bi-download me-1"></i> Unduh PDF
                    </a>

                    <a href="#" onclick="sendEmail(); return false;" class="btn btn-success w-100">
                        <i class="bi bi-envelope me-1"></i> Kirim via Email
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .sertifikat-preview {
        background-color: #fff;
        border-radius: 5px;
        position: relative;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .sertifikat-preview::before {
        content: "";
        position: absolute;
        top: 10px;
        left: 10px;
        right: 10px;
        bottom: 10px;
        border: 2px solid #ccc;
        pointer-events: none;
    }

    .sertifikat-nomor {
        font-family: monospace;
        letter-spacing: 1px;
    }

    @media print {

        .admin-wrapper .sidebar,
        .admin-wrapper .topbar,
        .admin-wrapper .footer,
        .breadcrumb,
        .card-header,
        .btn,
        .alert {
            display: none !important;
        }

        .admin-wrapper .main-content {
            margin-left: 0 !important;
            padding: 0 !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        .card-body {
            padding: 0 !important;
        }

        .sertifikat-preview {
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
        }

        .sertifikat-preview::before {
            border: none !important;
        }
    }
</style>

<script>
    function printCertificate() {
        window.print();
    }

    function downloadPDF() {
        alert('Fungsi unduh PDF akan diimplementasikan. Untuk saat ini, silakan gunakan fungsi cetak dan simpan sebagai PDF.');
    }

    function sendEmail() {
        alert('Sertifikat akan dikirim melalui email ke: <?= htmlspecialchars($sertifikat['email_pengguna']) ?>');
    }
</script>

<?php adminFooter(); ?>