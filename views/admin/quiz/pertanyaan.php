<?php
// Path: views/admin/quiz/pertanyaan.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Sambungkan ke database
$db = dbConnect();

// Ambil ID quiz dari URL
$quiz_id = $_GET['quiz_id'] ?? '';
$is_new_quiz = isset($_GET['new']) && $_GET['new'] == 1;

if (empty($quiz_id)) {
    header('Location: ' . BASE_URL . '/admin/quiz');
    exit;
}

// Query untuk mendapatkan informasi quiz
$sql_quiz = "
    SELECT 
        q.*,
        m.judul AS materi_judul,
        mo.judul AS modul_judul,
        k.judul AS kursus_judul
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

// Proses tambah pertanyaan baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_pertanyaan') {
    $tipe = $_POST['tipe'] ?? '';
    $pertanyaan = $_POST['pertanyaan'] ?? '';
    $bobot_nilai = $_POST['bobot_nilai'] ?? 1;
    $urutan = $_POST['urutan'] ?? 0;
    $gambar = '';

    $errors = [];

    // Validasi input
    if (empty($tipe)) {
        $errors[] = 'Tipe pertanyaan wajib dipilih';
    }

    if (empty($pertanyaan)) {
        $errors[] = 'Pertanyaan wajib diisi';
    }

    // Upload gambar jika ada
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($_FILES['gambar']['type'], $allowed_types)) {
            $errors[] = 'Tipe file tidak didukung. Hanya JPG, PNG, dan GIF yang diizinkan.';
        } elseif ($_FILES['gambar']['size'] > $max_size) {
            $errors[] = 'Ukuran file terlalu besar. Maksimal 2MB.';
        } else {
            $upload_dir = BASE_PATH . '/uploads/quiz/';

            // Buat direktori jika belum ada
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION);
            $new_filename = 'quiz_' . uniqid() . '.' . $file_extension;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['gambar']['tmp_name'], $destination)) {
                $gambar = $new_filename;
            } else {
                $errors[] = 'Gagal mengupload gambar.';
            }
        }
    }

    // Jika tidak ada error, simpan pertanyaan
    if (empty($errors)) {
        try {
            $pertanyaan_id = generate_uuid();

            $sql = "
                INSERT INTO pertanyaan_quiz (
                    id, quiz_id, tipe, pertanyaan, gambar, bobot_nilai, urutan, waktu_dibuat, waktu_diperbarui
                ) VALUES (
                    :id, :quiz_id, :tipe, :pertanyaan, :gambar, :bobot_nilai, :urutan, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                )
            ";

            $stmt = $db->prepare($sql);
            $stmt->bindValue(':id', $pertanyaan_id);
            $stmt->bindValue(':quiz_id', $quiz_id);
            $stmt->bindValue(':tipe', $tipe);
            $stmt->bindValue(':pertanyaan', $pertanyaan);
            $stmt->bindValue(':gambar', $gambar);
            $stmt->bindValue(':bobot_nilai', $bobot_nilai);
            $stmt->bindValue(':urutan', $urutan);
            $stmt->execute();

            // Jika tipe pilihan ganda atau benar/salah, tambahkan pilihan jawaban
            if ($tipe == 'pilihan_ganda' || $tipe == 'benar_salah') {
                $pilihan = $_POST['pilihan'] ?? [];
                $is_benar = $_POST['is_benar'] ?? [];

                foreach ($pilihan as $index => $teks_jawaban) {
                    if (!empty($teks_jawaban)) {
                        $pilihan_id = generate_uuid();
                        $benar = isset($is_benar[$index]) ? 1 : 0;

                        $sql_pilihan = "
                            INSERT INTO pilihan_jawaban (
                                id, pertanyaan_id, teks_jawaban, benar, urutan, waktu_dibuat, waktu_diperbarui
                            ) VALUES (
                                :id, :pertanyaan_id, :teks_jawaban, :benar, :urutan, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                            )
                        ";

                        $stmt_pilihan = $db->prepare($sql_pilihan);
                        $stmt_pilihan->bindValue(':id', $pilihan_id);
                        $stmt_pilihan->bindValue(':pertanyaan_id', $pertanyaan_id);
                        $stmt_pilihan->bindValue(':teks_jawaban', $teks_jawaban);
                        $stmt_pilihan->bindValue(':benar', $benar);
                        $stmt_pilihan->bindValue(':urutan', $index);
                        $stmt_pilihan->execute();
                    }
                }
            }

            // Set flash message dan redirect
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Pertanyaan berhasil ditambahkan'
            ];

            header('Location: ' . BASE_URL . '/admin/quiz/pertanyaan?quiz_id=' . $quiz_id);
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Proses hapus pertanyaan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hapus_pertanyaan') {
    $pertanyaan_id = $_POST['pertanyaan_id'] ?? '';

    if (!empty($pertanyaan_id)) {
        try {
            // Hapus pilihan jawaban terlebih dahulu
            $sql_hapus_pilihan = "DELETE FROM pilihan_jawaban WHERE pertanyaan_id = :pertanyaan_id";
            $stmt_hapus_pilihan = $db->prepare($sql_hapus_pilihan);
            $stmt_hapus_pilihan->bindValue(':pertanyaan_id', $pertanyaan_id);
            $stmt_hapus_pilihan->execute();

            // Lalu hapus pertanyaan
            $sql_hapus_pertanyaan = "DELETE FROM pertanyaan_quiz WHERE id = :id AND quiz_id = :quiz_id";
            $stmt_hapus_pertanyaan = $db->prepare($sql_hapus_pertanyaan);
            $stmt_hapus_pertanyaan->bindValue(':id', $pertanyaan_id);
            $stmt_hapus_pertanyaan->bindValue(':quiz_id', $quiz_id);
            $stmt_hapus_pertanyaan->execute();

            // Set flash message
            $_SESSION['flash_message'] = [
                'type' => 'success',
                'message' => 'Pertanyaan berhasil dihapus'
            ];
        } catch (PDOException $e) {
            $_SESSION['flash_message'] = [
                'type' => 'danger',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ];
        }

        header('Location: ' . BASE_URL . '/admin/quiz/pertanyaan?quiz_id=' . $quiz_id);
        exit;
    }
}

