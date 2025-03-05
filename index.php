<?php
// Path: index.php

define('BASE_PATH', __DIR__);
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

session_start();

require BASE_PATH . '/config/database.php';

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$script_name = dirname($_SERVER['SCRIPT_NAME']);

if ($script_name !== '/') {
    $request = str_replace($script_name, '', $request);
}

$request = filter_var($request, FILTER_SANITIZE_URL);

function generate_uuid()
{
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff)
    );
}

require BASE_PATH . '/routes/admin.php';
require BASE_PATH . '/routes/user.php';

switch ($request) {
    // Halaman Publik
    case '':
    case '/':
        require BASE_PATH . '/views/home.php';
        break;

    // Kursus publik
    case '/kursus':
        require BASE_PATH . '/views/kursus/index.php';
        break;

    case '/kursus/detail':
        require BASE_PATH . '/views/kursus/detail.php';
        break;

    // Halaman tentang
    case '/tentang':
        require BASE_PATH . '/views/tentang.php';
        break;

    // Logout
    case '/logout':
        session_destroy();
        header('Location: ' . BASE_URL . '/');
        exit;

    // Rute Admin
    case (preg_match('/^\/admin/', $request) ? true : false):
        handleAdminRoutes($request);
        break;

    // Rute User
    case (preg_match('/^\/user/', $request) ? true : false):
        handleUserRoutes($request);
        break;

    // Halaman Error
    case '/404':
        http_response_code(404);
        require BASE_PATH . '/views/errors/404.php';
        break;

    case '/403':
        http_response_code(403);
        require BASE_PATH . '/views/errors/403.php';
        break;

    case '/500':
        http_response_code(500);
        require BASE_PATH . '/views/errors/500.php';
        break;

    default:
        $file_path = BASE_PATH . '/views' . $request . '.php';
        if (file_exists($file_path)) {
            require $file_path;
        } else {
            http_response_code(404);
            require BASE_PATH . '/views/errors/404.php';
        }
        break;
}
