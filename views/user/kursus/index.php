<?php
// Path: views/user/kursus/index.php

require_once BASE_PATH . '/layouts/user/user-layout.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/user/login');
    exit;
}

$db = dbConnect();
$userId = $_SESSION['user']['id'];

$status = $_GET['status'] ?? 'semua';
$statusFilter = "";

if ($status === 'aktif') {
    $statusFilter = "AND p.status = 'aktif'";
} elseif ($status === 'selesai') {
    $statusFilter = "AND p.status = 'selesai'";
}

$stmt = $db->prepare("
    SELECT p.id, p.status, p.progres_persen, p.tanggal_daftar, 
           k.id as kursus_id, k.judul, k.deskripsi, k.gambar_sampul, k.level,
           (SELECT COUNT(*) FROM materi m JOIN modul md ON m.modul_id = md.id WHERE md.kursus_id = k.id) as total_materi
    FROM pendaftaran p
    JOIN kursus k ON p.kursus_id = k.id
    WHERE p.pengguna_id = :user_id $statusFilter
    ORDER BY p.tanggal_daftar DESC
");
$stmt->execute([':user_id' => $userId]);
$kursus = $stmt->fetchAll();

$content = '
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>Kursus Saya</h3>
    <a href="' . BASE_URL . '/kursus" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Jelajahi Kursus</a>
</div>

<div class="card mb-4">
    <div class="card-body p-0">
        <ul class="nav nav-pills nav-fill px-3 py-2">
            <li class="nav-item">
                <a class="nav-link ' . ($status === 'semua' ? 'active bg-primary' : '') . '" href="' . BASE_URL . '/user/kursus">Semua</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ' . ($status === 'aktif' ? 'active bg-primary' : '') . '" href="' . BASE_URL . '/user/kursus?status=aktif">Aktif</a>
            </li>
            <li class="nav-item">
                <a class="nav-link ' . ($status === 'selesai' ? 'active bg-primary' : '') . '" href="' . BASE_URL . '/user/kursus?status=selesai">Selesai</a>
            </li>
        </ul>
    </div>
</div>';

if (count($kursus) > 0) {
    $content .= '<div class="row">';

    foreach ($kursus as $item) {
        $content .= '
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
                <div class="position-relative">';

        if ($item['gambar_sampul']) {
            $content .= '<img src="' . BASE_URL . '/uploads/kursus/' . $item['gambar_sampul'] . '" 
                     class="card-img-top" style="height: 160px; object-fit: cover;" 
                     alt="' . htmlspecialchars($item['judul']) . '">';
        } else {
            $content .= '<div class="bg-secondary d-flex align-items-center justify-content-center" 
                     style="height: 160px;">
                    <i class="bi bi-book display-4 text-white"></i>
                </div>';
        }

        $content .= '
                    <span class="position-absolute top-0 end-0 badge ' . ($item['status'] === 'aktif' ? 'bg-primary' : 'bg-success') . ' m-2">
                        ' . ucfirst($item['status']) . '
                    </span>
                </div>
                
                <div class="card-body">
                    <h5 class="card-title">' . htmlspecialchars($item['judul']) . '</h5>
                    <p class="text-muted small">
                        <i class="bi bi-bar-chart-fill"></i> ' . ucfirst($item['level']) . '
                        &bull; <i class="bi bi-book"></i> ' . $item['total_materi'] . ' materi
                    </p>
                    <div class="progress mb-3">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: ' . $item['progres_persen'] . '%;" 
                             aria-valuenow="' . $item['progres_persen'] . '" aria-valuemin="0" aria-valuemax="100">
                            ' . $item['progres_persen'] . '%
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="' . BASE_URL . '/user/kursus/detail?id=' . $item['id'] . '" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-info-circle"></i> Detail
                        </a>';

        if ($item['status'] === 'aktif') {
            $content .= '
                        <a href="' . BASE_URL . '/user/belajar?id=' . $item['id'] . '" class="btn btn-sm btn-primary">
                            <i class="bi bi-play-fill"></i> Lanjut Belajar
                        </a>';
        } else {
            $content .= '
                        <a href="' . BASE_URL . '/user/sertifikat?pendaftaran_id=' . $item['id'] . '" class="btn btn-sm btn-success">
                            <i class="bi bi-award"></i> Lihat Sertifikat
                        </a>';
        }

        $content .= '
                    </div>
                </div>
                <div class="card-footer text-muted small">
                    <i class="bi bi-calendar"></i> Terdaftar: ' . date('d M Y', strtotime($item['tanggal_daftar'])) . '
                </div>
            </div>
        </div>';
    }

    $content .= '</div>';
} else {
    $content .= '
    <div class="card">
        <div class="card-body text-center py-5">
            <div class="mb-3">
                <i class="bi bi-journal-x text-muted" style="font-size: 4rem;"></i>
            </div>
            <h4>Tidak ada kursus</h4>
            <p>Anda belum mengikuti kursus apapun</p>
            <a href="' . BASE_URL . '/kursus" class="btn btn-primary">Jelajahi Kursus</a>
        </div>
    </div>';
}

userLayout('Kursus Saya', $content, 'kursus');
