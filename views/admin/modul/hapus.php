<?php
// Path: views/admin/modul/hapus.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Hapus Modul", "Hapus modul kursus");

$db = dbConnect();
$pesan = '';
$tipe = '';

// Cek parameter id
if (!isset($_GET['id'])) {
    header("Location: " . BASE_URL . "/admin/modul");
    exit;
}

$id = $_GET['id'];

// Ambil data modul
$stmt = $db->prepare("
    SELECT m.*, k.judul as kursus_judul, k.id as kursus_id
    FROM modul m
    JOIN kursus k ON m.kursus_id = k.id
    WHERE m.id = ?
");
$stmt->execute([$id]);
$modul = $stmt->fetch();

if (!$modul) {
    header("Location: " . BASE_URL . "/admin/modul");
    exit;
}

// Cek apakah modul memiliki materi
$stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM materi WHERE modul_id = ?");
$stmt->execute([$id]);
$jumlahMateri = $stmt->fetch()['jumlah'];

// Jika ada materi, ambil detail materi
$materiList = [];
if ($jumlahMateri > 0) {
    $stmt = $db->prepare("
        SELECT id, judul, tipe 
        FROM materi 
        WHERE modul_id = ? 
        ORDER BY urutan ASC
    ");
    $stmt->execute([$id]);
    $materiList = $stmt->fetchAll();
}

// Proses hapus jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi_hapus'])) {
    // Cek jika masih ada materi
    if ($jumlahMateri > 0 && !isset($_POST['force_delete'])) {
        $pesan = "Tidak dapat menghapus modul karena masih memiliki materi. Hapus materi terlebih dahulu atau pilih opsi hapus paksa.";
        $tipe = "danger";
    } else {
        try {
            $db->beginTransaction();

            // Jika force delete, hapus semua materi dan data terkait
            if (isset($_POST['force_delete']) && $jumlahMateri > 0) {
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
                }

                // Hapus semua materi dalam modul
                $stmt = $db->prepare("DELETE FROM materi WHERE modul_id = ?");
                $stmt->execute([$id]);
            }

            // Hapus modul
            $stmt = $db->prepare("DELETE FROM modul WHERE id = ?");
            $stmt->execute([$id]);

            // Update urutan modul dalam kursus
            $stmt = $db->prepare("SELECT id FROM modul WHERE kursus_id = ? ORDER BY urutan ASC");
            $stmt->execute([$modul['kursus_id']]);
            $moduls = $stmt->fetchAll();

            $urutan = 1;
            foreach ($moduls as $m) {
                $stmt = $db->prepare("UPDATE modul SET urutan = ? WHERE id = ?");
                $stmt->execute([$urutan, $m['id']]);
                $urutan++;
            }

            $db->commit();

            // Redirect ke halaman modul dengan pesan sukses
            header("Location: " . BASE_URL . "/admin/modul?kursus_id=" . $modul['kursus_id'] . "&pesan=Modul berhasil dihapus&tipe=success");
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            $pesan = "Gagal menghapus modul: " . $e->getMessage();
            $tipe = "danger";
        }
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="page-title">Hapus Modul</h1>
            <p class="text-muted">
                Kursus: <strong><?= htmlspecialchars($modul['kursus_judul']) ?></strong>
            </p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?= BASE_URL ?>/admin/modul?kursus_id=<?= $modul['kursus_id'] ?>" class="btn btn-secondary">
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
                                <p>Anda akan menghapus modul <strong><?= htmlspecialchars($modul['judul']) ?></strong>.</p>

                                <?php if ($jumlahMateri > 0): ?>
                                    <div class="alert alert-danger">
                                        <p><strong>Modul ini memiliki <?= $jumlahMateri ?> materi di dalamnya.</strong></p>
                                        <p class="mb-0">Anda harus menghapus semua materi terlebih dahulu atau menggunakan opsi "Hapus semua materi dan data terkait".</p>
                                    </div>
                                <?php endif; ?>

                                <p class="mb-0 fw-bold">Tindakan ini tidak dapat dibatalkan!</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <form action="" method="POST">
                            <?php if ($jumlahMateri > 0): ?>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="force_delete" name="force_delete" value="1">
                                    <label class="form-check-label" for="force_delete">
                                        <strong class="text-danger">Hapus semua materi dan data terkait</strong> (termasuk progres belajar, quiz, tugas, dll)
                                    </label>
                                </div>
                            <?php endif; ?>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="konfirmasi_hapus" name="konfirmasi_hapus" value="1" required>
                                <label class="form-check-label" for="konfirmasi_hapus">
                                    Saya mengerti dan ingin menghapus modul ini
                                </label>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="<?= BASE_URL ?>/admin/modul?kursus_id=<?= $modul['kursus_id'] ?>" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-danger" id="btn-hapus" disabled>
                                    <i class="bi bi-trash"></i> Hapus Modul
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Informasi Modul</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Judul:</span>
                            <span><?= htmlspecialchars($modul['judul']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Kursus:</span>
                            <span><?= htmlspecialchars($modul['kursus_judul']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Urutan:</span>
                            <span><?= $modul['urutan'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Jumlah Materi:</span>
                            <span class="badge bg-primary rounded-pill"><?= $jumlahMateri ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Dibuat:</span>
                            <span><?= date('d/m/Y H:i', strtotime($modul['waktu_dibuat'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Diperbarui:</span>
                            <span><?= date('d/m/Y H:i', strtotime($modul['waktu_diperbarui'])) ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <?php if ($jumlahMateri > 0): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Daftar Materi</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($materiList as $index => $materi): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
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
                                                            ?> me-2">
                                            <?= ucfirst($materi['tipe']) ?>
                                        </span>
                                        <?= htmlspecialchars($materi['judul']) ?>
                                    </div>
                                    <a href="<?= BASE_URL ?>/admin/materi/hapus?id=<?= $materi['id'] ?>" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const checkKonfirmasi = document.getElementById('konfirmasi_hapus');
        const btnHapus = document.getElementById('btn-hapus');
        const forceDelete = document.getElementById('force_delete');

        checkKonfirmasi.addEventListener('change', function() {
            updateButtonState();
        });

        if (forceDelete) {
            forceDelete.addEventListener('change', function() {
                updateButtonState();
            });
        }

        function updateButtonState() {
            if (<?= $jumlahMateri ?> > 0) {
                btnHapus.disabled = !(checkKonfirmasi.checked && forceDelete.checked);
            } else {
                btnHapus.disabled = !checkKonfirmasi.checked;
            }
        }
    });
</script>

<?php adminFooter(); ?>