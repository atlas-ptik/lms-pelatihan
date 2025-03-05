<?php
// Path: views/admin/pendaftaran/detail.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Detail Pendaftaran", "Informasi detail pendaftaran kursus");

$db = dbConnect();
$pesan = '';
$tipe = '';

// Cek parameter id
if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "/admin/pendaftaran");
    exit;
}

$id = $_GET['id'];

// Pesan notifikasi jika ada
if (isset($_GET['pesan']) && isset($_GET['tipe'])) {
    $pesan = $_GET['pesan'];
    $tipe = $_GET['tipe'];
}

// Ambil data pendaftaran
$stmt = $db->prepare("
    SELECT p.*, 
           pg.nama_lengkap as nama_pengguna, pg.email as email_pengguna, pg.username, pg.foto_profil, 
           k.judul as judul_kursus, k.deskripsi as deskripsi_kursus, k.gambar_sampul, k.level as level_kursus, k.harga
    FROM pendaftaran p
    JOIN pengguna pg ON p.pengguna_id = pg.id
    JOIN kursus k ON p.kursus_id = k.id
    WHERE p.id = ?
");
$stmt->execute([$id]);
$pendaftaran = $stmt->fetch();

if (!$pendaftaran) {
    header("Location: " . BASE_URL . "/admin/pendaftaran");
    exit;
}

// Proses update jika ada
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = trim($_POST['status'] ?? $pendaftaran['status']);
    $progres_persen = floatval($_POST['progres_persen'] ?? $pendaftaran['progres_persen']);

    $errors = [];

    if ($progres_persen < 0 || $progres_persen > 100) {
        $errors[] = "Progres harus berada di antara 0 dan 100 persen";
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Update status pendaftaran
            $stmt = $db->prepare("UPDATE pendaftaran SET status = ?, progres_persen = ?, waktu_diperbarui = NOW() WHERE id = ?");
            $stmt->execute([$status, $progres_persen, $id]);

            // Jika status selesai, update tanggal selesai
            if ($status === 'selesai' && $pendaftaran['status'] !== 'selesai') {
                $stmt = $db->prepare("UPDATE pendaftaran SET tanggal_selesai = NOW() WHERE id = ?");
                $stmt->execute([$id]);
            } elseif ($status !== 'selesai' && $pendaftaran['status'] === 'selesai') {
                // Jika status berubah dari selesai ke yang lain, hapus tanggal selesai
                $stmt = $db->prepare("UPDATE pendaftaran SET tanggal_selesai = NULL WHERE id = ?");
                $stmt->execute([$id]);
            }

            $db->commit();

            $pesan = "Pendaftaran berhasil diperbarui";
            $tipe = "success";

            // Refresh data pendaftaran
            $stmt = $db->prepare("
                SELECT p.*, 
                       pg.nama_lengkap as nama_pengguna, pg.email as email_pengguna, pg.username, pg.foto_profil, 
                       k.judul as judul_kursus, k.deskripsi as deskripsi_kursus, k.gambar_sampul, k.level as level_kursus, k.harga
                FROM pendaftaran p
                JOIN pengguna pg ON p.pengguna_id = pg.id
                JOIN kursus k ON p.kursus_id = k.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $pendaftaran = $stmt->fetch();
        } catch (PDOException $e) {
            $db->rollBack();
            $pesan = "Gagal memperbarui pendaftaran: " . $e->getMessage();
            $tipe = "danger";
        }
    } else {
        $pesan = implode("<br>", $errors);
        $tipe = "danger";
    }
}

