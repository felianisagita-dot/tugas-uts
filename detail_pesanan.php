
<?php
session_start();
require_once "koneksi.php";

// Periksa login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

// Periksa ID pesanan
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID pesanan tidak valid.");
}

$id_pesanan = (int) $_GET['id'];
$id_user = (int) $_SESSION['id_user'];

// Ambil pesanan milik pengguna yang sedang login
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT *
     FROM pesanan
     WHERE id_pesanan = ? AND id_user = ?"
);

mysqli_stmt_bind_param(
    $stmt,
    "ii",
    $id_pesanan,
    $id_user
);

mysqli_stmt_execute($stmt);

$hasil = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($hasil);

mysqli_stmt_close($stmt);

if (!$pesanan) {
    die("Pesanan tidak ditemukan atau bukan milik akun Anda.");
}

// Ambil rincian produk pesanan
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT d.*, p.nama_produk
     FROM detail_pesanan d
     LEFT JOIN produk p
     ON d.id_produk = p.id_produk
     WHERE d.id_pesanan = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id_pesanan);
mysqli_stmt_execute($stmt);

$hasil_detail = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Detail Pesanan - PasarKita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f8f3e9;
            font-family: Arial, sans-serif;
        }

        .navbar {
            background: #6b4226;
        }

        .navbar-brand {
            color: white;
            font-weight: bold;
        }

        .detail-card {
            background: white;
            padding: 25px;
            margin-top: 35px;
            margin-bottom: 40px;
            border-radius: 15px;
            box-shadow: 0 3px 12px rgba(0,0,0,.08);
        }

        .judul {
            color: #6b4226;
            font-weight: bold;
        }

        .total {
            font-size: 24px;
            color: #8b4513;
            font-weight: bold;
        }
    </style>
</head>

<body>

<nav class="navbar">
    <div class="container">
        <a href="index.php" class="navbar-brand">
            PasarKita
        </a>
    </div>
</nav>

<div class="container">

    <div class="detail-card">

        <h2 class="judul">Detail Pesanan</h2>

        <p class="text-muted">
            Informasi lengkap transaksi PasarKita.
        </p>

        <hr>

        <h5 class="judul">Informasi Transaksi</h5>

        <div class="row mb-3">

            <div class="col-md-6">
                <p>
                    <strong>Nomor Pesanan:</strong><br>
                    #<?php echo (int) $pesanan['id_pesanan']; ?>
                </p>

                <p>
                    <strong>Tanggal Pesanan:</strong><br>
                    <?php
                    echo date(
                        'd-m-Y H:i',
                        strtotime($pesanan['tanggal_pesanan'])
                    );
                    ?>
                </p>

                <p>
                    <strong>Nama Penerima:</strong><br>
                    <?php echo htmlspecialchars($pesanan['nama_penerima']); ?>
                </p>
            </div>

            <div class="col-md-6">
                <p>
                    <strong>Nomor WhatsApp:</strong><br>
                    <?php echo htmlspecialchars($pesanan['no_hp']); ?>
                </p>

                <p>
                    <strong>Metode Pembayaran:</strong><br>
                    <?php echo htmlspecialchars($pesanan['metode_pembayaran']); ?>
                </p>

                <p>
                    <strong>Status Pesanan:</strong><br>
                    <span class="badge bg-warning text-dark">
                        <?php echo htmlspecialchars($pesanan['status']); ?>
                    </span>
                </p>
            </div>

        </div>

        <p>
            <strong>Alamat Pengiriman:</strong><br>
            <?php echo nl2br(htmlspecialchars($pesanan['alamat'])); ?>
        </p>

        <hr>

        <h5 class="judul mb-3">Produk yang Dipesan</h5>

        <div class="table-responsive">

            <table class="table table-bordered align-middle">

                <thead class="table-light">
                    <tr>
                        <th>No.</th>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>

                <tbody>

                <?php $no = 1; ?>

                <?php while ($detail = mysqli_fetch_assoc($hasil_detail)): ?>

                    <tr>
                        <td><?php echo $no++; ?></td>

                        <td>
                            <?php echo htmlspecialchars($detail['nama_produk'] ?? 'Produk tidak tersedia'); ?>
                        </td>

                        <td>
                            Rp <?php echo number_format($detail['harga'], 0, ',', '.'); ?>
                        </td>

                        <td>
                            <?php echo (int) $detail['jumlah']; ?>
                        </td>

                        <td>
                            Rp <?php echo number_format($detail['subtotal'], 0, ',', '.'); ?>
                        </td>
                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        </div>

        <div class="text-end">
            <div class="text-muted">Total Pembayaran</div>

            <div class="total">
                Rp <?php
                echo number_format(
                    $pesanan['total_harga'],
                    0,
                    ',',
                    '.'
                );
                ?>
            </div>
        </div>

        <hr>

        <a href="riwayat_pesanan.php" class="btn btn-secondary">
            Kembali ke Riwayat
        </a>

        <a href="index.php" class="btn btn-outline-secondary">
            Beranda
        </a>

    </div>

</div>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>
