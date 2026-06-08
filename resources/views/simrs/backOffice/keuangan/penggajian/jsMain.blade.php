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

        let slipWhatsappRecipients = [];

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

        function escapeHtml(value) {
            return $('<div>').text(value || '').html();
        }

        function setSlipWhatsappLoading(isLoading) {
            $('#waSlipLoading').toggleClass('d-none', !isLoading);
            $('#waSlipTableWrap').toggleClass('d-none', true);
            $('#waSlipEmpty').toggleClass('d-none', true);
        }

        function updateSlipWhatsappSelectedCount() {
            const total = $('.wa-slip-checkbox').length;
            const selected = $('.wa-slip-checkbox:checked').length;

            $('#waSlipSelectedCount').text(selected + ' dipilih');
            $('#btnSendSlipWhatsapp').prop('disabled', selected === 0 || total === 0);

            $('#checkAllSlipWhatsapp')
                .prop('checked', total > 0 && selected === total)
                .prop('indeterminate', selected > 0 && selected < total);
        }

        function renderSlipWhatsappRecipients(items) {
            const tbody = $('#waSlipPegawaiList');
            tbody.empty();

            if (!items || items.length === 0) {
                $('#waSlipTableWrap').addClass('d-none');
                $('#waSlipEmpty').removeClass('d-none');
                updateSlipWhatsappSelectedCount();
                return;
            }

            items.forEach(function(item) {
                const statusClass = item.status === 'T' ? 'is-tetap' : item.status === 'FT' ?
                    'is-kontrak' : 'is-unknown';
                const statusLabel = item.status_label || '-';
                const searchable = [
                    item.nama,
                    item.nik,
                    item.jabatan,
                    item.no_telp,
                    item.no_whatsapp
                ].join(' ').toLowerCase();

                tbody.append(`
                    <tr data-search="${escapeHtml(searchable)}">
                        <td class="text-center">
                            <input class="form-check-input wa-slip-checkbox" type="checkbox"
                                value="${item.id}" id="waSlipPegawai${item.id}">
                        </td>
                        <td>
                            <span class="employee-name">${escapeHtml(item.nama || '-')}</span>
                            <span class="employee-subtext">${escapeHtml(item.nik || '')}</span>
                        </td>
                        <td>${escapeHtml(item.jabatan || '-')}</td>
                        <td class="text-center">
                            <span class="payroll-status-badge ${statusClass}">${escapeHtml(statusLabel)}</span>
                        </td>
                        <td>
                            <span class="fw-semibold">${escapeHtml(item.no_whatsapp || item.no_telp || '-')}</span>
                            <span class="employee-subtext">${escapeHtml(item.no_telp || '')}</span>
                        </td>
                        <td class="text-end currency-cell">${formatRupiah(item.total || 0)}</td>
                    </tr>
                `);
            });

            $('#waSlipTableWrap').removeClass('d-none');
            $('#waSlipEmpty').addClass('d-none');
            updateSlipWhatsappSelectedCount();
        }

        function filterSlipWhatsappRows() {
            const keyword = ($('#searchSlipWhatsappPegawai').val() || '').toLowerCase();

            $('#waSlipPegawaiList tr').each(function() {
                const searchable = $(this).data('search') || '';
                $(this).toggle(searchable.indexOf(keyword) !== -1);
            });
        }

        function loadSlipWhatsappRecipients() {
            const periode = $('#periodeGaji').val();

            $('#waSlipPeriode').text(periode || '-');
            $('#searchSlipWhatsappPegawai').val('');
            $('#checkAllSlipWhatsapp').prop('checked', false).prop('indeterminate', false);
            $('#btnSendSlipWhatsapp').prop('disabled', true);

            $.ajax({
                url: "{{ route("backOffice.keuangan.penggajian.getPenerimaSlipWhatsappTahap1") }}",
                type: "GET",
                data: {
                    periode: periode
                },
                beforeSend: function() {
                    setSlipWhatsappLoading(true);
                },
                success: function(response) {
                    slipWhatsappRecipients = response.data || [];
                    renderSlipWhatsappRecipients(slipWhatsappRecipients);
                },
                error: function(xhr) {
                    slipWhatsappRecipients = [];
                    renderSlipWhatsappRecipients([]);

                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: xhr.responseJSON?.message ||
                            'Gagal memuat daftar penerima slip gaji.'
                    });
                },
                complete: function() {
                    $('#waSlipLoading').addClass('d-none');
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

        $('#btnOpenSlipWhatsapp').on('click', function() {
            if ($('#tahapGaji').val() != '1') {
                Swal.fire({
                    icon: 'info',
                    title: 'Coming Soon',
                    text: 'Kirim slip Whatsapp untuk gaji tahap 2 belum tersedia.'
                });
                return;
            }

            if (!$('#periodeGaji').val()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Periode belum dipilih',
                    text: 'Silakan pilih bulan periode gaji terlebih dahulu.'
                });
                return;
            }

            const modal = new bootstrap.Modal(document.getElementById('modalSlipWhatsapp'));
            modal.show();
            loadSlipWhatsappRecipients();
        });

        $('#checkAllSlipWhatsapp').on('change', function() {
            $('.wa-slip-checkbox').prop('checked', $(this).is(':checked'));
            updateSlipWhatsappSelectedCount();
        });

        $(document).on('change', '.wa-slip-checkbox', function() {
            updateSlipWhatsappSelectedCount();
        });

        $('#searchSlipWhatsappPegawai').on('keyup', function() {
            filterSlipWhatsappRows();
        });

        $('#btnSendSlipWhatsapp').on('click', function() {
            const periode = $('#periodeGaji').val();
            const selectedIds = $('.wa-slip-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
            const btn = $(this);
            const btnHtml = btn.html();

            if (selectedIds.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Pegawai belum dipilih',
                    text: 'Pilih minimal satu pegawai.'
                });
                return;
            }

            Swal.fire({
                title: 'Masukkan ke antrean?',
                text: selectedIds.length + ' slip gaji akan dikirim bertahap lewat Whatsapp.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Antrekan',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: "{{ route("backOffice.keuangan.penggajian.kirimSlipGajiWhatsappTahap1") }}",
                    type: "POST",
                    data: {
                        periode: periode,
                        gaji_ids: selectedIds
                    },
                    beforeSend: function() {
                        btn.prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm me-1"></span> Menyiapkan...'
                        );
                    },
                    success: function(response) {
                        const queued = response.data?.queued || selectedIds.length;
                        const delaySeconds = response.data?.delay_seconds || 8;
                        const modalEl = document.getElementById('modalSlipWhatsapp');
                        const modal = bootstrap.Modal.getInstance(modalEl);

                        if (modal) {
                            modal.hide();
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Masuk Antrean',
                            text: queued + ' slip gaji akan dikirim oleh queue dengan jeda sekitar ' +
                                delaySeconds + ' detik.',
                            timer: 2600,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        let message = xhr.responseJSON?.message ||
                            'Gagal mengirim slip gaji ke Whatsapp.';

                        if (xhr.responseJSON?.errors) {
                            const errors = Object.values(xhr.responseJSON.errors);
                            if (errors.length && errors[0].length) {
                                message = errors[0][0];
                            }
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: message
                        });
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(btnHtml);
                        updateSlipWhatsappSelectedCount();
                    }
                });
            });
        });

        loadSummaryTahap1();
        // ======= END EVENT =======

    });
</script>
