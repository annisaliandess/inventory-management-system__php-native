<?php
// tambah.php
require_once 'cek_login.php';
require_once 'koneksi.php';

// Hak Akses: Hanya Admin yang boleh menambah produk baru
if (($_SESSION['role'] ?? '') !== 'admin') {
    die("<h3>Akses Ditolak: Hanya Admin yang berwenang menambah koleksi produk baru!</h3><p><a href='index.php'>Kembali ke Beranda</a></p>");
}

$pesan_error = "";

if (isset($_POST['simpan'])) {
    $sku         = trim($_POST['sku'] ?? '');
    $nama_barang = trim($_POST['nama_barang'] ?? '');
    $kategori    = trim($_POST['kategori'] ?? '');
    $bahan       = trim($_POST['bahan'] ?? '');
    $harga       = floatval($_POST['harga'] ?? 0);
    $stok        = intval($_POST['stok'] ?? 0);

    if (empty($sku) || empty($nama_barang) || empty($kategori) || $harga <= 0) {
        $pesan_error = "Harap isi semua kolom wajib (*) dengan benar!";
    } elseif (empty($pesan_error)) {
        try {
            $stmtCek = $pdo->prepare("SELECT id FROM produk WHERE sku = :sku LIMIT 1");
            $stmtCek->execute([':sku' => $sku]);
            if ($stmtCek->fetch()) {
                $pesan_error = "Kode SKU sudah terdaftar! Gunakan SKU lain.";
            } else {
                $query = "INSERT INTO produk (sku, nama_barang, kategori, bahan, harga, stok) 
                          VALUES (:sku, :nama_barang, :kategori, :bahan, :harga, :stok)";
                $stmt = $pdo->prepare($query);
                $stmt->execute([
                    ':sku'         => $sku,
                    ':nama_barang' => $nama_barang,
                    ':kategori'    => $kategori,
                    ':bahan'       => $bahan,
                    ':harga'       => $harga,
                    ':stok'        => $stok
                ]);

                header("Location: index.php?status=sukses_tambah");
                exit;
            }
        } catch (PDOException $e) {
            $pesan_error = "Gagal menyimpan data: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Koleksi Hijab - Toko Hijab</title>
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
            max-width: 640px;
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
        .alert { background: #fef2f2; color: #991b1b; border: 1.5px solid #f87171; padding: 13px 16px; border-radius: 10px; margin-bottom: 22px; font-size: 14px; font-weight: 700; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 700; font-size: 13.5px; color: #2e1c21; }
        input[type="text"], input[type="number"], select {
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
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(190, 24, 93, 0.15);
            background-color: #ffffff;
        }
        .row { display: flex; gap: 16px; }
        .col { flex: 1; }
        .btn-group { display: flex; justify-content: flex-end; gap: 12px; margin-top: 30px; }
        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-batal { background-color: #f1e5e8; color: #4a343a; border: 1px solid #dfced3; }
        .btn-batal:hover { background-color: #e5d4d9; color: #1e1b1d; }
        .btn-simpan { background-color: var(--primary); color: #ffffff; box-shadow: 0 6px 18px rgba(190, 24, 93, 0.35); }
        .btn-simpan:hover { background-color: var(--primary-hover); transform: translateY(-2px); box-shadow: 0 8px 22px rgba(190, 24, 93, 0.45); }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>✨ Tambah Koleksi Hijab Baru</h1>
        <p>Masukkan rincian informasi dan stok kerudung ke inventori toko hijab</p>
    </div>

    <?php if (!empty($pesan_error)): ?>
        <div class="alert">⚠️ <?= htmlspecialchars($pesan_error); ?></div>
    <?php endif; ?>

    <form action="tambah.php" method="POST">
        <div class="form-group">
            <label for="sku">Kode Produk / SKU *</label>
            <input type="text" id="sku" name="sku" placeholder="Contoh: KRD-005" required autocomplete="off">
        </div>

        <div class="form-group">
            <label for="nama_barang">Nama Produk Hijab *</label>
            <input type="text" id="nama_barang" name="nama_barang" placeholder="Contoh: Hijab Bella Square Dark Grey" required>
        </div>

        <div class="row">
            <div class="col form-group">
                <label for="kategori">Model / Jenis *</label>
                <select id="kategori" name="kategori" required>
                    <option value="">-- Pilih Model --</option>
                    <option value="Pashmina">Pashmina</option>
                    <option value="Segi Empat">Segi Empat</option>
                    <option value="Instan / Bergo">Instan / Bergo</option>
                    <option value="Khimar / Syar'i">Khimar / Syar'i</option>
                    <option value="Aksesoris Hijab">Aksesoris Hijab</option>
                </select>
            </div>
            <div class="col form-group">
                <label for="bahan">Bahan / Material</label>
                <input type="text" id="bahan" name="bahan" placeholder="Contoh: Double Hycon / Voal">
            </div>
        </div>

        <div class="row">
            <div class="col form-group">
                <label for="harga">Harga (Rp) *</label>
                <input type="number" id="harga" name="harga" placeholder="35000" min="0" required>
            </div>
            <div class="col form-group">
                <label for="stok">Jumlah Stok Awal (Pcs) *</label>
                <input type="number" id="stok" name="stok" placeholder="10" min="0" required>
            </div>
        </div>

        <div class="btn-group">
            <a href="index.php" class="btn btn-batal">Batal</a>
            <button type="submit" name="simpan" class="btn btn-simpan">Simpan Produk</button>
        </div>
    </form>
</div>

</body>
</html>
