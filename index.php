<?php
session_start();
?>
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
  <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200">
  <style>
    .dropdown-container {
      display: inline-block;
      padding: 10px;

      .dropdown {
        position: relative;

        &[open] .with-down-arrow::after {
          content: "\e5c7";
        }

        &[open] summary {
          background: #ffffff10;
        }

        summary {
          list-style: none;
          display: inline-block;
          cursor: pointer;
          border-radius: 6px;

          &.avatar {
            border-radius: 50px;

            img {
              width: 40px;
              height: 40px;
              border-radius: 50px;
              display: inline-block;
              margin: 0;
            }
          }

          .with-down-arrow {
            display: inline-flex;
            padding: 5px;
            align-items: center;
            color: #fff;
            line-height: 1;

            &::after {
              content: "\e5c5";
              font-family: "Material Symbols Outlined";
              font-weight: normal;
              font-style: normal;
              font-size: 1.5rem;
              line-height: 1;
              letter-spacing: normal;
              text-transform: none;
              display: inline-block;
              white-space: nowrap;
              word-wrap: normal;
              direction: ltr;
              -webkit-font-smoothing: antialiased;
            }
          }
        }

        &.left ul {
          left: 0;
        }

        &.right ul {
          right: 0;
        }

        ul {
          padding: 0;
          margin: 0;
          box-shadow: 0 0 10px #00000030;
          min-width: max-content;
          position: absolute;
          top: 100%;
          border-radius: 10px;
          background-color: #fff;
          z-index: 2;

          li {
            list-style-type: none;
            display: block;
            /* If you use divider & borders, it's best to use top borders */
            /*border-top: 1px solid #ccc;*/

            &:first-of-type {
              border: none;
              background-color: #f2f2f2;
            }

            p {
              padding: 10px 15px;
              margin: 0;
            }

            a {
              display: flex;
              align-items: center;
              justify-content: flex-start;
              padding: 10px 15px;
              text-decoration: none;
              line-height: 1;
              color: #333;

              &:hover {
                color: #ff34b2;
              }
            }

            &:first-of-type {
              border-radius: 10px 10px 0 0;
            }

            &:last-of-type {
              border-radius: 0 0 10px 10px;
            }

            &.divider {
              border: none;
              border-bottom: 1px solid #333;

              /* 
           * removes border from Li after the divider element
           * best used in combination with top borders on other LIs 
           */
              &~li {
                border: none;
              }
            }
          }
        }
      }
    }

    .block {
      display: block;
    }
  </style>
  <?php
    include("config/pilihanappcss.php");
  ?>
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
      <div class="d-flex align-items-center">
        <button class="btn btn-outline-light position-relative me-2">
          <i class="bi bi-cart3 fs-5"></i>
          <span id="cartCount"
            class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle">0</span>
        </button>
        <?php
        if (!isset($_SESSION["username"])) {


          ?>
          <a href="/?q=login" class="btn btn-info text-white fw-semibold">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
          </a>
          <?php
        } else {


          ?>
          <div class="dropdown-container">
            <details class="dropdown right">
              <summary class="avatar">
                <img src="https://gravatar.com/avatar/00000000000000000000000000000000?d=mp">
              </summary>
              <ul>
                <!-- Optional: user details area w/ gray bg -->
                <li>
                  <p>
                    <span class="block bold"><?= $_SESSION['namalengkap'] ?></span>
                    <span class="block italic"><?= $_SESSION['email'] ?></span>
                  </p>
                </li>
                <!-- Menu links -->
                <li>
                  <a href="#">
                    <span class="material-symbols-outlined">account_circle</span> Account
                  </a>
                </li>
                <li>
                  <a href="#">
                    <span class="material-symbols-outlined">settings</span> Settings
                  </a>
                </li>
                <li>
                  <a href="#">
                    <span class="material-symbols-outlined">help</span> Help
                  </a>
                </li>
                <!-- Optional divider -->
                <li class="divider"></li>
                <li>
                  <a href="/?q=logout">
                    <span class="material-symbols-outlined">logout</span> Logout
                  </a>
                </li>
              </ul>
            </details>
          </div>
          <?php
        }
        ?>
      </div>
    </div>
  </nav>

  <?php
  include("layout/sidemenu.php");
  ?>

  <main class="container-fluid mt-5 pt-3">
    <?php
    include("config/pilihanapphtml.php");
    ?>
  </main>

  <div style="height: 50px;"></div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
  <script>
    AOS.init({
      duration: 700,
      once: true
    });
  </script>
  <script src="js/script.js"></script>
      <?php
    include("config/pilihanappjs.php");
    ?>
</body>

</html>