<?php
// Path: views/admin/kategori/tambah.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Tambah Kategori", "Tambah kategori kursus baru di Atlas LMS");

$db = dbConnect();
$pesan = '';
$tipe = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $ikon = trim($_POST['ikon'] ?? '');
    $urutan = intval($_POST['urutan'] ?? 0);

    $errors = [];

    if (empty($nama)) {
        $errors[] = "Nama kategori harus diisi";
    }

    if (empty($errors)) {
        try {
            $id = generate_uuid();
            $stmt = $db->prepare("INSERT INTO kategori (id, nama, deskripsi, ikon, urutan, waktu_dibuat, waktu_diperbarui) 
                                 VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$id, $nama, $deskripsi, $ikon, $urutan]);

            $pesan = "Kategori berhasil ditambahkan";
            $tipe = "success";

            header("Location: " . BASE_URL . "/admin/kategori");
            exit;
        } catch (PDOException $e) {
            $pesan = "Gagal menambahkan kategori: " . $e->getMessage();
            $tipe = "danger";
        }
    } else {
        $pesan = implode("<br>", $errors);
        $tipe = "danger";
    }
}

// Ambil urutan tertinggi untuk default urutan baru
$stmt = $db->prepare("SELECT MAX(urutan) as max_urutan FROM kategori");
$stmt->execute();
$result = $stmt->fetch();
$nextUrutan = ($result['max_urutan'] ?? 0) + 1;
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1 class="page-title">Tambah Kategori</h1>
            <p class="text-muted">Tambahkan kategori baru untuk kursus</p>
        </div>
    </div>

    <?php if (!empty($pesan)): ?>
        <div class="alert alert-<?= $tipe ?> alert-dismissible fade show" role="alert">
            <?= $pesan ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="nama" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama" name="nama" value="<?= $_POST['nama'] ?? '' ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?= $_POST['deskripsi'] ?? '' ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="ikon" class="form-label">Ikon (Bootstrap Icons)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-tag" id="preview-icon"></i></span>
                                <input type="text" class="form-control" id="ikon" name="ikon" placeholder="Contoh: book, tag, star" value="<?= $_POST['ikon'] ?? '' ?>">
                            </div>
                            <div class="form-text">Masukkan nama ikon dari <a href="https://icons.getbootstrap.com/" target="_blank">Bootstrap Icons</a> tanpa awalan "bi-"</div>
                        </div>

                        <div class="mb-3">
                            <label for="urutan" class="form-label">Urutan</label>
                            <input type="number" class="form-control" id="urutan" name="urutan" value="<?= $_POST['urutan'] ?? $nextUrutan ?>" min="0">
                            <div class="form-text">Urutan kategori untuk ditampilkan (angka kecil akan ditampilkan lebih dulu)</div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= BASE_URL ?>/admin/kategori" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Icon Preview</h5>
                </div>
                <div class="card-body">
                    <div class="icon-preview text-center p-4">
                        <i class="bi bi-tag display-1" id="large-preview-icon"></i>
                        <p class="mt-3 mb-0">Preview icon yang dipilih</p>
                    </div>

                    <div class="mt-3">
                        <p class="mb-2">Contoh ikon yang tersedia:</p>
                        <div class="d-flex flex-wrap gap-3">
                            <span class="badge rounded-pill bg-light text-dark p-2" role="button" onclick="setIcon('book')">
                                <i class="bi bi-book"></i> book
                            </span>
                            <span class="badge rounded-pill bg-light text-dark p-2" role="button" onclick="setIcon('tag')">
                                <i class="bi bi-tag"></i> tag
                            </span>
                            <span class="badge rounded-pill bg-light text-dark p-2" role="button" onclick="setIcon('code-slash')">
                                <i class="bi bi-code-slash"></i> code-slash
                            </span>
                            <span class="badge rounded-pill bg-light text-dark p-2" role="button" onclick="setIcon('palette')">
                                <i class="bi bi-palette"></i> palette
                            </span>
                            <span class="badge rounded-pill bg-light text-dark p-2" role="button" onclick="setIcon('graph-up')">
                                <i class="bi bi-graph-up"></i> graph-up
                            </span>
                            <span class="badge rounded-pill bg-light text-dark p-2" role="button" onclick="setIcon('camera')">
                                <i class="bi bi-camera"></i> camera
                            </span>
                            <span class="badge rounded-pill bg-light text-dark p-2" role="button" onclick="setIcon('music-note')">
                                <i class="bi bi-music-note"></i> music-note
                            </span>
                            <span class="badge rounded-pill bg-light text-dark p-2" role="button" onclick="setIcon('globe')">
                                <i class="bi bi-globe"></i> globe
                            </span>
                            <span class="badge rounded-pill bg-light text-dark p-2" role="button" onclick="setIcon('star')">
                                <i class="bi bi-star"></i> star
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const iconInput = document.getElementById('ikon');
        const previewIcon = document.getElementById('preview-icon');
        const largePreviewIcon = document.getElementById('large-preview-icon');

        iconInput.addEventListener('input', updateIconPreview);

        function updateIconPreview() {
            const iconName = iconInput.value.trim();
            if (iconName) {
                previewIcon.className = `bi bi-${iconName}`;
                largePreviewIcon.className = `bi bi-${iconName} display-1`;
            } else {
                previewIcon.className = 'bi bi-tag';
                largePreviewIcon.className = 'bi bi-tag display-1';
            }
        }
    });

    function setIcon(iconName) {
        document.getElementById('ikon').value = iconName;
        document.getElementById('preview-icon').className = `bi bi-${iconName}`;
        document.getElementById('large-preview-icon').className = `bi bi-${iconName} display-1`;
    }
</script>

<?php adminFooter(); ?>