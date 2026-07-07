<script>
    $(function() {
        const routes = {
            table: "{{ route("backOffice.keuangan.hitungPremi.generatePremiBersama.table") }}",
            summary: "{{ route("backOffice.keuangan.hitungPremi.generatePremiBersama.summary") }}",
            store: "{{ route("backOffice.keuangan.hitungPremi.generatePremiBersama.store") }}",
            config: "{{ route("backOffice.keuangan.hitungPremi.generatePremiBersama.config") }}",
            updateConfig: "{{ route("backOffice.keuangan.hitungPremi.generatePremiBersama.updateConfig") }}",
            mappingOptions: "{{ route("backOffice.keuangan.hitungPremi.generatePremiBersama.mappingPremiOptions") }}",
            actionOptions: "{{ route("backOffice.keuangan.hitungPremi.generatePremiBersama.mappingActionOptions", ":id") }}",
            plotingOptions: "{{ route("backOffice.keuangan.hitungPremi.generatePremiBersama.plotingOptions") }}",
            dokterOptions: "{{ route("backOffice.keuangan.hitungPremi.generatePremiBersama.dokterOptions") }}",
            detail: "{{ route("backOffice.keuangan.hitungPremi.generatePremiBersama.detail", ":id") }}",
            lock: "{{ route("backOffice.keuangan.hitungPremi.generatePremiBersama.lock", ":id") }}",
            unlock: "{{ route("backOffice.keuangan.hitungPremi.generatePremiBersama.unlock", ":id") }}",
        };
        const csrf = $('meta[name="csrf-token"]').attr('content');
        const modalConfig = new bootstrap.Modal(document.getElementById('modalConfigPremiBersama'));
        const modalDetail = new bootstrap.Modal(document.getElementById('modalDetailPremiBersama'));
        let activeType = 'umum';
        let currentSummary = null;
        let configData = null;
        let mappingOptions = [];
        let plotingOptions = [];
        let actionOptions = [];
        let sourcePatternOptions = [];
        let doctorOptions = [];
        let previewActionRows = [];
        let previewDistributionRows = [];
        let detailActionRows = [];
        let detailDistributionRows = [];
        let detailRawatRows = [];
        let detailData = null;
        let selectedDetailMappingId = '';

        function routeWithParam(route, id) {
            return route.replace(':id', id);
        }

        function formatNumber(value, decimals = 0) {
            const number = Number(value || 0);
            return number.toLocaleString('id-ID', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        }

        function formatRupiah(value) {
            return 'Rp ' + formatNumber(value);
        }

        function percentOf(value, total) {
            const divisor = Number(total || 0);

            if (divisor <= 0) {
                return 0;
            }

            return (Number(value || 0) / divisor) * 100;
        }

        function progressWidth(value) {
            return Math.max(0, Math.min(100, Number(value || 0))).toFixed(2);
        }

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function normalizeText(value) {
            return String(value ?? '').toLowerCase().trim();
        }

        function notifySuccess(message) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: message,
                    timer: 1700,
                    showConfirmButton: false
                });
                return;
            }

            alert(message);
        }

        function notifyError(xhr, fallback = 'Terjadi kesalahan.') {
            const response = xhr.responseJSON || {};
            const errors = response.errors || {};
            const firstError = Object.values(errors).flat()[0];
            const message = firstError || response.message || fallback;

            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Belum bisa diproses',
                    text: message
                });
                return;
            }

            alert(message);
        }

        function confirmAction(title, text, callback) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'question',
                    title,
                    text,
                    showCancelButton: true,
                    confirmButtonText: 'Ya, lanjut',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        callback();
                    }
                });
                return;
            }

            if (confirm(text)) {
                callback();
            }
        }

        function setConfigLoading(isLoading, title = 'Menyiapkan konfigurasi...', text = 'Mohon tunggu sebentar.') {
            $('#configPremiBersamaLoadingTitle').text(title);
            $('#configPremiBersamaLoadingText').text(text);
            $('#configPremiBersamaLoading').toggleClass('d-none', !isLoading);
            $('#modalConfigPremiBersama .pb-config-body').toggleClass('is-loading', isLoading);
            $('#btnSaveConfigPremiBersama, #btnAddSourceMapping').prop('disabled', isLoading);
        }

        function optionHtml(item, selectedValue = null) {
            const selected = String(item.id) === String(selectedValue) ? 'selected' : '';
            return `<option value="${escapeHtml(item.id)}" ${selected}>${escapeHtml(item.text || item.label || item.jenis || item.ploting)}</option>`;
        }

        function fillSelect(selector, items, selected = null, placeholder = 'Pilih data') {
            const select = $(selector);
            select.empty().append(`<option value="">${placeholder}</option>`);
            items.forEach((item) => select.append(optionHtml(item, selected)));
        }

        function fillMultiSelect(selector, items, selectedValues = []) {
            const selectedSet = new Set((selectedValues || []).map((value) => String(value)));
            const select = $(selector);
            select.empty();

            items.forEach((item) => {
                const selected = selectedSet.has(String(item.id)) ? 'selected' : '';
                select.append(`<option value="${escapeHtml(item.id)}" ${selected}>${escapeHtml(item.text || item.label || item.jenis)}</option>`);
            });

            if (select.hasClass('select2-hidden-accessible')) {
                select.trigger('change.select2');
            }
        }

        function infoPill(icon, label, value) {
            return `
                <div class="pb-info-pill">
                    <i class="mdi ${escapeHtml(icon)}"></i>
                    <span>${escapeHtml(label)}</span>
                    <strong>${escapeHtml(value)}</strong>
                </div>
            `;
        }

        function detailKey(detail, index) {
            return String(detail.id ? 'detail-' + detail.id : (detail.kode_jenis_tindakan || 'mapping') + '-' + index);
        }

        function pelaksanaLabel(row) {
            return [
                row.nm_dokter ? `Dr: ${row.nm_dokter}` : '',
                row.nama_petugas ? `Pr: ${row.nama_petugas}` : '',
                row.route_label || ''
            ].filter(Boolean).join(' / ') || '-';
        }

        function mergeActionOptions(rows) {
            const map = new Map();

            (rows || []).forEach((item) => {
                if (!map.has(String(item.id))) {
                    map.set(String(item.id), item);
                }
            });

            return Array.from(map.values()).sort((a, b) => String(a.jenis || a.text || '')
                .localeCompare(String(b.jenis || b.text || '')));
        }

        function uniqueValues(values) {
            return [...new Set((values || []).filter(Boolean).map((value) => String(value)))];
        }

        function sourcePeriodForBpjs(periode, mode) {
            if (!periode) {
                return '-';
            }

            if (mode === 'current') {
                return periode;
            }

            const parts = String(periode).split('-').map(Number);
            const date = new Date(parts[0], (parts[1] || 1) - 2, 1);

            return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
        }

        function selectedPremiIds() {
            return uniqueValues([
                $('#configMappingUmum').val() || configData?.jnsPremi_umum_id,
                $('#configMappingBpjs').val() || configData?.jnsPremi_bpjs_id
            ]);
        }

        function initSelect2(selector, options) {
            if (!$.fn.select2) {
                return;
            }

            $(selector).each(function() {
                const select = $(this);

                if (select.hasClass('select2-hidden-accessible')) {
                    select.select2('destroy');
                }

                select.select2({
                    dropdownParent: $('#modalConfigPremiBersama'),
                    width: '100%',
                    ...options
                });
            });
        }

        function initConfigSelect2() {
            initSelect2('#configMappingUmum', {
                placeholder: 'Pilih mapping premi UMUM',
                allowClear: false
            });
            initSelect2('#configMappingBpjs', {
                placeholder: 'Pilih mapping premi BPJS',
                allowClear: true
            });
            initSelect2('#configBpjsSourceMode', {
                placeholder: 'Pilih sumber data BPJS',
                allowClear: false
            });
            initSelect2('#configUgdPloting, #configVkPloting, #configKamarPloting, #configBhpPloting', {
                placeholder: 'Pilih plotting',
                allowClear: true
            });
            initSelect2('#configIncludedActions', {
                placeholder: 'Pilih tindakan karcis BPJS',
                closeOnSelect: false
            });
            initSelect2('#configDoctorActions', {
                placeholder: 'Pilih tindakan filter dokter',
                closeOnSelect: false
            });

            if ($.fn.select2) {
                const doctorSelect = $('#configDoctorCodes');

                if (doctorSelect.hasClass('select2-hidden-accessible')) {
                    doctorSelect.select2('destroy');
                }

                doctorSelect.select2({
                    dropdownParent: $('#modalConfigPremiBersama'),
                    width: '100%',
                    placeholder: 'Cari dan pilih lebih dari satu dokter',
                    closeOnSelect: false,
                    ajax: {
                        url: routes.dokterOptions,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                q: params.term || ''
                            };
                        },
                        processResults: function(response) {
                            return {
                                results: (response.data || []).map((item) => ({
                                    id: item.id || item.kd_dokter,
                                    text: item.text || [item.kd_dokter, item.nm_dokter].filter(Boolean).join(' - ')
                                }))
                            };
                        }
                    }
                });
            }
        }

        function findOption(items, value) {
            return (items || []).find((item) => String(item.id) === String(value)) || null;
        }

        function selectedValues(selector) {
            return $(selector).val() || [];
        }

        function updateConfigSummary() {
            const periode = $('#periodePremiBersama').val() || '-';
            const typeLabel = activeType === 'bpjs' ? 'BPJS' : 'UMUM';
            const generatorTotal = Number(currentSummary?.total_generator_sumber || 0);
            const rawatTotal = Number(currentSummary?.total_tindakan_rawat || 0);
            const mapping = findOption(mappingOptions, $('#configMappingUmum').val());
            const bpjsSourceMode = $('#configBpjsSourceMode').val() || configData?.bpjs_source_mode || 'previous';
            const bpjsSourcePeriod = sourcePeriodForBpjs(periode, bpjsSourceMode);
            const totalActions = actionOptions.length || Number(mapping?.jumlah_tindakan || 0);
            const includedCount = selectedValues('#configIncludedActions').length;
            const doctorCount = selectedValues('#configDoctorCodes').length;
            const doctorActionCount = selectedValues('#configDoctorActions').length;
            const sourceMappings = collectSourceMappings();
            const uniqueSourceCount = new Set(sourceMappings.map((row) => row.source_pattern)).size;
            const uniqueActionCount = new Set(sourceMappings.map((row) => String(row.jnsTindakan_id))).size;
            const plotingCount = [
                '#configUgdPloting',
                '#configVkPloting',
                '#configKamarPloting',
                '#configBhpPloting'
            ].filter((selector) => Boolean($(selector).val())).length;

            $('#configHeaderSubtitle').text(`Periode ${periode} / ${typeLabel} - BPJS sumber ${bpjsSourcePeriod}.`);
            $('#configSummaryTotalValue').text(formatRupiah(currentSummary?.grand_total || 0));
            $('#configSummaryTotalFoot').text(
                currentSummary ?
                `${formatRupiah(generatorTotal)} generator + ${formatRupiah(rawatTotal)} tindakan` :
                'Preview periode aktif'
            );

            $('#configSummaryMappingValue').text(mapping ? (mapping.kode || 'UMUM') : '-');
            $('#configSummaryMappingFoot').text(
                mapping ?
                `${mapping.jenis || mapping.text || '-'} / ${formatNumber(mapping.jumlah_pegawai || 0)} pegawai` :
                'Belum dipilih'
            );
            $('#configMappingUmumNote').text(
                mapping ?
                `${formatNumber(mapping.jumlah_tindakan || totalActions)} tindakan / ${formatNumber(mapping.jumlah_pegawai || 0)} pegawai penerima.` :
                'Pilih mapping premi UMUM.'
            );

            $('#configSummaryActionValue').text(includedCount > 0 ? `${formatNumber(includedCount)} karcis` : '0');
            $('#configSummaryActionFoot').text(
                includedCount > 0 ?
                `Masuk UMUM dari BPJS ${bpjsSourcePeriod}` :
                `${formatNumber(totalActions)} tindakan tersedia dari mapping aktif`
            );
            $('#configBpjsSourceModeNote').text(
                bpjsSourceMode === 'current' ?
                `Data BPJS dan karcis BPJS memakai periode generate ${periode}.` :
                `Data BPJS dan karcis BPJS memakai bulan sebelumnya: ${bpjsSourcePeriod}.`
            );

            if (doctorCount > 0 && doctorActionCount > 0) {
                $('#configSummaryDoctorValue').text(`${formatNumber(doctorCount)} dokter`);
                $('#configSummaryDoctorFoot').text(`${formatNumber(doctorActionCount)} tindakan difilter`);
            } else if (doctorCount > 0 || doctorActionCount > 0) {
                $('#configSummaryDoctorValue').text('Belum lengkap');
                $('#configSummaryDoctorFoot').text(`${formatNumber(doctorCount)} dokter / ${formatNumber(doctorActionCount)} tindakan`);
            } else {
                $('#configSummaryDoctorValue').text('Nonaktif');
                $('#configSummaryDoctorFoot').text('Semua dokter masuk normal');
            }

            $('#configSummarySourceValue').text(`${formatNumber(sourceMappings.length)} rule`);
            $('#configSummarySourceFoot').text(
                sourceMappings.length > 0 ?
                `${formatNumber(uniqueSourceCount)} sumber / ${formatNumber(uniqueActionCount)} tindakan` :
                'Routing default'
            );
        }

        const table = $('#tablePremiBersama').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: routes.table,
                data: function(d) {
                    d.periode = $('#periodePremiBersama').val();
                    d.jenis_pelayanan = activeType;
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'periode'
                },
                {
                    data: 'jenis_pelayanan_label'
                },
                {
                    data: null,
                    render: (data) => `${escapeHtml(data.kode_premi || '-')}<br><span class="text-muted">${escapeHtml(data.nama_premi || '-')}</span>`
                },
                {
                    data: 'total_generator_sumber',
                    className: 'text-end',
                    render: (value, type, row) => `${formatRupiah(value)}<br><span class="text-muted">${formatNumber(row.jumlah_sumber_terkunci || 0)} sumber</span>`
                },
                {
                    data: 'total_tindakan_rawat',
                    className: 'text-end',
                    render: (value, type, row) => `${formatRupiah(value)}<br><span class="text-muted">${formatNumber(row.jumlah_transaksi || 0)} trx</span>`
                },
                {
                    data: 'grand_total',
                    className: 'text-end',
                    render: (value) => `<strong>${formatRupiah(value)}</strong>`
                },
                {
                    data: 'jumlah_penerima',
                    className: 'text-end',
                    render: (value, type, row) => `${formatNumber(value)}<br><span class="text-muted">${formatNumber(row.total_skor || 0, 2)} skor</span>`
                },
                {
                    data: 'is_locked',
                    render: (locked) => locked ?
                        '<span class="badge bg-success">Terkunci</span>' :
                        '<span class="badge bg-warning text-dark">Terbuka</span>'
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                },
            ],
            order: [
                [1, 'desc']
            ]
        });

        function loadSummary() {
            $('#btnGeneratePremiBersama').prop('disabled', true);
            $('#readinessMessage').text('Memuat preview...');

            $.get(routes.summary, {
                periode: $('#periodePremiBersama').val(),
                jenis_pelayanan: activeType
            }).done((response) => {
                currentSummary = response.data;
                renderSummary(currentSummary);
            }).fail((xhr) => {
                currentSummary = null;
                renderEmptyPreview();
                notifyError(xhr, 'Gagal memuat preview Premi Bersama.');
            });
        }

        function renderSummary(data) {
            const generatorTotal = Number(data.total_generator_sumber || 0);
            const rawatTotal = Number(data.total_tindakan_rawat || 0);
            const grandTotal = Number(data.grand_total || 0);

            $('#summaryGrandTotal').text(formatRupiah(grandTotal));
            $('#summaryGeneratorTotal').text(formatRupiah(generatorTotal));
            $('#summaryRawatTotal').text(formatRupiah(rawatTotal));
            $('#summaryPenerima').text(formatNumber(data.jumlah_penerima || 0));
            $('#summaryDibagikan').text(formatRupiah(data.total_dibagikan || 0));
            $('#summaryGrandFoot').text(formatRupiah(generatorTotal) + ' + ' + formatRupiah(rawatTotal));
            $('#summaryGeneratorFoot').text(`${formatNumber(data.jumlah_sumber_terkunci || 0)} dari ${formatNumber(data.jumlah_sumber_generator || 0)} sumber siap`);
            $('#summaryRawatFoot').text(`${formatNumber(data.jumlah_transaksi || 0)} transaksi / ${formatNumber(data.jumlah_mapping_premi || 0)} mapping`);
            $('#summarySkorFoot').text('Total skor ' + formatNumber(data.total_skor || 0, 2));
            $('#summaryReadyText').text(data.ready ? 'Siap generate' : 'Belum siap');
            $('#readinessMessage').text(data.readiness_message || '-');
            $('#btnGeneratePremiBersama').prop('disabled', !data.ready);

            renderReadinessSteps(data.readiness_steps || []);
            renderSources('#sourceGeneratorGrid', data.sources || []);
            previewActionRows = data.preview_details || [];
            previewDistributionRows = data.distributions || [];
            applyPreviewActionFilters();
            applyPreviewDistributionFilters();
            renderPreviewPanels(data);
            updateConfigSummary();
        }

        function renderPreviewPanels(data) {
            const sources = data.sources || [];
            const details = data.preview_details || [];
            const distributions = data.distributions || [];
            const generatorTotal = Number(data.total_generator_sumber || 0);
            const rawatTotal = Number(data.total_tindakan_rawat || 0);
            const grandTotal = Number(data.grand_total || 0);
            const lockedSources = sources.filter((source) => source.is_locked).length;
            const generatorShare = percentOf(generatorTotal, grandTotal);
            const rawatShare = percentOf(rawatTotal, grandTotal);
            const topAction = details
                .slice()
                .sort((a, b) => Number(b.hasil_mapping || 0) - Number(a.hasil_mapping || 0))[0];
            const topReceiver = distributions
                .slice()
                .sort((a, b) => Number(b.total_received || 0) - Number(a.total_received || 0))[0];

            $('#generatorMetaLocked').text(`${formatNumber(lockedSources)}/${formatNumber(sources.length)}`);
            $('#generatorMetaShare').text(`${formatNumber(generatorShare, 1)}%`);
            $('#rawatMetaAction').text(formatNumber(details.length));
            $('#rawatMetaTop').text(topAction ? (topAction.kode_jenis_tindakan || topAction.nama_jenis_tindakan || '-') : '-');
            $('#rawatPreviewActionCount').text(`${formatNumber(details.length)} mapping`);
            $('#rawatPreviewTransactionCount').text(`${formatNumber(data.jumlah_transaksi || 0)} data`);
            $('#rawatPreviewContribution').text(`${formatRupiah(rawatTotal)} (${formatNumber(rawatShare, 1)}%)`);
            $('#distributionMetaRecipient').text(formatNumber(data.jumlah_penerima || distributions.length));
            $('#distributionMetaPaid').text(formatRupiah(data.total_dibagikan || 0));
            $('#distPreviewRecipientCount').text(`${formatNumber(data.jumlah_penerima || distributions.length)} pegawai`);
            $('#distPreviewScoreTotal').text(formatNumber(data.total_skor || 0, 2));
            $('#distPreviewTopReceiver').text(
                topReceiver ?
                `${topReceiver.pegawai_name || topReceiver.nik || '-'} / ${formatRupiah(topReceiver.total_received || 0)}` :
                '-'
            );
        }

        function renderEmptyPreview() {
            $('#summaryGrandTotal, #summaryGeneratorTotal, #summaryRawatTotal, #summaryDibagikan').text('Rp 0');
            $('#summaryPenerima').text('0');
            $('#summaryGrandFoot').text('Generator + tindakan');
            $('#summaryGeneratorFoot').text('0 sumber siap');
            $('#summaryRawatFoot').text('0 transaksi');
            $('#summarySkorFoot').text('Total skor 0');
            $('#summaryReadyText').text('Belum siap');
            $('#readinessSteps').html('<div class="pb-empty">Preview belum tersedia.</div>');
            $('#sourceGeneratorGrid').html('<div class="pb-empty">Sumber generator belum tersedia.</div>');
            previewActionRows = [];
            previewDistributionRows = [];
            applyPreviewActionFilters();
            applyPreviewDistributionFilters();
            renderPreviewPanels({
                sources: [],
                preview_details: [],
                distributions: [],
                total_generator_sumber: 0,
                total_tindakan_rawat: 0,
                grand_total: 0,
                jumlah_transaksi: 0,
                jumlah_penerima: 0,
                total_skor: 0,
                total_dibagikan: 0
            });
            updateConfigSummary();
        }

        function renderReadinessSteps(steps) {
            if (!steps.length) {
                $('#readinessSteps').html('<div class="pb-empty">Belum ada status kesiapan.</div>');
                return;
            }

            $('#readinessSteps').html(steps.map((step) => `
                <div class="pb-step ${escapeHtml(step.status || 'muted')}">
                    <div class="pb-step-label">${escapeHtml(step.label)}</div>
                    <div class="pb-step-value">${escapeHtml(step.value)}</div>
                    <div class="pb-step-note">${escapeHtml(step.note)}</div>
                </div>
            `).join(''));
        }

        function renderSources(target, sources) {
            if (!sources.length) {
                $(target).html('<div class="pb-empty">Belum ada sumber generator.</div>');
                return;
            }

            const totalTaken = sources.reduce((sum, source) => sum + Number(source.total_diambil || 0), 0);

            $(target).html(sources.map((source) => {
                const badgeClass = source.is_locked ? 'success' : (Number(source.generated_count || 0) > 0 ? 'warning' : 'muted');
                const status = source.status_label || (source.is_locked ? 'Terkunci' : 'Belum siap');
                const itemClass = source.is_locked ? 'ready' : (Number(source.generated_count || 0) > 0 ? 'pending' : 'muted');
                const contribution = percentOf(source.total_diambil || 0, totalTaken);

                return `
                    <div class="pb-source-item ${itemClass}">
                        <div class="pb-source-top">
                            <div class="pb-source-name">${escapeHtml(source.source_label)}</div>
                            <span class="pb-badge ${badgeClass}">${escapeHtml(status)}</span>
                        </div>
                        <div class="pb-source-value">${formatRupiah(source.total_diambil || 0)}</div>
                        <div class="pb-source-note">${escapeHtml(source.note || '-')}</div>
                        <div class="pb-source-progress">
                            <span style="width: ${progressWidth(contribution)}%"></span>
                        </div>
                        <div class="pb-source-meta">
                            <span>${formatNumber(source.generated_count || 0)} data</span>
                            <span>${formatNumber(contribution, 1)}%</span>
                        </div>
                    </div>
                `;
            }).join(''));
        }

        function mappingSnapshot(detail) {
            return detail.mapping_snapshot || {};
        }

        function mappingValueText(detail) {
            const isPercent = detail.jenis_mapping === 'persen';
            return `${formatNumber(detail.nilai_mapping || 0, isPercent ? 2 : 0)}${isPercent ? '%' : ''}`;
        }

        function mappingBasisText(detail) {
            return detail.jenis_mapping === 'persen' ?
                formatRupiah(detail.dasar_hitung || 0) :
                formatNumber(detail.dasar_hitung || 0);
        }

        function flattenRawatRows(details) {
            const rows = [];

            (details || []).forEach((detail) => {
                const snapshot = mappingSnapshot(detail);
                const detailLabel = [detail.kode_jenis_tindakan, detail.nama_jenis_tindakan]
                    .filter(Boolean)
                    .join(' - ');

                (detail.data_rawat || snapshot.sample_rows || []).forEach((row) => {
                    rows.push({
                        ...row,
                        detail_label: detailLabel,
                        hasil_mapping: detail.hasil_mapping,
                        jenis_mapping: detail.jenis_mapping,
                        nilai_mapping: detail.nilai_mapping
                    });
                });
            });

            return rows.sort((a, b) => String(b.tanggal || '').localeCompare(String(a.tanggal || '')));
        }

        function actionFilterState(prefix) {
            return {
                search: normalizeText($(`#${prefix}FilterSearch`).val()),
                jenis: $(`#${prefix}FilterJenis`).val() || 'all',
                data: $(`#${prefix}FilterData`).val() || 'all',
                sort: $(`#${prefix}FilterSort`).val() || 'hasil_desc'
            };
        }

        function actionSearchText(detail) {
            const snapshot = mappingSnapshot(detail);

            return normalizeText([
                detail.kode_jenis_tindakan,
                detail.nama_jenis_tindakan,
                detail.jenis_mapping,
                snapshot.formula_text,
                mappingValueText(detail),
                mappingBasisText(detail)
            ].join(' '));
        }

        function filteredActionRows(rows, filters) {
            return (rows || []).filter((detail) => {
                const jenis = normalizeText(detail.jenis_mapping);
                const hasData = Number(detail.jumlah_data || 0) > 0;

                if (filters.search && !actionSearchText(detail).includes(filters.search)) {
                    return false;
                }

                if (filters.jenis === 'persen' && jenis !== 'persen') {
                    return false;
                }

                if (filters.jenis === 'nominal' && jenis === 'persen') {
                    return false;
                }

                if (filters.data === 'with_data' && !hasData) {
                    return false;
                }

                if (filters.data === 'empty' && hasData) {
                    return false;
                }

                return true;
            });
        }

        function sortActionRows(rows, sort) {
            return rows.slice().sort((a, b) => {
                if (sort === 'hasil_asc') {
                    return Number(a.hasil_mapping || 0) - Number(b.hasil_mapping || 0);
                }

                if (sort === 'data_desc') {
                    return Number(b.jumlah_data || 0) - Number(a.jumlah_data || 0);
                }

                if (sort === 'kode_asc') {
                    return String(a.kode_jenis_tindakan || a.nama_jenis_tindakan || '')
                        .localeCompare(String(b.kode_jenis_tindakan || b.nama_jenis_tindakan || ''));
                }

                return Number(b.hasil_mapping || 0) - Number(a.hasil_mapping || 0);
            });
        }

        function actionFilterInfo(rows, totalRows) {
            const totalValue = rows.reduce((sum, detail) => sum + Number(detail.hasil_mapping || 0), 0);
            const totalData = rows.reduce((sum, detail) => sum + Number(detail.jumlah_data || 0), 0);

            return `${formatNumber(rows.length)} dari ${formatNumber(totalRows.length)} tindakan - ${formatNumber(totalData)} data - ${formatRupiah(totalValue)}`;
        }

        function applyActionFilters(rows, target, prefix, infoTarget, rich = false) {
            const filters = actionFilterState(prefix);
            const filtered = sortActionRows(filteredActionRows(rows, filters), filters.sort);

            renderDetailRows(target, filtered, rich, true);
            $(infoTarget).text(actionFilterInfo(filtered, rows || []));
        }

        function applyPreviewActionFilters() {
            applyActionFilters(previewActionRows, '#previewDetailTable tbody', 'rawat', '#rawatFilterInfo');
        }

        function applyDetailActionFilters() {
            applyActionFilters(detailActionRows, '#detailActionTable tbody', 'detailAction', '#detailActionFilterInfo', true);
        }

        function resetActionFilters(prefix) {
            $(`#${prefix}FilterSearch`).val('');
            $(`#${prefix}FilterJenis`).val('all');
            $(`#${prefix}FilterData`).val('all');
            $(`#${prefix}FilterSort`).val('hasil_desc');
        }

        function renderDetailRows(target, details, rich = false, keepOrder = false) {
            if (!details.length) {
                $(target).html('<tr><td colspan="6" class="text-center text-muted">Belum ada detail tindakan.</td></tr>');
                return;
            }

            const totalResult = details.reduce((sum, detail) => sum + Number(detail.hasil_mapping || 0), 0);
            const sortedDetails = keepOrder ?
                details.slice() :
                details.slice().sort((a, b) => Number(b.hasil_mapping || 0) - Number(a.hasil_mapping || 0));

            $(target).html(sortedDetails.map((detail, index) => {
                const snapshot = mappingSnapshot(detail);
                const formula = snapshot.formula_text || '';
                const isPercent = detail.jenis_mapping === 'persen';
                const contribution = percentOf(detail.hasil_mapping || 0, totalResult);
                const providerNote = rich ?
                    `<span class="pb-action-subtitle">${formatNumber(snapshot.jumlah_dokter || detail.jumlah_data_dokter || 0)} dokter / ${formatNumber(snapshot.jumlah_paramedis || detail.jumlah_data_paramedis || 0)} paramedis</span>` :
                    '';

                return `
                    <tr>
                        <td>
                            <div class="pb-action-name">
                                <span class="pb-action-index">${formatNumber(index + 1)}</span>
                                <div class="min-w-0">
                                    <span class="pb-action-title">${escapeHtml(detail.kode_jenis_tindakan || '-')}</span>
                                    <span class="pb-action-subtitle">${escapeHtml(detail.nama_jenis_tindakan || '-')}</span>
                                    ${providerNote}
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="pb-map-chip ${isPercent ? 'percent' : 'nominal'}">
                                ${escapeHtml(String(detail.jenis_mapping || '-'))}
                            </span>
                            ${formula ? `<div class="pb-detail-formula">${escapeHtml(formula)}</div>` : ''}
                        </td>
                        <td class="text-end">
                            <span class="pb-amount-strong">${mappingValueText(detail)}</span>
                            <span class="pb-amount-note">${isPercent ? 'dari dasar' : 'per data'}</span>
                        </td>
                        <td class="text-end">
                            <span class="pb-amount-strong">${formatNumber(detail.jumlah_data || 0)}</span>
                            <span class="pb-amount-note">baris</span>
                        </td>
                        <td class="text-end">
                            <span class="pb-amount-strong">${mappingBasisText(detail)}</span>
                            <span class="pb-amount-note">dasar</span>
                        </td>
                        <td class="text-end">
                            <span class="pb-amount-strong">${formatRupiah(detail.hasil_mapping || 0)}</span>
                            <span class="pb-amount-note">${formatNumber(contribution, 1)}% tindakan</span>
                            <div class="pb-detail-progress">
                                <span style="width: ${progressWidth(contribution)}%"></span>
                            </div>
                        </td>
                    </tr>
                `;
            }).join(''));
        }

        function renderDetailInsights(data) {
            const details = data.details || [];
            const rawatRows = flattenRawatRows(details);
            const doctorCount = new Set(rawatRows.map((row) => row.kd_dokter).filter(Boolean)).size;
            const paramedicCount = new Set(rawatRows.map((row) => row.nip).filter(Boolean)).size;
            const patientCount = new Set(rawatRows.map((row) => row.no_rawat).filter(Boolean)).size;
            const routeCount = rawatRows.filter((row) => row.route_reason === 'doctor_filter_non_selected').length;

            $('#detailActionCount').text(formatNumber(details.length));
            $('#detailActionFoot').text(`${formatNumber(data.jumlah_mapping_premi || details.length)} jenis tindakan`);
            $('#detailTransactionCount').text(formatNumber(rawatRows.length));
            $('#detailPatientFoot').text(`${formatNumber(patientCount)} pasien unik`);
            $('#detailProviderCount').text(`${formatNumber(doctorCount)} / ${formatNumber(paramedicCount)}`);
            $('#detailProviderFoot').text('Dokter / paramedis');
            $('#detailRouteCount').text(formatNumber(routeCount));
        }

        function renderTopMappingGrid(details) {
            const topDetails = (details || [])
                .slice()
                .sort((a, b) => Number(b.hasil_mapping || 0) - Number(a.hasil_mapping || 0))
                .slice(0, 6);

            if (!topDetails.length) {
                $('#detailTopMappingGrid').html('<div class="pb-empty">Belum ada hasil mapping tindakan.</div>');
                return;
            }

            $('#detailTopMappingGrid').html(topDetails.map((detail) => {
                const snapshot = mappingSnapshot(detail);
                const sourceTop = (snapshot.source_breakdown || [])[0];

                return `
                    <div class="pb-mapping-card">
                        <div class="pb-mapping-label">${escapeHtml(detail.kode_jenis_tindakan || '-')}</div>
                        <strong>${escapeHtml(detail.nama_jenis_tindakan || '-')}</strong>
                        <div class="pb-mapping-value">${formatRupiah(detail.hasil_mapping || 0)}</div>
                        <div class="pb-mapping-note">
                            ${formatNumber(detail.jumlah_data || 0)} data / ${escapeHtml(snapshot.formula_text || mappingValueText(detail))}
                            ${sourceTop ? `<br>Sumber terbesar: ${escapeHtml(sourceTop.label || '-')}` : ''}
                        </div>
                    </div>
                `;
            }).join(''));
        }

        function renderMappingNarrative(data) {
            const details = data.details || [];
            const rawatRows = flattenRawatRows(details);
            const totalMapping = details.reduce((sum, detail) => sum + Number(detail.hasil_mapping || 0), 0);
            const topDetail = details
                .slice()
                .sort((a, b) => Number(b.hasil_mapping || 0) - Number(a.hasil_mapping || 0))[0];

            $('#detailMappingNarrative').text(
                topDetail ?
                `${formatNumber(details.length)} tindakan menghasilkan ${formatRupiah(totalMapping)} dari ${formatNumber(rawatRows.length)} transaksi. Tertinggi: ${topDetail.nama_jenis_tindakan || '-'}.` :
                'Belum ada tindakan rawat yang masuk mapping.'
            );
        }

        function detailFormulaHtml(data) {
            const generator = formatRupiah(data.total_generator_sumber || 0);
            const rawat = formatRupiah(data.total_tindakan_rawat || 0);
            const grand = formatRupiah(data.grand_total || 0);

            return `Grand total: <strong>${grand}</strong> <span class="pb-formula-muted">(${generator} generator + ${rawat} tindakan)</span> dibagikan berdasarkan proporsi skor pegawai.`;
        }

        function renderDetailConcept(data) {
            detailData = data || {};
            detailActionRows = detailData.details || [];
            detailDistributionRows = detailData.distributions || [];
            selectedDetailMappingId = detailActionRows.length ? detailKey(detailActionRows[0], 0) : '';

            $('#detailPremiBersamaMeta').text(
                `${detailData.jenis_pelayanan_label || '-'} / ${detailData.periode || '-'} / sumber ${detailData.source_periode || detailData.periode || '-'}`
            );
            $('#detailStatusBadge').text(detailData.is_locked ? 'Terkunci' : 'Terbuka');
            $('#detailGeneratedBy').text(`Generate oleh ${detailData.generate_by_name || '-'}`);
            $('#detailLockedInfo').text(detailData.is_locked ? `Dikunci ${detailData.locked_at || '-'} oleh ${detailData.locked_by_name || '-'}` : 'Belum dikunci');
            $('#detailGeneratorTotal').text(formatRupiah(detailData.total_generator_sumber || 0));
            $('#detailRawatTotal').text(formatRupiah(detailData.total_tindakan_rawat || 0));
            $('#detailGrandTotal').text(formatRupiah(detailData.grand_total || 0));
            $('#detailTotalSkor').text(formatNumber(detailData.total_skor || 0, 2));
            $('#detailDibagikan').text(formatRupiah(detailData.total_dibagikan || 0));
            $('#detailTransactionCount').text(formatNumber(detailData.jumlah_transaksi || flattenRawatRows(detailActionRows).length));
            $('#detailRecipientCount').text(formatNumber(detailData.jumlah_penerima || detailDistributionRows.length));
            $('#detailFormulaBersama').html(detailFormulaHtml(detailData));
            $('#detailDistributionNoteBersama').text(
                `Grand total ${formatRupiah(detailData.grand_total || 0)} / total skor ${formatNumber(detailData.total_skor || 0, 2)} / dibagikan ${formatRupiah(detailData.total_dibagikan || 0)}`
            );

            renderDetailInsightConcept(detailData);
            renderDetailDistributionConcept();
            renderDetailMappingRowsConcept();
            renderDetailMappingFilterConcept();
            renderDetailRawatConcept();
            $('#detailPremiBersamaLoading').addClass('d-none');
            $('#detailPremiBersamaContent').removeClass('d-none');
        }

        function renderDetailInsightConcept(data) {
            const details = data.details || [];
            const rawatRows = flattenRawatRows(details);
            const doctorCount = new Set(rawatRows.map((row) => row.kd_dokter).filter(Boolean)).size;
            const paramedicCount = new Set(rawatRows.map((row) => row.nip).filter(Boolean)).size;
            const patientCount = new Set(rawatRows.map((row) => row.no_rawat).filter(Boolean)).size;
            const lockedSources = (data.sources || []).filter((source) => source.is_locked).length;
            const topReceiver = (data.distributions || [])
                .slice()
                .sort((a, b) => Number(b.total_received || 0) - Number(a.total_received || 0))[0];

            $('#detailInsightBersama').html([
                infoPill('mdi-source-branch', 'Generator', `${formatNumber(lockedSources)} sumber terkunci`),
                infoPill('mdi-format-list-checks', 'Mapping', formatNumber(details.length)),
                infoPill('mdi-database-search-outline', 'Rawat', formatNumber(rawatRows.length)),
                infoPill('mdi-account-injury-outline', 'Pasien', formatNumber(patientCount)),
                infoPill('mdi-doctor', 'Provider', `${formatNumber(doctorCount)} / ${formatNumber(paramedicCount)}`),
                infoPill('mdi-trophy-outline', 'Teratas', topReceiver ? formatRupiah(topReceiver.total_received || 0) : 'Rp 0')
            ].join(''));
        }

        function renderDetailDistributionConcept() {
            const rows = detailDistributionRows || [];
            const keyword = normalizeText($('#searchDetailDistributionBersama').val());
            const limit = $('#filterDetailDistributionLimitBersama').val() || '25';
            const filtered = rows
                .filter((item) => !keyword || distributionSearchText(item).includes(keyword))
                .sort((a, b) => Number(b.total_received || 0) - Number(a.total_received || 0));
            const visible = limit === 'all' ? filtered : filtered.slice(0, Number(limit || 25));

            $('#detailDistributionCountBersama').text(
                `${formatNumber(visible.length)} dari ${formatNumber(filtered.length)} penerima`
            );

            if (!visible.length) {
                $('#detailDistributionRowsBersama').html('<tr><td colspan="4" class="pb-empty">Tidak ada distribusi.</td></tr>');
                return;
            }

            $('#detailDistributionRowsBersama').html(visible.map((row) => `
                <tr>
                    <td>
                        <div class="fw-semibold">${escapeHtml(row.pegawai_name || row.nik || '-')}</div>
                        <div class="text-muted">${escapeHtml(row.nik || '-')} / ${escapeHtml(row.pegawai_position || '-')}</div>
                    </td>
                    <td class="text-end">${formatNumber(row.skor_pegawai || 0, 2)}</td>
                    <td class="text-end">${formatNumber(row.allocation_percent || 0, 4)}%</td>
                    <td class="text-end fw-bold">${formatRupiah(row.total_received || 0)}</td>
                </tr>
            `).join(''));
        }

        function detailSourceBreakdown(detail) {
            const snapshot = mappingSnapshot(detail);
            const rows = snapshot.source_breakdown || detail.source_breakdown || [];

            if (rows.length) {
                return rows.map((row) => ({
                    label: row.label || row.source_label || row.source_table || '-',
                    count: Number(row.count || row.jumlah_data || 0),
                    total: Number(row.total_biaya_rawat || row.total || row.biaya_rawat || 0)
                }));
            }

            const grouped = {};
            (detail.data_rawat || snapshot.sample_rows || []).forEach((row) => {
                const key = rawatSourceKey(row);

                if (!grouped[key]) {
                    grouped[key] = {
                        label: key,
                        count: 0,
                        total: 0
                    };
                }

                grouped[key].count += 1;
                grouped[key].total += Number(row.biaya_rawat || 0);
            });

            return Object.values(grouped);
        }

        function sourceBreakdownLabel(detail) {
            const rows = detailSourceBreakdown(detail);

            if (!rows.length) {
                return 'Semua sumber rawat';
            }

            return rows
                .slice(0, 2)
                .map((row) => `${row.label} ${formatNumber(row.count || 0)}`)
                .join(' / ');
        }

        function renderDetailMappingRowsConcept() {
            const tbody = $('#detailMappingRowsBersama').empty();
            const keyword = normalizeText($('#searchDetailMappingBersama').val());
            const filtered = detailActionRows.filter((detail) => {
                const haystack = normalizeText([
                    detail.kode_jenis_tindakan,
                    detail.nama_jenis_tindakan,
                    detail.jenis_mapping,
                    sourceBreakdownLabel(detail),
                    mappingValueText(detail),
                    mappingBasisText(detail)
                ].join(' '));

                return !keyword || haystack.includes(keyword);
            });

            $('#detailMappingCountBersama').text(
                `${formatNumber(filtered.length)} dari ${formatNumber(detailActionRows.length)} mapping`
            );

            if (!filtered.length) {
                tbody.html('<tr><td colspan="7" class="pb-empty">Tidak ada detail mapping.</td></tr>');
                return;
            }

            tbody.html(filtered.map((detail) => {
                const originalIndex = detailActionRows.indexOf(detail);
                const rowKey = detailKey(detail, originalIndex);
                const active = rowKey === String(selectedDetailMappingId) ? 'table-active' : '';

                return `
                    <tr class="pb-detail-clickable detail-mapping-row-bersama ${active}" data-id="${escapeHtml(rowKey)}">
                        <td>
                            <div class="fw-semibold">${escapeHtml(detail.kode_jenis_tindakan || '-')}</div>
                            <div class="text-muted">${escapeHtml(detail.nama_jenis_tindakan || '-')}</div>
                        </td>
                        <td>${escapeHtml(sourceBreakdownLabel(detail))}</td>
                        <td><span class="pb-map-chip ${detail.jenis_mapping === 'persen' ? 'percent' : 'nominal'}">${escapeHtml(detail.jenis_mapping || '-')}</span></td>
                        <td class="text-end">${mappingValueText(detail)}</td>
                        <td class="text-center">${formatNumber(detail.jumlah_data || 0)}</td>
                        <td class="text-end">${mappingBasisText(detail)}</td>
                        <td class="text-end fw-bold">${formatRupiah(detail.hasil_mapping || 0)}</td>
                    </tr>
                `;
            }).join(''));
        }

        function renderDetailMappingFilterConcept() {
            const select = $('#filterDetailMappingBersama').empty();

            if (!detailActionRows.length) {
                select.append('<option value="">Belum ada mapping</option>');
                selectedDetailMappingId = '';
                return;
            }

            detailActionRows.forEach((detail, index) => {
                select.append($('<option>', {
                    value: detailKey(detail, index),
                    text: `${detail.kode_jenis_tindakan || '-'} - ${detail.nama_jenis_tindakan || '-'}`
                }));
            });

            select.val(selectedDetailMappingId);
        }

        function selectedDetailMapping() {
            return detailActionRows.find((detail, index) => {
                return detailKey(detail, index) === String(selectedDetailMappingId);
            });
        }

        function renderDetailRawatConcept() {
            const detail = selectedDetailMapping();
            const sourceRows = $('#detailSourceRowsBersama').empty();
            const rawRows = $('#detailRawatRowsBersama').empty();

            if (!detail) {
                sourceRows.html('<tr><td colspan="3" class="pb-empty">Pilih mapping.</td></tr>');
                rawRows.html('<tr><td colspan="9" class="pb-empty">Pilih mapping.</td></tr>');
                $('#detailSelectedInsightBersama').empty();
                return;
            }

            const snapshot = mappingSnapshot(detail);
            const rawat = detail.data_rawat || snapshot.sample_rows || [];
            const sources = detailSourceBreakdown(detail);
            const selectedSource = $('#filterDetailRawatSourceBersama').val() || 'all';
            const sourceSelect = $('#filterDetailRawatSourceBersama').empty()
                .append('<option value="all">Semua sumber</option>');

            sources.forEach((item) => {
                sourceSelect.append($('<option>', {
                    value: item.label,
                    text: `${item.label} (${formatNumber(item.count || 0)})`
                }));
            });

            sourceSelect.val(selectedSource !== 'all' && sources.some((item) => item.label === selectedSource) ? selectedSource : 'all');

            $('#detailSourceMetaBersama').text(`${formatNumber(rawat.length)} rawat untuk ${detail.nama_jenis_tindakan || '-'}`);
            $('#detailSourceCountBersama').text(`${formatNumber(sources.length)} sumber`);

            sourceRows.html(sources.length ? sources.map((item) => `
                <tr class="pb-detail-clickable detail-source-row-bersama" data-source="${escapeHtml(item.label)}">
                    <td>${escapeHtml(item.label)}</td>
                    <td class="text-center">${formatNumber(item.count || 0)}</td>
                    <td class="text-end">${formatRupiah(item.total || 0)}</td>
                </tr>
            `).join('') : '<tr><td colspan="3" class="pb-empty">Tidak ada sumber.</td></tr>');

            const keyword = normalizeText($('#searchDetailRawatBersama').val());
            const sourceFilter = $('#filterDetailRawatSourceBersama').val() || 'all';
            const executorFilter = $('#filterDetailRawatPelaksanaBersama').val() || 'all';
            const filtered = rawat.filter((row) => {
                const sourceLabel = rawatSourceKey(row);

                if (sourceFilter !== 'all' && sourceLabel !== sourceFilter) {
                    return false;
                }

                if (!rawatProviderMatches(row, executorFilter)) {
                    return false;
                }

                return !keyword || rawatSearchText(row).includes(keyword);
            });

            $('#detailRawatMetaBersama').text(
                `${formatNumber(filtered.length)} dari ${formatNumber(rawat.length)} rawat ditampilkan`
            );
            $('#detailSelectedInsightBersama').html([
                infoPill('mdi-database-outline', 'Rawat', formatNumber(detail.jumlah_data || rawat.length)),
                infoPill('mdi-doctor', 'Dokter', formatNumber(snapshot.jumlah_dokter || detail.jumlah_data_dokter || 0)),
                infoPill('mdi-account-heart-outline', 'Paramedis', formatNumber(snapshot.jumlah_paramedis || detail.jumlah_data_paramedis || 0)),
                infoPill('mdi-source-branch', 'Sumber', formatNumber(sources.length)),
                infoPill('mdi-cash-multiple', 'Hasil', formatRupiah(detail.hasil_mapping || 0))
            ].join(''));

            if (!filtered.length) {
                rawRows.html('<tr><td colspan="9" class="pb-empty">Data rawat tidak ditemukan.</td></tr>');
                return;
            }

            rawRows.html(filtered.map((row, index) => `
                <tr>
                    <td>${index + 1}</td>
                    <td>${escapeHtml(row.tanggal || '-')}<div class="small text-muted">${escapeHtml(row.jam || '-')}</div></td>
                    <td>${escapeHtml(row.no_rawat || '-')}</td>
                    <td>${escapeHtml(row.nm_pasien || '-')}<div class="small text-muted">${escapeHtml(row.no_rkm_medis || '-')}</div></td>
                    <td>
                        ${escapeHtml(rawatSourceKey(row))}
                        ${row.route_label ? `<div class="pb-route-note">${escapeHtml(row.route_label)}</div>` : ''}
                    </td>
                    <td>${escapeHtml(row.kd_tindakan || '-')}<div class="small text-muted">${escapeHtml(row.nm_tindakan || row.detail_label || '-')}</div></td>
                    <td>${escapeHtml(row.nama_penjamin || row.kd_pj || '-')}</td>
                    <td>${escapeHtml(pelaksanaLabel(row))}</td>
                    <td class="text-end">${formatRupiah(row.biaya_rawat || 0)}</td>
                </tr>
            `).join(''));
        }

        function rawatSourceKey(row) {
            return row.source_label || row.source_table || '-';
        }

        function rawatSearchText(row) {
            return normalizeText([
                row.tanggal,
                row.jam,
                row.no_rawat,
                row.no_rkm_medis,
                row.nm_pasien,
                row.detail_label,
                row.nm_tindakan,
                row.kd_tindakan,
                row.source_label,
                row.source_table,
                row.nm_dokter,
                row.nama_petugas,
                row.route_label,
                row.nama_penjamin,
                row.kd_pj,
                row.biaya_rawat
            ].join(' '));
        }

        function rawatProviderMatches(row, provider) {
            if (provider === 'doctor') {
                return Boolean(row.nm_dokter || row.kd_dokter);
            }

            if (provider === 'paramedic') {
                return Boolean(row.nama_petugas || row.nip);
            }

            if (provider === 'routed') {
                return Boolean(row.route_label || row.route_reason);
            }

            return true;
        }

        function sortRawatRows(rows, sort) {
            return rows.slice().sort((a, b) => {
                if (sort === 'date_asc') {
                    return String(`${a.tanggal || ''} ${a.jam || ''}`).localeCompare(String(`${b.tanggal || ''} ${b.jam || ''}`));
                }

                if (sort === 'cost_desc') {
                    return Number(b.biaya_rawat || 0) - Number(a.biaya_rawat || 0);
                }

                if (sort === 'patient_asc') {
                    return String(a.nm_pasien || a.no_rawat || '').localeCompare(String(b.nm_pasien || b.no_rawat || ''));
                }

                return String(`${b.tanggal || ''} ${b.jam || ''}`).localeCompare(String(`${a.tanggal || ''} ${a.jam || ''}`));
            });
        }

        function fillRawatSourceFilter(rows) {
            const currentValue = $('#detailRawatFilterSource').val() || 'all';
            const sources = [...new Set((rows || []).map(rawatSourceKey).filter(Boolean))]
                .sort((a, b) => String(a).localeCompare(String(b)));

            $('#detailRawatFilterSource').html([
                '<option value="all">Semua sumber</option>',
                ...sources.map((source) => `<option value="${escapeHtml(source)}">${escapeHtml(source)}</option>`)
            ].join(''));

            $('#detailRawatFilterSource').val(sources.includes(currentValue) ? currentValue : 'all');
        }

        function resetDetailRawatFilters() {
            $('#detailRawatFilterSearch').val('');
            $('#detailRawatFilterSource').val('all');
            $('#detailRawatFilterProvider').val('all');
            $('#detailRawatFilterSort').val('date_desc');
            $('#detailRawatFilterLimit').val('150');
        }

        function applyDetailRawatFilters() {
            const search = normalizeText($('#detailRawatFilterSearch').val());
            const source = $('#detailRawatFilterSource').val() || 'all';
            const provider = $('#detailRawatFilterProvider').val() || 'all';
            const sort = $('#detailRawatFilterSort').val() || 'date_desc';
            const limit = $('#detailRawatFilterLimit').val() || '150';
            const filtered = sortRawatRows((detailRawatRows || []).filter((row) => {
                if (search && !rawatSearchText(row).includes(search)) {
                    return false;
                }

                if (source !== 'all' && rawatSourceKey(row) !== source) {
                    return false;
                }

                return rawatProviderMatches(row, provider);
            }), sort);
            const visibleRows = limit === 'all' ? filtered : filtered.slice(0, Number(limit || 150));
            const totalBiaya = visibleRows.reduce((sum, row) => sum + Number(row.biaya_rawat || 0), 0);
            const patientCount = new Set(visibleRows.map((row) => row.no_rawat).filter(Boolean)).size;

            renderRawatRows('#detailRawatTable tbody', visibleRows);
            $('#detailRawatFoot').text(
                `${formatNumber(filtered.length)} dari ${formatNumber(detailRawatRows.length)} transaksi cocok dengan filter aktif.`
            );
            $('#detailRawatFilterInfo').text(
                `${formatNumber(visibleRows.length)} tampil - ${formatNumber(patientCount)} rawat unik - total biaya ${formatRupiah(totalBiaya)}`
            );
        }

        function renderRawatRows(target, rows) {
            if (!rows.length) {
                $(target).html('<tr><td colspan="8" class="text-center text-muted">Belum ada data tindakan hasil mapping.</td></tr>');
                return;
            }

            $(target).html(rows.map((row) => {
                const provider = [
                    row.nm_dokter ? `Dr: ${escapeHtml(row.nm_dokter)}` : '',
                    row.nama_petugas ? `Pr: ${escapeHtml(row.nama_petugas)}` : '',
                    row.route_label ? escapeHtml(row.route_label) : ''
                ].filter(Boolean).join('<br>');

                return `
                    <tr>
                        <td>${escapeHtml(row.tanggal || '-')}<br><span class="text-muted">${escapeHtml(row.jam || '')}</span></td>
                        <td>${escapeHtml(row.no_rawat || '-')}<br><span class="text-muted">${escapeHtml(row.no_rkm_medis || '-')}</span></td>
                        <td>${escapeHtml(row.nm_pasien || '-')}</td>
                        <td>${escapeHtml(row.detail_label || row.nm_tindakan || '-')}<br><span class="text-muted">${escapeHtml(row.kd_tindakan || '-')}</span></td>
                        <td>${escapeHtml(row.source_label || row.source_table || '-')}</td>
                        <td>${provider || '-'}</td>
                        <td>${escapeHtml(row.nama_penjamin || row.kd_pj || '-')}</td>
                        <td class="text-end"><strong>${formatRupiah(row.biaya_rawat || 0)}</strong></td>
                    </tr>
                `;
            }).join(''));
        }

        function distributionSearchText(row) {
            return normalizeText([
                row.pegawai_name,
                row.nik,
                row.pegawai_position,
                row.skor_pegawai,
                row.total_received
            ].join(' '));
        }

        function sortDistributionRows(rows, sort) {
            return rows.slice().sort((a, b) => {
                if (sort === 'score_desc') {
                    return Number(b.skor_pegawai || 0) - Number(a.skor_pegawai || 0);
                }

                if (sort === 'percent_desc') {
                    return Number(b.allocation_percent || 0) - Number(a.allocation_percent || 0);
                }

                if (sort === 'name_asc') {
                    return String(a.pegawai_name || a.nik || '').localeCompare(String(b.pegawai_name || b.nik || ''));
                }

                return Number(b.total_received || 0) - Number(a.total_received || 0);
            });
        }

        function applyDistributionFilters(rows, target, prefix, infoTarget) {
            const search = normalizeText($(`#${prefix}FilterSearch`).val());
            const sort = $(`#${prefix}FilterSort`).val() || 'received_desc';
            const limit = $(`#${prefix}FilterLimit`).val() || '25';
            const filtered = sortDistributionRows((rows || []).filter((row) => {
                return !search || distributionSearchText(row).includes(search);
            }), sort);
            const visible = limit === 'all' ? filtered : filtered.slice(0, Number(limit || 25));
            const visibleTotal = visible.reduce((sum, row) => sum + Number(row.total_received || 0), 0);
            const visibleScore = visible.reduce((sum, row) => sum + Number(row.skor_pegawai || 0), 0);

            renderDistributionRows(target, visible, true);
            $(infoTarget).text(
                `${formatNumber(visible.length)} dari ${formatNumber(filtered.length)} pegawai - ${formatRupiah(visibleTotal)} tampil - skor ${formatNumber(visibleScore, 2)}`
            );
        }

        function applyPreviewDistributionFilters() {
            applyDistributionFilters(previewDistributionRows, '#previewDistributionTable tbody', 'distribution', '#distributionFilterInfo');
        }

        function applyDetailDistributionFilters() {
            applyDistributionFilters(detailDistributionRows, '#detailDistributionTable tbody', 'detailDistribution', '#detailDistributionFilterInfo');
        }

        function resetDistributionFilters(prefix) {
            $(`#${prefix}FilterSearch`).val('');
            $(`#${prefix}FilterSort`).val('received_desc');
            $(`#${prefix}FilterLimit`).val('25');
        }

        function renderDistributionRows(target, rows, keepOrder = false) {
            if (!rows.length) {
                $(target).html('<tr><td colspan="4" class="text-center text-muted">Belum ada distribusi.</td></tr>');
                return;
            }

            const sortedRows = keepOrder ?
                rows.slice() :
                rows.slice().sort((a, b) => Number(b.total_received || 0) - Number(a.total_received || 0));

            $(target).html(sortedRows.map((row, index) => {
                const allocation = Number(row.allocation_percent || 0);

                return `
                <tr>
                    <td>
                        <div class="pb-employee-name">
                            <span class="pb-employee-rank">${formatNumber(index + 1)}</span>
                            <div class="min-w-0">
                                <span class="pb-employee-title">${escapeHtml(row.pegawai_name || row.nik || '-')}</span>
                                <span class="pb-employee-subtitle">${escapeHtml(row.nik || '-')} / ${escapeHtml(row.pegawai_position || '-')}</span>
                            </div>
                        </div>
                    </td>
                    <td class="text-end">
                        <span class="pb-amount-strong">${formatNumber(row.skor_pegawai || 0, 2)}</span>
                        <span class="pb-amount-note">skor</span>
                    </td>
                    <td class="text-end pb-distribution-cell">
                        <span class="pb-distribution-percent">${formatNumber(allocation, 4)}%</span>
                        <div class="pb-distribution-progress">
                            <span style="width: ${progressWidth(allocation)}%"></span>
                        </div>
                    </td>
                    <td class="text-end">
                        <span class="pb-amount-strong">${formatRupiah(row.total_received || 0)}</span>
                        <span class="pb-amount-note">diterima</span>
                    </td>
                </tr>
            `;
            }).join(''));
        }

        function loadConfigDependencies(callback) {
            setConfigLoading(true, 'Memuat konfigurasi...', 'Mengambil mapping premi, plotting, dan aturan tersimpan.');

            $.when(
                $.get(routes.mappingOptions),
                $.get(routes.plotingOptions),
                $.get(routes.config)
            ).done((mappingResponse, plotingResponse, configResponse) => {
                mappingOptions = mappingResponse[0].data || [];
                plotingOptions = plotingResponse[0].data || [];
                configData = configResponse[0].data || {};
                doctorOptions = configData.doctor_filter?.selected_doctors || [];
                sourcePatternOptions = configData.source_pattern_options || [];

                fillConfigBase();
                setConfigLoading(true, 'Memuat tindakan mapping...', 'Menyusun pilihan tindakan untuk karcis BPJS, filter dokter, dan routing sumber.');
                loadActionOptions([configData.jnsPremi_umum_id, configData.jnsPremi_bpjs_id], () => {
                    hydrateConfigForm(configData);
                    updateConfigSummary();
                    setConfigLoading(false);
                    if (typeof callback === 'function') {
                        callback();
                    }
                }, () => setConfigLoading(false));
            }).fail((xhr) => {
                setConfigLoading(false);
                notifyError(xhr, 'Gagal memuat konfigurasi.');
            });
        }

        function fillConfigBase() {
            fillSelect('#configMappingUmum', mappingOptions, configData.jnsPremi_umum_id, 'Pilih mapping UMUM');
            fillSelect('#configMappingBpjs', mappingOptions, configData.jnsPremi_bpjs_id, 'Pilih mapping BPJS');
            $('#configBpjsSourceMode').val(configData.bpjs_source_mode || 'previous');
            fillSelect('#configUgdPloting', plotingOptions, configData.ugd_plotingPremi_id, 'Pilih plotting UGD');
            fillSelect('#configVkPloting', plotingOptions, configData.vk_plotingPremi_id, 'Pilih plotting VK');
            fillSelect('#configKamarPloting', plotingOptions, configData.kamar_plotingPremi_id, 'Pilih plotting Kamar');
            fillSelect('#configBhpPloting', plotingOptions, configData.bhp_plotingPremi_id, 'Pilih plotting BHP');

            const selectedDoctors = configData.doctor_filter?.selected_doctors || [];
            const doctorItems = [...doctorOptions];
            selectedDoctors.forEach((doctor) => {
                if (!doctorItems.some((item) => String(item.id) === String(doctor.id))) {
                    doctorItems.push(doctor);
                }
            });
            fillMultiSelect('#configDoctorCodes', doctorItems, configData.doctor_filter?.selected_codes || []);
        }

        function loadActionOptions(mappingIds, callback, failCallback = null) {
            const ids = uniqueValues(Array.isArray(mappingIds) ? mappingIds : [mappingIds]);

            if (!ids.length) {
                actionOptions = [];
                fillMultiSelect('#configIncludedActions', [], []);
                fillMultiSelect('#configDoctorActions', [], []);
                $('#sourceMappingRows').empty();
                initConfigSelect2();
                updateConfigSummary();
                if (callback) callback();
                return;
            }

            const requests = ids.map((id) => $.get(routeWithParam(routes.actionOptions, id)));

            $.when.apply($, requests).done(function() {
                const responses = requests.length === 1 ?
                    [arguments[0]] :
                    Array.from(arguments).map((item) => item[0]);
                actionOptions = mergeActionOptions(responses.flatMap((response) => response.data || []));
                fillMultiSelect('#configIncludedActions', actionOptions, configData.included_umum_action_ids || []);
                fillMultiSelect('#configDoctorActions', actionOptions, configData.doctor_filter?.selected_action_ids || []);
                renderSourceMappingRows(configData.source_mappings || []);
                initConfigSelect2();
                updateConfigSummary();
                if (callback) callback();
            }).fail((xhr) => {
                notifyError(xhr, 'Gagal memuat tindakan mapping premi.');
                if (typeof failCallback === 'function') {
                    failCallback(xhr);
                }
            });
        }

        function hydrateConfigForm(config) {
            $('#configIgnoreIcu').prop('checked', Boolean(config.ignore_icu));
            $('#configIgnoreNicu').prop('checked', Boolean(config.ignore_nicu));
        }

        function renderSourceMappingRows(rows) {
            $('#sourceMappingRows').empty();

            if (!rows.length) {
                $('#sourceMappingRows').append('<div class="pb-empty mb-2">Belum ada mapping sumber khusus.</div>');
                updateConfigSummary();
                return;
            }

            rows.forEach((row) => addSourceMappingRow(row.source_pattern, row.jnsTindakan_id));
            updateConfigSummary();
        }

        function addSourceMappingRow(sourcePattern = '', tindakanId = '') {
            $('#sourceMappingRows .pb-empty').remove();
            const sourceOptions = sourcePatternOptions.map((item) => {
                const selected = String(item.source_pattern) === String(sourcePattern) ? 'selected' : '';
                return `<option value="${escapeHtml(item.source_pattern)}" ${selected}>${escapeHtml(item.label || item.source_pattern)}</option>`;
            }).join('');
            const actionItems = actionOptions.map((item) => {
                const selected = String(item.id) === String(tindakanId) ? 'selected' : '';
                return `<option value="${escapeHtml(item.id)}" ${selected}>${escapeHtml(item.text || item.jenis)}</option>`;
            }).join('');

            $('#sourceMappingRows').append(`
                <div class="pb-source-map-row">
                    <select class="form-select source-pattern-select">
                        <option value="">Pilih sumber</option>
                        ${sourceOptions}
                    </select>
                    <select class="form-select source-action-select">
                        <option value="">Pilih tindakan</option>
                        ${actionItems}
                    </select>
                    <button type="button" class="btn btn-outline-danger btn-remove-source-mapping" title="Hapus">
                        <i class="mdi mdi-close"></i>
                    </button>
                </div>
            `);
            updateConfigSummary();
        }

        function collectSourceMappings() {
            return $('#sourceMappingRows .pb-source-map-row').map(function() {
                return {
                    source_pattern: $(this).find('.source-pattern-select').val(),
                    jnsTindakan_id: $(this).find('.source-action-select').val()
                };
            }).get().filter((row) => row.source_pattern && row.jnsTindakan_id);
        }

        function saveConfig() {
            const button = $('#btnSaveConfigPremiBersama');
            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...');

            $.ajax({
                url: routes.updateConfig,
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': csrf
                },
                data: {
                    jnsPremi_umum_id: $('#configMappingUmum').val(),
                    jnsPremi_bpjs_id: $('#configMappingBpjs').val(),
                    ugd_plotingPremi_id: $('#configUgdPloting').val(),
                    vk_plotingPremi_id: $('#configVkPloting').val(),
                    kamar_plotingPremi_id: $('#configKamarPloting').val(),
                    bhp_plotingPremi_id: $('#configBhpPloting').val(),
                    bpjs_source_mode: $('#configBpjsSourceMode').val() || 'previous',
                    ignore_icu: $('#configIgnoreIcu').is(':checked') ? 1 : 0,
                    ignore_nicu: $('#configIgnoreNicu').is(':checked') ? 1 : 0,
                    included_umum_action_ids: $('#configIncludedActions').val() || [],
                    doctor_codes: $('#configDoctorCodes').val() || [],
                    doctor_tindakan_ids: $('#configDoctorActions').val() || [],
                    source_mappings: collectSourceMappings()
                }
            }).done((response) => {
                configData = response.data;
                modalConfig.hide();
                notifySuccess(response.message || 'Konfigurasi berhasil disimpan.');
                loadSummary();
            }).fail((xhr) => notifyError(xhr, 'Gagal menyimpan konfigurasi.')).always(() => {
                button.prop('disabled', false).html('<i class="mdi mdi-content-save-outline"></i> Simpan Konfigurasi');
            });
        }

        function generatePremiBersama() {
            confirmAction('Generate Premi Bersama?', 'Snapshot baru akan dibuat untuk periode aktif.', () => {
                const button = $('#btnGeneratePremiBersama');
                button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span>Generate...');

                $.ajax({
                    url: routes.store,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf
                    },
                    data: {
                        periode: $('#periodePremiBersama').val(),
                        jenis_pelayanan: activeType
                    }
                }).done((response) => {
                    notifySuccess(response.message || 'Premi Bersama berhasil digenerate.');
                    table.ajax.reload(null, false);
                    loadSummary();
                }).fail((xhr) => {
                    notifyError(xhr, 'Gagal generate Premi Bersama.');
                    loadSummary();
                }).always(() => {
                    button.html('<i class="mdi mdi-play-circle-outline"></i> Generate UMUM');
                });
            });
        }

        function loadDetail(id) {
            $('#searchDetailDistributionBersama, #searchDetailMappingBersama, #searchDetailRawatBersama').val('');
            $('#filterDetailDistributionLimitBersama').val('25');
            $('#filterDetailRawatSourceBersama, #filterDetailRawatPelaksanaBersama').val('all');
            $('#detailPremiBersamaContent').addClass('d-none');
            $('#detailPremiBersamaLoading').removeClass('d-none');
            modalDetail.show();

            $.get(routeWithParam(routes.detail, id)).done((response) => {
                const data = response.data;
                renderDetailConcept(data);
            }).fail((xhr) => {
                $('#detailPremiBersamaLoading').addClass('d-none');
                notifyError(xhr, 'Gagal memuat detail Premi Bersama.');
            });
        }

        function toggleLock(id, locked) {
            const route = locked ? routes.unlock : routes.lock;
            const title = locked ? 'Buka kunci data?' : 'Kunci data?';
            const text = locked ?
                'Data yang dibuka dapat digenerate ulang.' :
                'Data terkunci menjadi snapshot final.';

            confirmAction(title, text, () => {
                $.ajax({
                    url: routeWithParam(route, id),
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf
                    }
                }).done((response) => {
                    notifySuccess(response.message || 'Status kunci berhasil diperbarui.');
                    table.ajax.reload(null, false);
                    loadSummary();
                }).fail((xhr) => notifyError(xhr, 'Gagal memperbarui status kunci.'));
            });
        }

        $('#periodePremiBersama').on('change', function() {
            table.ajax.reload();
            loadSummary();
        });

        $('.pb-type-btn:not(:disabled)').on('click', function() {
            activeType = $(this).data('type');
            $('.pb-type-btn').removeClass('active');
            $(this).addClass('active');
            table.ajax.reload();
            loadSummary();
        });

        $('#btnRefreshPremiBersama').on('click', function() {
            table.ajax.reload(null, false);
            loadSummary();
        });

        $('#rawatFilterSearch, #rawatFilterJenis, #rawatFilterData, #rawatFilterSort').on('input change', applyPreviewActionFilters);

        $('#btnResetRawatFilter').on('click', function() {
            resetActionFilters('rawat');
            applyPreviewActionFilters();
        });

        $('#distributionFilterSearch, #distributionFilterSort, #distributionFilterLimit').on('input change', applyPreviewDistributionFilters);

        $('#btnResetDistributionFilter').on('click', function() {
            resetDistributionFilters('distribution');
            applyPreviewDistributionFilters();
        });

        $('#detailActionFilterSearch, #detailActionFilterJenis, #detailActionFilterData, #detailActionFilterSort').on('input change', applyDetailActionFilters);

        $('#btnResetDetailActionFilter').on('click', function() {
            resetActionFilters('detailAction');
            applyDetailActionFilters();
        });

        $('#detailDistributionFilterSearch, #detailDistributionFilterSort, #detailDistributionFilterLimit').on('input change', applyDetailDistributionFilters);

        $('#btnResetDetailDistributionFilter').on('click', function() {
            resetDistributionFilters('detailDistribution');
            applyDetailDistributionFilters();
        });

        $('#detailRawatFilterSearch, #detailRawatFilterSource, #detailRawatFilterProvider, #detailRawatFilterSort, #detailRawatFilterLimit').on('input change', applyDetailRawatFilters);

        $('#btnResetDetailRawatFilter').on('click', function() {
            resetDetailRawatFilters();
            applyDetailRawatFilters();
        });

        $('#searchDetailDistributionBersama, #filterDetailDistributionLimitBersama').on('input change', renderDetailDistributionConcept);

        $('#searchDetailMappingBersama').on('input', renderDetailMappingRowsConcept);

        $('#detailMappingRowsBersama').on('click', '.detail-mapping-row-bersama', function() {
            selectedDetailMappingId = String($(this).data('id') || '');
            $('#filterDetailMappingBersama').val(selectedDetailMappingId);
            renderDetailMappingRowsConcept();
            renderDetailRawatConcept();
        });

        $('#filterDetailMappingBersama').on('change', function() {
            selectedDetailMappingId = String($(this).val() || '');
            renderDetailMappingRowsConcept();
            renderDetailRawatConcept();
        });

        $('#filterDetailRawatSourceBersama, #filterDetailRawatPelaksanaBersama').on('change', renderDetailRawatConcept);

        $('#searchDetailRawatBersama').on('input', renderDetailRawatConcept);

        $('#detailSourceRowsBersama').on('click', '.detail-source-row-bersama', function() {
            $('#filterDetailRawatSourceBersama').val(String($(this).data('source') || 'all'));
            renderDetailRawatConcept();
        });

        $('#btnConfigPremiBersama').on('click', function() {
            setConfigLoading(true, 'Menyiapkan konfigurasi...', 'Modal dibuka dulu, data konfigurasi sedang dimuat.');
            modalConfig.show();
            loadConfigDependencies();
        });

        $('#configMappingUmum, #configMappingBpjs').on('change', function() {
            setConfigLoading(true, 'Memuat tindakan mapping...', 'Menyesuaikan daftar tindakan dari mapping yang dipilih.');
            configData.jnsPremi_umum_id = $('#configMappingUmum').val();
            configData.jnsPremi_bpjs_id = $('#configMappingBpjs').val();
            configData.included_umum_action_ids = [];
            configData.source_mappings = [];
            configData.doctor_filter = {
                selected_action_ids: [],
                selected_codes: $('#configDoctorCodes').val() || []
            };
            loadActionOptions(selectedPremiIds(), () => setConfigLoading(false), () => setConfigLoading(false));
        });

        $('#modalConfigPremiBersama').on('change', 'select, input[type="checkbox"]', updateConfigSummary);

        $('#btnAddSourceMapping').on('click', function() {
            addSourceMappingRow();
        });

        $('#sourceMappingRows').on('change', '.source-pattern-select, .source-action-select', updateConfigSummary);

        $('#sourceMappingRows').on('click', '.btn-remove-source-mapping', function() {
            $(this).closest('.pb-source-map-row').remove();
            if ($('#sourceMappingRows .pb-source-map-row').length === 0) {
                $('#sourceMappingRows').html('<div class="pb-empty mb-2">Belum ada mapping sumber khusus.</div>');
            }
            updateConfigSummary();
        });

        $('#formConfigPremiBersama').on('submit', function(event) {
            event.preventDefault();
            saveConfig();
        });

        $('#btnGeneratePremiBersama').on('click', generatePremiBersama);

        $('#tablePremiBersama').on('click', '.btn-detail-premi-bersama', function() {
            loadDetail($(this).data('id'));
        });

        $('#tablePremiBersama').on('click', '.btn-lock-premi-bersama', function() {
            toggleLock($(this).data('id'), false);
        });

        $('#tablePremiBersama').on('click', '.btn-unlock-premi-bersama', function() {
            toggleLock($(this).data('id'), true);
        });

        renderEmptyPreview();
        loadSummary();
    });
</script>
