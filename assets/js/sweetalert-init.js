/**
 * SweetAlert2 Global Helper & Handlers
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

const SDNAlert = {
    error: function(title, message) {
        return Swal.fire({
            icon: 'error',
            title: title || 'Terjadi Kesalahan',
            html: message,
            confirmButtonColor: '#852019',
            confirmButtonText: 'Tutup'
        });
    },

    success: function(title, message, callback) {
        return Swal.fire({
            icon: 'success',
            title: title || 'Berhasil',
            html: message,
            confirmButtonColor: '#852019',
            confirmButtonText: 'Mengerti'
        }).then((result) => {
            if (callback && typeof callback === 'function') {
                callback(result);
            }
        });
    },

    warning: function(title, message) {
        return Swal.fire({
            icon: 'warning',
            title: title || 'Peringatan',
            html: message,
            confirmButtonColor: '#852019',
            confirmButtonText: 'Mengerti'
        });
    },

    confirmLogout: function(event, url) {
        if (event) {
            event.preventDefault();
        }

        Swal.fire({
            title: 'Konfirmasi Keluar',
            text: 'Apakah Anda yakin ingin keluar dari sistem inventaris?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#852019',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Keluar',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
};

// Global helper untuk dipanggil langsung lewat inline onclick
function confirmLogout(event, url) {
    SDNAlert.confirmLogout(event, url);
}

document.addEventListener('DOMContentLoaded', () => {
    // Intersep link dengan atribut data-confirm-logout
    document.querySelectorAll('[data-confirm-logout]').forEach(el => {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            SDNAlert.confirmLogout(e, href);
        });
    });
});
