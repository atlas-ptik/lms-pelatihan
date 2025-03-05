<?php
// Path: views/admin/laporan/pengguna.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Ambil data dari database
$db = dbConnect();

// Filter periode
$periode = isset($_GET['periode']) ? $_GET['periode'] : 'bulan_ini';
$tanggal_mulai = '';
$tanggal_akhir = '';

switch ($periode) {
    case 'hari_ini':
        $tanggal_mulai = date('Y-m-d');
        $tanggal_akhir = date('Y-m-d');
        break;
    case 'minggu_ini':
        $tanggal_mulai = date('Y-m-d', strtotime('monday this week'));
        $tanggal_akhir = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'bulan_ini':
        $tanggal_mulai = date('Y-m-01');
        $tanggal_akhir = date('Y-m-t');
        break;
    case 'tahun_ini':
        $tanggal_mulai = date('Y-01-01');
        $tanggal_akhir = date('Y-12-31');
        break;
    case 'kustom':
        $tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-d', strtotime('-30 days'));
        $tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
        break;
    default:
        $tanggal_mulai = date('Y-m-01');
        $tanggal_akhir = date('Y-m-t');
        break;
}

// Filter role
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';

// Query untuk statistik pengguna
$sql_statistik = "
    SELECT 
        COUNT(*) as total_pengguna,
        SUM(CASE WHEN status = 'aktif' THEN 1 ELSE 0 END) as pengguna_aktif,
        SUM(CASE WHEN status = 'nonaktif' THEN 1 ELSE 0 END) as pengguna_nonaktif,
        SUM(CASE WHEN status = 'diblokir' THEN 1 ELSE 0 END) as pengguna_diblokir,
        SUM(CASE WHEN waktu_dibuat BETWEEN :tanggal_mulai AND :tanggal_akhir THEN 1 ELSE 0 END) as pengguna_baru
    FROM 
        pengguna
";

$params_statistik = [
    ':tanggal_mulai' => $tanggal_mulai . ' 00:00:00',
    ':tanggal_akhir' => $tanggal_akhir . ' 23:59:59'
];

if (!empty($role_filter)) {
    $sql_statistik .= " WHERE role_id = (SELECT id FROM role WHERE nama = :role)";
    $params_statistik[':role'] = $role_filter;
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
        'total_pengguna' => 0,
        'pengguna_aktif' => 0,
        'pengguna_nonaktif' => 0,
        'pengguna_diblokir' => 0,
        'pengguna_baru' => 0
    ];
}

// Query untuk laporan pengguna berdasarkan role
$sql_role = "
    SELECT 
        r.nama as role_nama,
        COUNT(p.id) as jumlah_pengguna,
        SUM(CASE WHEN p.status = 'aktif' THEN 1 ELSE 0 END) as aktif,
        SUM(CASE WHEN p.status = 'nonaktif' THEN 1 ELSE 0 END) as nonaktif,
        SUM(CASE WHEN p.status = 'diblokir' THEN 1 ELSE 0 END) as diblokir,
        SUM(CASE WHEN p.waktu_dibuat BETWEEN :tanggal_mulai AND :tanggal_akhir THEN 1 ELSE 0 END) as baru
    FROM 
        role r
    LEFT JOIN 
        pengguna p ON p.role_id = r.id
    GROUP BY 
        r.nama
    ORDER BY 
        jumlah_pengguna DESC
";

try {
    $stmt = $db->prepare($sql_role);
    $stmt->bindValue(':tanggal_mulai', $tanggal_mulai . ' 00:00:00');
    $stmt->bindValue(':tanggal_akhir', $tanggal_akhir . ' 23:59:59');
    $stmt->execute();
    $laporan_role = $stmt->fetchAll();
} catch (PDOException $e) {
    $laporan_role = [];
}

// Query untuk daftar pengguna terbaru
$sql_pengguna = "
    SELECT 
        p.id,
        p.nama_lengkap,
        p.email,
        p.status,
        r.nama as role_nama,
        p.waktu_dibuat,
        p.terakhir_login
    FROM 
        pengguna p
    JOIN 
        role r ON p.role_id = r.id
    WHERE 
        p.waktu_dibuat BETWEEN :tanggal_mulai AND :tanggal_akhir
";

$params_pengguna = [
    ':tanggal_mulai' => $tanggal_mulai . ' 00:00:00',
    ':tanggal_akhir' => $tanggal_akhir . ' 23:59:59'
];

if (!empty($role_filter)) {
    $sql_pengguna .= " AND r.nama = :role";
    $params_pengguna[':role'] = $role_filter;
}

$sql_pengguna .= " ORDER BY p.waktu_dibuat DESC LIMIT 10";

