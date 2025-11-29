<nav class="navbar px-4 py-3">
  <div class="d-flex w-100 justify-content-between align-items-center">
    <a href="/?q=menu" class="back-link">&larr; Kembali</a>
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
          <div class="change-display" id="changeDisplay" style="display: none;"></div>
        </div>

        <div class="mb-4" id="uangInputSection">  
          <label for="inputUangManual" class="form-label fw-bold">Input Jumlah Uang</label>
          <input type="text" id="inputUangManual" class="form-control form-control-lg"
            placeholder="Masukkan jumlah uang" data-next="namaPelanggan">
        </div>

        <form id="formPembayaran">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="namaPelanggan" class="form-label fw-bold">Nama Pelanggan</label>
              <input type="text" id="namaPelanggan" class="form-control" placeholder="Masukkan nama"
                data-next="metodePembayaran">
            </div>
            <div class="col-md-6">
              <label for="metodePembayaran" class="form-label fw-bold">Metode Pembayaran</label>
              <select id="metodePembayaran" class="form-select" data-next="keterangan">
                <option value="Cash">Cash</option>
                <option value="Piutang">Piutang</option>
              </select>
            </div>
            <div class="col-12">
              <label for="keterangan" class="form-label fw-bold">Keterangan</label>
              <textarea id="keterangan" class="form-control" rows="2" placeholder="Tambahkan catatan (opsional)"
                data-next="confirmBtn"></textarea>
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

    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header border-0 pb-0">
            <div class="success-icon mx-auto">
              <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                <circle cx="32" cy="32" r="32" fill="#198754" opacity="0.1" />
                <circle cx="32" cy="32" r="24" fill="#198754" opacity="0.2" />
                <circle cx="32" cy="32" r="16" fill="#198754" />
                <path d="M26.6667 32L31.3333 36.6667L38.6667 29.3333" stroke="white" stroke-width="3"
                  stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </div>
          </div>
          <div class="modal-body text-center pt-0">
            <h5 class="modal-title fw-bold text-success mb-3" id="successModalLabel">Pembayaran Berhasil!</h5>

            <div class="payment-details mb-4">
              <div class="detail-item d-flex justify-content-between py-2">
                <span class="text-muted">Total:</span>
                <span class="fw-bold" id="modalTotal">Rp 0</span>
              </div>
              <div class="detail-item d-flex justify-content-between py-2">
                <span class="text-muted">Dibayar:</span>
                <span class="fw-bold" id="modalDibayar">Rp 0</span>
              </div>
              <div class="detail-item d-flex justify-content-between py-2">
                <span class="text-muted">Kembalian:</span>
                <span class="fw-bold text-success" id="modalKembalian">Rp 0</span>
              </div>
              <hr class="my-2">
              <div class="detail-item d-flex justify-content-between py-2">
                <span class="text-muted">Metode:</span>
                <span class="fw-bold" id="modalMetode">-</span>
              </div>
              <div class="detail-item d-flex justify-content-between py-2">
                <span class="text-muted">Waktu:</span>
                <span class="fw-bold" id="modalWaktu">-</span>
              </div>
            </div>

            <button type="button" class="btn btn-success w-100 py-3 fw-bold" id="modalCloseBtn">
              Selesai
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="piutangModal" tabindex="-1" aria-labelledby="piutangModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header border-0 pb-0">
            <div class="warning-icon mx-auto">
              <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
                <circle cx="32" cy="32" r="32" fill="#ffc107" opacity="0.1" />
                <circle cx="32" cy="32" r="24" fill="#ffc107" opacity="0.2" />
                <circle cx="32" cy="32" r="16" fill="#ffc107" />
                <path d="M32 24V32M32 40H32.02" stroke="white" stroke-width="3" stroke-linecap="round"
                  stroke-linejoin="round" />
              </svg>
            </div>
          </div>
          <div class="modal-body text-center pt-0">
            <h5 class="modal-title fw-bold text-warning mb-3" id="piutangModalLabel">Piutang Disimpan</h5>

            <div class="payment-details mb-4">
              <div class="detail-item d-flex justify-content-between py-2">
                <span class="text-muted">Pelanggan:</span>
                <span class="fw-bold" id="modalPiutangNama">-</span>
              </div>
              <div class="detail-item d-flex justify-content-between py-2">
                <span class="text-muted">Total Piutang:</span>
                <span class="fw-bold text-warning" id="modalPiutangTotal">Rp 0</span>
              </div>
              <div class="detail-item d-flex justify-content-between py-2">
                <span class="text-muted">Keterangan:</span>
                <span class="fw-bold" id="modalPiutangKet">-</span>
              </div>
              <div class="detail-item d-flex justify-content-between py-2">
                <span class="text-muted">Waktu:</span>
                <span class="fw-bold" id="modalPiutangWaktu">-</span>
              </div>
            </div>

            <div class="d-flex gap-2">
              <button type="button" class="btn btn-outline-secondary flex-fill" id="modalPiutangCloseBtn">
                Tutup
              </button>
              <button type="button" class="btn btn-warning flex-fill text-white" id="modalPiutangListBtn">
                Lihat Piutang
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>