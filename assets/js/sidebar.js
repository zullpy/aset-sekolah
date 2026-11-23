/**
 * Sidebar Interactions - Sistem Informasi Aset SDN Budi Karya
 * Mengatur Toggle Accordion Submenu Grup (Data Aset, Monitoring, dll.)
 */

/**
 * Toggle Accordion Submenu Grup
 * @param {string} groupId - ID elemen <li> grup
 */
function toggleSidebarGroup(groupId) {
    const targetGroup = document.getElementById(groupId);
    if (!targetGroup) return;

    const isCurrentlyOpen = targetGroup.classList.contains('is-open');

    // Accordion: tutup submenu lain agar tidak memicu scroll berlebih
    document.querySelectorAll('.sidebar-group').forEach(function(group) {
        if (group !== targetGroup) {
            group.classList.remove('is-open');
            const btn = group.querySelector('.sidebar-btn');
            if (btn) {
                btn.setAttribute('aria-expanded', 'false');
            }
        }
    });

    if (isCurrentlyOpen) {
        targetGroup.classList.remove('is-open');
        const button = targetGroup.querySelector('.sidebar-btn');
        if (button) {
            button.setAttribute('aria-expanded', 'false');
        }
    } else {
        targetGroup.classList.add('is-open');
        const button = targetGroup.querySelector('.sidebar-btn');
        if (button) {
            button.setAttribute('aria-expanded', 'true');
        }
    }
}
