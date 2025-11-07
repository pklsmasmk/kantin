document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - Initializing Stok App');
    initializeStokApp();
});

function initializeStokApp() {
    console.log('Initializing Stok App...');
    loadStokData();
    setupEventListeners();
    setupFormHandlers();
}

function loadStokData() {
    console.log('Loading stok data...');
    showLoading('dataListContainer');

    fetch('../../pembelian_barang/ambil_daftar_barang.php')
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Data received:', data);
            if (data.success && data.data) {
                console.log('Data length:', data.data.length);
                displayStokData(data.data);
                updateClearAllButton(data.data);
            } else {
                console.error('Data error:', data.error);
                showError('Gagal memuat data stok: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error loading data:', error);
            showError('Terjadi kesalahan saat memuat data: ' + error.message);
        });
}

function displayStokData(data) {
    console.log('Displaying stok data:', data);
    const container = document.getElementById('dataListContainer');
    
    if (!container) {
        console.error('Container dataListContainer tidak ditemukan!');
        return;
    }
    
    if (!data || data.length === 0) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> Tidak ada data stok barang.
                </div>
            </div>
        `;
        return;
    }

    let html = '';
    data.forEach(item => {
        const profit = item.harga_jual - item.harga_dasar;
        const profitPercentage = item.harga_dasar > 0 ? ((profit / item.harga_dasar) * 100).toFixed(1) : 0;
        
        html += `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100 stok-card" data-tipe="${item.tipe}">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0">${escapeHtml(item.nama)}</h6>
                        <small class="text-muted">${escapeHtml(item.tipe)}</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Stok:</small>
                                <div class="fw-bold ${item.stok < 10 ? 'text-danger' : 'text-success'}">
                                    ${item.stok} pcs
                                </div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Pemasok:</small>
                                <div class="fw-bold">${escapeHtml(item.pemasok)}</div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <small class="text-muted">Modal:</small>
                                <div class="fw-bold">Rp ${formatNumber(item.harga_dasar)}</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Jual:</small>
                                <div class="fw-bold text-success">Rp ${formatNumber(item.harga_jual)}</div>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-12">
                                <small class="text-muted">Profit:</small>
                                <div class="fw-bold ${profit >= 0 ? 'text-success' : 'text-danger'}">
                                    Rp ${formatNumber(profit)} (${profitPercentage}%)
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent">
                        <div class="btn-group w-100" role="group">
                            <button type="button" class="btn btn-warning btn-sm" onclick="editItem(${item.id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(${item.id}, '${escapeHtml(item.nama)}')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    console.log('Data displayed successfully');
}

function setupEventListeners() {
    console.log('Setting up event listeners...');
    
    // Filter tipe
    const filterTipe = document.getElementById('filterTipe');
    if (filterTipe) {
        filterTipe.addEventListener('change', filterByTipe);
        console.log('Filter tipe event listener added');
    }
    
    // Clear all button
    const clearAllBtn = document.getElementById('clearAllBtn');
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', clearAllData);
        console.log('Clear all button event listener added');
    }
    
    // Form reset
    const resetBtn = document.getElementById('resetBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', resetForm);
        console.log('Reset button event listener added');
    }
}

function setupFormHandlers() {
    console.log('Setting up form handlers...');
    const itemForm = document.getElementById('itemForm');
    if (itemForm) {
        itemForm.addEventListener('submit', function(e) {
            console.log('Form submit intercepted');
            e.preventDefault();
            saveItem();
        });
        console.log('Form submit handler added');
    }
}

function saveItem() {
    console.log('saveItem function called');
    
    const formData = new FormData(document.getElementById('itemForm'));
    const itemId = document.getElementById('itemId').value;
    
    // Debug form data
    console.log('Form Data:');
    for (let [key, value] of formData.entries()) {
        console.log(key + ': ' + value);
    }

    const url = itemId ? '../../pembelian_barang/update_stok.php' : '../../pembelian_barang/insert_stok.php';
    console.log('Sending to URL:', url);
    
    showLoading('dataListContainer');
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Fetch response status:', response.status);
        if (!response.ok) {
            throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
    })
    .then(result => {
        console.log('Server result:', result);
        if (result.status === 'success') {
            showMessage(result.message, 'success');
            resetForm();
            setTimeout(() => {
                loadStokData();
            }, 1000);

            setTimeout(() => {
                const stokTab = new bootstrap.Tab(document.getElementById('tabStok-tab'));
                stokTab.show();
            }, 2000);
        } else {
            showMessage(result.message || 'Terjadi kesalahan', 'error');
        }
    })
    .catch(error => {
        console.error('Error in saveItem:', error);
        showMessage('Terjadi kesalahan saat menyimpan data: ' + error.message, 'error');
    });
}

