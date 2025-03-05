<?php
// Path: views/admin/modul/index.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Manajemen Modul", "Kelola modul kursus di Atlas LMS");

$db = dbConnect();

// Ambil kursus_id dari parameter
$kursus_id = isset($_GET['kursus_id']) ? $_GET['kursus_id'] : '';

// Jika tidak ada kursus_id, tampilkan daftar kursus
if (empty($kursus_id)) {
    // Ambil semua kursus
    $stmt = $db->prepare("SELECT k.id, k.judul, k.status, k.gambar_sampul, p.nama_lengkap as pembuat_nama,
                         (SELECT COUNT(*) FROM modul WHERE kursus_id = k.id) as jumlah_modul
                         FROM kursus k
                         JOIN pengguna p ON k.pembuat_id = p.id
                         ORDER BY k.judul ASC");
    $stmt->execute();
    $kursusList = $stmt->fetchAll();

    // Tampilkan daftar kursus
?>
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-6">
                <h1 class="page-title">Manajemen Modul</h1>
                <p class="text-muted">Pilih kursus untuk mengelola modulnya</p>
            </div>
        </div>

        <div class="row">
            <?php if (empty($kursusList)): ?>
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i> Belum ada kursus yang tersedia.
                        <a href="<?= BASE_URL ?>/admin/kursus/tambah" class="alert-link">Tambah kursus baru</a> terlebih dahulu.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($kursusList as $kursus): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><?= htmlspecialchars($kursus['judul']) ?></h5>
                                <span class="badge <?= $kursus['status'] === 'publikasi' ? 'bg-success' : ($kursus['status'] === 'draf' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                    <?= ucfirst($kursus['status']) ?>
                                </span>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <?php if (!empty($kursus['gambar_sampul'])): ?>
                                            <img src="<?= BASE_URL ?>/uploads/kursus/<?= $kursus['gambar_sampul'] ?>" alt="<?= htmlspecialchars($kursus['judul']) ?>" class="img-thumbnail" width="60">
                                        <?php else: ?>
                                            <div class="img-placeholder bg-light text-center rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ms-3">
                                        <div class="text-muted small">Pengajar: <?= htmlspecialchars($kursus['pembuat_nama']) ?></div>
                                        <div class="text-primary"><i class="bi bi-folder me-1"></i> <?= $kursus['jumlah_modul'] ?> modul</div>
                                    </div>
                                </div>

                                <div class="mt-auto d-flex justify-content-between">
                                    <a href="<?= BASE_URL ?>/admin/kursus/edit?id=<?= $kursus['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i> Edit Kursus
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/modul?kursus_id=<?= $kursus['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-folder"></i> Kelola Modul
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
<?php
} else {
    // Ambil data kursus
    $stmt = $db->prepare("SELECT * FROM kursus WHERE id = ?");
    $stmt->execute([$kursus_id]);
    $kursus = $stmt->fetch();

    if (!$kursus) {
        // Kursus tidak ditemukan
        header("Location: " . BASE_URL . "/admin/modul");
        exit;
    }

    // Ambil semua modul untuk kursus ini
    $stmt = $db->prepare("SELECT *, 
                         (SELECT COUNT(*) FROM materi WHERE modul_id = modul.id) as jumlah_materi
                         FROM modul 
                         WHERE kursus_id = ? 
                         ORDER BY urutan ASC");
    $stmt->execute([$kursus_id]);
    $modulList = $stmt->fetchAll();

    // Pesan notifikasi
    $pesan = '';
    $tipe = '';

    // Proses penanganan aksi
    if (isset($_GET['action'])) {
        $action = $_GET['action'];

        // Hapus modul
        if ($action === 'delete' && isset($_GET['id'])) {
            $modul_id = $_GET['id'];

            try {
                // Cek apakah modul memiliki materi
                $stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM materi WHERE modul_id = ?");
                $stmt->execute([$modul_id]);
                $jumlahMateri = $stmt->fetch()['jumlah'];

                if ($jumlahMateri > 0) {
                    $pesan = "Tidak dapat menghapus modul karena terdapat materi di dalamnya. Hapus materi terlebih dahulu.";
                    $tipe = "danger";
                } else {
                    // Hapus modul
                    $stmt = $db->prepare("DELETE FROM modul WHERE id = ?");
                    $stmt->execute([$modul_id]);

                    $pesan = "Modul berhasil dihapus";
                    $tipe = "success";

                    // Update urutan modul
                    $stmt = $db->prepare("SELECT id FROM modul WHERE kursus_id = ? ORDER BY urutan ASC");
                    $stmt->execute([$kursus_id]);
                    $moduls = $stmt->fetchAll();

                    $urutan = 1;
                    foreach ($moduls as $m) {
                        $stmt = $db->prepare("UPDATE modul SET urutan = ? WHERE id = ?");
                        $stmt->execute([$urutan, $m['id']]);
                        $urutan++;
                    }

                    // Refresh data modul
                    $stmt = $db->prepare("SELECT *, 
                                         (SELECT COUNT(*) FROM materi WHERE modul_id = modul.id) as jumlah_materi
                                         FROM modul 
                                         WHERE kursus_id = ? 
                                         ORDER BY urutan ASC");
                    $stmt->execute([$kursus_id]);
                    $modulList = $stmt->fetchAll();
                }
            } catch (PDOException $e) {
                $pesan = "Gagal menghapus modul: " . $e->getMessage();
                $tipe = "danger";
            }
        }

        // Ubah urutan modul
        if ($action === 'move_up' && isset($_GET['id'])) {
            $modul_id = $_GET['id'];

            try {
                // Ambil data modul
                $stmt = $db->prepare("SELECT * FROM modul WHERE id = ?");
                $stmt->execute([$modul_id]);
                $modul = $stmt->fetch();

                if ($modul && $modul['urutan'] > 1) {
                    // Ambil modul sebelumnya
                    $stmt = $db->prepare("SELECT * FROM modul WHERE kursus_id = ? AND urutan = ?");
                    $stmt->execute([$kursus_id, ($modul['urutan'] - 1)]);
                    $modulSebelumnya = $stmt->fetch();

                    if ($modulSebelumnya) {
                        // Tukar urutan
                        $stmt = $db->prepare("UPDATE modul SET urutan = ? WHERE id = ?");
                        $stmt->execute([$modulSebelumnya['urutan'], $modul_id]);

                        $stmt = $db->prepare("UPDATE modul SET urutan = ? WHERE id = ?");
                        $stmt->execute([$modul['urutan'], $modulSebelumnya['id']]);

                        $pesan = "Urutan modul berhasil diubah";
                        $tipe = "success";

                        // Refresh data modul
                        $stmt = $db->prepare("SELECT *, 
                                             (SELECT COUNT(*) FROM materi WHERE modul_id = modul.id) as jumlah_materi
                                             FROM modul 
                                             WHERE kursus_id = ? 
                                             ORDER BY urutan ASC");
                        $stmt->execute([$kursus_id]);
                        $modulList = $stmt->fetchAll();
                    }
                }
            } catch (PDOException $e) {
                $pesan = "Gagal mengubah urutan modul: " . $e->getMessage();
                $tipe = "danger";
            }
        }

        if ($action === 'move_down' && isset($_GET['id'])) {
            $modul_id = $_GET['id'];

            try {
                // Ambil data modul
                $stmt = $db->prepare("SELECT * FROM modul WHERE id = ?");
                $stmt->execute([$modul_id]);
                $modul = $stmt->fetch();

                // Hitung jumlah total modul
                $stmt = $db->prepare("SELECT COUNT(*) as total FROM modul WHERE kursus_id = ?");
                $stmt->execute([$kursus_id]);
                $totalModul = $stmt->fetch()['total'];

                if ($modul && $modul['urutan'] < $totalModul) {
                    // Ambil modul setelahnya
                    $stmt = $db->prepare("SELECT * FROM modul WHERE kursus_id = ? AND urutan = ?");
                    $stmt->execute([$kursus_id, ($modul['urutan'] + 1)]);
                    $modulSetelahnya = $stmt->fetch();

                    if ($modulSetelahnya) {
                        // Tukar urutan
                        $stmt = $db->prepare("UPDATE modul SET urutan = ? WHERE id = ?");
                        $stmt->execute([$modulSetelahnya['urutan'], $modul_id]);

                        $stmt = $db->prepare("UPDATE modul SET urutan = ? WHERE id = ?");
                        $stmt->execute([$modul['urutan'], $modulSetelahnya['id']]);

                        $pesan = "Urutan modul berhasil diubah";
                        $tipe = "success";

                        // Refresh data modul
                        $stmt = $db->prepare("SELECT *, 
                                             (SELECT COUNT(*) FROM materi WHERE modul_id = modul.id) as jumlah_materi
                                             FROM modul 
                                             WHERE kursus_id = ? 
                                             ORDER BY urutan ASC");
                        $stmt->execute([$kursus_id]);
                        $modulList = $stmt->fetchAll();
                    }
                }
            } catch (PDOException $e) {
                $pesan = "Gagal mengubah urutan modul: " . $e->getMessage();
                $tipe = "danger";
            }
        }
    }

    // Tampilkan daftar modul untuk kursus tersebut
?>
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-6">
                <h1 class="page-title">Manajemen Modul</h1>
                <p class="text-muted">
                    Kursus: <strong><?= htmlspecialchars($kursus['judul']) ?></strong>
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="<?= BASE_URL ?>/admin/modul" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <a href="<?= BASE_URL ?>/admin/modul/tambah?kursus_id=<?= $kursus_id ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Tambah Modul
                </a>
            </div>
        </div>

        <?php if (!empty($pesan)): ?>
            <div class="alert alert-<?= $tipe ?> alert-dismissible fade show" role="alert">
                <?= $pesan ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Kursus: <?= htmlspecialchars($kursus['judul']) ?></h5>
                    <span class="badge <?= $kursus['status'] === 'publikasi' ? 'bg-success' : ($kursus['status'] === 'draf' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                        <?= ucfirst($kursus['status']) ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label text-muted">Deskripsi Kursus</label>
                            <p><?= nl2br(htmlspecialchars($kursus['deskripsi'])) ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label text-muted">Informasi Kursus</label>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Level
                                    <span class="badge bg-secondary rounded-pill"><?= ucfirst($kursus['level']) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Durasi
                                    <span><?= !empty($kursus['durasi_menit']) ? $kursus['durasi_menit'] . ' menit' : 'N/A' ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Harga
                                    <span class="<?= $kursus['harga'] > 0 ? 'text-success' : '' ?>">
                                        <?= $kursus['harga'] > 0 ? 'Rp ' . number_format($kursus['harga'], 0, ',', '.') : 'Gratis' ?>
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Modul</h5>
                <span class="badge bg-primary rounded-pill"><?= count($modulList) ?> modul</span>
            </div>
            <div class="card-body">
                <?php if (empty($modulList)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i> Belum ada modul untuk kursus ini.
                        <a href="<?= BASE_URL ?>/admin/modul/tambah?kursus_id=<?= $kursus_id ?>" class="alert-link">Tambah modul baru</a> untuk memulai.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th width="60">Urutan</th>
                                    <th>Judul Modul</th>
                                    <th>Deskripsi</th>
                                    <th>Jumlah Materi</th>
                                    <th width="200">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="sortable">
                                <?php foreach ($modulList as $index => $modul): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <?php if ($modul['urutan'] > 1): ?>
                                                    <a href="<?= BASE_URL ?>/admin/modul?kursus_id=<?= $kursus_id ?>&action=move_up&id=<?= $modul['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-arrow-up"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button disabled class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-arrow-up"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($modul['urutan'] < count($modulList)): ?>
                                                    <a href="<?= BASE_URL ?>/admin/modul?kursus_id=<?= $kursus_id ?>&action=move_down&id=<?= $modul['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-arrow-down"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button disabled class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-arrow-down"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($modul['judul']) ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            echo !empty($modul['deskripsi'])
                                                ? (strlen($modul['deskripsi']) > 100
                                                    ? htmlspecialchars(substr($modul['deskripsi'], 0, 100)) . '...'
                                                    : htmlspecialchars($modul['deskripsi']))
                                                : '<span class="text-muted">Tidak ada deskripsi</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info rounded-pill"><?= $modul['jumlah_materi'] ?> materi</span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?= BASE_URL ?>/admin/materi?modul_id=<?= $modul['id'] ?>" class="btn btn-sm btn-outline-primary" title="Kelola Materi">
                                                    <i class="bi bi-file-text"></i> Materi
                                                </a>
                                                <a href="<?= BASE_URL ?>/admin/modul/edit?id=<?= $modul['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit Modul">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $modul['id'] ?>" title="Hapus Modul">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>

                                            <!-- Modal Konfirmasi Hapus -->
                                            <div class="modal fade" id="deleteModal<?= $modul['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $modul['id'] ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?= $modul['id'] ?>">Konfirmasi Hapus</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Apakah Anda yakin ingin menghapus modul <strong><?= htmlspecialchars($modul['judul']) ?></strong>?</p>
                                                            <?php if ($modul['jumlah_materi'] > 0): ?>
                                                                <div class="alert alert-warning">
                                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                                    Modul ini memiliki <?= $modul['jumlah_materi'] ?> materi. Anda perlu menghapus semua materi terlebih dahulu sebelum dapat menghapus modul ini.
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <?php if ($modul['jumlah_materi'] == 0): ?>
                                                                <a href="<?= BASE_URL ?>/admin/modul?kursus_id=<?= $kursus_id ?>&action=delete&id=<?= $modul['id'] ?>" class="btn btn-danger">Hapus</a>
                                                            <?php else: ?>
                                                                <button type="button" class="btn btn-danger" disabled>Hapus</button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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
<?php
}

adminFooter();
?>