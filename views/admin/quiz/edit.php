<?php
// Path: views/admin/quiz/edit.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Sambungkan ke database
$db = dbConnect();

// Ambil ID quiz dari URL
$quiz_id = $_GET['id'] ?? '';

if (empty($quiz_id)) {
    header('Location: ' . BASE_URL . '/admin/quiz');
    exit;
}

// Proses form jika di-submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi input
    $materi_id = $_POST['materi_id'] ?? '';
    $judul = $_POST['judul'] ?? '';
    $deskripsi = $_POST['deskripsi'] ?? '';
    $durasi_menit = $_POST['durasi_menit'] !== '' ? $_POST['durasi_menit'] : null;
    $nilai_lulus = $_POST['nilai_lulus'] ?? 70;
    $maksimal_percobaan = $_POST['maksimal_percobaan'] !== '' ? $_POST['maksimal_percobaan'] : null;
    $acak_pertanyaan = isset($_POST['acak_pertanyaan']) ? 1 : 0;

    $errors = [];

    // Validasi field yang wajib diisi
    if (empty($materi_id)) {
        $errors[] = 'Materi wajib dipilih';
    }

    if (empty($judul)) {
        $errors[] = 'Judul quiz wajib diisi';
    }

    // Jika tidak ada error, update ke database
    if (empty($errors)) {
        try {
            $sql = "
                UPDATE quiz SET
                    materi_id = :materi_id,
                    judul = :judul,
                    deskripsi = :deskripsi,
                    durasi_menit = :durasi_menit,
                    nilai_lulus = :nilai_lulus,
                    maksimal_percobaan = :maksimal_percobaan,
                    acak_pertanyaan = :acak_pertanyaan,
                    waktu_diperbarui = CURRENT_TIMESTAMP
                WHERE id = :id
            ";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $quiz_id);
            $stmt->bindValue(':materi_id', $materi_id);
            $stmt->bindValue(':judul', $judul);
            $stmt->bindValue(':deskripsi', $deskripsi);
            $stmt->bindValue(':durasi_menit', $durasi_menit);
            $stmt->bindValue(':nilai_lulus', $nilai_lulus);
            $stmt->bindValue(':maksimal_percobaan', $maksimal_percobaan);
            $stmt->bindValue(':acak_pertanyaan', $acak_pertanyaan);

            $stmt->execute();

            // Set flash message dan redirect
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Quiz berhasil diperbarui'
            ];

            header('Location: ' . BASE_URL . '/admin/quiz');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Query untuk mendapatkan data quiz yang akan diedit
$sql_quiz = "
    SELECT 
        q.*,
        m.judul AS materi_judul,
        mo.judul AS modul_judul,
        k.judul AS kursus_judul,
        (SELECT COUNT(*) FROM pertanyaan_quiz WHERE quiz_id = q.id) AS jumlah_pertanyaan,
        (SELECT COUNT(*) FROM percobaan_quiz WHERE quiz_id = q.id) AS jumlah_percobaan
    FROM 
        quiz q
    JOIN 
        materi m ON q.materi_id = m.id
    JOIN 
        modul mo ON m.modul_id = mo.id
    JOIN 
        kursus k ON mo.kursus_id = k.id
    WHERE 
        q.id = :id
";

try {
    $stmt = $db->prepare($sql_quiz);
    $stmt->bindValue(':id', $quiz_id);
    $stmt->execute();
    $quiz = $stmt->fetch();

    if (!$quiz) {
        header('Location: ' . BASE_URL . '/admin/quiz');
        exit;
    }
} catch (PDOException $e) {
    die('Terjadi kesalahan: ' . $e->getMessage());
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
        m.tipe IN ('quiz', 'video', 'artikel', 'dokumen')
    ORDER BY 
        k.judul, mo.urutan, m.urutan
";
try {
    $stmt = $db->query($sql_materi);
    $materi_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $materi_list = [];
}

adminHeader("Edit Quiz", "Edit data quiz");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Quiz</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/quiz">Quiz</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold">Informasi Quiz</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">ID Quiz</th>
                                    <td><?= $quiz['id'] ?></td>
                                </tr>
                                <tr>
                                    <th>Kursus</th>
                                    <td><?= htmlspecialchars($quiz['kursus_judul']) ?></td>
                                </tr>
                                <tr>
                                    <th>Modul</th>
                                    <td><?= htmlspecialchars($quiz['modul_judul']) ?></td>
                                </tr>
                                <tr>
                                    <th>Materi</th>
                                    <td><?= htmlspecialchars($quiz['materi_judul']) ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="150">Dibuat</th>
                                    <td><?= date('d M Y H:i', strtotime($quiz['waktu_dibuat'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Diperbarui</th>
                                    <td><?= date('d M Y H:i', strtotime($quiz['waktu_diperbarui'])) ?></td>
                                </tr>
                                <tr>
                                    <th>Jumlah Pertanyaan</th>
                                    <td><span class="badge bg-info"><?= number_format($quiz['jumlah_pertanyaan']) ?></span></td>
                                </tr>
                                <tr>
                                    <th>Jumlah Percobaan</th>
                                    <td><span class="badge bg-secondary"><?= number_format($quiz['jumlah_percobaan']) ?></span></td>
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
            <h6 class="m-0 font-weight-bold">Form Edit Quiz</h6>
            <div>
                <a href="<?= BASE_URL ?>/admin/quiz/pertanyaan?quiz_id=<?= $quiz_id ?>" class="btn btn-sm btn-success me-2">
                    <i class="bi bi-list-check"></i> Kelola Pertanyaan
                </a>
                <a href="<?= BASE_URL ?>/admin/quiz" class="btn btn-sm btn-secondary">
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

            <form method="POST" action="">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="materi_id" class="form-label">Materi <span class="text-danger">*</span></label>
                        <select class="form-select" id="materi_id" name="materi_id" required>
                            <option value="">-- Pilih Materi --</option>
                            <?php foreach ($materi_list as $materi): ?>
                                <option value="<?= $materi['id'] ?>" <?= $quiz['materi_id'] == $materi['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($materi['materi_lengkap']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Pilih materi tempat quiz ini akan ditambahkan</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="judul" class="form-label">Judul Quiz <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="judul" name="judul" value="<?= htmlspecialchars($quiz['judul']) ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?= htmlspecialchars($quiz['deskripsi']) ?></textarea>
                        <div class="form-text">Jelaskan tentang quiz ini, tujuan, dan petunjuk pengerjaan</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="durasi_menit" class="form-label">Durasi (menit)</label>
                        <input type="number" class="form-control" id="durasi_menit" name="durasi_menit" min="1" value="<?= $quiz['durasi_menit'] ? htmlspecialchars($quiz['durasi_menit']) : '' ?>">
                        <div class="form-text">Biarkan kosong jika tidak ada batasan waktu</div>
                    </div>
                    <div class="col-md-4">
                        <label for="nilai_lulus" class="form-label">Nilai Minimum Lulus</label>
                        <input type="number" class="form-control" id="nilai_lulus" name="nilai_lulus" min="0" max="100" value="<?= htmlspecialchars($quiz['nilai_lulus']) ?>">
                        <div class="form-text">Nilai minimum (0-100) untuk dinyatakan lulus</div>
                    </div>
                    <div class="col-md-4">
                        <label for="maksimal_percobaan" class="form-label">Maksimal Percobaan</label>
                        <input type="number" class="form-control" id="maksimal_percobaan" name="maksimal_percobaan" min="1" value="<?= $quiz['maksimal_percobaan'] ? htmlspecialchars($quiz['maksimal_percobaan']) : '' ?>">
                        <div class="form-text">Biarkan kosong jika tidak ada batasan percobaan</div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="acak_pertanyaan" name="acak_pertanyaan" <?= $quiz['acak_pertanyaan'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="acak_pertanyaan">
                                Acak urutan pertanyaan saat quiz dimulai
                            </label>
                        </div>
                    </div>
                </div>

                <?php if ($quiz['jumlah_percobaan'] > 0): ?>
                    <div class="alert alert-warning mb-4">
                        <i class="bi bi-exclamation-triangle me-2"></i> <strong>Perhatian:</strong> Quiz ini sudah memiliki <?= number_format($quiz['jumlah_percobaan']) ?> percobaan. Perubahan pada beberapa pengaturan mungkin memengaruhi data yang sudah ada.
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>/admin/quiz" class="btn btn-secondary">Batal</a>
                    <div>
                        <a href="<?= BASE_URL ?>/admin/quiz/pertanyaan?quiz_id=<?= $quiz_id ?>" class="btn btn-success me-2">
                            <i class="bi bi-list-check"></i> Kelola Pertanyaan
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
    });
</script>

<?php adminFooter(); ?>