<?php
// Path: views/admin/pengaturan/sistem.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

$db = dbConnect();

// Definisikan pengaturan sistem yang diperlukan
$sistem_keys = [
    'nama_aplikasi' => [
        'default' => 'Atlas LMS',
        'tipe' => 'teks',
        'deskripsi' => 'Nama sistem aplikasi yang ditampilkan'
    ],
    'deskripsi_aplikasi' => [
        'default' => 'Sistem Manajemen Pembelajaran Atlas',
        'tipe' => 'teks',
        'deskripsi' => 'Deskripsi singkat aplikasi'
    ],
    'email_admin' => [
        'default' => 'admin@atlas-lms.com',
        'tipe' => 'teks',
        'deskripsi' => 'Email administrator sistem'
    ],
    'kontak_telepon' => [
        'default' => '+62 8123456789',
        'tipe' => 'teks',
        'deskripsi' => 'Nomor telepon kontak'
    ],
    'alamat' => [
        'default' => 'Jl. Pendidikan No. 123, Jakarta',
        'tipe' => 'teks',
        'deskripsi' => 'Alamat fisik lembaga'
    ],
    'aktivasi_pendaftaran' => [
        'default' => '1',
        'tipe' => 'boolean',
        'deskripsi' => 'Mengaktifkan fitur pendaftaran pengguna baru'
    ],
    'verifikasi_email' => [
        'default' => '1',
        'tipe' => 'boolean',
        'deskripsi' => 'Mewajibkan verifikasi email setelah pendaftaran'
    ],
    'notifikasi_email' => [
        'default' => '1',
        'tipe' => 'boolean',
        'deskripsi' => 'Mengirim notifikasi melalui email'
    ],
    'batas_percobaan_login' => [
        'default' => '5',
        'tipe' => 'angka',
        'deskripsi' => 'Jumlah maksimal percobaan login sebelum akun dikunci'
    ],
    'durasi_kunci_akun' => [
        'default' => '30',
        'tipe' => 'angka',
        'deskripsi' => 'Durasi kunci akun setelah percobaan login berlebih (dalam menit)'
    ],
    'durasi_sesi_login' => [
        'default' => '120',
        'tipe' => 'angka',
        'deskripsi' => 'Durasi sesi login aktif (dalam menit)'
    ],
    'format_tanggal' => [
        'default' => 'd/m/Y H:i',
        'tipe' => 'teks',
        'deskripsi' => 'Format tampilan tanggal dan waktu'
    ],
    'timezone' => [
        'default' => 'Asia/Jakarta',
        'tipe' => 'teks',
        'deskripsi' => 'Zona waktu sistem'
    ],
    'footer_text' => [
        'default' => '&copy; ' . date('Y') . ' Atlas LMS. Hak Cipta Dilindungi.',
        'tipe' => 'html',
        'deskripsi' => 'Teks footer pada halaman'
    ]
];