// Query untuk mendapatkan daftar pertanyaan
$sql_pertanyaan = "
    SELECT 
        p.*,
        (SELECT COUNT(*) FROM pilihan_jawaban WHERE pertanyaan_id = p.id) AS jumlah_pilihan,
        (SELECT COUNT(*) FROM pilihan_jawaban WHERE pertanyaan_id = p.id AND benar = 1) AS jumlah_jawaban_benar
    FROM 
        pertanyaan_quiz p
    WHERE 
        p.quiz_id = :quiz_id
    ORDER BY 
        p.urutan ASC, p.waktu_dibuat ASC
";

try {
    $stmt = $db->prepare($sql_pertanyaan);
    $stmt->bindValue(':quiz_id', $quiz_id);
    $stmt->execute();
    $pertanyaan_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $pertanyaan_list = [];
}

// Query untuk mendapatkan jumlah urutan pertanyaan terakhir
$sql_urutan = "SELECT MAX(urutan) + 1 AS urutan_baru FROM pertanyaan_quiz WHERE quiz_id = :quiz_id";
try {
    $stmt = $db->prepare($sql_urutan);
    $stmt->bindValue(':quiz_id', $quiz_id);
    $stmt->execute();
    $urutan_baru = $stmt->fetch()['urutan_baru'] ?? 1;
} catch (PDOException $e) {
    $urutan_baru = 1;
}

