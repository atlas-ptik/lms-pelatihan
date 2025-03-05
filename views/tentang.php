<?php
// Path: views/tentang.php

require_once BASE_PATH . '/layouts/main.php';

mainHeader("Tentang Kami", "Tentang Atlas LMS - Platform Pelatihan Online");
?>

<!-- Header -->
<section class="py-5 bg-dark text-white">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="fw-bold mb-4">Tentang Atlas LMS</h1>
                <p class="lead">Platform pelatihan online yang dirancang untuk memberikan akses ke pendidikan berkualitas bagi semua orang</p>
            </div>
        </div>
    </div>
</section>

<!-- Visi Misi -->
<section class="py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <img src="<?= BASE_URL ?>/assets/img/about-vision.jpg" alt="Visi Atlas LMS" class="img-fluid rounded-3 shadow-lg">
            </div>
            <div class="col-lg-6">
                <h2 class="fw-bold mb-4">Visi & Misi Kami</h2>
                <div class="mb-4">
                    <h4 class="fw-bold text-primary">Visi</h4>
                    <p>Menjadi platform pembelajaran online terkemuka yang memberikan akses pendidikan berkualitas tinggi untuk semua orang, di mana pun mereka berada.</p>
                </div>
                <div>
                    <h4 class="fw-bold text-primary">Misi</h4>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i> Menyediakan konten pembelajaran berkualitas tinggi dari para ahli terbaik di bidangnya.</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i> Membangun platform pembelajaran yang mudah diakses dan ramah pengguna.</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i> Menciptakan komunitas belajar yang inklusif dan saling mendukung.</li>
                        <li class="mb-2"><i class="bi bi-check-circle-fill text-primary me-2"></i> Terus berinovasi dalam metode pembelajaran digital untuk meningkatkan efektivitas belajar.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tim Kami -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row mb-5 text-center">
            <div class="col-lg-8 mx-auto">
                <h2 class="fw-bold mb-4">Tim Kami</h2>
                <p>Kenali orang-orang hebat di balik Atlas LMS</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body p-4">
                        <div class="rounded-circle overflow-hidden mb-3 mx-auto" style="width: 120px; height: 120px;">
                            <img src="<?= BASE_URL ?>/assets/img/team-1.jpg" alt="CEO" class="img-fluid w-100 h-100 object-fit-cover">
                        </div>
                        <h5 class="fw-bold mb-1">Budi Santoso</h5>
                        <p class="text-primary mb-3">CEO & Co-Founder</p>
                        <p class="text-muted small">Pendidik dengan pengalaman lebih dari 15 tahun di bidang teknologi pendidikan.</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body p-4">
                        <div class="rounded-circle overflow-hidden mb-3 mx-auto" style="width: 120px; height: 120px;">
                            <img src="<?= BASE_URL ?>/assets/img/team-2.jpg" alt="CTO" class="img-fluid w-100 h-100 object-fit-cover">
                        </div>
                        <h5 class="fw-bold mb-1">Siti Rahma</h5>
                        <p class="text-primary mb-3">CTO & Co-Founder</p>
                        <p class="text-muted small">Insinyur perangkat lunak dengan keahlian dalam pengembangan platform pembelajaran.</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body p-4">
                        <div class="rounded-circle overflow-hidden mb-3 mx-auto" style="width: 120px; height: 120px;">
                            <img src="<?= BASE_URL ?>/assets/img/team-3.jpg" alt="CPO" class="img-fluid w-100 h-100 object-fit-cover">
                        </div>
                        <h5 class="fw-bold mb-1">Ahmad Hadi</h5>
                        <p class="text-primary mb-3">Chief Product Officer</p>
                        <p class="text-muted small">Ahli desain produk dengan fokus pada pengalaman pengguna dan desain pendidikan.</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="card border-0 shadow-sm text-center h-100">
                    <div class="card-body p-4">
                        <div class="rounded-circle overflow-hidden mb-3 mx-auto" style="width: 120px; height: 120px;">
                            <img src="<?= BASE_URL ?>/assets/img/team-4.jpg" alt="COO" class="img-fluid w-100 h-100 object-fit-cover">
                        </div>
                        <h5 class="fw-bold mb-1">Maya Wijaya</h5>
                        <p class="text-primary mb-3">Chief Operations Officer</p>
                        <p class="text-muted small">Profesional operasional yang berpengalaman dalam mengelola pertumbuhan startup.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php mainFooter(); ?>