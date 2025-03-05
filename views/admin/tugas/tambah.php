<?php
// Path: views/admin/tugas/tambah.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Sambungkan ke database
$db = dbConnect();

// Proses form jika di-submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input
    $materi_id = $_POST['materi_id'] ?? '';
    $judul = $_POST['judul'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $tenggat_waktu = !empty($_POST['tenggat_waktu']) ? $_POST['tenggat_waktu'] . ' ' . ($_POST['tenggat_jam'] ?? '23:59') : null;
    $nilai_maksimal = $_POST['nilai_maksimal'] ?? 100;
    $tipe_pengumpulan = $_POST['tipe_pengumpulan'] ?? 'file';
    $file_lampiran = '';

    $errors = [];

    // Validasi field yang wajib diisi
    if (empty($materi_id)) {
        $errors[] = 'Materi wajib dipilih';
    }

    if (empty($judul)) {
        $errors[] = 'Judul tugas wajib diisi';
    }

    if (empty($deskripsi)) {
        $errors[] = 'Deskripsi tugas wajib diisi';
    }

    // Upload file lampiran jika ada
    if (isset($_FILES['file_lampiran']) && $_FILES['file_lampiran']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/x-rar-compressed', 'image/jpeg', 'image/png', 'text/plain'];
        $max_size = 10 * 1024 * 1024; // 10MB

        if (!in_array($_FILES['file_lampiran']['type'], $allowed_types) && !empty($_FILES['file_lampiran']['type'])) {
            $errors[] = 'Tipe file tidak didukung. Format yang diizinkan: PDF, DOC, DOCX, XLS, XLSX, ZIP, RAR, JPG, PNG, TXT.';
        } elseif ($_FILES['file_lampiran']['size'] > $max_size) {
            $errors[] = 'Ukuran file terlalu besar. Maksimal 10MB.';
        } else {
            $upload_dir = BASE_PATH . '/uploads/tugas/';

            // Buat direktori jika belum ada
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['file_lampiran']['name'], PATHINFO_EXTENSION);
            $new_filename = 'tugas_' . uniqid() . '.' . $file_extension;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['file_lampiran']['tmp_name'], $destination)) {
                $file_lampiran = $new_filename;
            } else {
                $errors[] = 'Gagal mengupload file lampiran.';
            }
        }
    }

    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        try {
            $tugas_id = generate_uuid();

            $sql = "
                INSERT INTO tugas (
                    id, materi_id, judul, deskripsi, tenggat_waktu, 
                    nilai_maksimal, file_lampiran, tipe_pengumpulan, 
                    waktu_dibuat, waktu_diperbarui
                ) VALUES (
                    :id, :materi_id, :judul, :deskripsi, :tenggat_waktu, 
                    :nilai_maksimal, :file_lampiran, :tipe_pengumpulan, 
                    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )
            ";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $tugas_id);
            $stmt->bindValue(':materi_id', $materi_id);
            $stmt->bindValue(':judul', $judul);
            $stmt->bindValue(':deskripsi', $deskripsi);
            $stmt->bindValue(':tenggat_waktu', $tenggat_waktu);
            $stmt->bindValue(':nilai_maksimal', $nilai_maksimal);
            $stmt->bindValue(':file_lampiran', $file_lampiran);
            $stmt->bindValue(':tipe_pengumpulan', $tipe_pengumpulan);

            $stmt->execute();

            // Set flash message dan redirect
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Tugas berhasil ditambahkan'
            ];

            header('Location: ' . BASE_URL . '/admin/tugas');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Query untuk mendapatkan daftar materi (untuk dropdown)
$sql_materi = "
    SELECT 
        m.id, 
        m.judul,
        mo.judul as modul_judul,
        k.judul as kursus_judul,
        CONCAT(k.judul, ' - ', mo.judul, ' - ', m.judul) AS materi_lengkap
    FROM 
        materi m
    JOIN 
        modul mo ON m.modul_id = mo.id
    JOIN 
        kursus k ON mo.kursus_id = k.id
    WHERE 
        m.tipe IN ('tugas', 'video', 'artikel', 'dokumen')
    ORDER BY 
        k.judul, mo.urutan, m.urutan
