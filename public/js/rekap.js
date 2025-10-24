document.addEventListener("DOMContentLoaded", () => {
    const rekapBtn = document.querySelector(".btn-green");
    const akhirBtn = document.querySelector(".btn-red");
    const kembaliBtn = document.querySelector(".btn-secondary");

    if (akhirBtn) {
        akhirBtn.addEventListener("click", (e) => {
            e.preventDefault();
            window.location.href = "/?q=shift__Akhiri__akhiri_shift";
        });
    }

    if (rekapBtn) {
        rekapBtn.addEventListener("click", (e) => {
            e.preventDefault();
            window.location.href = "/?q=shift__Rekap_Shift__rekap_detail";
        });
    }

    if (kembaliBtn) {
        kembaliBtn.addEventListener("click", (e) => {
            e.preventDefault();
            window.location.href = "/?q=shift";
        });
    }

    function highlightChanges() {
        const lastUpdated = document.querySelector('.last-updated');
        if (lastUpdated) {
            lastUpdated.classList.add('updated');
            
            setTimeout(() => {
                lastUpdated.classList.remove('updated');
            }, 3000);
        }
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('updated')) {
        highlightChanges();
        showToast('Data berhasil diperbarui dari shift utama', 'success');
    }

    if (urlParams.has('sync')) {
        showToast('Data berhasil tersinkronisasi', 'success');
    }

    function showToast(message, type = 'info') {
        const existingToast = document.querySelector('.toast');
        if (existingToast) {
            existingToast.remove();
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <span class="toast-message">${message}</span>
                <button class="toast-close">&times;</button>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }
        }, 5000);
        
        const toastClose = toast.querySelector('.toast-close');
        toastClose.addEventListener('click', () => {
            if (toast.parentNode) {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }
        });
    }

    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });

    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('id-ID', { 
            hour: '2-digit', 
            minute: '2-digit',
            second: '2-digit'
        });
        const dateString = now.toLocaleDateString('id-ID', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        const clockElement = document.querySelector('.last-updated');
        if (clockElement) {
            const originalText = clockElement.getAttribute('data-original-text') || clockElement.textContent;
            clockElement.setAttribute('data-original-text', originalText);
            clockElement.innerHTML = `${originalText} <br><small>🕒 ${timeString} - ${dateString}</small>`;
        }
    }

    setInterval(updateClock, 1000);
    updateClock();

    console.log('Sistem rekap shift telah diinisialisasi');
});