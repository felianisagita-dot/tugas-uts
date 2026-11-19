
<?php
header("Content-Type: application/json; charset=UTF-8");

require_once "../koneksi.php";

// Mengatur response JSON
function response($status, $message, $data = null, $code = 200) {
    http_response_code($code);

    echo json_encode([
        "status" => $status,
        "message" => $message,
        "data" => $data
    ], JSON_PRETTY_PRINT);

    exit;
}

// Hanya menerima request GET
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    header("Allow: GET");
    response("error", "Method tidak diizinkan", null, 405);
}

// Jika ID diberikan, tampilkan detail produk
if (isset($_GET["id"])) {

    $id = filter_var($_GET["id"], FILTER_VALIDATE_INT);

    if ($id === false || $id === null || $id < 1) {
        response("error", "ID produk tidak valid", null, 400);
    }

    $query = mysqli_prepare(
        $koneksi,
        "SELECT id_produk, nama_produk, harga, stok,
                gambar, deskripsi, id_kategori
         FROM produk
         WHERE id_produk = ?"
    );

    if (!$query) {
        response("error", "Gagal menyiapkan query", null, 500);
    }

    mysqli_stmt_bind_param($query, "i", $id);
    mysqli_stmt_execute($query);

    $hasil = mysqli_stmt_get_result($query);
    $produk = mysqli_fetch_assoc($hasil);

    mysqli_stmt_close($query);

    if (!$produk) {
        response("error", "Produk tidak ditemukan", null, 404);
    }

    response("success", "Detail produk berhasil diambil", $produk);
}

// Jika ID tidak diberikan, tampilkan seluruh produk
$sql = "SELECT id_produk, nama_produk, harga, stok,
               gambar, deskripsi, id_kategori
        FROM produk
        ORDER BY id_produk DESC";

$hasil = mysqli_query($koneksi, $sql);

if (!$hasil) {
    response("error", "Gagal mengambil data produk", null, 500);
}

$data = [];

while ($baris = mysqli_fetch_assoc($hasil)) {
    $data[] = $baris;
}

response("success", "Data produk berhasil diambil", $data);
?>
