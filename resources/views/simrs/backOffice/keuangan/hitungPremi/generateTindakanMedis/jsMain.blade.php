<script>
    $(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const detailModal = new bootstrap.Modal(document.getElementById('modalDetailTindakanMedis'));
        const configModal = new bootstrap.Modal(document.getElementById('modalTindakanMedisConfig'));
        let activeType = 'umum';
        let activePremiId = '';
        let activePremiIds = {
            umum: '',
            bpjs: ''
        };
        let distributionMode = 'split_evenly';
        let mappingPremiList = [];
        let actionOptions = [];
        let sourceOptions = [];
        let sourceMappings = [];
        let karcisConfigOptions = [];
        let karcisSelectedIds = [];
        let selectedDoctors = [];
        let doctorActionSelectedIds = [];
        let bpjsIgnoreUgd = false;
        let bpjsIgnoreVk = false;
        let includeBpjsIcuPool = true;
        let summaryData = null;
        let detailData = null;
        let selectedUgdSourceId = '';
        let selectedVkSourceId = '';
        let detailMappings = [];
        let selectedDetailMappingId = '';

        function formatRupiah(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 2
            }).format(Number(value) || 0);
        }

        function formatNumber(value) {
            return new Intl.NumberFormat('id-ID').format(Number(value) || 0);
        }

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function errorMessage(xhr) {
            const errors = xhr.responseJSON?.errors;

            if (errors) {
                return Object.values(errors).flat().join('<br>');
            }

            return xhr.responseJSON?.message || 'Terjadi kesalahan saat memproses data.';
        }

        function typeBadge(type, label) {
            return `<span class="tm-badge ${escapeHtml(type)}">${escapeHtml(label)}</span>`;
        }

        function activePremi() {
            return mappingPremiList.find(function(item) {
                return String(item.id) === String(activePremiId);
            });
        }

        function syncActivePremiId() {
            activePremiId = String(activePremiIds[activeType] || '');
        }

        function activePremiLabel() {
            const premi = activePremi();

            return premi ? (premi.kode || '-') + ' - ' + (premi.jenis || '-') : '-';
        }

        function mappingValueLabel(detail) {
            return detail.jenis_mapping === 'persen' ?
                formatNumber(detail.nilai_mapping) + '%' :
                formatRupiah(detail.nilai_mapping);
        }

        function baseValueLabel(detail) {
            return detail.jenis_mapping === 'persen' ?
                formatRupiah(detail.total_biaya_rawat) :
                formatNumber(detail.jumlah_data) + ' data';
        }

        function sourceRulesLabel(rules) {
            rules = rules || [];

            if (!rules.length) {
                return 'Semua sumber rawat';
            }

            return rules.map(function(rule) {
                return rule.source_label || rule.source_pattern || '-';
            }).join(', ');
        }

        function distributionModeLabel(mode) {
            return mode === 'full_amount' ?
                'Nilai final penuh per pegawai' :
                'Dibagi rata ke pegawai';
        }

        function updateBpjsSourceModeNote() {
            const mode = $('#configBpjsSourceModeTindakan').val() || 'previous';
            $('#configBpjsSourceModeNote').text(
                (mode === 'previous' ? 'Tindakan BPJS memakai bulan sebelumnya.' :
                    'Tindakan BPJS memakai periode generate.') +
                ' UGD/VK dan pool ICU BPJS mengikuti opsi di bawah.'
            );
        }

        function selectedDoctorCodes() {
            return ($('#configDoctorFilterTindakan').val() || []).map(String);
        }

        function selectedDoctorActionIds() {
            return doctorActionSelectedIds.map(Number);
        }

        function doctorFilterLabel(filter) {
            filter = filter || {};
            const count = Number(filter.selected_count ?? selectedDoctorCodes().length) || 0;
            const actionCount = Number(filter.selected_action_count ?? selectedDoctorActionIds().length) || 0;

            if (count > 0 && actionCount > 0) {
                return formatNumber(count) + ' dokter / ' + formatNumber(actionCount) + ' tindakan';
            }

            return count > 0 ? formatNumber(count) + ' dokter / belum ada tindakan' : 'Semua dokter';
        }

        function statusIcon(status) {
            if (status === 'success') {
                return 'mdi-check-circle-outline';
            }

            if (status === 'warning') {
                return 'mdi-alert-circle-outline';
            }

            if (status === 'danger') {
                return 'mdi-close-circle-outline';
            }

            return 'mdi-timer-sand';
        }

        function infoPill(icon, label, value) {
            return `
                <div class="tm-info-pill">
                    <i class="mdi ${escapeHtml(icon)}"></i>
                    <span>${escapeHtml(label)}</span>
                    <strong>${escapeHtml(value)}</strong>
                </div>
            `;
        }

        function bonusCellHtml(total, summary) {
            total = Number(total) || 0;

            if (total <= 0) {
                return '<div class="text-muted">' + formatRupiah(0) + '</div>' +
                    '<div class="small text-muted">' + escapeHtml(summary || 'Tidak ada penerimaan.') +
                    '</div>';
            }

            return '<div class="fw-bold text-success">' + formatRupiah(total) + '</div>' +
                '<div class="small text-muted">' + escapeHtml(summary || 'Ada penerimaan.') + '</div>';
        }

        function renderDistributionRows(selector, distributions, filters = {}) {
            const tbody = $(selector).empty();
            distributions = distributions || [];
            const keyword = String(filters.keyword || '').toLowerCase();
            const bonusFilter = filters.bonus || 'all';
            const filtered = distributions.filter(function(item) {
                const hasBonus = Number(item.total_icu || 0) > 0 || Number(item.total_nicu || 0) > 0;
                const haystack = [
                    item.nik,
                    item.pegawai_name,
                    item.pegawai_position,
                    item.icu_bonus_summary,
                    item.nicu_bonus_summary
                ].join(' ').toLowerCase();

                if (bonusFilter === 'bonus' && !hasBonus) {
                    return false;
                }

                if (bonusFilter === 'no_bonus' && hasBonus) {
                    return false;
                }

                return !keyword || haystack.includes(keyword);
            });
            const maxTotal = Math.max(
                0,
                ...filtered.map(item => Number(item.total_diterima || 0))
            );

            if (!filtered.length) {
                tbody.html('<tr><td colspan="5" class="tm-empty">Pegawai penerima tidak ditemukan.</td></tr>');
                return;
            }

            filtered.forEach(function(item) {
                const percent = maxTotal > 0 ?
                    Math.max(5, Math.round((Number(item.total_diterima || 0) / maxTotal) * 100)) :
                    0;

                tbody.append(`
                    <tr>
                        <td>
                            <div class="fw-semibold">${escapeHtml(item.pegawai_name || '-')}</div>
                            <div class="text-muted">${escapeHtml(item.nik || '-')} ${item.pegawai_position ? ' / ' + escapeHtml(item.pegawai_position) : ''}</div>
                        </td>
                        <td class="text-end">
                            <div class="fw-semibold">${formatRupiah(item.total_dasar || 0)}</div>
                            <div class="small text-muted">${escapeHtml(item.distribution_mode_label || distributionModeLabel(item.distribution_mode))}</div>
                        </td>
                        <td class="text-end">${bonusCellHtml(item.total_icu, item.icu_bonus_summary)}</td>
                        <td class="text-end">${bonusCellHtml(item.total_nicu, item.nicu_bonus_summary)}</td>
                        <td class="text-end">
                            <div class="fw-bold">${formatRupiah(item.total_diterima || 0)}</div>
                            <div class="tm-progress"><span style="width:${percent}%"></span></div>
                        </td>
                    </tr>
                `);
            });
        }

        function pelaksanaLabel(row) {
            if (row.nm_dokter || row.kd_dokter) {
                return [row.kd_dokter, row.nm_dokter].filter(Boolean).join(' - ');
            }

            if (row.nama_petugas || row.nip) {
                return [row.nip, row.nama_petugas].filter(Boolean).join(' - ');
            }

            return '-';
        }

        function setDefaultPeriod() {
            const queryPeriod = new URLSearchParams(window.location.search).get('periode');
            if (/^\d{4}-\d{2}$/.test(queryPeriod || '')) {
                $('#periodeTindakanMedis').val(queryPeriod);
                return;
            }

            const now = new Date();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            $('#periodeTindakanMedis').val(now.getFullYear() + '-' + month);
        }

        function renderMappingPremiOptions() {
            const selects = $('#configMappingPremiUmumTindakan, #configMappingPremiBpjsTindakan');
            selects.empty().append('<option value="">Pilih mapping premi</option>');

            mappingPremiList.forEach(function(item) {
                const option = $('<option>', {
                    value: item.id,
                    text: (item.kode || '-') + ' - ' + (item.jenis || '-') +
                        ' (' + formatNumber(item.jumlah_tindakan) + ' tindakan)'
                });
                $('#configMappingPremiUmumTindakan').append(option.clone());
                $('#configMappingPremiBpjsTindakan').append(
                    $('<option>', {
                        value: item.id,
                        text: (item.kode || '-') + ' - ' + (item.jenis || '-') +
                            ' (' + formatNumber(item.jumlah_tindakan) + ' tindakan)'
                    })
                );
            });

            $('#configMappingPremiUmumTindakan').val(activePremiIds.umum || '');
            $('#configMappingPremiBpjsTindakan').val(activePremiIds.bpjs || '');
        }

        function updateActiveConfig() {
            const premi = activePremi();

            const umumPremi = mappingPremiList.find(item => String(item.id) === String(activePremiIds.umum));
            const bpjsPremi = mappingPremiList.find(item => String(item.id) === String(activePremiIds.bpjs));
            const umumLabel = umumPremi ? (umumPremi.kode || '-') + ' - ' + (umumPremi.jenis || '-') : 'UMUM belum dipilih';
            const bpjsLabel = bpjsPremi ? (bpjsPremi.kode || '-') + ' - ' + (bpjsPremi.jenis || '-') : 'BPJS belum dipilih';

            $('#activeConfigPremiTindakan').text(
                premi ? (activeType === 'bpjs' ? 'BPJS: ' : 'UMUM: ') + activePremiLabel() :
                'Belum ada mapping premi aktif'
            );
            $('#activeConfigPremiTindakanNote').text(
                premi ?
                formatNumber(premi.jumlah_tindakan) + ' tindakan / ' +
                formatNumber(premi.jumlah_pegawai || 0) + ' pegawai / pembagi ' +
                formatNumber(premi.pembagi || 1) + ' / ' +
                distributionModeLabel(distributionMode) + ' / ' +
                formatNumber(karcisSelectedIds.length) + ' karcis BPJS / ' +
                'BPJS ' + (bpjsIgnoreUgd ? 'tanpa UGD' : 'pakai UGD') + ', ' +
                (bpjsIgnoreVk ? 'tanpa VK' : 'pakai VK') + ' / ICU pool ' +
                (includeBpjsIcuPool ? 'aktif' : 'nonaktif') + ' / ' +
                doctorFilterLabel({
                    selected_count: selectedDoctorCodes().length,
                    selected_action_count: selectedDoctorActionIds().length
                }) :
                'Buka konfigurasi untuk memilih mapping premi.'
            );
            $('#configMappingPremiUmumTindakanNote').text(
                umumPremi ?
                formatNumber(umumPremi.jumlah_tindakan) + ' tindakan / ' +
                formatNumber(umumPremi.jumlah_pegawai || 0) + ' pegawai / pembagi ' +
                formatNumber(umumPremi.pembagi || 1) :
                'Pilih mapping premi untuk generate UMUM.'
            );
            $('#configMappingPremiBpjsTindakanNote').text(
                bpjsPremi ?
                formatNumber(bpjsPremi.jumlah_tindakan) + ' tindakan / ' +
                formatNumber(bpjsPremi.jumlah_pegawai || 0) + ' pegawai / pembagi ' +
                formatNumber(bpjsPremi.pembagi || 1) :
                'Pilih mapping premi untuk generate BPJS.'
            );
            $('#configActiveTypeNote').text(
                'Aktif: ' + (activeType === 'bpjs' ? bpjsLabel : umumLabel) +
                '. Aturan sumber memakai gabungan tindakan dari mapping UMUM dan BPJS.'
            );
        }

        function loadActionOptions(premiId) {
            actionOptions = [];

            if (!premiId) {
                renderSourceRuleList();
                renderDoctorActionList();
                return $.Deferred().resolve().promise();
            }

            const url =
                "{{ route("backOffice.keuangan.hitungPremi.generateTindakanMedis.mappingActionOptions", ["id" => "__ID__"]) }}"
                .replace('__ID__', premiId);

            return $.get(url, function(response) {
                actionOptions = response.data || [];
                renderSourceRuleList();
                renderDoctorActionList();
            }).fail(function(xhr) {
                Swal.fire('Gagal', errorMessage(xhr), 'error');
            });
        }

        function mergeActionOptionRows(rows) {
            const map = {};

            rows.forEach(function(item) {
                const key = String(item.id);

                if (!map[key]) {
                    map[key] = item;
                    return;
                }

                map[key].jumlah_mapping_tindakan = Math.max(
                    Number(map[key].jumlah_mapping_tindakan || 0),
                    Number(item.jumlah_mapping_tindakan || 0)
                );
            });

            return Object.values(map).sort(function(a, b) {
                return String(a.jenis || '').localeCompare(String(b.jenis || ''));
            });
        }

        function loadActionOptionsForConfig() {
            const ids = [...new Set([
                String(activePremiIds.umum || ''),
                String(activePremiIds.bpjs || '')
            ].filter(Boolean))];

            actionOptions = [];

            if (!ids.length) {
                renderSourceRuleList();
                renderDoctorActionList();
                return $.Deferred().resolve().promise();
            }

            const requests = ids.map(function(id) {
                const url =
                    "{{ route("backOffice.keuangan.hitungPremi.generateTindakanMedis.mappingActionOptions", ["id" => "__ID__"]) }}"
                    .replace('__ID__', id);

                return $.get(url);
            });

            return $.when.apply($, requests).done(function() {
                const responses = requests.length === 1 ? [arguments[0]] :
                    Array.from(arguments).map(item => item[0]);
                actionOptions = mergeActionOptionRows(
                    responses.flatMap(response => response.data || [])
                );
                renderSourceRuleList();
                renderDoctorActionList();
            }).fail(function(xhr) {
                Swal.fire('Gagal', errorMessage(xhr), 'error');
            });
        }

        function initDoctorSelect() {
            $('#configDoctorFilterTindakan').select2({
                dropdownParent: $('#modalTindakanMedisConfig'),
                width: '100%',
                placeholder: 'Cari dokter rawat dokter',
                allowClear: true,
                ajax: {
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateTindakanMedis.dokterOptions") }}",
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term || ''
                        };
                    },
                    processResults: function(response) {
                        return {
                            results: (response.data || []).map(function(item) {
                                return {
                                    id: item.kd_dokter,
                                    text: item.text,
                                    kd_dokter: item.kd_dokter,
                                    nm_dokter: item.nm_dokter
                                };
                            })
                        };
                    }
                }
            });
        }

        function setSelectedDoctors(doctors) {
            selectedDoctors = doctors || [];
            const select = $('#configDoctorFilterTindakan').empty();

            selectedDoctors.forEach(function(item) {
                const option = new Option(
                    item.text || [item.kd_dokter, item.nm_dokter].filter(Boolean).join(' - '),
                    item.kd_dokter,
                    true,
                    true
                );
                select.append(option);
            });

            select.trigger('change.select2');
            $('#configDoctorCountTindakan').text(doctorFilterLabel({
                selected_count: selectedDoctors.length
            }));
        }

        function loadConfig() {
            return $.get("{{ route("backOffice.keuangan.hitungPremi.generateTindakanMedis.config") }}",
                function(response) {
                    const data = response.data || {};
                    mappingPremiList = data.mapping_options || [];
                    sourceOptions = data.source_options || [];
                    sourceMappings = data.source_mappings || [];
                    actionOptions = data.action_options || [];
                    karcisConfigOptions = (data.karcis || {}).options || [];
                    karcisSelectedIds = ((data.karcis || {}).selected_ids || []).map(Number);
                    doctorActionSelectedIds = ((data.doctor_filter || {}).selected_action_ids || []).map(Number);
                    setSelectedDoctors((data.doctor_filter || {}).selected_doctors || []);
                    activePremiIds = {
                        umum: data.jnsPremi_umum_id ? String(data.jnsPremi_umum_id) : '',
                        bpjs: data.jnsPremi_bpjs_id ? String(data.jnsPremi_bpjs_id) : ''
                    };
                    syncActivePremiId();
                    distributionMode = data.distribution_mode || 'split_evenly';

                    $('#configBpjsSourceModeTindakan').val(data.bpjs_source_mode || 'previous');
                    updateBpjsSourceModeNote();
                    $('#configDistributionModeTindakan').val(distributionMode);
                    $('#configDistributionModeNote').text(data.distribution_mode_label ||
                        distributionModeLabel(distributionMode));
                    $('#configIgnoreIcuTindakan').prop('checked', Boolean(data.ignore_icu));
                    $('#configIgnoreNicuTindakan').prop('checked', Boolean(data.ignore_nicu));
                    bpjsIgnoreUgd = Boolean(data.bpjs_ignore_ugd);
                    bpjsIgnoreVk = Boolean(data.bpjs_ignore_vk);
                    includeBpjsIcuPool = data.include_bpjs_icu_pool !== false;
                    $('#configBpjsIgnoreUgdTindakan').prop('checked', bpjsIgnoreUgd);
                    $('#configBpjsIgnoreVkTindakan').prop('checked', bpjsIgnoreVk);
                    $('#configIncludeBpjsIcuPoolTindakan').prop('checked', includeBpjsIcuPool);
                    renderMappingPremiOptions();
                    renderSourceRuleList();
                    renderDoctorActionList();
                    renderKarcisConfigList();
                    updateActiveConfig();
                }
            ).fail(function(xhr) {
                Swal.fire('Gagal', errorMessage(xhr), 'error');
            });
        }

        function sourceOptionHtml(selectedValue) {
            return sourceOptions.map(function(item) {
                return `<option value="${escapeHtml(item.source_pattern)}" ${String(item.source_pattern) === String(selectedValue) ? 'selected' : ''}>
                    ${escapeHtml(item.label || item.source_pattern)}
                </option>`;
            }).join('');
        }

        function mappingOptionValue(value, jenis) {
            return jenis === 'nominal' ? formatRupiah(value) : formatNumber(value) + '%';
        }

        function mappingOptionMeta(item) {
            const umumJenis = item.jenis_umum || item.jenis_mapping || 'persen';
            const bpjsJenis = item.jenis_bpjs || item.jenis_mapping || 'persen';

            return 'UMUM ' + mappingOptionValue(item.nilai_umum, umumJenis) +
                ' / BPJS ' + mappingOptionValue(item.nilai_bpjs, bpjsJenis);
        }

        function actionOptionHtml(selectedValue) {
            return actionOptions.map(function(item) {
                return `<option value="${escapeHtml(item.id)}" ${String(item.id) === String(selectedValue) ? 'selected' : ''}>
                    ${escapeHtml(item.kode || '-')} - ${escapeHtml(item.jenis || '-')} (${escapeHtml(mappingOptionMeta(item))})
                </option>`;
            }).join('');
        }

        function renderSourceRuleList() {
            const list = $('#sourceRuleList').empty();
            $('#sourceRuleMeta').text(formatNumber(sourceMappings.length) + ' aturan sumber');

            if (!sourceMappings.length) {
                list.html('<div class="tm-empty border rounded">Belum ada aturan sumber.</div>');
                return;
            }

            sourceMappings.forEach(function(item, index) {
                list.append(`
                    <div class="tm-source-rule" data-index="${index}">
                        <div>
                            <label>Sumber Rawat</label>
                            <select class="form-select source-rule-pattern">
                                <option value="">Pilih sumber</option>
                                ${sourceOptionHtml(item.source_pattern)}
                            </select>
                        </div>
                        <div>
                            <label>Tindakan Tujuan</label>
                            <select class="form-select source-rule-action">
                                <option value="">Pilih tindakan</option>
                                ${actionOptionHtml(item.jnsTindakan_id)}
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-remove-source-rule" title="Hapus">
                            <i class="mdi mdi-delete-outline"></i>
                        </button>
                    </div>
                `);
            });
        }

        function renderKarcisConfigList() {
            const list = $('#karcisConfigListTindakan').empty();
            const keyword = String($('#karcisConfigSearchTindakan').val() || '').trim().toLowerCase();
            const filtered = karcisConfigOptions.filter(function(item) {
                const haystack = [
                    item.kode,
                    item.jenis,
                    item.jumlah_mapping_tindakan,
                    item.jumlah_mapping_premi
                ].join(' ').toLowerCase();

                return !keyword || haystack.includes(keyword);
            });

            $('#karcisConfigSelectedCountTindakan').text(
                formatNumber(karcisSelectedIds.length) + ' tindakan dipilih'
            );

            if (!filtered.length) {
                list.html('<div class="tm-empty">Jenis tindakan tidak ditemukan.</div>');
                return;
            }

            filtered.forEach(function(item) {
                const checked = karcisSelectedIds.includes(Number(item.id)) ? 'checked' : '';

                list.append(`
                    <label class="tm-karcis-item">
                        <input type="checkbox" class="form-check-input tm-karcis-check"
                            value="${escapeHtml(item.id)}" ${checked}>
                        <span class="tm-karcis-main">
                            <span class="tm-karcis-title">
                                ${escapeHtml(item.kode || '-')} - ${escapeHtml(item.jenis || '-')}
                            </span>
                            <span class="tm-karcis-meta d-block">
                                ${formatNumber(item.jumlah_mapping_tindakan)} mapping tindakan /
                                ${formatNumber(item.jumlah_mapping_premi)} mapping premi
                            </span>
                        </span>
                    </label>
                `);
            });
        }

        function renderDoctorActionList() {
            const list = $('#configDoctorActionListTindakan').empty();
            const allowedIds = actionOptions.map(item => Number(item.id));
            doctorActionSelectedIds = doctorActionSelectedIds
                .map(Number)
                .filter(id => allowedIds.includes(id));

            $('#configDoctorActionCountTindakan').text(
                formatNumber(doctorActionSelectedIds.length) + ' tindakan dipilih'
            );

            if (!actionOptions.length) {
                list.html('<div class="tm-empty">Pilih mapping premi terlebih dahulu.</div>');
                return;
            }

            actionOptions.forEach(function(item) {
                const value = Number(item.id);
                const checked = doctorActionSelectedIds.includes(value) ? 'checked' : '';
                const meta = mappingOptionMeta(item);

                list.append(`
                    <label class="tm-karcis-item">
                        <input type="checkbox" class="form-check-input tm-doctor-action-check"
                            value="${escapeHtml(item.id)}" ${checked}>
                        <span class="tm-karcis-main">
                            <span class="tm-karcis-title">
                                ${escapeHtml(item.kode || '-')} - ${escapeHtml(item.jenis || '-')}
                            </span>
                            <span class="tm-karcis-meta d-block">
                                ${escapeHtml(meta)} /
                                ${formatNumber(item.jumlah_mapping_tindakan || 0)} mapping tindakan
                            </span>
                        </span>
                    </label>
                `);
            });
        }

        function collectSourceRules() {
            const rows = [];

            $('#sourceRuleList .tm-source-rule').each(function() {
                const sourcePattern = $(this).find('.source-rule-pattern').val();
                const tindakanId = $(this).find('.source-rule-action').val();

                if (sourcePattern && tindakanId) {
                    rows.push({
                        source_pattern: sourcePattern,
                        jnsTindakan_id: tindakanId
                    });
                }
            });

            return rows;
        }

        function renderSourceSelect(selector, options, selectedId, placeholder, ignoredLabel = '') {
            const select = $(selector);
            select.empty();

            if (ignoredLabel) {
                select.append(`<option value="">${escapeHtml(ignoredLabel)}</option>`);
                select.val('');
                select.prop('disabled', true);
                return '';
            }

            select.prop('disabled', false);

            if (!options.length) {
                select.append(`<option value="">${placeholder}</option>`);
                select.val('');
                return '';
            }

            select.append('<option value="">Pilih sumber data</option>');
            options.forEach(function(item) {
                const locked = item.is_locked ? 'Terkunci' : 'Belum dikunci';
                select.append(
                    $('<option>', {
                        value: item.id,
                        text: item.ploting_label + ' - ' + formatRupiah(item.total) +
                            ' (' + locked + ')'
                    })
                );
            });

            select.val(selectedId ? String(selectedId) : '');
            return select.val() || '';
        }

        function renderDependency(prefix, dependency, fallbackLabel) {
            dependency = dependency || {};
            const ignored = Boolean(dependency.ignored);
            const exists = dependency.plotingPremi_id || dependency.exists;
            const locked = Boolean(dependency.is_locked);
            const card = $('#' + prefix + 'Tindakan');
            const value = ignored ? 'Diabaikan' : (exists ? formatRupiah(dependency.total || 0) :
                'Belum tersedia');
            const note = ignored ? (dependency.ploting_label || fallbackLabel + ' BPJS diabaikan') : (exists ?
                (dependency.ploting_label || fallbackLabel) + ' / ' +
                formatNumber(dependency.locked_count || 0) + ' dari ' +
                formatNumber(dependency.generated_count || 0) + ' terkunci' :
                'Generate dan kunci sumber terlebih dahulu.');

            $('#' + prefix + 'Value').text(value);
            $('#' + prefix + 'Note').text(note);
            card.removeClass('border-success border-warning');
            card.toggleClass('border-success', ignored || (exists && locked));
            card.toggleClass('border-warning', !ignored && exists && !locked);
        }

        function renderReadinessSteps(steps) {
            const container = $('#summaryReadinessStepsMedis').empty();
            steps = steps || [];

            if (!steps.length) {
                container.html(`
                    <div class="tm-readiness-item muted">
                        <div class="tm-readiness-icon"><i class="mdi mdi-timer-sand"></i></div>
                        <div>
                            <div class="tm-readiness-label">Kesiapan</div>
                            <div class="tm-readiness-value">Belum dicek</div>
                            <div class="tm-readiness-note">Pilih periode untuk melihat status.</div>
                        </div>
                    </div>
                `);
                return;
            }

            steps.forEach(function(step) {
                const status = step.status || 'muted';
                container.append(`
                    <div class="tm-readiness-item ${escapeHtml(status)}">
                        <div class="tm-readiness-icon">
                            <i class="mdi ${escapeHtml(statusIcon(status))}"></i>
                        </div>
                        <div>
                            <div class="tm-readiness-label">${escapeHtml(step.label || '-')}</div>
                            <div class="tm-readiness-value">${escapeHtml(step.value || '-')}</div>
                            <div class="tm-readiness-note">${escapeHtml(step.note || '-')}</div>
                        </div>
                    </div>
                `);
            });
        }

        function calculationFormulaHtml(data) {
            const mapping = formatRupiah(data.total_mapping_premi || 0);
            const ugd = formatRupiah(data.total_ugd || 0);
            const vk = formatRupiah(data.total_vk || 0);
            const icuPool = formatRupiah(data.total_icu_pool_bpjs || 0);
            const grand = formatRupiah(data.grand_total || 0);
            const finalTotal = formatRupiah(data.total_final || 0);

            return 'Grand total sebelum pembagi: <strong>' + grand + '</strong> ' +
                '<span class="tm-formula-muted">(' + mapping + ' + ' + ugd + ' + ' + vk +
                ' + ' + icuPool + ')</span> ' +
                '&rarr; / ' + formatNumber(data.pembagi || 1) + ' = ' + finalTotal;
        }

        function renderPeriodInfo(data) {
            const filter = data.doctor_filter || {};
            $('#summaryPeriodInfoMedis').html([
                infoPill('mdi-calendar-month-outline', 'Tindakan', (data.source_periode || '-') + ' / ' +
                    (data.bpjs_source_mode_label || 'Periode Generate')),
                infoPill('mdi-hospital-building', 'UGD/VK', (data.dependency_source_periode || data.periode || '-') +
                    ' / Periode Generate'),
                infoPill('mdi-tune-variant', 'Kebijakan', data.dependency_policy_label || 'UGD dan VK aktif'),
                infoPill('mdi-hospital-box-outline', 'Pool ICU BPJS',
                    data.jenis_pelayanan === 'bpjs' && data.include_bpjs_icu_pool ?
                    formatRupiah(data.total_icu_pool_bpjs || 0) : 'Tidak aktif'),
                infoPill('mdi-doctor', 'Dokter', doctorFilterLabel(filter)),
                infoPill('mdi-ticket-confirmation-outline', 'Karcis', formatNumber(data.karcis_config_count || 0) +
                    ' tindakan')
            ].join(''));
        }

        function renderDistributionInsight(data) {
            const insight = data.distribution_insight || {};
            $('#summaryDistributionInsightMedis').html([
                infoPill('mdi-account-group-outline', 'Penerima', formatNumber(insight.jumlah_penerima || 0)),
                infoPill('mdi-calculator', 'Grand Total', formatRupiah(data.grand_total || 0)),
                infoPill('mdi-chart-bell-curve-cumulative', 'Rata-rata', formatRupiah(insight.average_total || 0)),
                infoPill('mdi-arrow-up-bold-circle-outline', 'Tertinggi', formatRupiah(insight.highest_total || 0)),
                infoPill('mdi-plus-circle-outline', 'Bonus', formatNumber(insight.bonus_recipient_count || 0) +
                    ' pegawai / ' + formatRupiah(insight.total_bonus || 0))
            ].join(''));
        }

        function renderPreviewInsight(data) {
            const insight = data.preview_insight || {};
            $('#summaryPreviewInsightMedis').html([
                infoPill('mdi-format-list-bulleted-square', 'Tindakan', formatNumber(insight.jumlah_tindakan || 0)),
                infoPill('mdi-database-search-outline', 'Data', formatNumber(insight.jumlah_data || 0)),
                infoPill('mdi-doctor', 'Dokter', formatNumber(insight.jumlah_data_dokter || 0)),
                infoPill('mdi-account-heart-outline', 'Paramedis', formatNumber(insight.jumlah_data_paramedis || 0)),
                infoPill('mdi-account-switch-outline', 'Dialihkan', formatNumber(insight.jumlah_data_dialihkan_perawat || 0)),
                infoPill('mdi-source-branch', 'Sumber', formatNumber(insight.jumlah_sumber || 0))
            ].join(''));
        }

        function renderDistributionSection(data) {
            const keyword = $('#summaryDistributionSearchMedis').val() || '';
            const bonusFilter = $('#summaryDistributionBonusFilterMedis').val() || 'all';
            const rows = data.distributions || [];
            const filteredCount = rows.filter(function(item) {
                const hasBonus = Number(item.total_icu || 0) > 0 || Number(item.total_nicu || 0) > 0;
                const haystack = [
                    item.nik,
                    item.pegawai_name,
                    item.pegawai_position,
                    item.icu_bonus_summary,
                    item.nicu_bonus_summary
                ].join(' ').toLowerCase();

                if (bonusFilter === 'bonus' && !hasBonus) {
                    return false;
                }

                if (bonusFilter === 'no_bonus' && hasBonus) {
                    return false;
                }

                return !keyword || haystack.includes(String(keyword).toLowerCase());
            }).length;

            $('#summaryDistributionCountMedis').text(
                formatNumber(filteredCount) + ' dari ' + formatNumber(rows.length || 0) + ' penerima'
            );
            renderDistributionInsight(data);
            renderDistributionRows('#summaryDistributionRowsMedis', rows, {
                keyword: keyword,
                bonus: bonusFilter
            });
        }

        function sourceBreakdownLabel(detail) {
            const rows = detail.source_breakdown || [];

            if (!rows.length) {
                return sourceRulesLabel(detail.source_rules);
            }

            return rows
                .slice(0, 2)
                .map(row => (row.label || '-') + ' ' + formatNumber(row.count || 0))
                .join(' / ');
        }

        function detailMatchesPreviewFilter(detail, keyword, sourceFilter) {
            const haystack = [
                detail.kode_jenis_tindakan,
                detail.nama_jenis_tindakan,
                sourceRulesLabel(detail.source_rules),
                sourceBreakdownLabel(detail),
                ...(detail.doctor_breakdown || []).map(row => [row.kd_dokter, row.nm_dokter].join(' '))
            ].join(' ').toLowerCase();

            if (keyword && !haystack.includes(keyword)) {
                return false;
            }

            if (sourceFilter === 'doctor') {
                return Number(detail.jumlah_data_dokter || 0) > 0;
            }

            if (sourceFilter === 'paramedic') {
                return Number(detail.jumlah_data_paramedis || 0) > 0;
            }

            if (sourceFilter === 'drpr') {
                return Number(detail.jumlah_data_drpr || 0) > 0;
            }

            if (sourceFilter === 'routed') {
                return Number(detail.jumlah_data_dialihkan_perawat || 0) > 0;
            }

            if (sourceFilter === 'karcis') {
                return Number(detail.jumlah_data_karcis_bpjs || 0) > 0;
            }

            return true;
        }

        function loadSummary() {
            const periode = $('#periodeTindakanMedis').val();

            if (!periode || !activePremiId) {
                $('#btnGenerateTindakanMedis').prop('disabled', true);
                return;
            }

            $('#summaryTindakanMedisSubtitle').text('Memeriksa data sumber...');
            $('#btnGenerateTindakanMedis').prop('disabled', true);

            $.get("{{ route("backOffice.keuangan.hitungPremi.generateTindakanMedis.summary") }}", {
                periode: periode,
                jenis_pelayanan: activeType,
                jnsPremi_id: activePremiId,
                ugd_plotingPremi_id: selectedUgdSourceId,
                vk_plotingPremi_id: selectedVkSourceId
            }, function(response) {
                summaryData = response.data || {};
                renderSummary(summaryData);
            }).fail(function(xhr) {
                $('#summaryTindakanMedisSubtitle').text(errorMessage(xhr).replace(/<br>/g, ' '));
            });
        }

        function renderSummary(data) {
            const ignoredDependencies = data.ignored_dependencies || {};
            selectedUgdSourceId = renderSourceSelect(
                '#sourceUgdTindakanMedis',
                data.dependency_options?.ugd || [],
                data.selected_ugd_plotingPremi_id || selectedUgdSourceId,
                'Tidak ada sumber UGD',
                ignoredDependencies.ugd ? 'UGD BPJS diabaikan' : ''
            );
            selectedVkSourceId = renderSourceSelect(
                '#sourceVkTindakanMedis',
                data.dependency_options?.vk || [],
                data.selected_vk_plotingPremi_id || selectedVkSourceId,
                'Tidak ada sumber VK',
                ignoredDependencies.vk ? 'VK BPJS diabaikan' : ''
            );

            renderDependency('dependencyUgd', data.dependency_ugd, 'UGD');
            renderDependency('dependencyVk', data.dependency_vk, 'VK');
            renderReadinessSteps(data.readiness_steps || []);
            renderPeriodInfo(data);

            $('#sourceUgdTindakanMedisNote').text(
                ignoredDependencies.ugd ? 'UGD BPJS diabaikan sesuai konfigurasi.' : (selectedUgdSourceId ?
                'Sumber UGD terpilih untuk periode ' + (data.dependency_source_periode || data.periode || '-') + '.' :
                'Pilih hasil UGD sesuai ploting periode ' + (data.dependency_source_periode || data.periode || '-') + '.')
            );
            $('#sourceVkTindakanMedisNote').text(
                ignoredDependencies.vk ? 'VK BPJS diabaikan sesuai konfigurasi.' : (selectedVkSourceId ?
                'Sumber VK terpilih untuk periode ' + (data.dependency_source_periode || data.periode || '-') + '.' :
                'Pilih hasil VK sesuai ploting periode ' + (data.dependency_source_periode || data.periode || '-') + '.')
            );

            const karcisText = data.karcis_rule_message ?
                ' ' + data.karcis_rule_message +
                (activeType === 'umum' && Number(data.karcis_config_count) > 0 ?
                    ' Sumber karcis: ' + (data.karcis_source_period || '-') + '.' :
                    '') :
                '';

            $('#summaryTindakanMedisSubtitle').text(
                data.readiness_message + ' Tindakan: ' + (data.source_periode || '-') +
                ' / UGD-VK: ' + (data.dependency_source_periode || data.periode || '-') +
                ' / ' + (data.dependency_policy_label || 'UGD dan VK aktif') + karcisText
            );
            $('#summaryTransaksiMedis').text(formatNumber(data.jumlah_transaksi));
            $('#summaryPasienMedis').text(formatNumber(data.jumlah_pasien) + ' pasien / ' +
                formatNumber(data.jumlah_jenis_tindakan) + ' jenis tindakan');
            $('#summaryBiayaRawatMedis').text(formatRupiah(data.total_biaya_rawat));
            $('#summaryIgnoreMedis').text(
                formatNumber(data.jumlah_terabaikan_icu) + ' ICU / ' +
                formatNumber(data.jumlah_terabaikan_nicu) + ' NICU diabaikan'
            );
            $('#summaryMappingMedis').text(formatRupiah(data.total_mapping_premi));
            $('#summaryJumlahMappingMedis').text(formatNumber(data.jumlah_mapping_premi) + ' mapping terhitung');
            $('#summaryUgdVkMedis').text(formatRupiah((Number(data.total_ugd) || 0) + (Number(data.total_vk) || 0)));
            $('#summaryUgdVkNote').text(
                'UGD ' + (ignoredDependencies.ugd ? 'diabaikan' : formatRupiah(data.total_ugd)) +
                ' / VK ' + (ignoredDependencies.vk ? 'diabaikan' : formatRupiah(data.total_vk))
            );
            const icuPool = data.icu_pool_bpjs || {};
            const showIcuPool = data.jenis_pelayanan === 'bpjs' && data.include_bpjs_icu_pool;
            $('#summaryIcuPoolBpjsMedis').text(formatRupiah(showIcuPool ? data.total_icu_pool_bpjs : 0));
            $('#summaryIcuPoolBpjsNote').text(showIcuPool ?
                formatNumber(icuPool.locked_count || 0) + ' dari ' +
                formatNumber(icuPool.source_count || 0) + ' ICU BPJS terkunci' :
                'Hanya ditambahkan saat BPJS dan opsi aktif'
            );
            $('#summaryGrandMedis').text(formatRupiah(data.grand_total));
            $('#summaryFinalMedis').text('Setelah pembagi: ' + formatRupiah(data.total_final));
            $('#summaryPembagiMedis').text('Pembagi: ' + formatNumber(data.pembagi || 1));
            $('#summaryDibagikanMedis').text(formatRupiah(data.total_dibagikan || 0));
            $('#summaryTambahanMedis').text('ICU ' + formatRupiah(data.total_tambahan_icu || 0) +
                ' / NICU ' + formatRupiah(data.total_tambahan_nicu || 0));
            $('#summaryDibagikanNote').text(
                formatNumber(data.jumlah_penerima || 0) + ' penerima / ' +
                (data.distribution_mode_label || distributionModeLabel(data.distribution_mode))
            );
            $('#summaryFormulaMedis').html(calculationFormulaHtml(data));
            $('#summaryDistributionNoteMedis').text(
                'Grand total ' + formatRupiah(data.grand_total || 0) +
                ' / Setelah pembagi ' + formatRupiah(data.total_final || 0) +
                ' / ' + (data.distribution_mode_label || distributionModeLabel(data.distribution_mode)) +
                ' / Total dasar ' + formatRupiah(data.total_dasar_dibagikan || 0) +
                ' / Pool ICU BPJS ' + formatRupiah(data.total_icu_pool_bpjs || 0) +
                ' / Tambahan ICU+NICU ' +
                formatRupiah((Number(data.total_tambahan_icu) || 0) + (Number(data.total_tambahan_nicu) || 0))
            );
            renderDistributionSection(data);

            renderPreviewDetails(data.preview_details || []);
            $('#btnGenerateTindakanMedis').prop('disabled', !(data.ready && !data.is_locked));
        }

        function renderPreviewDetails(details) {
            const tbody = $('#summaryPreviewRowsMedis').empty();
            details = details || [];
            const keyword = String($('#summaryPreviewSearchMedis').val() || '').toLowerCase();
            const sourceFilter = $('#summaryPreviewSourceFilterMedis').val() || 'all';
            const filtered = details.filter(function(detail) {
                return detailMatchesPreviewFilter(detail, keyword, sourceFilter);
            });

            $('#summaryPreviewCountMedis').text(
                formatNumber(filtered.length) + ' dari ' + formatNumber(details.length) + ' tindakan'
            );
            renderPreviewInsight(summaryData || {
                preview_insight: {}
            });

            if (!filtered.length) {
                tbody.html('<tr><td colspan="6" class="tm-empty">Tidak ada transaksi rawat yang cocok.</td></tr>');
                return;
            }

            filtered.forEach(function(detail) {
                tbody.append(`
                    <tr>
                        <td>
                            <div class="fw-semibold">${escapeHtml(detail.kode_jenis_tindakan || '-')}</div>
                            <div class="text-muted">${escapeHtml(detail.nama_jenis_tindakan || '-')}</div>
                            ${Number(detail.jumlah_data_karcis_bpjs) > 0 ? `
                                <div class="small text-primary mt-1">
                                    +${formatNumber(detail.jumlah_data_karcis_bpjs)} transaksi karcis BPJS
                                </div>
                            ` : ''}
                            ${Number(detail.jumlah_data_dialihkan_perawat) > 0 ? `
                                <div class="tm-route-note">
                                    ${formatNumber(detail.jumlah_data_dialihkan_perawat)} dokter dialihkan ke perawat
                                </div>
                            ` : ''}
                        </td>
                        <td>
                            <div>${escapeHtml(sourceRulesLabel(detail.source_rules))}</div>
                            <div class="small text-muted mt-1">${escapeHtml(sourceBreakdownLabel(detail))}</div>
                            <div class="small text-muted">
                                Dokter ${formatNumber(detail.jumlah_data_dokter || 0)} /
                                Paramedis ${formatNumber(detail.jumlah_data_paramedis || 0)}
                            </div>
                            ${Number(detail.jumlah_data_drpr || 0) > 0 ? `
                                <div class="small text-muted">
                                    Dokter-paramedis ${formatNumber(detail.jumlah_data_drpr || 0)}
                                </div>
                            ` : ''}
                        </td>
                        <td class="text-center">${formatNumber(detail.jumlah_data)}</td>
                        <td class="text-end">${baseValueLabel(detail)}</td>
                        <td class="text-end">
                            <span class="tm-kind ${escapeHtml(detail.jenis_mapping)}">
                                ${escapeHtml(detail.jenis_mapping)}
                            </span>
                            <div class="small text-muted mt-1">${mappingValueLabel(detail)}</div>
                        </td>
                        <td class="text-end fw-bold">${formatRupiah(detail.hasil_mapping)}</td>
                    </tr>
                `);
            });
        }

        const resultTable = $('#tableGenerateTindakanMedis').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route("backOffice.keuangan.hitungPremi.generateTindakanMedis.table") }}",
                data: function(data) {
                    data.periode = $('#periodeTindakanMedis').val();
                    data.jenis_pelayanan = activeType;
                    data.jnsPremi_id = activePremiId;
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
                    data: 'jenis_pelayanan_label',
                    render: function(data, type, row) {
                        return typeBadge(row.jenis_pelayanan, data);
                    }
                },
                {
                    data: 'nama_premi',
                    render: function(data, type, row) {
                        return '<div class="fw-semibold">' + escapeHtml(row.kode_premi || '-') +
                            '</div><div class="text-muted">' + escapeHtml(data || '-') + '</div>';
                    }
                },
                {
                    data: 'jumlah_transaksi',
                    className: 'text-center',
                    render: formatNumber
                },
                {
                    data: 'total_mapping_premi',
                    className: 'text-end',
                    render: formatRupiah
                },
                {
                    data: 'total_ugd',
                    className: 'text-end',
                    render: formatRupiah
                },
                {
                    data: 'total_vk',
                    className: 'text-end',
                    render: formatRupiah
                },
                {
                    data: 'total_icu_pool_bpjs',
                    className: 'text-end',
                    render: formatRupiah
                },
                {
                    data: 'grand_total',
                    className: 'text-end fw-bold',
                    render: function(data, type, row) {
                        if (type === 'sort' || type === 'type') {
                            return Number(data) || 0;
                        }

                        return '<div class="text-success">' + formatRupiah(data) + '</div>' +
                            '<div class="small text-muted">Setelah pembagi ' +
                            formatRupiah(row.total_final || 0) + '</div>' +
                            '<div class="small text-muted">Dibagikan ' +
                            formatRupiah(row.total_dibagikan || 0) + '</div>';
                    }
                },
                {
                    data: 'is_locked',
                    render: function(value, type, row) {
                        return value ?
                            '<span class="badge bg-success">Terkunci</span>' :
                            '<span class="badge bg-warning text-dark">Terbuka</span>';
                    }
                },
                {
                    data: 'generate_by_name',
                    render: function(data, type, row) {
                        return escapeHtml(data || '-') +
                            '<div class="small text-muted">' + escapeHtml(row.generated_at || '-') + '</div>';
                    }
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ],
            order: [
                [1, 'desc']
            ]
        });

        function reloadTable() {
            resultTable.ajax.reload(null, false);
        }

        function saveConfig() {
            const payload = {
                jnsPremi_umum_id: $('#configMappingPremiUmumTindakan').val(),
                jnsPremi_bpjs_id: $('#configMappingPremiBpjsTindakan').val(),
                bpjs_source_mode: $('#configBpjsSourceModeTindakan').val(),
                distribution_mode: $('#configDistributionModeTindakan').val(),
                ignore_icu: $('#configIgnoreIcuTindakan').is(':checked') ? 1 : 0,
                ignore_nicu: $('#configIgnoreNicuTindakan').is(':checked') ? 1 : 0,
                bpjs_ignore_ugd: $('#configBpjsIgnoreUgdTindakan').is(':checked') ? 1 : 0,
                bpjs_ignore_vk: $('#configBpjsIgnoreVkTindakan').is(':checked') ? 1 : 0,
                include_bpjs_icu_pool: $('#configIncludeBpjsIcuPoolTindakan').is(':checked') ? 1 : 0,
                source_mappings: collectSourceRules(),
                jnsTindakan_id: karcisSelectedIds,
                doctor_codes: selectedDoctorCodes(),
                doctor_tindakan_ids: selectedDoctorActionIds()
            };

            $('#btnSaveConfigTindakanMedis').prop('disabled', true);
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateTindakanMedis.updateConfig") }}",
                method: 'PUT',
                data: payload
            }).done(function(response) {
                const data = response.data || {};
                mappingPremiList = data.mapping_options || mappingPremiList;
                activePremiIds = {
                    umum: data.jnsPremi_umum_id ? String(data.jnsPremi_umum_id) : '',
                    bpjs: data.jnsPremi_bpjs_id ? String(data.jnsPremi_bpjs_id) : ''
                };
                syncActivePremiId();
                distributionMode = data.distribution_mode || distributionMode;
                bpjsIgnoreUgd = Boolean(data.bpjs_ignore_ugd);
                bpjsIgnoreVk = Boolean(data.bpjs_ignore_vk);
                includeBpjsIcuPool = data.include_bpjs_icu_pool !== false;
                actionOptions = data.action_options || [];
                sourceOptions = data.source_options || sourceOptions;
                sourceMappings = data.source_mappings || [];
                karcisConfigOptions = (data.karcis || {}).options || karcisConfigOptions;
                karcisSelectedIds = ((data.karcis || {}).selected_ids || karcisSelectedIds).map(Number);
                doctorActionSelectedIds = ((data.doctor_filter || {}).selected_action_ids || doctorActionSelectedIds)
                    .map(Number);
                setSelectedDoctors((data.doctor_filter || {}).selected_doctors || []);
                $('#configBpjsIgnoreUgdTindakan').prop('checked', bpjsIgnoreUgd);
                $('#configBpjsIgnoreVkTindakan').prop('checked', bpjsIgnoreVk);
                $('#configIncludeBpjsIcuPoolTindakan').prop('checked', includeBpjsIcuPool);
                renderMappingPremiOptions();
                renderSourceRuleList();
                renderDoctorActionList();
                renderKarcisConfigList();
                updateActiveConfig();
                configModal.hide();
                Swal.fire('Berhasil', response.message || 'Konfigurasi disimpan.', 'success');
                selectedUgdSourceId = '';
                selectedVkSourceId = '';
                loadSummary();
                reloadTable();
            }).fail(function(xhr) {
                Swal.fire('Gagal', errorMessage(xhr), 'error');
            }).always(function() {
                $('#btnSaveConfigTindakanMedis').prop('disabled', false);
            });
        }

        function generateData() {
            if (!summaryData || !summaryData.ready) {
                Swal.fire('Belum Siap', summaryData?.readiness_message || 'Data sumber belum lengkap.', 'warning');
                return;
            }

            Swal.fire({
                title: 'Generate tindakan medis?',
                html: 'Grand total sebelum pembagi: <strong>' + formatRupiah(summaryData.grand_total) +
                    '</strong><br>Setelah pembagi: <strong>' +
                    formatRupiah(summaryData.total_final) + '</strong><br>Total ke pegawai: <strong>' +
                    formatRupiah(summaryData.total_dibagikan || 0) + '</strong>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Generate',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                $('#btnGenerateTindakanMedis').prop('disabled', true);
                $.post("{{ route("backOffice.keuangan.hitungPremi.generateTindakanMedis.store") }}", {
                    periode: $('#periodeTindakanMedis').val(),
                    jenis_pelayanan: activeType,
                    jnsPremi_id: activePremiId,
                    ugd_plotingPremi_id: selectedUgdSourceId,
                    vk_plotingPremi_id: selectedVkSourceId
                }).done(function(response) {
                    Swal.fire('Berhasil', response.message || 'Premi berhasil digenerate.', 'success');
                    loadSummary();
                    reloadTable();
                }).fail(function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                    $('#btnGenerateTindakanMedis').prop('disabled', false);
                });
            });
        }

        function toggleLock(id, locked) {
            const url = locked ?
                "{{ route("backOffice.keuangan.hitungPremi.generateTindakanMedis.lock", ["id" => "__ID__"]) }}" :
                "{{ route("backOffice.keuangan.hitungPremi.generateTindakanMedis.unlock", ["id" => "__ID__"]) }}";

            Swal.fire({
                title: locked ? 'Kunci data?' : 'Buka kunci data?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: locked ? 'Kunci' : 'Buka',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.post(url.replace('__ID__', id))
                    .done(function(response) {
                        Swal.fire('Berhasil', response.message || 'Status data diperbarui.', 'success');
                        loadSummary();
                        reloadTable();
                    })
                    .fail(function(xhr) {
                        Swal.fire('Gagal', errorMessage(xhr), 'error');
                    });
            });
        }

        function detailKey(detail, index) {
            return String(detail.id ? 'detail-' + detail.id : (detail.mapping_premi_id || 'mapping') + '-' + index);
        }

        function renderDetail(data) {
            detailData = data || {};
            detailMappings = data.details || [];
            selectedDetailMappingId = detailMappings.length ? detailKey(detailMappings[0], 0) : '';

            $('#detailTindakanMedisMeta').text(
                data.jenis_pelayanan_label + ' / ' + data.periode + ' / sumber ' + data.source_periode
            );
            $('#detailJumlahTransaksiMedis').text(formatNumber(data.jumlah_transaksi));
            $('#detailBiayaRawatMedis').text(formatRupiah(data.total_biaya_rawat));
            $('#detailMappingMedis').text(formatRupiah(data.total_mapping_premi));
            $('#detailUgdMedis').text(formatRupiah(data.total_ugd));
            $('#detailVkMedis').text(formatRupiah(data.total_vk));
            $('#detailIcuPoolBpjsMedis').text(formatRupiah(data.total_icu_pool_bpjs || 0));
            $('#detailGrandMedis').text(formatRupiah(data.grand_total));
            $('#detailFinalMedis').text(formatRupiah(data.total_final));
            $('#detailDibagikanMedis').text(formatRupiah(data.total_dibagikan || 0));
            $('#detailFormulaMedis').html(calculationFormulaHtml(data));
            $('#detailDistributionCountMedis').text(formatNumber(data.jumlah_penerima || 0) + ' penerima');
            $('#detailDistributionNoteMedis').text(
                'Grand total ' + formatRupiah(data.grand_total || 0) +
                ' / Setelah pembagi ' + formatRupiah(data.total_final || 0) +
                ' / ' + (data.distribution_mode_label || distributionModeLabel(data.distribution_mode)) +
                ' / Pool ICU BPJS ' + formatRupiah(data.total_icu_pool_bpjs || 0) +
                ' / ICU ' + formatRupiah(data.total_tambahan_icu || 0) +
                ' / NICU ' + formatRupiah(data.total_tambahan_nicu || 0)
            );
            renderDetailInsight(data);
            renderDetailDistributionSection();

            renderDetailMappingRows();
            renderDetailMappingFilter();
            renderDetailRawat();
            $('#detailTindakanMedisLoading').addClass('d-none');
            $('#detailTindakanMedisContent').removeClass('d-none');
        }

        function renderDetailInsight(data) {
            const details = data.details || [];
            const shifted = details.reduce(function(total, detail) {
                return total + (Number(detail.jumlah_data_dialihkan_perawat) || 0);
            }, 0);

            $('#detailInsightMedis').html([
                infoPill('mdi-calendar-month-outline', 'Sumber', (data.source_periode || '-') + ' / ' +
                    (data.bpjs_source_mode_label || '-')),
                infoPill('mdi-format-list-checks', 'Jenis Tindakan', formatNumber(data.jumlah_jenis_tindakan || 0)),
                infoPill('mdi-calculator', 'Grand Total', formatRupiah(data.grand_total || 0)),
                infoPill('mdi-hospital-box-outline', 'Pool ICU BPJS', formatRupiah(data.total_icu_pool_bpjs || 0)),
                infoPill('mdi-tune-variant', 'UGD/VK', data.dependency_policy_label || 'UGD dan VK aktif'),
                infoPill('mdi-account-switch-outline', 'Dialihkan', formatNumber(shifted) + ' rawat'),
                infoPill('mdi-plus-circle-outline', 'Bonus Medis', 'ICU ' + formatRupiah(data.total_tambahan_icu || 0) +
                    ' / NICU ' + formatRupiah(data.total_tambahan_nicu || 0))
            ].join(''));
        }

        function renderDetailDistributionSection() {
            const data = detailData || {};
            const rows = data.distributions || [];
            const keyword = $('#searchDetailDistributionMedis').val() || '';
            const bonusFilter = $('#filterDetailDistributionBonusMedis').val() || 'all';
            const filteredCount = rows.filter(function(item) {
                const hasBonus = Number(item.total_icu || 0) > 0 || Number(item.total_nicu || 0) > 0;
                const haystack = [
                    item.nik,
                    item.pegawai_name,
                    item.pegawai_position,
                    item.icu_bonus_summary,
                    item.nicu_bonus_summary
                ].join(' ').toLowerCase();

                if (bonusFilter === 'bonus' && !hasBonus) {
                    return false;
                }

                if (bonusFilter === 'no_bonus' && hasBonus) {
                    return false;
                }

                return !keyword || haystack.includes(String(keyword).toLowerCase());
            }).length;

            $('#detailDistributionCountMedis').text(
                formatNumber(filteredCount) + ' dari ' + formatNumber(rows.length || 0) + ' penerima'
            );
            renderDistributionRows('#detailDistributionRowsMedis', rows, {
                keyword: keyword,
                bonus: bonusFilter
            });
        }

        function renderDetailMappingRows() {
            const tbody = $('#detailMappingRowsMedis').empty();
            const keyword = String($('#searchDetailMappingMedis').val() || '').toLowerCase();
            const filtered = detailMappings.filter(function(detail) {
                const haystack = [
                    detail.kode_jenis_tindakan,
                    detail.nama_jenis_tindakan,
                    sourceRulesLabel(detail.source_rules),
                    sourceBreakdownLabel(detail),
                    mappingValueLabel(detail)
                ].join(' ').toLowerCase();

                return !keyword || haystack.includes(keyword);
            });

            $('#detailMappingCountMedis').text(
                formatNumber(filtered.length) + ' dari ' + formatNumber(detailMappings.length) + ' mapping'
            );

            if (!filtered.length) {
                tbody.html('<tr><td colspan="7" class="tm-empty">Tidak ada detail mapping.</td></tr>');
                return;
            }

            filtered.forEach(function(detail) {
                const originalIndex = detailMappings.indexOf(detail);
                const rowKey = detailKey(detail, originalIndex);
                const active = rowKey === String(selectedDetailMappingId) ?
                    'table-active' : '';

                tbody.append(`
                    <tr class="detail-mapping-row ${active}" data-id="${escapeHtml(rowKey)}">
                        <td>
                            <div class="fw-semibold">${escapeHtml(detail.kode_jenis_tindakan || '-')}</div>
                            <div class="text-muted">${escapeHtml(detail.nama_jenis_tindakan || '-')}</div>
                            ${Number(detail.jumlah_data_karcis_bpjs) > 0 ? `
                                <div class="small text-primary mt-1">
                                    +${formatNumber(detail.jumlah_data_karcis_bpjs)} transaksi karcis BPJS
                                </div>
                            ` : ''}
                            ${Number(detail.jumlah_data_dialihkan_perawat) > 0 ? `
                                <div class="tm-route-note">
                                    ${formatNumber(detail.jumlah_data_dialihkan_perawat)} dialihkan ke perawat
                                </div>
                            ` : ''}
                        </td>
                        <td>${escapeHtml(sourceRulesLabel(detail.source_rules))}</td>
                        <td><span class="tm-kind ${escapeHtml(detail.jenis_mapping)}">${escapeHtml(detail.jenis_mapping)}</span></td>
                        <td class="text-end">${mappingValueLabel(detail)}</td>
                        <td class="text-center">${formatNumber(detail.jumlah_data)}</td>
                        <td class="text-end">${baseValueLabel(detail)}</td>
                        <td class="text-end fw-bold">${formatRupiah(detail.hasil_mapping)}</td>
                    </tr>
                `);
            });
        }

        function renderDetailMappingFilter() {
            const select = $('#filterDetailMappingMedis').empty();

            detailMappings.forEach(function(detail, index) {
                select.append(
                    $('<option>', {
                        value: detailKey(detail, index),
                        text: (detail.kode_jenis_tindakan || '-') + ' - ' +
                            (detail.nama_jenis_tindakan || '-')
                    })
                );
            });

            select.val(selectedDetailMappingId);
        }

        function selectedDetailMapping() {
            return detailMappings.find(function(detail, index) {
                return detailKey(detail, index) === String(selectedDetailMappingId);
            });
        }

        function renderDetailRawat() {
            const detail = selectedDetailMapping();
            const sourceRows = $('#detailSourceRowsMedis').empty();
            const rawRows = $('#detailRawatRowsMedis').empty();

            if (!detail) {
                sourceRows.html('<tr><td colspan="3" class="tm-empty">Pilih mapping.</td></tr>');
                rawRows.html('<tr><td colspan="9" class="tm-empty">Pilih mapping.</td></tr>');
                return;
            }

            const rawat = detail.data_rawat || [];
            const grouped = {};
            rawat.forEach(function(row) {
                const key = row.source_table || '-';
                if (!grouped[key]) {
                    grouped[key] = {
                        label: row.source_label || key,
                        count: 0,
                        total: 0
                    };
                }
                grouped[key].count += 1;
                grouped[key].total += Number(row.biaya_rawat) || 0;
            });
            const sources = Object.values(grouped);
            const selectedSource = $('#filterDetailRawatSourceMedis').val() || 'all';
            const sourceSelect = $('#filterDetailRawatSourceMedis').empty()
                .append('<option value="all">Semua sumber</option>');

            sources.forEach(function(item) {
                sourceSelect.append(
                    $('<option>', {
                        value: item.label,
                        text: item.label + ' (' + formatNumber(item.count) + ')'
                    })
                );
            });

            sourceSelect.val(
                selectedSource !== 'all' && sources.some(item => item.label === selectedSource) ?
                selectedSource :
                'all'
            );

            $('#detailSourceMetaMedis').text(
                formatNumber(rawat.length) + ' rawat untuk ' + (detail.nama_jenis_tindakan || '-')
            );
            $('#detailSourceCountMedis').text(formatNumber(sources.length) + ' sumber');

            if (!sources.length) {
                sourceRows.html('<tr><td colspan="3" class="tm-empty">Tidak ada sumber.</td></tr>');
            } else {
                sources.forEach(function(item) {
                    sourceRows.append(`
                        <tr class="tm-detail-clickable detail-source-row" data-source="${escapeHtml(item.label)}">
                            <td>${escapeHtml(item.label)}</td>
                            <td class="text-center">${formatNumber(item.count)}</td>
                            <td class="text-end">${formatRupiah(item.total)}</td>
                        </tr>
                    `);
                });
            }

            const keyword = String($('#searchDetailRawatMedis').val() || '').toLowerCase();
            const sourceFilter = $('#filterDetailRawatSourceMedis').val() || 'all';
            const executorFilter = $('#filterDetailRawatPelaksanaMedis').val() || 'all';
            const filtered = rawat.filter(function(row) {
                const sourceLabel = row.source_label || row.source_table || '-';
                const hasDoctor = Boolean(row.kd_dokter || row.nm_dokter);
                const hasParamedic = Boolean(row.nip || row.nama_petugas);
                const isDrpr = ['rawat_jl_drpr', 'rawat_inap_drpr'].includes(row.source_table);
                const isRouted = row.route_reason === 'doctor_filter_non_selected';
                const isKarcis = row.jenis_pelayanan_sumber === 'bpjs_karcis';
                const haystack = [
                    row.no_rawat,
                    row.no_rkm_medis,
                    row.nm_pasien,
                    row.kd_tindakan,
                    row.nm_tindakan,
                    row.source_label,
                    row.nama_penjamin,
                    pelaksanaLabel(row),
                    row.route_label
                ].join(' ').toLowerCase();

                if (sourceFilter !== 'all' && sourceLabel !== sourceFilter) {
                    return false;
                }

                if (executorFilter === 'doctor' && !hasDoctor) {
                    return false;
                }

                if (executorFilter === 'paramedic' && !hasParamedic) {
                    return false;
                }

                if (executorFilter === 'drpr' && !isDrpr) {
                    return false;
                }

                if (executorFilter === 'routed' && !isRouted) {
                    return false;
                }

                if (executorFilter === 'karcis' && !isKarcis) {
                    return false;
                }

                return !keyword || haystack.includes(keyword);
            });

            $('#detailRawatMetaMedis').text(formatNumber(filtered.length) + ' dari ' +
                formatNumber(rawat.length) + ' rawat ditampilkan');
            $('#detailSelectedInsightMedis').html([
                infoPill('mdi-database-outline', 'Rawat', formatNumber(detail.jumlah_data || rawat.length)),
                infoPill('mdi-doctor', 'Dokter', formatNumber(detail.jumlah_data_dokter || 0)),
                infoPill('mdi-account-heart-outline', 'Paramedis', formatNumber(detail.jumlah_data_paramedis || 0)),
                infoPill('mdi-account-multiple-outline', 'Dokter-Paramedis', formatNumber(detail.jumlah_data_drpr || 0)),
                infoPill('mdi-account-switch-outline', 'Dialihkan', formatNumber(detail.jumlah_data_dialihkan_perawat || 0)),
                infoPill('mdi-cash-multiple', 'Hasil', formatRupiah(detail.hasil_mapping || 0))
            ].join(''));

            if (!filtered.length) {
                rawRows.html('<tr><td colspan="9" class="tm-empty">Data rawat tidak ditemukan.</td></tr>');
                return;
            }

            filtered.forEach(function(row, index) {
                rawRows.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td>${escapeHtml(row.tanggal || '-')}<div class="small text-muted">${escapeHtml(row.jam || '-')}</div></td>
                        <td>${escapeHtml(row.no_rawat || '-')}</td>
                        <td>${escapeHtml(row.nm_pasien || '-')}<div class="small text-muted">${escapeHtml(row.no_rkm_medis || '-')}</div></td>
                        <td>
                            ${escapeHtml(row.source_label || row.source_table || '-')}
                            ${row.route_label ? `<div class="tm-route-note">${escapeHtml(row.route_label)}</div>` : ''}
                        </td>
                        <td>${escapeHtml(row.kd_tindakan || '-')}<div class="small text-muted">${escapeHtml(row.nm_tindakan || '-')}</div></td>
                        <td>${escapeHtml(row.nama_penjamin || row.kd_pj || '-')}</td>
                        <td>${escapeHtml(pelaksanaLabel(row))}</td>
                        <td class="text-end">${formatRupiah(row.biaya_rawat)}</td>
                    </tr>
                `);
            });
        }

        $('#btnConfigTindakanMedis').on('click', function() {
            $('#karcisConfigSearchTindakan').val('');
            renderMappingPremiOptions();
            renderSourceRuleList();
            renderDoctorActionList();
            renderKarcisConfigList();
            configModal.show();
        });

        $('#configMappingPremiUmumTindakan, #configMappingPremiBpjsTindakan').on('change', function() {
            activePremiIds = {
                umum: String($('#configMappingPremiUmumTindakan').val() || ''),
                bpjs: String($('#configMappingPremiBpjsTindakan').val() || '')
            };
            syncActivePremiId();
            sourceMappings = [];
            doctorActionSelectedIds = [];
            updateActiveConfig();
            loadActionOptionsForConfig();
        });

        $('#configDistributionModeTindakan').on('change', function() {
            distributionMode = $(this).val() || 'split_evenly';
            $('#configDistributionModeNote').text(distributionModeLabel(distributionMode));
            updateActiveConfig();
        });

        $('#configBpjsSourceModeTindakan').on('change', updateBpjsSourceModeNote);

        $('#configBpjsIgnoreUgdTindakan, #configBpjsIgnoreVkTindakan, #configIncludeBpjsIcuPoolTindakan').on('change', function() {
            bpjsIgnoreUgd = $('#configBpjsIgnoreUgdTindakan').is(':checked');
            bpjsIgnoreVk = $('#configBpjsIgnoreVkTindakan').is(':checked');
            includeBpjsIcuPool = $('#configIncludeBpjsIcuPoolTindakan').is(':checked');
            updateActiveConfig();
        });

        $('#btnAddSourceRule').on('click', function() {
            sourceMappings = collectSourceRules();
            sourceMappings.push({
                source_pattern: sourceOptions[0]?.source_pattern || '',
                jnsTindakan_id: actionOptions[0]?.id || ''
            });
            renderSourceRuleList();
        });

        $('#sourceRuleList').on('click', '.btn-remove-source-rule', function() {
            $(this).closest('.tm-source-rule').remove();
            sourceMappings = collectSourceRules();
            renderSourceRuleList();
        });

        $('#karcisConfigSearchTindakan').on('input', renderKarcisConfigList);

        $('#karcisConfigListTindakan').on('change', '.tm-karcis-check', function() {
            const value = Number($(this).val());

            if ($(this).is(':checked')) {
                if (!karcisSelectedIds.includes(value)) {
                    karcisSelectedIds.push(value);
                }
            } else {
                karcisSelectedIds = karcisSelectedIds.filter(id => id !== value);
            }

            renderKarcisConfigList();
            updateActiveConfig();
        });

        $('#configDoctorFilterTindakan').on('change', function() {
            $('#configDoctorCountTindakan').text(doctorFilterLabel({
                selected_count: selectedDoctorCodes().length,
                selected_action_count: selectedDoctorActionIds().length
            }));
            updateActiveConfig();
        });

        $('#configDoctorActionListTindakan').on('change', '.tm-doctor-action-check', function() {
            const value = Number($(this).val());

            if ($(this).is(':checked')) {
                if (!doctorActionSelectedIds.includes(value)) {
                    doctorActionSelectedIds.push(value);
                }
            } else {
                doctorActionSelectedIds = doctorActionSelectedIds.filter(id => id !== value);
            }

            renderDoctorActionList();
            $('#configDoctorCountTindakan').text(doctorFilterLabel({
                selected_count: selectedDoctorCodes().length,
                selected_action_count: selectedDoctorActionIds().length
            }));
            updateActiveConfig();
        });

        $('#summaryDistributionSearchMedis, #summaryDistributionBonusFilterMedis').on('input change', function() {
            if (summaryData) {
                renderDistributionSection(summaryData);
            }
        });

        $('#summaryPreviewSearchMedis, #summaryPreviewSourceFilterMedis').on('input change', function() {
            if (summaryData) {
                renderPreviewDetails(summaryData.preview_details || []);
            }
        });

        $('#btnSaveConfigTindakanMedis').on('click', saveConfig);
        $('#btnGenerateTindakanMedis').on('click', generateData);

        $('#periodeTindakanMedis').on('change', function() {
            selectedUgdSourceId = '';
            selectedVkSourceId = '';
            loadSummary();
            reloadTable();
        });

        $('#sourceUgdTindakanMedis').on('change', function() {
            selectedUgdSourceId = String($(this).val() || '');
            loadSummary();
        });

        $('#sourceVkTindakanMedis').on('change', function() {
            selectedVkSourceId = String($(this).val() || '');
            loadSummary();
        });

        $('.tm-tab').on('click', function() {
            activeType = $(this).data('type');
            syncActivePremiId();
            selectedUgdSourceId = '';
            selectedVkSourceId = '';
            $('.tm-tab').removeClass('active');
            $(this).addClass('active');
            $('#activeTypeBadgeMedis')
                .removeClass('umum bpjs')
                .addClass(activeType)
                .text(activeType === 'bpjs' ? 'BPJS' : 'UMUM');
            updateActiveConfig();
            loadSummary();
            reloadTable();
        });

        $('#tableGenerateTindakanMedis').on('click', '.btn-detail-tindakan-medis', function() {
            const id = $(this).data('id');
            const url =
                "{{ route("backOffice.keuangan.hitungPremi.generateTindakanMedis.detail", ["id" => "__ID__"]) }}"
                .replace('__ID__', id);

            $('#searchDetailDistributionMedis, #searchDetailMappingMedis, #searchDetailRawatMedis').val('');
            $('#filterDetailDistributionBonusMedis, #filterDetailRawatSourceMedis, #filterDetailRawatPelaksanaMedis')
                .val('all');
            $('#detailTindakanMedisContent').addClass('d-none');
            $('#detailTindakanMedisLoading').removeClass('d-none');
            detailModal.show();

            $.get(url, function(response) {
                renderDetail(response.data || {});
            }).fail(function(xhr) {
                $('#detailTindakanMedisLoading').addClass('d-none');
                Swal.fire('Gagal', errorMessage(xhr), 'error');
            });
        });

        $('#tableGenerateTindakanMedis').on('click', '.btn-lock-tindakan-medis', function() {
            toggleLock($(this).data('id'), true);
        });

        $('#tableGenerateTindakanMedis').on('click', '.btn-unlock-tindakan-medis', function() {
            toggleLock($(this).data('id'), false);
        });

        $('#detailMappingRowsMedis').on('click', '.detail-mapping-row', function() {
            selectedDetailMappingId = String($(this).data('id'));
            $('#filterDetailMappingMedis').val(selectedDetailMappingId);
            renderDetailMappingRows();
            renderDetailRawat();
        });

        $('#filterDetailMappingMedis').on('change', function() {
            selectedDetailMappingId = String($(this).val() || '');
            renderDetailMappingRows();
            renderDetailRawat();
        });

        $('#searchDetailDistributionMedis, #filterDetailDistributionBonusMedis').on('input change', function() {
            renderDetailDistributionSection();
        });

        $('#searchDetailMappingMedis').on('input', renderDetailMappingRows);

        $('#filterDetailRawatSourceMedis, #filterDetailRawatPelaksanaMedis').on('change', renderDetailRawat);

        $('#detailSourceRowsMedis').on('click', '.detail-source-row', function() {
            $('#filterDetailRawatSourceMedis').val(String($(this).data('source') || 'all'));
            renderDetailRawat();
        });

        $('#searchDetailRawatMedis').on('input', renderDetailRawat);

        initDoctorSelect();
        setDefaultPeriod();
        loadConfig().then(function() {
            loadSummary();
            reloadTable();
        });
    });
</script>
