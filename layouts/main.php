<?php
// Path: layouts/main.php

require_once BASE_PATH . '/layouts/parent.php';

function mainHeader($title = "Atlas LMS", $description = "Sistem Manajemen Pembelajaran Atlas")
{
    startHTML($title, $description);
    $baseUrl = BASE_URL;
    $loginUrl = $baseUrl . '/user/login';
    $homeUrl = $baseUrl . '/';
    $kursusUrl = $baseUrl . '/kursus';
    $tentangUrl = $baseUrl . '/tentang';

    // Header dengan heredoc yang benar
    echo <<<HTML
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="{$homeUrl}">
                <img src="{$baseUrl}/assets/img/logo.png" alt="Atlas LMS" height="40">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="{$homeUrl}">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{$kursusUrl}">Kursus</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{$tentangUrl}">Tentang</a>
                    </li>
                </ul>
                <div class="d-flex">
HTML;

    // Tampilkan menu dropdown jika user sudah login
    if (isset($_SESSION['user'])) {
        echo <<<HTML
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i> {$_SESSION['user']['nama_lengkap']}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="{$baseUrl}/user/dashboard"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
                            <li><a class="dropdown-item" href="{$baseUrl}/user/profil"><i class="bi bi-person me-2"></i> Profil</a></li>
                            <li><a class="dropdown-item" href="{$baseUrl}/user/kursus"><i class="bi bi-journal-text me-2"></i> Kursus Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{$baseUrl}/logout"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </div>
HTML;
    } else {
        echo <<<HTML
                    <a href="{$loginUrl}" class="btn btn-primary">Masuk</a>
HTML;
    }

    echo <<<HTML
                </div>
            </div>
        </div>
    </nav>
    <!-- Content -->
    <main>
HTML;
}

function mainFooter()
{
    $baseUrl = BASE_URL;
    $year = date('Y');

    echo <<<HTML
    </main>
    <!-- Footer -->
    <footer class="footer mt-auto py-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="{$baseUrl}/assets/img/logo.png" alt="Atlas LMS" height="40" class="mb-3">
                    <p>Atlas LMS adalah platform pembelajaran online yang membantu Anda mengembangkan keterampilan dan pengetahuan melalui kursus berkualitas tinggi.</p>
                </div>
                <div class="col-lg-6 text-lg-end">
                    <h5>Ikuti Kami</h5>
                    <div class="d-flex justify-content-lg-end">
                        <a href="#" class="me-2 text-white"><i class="bi bi-facebook fs-5"></i></a>
                        <a href="#" class="me-2 text-white"><i class="bi bi-twitter fs-5"></i></a>
                        <a href="#" class="me-2 text-white"><i class="bi bi-instagram fs-5"></i></a>
                        <a href="#" class="me-2 text-white"><i class="bi bi-youtube fs-5"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; {$year} Atlas LMS. Hak Cipta Dilindungi.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0">Dibuat oleh Tim Atlas</p>
                </div>
            </div>
        </div>
    </footer>
HTML;
    endHTML();
}
