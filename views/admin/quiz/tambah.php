<?php
// Path: views/admin/quiz/tambah.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Sambungkan ke database
$db = dbConnect();

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

    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        try {
            $quiz_id = generate_uuid();

            $sql = "
                INSERT INTO quiz (
                    id, materi_id, judul, deskripsi, durasi_menit, 
                    nilai_lulus, maksimal_percobaan, acak_pertanyaan, 
                    waktu_dibuat, waktu_diperbarui
                ) VALUES (
                    :id, :materi_id, :judul, :deskripsi, :durasi_menit, 
                    :nilai_lulus, :maksimal_percobaan, :acak_pertanyaan, 
                    CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )
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

            // Redirect ke halaman pertanyaan quiz
            header('Location: ' . BASE_URL . '/admin/quiz/pertanyaan?quiz_id=' . $quiz_id . '&new=1');
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

// Mendapatkan materi_id dari URL jika ada
$selected_materi_id = $_GET['materi_id'] ?? '';

adminHeader("Tambah Quiz", "Buat quiz baru");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tambah Quiz</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/quiz">Quiz</a></li>
            <li class="breadcrumb-item active">Tambah</li>
        </ol>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">Form Tambah Quiz</h6>
            <a href="<?= BASE_URL ?>/admin/quiz" class="btn btn-sm btn-secondary">
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

            <form method="POST" action="">
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
                        <div class="form-text">Pilih materi tempat quiz ini akan ditambahkan</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-8">
                        <label for="judul" class="form-label">Judul Quiz <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="judul" name="judul" value="<?= isset($_POST['judul']) ? htmlspecialchars($_POST['judul']) : '' ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?= isset($_POST['deskripsi']) ? htmlspecialchars($_POST['deskripsi']) : '' ?></textarea>
                        <div class="form-text">Jelaskan tentang quiz ini, tujuan, dan petunjuk pengerjaan</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="durasi_menit" class="form-label">Durasi (menit)</label>
                        <input type="number" class="form-control" id="durasi_menit" name="durasi_menit" min="1" value="<?= isset($_POST['durasi_menit']) ? htmlspecialchars($_POST['durasi_menit']) : '' ?>">
                        <div class="form-text">Biarkan kosong jika tidak ada batasan waktu</div>
                    </div>
                    <div class="col-md-4">
                        <label for="nilai_lulus" class="form-label">Nilai Minimum Lulus</label>
                        <input type="number" class="form-control" id="nilai_lulus" name="nilai_lulus" min="0" max="100" value="<?= isset($_POST['nilai_lulus']) ? htmlspecialchars($_POST['nilai_lulus']) : '70' ?>">
                        <div class="form-text">Nilai minimum (0-100) untuk dinyatakan lulus</div>
                    </div>
                    <div class="col-md-4">
                        <label for="maksimal_percobaan" class="form-label">Maksimal Percobaan</label>
                        <input type="number" class="form-control" id="maksimal_percobaan" name="maksimal_percobaan" min="1" value="<?= isset($_POST['maksimal_percobaan']) ? htmlspecialchars($_POST['maksimal_percobaan']) : '' ?>">
                        <div class="form-text">Biarkan kosong jika tidak ada batasan percobaan</div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="acak_pertanyaan" name="acak_pertanyaan" <?= isset($_POST['acak_pertanyaan']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="acak_pertanyaan">
                                Acak urutan pertanyaan saat quiz dimulai
                            </label>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i> Setelah membuat quiz, Anda akan diarahkan ke halaman untuk menambahkan pertanyaan.
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>/admin/quiz" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> Simpan & Lanjutkan
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