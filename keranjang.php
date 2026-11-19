
<?php
session_start();
require_once "koneksi.php";

// Pastikan keranjang tersedia
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$pesan = "";

// PROSES UPDATE, HAPUS, DAN KOSONGKAN KERANJANG
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update jumlah produk
    if (isset($_POST['update'])) {

        foreach ($_POST['jumlah'] as $id => $jumlah) {

            $id = (int) $id;
            $jumlah = (int) $jumlah;

            if (!isset($_SESSION['keranjang'][$id])) {
                continue;
            }

            if ($jumlah < 1) {
                $pesan = "Jumlah produk minimal 1.";
                continue;
            }

            $stmt = mysqli_prepare(
                $koneksi,
                "SELECT stok FROM produk WHERE id_produk = ?"
            );

            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);

            $hasil = mysqli_stmt_get_result($stmt);
            $data = mysqli_fetch_assoc($hasil);

            mysqli_stmt_close($stmt);

            if (!$data) {
                unset($_SESSION['keranjang'][$id]);
                continue;
            }

            if ($jumlah > (int) $data['stok']) {
                $pesan = "Jumlah melebihi stok produk.";
                continue;
            }

            $_SESSION['keranjang'][$id] = $jumlah;
        }
    }

    // Hapus satu produk
    if (isset($_POST['hapus'])) {

        $id = (int) $_POST['hapus'];

        unset($_SESSION['keranjang'][$id]);

        $pesan = "Produk berhasil dihapus.";
    }

    // Kosongkan keranjang
    if (isset($_POST['kosongkan'])) {

        $_SESSION['keranjang'] = [];

        $pesan = "Keranjang berhasil dikosongkan.";
    }
}

// AMBIL DATA PRODUK DALAM KERANJANG
$daftar_produk = [];
$total = 0;

foreach ($_SESSION['keranjang'] as $id => $jumlah) {

    $id = (int) $id;
    $jumlah = (int) $jumlah;

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT * FROM produk WHERE id_produk = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $produk = mysqli_fetch_assoc($hasil);

    mysqli_stmt_close($stmt);

    if (!$produk) {
        unset($_SESSION['keranjang'][$id]);
        continue;
    }

    $subtotal = (float) $produk['harga'] * $jumlah;
    $total += $subtotal;

    $produk['jumlah'] = $jumlah;
    $produk['subtotal'] = $subtotal;

    $daftar_produk[] = $produk;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Keranjang Belanja - PasarKita</title>

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

        .judul {
            color: #6b4226;
            font-weight: bold;
        }

        .keranjang-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-top: 35px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }

        .btn-pasar {
            background-color: #8b5e3c;
            color: white;
        }

        .btn-pasar:hover {
            background-color: #6b4226;
            color: white;
        }

        .total {
            color: #8b4513;
            font-size: 23px;
            font-weight: bold;
        }
    </style>
</head>

<body>

<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            PasarKita
        </a>

        <a href="index.php" class="btn btn-light">
            Belanja Lagi
        </a>
    </div>
</nav>

<div class="container">

    <div class="keranjang-card">

        <h2 class="judul mb-4">
            Keranjang Belanja
        </h2>

        <?php if ($pesan !== ""): ?>
            <div class="alert alert-info">
                <?php echo htmlspecialchars($pesan); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($daftar_produk)): ?>

            <div class="text-center py-5">

                <h4>Keranjang Belanja Masih Kosong</h4>

                <p class="text-muted">
                    Yuk, pilih produk dari pasar favoritmu!
                </p>

                <a href="index.php" class="btn btn-pasar">
                    Mulai Belanja
                </a>

            </div>

        <?php else: ?>

            <form method="POST">

                <div class="table-responsive">

                    <table class="table table-bordered align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>Nama Produk</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th width="130">Jumlah</th>
                                <th>Subtotal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($daftar_produk as $produk): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?php echo htmlspecialchars($produk['nama_produk']); ?>
                                    </strong>
                                </td>

                                <td>
                                    Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?>
                                </td>

                                <td>
                                    <?php echo (int) $produk['stok']; ?>
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        name="jumlah[<?php echo (int) $produk['id_produk']; ?>]"
                                        class="form-control"
                                        min="1"
                                        max="<?php echo (int) $produk['stok']; ?>"
                                        value="<?php echo (int) $produk['jumlah']; ?>"
                                        required
                                    >
                                </td>

                                <td>
                                    Rp <?php echo number_format($produk['subtotal'], 0, ',', '.'); ?>
                                </td>

                                <td>
                                    <button
                                        type="submit"
                                        name="hapus"
                                        value="<?php echo (int) $produk['id_produk']; ?>"
                                        class="btn btn-danger btn-sm"
                                        formnovalidate
                                        onclick="return confirm('Hapus produk ini?')"
                                    >
                                        Hapus
                                    </button>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>

                <button
                    type="submit"
                    name="update"
                    class="btn btn-primary"
                >
                    Update Keranjang
                </button>

            </form>

            <hr>

            <div class="row">

                <div class="col-md-6">

                    <form method="POST"
                          onsubmit="return confirm('Kosongkan seluruh keranjang?')">

                        <button
                            type="submit"
                            name="kosongkan"
                            class="btn btn-outline-danger"
                        >
                            Kosongkan Keranjang
                        </button>

                    </form>

                </div>

                <div class="col-md-6 text-md-end mt-3 mt-md-0">

                    <h5>Total Belanja</h5>

                    <div class="total mb-3">
                        Rp <?php echo number_format($total, 0, ',', '.'); ?>
                    </div>

                    <a href="checkout.php" class="btn btn-pasar btn-lg">
                        Lanjut Checkout
                    </a>

                </div>

            </div>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
