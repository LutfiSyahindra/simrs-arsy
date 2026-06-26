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
        let tableGizi = null;
        let currentDetailGizi = null;
        let detailFilter = {
            kelompok: 'all',
            source: 'all',
            q: ''
        };

        const generateModal = modalInstance('modalGenerateGizi');
        const configModal = modalInstance('modalConfigGizi');
        const detailModal = modalInstance('modalDetailGizi');

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

        function percentText(value) {
            const number = Number(value) || 0;
            return number.toLocaleString('id-ID', {
                maximumFractionDigits: 4
            }) + '%';
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
            const params = new URLSearchParams(window.location.search);
            const periode = params.get('periode');

            if (/^\d{4}-\d{2}$/.test(periode || '')) {
                $('#periodeGizi').val(periode);
                return;
            }

            const now = new Date();
            $('#periodeGizi').val(now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
        }

        function periodFromInput() {
            const parts = String($('#periodeGizi').val() || '').split('-').map(Number);
            return parts.length === 2 && parts[0] && parts[1] ?
                new Date(parts[0], parts[1] - 1, 1) :
                new Date();
        }

        function setPeriodFromDate(date) {
            $('#periodeGizi')
                .val(date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0'))
                .trigger('change');
        }

        function movePeriod(offset) {
            const date = periodFromInput();
            date.setMonth(date.getMonth() + offset);
            setPeriodFromDate(date);
        }

        function sourcePeriodFor(periode, mode) {
            if (mode !== 'previous' || !periode) {
                return periode || '-';
            }

            const parts = String(periode).split('-').map(Number);
            if (parts.length !== 2 || !parts[0] || !parts[1]) {
                return periode;
            }

            const date = new Date(parts[0], parts[1] - 2, 1);
            return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
        }

        function stateBanner(selector, state, title, text, icon) {
            $(selector)
                .removeClass('success warning danger neutral')
                .addClass(state || 'neutral')
                .html(
                    '<i class="mdi ' + escapeHtml(icon || 'mdi-information-outline') + '"></i>' +
                    '<div>' +
                    '<div class="gizi-state-title">' + escapeHtml(title || '-') + '</div>' +
                    '<div class="gizi-state-text">' + escapeHtml(text || '-') + '</div>' +
                    '</div>'
                );
        }

        function emptyStateHtml(title, text, icon) {
            return '<div class="gizi-empty-state">' +
                '<i class="mdi ' + escapeHtml(icon || 'mdi-database-search-outline') + '"></i>' +
                '<div>' +
                '<div class="fw-bold text-dark">' + escapeHtml(title || 'Belum ada data') + '</div>' +
                '<div class="small">' + escapeHtml(text || 'Data akan tampil setelah proses selesai.') + '</div>' +
                '</div>' +
                '</div>';
        }

        function liveItem(label, value, icon) {
            return '<div class="gizi-live-item">' +
                '<div class="gizi-live-label">' + escapeHtml(label) + '</div>' +
                '<div class="gizi-live-value">' +
                '<i class="mdi ' + escapeHtml(icon || 'mdi-information-outline') + '"></i>' +
                '<span>' + escapeHtml(value || '-') + '</span>' +
                '</div>' +
                '</div>';
        }

        function summaryCard(label, value, note, color) {
            return '<div class="gizi-summary-item" style="--item-color:' + escapeHtml(color || '#15803d') + '">' +
                '<div class="gizi-summary-label">' + escapeHtml(label) + '</div>' +
                '<div class="gizi-summary-value">' + escapeHtml(value || '-') + '</div>' +
                (note ? '<div class="gizi-summary-note">' + escapeHtml(note) + '</div>' : '') +
                '</div>';
        }

        function flowCard(label, value, total, note, color) {
            const percent = total > 0 ? Math.min(100, (Number(value) || 0) / total * 100) : 0;
            return '<div class="gizi-flow-card">' +
                '<div class="gizi-flow-top">' +
                '<div class="gizi-flow-title">' + escapeHtml(label) + '</div>' +
                '<div class="gizi-flow-money">' + formatRupiah(value) + '</div>' +
                '</div>' +
                '<div class="gizi-bar-track">' +
                '<div class="gizi-bar-fill" style="--bar-color:' + escapeHtml(color || '#15803d') +
                ';width:' + percent + '%"></div>' +
                '</div>' +
                '<div class="gizi-info-note">' + escapeHtml((note || '-') + ' | ' + percentText(percent) +
                    ' dari grand total') + '</div>' +
                '</div>';
        }

        function detailKpi(label, value, note, color) {
            return '<div class="gizi-summary-item" style="--item-color:' + escapeHtml(color || '#15803d') + '">' +
                '<div class="gizi-detail-label">' + escapeHtml(label) + '</div>' +
                '<div class="gizi-detail-value">' + escapeHtml(value || '-') + '</div>' +
                (note ? '<div class="gizi-info-note">' + escapeHtml(note) + '</div>' : '') +
                '</div>';
        }

        function metaItem(label, value, icon) {
            return '<div class="gizi-detail-meta-item">' +
                '<div class="gizi-detail-label">' +
                '<i class="mdi ' + escapeHtml(icon || 'mdi-information-outline') + ' me-1"></i>' +
                escapeHtml(label || '-') +
                '</div>' +
                '<div class="gizi-detail-meta-value">' + escapeHtml(value || '-') + '</div>' +
                '</div>';
        }

        function infoRow(label, value, note) {
            return '<div class="gizi-info-row">' +
                '<div>' +
                '<strong>' + escapeHtml(label || '-') + '</strong>' +
                (note ? '<div class="gizi-info-note mt-0">' + escapeHtml(note) + '</div>' : '') +
                '</div>' +
                '<span>' + escapeHtml(value || '-') + '</span>' +
                '</div>';
        }

        function badge(text, type, icon) {
            return '<span class="gizi-badge ' + escapeHtml(type || 'info') + '">' +
                '<i class="mdi ' + escapeHtml(icon || 'mdi-information-outline') + '"></i>' +
                escapeHtml(text || '-') +
                '</span>';
        }

        function renderWarnings(selector, warnings) {
            warnings = warnings || {};
            const blocking = warnings.blocking || [];
            const info = warnings.info || [];
            let html = '';

            blocking.forEach(function(message) {
                html += '<div class="gizi-state-banner danger">' +
                    '<i class="mdi mdi-alert-circle-outline"></i>' +
                    '<div><div class="gizi-state-title">Perlu dilengkapi</div>' +
                    '<div class="gizi-state-text">' + escapeHtml(message) + '</div></div></div>';
            });

            info.forEach(function(message) {
                html += '<div class="gizi-state-banner warning">' +
                    '<i class="mdi mdi-information-outline"></i>' +
                    '<div><div class="gizi-state-title">Catatan data</div>' +
                    '<div class="gizi-state-text">' + escapeHtml(message) + '</div></div></div>';
            });

            $(selector).html(html);
        }

        function loadSummary() {
            const periode = $('#periodeGizi').val();
            if (!periode) {
                return;
            }

            $('#giziLiveStrip').html(
                liveItem('Status', 'Memuat...', 'mdi-progress-clock') +
                liveItem('Mode', typeConfig[activeType], 'mdi-account-cash-outline') +
                liveItem('Sumber', '-', 'mdi-calendar-sync-outline') +
                liveItem('Formula', '-', 'mdi-calculator-variant-outline')
            );
            $('#summaryGiziGrid').html('');
            $('#summaryGiziDistribution').html('');

            $.when(
                $.get('{{ route("backOffice.keuangan.hitungPremi.generateGizi.summary") }}', {
                    periode: periode,
                    jenis_gizi: activeType
                }),
                $.get('{{ route("backOffice.keuangan.hitungPremi.generateGizi.config") }}', {
                    jenis_gizi: activeType
                })
            ).done(function(summaryResponse, configResponse) {
                const data = summaryResponse[0].data || {};
                const config = configResponse[0].data || {};
                renderSummary(data, config);
            }).fail(function(xhr) {
                $('#heroGiziStatus').text('Gagal');
                $('#summaryGiziGrid').html(emptyStateHtml('Ringkasan gagal dimuat', errorMessage(xhr),
                    'mdi-alert-circle-outline'));
            });
        }

        function renderSummary(data, config) {
            const periode = $('#periodeGizi').val();
            const sourcePeriode = sourcePeriodFor(periode, config.source_period_mode);
            const generated = Number(data.generated_count || 0);
            const locked = Number(data.locked_count || 0);
            const statusText = generated < 1 ? 'Belum generate' : (locked >= generated ? 'Terkunci' : 'Terbuka');
            const statusIcon = generated < 1 ? 'mdi-play-circle-outline' : (locked >= generated ?
                'mdi-lock-check-outline' : 'mdi-lock-open-alert-outline');

            $('#heroGiziType').text(typeConfig[activeType]);
            $('#heroGiziSource').text(sourcePeriode || '-');
            $('#heroGiziStatus').text(statusText);
            $('#giziSourceRule').text(
                'Generate ' + periode + ' memakai sumber ' + (config.source_period_mode_label || 'Periode Generate') +
                ' (' + sourcePeriode + '), dengan filter penjamin ' +
                (activeType === 'bpjs' ? 'kd_pj = BPJ.' : 'kd_pj selain BPJ dan -.')
            );

            $('#giziLiveStrip').html(
                liveItem('Status', statusText, statusIcon) +
                liveItem('Mode', typeConfig[activeType], activeType === 'bpjs' ? 'mdi-shield-check-outline' :
                    'mdi-account-cash-outline') +
                liveItem('Mapping', (config.mapping_label || 'Belum dipilih'), 'mdi-shape-outline') +
                liveItem('Formula Diit', percentText(config.diit_petugas_percent) + ' / ' +
                    formatNumber(config.diit_petugas_divider), 'mdi-calculator-variant-outline')
            );

            $('#summaryGiziGrid').html(
                summaryCard('Hasil Generate', formatNumber(generated), formatNumber(locked) + ' terkunci',
                    '#15803d') +
                summaryCard('Grand Total', formatRupiah(data.grand_total), formatNumber(data.jumlah_tindakan) +
                    ' tindakan cocok mapping', '#2563eb') +
                summaryCard('Total Konsul', formatRupiah(data.grand_total_konsul), formatNumber(data
                    .jumlah_tindakan_konsul) + ' tindakan Konsul', '#0f766e') +
                summaryCard('Total Diit', formatRupiah(data.grand_total_diit), formatNumber(data
                    .jumlah_tindakan_diit) + ' tindakan Diit', '#d97706') +
                summaryCard('Bersama Konsul', formatRupiah(data.total_konsul_premi_bersama),
                    percentText(config.konsul_premi_bersama_percent) + ' dari total Konsul', '#e11d48') +
                summaryCard('Bersama Diit', formatRupiah(data.total_diit_premi_bersama),
                    config.diit_premi_bersama_enabled ? percentText(config.diit_premi_bersama_percent) +
                    ' dari total Diit' : 'Nonaktif', '#2563eb') +
                summaryCard('Total Bersama', formatRupiah(data.total_premi_bersama),
                    'Bersama Konsul + Bersama Diit', '#be123c') +
                summaryCard('Ke Pegawai', formatRupiah(data.total_dibagikan), 'Konsul + petugas Diit terpilih',
                    '#16a34a') +
                summaryCard('Pasien', formatNumber(data.jumlah_pasien), formatNumber(data.jumlah_pasien_sumber) +
                    ' pasien sumber', '#7c3aed') +
                summaryCard('Data Sumber', formatNumber(data.jumlah_data_sumber), 'Sebelum filter mapping',
                    '#475569')
            );

            const total = Number(data.grand_total || 0);
            $('#summaryGiziDistribution').html(
                flowCard('Konsul ke Pegawai', data.total_konsul_pegawai, total,
                    percentText(config.konsul_pegawai_percent) + ' dari total Konsul', '#16a34a') +
                flowCard('Konsul ke Premi Bersama', data.total_konsul_premi_bersama, total,
                    percentText(config.konsul_premi_bersama_percent) + ' dari total Konsul', '#e11d48') +
                flowCard('Diit ke Petugas', data.total_diit_petugas_pool, total,
                    percentText(config.diit_petugas_percent) + ' / ' + formatNumber(config.diit_petugas_divider),
                    '#d97706') +
                flowCard('Diit ke Premi Bersama', data.total_diit_premi_bersama, total,
                    config.diit_premi_bersama_enabled ? percentText(config.diit_premi_bersama_percent) +
                    ' dari total Diit' : 'Nonaktif', '#2563eb')
            );
        }

        function initDataTable() {
            tableGizi = $('#tableGenerateGizi').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                scrollX: true,
                order: [
                    [1, 'desc']
                ],
                ajax: {
                    url: '{{ route("backOffice.keuangan.hitungPremi.generateGizi.table") }}',
                    data: function(d) {
                        d.periode = $('#periodeGizi').val();
                        d.jenis_gizi = activeType;
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'periode',
                        name: 'periode'
                    },
                    {
                        data: 'jenis_gizi_label',
                        name: 'jenis_gizi',
                        render: function(data, type, row) {
                            return badge(data, row.jenis_gizi === 'bpjs' ? 'info' : 'success',
                                row.jenis_gizi === 'bpjs' ? 'mdi-shield-check-outline' :
                                'mdi-account-cash-outline');
                        }
                    },
                    {
                        data: 'source_periode',
                        name: 'source_periode',
                        render: function(data, type, row) {
                            return '<div class="fw-semibold">' + escapeHtml(data || '-') + '</div>' +
                                '<div class="text-muted small">' + escapeHtml(row.source_period_mode_label || '-') +
                                '</div>';
                        }
                    },
                    {
                        data: 'mapping_label',
                        name: 'nama_jenis_tindakan',
                        render: function(data) {
                            return '<span class="fw-semibold">' + escapeHtml(data || '-') + '</span>';
                        }
                    },
                    {
                        data: 'jumlah_pasien',
                        className: 'text-end',
                        render: formatNumber
                    },
                    {
                        data: 'jumlah_tindakan',
                        className: 'text-end',
                        render: function(data, type, row) {
                            return '<div class="fw-semibold">' + formatNumber(data) + '</div>' +
                                '<div class="text-muted small">K ' + formatNumber(row.jumlah_tindakan_konsul) +
                                ' / D ' + formatNumber(row.jumlah_tindakan_diit) + '</div>';
                        }
                    },
                    {
                        data: 'grand_total',
                        className: 'text-end',
                        render: formatRupiah
                    },
                    {
                        data: 'total_konsul_pegawai',
                        className: 'text-end',
                        render: function(data, type, row) {
                            return '<div class="fw-semibold">' + formatRupiah(data) + '</div>' +
                                '<div class="text-muted small">Total ' + formatRupiah(row.grand_total_konsul) +
                                '</div>';
                        }
                    },
                    {
                        data: 'total_diit_petugas_pool',
                        className: 'text-end',
                        render: function(data, type, row) {
                            return '<div class="fw-semibold">' + formatRupiah(row.diit_petugas_per_orang) +
                                '</div>' +
                                '<div class="text-muted small">Pool ' + formatRupiah(data) + '</div>';
                        }
                    },
                    {
                        data: 'total_premi_bersama',
                        className: 'text-end',
                        render: function(data, type, row) {
                            return '<div class="fw-semibold">' + formatRupiah(row
                                .total_konsul_premi_bersama) + '</div>' +
                                '<div class="text-muted small">Konsul</div>' +
                                '<div class="fw-semibold mt-1">' + formatRupiah(row
                                    .total_diit_premi_bersama) + '</div>' +
                                '<div class="text-muted small">Diit</div>' +
                                '<div class="text-muted small border-top mt-1 pt-1">Total ' +
                                formatRupiah(data) + '</div>';
                        }
                    },
                    {
                        data: 'is_locked',
                        render: function(data) {
                            return data ? badge('Terkunci', 'success', 'mdi-lock-check-outline') :
                                badge('Terbuka', 'warning', 'mdi-lock-open-alert-outline');
                        }
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false,
                        className: 'text-end'
                    }
                ]
            });
        }

        function reloadTable() {
            if (tableGizi) {
                tableGizi.ajax.reload(null, false);
            }
        }

        function initRemoteSelect(selector, url, placeholder) {
            $(selector).select2({
                width: '100%',
                dropdownParent: $('#modalConfigGizi'),
                placeholder: placeholder,
                allowClear: true,
                ajax: {
                    url: url,
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

        function setSelectItems(selector, items, idKey, textKey) {
            const select = $(selector);
            select.empty();
            (items || []).forEach(function(item) {
                const id = item[idKey || 'id'];
                const text = item[textKey || 'text'] || item.label || item.pegawai_name || id;
                select.append(new Option(text, id, true, true));
            });
            select.trigger('change');
        }

        function loadConfig() {
            $('#formConfigGizi')[0].reset();
            $('#jenisConfigGizi').val(activeType);
            $('#configGiziTitle').text('Konfigurasi Gizi ' + typeConfig[activeType]);
            $('#configGiziTypeBadge').text(typeConfig[activeType]);
            stateBanner('#configGiziHealthBanner', 'neutral', 'Memuat konfigurasi',
                'Mengambil mapping, formula, dan penerima aktif.', 'mdi-progress-clock');

            $.get('{{ route("backOffice.keuangan.hitungPremi.generateGizi.config") }}', {
                jenis_gizi: activeType
            }).done(function(response) {
                const data = response.data || {};
                $('#configGiziSourcePeriodMode').val(data.source_period_mode || 'current');
                $('#configKonsulPegawaiPercent').val(data.konsul_pegawai_percent ?? 50);
                $('#configKonsulBersamaPercent').val(data.konsul_premi_bersama_percent ?? 30);
                $('#configDiitPetugasPercent').val(data.diit_petugas_percent ?? 3);
                $('#configDiitPetugasDivider').val(data.diit_petugas_divider ?? 5);
                $('#configDiitBersamaPercent').val(data.diit_premi_bersama_percent ?? 37);
                $('#configDiitBersamaEnabled').prop('checked', Boolean(data.diit_premi_bersama_enabled));

                setSelectItems('#configGiziKonsulMapping', data.konsul_mapping_items || []);
                setSelectItems('#configGiziDiitMapping', data.diit_mapping_items || []);
                setSelectItems('#configKonsulRecipients', data.recipients?.konsul_pegawai || [],
                    'pegawai_id');
                setSelectItems('#configDiitRecipients', data.recipients?.diit_petugas || [], 'pegawai_id');
                renderConfigFormulaPreview();
            }).fail(function(xhr) {
                stateBanner('#configGiziHealthBanner', 'danger', 'Konfigurasi gagal dimuat', errorMessage(xhr),
                    'mdi-alert-circle-outline');
            });
        }

        function renderConfigFormulaPreview() {
            const konsulMappingCount = ($('#configGiziKonsulMapping').val() || []).length;
            const diitMappingCount = ($('#configGiziDiitMapping').val() || []).length;
            const konsulRecipients = ($('#configKonsulRecipients').val() || []).length;
            const diitRecipients = ($('#configDiitRecipients').val() || []).length;
            const diitDivider = Math.max(1, Number($('#configDiitPetugasDivider').val()) || 1);
            const diitEnabled = $('#configDiitBersamaEnabled').is(':checked');

            $('#countKonsulRecipients').text(formatNumber(konsulRecipients) + ' pegawai dipilih.');
            $('#countDiitRecipients').text(formatNumber(diitRecipients) + ' petugas dipilih dari pembagi ' +
                formatNumber(diitDivider) + '.');

            $('#configGiziFormulaPreview').html(
                infoRow('Mapping Konsul', formatNumber(konsulMappingCount) + ' pilihan',
                    'Data masuk kelompok Konsul.') +
                infoRow('Mapping Diit', formatNumber(diitMappingCount) + ' pilihan',
                    'Data masuk kelompok Diit.') +
                infoRow('Konsul ke Pegawai', percentText($('#configKonsulPegawaiPercent').val()),
                    'Dibagi rata ke pegawai Konsul terpilih.') +
                infoRow('Konsul ke Premi Bersama', percentText($('#configKonsulBersamaPercent').val()),
                    'Dihitung dari grand total Konsul.') +
                infoRow('Diit ke Petugas', percentText($('#configDiitPetugasPercent').val()) + ' / ' +
                    formatNumber(diitDivider), 'Setiap petugas menerima hasil persen dibagi pembagi.') +
                infoRow('Diit ke Premi Bersama', diitEnabled ? percentText($('#configDiitBersamaPercent').val()) :
                    'Nonaktif', diitEnabled ? 'Dihitung dari grand total Diit.' :
                    'Untuk BPJS default nonaktif karena bersama ikut Konsul.')
            );

            const hasMapping = konsulMappingCount + diitMappingCount > 0;
            const tooManyDiit = diitRecipients > diitDivider;
            stateBanner(
                '#configGiziHealthBanner',
                hasMapping && !tooManyDiit ? 'success' : 'warning',
                hasMapping && !tooManyDiit ? 'Konfigurasi terlihat siap' : 'Konfigurasi perlu dicek',
                !hasMapping ? 'Pilih minimal satu mapping Konsul atau Diit.' :
                (tooManyDiit ? 'Jumlah petugas Diit melebihi pembagi formula.' :
                    'Mapping, formula, dan penerima akan dipakai untuk preview berikutnya.'),
                hasMapping && !tooManyDiit ? 'mdi-check-circle-outline' : 'mdi-alert-outline'
            );
        }

        function openConfigModal() {
            configModal.show();
            loadConfig();
        }

        function saveConfig(event) {
            event.preventDefault();
            const button = $('#btnSubmitConfigGizi');
            button.prop('disabled', true);

            $.ajax({
                url: '{{ route("backOffice.keuangan.hitungPremi.generateGizi.updateConfig") }}',
                method: 'PUT',
                data: {
                    jenis_gizi: activeType,
                    source_period_mode: $('#configGiziSourcePeriodMode').val(),
                    konsul_jnsTindakan_ids: $('#configGiziKonsulMapping').val() || [],
                    diit_jnsTindakan_ids: $('#configGiziDiitMapping').val() || [],
                    konsul_pegawai_percent: $('#configKonsulPegawaiPercent').val(),
                    konsul_premi_bersama_percent: $('#configKonsulBersamaPercent').val(),
                    diit_petugas_percent: $('#configDiitPetugasPercent').val(),
                    diit_petugas_divider: $('#configDiitPetugasDivider').val(),
                    diit_premi_bersama_percent: $('#configDiitBersamaPercent').val(),
                    diit_premi_bersama_enabled: $('#configDiitBersamaEnabled').is(':checked') ? 1 : 0,
                    recipients: {
                        konsul_pegawai: $('#configKonsulRecipients').val() || [],
                        diit_petugas: $('#configDiitRecipients').val() || []
                    }
                }
            }).done(function(response) {
                configModal.hide();
                Swal.fire('Berhasil', response.message || 'Konfigurasi Gizi disimpan.', 'success');
                loadSummary();
            }).fail(function(xhr) {
                Swal.fire('Gagal', errorMessage(xhr), 'error');
            }).always(function() {
                button.prop('disabled', false);
            });
        }

        function openPreviewModal() {
            $('#previewGiziLoading').removeClass('d-none');
            $('#previewGiziContent').addClass('d-none');
            $('#btnSubmitGenerateGizi').prop('disabled', true);
            generateModal.show();

            $.get('{{ route("backOffice.keuangan.hitungPremi.generateGizi.preview") }}', {
                periode: $('#periodeGizi').val(),
                jenis_gizi: activeType
            }).done(function(response) {
                renderPreview(response.data || {});
            }).fail(function(xhr) {
                $('#previewGiziLoading').addClass('d-none');
                $('#previewGiziContent').removeClass('d-none');
                stateBanner('#previewGiziStateBanner', 'danger', 'Preview gagal', errorMessage(xhr),
                    'mdi-alert-circle-outline');
                $('#previewGiziStats, #previewGiziFormula, #previewGiziRecipients, #previewGiziDetails').html('');
            });
        }

        function renderPreview(data) {
            const source = data.source || {};
            const pools = data.pools || {};
            const config = data.config || {};
            const warnings = data.warnings || {};
            const details = source.details || [];
            const canGenerate = data.can_generate !== false;

            $('#previewGiziLoading').addClass('d-none');
            $('#previewGiziContent').removeClass('d-none');
            $('#generateGiziTitle').text('Preview Gizi ' + (data.jenis_gizi_label || typeConfig[activeType]));
            $('#generateGiziText').text('Periode generate ' + (data.periode || '-') + ' memakai sumber ' +
                (source.source_periode || '-') + '.');
            $('#generateGiziTypeBadge').text(data.jenis_gizi_label || typeConfig[activeType]);
            $('#btnSubmitGenerateGizi').prop('disabled', !canGenerate);

            stateBanner(
                '#previewGiziStateBanner',
                canGenerate ? 'success' : 'danger',
                canGenerate ? 'Preview siap disimpan' : 'Preview belum bisa disimpan',
                canGenerate ? 'Data, mapping, formula, dan penerima sudah lolos validasi.' :
                'Lengkapi catatan yang muncul sebelum generate.',
                canGenerate ? 'mdi-check-circle-outline' : 'mdi-alert-circle-outline'
            );

            $('#previewGiziStats').html(
                detailKpi('Data Sumber', formatNumber(source.jumlah_data_sumber), formatNumber(source
                    .jumlah_pasien_sumber) + ' pasien sumber', '#475569') +
                detailKpi('Tindakan Cocok', formatNumber(source.jumlah_tindakan), 'Konsul ' + formatNumber(source
                    .jumlah_tindakan_konsul) + ' / Diit ' + formatNumber(source.jumlah_tindakan_diit), '#2563eb') +
                detailKpi('Grand Total', formatRupiah(source.grand_total), 'Total semua biaya_rawat', '#15803d') +
                detailKpi('Bersama Konsul', formatRupiah(pools.konsul_premi_bersama),
                    percentText(config.konsul_premi_bersama_percent) + ' dari total Konsul', '#e11d48') +
                detailKpi('Bersama Diit', formatRupiah(pools.diit_premi_bersama),
                    config.diit_premi_bersama_enabled ? percentText(config.diit_premi_bersama_percent) +
                    ' dari total Diit' : 'Nonaktif', '#2563eb') +
                detailKpi('Total Bersama', formatRupiah(pools.premi_bersama),
                    'Bersama Konsul + Bersama Diit', '#be123c')
            );

            $('#previewGiziFormula').html(
                infoRow('Konsul Pegawai', formatRupiah(pools.konsul_pegawai),
                    percentText(config.konsul_pegawai_percent) + ' dari ' + formatRupiah(source
                        .grand_total_konsul)) +
                infoRow('Konsul Premi Bersama', formatRupiah(pools.konsul_premi_bersama),
                    percentText(config.konsul_premi_bersama_percent) + ' dari total Konsul') +
                infoRow('Diit Petugas', formatRupiah(pools.diit_petugas_per_orang) + ' / orang',
                    'Pool ' + formatRupiah(pools.diit_petugas_pool) + ' dibagi ' + formatNumber(pools
                        .diit_petugas_divider)) +
                infoRow('Diit Premi Bersama', formatRupiah(pools.diit_premi_bersama),
                    config.diit_premi_bersama_enabled ? percentText(config.diit_premi_bersama_percent) +
                    ' dari total Diit' : 'Nonaktif')
            );

            renderRecipientGroups('#previewGiziRecipients', data.recipient_groups || []);
            renderWarnings('#previewGiziWarnings', warnings);
            $('#previewGiziDetailSubtitle').text(formatNumber(details.length) +
                ' detail akan disimpan. Menampilkan maksimal 30 baris pertama.');
            $('#previewGiziDetails').html(renderDetailsTable(details.slice(0, 30)));
        }

        function renderRecipientGroups(selector, groups) {
            if (!groups || groups.length < 1) {
                $(selector).html(emptyStateHtml('Belum ada penerima', 'Pilih penerima pada konfigurasi Gizi.',
                    'mdi-account-search-outline'));
                return;
            }

            $(selector).html(groups.map(function(group) {
                const items = group.items || [];
                const employeeRows = items.length < 1 ? emptyStateHtml('Belum ada pegawai',
                    'Pilih pegawai pada konfigurasi Gizi.', 'mdi-account-search-outline') :
                    '<div class="gizi-recipient-list">' + items.map(function(item) {
                        const noteParts = [
                            item.pegawai_id || '-',
                            item.pegawai_position || '-',
                            item.allocation_percent != null ? 'Porsi ' + percentText(item.allocation_percent) :
                            null
                        ].filter(Boolean);

                        return '<div class="gizi-recipient-person">' +
                            '<div class="gizi-recipient-avatar"><i class="mdi mdi-account-outline"></i></div>' +
                            '<div>' +
                            '<div class="gizi-recipient-name">' + escapeHtml(item.pegawai_name || '-') +
                            '</div>' +
                            '<div class="gizi-recipient-note">' + escapeHtml(noteParts.join(' | ')) + '</div>' +
                            '</div>' +
                            '<div class="gizi-recipient-money">' + formatRupiah(item.total_received) + '</div>' +
                            '</div>';
                    }).join('') + '</div>';

                return '<div class="gizi-recipient-card">' +
                    '<div class="gizi-recipient-head">' +
                    '<div>' +
                    '<div class="gizi-recipient-title">' + escapeHtml(group.role_label || '-') + '</div>' +
                    '<div class="gizi-recipient-note">' + formatNumber(group.recipient_count) +
                    ' penerima | Pool ' + formatRupiah(group.pool_total) + '</div>' +
                    '</div>' +
                    '<div class="gizi-recipient-total">' + formatRupiah(group.total_received) + '</div>' +
                    '</div>' +
                    employeeRows +
                    '</div>';
            }).join(''));
        }

        function renderDetailsTable(details) {
            if (!details || details.length < 1) {
                return emptyStateHtml('Belum ada detail tindakan', 'Data akan muncul setelah mapping cocok.',
                    'mdi-database-search-outline');
            }

            return '<table class="table table-hover align-middle">' +
                '<thead><tr>' +
                '<th>Kelompok</th><th>Waktu</th><th>Pasien</th><th>Tindakan</th><th>Pelaksana</th><th class="text-end">Biaya</th>' +
                '</tr></thead><tbody>' +
                details.map(function(item) {
                    const pelaksana = item.nm_dokter || item.nama_petugas || '-';
                    return '<tr>' +
                        '<td>' + badge(item.kelompok_label || item.kelompok, item.kelompok === 'diit' ?
                            'warning' : 'success', item.kelompok === 'diit' ? 'mdi-silverware-fork-knife' :
                            'mdi-account-voice') + '</td>' +
                        '<td><div class="fw-semibold">' + escapeHtml(item.tanggal || '-') + '</div>' +
                        '<div class="text-muted small">' + escapeHtml(item.jam || '-') + ' | ' + escapeHtml(item
                            .source_label || item.source_table || '-') + '</div></td>' +
                        '<td><div class="fw-semibold">' + escapeHtml(item.nm_pasien || '-') + '</div>' +
                        '<div class="text-muted small">' + escapeHtml(item.no_rawat || '-') + '</div></td>' +
                        '<td><div class="fw-semibold">' + escapeHtml(item.nm_tindakan || '-') + '</div>' +
                        '<div class="text-muted small">' + escapeHtml(item.kd_tindakan || '-') + '</div></td>' +
                        '<td>' + escapeHtml(pelaksana) + '</td>' +
                        '<td class="text-end fw-semibold">' + formatRupiah(item.biaya_rawat) + '</td>' +
                        '</tr>';
                }).join('') +
                '</tbody></table>';
        }

        function submitGenerate(event) {
            event.preventDefault();
            const button = $('#btnSubmitGenerateGizi');
            button.prop('disabled', true);

            $.post('{{ route("backOffice.keuangan.hitungPremi.generateGizi.store") }}', {
                periode: $('#periodeGizi').val(),
                jenis_gizi: activeType
            }).done(function(response) {
                generateModal.hide();
                Swal.fire('Berhasil', response.message || 'Premi Gizi berhasil digenerate.', 'success');
                reloadTable();
                loadSummary();
            }).fail(function(xhr) {
                Swal.fire('Gagal', errorMessage(xhr), 'error');
            }).always(function() {
                button.prop('disabled', false);
            });
        }

        function openDetail(id) {
            $('#detailGiziLoading').removeClass('d-none');
            $('#detailGiziContent').addClass('d-none');
            detailModal.show();

            const url = '{{ route("backOffice.keuangan.hitungPremi.generateGizi.detail", ["id" => "__ID__"]) }}'
                .replace('__ID__', id);

            $.get(url).done(function(response) {
                renderDetail(response.data || {});
            }).fail(function(xhr) {
                $('#detailGiziLoading').addClass('d-none');
                $('#detailGiziContent').removeClass('d-none');
                $('#detailGiziRows').html(emptyStateHtml('Detail gagal dimuat', errorMessage(xhr),
                    'mdi-alert-circle-outline'));
            });
        }

        function renderDetail(data) {
            const source = data.source || {};
            const pools = data.pools || {};
            currentDetailGizi = data;
            detailFilter = {
                kelompok: 'all',
                source: 'all',
                q: ''
            };

            $('#detailGiziLoading').addClass('d-none');
            $('#detailGiziContent').removeClass('d-none');
            $('#detailGiziTitle').text('Gizi ' + data.jenis_gizi_label + ' - ' + data.periode);
            $('#detailGiziHeading').text('Generate Gizi ' + data.jenis_gizi_label);
            $('#detailGiziSubheading').text('Sumber ' + (source.source_tgl_awal || '-') + ' s.d. ' + (source
                .source_tgl_akhir || '-') + ' | ' + (data.mapping_label || '-'));
            $('#detailGiziStatus').html(data.is_locked ? 'Terkunci' : 'Terbuka');
            setDetailTab('overview');

            $('#detailGiziMeta').html(
                metaItem('Periode', data.periode || '-', 'mdi-calendar-month-outline') +
                metaItem('Jenis', data.jenis_gizi_label || '-', data.jenis_gizi === 'bpjs' ?
                    'mdi-shield-check-outline' : 'mdi-account-cash-outline') +
                metaItem('Sumber Data', (source.source_period_mode_label || data.source_period_mode_label || '-') +
                    ' | ' + (source.source_periode || data.source_periode || '-'), 'mdi-calendar-sync-outline') +
                metaItem('Generate Oleh', (data.generate_by_name || '-') + ' | ' + (data.generated_at || '-'),
                    'mdi-account-clock-outline')
            );

            $('#detailGiziKpi').html(
                detailKpi('Grand Total', formatRupiah(data.grand_total), formatNumber(data.jumlah_tindakan) +
                    ' tindakan', '#15803d') +
                detailKpi('Total Konsul', formatRupiah(data.grand_total_konsul), formatNumber(data
                    .jumlah_tindakan_konsul) + ' tindakan', '#0f766e') +
                detailKpi('Total Diit', formatRupiah(data.grand_total_diit), formatNumber(data
                    .jumlah_tindakan_diit) + ' tindakan', '#d97706') +
                detailKpi('Bersama Konsul', formatRupiah(data.total_konsul_premi_bersama),
                    'Bagian bersama dari Konsul', '#e11d48') +
                detailKpi('Bersama Diit', formatRupiah(data.total_diit_premi_bersama),
                    'Bagian bersama dari Diit', '#2563eb') +
                detailKpi('Total Bersama', formatRupiah(data.total_premi_bersama),
                    'Bersama Konsul + Bersama Diit', '#be123c')
            );

            $('#detailGiziPools').html(
                infoRow('Konsul ke Pegawai', formatRupiah(pools.konsul_pegawai), 'Pool pegawai Konsul') +
                infoRow('Konsul ke Premi Bersama', formatRupiah(pools.konsul_premi_bersama),
                    'Bagian bersama Konsul') +
                infoRow('Diit ke Petugas', formatRupiah(pools.diit_petugas_per_orang) + ' / orang',
                    'Pool ' + formatRupiah(pools.diit_petugas_pool) + ' / ' + formatNumber(pools
                        .diit_petugas_divider)) +
                infoRow('Diit ke Premi Bersama', formatRupiah(pools.diit_premi_bersama), 'Bagian bersama Diit') +
                infoRow('Total Premi Bersama', formatRupiah(pools.premi_bersama),
                    'Konsul bersama + Diit bersama') +
                infoRow('Total Dibagikan', formatRupiah(pools.total_dibagikan), 'Total diterima pegawai terpilih')
            );

            $('#detailGiziTogether').html(
                infoRow('Premi Bersama Konsul', formatRupiah(data.total_konsul_premi_bersama),
                    'Dihitung dari grand total Konsul ' + formatRupiah(data.grand_total_konsul)) +
                infoRow('Premi Bersama Diit', formatRupiah(data.total_diit_premi_bersama),
                    'Dihitung dari grand total Diit ' + formatRupiah(data.grand_total_diit)) +
                infoRow('Total Premi Bersama', formatRupiah(data.total_premi_bersama),
                    'Konsul + Diit')
            );

            $('#detailGiziRecipientNote').text(
                formatNumber(data.recipients_count || (data.recipients || []).length) +
                ' pegawai/petugas menerima total ' + formatRupiah(pools.total_dibagikan || data.total_dibagikan)
            );
            renderRecipientGroups('#detailGiziRecipients', data.recipient_groups || []);
            $('#detailGiziGroups').html(renderMiniGroups(data.kelompok_groups || [], 'kelompok_label'));
            $('#detailGiziSources').html(renderMiniGroups(data.source_groups || [], 'source_label'));
            setupDetailActionFilters(data.details || []);
            renderFilteredDetailRows();
        }

        function renderMiniGroups(groups, labelKey) {
            if (!groups || groups.length < 1) {
                return emptyStateHtml('Belum ada data', 'Detail akan tampil setelah generate tersimpan.',
                    'mdi-database-search-outline');
            }

            return groups.map(function(group) {
                return infoRow(group[labelKey] || '-', formatRupiah(group.grand_total),
                    formatNumber(group.jumlah_tindakan) + ' tindakan');
            }).join('');
        }

        function setDetailTab(tab) {
            $('#modalDetailGizi .gizi-detail-tab').removeClass('active');
            $('#modalDetailGizi .gizi-detail-tab[data-detail-tab="' + tab + '"]').addClass('active');
            $('#modalDetailGizi .gizi-detail-panel').removeClass('active');
            $('#modalDetailGizi .gizi-detail-panel[data-detail-panel="' + tab + '"]').addClass('active');
        }

        function setupDetailActionFilters(details) {
            const sourceSelect = $('#detailGiziSourceFilter');
            const sources = {};

            (details || []).forEach(function(item) {
                if (!item.source_table) {
                    return;
                }

                sources[item.source_table] = item.source_label || item.source_table;
            });

            sourceSelect.html('<option value="all">Semua tabel sumber</option>' +
                Object.keys(sources).sort().map(function(source) {
                    return '<option value="' + escapeHtml(source) + '">' + escapeHtml(sources[source]) +
                        '</option>';
                }).join(''));
            sourceSelect.val('all');
            $('#detailGiziSearch').val('');
            $('#modalDetailGizi [data-detail-group]').removeClass('active');
            $('#modalDetailGizi [data-detail-group="all"]').addClass('active');
        }

        function detailRowMatches(item) {
            const q = String(detailFilter.q || '').toLowerCase();
            const haystack = [
                item.kelompok_label,
                item.kelompok,
                item.source_label,
                item.source_table,
                item.no_rawat,
                item.no_rkm_medis,
                item.nm_pasien,
                item.kd_pj,
                item.nama_penjamin,
                item.kd_tindakan,
                item.nm_tindakan,
                item.kd_dokter,
                item.nm_dokter,
                item.nip,
                item.nama_petugas
            ].join(' ').toLowerCase();

            if (detailFilter.kelompok !== 'all' && item.kelompok !== detailFilter.kelompok) {
                return false;
            }

            if (detailFilter.source !== 'all' && item.source_table !== detailFilter.source) {
                return false;
            }

            return q === '' || haystack.indexOf(q) !== -1;
        }

        function renderFilteredDetailRows() {
            const details = (currentDetailGizi && currentDetailGizi.details) ? currentDetailGizi.details : [];
            const filtered = details.filter(detailRowMatches);
            const total = filtered.reduce(function(sum, item) {
                return sum + Number(item.biaya_rawat || 0);
            }, 0);

            $('#detailGiziRowCount').text(formatNumber(filtered.length) + ' dari ' + formatNumber(details.length) +
                ' baris | ' + formatRupiah(total));
            $('#detailGiziRows').html(renderDetailsTable(filtered));
        }

        function toggleLock(id, lock) {
            const url = (lock ?
                    '{{ route("backOffice.keuangan.hitungPremi.generateGizi.lock", ["id" => "__ID__"]) }}' :
                    '{{ route("backOffice.keuangan.hitungPremi.generateGizi.unlock", ["id" => "__ID__"]) }}')
                .replace('__ID__', id);

            Swal.fire({
                title: lock ? 'Kunci data Gizi?' : 'Buka kunci data Gizi?',
                text: lock ? 'Data terkunci tidak dapat digenerate ulang atau dihapus.' :
                    'Data akan dapat diproses ulang setelah kunci dibuka.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: lock ? 'Kunci' : 'Buka Kunci',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.post(url).done(function(response) {
                    Swal.fire('Berhasil', response.message || 'Status kunci diperbarui.', 'success');
                    reloadTable();
                    loadSummary();
                }).fail(function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                });
            });
        }

        function deleteResult(id) {
            const url = '{{ route("backOffice.keuangan.hitungPremi.generateGizi.delete", ["id" => "__ID__"]) }}'
                .replace('__ID__', id);

            Swal.fire({
                title: 'Hapus data Gizi?',
                text: 'Header, detail sumber, dan penerima hasil generate akan dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Hapus',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: url,
                    method: 'DELETE'
                }).done(function(response) {
                    Swal.fire('Berhasil', response.message || 'Data Gizi dihapus.', 'success');
                    reloadTable();
                    loadSummary();
                }).fail(function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                });
            });
        }

        initRemoteSelect('#configGiziKonsulMapping',
            '{{ route("backOffice.keuangan.hitungPremi.generateGizi.mappingOptions") }}',
            'Cari mapping Konsul');
        initRemoteSelect('#configGiziDiitMapping',
            '{{ route("backOffice.keuangan.hitungPremi.generateGizi.mappingOptions") }}',
            'Cari mapping Diit');
        initRemoteSelect('#configKonsulRecipients',
            '{{ route("backOffice.keuangan.hitungPremi.generateGizi.pegawaiOptions") }}',
            'Cari pegawai Konsul');
        initRemoteSelect('#configDiitRecipients',
            '{{ route("backOffice.keuangan.hitungPremi.generateGizi.pegawaiOptions") }}',
            'Cari petugas Diit');

        setDefaultPeriod();
        initDataTable();
        loadSummary();

        $('#btnPrevGizi').on('click', function() {
            movePeriod(-1);
        });
        $('#btnNextGizi').on('click', function() {
            movePeriod(1);
        });
        $('#periodeGizi').on('change', function() {
            reloadTable();
            loadSummary();
        });
        $('.gizi-type-tab').on('click', function() {
            activeType = $(this).data('type');
            $('.gizi-type-tab').removeClass('active');
            $(this).addClass('active');
            reloadTable();
            loadSummary();
        });
        $('#btnRefreshGizi').on('click', function() {
            reloadTable();
            loadSummary();
        });
        $('#btnConfigGizi').on('click', openConfigModal);
        $('#btnPreviewGizi').on('click', openPreviewModal);
        $('#formConfigGizi').on('submit', saveConfig);
        $('#formGenerateGizi').on('submit', submitGenerate);
        $('#modalConfigGizi').on('change input',
            '#configGiziSourcePeriodMode, #configGiziKonsulMapping, #configGiziDiitMapping, #configKonsulPegawaiPercent, #configKonsulBersamaPercent, #configDiitPetugasPercent, #configDiitPetugasDivider, #configDiitBersamaPercent, #configDiitBersamaEnabled, #configKonsulRecipients, #configDiitRecipients',
            renderConfigFormulaPreview);
        $('#modalDetailGizi').on('click', '.gizi-detail-tab', function() {
            setDetailTab($(this).data('detail-tab'));
        });
        $('#modalDetailGizi').on('input', '#detailGiziSearch', function() {
            detailFilter.q = $(this).val() || '';
            renderFilteredDetailRows();
        });
        $('#modalDetailGizi').on('change', '#detailGiziSourceFilter', function() {
            detailFilter.source = $(this).val() || 'all';
            renderFilteredDetailRows();
        });
        $('#modalDetailGizi').on('click', '[data-detail-group]', function() {
            detailFilter.kelompok = $(this).data('detail-group') || 'all';
            $('#modalDetailGizi [data-detail-group]').removeClass('active');
            $(this).addClass('active');
            renderFilteredDetailRows();
        });
        $('#tableGenerateGizi').on('click', '.btn-detail-gizi', function() {
            openDetail($(this).data('id'));
        });
        $('#tableGenerateGizi').on('click', '.btn-lock-gizi', function() {
            toggleLock($(this).data('id'), true);
        });
        $('#tableGenerateGizi').on('click', '.btn-unlock-gizi', function() {
            toggleLock($(this).data('id'), false);
        });
        $('#tableGenerateGizi').on('click', '.btn-delete-gizi', function() {
            deleteResult($(this).data('id'));
        });
    });
</script>