function editItem(id) {
    console.log('Editing item:', id);
    fetch('../../pembelian_barang/ambil_daftar_barang.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const item = data.data.find(i => i.id === id);
                if (item) {
                    // Switch to input tab
                    const inputTab = new bootstrap.Tab(document.getElementById('tabInput-tab'));
                    inputTab.show();
                    
                    // Fill form
                    document.getElementById('itemId').value = item.id;
                    document.getElementById('inputNama').value = item.nama;
                    document.getElementById('inputTipe').value = item.tipe;
                    document.getElementById('inputPemasok').value = item.pemasok;
                    document.getElementById('inputStok').value = item.stok;
                    document.getElementById('inputHargaDasar').value = item.harga_dasar;
                    document.getElementById('inputHargaJual').value = item.harga_jual;
                    
                    // Update mode indicator
                    document.getElementById('modeAksiContainer').innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-edit"></i> <strong>Mode Edit:</strong> Anda sedang mengedit "${escapeHtml(item.nama)}"
                        </div>
                    `;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Gagal memuat data barang', 'error');
        });
}

function resetForm() {
    document.getElementById('itemForm').reset();
    document.getElementById('itemId').value = '';
    document.getElementById('modeAksiContainer').innerHTML = `
        <div class="alert alert-success">
            <i class="fas fa-plus"></i> <strong>Mode Tambah:</strong> Anda sedang menambah barang baru
        </div>
    `;
}

function confirmDelete(id, nama) {
    document.getElementById('confirmMessage').textContent = 
        `Apakah Anda yakin ingin menghapus barang "${nama}"?`;
    
    document.getElementById('confirmDeleteBtn').onclick = function() {
        deleteItem(id);
    };
    
    const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
    modal.show();
}

function deleteItem(id) {
    const formData = new FormData();
    formData.append('id', id);

    fetch('../../pembelian_barang/delete_stok.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(result => {
        const modal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
        if (modal) {
            modal.hide();
        }
        
        if (result.status === 'success') {
            showMessage(result.message, 'success');
            loadStokData();
        } else {
            showMessage(result.message || 'Terjadi kesalahan', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Terjadi kesalahan saat menghapus data: ' + error.message, 'error');
    });
}

function filterByTipe() {
    const selectedTipe = document.getElementById('filterTipe').value;
    const cards = document.querySelectorAll('.stok-card');
    
    cards.forEach(card => {
        if (selectedTipe === 'Semua' || card.getAttribute('data-tipe') === selectedTipe) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function loadRiwayatData() {
    const container = document.getElementById('historyListContainer');
    if (container) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Fitur riwayat sedang dalam pengembangan.
                </div>
            </div>
        `;
    }
}

function updateClearAllButton(data) {
    const clearBtn = document.getElementById('clearAllBtn');
    if (clearBtn) {
        if (data && data.length > 0) {
            clearBtn.style.display = 'inline-block';
        } else {
            clearBtn.style.display = 'none';
        }
    }
}

function clearAllData() {
    if (confirm('Apakah Anda yakin ingin menghapus SEMUA data stok? Tindakan ini tidak dapat dibatalkan!')) {
        showMessage('Fitur hapus semua data sedang dalam pengembangan', 'info');
    }
}

// Utility functions
function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe
        .toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function showLoading(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="col-12 text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Memuat data...</p>
            </div>
        `;
    }
}

function showError(message) {
    const container = document.getElementById('dataListContainer');
    if (container) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger text-center">
                    <i class="fas fa-exclamation-triangle"></i> ${escapeHtml(message)}
                </div>
            </div>
        `;
    }
}

function showMessage(message, type = 'info') {
    console.log('Showing message:', message, 'Type:', type);
    const modalMessage = document.getElementById('modalMessage');
    if (modalMessage) {
        modalMessage.textContent = message;
        const modal = new bootstrap.Modal(document.getElementById('messageModal'));
        modal.show();
    } else {
        alert(message);
    }
}