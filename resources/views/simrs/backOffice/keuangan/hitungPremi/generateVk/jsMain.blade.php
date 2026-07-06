<script>
    $(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const generateModal = (function() {
            const modalElement = document.getElementById('modalGenerateVk');

            if (window.bootstrap && bootstrap.Modal && modalElement) {
                return bootstrap.Modal.getOrCreateInstance ?
                    bootstrap.Modal.getOrCreateInstance(modalElement) :
                    new bootstrap.Modal(modalElement);
            }

            return {
                show: function() {
                    $('#modalGenerateVk').modal('show');
                },
                hide: function() {
                    $('#modalGenerateVk').modal('hide');
                }
            };
        })();
        const configModal = (function() {
            const modalElement = document.getElementById('modalConfigVk');

            if (window.bootstrap && bootstrap.Modal && modalElement) {
                return bootstrap.Modal.getOrCreateInstance ?
                    bootstrap.Modal.getOrCreateInstance(modalElement) :
                    new bootstrap.Modal(modalElement);
            }

            return {
                show: function() {
                    $('#modalConfigVk').modal('show');
                },
                hide: function() {
                    $('#modalConfigVk').modal('hide');
                }
            };
        })();
        const detailModal = (function() {
            const modalElement = document.getElementById('modalDetailVk');

            if (window.bootstrap && bootstrap.Modal && modalElement) {
                return bootstrap.Modal.getOrCreateInstance ?
                    bootstrap.Modal.getOrCreateInstance(modalElement) :
                    new bootstrap.Modal(modalElement);
            }

            return {
                show: function() {
                    $('#modalDetailVk').modal('show');
                },
                hide: function() {
                    $('#modalDetailVk').modal('hide');
                }
            };
        })();

        const typeConfig = {
            umum: 'Umum',
            bpjs: 'BPJS'
        };
        let activeType = 'umum';
        let isEditMode = false;
        let formMode = 'generate';
        let rowCounter = 0;
        let plotingOptionsLoaded = false;
        let plotingOptionsRequest = null;
        let plotingOptions = [];
        let vkConfig = {
            jenis_vk: 'bpjs',
            bpjs_percent: 4,
            bpjs_pembagi: 4,
            distribution_mode: 'rata',
            recipients: []
        };
        let configRequest = null;
        let configSelectReady = false;
        let currentDetailRow = null;

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
            const errors = response.errors;

            if (errors) {
                return flattenErrors(errors).join('<br>');
            }

            return response.message || 'Terjadi kesalahan saat memproses data.';
        }

        function setDefaultPeriod() {
            const now = new Date();
            $('#periodeVk').val(now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
        }

        function periodFromInput() {
            const value = $('#periodeVk').val();
            const parts = String(value || '').split('-').map(Number);

            if (parts.length === 2 && parts[0] && parts[1]) {
                return new Date(parts[0], parts[1] - 1, 1);
            }

            const now = new Date();
            return new Date(now.getFullYear(), now.getMonth(), 1);
        }

        function setPeriodFromDate(date) {
            const value = date.getFullYear() + '-' +
                String(date.getMonth() + 1).padStart(2, '0');

            $('#periodeVk').val(value).trigger('change');
        }

        function movePeriod(offset) {
            const date = periodFromInput();
            date.setMonth(date.getMonth() + offset);
            setPeriodFromDate(date);
        }

        function formatInput(el) {
            const value = numeric(el.value).replace(/^0+(?=\d)/, '');
            el.value = value ? formatNumber(value) : '';
        }

        function tindakanText(row) {
            return row.display_text || row.nm_tindakan || '';
        }

        function rowTotal(row) {
            const jenis = $('#jenisGenerateVk').val() || activeType;
            const jumlah = Number(numeric(row.find('.jumlah-tindakan-generate-vk').val())) || 0;
            const nominal = Number(numeric(row.find('.nominal-generate-vk').val())) || 0;
            const rawTotal = jumlah * nominal;

            return jenis === 'bpjs' ? bpjsCalculation(rawTotal).totalDistributed : rawTotal;
        }

        function rawRowTotal(row) {
            const jumlah = Number(numeric(row.find('.jumlah-tindakan-generate-vk').val())) || 0;
            const nominal = Number(numeric(row.find('.nominal-generate-vk').val())) || 0;

            return jumlah * nominal;
        }

        function bpjsCalculation(rawTotal) {
            const percent = Number($('#configVkBpjsPercent').val() || vkConfig.bpjs_percent || 4);
            const pembagi = Math.max(1, Number($('#configVkBpjsPembagi').val() || vkConfig.bpjs_pembagi || 4));
            const distributionMode = $('#configVkDistributionMode').val() || vkConfig.distribution_mode || 'rata';
            const recipients = (vkConfig.recipients || []).length;
            const pool = Math.round((Number(rawTotal) || 0) * percent / 100);
            const finalTotal = Math.round(pool / pembagi);

            return {
                rawTotal: Number(rawTotal) || 0,
                percent: percent,
                pembagi: pembagi,
                distributionMode: distributionMode,
                recipients: recipients,
                pool: pool,
                finalTotal: finalTotal,
                totalDistributed: distributionMode === 'per_pegawai' ? finalTotal * recipients : finalTotal,
                perRecipient: distributionMode === 'per_pegawai' ?
                    finalTotal :
                    (recipients ? Math.floor(finalTotal / recipients) : 0)
            };
        }

        function bpjsPreviewRowsHtml(rows) {
            return rows.map(function(row) {
                return '<div class="vk-bpjs-preview-row">' +
                    '<span>' + escapeHtml(row.label) + '</span>' +
                    '<strong>' + escapeHtml(row.value) + '</strong>' +
                    '</div>';
            }).join('');
        }

        function percentText(value) {
            const numericValue = Number(value);

            if (!Number.isFinite(numericValue)) {
                return '-';
            }

            return formatNumber(numericValue) + '%';
        }

        function initials(value) {
            const parts = String(value || 'VK')
                .trim()
                .split(/\s+/)
                .filter(Boolean)
                .slice(0, 2);

            return (parts.map(part => part.charAt(0)).join('') || 'VK').toUpperCase();
        }

        function detailInfoRows(rows) {
            return '<div class="vk-detail-stat-grid">' + rows.map(function(row) {
                return '' +
                    '<div class="vk-detail-stat">' +
                    '   <span>' + escapeHtml(row.label) + '</span>' +
                    '   <strong>' + escapeHtml(row.value) + '</strong>' +
                    '</div>';
            }).join('') + '</div>';
        }

        function detailBadgesHtml(rows) {
            return rows.map(function(row) {
                return '' +
                    '<span class="vk-detail-badge">' +
                    '   <i class="mdi ' + escapeHtml(row.icon) + '"></i>' +
                    escapeHtml(row.value) +
                    '</span>';
            }).join('');
        }

        function detailPercent(part, total) {
            const totalNumber = Number(total) || 0;

            if (totalNumber <= 0) {
                return 0;
            }

            return Math.max(0, Math.min(100, Math.round((Number(part) || 0) / totalNumber * 100)));
        }

        function renderDetailBadges(row) {
            const config = row.config_snapshot || {};

            $('#detailVkBadges').html(detailBadgesHtml([{
                    icon: 'mdi-calendar-month-outline',
                    value: row.periode || '-'
                },
                {
                    icon: 'mdi-source-branch',
                    value: row.sumber_label || 'Manual'
                },
                {
                    icon: 'mdi-vector-arrange-below',
                    value: row.ploting_label || '-'
                },
                {
                    icon: 'mdi-account-group-outline',
                    value: formatNumber((row.details || []).length) + ' penerima'
                },
                {
                    icon: 'mdi-call-split',
                    value: config.distribution_mode_label || '-'
                }
            ]));
        }

        function renderDetailComposition(row) {
            const config = row.config_snapshot || {};
            const rawTotal = Number(row.total_vk_awal || row.total_vk || 0);
            const pool = Number(row.bpjs_pool || 0);
            const hasil = Number(config.bpjs_hasil_perhitungan || row.total_vk || 0);
            const distributed = Number(row.total_dibagikan || row.total_vk || 0);
            const rows = [{
                    label: 'Pool BPJS dari total awal',
                    value: formatRupiah(pool),
                    percent: detailPercent(pool, rawTotal)
                },
                {
                    label: 'Hasil setelah pembagi',
                    value: formatRupiah(hasil),
                    percent: detailPercent(hasil, rawTotal)
                },
                {
                    label: 'Total masuk pegawai',
                    value: formatRupiah(distributed),
                    percent: detailPercent(distributed, rawTotal)
                }
            ];

            $('#detailVkComposition').html(rows.map(function(item) {
                return '' +
                    '<div class="vk-composition-row">' +
                    '   <div class="vk-composition-head">' +
                    '       <span>' + escapeHtml(item.label) + '</span>' +
                    '       <strong>' + escapeHtml(item.value) + '</strong>' +
                    '   </div>' +
                    '   <div class="vk-composition-track">' +
                    '       <div class="vk-composition-bar" style="width: ' + item.percent + '%"></div>' +
                    '   </div>' +
                    '</div>';
            }).join(''));
        }

        function detailFormulaSteps(row) {
            const config = row.config_snapshot || {};
            const rawTotal = Number(row.total_vk_awal || (Number(row.jumlah_tindakan || 0) * Number(row.nominal_hitung || 0)) || 0);
            const percent = Number(config.bpjs_percent ?? 0);
            const pembagi = Math.max(1, Number(config.bpjs_pembagi ?? 1));
            const pool = Number(row.bpjs_pool || Math.round(rawTotal * percent / 100) || 0);
            const hasil = Number(config.bpjs_hasil_perhitungan || Math.round(pool / pembagi) || 0);
            const recipients = row.details || [];
            const totalPegawai = Number(row.total_dibagikan || row.total_vk || 0);
            const modeLabel = config.distribution_mode_label || 'Bagi rata ke semua pegawai';

            return [{
                    label: 'Total VK awal',
                    note: formatNumber(row.jumlah_tindakan || 0) + ' tindakan x ' + formatRupiah(row.nominal_hitung || 0),
                    value: formatRupiah(rawTotal)
                },
                {
                    label: 'Pool BPJS',
                    note: percentText(percent) + ' dari total VK awal',
                    value: formatRupiah(pool)
                },
                {
                    label: 'Hasil perhitungan',
                    note: 'Pool BPJS dibagi ' + formatNumber(pembagi),
                    value: formatRupiah(hasil)
                },
                {
                    label: 'Pemberian pegawai',
                    note: modeLabel + ' untuk ' + formatNumber(recipients.length) + ' pegawai',
                    value: formatRupiah(totalPegawai)
                }
            ];
        }

        function formulaStepsHtml(steps) {
            return steps.map(function(step, index) {
                return '' +
                    '<div class="vk-formula-step">' +
                    '   <div class="vk-formula-step-number">' + (index + 1) + '</div>' +
                    '   <div>' +
                    '       <div class="vk-formula-step-label">' + escapeHtml(step.label) + '</div>' +
                    '       <div class="vk-formula-step-note">' + escapeHtml(step.note) + '</div>' +
                    '   </div>' +
                    '   <div class="vk-formula-step-value">' + escapeHtml(step.value) + '</div>' +
                    '</div>';
            }).join('');
        }

        function renderDetailInfo(row) {
            const config = row.config_snapshot || {};

            $('#detailVkInfo').html(detailInfoRows([{
                    label: 'Periode',
                    value: row.periode || '-'
                },
                {
                    label: 'Ploting',
                    value: row.ploting_label || '-'
                },
                {
                    label: 'Jumlah tindakan',
                    value: formatNumber(row.jumlah_tindakan || 0)
                },
                {
                    label: 'Nominal hitung',
                    value: formatRupiah(row.nominal_hitung || 0)
                },
                {
                    label: 'Mode pembagian',
                    value: config.distribution_mode_label || '-'
                },
                {
                    label: 'Generate',
                    value: [row.generate_by_name, row.generated_at].filter(Boolean).join(' / ') || '-'
                }
            ]));
        }

        function renderDetailSnapshot(row) {
            const config = row.config_snapshot || {};

            $('#detailVkConfigSnapshot').html(detailInfoRows([{
                    label: 'Formula',
                    value: config.formula_label || 'Total VK awal x persen BPJS / pembagi'
                },
                {
                    label: 'Persen BPJS',
                    value: percentText(config.bpjs_percent)
                },
                {
                    label: 'Pembagi',
                    value: formatNumber(config.bpjs_pembagi || 1)
                },
                {
                    label: 'Mode',
                    value: config.distribution_mode_label || '-'
                },
                {
                    label: 'Total penerima',
                    value: formatNumber((row.details || []).length) + ' pegawai'
                },
                {
                    label: 'Total dibagikan',
                    value: formatRupiah(row.total_dibagikan || row.total_vk || 0)
                }
            ]));
        }

        function renderDetailRecipientMetrics(row) {
            const details = row.details || [];
            const total = details.reduce(function(sum, item) {
                return sum + Number(item.total_received || 0);
            }, 0);
            const amounts = details.map(item => Number(item.total_received || 0));
            const maxAmount = amounts.length ? Math.max.apply(null, amounts) : 0;
            const minAmount = amounts.length ? Math.min.apply(null, amounts) : 0;
            const average = details.length ? Math.round(total / details.length) : 0;

            $('#detailVkRecipientMetrics').html(detailInfoRows([{
                    label: 'Total penerima',
                    value: formatNumber(details.length) + ' pegawai'
                },
                {
                    label: 'Rata-rata',
                    value: formatRupiah(average)
                },
                {
                    label: 'Nominal terbesar',
                    value: formatRupiah(maxAmount)
                },
                {
                    label: 'Nominal terkecil',
                    value: formatRupiah(minAmount)
                },
                {
                    label: 'Total dibagikan',
                    value: formatRupiah(total)
                },
                {
                    label: 'Mode',
                    value: (row.config_snapshot || {}).distribution_mode_label || '-'
                }
            ]));
        }

        function renderDetailRecipients(row, keyword) {
            const details = row.details || [];
            const query = String(keyword || '').toLowerCase();
            const filtered = details.filter(function(item) {
                return !query ||
                    String(item.pegawai_name || '').toLowerCase().includes(query) ||
                    String(item.pegawai_id || '').toLowerCase().includes(query) ||
                    String(item.pegawai_position || '').toLowerCase().includes(query);
            });
            const total = details.reduce(function(sum, item) {
                return sum + Number(item.total_received || 0);
            }, 0);

            $('#detailVkRecipientSummary').text(
                formatNumber(details.length) + ' pegawai penerima / total ' + formatRupiah(total)
            );

            if (!filtered.length) {
                $('#detailVkRecipients').html(
                    '<div class="vk-detail-empty">' +
                    (details.length ? 'Tidak ada pegawai yang cocok dengan pencarian.' :
                        'Belum ada pegawai penerima pada hasil BPJS ini.') +
                    '</div>'
                );
                return;
            }

            $('#detailVkRecipients').html(filtered.map(function(item) {
                const meta = [
                    item.pegawai_id || '-',
                    item.pegawai_position || '-',
                    item.role_label || 'Petugas VK'
                ].filter(Boolean).join(' / ');
                const share = item.allocation_percent == null ?
                    'Nominal hasil per pegawai' :
                    percentText(item.allocation_percent) + ' dari pool pegawai';
                const ratio = total > 0 ? Math.round((Number(item.total_received || 0) / total) * 100) : 0;

                return '' +
                    '<div class="vk-recipient-item">' +
                    '   <div class="vk-recipient-avatar">' + escapeHtml(initials(item.pegawai_name)) + '</div>' +
                    '   <div class="min-w-0">' +
                    '       <div class="vk-recipient-name">' + escapeHtml(item.pegawai_name || '-') + '</div>' +
                    '       <div class="vk-recipient-meta">' + escapeHtml(meta) + '</div>' +
                    '   </div>' +
                    '   <div class="vk-recipient-amount">' +
                    formatRupiah(item.total_received || 0) +
                    '       <span class="vk-recipient-share">' + escapeHtml(share) + ' / ' + ratio + '% total</span>' +
                    '   </div>' +
                    '</div>';
            }).join(''));
        }

        function activateDetailTab(view) {
            $('.vk-detail-tab').removeClass('active');
            $('.vk-detail-tab[data-view="' + view + '"]').addClass('active');
            $('.vk-detail-view').removeClass('active');
            $('.vk-detail-view[data-view="' + view + '"]').addClass('active');
        }

        function openDetailModal(row) {
            if (!row || row.jenis_vk !== 'bpjs') {
                Swal.fire('Info', 'Detail perhitungan khusus tersedia untuk VK BPJS.', 'info');
                return;
            }

            currentDetailRow = row;
            const config = row.config_snapshot || {};
            const steps = detailFormulaSteps(row);
            const statusHtml = row.is_locked ?
                '<i class="mdi mdi-lock"></i>Terkunci' :
                '<i class="mdi mdi-lock-open-variant-outline"></i>Terbuka';

            $('#detailVkTitle').text(row.nm_tindakan || 'VK BPJS');
            $('#detailVkMeta').text([
                row.periode,
                row.ploting_label,
                row.pj_label && row.pj_label !== '-' ? row.pj_label : null
            ].filter(Boolean).join(' / '));
            $('#detailVkStatus').html(statusHtml);
            $('#detailVkAwal').text(formatRupiah(row.total_vk_awal || row.total_vk || 0));
            $('#detailVkPool').text(formatRupiah(row.bpjs_pool || 0));
            $('#detailVkHasil').text(formatRupiah(config.bpjs_hasil_perhitungan || row.total_vk || 0));
            $('#detailVkDibagikan, #detailVkTotalSelected').text(formatRupiah(row.total_dibagikan || row.total_vk || 0));
            $('#detailVkFormulaFlow, #detailVkFormulaOnly').html(formulaStepsHtml(steps));
            $('#detailVkRecipientSearch').val('');

            renderDetailBadges(row);
            renderDetailComposition(row);
            renderDetailInfo(row);
            renderDetailSnapshot(row);
            renderDetailRecipientMetrics(row);
            renderDetailRecipients(row, '');
            activateDetailTab('overview');
            detailModal.show();
        }

        function detailSummaryText(row) {
            const config = row.config_snapshot || {};
            const lines = [
                'Detail VK BPJS: ' + (row.nm_tindakan || '-'),
                'Periode: ' + (row.periode || '-'),
                'Ploting: ' + (row.ploting_label || '-'),
                'Total VK awal: ' + formatRupiah(row.total_vk_awal || 0),
                'Pool BPJS: ' + formatRupiah(row.bpjs_pool || 0),
                'Hasil rumus: ' + formatRupiah(config.bpjs_hasil_perhitungan || 0),
                'Mode: ' + (config.distribution_mode_label || '-'),
                'Total pemberian pegawai: ' + formatRupiah(row.total_dibagikan || row.total_vk || 0),
                'Pegawai: ' + (row.details || []).map(function(item) {
                    return (item.pegawai_name || '-') + ' = ' + formatRupiah(item.total_received || 0);
                }).join(', ')
            ];

            return lines.join('\n');
        }

        function copyText(text) {
            const textarea = $('<textarea>')
                .val(text)
                .css({
                    position: 'fixed',
                    opacity: 0
                })
                .appendTo('body');

            textarea[0].select();
            document.execCommand('copy');
            textarea.remove();
        }

        function updatePreviewTotal() {
            let total = 0;
            let rawTotal = 0;

            $('#generateVkRows .vk-input-row').each(function() {
                const row = $(this);
                const subtotal = rowTotal(row);
                rawTotal += rawRowTotal(row);
                total += subtotal;
                row.find('.vk-row-total').text(formatRupiah(subtotal));
            });

            $('#previewTotalGenerateVk').text(formatRupiah(total));
            renderGenerateBpjsPreview(rawTotal, total);
        }

        function renderGenerateBpjsPreview(rawTotal, finalTotal) {
            const jenis = $('#jenisGenerateVk').val() || activeType;

            if (jenis !== 'bpjs') {
                $('#previewBpjsGenerateVk').addClass('d-none').empty();
                return;
            }

            const calc = bpjsCalculation(rawTotal);

            $('#previewBpjsGenerateVk')
                .removeClass('d-none')
                .html(bpjsPreviewRowsHtml([{
                        label: 'Total VK awal',
                        value: formatRupiah(calc.rawTotal)
                    },
                    {
                        label: 'Persen BPJS',
                        value: formatNumber(calc.percent) + '% = ' + formatRupiah(calc.pool)
                    },
                    {
                        label: 'Setelah pembagi ' + formatNumber(calc.pembagi),
                        value: formatRupiah(calc.finalTotal)
                    },
                    {
                        label: 'Mode pembagian',
                        value: calc.distributionMode === 'per_pegawai' ? 'Per pegawai hasil penuh' : 'Bagi rata'
                    },
                    {
                        label: 'Pegawai penerima',
                        value: formatNumber(calc.recipients) + ' orang'
                    },
                    {
                        label: 'Total dibagikan',
                        value: formatRupiah(calc.totalDistributed)
                    }
                ]));
        }

        function resetFormErrors() {
            $('#entriesGenerateVkError').text('');
            $('#generateVkRows .is-invalid').removeClass('is-invalid');
            $('#generateVkRows .row-error').text('');
        }

        function fillPlotingSelect(select, selectedValue) {
            const value = selectedValue ? String(selectedValue) : '';

            select.empty().append('<option value="">Pilih ploting</option>');
            plotingOptions.forEach(function(item) {
                select.append(
                    $('<option>', {
                        value: item.id,
                        text: item.text
                    })
                );
            });
            select.val(value);
        }

        function refreshPlotingSelects() {
            $('#generateVkRows .ploting-generate-vk-row').each(function() {
                const select = $(this);
                fillPlotingSelect(select, select.data('selected') || select.val());
            });
        }

        function loadPlotingOptions() {
            if (plotingOptionsLoaded) {
                refreshPlotingSelects();
                return $.Deferred().resolve().promise();
            }

            if (plotingOptionsRequest) {
                return plotingOptionsRequest;
            }

            plotingOptionsRequest = $.get(
                "{{ route("backOffice.keuangan.hitungPremi.generateVk.plotingOptions") }}",
                function(response) {
                    plotingOptions = response.data || [];
                    plotingOptionsLoaded = true;
                    refreshPlotingSelects();
                }
            ).always(function() {
                plotingOptionsRequest = null;
            });

            return plotingOptionsRequest;
        }

        function initConfigSelect2() {
            if (configSelectReady || !$.fn.select2) {
                return;
            }

            $('#configVkRecipients').select2({
                dropdownParent: $('#modalConfigVk'),
                width: '100%',
                placeholder: 'Pilih pegawai penerima VK BPJS',
                ajax: {
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateVk.pegawaiOptions") }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({
                        q: params.term || ''
                    }),
                    processResults: response => ({
                        results: response.data || []
                    })
                }
            });

            configSelectReady = true;
        }

        function setConfigRecipients(items) {
            const select = $('#configVkRecipients');
            select.empty();

            (items || []).forEach(function(item) {
                const id = item.pegawai_id || item.id;
                const text = item.text || (id + ' - ' + item.pegawai_name);
                const option = new Option(text, id, true, true);
                select.append(option);
            });

            select.trigger('change');
        }

        function loadVkConfig(jenis) {
            if (configRequest) {
                configRequest.abort();
            }

            configRequest = $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateVk.config") }}",
                data: {
                    jenis_vk: jenis || 'bpjs'
                },
                success: function(response) {
                    vkConfig = response.data || vkConfig;
                    $('#configVkBpjsPercent').val(vkConfig.bpjs_percent ?? 4);
                    $('#configVkBpjsPembagi').val(vkConfig.bpjs_pembagi ?? 4);
                    $('#configVkDistributionMode').val(vkConfig.distribution_mode || 'rata');
                    setConfigRecipients(vkConfig.recipients || []);
                    renderConfigPreview();
                    updatePreviewTotal();
                },
                complete: function() {
                    configRequest = null;
                }
            });

            return configRequest;
        }

        function renderConfigPreview() {
            const calc = bpjsCalculation(1000000);
            const recipients = ($('#configVkRecipients').val() || []).length;
            const perPegawai = calc.distributionMode === 'per_pegawai' ?
                calc.finalTotal :
                (recipients ? Math.floor(calc.finalTotal / recipients) : 0);
            const totalDistributed = calc.distributionMode === 'per_pegawai' ?
                calc.finalTotal * recipients :
                calc.finalTotal;

            $('#configVkPreview').html(bpjsPreviewRowsHtml([{
                    label: 'Contoh total awal',
                    value: formatRupiah(calc.rawTotal)
                },
                {
                    label: 'Pool BPJS',
                    value: formatRupiah(calc.pool)
                },
                {
                    label: 'Hasil setelah pembagi',
                    value: formatRupiah(calc.finalTotal)
                },
                {
                    label: 'Mode pembagian',
                    value: calc.distributionMode === 'per_pegawai' ? 'Per pegawai hasil penuh' : 'Bagi rata'
                },
                {
                    label: 'Estimasi per pegawai',
                    value: recipients ? formatRupiah(perPegawai) + ' / orang' : 'Belum ada pegawai'
                },
                {
                    label: 'Estimasi total dibagikan',
                    value: formatRupiah(totalDistributed)
                }
            ]));
        }

        function openConfigModal() {
            initConfigSelect2();
            $('#configVkRecipientsError').text('');
            $('#jenisConfigVk').val('bpjs');
            configModal.show();
            loadVkConfig('bpjs');
        }

        function addGenerateRow(rowData) {
            rowCounter += 1;
            const selectedPloting = rowData ? String(rowData.plotingPremi_id || '') : '';
            const row = $(
                '<div class="vk-input-row" data-row-id="' + rowCounter + '">' +
                '   <div class="vk-row-number"></div>' +
                '   <div class="vk-row-field">' +
                '       <label>Tindakan</label>' +
                '       <input type="text" class="form-control tindakan-generate-vk-row" autocomplete="off" maxlength="255" placeholder="Ketik nama tindakan" value="' +
                escapeHtml(rowData ? tindakanText(rowData) : '') +
                '">' +
                '       <div class="invalid-feedback d-block row-error tindakan-error"></div>' +
                '   </div>' +
                '   <div class="vk-row-field">' +
                '       <label>Ploting</label>' +
                '       <select class="form-select ploting-generate-vk-row"></select>' +
                '       <div class="invalid-feedback d-block row-error ploting-error"></div>' +
                '   </div>' +
                '   <div class="vk-row-field">' +
                '       <label>Jumlah</label>' +
                '       <input type="text" class="form-control text-end jumlah-tindakan-generate-vk" inputmode="numeric" autocomplete="off" placeholder="0" value="' +
                (rowData ? formatNumber(rowData.jumlah_tindakan) : '') +
                '">' +
                '       <div class="invalid-feedback d-block row-error jumlah-error"></div>' +
                '   </div>' +
                '   <div class="vk-row-field">' +
                '       <label>Nominal</label>' +
                '       <div class="input-group">' +
                '           <span class="input-group-text">Rp</span>' +
                '           <input type="text" class="form-control text-end nominal-generate-vk" inputmode="numeric" autocomplete="off" placeholder="0" value="' +
                (rowData ? formatNumber(rowData.nominal_hitung) : '') +
                '">' +
                '       </div>' +
                '       <div class="invalid-feedback d-block row-error nominal-error"></div>' +
                '   </div>' +
                '   <div class="vk-row-total">Rp 0</div>' +
                '   <button type="button" class="vk-remove-row" title="Hapus baris">' +
                '       <i class="mdi mdi-close"></i>' +
                '   </button>' +
                '</div>'
            );

            row.find('.ploting-generate-vk-row').data('selected', selectedPloting);
            $('#generateVkRows').append(row);
            fillPlotingSelect(row.find('.ploting-generate-vk-row'), selectedPloting);
            refreshRowNumbers();
            updatePreviewTotal();
        }

        function refreshRowNumbers() {
            const rows = $('#generateVkRows .vk-input-row');
            const canRemove = !isEditMode && rows.length > 1;

            rows.each(function(index) {
                const row = $(this);
                row.find('.vk-row-number').text(index + 1);
                row.find('.vk-remove-row').prop('disabled', !canRemove);
            });
        }

        function renderPlotingSummary(items) {
            const container = $('#summaryPlotingVk');
            const rows = items || [];

            if (!rows.length) {
                container.html('<div class="vk-ploting-empty">Belum ada total per ploting.</div>');
                return;
            }

            container.html(rows.map(function(item) {
                return '' +
                    '<div class="vk-ploting-item">' +
                    '   <div class="vk-ploting-name">' + escapeHtml(item.ploting_label || '-') + '</div>' +
                    '   <div class="vk-ploting-meta">' +
                    formatNumber(item.generated_count || 0) + ' data / ' +
                    formatNumber(item.jumlah_tindakan || 0) + ' jumlah / ' +
                    formatNumber(item.locked_count || 0) + ' terkunci' +
                    '   </div>' +
                    '   <div class="vk-ploting-total">' + formatRupiah(item.total_vk || 0) + '</div>' +
                    '</div>';
            }).join(''));
        }

        function loadSummary() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateVk.summary") }}",
                data: {
                    periode: $('#periodeVk').val(),
                    jenis_vk: activeType
                },
                success: function(response) {
                    const data = response.data || {};
                    $('#summaryTindakanVk').text(formatNumber(data.jumlah_tindakan));
                    $('#summaryJumlahVk').text(formatNumber(data.total_jumlah_tindakan));
                    $('#summaryDataVk').text(formatNumber(data.generated_count));
                    $('#summaryHasilHitungVk').text(formatRupiah(data.total_hasil_hitung || 0));
                    $('#summaryHasilHitungVkCard').toggleClass('d-none', activeType !== 'bpjs');
                    $('#summaryTotalVk').text(formatRupiah(data.total_vk));
                    renderPlotingSummary(data.ploting_summaries);
                    $('#summaryVkSubtitle').text(
                        'Jenis ' + (data.jenis_vk_label || typeConfig[activeType]) +
                        ' / ' + formatNumber(data.locked_count || 0) + ' data terkunci'
                    );
                },
                error: function() {
                    $('#summaryTindakanVk, #summaryJumlahVk, #summaryDataVk').text('0');
                    $('#summaryHasilHitungVk').text('Rp 0');
                    $('#summaryHasilHitungVkCard').toggleClass('d-none', activeType !== 'bpjs');
                    $('#summaryTotalVk').text('Rp 0');
                    renderPlotingSummary([]);
                    $('#summaryVkSubtitle').text('Ringkasan gagal dimuat.');
                }
            });
        }

        setDefaultPeriod();

        const table = $('#tableGenerateVk').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            lengthChange: false,
            pageLength: 10,
            order: [],
            ajax: {
                url: "{{ route("backOffice.keuangan.hitungPremi.generateVk.table") }}",
                data: function(data) {
                    data.periode = $('#periodeVk').val();
                    data.jenis_vk = activeType;
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
                    data: 'jenis_vk_label',
                    render: function(data, type, row) {
                        return '<span class="vk-badge ' + escapeHtml(row.jenis_vk) + '">' +
                            escapeHtml(data) + '</span>';
                    }
                },
                {
                    data: 'nm_tindakan',
                    render: function(data, type, row) {
                        const parent = row.parent_label && row.parent_label !== '-' ?
                            '<small class="d-block text-muted">Judul: ' + escapeHtml(row.parent_label) + '</small>' : '';
                        const kode = row.sumber_tindakan === 'MANUAL' ?
                            '<small class="d-block text-muted">Input manual</small>' :
                            '<small class="d-block text-muted">' + escapeHtml(row.kd_tindakan || '-') + '</small>';

                        return '<strong>' + escapeHtml(data || '-') + '</strong>' +
                            kode +
                            parent;
                    }
                },
                {
                    data: 'sumber_label',
                    render: function(data, type, row) {
                        const pj = row.pj_label && row.pj_label !== '-' ?
                            '<small class="d-block text-muted">' + escapeHtml(row.pj_label) + '</small>' : '';

                        return '<span class="vk-source-badge">' + escapeHtml(data || row.sumber_tindakan || '-') + '</span>' + pj;
                    }
                },
                {
                    data: 'ploting_label',
                    render: function(data) {
                        return escapeHtml(data || '-');
                    }
                },
                {
                    data: 'jumlah_tindakan',
                    className: 'text-center',
                    render: formatNumber
                },
                {
                    data: 'nominal_hitung',
                    className: 'text-end',
                    render: formatRupiah
                },
                {
                    data: 'total_vk',
                    className: 'text-end',
                    render: function(data, type, row) {
                        const config = row.config_snapshot || {};
                        const bpjsInfo = row.jenis_vk === 'bpjs' ?
                            '<small class="d-block text-muted">Awal ' + formatRupiah(row.total_vk_awal || data) +
                            ' / hasil ' + formatRupiah(config.bpjs_hasil_perhitungan || data) + '</small>' +
                            '<small class="d-block text-muted">' +
                            escapeHtml(config.distribution_mode_label || 'Bagi rata ke semua pegawai') +
                            ' / ' + formatNumber(row.details_count || 0) + ' penerima</small>' :
                            '';

                        return '<strong class="text-primary">' + formatRupiah(data) + '</strong>' + bpjsInfo;
                    }
                },
                {
                    data: 'is_locked',
                    render: function(data, type, row) {
                        if (data) {
                            return '<span class="vk-lock locked" title="' +
                                escapeHtml([row.locked_by_name, row.locked_at].filter(Boolean).join(' / ')) +
                                '"><i class="mdi mdi-lock"></i>Terkunci</span>';
                        }

                        return '<span class="vk-lock open"><i class="mdi mdi-lock-open-variant-outline"></i>Terbuka</span>';
                    }
                },
                {
                    data: 'generate_by_name',
                    render: function(data, type, row) {
                        return '<span class="fw-semibold">' + escapeHtml(data || '-') + '</span>' +
                            '<small class="d-block text-muted">' + escapeHtml(row.generated_at || '-') + '</small>';
                    }
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ],
            language: {
                processing: 'Memuat data VK...',
                search: '',
                searchPlaceholder: 'Cari tindakan / ploting...',
                emptyTable: 'Belum ada hasil generate VK.',
                zeroRecords: 'Data tidak ditemukan.',
                info: 'Menampilkan _START_ - _END_ dari _TOTAL_ data',
                infoEmpty: 'Belum ada data',
                paginate: {
                    previous: 'Sebelumnya',
                    next: 'Berikutnya'
                }
            }
        });

        function refreshAll() {
            loadSummary();
            table.ajax.reload(null, false);
        }

        function setActiveType(type) {
            activeType = type;
            $('.vk-type-tab').removeClass('active');
            $('.vk-type-tab[data-type="' + type + '"]').addClass('active');
            $('#resultVkTitle').text('Hasil Generate VK ' + typeConfig[activeType]);
            $('#summaryHasilHitungVkCard').toggleClass('d-none', activeType !== 'bpjs');
        }

        function openGenerateModal(row) {
            isEditMode = Boolean(row);
            formMode = isEditMode ? 'edit' : 'generate';
            rowCounter = 0;

            const periode = row ? row.periode : $('#periodeVk').val();
            const jenis = row ? row.jenis_vk : activeType;

            $('#modalGenerateVkLabel').text(
                (isEditMode ? 'Revisi' : 'Generate') + ' VK ' + typeConfig[jenis]
            );
            $('#generateVkModalSubtitle').text(
                isEditMode ?
                'Revisi satu data tindakan dan ploting yang belum terkunci.' :
                'Tambahkan beberapa tindakan sekaligus, lalu generate dalam satu proses.'
            );
            $('#generateVkModeInfo')
                .toggleClass('alert-warning', isEditMode)
                .toggleClass('alert-info', !isEditMode)
                .text(
                    isEditMode ?
                    'Revisi akan memperbarui data VK tindakan dan ploting ini selama belum terkunci.' :
                    'Input manual jumlah tindakan VK berdasarkan data real pelayanan.'
                );
            $('#periodeGenerateVk').val(periode);
            $('#jenisGenerateVk').val(jenis);
            $('#jenisGenerateVkLabel').val(typeConfig[jenis]);
            $('#generateVkRows').empty();
            $('#btnAddGenerateVkRow').toggle(!isEditMode);
            $('#btnSubmitGenerateVk').html(
                '<i class="mdi mdi-calculator-variant-outline me-1"></i>' +
                (isEditMode ? 'Simpan Revisi' : 'Generate dan Hitung')
            );
            resetFormErrors();
            updatePreviewTotal();
            generateModal.show();

            $.when(
                loadPlotingOptions(),
                jenis === 'bpjs' ? loadVkConfig('bpjs') : $.Deferred().resolve().promise()
            ).then(function() {
                addGenerateRow(row || null);
            });
        }

        function openCopyPreviewModal(preview) {
            const items = preview.items || [];

            isEditMode = false;
            formMode = 'copy';
            rowCounter = 0;

            $('#modalGenerateVkLabel').text(
                'Copy VK ' + typeConfig[preview.jenis_vk] + ' ke ' + preview.target_periode
            );
            $('#generateVkModalSubtitle').text(
                'Preview data dari ' + preview.source_periode + '. Data masih bisa diubah sebelum disimpan.'
            );
            $('#generateVkModeInfo')
                .removeClass('alert-warning')
                .addClass('alert-info')
                .text(
                    'Data akan disimpan ke periode ' + preview.target_periode +
                    '. Jika kombinasi tindakan dan ploting sudah ada dan belum terkunci, data akan diperbarui.'
                );
            $('#periodeGenerateVk').val(preview.target_periode);
            $('#jenisGenerateVk').val(preview.jenis_vk);
            $('#jenisGenerateVkLabel').val(typeConfig[preview.jenis_vk]);
            $('#generateVkRows').empty();
            $('#btnAddGenerateVkRow').show();
            $('#btnSubmitGenerateVk').html(
                '<i class="mdi mdi-content-copy me-1"></i> Copy dan Simpan'
            );
            resetFormErrors();
            updatePreviewTotal();
            generateModal.show();

            $.when(
                loadPlotingOptions(),
                preview.jenis_vk === 'bpjs' ? loadVkConfig('bpjs') : $.Deferred().resolve().promise()
            ).then(function() {
                items.forEach(function(item) {
                    addGenerateRow(item);
                });
            });
        }

        function loadCopyPreview() {
            const button = $('#btnCopyNextMonthVk');
            const originalHtml = button.html();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateVk.copyPreview") }}",
                data: {
                    periode: $('#periodeVk').val(),
                    jenis_vk: activeType
                },
                beforeSend: function() {
                    button.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm me-1"></span> Memuat...'
                    );
                },
                success: function(response) {
                    const preview = response.data || {};

                    if (!preview.count) {
                        Swal.fire(
                            'Belum Ada Data',
                            'Tidak ada data VK ' + typeConfig[activeType] +
                            ' pada periode ' + $('#periodeVk').val() + ' untuk dicopy.',
                            'info'
                        );
                        return;
                    }

                    openCopyPreviewModal(preview);
                },
                error: function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                },
                complete: function() {
                    button.prop('disabled', false).html(originalHtml);
                }
            });
        }

        function showRowError(row, field, message) {
            const fieldClass = {
                tindakan: 'tindakan-generate-vk-row',
                ploting: 'ploting-generate-vk-row',
                jumlah: 'jumlah-tindakan-generate-vk',
                nominal: 'nominal-generate-vk'
            }[field];

            row.find('.' + fieldClass).addClass('is-invalid');
            row.find('.' + field + '-error').text(message);
        }

        function collectEntries() {
            const entries = [];
            const seen = {};
            let invalid = false;

            resetFormErrors();

            $('#generateVkRows .vk-input-row').each(function() {
                const row = $(this);
                const tindakan = String(row.find('.tindakan-generate-vk-row').val() || '').trim();
                const ploting = row.find('.ploting-generate-vk-row').val();
                const jumlahTindakan = numeric(row.find('.jumlah-tindakan-generate-vk').val());
                const nominal = numeric(row.find('.nominal-generate-vk').val());
                const key = tindakan.toLowerCase().replace(/\s+/g, ' ') + '|' + ploting;

                if (!tindakan) {
                    showRowError(row, 'tindakan', 'Tindakan wajib diisi.');
                    invalid = true;
                }

                if (!ploting) {
                    showRowError(row, 'ploting', 'Ploting premi wajib dipilih.');
                    invalid = true;
                }

                if (jumlahTindakan === '' || Number(jumlahTindakan) < 0) {
                    showRowError(row, 'jumlah', 'Jumlah tindakan minimal 0.');
                    invalid = true;
                }

                if (nominal === '' || Number(nominal) < 0) {
                    showRowError(row, 'nominal', 'Nominal hitung minimal Rp 0.');
                    invalid = true;
                }

                if (tindakan && ploting && seen[key]) {
                    showRowError(row, 'tindakan', 'Tindakan dan ploting ini duplikat.');
                    invalid = true;
                }

                if (tindakan && ploting) {
                    seen[key] = true;
                }

                entries.push({
                    nm_tindakan: tindakan,
                    plotingPremi_id: ploting,
                    jumlah_tindakan: jumlahTindakan,
                    nominal_hitung: nominal
                });
            });

            if (!entries.length) {
                $('#entriesGenerateVkError').text('Minimal tambahkan satu tindakan.');
                invalid = true;
            }

            return invalid ? null : entries;
        }

        function applyServerErrors(errors) {
            let handled = false;

            Object.keys(errors || {}).forEach(function(key) {
                const match = key.match(/^entries\.(\d+)\.(.+)$/);

                if (!match) {
                    return;
                }

                const row = $('#generateVkRows .vk-input-row').eq(Number(match[1]));
                const field = match[2];
                const message = flattenErrors({
                    field: errors[key]
                }).join('<br>');

                if (!row.length) {
                    return;
                }

                if (field === 'nm_tindakan' || field === 'source_key') {
                    showRowError(row, 'tindakan', message);
                    handled = true;
                } else if (field === 'plotingPremi_id') {
                    showRowError(row, 'ploting', message);
                    handled = true;
                } else if (field === 'jumlah_tindakan') {
                    showRowError(row, 'jumlah', message);
                    handled = true;
                } else if (field === 'nominal_hitung') {
                    showRowError(row, 'nominal', message);
                    handled = true;
                }
            });

            return handled;
        }

        $('.vk-type-tab').on('click', function() {
            setActiveType($(this).data('type'));
            refreshAll();
        });

        $('#periodeVk').on('change', refreshAll);

        $('#btnPrevPeriodVk').on('click', function() {
            movePeriod(-1);
        });

        $('#btnNextPeriodVk').on('click', function() {
            movePeriod(1);
        });

        $('#btnCurrentPeriodVk').on('click', function() {
            const now = new Date();
            setPeriodFromDate(new Date(now.getFullYear(), now.getMonth(), 1));
        });

        $('#btnRefreshVk').on('click', refreshAll);

        $('#searchGenerateVk').on('input', function() {
            table.search(this.value).draw();
        });

        $('#btnOpenGenerateVk').on('click', function() {
            openGenerateModal(null);
        });

        $('#btnCopyNextMonthVk').on('click', loadCopyPreview);

        $('#btnLockAllVk').on('click', lockAllResults);

        $('#btnConfigVk').on('click', openConfigModal);

        $('#configVkBpjsPercent, #configVkBpjsPembagi, #configVkDistributionMode').on('input change', function() {
            vkConfig.distribution_mode = $('#configVkDistributionMode').val() || 'rata';
            renderConfigPreview();
            updatePreviewTotal();
        });

        $('#configVkRecipients').on('change', function() {
            $('#configVkRecipientsError').text('');
            vkConfig.recipients = ($('#configVkRecipients').val() || []).map(function(id) {
                const selected = $('#configVkRecipients option').filter(function() {
                    return String($(this).val()) === String(id);
                });

                return {
                    pegawai_id: id,
                    pegawai_name: selected.text()
                };
            });
            renderConfigPreview();
            updatePreviewTotal();
        });

        $('#btnAddGenerateVkRow').on('click', function() {
            addGenerateRow(null);
        });

        $('#generateVkRows').on('input', '.jumlah-tindakan-generate-vk, .nominal-generate-vk', function() {
            formatInput(this);
            $(this).removeClass('is-invalid');
            $(this).closest('.vk-row-field').find('.row-error').text('');
            updatePreviewTotal();
        });

        $('#generateVkRows').on('input', '.tindakan-generate-vk-row', function() {
            $(this).removeClass('is-invalid');
            $(this).closest('.vk-row-field').find('.row-error').text('');
        });

        $('#generateVkRows').on('change', 'select', function() {
            $(this).removeClass('is-invalid');
            $(this).closest('.vk-row-field').find('.row-error').text('');
            updatePreviewTotal();
        });

        $('#generateVkRows').on('click', '.vk-remove-row', function() {
            if ($(this).prop('disabled')) {
                return;
            }

            $(this).closest('.vk-input-row').remove();
            refreshRowNumbers();
            updatePreviewTotal();
        });

        $('#formGenerateVk').on('submit', function(event) {
            event.preventDefault();

            const entries = collectEntries();

            if (!entries) {
                return;
            }

            const button = $('#btnSubmitGenerateVk');
            const originalHtml = button.html();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateVk.store") }}",
                method: 'POST',
                data: {
                    periode: $('#periodeGenerateVk').val(),
                    jenis_vk: $('#jenisGenerateVk').val(),
                    entries: entries
                },
                beforeSend: function() {
                    button.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm me-1"></span> ' +
                        (formMode === 'copy' ? 'Menyimpan copy...' : 'Generate...')
                    );
                },
                success: function(response) {
                    generateModal.hide();
                    Swal.fire('Berhasil', response.message, 'success');

                    if (formMode === 'copy') {
                        $('#periodeVk').val($('#periodeGenerateVk').val());
                        setActiveType($('#jenisGenerateVk').val());
                    }

                    refreshAll();
                },
                error: function(xhr) {
                    const response = xhr.responseJSON || {};

                    if (!applyServerErrors(response.errors || {})) {
                        Swal.fire('Gagal', errorMessage(xhr), 'error');
                    }
                },
                complete: function() {
                    button.prop('disabled', false).html(originalHtml);
                }
            });
        });

        $('#formConfigVk').on('submit', function(event) {
            event.preventDefault();

            const button = $('#btnSubmitConfigVk');
            const originalHtml = button.html();

            $('#configVkRecipientsError').text('');

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateVk.updateConfig") }}",
                method: 'PUT',
                data: {
                    jenis_vk: 'bpjs',
                    bpjs_percent: $('#configVkBpjsPercent').val(),
                    bpjs_pembagi: $('#configVkBpjsPembagi').val(),
                    distribution_mode: $('#configVkDistributionMode').val(),
                    recipients: $('#configVkRecipients').val() || []
                },
                beforeSend: function() {
                    button.prop('disabled', true).html(
                        '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...'
                    );
                },
                success: function(response) {
                    vkConfig = response.data || vkConfig;
                    configModal.hide();
                    Swal.fire('Berhasil', response.message, 'success');
                    updatePreviewTotal();
                },
                error: function(xhr) {
                    const response = xhr.responseJSON || {};

                    if (response.errors && response.errors.recipients) {
                        $('#configVkRecipientsError').html(flattenErrors({
                            recipients: response.errors.recipients
                        }).join('<br>'));
                        return;
                    }

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
                "{{ route("backOffice.keuangan.hitungPremi.generateVk.lock", ["id" => "__ID__"]) }}" :
                "{{ route("backOffice.keuangan.hitungPremi.generateVk.unlock", ["id" => "__ID__"]) }}";

            Swal.fire({
                icon: isLock ? 'warning' : 'question',
                title: isLock ? 'Kunci data VK?' : 'Buka kunci data VK?',
                text: isLock ?
                    'Data yang dikunci tidak bisa digenerate ulang.' :
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

        function lockAllResults() {
            Swal.fire({
                icon: 'warning',
                title: 'Kunci semua data VK?',
                text: 'Semua data VK ' + typeConfig[activeType] + ' periode ' + $('#periodeVk').val() +
                    ' yang masih terbuka akan dikunci.',
                showCancelButton: true,
                confirmButtonText: 'Kunci Semua',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: function() {
                    return $.ajax({
                        url: "{{ route("backOffice.keuangan.hitungPremi.generateVk.lockAll") }}",
                        method: 'POST',
                        data: {
                            periode: $('#periodeVk').val(),
                            jenis_vk: activeType
                        }
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
                "{{ route("backOffice.keuangan.hitungPremi.generateVk.delete", ["id" => "__ID__"]) }}";

            Swal.fire({
                icon: 'warning',
                title: 'Hapus data VK?',
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

        $('.vk-detail-tab').on('click', function() {
            activateDetailTab($(this).data('view'));
        });

        $('#detailVkRecipientSearch').on('input', function() {
            if (currentDetailRow) {
                renderDetailRecipients(currentDetailRow, this.value);
            }
        });

        $('#btnCopyDetailVk').on('click', function() {
            if (!currentDetailRow) {
                return;
            }

            copyText(detailSummaryText(currentDetailRow));
            Swal.fire({
                icon: 'success',
                title: 'Ringkasan disalin',
                timer: 1200,
                showConfirmButton: false
            });
        });

        $('#tableGenerateVk').on('click', '.btn-detail-vk', function() {
            const row = table.row($(this).closest('tr')).data();

            openDetailModal(row);
        });

        $('#tableGenerateVk').on('click', '.btn-lock-vk', function() {
            changeLock($(this).data('id'), 'lock');
        });

        $('#tableGenerateVk').on('click', '.btn-unlock-vk', function() {
            changeLock($(this).data('id'), 'unlock');
        });

        $('#tableGenerateVk').on('click', '.btn-delete-vk', function() {
            deleteResult($(this).data('id'));
        });

        $('#tableGenerateVk').on('click', '.btn-edit-vk', function() {
            const row = table.row($(this).closest('tr')).data();

            if (row) {
                openGenerateModal(row);
            }
        });

        loadPlotingOptions();
        loadVkConfig('bpjs');
        refreshAll();
    });
</script>
