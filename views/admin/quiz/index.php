<?php
// Path: views/admin/quiz/index.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Sambungkan ke database
$db = dbConnect();

// Ambil parameter filter
$materi_id = isset($_GET['materi_id']) ? $_GET['materi_id'] : '';
$kursus_id = isset($_GET['kursus_id']) ? $_GET['kursus_id'] : '';
$cari = isset($_GET['cari']) ? $_GET['cari'] : '';

// Query untuk mendapatkan daftar quiz
$sql = "
    SELECT 
        q.id,
        q.judul,
        q.deskripsi,
        q.durasi_menit,
        q.nilai_lulus,
        q.maksimal_percobaan,
        q.acak_pertanyaan,
        m.judul AS materi_judul,
        mo.judul AS modul_judul,
        k.judul AS kursus_judul,
        (SELECT COUNT(*) FROM pertanyaan_quiz WHERE quiz_id = q.id) AS jumlah_pertanyaan,
        (SELECT COUNT(*) FROM percobaan_quiz WHERE quiz_id = q.id) AS jumlah_percobaan,
        q.waktu_dibuat,
        q.waktu_diperbarui
    FROM 
        quiz q
    JOIN 
        materi m ON q.materi_id = m.id
    JOIN 
        modul mo ON m.modul_id = mo.id
    JOIN 
        kursus k ON mo.kursus_id = k.id
    WHERE 
        1=1
";

$params = [];

if (!empty($materi_id)) {
    $sql .= " AND q.materi_id = :materi_id";
    $params[':materi_id'] = $materi_id;
}

if (!empty($kursus_id)) {
    $sql .= " AND mo.kursus_id = :kursus_id";
    $params[':kursus_id'] = $kursus_id;
}

if (!empty($cari)) {
    $sql .= " AND (q.judul LIKE :cari OR q.deskripsi LIKE :cari OR m.judul LIKE :cari OR k.judul LIKE :cari)";
    $params[':cari'] = "%$cari%";
}

$sql .= " ORDER BY q.waktu_dibuat DESC";

try {
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->execute();
    $quiz_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $quiz_list = [];
}

// Query untuk mendapatkan daftar kursus (untuk filter)
$sql_kursus = "SELECT id, judul FROM kursus ORDER BY judul";
try {
    $stmt = $db->query($sql_kursus);
    $kursus_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $kursus_list = [];
}

// Query untuk mendapatkan daftar materi (untuk filter)
$sql_materi = "
    SELECT 
        m.id, 
        m.judul,
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

// Query untuk mendapatkan statistik quiz
$sql_statistik = "
    SELECT 
        COUNT(q.id) AS total_quiz,
        SUM(CASE WHEN q.acak_pertanyaan = 1 THEN 1 ELSE 0 END) AS quiz_acak,
        AVG(q.nilai_lulus) AS rata_nilai_lulus,
        (SELECT COUNT(*) FROM pertanyaan_quiz) AS total_pertanyaan,
        (SELECT COUNT(*) FROM percobaan_quiz) AS total_percobaan
    FROM 
        quiz q
";
try {
    $stmt = $db->query($sql_statistik);
    $statistik = $stmt->fetch();
} catch (PDOException $e) {
    $statistik = [
        'total_quiz' => 0,
        'quiz_acak' => 0,
        'rata_nilai_lulus' => 0,
        'total_pertanyaan' => 0,
        'total_percobaan' => 0
    ];
}

