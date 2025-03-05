<?php
// Path: views/admin/laporan/tugas.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Laporan Tugas", "Statistik dan laporan pengumpulan tugas");

$db = dbConnect();

// Filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$kursus_id = isset($_GET['kursus_id']) ? $_GET['kursus_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query untuk mendapatkan data pengumpulan tugas
$query = "
    SELECT pt.*, t.judul as tugas_judul, t.nilai_maksimal, t.tenggat_waktu,
           m.judul as materi_judul, m.id as materi_id,
           mo.judul as modul_judul, mo.id as modul_id,
           k.judul as kursus_judul, k.id as kursus_id,
           pg.nama_lengkap as nama_pengguna, pg.id as pengguna_id
    FROM pengumpulan_tugas pt
    JOIN tugas t ON pt.tugas_id = t.id
    JOIN materi m ON t.materi_id = m.id
    JOIN modul mo ON m.modul_id = mo.id
    JOIN kursus k ON mo.kursus_id = k.id
    JOIN pendaftaran p ON pt.pendaftaran_id = p.id
    JOIN pengguna pg ON p.pengguna_id = pg.id
    WHERE pt.waktu_pengumpulan BETWEEN ? AND ?
";
$params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];

// Tambahkan filter
if (!empty($kursus_id)) {
    $query .= " AND k.id = ?";
    $params[] = $kursus_id;
}

