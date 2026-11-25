/**
 * Dashboard - Sistem Informasi Inventaris Aset SDN Budi Karya
 * Mock data & Chart.js rendering
 */

const DashboardData = {
    kondisi: {
        total_unit: 0,
        baik: 0,
        rusak_ringan: 0,
        rusak_berat: 0,
        hilang: 0
    },

    kategoriKIB: [
        { kode: 'KIB A', nama: 'Tanah', jumlah: 0, warna: '#3b82f6', url: 'data-aset/kib-a.php' },
        { kode: 'KIB B', nama: 'Alat dan Mesin', jumlah: 0, warna: '#22c55e', url: 'data-aset/kib-b.php' },
        { kode: 'KIB C', nama: 'Gedung dan Bangunan', jumlah: 0, warna: '#f97316', url: 'data-aset/kib-c.php' },
        { kode: 'KIB D', nama: 'Jalan, Irigasi dan Jaringan', jumlah: 0, warna: '#8b5cf6', url: 'data-aset/kib-d.php' },
        { kode: 'KIB E', nama: 'Buku', jumlah: 0, warna: '#14b8a6', url: 'data-aset/kib-e.php' },
        { kode: 'KIB F', nama: 'Konstruksi Dalam Pengerjaan', jumlah: 0, warna: '#ef4444', url: 'data-aset/kib-f.php' }
    ],

    sebaranOperator: [
        { id: 'kelas-1', label: 'Kelas 1', jumlah: 0, entri: 0, warna: '#ef4444', url: 'monitoring/kelas-1.php' },
        { id: 'kelas-2', label: 'Kelas 2', jumlah: 0, entri: 0, warna: '#f97316', url: 'monitoring/kelas-2.php' },
        { id: 'kelas-3', label: 'Kelas 3', jumlah: 0, entri: 0, warna: '#22c55e', url: 'monitoring/kelas-3.php' },
        { id: 'kelas-4', label: 'Kelas 4', jumlah: 0, entri: 0, warna: '#3b82f6', url: 'monitoring/kelas-4.php' },
        { id: 'kelas-5', label: 'Kelas 5', jumlah: 0, entri: 0, warna: '#8b5cf6', url: 'monitoring/kelas-5.php' },
        { id: 'kelas-6', label: 'Kelas 6', jumlah: 0, entri: 0, warna: '#475569', url: 'monitoring/kelas-6.php' },
        { id: 'umum', label: 'Aset Umum', jumlah: 0, entri: 0, warna: '#14b8a6', url: 'monitoring/aset-umum.php' }
    ],

    pemeliharaan: {
        perlu: 55,
        sedang: 10,
        selesai: 9,
        disimpan: 4
    },

    aktivitas: []
};

