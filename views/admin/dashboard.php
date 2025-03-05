<?php
// Path: views/admin/dashboard.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Get stats from database
$db = dbConnect();

// Total pengguna
$stmt = $db->query("SELECT COUNT(*) as total FROM pengguna");
$total_pengguna = $stmt->fetch()['total'];

// Total kursus
$stmt = $db->query("SELECT COUNT(*) as total FROM kursus");
$total_kursus = $stmt->fetch()['total'];

// Total pendaftaran
$stmt = $db->query("SELECT COUNT(*) as total FROM pendaftaran");
$total_pendaftaran = $stmt->fetch()['total'];

// Total kategori
$stmt = $db->query("SELECT COUNT(*) as total FROM kategori");
$total_kategori = $stmt->fetch()['total'];

// Pendaftaran terbaru
$stmt = $db->query("
    SELECT p.tanggal_daftar, u.nama_lengkap as nama_pengguna, k.judul as judul_kursus
    FROM pendaftaran p
    JOIN pengguna u ON p.pengguna_id = u.id
    JOIN kursus k ON p.kursus_id = k.id
    ORDER BY p.tanggal_daftar DESC
    LIMIT 5
");
$pendaftaran_terbaru = $stmt->fetchAll();

// Kursus terpopuler
$stmt = $db->query("
    SELECT k.judul, COUNT(p.id) as jumlah_pendaftar
    FROM kursus k
    LEFT JOIN pendaftaran p ON k.id = p.kursus_id
    GROUP BY k.id
    ORDER BY jumlah_pendaftar DESC
    LIMIT 5
");
$kursus_populer = $stmt->fetchAll();

// Pengguna terbaru
$stmt = $db->query("
    SELECT id, nama_lengkap, email, waktu_dibuat
    FROM pengguna
    ORDER BY waktu_dibuat DESC
    LIMIT 5
");
$pengguna_terbaru = $stmt->fetchAll();

adminHeader("Dashboard Admin", "Panel admin Atlas LMS");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </div>

    <!-- Statistik Utama -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-people text-primary fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Total Pengguna</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($total_pengguna) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="bi bi-book text-success fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Total Kursus</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($total_kursus) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="bi bi-person-check text-info fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Total Pendaftaran</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($total_pendaftaran) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-dashboard h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="bi bi-tag text-warning fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Total Kategori</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($total_kategori) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grafik dan Tabel -->
    <div class="row g-4">
        <!-- Kursus Terpopuler -->
        <div class="col-lg-6">
            <div class="card card-dashboard h-100">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold">Kursus Terpopuler</h6>
                        <a href="<?= BASE_URL ?>/admin/kursus" class="btn btn-sm btn-primary">Lihat Semua</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($kursus_populer)): ?>
                        <p class="text-center py-3">Belum ada data kursus.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kursus</th>
                                        <th>Jumlah Pendaftar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kursus_populer as $kursus): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($kursus['judul']) ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?= number_format($kursus['jumlah_pendaftar']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Pendaftaran Terbaru -->
        <div class="col-lg-6">
            <div class="card card-dashboard h-100">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold">Pendaftaran Terbaru</h6>
                        <a href="<?= BASE_URL ?>/admin/pendaftaran" class="btn btn-sm btn-primary">Lihat Semua</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($pendaftaran_terbaru)): ?>
                        <p class="text-center py-3">Belum ada pendaftaran kursus.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Pengguna</th>
                                        <th>Kursus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendaftaran_terbaru as $daftar): ?>
                                        <tr>
                                            <td><?= date('d M Y', strtotime($daftar['tanggal_daftar'])) ?></td>
                                            <td><?= htmlspecialchars($daftar['nama_pengguna']) ?></td>
                                            <td><?= htmlspecialchars($daftar['judul_kursus']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Pengguna Terbaru -->
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="m-0 fw-bold">Pengguna Terbaru</h6>
                        <a href="<?= BASE_URL ?>/admin/pengguna" class="btn btn-sm btn-primary">Kelola Pengguna</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($pengguna_terbaru)): ?>
                        <p class="text-center py-3">Belum ada data pengguna.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>Tanggal Daftar</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pengguna_terbaru as $pengguna): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($pengguna['nama_lengkap']) ?></td>
                                            <td><?= htmlspecialchars($pengguna['email']) ?></td>
                                            <td><?= date('d M Y', strtotime($pengguna['waktu_dibuat'])) ?></td>
                                            <td>
                                                <?php if (isset($pengguna['id'])): ?>
                                                    <a href="<?= BASE_URL ?>/admin/pengguna/edit?id=<?= $pengguna['id'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button disabled class="btn btn-sm btn-secondary">
                                                        <i class="bi bi-pencil-square"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php adminFooter(); ?>