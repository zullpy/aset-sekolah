/**
 * Header Interactions - Sistem Informasi Aset SDN Budi Karya
 * Mengatur User Profile Dropdown dan Toggle Sidebar Mobile
 */

/**
 * Toggle Dropdown Menu Pengguna
 * @param {Event} event
 */
function toggleUserDropdown(event) {
    if (event) {
        event.stopPropagation();
    }
    const btn = document.getElementById('userMenuBtn');
    const menu = document.getElementById('userDropdownMenu');
    if (!menu || !btn) return;

    const isShown = menu.classList.contains('show');
    if (isShown) {
        menu.classList.remove('show');
        btn.classList.remove('active');
        btn.setAttribute('aria-expanded', 'false');
    } else {
        menu.classList.add('show');
        btn.classList.add('active');
        btn.setAttribute('aria-expanded', 'true');
    }
}

/**
 * Tutup dropdown saat klik di luar elemen menu
 */
document.addEventListener('click', function(e) {
    const menu = document.getElementById('userDropdownMenu');
    const btn = document.getElementById('userMenuBtn');
    if (menu && menu.classList.contains('show')) {
        if (!menu.contains(e.target) && !btn.contains(e.target)) {
            menu.classList.remove('show');
            if (btn) {
                btn.classList.remove('active');
                btn.setAttribute('aria-expanded', 'false');
            }
        }
    }
});

/**
 * Toggle Sidebar Mobile & Backdrop
 */
function toggleMobileSidebar() {
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar) return;

    const isOpen = sidebar.classList.contains('show') || sidebar.classList.contains('is-open-mobile');
    if (isOpen) {
        closeMobileSidebar();
    } else {
        sidebar.classList.add('show', 'is-open-mobile');
        if (backdrop) backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Tutup Sidebar Mobile & Sembunyikan Backdrop
 */
function closeMobileSidebar() {
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar) {
        sidebar.classList.remove('show', 'is-open-mobile');
    }
    if (backdrop) {
        backdrop.classList.remove('show');
    }
    document.body.style.overflow = '';
}
