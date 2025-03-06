<?php
// Path: views/user/tugas/kumpul.php

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/user/login');
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['kursus_id'])) {
    header('Location: ' . BASE_URL . '/user/kursus');
    exit;
}

$tugas_id = $_GET['id'];
$kursus_id = $_GET['kursus_id'];
$user_id = $_SESSION['user']['id'];

$db = dbConnect();

$sql_pendaftaran = "SELECT id FROM pendaftaran WHERE pengguna_id = ? AND kursus_id = ?";
$stmt_pendaftaran = $db->prepare($sql_pendaftaran);
$stmt_pendaftaran->execute([$user_id, $kursus_id]);
$pendaftaran = $stmt_pendaftaran->fetch();

if (!$pendaftaran) {
    header('Location: ' . BASE_URL . '/user/kursus');
    exit;
}

$pendaftaran_id = $pendaftaran['id'];

$sql_tugas = "SELECT t.*, m.judul as materi_judul, m.id as materi_id, mo.judul as modul_judul 
             FROM tugas t
             JOIN materi m ON t.materi_id = m.id
             JOIN modul mo ON m.modul_id = mo.id
             WHERE t.id = ? AND mo.kursus_id = ?";
$stmt_tugas = $db->prepare($sql_tugas);
$stmt_tugas->execute([$tugas_id, $kursus_id]);
$tugas = $stmt_tugas->fetch();

if (!$tugas) {
    header('Location: ' . BASE_URL . '/user/tugas?kursus_id=' . $kursus_id);
    exit;
}

$sql_pengumpulan = "SELECT * FROM pengumpulan_tugas WHERE tugas_id = ? AND pendaftaran_id = ?";
$stmt_pengumpulan = $db->prepare($sql_pengumpulan);
$stmt_pengumpulan->execute([$tugas_id, $pendaftaran_id]);
$pengumpulan = $stmt_pengumpulan->fetch();

$mode_revisi = false;
if ($pengumpulan && $pengumpulan['status'] === 'revisi') {
    $mode_revisi = true;
}

$sekarang = new DateTime();
$tenggat = $tugas['tenggat_waktu'] ? new DateTime($tugas['tenggat_waktu']) : null;
$terlambat = $tenggat && $sekarang > $tenggat;

if ($terlambat && !$mode_revisi) {
    header('Location: ' . BASE_URL . '/user/tugas/detail?id=' . $tugas_id . '&kursus_id=' . $kursus_id);
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teks_jawaban = isset($_POST['teks_jawaban']) ? $_POST['teks_jawaban'] : null;
    $file_jawaban = null;

    // Validasi
    if ($tugas['tipe_pengumpulan'] === 'file' && empty($_FILES['file_jawaban']['name'])) {
        $error = 'File jawaban wajib diupload.';
    } elseif ($tugas['tipe_pengumpulan'] === 'teks' && empty(trim($teks_jawaban))) {
        $error = 'Jawaban teks wajib diisi.';
    } elseif ($tugas['tipe_pengumpulan'] === 'keduanya' && (empty($_FILES['file_jawaban']['name']) || empty(trim($teks_jawaban)))) {
        $error = 'File jawaban dan teks jawaban wajib diisi.';
    }

    // Proses upload file
    if (!$error && isset($_FILES['file_jawaban']) && $_FILES['file_jawaban']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['file_jawaban']['name'];
        $file_tmp = $_FILES['file_jawaban']['tmp_name'];
        $file_size = $_FILES['file_jawaban']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_extensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip', 'rar', 'txt'];

        if (!in_array($file_ext, $allowed_extensions)) {
            $error = 'Format file tidak didukung. Format yang didukung: ' . implode(', ', $allowed_extensions);
        } elseif ($file_size > 10485760) { // 10 MB
            $error = 'Ukuran file terlalu besar. Maksimal 10 MB.';
        } else {
            $upload_dir = BASE_PATH . '/uploads/tugas/jawaban/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $new_file_name = $user_id . '_' . $tugas_id . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $file_jawaban = $new_file_name;
            } else {
                $error = 'Gagal mengupload file. Silakan coba lagi.';
            }
        }
    }

    if (!$error) {
        if ($mode_revisi) {
            // Hapus file lama jika ada dan diganti dengan yang baru
            if ($file_jawaban && !empty($pengumpulan['file_jawaban'])) {
                $old_file_path = BASE_PATH . '/uploads/tugas/jawaban/' . $pengumpulan['file_jawaban'];
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
            }

            $sql_update = "UPDATE pengumpulan_tugas SET 
                          teks_jawaban = ?, 
                          file_jawaban = CASE WHEN ? IS NOT NULL THEN ? ELSE file_jawaban END, 
                          waktu_pengumpulan = NOW(), 
                          status = 'menunggu penilaian', 
                          waktu_diperbarui = NOW() 
                          WHERE id = ?";
            $params = [$teks_jawaban, $file_jawaban, $file_jawaban, $pengumpulan['id']];
            $stmt_update = $db->prepare($sql_update);
            $stmt_update->execute($params);

            $success = 'Revisi tugas berhasil dikumpulkan.';
        } else {
            $pengumpulan_id = generate_uuid();
            $status = $terlambat ? 'terlambat' : 'menunggu penilaian';

            $sql_insert = "INSERT INTO pengumpulan_tugas (
                          id, pendaftaran_id, tugas_id, teks_jawaban, file_jawaban, 
                          waktu_pengumpulan, status, waktu_dibuat, waktu_diperbarui
                          ) VALUES (?, ?, ?, ?, ?, NOW(), ?, NOW(), NOW())";

            $params = [
                $pengumpulan_id,
                $pendaftaran_id,
                $tugas_id,
                $teks_jawaban,
                $file_jawaban,
                $status
            ];

            $stmt_insert = $db->prepare($sql_insert);
            $stmt_insert->execute($params);

            $success = 'Tugas berhasil dikumpulkan.';
        }

        // Redirect setelah berhasil
        header('Location: ' . BASE_URL . '/user/tugas/detail?id=' . $tugas_id . '&kursus_id=' . $kursus_id . '&success=' . urlencode($success));
        exit;
    }
}

