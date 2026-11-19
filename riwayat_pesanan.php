
<?php
session_start();
require_once "koneksi.php";

// Pastikan pembeli sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['id_user'];

// Ambil seluruh riwayat pesanan milik pengguna
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT *
     FROM pesanan
     WHERE id_user = ?
     ORDER BY tanggal_pesanan DESC"
);

mysqli_stmt_bind_param($stmt, "i", $id_user);
mysqli_stmt_execute($stmt);

$hasil = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Riwayat Pesanan - PasarKita</title>

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

        .riwayat-card {
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

<nav class="navbar">
    <div class="container">
        <a href="index.php" class="navbar-brand">
            PasarKita
        </a>

        <a href="keranjang.php" class="btn btn-light">
            Keranjang
        </a>
    </div>
</nav>

<div class="container">

    <div class="riwayat-card">

        <h2 class="judul mb-2">Riwayat Pesanan</h2>

        <p class="text-muted">
            Halo, <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Pembeli'); ?>.
            Berikut daftar transaksi belanja kamu.
        </p>

        <hr>

        <?php if (mysqli_num_rows($hasil) > 0): ?>

            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>Nomor Pesanan</th>
                            <th>Tanggal</th>
                            <th>Total Harga</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php $no = 1; ?>

                    <?php while ($pesanan = mysqli_fetch_assoc($hasil)): ?>

                        <?php
                        $status = $pesanan['status'] ?? 'Menunggu';

                        $warna = 'warning';

                        if ($status === 'Diproses') {
                            $warna = 'info';
                        } elseif ($status === 'Dikirim') {
                            $warna = 'primary';
                        } elseif ($status === 'Selesai') {
                            $warna = 'success';
                        }
                        ?>

                        <tr>
                            <td><?php echo $no++; ?></td>

                            <td>
                                #<?php echo (int) $pesanan['id_pesanan']; ?>
                            </td>

                            <td>
                                <?php
                                echo date(
                                    'd-m-Y H:i',
                                    strtotime($pesanan['tanggal_pesanan'])
                                );
                                ?>
                            </td>

                            <td>
                                Rp <?php
                                echo number_format(
                                    $pesanan['total_harga'],
                                    0,
                                    ',',
                                    '.'
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $pesanan['metode_pembayaran']
                                );
                                ?>
                            </td>

                            <td>
                                <span class="badge bg-<?php echo $warna; ?>">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </td>

                            <td>
                                <a
                                    href="detail_pesanan.php?id=<?php echo (int) $pesanan['id_pesanan']; ?>"
                                    class="btn btn-sm btn-pasar"
                                >
                                    Detail
                                </a>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="alert alert-info text-center">
                Kamu belum memiliki riwayat pesanan.
            </div>

            <div class="text-center">
                <a href="index.php" class="btn btn-pasar">
                    Mulai Belanja
                </a>
            </div>

        <?php endif; ?>

        <a href="index.php" class="btn btn-outline-secondary mt-3">
            Kembali ke Beranda
        </a>

    </div>

</div>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
?>
