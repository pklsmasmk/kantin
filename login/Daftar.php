<?php
include "../Database/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $passwordPlain = $_POST['password'];
    $passwordHash = password_hash($passwordPlain, PASSWORD_DEFAULT);
    $nama = 'karyawan';

    // Cek apakah username atau email sudah digunakan
    $cek = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $cek->execute([$username, $email]);

    if ($cek->rowCount() > 0) {
        $pesan = "Username atau Email sudah digunakan!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, nama, email) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $passwordHash, $nama, $email])) {
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
  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #a8edea, #fed6e3);
      min-height: 100vh;
      margin: 0;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .center-wrapper {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      min-height: 100vh;
      padding: 20px;
    }

    .card {
      border: none;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 420px;
      background-color: #fff;
    }

    .btn-success {
      background: linear-gradient(45deg, #28a745, #20c997);
      border: none;
      font-weight: 500;
    }
    .btn-success:hover {
      background: linear-gradient(45deg, #20c997, #28a745);
    }
  </style>
</head>
<body>

<div class="center-wrapper">
  <div class="card p-4">
    <h3 class="text-center mb-4">Daftar Akun</h3>

    <?php if (!empty($pesan)): ?>
      <div class="alert alert-info"><?= $pesan ?></div>
    <?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>

      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>

      <button type="submit" class="btn btn-success w-100">Daftar</button>
    </form>

    <p class="mt-3 text-center">Sudah punya akun? <a href="/?q=login">Login</a></p>
  </div>
</div>

</body>
</html>