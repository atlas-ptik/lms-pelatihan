<?php
// Path: views/admin/pengaturan/tampilan.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

$db = dbConnect();

// Definisikan pengaturan tampilan yang diperlukan
$tampilan_keys = [
    'logo_aplikasi' => [
        'default' => '/assets/img/logo.png',
        'tipe' => 'teks',
        'deskripsi' => 'Path ke logo utama aplikasi'
    ],
    'favicon' => [
        'default' => '/assets/favicon.png',
        'tipe' => 'teks',
        'deskripsi' => 'Path ke favicon aplikasi'
    ],
    'warna_primer' => [
        'default' => '#39ff14',
        'tipe' => 'teks',
        'deskripsi' => 'Warna primer untuk tema aplikasi'
    ],
    'warna_sekunder' => [
        'default' => '#343a40',
        'tipe' => 'teks',
        'deskripsi' => 'Warna sekunder untuk tema aplikasi'
    ],
    'warna_aksen' => [
        'default' => '#7fff56',
        'tipe' => 'teks',
        'deskripsi' => 'Warna aksen untuk tema aplikasi'
    ],
    'font_utama' => [
        'default' => 'Poppins, sans-serif',
        'tipe' => 'teks',
        'deskripsi' => 'Font utama untuk teks umum'
    ],
    'font_judul' => [
        'default' => 'Poppins, sans-serif',
        'tipe' => 'teks',
        'deskripsi' => 'Font untuk judul'
    ],
    'ukuran_font_dasar' => [
        'default' => '16px',
        'tipe' => 'teks',
        'deskripsi' => 'Ukuran font dasar'
    ],
    'radius_sudut' => [
        'default' => '8px',
        'tipe' => 'teks',
        'deskripsi' => 'Radius sudut elemen UI (border-radius)'
    ],
    'shadow_card' => [
        'default' => '0 4px 6px rgba(0, 0, 0, 0.1)',
        'tipe' => 'teks',
        'deskripsi' => 'Shadow untuk komponen card'
    ],
    'animasi_transisi' => [
        'default' => '0.3s ease',
        'tipe' => 'teks',
        'deskripsi' => 'Durasi dan timing function untuk transisi UI'
    ],
    'gambar_login' => [
        'default' => '/assets/img/login-bg.jpg',
        'tipe' => 'teks',
        'deskripsi' => 'Gambar latar belakang halaman login'
    ],
    'gambar_header' => [
        'default' => '/assets/img/header-bg.jpg',
        'tipe' => 'teks',
        'deskripsi' => 'Gambar latar belakang header'
    ],
    'tampilkan_preloader' => [
        'default' => '1',
        'tipe' => 'boolean',
        'deskripsi' => 'Tampilkan preloader saat memuat halaman'
    ],
    'tampilkan_breadcrumbs' => [
        'default' => '1',
        'tipe' => 'boolean',
        'deskripsi' => 'Tampilkan breadcrumbs navigasi'
    ],
    'dark_mode' => [
        'default' => '0',
        'tipe' => 'boolean',
        'deskripsi' => 'Aktifkan tema gelap (dark mode)'
    ],
    'custom_css' => [
        'default' => '',
        'tipe' => 'html',
        'deskripsi' => 'Kode CSS kustom tambahan'
    ]
];

