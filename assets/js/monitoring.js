/**
 * Monitoring Engine - Sistem Informasi Inventaris Aset SDN Budi Karya
 * Menangani 7 lokasi penempatan (Kelas 1-6 & Aset Umum) + tab Semua (read-only),
 * kartu ringkasan, pencarian, filter, pagination, dan CRUD monitoring aset.
 */

class MonitoringEngine {
    constructor(config) {
        this.config = Object.assign({
            initialPenempatan: 'kelas-1',
            baseMonitoringUrl: './',
            apiUrl: '../../includes/ajax-monitoring.php',
            kibBaseUrl: '../data-aset/',
            currentUserName: 'Administrator',
            defaultRange: 'all'
        }, config);

        this.locations = [
            { id: 'semua', label: 'Semua', file: 'semua.php', title: 'MONITORING - Semua Lokasi', subtitle: 'Ringkasan gabungan hasil pemantauan aset dari seluruh lokasi penempatan (read-only)' },
            { id: 'kelas-1', label: 'Kelas 1', file: 'kelas-1.php', title: 'MONITORING - Kelas 1', subtitle: 'Pemantauan kondisi fisik dan jumlah aset yang ditempatkan di Kelas 1' },
            { id: 'kelas-2', label: 'Kelas 2', file: 'kelas-2.php', title: 'MONITORING - Kelas 2', subtitle: 'Pemantauan kondisi fisik dan jumlah aset yang ditempatkan di Kelas 2' },
            { id: 'kelas-3', label: 'Kelas 3', file: 'kelas-3.php', title: 'MONITORING - Kelas 3', subtitle: 'Pemantauan kondisi fisik dan jumlah aset yang ditempatkan di Kelas 3' },
            { id: 'kelas-4', label: 'Kelas 4', file: 'kelas-4.php', title: 'MONITORING - Kelas 4', subtitle: 'Pemantauan kondisi fisik dan jumlah aset yang ditempatkan di Kelas 4' },
            { id: 'kelas-5', label: 'Kelas 5', file: 'kelas-5.php', title: 'MONITORING - Kelas 5', subtitle: 'Pemantauan kondisi fisik dan jumlah aset yang ditempatkan di Kelas 5' },
            { id: 'kelas-6', label: 'Kelas 6', file: 'kelas-6.php', title: 'MONITORING - Kelas 6', subtitle: 'Pemantauan kondisi fisik dan jumlah aset yang ditempatkan di Kelas 6' },
            { id: 'umum', label: 'Aset Umum', file: 'aset-umum.php', title: 'MONITORING - Aset Umum', subtitle: 'Pemantauan aset umum (perpustakaan, kantor, lapangan, UKS, dan fasilitas bersama)' }
        ];

        this.state = {
            penempatan: this.normalizePenempatan(this.config.initialPenempatan),
            items: [],
            summary: {
                total_aset_terdaftar: 0,
                total_unit_sekolah: 0,
                total_entri: 0,
                total_unit: 0,
                baik: 0,
                rusak_ringan: 0,
                rusak_berat: 0,
                hilang: 0
            },
            assetOptions: [],
            keyword: '',
            filters: {
                keyword: '',
                kib: '',
                jenis: '',
                kondisi: '',
                tglUpdate: ''
            },
            range: this.config.defaultRange,
            currentPage: 1,
            loading: false
        };

        this.editingId = null;
        this.assetDropdownOpen = false;
        this.assetActiveIndex = -1;
        this.filteredAssetOptions = [];
        this.selectedAssetLabel = '';
        this.init();
    }

    isClassLocation(penempatan = this.state.penempatan) {
        return String(penempatan || '').startsWith('kelas-');
    }

    normalizePenempatan(val) {
        const v = String(val || 'kelas-1').trim().toLowerCase();
        if (v === 'aset-umum') return 'umum';
        if (v === 'all') return 'semua';
        const valid = this.locations.some(loc => loc.id === v);
        return valid ? v : 'kelas-1';
    }

    getLocationMeta(penempatan) {
        return this.locations.find(loc => loc.id === penempatan) || this.locations[1];
    }

    isReadonlyMode() {
        return this.state.penempatan === 'semua';
    }

    getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    highlightMatch(text, term) {
        const safeText = this.escapeHtml(text);
        if (!term) return safeText;
        const escapedTerm = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        try {
            const regex = new RegExp(`(${escapedTerm})`, 'gi');
            return safeText.replace(regex, '<mark style="background:#fef08a;color:#0f172a;padding:0 2px;border-radius:3px;">$1</mark>');
        } catch (_) {
            return safeText;
        }
    }

    formatDateId(isoDate) {
        if (!isoDate || !/^\d{4}-\d{2}-\d{2}$/.test(isoDate)) return isoDate || '-';
        const [y, m, d] = isoDate.split('-');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const mIdx = parseInt(m, 10) - 1;
        return `${parseInt(d, 10)} ${months[mIdx] || m} ${y}`;
    }

