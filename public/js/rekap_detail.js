class RekapDetailManager {
    constructor() {
        this.currentOpenActions = null;
        this.init();
    }

    init() {
        console.log('Rekap Detail Manager initialized');
        this.setupEventListeners();
        this.autoCloseAlerts();
        this.setupRupiahFormatting();
        
        if (window.transaksiData) {
            console.log('Transaction data loaded:', window.transaksiData);
        }
    }

    setupEventListeners() {
        $(document).on('click', (e) => {
            this.handleTransactionClick(e);
            this.handleActionButtonClick(e);
        });

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

        const $editJumlahInput = $('#edit_jumlah');
        if ($editJumlahInput.length) {
            $editJumlahInput.on('input', (e) => {
                this.formatRupiah(e.target);
            });
        }

        const $mainJumlahInput = $('#main_jumlah');
        if ($mainJumlahInput.length) {
            $mainJumlahInput.on('input', (e) => {
                this.formatRupiah(e.target);
            });
        }
    }

    formatRupiah(input) {
        let value = $(input).val().replace(/[^\d]/g, '');
        
        if (value) {
            value = parseInt(value).toLocaleString('id-ID');
        }
        
        $(input).val(value);
    }

    handleTransactionClick(event) {
        const $transactionItem = $(event.target).closest('.transaction-item');
        if (!$transactionItem.length) return;

        if ($(event.target).closest('.btn-action').length) return;

        const index = $transactionItem.attr('data-index');
        this.toggleTransactionActions(index);
    }

    handleActionButtonClick(event) {
        if ($(event.target).closest('.btn-edit').length) {
            event.stopPropagation();
            const $button = $(event.target).closest('.btn-edit');
            const index = $button.attr('data-index');
            this.openEditModal(index);
        }
    }

    toggleTransactionActions(index) {
        const $actionsElement = $(`#actions-${index}`);
        if (!$actionsElement.length) return;

        if (this.currentOpenActions && this.currentOpenActions !== $actionsElement[0]) {
            $(this.currentOpenActions).hide();
        }

        if ($ActionsElement.css('display') === 'flex') {
            $ActionsElement.hide();
            this.currentOpenActions = null;
        } else {
            $ActionsElement.show();
            this.currentOpenActions = $ActionsElement[0];
        }
    }

    openEditModal(index) {
        if (!window.transaksiData || !window.transaksiData[index]) {
            alert('Data transaksi tidak ditemukan');
            return;
        }

        const transaksi = window.transaksiData[index];
        
        $('#edit_id').val(transaksi.id);
        $('#edit_jumlah').val(Math.abs(transaksi.nominal).toLocaleString('id-ID'));
        $('#edit_catatan').val(transaksi.keterangan);
        
        let aksiValue;
        if (transaksi.tipe === 'Penjualan Tunai') {
            aksiValue = 'penjualan';
        } else if (transaksi.tipe === 'Pengeluaran') {
            aksiValue = 'pengeluaran';
        } else if (transaksi.tipe === 'Masuk Lain') {
            aksiValue = 'masuk';
        } else if (transaksi.tipe === 'Keluar Lain') {
            aksiValue = 'keluar';
        } else {
            aksiValue = transaksi.nominal >= 0 ? 'masuk' : 'keluar';
        }
        
        $('#edit_aksi').val(aksiValue);

        this.showModal('editModal');

        if (this.currentOpenActions) {
            $(this.currentOpenActions).hide();
            this.currentOpenActions = null;
        }
    }

    setupFormValidation() {
        const $addForm = $('#addForm');
        const $editForm = $('#editForm');
        const $mainTransactionForm = $('#mainTransactionForm');

        if ($addForm.length) {
            $addForm.on('submit', (e) => this.validateForm(e));
        }

        if ($editForm.length) {
            $editForm.on('submit', (e) => this.validateForm(e));
        }

        if ($mainTransactionForm.length) {
            $mainTransactionForm.on('submit', (e) => this.validateForm(e));
        }
    }

    validateForm(event) {
        const $form = $(event.target);
        const $jumlahInput = $form.find('input[name="jumlah"]');
        const $catatanInput = $form.find('textarea[name="catatan"]');

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

        return true;
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
            
            const $firstInput = $modal.find('input, textarea, select').first();
            if ($firstInput.length) {
                setTimeout(() => $firstInput.trigger('focus'), 100);
            }
        }
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
        this.hideModal('editModal');
        this.hideModal('mainTransactionModal');
        
        if (this.currentOpenActions) {
            $(this.currentOpenActions).hide();
            this.currentOpenActions = null;
        }
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

function openMainTransactionModal() {
    if (window.rekapManager) {
        window.rekapManager.showModal('mainTransactionModal');
    }
}

function closeMainTransactionModal() {
    if (window.rekapManager) {
        window.rekapManager.hideModal('mainTransactionModal');
    }
}

function closeEditModal() {
    if (window.rekapManager) {
        window.rekapManager.hideModal('editModal');
    }
}

function openEditModal(index) {
    if (window.rekapManager) {
        window.rekapManager.openEditModal(index);
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
});

if (typeof module !== 'undefined' && module.exports) {
    module.exports = RekapDetailManager;
}