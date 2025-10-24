// ======== DATA MANAGEMENT =========
let items = [];
let currentEditId = null;

// ======== ELEMENTS =========
function getElement(id) {
    const element = document.getElementById(id);
    if (!element) {
        console.warn(`⚠️ Element dengan id '${id}' tidak ditemukan`);
    }
    return element;
}

// ======== HELPERS =========
function showModal(message) {
    // Gunakan modal yang sudah ada di HTML
    document.getElementById('modalMessage').textContent = message;
    $('#messageModal').modal('show');
}

function formatCurrency(num) {
    if (!num) return 'Rp 0';
    return 'Rp ' + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// ======== DATA FUNCTIONS =========
function loadItems() {
    console.log("🔄 Memuat data dari server...");
    
    fetch('/?q=retur__tampilstok')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log("✅ Data diterima:", data);
            items = data;
            renderItems();
        })
        .catch(error => {
            console.error("❌ Gagal memuat data:", error);
            showModal('Gagal memuat data dari server: ' + error.message);
        });
}

// ======== FORM SUBMISSION =========
function handleSubmit(event) {
    console.log("🎯 Submit dipanggil");
    
    if (event) {
        event.preventDefault();
    }

    // AMBIL NILAI DENGAN ID YANG BENAR
    const nama = getElement('inputNama')?.value.trim();
    const tipe = getElement('inputTipe')?.value;
    const pemasok = getElement('inputPemasok')?.value.trim();
    const stok = parseInt(getElement('inputStok')?.value) || 0;
    const hargaDasar = parseInt(getElement('inputHargaDasar')?.value) || 0;
    const hargaJual = parseInt(getElement('inputHargaJual')?.value) || 0;

    console.log("📝 Data form:", { nama, tipe, pemasok, stok, hargaDasar, hargaJual });

    // Validasi
    if (!nama || !tipe || !pemasok || stok < 0 || hargaDasar < 0 || hargaJual < 0) {
        showModal('Mohon isi semua field dengan benar!');
        return;
    }

    // Siapkan data untuk dikirim ke PHP
    const formData = new FormData();
    formData.append('nama', nama);
    formData.append('tipe', tipe);
    formData.append('pemasok', pemasok);
    formData.append('stok', stok);
    formData.append('harga_dasar', hargaDasar);
    formData.append('harga_jual', hargaJual);

    const url = currentEditId ? 'update_stok.php' : 'insert_stok.php';
    
    if (currentEditId) {
        formData.append('id', currentEditId);
        console.log("✏️ Mode EDIT, ID:", currentEditId);
    } else {
        console.log("➕ Mode TAMBAH BARU");
    }

    console.log("📤 Mengirim ke:", url);

    // Kirim data ke PHP
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log("📨 Response status:", response.status);
        return response.json();
    })
    .then(data => {
        console.log("📊 Response data:", data);
        if (data.status === 'success') {
            showModal(currentEditId ? 'Data berhasil diperbarui!' : 'Barang baru berhasil disimpan!');
            loadItems(); // Reload data
            resetForm();
            
            // Switch ke tab stok setelah simpan
            $('a[href="#tabStok"]').tab('show');
        } else {
            showModal('Error: ' + (data.message || 'Terjadi kesalahan'));
        }
    })
    .catch(error => {
        console.error('❌ Fetch error:', error);
        showModal('Gagal mengirim data ke server');
    });
}

function resetForm() {
    console.log("🔄 Reset form");
    currentEditId = null;
    const itemForm = getElement('itemForm');
    if (itemForm) {
        itemForm.reset();
    }
}

