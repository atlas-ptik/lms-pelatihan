<?php
// Path: views/user/kursus/daftar.php

require_once BASE_PATH . '/layouts/user/user-layout.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/user/login');
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . BASE_URL . '/kursus');
    exit;
}

$db = dbConnect();
$kursusId = $_GET['id'];
$userId = $_SESSION['user']['id'];

$kursusStmt = $db->prepare("
    SELECT k.*, u.nama_lengkap as nama_pembuat, u.foto_profil as foto_pembuat,
           (SELECT COUNT(*) FROM modul m WHERE m.kursus_id = k.id) as total_modul,
           (SELECT COUNT(*) FROM materi mt JOIN modul md ON mt.modul_id = md.id WHERE md.kursus_id = k.id) as total_materi
    FROM kursus k
    JOIN pengguna u ON k.pembuat_id = u.id
    WHERE k.id = :id AND k.status = 'publikasi'
");
$kursusStmt->execute([':id' => $kursusId]);
$kursus = $kursusStmt->fetch();

if (!$kursus) {
    header('Location: ' . BASE_URL . '/kursus');
    exit;
}

$cekPendaftaranStmt = $db->prepare("
    SELECT id FROM pendaftaran 
    WHERE pengguna_id = :user_id AND kursus_id = :kursus_id
");
$cekPendaftaranStmt->execute([':user_id' => $userId, ':kursus_id' => $kursusId]);
$sudahTerdaftar = $cekPendaftaranStmt->fetch();

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$sudahTerdaftar) {
    try {
        $pendaftaranId = generate_uuid();

        $insertStmt = $db->prepare("
            INSERT INTO pendaftaran (id, pengguna_id, kursus_id, tanggal_daftar, status, progres_persen)
            VALUES (:id, :pengguna_id, :kursus_id, NOW(), 'aktif', 0)
        ");
        $insertStmt->execute([
            ':id' => $pendaftaranId,
            ':pengguna_id' => $userId,
            ':kursus_id' => $kursusId
        ]);

        $success = true;
    } catch (PDOException $e) {
        $error = 'Gagal mendaftar kursus: ' . $e->getMessage();
    }
}

$content = '
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>Pendaftaran Kursus</h3>
    <a href="' . BASE_URL . '/kursus/detail?id=' . $kursus['id'] . '" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>';

if ($success) {
    $content .= '
<div class="card">
    <div class="card-body text-center py-4">
        <div class="mb-3">
            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
        </div>
        <h4 class="mb-3">Pendaftaran Berhasil!</h4>
        <p>Anda telah berhasil mendaftar ke kursus:<br><strong>' . htmlspecialchars($kursus['judul']) . '</strong></p>
        <div class="d-grid gap-2 d-md-flex justify-content-center mt-4">
            <a href="' . BASE_URL . '/user/kursus" class="btn btn-outline-primary">
                <i class="bi bi-journal-text"></i> Lihat Kursus Saya
            </a>
            <a href="' . BASE_URL . '/user/belajar?kursus_id=' . $kursus['id'] . '" class="btn btn-primary">
                <i class="bi bi-play-fill"></i> Mulai Belajar
            </a>
        </div>
    </div>
</div>';
} elseif ($sudahTerdaftar) {
    $content .= '
<div class="card">
    <div class="card-body text-center py-4">
        <div class="mb-3">
            <i class="bi bi-info-circle-fill text-primary" style="font-size: 4rem;"></i>
        </div>
        <h4 class="mb-3">Anda Sudah Terdaftar</h4>
        <p>Anda sudah terdaftar di kursus:<br><strong>' . htmlspecialchars($kursus['judul']) . '</strong></p>
        <div class="d-grid gap-2 d-md-flex justify-content-center mt-4">
            <a href="' . BASE_URL . '/user/kursus" class="btn btn-outline-primary">
                <i class="bi bi-journal-text"></i> Lihat Kursus Saya
            </a>
            <a href="' . BASE_URL . '/user/belajar?kursus_id=' . $kursus['id'] . '" class="btn btn-primary">
                <i class="bi bi-play-fill"></i> Lanjut Belajar
            </a>
        </div>
    </div>
</div>';
} elseif ($error) {
    $content .= '
<div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $error . '
</div>';
} else {
    $content .= '
<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="mb-3">';

    if ($kursus['gambar_sampul']) {
        $content .= '<img src="' . BASE_URL . '/assets/img/kursus/' . $kursus['gambar_sampul'] . '" 
                     class="img-fluid rounded" alt="' . htmlspecialchars($kursus['judul']) . '">';
    } else {
        $content .= '<div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                        <i class="bi bi-book display-3 text-white"></i>
                    </div>';
    }

    $content .= '
                </div>
            </div>
            
            <div class="col-md-8">
                <h4 class="mb-3">' . htmlspecialchars($kursus['judul']) . '</h4>
                
                <div class="mb-3 d-flex align-items-center">';

    if ($kursus['foto_pembuat']) {
        $content .= '<img src="' . BASE_URL . '/uploads/profil/' . $kursus['foto_pembuat'] . '" 
                     class="rounded-circle me-2" alt="Foto Instruktur" width="32" height="32" 
                     style="object-fit: cover;">';
    } else {
        $content .= '<div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-2" 
                     style="width: 32px; height: 32px;">
                    <span class="text-white small">
                        ' . strtoupper(substr($kursus['nama_pembuat'], 0, 1)) . '
                    </span>
                </div>';
    }

    $content .= '
                    <span>Instruktur: ' . htmlspecialchars($kursus['nama_pembuat']) . '</span>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-6 mb-2">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-bar-chart-fill me-2 text-primary"></i>
                            <span>Level: ' . ucfirst($kursus['level']) . '</span>
                        </div>
                    </div>
                    <div class="col-sm-6 mb-2">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-clock me-2 text-primary"></i>
                            <span>Durasi: ' . ($kursus['durasi_menit'] ? floor($kursus['durasi_menit'] / 60) . ' jam ' . ($kursus['durasi_menit'] % 60) . ' menit' : 'Tidak ditentukan') . '</span>
                        </div>
                    </div>
                    <div class="col-sm-6 mb-2">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-grid-3x3-gap me-2 text-primary"></i>
                            <span>Modul: ' . $kursus['total_modul'] . '</span>
                        </div>
                    </div>
                    <div class="col-sm-6 mb-2">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-book me-2 text-primary"></i>
                            <span>Materi: ' . $kursus['total_materi'] . '</span>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <h5>Deskripsi Kursus</h5>
                        <p>' . nl2br(htmlspecialchars($kursus['deskripsi'])) . '</p>
                    </div>
                </div>
                
                <form method="post" action="">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5>Konfirmasi Pendaftaran</h5>
                            <p>Anda akan mendaftar ke kursus "<strong>' . htmlspecialchars($kursus['judul']) . '</strong>".</p>';

    if ($kursus['harga'] > 0) {
        $content .= '
                            <div class="alert alert-info">
                                <p class="mb-0">
                                    <strong>Kursus Berbayar</strong><br>
                                    Harga: Rp ' . number_format($kursus['harga'], 0, ',', '.') . '
                                </p>
                            </div>';
    } else {
        $content .= '
                            <div class="alert alert-success">
                                <p class="mb-0">
                                    <strong>Kursus Gratis</strong><br>
                                    Anda dapat mengakses kursus ini tanpa biaya.
                                </p>
                            </div>';
    }

    $content .= '
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="' . BASE_URL . '/kursus/detail?id=' . $kursus['id'] . '" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">Daftar Kursus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>';
}

userLayout('Daftar Kursus', $content, 'kursus');
