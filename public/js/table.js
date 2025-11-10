// ======== DATA MANAGEMENT =========
let items = [];

// ======== CONFIGURATION =========
const API_BASE_URL = ''; // Kosongkan jika file PHP berada di folder yang sama

// ======== DEBUGGING =========
function debugElements() {
    const criticalElements = [
        'barang_id', 'jumlah', 'alasan_lainnya', 
        'keterangan', 'formRetur', 'stokTersedia'
    ];
    
    console.group('🔍 Debug Elements');
    criticalElements.forEach(id => {
        const element = document.getElementById(id);
        console.log(`${element ? '✅' : '❌'} ${id}:`, element);
    });
    console.groupEnd();
}

// ======== ELEMENTS =========
function getElement(id) {
    const element = document.getElementById(id);
    if (!element) {
        console.warn(`⚠️ Element dengan id '${id}' tidak ditemukan!`);
    }
    return element;
}

// ======== HELPERS =========
function showModal(message) {
    // Gunakan Bootstrap modal yang sudah ada di HTML
    const modalElement = document.getElementById('messageModal');
    if (modalElement) {
        const modal = new bootstrap.Modal(modalElement);
        const modalMessage = document.getElementById('modalMessage');
        if (modalMessage) {
            modalMessage.textContent = message;
            modal.show();
        }
    } else {
        alert(message);
    }
}