try {
    $stmt = $db->prepare($sql_pengguna);
    foreach ($params_pengguna as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $pengguna_terbaru = $stmt->fetchAll();
} catch (PDOException $e) {
    $pengguna_terbaru = [];
}

// Query untuk mendapatkan semua role
$sql_all_roles = "SELECT nama FROM role ORDER BY nama";
try {
    $stmt = $db->query($sql_all_roles);
    $all_roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $all_roles = [];
}

// Query untuk statistik tren pendaftaran pengguna (bulanan)
$sql_tren = "
    SELECT 
        DATE_FORMAT(waktu_dibuat, '%Y-%m') as bulan,
        COUNT(*) as jumlah
    FROM 
        pengguna
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

adminHeader("Laporan Pengguna", "Laporan statistik pengguna sistem");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Laporan Pengguna</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Laporan Pengguna</li>
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
                        <div class="col-md-4">
                            <label for="periode" class="form-label">Periode</label>
                            <select class="form-select" id="periode" name="periode" onchange="toggleDatePicker()">
                                <option value="hari_ini" <?= $periode == 'hari_ini' ? 'selected' : '' ?>>Hari Ini</option>
                                <option value="minggu_ini" <?= $periode == 'minggu_ini' ? 'selected' : '' ?>>Minggu Ini</option>
                                <option value="bulan_ini" <?= $periode == 'bulan_ini' ? 'selected' : '' ?>>Bulan Ini</option>
                                <option value="tahun_ini" <?= $periode == 'tahun_ini' ? 'selected' : '' ?>>Tahun Ini</option>
                                <option value="kustom" <?= $periode == 'kustom' ? 'selected' : '' ?>>Kustom</option>
                            </select>
                        </div>
                        <div class="col-md-3 date-range" style="<?= $periode == 'kustom' ? '' : 'display: none;' ?>">
                            <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="<?= $tanggal_mulai ?>">
                        </div>
                        <div class="col-md-3 date-range" style="<?= $periode == 'kustom' ? '' : 'display: none;' ?>">
                            <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" value="<?= $tanggal_akhir ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="">Semua Role</option>
                                <?php foreach ($all_roles as $role): ?>
                                    <option value="<?= $role ?>" <?= $role_filter == $role ? 'selected' : '' ?>><?= ucfirst($role) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
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
                                <i class="bi bi-people text-primary fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Total Pengguna</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik['total_pengguna']) ?></h2>
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
                                <i class="bi bi-person-check text-success fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Pengguna Aktif</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik['pengguna_aktif']) ?></h2>
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
                                <i class="bi bi-person-plus text-info fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Pengguna Baru</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik['pengguna_baru']) ?></h2>
                            <small class="text-muted">Periode: <?= date('d/m/Y', strtotime($tanggal_mulai)) ?> - <?= date('d/m/Y', strtotime($tanggal_akhir)) ?></small>
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
                                <i class="bi bi-person-x text-warning fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Pengguna Nonaktif/Diblokir</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik['pengguna_nonaktif'] + $statistik['pengguna_diblokir']) ?></h2>
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
                    <h6 class="m-0 font-weight-bold">Tren Pendaftaran Pengguna (12 Bulan Terakhir)</h6>
                </div>
                <div class="card-body">
                    <canvas id="trenChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold">Distribusi Pengguna Berdasarkan Role</h6>
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
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Pengguna Berdasarkan Role</h6>
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
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Role</th>
                                    <th>Total Pengguna</th>
                                    <th>Aktif</th>
                                    <th>Nonaktif</th>
                                    <th>Diblokir</th>
                                    <th>Pengguna Baru</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($laporan_role as $role): ?>
                                    <tr>
                                        <td><?= ucfirst(htmlspecialchars($role['role_nama'])) ?></td>
                                        <td><?= number_format($role['jumlah_pengguna']) ?></td>
                                        <td><?= number_format($role['aktif']) ?></td>
                                        <td><?= number_format($role['nonaktif']) ?></td>
                                        <td><?= number_format($role['diblokir']) ?></td>
                                        <td><?= number_format($role['baru']) ?></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/admin/pengguna?role=<?= $role['role_nama'] ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye"></i> Lihat
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
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold">Pengguna Terbaru</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($pengguna_terbaru)): ?>
                        <div class="alert alert-info">
                            Tidak ada pengguna baru pada periode yang dipilih.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Tanggal Daftar</th>
                                        <th>Terakhir Login</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pengguna_terbaru as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><?= ucfirst(htmlspecialchars($user['role_nama'])) ?></td>
                                            <td>
                                                <?php if ($user['status'] == 'aktif'): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                <?php elseif ($user['status'] == 'nonaktif'): ?>
                                                    <span class="badge bg-warning">Nonaktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Diblokir</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($user['waktu_dibuat'])) ?></td>
                                            <td><?= $user['terakhir_login'] ? date('d/m/Y H:i', strtotime($user['terakhir_login'])) : '-' ?></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/admin/pengguna/edit?id=<?= $user['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="<?= BASE_URL ?>/admin/pengguna/detail?id=<?= $user['id'] ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye"></i>
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
    function toggleDatePicker() {
        const periode = document.getElementById('periode').value;
        const dateRangeElements = document.querySelectorAll('.date-range');
        
        if (periode === 'kustom') {
            dateRangeElements.forEach(el => el.style.display = 'block');
        } else {
            dateRangeElements.forEach(el => el.style.display = 'none');
        }
    }
    
    function exportToExcel() {
        window.location.href = '<?= BASE_URL ?>/admin/laporan/pengguna/export?format=excel&periode=<?= $periode ?>&tanggal_mulai=<?= $tanggal_mulai ?>&tanggal_akhir=<?= $tanggal_akhir ?>&role=<?= $role_filter ?>';
    }
    
    function exportToPDF() {
        window.location.href = '<?= BASE_URL ?>/admin/laporan/pengguna/export?format=pdf&periode=<?= $periode ?>&tanggal_mulai=<?= $tanggal_mulai ?>&tanggal_akhir=<?= $tanggal_akhir ?>&role=<?= $role_filter ?>';
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Grafik Tren Pendaftaran
        const trenCtx = document.getElementById('trenChart').getContext('2d');
        new Chart(trenCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($tren_bulan) ?>,
                datasets: [{
                    label: 'Jumlah Pendaftaran',
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
        
        // Grafik Distribusi Role
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($laporan_role, 'role_nama')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($laporan_role, 'jumlah_pengguna')) ?>,
                    backgroundColor: [
                        'rgba(57, 255, 20, 0.7)',
                        'rgba(255, 99, 132, 0.7)',
                        'rgba(54, 162, 235, 0.7)',
                        'rgba(255, 206, 86, 0.7)',
                        'rgba(75, 192, 192, 0.7)'
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