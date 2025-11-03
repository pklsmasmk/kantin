class ShiftHistory {
    constructor() {
        this.modal = $('#shiftDetailModal');
        this.content = $('#shiftDetailContent');
        this.init();
    }

    init() {
        this.bindEvents();
        console.log('Shift History initialized');
    }

    bindEvents() {
        if (this.modal.length) {
            this.modal.on('click', (e) => {
                if (e.target === this.modal[0]) {
                    this.close();
                }
            });
        }

        $(document).on('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.css('display') === 'block') {
                this.close();
            }
        });
    }

    show(shiftId) {
        const shift = window.shiftHistoryData?.find(item => item.id === shiftId);
        const rekap = window.rekapData?.[shiftId];
        
        if (!shift) {
            console.error('Shift not found:', shiftId);
            return;
        }

        this.renderDetail(shift, rekap);
        this.modal.css('display', 'block');
        $('body').css('overflow', 'hidden');
    }

    close() {
        this.modal.hide();
        $('body').css('overflow', '');
    }

    renderDetail(shift, rekap) {
        const saldo_awal = rekap?.saldo_awal ?? shift.saldo_awal ?? 0;
        const saldo_akhir = rekap?.saldo_akhir ?? shift.saldo_akhir ?? saldo_awal;
        const total_penjualan = rekap?.total_penjualan ?? 0;
        const total_pengeluaran = rekap?.total_pengeluaran ?? 0;
        const total_pemasukan_lain = rekap?.total_pemasukan_lain ?? 0;
        const total_pengeluaran_lain = rekap?.total_pengeluaran_lain ?? 0;
        const selisih = rekap?.selisih ?? (saldo_akhir - saldo_awal);

        this.content.html(`
            <div class="shift-detail">
                <div class="detail-header">
                    <div class="cashdrawer-info">
                        <h4>${this.escapeHtml(shift.cashdrawer)}</h4>
                        <small>Cashdrawer Session</small>
                    </div>
                    <div class="shift-time">
                        <strong>Mulai:</strong> ${this.formatDateTime(shift.waktu)}<br>
                        ${shift.waktu_selesai ? `<strong>Selesai:</strong> ${this.formatDateTime(shift.waktu_selesai)}` : ''}
                    </div>
                </div>

                <div class="user-info">
                    <strong>${this.escapeHtml(shift.nama)}</strong> • ${this.escapeHtml(shift.role)}
                </div>

                <div class="saldo-details">
                    <h5>Informasi Saldo</h5>
                    <div class="saldo-grid">
                        <div class="saldo-item">
                            <span>Saldo Awal</span>
                            <strong>${this.formatRupiah(saldo_awal)}</strong>
                        </div>
                        <div class="saldo-item highlight">
                            <span>Saldo Akhir</span>
                            <strong>${this.formatRupiah(saldo_akhir)}</strong>
                        </div>
                        <div class="saldo-item">
                            <span>Selisih</span>
                            <strong class="${selisih >= 0 ? 'selisih-positif' : 'selisih-negatif'}">
                                ${selisih >= 0 ? '+' : ''}${this.formatRupiah(Math.abs(selisih))}
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="transaction-breakdown">
                    <h5>Rincian Transaksi</h5>
                    <div class="transaction-grid">
                        <div class="transaction-item">
                            <span>Penjualan Tunai</span>
                            <span class="income">${this.formatRupiah(total_penjualan)}</span>
                        </div>
                        <div class="transaction-item">
                            <span>Pengeluaran</span>
                            <span class="expense">${this.formatRupiah(total_pengeluaran)}</span>
                        </div>
                        ${total_pemasukan_lain > 0 ? `
                        <div class="transaction-item">
                            <span>Pemasukan Lain</span>
                            <span class="income">${this.formatRupiah(total_pemasukan_lain)}</span>
                        </div>
                        ` : ''}
                        ${total_pengeluaran_lain > 0 ? `
                        <div class="transaction-item">
                            <span>Pengeluaran Lain</span>
                            <span class="expense">${this.formatRupiah(total_pengeluaran_lain)}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>

                ${rekap ? `
                <div class="sync-info">
                    <span class="sync-status">🔄 Data tersinkronisasi</span>
                    <small>Terakhir update: ${this.formatDateTime(rekap.last_updated || shift.waktu)}</small>
                </div>
                ` : `
                <div class="sync-info">
                    <span class="sync-status not-synced">⏳ Data belum tersinkronisasi</span>
                </div>
                `}

                <div class="detail-actions">
                    <button type="button" class="btn-primary" onclick="shiftHistory.close()">
                        Tutup
                    </button>
                </div>
            </div>
        `);
    }

    escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    formatRupiah(amount) {
        return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
    }

    formatDateTime(dateString) {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

$(function() {
    window.shiftHistory = new ShiftHistory();
    
    window.showShiftDetail = function(shiftId) {
        window.shiftHistory.show(shiftId);
    };
});