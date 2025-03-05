CREATE DATABASE IF NOT EXISTS `db-atlas-lms-2` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db-atlas-lms-2`;

-- Tabel pengguna
CREATE TABLE `pengguna` (
  `id` CHAR(36) NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `role_id` CHAR(36) NOT NULL,
  `foto_profil` VARCHAR(255) DEFAULT NULL,
  `nomor_telepon` VARCHAR(20) DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `status` ENUM('aktif', 'nonaktif', 'diblokir') NOT NULL DEFAULT 'aktif',
  `token_reset` VARCHAR(100) DEFAULT NULL,
  `token_expired` DATETIME DEFAULT NULL,
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `terakhir_login` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
);

-- Tabel role
CREATE TABLE `role` (
  `id` CHAR(36) NOT NULL,
  `nama` VARCHAR(50) NOT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- Tabel kursus
CREATE TABLE `kursus` (
  `id` CHAR(36) NOT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `deskripsi` TEXT NOT NULL,
  `gambar_sampul` VARCHAR(255) DEFAULT NULL,
  `durasi_menit` INT DEFAULT NULL,
  `level` ENUM('pemula', 'menengah', 'mahir', 'semua level') NOT NULL DEFAULT 'semua level',
  `harga` DECIMAL(10,2) DEFAULT 0,
  `status` ENUM('draf', 'publikasi', 'arsip') NOT NULL DEFAULT 'draf',
  `pembuat_id` CHAR(36) NOT NULL,
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- Tabel kategori
CREATE TABLE `kategori` (
  `id` CHAR(36) NOT NULL,
  `nama` VARCHAR(100) NOT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `ikon` VARCHAR(50) DEFAULT NULL,
  `urutan` INT DEFAULT 0,
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- Tabel kursus_kategori
CREATE TABLE `kursus_kategori` (
  `id` CHAR(36) NOT NULL,
  `kursus_id` CHAR(36) NOT NULL,
  `kategori_id` CHAR(36) NOT NULL,
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kursus_kategori` (`kursus_id`, `kategori_id`)
);

