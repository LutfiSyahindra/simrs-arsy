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
        let tableIcu = null;
        let summaryView = 'all';

        const generateModal = modalInstance('modalGenerateIcu');
        const configModal = modalInstance('modalConfigIcu');
        const detailModal = modalInstance('modalDetailIcu');

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
            const params = new URLSearchParams(window.location.search);
            const periode = params.get('periode');

            if (/^\d{4}-\d{2}$/.test(periode || '')) {
                $('#periodeIcu').val(periode);
                return;
            }

            const now = new Date();
            $('#periodeIcu').val(now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
        }

        function periodFromInput() {
            const parts = String($('#periodeIcu').val() || '').split('-').map(Number);
            return parts.length === 2 && parts[0] && parts[1] ?
                new Date(parts[0], parts[1] - 1, 1) :
                new Date();
        }

        function setPeriodFromDate(date) {
            $('#periodeIcu')
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

        function sourceRuleText() {
            const periode = $('#periodeIcu').val();
            const sourcePeriode = sourcePeriodFor(periode, activeType);

            return activeType === 'bpjs' ?
                'Generate ' + periode + ' memakai tgl_masuk kamar_inap periode ' + sourcePeriode +
                ' dengan kd_pj BPJ.' :
                'Generate ' + periode +
                ' memakai tgl_masuk kamar_inap periode aktif dengan kd_pj selain BPJ dan -.';
        }

        function clampPercent(value) {
            value = Number(value) || 0;
            return Math.max(0, Math.min(100, value));
        }

        function percentOf(part, total) {
            total = Number(total) || 0;
            return total > 0 ? clampPercent((Number(part) || 0) / total * 100) : 0;
        }

        function stateBanner(selector, state, title, text, icon) {
            $(selector)
                .removeClass('success warning danger neutral')
                .addClass(state || 'neutral')
                .html(
                    '<i class="mdi ' + escapeHtml(icon || 'mdi-information-outline') + '"></i>' +
                    '<div>' +
                    '<div class="icu-state-title">' + escapeHtml(title || '-') + '</div>' +
                    '<div class="icu-state-text">' + escapeHtml(text || '-') + '</div>' +
                    '</div>'
                );
        }

        function emptyStateHtml(title, text, icon) {
            return '<div class="icu-empty-state">' +
                '<i class="mdi ' + escapeHtml(icon || 'mdi-database-search-outline') + '"></i>' +
                '<div>' +
                '<div class="icu-empty-title">' + escapeHtml(title || 'Belum ada data') + '</div>' +
                '<div class="icu-empty-text">' + escapeHtml(text || 'Data akan tampil setelah proses selesai.') +
                '</div>' +
                '</div>' +
                '</div>';
        }

        function loadingSummaryHtml(count) {
            return '<div class="icu-loading-grid">' + Array.from({
                length: count || 8
            }).map(function() {
                return '<div class="icu-skeleton"></div>';
            }).join('') + '</div>';
        }

        function liveItem(label, value, icon) {
            return '<div class="icu-live-item">' +
                '<div class="icu-live-label">' + escapeHtml(label) + '</div>' +
                '<div class="icu-live-value">' +
                '<i class="mdi ' + escapeHtml(icon || 'mdi-information-outline') + '"></i>' +
                '<span>' + escapeHtml(value || '-') + '</span>' +
                '</div>' +
                '</div>';
        }

        function summaryMetric(label, value, note, icon) {
            return '<div class="icu-summary-health-item">' +
                '<div class="icu-summary-health-label">' + escapeHtml(label) + '</div>' +
                '<div class="icu-summary-health-value">' +
                '<i class="mdi ' + escapeHtml(icon || 'mdi-information-outline') + ' me-1"></i>' +
                escapeHtml(value || '-') +
                '</div>' +
                (note ? '<div class="icu-info-note mt-1">' + escapeHtml(note) + '</div>' : '') +
                '</div>';
        }

        function summaryInsight(label, value, note, icon) {
            return '<div class="icu-summary-insight">' +
                '<div class="icu-summary-insight-icon"><i class="mdi ' + escapeHtml(icon ||
                    'mdi-chart-line') + '"></i></div>' +
                '<div>' +
                '<div class="icu-summary-insight-label">' + escapeHtml(label) + '</div>' +
                '<div class="icu-summary-insight-value">' + escapeHtml(value || '-') + '</div>' +
                (note ? '<div class="icu-summary-insight-note">' + escapeHtml(note) + '</div>' : '') +
                '</div>' +
                '</div>';
        }

        function summaryBar(label, value, total, note, color, basisTotal, basisLabel) {
            const grandPercent = percentOf(value, total);
            const basisPercent = basisTotal == null ? grandPercent : percentOf(value, basisTotal);
            const basisText = basisLabel ?
                percentText(basisPercent) + ' dari ' + basisLabel + ' | setara ' + percentText(grandPercent) +
                ' dari grand total' :
                percentText(grandPercent) + ' dari grand total';

            return '<div>' +
                '<div class="icu-summary-bar-top">' +
                '<span>' + escapeHtml(label) + '</span>' +
                '<span>' + formatRupiah(value) + '</span>' +
                '</div>' +
                '<div class="icu-summary-bar-track">' +
                '<div class="icu-summary-bar-fill ' + escapeHtml(color || '') + '" style="width:' + grandPercent +
                '%"></div>' +
                '</div>' +
                '<div class="icu-info-note mt-1">' + escapeHtml(basisText +
                    (note ? ' | ' + note : '')) + '</div>' +
                '</div>';
        }

        function summaryStatus(data) {
            const generated = Number(data.generated_count || 0);
            const locked = Number(data.locked_count || 0);

            if (generated < 1) {
                return {
                    state: 'neutral',
                    icon: 'mdi-play-circle-outline',
                    title: 'Belum ada hasil generate',
                    text: 'Gunakan Preview Generate untuk melihat data sumber sebelum menyimpan hasil periode ini.'
                };
            }

            if (locked >= generated) {
                return {
                    state: 'success',
                    icon: 'mdi-lock-check-outline',
                    title: 'Periode sudah aman',
                    text: 'Semua hasil generate pada filter ini sudah terkunci.'
                };
            }

            return {
                state: 'warning',
                icon: 'mdi-lock-open-alert-outline',
                title: 'Masih ada data terbuka',
                text: 'Hasil generate masih bisa diproses ulang, dikunci, atau dihapus dari riwayat.'
            };
        }

        function renderSummarySpotlight(data) {
            data = data || {};
            const status = summaryStatus(data);
            const periode = $('#periodeIcu').val() || '-';
            const sourcePeriode = sourcePeriodFor(periode, activeType) || '-';
            const generated = Number(data.generated_count || 0);
            const locked = Number(data.locked_count || 0);
            const actionMix = formatNumber(data.jumlah_tindakan_nicu) + ' NICU / ' +
                formatNumber(data.jumlah_tindakan_kritikal) + ' kritikal';

            $('#summaryIcuSpotlight').html(
                '<div class="icu-summary-total-card">' +
                '<div>' +
                '<div class="icu-summary-eyebrow">Grand Total Tersimpan</div>' +
                '<div class="icu-summary-main-value">' + formatRupiah(data.grand_total) + '</div>' +
                '<div class="icu-summary-caption">' + escapeHtml(sourceRuleText()) + '</div>' +
                '</div>' +
                '<div class="icu-summary-pill-row">' +
                '<span class="icu-summary-pill"><i class="mdi mdi-calendar-month-outline"></i>' + escapeHtml(
                    periode) + '</span>' +
                '<span class="icu-summary-pill"><i class="mdi mdi-calendar-search-outline"></i>Sumber ' +
                escapeHtml(sourcePeriode) + '</span>' +
                '<span class="icu-summary-pill"><i class="mdi mdi-hospital-building"></i>' + escapeHtml(
                    typeConfig[activeType]) + '</span>' +
                '</div>' +
                '</div>' +
                '<div class="icu-summary-status-card ' + escapeHtml(status.state) + '">' +
                '<div class="icu-summary-status-top">' +
                '<div class="icu-summary-status-icon"><i class="mdi ' + escapeHtml(status.icon) +
                '"></i></div>' +
                '<div>' +
                '<div class="icu-summary-status-title">' + escapeHtml(status.title) + '</div>' +
                '<div class="icu-summary-status-copy">' + escapeHtml(status.text) + '</div>' +
                '</div>' +
                '</div>' +
                '<div class="icu-summary-health-grid">' +
                summaryMetric('Generate', formatNumber(generated) + ' hasil', formatNumber(locked) +
                    ' terkunci', 'mdi-database-check-outline') +
                summaryMetric('Tindakan', formatNumber(data.jumlah_tindakan), actionMix,
                    'mdi-clipboard-pulse-outline') +
                summaryMetric('Pasien NICU', formatNumber(data.jumlah_pasien_nicu), 'Dari ' + formatNumber(data
                    .jumlah_pasien_sumber) + ' pasien sumber', 'mdi-bed-outline') +
                summaryMetric('Medis/Orang', formatRupiah(data.premi_medis_per_orang), 'Pool ' + formatRupiah(
                    data.total_premi_medis_pool), 'mdi-doctor') +
                '</div>' +
                '</div>'
            );
        }

        function renderSummaryInsights(data) {
            data = data || {};
            $('#summaryIcuInsights').html([
                summaryInsight('Cakupan NICU', percentText(percentOf(data.jumlah_pasien_nicu, data
                        .jumlah_pasien_sumber)),
                    formatNumber(data.jumlah_pasien_nicu) + ' dari ' + formatNumber(data
                        .jumlah_pasien_sumber) + ' pasien sumber.', 'mdi-account-injury-outline'),
                summaryInsight('Tindakan Terkait NICU', formatNumber(data.jumlah_tindakan_nicu),
                    formatNumber(data.jumlah_tindakan_kritikal) + ' tindakan kritikal ikut terhitung.',
                    'mdi-clipboard-pulse-outline'),
                summaryInsight('Pool NICU Reguler', formatRupiah(data.total_perawat_nicu_reguler),
                    'Bagian reguler dari pool Perawat NICU.', 'mdi-account-heart-outline'),
                summaryInsight('Premi Bersama', formatRupiah(data.total_premi_bersama),
                    'Dibagikan dari persentase bersama konfigurasi.', 'mdi-account-group-outline')
            ].join(''));
        }

        function renderSummaryDistribution(data) {
            data = data || {};
            const total = Number(data.grand_total || 0);
            $('#summaryIcuDistribution').html(
                '<div class="icu-summary-distribution-head">' +
                '<div>' +
                '<div class="icu-summary-distribution-title">Distribusi Nominal</div>' +
                '<div class="icu-summary-distribution-subtitle">Perbandingan pool terhadap grand total periode ini.</div>' +
                '</div>' +
                '<span class="icu-summary-pill"><i class="mdi mdi-chart-bar"></i>' + escapeHtml(formatRupiah(total)) +
                '</span>' +
                '</div>' +
                '<div class="icu-summary-bars">' + [
                    summaryBar('Pool Perawat NICU', data.total_perawat_nicu, total,
                        'basis utama NICU dari konfigurasi', ''),
                    summaryBar('NICU Reguler', data.total_perawat_nicu_reguler, total,
                        'porsi reguler sesuai konfigurasi', 'teal', data.total_perawat_nicu,
                        'Pool Perawat NICU'),
                    summaryBar('NICU Khusus', data.total_pegawai_nicu_khusus, total,
                        'sub-pool pegawai khusus sesuai konfigurasi', 'amber', data.total_perawat_nicu,
                        'Pool Perawat NICU'),
                    summaryBar('Pool Medis', data.total_premi_medis_pool, total,
                        'persentase medis dari konfigurasi', 'blue'),
                    summaryBar('Premi Bersama', data.total_premi_bersama, total,
                        'persentase bersama dari konfigurasi', 'teal')
                ].join('') + '</div>'
            );
        }

        function setSummaryLoading() {
            $('#summaryIcuSpotlight').html(loadingSummaryHtml(2));
            $('#summaryIcuInsights').html(loadingSummaryHtml(4));
            $('#summaryIcuDistribution').html('<div class="icu-skeleton"></div>');
            $('#summaryIcuNote').text('Memuat ringkasan...');
        }

        function renderSummaryHealth(data) {
            data = data || {};
            const periode = $('#periodeIcu').val();
            const sourcePeriode = sourcePeriodFor(periode, activeType);
            const generated = Number(data.generated_count || 0);
            const locked = Number(data.locked_count || 0);
            const status = generated > 0 ?
                (locked >= generated ? 'Sudah generate dan terkunci' : 'Sudah generate, masih terbuka') :
                'Belum ada hasil generate';

            $('#heroIcuType').text(typeConfig[activeType]);
            $('#heroIcuSource').text(sourcePeriode || '-');
            $('#heroIcuStatus').text(status);

            $('#icuLiveStrip').html([
                liveItem('Periode Generate', periode || '-', 'mdi-calendar-month-outline'),
                liveItem('Sumber Data', sourcePeriode || '-', activeType === 'bpjs' ?
                    'mdi-calendar-arrow-left' : 'mdi-calendar-check-outline'),
                liveItem('Riwayat Periode', formatNumber(generated) + ' hasil, ' + formatNumber(locked) +
                    ' terkunci', 'mdi-database-clock-outline'),
                liveItem('Nilai Tersimpan', formatRupiah(data.grand_total), 'mdi-cash-multiple')
            ].join(''));
        }

        function percentText(value) {
            return formatNumber(value || 0) + '%';
        }

        function compactListLabel(items, unit) {
            items = (items || []).filter(Boolean);

            if (!items.length) {
                return '-';
            }

            return items.length > 1 ? items[0] + ' + ' + (items.length - 1) + ' ' + unit : items[0];
        }

        function criticalActionNames(config) {
            const names = Array.isArray(config.critical_action_names) ? config.critical_action_names : [];

            if (names.length) {
                return names;
            }

            return config.critical_action_name ? [config.critical_action_name] : [];
        }

        function miniCard(label, value, primary, icon, note, group) {
            return '<div class="icu-mini-card ' + (primary ? 'total' : '') + '"' +
                (group ? ' data-summary-group="' + escapeHtml(group) + '"' : '') + '>' +
                '<div class="icu-mini-top">' +
                '<div class="icu-mini-label">' + escapeHtml(label) + '</div>' +
                '<div class="icu-mini-icon"><i class="mdi ' + escapeHtml(icon || 'mdi-chart-box-outline') +
                '"></i></div>' +
                '</div>' +
                '<div class="icu-mini-value">' + escapeHtml(value) + '</div>' +
                (note ? '<div class="icu-mini-note">' + escapeHtml(note) + '</div>' : '') +
                '</div>';
        }

        function insightCard(label, value, note, icon) {
            return '<div class="icu-insight-card">' +
                '<div class="icu-insight-icon"><i class="mdi ' + escapeHtml(icon || 'mdi-chart-line') +
                '"></i></div>' +
                '<div>' +
                '<div class="icu-insight-label">' + escapeHtml(label) + '</div>' +
                '<div class="icu-insight-value">' + escapeHtml(value || '-') + '</div>' +
                (note ? '<div class="icu-insight-note">' + escapeHtml(note) + '</div>' : '') +
                '</div>' +
                '</div>';
        }

        function detailKpi(label, value, note, icon, primary) {
            return '<div class="icu-detail-kpi ' + (primary ? 'primary' : '') + '">' +
                '<div class="icu-detail-kpi-top">' +
                '<div class="icu-detail-kpi-label">' + escapeHtml(label) + '</div>' +
                '<div class="icu-detail-kpi-icon"><i class="mdi ' + escapeHtml(icon ||
                    'mdi-chart-box-outline') + '"></i></div>' +
                '</div>' +
                '<div class="icu-detail-kpi-value">' + escapeHtml(value || '-') + '</div>' +
                (note ? '<div class="icu-detail-kpi-note">' + escapeHtml(note) + '</div>' : '') +
                '</div>';
        }

        function flowStep(title, note, state, icon) {
            return '<div class="icu-flow-step ' + escapeHtml(state || '') + '">' +
                '<div class="icu-flow-step-icon"><i class="mdi ' + escapeHtml(icon ||
                    'mdi-checkbox-blank-circle-outline') + '"></i></div>' +
                '<div>' +
                '<div class="icu-flow-step-title">' + escapeHtml(title) + '</div>' +
                '<div class="icu-flow-step-note">' + escapeHtml(note) + '</div>' +
                '</div>' +
                '</div>';
        }

        function barHtml(label, value, note, color) {
            const percent = clampPercent(value);
            return '<div>' +
                '<div class="icu-bar-top">' +
                '<span>' + escapeHtml(label) + '</span>' +
                '<span>' + percentText(percent) + '</span>' +
                '</div>' +
                '<div class="icu-bar-track">' +
                '<div class="icu-bar-fill ' + escapeHtml(color || '') + '" style="width:' + percent +
                '%"></div>' +
                '</div>' +
                (note ? '<div class="icu-info-note mt-1">' + escapeHtml(note) + '</div>' : '') +
                '</div>';
        }

        function formulaBarsHtml(config) {
            config = config || {};
            const khusus = clampPercent(config.pegawai_nicu_khusus_percent || 0);
            const reguler = clampPercent(config.perawat_nicu_reguler_percent != null ?
                config.perawat_nicu_reguler_percent : Math.max(0, 100 - khusus));
            return '<div class="icu-bar-list">' + [
                barHtml('Pool Perawat NICU', config.perawat_nicu_percent, 'Porsi dari grand total tindakan NICU.',
                    ''),
                barHtml('NICU Khusus', khusus, 'Porsi dari pool Perawat NICU.', 'amber'),
                barHtml('NICU Reguler', reguler, 'Porsi manual dari pool Perawat NICU.',
                    'teal'),
                barHtml('Premi Medis', config.premi_medis_percent, 'Porsi dari grand total.', 'blue'),
                barHtml('Premi Bersama', config.premi_bersama_percent, 'Porsi dari grand total.', 'teal')
            ].join('') + '</div>';
        }

        function infoRowsHtml(rows) {
            return rows.map(function(row) {
                return '<div class="icu-info-row">' +
                    '<div>' +
                    '<div class="icu-info-label">' + escapeHtml(row.label) + '</div>' +
                    (row.note ? '<div class="icu-info-note">' + escapeHtml(row.note) + '</div>' : '') +
                    '</div>' +
                    '<div class="icu-info-value">' + escapeHtml(row.value) + '</div>' +
                    '</div>';
            }).join('');
        }

        function formulaRows(data) {
            const config = data.config || data.config_snapshot || data || {};
            const mapping = config.mapping || (data.source ? data.source.mapping : null) || {};
            const criticalNames = criticalActionNames(config);
            const regulerPercent = config.perawat_nicu_reguler_percent != null ?
                Number(config.perawat_nicu_reguler_percent) :
                Math.max(0, 100 - Number(config.pegawai_nicu_khusus_percent || 25));

            return [{
                    label: 'Mapping Tindakan',
                    value: mapping.label || config.mapping_label || data.mapping_label || '-',
                    note: (mapping.jumlah_jenis_tindakan || (config.jnsTindakan_ids || []).length || 0) +
                        ' jenis, ' + (mapping.jumlah_mapping || 0) + ' rincian mapping'
                },
                {
                    label: 'Perawat NICU',
                    value: percentText(config.perawat_nicu_percent),
                    note: 'Pool utama dari grand total tindakan NICU.'
                },
                {
                    label: 'Pegawai NICU Khusus',
                    value: percentText(config.pegawai_nicu_khusus_percent),
                    note: 'Diambil dari pool Perawat NICU.'
                },
                {
                    label: 'Perawat NICU Reguler',
                    value: percentText(regulerPercent) + ' / ' + formatNumber(config.perawat_nicu_divider ||
                        4),
                    note: 'Porsi manual dari pool NICU, dibagi sesuai pembagi dan pegawai terpilih.'
                },
                {
                    label: 'Premi Medis',
                    value: percentText(config.premi_medis_percent) + ' / ' + formatNumber(config
                        .premi_medis_divider || 34),
                    note: 'Pool medis dibagi pembagi konfigurasi.'
                },
                {
                    label: 'Premi Bersama',
                    value: percentText(config.premi_bersama_percent),
                    note: 'Diambil dari grand total tindakan NICU.'
                },
                {
                    label: 'Tindakan Kritikal',
                    value: compactListLabel(criticalNames, 'tindakan'),
                    note: formatNumber(criticalNames.length) + ' nama tindakan rawat inap Khanza.'
                },
                {
                    label: 'Penerima Perawat NICU',
                    value: formatNumber((config.recipient_counts || {}).perawat_nicu || (config.recipients &&
                            config.recipients.perawat_nicu ? config.recipients.perawat_nicu.length : 0)) +
                        ' pegawai',
                    note: 'Jumlah penerima harus sama dengan pembagi pool reguler.'
                },
                {
                    label: 'Penerima NICU Khusus',
                    value: formatNumber((config.recipient_counts || {}).pegawai_nicu_khusus || (config
                        .recipients && config.recipients.pegawai_nicu_khusus ? config.recipients
                        .pegawai_nicu_khusus.length : 0)) + ' pegawai',
                    note: 'Menerima sub-pool pegawai NICU khusus.'
                }
            ];
        }

        function renderSummary(data) {
            $('#summaryIcuGrid').html([
                miniCard('Hasil Generate', formatNumber(data.generated_count), false, 'mdi-database-check-outline',
                    formatNumber(data.locked_count) + ' terkunci', 'data'),
                miniCard('Grand Total', formatRupiah(data.grand_total), true, 'mdi-cash-multiple',
                    'Total biaya_rawat tersimpan', 'nominal'),
                miniCard('Pasien NICU', formatNumber(data.jumlah_pasien_nicu), false, 'mdi-bed-outline',
                    'Riwayat kd_kamar memuat NICU', 'data'),
                miniCard('Tindakan', formatNumber(data.jumlah_tindakan), false,
                    'mdi-clipboard-pulse-outline', 'Mapping dan tindakan kritikal', 'data'),
                miniCard('Pool NICU', formatRupiah(data.total_perawat_nicu), false,
                    'mdi-account-heart-outline', 'Khusus dan reguler sesuai konfigurasi', 'distribusi'),
                miniCard('NICU Reguler', formatRupiah(data.total_perawat_nicu_reguler), false,
                    'mdi-account-multiple-check-outline', 'Pool reguler sebelum dibagi', 'distribusi'),
                miniCard('Pegawai Khusus', formatRupiah(data.total_pegawai_nicu_khusus), false,
                    'mdi-account-star-outline', 'Porsi khusus dari pool NICU', 'distribusi'),
                miniCard('Premi Medis', formatRupiah(data.total_premi_medis_pool), false, 'mdi-doctor',
                    'Pool sebelum dibagi', 'nominal'),
                miniCard('Medis/Orang', formatRupiah(data.premi_medis_per_orang), false,
                    'mdi-account-cash-outline', 'Hasil pembagian pool medis', 'nominal'),
                miniCard('Premi Bersama', formatRupiah(data.total_premi_bersama), false,
                    'mdi-account-group-outline', 'Pool bersama periode', 'nominal')
            ].join(''));
            applySummaryView();
        }

        function applySummaryView() {
            $('.icu-summary-view').removeClass('active');
            $('.icu-summary-view[data-summary-view="' + summaryView + '"]').addClass('active');
            $('#summaryIcuGrid .icu-mini-card').each(function() {
                const group = $(this).data('summary-group');
                const visible = summaryView === 'all' || group === summaryView;
                $(this).toggleClass('icu-summary-card-hidden', !visible);
            });

            const noteMap = {
                all: 'Menampilkan semua indikator periode.',
                nominal: 'Fokus pada nominal premi dan pool.',
                data: 'Fokus pada status generate, pasien, dan tindakan.',
                distribusi: 'Fokus pada pembagian pool NICU.'
            };
            $('#summaryIcuNote').text(noteMap[summaryView] || noteMap.all);
        }

        function loadSummary() {
            $('#icuSourceRule').text(sourceRuleText());
            renderSummaryHealth({});
            setSummaryLoading();
            $('#summaryIcuGrid').html(loadingSummaryHtml(8));
            $.get("{{ route("backOffice.keuangan.hitungPremi.generateNicu.summary") }}", {
                periode: $('#periodeIcu').val(),
                jenis_nicu: activeType
            }).done(function(response) {
                const data = response.data || {};
                renderSummaryHealth(data);
                renderSummarySpotlight(data);
                renderSummaryInsights(data);
                renderSummaryDistribution(data);
                renderSummary(data);
            }).fail(function() {
                renderSummaryHealth({});
                renderSummarySpotlight({});
                renderSummaryInsights({});
                renderSummaryDistribution({});
                renderSummary({});
            });
        }

        function initTable() {
            tableIcu = $('#tableGenerateIcu').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                scrollX: true,
                order: [
                    [1, 'desc']
                ],
                ajax: {
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateNicu.table") }}",
                    data: function(d) {
                        d.periode = $('#periodeIcu').val();
                        d.jenis_nicu = activeType;
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
                        data: 'jenis_nicu_label'
                    },
                    {
                        data: 'source_periode'
                    },
                    {
                        data: 'mapping_label',
                        render: function(value) {
                            return escapeHtml(value || '-');
                        }
                    },
                    {
                        data: 'jumlah_pasien',
                        className: 'text-end',
                        render: formatNumber
                    },
                    {
                        data: null,
                        className: 'text-end',
                        render: function(row) {
                            return '<strong>' + formatNumber(row.jumlah_tindakan) +
                                '</strong>' +
                                '<div class="text-muted small">NICU ' + formatNumber(row
                                    .jumlah_tindakan_nicu) +
                                ' / kritikal ' + formatNumber(row.jumlah_tindakan_kritikal) +
                                '</div>';
                        }
                    },
                    {
                        data: 'grand_total',
                        className: 'text-end',
                        render: formatRupiah
                    },
                    {
                        data: 'total_perawat_nicu',
                        className: 'text-end',
                        render: formatRupiah
                    },
                    {
                        data: 'premi_medis_per_orang',
                        className: 'text-end',
                        render: formatRupiah
                    },
                    {
                        data: 'total_premi_bersama',
                        className: 'text-end',
                        render: formatRupiah
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(row) {
                            return row.is_locked ?
                                '<span class="icu-status locked"><i class="mdi mdi-lock me-1"></i>Terkunci</span>' :
                                '<span class="icu-status open"><i class="mdi mdi-lock-open-outline me-1"></i>Terbuka</span>';
                        }
                    },
                    {
                        data: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ]
            });
        }

        function reloadTable() {
            if (tableIcu) {
                tableIcu.ajax.reload(null, false);
            }
        }

        function initConfigSelects() {
            $('#configIcuMapping').select2({
                dropdownParent: $('#modalConfigIcu'),
                placeholder: 'Pilih satu atau beberapa mapping',
                allowClear: true,
                closeOnSelect: false,
                width: '100%',
                ajax: {
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateNicu.mappingOptions") }}",
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
                                    id: item.id,
                                    text: item.text,
                                    item: item
                                };
                            })
                        };
                    }
                }
            });

            $('#configCriticalActionName').select2({
                dropdownParent: $('#modalConfigIcu'),
                placeholder: 'Pilih satu atau beberapa tindakan kritikal',
                allowClear: true,
                closeOnSelect: false,
                width: '100%',
                ajax: {
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateNicu.criticalActionOptions") }}",
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
                                    id: item.id,
                                    text: item.text
                                };
                            })
                        };
                    }
                }
            });

            $('#configPerawatIcuRecipients, #configPegawaiIcuKhususRecipients').select2({
                dropdownParent: $('#modalConfigIcu'),
                placeholder: 'Cari pegawai aktif',
                allowClear: true,
                closeOnSelect: false,
                width: '100%',
                ajax: {
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateNicu.pegawaiOptions") }}",
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
                                    id: item.id,
                                    text: item.text
                                };
                            })
                        };
                    }
                }
            });
        }

        function selectedOptionTexts(selector) {
            return $(selector + ' option:selected').map(function() {
                return $(this).text();
            }).get();
        }

        function setSelectOptions(selector, items, valueKey, textKey) {
            const select = $(selector);
            select.empty();
            (items || []).forEach(function(item) {
                const value = item[valueKey];
                const text = item[textKey] || item.text || value;
                select.append(new Option(text, value, true, true));
            });
            select.trigger('change');
        }

        function updateRecipientCounts() {
            const perawatCount = ($('#configPerawatIcuRecipients').val() || []).length;
            const khususCount = ($('#configPegawaiIcuKhususRecipients').val() || []).length;
            const perawatDivider = Math.max(1, Number($('#configPerawatIcuDivider').val() || 4));
            const perawatHelp = $('#countPerawatIcuRecipients');
            const khususHelp = $('#countPegawaiIcuKhususRecipients');

            perawatHelp
                .removeClass('ok warn')
                .addClass(perawatCount > 0 ? 'ok' : 'warn')
                .text(
                    formatNumber(perawatCount) + ' dari ' + formatNumber(perawatDivider) +
                    ' penerima reguler dipilih.'
                );
            khususHelp
                .removeClass('ok warn')
                .addClass(khususCount > 0 ? 'ok' : 'warn')
                .text(
                    formatNumber(khususCount) + ' penerima dipilih untuk pool khusus NICU.'
                );
        }

        function configFromFields() {
            const mappingTexts = selectedOptionTexts('#configIcuMapping');
            const criticalValues = $('#configCriticalActionName').val() || [];
            const criticalTexts = selectedOptionTexts('#configCriticalActionName');

            return {
                jnsTindakan_ids: $('#configIcuMapping').val() || [],
                perawat_nicu_percent: Number($('#configPerawatIcuPercent').val() || 0),
                pegawai_nicu_khusus_percent: Number($('#configPegawaiIcuKhususPercent').val() || 0),
                perawat_nicu_reguler_percent: Number($('#configPerawatIcuRegulerPercent').val() || 0),
                perawat_nicu_divider: Math.max(1, Number($('#configPerawatIcuDivider').val() || 4)),
                premi_medis_percent: Number($('#configPremiMedisPercent').val() || 0),
                premi_medis_divider: Math.max(1, Number($('#configPremiMedisDivider').val() || 1)),
                premi_bersama_percent: Number($('#configPremiBersamaPercent').val() || 0),
                critical_action_name: criticalValues[0] || '',
                critical_action_names: criticalValues,
                critical_action_label: compactListLabel(criticalTexts, 'tindakan'),
                mapping_label: mappingTexts.length > 1 ?
                    mappingTexts[0] + ' + ' + (mappingTexts.length - 1) + ' mapping' : (mappingTexts[0] || '-'),
                recipient_counts: {
                    perawat_nicu: ($('#configPerawatIcuRecipients').val() || []).length,
                    pegawai_nicu_khusus: ($('#configPegawaiIcuKhususRecipients').val() || []).length
                }
            };
        }

        function renderConfigFormula() {
            updateRecipientCounts();
            const config = configFromFields();
            const mappingCount = (config.jnsTindakan_ids || []).length;
            const criticalCount = (config.critical_action_names || []).length;
            const perawatCount = (config.recipient_counts || {}).perawat_nicu || 0;
            const khususCount = (config.recipient_counts || {}).pegawai_nicu_khusus || 0;
            const icuDistributionPercent = Number(config.pegawai_nicu_khusus_percent || 0) +
                Number(config.perawat_nicu_reguler_percent || 0);
            const isDistributionValid = icuDistributionPercent <= 100;
            const isReady = mappingCount > 0 && perawatCount > 0 && khususCount > 0 && isDistributionValid;

            $('#configIcuFormulaPreview').html(infoRowsHtml(formulaRows(config)));
            $('#configIcuDistributionBars').html(formulaBarsHtml(config));
            $('#configIcuLiveSummary').text(
                percentText(config.perawat_nicu_percent) + ' pool, khusus ' +
                percentText(config.pegawai_nicu_khusus_percent) + ', reguler ' +
                percentText(config.perawat_nicu_reguler_percent) + ' / ' + formatNumber(config
                    .perawat_nicu_divider)
            );
            $('#configRecipientHealth').html(infoRowsHtml([{
                    label: 'Mapping tindakan',
                    value: formatNumber(mappingCount) + ' jenis',
                    note: mappingCount > 0 ? config.mapping_label : 'Pilih minimal satu mapping tindakan.'
                },
                {
                    label: 'Tindakan kritikal',
                    value: formatNumber(criticalCount) + ' tindakan',
                    note: criticalCount > 0 ? config.critical_action_label :
                        'Opsional, tetapi membantu menangkap tindakan khusus NICU.'
                },
                {
                    label: 'Perawat NICU reguler',
                    value: formatNumber(perawatCount) + ' pegawai',
                    note: 'Porsi ' + percentText(config.perawat_nicu_reguler_percent) +
                        ', pembagi aktif: ' + formatNumber(config.perawat_nicu_divider) + '.'
                },
                {
                    label: 'Pegawai NICU khusus',
                    value: formatNumber(khususCount) + ' pegawai',
                    note: khususCount > 0 ? 'Porsi ' + percentText(config.pegawai_nicu_khusus_percent) +
                        ' dari pool NICU.' :
                        'Wajib diisi jika pool khusus bernilai.'
                },
                {
                    label: 'Total distribusi NICU',
                    value: percentText(icuDistributionPercent),
                    note: isDistributionValid ? 'Masih dalam batas pool Perawat NICU.' :
                        'Tidak boleh melebihi 100% dari pool Perawat NICU.'
                }
            ]));

            stateBanner(
                '#configIcuHealthBanner',
                isReady ? 'success' : (isDistributionValid ? 'warning' : 'danger'),
                isReady ? 'Konfigurasi terlihat siap dipakai' : (isDistributionValid ?
                    'Konfigurasi perlu dilengkapi' : 'Distribusi NICU melebihi 100%'),
                isReady ?
                'Mapping dan penerima wajib sudah dipilih untuk jenis NICU ' + typeConfig[activeType] + '.' :
                (isDistributionValid ?
                    'Pastikan mapping tindakan, perawat NICU, dan pegawai NICU khusus sudah dipilih sebelum preview.' :
                    'Kurangi persentase Pegawai NICU Khusus atau Perawat NICU Reguler.'),
                isReady ? 'mdi-check-decagram-outline' : (isDistributionValid ? 'mdi-alert-circle-outline' :
                    'mdi-close-circle-outline')
            );
        }

        function openConfig() {
            $('#jenisConfigIcu').val(activeType);
            $('#configIcuTypeBadge').text(typeConfig[activeType]);
            $('#configIcuTitle').text('Konfigurasi NICU ' + typeConfig[activeType]);
            $('#configIcuRuleCopy').text(sourceRuleText());
            $('#btnSubmitConfigIcu').prop('disabled', true);
            configModal.show();

            $.get("{{ route("backOffice.keuangan.hitungPremi.generateNicu.config") }}", {
                jenis_nicu: activeType
            }).done(function(response) {
                const data = response.data || {};
                $('#configPerawatIcuPercent').val(data.perawat_nicu_percent ?? 30);
                $('#configPegawaiIcuKhususPercent').val(data.pegawai_nicu_khusus_percent ?? 25);
                $('#configPerawatIcuRegulerPercent').val(data.perawat_nicu_reguler_percent ?? 75);
                $('#configPerawatIcuDivider').val(data.perawat_nicu_divider ?? 4);
                $('#configPremiMedisPercent').val(data.premi_medis_percent ?? 25);
                $('#configPremiMedisDivider').val(data.premi_medis_divider ?? 34);
                $('#configPremiBersamaPercent').val(data.premi_bersama_percent ?? 15);

                setSelectOptions('#configIcuMapping', data.mapping_items || [], 'id', 'text');

                const criticalNames = (data.critical_action_names || (data.critical_action_name ? [data
                        .critical_action_name
                    ] : []))
                    .map(function(name) {
                        return {
                            id: name,
                            text: name
                        };
                    });
                setSelectOptions('#configCriticalActionName', criticalNames, 'id', 'text');

                const recipients = data.recipients || {};
                setSelectOptions('#configPerawatIcuRecipients', recipients.perawat_nicu || [],
                    'pegawai_id', 'text');
                setSelectOptions('#configPegawaiIcuKhususRecipients', recipients.pegawai_nicu_khusus ||
                [], 'pegawai_id', 'text');

                if (!(data.mapping_items || []).length) {
                    $('#configIcuMapping').val(null).trigger('change');
                }
                if (!criticalNames.length) {
                    $('#configCriticalActionName').val(null).trigger('change');
                }
                renderConfigFormula();
            }).fail(function(xhr) {
                Swal.fire('Gagal', errorMessage(xhr), 'error');
            }).always(function() {
                $('#btnSubmitConfigIcu').prop('disabled', false);
            });
        }

        function warningHtml(warnings) {
            const blocking = (warnings && warnings.blocking) || [];
            const info = (warnings && warnings.info) || [];
            const all = blocking.concat(info);
            if (!all.length) {
                return '';
            }

            return '<div class="icu-warning mb-3">' +
                all.map(function(item) {
                    return '<div><i class="mdi mdi-alert-circle-outline me-1"></i>' + escapeHtml(item) +
                        '</div>';
                }).join('') +
                '</div>';
        }

        function auditTile(label, value, note, icon) {
            return '<div class="icu-audit-tile">' +
                '<div class="icu-audit-label">' + escapeHtml(label) + '</div>' +
                '<div class="icu-audit-value">' +
                (icon ? '<i class="mdi ' + escapeHtml(icon) + ' me-1"></i>' : '') +
                escapeHtml(value || '-') +
                '</div>' +
                (note ? '<div class="icu-audit-note">' + escapeHtml(note) + '</div>' : '') +
                '</div>';
        }

        function chipHtml(label, type, icon) {
            return '<span class="icu-chip ' + escapeHtml(type || '') + '">' +
                (icon ? '<i class="mdi ' + escapeHtml(icon) + '"></i>' : '') +
                '<span>' + escapeHtml(label || '-') + '</span>' +
                '</span>';
        }

        function chipsHtml(items, type, icon, emptyText) {
            items = (items || []).filter(Boolean);

            if (!items.length) {
                return emptyStateHtml('Belum ada data', emptyText || 'Data belum tersimpan untuk bagian ini.',
                    'mdi-database-search-outline');
            }

            return '<div class="icu-chip-list">' + items.map(function(item) {
                return chipHtml(item, type, icon);
            }).join('') + '</div>';
        }

        function sourceRangeText(data) {
            return (data.source_tgl_awal || '-') + ' s/d ' + (data.source_tgl_akhir || '-');
        }

        function detailOverviewHtml(data) {
            const config = data.config_snapshot || {};
            const criticalNames = criticalActionNames(config);
            const generatedBy = data.generate_by_name || '-';
            const lockNote = data.is_locked ?
                'Dikunci ' + (data.locked_at || '-') + (data.locked_by_name ? ' oleh ' + data.locked_by_name :
                    '') :
                'Masih bisa diproses ulang atau dihapus.';

            return [
                auditTile('Periode Generate', data.periode, 'Sumber data ' + (data.source_periode || '-') +
                    ' | ' + sourceRangeText(data), 'mdi-calendar-month-outline'),
                auditTile('Jenis NICU', data.jenis_nicu_label, 'Aturan penjamin mengikuti jenis generate.',
                    'mdi-hospital-building'),
                auditTile('Dibuat Oleh', generatedBy, 'Generate terakhir ' + (data.generated_at || '-'),
                    'mdi-account-check-outline'),
                auditTile('Status Data', data.is_locked ? 'Terkunci' : 'Terbuka', lockNote, data.is_locked ?
                    'mdi-lock-outline' : 'mdi-lock-open-outline'),
                auditTile('Mapping Aktif', data.mapping_label || '-', formatNumber((config.mapping || {})
                    .jumlah_mapping || 0) + ' rincian tindakan mapping.', 'mdi-shape-plus-outline'),
                auditTile('Tindakan Kritikal', compactListLabel(criticalNames, 'tindakan'), formatNumber(
                    criticalNames.length) + ' nama rawat inap tersimpan.', 'mdi-alert-decagram-outline'),
                auditTile('Pasien Sumber', formatNumber(data.jumlah_pasien_sumber), formatNumber(data
                    .jumlah_pasien_nicu) + ' pasien punya riwayat NICU.', 'mdi-account-injury-outline'),
                auditTile('Tindakan Tersimpan', formatNumber(data.jumlah_tindakan), formatNumber(data
                        .jumlah_tindakan_nicu) + ' range NICU, ' + formatNumber(data
                    .jumlah_tindakan_kritikal) + ' kritikal.', 'mdi-clipboard-pulse-outline')
            ].join('');
        }

        function detailAuditRows(data) {
            const config = data.config_snapshot || {};
            const generatedBy = data.generate_by_name || '-';
            const lockNote = data.is_locked ?
                'Dikunci ' + (data.locked_at || '-') + (data.locked_by_name ? ' oleh ' + data.locked_by_name :
                    '') :
                'Masih bisa diproses ulang.';

            return [{
                    label: 'Dibuat oleh',
                    value: generatedBy,
                    note: 'Generate terakhir ' + (data.generated_at || '-')
                },
                {
                    label: 'Status data',
                    value: data.is_locked ? 'Terkunci' : 'Terbuka',
                    note: lockNote
                },
                {
                    label: 'Periode sumber',
                    value: data.source_periode || '-',
                    note: sourceRangeText(data)
                },
                {
                    label: 'Mapping aktif',
                    value: data.mapping_label || '-',
                    note: formatNumber((config.mapping || {}).jumlah_mapping || 0) + ' rincian mapping.'
                }
            ];
        }

        function detailFormulaRows(data) {
            const config = data.config_snapshot || {};
            const pools = data.pools || {};
            const khususPercent = Number(config.pegawai_nicu_khusus_percent || 25);
            const regulerPercent = config.perawat_nicu_reguler_percent != null ?
                Number(config.perawat_nicu_reguler_percent) :
                (pools.perawat_nicu_reguler_percent != null ? Number(pools.perawat_nicu_reguler_percent) :
                    Math.max(0, 100 - khususPercent));

            return [{
                    label: 'Pool Perawat NICU',
                    value: formatRupiah(pools.perawat_nicu),
                    note: percentText(config.perawat_nicu_percent) + ' dari grand total.'
                },
                {
                    label: 'NICU Khusus',
                    value: formatRupiah(pools.pegawai_nicu_khusus),
                    note: percentText(khususPercent) + ' dari pool NICU.'
                },
                {
                    label: 'NICU Reguler',
                    value: formatRupiah(pools.perawat_nicu_reguler),
                    note: percentText(regulerPercent) + ' / ' + formatNumber(pools.perawat_nicu_divider ||
                        config.perawat_nicu_divider || 4) + ' pembagi.'
                },
                {
                    label: 'Medis per Orang',
                    value: formatRupiah(pools.premi_medis_per_orang),
                    note: 'Pool ' + formatRupiah(pools.premi_medis_pool) + ' / ' + formatNumber(config
                        .premi_medis_divider || 34) + '.'
                },
                {
                    label: 'Premi Bersama',
                    value: formatRupiah(pools.premi_bersama),
                    note: percentText(config.premi_bersama_percent) + ' dari grand total.'
                }
            ];
        }

        function mappingAuditHtml(data) {
            const config = data.config_snapshot || {};
            const mapping = config.mapping || {};
            const mappingItems = mapping.items || config.mapping_items || [];
            const mappingLabels = mappingItems.map(function(item) {
                const label = item.label || [item.kode, item.jenis].filter(Boolean).join(' - ');
                const count = item.jumlah_mapping != null ? ' (' + formatNumber(item.jumlah_mapping) +
                    ')' : '';
                return label + count;
            });
            const criticalNames = criticalActionNames(config);

            return '<div class="mb-3">' +
                '<div class="icu-info-label mb-2">Jenis Mapping</div>' +
                chipsHtml(mappingLabels, 'info', 'mdi-shape-outline', 'Mapping tindakan belum tersimpan.') +
                '</div>' +
                '<div>' +
                '<div class="icu-info-label mb-2">Tindakan Kritikal Khusus</div>' +
                chipsHtml(criticalNames, 'danger', 'mdi-alert-decagram-outline',
                    'Tindakan kritikal belum tersimpan.') +
                '</div>';
        }

        function detailRowsHtml(details, limit) {
            details = details || [];
            const visible = details.slice(0, limit || 120);

            if (!visible.length) {
                return emptyStateHtml('Belum ada tindakan', 'Tidak ada tindakan yang terambil pada audit ini.',
                    'mdi-clipboard-search-outline');
            }

            return '<table class="table table-sm table-hover align-middle">' +
                '<thead><tr>' +
                '<th>Waktu</th><th>Pasien</th><th>Tindakan</th><th>NICU</th><th>Pelaksana</th><th class="text-end">Biaya</th><th>Status</th>' +
                '</tr></thead><tbody>' +
                visible.map(function(item) {
                    const provider = item.nm_dokter || item.nama_petugas || item.kd_dokter || item.nip ||
                        '-';
                    const penjamin = [item.kd_pj, item.nama_penjamin].filter(Boolean).join(' - ') || '-';
                    const icuRange = (item.tgl_masuk_nicu || '-') + ' ' + (item.jam_masuk_nicu || '') +
                        ' s/d ' + (item.tgl_keluar_nicu || 'aktif') + ' ' + (item.jam_keluar_nicu || '');
                    const status = [
                        item.is_in_nicu_range ? chipHtml('Range NICU', 'success',
                            'mdi-check-circle-outline') : null,
                        item.is_critical_action ? chipHtml('Kritikal', 'danger',
                            'mdi-alert-decagram-outline') : null
                    ].filter(Boolean).join('') || chipHtml('Audit', 'info', 'mdi-information-outline');

                    return '<tr class="icu-detail-row">' +
                        '<td><div class="icu-cell-title">' + escapeHtml(item.tanggal || '-') +
                        '</div><div class="icu-cell-muted">' + escapeHtml(item.jam || '-') +
                        '</div><div class="icu-cell-muted">' + escapeHtml(item.source_label || item
                            .source_table || '-') + '</div></td>' +
                        '<td><div class="icu-cell-title">' + escapeHtml(item.nm_pasien || '-') +
                        '</div><div class="icu-cell-muted">' + escapeHtml(item.no_rawat || '-') +
                        '</div><div class="icu-cell-muted">' + escapeHtml(item.no_rkm_medis || '-') +
                        ' | ' + escapeHtml(penjamin) + '</div></td>' +
                        '<td><div class="icu-cell-title">' + escapeHtml(item.nm_tindakan || '-') +
                        '</div><div class="icu-cell-muted">' + escapeHtml(item.kd_tindakan || '-') +
                        '</div></td>' +
                        '<td><div class="icu-cell-title">' + escapeHtml(item.kd_kamar_nicu || '-') +
                        '</div><div class="icu-cell-muted">' + escapeHtml(icuRange) + '</div></td>' +
                        '<td><div class="icu-cell-title">' + escapeHtml(provider) +
                        '</div><div class="icu-cell-muted">' + escapeHtml(item.kd_dokter || item.nip ||
                        '-') + '</div></td>' +
                        '<td class="text-end"><div class="icu-cell-title">' + formatRupiah(item
                        .biaya_rawat) + '</div></td>' +
                        '<td><div class="icu-chip-list">' + status + '</div></td>' +
                        '</tr>';
                }).join('') +
                '</tbody></table>';
        }

        function recipientTableHtml(recipients) {
            recipients = recipients || [];

            if (!recipients.length) {
                return emptyStateHtml('Belum ada penerima', 'Penerima premi belum tersimpan pada hasil generate ini.',
                    'mdi-account-search-outline');
            }

            return '<table class="table table-sm table-hover align-middle">' +
                '<thead><tr>' +
                '<th>Penerima</th><th>Role</th><th class="text-end">Persen</th><th class="text-end">Pool</th><th class="text-end">Diterima</th>' +
                '</tr></thead><tbody>' +
                recipients.map(function(item) {
                    return '<tr>' +
                        '<td><div class="icu-cell-title">' + escapeHtml(item.pegawai_name || '-') +
                        '</div><div class="icu-cell-muted">' + escapeHtml(item.pegawai_id || '-') + (item
                            .pegawai_position ? ' | ' + escapeHtml(item.pegawai_position) : '') +
                        '</div></td>' +
                        '<td>' + chipHtml(item.role_label || item.role || '-', item.role === 'perawat_nicu' ?
                            'success' : 'warning', item.role === 'perawat_nicu' ?
                            'mdi-account-heart-outline' : 'mdi-account-star-outline') + '</td>' +
                        '<td class="text-end">' + escapeHtml(item.allocation_percent == null ? '-' :
                            percentText(item.allocation_percent)) + '</td>' +
                        '<td class="text-end">' + formatRupiah(item.pool_total) + '</td>' +
                        '<td class="text-end"><strong>' + formatRupiah(item.total_received) +
                        '</strong></td>' +
                        '</tr>';
                }).join('') +
                '</tbody></table>';
        }

        function recipientGroupsHtml(groups) {
            groups = groups || [];

            if (!groups.length) {
                return emptyStateHtml('Belum ada penerima', 'Pilih penerima premi pada konfigurasi NICU.',
                    'mdi-account-multiple-plus-outline');
            }

            return infoRowsHtml(groups.map(function(group) {
                const firstItem = (group.items || [])[0] || {};
                const names = (group.items || [])
                    .map(function(item) {
                        return item.pegawai_name || item.pegawai_id;
                    })
                    .slice(0, 4)
                    .join(', ');
                const extra = (group.items || []).length > 4 ? ' +' + ((group.items || []).length -
                    4) : '';
                const perPersonNote = group.role === 'perawat_nicu' && firstItem.total_received !=
                    null ?
                    ' | pool reguler / pembagi: ' + formatRupiah(firstItem.total_received) :
                    '';

                return {
                    label: group.role_label || group.role,
                    value: formatRupiah(group.total_received || group.pool_total || 0),
                    note: formatNumber(group.recipient_count || 0) + ' penerima' +
                        perPersonNote +
                        (names ? ' - ' + names + extra : '')
                };
            }));
        }

        function renderPreview(data) {
            const source = data.source || {};
            const pools = data.pools || {};
            const config = data.config || {};
            const details = source.details || [];
            const warnings = data.warnings || {};
            const blocking = warnings.blocking || [];
            const info = warnings.info || [];
            const mappingCount = (config.jnsTindakan_ids || []).length || ((config.mapping || {}).items || [])
                .length;
            const perawatCount = (config.recipient_counts || {}).perawat_nicu || 0;
            const khususCount = (config.recipient_counts || {}).pegawai_nicu_khusus || 0;
            const configReady = mappingCount > 0 && perawatCount > 0 && khususCount > 0;
            const sourceReady = Number(source.jumlah_tindakan || 0) > 0;

            $('#generateNicuTitle').text('Preview NICU ' + (data.jenis_nicu_label || typeConfig[activeType]));
            $('#generateNicuText').text('Periode generate ' + data.periode + ' memakai data sumber ' + (source
                .source_periode || '-'));
            $('#generateNicuTypeBadge').text(data.jenis_nicu_label || typeConfig[activeType]);
            stateBanner(
                '#previewIcuStateBanner',
                blocking.length ? 'danger' : (info.length ? 'warning' : 'success'),
                blocking.length ? 'Belum bisa generate' : (info.length ?
                    'Bisa dipreview, tetapi data perlu dicek' : 'Siap generate dan simpan'),
                blocking.length ? blocking.join(' ') : (info.length ? info.join(' ') :
                    'Konfigurasi, data sumber, dan penerima premi sudah lolos pemeriksaan preview.'),
                blocking.length ? 'mdi-close-circle-outline' : (info.length ? 'mdi-alert-circle-outline' :
                    'mdi-check-decagram-outline')
            );
            $('#previewIcuFlow').html([
                flowStep('Konfigurasi', configReady ?
                    'Mapping dan penerima wajib sudah terisi.' :
                    'Lengkapi mapping dan penerima premi.', configReady ? 'done' : 'warning',
                    configReady ? 'mdi-check-circle-outline' : 'mdi-alert-circle-outline'),
                flowStep('Data Sumber', sourceReady ?
                    formatNumber(source.jumlah_tindakan) + ' tindakan siap dihitung.' :
                    'Belum ada tindakan cocok pada periode sumber.', sourceReady ? 'done' : 'warning',
                    sourceReady ? 'mdi-database-check-outline' : 'mdi-database-alert-outline'),
                flowStep('Simpan Hasil', data.can_generate ?
                    'Tombol generate sudah aktif.' :
                    'Selesaikan catatan merah sebelum menyimpan.', data.can_generate ? 'active' :
                    'warning', data.can_generate ? 'mdi-content-save-check-outline' :
                    'mdi-lock-alert-outline')
            ].join(''));
            $('#previewIcuInsights').html([
                insightCard('Periode Sumber', (source.source_periode || '-') + ' | ' + sourceRangeText(source),
                    activeType === 'bpjs' ? 'BPJS membaca periode satu bulan sebelumnya.' :
                    'Umum membaca periode generate aktif.', 'mdi-calendar-search-outline'),
                insightCard('Rasio Pasien NICU', percentText(percentOf(source.jumlah_pasien_nicu, source
                        .jumlah_pasien_sumber)),
                    formatNumber(source.jumlah_pasien_nicu) + ' dari ' + formatNumber(source
                        .jumlah_pasien_sumber) + ' pasien sumber.', 'mdi-bed-outline'),
                insightCard('Komposisi Tindakan', formatNumber(source.jumlah_tindakan_nicu) + ' NICU / ' +
                    formatNumber(source.jumlah_tindakan_kritikal) + ' kritikal',
                    'Total tindakan terambil: ' + formatNumber(source.jumlah_tindakan) + '.',
                    'mdi-clipboard-pulse-outline')
            ].join(''));
            $('#previewIcuStats').html([
                miniCard('Grand Total', formatRupiah(source.grand_total), true, 'mdi-cash-multiple',
                    'Total biaya_rawat'),
                miniCard('Pasien Sumber', formatNumber(source.jumlah_pasien_sumber), false,
                    'mdi-account-injury-outline', 'tgl_masuk periode sumber'),
                miniCard('Pasien NICU', formatNumber(source.jumlah_pasien_nicu), false, 'mdi-bed-outline',
                    'kd_kamar memuat NICU'),
                miniCard('Tindakan', formatNumber(source.jumlah_tindakan), false,
                    'mdi-clipboard-pulse-outline', 'Sesuai mapping dan kritikal'),
                miniCard('Pool NICU', formatRupiah(pools.perawat_nicu), false,
                    'mdi-account-heart-outline', 'Reguler ' + formatRupiah(pools.perawat_nicu_reguler)),
                miniCard('Perawat Reguler', formatRupiah(pools.perawat_nicu_per_orang), false,
                    'mdi-account-multiple-check-outline', 'Reguler / ' + formatNumber(pools
                        .perawat_nicu_divider || config.perawat_nicu_divider || 4)),
                miniCard('Pegawai Khusus', formatRupiah(pools.pegawai_nicu_khusus), false,
                    'mdi-account-star-outline', percentText(config.pegawai_nicu_khusus_percent) +
                    ' dari pool NICU'),
                miniCard('Medis/Orang', formatRupiah(pools.premi_medis_per_orang), false, 'mdi-doctor',
                    'Pool ' + formatRupiah(pools.premi_medis_pool)),
                miniCard('Premi Bersama', formatRupiah(pools.premi_bersama), false,
                    'mdi-account-group-outline', percentText(config.premi_bersama_percent))
            ].join(''));
            $('#previewIcuFormula').html(infoRowsHtml(formulaRows(data)));
            $('#previewIcuRecipients').html(recipientGroupsHtml(data.recipient_groups || []));
            $('#previewIcuWarnings').html(warningHtml(data.warnings));
            $('#previewIcuDetailSubtitle').text(formatNumber(details.length) +
                ' tindakan ditemukan. Menampilkan maksimal 120 baris.');
            $('#previewIcuDetails').html(detailRowsHtml(details, 120));
            previewReady = !!data.can_generate;
            $('#btnSubmitGenerateIcu')
                .prop('disabled', !previewReady)
                .toggleClass('btn-primary', previewReady)
                .toggleClass('btn-secondary', !previewReady)
                .html(
                    '<i class="mdi ' + (previewReady ? 'mdi-content-save-check-outline' :
                        'mdi-lock-alert-outline') + ' me-1"></i>' +
                    (previewReady ? 'Generate dan Simpan' : 'Belum Bisa Generate')
                );
        }

        function openPreview() {
            previewReady = false;
            $('#btnSubmitGenerateIcu')
                .prop('disabled', true)
                .removeClass('btn-primary')
                .addClass('btn-secondary')
                .html('<i class="mdi mdi-progress-clock me-1"></i> Menunggu Preview');
            $('#previewIcuLoading').removeClass('d-none');
            $('#previewIcuContent').addClass('d-none');
            generateModal.show();

            $.get("{{ route("backOffice.keuangan.hitungPremi.generateNicu.preview") }}", {
                periode: $('#periodeIcu').val(),
                jenis_nicu: activeType
            }).done(function(response) {
                renderPreview(response.data || {});
                $('#previewIcuContent').removeClass('d-none');
            }).fail(function(xhr) {
                Swal.fire('Gagal', errorMessage(xhr), 'error');
                generateModal.hide();
            }).always(function() {
                $('#previewIcuLoading').addClass('d-none');
            });
        }

        function renderDetail(data) {
            const source = data.source || {};
            const pools = data.pools || {};
            const details = data.details || [];

            $('#detailIcuTitle').text('NICU ' + data.jenis_nicu_label + ' - ' + data.periode);
            $('#detailIcuMeta').text('Mapping: ' + (data.mapping_label || '-') + ' | Generated: ' + (data
                .generated_at || '-'));
            $('#detailIcuSourceMeta').text('Sumber data ' + (data.source_periode || '-') + ' (' + (data
                .source_tgl_awal || '-') + ' s/d ' + (data.source_tgl_akhir || '-') + ')');
            $('#detailIcuStatusBadge')
                .removeClass('locked open')
                .addClass(data.is_locked ? 'locked' : 'open')
                .text(data.is_locked ? 'Terkunci' : 'Terbuka');
            $('#detailIcuSourceBadge').text('Sumber ' + (data.source_periode || '-'));
            stateBanner(
                '#detailIcuNarrative',
                data.is_locked ? 'success' : 'warning',
                data.is_locked ? 'Data sudah dikunci' : 'Data masih terbuka',
                data.is_locked ?
                'Hasil generate ini sudah diamankan dari perubahan tidak sengaja.' :
                'Hasil generate masih bisa diproses ulang, dikunci, atau dihapus dari tabel riwayat.',
                data.is_locked ? 'mdi-lock-check-outline' : 'mdi-lock-open-alert-outline'
            );
            $('#detailIcuKpiGrid').html([
                detailKpi('Grand Total', formatRupiah(data.grand_total), formatNumber(data.jumlah_tindakan) +
                    ' tindakan', 'mdi-cash-multiple', true),
                detailKpi('Pasien NICU', formatNumber(data.jumlah_pasien_nicu), percentText(percentOf(data
                    .jumlah_pasien_nicu, data.jumlah_pasien_sumber)) + ' dari pasien sumber',
                    'mdi-bed-outline', false),
                detailKpi('Pool NICU', formatRupiah(pools.perawat_nicu), 'Reguler ' + formatRupiah(pools
                    .perawat_nicu_reguler), 'mdi-account-heart-outline', false),
                detailKpi('Medis/Orang', formatRupiah(pools.premi_medis_per_orang), 'Bersama ' +
                    formatRupiah(pools.premi_bersama), 'mdi-doctor', false)
            ].join(''));
            $('#detailIcuOverview').html(infoRowsHtml(detailAuditRows(data)));
            $('#detailIcuFormula').html(infoRowsHtml(detailFormulaRows(data)));
            $('#detailIcuMappingAudit').html(mappingAuditHtml(data));
            $('#detailIcuRecipients').html(recipientGroupsHtml(data.recipient_groups || []));
            $('#detailIcuRecipientTable').html(recipientTableHtml(data.recipients || []));

            const groups = data.source_groups || [];
            $('#detailIcuSourceGroups').html(groups.length ? infoRowsHtml(groups.map(function(group) {
                    return {
                        label: group.source_label || group.source_table,
                        value: formatRupiah(group.grand_total),
                        note: formatNumber(group.jumlah_tindakan) + ' tindakan'
                    };
                })) :
                emptyStateHtml('Belum ada sumber tindakan',
                    'Sumber tindakan akan tampil setelah detail generate memiliki rincian.',
                    'mdi-database-search-outline'));

            $('#detailIcuDetailSubtitle').text(formatNumber(details.length) +
                ' tindakan tersimpan. Menampilkan maksimal 300 baris.');
            $('#detailIcuDetails').html(detailRowsHtml(details, 300));
            $('#detailIcuSearch').val('');
            $('#detailIcuFilterNote').addClass('d-none').text('');
        }

        function resetDetailTabs() {
            $('#detailIcuTabs .nav-link').removeClass('active');
            $('#detailIcuTabs .nav-link:first').addClass('active');
            $('#modalDetailIcu .tab-pane').removeClass('show active');
            $('#detailIcuSummaryPane').addClass('show active');
        }

        function filterDetailRows() {
            const keyword = String($('#detailIcuSearch').val() || '').toLowerCase().trim();
            const rows = $('#detailIcuDetails .icu-detail-row');
            let visibleCount = 0;

            rows.each(function() {
                const visible = !keyword || $(this).text().toLowerCase().indexOf(keyword) !== -1;
                $(this).toggle(visible);
                if (visible) {
                    visibleCount++;
                }
            });

            if (keyword) {
                $('#detailIcuFilterNote')
                    .removeClass('d-none')
                    .text(formatNumber(visibleCount) + ' baris cocok dengan pencarian "' + keyword + '".');
                return;
            }

            $('#detailIcuFilterNote').addClass('d-none').text('');
        }

        function openDetail(id) {
            $('#detailIcuLoading').removeClass('d-none');
            $('#detailIcuContent').addClass('d-none');
            resetDetailTabs();
            detailModal.show();

            const url = "{{ route("backOffice.keuangan.hitungPremi.generateNicu.detail", ["id" => "__ID__"]) }}"
                .replace('__ID__', id);
            $.get(url).done(function(response) {
                renderDetail(response.data || {});
                $('#detailIcuContent').removeClass('d-none');
            }).fail(function(xhr) {
                Swal.fire('Gagal', errorMessage(xhr), 'error');
                detailModal.hide();
            }).always(function() {
                $('#detailIcuLoading').addClass('d-none');
            });
        }

        function toggleLock(id, locked) {
            const url = (locked ?
                "{{ route("backOffice.keuangan.hitungPremi.generateNicu.lock", ["id" => "__ID__"]) }}" :
                "{{ route("backOffice.keuangan.hitungPremi.generateNicu.unlock", ["id" => "__ID__"]) }}"
            ).replace('__ID__', id);

            Swal.fire({
                title: locked ? 'Kunci data NICU?' : 'Buka kunci data NICU?',
                text: locked ? 'Data terkunci tidak dapat dihapus atau ditimpa generate.' :
                    'Data akan bisa diproses ulang.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: locked ? 'Kunci' : 'Buka Kunci',
                cancelButtonText: 'Batal'
            }).then(function(result) {
                if (!result.isConfirmed) {
                    return;
                }

                $.post(url).done(function(response) {
                    Swal.fire('Berhasil', response.message || 'Status data diperbarui.',
                        'success');
                    reloadTable();
                    loadSummary();
                }).fail(function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                });
            });
        }

        function deleteResult(id) {
            const url = "{{ route("backOffice.keuangan.hitungPremi.generateNicu.delete", ["id" => "__ID__"]) }}"
                .replace('__ID__', id);

            Swal.fire({
                title: 'Hapus data NICU?',
                text: 'Detail tindakan hasil generate ikut dihapus.',
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
                    Swal.fire('Berhasil', response.message || 'Data NICU dihapus.', 'success');
                    reloadTable();
                    loadSummary();
                }).fail(function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                });
            });
        }

        $('#btnPrevIcu').on('click', function() {
            movePeriod(-1);
        });
        $('#btnNextIcu').on('click', function() {
            movePeriod(1);
        });
        $('#periodeIcu').on('change', function() {
            loadSummary();
            reloadTable();
        });
        $('#btnRefreshIcu').on('click', function() {
            loadSummary();
            reloadTable();
        });
        $('.icu-summary-view').on('click', function() {
            summaryView = $(this).data('summary-view') || 'all';
            applySummaryView();
        });
        $('.icu-type-tab').on('click', function() {
            activeType = $(this).data('type');
            $('.icu-type-tab').removeClass('active');
            $(this).addClass('active');
            loadSummary();
            reloadTable();
        });
        $('#btnConfigIcu').on('click', openConfig);
        $('#btnPreviewIcu').on('click', openPreview);
        $('#detailIcuSearch').on('input', filterDetailRows);
        $('#configIcuMapping, #configPerawatIcuPercent, #configPegawaiIcuKhususPercent, #configPerawatIcuRegulerPercent, #configPerawatIcuDivider, #configPremiMedisPercent, #configPremiMedisDivider, #configPremiBersamaPercent, #configCriticalActionName, #configPerawatIcuRecipients, #configPegawaiIcuKhususRecipients')
            .on('change input', renderConfigFormula);

        $('#formConfigIcu').on('submit', function(e) {
            e.preventDefault();
            $('#btnSubmitConfigIcu').prop('disabled', true);

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateNicu.updateConfig") }}",
                method: 'PUT',
                data: {
                    jenis_nicu: activeType,
                    jnsTindakan_ids: $('#configIcuMapping').val() || [],
                    perawat_nicu_percent: $('#configPerawatIcuPercent').val(),
                    pegawai_nicu_khusus_percent: $('#configPegawaiIcuKhususPercent').val(),
                    perawat_nicu_reguler_percent: $('#configPerawatIcuRegulerPercent').val(),
                    perawat_nicu_divider: $('#configPerawatIcuDivider').val(),
                    premi_medis_percent: $('#configPremiMedisPercent').val(),
                    premi_medis_divider: $('#configPremiMedisDivider').val(),
                    premi_bersama_percent: $('#configPremiBersamaPercent').val(),
                    critical_action_name: ($('#configCriticalActionName').val() || [])[0] || '',
                    critical_action_names: $('#configCriticalActionName').val() || [],
                    recipients: {
                        perawat_nicu: $('#configPerawatIcuRecipients').val() || [],
                        pegawai_nicu_khusus: $('#configPegawaiIcuKhususRecipients').val() || []
                    }
                }
            }).done(function(response) {
                Swal.fire('Berhasil', response.message || 'Konfigurasi NICU disimpan.',
                    'success');
                configModal.hide();
            }).fail(function(xhr) {
                Swal.fire('Gagal', errorMessage(xhr), 'error');
            }).always(function() {
                $('#btnSubmitConfigIcu').prop('disabled', false);
            });
        });

        $('#formGenerateIcu').on('submit', function(e) {
            e.preventDefault();
            if (!previewReady) {
                return;
            }

            $('#btnSubmitGenerateIcu').prop('disabled', true);
            $.post("{{ route("backOffice.keuangan.hitungPremi.generateNicu.store") }}", {
                periode: $('#periodeIcu').val(),
                jenis_nicu: activeType
            }).done(function(response) {
                Swal.fire('Berhasil', response.message || 'Premi NICU berhasil digenerate.',
                    'success');
                generateModal.hide();
                reloadTable();
                loadSummary();
            }).fail(function(xhr) {
                Swal.fire('Gagal', errorMessage(xhr), 'error');
            }).always(function() {
                $('#btnSubmitGenerateIcu').prop('disabled', false);
            });
        });

        $(document).on('click', '.btn-detail-icu', function() {
            openDetail($(this).data('id'));
        });
        $(document).on('click', '.btn-lock-icu', function() {
            toggleLock($(this).data('id'), true);
        });
        $(document).on('click', '.btn-unlock-icu', function() {
            toggleLock($(this).data('id'), false);
        });
        $(document).on('click', '.btn-delete-icu', function() {
            deleteResult($(this).data('id'));
        });

        setDefaultPeriod();
        initConfigSelects();
        initTable();
        loadSummary();
    });
</script>



