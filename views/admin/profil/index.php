<?php
// Path: views/admin/profil/index.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

$db = dbConnect();
$user_id = $_SESSION['user']['id'];

// Ambil data lengkap pengguna
$stmt = $db->prepare("SELECT p.*, r.nama as role_name FROM pengguna p JOIN role r ON p.role_id = r.id WHERE p.id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Jika form update profil disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $nomor_telepon = trim($_POST['nomor_telepon']);
    $bio = trim($_POST['bio']);

    $errors = [];

    // Validasi data
    if (empty($nama_lengkap)) {
        $errors[] = "Nama lengkap harus diisi";
    }

    if (empty($email)) {
        $errors[] = "Email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    } else {
        // Cek apakah email sudah digunakan pengguna lain
        $stmt = $db->prepare("SELECT id FROM pengguna WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email sudah digunakan pengguna lain";
        }
    }

    // Proses upload foto profil jika ada
    $foto_profil = $user['foto_profil'];
    if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = BASE_PATH . '/uploads/profil/';

        // Buat direktori jika belum ada
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileExt = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
        $fileName = 'profile_' . $user_id . '_' . time() . '.' . $fileExt;
        $uploadFile = $uploadDir . $fileName;

        $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($fileExt, $allowedExts)) {
            $errors[] = "Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF";
        } elseif ($_FILES['foto_profil']['size'] > $maxFileSize) {
            $errors[] = "Ukuran file terlalu besar. Maksimal 2MB";
        } elseif (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $uploadFile)) {
            // Hapus foto lama jika berhasil upload dan bukan foto default
            if ($foto_profil && file_exists($uploadDir . $foto_profil) && $foto_profil != 'default.png') {
                unlink($uploadDir . $foto_profil);
            }
            $foto_profil = $fileName;
        } else {
            $errors[] = "Gagal mengupload foto profil";
        }
    }

    // Update data jika tidak ada error
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE pengguna SET nama_lengkap = ?, email = ?, nomor_telepon = ?, bio = ?, foto_profil = ? WHERE id = ?");
            $stmt->execute([$nama_lengkap, $email, $nomor_telepon, $bio, $foto_profil, $user_id]);

            // Update session
            $_SESSION['user']['nama_lengkap'] = $nama_lengkap;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['foto_profil'] = $foto_profil;

            // Set flash message dan refresh
            $sukses = "Profil berhasil diperbarui";
            header('Location: ' . BASE_URL . '/admin/profil?sukses=' . urlencode($sukses));
            exit;
        } catch (PDOException $e) {
            $errors[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

// Jika form ganti password disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];

    $errors_password = [];

    // Validasi password
    if (empty($password_lama)) {
        $errors_password[] = "Password lama harus diisi";
    } else {
        // Verifikasi password lama
        $stmt = $db->prepare("SELECT password FROM pengguna WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_password = $stmt->fetch()['password'];

        if (!password_verify($password_lama, $current_password)) {
            $errors_password[] = "Password lama tidak sesuai";
        }
    }

    if (empty($password_baru)) {
        $errors_password[] = "Password baru harus diisi";
    } elseif (strlen($password_baru) < 8) {
        $errors_password[] = "Password baru minimal 8 karakter";
    }

    if ($password_baru !== $konfirmasi_password) {
        $errors_password[] = "Konfirmasi password tidak sesuai";
    }

    // Update password jika tidak ada error
    if (empty($errors_password)) {
        try {
            $hashed_password = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE pengguna SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);

            // Set flash message dan refresh
            $sukses_password = "Password berhasil diperbarui";
            header('Location: ' . BASE_URL . '/admin/profil?sukses_password=' . urlencode($sukses_password));
            exit;
        } catch (PDOException $e) {
            $errors_password[] = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
}

adminHeader("Profil Admin", "Kelola profil admin");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Profil Admin</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Profil</li>
        </ol>
    </div>

    <?php if (isset($_GET['sukses'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['sukses']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['sukses_password'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_GET['sukses_password']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-xl-4 col-lg-5">
            <!-- Profil Card -->
            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 font-weight-bold">Informasi Profil</h5>
                </div>
                <div class="card-body text-center">
                    <?php if (!empty($user['foto_profil'])): ?>
                        <img src="<?= BASE_URL ?>/uploads/profil/<?= htmlspecialchars($user['foto_profil']) ?>" alt="<?= htmlspecialchars($user['nama_lengkap']) ?>" class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 150px; height: 150px; font-size: 64px;">
                            <?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>

                    <h4 class="mt-3"><?= htmlspecialchars($user['nama_lengkap']) ?></h4>
                    <p class="badge bg-primary"><?= htmlspecialchars($user['role_name']) ?></p>

                    <ul class="list-group list-group-flush mt-3">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><i class="bi bi-envelope me-2"></i> Email</span>
                            <span class="text-muted"><?= htmlspecialchars($user['email']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><i class="bi bi-phone me-2"></i> Telepon</span>
                            <span class="text-muted"><?= htmlspecialchars($user['nomor_telepon'] ?: '-') ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><i class="bi bi-person-badge me-2"></i> Username</span>
                            <span class="text-muted"><?= htmlspecialchars($user['username']) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><i class="bi bi-calendar-check me-2"></i> Terdaftar</span>
                            <span class="text-muted"><?= date('d M Y', strtotime($user['waktu_dibuat'])) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span><i class="bi bi-clock-history me-2"></i> Login Terakhir</span>
                            <span class="text-muted"><?= $user['terakhir_login'] ? date('d M Y H:i', strtotime($user['terakhir_login'])) : '-' ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bio Card -->
            <?php if (!empty($user['bio'])): ?>
                <div class="card mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="m-0 font-weight-bold">Bio</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-xl-8 col-lg-7">
            <!-- Edit Profil Card -->
            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="edit-tab" data-bs-toggle="tab" data-bs-target="#edit" type="button" role="tab" aria-controls="edit" aria-selected="true">Edit Profil</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">Ganti Password</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="profileTabsContent">
                        <!-- Tab Edit Profil -->
                        <div class="tab-pane fade show active" id="edit" role="tabpanel" aria-labelledby="edit-tab">
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

                            <form method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                                        <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="nomor_telepon" class="form-label">Nomor Telepon</label>
                                        <input type="text" class="form-control" id="nomor_telepon" name="nomor_telepon" value="<?= htmlspecialchars($user['nomor_telepon'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($user['username']) ?>" readonly disabled>
                                        <small class="text-muted">Username tidak dapat diubah</small>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="bio" class="form-label">Bio</label>
                                        <textarea class="form-control" id="bio" name="bio" rows="4"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label for="foto_profil" class="form-label">Foto Profil</label>
                                        <input class="form-control" type="file" id="foto_profil" name="foto_profil" accept="image/*">
                                        <small class="text-muted">Upload foto baru untuk mengganti foto profil saat ini (JPG, JPEG, PNG, atau GIF, max 2MB)</small>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" name="update_profil" class="btn btn-primary">Simpan Perubahan</button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Tab Ganti Password -->
                        <div class="tab-pane fade" id="password" role="tabpanel" aria-labelledby="password-tab">
                            <?php if (isset($errors_password) && !empty($errors_password)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <ul class="mb-0">
                                        <?php foreach ($errors_password as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-3">
                                    <label for="password_lama" class="form-label">Password Lama</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password_lama" name="password_lama" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_lama">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="password_baru" class="form-label">Password Baru</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password_baru" name="password_baru" required minlength="8">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password_baru">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Minimal 8 karakter</small>
                                </div>
                                <div class="mb-3">
                                    <label for="konfirmasi_password" class="form-label">Konfirmasi Password Baru</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="konfirmasi_password">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                <button type="submit" name="update_password" class="btn btn-primary">Perbarui Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aktivitas Card -->
            <div class="card">
                <div class="card-header bg-white py-3">
                    <h5 class="m-0 font-weight-bold">Aktivitas Terbaru</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Fitur log aktivitas akan segera tersedia. Pantau aktivitas login, perubahan data, dan tindakan admin lainnya.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle password visibility
        const toggleButtons = document.querySelectorAll('.toggle-password');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = this.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });

        // Highlight active tab based on URL hash or error
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('sukses_password') || <?= isset($errors_password) ? 'true' : 'false' ?>) {
            const passwordTab = document.getElementById('password-tab');
            const passwordPane = document.getElementById('password');
            const editTab = document.getElementById('edit-tab');
            const editPane = document.getElementById('edit');

            passwordTab.classList.add('active');
            passwordPane.classList.add('show', 'active');
            editTab.classList.remove('active');
            editPane.classList.remove('show', 'active');
        }
    });
</script>

<?php adminFooter(); ?>