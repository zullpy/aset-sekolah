/**
 * Kelola Akun - Sistem Informasi Inventaris Aset SDN Budi Karya
 * Features:
 * - Mock data pengguna dengan email & dummy password
 * - Modal Filter Data (Kata Kunci, Nama Pengguna, Peran, Status Akun) + badge dot aktif
 * - Modal Tambah Akun dengan validasi komprehensif & password eye toggle
 * - Kolom Password yang bisa disembunyikan / ditampilkan via toolbar switch
 * - Toggle intip/sembunyikan password per baris (default masked '••••••••')
 * - Tombol Edit di kolom aktivitas & Modal Edit Akun
 * - Live Search debounced, Pagination fleksibel, dan Toggle Status Akun
 */

// 1. Data Akun Pengguna (Dimuat dari Database MySQL via AJAX)
let AccountData = [
    {
        id: 1,
        nama: 'Didih Maulidah, S.Pd',
        username: 'operator.sekolah',
        email: 'didih.maulidah@sdnbudikarya.sch.id',
        password: 'Password123!',
        peran: 'Administrator',
        status: 'Aktif'
    },
    {
        id: 2,
        nama: 'Nunung Nuryanti, S.Pd',
        username: 'guru.kelas1',
        email: 'nunung.nuryanti@sdnbudikarya.sch.id',
        password: 'Password123!',
        peran: 'Operator',
        status: 'Aktif'
    },
    {
        id: 3,
        nama: 'Guru kelas 2',
        username: 'guru.kelas2',
        email: 'guru.kelas2@sdnbudikarya.sch.id',
        password: 'Password123!',
        peran: 'Operator',
        status: 'Aktif'
    },
    {
        id: 4,
        nama: 'Guru kelas 3',
        username: 'guru.kelas3',
        email: 'guru.kelas3@sdnbudikarya.sch.id',
        password: 'Password123!',
        peran: 'Operator',
        status: 'Aktif'
    },
    {
        id: 5,
        nama: 'Guru kelas 4',
        username: 'guru.kelas4',
        email: 'guru.kelas4@sdnbudikarya.sch.id',
        password: 'Password123!',
        peran: 'Operator',
        status: 'Aktif'
    },
    {
        id: 6,
        nama: 'Guru kelas 5',
        username: 'guru.kelas5',
        email: 'guru.kelas5@sdnbudikarya.sch.id',
        password: 'Password123!',
        peran: 'Operator',
        status: 'Aktif'
    },
    {
        id: 7,
        nama: 'Guru kelas 6',
        username: 'guru.kelas6',
        email: 'guru.kelas6@sdnbudikarya.sch.id',
        password: 'Password123!',
        peran: 'Operator',
        status: 'Aktif'
    },
    {
        id: 8,
        nama: 'Penjaga sekolah',
        username: 'penjaga.sekolah',
        email: 'penjaga.sekolah@sdnbudikarya.sch.id',
        password: 'Password123!',
        peran: 'Operator',
        status: 'Aktif'
    }
];

// 2. State Aplikasi
const state = {
    keyword: '',
    range: 'all', // '5' | '10' | '25' | '50' | 'all'
    currentPage: 1,
    showPasswordCol: false,
    visiblePasswords: {}, // { [accountId]: boolean }
    filters: {
        keyword: '',
        nama: '',
        peran: '',
        status: ''
    }
};

// Helper: Deteksi URL endpoint API AJAX
function getAccountApiUrl() {
    const path = window.location.pathname;
    if (path.indexOf('/pages/') !== -1) {
        return '../includes/ajax-akun.php';
    }
    return 'includes/ajax-akun.php';
}

// Helper: Ambil token CSRF dari meta header
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && meta.content) return meta.content;
    const input = document.querySelector('input[name="csrf_token"]');
    if (input && input.value) return input.value;
    return '';
}

// Memuat data akun real-time dari database MySQL
async function loadAccountData() {
    try {
        const response = await fetch(getAccountApiUrl() + '?action=list', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const res = await response.json();
        if (res && res.success && Array.isArray(res.data.users)) {
            AccountData = res.data.users;
            populateFilterNamaDropdown();
            render();
        }
    } catch (e) {
        console.warn('Gagal memuat akun dari server, menampilkan data awal:', e);
    }
}

// Helper: Escape HTML untuk mencegah XSS
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Helper: Highlight teks yang cocok dengan keyword pencarian
function highlightMatch(fullText, query) {
    const safeText = escapeHtml(fullText);
    if (!query || query.trim() === '') {
        return safeText;
    }

    const trimmedQuery = query.trim();
    const escapedQuery = trimmedQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const regex = new RegExp('(' + escapedQuery + ')', 'gi');

    return safeText.replace(regex, '<mark class="search-highlight">$1</mark>');
}

// Helper: Debounce function
function debounce(fn, delay) {
    let timer = null;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
}

// Helper: Validasi format email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// 3. Modal Management (Generic)
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');

    const openModals = document.querySelectorAll('.modal-overlay.show');
    if (openModals.length === 0) {
        document.body.style.overflow = '';
    }
}

