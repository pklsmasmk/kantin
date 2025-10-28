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

