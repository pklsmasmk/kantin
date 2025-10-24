<?php
include "../Database/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $passwordPlain = $_POST['password'];
    $passwordHash = password_hash($passwordPlain, PASSWORD_DEFAULT);
    $nama = 'karyawan';

    $cek = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $cek->execute([$username]);

    if ($cek->rowCount() > 0) {
        $pesan = "Username sudah digunakan!";
    } else {

        $stmt = $pdo->prepare("INSERT INTO users (username, password, nama) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $passwordHash, $nama])) {
           echo "<script>window.location='/?q=login';</script>";
          exit;
        } else {
            $pesan = "Gagal mendaftar.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Daftar Akun</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
  <div class="card shadow-sm mx-auto" style="max-width: 400px;">
    <div class="card-body">
      <h3 class="text-center mb-4">Form Daftar</h3>

      <?php if (!empty($pesan)) : ?>
        <div class="alert alert-info"><?= $pesan ?></div>
      <?php endif; ?>

      <form method="post">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Daftar</button>
      </form>

      <p class="mt-3 text-center">Sudah punya akun? <a href="/?q=login">Login</a></p>
    </div>
  </div>
</div>

</body>
</html>
