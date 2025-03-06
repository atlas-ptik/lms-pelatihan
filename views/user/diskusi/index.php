<?php
// Path: views/user/diskusi/index.php

require_once BASE_PATH . '/layouts/user/user-layout.php';

$db = dbConnect();
$userId = $_SESSION['user']['id'];

$query = "SELECT d.*, k.judul as judul_kursus, p.nama_lengkap, COUNT(kd.id) as jumlah_komentar 
          FROM diskusi d 
          JOIN kursus k ON d.kursus_id = k.id 
          JOIN pengguna p ON d.pengguna_id = p.id
          LEFT JOIN komentar_diskusi kd ON d.id = kd.diskusi_id AND kd.status = 'aktif'
          WHERE d.status = 'aktif' 
          AND (d.kursus_id IN (SELECT kursus_id FROM pendaftaran WHERE pengguna_id = :user_id)
              OR d.pengguna_id = :user_id)
          GROUP BY d.id
          ORDER BY d.waktu_dibuat DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $userId);
$stmt->execute();
$diskusi = $stmt->fetchAll();

$content = function () use ($diskusi) {
    $baseUrl = BASE_URL;
?>
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Forum Diskusi</h2>
                <a href="<?= $baseUrl ?>/user/diskusi/tambah" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Buat Diskusi
                </a>
            </div>

            <?php if (isset($_SESSION['alert'])): ?>
                <div class="alert alert-<?= $_SESSION['alert']['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $_SESSION['alert']['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['alert']); ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <?php if (empty($diskusi)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-chat-square-text" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="mt-3">Belum ada diskusi. Mulai diskusi pertama Anda!</p>
                            <a href="<?= $baseUrl ?>/user/diskusi/tambah" class="btn btn-primary mt-2">Buat Diskusi</a>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($diskusi as $item): ?>
                                <a href="<?= $baseUrl ?>/user/diskusi/detail?id=<?= $item['id'] ?>" class="list-group-item list-group-item-action border-0 mb-3 shadow-sm rounded">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-1"><?= htmlspecialchars($item['judul']) ?></h5>
                                        <small class="text-muted"><?= date('d M Y, H:i', strtotime($item['waktu_dibuat'])) ?></small>
                                    </div>
                                    <p class="mb-1 text-truncate"><?= htmlspecialchars(substr($item['isi'], 0, 150)) ?>...</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <span class="badge bg-secondary me-2"><?= htmlspecialchars($item['judul_kursus']) ?></span>
                                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($item['nama_lengkap']) ?>
                                        </small>
                                        <span class="badge bg-primary rounded-pill">
                                            <i class="bi bi-chat-dots"></i> <?= $item['jumlah_komentar'] ?> komentar
                                        </span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php
};

userLayout("Forum Diskusi", $content(), "diskusi");
?>