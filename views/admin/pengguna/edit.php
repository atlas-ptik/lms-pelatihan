<?php
// Path: views/admin/pengguna/edit.php

require_once BASE_PATH . '/layouts/admin/admin-layout.php';

// Ambil ID pengguna
$user_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$user_id) {
    header('Location: ' . BASE_URL . '/admin/pengguna');
    exit;
}

$db = dbConnect();

// Ambil data pengguna
$stmt = $db->prepare("
    SELECT p.*, r.nama as role_nama 
    FROM pengguna p
    JOIN role r ON p.role_id = r.id
    WHERE p.id = :id
");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$pengguna = $stmt->fetch();

if (!$pengguna) {
    header('Location: ' . BASE_URL . '/admin/pengguna');
    exit;
}

// Ambil daftar role
$stmt = $db->query("SELECT id, nama FROM role ORDER BY nama");
$role_list = $stmt->fetchAll();

// Proses form
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = isset($_POST['nama_lengkap']) ? trim($_POST['nama_lengkap']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $role_id = isset($_POST['role_id']) ? $_POST['role_id'] : '';
    $status = isset($_POST['status']) ? $_POST['status'] : 'aktif';
    $hapus_foto = isset($_POST['hapus_foto']) ? true : false;
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Validasi input
    if (empty($nama_lengkap) || empty($username) || empty($email) || empty($role_id)) {
        $error = 'Nama lengkap, username, email, dan role harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username hanya boleh berisi huruf, angka, dan underscore';
    } elseif (!empty($password) && strlen($password) < 8) {
        $error = 'Password minimal 8 karakter';
    } elseif (!empty($password) && $password !== $confirm_password) {
        $error = 'Konfirmasi password tidak cocok';
    } else {
        // Cek username sudah digunakan atau belum (kecuali oleh pengguna itu sendiri)
        $stmt = $db->prepare("SELECT id FROM pengguna WHERE username = :username AND id != :id");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $error = 'Username sudah digunakan';
        } else {
            // Cek email sudah digunakan atau belum (kecuali oleh pengguna itu sendiri)
            $stmt = $db->prepare("SELECT id FROM pengguna WHERE email = :email AND id != :id");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $error = 'Email sudah terdaftar';
            } else {
                // Proses upload foto jika ada
                $foto_profil = $pengguna['foto_profil'];
                $foto_berubah = false;

                if ($hapus_foto && !empty($foto_profil)) {
                    // Hapus foto lama
                    $file_path = BASE_PATH . '/uploads/profil/' . $foto_profil;
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                    $foto_profil = '';
                    $foto_berubah = true;
                } elseif (!empty($_FILES['foto_profil']['name'])) {
                    $target_dir = BASE_PATH . '/uploads/profil/';

                    // Buat direktori jika belum ada
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }

                    // Generate nama file baru
                    $file_ext = pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION);
                    $foto_profil_baru = 'user_' . time() . '.' . $file_ext;
                    $target_file = $target_dir . $foto_profil_baru;

                    // Validasi file
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
                    if (!in_array(strtolower($file_ext), $allowed_types)) {
                        $error = 'Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF';
                    } elseif ($_FILES['foto_profil']['size'] > 2000000) { // 2MB
                        $error = 'Ukuran file maksimal 2MB';
                    } elseif (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $target_file)) {
                        // Hapus foto lama jika ada
                        if (!empty($pengguna['foto_profil'])) {
                            $old_file = $target_dir . $pengguna['foto_profil'];
                            if (file_exists($old_file)) {
                                unlink($old_file);
                            }
                        }
                        $foto_profil = $foto_profil_baru;
                        $foto_berubah = true;
                    } else {
                        $error = 'Gagal mengupload file';
                    }
                }

                if (empty($error)) {
                    // Buat query update
                    $query = "
                        UPDATE pengguna 
                        SET nama_lengkap = :nama_lengkap, 
                            username = :username, 
                            email = :email, 
                            role_id = :role_id, 
                            status = :status";

                    // Tambahkan foto_profil ke query jika berubah
                    if ($foto_berubah) {
                        $query .= ", foto_profil = :foto_profil";
                    }

                    // Tambahkan password ke query jika diisi
                    if (!empty($password)) {
                        $query .= ", password = :password";
                    }

                    $query .= ", waktu_diperbarui = NOW() WHERE id = :id";

                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':nama_lengkap', $nama_lengkap);
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':role_id', $role_id);
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':id', $user_id);

                    if ($foto_berubah) {
                        $stmt->bindParam(':foto_profil', $foto_profil);
                    }

                    if (!empty($password)) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt->bindParam(':password', $hashed_password);
                    }

                    if ($stmt->execute()) {
                        // Refresh data pengguna
                        $stmt = $db->prepare("
                            SELECT p.*, r.nama as role_nama 
                            FROM pengguna p
                            JOIN role r ON p.role_id = r.id
                            WHERE p.id = :id
                        ");
                        $stmt->bindParam(':id', $user_id);
                        $stmt->execute();
                        $pengguna = $stmt->fetch();

                        $success = 'Data pengguna berhasil diperbarui';
                    } else {
                        $error = 'Terjadi kesalahan. Silakan coba lagi.';
                    }
                }
            }
        }
    }
}

