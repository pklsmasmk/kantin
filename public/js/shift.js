$(function() {
    const saldoInput = $("#saldo_awal");
    const shiftForm = $(".shift-form");
    const cashdrawerSelect = $("#cashdrawer");
    const refreshBtn = $("#refreshCashdrawer");
    const confirmationModal = $("#confirmationModal");
    const confirmedForm = $("#confirmedForm");
    const cancelBtn = $("#cancelBtn");
    const confirmBtn = $("#confirmBtn");
    const submitShiftBtn = $("#submitShiftBtn");
    
    let counter = 0;
    let isSubmitting = false;
    let saldoAkhirSebelumnya = 0;

    function getSaldoAkhirSebelumnya() {
        const elementsToCheck = [
            '.saldo-rekomendasi',
            '.info',
            '#lastData',
            '.compact-card:first .saldo-akhir',
            '.history-list .compact-card:first .saldo-akhir'
        ];

        for (const selector of elementsToCheck) {
            const element = $(selector);
            if (element.length) {
                const text = element.text();
                const patterns = [
                    /Saldo akhir:\s*Rp\s*([\d.,]+)/i,
                    /akhir[^:]*:\s*Rp\s*([\d.,]+)/i,
                    /Rp\s*([\d.,]+)/
                ];
                
                for (const pattern of patterns) {
                    const match = text.match(pattern);
                    if (match) {
                        const saldo = parseRupiah(match[0]);
                        if (saldo > 0) {
                            console.log('Saldo akhir sebelumnya ditemukan:', saldo, 'dari:', selector);
                            return saldo;
                        }
                    }
                }
            }
        }

        if (submitShiftBtn.length) {
            const text = submitShiftBtn.text();
            const match = text.match(/Rp\s*([\d.,]+)/);
            if (match) {
                const saldo = parseRupiah(match[0]);
                if (saldo > 0) {
                    console.log('Saldo akhir sebelumnya ditemukan dari tombol:', saldo);
                    return saldo;
                }
            }
        }

        console.log('Tidak ditemukan saldo akhir sebelumnya');
        return 0;
    }

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
        const saldoTersediaElement = $('.saldo-tersedia');
        if (saldoTersediaElement.length) {
            const text = saldoTersediaElement.text() || '';
            const match = text.match(/Rp\s*([\d.,]+)/);
            if (match) {
                return parseRupiah(match[0]);
            }
        }
        
        const saldoElements = [
            $('.saldo-value'),
            $('.saldo-akhir'),
            $('[class*="saldo"]')
        ];
        
        for (const element of saldoElements) {
            if (element.length) {
                const text = element.text();
                const match = text.match(/Rp\s*([\d.,]+)/);
                if (match) {
                    return parseRupiah(match[0]);
                }
            }
        }
        
        return 0;
    }

    function updateSaldoTersedia() {
        const jumlahSetoran = $('#jumlah_setoran');
        
        if (!jumlahSetoran.length) return;
        
        jumlahSetoran.on('input', function(e) {
            let value = $(this).val().replace(/[^\d]/g, '');
            if (value) {
                value = parseInt(value).toLocaleString('id-ID');
                $(this).val(value);
            } else {
                $(this).val('');
            }

            const jumlahNumeric = parseRupiah(value);
            const saldoTersedia = getSaldoTersedia();
            
            if (jumlahNumeric > saldoTersedia) {
                $(this).css('border-color', '#dc3545');
                tampilkanError(`Jumlah setoran melebihi saldo tersedia. Saldo tersedia: ${formatRupiah(saldoTersedia)}`);
            } else {
                $(this).css('border-color', '');
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
        
        // VALIDASI BARU: Cek apakah saldo awal kurang dari saldo akhir sebelumnya
        const isShiftPertama = $('.info').text().includes('SHIFT PERTAMA');
        if (!isShiftPertama && saldoAkhirSebelumnya > 0 && numericValue < saldoAkhirSebelumnya) {
            return {
                isValid: false,
                message: `Saldo awal tidak boleh kurang dari saldo akhir shift sebelumnya (${formatRupiah(saldoAkhirSebelumnya)}).`
            };
        }
        
        return {
            isValid: true,
            numericValue: numericValue
        };
    }

    function validasiFormShift() {
        const isShiftPertama = $('.info').text().includes('SHIFT PERTAMA');
        const cashdrawerValue = isShiftPertama && cashdrawerSelect.length ? cashdrawerSelect.val() : 'Cashdrawer-Otomatis';
        const saldoAwalValue = saldoInput.length ? saldoInput.val() : '';

        if (isShiftPertama && !cashdrawerValue) {
            tampilkanError("Pilih cashdrawer terlebih dahulu.");
            if (cashdrawerSelect.length) cashdrawerSelect.trigger('focus');
            return null;
        }

        const validasiSaldo = validasiInputSaldo(saldoAwalValue);
        if (!validasiSaldo.isValid) {
            tampilkanError(validasiSaldo.message);
            if (saldoInput.length) saldoInput.trigger('focus');
            return null;
        }

        return {
            cashdrawer: cashdrawerValue,
            saldoAwal: validasiSaldo.numericValue,
            isShiftPertama: isShiftPertama
        };
    }

    function tampilkanError(pesan) {
        hapusAlertYangAda();
        
        const alertError = $('<div>')
            .addClass('alert error')
            .attr('role', 'alert')
            .text(pesan);
        
        const infoUser = $('.user-info');
        if (infoUser.length && infoUser.parent().length) {
            infoUser.after(alertError);
        } else {
            const main = $('main');
            if (main.length) {
                main.prepend(alertError);
            }
        }
        
        setTimeout(() => {
            if (alertError.parent().length) {
                alertError.css('opacity', '0');
                setTimeout(() => {
                    if (alertError.parent().length) {
                        alertError.remove();
                    }
                }, 300);
            }
        }, 5000);
    }

    function tampilkanSukses(pesan) {
        hapusAlertYangAda();
        
        const alertSukses = $('<div>')
            .addClass('alert success')
            .attr('role', 'status')
            .text(pesan);
        
        const infoUser = $('.user-info');
        if (infoUser.length && infoUser.parent().length) {
            infoUser.after(alertSukses);
        }
        
        setTimeout(() => {
            if (alertSukses.parent().length) {
                alertSukses.css('opacity', '0');
                setTimeout(() => {
                    if (alertSukses.parent().length) {
                        alertSukses.remove();
                    }
                }, 300);
            }
        }, 5000);
    }

    function hapusAlertYangAda() {
        $('.alert').remove();
    }

    function tampilkanModalKonfirmasi(dataForm) {
        if (!confirmationModal.length) return;
        
        const modalCashdrawer = $('#modalCashdrawer');
        const modalSaldoAwal = $('#modalSaldoAwal');
        
        if (modalCashdrawer.length) {
            modalCashdrawer.text(dataForm.cashdrawer);
        }
        
        if (modalSaldoAwal.length) {
            modalSaldoAwal.text(formatRupiah(dataForm.saldoAwal));
        }
        
        const modalBody = $('.body-modal-konfirmasi');
        if (modalBody.length) {
            if (dataForm.isShiftPertama) {
                modalBody.find('.item-detail:contains("Sumber Saldo")').remove();
            } else {
                // Tampilkan informasi saldo akhir sebelumnya di modal
                let saldoSebelumnyaElement = modalBody.find('.item-detail:contains("Sumber Saldo")');
                if (!saldoSebelumnyaElement.length) {
                    // Tambahkan elemen jika belum ada
                    saldoSebelumnyaElement = $(`
                        <div class="item-detail">
                            <span class="label-detail">Sumber Saldo:</span>
                            <span class="nilai-detail">${formatRupiah(saldoAkhirSebelumnya)}</span>
                        </div>
                    `);
                    modalBody.find('.detail-konfirmasi').append(saldoSebelumnyaElement);
                } else {
                    saldoSebelumnyaElement.find('.nilai-detail').text(formatRupiah(saldoAkhirSebelumnya));
                }
            }
        }
        
        const confirmedCashdrawer = $('#confirmedCashdrawer');
        const confirmedSaldoAwal = $('#confirmedSaldoAwal');
        
        if (confirmedCashdrawer.length) confirmedCashdrawer.val(dataForm.cashdrawer);
        if (confirmedSaldoAwal.length) confirmedSaldoAwal.val(dataForm.saldoAwal);
        
        confirmationModal.addClass('aktif');
        $('body').css('overflow', 'hidden');
        
        setTimeout(() => {
            if (cancelBtn.length) cancelBtn.trigger('focus');
        }, 100);
    }

    function sembunyikanModalKonfirmasi() {
        if (!confirmationModal.length) return;
        
        confirmationModal.removeClass('aktif');
        $('body').css('overflow', '');
        
        if (submitShiftBtn.length) {
            setTimeout(() => submitShiftBtn.trigger('focus'), 100);
        }
    }

    function inisialisasiInputSaldo() {
        if (!saldoInput.length) return;

        // Dapatkan saldo akhir sebelumnya saat inisialisasi
        saldoAkhirSebelumnya = getSaldoAkhirSebelumnya();
        console.log('Saldo akhir sebelumnya:', saldoAkhirSebelumnya);

        // Set placeholder dan nilai default jika ada saldo akhir sebelumnya
        const isShiftPertama = $('.info').text().includes('SHIFT PERTAMA');
        if (!isShiftPertama && saldoAkhirSebelumnya > 0) {
            saldoInput.attr('placeholder', `Min. ${formatRupiah(saldoAkhirSebelumnya)}`);
            
            // Set nilai default jika input kosong
            if (!saldoInput.val() || saldoInput.val().trim() === '') {
                saldoInput.val(saldoAkhirSebelumnya.toLocaleString('id-ID'));
            }
        }

        saldoInput.on("input", function (e) {
            let value = $(this).val().replace(/[^\d]/g, "");
            if (value) {
                value = parseInt(value).toLocaleString('id-ID');
                $(this).val(value);
            } else {
                $(this).val('');
            }

            // Validasi real-time
            const validasi = validasiInputSaldo($(this).val());
            if (!validasi.isValid && $(this).val().trim() !== '') {
                $(this).css('border-color', '#dc3545');
            } else {
                $(this).css('border-color', '');
                hapusAlertYangAda();
            }
        });
        
        saldoInput.on("blur", function(e) {
            const validasi = validasiInputSaldo($(this).val());
            if (!validasi.isValid && $(this).val().trim() !== '') {
                tampilkanError(validasi.message);
                $(this).css('border-color', '#dc3545');
                $(this).trigger('focus');
            } else {
                $(this).css('border-color', '');
            }
        });

        saldoInput.on("keypress", function(e) {
            const char = String.fromCharCode(e.keyCode || e.which);
            if (!/[\d]/.test(char)) {
                e.preventDefault();
            }
        });

        saldoInput.on("paste", function(e) {
            e.preventDefault();
            const teksTempel = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
            const nilaiNumerik = teksTempel.replace(/[^\d]/g, '');
            if (nilaiNumerik) {
                const nilaiTerformat = parseInt(nilaiNumerik).toLocaleString('id-ID');
                $(this).val(nilaiTerformat);
                
                $(this).trigger('input');
            }
        });

        saldoInput.on("keyup", function(e) {
            if ($(this).val().trim() === '') return;
            
            const validasi = validasiInputSaldo($(this).val());
            if (!validasi.isValid) {
                $(this).css('border-color', '#dc3545');
            } else {
                $(this).css('border-color', '');
            }
        });
    }

    function inisialisasiPengirimanForm() {
        if (!shiftForm.length) return;

        shiftForm.on("submit", function (e) {
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
        if (!refreshBtn.length || !cashdrawerSelect.length) return;

        refreshBtn.on("click", function () {
            if ($(this).prop('disabled')) return;
            
            counter += 1;
            if (counter > 3) counter = 1; 

            const opsiPlaceholder = cashdrawerSelect.find('option[value=""]');
            cashdrawerSelect.empty();
            if (opsiPlaceholder.length) {
                cashdrawerSelect.append(opsiPlaceholder);
            }

            const labelOpsi = `Kasir ${String(counter).padStart(2, "0")} - Cashdrawer ${counter}`;
            const opsi = $('<option>')
                .val(labelOpsi)
                .text(labelOpsi)
                .prop('selected', true);

            cashdrawerSelect.append(opsi);

            const htmlAsli = $(this).html();
            $(this).html("✓");
            $(this).prop('disabled', true);
            
            setTimeout(() => {
                $(this).html(htmlAsli);
                $(this).prop('disabled', false);
            }, 1000);
        });

        const opsiTerpilih = cashdrawerSelect.find('option:selected');
        if (opsiTerpilih.length && opsiTerpilih.val()) {
            const match = opsiTerpilih.val().match(/Kasir\s+(\d+)/);
            if (match) {
                counter = parseInt(match[1]);
            }
        }
    }

    function inisialisasiPenanganModal() {
        if (!cancelBtn.length || !confirmBtn.length) return;

        cancelBtn.on('click', function() {
            sembunyikanModalKonfirmasi();
        });

        confirmBtn.on('click', function() {
            if (isSubmitting) return;
            
            isSubmitting = true;
            
            const teksAsli = confirmBtn.text();
            confirmBtn.text("Memulai...");
            confirmBtn.prop('disabled', true);

            if (confirmedForm.length) {
                confirmedForm.trigger('submit');
            } else {
                shiftForm.trigger('submit');
            }
        });

        confirmationModal.on('click', function(e) {
            if (e.target === confirmationModal[0]) {
                sembunyikanModalKonfirmasi();
            }
        });

        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && confirmationModal.hasClass('aktif')) {
                sembunyikanModalKonfirmasi();
            }
        });

        confirmationModal.on('keydown', function(e) {
            if (e.key === 'Enter' && confirmationModal.hasClass('aktif')) {
                e.preventDefault();
                confirmBtn.trigger('click');
            }
        });
    }

    function inisialisasiTab() {
        const tabs = $(".tabs button");
        const panels = $(".tab-panel");

        panels.each(function() {
            if (!$(this).hasClass('is-active')) {
                $(this).hide();
            }
        });

        tabs.each(function() {
            $(this).on("click", function (e) {
                e.preventDefault();
                
                const target = $(this).data('tab');
                if (!target) return;

                tabs.each(function() {
                    $(this).removeClass("active")
                        .attr("aria-selected", "false")
                        .attr("tabindex", "-1");
                });

                $(this).addClass("active")
                    .attr("aria-selected", "true")
                    .attr("tabindex", "0");

                panels.each(function() {
                    const isActive = $(this).data('tab-panel') === target;
                    $(this).toggleClass("is-active", isActive);
                    
                    if (isActive) {
                        $(this).show();
                        $(this).trigger('tabActivated', { tabName: target });
                    } else {
                        $(this).hide();
                    }
                });

                const url = new URL(window.location);
                url.searchParams.set('tab', target);
                window.history.replaceState({}, '', url);

                setTimeout(() => {
                    const panelAktif = $('.tab-panel.is-active');
                    if (panelAktif.length) {
                        const fokusPertama = panelAktif.find(
                            'button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
                        ).first();
                        if (fokusPertama.length) {
                            fokusPertama.trigger('focus');
                        }
                    }
                }, 100);
            });
        });

        const parameterUrl = new URLSearchParams(window.location.search);
        const tabAktif = parameterUrl.get('tab') || 'current';
        
        const tabAwal = $(`.tabs button[data-tab="${tabAktif}"]`);
        if (tabAwal.length) {
            tabAwal.trigger('click');
        } else if (tabs.length > 0) {
            tabs.first().trigger('click');
        }
    }

    function inisialisasiFormSetoran() {
        const jenisSetoran = $('#jenis_setoran');
        const metodeSetoran = $('#metode_setoran');
        const grupDetailLainnya = $('#detail_lainnya_group');
        const grupBuktiTransfer = $('#bukti_transfer_group');
        const jumlahSetoran = $('#jumlah_setoran');
        const formSetoran = $('.setoran-form');
        const fileInputLabel = $('#fileInputLabel');
        const fileName = $('#fileName');

        if (jenisSetoran.length && grupDetailLainnya.length) {
            jenisSetoran.on('change', function() {
                if ($(this).val() === 'lainnya') {
                    grupDetailLainnya.show();
                    setTimeout(() => {
                        const inputDetail = $('#detail_lainnya');
                        if (inputDetail.length) inputDetail.trigger('focus');
                    }, 100);
                } else {
                    grupDetailLainnya.hide();
                    $('#detail_lainnya').val('');
                }
            });

            if (jenisSetoran.val() === 'lainnya') {
                grupDetailLainnya.show();
            }
        }

        if (metodeSetoran.length && grupBuktiTransfer.length) {
            metodeSetoran.on('change', function() {
                if ($(this).val() === 'transfer') {
                    grupBuktiTransfer.show();
                    setTimeout(() => {
                        const inputBukti = $('#bukti_transfer');
                        if (inputBukti.length) inputBukti.trigger('focus');
                    }, 100);
                } else {
                    grupBuktiTransfer.hide();
                    const buktiTransfer = $('#bukti_transfer');
                    if (buktiTransfer.length) {
                        buktiTransfer.val('');
                        const existingPreview = $('#bukti_preview');
                        if (existingPreview.length) {
                            existingPreview.remove();
                        }
                        if (fileInputLabel.length) {
                            fileInputLabel.removeClass('has-file');
                            fileInputLabel.html('📎 Klik untuk upload bukti transfer<div class="file-name" id="fileName"></div>');
                        }
                    }
                }
            });

            if (metodeSetoran.val() === 'transfer') {
                grupBuktiTransfer.show();
            }
        }

        const buktiTransferInput = $('#bukti_transfer');
        if (buktiTransferInput.length && fileInputLabel.length) {
            buktiTransferInput.on('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    fileInputLabel.addClass('has-file');
                    if (fileName.length) {
                        fileName.text(`File: ${file.name} (${formatFileSize(file.size)})`);
                    }

                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                    const maxSize = 5 * 1024 * 1024;
                    
                    if (!allowedTypes.includes(file.type)) {
                        tampilkanError('Format file tidak didukung. Gunakan JPG, PNG, PDF, atau DOC.');
                        $(this).val('');
                        fileInputLabel.removeClass('has-file');
                        if (fileName.length) fileName.text('');
                        return;
                    }
                    
                    if (file.size > maxSize) {
                        tampilkanError('Ukuran file terlalu besar. Maksimal 5MB.');
                        $(this).val('');
                        fileInputLabel.removeClass('has-file');
                        if (fileName.length) fileName.text('');
                        return;
                    }
                    
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const existingPreview = $('#bukti_preview');
                            if (existingPreview.length) {
                                existingPreview.remove();
                            }
                            
                            const preview = $('<div>')
                                .attr('id', 'bukti_preview')
                                .addClass('bukti-preview')
                                .html(`
                                    <img src="${e.target.result}" alt="Preview Bukti Transfer">
                                    <div class="preview-label">Preview Bukti Transfer</div>
                                `);
                            
                            buktiTransferInput.parent().append(preview);
                        };
                        reader.readAsDataURL(file);
                    } else {
                        const existingPreview = $('#bukti_preview');
                        if (existingPreview.length) {
                            existingPreview.remove();
                        }
                    }
                } else {
                    fileInputLabel.removeClass('has-file');
                    if (fileName.length) fileName.text('');
                    
                    const existingPreview = $('#bukti_preview');
                    if (existingPreview.length) {
                        existingPreview.remove();
                    }
                }
            });
        }

        updateSaldoTersedia();
        if (jumlahSetoran.length) {
            jumlahSetoran.on('blur', function() {
                const value = $(this).val().replace(/[^\d]/g, '');
                if (value && parseInt(value) === 0) {
                    tampilkanError('Jumlah setoran harus lebih besar dari 0.');
                    $(this).val('');
                    $(this).trigger('focus');
                }
            });

            jumlahSetoran.on('keypress', function(e) {
                const char = String.fromCharCode(e.keyCode || e.which);
                if (!/[\d]/.test(char)) {
                    e.preventDefault();
                }
            });
        }

        if (formSetoran.length) {
            formSetoran.on('submit', function(e) {
                if (isSubmitting) {
                    e.preventDefault();
                    tampilkanError('Sedang memproses setoran sebelumnya...');
                    return;
                }

                const tombolSubmit = $(this).find('.setoran-submit');
                const jumlah = $('#jumlah_setoran');
                const jenis = $('#jenis_setoran');
                const metode = $('#metode_setoran');
                const keterangan = $('#keterangan_setoran');
                const buktiTransfer = $('#bukti_transfer');

                if (!jumlah.length || !jenis.length || !metode.length || !keterangan.length) {
                    e.preventDefault();
                    tampilkanError('Form tidak lengkap. Silakan refresh halaman.');
                    return;
                }

                const nilaiJumlah = jumlah.val();
                const nilaiJenis = jenis.val();
                const nilaiMetode = metode.val();
                const nilaiKeterangan = keterangan.val().trim();

                if (!nilaiJumlah || !nilaiJenis || !nilaiMetode || !nilaiKeterangan) {
                    e.preventDefault();
                    tampilkanError('Semua field bertanda * harus diisi.');
                    return;
                }

                if (nilaiMetode === 'transfer') {
                    if (!buktiTransfer.length || !buktiTransfer[0].files || !buktiTransfer[0].files[0]) {
                        e.preventDefault();
                        tampilkanError('Bukti transfer wajib diupload untuk metode transfer.');
                        if (buktiTransfer.length) buktiTransfer.trigger('focus');
                        return;
                    }
                }

                const jumlahNumerik = parseRupiah(nilaiJumlah);
                if (jumlahNumerik <= 0) {
                    e.preventDefault();
                    tampilkanError('Jumlah setoran harus lebih besar dari 0.');
                    jumlah.trigger('focus');
                    return;
                }

                const saldoTersedia = getSaldoTersedia();
                if (jumlahNumerik > saldoTersedia) {
                    e.preventDefault();
                    tampilkanError(`Jumlah setoran melebihi saldo tersedia. Saldo tersedia: ${formatRupiah(saldoTersedia)}`);
                    jumlah.trigger('focus');
                    return;
                }

                const sisaSaldo = saldoTersedia - jumlahNumerik;
                if (sisaSaldo < 100000) {
                    e.preventDefault();
                    tampilkanError(`Setoran tidak dapat dilakukan. Minimal sisa saldo harus Rp 100.000. Sisa akan menjadi: ${formatRupiah(sisaSaldo)}`);
                    jumlah.trigger('focus');
                    return;
                }

                isSubmitting = true;
                if (tombolSubmit.length && !tombolSubmit.prop('disabled')) {
                    tombolSubmit.prop('disabled', true);
                    const htmlAsli = tombolSubmit.html();
                    tombolSubmit.html('<span>⏳</span> Memproses Setoran...');
                    
                    setTimeout(() => {
                        if (tombolSubmit.prop('disabled')) {
                            tombolSubmit.html(htmlAsli);
                            tombolSubmit.prop('disabled', false);
                            isSubmitting = false;
                        }
                    }, 10000);
                }
            });
        }
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    function inisialisasiNavigasiKeyboard() {
        $(document).on('keydown', function(e) {
            if (e.ctrlKey && e.key >= '1' && e.key <= '3') {
                e.preventDefault();
                const indexTab = parseInt(e.key) - 1;
                const tabs = $('.tabs button');
                if (tabs.eq(indexTab).length) {
                    tabs.eq(indexTab).trigger('click');
                }
            }
        });
    }

    function inisialisasiSimpanOtomatis() {
        const elemenForm = $('input, select, textarea');
        const kunciSimpanOtomatis = 'shift_form_draft';
        
        const drafTersimpan = localStorage.getItem(kunciSimpanOtomatis);
        if (drafTersimpan) {
            try {
                const draf = JSON.parse(drafTersimpan);
                Object.keys(draf).forEach(key => {
                    const element = $(`[name="${key}"]`);
                    if (element.length && element.attr('type') !== 'password' && element.attr('type') !== 'file') {
                        element.val(draf[key]);
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
        
        elemenForm.each(function() {
            if ($(this).attr('name') && $(this).attr('type') !== 'password' && $(this).attr('type') !== 'file') {
                $(this).on('input', debounce(function() {
                    const dataForm = {};
                    elemenForm.each(function() {
                        if ($(this).attr('name') && $(this).attr('type') !== 'password' && $(this).attr('type') !== 'file') {
                            dataForm[$(this).attr('name')] = $(this).val();
                        }
                    });
                    localStorage.setItem(kunciSimpanOtomatis, JSON.stringify(dataForm));
                }, 500));
            }
        });
        
        $(document).on('submit', function() {
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

    function inisialisasiRiwayatShift() {
        $(document).on('click', '.compact-card', function() {
            const shiftId = $(this).data('shift-id');
            showShiftDetail(shiftId);
        });

        $('.date-search-form').on('submit', function(e) {
            const dateInput = $(this).find('input[type="date"]');
            if (!dateInput.val()) {
                e.preventDefault();
                dateInput.focus();
            }
        });
    }

    function showShiftDetail(shiftId) {
        console.log('Menampilkan detail shift:', shiftId);
        
        const shiftData = window.shiftHistoryData.find(shift => shift.id === shiftId);
        const rekapData = window.rekapData[shiftId];
        
        if (shiftData) {
            const modalContent = `
                <div class="shift-detail">
                    <h4>Detail Shift: ${shiftData.cashdrawer}</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Saldo Awal:</label>
                            <span>${formatRupiah(rekapData?.saldo_awal || shiftData.saldo_awal)}</span>
                        </div>
                        <div class="detail-item">
                            <label>Saldo Akhir:</label>
                            <span>${formatRupiah(rekapData?.saldo_akhir || shiftData.saldo_akhir)}</span>
                        </div>
                        <div class="detail-item">
                            <label>Selisih:</label>
                            <span class="${(rekapData?.selisih || (shiftData.saldo_akhir - shiftData.saldo_awal)) >= 0 ? 'positif' : 'negatif'}">
                                ${formatRupiah(Math.abs(rekapData?.selisih || (shiftData.saldo_akhir - shiftData.saldo_awal)))}
                            </span>
                        </div>
                        <div class="detail-item">
                            <label>Waktu Mulai:</label>
                            <span>${new Date(shiftData.waktu_mulai).toLocaleString('id-ID')}</span>
                        </div>
                        ${rekapData?.waktu_selesai ? `
                        <div class="detail-item">
                            <label>Waktu Selesai:</label>
                            <span>${new Date(rekapData.waktu_selesai).toLocaleString('id-ID')}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
            `;
            
            $('#shiftDetailContent').html(modalContent);
            $('#shiftDetailModal').addClass('aktif');
        }
    }

    function inisialisasiAplikasi() {
        console.log('Menginisialisasi aplikasi shift UAM...');
        
        try {
            saldoAkhirSebelumnya = getSaldoAkhirSebelumnya();
            
            inisialisasiTab();
            inisialisasiInputSaldo();
            inisialisasiPengirimanForm();
            inisialisasiRefreshCashdrawer();
            inisialisasiPenanganModal();
            inisialisasiFormSetoran();
            inisialisasiRiwayatShift();
            inisialisasiNavigasiKeyboard();
            inisialisasiSimpanOtomatis();
            
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.href);
            }
            
            console.log('Aplikasi shift UAM berhasil diinisialisasi');
            console.log('Saldo akhir sebelumnya:', saldoAkhirSebelumnya);
        } catch (error) {
            console.error('Error menginisialisasi aplikasi:', error);
            tampilkanError('Terjadi error dalam memuat aplikasi. Silakan refresh halaman.');
        }
    }

    inisialisasiAplikasi();
    $(window).on('error', function(e) {
        console.error('Terjadi error global:', e.originalEvent.error);
    });
});