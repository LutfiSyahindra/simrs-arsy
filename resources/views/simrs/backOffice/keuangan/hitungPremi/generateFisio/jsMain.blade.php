<script>
    $(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const typeConfig = {
            umum: 'Umum',
            bpjs: 'BPJS Kesehatan'
        };
        let activeType = 'umum';
        let previewReady = false;
        let currentConfig = null;

        const generateModal = modalInstance('modalGenerateFisio');
        const configModal = modalInstance('modalConfigFisio');
        const detailModal = modalInstance('modalDetailFisio');

        function modalInstance(id) {
            const el = document.getElementById(id);
            if (window.bootstrap && bootstrap.Modal && el) {
                return bootstrap.Modal.getOrCreateInstance ?
                    bootstrap.Modal.getOrCreateInstance(el) :
                    new bootstrap.Modal(el);
            }

            return {
                show: () => $('#' + id).modal('show'),
                hide: () => $('#' + id).modal('hide')
            };
        }

        function formatNumber(value) {
            return new Intl.NumberFormat('id-ID').format(Number(value) || 0);
        }

        function formatRupiah(value) {
            return 'Rp ' + formatNumber(value);
        }

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        function flattenErrors(errors) {
            const messages = [];
            Object.keys(errors || {}).forEach(function(key) {
                (Array.isArray(errors[key]) ? errors[key] : [errors[key]]).forEach(function(message) {
                    messages.push(message);
                });
            });
            return messages;
        }

        function errorMessage(xhr) {
            const response = xhr.responseJSON || {};
            return response.errors ? flattenErrors(response.errors).join('<br>') :
                (response.message || 'Terjadi kesalahan saat memproses data.');
        }

        function setDefaultPeriod() {
            const queryPeriod = new URLSearchParams(window.location.search).get('periode');
            if (/^\d{4}-\d{2}$/.test(queryPeriod || '')) {
                $('#periodeFisio').val(queryPeriod);
                return;
            }

            const now = new Date();
            $('#periodeFisio').val(now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
        }

        function periodFromInput() {
            const parts = String($('#periodeFisio').val() || '').split('-').map(Number);
            return parts.length === 2 && parts[0] && parts[1] ?
                new Date(parts[0], parts[1] - 1, 1) :
                new Date();
        }

        function setPeriodFromDate(date) {
            $('#periodeFisio')
                .val(date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0'))
                .trigger('change');
        }

        function movePeriod(offset) {
            const date = periodFromInput();
            date.setMonth(date.getMonth() + offset);
            setPeriodFromDate(date);
        }

        function percentText(value) {
            return formatNumber(value || 0) + '%';
        }

        function nominalText(value) {
            return formatRupiah(value || 0) + (activeType === 'bpjs' ? ' x pasien' : ' tetap');
        }

        function modeLabel(mode, percent, nominal, baseLabel) {
            if (mode === 'nominal') {
                return nominalText(nominal);
            }

            return percentText(percent) + ' dari ' + baseLabel;
        }

        function infoRowsHtml(rows) {
            return rows.map(function(row) {
                return '<div class="fisio-info-row">' +
                    '<div>' +
                    '<div class="fisio-info-label">' + escapeHtml(row.label) + '</div>' +
                    (row.note ? '<div class="fisio-info-note">' + escapeHtml(row.note) + '</div>' : '') +
                    '</div>' +
                    '<div class="fisio-info-value">' + escapeHtml(row.value) + '</div>' +
                    '</div>';
            }).join('');
        }

        function formulaHtml(data) {
            const config = data.config || data || {};
            const type = data.jenis_fisio || config.jenis_fisio || activeType;

            return infoRowsHtml([
                {
                    label: 'Grand Total',
                    value: type === 'bpjs' ?
                        'Pasien x ' + formatRupiah(config.grand_nominal || 0) :
                        'Total subtotal tindakan',
                    note: type === 'bpjs' ? 'Nominal BPJS per pasien.' : 'Setiap subtotal = harga tindakan x jumlah.'
                },
                {
                    label: 'Petugas 1',
                    value: modeLabel(config.petugas1_mode, config.petugas1_percent, config.petugas1_nominal, 'grand total'),
                    note: 'Default UMUM 50%, BPJS Rp 4.000 per pasien.'
                },
                {
                    label: 'Petugas 2',
                    value: modeLabel(config.petugas2_mode, config.petugas2_percent, config.petugas2_nominal, 'Petugas 1'),
                    note: 'Default 50% dari nilai Petugas 1.'
                },
                {
                    label: 'Premi Bersama',
                    value: modeLabel(config.bersama_mode, config.bersama_percent, config.bersama_nominal, 'Petugas 1'),
                    note: 'Default 50% dari nilai Petugas 1.'
                }
            ]);
        }

        function miniCard(label, value, primary, icon, note) {
            return '<div class="fisio-mini-card ' + (primary ? 'total' : '') + '">' +
                '<div class="d-flex align-items-start justify-content-between gap-2">' +
                '<div class="fisio-mini-label">' + escapeHtml(label) + '</div>' +
                '<i class="mdi ' + escapeHtml(icon || 'mdi-chart-box-outline') + ' text-muted"></i>' +
                '</div>' +
                '<div class="fisio-mini-value">' + escapeHtml(value) + '</div>' +
                (note ? '<div class="fisio-field-help mt-1">' + escapeHtml(note) + '</div>' : '') +
                '</div>';
        }

        function recipientListHtml(items, options) {
            const showTotal = !options || options.showTotal !== false;

            if (!items || !items.length) {
                return '<div class="fisio-empty-state">Petugas penerima belum dipilih.</div>';
            }

            return items.map(function(item) {
                return '<div class="fisio-recipient-item">' +
                    '<div>' +
                    '<div class="fisio-recipient-name">' + escapeHtml(item.role_label || '-') + ' - ' +
                    escapeHtml(item.pegawai_name || '-') + '</div>' +
                    '<div class="fisio-recipient-meta">' +
                    escapeHtml(item.pegawai_id || '-') + ' / ' + escapeHtml(item.pegawai_position || '-') +
                    (item.allocation_percent ? ' / ' + percentText(item.allocation_percent) : '') +
                    '</div>' +
                    '</div>' +
                    '<div class="fisio-recipient-total">' +
                    (showTotal ? formatRupiah(item.total_received || 0) : '<span class="text-muted">Dipilih</span>') +
                    '</div>' +
                    '</div>';
            }).join('');
        }

        function sourceListHtml(items) {
            if (!items || !items.length) {
                return '<div class="fisio-empty-state">Belum ada rincian tindakan.</div>';
            }

            return items.map(function(item) {
                const title = item.nama_tindakan || item.source_label || 'Jumlah pasien BPJS';
                const meta = formatRupiah(item.harga_tindakan || 0) + ' x ' + formatNumber(item.jumlah || 0);

                return '<div class="fisio-recipient-item">' +
                    '<div>' +
                    '<div class="fisio-recipient-name">' + escapeHtml(title) + '</div>' +
                    '<div class="fisio-recipient-meta">' + escapeHtml(meta) + '</div>' +
                    '</div>' +
                    '<div class="fisio-recipient-total">' + formatRupiah(item.subtotal || 0) + '</div>' +
                    '</div>';
            }).join('');
        }

        function initGenerateItemSelect(row) {
            if (!$.fn.select2) {
                return;
            }

            $(row).find('.generate-fisio-tindakan').select2({
                dropdownParent: $('#modalGenerateFisio'),
                width: '100%',
                placeholder: 'Pilih tindakan fisio',
                ajax: {
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateFisio.tindakanOptions") }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ q: params.term || '' }),
                    processResults: response => ({
                        results: (response.data || []).map(item => ({ id: item.id, text: item.text }))
                    })
                }
            });
        }

        function generateItemRow() {
            return '<tr class="generate-fisio-item-row">' +
                '<td><select class="form-select generate-fisio-tindakan"></select></td>' +
                '<td><input type="number" min="1" step="1" class="form-control form-control-sm generate-fisio-jumlah" value="1"></td>' +
                '<td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-generate-fisio-item" title="Hapus"><i class="mdi mdi-delete-outline"></i></button></td>' +
                '</tr>';
        }

        function addGenerateItemRow() {
            $('#generateFisioItemRows').append(generateItemRow());
            initGenerateItemSelect($('#generateFisioItemRows tr').last());
            $('#generateFisioItemEmpty').addClass('d-none');
            resetGeneratePreview();
        }

        function generateItemsPayload() {
            return $('.generate-fisio-item-row').map(function() {
                const row = $(this);

                return {
                    tindakan_id: row.find('.generate-fisio-tindakan').val(),
                    jumlah: row.find('.generate-fisio-jumlah').val()
                };
            }).get();
        }

        function initSelects() {
            if (!$.fn.select2) {
                return;
            }

            ['#configFisioPetugas1', '#configFisioPetugas2'].forEach(function(selector) {
                $(selector).select2({
                    dropdownParent: $('#modalConfigFisio'),
                    width: '100%',
                    placeholder: 'Pilih pegawai',
                    allowClear: true,
                    ajax: {
                        url: "{{ route("backOffice.keuangan.hitungPremi.generateFisio.pegawaiOptions") }}",
                        dataType: 'json',
                        delay: 250,
                        data: params => ({ q: params.term || '' }),
                        processResults: response => ({
                            results: (response.data || []).map(item => ({ id: item.id, text: item.text }))
                        })
                    }
                });
            });
        }

        function fillSingleSelect(selector, item) {
            const select = $(selector);
            select.empty();

            if (item && item.pegawai_id) {
                select.append(new Option(item.text || (item.pegawai_id + ' - ' + item.pegawai_name), item.pegawai_id, true, true));
            }

            select.trigger('change');
        }

        function selectedPegawai(selector, roleLabel) {
            const option = $(selector).find('option:selected');
            const value = $(selector).val();

            if (!value) {
                return null;
            }

            const text = option.text() || value;
            const name = text.includes(' - ') ? text.split(' - ').slice(1).join(' - ') : text;

            return {
                role_label: roleLabel,
                pegawai_id: value,
                pegawai_name: name,
                pegawai_position: 'Penerima aktif',
                total_received: 0
            };
        }

        function actionRow(item) {
            const isActive = item && item.is_active === false ? '' : 'checked';

            return '<tr class="fisio-action-row">' +
                '<td><input type="hidden" class="fisio-action-id" value="' + escapeHtml(item && item.id ? item.id : '') + '">' +
                '<input type="text" class="form-control form-control-sm fisio-action-code" value="' + escapeHtml(item && item.kode_tindakan ? item.kode_tindakan : '') + '"></td>' +
                '<td><input type="text" class="form-control form-control-sm fisio-action-name" value="' + escapeHtml(item && item.nama_tindakan ? item.nama_tindakan : '') + '"></td>' +
                '<td><div class="input-group input-group-sm"><span class="input-group-text">Rp</span><input type="number" min="0" class="form-control fisio-action-price" value="' + escapeHtml(item && item.harga ? item.harga : 0) + '"></div></td>' +
                '<td><div class="form-check form-switch"><input class="form-check-input fisio-action-active" type="checkbox" ' + isActive + '></div></td>' +
                '<td><input type="text" class="form-control form-control-sm fisio-action-note" value="' + escapeHtml(item && item.note ? item.note : '') + '"></td>' +
                '<td><button type="button" class="btn btn-outline-danger btn-sm btn-remove-fisio-action" title="Hapus"><i class="mdi mdi-delete-outline"></i></button></td>' +
                '</tr>';
        }

        function renderActionRows(actions) {
            const rows = (actions || []).map(actionRow).join('');
            $('#configFisioActionRows').html(rows);
            $('#configFisioActionEmpty').toggleClass('d-none', !!rows);
        }

        function actionPayload() {
            return $('.fisio-action-row').map(function() {
                const row = $(this);

                return {
                    id: row.find('.fisio-action-id').val(),
                    kode_tindakan: row.find('.fisio-action-code').val(),
                    nama_tindakan: row.find('.fisio-action-name').val(),
                    harga: row.find('.fisio-action-price').val(),
                    is_active: row.find('.fisio-action-active').is(':checked') ? 1 : 0,
                    note: row.find('.fisio-action-note').val()
                };
            }).get();
        }

        function configPayload() {
            return {
                jenis_fisio: $('#jenisConfigFisio').val(),
                grand_nominal: $('#configFisioGrandNominal').val(),
                petugas1_mode: $('#configFisioPetugas1Mode').val(),
                petugas1_percent: $('#configFisioPetugas1Percent').val(),
                petugas1_nominal: $('#configFisioPetugas1Nominal').val(),
                petugas2_mode: $('#configFisioPetugas2Mode').val(),
                petugas2_percent: $('#configFisioPetugas2Percent').val(),
                petugas2_nominal: $('#configFisioPetugas2Nominal').val(),
                bersama_mode: $('#configFisioBersamaMode').val(),
                bersama_percent: $('#configFisioBersamaPercent').val(),
                bersama_nominal: $('#configFisioBersamaNominal').val(),
                petugas1_id: $('#configFisioPetugas1').val(),
                petugas2_id: $('#configFisioPetugas2').val(),
                tindakan: actionPayload()
            };
        }

        function updateConfigOverview() {
            const type = activeType;
            const config = {
                jenis_fisio: type,
                grand_nominal: Number($('#configFisioGrandNominal').val() || 0),
                petugas1_mode: $('#configFisioPetugas1Mode').val(),
                petugas1_percent: Number($('#configFisioPetugas1Percent').val() || 0),
                petugas1_nominal: Number($('#configFisioPetugas1Nominal').val() || 0),
                petugas2_mode: $('#configFisioPetugas2Mode').val(),
                petugas2_percent: Number($('#configFisioPetugas2Percent').val() || 0),
                petugas2_nominal: Number($('#configFisioPetugas2Nominal').val() || 0),
                bersama_mode: $('#configFisioBersamaMode').val(),
                bersama_percent: Number($('#configFisioBersamaPercent').val() || 0),
                bersama_nominal: Number($('#configFisioBersamaNominal').val() || 0)
            };
            const recipients = [
                selectedPegawai('#configFisioPetugas1', 'Petugas 1'),
                selectedPegawai('#configFisioPetugas2', 'Petugas 2')
            ].filter(Boolean);
            const activeActions = actionPayload().filter(item => String(item.nama_tindakan || '').trim() && Number(item.is_active));

            $('#configFisioTitle').text('Konfigurasi ' + typeConfig[type]);
            $('#configFisioText').text(type === 'bpjs' ?
                'BPJS memakai jumlah pasien dan nominal per pasien.' :
                'UMUM memakai tindakan, harga, dan jumlah generate.');
            $('#configFisioTypeBadge').text(typeConfig[type]);
            $('#configFisioGrandHelp').text(type === 'bpjs' ?
                'Default BPJS: pasien x Rp 8.000.' :
                'UMUM memakai harga tindakan yang dipilih saat generate.');
            $('#configFisioGrandNominal').prop('readonly', type !== 'bpjs');
            $('#configFisioFormulaPreview').html(
                formulaHtml(config) +
                infoRowsHtml([
                    { label: 'Penerima', value: formatNumber(recipients.length) + ' petugas' },
                    { label: 'Tindakan Aktif', value: formatNumber(activeActions.length) + ' tindakan' }
                ])
            );
        }

        function setActiveType(type) {
            activeType = type;
            $('.fisio-type-tab').removeClass('active');
            $('.fisio-type-tab[data-type="' + type + '"]').addClass('active');
            $('#resultFisioTitle').text('Hasil Generate Fisio ' + typeConfig[type]);
            $('#summaryVolumeLabelFisio').text(type === 'bpjs' ? 'Pasien' : 'Tindakan');
        }

        function loadFormulaConfig() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateFisio.config") }}",
                data: { jenis_fisio: activeType },
                success: function(response) {
                    currentConfig = response.data || {};
                    $('#formulaFisioStrip').html(formulaHtml(currentConfig));
                }
            });
        }

        function loadSummary() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateFisio.summary") }}",
                data: {
                    periode: $('#periodeFisio').val(),
                    jenis_fisio: activeType
                },
                success: function(response) {
                    const data = response.data || {};
                    $('#summaryVolumeFisio').text(formatNumber(activeType === 'bpjs' ?
                        (data.jumlah_pasien || 0) :
                        (data.jumlah_tindakan || 0)));
                    $('#summaryGrandFisio').text(formatRupiah(data.grand_total || 0));
                    $('#summaryPetugas1Fisio').text(formatRupiah(data.total_petugas1 || 0));
                    $('#summaryPetugas2Fisio').text(formatRupiah(data.total_petugas2 || 0));
                    $('#summaryBersamaFisio').text(formatRupiah(data.total_premi_bersama || 0));
                    $('#summaryLockedFisio').text(formatNumber(data.locked_count || 0));
                    $('#summaryFisioSubtitle').text(
                        'Jenis ' + (data.jenis_fisio_label || typeConfig[activeType]) +
                        ' / ' + formatNumber(data.generated_count || 0) + ' data generate / Periode ' +
                        ($('#periodeFisio').val() || '-')
                    );
                },
                error: function() {
                    $('#summaryVolumeFisio, #summaryLockedFisio').text('0');
                    $('#summaryGrandFisio, #summaryPetugas1Fisio, #summaryPetugas2Fisio, #summaryBersamaFisio').text('Rp 0');
                }
            });
        }

        setDefaultPeriod();
        initSelects();

        const table = $('#tableGenerateFisio').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            lengthChange: false,
            pageLength: 10,
            order: [],
            ajax: {
                url: "{{ route("backOffice.keuangan.hitungPremi.generateFisio.table") }}",
                data: function(data) {
                    data.periode = $('#periodeFisio').val();
                    data.jenis_fisio = activeType;
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'periode' },
                {
                    data: 'jenis_fisio_label',
                    render: function(data, type, row) {
                        return '<span class="fisio-badge ' + escapeHtml(row.jenis_fisio) + '">' +
                            escapeHtml(data) + '</span>';
                    }
                },
                {
                    data: 'source_label',
                    render: function(data, type, row) {
                        const note = row.jenis_fisio === 'bpjs' ?
                            'Tarif ' + formatRupiah(row.harga_tindakan || 0) + ' per pasien' :
                            (row.harga_tindakan > 0 ?
                                'Harga ' + formatRupiah(row.harga_tindakan || 0) :
                                'Total ' + formatNumber(row.jumlah_tindakan || 0) + ' tindakan');

                        return '<span class="fw-semibold">' + escapeHtml(data || '-') + '</span>' +
                            '<small class="d-block text-muted">' + escapeHtml(note) + '</small>';
                    }
                },
                {
                    data: null,
                    className: 'text-center',
                    render: function(data, type, row) {
                        return formatNumber(row.jenis_fisio === 'bpjs' ?
                            (row.jumlah_pasien || 0) :
                            (row.jumlah_tindakan || 0));
                    }
                },
                { data: 'grand_total', className: 'text-end', render: formatRupiah },
                { data: 'total_petugas1', className: 'text-end', render: formatRupiah },
                { data: 'total_petugas2', className: 'text-end', render: formatRupiah },
                { data: 'total_premi_bersama', className: 'text-end', render: formatRupiah },
                { data: 'recipient_count', className: 'text-center', render: formatNumber },
                {
                    data: 'is_locked',
                    render: function(data, type, row) {
                        if (data) {
                            return '<span class="fisio-lock locked" title="' +
                                escapeHtml([row.locked_by_name, row.locked_at].filter(Boolean).join(' / ')) +
                                '"><i class="mdi mdi-lock"></i>Terkunci</span>';
                        }
                        return '<span class="fisio-lock open"><i class="mdi mdi-lock-open-variant-outline"></i>Terbuka</span>';
                    }
                },
                { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
            ],
            language: {
                processing: 'Memuat data fisio...',
                emptyTable: 'Belum ada hasil generate fisio.',
                zeroRecords: 'Data tidak ditemukan.',
                info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                infoEmpty: 'Belum ada data',
                paginate: { previous: 'Sebelumnya', next: 'Berikutnya' }
            }
        });

        function refreshAll() {
            loadSummary();
            loadFormulaConfig();
            table.ajax.reload(null, false);
        }

        function openConfigModal() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateFisio.config") }}",
                data: { jenis_fisio: activeType },
                success: function(response) {
                    const data = response.data || {};
                    currentConfig = data;
                    $('#modalConfigFisioLabel').text('Konfigurasi Premi Fisio ' + typeConfig[activeType]);
                    $('#jenisConfigFisio').val(activeType);
                    $('#configFisioGrandNominal').val(data.grand_nominal || (activeType === 'bpjs' ? 8000 : 0));
                    $('#configFisioPetugas1Mode').val(data.petugas1_mode || 'percent');
                    $('#configFisioPetugas1Percent').val(data.petugas1_percent ?? 50);
                    $('#configFisioPetugas1Nominal').val(data.petugas1_nominal || 0);
                    $('#configFisioPetugas2Mode').val(data.petugas2_mode || 'percent');
                    $('#configFisioPetugas2Percent').val(data.petugas2_percent ?? 50);
                    $('#configFisioPetugas2Nominal').val(data.petugas2_nominal || 0);
                    $('#configFisioBersamaMode').val(data.bersama_mode || 'percent');
                    $('#configFisioBersamaPercent').val(data.bersama_percent ?? 50);
                    $('#configFisioBersamaNominal').val(data.bersama_nominal || 0);
                    fillSingleSelect('#configFisioPetugas1', (data.recipients || {}).petugas1);
                    fillSingleSelect('#configFisioPetugas2', (data.recipients || {}).petugas2);
                    renderActionRows(data.tindakan || []);
                    updateConfigOverview();
                    configModal.show();
                },
                error: function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                }
            });
        }

        function resetGeneratePreview() {
            previewReady = false;
            $('#btnSubmitGenerateFisio').prop('disabled', true);
            $('#previewFisioLoading').addClass('d-none');
            $('#previewFisioContent').addClass('d-none');
        }

        function toggleGenerateFields() {
            $('#generateFisioUmumFields').toggleClass('d-none', activeType !== 'umum');
            $('#generateFisioBpjsFields').toggleClass('d-none', activeType !== 'bpjs');
        }

        function openGenerateModal() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateFisio.config") }}",
                data: { jenis_fisio: activeType },
                success: function(response) {
                    currentConfig = response.data || {};
                    $('#generateFisioTitle').text('Generate Fisio ' + typeConfig[activeType]);
                    $('#generateFisioText').text(activeType === 'bpjs' ?
                        'Input jumlah pasien BPJS untuk periode aktif.' :
                        'Pilih satu atau beberapa tindakan UMUM dan masukkan jumlah masing-masing.');
                    $('#generateFisioTypeBadge').text(typeConfig[activeType]);
                    $('#generateFisioFormulaPreview').html(formulaHtml(currentConfig));
                    $('#generateFisioJumlahPasien').val(1);
                    $('#generateFisioItemRows').empty();
                    addGenerateItemRow();
                    toggleGenerateFields();
                    resetGeneratePreview();
                    generateModal.show();
                },
                error: function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                }
            });
        }

        function previewPayload() {
            return {
                periode: $('#periodeFisio').val(),
                jenis_fisio: activeType,
                tindakan_items: generateItemsPayload(),
                jumlah_pasien: $('#generateFisioJumlahPasien').val()
            };
        }

        function renderPreview(data) {
            const source = data.source || {};
            const pools = data.pools || {};
            const recipients = data.recipients || [];
            const sources = data.source_details || [];
            const warnings = data.warnings || {};

            $('#previewFisioStats').html([
                miniCard(data.jenis_fisio === 'bpjs' ? 'Pasien' : 'Tindakan', data.jenis_fisio === 'bpjs' ?
                    formatNumber(source.jumlah_pasien || 0) :
                    formatNumber(source.jumlah_tindakan || 0), false, 'mdi-format-list-numbered', source.source_label || '-'),
                miniCard('Grand Total', formatRupiah(data.grand_total || 0), true, 'mdi-cash-register', data.jenis_fisio === 'bpjs' ? 'Pasien x nominal BPJS' : 'Harga x jumlah'),
                miniCard('Petugas 1', formatRupiah(pools.petugas1 || 0), false, 'mdi-account-star-outline', 'Dari grand total'),
                miniCard('Petugas 2', formatRupiah(pools.petugas2 || 0), false, 'mdi-account-arrow-right-outline', 'Dari Petugas 1'),
                miniCard('Premi Bersama', formatRupiah(pools.premi_bersama || 0), false, 'mdi-account-group-outline', 'Dari Petugas 1'),
                miniCard('Dibagikan', formatRupiah(data.total_dibagikan || 0), true, 'mdi-bank-transfer-out', formatNumber(recipients.length) + ' penerima')
            ].join(''));
            $('#previewFisioSources').html(sourceListHtml(sources));
            $('#previewFisioFormula').html(formulaHtml(data));
            $('#previewFisioRecipients').html(recipientListHtml(recipients));
            $('#previewFisioQuality').html(
                (warnings.blocking || []).length ?
                '<div class="fisio-quality warn"><strong>Belum siap generate.</strong> ' + escapeHtml(warnings.blocking.join(' ')) + '</div>' :
                '<div class="fisio-quality ok"><strong>Siap generate.</strong> Klik Generate dan Simpan untuk menyimpan hasil periode ini.</div>'
            );
            $('#previewFisioLoading').addClass('d-none');
            $('#previewFisioContent').removeClass('d-none');
            previewReady = data.can_generate !== false;
            $('#btnSubmitGenerateFisio').prop('disabled', !previewReady);
        }

        function requestPreview() {
            resetGeneratePreview();
            $('#previewFisioLoading').removeClass('d-none');

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateFisio.preview") }}",
                data: previewPayload(),
                success: function(response) {
                    renderPreview(response.data || {});
                },
                error: function(xhr) {
                    $('#previewFisioLoading').addClass('d-none');
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                }
            });
        }

        function renderDetail(data) {
            const source = data.source || {};
            const pools = data.pools || {};
            const recipients = data.recipients || [];
            const sources = data.source_details || [];
            const type = data.jenis_fisio || activeType;

            $('#detailFisioTitle').text((data.periode || '-') + ' / ' + (data.jenis_fisio_label || '-'));
            $('#detailFisioMeta').text(
                'Generated oleh ' + (data.generate_by_name || '-') + ' pada ' + (data.generated_at || '-') +
                ' / Sumber: ' + (data.source_label || source.source_label || '-')
            );
            $('#detailFisioStatusBadge').text(data.is_locked ? 'Terkunci' : 'Terbuka');
            $('#detailFisioStats').html([
                miniCard(type === 'bpjs' ? 'Pasien' : 'Tindakan', type === 'bpjs' ?
                    formatNumber(source.jumlah_pasien || 0) :
                    formatNumber(source.jumlah_tindakan || 0), false, 'mdi-format-list-numbered', source.source_label || source.nama_tindakan || '-'),
                miniCard('Grand Total', formatRupiah(data.grand_total || source.grand_total || 0), true, 'mdi-cash-register', type === 'bpjs' ? 'Pasien x nominal BPJS' : 'Harga x jumlah'),
                miniCard('Petugas 1', formatRupiah(pools.petugas1 || 0), false, 'mdi-account-star-outline', 'Dari grand total'),
                miniCard('Petugas 2', formatRupiah(pools.petugas2 || 0), false, 'mdi-account-arrow-right-outline', 'Dari Petugas 1'),
                miniCard('Premi Bersama', formatRupiah(pools.premi_bersama || 0), false, 'mdi-account-group-outline', 'Dari Petugas 1'),
                miniCard('Dibagikan', formatRupiah(data.total_dibagikan || 0), true, 'mdi-bank-transfer-out', formatNumber(recipients.length) + ' penerima')
            ].join(''));
            $('#detailFisioSources').html(sourceListHtml(sources));
            $('#detailFisioFormula').html(formulaHtml({
                jenis_fisio: type,
                config: data.config_snapshot || {}
            }));
            $('#detailFisioRecipients').html(recipientListHtml(recipients));
        }

        function openDetailModal(id) {
            const template =
                "{{ route("backOffice.keuangan.hitungPremi.generateFisio.detail", ["id" => "__ID__"]) }}";

            $('#detailFisioLoading').removeClass('d-none');
            $('#detailFisioContent').addClass('d-none');
            detailModal.show();

            $.ajax({
                url: template.replace('__ID__', id),
                success: function(response) {
                    renderDetail(response.data || {});
                    $('#detailFisioLoading').addClass('d-none');
                    $('#detailFisioContent').removeClass('d-none');
                },
                error: function(xhr) {
                    detailModal.hide();
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                }
            });
        }

        function changeLock(id, action) {
            const isLock = action === 'lock';
            const template = isLock ?
                "{{ route("backOffice.keuangan.hitungPremi.generateFisio.lock", ["id" => "__ID__"]) }}" :
                "{{ route("backOffice.keuangan.hitungPremi.generateFisio.unlock", ["id" => "__ID__"]) }}";

            Swal.fire({
                icon: isLock ? 'warning' : 'question',
                title: isLock ? 'Kunci data fisio?' : 'Buka kunci data fisio?',
                text: isLock ? 'Data yang dikunci tidak bisa digenerate ulang.' :
                    'Data dapat digenerate ulang setelah kunci dibuka.',
                showCancelButton: true,
                confirmButtonText: isLock ? 'Kunci Data' : 'Buka Kunci',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: function() {
                    return $.ajax({
                        url: template.replace('__ID__', id),
                        method: 'POST'
                    }).catch(function(xhr) {
                        Swal.showValidationMessage(errorMessage(xhr));
                    });
                }
            }).then(function(result) {
                if (result.isConfirmed && result.value) {
                    Swal.fire('Berhasil', result.value.message, 'success');
                    refreshAll();
                }
            });
        }

        function deleteResult(id) {
            const template =
                "{{ route("backOffice.keuangan.hitungPremi.generateFisio.delete", ["id" => "__ID__"]) }}";

            Swal.fire({
                icon: 'warning',
                title: 'Hapus data fisio?',
                text: 'Data yang dihapus tidak bisa dikembalikan.',
                showCancelButton: true,
                confirmButtonText: 'Hapus Data',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545',
                showLoaderOnConfirm: true,
                preConfirm: function() {
                    return $.ajax({
                        url: template.replace('__ID__', id),
                        method: 'DELETE'
                    }).catch(function(xhr) {
                        Swal.showValidationMessage(errorMessage(xhr));
                    });
                }
            }).then(function(result) {
                if (result.isConfirmed && result.value) {
                    Swal.fire('Berhasil', result.value.message, 'success');
                    refreshAll();
                }
            });
        }

        $('#periodeFisio').on('change', refreshAll);
        $('#btnPrevPeriodFisio').on('click', () => movePeriod(-1));
        $('#btnNextPeriodFisio').on('click', () => movePeriod(1));
        $('#btnCurrentPeriodFisio').on('click', function() {
            const now = new Date();
            setPeriodFromDate(new Date(now.getFullYear(), now.getMonth(), 1));
        });
        $('#btnRefreshFisio').on('click', refreshAll);
        $('#btnOpenGenerateFisio').on('click', openGenerateModal);
        $('#btnOpenConfigFisio').on('click', openConfigModal);
        $('#btnPreviewGenerateFisio').on('click', requestPreview);
        $('#searchGenerateFisio').on('input', function() {
            table.search(this.value).draw();
        });
        $('.fisio-type-tab').on('click', function() {
            setActiveType($(this).data('type'));
            refreshAll();
        });
        $('#generateFisioItemRows').on('input change', '.generate-fisio-tindakan, .generate-fisio-jumlah', resetGeneratePreview);
        $('#generateFisioItemRows').on('click', '.btn-remove-generate-fisio-item', function() {
            $(this).closest('tr').remove();
            $('#generateFisioItemEmpty').toggleClass('d-none', $('.generate-fisio-item-row').length > 0);
            resetGeneratePreview();
        });
        $('#btnAddGenerateFisioItem').on('click', addGenerateItemRow);
        $('#generateFisioJumlahPasien').on('input change', resetGeneratePreview);
        $('#formConfigFisio select, #formConfigFisio input').on('input change', updateConfigOverview);
        $('#btnAddFisioAction').on('click', function() {
            $('#configFisioActionRows').append(actionRow({ is_active: true, harga: 0 }));
            $('#configFisioActionEmpty').addClass('d-none');
            updateConfigOverview();
        });
        $('#configFisioActionRows').on('click', '.btn-remove-fisio-action', function() {
            $(this).closest('tr').remove();
            $('#configFisioActionEmpty').toggleClass('d-none', $('.fisio-action-row').length > 0);
            updateConfigOverview();
        });

        $('#formConfigFisio').on('submit', function(event) {
            event.preventDefault();
            const button = $('#btnSubmitConfigFisio');
            const originalHtml = button.html();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateFisio.updateConfig") }}",
                method: 'PUT',
                data: configPayload(),
                beforeSend: function() {
                    button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');
                },
                success: function(response) {
                    configModal.hide();
                    Swal.fire('Berhasil', response.message, 'success');
                    currentConfig = response.data || null;
                    refreshAll();
                },
                error: function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                },
                complete: function() {
                    button.prop('disabled', false).html(originalHtml);
                }
            });
        });

        $('#formGenerateFisio').on('submit', function(event) {
            event.preventDefault();

            if (!previewReady) {
                requestPreview();
                return;
            }

            const button = $('#btnSubmitGenerateFisio');
            const originalHtml = button.html();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateFisio.store") }}",
                method: 'POST',
                data: previewPayload(),
                beforeSend: function() {
                    button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Generate...');
                },
                success: function(response) {
                    generateModal.hide();
                    Swal.fire('Berhasil', response.message, 'success');
                    refreshAll();
                },
                error: function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                },
                complete: function() {
                    button.prop('disabled', false).html(originalHtml);
                }
            });
        });

        $('#tableGenerateFisio').on('click', '.btn-detail-fisio', function() {
            openDetailModal($(this).data('id'));
        });

        $('#tableGenerateFisio').on('click', '.btn-lock-fisio', function() {
            changeLock($(this).data('id'), 'lock');
        });

        $('#tableGenerateFisio').on('click', '.btn-unlock-fisio', function() {
            changeLock($(this).data('id'), 'unlock');
        });

        $('#tableGenerateFisio').on('click', '.btn-delete-fisio', function() {
            deleteResult($(this).data('id'));
        });

        setActiveType(activeType);
        refreshAll();
    });
</script>
