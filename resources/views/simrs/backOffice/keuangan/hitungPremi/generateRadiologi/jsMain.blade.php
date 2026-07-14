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

        const generateModal = modalInstance('modalGenerateRadiologi');
        const configModal = modalInstance('modalConfigRadiologi');
        const detailModal = modalInstance('modalDetailRadiologi');

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
                $('#periodeRadiologi').val(queryPeriod);
                return;
            }

            const now = new Date();
            $('#periodeRadiologi').val(now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
        }

        function periodFromInput() {
            const parts = String($('#periodeRadiologi').val() || '').split('-').map(Number);
            return parts.length === 2 && parts[0] && parts[1] ?
                new Date(parts[0], parts[1] - 1, 1) :
                new Date();
        }

        function setPeriodFromDate(date) {
            $('#periodeRadiologi')
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

        function configValue(config, key, fallback) {
            return config && config[key] !== undefined && config[key] !== null && config[key] !== '' ?
                config[key] :
                fallback;
        }

        function bpjsFormulaRate(config) {
            const fieldValue = $('#configBpjsPetugasFormulaRate').val();
            return Number(configValue(config, 'bpjs_petugas_formula_rate', fieldValue !== '' ? fieldValue : 0.04));
        }

        function bpjsFormulaDivider(config) {
            const fieldValue = $('#configBpjsPetugasFormulaDivider').val();
            return Math.max(1, Number(configValue(config, 'bpjs_petugas_formula_divider', fieldValue !== '' ? fieldValue : 4)));
        }

        function bpjsEffectivePercent(rate, divider) {
            return (Number(rate || 0) / Math.max(1, Number(divider || 1))) * 100;
        }

        function bpjsFormulaText(config) {
            return formatNumber(bpjsFormulaRate(config)) + ' / ' + formatNumber(bpjsFormulaDivider(config));
        }

        function syncBpjsFormulaFields() {
            if (activeType !== 'bpjs' || $('#configPetugasMode').val() !== 'percent') {
                $('#configBpjsFormulaGroup').addClass('d-none');
                $('#configPetugasPercent').prop('readonly', false);
                return Number($('#configPetugasPercent').val() || 0);
            }

            const rate = bpjsFormulaRate();
            const divider = bpjsFormulaDivider();
            const effectivePercent = bpjsEffectivePercent(rate, divider);

            $('#configBpjsFormulaGroup').removeClass('d-none');
            $('#configPetugasPercent')
                .prop('readonly', true)
                .val(effectivePercent.toFixed(4));
            $('#configBpjsFormulaHelp').text(
                'Persen efektif: ' + percentText(effectivePercent) + ' dari total biaya radiologi.'
            );

            return effectivePercent;
        }

        function modeLabel(mode, percent, nominal, sourceLabel) {
            if (mode === 'nominal') {
                return 'Nominal tetap ' + formatRupiah(nominal);
            }

            if (mode === 'source') {
                return 'Mengambil penuh dari ' + sourceLabel;
            }

            if (mode === 'petugas') {
                return 'Sama dengan hasil premi petugas';
            }

            return percentText(percent) + ' dari ' + sourceLabel;
        }

        function petugasModeLabel(type, mode, percent, nominal, sourceLabel, config) {
            if (type === 'bpjs' && mode === 'percent') {
                return '(' + bpjsFormulaText(config) + ') dari ' + sourceLabel;
            }

            return modeLabel(mode, percent, nominal, sourceLabel);
        }

        function livePetugasFormula(type, mode, percent, nominal, config) {
            if (mode === 'nominal') {
                return formatRupiah(nominal) + ' nominal tetap';
            }

            if (type === 'bpjs') {
                return 'Total biaya radiologi x (' + bpjsFormulaText(config) + ')';
            }

            return percentText(percent) + ' x basis petugas';
        }

        function liveBersamaFormula(mode, percent, nominal) {
            if (mode === 'petugas') {
                return 'Sama dengan hasil premi petugas';
            }

            if (mode === 'source') {
                return '100% dari manajemen';
            }

            if (mode === 'nominal') {
                return formatRupiah(nominal) + ' nominal tetap';
            }

            return percentText(percent) + ' x manajemen';
        }

        function infoRowsHtml(rows) {
            return rows.map(function(row) {
                return '<div class="radiologi-info-row">' +
                    '<div>' +
                    '<div class="radiologi-info-label">' + escapeHtml(row.label) + '</div>' +
                    (row.note ? '<div class="radiologi-info-note">' + escapeHtml(row.note) + '</div>' : '') +
                    '</div>' +
                    '<div class="radiologi-info-value">' + escapeHtml(row.value) + '</div>' +
                    '</div>';
            }).join('');
        }

        function formulaHtml(data) {
            const config = data.config || data || {};
            const jenis = data.jenis_radiologi || config.jenis_radiologi || activeType;
            const petugasBase = jenis === 'bpjs' ? 'total biaya' : 'total tarif_tindakan_petugas';
            const sourcePeriode = data.source_periode || config.source_periode || sourcePeriodFor($('#periodeRadiologi').val(), jenis);

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
                    value: modeLabel(config.bersama_mode, config.bersama_percent, config.bersama_nominal, 'total manajemen'),
                    note: 'Basis: total manajemen.'
                }
            ]);
        }

        function miniCard(label, value, primary, icon, note) {
            return '<div class="radiologi-mini-card ' + (primary ? 'total' : '') + '">' +
                '<div class="radiologi-mini-top">' +
                '<div class="radiologi-mini-label">' + escapeHtml(label) + '</div>' +
                '<div class="radiologi-mini-icon"><i class="mdi ' + escapeHtml(icon || 'mdi-chart-box-outline') + '"></i></div>' +
                '</div>' +
                '<div class="radiologi-mini-value">' + escapeHtml(value) + '</div>' +
                (note ? '<div class="radiologi-mini-note">' + escapeHtml(note) + '</div>' : '') +
                '</div>';
        }

        function liveFormulaRow(label, value) {
            return infoRowsHtml([{ label: label, value: value }]);
        }

        function petugasBaseLabel(type) {
            return type === 'bpjs' ? 'Total biaya' : 'Tarif tindakan petugas';
        }

        function petugasBaseValue(type, source, fallback) {
            if (fallback !== undefined && fallback !== null) {
                return fallback;
            }

            return type === 'bpjs' ?
                (source.total_biaya || 0) :
                (source.total_tarif_tindakan_petugas || 0);
        }

        function recipientListHtml(items, options) {
            const showTotal = !options || options.showTotal !== false;

            if (!items || !items.length) {
                return '<div class="alert alert-warning mb-0">Belum ada penerima premi petugas pada konfigurasi ini.</div>';
            }

            return items.map(function(item) {
                const name = item.pegawai_name || item.text || '-';
                return '<div class="radiologi-recipient-item">' +
                    '<div>' +
                    '<div class="radiologi-recipient-name">' + escapeHtml(name) + '</div>' +
                    '<div class="radiologi-recipient-meta">' +
                    escapeHtml(item.pegawai_id || '-') + ' / ' + escapeHtml(item.pegawai_position || '-') +
                    (item.allocation_percent ? ' / ' + percentText(item.allocation_percent) : '') +
                    '</div>' +
                    '</div>' +
                    '<div class="radiologi-recipient-total">' +
                    (showTotal ? formatRupiah(item.total_received || 0) : '<span class="radiologi-recipient-status">Dipilih</span>') +
                    '</div>' +
                    '</div>';
            }).join('');
        }

        function initRecipientSelect() {
            if (!$.fn.select2) {
                return;
            }

            $('#configRadiologiRecipients').select2({
                dropdownParent: $('#modalConfigRadiologi'),
                width: '100%',
                placeholder: 'Pilih pegawai penerima',
                ajax: {
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateRadiologi.pegawaiOptions") }}",
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
            const select = $('#configRadiologiRecipients');
            select.empty();
            (items || []).forEach(function(item) {
                select.append(new Option(item.text || (item.pegawai_id + ' - ' + item.pegawai_name), item.pegawai_id, true, true));
            });
            select.trigger('change');
            updateRecipientCounter();
        }

        function updateRecipientCounter() {
            const count = ($('#configRadiologiRecipients').val() || []).length;
            $('#countRadiologiRecipients').text(formatNumber(count) + ' penerima dipilih');
        }

        function configRecipientItems() {
            return $('#configRadiologiRecipients option:selected').map(function() {
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
            const recipients = ($('#configRadiologiRecipients').val() || []).length;
            const petugasMode = $('#configPetugasMode').val();
            const bersamaMode = $('#configBersamaMode').val();
            const petugasPercent = syncBpjsFormulaFields();
            const bersamaPercent = Number($('#configBersamaPercent').val() || 0);
            const petugasNominal = Number($('#configPetugasNominal').val() || 0);
            const bersamaNominal = Number($('#configBersamaNominal').val() || 0);
            const periode = $('#periodeRadiologi').val() || '-';
            const sourcePeriode = sourcePeriodFor(periode, activeType);

            $('#configRadiologiRuleTitle').text('Konfigurasi ' + (typeConfig[activeType] || '-'));
            $('#configRadiologiRuleText').text(sourceRuleText(activeType, periode));
            $('#configRadiologiTypeBadge').text(typeConfig[activeType] || '-');
            $('#configPetugasModeHelp').text(
                activeType === 'bpjs' ?
                'Basis BPJS adalah total biaya bulan sebelumnya, dihitung dari angka dasar dibagi pembagi.' :
                'Basis UMUM adalah total tarif_tindakan_petugas dari periode aktif.'
            );
            $('#configRadiologiFormulaPreview').html([
                liveFormulaRow('Periode Data Khanza', sourcePeriode),
                liveFormulaRow('Basis Petugas', activeType === 'bpjs' ? 'Total biaya radiologi' : 'Total tarif_tindakan_petugas'),
                liveFormulaRow('Premi Petugas', livePetugasFormula(activeType, petugasMode, petugasPercent, petugasNominal)),
                liveFormulaRow('Premi Bersama', liveBersamaFormula(bersamaMode, bersamaPercent, bersamaNominal)),
                liveFormulaRow('Penerima Petugas', formatNumber(recipients) + ' pegawai')
            ].join(''));
            $('#configRadiologiRecipientPreview').html(
                configRecipientItems().length ?
                recipientListHtml(configRecipientItems(), { showTotal: false }) :
                '<div class="radiologi-empty-state">Belum ada pegawai dipilih. Cari nama pegawai lalu pilih sebagai penerima premi petugas.</div>'
            );
        }

        function setActiveType(type) {
            activeType = type;
            $('.radiologi-type-tab').removeClass('active');
            $('.radiologi-type-tab[data-type="' + type + '"]').addClass('active');
            $('#resultRadiologiTitle').text('Hasil Generate Radiologi ' + typeConfig[type]);
        }

        function loadFormulaConfig() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateRadiologi.config") }}",
                data: { jenis_radiologi: activeType },
                success: function(response) {
                    $('#formulaRadiologiStrip').html(formulaHtml(response.data || {}));
                }
            });
        }

        function loadSummary() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateRadiologi.summary") }}",
                data: {
                    periode: $('#periodeRadiologi').val(),
                    jenis_radiologi: activeType
                },
                success: function(response) {
                    const data = response.data || {};
                    $('#summaryTindakanRadiologi').text(formatNumber(data.jumlah_tindakan || 0));
                    $('#summaryPasienRadiologi').text(formatNumber(data.jumlah_pasien || 0));
                    $('#summaryPetugasRadiologi').text(formatRupiah(data.total_premi_petugas || 0));
                    $('#summaryBersamaRadiologi').text(formatRupiah(data.total_premi_bersama || 0));
                    $('#summaryLockedRadiologi').text(formatNumber(data.locked_count || 0));
                    $('#summaryRadiologiSubtitle').text(
                        'Jenis ' + (data.jenis_radiologi_label || typeConfig[activeType]) +
                        ' / ' + formatNumber(data.generated_count || 0) + ' data generate / ' +
                        sourceRuleText(activeType, $('#periodeRadiologi').val())
                    );
                },
                error: function() {
                    $('#summaryTindakanRadiologi, #summaryPasienRadiologi, #summaryLockedRadiologi').text('0');
                    $('#summaryPetugasRadiologi, #summaryBersamaRadiologi').text('Rp 0');
                }
            });
        }

        setDefaultPeriod();
        initRecipientSelect();

        const table = $('#tableGenerateRadiologi').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            lengthChange: false,
            pageLength: 10,
            order: [],
            ajax: {
                url: "{{ route("backOffice.keuangan.hitungPremi.generateRadiologi.table") }}",
                data: function(data) {
                    data.periode = $('#periodeRadiologi').val();
                    data.jenis_radiologi = activeType;
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'periode' },
                {
                    data: 'source_periode',
                    render: function(data, type, row) {
                        const source = data || sourcePeriodFor(row.periode, row.jenis_radiologi);
                        const note = row.jenis_radiologi === 'bpjs' ? 'Data bulan kemarin' : 'Data periode aktif';
                        return '<span class="fw-semibold">' + escapeHtml(source) + '</span>' +
                            '<small class="d-block text-muted">' + escapeHtml(note) + '</small>';
                    }
                },
                {
                    data: 'jenis_radiologi_label',
                    render: function(data, type, row) {
                        return '<span class="radiologi-badge ' + escapeHtml(row.jenis_radiologi) + '">' +
                            escapeHtml(data) + '</span>';
                    }
                },
                { data: 'jumlah_tindakan', className: 'text-center', render: formatNumber },
                {
                    data: null,
                    className: 'text-end',
                    render: function(data, type, row) {
                        const basis = row.jenis_radiologi === 'bpjs' ?
                            row.total_biaya :
                            row.total_tarif_tindakan_petugas;
                        return formatRupiah(basis);
                    }
                },
                { data: 'total_manajemen', className: 'text-end', render: formatRupiah },
                { data: 'total_premi_petugas', className: 'text-end', render: formatRupiah },
                { data: 'total_premi_bersama', className: 'text-end', render: formatRupiah },
                { data: 'details_count', className: 'text-center', render: formatNumber },
                {
                    data: 'is_locked',
                    render: function(data, type, row) {
                        if (data) {
                            return '<span class="radiologi-lock locked" title="' +
                                escapeHtml([row.locked_by_name, row.locked_at].filter(Boolean).join(' / ')) +
                                '"><i class="mdi mdi-lock"></i>Terkunci</span>';
                        }
                        return '<span class="radiologi-lock open"><i class="mdi mdi-lock-open-variant-outline"></i>Terbuka</span>';
                    }
                },
                { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
            ],
            language: {
                processing: 'Memuat data radiologi...',
                emptyTable: 'Belum ada hasil generate radiologi.',
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
            const periode = $('#periodeRadiologi').val() || '-';
            const type = data.jenis_radiologi || activeType;
            const sourcePeriode = data.source_periode || source.source_periode || sourcePeriodFor(periode, type);
            const petugasBase = petugasBaseLabel(type);

            $('#generateRadiologiTitle').text(
                'Preview ' + (data.jenis_radiologi_label || typeConfig[type] || '-') + ' / Generate ' + periode
            );
            $('#generateRadiologiText').text(
                'Pastikan nominal dan penerima sudah benar sebelum disimpan.'
            );
            $('#generateRadiologiTypeBadge').text(data.jenis_radiologi_label || typeConfig[type] || '-');
            $('#generateRadiologiSourceBadge').html(
                '<i class="mdi mdi-calendar-search"></i> Data ' + escapeHtml(sourcePeriode)
            );
            $('#previewRadiologiSourceNote').html(
                '<i class="mdi mdi-database-search-outline"></i>' +
                '<div>' +
                '<strong>Data Khanza: ' + escapeHtml(sourcePeriode) + '</strong>' +
                '<span>' + escapeHtml(type === 'bpjs' ? 'Filter kd_pj BPJ. Hasil disimpan untuk periode ' + periode + '.' : 'Filter kd_pj selain BPJ dan -. Hasil disimpan untuk periode ' + periode + '.') + '</span>' +
                '</div>'
            );
            $('#previewRadiologiStats').html([
                miniCard('Tindakan', formatNumber(source.jumlah_tindakan || 0), false, 'mdi-format-list-numbered', formatNumber(source.jumlah_pasien || 0) + ' pasien'),
                miniCard(petugasBase, formatRupiah(petugasBaseValue(type, source, data.petugas_base)), false, type === 'bpjs' ? 'mdi-cash-multiple' : 'mdi-cash-register', 'Basis premi petugas'),
                miniCard('Manajemen', formatRupiah(source.total_manajemen || 0), false, 'mdi-hospital-building', 'Basis premi bersama'),
                miniCard('Premi Petugas', formatRupiah(pools.petugas || 0), true, 'mdi-account-hard-hat-outline', formatNumber(recipients.length) + ' penerima'),
                miniCard('Premi Bersama', formatRupiah(pools.bersama || 0), false, 'mdi-account-group-outline', 'Untuk premi bersama'),
                miniCard('Dibagikan', formatRupiah(data.total_dibagikan || 0), true, 'mdi-bank-transfer-out', 'Total ke pegawai')
            ].join(''));
            $('#previewRadiologiFormula').html(formulaHtml(data));
            $('#previewRadiologiRecipientSubtitle').text(
                formatNumber(recipients.length) + ' penerima / masing-masing ' + formatRupiah(pools.petugas || 0)
            );
            $('#previewRadiologiRecipients').html(recipientListHtml(recipients));
            $('#previewRadiologiQuality').html(
                recipients.length ?
                '<div class="radiologi-quality ok"><strong>Siap generate.</strong> Klik Generate dan Simpan untuk menyimpan hasil periode ini.</div>' :
                '<div class="radiologi-quality warn"><strong>Penerima belum dipilih.</strong> Buka konfigurasi dan pilih pegawai penerima premi petugas.</div>'
            );
            $('#previewRadiologiLoading').addClass('d-none');
            $('#previewRadiologiContent').removeClass('d-none');
            previewReady = true;
            $('#btnSubmitGenerateRadiologi').prop('disabled', false);
        }

        function openGenerateModal() {
            previewReady = false;
            $('#btnSubmitGenerateRadiologi').prop('disabled', true);
            $('#previewRadiologiLoading').removeClass('d-none');
            $('#previewRadiologiContent').addClass('d-none');
            generateModal.show();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateRadiologi.preview") }}",
                data: {
                    periode: $('#periodeRadiologi').val(),
                    jenis_radiologi: activeType
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
                url: "{{ route("backOffice.keuangan.hitungPremi.generateRadiologi.config") }}",
                data: { jenis_radiologi: activeType },
                success: function(response) {
                    const data = response.data || {};
                    $('#modalConfigRadiologiLabel').text('Konfigurasi Premi Radiologi ' + typeConfig[activeType]);
                    $('#jenisConfigRadiologi').val(activeType);
                    $('#configPetugasMode').val(data.petugas_mode || 'percent');
                    $('#configPetugasPercent').val(data.petugas_percent || 0);
                    $('#configPetugasNominal').val(data.petugas_nominal || 0);
                    $('#configBpjsPetugasFormulaRate').val(data.bpjs_petugas_formula_rate || 0.04);
                    $('#configBpjsPetugasFormulaDivider').val(data.bpjs_petugas_formula_divider || 4);
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
            syncBpjsFormulaFields();

            return {
                jenis_radiologi: $('#jenisConfigRadiologi').val(),
                petugas_mode: $('#configPetugasMode').val(),
                petugas_percent: $('#configPetugasPercent').val(),
                petugas_nominal: $('#configPetugasNominal').val(),
                bpjs_petugas_formula_rate: $('#configBpjsPetugasFormulaRate').val(),
                bpjs_petugas_formula_divider: $('#configBpjsPetugasFormulaDivider').val(),
                bersama_mode: $('#configBersamaMode').val(),
                bersama_percent: $('#configBersamaPercent').val(),
                bersama_nominal: $('#configBersamaNominal').val(),
                recipients: $('#configRadiologiRecipients').val() || []
            };
        }

        function renderDetail(data) {
            const source = data.source || {};
            const pools = data.pools || {};
            const recipients = data.details || [];
            const config = data.config_snapshot || {};
            const type = data.jenis_radiologi || activeType;
            const sourcePeriode = data.source_periode || config.source_periode || sourcePeriodFor(data.periode, type);

            $('#detailRadiologiTitle').text((data.periode || '-') + ' / ' + (data.jenis_radiologi_label || '-'));
            $('#detailRadiologiMeta').text(
                'Generated oleh ' + (data.generate_by_name || '-') + ' pada ' + (data.generated_at || '-') +
                ' / Status: ' + (data.is_locked ? 'Terkunci' : 'Terbuka')
            );
            $('#detailRadiologiSourceMeta').text(
                'Periode data Khanza: ' + sourcePeriode + ' / ' +
                (type === 'bpjs' ? 'BPJS memakai data bulan kemarin' : 'UMUM memakai periode aktif')
            );
            $('#detailRadiologiStatusBadge').html(
                data.is_locked ? 'Terkunci' : 'Terbuka'
            );
            $('#detailRadiologiStats').html([
                miniCard('Tindakan', formatNumber(source.jumlah_tindakan || 0), false, 'mdi-format-list-numbered', formatNumber(source.jumlah_pasien || 0) + ' pasien'),
                miniCard(petugasBaseLabel(type), formatRupiah(petugasBaseValue(type, source)), false, type === 'bpjs' ? 'mdi-cash-multiple' : 'mdi-cash-register', 'Basis premi petugas'),
                miniCard('Manajemen', formatRupiah(source.total_manajemen || 0), false, 'mdi-hospital-building', 'Basis premi bersama'),
                miniCard('Premi Petugas', formatRupiah(pools.petugas || 0), true, 'mdi-account-hard-hat-outline', formatNumber(recipients.length) + ' penerima'),
                miniCard('Premi Bersama', formatRupiah(pools.bersama || 0), false, 'mdi-account-group-outline', 'Untuk premi bersama'),
                miniCard('Dibagikan', formatRupiah(data.total_dibagikan || 0), true, 'mdi-bank-transfer-out', 'Total ke pegawai')
            ].join(''));
            $('#detailRadiologiFormula').html(formulaHtml({
                jenis_radiologi: type,
                source_periode: sourcePeriode,
                config: config
            }));
            $('#detailRadiologiRecipientSubtitle').text(
                formatNumber(recipients.length) + ' penerima / masing-masing ' + formatRupiah(pools.petugas || 0)
            );
            $('#detailRadiologiRecipients').html(recipientListHtml(recipients));
        }

        function openDetailModal(id) {
            const template =
                "{{ route("backOffice.keuangan.hitungPremi.generateRadiologi.detail", ["id" => "__ID__"]) }}";

            $('#detailRadiologiLoading').removeClass('d-none');
            $('#detailRadiologiContent').addClass('d-none');
            detailModal.show();

            $.ajax({
                url: template.replace('__ID__', id),
                success: function(response) {
                    renderDetail(response.data || {});
                    $('#detailRadiologiLoading').addClass('d-none');
                    $('#detailRadiologiContent').removeClass('d-none');
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
                "{{ route("backOffice.keuangan.hitungPremi.generateRadiologi.lock", ["id" => "__ID__"]) }}" :
                "{{ route("backOffice.keuangan.hitungPremi.generateRadiologi.unlock", ["id" => "__ID__"]) }}";

            Swal.fire({
                icon: isLock ? 'warning' : 'question',
                title: isLock ? 'Kunci data radiologi?' : 'Buka kunci data radiologi?',
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
                "{{ route("backOffice.keuangan.hitungPremi.generateRadiologi.delete", ["id" => "__ID__"]) }}";

            Swal.fire({
                icon: 'warning',
                title: 'Hapus data radiologi?',
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

        $('#periodeRadiologi').on('change', refreshAll);
        $('#btnPrevPeriodRadiologi').on('click', () => movePeriod(-1));
        $('#btnNextPeriodRadiologi').on('click', () => movePeriod(1));
        $('#btnCurrentPeriodRadiologi').on('click', function() {
            const now = new Date();
            setPeriodFromDate(new Date(now.getFullYear(), now.getMonth(), 1));
        });
        $('#btnRefreshRadiologi').on('click', refreshAll);
        $('#btnOpenGenerateRadiologi').on('click', openGenerateModal);
        $('#btnOpenConfigRadiologi').on('click', openConfigModal);
        $('#searchGenerateRadiologi').on('input', function() {
            table.search(this.value).draw();
        });
        $('.radiologi-type-tab').on('click', function() {
            setActiveType($(this).data('type'));
            refreshAll();
        });
        $('#configRadiologiRecipients').on('change', function() {
            updateRecipientCounter();
            updateConfigOverview();
        });
        $('#formConfigRadiologi select, #formConfigRadiologi input').on('input change', updateConfigOverview);

        $('#formConfigRadiologi').on('submit', function(event) {
            event.preventDefault();
            const button = $('#btnSubmitConfigRadiologi');
            const originalHtml = button.html();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateRadiologi.updateConfig") }}",
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

        $('#formGenerateRadiologi').on('submit', function(event) {
            event.preventDefault();

            if (!previewReady) {
                openGenerateModal();
                return;
            }

            const button = $('#btnSubmitGenerateRadiologi');
            const originalHtml = button.html();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateRadiologi.store") }}",
                method: 'POST',
                data: {
                    periode: $('#periodeRadiologi').val(),
                    jenis_radiologi: activeType
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

        $('#tableGenerateRadiologi').on('click', '.btn-detail-radiologi', function() {
            openDetailModal($(this).data('id'));
        });

        $('#tableGenerateRadiologi').on('click', '.btn-lock-radiologi', function() {
            changeLock($(this).data('id'), 'lock');
        });

        $('#tableGenerateRadiologi').on('click', '.btn-unlock-radiologi', function() {
            changeLock($(this).data('id'), 'unlock');
        });

        $('#tableGenerateRadiologi').on('click', '.btn-delete-radiologi', function() {
            deleteResult($(this).data('id'));
        });

        refreshAll();
    });
</script>
