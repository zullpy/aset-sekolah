/**
 * Login Page Interactions - Sistem Informasi Inventaris Aset SDN Budi Karya
 * Mengatur Toggle Sembunyikan / Tampilkan Password & Form Submit AJAX Tanpa Reload
 */

/**
 * Toggle Sembunyikan / Tampilkan Password
 */
function togglePasswordVisibility() {
    const pwdInput = document.getElementById('password');
    const icon = document.getElementById('togglePasswordIcon');
    if (!pwdInput || !icon) return;

    if (pwdInput.type === 'password') {
        pwdInput.type = 'text';
        icon.classList.remove('ph-eye');
        icon.classList.add('ph-eye-slash');
    } else {
        pwdInput.type = 'password';
        icon.classList.remove('ph-eye-slash');
        icon.classList.add('ph-eye');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.querySelector('form[action*="login.php"]');
    if (loginForm && window.SDNAjax) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();

            SDNAjax.submitForm(this, {
                loadingText: 'Memverifikasi...',
                errorTitle: 'Gagal Masuk',
                onError: (res) => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Masuk',
                        text: res.message || 'Username atau kata sandi salah.',
                        confirmButtonColor: '#852019',
                        confirmButtonText: 'Coba Lagi'
                    }).then(() => {
                        const pwd = document.getElementById('password');
                        if (pwd) {
                            pwd.value = '';
                            pwd.focus();
                        }
                    });
                }
            });
        });
    }
});

