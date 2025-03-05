<?php
// Path: views/admin/pengguna/hapus.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Ambil ID pengguna
$user_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$user_id) {
    header('Location: ' . BASE_URL . '/admin/pengguna');
    exit;
}

$db = dbConnect();

// Cek dahulu apakah pengguna yang akan dihapus ada
$stmt = $db->prepare("SELECT id, username, nama_lengkap, foto_profil FROM pengguna WHERE id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$pengguna = $stmt->fetch();

if (!$pengguna) {
    $_SESSION['error'] = 'Pengguna tidak ditemukan.';
    header('Location: ' . BASE_URL . '/admin/pengguna');
    exit;
}

// Cek apakah ID yang dihapus adalah admin yang sedang login
if (isset($_SESSION['user']['id']) && $_SESSION['user']['id'] === $user_id) {
    $_SESSION['error'] = 'Anda tidak dapat menghapus akun yang sedang digunakan.';
    header('Location: ' . BASE_URL . '/admin/pengguna');
    exit;
}

// Cek apakah ada konfirmasi
$confirmed = isset($_GET['confirmed']) && $_GET['confirmed'] == 'yes';

if ($confirmed) {
    try {
        $db->beginTransaction();

        // Hapus data terkait dari tabel pendaftaran
        $stmt = $db->prepare("DELETE FROM pendaftaran WHERE pengguna_id = :id");
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();

        // Hapus data terkait dari tabel diskusi
        $stmt = $db->prepare("DELETE FROM diskusi WHERE pengguna_id = :id");
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();

        // Hapus data terkait dari tabel komentar_diskusi
        $stmt = $db->prepare("DELETE FROM komentar_diskusi WHERE pengguna_id = :id");
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();

        // Hapus data terkait dari tabel notifikasi
        $stmt = $db->prepare("DELETE FROM notifikasi WHERE pengguna_id = :id");
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();

        // Hapus pengguna
        $stmt = $db->prepare("DELETE FROM pengguna WHERE id = :id");
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();

        // Hapus foto profil jika ada
        if (!empty($pengguna['foto_profil'])) {
            $file_path = BASE_PATH . '/uploads/profil/' . $pengguna['foto_profil'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        $db->commit();

        $_SESSION['success'] = 'Pengguna berhasil dihapus.';
        header('Location: ' . BASE_URL . '/admin/pengguna');
        exit;
    } catch (PDOException $e) {
        $db->rollBack();
        $_SESSION['error'] = 'Terjadi kesalahan: ' . $e->getMessage();
        header('Location: ' . BASE_URL . '/admin/pengguna');
        exit;
    }
}

adminHeader("Hapus Pengguna", "Konfirmasi hapus pengguna");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Hapus Pengguna</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/pengguna">Pengguna</a></li>
            <li class="breadcrumb-item active">Hapus</li>
        </ol>
    </div>

    <div class="card shadow-sm">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Konfirmasi Hapus Pengguna</h6>
        </div>
        <div class="card-body">
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> Peringatan! Tindakan ini tidak dapat dibatalkan.
            </div>

            <div class="text-center mb-4">
                <?php if (!empty($pengguna['foto_profil'])): ?>
                    <img src="<?= BASE_URL ?>/uploads/profil/<?= $pengguna['foto_profil'] ?>" alt="<?= htmlspecialchars($pengguna['nama_lengkap']) ?>" class="rounded-circle mb-3" width="100" height="100">
                <?php else: ?>
                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 100px; height: 100px;">
                        <span class="text-white fw-bold fs-1"><?= strtoupper(substr($pengguna['nama_lengkap'], 0, 1)) ?></span>
                    </div>
                <?php endif; ?>

                <h4><?= htmlspecialchars($pengguna['nama_lengkap']) ?></h4>
                <p class="text-muted"><?= htmlspecialchars($pengguna['username']) ?></p>
            </div>

            <p class="text-center mb-4">
                Apakah Anda yakin ingin menghapus pengguna ini? Semua data terkait dengan pengguna ini, termasuk pendaftaran kursus, diskusi, komentar, dan notifikasi juga akan dihapus permanen.
            </p>

            <div class="d-flex justify-content-center gap-2">
                <a href="<?= BASE_URL ?>/admin/pengguna" class="btn btn-secondary">Batal</a>
                <a href="<?= BASE_URL ?>/admin/pengguna/hapus?id=<?= $user_id ?>&confirmed=yes" class="btn btn-danger">
                    <i class="bi bi-trash me-1"></i> Hapus Permanen
                </a>
            </div>
        </div>
    </div>
</div>

<?php adminFooter(); ?>