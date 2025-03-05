<?php
// Path: views/admin/kategori/edit.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Edit Kategori", "Edit kategori kursus di Atlas LMS");

$db = dbConnect();
$pesan = '';
$tipe = '';

if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "/admin/kategori");
    exit;
}

$id = $_GET['id'];

// Cek apakah kategori ada
$stmt = $db->prepare("SELECT * FROM kategori WHERE id = ?");
$stmt->execute([$id]);
$kategori = $stmt->fetch();

if (!$kategori) {
    header("Location: " . BASE_URL . "/admin/kategori");
    exit;
}

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
            $stmt = $db->prepare("UPDATE kategori SET nama = ?, deskripsi = ?, ikon = ?, urutan = ?, waktu_diperbarui = NOW() WHERE id = ?");
            $stmt->execute([$nama, $deskripsi, $ikon, $urutan, $id]);

            $pesan = "Kategori berhasil diperbarui";
            $tipe = "success";

            // Ambil data terbaru setelah update
            $stmt = $db->prepare("SELECT * FROM kategori WHERE id = ?");
            $stmt->execute([$id]);
            $kategori = $stmt->fetch();
        } catch (PDOException $e) {
            $pesan = "Gagal memperbarui kategori: " . $e->getMessage();
            $tipe = "danger";
        }
    } else {
        $pesan = implode("<br>", $errors);
        $tipe = "danger";
    }
}

// Hitung jumlah kursus yang terkait dengan kategori ini
$stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM kursus_kategori WHERE kategori_id = ?");
$stmt->execute([$id]);
$jumlahKursus = $stmt->fetch()['jumlah'];
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col">
            <h1 class="page-title">Edit Kategori</h1>
            <p class="text-muted">Edit kategori yang sudah ada</p>
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
                            <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($kategori['nama']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="deskripsi" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?= htmlspecialchars($kategori['deskripsi'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="ikon" class="form-label">Ikon (Bootstrap Icons)</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-<?= !empty($kategori['ikon']) ? htmlspecialchars($kategori['ikon']) : 'tag' ?>" id="preview-icon"></i>
                                </span>
                                <input type="text" class="form-control" id="ikon" name="ikon" placeholder="Contoh: book, tag, star" value="<?= htmlspecialchars($kategori['ikon'] ?? '') ?>">
                            </div>
                            <div class="form-text">Masukkan nama ikon dari <a href="https://icons.getbootstrap.com/" target="_blank">Bootstrap Icons</a> tanpa awalan "bi-"</div>
                        </div>

                        <div class="mb-3">
                            <label for="urutan" class="form-label">Urutan</label>
                            <input type="number" class="form-control" id="urutan" name="urutan" value="<?= $kategori['urutan'] ?>" min="0">
                            <div class="form-text">Urutan kategori untuk ditampilkan (angka kecil akan ditampilkan lebih dulu)</div>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= BASE_URL ?>/admin/kategori" class="btn btn-secondary">Kembali</a>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Icon Preview</h5>
                </div>
                <div class="card-body">
                    <div class="icon-preview text-center p-4">
                        <i class="bi bi-<?= !empty($kategori['ikon']) ? htmlspecialchars($kategori['ikon']) : 'tag' ?> display-1" id="large-preview-icon"></i>
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
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Kategori</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            ID Kategori
                            <span class="text-muted small"><?= $id ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Jumlah Kursus
                            <span class="badge bg-primary rounded-pill"><?= $jumlahKursus ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Dibuat Pada
                            <span class="text-muted small"><?= date('d/m/Y H:i', strtotime($kategori['waktu_dibuat'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Diperbarui Pada
                            <span class="text-muted small"><?= date('d/m/Y H:i', strtotime($kategori['waktu_diperbarui'])) ?></span>
                        </li>
                    </ul>
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