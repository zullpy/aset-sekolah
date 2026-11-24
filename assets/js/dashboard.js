/**
 * Dashboard - Sistem Informasi Inventaris Aset SDN Budi Karya
 * Mock data & Chart.js rendering
 */

const DashboardData = {
    kondisi: {
        baik: 245,
        rusakRingan: 48,
        rusakBerat: 18,
        hilang: 9
    },

    kategoriKIB: [
        { kode: 'KIB A', nama: 'Tanah', jumlah: 3, warna: '#3b82f6' },
        { kode: 'KIB B', nama: 'Alat dan Mesin', jumlah: 141, warna: '#22c55e' },
        { kode: 'KIB C', nama: 'Gedung dan Bangunan', jumlah: 6, warna: '#f97316' },
        { kode: 'KIB D', nama: 'Jalan, Irigasi dan Jaringan', jumlah: 3, warna: '#8b5cf6' },
        { kode: 'KIB E', nama: 'Buku', jumlah: 164, warna: '#14b8a6' },
        { kode: 'KIB F', nama: 'Konstruksi Dalam Pengerjaan', jumlah: 3, warna: '#ef4444' }
    ],

    sebaranOperator: [
        { label: 'Kelas 1', jumlah: 52, warna: '#ef4444' },
        { label: 'Kelas 2', jumlah: 51, warna: '#f97316' },
        { label: 'Kelas 3', jumlah: 58, warna: '#22c55e' },
        { label: 'Kelas 4', jumlah: 58, warna: '#3b82f6' },
        { label: 'Kelas 5', jumlah: 65, warna: '#8b5cf6' },
        { label: 'Kelas 6', jumlah: 68, warna: '#475569' }
    ],

    pemeliharaan: {
        perlu: 55,
        sedang: 10,
        selesai: 9,
        disimpan: 4
    },

    aktivitas: [
        {
            teks: 'Akun operator baru ditambahkan',
            pelaku: 'Administrator',
            waktu: '2 jam lalu',
            warna: '#3b82f6'
        },
        {
            teks: 'Data aset KIB B/2024/001 diperbarui',
            pelaku: 'Administrator',
            waktu: '2 jam lalu',
            warna: '#22c55e'
        },
        {
            teks: 'Aset KIB E/2025/002 diperbarui (Monitoring)',
            pelaku: 'Administrator',
            waktu: '2 jam lalu',
            warna: '#f97316'
        }
    ]
};

(function () {
    const D = DashboardData;
    let donutChartInstance = null;
    let barChartInstance = null;
    let liveCategories = null;
    let liveActivities = null;

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
                if (Array.isArray(res.data.activities) && res.data.activities.length > 0) {
                    liveActivities = res.data.activities;
                }
                renderDashboard();
            }
        } catch (e) {
            console.warn('Dashboard live fetch fallback to default:', e);
        }
    }

    function renderDashboard() {
        // Ambil data kategori KIB dari database MySQL atau fallback
        const kategoriKIB = liveCategories || D.kategoriKIB;

        var totalKIB = 0;
        kategoriKIB.forEach(function (k) { totalKIB += k.jumlah; });

        const totalAset = totalKIB > 0 ? totalKIB : (D.kondisi.baik + D.kondisi.rusakRingan + D.kondisi.rusakBerat + D.kondisi.hilang);

        function pct(val, total) {
            if (total === 0) return 0;
            return Math.round((val / total) * 100);
        }

        // Hitung kondisi aset terhubung proporsional dengan total aset KIB riil
        const rasioBaik = 245 / 320;
        const rasioRingan = 48 / 320;
        const rasioBerat = 18 / 320;

        const hitungBaik = Math.round(totalAset * rasioBaik);
        const hitungRingan = Math.round(totalAset * rasioRingan);
        const hitungBerat = Math.round(totalAset * rasioBerat);
        const hitungHilang = Math.max(0, totalAset - hitungBaik - hitungRingan - hitungBerat);

    // =====================================================================
    // Bagian 1: Summary Cards
    // =====================================================================
    const summaryItems = [
        {
            label: 'Total Aset',
            value: totalAset,
            desc: 'Aset terdaftar di KIB A - F',
            color: 'blue',
            icon: 'ph-bold ph-chart-bar'
        },
        {
            label: 'Baik',
            value: hitungBaik,
            desc: '(' + pct(hitungBaik, totalAset) + '%) dari total aset',
            color: 'green',
            icon: 'ph-bold ph-check-circle'
        },
        {
            label: 'Rusak Ringan',
            value: hitungRingan,
            desc: '(' + pct(hitungRingan, totalAset) + '%) dari total aset',
            color: 'orange',
            icon: 'ph-bold ph-warning'
        },
        {
            label: 'Rusak Berat',
            value: hitungBerat,
            desc: '(' + pct(hitungBerat, totalAset) + '%) dari total aset',
            color: 'red',
            icon: 'ph-bold ph-x-circle'
        },
        {
            label: 'Hilang',
            value: hitungHilang,
            desc: '(' + pct(hitungHilang, totalAset) + '%) dari total aset',
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
    // Bagian 2B: Bar Chart (Sebaran Operator)
    // =====================================================================
    var barCtx = document.getElementById('operatorBarChart');
    if (barCtx && typeof Chart !== 'undefined') {
        if (barChartInstance) {
            barChartInstance.destroy();
        }

        barChartInstance = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: D.sebaranOperator.map(function (o) { return o.label; }),
                datasets: [{
                    data: D.sebaranOperator.map(function (o) { return o.jumlah; }),
                    backgroundColor: D.sebaranOperator.map(function (o) { return o.warna; }),
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 44
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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
                                return ' ' + ctx.parsed.y + ' aset';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            stepSize: 20,
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
    // Bagian 3B: Aktivitas Terbaru (Terintegrasi Real-Time dengan Database KIB)
    // =====================================================================
    var aktivitasList = liveActivities || D.aktivitas;

    var actContainer = document.getElementById('activityList');
    if (actContainer) {
        actContainer.innerHTML = aktivitasList.map(function (a) {
            return '<div class="dash-activity-item">' +
                '<span class="dash-activity-dot" style="background:' + a.warna + '"></span>' +
                '<div class="dash-activity-body">' +
                    '<div class="dash-activity-text">"' + a.teks + '" oleh <strong>' + a.pelaku + '</strong></div>' +
                '</div>' +
                '<span class="dash-activity-time">' + a.waktu + '</span>' +
            '</div>';
        }).join('');
    }
    }

    renderDashboard();
    fetchDashboardData();
})();
