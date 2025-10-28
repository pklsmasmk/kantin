document.addEventListener("DOMContentLoaded", function () {
    const saldoInput = document.getElementById("saldo_awal");
    const shiftForm = document.querySelector(".shift-form");
    const cashdrawerSelect = document.getElementById("cashdrawer");
    const refreshBtn = document.getElementById("refreshCashdrawer");
    const confirmationModal = document.getElementById("confirmationModal");
    const confirmedForm = document.getElementById("confirmedForm");
    const cancelBtn = document.getElementById("cancelBtn");
    const confirmBtn = document.getElementById("confirmBtn");
    const submitShiftBtn = document.getElementById("submitShiftBtn");
    
    let counter = 0;
    let isSubmitting = false;

    function formatRupiah(value) {
        if (!value && value !== 0) return "Rp 0";
        const numericValue = typeof value === 'string' ? 
            parseInt(value.replace(/[^\d]/g, '')) || 0 : 
            Math.round(value);
        return "Rp " + numericValue.toLocaleString('id-ID');
    }

    function parseRupiah(value) {
        if (!value) return 0;
        return parseInt(value.replace(/[^\d]/g, '')) || 0;
    }

    function getSaldoTersedia() {
        const saldoTersediaElement = document.querySelector('.saldo-tersedia');
        if (saldoTersediaElement) {
            const text = saldoTersediaElement.textContent || '';
            const match = text.match(/Rp\s*([\d.,]+)/);
            if (match) {
                return parseRupiah(match[0]);
            }
        }
        
        const saldoAkhirElement = document.querySelector('.saldo-card:nth-child(3) .saldo-value');
        if (saldoAkhirElement) {
            return parseRupiah(saldoAkhirElement.textContent);
        }
        
        return 0;
    }

    function updateSaldoTersedia() {
        const jumlahSetoran = document.getElementById('jumlah_setoran');
        
        if (!jumlahSetoran) return;
        
        jumlahSetoran.addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^\d]/g, '');
            if (value) {
                value = parseInt(value).toLocaleString('id-ID');
                e.target.value = value;
            } else {
                e.target.value = '';
            }

            const jumlahNumeric = parseRupiah(value);
            const saldoTersedia = getSaldoTersedia();
            
            if (jumlahNumeric > saldoTersedia) {
                this.style.borderColor = '#dc3545';
                tampilkanError(`Jumlah setoran melebihi saldo tersedia. Saldo tersedia: ${formatRupiah(saldoTersedia)}`);
            } else {
                this.style.borderColor = '';
                hapusAlertYangAda();
            }
        });
    }

    function validasiInputSaldo(value) {
        const numericValue = parseRupiah(value);
        
        if (!value || value.trim() === '') {
            return {
                isValid: false,
                message: "Saldo awal harus diisi."
            };
        }
        
        if (isNaN(numericValue)) {
            return {
                isValid: false,
                message: "Saldo awal harus berupa angka."
            };
        }
        
        if (numericValue <= 0) {
            return {
                isValid: false,
                message: "Saldo awal harus lebih besar dari 0."
            };
        }
        
        return {
            isValid: true,
            numericValue: numericValue
        };
    }

    function validasiFormShift() {
        const cashdrawerValue = cashdrawerSelect ? cashdrawerSelect.value : '';
        const saldoAwalValue = saldoInput ? saldoInput.value : '';

        if (!cashdrawerValue) {
            tampilkanError("Pilih cashdrawer terlebih dahulu.");
            if (cashdrawerSelect) cashdrawerSelect.focus();
            return null;
        }

        const validasiSaldo = validasiInputSaldo(saldoAwalValue);
        if (!validasiSaldo.isValid) {
            tampilkanError(validasiSaldo.message);
            if (saldoInput) saldoInput.focus();
            return null;
        }

        return {
            cashdrawer: cashdrawerValue,
            saldoAwal: validasiSaldo.numericValue,
            saldoWarisan: getSaldoWarisan()
        };
    }

    function getSaldoWarisan() {
        const elemenWarisan = [
            document.querySelector('.saldo-warisan-info'),
            document.querySelector('.warisan-notice'),
            document.querySelector('[class*="warisan"]')
        ].filter(el => el !== null);

        for (const element of elemenWarisan) {
            const text = element.textContent || element.innerText;
            const match = text.match(/Rp\s*([\d.,]+)/);
            if (match) {
                return parseRupiah(match[0]);
            }
        }

        if (submitShiftBtn) {
            const teksTombol = submitShiftBtn.textContent || submitShiftBtn.innerText;
            const match = teksTombol.match(/Rp\s*([\d.,]+)/);
            if (match) {
                return parseRupiah(match[0]);
            }
        }

        return 0;
    }

    function tampilkanError(pesan) {
        hapusAlertYangAda();
        
        const alertError = document.createElement('div');
        alertError.className = 'alert error';
        alertError.setAttribute('role', 'alert');
        alertError.textContent = pesan;
        
        const infoUser = document.querySelector('.user-info');
        if (infoUser && infoUser.parentNode) {
            infoUser.parentNode.insertBefore(alertError, infoUser.nextSibling);
        } else {
            const main = document.querySelector('main');
            if (main) {
                main.insertBefore(alertError, main.firstChild);
            }
        }
        
        setTimeout(() => {
            if (alertError.parentNode) {
                alertError.style.opacity = '0';
                setTimeout(() => {
                    if (alertError.parentNode) {
                        alertError.parentNode.removeChild(alertError);
                    }
                }, 300);
            }
        }, 5000);
    }

    function tampilkanSukses(pesan) {
        hapusAlertYangAda();
        
        const alertSukses = document.createElement('div');
        alertSukses.className = 'alert success';
        alertSukses.setAttribute('role', 'status');
        alertSukses.textContent = pesan;
        
        const infoUser = document.querySelector('.user-info');
        if (infoUser && infoUser.parentNode) {
            infoUser.parentNode.insertBefore(alertSukses, infoUser.nextSibling);
        }
        
        setTimeout(() => {
            if (alertSukses.parentNode) {
                alertSukses.style.opacity = '0';
                setTimeout(() => {
                    if (alertSukses.parentNode) {
                        alertSukses.parentNode.removeChild(alertSukses);
                    }
                }, 300);
            }
        }, 5000);
    }

    function hapusAlertYangAda() {
        const alertYangAda = document.querySelectorAll('.alert');
        alertYangAda.forEach(alert => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        });
    }

    function tampilkanModalKonfirmasi(dataForm) {
        if (!confirmationModal) return;
        
        const modalCashdrawer = document.getElementById('modalCashdrawer');
        const modalSaldoAwal = document.getElementById('modalSaldoAwal');
        const modalSaldoWarisan = document.getElementById('modalSaldoWarisan');
        const modalTotalSaldo = document.getElementById('modalTotalSaldo');
        
        if (modalCashdrawer) modalCashdrawer.textContent = dataForm.cashdrawer;
        if (modalSaldoAwal) modalSaldoAwal.textContent = formatRupiah(dataForm.saldoAwal);
        
        const saldoWarisan = dataForm.saldoWarisan || 0;
        if (modalSaldoWarisan) modalSaldoWarisan.textContent = formatRupiah(saldoWarisan);
        
        const totalSaldo = dataForm.saldoAwal + saldoWarisan;
        if (modalTotalSaldo) modalTotalSaldo.textContent = formatRupiah(totalSaldo);
        
        const confirmedCashdrawer = document.getElementById('confirmedCashdrawer');
        const confirmedSaldoAwal = document.getElementById('confirmedSaldoAwal');
        
        if (confirmedCashdrawer) confirmedCashdrawer.value = dataForm.cashdrawer;
        if (confirmedSaldoAwal) confirmedSaldoAwal.value = dataForm.saldoAwal;
        
        confirmationModal.classList.add('aktif');
        document.body.style.overflow = 'hidden';
        
        setTimeout(() => {
            if (cancelBtn) cancelBtn.focus();
        }, 100);
    }

    function sembunyikanModalKonfirmasi() {
        if (!confirmationModal) return;
        
        confirmationModal.classList.remove('aktif');
        document.body.style.overflow = '';
        
        if (submitShiftBtn) {
            setTimeout(() => submitShiftBtn.focus(), 100);
        }
    }

    function inisialisasiInputSaldo() {
        if (!saldoInput) return;

        saldoInput.addEventListener("input", function (e) {
            let value = e.target.value.replace(/[^\d]/g, "");
            if (value) {
                value = parseInt(value).toLocaleString('id-ID');
                e.target.value = value;
            } else {
                e.target.value = '';
            }
        });
        
        saldoInput.addEventListener("blur", function(e) {
            const validasi = validasiInputSaldo(this.value);
            if (!validasi.isValid && this.value.trim() !== '') {
                tampilkanError(validasi.message);
                this.value = '';
                this.focus();
            }
        });

        saldoInput.addEventListener("keypress", function(e) {
            const char = String.fromCharCode(e.keyCode || e.which);
            if (!/[\d]/.test(char)) {
                e.preventDefault();
            }
        });

        saldoInput.addEventListener("paste", function(e) {
            e.preventDefault();
            const teksTempel = (e.clipboardData || window.clipboardData).getData('text');
            const nilaiNumerik = teksTempel.replace(/[^\d]/g, '');
            if (nilaiNumerik) {
                const nilaiTerformat = parseInt(nilaiNumerik).toLocaleString('id-ID');
                this.value = nilaiTerformat;
                
                const eventInput = new Event('input', { bubbles: true });
                this.dispatchEvent(eventInput);
            }
        });

        saldoInput.addEventListener("keyup", function(e) {
            if (this.value.trim() === '') return;
            
            const validasi = validasiInputSaldo(this.value);
            if (!validasi.isValid) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '';
            }
        });
    }

    function inisialisasiPengirimanForm() {
        if (!shiftForm) return;

        shiftForm.addEventListener("submit", function (e) {
            e.preventDefault();

            if (isSubmitting) {
                tampilkanError("Sedang memproses, harap tunggu...");
                return;
            }

            const dataForm = validasiFormShift();
            if (!dataForm) {
                return;
            }

            tampilkanModalKonfirmasi(dataForm);
        });
    }

    function inisialisasiRefreshCashdrawer() {
        if (!refreshBtn || !cashdrawerSelect) return;

        refreshBtn.addEventListener("click", function () {
            if (this.disabled) return;
            
            counter += 1;
            if (counter > 3) counter = 1; 

            const opsiPlaceholder = cashdrawerSelect.querySelector('option[value=""]');
            cashdrawerSelect.innerHTML = '';
            if (opsiPlaceholder) {
                cashdrawerSelect.appendChild(opsiPlaceholder);
            }

            const labelOpsi = `Kasir ${String(counter).padStart(2, "0")} - Cashdrawer ${counter}`;
            const opsi = document.createElement("option");
            opsi.value = labelOpsi;
            opsi.textContent = labelOpsi;
            opsi.selected = true;

            cashdrawerSelect.appendChild(opsi);

            const htmlAsli = this.innerHTML;
            this.innerHTML = "✓";
            this.disabled = true;
            
            setTimeout(() => {
                this.innerHTML = htmlAsli;
                this.disabled = false;
            }, 1000);
        });

        const opsiTerpilih = cashdrawerSelect.options[cashdrawerSelect.selectedIndex];
        if (opsiTerpilih && opsiTerpilih.value) {
            const match = opsiTerpilih.value.match(/Kasir\s+(\d+)/);
            if (match) {
                counter = parseInt(match[1]);
            }
        }
    }

    function inisialisasiPenanganModal() {
        if (!cancelBtn || !confirmBtn) return;

        cancelBtn.addEventListener('click', function() {
            sembunyikanModalKonfirmasi();
        });

        confirmBtn.addEventListener('click', function() {
            if (isSubmitting) return;
            
            isSubmitting = true;
            
            const teksAsli = confirmBtn.textContent;
            confirmBtn.textContent = "Memulai...";
            confirmBtn.disabled = true;

            if (confirmedForm) {
                confirmedForm.submit();
            } else {
                shiftForm.submit();
            }
        });

        confirmationModal.addEventListener('click', function(e) {
            if (e.target === confirmationModal) {
                sembunyikanModalKonfirmasi();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && confirmationModal.classList.contains('aktif')) {
                sembunyikanModalKonfirmasi();
            }
        });

        confirmationModal.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && confirmationModal.classList.contains('aktif')) {
                e.preventDefault();
                confirmBtn.click();
            }
        });
    }

    function inisialisasiTab() {
        const tabs = document.querySelectorAll(".tabs button");
        const panels = document.querySelectorAll(".tab-panel");

        panels.forEach(panel => {
            if (!panel.classList.contains('is-active')) {
                panel.style.display = 'none';
            }
        });

        tabs.forEach((tab) => {
            tab.addEventListener("click", function (e) {
                e.preventDefault();
                
                const target = this.dataset.tab;
                if (!target) return;

                tabs.forEach((t) => {
                    t.classList.remove("active");
                    t.setAttribute("aria-selected", "false");
                    t.setAttribute("tabindex", "-1");
                });

                this.classList.add("active");
                this.setAttribute("aria-selected", "true");
                this.setAttribute("tabindex", "0");

                panels.forEach((panel) => {
                    const isActive = panel.dataset.tabPanel === target;
                    panel.classList.toggle("is-active", isActive);
                    
                    if (isActive) {
                        panel.style.display = 'block';
                        panel.dispatchEvent(new CustomEvent('tabActivated', { 
                            detail: { tabName: target } 
                        }));
                    } else {
                        panel.style.display = 'none';
                    }
                });

                const url = new URL(window.location);
                url.searchParams.set('tab', target);
                window.history.replaceState({}, '', url);

                setTimeout(() => {
                    const panelAktif = document.querySelector(`.tab-panel.is-active`);
                    if (panelAktif) {
                        const fokusPertama = panelAktif.querySelector(
                            'button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
                        );
                        if (fokusPertama) {
                            fokusPertama.focus();
                        }
                    }
                }, 100);
            });
        });

        const parameterUrl = new URLSearchParams(window.location.search);
        const tabAktif = parameterUrl.get('tab') || 'current';
        
        const tabAwal = document.querySelector(`.tabs button[data-tab="${tabAktif}"]`);
        if (tabAwal) {
            tabAwal.click();
        } else if (tabs.length > 0) {
            tabs[0].click();
        }
    }

    function inisialisasiFormSetoran() {
        const jenisSetoran = document.getElementById('jenis_setoran');
        const grupDetailLainnya = document.getElementById('detail_lainnya_group');
        const jumlahSetoran = document.getElementById('jumlah_setoran');
        const formSetoran = document.querySelector('.setoran-form');

        if (jenisSetoran && grupDetailLainnya) {
            jenisSetoran.addEventListener('change', function() {
                if (this.value === 'lainnya') {
                    grupDetailLainnya.style.display = 'block';
                    setTimeout(() => {
                        const inputDetail = document.getElementById('detail_lainnya');
                        if (inputDetail) inputDetail.focus();
                    }, 100);
                } else {
                    grupDetailLainnya.style.display = 'none';
                    document.getElementById('detail_lainnya').value = '';
                }
            });

            if (jenisSetoran.value === 'lainnya') {
                grupDetailLainnya.style.display = 'block';
            }
        }

        updateSaldoTersedia();
        
        if (jumlahSetoran) {
            jumlahSetoran.addEventListener('blur', function() {
                const value = this.value.replace(/[^\d]/g, '');
                if (value && parseInt(value) === 0) {
                    tampilkanError('Jumlah setoran harus lebih besar dari 0.');
                    this.value = '';
                    this.focus();
                }
            });

            jumlahSetoran.addEventListener('keypress', function(e) {
                const char = String.fromCharCode(e.keyCode || e.which);
                if (!/[\d]/.test(char)) {
                    e.preventDefault();
                }
            });
        }

        if (formSetoran) {
            formSetoran.addEventListener('submit', function(e) {
                if (isSubmitting) {
                    e.preventDefault();
                    tampilkanError('Sedang memproses setoran sebelumnya...');
                    return;
                }

                const tombolSubmit = this.querySelector('.setoran-submit');
                const jumlah = document.getElementById('jumlah_setoran');
                const jenis = document.getElementById('jenis_setoran');
                const metode = document.getElementById('metode_setoran');
                const keterangan = document.getElementById('keterangan_setoran');

                if (!jumlah || !jenis || !metode || !keterangan) {
                    e.preventDefault();
                    tampilkanError('Form tidak lengkap. Silakan refresh halaman.');
                    return;
                }

                const nilaiJumlah = jumlah.value;
                const nilaiJenis = jenis.value;
                const nilaiMetode = metode.value;
                const nilaiKeterangan = keterangan.value.trim();

                if (!nilaiJumlah || !nilaiJenis || !nilaiMetode || !nilaiKeterangan) {
                    e.preventDefault();
                    tampilkanError('Semua field bertanda * harus diisi.');
                    return;
                }

                const jumlahNumerik = parseRupiah(nilaiJumlah);
                if (jumlahNumerik <= 0) {
                    e.preventDefault();
                    tampilkanError('Jumlah setoran harus lebih besar dari 0.');
                    jumlah.focus();
                    return;
                }

                const saldoTersedia = getSaldoTersedia();
                if (jumlahNumerik > saldoTersedia) {
                    e.preventDefault();
                    tampilkanError(`Jumlah setoran melebihi saldo tersedia. Saldo tersedia: ${formatRupiah(saldoTersedia)}`);
                    jumlah.focus();
                    return;
                }

                isSubmitting = true;
                if (tombolSubmit && !tombolSubmit.disabled) {
                    tombolSubmit.disabled = true;
                    const htmlAsli = tombolSubmit.innerHTML;
                    tombolSubmit.innerHTML = '<span>⏳</span> Memproses Setoran...';
                    
                    setTimeout(() => {
                        if (tombolSubmit.disabled) {
                            tombolSubmit.innerHTML = htmlAsli;
                            tombolSubmit.disabled = false;
                            isSubmitting = false;
                        }
                    }, 10000); 
                }
            });
        }
    }

    function inisialisasiNavigasiKeyboard() {
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key >= '1' && e.key <= '3') {
                e.preventDefault();
                const indexTab = parseInt(e.key) - 1;
                const tabs = document.querySelectorAll('.tabs button');
                if (tabs[indexTab]) {
                    tabs[indexTab].click();
                }
            }
        });
    }

    function inisialisasiSimpanOtomatis() {
        const elemenForm = document.querySelectorAll('input, select, textarea');
        const kunciSimpanOtomatis = 'shift_form_draft';
        
        const drafTersimpan = localStorage.getItem(kunciSimpanOtomatis);
        if (drafTersimpan) {
            try {
                const draf = JSON.parse(drafTersimpan);
                Object.keys(draf).forEach(key => {
                    const element = document.querySelector(`[name="${key}"]`);
                    if (element && element.type !== 'password') {
                        element.value = draf[key];
                    }
                });
                
                const adaData = Object.values(draf).some(val => val && val.toString().trim() !== '');
                if (adaData) {
                    setTimeout(() => {
                        tampilkanSukses('Data sebelumnya telah dipulihkan. Lanjutkan mengisi form.');
                    }, 1000);
                }
            } catch (e) {
                console.warn('Gagal memuat draf form:', e);
            }
        }
        
        elemenForm.forEach(element => {
            if (element.name && element.type !== 'password') {
                element.addEventListener('input', debounce(function() {
                    const dataForm = {};
                    elemenForm.forEach(el => {
                        if (el.name && el.type !== 'password') {
                            dataForm[el.name] = el.value;
                        }
                    });
                    localStorage.setItem(kunciSimpanOtomatis, JSON.stringify(dataForm));
                }, 500));
            }
        });
        
        document.addEventListener('submit', function() {
            setTimeout(() => {
                localStorage.removeItem(kunciSimpanOtomatis);
            }, 1000);
        });
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function inisialisasiAplikasi() {
        console.log('Menginisialisasi aplikasi shift...');
        
        try {
            inisialisasiTab();
            inisialisasiInputSaldo();
            inisialisasiPengirimanForm();
            inisialisasiRefreshCashdrawer();
            inisialisasiPenanganModal();
            inisialisasiFormSetoran();
            inisialisasiNavigasiKeyboard();
            inisialisasiSimpanOtomatis();
            
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            
            console.log('Aplikasi shift berhasil diinisialisasi');
        } catch (error) {
            console.error('Error menginisialisasi aplikasi:', error);
            tampilkanError('Terjadi error dalam memuat aplikasi. Silakan refresh halaman.');
        }
    }

    inisialisasiAplikasi();

    window.addEventListener('error', function(e) {
        console.error('Terjadi error global:', e.error);
    });
});