";
try {
    $stmt = $db->query($sql_materi);
    $materi_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $materi_list = [];
}

// Mendapatkan materi_id dari URL jika ada
$selected_materi_id = $_GET['materi_id'] ?? '';

adminHeader("Tambah Tugas", "Buat tugas baru");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tambah Tugas</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/tugas">Tugas</a></li>
            <li class="breadcrumb-item active">Tambah</li>
        </ol>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">Form Tambah Tugas</h6>
            <a href="<?= BASE_URL ?>/admin/tugas" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
        <div class="card-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= $error ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="materi_id" class="form-label">Materi <span class="text-danger">*</span></label>
                        <select class="form-select" id="materi_id" name="materi_id" required>
                            <option value="">-- Pilih Materi --</option>
                            <?php foreach ($materi_list as $materi): ?>
                                <option value="<?= $materi['id'] ?>" <?= $selected_materi_id == $materi['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($materi['materi_lengkap']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Pilih materi tempat tugas ini akan ditambahkan</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="judul" class="form-label">Judul Tugas <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="judul" name="judul" value="<?= isset($_POST['judul']) ? htmlspecialchars($_POST['judul']) : '' ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="deskripsi" class="form-label">Deskripsi Tugas <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" required><?= isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : '' ?></textarea>
                        <div class="form-text">Jelaskan tentang tugas ini, instruksi, dan kriteria penilaian</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="tenggat_waktu" class="form-label">Tenggat Waktu (Tanggal)</label>
                        <input type="date" class="form-control" id="tenggat_waktu" name="tenggat_waktu" value="<?= isset($_POST['tenggat_waktu']) ? htmlspecialchars($_POST['tenggat_waktu']) : '' ?>">
                        <div class="form-text">Biarkan kosong jika tidak ada tenggat</div>
                    </div>
                    <div class="col-md-2">
                        <label for="tenggat_jam" class="form-label">Tenggat Waktu (Jam)</label>
                        <input type="time" class="form-control" id="tenggat_jam" name="tenggat_jam" value="<?= isset($_POST['tenggat_jam']) ? htmlspecialchars($_POST['tenggat_jam']) : '23:59' ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="nilai_maksimal" class="form-label">Nilai Maksimal</label>
                        <input type="number" class="form-control" id="nilai_maksimal" name="nilai_maksimal" min="1" max="100" value="<?= isset($_POST['nilai_maksimal']) ? htmlspecialchars($_POST['nilai_maksimal']) : '100' ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="tipe_pengumpulan" class="form-label">Tipe Pengumpulan</label>
                        <select class="form-select" id="tipe_pengumpulan" name="tipe_pengumpulan">
                            <option value="file" <?= isset($_POST['tipe_pengumpulan']) && $_POST['tipe_pengumpulan'] == 'file' ? 'selected' : '' ?>>File</option>
                            <option value="teks" <?= isset($_POST['tipe_pengumpulan']) && $_POST['tipe_pengumpulan'] == 'teks' ? 'selected' : '' ?>>Teks</option>
                            <option value="keduanya" <?= isset($_POST['tipe_pengumpulan']) && $_POST['tipe_pengumpulan'] == 'keduanya' ? 'selected' : '' ?>>File & Teks</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-8">
                        <label for="file_lampiran" class="form-label">File Lampiran (opsional)</label>
                        <input type="file" class="form-control" id="file_lampiran" name="file_lampiran">
                        <div class="form-text">Format: PDF, DOC, DOCX, XLS, XLSX, ZIP, RAR, JPG, PNG, TXT. Maks: 10MB</div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>/admin/tugas" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan Tugas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Focus pada input pertama
        document.getElementById('materi_id').focus();

        // Inisialisasi editor jika diperlukan
        if (typeof ClassicEditor !== 'undefined') {
            ClassicEditor
                .create(document.querySelector('#deskripsi'))
                .catch(error => {
                    console.error(error);
                });
        }
    });
</script>

<?php adminFooter(); ?>