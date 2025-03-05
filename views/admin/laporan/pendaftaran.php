<?php
// Path: views/admin/laporan/pendaftaran.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Laporan Pendaftaran", "Statistik dan laporan pendaftaran kursus");

$db = dbConnect();

// Filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$kursus_id = isset($_GET['kursus_id']) ? $_GET['kursus_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Ambil data pendaftaran berdasarkan filter
$query = "SELECT p.id, p.tanggal_daftar, p.status, p.progres_persen, 
          pg.nama_lengkap as nama_pengguna, pg.email, 
          k.judul as judul_kursus, k.level
          FROM pendaftaran p
          JOIN pengguna pg ON p.pengguna_id = pg.id
          JOIN kursus k ON p.kursus_id = k.id
          WHERE p.tanggal_daftar BETWEEN ? AND ?";
$params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];

if (!empty($kursus_id)) {
    $query .= " AND p.kursus_id = ?";
    $params[] = $kursus_id;
}

if (!empty($status)) {
    $query .= " AND p.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY p.tanggal_daftar DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$pendaftaranList = $stmt->fetchAll();

// Ambil statistik
// 1. Total pendaftaran per hari
$stmt = $db->prepare("
    SELECT DATE(tanggal_daftar) as tanggal, COUNT(*) as jumlah
    FROM pendaftaran
    WHERE tanggal_daftar BETWEEN ? AND ?
    GROUP BY DATE(tanggal_daftar)
    ORDER BY tanggal
");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$pendaftaranPerHari = $stmt->fetchAll();

// 2. Pendaftaran berdasarkan status
$stmt = $db->prepare("
    SELECT status, COUNT(*) as jumlah
    FROM pendaftaran
    WHERE tanggal_daftar BETWEEN ? AND ?
    GROUP BY status
");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$pendaftaranStatus = $stmt->fetchAll();

// 3. Top 5 kursus terpopuler
$stmt = $db->prepare("
    SELECT k.judul, COUNT(p.id) as jumlah_pendaftaran
    FROM pendaftaran p
    JOIN kursus k ON p.kursus_id = k.id
    WHERE p.tanggal_daftar BETWEEN ? AND ?
    GROUP BY p.kursus_id
    ORDER BY jumlah_pendaftaran DESC
    LIMIT 5
");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$topKursus = $stmt->fetchAll();

// Ambil daftar kursus untuk filter
$stmt = $db->prepare("SELECT id, judul FROM kursus ORDER BY judul ASC");
$stmt->execute();
$kursusList = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="page-title">Laporan Pendaftaran</h1>
            <p class="text-muted">Analisis pendaftaran kursus pada periode tertentu</p>
        </div>
        <div class="col-md-6 text-md-end">
            <button type="button" class="btn btn-success" id="exportExcel">
                <i class="bi bi-file-excel"></i> Export Excel
            </button>
            <button type="button" class="btn btn-danger" id="exportPDF">
                <i class="bi bi-file-pdf"></i> Export PDF
            </button>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filter Laporan</h5>
        </div>
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Tanggal Akhir</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
                </div>
                <div class="col-md-3">
                    <label for="kursus_id" class="form-label">Kursus</label>
                    <select class="form-select" id="kursus_id" name="kursus_id">
                        <option value="">Semua Kursus</option>
                        <?php foreach ($kursusList as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= $kursus_id === $k['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($k['judul']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
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
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-filter"></i> Terapkan Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Status Pendaftaran</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Jumlah</th>
                                        <th>Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalPendaftaran = array_sum(array_column($pendaftaranStatus, 'jumlah'));
                                    foreach ($pendaftaranStatus as $status): 
                                        $percent = $totalPendaftaran > 0 ? round(($status['jumlah'] / $totalPendaftaran) * 100, 2) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge <?= $status['status'] === 'aktif' ? 'bg-success' : ($status['status'] === 'selesai' ? 'bg-primary' : 'bg-danger') ?>">
                                                <?= ucfirst($status['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= $status['jumlah'] ?></td>
                                        <td><?= $percent ?>%</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Tren Pendaftaran</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Kursus Terpopuler</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($topKursus)): ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i> Belum ada data pendaftaran kursus.
                        </div>
                    <?php else: ?>
                        <div class="chart-container">
                            <canvas id="popularChart"></canvas>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Statistik Ringkasan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-card-body">
                                    <div class="stat-card-icon bg-primary text-white">
                                        <i class="bi bi-people"></i>
                                    </div>
                                    <div class="stat-card-info">
                                        <h5 class="stat-card-title mb-0"><?= count($pendaftaranList) ?></h5>
                                        <p class="stat-card-text mb-0">Total Pendaftaran</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-card-body">
                                    <div class="stat-card-icon bg-success text-white">
                                        <i class="bi bi-check-circle"></i>
                                    </div>
                                    <div class="stat-card-info">
                                        <?php
                                            $totalAktif = 0;
                                            foreach ($pendaftaranStatus as $status) {
                                                if ($status['status'] === 'aktif') {
                                                    $totalAktif = $status['jumlah'];
                                                    break;
                                                }
                                            }
                                        ?>
                                        <h5 class="stat-card-title mb-0"><?= $totalAktif ?></h5>
                                        <p class="stat-card-text mb-0">Pendaftaran Aktif</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-card-body">
                                    <div class="stat-card-icon bg-primary text-white">
                                        <i class="bi bi-check2-all"></i>
                                    </div>
                                    <div class="stat-card-info">
                                        <?php
                                            $totalSelesai = 0;
                                            foreach ($pendaftaranStatus as $status) {
                                                if ($status['status'] === 'selesai') {
                                                    $totalSelesai = $status['jumlah'];
                                                    break;
                                                }
                                            }
                                        ?>
                                        <h5 class="stat-card-title mb-0"><?= $totalSelesai ?></h5>
                                        <p class="stat-card-text mb-0">Kursus Selesai</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6 mb-3">
                            <div class="stat-card">
                                <div class="stat-card-body">
                                    <div class="stat-card-icon bg-danger text-white">
                                        <i class="bi bi-x-circle"></i>
                                    </div>
                                    <div class="stat-card-info">
                                        <?php
                                            $totalBatal = 0;
                                            foreach ($pendaftaranStatus as $status) {
                                                if ($status['status'] === 'dibatalkan') {
                                                    $totalBatal = $status['jumlah'];
                                                    break;
                                                }
                                            }
                                        ?>
                                        <h5 class="stat-card-title mb-0"><?= $totalBatal ?></h5>
                                        <p class="stat-card-text mb-0">Pendaftaran Dibatalkan</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Data Pendaftaran</h5>
            <span class="badge bg-primary"><?= count($pendaftaranList) ?> data</span>
        </div>
        <div class="card-body">
            <?php if (empty($pendaftaranList)): ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i> Tidak ada data pendaftaran untuk filter yang dipilih.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="pendaftaranTable">
                        <thead>
                            <tr>
                                <th>Tanggal Daftar</th>
                                <th>Pengguna</th>
                                <th>Kursus</th>
                                <th>Level</th>
                                <th>Status</th>
                                <th>Progres</th>
                                <th>Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendaftaranList as $data): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($data['tanggal_daftar'])) ?></td>
                                <td>
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($data['nama_pengguna']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($data['email']) ?></div>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($data['judul_kursus']) ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= ucfirst(htmlspecialchars($data['level'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    switch($data['status']) {
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
                                        <?= ucfirst($data['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="progress" style="height: 6px; width: 120px">
                                        <div class="progress-bar" role="progressbar" style="width: <?= $data['progres_persen'] ?>%;" aria-valuenow="<?= $data['progres_persen'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="small"><?= number_format($data['progres_persen'], 1) ?>%</span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/admin/pendaftaran/detail?id=<?= $data['id'] ?>" class="btn btn-sm btn-outline-primary">
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

<style>
.chart-container {
    position: relative;
    height: 220px;
    margin: 0 auto;
}

.stat-card {
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    background-color: #fff;
}

.stat-card-body {
    padding: 15px;
    display: flex;
    align-items: center;
}

.stat-card-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 12px;
    margin-right: 15px;
    font-size: 24px;
}

.stat-card-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
}

.stat-card-text {
    font-size: 0.875rem;
    color: #6c757d;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status Chart
    const statusChart = document.getElementById('statusChart');
    if (statusChart) {
        const statusData = <?= json_encode($pendaftaranStatus) ?>;
        if (statusData.length > 0) {
            const labels = statusData.map(item => item.status.charAt(0).toUpperCase() + item.status.slice(1));
            const data = statusData.map(item => item.jumlah);
            const backgroundColors = [
                'rgba(57, 255, 20, 0.7)',  // green for aktif
                'rgba(13, 110, 253, 0.7)', // blue for selesai
                'rgba(220, 53, 69, 0.7)'   // red for dibatalkan
            ];
            
            new Chart(statusChart, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: backgroundColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }
    
    // Trend Chart
    const trendChart = document.getElementById('trendChart');
    if (trendChart) {
        const trendData = <?= json_encode($pendaftaranPerHari) ?>;
        if (trendData.length > 0) {
            const labels = trendData.map(item => {
                const date = new Date(item.tanggal);
                return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
            });
            const data = trendData.map(item => item.jumlah);
            
            new Chart(trendChart, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Pendaftaran',
                        data: data,
                        borderColor: 'rgba(57, 255, 20, 1)',
                        backgroundColor: 'rgba(57, 255, 20, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
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
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    }
    
    // Popular Courses Chart
    const popularChart = document.getElementById('popularChart');
    if (popularChart) {
        const popularData = <?= json_encode($topKursus) ?>;
        if (popularData.length > 0) {
            const labels = popularData.map(item => item.judul);
            const data = popularData.map(item => item.jumlah_pendaftaran);
            
            new Chart(popularChart, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Pendaftaran',
                        data: data,
                        backgroundColor: 'rgba(57, 255, 20, 0.7)',
                        borderColor: 'rgba(57, 255, 20, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    }
    
    // Export Excel Button
    document.getElementById('exportExcel').addEventListener('click', function() {
        alert('Fitur export Excel sedang dikembangkan');
    });
    
    // Export PDF Button
    document.getElementById('exportPDF').addEventListener('click', function() {
        alert('Fitur export PDF sedang dikembangkan');
    });
});
</script>

<?php adminFooter(); ?>