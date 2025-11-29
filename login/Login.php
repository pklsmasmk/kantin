<?php

if (session_status() === PHP_SESSION_NONE)
    session_start();

include "../Database/config.php";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Perbaikan: bind parameter untuk kedua kondisi
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $username); // Bind parameter kedua
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
  <title>Login Kantin Pintar</title>
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

    .login-container {
      display: flex;
      width: 100%;
      height: 100vh;
    }

    .login-left {
      flex: 1;
      background: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 50px;
    }

    .login-left h2 {
      font-weight: 700;
      margin-bottom: 10px;
      color: #222;
    }

    .login-left p {
      color: #777;
      margin-bottom: 20px;
    }

    .social-login {
      display: flex;
      gap: 15px;
      justify-content: center;
      margin-bottom: 25px;
    }

    .social-login a {
      width: 45px;
      height: 45px;
      display: flex;
      justify-content: center;
      align-items: center;
      border-radius: 50%;
      color: #fff;
      text-decoration: none;
      font-size: 20px;
    }

    .social-login .facebook { background-color: #3b5998; }
    .social-login .google { background-color: #db4437; }
    .social-login .linkedin { background-color: #0e76a8; }

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

    .btn-login {
      border-radius: 25px;
      background: linear-gradient(45deg, #28a745, #20c997);
      border: none;
      color: #fff;
      font-weight: 500;
      padding: 12px;
      width: 100%;
    }

    .btn-login:hover {
      opacity: 0.9;
    }

    .login-right {
    flex: 1;
    background: url("/img/uam5.jpg") no-repeat center center;
    background-size: cover;
    color: #fff;
    display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      padding: 40px;
      position: relative;
    }

    .login-right h3 {
      font-weight: 700;
      margin-bottom: 10px;
      font-size: 2rem;
    }

    .login-right p {
      font-size: 1rem;
      margin-bottom: 25px;
      max-width: 300px;
    }

    .btn-signup {
      background-color: #fff;
      color: #16a085;
      border: none;
      border-radius: 25px;
      padding: 10px 40px;
      font-weight: 500;
      text-decoration: none;
      transition: all 0.3s;
      display: inline-block;
    }

    .btn-signup:hover {
      background-color: #f1f1f1;
      transform: scale(1.05);
      text-decoration: none;
      color: #16a085;
    }

    @media (max-width: 900px) {
      .login-container {
        flex-direction: column;
      }
      .login-right {
        height: 40%;
      }
      .login-left {
        height: 60%;
      }
    }
  </style>
</head>
<body>

<div class="login-container">
  <div class="login-left">
    <h2>Login Kantin UAM</h2>

    <form method="POST" action="">
      <input type="text" name="username" placeholder="Email or Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" name="login" class="btn-login">Sign In</button>
    </form>
  </div>

  <div class="login-right">
    <h3>Belum ada akun?</h3>
    <p>Klik disini untuk daftar</p>
    <a href="/?q=daftar" class="btn-signup">daftar</a>
  </div>
</div>
</body>
</html>