<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();

include "../Database/config.php";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $data = $stmt->fetch();

    if ($data) {
        if (password_verify($password, $data['password'])) {
            $_SESSION['id_user'] = $data['id'];
            $_SESSION['username'] = $data['username'];
            $_SESSION['nama'] = $data['nama'];
            $_SESSION['role'] = $data['id_role'];
            $_SESSION['namalengkap'] = $data['namalengkap'];
            $_SESSION['email'] = $data['email'];

            header("Location: ../index.php");
            exit;
        } else {
            echo "<script>alert('Password salah!'); window.location='/?q=login';</script>";
        }
    } else {
        echo "<script>alert('Username atau Email tidak ditemukan!'); window.location='/?q=login';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Kantin Pintar</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, #a8edea, #fed6e3);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .card {
      border: none;
      border-radius: 20px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      width: 100%;
      max-width: 420px;
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

<div class="card p-4">
  <h4 class="text-center mb-4">Login Kantin UAM</h4>
  <form method="POST" action="">
    <div class="mb-3">
      <label for="username" class="form-label">Username atau Email</label>
      <input type="text" class="form-control" name="username" id="username" required>
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input type="password" class="form-control" name="password" id="password" required>
    </div>
    <div class="d-grid gap-2">
      <button type="submit" name="login" class="btn btn-success py-2">Masuk</button>
      <a href="/?q=daftar" class="btn btn-outline-secondary py-2">Daftar Akun</a>
    </div>
  </form>
</div>

</body>
</html>
