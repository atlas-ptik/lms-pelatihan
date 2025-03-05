<?php
// Path: views/admin/kursus/index.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Manajemen Kursus", "Kelola kursus di Atlas LMS");

$db = dbConnect();

// Filter
$status = isset($_GET['status']) ? $_GET['status'] : '';
$level = isset($_GET['level']) ? $_GET['level'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';

// Query dasar
$query = "SELECT k.*, p.nama_lengkap as pembuat_nama FROM kursus k 
          JOIN pengguna p ON k.pembuat_id = p.id
          WHERE 1=1";
$params = [];

// Tambahkan filter
if (!empty($status)) {
    $query .= " AND k.status = ?";
    $params[] = $status;
}

if (!empty($level)) {
    $query .= " AND k.level = ?";
    $params[] = $level;
}

if (!empty($search)) {
    $query .= " AND (k.judul LIKE ? OR k.deskripsi LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($kategori)) {
    $query .= " AND k.id IN (SELECT kursus_id FROM kursus_kategori WHERE kategori_id = ?)";
    $params[] = $kategori;
}

// Tambahkan pengurutan
$query .= " ORDER BY k.waktu_dibuat DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$kursus = $stmt->fetchAll();

// Ambil semua kategori untuk filter
$stmt = $db->prepare("SELECT id, nama FROM kategori ORDER BY nama ASC");
$stmt->execute();
$kategoriList = $stmt->fetchAll();

// Proses hapus kursus jika ada
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        // Cek apakah ada pendaftaran di kursus ini
        $stmt = $db->prepare("SELECT COUNT(*) as jumlah FROM pendaftaran WHERE kursus_id = ?");
        $stmt->execute([$id]);
        $jumlahPendaftaran = $stmt->fetch()['jumlah'];

        if ($jumlahPendaftaran > 0) {
            $pesan = "Tidak dapat menghapus kursus karena terdapat $jumlahPendaftaran pendaftaran terkait kursus ini";
            $tipe = "danger";
        } else {
            // Hapus relasi kursus_kategori
            $stmt = $db->prepare("DELETE FROM kursus_kategori WHERE kursus_id = ?");
            $stmt->execute([$id]);

            // Hapus kursus
            $stmt = $db->prepare("DELETE FROM kursus WHERE id = ?");
            $stmt->execute([$id]);

            $pesan = "Kursus berhasil dihapus";
            $tipe = "success";

            // Refresh data
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $kursus = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $pesan = "Gagal menghapus kursus. Error: " . $e->getMessage();
        $tipe = "danger";
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="page-title">Manajemen Kursus</h1>
            <p class="text-muted">Kelola semua kursus di sistem</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="<?= BASE_URL ?>/admin/kursus/tambah" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tambah Kursus
            </a>
        </div>
    </div>

    <?php if (isset($pesan)): ?>
        <div class="alert alert-<?= $tipe ?> alert-dismissible fade show" role="alert">
            <?= $pesan ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filter Kursus</h5>
        </div>
        <div class="card-body">
            <form action="" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Pencarian</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Judul atau deskripsi...">
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">Semua Status</option>
                        <option value="draf" <?= $status === 'draf' ? 'selected' : '' ?>>Draf</option>
                        <option value="publikasi" <?= $status === 'publikasi' ? 'selected' : '' ?>>Publikasi</option>
                        <option value="arsip" <?= $status === 'arsip' ? 'selected' : '' ?>>Arsip</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="level" class="form-label">Level</label>
                    <select class="form-select" id="level" name="level">
                        <option value="">Semua Level</option>
                        <option value="pemula" <?= $level === 'pemula' ? 'selected' : '' ?>>Pemula</option>
                        <option value="menengah" <?= $level === 'menengah' ? 'selected' : '' ?>>Menengah</option>
                        <option value="mahir" <?= $level === 'mahir' ? 'selected' : '' ?>>Mahir</option>
                        <option value="semua level" <?= $level === 'semua level' ? 'selected' : '' ?>>Semua Level</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="kategori" class="form-label">Kategori</label>
                    <select class="form-select" id="kategori" name="kategori">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategoriList as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= $kategori === $k['id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-filter"></i> Filter
                    </button>
                    <a href="<?= BASE_URL ?>/admin/kursus" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (empty($kursus)): ?>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i> Belum ada kursus yang ditemukan. Silakan tambah kursus baru.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th width="60">Cover</th>
                                <th>Judul</th>
                                <th>Pembuat</th>
                                <th>Level</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th>Tanggal Dibuat</th>
                                <th width="180">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kursus as $k): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($k['gambar_sampul'])): ?>
                                            <img src="<?= BASE_URL ?>/uploads/kursus/<?= $k['gambar_sampul'] ?>" alt="<?= htmlspecialchars($k['judul']) ?>" class="img-thumbnail" width="50">
                                        <?php else: ?>
                                            <div class="img-placeholder bg-light text-center rounded" style="width: 50px; height: 40px;">
                                                <i class="bi bi-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($k['judul']) ?></strong>
                                        <div class="text-muted small">
                                            <?= !empty($k['durasi_menit']) ? $k['durasi_menit'] . ' menit' : 'Durasi belum diatur' ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($k['pembuat_nama']) ?></td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?= ucfirst(htmlspecialchars($k['level'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($k['harga'] > 0): ?>
                                            <span class="text-success">Rp <?= number_format($k['harga'], 0, ',', '.') ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Gratis</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        switch ($k['status']) {
                                            case 'publikasi':
                                                $statusClass = 'bg-success';
                                                break;
                                            case 'draf':
                                                $statusClass = 'bg-warning text-dark';
                                                break;
                                            case 'arsip':
                                                $statusClass = 'bg-secondary';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?= $statusClass ?>">
                                            <?= ucfirst(htmlspecialchars($k['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($k['waktu_dibuat'])) ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?= BASE_URL ?>/admin/kursus/edit?id=<?= $k['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>/admin/modul?kursus_id=<?= $k['id'] ?>" class="btn btn-sm btn-outline-success" title="Kelola Modul">
                                                <i class="bi bi-folder"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $k['id'] ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>

                                        <!-- Modal Konfirmasi Hapus -->
                                        <div class="modal fade" id="deleteModal<?= $k['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $k['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel<?= $k['id'] ?>">Konfirmasi Hapus</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Apakah Anda yakin ingin menghapus kursus <strong><?= htmlspecialchars($k['judul']) ?></strong>?</p>
                                                        <div class="alert alert-warning">
                                                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                            Menghapus kursus akan menghapus semua modul, materi, quiz, dan data terkait lainnya.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <a href="<?= BASE_URL ?>/admin/kursus?action=delete&id=<?= $k['id'] ?>" class="btn btn-danger">Hapus</a>
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

<style>
    .img-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<?php adminFooter(); ?>