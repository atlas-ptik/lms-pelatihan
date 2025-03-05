<?php
// Path: views/admin/pendaftaran/index.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Manajemen Pendaftaran", "Kelola pendaftaran kursus");

$db = dbConnect();
$pesan = '';
$tipe = '';

// Parameter pencarian dan filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$kursus_id = isset($_GET['kursus_id']) ? $_GET['kursus_id'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'terbaru';

// Pesan notifikasi jika ada
if (isset($_GET['pesan']) && isset($_GET['tipe'])) {
    $pesan = $_GET['pesan'];
    $tipe = $_GET['tipe'];
}

// Proses hapus pendaftaran jika ada
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Cek apakah pendaftaran ada
        $stmt = $db->prepare("SELECT * FROM pendaftaran WHERE id = ?");
        $stmt->execute([$id]);
        $pendaftaran = $stmt->fetch();

        if ($pendaftaran) {
            // Hapus progres materi terkait
            $stmt = $db->prepare("DELETE FROM progres_materi WHERE pendaftaran_id = ?");
            $stmt->execute([$id]);

            // Hapus percobaan quiz terkait
            $stmt = $db->prepare("DELETE FROM percobaan_quiz WHERE pendaftaran_id = ?");
            $stmt->execute([$id]);

            // Hapus pengumpulan tugas terkait
            $stmt = $db->prepare("DELETE FROM pengumpulan_tugas WHERE pendaftaran_id = ?");
            $stmt->execute([$id]);

            // Hapus sertifikat terkait
            $stmt = $db->prepare("DELETE FROM sertifikat WHERE pendaftaran_id = ?");
            $stmt->execute([$id]);

            // Hapus pendaftaran
            $stmt = $db->prepare("DELETE FROM pendaftaran WHERE id = ?");
            $stmt->execute([$id]);

            $pesan = "Pendaftaran berhasil dihapus beserta data terkait";
            $tipe = "success";
        } else {
            $pesan = "Pendaftaran tidak ditemukan";
            $tipe = "danger";
        }
    } catch (PDOException $e) {
        $pesan = "Gagal menghapus pendaftaran: " . $e->getMessage();
        $tipe = "danger";
    }
}

// Query untuk mendapatkan daftar pendaftaran
$query = "SELECT p.*, pg.nama_lengkap as nama_pengguna, pg.email as email_pengguna, 
          k.judul as judul_kursus, k.harga as harga_kursus,
          (SELECT COUNT(*) FROM progres_materi WHERE pendaftaran_id = p.id AND status = 'selesai') as materi_selesai,
          (SELECT COUNT(*) FROM materi WHERE modul_id IN (SELECT id FROM modul WHERE kursus_id = p.kursus_id)) as total_materi
          FROM pendaftaran p
          JOIN pengguna pg ON p.pengguna_id = pg.id
          JOIN kursus k ON p.kursus_id = k.id
          WHERE 1=1";
$params = [];

// Tambahkan filter ke query
if (!empty($search)) {
    $query .= " AND (pg.nama_lengkap LIKE ? OR pg.email LIKE ? OR k.judul LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($status)) {
    $query .= " AND p.status = ?";
    $params[] = $status;
}

if (!empty($kursus_id)) {
    $query .= " AND p.kursus_id = ?";
    $params[] = $kursus_id;
}

// Tambahkan pengurutan
switch ($sort) {
    case 'terbaru':
        $query .= " ORDER BY p.tanggal_daftar DESC";
        break;
    case 'terlama':
        $query .= " ORDER BY p.tanggal_daftar ASC";
        break;
    case 'progres_tinggi':
        $query .= " ORDER BY p.progres_persen DESC";
        break;
    case 'progres_rendah':
        $query .= " ORDER BY p.progres_persen ASC";
        break;
    default:
        $query .= " ORDER BY p.tanggal_daftar DESC";
}

// Ambil data kursus untuk filter
$stmt = $db->prepare("SELECT id, judul FROM kursus ORDER BY judul ASC");
$stmt->execute();
$daftarKursus = $stmt->fetchAll();

// Eksekusi query pendaftaran
$stmt = $db->prepare($query);
$stmt->execute($params);
$pendaftaranList = $stmt->fetchAll();

// Ambil statistik pendaftaran
$stmt = $db->prepare("SELECT status, COUNT(*) as jumlah FROM pendaftaran GROUP BY status");
$stmt->execute();
$statusStats = $stmt->fetchAll();

$totalAktif = 0;
$totalSelesai = 0;
$totalDibatalkan = 0;

foreach ($statusStats as $stat) {
    if ($stat['status'] === 'aktif') {
        $totalAktif = $stat['jumlah'];
    } elseif ($stat['status'] === 'selesai') {
        $totalSelesai = $stat['jumlah'];
    } elseif ($stat['status'] === 'dibatalkan') {
        $totalDibatalkan = $stat['jumlah'];
    }
}

$totalPendaftaran = $totalAktif + $totalSelesai + $totalDibatalkan;

