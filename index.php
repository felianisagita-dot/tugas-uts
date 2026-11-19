
<?php
session_start();
require_once "koneksi.php";

// Pencarian produk
$cari = trim($_GET['q'] ?? '');
$kategori = (int)($_GET['kategori'] ?? 0);

// Ambil kategori
$dataKategori = mysqli_query(
    $koneksi,
    "SELECT * FROM kategori ORDER BY nama_kategori ASC"
);

// Ambil produk
$sql = "SELECT p.*, k.nama_kategori
        FROM produk p
        LEFT JOIN kategori k ON p.id_kategori = k.id_kategori
        WHERE 1=1";

if ($cari !== '') {
    $sql .= " AND (p.nama_produk LIKE ? OR k.nama_kategori LIKE ?)";
}

if ($kategori > 0) {
    $sql .= " AND p.id_kategori = ?";
}

$sql .= " ORDER BY p.id_produk DESC";

$stmt = mysqli_prepare($koneksi, $sql);

if ($cari !== '' && $kategori > 0) {
    $keyword = "%".$cari."%";
    mysqli_stmt_bind_param($stmt, "ssi", $keyword, $keyword, $kategori);
} elseif ($cari !== '') {
    $keyword = "%".$cari."%";
    mysqli_stmt_bind_param($stmt, "ss", $keyword, $keyword);
} elseif ($kategori > 0) {
    mysqli_stmt_bind_param($stmt, "i", $kategori);
}

mysqli_stmt_execute($stmt);
$dataProduk = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PasarKita - Marketplace Produk Lokal</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #faf7f1;
            font-family: Arial, sans-serif;
        }

        .navbar-brand {
            color: #99501f !important;
            font-size: 28px;
            font-weight: bold;
        }

        .hero {
            background: linear-gradient(110deg, #79421f, #bd8652);
            color: white;
            border-radius: 20px;
            padding: 75px 30px;
            margin-top: 35px;
        }

        .hero h1 {
            font-size: 42px;
            font-weight: bold;
        }

        .section-title {
            color: #49301e;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .category-link {
            display: inline-block;
            padding: 12px 22px;
            border: 1px solid #b58b6b;
            border-radius: 30px;
            color: #79421f;
            text-decoration: none;
            margin: 4px;
            background: white;
        }

        .category-link:hover,
        .category-active {
            background: #8b4b20;
            color: white;
        }

        .product-card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            height: 100%;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            transition: 0.2s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 210px;
            object-fit: cover;
            background: #eee;
        }

        .price {
            color: #a45120;
            font-size: 19px;
            font-weight: bold;
        }

        .btn-brown {
            background: #8b4b20;
            color: white;
        }

        .btn-brown:hover {
            background: #693817;
            color: white;
        }

        footer {
            background: #61391f;
            color: white;
            margin-top: 70px;
            padding: 30px;
        }
    </style>
</head>

<body>

<!-- NAVBAR -->
<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<div class="container">

    <!-- HERO -->
    <section class="hero">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1>Selamat Datang di PasarKita!</h1>
                <p class="fs-5 mt-3">
                    Temukan berbagai produk tradisional dan kerajinan lokal
                    berkualitas dari UMKM Indonesia.
                </p>
                <a href="#produk" class="btn btn-light btn-lg mt-3">
                    Belanja Sekarang
                </a>
            </div>

            <div class="col-md-4 text-center mt-4 mt-md-0">
                <div style="font-size:100px;">🧺</div>
                <h4>Bangga Produk Lokal</h4>
            </div>
        </div>
    </section>

    <!-- PENCARIAN -->
    <section class="mt-5">
        <h3 class="section-title">Cari Produk Favoritmu</h3>

        <form method="GET" action="index.php">
            <div class="input-group input-group-lg">
                <input
                    type="text"
                    name="q"
                    class="form-control"
                    placeholder="Cari nama produk atau kategori..."
                    value="<?= htmlspecialchars($cari) ?>"
                >

                <button class="btn btn-brown" type="submit">
                    Cari
                </button>
            </div>
        </form>
    </section>

    <!-- KATEGORI -->
    <section class="mt-5">
        <h3 class="section-title">Kategori Produk</h3>

        <a href="index.php"
           class="category-link <?= $kategori === 0 ? 'category-active' : '' ?>">
            Semua Produk
        </a>

        <?php if ($dataKategori): ?>
            <?php while ($kat = mysqli_fetch_assoc($dataKategori)): ?>
                <a
                    href="index.php?kategori=<?= (int)$kat['id_kategori'] ?>"
                    class="category-link <?= $kategori === (int)$kat['id_kategori'] ? 'category-active' : '' ?>"
                >
                    <?= htmlspecialchars($kat['nama_kategori']) ?>
                </a>
            <?php endwhile; ?>
        <?php endif; ?>
    </section>

    <!-- PRODUK -->
    <section class="mt-5" id="produk">
        <h3 class="section-title">Produk Pilihan</h3>

        <div class="row g-4">

            <?php if (mysqli_num_rows($dataProduk) > 0): ?>

                <?php while ($produk = mysqli_fetch_assoc($dataProduk)): ?>

                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="card product-card">

                            <?php
                            $gambar = trim($produk['gambar'] ?? '');
                            $lokasiGambar = "uploads/" . $gambar;
                            ?>

                            <?php if ($gambar !== '' && file_exists(__DIR__ . '/' . $lokasiGambar)): ?>
                                <img
                                    src="<?= htmlspecialchars($lokasiGambar) ?>"
                                    class="product-image"
                                    alt="<?= htmlspecialchars($produk['nama_produk']) ?>"
                                >
                            <?php else: ?>
                                <div class="product-image d-flex align-items-center justify-content-center">
                                    <span style="font-size:65px;">🛍️</span>
                                </div>
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column">

                                <small class="text-muted">
                                    <?= htmlspecialchars($produk['nama_kategori'] ?? 'Umum') ?>
                                </small>

                                <h5 class="mt-2">
                                    <?= htmlspecialchars($produk['nama_produk']) ?>
                                </h5>

                                <p class="price">
                                    Rp <?= number_format((float)$produk['harga'], 0, ',', '.') ?>
                                </p>

                                <small class="text-muted mb-3">
                                    Stok: <?= (int)$produk['stok'] ?>
                                </small>

                                <a
                                    href="detail.php?id=<?= (int)$produk['id_produk'] ?>"
                                    class="btn btn-brown mt-auto"
                                >
                                    Lihat Detail
                                </a>

                            </div>
                        </div>
                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="col-12">
                    <div class="alert alert-warning text-center">
                        Produk tidak ditemukan.
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </section>

</div>

<!-- FOOTER -->
<footer class="text-center">
    <h4>PasarKita</h4>
    <p>Marketplace Produk Tradisional dan UMKM Lokal Indonesia.</p>
    <small>© <?= date('Y') ?> PasarKita. Semua Hak Dilindungi.</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
