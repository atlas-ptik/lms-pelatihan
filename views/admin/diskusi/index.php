<?php
// Path: views/admin/diskusi/index.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Ambil data diskusi
$db = dbConnect();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT d.id, d.judul, d.status, d.waktu_dibuat, p.nama_lengkap as pengguna, k.judul as kursus 
          FROM diskusi d
          JOIN pengguna p ON d.pengguna_id = p.id
          JOIN kursus k ON d.kursus_id = k.id";

$count_query = "SELECT COUNT(*) as total FROM diskusi d";

$params = [];
$where_conditions = [];

if (!empty($search)) {
    $where_conditions[] = "(d.judul LIKE ? OR d.isi LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "d.status = ?";
    $params[] = $status_filter;
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(' AND ', $where_conditions);
    $count_query .= " WHERE " . implode(' AND ', $where_conditions);
}

$query .= " ORDER BY d.waktu_dibuat DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Execute query
$stmt = $db->prepare($query);
$stmt->execute($params);
$diskusi_list = $stmt->fetchAll();

// Get total count
$count_params = array_slice($params, 0, -2);
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($count_params);
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

adminHeader("Manajemen Diskusi", "Kelola diskusi pada sistem Atlas LMS");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manajemen Diskusi</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Diskusi</li>
        </ol>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <form action="" method="get" class="d-flex">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Cari diskusi..." name="search" value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-md-end">
                        <div class="me-2">
                            <select class="form-select" id="status-filter" onchange="this.form.submit()">
                                <option value="">Semua Status</option>
                                <option value="aktif" <?= $status_filter === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="ditutup" <?= $status_filter === 'ditutup' ? 'selected' : '' ?>>Ditutup</option>
                                <option value="dihapus" <?= $status_filter === 'dihapus' ? 'selected' : '' ?>>Dihapus</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Judul</th>
                            <th>Pengguna</th>
                            <th>Kursus</th>
                            <th>Status</th>
                            <th>Tanggal Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($diskusi_list)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-3">Tidak ada data diskusi</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($diskusi_list as $diskusi): ?>
                                <tr>
                                    <td><?= htmlspecialchars($diskusi['judul']) ?></td>
                                    <td><?= htmlspecialchars($diskusi['pengguna']) ?></td>
                                    <td><?= htmlspecialchars($diskusi['kursus']) ?></td>
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
                                    <td><?= date('d M Y H:i', strtotime($diskusi['waktu_dibuat'])) ?></td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="<?= BASE_URL ?>/admin/diskusi/detail?id=<?= $diskusi['id'] ?>" class="btn btn-sm btn-info me-1">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>/admin/diskusi/hapus?id=<?= $diskusi['id'] ?>" class="btn btn-sm btn-danger"
                                                onclick="return confirm('Apakah Anda yakin ingin menghapus diskusi ini?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    document.getElementById('status-filter').addEventListener('change', function() {
        let currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('status', this.value);
        currentUrl.searchParams.set('page', 1);
        window.location.href = currentUrl.toString();
    });
</script>

<?php adminFooter(); ?>