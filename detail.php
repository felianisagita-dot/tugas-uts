
<?php
session_start();
require_once "koneksi.php";

// Pastikan ID produk tersedia
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID produk tidak valid.");
}

$id = (int) $_GET['id'];

// Ambil data produk dari database
$query = mysqli_prepare(
    $koneksi,
    "SELECT produk.*, kategori.nama_kategori
     FROM produk
     LEFT JOIN kategori
     ON produk.id_kategori = kategori.id_kategori
     WHERE produk.id_produk = ?"
);

mysqli_stmt_bind_param($query, "i", $id);
mysqli_stmt_execute($query);

$hasil = mysqli_stmt_get_result($query);
$produk = mysqli_fetch_assoc($hasil);

if (!$produk) {
    die("Produk tidak ditemukan.");
}

$pesan = "";

// Proses tambah ke keranjang
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $jumlah = isset($_POST['jumlah'])
        ? (int) $_POST['jumlah']
        : 0;

    if ($jumlah < 1) {
        $pesan = "Jumlah produk minimal 1.";

    } elseif ($jumlah > (int) $produk['stok']) {
        $pesan = "Stok produk tidak mencukupi.";

    } else {

        if (!isset($_SESSION['keranjang'])) {
            $_SESSION['keranjang'] = [];
        }

        $jumlah_lama = isset($_SESSION['keranjang'][$id])
            ? (int) $_SESSION['keranjang'][$id]
            : 0;

        $jumlah_baru = $jumlah_lama + $jumlah;

        if ($jumlah_baru > (int) $produk['stok']) {
            $pesan = "Jumlah melebihi stok yang tersedia.";

        } else {
            $_SESSION['keranjang'][$id] = $jumlah_baru;

            header("Location: keranjang.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detail Produk - PasarKita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f3e9;
            font-family: Arial, sans-serif;
        }

        .navbar {
            background-color: #6b4226;
        }

        .navbar-brand {
            color: white;
            font-weight: bold;
        }

        .detail-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-top: 40px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .gambar-produk {
            width: 100%;
            height: 350px;
            object-fit: cover;
            border-radius: 12px;
        }

        .harga {
            color: #8b4513;
            font-size: 25px;
            font-weight: bold;
        }

        .btn-keranjang {
            background-color: #8b5e3c;
            color: white;
            border: none;
        }

        .btn-keranjang:hover {
            background-color: #6b4226;
            color: white;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            PasarKita
        </a>

        <a href="keranjang.php" class="btn btn-light">
            Lihat Keranjang
        </a>
    </div>
</nav>

<div class="container">

    <div class="detail-card">

        <div class="row g-4">

            <div class="col-md-6">

                <?php if (!empty($produk['gambar']) && file_exists("uploads/" . $produk['gambar'])): ?>

                    <img
                        src="uploads/<?php echo htmlspecialchars($produk['gambar']); ?>"
                        class="gambar-produk"
                        alt="Foto produk"
                    >

                <?php else: ?>

                    <div class="gambar-produk d-flex align-items-center justify-content-center bg-light">
                        <span class="text-muted">Foto produk belum tersedia</span>
                    </div>

                <?php endif; ?>

            </div>

            <div class="col-md-6">

                <h2>
                    <?php echo htmlspecialchars($produk['nama_produk']); ?>
                </h2>

                <p class="text-muted">
                    Kategori:
                    <?php echo htmlspecialchars($produk['nama_kategori'] ?? '-'); ?>
                </p>

                <p class="harga">
                    Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?>
                </p>

                <p>
                    Stok tersedia:
                    <strong><?php echo (int) $produk['stok']; ?></strong>
                </p>

                <hr>

                <h5>Deskripsi Produk</h5>

                <p>
                    <?php echo nl2br(htmlspecialchars($produk['deskripsi'] ?? 'Belum ada deskripsi.')); ?>
                </p>

                <?php if ($pesan !== ""): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($pesan); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <label class="form-label">
                        Jumlah Produk
                    </label>

                    <input
                        type="number"
                        name="jumlah"
                        class="form-control mb-3"
                        value="1"
                        min="1"
                        max="<?php echo (int) $produk['stok']; ?>"
                        required
                    >

                    <button
                        type="submit"
                        class="btn btn-keranjang w-100"
                        <?php echo ((int) $produk['stok'] < 1) ? 'disabled' : ''; ?>
                    >
                        Tambah ke Keranjang
                    </button>

                </form>

                <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">
                    Kembali ke Beranda
                </a>

            </div>

        </div>

    </div>

</div>

</body>
</html>
