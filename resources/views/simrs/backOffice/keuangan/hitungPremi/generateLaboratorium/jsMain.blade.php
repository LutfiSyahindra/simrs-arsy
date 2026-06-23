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

        const generateModal = modalInstance('modalGenerateLaboratorium');
        const configModal = modalInstance('modalConfigLaboratorium');
        const detailModal = modalInstance('modalDetailLaboratorium');

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
            const now = new Date();
            $('#periodeLaboratorium').val(now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
        }

        function periodFromInput() {
            const parts = String($('#periodeLaboratorium').val() || '').split('-').map(Number);
            return parts.length === 2 && parts[0] && parts[1] ?
                new Date(parts[0], parts[1] - 1, 1) :
                new Date();
        }

        function setPeriodFromDate(date) {
            $('#periodeLaboratorium')
                .val(date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0'))
                .trigger('change');
        }

        function movePeriod(offset) {
            const date = periodFromInput();
            date.setMonth(date.getMonth() + offset);
            setPeriodFromDate(date);
        }

        function sourcePeriodFor(periode, type) {
            if (type !== 'bpjs' || !periode) {
                return periode || '-';
            }

            const parts = String(periode).split('-').map(Number);
            if (parts.length !== 2 || !parts[0] || !parts[1]) {
                return periode;
            }

            const date = new Date(parts[0], parts[1] - 2, 1);
            return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
        }

        function sourceRuleText(type, periode) {
            const sourcePeriode = sourcePeriodFor(periode, type);

            return type === 'bpjs' ?
                'Periode generate ' + periode + ' memakai data Khanza periode ' + sourcePeriode +
                ' dengan filter reg_periksa.kd_pj = BPJ.' :
                'Periode generate ' + periode + ' memakai data Khanza periode yang sama dengan filter kd_pj selain BPJ dan -.';
        }

        function percentText(value) {
            return formatNumber(value || 0) + '%';
        }

        function bpjsPetugasDivider(config) {
            const fieldValue = $('#configBpjsPetugasDivider').val();
            const value = config && config.bpjs_petugas_divider !== undefined && config.bpjs_petugas_divider !== null ?
                config.bpjs_petugas_divider :
                (fieldValue !== '' ? fieldValue : 7);

            return Math.max(1, Number(value || 7));
        }

        function syncBpjsDividerFields() {
            const isBpjsDivider = activeType === 'bpjs' && $('#configPetugasMode').val() === 'bersama_divider';

            $('#configBpjsDividerGroup').toggleClass('d-none', !isBpjsDivider);
            $('#configPetugasPercent').prop('readonly', false);

            return Number($('#configPetugasPercent').val() || 0);
        }

        function modeLabel(mode, percent, nominal, sourceLabel) {
            if (mode === 'nominal') {
                return 'Nominal tetap ' + formatRupiah(nominal);
            }

            if (mode === 'source') {
                return 'Mengambil penuh dari ' + sourceLabel;
            }

            if (mode === 'bersama_divider') {
                return sourceLabel + ' / pembagi BPJS';
            }

            return percentText(percent) + ' dari ' + sourceLabel;
        }

        function petugasModeLabel(type, mode, percent, nominal, sourceLabel, config) {
            if (type === 'bpjs' && mode === 'bersama_divider') {
                return sourceLabel + ' / ' + formatNumber(bpjsPetugasDivider(config));
            }

            return modeLabel(mode, percent, nominal, sourceLabel);
        }

        function livePetugasFormula(type, mode, percent, nominal, config) {
            if (mode === 'nominal') {
                return formatRupiah(nominal) + ' nominal tetap';
            }

            if (type === 'bpjs' && mode === 'bersama_divider') {
                return 'Premi bersama / ' + formatNumber(bpjsPetugasDivider(config));
            }

            return percentText(percent) + ' x basis petugas';
        }

        function liveBersamaFormula(mode, percent, nominal) {
            if (mode === 'source') {
                return activeType === 'bpjs' ? '100% dari bagian_rs' : '100% dari manajemen';
            }

            if (mode === 'nominal') {
                return formatRupiah(nominal) + ' nominal tetap';
            }

            return percentText(percent) + ' x ' + (activeType === 'bpjs' ? 'bagian_rs' : 'manajemen');
        }

        function infoRowsHtml(rows) {
            return rows.map(function(row) {
                return '<div class="laboratorium-info-row">' +
                    '<div>' +
                    '<div class="laboratorium-info-label">' + escapeHtml(row.label) + '</div>' +
                    (row.note ? '<div class="laboratorium-info-note">' + escapeHtml(row.note) + '</div>' : '') +
                    '</div>' +
                    '<div class="laboratorium-info-value">' + escapeHtml(row.value) + '</div>' +
                    '</div>';
            }).join('');
        }

        function formulaHtml(data) {
            const config = data.config || data || {};
            const jenis = data.jenis_laboratorium || config.jenis_laboratorium || activeType;
            const petugasBase = jenis === 'bpjs' ? 'premi bersama' : 'total bagian_laborat';
            const bersamaBase = jenis === 'bpjs' ? 'total bagian_rs' : 'total manajemen';
            const sourcePeriode = data.source_periode || config.source_periode || sourcePeriodFor($('#periodeLaboratorium').val(), jenis);

            return infoRowsHtml([
                {
                    label: 'Data Khanza',
                    value: sourcePeriode,
                    note: jenis === 'bpjs' ? 'BPJS mengambil data bulan sebelumnya.' : 'UMUM mengambil data periode aktif.'
                },
                {
                    label: 'Premi Petugas',
                    value: petugasModeLabel(jenis, config.petugas_mode, config.petugas_percent, config.petugas_nominal, petugasBase, config),
                    note: 'Basis: ' + petugasBase + '.'
                },
                {
                    label: 'Premi Bersama',
                    value: modeLabel(config.bersama_mode, config.bersama_percent, config.bersama_nominal, bersamaBase),
                    note: 'Basis: ' + bersamaBase + '.'
                }
            ]);
        }

        function miniCard(label, value, primary, icon, note) {
            return '<div class="laboratorium-mini-card ' + (primary ? 'total' : '') + '">' +
                '<div class="laboratorium-mini-top">' +
                '<div class="laboratorium-mini-label">' + escapeHtml(label) + '</div>' +
                '<div class="laboratorium-mini-icon"><i class="mdi ' + escapeHtml(icon || 'mdi-chart-box-outline') + '"></i></div>' +
                '</div>' +
                '<div class="laboratorium-mini-value">' + escapeHtml(value) + '</div>' +
                (note ? '<div class="laboratorium-mini-note">' + escapeHtml(note) + '</div>' : '') +
                '</div>';
        }

        function liveFormulaRow(label, value) {
            return infoRowsHtml([{ label: label, value: value }]);
        }

        function petugasBaseLabel(type) {
            return type === 'bpjs' ? 'Premi bersama' : 'Bagian laborat';
        }

        function petugasBaseValue(type, source, fallback) {
            if (fallback !== undefined && fallback !== null) {
                return fallback;
            }

            return type === 'bpjs' ?
                (source.total_premi_bersama || 0) :
                (source.total_bagian_laborat || 0);
        }

        function recipientListHtml(items, options) {
            const showTotal = !options || options.showTotal !== false;

            if (!items || !items.length) {
                return '<div class="alert alert-warning mb-0">Belum ada penerima premi petugas pada konfigurasi ini.</div>';
            }

            return items.map(function(item) {
                const name = item.pegawai_name || item.text || '-';
                return '<div class="laboratorium-recipient-item">' +
                    '<div>' +
                    '<div class="laboratorium-recipient-name">' + escapeHtml(name) + '</div>' +
                    '<div class="laboratorium-recipient-meta">' +
                    escapeHtml(item.pegawai_id || '-') + ' / ' + escapeHtml(item.pegawai_position || '-') +
                    (item.allocation_percent ? ' / ' + percentText(item.allocation_percent) : '') +
                    '</div>' +
                    '</div>' +
                    '<div class="laboratorium-recipient-total">' +
                    (showTotal ? formatRupiah(item.total_received || 0) : '<span class="laboratorium-recipient-status">Dipilih</span>') +
                    '</div>' +
                    '</div>';
            }).join('');
        }

        function initRecipientSelect() {
            if (!$.fn.select2) {
                return;
            }

            $('#configLaboratoriumRecipients').select2({
                dropdownParent: $('#modalConfigLaboratorium'),
                width: '100%',
                placeholder: 'Pilih pegawai penerima',
                ajax: {
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateLaboratorium.pegawaiOptions") }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ q: params.term || '' }),
                    processResults: response => ({
                        results: (response.data || []).map(item => ({ id: item.id, text: item.text }))
                    })
                }
            });
        }

        function fillRecipients(items) {
            const select = $('#configLaboratoriumRecipients');
            select.empty();
            (items || []).forEach(function(item) {
                select.append(new Option(item.text || (item.pegawai_id + ' - ' + item.pegawai_name), item.pegawai_id, true, true));
            });
            select.trigger('change');
            updateRecipientCounter();
        }

        function updateRecipientCounter() {
            const count = ($('#configLaboratoriumRecipients').val() || []).length;
            $('#countLaboratoriumRecipients').text(formatNumber(count) + ' penerima dipilih');
        }

        function configRecipientItems() {
            return $('#configLaboratoriumRecipients option:selected').map(function() {
                const text = $(this).text();
                const parts = text.split(' - ');

                return {
                    pegawai_id: this.value,
                    pegawai_name: parts.length > 1 ? parts.slice(1).join(' - ') : text,
                    pegawai_position: 'Penerima aktif',
                    total_received: 0
                };
            }).get();
        }

        function updateConfigOverview() {
            const recipients = ($('#configLaboratoriumRecipients').val() || []).length;
            const petugasMode = $('#configPetugasMode').val();
            const bersamaMode = $('#configBersamaMode').val();
            const petugasPercent = syncBpjsDividerFields();
            const bersamaPercent = Number($('#configBersamaPercent').val() || 0);
            const petugasNominal = Number($('#configPetugasNominal').val() || 0);
            const bersamaNominal = Number($('#configBersamaNominal').val() || 0);
            const periode = $('#periodeLaboratorium').val() || '-';
            const sourcePeriode = sourcePeriodFor(periode, activeType);

            $('#configLaboratoriumRuleTitle').text('Konfigurasi ' + (typeConfig[activeType] || '-'));
            $('#configLaboratoriumRuleText').text(sourceRuleText(activeType, periode));
            $('#configLaboratoriumTypeBadge').text(typeConfig[activeType] || '-');
            $('#configPetugasModeHelp').text(
                activeType === 'bpjs' ?
                'BPJS: premi petugas diambil dari hasil premi bersama dibagi pembagi.' :
                'UMUM: basis premi petugas adalah total bagian_laborat dari periode aktif.'
            );
            $('#configLaboratoriumFormulaPreview').html([
                liveFormulaRow('Periode Data Khanza', sourcePeriode),
                liveFormulaRow('Basis Petugas', activeType === 'bpjs' ? 'Premi bersama' : 'Total bagian_laborat'),
                liveFormulaRow('Basis Bersama', activeType === 'bpjs' ? 'Total bagian_rs' : 'Total manajemen'),
                liveFormulaRow('Premi Petugas', livePetugasFormula(activeType, petugasMode, petugasPercent, petugasNominal)),
                liveFormulaRow('Premi Bersama', liveBersamaFormula(bersamaMode, bersamaPercent, bersamaNominal)),
                liveFormulaRow('Penerima Petugas', formatNumber(recipients) + ' pegawai')
            ].join(''));
            $('#configLaboratoriumRecipientPreview').html(
                configRecipientItems().length ?
                recipientListHtml(configRecipientItems(), { showTotal: false }) :
                '<div class="laboratorium-empty-state">Belum ada pegawai dipilih. Cari nama pegawai lalu pilih sebagai penerima premi petugas.</div>'
            );
        }

        function setActiveType(type) {
            activeType = type;
            $('.laboratorium-type-tab').removeClass('active');
            $('.laboratorium-type-tab[data-type="' + type + '"]').addClass('active');
            $('#resultLaboratoriumTitle').text('Hasil Generate Laboratorium ' + typeConfig[type]);
        }

        function loadFormulaConfig() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateLaboratorium.config") }}",
                data: { jenis_laboratorium: activeType },
                success: function(response) {
                    $('#formulaLaboratoriumStrip').html(formulaHtml(response.data || {}));
                }
            });
        }

        function loadSummary() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateLaboratorium.summary") }}",
                data: {
                    periode: $('#periodeLaboratorium').val(),
                    jenis_laboratorium: activeType
                },
                success: function(response) {
                    const data = response.data || {};
                    $('#summaryTindakanLaboratorium').text(formatNumber(data.jumlah_tindakan || 0));
                    $('#summaryPasienLaboratorium').text(formatNumber(data.jumlah_pasien || 0));
                    $('#summaryPetugasLaboratorium').text(formatRupiah(data.total_premi_petugas || 0));
                    $('#summaryBersamaLaboratorium').text(formatRupiah(data.total_premi_bersama || 0));
                    $('#summaryLockedLaboratorium').text(formatNumber(data.locked_count || 0));
                    $('#summaryLaboratoriumSubtitle').text(
                        'Jenis ' + (data.jenis_laboratorium_label || typeConfig[activeType]) +
                        ' / ' + formatNumber(data.generated_count || 0) + ' data generate / ' +
                        sourceRuleText(activeType, $('#periodeLaboratorium').val())
                    );
                },
                error: function() {
                    $('#summaryTindakanLaboratorium, #summaryPasienLaboratorium, #summaryLockedLaboratorium').text('0');
                    $('#summaryPetugasLaboratorium, #summaryBersamaLaboratorium').text('Rp 0');
                }
            });
        }

        setDefaultPeriod();
        initRecipientSelect();

        const table = $('#tableGenerateLaboratorium').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            lengthChange: false,
            pageLength: 10,
            order: [],
            ajax: {
                url: "{{ route("backOffice.keuangan.hitungPremi.generateLaboratorium.table") }}",
                data: function(data) {
                    data.periode = $('#periodeLaboratorium').val();
                    data.jenis_laboratorium = activeType;
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'periode' },
                {
                    data: 'source_periode',
                    render: function(data, type, row) {
                        const source = data || sourcePeriodFor(row.periode, row.jenis_laboratorium);
                        const note = row.jenis_laboratorium === 'bpjs' ? 'Data bulan kemarin' : 'Data periode aktif';
                        return '<span class="fw-semibold">' + escapeHtml(source) + '</span>' +
                            '<small class="d-block text-muted">' + escapeHtml(note) + '</small>';
                    }
                },
                {
                    data: 'jenis_laboratorium_label',
                    render: function(data, type, row) {
                        return '<span class="laboratorium-badge ' + escapeHtml(row.jenis_laboratorium) + '">' +
                            escapeHtml(data) + '</span>';
                    }
                },
                { data: 'jumlah_tindakan', className: 'text-center', render: formatNumber },
                {
                    data: null,
                    className: 'text-end',
                    render: function(data, type, row) {
                        const basis = row.jenis_laboratorium === 'bpjs' ?
                            row.total_premi_bersama :
                            row.total_bagian_laborat;
                        return formatRupiah(basis);
                    }
                },
                {
                    data: null,
                    className: 'text-end',
                    render: function(data, type, row) {
                        return formatRupiah(row.jenis_laboratorium === 'bpjs' ?
                            row.total_bagian_rs :
                            row.total_manajemen);
                    }
                },
                { data: 'total_premi_petugas', className: 'text-end', render: formatRupiah },
                { data: 'total_premi_bersama', className: 'text-end', render: formatRupiah },
                { data: 'details_count', className: 'text-center', render: formatNumber },
                {
                    data: 'is_locked',
                    render: function(data, type, row) {
                        if (data) {
                            return '<span class="laboratorium-lock locked" title="' +
                                escapeHtml([row.locked_by_name, row.locked_at].filter(Boolean).join(' / ')) +
                                '"><i class="mdi mdi-lock"></i>Terkunci</span>';
                        }
                        return '<span class="laboratorium-lock open"><i class="mdi mdi-lock-open-variant-outline"></i>Terbuka</span>';
                    }
                },
                { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
            ],
            language: {
                processing: 'Memuat data laboratorium...',
                emptyTable: 'Belum ada hasil generate laboratorium.',
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

        function renderPreview(data) {
            const source = data.source || {};
            const pools = data.pools || {};
            const recipients = data.recipients || [];
            const periode = $('#periodeLaboratorium').val() || '-';
            const type = data.jenis_laboratorium || activeType;
            const sourcePeriode = data.source_periode || source.source_periode || sourcePeriodFor(periode, type);
            const petugasBase = petugasBaseLabel(type);

            $('#generateLaboratoriumTitle').text(
                'Preview ' + (data.jenis_laboratorium_label || typeConfig[type] || '-') + ' / Generate ' + periode
            );
            $('#generateLaboratoriumText').text(
                'Pastikan nominal dan penerima sudah benar sebelum disimpan.'
            );
            $('#generateLaboratoriumTypeBadge').text(data.jenis_laboratorium_label || typeConfig[type] || '-');
            $('#generateLaboratoriumSourceBadge').html(
                '<i class="mdi mdi-calendar-search"></i> Data ' + escapeHtml(sourcePeriode)
            );
            $('#previewLaboratoriumSourceNote').html(
                '<i class="mdi mdi-database-search-outline"></i>' +
                '<div>' +
                '<strong>Data Khanza: ' + escapeHtml(sourcePeriode) + '</strong>' +
                '<span>' + escapeHtml(type === 'bpjs' ? 'Filter kd_pj BPJ. Hasil disimpan untuk periode ' + periode + '.' : 'Filter kd_pj selain BPJ dan -. Hasil disimpan untuk periode ' + periode + '.') + '</span>' +
                '</div>'
            );
            $('#previewLaboratoriumStats').html([
                miniCard('Tindakan', formatNumber(source.jumlah_tindakan || 0), false, 'mdi-format-list-numbered', formatNumber(source.jumlah_pasien || 0) + ' pasien'),
                miniCard(petugasBase, formatRupiah(petugasBaseValue(type, source, data.petugas_base)), false, type === 'bpjs' ? 'mdi-cash-multiple' : 'mdi-cash-register', 'Basis premi petugas'),
                miniCard(type === 'bpjs' ? 'Bagian RS' : 'Manajemen', formatRupiah(type === 'bpjs' ? (source.total_bagian_rs || 0) : (source.total_manajemen || 0)), false, 'mdi-hospital-building', 'Basis premi bersama'),
                miniCard('Premi Petugas', formatRupiah(pools.petugas || 0), true, 'mdi-account-hard-hat-outline', formatNumber(recipients.length) + ' penerima'),
                miniCard('Premi Bersama', formatRupiah(pools.bersama || 0), false, 'mdi-account-group-outline', 'Untuk premi bersama'),
                miniCard('Dibagikan', formatRupiah(data.total_dibagikan || 0), true, 'mdi-bank-transfer-out', 'Total ke pegawai')
            ].join(''));
            $('#previewLaboratoriumFormula').html(formulaHtml(data));
            $('#previewLaboratoriumRecipientSubtitle').text(
                formatNumber(recipients.length) + ' penerima / masing-masing ' + formatRupiah(pools.petugas || 0)
            );
            $('#previewLaboratoriumRecipients').html(recipientListHtml(recipients));
            $('#previewLaboratoriumQuality').html(
                recipients.length ?
                '<div class="laboratorium-quality ok"><strong>Siap generate.</strong> Klik Generate dan Simpan untuk menyimpan hasil periode ini.</div>' :
                '<div class="laboratorium-quality warn"><strong>Penerima belum dipilih.</strong> Buka konfigurasi dan pilih pegawai penerima premi petugas.</div>'
            );
            $('#previewLaboratoriumLoading').addClass('d-none');
            $('#previewLaboratoriumContent').removeClass('d-none');
            previewReady = true;
            $('#btnSubmitGenerateLaboratorium').prop('disabled', false);
        }

        function openGenerateModal() {
            previewReady = false;
            $('#btnSubmitGenerateLaboratorium').prop('disabled', true);
            $('#previewLaboratoriumLoading').removeClass('d-none');
            $('#previewLaboratoriumContent').addClass('d-none');
            generateModal.show();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateLaboratorium.preview") }}",
                data: {
                    periode: $('#periodeLaboratorium').val(),
                    jenis_laboratorium: activeType
                },
                success: function(response) {
                    renderPreview(response.data || {});
                },
                error: function(xhr) {
                    generateModal.hide();
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                }
            });
        }

        function openConfigModal() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateLaboratorium.config") }}",
                data: { jenis_laboratorium: activeType },
                success: function(response) {
                    const data = response.data || {};
                    $('#modalConfigLaboratoriumLabel').text('Konfigurasi Premi Laboratorium ' + typeConfig[activeType]);
                    $('#jenisConfigLaboratorium').val(activeType);
                    $('#configPetugasMode').val(data.petugas_mode || 'percent');
                    $('#configPetugasPercent').val(data.petugas_percent || 0);
                    $('#configPetugasNominal').val(data.petugas_nominal || 0);
                    $('#configBpjsPetugasDivider').val(data.bpjs_petugas_divider || 7);
                    $('#configBersamaMode').val(data.bersama_mode || 'source');
                    $('#configBersamaPercent').val(data.bersama_percent || 100);
                    $('#configBersamaNominal').val(data.bersama_nominal || 0);
                    fillRecipients(data.recipients || []);
                    updateConfigOverview();
                    configModal.show();
                },
                error: function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                }
            });
        }

        function configPayload() {
            syncBpjsDividerFields();

            return {
                jenis_laboratorium: $('#jenisConfigLaboratorium').val(),
                petugas_mode: $('#configPetugasMode').val(),
                petugas_percent: $('#configPetugasPercent').val(),
                petugas_nominal: $('#configPetugasNominal').val(),
                bpjs_petugas_divider: $('#configBpjsPetugasDivider').val(),
                bersama_mode: $('#configBersamaMode').val(),
                bersama_percent: $('#configBersamaPercent').val(),
                bersama_nominal: $('#configBersamaNominal').val(),
                recipients: $('#configLaboratoriumRecipients').val() || []
            };
        }

        function renderDetail(data) {
            const source = data.source || {};
            const pools = data.pools || {};
            const recipients = data.details || [];
            const config = data.config_snapshot || {};
            const type = data.jenis_laboratorium || activeType;
            const sourcePeriode = data.source_periode || config.source_periode || sourcePeriodFor(data.periode, type);

            $('#detailLaboratoriumTitle').text((data.periode || '-') + ' / ' + (data.jenis_laboratorium_label || '-'));
            $('#detailLaboratoriumMeta').text(
                'Generated oleh ' + (data.generate_by_name || '-') + ' pada ' + (data.generated_at || '-') +
                ' / Status: ' + (data.is_locked ? 'Terkunci' : 'Terbuka')
            );
            $('#detailLaboratoriumSourceMeta').text(
                'Periode data Khanza: ' + sourcePeriode + ' / ' +
                (type === 'bpjs' ? 'BPJS memakai data bulan kemarin' : 'UMUM memakai periode aktif')
            );
            $('#detailLaboratoriumStatusBadge').html(
                data.is_locked ? 'Terkunci' : 'Terbuka'
            );
            $('#detailLaboratoriumStats').html([
                miniCard('Tindakan', formatNumber(source.jumlah_tindakan || 0), false, 'mdi-format-list-numbered', formatNumber(source.jumlah_pasien || 0) + ' pasien'),
                miniCard(petugasBaseLabel(type), formatRupiah(petugasBaseValue(type, source, type === 'bpjs' ? (pools.bersama || 0) : undefined)), false, type === 'bpjs' ? 'mdi-cash-multiple' : 'mdi-cash-register', 'Basis premi petugas'),
                miniCard(type === 'bpjs' ? 'Bagian RS' : 'Manajemen', formatRupiah(type === 'bpjs' ? (source.total_bagian_rs || 0) : (source.total_manajemen || 0)), false, 'mdi-hospital-building', 'Basis premi bersama'),
                miniCard('Premi Petugas', formatRupiah(pools.petugas || 0), true, 'mdi-account-hard-hat-outline', formatNumber(recipients.length) + ' penerima'),
                miniCard('Premi Bersama', formatRupiah(pools.bersama || 0), false, 'mdi-account-group-outline', 'Untuk premi bersama'),
                miniCard('Dibagikan', formatRupiah(data.total_dibagikan || 0), true, 'mdi-bank-transfer-out', 'Total ke pegawai')
            ].join(''));
            $('#detailLaboratoriumFormula').html(formulaHtml({
                jenis_laboratorium: type,
                source_periode: sourcePeriode,
                config: config
            }));
            $('#detailLaboratoriumRecipientSubtitle').text(
                formatNumber(recipients.length) + ' penerima / masing-masing ' + formatRupiah(pools.petugas || 0)
            );
            $('#detailLaboratoriumRecipients').html(recipientListHtml(recipients));
        }

        function openDetailModal(id) {
            const template =
                "{{ route("backOffice.keuangan.hitungPremi.generateLaboratorium.detail", ["id" => "__ID__"]) }}";

            $('#detailLaboratoriumLoading').removeClass('d-none');
            $('#detailLaboratoriumContent').addClass('d-none');
            detailModal.show();

            $.ajax({
                url: template.replace('__ID__', id),
                success: function(response) {
                    renderDetail(response.data || {});
                    $('#detailLaboratoriumLoading').addClass('d-none');
                    $('#detailLaboratoriumContent').removeClass('d-none');
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
                "{{ route("backOffice.keuangan.hitungPremi.generateLaboratorium.lock", ["id" => "__ID__"]) }}" :
                "{{ route("backOffice.keuangan.hitungPremi.generateLaboratorium.unlock", ["id" => "__ID__"]) }}";

            Swal.fire({
                icon: isLock ? 'warning' : 'question',
                title: isLock ? 'Kunci data laboratorium?' : 'Buka kunci data laboratorium?',
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
                "{{ route("backOffice.keuangan.hitungPremi.generateLaboratorium.delete", ["id" => "__ID__"]) }}";

            Swal.fire({
                icon: 'warning',
                title: 'Hapus data laboratorium?',
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

        $('#periodeLaboratorium').on('change', refreshAll);
        $('#btnPrevPeriodLaboratorium').on('click', () => movePeriod(-1));
        $('#btnNextPeriodLaboratorium').on('click', () => movePeriod(1));
        $('#btnCurrentPeriodLaboratorium').on('click', function() {
            const now = new Date();
            setPeriodFromDate(new Date(now.getFullYear(), now.getMonth(), 1));
        });
        $('#btnRefreshLaboratorium').on('click', refreshAll);
        $('#btnOpenGenerateLaboratorium').on('click', openGenerateModal);
        $('#btnOpenConfigLaboratorium').on('click', openConfigModal);
        $('#searchGenerateLaboratorium').on('input', function() {
            table.search(this.value).draw();
        });
        $('.laboratorium-type-tab').on('click', function() {
            setActiveType($(this).data('type'));
            refreshAll();
        });
        $('#configLaboratoriumRecipients').on('change', function() {
            updateRecipientCounter();
            updateConfigOverview();
        });
        $('#formConfigLaboratorium select, #formConfigLaboratorium input').on('input change', updateConfigOverview);

        $('#formConfigLaboratorium').on('submit', function(event) {
            event.preventDefault();
            const button = $('#btnSubmitConfigLaboratorium');
            const originalHtml = button.html();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateLaboratorium.updateConfig") }}",
                method: 'PUT',
                data: configPayload(),
                beforeSend: function() {
                    button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');
                },
                success: function(response) {
                    configModal.hide();
                    Swal.fire('Berhasil', response.message, 'success');
                    loadFormulaConfig();
                },
                error: function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                },
                complete: function() {
                    button.prop('disabled', false).html(originalHtml);
                }
            });
        });

        $('#formGenerateLaboratorium').on('submit', function(event) {
            event.preventDefault();

            if (!previewReady) {
                openGenerateModal();
                return;
            }

            const button = $('#btnSubmitGenerateLaboratorium');
            const originalHtml = button.html();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateLaboratorium.store") }}",
                method: 'POST',
                data: {
                    periode: $('#periodeLaboratorium').val(),
                    jenis_laboratorium: activeType
                },
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

        $('#tableGenerateLaboratorium').on('click', '.btn-detail-laboratorium', function() {
            openDetailModal($(this).data('id'));
        });

        $('#tableGenerateLaboratorium').on('click', '.btn-lock-laboratorium', function() {
            changeLock($(this).data('id'), 'lock');
        });

        $('#tableGenerateLaboratorium').on('click', '.btn-unlock-laboratorium', function() {
            changeLock($(this).data('id'), 'unlock');
        });

        $('#tableGenerateLaboratorium').on('click', '.btn-delete-laboratorium', function() {
            deleteResult($(this).data('id'));
        });

        refreshAll();
    });
</script>
