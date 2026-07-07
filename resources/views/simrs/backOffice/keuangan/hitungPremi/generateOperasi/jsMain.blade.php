<script>
    $(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const typeConfig = {
            umum: 'Umum',
            bpjs: 'BPJS'
        };
        let activeType = 'umum';
        let previewReady = false;

        const generateModal = modalInstance('modalGenerateOperasi');
        const configModal = modalInstance('modalConfigOperasi');
        const detailModal = modalInstance('modalDetailOperasi');

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

        function numeric(value) {
            return String(value || '').replace(/\D/g, '');
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

        function formatInput(el) {
            const value = numeric(el.value).replace(/^0+(?=\d)/, '');
            el.value = value ? formatNumber(value) : '';
        }

        function resetGeneratePreview(message) {
            previewReady = false;
            $('#btnSubmitGenerateOperasi').prop('disabled', true);
            $('#previewGenerateOperasiWrap').addClass('d-none');
            $('#previewQualityOperasi').empty();
            $('#previewHintOperasi').removeClass('alert-success alert-danger alert-warning').addClass('alert-info')
                .text(message || 'Masukkan data generate, lalu tampilkan preview konfigurasi sebelum generate.');
        }

        function bpjsGrandTotal() {
            return Number(numeric($('#jumlahPasienGenerateOperasi').val())) *
                Number(numeric($('#nominalPengaliGenerateOperasi').val()));
        }

        function updateGenerateInputMode() {
            const isBpjs = activeType === 'bpjs';
            $('#totalGenerateOperasiGroup').toggleClass('d-none', isBpjs);
            $('#bpjsGenerateOperasiGroup').toggleClass('d-none', !isBpjs);
            $('#grandTotalBpjsGenerateOperasi').val(formatNumber(bpjsGrandTotal()));
        }

        function generatePayload() {
            const jenis = $('#jenisGenerateOperasi').val() || activeType;

            if (jenis === 'bpjs') {
                return {
                    jenis_operasi: jenis,
                    jumlah_pasien: numeric($('#jumlahPasienGenerateOperasi').val()),
                    nominal_pengali: numeric($('#nominalPengaliGenerateOperasi').val())
                };
            }

            return {
                jenis_operasi: jenis,
                total_operasi: numeric($('#totalGenerateOperasi').val())
            };
        }

        function setDefaultPeriod() {
            const now = new Date();
            $('#periodeOperasi').val(now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
        }

        function periodFromInput() {
            const parts = String($('#periodeOperasi').val() || '').split('-').map(Number);
            return parts.length === 2 && parts[0] && parts[1] ?
                new Date(parts[0], parts[1] - 1, 1) :
                new Date();
        }

        function setPeriodFromDate(date) {
            $('#periodeOperasi')
                .val(date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0'))
                .trigger('change');
        }

        function movePeriod(offset) {
            const date = periodFromInput();
            date.setMonth(date.getMonth() + offset);
            setPeriodFromDate(date);
        }

        function setActiveType(type) {
            activeType = type;
            $('.operasi-type-tab').removeClass('active');
            $('.operasi-type-tab[data-type="' + type + '"]').addClass('active');
            $('#resultOperasiTitle').text('Hasil Generate Operasi ' + typeConfig[type]);
            updateActiveContext();
        }

        function updateActiveContext() {
            $('#contextPeriodeOperasi').text($('#periodeOperasi').val() || '-');
            $('#contextJenisOperasi').text(typeConfig[activeType] || '-');
            $('#contextStatusOperasi').text('Siap preview');
        }

        function initRecipientSelects() {
            if (!$.fn.select2) {
                return;
            }

            $('.operasi-pegawai-select').select2({
                dropdownParent: $('#modalConfigOperasi'),
                width: '100%',
                placeholder: 'Pilih pegawai',
                ajax: {
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateOperasi.pegawaiOptions") }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ q: params.term || '' }),
                    processResults: response => ({
                        results: (response.data || []).map(item => ({ id: item.id, text: item.text }))
                    })
                }
            });

            $('.operasi-dokter-select').select2({
                dropdownParent: $('#modalConfigOperasi'),
                width: '100%',
                placeholder: 'Pilih dokter',
                ajax: {
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateOperasi.dokterOptions") }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ q: params.term || '' }),
                    processResults: response => ({
                        results: (response.data || []).map(item => ({ id: item.id, text: item.text }))
                    })
                }
            });
        }

        function fillSelect(select, items) {
            select.empty();
            (items || []).forEach(function(item) {
                select.append(new Option(item.text || (item.pegawai_id + ' - ' + item.pegawai_name), item.pegawai_id, true, true));
            });
            select.trigger('change');
        }

        function selectedCount(selector) {
            return ($(selector).val() || []).length;
        }

        function updateRecipientCounters() {
            $('#countInstrumen20Recipients').text(formatNumber(selectedCount('#configInstrumen20Recipients')) + ' penerima dipilih');
            $('#countInstrumen80Recipients').text(formatNumber(selectedCount('#configInstrumen80Recipients')) + ' penerima dipilih');
            $('#countDokterAnastesiRecipients').text(formatNumber(selectedCount('#configDokterAnastesiRecipients')) + ' penerima dipilih');
            $('#countPerawatAnastesiRecipients').text(formatNumber(selectedCount('#configPerawatAnastesiRecipients')) + ' penerima dipilih');
        }

        function recipientStatsFromRecipients(recipients) {
            const dokterAnastesiCount = activeType === 'bpjs' ? 0 : (recipients.dokter_anastesi || []).length;
            const counts = {
                instrumen20: (recipients.instrumen_20 || []).length,
                instrumen80: (recipients.instrumen_80 || []).length,
                dokterAnastesi: dokterAnastesiCount,
                perawatAnastesi: (recipients.perawat_anastesi || []).length
            };

            return {
                total: counts.instrumen20 + counts.instrumen80 + counts.dokterAnastesi + counts.perawatAnastesi,
                instrumen: counts.instrumen20 + counts.instrumen80,
                anastesi: counts.dokterAnastesi + counts.perawatAnastesi,
                counts: counts
            };
        }

        function renderMiniStats(container, stats) {
            $(container).html([
                ['Total', stats.total],
                ['Instrumen', stats.instrumen],
                ['Anastesi', stats.anastesi]
            ].map(function(item) {
                return '<div class="operasi-mini-stat">' +
                    '<div class="operasi-mini-stat-label">' + escapeHtml(item[0]) + '</div>' +
                    '<div class="operasi-mini-stat-value">' + formatNumber(item[1]) + '</div>' +
                    '</div>';
            }).join(''));
        }

        function updateGenerateConfigStats(config) {
            renderMiniStats('#generateConfigStats', recipientStatsFromRecipients((config || {}).recipients || {}));
        }

        function applyConfigMode() {
            const isBpjs = activeType === 'bpjs';
            $('#configInstrumenPetugasField, #configDokterAnastesiPercentField, #configPerawatAnastesiPercentField, #configDokterAnastesiRecipientsGroup')
                .toggleClass('d-none', isBpjs);
            $('#configPerawatAnastesiRecipientsLabel').text(isBpjs ? 'Pegawai Anastesi' : 'Perawat Anastesi');
            $('#formConfigOperasi input[type="number"]').prop('readonly', isBpjs);
        }

        function formRecipients() {
            const isBpjs = activeType === 'bpjs';

            return {
                instrumen_20: $('#configInstrumen20Recipients').val() || [],
                instrumen_80: $('#configInstrumen80Recipients').val() || [],
                dokter_anastesi: isBpjs ? [] : ($('#configDokterAnastesiRecipients').val() || []),
                perawat_anastesi: $('#configPerawatAnastesiRecipients').val() || []
            };
        }

        function numericFloat(selector) {
            return Number(String($(selector).val() || '0').replace(',', '.')) || 0;
        }

        function healthRow(ok, text) {
            return '<div class="operasi-config-health-row ' + (ok ? 'ok' : 'warn') + '">' +
                '<i class="mdi ' + (ok ? 'mdi-check-circle-outline' : 'mdi-alert-circle-outline') + '"></i>' +
                '<span>' + escapeHtml(text) + '</span>' +
                '</div>';
        }

        function updateConfigOverview() {
            const stats = recipientStatsFromRecipients(formRecipients());
            const instrumenSplit = numericFloat('#configPremiBersamaPercent') + numericFloat('#configInstrumenPetugasPercent');
            const bpjsInstrumenSplit = numericFloat('#configInstrumenPercent') + numericFloat('#configPremiBersamaPercent');
            const petugasSplit = numericFloat('#configInstrumen20Percent') + numericFloat('#configInstrumen80Percent');
            const anastesiPercent = numericFloat('#configDokterAnastesiPercent');
            const perawatPercent = numericFloat('#configPerawatAnastesiPercent');
            const perawatSplit = numericFloat('#configPerawatAnastesiPetugasPercent') + numericFloat('#configPerawatAnastesiBersamaPercent');
            const rows = activeType === 'bpjs' ? [
                healthRow(Math.abs(bpjsInstrumenSplit - 100) < 0.01, 'Instrumen 80 + premi bersama 20 = ' + formatNumber(bpjsInstrumenSplit) + '% dari grand total'),
                healthRow(Math.abs(petugasSplit - 100) < 0.01, 'Instrumen dibagi pegawai 80 + pegawai khusus 20 = ' + formatNumber(petugasSplit) + '% dari pool instrumen'),
                healthRow(Math.abs(perawatSplit - 100) < 0.01, 'Anastesi pegawai 80 + premi bersama 20 = ' + formatNumber(perawatSplit) + '% dari grand total')
            ] : [
                healthRow(Math.abs(instrumenSplit - 100) < 0.01, 'Premi bersama + petugas instrumen = ' + formatNumber(instrumenSplit) + '%'),
                healthRow(Math.abs(petugasSplit - 100) < 0.01, 'Kelompok instrumen 20 + 80 = ' + formatNumber(petugasSplit) + '%'),
                healthRow(anastesiPercent > 0, 'Pool anastesi dari total operasi = ' + formatNumber(anastesiPercent) + '%'),
                healthRow(perawatPercent <= 100, 'Bagian perawat anastesi dari pool anastesi = ' + formatNumber(perawatPercent) + '%'),
                healthRow(Math.abs(perawatSplit - 100) < 0.01, 'Petugas anastesi + premi bersama = ' + formatNumber(perawatSplit) + '%')
            ];

            renderMiniStats('#configRecipientStats', stats);
            $('#configFormulaHealth').html(rows.join(''));
        }

        function percentText(value) {
            return formatNumber(value || 0) + '%';
        }

        function formulaFlowRows(config) {
            const p = (config && config.percentages) || {};
            const recipients = (config && config.recipients) || {};
            const recipientCounts = (config && config.recipient_counts) || {};
            const isBpjs = (config && config.jenis_operasi === 'bpjs') || activeType === 'bpjs';
            const count = function(role) {
                return (recipients[role] || []).length || Number(recipientCounts[role] || 0);
            };

            if (isBpjs) {
                return [
                    {
                        icon: 'mdi-calculator-variant-outline',
                        title: 'Grand total BPJS',
                        subtitle: 'Jumlah PX dikali nominal saat generate.',
                        value: 'PX x nominal'
                    },
                    {
                        icon: 'mdi-stethoscope',
                        title: 'Anastesi',
                        subtitle: percentText(p.perawat_anastesi_petugas_percent) +
                            ' dari grand total dibagi rata ke pegawai anastesi, ' +
                            percentText(p.perawat_anastesi_premi_bersama_percent) +
                            ' masuk premi bersama.',
                        value: count('perawat_anastesi') + ' orang'
                    },
                    {
                        icon: 'mdi-medical-bag',
                        title: 'Instrumen',
                        subtitle: percentText(p.instrumen_percent) +
                            ' dari grand total menjadi pool instrumen.',
                        value: percentText(p.instrumen_percent)
                    },
                    {
                        icon: 'mdi-account-hard-hat-outline',
                        title: 'Pegawai instrumen',
                        subtitle: 'Pool instrumen dipecah ' +
                            percentText(p.instrumen_petugas_kelompok_80_percent) +
                            ' untuk pegawai instrumen dan ' +
                            percentText(p.instrumen_petugas_kelompok_20_percent) +
                            ' untuk pegawai khusus.',
                        value: count('instrumen_80') + ' + ' + count('instrumen_20') + ' orang'
                    },
                    {
                        icon: 'mdi-account-group-outline',
                        title: 'Premi bersama',
                        subtitle: percentText(p.instrumen_premi_bersama_percent) +
                            ' dari grand total untuk instrumen + ' +
                            percentText(p.perawat_anastesi_premi_bersama_percent) +
                            ' dari grand total untuk anastesi.',
                        value: 'Otomatis'
                    }
                ].map(function(item) {
                    return '<div class="operasi-flow-row">' +
                        '<div class="operasi-flow-icon"><i class="mdi ' + item.icon + '"></i></div>' +
                        '<div>' +
                        '<div class="operasi-flow-title">' + escapeHtml(item.title) + '</div>' +
                        '<div class="operasi-flow-subtitle">' + escapeHtml(item.subtitle) + '</div>' +
                        '</div>' +
                        '<div class="operasi-flow-value">' + escapeHtml(item.value) + '</div>' +
                        '</div>';
                }).join('');
            }

            return [
                {
                    icon: 'mdi-medical-bag',
                    title: 'Instrumen',
                    subtitle: percentText(p.instrumen_percent) + ' dari total operasi',
                    value: percentText(p.instrumen_percent)
                },
                {
                    icon: 'mdi-account-multiple-outline',
                    title: 'Premi bersama',
                    subtitle: percentText(p.instrumen_premi_bersama_percent) + ' dari pool instrumen',
                    value: percentText(p.instrumen_premi_bersama_percent)
                },
                {
                    icon: 'mdi-account-hard-hat-outline',
                    title: 'Petugas instrumen',
                    subtitle: percentText(p.instrumen_petugas_percent) + ' dari pool instrumen, lalu pecah ' +
                        percentText(p.instrumen_petugas_kelompok_20_percent) + ' dan ' +
                        percentText(p.instrumen_petugas_kelompok_80_percent),
                    value: count('instrumen_20') + ' + ' + count('instrumen_80') + ' orang'
                },
                {
                    icon: 'mdi-stethoscope',
                    title: 'Pool anastesi',
                    subtitle: percentText(p.dokter_anastesi_percent) + ' dari total operasi',
                    value: percentText(p.dokter_anastesi_percent)
                },
                {
                    icon: 'mdi-doctor',
                    title: 'Dokter dan perawat anastesi',
                    subtitle: 'Bagian perawat ' + percentText(p.perawat_anastesi_percent) +
                        ' dari pool anastesi, lalu pecah ' +
                        percentText(p.perawat_anastesi_petugas_percent) + ' untuk petugas dan ' +
                        percentText(p.perawat_anastesi_premi_bersama_percent) + ' ke premi bersama.',
                    value: count('dokter_anastesi') + ' + ' + count('perawat_anastesi') + ' orang'
                }
            ].map(function(item) {
                return '<div class="operasi-flow-row">' +
                    '<div class="operasi-flow-icon"><i class="mdi ' + item.icon + '"></i></div>' +
                    '<div>' +
                    '<div class="operasi-flow-title">' + escapeHtml(item.title) + '</div>' +
                    '<div class="operasi-flow-subtitle">' + escapeHtml(item.subtitle) + '</div>' +
                    '</div>' +
                    '<div class="operasi-flow-value">' + escapeHtml(item.value) + '</div>' +
                    '</div>';
            }).join('');
        }

        function renderFormulaFlow(config) {
            const p = (config && config.percentages) || {};
            const isBpjs = (config && config.jenis_operasi === 'bpjs') || activeType === 'bpjs';

            $('#generateFormulaSubtitle').text(isBpjs ?
                'BPJS memakai PX x nominal, lalu alur anastesi dan instrumen 80/20.' :
                'Instrumen ' + percentText(p.instrumen_percent) +
                ' / Anastesi ' + percentText(p.dokter_anastesi_percent)
            );
            $('#generateFormulaFlow').html(formulaFlowRows(config));
        }

        function loadGenerateConfigSummary() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateOperasi.config") }}",
                data: { jenis_operasi: activeType },
                success: function(response) {
                    const config = response.data || {};
                    renderFormulaFlow(config);
                    updateGenerateConfigStats(config);
                }
            });
        }

        function loadSummary() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateOperasi.summary") }}",
                data: {
                    periode: $('#periodeOperasi').val(),
                    jenis_operasi: activeType
                },
                success: function(response) {
                    const data = response.data || {};
                    $('#summaryTotalOperasi').text(formatRupiah(data.total_operasi));
                    $('#summaryDibagikanOperasi').text(formatRupiah(data.total_dibagikan));
                    $('#summaryBersamaOperasi').text(formatRupiah(data.total_premi_bersama));
                    $('#summaryLockedOperasi').text(formatNumber(data.locked_count || 0));
                    $('#summaryOperasiSubtitle').text(
                        'Jenis ' + (data.jenis_operasi_label || typeConfig[activeType]) +
                        ' / ' + formatNumber(data.generated_count || 0) + ' data generate'
                    );
                    $('#contextStatusOperasi').text(
                        formatNumber(data.generated_count || 0) + ' data / ' +
                        formatNumber(data.locked_count || 0) + ' terkunci'
                    );
                },
                error: function() {
                    $('#summaryTotalOperasi, #summaryDibagikanOperasi, #summaryBersamaOperasi').text('Rp 0');
                    $('#summaryLockedOperasi').text('0');
                    $('#contextStatusOperasi').text('Ringkasan gagal dimuat');
                }
            });
        }

        setDefaultPeriod();
        updateActiveContext();
        initRecipientSelects();

        const table = $('#tableGenerateOperasi').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            lengthChange: false,
            pageLength: 10,
            order: [],
            ajax: {
                url: "{{ route("backOffice.keuangan.hitungPremi.generateOperasi.table") }}",
                data: function(data) {
                    data.periode = $('#periodeOperasi').val();
                    data.jenis_operasi = activeType;
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'periode' },
                {
                    data: 'jenis_operasi_label',
                    render: function(data, type, row) {
                        return '<span class="operasi-badge ' + escapeHtml(row.jenis_operasi) + '">' +
                            escapeHtml(data) + '</span>';
                    }
                },
                {
                    data: 'total_operasi',
                    className: 'text-end',
                    render: function(data, type, row) {
                        const bpjsNote = row.jenis_operasi === 'bpjs' ?
                            '<small class="d-block text-muted">' +
                            formatNumber(row.jumlah_pasien || 0) + ' PX x ' +
                            formatRupiah(row.nominal_pengali || 0) +
                            '</small>' :
                            '';

                        return '<strong>' + formatRupiah(data) + '</strong>' + bpjsNote;
                    }
                },
                { data: 'total_premi_bersama', className: 'text-end', render: formatRupiah },
                {
                    data: null,
                    className: 'text-end',
                    render: function(data, type, row) {
                        const total = Number(row.total_instrumen_kelompok_20 || 0) +
                            Number(row.total_instrumen_kelompok_80 || 0) +
                            Number(row.total_dokter_anastesi || 0) +
                            Number(row.total_perawat_anastesi || 0);
                        return '<strong class="text-primary">' + formatRupiah(total) + '</strong>';
                    }
                },
                { data: 'details_count', className: 'text-center', render: formatNumber },
                {
                    data: 'is_locked',
                    render: function(data, type, row) {
                        if (data) {
                            return '<span class="operasi-lock locked" title="' +
                                escapeHtml([row.locked_by_name, row.locked_at].filter(Boolean).join(' / ')) +
                                '"><i class="mdi mdi-lock"></i>Terkunci</span>';
                        }
                        return '<span class="operasi-lock open"><i class="mdi mdi-lock-open-variant-outline"></i>Terbuka</span>';
                    }
                },
                {
                    data: 'generate_by_name',
                    render: function(data, type, row) {
                        return '<span class="fw-semibold">' + escapeHtml(data || '-') + '</span>' +
                            '<small class="d-block text-muted">' + escapeHtml(row.generated_at || '-') + '</small>';
                    }
                },
                { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
            ],
            language: {
                processing: 'Memuat data operasi...',
                search: '',
                searchPlaceholder: 'Cari data...',
                emptyTable: 'Belum ada hasil generate operasi.',
                zeroRecords: 'Data tidak ditemukan.',
                info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                infoEmpty: 'Belum ada data',
                paginate: { previous: 'Sebelumnya', next: 'Berikutnya' }
            }
        });

        function refreshAll() {
            loadSummary();
            table.ajax.reload(null, false);
        }

        function openGenerateModal() {
            previewReady = false;
            $('#periodeGenerateOperasi').val($('#periodeOperasi').val());
            $('#jenisGenerateOperasi').val(activeType);
            $('#jenisGenerateOperasiLabel').val(typeConfig[activeType]);
            $('#generateConfigBadge').text(typeConfig[activeType].toUpperCase());
            $('#generateHeroTypeBadge').text(typeConfig[activeType].toUpperCase());
            $('#generateHeroPeriodBadge').text($('#periodeOperasi').val() || '-');
            $('#generateModalHeroTitle').text('Generate Operasi ' + typeConfig[activeType]);
            $('#totalGenerateOperasi').val('');
            $('#jumlahPasienGenerateOperasi').val('');
            $('#nominalPengaliGenerateOperasi').val('');
            $('#grandTotalBpjsGenerateOperasi').val('0');
            updateGenerateInputMode();
            $('#previewGenerateOperasiWrap').addClass('d-none');
            $('#previewInsightOperasi, #previewPoolsOperasi, #previewRecipientsOperasi').empty();
            $('#previewQualityOperasi').empty();
            $('#previewHintOperasi').removeClass('alert-success alert-danger').addClass('alert-info')
                .text(activeType === 'bpjs' ?
                    'Masukkan jumlah PX dan nominal, lalu tampilkan preview konfigurasi sebelum generate.' :
                    'Masukkan total nominal, lalu tampilkan preview konfigurasi sebelum generate.'
                );
            $('#btnSubmitGenerateOperasi').prop('disabled', true);
            loadGenerateConfigSummary();
            generateModal.show();
        }

        function renderPreview(data) {
            const pools = data.pools || {};
            const totalSisa = Math.max(
                0,
                Number(data.total_operasi || 0) -
                Number(data.total_dibagikan || 0) -
                Number(pools.premi_bersama || 0)
            );
            renderFormulaFlow(data.config || {});
            const insightRows = [
                ['Total Operasi', data.total_operasi, true],
                ['Premi Bersama Total', pools.premi_bersama, false],
                ['Dibagikan', data.total_dibagikan, false],
                ['Sisa', totalSisa, false]
            ];

            if (data.jenis_operasi === 'bpjs') {
                insightRows.splice(1, 0,
                    ['Jumlah PX', data.jumlah_pasien || 0, false, 'number'],
                    ['Nominal', data.nominal_pengali || 0, false]
                );
            }

            $('#previewInsightOperasi').html(insightRows.map(function(item) {
                return '<div class="operasi-preview-insight-card' + (item[2] ? ' primary' : '') + '">' +
                    '<div class="operasi-preview-insight-label">' + escapeHtml(item[0]) + '</div>' +
                    '<div class="operasi-preview-insight-value">' +
                    (item[3] === 'number' ? formatNumber(item[1]) : formatRupiah(item[1])) +
                    '</div>' +
                    '</div>';
            }).join(''));
            const poolRows = data.jenis_operasi === 'bpjs' ? [
                ['Grand Total BPJS', data.total_operasi, true],
                ['Anastesi ke Pegawai', pools.perawat_anastesi, false],
                ['Premi Bersama Anastesi', pools.perawat_anastesi_premi_bersama, false],
                ['Pool Instrumen', pools.instrumen, false],
                ['Pegawai Instrumen 80%', pools.instrumen_kelompok_80, false],
                ['Pegawai Khusus Instrumen 20%', pools.instrumen_kelompok_20, false],
                ['Premi Bersama Instrumen', pools.premi_bersama_instrumen, false],
                ['Premi Bersama Total', pools.premi_bersama, true],
                ['Total Dibagikan', data.total_dibagikan, true]
            ] : [
                ['Total Operasi', data.total_operasi, true],
                ['Instrumen', pools.instrumen, false],
                ['Premi Bersama Total', pools.premi_bersama, false],
                ['Premi Bersama dari Instrumen', pools.premi_bersama_instrumen, false],
                ['Premi Bersama dari Perawat Anastesi', pools.perawat_anastesi_premi_bersama, false],
                ['Instrumen Kelompok 20', pools.instrumen_kelompok_20, false],
                ['Instrumen Kelompok 80', pools.instrumen_kelompok_80, false],
                ['Dokter Anastesi', pools.dokter_anastesi, false],
                ['Bagian Perawat Anastesi', pools.perawat_anastesi_pool, false],
                ['Petugas Anastesi', pools.perawat_anastesi, false],
                ['Total Dibagikan', data.total_dibagikan, true]
            ];

            $('#previewPoolsOperasi').html(poolRows.map(function(item) {
                return '<div class="operasi-preview-item' + (item[2] ? ' highlight' : '') + '">' +
                    '<div class="operasi-preview-label">' + escapeHtml(item[0]) + '</div>' +
                    '<div class="operasi-preview-value">' + formatRupiah(item[1]) + '</div>' +
                    '</div>';
            }).join(''));

            const recipients = data.recipients || [];
            const grouped = {};
            recipients.forEach(function(item) {
                if (!grouped[item.role]) {
                    grouped[item.role] = {
                        label: item.role_label,
                        pool: item.pool_total,
                        items: []
                    };
                }

                grouped[item.role].items.push(item);
            });
            const roleOrder = ['instrumen_20', 'instrumen_80', 'dokter_anastesi', 'perawat_anastesi'];
            const recipientHtml = roleOrder.filter(function(role) {
                return grouped[role];
            }).map(function(role) {
                const group = grouped[role];

                return '<div class="operasi-recipient-group">' +
                    '<div class="operasi-recipient-group-head">' +
                    '<div>' +
                    '<div class="operasi-recipient-role">' + escapeHtml(group.label) + '</div>' +
                    '<div class="text-muted small">' + formatNumber(group.items.length) + ' penerima</div>' +
                    '</div>' +
                    '<strong>' + formatRupiah(group.pool) + '</strong>' +
                    '</div>' +
                    '<div class="operasi-recipient-list">' +
                    group.items.map(function(item) {
                        return '<div class="operasi-recipient-item">' +
                            '<div class="operasi-recipient-name">' + escapeHtml(item.pegawai_name) + '</div>' +
                            '<div class="text-muted small">' + escapeHtml(item.pegawai_id) + ' / ' +
                            escapeHtml(item.pegawai_position || '-') + ' / ' +
                            escapeHtml(item.allocation_percent || 0) + '% pool</div>' +
                            '<div class="operasi-recipient-total">' + formatRupiah(item.total_received) + '</div>' +
                            '</div>';
                    }).join('') +
                    '</div>' +
                    '</div>';
            }).join('');

            $('#previewRecipientSubtitle').text(formatNumber(recipients.length) + ' penerima dari konfigurasi aktif.');
            $('#previewRecipientsOperasi').html(recipientHtml ||
                '<div class="alert alert-warning mb-0">Belum ada penerima premi pada konfigurasi ini.</div>');
            const previewOk = renderPreviewQuality(data, grouped);

            $('#previewGenerateOperasiWrap').removeClass('d-none');
            $('#previewHintOperasi')
                .removeClass('alert-info alert-danger alert-success alert-warning')
                .addClass(previewOk ? 'alert-success' : 'alert-warning')
                .text(previewOk ?
                    'Preview konfigurasi berhasil dimuat. Periksa pembagian sebelum generate.' :
                    'Preview berhasil dimuat, tetapi konfigurasi penerima belum lengkap.'
                );
            previewReady = previewOk;
            $('#btnSubmitGenerateOperasi').prop('disabled', !previewOk);
        }

        function renderPreviewQuality(data, grouped) {
            const pools = data.pools || {};
            const required = data.jenis_operasi === 'bpjs' ? [
                ['instrumen_20', 'Pegawai Khusus Instrumen 20%', pools.instrumen_kelompok_20],
                ['instrumen_80', 'Pegawai Instrumen 80%', pools.instrumen_kelompok_80],
                ['perawat_anastesi', 'Pegawai Anastesi', pools.perawat_anastesi]
            ] : [
                ['instrumen_20', 'Petugas Instrumen 20%', pools.instrumen_kelompok_20],
                ['instrumen_80', 'Petugas Instrumen 80%', pools.instrumen_kelompok_80],
                ['dokter_anastesi', 'Dokter Anastesi', pools.dokter_anastesi],
                ['perawat_anastesi', 'Perawat Anastesi', pools.perawat_anastesi]
            ];
            const missing = required
                .filter(function(item) {
                    return Number(item[2] || 0) > 0 && !grouped[item[0]];
                })
                .map(function(item) {
                    return item[1];
                });
            const totalSisa = Math.max(
                0,
                Number(data.total_operasi || 0) -
                Number(data.total_dibagikan || 0) -
                Number((pools || {}).premi_bersama || 0)
            );

            if (missing.length) {
                $('#previewQualityOperasi').html(
                    '<div class="operasi-preview-status warning">' +
                    '<div class="operasi-preview-status-title">' +
                    '<i class="mdi mdi-alert-circle-outline"></i>Konfigurasi belum siap generate' +
                    '</div>' +
                    '<div class="operasi-preview-status-text">Kelompok berikut belum punya penerima: ' +
                    escapeHtml(missing.join(', ')) +
                    '. Lengkapi konfigurasi dulu agar generate tidak ditolak.</div>' +
                    '</div>'
                );
                return false;
            }

            $('#previewQualityOperasi').html(
                '<div class="operasi-preview-status success">' +
                '<div class="operasi-preview-status-title">' +
                '<i class="mdi mdi-check-decagram-outline"></i>Preview siap digenerate' +
                '</div>' +
                '<div class="operasi-preview-status-text">Total dibagikan ' + formatRupiah(data.total_dibagikan || 0) +
                ', premi bersama ' + formatRupiah((pools || {}).premi_bersama || 0) +
                ' (termasuk ' + formatRupiah((pools || {}).perawat_anastesi_premi_bersama || 0) +
                ' dari premi bersama anastesi)' +
                ', dan sisa tidak dibagikan ' + formatRupiah(totalSisa) + '.</div>' +
                '</div>'
            );

            return true;
        }

        function detailRoleMeta(role, fallbackLabel) {
            const meta = {
                instrumen_20: {
                    label: 'Pegawai Khusus Instrumen 20%',
                    icon: 'mdi-account-wrench-outline'
                },
                instrumen_80: {
                    label: 'Pegawai Instrumen 80%',
                    icon: 'mdi-account-hard-hat-outline'
                },
                dokter_anastesi: {
                    label: 'Dokter Anastesi',
                    icon: 'mdi-doctor'
                },
                perawat_anastesi: {
                    label: 'Pegawai Anastesi',
                    icon: 'mdi-medical-bag'
                }
            };

            return meta[role] || {
                label: fallbackLabel || role || '-',
                icon: 'mdi-account-outline'
            };
        }

        function orderedRoleGroups(groups) {
            const roleOrder = ['instrumen_20', 'instrumen_80', 'dokter_anastesi', 'perawat_anastesi'];
            const byRole = {};

            (groups || []).forEach(function(group) {
                byRole[group.role] = group;
            });

            const ordered = roleOrder.filter(function(role) {
                return byRole[role];
            }).map(function(role) {
                return byRole[role];
            });

            (groups || []).forEach(function(group) {
                if (roleOrder.indexOf(group.role) === -1) {
                    ordered.push(group);
                }
            });

            return ordered;
        }

        function clampPercent(value) {
            const number = Number(value) || 0;
            return Math.max(0, Math.min(100, number));
        }

        function sharePercent(value, total) {
            const denominator = Number(total) || 0;
            return denominator > 0 ? (Number(value || 0) / denominator) * 100 : 0;
        }

        function percentNumber(value) {
            return formatNumber(Math.round((Number(value) || 0) * 100) / 100);
        }

        function renderDetailLedger(data) {
            const totalOperasi = Number(data.total_operasi || 0);
            const pools = data.pools || {};
            const rows = [
                ['Total Operasi', 'Nominal pendapatan operasi yang menjadi dasar hitung', data.total_operasi, 'mdi-cash-multiple', 'total', 100],
                ['Premi Bersama', 'Dari instrumen ' + formatRupiah(pools.premi_bersama_instrumen || 0) + ' + anastesi ' + formatRupiah(pools.perawat_anastesi_premi_bersama || 0), data.total_premi_bersama, 'mdi-account-group-outline', '', sharePercent(data.total_premi_bersama, totalOperasi)],
                ['Dibagikan ke Penerima', 'Total masuk ke pegawai dan dokter terpilih', data.total_dibagikan, 'mdi-hand-coin-outline', '', sharePercent(data.total_dibagikan, totalOperasi)],
                ['Tidak Dibagikan', 'Sisa di luar premi bersama dan penerima', data.total_tidak_dibagikan, 'mdi-chart-donut', '', sharePercent(data.total_tidak_dibagikan, totalOperasi)]
            ];

            $('#detailLedgerOperasi').html(rows.map(function(row) {
                const share = clampPercent(row[5]);

                return '<div class="operasi-detail-ledger-row ' + escapeHtml(row[4]) + '">' +
                    '<div class="operasi-detail-ledger-icon"><i class="mdi ' + escapeHtml(row[3]) + '"></i></div>' +
                    '<div>' +
                    '<div class="operasi-detail-ledger-title">' + escapeHtml(row[0]) + '</div>' +
                    '<div class="operasi-detail-ledger-note">' + escapeHtml(row[1]) + '</div>' +
                    '</div>' +
                    '<div>' +
                    '<div class="operasi-detail-ledger-value">' + formatRupiah(row[2]) + '</div>' +
                    '<div class="operasi-detail-ledger-share">' + percentNumber(row[5]) + '% dari total</div>' +
                    '</div>' +
                    '<div class="operasi-detail-ledger-track">' +
                    '<span class="operasi-detail-ledger-bar" style="width: ' + share + '%"></span>' +
                    '</div>' +
                    '</div>';
            }).join(''));
        }

        function renderDetailInsights(data) {
            const groups = orderedRoleGroups(data.recipient_groups || []);
            const recipientCount = Number(data.details_count || 0) || groups.reduce(function(total, group) {
                return total + Number(group.recipient_count || (group.items || []).length || 0);
            }, 0);
            const distributedShare = sharePercent(data.total_dibagikan, data.total_operasi);
            const averageReceived = recipientCount ? Number(data.total_dibagikan || 0) / recipientCount : 0;
            const largestGroup = groups.reduce(function(selected, group) {
                return Number(group.total_received || 0) > Number((selected || {}).total_received || 0) ? group : selected;
            }, null);

            const rows = [
                ['Jumlah Penerima', formatNumber(recipientCount) + ' orang', 'Semua role pada hasil generate', 'mdi-account-multiple-outline'],
                ['Rasio Dibagikan', percentNumber(distributedShare) + '%', 'Dari total operasi ke penerima', 'mdi-chart-areaspline'],
                ['Rata-rata Penerima', formatRupiah(averageReceived), 'Total dibagikan dibagi jumlah penerima', 'mdi-calculator-variant-outline'],
                ['Kelompok Terbesar', largestGroup ? (largestGroup.role_label || detailRoleMeta(largestGroup.role).label) : '-', largestGroup ? formatRupiah(largestGroup.total_received || 0) : 'Belum ada penerima', 'mdi-podium']
            ];

            $('#detailInsightOperasi').html(rows.map(function(row) {
                return '<div class="operasi-detail-insight">' +
                    '<div class="operasi-detail-insight-icon"><i class="mdi ' + escapeHtml(row[3]) + '"></i></div>' +
                    '<div class="operasi-detail-insight-label">' + escapeHtml(row[0]) + '</div>' +
                    '<div class="operasi-detail-insight-value">' + escapeHtml(row[1]) + '</div>' +
                    '<div class="operasi-detail-insight-note">' + escapeHtml(row[2]) + '</div>' +
                    '</div>';
            }).join(''));
        }

        function renderDetailRoleSummary(groups, totalOperasi) {
            const orderedGroups = orderedRoleGroups(groups);

            if (!orderedGroups.length) {
                $('#detailRoleSummaryOperasi').html(
                    '<div class="operasi-detail-empty">Belum ada komposisi penerima pada hasil generate ini.</div>'
                );
                return;
            }

            $('#detailRoleSummaryOperasi').html(orderedGroups.map(function(group) {
                const items = group.items || [];
                const meta = detailRoleMeta(group.role, group.role_label);
                const recipientCount = group.recipient_count || items.length;
                const averageReceived = recipientCount ? (Number(group.total_received || 0) / recipientCount) : 0;
                const poolRemainder = Number(group.pool_total || 0) - Number(group.total_received || 0);
                const groupShare = sharePercent(group.total_received, totalOperasi);

                return '<div class="operasi-detail-role-card">' +
                    '<div class="operasi-detail-role-top">' +
                    '<div class="operasi-detail-role-icon"><i class="mdi ' + escapeHtml(meta.icon) + '"></i></div>' +
                    '<div class="min-w-0">' +
                    '<div class="operasi-detail-role-title">' + escapeHtml(group.role_label || meta.label) + '</div>' +
                    '<div class="operasi-detail-role-count">' + formatNumber(recipientCount) + ' penerima</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="operasi-detail-role-total">' + formatRupiah(group.total_received || 0) + '</div>' +
                    '<div class="operasi-detail-role-pool">Pool tersedia ' + formatRupiah(group.pool_total || 0) + '</div>' +
                    '<div class="operasi-detail-role-meta">' +
                    '<span>Rata-rata<br><strong>' + formatRupiah(averageReceived) + '</strong></span>' +
                    '<span>Porsi total<br><strong>' + percentNumber(groupShare) + '%</strong></span>' +
                    '<span>Sisa pool<br><strong>' + formatRupiah(poolRemainder) + '</strong></span>' +
                    '<span>Alokasi<br><strong>' + percentNumber(items[0] ? items[0].allocation_percent : 0) + '%</strong></span>' +
                    '</div>' +
                    '</div>';
            }).join(''));
        }

        function renderDetailPercentSnapshot(percentages) {
            const p = percentages || {};
            const rows = [
                ['Instrumen', percentText(p.instrumen_percent), 'Dari total operasi'],
                ['Premi Bersama', percentText(p.instrumen_premi_bersama_percent), 'Dari pool instrumen'],
                ['Petugas Instrumen', percentText(p.instrumen_petugas_percent), 'Dari pool instrumen'],
                ['Kelompok 20', percentText(p.instrumen_petugas_kelompok_20_percent), 'Dari pool petugas instrumen'],
                ['Kelompok 80', percentText(p.instrumen_petugas_kelompok_80_percent), 'Dari pool petugas instrumen'],
                ['Dokter Anastesi', percentText(p.dokter_anastesi_percent), 'Dari total operasi'],
                ['Bagian Perawat Anastesi', percentText(p.perawat_anastesi_percent), 'Dari pool anastesi'],
                ['Petugas Anastesi', percentText(p.perawat_anastesi_petugas_percent), 'Dari bagian perawat anastesi'],
                ['Perawat ke Premi Bersama', percentText(p.perawat_anastesi_premi_bersama_percent), 'Dari bagian perawat anastesi']
            ];

            $('#detailPercentSnapshotOperasi').html(rows.map(function(row) {
                const percent = clampPercent(String(row[1]).replace(',', '.').replace('%', ''));

                return '<div class="operasi-detail-percent-card">' +
                    '<div class="operasi-detail-percent-label">' + escapeHtml(row[0]) + '</div>' +
                    '<div class="operasi-detail-percent-value">' + escapeHtml(row[1]) + '</div>' +
                    '<div class="operasi-detail-percent-note">' + escapeHtml(row[2]) + '</div>' +
                    '<div class="operasi-detail-percent-track">' +
                    '<span class="operasi-detail-percent-bar" style="width: ' + percent + '%"></span>' +
                    '</div>' +
                    '</div>';
            }).join(''));
        }

        function renderDetailSteps(data) {
            const pools = data.pools || {};
            const p = ((data.config_snapshot || {}).percentages) || {};

            if (data.jenis_operasi === 'bpjs') {
                return [
                    [
                        'Hitung grand total BPJS',
                        formatNumber(data.jumlah_pasien || 0) + ' PX x ' +
                        formatRupiah(data.nominal_pengali || 0) + ' = ' +
                        formatRupiah(data.total_operasi || 0) + '.'
                    ],
                    [
                        'Bagi anastesi',
                        percentText(p.perawat_anastesi_petugas_percent) +
                        ' dari grand total menjadi ' + formatRupiah(pools.perawat_anastesi || 0) +
                        ' untuk pegawai anastesi, dan ' +
                        percentText(p.perawat_anastesi_premi_bersama_percent) +
                        ' menjadi ' + formatRupiah(pools.perawat_anastesi_premi_bersama || 0) +
                        ' untuk premi bersama.'
                    ],
                    [
                        'Bentuk pool instrumen',
                        percentText(p.instrumen_percent) + ' dari grand total menjadi pool instrumen ' +
                        formatRupiah(pools.instrumen || 0) + '.'
                    ],
                    [
                        'Pecah petugas instrumen',
                        'Pool instrumen dibagi ' +
                        percentText(p.instrumen_petugas_kelompok_80_percent) + ' sebesar ' +
                        formatRupiah(pools.instrumen_kelompok_80 || 0) +
                        ' untuk pegawai instrumen dan ' +
                        percentText(p.instrumen_petugas_kelompok_20_percent) + ' sebesar ' +
                        formatRupiah(pools.instrumen_kelompok_20 || 0) +
                        ' untuk pegawai khusus.'
                    ],
                    [
                        'Premi bersama instrumen',
                        percentText(p.instrumen_premi_bersama_percent) +
                        ' dari grand total masuk premi bersama sebesar ' +
                        formatRupiah(pools.premi_bersama_instrumen || 0) + '.'
                    ],
                    [
                        'Hasil akhir',
                        'Total dibagikan ke penerima adalah ' + formatRupiah(data.total_dibagikan || 0) +
                        ', premi bersama total ' + formatRupiah(data.total_premi_bersama || 0) + '.'
                    ]
                ].map(function(row) {
                    return '<div class="operasi-detail-step">' +
                        '<div>' +
                        '<div class="operasi-detail-step-title">' + escapeHtml(row[0]) + '</div>' +
                        '<div class="operasi-detail-step-text">' + escapeHtml(row[1]) + '</div>' +
                        '</div>' +
                        '</div>';
                }).join('');
            }

            return [
                [
                    'Mulai dari total operasi',
                    'Total operasi periode ini adalah ' + formatRupiah(data.total_operasi || 0) + '.'
                ],
                [
                    'Hitung bagian instrumen',
                    percentText(p.instrumen_percent) + ' dari total operasi menjadi pool instrumen ' +
                    formatRupiah(pools.instrumen || 0) + '. Dari pool ini, ' +
                    formatRupiah(pools.premi_bersama_instrumen || 0) + ' masuk premi bersama dan ' +
                    formatRupiah(pools.instrumen_petugas || 0) + ' untuk petugas instrumen.'
                ],
                [
                    'Bagi petugas instrumen',
                    'Pool petugas instrumen dibagi menjadi kelompok 20% sebesar ' +
                    formatRupiah(pools.instrumen_kelompok_20 || 0) + ' dan kelompok 80% sebesar ' +
                    formatRupiah(pools.instrumen_kelompok_80 || 0) + '.'
                ],
                [
                    'Hitung bagian anastesi',
                    percentText(p.dokter_anastesi_percent) + ' dari total operasi menjadi pool anastesi ' +
                    formatRupiah(pools.anastesi || 0) + '. Dokter anastesi menerima ' +
                    formatRupiah(pools.dokter_anastesi || 0) + '.'
                ],
                [
                    'Pecah bagian perawat anastesi',
                    'Bagian perawat anastesi sebesar ' + formatRupiah(pools.perawat_anastesi_pool || 0) +
                    ' dipecah menjadi ' + formatRupiah(pools.perawat_anastesi || 0) +
                    ' untuk petugas anastesi dan ' +
                    formatRupiah(pools.perawat_anastesi_premi_bersama || 0) + ' masuk premi bersama.'
                ],
                [
                    'Hasil akhir',
                    'Total dibagikan ke penerima adalah ' + formatRupiah(data.total_dibagikan || 0) +
                    ', premi bersama total ' + formatRupiah(data.total_premi_bersama || 0) +
                    ', dan sisa tidak dibagikan ' + formatRupiah(data.total_tidak_dibagikan || 0) + '.'
                ]
            ].map(function(row) {
                return '<div class="operasi-detail-step">' +
                    '<div>' +
                    '<div class="operasi-detail-step-title">' + escapeHtml(row[0]) + '</div>' +
                    '<div class="operasi-detail-step-text">' + escapeHtml(row[1]) + '</div>' +
                    '</div>' +
                    '</div>';
            }).join('');
        }

        function renderDetailSimpleRows(data) {
            const pools = data.pools || {};
            const rows = data.jenis_operasi === 'bpjs' ? [
                ['Jumlah PX', 'Input jumlah PX BPJS saat generate', data.jumlah_pasien, false, 'number'],
                ['Nominal', 'Nominal dikalikan jumlah PX', data.nominal_pengali, false],
                ['Grand Total BPJS', 'Jumlah PX x nominal', data.total_operasi, true],
                ['Premi Bersama Total', 'Premi bersama instrumen + premi bersama anastesi', data.total_premi_bersama, true],
                ['Premi Bersama Instrumen', '20% dari grand total BPJS', pools.premi_bersama_instrumen, false],
                ['Premi Bersama Anastesi', '20% dari grand total BPJS', pools.perawat_anastesi_premi_bersama, false],
                ['Pegawai Khusus Instrumen 20%', 'Dibagi rata ke pegawai khusus instrumen', pools.instrumen_kelompok_20, false],
                ['Pegawai Instrumen 80%', 'Dibagi rata ke pegawai instrumen', pools.instrumen_kelompok_80, false],
                ['Pegawai Anastesi', '80% grand total dibagi rata ke pegawai anastesi', pools.perawat_anastesi, false],
                ['Total Dibagikan ke Penerima', 'Jumlah semua penerima yang dipilih', data.total_dibagikan, true],
                ['Sisa Tidak Dibagikan', 'Di luar premi bersama dan penerima', data.total_tidak_dibagikan, false]
            ] : [
                ['Total Operasi', 'Dasar semua perhitungan', data.total_operasi, true],
                ['Premi Bersama Total', 'Premi bersama instrumen + premi bersama dari bagian perawat anastesi', data.total_premi_bersama, true],
                ['Premi Bersama dari Instrumen', 'Bagian bersama dari pool instrumen', pools.premi_bersama_instrumen, false],
                ['Premi Bersama dari Perawat Anastesi', 'Bagian 20% default dari bagian perawat anastesi', pools.perawat_anastesi_premi_bersama, false],
                ['Petugas Instrumen 20%', 'Dibagi rata ke penerima kelompok instrumen 20%', pools.instrumen_kelompok_20, false],
                ['Petugas Instrumen 80%', 'Dibagi rata ke penerima kelompok instrumen 80%', pools.instrumen_kelompok_80, false],
                ['Dokter Anastesi', 'Sisa pool anastesi setelah bagian perawat', pools.dokter_anastesi, false],
                ['Petugas Anastesi', 'Bagian 80% default dari bagian perawat anastesi', pools.perawat_anastesi, false],
                ['Total Dibagikan ke Penerima', 'Jumlah semua penerima yang dipilih', data.total_dibagikan, true],
                ['Sisa Tidak Dibagikan', 'Di luar premi bersama dan penerima', data.total_tidak_dibagikan, false]
            ];

            return rows.map(function(row) {
                return '<div class="operasi-detail-simple-row ' + (row[3] ? 'highlight' : '') + '">' +
                    '<div>' +
                    '<div class="operasi-detail-simple-label">' + escapeHtml(row[0]) + '</div>' +
                    '<div class="operasi-detail-simple-note">' + escapeHtml(row[1]) + '</div>' +
                    '</div>' +
                    '<div class="operasi-detail-simple-value">' +
                    (row[4] === 'number' ? formatNumber(row[2] || 0) : formatRupiah(row[2] || 0)) +
                    '</div>' +
                    '</div>';
            }).join('');
        }

        function roleGroupHtml(groups) {
            return orderedRoleGroups(groups).map(function(group) {
                const items = group.items || [];
                const meta = detailRoleMeta(group.role, group.role_label);
                const recipientCount = group.recipient_count || items.length;

                return '<div class="operasi-detail-recipient-group">' +
                    '<div class="operasi-detail-recipient-head">' +
                    '<div class="operasi-detail-role-top">' +
                    '<div class="operasi-detail-role-icon"><i class="mdi ' + escapeHtml(meta.icon) + '"></i></div>' +
                    '<div class="min-w-0">' +
                    '<div class="operasi-recipient-role">' + escapeHtml(group.role_label || meta.label) + '</div>' +
                    '<div class="text-muted small">' + formatNumber(recipientCount) + ' penerima</div>' +
                    '</div>' +
                    '</div>' +
                    '<div class="text-end">' +
                    '<strong>' + formatRupiah(group.total_received || 0) + '</strong>' +
                    '</div>' +
                    '</div>' +
                    '<div class="operasi-detail-recipient-members">' +
                    items.map(function(item) {
                        return '<div class="operasi-detail-recipient-member">' +
                            '<div>' +
                            '<div class="operasi-detail-recipient-name">' + escapeHtml(item.pegawai_name) + '</div>' +
                            '<div class="operasi-detail-recipient-position">' +
                            escapeHtml(item.pegawai_position || '-') + ' / ' + escapeHtml(item.pegawai_id || '-') +
                            '</div>' +
                            '</div>' +
                            '<div class="operasi-detail-recipient-total">' + formatRupiah(item.total_received || 0) + '</div>' +
                            '</div>';
                    }).join('') +
                    '</div></div>';
            }).join('');
        }

        function renderDetail(data) {
            const statusHtml = data.is_locked ?
                '<i class="mdi mdi-lock me-1"></i>Terkunci' :
                '<i class="mdi mdi-lock-open-variant-outline me-1"></i>Terbuka';

            $('#detailOperasiEyebrow').text('Generate Operasi ' + (data.jenis_operasi_label || '-'));
            $('#detailOperasiTitle').text((data.periode || '-') + ' / ' + (data.jenis_operasi_label || '-'));
            $('#detailOperasiMeta').text(
                'Generated oleh ' + (data.generate_by_name || '-') + ' pada ' + (data.generated_at || '-')
            );
            $('#detailOperasiStatus').html(statusHtml);
            $('#detailTotalOperasi').text(formatRupiah(data.total_operasi));
            $('#detailTotalDibagikan').text(formatRupiah(data.total_dibagikan));
            $('#detailTotalBersama').text(formatRupiah(data.total_premi_bersama));
            $('#detailTotalSisa').text(formatRupiah(data.total_tidak_dibagikan));
            $('#detailFormulaFlow').html(renderDetailSteps(data));
            $('#detailPoolOperasi').html(renderDetailSimpleRows(data));

            const auditRows = [
                ['Generate oleh', data.generate_by_name || '-', 'mdi-account-check-outline'],
                ['Waktu generate/update', data.generated_at || '-', 'mdi-clock-outline'],
                ['Status kunci', data.is_locked ? 'Terkunci' : 'Terbuka', data.is_locked ? 'mdi-lock-outline' : 'mdi-lock-open-variant-outline'],
                ['Dikunci oleh', data.locked_by_name || '-', 'mdi-account-lock-outline'],
                ['Waktu kunci', data.locked_at || '-', 'mdi-calendar-lock-outline'],
                ['Snapshot konfigurasi', 'Tersimpan pada hasil generate', 'mdi-content-save-check-outline']
            ];

            if (data.jenis_operasi === 'bpjs') {
                auditRows.splice(2, 0,
                    ['Jumlah PX BPJS', formatNumber(data.jumlah_pasien || 0), 'mdi-account-injury-outline'],
                    ['Nominal', formatRupiah(data.nominal_pengali || 0), 'mdi-cash-multiple']
                );
            }

            $('#detailAuditOperasi').html(auditRows.map(function(item) {
                return '<div class="operasi-detail-audit-row">' +
                    '<div class="operasi-detail-audit-icon"><i class="mdi ' + escapeHtml(item[2]) + '"></i></div>' +
                    '<div>' +
                    '<div class="operasi-detail-audit-label">' + escapeHtml(item[0]) + '</div>' +
                    '<div class="operasi-detail-audit-value">' + escapeHtml(item[1]) + '</div>' +
                    '</div>' +
                    '</div>';
            }).join(''));

            $('#detailRecipientSubtitle').text(
                formatNumber(data.details_count || 0) + ' penerima / total dibagikan ' +
                formatRupiah(data.total_dibagikan || 0)
            );
            $('#detailRecipientsOperasi').html(
                roleGroupHtml(data.recipient_groups || []) ||
                '<div class="operasi-detail-empty">Belum ada detail penerima pada hasil generate ini.</div>'
            );
        }

        function openDetailModal(id) {
            const template =
                "{{ route("backOffice.keuangan.hitungPremi.generateOperasi.detail", ["id" => "__ID__"]) }}";

            $('#detailOperasiLoading').removeClass('d-none');
            $('#detailOperasiContent').addClass('d-none');
            detailModal.show();

            $.ajax({
                url: template.replace('__ID__', id),
                success: function(response) {
                    renderDetail(response.data || {});
                    $('#detailOperasiLoading').addClass('d-none');
                    $('#detailOperasiContent').removeClass('d-none');
                },
                error: function(xhr) {
                    detailModal.hide();
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                }
            });
        }

        function loadPreview() {
            const button = $('#btnPreviewOperasi');
            const originalHtml = button.html();
            const payload = generatePayload();

            if (payload.jenis_operasi === 'bpjs' && (payload.jumlah_pasien === '' || payload.nominal_pengali === '')) {
                $('#previewHintOperasi').removeClass('alert-info alert-success').addClass('alert-danger')
                    .text('Jumlah PX dan nominal BPJS wajib diisi.');
                return;
            }

            if (payload.jenis_operasi !== 'bpjs' && payload.total_operasi === '') {
                $('#previewHintOperasi').removeClass('alert-info alert-success').addClass('alert-danger')
                    .text('Total nominal pendapatan operasi wajib diisi.');
                return;
            }

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateOperasi.preview") }}",
                data: payload,
                beforeSend: function() {
                    previewReady = false;
                    $('#btnSubmitGenerateOperasi').prop('disabled', true);
                    button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Memuat...');
                },
                success: function(response) {
                    renderPreview(response.data || {});
                },
                error: function(xhr) {
                    $('#previewHintOperasi').removeClass('alert-info alert-success').addClass('alert-danger')
                        .html(errorMessage(xhr));
                },
                complete: function() {
                    button.prop('disabled', false).html(originalHtml);
                }
            });
        }

        function openConfigModal() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateOperasi.config") }}",
                data: { jenis_operasi: activeType },
                success: function(response) {
                    const data = response.data || {};
                    const p = data.percentages || {};
                    const r = data.recipients || {};
                    const isBpjs = activeType === 'bpjs';

                    $('#modalConfigOperasiLabel').text('Konfigurasi Premi Operasi ' + typeConfig[activeType]);
                    $('#configBpjsFlowHint').toggleClass('d-none', !isBpjs);
                    $('#jenisConfigOperasi').val(activeType);
                    $('#configInstrumenPercent').val(p.instrumen_percent);
                    $('#configPremiBersamaPercent').val(p.instrumen_premi_bersama_percent);
                    $('#configInstrumenPetugasPercent').val(p.instrumen_petugas_percent);
                    $('#configInstrumen20Percent').val(p.instrumen_petugas_kelompok_20_percent);
                    $('#configInstrumen80Percent').val(p.instrumen_petugas_kelompok_80_percent);
                    $('#configDokterAnastesiPercent').val(p.dokter_anastesi_percent);
                    $('#configPerawatAnastesiPercent').val(p.perawat_anastesi_percent);
                    $('#configPerawatAnastesiPetugasPercent').val(p.perawat_anastesi_petugas_percent);
                    $('#configPerawatAnastesiBersamaPercent').val(p.perawat_anastesi_premi_bersama_percent);
                    fillSelect($('#configInstrumen20Recipients'), r.instrumen_20);
                    fillSelect($('#configInstrumen80Recipients'), r.instrumen_80);
                    fillSelect($('#configDokterAnastesiRecipients'), isBpjs ? [] : r.dokter_anastesi);
                    fillSelect($('#configPerawatAnastesiRecipients'), r.perawat_anastesi);
                    applyConfigMode();
                    updateRecipientCounters();
                    updateConfigOverview();
                    configModal.show();
                },
                error: function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                }
            });
        }

        function configPayload() {
            const isBpjs = $('#jenisConfigOperasi').val() === 'bpjs';

            return {
                jenis_operasi: $('#jenisConfigOperasi').val(),
                instrumen_percent: $('#configInstrumenPercent').val(),
                instrumen_premi_bersama_percent: $('#configPremiBersamaPercent').val(),
                instrumen_petugas_percent: $('#configInstrumenPetugasPercent').val(),
                instrumen_petugas_kelompok_20_percent: $('#configInstrumen20Percent').val(),
                instrumen_petugas_kelompok_80_percent: $('#configInstrumen80Percent').val(),
                dokter_anastesi_percent: $('#configDokterAnastesiPercent').val(),
                perawat_anastesi_percent: $('#configPerawatAnastesiPercent').val(),
                perawat_anastesi_petugas_percent: $('#configPerawatAnastesiPetugasPercent').val(),
                perawat_anastesi_premi_bersama_percent: $('#configPerawatAnastesiBersamaPercent').val(),
                recipients: {
                    instrumen_20: $('#configInstrumen20Recipients').val() || [],
                    instrumen_80: $('#configInstrumen80Recipients').val() || [],
                    dokter_anastesi: isBpjs ? [] : ($('#configDokterAnastesiRecipients').val() || []),
                    perawat_anastesi: $('#configPerawatAnastesiRecipients').val() || []
                }
            };
        }

        $('#periodeOperasi').on('change', function() {
            updateActiveContext();
            refreshAll();
        });
        $('#btnPrevPeriodOperasi').on('click', () => movePeriod(-1));
        $('#btnNextPeriodOperasi').on('click', () => movePeriod(1));
        $('#btnCurrentPeriodOperasi').on('click', function() {
            const now = new Date();
            setPeriodFromDate(new Date(now.getFullYear(), now.getMonth(), 1));
        });
        $('#btnRefreshOperasi').on('click', refreshAll);
        $('#btnOpenGenerateOperasi').on('click', openGenerateModal);
        $('#btnOpenConfigOperasi').on('click', openConfigModal);
        $('#btnCommandGenerateOperasi').on('click', openGenerateModal);
        $('#btnCommandConfigOperasi').on('click', openConfigModal);
        $('#btnPreviewOperasi').on('click', loadPreview);
        $('#searchGenerateOperasi').on('input', function() {
            table.search(this.value).draw();
        });
        $('.operasi-type-tab').on('click', function() {
            setActiveType($(this).data('type'));
            refreshAll();
        });
        $('#totalGenerateOperasi').on('input', function() {
            formatInput(this);
            resetGeneratePreview('Total nominal berubah. Tampilkan preview ulang sebelum generate.');
        });
        $('#jumlahPasienGenerateOperasi, #nominalPengaliGenerateOperasi').on('input', function() {
            formatInput(this);
            updateGenerateInputMode();
            resetGeneratePreview('Input BPJS berubah. Tampilkan preview ulang sebelum generate.');
        });

        $('.operasi-pegawai-select, .operasi-dokter-select').on('change', updateRecipientCounters);
        $('.operasi-pegawai-select, .operasi-dokter-select').on('change', updateConfigOverview);
        $('#formConfigOperasi input[type="number"]').on('input', updateConfigOverview);

        $('#formConfigOperasi').on('submit', function(event) {
            event.preventDefault();
            const button = $('#btnSubmitConfigOperasi');
            const originalHtml = button.html();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateOperasi.updateConfig") }}",
                method: 'PUT',
                data: configPayload(),
                beforeSend: function() {
                    button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');
                },
                success: function(response) {
                    configModal.hide();
                    Swal.fire('Berhasil', response.message, 'success');
                },
                error: function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                },
                complete: function() {
                    button.prop('disabled', false).html(originalHtml);
                }
            });
        });

        $('#formGenerateOperasi').on('submit', function(event) {
            event.preventDefault();

            if (!previewReady) {
                loadPreview();
                return;
            }

            const button = $('#btnSubmitGenerateOperasi');
            const originalHtml = button.html();
            const payload = Object.assign({
                periode: $('#periodeGenerateOperasi').val()
            }, generatePayload());

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateOperasi.store") }}",
                method: 'POST',
                data: payload,
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

        function changeLock(id, action) {
            const isLock = action === 'lock';
            const template = isLock ?
                "{{ route("backOffice.keuangan.hitungPremi.generateOperasi.lock", ["id" => "__ID__"]) }}" :
                "{{ route("backOffice.keuangan.hitungPremi.generateOperasi.unlock", ["id" => "__ID__"]) }}";

            Swal.fire({
                icon: isLock ? 'warning' : 'question',
                title: isLock ? 'Kunci data operasi?' : 'Buka kunci data operasi?',
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
                "{{ route("backOffice.keuangan.hitungPremi.generateOperasi.delete", ["id" => "__ID__"]) }}";

            Swal.fire({
                icon: 'warning',
                title: 'Hapus data operasi?',
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

        $('#tableGenerateOperasi').on('click', '.btn-lock-operasi', function() {
            changeLock($(this).data('id'), 'lock');
        });

        $('#tableGenerateOperasi').on('click', '.btn-detail-operasi', function() {
            openDetailModal($(this).data('id'));
        });

        $('#tableGenerateOperasi').on('click', '.btn-unlock-operasi', function() {
            changeLock($(this).data('id'), 'unlock');
        });

        $('#tableGenerateOperasi').on('click', '.btn-delete-operasi', function() {
            deleteResult($(this).data('id'));
        });

        refreshAll();
    });
</script>
