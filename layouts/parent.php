<?php
// Path: layouts/parent.php

function startHTML($title = "Atlas LMS", $description = "Sistem Manajemen Pembelajaran Atlas")
{
    $baseUrl = BASE_URL;
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="{$description}">
        <title>{$title} - Atlas LMS</title>
        <link rel="shortcut icon" href="{$baseUrl}/assets/favicon.png" type="image/png">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
        <link rel="stylesheet" href="{$baseUrl}/assets/css/custom.css">
        <!-- Preloader -->
        <div class="preloader">
            <div class="loader"></div>
        </div>
    </head>
    <body>
HTML;
}

function endHTML()
{
    $baseUrl = BASE_URL;
    echo <<<HTML
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            window.addEventListener('load', function() {
                const preloader = document.querySelector('.preloader');
                preloader.style.opacity = '0';
                setTimeout(function() {
                    preloader.style.display = 'none';
                }, 300);
            });
        </script>
    </body>
    </html>
HTML;
}
