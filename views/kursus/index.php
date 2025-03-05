<?php
// Path: views/kursus/index.php

require_once BASE_PATH . '/layouts/main.php';

// Pagination setup
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$per_halaman = 8;
$offset = ($halaman - 1) * $per_halaman;

// Search query
$cari = isset($_GET['q']) ? $_GET['q'] : null;

// Database connection
$db = dbConnect();

// Count total courses
if (!empty($cari)) {
    $count_sql = "SELECT COUNT(*) as total FROM kursus WHERE status = 'publikasi' AND (judul LIKE ? OR deskripsi LIKE ?)";
    $stmt_count = $db->prepare($count_sql);
    $search_term = "%" . $cari . "%";
    $stmt_count->execute([$search_term, $search_term]);
} else {
    $count_sql = "SELECT COUNT(*) as total FROM kursus WHERE status = 'publikasi'";
    $stmt_count = $db->prepare($count_sql);
    $stmt_count->execute();
}

$total_kursus = $stmt_count->fetch()['total'];
$total_halaman = ceil($total_kursus / $per_halaman);

// Get courses with pagination
if (!empty($cari)) {
    $sql = "SELECT id, judul, deskripsi, gambar_sampul, level, harga 
            FROM kursus 
            WHERE status = 'publikasi' AND (judul LIKE ? OR deskripsi LIKE ?) 
            ORDER BY waktu_dibuat DESC 
            LIMIT ?, ?";
    $stmt = $db->prepare($sql);
    $search_term = "%" . $cari . "%";
    $stmt->bindParam(1, $search_term, PDO::PARAM_STR);
    $stmt->bindParam(2, $search_term, PDO::PARAM_STR);
    $stmt->bindParam(3, $offset, PDO::PARAM_INT);
    $stmt->bindParam(4, $per_halaman, PDO::PARAM_INT);
} else {
    $sql = "SELECT id, judul, deskripsi, gambar_sampul, level, harga 
            FROM kursus 
            WHERE status = 'publikasi' 
            ORDER BY waktu_dibuat DESC 
            LIMIT ?, ?";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(1, $offset, PDO::PARAM_INT);
    $stmt->bindParam(2, $per_halaman, PDO::PARAM_INT);
}

$stmt->execute();
$kursus_list = $stmt->fetchAll();

mainHeader("Daftar Kursus", "Jelajahi semua kursus pelatihan di Atlas LMS");
?>

<!-- Header -->
<section class="py-5 bg-dark text-white">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="fw-bold mb-4">Jelajahi Kursus Kami</h1>
                <p class="lead">Temukan berbagai kursus berkualitas tinggi untuk meningkatkan keterampilan Anda</p>

                <form action="<?= BASE_URL ?>/kursus" method="GET" class="mt-4">
                    <div class="input-group input-group-lg">
                        <input type="text" class="form-control" placeholder="Cari kursus..." name="q" value="<?= htmlspecialchars($cari ?? '') ?>">
                        <button class="btn btn-primary px-4" type="submit">
                            <i class="bi bi-search me-2"></i> Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Course List -->
<section class="py-5">
    <div class="container">
        <?php if (empty($kursus_list)): ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle me-2"></i>
                Tidak ada kursus yang ditemukan. Silakan coba kata kunci lain.
            </div>
        <?php else: ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold">Daftar Kursus</h2>
                <p class="mb-0">Menampilkan <?= count($kursus_list) ?> dari <?= $total_kursus ?> kursus</p>
            </div>

            <div class="row g-4">
                <?php foreach ($kursus_list as $kursus): ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card h-100 course-card">
                            <img src="<?= !empty($kursus['gambar_sampul']) ? BASE_URL . '/uploads/kursus/' . $kursus['gambar_sampul'] : BASE_URL . '/assets/img/course-placeholder.jpg' ?>"
                                class="card-img-top course-thumbnail" alt="<?= htmlspecialchars($kursus['judul']) ?>">
                            <div class="badge bg-primary position-absolute top-0 end-0 m-2">
                                <?= htmlspecialchars(ucfirst($kursus['level'])) ?>
                            </div>
                            <div class="card-body p-4">
                                <h5 class="card-title fw-bold mb-3"><?= htmlspecialchars($kursus['judul']) ?></h5>
                                <p class="card-text text-muted mb-3">
                                    <?= htmlspecialchars(substr($kursus['deskripsi'], 0, 100)) ?>...
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

            <!-- Pagination -->
            <?php if ($total_halaman > 1): ?>
                <nav class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php if ($halaman > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= BASE_URL ?>/kursus?halaman=<?= $halaman - 1 ?><?= $cari ? '&q=' . urlencode($cari) : '' ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $halaman - 2);
                        $end_page = min($total_halaman, $halaman + 2);

                        for ($i = $start_page; $i <= $end_page; $i++) {
                            echo '<li class="page-item ' . ($i == $halaman ? 'active' : '') . '">
                            <a class="page-link" href="' . BASE_URL . '/kursus?halaman=' . $i .
                                ($cari ? '&q=' . urlencode($cari) : '') . '">' . $i . '</a>
                        </li>';
                        }
                        ?>

                        <?php if ($halaman < $total_halaman): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= BASE_URL ?>/kursus?halaman=<?= $halaman + 1 ?><?= $cari ? '&q=' . urlencode($cari) : '' ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php mainFooter(); ?>