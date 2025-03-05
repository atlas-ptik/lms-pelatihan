<?php
// Path: routes/admin.php

function handleAdminRoutes($request)
{
    // Izinkan akses ke halaman login dan register tanpa autentikasi
    $public_routes = ['/admin/login', '/admin/initial-register'];

    // Jika bukan rute publik, cek autentikasi admin
    if (!in_array($request, $public_routes)) {
        if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
            header('Location: ' . BASE_URL . '/admin/login');
            exit;
        }
    }

    switch ($request) {
        // Halaman Publik Admin
        case '/admin/login':
            require BASE_PATH . '/views/admin/login.php';
            break;

        case '/admin/initial-register':
            require BASE_PATH . '/views/admin/initial-register.php';
            break;

        // Dashboard Admin (Halaman yang memerlukan login)
        case '/admin':
        case '/admin/':
        case '/admin/dashboard':
            require BASE_PATH . '/views/admin/dashboard.php';
            break;

        // Pengguna management
        case '/admin/pengguna':
            require BASE_PATH . '/views/admin/pengguna/index.php';
            break;
        case '/admin/pengguna/tambah':
            require BASE_PATH . '/views/admin/pengguna/tambah.php';
            break;
        case '/admin/pengguna/edit':
            require BASE_PATH . '/views/admin/pengguna/edit.php';
            break;
        case '/admin/pengguna/hapus':
            require BASE_PATH . '/views/admin/pengguna/hapus.php';
            break;

        // Kursus management
        case '/admin/kursus':
            require BASE_PATH . '/views/admin/kursus/index.php';
            break;
        case '/admin/kursus/tambah':
            require BASE_PATH . '/views/admin/kursus/tambah.php';
            break;
        case '/admin/kursus/edit':
            require BASE_PATH . '/views/admin/kursus/edit.php';
            break;
        case '/admin/kursus/hapus':
            require BASE_PATH . '/views/admin/kursus/hapus.php';
            break;

        // Modul management
        case '/admin/modul':
            require BASE_PATH . '/views/admin/modul/index.php';
            break;
        case '/admin/modul/tambah':
            require BASE_PATH . '/views/admin/modul/tambah.php';
            break;
        case '/admin/modul/edit':
            require BASE_PATH . '/views/admin/modul/edit.php';
            break;
        case '/admin/modul/hapus':
            require BASE_PATH . '/views/admin/modul/hapus.php';
            break;

        // Materi management
        case '/admin/materi':
            require BASE_PATH . '/views/admin/materi/index.php';
            break;
        case '/admin/materi/tambah':
            require BASE_PATH . '/views/admin/materi/tambah.php';
            break;
        case '/admin/materi/edit':
            require BASE_PATH . '/views/admin/materi/edit.php';
            break;
        case '/admin/materi/hapus':
            require BASE_PATH . '/views/admin/materi/hapus.php';
            break;

        // Kategori management
        case '/admin/kategori':
            require BASE_PATH . '/views/admin/kategori/index.php';
            break;
        case '/admin/kategori/tambah':
            require BASE_PATH . '/views/admin/kategori/tambah.php';
            break;
        case '/admin/kategori/edit':
            require BASE_PATH . '/views/admin/kategori/edit.php';
            break;
        case '/admin/kategori/hapus':
            require BASE_PATH . '/views/admin/kategori/hapus.php';
            break;

        // Quiz management
        case '/admin/quiz':
            require BASE_PATH . '/views/admin/quiz/index.php';
            break;
        case '/admin/quiz/tambah':
            require BASE_PATH . '/views/admin/quiz/tambah.php';
            break;
        case '/admin/quiz/edit':
            require BASE_PATH . '/views/admin/quiz/edit.php';
            break;
        case '/admin/quiz/pertanyaan':
            require BASE_PATH . '/views/admin/quiz/pertanyaan.php';
            break;

        // Tugas management
        case '/admin/tugas':
            require BASE_PATH . '/views/admin/tugas/index.php';
            break;
        case '/admin/tugas/tambah':
            require BASE_PATH . '/views/admin/tugas/tambah.php';
            break;
        case '/admin/tugas/edit':
            require BASE_PATH . '/views/admin/tugas/edit.php';
            break;
        case '/admin/tugas/nilai':
            require BASE_PATH . '/views/admin/tugas/nilai.php';
            break;

        // Pendaftaran management
        case '/admin/pendaftaran':
            require BASE_PATH . '/views/admin/pendaftaran/index.php';
            break;
        case '/admin/pendaftaran/tambah':
            require BASE_PATH . '/views/admin/pendaftaran/tambah.php';
            break;
        case '/admin/pendaftaran/detail':
            require BASE_PATH . '/views/admin/pendaftaran/detail.php';
            break;
        case '/admin/pendaftaran/hapus':
            require BASE_PATH . '/views/admin/pendaftaran/hapus.php';
            break;

        // Diskusi management
        case '/admin/diskusi':
            require BASE_PATH . '/views/admin/diskusi/index.php';
            break;
        case '/admin/diskusi/detail':
            require BASE_PATH . '/views/admin/diskusi/detail.php';
            break;
        case '/admin/diskusi/hapus':
            require BASE_PATH . '/views/admin/diskusi/hapus.php';
            break;

        // Sertifikat management
        case '/admin/sertifikat':
            require BASE_PATH . '/views/admin/sertifikat/index.php';
            break;
        case '/admin/sertifikat/tambah':
            require BASE_PATH . '/views/admin/sertifikat/tambah.php';
            break;
        case '/admin/sertifikat/detail':
            require BASE_PATH . '/views/admin/sertifikat/detail.php';
            break;

        // Laporan
        case '/admin/laporan/pengguna':
            require BASE_PATH . '/views/admin/laporan/pengguna.php';
            break;
        case '/admin/laporan/kursus':
            require BASE_PATH . '/views/admin/laporan/kursus.php';
            break;
        case '/admin/laporan/pendaftaran':
            require BASE_PATH . '/views/admin/laporan/pendaftaran.php';
            break;
        case '/admin/laporan/quiz':
            require BASE_PATH . '/views/admin/laporan/quiz.php';
            break;
        case '/admin/laporan/tugas':
            require BASE_PATH . '/views/admin/laporan/tugas.php';
            break;

        // Pengaturan
        case '/admin/pengaturan':
            require BASE_PATH . '/views/admin/pengaturan/index.php';
            break;
        case '/admin/pengaturan/sistem':
            require BASE_PATH . '/views/admin/pengaturan/sistem.php';
            break;
        case '/admin/pengaturan/tampilan':
            require BASE_PATH . '/views/admin/pengaturan/tampilan.php';
            break;

        default:
            http_response_code(404);
            require BASE_PATH . '/views/errors/404.php';
            break;
    }
}
