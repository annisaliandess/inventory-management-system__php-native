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

// Fitur Pencarian & Filter Produk
$keyword  = trim($_GET['q'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');

// Ambil list semua kategori untuk dropdown filter
try {
    $stmt_kat = $pdo->query("SELECT DISTINCT kategori FROM produk WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori ASC");
    $kategori_list = $stmt_kat->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $kategori_list = [];
}

// Statistik Ringkasan untuk Dashboard Admin & Staf
try {
    $stat_total_produk = $pdo->query("SELECT COUNT(*) FROM produk")->fetchColumn() ?: 0;
    $stat_total_stok   = $pdo->query("SELECT COALESCE(SUM(stok), 0) FROM produk")->fetchColumn() ?: 0;
    $stat_stok_menipis = $pdo->query("SELECT COUNT(*) FROM produk WHERE stok <= 5")->fetchColumn() ?: 0;
    
    // Metrik khusus Admin: Estimasi nilai inventori & total mutasi hari ini
    $stat_nilai_aset   = $pdo->query("SELECT COALESCE(SUM(stok * harga), 0) FROM produk")->fetchColumn() ?: 0;
    
    // Metrik operasional untuk Staf & Admin: Mutasi hari ini
    $stmt_mutasi_today = $pdo->query("
        SELECT 
            COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE 0 END), 0) as total_masuk,
            COALESCE(SUM(CASE WHEN jenis = 'keluar' THEN jumlah ELSE 0 END), 0) as total_keluar
        FROM transaksi_stok 
        WHERE DATE(tanggal) = CURRENT_DATE()
    ");
    $mutasi_today = $stmt_mutasi_today->fetch(PDO::FETCH_ASSOC) ?: ['total_masuk' => 0, 'total_keluar' => 0];
} catch (PDOException $e) {
    $stat_total_produk = 0;
    $stat_total_stok   = 0;
    $stat_stok_menipis = 0;
    $stat_nilai_aset   = 0;
    $mutasi_today      = ['total_masuk' => 0, 'total_keluar' => 0];
}

// Query Produk dengan Pencarian & Filter Aman (PDO Prepared Statements)
try {
    $sql_where = [];
    $params = [];

    if (!empty($keyword)) {
        $sql_where[] = "(sku LIKE :kw1 OR nama_barang LIKE :kw2 OR bahan LIKE :kw3)";
        $kw_term = "%" . $keyword . "%";
        $params[':kw1'] = $kw_term;
        $params[':kw2'] = $kw_term;
        $params[':kw3'] = $kw_term;
    }

    if (!empty($kategori)) {
        $sql_where[] = "kategori = :kat";
        $params[':kat'] = $kategori;
    }

    $sql = "SELECT * FROM produk";
    if (!empty($sql_where)) {
        $sql .= " WHERE " . implode(" AND ", $sql_where);
    }
    $sql .= " ORDER BY id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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

        /* DASHBOARD SUMMARY CARDS */
        .dashboard-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
            border: 1.5px solid var(--border-ui);
            padding: 16px 22px;
            border-radius: 14px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .banner-role-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .banner-icon {
            font-size: 32px;
            line-height: 1;
        }
        .banner-text h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 800;
            color: var(--primary);
        }
        .banner-text p {
            margin: 3px 0 0 0;
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
        }
        .banner-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12.5px;
            font-weight: 700;
            background: #ffffff;
            border: 1.5px solid var(--border-ui);
            color: var(--primary);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #ffffff;
            border: 1.5px solid var(--border-ui);
            border-radius: 14px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 4px 12px rgba(190, 24, 93, 0.04);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(190, 24, 93, 0.08);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .stat-icon.pink { background: #fdf2f8; color: #be185d; border: 1.5px solid #fbcfe8; }
        .stat-icon.blue { background: #f0f9ff; color: #0284c7; border: 1.5px solid #bae6fd; }
        .stat-icon.amber { background: #fffbeb; color: #b45309; border: 1.5px solid #fde68a; }
        .stat-icon.emerald { background: #ecfdf5; color: #047857; border: 1.5px solid #a7f3d0; }
        .stat-icon.rose { background: #fff1f2; color: #e11d48; border: 1.5px solid #fecdd3; }

        .stat-details {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .stat-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .stat-value {
            font-size: 20px;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .stat-sub {
            font-size: 11.5px;
            font-weight: 600;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* PENCARIAN & FILTER */
        .search-filter-card {
            background: #ffffff;
            border: 1.5px solid var(--border-ui);
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 22px;
            box-shadow: 0 4px 12px rgba(190, 24, 93, 0.03);
        }
        .search-filter-form {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        .search-input-wrap {
            position: relative;
            flex: 1 1 280px;
        }
        .search-input-wrap input {
            width: 100%;
            padding: 11px 16px 11px 40px;
            border: 1.5px solid #fbcfe8;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-main);
            background: #fff8f9;
            outline: none;
            transition: all 0.2s ease;
        }
        .search-input-wrap input:focus {
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(190, 24, 93, 0.15);
        }
        .search-input-wrap .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            color: var(--text-muted);
            pointer-events: none;
        }
        .filter-select {
            flex: 0 1 200px;
            padding: 11px 16px;
            border: 1.5px solid #fbcfe8;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--text-main);
            background: #fff8f9;
            outline: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .filter-select:focus {
            border-color: var(--primary);
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(190, 24, 93, 0.15);
        }
        .btn-search {
            background-color: var(--primary);
            color: #ffffff;
            padding: 11px 20px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13.5px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .btn-search:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }
        .btn-reset {
            background-color: #f1f5f9;
            color: #475569;
            border: 1.5px solid #cbd5e1;
            padding: 11px 16px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13.5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .btn-reset:hover {
            background-color: #e2e8f0;
            color: #1e293b;
        }
        .search-info-bar {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed var(--border-ui);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12.5px;
            color: var(--text-muted);
            font-weight: 600;
            flex-wrap: wrap;
            gap: 8px;
        }
        .search-info-bar strong {
            color: var(--primary);
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

    <!-- BANNER PERAN PENGGUNA (DASHBOARD ADMIN / STAF) -->
    <div class="dashboard-banner">
        <div class="banner-role-info">
            <div class="banner-icon"><?= $is_admin ? '👑' : '🧕'; ?></div>
            <div class="banner-text">
                <h3>Dashboard <?= $is_admin ? 'Administrator Inventori' : 'Staf Gudang & Kasir'; ?></h3>
                <p><?= $is_admin ? 'Selamat datang! Anda memiliki akses penuh ke manajemen produk, mutasi stok, laporan finansial, dan audit trail.' : 'Selamat bertugas! Fokus pada pencatatan barang masuk, barang keluar, dan monitoring stok produk.'; ?></p>
            </div>
        </div>
        <div class="banner-badge">
            <span>Mode Aktif:</span>
            <strong><?= $is_admin ? 'Full Access (Admin)' : 'Operational Only (Staff)'; ?></strong>
        </div>
    </div>

    <!-- STATISTIK WIDGET SESUAI PERAN (ADMIN & STAF) -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon pink">📦</div>
            <div class="stat-details">
                <span class="stat-label">Total Model / SKU</span>
                <span class="stat-value"><?= number_format($stat_total_produk); ?> <small style="font-size: 13px; font-weight: normal;">Item</small></span>
                <span class="stat-sub">Katalog hijab aktif</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon blue">🏷️</div>
            <div class="stat-details">
                <span class="stat-label">Total Stok Fisik</span>
                <span class="stat-value"><?= number_format($stat_total_stok); ?> <small style="font-size: 13px; font-weight: normal;">Pcs</small></span>
                <span class="stat-sub">Stok siap jual di toko</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon <?= ($stat_stok_menipis > 0) ? 'rose' : 'emerald'; ?>">
                <?= ($stat_stok_menipis > 0) ? '⚠️' : '✅'; ?>
            </div>
            <div class="stat-details">
                <span class="stat-label">Stok Menipis (≤ 5)</span>
                <span class="stat-value" style="<?= ($stat_stok_menipis > 0) ? 'color: #e11d48;' : ''; ?>">
                    <?= number_format($stat_stok_menipis); ?> <small style="font-size: 13px; font-weight: normal;">Item</small>
                </span>
                <span class="stat-sub"><?= ($stat_stok_menipis > 0) ? 'Segera lakukan restock' : 'Semua stok dalam batas aman'; ?></span>
            </div>
        </div>

        <?php if ($is_admin): ?>
            <!-- Metrik Khusus Administrator: Estimasi Nilai Aset -->
            <div class="stat-card">
                <div class="stat-icon amber">💰</div>
                <div class="stat-details">
                    <span class="stat-label">Estimasi Nilai Aset</span>
                    <span class="stat-value">Rp <?= number_format($stat_nilai_aset, 0, ',', '.'); ?></span>
                    <span class="stat-sub">Total modal & nilai jual stok</span>
                </div>
            </div>
        <?php else: ?>
            <!-- Metrik Khusus Staf Operasional: Mutasi Hari Ini -->
            <div class="stat-card">
                <div class="stat-icon emerald">🔄</div>
                <div class="stat-details">
                    <span class="stat-label">Aktivitas Hari Ini</span>
                    <span class="stat-value">
                        <span style="color: #15803d; font-size: 17px;">+<?= $mutasi_today['total_masuk']; ?></span> / 
                        <span style="color: #b91c1c; font-size: 17px;">-<?= $mutasi_today['total_keluar']; ?></span>
                    </span>
                    <span class="stat-sub">Masuk & Keluar hari ini</span>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- FORM PENCARIAN & FILTER PRODUK -->
    <div class="search-filter-card">
        <form method="GET" action="index.php" class="search-filter-form">
            <div class="search-input-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" name="q" placeholder="Cari nama kerudung, kode SKU, atau jenis bahan..." value="<?= htmlspecialchars($keyword); ?>" autofocus>
            </div>

            <select name="kategori" class="filter-select">
                <option value="">-- Semua Kategori --</option>
                <?php foreach ($kategori_list as $kat): ?>
                    <option value="<?= htmlspecialchars($kat); ?>" <?= ($kategori === $kat) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($kat); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-search">
                <span>Cari Produk</span>
            </button>

            <?php if (!empty($keyword) || !empty($kategori)): ?>
                <a href="index.php" class="btn-reset" title="Hapus filter pencarian">✕ Reset</a>
            <?php endif; ?>
        </form>

        <?php if (!empty($keyword) || !empty($kategori)): ?>
            <div class="search-info-bar">
                <div>
                    Menampilkan hasil pencarian
                    <?php if (!empty($keyword)): ?>
                        kata kunci: <strong>"<?= htmlspecialchars($keyword); ?>"</strong>
                    <?php endif; ?>
                    <?php if (!empty($kategori)): ?>
                        kategori: <strong>"<?= htmlspecialchars($kategori); ?>"</strong>
                    <?php endif; ?>
                    (Ditemukan <strong><?= count($produk_list); ?></strong> produk)
                </div>
                <div>
                    <a href="index.php" style="color: var(--primary); text-decoration: none; font-weight: 700;">Tampilkan Semua Produk &rarr;</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

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
                        <td colspan="8" class="text-center" style="padding: 45px 20px; color: var(--text-muted); font-size: 14.5px;">
                            <?php if (!empty($keyword) || !empty($kategori)): ?>
                                🔍 Tidak ditemukan produk hijab yang sesuai dengan kriteria pencarian.<br>
                                <a href="index.php" style="display: inline-block; margin-top: 10px; color: var(--primary); font-weight: 700; text-decoration: none;">Klik di sini untuk mereset pencarian</a>
                            <?php else: ?>
                                🌸 Belum ada koleksi hijab di inventori.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
