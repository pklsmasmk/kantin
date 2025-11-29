class RekapDetailManager {
    constructor() {
        this.init();
    }

    init() {
        console.log('Rekap Detail Manager initialized');
        this.setupEventListeners();
        this.autoCloseAlerts();
        this.setupRupiahFormatting();
        this.setupTransactionTypeHandling();
        
        if (window.transaksiData) {
            console.log('Transaction data loaded:', window.transaksiData);
        }
    }

    setupEventListeners() {
        $(document).on('click', (e) => {
            if ($(e.target).hasClass('modal')) {
                this.closeAllModals();
            }
        });

        $(document).on('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });

        this.setupFormValidation();
    }

    setupRupiahFormatting() {
        $('input[name="jumlah"]').on('input', (e) => {
            this.formatRupiah(e.target);
        });
    }

    formatRupiah(input) {
        let value = $(input).val().replace(/[^\d]/g, '');
        
        if (value) {
            value = parseInt(value).toLocaleString('id-ID');
        }
        
        $(input).val(value);
    }

    setupTransactionTypeHandling() {
        $('input[name="jenis_transaksi"]').on('change', (e) => {
            this.updateSubmitButtonText();
        });
    }

    updateSubmitButtonText() {
        const selectedType = $('input[name="jenis_transaksi"]:checked').val();
        const $submitButton = $('#submitButton');
        
        if (selectedType === 'masuk') {
            $submitButton.text('Simpan Uang Masuk');
            $submitButton.removeClass('btn-expense').addClass('btn-income');
        } else {
            $submitButton.text('Simpan Uang Keluar');
            $submitButton.removeClass('btn-income').addClass('btn-expense');
        }
    }

    setupFormValidation() {
        const $addForm = $('#addForm');

        if ($addForm.length) {
            $addForm.on('submit', (e) => this.validateForm(e));
        }
    }

    validateForm(event) {
        const $form = $(event.target);
        const $jenisTransaksi = $form.find('input[name="jenis_transaksi"]:checked');
        const $jumlahInput = $form.find('input[name="jumlah"]');
        const $catatanInput = $form.find('textarea[name="catatan"]');

        if (!$jenisTransaksi.length) {
            event.preventDefault();
            alert('Pilih jenis transaksi (Masuk atau Keluar)');
            return false;
        }

        if ($jumlahInput.length) {
            const jumlahValue = $jumlahInput.val().trim();
            const jumlahClean = jumlahValue.replace(/[^\d]/g, '');

            if (!jumlahClean || parseInt(jumlahClean) <= 0) {
                event.preventDefault();
                alert('Masukkan jumlah yang valid (minimal Rp 1)');
                $jumlahInput.trigger('focus');
                return false;
            }

            $jumlahInput.val(jumlahClean);
        }

        if ($catatanInput.length && !$catatanInput.val().trim()) {
            event.preventDefault();
            alert('Keterangan harus diisi');
            $catatanInput.trigger('focus');
            return false;
        }

        if ($jenisTransaksi.val() === 'keluar') {
            const jumlahClean = $jumlahInput.val().replace(/[^\d]/g, '');
            const saldoAkhir = this.getCurrentBalance();
            
            if (parseInt(jumlahClean) > saldoAkhir) {
                event.preventDefault();
                alert('Saldo tidak mencukupi untuk transaksi pengeluaran ini');
                $jumlahInput.trigger('focus');
                return false;
            }
        }

        return true;
    }

    getCurrentBalance() {
        const $saldoAkhirElement = $('.summary-value.total-amount');
        if ($saldoAkhirElement.length) {
            const saldoText = $saldoAkhirElement.text().replace(/[^\d]/g, '');
            return parseInt(saldoText) || 0;
        }
        return 0;
    }

    showModal(modalId) {
        const $modal = $(`#${modalId}`);
        if ($modal.length) {
            $modal.css({
                'display': 'flex',
                'align-items': 'center',
                'justify-content': 'center'
            });
            $('body').css('overflow', 'hidden');
            
            this.initializeModalState();
            
            const $firstInput = $modal.find('input, textarea, select').first();
            if ($firstInput.length) {
                setTimeout(() => $firstInput.trigger('focus'), 100);
            }
        }
    }

    initializeModalState() {
        $('input[name="jenis_transaksi"][value="masuk"]').prop('checked', true);
        this.updateSubmitButtonText();
        
        $('#addForm')[0].reset();
    }

    hideModal(modalId) {
        const $modal = $(`#${modalId}`);
        if ($modal.length) {
            $modal.hide();
            $('body').css('overflow', 'auto');
            
            const $form = $modal.find('form');
            if ($form.length) $form.trigger('reset');
        }
    }

    closeAllModals() {
        this.hideModal('addModal');
    }

    autoCloseAlerts() {
        $('.alert').each((index, alert) => {
            setTimeout(() => {
                $(alert).css({
                    'transition': 'opacity 0.3s ease',
                    'opacity': '0'
                });
                setTimeout(() => {
                    if ($(alert).parent().length) {
                        $(alert).remove();
                    }
                }, 300);
            }, 5000);
        });
    }

    handleTransactionTypeChange() {
        const selectedType = $('input[name="jenis_transaksi"]:checked').val();
        const $jumlahGroup = $('.form-group').has('input[name="jumlah"]');
        const $catatanInput = $('textarea[name="catatan"]');
        
        if (selectedType === 'masuk') {
            $catatanInput.attr('placeholder', 'Contoh: penerimaan dari...');
        } else {
            $catatanInput.attr('placeholder', 'Contoh: pembayaran untuk...');
        }
    }
}

function refreshData() {
            window.location.reload();
        }

function openAddModal() {
    if (window.rekapManager) {
        window.rekapManager.showModal('addModal');
    }
}

function closeAddModal() {
    if (window.rekapManager) {
        window.rekapManager.hideModal('addModal');
    }
}

$(function() {
    window.rekapManager = new RekapDetailManager();
    
    $(document).on('click', function(e) {
        if ($(e.target).hasClass('modal')) {
            window.rekapManager.closeAllModals();
        }
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            window.rekapManager.closeAllModals();
        }
    });

    $(document).on('change', 'input[name="jenis_transaksi"]', function() {
        window.rekapManager.handleTransactionTypeChange();
    });
});

$(document).ready(function() {
    $('head').append(`<style>${additionalStyles}</style>`);
});

if (typeof module !== 'undefined' && module.exports) {
    module.exports = RekapDetailManager;
}