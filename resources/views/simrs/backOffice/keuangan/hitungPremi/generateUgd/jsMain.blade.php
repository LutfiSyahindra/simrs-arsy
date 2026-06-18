<script>
    $(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        const generateModal = (function() {
            const modalElement = document.getElementById('modalGenerateUgd');

            if (window.bootstrap && bootstrap.Modal && modalElement) {
                return bootstrap.Modal.getOrCreateInstance ?
                    bootstrap.Modal.getOrCreateInstance(modalElement) :
                    new bootstrap.Modal(modalElement);
            }

            return {
                show: function() {
                    $('#modalGenerateUgd').modal('show');
                },
                hide: function() {
                    $('#modalGenerateUgd').modal('hide');
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
            $('#periodeUgd').val(now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0'));
        }

        function periodFromInput() {
            const value = $('#periodeUgd').val();
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

            $('#periodeUgd').val(value).trigger('change');
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

        function selectedDoctorText(row) {
            return (row.kd_dokter || '-') + ' - ' + (row.nm_dokter || '-');
        }

        function rowTotal(row) {
            const jumlah = Number(numeric(row.find('.jumlah-pasien-generate-ugd').val())) || 0;
            const nominal = Number(numeric(row.find('.nominal-generate-ugd').val())) || 0;

            return jumlah * nominal;
        }

        function updatePreviewTotal() {
            let total = 0;

            $('#generateUgdRows .ugd-input-row').each(function() {
                const row = $(this);
                const subtotal = rowTotal(row);
                total += subtotal;
                row.find('.ugd-row-total').text(formatRupiah(subtotal));
            });

            $('#previewTotalGenerateUgd').text(formatRupiah(total));
        }

        function resetFormErrors() {
            $('#entriesGenerateUgdError').text('');
            $('#generateUgdRows .is-invalid').removeClass('is-invalid');
            $('#generateUgdRows .row-error').text('');
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
            $('#generateUgdRows .ploting-generate-ugd-row').each(function() {
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
                "{{ route("backOffice.keuangan.hitungPremi.generateUgd.plotingOptions") }}",
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

        function doctorSelectHtml(rowData) {
            if (!rowData) {
                return '';
            }

            return '<option value="' + escapeHtml(rowData.kd_dokter) + '" selected>' +
                escapeHtml(selectedDoctorText(rowData)) +
                '</option>';
        }

        function addGenerateRow(rowData) {
            rowCounter += 1;
            const selectedPloting = rowData ? String(rowData.plotingPremi_id || '') : '';
            const row = $(
                '<div class="ugd-input-row" data-row-id="' + rowCounter + '">' +
                '   <div class="ugd-row-number"></div>' +
                '   <div class="ugd-row-field">' +
                '       <label>Dokter</label>' +
                '       <select class="form-select dokter-generate-ugd-row" style="width:100%;">' +
                doctorSelectHtml(rowData) +
                '       </select>' +
                '       <div class="invalid-feedback d-block row-error dokter-error"></div>' +
                '   </div>' +
                '   <div class="ugd-row-field">' +
                '       <label>Ploting</label>' +
                '       <select class="form-select ploting-generate-ugd-row"></select>' +
                '       <div class="invalid-feedback d-block row-error ploting-error"></div>' +
                '   </div>' +
                '   <div class="ugd-row-field">' +
                '       <label>Pasien</label>' +
                '       <input type="text" class="form-control text-end jumlah-pasien-generate-ugd" inputmode="numeric" autocomplete="off" placeholder="0" value="' +
                (rowData ? formatNumber(rowData.jumlah_pasien) : '') +
                '">' +
                '       <div class="invalid-feedback d-block row-error jumlah-error"></div>' +
                '   </div>' +
                '   <div class="ugd-row-field">' +
                '       <label>Nominal</label>' +
                '       <div class="input-group">' +
                '           <span class="input-group-text">Rp</span>' +
                '           <input type="text" class="form-control text-end nominal-generate-ugd" inputmode="numeric" autocomplete="off" placeholder="0" value="' +
                (rowData ? formatNumber(rowData.nominal_hitung) : '') +
                '">' +
                '       </div>' +
                '       <div class="invalid-feedback d-block row-error nominal-error"></div>' +
                '   </div>' +
                '   <div class="ugd-row-total">Rp 0</div>' +
                '   <button type="button" class="ugd-remove-row" title="Hapus baris">' +
                '       <i class="mdi mdi-close"></i>' +
                '   </button>' +
                '</div>'
            );

            row.find('.ploting-generate-ugd-row').data('selected', selectedPloting);
            $('#generateUgdRows').append(row);
            fillPlotingSelect(row.find('.ploting-generate-ugd-row'), selectedPloting);
            initDoctorSelect(row.find('.dokter-generate-ugd-row'));
            refreshRowNumbers();
            updatePreviewTotal();
        }

        function refreshRowNumbers() {
            const rows = $('#generateUgdRows .ugd-input-row');
            const canRemove = !isEditMode && rows.length > 1;

            rows.each(function(index) {
                const row = $(this);
                row.find('.ugd-row-number').text(index + 1);
                row.find('.ugd-remove-row').prop('disabled', !canRemove);
            });
        }

        function initDoctorSelect(select) {
            if (!$.fn.select2) {
                return;
            }

            select.select2({
                dropdownParent: $('#modalGenerateUgd'),
                width: '100%',
                placeholder: 'Pilih dokter',
                minimumInputLength: 0,
                ajax: {
                    url: "{{ route("backOffice.keuangan.hitungPremi.generateUgd.dokterOptions") }}",
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

        function renderPlotingSummary(items) {
            const container = $('#summaryPlotingUgd');
            const rows = items || [];

            if (!rows.length) {
                container.html('<div class="ugd-ploting-empty">Belum ada total per ploting.</div>');
                return;
            }

            container.html(rows.map(function(item) {
                return '' +
                    '<div class="ugd-ploting-item">' +
                    '   <div class="ugd-ploting-name">' + escapeHtml(item.ploting_label || '-') + '</div>' +
                    '   <div class="ugd-ploting-meta">' +
                    formatNumber(item.generated_count || 0) + ' data / ' +
                    formatNumber(item.jumlah_pasien || 0) + ' pasien / ' +
                    formatNumber(item.locked_count || 0) + ' terkunci' +
                    '   </div>' +
                    '   <div class="ugd-ploting-total">' + formatRupiah(item.total_ugd || 0) + '</div>' +
                    '</div>';
            }).join(''));
        }

        function loadSummary() {
            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateUgd.summary") }}",
                data: {
                    periode: $('#periodeUgd').val(),
                    jenis_ugd: activeType
                },
                success: function(response) {
                    const data = response.data || {};
                    $('#summaryDokterUgd').text(formatNumber(data.jumlah_dokter));
                    $('#summaryPasienUgd').text(formatNumber(data.jumlah_pasien));
                    $('#summaryDataUgd').text(formatNumber(data.generated_count));
                    $('#summaryTotalUgd').text(formatRupiah(data.total_ugd));
                    renderPlotingSummary(data.ploting_summaries);
                    $('#summaryUgdSubtitle').text(
                        'Jenis ' + (data.jenis_ugd_label || typeConfig[activeType]) +
                        ' / ' + formatNumber(data.locked_count || 0) + ' data terkunci'
                    );
                },
                error: function() {
                    $('#summaryDokterUgd, #summaryPasienUgd, #summaryDataUgd').text('0');
                    $('#summaryTotalUgd').text('Rp 0');
                    renderPlotingSummary([]);
                    $('#summaryUgdSubtitle').text('Ringkasan gagal dimuat.');
                }
            });
        }

        setDefaultPeriod();

        const table = $('#tableGenerateUgd').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            lengthChange: false,
            pageLength: 10,
            order: [],
            ajax: {
                url: "{{ route("backOffice.keuangan.hitungPremi.generateUgd.table") }}",
                data: function(data) {
                    data.periode = $('#periodeUgd').val();
                    data.jenis_ugd = activeType;
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
                    data: 'jenis_ugd_label',
                    render: function(data, type, row) {
                        return '<span class="ugd-badge ' + escapeHtml(row.jenis_ugd) + '">' +
                            escapeHtml(data) + '</span>';
                    }
                },
                {
                    data: 'nm_dokter',
                    render: function(data, type, row) {
                        return '<strong>' + escapeHtml(data || '-') + '</strong>' +
                            '<small class="d-block text-muted">' + escapeHtml(row.kd_dokter || '-') + '</small>';
                    }
                },
                {
                    data: 'ploting_label',
                    render: function(data) {
                        return escapeHtml(data || '-');
                    }
                },
                {
                    data: 'jumlah_pasien',
                    className: 'text-center',
                    render: formatNumber
                },
                {
                    data: 'nominal_hitung',
                    className: 'text-end',
                    render: formatRupiah
                },
                {
                    data: 'total_ugd',
                    className: 'text-end',
                    render: function(data) {
                        return '<strong class="text-primary">' + formatRupiah(data) + '</strong>';
                    }
                },
                {
                    data: 'is_locked',
                    render: function(data, type, row) {
                        if (data) {
                            return '<span class="ugd-lock locked" title="' +
                                escapeHtml([row.locked_by_name, row.locked_at].filter(Boolean).join(' / ')) +
                                '"><i class="mdi mdi-lock"></i>Terkunci</span>';
                        }

                        return '<span class="ugd-lock open"><i class="mdi mdi-lock-open-variant-outline"></i>Terbuka</span>';
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
                processing: 'Memuat data UGD...',
                search: '',
                searchPlaceholder: 'Cari dokter / ploting...',
                emptyTable: 'Belum ada hasil generate UGD.',
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
            $('.ugd-type-tab').removeClass('active');
            $('.ugd-type-tab[data-type="' + type + '"]').addClass('active');
            $('#resultUgdTitle').text('Hasil Generate UGD ' + typeConfig[activeType]);
        }

        function openGenerateModal(row) {
            isEditMode = Boolean(row);
            formMode = isEditMode ? 'edit' : 'generate';
            rowCounter = 0;

            const periode = row ? row.periode : $('#periodeUgd').val();
            const jenis = row ? row.jenis_ugd : activeType;

            $('#modalGenerateUgdLabel').text(
                (isEditMode ? 'Revisi' : 'Generate') + ' UGD ' + typeConfig[jenis]
            );
            $('#generateUgdModalSubtitle').text(
                isEditMode ?
                'Revisi satu data dokter dan ploting yang belum terkunci.' :
                'Tambahkan beberapa dokter sekaligus, lalu generate dalam satu proses.'
            );
            $('#generateUgdModeInfo')
                .toggleClass('alert-warning', isEditMode)
                .toggleClass('alert-info', !isEditMode)
                .text(
                    isEditMode ?
                    'Revisi akan memperbarui data UGD dokter dan ploting ini selama belum terkunci.' :
                    'Input manual pasien UGD berdasarkan data real pelayanan.'
                );
            $('#periodeGenerateUgd').val(periode);
            $('#jenisGenerateUgd').val(jenis);
            $('#jenisGenerateUgdLabel').val(typeConfig[jenis]);
            $('#generateUgdRows').empty();
            $('#btnAddGenerateUgdRow').toggle(!isEditMode);
            $('#btnSubmitGenerateUgd').html(
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

            $('#modalGenerateUgdLabel').text(
                'Copy UGD ' + typeConfig[preview.jenis_ugd] + ' ke ' + preview.target_periode
            );
            $('#generateUgdModalSubtitle').text(
                'Preview data dari ' + preview.source_periode + '. Data masih bisa diubah sebelum disimpan.'
            );
            $('#generateUgdModeInfo')
                .removeClass('alert-warning')
                .addClass('alert-info')
                .text(
                    'Data akan disimpan ke periode ' + preview.target_periode +
                    '. Jika kombinasi dokter dan ploting sudah ada dan belum terkunci, data akan diperbarui.'
                );
            $('#periodeGenerateUgd').val(preview.target_periode);
            $('#jenisGenerateUgd').val(preview.jenis_ugd);
            $('#jenisGenerateUgdLabel').val(typeConfig[preview.jenis_ugd]);
            $('#generateUgdRows').empty();
            $('#btnAddGenerateUgdRow').show();
            $('#btnSubmitGenerateUgd').html(
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
            const button = $('#btnCopyNextMonthUgd');
            const originalHtml = button.html();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateUgd.copyPreview") }}",
                data: {
                    periode: $('#periodeUgd').val(),
                    jenis_ugd: activeType
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
                            'Tidak ada data UGD ' + typeConfig[activeType] +
                            ' pada periode ' + $('#periodeUgd').val() + ' untuk dicopy.',
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
                dokter: 'dokter-generate-ugd-row',
                ploting: 'ploting-generate-ugd-row',
                jumlah: 'jumlah-pasien-generate-ugd',
                nominal: 'nominal-generate-ugd'
            }[field];

            row.find('.' + fieldClass).addClass('is-invalid');
            row.find('.' + field + '-error').text(message);
        }

        function collectEntries() {
            const entries = [];
            const seen = {};
            let invalid = false;

            resetFormErrors();

            $('#generateUgdRows .ugd-input-row').each(function() {
                const row = $(this);
                const dokter = row.find('.dokter-generate-ugd-row').val();
                const ploting = row.find('.ploting-generate-ugd-row').val();
                const jumlahPasien = numeric(row.find('.jumlah-pasien-generate-ugd').val());
                const nominal = numeric(row.find('.nominal-generate-ugd').val());
                const key = dokter + '|' + ploting;

                if (!dokter) {
                    showRowError(row, 'dokter', 'Dokter wajib dipilih.');
                    invalid = true;
                }

                if (!ploting) {
                    showRowError(row, 'ploting', 'Ploting premi wajib dipilih.');
                    invalid = true;
                }

                if (jumlahPasien === '' || Number(jumlahPasien) < 0) {
                    showRowError(row, 'jumlah', 'Jumlah pasien minimal 0.');
                    invalid = true;
                }

                if (nominal === '' || Number(nominal) < 0) {
                    showRowError(row, 'nominal', 'Nominal hitung minimal Rp 0.');
                    invalid = true;
                }

                if (dokter && ploting && seen[key]) {
                    showRowError(row, 'dokter', 'Dokter dan ploting ini duplikat.');
                    invalid = true;
                }

                if (dokter && ploting) {
                    seen[key] = true;
                }

                entries.push({
                    kd_dokter: dokter,
                    plotingPremi_id: ploting,
                    jumlah_pasien: jumlahPasien,
                    nominal_hitung: nominal
                });
            });

            if (!entries.length) {
                $('#entriesGenerateUgdError').text('Minimal tambahkan satu dokter.');
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

                const row = $('#generateUgdRows .ugd-input-row').eq(Number(match[1]));
                const field = match[2];
                const message = flattenErrors({
                    field: errors[key]
                }).join('<br>');

                if (!row.length) {
                    return;
                }

                if (field === 'kd_dokter') {
                    showRowError(row, 'dokter', message);
                    handled = true;
                } else if (field === 'plotingPremi_id') {
                    showRowError(row, 'ploting', message);
                    handled = true;
                } else if (field === 'jumlah_pasien') {
                    showRowError(row, 'jumlah', message);
                    handled = true;
                } else if (field === 'nominal_hitung') {
                    showRowError(row, 'nominal', message);
                    handled = true;
                }
            });

            return handled;
        }

        $('.ugd-type-tab').on('click', function() {
            setActiveType($(this).data('type'));
            refreshAll();
        });

        $('#periodeUgd').on('change', refreshAll);

        $('#btnPrevPeriodUgd').on('click', function() {
            movePeriod(-1);
        });

        $('#btnNextPeriodUgd').on('click', function() {
            movePeriod(1);
        });

        $('#btnCurrentPeriodUgd').on('click', function() {
            const now = new Date();
            setPeriodFromDate(new Date(now.getFullYear(), now.getMonth(), 1));
        });

        $('#btnRefreshUgd').on('click', refreshAll);

        $('#searchGenerateUgd').on('input', function() {
            table.search(this.value).draw();
        });

        $('#btnOpenGenerateUgd').on('click', function() {
            openGenerateModal(null);
        });

        $('#btnCopyNextMonthUgd').on('click', loadCopyPreview);

        $('#btnLockAllUgd').on('click', lockAllResults);

        $('#btnAddGenerateUgdRow').on('click', function() {
            addGenerateRow(null);
        });

        $('#generateUgdRows').on('input', '.jumlah-pasien-generate-ugd, .nominal-generate-ugd', function() {
            formatInput(this);
            $(this).removeClass('is-invalid');
            $(this).closest('.ugd-row-field').find('.row-error').text('');
            updatePreviewTotal();
        });

        $('#generateUgdRows').on('change', 'select', function() {
            $(this).removeClass('is-invalid');
            $(this).closest('.ugd-row-field').find('.row-error').text('');
            updatePreviewTotal();
        });

        $('#generateUgdRows').on('click', '.ugd-remove-row', function() {
            if ($(this).prop('disabled')) {
                return;
            }

            $(this).closest('.ugd-input-row').remove();
            refreshRowNumbers();
            updatePreviewTotal();
        });

        $('#formGenerateUgd').on('submit', function(event) {
            event.preventDefault();

            const entries = collectEntries();

            if (!entries) {
                return;
            }

            const button = $('#btnSubmitGenerateUgd');
            const originalHtml = button.html();

            $.ajax({
                url: "{{ route("backOffice.keuangan.hitungPremi.generateUgd.store") }}",
                method: 'POST',
                data: {
                    periode: $('#periodeGenerateUgd').val(),
                    jenis_ugd: $('#jenisGenerateUgd').val(),
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
                        $('#periodeUgd').val($('#periodeGenerateUgd').val());
                        setActiveType($('#jenisGenerateUgd').val());
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
                "{{ route("backOffice.keuangan.hitungPremi.generateUgd.lock", ["id" => "__ID__"]) }}" :
                "{{ route("backOffice.keuangan.hitungPremi.generateUgd.unlock", ["id" => "__ID__"]) }}";

            Swal.fire({
                icon: isLock ? 'warning' : 'question',
                title: isLock ? 'Kunci data UGD?' : 'Buka kunci data UGD?',
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
                title: 'Kunci semua data UGD?',
                text: 'Semua data UGD ' + typeConfig[activeType] + ' periode ' + $('#periodeUgd').val() +
                    ' yang masih terbuka akan dikunci.',
                showCancelButton: true,
                confirmButtonText: 'Kunci Semua',
                cancelButtonText: 'Batal',
                showLoaderOnConfirm: true,
                preConfirm: function() {
                    return $.ajax({
                        url: "{{ route("backOffice.keuangan.hitungPremi.generateUgd.lockAll") }}",
                        method: 'POST',
                        data: {
                            periode: $('#periodeUgd').val(),
                            jenis_ugd: activeType
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
                "{{ route("backOffice.keuangan.hitungPremi.generateUgd.delete", ["id" => "__ID__"]) }}";

            Swal.fire({
                icon: 'warning',
                title: 'Hapus data UGD?',
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

        $('#tableGenerateUgd').on('click', '.btn-lock-ugd', function() {
            changeLock($(this).data('id'), 'lock');
        });

        $('#tableGenerateUgd').on('click', '.btn-unlock-ugd', function() {
            changeLock($(this).data('id'), 'unlock');
        });

        $('#tableGenerateUgd').on('click', '.btn-delete-ugd', function() {
            deleteResult($(this).data('id'));
        });

        $('#tableGenerateUgd').on('click', '.btn-edit-ugd', function() {
            const row = table.row($(this).closest('tr')).data();

            if (row) {
                openGenerateModal(row);
            }
        });

        loadPlotingOptions();
        refreshAll();
    });
</script>
