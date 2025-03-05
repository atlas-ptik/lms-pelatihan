<?php
// Path: routes/user.php

function handleUserRoutes($request)
{
    // Pengecualian khusus untuk halaman login
    if ($request == '/user/login') {
        require BASE_PATH . '/views/user/login.php';
        return;
    }

    // Pastikan pengguna sudah login untuk mengakses rute user lainnya
    if (!isset($_SESSION['user'])) {
        // Simpan URL yang diminta untuk redirect setelah login
        $_SESSION['redirect_after_login'] = $request;
        header('Location: ' . BASE_URL . '/user/login');
        exit;
    }

    switch ($request) {
        case '/user':
        case '/user/':
            require BASE_PATH . '/views/user/dashboard.php';
            break;
        case '/user/dashboard':
            require BASE_PATH . '/views/user/dashboard.php';
            break;

        // Profil pengguna
        case '/user/profil':
            require BASE_PATH . '/views/user/profil/index.php';
            break;
        case '/user/profil/edit':
            require BASE_PATH . '/views/user/profil/edit.php';
            break;
        case '/user/profil/password':
            require BASE_PATH . '/views/user/profil/password.php';
            break;

        // Kursus saya
        case '/user/kursus':
            require BASE_PATH . '/views/user/kursus/index.php';
            break;
        case '/user/kursus/detail':
            require BASE_PATH . '/views/user/kursus/detail.php';
            break;
        case '/user/kursus/daftar':
            require BASE_PATH . '/views/user/kursus/daftar.php';
            break;

        // Belajar
        case '/user/belajar':
            require BASE_PATH . '/views/user/belajar/index.php';
            break;
        case '/user/belajar/materi':
            require BASE_PATH . '/views/user/belajar/materi.php';
            break;
        case '/user/belajar/selesai':
            require BASE_PATH . '/views/user/belajar/selesai.php';
            break;

        // Quiz
        case '/user/quiz':
            require BASE_PATH . '/views/user/quiz/index.php';
            break;
        case '/user/quiz/mulai':
            require BASE_PATH . '/views/user/quiz/mulai.php';
            break;
        case '/user/quiz/jawab':
            require BASE_PATH . '/views/user/quiz/jawab.php';
            break;
        case '/user/quiz/hasil':
            require BASE_PATH . '/views/user/quiz/hasil.php';
            break;

        // Tugas
        case '/user/tugas':
            require BASE_PATH . '/views/user/tugas/index.php';
            break;
        case '/user/tugas/detail':
            require BASE_PATH . '/views/user/tugas/detail.php';
            break;
        case '/user/tugas/kumpul':
            require BASE_PATH . '/views/user/tugas/kumpul.php';
            break;

        // Diskusi
        case '/user/diskusi':
            require BASE_PATH . '/views/user/diskusi/index.php';
            break;
        case '/user/diskusi/detail':
            require BASE_PATH . '/views/user/diskusi/detail.php';
            break;
        case '/user/diskusi/tambah':
            require BASE_PATH . '/views/user/diskusi/tambah.php';
            break;

        // Sertifikat
        case '/user/sertifikat':
            require BASE_PATH . '/views/user/sertifikat/index.php';
            break;
        case '/user/sertifikat/detail':
            require BASE_PATH . '/views/user/sertifikat/detail.php';
            break;

        // Instruktur khusus (jika user adalah instruktur)
        case '/user/instruktur/kursus':
            require BASE_PATH . '/views/user/instruktur/kursus/index.php';
            break;
        case '/user/instruktur/kursus/tambah':
            require BASE_PATH . '/views/user/instruktur/kursus/tambah.php';
            break;
        case '/user/instruktur/kursus/edit':
            require BASE_PATH . '/views/user/instruktur/kursus/edit.php';
            break;
        case '/user/instruktur/peserta':
            require BASE_PATH . '/views/user/instruktur/peserta/index.php';
            break;
        case '/user/instruktur/tugas/nilai':
            require BASE_PATH . '/views/user/instruktur/tugas/nilai.php';
            break;

        default:
            http_response_code(404);
            require BASE_PATH . '/views/errors/404.php';
            break;
    }
}
