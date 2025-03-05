<?php
// Path: views/admin/materi/hapus.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Hapus Materi", "Hapus materi pembelajaran");

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

// Cek data terkait
$hasRelatedData = false;
$relatedDataInfo = [];

// Cek progres materi
$stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM progres_materi WHERE materi_id = ?");
$stmt->execute([$id]);
$jumlahProgres = $stmt->fetch()['jumlah'];

if ($jumlahProgres > 0) {
    $hasRelatedData = true;
    $relatedDataInfo[] = "$jumlahProgres data progres belajar siswa";
}

// Cek apakah ada quiz atau tugas terkait
if ($materi['tipe'] === 'quiz') {
    // Cek data quiz
    $stmt = $db->prepare("SELECT id FROM quiz WHERE materi_id = ?");
    $stmt->execute([$id]);
    $quiz = $stmt->fetch();

    if ($quiz) {
        // Cek percobaan quiz
        $stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM percobaan_quiz WHERE quiz_id = ?");
        $stmt->execute([$quiz['id']]);
        $jumlahPercobaan = $stmt->fetch()['jumlah'];

        if ($jumlahPercobaan > 0) {
            $hasRelatedData = true;
            $relatedDataInfo[] = "$jumlahPercobaan percobaan quiz oleh siswa";
        }

        // Cek pertanyaan quiz
        $stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM pertanyaan_quiz WHERE quiz_id = ?");
        $stmt->execute([$quiz['id']]);
        $jumlahPertanyaan = $stmt->fetch()['jumlah'];

        if ($jumlahPertanyaan > 0) {
            $hasRelatedData = true;
            $relatedDataInfo[] = "$jumlahPertanyaan pertanyaan quiz";
        }
    }
} elseif ($materi['tipe'] === 'tugas') {
    // Cek data tugas
    $stmt = $db->prepare("SELECT id FROM tugas WHERE materi_id = ?");
    $stmt->execute([$id]);
    $tugas = $stmt->fetch();

    if ($tugas) {
        // Cek pengumpulan tugas
        $stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM pengumpulan_tugas WHERE tugas_id = ?");
        $stmt->execute([$tugas['id']]);
        $jumlahPengumpulan = $stmt->fetch()['jumlah'];

        if ($jumlahPengumpulan > 0) {
            $hasRelatedData = true;
            $relatedDataInfo[] = "$jumlahPengumpulan pengumpulan tugas oleh siswa";
        }
    }
}

