// bayar.js - VERSI DIPERBAIKI
function updateClock() {
    const now = new Date();
    const optionsDate = { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        timeZone: 'Asia/Jakarta'
    };
    const date = now.toLocaleDateString('id-ID', optionsDate);
    const time = now.toLocaleTimeString('id-ID', { 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit',
        hour12: false,
        timeZone: 'Asia/Jakarta'
    });

    const currentDateEl = document.getElementById('currentDate');
    const currentTimeEl = document.getElementById('currentTime');
    
    if (currentDateEl) currentDateEl.textContent = date;
    if (currentTimeEl) currentTimeEl.textContent = time;
    
    updateFormTime(now);
}

function updateFormTime(now) {
    const dateInput = document.querySelector('input[name="tanggal_bayar"]');
    const timeInput = document.querySelector('input[name="waktu_bayar"]');

    if (!dateInput || !timeInput) return;

    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const currentDate = `${year}-${month}-${day}`;

    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const currentTime = `${hours}:${minutes}`;

    // Only update if not manually set
    if (!dateInput.hasAttribute('data-manual')) {
        dateInput.value = currentDate;
    }
    
    if (!timeInput.hasAttribute('data-manual')) {
        timeInput.value = currentTime;
    }

    updateTimeHighlight();
}

function updateTimeHighlight() {
    const dateInput = document.querySelector('input[name="tanggal_bayar"]');
    const timeInput = document.querySelector('input[name="waktu_bayar"]');
    const highlightElement = document.getElementById('displayTime');
    
    if (dateInput && timeInput && highlightElement && dateInput.value && timeInput.value) {
        const [year, month, day] = dateInput.value.split('-');
        const formattedDate = `${day}/${month}/${year}`;
        highlightElement.textContent = `${formattedDate} | ${timeInput.value}`;
    }
}

function setCurrentTime() {
    const now = new Date();

    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const currentDate = `${year}-${month}-${day}`;

    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const currentTime = `${hours}:${minutes}`;

    const dateInput = document.querySelector('input[name="tanggal_bayar"]');
    const timeInput = document.querySelector('input[name="waktu_bayar"]');
    
    if (dateInput && timeInput) {
        dateInput.value = currentDate;
        timeInput.value = currentTime;

        dateInput.setAttribute('data-manual', 'true');
        timeInput.setAttribute('data-manual', 'true');
        
        updateTimeHighlight();
        
        // Show confirmation
        showAlert('Waktu berhasil diatur ke waktu sekarang', 'success');
    }
}

function showAlert(message, type = 'info') {
    const alertClass = type === 'error' ? 'alert-danger' : 
                     type === 'warning' ? 'alert-warning' : 
                     type === 'success' ? 'alert-success' : 'alert-info';
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alert.style.zIndex = '1050';
    alert.innerHTML = `
        <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 
                         type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 3000);
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Validasi jumlah bayar
    const jumlahBayarInput = document.querySelector('input[name="jumlah_bayar"]');
    if (jumlahBayarInput) {
        jumlahBayarInput.addEventListener('input', function() {
            // Gunakan max attribute dari HTML untuk validasi
            const maxAmount = parseFloat(this.max) || 0;
            const inputAmount = parseFloat(this.value) || 0;
            
            if (inputAmount > maxAmount) {
                this.value = maxAmount;
                showAlert('Jumlah pembayaran tidak boleh melebihi sisa bayar', 'warning');
            }
        });
    }

    // Update highlight ketika input berubah
    const dateInput = document.querySelector('input[name="tanggal_bayar"]');
    const timeInput = document.querySelector('input[name="waktu_bayar"]');
    
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            this.setAttribute('data-manual', 'true');
            updateTimeHighlight();
        });
    }

    if (timeInput) {
        timeInput.addEventListener('change', function() {
            this.setAttribute('data-manual', 'true');
            updateTimeHighlight();
        });
    }

    // Validasi form
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            const jumlahBayar = parseFloat(document.querySelector('input[name="jumlah_bayar"]').value);
            if (jumlahBayar <= 0) {
                e.preventDefault();
                showAlert('Jumlah bayar harus lebih dari 0!', 'error');
                return false;
            }
        });
    }

    // Initialize clock
    updateClock();
    setInterval(updateClock, 1000);
    updateTimeHighlight();
});