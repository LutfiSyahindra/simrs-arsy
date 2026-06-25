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
        const roleConfig = {
            penerima_31: {
                label: 'Penerima 31% / 2.5',
                select: '#configApotekRecipients31',
                count: '#countApotekRecipients31',
                icon: 'mdi-account-star-outline'
            },
            penerima_7: {
                label: 'Penerima 7%',
                select: '#configApotekRecipients7',
                count: '#countApotekRecipients7',
                icon: 'mdi-account-tie-outline'
            },
            penerima_12: {
                label: 'Penerima 12% / 2',
                select: '#configApotekRecipients12',
                count: '#countApotekRecipients12',
                icon: 'mdi-account-group-outline'
            }
        };
        let activeType = 'umum';
        let previewReady = false;
        let detailApotekRows = [];
        let detailApotekFilterKeyword = '';

        const generateModal = modalInstance('modalGenerateApotek');
        const configModal = modalInstance('modalConfigApotek');
        const detailModal = modalInstance('modalDetailApotek');

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
            return formatNumber(value || 0) + '%';
        }

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        function normalizeText(value) {
            return String(value == null ? '' : value).toLowerCase().replace(/\s+/g, ' ').trim();
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
            $('#periodeApotek').val(now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
        }

        function periodFromInput() {
            const parts = String($('#periodeApotek').val() || '').split('-').map(Number);
            return parts.length === 2 && parts[0] && parts[1] ?
                new Date(parts[0], parts[1] - 1, 1) :
                new Date();
        }

        function setPeriodFromDate(date) {
            $('#periodeApotek')
                .val(date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0'))
                .trigger('change');
        }

        function movePeriod(offset) {
            const date = periodFromInput();
            date.setMonth(date.getMonth() + offset);
            setPeriodFromDate(date);
        }

        function sourcePeriodMode(mode, type) {
            if (type !== 'bpjs') {
                return 'current';
            }

            return mode === 'current' ? 'current' : 'previous';
        }

        function sourcePeriodModeLabel(mode, type) {
            return sourcePeriodMode(mode, type) === 'current' ? 'Periode Generate' : 'Bulan Sebelumnya';
        }

        function sourcePeriodFor(periode, type, mode) {
            if (type !== 'bpjs' || !periode || sourcePeriodMode(mode, type) === 'current') {
                return periode || '-';
            }

            const parts = String(periode).split('-').map(Number);
            if (parts.length !== 2 || !parts[0] || !parts[1]) {
                return periode;
            }

            const date = new Date(parts[0], parts[1] - 2, 1);
            return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
        }

        function sourceRuleText(type, periode, mode) {
            const sourceMode = sourcePeriodMode(mode, type);
            const sourcePeriode = sourcePeriodFor(periode, type, sourceMode);

            return type === 'bpjs' ?
                'Periode generate ' + periode + ' memakai detail_pemberian_obat periode ' + sourcePeriode +
                ' (' + sourcePeriodModeLabel(sourceMode, type) + ') dengan filter reg_periksa.kd_pj = BPJ.' :
                'Periode generate ' + periode + ' memakai detail_pemberian_obat periode yang sama dengan filter kd_pj selain BPJ dan -.';
        }

        function infoRowsHtml(rows) {
            return rows.map(function(row) {
                return '<div class="apotek-info-row">' +
                    '<div>' +
                    '<div class="apotek-info-label">' + escapeHtml(row.label) + '</div>' +
                    (row.note ? '<div class="apotek-info-note">' + escapeHtml(row.note) + '</div>' : '') +
                    '</div>' +
                    '<div class="apotek-info-value">' + escapeHtml(row.value) + '</div>' +
                    '</div>';
            }).join('');
        }

        function currentConfigFromForm() {
            return {
                tarif_per_item: Number($('#configTarifPerItemApotek').val() || 0),
                source_period_mode: sourcePeriodMode(
                    $('input[name="configApotekSourcePeriodMode"]:checked').val(),
                    activeType
                ),
                jasa_farmasi_percent: Number($('#configJasaFarmasiPercent').val() || 0),
                formula_31_percent: Number($('#configFormula31Percent').val() || 0),
                formula_31_divider: Number($('#configFormula31Divider').val() || 1),
                formula_7_percent: Number($('#configFormula7Percent').val() || 0),
                formula_7_divider: Number($('#configFormula7Divider').val() || 1),
                formula_12_percent: Number($('#configFormula12Percent').val() || 0),
                formula_12_divider: Number($('#configFormula12Divider').val() || 1),
                premi_bersama_percent: Number($('#configPremiBersamaApotekPercent').val() || 0)
            };
        }

        function formulaCardHtml(item) {
            const accent = item.accent ? ' ' + item.accent : '';

            return '<div class="apotek-formula-card' + accent + '">' +
                '<div class="apotek-formula-card-top">' +
                '<div class="apotek-formula-card-main">' +
                '<div class="apotek-formula-icon"><i class="mdi ' + escapeHtml(item.icon || 'mdi-function-variant') + '"></i></div>' +
                '<div class="apotek-formula-card-title">' + escapeHtml(item.label) + '</div>' +
                '</div>' +
                '</div>' +
                '<div class="mt-3">' +
                '<span class="apotek-formula-chip">' + escapeHtml(item.value) + '</span>' +
                '</div>' +
                (item.note ? '<div class="apotek-formula-note">' + escapeHtml(item.note) + '</div>' : '') +
                (item.meta ? '<div class="apotek-formula-meta">' + escapeHtml(item.meta) + '</div>' : '') +
                '</div>';
        }

        function formulaHtml(data) {
            const config = data.config || data || {};
            const source = data.source || {};
            const type = data.jenis_apotek || config.jenis_apotek || activeType;
            const periode = data.periode || $('#periodeApotek').val() || '-';
            const sourceMode = data.source_period_mode || source.source_period_mode || config.source_period_mode;
            const sourcePeriode = data.source_periode || source.source_periode || config.source_periode || sourcePeriodFor(periode, type, sourceMode);
            const mapping = source.mapping || config.mapping || {};
            const mappingLabel = mapping.label || config.mapping_label || '-';
            const cards = [
                {
                    label: 'Data Khanza',
                    value: sourcePeriode,
                    note: type === 'bpjs' ? 'BPJS mengambil data: ' + sourcePeriodModeLabel(sourceMode, type) + '.' : 'UMUM mengambil data periode aktif.',
                    meta: type === 'bpjs' ? 'kd_pj = BPJ' : 'kd_pj selain BPJ dan -',
                    icon: 'mdi-database-search-outline',
                    accent: 'dark'
                },
                {
                    label: 'Mapping Farmasi',
                    value: mappingLabel,
                    note: formatNumber(mapping.jumlah_mapping || 0) + ' kode obat dari mapping_tindakan sumber FARMASI.',
                    meta: 'Sumber mapping: FARMASI',
                    icon: 'mdi-source-branch'
                },
                {
                    label: 'Grand Total',
                    value: 'Item mapping x ' + formatRupiah(config.tarif_per_item || source.tarif_per_item || 500),
                    note: 'Setiap baris detail_pemberian_obat yang cocok mapping dihitung satu kali.',
                    meta: 'Basis seluruh formula',
                    icon: 'mdi-cash-multiple',
                    accent: 'cyan'
                },
                {
                    label: 'Pool 50%',
                    value: 'Grand total x ' + percentText(config.jasa_farmasi_percent || 50),
                    note: 'Nilai 50% dari grand total menjadi dasar pembagian 31%, 7%, dan 12%.',
                    meta: 'Basis penerima',
                    icon: 'mdi-chart-pie',
                    accent: 'dark'
                },
                {
                    label: 'Penerima 31%',
                    value: 'Pool 50% x ' + percentText(config.formula_31_percent || 31) + ' / ' + formatNumber(config.formula_31_divider || 2.5),
                    note: 'Dihitung dari nilai 50% grand total, lalu hasilnya diberikan ke pegawai yang dipilih.',
                    meta: 'Kelompok penerima 31%',
                    icon: 'mdi-account-star-outline'
                },
                {
                    label: 'Penerima 7%',
                    value: 'Pool 50% x ' + percentText(config.formula_7_percent || 7),
                    note: 'Dihitung dari nilai 50% grand total. Pembagi default 1.',
                    meta: 'Kelompok penerima 7%',
                    icon: 'mdi-account-tie-outline'
                },
                {
                    label: 'Penerima 12%',
                    value: 'Pool 50% x ' + percentText(config.formula_12_percent || 12) + ' / ' + formatNumber(config.formula_12_divider || 2),
                    note: 'Dihitung dari nilai 50% grand total, lalu hasilnya diberikan ke pegawai yang dipilih.',
                    meta: 'Kelompok penerima 12%',
                    icon: 'mdi-account-group-outline'
                },
                {
                    label: 'Premi Bersama',
                    value: percentText(config.premi_bersama_percent || 30) + ' dari grand total',
                    note: 'Disimpan sebagai total premi bersama.',
                    meta: 'Pool bersama',
                    icon: 'mdi-handshake-outline',
                    accent: 'cyan'
                }
            ];

            return '<div class="apotek-formula-head">' +
                '<div>' +
                '<div class="apotek-formula-title">Formula Aktif ' + escapeHtml(typeConfig[type] || '-') + '</div>' +
                '<div class="apotek-formula-subtitle">' + escapeHtml(sourceRuleText(type, periode, sourceMode)) + '</div>' +
                '</div>' +
                '<span class="apotek-formula-head-badge"><i class="mdi mdi-check-decagram-outline"></i> ' +
                escapeHtml(formatNumber(mapping.jumlah_mapping || 0) + ' kode obat') +
                '</span>' +
                '</div>' +
                '<div class="apotek-formula-grid">' +
                cards.map(formulaCardHtml).join('') +
                '</div>';
        }

        function miniCard(label, value, primary, icon, note) {
            return '<div class="apotek-mini-card ' + (primary ? 'total' : '') + '">' +
                '<div class="d-flex align-items-center justify-content-between gap-2">' +
                '<div class="apotek-mini-label">' + escapeHtml(label) + '</div>' +
                '<i class="mdi ' + escapeHtml(icon || 'mdi-chart-box-outline') + '"></i>' +
                '</div>' +
                '<div class="apotek-mini-value">' + escapeHtml(value) + '</div>' +
                (note ? '<div class="apotek-mini-note small mt-1">' + escapeHtml(note) + '</div>' : '') +
                '</div>';
        }

        function recipientListHtml(items, options) {
            const showTotal = !options || options.showTotal !== false;

            if (!items || !items.length) {
                return '<div class="apotek-empty-state">Belum ada pegawai dipilih.</div>';
            }

            return items.map(function(item) {
                const name = item.pegawai_name || item.text || '-';
                const roleLabel = item.role_label ? '<div class="apotek-recipient-meta">' + escapeHtml(item.role_label) + '</div>' : '';

                return '<div class="apotek-recipient-item">' +
                    '<div>' +
                    '<div class="apotek-recipient-name">' + escapeHtml(name) + '</div>' +
                    roleLabel +
                    '<div class="apotek-recipient-meta">' +
                    escapeHtml(item.pegawai_id || '-') + ' / ' + escapeHtml(item.pegawai_position || '-') +
                    (item.allocation_percent ? ' / ' + percentText(item.allocation_percent) + ' dari grand total' : '') +
                    '</div>' +
                    '</div>' +
                    '<div class="apotek-recipient-total">' +
                    (showTotal ? formatRupiah(item.total_received || 0) : '<span class="apotek-recipient-status">Dipilih</span>') +
                    '</div>' +
                    '</div>';
            }).join('');
        }

        function recipientGroupsHtml(groups) {
            if (!groups || !groups.length) {
                return '<div class="apotek-empty-state">Belum ada penerima pada konfigurasi ini.</div>';
            }

            return groups.map(function(group) {
                return '<div class="apotek-simple-section">' +
                    '<div class="apotek-simple-section-title">' + escapeHtml(group.role_label || '-') + '</div>' +
                    '<div class="apotek-simple-section-subtitle mb-2">' +
                    formatNumber(group.recipient_count || 0) + ' penerima / ' +
                    formatRupiah(group.amount_per_recipient || 0) + ' per penerima / total ' +
                    formatRupiah(group.total_received || 0) +
                    '</div>' +
                    recipientListHtml(group.items || []) +
                    '</div>';
            }).join('');
        }

        function detailsRowsHtml(details, limit) {
            const rows = (details || []).slice(0, limit || 20);

            if (!rows.length) {
                return '<tr><td colspan="7" class="text-center text-muted py-3">Belum ada detail obat yang cocok mapping.</td></tr>';
            }

            return rows.map(function(item) {
                return '<tr>' +
                    '<td>' + escapeHtml(item.tanggal || '-') + '<small class="d-block text-muted">' + escapeHtml(item.jam || '-') + '</small></td>' +
                    '<td>' + escapeHtml(item.no_rawat || '-') + '</td>' +
                    '<td>' + escapeHtml(item.nm_pasien || '-') + '<small class="d-block text-muted">' + escapeHtml(item.nama_penjamin || item.kd_pj || '-') + '</small></td>' +
                    '<td><span class="fw-semibold">' + escapeHtml(item.kode_barang || '-') + '</span></td>' +
                    '<td>' + escapeHtml(item.nama_barang || '-') + '</td>' +
                    (item.qty !== undefined ? '<td class="text-end">' + formatNumber(item.qty || 0) + '</td>' : '') +
                    '<td class="text-end fw-semibold">' + formatRupiah(item.total_premi || 0) + '</td>' +
                    '</tr>';
            }).join('');
        }

        function filteredDetailApotekRows() {
            const keyword = normalizeText(detailApotekFilterKeyword);

            if (!keyword) {
                return detailApotekRows;
            }

            return detailApotekRows.filter(function(item) {
                return normalizeText(item.nama_barang).includes(keyword) ||
                    normalizeText(item.kode_barang).includes(keyword);
            });
        }

        function groupedDetailApotekRows(rows) {
            const groups = {};

            (rows || []).forEach(function(item) {
                const nama = item.nama_barang || item.kode_barang || '-';
                const kode = item.kode_barang || '-';
                const key = normalizeText(nama);

                if (!groups[key]) {
                    groups[key] = {
                        nama: nama,
                        kode: kode,
                        codes: {},
                        row_count: 0,
                        qty: 0,
                        total_premi: 0
                    };
                }

                groups[key].codes[kode] = true;
                groups[key].row_count += 1;
                groups[key].qty += Number(item.qty || 0);
                groups[key].total_premi += Number(item.total_premi || 0);
            });

            return Object.values(groups).map(function(group) {
                const codes = Object.keys(group.codes || {});
                group.kode = codes.slice(0, 3).join(', ') + (codes.length > 3 ? ' +' + formatNumber(codes.length - 3) : '');
                return group;
            }).sort(function(a, b) {
                if (b.total_premi !== a.total_premi) {
                    return b.total_premi - a.total_premi;
                }

                if (b.qty !== a.qty) {
                    return b.qty - a.qty;
                }

                return String(a.nama).localeCompare(String(b.nama));
            });
        }

        function detailObatBreakdownHtml(groups) {
            if (!groups.length) {
                return '<div class="apotek-empty-state py-3">Tidak ada obat pada filter ini.</div>';
            }

            const visibleGroups = groups.slice(0, 15);
            const rows = visibleGroups.map(function(group) {
                return '<div class="apotek-detail-breakdown-row">' +
                    '<div>' +
                    '<div class="apotek-detail-breakdown-name">' + escapeHtml(group.nama) + '</div>' +
                    '<div class="apotek-detail-breakdown-meta">' + escapeHtml(group.kode) + ' / ' +
                    formatNumber(group.row_count) + ' baris</div>' +
                    '</div>' +
                    '<div class="apotek-detail-breakdown-metric">' +
                    '<span>Qty</span>' +
                    '<strong>' + formatNumber(group.qty) + '</strong>' +
                    '</div>' +
                    '<div class="apotek-detail-breakdown-metric">' +
                    '<span>Premi</span>' +
                    '<strong>' + formatRupiah(group.total_premi) + '</strong>' +
                    '</div>' +
                    '</div>';
            });

            if (groups.length > visibleGroups.length) {
                rows.push('<div class="apotek-detail-breakdown-more">+' +
                    formatNumber(groups.length - visibleGroups.length) +
                    ' nama obat lain pada hasil filter.</div>');
            }

            return rows.join('');
        }

        function renderDetailApotekFilter() {
            const rows = filteredDetailApotekRows();
            const groups = groupedDetailApotekRows(rows);
            const totalQty = rows.reduce(function(total, item) {
                return total + Number(item.qty || 0);
            }, 0);
            const totalPremi = rows.reduce(function(total, item) {
                return total + Number(item.total_premi || 0);
            }, 0);
            const tableLimit = 250;
            const visibleRows = Math.min(rows.length, tableLimit);
            const subtitle = detailApotekFilterKeyword ?
                formatNumber(groups.length) + ' nama obat / ' + formatNumber(rows.length) +
                ' baris cocok filter "' + detailApotekFilterKeyword + '".' :
                formatNumber(groups.length) + ' nama obat / ' + formatNumber(rows.length) +
                ' baris detail masuk mapping.';

            $('#detailFilterJumlahNamaObat').text(formatNumber(groups.length));
            $('#detailFilterJumlahBarisObat').text(formatNumber(rows.length));
            $('#detailFilterTotalQtyObat').text(formatNumber(totalQty));
            $('#detailFilterTotalPremiObat').text(formatRupiah(totalPremi));
            $('#detailApotekFilterSubtitle').text(
                subtitle + (rows.length > tableLimit ? ' Tabel menampilkan ' + formatNumber(visibleRows) + ' baris pertama.' : '')
            );
            $('#detailApotekObatBreakdown').html(detailObatBreakdownHtml(groups));
            $('#detailApotekDetailRows').html(detailsRowsHtml(rows, tableLimit));
        }

        function initSelect2() {
            if (!$.fn.select2) {
                return;
            }

            $('#configApotekMappings').select2({
                dropdownParent: $('#modalConfigApotek'),
                width: '100%',
                placeholder: 'Pilih mapping Farmasi',
                ajax: {
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateApotek.mappingOptions") }}",
                    dataType: 'json',
                    delay: 250,
                    data: params => ({ q: params.term || '' }),
                    processResults: response => ({
                        results: (response.data || []).map(item => ({ id: item.id, text: item.text }))
                    })
                }
            });

            Object.values(roleConfig).forEach(function(role) {
                $(role.select).select2({
                    dropdownParent: $('#modalConfigApotek'),
                    width: '100%',
                    placeholder: 'Pilih pegawai',
                    ajax: {
                        url: "{{ route("backOffice.keuangan.hitungPremi.generateApotek.pegawaiOptions") }}",
                        dataType: 'json',
                        delay: 250,
                        data: params => ({ q: params.term || '' }),
                        processResults: response => ({
                            results: (response.data || []).map(item => ({ id: item.id, text: item.text }))
                        })
                    }
                });
            });
        }

        function fillSelect(selector, items) {
            const select = $(selector);
            select.empty();
            (items || []).forEach(function(item) {
                const value = item.pegawai_id || item.id;
                const text = item.text || item.label || (item.kode ? item.kode + ' - ' + item.jenis : value + ' - ' + item.pegawai_name);
                select.append(new Option(text, value, true, true));
            });
            select.trigger('change');
        }

        function selectedItemsFromSelect(selector, role) {
            return $(selector + ' option:selected').map(function() {
                const text = $(this).text();
                const parts = text.split(' - ');

                return {
                    role: role,
                    role_label: roleConfig[role] ? roleConfig[role].label : '',
                    pegawai_id: this.value,
                    pegawai_name: parts.length > 1 ? parts.slice(1).join(' - ') : text,
                    pegawai_position: 'Penerima aktif',
                    total_received: 0
                };
            }).get();
        }

        function updateRecipientCounter() {
            Object.entries(roleConfig).forEach(function([role, config]) {
                const count = ($(config.select).val() || []).length;
                $(config.count).text(formatNumber(count) + ' penerima dipilih');
            });
        }

        function configRecipientItems() {
            return Object.entries(roleConfig).flatMap(function([role, config]) {
                return selectedItemsFromSelect(config.select, role);
            });
        }

        function updateConfigOverview() {
            const config = currentConfigFromForm();
            const mappings = ($('#configApotekMappings').val() || []).length;
            const recipients = configRecipientItems();
            const periode = $('#periodeApotek').val() || '-';
            const sourcePeriode = sourcePeriodFor(periode, activeType, config.source_period_mode);

            $('#configApotekRuleTitle').text('Konfigurasi ' + (typeConfig[activeType] || '-'));
            $('#configApotekRuleText').text(sourceRuleText(activeType, periode, config.source_period_mode));
            $('#configApotekTypeBadge').text(typeConfig[activeType] || '-');
            $('#configApotekBpjsSourceSection').toggleClass('d-none', activeType !== 'bpjs');
            $('#configApotekFormulaPreview').html(infoRowsHtml([
                {
                    label: 'Periode Data Khanza',
                    value: sourcePeriode,
                    note: activeType === 'bpjs' ? 'BPJS memakai data: ' + sourcePeriodModeLabel(config.source_period_mode, activeType) + '.' : 'UMUM memakai data periode aktif.'
                },
                {
                    label: 'Mapping Farmasi',
                    value: formatNumber(mappings) + ' mapping dipilih',
                    note: 'Kode barang obat akan dicocokkan dengan mapping_tindakan sumber FARMASI.'
                },
                {
                    label: 'Nominal per Data',
                    value: formatRupiah(config.tarif_per_item),
                    note: 'Satu baris detail obat yang cocok mapping dihitung satu kali.'
                },
                {
                    label: 'Pool Formula',
                    value: 'Grand total x ' + percentText(config.jasa_farmasi_percent),
                    note: 'Nilai 50% ini menjadi dasar untuk formula 31%, 7%, dan 12%.'
                },
                {
                    label: 'Penerima 31%',
                    value: 'Pool 50% x ' + percentText(config.formula_31_percent) + ' / ' + formatNumber(config.formula_31_divider),
                    note: 'Nilai diberikan ke pegawai pada kelompok 31%.'
                },
                {
                    label: 'Penerima 7%',
                    value: 'Pool 50% x ' + percentText(config.formula_7_percent),
                    note: 'Nilai diberikan ke pegawai pada kelompok 7%.'
                },
                {
                    label: 'Penerima 12%',
                    value: 'Pool 50% x ' + percentText(config.formula_12_percent) + ' / ' + formatNumber(config.formula_12_divider),
                    note: 'Nilai diberikan ke pegawai pada kelompok 12%.'
                },
                {
                    label: 'Premi Bersama',
                    value: percentText(config.premi_bersama_percent),
                    note: 'Dihitung dari grand total.'
                }
            ]));
            $('#configApotekMappingPreview').html(
                mappings ?
                '<div class="apotek-quality ok mb-0"><strong>Mapping dipilih.</strong> Pastikan rincian mapping berisi kode obat Farmasi.</div>' :
                '<div class="apotek-empty-state">Belum ada mapping dipilih. Pilih mapping Farmasi sebelum generate.</div>'
            );
            $('#configApotekRecipientPreview').html(
                recipients.length ?
                recipientListHtml(recipients, { showTotal: false }) :
                '<div class="apotek-empty-state">Belum ada pegawai dipilih. Isi ketiga kelompok penerima sesuai formula.</div>'
            );
            updateRecipientCounter();
        }

        function setActiveType(type) {
            activeType = type;
            $('.apotek-type-tab').removeClass('active');
            $('.apotek-type-tab[data-type="' + type + '"]').addClass('active');
            $('#resultApotekTitle').text('Hasil Generate Apotek ' + typeConfig[type]);
        }

        function loadFormulaConfig() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateApotek.config") }}",
                data: { jenis_apotek: activeType },
                success: function(response) {
                    $('#formulaApotekStrip').html(formulaHtml(response.data || {}));
                }
            });
        }

        function loadSummary() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateApotek.summary") }}",
                data: {
                    periode: $('#periodeApotek').val(),
                    jenis_apotek: activeType
                },
                success: function(response) {
                    const data = response.data || {};
                    const sourceCount = Number(data.jumlah_data_sumber || 0);
                    const mappedCount = Number(data.jumlah_data_mapping || 0);
                    const matchRate = sourceCount > 0 ? Math.round(mappedCount * 100 / sourceCount) : 0;

                    $('#summaryItemApotek').text(formatNumber(data.jumlah_data_mapping || 0));
                    $('#summaryPasienApotek').text(formatNumber(data.jumlah_pasien || 0));
                    $('#summaryGrandApotek').text(formatRupiah(data.grand_total || 0));
                    $('#summaryDibagikanApotek').text(formatRupiah(data.total_dibagikan || 0));
                    $('#summaryBersamaApotek').text(formatRupiah(data.total_premi_bersama || 0));
                    $('#summaryLockedApotek').text(formatNumber(data.locked_count || 0));
                    $('#summarySourceApotek').text(formatNumber(data.jumlah_data_sumber || 0));
                    $('#summaryObatApotek').text(formatNumber(data.jumlah_obat_sumber || 0));
                    $('#summaryMatchRateApotek').text(formatNumber(matchRate) + '%');
                    $('#summaryGenerateCountApotek').text(formatNumber(data.generated_count || 0) + ' data generate');
                    $('#summaryTypeBadgeApotek').html(
                        '<i class="mdi ' + (activeType === 'bpjs' ? 'mdi-shield-account-outline' : 'mdi-account-cash-outline') + '"></i> ' +
                        escapeHtml(data.jenis_apotek_label || typeConfig[activeType])
                    );
                    $('#summaryGrandNoteApotek').text(
                        formatNumber(data.jumlah_data_mapping || 0) + ' item mapping dari ' +
                        formatNumber(data.jumlah_data_sumber || 0) + ' data sumber / Pool 50%: ' +
                        formatRupiah(data.total_jasa_farmasi_pool || 0) + '.'
                    );
                    $('#summaryApotekSubtitle').text(
                        'Jenis ' + (data.jenis_apotek_label || typeConfig[activeType]) +
                        ' / ' + formatNumber(data.generated_count || 0) + ' data generate / ' +
                        sourceRuleText(activeType, $('#periodeApotek').val(), data.source_period_mode)
                    );
                },
                error: function() {
                    $('#summaryItemApotek, #summaryPasienApotek, #summaryLockedApotek, #summarySourceApotek, #summaryObatApotek').text('0');
                    $('#summaryGrandApotek, #summaryDibagikanApotek, #summaryBersamaApotek').text('Rp 0');
                    $('#summaryMatchRateApotek').text('0%');
                    $('#summaryGenerateCountApotek').text('0 data generate');
                    $('#summaryGrandNoteApotek').text('0 item mapping x nominal aktif.');
                }
            });
        }

        setDefaultPeriod();
        initSelect2();

        const table = $('#tableGenerateApotek').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            lengthChange: false,
            pageLength: 10,
            order: [],
            ajax: {
                url: "{{ route("backOffice.keuangan.hitungPremi.generateApotek.table") }}",
                data: function(data) {
                    data.periode = $('#periodeApotek').val();
                    data.jenis_apotek = activeType;
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'periode' },
                {
                    data: 'source_periode',
                    render: function(data, type, row) {
                        const source = data || sourcePeriodFor(row.periode, row.jenis_apotek, row.source_period_mode);
                        const note = row.jenis_apotek === 'bpjs' ?
                            sourcePeriodModeLabel(row.source_period_mode, row.jenis_apotek) :
                            'Data periode aktif';
                        return '<span class="fw-semibold">' + escapeHtml(source) + '</span>' +
                            '<small class="d-block text-muted">' + escapeHtml(note) + '</small>';
                    }
                },
                {
                    data: 'jenis_apotek_label',
                    render: function(data, type, row) {
                        return '<span class="apotek-badge ' + escapeHtml(row.jenis_apotek) + '">' +
                            escapeHtml(data) + '</span>';
                    }
                },
                {
                    data: 'mapping_label',
                    render: function(data) {
                        return '<span class="fw-semibold">' + escapeHtml(data || '-') + '</span>';
                    }
                },
                { data: 'jumlah_data_mapping', className: 'text-center', render: formatNumber },
                { data: 'grand_total', className: 'text-end', render: formatRupiah },
                { data: 'total_premi_bersama', className: 'text-end', render: formatRupiah },
                { data: 'total_dibagikan', className: 'text-end', render: formatRupiah },
                { data: 'recipients_count', className: 'text-center', render: formatNumber },
                {
                    data: 'is_locked',
                    render: function(data, type, row) {
                        if (data) {
                            return '<span class="apotek-lock locked" title="' +
                                escapeHtml([row.locked_by_name, row.locked_at].filter(Boolean).join(' / ')) +
                                '"><i class="mdi mdi-lock"></i>Terkunci</span>';
                        }
                        return '<span class="apotek-lock open"><i class="mdi mdi-lock-open-variant-outline"></i>Terbuka</span>';
                    }
                },
                { data: 'actions', orderable: false, searchable: false, className: 'text-center' }
            ],
            language: {
                processing: 'Memuat data Apotek...',
                emptyTable: 'Belum ada hasil generate Apotek.',
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
            const groups = data.recipient_groups || [];
            const warnings = data.warnings || {};
            const periode = $('#periodeApotek').val() || '-';
            const type = data.jenis_apotek || activeType;
            const sourceMode = data.source_period_mode || source.source_period_mode || data.config?.source_period_mode;
            const sourcePeriode = data.source_periode || source.source_periode || sourcePeriodFor(periode, type, sourceMode);

            $('#generateApotekTitle').text(
                'Preview ' + (data.jenis_apotek_label || typeConfig[type] || '-') + ' / Generate ' + periode
            );
            $('#generateApotekText').text(
                'Data Khanza ' + sourcePeriode + ' / ' +
                (type === 'bpjs' ? sourcePeriodModeLabel(sourceMode, type) + ' / kd_pj BPJ' : 'kd_pj selain BPJ dan -')
            );
            $('#generateApotekTypeBadge').text(data.jenis_apotek_label || typeConfig[type] || '-');
            $('#previewApotekStats').html([
                miniCard('Data Sumber', formatNumber(source.jumlah_data_sumber || 0), false, 'mdi-database-search-outline', formatNumber(source.jumlah_pasien_sumber || 0) + ' pasien'),
                miniCard('Item Mapping', formatNumber(source.jumlah_data_mapping || 0), false, 'mdi-format-list-numbered', formatNumber(source.jumlah_obat || 0) + ' kode obat'),
                miniCard('Tarif per Item', formatRupiah(source.tarif_per_item || 0), false, 'mdi-cash', 'Setiap baris cocok mapping'),
                miniCard('Grand Total', formatRupiah(source.grand_total || 0), true, 'mdi-cash-multiple', 'Item mapping x tarif'),
                miniCard('Pool 50%', formatRupiah(pools.formula_base_total || pools.jasa_farmasi_pool || 0), false, 'mdi-chart-pie', 'Dasar formula penerima'),
                miniCard('Premi Bersama', formatRupiah(pools.premi_bersama || 0), false, 'mdi-account-group-outline', '30% default dari grand total'),
                miniCard('Total Dibagikan', formatRupiah(data.total_dibagikan || 0), true, 'mdi-bank-transfer-out', formatNumber((data.recipients || []).length) + ' penerima')
            ].join(''));
            $('#previewApotekFormula').html(formulaHtml(data));
            $('#previewApotekRecipientSubtitle').text(
                formatNumber((data.recipients || []).length) + ' penerima dari tiga kelompok formula.'
            );
            $('#previewApotekRecipients').html(recipientGroupsHtml(groups));
            $('#previewApotekQuality').html(
                (warnings.blocking && warnings.blocking.length) ?
                '<div class="apotek-quality warn"><strong>Belum siap generate.</strong> ' + escapeHtml(warnings.blocking.join(' ')) + '</div>' :
                '<div class="apotek-quality ok"><strong>Siap generate.</strong> ' + escapeHtml((warnings.info || []).join(' ') || 'Klik Generate dan Simpan untuk menyimpan hasil periode ini.') + '</div>'
            );
            $('#previewApotekDetailRows').html(detailsRowsHtml(source.details || [], 20));
            $('#previewApotekLoading').addClass('d-none');
            $('#previewApotekContent').removeClass('d-none');
            previewReady = true;
            $('#btnSubmitGenerateApotek').prop('disabled', data.can_generate === false);
        }

        function openGenerateModal() {
            previewReady = false;
            $('#btnSubmitGenerateApotek').prop('disabled', true);
            $('#previewApotekLoading').removeClass('d-none');
            $('#previewApotekContent').addClass('d-none');
            generateModal.show();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateApotek.preview") }}",
                data: {
                    periode: $('#periodeApotek').val(),
                    jenis_apotek: activeType
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
                url: "{{ route("backOffice.keuangan.hitungPremi.generateApotek.config") }}",
                data: { jenis_apotek: activeType },
                success: function(response) {
                    const data = response.data || {};
                    const recipients = data.recipients || {};
                    $('#modalConfigApotekLabel').text('Konfigurasi Premi Apotek ' + typeConfig[activeType]);
                    $('#jenisConfigApotek').val(activeType);
                    $('#configTarifPerItemApotek').val(data.tarif_per_item || 500);
                    $('input[name="configApotekSourcePeriodMode"][value="' +
                        sourcePeriodMode(data.source_period_mode, activeType) +
                        '"]').prop('checked', true);
                    $('#configJasaFarmasiPercent').val(data.jasa_farmasi_percent ?? 50);
                    $('#configFormula31Percent').val(data.formula_31_percent ?? 31);
                    $('#configFormula31Divider').val(data.formula_31_divider ?? 2.5);
                    $('#configFormula7Percent').val(data.formula_7_percent ?? 7);
                    $('#configFormula7Divider').val(data.formula_7_divider ?? 1);
                    $('#configFormula12Percent').val(data.formula_12_percent ?? 12);
                    $('#configFormula12Divider').val(data.formula_12_divider ?? 2);
                    $('#configPremiBersamaApotekPercent').val(data.premi_bersama_percent ?? 30);
                    fillSelect('#configApotekMappings', data.mapping_items || []);
                    fillSelect('#configApotekRecipients31', recipients.penerima_31 || []);
                    fillSelect('#configApotekRecipients7', recipients.penerima_7 || []);
                    fillSelect('#configApotekRecipients12', recipients.penerima_12 || []);
                    updateConfigOverview();
                    configModal.show();
                },
                error: function(xhr) {
                    Swal.fire('Gagal', errorMessage(xhr), 'error');
                }
            });
        }

        function configPayload() {
            return {
                jenis_apotek: $('#jenisConfigApotek').val(),
                jnsTindakan_ids: $('#configApotekMappings').val() || [],
                tarif_per_item: $('#configTarifPerItemApotek').val(),
                source_period_mode: sourcePeriodMode(
                    $('input[name="configApotekSourcePeriodMode"]:checked').val(),
                    $('#jenisConfigApotek').val()
                ),
                jasa_farmasi_percent: $('#configJasaFarmasiPercent').val(),
                formula_31_percent: $('#configFormula31Percent').val(),
                formula_31_divider: $('#configFormula31Divider').val(),
                formula_7_percent: $('#configFormula7Percent').val(),
                formula_7_divider: $('#configFormula7Divider').val(),
                formula_12_percent: $('#configFormula12Percent').val(),
                formula_12_divider: $('#configFormula12Divider').val(),
                premi_bersama_percent: $('#configPremiBersamaApotekPercent').val(),
                recipients: {
                    penerima_31: $('#configApotekRecipients31').val() || [],
                    penerima_7: $('#configApotekRecipients7').val() || [],
                    penerima_12: $('#configApotekRecipients12').val() || []
                }
            };
        }

        function renderDetail(data) {
            const source = data.source || {};
            const pools = data.pools || {};
            const config = data.config_snapshot || {};
            const groups = data.recipient_groups || [];
            const type = data.jenis_apotek || activeType;
            const sourceMode = data.source_period_mode || source.source_period_mode || config.source_period_mode;
            const sourcePeriode = data.source_periode || config.source_periode || sourcePeriodFor(data.periode, type, sourceMode);

            $('#detailApotekTitle').text((data.periode || '-') + ' / ' + (data.jenis_apotek_label || '-'));
            $('#detailApotekMeta').text(
                'Generated oleh ' + (data.generate_by_name || '-') + ' pada ' + (data.generated_at || '-') +
                ' / Status: ' + (data.is_locked ? 'Terkunci' : 'Terbuka')
            );
            $('#detailApotekSourceMeta').text(
                'Periode data Khanza: ' + sourcePeriode + ' / ' +
                (type === 'bpjs' ? 'BPJS memakai ' + sourcePeriodModeLabel(sourceMode, type) : 'UMUM memakai periode aktif')
            );
            $('#detailApotekStatusBadge').html(data.is_locked ? 'Terkunci' : 'Terbuka');
            $('#detailApotekStats').html([
                miniCard('Data Sumber', formatNumber(source.jumlah_data_sumber || 0), false, 'mdi-database-search-outline', formatNumber(source.jumlah_pasien_sumber || 0) + ' pasien'),
                miniCard('Item Mapping', formatNumber(source.jumlah_data_mapping || 0), false, 'mdi-format-list-numbered', formatNumber(source.jumlah_obat || 0) + ' kode obat'),
                miniCard('Tarif per Item', formatRupiah(source.tarif_per_item || 0), false, 'mdi-cash', 'Nominal per data obat'),
                miniCard('Grand Total', formatRupiah(source.grand_total || data.grand_total || 0), true, 'mdi-cash-multiple', 'Item mapping x tarif'),
                miniCard('Pool 50%', formatRupiah(pools.formula_base_total || pools.jasa_farmasi_pool || 0), false, 'mdi-chart-pie', 'Dasar formula penerima'),
                miniCard('Premi Bersama', formatRupiah(pools.premi_bersama || 0), false, 'mdi-account-group-outline', 'Untuk premi bersama'),
                miniCard('Total Dibagikan', formatRupiah(data.total_dibagikan || 0), true, 'mdi-bank-transfer-out', formatNumber(data.recipients_count || 0) + ' penerima')
            ].join(''));
            $('#detailApotekFormula').html(formulaHtml({
                jenis_apotek: type,
                source_periode: sourcePeriode,
                source: source,
                config: config
            }));
            $('#detailApotekRecipientSubtitle').text(
                formatNumber(data.recipients_count || 0) + ' penerima dari tiga kelompok formula.'
            );
            $('#detailApotekRecipients').html(recipientGroupsHtml(groups));
            detailApotekRows = data.details || [];
            detailApotekFilterKeyword = '';
            $('#filterDetailApotekObat').val('');
            renderDetailApotekFilter();
        }

        function openDetailModal(id) {
            const template =
                "{{ route("backOffice.keuangan.hitungPremi.generateApotek.detail", ["id" => "__ID__"]) }}";

            $('#detailApotekLoading').removeClass('d-none');
            $('#detailApotekContent').addClass('d-none');
            detailModal.show();

            $.ajax({
                url: template.replace('__ID__', id),
                success: function(response) {
                    renderDetail(response.data || {});
                    $('#detailApotekLoading').addClass('d-none');
                    $('#detailApotekContent').removeClass('d-none');
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
                "{{ route("backOffice.keuangan.hitungPremi.generateApotek.lock", ["id" => "__ID__"]) }}" :
                "{{ route("backOffice.keuangan.hitungPremi.generateApotek.unlock", ["id" => "__ID__"]) }}";

            Swal.fire({
                icon: isLock ? 'warning' : 'question',
                title: isLock ? 'Kunci data Apotek?' : 'Buka kunci data Apotek?',
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
                "{{ route("backOffice.keuangan.hitungPremi.generateApotek.delete", ["id" => "__ID__"]) }}";

            Swal.fire({
                icon: 'warning',
                title: 'Hapus data Apotek?',
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

        $('#periodeApotek').on('change', refreshAll);
        $('#btnPrevPeriodApotek').on('click', () => movePeriod(-1));
        $('#btnNextPeriodApotek').on('click', () => movePeriod(1));
        $('#btnCurrentPeriodApotek').on('click', function() {
            const now = new Date();
            setPeriodFromDate(new Date(now.getFullYear(), now.getMonth(), 1));
        });
        $('#btnRefreshApotek').on('click', refreshAll);
        $('#btnOpenGenerateApotek').on('click', openGenerateModal);
        $('#btnOpenConfigApotek').on('click', openConfigModal);
        $('#searchGenerateApotek').on('input', function() {
            table.search(this.value).draw();
        });
        $('.apotek-type-tab').on('click', function() {
            setActiveType($(this).data('type'));
            refreshAll();
        });
        $('#formConfigApotek select, #formConfigApotek input').on('input change', updateConfigOverview);

        $('#formConfigApotek').on('submit', function(event) {
            event.preventDefault();
            const button = $('#btnSubmitConfigApotek');
            const originalHtml = button.html();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateApotek.updateConfig") }}",
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

        $('#formGenerateApotek').on('submit', function(event) {
            event.preventDefault();

            if (!previewReady) {
                openGenerateModal();
                return;
            }

            const button = $('#btnSubmitGenerateApotek');
            const originalHtml = button.html();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateApotek.store") }}",
                method: 'POST',
                data: {
                    periode: $('#periodeApotek').val(),
                    jenis_apotek: activeType
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

        $('#filterDetailApotekObat').on('input', function() {
            detailApotekFilterKeyword = $(this).val() || '';
            renderDetailApotekFilter();
        });

        $('#btnClearFilterDetailApotek').on('click', function() {
            detailApotekFilterKeyword = '';
            $('#filterDetailApotekObat').val('');
            renderDetailApotekFilter();
            $('#filterDetailApotekObat').trigger('focus');
        });

        $('#tableGenerateApotek').on('click', '.btn-detail-apotek', function() {
            openDetailModal($(this).data('id'));
        });

        $('#tableGenerateApotek').on('click', '.btn-lock-apotek', function() {
            changeLock($(this).data('id'), 'lock');
        });

        $('#tableGenerateApotek').on('click', '.btn-unlock-apotek', function() {
            changeLock($(this).data('id'), 'unlock');
        });

        $('#tableGenerateApotek').on('click', '.btn-delete-apotek', function() {
            deleteResult($(this).data('id'));
        });

        refreshAll();
    });
</script>
