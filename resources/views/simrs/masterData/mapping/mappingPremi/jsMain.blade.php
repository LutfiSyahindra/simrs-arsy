<script>
    $(function() {
        const modal = $('#premiModal');
        const container = $('#premiMappingContainer');
        const storeUrl = "{{ route("masterData.mapping.mappingPremi.store") }}";
        const byPremiUrl = "{{ route("masterData.mapping.mappingPremi.byPremi", ":id") }}";
        const pegawaiByPremiUrl = "{{ route("masterData.mapping.mappingPremi.byPremi.pegawai", ":id") }}";
        const updatePegawaiUrl = "{{ route("masterData.mapping.mappingPremi.updatePegawai", ":id") }}";
        const updatePembagiUrl = "{{ route("masterData.mapping.mappingPremi.updatePembagi", ":id") }}";
        const updateUrl = "{{ route("masterData.mapping.mappingPremi.update", ":id") }}";
        const deleteUrl = "{{ route("masterData.mapping.mappingPremi.delete", ":id") }}";
        let tindakanList = [];
        let premiList = [];
        let pegawaiList = [];
        let tindakanRequest = null;
        let pegawaiRequest = null;
        let rowIndex = 0;

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function routeWithId(url, id) {
            return url.replace(':id', encodeURIComponent(id));
        }

        function tindakanOptions(selectedId = '') {
            let options = '<option value="">-- Pilih Jenis Tindakan --</option>';

            tindakanList.forEach(function(item) {
                const selected = String(item.id) === String(selectedId) ? 'selected' : '';
                options += `
                    <option value="${item.id}" ${selected}>
                        ${escapeHtml(item.kode)} - ${escapeHtml(item.jenis)}
                    </option>
                `;
            });

            return options;
        }

        function jenisOptions(selectedJenis = 'persen') {
            return `
                <option value="persen" ${selectedJenis === 'persen' ? 'selected' : ''}>Persen</option>
                <option value="nominal" ${selectedJenis === 'nominal' ? 'selected' : ''}>Nominal</option>
            `;
        }

        function normalizeText(value) {
            return String(value ?? '').trim().toLowerCase();
        }

        function employeeStatusLabel(status) {
            const code = String(status ?? '').trim().toUpperCase();
            const labels = {
                T: 'Tetap',
                FT: 'Kontrak',
                MT: 'Mitra'
            };

            return labels[code] || String(status || '-').trim();
        }

        function employeeInitials(name) {
            const parts = String(name || 'P')
                .trim()
                .split(/\s+/)
                .filter(Boolean);

            return (parts.slice(0, 2).map(part => part.charAt(0)).join('') || 'P').toUpperCase();
        }

        function statusBadgeClass(status) {
            const normalized = normalizeText(employeeStatusLabel(status));

            if (normalized.includes('tetap')) return 'tetap';
            if (normalized.includes('kontrak')) return 'kontrak';
            // if (normalized.includes('casual')) return 'casual';
            if (normalized.includes('mitra')) return 'mitra';

            return 'netral';
        }

        function employeeStatusOptions(items, selectedStatus = '') {
            const statuses = [...new Set(
                (items || [])
                    .map(item => employeeStatusLabel(item.stts_kerja))
                    .filter(Boolean)
            )].sort((a, b) => a.localeCompare(b, 'id'));
            let options = '<option value="">Semua status kerja</option>';

            statuses.forEach(function(status) {
                const value = normalizeText(status);
                options += `
                    <option value="${escapeHtml(value)}" ${value === selectedStatus ? 'selected' : ''}>
                        ${escapeHtml(status)}
                    </option>
                `;
            });

            return options;
        }

        function employeeSearchText(item) {
            return normalizeText([
                item.nik,
                item.nama,
                item.jbtn,
                employeeStatusLabel(item.stts_kerja)
            ].join(' '));
        }

        function employeeIdentity(item) {
            return `
                <div class="premi-employee-avatar">${escapeHtml(employeeInitials(item.nama))}</div>
                <div class="premi-employee-info">
                    <div class="premi-employee-name" title="${escapeHtml(item.nama)}">
                        ${escapeHtml(item.nama)}
                    </div>
                    <div class="premi-employee-nik">
                        <i class="mdi mdi-card-account-details-outline"></i>
                        ${escapeHtml(item.nik)}
                    </div>
                    <div class="premi-employee-meta">
                        <span class="premi-employee-job" title="${escapeHtml(item.jbtn || '-')}">
                            <i class="mdi mdi-briefcase-outline"></i>
                            ${escapeHtml(item.jbtn || '-')}
                        </span>
                        <span class="premi-status-badge ${statusBadgeClass(item.stts_kerja)}">
                            ${escapeHtml(employeeStatusLabel(item.stts_kerja))}
                        </span>
                    </div>
                </div>
            `;
        }

        function formatRupiah(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(value) || 0);
        }

        function formatMappingValue(item, field, jenisField) {
            const jenis = item[jenisField] || item.jenis || 'persen';

            return jenis === 'nominal'
                ? formatRupiah(item[field])
                : `${Number(item[field]) || 0}%`;
        }

        function applyValueMode(scope, payer = null) {
            const payers = payer ? [payer] : ['umum', 'bpjs'];

            payers.forEach(function(item) {
                const jenis = scope.find(`.mapping-jenis-${item}, .inline-jenis-${item}`).val() || 'persen';
                const input = scope.find(`.mapping-nilai-${item}, .mapping-bersama-${item}, .inline-nilai-${item}, .inline-bersama-${item}`);
                const addon = scope.find(`.mapping-value-addon-${item}, .inline-value-addon-${item}`);
                const help = scope.find(`.inline-value-help-${item}`);

                input.attr('min', 0);

                if (jenis === 'nominal') {
                    input.attr('max', 2147483647).attr('placeholder', '0');
                    addon.text('Rp');
                    help.text('Masukkan nominal dalam Rupiah.');
                    return;
                }

                input.attr('max', 100).attr('placeholder', '0');
                addon.text('%');
                help.text('Persentase yang diizinkan adalah 0 sampai 100.');
            });
        }

        function resetValidation() {
            modal.find('.is-invalid').removeClass('is-invalid');
            modal.find('.invalid-feedback').text('');
        }

        function appendRow(data = {}) {
            const index = rowIndex++;
            const row = $(`
                <div class="card border premi-form-row">
                    <div class="card-body py-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-3">
                                <label class="form-label">Jenis Tindakan</label>
                                <select name="mappings[${index}][jnsTindakan_id]"
                                    class="form-select mapping-tindakan-select">
                                    ${tindakanOptions(data.jnsTindakan_id)}
                                </select>
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label">Jenis UMUM</label>
                                <select name="mappings[${index}][jenis_umum]" class="form-select mapping-jenis-umum">
                                    ${jenisOptions(data.jenis_umum || data.jenis || 'persen')}
                                </select>
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label">Nilai UMUM</label>
                                <div class="input-group">
                                    <span class="input-group-text mapping-value-addon-umum">%</span>
                                    <input type="number" name="mappings[${index}][nilai_umum]"
                                        class="form-control mapping-nilai mapping-nilai-umum" min="0" step="1"
                                        value="${escapeHtml(data.nilai_umum ?? '')}" placeholder="0">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label">Bersama UMUM</label>
                                <div class="input-group">
                                    <span class="input-group-text mapping-value-addon-umum">%</span>
                                    <input type="number" name="mappings[${index}][nilai_bersama_umum]"
                                        class="form-control mapping-bersama mapping-bersama-umum" min="0" step="1"
                                        value="${escapeHtml(data.nilai_bersama_umum ?? 0)}" placeholder="0">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label">Jenis BPJS</label>
                                <select name="mappings[${index}][jenis_bpjs]" class="form-select mapping-jenis-bpjs">
                                    ${jenisOptions(data.jenis_bpjs || data.jenis || 'persen')}
                                </select>
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label">Nilai BPJS</label>
                                <div class="input-group">
                                    <span class="input-group-text mapping-value-addon-bpjs">%</span>
                                    <input type="number" name="mappings[${index}][nilai_bpjs]"
                                        class="form-control mapping-nilai mapping-nilai-bpjs" min="0" step="1"
                                        value="${escapeHtml(data.nilai_bpjs ?? '')}" placeholder="0">
                                </div>
                            </div>
                            <div class="col-lg-2">
                                <label class="form-label">Bersama BPJS</label>
                                <div class="input-group">
                                    <span class="input-group-text mapping-value-addon-bpjs">%</span>
                                    <input type="number" name="mappings[${index}][nilai_bersama_bpjs]"
                                        class="form-control mapping-bersama mapping-bersama-bpjs" min="0" step="1"
                                        value="${escapeHtml(data.nilai_bersama_bpjs ?? 0)}" placeholder="0">
                                </div>
                            </div>
                            <div class="col-lg-1 text-end">
                                <button type="button" class="btn btn-light btn-icon remove-premi-row" title="Hapus baris">
                                    <i class="mdi mdi-close"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            container.append(row);
            row.find('.mapping-tindakan-select').select2({
                dropdownParent: modal,
                width: '100%',
                placeholder: 'Pilih Jenis Tindakan',
                allowClear: true
            });
            applyValueMode(row);
        }

        function resetModal() {
            $('#premiForm')[0].reset();
            container.empty();
            rowIndex = 0;
            resetValidation();
            appendRow();
            syncSelectedPremiPembagi();
        }

        function showToast(icon, title) {
            Swal.fire({
                icon: icon,
                title: title,
                toast: true,
                position: 'top-end',
                timer: 2200,
                showConfirmButton: false
            });
        }

        function loadGuides() {
            const premiRequest = premiList.length ? $.Deferred().resolve().promise() : $.get(
                "{{ route("masterData.mapping.mappingPremi.guideJenisPremi") }}",
                function(response) {
                    premiList = response || [];
                }
            );

            return $.when(premiRequest, loadTindakanGuide());
        }

        function loadTindakanGuide() {
            if (tindakanList.length) {
                return $.Deferred().resolve(tindakanList).promise();
            }

            if (tindakanRequest) {
                return tindakanRequest;
            }

            tindakanRequest = $.get(
                "{{ route("masterData.mapping.mappingPremi.guideJenisTindakan") }}",
                function(response) {
                    tindakanList = response || [];
                }
            ).always(function() {
                tindakanRequest = null;
            });

            return tindakanRequest;
        }

        function loadPegawaiGuide() {
            if (pegawaiList.length) {
                return $.Deferred().resolve(pegawaiList).promise();
            }

            if (pegawaiRequest) {
                return pegawaiRequest;
            }

            pegawaiRequest = $.get(
                "{{ route("masterData.mapping.mappingPremi.guidePegawai") }}",
                function(response) {
                    pegawaiList = response || [];
                }
            ).always(function() {
                pegawaiRequest = null;
            });

            return pegawaiRequest;
        }

        function fillPremiSelect() {
            const select = $('#jenisPremiSelect');
            const current = select.val();
            let options = '<option value="">-- Pilih Jenis Premi --</option>';

            premiList.forEach(function(item) {
                options += `<option value="${item.id}" data-pembagi="${Number(item.pembagi) || 1}">
                    ${escapeHtml(item.kode)} - ${escapeHtml(item.jenis)}
                </option>`;
            });

            select.html(options).val(current).trigger('change.select2');
        }

        function syncSelectedPremiPembagi() {
            const selected = $('#jenisPremiSelect').find(':selected');
            const pembagi = selected.length ? Number(selected.data('pembagi')) || 1 : 1;

            $('#jenisPremiPembagi').val(pembagi);
        }

        function renderDetailPanel(data) {
            return `
                <div class="premi-expand-panel" data-premi-id="${data.id}" data-pembagi="${Number(data.pembagi) || 1}">
                    <div class="premi-expand-head">
                        <div>
                            <div class="text-muted small fw-semibold">RINCIAN MAPPING PREMI</div>
                            <div class="fw-semibold text-dark">${escapeHtml(data.kode)} - ${escapeHtml(data.jenis)}</div>
                        </div>
                        <div class="premi-panel-actions">
                            <span class="badge bg-white text-secondary border premi-employee-count">
                                ${Number(data.jumlah_pegawai) || 0} pegawai
                            </span>
                            <span class="badge bg-white text-secondary border premi-panel-count">
                                ${Number(data.jumlah_tindakan) || 0} tindakan
                            </span>
                            <div class="input-group input-group-sm premi-pembagi-control">
                                <span class="input-group-text bg-white">Pembagi</span>
                                <input type="number" class="form-control premi-pembagi-input"
                                    min="1" step="1" value="${Number(data.pembagi) || 1}">
                                <button type="button" class="btn btn-outline-primary save-premi-pembagi"
                                    title="Simpan pembagi jenis premi">
                                    <i class="mdi mdi-content-save-outline"></i>
                                </button>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-manage-premi-employees">
                                <i class="mdi mdi-account-multiple-outline"></i> Atur Pegawai
                            </button>
                            <button type="button" class="btn btn-sm btn-primary btn-inline-add-premi">
                                <i class="mdi mdi-plus"></i> Tambah Tindakan
                            </button>
                        </div>
                    </div>
                    <div class="premi-employee-slot"></div>
                    <div class="premi-employee-summary">
                        <div class="small text-muted py-1">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Memuat pegawai penerima...
                        </div>
                    </div>
                    <div class="premi-editor-slot"></div>
                    <div class="premi-mapping-list">
                        <div class="small text-muted py-2">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Memuat rincian...
                        </div>
                    </div>
                </div>
            `;
        }

        function renderEmployeeSummary(items) {
            if (!items || items.length === 0) {
                return `
                    <div class="premi-employee-summary-head">
                        <div class="premi-employee-heading">
                            <div class="premi-employee-heading-icon">
                                <i class="mdi mdi-account-group-outline"></i>
                            </div>
                            <div>
                                <div class="fw-bold text-dark">Pegawai Penerima Premi</div>
                                <div class="small text-muted">Kelola siapa saja yang menerima jenis premi ini.</div>
                            </div>
                        </div>
                    </div>
                    <div class="premi-employee-summary-body">
                        <div class="premi-employee-empty">
                            <i class="mdi mdi-account-plus-outline d-block fs-3 text-primary mb-2"></i>
                            <div class="fw-semibold text-dark">Belum ada pegawai penerima</div>
                            <div class="small mb-3">Tambahkan pegawai agar premi ini memiliki daftar penerima yang jelas.</div>
                            <button type="button" class="btn btn-sm btn-primary btn-manage-premi-employees">
                                <i class="mdi mdi-account-multiple-plus-outline"></i>
                                Pilih Pegawai
                            </button>
                        </div>
                    </div>
                `;
            }

            const totalJabatan = new Set(
                items.map(item => normalizeText(item.jbtn)).filter(Boolean)
            ).size;
            const totalStatus = new Set(
                items.map(item => normalizeText(employeeStatusLabel(item.stts_kerja))).filter(Boolean)
            ).size;

            return `
                <div class="premi-employee-summary-head">
                    <div class="premi-employee-heading">
                        <div class="premi-employee-heading-icon">
                            <i class="mdi mdi-account-group-outline"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Pegawai Penerima Premi</div>
                            <div class="small text-muted">
                                Daftar pegawai yang berhak menerima premi ini.
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary btn-manage-premi-employees">
                        <i class="mdi mdi-account-edit-outline"></i>
                        Ubah Penerima
                    </button>
                </div>
                <div class="premi-employee-summary-body">
                    <div class="premi-employee-stats">
                        <div class="premi-employee-stat">
                            <i class="mdi mdi-account-check-outline"></i>
                            <div>
                                <div class="premi-employee-stat-value">${items.length}</div>
                                <div class="premi-employee-stat-label">Total penerima</div>
                            </div>
                        </div>
                        <div class="premi-employee-stat">
                            <i class="mdi mdi-briefcase-variant-outline"></i>
                            <div>
                                <div class="premi-employee-stat-value">${totalJabatan}</div>
                                <div class="premi-employee-stat-label">Jabatan berbeda</div>
                            </div>
                        </div>
                        <div class="premi-employee-stat">
                            <i class="mdi mdi-badge-account-horizontal-outline"></i>
                            <div>
                                <div class="premi-employee-stat-value">${totalStatus}</div>
                                <div class="premi-employee-stat-label">Status kerja</div>
                            </div>
                        </div>
                    </div>

                    <div class="premi-employee-summary-tools">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white">
                                <i class="mdi mdi-magnify text-muted"></i>
                            </span>
                            <input type="text" class="form-control premi-employee-summary-search"
                                placeholder="Cari nama, NIK, atau jabatan...">
                        </div>
                        <select class="form-select form-select-sm premi-employee-summary-status">
                            ${employeeStatusOptions(items)}
                        </select>
                    </div>

                    <div class="small text-muted mb-2">
                        Menampilkan <strong class="premi-summary-result-count text-dark">${items.length}</strong>
                        dari ${items.length} pegawai
                    </div>

                    <div class="premi-employee-list">
                        ${items.map(function(item) {
                            return `
                                <div class="premi-employee-card"
                                    data-search="${escapeHtml(employeeSearchText(item))}"
                                    data-status="${escapeHtml(normalizeText(employeeStatusLabel(item.stts_kerja)))}">
                                    ${employeeIdentity(item)}
                                </div>
                            `;
                        }).join('')}
                        <div class="premi-employee-empty premi-employee-filter-empty d-none">
                            <i class="mdi mdi-account-search-outline d-block fs-3 mb-2"></i>
                            Tidak ada pegawai yang cocok dengan pencarian.
                        </div>
                    </div>
                </div>
            `;
        }

        function applyEmployeeSummaryFilter(summary) {
            const query = normalizeText(summary.find('.premi-employee-summary-search').val());
            const status = normalizeText(summary.find('.premi-employee-summary-status').val());
            let visibleCount = 0;

            summary.find('.premi-employee-card').each(function() {
                const card = $(this);
                const matchesQuery = !query || String(card.data('search')).includes(query);
                const matchesStatus = !status || String(card.data('status')) === status;
                const visible = matchesQuery && matchesStatus;

                card.toggle(visible);
                if (visible) visibleCount++;
            });

            summary.find('.premi-summary-result-count').text(visibleCount);
            summary.find('.premi-employee-filter-empty').toggleClass('d-none', visibleCount > 0);
        }

        function renderMappingList(items) {
            if (!items || items.length === 0) {
                return `
                    <div class="premi-empty-state">
                        <div class="fw-semibold mb-1">Belum ada jenis tindakan</div>
                        <div class="small">
                            Tambahkan jenis tindakan serta nilai UMUM dan BPJS dari panel ini.
                        </div>
                    </div>
                `;
            }

            return `
                <div class="premi-list-grid">
                    ${items.map(function(item) {
                        return `
                            <div class="premi-mapping-card" data-id="${item.id}"
                                data-tindakan-id="${item.jnsTindakan_id}"
                                data-jenis="${escapeHtml(item.jenis)}"
                                data-jenis-umum="${escapeHtml(item.jenis_umum || item.jenis)}"
                                data-jenis-bpjs="${escapeHtml(item.jenis_bpjs || item.jenis)}"
                                data-nilai-umum="${item.nilai_umum}"
                                data-nilai-bpjs="${item.nilai_bpjs}"
                                data-nilai-bersama-umum="${item.nilai_bersama_umum || 0}"
                                data-nilai-bersama-bpjs="${item.nilai_bersama_bpjs || 0}">
                                <div class="premi-card-icon">
                                    <i class="mdi mdi-medical-bag"></i>
                                </div>
                                <div class="premi-card-meta">
                                    <div class="small text-muted">${escapeHtml(item.kode_tindakan)}</div>
                                    <div class="premi-card-title">${escapeHtml(item.jenis_tindakan)}</div>
                                    <span class="premi-kind-badge ${escapeHtml(item.jenis_umum || item.jenis)}">
                                        UMUM ${escapeHtml(item.jenis_umum || item.jenis)}
                                    </span>
                                    <span class="premi-kind-badge ${escapeHtml(item.jenis_bpjs || item.jenis)}">
                                        BPJS ${escapeHtml(item.jenis_bpjs || item.jenis)}
                                    </span>
                                </div>
                                <div class="premi-value-grid">
                                    <div class="premi-value-item umum">
                                        <span class="premi-value-label">UMUM</span>
                                        <span class="premi-value-number">
                                            ${formatMappingValue(item, 'nilai_umum', 'jenis_umum')}
                                        </span>
                                    </div>
                                    <div class="premi-value-item umum">
                                        <span class="premi-value-label">Bersama UMUM</span>
                                        <span class="premi-value-number">
                                            ${formatMappingValue(item, 'nilai_bersama_umum', 'jenis_umum')}
                                        </span>
                                    </div>
                                    <div class="premi-value-item bpjs">
                                        <span class="premi-value-label">BPJS</span>
                                        <span class="premi-value-number">
                                            ${formatMappingValue(item, 'nilai_bpjs', 'jenis_bpjs')}
                                        </span>
                                    </div>
                                    <div class="premi-value-item bpjs">
                                        <span class="premi-value-label">Bersama BPJS</span>
                                        <span class="premi-value-number">
                                            ${formatMappingValue(item, 'nilai_bersama_bpjs', 'jenis_bpjs')}
                                        </span>
                                    </div>
                                </div>
                                <div class="premi-card-actions">
                                    <button type="button" class="btn btn-light btn-sm edit-mapping-premi" title="Edit">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-light btn-sm text-danger delete-mapping-premi"
                                        title="Hapus">
                                        <i class="mdi mdi-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }

        function renderTableTotals(panel) {
            const actionCount = Number(panel.data('action-count')) || 0;
            const employeeCount = Number(panel.data('employee-count')) || 0;
            const pembagi = Number(panel.data('pembagi')) || 1;
            const summaries = panel.data('mapping-summaries') || [];

            panel.closest('tr').prev('tr').find('td').last().html(
                `<span class="fw-bold text-primary">${actionCount} tindakan</span>
                <div class="small text-muted">${employeeCount} pegawai</div>
                <div class="small text-muted">Pembagi ${pembagi}</div>
                ${summaries.length ? `<div class="small text-muted">${summaries.join(' + ')}</div>` : ''}`
            );
        }

        function updatePanelTotals(panel, items) {
            const totalPersenUmum = (items || [])
                .filter(item => (item.jenis_umum || item.jenis) === 'persen')
                .reduce((sum, item) => sum + (Number(item.nilai_umum) || 0), 0);
            const totalPersenBpjs = (items || [])
                .filter(item => (item.jenis_bpjs || item.jenis) === 'persen')
                .reduce((sum, item) => sum + (Number(item.nilai_bpjs) || 0), 0);
            const totalNominalUmum = (items || [])
                .filter(item => (item.jenis_umum || item.jenis) === 'nominal')
                .reduce((sum, item) => sum + (Number(item.nilai_umum) || 0), 0);
            const totalNominalBpjs = (items || [])
                .filter(item => (item.jenis_bpjs || item.jenis) === 'nominal')
                .reduce((sum, item) => sum + (Number(item.nilai_bpjs) || 0), 0);
            const count = (items || []).length;
            const summaries = [];

            if (totalPersenUmum > 0 || totalNominalUmum > 0) {
                summaries.push(
                    `UMUM ${totalPersenUmum}% / ${formatRupiah(totalNominalUmum)}`
                );
            }
            if (totalPersenBpjs > 0 || totalNominalBpjs > 0) {
                summaries.push(
                    `BPJS ${totalPersenBpjs}% / ${formatRupiah(totalNominalBpjs)}`
                );
            }

            panel.find('.premi-panel-count').text(count + ' tindakan');
            panel.data('action-count', count);
            panel.data('mapping-summaries', summaries);
            renderTableTotals(panel);
        }

        function updateEmployeeTotals(panel, items) {
            const count = (items || []).length;

            panel.find('.premi-employee-count').text(count + ' pegawai');
            panel.data('employee-count', count);
            renderTableTotals(panel);
        }

        function refreshDetailPanel(panel) {
            const premiId = panel.data('premi-id');
            const mappingRequest = $.get(routeWithId(byPremiUrl, premiId), function(items) {
                panel.find('.premi-mapping-list').html(renderMappingList(items));
                updatePanelTotals(panel, items);
            }).fail(function(xhr) {
                panel.find('.premi-mapping-list').html(`
                    <div class="premi-empty-state text-danger">
                        ${escapeHtml(xhr.responseJSON?.message || 'Rincian mapping tidak dapat dimuat.')}
                    </div>
                `);
            });

            const pegawaiRequest = $.get(routeWithId(pegawaiByPremiUrl, premiId), function(items) {
                panel.find('.premi-employee-summary').html(renderEmployeeSummary(items));
                updateEmployeeTotals(panel, items);
            }).fail(function(xhr) {
                panel.find('.premi-employee-summary').html(`
                    <div class="small text-danger">
                        ${escapeHtml(xhr.responseJSON?.message || 'Pegawai penerima tidak dapat dimuat.')}
                    </div>
                `);
            });

            return $.when(mappingRequest, pegawaiRequest);
        }

        function getPickerSelected(editor) {
            return editor.data('selectedNiks') || new Set();
        }

        function getFilteredPickerEmployees(editor) {
            const query = normalizeText(editor.find('.premi-picker-search').val());
            const status = normalizeText(editor.find('.premi-picker-status').val());

            return pegawaiList.filter(function(item) {
                const matchesQuery = !query || employeeSearchText(item).includes(query);
                const matchesStatus = !status ||
                    normalizeText(employeeStatusLabel(item.stts_kerja)) === status;

                return matchesQuery && matchesStatus;
            });
        }

        function renderEmployeePickerList(editor) {
            const selectedNiks = getPickerSelected(editor);
            const filteredItems = getFilteredPickerEmployees(editor);
            const list = editor.find('.premi-picker-list');

            editor.find('.premi-picker-filter-count').text(filteredItems.length + ' pegawai');

            if (!filteredItems.length) {
                list.html(`
                    <div class="premi-employee-empty">
                        <i class="mdi mdi-account-search-outline d-block fs-3 mb-2"></i>
                        Pegawai tidak ditemukan. Coba ubah kata kunci atau filter status.
                    </div>
                `);
                return;
            }

            list.html(filteredItems.map(function(item) {
                const selected = selectedNiks.has(String(item.nik));

                return `
                    <label class="premi-picker-row ${selected ? 'selected' : ''}" data-nik="${escapeHtml(item.nik)}">
                        <input type="checkbox" class="form-check-input premi-picker-check"
                            value="${escapeHtml(item.nik)}" ${selected ? 'checked' : ''}>
                        ${employeeIdentity(item)}
                        <i class="mdi ${selected ? 'mdi-check-circle text-primary' : 'mdi-plus-circle-outline text-muted'} fs-5"></i>
                    </label>
                `;
            }).join(''));
        }

        function renderSelectedEmployeeList(editor) {
            const selectedNiks = getPickerSelected(editor);
            const selectedItems = pegawaiList.filter(item => selectedNiks.has(String(item.nik)));
            const selectedList = editor.find('.premi-picker-selected-list');
            const remaining = Math.max(pegawaiList.length - selectedItems.length, 0);

            editor.find('.premi-picker-total-count').text(pegawaiList.length);
            editor.find('.premi-picker-selected-count').text(selectedItems.length);
            editor.find('.premi-picker-remaining-count').text(remaining);
            editor.find('.employee-selection-count').text(selectedItems.length + ' pegawai dipilih');

            if (!selectedItems.length) {
                selectedList.html(`
                    <div class="premi-employee-empty">
                        <i class="mdi mdi-account-arrow-right-outline d-block fs-3 mb-2"></i>
                        Belum ada pegawai dipilih.
                    </div>
                `);
                return;
            }

            selectedList.html(selectedItems.map(function(item) {
                return `
                    <div class="premi-picker-selected-item" data-nik="${escapeHtml(item.nik)}">
                        ${employeeIdentity(item)}
                        <button type="button" class="premi-picker-remove remove-premi-employee"
                            title="Hapus ${escapeHtml(item.nama)}">
                            <i class="mdi mdi-close"></i>
                        </button>
                    </div>
                `;
            }).join(''));
        }

        function refreshEmployeePicker(editor) {
            renderEmployeePickerList(editor);
            renderSelectedEmployeeList(editor);
        }

        function showEmployeeEditor(panel, items) {
            const selectedNiks = new Set((items || []).map(item => String(item.nik)));

            panel.find('.premi-employee-slot').html(`
                <div class="premi-inline-editor premi-employee-editor">
                    <div class="premi-inline-head">
                        <div class="premi-inline-heading">
                            <div class="premi-inline-icon">
                                <i class="mdi mdi-account-multiple-outline"></i>
                            </div>
                            <div>
                                <div class="premi-inline-title">Atur Pegawai Penerima</div>
                                <div class="premi-inline-subtitle">
                                    Cari pegawai aktif, pilih penerima, lalu periksa daftar pilihan sebelum menyimpan.
                                </div>
                            </div>
                        </div>
                        <span class="premi-inline-mode employee-selection-count">
                            ${selectedNiks.size} pegawai dipilih
                        </span>
                    </div>

                    <div class="premi-inline-body">
                        <div class="premi-employee-picker-overview">
                            <div class="premi-picker-stat">
                                <strong class="premi-picker-total-count">${pegawaiList.length}</strong>
                                <span>Pegawai aktif tersedia</span>
                            </div>
                            <div class="premi-picker-stat">
                                <strong class="premi-picker-selected-count">${selectedNiks.size}</strong>
                                <span>Dipilih sebagai penerima</span>
                            </div>
                            <div class="premi-picker-stat">
                                <strong class="premi-picker-remaining-count">
                                    ${Math.max(pegawaiList.length - selectedNiks.size, 0)}
                                </strong>
                                <span>Belum dipilih</span>
                            </div>
                        </div>

                        <div class="premi-employee-picker-grid">
                            <div class="premi-picker-pane">
                                <div class="premi-picker-pane-head">
                                    <div class="premi-picker-pane-title">
                                        <i class="mdi mdi-account-search-outline me-1"></i>
                                        Daftar Pegawai Aktif
                                    </div>
                                    <span class="small text-muted premi-picker-filter-count">
                                        ${pegawaiList.length} pegawai
                                    </span>
                                </div>
                                <div class="premi-picker-toolbar">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-white">
                                            <i class="mdi mdi-magnify text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control premi-picker-search"
                                            placeholder="Cari nama, NIK, atau jabatan...">
                                    </div>
                                    <select class="form-select form-select-sm premi-picker-status">
                                        ${employeeStatusOptions(pegawaiList)}
                                    </select>
                                </div>
                                <div class="premi-employee-tools px-2 mt-0 mb-2">
                                    <button type="button" class="btn btn-outline-primary btn-sm select-visible-premi-employees">
                                        <i class="mdi mdi-check-all"></i>
                                        Pilih Hasil Filter
                                    </button>
                                </div>
                                <div class="premi-picker-list"></div>
                            </div>

                            <div class="premi-picker-pane">
                                <div class="premi-picker-pane-head">
                                    <div class="premi-picker-pane-title">
                                        <i class="mdi mdi-account-check-outline me-1"></i>
                                        Penerima Terpilih
                                    </div>
                                    <button type="button" class="btn btn-light btn-sm text-danger clear-premi-employees">
                                        Kosongkan
                                    </button>
                                </div>
                                <div class="small text-muted px-3 py-2">
                                    Daftar ini yang akan disimpan sebagai penerima premi.
                                </div>
                                <div class="premi-picker-selected-list"></div>
                            </div>
                        </div>
                    </div>

                    <div class="premi-inline-actions">
                        <button type="button" class="btn btn-light btn-sm cancel-premi-employees">
                            <i class="mdi mdi-close"></i>
                            Batal
                        </button>
                        <button type="button" class="btn btn-primary btn-sm save-premi-employees">
                            <i class="mdi mdi-content-save-outline"></i>
                            Simpan Pegawai
                        </button>
                    </div>
                </div>
            `);

            const editor = panel.find('.premi-employee-editor');
            editor.data('selectedNiks', selectedNiks);
            refreshEmployeePicker(editor);
        }

        function showInlineEditor(panel, mode, data = {}) {
            const isEdit = mode === 'edit';
            const title = isEdit ? 'Edit Mapping Premi' : 'Tambah Mapping Premi';
            const subtitle = isEdit
                ? 'Ubah tindakan, jenis nilai, serta nilai UMUM dan BPJS.'
                : 'Tambahkan tindakan beserta nilai UMUM dan BPJS.';

            panel.find('.premi-editor-slot').html(`
                <div class="premi-inline-editor" data-mode="${mode}" data-id="${data.id || ''}">
                    <div class="premi-inline-head">
                        <div class="premi-inline-heading">
                            <div class="premi-inline-icon">
                                <i class="mdi ${isEdit ? 'mdi-pencil' : 'mdi-plus'}"></i>
                            </div>
                            <div>
                                <div class="premi-inline-title">${title}</div>
                                <div class="premi-inline-subtitle">${subtitle}</div>
                            </div>
                        </div>
                        <span class="premi-inline-mode">${isEdit ? 'MODE EDIT' : 'DATA BARU'}</span>
                    </div>

                    <div class="premi-inline-body">
                        <div class="row g-3">
                            <div class="col-lg-4">
                                <div class="premi-inline-field">
                                    <label class="form-label">Jenis Tindakan</label>
                                    <div class="premi-inline-control">
                                        <select class="form-select inline-tindakan">
                                            ${tindakanOptions(data.jnsTindakan_id)}
                                        </select>
                                    </div>
                                    <span class="premi-inline-help">
                                        Pilih kategori tindakan yang menerima premi.
                                    </span>
                                </div>
                            </div>

                            <div class="col-lg-2">
                                <div class="premi-inline-field">
                                    <label class="form-label">Jenis UMUM</label>
                                    <div class="premi-inline-control">
                                        <select class="form-select inline-jenis-umum">
                                            ${jenisOptions(data.jenis_umum || data.jenis || 'persen')}
                                        </select>
                                    </div>
                                    <span class="premi-inline-help">Persen atau nominal.</span>
                                </div>
                            </div>

                            <div class="col-lg-2">
                                <div class="premi-inline-field">
                                    <label class="form-label">Nilai UMUM</label>
                                    <div class="premi-inline-control">
                                        <div class="input-group">
                                            <span class="input-group-text fw-bold inline-value-addon-umum">%</span>
                                            <input type="number"
                                                class="form-control inline-nilai inline-nilai-umum" min="0"
                                                step="1" value="${escapeHtml(data.nilai_umum ?? '')}" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-2">
                                <div class="premi-inline-field">
                                    <label class="form-label">Bersama UMUM</label>
                                    <div class="premi-inline-control">
                                        <div class="input-group">
                                            <span class="input-group-text fw-bold inline-value-addon-umum">%</span>
                                            <input type="number"
                                                class="form-control inline-bersama inline-bersama-umum" min="0"
                                                step="1" value="${escapeHtml(data.nilai_bersama_umum ?? 0)}" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-2">
                                <div class="premi-inline-field">
                                    <label class="form-label">Jenis BPJS</label>
                                    <div class="premi-inline-control">
                                        <select class="form-select inline-jenis-bpjs">
                                            ${jenisOptions(data.jenis_bpjs || data.jenis || 'persen')}
                                        </select>
                                    </div>
                                    <span class="premi-inline-help">Persen atau nominal.</span>
                                </div>
                            </div>

                            <div class="col-lg-2">
                                <div class="premi-inline-field">
                                    <label class="form-label">Nilai BPJS</label>
                                    <div class="premi-inline-control">
                                        <div class="input-group">
                                            <span class="input-group-text fw-bold inline-value-addon-bpjs">%</span>
                                            <input type="number"
                                                class="form-control inline-nilai inline-nilai-bpjs" min="0"
                                                step="1" value="${escapeHtml(data.nilai_bpjs ?? '')}" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-2">
                                <div class="premi-inline-field">
                                    <label class="form-label">Bersama BPJS</label>
                                    <div class="premi-inline-control">
                                        <div class="input-group">
                                            <span class="input-group-text fw-bold inline-value-addon-bpjs">%</span>
                                            <input type="number"
                                                class="form-control inline-bersama inline-bersama-bpjs" min="0"
                                                step="1" value="${escapeHtml(data.nilai_bersama_bpjs ?? 0)}" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <span class="premi-inline-help inline-value-help-umum">
                            Persentase UMUM yang diizinkan adalah 0 sampai 100.
                        </span>
                        <span class="premi-inline-help inline-value-help-bpjs">
                            Persentase BPJS yang diizinkan adalah 0 sampai 100.
                        </span>
                    </div>

                    <div class="premi-inline-actions">
                        <button type="button" class="btn btn-light btn-sm cancel-inline-premi">
                            <i class="mdi mdi-close"></i>
                            Batal
                        </button>
                        <button type="button" class="btn btn-primary btn-sm save-inline-premi">
                            <i class="mdi mdi-content-save-outline"></i>
                            ${isEdit ? 'Simpan Perubahan' : 'Simpan Mapping'}
                        </button>
                    </div>
                </div>
            `);

            const editor = panel.find('.premi-inline-editor');
            const tindakanSelect = editor.find('.inline-tindakan');

            tindakanSelect.select2({
                dropdownParent: $('body'),
                width: '100%',
                placeholder: 'Pilih Jenis Tindakan',
                allowClear: true
            });

            if (data.jnsTindakan_id) {
                tindakanSelect.val(String(data.jnsTindakan_id)).trigger('change.select2');
            }

            applyValueMode(editor);
        }

        const premiTable = $('#tablePremi').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route("masterData.mapping.mappingPremi.getPremiTable") }}",
            columns: [{
                    data: 'detail_control',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                },
                {
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'kode',
                    name: 'kode'
                },
                {
                    data: 'jenis',
                    name: 'jenis'
                },
                {
                    data: 'total',
                    name: 'total',
                    className: 'text-end',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $('.dataTables_filter').hide();

        $('#searchPremi').on('keyup', function() {
            premiTable.search(this.value).draw();
        });

        $('#tablePremi tbody').on('click', '.btn-detail-premi', function() {
            const tr = $(this).closest('tr');
            const row = premiTable.row(tr);
            const icon = $(this).find('.mdi');

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
                icon.removeClass('mdi-chevron-up').addClass('mdi-chevron-down');
                return;
            }

            const data = row.data();
            row.child(renderDetailPanel(data)).show();
            tr.addClass('shown');
            icon.removeClass('mdi-chevron-down').addClass('mdi-chevron-up');
            refreshDetailPanel(tr.next('tr').find('.premi-expand-panel'));
        });

        modal.on('show.bs.modal', function() {
            loadGuides().done(function() {
                fillPremiSelect();
                resetModal();
                $('#jenisPremiSelect').select2({
                    dropdownParent: modal,
                    width: '100%',
                    placeholder: 'Pilih Jenis Premi',
                    allowClear: true
                });
            }).fail(function() {
                Swal.fire('Gagal', 'Master premi atau jenis tindakan tidak dapat dimuat.', 'error');
            });
        });

        modal.on('hidden.bs.modal', function() {
            container.empty();
            resetValidation();
        });

        $('#addPremiMappingRow').on('click', function() {
            appendRow();
        });

        $('#jenisPremiSelect').on('change', function() {
            syncSelectedPremiPembagi();
        });

        container.on('click', '.remove-premi-row', function() {
            if (container.find('.premi-form-row').length === 1) {
                showToast('warning', 'Minimal satu jenis tindakan');
                return;
            }

            $(this).closest('.premi-form-row').remove();
        });

        container.on('change', '.mapping-jenis-umum, .mapping-jenis-bpjs', function() {
            applyValueMode($(this).closest('.premi-form-row'));
        });

        $(document).on('change', '.inline-jenis-umum, .inline-jenis-bpjs', function() {
            applyValueMode($(this).closest('.premi-inline-editor'));
        });

        $('#premiForm').on('submit', function(e) {
            e.preventDefault();
            resetValidation();

            const ids = container.find('.mapping-tindakan-select').map(function() {
                return $(this).val();
            }).get().filter(Boolean);

            if (!$('#jenisPremiSelect').val()) {
                $('#jenisPremiSelect').addClass('is-invalid');
                $('#error-jnsPremi_id').text('Jenis premi wajib dipilih');
                return;
            }

            if ($('#jenisPremiPembagi').val() === '' || Number($('#jenisPremiPembagi').val()) < 1) {
                $('#jenisPremiPembagi').addClass('is-invalid');
                $('#error-pembagi').text('Pembagi jenis premi minimal 1');
                return;
            }

            if (!ids.length) {
                $('#error-mappings').text('Jenis tindakan wajib ditambahkan');
                return;
            }

            if (ids.length !== [...new Set(ids)].length) {
                $('#error-mappings').text('Jenis tindakan tidak boleh duplikat');
                return;
            }

            let valueError = '';
            container.find('.premi-form-row').each(function() {
                const row = $(this);
                const jenisUmum = row.find('.mapping-jenis-umum').val();
                const jenisBpjs = row.find('.mapping-jenis-bpjs').val();
                const nilaiUmum = row.find('.mapping-nilai-umum').val();
                const nilaiBpjs = row.find('.mapping-nilai-bpjs').val();
                const bersamaUmum = row.find('.mapping-bersama-umum').val();
                const bersamaBpjs = row.find('.mapping-bersama-bpjs').val();

                if (!jenisUmum || !jenisBpjs || nilaiUmum === '' || nilaiBpjs === '' ||
                    bersamaUmum === '' || bersamaBpjs === '' ||
                    Number(nilaiUmum) < 0 || Number(nilaiBpjs) < 0 ||
                    Number(bersamaUmum) < 0 || Number(bersamaBpjs) < 0) {
                    valueError = 'Jenis nilai, nilai UMUM/BPJS, dan nilai Bersama wajib diisi.';
                    return false;
                }

                if (jenisUmum === 'persen' &&
                    (Number(nilaiUmum) > 100 || Number(bersamaUmum) > 100)) {
                    valueError = 'Nilai persen UMUM dan Bersama UMUM maksimal 100%.';
                    return false;
                }

                if (jenisBpjs === 'persen' &&
                    (Number(nilaiBpjs) > 100 || Number(bersamaBpjs) > 100)) {
                    valueError = 'Nilai persen BPJS dan Bersama BPJS maksimal 100%.';
                    return false;
                }
            });

            if (valueError) {
                $('#error-mappings').text(valueError);
                return;
            }

            $.ajax({
                url: storeUrl,
                method: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    modal.modal('hide');
                    premiTable.ajax.reload(null, false);
                    showToast('success', response.message);
                },
                error: function(xhr) {
                    const errors = xhr.responseJSON?.errors || {};
                    const message = xhr.responseJSON?.message || Object.values(errors)[0]?.[0] ||
                        'Mapping premi gagal disimpan';

                    if (errors.jnsPremi_id) {
                        $('#jenisPremiSelect').addClass('is-invalid');
                        $('#error-jnsPremi_id').text(errors.jnsPremi_id[0]);
                    }

                    if (errors.pembagi) {
                        $('#jenisPremiPembagi').addClass('is-invalid');
                        $('#error-pembagi').text(errors.pembagi[0]);
                    }

                    $('#error-mappings').text(errors.mappings?.[0] || '');
                    Swal.fire('Gagal', message, xhr.status === 422 ? 'warning' : 'error');
                }
            });
        });

        $(document).on('click', '.save-premi-pembagi', function() {
            const button = $(this);
            const panel = button.closest('.premi-expand-panel');
            const input = panel.find('.premi-pembagi-input');
            const pembagi = input.val();

            if (pembagi === '' || Number(pembagi) < 1) {
                Swal.fire('Pembagi tidak valid', 'Pembagi jenis premi minimal 1.', 'warning');
                return;
            }

            const originalButton = button.html();
            button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

            $.ajax({
                url: routeWithId(updatePembagiUrl, panel.data('premi-id')),
                method: 'PUT',
                data: {
                    pembagi: pembagi
                },
                success: function(response) {
                    const savedPembagi = Number(response.pembagi) || 1;
                    panel.data('pembagi', savedPembagi);
                    panel.attr('data-pembagi', savedPembagi);
                    input.val(savedPembagi);

                    const row = premiTable.row(panel.closest('tr').prev('tr'));
                    const rowData = row.data();
                    if (rowData) {
                        rowData.pembagi = savedPembagi;
                        row.data(rowData).invalidate();
                    }

                    renderTableTotals(panel);
                    showToast('success', response.message);
                },
                error: function(xhr) {
                    Swal.fire(
                        'Gagal',
                        xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {})[0]?.[0] ||
                        'Pembagi jenis premi gagal disimpan',
                        xhr.status === 422 ? 'warning' : 'error'
                    );
                },
                complete: function() {
                    button.prop('disabled', false).html(originalButton);
                }
            });
        });

        $(document).on('click', '.btn-inline-add-premi', function() {
            const panel = $(this).closest('.premi-expand-panel');

            loadTindakanGuide().done(function() {
                showInlineEditor(panel, 'add');
            }).fail(function() {
                Swal.fire('Gagal', 'Master jenis tindakan tidak dapat dimuat.', 'error');
            });
        });

        $(document).on('click', '.btn-manage-premi-employees', function() {
            const panel = $(this).closest('.premi-expand-panel');
            const premiId = panel.data('premi-id');

            $.when(
                loadPegawaiGuide(),
                $.get(routeWithId(pegawaiByPremiUrl, premiId))
            ).done(function(guideResponse, employeeResponse) {
                const items = Array.isArray(employeeResponse?.[0])
                    ? employeeResponse[0]
                    : employeeResponse;
                showEmployeeEditor(panel, items || []);
            }).fail(function() {
                Swal.fire('Gagal', 'Data pegawai penerima tidak dapat dimuat.', 'error');
            });
        });

        $(document).on('input', '.premi-employee-summary-search', function() {
            applyEmployeeSummaryFilter($(this).closest('.premi-employee-summary'));
        });

        $(document).on('change', '.premi-employee-summary-status', function() {
            applyEmployeeSummaryFilter($(this).closest('.premi-employee-summary'));
        });

        $(document).on('input', '.premi-picker-search', function() {
            renderEmployeePickerList($(this).closest('.premi-employee-editor'));
        });

        $(document).on('change', '.premi-picker-status', function() {
            renderEmployeePickerList($(this).closest('.premi-employee-editor'));
        });

        $(document).on('change', '.premi-picker-check', function() {
            const editor = $(this).closest('.premi-employee-editor');
            const selectedNiks = getPickerSelected(editor);
            const nik = String($(this).val());

            if (this.checked) {
                selectedNiks.add(nik);
            } else {
                selectedNiks.delete(nik);
            }

            editor.data('selectedNiks', selectedNiks);
            refreshEmployeePicker(editor);
        });

        $(document).on('click', '.select-visible-premi-employees', function() {
            const editor = $(this).closest('.premi-employee-editor');
            const selectedNiks = getPickerSelected(editor);

            getFilteredPickerEmployees(editor).forEach(function(item) {
                selectedNiks.add(String(item.nik));
            });

            editor.data('selectedNiks', selectedNiks);
            refreshEmployeePicker(editor);
        });

        $(document).on('click', '.clear-premi-employees', function() {
            const editor = $(this).closest('.premi-employee-editor');
            editor.data('selectedNiks', new Set());
            refreshEmployeePicker(editor);
        });

        $(document).on('click', '.remove-premi-employee', function() {
            const editor = $(this).closest('.premi-employee-editor');
            const selectedNiks = getPickerSelected(editor);
            const nik = String($(this).closest('.premi-picker-selected-item').attr('data-nik'));

            selectedNiks.delete(nik);
            editor.data('selectedNiks', selectedNiks);
            refreshEmployeePicker(editor);
        });

        $(document).on('click', '.cancel-premi-employees', function() {
            $(this).closest('.premi-employee-slot').empty();
        });

        $(document).on('click', '.save-premi-employees', function() {
            const button = $(this);
            const editor = button.closest('.premi-employee-editor');
            const panel = button.closest('.premi-expand-panel');
            const selectedNiks = [...getPickerSelected(editor)];
            const originalButton = button.html();

            button.prop('disabled', true).html(`
                <span class="spinner-border spinner-border-sm"></span>
                Menyimpan...
            `);

            $.ajax({
                url: routeWithId(updatePegawaiUrl, panel.data('premi-id')),
                method: 'PUT',
                contentType: 'application/json',
                data: JSON.stringify({
                    nik: selectedNiks
                }),
                success: function(response) {
                    editor.remove();
                    refreshDetailPanel(panel);
                    showToast('success', response.message);
                },
                error: function(xhr) {
                    Swal.fire(
                        'Gagal',
                        xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {})[0]?.[0] ||
                        'Pegawai penerima gagal disimpan',
                        xhr.status === 422 ? 'warning' : 'error'
                    );
                },
                complete: function() {
                    button.prop('disabled', false).html(originalButton);
                }
            });
        });

        $(document).on('click', '.edit-mapping-premi', function() {
            const card = $(this).closest('.premi-mapping-card');
            const panel = $(this).closest('.premi-expand-panel');
            const data = {
                id: card.attr('data-id'),
                jnsTindakan_id: card.attr('data-tindakan-id'),
                jenis: card.attr('data-jenis'),
                jenis_umum: card.attr('data-jenis-umum'),
                jenis_bpjs: card.attr('data-jenis-bpjs'),
                nilai_umum: card.attr('data-nilai-umum'),
                nilai_bpjs: card.attr('data-nilai-bpjs'),
                nilai_bersama_umum: card.attr('data-nilai-bersama-umum'),
                nilai_bersama_bpjs: card.attr('data-nilai-bersama-bpjs')
            };

            loadTindakanGuide().done(function() {
                showInlineEditor(panel, 'edit', data);
            }).fail(function() {
                Swal.fire('Gagal', 'Master jenis tindakan tidak dapat dimuat.', 'error');
            });
        });

        $(document).on('click', '.cancel-inline-premi', function() {
            $(this).closest('.premi-expand-panel').find('.premi-editor-slot').empty();
        });

        $(document).on('click', '.save-inline-premi', function() {
            const button = $(this);
            const editor = button.closest('.premi-inline-editor');
            const panel = button.closest('.premi-expand-panel');
            const mode = editor.data('mode');
            const id = editor.data('id');
            const tindakanId = editor.find('.inline-tindakan').val();
            const jenisUmum = editor.find('.inline-jenis-umum').val();
            const jenisBpjs = editor.find('.inline-jenis-bpjs').val();
            const nilaiUmum = editor.find('.inline-nilai-umum').val();
            const nilaiBpjs = editor.find('.inline-nilai-bpjs').val();
            const bersamaUmum = editor.find('.inline-bersama-umum').val();
            const bersamaBpjs = editor.find('.inline-bersama-bpjs').val();

            if (!tindakanId || !jenisUmum || !jenisBpjs || nilaiUmum === '' || nilaiBpjs === '' ||
                bersamaUmum === '' || bersamaBpjs === '' ||
                Number(nilaiUmum) < 0 || Number(nilaiBpjs) < 0 ||
                Number(bersamaUmum) < 0 || Number(bersamaBpjs) < 0) {
                Swal.fire(
                    'Data belum lengkap',
                    'Pilih tindakan, jenis nilai, serta isi nilai UMUM/BPJS dan Bersama.',
                    'warning'
                );
                return;
            }

            if (jenisUmum === 'persen' &&
                (Number(nilaiUmum) > 100 || Number(bersamaUmum) > 100)) {
                Swal.fire('Nilai tidak valid', 'Nilai persen UMUM dan Bersama UMUM maksimal 100%.', 'warning');
                return;
            }

            if (jenisBpjs === 'persen' &&
                (Number(nilaiBpjs) > 100 || Number(bersamaBpjs) > 100)) {
                Swal.fire('Nilai tidak valid', 'Nilai persen BPJS dan Bersama BPJS maksimal 100%.', 'warning');
                return;
            }

            const data = mode === 'edit' ? {
                jnsTindakan_id: tindakanId,
                jenis_umum: jenisUmum,
                jenis_bpjs: jenisBpjs,
                nilai_umum: nilaiUmum,
                nilai_bpjs: nilaiBpjs,
                nilai_bersama_umum: bersamaUmum,
                nilai_bersama_bpjs: bersamaBpjs
            } : {
                jnsPremi_id: panel.data('premi-id'),
                pembagi: panel.find('.premi-pembagi-input').val() || panel.data('pembagi') || 1,
                mappings: [{
                    jnsTindakan_id: tindakanId,
                    jenis_umum: jenisUmum,
                    jenis_bpjs: jenisBpjs,
                    nilai_umum: nilaiUmum,
                    nilai_bpjs: nilaiBpjs,
                    nilai_bersama_umum: bersamaUmum,
                    nilai_bersama_bpjs: bersamaBpjs
                }]
            };

            button.prop('disabled', true);
            const originalButton = button.html();
            button.html(`
                <span class="spinner-border spinner-border-sm"></span>
                Menyimpan...
            `);

            $.ajax({
                url: mode === 'edit' ? routeWithId(updateUrl, id) : storeUrl,
                method: mode === 'edit' ? 'PUT' : 'POST',
                data: data,
                success: function(response) {
                    if (response.pembagi) {
                        const savedPembagi = Number(response.pembagi) || 1;
                        panel.data('pembagi', savedPembagi);
                        panel.attr('data-pembagi', savedPembagi);
                        panel.find('.premi-pembagi-input').val(savedPembagi);
                    }

                    editor.remove();
                    refreshDetailPanel(panel);
                    showToast('success', response.message);
                },
                error: function(xhr) {
                    Swal.fire(
                        'Gagal',
                        xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {})[0]?.[0] ||
                        'Mapping premi gagal disimpan',
                        xhr.status === 422 ? 'warning' : 'error'
                    );
                },
                complete: function() {
                    button.prop('disabled', false).html(originalButton);
                }
            });
        });

        $(document).on('click', '.delete-mapping-premi', function() {
            const card = $(this).closest('.premi-mapping-card');
            const panel = $(this).closest('.premi-expand-panel');
            const id = card.data('id');

            Swal.fire({
                title: 'Hapus mapping premi?',
                text: 'Jenis tindakan ini akan dilepas dari premi.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function(result) {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: routeWithId(deleteUrl, id),
                    method: 'DELETE',
                    success: function(response) {
                        panel.find('.premi-editor-slot').empty();
                        refreshDetailPanel(panel);
                        showToast('success', response.message);
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Mapping premi gagal dihapus.',
                            'error');
                    }
                });
            });
        });
    });
</script>
