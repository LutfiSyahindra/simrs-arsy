<script>
    $(function() {
        const modal = $('#tindakanModal');
        const byJenisUrl = "{{ route("masterData.mapping.mappingTindakan.byJenis", ":id") }}";
        const updateTindakanUrl = "{{ route("masterData.mapping.mappingTindakan.update", ":id") }}";
        const deleteTindakanUrl = "{{ route("masterData.mapping.mappingTindakan.delete", ":id") }}";
        const storeTindakanUrl = "{{ route("masterData.mapping.mappingTindakan.store") }}";
        const copyTindakanUrl = "{{ route("masterData.mapping.mappingTindakan.copy") }}";
        const bulkDeleteTindakanUrl = "{{ route("masterData.mapping.mappingTindakan.bulkDelete") }}";
        const searchTindakanUrl = "{{ route("masterData.mapping.mappingTindakan.searchTindakan") }}";
        const sourceOptionsUrl = "{{ route("masterData.mapping.mappingTindakan.sourceOptions") }}";

        let jenisList = [];
        let jenisListRequest = null;
        let sourceList = [];
        let sourceListRequest = null;
        let selectedTindakanMap = new Map();
        let searchTindakanTimer = null;
        let sourceSearchRequest = null;
        let lastSourceResults = [];

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
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

        function formatAngka(value) {
            return new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 0
            }).format(Number(value) || 0);
        }

        function routeWithParam(url, key, value) {
            return url.replace(key, encodeURIComponent(value));
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

        function loadJenis() {
            if (jenisList.length) {
                return $.Deferred().resolve(jenisList).promise();
            }

            if (jenisListRequest) {
                return jenisListRequest;
            }

            jenisListRequest = $.ajax({
                url: "{{ route("masterData.mapping.mappingTindakan.guideJenisTindakan") }}",
                method: 'GET',
                success: function(res) {
                    jenisList = res || [];
                },
                complete: function() {
                    jenisListRequest = null;
                }
            });

            return jenisListRequest;
        }

        function jenisOptions(selectedId = null) {
            let options = '<option value="">-- Pilih Jenis Tindakan --</option>';

            jenisList.forEach(function(item) {
                const selected = String(item.id) === String(selectedId) ? 'selected' : '';
                options += `
                    <option value="${item.id}" ${selected}>
                        ${escapeHtml(item.kode)} - ${escapeHtml(item.jenis)}
                    </option>
                `;
            });

            return options;
        }

        function copyTargetOptions(sourceJenisId, selectedId = null) {
            let options = '<option value="">-- Pilih Tujuan Copy --</option>';

            jenisList.forEach(function(item) {
                if (String(item.id) === String(sourceJenisId)) {
                    return;
                }

                const selected = String(item.id) === String(selectedId) ? 'selected' : '';
                options += `
                    <option value="${item.id}" ${selected}>
                        ${escapeHtml(item.kode)} - ${escapeHtml(item.jenis)}
                    </option>
                `;
            });

            return options;
        }

        function loadSources() {
            if (sourceList.length) {
                return $.Deferred().resolve(sourceList).promise();
            }

            if (sourceListRequest) {
                return sourceListRequest;
            }

            sourceListRequest = $.ajax({
                url: sourceOptionsUrl,
                method: 'GET',
                success: function(res) {
                    sourceList = res || [];
                },
                complete: function() {
                    sourceListRequest = null;
                }
            });

            return sourceListRequest;
        }

        function sourceOptions(selectedId = '') {
            let options = '<option value="">Semua sumber</option>';

            sourceList.forEach(function(item) {
                const selected = String(item.id) === String(selectedId) ? 'selected' : '';
                options += `
                    <option value="${item.id}" ${selected}>
                        ${escapeHtml(item.id)} - ${escapeHtml(item.text)}
                    </option>
                `;
            });

            return options;
        }

        function resetValidation() {
            modal.find('.form-control, .form-select').removeClass('is-invalid');
            modal.find('.invalid-feedback').text('');
        }

        function resetModal() {
            $('#tindakanForm')[0].reset();
            $('#jenisTindakanSelect').html(jenisOptions()).val('').trigger('change');
            $('#sourceTindakanFilter').html(sourceOptions()).val('').prop('disabled', true);
            $('#searchTindakanSource').val('').prop('disabled', true);
            $('#clearSearchTindakanSource').prop('disabled', true);
            setSourceSelectAllDisabled();
            selectedTindakanMap.clear();
            lastSourceResults = [];

            if (searchTindakanTimer) {
                clearTimeout(searchTindakanTimer);
                searchTindakanTimer = null;
            }

            if (sourceSearchRequest) {
                sourceSearchRequest.abort();
                sourceSearchRequest = null;
            }

            renderSourceChecklist('disabled');
            renderSelectedPreview();
            updateSelectedCount();
            resetValidation();
        }

        function initSelect2(el, parentModal, placeholder = '-- Pilih --') {
            const select = $(el);

            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }

            select.select2({
                dropdownParent: $(parentModal),
                width: '100%',
                placeholder: placeholder,
                allowClear: true
            });
        }

        function sourceOptionTemplate(item) {
            if (item.loading) {
                return item.text;
            }

            const parent = item.parent_label && item.parent_label !== '-' ?
                `<div class="tindakan-source-meta">Judul: ${escapeHtml(item.parent_label)}</div>` : '';
            const pj = item.pj_label && item.pj_label !== '-' ?
                `<div class="tindakan-source-meta">PJ: ${escapeHtml(item.pj_label)}</div>` : '';
            const sourceKey = item.source_key ?
                `<div class="tindakan-source-meta">Source key: ${escapeHtml(item.source_key)}</div>` : '';

            return $(`
                <div class="tindakan-source-option">
                    <div>
                        <span class="tindakan-source-badge">${escapeHtml(item.sumber_label || '-')}</span>
                        <span class="tindakan-source-badge active">Aktif</span>
                    </div>
                    <div class="tindakan-source-title">
                        ${escapeHtml(item.kd_tindakan || '-')} - ${escapeHtml(item.nm_tindakan || '-')}
                    </div>
                    ${sourceKey}
                    ${parent}
                    ${pj}
                </div>
            `);
        }

        function sourceSelectionTemplate(item) {
            return escapeHtml(item.display_text || item.text || item.id || '');
        }

        function initSourceSelect(el, parent = modal, placeholder = 'Ketik minimal 3 huruf tindakan') {
            const select = $(el);

            if (select.hasClass('select2-hidden-accessible')) {
                select.select2('destroy');
            }

            select.select2({
                dropdownParent: $(parent),
                width: '100%',
                placeholder: placeholder,
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: searchTindakanUrl,
                    dataType: 'json',
                    delay: 350,
                    data: function(params) {
                        const editor = select.closest('.tindakan-inline-editor');

                        return {
                            q: params.term || '',
                            source: editor.find('.inline-source-filter').val() || ''
                        };
                    },
                    processResults: function(data) {
                        return data;
                    }
                },
                templateResult: sourceOptionTemplate,
                templateSelection: sourceSelectionTemplate,
                escapeMarkup: function(markup) {
                    return markup;
                },
                language: {
                    inputTooShort: function() {
                        return 'Ketik minimal 3 huruf tindakan';
                    },
                    noResults: function() {
                        return 'Tindakan tidak ditemukan';
                    },
                    searching: function() {
                        return 'Mencari tindakan...';
                    }
                }
            });
        }

        function updateSelectedCount() {
            $('#tindakanSelectedCount').text(formatAngka(selectedTindakanMap.size));
        }

        function setSourceResultInfo(text) {
            $('#sourceResultInfo').text(text);
        }

        function setSourceSelectAllDisabled() {
            $('#checkAllVisibleTindakan').prop({
                checked: false,
                indeterminate: false,
                disabled: true
            });
            $('#checkAllVisibleTindakanLabel').text('Pilih semua yang tampil');
        }

        function updateSourceSelectAllState() {
            const total = lastSourceResults.length;
            const checkedCount = lastSourceResults.filter(function(item) {
                return selectedTindakanMap.has(item.source_key);
            }).length;

            if (total === 0) {
                setSourceSelectAllDisabled();
                return;
            }

            $('#checkAllVisibleTindakan').prop({
                disabled: false,
                checked: checkedCount === total,
                indeterminate: checkedCount > 0 && checkedCount < total
            });
            $('#checkAllVisibleTindakanLabel').text(
                'Pilih semua yang tampil (' + formatAngka(total) + ')'
            );
        }

        function renderSelectedPreview() {
            if (selectedTindakanMap.size === 0) {
                $('#selectedTindakanPreview')
                    .addClass('text-muted small')
                    .html('Belum ada tindakan dipilih.');
                return;
            }

            $('#selectedTindakanPreview')
                .removeClass('text-muted small')
                .html([...selectedTindakanMap.values()].map(function(item) {
                    return `
                        <span class="tindakan-selected-chip">
                            <span class="tindakan-source-badge">${escapeHtml(item.sumber_label || item.sumber_tindakan || '-')}</span>
                            ${escapeHtml(item.kd_tindakan || '-')} - ${escapeHtml(item.nm_tindakan || '-')}
                            <button type="button" class="tindakan-selected-remove"
                                data-source-key="${escapeHtml(item.source_key)}" title="Hapus pilihan">
                                <i class="mdi mdi-close"></i>
                            </button>
                        </span>
                    `;
                }).join(''));
        }

        function renderSourceChecklist(state = 'ready', items = []) {
            if (state === 'disabled') {
                setSourceResultInfo('Menunggu jenis tindakan');
                setSourceSelectAllDisabled();
                $('#sourceTindakanChecklist').html(`
                    <div class="tindakan-source-empty">
                        Pilih jenis tindakan terlebih dahulu.
                    </div>
                `);
                return;
            }

            if (state === 'too-short') {
                setSourceResultInfo('Minimal 3 huruf');
                setSourceSelectAllDisabled();
                $('#sourceTindakanChecklist').html(`
                    <div class="tindakan-source-empty">
                        Pilih sumber bila perlu, lalu ketik minimal 3 huruf untuk mencari tindakan aktif.
                    </div>
                `);
                return;
            }

            if (state === 'loading') {
                setSourceResultInfo('Mencari...');
                setSourceSelectAllDisabled();
                $('#sourceTindakanChecklist').html(`
                    <div class="tindakan-source-empty">
                        <span class="spinner-border spinner-border-sm me-2"></span>
                        Mencari tindakan...
                    </div>
                `);
                return;
            }

            if (state === 'error') {
                setSourceResultInfo('Gagal memuat');
                setSourceSelectAllDisabled();
                $('#sourceTindakanChecklist').html(`
                    <div class="tindakan-source-empty text-danger">
                        Tindakan tidak dapat dimuat.
                    </div>
                `);
                return;
            }

            if (!items.length) {
                setSourceResultInfo('0 hasil');
                setSourceSelectAllDisabled();
                $('#sourceTindakanChecklist').html(`
                    <div class="tindakan-source-empty">
                        Tindakan tidak ditemukan.
                    </div>
                `);
                return;
            }

            const sourceLabel = $('#sourceTindakanFilter option:selected').text() || 'Semua sumber';
            setSourceResultInfo(formatAngka(items.length) + ' hasil / ' + sourceLabel);
            $('#sourceTindakanChecklist').html(items.map(function(item) {
                const checked = selectedTindakanMap.has(item.source_key) ? 'checked' : '';
                const checkedClass = checked ? ' is-checked' : '';
                const sourceKey = item.source_key ? `
                    <span class="tindakan-source-meta d-block">
                        Source key: ${escapeHtml(item.source_key)}
                    </span>
                ` : '';
                const parent = item.parent_label && item.parent_label !== '-' ? `
                    <span class="tindakan-source-meta d-block">
                        Judul: ${escapeHtml(item.parent_label)}
                    </span>
                ` : '';
                const pj = item.pj_label && item.pj_label !== '-' ? `
                    <span class="tindakan-source-meta d-block">
                        PJ: ${escapeHtml(item.pj_label)}
                    </span>
                ` : '';

                return `
                    <label class="tindakan-check-option${checkedClass}">
                        <input type="checkbox" class="form-check-input tindakan-source-check"
                            value="${escapeHtml(item.source_key)}" ${checked}>
                        <span>
                            <span class="tindakan-source-badge mb-1">${escapeHtml(item.sumber_label || '-')}</span>
                            <span class="tindakan-source-badge active mb-1">Aktif</span>
                            <span class="tindakan-source-title d-block">
                                ${escapeHtml(item.kd_tindakan || '-')} - ${escapeHtml(item.nm_tindakan || '-')}
                            </span>
                            ${sourceKey}
                            ${parent}
                            ${pj}
                        </span>
                    </label>
                `;
            }).join(''));

            updateSourceSelectAllState();
        }

        function searchSourceTindakan(keyword) {
            const query = String(keyword || '').trim();

            if (!$('#jenisTindakanSelect').val()) {
                renderSourceChecklist('disabled');
                return;
            }

            if (query.length < 2) {
                lastSourceResults = [];
                renderSourceChecklist('too-short');
                return;
            }

            if (sourceSearchRequest) {
                sourceSearchRequest.abort();
                sourceSearchRequest = null;
            }

            lastSourceResults = [];
            renderSourceChecklist('loading');

            sourceSearchRequest = $.ajax({
                url: searchTindakanUrl,
                method: 'GET',
                data: {
                    q: query,
                    source: $('#sourceTindakanFilter').val()
                },
                success: function(response) {
                    lastSourceResults = response.results || [];
                    renderSourceChecklist('ready', lastSourceResults);
                },
                error: function(xhr) {
                    if (xhr.statusText === 'abort') {
                        return;
                    }

                    renderSourceChecklist('error');
                },
                complete: function() {
                    sourceSearchRequest = null;
                }
            });
        }

        function renderDetailPanel(data) {
            return `
                <div class="skor-expand-panel" data-jenis-id="${data.id}"
                    data-jenis-code="${escapeHtml(data.kode || '-')}"
                    data-jenis-name="${escapeHtml(data.jenis || '-')}">
                    <div class="skor-expand-head">
                        <div class="skor-panel-title">
                            <div class="text-muted small fw-semibold">RINCIAN MAPPING TINDAKAN</div>
                            <div class="fw-semibold text-dark">${escapeHtml(data.jenis || '-')}</div>
                            <div class="small text-muted">${escapeHtml(data.kode || '-')}</div>
                        </div>

                        <div class="skor-panel-actions">
                            <span class="badge bg-white text-secondary border skor-panel-count">
                                ${formatAngka(data.jumlah_tindakan || 0)} tindakan
                            </span>
                            <button type="button" class="btn btn-sm btn-primary btn-inline-add-tindakan">
                                <i class="mdi mdi-plus"></i>
                                Tambah
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-copy-mapping-tindakan">
                                <i class="mdi mdi-content-copy"></i>
                                Copy
                            </button>
                        </div>
                    </div>

                    <div class="skor-editor-slot"></div>
                    <div class="tindakan-detail-tools">
                        <div class="input-group input-group-sm tindakan-detail-search">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="mdi mdi-magnify text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0 search-mapped-tindakan"
                                placeholder="Cari tindakan yang sudah termapping...">
                            <button type="button" class="btn btn-light border clear-mapped-search" disabled>
                                <i class="mdi mdi-close"></i>
                            </button>
                        </div>

                        <div class="tindakan-bulk-tools">
                            <div class="form-check mb-0">
                                <input class="form-check-input check-all-mapped-visible" type="checkbox"
                                    id="checkMapped-${data.id}" disabled>
                                <label class="form-check-label small fw-semibold" for="checkMapped-${data.id}">
                                    Pilih yang tampil
                                </label>
                            </div>

                            <span class="tindakan-bulk-selected">
                                <span class="mapped-selected-count">0</span> dipilih
                            </span>

                            <button type="button" class="btn btn-sm btn-outline-danger delete-selected-mapping-tindakan" disabled>
                                <i class="mdi mdi-trash-can-outline"></i>
                                Hapus Dipilih
                            </button>

                            <button type="button" class="btn btn-sm btn-danger delete-all-mapping-tindakan" disabled>
                                <i class="mdi mdi-delete-sweep-outline"></i>
                                Hapus Semua
                            </button>

                            <span class="tindakan-detail-count">
                                <span class="tindakan-filter-count">${formatAngka(data.jumlah_tindakan || 0)} tampil</span>
                            </span>
                        </div>
                    </div>
                    <div class="tindakan-list">
                        <div class="text-muted small py-2">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Memuat rincian tindakan...
                        </div>
                    </div>
                </div>
            `;
        }

        function renderTindakanList(items) {
            if (!items || items.length === 0) {
                return `
                    <div class="skor-empty-state">
                        <div class="fw-semibold mb-1">Belum ada tindakan untuk jenis ini</div>
                        <div class="small mb-3">Tambahkan tindakan langsung dari panel ini.</div>
                        <button type="button" class="btn btn-sm btn-primary btn-inline-add-tindakan">
                            <i class="mdi mdi-plus"></i>
                            Tambah tindakan
                        </button>
                    </div>
                `;
            }

            return `
                <div class="tindakan-expand-scroll">
                    <div class="skor-list-grid">
                    ${items.map(function(item) {
                        const parent = item.parent_label && item.parent_label !== '-' ? `
                            <div class="small text-muted">
                                Judul: ${escapeHtml(item.parent_label)}
                            </div>
                        ` : '';
                        const searchText = [
                            item.sumber_label,
                            item.kd_tindakan,
                            item.nm_tindakan,
                            item.pj_label,
                            item.parent_label
                        ].join(' ').toLowerCase();

                        return `
                            <div class="skor-score-card tindakan-mapped-card"
                                data-id="${item.id}"
                                data-source-key="${escapeHtml(item.source_key)}"
                                data-source="${escapeHtml(item.sumber_tindakan || '')}"
                                data-source-label="${escapeHtml(item.sumber_label || '-')}"
                                data-display-text="${escapeHtml(item.display_text)}"
                                data-title="${escapeHtml((item.kd_tindakan || '-') + ' - ' + (item.nm_tindakan || '-'))}"
                                data-parent-label="${escapeHtml(item.parent_label || '-')}"
                                data-pj-label="${escapeHtml(item.pj_label || '-')}"
                                data-search="${escapeHtml(searchText)}">
                                <label class="tindakan-mapped-check-wrap" title="Pilih tindakan">
                                    <input type="checkbox" class="form-check-input mapped-tindakan-check"
                                        value="${escapeHtml(item.id)}">
                                </label>

                                <div class="skor-score-icon">
                                    <i class="mdi mdi-medical-bag"></i>
                                </div>

                                <div class="skor-score-meta">
                                    <div class="skor-score-subtitle">
                                        <span class="tindakan-source-badge">${escapeHtml(item.sumber_label || '-')}</span>
                                        <span class="ms-1">PJ: ${escapeHtml(item.pj_label || '-')}</span>
                                    </div>
                                    <div class="skor-score-title">
                                        ${escapeHtml(item.kd_tindakan || '-')} - ${escapeHtml(item.nm_tindakan || '-')}
                                    </div>
                                    ${parent}
                                </div>

                                <div class="skor-score-actions">
                                    <button type="button" class="btn btn-light btn-sm edit-mapping-tindakan" title="Edit tindakan">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-light btn-sm text-danger delete-mapping-tindakan" data-id="${item.id}" title="Hapus tindakan">
                                        <i class="mdi mdi-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    }).join('')}
                    </div>

                    <div class="tindakan-filter-empty d-none mt-2">
                        Tindakan termapping tidak ditemukan dari kata kunci ini.
                    </div>
                </div>
            `;
        }

        function filterMappedTindakan(panel, keyword = '') {
            const query = String(keyword || '').toLowerCase().trim();
            const cards = panel.find('.tindakan-mapped-card');
            let visibleCount = 0;

            cards.each(function() {
                const haystack = String($(this).data('search') || '');
                const visible = !query || haystack.includes(query);

                $(this).toggle(visible);

                if (visible) {
                    visibleCount++;
                }
            });

            panel.find('.tindakan-filter-count').text(formatAngka(visibleCount) + ' tampil');
            panel.find('.tindakan-filter-empty').toggleClass('d-none', cards.length === 0 || visibleCount > 0);
            panel.find('.clear-mapped-search').prop('disabled', !query);
            updateMappedBulkState(panel);
        }

        function updatePanelTotals(panel, items) {
            const count = (items || []).length;

            panel.find('.skor-panel-count').text(formatAngka(count) + ' tindakan');
            panel.closest('tr').prev('tr').find('td').last().html(
                `<span class="fw-bold text-primary">${formatAngka(count)} tindakan</span>`
            );
        }

        function mappedSelectedIds(panel) {
            return panel.find('.mapped-tindakan-check:checked').map(function() {
                return $(this).val();
            }).get();
        }

        function updateMappedBulkState(panel) {
            const cards = panel.find('.tindakan-mapped-card');
            const checks = panel.find('.mapped-tindakan-check');
            const visibleChecks = checks.filter(function() {
                return $(this).closest('.tindakan-mapped-card').is(':visible');
            });
            const selectedCount = checks.filter(':checked').length;
            const visibleSelectedCount = visibleChecks.filter(':checked').length;

            panel.find('.mapped-selected-count').text(formatAngka(selectedCount));
            panel.find('.delete-selected-mapping-tindakan').prop('disabled', selectedCount === 0);
            panel.find('.delete-all-mapping-tindakan').prop('disabled', cards.length === 0);
            panel.find('.check-all-mapped-visible').prop({
                disabled: visibleChecks.length === 0,
                checked: visibleChecks.length > 0 && visibleSelectedCount === visibleChecks.length,
                indeterminate: visibleSelectedCount > 0 && visibleSelectedCount < visibleChecks.length
            });

            cards.each(function() {
                const card = $(this);
                card.toggleClass('is-selected', card.find('.mapped-tindakan-check').is(':checked'));
            });
        }

        function deleteMappingBulk(panel, options = {}) {
            const jenisId = panel.data('jenis-id');
            const deleteAll = Boolean(options.deleteAll);
            const mappingIds = deleteAll ? [] : mappedSelectedIds(panel);
            const count = deleteAll ? panel.find('.tindakan-mapped-card').length : mappingIds.length;

            if (!deleteAll && mappingIds.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Rincian tindakan wajib dipilih'
                });
                return;
            }

            if (deleteAll && count === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Belum ada rincian tindakan'
                });
                return;
            }

            Swal.fire({
                title: deleteAll ? 'Hapus semua rincian tindakan?' : 'Hapus rincian tindakan terpilih?',
                text: deleteAll ?
                    'Semua rincian pada jenis tindakan ini akan dihapus.' : formatAngka(count) +
                    ' rincian tindakan akan dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: deleteAll ? 'Ya, hapus semua' : 'Ya, hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function(result) {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: bulkDeleteTindakanUrl,
                    type: 'DELETE',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        jnsTindakan_id: jenisId,
                        delete_all: deleteAll ? 1 : 0,
                        mapping_ids: mappingIds
                    }),
                    beforeSend: function() {
                        panel.find(
                                '.delete-selected-mapping-tindakan, .delete-all-mapping-tindakan'
                                )
                            .prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.status === true) {
                            panel.find('.skor-editor-slot').empty();
                            refreshDetailPanel(panel, jenisId);
                            showToast('success', response.message);
                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: response.message ||
                                'Rincian tindakan tidak dapat dihapus'
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: xhr.status === 422 ? 'warning' : 'error',
                            title: xhr.responseJSON?.message ||
                                'Gagal menghapus rincian tindakan',
                            text: Object.values(xhr.responseJSON?.errors || {})[0]
                                ?.[0] || ''
                        });
                    },
                    complete: function() {
                        updateMappedBulkState(panel);
                    }
                });
            });
        }

        function refreshDetailPanel(panel, jenisId) {
            panel.find('.tindakan-list').html(`
                <div class="text-muted small py-2">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Memuat rincian tindakan...
                </div>
            `);

            return $.ajax({
                url: routeWithParam(byJenisUrl, ':id', jenisId),
                method: 'GET',
                success: function(items) {
                    panel.find('.tindakan-list').html(renderTindakanList(items || []));
                    updatePanelTotals(panel, items || []);
                    filterMappedTindakan(panel, panel.find('.search-mapped-tindakan').val());
                },
                error: function() {
                    panel.find('.tindakan-list').html(`
                        <div class="skor-empty-state text-danger">
                            Rincian tindakan tidak dapat dimuat.
                        </div>
                    `);
                }
            });
        }

        function renderInlineEditor(mode, data = {}) {
            const isEdit = mode === 'edit';
            const currentText = data.display_text ? `
                <div class="tindakan-inline-hint mt-1">
                    Saat ini: ${escapeHtml(data.display_text)}
                </div>
            ` : `
                <div class="tindakan-inline-hint mt-1">
                    Ketik minimal 3 huruf pada kotak pencarian tindakan.
                </div>
            `;

            return `
                <div class="skor-inline-editor tindakan-inline-editor" data-mode="${mode}" data-id="${data.id || ''}">
                    <div class="tindakan-inline-head">
                        <div>
                            <div class="tindakan-inline-title">
                                <i class="mdi ${isEdit ? 'mdi-pencil' : 'mdi-plus-circle-outline'}"></i>
                                ${isEdit ? 'Edit mapping tindakan' : 'Tambah tindakan pada jenis ini'}
                            </div>
                            ${currentText}
                        </div>
                        <span class="tindakan-source-badge">
                            Search Khanza
                        </span>
                    </div>

                    <div class="row g-2 align-items-end">
                        <div class="col-lg-3">
                            <label class="form-label mb-1">Sumber</label>
                            <select class="form-select form-select-sm inline-source-filter">
                                ${sourceOptions()}
                            </select>
                        </div>

                        <div class="col-lg-6">
                            <label class="form-label mb-1">Tindakan</label>
                            <select class="form-select form-select-sm inline-source-key"></select>
                        </div>

                        <div class="col-lg-3">
                            <div class="d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-sm btn-primary save-inline-tindakan">
                                    <i class="mdi mdi-content-save-outline"></i>
                                    Simpan
                                </button>
                                <button type="button" class="btn btn-sm btn-light cancel-inline-tindakan">
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        async function showInlineEditor(panel, mode, data = {}) {
            await loadSources();

            const slot = panel.find('.skor-editor-slot');

            slot.html(renderInlineEditor(mode, data));

            const select = slot.find('.inline-source-key');
            slot.find('.inline-source-filter').val(data.sumber_tindakan || '');

            if (data.source_key) {
                select.append(new Option(data.display_text || data.source_key, data.source_key, true,
                true));
            }

            initSourceSelect(select, $(document.body));
            select.trigger('change');

            setTimeout(function() {
                select.select2('open');
            }, 120);
        }

        function panelMappedItems(panel) {
            return panel.find('.tindakan-mapped-card').map(function() {
                const card = $(this);

                return {
                    id: card.data('id'),
                    source_label: card.data('source-label') || '-',
                    title: card.data('title') || card.data('display-text') || '-',
                    parent_label: card.data('parent-label') || '-',
                    pj_label: card.data('pj-label') || '-',
                    search_text: card.data('search') || ''
                };
            }).get();
        }

        function renderCopyEditor(panel) {
            const sourceJenisId = panel.data('jenis-id');
            const sourceTitle = (panel.data('jenis-code') || '-') + ' - ' + (panel.data('jenis-name') || '-');
            const items = panelMappedItems(panel);

            return `
                <div class="skor-inline-editor tindakan-inline-editor tindakan-copy-editor">
                    <div class="tindakan-copy-hero">
                        <div>
                            <div class="tindakan-copy-hero-title">
                                <i class="mdi mdi-content-copy"></i>
                                Copy rincian tindakan
                            </div>
                            <div class="tindakan-copy-hero-hint">
                                Pilih tujuan, saring bila perlu, lalu centang rincian yang akan dicopy.
                            </div>
                        </div>
                        <span class="tindakan-copy-count">
                            <i class="mdi mdi-check-circle-outline"></i>
                            <span class="copy-selected-count">${formatAngka(items.length)}</span> dipilih
                        </span>
                    </div>

                    <div class="tindakan-copy-body">
                        <div class="tindakan-copy-route">
                            <div class="tindakan-copy-route-card">
                                <div class="tindakan-copy-route-label">Dari</div>
                                <div class="tindakan-copy-route-title">${escapeHtml(sourceTitle)}</div>
                                <div class="small text-muted mt-1">${formatAngka(items.length)} rincian tersedia</div>
                            </div>

                            <div class="tindakan-copy-arrow">
                                <i class="mdi mdi-arrow-right"></i>
                            </div>

                            <div class="tindakan-copy-route-card bg-white">
                                <label class="tindakan-copy-route-label mb-1">Ke tujuan</label>
                                <select class="form-select form-select-sm copy-target-jenis">
                                    ${copyTargetOptions(sourceJenisId)}
                                </select>
                            </div>
                        </div>

                        <div class="tindakan-copy-toolbar">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="mdi mdi-magnify text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-start-0 copy-search-tindakan"
                                    placeholder="Cari rincian yang ingin dicopy...">
                            </div>

                            <div class="form-check mb-0">
                                <input class="form-check-input copy-check-all" type="checkbox" id="copyAll-${sourceJenisId}" checked>
                                <label class="form-check-label small fw-semibold" for="copyAll-${sourceJenisId}">
                                    Pilih semua
                                </label>
                            </div>

                            <div class="d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-sm btn-primary save-copy-mapping-tindakan">
                                    <i class="mdi mdi-content-save-outline"></i>
                                    Copy
                                </button>
                                <button type="button" class="btn btn-sm btn-light cancel-inline-tindakan">
                                    Batal
                                </button>
                            </div>
                        </div>

                        <div class="small text-muted mb-2">
                            <span class="copy-visible-count">${formatAngka(items.length)}</span> rincian tampil.
                            Rincian yang sudah ada di tujuan akan dilewati otomatis saat disimpan.
                        </div>

                        <div class="tindakan-copy-list">
                            ${items.map(function(item) {
                                const parent = item.parent_label && item.parent_label !== '-' ? `
                                    <span class="tindakan-source-meta d-block">
                                        Judul: ${escapeHtml(item.parent_label)}
                                    </span>
                                ` : '';

                                return `
                                    <label class="tindakan-copy-option is-checked"
                                        data-search="${escapeHtml(item.search_text)}">
                                        <input type="checkbox" class="form-check-input copy-mapping-tindakan-check"
                                            value="${escapeHtml(item.id)}" checked>
                                        <span>
                                            <span class="tindakan-copy-meta-row">
                                                <span class="tindakan-source-badge">${escapeHtml(item.source_label)}</span>
                                                <span class="tindakan-source-badge active">Siap copy</span>
                                            </span>
                                            <span class="tindakan-source-title d-block">
                                                ${escapeHtml(item.title)}
                                            </span>
                                            <span class="tindakan-source-meta d-block">
                                                PJ: ${escapeHtml(item.pj_label || '-')}
                                            </span>
                                            ${parent}
                                        </span>
                                    </label>
                                `;
                            }).join('')}
                        </div>

                        <div class="tindakan-copy-empty d-none mt-2">
                            Rincian tindakan tidak ditemukan dari kata kunci ini.
                        </div>
                    </div>
                </div>
            `;
        }

        async function showCopyEditor(panel) {
            await loadJenis();

            const items = panelMappedItems(panel);

            if (!items.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Belum ada rincian tindakan',
                    text: 'Tambahkan rincian dulu sebelum menggunakan fitur copy.'
                });
                return;
            }

            const slot = panel.find('.skor-editor-slot');
            slot.html(renderCopyEditor(panel));
            initSelect2(slot.find('.copy-target-jenis'), $(document.body), 'Pilih tujuan copy');
            updateCopyEditorState(slot.find('.tindakan-copy-editor'));
        }

        function updateCopyEditorState(editor) {
            const checks = editor.find('.copy-mapping-tindakan-check');
            const visibleChecks = checks.filter(function() {
                return $(this).closest('.tindakan-copy-option').is(':visible');
            });
            const checkedCount = checks.filter(':checked').length;
            const visibleCheckedCount = visibleChecks.filter(':checked').length;

            editor.find('.copy-selected-count').text(formatAngka(checkedCount));
            editor.find('.copy-check-all').prop({
                checked: visibleChecks.length > 0 && visibleCheckedCount === visibleChecks.length,
                indeterminate: visibleCheckedCount > 0 && visibleCheckedCount < visibleChecks.length,
                disabled: visibleChecks.length === 0
            });
            checks.each(function() {
                $(this).closest('.tindakan-copy-option').toggleClass('is-checked', $(this).is(
                    ':checked'));
            });
        }

        function filterCopyItems(editor, keyword = '') {
            const query = String(keyword || '').toLowerCase().trim();
            const options = editor.find('.tindakan-copy-option');
            let visibleCount = 0;

            options.each(function() {
                const option = $(this);
                const visible = !query || String(option.data('search') || '').includes(query);

                option.toggle(visible);

                if (visible) {
                    visibleCount++;
                }
            });

            editor.find('.copy-visible-count').text(formatAngka(visibleCount));
            editor.find('.tindakan-copy-empty').toggleClass('d-none', visibleCount > 0);
        }

        const tindakanTable = $('#tableTindakan').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route("masterData.mapping.mappingTindakan.getTindakanTable") }}",
                type: 'GET'
            },
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
                    className: 'text-end'
                }
            ]
        });

        $('.dataTables_filter').hide();

        $('#searchTindakan').on('keyup', function() {
            tindakanTable.search(this.value).draw();
        });

        $('#tableTindakan tbody').on('click', '.btn-detail-tindakan', function() {
            const tr = $(this).closest('tr');
            const row = tindakanTable.row(tr);
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

            const panel = tr.next('tr').find('.skor-expand-panel');
            refreshDetailPanel(panel, data.id);
        });

        $(document).on('input', '.search-mapped-tindakan', function() {
            filterMappedTindakan($(this).closest('.skor-expand-panel'), $(this).val());
        });

        $(document).on('click', '.clear-mapped-search', function() {
            const panel = $(this).closest('.skor-expand-panel');

            panel.find('.search-mapped-tindakan').val('').trigger('focus');
            filterMappedTindakan(panel, '');
        });

        modal.on('show.bs.modal', async function() {
            resetModal();

            try {
                await $.when(loadJenis(), loadSources());
                $('#jenisTindakanSelect').html(jenisOptions());
                $('#sourceTindakanFilter').html(sourceOptions()).val('').prop('disabled', true);
                initSelect2('#jenisTindakanSelect', modal, 'Pilih Jenis Tindakan');
                $('#searchTindakanSource').prop('disabled', true);
                $('#clearSearchTindakanSource').prop('disabled', true);
                setSourceSelectAllDisabled();
                renderSourceChecklist('disabled');
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal memuat data',
                    text: 'Master jenis tindakan tidak dapat dimuat.'
                });
            }
        });

        modal.on('hidden.bs.modal', function() {
            resetModal();
        });

        $('#jenisTindakanSelect').on('change', function() {
            const hasJenis = Boolean($(this).val());
            $('#sourceTindakanFilter').prop('disabled', !hasJenis);
            $('#searchTindakanSource').val('').prop('disabled', !hasJenis);
            $('#clearSearchTindakanSource').prop('disabled', true);
            setSourceSelectAllDisabled();
            selectedTindakanMap.clear();
            lastSourceResults = [];
            renderSourceChecklist(hasJenis ? 'too-short' : 'disabled');
            renderSelectedPreview();
            updateSelectedCount();
        });

        $('#searchTindakanSource').on('input', function() {
            const keyword = $(this).val();

            $('#clearSearchTindakanSource').prop('disabled', !keyword);

            if (searchTindakanTimer) {
                clearTimeout(searchTindakanTimer);
            }

            searchTindakanTimer = setTimeout(function() {
                searchSourceTindakan(keyword);
            }, 350);
        });

        $('#sourceTindakanFilter').on('change', function() {
            const keyword = $('#searchTindakanSource').val();

            lastSourceResults = [];
            setSourceSelectAllDisabled();
            searchSourceTindakan(keyword);
        });

        $('#clearSearchTindakanSource').on('click', function() {
            $('#searchTindakanSource').val('').trigger('focus');
            $(this).prop('disabled', true);
            lastSourceResults = [];
            renderSourceChecklist($('#jenisTindakanSelect').val() ? 'too-short' : 'disabled');
        });

        $(document).on('change', '.tindakan-source-check', function() {
            const sourceKey = $(this).val();
            const sourceItem = lastSourceResults.find(item => item.source_key === sourceKey) ||
                selectedTindakanMap.get(sourceKey);
            const checked = $(this).is(':checked');

            $(this).closest('.tindakan-check-option').toggleClass('is-checked', checked);

            if (checked) {
                if (sourceItem) {
                    selectedTindakanMap.set(sourceKey, sourceItem);
                }
            } else {
                selectedTindakanMap.delete(sourceKey);
            }

            updateSelectedCount();
            renderSelectedPreview();
            updateSourceSelectAllState();
        });

        $('#checkAllVisibleTindakan').on('change', function() {
            const checked = $(this).is(':checked');

            if (!lastSourceResults.length) {
                setSourceSelectAllDisabled();
                return;
            }

            lastSourceResults.forEach(function(item) {
                if (checked) {
                    selectedTindakanMap.set(item.source_key, item);
                    return;
                }

                selectedTindakanMap.delete(item.source_key);
            });

            renderSourceChecklist('ready', lastSourceResults);
            updateSelectedCount();
            renderSelectedPreview();
            updateSourceSelectAllState();
        });

        $(document).on('click', '.tindakan-selected-remove', function() {
            const sourceKey = $(this).data('source-key');

            selectedTindakanMap.delete(sourceKey);
            $('.tindakan-source-check').filter(function() {
                    return $(this).val() === String(sourceKey);
                })
                .prop('checked', false)
                .closest('.tindakan-check-option')
                .removeClass('is-checked');

            updateSelectedCount();
            renderSelectedPreview();
            updateSourceSelectAllState();
        });

        $('#tindakanForm').on('submit', function(e) {
            e.preventDefault();

            const jenisId = $('#jenisTindakanSelect').val();
            const sourceKeys = [...selectedTindakanMap.keys()];

            resetValidation();

            if (!jenisId) {
                $('#jenisTindakanSelect').addClass('is-invalid');
                $('#error-jnsTindakan_id').text('Jenis tindakan wajib dipilih');
                return;
            }

            if (sourceKeys.length === 0) {
                $('#error-source_keys').text('Tindakan wajib dipilih');
                Swal.fire({
                    icon: 'warning',
                    title: 'Tindakan wajib dipilih'
                });
                return;
            }

            Swal.fire({
                title: 'Simpan mapping tindakan?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, simpan',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function(result) {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: storeTindakanUrl,
                    method: 'POST',
                    data: {
                        jnsTindakan_id: jenisId,
                        source_keys: sourceKeys
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Menyimpan...',
                            text: 'Mohon tunggu sebentar',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => Swal.showLoading()
                        });
                    },
                    success: function(response) {
                        Swal.close();

                        if (response.status === true) {
                            modal.modal('hide');
                            tindakanTable.ajax.reload(null, false);
                            showToast('success', response.message);
                        }
                    },
                    error: function(xhr) {
                        Swal.close();

                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors || {};

                            if (errors.jnsTindakan_id) {
                                $('#jenisTindakanSelect').addClass('is-invalid');
                                $('#error-jnsTindakan_id').text(errors
                                    .jnsTindakan_id[0]);
                            }

                            if (errors.source_keys) {
                                $('#error-source_keys').text(errors.source_keys[0]);
                            }

                            Swal.fire({
                                icon: 'warning',
                                title: xhr.responseJSON.message ||
                                    'Data belum lengkap',
                                text: Object.values(errors)[0]?.[0] || ''
                            });
                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi Kesalahan',
                            text: xhr.responseJSON?.message ||
                                'Server error'
                        });
                    }
                });
            });
        });

        $(document).on('click', '.btn-inline-add-tindakan', function() {
            showInlineEditor($(this).closest('.skor-expand-panel'), 'add');
        });

        $(document).on('click', '.btn-copy-mapping-tindakan', function() {
            showCopyEditor($(this).closest('.skor-expand-panel'));
        });

        $(document).on('click', '.edit-mapping-tindakan', function() {
            const card = $(this).closest('.skor-score-card');
            const panel = $(this).closest('.skor-expand-panel');

            showInlineEditor(panel, 'edit', {
                id: card.data('id'),
                source_key: card.data('source-key'),
                sumber_tindakan: card.data('source'),
                display_text: card.data('display-text')
            });
        });

        $(document).on('click', '.cancel-inline-tindakan', function() {
            $(this).closest('.skor-expand-panel').find('.skor-editor-slot').empty();
        });

        $(document).on('change', '.inline-source-filter', function() {
            const editor = $(this).closest('.tindakan-inline-editor');
            editor.find('.inline-source-key').val(null).trigger('change');
        });

        $(document).on('change', '.copy-check-all', function() {
            const editor = $(this).closest('.tindakan-copy-editor');
            const checked = $(this).is(':checked');

            editor.find('.tindakan-copy-option:visible .copy-mapping-tindakan-check').prop('checked',
                checked);
            updateCopyEditorState(editor);
        });

        $(document).on('change', '.copy-mapping-tindakan-check', function() {
            updateCopyEditorState($(this).closest('.tindakan-copy-editor'));
        });

        $(document).on('input', '.copy-search-tindakan', function() {
            const editor = $(this).closest('.tindakan-copy-editor');

            filterCopyItems(editor, $(this).val());
            updateCopyEditorState(editor);
        });

        $(document).on('change', '.mapped-tindakan-check', function() {
            updateMappedBulkState($(this).closest('.skor-expand-panel'));
        });

        $(document).on('change', '.check-all-mapped-visible', function() {
            const panel = $(this).closest('.skor-expand-panel');
            const checked = $(this).is(':checked');

            panel.find('.tindakan-mapped-card:visible .mapped-tindakan-check').prop('checked', checked);
            updateMappedBulkState(panel);
        });

        $(document).on('click', '.delete-selected-mapping-tindakan', function() {
            deleteMappingBulk($(this).closest('.skor-expand-panel'));
        });

        $(document).on('click', '.delete-all-mapping-tindakan', function() {
            deleteMappingBulk($(this).closest('.skor-expand-panel'), {
                deleteAll: true
            });
        });

        $(document).on('click', '.save-copy-mapping-tindakan', function() {
            const btn = $(this);
            const editor = btn.closest('.tindakan-copy-editor');
            const panel = btn.closest('.skor-expand-panel');
            const targetJenisId = editor.find('.copy-target-jenis').val();
            const mappingIds = editor.find('.copy-mapping-tindakan-check:checked').map(function() {
                return $(this).val();
            }).get();

            if (!targetJenisId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tujuan copy wajib dipilih'
                });
                return;
            }

            if (!mappingIds.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Rincian tindakan wajib dipilih'
                });
                return;
            }

            Swal.fire({
                title: 'Copy rincian tindakan?',
                text: formatAngka(mappingIds.length) +
                    ' rincian akan dicopy ke jenis tindakan tujuan.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, copy',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function(result) {
                if (!result.isConfirmed) return;

                btn.prop('disabled', true).html(`
                    <span class="spinner-border spinner-border-sm"></span>
                    Copy
                `);

                $.ajax({
                    url: copyTindakanUrl,
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        target_jnsTindakan_id: targetJenisId,
                        mapping_ids: mappingIds
                    }),
                    success: function(response) {
                        if (response.status === true) {
                            editor.remove();
                            tindakanTable.ajax.reload(null, false);
                            const targetPanel = $(
                                `.skor-expand-panel[data-jenis-id="${targetJenisId}"]`
                                );

                            if (targetPanel.length) {
                                refreshDetailPanel(targetPanel, targetJenisId);
                            }

                            showToast((response.data?.copied || 0) > 0 ? 'success' :
                                'info',
                                response.message);
                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: response.message ||
                                'Rincian tindakan tidak dapat dicopy'
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: xhr.status === 422 ? 'warning' : 'error',
                            title: xhr.responseJSON?.message ||
                                'Gagal copy tindakan',
                            text: Object.values(xhr.responseJSON?.errors ||
                            {})[0]?.[0] || ''
                        });
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(`
                            <i class="mdi mdi-content-save-outline"></i>
                            Copy
                        `);
                    }
                });
            });
        });

        $(document).on('click', '.save-inline-tindakan', function() {
            const btn = $(this);
            const editor = btn.closest('.skor-inline-editor');
            const panel = btn.closest('.skor-expand-panel');
            const jenisId = panel.data('jenis-id');
            const mode = editor.data('mode');
            const id = editor.data('id');
            const sourceKey = editor.find('.inline-source-key').val();

            if (!sourceKey) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tindakan wajib dipilih'
                });
                return;
            }

            btn.prop('disabled', true).html(`
                <span class="spinner-border spinner-border-sm"></span>
                Simpan
            `);

            $.ajax({
                url: mode === 'edit' ? routeWithParam(updateTindakanUrl, ':id', id) :
                    storeTindakanUrl,
                method: mode === 'edit' ? 'PUT' : 'POST',
                data: mode === 'edit' ? {
                    source_key: sourceKey
                } : {
                    jnsTindakan_id: jenisId,
                    source_keys: [sourceKey]
                },
                success: function(response) {
                    if (response.status === true) {
                        editor.remove();
                        refreshDetailPanel(panel, jenisId);
                        showToast('success', response.message);
                        return;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: response.message || 'Data tidak dapat disimpan'
                    });
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: xhr.status === 422 ? 'warning' : 'error',
                        title: xhr.responseJSON?.message ||
                            'Gagal menyimpan tindakan',
                        text: Object.values(xhr.responseJSON?.errors || {})[0]?.[
                            0
                        ] || ''
                    });
                },
                complete: function() {
                    btn.prop('disabled', false).html(`
                        <i class="mdi mdi-content-save-outline"></i>
                        Simpan
                    `);
                }
            });
        });

        $(document).on('click', '.delete-mapping-tindakan', function() {
            const id = $(this).data('id');
            const panel = $(this).closest('.skor-expand-panel');
            const jenisId = panel.data('jenis-id');

            Swal.fire({
                title: 'Hapus mapping tindakan?',
                text: 'Tindakan ini akan dihapus dari jenis tindakan.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function(result) {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: routeWithParam(deleteTindakanUrl, ':id', id),
                    type: 'DELETE',
                    success: function(response) {
                        if (response.status === true) {
                            panel.find('.skor-editor-slot').empty();
                            refreshDetailPanel(panel, jenisId);
                            showToast('success', response.message);
                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: response.message ||
                                'Data tidak dapat dihapus'
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: xhr.responseJSON?.message ||
                                'Terjadi kesalahan saat menghapus mapping tindakan.'
                        });
                    }
                });
            });
        });
    });
</script>
