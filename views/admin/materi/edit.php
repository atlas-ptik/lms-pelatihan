<?php
// Path: views/admin/materi/edit.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Edit Materi", "Edit materi pembelajaran");

$db = dbConnect();
$pesan = '';
$tipe = '';

// Cek parameter id
if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "/admin/materi");
    exit;
}

$id = $_GET['id'];

// Ambil data materi
$stmt = $db->prepare("
    SELECT m.*, mo.judul as modul_judul, mo.id as modul_id, k.judul as kursus_judul, k.id as kursus_id 
    FROM materi m
    JOIN modul mo ON m.modul_id = mo.id
    JOIN kursus k ON mo.kursus_id = k.id
    WHERE m.id = ?
");
$stmt->execute([$id]);
$materi = $stmt->fetch();

if (!$materi) {
    header("Location: " . BASE_URL . "/admin/materi");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $durasi_menit = !empty($_POST['durasi_menit']) ? intval($_POST['durasi_menit']) : null;
    $urutan = intval($_POST['urutan'] ?? $materi['urutan']);

    // Variabel untuk menyimpan konten, file_path, dan video_url
    $konten = $materi['konten'];
    $file_path = $materi['file_path'];
    $video_url = $materi['video_url'];

    $errors = [];

    if (empty($judul)) {
        $errors[] = "Judul materi harus diisi";
    }

    // Validasi berdasarkan tipe materi
    if (empty($errors)) {
        switch ($materi['tipe']) {
            case 'video':
                $video_url = trim($_POST['video_url'] ?? '');
                if (empty($video_url)) {
                    $errors[] = "URL video harus diisi";
                }
                break;

            case 'artikel':
                $konten = trim($_POST['konten'] ?? '');
                if (empty($konten)) {
                    $errors[] = "Konten artikel harus diisi";
                }
                break;

            case 'dokumen':
                // Jika ada file baru yang diupload
                if (!empty($_FILES['file_dokumen']['name'])) {
                    $file_name = $_FILES['file_dokumen']['name'];
                    $file_size = $_FILES['file_dokumen']['size'];
                    $file_tmp = $_FILES['file_dokumen']['tmp_name'];
                    $file_type = $_FILES['file_dokumen']['type'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                    $extensions = ["pdf", "doc", "docx", "ppt", "pptx", "xls", "xlsx", "txt", "zip", "rar"];

                    if (!in_array($file_ext, $extensions)) {
                        $errors[] = "Format file tidak didukung";
                    }

                    if ($file_size > 10485760) { // 10MB
                        $errors[] = "Ukuran file tidak boleh lebih dari 10MB";
                    }
                }
                break;
        }
    }

    if (empty($errors)) {
        try {
            // Upload file baru jika tipe dokumen dan ada file yang diupload
            if ($materi['tipe'] === 'dokumen' && !empty($_FILES['file_dokumen']['name'])) {
                $upload_dir = BASE_PATH . '/uploads/materi/';

                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                // Hapus file lama jika ada
                if (!empty($materi['file_path']) && file_exists($upload_dir . $materi['file_path'])) {
                    unlink($upload_dir . $materi['file_path']);
                }

                $file_path = uniqid() . '_' . $file_name;
                move_uploaded_file($file_tmp, $upload_dir . $file_path);
            }

            // Update data materi
            $stmt = $db->prepare("UPDATE materi SET judul = ?, konten = ?, file_path = ?, video_url = ?, durasi_menit = ?, urutan = ?, waktu_diperbarui = NOW() WHERE id = ?");
            $stmt->execute([$judul, $konten, $file_path, $video_url, $durasi_menit, $urutan, $id]);

            $pesan = "Materi berhasil diperbarui";
            $tipe = "success";

            // Refresh data materi
            $stmt = $db->prepare("
                SELECT m.*, mo.judul as modul_judul, mo.id as modul_id, k.judul as kursus_judul, k.id as kursus_id 
                FROM materi m
                JOIN modul mo ON m.modul_id = mo.id
                JOIN kursus k ON mo.kursus_id = k.id
                WHERE m.id = ?
            ");
            $stmt->execute([$id]);
            $materi = $stmt->fetch();
        } catch (PDOException $e) {
            $pesan = "Gagal memperbarui materi: " . $e->getMessage();
            $tipe = "danger";
        }
    } else {
        $pesan = implode("<br>", $errors);
        $tipe = "danger";
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="page-title">Edit Materi</h1>
            <p class="text-muted">
                Kursus: <strong><?= htmlspecialchars($materi['kursus_judul']) ?></strong> |
                Modul: <strong><?= htmlspecialchars($materi['modul_judul']) ?></strong>
            </p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?= BASE_URL ?>/admin/materi?modul_id=<?= $materi['modul_id'] ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
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
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Edit Materi</h5>
                        <span class="badge <?php
                                            switch ($materi['tipe']) {
                                                case 'video':
                                                    echo 'bg-danger';
                                                    break;
                                                case 'artikel':
                                                    echo 'bg-primary';
                                                    break;
                                                case 'dokumen':
                                                    echo 'bg-secondary';
                                                    break;
                                                case 'quiz':
                                                    echo 'bg-success';
                                                    break;
                                                case 'tugas':
                                                    echo 'bg-warning text-dark';
                                                    break;
                                            }
                                            ?>">
                            <?= ucfirst($materi['tipe']) ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="judul" class="form-label">Judul Materi <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="judul" name="judul" value="<?= htmlspecialchars($materi['judul']) ?>" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="durasi_menit" class="form-label">Durasi (menit)</label>
                                <input type="number" class="form-control" id="durasi_menit" name="durasi_menit" value="<?= $materi['durasi_menit'] ?>">
                                <div class="form-text">Estimasi waktu yang diperlukan untuk menyelesaikan materi ini.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="urutan" class="form-label">Urutan</label>
                                <input type="number" class="form-control" id="urutan" name="urutan" value="<?= $materi['urutan'] ?>" min="1">
                            </div>
                        </div>

                        <?php if ($materi['tipe'] === 'video'): ?>
                            <div class="mb-3">
                                <label for="video_url" class="form-label">URL Video <span class="text-danger">*</span></label>
                                <input type="url" class="form-control" id="video_url" name="video_url" value="<?= htmlspecialchars($materi['video_url']) ?>" required>
                                <div class="form-text">Masukkan URL video dari YouTube, Vimeo, atau platform video lainnya.</div>
                            </div>

                            <?php if (!empty($materi['video_url'])): ?>
                                <div class="mb-3">
                                    <label class="form-label">Preview Video</label>
                                    <div class="ratio ratio-16x9">
                                        <?php
                                        $video_url = $materi['video_url'];

                                        // YouTube embed
                                        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
                                            $video_id = '';

                                            if (strpos($video_url, 'youtube.com/watch?v=') !== false) {
                                                $video_id = substr($video_url, strpos($video_url, 'watch?v=') + 8);
                                                $video_id = strtok($video_id, '&');
                                            } elseif (strpos($video_url, 'youtu.be/') !== false) {
                                                $video_id = substr($video_url, strpos($video_url, 'youtu.be/') + 9);
                                            }

                                            if (!empty($video_id)) {
                                                echo '<iframe width="560" height="315" src="https://www.youtube.com/embed/' . $video_id . '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
                                            } else {
                                                echo '<div class="alert alert-warning">Format URL YouTube tidak valid</div>';
                                            }
                                        }
                                        // Vimeo embed
                                        elseif (strpos($video_url, 'vimeo.com') !== false) {
                                            $video_id = substr($video_url, strrpos($video_url, '/') + 1);
                                            echo '<iframe src="https://player.vimeo.com/video/' . $video_id . '" width="640" height="360" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe>';
                                        }
                                        // Other video URL (direct link)
                                        else {
                                            echo '<div class="alert alert-info">Video URL: <a href="' . htmlspecialchars($video_url) . '" target="_blank">' . htmlspecialchars($video_url) . '</a></div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php elseif ($materi['tipe'] === 'artikel'): ?>
                            <div class="mb-3">
                                <label for="konten" class="form-label">Konten Artikel <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="konten" name="konten" rows="15" required><?= htmlspecialchars($materi['konten']) ?></textarea>
                                <div class="form-text">Tulis konten artikel dengan format yang baik. Gunakan paragraf, poin-poin, dan penekanan teks jika diperlukan.</div>
                            </div>

                        <?php elseif ($materi['tipe'] === 'dokumen'): ?>
                            <div class="mb-3">
                                <label for="file_dokumen" class="form-label">File Dokumen</label>
                                <input type="file" class="form-control" id="file_dokumen" name="file_dokumen">
                                <div class="form-text">Format yang didukung: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, ZIP, RAR. Maksimal 10MB. Kosongkan jika tidak ingin mengubah file.</div>
                            </div>

                            <?php if (!empty($materi['file_path'])): ?>
                                <div class="mb-3">
                                    <label class="form-label">File Saat Ini</label>
                                    <div class="d-flex align-items-center">
                                        <a href="<?= BASE_URL ?>/uploads/materi/<?= $materi['file_path'] ?>" class="btn btn-outline-primary btn-sm me-2" target="_blank">
                                            <i class="bi bi-download"></i> Unduh File
                                        </a>
                                        <span class="text-muted small"><?= $materi['file_path'] ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php elseif ($materi['tipe'] === 'quiz'): ?>
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle me-2"></i>Informasi Quiz</h6>
                                <p class="mb-2">Untuk mengelola pertanyaan dan pengaturan quiz, gunakan tombol "Kelola Quiz" di bawah.</p>
                                <a href="<?= BASE_URL ?>/admin/quiz?materi_id=<?= $id ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-question-circle"></i> Kelola Quiz
                                </a>
                            </div>

                        <?php elseif ($materi['tipe'] === 'tugas'): ?>
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle me-2"></i>Informasi Tugas</h6>
                                <p class="mb-2">Untuk mengelola detail tugas dan pengaturan penilaian, gunakan tombol "Kelola Tugas" di bawah.</p>
                                <a href="<?= BASE_URL ?>/admin/tugas?materi_id=<?= $id ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-clipboard-check"></i> Kelola Tugas
                                </a>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= BASE_URL ?>/admin/materi?modul_id=<?= $materi['modul_id'] ?>" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Materi</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            ID Materi
                            <span class="text-muted small"><?= $id ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Tipe Materi
                            <span class="badge <?php
                                                switch ($materi['tipe']) {
                                                    case 'video':
                                                        echo 'bg-danger';
                                                        break;
                                                    case 'artikel':
                                                        echo 'bg-primary';
                                                        break;
                                                    case 'dokumen':
                                                        echo 'bg-secondary';
                                                        break;
                                                    case 'quiz':
                                                        echo 'bg-success';
                                                        break;
                                                    case 'tugas':
                                                        echo 'bg-warning text-dark';
                                                        break;
                                                }
                                                ?>">
                                <?= ucfirst($materi['tipe']) ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Urutan
                            <span class="badge bg-secondary rounded-pill"><?= $materi['urutan'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Dibuat Pada
                            <span class="text-muted small"><?= date('d/m/Y H:i', strtotime($materi['waktu_dibuat'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Terakhir Diperbarui
                            <span class="text-muted small"><?= date('d/m/Y H:i', strtotime($materi['waktu_diperbarui'])) ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Navigasi</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= BASE_URL ?>/admin/materi?modul_id=<?= $materi['modul_id'] ?>" class="btn btn-outline-primary">
                            <i class="bi bi-list-ul"></i> Daftar Materi
                        </a>
                        <a href="<?= BASE_URL ?>/admin/modul?kursus_id=<?= $materi['kursus_id'] ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-folder"></i> Daftar Modul
                        </a>
                        <a href="<?= BASE_URL ?>/admin/kursus/edit?id=<?= $materi['kursus_id'] ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-book"></i> Edit Kursus
                        </a>
                        <?php if ($materi['tipe'] === 'quiz'): ?>
                            <a href="<?= BASE_URL ?>/admin/quiz?materi_id=<?= $id ?>" class="btn btn-success">
                                <i class="bi bi-question-circle"></i> Kelola Quiz
                            </a>
                        <?php elseif ($materi['tipe'] === 'tugas'): ?>
                            <a href="<?= BASE_URL ?>/admin/tugas?materi_id=<?= $id ?>" class="btn btn-warning">
                                <i class="bi bi-clipboard-check"></i> Kelola Tugas
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php adminFooter(); ?>