<script>
    $(function() {
        const modal = $('#unitPegawaiModal');
        const byPegawaiUrl = "{{ route("masterData.mapping.mappingUnit.getByPegawaiUnit", ":nik") }}";
        const updateUnitUrl = "{{ route("masterData.mapping.mappingUnit.update", ":id") }}";
        const deleteUnitUrl = "{{ route("masterData.mapping.mappingUnit.delete", ":id") }}";
        const storeUnitUrl = "{{ route("masterData.mapping.mappingUnit.store") }}";

        let pegawaiList = [];
        let pegawaiListRequest = null;
        let unitList = [];
        let unitListRequest = null;

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });

        function initSelect2(el, parentModal, placeholder = '-- Pilih --') {
            if ($(el).hasClass('select2-hidden-accessible')) {
                $(el).select2('destroy');
            }

            $(el).select2({
                dropdownParent: $(parentModal),
                width: '100%',
                placeholder: placeholder,
                allowClear: true
            });
        }

        function initUnitDropify() {
            const input = $('#unitDropify');

            if (!input.length || typeof input.dropify !== 'function') {
                return;
            }

            if (!input.data('dropify')) {
                input.dropify();
            }
        }

        function resetUnitDropify() {
            const input = $('#unitDropify');
            const dropify = input.data('dropify');

            if (dropify) {
                dropify.resetPreview();
                dropify.clearElement();
                return;
            }

            input.val('');
        }

        function formatAngka(value) {
            return new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 0
            }).format(Number(value) || 0);
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function statusLabel(status) {
            switch (status) {
                case 'T':
                    return 'Tetap';
                case 'FT':
                    return 'Kontrak';
                case 'PT':
                    return 'Part Time';
                case 'MT':
                    return 'Mitra';
                default:
                    return status || '-';
            }
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

        function loadPegawai() {
            if (pegawaiList.length) {
                return $.Deferred().resolve(pegawaiList).promise();
            }

            if (pegawaiListRequest) {
                return pegawaiListRequest;
            }

            pegawaiListRequest = $.ajax({
                url: "{{ route("masterData.mapping.mappingUnit.getPegawai") }}",
                method: 'GET',
                success: function(res) {
                    pegawaiList = res || [];
                },
                complete: function() {
                    pegawaiListRequest = null;
                }
            });

            return pegawaiListRequest;
        }

        function loadUnit() {
            if (unitList.length) {
                return $.Deferred().resolve(unitList).promise();
            }

            if (unitListRequest) {
                return unitListRequest;
            }

            unitListRequest = $.ajax({
                url: "{{ route("masterData.mapping.mappingUnit.guideUnit") }}",
                method: 'GET',
                success: function(res) {
                    unitList = res || [];
                },
                complete: function() {
                    unitListRequest = null;
                }
            });

            return unitListRequest;
        }

        function jenisOptions() {
            const jenisList = [...new Set(unitList.map(item => item.jenis).filter(Boolean))];
            let options = '<option value="">-- Pilih Jenis Unit --</option>';

            jenisList.forEach(function(jenis) {
                options += `<option value="${escapeHtml(jenis)}">${escapeHtml(jenis)}</option>`;
            });

            return options;
        }

        function unitOptions(jenis, selectedId = null) {
            let options = '<option value="">-- Pilih Nama Unit --</option>';

            unitList
                .filter(item => item.jenis === jenis)
                .forEach(function(item) {
                    const selected = String(item.id) === String(selectedId) ? 'selected' : '';
                    options += `
                        <option value="${item.id}" data-kode="${escapeHtml(item.kode)}" ${selected}>
                            ${escapeHtml(item.kode)} - ${escapeHtml(item.keterangan)}
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
            $('#unitPegawaiForm')[0].reset();
            $('#unitPegawaiId').val('');
            $('#jenisUnitSelect').html(jenisOptions());
            $('#unitSelect').html('<option value="">-- Pilih Nama Unit --</option>');
            $('#searchPegawaiChecklist').val('');
            $('#checkAllPegawai').prop({
                checked: false,
                indeterminate: false
            });
            $('#pegawaiCheckedCount').text('0');
            $('#pegawaiChecklist').html(`
                <div class="unit-pegawai-empty">
                    Pilih unit untuk menampilkan pegawai.
                </div>
            `);
            resetValidation();
        }

        function renderPegawaiChecklist() {
            const selectedUnit = $('#unitSelect').val();

            $('#checkAllPegawai').prop({
                checked: false,
                indeterminate: false
            });
            $('#pegawaiCheckedCount').text('0');

            if (!selectedUnit) {
                $('#pegawaiChecklist').html(`
                    <div class="unit-pegawai-empty">
                        Pilih unit untuk menampilkan pegawai.
                    </div>
                `);
                return;
            }

            if (!pegawaiList.length) {
                $('#pegawaiChecklist').html(`
                    <div class="unit-pegawai-empty">
                        Pegawai aktif belum tersedia.
                    </div>
                `);
                return;
            }

            $('#pegawaiChecklist').html(pegawaiList.map(function(pegawai) {
                const status = statusLabel(pegawai.stts_kerja);
                const searchText = [
                    pegawai.nik,
                    pegawai.nama,
                    pegawai.jbtn,
                    status
                ].join(' ').toLowerCase();

                return `
                    <label class="unit-pegawai-option" data-search="${escapeHtml(searchText)}">
                        <input type="checkbox" name="nik[]" class="form-check-input pegawai-check"
                            value="${escapeHtml(pegawai.nik)}">
                        <span>
                            <span class="unit-pegawai-name">${escapeHtml(pegawai.nama || '-')}</span>
                            <span class="unit-pegawai-meta d-block">
                                ${escapeHtml(pegawai.nik || '-')} / ${escapeHtml(pegawai.jbtn || '-')} / ${escapeHtml(status)}
                            </span>
                        </span>
                    </label>
                `;
            }).join(''));
        }

        function visiblePegawaiOptions() {
            return $('#pegawaiChecklist .unit-pegawai-option').filter(function() {
                return $(this).css('display') !== 'none';
            });
        }

        function updatePegawaiCheckedCount() {
            const checkedCount = $('.pegawai-check:checked').length;
            const visibleChecks = visiblePegawaiOptions().find('.pegawai-check');
            const visibleChecked = visibleChecks.filter(':checked').length;

            $('#pegawaiCheckedCount').text(formatAngka(checkedCount));
            $('#checkAllPegawai').prop('checked', visibleChecks.length > 0 && visibleChecked === visibleChecks.length);
            $('#checkAllPegawai').prop('indeterminate', visibleChecked > 0 && visibleChecked < visibleChecks.length);
        }

        function filterPegawaiChecklist(keyword) {
            const query = String(keyword || '').toLowerCase().trim();

            $('#pegawaiChecklist .unit-pegawai-option').each(function() {
                const haystack = String($(this).data('search') || '');
                $(this).toggle(haystack.includes(query));
            });

            if (visiblePegawaiOptions().length === 0 && $('#unitSelect').val()) {
                if (!$('#pegawaiChecklist .unit-pegawai-empty.filter-empty').length) {
                    $('#pegawaiChecklist').append(`
                        <div class="unit-pegawai-empty filter-empty">
                            Pegawai tidak ditemukan.
                        </div>
                    `);
                }
            } else {
                $('#pegawaiChecklist .filter-empty').remove();
            }

            updatePegawaiCheckedCount();
        }

        function renderDetailPanel(data) {
            return `
                <div class="skor-expand-panel" data-nik="${escapeHtml(data.nik)}">
                    <div class="skor-expand-head">
                        <div class="skor-panel-title">
                            <div class="text-muted small fw-semibold">RINCIAN UNIT</div>
                            <div class="fw-semibold text-dark">${escapeHtml(data.nama_pegawai || '-')}</div>
                            <div class="small text-muted">
                                ${escapeHtml(data.nik || '-')} / ${escapeHtml(data.jabatan || '-')}
                            </div>
                        </div>

                        <div class="skor-panel-actions">
                            <span class="badge bg-white text-secondary border skor-panel-count">
                                ${formatAngka(data.jumlah_unit || 0)} unit
                            </span>
                            <button type="button" class="btn btn-sm btn-primary btn-inline-add-unit">
                                <i class="mdi mdi-plus"></i>
                                Tambah
                            </button>
                        </div>
                    </div>

                    <div class="skor-editor-slot"></div>
                    <div class="unit-list">
                        <div class="text-muted small py-2">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Memuat rincian unit...
                        </div>
                    </div>
                </div>
            `;
        }

        function renderUnitList(units) {
            if (!units || units.length === 0) {
                return `
                    <div class="skor-empty-state">
                        <div class="fw-semibold mb-1">Belum ada unit untuk pegawai ini</div>
                        <div class="small mb-3">Tambahkan unit langsung dari panel ini.</div>
                        <button type="button" class="btn btn-sm btn-primary btn-inline-add-unit">
                            <i class="mdi mdi-plus"></i>
                            Tambah unit
                        </button>
                    </div>
                `;
            }

            return `
                <div class="skor-list-grid">
                    ${units.map(function(unit) {
                        return `
                            <div class="skor-score-card"
                                data-id="${unit.id}"
                                data-unit-id="${unit.unit_id}"
                                data-jenis="${escapeHtml(unit.jenis)}">
                                <div class="skor-score-icon">
                                    <i class="mdi mdi-office-building-outline"></i>
                                </div>

                                <div class="skor-score-meta">
                                    <div class="skor-score-subtitle">${escapeHtml(unit.jenis || '-')}</div>
                                    <div class="skor-score-title">
                                        ${escapeHtml(unit.kode || '-')} - ${escapeHtml(unit.keterangan || '-')}
                                    </div>
                                </div>

                                <div class="skor-score-actions">
                                    <button type="button" class="btn btn-light btn-sm edit-unit-pegawai" title="Edit unit">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-light btn-sm text-danger delete-unit-pegawai" data-id="${unit.id}" title="Hapus unit">
                                        <i class="mdi mdi-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }

        function updatePanelTotals(panel, units) {
            const count = (units || []).length;

            panel.find('.skor-panel-count').text(formatAngka(count) + ' unit');
            panel.closest('tr').prev('tr').find('td').last().html(
                `<span class="fw-bold text-primary">${formatAngka(count)} unit</span>`
            );
        }

        function refreshDetailPanel(panel, nik) {
            panel.find('.unit-list').html(`
                <div class="text-muted small py-2">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Memuat rincian unit...
                </div>
            `);

            return $.ajax({
                url: routeWithParam(byPegawaiUrl, ':nik', nik),
                method: 'GET',
                success: function(units) {
                    panel.find('.unit-list').html(renderUnitList(units || []));
                    updatePanelTotals(panel, units || []);
                },
                error: function() {
                    panel.find('.unit-list').html(`
                        <div class="skor-empty-state text-danger">
                            Rincian unit tidak dapat dimuat.
                        </div>
                    `);
                }
            });
        }

        function renderInlineEditor(mode, nik, data = {}) {
            const isEdit = mode === 'edit';

            return `
                <div class="skor-inline-editor" data-mode="${mode}" data-id="${data.id || ''}" data-nik="${escapeHtml(nik)}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label mb-1">${isEdit ? 'Edit Unit' : 'Tambah Unit'}</label>
                            <select class="form-select form-select-sm inline-jenis">
                                ${jenisOptions()}
                            </select>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label mb-1">Nama Unit</label>
                            <select class="form-select form-select-sm inline-unit">
                                ${data.jenis ? unitOptions(data.jenis, data.unit_id) : '<option value="">-- Pilih Nama Unit --</option>'}
                            </select>
                        </div>

                        <div class="col-md-3">
                            <div class="d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-sm btn-primary save-inline-unit">
                                    <i class="mdi mdi-content-save-outline"></i>
                                    Simpan
                                </button>
                                <button type="button" class="btn btn-sm btn-light cancel-inline-unit">
                                    Batal
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function showInlineEditor(panel, mode, data = {}) {
            const nik = panel.data('nik');
            const slot = panel.find('.skor-editor-slot');

            slot.html(renderInlineEditor(mode, nik, data));

            const editor = slot.find('.skor-inline-editor');
            editor.find('.inline-jenis').val(data.jenis || '');
            editor.find('.inline-unit').val(data.unit_id || '');
        }

        const unitTable = $('#tableUnitPegawai').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route("masterData.mapping.mappingUnit.getUnitTable") }}",
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
                    data: 'nama_pegawai',
                    name: 'nama_pegawai'
                },
                {
                    data: 'jabatan',
                    name: 'jabatan'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'total',
                    name: 'total',
                    className: 'text-end'
                }
            ]
        });

        $('.dataTables_filter').hide();
        initUnitDropify();

        $('#searchUnitPegawai').on('keyup', function() {
            unitTable.search(this.value).draw();
        });

        $('#tableUnitPegawai tbody').on('click', '.btn-detail-unit', async function() {
            const tr = $(this).closest('tr');
            const row = unitTable.row(tr);
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

            try {
                await loadUnit();
                refreshDetailPanel(panel, data.nik);
            } catch (error) {
                panel.find('.unit-list').html(`
                    <div class="skor-empty-state text-danger">
                        Master unit tidak dapat dimuat.
                    </div>
                `);
            }
        });

        modal.on('show.bs.modal', async function() {
            resetModal();

            try {
                await $.when(loadPegawai(), loadUnit());

                $('#jenisUnitSelect').html(jenisOptions());
                $('#unitSelect').html('<option value="">-- Pilih Nama Unit --</option>');
                initSelect2('#jenisUnitSelect', modal, 'Pilih Jenis Unit');
                initSelect2('#unitSelect', modal, 'Pilih Nama Unit');
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal memuat data',
                    text: 'Pegawai atau master unit tidak dapat dimuat.'
                });
            }
        });

        modal.on('hidden.bs.modal', function() {
            resetModal();
        });

        $('#jenisUnitSelect').on('change', function() {
            $('#unitSelect').html(unitOptions($(this).val())).val('').trigger('change.select2');
            renderPegawaiChecklist();
        });

        $('#unitSelect').on('change', function() {
            renderPegawaiChecklist();
        });

        $('#searchPegawaiChecklist').on('keyup', function() {
            filterPegawaiChecklist($(this).val());
        });

        $(document).on('change', '.pegawai-check', function() {
            updatePegawaiCheckedCount();
        });

        $('#checkAllPegawai').on('change', function() {
            const checked = $(this).is(':checked');
            visiblePegawaiOptions().find('.pegawai-check').prop('checked', checked);
            updatePegawaiCheckedCount();
        });

        $('#unitPegawaiForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const unitId = $('#unitSelect').val();
            const selectedPegawai = $('.pegawai-check:checked').map(function() {
                return $(this).val();
            }).get();

            resetValidation();

            if (!unitId) {
                $('#unitSelect').addClass('is-invalid');
                $('#error-unit_id').text('Unit wajib dipilih');
                return;
            }

            if (selectedPegawai.length === 0) {
                $('#error-nik').text('Pegawai wajib dipilih');
                Swal.fire({
                    icon: 'warning',
                    title: 'Pegawai wajib dipilih'
                });
                return;
            }

            Swal.fire({
                title: 'Simpan unit untuk pegawai terpilih?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, simpan',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function(result) {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: storeUnitUrl,
                    method: 'POST',
                    data: form.serialize(),
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
                            unitTable.ajax.reload(null, false);
                            showToast('success', response.message);
                        }
                    },
                    error: function(xhr) {
                        Swal.close();

                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors || {};

                            if (errors.unit_id) {
                                $('#unitSelect').addClass('is-invalid');
                                $('#error-unit_id').text(errors.unit_id[0]);
                            }

                            if (errors.nik) {
                                $('#error-nik').text(errors.nik[0]);
                            }

                            Swal.fire({
                                icon: 'warning',
                                title: xhr.responseJSON.message || 'Data belum lengkap',
                                text: Object.values(errors)[0]?.[0] || ''
                            });
                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi Kesalahan',
                            text: xhr.responseJSON?.message || 'Server error'
                        });
                    }
                });
            });
        });

        $(document).on('click', '.btn-inline-add-unit', async function() {
            const panel = $(this).closest('.skor-expand-panel');

            try {
                await loadUnit();
                showInlineEditor(panel, 'add');
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal memuat master unit'
                });
            }
        });

        $(document).on('click', '.edit-unit-pegawai', async function() {
            const card = $(this).closest('.skor-score-card');
            const panel = $(this).closest('.skor-expand-panel');

            try {
                await loadUnit();
                showInlineEditor(panel, 'edit', {
                    id: card.data('id'),
                    unit_id: card.data('unit-id'),
                    jenis: card.data('jenis')
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal memuat master unit'
                });
            }
        });

        $(document).on('click', '.cancel-inline-unit', function() {
            $(this).closest('.skor-expand-panel').find('.skor-editor-slot').empty();
        });

        $(document).on('change', '.inline-jenis', function() {
            const editor = $(this).closest('.skor-inline-editor');
            const unitSelect = editor.find('.inline-unit');

            unitSelect.html(unitOptions($(this).val()));
        });

        $(document).on('click', '.save-inline-unit', function() {
            const btn = $(this);
            const editor = btn.closest('.skor-inline-editor');
            const panel = btn.closest('.skor-expand-panel');
            const nik = editor.data('nik');
            const mode = editor.data('mode');
            const id = editor.data('id');
            const unitId = editor.find('.inline-unit').val();

            if (!unitId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Unit wajib dipilih'
                });
                return;
            }

            btn.prop('disabled', true).html(`
                <span class="spinner-border spinner-border-sm"></span>
                Simpan
            `);

            $.ajax({
                url: mode === 'edit' ? routeWithParam(updateUnitUrl, ':id', id) : storeUnitUrl,
                method: mode === 'edit' ? 'PUT' : 'POST',
                data: mode === 'edit' ? {
                    unit_id: unitId
                } : {
                    nik: nik,
                    unit_id: [unitId]
                },
                success: function(response) {
                    if (response.status === true) {
                        editor.remove();
                        refreshDetailPanel(panel, nik);
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
                        title: xhr.responseJSON?.message || 'Gagal menyimpan unit',
                        text: Object.values(xhr.responseJSON?.errors || {})[0]?.[0] || ''
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

        $(document).on('click', '.delete-unit-pegawai', function() {
            const id = $(this).data('id');
            const panel = $(this).closest('.skor-expand-panel');
            const nik = panel.data('nik');

            Swal.fire({
                title: 'Hapus unit pegawai?',
                text: 'Data unit ini akan dihapus dari pegawai.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function(result) {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: routeWithParam(deleteUnitUrl, ':id', id),
                    type: 'DELETE',
                    success: function(response) {
                        if (response.status === true) {
                            panel.find('.skor-editor-slot').empty();
                            refreshDetailPanel(panel, nik);
                            showToast('success', response.message);
                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: response.message || 'Data tidak dapat dihapus'
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: xhr.responseJSON?.message ||
                                'Terjadi kesalahan saat menghapus unit.'
                        });
                    }
                });
            });
        });

        $('#unitPegawaiModalExcell').on('shown.bs.modal', function() {
            initUnitDropify();
        });

        $('#unitPegawaiModalExcell').on('hidden.bs.modal', function() {
            resetUnitDropify();
        });

        $('#downloadTemplateUnitBtn').on('click', function() {
            window.location.href =
                "{{ route("masterData.mapping.mappingUnit.exportTemplate") }}";
        });

        $('#submitFormUnitExcell').on('click', function() {
            let btn = $(this);
            let fileInput = $('#unitDropify')[0];
            let file = fileInput.files[0];

            if (!file) {
                $('#unitDropify').addClass('shake border-danger');

                setTimeout(() => {
                    $('#unitDropify').removeClass('shake border-danger');
                }, 600);

                Swal.fire({
                    icon: 'warning',
                    title: 'Peringatan',
                    text: 'Silakan pilih file Excel terlebih dahulu!'
                });

                return;
            }

            let allowed = ['xls', 'xlsx'];
            let ext = file.name.split('.').pop().toLowerCase();

            if (!allowed.includes(ext)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Format Salah',
                    text: 'File harus berformat Excel (.xls atau .xlsx)'
                });
                return;
            }

            let formData = new FormData();
            formData.append('file', file);
            btn.prop('disabled', true);

            Swal.fire({
                title: 'Mengupload File...',
                html: `
                    <div class="progress" style="height:20px;">
                        <div id="uploadUnitProgressBar"
                            class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                            role="progressbar"
                            style="width:0%">0%</div>
                    </div>
                    <p class="mt-2 text-muted small">
                        Sistem sedang memproses data Excel...
                    </p>
                `,
                allowOutsideClick: false,
                showConfirmButton: false
            });

            $.ajax({
                url: "{{ route("masterData.mapping.mappingUnit.importMappingUnit") }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    let xhr = new window.XMLHttpRequest();

                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            let percent = Math.round((evt.loaded / evt.total) * 100);

                            $('#uploadUnitProgressBar')
                                .css('width', percent + '%')
                                .text(percent + '%');
                        }
                    }, false);

                    return xhr;
                },
                success: function(response) {
                    Swal.close();
                    btn.prop('disabled', false);

                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Import Berhasil',
                            html: `
                                <div class="text-start">
                                    <p><b>${response.added}</b> data berhasil ditambahkan.</p>
                                    <p><b>${response.skipped}</b> data dilewati.</p>
                                </div>
                            `,
                            timer: 2500,
                            showConfirmButton: false,
                            willClose: () => {
                                $('#unitPegawaiModalExcell').modal('hide');
                                resetUnitDropify();
                                unitTable.ajax.reload(null, false);
                            }
                        });
                        return;
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Import Gagal',
                        text: response.message || 'Terjadi kesalahan saat import data'
                    });
                },
                error: function(xhr) {
                    btn.prop('disabled', false);

                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Gagal',
                        text: xhr.responseJSON?.message ||
                            'Terjadi kesalahan pada server'
                    });
                }
            });
        });
    });
</script>
