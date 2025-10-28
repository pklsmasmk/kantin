// ======== DATA MANAGEMENT =========
let items = [];
let currentEditId = null;

// ======== DEBUGGING =========
function debugElements() {
    const criticalElements = [
        'itemForm', 'inputNama', 'inputTipe', 'inputPemasok', 
        'inputStok', 'inputHargaDasar', 'inputHargaJual',
        'dataListContainer', 'filterTipe', 'returBarang'
    ];
    
    console.group('🔍 Debug Elements');
    criticalElements.forEach(id => {
        const element = document.getElementById(id);
        console.log(`${element ? '✅' : '❌'} ${id}:`, element);
    });
    console.groupEnd();
}

// ======== TAB MANAGEMENT =========
function showTab(tabId) {
    console.log(`🔄 Pindah ke tab: ${tabId}`);
    
    // Cara yang paling pasti - langsung klik element tabnya
    const tabElement = document.querySelector(`[data-bs-target="${tabId}"]`);
    if (tabElement) {
        tabElement.click();
        console.log(`✅ Berhasil klik tab ${tabId}`);
    } else {
        console.error(`❌ Tab element tidak ditemukan: ${tabId}`);
    }
}

function updateModeIndicator(isEdit = false, itemName = '') {
    const modeContainer = getElement('modeAksiContainer');
    if (!modeContainer) return;
    
    if (isEdit) {
        modeContainer.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-edit"></i> <strong>Mode Edit:</strong> Anda sedang mengedit data barang "<strong>${itemName}</strong>"
            </div>
        `;
    } else {
        modeContainer.innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-plus"></i> <strong>Mode Tambah:</strong> Anda sedang menambah barang baru
            </div>
        `;
    }
}

// ======== ELEMENTS =========
function getElement(id) {
    const element = document.getElementById(id);
    if (!element) {
        console.error(`❌ Element dengan id '${id}' tidak ditemukan!`);
    }
    return element;
}

// ======== HELPERS =========
function showModal(message) {
    const modalMessage = getElement('modalMessage');
    if (modalMessage) {
        modalMessage.textContent = message;
        $('#messageModal').modal('show');
    } else {
        alert(message);
    }
}

