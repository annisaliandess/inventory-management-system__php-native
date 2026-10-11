<?php
// login.php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once 'koneksi.php';

$error = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();

            if ($user) {
                if ($password === $user['password'] || password_verify($password, $user['password'])) {
                    $_SESSION['user_id']      = $user['id'];
                    $_SESSION['username']     = $user['username'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    $_SESSION['role']         = $user['role'] ?? 'staff';

                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Password yang Anda masukkan salah!";
                }
            } else {
                $error = "Username tidak terdaftar!";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Inventori Toko Hijab</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #fff1f2;         /* Soft Pastel Rose Pink */
            --card-bg: #ffffff;
            --primary: #be185d;         /* Deep Rose Pink - Kontras Tinggi */
            --primary-hover: #9d174d;
            --text-main: #1e1b1d;       /* Hitam Pekat HD */
            --text-muted: #645558;
            --border-ui: #fbcfe8;       /* Pink Rose Border */
            --accent-soft: #fdf2f8;
        }

        * {
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #fff1f2 0%, #fce7f3 50%, #fbcfe8 100%);
            margin: 0;
            padding: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: var(--text-main);
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--card-bg);
            padding: 40px 36px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(190, 24, 93, 0.12), 0 5px 15px rgba(0, 0, 0, 0.04);
            border: 2px solid var(--border-ui);
        }

        .brand-header {
            text-align: center;
            margin-bottom: 28px;
        }

        .brand-header .icon {
            font-size: 42px;
            margin-bottom: 10px;
            display: inline-block;
        }

        .brand-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.4px;
        }

        .brand-header p {
            margin: 6px 0 0 0;
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
        }

        .alert-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1.5px solid #f87171;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 22px;
            font-size: 13.5px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            font-size: 13.5px;
            color: #37282c;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 13px 15px;
            border: 1.5px solid #e7cfd4;
            border-radius: 10px;
            font-size: 14.5px;
            color: #1e1b1d;
            background-color: #fffbfc;
            transition: all 0.2s;
            font-weight: 600;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(190, 24, 93, 0.15);
            background-color: #ffffff;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14.5px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s;
            margin-top: 10px;
            box-shadow: 0 6px 18px rgba(190, 24, 93, 0.35);
        }

        .btn-login:hover {
            background-color: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(190, 24, 93, 0.45);
        }

        .hint-box {
            margin-top: 26px;
            padding: 14px;
            background: var(--accent-soft);
            border: 1.5px dashed #f472b6;
            border-radius: 12px;
            font-size: 13px;
            color: #4b363c;
            line-height: 1.6;
        }
        .hint-title {
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 4px;
            text-align: center;
        }
        .hint-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 6px;
            font-size: 12.5px;
        }
        .hint-item {
            background: #ffffff;
            padding: 6px 8px;
            border-radius: 6px;
            border: 1px solid #fbcfe8;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-header">
        <div class="icon">🌸</div>
        <h1>Toko Hijab</h1>
        <p>Masuk ke Sistem Inventori & Manajemen Stok</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert-error">
            ⚠️ <?= htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" placeholder="Masukkan username" required autocomplete="off">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Masukkan password" required>
        </div>

        <button type="submit" name="login" class="btn-login">Masuk ke Sistem</button>
    </form>

</body>
</html>
