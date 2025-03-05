<?php
// Path: views/admin/tugas/edit.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Sambungkan ke database
$db = dbConnect();

// Ambil ID tugas dari URL
$tugas_id = $_GET['id'] ?? '';

if (empty($tugas_id)) {
    header('Location: ' . BASE_URL . '/admin/tugas');
    exit;
}

// Query untuk mendapatkan data tugas yang akan diedit
$sql_tugas = "
    SELECT 
        t.*,
        m.judul AS materi_judul,
        mo.judul AS modul_judul,
        k.judul AS kursus_judul,
        (SELECT COUNT(*) FROM pengumpulan_tugas WHERE tugas_id = t.id) AS jumlah_pengumpulan
    FROM 
        tugas t
    JOIN 
        materi m ON t.materi_id = m.id
    JOIN 
        modul mo ON m.modul_id = mo.id
    JOIN 
        kursus k ON mo.kursus_id = k.id
    WHERE 
        t.id = :id
";

try {
    $stmt = $db->prepare($sql_tugas);
    $stmt->bindValue(':id', $tugas_id);
    $stmt->execute();
    $tugas = $stmt->fetch();

    if (!$tugas) {
        header('Location: ' . BASE_URL . '/admin/tugas');
        exit;
    }
} catch (PDOException $e) {
    die('Terjadi kesalahan: ' . $e->getMessage());
}