function formatCurrency(num) {
    const number = parseInt(num) || 0;
    return 'Rp ' + number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// ======== DATA FUNCTIONS =========
function loadItems() {
    console.log("🔄 Memuat data dari server...");
    
    fetch('tampil_stok.php')
        .then(response => response.json())
        .then(data => {
            console.log("✅ Data JSON:", data);
            if (Array.isArray(data)) {
                items = data;
                renderItems();
            } else {
                throw new Error('Format data tidak valid - bukan array');
            }
        })
        .catch(error => {
            console.error("❌ Gagal memuat data:", error);
            showModal('Gagal memuat data: ' + error.message);
            items = [];
            renderItems();
        });
}

// ======== FUNGSI UNTUK TAB RETUR =========
function loadDaftarBarang() {
    console.log("🔄 Memuat daftar barang untuk retur...");
    
    fetch('ambil_daftar_barang.php')
        .then(response => response.json())
        .then(data => {
            console.log("✅ Data daftar barang:", data);
            const select = getElement('returBarang');
            if (!select) return;
            
            if (data.success && Array.isArray(data.data)) {
                select.innerHTML = '<option value="">-- Pilih Barang --</option>';
                data.data.forEach(barang => {
                    if (!barang.id || !barang.nama) return;
                    
                    const stok = parseInt(barang.stok) || 0;
                    if (stok > 0) {
                        const option = document.createElement('option');
                        option.value = barang.id;
                        option.textContent = `${barang.nama} (Stok: ${stok})`;
                        option.setAttribute('data-stok', stok);
                        select.appendChild(option);
                    }
                });
                
                if (select.options.length === 1) {
                    select.innerHTML = '<option value="">-- Tidak ada barang dengan stok --</option>';
                }
            } else {
                select.innerHTML = '<option value="">-- Error memuat data --</option>';
            }
        })
        .catch(error => {
            console.error('❌ Gagal memuat daftar barang:', error);
            const select = getElement('returBarang');
            if (select) {
                select.innerHTML = '<option value="">-- Error memuat data --</option>';
            }
        });
}

function validateJumlahRetur() {
    const selectedOption = document.querySelector('#returBarang option:selected');
    if (!selectedOption || !selectedOption.value) return true;
    
    const maxStok = parseInt(selectedOption.getAttribute('data-stok')) || 0;
    const jumlahRetur = parseInt(getElement('returJumlah').value) || 0;
    
    if (jumlahRetur > maxStok) {
        showModal(`Jumlah retur tidak boleh melebihi stok yang tersedia (${maxStok})`);
        getElement('returJumlah').value = maxStok;
        return false;
    }
    return true;
}

function handleAlasanReturChange() {
    const alasan = getElement('returAlasan').value;
    const container = getElement('alasanLainnyaContainer');
    const inputAlasanLainnya = getElement('returAlasanLainnya');
    
    if (container && inputAlasanLainnya) {
        if (alasan === 'Lainnya') {
            container.style.display = 'block';
            inputAlasanLainnya.required = true;
        } else {
            container.style.display = 'none';
            inputAlasanLainnya.required = false;
            inputAlasanLainnya.value = '';
        }
    }
}

function handleReturSubmit() {
    const barangSelect = getElement('returBarang');
    const selectedOption = barangSelect.options[barangSelect.selectedIndex];
    
    if (!selectedOption || !selectedOption.value) {
        return showModal('Pilih barang terlebih dahulu!');
    }
    
    const maxStok = parseInt(selectedOption.getAttribute('data-stok')) || 0;
    
    const formData = {
        barang_id: parseInt(barangSelect.value),
        jumlah: parseInt(getElement('returJumlah').value) || 0,
        alasan: getElement('returAlasan').value,
        alasan_lainnya: getElement('returAlasanLainnya').value,
        keterangan: getElement('returKeterangan').value
    };

    // Validasi
    if (!formData.barang_id) return showModal('Pilih barang terlebih dahulu!');
    if (formData.jumlah <= 0) return showModal('Jumlah retur harus lebih dari 0!');
    if (formData.jumlah > maxStok) return showModal(`Jumlah retur melebihi stok tersedia (${maxStok})!`);
    if (!formData.alasan) return showModal('Pilih alasan retur!');
    if (formData.alasan === 'Lainnya' && !formData.alasan_lainnya) {
        return showModal('Harap isi alasan retur lainnya!');
    }

    if (!confirm('Apakah Anda yakin ingin memproses retur barang ini?')) {
        return;
    }

    fetch('proses_retur.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            showModal('Retur berhasil diproses!');
            // Reset form retur
            getElement('returBarang').value = '';
            getElement('returJumlah').value = '';
            getElement('returAlasan').value = '';
            getElement('returAlasanLainnya').value = '';
            getElement('returKeterangan').value = '';
            getElement('alasanLainnyaContainer').style.display = 'none';
            
            // Refresh data
            loadDaftarBarang();
            loadItems();
            loadRiwayatRetur();
        } else {
            showModal('Error: ' + (data.message || 'Gagal memproses retur!'));
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        showModal('Gagal memproses retur: ' + error.message);
    });
}

function loadRiwayatRetur() {
    fetch('riwayat_retur.php')
        .then(response => response.json())
        .then(data => {
            const container = getElement('riwayatReturContainer');
            if (!container) return;

            if (!Array.isArray(data) || data.length === 0 || data.message) {
                container.innerHTML = '<div class="col-12 text-center text-muted"><p>Belum ada riwayat retur</p></div>';
                return;
            }

            container.innerHTML = data.map(retur => `
                <div class="col-md-6 mb-3">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-dark">
                            <strong>${retur.nama_barang}</strong>
                            <span class="float-end badge bg-danger">${retur.jumlah} pcs</span>
                        </div>
                        <div class="card-body">
                            <p><strong>Alasan:</strong> ${retur.alasan}</p>
                            <p><strong>Keterangan:</strong> ${retur.keterangan || '-'}</p>
                            <p><strong>Tanggal:</strong> ${new Date(retur.tanggal).toLocaleString()}</p>
                        </div>
                    </div>
                </div>
            `).join('');
        })
        .catch(error => {
            console.error('Gagal memuat riwayat retur:', error);
        });
}

// ======== FUNGSI UNTUK TAB RIWAYAT =========
function loadRiwayatTransaksi() {
    fetch('tampil_riwayat.php')
        .then(response => response.json())
        .then(data => {
            const container = getElement('historyListContainer');
            if (!container) return;

            if (!Array.isArray(data) || data.length === 0 || data.message) {
                container.innerHTML = '<div class="col-12 text-center text-muted"><p>Belum ada riwayat transaksi</p></div>';
                return;
            }

            container.innerHTML = data.map(transaksi => `
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-header">
                            <strong>${transaksi.jenis_transaksi}</strong>
                            <span class="float-end text-muted small">${new Date(transaksi.tanggal).toLocaleString()}</span>
                        </div>
                        <div class="card-body">
                            <p><strong>Barang:</strong> ${transaksi.nama_barang}</p>
                            <p><strong>Keterangan:</strong> ${transaksi.keterangan || '-'}</p>
                            ${transaksi.perubahan_stok !== 0 ? 
                                `<p><strong>Perubahan Stok:</strong> 
                                 <span class="${transaksi.perubahan_stok > 0 ? 'text-success' : 'text-danger'}">
                                 ${transaksi.perubahan_stok > 0 ? '+' : ''}${transaksi.perubahan_stok}
                                 </span></p>` : ''
                            }
                            <p><strong>User:</strong> ${transaksi.user || 'Admin'}</p>
                        </div>
                    </div>
                </div>
            `).join('');
        })
        .catch(error => {
            console.error('Gagal memuat riwayat:', error);
            showModal('Gagal memuat riwayat transaksi');
        });
}

