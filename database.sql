-- Script Inisialisasi Database & Tabel untuk Toko Kerudung / Hijab

CREATE DATABASE IF NOT EXISTS `db_toko_hijab` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_toko_hijab`;

-- 1. Tabel Users (Mendukung Multi-Role: Admin & Staff)
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `role` ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Akun Default Pengujian
INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`)
VALUES 
    (1, 'admin', 'admin123', 'Admin', 'admin'),
    (2, 'staff', 'staff123', 'Staff Kasir / Gudang', 'staff')
ON DUPLICATE KEY UPDATE 
    `nama_lengkap` = VALUES(`nama_lengkap`),
    `role` = VALUES(`role`);

-- 2. Tabel Produk (Tanpa Kolom Foto Sesuai Permintaan)
CREATE TABLE IF NOT EXISTS `produk` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sku` VARCHAR(50) NOT NULL UNIQUE,
    `nama_barang` VARCHAR(150) NOT NULL,
    `kategori` VARCHAR(100) NOT NULL,
    `bahan` VARCHAR(100) DEFAULT NULL,
    `harga` DECIMAL(12, 2) NOT NULL DEFAULT 0,
    `stok` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Tabel Transaksi Barang Masuk / Keluar (Riwayat Stok)
CREATE TABLE IF NOT EXISTS `transaksi_stok` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `produk_id` INT NOT NULL,
    `jenis` ENUM('masuk', 'keluar') NOT NULL,
    `jumlah` INT NOT NULL,
    `stok_sebelum` INT NOT NULL,
    `stok_sesudah` INT NOT NULL,
    `keterangan` VARCHAR(255) DEFAULT NULL,
    `tanggal` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_transaksi_produk` FOREIGN KEY (`produk_id`) 
        REFERENCES `produk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