// Periksa dan buat pengaturan default jika belum ada
foreach ($tampilan_keys as $key => $values) {
    $stmt = $db->prepare("SELECT id FROM pengaturan WHERE kunci = ?");
    $stmt->execute([$key]);

    if ($stmt->rowCount() == 0) {
        $id = generate_uuid();
        $stmt = $db->prepare("INSERT INTO pengaturan (id, kunci, nilai, deskripsi, tipe) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id, $key, $values['default'], $values['deskripsi'], $values['tipe']]);
    }
}

// Ambil pengaturan tampilan dari database
$stmt = $db->prepare("SELECT * FROM pengaturan WHERE kunci IN (" . implode(',', array_fill(0, count(array_keys($tampilan_keys)), '?')) . ") ORDER BY kunci ASC");
$stmt->execute(array_keys($tampilan_keys));
$pengaturan_tampilan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ubah array ke format kunci => nilai untuk memudahkan akses
$pengaturan = [];
foreach ($pengaturan_tampilan as $p) {
    $pengaturan[$p['kunci']] = $p;
}

// Jika form disubmit untuk update pengaturan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tampilan'])) {
    // Proses upload logo jika ada
    if (isset($_FILES['logo_upload']) && $_FILES['logo_upload']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = BASE_PATH . '/assets/img/';
        $fileName = 'logo-' . time() . '.png';
        $uploadFile = $uploadDir . $fileName;

        $allowed_types = ['image/png', 'image/jpeg', 'image/gif', 'image/svg+xml'];

        if (!in_array($_FILES['logo_upload']['type'], $allowed_types)) {
            $error = "Tipe file logo tidak didukung. Gunakan PNG, JPG, GIF, atau SVG.";
        } elseif ($_FILES['logo_upload']['size'] > 1048576) { // 1MB
            $error = "Ukuran file logo terlalu besar. Maksimal 1MB.";
        } elseif (move_uploaded_file($_FILES['logo_upload']['tmp_name'], $uploadFile)) {
            $_POST['logo_aplikasi'] = '/assets/img/' . $fileName;
        } else {
            $error = "Gagal mengupload file logo.";
        }
    }

    // Proses upload favicon jika ada
    if (isset($_FILES['favicon_upload']) && $_FILES['favicon_upload']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = BASE_PATH . '/assets/';
        $fileName = 'favicon-' . time() . '.png';
        $uploadFile = $uploadDir . $fileName;

        $allowed_types = ['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'];

        if (!in_array($_FILES['favicon_upload']['type'], $allowed_types)) {
            $error = "Tipe file favicon tidak didukung. Gunakan PNG atau ICO.";
        } elseif ($_FILES['favicon_upload']['size'] > 524288) { // 512KB
            $error = "Ukuran file favicon terlalu besar. Maksimal 512KB.";
        } elseif (move_uploaded_file($_FILES['favicon_upload']['tmp_name'], $uploadFile)) {
            $_POST['favicon'] = '/assets/' . $fileName;
        } else {
            $error = "Gagal mengupload file favicon.";
        }
    }

    // Jika tidak ada error, lanjutkan update data
    if (!isset($error)) {
        try {
            foreach ($_POST as $key => $value) {
                // Lewati yang bukan pengaturan tampilan
                if ($key === 'update_tampilan' || !isset($tampilan_keys[$key])) {
                    continue;
                }

                // Update nilai pengaturan
                $stmt = $db->prepare("UPDATE pengaturan SET nilai = ? WHERE kunci = ?");
                $stmt->execute([$value, $key]);
            }

            // Set nilai checkbox yang tidak dicentang (tidak dikirim dalam $_POST)
            $checkbox_keys = ['tampilkan_preloader', 'tampilkan_breadcrumbs', 'dark_mode'];
            foreach ($checkbox_keys as $key) {
                if (!isset($_POST[$key])) {
                    $stmt = $db->prepare("UPDATE pengaturan SET nilai = '0' WHERE kunci = ?");
                    $stmt->execute([$key]);
                }
            }

            $sukses = "Pengaturan tampilan berhasil diperbarui";

            // Refresh halaman dengan pesan sukses
            header('Location: ' . BASE_URL . '/admin/pengaturan/tampilan?sukses=' . urlencode($sukses));
            exit;
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Buat pratinjau CSS berdasarkan pengaturan saat ini
$warna_primer = $pengaturan['warna_primer']['nilai'] ?? $tampilan_keys['warna_primer']['default'];
$warna_sekunder = $pengaturan['warna_sekunder']['nilai'] ?? $tampilan_keys['warna_sekunder']['default'];
$warna_aksen = $pengaturan['warna_aksen']['nilai'] ?? $tampilan_keys['warna_aksen']['default'];
$font_utama = $pengaturan['font_utama']['nilai'] ?? $tampilan_keys['font_utama']['default'];
$radius_sudut = $pengaturan['radius_sudut']['nilai'] ?? $tampilan_keys['radius_sudut']['default'];

$preview_css = <<<CSS
.color-primary { background-color: {$warna_primer}; }
.color-secondary { background-color: {$warna_sekunder}; }
.color-accent { background-color: {$warna_aksen}; }
.preview-card {
    border-radius: {$radius_sudut};
    overflow: hidden;
    font-family: {$font_utama};
    border: 1px solid #dee2e6;
}
.preview-header {
    background-color: {$warna_sekunder};
    color: white;
    padding: 15px;
}
.preview-body {
    padding: 15px;
}
.preview-button {
    background-color: {$warna_primer};
    color: {$warna_sekunder};
    border: none;
    border-radius: calc({$radius_sudut} / 2);
    padding: 8px 16px;
    cursor: pointer;
}
.preview-color {
    width: 100%;
    height: 50px;
    margin-bottom: 10px;
    border-radius: 4px;
    position: relative;
}
.preview-color span {
    position: absolute;
    bottom: 5px;
    right: 10px;
    background: rgba(255,255,255,0.8);
    padding: 2px 5px;
    border-radius: 3px;
    font-size: 12px;
}
CSS;

adminHeader("Pengaturan Tampilan", "Kelola pengaturan tampilan Atlas LMS");
?>

<style>
    <?= $preview_css ?>
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Pengaturan Tampilan</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/pengaturan">Pengaturan</a></li>
            <li class="breadcrumb-item active">Tampilan</li>
        </ol>
    </div>

    <?php if (isset($_GET['sukses'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['sukses']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-7">
            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 font-weight-bold">Pengaturan Tampilan</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <ul class="nav nav-tabs mb-4" id="tampilan-tab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="branding-tab" data-bs-toggle="tab" data-bs-target="#branding" type="button" role="tab" aria-controls="branding" aria-selected="true">Branding</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="warna-tab" data-bs-toggle="tab" data-bs-target="#warna" type="button" role="tab" aria-controls="warna" aria-selected="false">Warna</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tipografi-tab" data-bs-toggle="tab" data-bs-target="#tipografi" type="button" role="tab" aria-controls="tipografi" aria-selected="false">Tipografi</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="komponen-tab" data-bs-toggle="tab" data-bs-target="#komponen" type="button" role="tab" aria-controls="komponen" aria-selected="false">Komponen</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="lainnya-tab" data-bs-toggle="tab" data-bs-target="#lainnya" type="button" role="tab" aria-controls="lainnya" aria-selected="false">Lainnya</button>
                            </li>
                        </ul>
                        <div class="tab-content" id="tampilanTabContent">
                            <!-- Tab Branding -->
                            <div class="tab-pane fade show active" id="branding" role="tabpanel" aria-labelledby="branding-tab">
                                <div class="mb-4">
                                    <label for="logo_aplikasi" class="form-label">Logo Aplikasi</label>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="logo_aplikasi" name="logo_aplikasi"
                                            value="<?= htmlspecialchars($pengaturan['logo_aplikasi']['nilai'] ?? $tampilan_keys['logo_aplikasi']['default']) ?>">
                                        <button class="btn btn-outline-secondary" type="button" id="preview-logo">Preview</button>
                                    </div>
                                    <div class="mb-3">
                                        <label for="logo_upload" class="form-label">Unggah Logo Baru</label>
                                        <input class="form-control" type="file" id="logo_upload" name="logo_upload" accept="image/*">
                                        <small class="text-muted">Ukuran maksimal: 1MB. Format: PNG, JPG, GIF atau SVG.</small>
                                    </div>
                                    <div id="logo-preview" class="mb-3 text-center p-3 bg-light rounded" style="display: none;">
                                        <img src="" alt="Logo Preview" style="max-height: 100px;">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="favicon" class="form-label">Favicon</label>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="favicon" name="favicon"
                                            value="<?= htmlspecialchars($pengaturan['favicon']['nilai'] ?? $tampilan_keys['favicon']['default']) ?>">
                                        <button class="btn btn-outline-secondary" type="button" id="preview-favicon">Preview</button>
                                    </div>
                                    <div class="mb-3">
                                        <label for="favicon_upload" class="form-label">Unggah Favicon Baru</label>
                                        <input class="form-control" type="file" id="favicon_upload" name="favicon_upload" accept="image/png,image/x-icon">
                                        <small class="text-muted">Ukuran maksimal: 512KB. Format: PNG atau ICO.</small>
                                    </div>
                                    <div id="favicon-preview" class="mb-3 text-center p-3 bg-light rounded" style="display: none;">
                                        <img src="" alt="Favicon Preview" style="max-height: 48px;">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="gambar_login" class="form-label">Gambar Latar Belakang Login</label>
                                    <input type="text" class="form-control" id="gambar_login" name="gambar_login"
                                        value="<?= htmlspecialchars($pengaturan['gambar_login']['nilai'] ?? $tampilan_keys['gambar_login']['default']) ?>">
                                    <small class="text-muted"><?= htmlspecialchars($tampilan_keys['gambar_login']['deskripsi']) ?></small>
                                </div>

                                <div class="mb-3">
                                    <label for="gambar_header" class="form-label">Gambar Header</label>
                                    <input type="text" class="form-control" id="gambar_header" name="gambar_header"
                                        value="<?= htmlspecialchars($pengaturan['gambar_header']['nilai'] ?? $tampilan_keys['gambar_header']['default']) ?>">
                                    <small class="text-muted"><?= htmlspecialchars($tampilan_keys['gambar_header']['deskripsi']) ?></small>
                                </div>
                            </div>

                            <!-- Tab Warna -->
                            <div class="tab-pane fade" id="warna" role="tabpanel" aria-labelledby="warna-tab">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="warna_primer" class="form-label">Warna Primer</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="warna_primer_picker"
                                                value="<?= htmlspecialchars($pengaturan['warna_primer']['nilai'] ?? $tampilan_keys['warna_primer']['default']) ?>">
                                            <input type="text" class="form-control" id="warna_primer" name="warna_primer"
                                                value="<?= htmlspecialchars($pengaturan['warna_primer']['nilai'] ?? $tampilan_keys['warna_primer']['default']) ?>">
                                        </div>
                                        <small class="text-muted"><?= htmlspecialchars($tampilan_keys['warna_primer']['deskripsi']) ?></small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="warna_sekunder" class="form-label">Warna Sekunder</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="warna_sekunder_picker"
                                                value="<?= htmlspecialchars($pengaturan['warna_sekunder']['nilai'] ?? $tampilan_keys['warna_sekunder']['default']) ?>">
                                            <input type="text" class="form-control" id="warna_sekunder" name="warna_sekunder"
                                                value="<?= htmlspecialchars($pengaturan['warna_sekunder']['nilai'] ?? $tampilan_keys['warna_sekunder']['default']) ?>">
                                        </div>
                                        <small class="text-muted"><?= htmlspecialchars($tampilan_keys['warna_sekunder']['deskripsi']) ?></small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="warna_aksen" class="form-label">Warna Aksen</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="warna_aksen_picker"
                                                value="<?= htmlspecialchars($pengaturan['warna_aksen']['nilai'] ?? $tampilan_keys['warna_aksen']['default']) ?>">
                                            <input type="text" class="form-control" id="warna_aksen" name="warna_aksen"
                                                value="<?= htmlspecialchars($pengaturan['warna_aksen']['nilai'] ?? $tampilan_keys['warna_aksen']['default']) ?>">
                                        </div>
                                        <small class="text-muted"><?= htmlspecialchars($tampilan_keys['warna_aksen']['deskripsi']) ?></small>
                                    </div>
                                    <div class="col-md-6 mb-3 form-check form-switch mt-4 ps-5">
                                        <input class="form-check-input" type="checkbox" id="dark_mode" name="dark_mode" value="1"
                                            <?= ($pengaturan['dark_mode']['nilai'] ?? $tampilan_keys['dark_mode']['default']) == '1' ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="dark_mode">Aktifkan Tema Gelap (Dark Mode)</label>
                                        <div><small class="text-muted"><?= htmlspecialchars($tampilan_keys['dark_mode']['deskripsi']) ?></small></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tab Tipografi -->
                            <div class="tab-pane fade" id="tipografi" role="tabpanel" aria-labelledby="tipografi-tab">
                                <div class="mb-3">
                                    <label for="font_utama" class="form-label">Font Utama</label>
                                    <input type="text" class="form-control" id="font_utama" name="font_utama"
                                        value="<?= htmlspecialchars($pengaturan['font_utama']['nilai'] ?? $tampilan_keys['font_utama']['default']) ?>">
                                    <small class="text-muted"><?= htmlspecialchars($tampilan_keys['font_utama']['deskripsi']) ?></small>
                                </div>

                                <div class="mb-3">
                                    <label for="font_judul" class="form-label">Font Judul</label>
                                    <input type="text" class="form-control" id="font_judul" name="font_judul"
                                        value="<?= htmlspecialchars($pengaturan['font_judul']['nilai'] ?? $tampilan_keys['font_judul']['default']) ?>">
                                    <small class="text-muted"><?= htmlspecialchars($tampilan_keys['font_judul']['deskripsi']) ?></small>
                                </div>

                                <div class="mb-3">
                                    <label for="ukuran_font_dasar" class="form-label">Ukuran Font Dasar</label>
                                    <input type="text" class="form-control" id="ukuran_font_dasar" name="ukuran_font_dasar"
                                        value="<?= htmlspecialchars($pengaturan['ukuran_font_dasar']['nilai'] ?? $tampilan_keys['ukuran_font_dasar']['default']) ?>">
                                    <small class="text-muted"><?= htmlspecialchars($tampilan_keys['ukuran_font_dasar']['deskripsi']) ?></small>
                                </div>
                            </div>

                            <!-- Tab Komponen -->
                            <div class="tab-pane fade" id="komponen" role="tabpanel" aria-labelledby="komponen-tab">
                                <div class="mb-3">
                                    <label for="radius_sudut" class="form-label">Radius Sudut</label>
                                    <input type="text" class="form-control" id="radius_sudut" name="radius_sudut"
                                        value="<?= htmlspecialchars($pengaturan['radius_sudut']['nilai'] ?? $tampilan_keys['radius_sudut']['default']) ?>">
                                    <small class="text-muted"><?= htmlspecialchars($tampilan_keys['radius_sudut']['deskripsi']) ?></small>
                                </div>

                                <div class="mb-3">
                                    <label for="shadow_card" class="form-label">Shadow Card</label>
                                    <input type="text" class="form-control" id="shadow_card" name="shadow_card"
                                        value="<?= htmlspecialchars($pengaturan['shadow_card']['nilai'] ?? $tampilan_keys['shadow_card']['default']) ?>">
                                    <small class="text-muted"><?= htmlspecialchars($tampilan_keys['shadow_card']['deskripsi']) ?></small>
                                </div>

                                <div class="mb-3">
                                    <label for="animasi_transisi" class="form-label">Animasi Transisi</label>
                                    <input type="text" class="form-control" id="animasi_transisi" name="animasi_transisi"
                                        value="<?= htmlspecialchars($pengaturan['animasi_transisi']['nilai'] ?? $tampilan_keys['animasi_transisi']['default']) ?>">
                                    <small class="text-muted"><?= htmlspecialchars($tampilan_keys['animasi_transisi']['deskripsi']) ?></small>
                                </div>

                                <div class="mb-3 form-check form-switch ps-5">
                                    <input class="form-check-input" type="checkbox" id="tampilkan_preloader" name="tampilkan_preloader" value="1"
                                        <?= ($pengaturan['tampilkan_preloader']['nilai'] ?? $tampilan_keys['tampilkan_preloader']['default']) == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tampilkan_preloader">Tampilkan Preloader</label>
                                    <div><small class="text-muted"><?= htmlspecialchars($tampilan_keys['tampilkan_preloader']['deskripsi']) ?></small></div>
                                </div>

                                <div class="mb-3 form-check form-switch ps-5">
                                    <input class="form-check-input" type="checkbox" id="tampilkan_breadcrumbs" name="tampilkan_breadcrumbs" value="1"
                                        <?= ($pengaturan['tampilkan_breadcrumbs']['nilai'] ?? $tampilan_keys['tampilkan_breadcrumbs']['default']) == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="tampilkan_breadcrumbs">Tampilkan Breadcrumbs</label>
                                    <div><small class="text-muted"><?= htmlspecialchars($tampilan_keys['tampilkan_breadcrumbs']['deskripsi']) ?></small></div>
                                </div>
                            </div>

                            <!-- Tab Lainnya -->
                            <div class="tab-pane fade" id="lainnya" role="tabpanel" aria-labelledby="lainnya-tab">
                                <div class="mb-3">
                                    <label for="custom_css" class="form-label">CSS Kustom</label>
                                    <textarea class="form-control font-monospace" id="custom_css" name="custom_css" rows="10" style="font-size: 0.9rem;"><?= htmlspecialchars($pengaturan['custom_css']['nilai'] ?? $tampilan_keys['custom_css']['default']) ?></textarea>
                                    <small class="text-muted"><?= htmlspecialchars($tampilan_keys['custom_css']['deskripsi']) ?></small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <a href="<?= BASE_URL ?>/admin/pengaturan" class="btn btn-secondary me-2">Kembali</a>
                            <button type="submit" name="update_tampilan" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card mb-4 sticky-top" style="top: 80px; z-index: 100;">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 font-weight-bold">Pratinjau Tema</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6 class="mb-3">Palet Warna</h6>
                        <div class="row">
                            <div class="col-4">
                                <div class="preview-color color-primary">
                                    <span id="preview-primer"><?= htmlspecialchars($warna_primer) ?></span>
                                </div>
                                <small class="d-block text-center">Primer</small>
                            </div>
                            <div class="col-4">
                                <div class="preview-color color-secondary">
                                    <span id="preview-sekunder"><?= htmlspecialchars($warna_sekunder) ?></span>
                                </div>
                                <small class="d-block text-center">Sekunder</small>
                            </div>
                            <div class="col-4">
                                <div class="preview-color color-accent">
                                    <span id="preview-aksen"><?= htmlspecialchars($warna_aksen) ?></span>
                                </div>
                                <small class="d-block text-center">Aksen</small>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h6 class="mb-3">Komponen UI</h6>
                        <div class="preview-card">
                            <div class="preview-header">
                                <h6 class="mb-0">Judul Kartu</h6>
                            </div>
                            <div class="preview-body">
                                <p>Contoh teks dalam kartu menggunakan font <strong id="preview-font"><?= htmlspecialchars($font_utama) ?></strong>.</p>
                                <button class="preview-button">Tombol Aksi</button>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        Pratinjau ini hanya menampilkan sebagian perubahan tema. Untuk melihat hasil lengkap, simpan perubahan.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Preview logo
        const previewLogoBtn = document.getElementById('preview-logo');
        const logoField = document.getElementById('logo_aplikasi');
        const logoPreview = document.getElementById('logo-preview');
        const logoImg = logoPreview.querySelector('img');

        previewLogoBtn.addEventListener('click', function() {
            const logoPath = logoField.value;
            if (logoPath) {
                logoImg.src = '<?= BASE_URL ?>' + logoPath;
                logoPreview.style.display = 'block';
            }
        });

        // Preview favicon
        const previewFaviconBtn = document.getElementById('preview-favicon');
        const faviconField = document.getElementById('favicon');
        const faviconPreview = document.getElementById('favicon-preview');
        const faviconImg = faviconPreview.querySelector('img');

        previewFaviconBtn.addEventListener('click', function() {
            const faviconPath = faviconField.value;
            if (faviconPath) {
                faviconImg.src = '<?= BASE_URL ?>' + faviconPath;
                faviconPreview.style.display = 'block';
            }
        });

        // Color pickers
        const primerPicker = document.getElementById('warna_primer_picker');
        const sekunderPicker = document.getElementById('warna_sekunder_picker');
        const aksenPicker = document.getElementById('warna_aksen_picker');
        const primerField = document.getElementById('warna_primer');
        const sekunderField = document.getElementById('warna_sekunder');
        const aksenField = document.getElementById('warna_aksen');

        primerPicker.addEventListener('input', function() {
            primerField.value = this.value;
            updatePreview('primer', this.value);
        });

        sekunderPicker.addEventListener('input', function() {
            sekunderField.value = this.value;
            updatePreview('sekunder', this.value);
        });

        aksenPicker.addEventListener('input', function() {
            aksenField.value = this.value;
            updatePreview('aksen', this.value);
        });

        primerField.addEventListener('input', function() {
            primerPicker.value = this.value;
            updatePreview('primer', this.value);
        });

        sekunderField.addEventListener('input', function() {
            sekunderPicker.value = this.value;
            updatePreview('sekunder', this.value);
        });

        aksenField.addEventListener('input', function() {
            aksenPicker.value = this.value;
            updatePreview('aksen', this.value);
        });

        // Font preview
        const fontField = document.getElementById('font_utama');
        fontField.addEventListener('input', function() {
            document.getElementById('preview-font').textContent = this.value;
        });

        function updatePreview(type, value) {
            document.getElementById('preview-' + type).textContent = value;

            if (type === 'primer') {
                document.querySelector('.color-primary').style.backgroundColor = value;
                document.querySelector('.preview-button').style.backgroundColor = value;
            } else if (type === 'sekunder') {
                document.querySelector('.color-secondary').style.backgroundColor = value;
                document.querySelector('.preview-header').style.backgroundColor = value;
                document.querySelector('.preview-button').style.color = value;
            } else if (type === 'aksen') {
                document.querySelector('.color-accent').style.backgroundColor = value;
            }
        }
    });
</script>

<?php adminFooter(); ?>