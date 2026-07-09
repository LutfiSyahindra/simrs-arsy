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
            syncPayrollContext();
            reloadActivePayrollTable();
            loadPayrollSummaries();
        });

        $('#searchPenggajian').on('keyup', function() {
            if ($('#tahapGaji').val() == '2') {
                tablePenggajianTahap2.search(this.value).draw();
                return;
            }

            tablePenggajianTahap1.search(this.value).draw();
        });
        // ======= END KONFIGURASI AJAX =======

        let slipWhatsappRecipients = [];
        let stage2DoctorConfigRows = [];
        let stage2DoctorPremiumOptions = [];
        let stage2DoctorConfigLoaded = false;
        let stage2GeneratorReadiness = {
            ready: false,
            message: 'Status generator tahap 2 belum dimuat.'
        };

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
                    className: 'text-end currency-cell',
                    render: function(data, type, row) {
                        if (type !== 'display') {
                            return data;
                        }

                        return `
                            <span class="fw-semibold">${escapeHtml(data || 'Rp 0')}</span>
                            <span class="employee-subtext">${escapeHtml(row.komponen_gaji_label || 'Gaji Pokok')}</span>
                        `;
                    }
                },
                {
                    data: 'gaji_dibayarkan',
                    name: 'gaji_dibayarkan',
                    className: 'text-end currency-cell',
                    render: function(data, type, row) {
                        if (type !== 'display') {
                            return data;
                        }

                        return `
                            <span class="fw-semibold">${escapeHtml(data || 'Rp 0')}</span>
                            <span class="employee-subtext">${escapeHtml(row.komponen_gaji_dibayar_label || 'Gaji Dibayarkan')}</span>
                        `;
                    }
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

        let tablePenggajianTahap2 = $('#tablePenggajianTahap2').DataTable({
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
                zeroRecords: 'Data gaji tahap 2 belum tersedia',
                processing: 'Memuat data...',
                paginate: {
                    previous: '<i class="mdi mdi-chevron-left"></i>',
                    next: '<i class="mdi mdi-chevron-right"></i>'
                }
            },
            ajax: {
                url: "{{ route("backOffice.keuangan.penggajian.getGajiTahap2Table") }}",
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
                            <span class="employee-name">${escapeHtml(data || '-')}</span>
                            <span class="employee-subtext">${escapeHtml(row.nik || '')}</span>
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
                    render: function(data, type, row) {
                        if (data === 'T') {
                            return '<span class="payroll-status-badge is-tetap">Tetap</span>';
                        }

                        if (data === 'FT') {
                            return '<span class="payroll-status-badge is-kontrak">Kontrak</span>';
                        }

                        return '<span class="payroll-status-badge is-unknown">' + escapeHtml(row
                            .status_label || '-') + '</span>';
                    }
                },
                {
                    data: 'gaji_pokok',
                    name: 'gaji_pokok',
                    className: 'text-end currency-cell',
                    render: function(data, type, row) {
                        if (type !== 'display') {
                            return data;
                        }

                        return `
                            <span class="fw-semibold">${escapeHtml(data || 'Rp 0')}</span>
                            <span class="employee-subtext">${escapeHtml(row.komponen_gaji_label || 'Gaji Pokok')}</span>
                        `;
                    }
                },
                {
                    data: 'gaji_dibayarkan',
                    name: 'gaji_dibayarkan',
                    className: 'text-end currency-cell',
                    render: function(data, type, row) {
                        if (type !== 'display') {
                            return data;
                        }

                        return `
                            <span class="fw-semibold">${escapeHtml(data || 'Rp 0')}</span>
                            <span class="employee-subtext">${escapeHtml(row.komponen_gaji_dibayar_label || 'Komponen Gaji')}</span>
                        `;
                    }
                },
                {
                    data: 'total_premi',
                    name: 'total_premi',
                    className: 'text-end currency-cell'
                },
                {
                    data: 'jumlah_sumber_premi',
                    name: 'jumlah_sumber_premi',
                    className: 'text-center',
                    render: function(data) {
                        return '<span class="badge bg-primary-subtle text-primary">' + (data || 0) +
                            '</span>';
                    }
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

                    $('#summaryTahap1Pegawai').text(data.jumlah_pegawai || 0);
                    $('#summaryTahap1Status').text(summaryStatusText(data));
                    $('#summaryTahap1Total').text(formatRupiah(data.total_gaji || 0));
                    $('#summaryTahap1Gapok').text(formatRupiah(data.total_gapok || 0));
                    $('#summaryTahap1Tunjangan').text(formatRupiah(data.total_tunjangan || 0));

                    if ($('#tahapGaji').val() == '1') {
                        renderActiveSummary(data, '1');
                    }
                }
            });
        }

        function loadSummaryTahap2() {
            const periode = $('#periodeGaji').val();

            $.ajax({
                url: "{{ route("backOffice.keuangan.penggajian.getSummaryGajiTahap2") }}",
                type: "GET",
                data: {
                    periode: periode
                },
                success: function(response) {
                    const data = response.data || {};

                    $('#summaryTahap2Pegawai').text(data.jumlah_pegawai || 0);
                    $('#summaryTahap2Status').text(summaryStatusText(data));
                    $('#summaryTahap2Total').text(formatRupiah(data.total_gaji || 0));
                    $('#summaryTahap2Gapok').text(formatRupiah(data.total_gapok || 0));
                    $('#summaryTahap2Premi').text(formatRupiah(data.total_premi || 0));

                    if ($('#tahapGaji').val() == '2') {
                        renderActiveSummary(data, '2');
                    }
                }
            });
        }

        function loadPayrollSummaries() {
            loadSummaryTahap1();
            loadSummaryTahap2();
            loadStage2GeneratorReadiness();
        }

        function summaryStatusText(data) {
            let text = (data.jumlah_tetap || 0) + ' tetap / ' +
                (data.jumlah_kontrak || 0) + ' kontrak';

            if ((data.jumlah_lainnya || 0) > 0) {
                text += ' / ' + data.jumlah_lainnya + ' lainnya';
            }

            return text;
        }

        function syncPayrollContext(tahap = $('#tahapGaji').val()) {
            const periode = $('#periodeGaji').val() || '-';
            const isTahap2 = tahap == '2';
            const stageLabel = 'Tahap ' + (isTahap2 ? '2' : '1');
            const description = isTahap2 ?
                'Premi generator dan komponen terpilih' :
                'Gaji pokok dan tunjangan';

            $('#commandPeriodeText').text(periode);
            $('#activePeriodeBadge').text(periode);
            $('#activeStageBadge').text(stageLabel);
            $('#activeStageDescription').text(description);
            $('#tableStageTitle').text('Hasil Generate ' + stageLabel);
            $('#tableStageSubtitle').text(isTahap2 ?
                'Sisa gaji kontrak, premi generator, dan konfigurasi dokter tahap 2' :
                'Gaji pokok dan tunjangan pegawai periode aktif'
            );
            $('#stage2GeneratorReadinessPanel').toggleClass('d-none', !isTahap2);
        }

        function renderActiveSummary(data, tahap) {
            syncPayrollContext(tahap);
            $('#summaryPegawai').text(data.jumlah_pegawai || 0);
            $('#summaryStatusPegawai').text(summaryStatusText(data));
            $('#summaryTotalGaji').text(formatRupiah(data.total_gaji || 0));
            $('#summaryGapok').text(formatRupiah(data.total_gapok || 0));
            $('#summaryTunjangan').text(formatRupiah(
                tahap == '2' ? (data.total_premi || 0) : (data.total_tunjangan || 0)
            ));
            $('#summaryExtraLabel').text(tahap == '2' ? 'Premi' : 'Tunjangan');
            $('#summaryExtraNote').text(tahap == '2' ? 'Dari generator premi' : 'Masuk komponen gaji');
            $('#summaryPeriode').text((data.periode || '-') + ' / Tahap ' + tahap);
        }

        function reloadActivePayrollTable() {
            if ($('#tahapGaji').val() == '2') {
                tablePenggajianTahap2.ajax.reload(null, false);
                return;
            }

            tablePenggajianTahap1.ajax.reload(null, false);
        }

        function escapeHtml(value) {
            return $('<div>').text(value || '').html();
        }

        function generatorStateMeta(item) {
            if (item.ready) {
                return {
                    className: 'ready',
                    icon: 'mdi-lock-check-outline',
                    text: 'Ready'
                };
            }

            if (item.state === 'unlocked') {
                return {
                    className: 'unlocked',
                    icon: 'mdi-lock-open-variant-outline',
                    text: 'Belum Dikunci'
                };
            }

            return {
                className: 'missing',
                icon: 'mdi-alert-circle-outline',
                text: 'Belum Ready'
            };
        }

        function renderStage2GeneratorReadiness(data) {
            stage2GeneratorReadiness = data || {
                ready: false,
                message: 'Status generator tahap 2 belum tersedia.',
                items: []
            };

            const ready = !!stage2GeneratorReadiness.ready;
            const badge = $('#stage2GeneratorReadinessBadge');
            const items = stage2GeneratorReadiness.items || [];

            badge
                .removeClass('ready blocked')
                .addClass(ready ? 'ready' : 'blocked')
                .html(
                    '<i class="mdi ' + (ready ? 'mdi-lock-check-outline' : 'mdi-alert-circle-outline') + '"></i>' +
                    (ready ? 'Siap Generate' : 'Belum Lengkap')
                );

            $('#stage2GeneratorReadinessMessage').text(stage2GeneratorReadiness.message || '-');
            $('#stage2GeneratorReadyCount').text(
                (stage2GeneratorReadiness.total_ready || 0) + ' / ' +
                (stage2GeneratorReadiness.total_required || 0)
            );
            $('#stage2GeneratorGeneratedCount').text((stage2GeneratorReadiness.total_generated || 0) + ' data');
            $('#stage2GeneratorLockedCount').text((stage2GeneratorReadiness.total_locked || 0) + ' data');
            $('#stage2GeneratorUnlockedCount').text((stage2GeneratorReadiness.total_unlocked || 0) + ' data');

            if (items.length === 0) {
                $('#stage2GeneratorReadinessList').html(
                    '<div class="stage2-generator-note">Belum ada data generator yang bisa ditampilkan.</div>'
                );
                return;
            }

            $('#stage2GeneratorReadinessList').html(items.map(function(item) {
                const meta = generatorStateMeta(item);

                return `
                    <div class="stage2-generator-item ${meta.className}">
                        <div class="stage2-generator-title">
                            <span>${escapeHtml(item.label || '-')}</span>
                            <span class="stage2-readiness-badge ${item.ready ? 'ready' : 'blocked'}">
                                <i class="mdi ${meta.icon}"></i>${meta.text}
                            </span>
                        </div>
                        <div class="stage2-generator-note">${escapeHtml(item.note || '-')}</div>
                        <div class="stage2-generator-meta">
                            <span>${item.generated_count || 0} generated</span>
                            <span>${item.locked_count || 0} terkunci</span>
                            <span>${item.unlocked_count || 0} terbuka</span>
                        </div>
                    </div>
                `;
            }).join(''));
        }

        function renderStage2GeneratorReadinessLoading() {
            $('#stage2GeneratorReadinessBadge')
                .removeClass('ready blocked')
                .addClass('blocked')
                .html('<i class="mdi mdi-timer-sand"></i>Memuat');
            $('#stage2GeneratorReadinessMessage').text('Memeriksa generator yang sudah digenerate dan dikunci.');
            $('#stage2GeneratorReadinessList').html(
                '<div class="stage2-generator-note">Memuat status generator...</div>'
            );
        }

        function loadStage2GeneratorReadiness() {
            const periode = $('#periodeGaji').val();

            if (!periode) {
                renderStage2GeneratorReadiness({
                    ready: false,
                    message: 'Periode belum dipilih.',
                    items: []
                });
                return;
            }

            $.ajax({
                url: "{{ route("backOffice.keuangan.penggajian.getGajiTahap2GeneratorReadiness") }}",
                type: "GET",
                data: {
                    periode: periode
                },
                beforeSend: function() {
                    renderStage2GeneratorReadinessLoading();
                },
                success: function(response) {
                    renderStage2GeneratorReadiness(response.data || {});
                },
                error: function(xhr) {
                    renderStage2GeneratorReadiness({
                        ready: false,
                        message: xhr.responseJSON?.message ||
                            'Gagal memuat status generator tahap 2.',
                        items: []
                    });
                }
            });
        }

        $('#configStage2DoctorSelect').select2({
            dropdownParent: $('#modalGajiTahap2DoctorConfig'),
            width: '100%',
            placeholder: 'Pilih dokter...',
            allowClear: true,
            ajax: {
                url: "{{ route("backOffice.keuangan.penggajian.dokterUmumTahap2Options") }}",
                type: "GET",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term || ''
                    };
                },
                processResults: function(response) {
                    return {
                        results: response.data || []
                    };
                }
            }
        });

        function normalizeStage2DoctorConfigRow(row) {
            return {
                kd_dokter: row.kd_dokter || row.id || '',
                nm_dokter: row.nm_dokter || '',
                kd_sps: row.kd_sps || null,
                nm_sps: row.nm_sps || null,
                include_salary: !!row.include_salary,
                premium_types: Array.isArray(row.premium_types) ? row.premium_types : []
            };
        }

        function stage2DoctorComponentCount(row) {
            return (row.include_salary ? 1 : 0) + ((row.premium_types || []).length);
        }

        function updateStage2DoctorConfigMeta() {
            $('#stage2DoctorConfigCount').text(stage2DoctorConfigRows.length + ' dokter');
            $('#stage2DoctorPremiumCount').text(stage2DoctorPremiumOptions.length + ' opsi');

            stage2DoctorConfigRows.forEach(function(row, index) {
                $('.payroll-config-selected-count[data-index="' + index + '"]')
                    .text(stage2DoctorComponentCount(row) + ' komponen');
            });
        }

        function renderStage2DoctorConfigRows() {
            const wrap = $('#stage2DoctorConfigRows');
            wrap.empty();

            $('#stage2DoctorConfigEmpty').toggleClass('d-none', stage2DoctorConfigRows.length > 0);
            updateStage2DoctorConfigMeta();

            stage2DoctorConfigRows.forEach(function(row, index) {
                const premiumCheckboxes = stage2DoctorPremiumOptions.length > 0 ?
                    stage2DoctorPremiumOptions.map(function(option) {
                        const checked = (row.premium_types || []).indexOf(option.id) !== -1 ?
                            'checked' : '';

                        return `
                            <label class="form-check mb-0">
                                <input class="form-check-input stage2-doctor-premium" type="checkbox"
                                    data-index="${index}" value="${escapeHtml(option.id)}" ${checked}>
                                <span class="form-check-label">${escapeHtml(option.label)}</span>
                            </label>
                        `;
                    }).join('') :
                    '<div class="payroll-config-empty-mini">Opsi premi belum tersedia.</div>';

                wrap.append(`
                    <div class="payroll-config-row" data-index="${index}">
                        <div class="payroll-config-row-header">
                            <div>
                                <span class="employee-name">${escapeHtml(row.nm_dokter || '-')}</span>
                                <span class="employee-subtext">${escapeHtml(row.kd_dokter || '')} / ${escapeHtml(row.nm_sps || 'Umum')}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="payroll-config-count payroll-config-selected-count" data-index="${index}">
                                    ${stage2DoctorComponentCount(row)} komponen
                                </span>
                                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-stage2-doctor"
                                    data-index="${index}" title="Hapus">
                                    <i class="mdi mdi-trash-can-outline"></i>
                                </button>
                            </div>
                        </div>

                        <div class="payroll-config-components">
                            <label class="form-check mb-0">
                                <input class="form-check-input stage2-doctor-salary" type="checkbox"
                                    data-index="${index}" ${row.include_salary ? 'checked' : ''}>
                                <span class="form-check-label">STR/Gaji Pokok</span>
                            </label>
                            ${premiumCheckboxes}
                        </div>
                    </div>
                `);
            });

            updateStage2DoctorConfigMeta();
        }

        function loadStage2DoctorConfig(force = false) {
            if (stage2DoctorConfigLoaded && !force) {
                renderStage2DoctorConfigRows();
                return;
            }

            $.ajax({
                url: "{{ route("backOffice.keuangan.penggajian.gajiTahap2DoctorConfig") }}",
                type: "GET",
                beforeSend: function() {
                    $('#stage2DoctorConfigLoading').removeClass('d-none');
                    $('#stage2DoctorConfigRows').addClass('d-none');
                    $('#stage2DoctorConfigEmpty').addClass('d-none');
                },
                success: function(response) {
                    const data = response.data || {};

                    stage2DoctorPremiumOptions = data.premium_type_options || [];
                    stage2DoctorConfigRows = (data.rows || []).map(normalizeStage2DoctorConfigRow);
                    stage2DoctorConfigLoaded = true;
                    renderStage2DoctorConfigRows();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: xhr.responseJSON?.message ||
                            'Gagal memuat konfigurasi dokter tahap 2.'
                    });
                },
                complete: function() {
                    $('#stage2DoctorConfigLoading').addClass('d-none');
                    $('#stage2DoctorConfigRows').removeClass('d-none');
                }
            });
        }

        $('#btnOpenStage2DoctorConfig').on('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('modalGajiTahap2DoctorConfig'));
            modal.show();
            loadStage2DoctorConfig();
        });

        $('#btnAddStage2DoctorConfig').on('click', function() {
            const selected = $('#configStage2DoctorSelect').select2('data')[0];

            if (!selected) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Dokter belum dipilih',
                    text: 'Pilih dokter terlebih dahulu.'
                });
                return;
            }

            if (stage2DoctorConfigRows.some(function(row) {
                    return row.kd_dokter === selected.kd_dokter;
                })) {
                Swal.fire({
                    icon: 'info',
                    title: 'Sudah Ada',
                    text: 'Dokter ini sudah masuk konfigurasi tahap 2.'
                });
                return;
            }

            stage2DoctorConfigRows.push({
                kd_dokter: selected.kd_dokter,
                nm_dokter: selected.nm_dokter,
                kd_sps: selected.kd_sps || null,
                nm_sps: selected.nm_sps || null,
                include_salary: false,
                premium_types: stage2DoctorPremiumOptions.map(function(option) {
                    return option.id;
                })
            });

            $('#configStage2DoctorSelect').val(null).trigger('change');
            renderStage2DoctorConfigRows();
        });

        $(document).on('change', '.stage2-doctor-salary', function() {
            const index = Number($(this).data('index'));

            if (stage2DoctorConfigRows[index]) {
                stage2DoctorConfigRows[index].include_salary = $(this).is(':checked');
                updateStage2DoctorConfigMeta();
            }
        });

        $(document).on('change', '.stage2-doctor-premium', function() {
            const index = Number($(this).data('index'));

            if (!stage2DoctorConfigRows[index]) {
                return;
            }

            stage2DoctorConfigRows[index].premium_types = $('.stage2-doctor-premium[data-index="' + index + '"]:checked')
                .map(function() {
                    return $(this).val();
                })
                .get();
            updateStage2DoctorConfigMeta();
        });

        $(document).on('click', '.btn-remove-stage2-doctor', function() {
            const index = Number($(this).data('index'));
            stage2DoctorConfigRows.splice(index, 1);
            renderStage2DoctorConfigRows();
        });

        $('#btnSaveStage2DoctorConfig').on('click', function() {
            const invalidRow = stage2DoctorConfigRows.find(function(row) {
                return !row.include_salary && (!row.premium_types || row.premium_types.length === 0);
            });

            if (invalidRow) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Komponen belum dipilih',
                    text: 'Setiap dokter wajib memiliki minimal satu komponen.'
                });
                return;
            }

            const btn = $(this);
            const btnHtml = btn.html();

            $.ajax({
                url: "{{ route("backOffice.keuangan.penggajian.updateGajiTahap2DoctorConfig") }}",
                type: "PUT",
                contentType: 'application/json',
                data: JSON.stringify({
                    rows: stage2DoctorConfigRows
                }),
                beforeSend: function() {
                    btn.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...'
                    );
                },
                success: function(response) {
                    const data = response.data || {};
                    stage2DoctorPremiumOptions = data.premium_type_options || stage2DoctorPremiumOptions;
                    stage2DoctorConfigRows = (data.rows || []).map(normalizeStage2DoctorConfigRow);
                    stage2DoctorConfigLoaded = true;
                    renderStage2DoctorConfigRows();

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message || 'Konfigurasi berhasil disimpan.',
                        timer: 1600,
                        showConfirmButton: false
                    });
                },
                error: function(xhr) {
                    let message = xhr.responseJSON?.message || 'Gagal menyimpan konfigurasi.';

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
                }
            });
        });

        function setSlipWhatsappLoading(isLoading) {
            $('#waSlipLoading').toggleClass('d-none', !isLoading);
            $('#waSlipTableWrap').toggleClass('d-none', true);
            $('#waSlipEmpty').toggleClass('d-none', true);
        }

        function updateSlipWhatsappSelectedCount() {
            const total = $('.wa-slip-checkbox').length;
            const selected = $('.wa-slip-checkbox:checked').length;

            $('#waSlipRecipientCount').text(total + ' penerima');
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
            $('#waSlipRecipientCount').text('0 penerima');
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
            const generateUrl = tahap == '2' ?
                "{{ route("backOffice.keuangan.penggajian.generateGajiTahap2") }}" :
                "{{ route("backOffice.keuangan.penggajian.generateGajiTahap1") }}";

            if (!periode) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Periode belum dipilih',
                    text: 'Silakan pilih bulan periode gaji terlebih dahulu.'
                });
                return;
            }

            if (tahap == '2' && !stage2GeneratorReadiness.ready) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Generator Belum Lengkap',
                    text: stage2GeneratorReadiness.message ||
                        'Semua generator tahap 2 wajib digenerate dan dikunci terlebih dahulu.'
                });
                return;
            }

            Swal.fire({
                title: 'Generate gaji tahap ' + tahap + '?',
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
                    url: generateUrl,
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
                                'Gaji tahap ' + tahap + ' berhasil digenerate.',
                            timer: 1600,
                            showConfirmButton: false
                        });

                        reloadActivePayrollTable();
                        loadPayrollSummaries();
                    },
                    error: function(xhr) {
                        let message = 'Gagal generate gaji tahap ' + tahap + '.';

                        if (xhr.responseJSON?.errors) {
                            const errors = Object.values(xhr.responseJSON.errors);
                            if (errors.length && errors[0].length) {
                                message = errors[0][0];
                            }
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
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

        function renderTunjanganDetail(items, emptyText = 'Tidak ada tunjangan') {
            if (!items || items.length === 0) {
                $('#slipTunjanganDetail').html(
                    '<div class="slip-empty-row">' + escapeHtml(emptyText) + '</div>'
                );
                return;
            }

            let html = '';

            items.forEach(function(item) {
                html += `
                    <div class="slip-detail-row">
                        <span>${escapeHtml(item.nama || 'Tunjangan')}</span>
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

                    $('#modalSlipGajiTahap1Label').text('Detail Gaji Tahap 1');
                    $('#slipPeriode').text(data.periode || '-');
                    $('#slipNik').text(data.nik || '-');
                    $('#slipNama').text(data.nama || '-');
                    $('#slipJabatan').text(data.jabatan || '-');

                    $('#slipGajiPokokLabel').text(data.komponen_gaji_label || 'Gaji Pokok');
                    $('#slipGajiDibayarLabel').text(data.komponen_gaji_dibayar_label ||
                        'Gaji Dibayarkan');
                    $('#slipGajiPokok').text(formatRupiah(data.gaji_pokok));
                    $('#slipGajiDibayar').text(formatRupiah(data.gaji_dibayar));
                    $('#slipTunjangan').text(formatRupiah(data.tunjangan));
                    $('#slipTotal').text(formatRupiah(data.total));
                    $('#slipDetailListTitle').text('Rincian Tunjangan');
                    $('#slipTotalTunjanganLabel').text('Total Tunjangan');

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

        window.detailGajiTahap2 = function(id) {
            $.ajax({
                url: "{{ route("backOffice.keuangan.penggajian.detailGajiTahap2", ":id") }}"
                    .replace(':id', id),
                type: "GET",
                success: function(response) {
                    const data = response.data || {};

                    $('#modalSlipGajiTahap1Label').text('Detail Gaji Tahap 2');
                    $('#slipPeriode').text(data.periode || '-');
                    $('#slipNik').text(data.nik || '-');
                    $('#slipNama').text(data.nama || '-');
                    $('#slipJabatan').text(data.jabatan || '-');

                    $('#slipGajiPokokLabel').text(data.komponen_gaji_label || 'Gaji Pokok');
                    $('#slipGajiDibayarLabel').text(data.komponen_gaji_dibayar_label ||
                        'Gaji Dibayarkan');
                    $('#slipGajiPokok').text(formatRupiah(data.gaji_pokok));
                    $('#slipGajiDibayar').text(formatRupiah(data.gaji_dibayar));
                    $('#slipTunjangan').text(formatRupiah(data.total_premi));
                    $('#slipTotal').text(formatRupiah(data.total));
                    $('#slipDetailListTitle').text('Rincian Premi Generator');
                    $('#slipTotalTunjanganLabel').text('Total Premi');

                    $('#slipStatus')
                        .removeClass('is-tetap is-kontrak is-unknown')
                        .addClass(data.status === 'T' ? 'is-tetap' : data.status === 'FT' ?
                            'is-kontrak' : 'is-unknown')
                        .text(data.status_label || '-');
                    renderTunjanganDetail(data.premi_detail || [], 'Tidak ada premi generator');

                    const modal = new bootstrap.Modal(document.getElementById(
                        'modalSlipGajiTahap1'));
                    modal.show();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: xhr.responseJSON?.message ||
                            'Gagal mengambil detail gaji tahap 2.'
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
            reloadActivePayrollTable();
            loadPayrollSummaries();
        });

        $('#btnExportGajiTahap2').on('click', function() {
            const periode = $('#periodeGaji').val();

            if (!periode) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Periode belum dipilih',
                    text: 'Silakan pilih bulan periode gaji terlebih dahulu.'
                });
                return;
            }

            window.location.href =
                "{{ route("backOffice.keuangan.penggajian.exportGajiTahap2Excel") }}" +
                '?periode=' + encodeURIComponent(periode);
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

        $('#tahapGaji').on('change', function() {
            syncPayrollContext();
            reloadActivePayrollTable();
            loadPayrollSummaries();
        });

        syncPayrollContext();
        loadPayrollSummaries();
        // ======= END EVENT =======

    });
</script>
