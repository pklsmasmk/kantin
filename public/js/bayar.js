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

    document.getElementById('currentDate').textContent = date;
    document.getElementById('currentTime').textContent = time;
    updateFormTime(now);
}

function updateFormTime(now) {
    const dateInput = document.querySelector('input[name="tanggal_bayar"]');
    const timeInput = document.querySelector('input[name="waktu_bayar"]');

    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    const currentDate = `${year}-${month}-${day}`;

    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const currentTime = `${hours}:${minutes}`;

    if (!dateInput.hasAttribute('data-manual') && dateInput.value === '<?= $defaultTanggal ?>') {
        dateInput.value = currentDate;
    }
    
    if (!timeInput.hasAttribute('data-manual') && timeInput.value === '<?= $defaultWaktu ?>') {
        timeInput.value = currentTime;
    }

    updateTimeHighlight();
}

function updateTimeHighlight() {
    const dateInput = document.querySelector('input[name="tanggal_bayar"]');
    const timeInput = document.querySelector('input[name="waktu_bayar"]');
    const highlightElement = document.getElementById('displayTime');
    
    if (dateInput.value && timeInput.value) {
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
    
    dateInput.value = currentDate;
    timeInput.value = currentTime;

    dateInput.setAttribute('data-manual', 'true');
    timeInput.setAttribute('data-manual', 'true');
    
    updateTimeHighlight();
}

// Event Listeners
document.querySelector('input[name="jumlah_bayar"]').addEventListener('input', function() {
    const maxAmount =  $sisaBayar;
    const inputAmount = parseFloat(this.value) || 0;
    
    if (inputAmount > maxAmount) {
        this.value = maxAmount;
        alert('Jumlah pembayaran tidak boleh melebihi sisa bayar: <?= formatRupiah($sisaBayar) ?>');
    }
});

document.querySelector('input[name="tanggal_bayar"]').addEventListener('change', function() {
    this.setAttribute('data-manual', 'true');
    updateTimeHighlight();
});

document.querySelector('input[name="waktu_bayar"]').addEventListener('change', function() {
    this.setAttribute('data-manual', 'true');
    updateTimeHighlight();
});

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    updateClock();
    setInterval(updateClock, 1000);
    updateTimeHighlight();
    
    // Set default waktu bayar to current time
    const now = new Date();
    const timeString = now.getHours().toString().padStart(2, '0') + ':' + 
                     now.getMinutes().toString().padStart(2, '0');
    
    document.querySelector('input[name="waktu_bayar"]').value = timeString;
    updateTimeHighlight();
});