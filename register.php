
<?php
session_start();
require_once "koneksi.php";

if (isset($_SESSION['id_user'])) {
    header("Location: index.php");
    exit;
}

$pesan = "";
$nama = "";
$email = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if ($nama === "" || $email === "" || $password === "") {
        $pesan = "Semua kolom wajib diisi.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $pesan = "Format email tidak valid.";

    } elseif (strlen($password) < 8) {
        $pesan = "Password minimal 8 karakter.";

    } elseif ($password !== $konfirmasi) {
        $pesan = "Konfirmasi password tidak sama.";

    } else {

        // Periksa apakah email sudah digunakan
        $cek = mysqli_prepare(
            $koneksi,
            "SELECT id_user FROM users WHERE email = ?"
        );

        mysqli_stmt_bind_param($cek, "s", $email);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if (mysqli_stmt_num_rows($cek) > 0) {

            $pesan = "Email sudah terdaftar.";

        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = "pembeli";

            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO users (nama, email, password, role)
                 VALUES (?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssss",
                $nama,
                $email,
                $hash,
                $role
            );

            if (mysqli_stmt_execute($stmt)) {

                mysqli_stmt_close($stmt);
                mysqli_stmt_close($cek);

                header("Location: login.php?daftar=berhasil");
                exit;

            } else {
                $pesan = "Registrasi gagal: " .
                    mysqli_stmt_error($stmt);
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($cek);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - PasarKita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f8f3e9;
            font-family: Arial, sans-serif;
        }

        .register-card {
            max-width: 450px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,.1);
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

    <div class="register-card">

        <h2 class="judul text-center">PasarKita</h2>

        <p class="text-center text-muted">
            Buat akun pembeli baru
        </p>

        <?php if ($pesan !== ""): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($pesan); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input
                    type="text"
                    name="nama"
                    class="form-control"
                    value="<?php echo htmlspecialchars($nama); ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input
                    type="email"
                    name="email"
                    class="form-control"
                    value="<?php echo htmlspecialchars($email); ?>"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input
                    type="password"
                    name="password"
                    class="form-control"
                    minlength="8"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Konfirmasi Password</label>
                <input
                    type="password"
                    name="konfirmasi"
                    class="form-control"
                    minlength="8"
                    required
                >
            </div>

            <button type="submit" class="btn btn-pasar w-100">
                Daftar Sekarang
            </button>

        </form>

        <p class="text-center mt-3 mb-0">
            Sudah punya akun?
            <a href="login.php">Login di sini</a>
        </p>

        <div class="text-center mt-3">
            <a href="index.php" class="text-muted">
                Kembali ke Beranda
            </a>
        </div>

    </div>

</div>

</body>
</html>
