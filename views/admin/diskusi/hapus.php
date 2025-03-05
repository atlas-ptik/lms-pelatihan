<?php
// Path: views/admin/diskusi/hapus.php

// Cek id diskusi
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: ' . BASE_URL . '/admin/diskusi');
    exit;
}

$diskusi_id = $_GET['id'];
$db = dbConnect();

// Cek apakah diskusi ada
$check_stmt = $db->prepare("SELECT id FROM diskusi WHERE id = ?");
$check_stmt->execute([$diskusi_id]);
$diskusi = $check_stmt->fetch();

if (!$diskusi) {
    header('Location: ' . BASE_URL . '/admin/diskusi?error=not_found');
    exit;
}

// Update status diskusi menjadi dihapus (soft delete)
$update_stmt = $db->prepare("UPDATE diskusi SET status = 'dihapus' WHERE id = ?");
$update_stmt->execute([$diskusi_id]);

// Redirect kembali ke halaman daftar diskusi
header('Location: ' . BASE_URL . '/admin/diskusi?deleted=1');
exit;
