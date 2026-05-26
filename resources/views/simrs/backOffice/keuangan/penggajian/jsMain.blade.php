<script>
    $(document).ready(function() {

        // ======= KONFIGURASI AJAX =======
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });

        $('#periodeGaji').datepicker({
            format: 'yyyy-mm',
            startView: 'months',
            minViewMode: 'months',
            autoclose: true,
            todayHighlight: true,
            orientation: 'bottom auto'
        }).on('changeDate', function() {
            if ($('#tahapGaji').val() == '1') {
                tablePenggajianTahap1.ajax.reload();
                loadSummaryTahap1();
            }
        });

        $('#searchPenggajian').on('keyup', function() {
            tablePenggajianTahap1.search(this.value).draw();
        });
        // ======= END KONFIGURASI AJAX =======

        // ======= DATA =======
        let tablePenggajianTahap1 = $('#tablePenggajianTahap1').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            dom: "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6 text-md-end'i>>" +
                "rt" +
                "<'row g-2 align-items-center mt-3'<'col-12 col-md-6'i><'col-12 col-md-6 d-flex justify-content-md-end'p>>",
            language: {
                lengthMenu: 'Tampilkan _MENU_ data',
                info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                infoEmpty: 'Belum ada data',
                zeroRecords: 'Data gaji belum tersedia',
                processing: 'Memuat data...',
                paginate: {
                    previous: '<i class="mdi mdi-chevron-left"></i>',
                    next: '<i class="mdi mdi-chevron-right"></i>'
                }
            },
            ajax: {
                url: "{{ route("backOffice.keuangan.penggajian.getGajiTahap1Table") }}",
                type: "GET",
                data: function(d) {
                    d.periode = $('#periodeGaji').val();
                    d.search_penggajian = $('#searchPenggajian').val();
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },
                {
                    data: 'nama_pegawai',
                    name: 'nama_pegawai',
                    render: function(data, type, row) {
                        return `
                    <span class="employee-name">${data || '-'}</span>
                    <span class="employee-subtext">${row.nik || ''}</span>
                `;
                    }
                },
                {
                    data: 'jabatan',
                    name: 'jabatan'
                },
                {
                    data: 'status',
                    name: 'status',
                    className: 'text-center',
                    render: function(data) {
                        if (data === 'T') {
                            return '<span class="payroll-status-badge is-tetap">Tetap</span>';
                        }

                        if (data === 'FT') {
                            return '<span class="payroll-status-badge is-kontrak">Kontrak</span>';
                        }

                        return '<span class="payroll-status-badge is-unknown">-</span>';
                    }
                },
                {
                    data: 'gapok',
                    name: 'gapok',
                    className: 'text-end currency-cell'
                },
                {
                    data: 'gaji_dibayarkan',
                    name: 'gaji_dibayarkan',
                    className: 'text-end currency-cell'
                },
                {
                    data: 'tunjangan',
                    name: 'tunjangan',
                    className: 'text-end currency-cell'
                },
                {
                    data: 'total',
                    name: 'total',
                    className: 'text-end currency-cell',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'actions',
                    name: 'actions',
                    className: 'text-center',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        function loadSummaryTahap1() {
            const periode = $('#periodeGaji').val();

            $.ajax({
                url: "{{ route("backOffice.keuangan.penggajian.getSummaryGajiTahap1") }}",
                type: "GET",
                data: {
                    periode: periode
                },
                success: function(response) {
                    const data = response.data || {};

                    $('#summaryPegawai').text(data.jumlah_pegawai || 0);
                    $('#summaryStatusPegawai').text(
                        (data.jumlah_tetap || 0) + ' tetap / ' +
                        (data.jumlah_kontrak || 0) + ' kontrak'
                    );

                    $('#summaryTotalGaji').text(formatRupiah(data.total_gaji || 0));
                    $('#summaryGapok').text(formatRupiah(data.total_gapok || 0));
                    $('#summaryTunjangan').text(formatRupiah(data.total_tunjangan || 0));
                    $('#summaryPeriode').text(data.periode || '-');
                }
            });
        }

        $('#btnGenerateGaji').on('click', function() {
            const periode = $('#periodeGaji').val();
            const tahap = $('#tahapGaji').val();
            const btn = $(this);
            const btnHtml = btn.html();

            if (tahap != '1') {
                Swal.fire({
                    icon: 'info',
                    title: 'Coming Soon',
                    text: 'Generate gaji tahap 2 belum tersedia.'
                });
                return;
            }

            if (!periode) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Periode belum dipilih',
                    text: 'Silakan pilih bulan periode gaji terlebih dahulu.'
                });
                return;
            }

            Swal.fire({
                title: 'Generate gaji tahap 1?',
                text: 'Data gaji pada periode ini akan dibuat atau diperbarui.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Generate',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: "{{ route("backOffice.keuangan.penggajian.generateGajiTahap1") }}",
                    type: "POST",
                    data: {
                        periode: periode
                    },
                    beforeSend: function() {
                        btn.prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm me-1"></span> Generate...'
                        );
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message ||
                                'Gaji tahap 1 berhasil digenerate.',
                            timer: 1600,
                            showConfirmButton: false
                        });

                        tablePenggajianTahap1.ajax.reload(null, false);
                        loadSummaryTahap1();

                        if (response.data) {
                            $('#summaryPegawai').text(response.data
                                .jumlah_pegawai || 0);
                            $('#summaryStatusPegawai').text(
                                (response.data.jumlah_tetap || 0) +
                                ' tetap / ' +
                                (response.data.jumlah_kontrak || 0) + ' kontrak'
                            );
                            $('#summaryTotalGaji').text(formatRupiah(response.data
                                .total_gaji || 0));
                            $('#summaryPeriode').text(periode);
                        }
                    },
                    error: function(xhr) {
                        let message = 'Gagal generate gaji tahap 1.';

                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: message
                        });
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(btnHtml);
                    }
                });
            });
        });

        function renderTunjanganDetail(items) {
            if (!items || items.length === 0) {
                $('#slipTunjanganDetail').html(
                    '<div class="slip-empty-row">Tidak ada tunjangan</div>'
                );
                return;
            }

            let html = '';

            items.forEach(function(item) {
                html += `
                    <div class="slip-detail-row">
                        <span>${item.nama || 'Tunjangan'}</span>
                        <strong>${formatRupiah(item.nominal || 0)}</strong>
                    </div>
                `;
            });

            $('#slipTunjanganDetail').html(html);
        }

        window.detailGajiTahap1 = function(id) {
            $.ajax({
                url: "{{ route("backOffice.keuangan.penggajian.detailGajiTahap1", ":id") }}"
                    .replace(
                        ':id', id),
                type: "GET",
                success: function(response) {
                    const data = response.data || {};

                    $('#slipPeriode').text(data.periode || '-');
                    $('#slipNik').text(data.nik || '-');
                    $('#slipNama').text(data.nama || '-');
                    $('#slipJabatan').text(data.jabatan || '-');

                    $('#slipGajiPokok').text(formatRupiah(data.gaji_pokok));
                    $('#slipGajiDibayar').text(formatRupiah(data.gaji_dibayar));
                    $('#slipTunjangan').text(formatRupiah(data.tunjangan));
                    $('#slipTotal').text(formatRupiah(data.total));

                    $('#slipStatus')
                        .removeClass('is-tetap is-kontrak is-unknown')
                        .addClass(data.status === 'T' ? 'is-tetap' : data.status === 'FT' ?
                            'is-kontrak' : 'is-unknown')
                        .text(data.status_label || '-');
                    renderTunjanganDetail(data.tunjangan_detail || []);

                    const modal = new bootstrap.Modal(document.getElementById(
                        'modalSlipGajiTahap1'));
                    modal.show();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: xhr.responseJSON?.message ||
                            'Gagal mengambil detail slip gaji.'
                    });
                }
            });
        }
        // ======= END DATA =======

        // ======= EVENT =======
        function formatRupiah(angka) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(angka || 0);
        }

        $('#btnRefreshPenggajian').on('click', function() {
            if ($('#tahapGaji').val() == '1') {
                tablePenggajianTahap1.ajax.reload(null, false);
                loadSummaryTahap1();
            }
        });

        loadSummaryTahap1();
        // ======= END EVENT =======

    });
</script>
