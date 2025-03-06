<?php
// Path: views/user/sertifikat/index.php

require_once BASE_PATH . '/layouts/user/user-layout.php';

$userId = $_SESSION['user']['id'];
$db = dbConnect();

// Ambil daftar sertifikat pengguna
$query = "SELECT s.*, p.tanggal_daftar, k.judul as judul_kursus, k.gambar_sampul 
          FROM sertifikat s
          JOIN pendaftaran p ON s.pendaftaran_id = p.id
          JOIN kursus k ON p.kursus_id = k.id
          WHERE p.pengguna_id = :user_id AND s.status = 'aktif'
          ORDER BY s.tanggal_terbit DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$sertifikat = $stmt->fetchAll();

$content = function() use ($sertifikat) {
    $baseUrl = BASE_URL;
?>
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">Sertifikat Saya</h2>

            <?php if (isset($_SESSION['alert'])): ?>
                <div class="alert alert-<?= $_SESSION['alert']['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['alert']['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['alert']); ?>
            <?php endif; ?>

            <?php if (empty($sertifikat)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-award" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="mt-3">Anda belum memiliki sertifikat. Selesaikan kursus untuk mendapatkan sertifikat.</p>
                    <a href="<?= $baseUrl ?>/user/kursus" class="btn btn-primary mt-2">Lihat Kursus Saya</a>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php foreach($sertifikat as $item): ?>
                        <div class="col">
                            <div class="card h-100">
                                <?php if (!empty($item['gambar_sampul'])): ?>
                                    <img src="<?= $baseUrl ?>/uploads/kursus/<?= $item['gambar_sampul'] ?>" 
                                        class="card-img-top" alt="<?= htmlspecialchars($item['judul_kursus']) ?>"
                                        style="height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-secondary text-white d-flex justify-content-center align-items-center" 
                                        style="height: 150px;">
                                        <i class="bi bi-image" style="font-size: 3rem;"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title text-truncate"><?= htmlspecialchars($item['judul_kursus']) ?></h5>
                                    <div class="mb-2">
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Selesai</span>
                                    </div>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <i class="bi bi-calendar-check"></i> Diterbitkan: <?= date('d M Y', strtotime($item['tanggal_terbit'])) ?>
                                        </small>
                                    </p>
                                    <p class="card-text">
                                        <small class="text-muted">
                                            <i class="bi bi-upc"></i> No. Sertifikat: <?= htmlspecialchars($item['nomor_sertifikat']) ?>
                                        </small>
                                    </p>
                                </div>
                                <div class="card-footer bg-white border-0 d-flex justify-content-between">
                                    <a href="<?= $baseUrl ?>/user/sertifikat/detail?id=<?= $item['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-eye"></i> Lihat
                                    </a>
                                    <?php if (!empty($item['file_sertifikat'])): ?>
                                        <a href="<?= $baseUrl ?>/uploads/sertifikat/<?= $item['file_sertifikat'] ?>" 
                                           class="btn btn-sm btn-outline-primary" download>
                                            <i class="bi bi-download"></i> Unduh
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php
};

userLayout("Sertifikat Saya", $content(), "sertifikat");
?>