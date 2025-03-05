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

$content = function () use ($kursus, $sudahTerdaftar, $success, $error) {
    $baseUrl = BASE_URL;
?>
    <div class="card">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Pendaftaran Kursus</h5>
            <a href="<?= $baseUrl ?>/kursus/detail?id=<?= $kursus['id'] ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <h4 class="alert-heading"><i class="bi bi-check-circle-fill"></i> Selamat!</h4>
                    <p>Anda berhasil mendaftar ke kursus "<strong><?= htmlspecialchars($kursus['judul']) ?></strong>".</p>
                    <hr>
                    <div class="d-grid gap-2 d-md-flex">
                        <a href="<?= $baseUrl ?>/user/kursus" class="btn btn-outline-success">
                            <i class="bi bi-journal-text"></i> Lihat Kursus Saya
                        </a>
                        <a href="<?= $baseUrl ?>/user/belajar?kursus_id=<?= $kursus['id'] ?>" class="btn btn-success">
                            <i class="bi bi-play-fill"></i> Mulai Belajar
                        </a>
                    </div>
                </div>
            <?php elseif ($sudahTerdaftar): ?>
                <div class="alert alert-info">
                    <h4 class="alert-heading"><i class="bi bi-info-circle-fill"></i> Anda Sudah Terdaftar</h4>
                    <p>Anda sudah terdaftar di kursus "<strong><?= htmlspecialchars($kursus['judul']) ?></strong>".</p>
                    <hr>
                    <div class="d-grid gap-2 d-md-flex">
                        <a href="<?= $baseUrl ?>/user/kursus" class="btn btn-outline-primary">
                            <i class="bi bi-journal-text"></i> Lihat Kursus Saya
                        </a>
                        <a href="<?= $baseUrl ?>/user/belajar?kursus_id=<?= $kursus['id'] ?>" class="btn btn-primary">
                            <i class="bi bi-play-fill"></i> Lanjut Belajar
                        </a>
                    </div>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-danger">
                    <?= $error ?>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-4 mb-4 mb-md-0">
                        <?php if ($kursus['gambar_sampul']): ?>
                            <img src="<?= $baseUrl ?>/assets/img/kursus/<?= $kursus['gambar_sampul'] ?>"
                                class="img-fluid rounded mb-3" alt="<?= htmlspecialchars($kursus['judul']) ?>">
                        <?php else: ?>
                            <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-book display-3 text-white"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-8">
                        <h3 class="mb-3"><?= htmlspecialchars($kursus['judul']) ?></h3>

                        <div class="d-flex align-items-center mb-3">
                            <?php if ($kursus['foto_pembuat']): ?>
                                <img src="<?= $baseUrl ?>/assets/img/profile/<?= $kursus['foto_pembuat'] ?>"
                                    class="rounded-circle me-2" alt="Foto Instruktur" width="32" height="32"
                                    style="object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-2"
                                    style="width: 32px; height: 32px;">
                                    <span class="text-white small">
                                        <?= strtoupper(substr($kursus['nama_pembuat'], 0, 1)) ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <span>Instruktur: <?= htmlspecialchars($kursus['nama_pembuat']) ?></span>
                        </div>

                        <div class="row mb-3">
                            <div class="col-sm-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-bar-chart-fill me-2 text-primary"></i>
                                    <span>Level: <?= ucfirst($kursus['level']) ?></span>
                                </div>
                            </div>
                            <div class="col-sm-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-clock me-2 text-primary"></i>
                                    <span>Durasi: <?= $kursus['durasi_menit'] ? floor($kursus['durasi_menit'] / 60) . ' jam ' . ($kursus['durasi_menit'] % 60) . ' menit' : 'Tidak ditentukan' ?></span>
                                </div>
                            </div>
                            <div class="col-sm-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-grid-3x3-gap me-2 text-primary"></i>
                                    <span>Modul: <?= $kursus['total_modul'] ?></span>
                                </div>
                            </div>
                            <div class="col-sm-6 mb-2">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-book me-2 text-primary"></i>
                                    <span>Materi: <?= $kursus['total_materi'] ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-body">
                                <h5>Deskripsi Kursus</h5>
                                <p><?= nl2br(htmlspecialchars($kursus['deskripsi'])) ?></p>
                            </div>
                        </div>

                        <form method="post" action="">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5>Konfirmasi Pendaftaran</h5>
                                    <p>Anda akan mendaftar ke kursus "<strong><?= htmlspecialchars($kursus['judul']) ?></strong>".</p>

                                    <?php if ($kursus['harga'] > 0): ?>
                                        <div class="alert alert-info">
                                            <p class="mb-0">
                                                <strong>Kursus Berbayar</strong><br>
                                                Harga: Rp <?= number_format($kursus['harga'], 0, ',', '.') ?>
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-success">
                                            <p class="mb-0">
                                                <strong>Kursus Gratis</strong><br>
                                                Anda dapat mengakses kursus ini tanpa biaya.
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?= $baseUrl ?>/kursus/detail?id=<?= $kursus['id'] ?>" class="btn btn-outline-secondary">Batal</a>
                                <button type="submit" class="btn btn-primary">Daftar Kursus</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php
};

userLayout('Daftar Kursus', $content(), 'kursus');