-- Tabel modul
CREATE TABLE `modul` (
  `id` CHAR(36) NOT NULL,
  `kursus_id` CHAR(36) NOT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `urutan` INT NOT NULL DEFAULT 0,
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- Tabel materi
CREATE TABLE `materi` (
  `id` CHAR(36) NOT NULL,
  `modul_id` CHAR(36) NOT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `tipe` ENUM('video', 'artikel', 'dokumen', 'quiz', 'tugas') NOT NULL,
  `konten` TEXT DEFAULT NULL,
  `file_path` VARCHAR(255) DEFAULT NULL,
  `video_url` VARCHAR(255) DEFAULT NULL,
  `durasi_menit` INT DEFAULT NULL,
  `urutan` INT NOT NULL DEFAULT 0,
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- Tabel pendaftaran
CREATE TABLE `pendaftaran` (
  `id` CHAR(36) NOT NULL,
  `pengguna_id` CHAR(36) NOT NULL,
  `kursus_id` CHAR(36) NOT NULL,
  `tanggal_daftar` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('aktif', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'aktif',
  `tanggal_selesai` DATETIME DEFAULT NULL,
  `progres_persen` DECIMAL(5,2) DEFAULT 0,
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pengguna_kursus` (`pengguna_id`, `kursus_id`)
);

-- Tabel progres_materi
CREATE TABLE `progres_materi` (
  `id` CHAR(36) NOT NULL,
  `pendaftaran_id` CHAR(36) NOT NULL,
  `materi_id` CHAR(36) NOT NULL,
  `status` ENUM('belum mulai', 'sedang dipelajari', 'selesai') NOT NULL DEFAULT 'belum mulai',
  `waktu_mulai` DATETIME DEFAULT NULL,
  `waktu_selesai` DATETIME DEFAULT NULL,
  `durasi_belajar_detik` INT DEFAULT 0,
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pendaftaran_materi` (`pendaftaran_id`, `materi_id`)
);

-- Tabel quiz
CREATE TABLE `quiz` (
  `id` CHAR(36) NOT NULL,
  `materi_id` CHAR(36) NOT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `durasi_menit` INT DEFAULT NULL,
  `nilai_lulus` INT NOT NULL DEFAULT 70,
  `maksimal_percobaan` INT DEFAULT NULL,
  `acak_pertanyaan` BOOLEAN NOT NULL DEFAULT FALSE,
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- Tabel pertanyaan_quiz
CREATE TABLE `pertanyaan_quiz` (
  `id` CHAR(36) NOT NULL,
  `quiz_id` CHAR(36) NOT NULL,
  `tipe` ENUM('pilihan_ganda', 'benar_salah', 'isian', 'menjodohkan', 'esai') NOT NULL,
  `pertanyaan` TEXT NOT NULL,
  `gambar` VARCHAR(255) DEFAULT NULL,
  `bobot_nilai` INT NOT NULL DEFAULT 1,
  `urutan` INT NOT NULL DEFAULT 0,
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- Tabel pilihan_jawaban
CREATE TABLE `pilihan_jawaban` (
  `id` CHAR(36) NOT NULL,
  `pertanyaan_id` CHAR(36) NOT NULL,
  `teks_jawaban` TEXT NOT NULL,
  `gambar` VARCHAR(255) DEFAULT NULL,
  `benar` BOOLEAN NOT NULL DEFAULT FALSE,
  `urutan` INT NOT NULL DEFAULT 0,
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- Tabel percobaan_quiz
CREATE TABLE `percobaan_quiz` (
  `id` CHAR(36) NOT NULL,
  `pendaftaran_id` CHAR(36) NOT NULL,
  `quiz_id` CHAR(36) NOT NULL,
  `waktu_mulai` DATETIME NOT NULL,
  `waktu_selesai` DATETIME DEFAULT NULL,
  `durasi_detik` INT DEFAULT NULL,
  `nilai` DECIMAL(5,2) DEFAULT NULL,
  `status` ENUM('sedang dikerjakan', 'selesai', 'waktu habis', 'dibatalkan') NOT NULL DEFAULT 'sedang dikerjakan',
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- Tabel jawaban_percobaan
CREATE TABLE `jawaban_percobaan` (
  `id` CHAR(36) NOT NULL,
  `percobaan_quiz_id` CHAR(36) NOT NULL,
  `pertanyaan_id` CHAR(36) NOT NULL,
  `pilihan_jawaban_id` CHAR(36) DEFAULT NULL,
  `teks_jawaban` TEXT DEFAULT NULL,
  `benar` BOOLEAN DEFAULT NULL,
  `nilai` DECIMAL(5,2) DEFAULT NULL,
  `waktu_dijawab` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `percobaan_pertanyaan` (`percobaan_quiz_id`, `pertanyaan_id`)
);

-- Tabel tugas
CREATE TABLE `tugas` (
  `id` CHAR(36) NOT NULL,
  `materi_id` CHAR(36) NOT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `deskripsi` TEXT NOT NULL,
  `tenggat_waktu` DATETIME DEFAULT NULL,
  `nilai_maksimal` INT NOT NULL DEFAULT 100,
  `file_lampiran` VARCHAR(255) DEFAULT NULL,
  `tipe_pengumpulan` ENUM('file', 'teks', 'keduanya') NOT NULL DEFAULT 'file',
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- Tabel pengumpulan_tugas
CREATE TABLE `pengumpulan_tugas` (
  `id` CHAR(36) NOT NULL,
  `pendaftaran_id` CHAR(36) NOT NULL,
  `tugas_id` CHAR(36) NOT NULL,
  `teks_jawaban` TEXT DEFAULT NULL,
  `file_jawaban` VARCHAR(255) DEFAULT NULL,
  `nilai` INT DEFAULT NULL,
  `komentar_pengajar` TEXT DEFAULT NULL,
  `waktu_pengumpulan` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('menunggu penilaian', 'dinilai', 'revisi', 'terlambat') NOT NULL DEFAULT 'menunggu penilaian',
  `waktu_penilaian` DATETIME DEFAULT NULL,
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pendaftaran_tugas` (`pendaftaran_id`, `tugas_id`)
);

-- Tabel diskusi
CREATE TABLE `diskusi` (
  `id` CHAR(36) NOT NULL,
  `kursus_id` CHAR(36) NOT NULL,
  `materi_id` CHAR(36) DEFAULT NULL,
  `pengguna_id` CHAR(36) NOT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `isi` TEXT NOT NULL,
  `status` ENUM('aktif', 'ditutup', 'dihapus') NOT NULL DEFAULT 'aktif',
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- Tabel komentar_diskusi
CREATE TABLE `komentar_diskusi` (
  `id` CHAR(36) NOT NULL,
  `diskusi_id` CHAR(36) NOT NULL,
  `pengguna_id` CHAR(36) NOT NULL,
  `isi` TEXT NOT NULL,
  `disukai` INT NOT NULL DEFAULT 0,
  `status` ENUM('aktif', 'dihapus') NOT NULL DEFAULT 'aktif',
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- Tabel notifikasi
CREATE TABLE `notifikasi` (
  `id` CHAR(36) NOT NULL,
  `pengguna_id` CHAR(36) NOT NULL,
  `judul` VARCHAR(255) NOT NULL,
  `pesan` TEXT NOT NULL,
  `tipe` ENUM('info', 'peringatan', 'sukses', 'error') NOT NULL DEFAULT 'info',
  `tautan` VARCHAR(255) DEFAULT NULL,
  `dibaca` BOOLEAN NOT NULL DEFAULT FALSE,
  `waktu_dibaca` DATETIME DEFAULT NULL,
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
);

-- Tabel sertifikat
CREATE TABLE `sertifikat` (
  `id` CHAR(36) NOT NULL,
  `pendaftaran_id` CHAR(36) NOT NULL,
  `nomor_sertifikat` VARCHAR(50) NOT NULL,
  `tanggal_terbit` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tanggal_kedaluwarsa` DATETIME DEFAULT NULL,
  `file_sertifikat` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('aktif', 'dicabut') NOT NULL DEFAULT 'aktif',
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nomor_sertifikat` (`nomor_sertifikat`),
  UNIQUE KEY `pendaftaran_id` (`pendaftaran_id`)
);

-- Tabel pengaturan
CREATE TABLE `pengaturan` (
  `id` CHAR(36) NOT NULL,
  `kunci` VARCHAR(100) NOT NULL,
  `nilai` TEXT NOT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `tipe` ENUM('teks', 'angka', 'boolean', 'json', 'html') NOT NULL DEFAULT 'teks',
  `waktu_dibuat` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `waktu_diperbarui` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kunci` (`kunci`)
);

-- Data default
INSERT INTO `role` (`id`, `nama`, `deskripsi`) VALUES 
(UUID(), 'admin', 'Administrator sistem dengan akses penuh'),
(UUID(), 'instruktur', 'Pengajar yang dapat membuat dan mengelola kursus'),
(UUID(), 'siswa', 'Peserta yang dapat mengikuti kursus');