(function () {
    const D = DashboardData;
    let donutChartInstance = null;
    let barChartInstance = null;
    let liveCategories = null;
    let liveMonitoringSummary = null;
    let liveLocations = null;
    let liveActivities = null;

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatActivityTime(a, detailed) {
        if (a && a.created_at) {
            var iso = String(a.created_at).replace(' ', 'T');
            var dt = new Date(iso);
            if (!isNaN(dt.getTime())) {
                if (detailed) {
                    return dt.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    }) + ' ' + dt.toLocaleTimeString('id-ID', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
                var diffSec = Math.floor((Date.now() - dt.getTime()) / 1000);
                if (diffSec < 0 || diffSec < 60) return 'Baru saja';
                var diffMin = Math.floor(diffSec / 60);
                if (diffMin < 60) return diffMin + ' menit lalu';
                var diffHour = Math.floor(diffMin / 60);
                if (diffHour < 24) return diffHour + ' jam lalu';
                var diffDay = Math.floor(diffHour / 24);
                if (diffDay < 7) return diffDay + ' hari lalu';
                return dt.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
            }
        }
        return (a && a.waktu) ? String(a.waktu) : '-';
    }

    function getDashboardApiUrl() {
        const path = window.location.pathname;
        if (path.indexOf('/pages/') !== -1) {
            return '../includes/ajax-kib.php';
        }
        return 'includes/ajax-kib.php';
    }

    async function fetchDashboardData() {
        try {
            const response = await fetch(getDashboardApiUrl() + '?action=all', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const res = await response.json();
            if (res && res.success && res.data) {
                if (Array.isArray(res.data.summary) && res.data.summary.length > 0) {
                    liveCategories = res.data.summary;
                }
                if (res.data.monitoringSummary && typeof res.data.monitoringSummary === 'object') {
                    liveMonitoringSummary = res.data.monitoringSummary;
                }
                if (Array.isArray(res.data.monitoringLocations) && res.data.monitoringLocations.length > 0) {
                    liveLocations = res.data.monitoringLocations;
                }
                if (Array.isArray(res.data.activities)) {
                    liveActivities = res.data.activities;
                }
                renderDashboard();
            }
        } catch (e) {
            console.warn('Dashboard live fetch fallback to default:', e);
        }
    }

    function renderDashboard() {
        // Ambil data kategori KIB & kondisi monitoring riil dari database
        const kategoriKIB = liveCategories || D.kategoriKIB;
        const monKondisi = liveMonitoringSummary || D.kondisi;
        const sebaranOperator = liveLocations || D.sebaranOperator;

        var totalKIB = 0;
        kategoriKIB.forEach(function (k) { totalKIB += Number(k.jumlah || 0); });

        const totalAset = totalKIB;
        const totalTermonitor = Number(monKondisi.total_unit || 0);
        const hitungBaik = Number(monKondisi.baik || 0);
        const hitungRingan = Number(monKondisi.rusak_ringan || 0);
        const hitungBerat = Number(monKondisi.rusak_berat || 0);
        const hitungHilang = Number(monKondisi.hilang || 0);

        function pct(val, total) {
            if (total === 0) return 0;
            return Math.round((val / total) * 100);
        }

        function kondisiDesc(val) {
            if (totalTermonitor === 0) {
                return 'Belum ada data monitoring';
            }
            return '(' + pct(val, totalTermonitor) + '%) dari unit termonitor';
        }

    // =====================================================================
    // Bagian 1: Summary Cards (Terintegrasi Data Aset KIB A-F & Monitoring)
    // =====================================================================
    const totalAsetDesc = totalTermonitor > 0
        ? totalTermonitor.toLocaleString('id-ID') + ' unit termonitor (' + pct(totalTermonitor, totalAset) + '%)'
        : 'Aset terdaftar di KIB A - F';

    const summaryItems = [
        {
            label: 'Total Aset',
            value: totalAset,
            desc: totalAsetDesc,
            color: 'blue',
            icon: 'ph-bold ph-chart-bar'
        },
        {
            label: 'Baik',
            value: hitungBaik,
            desc: kondisiDesc(hitungBaik),
            color: 'green',
            icon: 'ph-bold ph-check-circle'
        },
        {
            label: 'Rusak Ringan',
            value: hitungRingan,
            desc: kondisiDesc(hitungRingan),
            color: 'orange',
            icon: 'ph-bold ph-warning'
        },
        {
            label: 'Rusak Berat',
            value: hitungBerat,
            desc: kondisiDesc(hitungBerat),
            color: 'red',
            icon: 'ph-bold ph-x-circle'
        },
        {
            label: 'Hilang',
            value: hitungHilang,
            desc: kondisiDesc(hitungHilang),
            color: 'gray',
            icon: 'ph-bold ph-minus-circle'
        }
    ];

    const summaryContainer = document.getElementById('summaryCards');
    if (summaryContainer) {
        summaryContainer.innerHTML = summaryItems.map(function (item) {
            return '<div class="dash-stat-card dash-stat-card--' + item.color + '">' +
                '<div class="dash-stat-top">' +
                    '<div class="dash-stat-icon"><i class="' + item.icon + '"></i></div>' +
                    '<span class="dash-stat-label">' + item.label + '</span>' +
                '</div>' +
                '<div class="dash-stat-value">' + item.value.toLocaleString('id-ID') + '</div>' +
                '<div class="dash-stat-desc">' + item.desc + '</div>' +
            '</div>';
        }).join('');
    }

    // =====================================================================
    // Bagian 2A: Donut Chart (Kategori KIB)
    // =====================================================================
    var donutCtx = document.getElementById('kibDonutChart');
    if (donutCtx && typeof Chart !== 'undefined') {
        var centerTextPlugin = {
            id: 'centerText',
            afterDraw: function (chart) {
                var ctx = chart.ctx;
                var w = chart.width;
                var h = chart.height;
                ctx.save();
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillStyle = '#0f172a';
                ctx.font = '800 28px "Plus Jakarta Sans", sans-serif';
                ctx.fillText(totalKIB.toLocaleString('id-ID'), w / 2, h / 2 - 8);
                ctx.fillStyle = '#94a3b8';
                ctx.font = '600 12px "Plus Jakarta Sans", sans-serif';
                ctx.fillText('Aset', w / 2, h / 2 + 16);
                ctx.restore();
            }
        };

        if (donutChartInstance) {
            donutChartInstance.destroy();
        }

        donutChartInstance = new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: kategoriKIB.map(function (k) { return k.kode + ' - ' + k.nama; }),
                datasets: [{
                    data: kategoriKIB.map(function (k) { return k.jumlah; }),
                    backgroundColor: kategoriKIB.map(function (k) { return k.warna; }),
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverBorderWidth: 0,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '65%',
                onHover: function (evt, elements) {
                    evt.native.target.style.cursor = elements && elements.length > 0 ? 'pointer' : 'default';
                },
                onClick: function (evt, elements) {
                    if (elements && elements.length > 0) {
                        var idx = elements[0].index;
                        var cat = kategoriKIB[idx];
                        if (cat && cat.url) {
                            window.location.href = cat.url;
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { family: '"Plus Jakarta Sans"', weight: '700', size: 12 },
                        bodyFont: { family: '"Plus Jakarta Sans"', size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        position: 'nearest',
                        xAlign: 'center',
                        yAlign: 'bottom',
                        callbacks: {
                            label: function (ctx) {
                                var cat = kategoriKIB[ctx.dataIndex];
                                var str = ' ' + ctx.parsed + ' aset (' + pct(ctx.parsed, totalKIB) + '%)';
                                if (cat && cat.rowCount && cat.rowCount !== cat.jumlah) {
                                    str += ' • ' + cat.rowCount + ' jenis barang';
                                }
                                return str;
                            }
                        }
                    }
                }
            },
            plugins: [centerTextPlugin]
        });
    }

    // Legend KIB Terintegrasi (Bisa Diklik Langsung Menuju Halaman KIB)
    var legendContainer = document.getElementById('kibLegend');
    if (legendContainer) {
        legendContainer.innerHTML = kategoriKIB.map(function (k) {
            var subInfo = (k.rowCount && k.rowCount !== k.jumlah)
                ? ' <span style="font-size:11px;color:#94a3b8;font-weight:500;">(' + k.rowCount + ' jenis)</span>'
                : '';
            return '<a href="' + k.url + '" class="dash-legend-item" title="Klik untuk membuka ' + k.kode + ' (' + k.nama + ')">' +
                '<span class="dash-legend-dot" style="background:' + k.warna + '"></span>' +
                '<span class="dash-legend-label">' + k.kode + ' - ' + k.nama + subInfo + '</span>' +
                '<span class="dash-legend-value">' + k.jumlah + ' <span class="dash-legend-pct">(' + pct(k.jumlah, totalKIB) + '%)</span></span>' +
            '</a>';
        }).join('');
    }

    // =====================================================================
    // Bagian 2B: Bar Chart (Sebaran Operator — Terintegrasi Monitoring)
    // =====================================================================
    var barCtx = document.getElementById('operatorBarChart');
    if (barCtx && typeof Chart !== 'undefined') {
        if (barChartInstance) {
            barChartInstance.destroy();
        }

        var maxLocUnit = 0;
        sebaranOperator.forEach(function (o) {
            if (Number(o.jumlah || 0) > maxLocUnit) {
                maxLocUnit = Number(o.jumlah || 0);
            }
        });
        var ySuggestedMax = maxLocUnit > 0 ? Math.ceil(maxLocUnit * 1.2) : 10;

        barChartInstance = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: sebaranOperator.map(function (o) { return o.label; }),
                datasets: [{
                    data: sebaranOperator.map(function (o) { return Number(o.jumlah || 0); }),
                    backgroundColor: sebaranOperator.map(function (o) { return o.warna; }),
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 44
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                onHover: function (evt, elements) {
                    if (evt && evt.native && evt.native.target) {
                        evt.native.target.style.cursor = elements && elements.length > 0 ? 'pointer' : 'default';
                    }
                },
                onClick: function (evt, elements) {
                    if (elements && elements.length > 0) {
                        var idx = elements[0].index;
                        var loc = sebaranOperator[idx];
                        if (loc && loc.url) {
                            window.location.href = loc.url;
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { family: '"Plus Jakarta Sans"', weight: '700', size: 12 },
                        bodyFont: { family: '"Plus Jakarta Sans"', size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (ctx) {
                                var loc = sebaranOperator[ctx.dataIndex];
                                var str = ' ' + ctx.parsed.y.toLocaleString('id-ID') + ' unit';
                                if (loc && loc.entri > 0) {
                                    str += ' (' + loc.entri + ' catatan)';
                                }
                                return str;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMax: ySuggestedMax,
                        ticks: {
                            precision: 0,
                            font: { family: '"Plus Jakarta Sans"', size: 11, weight: '500' },
                            color: '#94a3b8'
                        },
                        grid: {
                            color: '#f1f5f9',
                            drawBorder: false
                        },
                        border: { display: false }
                    },
                    x: {
                        ticks: {
                            font: { family: '"Plus Jakarta Sans"', size: 11, weight: '600' },
                            color: '#64748b'
                        },
                        grid: { display: false },
                        border: { display: false }
                    }
                }
            }
        });
    }

    // =====================================================================
    // Bagian 3A: Pemeliharaan Tiles
    // =====================================================================
    var maintItems = [
        {
            label: 'Perlu Pemeliharaan',
            value: D.pemeliharaan.perlu,
            color: 'red',
            icon: 'ph-bold ph-warning-octagon'
        },
        {
            label: 'Sedang Ditindak Lanjut',
            value: D.pemeliharaan.sedang,
            color: 'blue',
            icon: 'ph-bold ph-gear'
        },
        {
            label: 'Selesai Ditindak Lanjut',
            value: D.pemeliharaan.selesai,
            color: 'orange',
            icon: 'ph-bold ph-wrench'
        },
        {
            label: 'Disimpan',
            value: D.pemeliharaan.disimpan,
            color: 'purple',
            icon: 'ph-bold ph-archive-box'
        }
    ];

    var maintContainer = document.getElementById('maintCards');
    if (maintContainer) {
        maintContainer.innerHTML = maintItems.map(function (item) {
            return '<div class="dash-maint-tile dash-maint-tile--' + item.color + '">' +
                '<div class="dash-maint-icon"><i class="' + item.icon + '"></i></div>' +
                '<div class="dash-maint-label">' + item.label + '</div>' +
                '<div class="dash-maint-value">' + item.value + '</div>' +
                '<div class="dash-maint-unit">aset</div>' +
            '</div>';
        }).join('');
    }

    // =====================================================================
    // Bagian 3B: Aktivitas Terbaru (Maksimal 5 Item di Dashboard)
    // =====================================================================
    var aktivitasList = (liveActivities || D.aktivitas).slice(0, 3);

    var actContainer = document.getElementById('activityList');
    if (actContainer) {
        if (aktivitasList.length === 0) {
            actContainer.innerHTML = '<div class="dash-activity-item"><div class="dash-activity-body"><div class="dash-activity-text" style="color:#94a3b8;">Belum ada aktivitas tercatat.</div></div></div>';
        } else {
            actContainer.innerHTML = aktivitasList.map(function (a) {
                return '<div class="dash-activity-item">' +
                    '<span class="dash-activity-dot" style="background:' + escapeHtml(a.warna || '#3b82f6') + '"></span>' +
                    '<div class="dash-activity-body">' +
                        '<div class="dash-activity-text">"' + escapeHtml(a.teks) + '" oleh <strong>' + escapeHtml(a.pelaku) + '</strong></div>' +
                    '</div>' +
                    '<span class="dash-activity-time">' + escapeHtml(formatActivityTime(a, false)) + '</span>' +
                '</div>';
            }).join('');
        }
    }
    }

    // =====================================================================
    // Modal Lihat Semua Aktivitas
    // =====================================================================
    var actModalState = {
        items: [],
        total: 0,
        offset: 0,
        limit: 30,
        hasMore: false,
        query: '',
        loading: false,
        searchTimer: null
    };

    function renderAllActivitiesModalList() {
        var listEl = document.getElementById('dashAllActivitiesList');
        var countEl = document.getElementById('dashActModalCount');
        var loadMoreWrap = document.getElementById('dashActLoadMoreWrap');
        var loadMoreBtn = document.getElementById('btnActLoadMore');
        if (!listEl) return;

        if (countEl) {
            countEl.textContent = actModalState.total > 0
                ? 'Menampilkan ' + actModalState.items.length + ' dari ' + actModalState.total + ' aktivitas'
                : 'Tidak ada aktivitas';
        }

        if (actModalState.items.length === 0) {
            listEl.innerHTML = '<div class="dash-activity-item"><div class="dash-activity-body"><div class="dash-activity-text" style="color:#94a3b8;text-align:center;">' +
                (actModalState.query ? 'Tidak ada aktivitas yang cocok dengan pencarian.' : 'Belum ada aktivitas tercatat.') +
                '</div></div></div>';
        } else {
            listEl.innerHTML = actModalState.items.map(function (a) {
                return '<div class="dash-activity-item">' +
                    '<span class="dash-activity-dot" style="background:' + escapeHtml(a.warna || '#3b82f6') + '"></span>' +
                    '<div class="dash-activity-body">' +
                        '<div class="dash-activity-text">"' + escapeHtml(a.teks) + '" oleh <strong>' + escapeHtml(a.pelaku) + '</strong></div>' +
                    '</div>' +
                    '<span class="dash-activity-time" title="' + escapeHtml(formatActivityTime(a, true)) + '">' + escapeHtml(formatActivityTime(a, true)) + '</span>' +
                '</div>';
            }).join('');
        }

        if (loadMoreWrap) {
            loadMoreWrap.style.display = actModalState.hasMore ? 'block' : 'none';
        }
        if (loadMoreBtn) {
            loadMoreBtn.disabled = actModalState.loading;
            loadMoreBtn.textContent = actModalState.loading ? 'Memuat...' : 'Muat Lebih Banyak';
        }
    }

    async function fetchAllActivities(reset) {
        if (actModalState.loading) return;
        actModalState.loading = true;

        var listEl = document.getElementById('dashAllActivitiesList');
        var loadMoreBtn = document.getElementById('btnActLoadMore');
        if (reset) {
            actModalState.offset = 0;
            actModalState.items = [];
            if (listEl) {
                listEl.innerHTML = '<div class="dash-activity-item"><div class="dash-activity-body"><div class="dash-activity-text" style="color:#64748b;text-align:center;">Memuat riwayat aktivitas...</div></div></div>';
            }
        } else if (loadMoreBtn) {
            loadMoreBtn.disabled = true;
            loadMoreBtn.textContent = 'Memuat...';
        }

        try {
            var url = getDashboardApiUrl() +
                '?action=activities&limit=' + actModalState.limit +
                '&offset=' + actModalState.offset +
                (actModalState.query ? '&q=' + encodeURIComponent(actModalState.query) : '');
            var resp = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            var res = await resp.json();
            if (res && res.success && res.data) {
                var batch = Array.isArray(res.data.activities) ? res.data.activities : [];
                actModalState.items = reset ? batch : actModalState.items.concat(batch);
                actModalState.total = Number(res.data.total ?? actModalState.items.length);
                actModalState.hasMore = Boolean(res.data.hasMore);
                actModalState.offset = actModalState.items.length;
            }
        } catch (e) {
            console.warn('Gagal memuat riwayat aktivitas:', e);
        } finally {
            actModalState.loading = false;
            renderAllActivitiesModalList();
        }
    }

    function openAllActivitiesModal() {
        var modal = document.getElementById('dashActivitiesModal');
        var searchInput = document.getElementById('dashActSearchInput');
        if (!modal) return;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        actModalState.query = '';
        if (searchInput) searchInput.value = '';
        fetchAllActivities(true);
    }

    function closeAllActivitiesModal() {
        var modal = document.getElementById('dashActivitiesModal');
        if (!modal) return;
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function bindActivityModalEvents() {
        var btnOpen = document.getElementById('btnAllActivities');
        var btnClose = document.getElementById('btnCloseActModal');
        var modal = document.getElementById('dashActivitiesModal');
        var searchInput = document.getElementById('dashActSearchInput');
        var btnLoadMore = document.getElementById('btnActLoadMore');

        if (btnOpen) {
            btnOpen.addEventListener('click', openAllActivitiesModal);
        }
        if (btnClose) {
            btnClose.addEventListener('click', closeAllActivitiesModal);
        }
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeAllActivitiesModal();
                }
            });
        }
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(actModalState.searchTimer);
                actModalState.searchTimer = setTimeout(function () {
                    actModalState.query = searchInput.value.trim();
                    fetchAllActivities(true);
                }, 220);
            });
        }
        if (btnLoadMore) {
            btnLoadMore.addEventListener('click', function () {
                fetchAllActivities(false);
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal && modal.classList.contains('show')) {
                closeAllActivitiesModal();
            }
        });
    }

    bindActivityModalEvents();
    renderDashboard();
    fetchDashboardData();
})();
