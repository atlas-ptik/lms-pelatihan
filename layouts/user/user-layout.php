<?php
// Path: layouts/user/user-layout.php

require_once BASE_PATH . '/layouts/parent.php';

function userLayout($title, $content, $activeMenu = '')
{
    if (!isset($_SESSION['user'])) {
        header('Location: ' . BASE_URL . '/user/login');
        exit;
    }

    $baseUrl = BASE_URL;
    $user = $_SESSION['user'];

    startHTML($title, "Atlas LMS - Sistem Manajemen Pembelajaran");
?>
    <div class="wrapper">
        <!-- Main Content -->
        <div class="content-area pb-5">
            <div class="container py-3">
                <?= $content ?>
            </div>
        </div>

        <!-- Bottom Navbar -->
        <nav class="navbar fixed-bottom navbar-dark bg-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?= $baseUrl ?>/user/dashboard">
                    <img src="<?= $baseUrl ?>/assets/img/logo.png" alt="Atlas LMS" height="30" class="d-inline-block align-text-top">
                </a>

                <div class="d-flex align-items-center">
                    <a class="nav-link px-3 <?= ($activeMenu == 'dashboard') ? 'text-primary' : 'text-white' ?>" href="<?= $baseUrl ?>/user/dashboard">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>

                    <a class="nav-link px-3 <?= ($activeMenu == 'kursus') ? 'text-primary' : 'text-white' ?>" href="<?= $baseUrl ?>/user/kursus">
                        <i class="bi bi-book"></i> Kursus Saya
                    </a>

                    <a class="nav-link px-3 <?= ($activeMenu == 'sertifikat') ? 'text-primary' : 'text-white' ?>" href="<?= $baseUrl ?>/user/sertifikat">
                        <i class="bi bi-award"></i> Sertifikat
                    </a>

                    <a class="nav-link px-3 <?= ($activeMenu == 'profil') ? 'text-primary' : 'text-white' ?>" href="<?= $baseUrl ?>/user/profil">
                        <i class="bi bi-person"></i> Profil
                    </a>
                </div>

                <div class="dropdown dropup">
                    <a class="btn btn-dark dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if (!empty($user['foto_profil'])): ?>
                            <img src="<?= $baseUrl ?>/uploads/profil/<?= $user['foto_profil'] ?>"
                                alt="<?= htmlspecialchars($user['nama_lengkap']) ?>" class="rounded-circle"
                                width="24" height="24" style="object-fit: cover;">
                        <?php else: ?>
                            <i class="bi bi-person-circle"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($user['nama_lengkap'] ?? 'Pengguna') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="<?= $baseUrl ?>/user/profil"><i class="bi bi-person me-2"></i>Profil</a></li>
                        <li><a class="dropdown-item" href="<?= $baseUrl ?>/user/profil/password"><i class="bi bi-key me-2"></i>Ubah Password</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="<?= $baseUrl ?>/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
    </div>

    <style>
        body {
            padding-bottom: 56px;
            background-color: #f5f5f5;
        }

        .wrapper {
            min-height: calc(100vh - 56px);
        }

        .content-area {
            min-height: calc(100vh - 56px);
        }

        .btn-primary,
        .bg-primary,
        .text-primary {
            background-color: #39ff14 !important;
            border-color: #39ff14 !important;
            color: #212529 !important;
        }

        .btn-outline-primary {
            border-color: #39ff14 !important;
            color: #39ff14 !important;
        }

        .btn-outline-primary:hover {
            background-color: #39ff14 !important;
            color: #212529 !important;
        }

        .navbar {
            padding: 0.5rem 1rem;
        }

        .navbar .nav-link i {
            font-size: 1.25rem;
        }

        .card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 20px;
        }

        .dropdown-menu {
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: none;
        }

        .dropdown-item {
            padding: 0.5rem 1.5rem;
        }

        .dropdown-item:active {
            background-color: #39ff14;
            color: #212529;
        }
    </style>
<?php
    endHTML();
}
