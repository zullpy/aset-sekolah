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

        // B. Dynamic Fields untuk Modal Tambah / Edit Aset
        const formFieldsHtml = this.config.formFields.map(field => {
            const reqClass = field.required ? 'required' : '';
            if (field.type === 'select') {
                const optionsHtml = (field.options || []).map(opt => `<option value="${this.escapeHtml(opt)}">${this.escapeHtml(opt)}</option>`).join('');
                return `
                    <div class="kib-form-group">
                        <label for="formField_${field.name}" class="kib-label ${reqClass}">${this.escapeHtml(field.label)}</label>
                        <select id="formField_${field.name}" name="${field.name}" class="kib-select" ${field.required ? 'required' : ''}>
                            <option value="">Pilih ${this.escapeHtml(field.label)}</option>
                            ${optionsHtml}
                        </select>
                        <span class="kib-field-error" id="err_${field.name}"></span>
                    </div>
                `;
            } else if (field.type === 'textarea') {
                return `
                    <div class="kib-form-group">
                        <label for="formField_${field.name}" class="kib-label ${reqClass}">${this.escapeHtml(field.label)}</label>
                        <textarea id="formField_${field.name}" name="${field.name}" class="kib-textarea" placeholder="${this.escapeHtml(field.placeholder || '')}" ${field.required ? 'required' : ''}></textarea>
                        <span class="kib-field-error" id="err_${field.name}"></span>
                    </div>
                `;
            } else if (field.type === 'file') {
                return `
                    <div class="kib-form-group">
                        <label for="formField_${field.name}" class="kib-label ${reqClass}">${this.escapeHtml(field.label)}</label>
                        <input
                            type="file"
                            id="formField_${field.name}"
                            name="${field.name}"
                            class="kib-input"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                            ${field.required ? 'required' : ''}
                        >
                        <span id="hint_${field.name}" style="color: #64748b; font-size: 11.5px; margin-top: 3px; display: block;">Format didukung: PDF, JPG, PNG, DOC (Maks. 5MB)</span>
                        <span class="kib-field-error" id="err_${field.name}"></span>
                    </div>
                `;
            } else {
                return `
                    <div class="kib-form-group">
                        <label for="formField_${field.name}" class="kib-label ${reqClass}">${this.escapeHtml(field.label)}</label>
                        <input
                            type="${field.type || 'text'}"
                            id="formField_${field.name}"
                            name="${field.name}"
                            class="kib-input"
                            placeholder="${this.escapeHtml(field.placeholder || '')}"
                            ${field.required ? 'required' : ''}
                        >
                        <span class="kib-field-error" id="err_${field.name}"></span>
                    </div>
                `;
            }
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
                        <h3 class="kib-modal-title" id="kibAssetFormTitle">Tambah Aset - ${this.config.kibName}</h3>
                        <button type="button" class="kib-modal-close" data-modal-close aria-label="Tutup">
                            <i class="ph-bold ph-x"></i>
                        </button>
                    </div>

                    <form id="kibAssetForm" novalidate autocomplete="off">
                        <input type="hidden" id="formAssetId" value="">
                        <div class="kib-modal-body">
                            ${formFieldsHtml}
                        </div>
                        <div class="kib-modal-footer kib-modal-footer--center">
                            <button type="submit" class="btn-kib-modal-submit" id="btnKibSubmitAsset">
                                Simpan Aset
                            </button>
                        </div>
                    </form>
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

    // Modal Tambah Aset
    openAddModal() {
        this.editingId = null;
        const titleEl = document.getElementById('kibAssetFormTitle');
        const submitBtn = document.getElementById('btnKibSubmitAsset');
        const form = document.getElementById('kibAssetForm');
        const idInput = document.getElementById('formAssetId');

        if (titleEl) titleEl.textContent = `Tambah Aset - ${this.config.kibName}`;
        if (submitBtn) submitBtn.textContent = 'Simpan Aset';
        if (idInput) idInput.value = '';
        if (form) form.reset();

        this.config.formFields.forEach(field => {
            if (field.type === 'file') {
                const hint = document.getElementById(`hint_${field.name}`);
                if (hint) hint.textContent = 'Format didukung: PDF, JPG, PNG, DOC (Maks. 5MB)';
            }
        });

        this.clearFormErrors();
        this.openModal('kibModalAssetForm');
    }

    // Modal Edit Aset
    openEditModal(id) {
        const item = this.state.data.find(d => d.id === id);
        if (!item) return;

        this.editingId = id;
        const titleEl = document.getElementById('kibAssetFormTitle');
        const submitBtn = document.getElementById('btnKibSubmitAsset');
        const idInput = document.getElementById('formAssetId');

        if (titleEl) titleEl.textContent = `Edit Aset - ${this.config.kibName}`;
        if (submitBtn) submitBtn.textContent = 'Simpan Perubahan';
        if (idInput) idInput.value = item.id;

        this.clearFormErrors();

        // Populate fields
        this.config.formFields.forEach(field => {
            const el = document.getElementById(`formField_${field.name}`);
            if (field.type === 'file') {
                const hint = document.getElementById(`hint_${field.name}`);
                if (hint) {
                    hint.innerHTML = item[field.name] 
                        ? `<span style="color: #16a34a; font-weight: 600;"><i class="ph-bold ph-paperclip"></i> File saat ini: ${this.escapeHtml(item[field.name])}</span> (Pilih file baru jika ingin mengganti)`
                        : 'Format didukung: PDF, JPG, PNG, DOC (Maks. 5MB)';
                }
            } else if (el) {
                el.value = item[field.name] !== undefined ? item[field.name] : '';
            }
        });

        this.openModal('kibModalAssetForm');
    }

    clearFormErrors() {
        this.config.formFields.forEach(field => {
            const el = document.getElementById(`formField_${field.name}`);
            const err = document.getElementById(`err_${field.name}`);
            if (el) el.classList.remove('has-error');
            if (err) {
                err.textContent = '';
                err.classList.remove('show');
            }
        });
    }

    // Handle Simpan / Update Aset
    handleAssetFormSubmit(e) {
        e.preventDefault();
        this.clearFormErrors();

        let isValid = true;
        const formData = {};

        this.config.formFields.forEach(field => {
            const el = document.getElementById(`formField_${field.name}`);
            const err = document.getElementById(`err_${field.name}`);
            let val = '';

            if (field.type === 'file') {
                if (el && el.files && el.files[0]) {
                    val = el.files[0].name;
                } else if (this.editingId) {
                    const existingItem = this.state.data.find(d => d.id === this.editingId);
                    val = (existingItem && existingItem[field.name]) ? existingItem[field.name] : '';
                }
            } else {
                val = el ? el.value.trim() : '';
            }

            formData[field.name] = val;

            if (field.required && !val) {
                if (el) el.classList.add('has-error');
                if (err) {
                    err.textContent = `${field.label} wajib diisi`;
                    err.classList.add('show');
                }
                isValid = false;
            }
        });

        if (!isValid) return;

        if (this.editingId) {
            // Edit Mode
            const target = this.state.data.find(d => d.id === this.editingId);
            const submitBtn = document.querySelector('#kibAssetForm button[type="submit"]');
            const origHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>Menyimpan ke DB...</span>';
            }

            const formDataObj = new FormData();
            formDataObj.append('action', 'save');
            formDataObj.append('kib', this.config.kibId);
            formDataObj.append('id', this.editingId);
            formDataObj.append('csrf_token', this.getCsrfToken());
            Object.keys(formData).forEach(k => formDataObj.append(k, formData[k]));

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
                    if (target) {
                        Object.assign(target, formData);
                    }
                    this.closeModal('kibModalAssetForm');
                    this.render();

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Perubahan Disimpan!',
                            text: 'Data aset berhasil diperbarui di database.',
                            timer: 1600,
                            showConfirmButton: false,
                            confirmButtonColor: '#16a34a'
                        });
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menyimpan',
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
        } else {
            // Add Mode
            const submitBtn = document.querySelector('#kibAssetForm button[type="submit"]');
            const origHtml = submitBtn ? submitBtn.innerHTML : '';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span>Menyimpan ke DB...</span>';
            }

            const formDataObj = new FormData();
            formDataObj.append('action', 'save');
            formDataObj.append('kib', this.config.kibId);
            formDataObj.append('csrf_token', this.getCsrfToken());
            Object.keys(formData).forEach(k => formDataObj.append(k, formData[k]));

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
                    const newItem = (res.data && res.data.item)
                        ? res.data.item
                        : Object.assign({ id: Date.now() }, formData);

                    this.state.data.unshift(newItem);
                    this.closeModal('kibModalAssetForm');
                    this.state.currentPage = 1;
                    this.render();

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Aset Berhasil Ditambahkan!',
                            text: `Data aset baru berhasil didaftarkan ke ${this.config.kibName} di database.`,
                            timer: 1600,
                            showConfirmButton: false,
                            confirmButtonColor: '#16a34a'
                        });
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Menambah Aset',
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

    // Preview / Unduh Lampiran Dokumen
    previewLampiran(filename) {
        if (!filename || filename === '-') return;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Lampiran Dokumen',
                html: `
                    <div style="text-align: center; padding: 12px 6px;">
                        <div style="width: 54px; height: 54px; border-radius: 12px; background: #fef2f2; border: 1.5px solid #fecaca; color: #852019; display: inline-flex; align-items: center; justify-content: center; font-size: 26px; margin-bottom: 12px;">
                            <i class="ph-bold ph-file-pdf"></i>
                        </div>
                        <h4 style="font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 6px; word-break: break-all;">${this.escapeHtml(filename)}</h4>
                        <p style="font-size: 12.5px; color: #64748b; margin: 0;">Dokumen lampiran tersimpan dalam sistem arsip inventaris SDN Budi Karya.</p>
                    </div>
                `,
                showCancelButton: true,
                cancelButtonText: 'Tutup',
                confirmButtonText: '<i class="ph-bold ph-download-simple"></i> Unduh File',
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#64748b'
            }).then(res => {
                if (res.isConfirmed) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Mengunduh Dokumen',
                        text: `File "${filename}" berhasil diunduh.`,
                        timer: 1600,
                        showConfirmButton: false,
                        confirmButtonColor: '#16a34a'
                    });
                }
            });
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
