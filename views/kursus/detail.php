<?php
// Path: views/kursus/detail.php

require_once BASE_PATH . '/layouts/main.php';

// Get course ID
$kursus_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$kursus_id) {
    header('Location: ' . BASE_URL . '/kursus');
    exit;
}

$db = dbConnect();

// Get course details
$stmt = $db->prepare("
    SELECT k.*, p.nama_lengkap as nama_pembuat
    FROM kursus k
    LEFT JOIN pengguna p ON k.pembuat_id = p.id
    WHERE k.id = :id AND k.status = 'publikasi'
");
$stmt->bindParam(':id', $kursus_id);
$stmt->execute();
$kursus = $stmt->fetch();

if (!$kursus) {
    header('Location: ' . BASE_URL . '/404');
    exit;
}

// Get modules and count materials
$stmt = $db->prepare("
    SELECT m.id, m.judul, m.deskripsi, m.urutan,
           COUNT(mt.id) as jumlah_materi
    FROM modul m
    LEFT JOIN materi mt ON m.id = mt.modul_id
    WHERE m.kursus_id = :kursus_id
    GROUP BY m.id
    ORDER BY m.urutan
");
$stmt->bindParam(':kursus_id', $kursus_id);
$stmt->execute();
$modul_list = $stmt->fetchAll();

// Count total materials
$stmt = $db->prepare("
    SELECT COUNT(*) as total_materi,
           SUM(durasi_menit) as total_durasi
    FROM materi
    WHERE modul_id IN (SELECT id FROM modul WHERE kursus_id = :kursus_id)
");
$stmt->bindParam(':kursus_id', $kursus_id);
$stmt->execute();
$stats = $stmt->fetch();

mainHeader($kursus['judul'], substr(strip_tags($kursus['deskripsi']), 0, 160));
?>

<!-- Course Header -->
<section class="py-5 bg-dark text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/" class="text-white-50">Beranda</a></li>
                        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/kursus" class="text-white-50">Kursus</a></li>
                        <li class="breadcrumb-item active text-white" aria-current="page"><?= htmlspecialchars($kursus['judul']) ?></li>
                    </ol>
                </nav>

                <h1 class="fw-bold mb-3"><?= htmlspecialchars($kursus['judul']) ?></h1>

                <div class="d-flex flex-wrap gap-4 text-white-50 mb-4">
                    <div>
                        <i class="bi bi-person-fill me-2"></i> Oleh: <?= htmlspecialchars($kursus['nama_pembuat']) ?>
                    </div>
                    <div>
                        <i class="bi bi-clock-fill me-2"></i> <?= $stats['total_durasi'] ?? 0 ?> menit
                    </div>
                    <div>
                        <i class="bi bi-calendar-fill me-2"></i> Terakhir diperbarui: <?= date('d M Y', strtotime($kursus['waktu_diperbarui'])) ?>
                    </div>
                    <div>
                        <i class="bi bi-tag-fill me-2"></i> Level: <?= ucfirst($kursus['level']) ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow">
                    <?php if (!empty($kursus['gambar_sampul'])): ?>
                        <img src="<?= BASE_URL ?>/uploads/kursus/<?= $kursus['gambar_sampul'] ?>" alt="<?= htmlspecialchars($kursus['judul']) ?>" class="card-img-top">
                    <?php else: ?>
                        <img src="<?= BASE_URL ?>/assets/img/course-placeholder.jpg" alt="<?= htmlspecialchars($kursus['judul']) ?>" class="card-img-top">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Course Info -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Left Column: Course Description -->
            <div class="col-lg-8 mb-5 mb-lg-0">
                <!-- Course Tabs -->
                <ul class="nav nav-tabs mb-4" id="courseTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="overview-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Ringkasan</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="curriculum-tab" data-bs-toggle="tab" href="#curriculum" role="tab" aria-controls="curriculum" aria-selected="false">Kurikulum</a>
                    </li>
                </ul>

                <div class="tab-content" id="courseTabContent">
                    <!-- Overview Tab -->
                    <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h4 class="mb-0 fw-bold">Tentang Kursus</h4>
                            </div>
                            <div class="card-body">
                                <div class="course-description">
                                    <?= nl2br(htmlspecialchars($kursus['deskripsi'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Curriculum Tab -->
                    <div class="tab-pane fade" id="curriculum" role="tabpanel" aria-labelledby="curriculum-tab">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <h4 class="mb-0 fw-bold">Kurikulum Kursus</h4>
                            </div>
                            <div class="card-body p-0">
                                <div class="accordion" id="accordionCurriculum">
                                    <?php if (empty($modul_list)): ?>
                                        <div class="p-4 text-center">
                                            <p class="mb-0">Kurikulum belum tersedia.</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($modul_list as $index => $modul): ?>
                                            <div class="accordion-item border-0 border-bottom">
                                                <h2 class="accordion-header" id="heading<?= $index ?>">
                                                    <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button"
                                                        data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>"
                                                        aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                                                        aria-controls="collapse<?= $index ?>">
                                                        <div class="w-100 d-flex justify-content-between align-items-center">
                                                            <span class="fw-bold"><?= htmlspecialchars($modul['judul']) ?></span>
                                                            <span class="badge bg-light text-dark ms-2"><?= $modul['jumlah_materi'] ?> materi</span>
                                                        </div>
                                                    </button>
                                                </h2>
                                                <div id="collapse<?= $index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
                                                    aria-labelledby="heading<?= $index ?>" data-bs-parent="#accordionCurriculum">
                                                    <div class="accordion-body bg-light">
                                                        <?php if (!empty($modul['deskripsi'])): ?>
                                                            <p class="mb-3"><?= nl2br(htmlspecialchars($modul['deskripsi'])) ?></p>
                                                        <?php endif; ?>

                                                        <?php
                                                        // Get materials for this module
                                                        $stmt = $db->prepare("
                                                            SELECT id, judul, tipe, durasi_menit, urutan
                                                            FROM materi
                                                            WHERE modul_id = :modul_id
                                                            ORDER BY urutan
                                                        ");
                                                        $stmt->bindParam(':modul_id', $modul['id']);
                                                        $stmt->execute();
                                                        $materi_list = $stmt->fetchAll();

                                                        if (!empty($materi_list)):
                                                        ?>
                                                            <ul class="list-group">
                                                                <?php foreach ($materi_list as $materi): ?>
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center border-0 mb-2 rounded">
                                                                        <div class="d-flex align-items-center">
                                                                            <?php
                                                                            $icon = 'bi-file-text';
                                                                            switch ($materi['tipe']) {
                                                                                case 'video':
                                                                                    $icon = 'bi-play-circle';
                                                                                    break;
                                                                                case 'quiz':
                                                                                    $icon = 'bi-question-circle';
                                                                                    break;
                                                                                case 'tugas':
                                                                                    $icon = 'bi-clipboard-check';
                                                                                    break;
                                                                                case 'artikel':
                                                                                    $icon = 'bi-file-richtext';
                                                                                    break;
                                                                                case 'dokumen':
                                                                                    $icon = 'bi-file-pdf';
                                                                                    break;
                                                                            }
                                                                            ?>
                                                                            <i class="bi <?= $icon ?> me-3 text-primary"></i>
                                                                            <span><?= htmlspecialchars($materi['judul']) ?></span>

                                                                            <?php if ($materi['tipe'] == 'quiz'): ?>
                                                                                <span class="badge bg-primary ms-2">Quiz</span>
                                                                            <?php elseif ($materi['tipe'] == 'tugas'): ?>
                                                                                <span class="badge bg-success ms-2">Tugas</span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <?php if ($materi['durasi_menit']): ?>
                                                                            <span class="text-muted small"><?= $materi['durasi_menit'] ?> menit</span>
                                                                        <?php endif; ?>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        <?php else: ?>
                                                            <p class="text-center my-3">Belum ada materi dalam modul ini.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Enroll Card -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm position-sticky" style="top: 20px;">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-4 text-center">
                            <?php if ($kursus['harga'] > 0): ?>
                                Rp <?= number_format($kursus['harga'], 0, ',', '.') ?>
                            <?php else: ?>
                                Gratis
                            <?php endif; ?>
                        </h4>

                        <a href="<?= BASE_URL ?>/admin/login" class="btn btn-primary btn-lg w-100 mb-3">
                            <i class="bi bi-journal-plus me-2"></i> Daftar Kursus
                        </a>

                        <hr>

                        <h5 class="fw-bold mb-3">Kursus ini mencakup:</h5>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-clock-fill text-primary me-2"></i> <?= $stats['total_durasi'] ?? 0 ?> menit durasi total
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-file-text-fill text-primary me-2"></i> <?= $stats['total_materi'] ?? 0 ?> materi pembelajaran
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-device-hdd text-primary me-2"></i> Akses dari perangkat apa saja
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-chat-left-text-fill text-primary me-2"></i> Forum diskusi
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-award-fill text-primary me-2"></i> Sertifikat penyelesaian
                            </li>
                            <li>
                                <i class="bi bi-infinity text-primary me-2"></i> Akses seumur hidup
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php mainFooter(); ?>