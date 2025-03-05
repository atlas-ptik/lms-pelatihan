<?php
// Path: views/admin/laporan/kursus.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Ambil data dari database
$db = dbConnect();

// Filter kategori
$kategori_id = isset($_GET['kategori_id']) ? $_GET['kategori_id'] : '';

// Filter level
$level = isset($_GET['level']) ? $_GET['level'] : '';

// Filter status
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Filter tanggal
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-d', strtotime('-6 months'));
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');

// Query untuk statistik kursus
$sql_statistik = "
    SELECT 
        COUNT(*) as total_kursus,
        SUM(CASE WHEN status = 'publikasi' THEN 1 ELSE 0 END) as kursus_publikasi,
        SUM(CASE WHEN status = 'draf' THEN 1 ELSE 0 END) as kursus_draf,
        SUM(CASE WHEN status = 'arsip' THEN 1 ELSE 0 END) as kursus_arsip,
        SUM(CASE WHEN waktu_dibuat BETWEEN :tanggal_mulai AND :tanggal_akhir THEN 1 ELSE 0 END) as kursus_baru,
        COUNT(DISTINCT pembuat_id) as jumlah_instruktur
    FROM 
        kursus
";

$params_statistik = [
    ':tanggal_mulai' => $tanggal_mulai . ' 00:00:00',
    ':tanggal_akhir' => $tanggal_akhir . ' 23:59:59'
];

$where_clauses = [];

if (!empty($kategori_id)) {
    $where_clauses[] = "id IN (SELECT kursus_id FROM kursus_kategori WHERE kategori_id = :kategori_id)";
    $params_statistik[':kategori_id'] = $kategori_id;
}

if (!empty($level)) {
    $where_clauses[] = "level = :level";
    $params_statistik[':level'] = $level;
}

if (!empty($status)) {
    $where_clauses[] = "status = :status";
    $params_statistik[':status'] = $status;
}

if (!empty($where_clauses)) {
    $sql_statistik .= " WHERE " . implode(' AND ', $where_clauses);
}