// ======== FORM SUBMISSION =========
function handleSubmit(event) {
    event.preventDefault();
    console.log("🎯 Form submitted");

    const formData = {
        nama: getElement('inputNama')?.value.trim(),
        tipe: getElement('inputTipe')?.value,
        pemasok: getElement('inputPemasok')?.value.trim(),
        stok: parseInt(getElement('inputStok')?.value) || 0,
        hargaDasar: parseInt(getElement('inputHargaDasar')?.value) || 0,
        hargaJual: parseInt(getElement('inputHargaJual')?.value) || 0
    };

    // Validasi
    if (!formData.nama) return showModal('Nama barang harus diisi!');
    if (!formData.tipe) return showModal('Tipe barang harus dipilih!');
    if (!formData.pemasok) return showModal('Pemasok harus diisi!');

    sendToServer(formData);
}

function sendToServer(formData) {
    const phpData = new FormData();
    phpData.append('nama', formData.nama);
    phpData.append('tipe', formData.tipe);
    phpData.append('pemasok', formData.pemasok);
    phpData.append('stok', formData.stok);
    phpData.append('harga_dasar', formData.hargaDasar);
    phpData.append('harga_jual', formData.hargaJual);

    const url = currentEditId ? 'update_stok.php' : 'insert_stok.php';
    if (currentEditId) phpData.append('id', currentEditId);

    fetch(url, { method: 'POST', body: phpData })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showModal(currentEditId ? 'Data berhasil diperbarui!' : 'Barang berhasil disimpan!');
                loadItems();
                resetForm();
                // Kembali ke tab Stok setelah berhasil
                setTimeout(() => showTab('#tabStok'), 1000);
            } else {
                showModal('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('❌ Fetch error:', error);
            showModal('Gagal mengirim data: ' + error.message);
        });
}

function resetForm() {
    console.log("🔄 Reset form");
    currentEditId = null;
    const form = getElement('itemForm');
    if (form) form.reset();
    updateModeIndicator(false);
}

