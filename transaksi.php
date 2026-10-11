<?php
// transaksi.php
require_once 'cek_login.php';
require_once 'koneksi.php';

$pesan_error = "";
$pesan_sukses = "";

// Ambil produk spesifik jika ada param ?id=
$selected_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$default_jenis = isset($_GET['jenis']) && in_array($_GET['jenis'], ['masuk', 'keluar']) ? $_GET['jenis'] : 'masuk';

// Ambil daftar seluruh produk untuk pilihan dropdown
try {
    $stmtProduk = $pdo->query("SELECT id, sku, nama_barang, stok FROM produk ORDER BY nama_barang ASC");
    $list_produk = $stmtProduk->fetchAll();
} catch (PDOException $e) {
    die("Gagal memuat produk: " . $e->getMessage());
}

if (isset($_POST['simpan_transaksi'])) {
    $produk_id  = intval($_POST['produk_id'] ?? 0);
    $jenis      = trim($_POST['jenis'] ?? '');
    $jumlah     = intval($_POST['jumlah'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($produk_id <= 0 || !in_array($jenis, ['masuk', 'keluar']) || $jumlah <= 0) {
        $pesan_error = "Harap isi produk, tipe transaksi, dan kuantitas (minimal 1) dengan benar!";
    } else {
        try {
            $pdo->beginTransaction();

            $stmtCek = $pdo->prepare("SELECT id, nama_barang, stok FROM produk WHERE id = :id FOR UPDATE");
            $stmtCek->execute([':id' => $produk_id]);
            $item = $stmtCek->fetch();

            if (!$item) {
                throw new Exception("Produk tidak ditemukan di database!");
            }

            $stok_sebelum = intval($item['stok']);

            if ($jenis === 'masuk') {
                $stok_sesudah = $stok_sebelum + $jumlah;
            } else {
                if ($stok_sebelum < $jumlah) {
                    throw new Exception("Stok tidak mencukupi! Stok saat ini: {$stok_sebelum} pcs, diminta keluar: {$jumlah} pcs.");
                }
                $stok_sesudah = $stok_sebelum - $jumlah;
            }

            $stmtUpdate = $pdo->prepare("UPDATE produk SET stok = :stok WHERE id = :id");
            $stmtUpdate->execute([
                ':stok' => $stok_sesudah,
                ':id'   => $produk_id
            ]);

            $stmtLog = $pdo->prepare("INSERT INTO transaksi_stok (produk_id, jenis, jumlah, stok_sebelum, stok_sesudah, keterangan) 
                                      VALUES (:produk_id, :jenis, :jumlah, :stok_sebelum, :stok_sesudah, :keterangan)");
            $stmtLog->execute([
                ':produk_id'    => $produk_id,
                ':jenis'        => $jenis,
                ':jumlah'       => $jumlah,
                ':stok_sebelum' => $stok_sebelum,
                ':stok_sesudah' => $stok_sesudah,
                ':keterangan'   => $keterangan
            ]);

            $pdo->commit();

            $jenis_teks = ($jenis === 'masuk') ? 'penambahan (masuk)' : 'pengurangan (keluar)';
            $pesan_sukses = "✅ Berhasil mencatat mutasi barang {$jenis_teks} untuk \"{$item['nama_barang']}\". Stok sekarang: {$stok_sesudah} pcs.";
            
            $stmtProduk = $pdo->query("SELECT id, sku, nama_barang, stok FROM produk ORDER BY nama_barang ASC");
            $list_produk = $stmtProduk->fetchAll();

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $pesan_error = "Transaksi Gagal: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Barang Masuk / Keluar - Sistem Inventori Hijab</title>
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
            --input-border: #e7cfd4;
            --masuk-color: #15803d;
            --keluar-color: #b91c1c;
        }

        * { box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { 
            background: linear-gradient(135deg, #fff1f2 0%, #fce7f3 50%, #fbcfe8 100%);
            margin: 0; 
            padding: 40px 20px; 
            color: var(--text-main); 
            min-height: 100vh;
        }
        .container {
            max-width: 660px;
            margin: 0 auto;
            background: var(--card-bg);
            padding: 38px 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(190, 24, 93, 0.12), 0 5px 15px rgba(0, 0, 0, 0.04);
            border: 2px solid var(--border-ui);
        }
        .header { margin-bottom: 26px; border-bottom: 2px solid var(--border-ui); padding-bottom: 18px; }
        h1 { margin: 0; font-size: 23px; color: var(--primary); font-weight: 800; letter-spacing: -0.4px; }
        p { margin: 6px 0 0 0; color: var(--text-muted); font-size: 14px; font-weight: 500; }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1.5px solid #f87171; padding: 13px 16px; border-radius: 10px; margin-bottom: 22px; font-size: 14px; font-weight: 700; }
        .alert-sukses { background: #f0fdf4; color: #166534; border: 1.5px solid #86efac; padding: 13px 16px; border-radius: 10px; margin-bottom: 22px; font-size: 14px; font-weight: 700; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 700; font-size: 13.5px; color: #2e1c21; }
        input[type="number"], select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid var(--input-border);
            border-radius: 10px;
            font-size: 14.5px;
            color: #1e1b1d;
            background-color: #fffbfc;
            transition: all 0.2s;
            font-weight: 600;
        }
        textarea { resize: vertical; min-height: 80px; }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(190, 24, 93, 0.15);
            background-color: #ffffff;
        }
        .radio-pilihan {
            display: flex;
            gap: 16px;
        }
        .radio-card {
            flex: 1;
            border: 2px solid var(--border-ui);
            border-radius: 12px;
            padding: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 800;
            background: #fffbfc;
            transition: all 0.2s;
            color: #4b363c;
        }
        .radio-card:hover {
            border-color: var(--primary);
        }
        .radio-card.active-masuk {
            border-color: #16a34a;
            background: #f0fdf4;
            color: #15803d;
        }
        .radio-card.active-keluar {
            border-color: #dc2626;
            background: #fef2f2;
            color: #b91c1c;
        }
        .btn-group { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; }
        .btn {
            padding: 12px 22px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-batal { background-color: #f1e5e8; color: #4a343a; border: 1px solid #dfced3; }
        .btn-batal:hover { background-color: #e5d4d9; color: #1e1b1d; }
        .btn-riwayat { background-color: #fce7f3; color: #9d174d; border: 1.5px solid #fbcfe8; }
        .btn-riwayat:hover { background-color: #fbcfe8; color: #831843; }
        .btn-simpan { background-color: var(--primary); color: white; box-shadow: 0 6px 18px rgba(190, 24, 93, 0.35); }
        .btn-simpan:hover { background-color: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 8px 22px rgba(190, 24, 93, 0.45); }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>📦 Pencatatan Barang Masuk / Keluar</h1>
        <p>Gunakan transaksi database agar stok barang selalu sinkron dan riwayat mutasi tercatat</p>
    </div>

    <?php if (!empty($pesan_error)): ?>
        <div class="alert-error">⚠️ <?= htmlspecialchars($pesan_error); ?></div>
    <?php endif; ?>

    <?php if (!empty($pesan_sukses)): ?>
        <div class="alert-sukses"><?= $pesan_sukses; ?></div>
    <?php endif; ?>

    <form action="transaksi.php" method="POST">
        <div class="form-group">
            <label for="produk_id">Pilih Produk Hijab *</label>
            <select id="produk_id" name="produk_id" required>
                <option value="">-- Pilih Produk --</option>
                <?php foreach ($list_produk as $item): ?>
                    <option value="<?= $item['id']; ?>" <?= ($selected_id == $item['id']) ? 'selected' : ''; ?>>
                        [<?= htmlspecialchars($item['sku']); ?>] <?= htmlspecialchars($item['nama_barang']); ?> (Stok saat ini: <?= $item['stok']; ?> pcs)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Tipe Mutasi Transaksi *</label>
            <div class="radio-pilihan">
                <label class="radio-card <?= ($default_jenis === 'masuk') ? 'active-masuk' : ''; ?>" id="card-masuk">
                    <input type="radio" name="jenis" value="masuk" <?= ($default_jenis === 'masuk') ? 'checked' : ''; ?> onchange="updateStyle()">
                    🟢 Barang Masuk (+ Restock)
                </label>
                <label class="radio-card <?= ($default_jenis === 'keluar') ? 'active-keluar' : ''; ?>" id="card-keluar">
                    <input type="radio" name="jenis" value="keluar" <?= ($default_jenis === 'keluar') ? 'checked' : ''; ?> onchange="updateStyle()">
                    🔴 Barang Keluar (- Penjualan)
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="jumlah">Jumlah Pcs *</label>
            <input type="number" id="jumlah" name="jumlah" placeholder="Contoh: 10" min="1" required>
        </div>

        <div class="form-group">
            <label for="keterangan">Keterangan / Catatan Transaksi</label>
            <textarea id="keterangan" name="keterangan" placeholder="Contoh: Restock dari konveksi Bandung atau Penjualan invoice #INV-102"></textarea>
        </div>

        <div class="btn-group">
            <div style="display: flex; gap: 10px;">
                <a href="index.php" class="btn btn-batal">← Beranda</a>
                <a href="riwayat_transaksi.php" class="btn btn-riwayat">📋 Log Mutasi</a>
            </div>
            <button type="submit" name="simpan_transaksi" class="btn btn-simpan">Eksekusi Transaksi</button>
        </div>
    </form>
</div>

<script>
function updateStyle() {
    const radioMasuk = document.querySelector('input[name="jenis"][value="masuk"]');
    const cardMasuk = document.getElementById('card-masuk');
    const cardKeluar = document.getElementById('card-keluar');

    if (radioMasuk.checked) {
        cardMasuk.className = 'radio-card active-masuk';
        cardKeluar.className = 'radio-card';
    } else {
        cardMasuk.className = 'radio-card';
        cardKeluar.className = 'radio-card active-keluar';
    }
}
</script>

</body>
</html>