try {
    $stmt = $db->prepare($sql_statistik);
    foreach ($params_statistik as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $statistik = $stmt->fetch();
} catch (PDOException $e) {
    $statistik = [
        'total_kursus' => 0,
        'kursus_publikasi' => 0,
        'kursus_draf' => 0,
        'kursus_arsip' => 0,
        'kursus_baru' => 0,
        'jumlah_instruktur' => 0
    ];
}

// Query untuk mendapatkan statistik pendaftaran
$sql_pendaftaran = "
    SELECT 
        COUNT(*) as total_pendaftaran,
        SUM(CASE WHEN p.status = 'aktif' THEN 1 ELSE 0 END) as pendaftaran_aktif,
        SUM(CASE WHEN p.status = 'selesai' THEN 1 ELSE 0 END) as pendaftaran_selesai,
        AVG(p.progres_persen) as rata_rata_progres,
        SUM(CASE WHEN p.tanggal_daftar BETWEEN :tanggal_mulai AND :tanggal_akhir THEN 1 ELSE 0 END) as pendaftaran_baru
    FROM 
        pendaftaran p
    JOIN 
        kursus k ON p.kursus_id = k.id
";

$params_pendaftaran = [
    ':tanggal_mulai' => $tanggal_mulai . ' 00:00:00',
    ':tanggal_akhir' => $tanggal_akhir . ' 23:59:59'
];

$where_clauses = [];

if (!empty($kategori_id)) {
    $where_clauses[] = "k.id IN (SELECT kursus_id FROM kursus_kategori WHERE kategori_id = :kategori_id)";
    $params_pendaftaran[':kategori_id'] = $kategori_id;
}

if (!empty($level)) {
    $where_clauses[] = "k.level = :level";
    $params_pendaftaran[':level'] = $level;
}

if (!empty($status)) {
    $where_clauses[] = "k.status = :status";
    $params_pendaftaran[':status'] = $status;
}

if (!empty($where_clauses)) {
    $sql_pendaftaran .= " WHERE " . implode(' AND ', $where_clauses);
}

try {
    $stmt = $db->prepare($sql_pendaftaran);
    foreach ($params_pendaftaran as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $statistik_pendaftaran = $stmt->fetch();
} catch (PDOException $e) {
    $statistik_pendaftaran = [
        'total_pendaftaran' => 0,
        'pendaftaran_aktif' => 0,
        'pendaftaran_selesai' => 0,
        'rata_rata_progres' => 0,
        'pendaftaran_baru' => 0
    ];
}

// Query untuk laporan kursus dengan data pendaftaran
$sql_laporan = "
    SELECT 
        k.id,
        k.judul,
        k.level,
        k.harga,
        k.status,
        k.waktu_dibuat,
        u.nama_lengkap as pembuat,
        COUNT(p.id) as jumlah_pendaftaran,
        COUNT(CASE WHEN p.status = 'aktif' THEN 1 END) as pendaftaran_aktif,
        COUNT(CASE WHEN p.status = 'selesai' THEN 1 END) as pendaftaran_selesai,
        COUNT(CASE WHEN p.tanggal_daftar BETWEEN :tanggal_mulai AND :tanggal_akhir THEN 1 END) as pendaftaran_baru,
        AVG(p.progres_persen) as rata_rata_progres
    FROM 
        kursus k
    LEFT JOIN 
        pengguna u ON k.pembuat_id = u.id
    LEFT JOIN 
        pendaftaran p ON k.id = p.kursus_id
";

$params_laporan = [
    ':tanggal_mulai' => $tanggal_mulai . ' 00:00:00',
    ':tanggal_akhir' => $tanggal_akhir . ' 23:59:59'
];

$where_clauses = [];

if (!empty($kategori_id)) {
    $where_clauses[] = "k.id IN (SELECT kursus_id FROM kursus_kategori WHERE kategori_id = :kategori_id)";
    $params_laporan[':kategori_id'] = $kategori_id;
}

if (!empty($level)) {
    $where_clauses[] = "k.level = :level";
    $params_laporan[':level'] = $level;
}

if (!empty($status)) {
    $where_clauses[] = "k.status = :status";
    $params_laporan[':status'] = $status;
}

if (!empty($where_clauses)) {
    $sql_laporan .= " WHERE " . implode(' AND ', $where_clauses);
}

$sql_laporan .= " GROUP BY k.id, k.judul, k.level, k.harga, k.status, k.waktu_dibuat, u.nama_lengkap";
$sql_laporan .= " ORDER BY jumlah_pendaftaran DESC, k.waktu_dibuat DESC";

try {
    $stmt = $db->prepare($sql_laporan);
    foreach ($params_laporan as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $laporan_kursus = $stmt->fetchAll();
} catch (PDOException $e) {
    $laporan_kursus = [];
}

// Query untuk mendapatkan semua kategori
$sql_kategori = "SELECT id, nama FROM kategori ORDER BY nama";
try {
    $stmt = $db->query($sql_kategori);
    $kategori_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $kategori_list = [];
}

// Statistik kursus per kategori
$sql_per_kategori = "
    SELECT 
        k.nama as kategori_nama,
        COUNT(DISTINCT kk.kursus_id) as jumlah_kursus,
        COUNT(DISTINCT p.id) as jumlah_pendaftaran
    FROM 
        kategori k
    LEFT JOIN 
        kursus_kategori kk ON k.id = kk.kategori_id
    LEFT JOIN 
        kursus ku ON kk.kursus_id = ku.id
    LEFT JOIN 
        pendaftaran p ON ku.id = p.kursus_id
    GROUP BY 
        k.id, k.nama
    ORDER BY 
        jumlah_kursus DESC, jumlah_pendaftaran DESC
";

try {
    $stmt = $db->query($sql_per_kategori);
    $kursus_per_kategori = $stmt->fetchAll();
} catch (PDOException $e) {
    $kursus_per_kategori = [];
}

// Statistik tren pembuatan kursus (bulanan)
$sql_tren = "
    SELECT 
        DATE_FORMAT(waktu_dibuat, '%Y-%m') as bulan,
        COUNT(*) as jumlah
    FROM 
        kursus
    WHERE 
        waktu_dibuat >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY 
        DATE_FORMAT(waktu_dibuat, '%Y-%m')
    ORDER BY 
        bulan ASC
";

try {
    $stmt = $db->query($sql_tren);
    $tren_data = $stmt->fetchAll();
} catch (PDOException $e) {
    $tren_data = [];
}

// Format data tren untuk grafik JavaScript
$tren_bulan = [];
$tren_jumlah = [];
foreach ($tren_data as $tren) {
    $bulan_tahun = explode('-', $tren['bulan']);
    $bulan_nama = date('M Y', mktime(0, 0, 0, $bulan_tahun[1], 1, $bulan_tahun[0]));
    $tren_bulan[] = $bulan_nama;
    $tren_jumlah[] = $tren['jumlah'];
}

adminHeader("Laporan Kursus", "Laporan statistik dan analisis kursus");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Laporan Kursus</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/laporan/pengguna">Laporan</a></li>
            <li class="breadcrumb-item active">Kursus</li>
        </ol>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold">Filter Laporan</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-3">
                            <label for="kategori_id" class="form-label">Kategori</label>
                            <select class="form-select" id="kategori_id" name="kategori_id">
                                <option value="">Semua Kategori</option>
                                <?php foreach ($kategori_list as $kat): ?>
                                    <option value="<?= $kat['id'] ?>" <?= $kategori_id == $kat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($kat['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="level" class="form-label">Level</label>
                            <select class="form-select" id="level" name="level">
                                <option value="">Semua Level</option>
                                <option value="pemula" <?= $level == 'pemula' ? 'selected' : '' ?>>Pemula</option>
                                <option value="menengah" <?= $level == 'menengah' ? 'selected' : '' ?>>Menengah</option>
                                <option value="mahir" <?= $level == 'mahir' ? 'selected' : '' ?>>Mahir</option>
                                <option value="semua level" <?= $level == 'semua level' ? 'selected' : '' ?>>Semua Level</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Semua Status</option>
                                <option value="publikasi" <?= $status == 'publikasi' ? 'selected' : '' ?>>Publikasi</option>
                                <option value="draf" <?= $status == 'draf' ? 'selected' : '' ?>>Draf</option>
                                <option value="arsip" <?= $status == 'arsip' ? 'selected' : '' ?>>Arsip</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="<?= $tanggal_mulai ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" value="<?= $tanggal_akhir ?>">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-book text-primary fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Total Kursus</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik['total_kursus']) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="bi bi-check-circle text-success fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Kursus Publikasi</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik['kursus_publikasi']) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="bi bi-people text-info fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Total Pendaftaran</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik_pendaftaran['total_pendaftaran']) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="bi bi-lightning text-warning fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Rata-rata Progres</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik_pendaftaran['rata_rata_progres'], 1) ?>%</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold">Tren Pembuatan Kursus (12 Bulan Terakhir)</h6>
                </div>
                <div class="card-body">
                    <canvas id="trenChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold">Distribusi Kursus per Kategori</h6>
                </div>
                <div class="card-body">
                    <canvas id="pieChart" height="240"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold">Kursus per Kategori</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Kategori</th>
                                    <th>Jumlah Kursus</th>
                                    <th>Jumlah Pendaftaran</th>
                                    <th>Rata-rata Pendaftaran</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kursus_per_kategori as $kategori): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($kategori['kategori_nama']) ?></td>
                                        <td><?= number_format($kategori['jumlah_kursus']) ?></td>
                                        <td><?= number_format($kategori['jumlah_pendaftaran']) ?></td>
                                        <td>
                                            <?php
                                            $rata_rata = $kategori['jumlah_kursus'] ? $kategori['jumlah_pendaftaran'] / $kategori['jumlah_kursus'] : 0;
                                            echo number_format($rata_rata, 1);
                                            ?>
                                        </td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/admin/kursus?kategori=<?= urlencode($kategori['kategori_nama']) ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> Lihat Kursus
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Detail Laporan Kursus</h6>
                    <div>
                        <button class="btn btn-sm btn-success me-2" onclick="exportToExcel()">
                            <i class="bi bi-file-excel me-1"></i> Export Excel
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="exportToPDF()">
                            <i class="bi bi-file-pdf me-1"></i> Export PDF
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($laporan_kursus)): ?>
                        <div class="alert alert-info">
                            Tidak ada data kursus yang sesuai dengan filter.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="dataTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Judul Kursus</th>
                                        <th>Pembuat</th>
                                        <th>Level</th>
                                        <th>Harga</th>
                                        <th>Status</th>
                                        <th>Total Pendaftaran</th>
                                        <th>Pendaftaran Aktif</th>
                                        <th>Pendaftaran Selesai</th>
                                        <th>Rata-rata Progres</th>
                                        <th>Tanggal Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($laporan_kursus as $kursus): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($kursus['judul']) ?></td>
                                            <td><?= htmlspecialchars($kursus['pembuat']) ?></td>
                                            <td><?= ucfirst(htmlspecialchars($kursus['level'])) ?></td>
                                            <td><?= $kursus['harga'] > 0 ? 'Rp ' . number_format($kursus['harga'], 0, ',', '.') : 'Gratis' ?></td>
                                            <td>
                                                <?php if ($kursus['status'] == 'publikasi'): ?>
                                                    <span class="badge bg-success">Publikasi</span>
                                                <?php elseif ($kursus['status'] == 'draf'): ?>
                                                    <span class="badge bg-warning">Draf</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Arsip</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= number_format($kursus['jumlah_pendaftaran']) ?></td>
                                            <td><?= number_format($kursus['pendaftaran_aktif']) ?></td>
                                            <td><?= number_format($kursus['pendaftaran_selesai']) ?></td>
                                            <td><?= number_format($kursus['rata_rata_progres'], 1) ?>%</td>
                                            <td><?= date('d/m/Y', strtotime($kursus['waktu_dibuat'])) ?></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/admin/kursus/edit?id=<?= $kursus['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>/admin/pendaftaran?kursus_id=<?= $kursus['id'] ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-people"></i>
                                                </a>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    function exportToExcel() {
        window.location.href = '<?= BASE_URL ?>/admin/laporan/kursus/export?format=excel&kategori_id=<?= $kategori_id ?>&level=<?= $level ?>&status=<?= $status ?>&tanggal_mulai=<?= $tanggal_mulai ?>&tanggal_akhir=<?= $tanggal_akhir ?>';
    }

    function exportToPDF() {
        window.location.href = '<?= BASE_URL ?>/admin/laporan/kursus/export?format=pdf&kategori_id=<?= $kategori_id ?>&level=<?= $level ?>&status=<?= $status ?>&tanggal_mulai=<?= $tanggal_mulai ?>&tanggal_akhir=<?= $tanggal_akhir ?>';
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Grafik Tren Pembuatan Kursus
        const trenCtx = document.getElementById('trenChart').getContext('2d');
        new Chart(trenCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($tren_bulan) ?>,
                datasets: [{
                    label: 'Jumlah Kursus',
                    data: <?= json_encode($tren_jumlah) ?>,
                    backgroundColor: 'rgba(57, 255, 20, 0.1)',
                    borderColor: 'rgba(57, 255, 20, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Grafik Distribusi Kursus per Kategori
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($kursus_per_kategori, 'kategori_nama')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($kursus_per_kategori, 'jumlah_kursus')) ?>,
                    backgroundColor: [
                        'rgba(57, 255, 20, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)',
                        'rgba(153, 102, 255, 0.7)',
                        'rgba(255, 159, 64, 0.7)',
                        'rgba(199, 199, 199, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    });
</script>

<?php adminFooter(); ?>