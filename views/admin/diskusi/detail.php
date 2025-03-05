<?php
// Path: views/admin/diskusi/detail.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Cek id diskusi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . BASE_URL . '/admin/diskusi');
    exit;
}

$diskusi_id = $_GET['id'];
$db = dbConnect();

// Ambil data diskusi
$stmt = $db->prepare("
    SELECT d.*, p.nama_lengkap as pengguna_nama, p.email as pengguna_email, k.judul as kursus_judul 
    FROM diskusi d
    JOIN pengguna p ON d.pengguna_id = p.id
    JOIN kursus k ON d.kursus_id = k.id
    WHERE d.id = ?
");
$stmt->execute([$diskusi_id]);
$diskusi = $stmt->fetch();

if (!$diskusi) {
    header('Location: ' . BASE_URL . '/admin/diskusi');
    exit;
}

// Update status diskusi
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $update_stmt = $db->prepare("UPDATE diskusi SET status = ? WHERE id = ?");
    $update_stmt->execute([$new_status, $diskusi_id]);

    header('Location: ' . BASE_URL . '/admin/diskusi/detail?id=' . $diskusi_id . '&status_updated=1');
    exit;
}

// Ambil komentar diskusi
$comment_stmt = $db->prepare("
    SELECT kd.*, p.nama_lengkap as pengguna_nama 
    FROM komentar_diskusi kd
    JOIN pengguna p ON kd.pengguna_id = p.id
    WHERE kd.diskusi_id = ?
    ORDER BY kd.waktu_dibuat ASC
");
$comment_stmt->execute([$diskusi_id]);
$komentar_list = $comment_stmt->fetchAll();

// Tambahkan komentar baru (admin)
$comment_error = '';
$comment_success = '';

if (isset($_POST['tambah_komentar'])) {
    $isi_komentar = trim($_POST['isi_komentar']);

    if (empty($isi_komentar)) {
        $comment_error = 'Isi komentar tidak boleh kosong';
    } else {
        $admin_id = $_SESSION['user']['id'];
        $comment_id = generate_uuid();

        $add_comment_stmt = $db->prepare("
            INSERT INTO komentar_diskusi (id, diskusi_id, pengguna_id, isi, status, waktu_dibuat)
            VALUES (?, ?, ?, ?, 'aktif', NOW())
        ");

        $add_comment_stmt->execute([$comment_id, $diskusi_id, $admin_id, $isi_komentar]);
        $comment_success = 'Komentar berhasil ditambahkan';

        // Refresh halaman
        header('Location: ' . BASE_URL . '/admin/diskusi/detail?id=' . $diskusi_id . '&comment_added=1');
        exit;
    }
}

// Hapus komentar
if (isset($_GET['hapus_komentar'])) {
    $komentar_id = $_GET['hapus_komentar'];

    $delete_comment_stmt = $db->prepare("
        UPDATE komentar_diskusi SET status = 'dihapus' WHERE id = ?
    ");
    $delete_comment_stmt->execute([$komentar_id]);

    header('Location: ' . BASE_URL . '/admin/diskusi/detail?id=' . $diskusi_id . '&comment_deleted=1');
    exit;
}

adminHeader("Detail Diskusi", "Lihat detail diskusi pada sistem Atlas LMS");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detail Diskusi</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/diskusi">Diskusi</a></li>
            <li class="breadcrumb-item active">Detail</li>
        </ol>
    </div>

    <?php if (isset($_GET['status_updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Status diskusi berhasil diperbarui
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['comment_added'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Komentar berhasil ditambahkan
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['comment_deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Komentar berhasil dihapus
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0"><?= htmlspecialchars($diskusi['judul']) ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-4 pb-2 border-bottom">
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0">
                                <div class="avatar-placeholder">
                                    <span><?= strtoupper(substr($diskusi['pengguna_nama'], 0, 1)) ?></span>
                                </div>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-1"><?= htmlspecialchars($diskusi['pengguna_nama']) ?></h6>
                                <small class="text-muted">
                                    <i class="bi bi-clock me-1"></i> <?= date('d M Y H:i', strtotime($diskusi['waktu_dibuat'])) ?>
                                </small>
                            </div>
                        </div>
                        <div class="discussion-content">
                            <?= nl2br(htmlspecialchars($diskusi['isi'])) ?>
                        </div>
                    </div>

                    <h5 class="mb-3">Komentar (<?= count($komentar_list) ?>)</h5>

                    <?php if (empty($komentar_list)): ?>
                        <p class="text-center py-3">Belum ada komentar untuk diskusi ini</p>
                    <?php else: ?>
                        <?php foreach ($komentar_list as $komentar): ?>
                            <div class="comment-item mb-3 <?= $komentar['status'] === 'dihapus' ? 'opacity-50' : '' ?>">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <div class="avatar-placeholder small">
                                            <span><?= strtoupper(substr($komentar['pengguna_nama'], 0, 1)) ?></span>
                                        </div>
                                    </div>
                                    <div class="ms-2 flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-0"><?= htmlspecialchars($komentar['pengguna_nama']) ?></h6>
                                                <small class="text-muted"><?= date('d M Y H:i', strtotime($komentar['waktu_dibuat'])) ?></small>
                                            </div>
                                            <?php if ($komentar['status'] !== 'dihapus'): ?>
                                                <a href="<?= BASE_URL ?>/admin/diskusi/detail?id=<?= $diskusi_id ?>&hapus_komentar=<?= $komentar['id'] ?>"
                                                    class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus komentar ini?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($komentar['status'] === 'dihapus'): ?>
                                            <p class="text-muted fst-italic mt-2 mb-0">Komentar ini telah dihapus</p>
                                        <?php else: ?>
                                            <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($komentar['isi'])) ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="mt-4">
                        <h5 class="mb-3">Tambahkan Komentar</h5>
                        <form action="" method="post">
                            <?php if (!empty($comment_error)): ?>
                                <div class="alert alert-danger"><?= $comment_error ?></div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <textarea class="form-control" name="isi_komentar" rows="3" placeholder="Tulis komentar Anda di sini..." required></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" name="tambah_komentar" class="btn btn-primary">
                                    <i class="bi bi-send me-1"></i> Kirim Komentar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Informasi Diskusi</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Status</th>
                            <td>
                                <?php
                                $badge_class = 'bg-secondary';
                                switch ($diskusi['status']) {
                                    case 'aktif':
                                        $badge_class = 'bg-success';
                                        break;
                                    case 'ditutup':
                                        $badge_class = 'bg-warning';
                                        break;
                                    case 'dihapus':
                                        $badge_class = 'bg-danger';
                                        break;
                                }
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= ucfirst($diskusi['status']) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th>Pengguna</th>
                            <td><?= htmlspecialchars($diskusi['pengguna_nama']) ?></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td><?= htmlspecialchars($diskusi['pengguna_email']) ?></td>
                        </tr>
                        <tr>
                            <th>Kursus</th>
                            <td><?= htmlspecialchars($diskusi['kursus_judul']) ?></td>
                        </tr>
                        <tr>
                            <th>Dibuat</th>
                            <td><?= date('d M Y H:i', strtotime($diskusi['waktu_dibuat'])) ?></td>
                        </tr>
                        <tr>
                            <th>Diperbarui</th>
                            <td><?= date('d M Y H:i', strtotime($diskusi['waktu_diperbarui'])) ?></td>
                        </tr>
                    </table>

                    <form action="" method="post" class="mt-3">
                        <div class="mb-3">
                            <label for="status" class="form-label">Ubah Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="aktif" <?= $diskusi['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="ditutup" <?= $diskusi['status'] === 'ditutup' ? 'selected' : '' ?>>Ditutup</option>
                                <option value="dihapus" <?= $diskusi['status'] === 'dihapus' ? 'selected' : '' ?>>Dihapus</option>
                            </select>
                        </div>
                        <button type="submit" name="update_status" class="btn btn-primary w-100">
                            <i class="bi bi-save me-1"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Aksi</h5>
                </div>
                <div class="card-body">
                    <a href="<?= BASE_URL ?>/admin/diskusi" class="btn btn-secondary w-100 mb-2">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                    </a>
                    <a href="<?= BASE_URL ?>/admin/diskusi/hapus?id=<?= $diskusi_id ?>" class="btn btn-danger w-100"
                        onclick="return confirm('Apakah Anda yakin ingin menghapus diskusi ini?')">
                        <i class="bi bi-trash me-1"></i> Hapus Diskusi
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .avatar-placeholder {
        width: 40px;
        height: 40px;
        background-color: var(--primary);
        color: var(--dark);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }

    .avatar-placeholder.small {
        width: 32px;
        height: 32px;
        font-size: 0.8rem;
    }

    .discussion-content {
        font-size: 1rem;
        line-height: 1.6;
    }

    .comment-item {
        padding-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .comment-item:last-child {
        border-bottom: none;
    }
</style>

<?php adminFooter(); ?>