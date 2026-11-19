
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Jika belum login, arahkan ke halaman login
if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

// Jika bukan admin, tolak akses
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Akses ditolak. Halaman ini hanya untuk admin.');
}

require_once __DIR__ . '/../koneksi.php';

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function admin_header($title) {
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> - PasarKita Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f7f4ef;
        }

        .sidebar {
            background: #713f20;
            min-height: 100vh;
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            display: block;
            padding: 11px 14px;
            border-radius: 8px;
            margin-bottom: 5px;
        }

        .sidebar a:hover {
            background: #965b32;
        }

        .brand {
            color: white;
            font-size: 25px;
            font-weight: bold;
        }

        .content-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 3px 15px #0000000a;
        }
    </style>
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <aside class="col-md-2 sidebar p-3">

            <div class="brand mb-4">
                PasarKita
            </div>

            <a href="index.php">📊 Dashboard</a>
            <a href="produk.php">📦 Kelola Produk</a>
            <a href="kategori.php">🗂️ Kelola Kategori</a>
            <a href="pesanan.php">🧾 Kelola Pesanan</a>
            <a href="pengguna.php">👥 Data Pengguna</a>

            <hr class="border-light">

            <a href="../index.php">🏠 Lihat Website</a>

            <a href="../login.php">🔑 Halaman Login</a>

            <a href="../logout.php"
               class="bg-danger mt-2">
                🚪 Logout
            </a>

        </aside>

        <!-- KONTEN -->
        <main class="col-md-10 p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>
                    <h3 class="mb-0">
                        <?= h($title) ?>
                    </h3>
                    <small class="text-muted">
                        Panel Administrasi PasarKita
                    </small>
                </div>

                <div class="d-flex align-items-center gap-2">

                    <span class="badge bg-light text-dark p-2">
                        Admin: <?= h($_SESSION['nama'] ?? 'Administrator') ?>
                    </span>

                    <a href="../logout.php"
                       class="btn btn-danger">
                        Logout
                    </a>

                </div>

            </div>

<?php
}

function admin_footer() {
?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
<?php
}
?>
