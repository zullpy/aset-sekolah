/**
 * Engine Halaman Pemeliharaan Aset Sekolah
 * Sistem Informasi Inventaris Aset SDN Budi Karya
 */

class PemeliharaanEngine {
    constructor(config) {
        this.config = config || {};
        this.state = {
            items: [],
            timeline: {
                perlu_pemeliharaan: 0,
                sedang_ditindak_lanjut: 0,
                selesai_ditindak_lanjut: 0,
                digunakan_kembali: 0,
                disimpan: 0
            },
            pagination: {
                page: 1,
                range: '10',
                total_rows: 0,
                total_pages: 1,
                showing_count: 0
            },
            filters: {
                q: '',
                nama_aset: '',
                lokasi: '',
                kondisi_awal: '',
                kondisi_akhir: '',
                status: '',
                tgl_dari: '',
                tgl_sampai: ''
            },
            lokasiOptions: [],
            filterNamaOptions: [],
            formAsetOptions: [],
            selectedFiles: [],
            maxJumlah: null,
            mode: 'add'
        };

        this.assetDropdownOpen = false;
        this.assetActiveIndex = -1;
        this.filteredAssetOptions = [];
        this.selectedAssetLabel = '';

        this.init();
    }

    init() {
        this.bindEvents();
        this.loadInitialData();
    }

    async loadInitialData() {
        await Promise.all([
            this.fetchOpsi(),
            this.fetchList()
        ]);
    }

    getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? (meta.getAttribute('content') || '') : '';
    }

    escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    getTodayIso() {
        const now = new Date();
        const yyyy = now.getFullYear();
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const dd = String(now.getDate()).padStart(2, '0');
        return `${yyyy}-${mm}-${dd}`;
    }

    formatShortDate(isoDate) {
        if (!isoDate || !/^\d{4}-\d{2}-\d{2}$/.test(isoDate)) return 'dd/mm/yy';
        const [yyyy, mm, dd] = isoDate.split('-');
        return `${dd}/${mm}/${yyyy.slice(2)}`;
    }

    bindEvents() {
        // Tombol buka Filter & Tambah Proses
        const btnFilter = document.getElementById('btnPmlFilter');
        if (btnFilter) {
            btnFilter.addEventListener('click', () => this.openFilterModal());
        }
        const btnTambah = document.getElementById('btnPmlTambah');
        if (btnTambah) {
            btnTambah.addEventListener('click', () => this.openAddModal());
        }

        // Range pagination
        document.querySelectorAll('#pmlRangeOptions .range-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('#pmlRangeOptions .range-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.state.pagination.range = btn.getAttribute('data-range') || '10';
                this.state.pagination.page = 1;
                this.fetchList();
            });
        });

        // Filter modal actions
        const btnReset = document.getElementById('btnPmlResetFilter');
        const btnApply = document.getElementById('btnPmlApplyFilter');
        const kwInput = document.getElementById('pmlFilterKeyword');
        if (btnReset) {
            btnReset.addEventListener('click', () => this.resetFilters());
        }
        if (btnApply) {
            btnApply.addEventListener('click', () => this.applyFilters());
        }
        if (kwInput) {
            kwInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.applyFilters();
                }
            });
        }

        // Proses form events
        this.bindAssetComboboxEvents();

        const statusSelect = document.getElementById('pmlFieldStatus');
        if (statusSelect) {
            statusSelect.addEventListener('change', () => this.syncKondisiAkhirRule());
        }

        const tglInput = document.getElementById('pmlFieldTanggal');
        if (tglInput) {
            tglInput.addEventListener('input', () => this.updateTanggalPreview());
            tglInput.addEventListener('change', () => this.updateTanggalPreview());
        }

        const prosesForm = document.getElementById('pmlProsesForm');
        if (prosesForm) {
            prosesForm.addEventListener('submit', (e) => this.handleProsesSubmit(e));
        }

        // Upload dropzone & file input
        this.bindUploadEvents();

        // Modal close (tombol X, overlay click, Esc)
        document.querySelectorAll('#pmlModalFilter [data-modal-close], #pmlModalProses [data-modal-close], #pmlModalRiwayat [data-modal-close], #pmlModalFoto [data-modal-close]').forEach(btn => {
            btn.addEventListener('click', () => {
                const overlay = btn.closest('.kib-modal-overlay');
                if (overlay) this.closeModal(overlay.id);
            });
        });

        ['pmlModalFilter', 'pmlModalProses', 'pmlModalRiwayat', 'pmlModalFoto'].forEach(modalId => {
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
                const fotoModal = document.getElementById('pmlModalFoto');
                if (fotoModal && fotoModal.classList.contains('show')) {
                    this.closeModal('pmlModalFoto');
                    return;
                }
                this.closeModal('pmlModalRiwayat');
                this.closeModal('pmlModalProses');
                this.closeModal('pmlModalFilter');
            }
        });
    }

    bindUploadEvents() {
        const dropzone = document.getElementById('pmlUploadDropzone');
        const fileInput = document.getElementById('pmlFieldDokumentasi');
        if (!dropzone || !fileInput) return;

        dropzone.addEventListener('click', (e) => {
            if (e.target.closest('.pml-thumb-remove')) return;
            fileInput.click();
        });

        dropzone.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                fileInput.click();
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files && fileInput.files.length > 0) {
                this.addSelectedFiles(fileInput.files);
                fileInput.value = '';
            }
        });

        dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropzone.classList.add('is-dragover');
        });

        dropzone.addEventListener('dragleave', () => {
            dropzone.classList.remove('is-dragover');
        });

        dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropzone.classList.remove('is-dragover');
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                this.addSelectedFiles(e.dataTransfer.files);
            }
        });
    }

    addSelectedFiles(fileList) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        const maxBytes = 5 * 1024 * 1024;
        const errors = [];

        Array.from(fileList).forEach(file => {
            if (!allowedTypes.includes(file.type)) {
                errors.push(`"${file.name}" bukan format JPG/PNG/WEBP.`);
                return;
            }
            if (file.size > maxBytes) {
                errors.push(`"${file.name}" melebihi batas 5MB.`);
                return;
            }
            const dup = this.state.selectedFiles.some(f => f.name === file.name && f.size === file.size && f.lastModified === file.lastModified);
            if (!dup) {
                this.state.selectedFiles.push(file);
            }
        });

        if (errors.length > 0) {
            this.showFieldError('dokumentasi', errors[0]);
        } else {
            this.clearSingleFieldError('dokumentasi');
        }

        this.renderUploadPreviews();
    }

    renderUploadPreviews() {
        const previewWrap = document.getElementById('pmlUploadPreviews');
        const placeholder = document.getElementById('pmlUploadPlaceholder');
        if (!previewWrap || !placeholder) return;

        if (this.state.selectedFiles.length === 0) {
            previewWrap.innerHTML = '';
            placeholder.classList.remove('hidden');
            return;
        }

        placeholder.classList.add('hidden');
        previewWrap.innerHTML = '';

        this.state.selectedFiles.forEach((file, idx) => {
            const card = document.createElement('div');
            card.className = 'pml-thumb-card';

            const img = document.createElement('img');
            img.alt = file.name;
            const reader = new FileReader();
            reader.onload = (ev) => {
                img.src = ev.target?.result || '';
            };
            reader.readAsDataURL(file);

            const rmBtn = document.createElement('button');
            rmBtn.type = 'button';
            rmBtn.className = 'pml-thumb-remove';
            rmBtn.title = 'Hapus foto';
            rmBtn.setAttribute('aria-label', `Hapus ${file.name}`);
            rmBtn.innerHTML = '<i class="ph-bold ph-x"></i>';
            rmBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.state.selectedFiles.splice(idx, 1);
                this.renderUploadPreviews();
            });

            card.appendChild(img);
            card.appendChild(rmBtn);
            previewWrap.appendChild(card);
        });
    }

    async fetchOpsi() {
        try {
            const resp = await fetch(this.config.opsiUrl, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const res = await resp.json();
            if (!res || !res.success || !res.data) return;

            this.state.lokasiOptions = res.data.lokasi_options || [];
            this.state.filterNamaOptions = res.data.filter_nama_aset_options || [];
            this.state.formAsetOptions = res.data.form_aset_options || [];

            this.populateFilterSelects();
        } catch (err) {
            console.error('Gagal memuat opsi pemeliharaan:', err);
        }
    }

    populateFilterSelects() {
        const namaSel = document.getElementById('pmlFilterNamaAset');
        const lokSel = document.getElementById('pmlFilterLokasi');

        if (namaSel) {
            const cur = this.state.filters.nama_aset;
            const opts = ['<option value="">Pilih nama aset</option>'];
            this.state.filterNamaOptions.forEach(nama => {
                opts.push(`<option value="${this.escapeHtml(nama)}">${this.escapeHtml(nama)}</option>`);
            });
            namaSel.innerHTML = opts.join('');
            namaSel.value = cur;
        }

        if (lokSel) {
            const cur = this.state.filters.lokasi;
            const opts = ['<option value="">Pilih Lokasi</option>'];
            this.state.lokasiOptions.forEach(loc => {
                opts.push(`<option value="${this.escapeHtml(loc)}">${this.escapeHtml(loc)}</option>`);
            });
            lokSel.innerHTML = opts.join('');
            lokSel.value = cur;
        }
    }

    setProsesLokasi(value = '') {
        const lokEl = document.getElementById('pmlFieldLokasi');
        if (lokEl) {
            lokEl.value = value || '';
        }
    }

    formatAssetDisplayLabel(opt) {
        if (!opt) return '';
        return `${opt.nama_aset} (${opt.kode_aset})`;
    }

    bindAssetComboboxEvents() {
        const combobox = document.getElementById('pmlAsetCombobox');
        const hiddenInput = document.getElementById('pmlFieldNamaAset');
        const searchInput = document.getElementById('pmlFieldNamaAsetSearch');
        const clearBtn = document.getElementById('pmlFieldNamaAsetClear');
        const toggleBtn = document.getElementById('pmlFieldNamaAsetToggle');
        const dropdown = document.getElementById('pmlAsetDropdown');

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
            const trimmed = searchInput.value.trim();

            if (trimmed !== this.selectedAssetLabel && hiddenInput.value !== '') {
                hiddenInput.value = '';
                this.selectedAssetLabel = '';
                this.handleTambahAsetChange();
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
                        this.selectAssetOption(targetOpt.option_key);
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
            const optEl = e.target.closest('[data-option-key]');
            if (!optEl) return;
            e.preventDefault();
            this.selectAssetOption(optEl.getAttribute('data-option-key'));
        });

        document.addEventListener('click', (e) => {
            if (this.assetDropdownOpen && !combobox.contains(e.target)) {
                this.closeAssetDropdown();
            }
        });
    }

    updateAssetClearButton() {
        const searchInput = document.getElementById('pmlFieldNamaAsetSearch');
        const hiddenInput = document.getElementById('pmlFieldNamaAset');
        const clearBtn = document.getElementById('pmlFieldNamaAsetClear');
        if (!clearBtn) return;
        const hasValue = Boolean((searchInput && searchInput.value.trim()) || (hiddenInput && hiddenInput.value));
        clearBtn.classList.toggle('visible', hasValue && !(searchInput && searchInput.disabled));
    }

    openAssetDropdown(query = '') {
        const searchInput = document.getElementById('pmlFieldNamaAsetSearch');
        const dropdown = document.getElementById('pmlAsetDropdown');
        const combobox = document.getElementById('pmlAsetCombobox');
        if (!searchInput || !dropdown || !combobox || searchInput.disabled) return;

        this.assetDropdownOpen = true;
        combobox.classList.add('is-open');
        searchInput.setAttribute('aria-expanded', 'true');
        this.renderAssetDropdown(query);
    }

    closeAssetDropdown() {
        const searchInput = document.getElementById('pmlFieldNamaAsetSearch');
        const dropdown = document.getElementById('pmlAsetDropdown');
        const combobox = document.getElementById('pmlAsetCombobox');
        this.assetDropdownOpen = false;
        this.assetActiveIndex = -1;
        if (combobox) combobox.classList.remove('is-open');
        if (searchInput) searchInput.setAttribute('aria-expanded', 'false');
        if (dropdown) dropdown.innerHTML = '';
    }

    renderAssetDropdown(query = '') {
        const dropdown = document.getElementById('pmlAsetDropdown');
        const hiddenInput = document.getElementById('pmlFieldNamaAset');
        if (!dropdown) return;

        const opts = this.state.formAsetOptions || [];
        const q = String(query || '').trim().toLowerCase();
        const terms = q ? q.split(/\s+/).filter(Boolean) : [];

        this.filteredAssetOptions = opts.filter(o => {
            if (terms.length === 0) return true;
            const hay = `${o.nama_aset} ${o.kode_aset} ${o.kib_label} ${o.lokasi} ${o.kondisi_awal}`.toLowerCase();
            return terms.every(t => hay.includes(t));
        });

        const selectedKey = hiddenInput ? hiddenInput.value : '';
        if (this.filteredAssetOptions.length === 0) {
            this.assetActiveIndex = -1;
            dropdown.innerHTML = `<div class="mon-aset-empty">Tidak ada aset yang cocok dengan "${this.escapeHtml(query)}".</div>`;
            return;
        }

        const selectedIdx = this.filteredAssetOptions.findIndex(o => o.option_key === selectedKey);
        this.assetActiveIndex = selectedIdx >= 0 ? selectedIdx : 0;

        dropdown.innerHTML = this.filteredAssetOptions.map((o, idx) => {
            const isSelected = o.option_key === selectedKey;
            const isActive = idx === this.assetActiveIndex;
            const statusText = o.source === 'queue'
                ? `Antrean (${o.sisa_jumlah} unit)`
                : `Sisa ${o.sisa_jumlah} unit`;

            return `
                <div
                    class="mon-aset-option${isSelected ? ' is-selected' : ''}${isActive ? ' is-active' : ''}"
                    role="option"
                    aria-selected="${isSelected ? 'true' : 'false'}"
                    data-option-key="${this.escapeHtml(o.option_key)}"
                    data-idx="${idx}"
                >
                    <div class="mon-aset-option-top">
                        <span class="mon-aset-option-kib">${this.escapeHtml(o.kib_label)}</span>
                        <span class="mon-aset-option-name">${this.escapeHtml(o.nama_aset)}</span>
                        <span class="mon-aset-option-code">${this.escapeHtml(o.kode_aset)}</span>
                    </div>
                    <div class="mon-aset-option-meta">
                        <span>Lokasi: ${this.escapeHtml(o.lokasi)} • ${this.escapeHtml(o.kondisi_awal)}</span>
                        <span class="mon-aset-option-quota">${this.escapeHtml(statusText)}</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    updateAssetActiveOption() {
        const dropdown = document.getElementById('pmlAsetDropdown');
        if (!dropdown) return;
        const nodes = dropdown.querySelectorAll('.mon-aset-option');
        nodes.forEach((node, idx) => {
            const active = idx === this.assetActiveIndex;
            node.classList.toggle('is-active', active);
            if (active) {
                node.scrollIntoView({ block: 'nearest' });
            }
        });
    }

    selectAssetOption(optionKey) {
        const found = (this.state.formAsetOptions || []).find(o => o.option_key === optionKey);
        const hiddenInput = document.getElementById('pmlFieldNamaAset');
        const searchInput = document.getElementById('pmlFieldNamaAsetSearch');
        if (!found || !hiddenInput || !searchInput) return;

        hiddenInput.value = found.option_key;
        this.selectedAssetLabel = this.formatAssetDisplayLabel(found);
        searchInput.value = this.selectedAssetLabel;
        searchInput.classList.remove('has-error');
        this.updateAssetClearButton();
        this.closeAssetDropdown();
        this.handleTambahAsetChange();
    }

    clearAssetSelection() {
        const hiddenInput = document.getElementById('pmlFieldNamaAset');
        const searchInput = document.getElementById('pmlFieldNamaAsetSearch');
        if (hiddenInput) hiddenInput.value = '';
        if (searchInput) searchInput.value = '';
        this.selectedAssetLabel = '';
        this.updateAssetClearButton();
        this.handleTambahAsetChange();
    }

    async fetchList() {
        const tbody = document.getElementById('pmlTableBody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="kib-empty-row">
                        <i class="ph-bold ph-spinner"></i>
                        Memuat data pemeliharaan aset...
                    </td>
                </tr>
            `;
        }

        const qs = new URLSearchParams();
        qs.set('page', String(this.state.pagination.page));
        qs.set('range', String(this.state.pagination.range));

        Object.entries(this.state.filters).forEach(([k, v]) => {
            if (v !== '' && v !== null && v !== undefined) {
                qs.set(k, v);
            }
        });

        try {
            const resp = await fetch(`${this.config.listUrl}?${qs.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const res = await resp.json();
            if (!res || !res.success || !res.data) {
                throw new Error(res?.message || 'Gagal memuat data.');
            }

            this.state.items = res.data.items || [];
            this.state.timeline = res.data.timeline || this.state.timeline;
            this.state.pagination = res.data.pagination || this.state.pagination;

            this.renderTimeline();
            this.renderTable();
            this.renderPagination();
            this.updateFilterBadge();
        } catch (err) {
            if (tbody) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" class="kib-empty-row">
                            <i class="ph-bold ph-warning-circle"></i>
                            Gagal memuat data pemeliharaan aset. Silakan coba lagi.
                        </td>
                    </tr>
                `;
            }
        }
    }

    renderTimeline() {
        const t = this.state.timeline || {};
        const map = {
            pmlCountPerlu: t.perlu_pemeliharaan ?? 0,
            pmlCountSedang: t.sedang_ditindak_lanjut ?? 0,
            pmlCountSelesai: t.selesai_ditindak_lanjut ?? 0,
            pmlCountDigunakan: t.digunakan_kembali ?? 0,
            pmlCountDisimpan: t.disimpan ?? 0
        };
        Object.entries(map).forEach(([id, val]) => {
            const el = document.getElementById(id);
            if (el) el.textContent = String(val);
        });
    }

    getStatusCssClass(status) {
        switch (status) {
            case 'Perlu pemeliharaan': return 'pml-status-mini--perlu';
            case 'Sedang ditindak lanjut': return 'pml-status-mini--sedang';
            case 'Selesai ditindak lanjut': return 'pml-status-mini--selesai';
            case 'Digunakan kembali': return 'pml-status-mini--digunakan';
            case 'Disimpan': return 'pml-status-mini--disimpan';
            default: return '';
        }
    }

    getKondisiBadgeClass(kondisi) {
        const k = String(kondisi || '').toLowerCase();
        if (k === 'rusak berat') return 'badge-kib--rusak-berat';
        if (k === 'rusak ringan') return 'badge-kib--rusak-ringan';
        if (k === 'hilang') return 'badge-kib--hilang';
        return 'badge-kib--baik';
    }

    renderTable() {
        const tbody = document.getElementById('pmlTableBody');
        if (!tbody) return;

        const items = this.state.items || [];
        if (items.length === 0) {
            const hasFilter = this.getActiveFilterCount() > 0;
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="kib-empty-row">
                        <i class="ph-bold ph-wrench"></i>
                        ${hasFilter
                            ? 'Tidak ada data pemeliharaan yang cocok dengan filter aktif.'
                            : 'Belum ada aset dalam daftar pemeliharaan. Catat dari Monitoring atau klik "+ Tambah Proses".'}
                    </td>
                </tr>
            `;
            return;
        }

        const page = this.state.pagination.page || 1;
        const range = this.state.pagination.range;
        const perPage = range === 'all' ? items.length : (parseInt(range, 10) || 10);
        const startNo = range === 'all' ? 1 : ((page - 1) * perPage) + 1;

        tbody.innerHTML = items.map((item, idx) => {
            const no = startNo + idx;
            const statusClass = this.getStatusCssClass(item.status);
            const kondisiBadgeClass = this.getKondisiBadgeClass(item.kondisi_display);
            const actBtn = item.can_process
                ? `<button type="button" class="pml-btn-proses" data-action="proses" data-id="${item.id}">Proses</button>`
                : `<span class="pml-done-chip">Selesai</span>`;

            return `
                <tr data-id="${item.id}">
                    <td class="cell-no">${no}</td>
                    <td class="pml-cell-code">${this.escapeHtml(item.kode_aset)}</td>
                    <td class="pml-cell-name">${this.escapeHtml(item.nama_aset)}</td>
                    <td class="cell-center"><span class="badge-mon-lokasi">${this.escapeHtml(item.lokasi)}</span></td>
                    <td class="cell-center">
                        <div class="pml-kondisi-stack">
                            <span class="badge-kib ${kondisiBadgeClass}">${this.escapeHtml(item.kondisi_display)}</span>
                            <span class="pml-status-mini ${statusClass}">${this.escapeHtml(item.status)}</span>
                        </div>
                    </td>
                    <td class="cell-center"><strong>${item.jumlah}</strong></td>
                    <td class="cell-center">${this.escapeHtml(item.tanggal_update_fmt)}</td>
                    <td class="cell-actions">
                        <div class="pml-act-wrap">
                            <button
                                type="button"
                                class="pml-btn-book"
                                title="Lihat Riwayat Tahapan"
                                aria-label="Lihat Riwayat Tahapan ${this.escapeHtml(item.nama_aset)}"
                                data-action="riwayat"
                                data-id="${item.id}"
                            >
                                <i class="ph-bold ph-book" aria-hidden="true"></i>
                            </button>
                            ${actBtn}
                            <button
                                type="button"
                                class="pml-btn-delete"
                                title="Hapus Data Pemeliharaan"
                                aria-label="Hapus Data Pemeliharaan ${this.escapeHtml(item.nama_aset)}"
                                data-action="hapus"
                                data-id="${item.id}"
                            >
                                <i class="ph-bold ph-trash" aria-hidden="true"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        tbody.querySelectorAll('[data-action="riwayat"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.getAttribute('data-id'), 10);
                this.openHistoryModal(id);
            });
        });

        tbody.querySelectorAll('[data-action="proses"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.getAttribute('data-id'), 10);
                const found = this.state.items.find(i => i.id === id);
                if (found) this.openProsesModal(found);
            });
        });

        tbody.querySelectorAll('[data-action="hapus"]').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = parseInt(btn.getAttribute('data-id'), 10);
                const found = this.state.items.find(i => i.id === id);
                if (found) this.confirmDeleteItem(found);
            });
        });
    }

    renderPagination() {
        const container = document.getElementById('pmlPagination');
        if (!container) return;

        const { page, range, total_pages: totalPages } = this.state.pagination;
        if (range === 'all' || totalPages <= 1) {
            container.innerHTML = '';
            return;
        }

        const btns = [];
        btns.push(`<button type="button" class="page-btn" ${page <= 1 ? 'disabled' : ''} data-page="${page - 1}" aria-label="Sebelumnya"><i class="ph-bold ph-caret-left"></i></button>`);

        for (let p = 1; p <= totalPages; p++) {
            btns.push(`<button type="button" class="page-btn ${p === page ? 'active' : ''}" data-page="${p}">${p}</button>`);
        }

        btns.push(`<button type="button" class="page-btn" ${page >= totalPages ? 'disabled' : ''} data-page="${page + 1}" aria-label="Berikutnya"><i class="ph-bold ph-caret-right"></i></button>`);

        container.innerHTML = btns.join('');
        container.querySelectorAll('.page-btn[data-page]').forEach(btn => {
            btn.addEventListener('click', () => {
                if (btn.disabled) return;
                const targetPage = parseInt(btn.getAttribute('data-page'), 10);
                if (targetPage >= 1 && targetPage <= totalPages) {
                    this.state.pagination.page = targetPage;
                    this.fetchList();
                }
            });
        });
    }

    getActiveFilterCount() {
        let count = 0;
        const f = this.state.filters;
        if (f.q) count++;
        if (f.nama_aset) count++;
        if (f.lokasi) count++;
        if (f.kondisi_awal) count++;
        if (f.kondisi_akhir) count++;
        if (f.status) count++;
        if (f.tgl_dari || f.tgl_sampai) count++;
        return count;
    }

    updateFilterBadge() {
        const badge = document.getElementById('pmlFilterBadge');
        const btn = document.getElementById('btnPmlFilter');
        const count = this.getActiveFilterCount();
        if (badge) {
            badge.textContent = String(count);
            badge.classList.toggle('show', count > 0);
        }
        if (btn) {
            btn.classList.toggle('has-filter', count > 0);
        }
    }

    openFilterModal() {
        this.populateFilterSelects();
        const f = this.state.filters;
        const setVal = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        };
        setVal('pmlFilterKeyword', f.q);
        setVal('pmlFilterNamaAset', f.nama_aset);
        setVal('pmlFilterLokasi', f.lokasi);
        setVal('pmlFilterKondisiAwal', f.kondisi_awal);
        setVal('pmlFilterKondisiAkhir', f.kondisi_akhir);
        setVal('pmlFilterStatus', f.status);
        setVal('pmlFilterTglDari', f.tgl_dari);
        setVal('pmlFilterTglSampai', f.tgl_sampai);

        this.openModal('pmlModalFilter', 'pmlFilterKeyword');
    }

    applyFilters() {
        const getVal = (id) => (document.getElementById(id)?.value || '').trim();
        this.state.filters = {
            q: getVal('pmlFilterKeyword'),
            nama_aset: getVal('pmlFilterNamaAset'),
            lokasi: getVal('pmlFilterLokasi'),
            kondisi_awal: getVal('pmlFilterKondisiAwal'),
            kondisi_akhir: getVal('pmlFilterKondisiAkhir'),
            status: getVal('pmlFilterStatus'),
            tgl_dari: getVal('pmlFilterTglDari'),
            tgl_sampai: getVal('pmlFilterTglSampai')
        };
        this.state.pagination.page = 1;
        this.closeModal('pmlModalFilter');
        this.fetchList();
    }

    resetFilters() {
        this.state.filters = {
            q: '',
            nama_aset: '',
            lokasi: '',
            kondisi_awal: '',
            kondisi_akhir: '',
            status: '',
            tgl_dari: '',
            tgl_sampai: ''
        };
        ['pmlFilterKeyword', 'pmlFilterNamaAset', 'pmlFilterLokasi', 'pmlFilterKondisiAwal', 'pmlFilterKondisiAkhir', 'pmlFilterStatus', 'pmlFilterTglDari', 'pmlFilterTglSampai'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        this.state.pagination.page = 1;
        this.closeModal('pmlModalFilter');
        this.fetchList();
    }

    async openAddModal() {
        this.state.mode = 'add';
        this.state.maxJumlah = null;
        this.state.selectedFiles = [];
        this.clearFormErrors();
        this.renderUploadPreviews();

        await this.fetchOpsi();

        const form = document.getElementById('pmlProsesForm');
        if (form) form.reset();

        document.getElementById('pmlFieldSourceId').value = '0';
        document.getElementById('pmlFieldKib').value = '';
        document.getElementById('pmlFieldAsetId').value = '0';
        document.getElementById('pmlFieldKode').value = '';
        document.getElementById('pmlFieldKondisiAwal').value = '';
        document.getElementById('pmlFieldJumlah').value = '1';
        document.getElementById('pmlFieldJumlah').removeAttribute('max');
        document.getElementById('pmlFieldJumlahHint').textContent = '';
        document.getElementById('pmlFieldTanggal').value = this.getTodayIso();
        this.updateTanggalPreview();

        const hiddenAset = document.getElementById('pmlFieldNamaAset');
        const searchAset = document.getElementById('pmlFieldNamaAsetSearch');
        const toggleAset = document.getElementById('pmlFieldNamaAsetToggle');
        if (hiddenAset) hiddenAset.value = '';
        if (searchAset) {
            searchAset.value = '';
            searchAset.disabled = false;
        }
        if (toggleAset) toggleAset.disabled = false;
        this.selectedAssetLabel = '';
        this.closeAssetDropdown();
        this.updateAssetClearButton();

        this.setProsesLokasi('');
        this.populateStatusSelect([]);
        this.syncKondisiAkhirRule();

        this.openModal('pmlModalProses', 'pmlFieldNamaAsetSearch');
    }

    async openProsesModal(item) {
        if (!item || !item.can_process) return;
        this.state.mode = 'process';
        this.state.maxJumlah = item.jumlah;
        this.state.selectedFiles = [];
        this.clearFormErrors();
        this.renderUploadPreviews();

        document.getElementById('pmlFieldSourceId').value = String(item.id);
        document.getElementById('pmlFieldKib').value = item.kib;
        document.getElementById('pmlFieldAsetId').value = String(item.aset_id);
        document.getElementById('pmlFieldKode').value = item.kode_aset;
        document.getElementById('pmlFieldKondisiAwal').value = item.kondisi_awal;

        const hiddenAset = document.getElementById('pmlFieldNamaAset');
        const searchAset = document.getElementById('pmlFieldNamaAsetSearch');
        const toggleAset = document.getElementById('pmlFieldNamaAsetToggle');
        this.selectedAssetLabel = item.nama_aset;
        if (hiddenAset) hiddenAset.value = 'locked';
        if (searchAset) {
            searchAset.value = item.nama_aset;
            searchAset.disabled = true;
        }
        if (toggleAset) toggleAset.disabled = true;
        this.closeAssetDropdown();
        this.updateAssetClearButton();

        this.setProsesLokasi(item.lokasi);

        const jumlahEl = document.getElementById('pmlFieldJumlah');
        const hintEl = document.getElementById('pmlFieldJumlahHint');
        if (jumlahEl) {
            jumlahEl.min = '1';
            jumlahEl.max = String(item.jumlah);
            jumlahEl.value = '1';
        }
        if (hintEl) {
            hintEl.textContent = `Sisa di tahap ini: ${item.jumlah} unit`;
        }

        this.populateStatusSelect(item.next_statuses || []);
        const kondisiAkhirEl = document.getElementById('pmlFieldKondisiAkhir');
        if (kondisiAkhirEl) kondisiAkhirEl.value = item.kondisi_akhir || '';
        this.syncKondisiAkhirRule();

        document.getElementById('pmlFieldTanggal').value = this.getTodayIso();
        this.updateTanggalPreview();

        this.openModal('pmlModalProses', 'pmlFieldStatus');
    }

    handleTambahAsetChange() {
        const namaSel = document.getElementById('pmlFieldNamaAset');
        const selectedKey = namaSel ? namaSel.value : '';
        const found = this.state.formAsetOptions.find(o => o.option_key === selectedKey);

        const sourceEl = document.getElementById('pmlFieldSourceId');
        const kibEl = document.getElementById('pmlFieldKib');
        const asetIdEl = document.getElementById('pmlFieldAsetId');
        const kodeEl = document.getElementById('pmlFieldKode');
        const kondisiAwalEl = document.getElementById('pmlFieldKondisiAwal');
        const jumlahEl = document.getElementById('pmlFieldJumlah');
        const hintEl = document.getElementById('pmlFieldJumlahHint');

        if (!found) {
            this.state.maxJumlah = null;
            if (sourceEl) sourceEl.value = '0';
            if (kibEl) kibEl.value = '';
            if (asetIdEl) asetIdEl.value = '0';
            if (kodeEl) kodeEl.value = '';
            this.setProsesLokasi('');
            if (jumlahEl) {
                jumlahEl.value = '1';
                jumlahEl.removeAttribute('max');
            }
            if (hintEl) hintEl.textContent = '';
            this.populateStatusSelect([]);
            this.syncKondisiAkhirRule();
            return;
        }

        this.state.maxJumlah = found.sisa_jumlah;
        if (sourceEl) sourceEl.value = String(found.pemeliharaan_id || 0);
        if (kibEl) kibEl.value = found.kib;
        if (asetIdEl) asetIdEl.value = String(found.aset_id);
        if (kodeEl) kodeEl.value = found.kode_aset;
        this.setProsesLokasi(found.lokasi);

        if (jumlahEl) {
            jumlahEl.min = '1';
            jumlahEl.max = String(found.sisa_jumlah);
            jumlahEl.value = '1';
        }
        if (hintEl) {
            hintEl.textContent = found.source === 'queue'
                ? `Sisa perlu pemeliharaan: ${found.sisa_jumlah} unit`
                : `Maksimal unit tersedia: ${found.sisa_jumlah} unit`;
        }

        this.populateStatusSelect(found.allowed_statuses || []);
        this.syncKondisiAkhirRule();
        this.clearSingleFieldError('aset');
    }

    populateStatusSelect(statuses) {
        const statusEl = document.getElementById('pmlFieldStatus');
        if (!statusEl) return;

        if (!statuses || statuses.length === 0) {
            statusEl.innerHTML = '<option value="">Pilih status pemeliharaan</option>';
            statusEl.value = '';
            return;
        }

        const opts = statuses.length > 1
            ? ['<option value="">Pilih status berikutnya</option>']
            : [];
        statuses.forEach(st => {
            opts.push(`<option value="${this.escapeHtml(st)}">${this.escapeHtml(st)}</option>`);
        });
        statusEl.innerHTML = opts.join('');
        statusEl.value = statuses.length === 1 ? statuses[0] : '';
    }

    syncKondisiAkhirRule() {
        const statusEl = document.getElementById('pmlFieldStatus');
        const akhirEl = document.getElementById('pmlFieldKondisiAkhir');
        const labelEl = document.getElementById('pmlLabelKondisiAkhir');
        if (!statusEl || !akhirEl) return;

        const st = statusEl.value;
        const requiresFinal = (
            st === 'Selesai ditindak lanjut' ||
            st === 'Digunakan kembali' ||
            st === 'Disimpan'
        );

        akhirEl.disabled = false;
        if (requiresFinal) {
            akhirEl.required = true;
            if (labelEl) labelEl.classList.add('required');
        } else {
            akhirEl.required = false;
            if (labelEl) labelEl.classList.remove('required');
            this.clearSingleFieldError('kondisi_akhir');
        }
    }

    updateTanggalPreview() {
        const tglEl = document.getElementById('pmlFieldTanggal');
        const previewEl = document.getElementById('pmlFieldTanggalFmt');
        if (tglEl && previewEl) {
            previewEl.textContent = this.formatShortDate(tglEl.value);
        }
    }

    clearFormErrors() {
        document.querySelectorAll('#pmlProsesForm .kib-field-error').forEach(el => {
            el.textContent = '';
            el.classList.remove('show');
        });
        document.querySelectorAll('#pmlProsesForm .has-error').forEach(el => {
            el.classList.remove('has-error');
        });
    }

    clearSingleFieldError(field) {
        const errEl = document.getElementById(`errPml_${field}`);
        if (errEl) {
            errEl.textContent = '';
            errEl.classList.remove('show');
        }
    }

    showFieldError(field, msg) {
        const errEl = document.getElementById(`errPml_${field}`);
        if (errEl) {
            errEl.textContent = msg;
            errEl.classList.add('show');
        }
        const mapInput = {
            aset: 'pmlFieldNamaAsetSearch',
            lokasi: 'pmlFieldLokasi',
            kondisi_awal: 'pmlFieldKondisiAwal',
            jumlah: 'pmlFieldJumlah',
            status: 'pmlFieldStatus',
            kondisi_akhir: 'pmlFieldKondisiAkhir',
            tanggal_update: 'pmlFieldTanggal'
        };
        const inputEl = document.getElementById(mapInput[field] || '');
        if (inputEl) inputEl.classList.add('has-error');
    }

    async handleProsesSubmit(e) {
        e.preventDefault();
        this.clearFormErrors();

        const sourceId = parseInt(document.getElementById('pmlFieldSourceId')?.value || '0', 10);
        const kib = document.getElementById('pmlFieldKib')?.value || '';
        const asetId = parseInt(document.getElementById('pmlFieldAsetId')?.value || '0', 10);
        const lokasi = (document.getElementById('pmlFieldLokasi')?.value || '').trim();
        const kondisiAwal = (document.getElementById('pmlFieldKondisiAwal')?.value || '').trim();
        const jumlah = parseInt(document.getElementById('pmlFieldJumlah')?.value || '0', 10);
        const status = (document.getElementById('pmlFieldStatus')?.value || '').trim();
        const kondisiAkhir = (document.getElementById('pmlFieldKondisiAkhir')?.value || '').trim();
        const tanggal = (document.getElementById('pmlFieldTanggal')?.value || '').trim();

        let valid = true;
        if (!kib || asetId <= 0) {
            this.showFieldError('aset', 'Nama Aset wajib dipilih.');
            valid = false;
        }
        if (!lokasi) {
            this.showFieldError('lokasi', 'Lokasi aset wajib dipilih.');
            valid = false;
        }
        if (!kondisiAwal) {
            this.showFieldError('kondisi_awal', 'Kondisi awal wajib dipilih.');
            valid = false;
        }
        if (isNaN(jumlah) || jumlah < 1) {
            this.showFieldError('jumlah', 'Jumlah minimal 1 unit.');
            valid = false;
        } else if (this.state.maxJumlah !== null && jumlah > this.state.maxJumlah) {
            this.showFieldError('jumlah', `Jumlah tidak boleh melebihi sisa unit (${this.state.maxJumlah} unit).`);
            valid = false;
        }
        if (!status) {
            this.showFieldError('status', 'Status pemeliharaan wajib dipilih.');
            valid = false;
        }
        if ((status === 'Selesai ditindak lanjut' || status === 'Digunakan kembali' || status === 'Disimpan') && !kondisiAkhir) {
            this.showFieldError('kondisi_akhir', 'Kondisi akhir wajib diisi untuk tahap ini.');
            valid = false;
        }
        if (!tanggal) {
            this.showFieldError('tanggal_update', 'Tanggal update wajib diisi.');
            valid = false;
        }
        if (!valid) return;

        const submitBtn = document.getElementById('btnPmlSimpanProses');
        const origText = submitBtn ? submitBtn.textContent : 'Simpan Proses';
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Menyimpan...';
        }

        const fd = new FormData();
        fd.append('csrf_token', this.getCsrfToken());
        fd.append('source_id', String(sourceId));
        fd.append('kib', kib);
        fd.append('aset_id', String(asetId));
        fd.append('lokasi', lokasi);
        fd.append('kondisi_awal', kondisiAwal);
        fd.append('jumlah', String(jumlah));
        fd.append('status', status);
        fd.append('kondisi_akhir', kondisiAkhir);
        fd.append('tanggal_update', tanggal);

        this.state.selectedFiles.forEach(file => {
            fd.append('dokumentasi[]', file);
        });

        try {
            const resp = await fetch(this.config.simpanUrl, {
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
                submitBtn.textContent = origText;
            }

            if (res && res.success) {
                this.closeModal('pmlModalProses');
                await Promise.all([this.fetchOpsi(), this.fetchList()]);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Proses pemeliharaan berhasil disimpan.',
                        showConfirmButton: false,
                        timer: 2400,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'pml-swal-toast'
                        }
                    });
                }
            } else {
                if (res && res.data && res.data.field) {
                    this.showFieldError(res.data.field, res.message || 'Input tidak valid.');
                }
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan Proses',
                        text: res?.message || 'Terjadi kesalahan saat menyimpan proses pemeliharaan.',
                        confirmButtonColor: '#2f6df0'
                    });
                }
            }
        } catch (err) {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = origText;
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gangguan Jaringan',
                    text: 'Tidak dapat terhubung ke server.',
                    confirmButtonColor: '#2f6df0'
                });
            }
        }
    }

    async openHistoryModal(id) {
        const subEl = document.getElementById('pmlRiwayatSubtitle');
        const bodyEl = document.getElementById('pmlRiwayatBody');
        if (subEl) subEl.textContent = 'Memuat riwayat...';
        if (bodyEl) bodyEl.innerHTML = '<div class="kib-empty-row"><i class="ph-bold ph-spinner"></i> Memuat riwayat tahapan aset...</div>';

        this.openModal('pmlModalRiwayat');

        try {
            const resp = await fetch(`${this.config.riwayatUrl}?id=${encodeURIComponent(id)}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const res = await resp.json();
            if (!res || !res.success || !res.data) {
                throw new Error(res?.message || 'Gagal memuat riwayat.');
            }

            const asset = res.data.asset || {};
            const list = res.data.riwayat || [];
            if (subEl) {
                subEl.textContent = `${asset.kode_aset || '-'} • ${asset.nama_aset || '-'}`;
            }

            if (list.length === 0) {
                if (bodyEl) {
                    bodyEl.innerHTML = '<div class="kib-empty-row">Belum ada riwayat tahapan pemeliharaan untuk aset ini.</div>';
                }
                return;
            }

            const baseUrl = this.config.baseUrl || '/';
            if (bodyEl) {
                bodyEl.innerHTML = `<div class="pml-history-list">${list.map(row => {
                    const statusClass = this.getStatusCssClass(row.status);
                    const condText = row.kondisi_akhir
                        ? `${row.kondisi_awal} → ${row.kondisi_akhir}`
                        : row.kondisi_awal;

                    const photosHtml = (row.dokumentasi && row.dokumentasi.length > 0)
                        ? `<div class="pml-history-photos">${row.dokumentasi.map(relPath => {
                            const fullUrl = baseUrl + relPath.replace(/^\/+/, '');
                            const photoLabel = `${asset.nama_aset || 'Dokumentasi'} • ${row.tanggal_update_fmt || ''}`;
                            return `<button type="button" class="pml-history-photo-btn" data-photo-url="${this.escapeHtml(fullUrl)}" data-photo-label="${this.escapeHtml(photoLabel)}" title="Lihat foto ukuran penuh"><img src="${this.escapeHtml(fullUrl)}" alt="Dokumentasi pemeliharaan"></button>`;
                        }).join('')}</div>`
                        : '';

                    return `
                        <div class="pml-history-item">
                            <div class="pml-history-top">
                                <span class="pml-history-date">${this.escapeHtml(row.tanggal_update_fmt)}</span>
                                <span class="pml-status-mini ${statusClass}">${this.escapeHtml(row.status)}</span>
                            </div>
                            <div class="pml-history-meta">
                                <span><strong>Lokasi:</strong> ${this.escapeHtml(row.lokasi)}</span>
                                <span><strong>Kondisi:</strong> ${this.escapeHtml(condText)}</span>
                                <span><strong>Jumlah:</strong> ${row.jumlah} unit</span>
                                <span><strong>Petugas:</strong> ${this.escapeHtml(row.created_by || '-')}</span>
                            </div>
                            ${photosHtml}
                        </div>
                    `;
                }).join('')}</div>`;

                bodyEl.querySelectorAll('.pml-history-photo-btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const url = btn.getAttribute('data-photo-url') || '';
                        const label = btn.getAttribute('data-photo-label') || 'Dokumentasi Pemeliharaan';
                        if (url) this.openPhotoModal(url, label);
                    });
                });
            }
        } catch (err) {
            if (bodyEl) {
                bodyEl.innerHTML = `<div class="kib-empty-row">${this.escapeHtml(err.message || 'Gagal memuat riwayat aset.')}</div>`;
            }
        }
    }

    openPhotoModal(url, subtitle = '') {
        const imgEl = document.getElementById('pmlFotoModalImg');
        const subEl = document.getElementById('pmlFotoSubtitle');
        if (imgEl) imgEl.src = url;
        if (subEl) subEl.textContent = subtitle || 'Dokumentasi Pemeliharaan';
        this.openModal('pmlModalFoto');
    }

    async confirmDeleteItem(item) {
        if (!item) return;

        let confirmed = false;
        if (typeof Swal !== 'undefined') {
            const result = await Swal.fire({
                icon: 'warning',
                title: 'Hapus Data Pemeliharaan?',
                html: `Data <strong>${this.escapeHtml(item.nama_aset)}</strong> (${this.escapeHtml(item.kode_aset)}) pada tahap <strong>${this.escapeHtml(item.status)}</strong> akan dihapus.`,
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b'
            });
            confirmed = Boolean(result.isConfirmed);
        } else {
            confirmed = window.confirm(`Hapus data pemeliharaan ${item.nama_aset} (${item.status})?`);
        }

        if (!confirmed) return;

        try {
            const fd = new FormData();
            fd.append('csrf_token', this.getCsrfToken());
            fd.append('id', String(item.id));

            const resp = await fetch(this.config.hapusUrl, {
                method: 'POST',
                body: fd,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const res = await resp.json();

            if (res && res.success) {
                await Promise.all([this.fetchOpsi(), this.fetchList()]);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Data pemeliharaan berhasil dihapus.',
                        showConfirmButton: false,
                        timer: 2400,
                        timerProgressBar: true,
                        customClass: {
                            popup: 'pml-swal-toast'
                        }
                    });
                }
            } else if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Menghapus',
                    text: res?.message || 'Data pemeliharaan tidak dapat dihapus.',
                    confirmButtonColor: '#2f6df0'
                });
            }
        } catch (err) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Gangguan Jaringan',
                    text: 'Tidak dapat terhubung ke server.',
                    confirmButtonColor: '#2f6df0'
                });
            }
        }
    }

    openModal(modalId, focusTargetId = '') {
        const overlay = document.getElementById(modalId);
        if (!overlay) return;
        overlay.classList.add('show');
        overlay.setAttribute('aria-hidden', 'false');
        if (focusTargetId) {
            setTimeout(() => {
                const target = document.getElementById(focusTargetId);
                if (target && !target.disabled) {
                    target.focus();
                }
            }, 60);
        }
    }

    closeModal(modalId) {
        const overlay = document.getElementById(modalId);
        if (!overlay) return;
        if (modalId === 'pmlModalProses') {
            this.closeAssetDropdown();
        }
        overlay.classList.remove('show');
        overlay.setAttribute('aria-hidden', 'true');
    }
}