adminHeader("Pertanyaan Quiz", "Kelola pertanyaan quiz");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Pertanyaan Quiz</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/quiz">Quiz</a></li>
            <li class="breadcrumb-item active">Pertanyaan</li>
        </ol>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_message']['type'] ?> alert-dismissible fade show mb-4" role="alert">
            <?= $_SESSION['flash_message']['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); ?>
    <?php endif; ?>

    <?php if ($is_new_quiz): ?>
        <div class="alert alert-success mb-4">
            <i class="bi bi-check-circle me-2"></i> Quiz berhasil dibuat! Silakan tambahkan pertanyaan untuk quiz ini.
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">Detail Quiz</h6>
            <a href="<?= BASE_URL ?>/admin/quiz/edit?id=<?= $quiz_id ?>" class="btn btn-sm btn-primary">
                <i class="bi bi-pencil"></i> Edit Quiz
            </a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th width="150">Judul Quiz</th>
                            <td><?= htmlspecialchars($quiz['judul']) ?></td>
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
                    <table class="table table-bordered">
                        <tr>
                            <th width="180">Durasi</th>
                            <td><?= $quiz['durasi_menit'] ? $quiz['durasi_menit'] . ' menit' : 'Tidak dibatasi' ?></td>
                        </tr>
                        <tr>
                            <th>Nilai Lulus</th>
                            <td><?= number_format($quiz['nilai_lulus']) ?></td>
                        </tr>
                        <tr>
                            <th>Maksimal Percobaan</th>
                            <td><?= $quiz['maksimal_percobaan'] ? number_format($quiz['maksimal_percobaan']) : 'Tidak dibatasi' ?></td>
                        </tr>
                        <tr>
                            <th>Acak Pertanyaan</th>
                            <td><?= $quiz['acak_pertanyaan'] ? 'Ya' : 'Tidak' ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold">Daftar Pertanyaan (<?= count($pertanyaan_list) ?>)</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($pertanyaan_list)): ?>
                        <div class="alert alert-info">
                            Belum ada pertanyaan untuk quiz ini. Silakan tambahkan pertanyaan menggunakan form di bawah.
                        </div>
                    <?php else: ?>
                        <div class="accordion mb-4" id="accordionPertanyaan">
                            <?php foreach ($pertanyaan_list as $index => $pertanyaan): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?= $index ?>">
                                        <button class="accordion-button <?= $index !== 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $index ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $index ?>">
                                            <span class="me-2"><?= $index + 1 ?>.</span>
                                            <?= htmlspecialchars($pertanyaan['pertanyaan']) ?>
                                            <span class="badge bg-primary ms-2"><?= ucfirst(str_replace('_', ' ', $pertanyaan['tipe'])) ?></span>
                                            <?php if ($pertanyaan['gambar']): ?>
                                                <span class="badge bg-info ms-1"><i class="bi bi-image"></i></span>
                                            <?php endif; ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?= $index ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $index ?>" data-bs-parent="#accordionPertanyaan">
                                        <div class="accordion-body">
                                            <div class="row">
                                                <?php if ($pertanyaan['gambar']): ?>
                                                    <div class="col-md-4 mb-3">
                                                        <img src="<?= BASE_URL ?>/uploads/quiz/<?= $pertanyaan['gambar'] ?>" alt="Gambar pertanyaan" class="img-fluid border rounded">
                                                    </div>
                                                <?php endif; ?>

                                                <div class="<?= $pertanyaan['gambar'] ? 'col-md-8' : 'col-12' ?>">
                                                    <div class="mb-3">
                                                        <h6>Pertanyaan:</h6>
                                                        <p><?= nl2br(htmlspecialchars($pertanyaan['pertanyaan'])) ?></p>
                                                    </div>

                                                    <?php if ($pertanyaan['tipe'] == 'pilihan_ganda' || $pertanyaan['tipe'] == 'benar_salah'): ?>
                                                        <div class="mb-3">
                                                            <h6>Pilihan Jawaban:</h6>
                                                            <?php
                                                            // Ambil pilihan jawaban
                                                            $sql_pilihan = "
                                                                SELECT *
                                                                FROM pilihan_jawaban
                                                                WHERE pertanyaan_id = :pertanyaan_id
                                                                ORDER BY urutan ASC
                                                            ";
                                                            $stmt_pilihan = $db->prepare($sql_pilihan);
                                                            $stmt_pilihan->bindValue(':pertanyaan_id', $pertanyaan['id']);
                                                            $stmt_pilihan->execute();
                                                            $pilihan_list = $stmt_pilihan->fetchAll();

                                                            foreach ($pilihan_list as $pilihan_index => $pilihan):
                                                            ?>
                                                                <div class="form-check mb-2">
                                                                    <input class="form-check-input" type="radio" disabled <?= $pilihan['benar'] ? 'checked' : '' ?>>
                                                                    <label class="form-check-label <?= $pilihan['benar'] ? 'text-success fw-bold' : '' ?>">
                                                                        <?= chr(65 + $pilihan_index) ?>. <?= htmlspecialchars($pilihan['teks_jawaban']) ?>
                                                                        <?php if ($pilihan['benar']): ?>
                                                                            <span class="badge bg-success ms-1">Jawaban Benar</span>
                                                                        <?php endif; ?>
                                                                    </label>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php elseif ($pertanyaan['tipe'] == 'isian'): ?>
                                                        <div class="mb-3">
                                                            <h6>Jenis Jawaban:</h6>
                                                            <p>Isian bebas (text)</p>
                                                        </div>
                                                    <?php elseif ($pertanyaan['tipe'] == 'esai'): ?>
                                                        <div class="mb-3">
                                                            <h6>Jenis Jawaban:</h6>
                                                            <p>Esai/jawaban panjang (textarea)</p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <div class="d-flex justify-content-between mt-3">
                                                        <div>
                                                            <span class="badge bg-secondary me-2">Urutan: <?= $pertanyaan['urutan'] ?></span>
                                                            <span class="badge bg-warning me-2">Bobot: <?= $pertanyaan['bobot_nilai'] ?></span>
                                                        </div>
                                                        <div>
                                                            <button type="button" class="btn btn-sm btn-primary me-1" onclick="editPertanyaan('<?= $pertanyaan['id'] ?>')">
                                                                <i class="bi bi-pencil"></i> Edit
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger" onclick="hapusPertanyaan('<?= $pertanyaan['id'] ?>', '<?= $index + 1 ?>')">
                                                                <i class="bi bi-trash"></i> Hapus
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Tambah Pertanyaan Baru</h6>
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
                <input type="hidden" name="action" value="tambah_pertanyaan">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="tipe" class="form-label">Tipe Pertanyaan <span class="text-danger">*</span></label>
                        <select class="form-select" id="tipe" name="tipe" required onchange="togglePilihanJawaban()">
                            <option value="">-- Pilih Tipe --</option>
                            <option value="pilihan_ganda" <?= isset($_POST['tipe']) && $_POST['tipe'] == 'pilihan_ganda' ? 'selected' : '' ?>>Pilihan Ganda</option>
                            <option value="benar_salah" <?= isset($_POST['tipe']) && $_POST['tipe'] == 'benar_salah' ? 'selected' : '' ?>>Benar/Salah</option>
                            <option value="isian" <?= isset($_POST['tipe']) && $_POST['tipe'] == 'isian' ? 'selected' : '' ?>>Isian Singkat</option>
                            <option value="esai" <?= isset($_POST['tipe']) && $_POST['tipe'] == 'esai' ? 'selected' : '' ?>>Esai</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="bobot_nilai" class="form-label">Bobot Nilai</label>
                        <input type="number" class="form-control" id="bobot_nilai" name="bobot_nilai" min="1" value="<?= isset($_POST['bobot_nilai']) ? htmlspecialchars($_POST['bobot_nilai']) : '1' ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="urutan" class="form-label">Urutan</label>
                        <input type="number" class="form-control" id="urutan" name="urutan" min="0" value="<?= isset($_POST['urutan']) ? htmlspecialchars($_POST['urutan']) : $urutan_baru ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="gambar" class="form-label">Gambar (opsional)</label>
                        <input type="file" class="form-control" id="gambar" name="gambar" accept="image/*">
                        <div class="form-text">Format: JPG, PNG, GIF. Maks: 2MB</div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-12">
                        <label for="pertanyaan" class="form-label">Pertanyaan <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="pertanyaan" name="pertanyaan" rows="3" required><?= isset($_POST['pertanyaan']) ? htmlspecialchars($_POST['pertanyaan']) : '' ?></textarea>
                    </div>
                </div>

                <div id="pilihan_jawaban_container" style="display: none;">
                    <h6 class="mb-3">Pilihan Jawaban</h6>

                    <div id="pilihan_ganda_options" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="input-group mb-2">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="radio" name="is_benar[0]" checked>
                                    </div>
                                    <input type="text" class="form-control" name="pilihan[0]" placeholder="Pilihan A (Jawaban Benar)">
                                </div>
                                <div class="input-group mb-2">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="radio" name="is_benar[1]">
                                    </div>
                                    <input type="text" class="form-control" name="pilihan[1]" placeholder="Pilihan B">
                                </div>
                                <div class="input-group mb-2">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="radio" name="is_benar[2]">
                                    </div>
                                    <input type="text" class="form-control" name="pilihan[2]" placeholder="Pilihan C">
                                </div>
                                <div class="input-group mb-2">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="radio" name="is_benar[3]">
                                    </div>
                                    <input type="text" class="form-control" name="pilihan[3]" placeholder="Pilihan D">
                                </div>
                                <div class="input-group mb-2">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="radio" name="is_benar[4]">
                                    </div>
                                    <input type="text" class="form-control" name="pilihan[4]" placeholder="Pilihan E (opsional)">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i> Pilih satu jawaban yang benar dengan mengklik tombol radio di sebelah kiri.
                                </div>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i> Minimal harus ada 2 pilihan jawaban. Anda dapat mengosongkan pilihan yang tidak diperlukan.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="benar_salah_options" style="display: none;">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="input-group mb-2">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="radio" name="is_benar[0]" checked>
                                    </div>
                                    <input type="text" class="form-control" name="pilihan[0]" value="Benar" readonly>
                                </div>
                                <div class="input-group mb-2">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="radio" name="is_benar[1]">
                                    </div>
                                    <input type="text" class="form-control" name="pilihan[1]" value="Salah" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i> Pilih jawaban yang benar (Benar atau Salah).
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>/admin/quiz" class="btn btn-secondary">Kembali ke Daftar Quiz</a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Pertanyaan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Pertanyaan -->
<div class="modal fade" id="hapusPertanyaanModal" tabindex="-1" aria-labelledby="hapusPertanyaanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hapusPertanyaanModalLabel">Konfirmasi Hapus Pertanyaan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus pertanyaan #<span id="pertanyaanNumber"></span>?</p>
                <p class="text-danger"><strong>Perhatian:</strong> Semua pilihan jawaban terkait pertanyaan ini juga akan dihapus.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="" method="POST" id="formHapusPertanyaan">
                    <input type="hidden" name="action" value="hapus_pertanyaan">
                    <input type="hidden" name="pertanyaan_id" id="pertanyaanId">
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function togglePilihanJawaban() {
        const tipe = document.getElementById('tipe').value;
        const pilihanContainer = document.getElementById('pilihan_jawaban_container');
        const pilihanGandaOptions = document.getElementById('pilihan_ganda_options');
        const benarSalahOptions = document.getElementById('benar_salah_options');

        if (tipe === 'pilihan_ganda') {
            pilihanContainer.style.display = 'block';
            pilihanGandaOptions.style.display = 'block';
            benarSalahOptions.style.display = 'none';
        } else if (tipe === 'benar_salah') {
            pilihanContainer.style.display = 'block';
            pilihanGandaOptions.style.display = 'none';
            benarSalahOptions.style.display = 'block';
        } else {
            pilihanContainer.style.display = 'none';
        }
    }

    function hapusPertanyaan(id, number) {
        document.getElementById('pertanyaanId').value = id;
        document.getElementById('pertanyaanNumber').textContent = number;

        const hapusPertanyaanModal = new bootstrap.Modal(document.getElementById('hapusPertanyaanModal'));
        hapusPertanyaanModal.show();
    }

    function editPertanyaan(id) {
        window.location.href = '<?= BASE_URL ?>/admin/quiz/edit-pertanyaan?id=' + id + '&quiz_id=<?= $quiz_id ?>';
    }

    document.addEventListener('DOMContentLoaded', function() {
        togglePilihanJawaban();
    });
</script>

<?php adminFooter(); ?>