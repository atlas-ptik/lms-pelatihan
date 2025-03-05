<?php
// Path: layouts/user/auth.php

require_once BASE_PATH . '/layouts/parent.php';

function userAuthHeader($title = "User - Atlas LMS", $description = "Halaman autentikasi pengguna Atlas LMS")
{
    startHTML($title, $description);
    $baseUrl = BASE_URL;

    echo <<<HTML
    <div class="auth-container bg-dark min-vh-100 d-flex align-items-center justify-content-center py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="text-center mb-4">
                        <a href="{$baseUrl}/">
                            <img src="{$baseUrl}/assets/img/logo.png" alt="Atlas LMS" height="60">
                        </a>
                    </div>
                    <div class="card shadow-lg border-0">
                        <div class="card-body p-4 p-sm-5">
HTML;
}

function userAuthFooter()
{
    $baseUrl = BASE_URL;
    $year = date('Y');

    echo <<<HTML
                        </div>
                    </div>
                    <div class="text-center mt-4 text-white">
                        <p>&copy; {$year} Atlas LMS. Hak Cipta Dilindungi.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        .auth-container {
            background: linear-gradient(135deg, #212529, #343a40);
        }
        .card {
            border-radius: 10px;
        }
        .form-control {
            border-radius: 5px;
            padding: 12px 15px;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(57, 255, 20, 0.25);
        }
        .btn-auth {
            background-color: var(--primary);
            border-color: var(--primary);
            color: var(--dark);
            font-weight: 600;
            padding: 12px 15px;
            border-radius: 5px;
            width: 100%;
        }
        .btn-auth:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            color: var(--dark);
        }
    </style>
HTML;
    endHTML();
}