// ======== RENDERING =========
function renderItems() {
    const container = getElement('dataListContainer');
    if (!container) {
        console.error("❌ Container tidak ditemukan!");
        return;
    }

    const filterTipe = getElement('filterTipe')?.value;
    let filteredItems = items;
    
    if (filterTipe && filterTipe !== 'Semua') {
        filteredItems = items.filter(item => item.tipe === filterTipe);
    }

    if (filteredItems.length === 0) {
        container.innerHTML = `
            <div class="col-12 text-center text-muted">
                <p>${items.length === 0 ? 'Belum ada data barang.' : 'Tidak ada data yang sesuai filter.'}</p>
            </div>`;
        return;
    }

    container.innerHTML = filteredItems.map(item => `
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>${item.nama || 'No Name'}</strong>
                    <span class="badge ${getBadgeClass(item.tipe)}">${item.tipe || 'No Type'}</span>
                </div>
                <div class="card-body">
                    <p><strong>Pemasok:</strong> ${item.pemasok || '-'}</p>
                    <p><strong>Stok:</strong> <span class="${item.stok <= 5 ? 'text-danger fw-bold' : ''}">${item.stok || 0}</span></p>
                    <p><strong>Harga Dasar:</strong> ${formatCurrency(item.harga_dasar)}</p>
                    <p><strong>Harga Jual:</strong> ${formatCurrency(item.harga_jual)}</p>
                    <div class="d-grid gap-2">
                        <button class="btn btn-warning btn-sm edit-btn" data-id="${item.id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-danger btn-sm delete-btn" data-id="${item.id}">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    attachItemEventListeners();
}

function getBadgeClass(tipe) {
    const classes = {
        'Makanan': 'bg-warning',
        'Minuman': 'bg-info', 
        'Alat Tulis': 'bg-primary',
        'Lainnya': 'bg-secondary'
    };
    return classes[tipe] || 'bg-secondary';
}

function attachItemEventListeners() {
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            console.log(`✏️ Tombol edit diklik untuk ID: ${id}`);
            editItem(parseInt(id));
        });
    });

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            confirmDelete(parseInt(id));
        });
    });
}

// ======== FUNGSI EDIT ITEM YANG DIPERBAIKI =========
function editItem(id) {
    console.log(`✏️ Memulai edit item dengan ID: ${id}`);
    
    const item = items.find(item => item.id === id);
    if (!item) {
        console.error('❌ Item tidak ditemukan untuk ID:', id);
        return showModal('Data tidak ditemukan!');
    }

    console.log('✅ Item ditemukan:', item);
    
    currentEditId = id;
    
    // Isi form dengan data yang akan diedit
    getElement('inputNama').value = item.nama || '';
    getElement('inputTipe').value = item.tipe || '';
    getElement('inputPemasok').value = item.pemasok || '';
    getElement('inputStok').value = item.stok || 0;
    getElement('inputHargaDasar').value = item.harga_dasar || 0;
    getElement('inputHargaJual').value = item.harga_jual || 0;

    // Update mode indicator
    updateModeIndicator(true, item.nama);
    
    // CARA YANG PASTI BEKERJA - langsung klik tab Input
    console.log('🔄 Mengklik tab Input...');
    const tabInput = document.getElementById('tabInput-tab');
    if (tabInput) {
        tabInput.click();
        console.log('✅ Tab Input berhasil diklik');
    } else {
        console.error('❌ Tab Input tidak ditemukan');
    }
}

function confirmDelete(id) {
    const item = items.find(item => item.id === id);
    const itemName = item?.nama || 'item ini';
    
    getElement('confirmMessage').textContent = `Hapus "${itemName}"?`;
    getElement('confirmDeleteBtn').onclick = () => performDelete(id);
    $('#confirmModal').modal('show');
}

function performDelete(id) {
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('delete_stok.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showModal('Data berhasil dihapus!');
                loadItems();
            } else {
                showModal('Gagal menghapus: ' + data.message);
            }
            $('#confirmModal').modal('hide');
        })
        .catch(error => {
            console.error('Delete error:', error);
            showModal('Gagal menghapus data');
            $('#confirmModal').modal('hide');
        });
}

// ======== INITIALIZATION =========
function initializeEventListeners() {
    console.log("🔌 Initializing event listeners...");
    
    const form = getElement('itemForm');
    const resetBtn = getElement('resetBtn');
    const filterTipe = getElement('filterTipe');
    const returBtn = getElement('submitReturBtn');
    const resetReturBtn = getElement('resetReturBtn');
    const returAlasan = getElement('returAlasan');
    const returJumlah = getElement('returJumlah');
    const returBarang = getElement('returBarang');

    if (form) form.addEventListener('submit', handleSubmit);
    if (resetBtn) resetBtn.addEventListener('click', resetForm);
    if (filterTipe) filterTipe.addEventListener('change', renderItems);
    
    // Event listeners untuk retur
    if (returBtn) returBtn.addEventListener('click', handleReturSubmit);
    if (resetReturBtn) {
        resetReturBtn.addEventListener('click', function() {
            getElement('returBarang').value = '';
            getElement('returJumlah').value = '';
            getElement('returAlasan').value = '';
            getElement('returAlasanLainnya').value = '';
            getElement('returKeterangan').value = '';
            getElement('alasanLainnyaContainer').style.display = 'none';
        });
    }
    
    if (returAlasan) returAlasan.addEventListener('change', handleAlasanReturChange);
    if (returJumlah) returJumlah.addEventListener('input', validateJumlahRetur);
    
    if (returBarang) {
        returBarang.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const maxStok = parseInt(selectedOption?.getAttribute('data-stok')) || 0;
            
            if (maxStok === 0 && selectedOption?.value) {
                showModal('Stok barang ini habis, tidak dapat melakukan retur');
                getElement('returJumlah').value = '';
            }
        });
    }

    // PERBAIKAN: Event listener untuk tab change yang benar
    const tabElements = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabElements.forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target');
            console.log(`🔀 Switch to tab: ${target}`);
            
            if (target === '#tabStok') {
                loadItems();
            } else if (target === '#tabRetur') {
                loadDaftarBarang();
                loadRiwayatRetur();
            } else if (target === '#tabRiwayat') {
                loadRiwayatTransaksi();
            }
        });
    });
}

function init() {
    console.log("🚀 Starting application...");
    debugElements();
    initializeEventListeners();
    loadItems();
    updateModeIndicator(false);
    console.log("✅ Application ready!");
}

// Start when ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}