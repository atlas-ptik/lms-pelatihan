<?php
// Path: views/user/dashboard.php

require_once BASE_PATH . '/layouts/user/user-layout.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/user/login');
    exit;
}

$db = dbConnect();

$userId = $_SESSION['user']['id'];

$pendaftaranStmt = $db->prepare("SELECT COUNT(*) as total FROM pendaftaran WHERE pengguna_id = :user_id");
$pendaftaranStmt->execute([':user_id' => $userId]);
$totalKursus = $pendaftaranStmt->fetch()['total'];

$certificateStmt = $db->prepare("
    SELECT COUNT(*) as total 
    FROM sertifikat s
    JOIN pendaftaran p ON s.pendaftaran_id = p.id
    WHERE p.pengguna_id = :user_id AND s.status = 'aktif'
");
$certificateStmt->execute([':user_id' => $userId]);
$totalSertifikat = $certificateStmt->fetch()['total'];

$progresStmt = $db->prepare("
    SELECT k.judul, p.progres_persen, p.id as pendaftaran_id, p.status
    FROM pendaftaran p
    JOIN kursus k ON p.kursus_id = k.id
    WHERE p.pengguna_id = :user_id AND p.status = 'aktif'
    ORDER BY p.waktu_diperbarui DESC
    LIMIT 5
");
$progresStmt->execute([':user_id' => $userId]);
$progresKursus = $progresStmt->fetchAll();

$content = '
<div class="card mb-4">
    <div class="card-body">
        <h3>Selamat Datang, ' . htmlspecialchars($_SESSION['user']['nama_lengkap']) . '!</h3>
        <p>Lanjutkan belajar atau temukan kursus baru di Atlas LMS.</p>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <div class="mb-3">
                    <i class="bi bi-book text-primary" style="font-size: 3rem;"></i>
                </div>
                <h3>' . $totalKursus . '</h3>
                <p>Kursus Diikuti</p>
                <a href="' . BASE_URL . '/user/kursus" class="btn btn-outline-primary">Lihat Kursus</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <div class="mb-3">
                    <i class="bi bi-award text-primary" style="font-size: 3rem;"></i>
                </div>
                <h3>' . $totalSertifikat . '</h3>
                <p>Sertifikat</p>
                <a href="' . BASE_URL . '/user/sertifikat" class="btn btn-outline-primary">Lihat Sertifikat</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 mb-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <div class="mb-3">
                    <i class="bi bi-person-circle text-primary" style="font-size: 3rem;"></i>
                </div>
                <h3>' . htmlspecialchars($_SESSION['user']['username']) . '</h3>
                <p>Profil Pengguna</p>
                <a href="' . BASE_URL . '/user/profil/edit" class="btn btn-outline-primary">Edit Profil</a>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center bg-white">
        <h5 class="mb-0">Kursus Aktif</h5>
        <a href="' . BASE_URL . '/user/kursus" class="btn btn-outline-primary btn-sm">Lihat Semua</a>
    </div>
    <div class="card-body">';

if (count($progresKursus) > 0) {
    foreach ($progresKursus as $kursus) {
        $content .= '
        <div class="mb-3">
            <h6>' . htmlspecialchars($kursus['judul']) . '</h6>
            <div class="progress mb-2" style="height: 10px;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: ' . $kursus['progres_persen'] . '%;" 
                     aria-valuenow="' . $kursus['progres_persen'] . '" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted small">' . $kursus['progres_persen'] . '% selesai</span>
                <a href="' . BASE_URL . '/user/belajar?id=' . $kursus['pendaftaran_id'] . '" class="btn btn-sm btn-primary">
                    <i class="bi bi-play-fill"></i> Lanjut Belajar
                </a>
            </div>
        </div>';
    }
} else {
    $content .= '
    <div class="text-center py-4">
        <p>Anda belum mengikuti kursus apapun</p>
        <a href="' . BASE_URL . '/kursus" class="btn btn-primary">Jelajahi Kursus</a>
    </div>';
}

$content .= '
    </div>
</div>';

userLayout('Dashboard', $content, 'dashboard');
