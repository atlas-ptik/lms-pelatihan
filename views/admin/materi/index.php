<?php
// Path: views/admin/materi/index.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

adminHeader("Manajemen Materi", "Kelola materi pembelajaran");

$db = dbConnect();
$pesan = '';
$tipe = '';

// Cek apakah ada parameter modul_id
if (!isset($_GET['modul_id'])) {
    // Tampilkan daftar semua modul
    $stmt = $db->prepare("
        SELECT m.id, m.judul as modul_judul, 
               k.id as kursus_id, k.judul as kursus_judul, k.status as kursus_status,
               (SELECT COUNT(*) FROM materi WHERE modul_id = m.id) as jumlah_materi
        FROM modul m
        JOIN kursus k ON m.kursus_id = k.id
        ORDER BY k.judul ASC, m.urutan ASC
    ");
    $stmt->execute();
    $modulList = $stmt->fetchAll();

    // Tampilkan daftar semua modul
?>
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col">
                <h1 class="page-title">Manajemen Materi</h1>
                <p class="text-muted">Pilih modul untuk mengelola materinya</p>
            </div>
        </div>

        <?php if (empty($modulList)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i> Belum ada modul yang tersedia.
                <a href="<?= BASE_URL ?>/admin/modul" class="alert-link">Tambahkan modul</a> terlebih dahulu.
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Kursus</th>
                                    <th>Modul</th>
                                    <th>Jumlah Materi</th>
                                    <th>Status Kursus</th>
                                    <th width="150">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modulList as $modul): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($modul['kursus_judul']) ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($modul['modul_judul']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info rounded-pill"><?= $modul['jumlah_materi'] ?> materi</span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $modul['kursus_status'] === 'publikasi' ? 'bg-success' : ($modul['kursus_status'] === 'draf' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                                <?= ucfirst($modul['kursus_status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?= BASE_URL ?>/admin/materi?modul_id=<?= $modul['id'] ?>" class="btn btn-sm btn-primary" title="Kelola Materi">
                                                    <i class="bi bi-file-text"></i> Kelola Materi
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php
} else {
    $modul_id = $_GET['modul_id'];

    // Ambil data modul
    $stmt = $db->prepare("
        SELECT m.*, k.judul as kursus_judul, k.id as kursus_id, k.status as kursus_status
        FROM modul m
        JOIN kursus k ON m.kursus_id = k.id
        WHERE m.id = ?
    ");
    $stmt->execute([$modul_id]);
    $modul = $stmt->fetch();

    if (!$modul) {
        // Modul tidak ditemukan, redirect ke daftar modul
        header("Location: " . BASE_URL . "/admin/materi");
        exit;
    }

    // Proses action jika ada
    if (isset($_GET['action'])) {
        $action = $_GET['action'];

        // Hapus materi
        if ($action === 'delete' && isset($_GET['id'])) {
            $materi_id = $_GET['id'];

            try {
                // Hapus materi
                $stmt = $db->prepare("DELETE FROM materi WHERE id = ?");
                $stmt->execute([$materi_id]);

                $pesan = "Materi berhasil dihapus";
                $tipe = "success";

                // Update urutan materi
                $stmt = $db->prepare("SELECT id FROM materi WHERE modul_id = ? ORDER BY urutan ASC");
                $stmt->execute([$modul_id]);
                $materis = $stmt->fetchAll();

                $urutan = 1;
                foreach ($materis as $m) {
                    $stmt = $db->prepare("UPDATE materi SET urutan = ? WHERE id = ?");
                    $stmt->execute([$urutan, $m['id']]);
                    $urutan++;
                }
            } catch (PDOException $e) {
                $pesan = "Gagal menghapus materi: " . $e->getMessage();
                $tipe = "danger";
            }
        }

        // Pindah urutan materi
        if (($action === 'move_up' || $action === 'move_down') && isset($_GET['id'])) {
            $materi_id = $_GET['id'];

            try {
                // Ambil data materi
                $stmt = $db->prepare("SELECT * FROM materi WHERE id = ?");
                $stmt->execute([$materi_id]);
                $materi = $stmt->fetch();

                if ($materi) {
                    if ($action === 'move_up' && $materi['urutan'] > 1) {
                        // Ambil materi sebelumnya
                        $stmt = $db->prepare("SELECT * FROM materi WHERE modul_id = ? AND urutan = ?");
                        $stmt->execute([$modul_id, ($materi['urutan'] - 1)]);
                        $materiSebelumnya = $stmt->fetch();

                        if ($materiSebelumnya) {
                            // Tukar urutan
                            $stmt = $db->prepare("UPDATE materi SET urutan = ? WHERE id = ?");
                            $stmt->execute([$materiSebelumnya['urutan'], $materi_id]);

                            $stmt = $db->prepare("UPDATE materi SET urutan = ? WHERE id = ?");
                            $stmt->execute([$materi['urutan'], $materiSebelumnya['id']]);

                            $pesan = "Urutan materi berhasil diubah";
                            $tipe = "success";
                        }
                    } elseif ($action === 'move_down') {
                        // Ambil materi setelahnya
                        $stmt = $db->prepare("SELECT * FROM materi WHERE modul_id = ? AND urutan = ?");
                        $stmt->execute([$modul_id, ($materi['urutan'] + 1)]);
                        $materiSetelahnya = $stmt->fetch();

                        if ($materiSetelahnya) {
                            // Tukar urutan
                            $stmt = $db->prepare("UPDATE materi SET urutan = ? WHERE id = ?");
                            $stmt->execute([$materiSetelahnya['urutan'], $materi_id]);

                            $stmt = $db->prepare("UPDATE materi SET urutan = ? WHERE id = ?");
                            $stmt->execute([$materi['urutan'], $materiSetelahnya['id']]);

                            $pesan = "Urutan materi berhasil diubah";
                            $tipe = "success";
                        }
                    }
                }
            } catch (PDOException $e) {
                $pesan = "Gagal mengubah urutan materi: " . $e->getMessage();
                $tipe = "danger";
            }
        }
    }

    // Ambil semua materi untuk modul ini
    $stmt = $db->prepare("SELECT * FROM materi WHERE modul_id = ? ORDER BY urutan ASC");
    $stmt->execute([$modul_id]);
    $materiList = $stmt->fetchAll();

    // Tampilkan daftar materi untuk modul tersebut
?>
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-6">
                <h1 class="page-title">Manajemen Materi</h1>
                <p class="text-muted">
                    Kursus: <strong><?= htmlspecialchars($modul['kursus_judul']) ?></strong> |
                    Modul: <strong><?= htmlspecialchars($modul['judul']) ?></strong>
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="<?= BASE_URL ?>/admin/modul?kursus_id=<?= $modul['kursus_id'] ?>" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left"></i> Kembali ke Modul
                </a>
                <a href="<?= BASE_URL ?>/admin/materi/tambah?modul_id=<?= $modul_id ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Tambah Materi
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
                    <h5 class="mb-0">Informasi Modul</h5>
                    <a href="<?= BASE_URL ?>/admin/modul/edit?id=<?= $modul_id ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil"></i> Edit Modul
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label text-muted">Deskripsi Modul</label>
                            <p><?= !empty($modul['deskripsi']) ? nl2br(htmlspecialchars($modul['deskripsi'])) : '<em class="text-muted">Tidak ada deskripsi</em>' ?></p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label text-muted">Informasi</label>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Urutan Modul
                                    <span class="badge bg-secondary rounded-pill"><?= $modul['urutan'] ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Jumlah Materi
                                    <span class="badge bg-primary rounded-pill"><?= count($materiList) ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Status Kursus
                                    <span class="badge <?= $modul['kursus_status'] === 'publikasi' ? 'bg-success' : ($modul['kursus_status'] === 'draf' ? 'bg-warning text-dark' : 'bg-secondary') ?>">
                                        <?= ucfirst($modul['kursus_status']) ?>
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
                <h5 class="mb-0">Daftar Materi</h5>
                <span class="badge bg-primary rounded-pill"><?= count($materiList) ?> materi</span>
            </div>
            <div class="card-body">
                <?php if (empty($materiList)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i> Belum ada materi untuk modul ini.
                        <a href="<?= BASE_URL ?>/admin/materi/tambah?modul_id=<?= $modul_id ?>" class="alert-link">Tambah materi baru</a> untuk memulai.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th width="70">Urutan</th>
                                    <th width="80">Tipe</th>
                                    <th>Judul Materi</th>
                                    <th>File/URL</th>
                                    <th>Durasi</th>
                                    <th width="180">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($materiList as $index => $materi): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <?php if ($materi['urutan'] > 1): ?>
                                                    <a href="<?= BASE_URL ?>/admin/materi?modul_id=<?= $modul_id ?>&action=move_up&id=<?= $materi['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Pindah ke atas">
                                                        <i class="bi bi-arrow-up"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button disabled class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-arrow-up"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($materi['urutan'] < count($materiList)): ?>
                                                    <a href="<?= BASE_URL ?>/admin/materi?modul_id=<?= $modul_id ?>&action=move_down&id=<?= $materi['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Pindah ke bawah">
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
                                            <?php
                                            $tipeBadge = '';
                                            $tipeIcon = '';

                                            switch ($materi['tipe']) {
                                                case 'video':
                                                    $tipeBadge = 'bg-danger';
                                                    $tipeIcon = 'bi-camera-video';
                                                    break;
                                                case 'artikel':
                                                    $tipeBadge = 'bg-primary';
                                                    $tipeIcon = 'bi-file-text';
                                                    break;
                                                case 'dokumen':
                                                    $tipeBadge = 'bg-secondary';
                                                    $tipeIcon = 'bi-file-earmark';
                                                    break;
                                                case 'quiz':
                                                    $tipeBadge = 'bg-success';
                                                    $tipeIcon = 'bi-question-circle';
                                                    break;
                                                case 'tugas':
                                                    $tipeBadge = 'bg-warning text-dark';
                                                    $tipeIcon = 'bi-clipboard-check';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?= $tipeBadge ?>">
                                                <i class="bi <?= $tipeIcon ?> me-1"></i>
                                                <?= ucfirst($materi['tipe']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($materi['judul']) ?></strong>
                                        </td>
                                        <td>
                                            <?php if ($materi['tipe'] === 'video' && !empty($materi['video_url'])): ?>
                                                <a href="<?= htmlspecialchars($materi['video_url']) ?>" target="_blank" class="text-truncate d-inline-block" style="max-width: 200px;">
                                                    <i class="bi bi-link-45deg"></i> <?= htmlspecialchars($materi['video_url']) ?>
                                                </a>
                                            <?php elseif ($materi['tipe'] === 'dokumen' && !empty($materi['file_path'])): ?>
                                                <a href="<?= BASE_URL ?>/uploads/materi/<?= $materi['file_path'] ?>" target="_blank" class="text-truncate d-inline-block" style="max-width: 200px;">
                                                    <i class="bi bi-file-earmark"></i> <?= htmlspecialchars($materi['file_path']) ?>
                                                </a>
                                            <?php elseif (in_array($materi['tipe'], ['quiz', 'tugas'])): ?>
                                                <span class="text-muted">-</span>
                                            <?php elseif ($materi['tipe'] === 'artikel' && !empty($materi['konten'])): ?>
                                                <span class="text-muted"><i class="bi bi-file-text"></i> Artikel teks</span>
                                            <?php else: ?>
                                                <span class="text-muted">Tidak ada file/URL</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= !empty($materi['durasi_menit']) ? $materi['durasi_menit'] . ' menit' : '-' ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?= BASE_URL ?>/admin/materi/edit?id=<?= $materi['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Materi">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if ($materi['tipe'] === 'quiz'): ?>
                                                    <a href="<?= BASE_URL ?>/admin/quiz?materi_id=<?= $materi['id'] ?>" class="btn btn-sm btn-outline-success" title="Kelola Quiz">
                                                        <i class="bi bi-question-circle"></i>
                                                    </a>
                                                <?php elseif ($materi['tipe'] === 'tugas'): ?>
                                                    <a href="<?= BASE_URL ?>/admin/tugas?materi_id=<?= $materi['id'] ?>" class="btn btn-sm btn-outline-success" title="Kelola Tugas">
                                                        <i class="bi bi-clipboard-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $materi['id'] ?>" title="Hapus Materi">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>

                                            <!-- Modal Konfirmasi Hapus -->
                                            <div class="modal fade" id="deleteModal<?= $materi['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $materi['id'] ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?= $materi['id'] ?>">Konfirmasi Hapus</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Apakah Anda yakin ingin menghapus materi <strong><?= htmlspecialchars($materi['judul']) ?></strong>?</p>
                                                            <?php if ($materi['tipe'] === 'quiz' || $materi['tipe'] === 'tugas'): ?>
                                                                <div class="alert alert-warning">
                                                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                                    Menghapus <?= $materi['tipe'] === 'quiz' ? 'quiz' : 'tugas' ?> akan menghapus semua data terkait seperti pertanyaan, jawaban, dan nilai.
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <a href="<?= BASE_URL ?>/admin/materi?modul_id=<?= $modul_id ?>&action=delete&id=<?= $materi['id'] ?>" class="btn btn-danger">Hapus</a>
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