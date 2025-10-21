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
        document.addEventListener('click', (e) => {
            this.handleTransactionClick(e);
            this.handleActionButtonClick(e);
        });

        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeAllModals();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAllModals();
            }
        });

        this.setupFormValidation();
    }

    setupRupiahFormatting() {
        const jumlahInputs = document.querySelectorAll('input[name="jumlah"]');
        jumlahInputs.forEach(input => {
            input.addEventListener('input', (e) => {
                this.formatRupiah(e.target);
            });
        });

        const editJumlahInput = document.getElementById('edit_jumlah');
        if (editJumlahInput) {
            editJumlahInput.addEventListener('input', (e) => {
                this.formatRupiah(e.target);
            });
        }

        const mainJumlahInput = document.getElementById('main_jumlah');
        if (mainJumlahInput) {
            mainJumlahInput.addEventListener('input', (e) => {
                this.formatRupiah(e.target);
            });
        }
    }

    formatRupiah(input) {
        let value = input.value.replace(/[^\d]/g, '');
        
        if (value) {
            value = parseInt(value).toLocaleString('id-ID');
        }
        
        input.value = value;
    }

    handleTransactionClick(event) {
        const transactionItem = event.target.closest('.transaction-item');
        if (!transactionItem) return;

        if (event.target.closest('.btn-action')) return;

        const index = transactionItem.getAttribute('data-index');
        this.toggleTransactionActions(index);
    }

    handleActionButtonClick(event) {
        if (event.target.closest('.btn-edit')) {
            event.stopPropagation();
            const button = event.target.closest('.btn-edit');
            const index = button.getAttribute('data-index');
            this.openEditModal(index);
        }

        if (event.target.closest('.btn-delete')) {
            event.stopPropagation();
            const button = event.target.closest('.btn-delete');
            const index = button.getAttribute('data-index');
            this.deleteTransaction(index);
        }
    }

    toggleTransactionActions(index) {
        const actionsElement = document.getElementById(`actions-${index}`);
        if (!actionsElement) return;

        if (this.currentOpenActions && this.currentOpenActions !== actionsElement) {
            this.currentOpenActions.style.display = 'none';
        }

        if (actionsElement.style.display === 'flex') {
            actionsElement.style.display = 'none';
            this.currentOpenActions = null;
        } else {
            actionsElement.style.display = 'flex';
            this.currentOpenActions = actionsElement;
        }
    }

    openEditModal(index) {
        if (!window.transaksiData || !window.transaksiData[index]) {
            alert('Data transaksi tidak ditemukan');
            return;
        }

        const transaksi = window.transaksiData[index];
        
        document.getElementById('edit_id').value = transaksi.id;
        document.getElementById('edit_jumlah').value = Math.abs(transaksi.nominal).toLocaleString('id-ID');
        document.getElementById('edit_catatan').value = transaksi.keterangan;
        
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
        
        document.getElementById('edit_aksi').value = aksiValue;

        this.showModal('editModal');

        if (this.currentOpenActions) {
            this.currentOpenActions.style.display = 'none';
            this.currentOpenActions = null;
        }
    }

    deleteTransaction(index) {
        if (!window.transaksiData || !window.transaksiData[index]) {
            alert('Data transaksi tidak ditemukan');
            return;
        }

        const transaksi = window.transaksiData[index];
        const message = `Apakah Anda yakin ingin menghapus transaksi ini?\n\n${transaksi.tipe} - ${transaksi.keterangan}\nRp ${Math.abs(transaksi.nominal).toLocaleString('id-ID')}`;

        if (confirm(message)) {
            window.location.href = `?delete_id=${encodeURIComponent(transaksi.id)}`;
        }
    }

    setupFormValidation() {
        const addForm = document.getElementById('addForm');
        const editForm = document.getElementById('editForm');
        const mainTransactionForm = document.getElementById('mainTransactionForm');

        if (addForm) {
            addForm.addEventListener('submit', (e) => this.validateForm(e));
        }

        if (editForm) {
            editForm.addEventListener('submit', (e) => this.validateForm(e));
        }

        if (mainTransactionForm) {
            mainTransactionForm.addEventListener('submit', (e) => this.validateForm(e));
        }
    }

    validateForm(event) {
        const form = event.target;
        const jumlahInput = form.querySelector('input[name="jumlah"]');
        const catatanInput = form.querySelector('textarea[name="catatan"]');

        if (jumlahInput) {
            const jumlahValue = jumlahInput.value.trim();
            const jumlahClean = jumlahValue.replace(/[^\d]/g, '');

            if (!jumlahClean || parseInt(jumlahClean) <= 0) {
                event.preventDefault();
                alert('Masukkan jumlah yang valid (minimal Rp 1)');
                jumlahInput.focus();
                return false;
            }

            jumlahInput.value = jumlahClean;
        }

        if (catatanInput && !catatanInput.value.trim()) {
            event.preventDefault();
            alert('Keterangan harus diisi');
            catatanInput.focus();
            return false;
        }

        return true;
    }

    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            const firstInput = modal.querySelector('input, textarea, select');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    }

    hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            const form = modal.querySelector('form');
            if (form) form.reset();
        }
    }

    closeAllModals() {
        this.hideModal('addModal');
        this.hideModal('editModal');
        this.hideModal('mainTransactionModal');
        
        if (this.currentOpenActions) {
            this.currentOpenActions.style.display = 'none';
            this.currentOpenActions = null;
        }
    }

    autoCloseAlerts() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
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

document.addEventListener('DOMContentLoaded', function() {
    window.rekapManager = new RekapDetailManager();
    
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            window.rekapManager.closeAllModals();
        }
    });
});