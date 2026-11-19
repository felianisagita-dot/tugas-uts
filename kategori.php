
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

// API kategori hanya menerima GET
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    header("Allow: GET");
    response("error", "Method tidak diizinkan", null, 405);
}

// Mengambil seluruh kategori
$sql = "SELECT id_kategori, nama_kategori
        FROM kategori
        ORDER BY id_kategori ASC";

$hasil = mysqli_query($koneksi, $sql);

if (!$hasil) {
    response("error", "Gagal mengambil data kategori", null, 500);
}

$data = [];

while ($baris = mysqli_fetch_assoc($hasil)) {
    $data[] = $baris;
}

response("success", "Data kategori berhasil diambil", $data);
?>
