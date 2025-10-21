<?php
include "koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama = 'karyawan';

    $cek = $conn->prepare("SELECT * FROM users WHERE username=?");
    $cek->bind_param("s", $username);
    $cek->execute();
    $result = $cek->get_result();

    if ($result->num_rows > 0) {
        $pesan = "Username sudah digunakan!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, nama) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $nama);
        if ($stmt->execute()) {
            $pesan = "Pendaftaran berhasil! Silakan login.";
        } else {
            $pesan = "Gagal mendaftar: " . $stmt->error;
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

      <p class="mt-3 text-center">Sudah punya akun? <a href="login.php">Login</a></p>
    </div>
  </div>
</div>

</body>
</html>
