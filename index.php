<?php
// index.php
require_once 'cek_login.php';
require_once 'koneksi.php';

$user_role   = $_SESSION['role'] ?? 'staff';
$is_admin    = ($user_role === 'admin');
$nama_user   = $_SESSION['nama_lengkap'] ?? 'User';

// Notifikasi status pesan
$notif = "";
if (isset($_GET['status'])) {
    if ($_GET['status'] == 'sukses_tambah') {
        $notif = '<div class="alert alert-sukses">✨ Koleksi hijab baru berhasil ditambahkan!</div>';
    } elseif ($_GET['status'] == 'sukses_update') {
        $notif = '<div class="alert alert-sukses">✨ Rincian produk berhasil diperbarui!</div>';
    } elseif ($_GET['status'] == 'sukses_hapus') {
        $notif = '<div class="alert alert-info">🗑️ Produk telah dihapus dari sistem inventori.</div>';
    }
}

// Ambil data produk kerudung menggunakan PDO
try {
    $stmt = $pdo->prepare("SELECT * FROM produk ORDER BY id DESC");
    $stmt->execute();
    $produk_list = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal memuat data produk: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Inventori Toko Hijab</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Palette: Pink Soft HD with High Contrast */
            --bg-body: #fff1f2;        
            --card-bg: #ffffff;
            --primary: #be185d;         
            --primary-hover: #9d174d;
            --secondary: #0284c7;      
            --secondary-hover: #0369a1;

            --text-main: #1e1b1d;      
            --text-muted: #5e4d51;
            --border-ui: #fbcfe8;       
            --border-table: #fce7f3;

            /* Tombol Aksi */
            --btn-in: #15803d;          
            --btn-out: #b91c1c;        
            --btn-edit: #c2410c;       
            --btn-del: #9f1239;         
        }

        * {
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #fff1f2 0%, #fce7f3 45%, #fbcfe8 100%);
            margin: 0;
            padding: 36px 20px;
            color: var(--text-main);
            min-height: 100vh;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            background: var(--card-bg);
            padding: 34px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(190, 24, 93, 0.10), 0 4px 12px rgba(0, 0, 0, 0.03);
            border: 2px solid var(--border-ui);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            border-bottom: 2px solid var(--border-ui);
            padding-bottom: 20px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .title h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .title p {
            margin: 6px 0 0 0;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 500;
        }

        .alert {
            padding: 13px 18px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 24px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-sukses {
            background-color: #f0fdf4;
            color: #166534;
            border: 1.5px solid #86efac;
        }

        .alert-info {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1.5px solid #fca5a5;
        }

        .nav-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .user-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fff0f5;
            padding: 7px 14px;
            border-radius: 30px;
            border: 1.5px solid var(--border-ui);
            font-size: 13.5px;
            color: #37282c;
            font-weight: 600;
        }
        .role-pill {
            font-size: 11.5px;
            font-weight: 800;
            padding: 3px 8px;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .role-admin { background: #be185d; color: #ffffff; }
        .role-staff { background: #0284c7; color: #ffffff; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            border: none;
            line-height: 1;
            padding: 11px 18px;
            white-space: nowrap;
        }

        .btn-tambah {
            background-color: var(--primary);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(190, 24, 93, 0.35);
        }
        .btn-tambah:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(190, 24, 93, 0.45);
        }

        .btn-transaksi {
            background-color: #059669;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3);
        }
        .btn-transaksi:hover {
            background-color: #047857;
            transform: translateY(-2px);
        }

        .btn-log {
            background-color: #fce7f3;
            color: #9d174d;
            border: 1.5px solid #fbcfe8;
        }
        .btn-log:hover {
            background-color: #fbcfe8;
            color: #831843;
        }

        .btn-keluar {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1.5px solid #fecaca;
        }
        .btn-keluar:hover {
            background-color: #fca5a5;
            color: #7f1d1d;
        }

        /* TABEL INVENTORI */
        .table-responsive {
            overflow-x: auto;
            border-radius: 14px;
            border: 2px solid var(--border-ui);
            background: #ffffff;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background-color: #fff1f2;
            color: var(--primary);
            font-weight: 800;
            font-size: 12.5px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            padding: 15px 16px;
            border-bottom: 2px solid var(--border-ui);
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border-table);
            font-size: 14px;
            vertical-align: middle;
            color: var(--text-main);
        }

        tr:hover td {
            background-color: #fff8f9;
        }

        .text-center { text-align: center; }

        .badge-sku {
            display: inline-block;
            padding: 4px 9px;
            border-radius: 6px;
            font-size: 12px;
            background: #fdf2f8;
            color: #9d174d;
            border: 1.5px solid #fbcfe8;
            font-weight: 800;
            letter-spacing: 0.4px;
        }

        .badge-kategori {
            display: inline-block;
            padding: 4px 11px;
            border-radius: 20px;
            font-size: 12px;
            background: #fce7f3;
            color: #831843;
            border: 1px solid #f472b6;
            font-weight: 700;
        }

        .stok-badge {
            display: inline-block;
            font-weight: 800;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 13px;
        }

        .stok-aman {
            color: #14532d;
            background: #dcfce7;
            border: 1.5px solid #86efac;
        }

        .stok-menipis {
            color: #7f1d1d;
            background: #fee2e2;
            border: 1.5px solid #fca5a5;
        }

        .harga-text {
            font-weight: 800;
            color: #9d174d;
            font-size: 14.5px;
        }

        /* GRID ACTION BUTTONS (2 Kolom Presisi & Rapi) */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
            min-width: 200px;
        }
        .action-grid.staff-view {
            grid-template-columns: 1fr 1fr;
            min-width: 170px;
        }

        .btn-act {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 12.5px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.15s ease-in-out;
            border: 1.5px solid transparent;
            text-align: center;
        }

        .btn-act-in {
            background-color: #dcfce7;
            color: var(--btn-in);
            border-color: #86efac;
        }
        .btn-act-in:hover {
            background-color: var(--btn-in);
            color: #ffffff;
        }

        .btn-act-out {
            background-color: #fee2e2;
            color: var(--btn-out);
            border-color: #fca5a5;
        }
        .btn-act-out:hover {
            background-color: var(--btn-out);
            color: #ffffff;
        }

        .btn-act-edit {
            background-color: #ffedd5;
            color: var(--btn-edit);
            border-color: #fdba74;
        }
        .btn-act-edit:hover {
            background-color: var(--btn-edit);
            color: #ffffff;
        }

        .btn-act-del {
            background-color: #ffe4e6;
            color: var(--btn-del);
            border-color: #fda4af;
        }
        .btn-act-del:hover {
            background-color: var(--btn-del);
            color: #ffffff;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="title">
            <h1>🌸 Inventori toko Hijab</h1>
            <p>Sistem manajemen stok, mutasi masuk/keluar & transaksi</p>
        </div>
        <div class="nav-actions">
            <div class="user-badge">
                <span><strong><?= htmlspecialchars($nama_user); ?></strong></span>
                <span class="role-pill <?= $is_admin ? 'role-admin' : 'role-staff'; ?>">
                    <?= $is_admin ? '👑 Admin' : '🧕 Staff'; ?>
                </span>
            </div>
            <a href="transaksi.php" class="btn btn-transaksi">📦 Mutasi Masuk/Keluar</a>
            <a href="riwayat_transaksi.php" class="btn btn-log">📋 Log Mutasi</a>
            <?php if ($is_admin): ?>
                <a href="tambah.php" class="btn btn-tambah">+ Tambah Produk</a>
            <?php endif; ?>
            <a href="logout.php" class="btn btn-keluar" onclick="return confirm('Apakah Anda yakin ingin logout?')">Keluar</a>
        </div>
    </div>

    <?= $notif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width: 50px;">No</th>
                    <th>Kode SKU</th>
                    <th>Nama Produk Hijab</th>
                    <th>Model / Jenis</th>
                    <th>Bahan</th>
                    <th>Harga Jual</th>
                    <th class="text-center">Stok</th>
                    <th class="text-center" style="width: <?= $is_admin ? '210px' : '180px'; ?>;">Aksi Cepat</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1;
                if (!empty($produk_list)): 
                    foreach ($produk_list as $row): 
                        $stok_class = ($row['stok'] <= 5) ? 'stok-menipis' : 'stok-aman';
                ?>
                    <tr>
                        <td class="text-center" style="color: var(--text-muted); font-weight: 700;"><?= $no++; ?></td>
                        <td><span class="badge-sku"><?= htmlspecialchars($row['sku']); ?></span></td>
                        <td><strong style="color: #1e1b1d; font-size: 14.5px;"><?= htmlspecialchars($row['nama_barang']); ?></strong></td>
                        <td><span class="badge-kategori"><?= htmlspecialchars($row['kategori']); ?></span></td>
                        <td style="color: #4b383c; font-weight: 500;"><?= htmlspecialchars($row['bahan'] ?? '-'); ?></td>
                        <td><span class="harga-text">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></span></td>
                        <td class="text-center">
                            <span class="stok-badge <?= $stok_class; ?>">
                                <?= htmlspecialchars($row['stok']); ?> pcs
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="action-grid <?= $is_admin ? '' : 'staff-view'; ?>">
                                <a href="transaksi.php?id=<?= $row['id']; ?>&jenis=masuk" class="btn-act btn-act-in" title="Catat Stok Masuk">+ Masuk</a>
                                <a href="transaksi.php?id=<?= $row['id']; ?>&jenis=keluar" class="btn-act btn-act-out" title="Catat Stok Keluar">- Keluar</a>
                                <?php if ($is_admin): ?>
                                    <a href="edit.php?id=<?= $row['id']; ?>" class="btn-act btn-act-edit" title="Edit Data Produk">✏️ Edit</a>
                                    <a href="hapus.php?id=<?= $row['id']; ?>" class="btn-act btn-act-del" onclick="return confirm('Yakin ingin menghapus produk <?= htmlspecialchars($row['nama_barang']); ?>?')" title="Hapus Produk">🗑️ Hapus</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php 
                    endforeach;
                else: 
                ?>
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 50px; color: var(--text-muted); font-size: 14.5px;">
                            Belum ada koleksi hijab di inventori.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
