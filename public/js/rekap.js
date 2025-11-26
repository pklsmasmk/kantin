$(function() {
    const rekapBtn = $(".btn-green");
    const akhirBtn = $(".btn-red");
    const kembaliBtn = $(".btn-secondary");

    if (akhirBtn.length) {
        akhirBtn.on("click", (e) => {
            e.preventDefault();
            window.location.href = "/?q=shift__Akhiri__akhiri_shift";
        });
    }

    if (rekapBtn.length) {
        rekapBtn.on("click", (e) => {
            e.preventDefault();
            window.location.href = "/?q=shift__Rekap_Shift__rekap_detail";
        });
    }

    if (kembaliBtn.length) {
        kembaliBtn.on("click", (e) => {
            e.preventDefault();
            window.location.href = "/?q=shift";
        });
    }

    function highlightChanges() {
        const lastUpdated = $('.last-updated');
        if (lastUpdated.length) {
            lastUpdated.addClass('updated');
            
            setTimeout(() => {
                lastUpdated.removeClass('updated');
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
        const existingToast = $('.toast');
        if (existingToast.length) {
            existingToast.remove();
        }

        const toast = $('<div>')
            .addClass(`toast toast-${type}`)
            .html(`
                <div class="toast-content">
                    <span class="toast-message">${message}</span>
                    <button class="toast-close">&times;</button>
                </div>
            `);
        
        $('body').append(toast);
        
        setTimeout(() => {
            if (toast.parent().length) {
                toast.css('animation', 'slideOut 0.3s ease');
                setTimeout(() => {
                    if (toast.parent().length) {
                        toast.remove();
                    }
                }, 300);
            }
        }, 5000);
        
        const toastClose = toast.find('.toast-close');
        toastClose.on('click', () => {
            if (toast.parent().length) {
                toast.css('animation', 'slideOut 0.3s ease');
                setTimeout(() => {
                    if (toast.parent().length) {
                        toast.remove();
                    }
                }, 300);
            }
        });
    }

    $('.btn').each(function() {
        $(this).on('click', function(e) {
            $(this).css('transform', 'scale(0.98)');
            setTimeout(() => {
                $(this).css('transform', '');
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

        const clockElement = $('.last-updated');
        if (clockElement.length) {
            const originalText = clockElement.attr('data-original-text') || clockElement.text();
            clockElement.attr('data-original-text', originalText);
            clockElement.html(`${originalText} <br><small>🕒 ${timeString} - ${dateString}</small>`);
        }
    }

    setInterval(updateClock, 1000);
    updateClock();

    console.log('Sistem rekap shift telah diinisialisasi');
});