
<?php
session_start();
require_once "koneksi.php";

// Pastikan pembeli sudah login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

// Pastikan keranjang tersedia
if (empty($_SESSION['keranjang'])) {
    header("Location: keranjang.php");
    exit;
}

$pesan = "";

// Ambil data produk untuk ringkasan checkout
$daftar_produk = [];
$total = 0;

foreach ($_SESSION['keranjang'] as $id => $jumlah) {

    $id = (int) $id;
    $jumlah = (int) $jumlah;

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT id_produk, nama_produk, harga, stok
         FROM produk
         WHERE id_produk = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $hasil = mysqli_stmt_get_result($stmt);
    $produk = mysqli_fetch_assoc($hasil);

    mysqli_stmt_close($stmt);

    if (!$produk) {
        $pesan = "Ada produk dalam keranjang yang sudah tidak tersedia.";
        continue;
    }

    $produk['jumlah'] = $jumlah;
    $produk['subtotal'] = (float) $produk['harga'] * $jumlah;

    $total += $produk['subtotal'];
    $daftar_produk[] = $produk;
}

// Proses ketika tombol buat pesanan ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama = trim($_POST['nama_penerima'] ?? '');
    $no_hp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $metode = $_POST['metode_pembayaran'] ?? '';

    if ($nama === '' || $no_hp === '' || $alamat === '') {

        $pesan = "Semua data penerima wajib diisi.";

    } elseif (!preg_match('/^[0-9+\-\s]{8,20}$/', $no_hp)) {

        $pesan = "Nomor WhatsApp tidak valid.";

    } elseif (!in_array($metode, ['COD', 'Transfer Bank'], true)) {

        $pesan = "Metode pembayaran tidak valid.";

    } else {

        try {

            // Mulai transaksi database
            mysqli_begin_transaction($koneksi);

            $keranjang = $_SESSION['keranjang'];
            $produk_valid = [];
            $total_baru = 0;

            // Periksa kembali stok dan harga produk
            foreach ($keranjang as $id => $jumlah) {

                $id = (int) $id;
                $jumlah = (int) $jumlah;

                if ($jumlah < 1) {
                    throw new Exception("Jumlah produk tidak valid.");
                }

                $stmt = mysqli_prepare(
                    $koneksi,
                    "SELECT id_produk, nama_produk, harga, stok
                     FROM produk
                     WHERE id_produk = ?
                     FOR UPDATE"
                );

                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);

                $hasil = mysqli_stmt_get_result($stmt);
                $produk = mysqli_fetch_assoc($hasil);

                mysqli_stmt_close($stmt);

                if (!$produk) {
                    throw new Exception("Produk tidak ditemukan.");
                }

                if ($jumlah > (int) $produk['stok']) {
                    throw new Exception(
                        "Stok " . $produk['nama_produk'] .
                        " tidak mencukupi. Stok tersedia: " .
                        $produk['stok']
                    );
                }

                $produk['jumlah'] = $jumlah;
                $produk['subtotal'] =
                    (float) $produk['harga'] * $jumlah;

                $total_baru += $produk['subtotal'];

                $produk_valid[] = $produk;
            }

            if (empty($produk_valid)) {
                throw new Exception("Keranjang belanja kosong.");
            }

            // Simpan data pesanan
            $id_user = (int) $_SESSION['id_user'];
            $status = "Menunggu";

            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO pesanan
                (id_user, nama_penerima, alamat, no_hp,
                 total_harga, status, metode_pembayaran)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "isssdss",
                $id_user,
                $nama,
                $alamat,
                $no_hp,
                $total_baru,
                $status,
                $metode
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Gagal menyimpan pesanan.");
            }

            $id_pesanan = mysqli_insert_id($koneksi);

            mysqli_stmt_close($stmt);

            // Simpan setiap produk ke detail_pesanan
            foreach ($produk_valid as $produk) {

                $id_produk = (int) $produk['id_produk'];
                $jumlah = (int) $produk['jumlah'];
                $harga = (float) $produk['harga'];
                $subtotal = (float) $produk['subtotal'];

                $stmt = mysqli_prepare(
                    $koneksi,
                    "INSERT INTO detail_pesanan
                    (id_pesanan, id_produk, jumlah, harga, subtotal)
                    VALUES (?, ?, ?, ?, ?)"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "iiidd",
                    $id_pesanan,
                    $id_produk,
                    $jumlah,
                    $harga,
                    $subtotal
                );

                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception(
                        "Gagal menyimpan detail pesanan."
                    );
                }

                mysqli_stmt_close($stmt);

                // Kurangi stok produk
                $stmt = mysqli_prepare(
                    $koneksi,
                    "UPDATE produk
                     SET stok = stok - ?
                     WHERE id_produk = ? AND stok >= ?"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "iii",
                    $jumlah,
                    $id_produk,
                    $jumlah
                );

                if (!mysqli_stmt_execute($stmt) ||
                    mysqli_stmt_affected_rows($stmt) !== 1) {

                    throw new Exception(
                        "Gagal memperbarui stok produk."
                    );
                }

                mysqli_stmt_close($stmt);
            }

            // Simpan seluruh perubahan
            mysqli_commit($koneksi);

            // Kosongkan keranjang setelah berhasil
            $_SESSION['keranjang'] = [];

            // Arahkan ke halaman sukses
            header(
                "Location: sukses.php?id=" . $id_pesanan
            );
            exit;

        } catch (Throwable $e) {

            mysqli_rollback($koneksi);

            $pesan = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Checkout - PasarKita</title>

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

        .checkout-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            margin-top: 30px;
            margin-bottom: 40px;
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

        .total {
            font-size: 24px;
            font-weight: bold;
            color: #8b4513;
        }
    </style>
</head>

<body>

<nav class="navbar">
    <div class="container">
        <a href="index.php" class="navbar-brand">
            PasarKita
        </a>

        <span class="text-white">
            Checkout
        </span>
    </div>
</nav>

<div class="container">

    <div class="checkout-card">

        <h2 class="judul mb-4">
            Checkout Pesanan
        </h2>

        <p class="text-muted">
            Halo, <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Pembeli'); ?>.
            Silakan lengkapi data pengiriman.
        </p>

        <?php if ($pesan !== ''): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($pesan); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($daftar_produk)): ?>

        <form method="POST">

            <h5 class="judul mb-3">
                Data Penerima
            </h5>

            <div class="mb-3">
                <label class="form-label">Nama Penerima</label>

                <input
                    type="text"
                    name="nama_penerima"
                    class="form-control"
                    value="<?php echo htmlspecialchars($_POST['nama_penerima'] ?? $_SESSION['nama'] ?? ''); ?>"
                    maxlength="100"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Nomor WhatsApp</label>

                <input
                    type="tel"
                    name="no_hp"
                    class="form-control"
                    placeholder="Contoh: 081234567890"
                    value="<?php echo htmlspecialchars($_POST['no_hp'] ?? ''); ?>"
                    required
                >
            </div>

            <div class="mb-4">
                <label class="form-label">Alamat Pengiriman</label>

                <textarea
                    name="alamat"
                    class="form-control"
                    rows="4"
                    placeholder="Masukkan alamat lengkap..."
                    required
                ><?php echo htmlspecialchars($_POST['alamat'] ?? ''); ?></textarea>
            </div>

            <hr>

            <h5 class="judul mb-3">
                Metode Pembayaran
            </h5>

            <div class="mb-4">

                <div class="form-check mb-2">
                    <input
                        type="radio"
                        name="metode_pembayaran"
                        value="COD"
                        class="form-check-input"
                        id="cod"
                        <?php echo (($_POST['metode_pembayaran'] ?? 'COD') === 'COD') ? 'checked' : ''; ?>
                    >

                    <label class="form-check-label" for="cod">
                        Cash on Delivery (COD)
                    </label>
                </div>

                <div class="form-check">
                    <input
                        type="radio"
                        name="metode_pembayaran"
                        value="Transfer Bank"
                        class="form-check-input"
                        id="transfer"
                        <?php echo (($_POST['metode_pembayaran'] ?? '') === 'Transfer Bank') ? 'checked' : ''; ?>
                    >

                    <label class="form-check-label" for="transfer">
                        Transfer Bank
                    </label>
                </div>

            </div>

            <hr>

            <h5 class="judul mb-3">
                Ringkasan Belanja
            </h5>

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($daftar_produk as $produk): ?>

                        <tr>
                            <td>
                                <?php echo htmlspecialchars($produk['nama_produk']); ?>
                            </td>

                            <td>
                                Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?>
                            </td>

                            <td>
                                <?php echo (int) $produk['jumlah']; ?>
                            </td>

                            <td>
                                Rp <?php echo number_format($produk['subtotal'], 0, ',', '.'); ?>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <div class="text-end mb-4">

                <div class="text-muted">
                    Total Pembayaran
                </div>

                <div class="total">
                    Rp <?php echo number_format($total, 0, ',', '.'); ?>
                </div>

            </div>

            <button
                type="submit"
                class="btn btn-pasar btn-lg w-100"
                onclick="return confirm('Apakah data pesanan sudah benar?')"
            >
                Buat Pesanan Sekarang
            </button>

        </form>

        <?php else: ?>

            <div class="alert alert-warning">
                Tidak ada produk yang bisa diproses.
                Silakan kembali ke keranjang.
            </div>

            <a href="keranjang.php" class="btn btn-pasar">
                Kembali ke Keranjang
            </a>

        <?php endif; ?>

    </div>

</div>

</body>
</html>