// Periksa dan buat pengaturan default jika belum ada
foreach ($sistem_keys as $key => $values) {
    $stmt = $db->prepare("SELECT id FROM pengaturan WHERE kunci = ?");
    $stmt->execute([$key]);

    if ($stmt->rowCount() == 0) {
        $id = generate_uuid();
        $stmt = $db->prepare("INSERT INTO pengaturan (id, kunci, nilai, deskripsi, tipe) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id, $key, $values['default'], $values['deskripsi'], $values['tipe']]);
    }
}

// Ambil pengaturan sistem dari database
$stmt = $db->prepare("SELECT * FROM pengaturan WHERE kunci IN (" . implode(',', array_fill(0, count(array_keys($sistem_keys)), '?')) . ") ORDER BY kunci ASC");
$stmt->execute(array_keys($sistem_keys));
$pengaturan_sistem = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ubah array ke format kunci => nilai untuk memudahkan akses
$pengaturan = [];
foreach ($pengaturan_sistem as $p) {
    $pengaturan[$p['kunci']] = $p;
}

// Jika form disubmit untuk update pengaturan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pengaturan'])) {
    // Validasi dan update data
    try {
        foreach ($_POST as $key => $value) {
            // Lewati yang bukan pengaturan sistem
            if ($key === 'update_pengaturan' || !isset($sistem_keys[$key])) {
                continue;
            }

            // Update nilai pengaturan
            $stmt = $db->prepare("UPDATE pengaturan SET nilai = ? WHERE kunci = ?");
            $stmt->execute([$value, $key]);
        }

        $sukses = "Pengaturan sistem berhasil diperbarui";

        // Refresh halaman dengan pesan sukses
        header('Location: ' . BASE_URL . '/admin/pengaturan/sistem?sukses=' . urlencode($sukses));
        exit;
    } catch (PDOException $e) {
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

// Update pengaturan dari form edit modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pengaturan']) && isset($_POST['id'])) {
    try {
        $id = $_POST['id'];
        $nilai = $_POST['nilai'];
        $deskripsi = $_POST['deskripsi'];
        $tipe = $_POST['tipe'];

        $stmt = $db->prepare("UPDATE pengaturan SET nilai = ?, deskripsi = ?, tipe = ? WHERE id = ?");
        $stmt->execute([$nilai, $deskripsi, $tipe, $id]);

        $sukses = "Pengaturan berhasil diperbarui";

        // Refresh halaman dengan pesan sukses
        header('Location: ' . BASE_URL . '/admin/pengaturan/sistem?sukses=' . urlencode($sukses));
        exit;
    } catch (PDOException $e) {
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

adminHeader("Pengaturan Sistem", "Kelola pengaturan sistem Atlas LMS");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Pengaturan Sistem</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/pengaturan">Pengaturan</a></li>
            <li class="breadcrumb-item active">Sistem</li>
        </ol>
    </div>

    <?php if (isset($_GET['sukses'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['sukses']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="m-0 font-weight-bold">Pengaturan Sistem</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="m-0">Informasi Dasar</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="nama_aplikasi" class="form-label">Nama Aplikasi</label>
                                    <input type="text" class="form-control" id="nama_aplikasi" name="nama_aplikasi"
                                        value="<?= htmlspecialchars($pengaturan['nama_aplikasi']['nilai'] ?? $sistem_keys['nama_aplikasi']['default']) ?>" required>
                                    <small class="text-muted"><?= htmlspecialchars($sistem_keys['nama_aplikasi']['deskripsi']) ?></small>
                                </div>
                                <div class="mb-3">
                                    <label for="deskripsi_aplikasi" class="form-label">Deskripsi Aplikasi</label>
                                    <textarea class="form-control" id="deskripsi_aplikasi" name="deskripsi_aplikasi" rows="3"><?= htmlspecialchars($pengaturan['deskripsi_aplikasi']['nilai'] ?? $sistem_keys['deskripsi_aplikasi']['default']) ?></textarea>
                                    <small class="text-muted"><?= htmlspecialchars($sistem_keys['deskripsi_aplikasi']['deskripsi']) ?></small>
                                </div>
                                <div class="mb-3">
                                    <label for="email_admin" class="form-label">Email Admin</label>
                                    <input type="email" class="form-control" id="email_admin" name="email_admin"
                                        value="<?= htmlspecialchars($pengaturan['email_admin']['nilai'] ?? $sistem_keys['email_admin']['default']) ?>">
                                    <small class="text-muted"><?= htmlspecialchars($sistem_keys['email_admin']['deskripsi']) ?></small>
                                </div>
                                <div class="mb-3">
                                    <label for="kontak_telepon" class="form-label">Kontak Telepon</label>
                                    <input type="text" class="form-control" id="kontak_telepon" name="kontak_telepon"
                                        value="<?= htmlspecialchars($pengaturan['kontak_telepon']['nilai'] ?? $sistem_keys['kontak_telepon']['default']) ?>">
                                    <small class="text-muted"><?= htmlspecialchars($sistem_keys['kontak_telepon']['deskripsi']) ?></small>
                                </div>
                                <div class="mb-3">
                                    <label for="alamat" class="form-label">Alamat</label>
                                    <textarea class="form-control" id="alamat" name="alamat" rows="3"><?= htmlspecialchars($pengaturan['alamat']['nilai'] ?? $sistem_keys['alamat']['default']) ?></textarea>
                                    <small class="text-muted"><?= htmlspecialchars($sistem_keys['alamat']['deskripsi']) ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="m-0">Pengaturan Keamanan</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="aktivasi_pendaftaran" name="aktivasi_pendaftaran" value="1"
                                        <?= ($pengaturan['aktivasi_pendaftaran']['nilai'] ?? $sistem_keys['aktivasi_pendaftaran']['default']) == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="aktivasi_pendaftaran">Aktifkan Pendaftaran Pengguna Baru</label>
                                    <div><small class="text-muted"><?= htmlspecialchars($sistem_keys['aktivasi_pendaftaran']['deskripsi']) ?></small></div>
                                </div>
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="verifikasi_email" name="verifikasi_email" value="1"
                                        <?= ($pengaturan['verifikasi_email']['nilai'] ?? $sistem_keys['verifikasi_email']['default']) == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="verifikasi_email">Wajib Verifikasi Email</label>
                                    <div><small class="text-muted"><?= htmlspecialchars($sistem_keys['verifikasi_email']['deskripsi']) ?></small></div>
                                </div>
                                <div class="mb-3 form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notifikasi_email" name="notifikasi_email" value="1"
                                        <?= ($pengaturan['notifikasi_email']['nilai'] ?? $sistem_keys['notifikasi_email']['default']) == '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="notifikasi_email">Kirim Notifikasi Email</label>
                                    <div><small class="text-muted"><?= htmlspecialchars($sistem_keys['notifikasi_email']['deskripsi']) ?></small></div>
                                </div>
                                <div class="mb-3">
                                    <label for="batas_percobaan_login" class="form-label">Batas Percobaan Login</label>
                                    <input type="number" class="form-control" id="batas_percobaan_login" name="batas_percobaan_login" min="1" max="10"
                                        value="<?= htmlspecialchars($pengaturan['batas_percobaan_login']['nilai'] ?? $sistem_keys['batas_percobaan_login']['default']) ?>">
                                    <small class="text-muted"><?= htmlspecialchars($sistem_keys['batas_percobaan_login']['deskripsi']) ?></small>
                                </div>
                                <div class="mb-3">
                                    <label for="durasi_kunci_akun" class="form-label">Durasi Kunci Akun (menit)</label>
                                    <input type="number" class="form-control" id="durasi_kunci_akun" name="durasi_kunci_akun" min="5" max="1440"
                                        value="<?= htmlspecialchars($pengaturan['durasi_kunci_akun']['nilai'] ?? $sistem_keys['durasi_kunci_akun']['default']) ?>">
                                    <small class="text-muted"><?= htmlspecialchars($sistem_keys['durasi_kunci_akun']['deskripsi']) ?></small>
                                </div>
                                <div class="mb-3">
                                    <label for="durasi_sesi_login" class="form-label">Durasi Sesi Login (menit)</label>
                                    <input type="number" class="form-control" id="durasi_sesi_login" name="durasi_sesi_login" min="15" max="1440"
                                        value="<?= htmlspecialchars($pengaturan['durasi_sesi_login']['nilai'] ?? $sistem_keys['durasi_sesi_login']['default']) ?>">
                                    <small class="text-muted"><?= htmlspecialchars($sistem_keys['durasi_sesi_login']['deskripsi']) ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="m-0">Lokalisasi</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="format_tanggal" class="form-label">Format Tanggal</label>
                                    <input type="text" class="form-control" id="format_tanggal" name="format_tanggal"
                                        value="<?= htmlspecialchars($pengaturan['format_tanggal']['nilai'] ?? $sistem_keys['format_tanggal']['default']) ?>">
                                    <small class="text-muted"><?= htmlspecialchars($sistem_keys['format_tanggal']['deskripsi']) ?></small>
                                </div>
                                <div class="mb-3">
                                    <label for="timezone" class="form-label">Zona Waktu</label>
                                    <select class="form-select" id="timezone" name="timezone">
                                        <?php
                                        $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
                                        $current_timezone = $pengaturan['timezone']['nilai'] ?? $sistem_keys['timezone']['default'];
                                        foreach ($timezones as $tz):
                                        ?>
                                            <option value="<?= htmlspecialchars($tz) ?>" <?= $tz === $current_timezone ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($tz) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted"><?= htmlspecialchars($sistem_keys['timezone']['deskripsi']) ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h6 class="m-0">Tampilan</h6>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="footer_text" class="form-label">Teks Footer</label>
                                    <textarea class="form-control" id="footer_text" name="footer_text" rows="3"><?= htmlspecialchars($pengaturan['footer_text']['nilai'] ?? $sistem_keys['footer_text']['default']) ?></textarea>
                                    <small class="text-muted"><?= htmlspecialchars($sistem_keys['footer_text']['deskripsi']) ?></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-end">
                            <a href="<?= BASE_URL ?>/admin/pengaturan" class="btn btn-secondary me-2">Kembali</a>
                            <button type="submit" name="update_pengaturan" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php adminFooter(); ?>