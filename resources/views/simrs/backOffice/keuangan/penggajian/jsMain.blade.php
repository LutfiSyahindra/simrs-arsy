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
        let slipDeliveryChannel = 'whatsapp';
        let tableSlipDeliveryLog = null;
        const slipDeliveryChannels = {
            whatsapp: {
                label: 'WhatsApp',
                shortLabel: 'WA',
                icon: 'mdi-whatsapp',
                iconWrapClass: 'is-green',
                buttonClass: 'btn-success',
                badgeClass: 'bg-success-subtle text-success',
                contactHeader: 'No. WhatsApp',
                emptyIcon: 'mdi-account-alert-outline',
                emptyText: 'Pastikan data gaji periode ini sudah digenerate dan nomor WhatsApp terisi.',
                loadingText: 'Memuat penerima WhatsApp...',
                sendText: 'Kirim Slip Gaji WhatsApp',
                confirmText: ' akan dikirim bertahap lewat WhatsApp.',
                successTitle: 'Masuk Antrean WhatsApp',
                errorText: 'Gagal mengirim slip gaji ke WhatsApp.'
            },
            email: {
                label: 'Email',
                shortLabel: 'Email',
                icon: 'mdi-email-outline',
                iconWrapClass: 'is-blue',
                buttonClass: 'btn-primary',
                badgeClass: 'bg-primary-subtle text-primary',
                contactHeader: 'Email',
                emptyIcon: 'mdi-email-alert-outline',
                emptyText: 'Pastikan data gaji periode ini sudah digenerate dan email pegawai terisi di Gaji Pokok.',
                loadingText: 'Memuat penerima Email...',
                sendText: 'Kirim Slip Gaji Email',
                confirmText: ' akan dikirim bertahap lewat Email.',
                successTitle: 'Masuk Antrean Email',
                errorText: 'Gagal mengirim slip gaji ke Email.'
            }
        };
        let payrollDoctorConfigState = {
            stage1: {
                label: 'tahap 1',
                rows: [],
                premiumOptions: [],
                salaryComponent: {
                    id: 'include_salary',
                    label: 'Gaji Pokok (Kehadiran)'
                },
                loaded: false,
                configUrl: "{{ route("backOffice.keuangan.penggajian.gajiTahap1DoctorConfig") }}",
                saveUrl: "{{ route("backOffice.keuangan.penggajian.updateGajiTahap1DoctorConfig") }}",
                optionsUrl: "{{ route("backOffice.keuangan.penggajian.dokterUgdKontrakTahap1Options") }}",
                select: '#configStage1DoctorSelect',
                rowsWrap: '#stage1DoctorConfigRows',
                empty: '#stage1DoctorConfigEmpty',
                loading: '#stage1DoctorConfigLoading',
                doctorCount: '#stage1DoctorConfigCount',
                premiumCount: '#stage1DoctorPremiumCount',
                salaryLabel: '#stage1DoctorSalaryLabel',
                defaultIncludeSalary: true
            },
            stage2: {
                label: 'tahap 2',
                rows: [],
                premiumOptions: [],
                salaryComponent: {
                    id: 'include_salary',
                    label: 'STR/Gaji Pokok'
                },
                loaded: false,
                configUrl: "{{ route("backOffice.keuangan.penggajian.gajiTahap2DoctorConfig") }}",
                saveUrl: "{{ route("backOffice.keuangan.penggajian.updateGajiTahap2DoctorConfig") }}",
                optionsUrl: "{{ route("backOffice.keuangan.penggajian.dokterUmumTahap2Options") }}",
                select: '#configStage2DoctorSelect',
                rowsWrap: '#stage2DoctorConfigRows',
                empty: '#stage2DoctorConfigEmpty',
                loading: '#stage2DoctorConfigLoading',
                doctorCount: '#stage2DoctorConfigCount',
                premiumCount: '#stage2DoctorPremiumCount',
                salaryLabel: '#stage2DoctorSalaryLabel',
                defaultIncludeSalary: false
            }
        };
        let payrollRoundingConfigState = {
            loaded: false,
            configUrl: "{{ route("backOffice.keuangan.penggajian.payrollRoundingConfig") }}",
            saveUrl: "{{ route("backOffice.keuangan.penggajian.updatePayrollRoundingConfig") }}",
            data: {}
        };
        let stage2GeneratorReadiness = {
            ready: false,
            message: 'Status generator tahap 2 belum dimuat.'
        };

        // ======= DATA =======
        let tablePenggajianTahap1 = $('#tablePenggajianTahap1').DataTable({
            processing: true,
            serverSide: true,
            responsive: false,
            autoWidth: false,
            deferRender: true,
            pageLength: 15,
            lengthMenu: [15, 25, 50, 100],
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
                    data: 'premi',
                    name: 'premi',
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
            autoWidth: false,
            deferRender: true,
            pageLength: 15,
            lengthMenu: [15, 25, 50, 100],
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
                    data: 'total_potongan',
                    name: 'total_potongan',
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
                    $('#summaryTahap1Premi').text(formatRupiah(data.total_premi || 0));

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
                    $('#summaryTahap2Potongan').text(formatRupiah(data.total_potongan || 0));

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
            const actionHint = isTahap2 ?
                'Export Excel tersedia untuk tahap 1, tahap 2, dan keseluruhan. Slip WA/Email tersedia untuk tahap 2.' :
                'Export Excel tersedia untuk tahap 1, tahap 2, dan keseluruhan. Slip WA/Email tersedia untuk tahap 1.';

            $('#commandPeriodeText').text(periode);
            $('#stage2GeneratorReadinessPanel').toggleClass('d-none', !isTahap2);
            $('#stageActionHint').text(actionHint);
            $('#btnGenerateGaji .payroll-action-label').text('Generate ' + stageLabel);
            $('.payroll-stage-card').removeClass('is-active');
            $('.payroll-stage-card[data-stage-shortcut="' + (isTahap2 ? '2' : '1') + '"]').addClass('is-active');
        }

        function renderActiveSummary(data, tahap) {
            syncPayrollContext(tahap);
        }

        function reloadActivePayrollTable() {
            if ($('#tahapGaji').val() == '2') {
                tablePenggajianTahap2.ajax.reload(null, false);
                return;
            }

            tablePenggajianTahap1.ajax.reload(null, false);
        }

        function reloadSlipDeliveryLogTable(resetPaging = false) {
            if (tableSlipDeliveryLog) {
                tableSlipDeliveryLog.ajax.reload(null, resetPaging);
            }
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
                        ${renderGeneratorTypeItems(item)}
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

        function renderGeneratorTypeItems(item) {
            const typeItems = item.type_items || [];

            if (typeItems.length === 0) {
                return '';
            }

            return `
                <div class="stage2-generator-types">
                    ${typeItems.map(function(typeItem) {
                        const meta = generatorStateMeta(typeItem);

                        return `
                            <div class="stage2-generator-type ${meta.className}">
                                <span class="stage2-generator-type-icon">
                                    <i class="mdi ${meta.icon}"></i>
                                </span>
                                <span>
                                    <strong>${escapeHtml(typeItem.label || '-')}</strong>
                                    <small>${escapeHtml(typeItem.note || '-')}</small>
                                </span>
                                <span class="stage2-generator-type-count">
                                    ${typeItem.locked_count || 0}/${typeItem.generated_count || 0}
                                </span>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
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

        function initPayrollDoctorSelect(stage) {
            const config = payrollDoctorConfigState[stage];

            $(config.select).select2({
                dropdownParent: $('#modalPayrollDoctorConfig'),
                width: '100%',
                placeholder: stage === 'stage1' ? 'Pilih dokter UGD kontrak...' : 'Pilih dokter...',
                allowClear: true,
                ajax: {
                    url: config.optionsUrl,
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
        }

        initPayrollDoctorSelect('stage1');
        initPayrollDoctorSelect('stage2');

        function normalizeDoctorConfigRow(row) {
            return {
                kd_dokter: row.kd_dokter || row.id || '',
                nm_dokter: row.nm_dokter || '',
                kd_sps: row.kd_sps || null,
                nm_sps: row.nm_sps || null,
                include_salary: !!row.include_salary,
                premium_types: Array.isArray(row.premium_types) ? row.premium_types : []
            };
        }

        function doctorComponentCount(row) {
            return (row.include_salary ? 1 : 0) + ((row.premium_types || []).length);
        }

        function updateDoctorConfigMeta(stage) {
            const config = payrollDoctorConfigState[stage];

            $(config.doctorCount).text(config.rows.length + ' dokter');
            $(config.premiumCount).text(config.premiumOptions.length + ' opsi');
            $(config.salaryLabel).text(config.salaryComponent.label || '-');

            config.rows.forEach(function(row, index) {
                $('.payroll-config-selected-count[data-stage="' + stage + '"][data-index="' + index + '"]')
                    .text(doctorComponentCount(row) + ' komponen');
            });
        }

        function renderDoctorConfigRows(stage) {
            const config = payrollDoctorConfigState[stage];
            const wrap = $(config.rowsWrap);
            wrap.empty();

            $(config.empty).toggleClass('d-none', config.rows.length > 0);
            updateDoctorConfigMeta(stage);

            config.rows.forEach(function(row, index) {
                const premiumCheckboxes = config.premiumOptions.length > 0 ?
                    config.premiumOptions.map(function(option) {
                        const checked = (row.premium_types || []).indexOf(option.id) !== -1 ?
                            'checked' : '';

                        return `
                            <label class="form-check mb-0">
                                <input class="form-check-input payroll-doctor-premium" type="checkbox"
                                    data-stage="${stage}" data-index="${index}" value="${escapeHtml(option.id)}" ${checked}>
                                <span class="form-check-label">${escapeHtml(option.label)}</span>
                            </label>
                        `;
                    }).join('') :
                    '<div class="payroll-config-empty-mini">Opsi premi belum tersedia.</div>';

                wrap.append(`
                    <div class="payroll-config-row" data-stage="${stage}" data-index="${index}">
                        <div class="payroll-config-row-header">
                            <div>
                                <span class="employee-name">${escapeHtml(row.nm_dokter || '-')}</span>
                                <span class="employee-subtext">${escapeHtml(row.kd_dokter || '')} / ${escapeHtml(row.nm_sps || 'Umum')}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="payroll-config-count payroll-config-selected-count" data-stage="${stage}" data-index="${index}">
                                    ${doctorComponentCount(row)} komponen
                                </span>
                                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-payroll-doctor"
                                    data-stage="${stage}" data-index="${index}" title="Hapus">
                                    <i class="mdi mdi-trash-can-outline"></i>
                                </button>
                            </div>
                        </div>

                        <div class="payroll-config-components">
                            <label class="form-check mb-0">
                                <input class="form-check-input payroll-doctor-salary" type="checkbox"
                                    data-stage="${stage}" data-index="${index}" ${row.include_salary ? 'checked' : ''}>
                                <span class="form-check-label">${escapeHtml(config.salaryComponent.label || 'Komponen Gaji')}</span>
                            </label>
                            ${premiumCheckboxes}
                        </div>
                    </div>
                `);
            });

            updateDoctorConfigMeta(stage);
        }

        function setDoctorConfigLoading(stage, isLoading) {
            const config = payrollDoctorConfigState[stage];

            $(config.loading).toggleClass('d-none', !isLoading);
            $(config.rowsWrap).toggleClass('d-none', isLoading);
            if (isLoading) {
                $(config.empty).addClass('d-none');
            }
        }

        function loadDoctorConfig(stage, force = false) {
            const config = payrollDoctorConfigState[stage];

            if (config.loaded && !force) {
                renderDoctorConfigRows(stage);
                return $.Deferred().resolve().promise();
            }

            return $.ajax({
                url: config.configUrl,
                type: "GET",
                beforeSend: function() {
                    setDoctorConfigLoading(stage, true);
                },
                success: function(response) {
                    const data = response.data || {};

                    config.premiumOptions = data.premium_type_options || [];
                    config.salaryComponent = data.salary_component || config.salaryComponent;
                    config.rows = (data.rows || []).map(normalizeDoctorConfigRow);
                    config.loaded = true;
                    renderDoctorConfigRows(stage);
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: xhr.responseJSON?.message ||
                            'Gagal memuat konfigurasi dokter ' + config.label + '.'
                    });
                },
                complete: function() {
                    setDoctorConfigLoading(stage, false);
                }
            });
        }

        function loadPayrollDoctorConfigs(force = false) {
            loadDoctorConfig('stage1', force);
            loadDoctorConfig('stage2', force);
            loadPayrollRoundingConfig(force);
        }

        function normalizePayrollRoundingConfig(data) {
            data = data || {};
            const boolValue = (value, fallback = false) => {
                if (typeof value === 'boolean') {
                    return value;
                }

                if (value === 1 || value === '1' || value === 'true') {
                    return true;
                }

                if (value === 0 || value === '0' || value === 'false') {
                    return false;
                }

                return fallback;
            };

            return {
                premium_received_enabled: boolValue(data.premium_received_enabled, false),
                premium_received_base: Math.max(1, parseInt(data.premium_received_base || 1000, 10)),
                premium_received_mode: ['nearest', 'up', 'down'].includes(data.premium_received_mode) ?
                    data.premium_received_mode : 'up',
                stage1_total_enabled: boolValue(data.stage1_total_enabled, true),
                stage1_total_base: Math.max(1, parseInt(data.stage1_total_base || 1000, 10)),
                stage1_total_mode: ['nearest', 'up', 'down'].includes(data.stage1_total_mode) ?
                    data.stage1_total_mode : 'up',
                stage2_total_enabled: boolValue(data.stage2_total_enabled, true),
                stage2_total_base: Math.max(1, parseInt(data.stage2_total_base || 1000, 10)),
                stage2_total_mode: ['nearest', 'up', 'down'].includes(data.stage2_total_mode) ?
                    data.stage2_total_mode : 'up'
            };
        }

        function fillPayrollRoundingForm(data) {
            const config = normalizePayrollRoundingConfig(data);

            $('#roundingPremiumEnabled').prop('checked', config.premium_received_enabled);
            $('#roundingPremiumBase').val(config.premium_received_base);
            $('#roundingPremiumMode').val(config.premium_received_mode);
            $('#roundingStage1Enabled').prop('checked', config.stage1_total_enabled);
            $('#roundingStage1Base').val(config.stage1_total_base);
            $('#roundingStage1Mode').val(config.stage1_total_mode);
            $('#roundingStage2Enabled').prop('checked', config.stage2_total_enabled);
            $('#roundingStage2Base').val(config.stage2_total_base);
            $('#roundingStage2Mode').val(config.stage2_total_mode);
        }

        function collectPayrollRoundingForm() {
            return normalizePayrollRoundingConfig({
                premium_received_enabled: $('#roundingPremiumEnabled').is(':checked'),
                premium_received_base: $('#roundingPremiumBase').val(),
                premium_received_mode: $('#roundingPremiumMode').val(),
                stage1_total_enabled: $('#roundingStage1Enabled').is(':checked'),
                stage1_total_base: $('#roundingStage1Base').val(),
                stage1_total_mode: $('#roundingStage1Mode').val(),
                stage2_total_enabled: $('#roundingStage2Enabled').is(':checked'),
                stage2_total_base: $('#roundingStage2Base').val(),
                stage2_total_mode: $('#roundingStage2Mode').val()
            });
        }

        function setPayrollRoundingLoading(isLoading) {
            $('#roundingConfigLoading').toggleClass('d-none', !isLoading);
            $('#roundingConfigForm').toggleClass('d-none', isLoading);
        }

        function loadPayrollRoundingConfig(force = false) {
            if (payrollRoundingConfigState.loaded && !force) {
                fillPayrollRoundingForm(payrollRoundingConfigState.data);
                return $.Deferred().resolve().promise();
            }

            return $.ajax({
                url: payrollRoundingConfigState.configUrl,
                type: "GET",
                beforeSend: function() {
                    setPayrollRoundingLoading(true);
                },
                success: function(response) {
                    payrollRoundingConfigState.data = normalizePayrollRoundingConfig(response.data || {});
                    payrollRoundingConfigState.loaded = true;
                    fillPayrollRoundingForm(payrollRoundingConfigState.data);
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: xhr.responseJSON?.message ||
                            'Gagal memuat konfigurasi pembulatan.'
                    });
                },
                complete: function() {
                    setPayrollRoundingLoading(false);
                }
            });
        }

        function savePayrollRoundingConfig() {
            const payload = collectPayrollRoundingForm();
            const periode = $('#periodeGaji').val();

            if (periode) {
                payload.periode = periode;
            }

            return $.ajax({
                url: payrollRoundingConfigState.saveUrl,
                type: "PUT",
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function(response) {
                    payrollRoundingConfigState.data = normalizePayrollRoundingConfig(response.data || payload);
                    payrollRoundingConfigState.loaded = true;
                    fillPayrollRoundingForm(payrollRoundingConfigState.data);
                }
            });
        }

        $('#btnOpenPayrollDoctorConfig').on('click', function() {
            const modal = new bootstrap.Modal(document.getElementById('modalPayrollDoctorConfig'));
            const activeTab = document.querySelector($('#tahapGaji').val() == '2' ?
                '#doctorConfigStage2Tab' :
                '#doctorConfigStage1Tab');

            if (activeTab) {
                new bootstrap.Tab(activeTab).show();
            }

            modal.show();
            loadPayrollDoctorConfigs();
        });

        $(document).on('click', '.btn-add-doctor-config', function() {
            const stage = $(this).data('config-stage');
            const config = payrollDoctorConfigState[stage];
            const selected = $(config.select).select2('data')[0];

            if (!selected) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Dokter belum dipilih',
                    text: 'Pilih dokter terlebih dahulu.'
                });
                return;
            }

            if (config.rows.some(function(row) {
                    return row.kd_dokter === selected.kd_dokter;
                })) {
                Swal.fire({
                    icon: 'info',
                    title: 'Sudah Ada',
                    text: 'Dokter ini sudah masuk konfigurasi ' + config.label + '.'
                });
                return;
            }

            config.rows.push({
                kd_dokter: selected.kd_dokter,
                nm_dokter: selected.nm_dokter,
                kd_sps: selected.kd_sps || null,
                nm_sps: selected.nm_sps || null,
                include_salary: !!config.defaultIncludeSalary,
                premium_types: stage === 'stage2' ? config.premiumOptions.map(function(option) {
                    return option.id;
                }) : []
            });

            $(config.select).val(null).trigger('change');
            renderDoctorConfigRows(stage);
        });

        $(document).on('change', '.payroll-doctor-salary', function() {
            const stage = $(this).data('stage');
            const index = Number($(this).data('index'));
            const config = payrollDoctorConfigState[stage];

            if (config?.rows[index]) {
                config.rows[index].include_salary = $(this).is(':checked');
                updateDoctorConfigMeta(stage);
            }
        });

        $(document).on('change', '.payroll-doctor-premium', function() {
            const stage = $(this).data('stage');
            const index = Number($(this).data('index'));
            const config = payrollDoctorConfigState[stage];

            if (!config?.rows[index]) {
                return;
            }

            config.rows[index].premium_types = $('.payroll-doctor-premium[data-stage="' + stage + '"][data-index="' + index + '"]:checked')
                .map(function() {
                    return $(this).val();
                })
                .get();
            updateDoctorConfigMeta(stage);
        });

        $(document).on('click', '.btn-remove-payroll-doctor', function() {
            const stage = $(this).data('stage');
            const index = Number($(this).data('index'));
            const config = payrollDoctorConfigState[stage];

            config.rows.splice(index, 1);
            renderDoctorConfigRows(stage);
        });

        function invalidDoctorConfigStage() {
            return ['stage1', 'stage2'].find(function(stage) {
                const config = payrollDoctorConfigState[stage];

                return config.rows.some(function(row) {
                    return !row.include_salary && (!row.premium_types || row.premium_types.length === 0);
                });
            });
        }

        function saveDoctorConfig(stage) {
            const config = payrollDoctorConfigState[stage];

            return $.ajax({
                url: config.saveUrl,
                type: "PUT",
                contentType: 'application/json',
                data: JSON.stringify({
                    rows: config.rows
                }),
                success: function(response) {
                    const data = response.data || {};

                    config.premiumOptions = data.premium_type_options || config.premiumOptions;
                    config.salaryComponent = data.salary_component || config.salaryComponent;
                    config.rows = (data.rows || []).map(normalizeDoctorConfigRow);
                    config.loaded = true;
                    renderDoctorConfigRows(stage);
                }
            });
        }

        $('#btnSavePayrollDoctorConfig').on('click', function() {
            if (!payrollDoctorConfigState.stage1.loaded ||
                !payrollDoctorConfigState.stage2.loaded ||
                !payrollRoundingConfigState.loaded) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Masih Memuat',
                    text: 'Tunggu konfigurasi tahap 1, tahap 2, dan pembulatan selesai dimuat.'
                });
                return;
            }

            const invalidStage = invalidDoctorConfigStage();

            if (invalidStage) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Komponen belum dipilih',
                    text: 'Setiap dokter ' + payrollDoctorConfigState[invalidStage].label +
                        ' wajib memiliki minimal satu komponen.'
                });
                return;
            }

            const btn = $(this);
            const btnHtml = btn.html();

            btn.prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...'
            );

            saveDoctorConfig('stage1')
                .then(function() {
                    return saveDoctorConfig('stage2');
                })
                .then(function() {
                    return savePayrollRoundingConfig();
                })
                .done(function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: 'Konfigurasi gaji dan pembulatan berhasil disimpan.',
                        timer: 1600,
                        showConfirmButton: false
                    });

                    reloadActivePayrollTable();
                    loadPayrollSummaries();
                })
                .fail(function(xhr) {
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
                })
                .always(function() {
                    btn.prop('disabled', false).html(btnHtml);
                });
        });

        function setSlipWhatsappLoading(isLoading) {
            $('#waSlipLoading').toggleClass('d-none', !isLoading);
            $('#waSlipTableWrap').toggleClass('d-none', true);
            $('#waSlipEmpty').toggleClass('d-none', true);
        }

        function updateSlipWhatsappSelectedCount() {
            const allRows = $('#waSlipPegawaiList tr');
            const visibleRows = allRows.filter(':visible');
            const total = visibleRows.length;
            const selectedVisible = visibleRows.find('.wa-slip-checkbox:checked').length;
            const selectedTotal = allRows.find('.wa-slip-checkbox:checked').length;
            const totalLabel = allRows.length > 0 && total !== allRows.length ?
                total + ' dari ' + allRows.length + ' penerima' :
                total + ' penerima';

            $('#waSlipRecipientCount').text(totalLabel);
            $('#waSlipSelectedCount').text(selectedTotal + ' dipilih');
            $('#btnSendSlipWhatsapp').prop('disabled', selectedTotal === 0 || allRows.length === 0);

            $('#checkAllSlipWhatsapp')
                .prop('checked', total > 0 && selectedVisible === total)
                .prop('indeterminate', selectedVisible > 0 && selectedVisible < total);
        }

        function activeSlipWhatsappTahap() {
            return $('#tahapGaji').val() == '2' ? 2 : 1;
        }

        function formatSlipWhatsappDelay(seconds) {
            seconds = Number(seconds || 0);

            if (seconds >= 60 && seconds % 60 === 0) {
                return (seconds / 60) + ' menit';
            }

            return seconds + ' detik';
        }

        function currentSlipDeliveryMeta() {
            return slipDeliveryChannels[slipDeliveryChannel] || slipDeliveryChannels.whatsapp;
        }

        function normalizeSlipFilterValue(value) {
            return String(value || '').trim();
        }

        function splitSlipFilterList(value) {
            return normalizeSlipFilterValue(value)
                .split(',')
                .map(function(part) {
                    return part.trim();
                })
                .filter(Boolean);
        }

        function fillSlipFilterSelect(selector, items, valueResolver, labelResolver, placeholder) {
            const select = $(selector);
            const currentValue = select.val() || '';
            const options = {};

            items.forEach(function(item) {
                const values = valueResolver(item);
                const labels = labelResolver(item);
                const valueList = Array.isArray(values) ? values : [values];
                const labelList = Array.isArray(labels) ? labels : [labels];

                valueList.forEach(function(rawValue, index) {
                    const value = normalizeSlipFilterValue(rawValue);
                    const label = normalizeSlipFilterValue(labelList[index] || rawValue);

                    if (value && !options[value]) {
                        options[value] = label || value;
                    }
                });
            });

            const sortedOptions = Object.keys(options)
                .map(function(value) {
                    return {
                        value: value,
                        label: options[value]
                    };
                })
                .sort(function(a, b) {
                    return a.label.localeCompare(b.label, 'id', {
                        sensitivity: 'base'
                    });
                });

            select.empty().append($('<option>', {
                value: '',
                text: placeholder
            }));

            sortedOptions.forEach(function(option) {
                select.append($('<option>', {
                    value: option.value,
                    text: option.label
                }));
            });

            select.val(sortedOptions.some(function(option) {
                return option.value === currentValue;
            }) ? currentValue : '');
        }

        function populateSlipWhatsappFilters(items) {
            fillSlipFilterSelect(
                '#filterSlipStatus',
                items,
                function(item) {
                    return item.status || item.status_label;
                },
                function(item) {
                    return item.status_label || item.status;
                },
                'Semua Status'
            );

            fillSlipFilterSelect(
                '#filterSlipUnit',
                items,
                function(item) {
                    return splitSlipFilterList(item.unit_kerja);
                },
                function(item) {
                    return splitSlipFilterList(item.unit_kerja);
                },
                'Semua Unit Kerja'
            );

            fillSlipFilterSelect(
                '#filterSlipJabatan',
                items,
                function(item) {
                    return item.jabatan;
                },
                function(item) {
                    return item.jabatan;
                },
                'Semua Jabatan'
            );
        }

        function resetSlipWhatsappFilters() {
            $('#searchSlipWhatsappPegawai').val('');
            $('#filterSlipStatus').val('');
            $('#filterSlipUnit').val('');
            $('#filterSlipJabatan').val('');
        }

        function updateSlipWhatsappStageLabels(tahap) {
            const label = 'Tahap ' + tahap;
            const meta = currentSlipDeliveryMeta();

            $('#modalSlipWhatsappLabel').text('Kirim Slip Gaji ' + meta.label + ' ' + label);
            $('#waSlipStageBadge')
                .removeClass('bg-success-subtle text-success bg-primary-subtle text-primary')
                .addClass(meta.badgeClass)
                .text((tahap === 2 ? 'Tahap 2' : 'Tahap 1 + 2') + ' / ' + meta.shortLabel);
            $('#waSlipModalSubtitle').text(tahap === 2 ?
                'Slip gaji tahap 2, termasuk dokter jika datanya tersedia.' :
                'Slip gabungan tahap 1 + 2, termasuk dokter jika datanya tersedia.'
            );
            $('#slipDeliveryModalIconWrap')
                .removeClass('is-green is-blue is-amber')
                .addClass(meta.iconWrapClass);
            $('#slipDeliveryModalIcon').attr('class', 'mdi ' + meta.icon + ' mdi-24px');
            $('#slipDeliveryContactHeader').text(meta.contactHeader);
            $('#slipDeliveryEmptyIcon')
                .attr('class', 'mdi ' + meta.emptyIcon + ' mdi-36px text-muted d-block mb-2');
            $('#slipDeliveryEmptyTitle').text('Belum ada penerima');
            $('#slipDeliveryEmptyText').text(meta.emptyText);
            $('#slipDeliveryLoadingText').text(meta.loadingText);
            $('#btnSendSlipWhatsapp')
                .removeClass('btn-success btn-primary')
                .addClass(meta.buttonClass);
            $('#slipDeliverySendText').text(meta.sendText);
        }

        function slipDeliveryStatusMeta(item) {
            const status = item.delivery_status || 'pending';

            if (status === 'success') {
                return {
                    className: 'bg-success-subtle text-success',
                    icon: 'mdi-check-circle-outline',
                    label: item.delivery_status_label || 'Berhasil'
                };
            }

            if (status === 'failed') {
                return {
                    className: 'bg-danger-subtle text-danger',
                    icon: 'mdi-alert-circle-outline',
                    label: item.delivery_status_label || 'Gagal'
                };
            }

            return {
                className: 'bg-secondary-subtle text-secondary',
                icon: 'mdi-clock-outline',
                label: item.delivery_status_label || 'Belum'
            };
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
                const meta = currentSlipDeliveryMeta();
                const statusClass = item.status === 'T' ? 'is-tetap' : item.status === 'FT' ?
                    'is-kontrak' : 'is-unknown';
                const statusLabel = item.status_label || '-';
                const unitKerja = item.unit_kerja || item.jabatan || '-';
                const contactPrimary = slipDeliveryChannel === 'email' ?
                    (item.email || '-') :
                    (item.no_whatsapp || item.no_telp || '-');
                const contactSecondary = slipDeliveryChannel === 'email' ?
                    (item.no_telp ? 'WA: ' + item.no_telp : '') :
                    (item.no_telp || '');
                const deliveryMeta = slipDeliveryStatusMeta(item);
                const deliveryNote = item.delivery_processed_at ?
                    item.delivery_processed_at :
                    (item.delivery_status_message || 'Belum ada log periode ini.');
                const searchable = [
                    item.nama,
                    item.nik,
                    item.jabatan,
                    unitKerja,
                    item.status,
                    statusLabel,
                    item.no_telp,
                    item.no_whatsapp,
                    item.email,
                    meta.label,
                    item.delivery_status,
                    item.delivery_status_label,
                    item.delivery_status_message,
                    item.is_doctor_slip ? 'dokter' : ''
                ].join(' ').toLowerCase();

                tbody.append(`
                    <tr data-search="${escapeHtml(searchable)}"
                        data-status="${escapeHtml(item.status || item.status_label || '')}"
                        data-unit="${escapeHtml(unitKerja)}"
                        data-jabatan="${escapeHtml(item.jabatan || '')}">
                        <td class="text-center">
                            <input class="form-check-input wa-slip-checkbox" type="checkbox"
                                value="${item.id}" id="waSlipPegawai${item.id}">
                        </td>
                        <td>
                            <span class="employee-name">${escapeHtml(item.nama || '-')}</span>
                            <span class="employee-subtext">${escapeHtml(item.nik || '')}${item.is_doctor_slip ? ' / Dokter' : ''}</span>
                        </td>
                        <td>${escapeHtml(item.jabatan || '-')}</td>
                        <td>${escapeHtml(unitKerja)}</td>
                        <td class="text-center">
                            <span class="payroll-status-badge ${statusClass}">${escapeHtml(statusLabel)}</span>
                        </td>
                        <td class="wa-slip-contact-cell">
                            <span class="fw-semibold">${escapeHtml(contactPrimary)}</span>
                            <span class="employee-subtext">${escapeHtml(contactSecondary)}</span>
                        </td>
                        <td class="wa-slip-delivery-cell">
                            <span class="wa-slip-delivery-badge ${deliveryMeta.className}"
                                title="${escapeHtml(item.delivery_status_message || '')}">
                                <i class="mdi ${deliveryMeta.icon}"></i>
                                ${escapeHtml(deliveryMeta.label)}
                            </span>
                            <span class="employee-subtext">${escapeHtml(deliveryNote)}</span>
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
            const status = normalizeSlipFilterValue($('#filterSlipStatus').val());
            const unit = normalizeSlipFilterValue($('#filterSlipUnit').val());
            const jabatan = normalizeSlipFilterValue($('#filterSlipJabatan').val());
            let visibleCount = 0;

            $('#waSlipPegawaiList tr').each(function() {
                const searchable = $(this).data('search') || '';
                const rowStatus = normalizeSlipFilterValue($(this).data('status'));
                const rowUnits = splitSlipFilterList($(this).data('unit'));
                const rowJabatan = normalizeSlipFilterValue($(this).data('jabatan'));
                const matches = searchable.indexOf(keyword) !== -1 &&
                    (!status || rowStatus === status) &&
                    (!unit || rowUnits.indexOf(unit) !== -1) &&
                    (!jabatan || rowJabatan === jabatan);

                $(this).toggle(matches);

                if (matches) {
                    visibleCount++;
                }
            });

            if ($('#waSlipPegawaiList tr').length > 0 && visibleCount === 0) {
                $('#waSlipTableWrap').addClass('d-none');
                $('#waSlipEmpty').removeClass('d-none');
                $('#slipDeliveryEmptyIcon')
                    .attr('class', 'mdi mdi-filter-remove-outline mdi-36px text-muted d-block mb-2');
                $('#slipDeliveryEmptyTitle').text('Tidak ada hasil filter');
                $('#slipDeliveryEmptyText').text('Ubah status, unit kerja, jabatan, atau kata kunci pencarian.');
            } else if ($('#waSlipPegawaiList tr').length > 0) {
                $('#waSlipTableWrap').removeClass('d-none');
                $('#waSlipEmpty').addClass('d-none');
                $('#slipDeliveryEmptyTitle').text('Belum ada penerima');
                $('#slipDeliveryEmptyText').text(currentSlipDeliveryMeta().emptyText);
            }

            updateSlipWhatsappSelectedCount();
        }

        function loadSlipWhatsappRecipients() {
            const periode = $('#periodeGaji').val();
            const tahap = activeSlipWhatsappTahap();

            updateSlipWhatsappStageLabels(tahap);
            $('#waSlipPeriode').text(periode || '-');
            $('#waSlipRecipientCount').text('0 penerima');
            resetSlipWhatsappFilters();
            $('#checkAllSlipWhatsapp').prop('checked', false).prop('indeterminate', false);
            $('#btnSendSlipWhatsapp').prop('disabled', true);

            $.ajax({
                url: "{{ route("backOffice.keuangan.penggajian.getPenerimaSlip") }}",
                type: "GET",
                data: {
                    periode: periode,
                    tahap: tahap,
                    channel: slipDeliveryChannel
                },
                beforeSend: function() {
                    setSlipWhatsappLoading(true);
                },
                success: function(response) {
                    slipWhatsappRecipients = response.data || [];
                    populateSlipWhatsappFilters(slipWhatsappRecipients);
                    renderSlipWhatsappRecipients(slipWhatsappRecipients);
                    filterSlipWhatsappRows();
                },
                error: function(xhr) {
                    slipWhatsappRecipients = [];
                    populateSlipWhatsappFilters([]);
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

        function deliveryLogFilterText() {
            const labels = [];
            const tahap = $('#filterDeliveryLogTahap').val();
            const channel = $('#filterDeliveryLogChannel').val();
            const status = $('#filterDeliveryLogStatus').val();

            if (tahap) {
                labels.push('Tahap ' + tahap);
            }

            if (channel) {
                labels.push(channel === 'email' ? 'Email' : 'WhatsApp');
            }

            if (status) {
                labels.push(status === 'success' ? 'Berhasil' : 'Gagal');
            }

            return labels.length ? labels.join(' / ') : 'Semua log';
        }

        function updateSlipDeliveryLogMeta() {
            $('#slipDeliveryLogPeriode').text($('#periodeGaji').val() || '-');
            $('#slipDeliveryLogFilterText').text(deliveryLogFilterText());
        }

        function markSlipDeliveryLogRefreshed() {
            $('#slipDeliveryLogRefreshedAt').text(new Date().toLocaleString('id-ID', {
                hour12: false
            }));
        }

        function initSlipDeliveryLogTable() {
            if (tableSlipDeliveryLog) {
                return;
            }

            tableSlipDeliveryLog = $('#tableSlipDeliveryLog').DataTable({
                processing: true,
                serverSide: true,
                responsive: false,
                autoWidth: false,
                deferRender: true,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [],
                dom: "<'row g-2 align-items-center mb-2'<'col-12 col-md-6'l><'col-12 col-md-6'f>>" +
                    "rt" +
                    "<'row g-2 align-items-center mt-3'<'col-12 col-md-6'i><'col-12 col-md-6 d-flex justify-content-md-end'p>>",
                language: {
                    lengthMenu: 'Tampilkan _MENU_ log',
                    search: 'Cari:',
                    info: 'Menampilkan _START_ - _END_ dari _TOTAL_ log',
                    infoEmpty: 'Belum ada log',
                    zeroRecords: 'Log pengiriman belum tersedia',
                    processing: 'Memuat log...',
                    paginate: {
                        previous: '<i class="mdi mdi-chevron-left"></i>',
                        next: '<i class="mdi mdi-chevron-right"></i>'
                    }
                },
                ajax: {
                    url: "{{ route("backOffice.keuangan.penggajian.getSlipDeliveryLogTable") }}",
                    type: "GET",
                    data: function(d) {
                        d.periode = $('#periodeGaji').val();
                        d.tahap = $('#filterDeliveryLogTahap').val();
                        d.channel = $('#filterDeliveryLogChannel').val();
                        d.status = $('#filterDeliveryLogStatus').val();
                    },
                    complete: function() {
                        markSlipDeliveryLogRefreshed();
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
                        data: 'processed_at',
                        name: 'processed_at'
                    },
                    {
                        data: 'channel_badge',
                        name: 'channel',
                        orderable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'status_badge',
                        name: 'status',
                        orderable: false,
                        className: 'text-center'
                    },
                    {
                        data: 'tahap_label',
                        name: 'tahap',
                        className: 'text-center'
                    },
                    {
                        data: 'pegawai',
                        name: 'nama',
                        orderable: false
                    },
                    {
                        data: 'contact',
                        name: 'contact'
                    },
                    {
                        data: 'message',
                        name: 'message',
                        orderable: false
                    }
                ]
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
                const sourcePeriod = item.source_period_label ?
                    `<span class="employee-subtext">Periode sumber: ${escapeHtml(item.source_period_label)}</span>` :
                    '';

                html += `
                    <div class="slip-detail-row">
                        <span>${escapeHtml(item.nama || 'Tunjangan')}${sourcePeriod}</span>
                        <strong>${formatRupiah(item.nominal || 0)}</strong>
                    </div>
                `;
            });

            $('#slipTunjanganDetail').html(html);
        }

        function renderPremiTahap1Detail(items) {
            if (!items || items.length === 0) {
                $('#slipPremiDetail').html(
                    '<div class="slip-empty-row">Tidak ada premi yang dipilih pada konfigurasi</div>'
                );
                return;
            }

            let html = '';

            items.forEach(function(item) {
                html += `
                    <div class="slip-detail-row">
                        <span>${escapeHtml(item.nama || 'Premi Dokter')}</span>
                        <strong>${formatRupiah(item.nominal || 0)}</strong>
                    </div>
                `;
            });

            $('#slipPremiDetail').html(html);
        }

        function renderPotonganDetail(items, emptyText = 'Tidak ada potongan') {
            if (!items || items.length === 0) {
                $('#slipPotonganDetail').html(
                    '<div class="slip-empty-row">' + escapeHtml(emptyText) + '</div>'
                );
                return;
            }

            let html = '';

            items.forEach(function(item) {
                const notes = [];

                if (item.keterangan) {
                    notes.push(escapeHtml(item.keterangan));
                }

                if (item.tipe === 'persen_total_gaji') {
                    if (Number(item.total_tahap1 || 0) > 0) {
                        notes.push(`Total gaji tahap 1: ${formatRupiah(item.total_tahap1 || 0)}`);
                    }

                    if (Number(item.total_tahap2 || 0) > 0) {
                        notes.push(`Total gaji tahap 2: ${formatRupiah(item.total_tahap2 || 0)}`);
                    }

                    if (Number(item.basis || 0) > 0) {
                        notes.push(`Dasar potongan tahap 1 + 2: ${formatRupiah(item.basis || 0)}`);
                    }
                }

                const note = notes.length ?
                    `<span class="employee-subtext">${notes.join('<br>')}</span>` :
                    '';

                html += `
                    <div class="slip-detail-row">
                        <span>${escapeHtml(item.nama || 'Potongan')}${note}</span>
                        <strong>${formatRupiah(item.nominal || 0)}</strong>
                    </div>
                `;
            });

            $('#slipPotonganDetail').html(html);
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
                    $('#slipPremiUtama').text(formatRupiah(data.premi || 0));
                    $('#slipPremiUtamaRow').toggleClass('d-none', Number(data.premi || 0) <= 0);
                    $('#slipTunjangan').text(formatRupiah(data.tunjangan));
                    $('#slipPremi').text(formatRupiah(data.premi || 0));
                    $('#slipTotal').text(formatRupiah(data.total));
                    $('#slipDetailListTitle').text('Rincian Tunjangan');
                    $('#slipTotalTunjanganLabel').text('Total Tunjangan');
                    $('#slipPotonganBox').addClass('d-none');
                    $('#slipPremiBox').removeClass('d-none');

                    $('#slipStatus')
                        .removeClass('is-tetap is-kontrak is-unknown')
                        .addClass(data.status === 'T' ? 'is-tetap' : data.status === 'FT' ?
                            'is-kontrak' : 'is-unknown')
                        .text(data.status_label || '-');
                    renderTunjanganDetail(data.tunjangan_detail || []);
                    renderPremiTahap1Detail(data.premi_detail || []);

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
                    $('#slipPremiUtamaRow').addClass('d-none');
                    $('#slipTunjangan').text(formatRupiah(data.total_premi));
                    $('#slipTotal').text(formatRupiah(data.total));
                    $('#slipDetailListTitle').text('Rincian Premi Generator');
                    $('#slipTotalTunjanganLabel').text('Total Premi');
                    $('#slipPotongan').text(formatRupiah(data.total_potongan || 0));
                    $('#slipPotonganBox').removeClass('d-none');
                    $('#slipPremiBox').addClass('d-none');

                    $('#slipStatus')
                        .removeClass('is-tetap is-kontrak is-unknown')
                        .addClass(data.status === 'T' ? 'is-tetap' : data.status === 'FT' ?
                            'is-kontrak' : 'is-unknown')
                        .text(data.status_label || '-');
                    renderTunjanganDetail(data.premi_detail || [], 'Tidak ada premi generator');
                    renderPotonganDetail(data.potongan_detail || [], 'Tidak ada potongan');

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

        $('#btnOpenSlipDeliveryLog').on('click', function() {
            if (!$('#periodeGaji').val()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Periode belum dipilih',
                    text: 'Silakan pilih bulan periode gaji terlebih dahulu.'
                });
                return;
            }

            $('#filterDeliveryLogTahap').val('');
            $('#filterDeliveryLogChannel').val('');
            $('#filterDeliveryLogStatus').val('');
            updateSlipDeliveryLogMeta();

            const modal = new bootstrap.Modal(document.getElementById('modalSlipDeliveryLog'));
            modal.show();
            initSlipDeliveryLogTable();
            reloadSlipDeliveryLogTable(true);

            setTimeout(function() {
                tableSlipDeliveryLog.columns.adjust();
            }, 150);
        });

        $('#btnRefreshSlipDeliveryLog').on('click', function() {
            updateSlipDeliveryLogMeta();
            reloadSlipDeliveryLogTable(false);
        });

        $('#filterDeliveryLogTahap, #filterDeliveryLogChannel, #filterDeliveryLogStatus').on('change', function() {
            updateSlipDeliveryLogMeta();
            reloadSlipDeliveryLogTable(true);
        });

        $('#btnExportGajiExcel').on('click', function() {
            const periode = $('#periodeGaji').val();

            if (!periode) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Periode belum dipilih',
                    text: 'Silakan pilih bulan periode gaji terlebih dahulu.'
                });
                return;
            }

            Swal.fire({
                icon: 'question',
                title: 'Export Excel Penggajian',
                text: 'Pilih data gaji yang ingin diexport.',
                input: 'select',
                inputOptions: {
                    tahap1: 'Tahap 1',
                    tahap2: 'Tahap 2',
                    keseluruhan: 'Keseluruhan Tahap 1 + Tahap 2'
                },
                inputValue: $('#tahapGaji').val() == '2' ? 'tahap2' : 'tahap1',
                showCancelButton: true,
                confirmButtonText: 'Export',
                cancelButtonText: 'Batal',
                inputValidator: function(value) {
                    if (!value) {
                        return 'Pilih jenis export terlebih dahulu.';
                    }
                }
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                window.location.href =
                    "{{ route("backOffice.keuangan.penggajian.exportGajiExcel") }}" +
                    '?periode=' + encodeURIComponent(periode) +
                    '&jenis=' + encodeURIComponent(result.value);
            });
        });

        $('.slip-delivery-option').on('click', function() {
            if (!$('#periodeGaji').val()) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Periode belum dipilih',
                    text: 'Silakan pilih bulan periode gaji terlebih dahulu.'
                });
                return;
            }

            slipDeliveryChannel = $(this).data('channel') === 'email' ? 'email' : 'whatsapp';
            const modal = new bootstrap.Modal(document.getElementById('modalSlipWhatsapp'));
            modal.show();
            loadSlipWhatsappRecipients();
        });

        $('#checkAllSlipWhatsapp').on('change', function() {
            $('#waSlipPegawaiList tr:visible .wa-slip-checkbox').prop('checked', $(this).is(':checked'));
            updateSlipWhatsappSelectedCount();
        });

        $(document).on('change', '.wa-slip-checkbox', function() {
            updateSlipWhatsappSelectedCount();
        });

        $('#searchSlipWhatsappPegawai').on('keyup', function() {
            filterSlipWhatsappRows();
        });

        $('#filterSlipStatus, #filterSlipUnit, #filterSlipJabatan').on('change', function() {
            filterSlipWhatsappRows();
        });

        $('#btnSendSlipWhatsapp').on('click', function() {
            const periode = $('#periodeGaji').val();
            const tahap = activeSlipWhatsappTahap();
            const meta = currentSlipDeliveryMeta();
            const selectedIds = $('#waSlipPegawaiList .wa-slip-checkbox:checked').map(function() {
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
                text: selectedIds.length + ' slip gaji tahap ' + tahap +
                    meta.confirmText,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Antrekan',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: "{{ route("backOffice.keuangan.penggajian.kirimSlipGaji") }}",
                    type: "POST",
                    data: {
                        periode: periode,
                        tahap: tahap,
                        channel: slipDeliveryChannel,
                        gaji_ids: selectedIds
                    },
                    beforeSend: function() {
                        btn.prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm me-1"></span> Menyiapkan...'
                        );
                    },
                    success: function(response) {
                        const queued = response.data?.queued || selectedIds.length;
                        const delaySeconds = response.data?.delay_seconds || 600;
                        const modalEl = document.getElementById('modalSlipWhatsapp');
                        const modal = bootstrap.Modal.getInstance(modalEl);

                        if (modal) {
                            modal.hide();
                        }

                        Swal.fire({
                            icon: 'success',
                            title: meta.successTitle,
                            text: queued + ' slip gaji akan dikirim oleh queue dengan jeda sekitar ' +
                                formatSlipWhatsappDelay(delaySeconds) + '.',
                            timer: 2600,
                            showConfirmButton: false
                        });

                        reloadSlipDeliveryLogTable(false);
                    },
                    error: function(xhr) {
                        let message = xhr.responseJSON?.message ||
                            meta.errorText;

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
