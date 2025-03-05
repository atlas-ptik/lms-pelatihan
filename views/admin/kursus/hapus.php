<?php
// Path: views/admin/kursus/hapus.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Hapus Kursus", "Hapus kursus dari sistem");

$db = dbConnect();
$pesan = '';
$tipe = '';

// Cek parameter id
if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "/admin/kursus");
    exit;
}

$id = $_GET['id'];

// Ambil data kursus
$stmt = $db->prepare("SELECT * FROM kursus WHERE id = ?");
$stmt->execute([$id]);
$kursus = $stmt->fetch();

if (!$kursus) {
    header("Location: " . BASE_URL . "/admin/kursus");
    exit;
}

// Cek data terkait
// Jumlah pendaftaran
$stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM pendaftaran WHERE kursus_id = ?");
$stmt->execute([$id]);
$jumlahPendaftaran = $stmt->fetch()['jumlah'];

// Jumlah modul
$stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM modul WHERE kursus_id = ?");
$stmt->execute([$id]);
$jumlahModul = $stmt->fetch()['jumlah'];

// Jumlah materi
$stmt = $db->prepare("
    SELECT COUNT(*) as jumlah FROM materi m
    JOIN modul mo ON m.modul_id = mo.id
    WHERE mo.kursus_id = ?
");
$stmt->execute([$id]);
$jumlahMateri = $stmt->fetch()['jumlah'];

// Jumlah kategori terkait
$stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM kursus_kategori WHERE kursus_id = ?");
$stmt->execute([$id]);
$jumlahKategori = $stmt->fetch()['jumlah'];

// Jika ada pendaftaran, tampilkan pesan error
if ($jumlahPendaftaran > 0) {
    $pesan = "Tidak dapat menghapus kursus karena masih ada $jumlahPendaftaran pendaftaran terkait. Hapus semua pendaftaran terlebih dahulu.";
    $tipe = "danger";
}

// Proses hapus jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi_hapus']) && $jumlahPendaftaran === 0) {
    try {
        $db->beginTransaction();

        // Hapus kategori kursus
        $stmt = $db->prepare("DELETE FROM kursus_kategori WHERE kursus_id = ?");
        $stmt->execute([$id]);

        // Hapus materi, progres, quiz, tugas dan data terkait
        if ($jumlahModul > 0) {
            // Ambil semua modul
            $stmt = $db->prepare("SELECT id FROM modul WHERE kursus_id = ?");
            $stmt->execute([$id]);
            $modulList = $stmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($modulList as $modulId) {
                // Ambil semua materi dari modul
                $stmt = $db->prepare("SELECT id, tipe FROM materi WHERE modul_id = ?");
                $stmt->execute([$modulId]);
                $materiList = $stmt->fetchAll();

                foreach ($materiList as $materi) {
                    // Hapus progres materi
                    $stmt = $db->prepare("DELETE FROM progres_materi WHERE materi_id = ?");
                    $stmt->execute([$materi['id']]);

                    // Jika tipe quiz, hapus quiz dan data terkait
                    if ($materi['tipe'] === 'quiz') {
                        $stmt = $db->prepare("SELECT id FROM quiz WHERE materi_id = ?");
                        $stmt->execute([$materi['id']]);
                        $quiz = $stmt->fetch();

                        if ($quiz) {
                            $quizId = $quiz['id'];

                            // Hapus jawaban percobaan
                            $stmt = $db->prepare("
                                DELETE FROM jawaban_percobaan 
                                WHERE percobaan_quiz_id IN (SELECT id FROM percobaan_quiz WHERE quiz_id = ?)
                            ");
                            $stmt->execute([$quizId]);

                            // Hapus percobaan quiz
                            $stmt = $db->prepare("DELETE FROM percobaan_quiz WHERE quiz_id = ?");
                            $stmt->execute([$quizId]);

                            // Hapus pilihan jawaban
                            $stmt = $db->prepare("
                                DELETE FROM pilihan_jawaban 
                                WHERE pertanyaan_id IN (SELECT id FROM pertanyaan_quiz WHERE quiz_id = ?)
                            ");
                            $stmt->execute([$quizId]);

                            // Hapus pertanyaan quiz
                            $stmt = $db->prepare("DELETE FROM pertanyaan_quiz WHERE quiz_id = ?");
                            $stmt->execute([$quizId]);

                            // Hapus quiz
                            $stmt = $db->prepare("DELETE FROM quiz WHERE id = ?");
                            $stmt->execute([$quizId]);
                        }
                    }

                    // Jika tipe tugas, hapus tugas dan data terkait
                    if ($materi['tipe'] === 'tugas') {
                        $stmt = $db->prepare("SELECT id FROM tugas WHERE materi_id = ?");
                        $stmt->execute([$materi['id']]);
                        $tugas = $stmt->fetch();

                        if ($tugas) {
                            $tugasId = $tugas['id'];

                            // Hapus pengumpulan tugas
                            $stmt = $db->prepare("DELETE FROM pengumpulan_tugas WHERE tugas_id = ?");
                            $stmt->execute([$tugasId]);

                            // Hapus tugas
                            $stmt = $db->prepare("DELETE FROM tugas WHERE id = ?");
                            $stmt->execute([$tugasId]);
                        }
                    }

                    // Hapus file materi jika ada
                    if (!empty($materi['file_path'])) {
                        $filePath = BASE_PATH . '/uploads/materi/' . $materi['file_path'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                }

                // Hapus semua materi dari modul
                $stmt = $db->prepare("DELETE FROM materi WHERE modul_id = ?");
                $stmt->execute([$modulId]);
            }

            // Hapus semua modul
            $stmt = $db->prepare("DELETE FROM modul WHERE kursus_id = ?");
            $stmt->execute([$id]);
        }

        // Hapus diskusi dan komentar terkait kursus
        $stmt = $db->prepare("
            DELETE FROM komentar_diskusi 
            WHERE diskusi_id IN (SELECT id FROM diskusi WHERE kursus_id = ?)
        ");
        $stmt->execute([$id]);

        $stmt = $db->prepare("DELETE FROM diskusi WHERE kursus_id = ?");
        $stmt->execute([$id]);

        // Hapus gambar sampul jika ada
        if (!empty($kursus['gambar_sampul'])) {
            $filePath = BASE_PATH . '/uploads/kursus/' . $kursus['gambar_sampul'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Hapus kursus
        $stmt = $db->prepare("DELETE FROM kursus WHERE id = ?");
        $stmt->execute([$id]);

        $db->commit();

        // Redirect ke halaman kursus dengan pesan sukses
        header("Location: " . BASE_URL . "/admin/kursus?pesan=Kursus berhasil dihapus&tipe=success");
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        $pesan = "Gagal menghapus kursus: " . $e->getMessage();
        $tipe = "danger";
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="page-title">Hapus Kursus</h1>
            <p class="text-muted">Konfirmasi penghapusan kursus</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?= BASE_URL ?>/admin/kursus" class="btn btn-secondary">
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
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Konfirmasi Penghapusan</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                            </div>
                            <div>
                                <h5>Perhatian!</h5>
                                <p>Anda akan menghapus kursus <strong><?= htmlspecialchars($kursus['judul']) ?></strong>.</p>

                                <?php if ($jumlahPendaftaran > 0): ?>
                                    <div class="alert alert-danger">
                                        <p><strong>Kursus ini memiliki <?= $jumlahPendaftaran ?> pendaftaran aktif.</strong></p>
                                        <p class="mb-0">Anda harus menghapus semua pendaftaran terlebih dahulu sebelum dapat menghapus kursus ini.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="mt-2">
                                        <p><strong>Tindakan ini akan menghapus:</strong></p>
                                        <ul>
                                            <li><?= $jumlahModul ?> modul</li>
                                            <li><?= $jumlahMateri ?> materi</li>
                                            <li>Semua quiz, tugas, dan progres terkait</li>
                                            <li>Semua diskusi dan komentar terkait</li>
                                            <li>Semua kategori terkait (<?= $jumlahKategori ?> hubungan kategori)</li>
                                        </ul>
                                        <p class="mb-0 fw-bold">Tindakan ini tidak dapat dibatalkan!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($jumlahPendaftaran === 0): ?>
                        <div class="mt-4">
                            <form action="" method="POST">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="konfirmasi_hapus" name="konfirmasi_hapus" value="1" required>
                                    <label class="form-check-label" for="konfirmasi_hapus">
                                        Saya mengerti dan ingin menghapus kursus ini beserta seluruh data terkait
                                    </label>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="<?= BASE_URL ?>/admin/kursus" class="btn btn-secondary">Batal</a>
                                    <button type="submit" class="btn btn-danger" id="btn-hapus" disabled>
                                        <i class="bi bi-trash"></i> Hapus Kursus
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="mt-4">
                            <a href="<?= BASE_URL ?>/admin/pendaftaran?kursus_id=<?= $id ?>" class="btn btn-primary">
                                <i class="bi bi-person-check"></i> Kelola Pendaftaran
                            </a>
                            <a href="<?= BASE_URL ?>/admin/kursus" class="btn btn-secondary ms-2">
                                Kembali
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Kursus</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($kursus['gambar_sampul'])): ?>
                        <div class="text-center mb-3">
                            <img src="<?= BASE_URL ?>/uploads/kursus/<?= $kursus['gambar_sampul'] ?>" alt="<?= htmlspecialchars($kursus['judul']) ?>" class="img-thumbnail" style="max-height: 150px;">
                        </div>
                    <?php endif; ?>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Judul:</span>
                            <span><?= htmlspecialchars($kursus['judul']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Level:</span>
                            <span><?= ucfirst($kursus['level']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Harga:</span>
                            <span><?= $kursus['harga'] > 0 ? 'Rp ' . number_format($kursus['harga'], 0, ',', '.') : 'Gratis' ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Status:</span>
                            <span class="badge <?php
                                                switch ($kursus['status']) {
                                                    case 'publikasi':
                                                        echo 'bg-success';
                                                        break;
                                                    case 'draf':
                                                        echo 'bg-warning text-dark';
                                                        break;
                                                    case 'arsip':
                                                        echo 'bg-secondary';
                                                        break;
                                                }
                                                ?>"><?= ucfirst($kursus['status']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Durasi:</span>
                            <span><?= !empty($kursus['durasi_menit']) ? $kursus['durasi_menit'] . ' menit' : 'Tidak ditentukan' ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Dibuat:</span>
                            <span><?= date('d/m/Y', strtotime($kursus['waktu_dibuat'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Jumlah Modul:</span>
                            <span class="badge bg-primary rounded-pill"><?= $jumlahModul ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Jumlah Materi:</span>
                            <span class="badge bg-info rounded-pill"><?= $jumlahMateri ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Jumlah Pendaftaran:</span>
                            <span class="badge <?= $jumlahPendaftaran > 0 ? 'bg-danger' : 'bg-success' ?> rounded-pill"><?= $jumlahPendaftaran ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkKonfirmasi = document.getElementById('konfirmasi_hapus');
        const btnHapus = document.getElementById('btn-hapus');

        if (checkKonfirmasi) {
            checkKonfirmasi.addEventListener('change', function() {
                btnHapus.disabled = !this.checked;
            });
        }
    });
</script>

<?php adminFooter(); ?>