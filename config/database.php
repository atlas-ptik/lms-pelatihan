<?php
// Path: config/database.php

// Load environment variables from .env file
$env = parse_ini_file(__DIR__ . "/../.env");

try {
    $db = new PDO(
        "mysql:host=" . $env['DB_HOST'] . 
        ";dbname=" . $env['DB_NAME'] . 
        ";charset=utf8mb4",
        $env['DB_USER'],
        $env['DB_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]
    );
} catch(PDOException $e) {
    http_response_code(500);
    die("Koneksi database gagal: " . $e->getMessage());
}

function dbConnect() {
    global $db;
    return $db;
}