/**
 * Reset Password Interaction Script
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

document.addEventListener('DOMContentLoaded', () => {
    // Inisialisasi tombol toggle show/hide password
    const toggleButtons = document.querySelectorAll('.reset-toggle-password');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;

            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                btn.setAttribute('aria-label', 'Sembunyikan kata sandi');
                if (icon) {
                    icon.classList.remove('ph-eye');
                    icon.classList.add('ph-eye-slash');
                }
            } else {
                input.type = 'password';
                btn.setAttribute('aria-label', 'Tampilkan kata sandi');
                if (icon) {
                    icon.classList.remove('ph-eye-slash');
                    icon.classList.add('ph-eye');
                }
            }
        });
    });

    // Validasi live kata sandi
    const passwordInput = document.getElementById('new_password');
    const confirmInput = document.getElementById('confirm_password');
    const hintLength = document.getElementById('hint-length');
    const hintMatch = document.getElementById('hint-match');

    function validatePasswords() {
        if (!passwordInput || !confirmInput) return;

        const val = passwordInput.value;
        const confirmVal = confirmInput.value;

        // Cek panjang minimal 6 karakter
        if (hintLength) {
            const isLengthValid = val.length >= 6;
            hintLength.classList.toggle('valid', isLengthValid);
            const icon = hintLength.querySelector('i');
            if (icon) {
                icon.className = isLengthValid ? 'ph-bold ph-check' : 'ph-bold ph-circle';
            }
        }

        // Cek kesesuaian konfirmasi kata sandi
        if (hintMatch) {
            const isMatch = val.length > 0 && confirmVal.length > 0 && val === confirmVal;
            hintMatch.classList.toggle('valid', isMatch);
            const icon = hintMatch.querySelector('i');
            if (icon) {
                icon.className = isMatch ? 'ph-bold ph-check' : 'ph-bold ph-circle';
            }
        }
    }

    if (passwordInput && confirmInput) {
        passwordInput.addEventListener('input', validatePasswords);
        confirmInput.addEventListener('input', validatePasswords);
    }

    // Intersep submit form untuk validasi interaktif dengan SweetAlert2
    const formReset = document.getElementById('form-reset-password');
    if (formReset) {
        formReset.addEventListener('submit', function(e) {
            const val = passwordInput ? passwordInput.value : '';
            const confirmVal = confirmInput ? confirmInput.value : '';

            if (val.length < 6) {
                e.preventDefault();
                if (window.SDNAlert) {
                    SDNAlert.warning('Kata Sandi Terlalu Pendek', 'Kata sandi minimal harus terdiri dari 6 karakter.');
                } else {
                    alert('Kata sandi minimal harus terdiri dari 6 karakter.');
                }
                if (passwordInput) passwordInput.focus();
                return;
            }

            if (val !== confirmVal) {
                e.preventDefault();
                if (window.SDNAlert) {
                    SDNAlert.warning('Kata Sandi Tidak Cocok', 'Konfirmasi kata sandi tidak cocok dengan kata sandi baru.');
                } else {
                    alert('Konfirmasi kata sandi tidak cocok dengan kata sandi baru.');
                }
                if (confirmInput) confirmInput.focus();
                return;
            }

            // Kirim secara AJAX tanpa reload halaman
            if (window.SDNAjax) {
                e.preventDefault();
                SDNAjax.submitForm(this, {
                    loadingText: 'Menyimpan...',
                    successTitle: 'Kata Sandi Diperbarui!',
                    onSuccess: (res) => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Kata Sandi Diperbarui!',
                            text: res.message || 'Kata sandi akun Anda telah berhasil diubah.',
                            confirmButtonColor: '#852019',
                            confirmButtonText: 'Masuk ke Sistem'
                        }).then(() => {
                            window.location.href = res.data && res.data.redirect ? res.data.redirect : 'login.php';
                        });
                    }
                });
            }
        });
    }
});
