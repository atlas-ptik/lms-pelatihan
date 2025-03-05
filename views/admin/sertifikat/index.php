<?php
// Path: views/admin/sertifikat/index.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Ambil data sertifikat
$db = dbConnect();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "SELECT s.id, s.nomor_sertifikat, s.tanggal_terbit, s.tanggal_kedaluwarsa, s.status, 
                 p.nama_lengkap as nama_pengguna, k.judul as judul_kursus 
          FROM sertifikat s
          JOIN pendaftaran pd ON s.pendaftaran_id = pd.id
          JOIN pengguna p ON pd.pengguna_id = p.id
          JOIN kursus k ON pd.kursus_id = k.id";

$count_query = "SELECT COUNT(*) as total FROM sertifikat s";

$params = [];
$where_conditions = [];

if (!empty($search)) {
    $where_conditions[] = "(s.nomor_sertifikat LIKE ? OR p.nama_lengkap LIKE ? OR k.judul LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "s.status = ?";
    $params[] = $status_filter;
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(' AND ', $where_conditions);
    $count_query .= " JOIN pendaftaran pd ON s.pendaftaran_id = pd.id
                       JOIN pengguna p ON pd.pengguna_id = p.id
                       JOIN kursus k ON pd.kursus_id = k.id
                       WHERE " . implode(' AND ', $where_conditions);
}

$query .= " ORDER BY s.tanggal_terbit DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Execute query
$stmt = $db->prepare($query);
$stmt->execute($params);
$sertifikat_list = $stmt->fetchAll();

// Get total count
$count_params = array_slice($params, 0, -2);
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($count_params);
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $limit);

adminHeader("Manajemen Sertifikat", "Kelola sertifikat pada sistem Atlas LMS");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manajemen Sertifikat</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Sertifikat</li>
        </ol>
    </div>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Sertifikat berhasil dicabut
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Sertifikat berhasil ditambahkan
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <form action="" method="get" class="d-flex">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Cari sertifikat..." name="search" value="<?= htmlspecialchars($search) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 d-flex justify-content-md-end">
                    <div class="me-2">
                        <select class="form-select" id="status-filter" onchange="document.getElementById('filter-form').submit()">
                            <option value="">Semua Status</option>
                            <option value="aktif" <?= $status_filter === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="dicabut" <?= $status_filter === 'dicabut' ? 'selected' : '' ?>>Dicabut</option>
                        </select>
                        <form id="filter-form" action="" method="get" class="d-none">
                            <input type="hidden" name="status" id="status-input" value="<?= $status_filter ?>">
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        </form>
                    </div>
                    <a href="<?= BASE_URL ?>/admin/sertifikat/tambah" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Tambah Sertifikat
                    </a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nomor Sertifikat</th>
                            <th>Nama Peserta</th>
                            <th>Kursus</th>
                            <th>Tanggal Terbit</th>
                            <th>Tanggal Kedaluwarsa</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sertifikat_list)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-3">Tidak ada data sertifikat</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sertifikat_list as $sertifikat): ?>
                                <tr>
                                    <td><?= htmlspecialchars($sertifikat['nomor_sertifikat']) ?></td>
                                    <td><?= htmlspecialchars($sertifikat['nama_pengguna']) ?></td>
                                    <td><?= htmlspecialchars($sertifikat['judul_kursus']) ?></td>
                                    <td><?= date('d M Y', strtotime($sertifikat['tanggal_terbit'])) ?></td>
                                    <td>
                                        <?= $sertifikat['tanggal_kedaluwarsa']
                                            ? date('d M Y', strtotime($sertifikat['tanggal_kedaluwarsa']))
                                            : '<span class="text-muted">Tidak ada</span>' ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $sertifikat['status'] === 'aktif' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= ucfirst($sertifikat['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex">
                                            <a href="<?= BASE_URL ?>/admin/sertifikat/detail?id=<?= $sertifikat['id'] ?>" class="btn btn-sm btn-info me-1">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if ($sertifikat['status'] === 'aktif'): ?>
                                                <a href="<?= BASE_URL ?>/admin/sertifikat/detail?id=<?= $sertifikat['id'] ?>&revoke=1"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Apakah Anda yakin ingin mencabut sertifikat ini?')">
                                                    <i class="bi bi-x-circle"></i>
                                                </a>
                                            <?php endif; ?>
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
        document.getElementById('status-input').value = this.value;
        document.getElementById('filter-form').submit();
    });
</script>

<?php adminFooter(); ?>