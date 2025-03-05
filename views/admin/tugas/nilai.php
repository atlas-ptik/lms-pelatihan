<?php
// Path: views/admin/tugas/nilai.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Sambungkan ke database
$db = dbConnect();

// Ambil ID tugas dari URL
$tugas_id = $_GET['id'] ?? '';

if (empty($tugas_id)) {
    header('Location: ' . BASE_URL . '/admin/tugas');
    exit;
}

// Filter status penilaian
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$cari = isset($_GET['cari']) ? $_GET['cari'] : '';

// Query untuk mendapatkan informasi tugas
$sql_tugas = "
    SELECT 
        t.*,
        m.judul AS materi_judul,
        mo.judul AS modul_judul,
        k.judul AS kursus_judul,
        (SELECT COUNT(*) FROM pengumpulan_tugas WHERE tugas_id = t.id) AS jumlah_pengumpulan,
        (SELECT COUNT(*) FROM pengumpulan_tugas WHERE tugas_id = t.id AND status = 'dinilai') AS jumlah_dinilai,
        (SELECT COUNT(*) FROM pengumpulan_tugas WHERE tugas_id = t.id AND status = 'menunggu penilaian') AS jumlah_menunggu,
        (SELECT COUNT(*) FROM pengumpulan_tugas WHERE tugas_id = t.id AND status = 'revisi') AS jumlah_revisi,
        (SELECT AVG(nilai) FROM pengumpulan_tugas WHERE tugas_id = t.id AND nilai IS NOT NULL) AS rata_rata_nilai
    FROM 
        tugas t
    JOIN 
        materi m ON t.materi_id = m.id
    JOIN 
        modul mo ON m.modul_id = mo.id
    JOIN 
        kursus k ON mo.kursus_id = k.id
    WHERE 
        t.id = :id
";

try {
    $stmt = $db->prepare($sql_tugas);
    $stmt->bindValue(':id', $tugas_id);
    $stmt->execute();
    $tugas = $stmt->fetch();

    if (!$tugas) {
        header('Location: ' . BASE_URL . '/admin/tugas');
        exit;
    }
} catch (PDOException $e) {
    die('Terjadi kesalahan: ' . $e->getMessage());
}

// Proses penilaian jika form di-submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pengumpulan_id = $_POST['pengumpulan_id'] ?? '';
    $nilai = $_POST['nilai'] ?? null;
    $komentar_pengajar = $_POST['komentar_pengajar'] ?? '';
    $status = $_POST['status'] ?? 'dinilai';

    if (!empty($pengumpulan_id) && $nilai !== null) {
        try {
            $sql = "
                UPDATE pengumpulan_tugas SET
                    nilai = :nilai,
                    komentar_pengajar = :komentar_pengajar,
                    status = :status,
                    waktu_penilaian = CURRENT_TIMESTAMP,
                    waktu_diperbarui = CURRENT_TIMESTAMP
                WHERE id = :id AND tugas_id = :tugas_id
            ";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $pengumpulan_id);
            $stmt->bindValue(':tugas_id', $tugas_id);
            $stmt->bindValue(':nilai', $nilai);
            $stmt->bindValue(':komentar_pengajar', $komentar_pengajar);
            $stmt->bindValue(':status', $status);

            $stmt->execute();

            // Set flash message
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Penilaian berhasil disimpan'
            ];

            // Redirect kembali ke halaman yang sama
            header('Location: ' . BASE_URL . '/admin/tugas/nilai?id=' . $tugas_id);
            exit;
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ];
        }
    }
}

// Query untuk mendapatkan daftar pengumpulan tugas
$sql_pengumpulan = "
    SELECT 
        pt.*,
        p.nama_lengkap as nama_siswa,
        p.email as email_siswa,
        pnd.progres_persen
    FROM 
        pengumpulan_tugas pt
    JOIN 
        pendaftaran pnd ON pt.pendaftaran_id = pnd.id
    JOIN 
        pengguna p ON pnd.pengguna_id = p.id
    WHERE 
        pt.tugas_id = :tugas_id
";

$params = [':tugas_id' => $tugas_id];

if (!empty($status_filter)) {
    $sql_pengumpulan .= " AND pt.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($cari)) {
    $sql_pengumpulan .= " AND (p.nama_lengkap LIKE :cari OR p.email LIKE :cari)";
    $params[':cari'] = "%$cari%";
}

$sql_pengumpulan .= " ORDER BY 
    CASE 
        WHEN pt.status = 'menunggu penilaian' THEN 1 
        WHEN pt.status = 'revisi' THEN 2 
        WHEN pt.status = 'dinilai' THEN 3
        WHEN pt.status = 'terlambat' THEN 4
    END,
    pt.waktu_pengumpulan DESC
";

try {
    $stmt = $db->prepare($sql_pengumpulan);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $pengumpulan_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $pengumpulan_list = [];
}

