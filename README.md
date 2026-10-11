# 🌸 Sistem Inventori Toko Hijab & Kerudung
**Mini Project 3 — Pertemuan 4: Relational Database & Advanced PHP**

Aplikasi web manajemen inventori produk toko hijab dan kerudung berbasis **PHP Native** dan **MySQL** yang telah disesuaikan secara penuh dengan seluruh ketentuan dan rubrik evaluasi materi **Pertemuan 4 (Web 4)**: dari sistem CRUD sederhana menuju aplikasi berbasis relasi data, transaksi database atomik (ACID), hak akses berjenjang (RBAC), serta penegakan aturan bisnis (*business logic*) yang ketat.

---

## 📌 Daftar Isi
- [1. Fitur Utama & Kesesuaian Silabus Web 4](#1-fitur-utama--kesesuaian-silabus-web-4)
- [2. Aturan Bisnis (Business Rules Wajib)](#2-aturan-bisnis-business-rules-wajib)
- [3. Struktur Tabel & Desain Relasi Database (ERD)](#3-struktur-tabel--desain-relasi-database-erd)
- [4. Struktur Berkas Proyek](#4-struktur-berkas-proyek)
- [5. Akun Pengujian Sistem (Role-Based Access)](#5-akun-pengujian-sistem-role-based-access)
- [6. Panduan Instalasi & Menjalankan Aplikasi](#6-panduan-instalasi--menjalankan-aplikasi)
- [7. Pengujian Kasus Khusus (Validation & Security Test)](#7-pengujian-kasus-khusus-validation--security-test)
- [8. Checklist Kelengkapan Pengumpulan Tugas](#8-checklist-kelengkapan-pengumpulan-tugas)

---

## 1. Fitur Utama & Kesesuaian Silabus Web 4

Sesuai materi slide perkuliahan Web 4, sistem ini mengimplementasikan:

1. **Authentication & Session Security**:
   - Sistem Login dan Logout dengan session PHP yang aman.
   - Password hashing menggunakan algoritma bawaan PHP (`password_hash` dan `password_verify`).
   - Proteksi session middleware pada seluruh halaman internal ([`cek_login.php`](cek_login.php)).

2. **Authorization (Multi-User Role)**:
   - **Role Admin**: Hak akses penuh (CRUD master produk, transaksi barang masuk/keluar, log mutasi stok).
   - **Role Staff**: Hak akses operasional terbatas (hanya dapat melakukan pencatatan mutasi stok dan melihat riwayat). Aksi tambah, edit, dan hapus diblokir baik di tampilan maupun di level backend.

3. **Database Transaction (ACID Compliance)**:
   - Transaksi stok masuk dan keluar dijalankan dengan mekanisme transaksi atomik (`$pdo->beginTransaction()`, `$pdo->commit()`, `$pdo->rollBack()`).
   - Mencegah inkonsistensi stok saat terjadi error di tengah proses.
   - Penerapan *Pessimistic Locking* (`SELECT ... FOR UPDATE`) untuk menghindari *race conditions*.

4. **Keamanan Aplikasi (PDO Prepared Statements & Output Escaping)**:
   - 100% query SQL menggunakan **PDO Prepared Statements** dengan parameter binding untuk mencegah **SQL Injection (SQLi)**.
   - Parameter `PDO::ATTR_EMULATE_PREPARES => false` aktif.
   - Seluruh tampilan variabel dinamis menggunakan escaping `htmlspecialchars()` untuk menangkal serangan **Cross-Site Scripting (XSS)**.

5. **Listing Lanjutan & Dashboard Agregat**:
   - Fitur pencarian (*search*) berdasarkan nama barang atau SKU.
   - Filter data berdasarkan kategori produk.
   - Pengurutan data (*sorting*) dengan whitelisting kolom yang diizinkan.
   - Widget ringkasan inventori (Total Produk, Total Stok Fisik, Perkiraan Nilai Aset Inventori, dan Log Transaksi Terakhir).

---

## 2. Aturan Bisnis (Business Rules Wajib)

Berdasarkan silabus **Pertemuan 4 Slide 33 (Business Rules)**, berikut adalah aturan bisnis yang diterapkan secara ketat oleh sistem:

1. **SKU Unik**: Setiap produk hijab wajib memiliki kode SKU yang unik di database (`UNIQUE constraint`). Sistem akan menolak penambahan produk jika SKU sudah terdaftar.
2. **Kuantitas & Harga Non-Negatif**: Nilai harga jual dan kuantitas stok produk tidak boleh bernilai negatif (`harga >= 0`, `stok >= 0`, `jumlah transaksi >= 1`).
3. **Validasi Stok Barang Keluar**: Transaksi barang keluar tidak boleh melebihi jumlah stok fisik yang tersedia di gudang/toko.
4. **Stok Tidak Pernah Negatif**: Jika permintaan barang keluar lebih besar dari stok saat ini, sistem seketika membatalkan proses (*rollback*) dan menampilkan pesan peringatan.
5. **Integritas Riwayat Produk**: Produk yang telah memiliki jejak transaksi terikat oleh integritas referensial foreign key (`transaksi_stok.produk_id -> produk.id`).
6. **Autentikasi Wajib**: Semua halaman operasional sistem tidak dapat diakses secara langsung tanpa proses login yang sah.
7. **Pemisahan Otoritas**: Pengguna dengan peran **Staff** dilarang keras memanipulasi master data produk (tambah/edit/hapus).
8. **Whitelisting Sorting**: Pengurutan data tabel hanya diizinkan melalui kolom-kolom yang telah divalidasi guna mencegah manipulasi query via URL.

---

## 3. Struktur Tabel & Desain Relasi Database (ERD)

Database: `` (Engine: InnoDB, Charset: utf8mb4)

```text
+-------------------+        1 : N        +-----------------------+
|      produk       | -------------------< |    transaksi_stok     |
+-------------------+                      +-----------------------+
| PK id             |                      | PK id                 |
|    sku (UNIQUE)   |                      | FK produk_id          |
|    nama_barang    |                      |    jenis masuk/keluar |
|    kategori       |                      |    jumlah             |
|    bahan          |                      |    stok_sebelum       |
|    harga          |                      |    stok_sesudah       |
|    stok           |                      |    keterangan         |
|    created_at     |                      |    tanggal            |
+-------------------+                      +-----------------------+

+-------------------+
|       users       |
+-------------------+
| PK id             |
|    username (UNQ) |
|    password       |
|    nama_lengkap   |
|    role (adm/stf) |
|    created_at     |
+-------------------+
```

---

## 4. Struktur Berkas Proyek

```text
inventory-management-system-php/
├── cek_login.php             # Middleware validasi otentikasi session user
├── database.sql              # DDL schema database MySQL & sample data awal
├── edit.php                  # Antarmuka & pemrosesan edit data produk (Khusus Admin)
├── hapus.php                 # Pemrosesan penghapusan produk (Khusus Admin)
├── index.php                 # Dashboard utama, ringkasan inventori & tabel produk
├── koneksi.php               # Konfigurasi koneksi database PDO & exception handling
├── login.php                 # Form otentikasi login multi-role
├── logout.php                # Destruksi session & redirect ke login
├── README.md                 # Dokumentasi spesifikasi, business rules & panduan sistem
├── riwayat_transaksi.php     # Halaman audit trail / log mutasi stok masuk & keluar
├── tambah.php                # Antarmuka & pemrosesan penambahan produk baru (Khusus Admin)
└── transaksi.php             # Modul transaksi barang masuk/keluar (PDO Transaction ACID)
```

---

## 5. Akun Pengujian Sistem (Role-Based Access)

Gunakan akun uji coba berikut saat pengujian:

| Role | Username | Password | Deskripsi Hak Akses |
| :--- | :--- | :--- | :--- |
| **👑 Admin** | `admin` | `admin123` | Akses Penuh: Menambah produk, mengedit produk, menghapus produk, mutasi stok, serta memantau log mutasi. |
| **🧕 Staff** | `staff` | `staff123` | Akses Terbatas: Hanya dapat melakukan transaksi mutasi (Barang Masuk/Keluar) dan melihat riwayat mutasi. |

---

## 6. Panduan Instalasi & Menjalankan Aplikasi

### 1. Letak Folder Proyek
Pastikan folder proyek diletakkan langsung di direktori web server XAMPP:
```text
C:\xampp\htdocs\inventory-management-system-php
```

### 2. Nyalakan Web Server & Database
1. Buka aplikasi **XAMPP Control Panel**.
2. Klik tombol **Start** pada modul **Apache** dan **MySQL**.

### 3. Import Database MySQL
1. Buka browser dan akses phpMyAdmin:
   ```text
   http://localhost/phpmyadmin/   (atau port 8080: http://localhost:8080/phpmyadmin/)
   ```
2. Buat database baru bernama: `db_toko_hijab`
3. Pilih tab **Import**, pilih berkas [`database.sql`](database.sql), lalu klik **Go / Kirim**.

### 4. Buka Aplikasi di Browser
Akses URL berikut pada browser Anda:
```text
http://localhost/inventory-management-system-php/login.php
```
*(Catatan: Jika Apache Anda dikonfigurasi pada port 8080, gunakan `http://localhost:8080/inventory-management-system-php/login.php`)*

---

## 7. Pengujian Kasus Khusus (Validation & Security Test)

Sesuai instruksi **Pertemuan 4 Slide 38** *(Uji minimal satu kasus validation error dan satu kasus akses terlarang)*:

### 🧪 Kasus 1: Uji Validation Error (Stok Tidak Boleh Negatif)
- **Skenario**: Mencoba mengeluarkan barang dengan jumlah melebihi stok yang ada.
- **Langkah**:
  1. Login sebagai `admin` atau `staff`.
  2. Buka halaman transaksi (`transaksi.php`), pilih produk dengan sisa stok misal `10 pcs`.
  3. Pilih jenis transaksi **"Barang Keluar"** dan masukkan jumlah `15 pcs`.
  4. Klik **Eksekusi Transaksi**.
- **Hasil yang Diharapkan**: Sistem membatalkan transaksi (*Rollback*), stok tidak berkurang, dan muncul pesan error:
  `Transaksi Gagal: Stok tidak mencukupi! Stok saat ini: 10 pcs, diminta keluar: 15 pcs.`

### 🛡️ Kasus 2: Uji Akses Terlarang (Unauthorized Access Control)
- **Skenario**: Pengguna dengan role `staff` mencoba mengakses langsung URL penambahan atau penghapusan produk.
- **Langkah**:
  1. Login menggunakan akun `staff` (`staff` / `staff123`).
  2. Coba ketik manual URL penambahan produk pada bilah browser:
     `http://localhost/inventory-management-system-php/tambah.php`
- **Hasil yang Diharapkan**: Sistem mendeteksi role bukan admin, memblokir eksekusi halaman, dan mengarahkan kembali (*redirect*) ke `index.php` atau menampilkan pesan akses ditolak.

---

## 8. Checklist Kelengkapan Pengumpulan Tugas

Berdasarkan checklist rubrik penilaian Web 4 (Slide 35 & 36):

- [x] **Relasi & Foreign Key**: Tabel `transaksi_stok` terhubung dengan `produk` menggunakan foreign key InnoDB.
- [x] **SKU Unik & Validasi Server**: Pengecekan SKU duplikat dan validasi data server-side aktif.
- [x] **Login, Logout, Session, & Role**: Otentikasi dan otorisasi Admin vs Staff berjalan optimal.
- [x] **Pencegahan SQL Injection**: Tidak ada konkatenasi query SQL dari input user; seluruhnya menggunakan PDO Prepared Statement.
- [x] **Output HTML Encoding**: Menggunakan `htmlspecialchars()` untuk mencegah XSS.
- [x] **Search & Filter**: Pencarian kata kunci dan filter kategori produk berjalan normal.
- [x] **Stok Tidak Negatif & Histori Tersimpan**: Log audit mutasi tercatat otomatis setiap terjadi perubahan stok.
- [x] **Database Transaction**: Menggunakan mekanisme ACID (`beginTransaction`, `commit`, `rollBack`).
- [x] **Deliverables Siap**: Source code, `database.sql`, dan `README.md`.