ob_start();
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/kursus">Kursus Saya</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/belajar?kursus_id=<?= $kursus_id ?>">Materi</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/tugas?kursus_id=<?= $kursus_id ?>">Tugas</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/user/tugas/detail?id=<?= $tugas_id ?>&kursus_id=<?= $kursus_id ?>"><?= htmlspecialchars($tugas['judul']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= $mode_revisi ? 'Revisi' : 'Kumpulkan' ?> Tugas</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h4 class="card-title mb-0"><?= $mode_revisi ? 'Revisi' : 'Kumpulkan' ?> Tugas: <?= htmlspecialchars($tugas['judul']) ?></h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?= $success ?>
                        </div>
                    <?php endif; ?>

                    <div class="info-tugas mb-4">
                        <div class="mb-3">
                            <?php if ($tugas['tenggat_waktu']): ?>
                                <div class="badge bg-<?= $terlambat ? 'danger' : 'primary' ?> mb-2">
                                    <i class="bi bi-clock me-1"></i>
                                    Tenggat: <?= date('d M Y, H:i', strtotime($tugas['tenggat_waktu'])) ?>
                                </div>

                                <?php if (!$terlambat && $tenggat):
                                    $interval = $sekarang->diff($tenggat);
                                    $sisa_waktu = '';

                                    if ($interval->days > 0) {
                                        $sisa_waktu .= $interval->days . ' hari ';
                                    }

                                    if ($interval->h > 0) {
                                        $sisa_waktu .= $interval->h . ' jam';
                                    }

                                    if ($interval->days == 0 && $interval->h == 0) {
                                        $sisa_waktu = $interval->i . ' menit';
                                    }
                                ?>
                                    <div class="badge bg-info mb-2 ms-2">
                                        <i class="bi bi-hourglass-split me-1"></i>
                                        Sisa waktu: <?= $sisa_waktu ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <h5>Deskripsi Tugas</h5>
                        <div class="tugas-content mb-4">
                            <?= nl2br(htmlspecialchars($tugas['deskripsi'])) ?>
                        </div>

                        <?php if ($tugas['file_lampiran']): ?>
                            <div class="mb-4">
                                <h5>Lampiran</h5>
                                <a href="<?= BASE_URL ?>/uploads/tugas/<?= $tugas['file_lampiran'] ?>" class="btn btn-outline-primary" target="_blank">
                                    <i class="bi bi-file-earmark-arrow-down me-2"></i>Unduh Lampiran
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($mode_revisi && $pengumpulan['komentar_pengajar']): ?>
                        <div class="alert alert-warning mb-4">
                            <h5 class="alert-heading"><i class="bi bi-exclamation-circle me-2"></i>Komentar Pengajar</h5>
                            <p><?= nl2br(htmlspecialchars($pengumpulan['komentar_pengajar'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <?php if ($tugas['tipe_pengumpulan'] === 'file' || $tugas['tipe_pengumpulan'] === 'keduanya'): ?>
                            <div class="mb-4">
                                <label for="file_jawaban" class="form-label">
                                    <i class="bi bi-file-earmark-arrow-up me-1"></i>
                                    Upload File Jawaban
                                    <?= $tugas['tipe_pengumpulan'] === 'file' ? '<span class="text-danger">*</span>' : '' ?>
                                </label>
                                <input type="file" class="form-control" id="file_jawaban" name="file_jawaban">
                                <div class="form-text">
                                    Format yang didukung: PDF, DOC, DOCX, JPG, JPEG, PNG, ZIP, RAR, TXT. Maksimal 10 MB.
                                </div>

                                <?php if ($mode_revisi && $pengumpulan['file_jawaban']): ?>
                                    <div class="mt-2">
                                        <strong>File sebelumnya:</strong>
                                        <div class="d-flex align-items-center mt-1">
                                            <i class="bi bi-file-earmark me-2"></i>
                                            <a href="<?= BASE_URL ?>/uploads/tugas/jawaban/<?= $pengumpulan['file_jawaban'] ?>" target="_blank">
                                                <?= $pengumpulan['file_jawaban'] ?>
                                            </a>
                                        </div>
                                        <div class="form-text">
                                            Jika Anda tidak mengupload file baru, file sebelumnya akan tetap digunakan.
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($tugas['tipe_pengumpulan'] === 'teks' || $tugas['tipe_pengumpulan'] === 'keduanya'): ?>
                            <div class="mb-4">
                                <label for="teks_jawaban" class="form-label">
                                    <i class="bi bi-text-paragraph me-1"></i>
                                    Jawaban Teks
                                    <?= $tugas['tipe_pengumpulan'] === 'teks' ? '<span class="text-danger">*</span>' : '' ?>
                                </label>
                                <textarea class="form-control" id="teks_jawaban" name="teks_jawaban" rows="8" placeholder="Masukkan jawaban Anda di sini..."><?= $mode_revisi ? htmlspecialchars($pengumpulan['teks_jawaban']) : '' ?></textarea>
                            </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload me-2"></i><?= $mode_revisi ? 'Kumpulkan Revisi' : 'Kumpulkan Tugas' ?>
                            </button>
                            <a href="<?= BASE_URL ?>/user/tugas/detail?id=<?= $tugas_id ?>&kursus_id=<?= $kursus_id ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Panduan Pengumpulan</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6><i class="bi bi-lightbulb me-2"></i>Tips Pengumpulan</h6>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle text-success me-2"></i>Baca dengan teliti deskripsi dan petunjuk tugas</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Pastikan file dalam format yang didukung</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Periksa kembali tugas sebelum mengumpulkan</li>
                            <li><i class="bi bi-check-circle text-success me-2"></i>Jawab sesuai dengan pertanyaan yang diberikan</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <h6><i class="bi bi-exclamation-triangle me-2"></i>Perhatian</h6>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-x-circle text-danger me-2"></i>Jangan menunggu hingga mendekati tenggat waktu</li>
                            <li><i class="bi bi-x-circle text-danger me-2"></i>Jangan mengumpulkan file yang terinfeksi virus</li>
                            <li><i class="bi bi-x-circle text-danger me-2"></i>Hindari plagiarisme dalam mengerjakan tugas</li>
                        </ul>
                    </div>

                    <div class="alert alert-info mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Jika mengalami kesulitan dalam pengumpulan tugas, silakan hubungi pengajar atau administrator.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/layouts/user/user-layout.php';
userLayout(($mode_revisi ? 'Revisi' : 'Kumpulkan') . ' Tugas - ' . htmlspecialchars($tugas['judul']), $content, 'kursus');
?>