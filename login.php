
<?php
session_start();
require_once "koneksi.php";

// Jika sudah login, kembali ke halaman utama
if (isset($_SESSION['id_user'])) {
    header("Location: index.php");
    exit;
}

$pesan = "";

// Proses login
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === "" || $password === "") {

        $pesan = "Email dan password wajib diisi.";

    } else {

        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_user, nama, email, password, role
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $hasil = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($hasil);

        if ($user && password_verify($password, $user['password'])) {

            session_regenerate_id(true);

            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            mysqli_stmt_close($stmt);

            header("Location: index.php");
            exit;

        } else {

            $pesan = "Email atau password salah.";
        }

        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - PasarKita</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f3e9;
            font-family: Arial, sans-serif;
        }

        .login-card {
            max-width: 420px;
            margin: 70px auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        }

        .judul {
            color: #6b4226;
            font-weight: bold;
        }

        .btn-pasar {
            background-color: #8b5e3c;
            color: white;
        }

        .btn-pasar:hover {
            background-color: #6b4226;
            color: white;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="login-card">

        <h2 class="judul text-center">PasarKita</h2>

        <p class="text-center text-muted">
            Silakan masuk ke akun Anda
        </p>

        <?php if (isset($_GET['daftar']) && $_GET['daftar'] === 'berhasil'): ?>
            <div class="alert alert-success">
                Registrasi berhasil! Silakan login.
            </div>
        <?php endif; ?>

        <?php if ($pesan !== ""): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($pesan); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">
                <label class="form-label">Email</label>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="Masukkan email"
                    required
                >
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>

                <input
                    type="password"
                    name="password"
                    class="form-control"
                    placeholder="Masukkan password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-pasar w-100">
                Login
            </button>

        </form>

        <p class="text-center mt-3">
            Belum punya akun?
            <a href="register.php">Daftar sekarang</a>
        </p>

        <div class="text-center">
            <a href="index.php" class="text-muted">
                Kembali ke Beranda
            </a>
        </div>

    </div>

</div>

</body>
</html>