// Hitung rata-rata progres
$stmt = $db->prepare("SELECT AVG(progres_persen) as rata_progres FROM pendaftaran WHERE status = 'aktif'");
$stmt->execute();
$rataProgres = $stmt->fetch()['rata_progres'] ?? 0;
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="page-title">Manajemen Pendaftaran</h1>
            <p class="text-muted">Kelola pendaftaran kursus siswa</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?= BASE_URL ?>/admin/pendaftaran/tambah" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah Pendaftaran
            </a>
        </div>
    </div>

    <?php if (!empty($pesan)): ?>
        <div class="alert alert-<?= $tipe ?> alert-dismissible fade show" role="alert">
            <?= $pesan ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                            <i class="bi bi-people-fill text-primary fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0">Total Pendaftaran</p>
                            <h3 class="mb-0"><?= number_format($totalPendaftaran) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 p-3 rounded me-3">
                            <i class="bi bi-play-circle-fill text-success fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0">Aktif</p>
                            <h3 class="mb-0"><?= number_format($totalAktif) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 p-3 rounded me-3">
                            <i class="bi bi-check-circle-fill text-info fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0">Selesai</p>
                            <h3 class="mb-0"><?= number_format($totalSelesai) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 p-3 rounded me-3">
                            <i class="bi bi-x-circle-fill text-danger fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0">Dibatalkan</p>
                            <h3 class="mb-0"><?= number_format($totalDibatalkan) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filter Pendaftaran</h5>
        </div>
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">Pencarian</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Nama siswa, email, judul kursus...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua Status</option>
                        <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                        <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        <option value="dibatalkan" <?= $status === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="kursus_id" class="form-label">Kursus</label>
                    <select class="form-select" id="kursus_id" name="kursus_id">
                        <option value="">Semua Kursus</option>
                        <?php foreach ($daftarKursus as $kursus): ?>
                            <option value="<?= $kursus['id'] ?>" <?= $kursus_id === $kursus['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kursus['judul']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="sort" class="form-label">Urutkan</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="terlama" <?= $sort === 'terlama' ? 'selected' : '' ?>>Terlama</option>
                        <option value="progres_tinggi" <?= $sort === 'progres_tinggi' ? 'selected' : '' ?>>Progres Tertinggi</option>
                        <option value="progres_rendah" <?= $sort === 'progres_rendah' ? 'selected' : '' ?>>Progres Terendah</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                    <a href="<?= BASE_URL ?>/admin/pendaftaran" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Data Pendaftaran</h5>
            <span class="badge bg-primary rounded-pill"><?= count($pendaftaranList) ?> pendaftaran</span>
        </div>
        <div class="card-body">
            <?php if (empty($pendaftaranList)): ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i> Belum ada data pendaftaran yang sesuai dengan filter.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal Daftar</th>
                                <th>Siswa</th>
                                <th>Kursus</th>
                                <th>Status</th>
                                <th>Progres</th>
                                <th>Tanggal Selesai</th>
                                <th width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendaftaranList as $pendaftaran): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($pendaftaran['tanggal_daftar'])) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-placeholder rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px; font-size: 14px;">
                                                <?= strtoupper(substr($pendaftaran['nama_pengguna'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($pendaftaran['nama_pengguna']) ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($pendaftaran['email_pengguna']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($pendaftaran['judul_kursus']) ?></td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch ($pendaftaran['status']) {
                                            case 'aktif':
                                                $statusClass = 'bg-success';
                                                break;
                                            case 'selesai':
                                                $statusClass = 'bg-primary';
                                                break;
                                            case 'dibatalkan':
                                                $statusClass = 'bg-danger';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?= $statusClass ?>">
                                            <?= ucfirst($pendaftaran['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                <div class="progress-bar" role="progressbar" style="width: <?= $pendaftaran['progres_persen'] ?>%;" aria-valuenow="<?= $pendaftaran['progres_persen'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="small text-nowrap"><?= number_format($pendaftaran['progres_persen'], 1) ?>%</span>
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <?= $pendaftaran['materi_selesai'] ?>/<?= $pendaftaran['total_materi'] ?> materi selesai
                                        </div>
                                    </td>
                                    <td>
                                        <?= !empty($pendaftaran['tanggal_selesai']) ? date('d/m/Y', strtotime($pendaftaran['tanggal_selesai'])) : '<span class="text-muted">-</span>' ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?= BASE_URL ?>/admin/pendaftaran/detail?id=<?= $pendaftaran['id'] ?>" class="btn btn-sm btn-outline-primary" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $pendaftaran['id'] ?>" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>

                                        <!-- Modal Konfirmasi Hapus -->
                                        <div class="modal fade" id="deleteModal<?= $pendaftaran['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $pendaftaran['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel<?= $pendaftaran['id'] ?>">Konfirmasi Hapus</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Apakah Anda yakin ingin menghapus pendaftaran kursus <strong><?= htmlspecialchars($pendaftaran['judul_kursus']) ?></strong> oleh <strong><?= htmlspecialchars($pendaftaran['nama_pengguna']) ?></strong>?</p>
                                                        <div class="alert alert-warning">
                                                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                            Tindakan ini akan menghapus semua data terkait pendaftaran, termasuk progres belajar, hasil quiz, pengumpulan tugas, dan sertifikat (jika ada).
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <a href="<?= BASE_URL ?>/admin/pendaftaran?action=delete&id=<?= $pendaftaran['id'] ?>" class="btn btn-danger">Hapus</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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

<style>
    .avatar-placeholder {
        font-weight: 600;
    }
</style>

<?php adminFooter(); ?>