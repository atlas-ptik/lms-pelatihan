<?php
// Path: layouts/main.php

require_once BASE_PATH . '/layouts/parent.php';

function mainHeader($title = "Atlas LMS", $description = "Sistem Manajemen Pembelajaran Atlas")
{
    startHTML($title, $description);
    $baseUrl = BASE_URL;
    $loginUrl = $baseUrl . '/login';
    $homeUrl = $baseUrl . '/';
    $kursusUrl = $baseUrl . '/kursus';
    $tentangUrl = $baseUrl . '/tentang';

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
                <form class="d-flex me-2" action="{$baseUrl}/kursus" method="GET">
                    <div class="input-group">
                        <input class="form-control" type="search" name="q" placeholder="Cari kursus..." aria-label="Cari">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </form>
                <div class="d-flex">
                    <a href="{$loginUrl}" class="btn btn-primary">Masuk</a>
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
