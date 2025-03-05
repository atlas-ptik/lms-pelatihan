<?php
// Path: views/admin/tugas/index.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Sambungkan ke database
$db = dbConnect();

// Ambil parameter filter
$materi_id = isset($_GET['materi_id']) ? $_GET['materi_id'] : '';
$kursus_id = isset($_GET['kursus_id']) ? $_GET['kursus_id'] : '';
$cari = isset($_GET['cari']) ? $_GET['cari'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Query untuk mendapatkan daftar tugas
$sql = "
    SELECT 
        t.id,
        t.judul,
        t.deskripsi,
        t.tenggat_waktu,
        t.nilai_maksimal,
        t.file_lampiran,
        t.tipe_pengumpulan,
        m.judul AS materi_judul,
        mo.judul AS modul_judul,
        k.judul AS kursus_judul,
        (SELECT COUNT(*) FROM pengumpulan_tugas WHERE tugas_id = t.id) AS jumlah_pengumpulan,
        (SELECT COUNT(*) FROM pengumpulan_tugas WHERE tugas_id = t.id AND status = 'dinilai') AS jumlah_dinilai,
        (SELECT COUNT(*) FROM pengumpulan_tugas WHERE tugas_id = t.id AND status = 'menunggu penilaian') AS jumlah_menunggu,
        t.waktu_dibuat,
        t.waktu_diperbarui
    FROM 
        tugas t
    JOIN 
        materi m ON t.materi_id = m.id
    JOIN 
        modul mo ON m.modul_id = mo.id
    JOIN 
        kursus k ON mo.kursus_id = k.id
    WHERE 
        1=1
";

$params = [];

if (!empty($materi_id)) {
    $sql .= " AND t.materi_id = :materi_id";
    $params[':materi_id'] = $materi_id;
}

if (!empty($kursus_id)) {
    $sql .= " AND mo.kursus_id = :kursus_id";
    $params[':kursus_id'] = $kursus_id;
}

if (!empty($cari)) {
    $sql .= " AND (t.judul LIKE :cari OR t.deskripsi LIKE :cari OR m.judul LIKE :cari OR k.judul LIKE :cari)";
    $params[':cari'] = "%$cari%";
}

if ($status_filter === 'dengan_tenggat') {
    $sql .= " AND t.tenggat_waktu IS NOT NULL";
} elseif ($status_filter === 'tanpa_tenggat') {
    $sql .= " AND t.tenggat_waktu IS NULL";
} elseif ($status_filter === 'aktif') {
    $sql .= " AND (t.tenggat_waktu IS NULL OR t.tenggat_waktu >= NOW())";
} elseif ($status_filter === 'berakhir') {
    $sql .= " AND t.tenggat_waktu < NOW()";
}

$sql .= " ORDER BY t.waktu_dibuat DESC";

try {
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $tugas_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $tugas_list = [];
}

// Query untuk mendapatkan daftar kursus (untuk filter)
$sql_kursus = "SELECT id, judul FROM kursus ORDER BY judul";
try {
    $stmt = $db->query($sql_kursus);
    $kursus_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $kursus_list = [];
}

// Query untuk mendapatkan daftar materi (untuk filter)
$sql_materi = "
    SELECT 
        m.id, 
        m.judul,
        CONCAT(k.judul, ' - ', mo.judul, ' - ', m.judul) AS materi_lengkap
    FROM 
        materi m
    JOIN 
        modul mo ON m.modul_id = mo.id
    JOIN 
        kursus k ON mo.kursus_id = k.id
    WHERE 
        m.tipe IN ('tugas', 'video', 'artikel', 'dokumen')
    ORDER BY 
        k.judul, mo.urutan, m.urutan
";
try {
    $stmt = $db->query($sql_materi);
    $materi_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $materi_list = [];
}

// Query untuk mendapatkan statistik tugas
$sql_statistik = "
    SELECT 
        COUNT(t.id) AS total_tugas,
        SUM(CASE WHEN t.tenggat_waktu IS NULL THEN 1 ELSE 0 END) AS tugas_tanpa_tenggat,
        SUM(CASE WHEN t.tenggat_waktu IS NOT NULL AND t.tenggat_waktu < NOW() THEN 1 ELSE 0 END) AS tugas_berakhir,
        SUM(CASE WHEN t.tenggat_waktu IS NOT NULL AND t.tenggat_waktu >= NOW() THEN 1 ELSE 0 END) AS tugas_aktif,
        (SELECT COUNT(*) FROM pengumpulan_tugas) AS total_pengumpulan,
        (SELECT COUNT(*) FROM pengumpulan_tugas WHERE status = 'menunggu penilaian') AS menunggu_penilaian
    FROM 
        tugas t
";
try {
    $stmt = $db->query($sql_statistik);
    $statistik = $stmt->fetch();
} catch (PDOException $e) {
    $statistik = [
        'total_tugas' => 0,
        'tugas_tanpa_tenggat' => 0,
        'tugas_berakhir' => 0,
        'tugas_aktif' => 0,
        'total_pengumpulan' => 0,
        'menunggu_penilaian' => 0
    ];
}

adminHeader("Manajemen Tugas", "Kelola tugas dan penilaian");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manajemen Tugas</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Tugas</li>
        </ol>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show mb-4" role="alert">
            <?= $_SESSION['flash_message']['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-clipboard-check text-primary fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Total Tugas</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik['total_tugas']) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="bi bi-file-check text-success fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Tugas Aktif</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik['tugas_aktif'] + $statistik['tugas_tanpa_tenggat']) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="bi bi-cloud-upload text-info fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Total Pengumpulan</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik['total_pengumpulan']) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="bi bi-hourglass-split text-warning fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Menunggu Penilaian</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik['menunggu_penilaian']) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">Daftar Tugas</h6>
            <a href="<?= BASE_URL ?>/admin/tugas/tambah" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Tambah Tugas
            </a>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Cari judul tugas..." name="cari" value="<?= htmlspecialchars($cari) ?>">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="kursus_id" onchange="this.form.submit()">
                            <option value="">-- Pilih Kursus --</option>
                            <?php foreach ($kursus_list as $kursus): ?>
                                <option value="<?= $kursus['id'] ?>" <?= $kursus_id == $kursus['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kursus['judul']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="materi_id" onchange="this.form.submit()">
                            <option value="">-- Pilih Materi --</option>
                            <?php foreach ($materi_list as $materi): ?>
                                <option value="<?= $materi['id'] ?>" <?= $materi_id == $materi['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($materi['materi_lengkap']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="">-- Semua Status --</option>
                            <option value="aktif" <?= $status_filter == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="berakhir" <?= $status_filter == 'berakhir' ? 'selected' : '' ?>>Berakhir</option>
                            <option value="dengan_tenggat" <?= $status_filter == 'dengan_tenggat' ? 'selected' : '' ?>>Dengan Tenggat</option>
                            <option value="tanpa_tenggat" <?= $status_filter == 'tanpa_tenggat' ? 'selected' : '' ?>>Tanpa Tenggat</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <?php if (!empty($cari) || !empty($kursus_id) || !empty($materi_id) || !empty($status_filter)): ?>
                            <a href="<?= BASE_URL ?>/admin/tugas" class="btn btn-secondary w-100">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if (empty($tugas_list)): ?>
                <div class="alert alert-info">
                    Belum ada tugas yang tersedia.
                    <?php if (!empty($cari) || !empty($kursus_id) || !empty($materi_id) || !empty($status_filter)): ?>
                        <br>Silakan ubah filter pencarian.
                    <?php else: ?>
                        <br>Silakan buat tugas baru.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Judul Tugas</th>
                                <th>Kursus / Modul / Materi</th>
                                <th>Tenggat</th>
                                <th>Tipe Pengumpulan</th>
                                <th>Nilai Maks</th>
                                <th>Status Penilaian</th>
                                <th>Diperbarui</th>
                                <th width="180">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tugas_list as $tugas): ?>
                                <tr>
                                    <td><?= htmlspecialchars($tugas['judul']) ?></td>
                                    <td>
                                        <small><?= htmlspecialchars($tugas['kursus_judul']) ?> &raquo; <?= htmlspecialchars($tugas['modul_judul']) ?> &raquo; <?= htmlspecialchars($tugas['materi_judul']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($tugas['tenggat_waktu']): ?>
                                            <?php
                                            $tenggat = new DateTime($tugas['tenggat_waktu']);
                                            $sekarang = new DateTime();
                                            $status_class = $tenggat < $sekarang ? 'bg-danger' : 'bg-success';
                                            ?>
                                            <span class="badge <?= $status_class ?>">
                                                <?= date('d/m/Y H:i', strtotime($tugas['tenggat_waktu'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Tidak ada tenggat</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $tipe_text = '';
                                        switch ($tugas['tipe_pengumpulan']) {
                                            case 'file':
                                                $tipe_text = '<i class="bi bi-file-earmark me-1"></i> File';
                                                break;
                                            case 'teks':
                                                $tipe_text = '<i class="bi bi-fonts me-1"></i> Teks';
                                                break;
                                            case 'keduanya':
                                                $tipe_text = '<i class="bi bi-file-text me-1"></i> File & Teks';
                                                break;
                                        }
                                        echo $tipe_text;
                                        ?>
                                    </td>
                                    <td><?= number_format($tugas['nilai_maksimal']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1 me-2" style="height: 5px; background-color: #e9ecef; border-radius: 5px;">
                                                <?php if ($tugas['jumlah_pengumpulan'] > 0): ?>
                                                    <?php $persen_dinilai = ($tugas['jumlah_dinilai'] / $tugas['jumlah_pengumpulan']) * 100; ?>
                                                    <div style="width: <?= $persen_dinilai ?>%; height: 100%; background-color: #39ff14; border-radius: 5px;"></div>
                                                <?php endif; ?>
                                            </div>
                                            <span class="small">
                                                <?= $tugas['jumlah_dinilai'] ?>/<?= $tugas['jumlah_pengumpulan'] ?>
                                                <?php if ($tugas['jumlah_menunggu'] > 0): ?>
                                                    <span class="badge bg-warning ms-1"><?= $tugas['jumlah_menunggu'] ?> menunggu</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($tugas['waktu_diperbarui'])) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/admin/tugas/edit?id=<?= $tugas['id'] ?>" class="btn btn-sm btn-primary mb-1">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/tugas/nilai?id=<?= $tugas['id'] ?>" class="btn btn-sm btn-success mb-1">
                                            <i class="bi bi-award"></i> Nilai
                                            <?php if ($tugas['jumlah_menunggu'] > 0): ?>
                                                <span class="badge bg-white text-success"><?= $tugas['jumlah_menunggu'] ?></span>
                                            <?php endif; ?>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger mb-1" onclick="hapusTugas('<?= $tugas['id'] ?>', '<?= htmlspecialchars($tugas['judul']) ?>')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
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

<!-- Modal Hapus Tugas -->
<div class="modal fade" id="hapusTugasModal" tabindex="-1" aria-labelledby="hapusTugasModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hapusTugasModalLabel">Konfirmasi Hapus Tugas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus tugas "<span id="tugasTitle"></span>"?</p>
                <p class="text-danger"><strong>Perhatian:</strong> Semua data pengumpulan tugas terkait juga akan dihapus.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="<?= BASE_URL ?>/admin/tugas/hapus" method="POST" id="formHapusTugas">
                    <input type="hidden" name="id" id="tugasId">
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function hapusTugas(id, judul) {
        document.getElementById('tugasId').value = id;
        document.getElementById('tugasTitle').textContent = judul;

        const hapusTugasModal = new bootstrap.Modal(document.getElementById('hapusTugasModal'));
        hapusTugasModal.show();
    }
</script>

<?php adminFooter(); ?>