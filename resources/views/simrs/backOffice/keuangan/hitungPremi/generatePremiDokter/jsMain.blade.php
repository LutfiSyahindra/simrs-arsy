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
            kebersamaan: { label: 'Dokter Kebersamaan', percent: 0, select: '#doctorSelectKebersamaan', rows: '#doctorRowsKebersamaan', usePercent: false, fixedText: 'Dibagi dari formula' },
            jasa_operasi: { label: 'Dokter Operasi', percent: 100, select: '#doctorSelectOperasi', rows: '#doctorRowsOperasi' },
            jasa_rawat_jalan: { label: 'Dokter Rawat Jalan', percent: 0, select: '#doctorSelectRawatJalan', rows: '#doctorRowsRawatJalan', usePercent: false, fixedText: 'Dari pengkali mapping' },
            jasa_poli: { label: 'Dokter Penerima Poli', percent: 0, select: '#doctorSelectPoli', rows: '#doctorRowsPoli', usePercent: false, fixedText: 'Dari formula Poli' },
            jasa_ecg: { label: 'Dokter ECG', percent: 0, select: '#doctorSelectEcg', rows: '#doctorRowsEcg', usePercent: false, fixedText: 'Dibagi rata' },
            konsul_wa: { label: 'Dokter Konsul WA', percent: 0, select: '#doctorSelectKonsulWa', rows: '#doctorRowsKonsulWa', usePercent: false, fixedText: 'Nominal per data' },
        };
        const rawatJalanSpecialGroups = {
            rawat_jalan_khusus_45000: {
                label: 'Dokter Khusus 45.000',
                defaultNominal: 45000,
                nominal: '#configRawatJalanKhusus45000Nominal',
                select: '#doctorSelectRawatJalanKhusus45000',
                rows: '#doctorRowsRawatJalanKhusus45000'
            },
            rawat_jalan_khusus_72000: {
                label: 'Dokter Khusus 72.000',
                defaultNominal: 72000,
                nominal: '#configRawatJalanKhusus72000Nominal',
                select: '#doctorSelectRawatJalanKhusus72000',
                rows: '#doctorRowsRawatJalanKhusus72000'
            },
        };
        const csrf = $('meta[name="csrf-token"]').attr('content');
        const modalConfig = modalInstance('modalConfigPremiDokter');
        const modalDetail = modalInstance('modalDetailPremiDokter');
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

        function modalInstance(id) {
            const element = document.getElementById(id);
            const selector = '#' + id;

            function showWithNative() {
                if (!window.bootstrap || !bootstrap.Modal || !element) {
                    return false;
                }

                const instance = bootstrap.Modal.getOrCreateInstance
                    ? bootstrap.Modal.getOrCreateInstance(element)
                    : new bootstrap.Modal(element);

                instance.show();

                return true;
            }

            function hideWithNative() {
                if (!window.bootstrap || !bootstrap.Modal || !element) {
                    return false;
                }

                const instance = bootstrap.Modal.getInstance
                    ? bootstrap.Modal.getInstance(element)
                    : null;

                if (instance) {
                    instance.hide();

                    return true;
                }

                return false;
            }

            function showManually() {
                if (!element) {
                    return;
                }

                element.style.display = 'block';
                element.removeAttribute('aria-hidden');
                element.setAttribute('aria-modal', 'true');
                element.setAttribute('role', 'dialog');
                element.classList.add('show');
                document.body.classList.add('modal-open');

                if (!$('.modal-backdrop').length) {
                    $('<div class="modal-backdrop fade show"></div>').appendTo(document.body);
                }
            }

            function hideManually() {
                if (!element) {
                    return;
                }

                element.classList.remove('show');
                element.style.display = 'none';
                element.setAttribute('aria-hidden', 'true');
                element.removeAttribute('aria-modal');
                element.removeAttribute('role');
                $('.modal-backdrop').remove();
                document.body.classList.remove('modal-open');
            }

            return {
                show: function() {
                    try {
                        if ($.fn.modal && $(selector).length) {
                            $(selector).modal('show');

                            return;
                        }
                    } catch (error) {
                        console.warn('Fallback modal show:', error);
                    }

                    try {
                        if (showWithNative()) {
                            return;
                        }
                    } catch (error) {
                        console.warn('Fallback native modal show:', error);
                    }

                    showManually();
                },
                hide: function() {
                    try {
                        if ($.fn.modal && $(selector).length) {
                            $(selector).modal('hide');

                            return;
                        }
                    } catch (error) {
                        console.warn('Fallback modal hide:', error);
                    }

                    try {
                        if (hideWithNative()) {
                            return;
                        }
                    } catch (error) {
                        console.warn('Fallback native modal hide:', error);
                    }

                    hideManually();
                }
            };
        }

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
            const labels = {
                kebersamaan: 'Kebersamaan',
                jasa_operasi: 'Jasa Operasi',
                jasa_rawat_jalan: 'Jasa Rawat Jalan',
                jasa_poli: 'Jasa Poli',
                jasa_ecg: 'Jasa ECG',
                konsul_wa: 'Konsul WA',
                visite: 'Jasa Visite',
            };

            return labels[activePremiumType] || 'Jasa Visite';
        }

        function isKebersamaan() {
            return activePremiumType === 'kebersamaan';
        }

        function isOperasi() {
            return activePremiumType === 'jasa_operasi';
        }

        function isRawatJalan() {
            return activePremiumType === 'jasa_rawat_jalan';
        }

        function isPoli() {
            return activePremiumType === 'jasa_poli';
        }

        function isEcg() {
            return activePremiumType === 'jasa_ecg';
        }

        function isKonsulWa() {
            return activePremiumType === 'konsul_wa';
        }

        function isStandalonePremium() {
            return isKebersamaan() || isOperasi();
        }

        function generateButtonLabel() {
            if (isKebersamaan()) {
                return 'Generate Kebersamaan';
            }

            if (isOperasi()) {
                return 'Generate Jasa Operasi';
            }

            if (isRawatJalan()) {
                return `Generate Jasa Rawat Jalan ${activeTypeLabel()}`;
            }

            if (isPoli()) {
                return `Generate Jasa Poli ${activeTypeLabel()}`;
            }

            if (isEcg()) {
                return `Generate Jasa ECG ${activeTypeLabel()}`;
            }

            if (isKonsulWa()) {
                return `Generate Konsul WA ${activeTypeLabel()}`;
            }

            return `Generate ${activeTypeLabel()}`;
        }

        function ecgDistributionModeLabel(mode) {
            return mode === 'full_amount' ? 'Diberikan penuh' : 'Dibagi rata';
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

        function rowPremiValue(row) {
            return Number(row.premi_konsul_wa ?? row.premi_poli ?? row.premi_ecg ?? row.premi_rawat_jalan ?? row.biaya_rawat ?? 0);
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
                current.total += rowPremiValue(row);
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

        function configPaneForActivePremium() {
            if (isRawatJalan()) {
                return 'rawat_jalan';
            }

            if (isPoli()) {
                return 'poli';
            }

            if (isEcg()) {
                return 'ecg';
            }

            if (isKonsulWa()) {
                return 'konsul_wa';
            }

            if (isKebersamaan() || isOperasi()) {
                return 'penerima_lain';
            }

            return 'visite';
        }

        function showConfigPane(pane) {
            const targetPane = pane || 'formula';
            $('.pd-config-tab').removeClass('active');
            $(`.pd-config-tab[data-config-pane-target="${targetPane}"]`).addClass('active');
            $('.pd-config-pane').removeClass('active');
            $(`.pd-config-pane[data-config-pane="${targetPane}"]`).addClass('active');
        }

        function selectedCount(selector) {
            return ($(selector).val() || []).length;
        }

        function updateConfigSummary() {
            const visiteMapping = selectedCount('#configMappingTindakan');
            const rawatJalanMapping = selectedCount('#configRawatJalanMappingTindakan');
            const poliMapping = selectedCount('#configPoliMappingTindakan');
            const ecgMapping = selectedCount('#configEcgMappingTindakan');
            const konsulWaMapping = selectedCount('#configKonsulWaMappingTindakan');
            const dokterUmum = selectedCount('#doctorSelectUmum');
            const dokterSpesialis65 = selectedCount('#doctorSelectSpesialis65');
            const dokterSpesialis80 = selectedCount('#doctorSelectSpesialis80');
            const dokterKebersamaan = selectedCount('#doctorSelectKebersamaan');
            const dokterOperasi = selectedCount('#doctorSelectOperasi');
            const dokterRawatJalan = selectedCount('#doctorSelectRawatJalan');
            const dokterPoli = selectedCount('#doctorSelectPoli');
            const sumberPoliFilter = selectedCount('#configPoliFilterSources');
            const dokterPoliFilter = selectedCount('#configPoliFilterDoctors');
            const poliFilterMapping = selectedCount('#configPoliFilterTindakan');
            const dokterEcg = selectedCount('#doctorSelectEcg');
            const dokterKonsulWa = selectedCount('#doctorSelectKonsulWa');
            const dokterKhusus45 = selectedCount('#doctorSelectRawatJalanKhusus45000');
            const dokterKhusus72 = selectedCount('#doctorSelectRawatJalanKhusus72000');
            const dokterVisite = dokterUmum + dokterSpesialis65 + dokterSpesialis80;
            const dokterRawatTotal = dokterRawatJalan + dokterKhusus45 + dokterKhusus72;
            const dokterTotal = dokterVisite + dokterKebersamaan + dokterOperasi + dokterRawatTotal + dokterPoli + dokterEcg + dokterKonsulWa;
            const visiteUmumPercent = Number($('#configVisiteUmumPercent').val() || 0);
            const visiteBpjsNominal = Number($('#configVisiteBpjsNominal').val() || 0);
            const visiteBpjsPercent = Number($('#configVisiteBpjsPercent').val() || 0);
            const kebersamaanDivider = Number($('#configKebersamaanDivider').val() || 0);
            const kebersamaanOnlyUmum = $('#configKebersamaanOnlyUmum').is(':checked');
            const ecgNominal = Number($('#configEcgNominal').val() || 0);
            const ecgDivider = Number($('#configEcgDivider').val() || 0);
            const ecgDistributionMode = $('#configEcgDistributionMode').val() || 'split_evenly';
            const poliPercent = Number($('#configPoliPercent').val() || 0);
            const poliDistributionMode = $('#configPoliDistributionMode').val() || 'split_evenly';
            const konsulWaNominal = Number($('#configKonsulWaNominal').val() || 0);
            const bpjsSourceLabel = $('#configSourcePeriodMode option:selected').text() || 'Periode Berjalan';

            $('#configSummaryFormula').text(`${formatNumber(visiteUmumPercent, 2)}% UMUM / ${formatRupiah(visiteBpjsNominal)} BPJS`);
            $('#configSummaryVisite').text(`${formatNumber(visiteMapping)} mapping / ${formatNumber(dokterVisite)} dokter`);
            $('#configSummaryRawatJalan').text(`${formatNumber(rawatJalanMapping)} mapping / ${formatNumber(dokterRawatTotal)} dokter`);
            $('#configSummaryPoli').text(`${formatNumber(poliMapping)} mapping / ${formatNumber(sumberPoliFilter)} sumber / ${formatNumber(dokterPoliFilter)} filter / ${formatNumber(dokterPoli)} penerima`);
            $('#configSummaryEcg').text(`${formatNumber(ecgMapping)} mapping / ${formatNumber(dokterEcg)} dokter`);
            $('#configSummaryKonsulWa').text(`${formatNumber(konsulWaMapping)} mapping / ${formatNumber(dokterKonsulWa)} dokter`);
            $('#configSummaryPenerima').text(`${formatNumber(dokterTotal)} dokter`);
            $('#configBadgeFormulaVisite').text(`${formatNumber(visiteUmumPercent, 2)}% UMUM / ${formatNumber(visiteBpjsPercent, 2)}% BPJS`);
            $('#configBadgeKebersamaan').text(`${formatNumber(kebersamaanDivider)} pembagi${kebersamaanOnlyUmum ? ' / umum saja' : ''}`);
            $('#configBadgeEcgFormula').text(`${formatRupiah(ecgNominal)} / ${formatNumber(ecgDivider)} - ${ecgDistributionModeLabel(ecgDistributionMode)}`);
            $('#configBadgePoliFormula').text(`${formatNumber(poliPercent, 2)}% - ${ecgDistributionModeLabel(poliDistributionMode)}`);
            $('#configBadgeKonsulWaFormula').text(`${formatRupiah(konsulWaNominal)} / data`);
            $('#configScopeBpjsModeText, #rawatJalanBpjsModePreview, #poliBpjsModePreview, #ecgBpjsModePreview, #konsulWaBpjsModePreview').text(bpjsSourceLabel);
            $('#configBadgeMappingVisite').text(`${formatNumber(visiteMapping)} mapping`);
            $('#configBadgeRawatJalanMapping').text(`${formatNumber(rawatJalanMapping)} mapping`);
            $('#configBadgePoliMapping').text(`${formatNumber(poliMapping)} mapping`);
            $('#configBadgePoliFilter').text(`${formatNumber(sumberPoliFilter)} sumber / ${formatNumber(dokterPoliFilter)} dokter / ${formatNumber(poliFilterMapping)} tindakan`);
            $('#configBadgeEcgMapping').text(`${formatNumber(ecgMapping)} mapping`);
            $('#configBadgeKonsulWaMapping').text(`${formatNumber(konsulWaMapping)} mapping`);
            $('#configBadgeDokterUmum').text(`${formatNumber(dokterUmum)} dokter`);
            $('#configBadgeDokterSpesialis65').text(`${formatNumber(dokterSpesialis65)} dokter`);
            $('#configBadgeDokterSpesialis80').text(`${formatNumber(dokterSpesialis80)} dokter`);
            $('#configBadgeDokterKebersamaan').text(`${formatNumber(dokterKebersamaan)} dokter`);
            $('#configBadgeDokterOperasi').text(`${formatNumber(dokterOperasi)} dokter`);
            $('#configBadgeDokterRawatJalan').text(`${formatNumber(dokterRawatJalan)} dokter`);
            $('#configBadgeDokterRawatJalanKhusus').text(`${formatNumber(dokterKhusus45 + dokterKhusus72)} dokter`);
            $('#configBadgeDokterPoli').text(`${formatNumber(dokterPoli)} dokter`);
            $('#configBadgeDokterEcg').text(`${formatNumber(dokterEcg)} dokter`);
            $('#configBadgeDokterKonsulWa').text(`${formatNumber(dokterKonsulWa)} dokter`);
        }

        function initSelect2() {
            if (!$.fn.select2) {
                return;
            }

            $('#configMappingTindakan, #configRawatJalanMappingTindakan, #configPoliMappingTindakan, #configEcgMappingTindakan, #configKonsulWaMappingTindakan').each(function() {
                $(this).select2({
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
            });

            $('#configPoliFilterTindakan').select2({
                dropdownParent: $('#modalConfigPremiDokter'),
                placeholder: 'Pilih tindakan yang difilter dokter',
                width: '100%',
                closeOnSelect: false
            });

            $('#configPoliFilterSources').select2({
                dropdownParent: $('#modalConfigPremiDokter'),
                placeholder: 'Pilih sumber data rawat',
                width: '100%',
                closeOnSelect: false
            });

            $('#configPoliFilterDoctors').select2({
                dropdownParent: $('#modalConfigPremiDokter'),
                placeholder: 'Cari dokter sumber data',
                width: '100%',
                closeOnSelect: false,
                ajax: {
                    url: routes.dokterOptions,
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ q: params.term || '' }),
                    processResults: response => ({
                        results: (response.data || []).map(item => ({
                            id: item.id || item.kd_dokter,
                            text: item.text || [item.kd_dokter, item.nm_dokter].filter(Boolean).join(' - '),
                            item
                        }))
                    })
                }
            });

            $('.pd-doctor-select, .pd-rj-special-doctor-select').each(function() {
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

        function setPoliFilterSourceOptions(options, selectedItems) {
            const select = $('#configPoliFilterSources');
            const selectedIds = new Set((selectedItems || []).map(item => String(item.id || item.source_table)));

            select.empty();

            (options || []).forEach((item) => {
                const id = item.id || item.source_table;
                const selected = selectedIds.has(String(id));
                select.append(new Option(item.text || item.source_label || id, id, selected, selected));
            });

            select.trigger('change');
        }

        function poliSelectedMappingItems() {
            const select = $('#configPoliMappingTindakan');
            const configured = new Map((configData?.poli_mapping_tindakan || [])
                .map(item => [String(item.id), item]));

            return (select.val() || []).map((id) => {
                const option = select.find('option').filter(function() {
                    return String(this.value) === String(id);
                });
                const saved = configured.get(String(id)) || {};

                return {
                    id,
                    text: option.text() || saved.text || id
                };
            });
        }

        function syncPoliFilterTindakanOptions(savedItems = null) {
            const select = $('#configPoliFilterTindakan');
            const selectedIds = new Set((savedItems !== null
                ? (savedItems || []).map(item => String(item.id ?? item.jnsTindakan_id))
                : (select.val() || []).map(String)
            ));

            select.empty();

            poliSelectedMappingItems().forEach((item) => {
                const selected = selectedIds.has(String(item.id));
                select.append(new Option(item.text, item.id, selected, selected));
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
                target.html(`
                    <div class="pd-empty-state">
                        <i class="mdi mdi-account-search-outline"></i>
                        <span>Belum ada dokter dipilih.</span>
                    </div>
                `);
                return;
            }

            target.html(rows.map((item) => `
                <div class="pd-doctor-row">
                    <div class="pd-row-icon"><i class="mdi mdi-account-outline"></i></div>
                    <div>
                        <div class="pd-doctor-name">${escapeHtml(item.text)}</div>
                        <div class="pd-doctor-meta">${escapeHtml(meta.label)}</div>
                    </div>
                    ${meta.usePercent === false
                        ? `<div class="pd-row-chip">${escapeHtml(category === 'jasa_ecg'
                            ? ecgDistributionModeLabel($('#configEcgDistributionMode').val())
                            : category === 'jasa_poli'
                            ? ecgDistributionModeLabel($('#configPoliDistributionMode').val())
                            : category === 'konsul_wa'
                            ? `${formatRupiah(Number($('#configKonsulWaNominal').val() || 0))} / data`
                            : (meta.fixedText || 'Dari formula'))}</div>`
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

        function rawatJalanConfiguredMappingMap() {
            return new Map((configData?.rawat_jalan_mapping_configs || [])
                .map(item => [String(item.jnsTindakan_id || item.id), item]));
        }

        function rawatJalanMultiplierText(type, value) {
            return type === 'percent'
                ? `${formatNumber(value || 0, 2)}%`
                : formatRupiah(value || 0);
        }

        function rawatJalanSelectedMappings() {
            const select = $('#configRawatJalanMappingTindakan');
            const configured = rawatJalanConfiguredMappingMap();

            return (select.val() || []).map((id) => {
                const option = select.find('option').filter(function() {
                    return String(this.value) === String(id);
                });
                const saved = configured.get(String(id)) || {};
                const typeInput = $('.rj-mapping-type').filter(function() {
                    return String($(this).data('id')) === String(id);
                });
                const valueInput = $('.rj-mapping-value').filter(function() {
                    return String($(this).data('id')) === String(id);
                });
                const existingType = typeInput.length ? typeInput.val() : null;
                const existingValue = valueInput.length ? Number(valueInput.val()) : null;

                return {
                    id,
                    text: option.text() || saved.text || id,
                    multiplier_type: existingType || saved.multiplier_type || 'nominal',
                    multiplier_value: Number.isFinite(existingValue) ? existingValue : Number(saved.multiplier_value || 0),
                    jumlah_mapping: saved.jumlah_mapping || 0
                };
            });
        }

        function updateRawatJalanMappingSuffix(id = null) {
            const types = id === null
                ? $('.rj-mapping-type')
                : $('.rj-mapping-type').filter(function() {
                    return String($(this).data('id')) === String(id);
                });

            types.each(function() {
                const mappingId = $(this).data('id');
                const type = $(this).val() === 'percent' ? 'percent' : 'nominal';
                const suffix = type === 'percent' ? '%' : 'Rp';
                const value = Number($('.rj-mapping-value').filter(function() {
                    return String($(this).data('id')) === String(mappingId);
                }).val() || 0);
                $('.rj-mapping-suffix').filter(function() {
                    return String($(this).data('id')) === String(mappingId);
                }).text(suffix);
                $('.rj-mapping-preview').filter(function() {
                    return String($(this).data('id')) === String(mappingId);
                }).text(rawatJalanMultiplierText(type, value));
            });
        }

        function renderRawatJalanMappingRows() {
            const target = $('#rawatJalanMappingRows');
            const rows = rawatJalanSelectedMappings();

            if (!rows.length) {
                target.html(`
                    <div class="pd-empty-state">
                        <i class="mdi mdi-clipboard-search-outline"></i>
                        <span>Belum ada mapping Rawat Jalan dipilih.</span>
                    </div>
                `);
                return;
            }

            target.html(rows.map((item) => `
                <div class="pd-rj-mapping-row">
                    <div class="pd-rj-mapping-main">
                        <div class="pd-row-icon"><i class="mdi mdi-clipboard-pulse-outline"></i></div>
                        <div>
                            <div class="pd-doctor-name">${escapeHtml(item.text)}</div>
                            <div class="pd-doctor-meta">${formatNumber(item.jumlah_mapping || 0)} rincian RAJAL/RANAP</div>
                        </div>
                    </div>
                    <select class="form-select form-select-sm rj-mapping-type" data-id="${escapeHtml(item.id)}">
                        <option value="nominal">Nominal</option>
                        <option value="percent">Persen</option>
                    </select>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text rj-mapping-suffix" data-id="${escapeHtml(item.id)}">Rp</span>
                        <input type="number" class="form-control rj-mapping-value"
                            data-id="${escapeHtml(item.id)}"
                            min="0" step="0.0001" value="${escapeHtml(item.multiplier_value)}">
                    </div>
                    <div class="pd-rj-preview rj-mapping-preview" data-id="${escapeHtml(item.id)}">
                        ${escapeHtml(rawatJalanMultiplierText(item.multiplier_type, item.multiplier_value))}
                    </div>
                </div>
            `).join(''));

            rows.forEach((item) => {
                $('.rj-mapping-type').filter(function() {
                    return String($(this).data('id')) === String(item.id);
                }).val(item.multiplier_type === 'percent' ? 'percent' : 'nominal');
            });
            updateRawatJalanMappingSuffix();
        }

        function collectRawatJalanMappingConfigs() {
            return ($('#configRawatJalanMappingTindakan').val() || []).map((id) => {
                const typeInput = $('.rj-mapping-type').filter(function() {
                    return String($(this).data('id')) === String(id);
                });
                const valueInput = $('.rj-mapping-value').filter(function() {
                    return String($(this).data('id')) === String(id);
                });

                return {
                    jnsTindakan_id: id,
                    multiplier_type: typeInput.val() || 'nominal',
                    multiplier_value: Number(valueInput.val() || 0)
                };
            });
        }

        function rawatJalanSpecialNominal(groupKey) {
            const group = rawatJalanSpecialGroups[groupKey];

            return Number($(group.nominal).val() || group.defaultNominal || 0);
        }

        function rawatJalanSpecialSelectedItems(groupKey) {
            const group = rawatJalanSpecialGroups[groupKey];
            const select = $(group.select);
            const configured = new Map((configData?.rawat_jalan_special_doctors || [])
                .filter(item => item.group_key === groupKey)
                .map(item => [String(item.kd_dokter), item]));

            return (select.val() || []).map((code) => {
                const option = select.find('option').filter(function() {
                    return String(this.value) === String(code);
                });
                const saved = configured.get(String(code)) || {};

                return {
                    kd_dokter: code,
                    text: option.text() || saved.text || code,
                    nm_sps: saved.nm_sps || '',
                    nominal: rawatJalanSpecialNominal(groupKey)
                };
            });
        }

        function renderRawatJalanSpecialRows(groupKey) {
            const group = rawatJalanSpecialGroups[groupKey];
            const target = $(group.rows);
            const rows = rawatJalanSpecialSelectedItems(groupKey);

            if (!rows.length) {
                target.html(`
                    <div class="pd-empty-state">
                        <i class="mdi mdi-account-search-outline"></i>
                        <span>Belum ada dokter dipilih.</span>
                    </div>
                `);
                return;
            }

            target.html(rows.map((item) => `
                <div class="pd-doctor-row">
                    <div class="pd-row-icon"><i class="mdi mdi-account-cash-outline"></i></div>
                    <div>
                        <div class="pd-doctor-name">${escapeHtml(item.text)}</div>
                        <div class="pd-doctor-meta">${escapeHtml(group.label)}</div>
                    </div>
                    <div class="pd-row-chip">${formatRupiah(item.nominal)} / data</div>
                </div>
            `).join(''));
        }

        function collectRawatJalanSpecialDoctorConfigs() {
            const rows = [];

            Object.entries(rawatJalanSpecialGroups).forEach(([groupKey, group]) => {
                ($(group.select).val() || []).forEach((code) => {
                    rows.push({
                        group_key: groupKey,
                        kd_dokter: code,
                        nominal: rawatJalanSpecialNominal(groupKey)
                    });
                });
            });

            return rows;
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
            $('#configKebersamaanOnlyUmum').prop('checked', Boolean(data.kebersamaan_only_umum));
            $('#configEcgNominal').val(data.ecg_nominal || 5000);
            $('#configEcgDivider').val(data.ecg_divider || 3);
            $('#configEcgDistributionMode').val(data.ecg_distribution_mode || 'split_evenly');
            $('#configPoliPercent').val(data.poli_percent || 30);
            $('#configPoliDistributionMode').val(data.poli_distribution_mode || 'split_evenly');
            $('#configKonsulWaNominal').val(data.konsul_wa_nominal || 0);
            setSelectedOptions('#configMappingTindakan', data.mapping_tindakan || []);
            setSelectedOptions('#configEcgMappingTindakan', data.ecg_mapping_tindakan || []);
            setSelectedOptions('#configPoliMappingTindakan', data.poli_mapping_tindakan || []);
            setPoliFilterSourceOptions(data.poli_source_table_options || [], data.poli_filter_sources || []);
            setSelectedOptions('#configPoliFilterDoctors', data.poli_filter_doctors || []);
            syncPoliFilterTindakanOptions(data.poli_filter_tindakan || []);
            setSelectedOptions('#configKonsulWaMappingTindakan', data.konsul_wa_mapping_tindakan || []);
            setSelectedOptions('#configRawatJalanMappingTindakan', data.rawat_jalan_mapping_configs || []);
            renderRawatJalanMappingRows();

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

            Object.entries(rawatJalanSpecialGroups).forEach(([groupKey, group]) => {
                const savedRows = (data.rawat_jalan_special_doctors || [])
                    .filter(item => item.group_key === groupKey);
                const defaultGroup = (data.rawat_jalan_special_groups || [])
                    .find(item => item.id === groupKey);
                const nominal = savedRows[0]?.nominal ?? defaultGroup?.default_nominal ?? group.defaultNominal;
                const items = savedRows.map(item => ({
                    id: item.kd_dokter,
                    text: `${item.kd_dokter} - ${item.nm_dokter} (${item.nm_sps || group.label})`
                }));

                $(group.nominal).val(nominal);
                setSelectedOptions(group.select, items);
                renderRawatJalanSpecialRows(groupKey);
            });
            updateConfigSummary();
        }

        function loadConfig(openModal = false) {
            if (openModal) {
                showConfigPane(configPaneForActivePremium());
                modalConfig.show();
            }

            return $.get(routes.config)
                .done((response) => {
                    fillConfig(response.data || {});
                    if (openModal) {
                        showConfigPane(configPaneForActivePremium());
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
                kebersamaan_only_umum: $('#configKebersamaanOnlyUmum').is(':checked') ? 1 : 0,
                ecg_nominal: Number($('#configEcgNominal').val() || 5000),
                ecg_divider: Number($('#configEcgDivider').val() || 3),
                ecg_distribution_mode: $('#configEcgDistributionMode').val() || 'split_evenly',
                poli_percent: Number($('#configPoliPercent').val() || 30),
                poli_distribution_mode: $('#configPoliDistributionMode').val() || 'split_evenly',
                konsul_wa_nominal: Number($('#configKonsulWaNominal').val() || 0),
                mapping_tindakan_ids: $('#configMappingTindakan').val() || [],
                ecg_mapping_tindakan_ids: $('#configEcgMappingTindakan').val() || [],
                poli_mapping_tindakan_ids: $('#configPoliMappingTindakan').val() || [],
                poli_filter_doctor_codes: $('#configPoliFilterDoctors').val() || [],
                poli_filter_source_tables: $('#configPoliFilterSources').val() || [],
                poli_filter_tindakan_ids: $('#configPoliFilterTindakan').val() || [],
                konsul_wa_mapping_tindakan_ids: $('#configKonsulWaMappingTindakan').val() || [],
                doctor_configs: collectDoctorConfigs(),
                rawat_jalan_mapping_configs: collectRawatJalanMappingConfigs(),
                rawat_jalan_special_doctors: collectRawatJalanSpecialDoctorConfigs()
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
            $('#summaryPasien').text(isOperasi() ? 'input manual' : formatNumber(data.jumlah_pasien || 0) + ' pasien');
            $('#summaryDokter').text(formatNumber(data.jumlah_dokter || 0));
            $('#summaryDokterFoot').text(isOperasi()
                ? 'Penerima operasi'
                : isKebersamaan()
                ? 'Penerima kebersamaan'
                : isRawatJalan()
                ? 'Penerima rawat jalan'
                : isPoli()
                ? 'Penerima Poli'
                : isEcg()
                ? 'Penerima ECG'
                : isKonsulWa()
                ? 'Penerima Konsul WA'
                : 'Penerima visite');
            $('#summaryMapping').text(formatNumber(data.jumlah_mapping_tindakan || 0));
            $('#summaryMappingFoot').text(isOperasi()
                ? 'Input manual'
                : isRawatJalan()
                ? 'Mapping rawat jalan'
                : isPoli()
                ? 'Mapping Poli'
                : isEcg()
                ? 'Mapping ECG'
                : isKonsulWa()
                ? 'Mapping Konsul WA'
                : 'Tindakan visite');
            $('#summarySkipped').text(isOperasi()
                ? `${formatNumber(data.operasi_total_percent || 0, 2)}%`
                : isKebersamaan()
                ? formatNumber(data.kebersamaan_divider || 0)
                : isPoli()
                ? formatNumber(data.jumlah_tidak_terkonfigurasi || 0)
                : isEcg()
                ? formatNumber(data.ecg_divider || 0)
                : isKonsulWa()
                ? formatNumber(data.jumlah_tidak_terkonfigurasi || 0)
                : formatNumber(data.jumlah_tidak_terkonfigurasi || 0));
            $('#summarySkippedFoot').text(isOperasi()
                ? 'Total persen penerima'
                : isKebersamaan()
                ? `Pembagi, alokasi ${formatRupiah(data.kebersamaan_allocation_per_doctor || 0)} per dokter`
                : isRawatJalan()
                ? 'Dokter belum dikonfigurasi'
                : isPoli()
                ? `${data.poli_distribution_mode_label || ecgDistributionModeLabel(data.poli_distribution_mode)}, ${formatRupiah(data.poli_allocation_per_doctor || 0)} per dokter`
                : isEcg()
                ? `${data.ecg_distribution_mode_label || ecgDistributionModeLabel(data.ecg_distribution_mode)}, ${formatRupiah(data.ecg_allocation_per_doctor || 0)} per dokter`
                : isKonsulWa()
                ? 'Baris dokter belum dikonfigurasi'
                : activeType === 'bpjs'
                ? `${formatNumber(data.jumlah_spesialis_diabaikan || 0)} data spesialis BPJS diabaikan`
                : 'Baris dokter belum dikonfigurasi');
            $('#summaryMessage').text(data.readiness_message || '-');
            $('#summaryFormula').text(isOperasi()
                ? `${formatRupiah(data.nominal_operasi || 0)} x persen dokter penerima`
                : isKebersamaan()
                ? `${data.kebersamaan_sumber_dokter_label || 'Dokter Umum & Spesialis'} | ${formatRupiah(data.kebersamaan_visite_umum_total_premi || 0)} x ${formatNumber(data.kebersamaan_umum_percent || 0, 2)}% + ${formatNumber(data.kebersamaan_visite_bpjs_jumlah_transaksi || 0)} transaksi x ${formatRupiah(data.kebersamaan_bpjs_nominal || 0)} x ${formatNumber(data.kebersamaan_bpjs_percent || 0, 2)}%`
                : isRawatJalan()
                ? `${activeTypeLabel()} | Jumlah data per dokter x pengkali mapping | ${sourcePeriodText(data)}`
                : isPoli()
                ? `${activeTypeLabel()} | ${formatRupiah(data.total_biaya_rawat || 0)} x ${formatNumber(data.poli_percent || 0, 2)}% | filter ${formatNumber(data.poli_filter_source_count || 0)} sumber / ${formatNumber(data.poli_filter_doctor_count || 0)} dokter / ${formatNumber(data.poli_filter_tindakan_count || 0)} tindakan | ${data.poli_distribution_mode_label || ecgDistributionModeLabel(data.poli_distribution_mode)} | ${sourcePeriodText(data)}`
                : isEcg()
                ? `${activeTypeLabel()} | ${formatNumber(data.jumlah_transaksi || 0)} data x ${formatRupiah(data.ecg_nominal || 0)} / ${formatNumber(data.ecg_divider || 1)} | ${data.ecg_distribution_mode_label || ecgDistributionModeLabel(data.ecg_distribution_mode)} | ${sourcePeriodText(data)}`
                : isKonsulWa()
                ? `${activeTypeLabel()} | ${formatNumber(data.jumlah_transaksi || 0)} data x ${formatRupiah(data.konsul_wa_nominal || 0)} | ${sourcePeriodText(data)}`
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

            if (isOperasi()) {
                payload.nominal_operasi = Number($('#nominalOperasiPremiDokter').val() || 0);
            } else if (!isStandalonePremium()) {
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
                `Data ${activePremiumLabel()}${isStandalonePremium() ? '' : ' ' + activeTypeLabel()} periode ${$('#periodePremiDokter').val()} akan disimpan.`,
                () => {
                    $('#btnGeneratePremiDokter').prop('disabled', true);

                    const payload = {
                        periode: $('#periodePremiDokter').val(),
                        jenis_premi_dokter: activePremiumType
                    };

                    if (isOperasi()) {
                        payload.nominal_operasi = Number($('#nominalOperasiPremiDokter').val() || 0);
                    } else if (!isStandalonePremium()) {
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

                    if (!isStandalonePremium()) {
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
                    render: (value, type, row) => {
                        const standalone = ['kebersamaan', 'jasa_operasi'].includes(row.jenis_premi_dokter);
                        const sourceText = row.source_period_text || 'sumber ' + (row.source_periode || '-');
                        const serviceLabel = String(value || '');
                        const serviceText = standalone || !serviceLabel || sourceText.toLowerCase().startsWith(serviceLabel.toLowerCase())
                            ? ''
                            : `${escapeHtml(serviceLabel)} / `;

                        return `${escapeHtml(row.jenis_premi_dokter_label || 'Jasa Visite')}<br><span class="text-muted">${serviceText}${escapeHtml(sourceText)}</span>`;
                    }
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
            const totalBiaya = filteredRows.reduce((sum, row) => sum + rowPremiValue(row), 0);

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
                    <td class="text-end">
                        ${formatRupiah(rowPremiValue(row))}
                        ${row.premi_rawat_jalan !== undefined || row.premi_ecg !== undefined || row.premi_poli !== undefined || row.premi_konsul_wa !== undefined
                            ? `<div class="text-muted small">Rawat ${formatRupiah(row.biaya_rawat_asli ?? row.biaya_rawat ?? 0)}</div>`
                            : ''}
                    </td>
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
                    const serviceLabel = ['kebersamaan', 'jasa_operasi'].includes(data.jenis_premi_dokter)
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
            $('#jenisPelayananSwitch').toggle(!isStandalonePremium());
            $('#operasiNominalPanel').toggleClass('d-none', !isOperasi());
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
        $('#nominalOperasiPremiDokter').on('input', refreshSummary);

        $('#btnConfigPremiDokter').on('click', () => loadConfig(true));
        $('#btnGeneratePremiDokter').on('click', generatePremiDokter);
        $('#modalConfigPremiDokter [data-bs-dismiss="modal"]').on('click', () => modalConfig.hide());
        $('#modalDetailPremiDokter [data-bs-dismiss="modal"]').on('click', () => modalDetail.hide());
        $('#formConfigPremiDokter').on('submit', function(event) {
            event.preventDefault();
            saveConfig();
        });
        $('.pd-config-tab').on('click', function() {
            showConfigPane($(this).data('config-pane-target'));
        });
        $('#configVisiteUmumPercent, #configVisiteBpjsNominal, #configVisiteBpjsPercent, #configSourcePeriodMode, #configKebersamaanUmumPercent, #configKebersamaanBpjsNominal, #configKebersamaanBpjsPercent, #configKebersamaanDivider, #configKebersamaanOnlyUmum, #configEcgNominal, #configEcgDivider, #configEcgDistributionMode, #configPoliPercent, #configPoliDistributionMode, #configKonsulWaNominal')
            .on('input change', updateConfigSummary);
        $('#configMappingTindakan').on('change', updateConfigSummary);
        $('#configPoliMappingTindakan').on('change', function() {
            syncPoliFilterTindakanOptions();
            updateConfigSummary();
        });
        $('#configPoliFilterSources, #configPoliFilterDoctors, #configPoliFilterTindakan').on('change', updateConfigSummary);
        $('#configEcgMappingTindakan').on('change', updateConfigSummary);
        $('#configKonsulWaMappingTindakan').on('change', updateConfigSummary);
        $('#configPoliDistributionMode').on('change', () => renderDoctorRows('jasa_poli'));
        $('#configEcgDistributionMode').on('change', () => renderDoctorRows('jasa_ecg'));
        $('#configKonsulWaNominal').on('input', () => renderDoctorRows('konsul_wa'));
        $('.pd-doctor-select').on('change', function() {
            renderDoctorRows($(this).data('category'));
            updateConfigSummary();
        });
        $('#configRawatJalanMappingTindakan').on('change', function() {
            renderRawatJalanMappingRows();
            updateConfigSummary();
        });
        $('#rawatJalanMappingRows').on('change', '.rj-mapping-type', function() {
            updateRawatJalanMappingSuffix($(this).data('id'));
            updateConfigSummary();
        });
        $('#rawatJalanMappingRows').on('input', '.rj-mapping-value', function() {
            updateRawatJalanMappingSuffix($(this).data('id'));
            updateConfigSummary();
        });
        $('.pd-rj-special-doctor-select').on('change', function() {
            renderRawatJalanSpecialRows($(this).data('group-key'));
            updateConfigSummary();
        });
        $('#configRawatJalanKhusus45000Nominal, #configRawatJalanKhusus72000Nominal').on('input', function() {
            const groupKey = $(this).attr('id') === 'configRawatJalanKhusus45000Nominal'
                ? 'rawat_jalan_khusus_45000'
                : 'rawat_jalan_khusus_72000';
            renderRawatJalanSpecialRows(groupKey);
            updateConfigSummary();
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
