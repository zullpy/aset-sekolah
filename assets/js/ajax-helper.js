/**
 * AJAX Helper & CRUD Request Handler
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

const SDNAjax = {
    /**
     * Mengambil token CSRF dari meta tag atau hidden input
     */
    getCsrfToken: function() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        const input = document.querySelector('input[name="csrf_token"]');
        if (input && input.value) return input.value;
        return '';
    },

    /**
     * Mengirim form secara asynchronous (AJAX) tanpa reload halaman
     *
     * @param {HTMLFormElement} formElement
     * @param {Object} options
     */
    submitForm: async function(formElement, options = {}) {
        const form = formElement instanceof HTMLFormElement ? formElement : document.querySelector(formElement);
        if (!form) return;

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';
        const originalBtnDisabled = submitBtn ? submitBtn.disabled : false;

        // Ambil data form
        const formData = new FormData(form);
        formData.append('ajax', '1');

        const csrfToken = this.getCsrfToken();
        if (csrfToken && !formData.has('csrf_token')) {
            formData.append('csrf_token', csrfToken);
        }

        const url = options.url || form.getAttribute('action') || window.location.href;
        const method = (options.method || form.getAttribute('method') || 'POST').toUpperCase();

        try {
            // Tampilkan status memuat (loading) pada tombol submit
            if (submitBtn) {
                submitBtn.disabled = true;
                const loadingText = options.loadingText || 'Memproses...';
                submitBtn.innerHTML = `<i class="ph-bold ph-spinner" style="animation: spin 1s linear infinite; display: inline-block;"></i> <span>${loadingText}</span>`;
            }

            const headers = {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            };
            if (csrfToken) {
                headers['X-CSRF-TOKEN'] = csrfToken;
            }

            const response = await fetch(url, {
                method: method,
                body: formData,
                headers: headers
            });

            const result = await response.json().catch(() => null);

            // Kembalikan tombol ke keadaan semula
            if (submitBtn) {
                submitBtn.disabled = originalBtnDisabled;
                submitBtn.innerHTML = originalBtnHtml;
            }

            if (!result) {
                throw new Error('Respon server tidak valid atau gagal diproses.');
            }

            if (result.success) {
                // Aksi jika berhasil
                if (typeof options.onSuccess === 'function') {
                    options.onSuccess(result);
                } else if (result.data && result.data.redirect) {
                    // Redirect jika server menginstruksikan
                    if (options.showSuccessAlert !== false) {
                        Swal.fire({
                            icon: 'success',
                            title: options.successTitle || 'Berhasil!',
                            text: result.message || 'Data berhasil diproses.',
                            confirmButtonColor: '#852019',
                            confirmButtonText: 'Lanjutkan',
                            timer: 1500,
                            timerProgressBar: true
                        }).then(() => {
                            window.location.href = result.data.redirect;
                        });
                    } else {
                        window.location.href = result.data.redirect;
                    }
                } else if (options.showSuccessAlert !== false) {
                    Swal.fire({
                        icon: 'success',
                        title: options.successTitle || 'Berhasil!',
                        html: result.message || 'Operasi berhasil dilakukan.',
                        confirmButtonColor: '#852019',
                        confirmButtonText: 'Selesai'
                    });
                }
            } else {
                // Aksi jika gagal dari validasi server
                if (typeof options.onError === 'function') {
                    options.onError(result);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: options.errorTitle || 'Gagal Diproses',
                        html: result.message || 'Terjadi kesalahan saat memproses data.',
                        confirmButtonColor: '#852019',
                        confirmButtonText: 'Tutup'
                    });
                }
            }

            return result;
        } catch (error) {
            if (submitBtn) {
                submitBtn.disabled = originalBtnDisabled;
                submitBtn.innerHTML = originalBtnHtml;
            }

            console.error('AJAX Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Koneksi Terputus',
                text: error.message || 'Tidak dapat terhubung ke server. Silakan periksa jaringan Anda.',
                confirmButtonColor: '#852019',
                confirmButtonText: 'Tutup'
            });
        }
    },

    /**
     * Konfirmasi dan hapus data via AJAX tanpa reload halaman
     *
     * @param {string} deleteUrl 
     * @param {Object} options 
     */
    confirmDelete: function(deleteUrl, options = {}) {
        const title = options.title || 'Konfirmasi Hapus';
        const text = options.text || 'Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.';
        const targetRow = options.targetRow ? (typeof options.targetRow === 'string' ? document.querySelector(options.targetRow) : options.targetRow) : null;

        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#852019',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    Swal.fire({
                        title: 'Menghapus...',
                        text: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    const deleteHeaders = {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    };
                    const csrfToken = SDNAjax.getCsrfToken();
                    if (csrfToken) {
                        deleteHeaders['X-CSRF-TOKEN'] = csrfToken;
                    }

                    const deleteBody = new FormData();
                    deleteBody.append('ajax', '1');
                    if (csrfToken) {
                        deleteBody.append('csrf_token', csrfToken);
                    }

                    const response = await fetch(deleteUrl, {
                        method: 'POST',
                        body: deleteBody,
                        headers: deleteHeaders
                    });

                    const res = await response.json();

                    if (res.success) {
                        // Hapus baris tabel jika ada target
                        if (targetRow) {
                            targetRow.style.transition = 'all 0.35s ease';
                            targetRow.style.opacity = '0';
                            targetRow.style.transform = 'translateX(20px)';
                            setTimeout(() => targetRow.remove(), 350);
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus!',
                            text: res.message || 'Data telah berhasil dihapus.',
                            confirmButtonColor: '#852019',
                            timer: 1500,
                            timerProgressBar: true
                        });

                        if (typeof options.onSuccess === 'function') {
                            options.onSuccess(res);
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menghapus',
                            text: res.message || 'Data tidak dapat dihapus.',
                            confirmButtonColor: '#852019'
                        });
                    }
                } catch (e) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Kesalahan Sistem',
                        text: 'Terjadi kesalahan jaringan saat menghapus data.',
                        confirmButtonColor: '#852019'
                    });
                }
            }
        });
    }
};

// CSS animasi rotasi spinner
if (!document.getElementById('sdn-ajax-styles')) {
    const styleEl = document.createElement('style');
    styleEl.id = 'sdn-ajax-styles';
    styleEl.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(styleEl);
}