// Ambil progres materi
$stmt = $db->prepare("
    SELECT pm.*, m.judul as judul_materi, m.tipe as tipe_materi, md.judul as judul_modul
    FROM progres_materi pm
    JOIN materi m ON pm.materi_id = m.id
    JOIN modul md ON m.modul_id = md.id
    WHERE pm.pendaftaran_id = ?
    ORDER BY md.urutan ASC, m.urutan ASC
");
$stmt->execute([$id]);
$progresMateriList = $stmt->fetchAll();

// Hitung statistik progres
$totalMateri = count($progresMateriList);
$materiSelesai = 0;
$materiSedangDipelajari = 0;
$materiBelumMulai = 0;

foreach ($progresMateriList as $progres) {
    switch ($progres['status']) {
        case 'selesai':
            $materiSelesai++;
            break;
        case 'sedang dipelajari':
            $materiSedangDipelajari++;
            break;
        case 'belum mulai':
            $materiBelumMulai++;
            break;
    }
}

// Hitung progres persentase berdasarkan materi yang selesai
$hitungProgresPersentase = $totalMateri > 0 ? ($materiSelesai / $totalMateri) * 100 : 0;

// Ambil data quiz yang telah diambil
$stmt = $db->prepare("
    SELECT pq.*, q.judul as judul_quiz, q.nilai_lulus,
           m.judul as judul_materi
    FROM percobaan_quiz pq
    JOIN quiz q ON pq.quiz_id = q.id
    JOIN materi m ON q.materi_id = m.id
    WHERE pq.pendaftaran_id = ?
    ORDER BY pq.waktu_mulai DESC
");
$stmt->execute([$id]);
$quizList = $stmt->fetchAll();

// Ambil data pengumpulan tugas
$stmt = $db->prepare("
    SELECT pt.*, t.judul as judul_tugas, t.tenggat_waktu,
           m.judul as judul_materi
    FROM pengumpulan_tugas pt
    JOIN tugas t ON pt.tugas_id = t.id
    JOIN materi m ON t.materi_id = m.id
    WHERE pt.pendaftaran_id = ?
    ORDER BY pt.waktu_pengumpulan DESC
");
$stmt->execute([$id]);
$tugasList = $stmt->fetchAll();

// Ambil data sertifikat
$stmt = $db->prepare("
    SELECT * FROM sertifikat WHERE pendaftaran_id = ?
");
$stmt->execute([$id]);
$sertifikat = $stmt->fetch();
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="page-title">Detail Pendaftaran</h1>
            <p class="text-muted">
                Pendaftaran <strong><?= htmlspecialchars($pendaftaran['nama_pengguna']) ?></strong> pada kursus <strong><?= htmlspecialchars($pendaftaran['judul_kursus']) ?></strong>
            </p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?= BASE_URL ?>/admin/pendaftaran" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <?php if (!$sertifikat && $pendaftaran['status'] === 'selesai'): ?>
                <a href="<?= BASE_URL ?>/admin/sertifikat/tambah?pendaftaran_id=<?= $id ?>" class="btn btn-success">
                    <i class="bi bi-award"></i> Terbitkan Sertifikat
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($pesan)): ?>
        <div class="alert alert-<?= $tipe ?> alert-dismissible fade show" role="alert">
            <?= $pesan ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-lg-9">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <?php if (!empty($pendaftaran['foto_profil'])): ?>
                                <img src="<?= BASE_URL ?>/uploads/profil/<?= $pendaftaran['foto_profil'] ?>" alt="<?= htmlspecialchars($pendaftaran['nama_pengguna']) ?>" class="img-thumbnail rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                            <?php else: ?>
                                <div class="avatar-placeholder rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 100px; height: 100px; font-size: 36px;">
                                    <?= strtoupper(substr($pendaftaran['nama_pengguna'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col">
                            <h4 class="mb-0"><?= htmlspecialchars($pendaftaran['nama_pengguna']) ?></h4>
                            <p class="text-muted mb-2">
                                <i class="bi bi-envelope"></i> <?= htmlspecialchars($pendaftaran['email_pengguna']) ?>
                                &nbsp;|&nbsp;
                                <i class="bi bi-person"></i> <?= htmlspecialchars($pendaftaran['username']) ?>
                            </p>

                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <span class="badge <?php
                                                        switch ($pendaftaran['status']) {
                                                            case 'aktif':
                                                                echo 'bg-success';
                                                                break;
                                                            case 'selesai':
                                                                echo 'bg-primary';
                                                                break;
                                                            case 'dibatalkan':
                                                                echo 'bg-danger';
                                                                break;
                                                        }
                                                        ?>">
                                        <?= ucfirst($pendaftaran['status']) ?>
                                    </span>
                                </div>
                                <div class="me-3">
                                    <i class="bi bi-calendar"></i> Terdaftar: <?= date('d/m/Y', strtotime($pendaftaran['tanggal_daftar'])) ?>
                                </div>
                                <?php if (!empty($pendaftaran['tanggal_selesai'])): ?>
                                    <div>
                                        <i class="bi bi-check-circle"></i> Selesai: <?= date('d/m/Y', strtotime($pendaftaran['tanggal_selesai'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Progres Belajar</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="progress-circle position-relative d-inline-block">
                            <svg width="120" height="120" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="54" fill="none" stroke="#e9ecef" stroke-width="12" />
                                <circle cx="60" cy="60" r="54" fill="none" stroke="#39ff14" stroke-width="12"
                                    stroke-dasharray="339.292" stroke-dashoffset="<?= 339.292 * (1 - $pendaftaran['progres_persen'] / 100) ?>" />
                            </svg>
                            <div class="position-absolute top-50 start-50 translate-middle">
                                <h3 class="mb-0"><?= number_format($pendaftaran['progres_persen'], 1) ?>%</h3>
                            </div>
                        </div>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            Selesai
                            <span class="badge bg-success rounded-pill"><?= $materiSelesai ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            Sedang Dipelajari
                            <span class="badge bg-warning rounded-pill"><?= $materiSedangDipelajari ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            Belum Mulai
                            <span class="badge bg-secondary rounded-pill"><?= $materiBelumMulai ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Kursus</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 me-3">
                            <?php if (!empty($pendaftaran['gambar_sampul'])): ?>
                                <img src="<?= BASE_URL ?>/uploads/kursus/<?= $pendaftaran['gambar_sampul'] ?>" alt="<?= htmlspecialchars($pendaftaran['judul_kursus']) ?>" class="img-thumbnail" style="width: 80px; height: 60px; object-fit: cover;">
                            <?php else: ?>
                                <div class="img-placeholder bg-light text-center rounded d-flex align-items-center justify-content-center" style="width: 80px; height: 60px;">
                                    <i class="bi bi-image text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h5 class="mb-1"><?= htmlspecialchars($pendaftaran['judul_kursus']) ?></h5>
                            <div>
                                <span class="badge bg-secondary"><?= ucfirst($pendaftaran['level_kursus']) ?></span>
                                <span class="ms-2"><?= $pendaftaran['harga'] > 0 ? 'Rp ' . number_format($pendaftaran['harga'], 0, ',', '.') : '<span class="badge bg-success">Gratis</span>' ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi Kursus</label>
                        <p class="text-muted small"><?= nl2br(htmlspecialchars(substr($pendaftaran['deskripsi_kursus'], 0, 200))) ?>...</p>
                    </div>

                    <a href="<?= BASE_URL ?>/admin/kursus/edit?id=<?= $pendaftaran['kursus_id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> Lihat Detail Kursus
                    </a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Update Status Pendaftaran</h5>
                    <span class="badge <?php
                                        switch ($pendaftaran['status']) {
                                            case 'aktif':
                                                echo 'bg-success';
                                                break;
                                            case 'selesai':
                                                echo 'bg-primary';
                                                break;
                                            case 'dibatalkan':
                                                echo 'bg-danger';
                                                break;
                                        }
                                        ?>">
                        Status Saat Ini: <?= ucfirst($pendaftaran['status']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <form action="" method="POST" class="row align-items-end">
                        <div class="col-md-4 mb-3">
                            <label for="status" class="form-label">Status Pendaftaran</label>
                            <select class="form-select" id="status" name="status">
                                <option value="aktif" <?= $pendaftaran['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="selesai" <?= $pendaftaran['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                <option value="dibatalkan" <?= $pendaftaran['status'] === 'dibatalkan' ? 'selected' : '' ?>>Dibatalkan</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="progres_persen" class="form-label">Progres (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="progres_persen" name="progres_persen" value="<?= $pendaftaran['progres_persen'] ?>" min="0" max="100" step="0.1">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-save"></i> Update Status
                            </button>
                        </div>

                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <strong>Tips:</strong>
                                <ul class="mb-0 ps-3">
                                    <li>Status "Selesai" akan mengatur tanggal selesai ke saat ini jika belum ada.</li>
                                    <li>Progres dihitung otomatis berdasarkan materi yang selesai, tapi dapat diatur manual.</li>
                                    <li>Perubahan status ke "Selesai" tidak otomatis mengubah status materi.</li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" id="progressTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="materi-tab" data-bs-toggle="tab" data-bs-target="#materi-tab-pane" type="button" role="tab" aria-controls="materi-tab-pane" aria-selected="true">
                                Progres Materi <span class="badge bg-primary ms-1"><?= $totalMateri ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="quiz-tab" data-bs-toggle="tab" data-bs-target="#quiz-tab-pane" type="button" role="tab" aria-controls="quiz-tab-pane" aria-selected="false">
                                Quiz <span class="badge bg-primary ms-1"><?= count($quizList) ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tugas-tab" data-bs-toggle="tab" data-bs-target="#tugas-tab-pane" type="button" role="tab" aria-controls="tugas-tab-pane" aria-selected="false">
                                Tugas <span class="badge bg-primary ms-1"><?= count($tugasList) ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="sertifikat-tab" data-bs-toggle="tab" data-bs-target="#sertifikat-tab-pane" type="button" role="tab" aria-controls="sertifikat-tab-pane" aria-selected="false">
                                Sertifikat <?= $sertifikat ? '<span class="badge bg-success ms-1">Ada</span>' : '<span class="badge bg-secondary ms-1">Belum Ada</span>' ?>
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="progressTabContent">
                        <div class="tab-pane fade show active" id="materi-tab-pane" role="tabpanel" aria-labelledby="materi-tab" tabindex="0">
                            <?php if (empty($progresMateriList)): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i> Belum ada data progres materi untuk kursus ini.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Modul</th>
                                                <th>Materi</th>
                                                <th>Tipe</th>
                                                <th>Status</th>
                                                <th>Waktu Mulai</th>
                                                <th>Waktu Selesai</th>
                                                <th>Durasi Belajar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $currentModul = '';
                                            foreach ($progresMateriList as $progress):
                                                $isNewModul = $currentModul !== $progress['judul_modul'];
                                                $currentModul = $progress['judul_modul'];
                                            ?>
                                                <tr>
                                                    <td><?= $isNewModul ? htmlspecialchars($progress['judul_modul']) : '' ?></td>
                                                    <td><?= htmlspecialchars($progress['judul_materi']) ?></td>
                                                    <td>
                                                        <?php
                                                        $tipeBadge = '';
                                                        $tipeIcon = '';

                                                        switch ($progress['tipe_materi']) {
                                                            case 'video':
                                                                $tipeBadge = 'bg-danger';
                                                                $tipeIcon = 'bi-camera-video';
                                                                break;
                                                            case 'artikel':
                                                                $tipeBadge = 'bg-primary';
                                                                $tipeIcon = 'bi-file-text';
                                                                break;
                                                            case 'dokumen':
                                                                $tipeBadge = 'bg-secondary';
                                                                $tipeIcon = 'bi-file-earmark';
                                                                break;
                                                            case 'quiz':
                                                                $tipeBadge = 'bg-success';
                                                                $tipeIcon = 'bi-question-circle';
                                                                break;
                                                            case 'tugas':
                                                                $tipeBadge = 'bg-warning text-dark';
                                                                $tipeIcon = 'bi-clipboard-check';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge <?= $tipeBadge ?>">
                                                            <i class="bi <?= $tipeIcon ?> me-1"></i>
                                                            <?= ucfirst($progress['tipe_materi']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $statusBadge = '';
                                                        $statusIcon = '';

                                                        switch ($progress['status']) {
                                                            case 'selesai':
                                                                $statusBadge = 'bg-success';
                                                                $statusIcon = 'bi-check-circle';
                                                                break;
                                                            case 'sedang dipelajari':
                                                                $statusBadge = 'bg-warning text-dark';
                                                                $statusIcon = 'bi-play-circle';
                                                                break;
                                                            case 'belum mulai':
                                                                $statusBadge = 'bg-secondary';
                                                                $statusIcon = 'bi-clock';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge <?= $statusBadge ?>">
                                                            <i class="bi <?= $statusIcon ?> me-1"></i>
                                                            <?= ucfirst($progress['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?= !empty($progress['waktu_mulai']) ? date('d/m/Y H:i', strtotime($progress['waktu_mulai'])) : '<span class="text-muted">-</span>' ?>
                                                    </td>
                                                    <td>
                                                        <?= !empty($progress['waktu_selesai']) ? date('d/m/Y H:i', strtotime($progress['waktu_selesai'])) : '<span class="text-muted">-</span>' ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if (!empty($progress['durasi_belajar_detik'])) {
                                                            $hours = floor($progress['durasi_belajar_detik'] / 3600);
                                                            $minutes = floor(($progress['durasi_belajar_detik'] % 3600) / 60);
                                                            echo $hours > 0 ? $hours . ' jam ' : '';
                                                            echo $minutes . ' menit';
                                                        } else {
                                                            echo '<span class="text-muted">-</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="tab-pane fade" id="quiz-tab-pane" role="tabpanel" aria-labelledby="quiz-tab" tabindex="0">
                            <?php if (empty($quizList)): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i> Belum ada data quiz yang diambil.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Quiz</th>
                                                <th>Materi</th>
                                                <th>Waktu Mulai</th>
                                                <th>Waktu Selesai</th>
                                                <th>Durasi</th>
                                                <th>Nilai</th>
                                                <th>Status</th>
                                                <th>Hasil</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($quizList as $quiz): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($quiz['judul_quiz']) ?></td>
                                                    <td><?= htmlspecialchars($quiz['judul_materi']) ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($quiz['waktu_mulai'])) ?></td>
                                                    <td>
                                                        <?= !empty($quiz['waktu_selesai']) ? date('d/m/Y H:i', strtotime($quiz['waktu_selesai'])) : '<span class="text-muted">-</span>' ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        if (!empty($quiz['durasi_detik'])) {
                                                            $minutes = floor($quiz['durasi_detik'] / 60);
                                                            $seconds = $quiz['durasi_detik'] % 60;
                                                            echo $minutes . ' menit ' . $seconds . ' detik';
                                                        } else {
                                                            echo '<span class="text-muted">-</span>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($quiz['nilai'])): ?>
                                                            <span class="fw-semibold <?= $quiz['nilai'] >= $quiz['nilai_lulus'] ? 'text-success' : 'text-danger' ?>">
                                                                <?= number_format($quiz['nilai'], 1) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $quizStatusBadge = '';
                                                        switch ($quiz['status']) {
                                                            case 'selesai':
                                                                $quizStatusBadge = 'bg-success';
                                                                break;
                                                            case 'sedang dikerjakan':
                                                                $quizStatusBadge = 'bg-warning text-dark';
                                                                break;
                                                            case 'waktu habis':
                                                                $quizStatusBadge = 'bg-danger';
                                                                break;
                                                            case 'dibatalkan':
                                                                $quizStatusBadge = 'bg-secondary';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge <?= $quizStatusBadge ?>">
                                                            <?= ucfirst($quiz['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($quiz['nilai'])): ?>
                                                            <?php if ($quiz['nilai'] >= $quiz['nilai_lulus']): ?>
                                                                <span class="badge bg-success">Lulus</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger">Tidak Lulus</span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="tab-pane fade" id="tugas-tab-pane" role="tabpanel" aria-labelledby="tugas-tab" tabindex="0">
                            <?php if (empty($tugasList)): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i> Belum ada data pengumpulan tugas.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Tugas</th>
                                                <th>Materi</th>
                                                <th>Waktu Pengumpulan</th>
                                                <th>Tenggat Waktu</th>
                                                <th>Status</th>
                                                <th>Nilai</th>
                                                <th>File Jawaban</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($tugasList as $tugas): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($tugas['judul_tugas']) ?></td>
                                                    <td><?= htmlspecialchars($tugas['judul_materi']) ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($tugas['waktu_pengumpulan'])) ?></td>
                                                    <td>
                                                        <?php if (!empty($tugas['tenggat_waktu'])): ?>
                                                            <?= date('d/m/Y H:i', strtotime($tugas['tenggat_waktu'])) ?>
                                                            <?php
                                                            $terlambat = strtotime($tugas['waktu_pengumpulan']) > strtotime($tugas['tenggat_waktu']);
                                                            if ($terlambat) {
                                                                echo ' <span class="badge bg-danger">Terlambat</span>';
                                                            }
                                                            ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Tidak ada tenggat</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $tugasStatusBadge = '';
                                                        switch ($tugas['status']) {
                                                            case 'dinilai':
                                                                $tugasStatusBadge = 'bg-success';
                                                                break;
                                                            case 'menunggu penilaian':
                                                                $tugasStatusBadge = 'bg-warning text-dark';
                                                                break;
                                                            case 'revisi':
                                                                $tugasStatusBadge = 'bg-info';
                                                                break;
                                                            case 'terlambat':
                                                                $tugasStatusBadge = 'bg-danger';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="badge <?= $tugasStatusBadge ?>">
                                                            <?= ucfirst($tugas['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?= !empty($tugas['nilai']) ? '<span class="fw-semibold">' . $tugas['nilai'] . '</span>' : '<span class="text-muted">Belum dinilai</span>' ?>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($tugas['file_jawaban'])): ?>
                                                            <a href="<?= BASE_URL ?>/uploads/tugas/<?= $tugas['file_jawaban'] ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                                                <i class="bi bi-download"></i> Unduh
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-muted">Tidak ada file</span>
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

                        <div class="tab-pane fade" id="sertifikat-tab-pane" role="tabpanel" aria-labelledby="sertifikat-tab" tabindex="0">
                            <?php if (!$sertifikat): ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i> Belum ada sertifikat yang diterbitkan untuk pendaftaran ini.
                                    <?php if ($pendaftaran['status'] === 'selesai'): ?>
                                        <div class="mt-3">
                                            <a href="<?= BASE_URL ?>/admin/sertifikat/tambah?pendaftaran_id=<?= $id ?>" class="btn btn-success">
                                                <i class="bi bi-award"></i> Terbitkan Sertifikat
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-2">
                                            <p class="mb-0">Sertifikat hanya dapat diterbitkan jika status pendaftaran "Selesai".</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card border-0 shadow-sm mb-3">
                                            <div class="card-body">
                                                <h5 class="mb-3">Informasi Sertifikat</h5>
                                                <ul class="list-group list-group-flush">
                                                    <li class="list-group-item d-flex justify-content-between px-0">
                                                        <span class="text-muted">Nomor Sertifikat:</span>
                                                        <span class="fw-semibold"><?= $sertifikat['nomor_sertifikat'] ?></span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between px-0">
                                                        <span class="text-muted">Tanggal Terbit:</span>
                                                        <span><?= date('d/m/Y', strtotime($sertifikat['tanggal_terbit'])) ?></span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between px-0">
                                                        <span class="text-muted">Tanggal Kedaluwarsa:</span>
                                                        <span><?= !empty($sertifikat['tanggal_kedaluwarsa']) ? date('d/m/Y', strtotime($sertifikat['tanggal_kedaluwarsa'])) : '<span class="text-muted">Tidak ada</span>' ?></span>
                                                    </li>
                                                    <li class="list-group-item d-flex justify-content-between px-0">
                                                        <span class="text-muted">Status:</span>
                                                        <span class="badge <?= $sertifikat['status'] === 'aktif' ? 'bg-success' : 'bg-danger' ?>"><?= ucfirst($sertifikat['status']) ?></span>
                                                    </li>
                                                </ul>

                                                <div class="mt-3">
                                                    <?php if (!empty($sertifikat['file_sertifikat'])): ?>
                                                        <a href="<?= BASE_URL ?>/uploads/sertifikat/<?= $sertifikat['file_sertifikat'] ?>" class="btn btn-primary" target="_blank">
                                                            <i class="bi bi-file-earmark-pdf"></i> Lihat Sertifikat
                                                        </a>
                                                    <?php endif; ?>

                                                    <a href="<?= BASE_URL ?>/admin/sertifikat/detail?id=<?= $sertifikat['id'] ?>" class="btn btn-outline-primary ms-2">
                                                        <i class="bi bi-gear"></i> Kelola Sertifikat
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card border-0 mb-3">
                                            <?php if (!empty($sertifikat['file_sertifikat'])): ?>
                                                <div class="text-center p-3">
                                                    <img src="<?= BASE_URL ?>/uploads/sertifikat/preview_<?= $sertifikat['file_sertifikat'] ?>" alt="Preview Sertifikat" class="img-fluid border">
                                                </div>
                                            <?php else: ?>
                                                <div class="card-body text-center text-muted">
                                                    <i class="bi bi-file-earmark-x display-4"></i>
                                                    <p class="mt-3">Preview sertifikat tidak tersedia</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-placeholder {
        font-weight: 600;
    }

    .progress-circle circle {
        transform: rotate(-90deg);
        transform-origin: 50% 50%;
        transition: stroke-dashoffset 0.5s;
    }

    .tab-content {
        min-height: 300px;
    }
</style>

<?php adminFooter(); ?>