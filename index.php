<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kantin UAM - Kasir</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="CSS/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">
</head>

<body class="bg-light">
  <nav class="navbar navbar-dark bg-dark shadow-sm fixed-top">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <div class="d-flex align-items-center">
        <button class="btn btn-outline-light me-2" type="button" data-bs-toggle="offcanvas"
          data-bs-target="#sidebarMenu">
          <i class="bi bi-list"></i>
        </button>
        <a class="navbar-brand fw-bold text-white" href="#">
          <i class="bi bi-shop me-1 text-info"></i>Kantin<small class="text-info fst-italic">UAM</small>
        </a>
      </div>
      <div>
        <button class="btn btn-outline-light position-relative">
          <i class="bi bi-cart3 fs-5"></i>
          <span id="cartCount"
            class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle">0</span>
        </button>
      </div>
    </div>
  </nav>

  <?php
  include("layout/sidemenu.php");
  ?>

  <main class="container-fluid mt-5 pt-3">
    <?php
      switch ($_GET["q"] ?? "") {
        case 'dungo':
          include("konten/dungo.php");
          break;
        case 'retur':
          include("retur/index.php");
          break;
        case 'shift':
          include("shift/index.php");
          break;

        default:
          include("konten/ori.php");
          break;
      }
    ?>
  </main>

  <div style="height: 50px;"></div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script>AOS.init({ duration: 700, once: true });</script>
  <script src="js/script.js"></script>
</body>

</html>