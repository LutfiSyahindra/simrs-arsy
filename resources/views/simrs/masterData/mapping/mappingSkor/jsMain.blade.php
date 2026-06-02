<script>
    $(function() {
        const modal = $('#skoringPegawaiModal');
        const container = $('#skoringContainer');

        let skorList = [];
        let skorListRequest = null;
        const byPegawaiUrl = "{{ route("masterData.mapping.mappingSkor.getByPegawai", ":nik") }}";
        const updateSkorUrl = "{{ route("masterData.mapping.mappingSkor.update", ":id") }}";
        const deleteSkorUrl = "{{ route("masterData.mapping.mappingSkor.delete", ":id") }}";
        const storeSkorUrl = "{{ route("masterData.mapping.mappingSkor.store") }}";

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
                default:
                    return status || '-';
            }
        }

        function loadPegawai() {
            const select = $('#pegawaiSelect');

            select.html('<option value="">-- Pilih Pegawai --</option>');

            return $.ajax({
                url: "{{ route("masterData.mapping.mappingSkor.getPegawai") }}",
                method: 'GET',
                success: function(res) {
                    res.forEach(function(p) {
                        select.append(`
                            <option value="${escapeHtml(p.nik)}"
                                data-nama="${escapeHtml(p.nama)}"
                                data-jbtn="${escapeHtml(p.jbtn)}"
                                data-status="${escapeHtml(p.stts_kerja)}"
                                data-gapok="${Number(p.gaji_pokok) || 0}">
                                ${escapeHtml(p.nik)} - ${escapeHtml(p.nama)}
                            </option>
                        `);
                    });
                }
            });
        }

        function loadSkor() {
            if (skorList.length) {
                return $.Deferred().resolve(skorList).promise();
            }

            if (skorListRequest) {
                return skorListRequest;
            }

            skorListRequest = $.ajax({
                url: "{{ route("masterData.mapping.mappingSkor.guideSkor") }}",
                method: 'GET',
                success: function(res) {
                    skorList = res || [];
                },
                complete: function() {
                    skorListRequest = null;
                }
            });

            return skorListRequest;
        }

        function jenisOptions() {
            const jenisList = [...new Set(skorList.map(item => item.jenis).filter(Boolean))];
            let options = '<option value="">-- Pilih Jenis Skor --</option>';

            jenisList.forEach(function(jenis) {
                options += `<option value="${escapeHtml(jenis)}">${escapeHtml(jenis)}</option>`;
            });

            return options;
        }

        function skorOptions(jenis, selectedId = null) {
            let options = '<option value="">-- Pilih Nama Skor --</option>';

            skorList
                .filter(item => item.jenis === jenis)
                .forEach(function(item) {
                    const selected = String(item.id) === String(selectedId) ? 'selected' : '';
                    options += `
                        <option value="${item.id}" data-bobot="${Number(item.bobot_skor) || 0}" ${selected}>
                            ${escapeHtml(item.kd_skor)} - ${escapeHtml(item.keterangan)}
                        </option>
                    `;
                });

            return options;
        }

        function createRow(data = {}) {
            return `
                <div class="card border skoring-item">
                    <div class="card-body py-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Jenis Skor</label>
                                <select name="jenis_skor[]" class="form-select jenis-skor-select">
                                    ${jenisOptions()}
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Nama Skor</label>
                                <select name="skor_id[]" class="form-select skor-select">
                                    ${data.jenis ? skorOptions(data.jenis, data.skor_id) : '<option value="">-- Pilih Nama Skor --</option>'}
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Bobot Skor</label>
                                <input type="text" class="form-control nominal-preview" value="${data.bobot_skor ? formatAngka(data.bobot_skor) : ''}" readonly>
                                <input type="hidden" name="bobot_skor[]" class="bobot-hidden" value="${data.bobot_skor || ''}">
                            </div>

                            <div class="col-md-1 text-end">
                                <button type="button" class="btn btn-light btn-icon removeRow">
                                    <i data-feather="x"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function appendRow(data = {}) {
            const row = $(createRow(data));

            container.append(row);

            const jenisSelect = row.find('.jenis-skor-select');
            const skorSelect = row.find('.skor-select');

            if (data.jenis) {
                jenisSelect.val(data.jenis);
            }

            initSelect2(jenisSelect, modal, 'Pilih Jenis Skor');
            initSelect2(skorSelect, modal, 'Pilih Nama Skor');

            if (typeof feather !== 'undefined') {
                feather.replace();
            }
        }

        function resetValidation() {
            modal.find('.form-control, .form-select').removeClass('is-invalid');
            modal.find('.invalid-feedback').text('');
        }

        function resetModal() {
            $('#skoringPegawaiForm')[0].reset();
            $('#skoringPegawaiId').val('');
            $('#pegawaiInfo').addClass('d-none');
            container.html('');
            resetValidation();
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

        function renderDetailPanel(data) {
            return `
                <div class="skor-expand-panel" data-nik="${escapeHtml(data.nik)}">
                    <div class="skor-expand-head">
                        <div class="skor-panel-title">
                            <div class="text-muted small fw-semibold">RINCIAN SKOR</div>
                            <div class="fw-semibold text-dark">${escapeHtml(data.nama_pegawai || '-')}</div>
                            <div class="small text-muted">
                                ${escapeHtml(data.nik || '-')} / ${escapeHtml(data.jabatan || '-')}
                            </div>
                        </div>

                        <div class="skor-panel-actions">
                            <div class="skor-total-pill">
                                <div class="small fw-normal text-muted">Total</div>
                                <div class="skor-panel-total">${formatAngka(data.total_skor || 0)}</div>
                            </div>
                            <span class="badge bg-white text-secondary border skor-panel-count">
                                ${formatAngka(data.jumlah_skor || 0)} skor
                            </span>
                            <button type="button" class="btn btn-sm btn-primary btn-inline-add-skor">
                                <i class="mdi mdi-plus"></i>
                                Tambah
                            </button>
                        </div>
                    </div>

                    <div class="skor-editor-slot"></div>
                    <div class="skor-score-list">
                        <div class="text-muted small py-2">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Memuat rincian skor...
                        </div>
                    </div>
                </div>
            `;
        }

        function renderScoreList(scores) {
            if (!scores || scores.length === 0) {
                return `
                    <div class="skor-empty-state">
                        <div class="fw-semibold mb-1">Belum ada skor untuk pegawai ini</div>
                        <div class="small mb-3">Tambahkan skor langsung dari panel ini.</div>
                        <button type="button" class="btn btn-sm btn-primary btn-inline-add-skor">
                            <i class="mdi mdi-plus"></i>
                            Tambah skor
                        </button>
                    </div>
                `;
            }

            return `
                <div class="skor-list-grid">
                    ${scores.map(function(score) {
                        return `
                            <div class="skor-score-card"
                                data-id="${score.id}"
                                data-skor-id="${score.skor_id}"
                                data-jenis="${escapeHtml(score.jenis)}"
                                data-bobot="${Number(score.bobot_skor) || 0}">
                                <div class="skor-score-icon">
                                    <i class="mdi mdi-star-circle-outline"></i>
                                </div>

                                <div class="skor-score-meta">
                                    <div class="skor-score-subtitle">${escapeHtml(score.jenis || '-')}</div>
                                    <div class="skor-score-title">
                                        ${escapeHtml(score.kd_skor || '-')} - ${escapeHtml(score.keterangan || '-')}
                                    </div>
                                </div>

                                <div class="skor-bobot-box">
                                    <div class="small text-muted">Bobot</div>
                                    <div class="skor-bobot-value">${formatAngka(score.bobot_skor || 0)}</div>
                                </div>

                                <div class="skor-score-actions">
                                    <button type="button" class="btn btn-light btn-sm edit-skor-pegawai" title="Edit skor">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-light btn-sm text-danger delete-skor-pegawai" data-id="${score.id}" title="Hapus skor">
                                        <i class="mdi mdi-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }

        function updatePanelTotals(panel, scores) {
            const total = (scores || []).reduce(function(sum, item) {
                return sum + (Number(item.bobot_skor) || 0);
            }, 0);

            panel.find('.skor-panel-total').text(formatAngka(total));
            panel.find('.skor-panel-count').text(formatAngka((scores || []).length) + ' skor');
            panel.closest('tr').prev('tr').find('td').last().html(
                `<span class="fw-bold text-primary">${formatAngka(total)}</span>`
            );
        }

        function refreshDetailPanel(panel, nik) {
            panel.find('.skor-score-list').html(`
                <div class="text-muted small py-2">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Memuat rincian skor...
                </div>
            `);

            return $.ajax({
                url: routeWithParam(byPegawaiUrl, ':nik', nik),
                method: 'GET',
                success: function(scores) {
                    panel.find('.skor-score-list').html(renderScoreList(scores || []));
                    updatePanelTotals(panel, scores || []);
                },
                error: function() {
                    panel.find('.skor-score-list').html(`
                        <div class="skor-empty-state text-danger">
                            Rincian skor tidak dapat dimuat.
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
                        <div class="col-md-3">
                            <label class="form-label mb-1">${isEdit ? 'Edit Skor' : 'Tambah Skor'}</label>
                            <select class="form-select form-select-sm inline-jenis">
                                ${jenisOptions()}
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label mb-1">Nama Skor</label>
                            <select class="form-select form-select-sm inline-skor">
                                ${data.jenis ? skorOptions(data.jenis, data.skor_id) : '<option value="">-- Pilih Nama Skor --</option>'}
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label mb-1">Bobot</label>
                            <input type="text" class="form-control form-control-sm inline-bobot" value="${data.bobot_skor ? formatAngka(data.bobot_skor) : ''}" readonly>
                        </div>

                        <div class="col-md-3">
                            <div class="d-flex gap-2 justify-content-end">
                                <button type="button" class="btn btn-sm btn-primary save-inline-skor">
                                    <i class="mdi mdi-content-save-outline"></i>
                                    Simpan
                                </button>
                                <button type="button" class="btn btn-sm btn-light cancel-inline-skor">
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
            editor.find('.inline-skor').val(data.skor_id || '');
            updateInlineBobot(editor);
        }

        function updateInlineBobot(editor) {
            const bobot = Number(editor.find('.inline-skor :selected').data('bobot')) || 0;
            editor.find('.inline-bobot').val(bobot ? formatAngka(bobot) : '');
        }

        const skorTable = $('#tableSkorPegawai').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route("masterData.mapping.mappingSkor.getSkorTable") }}",
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

        $('#searchSkorPegawai').on('keyup', function() {
            skorTable.search(this.value).draw();
        });

        $('#tableSkorPegawai tbody').on('click', '.btn-detail-skor', async function() {
            const tr = $(this).closest('tr');
            const row = skorTable.row(tr);
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
                await loadSkor();
                refreshDetailPanel(panel, data.nik);
            } catch (error) {
                panel.find('.skor-score-list').html(`
                    <div class="skor-empty-state text-danger">
                        Master skor tidak dapat dimuat.
                    </div>
                `);
            }
        });

        modal.on('show.bs.modal', async function() {
            resetModal();

            try {
                await $.when(loadPegawai(), loadSkor());

                initSelect2('#pegawaiSelect', modal, 'Pilih Pegawai');
                appendRow();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal memuat data',
                    text: 'Pegawai atau master skor tidak dapat dimuat.'
                });
            }
        });

        modal.on('hidden.bs.modal', function() {
            resetModal();
        });

        $('#addRow').on('click', function() {
            appendRow();
        });

        container.on('click', '.removeRow', function() {
            if (container.find('.skoring-item').length === 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Minimal 1 skor'
                });
                return;
            }

            $(this).closest('.skoring-item').remove();
        });

        container.on('change', '.jenis-skor-select', function() {
            const row = $(this).closest('.skoring-item');
            const jenis = $(this).val();
            const skorSelect = row.find('.skor-select');

            skorSelect.html(skorOptions(jenis));
            row.find('.nominal-preview').val('');
            row.find('.bobot-hidden').val('');
            initSelect2(skorSelect, modal, 'Pilih Nama Skor');
        });

        container.on('change', '.skor-select', function() {
            const selected = $(this).find(':selected');
            const bobot = Number(selected.data('bobot')) || 0;
            const row = $(this).closest('.skoring-item');

            row.find('.nominal-preview').val(bobot ? formatAngka(bobot) : '');
            row.find('.bobot-hidden').val(bobot || '');
        });

        $('#pegawaiSelect').on('change', function() {
            const selected = $(this).find(':selected');
            const nik = $(this).val();

            if (!nik) {
                $('#pegawaiInfo').addClass('d-none');
                return;
            }

            $('#infoNama').text(selected.data('nama') || '-');
            $('#infoJabatan').text(selected.data('jbtn') || '-');
            $('#infoStatus').text(statusLabel(selected.data('status')));
            $('#infoGapok').text('Rp ' + formatAngka(selected.data('gapok')));
            $('#pegawaiInfo').removeClass('d-none');
        });

        $('#skoringPegawaiForm').on('submit', function(e) {
            e.preventDefault();

            const form = $(this);
            const selectedScores = form.find('.skor-select').map(function() {
                return $(this).val();
            }).get().filter(Boolean);

            resetValidation();

            if (!$('#pegawaiSelect').val()) {
                $('#pegawaiSelect').addClass('is-invalid');
                $('#error-nik').text('Pegawai wajib dipilih');
                return;
            }

            if (selectedScores.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Skor wajib dipilih'
                });
                return;
            }

            if (selectedScores.length !== [...new Set(selectedScores)].length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Skor tidak boleh duplikat'
                });
                return;
            }

            Swal.fire({
                title: 'Simpan skoring pegawai?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, simpan',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function(result) {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: "{{ route("masterData.mapping.mappingSkor.store") }}",
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
                            skorTable.ajax.reload(null, false);

                            Swal.fire({
                                icon: 'success',
                                title: response.message,
                                toast: true,
                                position: 'top-end',
                                timer: 2500,
                                showConfirmButton: false
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.close();

                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors || {};

                            if (errors.nik) {
                                $('#pegawaiSelect').addClass('is-invalid');
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

        $(document).on('click', '.btn-inline-add-skor', async function() {
            const panel = $(this).closest('.skor-expand-panel');

            try {
                await loadSkor();
                showInlineEditor(panel, 'add');
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal memuat master skor'
                });
            }
        });

        $(document).on('click', '.edit-skor-pegawai', async function() {
            const card = $(this).closest('.skor-score-card');
            const panel = $(this).closest('.skor-expand-panel');

            try {
                await loadSkor();
                showInlineEditor(panel, 'edit', {
                    id: card.data('id'),
                    skor_id: card.data('skor-id'),
                    jenis: card.data('jenis'),
                    bobot_skor: card.data('bobot')
                });
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal memuat master skor'
                });
            }
        });

        $(document).on('click', '.cancel-inline-skor', function() {
            $(this).closest('.skor-expand-panel').find('.skor-editor-slot').empty();
        });

        $(document).on('change', '.inline-jenis', function() {
            const editor = $(this).closest('.skor-inline-editor');
            const skorSelect = editor.find('.inline-skor');

            skorSelect.html(skorOptions($(this).val()));
            editor.find('.inline-bobot').val('');
        });

        $(document).on('change', '.inline-skor', function() {
            updateInlineBobot($(this).closest('.skor-inline-editor'));
        });

        $(document).on('click', '.save-inline-skor', function() {
            const btn = $(this);
            const editor = btn.closest('.skor-inline-editor');
            const panel = btn.closest('.skor-expand-panel');
            const nik = editor.data('nik');
            const mode = editor.data('mode');
            const id = editor.data('id');
            const skorId = editor.find('.inline-skor').val();

            if (!skorId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Skor wajib dipilih'
                });
                return;
            }

            btn.prop('disabled', true).html(`
                <span class="spinner-border spinner-border-sm"></span>
                Simpan
            `);

            $.ajax({
                url: mode === 'edit'
                    ? routeWithParam(updateSkorUrl, ':id', id)
                    : storeSkorUrl,
                method: mode === 'edit' ? 'PUT' : 'POST',
                data: mode === 'edit' ? {
                    skor_id: skorId
                } : {
                    nik: nik,
                    skor_id: [skorId]
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
                        title: xhr.responseJSON?.message || 'Gagal menyimpan skor',
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

        $(document).on('click', '.delete-skor-pegawai', function() {
            const id = $(this).data('id');
            const panel = $(this).closest('.skor-expand-panel');
            const nik = panel.data('nik');

            Swal.fire({
                title: 'Hapus skor pegawai?',
                text: 'Data skor ini akan dihapus dari pegawai.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then(function(result) {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: routeWithParam(deleteSkorUrl, ':id', id),
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
                            text: xhr.responseJSON?.message || 'Terjadi kesalahan saat menghapus skor.'
                        });
                    }
                });
            });
        });
    });
</script>
