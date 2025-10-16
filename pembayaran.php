<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pembayaran - Kantin UAM</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f5f7fa;
      font-family: 'Poppins', sans-serif;
      color: #333;
      min-height: 100vh;
    }

    .navbar {
      background-color: #fff;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      position: sticky;
      top: 0;
      z-index: 10;
    }

    .navbar h5 {
      font-weight: 600;
      color: #198754;
    }

    .total-section {
      font-weight: 600;
      color: #198754;
      font-size: 1rem;
    }

    .section-box {
      background: #fff;
      border-radius: 14px;
      padding: 25px 30px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .display-amount {
      font-size: 2.6rem;
      font-weight: 700;
      color: #198754;
      text-align: center;
      margin: 10px 0 30px;
    }

    label {
      font-weight: 500;
    }

    .form-control,
    .form-select {
      border-radius: 10px;
      padding: 10px;
    }

    .btn-green {
      background-color: #198754;
      color: #fff;
      font-weight: 500;
      border: none;
      border-radius: 10px;
      transition: 0.2s;
    }

    .btn-green:hover {
      background-color: #156b43;
    }

    .btn-light {
      border-radius: 10px;
      font-weight: 500;
      background-color: #f9f9f9;
      border: 1px solid #ddd;
      transition: all 0.2s ease;
    }

    .btn-light:hover {
      background-color: #e9ecef;
    }

    .numpad {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
      margin-bottom: 20px;
    }

    .numpad button {
      height: 70px;
      font-size: 1.4rem;
      border-radius: 12px;
      font-weight: 600;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    }

    #clearInput {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    #backspaceBtn {
      background-color: #fff3cd;
      color: #856404;
      border: 1px solid #ffeeba;
    }

    #confirmBtn {
      font-size: 1.2rem;
      border-radius: 12px;
      background-color: #198754;
      border: none;
      font-weight: 600;
      letter-spacing: 0.3px;
      padding: 12px;
    }

    #confirmBtn:hover {
      background-color: #167448;
      color: #fff;
    }

    .back-link {
      color: #198754;
      font-weight: 500;
      text-decoration: none;
    }

    .back-link:hover {
      text-decoration: underline;
    }

    .quick-buttons button {
      width: 100%;
      font-weight: 500;
      font-size: 1rem;
    }

    @media (max-width: 992px) {
      .display-amount {
        font-size: 2rem;
      }

      .section-box {
        padding: 20px;
      }

      .numpad button {
        height: 60px;
        font-size: 1.2rem;
      }

      .quick-buttons .col-3 {
        width: 50%;
        margin-bottom: 10px;
      }
    }

    @media (max-width: 576px) {
      .numpad {
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
      }

      .numpad button {
        height: 55px;
        font-size: 1.1rem;
      }

      #confirmBtn {
        font-size: 1rem;
      }
    }
  </style>
</head>

<body>

  <nav class="navbar px-4 py-3">
    <div class="d-flex w-100 justify-content-between align-items-center">
      <a href="index.php" class="back-link">&larr; Kembali</a>
      <h5 class="mb-0">Pembayaran</h5>
      <div class="total-section">TOTAL: <span id="totalNav">Rp 0</span></div>
    </div>
  </nav>

  <div class="container-fluid mt-4 mb-5 px-4">
    <div class="row g-4">

      <div class="col-lg-8 col-md-7">
        <div class="section-box">
          <div class="display-amount" id="displayTotal">Rp 0</div>
          <div class="text-center mb-3">
            <div class="fs-5">Uang Diterima:</div>
            <div class="display-amount" id="inputBayar">0</div>
          </div>

          <form id="formPembayaran">
            <div class="row g-3">
              <div class="col-md-6">
                <label for="namaPelanggan">Nama Pelanggan</label>
                <input type="text" id="namaPelanggan" class="form-control" placeholder="Masukkan nama">
              </div>
              <div class="col-md-4">
                <label for="metodePembayaran">Metode Pembayaran</label>
                <select id="metodePembayaran" class="form-select">
                  <option value="Cash">Cash</option>
                  <option value="QRIS">QRIS</option>
                  <option value="Transfer">Transfer</option>
                  <option value="Piutang">Piutang</option>
                </select>
              </div>
              <div class="col-md-12">
                <label for="keterangan">Keterangan</label>
                <textarea id="keterangan" class="form-control" rows="2" placeholder="Tambahkan catatan (opsional)"></textarea>
              </div>
            </div>

            <div id="uangSection" class="mt-4">
              <div class="row quick-buttons g-2">
                <div class="col-6 col-md-3">
                  <button type="button" class="btn btn-green w-100" id="btnPas">Uang Pas</button>
                </div>
                <div class="col-6 col-md-3">
                  <button type="button" class="btn btn-green w-100 btnQuick" data-value="20000">Rp 20.000</button>
                </div>
                <div class="col-6 col-md-3">
                  <button type="button" class="btn btn-green w-100 btnQuick" data-value="50000">Rp 50.000</button>
                </div>
                <div class="col-6 col-md-3">
                  <button type="button" class="btn btn-green w-100 btnQuick" data-value="100000">Rp 100.000</button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div class="col-lg-4 col-md-5">
        <div class="section-box text-center">
          <div class="numpad">
            <button class="btn btn-light numKey">7</button>
            <button class="btn btn-light numKey">8</button>
            <button class="btn btn-light numKey">9</button>
            <button class="btn btn-light numKey">4</button>
            <button class="btn btn-light numKey">5</button>
            <button class="btn btn-light numKey">6</button>
            <button class="btn btn-light numKey">1</button>
            <button class="btn btn-light numKey">2</button>
            <button class="btn btn-light numKey">3</button>
            <button class="btn btn-light numKey">0</button>
            <button class="btn btn-light numKey">000</button>
            <button class="btn" id="clearInput">C</button>
            <button class="btn" id="backspaceBtn">←</button>
          </div>
          <button class="btn btn-success w-100 mt-3" id="confirmBtn">Konfirmasi Pembayaran</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="pembayaran.js"></script>
</body>

</html>
