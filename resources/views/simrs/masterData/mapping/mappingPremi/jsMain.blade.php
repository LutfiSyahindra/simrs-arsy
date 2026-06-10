<script>
    $(function() {
        const modal = $('#premiModal');
        const container = $('#premiMappingContainer');
        const storeUrl = "{{ route("masterData.mapping.mappingPremi.store") }}";
        const byPremiUrl = "{{ route("masterData.mapping.mappingPremi.byPremi", ":id") }}";
        const updateUrl = "{{ route("masterData.mapping.mappingPremi.update", ":id") }}";
        const deleteUrl = "{{ route("masterData.mapping.mappingPremi.delete", ":id") }}";
        let tindakanList = [];
        let premiList = [];
        let tindakanRequest = null;
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

        function formatRupiah(value) {
            return 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(value) || 0);
        }

        function formatMappingValue(item) {
            return item.jenis === 'nominal'
                ? formatRupiah(item.nilai)
                : `${Number(item.nilai) || 0}%`;
        }

        function applyValueMode(scope) {
            const jenis = scope.find('.mapping-jenis, .inline-jenis').val() || 'persen';
            const input = scope.find('.mapping-nilai, .inline-nilai');
            const addon = scope.find('.mapping-value-addon, .inline-value-addon');
            const label = scope.find('.mapping-value-label, .inline-value-label');
            const help = scope.find('.inline-value-help');

            input.attr('min', 0);

            if (jenis === 'nominal') {
                input.attr('max', 2147483647).attr('placeholder', '0');
                addon.text('Rp');
                label.text('Nilai Nominal');
                help.text('Masukkan nominal premi dalam Rupiah.');
                return;
            }

            input.attr('max', 100).attr('placeholder', '0');
            addon.text('%');
            label.text('Nilai Persen');
            help.text('Nilai yang diizinkan 0 sampai 100.');
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
                            <div class="col-md-5">
                                <label class="form-label">Jenis Tindakan</label>
                                <select name="mappings[${index}][jnsTindakan_id]"
                                    class="form-select mapping-tindakan-select">
                                    ${tindakanOptions(data.jnsTindakan_id)}
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Jenis Nilai</label>
                                <select name="mappings[${index}][jenis]" class="form-select mapping-jenis">
                                    ${jenisOptions(data.jenis || 'persen')}
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mapping-value-label">Nilai Persen</label>
                                <div class="input-group">
                                    <span class="input-group-text mapping-value-addon">%</span>
                                    <input type="number" name="mappings[${index}][nilai]"
                                        class="form-control mapping-nilai" min="0" step="1"
                                        value="${escapeHtml(data.nilai ?? '')}" placeholder="0">
                                </div>
                            </div>
                            <div class="col-md-1 text-end">
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

        function fillPremiSelect() {
            const select = $('#jenisPremiSelect');
            const current = select.val();
            let options = '<option value="">-- Pilih Jenis Premi --</option>';

            premiList.forEach(function(item) {
                options += `<option value="${item.id}">${escapeHtml(item.kode)} - ${escapeHtml(item.jenis)}</option>`;
            });

            select.html(options).val(current).trigger('change.select2');
        }

        function renderDetailPanel(data) {
            return `
                <div class="premi-expand-panel" data-premi-id="${data.id}">
                    <div class="premi-expand-head">
                        <div>
                            <div class="text-muted small fw-semibold">RINCIAN MAPPING PREMI</div>
                            <div class="fw-semibold text-dark">${escapeHtml(data.kode)} - ${escapeHtml(data.jenis)}</div>
                        </div>
                        <div class="premi-panel-actions">
                            <span class="badge bg-white text-secondary border premi-panel-count">
                                ${Number(data.jumlah_tindakan) || 0} tindakan
                            </span>
                            <button type="button" class="btn btn-sm btn-primary btn-inline-add-premi">
                                <i class="mdi mdi-plus"></i> Tambah
                            </button>
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

        function renderMappingList(items) {
            if (!items || items.length === 0) {
                return `
                    <div class="premi-empty-state">
                        <div class="fw-semibold mb-1">Belum ada jenis tindakan</div>
                        <div class="small">Tambahkan jenis tindakan serta nilai persen atau nominal dari panel ini.</div>
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
                                data-nilai="${item.nilai}">
                                <div class="premi-card-icon">
                                    <i class="mdi mdi-medical-bag"></i>
                                </div>
                                <div class="premi-card-meta">
                                    <div class="small text-muted">${escapeHtml(item.kode_tindakan)}</div>
                                    <div class="premi-card-title">${escapeHtml(item.jenis_tindakan)}</div>
                                    <span class="premi-kind-badge ${escapeHtml(item.jenis)}">
                                        ${escapeHtml(item.jenis)}
                                    </span>
                                </div>
                                <div class="premi-value ${escapeHtml(item.jenis)}">
                                    ${formatMappingValue(item)}
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

        function updatePanelTotals(panel, items) {
            const totalPersen = (items || [])
                .filter(item => item.jenis === 'persen')
                .reduce((sum, item) => sum + (Number(item.nilai) || 0), 0);
            const totalNominal = (items || [])
                .filter(item => item.jenis === 'nominal')
                .reduce((sum, item) => sum + (Number(item.nilai) || 0), 0);
            const count = (items || []).length;
            const summaries = [];

            if (totalPersen > 0) summaries.push(`${totalPersen}%`);
            if (totalNominal > 0) summaries.push(formatRupiah(totalNominal));

            panel.find('.premi-panel-count').text(count + ' tindakan');
            panel.closest('tr').prev('tr').find('td').last().html(
                `<span class="fw-bold text-primary">${count} tindakan</span>
                ${summaries.length ? `<div class="small text-muted">${summaries.join(' + ')}</div>` : ''}`
            );
        }

        function refreshDetailPanel(panel) {
            const premiId = panel.data('premi-id');

            return $.get(routeWithId(byPremiUrl, premiId), function(items) {
                panel.find('.premi-mapping-list').html(renderMappingList(items));
                updatePanelTotals(panel, items);
            }).fail(function(xhr) {
                panel.find('.premi-mapping-list').html(`
                    <div class="premi-empty-state text-danger">
                        ${escapeHtml(xhr.responseJSON?.message || 'Rincian mapping tidak dapat dimuat.')}
                    </div>
                `);
            });
        }

        function showInlineEditor(panel, mode, data = {}) {
            const isEdit = mode === 'edit';
            const title = isEdit ? 'Edit Mapping Premi' : 'Tambah Mapping Premi';
            const subtitle = isEdit
                ? 'Ubah tindakan, jenis nilai, atau nilai premi pada mapping ini.'
                : 'Tambahkan tindakan beserta nilai persen atau nominal.';

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
                            <div class="col-lg-6">
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
                                    <label class="form-label">Jenis Nilai</label>
                                    <div class="premi-inline-control">
                                        <select class="form-select inline-jenis">
                                            ${jenisOptions(data.jenis || 'persen')}
                                        </select>
                                    </div>
                                    <span class="premi-inline-help">Persen atau nominal.</span>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="premi-inline-field">
                                    <label class="form-label inline-value-label">Nilai Persen</label>
                                    <div class="premi-inline-control">
                                        <div class="input-group">
                                            <span class="input-group-text fw-bold inline-value-addon">%</span>
                                            <input type="number" class="form-control inline-nilai" min="0"
                                                step="1" value="${escapeHtml(data.nilai ?? '')}" placeholder="0">
                                        </div>
                                    </div>
                                    <span class="premi-inline-help inline-value-help">
                                        Nilai yang diizinkan 0 sampai 100.
                                    </span>
                                </div>
                            </div>
                        </div>
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

        container.on('click', '.remove-premi-row', function() {
            if (container.find('.premi-form-row').length === 1) {
                showToast('warning', 'Minimal satu jenis tindakan');
                return;
            }

            $(this).closest('.premi-form-row').remove();
        });

        container.on('change', '.mapping-jenis', function() {
            applyValueMode($(this).closest('.premi-form-row'));
        });

        $(document).on('change', '.inline-jenis', function() {
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
                const jenis = row.find('.mapping-jenis').val();
                const nilai = row.find('.mapping-nilai').val();

                if (!jenis || nilai === '' || Number(nilai) < 0) {
                    valueError = 'Jenis nilai dan nilai premi wajib diisi.';
                    return false;
                }

                if (jenis === 'persen' && Number(nilai) > 100) {
                    valueError = 'Nilai persen maksimal 100%.';
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

                    $('#error-mappings').text(errors.mappings?.[0] || '');
                    Swal.fire('Gagal', message, xhr.status === 422 ? 'warning' : 'error');
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

        $(document).on('click', '.edit-mapping-premi', function() {
            const card = $(this).closest('.premi-mapping-card');
            const panel = $(this).closest('.premi-expand-panel');
            const data = {
                id: card.attr('data-id'),
                jnsTindakan_id: card.attr('data-tindakan-id'),
                jenis: card.attr('data-jenis'),
                nilai: card.attr('data-nilai')
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
            const jenis = editor.find('.inline-jenis').val();
            const nilai = editor.find('.inline-nilai').val();

            if (!tindakanId || !jenis || nilai === '' || Number(nilai) < 0) {
                Swal.fire('Data belum lengkap', 'Pilih tindakan, jenis nilai, dan isi nilai premi.', 'warning');
                return;
            }

            if (jenis === 'persen' && Number(nilai) > 100) {
                Swal.fire('Nilai tidak valid', 'Nilai persen maksimal 100%.', 'warning');
                return;
            }

            const data = mode === 'edit' ? {
                jnsTindakan_id: tindakanId,
                jenis: jenis,
                nilai: nilai
            } : {
                jnsPremi_id: panel.data('premi-id'),
                mappings: [{
                    jnsTindakan_id: tindakanId,
                    jenis: jenis,
                    nilai: nilai
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
