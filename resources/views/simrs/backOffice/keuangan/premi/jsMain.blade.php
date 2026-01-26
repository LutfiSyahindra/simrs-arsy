<script>
    $(document).ready(function() {

        // ===============================
        // DATE RANGE PICKER
        // ===============================
        let startDate = moment().startOf('month');
        let endDate = moment();
        let tableDetailPremi;
        let currentLayanan = '';
        let currentSumber = '';
        let currentKode = '';
        let currentJenis = '';


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

        function getFilter(d, jenis) {
            let picker = $('#date-range').data('daterangepicker');

            d.tgl_awal = picker.startDate.format('YYYY-MM-DD');
            d.tgl_akhir = picker.endDate.format('YYYY-MM-DD');
            d.status_bayar = $('#filterStatusBayar').val();
            d.status_rawat = $('#filterStatusRawat').val();
            d.penjamin = $('#filterPenjamin').val();
            d.jenis = jenis; // 🔑 dokter / paramedis
        }

        function reloadAllTable() {
            tablePremiDokter.ajax.reload(null, false);
            tablePremiParamedis.ajax.reload(null, false);
        }

        // ===============================
        // DATATABLE PREMI
        // ===============================
        let tablePremiDokter = $('#tablePremiDokter').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route("backOffice.keuangan.premi.getPremiTable") }}",
                type: "GET",
                data: function(d) {
                    getFilter(d, 'dokter');
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

        let tablePremiParamedis = $('#tablePremiParamedis').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route("backOffice.keuangan.premi.getPremiTable") }}",
                type: "GET",
                data: function(d) {
                    getFilter(d, 'paramedis');
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

        window.detailPremi = function(kode, jenis, nama = '') {

            console.log(kode, jenis, nama);

            currentKode = kode;
            currentJenis = (jenis === 'Dokter') ? 'Dokter' : 'Perawat';
            currentSumber = '';
            currentLayanan = '';

            // Header info
            $('#namaPegawai').text(nama);
            $('#avatarPegawai').text(nama ? nama.charAt(0).toUpperCase() : '?');

            let picker = $('#date-range').data('daterangepicker');
            $('#periodePremi').text(
                picker.startDate.format('DD MMM YYYY') + ' - ' +
                picker.endDate.format('DD MMM YYYY')
            );

            $('#modalDetailPremi').modal('show');

            // Reset summary
            $('#totalPremiTab').text('Rp 0');
            $('#jumlahData').text('0');
            $('#badgeKategori').removeClass().addClass('badge bg-secondary').text('Semua');

            // Reset tab
            $('#tabDetailPremi .nav-link').removeClass('active')
                .filter('[data-sumber=""]').addClass('active');

            // Destroy table
            if ($.fn.DataTable.isDataTable('#tableDetailPremi')) {
                $('#tableDetailPremi').DataTable().clear().destroy();
            }

            // Init DataTable
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
                        d.sumber = currentSumber;
                        d.layanan = currentLayanan;
                        d.tgl_awal = picker.startDate.format('YYYY-MM-DD');
                        d.tgl_akhir = picker.endDate.format('YYYY-MM-DD');
                        d.status_bayar = $('#filterStatusBayar').val();
                        d.status_rawat = $('#filterStatusRawat').val();
                        d.penjamin = $('#filterPenjamin').val();
                    }
                },

                columns: [{
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
                        render: function(data, type) {
                            let value = data;
                            if (typeof value === 'string') {
                                value = value.replace(/[^\d]/g, '');
                            }
                            value = parseInt(value, 10) || 0;

                            return (type === 'display' || type === 'filter') ?
                                value.toLocaleString('id-ID') :
                                value;
                        }
                    }
                ]
            });

            // Backend response
            tableDetailPremi.on('xhr.dt', function(e, settings, json) {
                if (!json) return;

                $('#totalPremiTab').text(
                    'Rp ' + Number(json.total_premi || 0).toLocaleString('id-ID')
                );

                $('#jumlahData').text(json.recordsFiltered || 0);

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



        // ===============================
        // HIDE SEARCH DEFAULT
        // ===============================
        $('.dataTables_filter').hide();

        // ===============================
        // CUSTOM SEARCH
        // ===============================
        $('#searchPremi').on('keyup', function() {
            let activeTab = $('#premiTab .nav-link.active').attr('id');

            if (activeTab === 'dokter-tab') {
                tablePremiDokter.search(this.value).draw();
            } else {
                tablePremiParamedis.search(this.value).draw();
            }
        });

        // ===============================
        // RELOAD TABLE SAAT DATE BERUBAH
        // ===============================
        $('#date-range').on('apply.daterangepicker', reloadAllTable);
        $('#filterStatusBayar').on('change', reloadAllTable);
        $('#filterStatusRawat').on('change', reloadAllTable);
        $('#filterPenjamin').on('change', reloadAllTable);
        $('#tabDetailPremi').on('click', '.nav-link', function() {
            $('#tabDetailPremi .nav-link').removeClass('active');
            $(this).addClass('active');

            currentSumber = $(this).data('sumber') || '';
            currentLayanan = $(this).data('layanan') || '';

            if (tableDetailPremi) tableDetailPremi.ajax.reload();
        });



    });
</script>
