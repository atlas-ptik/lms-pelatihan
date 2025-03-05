<?php
// Path: views/home.php

require_once BASE_PATH . '/layouts/main.php';

// Ambil kursus terbaru dari database
$db = dbConnect();
$stmt = $db->prepare("
    SELECT k.id, k.judul, k.deskripsi, k.gambar_sampul, k.level, k.harga
    FROM kursus k
    WHERE k.status = 'publikasi'
    ORDER BY k.waktu_dibuat DESC
    LIMIT 8
");
$stmt->execute();
$kursus_terbaru = $stmt->fetchAll();

mainHeader("Beranda", "Atlas LMS - Platform Pelatihan Online Terbaik");
?>

<!-- Hero Section -->
<section class="py-5 bg-dark text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <h1 class="display-4 fw-bold mb-4">Tingkatkan Keterampilan Anda Bersama Atlas LMS</h1>
                <p class="lead mb-4">Platform pelatihan online terbaik dengan berbagai kursus berkualitas tinggi untuk semua level pembelajaran. Mulai perjalanan belajar Anda sekarang!</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= BASE_URL ?>/kursus" class="btn btn-primary btn-lg px-4 me-md-2">Jelajahi Kursus</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="<?= BASE_URL ?>/assets/img/hero.png" alt="Hero Image" class="img-fluid rounded-3 shadow">
            </div>
        </div>
    </div>
</section>

<!-- Statistik Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4 text-center">
            <div class="col-md-4 col-12">
                <div class="card h-100 border-0">
                    <div class="card-body">
                        <i class="bi bi-mortarboard-fill text-primary display-4"></i>
                        <h3 class="mt-3 fw-bold">100+</h3>
                        <p class="text-muted">Kursus Tersedia</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="card h-100 border-0">
                    <div class="card-body">
                        <i class="bi bi-people-fill text-primary display-4"></i>
                        <h3 class="mt-3 fw-bold">5000+</h3>
                        <p class="text-muted">Peserta Aktif</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="card h-100 border-0">
                    <div class="card-body">
                        <i class="bi bi-person-check-fill text-primary display-4"></i>
                        <h3 class="mt-3 fw-bold">50+</h3>
                        <p class="text-muted">Instruktur Berpengalaman</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Kursus Terbaru -->
<section class="py-5">
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2 class="fw-bold">Kursus Terbaru</h2>
                <p class="text-muted">Jelajahi kursus terbaru kami dan mulai belajar</p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="<?= BASE_URL ?>/kursus" class="btn btn-outline-primary">Lihat Semua Kursus</a>
            </div>
        </div>
        
        <div class="row g-4">
            <?php foreach ($kursus_terbaru as $kursus): ?>
            <div class="col-lg-3 col-md-6">
                <div class="card h-100 course-card">
                    <img src="<?= !empty($kursus['gambar_sampul']) ? BASE_URL . '/uploads/kursus/' . $kursus['gambar_sampul'] : BASE_URL . '/assets/img/course-placeholder.jpg' ?>" 
                         class="card-img-top course-thumbnail" alt="<?= htmlspecialchars($kursus['judul']) ?>">
                    <div class="badge bg-primary position-absolute top-0 end-0 m-2">
                        <?= htmlspecialchars(ucfirst($kursus['level'])) ?>
                    </div>
                    <div class="card-body p-4">
                        <h5 class="card-title fw-bold mb-3"><?= htmlspecialchars($kursus['judul']) ?></h5>
                        <p class="card-text text-muted mb-3">
                            <?= htmlspecialchars(substr($kursus['deskripsi'], 0, 80)) ?>...
                        </p>
                        <div class="d-flex justify-content-between align-items-center mt-auto">
                            <span class="fw-bold text-primary">
                                <?= $kursus['harga'] > 0 ? 'Rp ' . number_format($kursus['harga'], 0, ',', '.') : 'Gratis' ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-footer bg-white p-3">
                        <a href="<?= BASE_URL ?>/kursus/detail?id=<?= $kursus['id'] ?>" class="btn btn-primary w-100">Detail Kursus</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 bg-dark text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="fw-bold mb-4">Siap Meningkatkan Keterampilan Anda?</h2>
                <p class="lead mb-4">Bergabunglah dengan ribuan peserta yang telah berhasil belajar dengan Atlas LMS</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?= BASE_URL ?>/kursus" class="btn btn-primary btn-lg px-4">Jelajahi Kursus</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php mainFooter(); ?>