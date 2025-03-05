<?php
// Path: views/admin/pengguna/tambah.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Ambil daftar role
$db = dbConnect();
$stmt = $db->query("SELECT id, nama FROM role ORDER BY nama");
$role_list = $stmt->fetchAll();

// Proses form
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = isset($_POST['nama_lengkap']) ? trim($_POST['nama_lengkap']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $role_id = isset($_POST['role_id']) ? $_POST['role_id'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'aktif';

    // Validasi input
    if (empty($nama_lengkap) || empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($role_id)) {
        $error = 'Semua field harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username hanya boleh berisi huruf, angka, dan underscore';
    } else {
        // Cek username sudah digunakan atau belum
        $stmt = $db->prepare("SELECT id FROM pengguna WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $error = 'Username sudah digunakan';
        } else {
            // Cek email sudah digunakan atau belum
            $stmt = $db->prepare("SELECT id FROM pengguna WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = 'Email sudah terdaftar';
            } else {
                // Proses upload foto jika ada
                $foto_profil = '';
                if (!empty($_FILES['foto_profil']['name'])) {
                    $target_dir = BASE_PATH . '/uploads/profil/';

                    // Buat direktori jika belum ada
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }

                    // Generate nama file baru
                    $file_ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
                    $foto_profil = 'user_' . time() . '.' . $file_ext;
                    $target_file = $target_dir . $foto_profil;

                    // Validasi file
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    if (!in_array(strtolower($file_ext), $allowed_types)) {
                        $error = 'Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF';
                    } elseif ($_FILES['foto_profil']['size'] > 2000000) { // 2MB
                        $error = 'Ukuran file maksimal 2MB';
                    } elseif (!move_uploaded_file($_FILES['foto_profil']['tmp_name'], $target_file)) {
                        $error = 'Gagal mengupload file';
                    }
                }

                if (empty($error)) {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $user_id = generate_uuid();

                    // Tambahkan user baru
                    $stmt = $db->prepare("
                        INSERT INTO pengguna (id, username, password, email, nama_lengkap, role_id, foto_profil, status, waktu_dibuat)
                        VALUES (:id, :username, :password, :email, :nama_lengkap, :role_id, :foto_profil, :status, NOW())
                    ");

                    $stmt->bindParam(':id', $user_id);
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':password', $hashed_password);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':nama_lengkap', $nama_lengkap);
                    $stmt->bindParam(':role_id', $role_id);
                    $stmt->bindParam(':foto_profil', $foto_profil);
                    $stmt->bindParam(':status', $status);

                    if ($stmt->execute()) {
                        $success = 'Pengguna berhasil ditambahkan';

                        // Reset form
                        $nama_lengkap = $username = $email = $password = $confirm_password = '';
                        $role_id = $status = '';
                    } else {
                        $error = 'Terjadi kesalahan. Silakan coba lagi.';

                        // Hapus file yang sudah diupload jika ada
                        if (!empty($foto_profil) && file_exists($target_dir . $foto_profil)) {
                            unlink($target_dir . $foto_profil);
                        }
                    }
                } else {
                    // Hapus file yang sudah diupload jika ada error
                    if (!empty($foto_profil) && file_exists($target_dir . $foto_profil)) {
                        unlink($target_dir . $foto_profil);
                    }
                }
            }
        }
    }
}

adminHeader("Tambah Pengguna", "Tambah pengguna baru");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Tambah Pengguna</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/pengguna">Pengguna</a></li>
            <li class="breadcrumb-item active">Tambah</li>
        </ol>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">Form Tambah Pengguna</h6>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill me-2"></i> <?= $success ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($nama_lengkap ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required>
                            <div class="form-text">Username hanya boleh berisi huruf, angka, dan underscore</div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Password minimal 8 karakter</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <option value="">Pilih Role</option>
                                <?php foreach ($role_list as $role): ?>
                                    <option value="<?= $role['id'] ?>" <?= isset($role_id) && $role_id === $role['id'] ? 'selected' : '' ?>>
                                        <?= ucfirst($role['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="aktif" <?= isset($status) && $status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= isset($status) && $status === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                <option value="diblokir" <?= isset($status) && $status === 'diblokir' ? 'selected' : '' ?>>Diblokir</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="foto_profil" class="form-label">Foto Profil</label>
                            <input type="file" class="form-control" id="foto_profil" name="foto_profil" accept="image/*">
                            <div class="form-text">Format: JPG, JPEG, PNG, GIF. Maks: 2MB</div>
                        </div>

                        <div class="mb-3">
                            <div id="preview-container" class="mt-3 d-none">
                                <p>Preview:</p>
                                <img id="preview-image" src="#" alt="Preview" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= BASE_URL ?>/admin/pengguna" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Preview gambar saat dipilih
    document.getElementById('foto_profil').addEventListener('change', function(e) {
        const previewContainer = document.getElementById('preview-container');
        const previewImage = document.getElementById('preview-image');

        if (this.files && this.files[0]) {
            const reader = new FileReader();

            reader.onload = function(e) {
                previewImage.setAttribute('src', e.target.result);
                previewContainer.classList.remove('d-none');
            }

            reader.readAsDataURL(this.files[0]);
        } else {
            previewContainer.classList.add('d-none');
        }
    });
</script>

<?php adminFooter(); ?>