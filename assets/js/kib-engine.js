/**
 * KIB Engine - Sistem Informasi Inventaris Aset SDN Budi Karya
 * Unified, reusable table and modal controller for KIB A, B, C, D, E, F
 */

class KibEngine {
    constructor(config) {
        this.config = Object.assign({
            kibId: 'kib-a',
            kibName: 'KIB A',
            kibTitle: 'DATA ASET - KIB A',
            subtitle: '',
            hasJenis: false,
            jenisLabel: 'Jenis Aset',
            defaultRange: 'all',
            columns: [],
            headerRows: null, // For grouped headers in KIB E & F
            formFields: [],
            initialData: []
        }, config);

        // State Per Halaman (Data dimuat langsung dari database MySQL)
        this.state = {
            keyword: '',
            range: this.config.defaultRange || 'all',
            currentPage: 1,
            filters: {
                keyword: '',
                jenis: '',
                tahun: '',
                sumber: ''
            },
            data: Array.isArray(this.config.initialData) ? JSON.parse(JSON.stringify(this.config.initialData)) : []
        };

        this.editingId = null;
        this._selectedFiles = {};

        this.init();
    }

    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }

    setup() {
        this.renderHeaderAndModals();
        this.bindEvents();
        this.updateFilterBadge();
        this.render();
        this.loadDataFromDatabase();
    }

    // Helper deteksi path endpoint API AJAX KIB
    getApiUrl() {
        const path = window.location.pathname;
        if (path.indexOf('/pages/data-aset/') !== -1) {
            return '../../includes/ajax-kib.php';
        }
        if (path.indexOf('/pages/') !== -1) {
            return '../includes/ajax-kib.php';
        }
        return 'includes/ajax-kib.php';
    }

    // Helper membaca CSRF token
    formatPenempatan(val) {
        const map = {
            'kelas-1': 'Kelas 1',
            'kelas-2': 'Kelas 2',
            'kelas-3': 'Kelas 3',
            'kelas-4': 'Kelas 4',
            'kelas-5': 'Kelas 5',
            'kelas-6': 'Kelas 6',
            'umum': 'Aset Umum'
        };
        const key = String(val || 'umum').trim().toLowerCase();
        return map[key] || val || 'Aset Umum';
    }

    getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && meta.content) return meta.content;
        const input = document.querySelector('input[name="csrf_token"]');
        if (input && input.value) return input.value;
        return '';
    }

    // Memuat data aset secara real-time dari database MySQL
    async loadDataFromDatabase() {
        try {
            const url = `${this.getApiUrl()}?action=list&kib=${encodeURIComponent(this.config.kibId)}`;
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const res = await response.json();
            if (res && res.success && Array.isArray(res.data.data)) {
                this.state.data = res.data.data;
                this.render();
            }
        } catch (e) {
            console.warn('Gagal memuat data KIB dari server database:', e);
        }
    }

    // Helper: Escape HTML
    escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Helper: Highlight match
    highlightMatch(fullText, query) {
        const safeText = this.escapeHtml(fullText);
        if (!query || query.trim() === '') {
            return safeText;
        }

        const trimmedQuery = query.trim();
        const escapedQuery = trimmedQuery.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp('(' + escapedQuery + ')', 'gi');

        return safeText.replace(regex, '<mark class="search-highlight">$1</mark>');
    }

    // Helper: Debounce
    debounce(fn, delay) {
        let timer = null;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    // Ekstrak Tahun dari Kode Aset atau Field Tahun
    extractYear(item) {
        if (item.tahun) return String(item.tahun);
        if (item.tahun_perolehan) return String(item.tahun_perolehan);
        if (item.spesifikasi_tahun) return String(item.spesifikasi_tahun);

        const kode = String(item.kode || '');
        const match = kode.match(/\b(19\d\d|20\d\d)\b/);
        return match ? match[1] : '';
    }

    // Populate Dropdown Modal Filter
    populateFilterDropdowns() {
        // 1. Jenis Dropdown (jika hasJenis aktif)
        if (this.config.hasJenis) {
            const selectJenis = document.getElementById('kibFilterJenis');
            if (selectJenis) {
                const currentVal = this.state.filters.jenis;
                const uniqueJenis = [...new Set(this.state.data.map(item => item.jenis).filter(Boolean))].sort();

                selectJenis.innerHTML = `<option value="">Semua ${this.config.jenisLabel}</option>` +
                    uniqueJenis.map(val => `<option value="${this.escapeHtml(val)}" ${val === currentVal ? 'selected' : ''}>${this.escapeHtml(val)}</option>`).join('');
            }
        }

        // 2. Tahun Dropdown (diambil otomatis dari data)
        const selectTahun = document.getElementById('kibFilterTahun');
        if (selectTahun) {
            const currentTahun = this.state.filters.tahun;
            const years = [...new Set(this.state.data.map(item => this.extractYear(item)).filter(Boolean))].sort((a, b) => b.localeCompare(a));

            selectTahun.innerHTML = '<option value="">Semua Tahun</option>' +
                years.map(y => `<option value="${y}" ${y === currentTahun ? 'selected' : ''}>${y}</option>`).join('');
        }

        // 3. Sumber Dropdown (BOS, APBD, Hibah, DAK Fisik, Swadaya, dll.)
        const selectSumber = document.getElementById('kibFilterSumber');
        if (selectSumber) {
            const currentSumber = this.state.filters.sumber;
            const sumberList = [...new Set(this.state.data.map(item => item.sumber).filter(Boolean))].sort();

            // Default fallback options jika data sedikit
            const allSumber = [...new Set([...sumberList, 'BOS', 'APBD', 'Hibah', 'DAK Fisik', 'Swadaya'])];

            selectSumber.innerHTML = '<option value="">Semua Sumber</option>' +
                allSumber.map(s => `<option value="${this.escapeHtml(s)}" ${s === currentSumber ? 'selected' : ''}>${this.escapeHtml(s)}</option>`).join('');
        }
    }

    // Render Markup Modal Filter Data dan Modal Tambah/Edit Aset ke DOM
    renderHeaderAndModals() {
        // Modal Container Wrapper di dalam body
        let modalHost = document.getElementById('kibModalHost');
        if (!modalHost) {
            modalHost = document.createElement('div');
            modalHost.id = 'kibModalHost';
            document.body.appendChild(modalHost);
        }

        // A. Modal Filter Data
        const jenisFieldHtml = this.config.hasJenis ? `
            <div class="kib-form-group">
                <label for="kibFilterJenis" class="kib-label">${this.config.jenisLabel}</label>
                <select id="kibFilterJenis" class="kib-select">
                    <option value="">Semua ${this.config.jenisLabel}</option>
                </select>
            </div>
        ` : '';

        // B. Dynamic Fields untuk Modal Tambah / Edit Aset (Grid 12 Kolom)
        const formFieldsHtml = this.config.formFields.map(field => {
            const span = Number(field.span) > 0 ? Number(field.span) : 12;
            const rowSpanClass = field.rowSpan === 2 ? ' kib-form-group--row-span-2' : '';
            const groupStyle = `--field-span: ${span};`;
            const isAutoReadonly = this.isAutoReadonlyField(field);
            const reqAttr = (!isAutoReadonly && field.required) ? 'required' : '';
            const reqLabelClass = (!isAutoReadonly && field.required) ? ' required' : '';
            const labelHtml = `<label for="formField_${field.name}" class="kib-label${reqLabelClass}">${this.escapeHtml(field.label)}</label>`;

            if (field.type === 'select') {
                const optionsHtml = (field.options || []).map(opt => {
                    const optValue = (opt && typeof opt === 'object') ? opt.value : opt;
                    const optLabel = (opt && typeof opt === 'object') ? opt.label : opt;
                    const selected = (field.default && field.default === optValue) ? 'selected' : '';
                    return `<option value="${this.escapeHtml(optValue)}" ${selected}>${this.escapeHtml(optLabel)}</option>`;
                }).join('');
                return `
                    <div class="kib-form-group${rowSpanClass}" style="${groupStyle}">
                        ${labelHtml}
                        <div class="kib-select-wrapper">
                            <select id="formField_${field.name}" name="${field.name}" class="kib-select" ${reqAttr}>
                                <option value="">Pilih ${this.escapeHtml(field.label)}</option>
                                ${optionsHtml}
                            </select>
                            <i class="ph-bold ph-caret-down kib-select-chevron" aria-hidden="true"></i>
                        </div>
                        <span class="kib-field-error" id="err_${field.name}"></span>
                    </div>
                `;
            }

            if (field.type === 'textarea') {
                return `
                    <div class="kib-form-group${rowSpanClass}" style="${groupStyle}">
                        ${labelHtml}
                        <textarea
                            id="formField_${field.name}"
                            name="${field.name}"
                            class="kib-textarea"
                            placeholder="${this.escapeHtml(field.placeholder || '')}"
                            ${reqAttr}
                        ></textarea>
                        <span class="kib-field-error" id="err_${field.name}"></span>
                    </div>
                `;
            }

            if (field.type === 'file') {
                return `
                    <div class="kib-form-group${rowSpanClass}" style="${groupStyle}">
                        ${labelHtml}
                        <div class="kib-upload-wrapper">
                            <input
                                type="file"
                                id="formField_${field.name}"
                                name="${field.name}"
                                class="kib-upload-input-hidden"
                                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                            >
                            <button
                                type="button"
                                class="kib-upload-btn"
                                id="btnUpload_${field.name}"
                                data-upload-target="${field.name}"
                            >
                                <i class="ph-bold ph-upload-simple kib-upload-btn-icon" aria-hidden="true"></i>
                                <span class="kib-upload-btn-text" id="textUpload_${field.name}">Upload File +</span>
                            </button>
                            <span id="hint_${field.name}" class="kib-upload-hint">PDF, JPG, PNG (Maks. 5MB, gambar lebih otomatis dikompres)</span>
                        </div>
                        <span class="kib-field-error" id="err_${field.name}"></span>
                    </div>
                `;
            }

            if (field.type === 'date') {
                return `
                    <div class="kib-form-group${rowSpanClass}" style="${groupStyle}">
                        ${labelHtml}
                        <div class="kib-date-wrapper">
                            <input
                                type="text"
                                id="formField_${field.name}"
                                name="${field.name}"
                                class="kib-input kib-input--date"
                                placeholder="${this.escapeHtml(field.placeholder || 'dd/mm/yy')}"
                                inputmode="numeric"
                                maxlength="8"
                                ${reqAttr}
                            >
                            <button type="button" class="kib-date-picker-btn" tabindex="-1" aria-label="Pilih tanggal">
                                <i class="ph-bold ph-calendar-blank"></i>
                            </button>
                            <input
                                type="date"
                                id="datePicker_${field.name}"
                                class="kib-date-native-hidden"
                                aria-label="Kalender ${this.escapeHtml(field.label)}"
                                tabindex="-1"
                            >
                        </div>
                        <span class="kib-field-error" id="err_${field.name}"></span>
                    </div>
                `;
            }

            const readonlyAttrs = isAutoReadonly ? 'readonly aria-readonly="true" tabindex="-1"' : '';
            const extraClasses = [
                isAutoReadonly ? 'kib-input--readonly' : '',
                field.narrow ? 'kib-input--narrow' : '',
                field.type === 'currency' ? 'kib-input--currency' : ''
            ].filter(Boolean).join(' ');

            let inputType = 'text';
            let extraAttrs = '';
            if (field.type === 'number') {
                inputType = 'number';
                const minVal = field.min !== undefined ? field.min : 1;
                const defaultVal = field.default !== undefined ? field.default : 1;
                extraAttrs = `min="${minVal}" step="1" value="${defaultVal}" inputmode="numeric"`;
            } else if (field.type === 'currency') {
                extraAttrs = 'inputmode="numeric"';
            } else if (field.name === 'tahun' || field.name === 'spesifikasi_tahun') {
                extraAttrs = 'inputmode="numeric" maxlength="4"';
            }

            const placeholderText = isAutoReadonly
                ? (field.placeholder || 'Otomatis')
                : (field.placeholder || '');

            return `
                <div class="kib-form-group${rowSpanClass}" style="${groupStyle}">
                    ${labelHtml}
                    <input
                        type="${inputType}"
                        id="formField_${field.name}"
                        name="${field.name}"
                        class="kib-input${extraClasses ? ' ' + extraClasses : ''}"
                        placeholder="${this.escapeHtml(placeholderText)}"
                        ${readonlyAttrs}
                        ${extraAttrs}
                        ${reqAttr}
                    >
                    <span class="kib-field-error" id="err_${field.name}"></span>
                </div>
            `;
        }).join('');

        modalHost.innerHTML = `
            <!-- Modal Filter Data (Sesuai Referensi Gambar 7) -->
            <div class="kib-modal-overlay" id="kibModalFilter" aria-hidden="true">
                <div class="kib-modal-dialog kib-modal-dialog--filter" role="dialog" aria-modal="true" aria-labelledby="kibFilterTitle">
                    <div class="kib-modal-header">
                        <div class="kib-modal-header-left">
                            <span class="kib-modal-icon-green">
                                <i class="ph-bold ph-faders"></i>
                            </span>
                            <h3 class="kib-modal-title" id="kibFilterTitle">Filter Data</h3>
                        </div>
                        <button type="button" class="kib-modal-close" data-modal-close aria-label="Tutup">
                            <i class="ph-bold ph-x"></i>
                        </button>
                    </div>

                    <div class="kib-modal-body">
                        <div class="kib-form-group">
                            <label for="kibFilterKeyword" class="kib-label">Kata Kunci</label>
                            <div class="kib-input-icon-wrapper">
                                <i class="ph-bold ph-magnifying-glass kib-input-icon"></i>
                                <input
                                    type="text"
                                    id="kibFilterKeyword"
                                    class="kib-input kib-input--has-icon"
                                    placeholder="Masukkan kata kunci ..."
                                    autocomplete="off"
                                >
                            </div>
                        </div>

                        ${jenisFieldHtml}

                        <div class="kib-form-group">
                            <label for="kibFilterTahun" class="kib-label">Tahun Perolehan</label>
                            <select id="kibFilterTahun" class="kib-select">
                                <option value="">Semua Tahun</option>
                            </select>
                        </div>

                        <div class="kib-form-group">
                            <label for="kibFilterSumber" class="kib-label">Sumber</label>
                            <select id="kibFilterSumber" class="kib-select">
                                <option value="">Semua Sumber</option>
                            </select>
                        </div>
                    </div>

                    <div class="kib-modal-footer kib-modal-footer--space-between">
                        <button type="button" class="btn-kib-modal-reset" id="btnKibResetFilter">
                            <i class="ph-bold ph-arrows-counter-clockwise"></i>
                            <span>Reset</span>
                        </button>
                        <button type="button" class="btn-kib-modal-apply" id="btnKibApplyFilter">
                            Terapkan
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Tambah / Edit Aset -->
            <div class="kib-modal-overlay" id="kibModalAssetForm" aria-hidden="true">
                <div class="kib-modal-dialog kib-modal-dialog--form" role="dialog" aria-modal="true" aria-labelledby="kibAssetFormTitle">
                    <div class="kib-modal-header">
                        <div class="kib-modal-header-left">
                            <span class="kib-modal-icon-maroon" aria-hidden="true">
                                <i class="ph-bold ph-archive-box"></i>
                            </span>
                            <h3 class="kib-modal-title" id="kibAssetFormTitle">Tambah Aset ${this.getShortKibTitle()}</h3>
                        </div>
                        <button type="button" class="kib-modal-close" data-modal-close aria-label="Tutup">
                            <i class="ph-bold ph-x"></i>
                        </button>
                    </div>

                    <form id="kibAssetForm" novalidate autocomplete="off">
                        <input type="hidden" id="formAssetId" value="">
                        <div class="kib-modal-body">
                            <div class="kib-form-grid">
                                ${formFieldsHtml}
                            </div>
                            <div class="kib-modal-submit-row">
                                <button type="submit" class="btn-kib-modal-submit" id="btnKibSubmitAsset">
                                    Submit
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal Preview Lampiran Dokumen -->
            <div class="kib-modal-overlay" id="kibModalPreview" aria-hidden="true">
                <div class="kib-modal-dialog kib-modal-dialog--preview" role="dialog" aria-modal="true" aria-labelledby="kibPreviewTitle">
                    <div class="kib-modal-header">
                        <div class="kib-modal-header-left">
                            <span class="kib-modal-icon-maroon" aria-hidden="true">
                                <i class="ph-bold ph-file-pdf" id="kibPreviewHeaderIcon"></i>
                            </span>
                            <div class="kib-preview-title-wrap">
                                <h3 class="kib-modal-title" id="kibPreviewTitle">Pratinjau Lampiran</h3>
                                <p class="kib-preview-filename" id="kibPreviewFilename"></p>
                            </div>
                        </div>
                        <div class="kib-preview-header-actions">
                            <a href="#" target="_blank" rel="noopener noreferrer" class="btn-kib-preview-tab" id="btnKibPreviewNewTab" style="display:none;">
                                <i class="ph-bold ph-arrow-square-out"></i>
                                <span>Tab Baru</span>
                            </a>
                            <button type="button" class="btn-kib-preview-download" id="btnKibPreviewDownload" style="display:none;">
                                <i class="ph-bold ph-download-simple"></i>
                                <span>Unduh</span>
                            </button>
                            <button type="button" class="kib-modal-close" data-modal-close aria-label="Tutup">
                                <i class="ph-bold ph-x"></i>
                            </button>
                        </div>
                    </div>
                    <div class="kib-modal-body kib-preview-body" id="kibPreviewBody"></div>
                </div>
            </div>
        `;
    }

    // Modal Visibility Helpers
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
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');

        if (id === 'kibModalPreview') {
            const bodyEl = document.getElementById('kibPreviewBody');
            if (bodyEl) bodyEl.innerHTML = '';
            if (this._activePreviewBlobUrl) {
                URL.revokeObjectURL(this._activePreviewBlobUrl);
                this._activePreviewBlobUrl = null;
            }
        }

        const openModals = document.querySelectorAll('.kib-modal-overlay.show');
        if (openModals.length === 0) {
            document.body.style.overflow = '';
        }
    }

    // Update Badge Filter Aktif
    updateFilterBadge() {
        const btnFilter = document.getElementById('btnFilter');
        if (!btnFilter) return;

        const hasFilter = Boolean(
            this.state.filters.keyword.trim() !== '' ||
            this.state.filters.jenis !== '' ||
            this.state.filters.tahun !== '' ||
            this.state.filters.sumber !== ''
        );

        if (hasFilter) {
            btnFilter.classList.add('has-filter');
        } else {
            btnFilter.classList.remove('has-filter');
        }
    }

    // Bind Event Listeners
    bindEvents() {
        const searchInput = document.getElementById('kibSearchInput');
        const clearBtn = document.getElementById('kibSearchClear');
        const rangeOptions = document.getElementById('kibRangeOptions');
        const btnFilter = document.getElementById('btnFilter');
        const btnAdd = document.getElementById('btnAddAsset');
        const btnApplyFilter = document.getElementById('btnKibApplyFilter');
        const btnResetFilter = document.getElementById('btnKibResetFilter');
        const assetForm = document.getElementById('kibAssetForm');

        // Search Input dengan Debounce 200ms
        if (searchInput) {
            const handleSearch = this.debounce((val) => {
                this.state.keyword = val;
                this.state.currentPage = 1;
                this.render();
            }, 200);

            searchInput.addEventListener('input', (e) => handleSearch(e.target.value));

            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    searchInput.value = '';
                    this.state.keyword = '';
                    this.state.currentPage = 1;
                    this.render();
                }
            });
        }

        // Clear Search
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.focus();
                }
                this.state.keyword = '';
                this.state.currentPage = 1;
                this.render();
            });
        }

        // Sinkronisasi data real-time saat server merespon
        window.addEventListener('kib:synced', (e) => {
            if (e.detail && e.detail.datasets && e.detail.datasets[this.config.kibId]) {
                this.state.data = JSON.parse(JSON.stringify(e.detail.datasets[this.config.kibId]));
                this.render();
            }
        });

        // Range Selector
        if (rangeOptions) {
            rangeOptions.addEventListener('click', (e) => {
                const btn = e.target.closest('.range-btn');
                if (!btn) return;

                const range = btn.getAttribute('data-range');
                if (!range) return;

                rangeOptions.querySelectorAll('.range-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                this.state.range = range;
                this.state.currentPage = 1;
                this.render();
            });
        }

        // Buka Modal Filter
        if (btnFilter) {
            btnFilter.addEventListener('click', () => {
                this.populateFilterDropdowns();

                const kw = document.getElementById('kibFilterKeyword');
                const jenis = document.getElementById('kibFilterJenis');
                const th = document.getElementById('kibFilterTahun');
                const sm = document.getElementById('kibFilterSumber');

                if (kw) kw.value = this.state.filters.keyword;
                if (jenis) jenis.value = this.state.filters.jenis;
                if (th) th.value = this.state.filters.tahun;
                if (sm) sm.value = this.state.filters.sumber;

                this.openModal('kibModalFilter');
            });
        }

        // Apply Filter
        if (btnApplyFilter) {
            btnApplyFilter.addEventListener('click', () => {
                const kw = document.getElementById('kibFilterKeyword');
                const jenis = document.getElementById('kibFilterJenis');
                const th = document.getElementById('kibFilterTahun');
                const sm = document.getElementById('kibFilterSumber');

                this.state.filters.keyword = kw ? kw.value.trim() : '';
                this.state.filters.jenis = jenis ? jenis.value : '';
                this.state.filters.tahun = th ? th.value : '';
                this.state.filters.sumber = sm ? sm.value : '';

                this.updateFilterBadge();
                this.state.currentPage = 1;
                this.closeModal('kibModalFilter');
                this.render();
            });
        }

        // Reset Filter
        if (btnResetFilter) {
            btnResetFilter.addEventListener('click', () => {
                const kw = document.getElementById('kibFilterKeyword');
                const jenis = document.getElementById('kibFilterJenis');
                const th = document.getElementById('kibFilterTahun');
                const sm = document.getElementById('kibFilterSumber');

                if (kw) kw.value = '';
                if (jenis) jenis.value = '';
                if (th) th.value = '';
                if (sm) sm.value = '';

                this.state.filters = {
                    keyword: '',
                    jenis: '',
                    tahun: '',
                    sumber: ''
                };

                this.updateFilterBadge();
                this.state.currentPage = 1;
                this.closeModal('kibModalFilter');
                this.render();
            });
        }

        // Buka Modal Tambah Aset
        if (btnAdd) {
            btnAdd.addEventListener('click', () => this.openAddModal());
        }

        // Bind Currency Formatting, Computed Fields, Date Picker & Upload Button
        this.bindDynamicFormBehaviors();

        // Pratinjau Kode Aset & No Registrasi saat Tahun Perolehan (atau Tanggal Mulai di KIB F) berubah
        const yearFieldEl = document.getElementById(`formField_${this.getYearSourceFieldName()}`);
        if (yearFieldEl) {
            const debouncedPreview = this.debounce(() => this.handleYearPreviewChange(false), 200);
            yearFieldEl.addEventListener('input', debouncedPreview);
            yearFieldEl.addEventListener('change', () => this.handleYearPreviewChange(true));
        }

        // Form Submit Tambah/Edit Aset
        if (assetForm) {
            assetForm.addEventListener('submit', (e) => this.handleAssetFormSubmit(e));
        }

        // Global Modal Close (Click overlay & [data-modal-close])
        document.addEventListener('click', (e) => {
            const closeBtn = e.target.closest('[data-modal-close]');
            if (closeBtn) {
                const overlay = closeBtn.closest('.kib-modal-overlay');
                if (overlay) this.closeModal(overlay.id);
                return;
            }

            if (e.target.classList.contains('kib-modal-overlay')) {
                this.closeModal(e.target.id);
            }
        });

        // Global Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const openModalEl = document.querySelector('.kib-modal-overlay.show');
                if (openModalEl) this.closeModal(openModalEl.id);
            }
        });
    }

    getShortKibTitle() {
        const match = String(this.config.kibId || '').match(/kib-([a-f])/i);
        return match ? `KIB ${match[1].toUpperCase()}` : (this.config.kibName || 'KIB');
    }

    getYearSourceFieldName() {
        return this.config.kibId === 'kib-f' ? 'tgl_mulai' : 'tahun';
    }

    isAutoReadonlyField(field) {
        return Boolean(field.readonly || field.disabled || field.name === 'kode' || field.name === 'no_reg');
    }

    parsePureDigits(val) {
        if (val === null || val === undefined) return '';
        const digits = String(val).replace(/[^\d]/g, '');
        return digits.replace(/^0+(?=\d)/, '');
    }

    formatThousands(val) {
        const digits = this.parsePureDigits(val);
        if (!digits) return '';
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    formatIsoToDisplayDate(isoStr) {
        const m = String(isoStr || '').trim().match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!m) return '';
        return `${m[3]}/${m[2]}/${m[1].slice(-2)}`;
    }

    parseDisplayDateToIso(val) {
        const str = String(val || '').trim();
        if (!str) return '';

        const isoMatch = str.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (isoMatch) return str;

        const dmyMatch = str.match(/^(\d{1,2})\/(\d{1,2})\/(\d{2}|\d{4})$/);
        if (dmyMatch) {
            const dd = dmyMatch[1].padStart(2, '0');
            const mm = dmyMatch[2].padStart(2, '0');
            let yyyy = dmyMatch[3];
            if (yyyy.length === 2) {
                const yy = parseInt(yyyy, 10);
                const currYY = new Date().getFullYear() % 100;
                yyyy = (yy <= currYY + 10 ? 2000 + yy : 1900 + yy).toString();
            }
            const dNum = parseInt(dd, 10);
            const mNum = parseInt(mm, 10);
            if (mNum >= 1 && mNum <= 12 && dNum >= 1 && dNum <= 31) {
                return `${yyyy}-${mm}-${dd}`;
            }
        }

        const bulanMap = {
            januari: '01', jan: '01', februari: '02', feb: '02', maret: '03', mar: '03',
            april: '04', apr: '04', mei: '05', juni: '06', jun: '06',
            juli: '07', jul: '07', agustus: '08', agu: '08', agt: '08',
            september: '09', sep: '09', oktober: '10', okt: '10',
            november: '11', nov: '11', desember: '12', des: '12'
        };
        const indoMatch = str.toLowerCase().match(/(\d{1,2})\s+([a-z]+)\s+(\d{4})/);
        if (indoMatch && bulanMap[indoMatch[2]]) {
            return `${indoMatch[3]}-${bulanMap[indoMatch[2]]}-${indoMatch[1].padStart(2, '0')}`;
        }

        return '';
    }

    formatAnyDateToDisplay(val) {
        const str = String(val || '').trim();
        if (!str) return '';
        if (/^\d{2}\/\d{2}\/\d{2}$/.test(str)) return str;
        const iso = this.parseDisplayDateToIso(str);
        return iso ? this.formatIsoToDisplayDate(iso) : str;
    }

    bindDynamicFormBehaviors() {
        this.config.formFields.forEach(field => {
            const el = document.getElementById(`formField_${field.name}`);
            if (!el) return;

            if (field.type === 'currency' && !this.isAutoReadonlyField(field)) {
                el.addEventListener('input', () => {
                    const formatted = this.formatThousands(el.value);
                    el.value = formatted;
                    this.clearSingleFieldError(field.name);
                    this.updateComputedFields();
                });
            }

            if (field.type === 'number') {
                el.addEventListener('input', () => {
                    this.clearSingleFieldError(field.name);
                    this.updateComputedFields();
                });
                el.addEventListener('change', () => {
                    const minVal = field.min !== undefined ? Number(field.min) : 1;
                    if (!el.value || Number(el.value) < minVal) {
                        el.value = String(minVal);
                    }
                    this.updateComputedFields();
                });
            }

            if (field.type === 'date') {
                const pickerEl = document.getElementById(`datePicker_${field.name}`);
                const triggerBtn = el.parentElement ? el.parentElement.querySelector('.kib-date-picker-btn') : null;

                el.addEventListener('input', () => {
                    let digits = el.value.replace(/\D/g, '').slice(0, 6);
                    if (digits.length > 4) {
                        el.value = `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4)}`;
                    } else if (digits.length > 2) {
                        el.value = `${digits.slice(0, 2)}/${digits.slice(2)}`;
                    } else {
                        el.value = digits;
                    }
                    const iso = this.parseDisplayDateToIso(el.value);
                    if (pickerEl && iso) pickerEl.value = iso;
                    this.clearSingleFieldError(field.name);
                });

                if (pickerEl) {
                    pickerEl.addEventListener('change', () => {
                        if (pickerEl.value) {
                            el.value = this.formatIsoToDisplayDate(pickerEl.value);
                            el.dispatchEvent(new Event('input', { bubbles: true }));
                            el.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });
                }

                if (triggerBtn && pickerEl) {
                    triggerBtn.addEventListener('click', () => {
                        if (typeof pickerEl.showPicker === 'function') {
                            pickerEl.showPicker();
                        } else {
                            pickerEl.click();
                        }
                    });
                }
            }

            if (field.type === 'file') {
                const uploadBtn = document.getElementById(`btnUpload_${field.name}`);
                if (uploadBtn) {
                    uploadBtn.addEventListener('click', () => el.click());
                }
                el.addEventListener('change', () => this.handleFileSelection(field, el));
            }

            if (!['currency', 'number', 'date', 'file'].includes(field.type || 'text')) {
                el.addEventListener('input', () => this.clearSingleFieldError(field.name));
                el.addEventListener('change', () => this.clearSingleFieldError(field.name));
            }
        });
    }

    clearSingleFieldError(fieldName) {
        const el = document.getElementById(`formField_${fieldName}`);
        const err = document.getElementById(`err_${fieldName}`);
        const uploadBtn = document.getElementById(`btnUpload_${fieldName}`);
        if (el) el.classList.remove('has-error');
        if (uploadBtn) uploadBtn.classList.remove('has-error');
        if (err) {
            err.textContent = '';
            err.classList.remove('show');
        }
    }

    updateComputedFields() {
        this.config.formFields.forEach(field => {
            if (field.computed === 'jumlah * harga_satuan') {
                const jumlahEl = document.getElementById('formField_jumlah');
                const satuanEl = document.getElementById('formField_harga_satuan');
                const targetEl = document.getElementById(`formField_${field.name}`);
                if (!targetEl) return;

                const qty = parseInt(jumlahEl ? jumlahEl.value : '0', 10) || 0;
                const unitPrice = parseInt(this.parsePureDigits(satuanEl ? satuanEl.value : ''), 10) || 0;
                const total = qty * unitPrice;
                targetEl.value = total > 0 ? this.formatThousands(String(total)) : '';
            }
        });
    }

    async handleFileSelection(field, fileInput) {
        const file = fileInput && fileInput.files ? fileInput.files[0] : null;
        if (!file) return;

        const maxBytes = 5 * 1024 * 1024;
        const allowedExt = ['pdf', 'jpg', 'jpeg', 'png'];
        const ext = (file.name.split('.').pop() || '').toLowerCase();
        const isImage = ['jpg', 'jpeg', 'png'].includes(ext) || /^image\/(jpeg|png)$/i.test(file.type);
        const isPdf = ext === 'pdf' || file.type === 'application/pdf';

        this.clearSingleFieldError(field.name);

        if (!allowedExt.includes(ext) && !isImage && !isPdf) {
            fileInput.value = '';
            delete this._selectedFiles[field.name];
            this.setUploadButtonState(field.name, '', 'PDF, JPG, PNG (Maks. 5MB, gambar lebih otomatis dikompres)');
            this.showFieldError(field.name, 'Format file harus PDF, JPG, atau PNG');
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Format Tidak Didukung',
                    text: 'Lampiran hanya mendukung file PDF, JPG, atau PNG.',
                    confirmButtonColor: '#2f6df0'
                });
            }
            return;
        }

        let finalFile = file;
        let hintMsg = `Ukuran: ${(file.size / 1024 / 1024).toFixed(2)} MB`;

        if (file.size > maxBytes) {
            if (isImage) {
                try {
                    this.setUploadButtonState(field.name, 'Mengompres gambar...', 'Sedang mengompres gambar > 5MB...');
                    finalFile = await this.compressImageUnderLimit(file, maxBytes);
                    hintMsg = `Dikompres otomatis dari ${(file.size / 1024 / 1024).toFixed(2)} MB menjadi ${(finalFile.size / 1024 / 1024).toFixed(2)} MB`;
                } catch (err) {
                    fileInput.value = '';
                    delete this._selectedFiles[field.name];
                    this.setUploadButtonState(field.name, '', 'PDF, JPG, PNG (Maks. 5MB)');
                    this.showFieldError(field.name, 'Gagal mengompres gambar agar <= 5MB');
                    return;
                }
            } else if (isPdf) {
                if (file.size > 20 * 1024 * 1024) {
                    fileInput.value = '';
                    delete this._selectedFiles[field.name];
                    this.setUploadButtonState(field.name, '', 'PDF maksimal 20MB untuk kompresi server');
                    this.showFieldError(field.name, 'Ukuran PDF terlalu besar (maks. 20MB sebelum kompresi server)');
                    return;
                }
                hintMsg = `PDF ${(file.size / 1024 / 1024).toFixed(2)} MB (> 5MB, akan dikompres di server saat Submit)`;
            }
        }

        this._selectedFiles[field.name] = finalFile;
        this.setUploadButtonState(field.name, finalFile.name, hintMsg);
    }

    setUploadButtonState(fieldName, fileName, hintText) {
        const btn = document.getElementById(`btnUpload_${fieldName}`);
        const textEl = document.getElementById(`textUpload_${fieldName}`);
        const hintEl = document.getElementById(`hint_${fieldName}`);

        if (textEl) {
            textEl.textContent = fileName || 'Upload File +';
        }
        if (btn) {
            if (fileName) {
                btn.classList.add('has-file');
                btn.title = fileName;
            } else {
                btn.classList.remove('has-file');
                btn.removeAttribute('title');
            }
        }
        if (hintEl && hintText !== undefined) {
            hintEl.textContent = hintText;
        }
    }

    compressImageUnderLimit(file, maxBytes) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onerror = () => reject(new Error('Gagal membaca file gambar'));
            reader.onload = () => {
                const img = new Image();
                img.onerror = () => reject(new Error('File gambar tidak valid'));
                img.onload = async () => {
                    let scale = 1;
                    const maxDim = 2400;
                    if (img.width > maxDim || img.height > maxDim) {
                        scale = Math.min(maxDim / img.width, maxDim / img.height);
                    }

                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    if (!ctx) {
                        reject(new Error('Canvas tidak tersedia'));
                        return;
                    }

                    const toBlobAsync = (mime, quality) => new Promise(res => canvas.toBlob(res, mime, quality));
                    const baseName = file.name.replace(/\.[^.]+$/, '');
                    const qualities = [0.85, 0.72, 0.58, 0.45, 0.32];

                    for (let step = 0; step < 4; step++) {
                        const w = Math.max(320, Math.round(img.width * scale));
                        const h = Math.max(320, Math.round(img.height * scale));
                        canvas.width = w;
                        canvas.height = h;
                        ctx.fillStyle = '#ffffff';
                        ctx.fillRect(0, 0, w, h);
                        ctx.drawImage(img, 0, 0, w, h);

                        for (const q of qualities) {
                            const blob = await toBlobAsync('image/jpeg', q);
                            if (blob && blob.size <= maxBytes) {
                                resolve(new File([blob], `${baseName}.jpg`, {
                                    type: 'image/jpeg',
                                    lastModified: Date.now()
                                }));
                                return;
                            }
                        }
                        scale *= 0.75;
                    }
                    reject(new Error('Gambar melebihi batas 5MB setelah kompresi'));
                };
                img.src = reader.result;
            };
            reader.readAsDataURL(file);
        });
    }

    showFieldError(fieldName, message) {
        const el = document.getElementById(`formField_${fieldName}`);
        const uploadBtn = document.getElementById(`btnUpload_${fieldName}`);
        const err = document.getElementById(`err_${fieldName}`);
        if (el && el.type !== 'file') el.classList.add('has-error');
        if (uploadBtn) uploadBtn.classList.add('has-error');
        if (err) {
            err.textContent = message;
            err.classList.add('show');
        }
    }

    focusFirstEditableField() {
        const firstField = this.config.formFields.find(f => !this.isAutoReadonlyField(f) && f.type !== 'file');
        if (!firstField) return;
        const el = document.getElementById(`formField_${firstField.name}`);
        if (el) {
            setTimeout(() => {
                try {
                    el.focus();
                } catch (_) {}
            }, 60);
        }
    }

    extractAndValidateYear(rawVal) {
        const maxYear = new Date().getFullYear();
        const text = String(rawVal || '').trim();

        if (!text) {
            return { valid: false, year: '', empty: true, message: '' };
        }

        if (this.config.kibId === 'kib-f') {
            const iso = this.parseDisplayDateToIso(text);
            let yearStr = '';
            if (iso) {
                yearStr = iso.slice(0, 4);
            } else {
                const match = text.match(/\b(\d{4})\b/);
                if (match) yearStr = match[1];
            }

            if (!yearStr) {
                return {
                    valid: false,
                    year: '',
                    empty: false,
                    message: `Tanggal Mulai harus berformat dd/mm/yy (tahun 1900–${maxYear})`
                };
            }
            const yearInt = parseInt(yearStr, 10);
            if (yearInt < 1900 || yearInt > maxYear) {
                return {
                    valid: false,
                    year: '',
                    empty: false,
                    message: `Tahun pada Tanggal Mulai harus antara 1900 sampai ${maxYear}`
                };
            }
            return { valid: true, year: yearStr, empty: false, message: '' };
        }

        if (!/^\d{4}$/.test(text)) {
            return {
                valid: false,
                year: '',
                empty: false,
                message: `Tahun Perolehan harus berupa 4 digit angka (1900–${maxYear})`
            };
        }

        const yearInt = parseInt(text, 10);
        if (yearInt < 1900 || yearInt > maxYear) {
            return {
                valid: false,
                year: '',
                empty: false,
                message: `Tahun Perolehan harus antara 1900 sampai ${maxYear}`
            };
        }

        return { valid: true, year: text, empty: false, message: '' };
    }

    handleYearPreviewChange(showInvalidError) {
        if (this.editingId) return;

        const yearFieldName = this.getYearSourceFieldName();
        const yearEl = document.getElementById(`formField_${yearFieldName}`);
        const errEl = document.getElementById(`err_${yearFieldName}`);
        const kodeEl = document.getElementById('formField_kode');

        if (!yearEl) return;

        const check = this.extractAndValidateYear(yearEl.value);

        if (check.valid) {
            yearEl.classList.remove('has-error');
            if (errEl) {
                errEl.textContent = '';
                errEl.classList.remove('show');
            }
            this.fetchKodePreview(check.year);
            return;
        }

        if (kodeEl) kodeEl.value = '';
        this._previewSeq = (this._previewSeq || 0) + 1;

        const hasFullDate = this.config.kibId === 'kib-f' && /^\d{2}\/\d{2}\/\d{2}$/.test(yearEl.value.trim());
        const hasFourDigits = /\b\d{4}\b/.test(yearEl.value);
        if ((showInvalidError && !check.empty) || (!check.empty && (hasFourDigits || hasFullDate))) {
            yearEl.classList.add('has-error');
            if (errEl) {
                errEl.textContent = check.message;
                errEl.classList.add('show');
            }
        } else if (!showInvalidError) {
            yearEl.classList.remove('has-error');
            if (errEl) {
                errEl.textContent = '';
                errEl.classList.remove('show');
            }
        }
    }

    async fetchKodePreview(yearStr) {
        this._previewSeq = (this._previewSeq || 0) + 1;
        const seq = this._previewSeq;

        try {
            const url = `${this.getApiUrl()}?action=next_kode&kib=${encodeURIComponent(this.config.kibId)}&tahun=${encodeURIComponent(yearStr || '')}`;
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const res = await response.json();

            if (seq !== this._previewSeq || this.editingId) return;

            if (res && res.success && res.data) {
                const kodeEl = document.getElementById('formField_kode');
                const noRegEl = document.getElementById('formField_no_reg');
                if (noRegEl && res.data.no_registrasi !== undefined) {
                    noRegEl.value = res.data.no_registrasi;
                }
                if (kodeEl) {
                    kodeEl.value = res.data.kode_aset || '';
                }
            }
        } catch (e) {
            console.warn('Gagal memuat pratinjau kode aset:', e);
        }
    }

    // Modal Tambah Aset
    openAddModal() {
        this.editingId = null;
        this._selectedFiles = {};
        const titleEl = document.getElementById('kibAssetFormTitle');
        const submitBtn = document.getElementById('btnKibSubmitAsset');
        const form = document.getElementById('kibAssetForm');
        const idInput = document.getElementById('formAssetId');

        if (titleEl) titleEl.textContent = `Tambah Aset ${this.getShortKibTitle()}`;
        if (submitBtn) submitBtn.textContent = 'Submit';
        if (idInput) idInput.value = '';
        if (form) form.reset();

        this.config.formFields.forEach(field => {
            const el = document.getElementById(`formField_${field.name}`);
            if (field.type === 'file') {
                if (el) el.value = '';
                this.setUploadButtonState(field.name, '', 'PDF, JPG, PNG (Maks. 5MB, gambar lebih otomatis dikompres)');
            } else if (field.type === 'select' && el) {
                el.value = field.default !== undefined ? field.default : '';
            } else if (field.type === 'number' && el) {
                el.value = field.default !== undefined ? String(field.default) : '1';
            } else if (field.type === 'date' && el) {
                el.value = '';
                const pickerEl = document.getElementById(`datePicker_${field.name}`);
                if (pickerEl) pickerEl.value = '';
            } else if (el && this.isAutoReadonlyField(field)) {
                el.value = '';
            }
        });

        this.updateComputedFields();
        this.clearFormErrors();
        this.openModal('kibModalAssetForm');
        this.fetchKodePreview('');
        this.focusFirstEditableField();
    }

    // Modal Edit Aset
    openEditModal(id) {
        const item = this.state.data.find(d => d.id === id);
        if (!item) return;

        this.editingId = id;
        this._selectedFiles = {};
        this._previewSeq = (this._previewSeq || 0) + 1;
        const titleEl = document.getElementById('kibAssetFormTitle');
        const submitBtn = document.getElementById('btnKibSubmitAsset');
        const idInput = document.getElementById('formAssetId');

        if (titleEl) titleEl.textContent = `Edit Aset ${this.getShortKibTitle()}`;
        if (submitBtn) submitBtn.textContent = 'Submit';
        if (idInput) idInput.value = item.id;

        this.clearFormErrors();

        // Populate fields
        this.config.formFields.forEach(field => {
            const el = document.getElementById(`formField_${field.name}`);
            const rawVal = item[field.name] !== undefined && item[field.name] !== null ? String(item[field.name]) : '';

            if (field.type === 'file') {
                if (el) el.value = '';
                if (rawVal && rawVal !== '-') {
                    this.setUploadButtonState(field.name, rawVal, 'Klik tombol untuk mengganti file lampiran');
                } else {
                    this.setUploadButtonState(field.name, '', 'PDF, JPG, PNG (Maks. 5MB, gambar lebih otomatis dikompres)');
                }
            } else if (field.type === 'currency' && el) {
                el.value = this.formatThousands(rawVal);
            } else if (field.type === 'number' && el) {
                const parsedQty = parseInt(rawVal.replace(/[^\d]/g, ''), 10);
                el.value = parsedQty > 0 ? String(parsedQty) : String(field.default !== undefined ? field.default : 1);
            } else if (field.type === 'date' && el) {
                el.value = this.formatAnyDateToDisplay(rawVal);
                const pickerEl = document.getElementById(`datePicker_${field.name}`);
                const iso = this.parseDisplayDateToIso(rawVal);
                if (pickerEl) pickerEl.value = iso || '';
            } else if (field.type === 'select' && el) {
                if (rawVal) {
                    const hasOpt = Array.from(el.options).some(o => o.value === rawVal);
                    if (!hasOpt) {
                        const extraOpt = document.createElement('option');
                        extraOpt.value = rawVal;
                        extraOpt.textContent = rawVal;
                        el.appendChild(extraOpt);
                    }
                    el.value = rawVal;
                } else {
                    el.value = field.default !== undefined ? field.default : '';
                }
            } else if (el) {
                el.value = rawVal;
            }
        });

        this.updateComputedFields();
        this.openModal('kibModalAssetForm');
        this.focusFirstEditableField();
    }

    clearFormErrors() {
        this.config.formFields.forEach(field => {
            this.clearSingleFieldError(field.name);
        });
    }

    // Handle Simpan / Update Aset
    handleAssetFormSubmit(e) {
        e.preventDefault();
        this.clearFormErrors();
        this.updateComputedFields();

        let isValid = true;
        let firstErrorField = null;
        const formData = {};
        const errorMessages = [];

        this.config.formFields.forEach(field => {
            const el = document.getElementById(`formField_${field.name}`);
            let val = '';

            if (field.type === 'file') {
                if (this._selectedFiles[field.name]) {
                    val = this._selectedFiles[field.name].name;
                } else if (el && el.files && el.files[0]) {
                    val = el.files[0].name;
                } else if (this.editingId) {
                    const existingItem = this.state.data.find(d => d.id === this.editingId);
                    val = (existingItem && existingItem[field.name]) ? existingItem[field.name] : '';
                }
                formData[field.name] = val;
                return;
            }

            if (field.type === 'currency') {
                val = this.parsePureDigits(el ? el.value : '');
            } else {
                val = el ? el.value.trim() : '';
            }

            if (!this.isAutoReadonlyField(field) || field.computed) {
                formData[field.name] = val;

                if (field.required && !val) {
                    const msg = `${field.label} wajib diisi`;
                    this.showFieldError(field.name, msg);
                    errorMessages.push(msg);
                    if (!firstErrorField) firstErrorField = el;
                    isValid = false;
                    return;
                }

                if (field.type === 'number' && val !== '') {
                    const num = parseInt(val, 10);
                    const minVal = field.min !== undefined ? Number(field.min) : 1;
                    if (isNaN(num) || num < minVal) {
                        const msg = `${field.label} minimal ${minVal}`;
                        this.showFieldError(field.name, msg);
                        errorMessages.push(msg);
                        if (!firstErrorField) firstErrorField = el;
                        isValid = false;
                    }
                }

                if (field.name === 'spesifikasi_tahun' && val !== '') {
                    const maxYear = new Date().getFullYear();
                    if (!/^\d{4}$/.test(val) || Number(val) < 1900 || Number(val) > maxYear) {
                        const msg = `Tahun Terbit harus 4 digit (1900–${maxYear})`;
                        this.showFieldError(field.name, msg);
                        errorMessages.push(msg);
                        if (!firstErrorField) firstErrorField = el;
                        isValid = false;
                    }
                }

                if (field.type === 'date' && val !== '') {
                    const iso = this.parseDisplayDateToIso(val);
                    if (!iso) {
                        const msg = `${field.label} harus berformat dd/mm/yy`;
                        this.showFieldError(field.name, msg);
                        errorMessages.push(msg);
                        if (!firstErrorField) firstErrorField = el;
                        isValid = false;
                    }
                }
            }
        });

        const yearFieldName = this.getYearSourceFieldName();
        const yearEl = document.getElementById(`formField_${yearFieldName}`);
        if (yearEl && formData[yearFieldName]) {
            const yearCheck = this.extractAndValidateYear(formData[yearFieldName]);
            if (!yearCheck.valid) {
                this.showFieldError(yearFieldName, yearCheck.message);
                errorMessages.push(yearCheck.message);
                if (!firstErrorField) firstErrorField = yearEl;
                isValid = false;
            }
        }

        if (!isValid) {
            if (firstErrorField && typeof firstErrorField.focus === 'function') {
                firstErrorField.focus();
            }
            if (typeof Swal !== 'undefined' && errorMessages.length > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Periksa Isian Form',
                    text: errorMessages[0],
                    confirmButtonColor: '#2f6df0'
                });
            }
            return;
        }

        const submitBtn = document.querySelector('#kibAssetForm button[type="submit"]');
        const origHtml = submitBtn ? submitBtn.innerHTML : 'Submit';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>Menyimpan...</span>';
        }

        const formDataObj = new FormData();
        formDataObj.append('action', 'save');
        formDataObj.append('kib', this.config.kibId);
        if (this.editingId) {
            formDataObj.append('id', this.editingId);
        }
        formDataObj.append('csrf_token', this.getCsrfToken());

        Object.keys(formData).forEach(k => {
            if (k === 'lampiran' && this._selectedFiles[k]) {
                formDataObj.append('lampiran', this._selectedFiles[k], this._selectedFiles[k].name);
            } else {
                formDataObj.append(k, formData[k]);
            }
        });

        fetch(this.getApiUrl(), {
            method: 'POST',
            body: formDataObj,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(r => r.json())
        .then(res => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = origHtml;
            }
            if (res && res.success) {
                if (this.editingId) {
                    const target = this.state.data.find(d => d.id === this.editingId);
                    if (target) {
                        Object.assign(target, (res.data && res.data.item) ? res.data.item : formData);
                    }
                } else {
                    const newItem = (res.data && res.data.item)
                        ? res.data.item
                        : Object.assign({ id: Date.now() }, formData);
                    this.state.data.unshift(newItem);
                    this.state.currentPage = 1;
                }

                this.closeModal('kibModalAssetForm');
                this.render();

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: this.editingId ? 'Perubahan Disimpan!' : 'Aset Berhasil Ditambahkan!',
                        text: res.message || `Data aset berhasil disimpan ke ${this.config.kibName}.`,
                        timer: 1600,
                        showConfirmButton: false,
                        confirmButtonColor: '#16a34a'
                    });
                }
            } else {
                if (res && res.data && res.data.field) {
                    this.showFieldError(res.data.field, res.message || 'Input tidak valid');
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: this.editingId ? 'Gagal Menyimpan' : 'Gagal Menambah Aset',
                        text: (res && res.message) ? res.message : 'Terjadi kesalahan sistem.',
                        confirmButtonColor: '#852019'
                    });
                }
            }
        })
        .catch(() => {
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
        });
    }

    // Detail Aset Popup
    showDetail(id) {
        const item = this.state.data.find(d => d.id === id);
        if (!item) return;

        const rowsHtml = this.config.formFields.map(f => {
            const raw = item[f.name];
            let val = raw || '-';

            if (f.name === 'lampiran' && raw && raw !== '-') {
                val = `<button type="button" class="btn-kib-lampiran" onclick="window.__kibEngine.previewLampiran('${this.escapeHtml(raw)}')"><i class="ph-bold ph-paperclip"></i><span>${this.escapeHtml(raw)}</span></button>`;
            } else if (f.name === 'penempatan') {
                val = this.escapeHtml(this.formatPenempatan(val));
            } else {
                val = this.escapeHtml(val);
            }

            return `
                <div style="border-bottom: 1px solid #e2e8f0; padding: 8px 0; display: flex; justify-content: space-between; align-items: center; gap: 12px;">
                    <span style="color: #64748b; font-weight: 500; font-size: 13px;">${this.escapeHtml(f.label)}</span>
                    <strong style="color: #1e293b; font-size: 13px; text-align: right;">${val}</strong>
                </div>
            `;
        }).join('');

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: `Detail Aset - ${this.config.kibName}`,
                html: `
                    <div style="text-align: left; padding: 4px 8px; max-height: 420px; overflow-y: auto;">
                        ${rowsHtml}
                    </div>
                `,
                confirmButtonColor: '#852019',
                confirmButtonText: 'Tutup'
            });
        }
    }

    // Preview / Unduh Lampiran Dokumen langsung di web
    async previewLampiran(filename) {
        if (!filename || filename === '-') return;

        if (typeof Swal !== 'undefined' && Swal.isVisible()) {
            Swal.close();
        }

        const isImage = /\.(jpe?g|png)$/i.test(filename);
        const iconEl = document.getElementById('kibPreviewHeaderIcon');
        const filenameEl = document.getElementById('kibPreviewFilename');
        const bodyEl = document.getElementById('kibPreviewBody');
        const newTabBtn = document.getElementById('btnKibPreviewNewTab');
        const downloadBtn = document.getElementById('btnKibPreviewDownload');

        if (!bodyEl) return;

        const baseUrl = `${this.getApiUrl()}?action=download&kib=${encodeURIComponent(this.config.kibId)}&file=${encodeURIComponent(filename)}`;
        const inlineUrl = `${baseUrl}&inline=1`;

        if (iconEl) {
            iconEl.className = `ph-bold ${isImage ? 'ph-file-image' : 'ph-file-pdf'}`;
        }
        if (filenameEl) {
            filenameEl.textContent = filename;
            filenameEl.title = filename;
        }
        if (newTabBtn) newTabBtn.style.display = 'none';
        if (downloadBtn) downloadBtn.style.display = 'none';

        if (this._activePreviewBlobUrl) {
            URL.revokeObjectURL(this._activePreviewBlobUrl);
            this._activePreviewBlobUrl = null;
        }

        bodyEl.innerHTML = `
            <div class="kib-preview-state">
                <i class="ph-bold ph-spinner-gap kib-preview-spin"></i>
                <p>Memuat dokumen lampiran...</p>
            </div>
        `;

        this.openModal('kibModalPreview');

        try {
            const resp = await fetch(inlineUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const contentType = (resp.headers.get('content-type') || '').toLowerCase();

            if (!resp.ok || contentType.includes('application/json')) {
                const errJson = await resp.json().catch(() => null);
                const msg = (errJson && errJson.message)
                    ? errJson.message
                    : `File fisik "${filename}" belum tersedia di server.`;

                bodyEl.innerHTML = `
                    <div class="kib-preview-state">
                        <div class="kib-preview-empty-icon">
                            <i class="ph-bold ${isImage ? 'ph-file-image' : 'ph-file-pdf'}"></i>
                        </div>
                        <h4>Berkas Tidak Tersedia</h4>
                        <p>${this.escapeHtml(msg)}</p>
                    </div>
                `;
                return;
            }

            const blob = await resp.blob();
            const blobUrl = URL.createObjectURL(blob);
            this._activePreviewBlobUrl = blobUrl;

            const modalEl = document.getElementById('kibModalPreview');
            if (!modalEl || !modalEl.classList.contains('show')) {
                URL.revokeObjectURL(blobUrl);
                this._activePreviewBlobUrl = null;
                return;
            }

            if (newTabBtn) {
                newTabBtn.href = inlineUrl;
                newTabBtn.style.display = 'inline-flex';
            }
            if (downloadBtn) {
                downloadBtn.style.display = 'inline-flex';
                downloadBtn.onclick = () => {
                    const a = document.createElement('a');
                    a.href = blobUrl;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                };
            }

            if (contentType.includes('pdf') || /\.pdf$/i.test(filename)) {
                bodyEl.innerHTML = `
                    <iframe
                        src="${blobUrl}#toolbar=1&navpanes=0"
                        class="kib-preview-frame"
                        title="Pratinjau ${this.escapeHtml(filename)}"
                    ></iframe>
                `;
            } else if (contentType.startsWith('image/') || isImage) {
                bodyEl.innerHTML = `
                    <div class="kib-preview-image-wrap">
                        <img src="${blobUrl}" class="kib-preview-image" alt="${this.escapeHtml(filename)}">
                    </div>
                `;
            } else {
                bodyEl.innerHTML = `
                    <div class="kib-preview-state">
                        <div class="kib-preview-empty-icon">
                            <i class="ph-bold ph-file"></i>
                        </div>
                        <h4>Pratinjau Tidak Didukung</h4>
                        <p>Format file ini tidak dapat ditampilkan langsung di browser. Gunakan tombol <strong>Unduh</strong> di atas.</p>
                    </div>
                `;
            }
        } catch (_) {
            bodyEl.innerHTML = `
                <div class="kib-preview-state">
                    <div class="kib-preview-empty-icon">
                        <i class="ph-bold ph-warning-circle"></i>
                    </div>
                    <h4>Gagal Memuat Lampiran</h4>
                    <p>Tidak dapat terhubung ke server untuk memuat "${this.escapeHtml(filename)}".</p>
                </div>
            `;
        }
    }

    // Hapus Aset dengan Konfirmasi
    deleteAsset(id) {
        const item = this.state.data.find(d => d.id === id);
        if (!item) return;

        const nama = item.nama || item.nama_aset || item.nama_buku || item.kode || 'Aset ini';

        const doDelete = () => {
            const formDataObj = new FormData();
            formDataObj.append('action', 'delete');
            formDataObj.append('kib', this.config.kibId);
            formDataObj.append('id', id);
            formDataObj.append('csrf_token', this.getCsrfToken());

            fetch(this.getApiUrl(), {
                method: 'POST',
                body: formDataObj,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(res => {
                if (res && res.success) {
                    this.state.data = this.state.data.filter(d => d.id !== id);
                    this.render();

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Aset Dihapus',
                            text: `Data ${nama} berhasil dihapus dari database.`,
                            timer: 1500,
                            showConfirmButton: false,
                            confirmButtonColor: '#852019'
                        });
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menghapus',
                            text: (res && res.message) ? res.message : 'Data tidak dapat dihapus.',
                            confirmButtonColor: '#852019'
                        });
                    }
                }
            })
            .catch(() => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gangguan Jaringan',
                        text: 'Tidak dapat terhubung ke server database.',
                        confirmButtonColor: '#852019'
                    });
                }
            });
        };

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Hapus Aset?',
                html: `Apakah Anda yakin ingin menghapus data aset <strong>${this.escapeHtml(nama)}</strong>? Tindakan ini tidak dapat dibatalkan.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true,
                focusCancel: true
            }).then(res => {
                if (res.isConfirmed) doDelete();
            });
        } else {
            if (window.confirm(`Hapus data ${nama}?`)) doDelete();
        }
    }

    // Render Tabel & Pagination
    render() {
        const tbody = document.getElementById('kibTableBody');
        const clearBtn = document.getElementById('kibSearchClear');
        const countCurrentEl = document.getElementById('countCurrent');
        const countTotalEl = document.getElementById('countTotal');

        if (!tbody) return;

        // Clear Search Btn Visibility
        if (clearBtn) {
            if (this.state.keyword.trim().length > 0) {
                clearBtn.classList.add('show');
            } else {
                clearBtn.classList.remove('show');
            }
        }

        // Filtering Logic
        const qSearch = this.state.keyword.trim().toLowerCase();
        const fKeyword = this.state.filters.keyword.trim().toLowerCase();
        const fJenis = this.state.filters.jenis;
        const fTahun = this.state.filters.tahun;
        const fSumber = this.state.filters.sumber;

        const filtered = this.state.data.filter(item => {
            // 1. Live Search Bar (cocokkan pada field nama, kode, jenis, merk, spesifikasi, lokasi)
            if (qSearch) {
                const combined = Object.values(item).join(' ').toLowerCase();
                if (!combined.includes(qSearch)) return false;
            }

            // 2. Filter Modal: Kata Kunci
            if (fKeyword) {
                const combined = Object.values(item).join(' ').toLowerCase();
                if (!combined.includes(fKeyword)) return false;
            }

            // 3. Filter Modal: Jenis Aset / Buku
            if (fJenis && item.jenis !== fJenis) {
                return false;
            }

            // 4. Filter Modal: Tahun
            if (fTahun) {
                const itemYear = this.extractYear(item);
                if (itemYear !== fTahun) return false;
            }

            // 5. Filter Modal: Sumber
            if (fSumber && item.sumber !== fSumber) {
                return false;
            }

            return true;
        });

        const totalFiltered = filtered.length;

        // Pagination Calculations
        let pageSize = totalFiltered;
        if (this.state.range !== 'all') {
            pageSize = parseInt(this.state.range, 10) || totalFiltered;
        }
        if (pageSize <= 0) pageSize = 1;

        const totalPages = Math.ceil(totalFiltered / pageSize) || 1;

        if (this.state.currentPage > totalPages) this.state.currentPage = totalPages;
        if (this.state.currentPage < 1) this.state.currentPage = 1;

        const startIndex = (this.state.currentPage - 1) * pageSize;
        const pageData = (this.state.range === 'all')
            ? filtered
            : filtered.slice(startIndex, startIndex + pageSize);

        // Update Meta Count
        if (countCurrentEl) countCurrentEl.textContent = pageData.length;
        if (countTotalEl) countTotalEl.textContent = totalFiltered;

        // Total columns count for empty row colspan
        const colCount = this.config.columns.length + 2; // + No & Aksi

        // Render Rows
        if (pageData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="${colCount}" class="kib-empty-row">
                        <i class="ph-bold ph-magnifying-glass"></i>
                        Tidak ada data yang cocok
                    </td>
                </tr>
            `;
        } else {
            tbody.innerHTML = pageData.map((item, idx) => {
                const rowNo = startIndex + idx + 1;
                const cellsHtml = this.config.columns.map(col => {
                    const rawVal = item[col.key] !== undefined ? item[col.key] : '';
                    let renderedVal = this.escapeHtml(rawVal);

                    // Custom cell rendering if provided
                    if (typeof col.render === 'function') {
                        renderedVal = col.render(rawVal, item, this);
                    } else if (col.key === 'kode' || col.key === 'kode_aset') {
                        renderedVal = `<span class="cell-code">${this.highlightMatch(rawVal, this.state.keyword || this.state.filters.keyword)}</span>`;
                    } else if (col.key === 'nama' || col.key === 'nama_aset' || col.key === 'nama_buku') {
                        renderedVal = `<strong class="cell-name">${this.highlightMatch(rawVal, this.state.keyword || this.state.filters.keyword)}</strong>`;
                    } else if (col.key === 'kondisi') {
                        const kLower = String(rawVal).toLowerCase();
                        let badgeClass = 'badge-kib--baik';
                        if (kLower.includes('rusak berat')) badgeClass = 'badge-kib--rusak-berat';
                        else if (kLower.includes('rusak') || kLower.includes('sedang')) badgeClass = 'badge-kib--rusak-ringan';
                        renderedVal = `<span class="badge-kib ${badgeClass}">${this.escapeHtml(rawVal)}</span>`;
                    } else if (col.key === 'sumber') {
                        renderedVal = `<span class="badge-kib badge-kib--sumber">${this.escapeHtml(rawVal)}</span>`;
                    } else if (col.key === 'penempatan') {
                        const penempatanLabel = this.formatPenempatan(rawVal);
                        renderedVal = `<span class="badge-kib badge-kib--sumber">${this.highlightMatch(penempatanLabel, qSearch || fKeyword)}</span>`;
                    } else if (col.key === 'lampiran') {
                        if (rawVal && rawVal !== '-' && rawVal !== 'null') {
                            renderedVal = `
                                <button
                                    type="button"
                                    class="btn-kib-lampiran"
                                    title="Lihat / Unduh Dokumen: ${this.escapeHtml(rawVal)}"
                                    onclick="window.__kibEngine.previewLampiran('${this.escapeHtml(rawVal)}')"
                                >
                                    <i class="ph-bold ph-paperclip"></i>
                                    <span>${this.escapeHtml(rawVal)}</span>
                                </button>
                            `;
                        } else {
                            renderedVal = `<span class="cell-muted">-</span>`;
                        }
                    } else if (col.key === 'harga' || col.key === 'harga_satuan' || col.key === 'harga_total' || col.key === 'biaya') {
                        renderedVal = `<span class="cell-price">${this.escapeHtml(rawVal)}</span>`;
                    } else if (col.key === 'persen_fisik') {
                        renderedVal = `<span class="badge-kib badge-kib--percent">${this.escapeHtml(rawVal)}</span>`;
                    } else if (col.key === 'status_pengerjaan') {
                        const sLower = String(rawVal).toLowerCase();
                        let badgeClass = 'badge-kib--progress';
                        if (sLower.includes('selesai') || sLower.includes('100%')) badgeClass = 'badge-kib--done';
                        renderedVal = `<span class="badge-kib ${badgeClass}">${this.escapeHtml(rawVal)}</span>`;
                    } else if (qSearch || fKeyword) {
                        renderedVal = this.highlightMatch(rawVal, qSearch || fKeyword);
                    }

                    const alignClass = col.align === 'center' ? 'cell-center' : '';
                    return `<td class="${alignClass}">${renderedVal}</td>`;
                }).join('');

                return `
                    <tr data-id="${item.id}">
                        <td class="cell-no">${rowNo}</td>
                        ${cellsHtml}
                        <td class="cell-actions">
                            <div class="kib-actions-cell">
                                <button
                                    type="button"
                                    class="btn-kib-act btn-kib-detail"
                                    title="Lihat Detail"
                                    aria-label="Detail"
                                    onclick="window.__kibEngine.showDetail(${item.id})"
                                >
                                    <i class="ph-bold ph-notebook"></i>
                                </button>
                                <button
                                    type="button"
                                    class="btn-kib-act btn-kib-edit"
                                    title="Edit Data"
                                    aria-label="Edit"
                                    onclick="window.__kibEngine.openEditModal(${item.id})"
                                >
                                    <i class="ph-bold ph-pencil-simple"></i>
                                </button>
                                <button
                                    type="button"
                                    class="btn-kib-act btn-kib-delete"
                                    title="Hapus Data"
                                    aria-label="Hapus"
                                    onclick="window.__kibEngine.deleteAsset(${item.id})"
                                >
                                    <i class="ph-bold ph-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Render Pagination
        this.renderPagination(totalPages);
    }

    renderPagination(totalPages) {
        const container = document.getElementById('kibPagination');
        if (!container) return;

        if (totalPages <= 1) {
            container.innerHTML = `
                <button type="button" class="page-btn" disabled aria-label="Previous">Previous</button>
                <button type="button" class="page-btn active">1</button>
                <button type="button" class="page-btn" disabled aria-label="Next">Next</button>
            `;
            return;
        }

        const current = this.state.currentPage;
        const isPrevDisabled = (current <= 1);
        const isNextDisabled = (current >= totalPages);

        let html = '';

        html += `
            <button
                type="button"
                class="page-btn"
                ${isPrevDisabled ? 'disabled' : ''}
                onclick="window.__kibEngine.goToPage(${current - 1})"
                aria-label="Previous"
            >
                Previous
            </button>
        `;

        const pages = this.getPaginationNumbers(current, totalPages);
        pages.forEach(p => {
            if (p === '...') {
                html += `<span class="page-ellipsis">...</span>`;
            } else {
                const isActive = (p === current);
                html += `
                    <button
                        type="button"
                        class="page-btn ${isActive ? 'active' : ''}"
                        onclick="window.__kibEngine.goToPage(${p})"
                    >
                        ${p}
                    </button>
                `;
            }
        });

        html += `
            <button
                type="button"
                class="page-btn"
                ${isNextDisabled ? 'disabled' : ''}
                onclick="window.__kibEngine.goToPage(${current + 1})"
                aria-label="Next"
            >
                Next
            </button>
        `;

        container.innerHTML = html;
    }

    getPaginationNumbers(current, total) {
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

    goToPage(p) {
        this.state.currentPage = p;
        this.render();
    }
}

// Global reference helper
window.KibEngine = KibEngine;
