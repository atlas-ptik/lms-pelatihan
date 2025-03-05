<?php
// Path: views/admin/pengguna/index.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Pagination
$halaman = isset($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$per_halaman = 10;
$offset = ($halaman - 1) * $per_halaman;

// Filter & Search
$role = isset($_GET['role']) ? $_GET['role'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$cari = isset($_GET['cari']) ? $_GET['cari'] : '';

// Build query
$db = dbConnect();
$params = [];
$where_clauses = ["1=1"];

if (!empty($role)) {
    $where_clauses[] = "r.nama = :role";
    $params[':role'] = $role;
}

if (!empty($status)) {
    $where_clauses[] = "p.status = :status";
    $params[':status'] = $status;
}

if (!empty($cari)) {
    $where_clauses[] = "(p.nama_lengkap LIKE :cari OR p.username LIKE :cari OR p.email LIKE :cari)";
    $params[':cari'] = "%$cari%";
}

$where_sql = implode(' AND ', $where_clauses);

// Count total users
$count_sql = "
    SELECT COUNT(*) as total
    FROM pengguna p
    JOIN role r ON p.role_id = r.id
    WHERE $where_sql
";
$stmt = $db->prepare($count_sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$total_pengguna = $stmt->fetch()['total'];
$total_halaman = ceil($total_pengguna / $per_halaman);

// Get users
$sql = "
    SELECT p.id, p.username, p.nama_lengkap, p.email, p.status, p.foto_profil, 
           r.nama as role, p.waktu_dibuat, p.terakhir_login, p.nomor_telepon, p.bio
    FROM pengguna p
    JOIN role r ON p.role_id = r.id
    WHERE $where_sql
    ORDER BY p.waktu_dibuat DESC
    LIMIT :offset, :limit
";
$stmt = $db->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $per_halaman, PDO::PARAM_INT);
$stmt->execute();
$pengguna_list = $stmt->fetchAll();

// Get all roles
$stmt = $db->query("SELECT nama FROM role ORDER BY nama");
$role_list = $stmt->fetchAll(PDO::FETCH_COLUMN);

adminHeader("Manajemen Pengguna", "Kelola pengguna Atlas LMS");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Manajemen Pengguna</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
                <li class="breadcrumb-item active">Pengguna</li>
            </ol>
        </nav>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
                <h5 class="mb-3 mb-md-0">Daftar Pengguna</h5>
                <a href="<?= BASE_URL ?>/admin/pengguna/tambah" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Pengguna
                </a>
            </div>

            <!-- Filter dan Pencarian -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <form action="" method="GET" id="formRole">
                        <?php if (!empty($cari)): ?>
                            <input type="hidden" name="cari" value="<?= htmlspecialchars($cari) ?>">
                        <?php endif; ?>
                        <?php if (!empty($status)): ?>
                            <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                        <?php endif; ?>
                        <select class="form-select" name="role" onchange="document.getElementById('formRole').submit()">
                            <option value="">Semua Role</option>
                            <?php foreach ($role_list as $r): ?>
                                <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <div class="col-md-3">
                    <form action="" method="GET" id="formStatus">
                        <?php if (!empty($cari)): ?>
                            <input type="hidden" name="cari" value="<?= htmlspecialchars($cari) ?>">
                        <?php endif; ?>
                        <?php if (!empty($role)): ?>
                            <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
                        <?php endif; ?>
                        <select class="form-select" name="status" onchange="document.getElementById('formStatus').submit()">
                            <option value="">Semua Status</option>
                            <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                            <option value="diblokir" <?= $status === 'diblokir' ? 'selected' : '' ?>>Diblokir</option>
                        </select>
                    </form>
                </div>
                <div class="col-md-6">
                    <form action="" method="GET">
                        <?php if (!empty($role)): ?>
                            <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
                        <?php endif; ?>
                        <?php if (!empty($status)): ?>
                            <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                        <?php endif; ?>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Cari nama, username, atau email" name="cari" value="<?= htmlspecialchars($cari) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                            <?php if (!empty($cari) || !empty($role) || !empty($status)): ?>
                                <a href="<?= BASE_URL ?>/admin/pengguna" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabel Pengguna -->
            <?php if (empty($pengguna_list)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i> Tidak ada pengguna yang ditemukan.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-4">
                        <thead class="table-light">
                            <tr>
                                <th width="50">#</th>
                                <th>Nama</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = $offset + 1;
                            foreach ($pengguna_list as $pengguna):
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($pengguna['foto_profil'])): ?>
                                                <img src="<?= BASE_URL ?>/uploads/profil/<?= $pengguna['foto_profil'] ?>" alt="<?= htmlspecialchars($pengguna['nama_lengkap']) ?>" class="rounded-circle me-2" width="40" height="40">
                                            <?php else: ?>
                                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                                    <span class="text-white fw-bold"><?= strtoupper(substr($pengguna['nama_lengkap'], 0, 1)) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($pengguna['nama_lengkap']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($pengguna['username']) ?></td>
                                    <td>
                                        <?php
                                        $badge_class = '';
                                        switch ($pengguna['role']) {
                                            case 'admin':
                                                $badge_class = 'bg-danger';
                                                break;
                                            case 'instruktur':
                                                $badge_class = 'bg-success';
                                                break;
                                            case 'siswa':
                                                $badge_class = 'bg-primary';
                                                break;
                                            default:
                                                $badge_class = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= ucfirst($pengguna['role']) ?></span>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch ($pengguna['status']) {
                                            case 'aktif':
                                                $status_class = 'bg-success';
                                                break;
                                            case 'nonaktif':
                                                $status_class = 'bg-warning';
                                                break;
                                            case 'diblokir':
                                                $status_class = 'bg-danger';
                                                break;
                                            default:
                                                $status_class = 'bg-secondary';
                                        }
                                        ?>
                                        <span class="badge <?= $status_class ?>"><?= ucfirst($pengguna['status']) ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailModal<?= $pengguna['id'] ?>" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <a href="<?= BASE_URL ?>/admin/pengguna/edit?id=<?= $pengguna['id'] ?>" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $pengguna['id'] ?>" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <p class="mb-0">Menampilkan <?= count($pengguna_list) ?> dari <?= $total_pengguna ?> pengguna</p>

                    <!-- Pagination -->
                    <?php if ($total_halaman > 1): ?>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($halaman > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= BASE_URL ?>/admin/pengguna?halaman=<?= $halaman - 1 ?><?= !empty($role) ? '&role=' . urlencode($role) : '' ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?><?= !empty($cari) ? '&cari=' . urlencode($cari) : '' ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $halaman - 2);
                                $end_page = min($total_halaman, $halaman + 2);

                                for ($i = $start_page; $i <= $end_page; $i++) {
                                    echo '<li class="page-item ' . ($i == $halaman ? 'active' : '') . '">
                                    <a class="page-link" href="' . BASE_URL . '/admin/pengguna?halaman=' . $i .
                                        (!empty($role) ? '&role=' . urlencode($role) : '') .
                                        (!empty($status) ? '&status=' . urlencode($status) : '') .
                                        (!empty($cari) ? '&cari=' . urlencode($cari) : '') . '">' . $i . '</a>
                                </li>';
                                }
                                ?>

                                <?php if ($halaman < $total_halaman): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= BASE_URL ?>/admin/pengguna?halaman=<?= $halaman + 1 ?><?= !empty($role) ? '&role=' . urlencode($role) : '' ?><?= !empty($status) ? '&status=' . urlencode($status) : '' ?><?= !empty($cari) ? '&cari=' . urlencode($cari) : '' ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Semua Modal Diletakkan di Sini -->
    <?php foreach ($pengguna_list as $pengguna): ?>
        <!-- Modal Detail -->
        <div class="modal fade" id="detailModal<?= $pengguna['id'] ?>" tabindex="-1" aria-labelledby="detailModalLabel<?= $pengguna['id'] ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="detailModalLabel<?= $pengguna['id'] ?>">Detail Pengguna</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <?php if (!empty($pengguna['foto_profil'])): ?>
                                <img src="<?= BASE_URL ?>/uploads/profil/<?= $pengguna['foto_profil'] ?>" alt="<?= htmlspecialchars($pengguna['nama_lengkap']) ?>" class="rounded-circle mb-3" width="100" height="100">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mb-3 mx-auto" style="width: 100px; height: 100px;">
                                    <span class="text-white fw-bold fs-1"><?= strtoupper(substr($pengguna['nama_lengkap'], 0, 1)) ?></span>
                                </div>
                            <?php endif; ?>
                            <h4><?= htmlspecialchars($pengguna['nama_lengkap']) ?></h4>
                            <span class="badge <?= $badge_class ?> mb-2"><?= ucfirst($pengguna['role']) ?></span>
                            <span class="badge <?= $status_class ?>"><?= ucfirst($pengguna['status']) ?></span>
                        </div>

                        <div class="user-details">
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">Username</div>
                                <div class="col-8"><?= htmlspecialchars($pengguna['username']) ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">Email</div>
                                <div class="col-8"><?= htmlspecialchars($pengguna['email']) ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">No. Telepon</div>
                                <div class="col-8"><?= !empty($pengguna['nomor_telepon']) ? htmlspecialchars($pengguna['nomor_telepon']) : '<em>Tidak ada</em>' ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">Tanggal Daftar</div>
                                <div class="col-8"><?= date('d M Y', strtotime($pengguna['waktu_dibuat'])) ?></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-4 fw-bold">Terakhir Login</div>
                                <div class="col-8"><?= $pengguna['terakhir_login'] ? date('d M Y H:i', strtotime($pengguna['terakhir_login'])) : 'Belum pernah' ?></div>
                            </div>
                            <?php if (!empty($pengguna['bio'])): ?>
                                <div class="row mb-2">
                                    <div class="col-4 fw-bold">Bio</div>
                                    <div class="col-8"><?= nl2br(htmlspecialchars($pengguna['bio'])) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                        <a href="<?= BASE_URL ?>/admin/pengguna/edit?id=<?= $pengguna['id'] ?>" class="btn btn-primary">Edit Pengguna</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Hapus -->
        <div class="modal fade" id="deleteModal<?= $pengguna['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $pengguna['id'] ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel<?= $pengguna['id'] ?>">Konfirmasi Hapus</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Apakah Anda yakin ingin menghapus pengguna <strong><?= htmlspecialchars($pengguna['nama_lengkap']) ?></strong>?</p>
                        <p class="text-danger">Tindakan ini tidak dapat dibatalkan.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <a href="<?= BASE_URL ?>/admin/pengguna/hapus?id=<?= $pengguna['id'] ?>" class="btn btn-danger">Hapus</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php adminFooter(); ?>