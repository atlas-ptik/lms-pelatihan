<?php
// Path: views/admin/materi/tambah.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Tambah Materi", "Tambah materi pembelajaran baru");

$db = dbConnect();
$pesan = '';
$tipe = '';

// Cek parameter modul_id
if (!isset($_GET['modul_id'])) {
    header("Location: " . BASE_URL . "/admin/materi");
    exit;
}

$modul_id = $_GET['modul_id'];

// Cek apakah modul ada
$stmt = $db->prepare("SELECT m.*, k.judul as kursus_judul, k.id as kursus_id 
                     FROM modul m
                     JOIN kursus k ON m.kursus_id = k.id
                     WHERE m.id = ?");
$stmt->execute([$modul_id]);
$modul = $stmt->fetch();

if (!$modul) {
    header("Location: " . BASE_URL . "/admin/materi");
    exit;
}

// Cek urutan terakhir untuk materi pada modul ini
$stmt = $db->prepare("SELECT MAX(urutan) as max_urutan FROM materi WHERE modul_id = ?");
$stmt->execute([$modul_id]);
$result = $stmt->fetch();
$nextUrutan = ($result['max_urutan'] ?? 0) + 1;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $tipe = trim($_POST['tipe'] ?? '');
    $durasi_menit = !empty($_POST['durasi_menit']) ? intval($_POST['durasi_menit']) : null;
    $urutan = intval($_POST['urutan'] ?? $nextUrutan);

    // Variabel untuk menyimpan konten, file_path, dan video_url
    $konten = null;
    $file_path = null;
    $video_url = null;

    $errors = [];

    if (empty($judul)) {
        $errors[] = "Judul materi harus diisi";
    }

    if (empty($tipe) || !in_array($tipe, ['video', 'artikel', 'dokumen', 'quiz', 'tugas'])) {
        $errors[] = "Tipe materi harus dipilih";
    }

    // Validasi berdasarkan tipe materi
    if (empty($errors)) {
        switch ($tipe) {
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
                if (empty($_FILES['file_dokumen']['name'])) {
                    $errors[] = "File dokumen harus diunggah";
                } else {
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

            case 'quiz':
                // Validasi untuk quiz akan dilakukan pada halaman pengelolaan quiz
                break;

            case 'tugas':
                // Validasi untuk tugas akan dilakukan pada halaman pengelolaan tugas
                break;
        }
    }

    if (empty($errors)) {
        try {
            // Upload file jika tipe dokumen
            if ($tipe === 'dokumen' && !empty($_FILES['file_dokumen']['name'])) {
                $upload_dir = BASE_PATH . '/uploads/materi/';

                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_path = uniqid() . '_' . $file_name;
                move_uploaded_file($file_tmp, $upload_dir . $file_path);
            }

            // Simpan data materi
            $id = generate_uuid();
            $stmt = $db->prepare("INSERT INTO materi (id, modul_id, judul, tipe, konten, file_path, video_url, durasi_menit, urutan, waktu_dibuat, waktu_diperbarui) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$id, $modul_id, $judul, $tipe, $konten, $file_path, $video_url, $durasi_menit, $urutan]);

            // Jika tipe quiz atau tugas, redirect ke halaman pengelolaan quiz/tugas
            if ($tipe === 'quiz') {
                // Buat entri quiz
                $quiz_id = generate_uuid();
                $stmt = $db->prepare("INSERT INTO quiz (id, materi_id, judul, deskripsi, nilai_lulus, waktu_dibuat, waktu_diperbarui) 
                                     VALUES (?, ?, ?, ?, 70, NOW(), NOW())");
                $stmt->execute([$quiz_id, $id, $judul, "Quiz untuk " . $judul]);

                $pesan = "Materi quiz berhasil ditambahkan. Silakan kelola pertanyaan quiz.";
                $tipe = "success";
                header("Location: " . BASE_URL . "/admin/quiz?materi_id=" . $id);
                exit;
            } elseif ($tipe === 'tugas') {
                // Buat entri tugas
                $tugas_id = generate_uuid();
                $stmt = $db->prepare("INSERT INTO tugas (id, materi_id, judul, deskripsi, nilai_maksimal, tipe_pengumpulan, waktu_dibuat, waktu_diperbarui) 
                                     VALUES (?, ?, ?, ?, 100, 'file', NOW(), NOW())");
                $stmt->execute([$tugas_id, $id, $judul, "Tugas untuk " . $judul]);

                $pesan = "Materi tugas berhasil ditambahkan. Silakan kelola detail tugas.";
                $tipe = "success";
                header("Location: " . BASE_URL . "/admin/tugas?materi_id=" . $id);
                exit;
            } else {
                $pesan = "Materi berhasil ditambahkan";
                $tipe = "success";
                header("Location: " . BASE_URL . "/admin/materi?modul_id=" . $modul_id);
                exit;
            }
        } catch (PDOException $e) {
            $pesan = "Gagal menambahkan materi: " . $e->getMessage();
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
            <h1 class="page-title">Tambah Materi</h1>
            <p class="text-muted">
                Kursus: <strong><?= htmlspecialchars($modul['kursus_judul']) ?></strong> |
                Modul: <strong><?= htmlspecialchars($modul['judul']) ?></strong>
            </p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?= BASE_URL ?>/admin/materi?modul_id=<?= $modul_id ?>" class="btn btn-outline-secondary">
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

    <div class="card">
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="judul" class="form-label">Judul Materi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="judul" name="judul" value="<?= $_POST['judul'] ?? '' ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="tipe" class="form-label">Tipe Materi <span class="text-danger">*</span></label>
                        <select class="form-select" id="tipe" name="tipe" required>
                            <option value="">Pilih Tipe Materi</option>
                            <option value="video" <?= (isset($_POST['tipe']) && $_POST['tipe'] === 'video') ? 'selected' : '' ?>>Video</option>
                            <option value="artikel" <?= (isset($_POST['tipe']) && $_POST['tipe'] === 'artikel') ? 'selected' : '' ?>>Artikel</option>
                            <option value="dokumen" <?= (isset($_POST['tipe']) && $_POST['tipe'] === 'dokumen') ? 'selected' : '' ?>>Dokumen</option>
                            <option value="quiz" <?= (isset($_POST['tipe']) && $_POST['tipe'] === 'quiz') ? 'selected' : '' ?>>Quiz</option>
                            <option value="tugas" <?= (isset($_POST['tipe']) && $_POST['tipe'] === 'tugas') ? 'selected' : '' ?>>Tugas</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="urutan" class="form-label">Urutan</label>
                        <input type="number" class="form-control" id="urutan" name="urutan" value="<?= $_POST['urutan'] ?? $nextUrutan ?>" min="1">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="durasi_menit" class="form-label">Durasi (menit)</label>
                    <input type="number" class="form-control" id="durasi_menit" name="durasi_menit" value="<?= $_POST['durasi_menit'] ?? '' ?>">
                    <div class="form-text">Estimasi waktu yang diperlukan untuk menyelesaikan materi ini (kosongkan jika tidak perlu).</div>
                </div>

                <!-- Form untuk Video -->
                <div id="form-video" class="materi-form" style="display: none;">
                    <div class="mb-3">
                        <label for="video_url" class="form-label">URL Video <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="video_url" name="video_url" value="<?= $_POST['video_url'] ?? '' ?>">
                        <div class="form-text">Masukkan URL video dari YouTube, Vimeo, atau platform video lainnya.</div>
                    </div>

                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle me-2"></i>Tips Menambahkan Video</h6>
                        <ul class="mb-0">
                            <li>Gunakan URL video yang dapat diakses publik.</li>
                            <li>Untuk YouTube, gunakan format: https://www.youtube.com/watch?v=VIDEO_ID atau https://youtu.be/VIDEO_ID</li>
                            <li>Untuk Vimeo, gunakan format: https://vimeo.com/VIDEO_ID</li>
                        </ul>
                    </div>
                </div>

                <!-- Form untuk Artikel -->
                <div id="form-artikel" class="materi-form" style="display: none;">
                    <div class="mb-3">
                        <label for="konten" class="form-label">Konten Artikel <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="konten" name="konten" rows="10"><?= $_POST['konten'] ?? '' ?></textarea>
                        <div class="form-text">Tulis konten artikel dengan format yang baik. Gunakan paragraf, poin-poin, dan penekanan teks jika diperlukan.</div>
                    </div>
                </div>

                <!-- Form untuk Dokumen -->
                <div id="form-dokumen" class="materi-form" style="display: none;">
                    <div class="mb-3">
                        <label for="file_dokumen" class="form-label">File Dokumen <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" id="file_dokumen" name="file_dokumen">
                        <div class="form-text">Format yang didukung: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, ZIP, RAR. Maksimal 10MB.</div>
                    </div>
                </div>

                <!-- Form untuk Quiz -->
                <div id="form-quiz" class="materi-form" style="display: none;">
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle me-2"></i>Informasi Quiz</h6>
                        <p>Setelah menambahkan materi quiz, Anda akan diarahkan ke halaman pengelolaan quiz untuk menambahkan pertanyaan dan jawaban.</p>
                    </div>
                </div>

                <!-- Form untuk Tugas -->
                <div id="form-tugas" class="materi-form" style="display: none;">
                    <div class="alert alert-info">
                        <h6><i class="bi bi-info-circle me-2"></i>Informasi Tugas</h6>
                        <p>Setelah menambahkan materi tugas, Anda akan diarahkan ke halaman pengelolaan tugas untuk mengatur detail tugas dan penilaian.</p>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= BASE_URL ?>/admin/materi?modul_id=<?= $modul_id ?>" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Materi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tampilkan form sesuai tipe materi yang dipilih
        const tipeMateri = document.getElementById('tipe');
        const materiForms = document.querySelectorAll('.materi-form');

        // Menampilkan form awal jika ada nilai default
        if (tipeMateri.value) {
            showForm(tipeMateri.value);
        }

        tipeMateri.addEventListener('change', function() {
            showForm(this.value);
        });

        function showForm(tipe) {
            // Sembunyikan semua form
            materiForms.forEach(form => {
                form.style.display = 'none';
            });

            // Tampilkan form yang sesuai
            if (tipe) {
                const formToShow = document.getElementById('form-' + tipe);
                if (formToShow) {
                    formToShow.style.display = 'block';
                }
            }
        }

        // Text editor sederhana untuk konten artikel (jika diperlukan)
        const kontenTextarea = document.getElementById('konten');
        if (kontenTextarea) {
            // Di sini bisa ditambahkan inisialisasi text editor sederhana jika diperlukan
        }
    });
</script>

<?php adminFooter(); ?>