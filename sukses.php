
<?php
session_start();
require_once "koneksi.php";

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_pesanan = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$id_user = (int) $_SESSION['id_user'];

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM pesanan
     WHERE id_pesanan = ? AND id_user = ?"
);

mysqli_stmt_bind_param($stmt, "ii", $id_pesanan, $id_user);
mysqli_stmt_execute($stmt);

$hasil = mysqli_stmt_get_result($stmt);
$pesanan = mysqli_fetch_assoc($hasil);

mysqli_stmt_close($stmt);

if (!$pesanan) {
    die("Pesanan tidak ditemukan.");
}

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT d.*, p.nama_produk
     FROM detail_pesanan d
     LEFT JOIN produk p ON d.id_produk = p.id_produk
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

    <title>Pesanan Berhasil - PasarKita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f8f3e9;
            font-family: Arial, sans-serif;
        }

        .success-card {
            max-width: 700px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,.08);
        }

        .judul {
            color: #6b4226;
            font-weight: bold;
        }

        .btn-pasar {
            background: #8b5e3c;
            color: white;
        }

        .btn-pasar:hover {
            background: #6b4226;
            color: white;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="success-card text-center">

        <div class="text-success mb-3">
            <span style="font-size:65px;">✓</span>
        </div>

        <h2 class="judul">Pesanan Berhasil!</h2>

        <p class="text-muted">
            Terima kasih sudah berbelanja di PasarKita.
            Pesanan kamu berhasil disimpan.
        </p>

        <hr>

        <div class="text-start">

            <h5 class="judul">Informasi Pesanan</h5>

            <p>
                <strong>Nomor Pesanan:</strong>
                #<?php echo $pesanan['id_pesanan']; ?>
            </p>

            <p>
                <strong>Nama Penerima:</strong>
                <?php echo htmlspecialchars($pesanan['nama_penerima']); ?>
            </p>

            <p>
                <strong>Nomor WhatsApp:</strong>
                <?php echo htmlspecialchars($pesanan['no_hp']); ?>
            </p>

            <p>
                <strong>Alamat:</strong>
                <?php echo nl2br(htmlspecialchars($pesanan['alamat'])); ?>
            </p>

            <p>
                <strong>Metode Pembayaran:</strong>
                <?php echo htmlspecialchars($pesanan['metode_pembayaran']); ?>
            </p>

            <p>
                <strong>Status:</strong>
                <span class="badge bg-warning text-dark">
                    <?php echo htmlspecialchars($pesanan['status']); ?>
                </span>
            </p>

            <hr>

            <h5 class="judul">Detail Produk</h5>

            <div class="table-responsive">

                <table class="table table-bordered">

                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php while ($detail = mysqli_fetch_assoc($hasil_detail)): ?>

                        <tr>
                            <td>
                                <?php echo htmlspecialchars($detail['nama_produk'] ?? 'Produk'); ?>
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

            <h4 class="text-end judul">
                Total: Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?>
            </h4>

        </div>

        <hr>

        <a href="index.php" class="btn btn-pasar w-100">
            Kembali ke Beranda
        </a>

    </div>

</div>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>
