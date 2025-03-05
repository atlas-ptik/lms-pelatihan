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
                    $upload_dir = BASE_PATH . '/uploads/profil/';
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

$content = '
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>Edit Profil</h3>
    <a href="' . BASE_URL . '/user/profil" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>';

if ($success) {
    $content .= '
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>Profil berhasil diperbarui!
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>';
}

if ($error) {
    $content .= '
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>' . $error . '
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>';
}

$content .= '
<div class="card">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <div class="mb-4 text-center">
                <div class="mb-3">';

if ($user['foto_profil']) {
    $content .= '<img src="' . BASE_URL . '/uploads/profil/' . $user['foto_profil'] . '" alt="Foto Profil" 
                 class="rounded-circle img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">';
} else {
    $content .= '<div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto" 
                 style="width: 150px; height: 150px;">
                <span class="display-4 text-white">' . strtoupper(substr($user['nama_lengkap'], 0, 1)) . '</span>
            </div>';
}

$content .= '
                </div>
                <div class="mb-3">
                    <label for="foto_profil" class="form-label">Foto Profil</label>
                    <input type="file" class="form-control" id="foto_profil" name="foto_profil">
                    <div class="form-text">Unggah gambar (JPG, JPEG, PNG, GIF)</div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                       value="' . htmlspecialchars($user['nama_lengkap']) . '" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="' . htmlspecialchars($user['email']) . '" required>
            </div>
            
            <div class="mb-3">
                <label for="nomor_telepon" class="form-label">Nomor Telepon</label>
                <input type="text" class="form-control" id="nomor_telepon" name="nomor_telepon" 
                       value="' . htmlspecialchars($user['nomor_telepon'] ?? '') . '">
            </div>
            
            <div class="mb-3">
                <label for="bio" class="form-label">Bio</label>
                <textarea class="form-control" id="bio" name="bio" rows="4">' . htmlspecialchars($user['bio'] ?? '') . '</textarea>
                <div class="form-text">Ceritakan sedikit tentang diri Anda</div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="' . BASE_URL . '/user/profil" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>';

userLayout('Edit Profil', $content, 'profil');