function closeAllModals() {
    document.querySelectorAll('.modal-overlay.show').forEach(m => {
        m.classList.remove('show');
        m.setAttribute('aria-hidden', 'true');
    });
    document.body.style.overflow = '';
}

// Update Badge Filter Aktif
function updateFilterBadge() {
    const btnFilter = document.getElementById('btnFilter');
    if (!btnFilter) return;

    const hasFilter = Boolean(
        state.filters.keyword.trim() !== '' ||
        state.filters.nama !== '' ||
        state.filters.peran !== '' ||
        state.filters.status !== ''
    );

    if (hasFilter) {
        btnFilter.classList.add('has-filter');
    } else {
        btnFilter.classList.remove('has-filter');
    }
}

// 4. Fungsi Render Utama
function render() {
    const tbody = document.getElementById('accountTableBody');
    const thPassword = document.getElementById('thPassword');
    const countCurrentEl = document.getElementById('countCurrent');
    const countTotalEl = document.getElementById('countTotal');
    const clearBtn = document.getElementById('accountSearchClear');

    if (!tbody) return;

    // A. Update visibilitas kolom password header
    if (thPassword) {
        thPassword.style.display = state.showPasswordCol ? '' : 'none';
    }

    // B. Update visibilitas tombol clear "x"
    if (clearBtn) {
        if (state.keyword.trim().length > 0) {
            clearBtn.classList.add('show');
        } else {
            clearBtn.classList.remove('show');
        }
    }

    // C. Filter Data berdasarkan Search Bar DAN Kriteria Modal Filter
    const searchBarQuery = state.keyword.trim().toLowerCase();
    const filterKeyword = state.filters.keyword.trim().toLowerCase();
    const filterNama = state.filters.nama;
    const filterPeran = state.filters.peran;
    const filterStatus = state.filters.status;

    const filteredData = AccountData.filter(account => {
        // 1. Top Search Bar: mencocokkan kata pada kolom Nama
        if (searchBarQuery && !account.nama.toLowerCase().includes(searchBarQuery)) {
            return false;
        }

        // 2. Filter Modal - Kata Kunci: cari pada Nama, Username, atau Email
        if (filterKeyword) {
            const inNama = account.nama.toLowerCase().includes(filterKeyword);
            const inUsername = account.username.toLowerCase().includes(filterKeyword);
            const inEmail = (account.email || '').toLowerCase().includes(filterKeyword);
            if (!inNama && !inUsername && !inEmail) {
                return false;
            }
        }

        // 3. Filter Modal - Nama Pengguna
        if (filterNama && account.nama !== filterNama) {
            return false;
        }

        // 4. Filter Modal - Peran
        if (filterPeran && account.peran !== filterPeran) {
            return false;
        }

        // 5. Filter Modal - Status Akun
        if (filterStatus && account.status !== filterStatus) {
            return false;
        }

        return true;
    });

    const totalFiltered = filteredData.length;

    // D. Hitung Pagination
    let pageSize = totalFiltered;
    if (state.range !== 'all') {
        pageSize = parseInt(state.range, 10) || totalFiltered;
    }
    if (pageSize <= 0) pageSize = 1;

    const totalPages = Math.ceil(totalFiltered / pageSize) || 1;

    // Validasi currentPage
    if (state.currentPage > totalPages) {
        state.currentPage = totalPages;
    }
    if (state.currentPage < 1) {
        state.currentPage = 1;
    }

    // Slicing data untuk halaman aktif
    const startIndex = (state.currentPage - 1) * pageSize;
    const pageData = (state.range === 'all')
        ? filteredData
        : filteredData.slice(startIndex, startIndex + pageSize);

    // E. Update Info Meta Count ("Menampilkan X dari Y akun")
    if (countCurrentEl) countCurrentEl.textContent = pageData.length;
    if (countTotalEl) countTotalEl.textContent = totalFiltered;

    // F. Render Baris Tabel
    const colSpan = state.showPasswordCol ? 7 : 6;

    if (pageData.length === 0) {
        const queryDisplay = escapeHtml(state.keyword.trim());
        const emptyMessage = queryDisplay
            ? `Tidak ada akun dengan nama "${queryDisplay}"`
            : 'Tidak ada data akun yang sesuai dengan filter';

        tbody.innerHTML = `
            <tr>
                <td colspan="${colSpan}" class="account-empty-row">
                    <i class="ph-bold ph-magnifying-glass"></i>
                    ${emptyMessage}
                </td>
            </tr>
        `;
    } else {
        tbody.innerHTML = pageData.map((item, index) => {
            const rowNumber = startIndex + index + 1;
            const highlightedNama = highlightMatch(item.nama, state.keyword);
            const isAktif = (item.status === 'Aktif');

            const badgeClass = isAktif ? 'badge-status--aktif' : 'badge-status--nonaktif';
            const btnClass = isAktif ? 'btn-toggle-status--deactivate' : 'btn-toggle-status--activate';
            const btnText = isAktif ? 'Nonaktifkan' : 'Aktifkan';
            const btnIcon = isAktif ? 'ph-bold ph-power' : 'ph-bold ph-check';

            // Kolom Password (jika ditampilkan)
            let passwordCellHtml = '';
            if (state.showPasswordCol) {
                const isRevealed = Boolean(state.visiblePasswords[item.id]);
                const rawPw = item.password || 'Password123!';
                const displayPw = isRevealed ? escapeHtml(rawPw) : '••••••••';
                const pwTextClass = isRevealed ? 'password-text revealed' : 'password-text';
                const eyeIcon = isRevealed ? 'ph-bold ph-eye-slash' : 'ph-bold ph-eye';
                const eyeTitle = isRevealed ? 'Sembunyikan password' : 'Lihat password';

                passwordCellHtml = `
                    <td class="cell-password">
                        <div class="password-display-wrapper">
                            <span class="${pwTextClass}">${displayPw}</span>
                            <button
                                type="button"
                                class="btn-pw-eye"
                                title="${eyeTitle}"
                                aria-label="${eyeTitle}"
                                onclick="toggleRowPassword(${item.id})"
                            >
                                <i class="${eyeIcon}"></i>
                            </button>
                        </div>
                    </td>
                `;
            }

            return `
                <tr data-id="${item.id}">
                    <td class="cell-no">${rowNumber}</td>
                    <td class="cell-nama">${highlightedNama}</td>
                    <td class="cell-username">${escapeHtml(item.username)}</td>
                    ${passwordCellHtml}
                    <td class="cell-peran">${escapeHtml(item.peran)}</td>
                    <td class="cell-status">
                        <span class="badge-status ${badgeClass}">${escapeHtml(item.status)}</span>
                    </td>
                    <td class="cell-aktivitas">
                        <div class="account-actions-cell">
                            <button
                                type="button"
                                class="btn-act-detail"
                                title="Lihat Detail Akun"
                                aria-label="Lihat Detail"
                                onclick="showAccountDetail(${item.id})"
                            >
                                <i class="ph-bold ph-notebook"></i>
                            </button>
                            <button
                                type="button"
                                class="btn-act-edit"
                                title="Edit Akun"
                                aria-label="Edit Akun"
                                onclick="openEditAccountModal(${item.id})"
                            >
                                <i class="ph-bold ph-pencil-simple"></i>
                            </button>
                            <button
                                type="button"
                                class="btn-toggle-status ${btnClass}"
                                onclick="toggleAccountStatus(${item.id})"
                                title="${btnText} Akun"
                            >
                                <i class="${btnIcon}"></i>
                                <span>${btnText}</span>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // G. Render Pagination Controls
    renderPagination(totalPages);
}

// 5. Render Pagination Controls
function renderPagination(totalPages) {
    const container = document.getElementById('accountPagination');
    if (!container) return;

    if (totalPages <= 1) {
        container.innerHTML = `
            <button type="button" class="page-btn" disabled aria-label="Halaman Sebelumnya">Previous</button>
            <button type="button" class="page-btn active">1</button>
            <button type="button" class="page-btn" disabled aria-label="Halaman Berikutnya">Next</button>
        `;
        return;
    }

    const current = state.currentPage;
    const isPrevDisabled = (current <= 1);
    const isNextDisabled = (current >= totalPages);

    let html = '';

    // Tombol Previous
    html += `
        <button
            type="button"
            class="page-btn"
            ${isPrevDisabled ? 'disabled' : ''}
            onclick="goToPage(${current - 1})"
            aria-label="Halaman Sebelumnya"
        >
            Previous
        </button>
    `;

    // Deretan Nomor Halaman dengan Ellipsis
    const pages = getPaginationNumbers(current, totalPages);
    pages.forEach(p => {
        if (p === '...') {
            html += `<span class="page-ellipsis">...</span>`;
        } else {
            const isActive = (p === current);
            html += `
                <button
                    type="button"
                    class="page-btn ${isActive ? 'active' : ''}"
                    onclick="goToPage(${p})"
                >
                    ${p}
                </button>
            `;
        }
    });

    // Tombol Next
    html += `
        <button
            type="button"
            class="page-btn"
            ${isNextDisabled ? 'disabled' : ''}
            onclick="goToPage(${current + 1})"
            aria-label="Halaman Berikutnya"
        >
            Next
        </button>
    `;

    container.innerHTML = html;
}

// Helper Pagination Numbers
function getPaginationNumbers(current, total) {
    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }

    const pages = [];
    if (current <= 3) {
        pages.push(1, 2, 3, 4, '...', total);
    } else if (current >= total - 2) {
        pages.push(1, '...', total - 3, total - 2, total - 1, total);
    } else {
        pages.push(1, '...', current - 1, current, current + 1, '...', total);
    }

    return pages;
}

// Navigasi Halaman
function goToPage(page) {
    state.currentPage = page;
    render();
}

// 6. Toggle Password Row Level (On-Demand Secure Reveal)
async function toggleRowPassword(id) {
    if (state.visiblePasswords[id]) {
        delete state.visiblePasswords[id];
        render();
        return;
    }

    const account = AccountData.find(a => a.id === id);
    if (!account) return;

    if (account.revealedPassword) {
        state.visiblePasswords[id] = true;
        render();
        return;
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const fd = new FormData();
        fd.append('action', 'reveal_password');
        fd.append('id', id);

        const res = await fetch('../includes/ajax-akun.php', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            },
            body: fd
        });

        const data = await res.json();
        if (data && data.success && data.data && data.data.password) {
            account.revealedPassword = data.data.password;
            account.password = data.data.password;
            state.visiblePasswords[id] = true;
            render();
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak Dapat Membuka Password',
                    text: data?.message || 'Gagal memuat password terenkripsi.',
                    confirmButtonColor: '#852019'
                });
            }
        }
    } catch (err) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Kesalahan Sistem',
                text: 'Terjadi gangguan jaringan saat meminta password.',
                confirmButtonColor: '#852019'
            });
        }
    }
}
window.toggleRowPassword = toggleRowPassword;

// 7. Toggle Status Akun (Aktif <-> Nonaktif) dengan Dialog Konfirmasi
function toggleAccountStatus(id) {
    const account = AccountData.find(a => a.id === id);
    if (!account) return;

    const isDeactivating = (account.status === 'Aktif');

    const config = isDeactivating
        ? {
            title: 'Nonaktifkan Akun?',
            html: `Apakah Anda yakin ingin menonaktifkan akun <strong>${escapeHtml(account.nama)}</strong> (${escapeHtml(account.username)})? Akun ini tidak akan dapat masuk ke sistem sampai diaktifkan kembali.`,
            icon: 'warning',
            confirmButtonText: 'Ya, Nonaktifkan',
            confirmButtonColor: '#dc2626',
            cancelButtonText: 'Batal',
            cancelButtonColor: '#64748b'
        }
        : {
            title: 'Aktifkan Akun?',
            html: `Apakah Anda yakin ingin mengaktifkan kembali akun <strong>${escapeHtml(account.nama)}</strong> (${escapeHtml(account.username)})?`,
            icon: 'question',
            confirmButtonText: 'Ya, Aktifkan',
            confirmButtonColor: '#16a34a',
            cancelButtonText: 'Batal',
            cancelButtonColor: '#64748b'
        };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            ...config,
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true
        }).then(result => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'toggle_status');
                formData.append('id', id);
                formData.append('csrf_token', getCsrfToken());

                fetch(getAccountApiUrl(), {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(res => {
                    if (res && res.success) {
                        account.status = res.data.status || (isDeactivating ? 'Nonaktif' : 'Aktif');
                        render();

                        Swal.fire({
                            icon: 'success',
                            title: isDeactivating ? 'Akun Dinonaktifkan' : 'Akun Diaktifkan',
                            text: `Status akun ${account.nama} berhasil disimpan ke database menjadi ${account.status}.`,
                            timer: 1600,
                            showConfirmButton: false,
                            confirmButtonColor: '#852019'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Mengubah Status',
                            text: res.message || 'Terjadi kesalahan saat menyimpan status.',
                            confirmButtonColor: '#852019'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gangguan Jaringan',
                        text: 'Tidak dapat terhubung ke server database.',
                        confirmButtonColor: '#852019'
                    });
                });
            }
        });
    } else {
        const confirmed = window.confirm(
            isDeactivating
                ? `Apakah Anda yakin ingin menonaktifkan akun ${account.nama}?`
                : `Apakah Anda yakin ingin mengaktifkan akun ${account.nama}?`
        );
        if (confirmed) {
            const formData = new FormData();
            formData.append('action', 'toggle_status');
            formData.append('id', id);
            formData.append('csrf_token', getCsrfToken());

            fetch(getAccountApiUrl(), {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(res => {
                if (res && res.success) {
                    account.status = res.data.status || (isDeactivating ? 'Nonaktif' : 'Aktif');
                    render();
                }
            });
        }
    }
}

// 8. Tampilkan Detail Akun
function showAccountDetail(id) {
    const account = AccountData.find(a => a.id === id);
    if (!account) return;

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Detail Akun Pengguna',
            html: `
                <div style="text-align: left; font-size: 13.5px; line-height: 1.8; padding: 6px 12px;">
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 8px;">
                        <span style="color: #64748b; display: inline-block; width: 120px;">ID Pengguna</span>
                        <strong style="color: #1e293b;">#${account.id}</strong>
                    </div>
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 8px;">
                        <span style="color: #64748b; display: inline-block; width: 120px;">Nama Lengkap</span>
                        <strong style="color: #1e293b;">${escapeHtml(account.nama)}</strong>
                    </div>
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 8px;">
                        <span style="color: #64748b; display: inline-block; width: 120px;">Username</span>
                        <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: #0f172a;">${escapeHtml(account.username)}</code>
                    </div>
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 8px;">
                        <span style="color: #64748b; display: inline-block; width: 120px;">Email</span>
                        <strong style="color: #1e293b;">${escapeHtml(account.email || '-')}</strong>
                    </div>
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 8px;">
                        <span style="color: #64748b; display: inline-block; width: 120px;">Peran Akses</span>
                        <strong style="color: #1e293b;">${escapeHtml(account.peran)}</strong>
                    </div>
                    <div>
                        <span style="color: #64748b; display: inline-block; width: 120px;">Status Akun</span>
                        <span class="badge-status ${account.status === 'Aktif' ? 'badge-status--aktif' : 'badge-status--nonaktif'}">${account.status}</span>
                    </div>
                </div>
            `,
            confirmButtonColor: '#852019',
            confirmButtonText: 'Tutup'
        });
    }
}

// 9. Modal Filter Data Logic
function populateFilterNamaDropdown() {
    const select = document.getElementById('filterNama');
    if (!select) return;

    const currentVal = state.filters.nama;
    // Ambil nama-nama unik dari data akun
    const uniqueNames = [...new Set(AccountData.map(a => a.nama))].sort();

    select.innerHTML = '<option value="">Pilih Nama Pengguna</option>' +
        uniqueNames.map(name => {
            const isSelected = (name === currentVal) ? 'selected' : '';
            return `<option value="${escapeHtml(name)}" ${isSelected}>${escapeHtml(name)}</option>`;
        }).join('');
}

function openFilterModal() {
    populateFilterNamaDropdown();

    // Set nilai input sesuai filter saat ini
    const keywordInput = document.getElementById('filterKeyword');
    const namaSelect = document.getElementById('filterNama');
    const peranSelect = document.getElementById('filterPeran');
    const statusSelect = document.getElementById('filterStatus');

    if (keywordInput) keywordInput.value = state.filters.keyword;
    if (namaSelect) namaSelect.value = state.filters.nama;
    if (peranSelect) peranSelect.value = state.filters.peran;
    if (statusSelect) statusSelect.value = state.filters.status;

    openModal('modalFilter');
}

function applyFilter() {
    const keywordInput = document.getElementById('filterKeyword');
    const namaSelect = document.getElementById('filterNama');
    const peranSelect = document.getElementById('filterPeran');
    const statusSelect = document.getElementById('filterStatus');

    state.filters.keyword = keywordInput ? keywordInput.value.trim() : '';
    state.filters.nama = namaSelect ? namaSelect.value : '';
    state.filters.peran = peranSelect ? peranSelect.value : '';
    state.filters.status = statusSelect ? statusSelect.value : '';

    updateFilterBadge();
    state.currentPage = 1;
    closeModal('modalFilter');
    render();
}

function resetFilter() {
    const keywordInput = document.getElementById('filterKeyword');
    const namaSelect = document.getElementById('filterNama');
    const peranSelect = document.getElementById('filterPeran');
    const statusSelect = document.getElementById('filterStatus');

    if (keywordInput) keywordInput.value = '';
    if (namaSelect) namaSelect.value = '';
    if (peranSelect) peranSelect.value = '';
    if (statusSelect) statusSelect.value = '';

    state.filters = {
        keyword: '',
        nama: '',
        peran: '',
        status: ''
    };

    updateFilterBadge();
    state.currentPage = 1;
    closeModal('modalFilter');
    render();
}

// 10. Modal Tambah Akun Logic
function resetFieldError(inputEl, errorEl) {
    if (inputEl) inputEl.classList.remove('has-error');
    if (errorEl) {
        errorEl.textContent = '';
        errorEl.classList.remove('show');
    }
}

function setFieldError(inputEl, errorEl, message) {
    if (inputEl) inputEl.classList.add('has-error');
    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.add('show');
    }
}

function openAddAccountModal() {
    const form = document.getElementById('formAddAccount');
    if (form) form.reset();

    // Reset pesan error
    ['addNama', 'addUsername', 'addPeran', 'addEmail', 'addPassword', 'addConfirmPassword'].forEach(field => {
        const input = document.getElementById(field);
        const error = document.getElementById('error' + field.charAt(0).toUpperCase() + field.slice(1));
        resetFieldError(input, error);
    });

    // Reset password inputs ke mode hidden
    ['addPassword', 'addConfirmPassword'].forEach(field => {
        const input = document.getElementById(field);
        if (input) input.type = 'password';
    });
    document.querySelectorAll('#modalAddAccount .btn-password-toggle i').forEach(icon => {
        icon.className = 'ph-bold ph-eye';
    });

    openModal('modalAddAccount');
}

function handleAddAccountSubmit(e) {
    e.preventDefault();

    const namaInput = document.getElementById('addNama');
    const usernameInput = document.getElementById('addUsername');
    const peranSelect = document.getElementById('addPeran');
    const emailInput = document.getElementById('addEmail');
    const passwordInput = document.getElementById('addPassword');
    const confirmPasswordInput = document.getElementById('addConfirmPassword');

    const errNama = document.getElementById('errorAddNama');
    const errUsername = document.getElementById('errorAddUsername');
    const errPeran = document.getElementById('errorAddPeran');
    const errEmail = document.getElementById('errorAddEmail');
    const errPassword = document.getElementById('errorAddPassword');
    const errConfirmPassword = document.getElementById('errorAddConfirmPassword');

    // Reset status error
    resetFieldError(namaInput, errNama);
    resetFieldError(usernameInput, errUsername);
    resetFieldError(peranSelect, errPeran);
    resetFieldError(emailInput, errEmail);
    resetFieldError(passwordInput, errPassword);
    resetFieldError(confirmPasswordInput, errConfirmPassword);

    const nama = namaInput ? namaInput.value.trim() : '';
    const username = usernameInput ? usernameInput.value.trim() : '';
    const peran = peranSelect ? peranSelect.value : '';
    const email = emailInput ? emailInput.value.trim() : '';
    const password = passwordInput ? passwordInput.value : '';
    const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : '';

    let isValid = true;

    // 1. Validasi Nama
    if (!nama) {
        setFieldError(namaInput, errNama, 'Nama lengkap wajib diisi');
        isValid = false;
    }

    // 2. Validasi Username
    if (!username) {
        setFieldError(usernameInput, errUsername, 'Username wajib diisi');
        isValid = false;
    } else if (AccountData.some(a => a.username.toLowerCase() === username.toLowerCase())) {
        setFieldError(usernameInput, errUsername, 'Username sudah digunakan oleh akun lain');
        isValid = false;
    }

    // 3. Validasi Peran
    if (!peran) {
        setFieldError(peranSelect, errPeran, 'Pilih peran akun');
        isValid = false;
    }

    // 4. Validasi Email
    if (!email) {
        setFieldError(emailInput, errEmail, 'Email wajib diisi');
        isValid = false;
    } else if (!isValidEmail(email)) {
        setFieldError(emailInput, errEmail, 'Format email tidak valid (contoh: user@sdnbudikarya.sch.id)');
        isValid = false;
    }

    // 5. Validasi Password
    if (!password) {
        setFieldError(passwordInput, errPassword, 'Password wajib diisi');
        isValid = false;
    } else if (password.length < 6) {
        setFieldError(passwordInput, errPassword, 'Password minimal 6 karakter');
        isValid = false;
    }

    // 6. Validasi Konfirmasi Password
    if (!confirmPassword) {
        setFieldError(confirmPasswordInput, errConfirmPassword, 'Konfirmasi password wajib diisi');
        isValid = false;
    } else if (password !== confirmPassword) {
        setFieldError(confirmPasswordInput, errConfirmPassword, 'Konfirmasi password tidak cocok');
        isValid = false;
    }

    if (!isValid) return;

    const submitBtn = e.target.querySelector('button[type="submit"]');
    const origBtnHtml = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Menyimpan ke DB...</span>';
    }

    const formData = new FormData();
    formData.append('action', 'create');
    formData.append('csrf_token', getCsrfToken());
    formData.append('nama', nama);
    formData.append('username', username);
    formData.append('email', email);
    formData.append('password', password);
    formData.append('peran', peran);
    formData.append('status', 'Aktif');

    fetch(getAccountApiUrl(), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(res => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnHtml;
        }

        if (res && res.success) {
            const createdUser = res.data.user || {
                id: Date.now(),
                nama: nama,
                username: username,
                email: email,
                password: password,
                peran: peran,
                status: 'Aktif'
            };

            AccountData.unshift(createdUser);
            populateFilterNamaDropdown();
            closeModal('modalAddAccount');
            state.currentPage = 1;
            render();

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Akun Tersimpan di Database!',
                    text: `Akun ${nama} (${peran}) berhasil didaftarkan ke tabel users.`,
                    timer: 1800,
                    showConfirmButton: false,
                    confirmButtonColor: '#16a34a'
                });
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menambah Akun',
                    text: (res && res.message) ? res.message : 'Terjadi kesalahan saat menyimpan ke database.',
                    confirmButtonColor: '#852019'
                });
            }
        }
    })
    .catch(err => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnHtml;
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Gangguan Jaringan',
                text: 'Tidak dapat terhubung ke server database.',
                confirmButtonColor: '#852019'
            });
        }
    });
}

// 11. Modal Edit Akun Logic
function openEditAccountModal(id) {
    const account = AccountData.find(a => a.id === id);
    if (!account) return;

    const idInput = document.getElementById('editAccountId');
    const namaInput = document.getElementById('editNama');
    const usernameInput = document.getElementById('editUsername');
    const peranSelect = document.getElementById('editPeran');
    const emailInput = document.getElementById('editEmail');
    const passwordInput = document.getElementById('editPassword');
    const confirmPasswordInput = document.getElementById('editConfirmPassword');

    if (idInput) idInput.value = account.id;
    if (namaInput) namaInput.value = account.nama;
    if (usernameInput) usernameInput.value = account.username;
    if (peranSelect) peranSelect.value = account.peran;
    if (emailInput) emailInput.value = account.email || '';
    if (passwordInput) passwordInput.value = '';
    if (confirmPasswordInput) confirmPasswordInput.value = '';

    // Reset error messages
    ['editNama', 'editUsername', 'editPeran', 'editEmail', 'editPassword', 'editConfirmPassword'].forEach(field => {
        const input = document.getElementById(field);
        const error = document.getElementById('error' + field.charAt(0).toUpperCase() + field.slice(1));
        resetFieldError(input, error);
    });

    // Reset password inputs ke hidden
    ['editPassword', 'editConfirmPassword'].forEach(field => {
        const input = document.getElementById(field);
        if (input) input.type = 'password';
    });
    document.querySelectorAll('#modalEditAccount .btn-password-toggle i').forEach(icon => {
        icon.className = 'ph-bold ph-eye';
    });

    openModal('modalEditAccount');
}

function handleEditAccountSubmit(e) {
    e.preventDefault();

    const idInput = document.getElementById('editAccountId');
    const namaInput = document.getElementById('editNama');
    const usernameInput = document.getElementById('editUsername');
    const peranSelect = document.getElementById('editPeran');
    const emailInput = document.getElementById('editEmail');
    const passwordInput = document.getElementById('editPassword');
    const confirmPasswordInput = document.getElementById('editConfirmPassword');

    const errNama = document.getElementById('errorEditNama');
    const errUsername = document.getElementById('errorEditUsername');
    const errPeran = document.getElementById('errorEditPeran');
    const errEmail = document.getElementById('errorEditEmail');
    const errPassword = document.getElementById('errorEditPassword');
    const errConfirmPassword = document.getElementById('errorEditConfirmPassword');

    resetFieldError(namaInput, errNama);
    resetFieldError(usernameInput, errUsername);
    resetFieldError(peranSelect, errPeran);
    resetFieldError(emailInput, errEmail);
    resetFieldError(passwordInput, errPassword);
    resetFieldError(confirmPasswordInput, errConfirmPassword);

    const accountId = idInput ? parseInt(idInput.value, 10) : 0;
    const account = AccountData.find(a => a.id === accountId);
    if (!account) return;

    const nama = namaInput ? namaInput.value.trim() : '';
    const username = usernameInput ? usernameInput.value.trim() : '';
    const peran = peranSelect ? peranSelect.value : '';
    const email = emailInput ? emailInput.value.trim() : '';
    const password = passwordInput ? passwordInput.value : '';
    const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : '';

    let isValid = true;

    // 1. Validasi Nama
    if (!nama) {
        setFieldError(namaInput, errNama, 'Nama lengkap wajib diisi');
        isValid = false;
    }

    // 2. Validasi Username
    if (!username) {
        setFieldError(usernameInput, errUsername, 'Username wajib diisi');
        isValid = false;
    } else if (AccountData.some(a => a.id !== accountId && a.username.toLowerCase() === username.toLowerCase())) {
        setFieldError(usernameInput, errUsername, 'Username sudah digunakan oleh akun lain');
        isValid = false;
    }

    // 3. Validasi Peran
    if (!peran) {
        setFieldError(peranSelect, errPeran, 'Pilih peran akun');
        isValid = false;
    }

    // 4. Validasi Email
    if (!email) {
        setFieldError(emailInput, errEmail, 'Email wajib diisi');
        isValid = false;
    } else if (!isValidEmail(email)) {
        setFieldError(emailInput, errEmail, 'Format email tidak valid (contoh: user@sdnbudikarya.sch.id)');
        isValid = false;
    }

    // 5. Validasi Password (Opsional jika ingin diubah)
    if (password) {
        if (password.length < 6) {
            setFieldError(passwordInput, errPassword, 'Password baru minimal 6 karakter');
            isValid = false;
        }

        if (!confirmPassword) {
            setFieldError(confirmPasswordInput, errConfirmPassword, 'Konfirmasi password baru wajib diisi');
            isValid = false;
        } else if (password !== confirmPassword) {
            setFieldError(confirmPasswordInput, errConfirmPassword, 'Konfirmasi password tidak cocok');
            isValid = false;
        }
    }

    if (!isValid) return;

    const submitBtn = e.target.querySelector('button[type="submit"]');
    const origBtnHtml = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span>Menyimpan ke DB...</span>';
    }

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('csrf_token', getCsrfToken());
    formData.append('id', accountId);
    formData.append('nama', nama);
    formData.append('username', username);
    formData.append('email', email);
    formData.append('peran', peran);
    if (password) {
        formData.append('password', password);
    }

    fetch(getAccountApiUrl(), {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(res => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnHtml;
        }

        if (res && res.success) {
            account.nama = nama;
            account.username = username;
            account.peran = peran;
            account.email = email;
            if (password) {
                account.password = password;
            }

            populateFilterNamaDropdown();
            closeModal('modalEditAccount');
            render();

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Perubahan Disimpan di DB!',
                    text: `Data akun ${account.nama} berhasil diperbarui di database.`,
                    timer: 1600,
                    showConfirmButton: false,
                    confirmButtonColor: '#16a34a'
                });
            }
        } else {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Memperbarui Akun',
                    text: (res && res.message) ? res.message : 'Terjadi kesalahan saat memperbarui database.',
                    confirmButtonColor: '#852019'
                });
            }
        }
    })
    .catch(err => {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnHtml;
        }
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Gangguan Jaringan',
                text: 'Tidak dapat terhubung ke server database.',
                confirmButtonColor: '#852019'
            });
        }
    });
}

// 12. Password Eye Toggle untuk Form Fields
function setupPasswordEyeToggles() {
    document.querySelectorAll('.btn-password-toggle').forEach(btn => {
        btn.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            if (!targetId) return;

            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'ph-bold ph-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'ph-bold ph-eye';
            }
        });
    });
}

// 13. Event Listeners & Inisialisasi
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('accountSearchInput');
    const clearBtn = document.getElementById('accountSearchClear');
    const rangeOptions = document.getElementById('rangeOptions');
    const btnAdd = document.getElementById('btnAddAccount');
    const btnFilter = document.getElementById('btnFilter');
    const togglePasswordCol = document.getElementById('togglePasswordCol');
    const btnApplyFilterEl = document.getElementById('btnApplyFilter');
    const btnResetFilterEl = document.getElementById('btnResetFilter');
    const formAddAccountEl = document.getElementById('formAddAccount');
    const formEditAccountEl = document.getElementById('formEditAccount');

    // A. Live Search dengan Debounce 200ms
    if (searchInput) {
        const handleSearch = debounce(function (val) {
            state.keyword = val;
            state.currentPage = 1;
            render();
        }, 200);

        searchInput.addEventListener('input', function (e) {
            handleSearch(e.target.value);
        });

        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                searchInput.value = '';
                state.keyword = '';
                state.currentPage = 1;
                render();
            }
        });
    }

    // B. Tombol Clear Pencarian ("x")
    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            state.keyword = '';
            state.currentPage = 1;
            render();
        });
    }

    // C. Range Selector (5, 10, 25, 50, all)
    if (rangeOptions) {
        rangeOptions.addEventListener('click', function (e) {
            const btn = e.target.closest('.range-btn');
            if (!btn) return;

            const selectedRange = btn.getAttribute('data-range');
            if (!selectedRange) return;

            rangeOptions.querySelectorAll('.range-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            state.range = selectedRange;
            state.currentPage = 1;
            render();
        });
    }

    // D. Toggle Tampilkan Kolom Password
    if (togglePasswordCol) {
        togglePasswordCol.addEventListener('change', function (e) {
            state.showPasswordCol = e.target.checked;
            if (!state.showPasswordCol) {
                state.visiblePasswords = {}; // Reset keadaan intip password saat kolom disembunyikan
            }
            render();
        });
    }

    // E. Tombol Filter & Handlers
    if (btnFilter) {
        btnFilter.addEventListener('click', openFilterModal);
    }
    if (btnApplyFilterEl) {
        btnApplyFilterEl.addEventListener('click', applyFilter);
    }
    if (btnResetFilterEl) {
        btnResetFilterEl.addEventListener('click', resetFilter);
    }

    // F. Tombol Tambah Akun & Form
    if (btnAdd) {
        btnAdd.addEventListener('click', openAddAccountModal);
    }
    if (formAddAccountEl) {
        formAddAccountEl.addEventListener('submit', handleAddAccountSubmit);
    }

    // G. Form Edit Akun
    if (formEditAccountEl) {
        formEditAccountEl.addEventListener('submit', handleEditAccountSubmit);
    }

    // H. Setup Password Eye Toggles
    setupPasswordEyeToggles();

    // I. Modal Backdrop & Close Buttons
    document.addEventListener('click', function (e) {
        // Klik tombol dengan [data-modal-close]
        const closeBtn = e.target.closest('[data-modal-close]');
        if (closeBtn) {
            const overlay = closeBtn.closest('.modal-overlay');
            if (overlay) {
                closeModal(overlay.id);
            }
            return;
        }

        // Klik di area overlay (luar dialog)
        if (e.target.classList.contains('modal-overlay')) {
            closeModal(e.target.id);
        }
    });

    // J. Keyboard ESC untuk Menutup Modal
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const openModalEl = document.querySelector('.modal-overlay.show');
            if (openModalEl) {
                closeModal(openModalEl.id);
            }
        }
    });

    // Inisialisasi status filter badge & Render pertama kali
    updateFilterBadge();
    render();
    loadAccountData();
});
