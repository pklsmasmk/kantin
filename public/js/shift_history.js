class ShiftHistory {
    constructor() {
        this.modal = $('#shiftDetailModal');
        this.content = $('#shiftDetailContent');
        this.isLoading = false;
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

        $(document).on('keydown.shiftHistory', (e) => {
            if (e.key === 'Escape' && this.modal.is(':visible')) {
                this.close();
            }
        });

        this.modal.find('.modal-content').on('click', (e) => {
            e.stopPropagation();
        });
    }

    async show(shiftId) {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();

        try {
            let shift = $.grep(window.shiftHistoryData || [], item => item.id === shiftId)[0];
            let rekap = window.rekapData?.[shiftId];
            
            if (!shift) {
                console.warn('Shift data not found in cache, fetching from server...');
                const result = await this.fetchShiftDetail(shiftId);
                if (result) {
                    shift = result.shift;
                    rekap = result.rekap;
                }
            }

            if (!shift) {
                this.showError('Data shift tidak ditemukan');
                return;
            }

            this.renderDetail(shift, rekap);
            this.modal.show();
            $('body').css('overflow', 'hidden');
            
        } catch (error) {
            console.error('Error showing shift detail:', error);
            this.showError('Terjadi kesalahan saat memuat data');
        } finally {
            this.isLoading = false;
        }
    }

    async fetchShiftDetail(shiftId) {
        try {
            const params = $.param({
                action: 'get_shift_detail',
                shift_id: shiftId
            });
            
            const response = await fetch(`PHP/Back_shift.php?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                return {
                    shift: data.shift,
                    rekap: data.rekap
                };
            } else {
                throw new Error(data.message || 'Failed to fetch shift detail');
            }
            
        } catch (error) {
            console.error('Error fetching shift detail:', error);
            return null;
        }
    }

    close() {
        this.modal.hide();
        $('body').css('overflow', '');
        this.content.empty();
        
        $(document).off('keydown.shiftHistory');
    }

    showLoading() {
        this.content.html(`
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Memuat data shift...</p>
            </div>
        `);
        this.modal.show();
    }

    showError(message) {
        this.content.html(`
            <div class="error-state">
                <div class="error-icon">⚠️</div>
                <h4>Gagal Memuat Data</h4>
                <p>${this.escapeHtml(message)}</p>
                <button type="button" class="btn-retry" onclick="shiftHistory.close()">
                    Tutup
                </button>
            </div>
        `);
        this.modal.show();
    }

    renderDetail(shift, rekap) {
        const shiftTitle = "Riwayat Shift";

        $('#modalShiftTitle').text(`Detail Shift: ${shiftTitle}`);

        const saldo_awal = this.parseNumber(rekap?.saldo_awal ?? shift.saldo_awal ?? 0);
        const saldo_akhir = this.parseNumber(rekap?.saldo_akhir ?? shift.saldo_akhir ?? saldo_awal);
        
        const total_penjualan = this.parseNumber(rekap?.total_penjualan ?? 0);
        const total_pengeluaran = this.parseNumber(rekap?.total_pengeluaran ?? 0);
        const total_pemasukan_lain = this.parseNumber(rekap?.total_pemasukan_lain ?? 0);
        const total_pengeluaran_lain = this.parseNumber(rekap?.total_pengeluaran_lain ?? 0);
        
        const selisih = saldo_akhir - saldo_awal;
        
        const duration = this.calculateDuration(shift.waktu_mulai, shift.waktu_selesai);

        const total_pemasukan = total_penjualan + total_pemasukan_lain;
        const total_pengeluaran_all = total_pengeluaran + total_pengeluaran_lain;

        const selisih_teoritis = saldo_awal + total_pemasukan - total_pengeluaran_all - saldo_akhir;

        this.content.html(`
            <div class="shift-detail">
                <!-- REVISI: Header sederhana dengan judul konsisten -->
                <div class="detail-header">
                    <div class="shift-info">
                        <h4>Detail Shift</h4>
                        <small>ID: ${this.escapeHtml(shift.id)}</small>
                    </div>
                    <div class="shift-status ${shift.waktu_selesai ? 'completed' : 'active'}">
                        ${shift.waktu_selesai ? '✅ Selesai' : '🟢 Aktif'}
                    </div>
                </div>

                <div class="user-info">
                    <strong>${this.escapeHtml(shift.nama)}</strong> • ${this.escapeHtml(shift.role)}
                </div>

                <div class="time-info">
                    <div class="time-item">
                        <strong>Mulai:</strong> ${this.formatDateTime(shift.waktu_mulai)}
                    </div>
                    ${shift.waktu_selesai ? `
                    <div class="time-item">
                        <strong>Selesai:</strong> ${this.formatDateTime(shift.waktu_selesai)}
                    </div>
                    <div class="time-item">
                        <strong>Durasi:</strong> ${duration}
                    </div>
                    ` : ''}
                </div>

                <div class="saldo-details">
                    <h5>Informasi Saldo</h5>
                    <div class="saldo-grid">
                        <div class="saldo-item">
                            <span class="saldo-label">Saldo Awal</span>
                            <strong class="saldo-value">${this.formatRupiah(saldo_awal)}</strong>
                            <small class="saldo-numeric">${this.formatNumber(saldo_awal)}</small>
                        </div>
                        <div class="saldo-item highlight">
                            <span class="saldo-label">Saldo Akhir</span>
                            <strong class="saldo-value">${this.formatRupiah(saldo_akhir)}</strong>
                            <small class="saldo-numeric">${this.formatNumber(saldo_akhir)}</small>
                        </div>
                        <div class="saldo-item">
                            <span class="saldo-label">Selisih Kas</span>
                            <strong class="saldo-value ${selisih >= 0 ? 'selisih-positif' : 'selisih-negatif'}">
                                ${selisih >= 0 ? '+' : ''}${this.formatRupiah(Math.abs(selisih))}
                            </strong>
                            <small class="saldo-numeric ${selisih >= 0 ? 'selisih-positif' : 'selisih-negatif'}">
                                ${selisih >= 0 ? '+' : ''}${this.formatNumber(Math.abs(selisih))}
                            </small>
                        </div>
                    </div>
                </div>

                ${(total_pemasukan > 0 || total_pengeluaran_all > 0) ? `
                <div class="cash-flow-summary">
                    <h5>Ringkasan Arus Kas</h5>
                    <div class="cash-flow-grid">
                        ${total_pemasukan > 0 ? `
                        <div class="cash-flow-item income">
                            <span class="cash-flow-label">Total Pemasukan</span>
                            <strong class="cash-flow-value">${this.formatRupiah(total_pemasukan)}</strong>
                            <div class="cash-flow-breakdown">
                                ${total_penjualan > 0 ? `<small>Penjualan: ${this.formatRupiah(total_penjualan)}</small>` : ''}
                                ${total_pemasukan_lain > 0 ? `<small>Pemasukan Lain: ${this.formatRupiah(total_pemasukan_lain)}</small>` : ''}
                            </div>
                        </div>
                        ` : ''}
                        
                        ${total_pengeluaran_all > 0 ? `
                        <div class="cash-flow-item expense">
                            <span class="cash-flow-label">Total Pengeluaran</span>
                            <strong class="cash-flow-value">${this.formatRupiah(total_pengeluaran_all)}</strong>
                            <div class="cash-flow-breakdown">
                                ${total_pengeluaran > 0 ? `<small>Pengeluaran: ${this.formatRupiah(total_pengeluaran)}</small>` : ''}
                                ${total_pengeluaran_lain > 0 ? `<small>Pengeluaran Lain: ${this.formatRupiah(total_pengeluaran_lain)}</small>` : ''}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                </div>
                ` : `
                <div class="no-cashflow">
                    <p>Tidak ada data arus kas</p>
                    <small>Data pemasukan dan pengeluaran tidak tersedia untuk shift ini.</small>
                </div>
                `}

                ${(total_penjualan > 0 || total_pengeluaran > 0 || total_pemasukan_lain > 0 || total_pengeluaran_lain > 0) ? `
                <div class="transaction-breakdown">
                    <h5>Rincian Transaksi</h5>
                    <div class="transaction-grid">
                        ${total_penjualan > 0 ? `
                        <div class="transaction-item">
                            <span>Total Penjualan Tunai</span>
                            <span class="amount income">+${this.formatRupiah(total_penjualan)}</span>
                        </div>
                        ` : ''}
                        ${total_pengeluaran > 0 ? `
                        <div class="transaction-item">
                            <span>Total Pengeluaran Operasional</span>
                            <span class="amount expense">-${this.formatRupiah(total_pengeluaran)}</span>
                        </div>
                        ` : ''}
                        ${total_pemasukan_lain > 0 ? `
                        <div class="transaction-item">
                            <span>Pemasukan Lainnya</span>
                            <span class="amount income">+${this.formatRupiah(total_pemasukan_lain)}</span>
                        </div>
                        ` : ''}
                        ${total_pengeluaran_lain > 0 ? `
                        <div class="transaction-item">
                            <span>Pengeluaran Lainnya</span>
                            <span class="amount expense">-${this.formatRupiah(total_pengeluaran_lain)}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
                ` : `
                <div class="no-transactions">
                    <p>Tidak ada data transaksi terperinci</p>
                    <small>Data transaksi penjualan dan pengeluaran tidak tersedia untuk shift ini.</small>
                </div>
                `}

                <div class="financial-analysis">
                    <h5>Analisis Keuangan</h5>
                    <div class="analysis-grid">
                        <div class="analysis-item">
                            <span>Laba/Rugi Kotor</span>
                            <strong class="${total_pemasukan - total_pengeluaran_all >= 0 ? 'positive' : 'negative'}">
                                ${total_pemasukan - total_pengeluaran_all >= 0 ? '+' : ''}${this.formatRupiah(total_pemasukan - total_pengeluaran_all)}
                            </strong>
                        </div>
                        <div class="analysis-item">
                            <span>Selisih Teoritis</span>
                            <strong class="${Math.abs(selisih_teoritis) <= 100 ? 'balanced' : 'unbalanced'}">
                                ${selisih_teoritis >= 0 ? '+' : ''}${this.formatRupiah(selisih_teoritis)}
                            </strong>
                        </div>
                    </div>
                    ${Math.abs(selisih_teoritis) > 100 ? `
                    <div class="variance-warning">
                        <small>Terdapat selisih antara perhitungan teoritis dan saldo aktual: ${this.formatRupiah(selisih_teoritis)}</small>
                        <br>
                        <small>Perhitungan: Saldo Awal (${this.formatNumber(saldo_awal)}) + Pemasukan (${this.formatNumber(total_pemasukan)}) - Pengeluaran (${this.formatNumber(total_pengeluaran_all)}) - Saldo Akhir (${this.formatNumber(saldo_akhir)})</small>
                    </div>
                    ` : ''}
                </div>

                <div class="data-source-info">
                    <small>
                        <strong>Sumber Data:</strong> ${rekap ? 'Rekap Shift' : 'Shift Lokal'} • 
                        <strong>Periode:</strong> ${this.formatDateTime(shift.waktu_mulai)} ${shift.waktu_selesai ? ' - ' + this.formatDateTime(shift.waktu_selesai) : ''}
                    </small>
                </div>

                <div class="sync-info ${rekap ? 'synced' : 'not-synced'}">
                    ${rekap ? `
                    <span class="sync-status">Data tersinkronisasi dengan rekap</span>
                    <small>Terakhir update: ${this.formatDateTime(rekap.last_updated || shift.waktu_mulai)}</small>
                    ` : `
                    <span class="sync-status">Data belum tersinkronisasi</span>
                    <small>Data hanya tersimpan di shift lokal</small>
                    `}
                </div>

                <div class="detail-actions">
                    <button type="button" class="btn btn-secondary" onclick="shiftHistory.close()">
                        Tutup
                    </button>
                    ${!rekap ? `
                    <button type="button" class="btn btn-primary" onclick="shiftHistory.syncShift('${shift.id}')">
                        Sinkronisasi ke Rekap
                    </button>
                    ` : ''}
                    <button type="button" class="btn btn-info" onclick="shiftHistory.exportShift('${shift.id}')">
                        Export Laporan
                    </button>
                </div>
            </div>
        `);
    }

    parseNumber(value) {
        if (value === null || value === undefined) return 0;
        if (typeof value === 'number') return value;
        if (typeof value === 'string') {
            const cleaned = value.toString().replace(/[^\d.-]/g, '');
            const parsed = parseFloat(cleaned);
            return isNaN(parsed) ? 0 : parsed;
        }
        
        return 0;
    }

    calculateDuration(startTime, endTime) {
        if (!startTime) return '-';
        
        const start = new Date(startTime);
        const end = endTime ? new Date(endTime) : new Date();
        
        const diffMs = end - start;
        const hours = Math.floor(diffMs / (1000 * 60 * 60));
        const minutes = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
        
        if (hours > 0) {
            return `${hours} jam ${minutes} menit`;
        } else {
            return `${minutes} menit`;
        }
    }

    async syncShift(shiftId) {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showLoading();

        try {
            const params = $.param({
                action: 'sync_shift',
                shift_id: shiftId
            });
            
            const response = await fetch(`PHP/Back_shift.php?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                const result = await this.fetchShiftDetail(shiftId);
                if (result) {
                    this.renderDetail(result.shift, result.rekap);
                    if (window.rekapData) {
                        window.rekapData[shiftId] = result.rekap;
                    }
                    
                    this.showSuccess('Shift berhasil disinkronisasi dengan rekap');
                }
            } else {
                throw new Error(data.message || 'Gagal menyinkronisasi shift');
            }
            
        } catch (error) {
            console.error('Error syncing shift:', error);
            this.showError('Gagal menyinkronisasi shift: ' + error.message);
        } finally {
            this.isLoading = false;
        }
    }

    async exportShift(shiftId) {
        try {
            const shift = $.grep(window.shiftHistoryData || [], item => item.id === shiftId)[0];
            const rekap = window.rekapData?.[shiftId];
            
            if (!shift) {
                alert('Data shift tidak ditemukan untuk export');
                return;
            }

            const csvContent = this.generateCSV(shift, rekap);
            this.downloadCSV(csvContent, `shift_${shiftId}_${new Date().toISOString().split('T')[0]}.csv`);
            
        } catch (error) {
            console.error('Error exporting shift:', error);
            alert('Gagal mengexport data shift');
        }
    }

    generateCSV(shift, rekap) {
        const shiftTitle = "Riwayat Shift";

        const saldo_awal = this.parseNumber(rekap?.saldo_awal ?? shift.saldo_awal ?? 0);
        const saldo_akhir = this.parseNumber(rekap?.saldo_akhir ?? shift.saldo_akhir ?? saldo_awal);
        const total_penjualan = this.parseNumber(rekap?.total_penjualan ?? 0);
        const total_pengeluaran = this.parseNumber(rekap?.total_pengeluaran ?? 0);
        const selisih = saldo_akhir - saldo_awal;
        
        const headers = ['Item', 'Nilai'];
        
        const rows = [
            ['ID Shift', shift.id],
            ['Jenis Shift', shiftTitle],
            ['Kasir', shift.nama],
            ['Role', shift.role],
            ['Waktu Mulai', this.formatDateTime(shift.waktu_mulai)],
            ['Waktu Selesai', shift.waktu_selesai ? this.formatDateTime(shift.waktu_selesai) : '-'],
            ['', ''],
            ['SALDO AWAL', this.formatNumber(saldo_awal)],
            ['SALDO AWAL (Format)', this.formatRupiah(saldo_awal)],
            ['SALDO AKHIR', this.formatNumber(saldo_akhir)],
            ['SALDO AKHIR (Format)', this.formatRupiah(saldo_akhir)],
            ['SELISIH KAS', this.formatNumber(selisih)],
            ['SELISIH KAS (Format)', this.formatRupiah(selisih)],
            ['', ''],
            ['TOTAL PENJUALAN', this.formatNumber(total_penjualan)],
            ['TOTAL PENGELUARAN', this.formatNumber(total_pengeluaran)],
            ['LABA/RUGI', this.formatNumber(total_penjualan - total_pengeluaran)],
            ['', ''],
            ['Tanggal Export', new Date().toLocaleDateString('id-ID')],
            ['Sumber Data', rekap ? 'Rekap' : 'Lokal']
        ];
        
        return [headers, ...rows].map(row => row.join(',')).join('\n');
    }

    downloadCSV(csvContent, filename) {
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    showSuccess(message) {
        const successDiv = $('<div>', {
            class: 'alert alert-success',
            css: {
                position: 'fixed',
                top: '20px',
                right: '20px',
                zIndex: '10001',
                padding: '12px 16px',
                borderRadius: '6px',
                background: '#d4edda',
                color: '#155724',
                border: '1px solid #c3e6cb'
            },
            text: message
        });
        
        $('body').append(successDiv);
        setTimeout(() => successDiv.remove(), 3000);
    }

    escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    formatRupiah(amount) {
        const num = this.parseNumber(amount);
        return 'Rp ' + Math.round(num).toLocaleString('id-ID');
    }

    formatNumber(amount) {
        const num = this.parseNumber(amount);
        return Math.round(num).toLocaleString('id-ID');
    }

    formatDateTime(dateString) {
        if (!dateString) return '-';
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (error) {
            return dateString;
        }
    }
}

$(document).ready(function() {
    window.shiftHistory = new ShiftHistory();
    window.showShiftDetail = function(shiftId) {
        window.shiftHistory.show(shiftId);
    };
});