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
            const jumlah = Number(numeric(row.find('.jumlah-tindakan-generate-vk').val())) || 0;
            const nominal = Number(numeric(row.find('.nominal-generate-vk').val())) || 0;

            return jumlah * nominal;
        }

        function updatePreviewTotal() {
            let total = 0;

            $('#generateVkRows .vk-input-row').each(function() {
                const row = $(this);
                const subtotal = rowTotal(row);
                total += subtotal;
                row.find('.vk-row-total').text(formatRupiah(subtotal));
            });

            $('#previewTotalGenerateVk').text(formatRupiah(total));
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
                    $('#summaryTotalVk').text(formatRupiah(data.total_vk));
                    $('#summaryVkSubtitle').text(
                        'Jenis ' + (data.jenis_vk_label || typeConfig[activeType]) +
                        ' / ' + formatNumber(data.locked_count || 0) + ' data terkunci'
                    );
                },
                error: function() {
                    $('#summaryTindakanVk, #summaryJumlahVk, #summaryDataVk').text('0');
                    $('#summaryTotalVk').text('Rp 0');
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
                    render: function(data) {
                        return '<strong class="text-primary">' + formatRupiah(data) + '</strong>';
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

            loadPlotingOptions().then(function() {
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

            loadPlotingOptions().then(function() {
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

                if (!jumlahTindakan || Number(jumlahTindakan) < 1) {
                    showRowError(row, 'jumlah', 'Jumlah tindakan wajib lebih dari 0.');
                    invalid = true;
                }

                if (!nominal || Number(nominal) < 1) {
                    showRowError(row, 'nominal', 'Nominal hitung wajib lebih dari Rp 0.');
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

        $('#tableGenerateVk').on('click', '.btn-lock-vk', function() {
            changeLock($(this).data('id'), 'lock');
        });

        $('#tableGenerateVk').on('click', '.btn-unlock-vk', function() {
            changeLock($(this).data('id'), 'unlock');
        });

        $('#tableGenerateVk').on('click', '.btn-edit-vk', function() {
            const row = table.row($(this).closest('tr')).data();

            if (row) {
                openGenerateModal(row);
            }
        });

        loadPlotingOptions();
        refreshAll();
    });
</script>
