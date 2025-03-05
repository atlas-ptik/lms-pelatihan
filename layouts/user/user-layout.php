<?php
// Path: layouts/user/user-layout.php

require_once BASE_PATH . '/layouts/parent.php';

function userLayout($title, $content, $activeMenu = '')
{
    $baseUrl = BASE_URL;

    startHTML($title, "Atlas LMS - Sistem Manajemen Pembelajaran");
?>

    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?= $baseUrl ?>">
                <img src="<?= $baseUrl ?>/assets/img/logo.png" alt="Atlas LMS" height="40">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeMenu == 'dashboard') ? 'active' : '' ?>" href="<?= $baseUrl ?>/user/dashboard">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeMenu == 'kursus') ? 'active' : '' ?>" href="<?= $baseUrl ?>/user/kursus">
                            <i class="bi bi-book"></i> Kursus Saya
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($activeMenu == 'profil') ? 'active' : '' ?>" href="<?= $baseUrl ?>/user/profil">
                            <i class="bi bi-person"></i> Profil
                        </a>
                    </li>
                </ul>
                <div class="d-flex">
                    <div class="dropdown">
                        <a class="btn btn-outline-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['user']['nama_lengkap'] ?? 'Pengguna') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= $baseUrl ?>/user/profil"><i class="bi bi-person"></i> Profil</a></li>
                            <li><a class="dropdown-item" href="<?= $baseUrl ?>/user/profil/password"><i class="bi bi-key"></i> Ubah Password</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="<?= $baseUrl ?>/logout"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <?= $content ?>
    </main>

    <footer class="footer mt-auto">
        <div class="container py-3">
            <div class="row">
                <div class="col-md-6">
                    <h5>Atlas LMS</h5>
                    <p>Sistem Manajemen Pembelajaran berbasis PHP</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p>&copy; <?= date('Y') ?> Atlas Team. All rights reserved.</p>
                </div>
            </div>
        </div>
    </footer>
<?php
    endHTML();
}