adminHeader("Edit Pengguna", "Edit data pengguna");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Edit Pengguna</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/pengguna">Pengguna</a></li>
            <li class="breadcrumb-item active">Edit</li>
        </ol>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">Edit Data Pengguna</h6>
            <a href="<?= BASE_URL ?>/admin/pengguna" class="btn btn-sm btn-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
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
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?= htmlspecialchars($pengguna['nama_lengkap']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($pengguna['username']) ?>" required>
                            <div class="form-text">Username hanya boleh berisi huruf, angka, dan underscore</div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($pengguna['email']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role_id" name="role_id" required>
                                <?php foreach ($role_list as $role): ?>
                                    <option value="<?= $role['id'] ?>" <?= $pengguna['role_id'] === $role['id'] ? 'selected' : '' ?>>
                                        <?= ucfirst($role['nama']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="aktif" <?= $pengguna['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                                <option value="nonaktif" <?= $pengguna['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                <option value="diblokir" <?= $pengguna['status'] === 'diblokir' ? 'selected' : '' ?>>Diblokir</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label d-block">Foto Profil Saat Ini</label>
                            <?php if (!empty($pengguna['foto_profil'])): ?>
                                <img src="<?= BASE_URL ?>/uploads/profil/<?= $pengguna['foto_profil'] ?>" alt="<?= htmlspecialchars($pengguna['nama_lengkap']) ?>" class="img-thumbnail mb-2" style="max-width: 150px; max-height: 150px;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="hapus_foto" name="hapus_foto">
                                    <label class="form-check-label" for="hapus_foto">Hapus foto profil</label>
                                </div>
                            <?php else: ?>
                                <div class="text-muted">Tidak ada foto profil</div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="foto_profil" class="form-label">Upload Foto Baru</label>
                            <input type="file" class="form-control" id="foto_profil" name="foto_profil" accept="image/*">
                            <div class="form-text">Format: JPG, JPEG, PNG, GIF. Maks: 2MB</div>
                        </div>

                        <div class="mb-3">
                            <div id="preview-container" class="mt-3 d-none">
                                <p>Preview Foto Baru:</p>
                                <img id="preview-image" src="#" alt="Preview" class="img-thumbnail" style="max-width: 150px; max-height: 150px;">
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label for="password" class="form-label">Password Baru <small class="text-muted">(kosongkan jika tidak ingin mengubah)</small></label>
                            <input type="password" class="form-control" id="password" name="password">
                            <div class="form-text">Password minimal 8 karakter</div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tanggal Dibuat</label>
                            <p class="form-control-static"><?= date('d M Y H:i', strtotime($pengguna['waktu_dibuat'])) ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Terakhir Login</label>
                            <p class="form-control-static">
                                <?= $pengguna['terakhir_login'] ? date('d M Y H:i', strtotime($pengguna['terakhir_login'])) : 'Belum pernah login' ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="<?= BASE_URL ?>/admin/pengguna" class="btn btn-secondary">Batal</a>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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

    // Disable hapus foto jika upload foto baru dipilih
    document.getElementById('foto_profil').addEventListener('change', function() {
        const hapusFoto = document.getElementById('hapus_foto');
        if (hapusFoto && this.files.length > 0) {
            hapusFoto.checked = false;
            hapusFoto.disabled = true;
        } else if (hapusFoto) {
            hapusFoto.disabled = false;
        }
    });
</script>

<?php adminFooter(); ?>