adminHeader("Manajemen Quiz", "Kelola quiz dan pertanyaan");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manajemen Quiz</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Quiz</li>
        </ol>
    </div>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-question-circle text-primary fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Total Quiz</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik['total_quiz']) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="bi bi-list-check text-success fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Total Pertanyaan</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik['total_pertanyaan']) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="bi bi-clipboard-check text-info fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Total Percobaan</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik['total_percobaan']) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="bi bi-trophy text-warning fs-3"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="card-title text-muted mb-0">Rata-rata Nilai Lulus</h6>
                            <h2 class="mt-2 mb-0"><?= number_format($statistik['rata_nilai_lulus'], 1) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">Daftar Quiz</h6>
            <a href="<?= BASE_URL ?>/admin/quiz/tambah" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Tambah Quiz
            </a>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Cari judul quiz..." name="cari" value="<?= htmlspecialchars($cari) ?>">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="kursus_id" onchange="this.form.submit()">
                            <option value="">-- Pilih Kursus --</option>
                            <?php foreach ($kursus_list as $kursus): ?>
                                <option value="<?= $kursus['id'] ?>" <?= $kursus_id == $kursus['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kursus['judul']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="materi_id" onchange="this.form.submit()">
                            <option value="">-- Pilih Materi --</option>
                            <?php foreach ($materi_list as $materi): ?>
                                <option value="<?= $materi['id'] ?>" <?= $materi_id == $materi['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($materi['materi_lengkap']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <?php if (!empty($cari) || !empty($kursus_id) || !empty($materi_id)): ?>
                            <a href="<?= BASE_URL ?>/admin/quiz" class="btn btn-secondary w-100">Reset</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if (empty($quiz_list)): ?>
                <div class="alert alert-info">
                    Belum ada quiz yang tersedia.
                    <?php if (!empty($cari) || !empty($kursus_id) || !empty($materi_id)): ?>
                        <br>Silakan ubah filter pencarian.
                    <?php else: ?>
                        <br>Silakan buat quiz baru.
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Judul Quiz</th>
                                <th>Kursus - Modul - Materi</th>
                                <th>Durasi (menit)</th>
                                <th>Nilai Lulus</th>
                                <th>Pertanyaan</th>
                                <th>Percobaan</th>
                                <th>Diperbarui</th>
                                <th width="180">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($quiz_list as $quiz): ?>
                                <tr>
                                    <td><?= htmlspecialchars($quiz['judul']) ?></td>
                                    <td>
                                        <small><?= htmlspecialchars($quiz['kursus_judul']) ?> &raquo; <?= htmlspecialchars($quiz['modul_judul']) ?> &raquo; <?= htmlspecialchars($quiz['materi_judul']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($quiz['durasi_menit']): ?>
                                            <?= number_format($quiz['durasi_menit']) ?> menit
                                        <?php else: ?>
                                            <span class="text-muted">Tidak dibatasi</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($quiz['nilai_lulus']) ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= number_format($quiz['jumlah_pertanyaan']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= number_format($quiz['jumlah_percobaan']) ?></span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($quiz['waktu_diperbarui'])) ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/admin/quiz/edit?id=<?= $quiz['id'] ?>" class="btn btn-sm btn-primary mb-1">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/quiz/pertanyaan?quiz_id=<?= $quiz['id'] ?>" class="btn btn-sm btn-success mb-1">
                                            <i class="bi bi-list-check"></i> Pertanyaan
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger mb-1" onclick="hapusQuiz('<?= $quiz['id'] ?>', '<?= htmlspecialchars($quiz['judul']) ?>')">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Hapus Quiz -->
<div class="modal fade" id="hapusQuizModal" tabindex="-1" aria-labelledby="hapusQuizModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="hapusQuizModalLabel">Konfirmasi Hapus Quiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus quiz "<span id="quizTitle"></span>"?</p>
                <p class="text-danger"><strong>Perhatian:</strong> Semua pertanyaan, pilihan jawaban, dan data percobaan terkait quiz ini juga akan dihapus.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="<?= BASE_URL ?>/admin/quiz/hapus" method="POST" id="formHapusQuiz">
                    <input type="hidden" name="id" id="quizId">
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function hapusQuiz(id, judul) {
        document.getElementById('quizId').value = id;
        document.getElementById('quizTitle').textContent = judul;

        const hapusQuizModal = new bootstrap.Modal(document.getElementById('hapusQuizModal'));
        hapusQuizModal.show();
    }
</script>

<?php adminFooter(); ?>