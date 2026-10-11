<?php
// riwayat_transaksi.php
require_once 'cek_login.php';
require_once 'koneksi.php';

try {
    $sql = "SELECT t.*, p.nama_barang, p.sku 
            FROM transaksi_stok t
            JOIN produk p ON t.produk_id = p.id
            ORDER BY t.id DESC";
    $stmt = $pdo->query($sql);
    $riwayat = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Gagal mengambil data riwayat: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Barang Masuk & Keluar - Toko Hijab</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #fff1f2;
            --card-bg: #ffffff;
            --primary: #be185d;
            --primary-hover: #9d174d;
            --text-main: #1e1b1d;
            --text-muted: #5e4d51;
            --border-ui: #fbcfe8;
            --border-table: #fce7f3;
        }

        * { box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { 
            background: linear-gradient(135deg, #fff1f2 0%, #fce7f3 50%, #fbcfe8 100%);
            margin: 0; 
            padding: 36px 20px; 
            color: var(--text-main); 
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--card-bg);
            padding: 34px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(190, 24, 93, 0.12), 0 5px 15px rgba(0, 0, 0, 0.04);
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
        .title h1 { margin: 0; font-size: 23px; font-weight: 800; color: var(--primary); letter-spacing: -0.4px; }
        .title p { margin: 6px 0 0 0; color: var(--text-muted); font-size: 14px; font-weight: 500; }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 800;
            padding: 11px 20px;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-kembali { background-color: #f1e5e8; color: #4a343a; border: 1px solid #dfced3; }
        .btn-kembali:hover { background-color: #e5d4d9; color: #1e1b1d; }
        .btn-transaksi { background-color: var(--primary); color: white; box-shadow: 0 4px 14px rgba(190, 24, 93, 0.35); }
        .btn-transaksi:hover { background-color: var(--primary-hover); transform: translateY(-2px); }

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
        tr:hover td { background-color: #fff8f9; }
        .text-center { text-align: center; }

        .badge-tipe {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
        }
        .badge-masuk {
            background-color: #dcfce7;
            color: #15803d;
            border: 1.5px solid #86efac;
        }
        .badge-keluar {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1.5px solid #fca5a5;
        }
        .badge-sku {
            background: #fdf2f8;
            color: #9d174d;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 800;
            border: 1.5px solid #fbcfe8;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="title">
            <h1>📋 Riwayat Transaksi Mutasi Stok</h1>
            <p>Log mutasi stok barang masuk & keluar yang dicatat secara real-time</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="index.php" class="btn btn-kembali">← Kembali ke Beranda</a>
            <a href="transaksi.php" class="btn btn-transaksi">+ Catat Mutasi Baru</a>
        </div>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="text-center" style="width: 50px;">No</th>
                    <th>Waktu & Tanggal</th>
                    <th>SKU & Produk</th>
                    <th class="text-center">Tipe Mutasi</th>
                    <th class="text-center">Jumlah</th>
                    <th class="text-center">Stok Awal → Akhir</th>
                    <th>Catatan Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($riwayat)): ?>
                    <?php $no = 1; foreach ($riwayat as $row): ?>
                        <tr>
                            <td class="text-center" style="color: var(--text-muted); font-weight: 700;"><?= $no++; ?></td>
                            <td style="color: #4b363c; font-size: 13.5px; font-weight: 600;">
                                <?= date('d M Y, H:i', strtotime($row['tanggal'])); ?>
                            </td>
                            <td>
                                <span class="badge-sku"><?= htmlspecialchars($row['sku']); ?></span>
                                <strong style="margin-left: 8px; color: #1e1b1d;"><?= htmlspecialchars($row['nama_barang']); ?></strong>
                            </td>
                            <td class="text-center">
                                <?php if ($row['jenis'] === 'masuk'): ?>
                                    <span class="badge-tipe badge-masuk">🟢 Masuk</span>
                                <?php else: ?>
                                    <span class="badge-tipe badge-keluar">🔴 Keluar</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center" style="font-weight: 800; font-size: 14.5px; color: <?= ($row['jenis'] === 'masuk') ? '#15803d' : '#b91c1c'; ?>;">
                                <?= ($row['jenis'] === 'masuk') ? '+' : '-'; ?><?= $row['jumlah']; ?> pcs
                            </td>
                            <td class="text-center">
                                <span style="color: #5e4d51; font-weight: 600;"><?= $row['stok_sebelum']; ?> pcs</span>
                                <span style="color: #9d174d; margin: 0 4px; font-weight: 800;">&rarr;</span>
                                <strong style="color: var(--primary); font-size: 14px;"><?= $row['stok_sesudah']; ?> pcs</strong>
                            </td>
                            <td style="color: #4b383c; font-size: 13.5px; font-weight: 500;">
                                <?= !empty($row['keterangan']) ? htmlspecialchars($row['keterangan']) : '-'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 45px; color: var(--text-muted); font-size: 14.5px;">
                            Belum ada riwayat mutasi barang masuk atau keluar.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
