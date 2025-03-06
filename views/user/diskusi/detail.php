<?php
// Path: views/user/diskusi/detail.php

require_once BASE_PATH . '/layouts/user/user-layout.php';

if (!isset($_GET['id'])) {
    header('Location: ' . BASE_URL . '/user/diskusi');
    exit;
}

$diskusiId = $_GET['id'];
$userId = $_SESSION['user']['id'];
$db = dbConnect();

// Ambil data diskusi
$query = "SELECT d.*, k.judul as judul_kursus, p.nama_lengkap, p.foto_profil 
          FROM diskusi d 
          JOIN kursus k ON d.kursus_id = k.id 
          JOIN pengguna p ON d.pengguna_id = p.id
          WHERE d.id = :diskusi_id AND d.status = 'aktif'";

$stmt = $db->prepare($query);
$stmt->bindParam(':diskusi_id', $diskusiId);
$stmt->execute();
$diskusi = $stmt->fetch();

if (!$diskusi) {
    header('Location: ' . BASE_URL . '/user/diskusi');
    exit;
}

// Cek apakah pengguna memiliki akses ke diskusi ini
$queryAkses = "SELECT COUNT(*) as akses FROM pendaftaran 
               WHERE pengguna_id = :user_id AND kursus_id = :kursus_id";
$stmtAkses = $db->prepare($queryAkses);
$stmtAkses->bindParam(':user_id', $userId);
$stmtAkses->bindParam(':kursus_id', $diskusi['kursus_id']);
$stmtAkses->execute();
$hasAccess = $stmtAkses->fetch()['akses'] > 0 || $diskusi['pengguna_id'] == $userId;

if (!$hasAccess) {
    header('Location: ' . BASE_URL . '/user/diskusi');
    exit;
}

// Ambil komentar diskusi
$queryKomentar = "SELECT kd.*, p.nama_lengkap, p.foto_profil 
                  FROM komentar_diskusi kd 
                  JOIN pengguna p ON kd.pengguna_id = p.id
                  WHERE kd.diskusi_id = :diskusi_id AND kd.status = 'aktif'
                  ORDER BY kd.waktu_dibuat ASC";
$stmtKomentar = $db->prepare($queryKomentar);
$stmtKomentar->bindParam(':diskusi_id', $diskusiId);
$stmtKomentar->execute();
$komentar = $stmtKomentar->fetchAll();

// Proses penambahan komentar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['komentar'])) {
    $isiKomentar = trim($_POST['komentar']);

    if (!empty($isiKomentar)) {
        $komentarId = generate_uuid();

        $queryTambah = "INSERT INTO komentar_diskusi (id, diskusi_id, pengguna_id, isi) 
                        VALUES (:id, :diskusi_id, :pengguna_id, :isi)";
        $stmtTambah = $db->prepare($queryTambah);
        $stmtTambah->bindParam(':id', $komentarId);
        $stmtTambah->bindParam(':diskusi_id', $diskusiId);
        $stmtTambah->bindParam(':pengguna_id', $userId);
        $stmtTambah->bindParam(':isi', $isiKomentar);

        if ($stmtTambah->execute()) {
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Komentar berhasil ditambahkan'
            ];
            header('Location: ' . BASE_URL . '/user/diskusi/detail?id=' . $diskusiId);
            exit;
        } else {
            $_SESSION['alert'] = [
                'type' => 'danger',
                'message' => 'Gagal menambahkan komentar'
            ];
        }
    } else {
        $_SESSION['alert'] = [
            'type' => 'warning',
            'message' => 'Komentar tidak boleh kosong'
        ];
    }
}

$content = function () use ($diskusi, $komentar, $userId) {
    $baseUrl = BASE_URL;
?>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">Detail Diskusi</h2>
                <a href="<?= $baseUrl ?>/user/diskusi" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <?php if (isset($_SESSION['alert'])): ?>
                <div class="alert alert-<?= $_SESSION['alert']['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['alert']['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['alert']); ?>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= htmlspecialchars($diskusi['judul']) ?></h5>
                    <span class="badge bg-secondary"><?= htmlspecialchars($diskusi['judul_kursus']) ?></span>
                </div>
                <div class="card-body">
                    <div class="d-flex mb-3">
                        <div class="flex-shrink-0">
                            <?php if (!empty($diskusi['foto_profil'])): ?>
                                <img src="<?= $baseUrl ?>/uploads/profil/<?= $diskusi['foto_profil'] ?>"
                                    class="rounded-circle" width="50" height="50" alt="<?= htmlspecialchars($diskusi['nama_lengkap']) ?>"
                                    style="object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary text-white d-flex justify-content-center align-items-center"
                                    style="width: 50px; height: 50px;">
                                    <i class="bi bi-person-fill" style="font-size: 1.5rem;"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0"><?= htmlspecialchars($diskusi['nama_lengkap']) ?></h6>
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> <?= date('d M Y, H:i', strtotime($diskusi['waktu_dibuat'])) ?>
                            </small>
                        </div>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded">
                        <?= nl2br(htmlspecialchars($diskusi['isi'])) ?>
                    </div>
                </div>
            </div>

            <h4 class="mb-3">Komentar (<?= count($komentar) ?>)</h4>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="POST" action="<?= $baseUrl ?>/user/diskusi/detail?id=<?= $diskusi['id'] ?>">
                        <div class="mb-3">
                            <label for="komentar" class="form-label">Tambahkan Komentar</label>
                            <textarea class="form-control" id="komentar" name="komentar" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Kirim Komentar
                        </button>
                    </form>
                </div>
            </div>

            <?php if (empty($komentar)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-chat-dots" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="mt-3">Belum ada komentar. Jadilah yang pertama berkomentar!</p>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body">
                        <?php foreach ($komentar as $key => $item): ?>
                            <div class="d-flex mb-4 <?= ($item['pengguna_id'] == $userId) ? 'border-start border-4 border-primary ps-3' : '' ?>">
                                <div class="flex-shrink-0">
                                    <?php if (!empty($item['foto_profil'])): ?>
                                        <img src="<?= $baseUrl ?>/uploads/profil/<?= $item['foto_profil'] ?>"
                                            class="rounded-circle" width="40" height="40" alt="<?= htmlspecialchars($item['nama_lengkap']) ?>"
                                            style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-secondary text-white d-flex justify-content-center align-items-center"
                                            style="width: 40px; height: 40px;">
                                            <i class="bi bi-person-fill"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <?= htmlspecialchars($item['nama_lengkap']) ?>
                                            <?php if ($item['pengguna_id'] == $diskusi['pengguna_id']): ?>
                                                <span class="badge bg-info ms-1">Penulis</span>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?= date('d M Y, H:i', strtotime($item['waktu_dibuat'])) ?>
                                        </small>
                                    </div>
                                    <div class="mt-2">
                                        <?= nl2br(htmlspecialchars($item['isi'])) ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ($key < count($komentar) - 1): ?>
                                <hr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php
};

userLayout("Detail Diskusi", $content(), "diskusi");
?>