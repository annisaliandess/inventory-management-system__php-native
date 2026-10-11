<?php
// hapus.php
require_once 'cek_login.php';
require_once 'koneksi.php';

// Hak Akses: Hanya Admin yang boleh menghapus produk
if (($_SESSION['role'] ?? '') !== 'admin') {
    die("<h3>Akses Ditolak: Hanya Admin yang berwenang menghapus data produk!</h3><p><a href='index.php'>Kembali ke Beranda</a></p>");
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = intval($_GET['id']);
    
    try {
        $stmtDelete = $pdo->prepare("DELETE FROM produk WHERE id = :id");
        $stmtDelete->execute([':id' => $id]);
    } catch (PDOException $e) {
        die("Gagal menghapus produk: " . $e->getMessage());
    }
}

header("Location: index.php?status=sukses_hapus");
exit;
?>