// Proses hapus jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi_hapus'])) {
    try {
        $db->beginTransaction();

        // Hapus progres materi
        $stmt = $db->prepare("DELETE FROM progres_materi WHERE materi_id = ?");
        $stmt->execute([$id]);

        // Jika tipe quiz, hapus quiz dan data terkait
        if ($materi['tipe'] === 'quiz') {
            $stmt = $db->prepare("SELECT id FROM quiz WHERE materi_id = ?");
            $stmt->execute([$id]);
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
            $stmt->execute([$id]);
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

        // Hapus file jika ada
        if (!empty($materi['file_path'])) {
            $filePath = BASE_PATH . '/uploads/materi/' . $materi['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Hapus materi
        $stmt = $db->prepare("DELETE FROM materi WHERE id = ?");
        $stmt->execute([$id]);

        // Update urutan materi dalam modul
        $stmt = $db->prepare("SELECT id FROM materi WHERE modul_id = ? ORDER BY urutan ASC");
        $stmt->execute([$materi['modul_id']]);
        $materis = $stmt->fetchAll();

        $urutan = 1;
        foreach ($materis as $m) {
            $stmt = $db->prepare("UPDATE materi SET urutan = ? WHERE id = ?");
            $stmt->execute([$urutan, $m['id']]);
            $urutan++;
        }

        $db->commit();

        // Redirect ke halaman materi dengan pesan sukses
        header("Location: " . BASE_URL . "/admin/materi?modul_id=" . $materi['modul_id'] . "&pesan=Materi berhasil dihapus&tipe=success");
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        $pesan = "Gagal menghapus materi: " . $e->getMessage();
        $tipe = "danger";
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="page-title">Hapus Materi</h1>
            <p class="text-muted">
                Kursus: <strong><?= htmlspecialchars($materi['kursus_judul']) ?></strong> |
                Modul: <strong><?= htmlspecialchars($materi['modul_judul']) ?></strong>
            </p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?= BASE_URL ?>/admin/materi?modul_id=<?= $materi['modul_id'] ?>" class="btn btn-secondary">
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
                                <p>Anda akan menghapus materi <strong><?= htmlspecialchars($materi['judul']) ?></strong> dengan tipe <strong><?= ucfirst($materi['tipe']) ?></strong>.</p>

                                <?php if ($hasRelatedData): ?>
                                    <div class="mt-2">
                                        <p><strong>Materi ini memiliki data terkait:</strong></p>
                                        <ul>
                                            <?php foreach ($relatedDataInfo as $info): ?>
                                                <li><?= $info ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <p class="mb-0">Menghapus materi ini akan menghapus semua data terkait di atas!</p>
                                    </div>
                                <?php endif; ?>

                                <p class="mt-2 mb-0 fw-bold">Tindakan ini tidak dapat dibatalkan!</p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <form action="" method="POST">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="konfirmasi_hapus" name="konfirmasi_hapus" value="1" required>
                                <label class="form-check-label" for="konfirmasi_hapus">
                                    Saya mengerti dan ingin menghapus materi ini beserta seluruh data terkait
                                </label>
                            </div>

                            <div class="d-flex justify-content-between">
                                <a href="<?= BASE_URL ?>/admin/materi?modul_id=<?= $materi['modul_id'] ?>" class="btn btn-secondary">Batal</a>
                                <button type="submit" class="btn btn-danger" id="btn-hapus" disabled>
                                    <i class="bi bi-trash"></i> Hapus Materi
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
                    <h5 class="mb-0">Informasi Materi</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Judul:</span>
                            <span><?= htmlspecialchars($materi['judul']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Tipe:</span>
                            <span>
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
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Urutan:</span>
                            <span><?= $materi['urutan'] ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Durasi:</span>
                            <span><?= !empty($materi['durasi_menit']) ? $materi['durasi_menit'] . ' menit' : 'Tidak ditentukan' ?></span>
                        </li>

                        <?php if ($materi['tipe'] === 'video' && !empty($materi['video_url'])): ?>
                            <li class="list-group-item">
                                <span class="fw-semibold">URL Video:</span>
                                <div class="text-truncate mt-1">
                                    <a href="<?= htmlspecialchars($materi['video_url']) ?>" target="_blank">
                                        <?= htmlspecialchars($materi['video_url']) ?>
                                    </a>
                                </div>
                            </li>
                        <?php endif; ?>

                        <?php if ($materi['tipe'] === 'dokumen' && !empty($materi['file_path'])): ?>
                            <li class="list-group-item">
                                <span class="fw-semibold">File:</span>
                                <div class="mt-1">
                                    <a href="<?= BASE_URL ?>/uploads/materi/<?= $materi['file_path'] ?>" target="_blank">
                                        <i class="bi bi-file-earmark"></i> <?= htmlspecialchars($materi['file_path']) ?>
                                    </a>
                                </div>
                            </li>
                        <?php endif; ?>

                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Dibuat:</span>
                            <span><?= date('d/m/Y H:i', strtotime($materi['waktu_dibuat'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="fw-semibold">Diperbarui:</span>
                            <span><?= date('d/m/Y H:i', strtotime($materi['waktu_diperbarui'])) ?></span>
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

        checkKonfirmasi.addEventListener('change', function() {
            btnHapus.disabled = !this.checked;
        });
    });
</script>

<?php adminFooter(); ?>