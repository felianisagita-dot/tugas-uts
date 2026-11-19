
<?php
header("Content-Type: application/json; charset=UTF-8");

require_once "../koneksi.php";

// Fungsi response JSON
function response($status, $message, $data = null, $code = 200) {
    http_response_code($code);

    echo json_encode([
        "status" => $status,
        "message" => $message,
        "data" => $data
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    exit;
}

// API hanya menerima POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Allow: POST");
    response("error", "Gunakan method POST", null, 405);
}

// Membaca data JSON dari request
$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    response("error", "Format JSON tidak valid", null, 400);
}

// Mengambil data penerima
$id_user = filter_var(
    $input["id_user"] ?? null,
    FILTER_VALIDATE_INT
);

$nama = trim($input["nama_penerima"] ?? "");
$no_hp = trim($input["no_hp"] ?? "");
$alamat = trim($input["alamat"] ?? "");
$metode = $input["metode_pembayaran"] ?? "";
$produk_input = $input["produk"] ?? null;

// Validasi data penerima
if (!$id_user || $id_user < 1) {
    response("error", "ID pengguna tidak valid", null, 400);
}

if ($nama === "" || strlen($nama) > 100) {
    response("error", "Nama penerima wajib diisi (maksimal 100 karakter)", null, 400);
}

if (!preg_match('/^[0-9+\-\s]{8,20}$/', $no_hp)) {
    response("error", "Nomor HP tidak valid", null, 400);
}

if ($alamat === "") {
    response("error", "Alamat wajib diisi", null, 400);
}

if (!in_array($metode, ["COD", "Transfer Bank"], true)) {
    response("error", "Metode pembayaran tidak valid", null, 400);
}

if (!is_array($produk_input) || empty($produk_input)) {
    response("error", "Daftar produk wajib diisi", null, 400);
}

// Menggabungkan produk yang memiliki ID sama
$keranjang = [];

foreach ($produk_input as $item) {

    if (!is_array($item)) {
        response("error", "Format produk tidak valid", null, 400);
    }

    $id_produk = filter_var(
        $item["id_produk"] ?? null,
        FILTER_VALIDATE_INT
    );

    $jumlah = filter_var(
        $item["jumlah"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!$id_produk || $id_produk < 1 ||
        !$jumlah || $jumlah < 1) {
        response("error", "ID produk atau jumlah tidak valid", null, 400);
    }

    if (!isset($keranjang[$id_produk])) {
        $keranjang[$id_produk] = 0;
    }

    $keranjang[$id_produk] += $jumlah;
}

try {

    mysqli_begin_transaction($koneksi);

    $produk_valid = [];
    $total = 0;

    // Memeriksa pengguna
    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT id_user FROM users WHERE id_user = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id_user);
    mysqli_stmt_execute($stmt);

    $hasil_user = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($hasil_user);

    mysqli_stmt_close($stmt);

    if (!$user) {
        throw new Exception("Pengguna tidak ditemukan.");
    }

    // Memeriksa produk dan stok
    foreach ($keranjang as $id_produk => $jumlah) {

        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_produk, nama_produk, harga, stok
             FROM produk
             WHERE id_produk = ?
             FOR UPDATE"
        );

        mysqli_stmt_bind_param($stmt, "i", $id_produk);
        mysqli_stmt_execute($stmt);

        $hasil = mysqli_stmt_get_result($stmt);
        $produk = mysqli_fetch_assoc($hasil);

        mysqli_stmt_close($stmt);

        if (!$produk) {
            throw new Exception("Produk ID $id_produk tidak ditemukan.");
        }

        if ($jumlah > (int) $produk["stok"]) {
            throw new Exception(
                "Stok " . $produk["nama_produk"] .
                " tidak mencukupi. Stok tersedia: " .
                $produk["stok"]
            );
        }

        $produk["jumlah"] = $jumlah;
        $produk["subtotal"] = (float) $produk["harga"] * $jumlah;

        $total += $produk["subtotal"];
        $produk_valid[] = $produk;
    }

    // Menyimpan pesanan utama
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
        $total,
        $status,
        $metode
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Gagal menyimpan pesanan.");
    }

    $id_pesanan = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt);

    // Menyimpan detail pesanan dan mengurangi stok
    foreach ($produk_valid as $produk) {

        $id_produk = (int) $produk["id_produk"];
        $jumlah = (int) $produk["jumlah"];
        $harga = (float) $produk["harga"];
        $subtotal = (float) $produk["subtotal"];

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
            throw new Exception("Gagal menyimpan detail pesanan.");
        }

        mysqli_stmt_close($stmt);

        // Mengurangi stok
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
            throw new Exception("Gagal memperbarui stok produk.");
        }

        mysqli_stmt_close($stmt);
    }

    // Menyelesaikan transaksi
    mysqli_commit($koneksi);

    response("success", "Pesanan berhasil dibuat", [
        "id_pesanan" => $id_pesanan,
        "id_user" => $id_user,
        "total_harga" => $total,
        "status" => $status,
        "metode_pembayaran" => $metode
    ], 201);

} catch (Throwable $e) {

    mysqli_rollback($koneksi);

    response("error", $e->getMessage(), null, 400);
}
?>