// Hitung persentase penilaian
$total_pengumpulan = $tugas['jumlah_pengumpulan'] > 0 ? $tugas['jumlah_pengumpulan'] : 1;
$persen_dinilai = ($tugas['jumlah_dinilai'] / $total_pengumpulan) * 100;

adminHeader("Penilaian Tugas", "Nilai pengumpulan tugas siswa");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Penilaian Tugas</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/tugas">Tugas</a></li>
            <li class="breadcrumb-item active">Penilaian</li>
        </ol>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show mb-4" role="alert">
            <?= $_SESSION['flash_message']['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">Detail Tugas</h6>
            <a href="<?= BASE_URL ?>/admin/tugas/edit?id=<?= $tugas_id ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-pencil"></i> Edit Tugas
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="150">Judul Tugas</th>
                            <td><?= htmlspecialchars($tugas['judul']) ?></td>
                        </tr>
                        <tr>
                            <th>Kursus</th>
                            <td><?= htmlspecialchars($tugas['kursus_judul']) ?></td>
                        </tr>
                        <tr>
                            <th>Modul / Materi</th>
                            <td><?= htmlspecialchars($tugas['modul_judul']) ?> / <?= htmlspecialchars($tugas['materi_judul']) ?></td>
                        </tr>
                        <tr>
                            <th>Tenggat</th>
                            <td>
                                <?php if ($tugas['tenggat_waktu']): ?>
                                    <?= date('d/m/Y H:i', strtotime($tugas['tenggat_waktu'])) ?>
                                    <?php
                                    $tenggat = new DateTime($tugas['tenggat_waktu']);
                                    $sekarang = new DateTime();
                                    if ($tenggat < $sekarang) {
                                        echo ' <span class="badge bg-danger">Berakhir</span>';
                                    } else {
                                        echo ' <span class="badge bg-success">Aktif</span>';
                                    }
                                    ?>
                                <?php else: ?>
                                    <span class="text-muted">Tidak ada tenggat</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="180">Total Pengumpulan</th>
                            <td>
                                <span class="badge bg-primary"><?= number_format($tugas['jumlah_pengumpulan']) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>Status Penilaian</th>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 10px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $persen_dinilai ?>%;" aria-valuenow="<?= $persen_dinilai ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <div><?= number_format($persen_dinilai, 1) ?>%</div>
                                </div>
                                <div class="mt-2 small">
                                    <span class="badge bg-success me-1"><?= number_format($tugas['jumlah_dinilai']) ?> dinilai</span>
                                    <span class="badge bg-warning me-1"><?= number_format($tugas['jumlah_menunggu']) ?> menunggu</span>
                                    <span class="badge bg-info me-1"><?= number_format($tugas['jumlah_revisi']) ?> revisi</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th>Nilai Maksimal</th>
                            <td><?= number_format($tugas['nilai_maksimal']) ?></td>
                        </tr>
                        <tr>
                            <th>Rata-rata Nilai</th>
                            <td>
                                <?php if ($tugas['rata_rata_nilai']): ?>
                                    <?= number_format($tugas['rata_rata_nilai'], 1) ?>
                                <?php else: ?>
                                    <span class="text-muted">Belum ada penilaian</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Daftar Pengumpulan Tugas</h6>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <form method="GET" action="" class="row g-3">
                    <input type="hidden" name="id" value="<?= $tugas_id ?>">
                    <div class="col-md-5">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Cari nama siswa..." name="cari" value="<?= htmlspecialchars($cari) ?>">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <select class="form-select" name="status" onchange="this.form.submit()">
                            <option value="">-- Semua Status --</option>
                            <option value="menunggu penilaian" <?= $status_filter == 'menunggu penilaian' ? 'selected' : '' ?>>Menunggu Penilaian</option>
                            <option value="dinilai" <?= $status_filter == 'dinilai' ? 'selected' : '' ?>>Sudah Dinilai</option>
                            <option value="revisi" <?= $status_filter == 'revisi' ? 'selected' : '' ?>>Perlu Revisi</option>
                            <option value="terlambat" <?= $status_filter == 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <?php if (!empty($cari) || !empty($status_filter)): ?>
                            <a href="<?= BASE_URL ?>/admin/tugas/nilai?id=<?= $tugas_id ?>" class="btn btn-secondary w-100">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if (empty($pengumpulan_list)): ?>
                <div class="alert alert-info">
                    Belum ada pengumpulan tugas untuk saat ini.
                    <?php if (!empty($cari) || !empty($status_filter)): ?>
                        <br>Silakan ubah filter pencarian.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Siswa</th>
                                <th>Waktu Pengumpulan</th>
                                <th>Jawaban</th>
                                <th>Status</th>
                                <th>Nilai</th>
                                <th width="180">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pengumpulan_list as $pengumpulan): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($pengumpulan['nama_siswa']) ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars($pengumpulan['email_siswa']) ?></div>
                                                <div class="small">Progres: <?= number_format($pengumpulan['progres_persen'], 1) ?>%</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y H:i', strtotime($pengumpulan['waktu_pengumpulan'])) ?>
                                        <?php
                                        if (!empty($tugas['tenggat_waktu']) && strtotime($pengumpulan['waktu_pengumpulan']) > strtotime($tugas['tenggat_waktu'])) {
                                            echo '<br><span class="badge bg-danger">Terlambat</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($pengumpulan['file_jawaban']): ?>
                                            <a href="<?= BASE_URL ?>/uploads/tugas/jawaban/<?= $pengumpulan['file_jawaban'] ?>" target="_blank" class="btn btn-sm btn-outline-primary mb-1">
                                                <i class="bi bi-file-earmark"></i> Lihat File
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($pengumpulan['teks_jawaban']): ?>
                                            <div class="mt-1">
                                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#jawabanModal<?= $pengumpulan['id'] ?>">
                                                    <i class="bi bi-file-text"></i> Lihat Teks
                                                </button>

                                                <!-- Modal Jawaban Teks -->
                                                <div class="modal fade" id="jawabanModal<?= $pengumpulan['id'] ?>" tabindex="-1" aria-labelledby="jawabanModalLabel<?= $pengumpulan['id'] ?>" aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="jawabanModalLabel<?= $pengumpulan['id'] ?>">Jawaban <?= htmlspecialchars($pengumpulan['nama_siswa']) ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="p-3 border rounded bg-light">
                                                                    <?= nl2br(htmlspecialchars($pengumpulan['teks_jawaban'])) ?>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!$pengumpulan['file_jawaban'] && !$pengumpulan['teks_jawaban']): ?>
                                            <span class="text-muted">Tidak ada jawaban</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $status_badge = '';
                                        switch ($pengumpulan['status']) {
                                            case 'menunggu penilaian':
                                                $status_badge = '<span class="badge bg-warning">Menunggu Penilaian</span>';
                                                break;
                                            case 'dinilai':
                                                $status_badge = '<span class="badge bg-success">Dinilai</span>';
                                                break;
                                            case 'revisi':
                                                $status_badge = '<span class="badge bg-info">Perlu Revisi</span>';
                                                break;
                                            case 'terlambat':
                                                $status_badge = '<span class="badge bg-danger">Terlambat</span>';
                                                break;
                                        }
                                        echo $status_badge;

                                        if ($pengumpulan['waktu_penilaian']) {
                                            echo '<div class="small text-muted mt-1">Dinilai: ' . date('d/m/Y H:i', strtotime($pengumpulan['waktu_penilaian'])) . '</div>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($pengumpulan['nilai'] !== null): ?>
                                            <span class="fw-bold"><?= number_format($pengumpulan['nilai']) ?></span> / <?= number_format($tugas['nilai_maksimal']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#nilaiModal<?= $pengumpulan['id'] ?>">
                                            <i class="bi bi-award"></i> Nilai
                                        </button>

                                        <!-- Modal Penilaian -->
                                        <div class="modal fade" id="nilaiModal<?= $pengumpulan['id'] ?>" tabindex="-1" aria-labelledby="nilaiModalLabel<?= $pengumpulan['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="pengumpulan_id" value="<?= $pengumpulan['id'] ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="nilaiModalLabel<?= $pengumpulan['id'] ?>">Penilaian Tugas <?= htmlspecialchars($pengumpulan['nama_siswa']) ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label for="nilai<?= $pengumpulan['id'] ?>" class="form-label">Nilai (0-<?= number_format($tugas['nilai_maksimal']) ?>)</label>
                                                                <input type="number" class="form-control" id="nilai<?= $pengumpulan['id'] ?>" name="nilai" min="0" max="<?= $tugas['nilai_maksimal'] ?>" value="<?= $pengumpulan['nilai'] !== null ? $pengumpulan['nilai'] : '' ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="status<?= $pengumpulan['id'] ?>" class="form-label">Status</label>
                                                                <select class="form-select" id="status<?= $pengumpulan['id'] ?>" name="status">
                                                                    <option value="dinilai" <?= $pengumpulan['status'] == 'dinilai' ? 'selected' : '' ?>>Dinilai</option>
                                                                    <option value="revisi" <?= $pengumpulan['status'] == 'revisi' ? 'selected' : '' ?>>Perlu Revisi</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="komentar<?= $pengumpulan['id'] ?>" class="form-label">Komentar</label>
                                                                <textarea class="form-control" id="komentar<?= $pengumpulan['id'] ?>" name="komentar_pengajar" rows="3"><?= htmlspecialchars($pengumpulan['komentar_pengajar']) ?></textarea>
                                                                <div class="form-text">Berikan feedback atau catatan untuk siswa</div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-primary">Simpan Penilaian</button>
                                                        </div>
                                                    </form>
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

<?php adminFooter(); ?>