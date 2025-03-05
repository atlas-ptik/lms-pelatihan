<?php
// Path: layouts/admin/admin-layout.php

require_once BASE_PATH . '/layouts/parent.php';

function adminHeader($title = "Admin - Atlas LMS", $description = "Panel Admin Atlas LMS")
{
    if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
        header('Location: ' . BASE_URL . '/admin/login');
        exit;
    }

    startHTML($title, $description);
    $baseUrl = BASE_URL;
    $user = $_SESSION['user'];
?>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/admin-layout.css">
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <a href="<?= $baseUrl ?>/admin/dashboard" class="sidebar-brand">
                    <img src="<?= $baseUrl ?>/assets/img/logo.png" alt="Atlas LMS" height="30">
                </a>
                <button class="btn-close-sidebar d-md-none" id="closeSidebar">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="sidebar-user">
                <?php if (!empty($user['foto_profil'])): ?>
                    <img src="<?= $baseUrl ?>/uploads/profil/<?= $user['foto_profil'] ?>" alt="<?= htmlspecialchars($user['nama_lengkap']) ?>" class="sidebar-user-img">
                <?php else: ?>
                    <div class="sidebar-user-placeholder">
                        <span><?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?></span>
                    </div>
                <?php endif; ?>
                <div class="sidebar-user-info">
                    <h6 class="mb-0"><?= htmlspecialchars($user['nama_lengkap']) ?></h6>
                    <small>Administrator</small>
                </div>
            </div>

            <nav class="sidebar-nav">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/dashboard') !== false ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/dashboard">
                            <i class="bi bi-speedometer2"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/pengguna') !== false ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/pengguna">
                            <i class="bi bi-people"></i>
                            <span>Manajemen Pengguna</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/kursus') !== false ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/kursus">
                            <i class="bi bi-book"></i>
                            <span>Manajemen Kursus</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/modul') !== false ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/modul">
                            <i class="bi bi-folder"></i>
                            <span>Manajemen Modul</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/materi') !== false ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/materi">
                            <i class="bi bi-file-text"></i>
                            <span>Manajemen Materi</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/kategori') !== false ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/kategori">
                            <i class="bi bi-tag"></i>
                            <span>Manajemen Kategori</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/quiz') !== false ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/quiz">
                            <i class="bi bi-question-circle"></i>
                            <span>Manajemen Quiz</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/tugas') !== false ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/tugas">
                            <i class="bi bi-clipboard-check"></i>
                            <span>Manajemen Tugas</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/pendaftaran') !== false ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/pendaftaran">
                            <i class="bi bi-person-check"></i>
                            <span>Pendaftaran</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/diskusi') !== false ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/diskusi">
                            <i class="bi bi-chat-dots"></i>
                            <span>Manajemen Diskusi</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/sertifikat') !== false ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/sertifikat">
                            <i class="bi bi-award"></i>
                            <span>Manajemen Sertifikat</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/laporan') !== false ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/laporan/pengguna">
                            <i class="bi bi-graph-up"></i>
                            <span>Laporan</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/pengaturan') !== false ? 'active' : '' ?>" href="<?= $baseUrl ?>/admin/pengaturan">
                            <i class="bi bi-gear"></i>
                            <span>Pengaturan</span>
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <a class="nav-link text-danger" href="<?= $baseUrl ?>/logout">
                            <i class="bi bi-box-arrow-right"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content" id="main-content">
            <!-- Topbar -->
            <nav class="topbar">
                <div class="container-fluid">
                    <button class="btn-toggle-sidebar d-md-none" id="toggleSidebar">
                        <i class="bi bi-list"></i>
                    </button>

                    <div class="topbar-search d-none d-md-block">
                        <form action="#">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="search" class="form-control bg-light border-0" placeholder="Cari...">
                            </div>
                        </form>
                    </div>

                    <div class="topbar-right">


                        <div class="topbar-user-menu">
                            <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                                <?php if (!empty($user['foto_profil'])): ?>
                                    <img src="<?= $baseUrl ?>/uploads/profil/<?= $user['foto_profil'] ?>" alt="<?= htmlspecialchars($user['nama_lengkap']) ?>" class="avatar-img">
                                <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <span><?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?></span>
                                    </div>
                                <?php endif; ?>
                                <span class="d-none d-md-inline-block"><?= htmlspecialchars($user['nama_lengkap']) ?></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="<?= $baseUrl ?>/admin/profil">
                                    <i class="bi bi-person"></i> Profil
                                </a>
                                <a class="dropdown-item" href="<?= $baseUrl ?>/admin/pengaturan">
                                    <i class="bi bi-gear"></i> Pengaturan
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?= $baseUrl ?>/logout">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Content -->
            <div class="content">
            <?php
        }

        function adminFooter()
        {
            $baseUrl = BASE_URL;
            $year = date('Y');
            ?>
            </div>

            <!-- Footer -->
            <footer class="footer">
                <div class="container-fluid">
                    <div class="footer-content">
                        <p>&copy; <?= $year ?> Atlas LMS Admin. Hak Cipta Dilindungi.</p>
                    </div>
                </div>
            </footer>
        </div>
    </div>



    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('main-content');
            const toggleSidebar = document.getElementById('toggleSidebar');
            const closeSidebar = document.getElementById('closeSidebar');

            // Toggle sidebar on mobile
            if (toggleSidebar) {
                toggleSidebar.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }

            // Close sidebar on mobile
            if (closeSidebar) {
                closeSidebar.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth < 768 &&
                    sidebar.classList.contains('show') &&
                    !sidebar.contains(event.target) &&
                    !toggleSidebar.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            });

            // Initialize tooltips if Bootstrap 5 is used
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
                tooltips.forEach(tooltip => {
                    new bootstrap.Tooltip(tooltip);
                });
            }

            // Make tables responsive
            const tables = document.querySelectorAll('table');
            tables.forEach(table => {
                if (!table.parentElement.classList.contains('table-responsive')) {
                    const wrapper = document.createElement('div');
                    wrapper.classList.add('table-responsive');
                    table.parentNode.insertBefore(wrapper, table);
                    wrapper.appendChild(table);
                }
            });
        });
    </script>
<?php
            endHTML();
        }
