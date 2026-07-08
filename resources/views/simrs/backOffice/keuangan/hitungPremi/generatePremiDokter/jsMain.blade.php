<script>
    $(function() {
        const routes = {
            table: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDokter.table") }}",
            summary: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDokter.summary") }}",
            store: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDokter.store") }}",
            config: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDokter.config") }}",
            updateConfig: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDokter.updateConfig") }}",
            mappingOptions: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDokter.mappingTindakanOptions") }}",
            dokterOptions: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDokter.dokterOptions") }}",
            detail: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDokter.detail", ":id") }}",
            lock: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDokter.lock", ":id") }}",
            unlock: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDokter.unlock", ":id") }}",
        };
        const categoryDefaults = {
            umum: { label: 'Dokter Umum', percent: 50, select: '#doctorSelectUmum', rows: '#doctorRowsUmum' },
            spesialis_65: { label: 'Dokter Spesialis 65%', percent: 65, select: '#doctorSelectSpesialis65', rows: '#doctorRowsSpesialis65' },
            spesialis_80: { label: 'Dokter Spesialis 80%', percent: 80, select: '#doctorSelectSpesialis80', rows: '#doctorRowsSpesialis80' },
            kebersamaan: { label: 'Dokter Kebersamaan', percent: 0, select: '#doctorSelectKebersamaan', rows: '#doctorRowsKebersamaan', usePercent: false },
        };
        const csrf = $('meta[name="csrf-token"]').attr('content');
        const modalConfig = new bootstrap.Modal(document.getElementById('modalConfigPremiDokter'));
        const modalDetail = new bootstrap.Modal(document.getElementById('modalDetailPremiDokter'));
        let activePremiumType = 'visite';
        let activeType = 'umum';
        let configData = null;
        let currentSummary = null;
        let currentDetailRows = [];
        let currentRawatDetail = null;
        let currentRawatRows = [];

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrf
            }
        });

        function routeWithId(route, id) {
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

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function activeTypeLabel() {
            return activeType === 'bpjs' ? 'BPJS' : 'UMUM';
        }

        function activePremiumLabel() {
            return activePremiumType === 'kebersamaan' ? 'Kebersamaan' : 'Jasa Visite';
        }

        function isKebersamaan() {
            return activePremiumType === 'kebersamaan';
        }

        function generateButtonLabel() {
            return isKebersamaan() ? 'Generate Kebersamaan' : `Generate ${activeTypeLabel()}`;
        }

        function sourcePeriodText(data) {
            if (data.source_period_text) {
                return data.source_period_text;
            }

            return `Sumber ${data.source_periode || '-'} (${data.source_period_mode_label || '-'})`;
        }

        function normalizeText(value) {
            return String(value ?? '').toLowerCase().trim();
        }

        function sourceLabel(row) {
            return row.source_label || row.source_table || row.sumber_tindakan || '-';
        }

        function actionLabel(row) {
            return `${row.kd_tindakan || '-'} - ${row.nm_tindakan || '-'}`;
        }

        function penjaminLabel(row) {
            const code = row.kd_pj || '-';
            const name = row.nama_penjamin || '-';
            return `${code} - ${name}`;
        }

        function topBreakdown(rows, keyResolver, labelResolver) {
            const map = new Map();

            (rows || []).forEach((row) => {
                const key = keyResolver(row) || '-';
                const current = map.get(key) || {
                    key,
                    label: labelResolver(row),
                    count: 0,
                    total: 0
                };
                current.count += 1;
                current.total += Number(row.biaya_rawat || 0);
                map.set(key, current);
            });

            return Array.from(map.values())
                .sort((a, b) => b.count - a.count || b.total - a.total)[0] || null;
        }

        function summarizeDetailBreakdown(items) {
            const first = (items || [])[0];

            if (!first) {
                return '-';
            }

            return `${first.label || first.key || '-'} (${formatNumber(first.jumlah_data || 0)} data)`;
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

        function initSelect2() {
            if (!$.fn.select2) {
                return;
            }

            $('#configMappingTindakan').select2({
                dropdownParent: $('#modalConfigPremiDokter'),
                placeholder: 'Cari Master Mapping Tindakan',
                width: '100%',
                ajax: {
                    url: routes.mappingOptions,
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term || ''
                    }),
                    processResults: response => ({
                        results: (response.data || []).map(item => ({
                            id: item.id,
                            text: item.text,
                            item
                        }))
                    })
                }
            });

            $('.pd-doctor-select').each(function() {
                $(this).select2({
                    dropdownParent: $('#modalConfigPremiDokter'),
                    placeholder: 'Cari dokter',
                    width: '100%',
                    ajax: {
                        url: routes.dokterOptions,
                        dataType: 'json',
                        delay: 250,
                        data: params => ({ q: params.term || '' }),
                        processResults: response => ({
                            results: (response.data || []).map(item => ({
                                id: item.id,
                                text: item.text,
                                item
                            }))
                        })
                    }
                });
            });
        }

        function setSelectedOptions(selector, items) {
            const select = $(selector);
            select.empty();

            (items || []).forEach((item) => {
                const option = new Option(item.text || item.nm_dokter || item.nm_tindakan || item.id, item.id, true, true);
                select.append(option);
            });

            select.trigger('change');
        }

        function doctorSelectedItems(category) {
            const select = $(categoryDefaults[category].select);
            const configured = new Map((configData?.doctor_configs || [])
                .filter(item => item.kategori === category)
                .map(item => [String(item.kd_dokter), item]));

            return (select.val() || []).map((code) => {
                const option = select.find('option').filter(function() {
                    return String(this.value) === String(code);
                });
                const saved = configured.get(String(code)) || {};
                const existingInput = $('.doctor-percent').filter(function() {
                    return $(this).data('category') === category && String($(this).data('code')) === String(code);
                });
                const existingPercent = existingInput.length ? Number(existingInput.val()) : null;

                return {
                    kd_dokter: code,
                    text: option.text() || saved.text || code,
                    percent: Number.isFinite(existingPercent) ? existingPercent : (saved.percent ?? categoryDefaults[category].percent),
                    nm_sps: saved.nm_sps || ''
                };
            });
        }

        function renderDoctorRows(category) {
            const target = $(categoryDefaults[category].rows);
            const rows = doctorSelectedItems(category);
            const meta = categoryDefaults[category];

            if (!rows.length) {
                target.html('<div class="text-muted small mt-2">Belum ada dokter dipilih.</div>');
                return;
            }

            target.html(rows.map((item) => `
                <div class="pd-doctor-row">
                    <div>
                        <div class="pd-doctor-name">${escapeHtml(item.text)}</div>
                        <div class="pd-doctor-meta">${escapeHtml(meta.label)}</div>
                    </div>
                    ${meta.usePercent === false
                        ? '<div class="text-end text-muted small">Dibagi dari formula</div>'
                        : `<div class="input-group input-group-sm">
                            <input type="number" class="form-control doctor-percent"
                                data-category="${escapeHtml(category)}"
                                data-code="${escapeHtml(item.kd_dokter)}"
                                min="0" max="100" step="0.0001" value="${escapeHtml(item.percent)}">
                            <span class="input-group-text">%</span>
                        </div>`}
                </div>
            `).join(''));
        }

        function fillConfig(data) {
            configData = data;
            $('#configVisiteUmumPercent').val(data.visite_umum_percent || 50);
            $('#configVisiteBpjsPercent').val(data.visite_bpjs_percent || 50);
            $('#configVisiteBpjsNominal').val(data.visite_bpjs_nominal || 0);
            $('#configSourcePeriodMode').val(data.source_period_mode || 'current');
            $('#configKebersamaanUmumPercent').val(data.kebersamaan_umum_percent || 30);
            $('#configKebersamaanBpjsNominal').val(data.kebersamaan_bpjs_nominal || 40000);
            $('#configKebersamaanBpjsPercent').val(data.kebersamaan_bpjs_percent || 30);
            $('#configKebersamaanDivider').val(data.kebersamaan_divider || 4);
            setSelectedOptions('#configMappingTindakan', data.mapping_tindakan || []);

            Object.entries(categoryDefaults).forEach(([category, meta]) => {
                const items = (data.doctor_configs || [])
                    .filter(item => item.kategori === category)
                    .map(item => ({
                        id: item.kd_dokter,
                        text: `${item.kd_dokter} - ${item.nm_dokter} (${item.nm_sps || meta.label})`
                    }));
                setSelectedOptions(meta.select, items);
                renderDoctorRows(category);
            });
        }

        function loadConfig(openModal = false) {
            return $.get(routes.config)
                .done((response) => {
                    fillConfig(response.data || {});
                    if (openModal) {
                        modalConfig.show();
                    }
                })
                .fail((xhr) => notifyError(xhr, 'Konfigurasi gagal dimuat.'));
        }

        function collectDoctorConfigs() {
            const rows = [];

            Object.entries(categoryDefaults).forEach(([category, meta]) => {
                ($(meta.select).val() || []).forEach((code) => {
                    const input = $('.doctor-percent').filter(function() {
                        return $(this).data('category') === category && String($(this).data('code')) === String(code);
                    });
                    rows.push({
                        kategori: category,
                        kd_dokter: code,
                        percent: Number(input.val() || meta.percent)
                    });
                });
            });

            return rows;
        }

        function saveConfig() {
            const payload = {
                visite_umum_percent: Number($('#configVisiteUmumPercent').val() || 50),
                visite_bpjs_percent: Number($('#configVisiteBpjsPercent').val() || 50),
                visite_bpjs_nominal: Number($('#configVisiteBpjsNominal').val() || 0),
                source_period_mode: $('#configSourcePeriodMode').val() || 'current',
                kebersamaan_umum_percent: Number($('#configKebersamaanUmumPercent').val() || 30),
                kebersamaan_bpjs_nominal: Number($('#configKebersamaanBpjsNominal').val() || 40000),
                kebersamaan_bpjs_percent: Number($('#configKebersamaanBpjsPercent').val() || 30),
                kebersamaan_divider: Number($('#configKebersamaanDivider').val() || 4),
                mapping_tindakan_ids: $('#configMappingTindakan').val() || [],
                doctor_configs: collectDoctorConfigs()
            };

            $('#btnSaveConfigPremiDokter').prop('disabled', true);

            $.ajax({
                url: routes.updateConfig,
                method: 'PUT',
                data: payload
            })
                .done((response) => {
                    fillConfig(response.data || {});
                    modalConfig.hide();
                    notifySuccess(response.message || 'Konfigurasi berhasil disimpan.');
                    refreshSummary();
                    table.ajax.reload(null, false);
                })
                .fail((xhr) => notifyError(xhr, 'Konfigurasi gagal disimpan.'))
                .always(() => $('#btnSaveConfigPremiDokter').prop('disabled', false));
        }

        function renderSteps(steps) {
            $('#readinessSteps').html((steps || []).map((step) => `
                <div class="pd-step ${escapeHtml(step.status || 'warning')}">
                    <div class="pd-step-label">${escapeHtml(step.label)}</div>
                    <div class="pd-step-value">${escapeHtml(step.value)}</div>
                </div>
            `).join(''));
        }

        function renderPreviewRows(details) {
            if (!details || !details.length) {
                $('#previewPremiDokterRows').html(`
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada data dokter untuk preview ini.</td>
                    </tr>
                `);
                return;
            }

            $('#previewPremiDokterRows').html(details.map((row) => `
                <tr>
                    <td>
                        <strong>${escapeHtml(row.nm_dokter || '-')}</strong>
                        <div class="text-muted small">${escapeHtml(row.kd_dokter || '-')} ${row.nm_sps ? '- ' + escapeHtml(row.nm_sps) : ''}</div>
                    </td>
                    <td>${escapeHtml(row.kategori_label || row.kategori || '-')}</td>
                    <td class="text-end">${formatNumber(row.jumlah_data || 0)}</td>
                    <td class="text-end">${formatRupiah(row.grand_total || 0)}</td>
                    <td class="text-end">${formatNumber(row.percent || 0, 2)}%</td>
                    <td class="text-end"><strong>${formatRupiah(row.total_premi || 0)}</strong></td>
                </tr>
            `).join(''));
        }

        function renderSummary(data) {
            currentSummary = data;
            $('#previewPanelTitle').text(`Preview ${activePremiumLabel()}`);
            $('#historyPanelTitle').text(`History Generate Premi Dokter ${activePremiumLabel()}`);
            $('#summaryTotalPremi').text(formatRupiah(data.total_premi || 0));
            $('#summaryGrandTotal').text(formatRupiah(data.total_grand || 0));
            $('#summaryTransaksi').text(formatNumber(data.jumlah_transaksi || 0));
            $('#summaryPasien').text(formatNumber(data.jumlah_pasien || 0) + ' pasien');
            $('#summaryDokter').text(formatNumber(data.jumlah_dokter || 0));
            $('#summaryMapping').text(formatNumber(data.jumlah_mapping_tindakan || 0));
            $('#summarySkipped').text(isKebersamaan()
                ? formatNumber(data.kebersamaan_divider || 0)
                : formatNumber(data.jumlah_tidak_terkonfigurasi || 0));
            $('#summarySkippedFoot').text(isKebersamaan()
                ? `Pembagi, alokasi ${formatRupiah(data.kebersamaan_allocation_per_doctor || 0)} per dokter`
                : activeType === 'bpjs'
                ? `${formatNumber(data.jumlah_spesialis_diabaikan || 0)} data spesialis BPJS diabaikan`
                : 'Baris dokter belum dikonfigurasi');
            $('#summaryMessage').text(data.readiness_message || '-');
            $('#summaryFormula').text(isKebersamaan()
                ? `${formatRupiah(data.kebersamaan_visite_umum_total_premi || 0)} x ${formatNumber(data.kebersamaan_umum_percent || 0, 2)}% + ${formatNumber(data.kebersamaan_visite_bpjs_jumlah_transaksi || 0)} transaksi x ${formatRupiah(data.kebersamaan_bpjs_nominal || 0)} x ${formatNumber(data.kebersamaan_bpjs_percent || 0, 2)}%`
                : activeType === 'bpjs'
                ? `${formatNumber(data.jumlah_transaksi || 0)} data x ${formatRupiah(data.visite_bpjs_nominal || 0)} x ${formatNumber(data.visite_bpjs_percent || 0, 2)}% | ${sourcePeriodText(data)}`
                : `Biaya rawat x persen kategori dokter | ${sourcePeriodText(data)}`);
            $('#btnGeneratePremiDokter')
                .prop('disabled', !data.ready)
                .html(`<i class="mdi mdi-play-circle-outline"></i> ${generateButtonLabel()}`);
            renderSteps(data.readiness_steps || []);
            renderPreviewRows(data.details || []);
        }

        function refreshSummary() {
            const periode = $('#periodePremiDokter').val();

            if (!periode) {
                return;
            }

            $('#summaryMessage').text('Memuat preview...');
            $('#btnGeneratePremiDokter').prop('disabled', true);

            const payload = {
                periode,
                jenis_premi_dokter: activePremiumType
            };

            if (!isKebersamaan()) {
                payload.jenis_pelayanan = activeType;
            }

            $.get(routes.summary, payload)
                .done((response) => renderSummary(response.data || {}))
                .fail((xhr) => {
                    notifyError(xhr, 'Preview gagal dimuat.');
                    renderPreviewRows([]);
                });
        }

        function generatePremiDokter() {
            if (!currentSummary?.ready) {
                notifyError({ responseJSON: { message: currentSummary?.readiness_message || 'Preview belum siap.' } });
                return;
            }

            confirmAction(
                'Generate Premi Dokter?',
                `Data ${activePremiumLabel()}${isKebersamaan() ? '' : ' ' + activeTypeLabel()} periode ${$('#periodePremiDokter').val()} akan disimpan.`,
                () => {
                    $('#btnGeneratePremiDokter').prop('disabled', true);

                    const payload = {
                        periode: $('#periodePremiDokter').val(),
                        jenis_premi_dokter: activePremiumType
                    };

                    if (!isKebersamaan()) {
                        payload.jenis_pelayanan = activeType;
                    }

                    $.post(routes.store, payload)
                        .done((response) => {
                            notifySuccess(response.message || 'Premi dokter berhasil digenerate.');
                            refreshSummary();
                            table.ajax.reload(null, false);
                        })
                        .fail((xhr) => notifyError(xhr, 'Generate gagal diproses.'))
                        .always(() => $('#btnGeneratePremiDokter').prop('disabled', !(currentSummary?.ready)));
                }
            );
        }

        const table = $('#tablePremiDokter').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: routes.table,
                data: function(data) {
                    data.periode = $('#periodePremiDokter').val();
                    data.jenis_premi_dokter = activePremiumType;

                    if (!isKebersamaan()) {
                        data.jenis_pelayanan = activeType;
                    }
                }
            },
            order: [[1, 'desc']],
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'periode' },
                {
                    data: 'jenis_pelayanan_label',
                    render: (value, type, row) => `${escapeHtml(row.jenis_premi_dokter_label || 'Jasa Visite')}<br><span class="text-muted">${row.jenis_premi_dokter === 'kebersamaan' ? escapeHtml(row.source_period_text || row.source_periode || '-') : `${escapeHtml(value || '-')} / sumber ${escapeHtml(row.source_periode || '-')}`}</span>`
                },
                { data: 'jumlah_dokter', className: 'text-end', render: value => formatNumber(value || 0) },
                { data: 'jumlah_transaksi', className: 'text-end', render: value => formatNumber(value || 0) },
                { data: 'total_premi', className: 'text-end', render: value => `<strong>${formatRupiah(value || 0)}</strong>` },
                {
                    data: 'is_locked',
                    render: value => value
                        ? '<span class="badge bg-warning text-dark">Terkunci</span>'
                        : '<span class="badge bg-success">Terbuka</span>'
                },
                { data: 'actions', orderable: false, searchable: false },
            ]
        });

        function renderDetailRows(details) {
            currentDetailRows = details || [];
            $('#detailDoctorCountPill').text(`${formatNumber(currentDetailRows.length)} dokter`);

            if (!details || !details.length) {
                $('#detailPremiDokterRows').html('<div class="text-muted">Tidak ada detail dokter.</div>');
                renderRawatRows(null);
                return;
            }

            $('#detailPremiDokterRows').html(details.map((row, index) => `
                <div class="pd-detail-card ${index === 0 ? 'active' : ''}">
                    <div class="pd-detail-card-title">${escapeHtml(row.nm_dokter || '-')}</div>
                    <div class="pd-detail-card-meta">${escapeHtml(row.kd_dokter || '-')} / ${escapeHtml(row.kategori_label || '-')} / ${formatNumber(row.percent || 0, 2)}%</div>
                    <div class="pd-detail-card-value">${formatRupiah(row.total_premi || 0)}</div>
                    <div class="pd-detail-card-kpis">
                        <div class="pd-detail-card-kpi">
                            <span>Data</span>
                            <strong>${formatNumber(row.jumlah_data || 0)} / ${formatNumber(row.jumlah_pasien || 0)} pasien</strong>
                        </div>
                        <div class="pd-detail-card-kpi">
                            <span>Grand</span>
                            <strong>${formatRupiah(row.grand_total || 0)}</strong>
                        </div>
                        <div class="pd-detail-card-kpi">
                            <span>Sumber atas</span>
                            <strong>${escapeHtml(summarizeDetailBreakdown(row.source_breakdown || []))}</strong>
                        </div>
                        <div class="pd-detail-card-kpi">
                            <span>Tindakan atas</span>
                            <strong>${escapeHtml(summarizeDetailBreakdown(row.action_breakdown || []))}</strong>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 btn-show-rawat"
                        data-index="${index}">
                        <i class="mdi mdi-table-search"></i> Lihat Rawat
                    </button>
                </div>
            `).join(''));
            renderRawatRows(details[0]);
        }

        function renderRawatRows(detail) {
            currentRawatDetail = detail || null;
            currentRawatRows = detail?.data_rawat || [];
            buildRawatFilterOptions(currentRawatRows);
            resetRawatFilterControls(false);
            applyRawatFilters();
        }

        function resetRawatFilterControls(apply = true) {
            $('#detailRawatSearch').val('');
            $('#detailFilterSource').val('');
            $('#detailFilterTindakan').val('');
            $('#detailFilterPenjamin').val('');
            $('#detailFilterDateStart').val('');
            $('#detailFilterDateEnd').val('');

            if (apply) {
                applyRawatFilters();
            }
        }

        function buildRawatFilterOptions(rows) {
            fillRawatSelect('#detailFilterSource', 'Semua sumber', rows, row => row.source_table, sourceLabel);
            fillRawatSelect('#detailFilterTindakan', 'Semua tindakan', rows, row => row.kd_tindakan, actionLabel);
            fillRawatSelect('#detailFilterPenjamin', 'Semua penjamin', rows, row => row.kd_pj, penjaminLabel);
        }

        function fillRawatSelect(selector, placeholder, rows, keyResolver, labelResolver) {
            const map = new Map();

            (rows || []).forEach((row) => {
                const key = keyResolver(row) || '';

                if (!key) {
                    return;
                }

                const current = map.get(key) || {
                    value: key,
                    label: labelResolver(row),
                    count: 0
                };
                current.count += 1;
                map.set(key, current);
            });

            const options = Array.from(map.values())
                .sort((a, b) => a.label.localeCompare(b.label, 'id'));
            const html = [`<option value="">${escapeHtml(placeholder)}</option>`]
                .concat(options.map((item) => `
                    <option value="${escapeHtml(item.value)}">
                        ${escapeHtml(item.label)} (${formatNumber(item.count)})
                    </option>
                `));

            $(selector).html(html.join(''));
        }

        function rawatFilterValues() {
            return {
                search: normalizeText($('#detailRawatSearch').val()),
                source: $('#detailFilterSource').val() || '',
                tindakan: $('#detailFilterTindakan').val() || '',
                penjamin: $('#detailFilterPenjamin').val() || '',
                dateStart: $('#detailFilterDateStart').val() || '',
                dateEnd: $('#detailFilterDateEnd').val() || '',
            };
        }

        function rowMatchesRawatFilter(row, filters) {
            if (filters.source && String(row.source_table || '') !== String(filters.source)) {
                return false;
            }

            if (filters.tindakan && String(row.kd_tindakan || '') !== String(filters.tindakan)) {
                return false;
            }

            if (filters.penjamin && String(row.kd_pj || '') !== String(filters.penjamin)) {
                return false;
            }

            if (filters.dateStart && String(row.tanggal || '') < filters.dateStart) {
                return false;
            }

            if (filters.dateEnd && String(row.tanggal || '') > filters.dateEnd) {
                return false;
            }

            if (filters.search) {
                const searchable = [
                    row.no_rawat,
                    row.no_rkm_medis,
                    row.nm_pasien,
                    row.kd_tindakan,
                    row.nm_tindakan,
                    row.kd_dokter,
                    row.nm_dokter,
                    row.nip,
                    row.nama_petugas,
                    row.kd_pj,
                    row.nama_penjamin,
                    row.source_table,
                    row.source_label,
                ].map(normalizeText).join(' ');

                if (!searchable.includes(filters.search)) {
                    return false;
                }
            }

            return true;
        }

        function selectedOptionText(selector) {
            const option = $(`${selector} option:selected`);

            return option.val() ? option.text().trim() : '';
        }

        function renderActiveFilterPills(filters) {
            const pills = [];

            if (filters.search) {
                pills.push(`Cari: ${filters.search}`);
            }

            if (filters.source) {
                pills.push(`Sumber: ${selectedOptionText('#detailFilterSource')}`);
            }

            if (filters.tindakan) {
                pills.push(`Tindakan: ${selectedOptionText('#detailFilterTindakan')}`);
            }

            if (filters.penjamin) {
                pills.push(`Penjamin: ${selectedOptionText('#detailFilterPenjamin')}`);
            }

            if (filters.dateStart || filters.dateEnd) {
                pills.push(`Tanggal: ${filters.dateStart || 'awal'} sampai ${filters.dateEnd || 'akhir'}`);
            }

            $('#detailActiveFilters').html(pills.length
                ? pills.map((item) => `<span class="pd-filter-pill">${escapeHtml(item)}</span>`).join('')
                : '<span class="pd-filter-pill">Semua data dokter terpilih</span>');
        }

        function updateRawatInsights(filteredRows, visibleCount, filters) {
            const topSource = topBreakdown(filteredRows, row => row.source_table, sourceLabel);
            const topAction = topBreakdown(filteredRows, row => row.kd_tindakan, actionLabel);
            const totalBiaya = filteredRows.reduce((sum, row) => sum + Number(row.biaya_rawat || 0), 0);

            $('#detailFilteredCount').text(`${formatNumber(filteredRows.length)} data / tampil ${formatNumber(visibleCount)}`);
            $('#detailFilteredTotal').text(formatRupiah(totalBiaya));
            $('#detailTopSource').text(topSource ? `${topSource.label} (${formatNumber(topSource.count)})` : '-');
            $('#detailTopAction').text(topAction ? `${topAction.label} (${formatNumber(topAction.count)})` : '-');
            renderActiveFilterPills(filters);
        }

        function applyRawatFilters() {
            if (!currentRawatDetail) {
                $('#detailRawatTitle').text('Data Rawat Tersimpan');
                $('#detailRawatNote').text('Pilih dokter untuk melihat data rawat.');
                $('#detailFilteredCount').text('0 data');
                $('#detailFilteredTotal').text('Rp 0');
                $('#detailTopSource').text('-');
                $('#detailTopAction').text('-');
                $('#detailActiveFilters').empty();
                $('#detailRawatRows').html(`
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Pilih dokter untuk melihat data rawat.</td>
                    </tr>
                `);
                return;
            }

            const filters = rawatFilterValues();
            const filteredRows = currentRawatRows.filter((row) => rowMatchesRawatFilter(row, filters));
            const visibleRows = filteredRows.slice(0, 300);
            $('#detailRawatTitle').text(currentRawatDetail.nm_dokter || 'Data Rawat Tersimpan');
            $('#detailRawatNote').text(`${formatNumber(currentRawatRows.length)} data tersimpan, ${formatNumber(filteredRows.length)} sesuai filter${filteredRows.length > visibleRows.length ? ', menampilkan 300 data pertama' : ''}`);
            updateRawatInsights(filteredRows, visibleRows.length, filters);

            if (!visibleRows.length) {
                $('#detailRawatRows').html(`
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Tidak ada data rawat yang sesuai filter.</td>
                    </tr>
                `);
                return;
            }

            $('#detailRawatRows').html(visibleRows.map((row) => `
                <tr>
                    <td>
                        <strong>${escapeHtml(row.source_table || '-')}</strong>
                        <div class="text-muted small">${escapeHtml(row.source_label || row.sumber_tindakan || '-')}</div>
                    </td>
                    <td>
                        ${escapeHtml(row.tanggal || '-')}
                        <div class="text-muted small">${escapeHtml(row.jam || '-')}</div>
                    </td>
                    <td>
                        <strong>${escapeHtml(row.no_rawat || '-')}</strong>
                        <div class="text-muted small">${escapeHtml(row.nm_pasien || row.no_rkm_medis || '-')}</div>
                    </td>
                    <td>
                        ${escapeHtml(row.kd_tindakan || '-')}
                        <div class="text-muted small">${escapeHtml(row.nm_tindakan || '-')}</div>
                    </td>
                    <td>
                        ${escapeHtml(row.kd_pj || '-')}
                        <div class="text-muted small">${escapeHtml(row.nama_penjamin || '-')}</div>
                    </td>
                    <td>
                        ${escapeHtml(row.nip || '-')}
                        <div class="text-muted small">${escapeHtml(row.nama_petugas || row.nm_dokter || row.doctor_source || '-')}</div>
                    </td>
                    <td class="text-end">${formatRupiah(row.biaya_rawat || 0)}</td>
                </tr>
            `).join(''));
        }

        function openDetail(id) {
            $('#detailPremiDokterMeta').text('Memuat detail...');
            $('#detailPremiDokterRows').html('<div class="text-muted">Memuat detail...</div>');
            renderRawatRows(null);
            modalDetail.show();

            $.get(routeWithId(routes.detail, id))
                .done((response) => {
                    const data = response.data || {};
                    const serviceLabel = data.jenis_premi_dokter === 'kebersamaan'
                        ? ''
                        : ` ${data.jenis_pelayanan_label || '-'}`;
                    $('#detailPremiDokterMeta').text(`${data.jenis_premi_dokter_label || 'Jasa Visite'}${serviceLabel} periode ${data.periode || '-'} / ${sourcePeriodText(data)}`);
                    $('#detailTotalPremi').text(formatRupiah(data.total_premi || 0));
                    $('#detailGrandTotal').text(formatRupiah(data.total_grand || 0));
                    $('#detailJumlahDokter').text(formatNumber(data.jumlah_dokter || 0));
                    $('#detailJumlahTransaksi').text(formatNumber(data.jumlah_transaksi || 0));
                    $('#detailJumlahMapping').text(formatNumber(data.jumlah_mapping_tindakan || 0));
                    $('#detailStatus').text(data.is_locked ? 'Terkunci' : 'Terbuka');
                    renderDetailRows(data.details || []);
                })
                .fail((xhr) => {
                    notifyError(xhr, 'Detail gagal dimuat.');
                    modalDetail.hide();
                });
        }

        function lockItem(id, unlock = false) {
            confirmAction(
                unlock ? 'Buka kunci data?' : 'Kunci data?',
                unlock ? 'Data bisa digenerate ulang setelah kunci dibuka.' : 'Data terkunci tidak bisa digenerate ulang.',
                () => {
                    $.post(routeWithId(unlock ? routes.unlock : routes.lock, id))
                        .done((response) => {
                            notifySuccess(response.message || 'Status data diperbarui.');
                            table.ajax.reload(null, false);
                            refreshSummary();
                        })
                        .fail((xhr) => notifyError(xhr, 'Status data gagal diperbarui.'));
                }
            );
        }

        function updatePremiumUi() {
            $('#jenisPelayananSwitch').toggle(!isKebersamaan());
            $('#premiDokterTypeGrid .pd-type-card').removeClass('active');
            $(`#premiDokterTypeGrid .pd-type-card[data-premi-type="${activePremiumType}"]`).addClass('active');
            $('#btnGeneratePremiDokter').html(`<i class="mdi mdi-play-circle-outline"></i> ${generateButtonLabel()}`);
            $('#previewPanelTitle').text(`Preview ${activePremiumLabel()}`);
            $('#historyPanelTitle').text(`History Generate Premi Dokter ${activePremiumLabel()}`);
        }

        $('.pd-type-btn').on('click', function() {
            activeType = $(this).data('type');
            $('.pd-type-btn').removeClass('active');
            $(this).addClass('active');
            $('#btnGeneratePremiDokter').html(`<i class="mdi mdi-play-circle-outline"></i> ${generateButtonLabel()}`);
            refreshSummary();
            table.ajax.reload();
        });

        $('#premiDokterTypeGrid').on('click', '.pd-type-card[data-premi-type]', function() {
            activePremiumType = $(this).data('premi-type');
            updatePremiumUi();
            refreshSummary();
            table.ajax.reload();
        });

        $('#periodePremiDokter').on('change', function() {
            refreshSummary();
            table.ajax.reload();
        });

        $('#btnConfigPremiDokter').on('click', () => loadConfig(true));
        $('#btnGeneratePremiDokter').on('click', generatePremiDokter);
        $('#formConfigPremiDokter').on('submit', function(event) {
            event.preventDefault();
            saveConfig();
        });
        $('.pd-doctor-select').on('change', function() {
            renderDoctorRows($(this).data('category'));
        });
        $('#tablePremiDokter').on('click', '.btn-detail-premi-dokter', function() {
            openDetail($(this).data('id'));
        });
        $('#tablePremiDokter').on('click', '.btn-lock-premi-dokter', function() {
            lockItem($(this).data('id'), false);
        });
        $('#tablePremiDokter').on('click', '.btn-unlock-premi-dokter', function() {
            lockItem($(this).data('id'), true);
        });
        $('#detailPremiDokterRows').on('click', '.btn-show-rawat', function() {
            const index = Number($(this).data('index'));
            $('#detailPremiDokterRows .pd-detail-card').removeClass('active');
            $(this).closest('.pd-detail-card').addClass('active');
            renderRawatRows(currentDetailRows[index] || null);
        });
        $('#detailRawatSearch, #detailFilterSource, #detailFilterTindakan, #detailFilterPenjamin, #detailFilterDateStart, #detailFilterDateEnd')
            .on('input change', applyRawatFilters);
        $('#btnResetRawatFilter').on('click', () => resetRawatFilterControls(true));

        initSelect2();
        updatePremiumUi();
        loadConfig(false).always(refreshSummary);
    });
</script>