    getTodayIso() {
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const dd = String(now.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }

    init() {
        this.bindStaticEvents();
        this.syncHeaderAndTabs();
        this.loadLocationData(this.state.penempatan);
    }

    bindStaticEvents() {
        // Browser back/forward button
        window.addEventListener('popstate', (e) => {
            if (e.state && e.state.penempatan) {
                this.switchLocation(e.state.penempatan, false);
            }
        });

        // Search bar
        const searchInput = document.getElementById('monSearchInput');
        const searchClear = document.getElementById('monSearchClear');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                this.state.keyword = searchInput.value.trim();
                this.state.currentPage = 1;
                if (searchClear) {
                    searchClear.classList.toggle('visible', this.state.keyword.length > 0);
                }
                this.renderTable();
            });
        }
        if (searchClear) {
            searchClear.addEventListener('click', () => {
                if (searchInput) searchInput.value = '';
                this.state.keyword = '';
                this.state.currentPage = 1;
                searchClear.classList.remove('visible');
                this.renderTable();
            });
        }

        // Range buttons
        document.querySelectorAll('#monRangeOptions .range-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('#monRangeOptions .range-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const r = btn.getAttribute('data-range');
                this.state.range = (r === 'all') ? 'all' : parseInt(r, 10);
                this.state.currentPage = 1;
                this.renderTable();
            });
        });

        // Tombol buka filter & tambah
        const btnFilter = document.getElementById('btnMonFilter');
        if (btnFilter) {
            btnFilter.addEventListener('click', () => this.openFilterModal());
        }
        const btnAdd = document.getElementById('btnAddMonitoring');
        if (btnAdd) {
            btnAdd.addEventListener('click', () => this.openAddModal());
        }

        // Filter actions
        const btnResetFilter = document.getElementById('btnMonResetFilter');
        const btnApplyFilter = document.getElementById('btnMonApplyFilter');
        if (btnResetFilter) {
            btnResetFilter.addEventListener('click', () => this.resetFilters(true));
        }
        if (btnApplyFilter) {
            btnApplyFilter.addEventListener('click', () => this.applyFilters());
        }

        // Combobox pencarian Nama Aset -> autofill KIB, Kode, Jenis, Jumlah default
        this.bindAssetComboboxEvents();

        // Kondisi berubah -> aktif/nonaktif opsi Perlu Pemeliharaan?
        const kondisiEl = document.getElementById('monFieldKondisi');
        if (kondisiEl) {
            kondisiEl.addEventListener('change', () => this.syncPerluPemeliharaanState());
        }

        // Form submit tambah/edit monitoring
        const form = document.getElementById('monAssetForm');
        if (form) {
            form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        // Tutup modal
        document.querySelectorAll('#monModalFilter [data-modal-close], #monModalForm [data-modal-close]').forEach(btn => {
            btn.addEventListener('click', () => {
                const overlay = btn.closest('.kib-modal-overlay');
                if (overlay) this.closeModal(overlay.id);
            });
        });

        ['monModalFilter', 'monModalForm'].forEach(modalId => {
            const overlay = document.getElementById(modalId);
            if (overlay) {
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) this.closeModal(modalId);
                });
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (this.assetDropdownOpen) {
                    this.closeAssetDropdown();
                    return;
                }
                this.closeModal('monModalFilter');
                this.closeModal('monModalForm');
            }
        });
    }

    switchLocation(penempatan, pushHistory = true) {
        const nextLoc = this.normalizePenempatan(penempatan);
        this.state.penempatan = nextLoc;

        // Reset filter & pencarian saat pindah lokasi
        this.resetFilters(false);
        const searchInput = document.getElementById('monSearchInput');
        const searchClear = document.getElementById('monSearchClear');
        if (searchInput) searchInput.value = '';
        if (searchClear) searchClear.classList.remove('visible');
        this.state.keyword = '';
        this.state.currentPage = 1;

        this.syncHeaderAndTabs();

        if (pushHistory && window.history && window.history.pushState) {
            const meta = this.getLocationMeta(nextLoc);
            const targetUrl = `${this.config.baseMonitoringUrl}${meta.file}`;
            window.history.pushState({ penempatan: nextLoc }, '', targetUrl);
        }

        this.loadLocationData(nextLoc);
    }

    syncHeaderAndTabs() {
        const meta = this.getLocationMeta(this.state.penempatan);
        const readonly = this.isReadonlyMode();

        // Update judul & subjudul
        const titleEl = document.getElementById('monPageTitle');
        const subtitleEl = document.getElementById('monPageSubtitle');
        if (titleEl) titleEl.textContent = meta.title;
        if (subtitleEl) subtitleEl.textContent = meta.subtitle;
        document.title = `${meta.label} - Monitoring Aset - Sistem Informasi Inventaris Aset SDN Budi Karya`;

        // Toggle tombol Tambah & banner read-only
        const btnAdd = document.getElementById('btnAddMonitoring');
        if (btnAdd) {
            btnAdd.style.display = readonly ? 'none' : 'inline-flex';
        }
        const roBanner = document.getElementById('monReadonlyBanner');
        if (roBanner) {
            roBanner.classList.toggle('show', readonly);
        }

        // Toggle kolom Lokasi pada thead
        const thLokasi = document.getElementById('monThLokasi');
        if (thLokasi) {
            thLokasi.style.display = readonly ? '' : 'none';
        }

        // Sinkronisasi opsi KIB pada Modal Filter & placeholder combobox
        this.syncFilterKibOptions();
        const asetSearchInput = document.getElementById('monFieldAsetSearch');
        if (asetSearchInput) {
            asetSearchInput.placeholder = this.isClassLocation()
                ? 'Cari aset KIB B / KIB E (nama, kode, jenis)...'
                : 'Cari aset KIB A-F (nama, kode, jenis)...';
        }

        // Sinkronisasi active state submenu sidebar
        document.querySelectorAll('[data-monitoring-subitem]').forEach(li => {
            const loc = li.getAttribute('data-monitoring-subitem');
            li.classList.toggle('active', loc === this.state.penempatan);
        });
    }

    syncFilterKibOptions() {
        const kibSelect = document.getElementById('monFilterKib');
        if (!kibSelect) return;

        const allowed = this.isClassLocation()
            ? [
                { val: 'kib-b', label: 'KIB B' },
                { val: 'kib-e', label: 'KIB E' }
            ]
            : [
                { val: 'kib-a', label: 'KIB A' },
                { val: 'kib-b', label: 'KIB B' },
                { val: 'kib-c', label: 'KIB C' },
                { val: 'kib-d', label: 'KIB D' },
                { val: 'kib-e', label: 'KIB E' },
                { val: 'kib-f', label: 'KIB F' }
            ];

        const prevVal = this.state.filters.kib || '';
        const isStillAllowed = allowed.some(k => k.val === prevVal);
        if (prevVal && !isStillAllowed) {
            this.state.filters.kib = '';
            this.updateFilterButtonIndicator();
        }

        kibSelect.innerHTML = '<option value="">Semua KIB</option>' +
            allowed.map(k => `<option value="${k.val}">${k.label}</option>`).join('');
        kibSelect.value = isStillAllowed ? prevVal : '';
    }

    async loadLocationData(penempatan) {
        const activeLoc = this.normalizePenempatan(penempatan);
        this.state.loading = true;
        this.renderLoadingState();

        try {
            const listUrl = `${this.config.apiUrl}?action=list&penempatan=${encodeURIComponent(activeLoc)}`;
            const resp = await fetch(listUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const res = await resp.json();

            if (this.state.penempatan !== activeLoc) return;

            if (res && res.success && res.data) {
                this.state.items = Array.isArray(res.data.items) ? res.data.items : [];
                this.state.summary = res.data.summary || this.state.summary;
                this.state.assetOptions = Array.isArray(res.data.asset_options) ? res.data.asset_options : [];
                this.populateFilterOptions();
                this.renderSummaryCards();
                this.renderTable();
            } else {
                this.state.items = [];
                this.state.assetOptions = [];
                this.renderSummaryCards();
                this.renderTable();
                if (typeof Swal !== 'undefined' && res && res.message) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Memuat Data',
                        text: res.message,
                        confirmButtonColor: '#852019'
                    });
                }
            }
        } catch (_) {
            if (this.state.penempatan !== activeLoc) return;
            this.state.items = [];
            this.state.assetOptions = [];
            this.renderSummaryCards();
            this.renderTable();
        } finally {
            this.state.loading = false;
        }
    }

    async refreshAssetOptions() {
        if (this.isReadonlyMode()) {
            this.state.assetOptions = [];
            return [];
        }
        const url = `${this.config.apiUrl}?action=asset_options&penempatan=${encodeURIComponent(this.state.penempatan)}`;
        const resp = await fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const res = await resp.json();
        if (res && res.success && res.data && Array.isArray(res.data.asset_options)) {
            this.state.assetOptions = res.data.asset_options;
        }
        return this.state.assetOptions;
    }

    renderLoadingState() {
        const tbody = document.getElementById('monTableBody');
        const colSpan = this.isReadonlyMode() ? 11 : 10;
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="${colSpan}" class="kib-empty-row">
                        <i class="ph-bold ph-spinner-gap"></i> Memuat data monitoring...
                    </td>
                </tr>
            `;
        }
    }

    renderSummaryCards() {
        const container = document.getElementById('monSummaryCards');
        if (!container) return;

        const s = this.state.summary || {};
        const totalUnit = Number(s.total_unit || 0);
        const totalEntri = Number(s.total_entri || 0);
        const totalTerdaftar = Number(s.total_aset_terdaftar || 0);
        const totalUnitSekolah = Number(s.total_unit_sekolah || 0);
        const baik = Number(s.baik || 0);
        const ringan = Number(s.rusak_ringan || 0);
        const berat = Number(s.rusak_berat || 0);
        const hilang = Number(s.hilang || 0);
        const pct = (val) => totalUnit > 0 ? Math.round((val / totalUnit) * 100) : 0;
        const meta = this.getLocationMeta(this.state.penempatan);

        const totalDesc = this.isReadonlyMode()
            ? `dari ${totalUnitSekolah.toLocaleString('id-ID')} unit KIB (${totalTerdaftar} aset)`
            : `${totalEntri} catatan monitoring di ${meta.label}`;

        const cards = [
            {
                label: 'Total Aset',
                value: totalUnit,
                desc: totalDesc,
                color: 'blue',
                icon: 'ph-bold ph-chart-bar'
            },
            {
                label: 'Baik',
                value: baik,
                desc: `(${pct(baik)}%) dari total unit termonitor`,
                color: 'green',
                icon: 'ph-bold ph-check-circle'
            },
            {
                label: 'Rusak Ringan',
                value: ringan,
                desc: `(${pct(ringan)}%) dari total unit termonitor`,
                color: 'orange',
                icon: 'ph-bold ph-warning'
            },
            {
                label: 'Rusak Berat',
                value: berat,
                desc: `(${pct(berat)}%) dari total unit termonitor`,
                color: 'red',
                icon: 'ph-bold ph-x-circle'
            },
            {
                label: 'Hilang',
                value: hilang,
                desc: `(${pct(hilang)}%) dari total unit termonitor`,
                color: 'gray',
                icon: 'ph-bold ph-minus-circle'
            }
        ];

        container.innerHTML = cards.map(item => `
            <div class="dash-stat-card dash-stat-card--${item.color}">
                <div class="dash-stat-top">
                    <div class="dash-stat-icon"><i class="${item.icon}"></i></div>
                    <span class="dash-stat-label">${this.escapeHtml(item.label)}</span>
                </div>
                <div class="dash-stat-value">${item.value.toLocaleString('id-ID')}</div>
                <div class="dash-stat-desc">${this.escapeHtml(item.desc)}</div>
            </div>
        `).join('');
    }

    getFilteredItems() {
        const q = this.state.keyword.toLowerCase();
        const f = this.state.filters;
        const fKw = (f.keyword || '').toLowerCase();

        return this.state.items.filter(item => {
            if (f.kib && item.kib !== f.kib) return false;
            if (f.jenis && item.jenis_aset !== f.jenis) return false;
            if (f.kondisi && item.kondisi !== f.kondisi) return false;
            if (f.tglUpdate && item.tgl_update !== f.tglUpdate) return false;

            const searchable = [
                item.kode_aset,
                item.nama_aset,
                item.kib_label,
                item.jenis_aset,
                item.penempatan_label,
                item.kondisi,
                item.petugas,
                item.catatan,
                item.tgl_update
            ].join(' ').toLowerCase();

            if (q && !searchable.includes(q)) return false;
            if (fKw && !searchable.includes(fKw)) return false;

            return true;
        });
    }

    renderTable() {
        const tbody = document.getElementById('monTableBody');
        const countCurrent = document.getElementById('monCountCurrent');
        const countTotal = document.getElementById('monCountTotal');
        const paginationEl = document.getElementById('monPagination');
        if (!tbody) return;

        const filtered = this.getFilteredItems();
        const totalAll = this.state.items.length;
        const totalFiltered = filtered.length;
        const readonly = this.isReadonlyMode();
        const colSpan = readonly ? 11 : 10;

        let displayItems = filtered;
        let startIndex = 0;
        let totalPages = 1;

        if (this.state.range !== 'all') {
            const perPage = Number(this.state.range) || 10;
            totalPages = Math.max(1, Math.ceil(totalFiltered / perPage));
            if (this.state.currentPage > totalPages) {
                this.state.currentPage = totalPages;
            }
            startIndex = (this.state.currentPage - 1) * perPage;
            displayItems = filtered.slice(startIndex, startIndex + perPage);
        } else {
            this.state.currentPage = 1;
        }

        if (countCurrent) countCurrent.textContent = displayItems.length;
        if (countTotal) countTotal.textContent = totalAll;

        const hlTerm = this.state.keyword || this.state.filters.keyword;

        if (displayItems.length === 0) {
            const meta = this.getLocationMeta(this.state.penempatan);
            const emptyMsg = totalAll === 0
                ? `Belum ada data monitoring aset pada ${meta.label}.`
                : 'Tidak ada data monitoring yang sesuai dengan pencarian atau filter.';
            tbody.innerHTML = `
                <tr>
                    <td colspan="${colSpan}" class="kib-empty-row">
                        ${this.escapeHtml(emptyMsg)}
                    </td>
                </tr>
            `;
        } else {
            tbody.innerHTML = displayItems.map((item, idx) => {
                const rowNo = startIndex + idx + 1;
                const badgeKondisi = this.getKondisiBadgeClass(item.kondisi);
                const lokasiTd = readonly
                    ? `<td class="cell-center"><span class="badge-mon-lokasi">${this.escapeHtml(item.penempatan_label)}</span></td>`
                    : '';

                const editDeleteBtns = readonly ? '' : `
                    <button
                        type="button"
                        class="btn-kib-act btn-kib-edit"
                        title="Edit Monitoring"
                        aria-label="Edit"
                        onclick="window.__monitoringEngine.openEditModal(${item.id})"
                    >
                        <i class="ph-bold ph-pencil-simple"></i>
                    </button>
                    <button
                        type="button"
                        class="btn-kib-act btn-kib-delete"
                        title="Hapus Monitoring"
                        aria-label="Hapus"
                        onclick="window.__monitoringEngine.deleteItem(${item.id})"
                    >
                        <i class="ph-bold ph-trash"></i>
                    </button>
                `;

                return `
                    <tr data-id="${item.id}">
                        <td class="cell-no">${rowNo}</td>
                        <td><span class="cell-code">${this.highlightMatch(item.kode_aset, hlTerm)}</span></td>
                        <td><strong class="cell-name">${this.highlightMatch(item.nama_aset, hlTerm)}</strong></td>
                        <td class="cell-center"><span class="badge-mon-kib">${this.escapeHtml(item.kib_label)}</span></td>
                        <td>${this.highlightMatch(item.jenis_aset || '-', hlTerm)}</td>
                        ${lokasiTd}
                        <td class="cell-center"><strong>${item.jumlah}</strong></td>
                        <td class="cell-center"><span class="badge-kib ${badgeKondisi}">${this.escapeHtml(item.kondisi)}</span></td>
                        <td class="cell-center">${this.escapeHtml(this.formatDateId(item.tgl_update))}</td>
                        <td>${this.highlightMatch(item.petugas || '-', hlTerm)}</td>
                        <td class="cell-actions">
                            <div class="kib-actions-cell">
                                <button
                                    type="button"
                                    class="btn-kib-act btn-kib-detail"
                                    title="Lihat Detail"
                                    aria-label="Detail"
                                    onclick="window.__monitoringEngine.showDetail(${item.id})"
                                >
                                    <i class="ph-bold ph-notebook"></i>
                                </button>
                                ${editDeleteBtns}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        this.renderPagination(paginationEl, totalPages);
    }

    getKondisiBadgeClass(kondisi) {
        const k = String(kondisi || '').toLowerCase();
        if (k === 'rusak berat') return 'badge-kib--rusak-berat';
        if (k === 'rusak ringan') return 'badge-kib--rusak-ringan';
        if (k === 'hilang') return 'badge-kib--hilang';
        return 'badge-kib--baik';
    }

    renderPagination(container, totalPages) {
        if (!container) return;
        if (this.state.range === 'all' || totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        const current = this.state.currentPage;
        const btns = [];

        btns.push(`<button type="button" class="page-btn" ${current === 1 ? 'disabled' : ''} data-page="${current - 1}" aria-label="Sebelumnya"><i class="ph-bold ph-caret-left"></i></button>`);
        for (let p = 1; p <= totalPages; p++) {
            btns.push(`<button type="button" class="page-btn ${p === current ? 'active' : ''}" data-page="${p}">${p}</button>`);
        }
        btns.push(`<button type="button" class="page-btn" ${current === totalPages ? 'disabled' : ''} data-page="${current + 1}" aria-label="Berikutnya"><i class="ph-bold ph-caret-right"></i></button>`);

        container.innerHTML = btns.join('');
        container.querySelectorAll('.page-btn[data-page]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.disabled) return;
                const p = parseInt(btn.getAttribute('data-page'), 10);
                if (!isNaN(p) && p >= 1 && p <= totalPages) {
                    this.state.currentPage = p;
                    this.renderTable();
                }
            });
        });
    }

    populateFilterOptions() {
        const jenisSelect = document.getElementById('monFilterJenis');
        if (!jenisSelect) return;

        const currentVal = this.state.filters.jenis || '';
        const jenisSet = new Set();
        this.state.items.forEach(item => {
            if (item.jenis_aset) jenisSet.add(item.jenis_aset);
        });
        (this.state.assetOptions || []).forEach(opt => {
            if (opt.jenis) jenisSet.add(opt.jenis);
        });

        const sortedJenis = Array.from(jenisSet).sort((a, b) => a.localeCompare(b, 'id'));
        jenisSelect.innerHTML = '<option value="">Semua Jenis Aset</option>' +
            sortedJenis.map(j => `<option value="${this.escapeHtml(j)}">${this.escapeHtml(j)}</option>`).join('');
        if (currentVal && sortedJenis.includes(currentVal)) {
            jenisSelect.value = currentVal;
        }
    }

    openFilterModal() {
        const kwEl = document.getElementById('monFilterKeyword');
        const kibEl = document.getElementById('monFilterKib');
        const jenisEl = document.getElementById('monFilterJenis');
        const kondisiEl = document.getElementById('monFilterKondisi');
        const tglEl = document.getElementById('monFilterTglUpdate');

        if (kwEl) kwEl.value = this.state.filters.keyword || '';
        if (kibEl) kibEl.value = this.state.filters.kib || '';
        if (jenisEl) jenisEl.value = this.state.filters.jenis || '';
        if (kondisiEl) kondisiEl.value = this.state.filters.kondisi || '';
        if (tglEl) tglEl.value = this.state.filters.tglUpdate || '';

        this.openModal('monModalFilter');
    }

    applyFilters() {
        this.state.filters = {
            keyword: (document.getElementById('monFilterKeyword')?.value || '').trim(),
            kib: document.getElementById('monFilterKib')?.value || '',
            jenis: document.getElementById('monFilterJenis')?.value || '',
            kondisi: document.getElementById('monFilterKondisi')?.value || '',
            tglUpdate: document.getElementById('monFilterTglUpdate')?.value || ''
        };
        this.state.currentPage = 1;
        this.closeModal('monModalFilter');
        this.renderTable();
        this.updateFilterButtonIndicator();
    }

    resetFilters(rerender = true) {
        this.state.filters = {
            keyword: '',
            kib: '',
            jenis: '',
            kondisi: '',
            tglUpdate: ''
        };
        ['monFilterKeyword', 'monFilterKib', 'monFilterJenis', 'monFilterKondisi', 'monFilterTglUpdate'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        this.updateFilterButtonIndicator();
        if (rerender) {
            this.state.currentPage = 1;
            this.closeModal('monModalFilter');
            this.renderTable();
        }
    }

    updateFilterButtonIndicator() {
        const btn = document.getElementById('btnMonFilter');
        if (!btn) return;
        const f = this.state.filters;
        const activeCount = [f.keyword, f.kib, f.jenis, f.kondisi, f.tglUpdate].filter(Boolean).length;
        btn.classList.toggle('has-filter', activeCount > 0);
    }

    formatAssetDisplayLabel(asset) {
        if (!asset) return '';
        return `[${asset.kib_label}] ${asset.nama} (${asset.kode})`;
    }

    bindAssetComboboxEvents() {
        const combobox = document.getElementById('monAsetCombobox');
        const hiddenInput = document.getElementById('monFieldAset');
        const searchInput = document.getElementById('monFieldAsetSearch');
        const clearBtn = document.getElementById('monFieldAsetClear');
        const toggleBtn = document.getElementById('monFieldAsetToggle');
        const dropdown = document.getElementById('monAsetDropdown');

        if (!combobox || !hiddenInput || !searchInput || !dropdown) return;

        searchInput.addEventListener('focus', () => {
            if (searchInput.disabled) return;
            const val = searchInput.value.trim();
            const query = (val === this.selectedAssetLabel) ? '' : val;
            this.openAssetDropdown(query);
        });

        searchInput.addEventListener('click', () => {
            if (searchInput.disabled) return;
            if (!this.assetDropdownOpen) {
                const val = searchInput.value.trim();
                const query = (val === this.selectedAssetLabel) ? '' : val;
                this.openAssetDropdown(query);
            }
        });

        searchInput.addEventListener('input', () => {
            if (searchInput.disabled) return;
            const rawVal = searchInput.value;
            const trimmed = rawVal.trim();

            if (trimmed !== this.selectedAssetLabel) {
                if (hiddenInput.value !== '') {
                    hiddenInput.value = '';
                    this.selectedAssetLabel = '';
                    this.handleAssetSelectChange();
                }
            }
            this.updateAssetClearButton();
            this.openAssetDropdown(trimmed);
        });

        searchInput.addEventListener('keydown', (e) => {
            if (searchInput.disabled) return;
            const len = this.filteredAssetOptions.length;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!this.assetDropdownOpen) {
                    const val = searchInput.value.trim();
                    const query = (val === this.selectedAssetLabel) ? '' : val;
                    this.openAssetDropdown(query);
                    return;
                }
                if (len > 0) {
                    this.assetActiveIndex = (this.assetActiveIndex + 1) % len;
                    this.updateAssetActiveOption();
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (!this.assetDropdownOpen) {
                    const val = searchInput.value.trim();
                    const query = (val === this.selectedAssetLabel) ? '' : val;
                    this.openAssetDropdown(query);
                    return;
                }
                if (len > 0) {
                    this.assetActiveIndex = (this.assetActiveIndex <= 0 ? len - 1 : this.assetActiveIndex - 1);
                    this.updateAssetActiveOption();
                }
            } else if (e.key === 'Enter') {
                if (this.assetDropdownOpen) {
                    e.preventDefault();
                    const targetOpt = this.filteredAssetOptions[this.assetActiveIndex] ||
                        (len === 1 ? this.filteredAssetOptions[0] : null);
                    if (targetOpt) {
                        this.selectAssetOption(`${targetOpt.kib}:${targetOpt.aset_id}`);
                    }
                }
            } else if (e.key === 'Escape') {
                if (this.assetDropdownOpen) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.closeAssetDropdown();
                }
            } else if (e.key === 'Tab') {
                this.closeAssetDropdown();
            }
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (searchInput.disabled) return;
                this.clearAssetSelection();
                searchInput.focus();
                this.openAssetDropdown('');
            });
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (searchInput.disabled) return;
                if (this.assetDropdownOpen) {
                    this.closeAssetDropdown();
                } else {
                    searchInput.focus();
                    const val = searchInput.value.trim();
                    const query = (val === this.selectedAssetLabel) ? '' : val;
                    this.openAssetDropdown(query);
                }
            });
        }

        dropdown.addEventListener('mousedown', (e) => {
            const optEl = e.target.closest('[data-asset-key]');
            if (!optEl) return;
            e.preventDefault();
            this.selectAssetOption(optEl.getAttribute('data-asset-key'));
        });

        document.addEventListener('click', (e) => {
            if (this.assetDropdownOpen && !combobox.contains(e.target)) {
                this.closeAssetDropdown();
            }
        });
    }

    updateAssetClearButton() {
        const searchInput = document.getElementById('monFieldAsetSearch');
        const hiddenInput = document.getElementById('monFieldAset');
        const clearBtn = document.getElementById('monFieldAsetClear');
        if (!clearBtn) return;
        const hasValue = Boolean((searchInput && searchInput.value.trim()) || (hiddenInput && hiddenInput.value));
        clearBtn.classList.toggle('visible', hasValue && !(searchInput && searchInput.disabled));
    }

    openAssetDropdown(query = '') {
        const searchInput = document.getElementById('monFieldAsetSearch');
        const dropdown = document.getElementById('monAsetDropdown');
        const combobox = document.getElementById('monAsetCombobox');
        if (!searchInput || !dropdown || !combobox || searchInput.disabled) return;

        this.assetDropdownOpen = true;
        combobox.classList.add('is-open');
        searchInput.setAttribute('aria-expanded', 'true');
        this.renderAssetDropdown(query);
    }

    closeAssetDropdown() {
        const searchInput = document.getElementById('monFieldAsetSearch');
        const dropdown = document.getElementById('monAsetDropdown');
        const combobox = document.getElementById('monAsetCombobox');
        this.assetDropdownOpen = false;
        this.assetActiveIndex = -1;
        if (combobox) combobox.classList.remove('is-open');
        if (searchInput) searchInput.setAttribute('aria-expanded', 'false');
        if (dropdown) dropdown.innerHTML = '';
    }

    renderAssetDropdown(query = '') {
        const dropdown = document.getElementById('monAsetDropdown');
        const hiddenInput = document.getElementById('monFieldAset');
        if (!dropdown) return;

        const opts = this.state.assetOptions || [];
        const q = String(query || '').trim().toLowerCase();
        const terms = q ? q.split(/\s+/).filter(Boolean) : [];

        this.filteredAssetOptions = opts.filter(a => {
            if (terms.length === 0) return true;
            const hay = `${a.kib_label} ${a.kib} ${a.nama} ${a.kode} ${a.jenis}`.toLowerCase();
            return terms.every(t => hay.includes(t));
        });

        const selectedKey = hiddenInput ? hiddenInput.value : '';
        if (this.filteredAssetOptions.length === 0) {
            this.assetActiveIndex = -1;
            dropdown.innerHTML = `<div class="mon-aset-empty">Tidak ada aset yang cocok dengan "${this.escapeHtml(query)}".</div>`;
            return;
        }

        const selectedIdx = this.filteredAssetOptions.findIndex(a => `${a.kib}:${a.aset_id}` === selectedKey);
        this.assetActiveIndex = selectedIdx >= 0 ? selectedIdx : 0;

        dropdown.innerHTML = this.filteredAssetOptions.map((a, idx) => {
            const key = `${a.kib}:${a.aset_id}`;
            const totalQty = Math.max(1, Number(a.jumlah_total ?? a.jumlah ?? 1));
            const maxQuota = this.getAssetMaxQuota(a);
            const isSelected = key === selectedKey;
            const isActive = idx === this.assetActiveIndex;
            const isFull = maxQuota === 0;
            const quotaLabel = totalQty > 1
                ? (isFull ? `Total ${totalQty} • Kuota penuh` : `Total ${totalQty} • Sisa ${maxQuota}`)
                : (isFull ? 'Sudah termonitor' : 'Tersedia 1 unit');

            return `
                <div
                    class="mon-aset-option${isSelected ? ' is-selected' : ''}${isActive ? ' is-active' : ''}${isFull ? ' is-full' : ''}"
                    role="option"
                    aria-selected="${isSelected ? 'true' : 'false'}"
                    data-asset-key="${this.escapeHtml(key)}"
                    data-idx="${idx}"
                >
                    <div class="mon-aset-option-top">
                        <span class="mon-aset-option-kib">${this.escapeHtml(a.kib_label)}</span>
                        <span class="mon-aset-option-name">${this.highlightMatch(a.nama, query)}</span>
                        <span class="mon-aset-option-code">${this.highlightMatch(a.kode, query)}</span>
                    </div>
                    <div class="mon-aset-option-meta">
                        <span>${this.highlightMatch(a.jenis || '-', query)}</span>
                        <span class="mon-aset-option-quota${isFull ? ' is-full' : ''}">${this.escapeHtml(quotaLabel)}</span>
                    </div>
                </div>
            `;
        }).join('');

        this.scrollActiveAssetOption();
    }

    updateAssetActiveOption() {
        const dropdown = document.getElementById('monAsetDropdown');
        if (!dropdown) return;
        dropdown.querySelectorAll('.mon-aset-option').forEach((el, idx) => {
            el.classList.toggle('is-active', idx === this.assetActiveIndex);
        });
        this.scrollActiveAssetOption();
    }

    scrollActiveAssetOption() {
        const dropdown = document.getElementById('monAsetDropdown');
        if (!dropdown || this.assetActiveIndex < 0) return;
        const activeEl = dropdown.querySelector(`.mon-aset-option[data-idx="${this.assetActiveIndex}"]`);
        if (activeEl) {
            activeEl.scrollIntoView({ block: 'nearest' });
        }
    }

    selectAssetOption(key) {
        const hiddenInput = document.getElementById('monFieldAset');
        const searchInput = document.getElementById('monFieldAsetSearch');
        const found = (this.state.assetOptions || []).find(a => `${a.kib}:${a.aset_id}` === key);

        if (!found) return;
        const label = this.formatAssetDisplayLabel(found);
        this.selectedAssetLabel = label;

        if (hiddenInput) hiddenInput.value = key;
        if (searchInput) {
            searchInput.value = label;
            searchInput.classList.remove('has-error');
        }
        const errEl = document.getElementById('errMon_aset_key');
        if (errEl) {
            errEl.textContent = '';
            errEl.classList.remove('show');
        }

        this.updateAssetClearButton();
        this.closeAssetDropdown();
        this.handleAssetSelectChange();
    }

    clearAssetSelection() {
        const hiddenInput = document.getElementById('monFieldAset');
        const searchInput = document.getElementById('monFieldAsetSearch');
        this.selectedAssetLabel = '';
        if (hiddenInput) hiddenInput.value = '';
        if (searchInput) searchInput.value = '';
        this.updateAssetClearButton();
        this.handleAssetSelectChange();
    }

    populateAssetDropdown(selectedKey = '') {
        const hiddenInput = document.getElementById('monFieldAset');
        const searchInput = document.getElementById('monFieldAsetSearch');
        const toggleBtn = document.getElementById('monFieldAsetToggle');
        const emptyHint = document.getElementById('monEmptyAssetHint');
        if (!hiddenInput || !searchInput) return;

        const opts = this.state.assetOptions || [];
        this.closeAssetDropdown();

        if (emptyHint) {
            const kibLink = `${this.config.kibBaseUrl}kib-b.php`;
            if (this.isClassLocation()) {
                emptyHint.innerHTML = `Belum ada aset KIB B atau KIB E terdaftar pada Data Aset. Silakan tambahkan aset terlebih dahulu di halaman <a href="${this.escapeHtml(kibLink)}">Data Aset (KIB B / KIB E)</a>.`;
            } else {
                emptyHint.innerHTML = `Belum ada aset terdaftar pada Data Aset. Silakan tambahkan aset terlebih dahulu di halaman <a href="${this.escapeHtml(kibLink)}">Data Aset (KIB A-F)</a>.`;
            }
        }

        if (opts.length === 0) {
            hiddenInput.value = '';
            searchInput.value = '';
            this.selectedAssetLabel = '';
            searchInput.placeholder = this.isClassLocation()
                ? 'Belum ada aset KIB B / KIB E terdaftar'
                : 'Belum ada aset terdaftar';
            searchInput.disabled = true;
            if (toggleBtn) toggleBtn.disabled = true;
            if (emptyHint) emptyHint.classList.add('show');
        } else {
            searchInput.disabled = false;
            if (toggleBtn) toggleBtn.disabled = false;
            searchInput.placeholder = this.isClassLocation()
                ? 'Cari aset KIB B / KIB E (nama, kode, jenis)...'
                : 'Cari aset KIB A-F (nama, kode, jenis)...';
            if (emptyHint) emptyHint.classList.remove('show');

            if (selectedKey) {
                const found = opts.find(a => `${a.kib}:${a.aset_id}` === selectedKey);
                if (found) {
                    const label = this.formatAssetDisplayLabel(found);
                    hiddenInput.value = selectedKey;
                    searchInput.value = label;
                    this.selectedAssetLabel = label;
                } else {
                    hiddenInput.value = '';
                    searchInput.value = '';
                    this.selectedAssetLabel = '';
                }
            } else {
                hiddenInput.value = '';
                searchInput.value = '';
                this.selectedAssetLabel = '';
            }
        }
        this.updateAssetClearButton();
    }

    getAssetMaxQuota(found) {
        if (!found) return null;
        const totalQty = Math.max(1, Number(found.jumlah_total ?? found.jumlah ?? 1));
        const usedTotal = Math.max(0, Number(found.jumlah_termonitor ?? 0));
        let editingOwnQty = 0;
        if (this.editingId) {
            const currentItem = this.state.items.find(d => d.id === this.editingId);
            if (currentItem && currentItem.kib === found.kib && Number(currentItem.aset_id) === Number(found.aset_id)) {
                editingOwnQty = Math.max(0, Number(currentItem.jumlah || 0));
            }
        }
        const usedOther = Math.max(0, usedTotal - editingOwnQty);
        return Math.max(0, totalQty - usedOther);
    }

    handleAssetSelectChange(preserveJumlah = false) {
        const selectEl = document.getElementById('monFieldAset');
        const kibInput = document.getElementById('monFieldKibDisplay');
        const kodeInput = document.getElementById('monFieldKodeDisplay');
        const jenisInput = document.getElementById('monFieldJenisDisplay');
        const jumlahInput = document.getElementById('monFieldJumlah');
        const jumlahHint = document.getElementById('monFieldJumlahHint');

        const val = selectEl ? selectEl.value : '';
        if (!val) {
            if (kibInput) kibInput.value = '';
            if (kodeInput) kodeInput.value = '';
            if (jenisInput) jenisInput.value = '';
            if (jumlahInput) jumlahInput.removeAttribute('max');
            if (jumlahHint) {
                jumlahHint.textContent = '';
                jumlahHint.classList.remove('show', 'is-full');
            }
            return;
        }

        const [kibId, asetIdStr] = val.split(':');
        const asetId = parseInt(asetIdStr, 10);
        const found = (this.state.assetOptions || []).find(a => a.kib === kibId && a.aset_id === asetId);
        if (!found) return;

        const totalQty = Math.max(1, Number(found.jumlah_total ?? found.jumlah ?? 1));
        const maxAllowed = this.getAssetMaxQuota(found);

        if (kibInput) kibInput.value = found.kib_label;
        if (kodeInput) kodeInput.value = found.kode;
        if (jenisInput) jenisInput.value = found.jenis;
        if (jumlahInput) {
            if (maxAllowed !== null && maxAllowed > 0) {
                jumlahInput.setAttribute('max', String(maxAllowed));
            } else {
                jumlahInput.removeAttribute('max');
            }
            if (!preserveJumlah && !this.editingId) {
                jumlahInput.value = String(maxAllowed > 0 ? maxAllowed : 1);
            }
        }
        if (jumlahHint) {
            jumlahHint.classList.add('show');
            if (maxAllowed === 0) {
                jumlahHint.classList.add('is-full');
                jumlahHint.textContent = `Total di KIB: ${totalQty} unit (kuota sudah termonitor penuh di seluruh lokasi)`;
            } else {
                jumlahHint.classList.remove('is-full');
                jumlahHint.textContent = `Total di KIB: ${totalQty} unit • Maksimal input: ${maxAllowed} unit`;
            }
        }
    }

    clearFormErrors() {
        document.querySelectorAll('#monAssetForm .kib-field-error').forEach(el => {
            el.textContent = '';
            el.classList.remove('show');
        });
        document.querySelectorAll('#monAssetForm .has-error').forEach(el => {
            el.classList.remove('has-error');
        });
    }

    showFieldError(fieldId, msg) {
        const errEl = document.getElementById(`errMon_${fieldId}`);
        const inputEl = document.getElementById(fieldId === 'aset_key' ? 'monFieldAsetSearch' :
            fieldId === 'jumlah' ? 'monFieldJumlah' :
            fieldId === 'kondisi' ? 'monFieldKondisi' :
            fieldId === 'tgl_update' ? 'monFieldTglUpdate' : '');
        if (errEl) {
            errEl.textContent = msg;
            errEl.classList.add('show');
        }
        if (inputEl) {
            inputEl.classList.add('has-error');
        }
    }

    async openAddModal() {
        if (this.isReadonlyMode()) return;

        this.editingId = null;
        this.clearFormErrors();

        const form = document.getElementById('monAssetForm');
        if (form) form.reset();

        const meta = this.getLocationMeta(this.state.penempatan);
        const titleEl = document.getElementById('monFormTitle');
        const locEl = document.getElementById('monFieldLokasiDisplay');
        const tglEl = document.getElementById('monFieldTglUpdate');
        const petugasEl = document.getElementById('monFieldPetugas');
        const jumlahEl = document.getElementById('monFieldJumlah');
        const kondisiEl = document.getElementById('monFieldKondisi');

        if (titleEl) titleEl.textContent = `Tambah Monitoring - ${meta.label}`;
        if (locEl) locEl.value = meta.label;
        if (tglEl) tglEl.value = this.getTodayIso();
        if (petugasEl) petugasEl.value = this.config.currentUserName || 'Administrator';
        if (jumlahEl) jumlahEl.value = '1';
        if (kondisiEl) kondisiEl.value = 'Baik';
        const perluEl = document.getElementById('monFieldPerluPemeliharaan');
        if (perluEl) perluEl.value = 'Tidak';
        this.syncPerluPemeliharaanState();

        await this.refreshAssetOptions();
        this.populateAssetDropdown('');
        this.handleAssetSelectChange();
        this.openModal('monModalForm');
    }

    async openEditModal(id) {
        if (this.isReadonlyMode()) return;
        const item = this.state.items.find(d => d.id === id);
        if (!item) return;

        this.editingId = id;
        this.clearFormErrors();

        const meta = this.getLocationMeta(this.state.penempatan);
        const titleEl = document.getElementById('monFormTitle');
        const locEl = document.getElementById('monFieldLokasiDisplay');
        const kibEl = document.getElementById('monFieldKibDisplay');
        const kodeEl = document.getElementById('monFieldKodeDisplay');
        const jenisEl = document.getElementById('monFieldJenisDisplay');
        const jumlahEl = document.getElementById('monFieldJumlah');
        const kondisiEl = document.getElementById('monFieldKondisi');
        const tglEl = document.getElementById('monFieldTglUpdate');
        const petugasEl = document.getElementById('monFieldPetugas');
        const catatanEl = document.getElementById('monFieldCatatan');

        if (titleEl) titleEl.textContent = `Edit Monitoring - ${meta.label}`;
        if (locEl) locEl.value = meta.label;

        await this.refreshAssetOptions();
        const key = `${item.kib}:${item.aset_id}`;
        this.populateAssetDropdown(key);
        this.handleAssetSelectChange(true);

        if (kibEl) kibEl.value = item.kib_label;
        if (kodeEl) kodeEl.value = item.kode_aset;
        if (jenisEl) jenisEl.value = item.jenis_aset;
        if (jumlahEl) jumlahEl.value = String(item.jumlah || 1);
        if (kondisiEl) kondisiEl.value = item.kondisi || 'Baik';
        if (tglEl) tglEl.value = item.tgl_update || this.getTodayIso();
        if (petugasEl) petugasEl.value = item.petugas || this.config.currentUserName || 'Administrator';
        const perluEl = document.getElementById('monFieldPerluPemeliharaan');
        if (perluEl) perluEl.value = item.perlu_pemeliharaan === 'Ya' ? 'Ya' : 'Tidak';
        this.syncPerluPemeliharaanState();
        if (catatanEl) catatanEl.value = item.catatan || '';

        this.openModal('monModalForm');
    }

    syncPerluPemeliharaanState() {
        const kondisiEl = document.getElementById('monFieldKondisi');
        const perluEl = document.getElementById('monFieldPerluPemeliharaan');
        const hintEl = document.getElementById('monFieldPerluHint');
        if (!kondisiEl || !perluEl) return;

        const kondisi = kondisiEl.value;
        const canNeedMaint = (kondisi === 'Rusak Ringan' || kondisi === 'Rusak Berat');
        if (canNeedMaint) {
            perluEl.disabled = false;
            if (hintEl) {
                hintEl.textContent = 'Pilih "Ya" agar aset otomatis masuk antrean Pemeliharaan.';
            }
        } else {
            perluEl.value = 'Tidak';
            perluEl.disabled = true;
            if (hintEl) {
                hintEl.textContent = 'Aktif bila Kondisi Rusak Ringan atau Rusak Berat.';
            }
        }
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        if (this.isReadonlyMode()) return;

        this.clearFormErrors();

        const asetKey = document.getElementById('monFieldAset')?.value || '';
        const jumlahVal = parseInt(document.getElementById('monFieldJumlah')?.value || '0', 10);
        const kondisiVal = document.getElementById('monFieldKondisi')?.value || '';
        const tglVal = document.getElementById('monFieldTglUpdate')?.value || '';
        const petugasVal = (document.getElementById('monFieldPetugas')?.value || '').trim();
        const perluVal = (kondisiVal === 'Rusak Ringan' || kondisiVal === 'Rusak Berat')
            ? (document.getElementById('monFieldPerluPemeliharaan')?.value === 'Ya' ? 'Ya' : 'Tidak')
            : 'Tidak';
        const catatanVal = (document.getElementById('monFieldCatatan')?.value || '').trim();

        let valid = true;
        if (!asetKey || !asetKey.includes(':')) {
            this.showFieldError('aset_key', 'Nama Aset wajib dipilih.');
            valid = false;
        }
        if (isNaN(jumlahVal) || jumlahVal < 1) {
            this.showFieldError('jumlah', 'Jumlah minimal 1.');
            valid = false;
        } else if (asetKey && asetKey.includes(':')) {
            const [chkKib, chkIdStr] = asetKey.split(':');
            const foundOpt = (this.state.assetOptions || []).find(a => a.kib === chkKib && a.aset_id === parseInt(chkIdStr, 10));
            const maxAllowed = this.getAssetMaxQuota(foundOpt);
            if (maxAllowed !== null && jumlahVal > maxAllowed) {
                this.showFieldError('jumlah', maxAllowed === 0
                    ? 'Kuota unit aset ini sudah termonitor penuh di seluruh lokasi.'
                    : `Jumlah melebihi sisa kuota aset (maksimal ${maxAllowed} unit).`);
                valid = false;
            }
        }
        if (!kondisiVal) {
            this.showFieldError('kondisi', 'Kondisi wajib dipilih.');
            valid = false;
        }
        if (!tglVal) {
            this.showFieldError('tgl_update', 'Tanggal Update wajib diisi.');
            valid = false;
        }
        if (!valid) return;

        const [kibId, asetId] = asetKey.split(':');
        const submitBtn = document.getElementById('btnMonSubmitForm');
        const origHtml = submitBtn ? submitBtn.innerHTML : 'Submit';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Menyimpan...</span>';
        }

        const fd = new FormData();
        fd.append('action', 'save');
        fd.append('penempatan', this.state.penempatan);
        if (this.editingId) {
            fd.append('id', String(this.editingId));
        }
        fd.append('kib', kibId);
        fd.append('aset_id', asetId);
        fd.append('jumlah', String(jumlahVal));
        fd.append('kondisi', kondisiVal);
        fd.append('perlu_pemeliharaan', perluVal);
        fd.append('tgl_update', tglVal);
        fd.append('petugas', petugasVal);
        fd.append('catatan', catatanVal);
        fd.append('csrf_token', this.getCsrfToken());

        try {
            const resp = await fetch(this.config.apiUrl, {
                method: 'POST',
                body: fd,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const res = await resp.json();
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origHtml;
            }

            if (res && res.success) {
                this.closeModal('monModalForm');
                await this.loadLocationData(this.state.penempatan);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: this.editingId ? 'Monitoring Diperbarui!' : 'Monitoring Ditambahkan!',
                        text: res.message || 'Data monitoring berhasil disimpan.',
                        timer: 1600,
                        showConfirmButton: false
                    });
                }
            } else {
                if (res && res.data && res.data.field) {
                    this.showFieldError(res.data.field, res.message || 'Input tidak valid.');
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan',
                        text: (res && res.message) ? res.message : 'Terjadi kesalahan saat menyimpan data monitoring.',
                        confirmButtonColor: '#852019'
                    });
                }
            }
        } catch (_) {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origHtml;
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gangguan Jaringan',
                    text: 'Tidak dapat terhubung ke server database.',
                    confirmButtonColor: '#852019'
                });
            }
        }
    }

    showDetail(id) {
        const item = this.state.items.find(d => d.id === id);
        if (!item || typeof Swal === 'undefined') return;

        const fields = [
            ['Lokasi Penempatan', item.penempatan_label],
            ['Kategori KIB', item.kib_label],
            ['Kode Aset', item.kode_aset],
            ['Nama Aset', item.nama_aset],
            ['Jenis Aset', item.jenis_aset || '-'],
            ['Jumlah Unit', String(item.jumlah)],
            ['Kondisi', item.kondisi],
            ['Perlu Pemeliharaan?', item.perlu_pemeliharaan === 'Ya' ? 'Ya' : 'Tidak'],
            ['Tanggal Update', this.formatDateId(item.tgl_update)],
            ['Petugas', item.petugas || '-'],
            ['Catatan', item.catatan || '-']
        ];

        const rowsHtml = fields.map(([label, val]) => `
            <div style="border-bottom: 1px solid #e2e8f0; padding: 8px 0; display: flex; justify-content: space-between; align-items: flex-start; gap: 12px;">
                <span style="color: #64748b; font-weight: 500; font-size: 13px; flex-shrink: 0;">${this.escapeHtml(label)}</span>
                <strong style="color: #1e293b; font-size: 13px; text-align: right; word-break: break-word;">${this.escapeHtml(val)}</strong>
            </div>
        `).join('');

        Swal.fire({
            title: `Detail Monitoring - ${item.penempatan_label}`,
            html: `<div style="text-align: left; padding: 4px 8px; max-height: 420px; overflow-y: auto;">${rowsHtml}</div>`,
            confirmButtonColor: '#852019',
            confirmButtonText: 'Tutup'
        });
    }

    deleteItem(id) {
        if (this.isReadonlyMode()) return;
        const item = this.state.items.find(d => d.id === id);
        if (!item || typeof Swal === 'undefined') return;

        Swal.fire({
            title: 'Hapus Data Monitoring?',
            html: `Apakah Anda yakin ingin menghapus catatan monitoring <strong>${this.escapeHtml(item.nama_aset)}</strong> (${this.escapeHtml(item.kode_aset)})?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then(async (result) => {
            if (!result.isConfirmed) return;

            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('penempatan', this.state.penempatan);
            fd.append('id', String(id));
            fd.append('csrf_token', this.getCsrfToken());

            try {
                const resp = await fetch(this.config.apiUrl, {
                    method: 'POST',
                    body: fd,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const res = await resp.json();
                if (res && res.success) {
                    await this.loadLocationData(this.state.penempatan);
                    Swal.fire({
                        icon: 'success',
                        title: 'Terhapus!',
                        text: res.message || 'Data monitoring berhasil dihapus.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menghapus',
                        text: (res && res.message) ? res.message : 'Terjadi kesalahan saat menghapus data.',
                        confirmButtonColor: '#852019'
                    });
                }
            } catch (_) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gangguan Jaringan',
                    text: 'Tidak dapat terhubung ke server database.',
                    confirmButtonColor: '#852019'
                });
            }
        });
    }

    openModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    closeModal(id) {
        const modal = document.getElementById(id);
        if (!modal) return;
        if (id === 'monModalForm') {
            this.closeAssetDropdown();
        }
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        if (document.querySelectorAll('.kib-modal-overlay.show').length === 0) {
            document.body.style.overflow = '';
        }
    }
}
