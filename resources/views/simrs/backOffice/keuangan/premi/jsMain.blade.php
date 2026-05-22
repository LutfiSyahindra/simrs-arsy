<script>
    $(document).ready(function() {

        function animateNumber(el, start, end, duration = 600) {
            const range = end - start;
            const startTime = performance.now();

            function step(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);

                const value = Math.floor(start + (range * progress));
                el.textContent = 'Rp ' + value.toLocaleString('id-ID');

                if (progress < 1) {
                    requestAnimationFrame(step);
                }
            }

            requestAnimationFrame(step);
        }


        // ===============================
        // DATE RANGE PICKER & GLOBAL VARIABLE
        // ===============================
        let startDate = moment().startOf('month');
        let endDate = moment();
        let premiDokterChart = null;
        let premiParamedisChart = null;
        let premiKamarChart = null;
        let premiRsChart = null;
        let tableDetailPremi;
        let currentLayanan = '';
        let currentSumber = '';
        let currentKode = '';
        let currentJenis = '';
        let modeKamar = 'grouped';
        let currentMode = 'umum';

        let tablePremiDokter = null;
        let tablePremiParamedis = null;
        let tablePremiKamarInap = null;
        let tablePremiRs = null;

        let loadedTable = {
            dokter: false,
            paramedis: false,
            kamar: false,
            rs: false
        };

        let reloadTimer = null;


        $('#date-range').daterangepicker({
            startDate: startDate,
            endDate: endDate,
            autoUpdateInput: true,
            locale: {
                format: 'YYYY-MM-DD',
                applyLabel: 'Pilih',
                cancelLabel: 'Batal'
            }
        });

        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
            let tables = $.fn.dataTable.tables({
                visible: true,
                api: true
            });

            tables.columns.adjust();

            tables.each(function() {
                if (this.responsive) {
                    this.responsive.recalc();
                }
            });
        });

        function buildFilter(jenis) {
            const picker = $('#date-range').data('daterangepicker');

            if (!picker) {
                Swal.fire('Error', 'Tanggal belum dipilih', 'error');
                return null;
            }

            return {
                jenis: jenis,
                tgl_awal: picker.startDate.format('YYYY-MM-DD'),
                tgl_akhir: picker.endDate.format('YYYY-MM-DD'),
                status_bayar: $('#filterStatusBayar').val(),
                status_rawat: $('#filterStatusRawat').val(),
                penjamin: $('#filterPenjamin').val(),
                sumber: $('#filterSumber').val(),
                layanan: $('#filterLayanan').val(),
            };
        }

        function applyFilterToDatatable(d, jenis) {
            const filter = buildFilter(jenis);
            if (!filter) return;

            Object.assign(d, filter);
        }

        function reloadAllTable() {
            clearTimeout(reloadTimer);

            reloadTimer = setTimeout(function() {
                if (tablePremiDokter) tablePremiDokter.ajax.reload(null, false);
                if (tablePremiParamedis) tablePremiParamedis.ajax.reload(null, false);
                if (tablePremiKamarInap) tablePremiKamarInap.ajax.reload(null, false);
                if (tablePremiRs) tablePremiRs.ajax.reload(null, false);

                reloadPremiDokterMiniTotal();
                reloadPremiParamedisMiniTotal();
                reloadPremiKamarMiniTotal();
                reloadPremiRsMiniTotal();

                loadChartDashboardStaggered();
            }, 400);
        }

        // ===============================
        // DATATABLE PREMI
        // ===============================

        function renderTheadDetailPremi(mode) {
            let html = '<tr>';

            if (mode === 'kamar') {
                html += `
                <th>No</th>
                <th>No Rawat</th>
                <th>Tgl Masuk</th>
                <th>Tgl Keluar</th>
                <th>Nama Kamar</th>
                <th>Lama</th>
                <th>Status</th>
                <th class="text-end">Premi (Rp)</th>
            `;
            } else if (mode === 'rs') {
                html += `
                <th>No</th>
                <th>Tanggal</th>
                <th>Sumber</th>
                <th class="text-end">Premi RS (Rp)</th>
            `;
            } else {
                html += `
                <th>No</th>
                <th>Tanggal</th>
                <th>No Rawat</th>
                <th>Tindakan</th>
                <th>Layanan</th>
                <th class="text-end">Premi (Rp)</th>
            `;
            }

            html += '</tr>';

            $('#tableDetailPremi thead').html(html);
        }

        function initTableDokter() {
            if (tablePremiDokter) return;
            tablePremiDokter = $('#tablePremiDokter').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: "{{ route("backOffice.keuangan.premi.getPremiTable") }}",
                    type: "GET",
                    data: function(d) {
                        applyFilterToDatatable(d, 'dokter');
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nama',
                        name: 'nama'
                    },
                    {
                        data: 'premi',
                        className: 'text-end'
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ]
            });

        }

        function initTableParamedis() {
            if (tablePremiParamedis) return;
            tablePremiParamedis = $('#tablePremiParamedis').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: "{{ route("backOffice.keuangan.premi.getPremiTable") }}",
                    type: "GET",
                    data: function(d) {
                        applyFilterToDatatable(d, 'paramedis');
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'nama',
                        name: 'nama'
                    },
                    {
                        data: 'premi',
                        className: 'text-end'
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ]
            });
        }

        function initTableRs() {
            if (tablePremiRs) return;
            tablePremiRs = $('#tablePremiRs').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: "{{ route("backOffice.keuangan.premi.getPremiTable") }}",
                    type: "GET",
                    data: function(d) {
                        applyFilterToDatatable(d, 'rs');
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'tanggal',
                        name: 'tanggal'
                    },
                    {
                        data: 'premi',
                        className: 'text-end'
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ]
            });
        }

        function initTableKamar() {
            if (tablePremiKamarInap) return;
            tablePremiKamarInap = $('#tablePremiKamarInap').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                ajax: {
                    url: "{{ route("backOffice.keuangan.premi.getPremiTable") }}",
                    type: "GET",
                    data: function(d) {
                        applyFilterToDatatable(d, 'kamar_inap');
                        d.view_mode = modeKamar; // 🔥 kirim mode ke backend
                    }
                },
                columns: modeKamar === 'grouped' ? [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'kode',
                        name: 'kode'
                    },
                    {
                        data: 'premi',
                        className: 'text-end'
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ] : [{
                        data: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'no_rawat'
                    },
                    {
                        data: 'nama'
                    },
                    {
                        data: 'kode'
                    },
                    {
                        data: 'tgl_masuk'
                    },
                    {
                        data: 'lama'
                    },
                    {
                        data: 'premi',
                        className: 'text-end'
                    }
                ]
            });
            tablePremiKamarInap.columns.adjust();
        }

        function updateHeaderKamar() {

            let headerGrouped = `
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Total Premi</th>
                    <th>Aksi</th>
                </tr>
            `;

            let headerAll = `
                <tr>
                    <th>No</th>
                    <th>No Rawat</th>
                    <th>Nama Pasien</th>
                    <th>Kamar</th>
                    <th>Tanggal Masuk</th>
                    <th>Lama</th>
                    <th>Premi</th>
                </tr>
            `;

            $('#tablePremiKamarInap thead').html(
                modeKamar === 'grouped' ? headerGrouped : headerAll
            );
        }

        window.toggleKamarView = function() {

            if ($.fn.DataTable.isDataTable('#tablePremiKamarInap')) {
                tablePremiKamarInap.clear().destroy();
            }

            tablePremiKamarInap = null;

            // 🔥 RESET STYLE LAMA
            $('#tablePremiKamarInap')
                .removeAttr('style')
                .css('width', '100%');

            if (modeKamar === 'grouped') {
                modeKamar = 'all';
                $('#btnToggleKamarView').text('Tampilkan Grouped');
            } else {
                modeKamar = 'grouped';
                $('#btnToggleKamarView').text('Tampilkan Semua Data');
            }

            updateHeaderKamar();
            initTableKamar();
        }

        window.detailPremi = function(kode, jenis, nama = '', mode = 'umum') {

            currentKode = kode;
            currentJenis = jenis;
            currentMode = mode;
            currentSumber = '';
            currentLayanan = '';

            // ================= HEADER =================
            $('#namaPegawai').text(nama);
            $('#avatarPegawai').text(nama ? nama.charAt(0).toUpperCase() : '?');

            let picker = $('#date-range').data('daterangepicker');
            $('#periodePremi').text(
                picker.startDate.format('DD MMM YYYY') + ' - ' +
                picker.endDate.format('DD MMM YYYY')
            );

            $('#modalDetailPremi').modal('show');

            // ================= RESET SUMMARY =================
            $('#totalPremiTab').text('Rp 0');
            $('#jumlahData').text('0');

            // ================= MODE HANDLING =================
            if (currentMode === 'kamar') {
                $('#tabDetailPremi').hide();

                $('#badgeKategori')
                    .removeClass()
                    .addClass('badge bg-info')
                    .text('Kamar Inap');

                currentSumber = 'KAMAR';

            } else if (currentMode === 'rs') {
                $('#tabDetailPremi').show();

                $('#tabDetailPremi .tab-layanan').hide();
                $('#tabDetailPremi [data-sumber="RAWAT"]').closest('.nav-item').hide();
                $('#tabDetailPremi .tab-rs').show();

                $('#badgeKategori')
                    .removeClass()
                    .addClass('badge bg-success')
                    .text('Rumah Sakit');

                $('#tabDetailPremi .nav-link')
                    .removeClass('active')
                    .filter('[data-sumber=""]')
                    .addClass('active');

                currentSumber = '';
                currentLayanan = '';
            } else {
                $('#tabDetailPremi').show();

                $('#tabDetailPremi .tab-layanan').show();
                $('#tabDetailPremi [data-sumber="RAWAT"]').closest('.nav-item').show();
                $('#tabDetailPremi .tab-rs').hide();

                $('#badgeKategori')
                    .removeClass()
                    .addClass('badge bg-secondary')
                    .text('Semua');

                $('#tabDetailPremi .nav-link')
                    .removeClass('active')
                    .filter('[data-sumber=""]')
                    .addClass('active');
            }

            // ================= DESTROY TABLE =================
            if ($.fn.DataTable.isDataTable('#tableDetailPremi')) {
                $('#tableDetailPremi').DataTable().clear().destroy();
            }

            renderTheadDetailPremi(currentMode);

            // ================= COLUMN CONFIG =================
            const formatRupiah = function(data, type) {
                let val = parseInt(String(data).replace(/[^\d]/g, '')) || 0;
                return (type === 'display' || type === 'filter') ?
                    val.toLocaleString('id-ID') :
                    val;
            };

            let columnsUmum = [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'tanggal'
                },
                {
                    data: 'no_rawat'
                },
                {
                    data: 'tindakan'
                },
                {
                    data: 'layanan'
                },
                {
                    data: 'nilai',
                    className: 'text-end',
                    render: formatRupiah
                }
            ];

            let columnsKamar = [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'no_rawat'
                },
                {
                    data: 'tanggal'
                },
                {
                    data: 'tgl_keluar'
                },
                {
                    data: 'kd_kamar'
                },
                {
                    data: 'lama'
                },
                {
                    data: 'stts_pulang'
                },
                {
                    data: 'nilai',
                    className: 'text-end',
                    render: formatRupiah
                }
            ];

            let columnsRs = [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'tanggal'
                },
                {
                    data: 'sumber'
                },
                {
                    data: 'nilai',
                    className: 'text-end',
                    render: formatRupiah
                }
            ];

            let selectedColumns = columnsUmum;

            if (currentMode === 'kamar') {
                selectedColumns = columnsKamar;
            } else if (currentMode === 'rs') {
                selectedColumns = columnsRs;
            }

            // ================= INIT DATATABLE =================
            tableDetailPremi = $('#tableDetailPremi').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ordering: false,

                ajax: {
                    url: "{{ route("backOffice.keuangan.premi.getPremiDetail") }}",
                    type: "GET",
                    data: function(d) {
                        d.kode = currentKode;
                        d.jenis = currentJenis;
                        d.mode = currentMode;
                        d.sumber = currentSumber;
                        d.layanan = currentLayanan;
                        d.tgl_awal = picker.startDate.format('YYYY-MM-DD');
                        d.tgl_akhir = picker.endDate.format('YYYY-MM-DD');
                        d.status_bayar = $('#filterStatusBayar').val();
                        d.status_rawat = $('#filterStatusRawat').val();
                        d.penjamin = $('#filterPenjamin').val();
                    }
                },

                columns: selectedColumns
            });

            // ================= RESPONSE HANDLER =================
            tableDetailPremi.on('xhr.dt', function(e, settings, json) {
                if (!json) return;

                $('#totalPremiTab').text(
                    'Rp ' + Number(json.total_premi || 0).toLocaleString('id-ID')
                );

                $('#jumlahData').text(json.recordsFiltered || 0);

                if (currentMode === 'kamar') return;

                let label = 'Semua';
                if (currentLayanan) label = currentLayanan;
                else if (currentSumber) label = currentSumber;

                let badge = $('#badgeKategori');
                badge.removeClass().addClass('badge');

                if (label.includes('Rawat')) badge.addClass('bg-primary');
                else if (label === 'Operasi') badge.addClass('bg-danger');
                else if (label === 'LAB') badge.addClass('bg-success');
                else if (label === 'RADIOLOGI') badge.addClass('bg-info');
                else badge.addClass('bg-secondary');

                badge.text(label);
            });
        };

        $(document).on('click', '#tabDetailPremi .nav-link', function(e) {
            e.preventDefault();

            $('#tabDetailPremi .nav-link').removeClass('active');
            $(this).addClass('active');

            currentSumber = $(this).data('sumber') || '';
            currentLayanan = currentMode === 'rs' ? '' : ($(this).data('layanan') || '');

            if (tableDetailPremi) {
                tableDetailPremi.ajax.reload();
            }
        });

        // ===============================
        // Grafik Premi Dokter
        // ===============================
        function initPremiChart(labels = [], thisMonth = [], lastMonth = []) {

            const dataThis = labels.map((tgl, i) => ({
                x: Date.parse(tgl + 'T00:00:00Z'),
                y: Number(thisMonth[i] || 0)
            }));

            const dataLast = labels.map((tgl, i) => ({
                x: Date.parse(tgl + 'T00:00:00Z'),
                y: Number(lastMonth[i] || 0)
            }));

            // 🔢 total & tren
            const totalThis = thisMonth.reduce((a, b) => a + b, 0);
            const totalLast = lastMonth.reduce((a, b) => a + b, 0);
            const isUp = totalThis >= totalLast;

            const options = {
                chart: {
                    type: 'area',
                    height: 90,
                    sparkline: {
                        enabled: true
                    },
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    },

                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: {
                            enabled: false
                        },
                        dynamicAnimation: {
                            enabled: true,
                            speed: 600
                        }
                    }
                },

                series: [{
                        name: 'Bulan Ini',
                        data: dataThis
                    },
                    {
                        name: 'Bulan Lalu',
                        data: dataLast
                    }
                ],

                // 🎯 garis fokus & konteks
                stroke: {
                    curve: 'smooth',
                    width: [2.5, 1],
                    dashArray: [0, 6]
                },

                // 🌊 area clean & terbaca
                fill: {
                    type: ['gradient', 'solid'],
                    opacity: [0.35, 0.14],
                    gradient: {
                        shadeIntensity: 0.6,
                        opacityFrom: 0.4,
                        opacityTo: 0.18,
                        stops: [0, 90, 100]
                    }
                },

                // 🎨 warna adaptif
                colors: [
                    isUp ? '#2563EB' : '#DC2626', // bulan ini
                    '#94A3B8' // bulan lalu
                ],

                xaxis: {
                    type: 'datetime'
                },

                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: val =>
                            'Rp ' + val.toLocaleString('id-ID')
                    }
                },

                grid: {
                    show: false
                }
            };

            premiDokterChart = new ApexCharts(
                document.querySelector('#premiDokterChart'),
                options
            );

            premiDokterChart.render();
        }

        function reloadPremiChart() {
            let picker = $('#date-range').data('daterangepicker');

            $.ajax({
                url: "{{ route("backOffice.keuangan.premi.getPremiDokterChartOverlay") }}",
                type: "GET",
                data: {
                    tgl_awal: picker.startDate.format('YYYY-MM-DD'),
                    tgl_akhir: picker.endDate.format('YYYY-MM-DD'),
                    status_bayar: $('#filterStatusBayar').val(),
                    status_rawat: $('#filterStatusRawat').val(),
                    penjamin: $('#filterPenjamin').val()
                },
                success: function(res) {
                    updatePremiChartOverlay(
                        res.labels,
                        res.this_month,
                        res.last_month
                    );
                }
            });
        }

        function updatePremiChartOverlay(labels, thisMonth, lastMonth) {
            // 🔥 destroy + init = animasi pasti muncul
            if (premiDokterChart) {
                premiDokterChart.destroy();
                premiDokterChart = null;
            }
            initPremiChart(labels, thisMonth, lastMonth);
        }

        function reloadPremiDokterSummary() {

            let picker = $('#date-range').data('daterangepicker');

            $.ajax({
                url: "{{ route("backOffice.keuangan.premi.getPremiDokterSummary") }}",
                type: "GET",
                data: {
                    tgl_awal: picker.startDate.format('YYYY-MM-DD'),
                    tgl_akhir: picker.endDate.format('YYYY-MM-DD'),
                    status_bayar: $('#filterStatusBayar').val(),
                    status_rawat: $('#filterStatusRawat').val(),
                    penjamin: $('#filterPenjamin').val()
                },
                success: function(res) {

                    $('#totalPremiDokter').text(
                        'Rp ' + Number(res.total_periode).toLocaleString('id-ID')
                    );

                    const selisih = Number(res.selisih_abs).toLocaleString('id-ID');
                    const persen = Math.abs(res.persen).toFixed(2);
                    const el = $('#premiDokterGrowth');

                    el.removeClass('text-success text-danger').html('');

                    if (res.naik) {
                        el.addClass('text-success').html(`
                    Naik Rp ${selisih} (${persen}%)
                    <i data-feather="arrow-up" class="icon-sm mb-1"></i>
                `);
                    } else {
                        el.addClass('text-danger').html(`
                    Turun Rp ${selisih} (${persen}%)
                    <i data-feather="arrow-down" class="icon-sm mb-1"></i>
                `);
                    }

                    feather.replace();
                }
            });
        }

        function reloadPremiDokterMiniTotal() {

            let picker = $('#date-range').data('daterangepicker');
            const el = document.getElementById('totalPremiDokterMini');

            // ambil nilai lama (kalau ada)
            const currentValue =
                Number(el.dataset.value || 0);

            $.ajax({
                url: "{{ route("backOffice.keuangan.premi.getPremiDokterChart") }}",
                type: "GET",
                data: {
                    tgl_awal: picker.startDate.format('YYYY-MM-DD'),
                    tgl_akhir: picker.endDate.format('YYYY-MM-DD'),
                    status_bayar: $('#filterStatusBayar').val(),
                    status_rawat: $('#filterStatusRawat').val(),
                    penjamin: $('#filterPenjamin').val()
                },
                success: function(res) {

                    const newValue = Number(res.total || 0);

                    // 🔥 animasi angka
                    animateNumber(el, currentValue, newValue, 700);

                    // simpan nilai terakhir
                    el.dataset.value = newValue;
                }
            });
        }

        // ===============================
        // Grafik Premi Paramedis
        // ===============================
        function initPremiParamedisChart(labels = [], thisMonth = [], lastMonth = []) {

            const dataThis = labels.map((tgl, i) => ({
                x: Date.parse(tgl + 'T00:00:00Z'),
                y: Number(thisMonth[i] || 0)
            }));

            const dataLast = labels.map((tgl, i) => ({
                x: Date.parse(tgl + 'T00:00:00Z'),
                y: Number(lastMonth[i] || 0)
            }));

            // 🔢 total & tren
            const totalThis = thisMonth.reduce((a, b) => a + b, 0);
            const totalLast = lastMonth.reduce((a, b) => a + b, 0);
            const isUp = totalThis >= totalLast;

            const options = {
                chart: {
                    type: 'area',
                    height: 90,
                    sparkline: {
                        enabled: true
                    },
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    },

                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: {
                            enabled: false
                        },
                        dynamicAnimation: {
                            enabled: true,
                            speed: 600
                        }
                    }
                },

                series: [{
                        name: 'Bulan Ini',
                        data: dataThis
                    },
                    {
                        name: 'Bulan Lalu',
                        data: dataLast
                    }
                ],

                // 🎯 garis fokus & konteks
                stroke: {
                    curve: 'smooth',
                    width: [2.5, 1],
                    dashArray: [0, 6]
                },

                // 🌊 area clean & terbaca
                fill: {
                    type: ['gradient', 'solid'],
                    opacity: [0.35, 0.14],
                    gradient: {
                        shadeIntensity: 0.6,
                        opacityFrom: 0.4,
                        opacityTo: 0.18,
                        stops: [0, 90, 100]
                    }
                },

                // 🎨 warna adaptif
                colors: [
                    isUp ? '#2563EB' : '#DC2626', // bulan ini
                    '#94A3B8' // bulan lalu
                ],

                xaxis: {
                    type: 'datetime'
                },

                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: val =>
                            'Rp ' + val.toLocaleString('id-ID')
                    }
                },

                grid: {
                    show: false
                }
            };

            premiParamedisChart = new ApexCharts(
                document.querySelector('#premiParamedisChart'),
                options
            );

            premiParamedisChart.render();
        }

        function reloadPremiParamedisChart() {

            let picker = $('#date-range').data('daterangepicker');

            $.ajax({
                url: "{{ route("backOffice.keuangan.premi.getPremiParamedisChartOverlay") }}",
                type: "GET",
                data: {
                    tgl_awal: picker.startDate.format('YYYY-MM-DD'),
                    tgl_akhir: picker.endDate.format('YYYY-MM-DD'),
                    status_bayar: $('#filterStatusBayar').val(),
                    status_rawat: $('#filterStatusRawat').val(),
                    penjamin: $('#filterPenjamin').val()
                },
                success: function(res) {
                    updatePremiParamedisChartOverlay(
                        res.labels,
                        res.this_month,
                        res.last_month
                    );
                }
            });
        }

        function updatePremiParamedisChartOverlay(labels, thisMonth, lastMonth) {

            // 🔥 HAPUS CHART LAMA (agar animasi selalu muncul)
            if (premiParamedisChart) {
                premiParamedisChart.destroy();
                premiParamedisChart = null;
            }

            // 🔥 INIT ULANG (animasi pasti jalan)
            initPremiParamedisChart(labels, thisMonth, lastMonth);
        }

        function reloadPremiParamedisSummary() {

            let picker = $('#date-range').data('daterangepicker');

            $.ajax({
                url: "{{ route("backOffice.keuangan.premi.getPremiParamedisSummary") }}",
                type: "GET",
                data: {
                    tgl_awal: picker.startDate.format('YYYY-MM-DD'),
                    tgl_akhir: picker.endDate.format('YYYY-MM-DD'),
                    status_bayar: $('#filterStatusBayar').val(),
                    status_rawat: $('#filterStatusRawat').val(),
                    penjamin: $('#filterPenjamin').val()
                },
                success: function(res) {

                    const selisih = Number(res.selisih_abs).toLocaleString('id-ID');
                    const persen = Math.abs(res.persen).toFixed(2);
                    const el = $('#premiParamedisGrowth');

                    // 🔥 INI YANG KEMARIN KURANG
                    $('#totalPremiParamedis').text(
                        'Rp ' + Number(res.total_periode).toLocaleString('id-ID')
                    );

                    // 🔥 RESET
                    el.removeClass('text-success text-danger').html('');

                    if (res.naik) {
                        // NAIK
                        el.addClass('text-success');
                        el.html(`
                    Naik Rp ${selisih} (${persen}%)
                    <i data-feather="arrow-up" class="icon-sm mb-1"></i>
                `);
                    } else {
                        // TURUN
                        el.addClass('text-danger');
                        el.html(`
                    Turun Rp ${selisih} (${persen}%)
                    <i data-feather="arrow-down" class="icon-sm mb-1"></i>
                `);
                    }

                    feather.replace();
                }
            });
        }

        function reloadPremiParamedisMiniTotal() {

            let picker = $('#date-range').data('daterangepicker');
            const el = document.getElementById('totalPremiParamedisMini');

            // ambil nilai lama (kalau ada)
            const currentValue =
                Number(el.dataset.value || 0);

            $.ajax({
                url: "{{ route("backOffice.keuangan.premi.getPremiParamedisChart") }}",
                type: "GET",
                data: {
                    tgl_awal: picker.startDate.format('YYYY-MM-DD'),
                    tgl_akhir: picker.endDate.format('YYYY-MM-DD'),
                    status_bayar: $('#filterStatusBayar').val(),
                    status_rawat: $('#filterStatusRawat').val(),
                    penjamin: $('#filterPenjamin').val()
                },
                success: function(res) {

                    const newValue = Number(res.total || 0);

                    // 🔥 animasi angka
                    animateNumber(el, currentValue, newValue, 700);

                    // simpan nilai terakhir
                    el.dataset.value = newValue;
                }
            });
        }

        // ===============================
        // Grafik Premi Kamar
        // ===============================
        function initPremiKamarChart(labels = [], thisMonth = [], lastMonth = []) {

            const dataThis = labels.map((tgl, i) => ({
                x: Date.parse(tgl + 'T00:00:00Z'),
                y: Number(thisMonth[i] || 0)
            }));

            const dataLast = labels.map((tgl, i) => ({
                x: Date.parse(tgl + 'T00:00:00Z'),
                y: Number(lastMonth[i] || 0)
            }));

            // 🔢 total & tren
            const totalThis = thisMonth.reduce((a, b) => a + b, 0);
            const totalLast = lastMonth.reduce((a, b) => a + b, 0);
            const isUp = totalThis >= totalLast;

            const options = {
                chart: {
                    type: 'area',
                    height: 90,
                    sparkline: {
                        enabled: true
                    },
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    },

                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: {
                            enabled: false
                        },
                        dynamicAnimation: {
                            enabled: true,
                            speed: 600
                        }
                    }
                },

                series: [{
                        name: 'Bulan Ini',
                        data: dataThis
                    },
                    {
                        name: 'Bulan Lalu',
                        data: dataLast
                    }
                ],

                // 🎯 garis fokus & konteks
                stroke: {
                    curve: 'smooth',
                    width: [2.5, 1],
                    dashArray: [0, 6]
                },

                // 🌊 area clean & terbaca
                fill: {
                    type: ['gradient', 'solid'],
                    opacity: [0.35, 0.14],
                    gradient: {
                        shadeIntensity: 0.6,
                        opacityFrom: 0.4,
                        opacityTo: 0.18,
                        stops: [0, 90, 100]
                    }
                },

                // 🎨 warna adaptif
                colors: [
                    isUp ? '#2563EB' : '#DC2626', // bulan ini
                    '#94A3B8' // bulan lalu
                ],

                xaxis: {
                    type: 'datetime'
                },

                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: val =>
                            'Rp ' + val.toLocaleString('id-ID')
                    }
                },

                grid: {
                    show: false
                }
            };

            premiKamarChart = new ApexCharts(
                document.querySelector('#premiKamarChart'),
                options
            );

            premiKamarChart.render();
        }

        function reloadPremiKamarChart() {

            let picker = $('#date-range').data('daterangepicker');

            $.ajax({
                url: "{{ route("backOffice.keuangan.premi.getPremiKamarChartOverlay") }}",
                type: "GET",
                data: {
                    tgl_awal: picker.startDate.format('YYYY-MM-DD'),
                    tgl_akhir: picker.endDate.format('YYYY-MM-DD'),
                    status_bayar: $('#filterStatusBayar').val(),
                    status_rawat: $('#filterStatusRawat').val(),
                    penjamin: $('#filterPenjamin').val()
                },
                success: function(res) {
                    updatePremiKamarChartOverlay(
                        res.labels,
                        res.this_month,
                        res.last_month
                    );
                }
            });
        }

        function updatePremiKamarChartOverlay(labels, thisMonth, lastMonth) {

            // 🔥 HAPUS CHART LAMA (agar animasi selalu muncul)
            if (premiKamarChart) {
                premiKamarChart.destroy();
                premiKamarChart = null;
            }

            // 🔥 INIT ULANG (animasi pasti jalan)
            initPremiKamarChart(labels, thisMonth, lastMonth);
        }

        function reloadPremiKamarSummary() {

            let picker = $('#date-range').data('daterangepicker');

            $.ajax({
                url: "{{ route("backOffice.keuangan.premi.getPremiKamarSummary") }}",
                type: "GET",
                data: {
                    tgl_awal: picker.startDate.format('YYYY-MM-DD'),
                    tgl_akhir: picker.endDate.format('YYYY-MM-DD'),
                    status_bayar: $('#filterStatusBayar').val(),
                    status_rawat: $('#filterStatusRawat').val(),
                    penjamin: $('#filterPenjamin').val()
                },
                success: function(res) {

                    const selisih = Number(res.selisih_abs).toLocaleString('id-ID');
                    const persen = Math.abs(res.persen).toFixed(2);
                    const el = $('#premiKamarGrowth');

                    // 🔥 INI YANG KEMARIN KURANG
                    $('#totalPremiKamar').text(
                        'Rp ' + Number(res.total_periode).toLocaleString('id-ID')
                    );

                    // 🔥 RESET
                    el.removeClass('text-success text-danger').html('');

                    if (res.naik) {
                        // NAIK
                        el.addClass('text-success');
                        el.html(`
                    Naik Rp ${selisih} (${persen}%)
                    <i data-feather="arrow-up" class="icon-sm mb-1"></i>
                `);
                    } else {
                        // TURUN
                        el.addClass('text-danger');
                        el.html(`
                    Turun Rp ${selisih} (${persen}%)
                    <i data-feather="arrow-down" class="icon-sm mb-1"></i>
                `);
                    }

                    feather.replace();
                }
            });
        }

        function reloadPremiKamarMiniTotal() {

            let picker = $('#date-range').data('daterangepicker');
            const el = document.getElementById('totalPremiKamarInapMini');

            // ambil nilai lama (kalau ada)
            const currentValue =
                Number(el.dataset.value || 0);

            $.ajax({
                url: "{{ route("backOffice.keuangan.premi.getPremiKamarChart") }}",
                type: "GET",
                data: {
                    tgl_awal: picker.startDate.format('YYYY-MM-DD'),
                    tgl_akhir: picker.endDate.format('YYYY-MM-DD'),
                    status_bayar: $('#filterStatusBayar').val(),
                    status_rawat: $('#filterStatusRawat').val(),
                    penjamin: $('#filterPenjamin').val()
                },
                success: function(res) {

                    const newValue = Number(res.total || 0);

                    // 🔥 animasi angka
                    animateNumber(el, currentValue, newValue, 700);

                    // simpan nilai terakhir
                    el.dataset.value = newValue;
                }
            });
        }

        // ===============================
        // Grafik Premi RS
        // ===============================
        function initPremiRsChart(labels = [], thisMonth = [], lastMonth = []) {

            const dataThis = labels.map((tgl, i) => ({
                x: Date.parse(tgl + 'T00:00:00Z'),
                y: Number(thisMonth[i] || 0)
            }));

            const dataLast = labels.map((tgl, i) => ({
                x: Date.parse(tgl + 'T00:00:00Z'),
                y: Number(lastMonth[i] || 0)
            }));

            const totalThis = thisMonth.reduce((a, b) => a + Number(b || 0), 0);
            const totalLast = lastMonth.reduce((a, b) => a + Number(b || 0), 0);
            const isUp = totalThis >= totalLast;

            const options = {
                chart: {
                    type: 'area',
                    height: 90,
                    sparkline: {
                        enabled: true
                    },
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    },
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800,
                        animateGradually: {
                            enabled: false
                        },
                        dynamicAnimation: {
                            enabled: true,
                            speed: 600
                        }
                    }
                },

                series: [{
                        name: 'Bulan Ini',
                        data: dataThis
                    },
                    {
                        name: 'Bulan Lalu',
                        data: dataLast
                    }
                ],

                stroke: {
                    curve: 'smooth',
                    width: [2.5, 1],
                    dashArray: [0, 6]
                },

                fill: {
                    type: ['gradient', 'solid'],
                    opacity: [0.35, 0.14],
                    gradient: {
                        shadeIntensity: 0.6,
                        opacityFrom: 0.4,
                        opacityTo: 0.18,
                        stops: [0, 90, 100]
                    }
                },

                colors: [
                    isUp ? '#16A34A' : '#DC2626',
                    '#94A3B8'
                ],

                xaxis: {
                    type: 'datetime'
                },

                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: val => 'Rp ' + Number(val || 0).toLocaleString('id-ID')
                    }
                },

                grid: {
                    show: false
                }
            };

            premiRsChart = new ApexCharts(
                document.querySelector('#premiRsChart'),
                options
            );

            premiRsChart.render();
        }

        function reloadPremiRsChart() {

            let picker = $('#date-range').data('daterangepicker');

            $.ajax({
                url: "{{ route("backOffice.keuangan.premi.getPremiRsChartOverlay") }}",
                type: "GET",
                data: {
                    tgl_awal: picker.startDate.format('YYYY-MM-DD'),
                    tgl_akhir: picker.endDate.format('YYYY-MM-DD'),
                    status_bayar: $('#filterStatusBayar').val(),
                    status_rawat: $('#filterStatusRawat').val(),
                    penjamin: $('#filterPenjamin').val()
                },
                success: function(res) {
                    updatePremiRsChartOverlay(
                        res.labels,
                        res.this_month,
                        res.last_month
                    );
                }
            });
        }

        function updatePremiRsChartOverlay(labels, thisMonth, lastMonth) {

            if (premiRsChart) {
                premiRsChart.destroy();
                premiRsChart = null;
            }

            initPremiRsChart(labels, thisMonth, lastMonth);
        }

        function reloadPremiRsSummary() {

            let picker = $('#date-range').data('daterangepicker');

            $.ajax({
                url: "{{ route("backOffice.keuangan.premi.getPremiRsSummary") }}",
                type: "GET",
                data: {
                    tgl_awal: picker.startDate.format('YYYY-MM-DD'),
                    tgl_akhir: picker.endDate.format('YYYY-MM-DD'),
                    status_bayar: $('#filterStatusBayar').val(),
                    status_rawat: $('#filterStatusRawat').val(),
                    penjamin: $('#filterPenjamin').val()
                },
                success: function(res) {

                    const selisih = Number(res.selisih_abs || 0).toLocaleString('id-ID');
                    const persen = Math.abs(Number(res.persen || 0)).toFixed(2);
                    const el = $('#premiRsGrowth');

                    $('#totalPremiRs').text(
                        'Rp ' + Number(res.total_periode || 0).toLocaleString('id-ID')
                    );

                    el.removeClass('text-success text-danger').html('');

                    if (res.naik) {
                        el.addClass('text-success');
                        el.html(`
                    Naik Rp ${selisih} (${persen}%)
                    <i data-feather="arrow-up" class="icon-sm mb-1"></i>
                `);
                    } else {
                        el.addClass('text-danger');
                        el.html(`
                    Turun Rp ${selisih} (${persen}%)
                    <i data-feather="arrow-down" class="icon-sm mb-1"></i>
                `);
                    }

                    feather.replace();
                }
            });
        }

        function reloadPremiRsMiniTotal() {

            let picker = $('#date-range').data('daterangepicker');
            const el = document.getElementById('totalPremiRsMini');

            if (!el) return;

            const currentValue = Number(el.dataset.value || 0);

            $.ajax({
                url: "{{ route("backOffice.keuangan.premi.getPremiRsChart") }}",
                type: "GET",
                data: {
                    tgl_awal: picker.startDate.format('YYYY-MM-DD'),
                    tgl_akhir: picker.endDate.format('YYYY-MM-DD'),
                    status_bayar: $('#filterStatusBayar').val(),
                    status_rawat: $('#filterStatusRawat').val(),
                    penjamin: $('#filterPenjamin').val()
                },
                success: function(res) {

                    const newValue = Number(res.total || 0);

                    animateNumber(el, currentValue, newValue, 700);

                    el.dataset.value = newValue;
                }
            });
        }

        // ===============================
        // CETAK PDF & EXCEL
        // ===============================
        function cetakPremiPdf(jenis) {

            const params = buildFilter(jenis);
            if (!params) return;

            // 🔥 Tambahkan mode yang sedang aktif
            params.view_mode = modeKamar;

            Swal.fire({
                title: 'Menyiapkan PDF',
                text: 'Mohon tunggu sebentar...',
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            setTimeout(() => {
                Swal.close();

                window.open(
                    '{{ route("backOffice.keuangan.premi.cetakPremiAllPdf") }}?' + $.param(params),
                    '_blank'
                );

            }, 300);
        }

        window.exportPremi = function(kode, jenis) {

            let picker = $('#date-range').data('daterangepicker');

            // ===== Ambil filter aktif =====
            const params = {
                kode: kode,
                jenis: jenis,
                tgl_awal: picker.startDate.format('YYYY-MM-DD'),
                tgl_akhir: picker.endDate.format('YYYY-MM-DD'),
                status_bayar: $('#filterStatusBayar').val(),
                status_rawat: $('#filterStatusRawat').val(),
                penjamin: $('#filterPenjamin').val(),
                sumber: $('#filterSumber').val(),
                layanan: $('#filterLayanan').val(),
            };

            // ===== UX: loading =====
            Swal.fire({
                title: 'Menyiapkan PDF',
                text: 'Mohon tunggu sebentar...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // ===== AJAX kecil (opsional, untuk log / validasi) =====
            $.ajax({
                url: '{{ route("backOffice.keuangan.premi.getPremiDetail") }}',
                type: 'GET',
                data: params,
                success: function(res) {

                    Swal.close();

                    if (!res.data || res.data.length === 0) {
                        Swal.fire(
                            'Data Kosong',
                            'Tidak ada data premi untuk dicetak',
                            'warning'
                        );
                        return;
                    }

                    // ===== BUKA PDF =====
                    window.open(
                        '/simrs/backOffice/keuangan/premi/cetakPremiDetailPdf?' + $.param(
                            params),
                        '_blank'
                    );
                },
                error: function() {
                    Swal.close();
                    Swal.fire(
                        'Gagal',
                        'Gagal menyiapkan data PDF',
                        'error'
                    );
                }
            });
        }

        window.exportPremiExcell = function(kode, jenis) {

            let picker = $('#date-range').data('daterangepicker');

            // ===== Ambil filter aktif =====
            const params = {
                kode: kode,
                jenis: jenis,
                tgl_awal: picker.startDate.format('YYYY-MM-DD'),
                tgl_akhir: picker.endDate.format('YYYY-MM-DD'),
                status_bayar: $('#status_bayar').val(),
                status_rawat: $('#status_rawat').val(),
                penjamin: $('#penjamin').val(),
                sumber: $('#filterSumber').val(),
                layanan: $('#filterLayanan').val(),
            };

            // ===== UX: loading =====
            Swal.fire({
                title: 'Menyiapkan Excel',
                text: 'Mohon tunggu sebentar...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // ===== AJAX kecil (opsional, untuk log / validasi) =====
            $.ajax({
                url: '{{ route("backOffice.keuangan.premi.getPremiDetail") }}',
                type: 'GET',
                data: params,
                success: function(res) {

                    Swal.close();

                    if (!res.data || res.data.length === 0) {
                        Swal.fire(
                            'Data Kosong',
                            'Tidak ada data premi untuk dicetak',
                            'warning'
                        );
                        return;
                    }

                    // ===== BUKA PDF =====
                    window.open(
                        '/simrs/backOffice/keuangan/premi/cetakPremiDetailExcel?' + $.param(
                            params),
                        '_blank'
                    );
                },
                error: function() {
                    Swal.close();
                    Swal.fire(
                        'Gagal',
                        'Gagal menyiapkan data Excel',
                        'error'
                    );
                }
            });
        }

        window.btnPdfDokter = () => cetakPremiPdf('dokter');
        window.btnPdfParamedis = () => cetakPremiPdf(
            'paramedis');
        window.btnPdfKamarInap = () => cetakPremiPdf('kamar_inap');
        window.btnPdfRs = () => cetakPremiPdf('rs');

        // ===============================
        // HIDE SEARCH DEFAULT
        // ===============================
        $('.dataTables_filter').hide();

        // ===============================
        // CUSTOM SEARCH
        // ===============================
        $('#searchPremi').on('keyup', function() {
            let activeTab = $('#premiTab .nav-link.active').attr('id');

            if (activeTab === 'dokter-tab' && tablePremiDokter) {
                tablePremiDokter.search(this.value).draw();
            } else if (activeTab === 'paramedis-tab' && tablePremiParamedis) {
                tablePremiParamedis.search(this.value).draw();
            } else if (activeTab === 'kamar-inap-tab' && tablePremiKamarInap) {
                tablePremiKamarInap.search(this.value).draw();
            } else if (activeTab === 'rs-tab' && tablePremiRs) {
                tablePremiRs.search(this.value).draw();
            }
        });

        // ===============================
        // RELOAD TABLE SAAT DATE BERUBAH
        // ===============================
        function markFilterDirty() {
            $('#btnApplyFilter')
                .removeClass('btn-primary')
                .addClass('btn-warning')
                .html('<i class="mdi mdi-alert-circle-outline me-1"></i> Terapkan');
        }

        function markFilterClean() {
            $('#btnApplyFilter')
                .removeClass('btn-warning')
                .addClass('btn-primary')
                .html('<i class="mdi mdi-filter-outline me-1"></i> Terapkan');
        }

        $('#date-range').on('apply.daterangepicker', markFilterDirty);
        $('#filterStatusBayar').on('change', markFilterDirty);
        $('#filterStatusRawat').on('change', markFilterDirty);
        $('#filterPenjamin').on('change', markFilterDirty);

        $('#btnApplyFilter').on('click', function() {
            const btn = $(this);

            btn.prop('disabled', true)
                .html('<i class="mdi mdi-loading mdi-spin me-1"></i> Memuat');

            reloadAllTable();

            setTimeout(function() {
                btn.prop('disabled', false);
                markFilterClean();
            }, 700);
        });

        $('#btnResetFilter').on('click', function() {
            const picker = $('#date-range').data('daterangepicker');

            picker.setStartDate(moment().startOf('month'));
            picker.setEndDate(moment());

            $('#date-range').val(
                picker.startDate.format('YYYY-MM-DD') + ' - ' +
                picker.endDate.format('YYYY-MM-DD')
            );

            $('#filterStatusBayar').val('');
            $('#filterStatusRawat').val('');
            $('#filterPenjamin').val('');

            markFilterDirty();
        });

        $('#premiTab a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
            const target = $(e.target).attr('href');

            if (target === '#tab-dokter' && !loadedTable.dokter) {
                initTableDokter();
                reloadPremiDokterMiniTotal();
                loadedTable.dokter = true;
            }

            if (target === '#tab-paramedis' && !loadedTable.paramedis) {
                initTableParamedis();
                reloadPremiParamedisMiniTotal();
                loadedTable.paramedis = true;
            }

            if (target === '#tab-kamar-inap' && !loadedTable.kamar) {
                initTableKamar();
                reloadPremiKamarMiniTotal();
                loadedTable.kamar = true;
            }

            if (target === '#tab-rs' && !loadedTable.rs) {
                initTableRs();
                reloadPremiRsMiniTotal();
                loadedTable.rs = true;
            }

            $.fn.dataTable.tables({
                visible: true,
                api: true
            }).columns.adjust();
        });

        function loadChartDashboardStaggered() {
            setTimeout(() => {
                reloadPremiDokterSummary();
                reloadPremiChart();
            }, 100);

            setTimeout(() => {
                reloadPremiParamedisSummary();
                reloadPremiParamedisChart();
            }, 500);

            setTimeout(() => {
                reloadPremiKamarSummary();
                reloadPremiKamarChart();
            }, 900);

            setTimeout(() => {
                reloadPremiRsSummary();
                reloadPremiRsChart();
            }, 1300);
        }

        initTableDokter();
        loadedTable.dokter = true;

        reloadPremiDokterMiniTotal();
        loadChartDashboardStaggered();

    });
</script>