if (!empty($status)) {
    $query .= " AND pt.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $query .= " AND (t.judul LIKE ? OR pg.nama_lengkap LIKE ? OR k.judul LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Tambahkan pengurutan
$query .= " ORDER BY pt.waktu_pengumpulan DESC";

// Eksekusi query
$stmt = $db->prepare($query);
$stmt->execute($params);
$tugasList = $stmt->fetchAll();

// Ambil daftar kursus untuk filter
$stmt = $db->prepare("SELECT id, judul FROM kursus WHERE status = 'publikasi' ORDER BY judul ASC");
$stmt->execute();
$kursusList = $stmt->fetchAll();

// Statistik Tugas
// Total pengumpulan tugas per status
$stmt = $db->prepare("
    SELECT status, COUNT(*) as jumlah 
    FROM pengumpulan_tugas 
    WHERE waktu_pengumpulan BETWEEN ? AND ?
    GROUP BY status
");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$statusCounts = $stmt->fetchAll();

// Rata-rata nilai
$stmt = $db->prepare("
    SELECT AVG(nilai) as rata_nilai 
    FROM pengumpulan_tugas 
    WHERE nilai IS NOT NULL AND waktu_pengumpulan BETWEEN ? AND ?
");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$rataRataNilai = $stmt->fetch()['rata_nilai'] ?? 0;

// Ketepatan waktu pengumpulan
$stmt = $db->prepare("
    SELECT 
        COUNT(CASE WHEN pt.waktu_pengumpulan <= t.tenggat_waktu OR t.tenggat_waktu IS NULL THEN 1 END) as tepat_waktu,
        COUNT(CASE WHEN pt.waktu_pengumpulan > t.tenggat_waktu AND t.tenggat_waktu IS NOT NULL THEN 1 END) as terlambat,
        COUNT(*) as total
    FROM pengumpulan_tugas pt
    JOIN tugas t ON pt.tugas_id = t.id
    WHERE pt.waktu_pengumpulan BETWEEN ? AND ?
");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$waktuStats = $stmt->fetch();

$jumlahTepatWaktu = $waktuStats['tepat_waktu'] ?? 0;
$jumlahTerlambat = $waktuStats['terlambat'] ?? 0;
$totalPengumpulan = $waktuStats['total'] ?? 0;
$persentaseTepatWaktu = $totalPengumpulan > 0 ? ($jumlahTepatWaktu / $totalPengumpulan) * 100 : 0;

// Tugas dengan nilai rata-rata terendah
$stmt = $db->prepare("
    SELECT t.id, t.judul, t.nilai_maksimal,
           COUNT(pt.id) as total_pengumpulan,
           AVG(pt.nilai) as rata_nilai
    FROM tugas t
    JOIN pengumpulan_tugas pt ON t.id = pt.tugas_id
    WHERE pt.nilai IS NOT NULL AND pt.waktu_pengumpulan BETWEEN ? AND ?
    GROUP BY t.id
    HAVING COUNT(pt.id) >= 5
    ORDER BY AVG(pt.nilai) ASC
    LIMIT 5
");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$tugasSulitList = $stmt->fetchAll();

// Statistik pengumpulan tugas per hari
$stmt = $db->prepare("
    SELECT DATE(waktu_pengumpulan) as tanggal, COUNT(*) as jumlah
    FROM pengumpulan_tugas
    WHERE waktu_pengumpulan BETWEEN ? AND ?
    GROUP BY DATE(waktu_pengumpulan)
    ORDER BY tanggal
");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$tugasPerHari = $stmt->fetchAll();

// Hitung jumlah
$totalTugas = count($tugasList);
$totalDinilai = 0;
$totalMenungguPenilaian = 0;
$totalRevisi = 0;
$totalTerlambat = 0;

foreach ($statusCounts as $stat) {
    if ($stat['status'] === 'dinilai') {
        $totalDinilai = $stat['jumlah'];
    } elseif ($stat['status'] === 'menunggu penilaian') {
        $totalMenungguPenilaian = $stat['jumlah'];
    } elseif ($stat['status'] === 'revisi') {
        $totalRevisi = $stat['jumlah'];
    } elseif ($stat['status'] === 'terlambat') {
        $totalTerlambat = $stat['jumlah'];
    }
}

// Siapkan data untuk chart
// Tugas per hari
$hariLabels = [];
$hariData = [];

$currentDay = new DateTime($start_date);
$endDay = new DateTime($end_date);
$endDay->modify('+1 day'); // Termasuk hari terakhir

while ($currentDay < $endDay) {
    $currentDate = $currentDay->format('Y-m-d');
    $hariLabels[] = $currentDay->format('d M');

    $found = false;
    foreach ($tugasPerHari as $item) {
        if ($item['tanggal'] === $currentDate) {
            $hariData[] = $item['jumlah'];
            $found = true;
            break;
        }
    }

    if (!$found) {
        $hariData[] = 0;
    }

    $currentDay->modify('+1 day');
}

// Convert to JSON for charts
$hariLabelsJSON = json_encode($hariLabels);
$hariDataJSON = json_encode($hariData);
$statusDataJSON = json_encode([$totalDinilai, $totalMenungguPenilaian, $totalRevisi, $totalTerlambat]);
$waktuDataJSON = json_encode([$jumlahTepatWaktu, $jumlahTerlambat]);
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="page-title">Laporan Tugas</h1>
            <p class="text-muted">Statistik dan analisis pengumpulan tugas</p>
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

    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                            <i class="bi bi-clipboard-check text-primary fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0">Total Pengumpulan</p>
                            <h3 class="mb-0"><?= number_format($totalTugas) ?></h3>
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
                            <i class="bi bi-star-fill text-success fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0">Rata-rata Nilai</p>
                            <h3 class="mb-0"><?= number_format($rataRataNilai, 1) ?></h3>
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
                            <i class="bi bi-clock-history text-info fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0">Tepat Waktu</p>
                            <h3 class="mb-0"><?= number_format($persentaseTepatWaktu, 1) ?>%</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 p-3 rounded me-3">
                            <i class="bi bi-hourglass-split text-warning fs-4"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-0">Belum Dinilai</p>
                            <h3 class="mb-0"><?= number_format($totalMenungguPenilaian) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
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
                        <?php foreach ($kursusList as $kursus): ?>
                            <option value="<?= $kursus['id'] ?>" <?= $kursus_id === $kursus['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($kursus['judul']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua Status</option>
                        <option value="dinilai" <?= $status === 'dinilai' ? 'selected' : '' ?>>Dinilai</option>
                        <option value="menunggu penilaian" <?= $status === 'menunggu penilaian' ? 'selected' : '' ?>>Menunggu Penilaian</option>
                        <option value="revisi" <?= $status === 'revisi' ? 'selected' : '' ?>>Revisi</option>
                        <option value="terlambat" <?= $status === 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label for="search" class="form-label">Pencarian</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Judul tugas, nama siswa, judul kursus...">
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                    <a href="<?= BASE_URL ?>/admin/laporan/tugas" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Grafik Pengumpulan Tugas Per Hari</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="tugasHarianChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Distribusi Tugas</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container" style="height: 140px;">
                                <canvas id="statusChart"></canvas>
                            </div>
                            <div class="text-center mt-2">
                                <h6>Berdasarkan Status</h6>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="chart-container" style="height: 140px;">
                                <canvas id="waktuChart"></canvas>
                            </div>
                            <div class="text-center mt-2">
                                <h6>Ketepatan Waktu</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Tugas dengan Nilai Rata-rata Terendah</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($tugasSulitList)): ?>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i> Belum cukup data tugas untuk menampilkan statistik ini.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tugas</th>
                                        <th>Total</th>
                                        <th>Rata-rata Nilai</th>
                                        <th>Nilai Maksimal</th>
                                        <th>Persentase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tugasSulitList as $tugas):
                                        $persentase = $tugas['nilai_maksimal'] > 0 ? ($tugas['rata_nilai'] / $tugas['nilai_maksimal']) * 100 : 0;
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($tugas['judul']) ?></td>
                                            <td><?= $tugas['total_pengumpulan'] ?></td>
                                            <td><?= number_format($tugas['rata_nilai'], 1) ?></td>
                                            <td><?= $tugas['nilai_maksimal'] ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                                        <div class="progress-bar <?= $persentase < 60 ? 'bg-danger' : 'bg-success' ?>" role="progressbar" style="width: <?= $persentase ?>%;" aria-valuenow="<?= $persentase ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                    <span class="small"><?= number_format($persentase, 1) ?>%</span>
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
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Statistik Ringkasan</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold">Status Pengumpulan</h6>
                                <div class="mt-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Dinilai:</span>
                                        <span class="fw-semibold"><?= $totalDinilai ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Menunggu Penilaian:</span>
                                        <span class="fw-semibold"><?= $totalMenungguPenilaian ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Revisi:</span>
                                        <span class="fw-semibold"><?= $totalRevisi ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Terlambat:</span>
                                        <span class="fw-semibold"><?= $totalTerlambat ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="border rounded p-3 h-100">
                                <h6 class="fw-bold">Ketepatan Waktu</h6>
                                <div class="mt-2">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Tepat Waktu:</span>
                                        <span class="fw-semibold"><?= $jumlahTepatWaktu ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Terlambat:</span>
                                        <span class="fw-semibold"><?= $jumlahTerlambat ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Total Pengumpulan:</span>
                                        <span class="fw-semibold"><?= $totalPengumpulan ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Persentase Tepat Waktu:</span>
                                        <span class="fw-semibold"><?= number_format($persentaseTepatWaktu, 1) ?>%</span>
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
            <h5 class="mb-0">Data Pengumpulan Tugas</h5>
            <span class="badge bg-primary rounded-pill"><?= count($tugasList) ?> data</span>
        </div>
        <div class="card-body">
            <?php if (empty($tugasList)): ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i> Tidak ada data pengumpulan tugas yang sesuai dengan filter.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="tugasTable">
                        <thead>
                            <tr>
                                <th>Siswa</th>
                                <th>Tugas</th>
                                <th>Kursus</th>
                                <th>Waktu Pengumpulan</th>
                                <th>Tenggat Waktu</th>
                                <th>Status</th>
                                <th>Nilai</th>
                                <th>File</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tugasList as $tugas):
                                $terlambat = !empty($tugas['tenggat_waktu']) && strtotime($tugas['waktu_pengumpulan']) > strtotime($tugas['tenggat_waktu']);
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($tugas['nama_pengguna']) ?></td>
                                    <td><?= htmlspecialchars($tugas['tugas_judul']) ?></td>
                                    <td><?= htmlspecialchars($tugas['kursus_judul']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($tugas['waktu_pengumpulan'])) ?></td>
                                    <td>
                                        <?php if (!empty($tugas['tenggat_waktu'])): ?>
                                            <?= date('d/m/Y H:i', strtotime($tugas['tenggat_waktu'])) ?>
                                            <?php if ($terlambat): ?>
                                                <span class="badge bg-danger ms-1">Terlambat</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php
                                                            switch ($tugas['status']) {
                                                                case 'dinilai':
                                                                    echo 'bg-success';
                                                                    break;
                                                                case 'menunggu penilaian':
                                                                    echo 'bg-warning text-dark';
                                                                    break;
                                                                case 'revisi':
                                                                    echo 'bg-info';
                                                                    break;
                                                                case 'terlambat':
                                                                    echo 'bg-danger';
                                                                    break;
                                                            }
                                                            ?>">
                                            <?= ucfirst($tugas['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($tugas['nilai'])): ?>
                                            <span class="fw-semibold">
                                                <?= $tugas['nilai'] ?>/<?= $tugas['nilai_maksimal'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($tugas['file_jawaban'])): ?>
                                            <a href="<?= BASE_URL ?>/uploads/tugas/<?= $tugas['file_jawaban'] ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="bi bi-download"></i> Unduh
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/admin/tugas/nilai?id=<?= $tugas['id'] ?>" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-check-square"></i> Nilai
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
        margin: 0 auto;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Chart Tugas Harian
        const tugasHarianChart = document.getElementById('tugasHarianChart');
        if (tugasHarianChart) {
            const labels = <?= $hariLabelsJSON ?>;
            const data = <?= $hariDataJSON ?>;

            new Chart(tugasHarianChart, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Jumlah Pengumpulan',
                        data: data,
                        backgroundColor: 'rgba(57, 255, 20, 0.7)',
                        borderColor: 'rgba(57, 255, 20, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
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
        }

        // Chart Status
        const statusChart = document.getElementById('statusChart');
        if (statusChart) {
            const data = <?= $statusDataJSON ?>;

            new Chart(statusChart, {
                type: 'doughnut',
                data: {
                    labels: ['Dinilai', 'Menunggu Penilaian', 'Revisi', 'Terlambat'],
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            'rgba(25, 135, 84, 0.7)',
                            'rgba(255, 193, 7, 0.7)',
                            'rgba(13, 202, 240, 0.7)',
                            'rgba(220, 53, 69, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Chart Ketepatan Waktu
        const waktuChart = document.getElementById('waktuChart');
        if (waktuChart) {
            const data = <?= $waktuDataJSON ?>;

            new Chart(waktuChart, {
                type: 'doughnut',
                data: {
                    labels: ['Tepat Waktu', 'Terlambat'],
                    datasets: [{
                        data: data,
                        backgroundColor: [
                            'rgba(25, 135, 84, 0.7)',
                            'rgba(220, 53, 69, 0.7)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // Export buttons
        document.getElementById('exportExcel').addEventListener('click', function() {
            alert('Fitur export Excel sedang dikembangkan');
        });

        document.getElementById('exportPDF').addEventListener('click', function() {
            alert('Fitur export PDF sedang dikembangkan');
        });
    });
</script>

<?php adminFooter(); ?>