// Proses form jika di-submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input
    $materi_id = $_POST['materi_id'] ?? '';
    $judul = $_POST['judul'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $tenggat_waktu = !empty($_POST['tenggat_waktu']) ? $_POST['tenggat_waktu'] . ' ' . ($_POST['tenggat_jam'] ?? '23:59') : null;
    $nilai_maksimal = $_POST['nilai_maksimal'] ?? 100;
    $tipe_pengumpulan = $_POST['tipe_pengumpulan'] ?? 'file';
    $file_lampiran = $tugas['file_lampiran']; // Default ke file yang sudah ada
    $hapus_lampiran = isset($_POST['hapus_lampiran']) && $_POST['hapus_lampiran'] == 1;

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

    // Jika opsi hapus lampiran dipilih, kosongkan file_lampiran
    if ($hapus_lampiran) {
        // Hapus file fisik jika ada
        if (!empty($file_lampiran)) {
            $file_path = BASE_PATH . '/uploads/tugas/' . $file_lampiran;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $file_lampiran = '';
    }
    // Upload file lampiran baru jika ada
    elseif (isset($_FILES['file_lampiran']) && $_FILES['file_lampiran']['error'] === UPLOAD_ERR_OK) {
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

            // Hapus file lama jika ada
            if (!empty($tugas['file_lampiran'])) {
                $old_file_path = $upload_dir . $tugas['file_lampiran'];
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
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

    // Jika tidak ada error, update ke database
    if (empty($errors)) {
        try {
            $sql = "
                UPDATE tugas SET
                    materi_id = :materi_id,
                    judul = :judul,
                    deskripsi = :deskripsi,
                    tenggat_waktu = :tenggat_waktu,
                    nilai_maksimal = :nilai_maksimal,
                    file_lampiran = :file_lampiran,
                    tipe_pengumpulan = :tipe_pengumpulan,
                    waktu_diperbarui = CURRENT_TIMESTAMP
                WHERE id = :id
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
                'message' => 'Tugas berhasil diperbarui'
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

// Format tenggat waktu untuk form
$tenggat_tanggal = '';
$tenggat_jam = '';
if (!empty($tugas['tenggat_waktu'])) {
    $tenggat_tanggal = date('Y-m-d', strtotime($tugas['tenggat_waktu']));
    $tenggat_jam = date('H:i', strtotime($tugas['tenggat_waktu']));
}

adminHeader("Edit Tugas", "Edit data tugas");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Tugas</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/tugas">Tugas</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold">Informasi Tugas</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">ID Tugas</th>
                                    <td><?= $tugas['id'] ?></td>
                                </tr>
                                <tr>
                                    <th>Kursus</th>
                                    <td><?= htmlspecialchars($tugas['kursus_judul']) ?></td>
                                </tr>
                                <tr>
                                    <th>Modul</th>
                                    <td><?= htmlspecialchars($tugas['modul_judul']) ?></td>
                                </tr>
                                <tr>
                                    <th>Materi</th>
                                    <td><?= htmlspecialchars($tugas['materi_judul']) ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Dibuat</th>
                                    <td><?= date('d M Y H:i', strtotime($tugas['waktu_dibuat'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Diperbarui</th>
                                    <td><?= date('d M Y H:i', strtotime($tugas['waktu_diperbarui'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Pengumpulan</th>
                                    <td>
                                        <span class="badge bg-info"><?= number_format($tugas['jumlah_pengumpulan']) ?> pengumpulan</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td>
                                        <?php
                                        if (empty($tugas['tenggat_waktu'])) {
                                            echo '<span class="badge bg-primary">Tanpa Tenggat</span>';
                                        } else {
                                            $tenggat = new DateTime($tugas['tenggat_waktu']);
                                            $sekarang = new DateTime();
                                            if ($tenggat < $sekarang) {
                                                echo '<span class="badge bg-danger">Berakhir</span>';
                                            } else {
                                                echo '<span class="badge bg-success">Aktif</span>';
                                            }
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">Form Edit Tugas</h6>
            <div>
                <a href="<?= BASE_URL ?>/admin/tugas/nilai?id=<?= $tugas_id ?>" class="btn btn-sm btn-success me-2">
                    <i class="bi bi-award"></i> Nilai Pengumpulan
                </a>
                <a href="<?= BASE_URL ?>/admin/tugas" class="btn btn-sm btn-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
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
                                <option value="<?= $materi['id'] ?>" <?= $tugas['materi_id'] == $materi['id'] ? 'selected' : '' ?>>
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
                        <input type="text" class="form-control" id="judul" name="judul" value="<?= htmlspecialchars($tugas['judul']) ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="deskripsi" class="form-label">Deskripsi Tugas <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="5" required><?= htmlspecialchars($tugas['deskripsi']) ?></textarea>
                        <div class="form-text">Jelaskan tentang tugas ini, instruksi, dan kriteria penilaian</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="tenggat_waktu" class="form-label">Tenggat Waktu (Tanggal)</label>
                        <input type="date" class="form-control" id="tenggat_waktu" name="tenggat_waktu" value="<?= $tenggat_tanggal ?>">
                        <div class="form-text">Biarkan kosong jika tidak ada tenggat</div>
                    </div>
                    <div class="col-md-2">
                        <label for="tenggat_jam" class="form-label">Tenggat Waktu (Jam)</label>
                        <input type="time" class="form-control" id="tenggat_jam" name="tenggat_jam" value="<?= $tenggat_jam ? $tenggat_jam : '23:59' ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="nilai_maksimal" class="form-label">Nilai Maksimal</label>
                        <input type="number" class="form-control" id="nilai_maksimal" name="nilai_maksimal" min="1" max="100" value="<?= htmlspecialchars($tugas['nilai_maksimal']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="tipe_pengumpulan" class="form-label">Tipe Pengumpulan</label>
                        <select class="form-select" id="tipe_pengumpulan" name="tipe_pengumpulan">
                            <option value="file" <?= $tugas['tipe_pengumpulan'] == 'file' ? 'selected' : '' ?>>File</option>
                            <option value="teks" <?= $tugas['tipe_pengumpulan'] == 'teks' ? 'selected' : '' ?>>Teks</option>
                            <option value="keduanya" <?= $tugas['tipe_pengumpulan'] == 'keduanya' ? 'selected' : '' ?>>File & Teks</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-8">
                        <label for="file_lampiran" class="form-label">File Lampiran</label>
                        <?php if (!empty($tugas['file_lampiran'])): ?>
                            <div class="mb-2">
                                <strong>File saat ini:</strong>
                                <a href="<?= BASE_URL ?>/uploads/tugas/<?= $tugas['file_lampiran'] ?>" target="_blank">
                                    <?= $tugas['file_lampiran'] ?>
                                </a>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="hapus_lampiran" name="hapus_lampiran" value="1">
                                    <label class="form-check-label" for="hapus_lampiran">
                                        Hapus file lampiran
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="file_lampiran" name="file_lampiran">
                        <div class="form-text">Format: PDF, DOC, DOCX, XLS, XLSX, ZIP, RAR, JPG, PNG, TXT. Maks: 10MB</div>
                    </div>
                </div>

                <?php if ($tugas['jumlah_pengumpulan'] > 0): ?>
                    <div class="alert alert-warning mb-4">
                        <i class="bi bi-exclamation-triangle me-2"></i> <strong>Perhatian:</strong> Tugas ini sudah memiliki <?= number_format($tugas['jumlah_pengumpulan']) ?> pengumpulan. Perubahan pada beberapa pengaturan mungkin memengaruhi siswa yang sudah mengumpulkan.
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>/admin/tugas" class="btn btn-secondary">Batal</a>
                    <div>
                        <a href="<?= BASE_URL ?>/admin/tugas/nilai?id=<?= $tugas_id ?>" class="btn btn-success me-2">
                            <i class="bi bi-award"></i> Nilai Pengumpulan
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inisialisasi editor jika diperlukan
        if (typeof ClassicEditor !== 'undefined') {
            ClassicEditor
                .create(document.querySelector('#deskripsi'))
                .catch(error => {
                    console.error(error);
                });
        }

        // Handler untuk checkbox hapus lampiran
        const hapusLampiranCheckbox = document.getElementById('hapus_lampiran');
        const fileLampiranInput = document.getElementById('file_lampiran');

        if (hapusLampiranCheckbox) {
            hapusLampiranCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    fileLampiranInput.disabled = true;
                } else {
                    fileLampiranInput.disabled = false;
                }
            });
        }
    });
</script>

<?php adminFooter(); ?>