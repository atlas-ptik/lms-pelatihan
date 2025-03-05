<?php
// Path: views/admin/pengaturan/index.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

$db = dbConnect();

// Ambil semua data pengaturan
$stmt = $db->query("SELECT * FROM pengaturan ORDER BY kunci ASC");
$pengaturan = $stmt->fetchAll();

// Jika ada form submit untuk menambah pengaturan baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_pengaturan'])) {
    $kunci = trim($_POST['kunci']);
    $nilai = trim($_POST['nilai']);
    $deskripsi = trim($_POST['deskripsi']);
    $tipe = $_POST['tipe'];

    // Validasi data
    $errors = [];

    if (empty($kunci)) {
        $errors[] = "Kunci pengaturan harus diisi";
    } else {
        // Cek apakah kunci sudah ada
        $stmt = $db->prepare("SELECT id FROM pengaturan WHERE kunci = ?");
        $stmt->execute([$kunci]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Kunci pengaturan sudah digunakan";
        }
    }

    if (empty($nilai)) {
        $errors[] = "Nilai pengaturan harus diisi";
    }

    // Jika tidak ada error, simpan data
    if (empty($errors)) {
        try {
            $id = generate_uuid();
            $stmt = $db->prepare("INSERT INTO pengaturan (id, kunci, nilai, deskripsi, tipe) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $kunci, $nilai, $deskripsi, $tipe]);

            // Redirect untuk refresh halaman
            header('Location: ' . BASE_URL . '/admin/pengaturan?sukses=Pengaturan berhasil ditambahkan');
            exit;
        } catch (PDOException $e) {
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Jika ada form submit untuk menghapus pengaturan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_pengaturan'])) {
    $id = $_POST['id'];

    try {
        $stmt = $db->prepare("DELETE FROM pengaturan WHERE id = ?");
        $stmt->execute([$id]);

        // Redirect untuk refresh halaman
        header('Location: ' . BASE_URL . '/admin/pengaturan?sukses=Pengaturan berhasil dihapus');
        exit;
    } catch (PDOException $e) {
        $errors[] = "Terjadi kesalahan: " . $e->getMessage();
    }
}

adminHeader("Pengaturan", "Kelola pengaturan sistem Atlas LMS");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Pengaturan</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Pengaturan</li>
        </ol>
    </div>

    <?php if (isset($_GET['sukses'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['sukses']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($errors) && !empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="m-0 font-weight-bold">Menu Pengaturan</h5>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                                <i class="bi bi-gear text-primary fs-3"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h5 class="card-title">Pengaturan Sistem</h5>
                                            <p class="card-text">Kelola pengaturan sistem seperti nama aplikasi, email, dan konfigurasi dasar lainnya.</p>
                                            <a href="<?= BASE_URL ?>/admin/pengaturan/sistem" class="btn btn-primary">Kelola Pengaturan Sistem</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                                <i class="bi bi-palette text-success fs-3"></i>
                                            </div>
                                        </div>
                                        <div>
                                            <h5 class="card-title">Pengaturan Tampilan</h5>
                                            <p class="card-text">Sesuaikan tampilan aplikasi seperti logo, warna tema, dan elemen visual lainnya.</p>
                                            <a href="<?= BASE_URL ?>/admin/pengaturan/tampilan" class="btn btn-success">Kelola Pengaturan Tampilan</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white py-3">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="m-0 font-weight-bold">Daftar Pengaturan</h5>
                        </div>
                        <div class="col text-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahPengaturanModal">
                                <i class="bi bi-plus-circle"></i> Tambah Pengaturan
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Kunci</th>
                                    <th>Nilai</th>
                                    <th>Deskripsi</th>
                                    <th>Tipe</th>
                                    <th>Terakhir Diperbarui</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pengaturan)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Belum ada data pengaturan</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pengaturan as $p): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($p['kunci']) ?></td>
                                            <td>
                                                <?php if ($p['tipe'] === 'boolean'): ?>
                                                    <?= $p['nilai'] == '1' ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Nonaktif</span>' ?>
                                                <?php elseif (strlen($p['nilai']) > 100): ?>
                                                    <?= htmlspecialchars(substr($p['nilai'], 0, 100)) ?>...
                                                <?php else: ?>
                                                    <?= htmlspecialchars($p['nilai']) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($p['deskripsi'] ?? '-') ?></td>
                                            <td><span class="badge bg-info"><?= htmlspecialchars($p['tipe']) ?></span></td>
                                            <td><?= date('d/m/Y H:i', strtotime($p['waktu_diperbarui'])) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info edit-btn"
                                                    data-id="<?= $p['id'] ?>"
                                                    data-kunci="<?= htmlspecialchars($p['kunci']) ?>"
                                                    data-nilai="<?= htmlspecialchars($p['nilai']) ?>"
                                                    data-deskripsi="<?= htmlspecialchars($p['deskripsi'] ?? '') ?>"
                                                    data-tipe="<?= $p['tipe'] ?>"
                                                    data-bs-toggle="modal" data-bs-target="#editPengaturanModal">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#hapusPengaturanModal<?= $p['id'] ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>

                                                <!-- Modal Hapus -->
                                                <div class="modal fade" id="hapusPengaturanModal<?= $p['id'] ?>" tabindex="-1" aria-labelledby="hapusPengaturanModalLabel<?= $p['id'] ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="hapusPengaturanModalLabel<?= $p['id'] ?>">Konfirmasi Hapus</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Apakah Anda yakin ingin menghapus pengaturan <strong><?= htmlspecialchars($p['kunci']) ?></strong>?</p>
                                                                <p class="text-danger"><small>Perhatian: Tindakan ini tidak dapat dibatalkan dan mungkin memengaruhi fungsionalitas sistem.</small></p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                <form method="POST">
                                                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                                    <button type="submit" name="hapus_pengaturan" class="btn btn-danger">Hapus</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah Pengaturan -->
<div class="modal fade" id="tambahPengaturanModal" tabindex="-1" aria-labelledby="tambahPengaturanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="tambahPengaturanModalLabel">Tambah Pengaturan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="kunci" class="form-label">Kunci</label>
                        <input type="text" class="form-control" id="kunci" name="kunci" placeholder="Contoh: nama_aplikasi" required>
                        <small class="text-muted">Kunci harus unik dan tidak mengandung spasi, gunakan underscore.</small>
                    </div>
                    <div class="mb-3">
                        <label for="nilai" class="form-label">Nilai</label>
                        <textarea class="form-control" id="nilai" name="nilai" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="tipe" class="form-label">Tipe Data</label>
                        <select class="form-select" id="tipe" name="tipe" required>
                            <option value="teks">Teks</option>
                            <option value="angka">Angka</option>
                            <option value="boolean">Boolean</option>
                            <option value="json">JSON</option>
                            <option value="html">HTML</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_pengaturan" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Pengaturan -->
<div class="modal fade" id="editPengaturanModal" tabindex="-1" aria-labelledby="editPengaturanModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPengaturanModalLabel">Edit Pengaturan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= BASE_URL ?>/admin/pengaturan/sistem">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label for="edit_kunci" class="form-label">Kunci</label>
                        <input type="text" class="form-control" id="edit_kunci" name="kunci" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="edit_nilai" class="form-label">Nilai</label>
                        <textarea class="form-control" id="edit_nilai" name="nilai" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control" id="edit_deskripsi" name="deskripsi" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_tipe" class="form-label">Tipe Data</label>
                        <select class="form-select" id="edit_tipe" name="tipe" required>
                            <option value="teks">Teks</option>
                            <option value="angka">Angka</option>
                            <option value="boolean">Boolean</option>
                            <option value="json">JSON</option>
                            <option value="html">HTML</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="update_pengaturan" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mengatur nilai pada form edit saat modal terbuka
        const editButtons = document.querySelectorAll('.edit-btn');
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_kunci').value = this.dataset.kunci;
                document.getElementById('edit_nilai').value = this.dataset.nilai;
                document.getElementById('edit_deskripsi').value = this.dataset.deskripsi;
                document.getElementById('edit_tipe').value = this.dataset.tipe;
            });
        });
    });
</script>

<?php adminFooter(); ?>