function showAlert(message, type) {
    const alertContainer = getElement('alertContainer');
    if (!alertContainer) return;
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show mt-3" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    alertContainer.innerHTML = alertHtml;
    
    // Auto dismiss setelah 5 detik
    setTimeout(() => {
        const alert = alertContainer.querySelector('.alert');
        if (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}

// ======== FUNGSI UNTUK FITUR RETUR =========
function loadDaftarBarang() {
    console.log("🔄 Memuat daftar barang untuk retur...");
    
    fetch(`${API_BASE_URL}get_barang.php`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("✅ Data daftar barang:", data);
            const select = getElement('barang_id');
            if (!select) return;
            
            if (Array.isArray(data)) {
                select.innerHTML = '<option value="">-- Pilih Barang --</option>';
                data.forEach(barang => {
                    if (!barang.id || !barang.nama) return;
                    
                    const stok = parseInt(barang.stok) || 0;
                    const option = document.createElement('option');
                    option.value = barang.id;
                    option.textContent = `${barang.nama} (Stok: ${stok})`;
                    option.setAttribute('data-stok', stok);
                    select.appendChild(option);
                });
                
                if (select.options.length === 1) {
                    select.innerHTML = '<option value="">-- Tidak ada barang dengan stok --</option>';
                }
            } else {
                console.error('❌ Format data tidak valid:', data);
                select.innerHTML = '<option value="">-- Error memuat data --</option>';
            }
        })
        .catch(error => {
            console.error('❌ Gagal memuat daftar barang:', error);
            const select = getElement('barang_id');
            if (select) {
                select.innerHTML = '<option value="">-- Error memuat data --</option>';
            }
            showAlert('Gagal memuat daftar barang: ' + error.message, 'danger');
        });
}

function validateJumlahRetur() {
    const selectedOption = document.querySelector('#barang_id option:selected');
    if (!selectedOption || !selectedOption.value) return true;
    
    const maxStok = parseInt(selectedOption.getAttribute('data-stok')) || 0;
    const jumlahRetur = parseInt(getElement('jumlah').value) || 0;
    
    if (jumlahRetur > maxStok) {
        showAlert(`Jumlah retur tidak boleh melebihi stok yang tersedia (${maxStok})`, 'warning');
        getElement('jumlah').value = maxStok;
        return false;
    }
    return true;
}

function handleAlasanReturChange() {
    const alasanInputs = document.querySelectorAll('input[name="alasan"]');
    const container = getElement('alasanLainnyaContainer');
    const inputAlasanLainnya = getElement('alasan_lainnya');
    
    if (!container || !inputAlasanLainnya) return;
    
    let selectedAlasan = '';
    alasanInputs.forEach(input => {
        if (input.checked) {
            selectedAlasan = input.value;
        }
    });
    
    if (selectedAlasan === 'Lainnya') {
        container.style.display = 'block';
        inputAlasanLainnya.required = true;
    } else {
        container.style.display = 'none';
        inputAlasanLainnya.required = false;
        inputAlasanLainnya.value = '';
    }
}

function handleReturSubmit(event) {
    event.preventDefault();
    
    const barangSelect = getElement('barang_id');
    const selectedOption = barangSelect?.options[barangSelect.selectedIndex];
    
    if (!selectedOption || !selectedOption.value) {
        return showAlert('Pilih barang terlebih dahulu!', 'warning');
    }
    
    const maxStok = parseInt(selectedOption.getAttribute('data-stok')) || 0;
    
    // Get selected alasan
    const alasanChecked = document.querySelector('input[name="alasan"]:checked');
    if (!alasanChecked) {
        return showAlert('Pilih alasan retur!', 'warning');
    }
    
    const formData = {
        barang_id: parseInt(barangSelect.value),
        jumlah: parseInt(getElement('jumlah').value) || 0,
        alasan: alasanChecked.value,
        alasan_lainnya: getElement('alasan_lainnya').value,
        keterangan: getElement('keterangan').value
    };

    // Validasi
    if (!formData.barang_id) return showAlert('Pilih barang terlebih dahulu!', 'warning');
    if (formData.jumlah <= 0) return showAlert('Jumlah retur harus lebih dari 0!', 'warning');
    if (formData.jumlah > maxStok) return showAlert(`Jumlah retur melebihi stok tersedia (${maxStok})!`, 'warning');
    if (!formData.alasan) return showAlert('Pilih alasan retur!', 'warning');
    if (formData.alasan === 'Lainnya' && !formData.alasan_lainnya) {
        return showAlert('Harap isi alasan retur lainnya!', 'warning');
    }

    if (!confirm('Apakah Anda yakin ingin memproses retur barang ini?')) {
        return;
    }

    // Show loading
    const loading = getElement('loading');
    const submitBtn = document.querySelector('#formRetur button[type="submit"]');
    if (loading) loading.style.display = 'block';
    if (submitBtn) submitBtn.disabled = true;

    fetch(`${API_BASE_URL}proses_retur.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(formData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.status === 'success') {
            showAlert('Retur berhasil diproses!', 'success');
            // Reset form retur
            const form = getElement('formRetur');
            if (form) form.reset();
            
            const stokTersedia = getElement('stokTersedia');
            if (stokTersedia) stokTersedia.textContent = '0';
            
            // Refresh data
            loadDaftarBarang();
            loadRiwayatRetur();
        } else {
            showAlert('Error: ' + (data.message || 'Gagal memproses retur!'), 'danger');
        }
    })
    .catch(error => {
        console.error('❌ Error:', error);
        showAlert('Gagal memproses retur: ' + error.message, 'danger');
    })
    .finally(() => {
        // Hide loading
        if (loading) loading.style.display = 'none';
        if (submitBtn) submitBtn.disabled = false;
    });
}

function loadRiwayatRetur() {
    fetch(`${API_BASE_URL}riwayat_retur.php`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            const container = getElement('riwayatReturBody');
            if (!container) return;

            if (!Array.isArray(data) || data.length === 0 || data.message) {
                const message = data.message || 'Belum ada riwayat retur';
                container.innerHTML = `<tr><td colspan="4" class="text-center">${message}</td></tr>`;
                return;
            }

            container.innerHTML = data.map(retur => `
                <tr>
                    <td>${retur.nama_barang || 'Unknown'}</td>
                    <td>${retur.jumlah || 0}</td>
                    <td>${retur.alasan || '-'}</td>
                    <td>${formatDate(retur.tanggal)}</td>
                </tr>
            `).join('');
        })
        .catch(error => {
            console.error('Gagal memuat riwayat retur:', error);
            const container = getElement('riwayatReturBody');
            if (container) {
                container.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error loading data</td></tr>';
            }
            showAlert('Gagal memuat riwayat retur: ' + error.message, 'danger');
        });
}

function formatDate(dateString) {
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return dateString;
    }
}

// ======== INITIALIZATION =========
function initializeEventListeners() {
    console.log("🔌 Initializing event listeners...");
    
    const form = getElement('formRetur');
    const jumlahInput = getElement('jumlah');
    const barangSelect = getElement('barang_id');

    // Event listener untuk form submission
    if (form) {
        form.addEventListener('submit', handleReturSubmit);
        console.log("✅ Form event listener added");
    }

    // Event listener untuk alasan retur
    const alasanInputs = document.querySelectorAll('input[name="alasan"]');
    alasanInputs.forEach(input => {
        input.addEventListener('change', handleAlasanReturChange);
    });
    console.log(`✅ ${alasanInputs.length} alasan inputs event listeners added`);

    // Event listener untuk validasi jumlah
    if (jumlahInput) {
        jumlahInput.addEventListener('input', validateJumlahRetur);
        console.log("✅ Jumlah input event listener added");
    }
    
    // Event listener untuk perubahan barang
    if (barangSelect) {
        barangSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const stokTersedia = getElement('stokTersedia');
            
            if (selectedOption && stokTersedia) {
                const maxStok = parseInt(selectedOption.getAttribute('data-stok')) || 0;
                stokTersedia.textContent = maxStok;
                
                if (maxStok === 0 && selectedOption.value) {
                    showAlert('Stok barang ini habis, tidak dapat melakukan retur', 'warning');
                    if (jumlahInput) jumlahInput.value = '';
                }
            }
        });
        console.log("✅ Barang select event listener added");
    }

    console.log("✅ All event listeners initialized");
}

function init() {
    console.log("🚀 Starting retur application...");
    debugElements();
    initializeEventListeners();
    
    // Load data retur saat pertama kali
    loadDaftarBarang();
    loadRiwayatRetur();
    
    console.log("✅ Retur application ready!");
}

// Start when ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}