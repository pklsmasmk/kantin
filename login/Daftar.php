<?php
if (session_status() === PHP_SESSION_NONE)
    session_start();

include "../Database/config.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $passwordPlain = $_POST['password'];
    $passwordHash = password_hash($passwordPlain, PASSWORD_DEFAULT);
    $nama = 'karyawan';
    $namalengkap = trim($_POST['namalengkap']);

    $cek = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
    $cek->execute(['username' => $username, 'email' => $email]);

    if ($cek->rowCount() > 0) {
        echo "<script>alert('Username atau Email sudah digunakan!'); window.location='/?q=daftar';</script>";
    } else {
        $insert = $pdo->prepare("INSERT INTO users (username, email, password, nama, namalengkap, id_role) VALUES (:username, :email, :password, :nama, :namalengkap, 2)");
        $insert->execute([
            'username' => $username,
            'email' => $email,
            'password' => $passwordHash,
            'nama' => $nama,
            'namalengkap' => $namalengkap
        ]);

        echo "<script>alert('Pendaftaran berhasil! Silakan login.'); window.location='/?q=login';</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Kantin UAM</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      font-family: 'Poppins', sans-serif;
      height: 100vh;
      width: 100vw;
      overflow: hidden;
      display: flex;
    }

    .signup-container {
      display: flex;
      width: 100%;
      height: 100vh;
    }

    .signup-left {
      flex: 1;
      background: linear-gradient(135deg, #1abc9c, #16a085);
      color: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      padding: 40px;
    }

    .signup-left h3 {
      font-weight: 700;
      margin-bottom: 10px;
      font-size: 2rem;
    }

    .signup-left p {
      font-size: 1rem;
      margin-bottom: 25px;
      max-width: 300px;
    }

    .btn-login {
      background-color: #fff;
      color: #16a085;
      border: none;
      border-radius: 25px;
      padding: 10px 40px;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.3s;
    }

    .btn-login:hover {
      background-color: #f1f1f1;
      transform: scale(1.05);
    }

    .signup-right {
      flex: 1;
      background: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 50px;
    }

    .signup-right h2 {
      font-weight: 700;
      margin-bottom: 10px;
      color: #222;
    }

    .signup-right p {
      color: #777;
      margin-bottom: 20px;
    }

    form {
      width: 100%;
      max-width: 350px;
    }

    form input {
      border-radius: 25px;
      padding: 12px 20px;
      border: 1px solid #ddd;
      margin-bottom: 15px;
      width: 100%;
      outline: none;
    }

    form input:focus {
      border-color: #20c997;
      box-shadow: 0 0 5px rgba(32,201,151,0.3);
    }

    .btn-signup {
      border-radius: 25px;
      background: linear-gradient(45deg, #28a745, #20c997);
      border: none;
      color: #fff;
      font-weight: 500;
      padding: 12px;
      width: 100%;
    }

    .btn-signup:hover {
      opacity: 0.9;
    }

    @media (max-width: 900px) {
      .signup-container {
        flex-direction: column;
      }
      .signup-left {
        height: 40%;
      }
      .signup-right {
        height: 60%;
      }
    }
  </style>
</head>
<body>

<div class="signup-container">
  <div class="signup-left">
    <h3>Sudah punya akun?</h3>
    <p>Klik tombol dibawah ini untuk login ke akun anda</p>
    <a href="/?q=login" class="btn-login">Sign In</a>
  </div>

  <div class="signup-right">
    <h2>Buat akun</h2>

    <form method="POST" action="">
      <input type="text" name="namalengkap" placeholder="Nama Lengkap" required>
      <input type="text" name="username" placeholder="Username" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" name="daftar" class="btn-signup">Sign Up</button>
    </form>
  </div>
</div>

</body>
</html>
