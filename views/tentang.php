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
                <img src="<?= BASE_URL ?>/assets/img/hero.png" alt="Visi Atlas LMS" class="img-fluid rounded-3 shadow-lg">
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

<?php mainFooter(); ?>