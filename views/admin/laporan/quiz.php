<?php
// Path: views/admin/laporan/quiz.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Ambil data dari database
$db = dbConnect();

// Filter tanggal
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-d', strtotime('-30 days'));
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');

// Query untuk mengambil data laporan quiz
$sql = "
    SELECT 
        q.id as quiz_id,
        q.judul as quiz_judul,
        m.judul as materi_judul,
        k.judul as kursus_judul,
        COUNT(pq.id) as total_percobaan,
        AVG(pq.nilai) as rata_rata_nilai,
        MIN(pq.nilai) as nilai_terendah,
        MAX(pq.nilai) as nilai_tertinggi,
        COUNT(CASE WHEN pq.nilai >= q.nilai_lulus THEN 1 END) as lulus,
        COUNT(CASE WHEN pq.nilai < q.nilai_lulus THEN 1 END) as tidak_lulus
    FROM 
        quiz q
    JOIN 
        materi m ON q.materi_id = m.id
    JOIN 
        modul md ON m.modul_id = md.id
    JOIN 
        kursus k ON md.kursus_id = k.id
    LEFT JOIN 
        percobaan_quiz pq ON q.id = pq.quiz_id
    WHERE 
        (pq.waktu_mulai IS NULL OR (pq.waktu_mulai BETWEEN :tanggal_mulai_awal AND :tanggal_akhir_akhir))
    GROUP BY 
        q.id, q.judul, m.judul, k.judul
    ORDER BY 
        total_percobaan DESC, rata_rata_nilai DESC
";

try {
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':tanggal_mulai_awal', $tanggal_mulai . ' 00:00:00', PDO::PARAM_STR);
    $stmt->bindValue(':tanggal_akhir_akhir', $tanggal_akhir . ' 23:59:59', PDO::PARAM_STR);
    $stmt->execute();
    $laporan_quiz = $stmt->fetchAll();
} catch (PDOException $e) {
    $laporan_quiz = [];
}

// Query untuk statistik umum quiz
$sql_statistik = "
    SELECT 
        COUNT(DISTINCT q.id) as total_quiz,
        COUNT(pq.id) as total_pengerjaan,
        AVG(pq.nilai) as rata_nilai_keseluruhan,
        SUM(CASE WHEN pq.nilai >= q.nilai_lulus THEN 1 ELSE 0 END) as total_lulus,
        AVG(CASE WHEN pq.nilai >= q.nilai_lulus THEN 1 ELSE 0 END) * 100 as persentase_lulus
    FROM 
        quiz q
    LEFT JOIN 
        percobaan_quiz pq ON q.id = pq.quiz_id
    WHERE 
        (pq.waktu_mulai IS NULL OR (pq.waktu_mulai BETWEEN :tanggal_mulai AND :tanggal_akhir))
";

try {
    $stmt = $db->prepare($sql_statistik);
    $stmt->bindValue(':tanggal_mulai', $tanggal_mulai . ' 00:00:00', PDO::PARAM_STR);
    $stmt->bindValue(':tanggal_akhir', $tanggal_akhir . ' 23:59:59', PDO::PARAM_STR);
    $stmt->execute();
    $statistik = $stmt->fetch();
} catch (PDOException $e) {
    $statistik = [
        'total_quiz' => 0,
        'total_pengerjaan' => 0,
        'rata_nilai_keseluruhan' => 0,
        'total_lulus' => 0,
        'persentase_lulus' => 0
    ];
}

adminHeader("Laporan Quiz", "Laporan statistik quiz dan tingkat kelulusan");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Laporan Quiz</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/laporan/pengguna">Laporan</a></li>
            <li class="breadcrumb-item active">Quiz</li>
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
                        <div class="col-md-5">
                            <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="<?= $tanggal_mulai ?>">
                        </div>
                        <div class="col-md-5">
                            <label for="tanggal_akhir" class="form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" value="<?= $tanggal_akhir ?>">
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
                                <i class="bi bi-question-circle text-primary fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Total Quiz</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik['total_quiz']) ?></h2>
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
                                <i class="bi bi-clipboard-check text-success fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Total Pengerjaan</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik['total_pengerjaan']) ?></h2>
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
                                <i class="bi bi-bar-chart text-info fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Rata-rata Nilai</h6>
                            <h2 class="mt-2 mb-0"><?= $statistik['rata_nilai_keseluruhan'] ? number_format($statistik['rata_nilai_keseluruhan'], 2) : '0' ?></h2>
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
                                <i class="bi bi-trophy text-warning fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Persentase Lulus</h6>
                            <h2 class="mt-2 mb-0"><?= $statistik['persentase_lulus'] ? number_format($statistik['persentase_lulus'], 1) : '0' ?>%</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">Detail Laporan Quiz</h6>
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
                    <?php if (empty($laporan_quiz)): ?>
                        <div class="alert alert-info">
                            Belum ada data quiz untuk periode yang dipilih.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="dataTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Quiz</th>
                                        <th>Materi</th>
                                        <th>Kursus</th>
                                        <th>Total Percobaan</th>
                                        <th>Rata-rata Nilai</th>
                                        <th>Nilai Terendah</th>
                                        <th>Nilai Tertinggi</th>
                                        <th>Lulus</th>
                                        <th>Tidak Lulus</th>
                                        <th>% Kelulusan</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($laporan_quiz as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['quiz_judul']) ?></td>
                                            <td><?= htmlspecialchars($row['materi_judul']) ?></td>
                                            <td><?= htmlspecialchars($row['kursus_judul']) ?></td>
                                            <td><?= number_format($row['total_percobaan']) ?></td>
                                            <td><?= number_format($row['rata_rata_nilai'], 2) ?></td>
                                            <td><?= is_null($row['nilai_terendah']) ? '-' : number_format($row['nilai_terendah'], 2) ?></td>
                                            <td><?= is_null($row['nilai_tertinggi']) ? '-' : number_format($row['nilai_tertinggi'], 2) ?></td>
                                            <td><?= number_format($row['lulus']) ?></td>
                                            <td><?= number_format($row['tidak_lulus']) ?></td>
                                            <td>
                                                <?php
                                                $persentase = $row['total_percobaan'] ? ($row['lulus'] / $row['total_percobaan']) * 100 : 0;
                                                echo number_format($persentase, 1) . '%';
                                                ?>
                                            </td>
                                            <td>
                                                <a href="<?= BASE_URL ?>/admin/quiz/detail?id=<?= $row['quiz_id'] ?>" class="btn btn-sm btn-info">
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

<script>
    function exportToExcel() {
        window.location.href = '<?= BASE_URL ?>/admin/laporan/quiz/export?format=excel&tanggal_mulai=<?= $tanggal_mulai ?>&tanggal_akhir=<?= $tanggal_akhir ?>';
    }

    function exportToPDF() {
        window.location.href = '<?= BASE_URL ?>/admin/laporan/quiz/export?format=pdf&tanggal_mulai=<?= $tanggal_mulai ?>&tanggal_akhir=<?= $tanggal_akhir ?>';
    }
</script>

<?php adminFooter(); ?>