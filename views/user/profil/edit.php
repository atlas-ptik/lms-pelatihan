<?php
// Path: views/user/profil/edit.php

require_once BASE_PATH . '/layouts/user/user-layout.php';

if (!isset($_SESSION['user'])) {
    header('Location: ' . BASE_URL . '/user/login');
    exit;
}

$db = dbConnect();
$userId = $_SESSION['user']['id'];

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $email = $_POST['email'] ?? '';
    $nomor_telepon = $_POST['nomor_telepon'] ?? '';
    $bio = $_POST['bio'] ?? '';

    if (empty($nama_lengkap) || empty($email)) {
        $error = 'Nama lengkap dan email harus diisi';
    } else {
        try {
            $checkEmail = $db->prepare("SELECT id FROM pengguna WHERE email = :email AND id <> :id");
            $checkEmail->execute([':email' => $email, ':id' => $userId]);

            if ($checkEmail->fetch()) {
                $error = 'Email sudah digunakan oleh pengguna lain';
            } else {
                $foto_profil = '';

                if (!empty($_FILES['foto_profil']['name'])) {
                    $upload_dir = BASE_PATH . '/assets/img/profile/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                    if (!in_array($file_extension, $allowed_extensions)) {
                        $error = 'Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF';
                    } else {
                        $new_filename = $userId . '_' . time() . '.' . $file_extension;
                        $upload_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($_FILES['foto_profil']['tmp_name'], $upload_path)) {
                            $foto_profil = $new_filename;

                            $getUserPhoto = $db->prepare("SELECT foto_profil FROM pengguna WHERE id = :id");
                            $getUserPhoto->execute([':id' => $userId]);
                            $oldPhoto = $getUserPhoto->fetch()['foto_profil'];

                            if ($oldPhoto && file_exists($upload_dir . $oldPhoto)) {
                                unlink($upload_dir . $oldPhoto);
                            }
                        } else {
                            $error = 'Gagal mengunggah foto profil';
                        }
                    }
                }

                if (empty($error)) {
                    $stmt = $db->prepare("
                        UPDATE pengguna 
                        SET nama_lengkap = :nama_lengkap, 
                            email = :email, 
                            nomor_telepon = :nomor_telepon, 
                            bio = :bio
                            " . ($foto_profil ? ", foto_profil = :foto_profil" : "") . "
                        WHERE id = :id
                    ");

                    $params = [
                        ':nama_lengkap' => $nama_lengkap,
                        ':email' => $email,
                        ':nomor_telepon' => $nomor_telepon,
                        ':bio' => $bio,
                        ':id' => $userId
                    ];

                    if ($foto_profil) {
                        $params[':foto_profil'] = $foto_profil;
                    }

                    $stmt->execute($params);

                    $_SESSION['user']['nama_lengkap'] = $nama_lengkap;
                    $_SESSION['user']['email'] = $email;

                    $success = true;
                }
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

$stmt = $db->prepare("SELECT * FROM pengguna WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

$content = function () use ($user, $success, $error) {
    $baseUrl = BASE_URL;
?>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Edit Profil</h5>
                    <a href="<?= $baseUrl ?>/user/profil" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            Profil berhasil diperbarui!
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3 text-center">
                            <?php if ($user['foto_profil']): ?>
                                <img src="<?= $baseUrl ?>/assets/img/profile/<?= $user['foto_profil'] ?>" alt="Foto Profil"
                                    class="rounded-circle img-thumbnail mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto mb-3"
                                    style="width: 150px; height: 150px;">
                                    <span class="display-4 text-white"><?= strtoupper(substr($user['nama_lengkap'], 0, 1)) ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="foto_profil" class="form-label">Foto Profil</label>
                                <input type="file" class="form-control" id="foto_profil" name="foto_profil">
                                <small class="text-muted">Unggah gambar baru untuk mengubah foto profil (JPG, JPEG, PNG, GIF)</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap"
                                value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="nomor_telepon" class="form-label">Nomor Telepon</label>
                            <input type="text" class="form-control" id="nomor_telepon" name="nomor_telepon"
                                value="<?= htmlspecialchars($user['nomor_telepon'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="5"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                            <small class="text-muted">Ceritakan sedikit tentang diri Anda</small>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php
};

userLayout('Edit Profil', $content(), 'profil');