// ======== RENDERING =========
function renderItems() {
    const dataListContainer = getElement('dataListContainer');
    
    if (!dataListContainer) {
        console.error("❌ dataListContainer tidak ditemukan!");
        return;
    }

    console.log("🎨 Render items:", items);

    if (!items || items.length === 0) {
        dataListContainer.innerHTML = `
            <div class="col-xs-12 text-center text-muted">
                <p>Belum ada data barang.</p>
                <p>Silakan tambah barang baru di tab "Restock/Tambah Barang".</p>
            </div>`;
        return;
    }

    dataListContainer.innerHTML = '';
    items.forEach(item => {
        const col = document.createElement('div');
        col.className = 'col-md-4 mb-3';
        col.innerHTML = `
            <div class="panel panel-default">
                <div class="panel-heading">
                    <strong>${item.nama || 'No Name'}</strong>
                    <span class="pull-right badge">${item.tipe || 'No Type'}</span>
                </div>
                <div class="panel-body">
                    <p><strong>Pemasok:</strong> ${item.pemasok || '-'}</p>
                    <p><strong>Stok:</strong> ${item.stok || 0}</p>
                    <p><strong>Harga Dasar:</strong> ${formatCurrency(item.harga_dasar)}</p>
                    <p><strong>Harga Jual:</strong> ${formatCurrency(item.harga_jual)}</p>
                    <div class="btn-group btn-group-justified">
                        <div class="btn-group">
                            <button class="btn btn-warning btn-xs edit-btn" data-id="${item.id}">
                                <i class="fa fa-edit"></i> Edit
                            </button>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-danger btn-xs delete-btn" data-id="${item.id}">
                                <i class="fa fa-trash"></i> Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        dataListContainer.appendChild(col);
    });

    // Tambah event listeners untuk edit/delete buttons
    setTimeout(() => {
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                editItem(id);
            });
        });

        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                deleteItem(id);
            });
        });
    }, 100);
}

function editItem(id) {
    console.log("✏️ Edit item:", id);
    const item = items.find(item => item.id == id);
    if (!item) {
        showModal('Data tidak ditemukan!');
        return;
    }

    currentEditId = id;
    
    // Isi form dengan data yang dipilih - GUNAKAN ID YANG BENAR
    getElement('inputNama').value = item.nama || '';
    getElement('inputTipe').value = item.tipe || '';
    getElement('inputPemasok').value = item.pemasok || '';
    getElement('inputStok').value = item.stok || 0;
    getElement('inputHargaDasar').value = item.harga_dasar || 0;
    getElement('inputHargaJual').value = item.harga_jual || 0;

    // Switch ke tab input
    $('a[href="#tabInput"]').tab('show');
}

function deleteItem(id) {
    const item = items.find(item => item.id == id);
    const itemName = item ? item.nama : 'item ini';
    
    document.getElementById('confirmMessage').textContent = `Apakah Anda yakin ingin menghapus "${itemName}"?`;
    $('#confirmModal').modal('show');
    
    // Set event listener untuk confirm delete
    const confirmBtn = getElement('confirmDeleteBtn');
    confirmBtn.onclick = function() {
        performDelete(id);
    };
}

function performDelete(id) {
    console.log("🗑️ Delete item:", id);
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch('delete_stok.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log("Delete response:", data);
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

// ======== EVENT LISTENERS =========
function initializeEventListeners() {
    console.log("🔌 Initializing event listeners...");
    
    const itemForm = getElement('itemForm');
    const resetBtn = getElement('resetBtn');

    // Form submission - tangani submit button yang tidak punya ID
    if (itemForm) {
        itemForm.addEventListener('submit', handleSubmit);
        console.log("✅ Form event listener added");
    }
    
    // Reset button
    if (resetBtn) {
        resetBtn.addEventListener('click', resetForm);
        console.log("✅ Reset button event listener added");
    }
    
    // Filter
    const filterTipe = getElement('filterTipe');
    if (filterTipe) {
        filterTipe.addEventListener('change', renderItems);
        console.log("✅ Filter event listener added");
    }
    
    console.log("🎉 All event listeners initialized");
}

// ======== INITIALIZATION =========
function init() {
    console.log("🚀 Aplikasi dimulai...");
    
    // Tunggu sebentar untuk memastikan DOM siap
    setTimeout(() => {
        initializeEventListeners();
        loadItems();
        console.log("✅ Aplikasi siap!");
    }, 100);
}

// Start aplikasi ketika DOM siap
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}