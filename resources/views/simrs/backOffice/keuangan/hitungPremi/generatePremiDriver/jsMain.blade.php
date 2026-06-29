 <script>
        $(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            const detailModal = (function() {
                const element = document.getElementById('modalDetailDriver');

                if (window.bootstrap && bootstrap.Modal && element) {
                    return bootstrap.Modal.getOrCreateInstance ?
                        bootstrap.Modal.getOrCreateInstance(element) :
                        new bootstrap.Modal(element);
                }

                return {
                    show: function() {
                        $('#modalDetailDriver').modal('show');
                    },
                    hide: function() {
                        $('#modalDetailDriver').modal('hide');
                    }
                };
            })();

            let tujuanOptions = [];
            let tujuanList = [];
            let configDriver = {
                premi_bersama_percent: 20,
                premi_pegawai_percent: 100
            };
            let rowCounter = 0;
            let selectedEditPegawaiId = '';

            function formatNumber(value) {
                return new Intl.NumberFormat('id-ID').format(Number(value) || 0);
            }

            function formatRupiah(value) {
                return 'Rp ' + formatNumber(value);
            }

            function percentText(value) {
                return formatNumber(Number(value) || 0) + '%';
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
                const params = new URLSearchParams(window.location.search);
                const queryPeriod = params.get('periode');

                if (queryPeriod && /^\d{4}-\d{2}$/.test(queryPeriod)) {
                    $('#periodeDriver').val(queryPeriod);
                    $('#periodeGenerateDriver').val(queryPeriod);
                    return;
                }

                const now = new Date();
                const value = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
                $('#periodeDriver').val(value);
                $('#periodeGenerateDriver').val(value);
            }

            function periodFromInput() {
                const value = $('#periodeDriver').val();
                const parts = String(value || '').split('-').map(Number);

                if (parts.length === 2 && parts[0] && parts[1]) {
                    return new Date(parts[0], parts[1] - 1, 1);
                }

                const now = new Date();
                return new Date(now.getFullYear(), now.getMonth(), 1);
            }

            function setPeriodFromDate(date) {
                const value = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
                $('#periodeDriver').val(value).trigger('change');
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

            function resetFormErrors() {
                $('#pegawaiGenerateDriverError, #entriesGenerateDriverError').text('');
                $('#driverRows .is-invalid').removeClass('is-invalid');
                $('#driverRows .row-error').text('');
            }

            function resetTujuanErrors() {
                $('#tujuanDriverKodeError, #tujuanDriverNamaError, #tujuanDriverHargaError').text('');
            }

            function selectedPegawaiText() {
                if (!$.fn.select2) {
                    const selected = $('#pegawaiGenerateDriver option:selected').text();

                    return selected || '-';
                }

                const data = $('#pegawaiGenerateDriver').select2('data');
                return data && data.length ? data[0].text : '-';
            }

            function activeTujuanById(id) {
                return tujuanOptions.find(function(item) {
                    return String(item.id) === String(id);
                });
            }

            function rowTotal(row) {
                const tujuan = activeTujuanById(row.find('.tujuan-driver-row').val());
                const harga = tujuan ? Number(tujuan.harga || 0) : 0;
                const jumlah = Number(numeric(row.find('.jumlah-driver-row').val())) || 0;

                return harga * jumlah;
            }

            function updatePreviewTotal() {
                let grandTotal = 0;
                let totalJumlah = 0;
                let totalTujuan = 0;

                $('#driverRows .driver-input-row').each(function() {
                    const row = $(this);
                    const tujuan = activeTujuanById(row.find('.tujuan-driver-row').val());
                    const jumlah = Number(numeric(row.find('.jumlah-driver-row').val())) || 0;
                    const subtotal = rowTotal(row);

                    if (tujuan) {
                        totalTujuan += 1;
                        row.find('.harga-driver-row').val(formatNumber(tujuan.harga || 0));
                    } else {
                        row.find('.harga-driver-row').val('');
                    }

                    grandTotal += subtotal;
                    totalJumlah += jumlah;
                    row.find('.driver-row-total').text(formatRupiah(subtotal));
                });

                const bersamaPercent = Number(configDriver.premi_bersama_percent || 0);
                const bersama = Math.round(grandTotal * bersamaPercent / 100);
                const pegawai = grandTotal;

                $('#previewGrandDriver').text(formatRupiah(grandTotal));
                $('#previewFormulaDriver').text(formatNumber(totalTujuan) + ' tujuan / ' + formatNumber(
                    totalJumlah) + ' perjalanan');
                $('#previewPegawaiDriver').text(formatRupiah(pegawai));
                $('#previewBersamaDriver').text(formatRupiah(bersama));
                $('#previewPegawaiPercentDriver').text(percentText(100));
                $('#previewBersamaPercentDriver').text(percentText(bersamaPercent));
                $('#previewPegawaiNameDriver').text(selectedPegawaiText());
            }

            function refreshRowNumbers() {
                const rows = $('#driverRows .driver-input-row');
                const canRemove = rows.length > 1;

                rows.each(function(index) {
                    const row = $(this);
                    row.find('.driver-row-number').text(index + 1);
                    row.find('.driver-remove-row').prop('disabled', !canRemove);
                });
            }

            function fillTujuanSelect(select, selectedValue) {
                const value = selectedValue ? String(selectedValue) : '';

                select.empty().append('<option value="">Pilih tujuan</option>');
                tujuanOptions.forEach(function(item) {
                    select.append(
                        $('<option>', {
                            value: item.id,
                            text: item.text
                        })
                    );
                });
                select.val(value);
            }

            function refreshTujuanSelects() {
                $('#driverRows .tujuan-driver-row').each(function() {
                    const select = $(this);
                    fillTujuanSelect(select, select.data('selected') || select.val());
                });
                refreshSetupState();
                updatePreviewTotal();
            }

            function refreshSetupState() {
                const hasTujuan = tujuanOptions.length > 0;

                $('#driverSetupAlert').toggleClass('active', !hasTujuan);
                $('#btnAddDriverRow, #btnPreviewDriver, #btnSubmitDriver').prop('disabled', !hasTujuan);

                if (!hasTujuan) {
                    $('#entriesGenerateDriverError').text(
                        'Tambahkan tujuan ambulance pada tab Konfigurasi terlebih dahulu.');
                } else if ($('#entriesGenerateDriverError').text() ===
                    'Tambahkan tujuan ambulance pada tab Konfigurasi terlebih dahulu.') {
                    $('#entriesGenerateDriverError').text('');
                }
            }

            function addDriverRow(rowData) {
                rowCounter += 1;
                const selectedTujuan = rowData ? String(rowData.tujuan_id || '') : '';
                const jumlah = rowData ? formatNumber(rowData.jumlah || 0) : '';
                const row = $(
                    '<div class="driver-input-row" data-row-id="' + rowCounter + '">' +
                    '   <div class="driver-row-number"></div>' +
                    '   <div class="driver-row-field">' +
                    '       <label>Tujuan</label>' +
                    '       <select class="form-select tujuan-driver-row"></select>' +
                    '       <div class="invalid-feedback d-block row-error tujuan-error"></div>' +
                    '   </div>' +
                    '   <div class="driver-row-field">' +
                    '       <label>Harga</label>' +
                    '       <div class="input-group">' +
                    '           <span class="input-group-text">Rp</span>' +
                    '           <input type="text" class="form-control text-end harga-driver-row" readonly>' +
                    '       </div>' +
                    '   </div>' +
                    '   <div class="driver-row-field">' +
                    '       <label>Jumlah</label>' +
                    '       <input type="text" class="form-control text-end jumlah-driver-row" inputmode="numeric" autocomplete="off" placeholder="0" value="' +
                    jumlah + '">' +
                    '       <div class="invalid-feedback d-block row-error jumlah-error"></div>' +
                    '   </div>' +
                    '   <div class="driver-row-total">Rp 0</div>' +
                    '   <button type="button" class="driver-remove-row" title="Hapus baris">' +
                    '       <i class="mdi mdi-close"></i>' +
                    '   </button>' +
                    '</div>'
                );

                row.find('.tujuan-driver-row').data('selected', selectedTujuan);
                $('#driverRows').append(row);
                fillTujuanSelect(row.find('.tujuan-driver-row'), selectedTujuan);
                refreshRowNumbers();
                updatePreviewTotal();
            }

            function loadConfig() {
                return $.get("{{ route("backOffice.keuangan.hitungPremi.generatePremiDriver.config") }}", function(
                    response) {
                    configDriver = response.data || configDriver;
                    $('#configPremiBersamaDriver').val(configDriver.premi_bersama_percent);
                    renderConfigPercent();
                    updatePreviewTotal();
                });
            }

            function loadTujuanOptions() {
                return $.get("{{ route("backOffice.keuangan.hitungPremi.generatePremiDriver.tujuanOptions") }}",
                    function(response) {
                        tujuanOptions = response.data || [];
                        refreshTujuanSelects();
                    });
            }

            function loadTujuanList() {
                return $.get("{{ route("backOffice.keuangan.hitungPremi.generatePremiDriver.tujuanList") }}",
                    function(response) {
                        tujuanList = response.data || [];
                        renderTujuanList();
                    });
            }

            function renderConfigPercent() {
                const bersama = Number($('#configPremiBersamaDriver').val() || configDriver.premi_bersama_percent ||
                    0);
                const pegawai = 100;

                $('#configPegawaiPercentDriver').text(percentText(pegawai));
                $('#configBersamaPercentDriver').text(percentText(bersama));
                $('#summaryPercentDriver').text(percentText(configDriver.premi_bersama_percent) +
                    ' dari grand total');
            }

            function renderTujuanList() {
                const container = $('#driverTujuanList').empty();

                if (!tujuanList.length) {
                    container.html('<div class="driver-empty">Belum ada tujuan ambulance.</div>');
                    return;
                }

                tujuanList.forEach(function(item) {
                    const statusClass = item.is_active ? 'active' : 'inactive';
                    const statusText = item.is_active ? 'Aktif' : 'Nonaktif';
                    container.append(
                        '<div class="driver-destination-item">' +
                        '   <div>' +
                        '       <div class="driver-destination-name">' + escapeHtml(item.nama_tujuan) +
                        '</div>' +
                        '       <div class="driver-destination-meta">' + escapeHtml(item.kode || '-') +
                        ' / urutan ' + formatNumber(item.sort_order || 0) + '</div>' +
                        '   </div>' +
                        '   <div class="driver-destination-price">' + formatRupiah(item.harga || 0) +
                        '</div>' +
                        '   <div class="d-flex align-items-center gap-2">' +
                        '       <span class="driver-status-pill ' + statusClass + '">' + statusText +
                        '</span>' +
                        '       <button type="button" class="btn btn-outline-primary btn-sm btn-edit-tujuan-driver" data-id="' +
                        item.id + '" title="Edit">' +
                        '           <i class="mdi mdi-pencil-outline"></i>' +
                        '       </button>' +
                        '       <button type="button" class="btn btn-outline-danger btn-sm btn-delete-tujuan-driver" data-id="' +
                        item.id + '" title="Hapus">' +
                        '           <i class="mdi mdi-delete-outline"></i>' +
                        '       </button>' +
                        '   </div>' +
                        '</div>'
                    );
                });
            }

            function loadSummary() {
                $.ajax({
                    url: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDriver.summary") }}",
                    data: {
                        periode: $('#periodeDriver').val()
                    },
                    success: function(response) {
                        const data = response.data || {};
                        $('#summaryPegawaiDriver').text(formatNumber(data.jumlah_pegawai || 0));
                        $('#summaryGeneratedDriver').text(formatNumber(data.generated_count || 0) +
                            ' data generate');
                        $('#summaryJumlahDriver').text(formatNumber(data.total_jumlah || 0));
                        $('#summaryTujuanDriver').text(formatNumber(data.tujuan_count || 0) +
                            ' tujuan aktif');
                        $('#summaryGrandDriver').text(formatRupiah(data.grand_total || 0));
                        $('#summaryLockedDriver').text(formatNumber(data.locked_count || 0) +
                            ' terkunci');
                        $('#summaryBersamaDriver').text(formatRupiah(data.total_premi_bersama || 0));
                        if (data.config) {
                            configDriver = data.config;
                            renderConfigPercent();
                            updatePreviewTotal();
                        }
                    },
                    error: function() {
                        $('#summaryPegawaiDriver, #summaryJumlahDriver').text('0');
                        $('#summaryGrandDriver, #summaryBersamaDriver').text('Rp 0');
                    }
                });
            }

            function initPegawaiSelect() {
                if (!$.fn.select2) {
                    return;
                }

                $('#pegawaiGenerateDriver').select2({
                    width: '100%',
                    placeholder: 'Pilih pegawai',
                    minimumInputLength: 0,
                    ajax: {
                        url: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDriver.pegawaiOptions") }}",
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
                                        nama: item.nama,
                                        jbtn: item.jbtn
                                    };
                                })
                            };
                        }
                    }
                });
            }

            setDefaultPeriod();
            initPegawaiSelect();

            const table = $('#tableGenerateDriver').DataTable({
                processing: true,
                serverSide: true,
                searching: true,
                lengthChange: false,
                pageLength: 10,
                order: [],
                ajax: {
                    url: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDriver.table") }}",
                    data: function(data) {
                        data.periode = $('#periodeDriver').val();
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
                        data: 'pegawai_name',
                        render: function(data, type, row) {
                            return '<strong>' + escapeHtml(data || '-') + '</strong>' +
                                '<small class="d-block text-muted">' + escapeHtml(row.pegawai_id ||
                                    '-') +
                                ' / ' + escapeHtml(row.pegawai_position || '-') + '</small>';
                        }
                    },
                    {
                        data: 'jumlah_tujuan',
                        className: 'text-center',
                        render: formatNumber
                    },
                    {
                        data: 'total_jumlah',
                        className: 'text-center',
                        render: formatNumber
                    },
                    {
                        data: 'grand_total',
                        className: 'text-end',
                        render: function(data) {
                            return '<strong class="text-primary">' + formatRupiah(data) +
                                '</strong>';
                        }
                    },
                    {
                        data: 'total_premi_pegawai',
                        className: 'text-end',
                        render: formatRupiah
                    },
                    {
                        data: 'total_premi_bersama',
                        className: 'text-end',
                        render: formatRupiah
                    },
                    {
                        data: 'is_locked',
                        render: function(data, type, row) {
                            if (data) {
                                return '<span class="driver-lock locked" title="' +
                                    escapeHtml([row.locked_by_name, row.locked_at].filter(Boolean)
                                        .join(' / ')) +
                                    '"><i class="mdi mdi-lock"></i>Terkunci</span>';
                            }

                            return '<span class="driver-lock open"><i class="mdi mdi-lock-open-variant-outline"></i>Terbuka</span>';
                        }
                    },
                    {
                        data: 'generate_by_name',
                        render: function(data, type, row) {
                            return '<span class="fw-semibold">' + escapeHtml(data || '-') +
                                '</span>' +
                                '<small class="d-block text-muted">' + escapeHtml(row
                                    .generated_at || '-') + '</small>';
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
                    processing: 'Memuat data driver...',
                    search: '',
                    searchPlaceholder: 'Cari pegawai...',
                    emptyTable: 'Belum ada hasil generate premi driver.',
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
                $('#resultDriverPeriodLabel').text($('#periodeDriver').val() || '-');
                $('#periodeGenerateDriver').val($('#periodeDriver').val());
                loadSummary();
                table.ajax.reload(null, false);
            }

            function resetDriverForm() {
                rowCounter = 0;
                selectedEditPegawaiId = '';
                $('#editingDriverId').val('');
                $('#formDriverTitle').text('Generate Premi Driver');
                $('#formDriverSubtitle').text('Pilih pegawai dan rincian tujuan ambulance.');
                $('#pegawaiGenerateDriver').prop('disabled', false).val(null).trigger('change');
                $('#periodeGenerateDriver').val($('#periodeDriver').val());
                $('#driverRows').empty();
                resetFormErrors();
                addDriverRow(null);
                refreshSetupState();
                updatePreviewTotal();
            }

            function selectedPegawaiId() {
                return selectedEditPegawaiId || $('#pegawaiGenerateDriver').val();
            }

            function collectEntries() {
                const entries = [];
                const seen = {};
                let invalid = false;

                resetFormErrors();

                if (!selectedPegawaiId()) {
                    $('#pegawaiGenerateDriverError').text('Pegawai penerima wajib dipilih.');
                    invalid = true;
                }

                $('#driverRows .driver-input-row').each(function() {
                    const row = $(this);
                    const tujuanId = row.find('.tujuan-driver-row').val();
                    const jumlah = numeric(row.find('.jumlah-driver-row').val());

                    if (!tujuanId) {
                        row.find('.tujuan-driver-row').addClass('is-invalid');
                        row.find('.tujuan-error').text('Tujuan wajib dipilih.');
                        invalid = true;
                    }

                    if (!jumlah || Number(jumlah) < 1) {
                        row.find('.jumlah-driver-row').addClass('is-invalid');
                        row.find('.jumlah-error').text('Jumlah minimal 1.');
                        invalid = true;
                    }

                    if (tujuanId && seen[tujuanId]) {
                        row.find('.tujuan-driver-row').addClass('is-invalid');
                        row.find('.tujuan-error').text('Tujuan ini sudah dipilih.');
                        invalid = true;
                    }

                    if (tujuanId) {
                        seen[tujuanId] = true;
                    }

                    entries.push({
                        tujuan_id: tujuanId,
                        jumlah: jumlah
                    });
                });

                if (!entries.length) {
                    $('#entriesGenerateDriverError').text('Minimal tambahkan satu tujuan.');
                    invalid = true;
                }

                return invalid ? null : entries;
            }

            function showServerEntryErrors(errors) {
                let handled = false;

                Object.keys(errors || {}).forEach(function(key) {
                    const match = key.match(/^entries\.(\d+)\.(.+)$/);

                    if (!match) {
                        return;
                    }

                    const row = $('#driverRows .driver-input-row').eq(Number(match[1]));
                    const field = match[2];
                    const message = flattenErrors({
                        field: errors[key]
                    }).join('<br>');

                    if (!row.length) {
                        return;
                    }

                    if (field === 'tujuan_id') {
                        row.find('.tujuan-driver-row').addClass('is-invalid');
                        row.find('.tujuan-error').html(message);
                        handled = true;
                    } else if (field === 'jumlah') {
                        row.find('.jumlah-driver-row').addClass('is-invalid');
                        row.find('.jumlah-error').html(message);
                        handled = true;
                    }
                });

                if (errors && errors.pegawai_id) {
                    $('#pegawaiGenerateDriverError').html(flattenErrors({
                        pegawai_id: errors.pegawai_id
                    }).join('<br>'));
                    handled = true;
                }

                return handled;
            }

            function applyPreviewData(data) {
                $('#previewGrandDriver').text(formatRupiah(data.grand_total || 0));
                $('#previewFormulaDriver').text(formatNumber(data.jumlah_tujuan || 0) + ' tujuan / ' + formatNumber(
                    data.total_jumlah || 0) + ' perjalanan');
                $('#previewPegawaiDriver').text(formatRupiah(data.total_premi_pegawai || 0));
                $('#previewBersamaDriver').text(formatRupiah(data.total_premi_bersama || 0));
                $('#previewPegawaiPercentDriver').text(percentText(data.premi_pegawai_percent || 0));
                $('#previewBersamaPercentDriver').text(percentText(data.premi_bersama_percent || 0));
                $('#previewPegawaiNameDriver').text((data.pegawai || {}).pegawai_name || selectedPegawaiText());
                $('#previewPegawaiPositionDriver').text((data.pegawai || {}).pegawai_position || '-');
            }

            function previewServer() {
                const entries = collectEntries();

                if (!entries) {
                    return;
                }

                const button = $('#btnPreviewDriver');
                const originalHtml = button.html();

                $.ajax({
                    url: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDriver.preview") }}",
                    method: 'POST',
                    data: {
                        pegawai_id: selectedPegawaiId(),
                        entries: entries
                    },
                    beforeSend: function() {
                        button.prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm me-1"></span> Preview');
                    },
                    success: function(response) {
                        applyPreviewData(response.data || {});
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON || {};
                        if (!showServerEntryErrors(response.errors || {})) {
                            Swal.fire('Gagal', errorMessage(xhr), 'error');
                        }
                    },
                    complete: function() {
                        button.prop('disabled', false).html(originalHtml);
                    }
                });
            }

            function openDetail(id, editMode) {
                $.ajax({
                    url: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDriver.detail", ["id" => "__ID__"]) }}"
                        .replace('__ID__', id),
                    success: function(response) {
                        const data = response.data || {};

                        if (editMode) {
                            fillEditForm(data);
                            showSection('generate');
                            return;
                        }

                        $('#detailDriverSubtitle').text(data.pegawai_label + ' / ' + data.periode);
                        $('#detailGrandDriver').text(formatRupiah(data.grand_total || 0));
                        $('#detailPegawaiDriver').text(formatRupiah(data.total_premi_pegawai || 0));
                        $('#detailBersamaDriver').text(formatRupiah(data.total_premi_bersama || 0));

                        const rows = data.details || [];
                        $('#detailDriverRows').html(rows.length ? rows.map(function(item) {
                                return '<div class="driver-detail-item">' +
                                    '   <div><strong>' + escapeHtml(item.nama_tujuan || '-') +
                                    '</strong><small class="d-block text-muted">' + escapeHtml(
                                        item.kode_tujuan || '-') + '</small></div>' +
                                    '   <div class="text-end">' + formatNumber(item.jumlah ||
                                    0) + '</div>' +
                                    '   <div class="text-end">' + formatRupiah(item.harga ||
                                    0) + '</div>' +
                                    '   <div class="text-end fw-bold text-primary">' +
                                    formatRupiah(item.subtotal || 0) + '</div>' +
                                    '</div>';
                            }).join('') :
                            '<div class="driver-empty">Detail tujuan belum tersedia.</div>');

                        detailModal.show();
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal', errorMessage(xhr), 'error');
                    }
                });
            }

            function fillEditForm(data) {
                rowCounter = 0;
                $('#editingDriverId').val(data.id);
                $('#formDriverTitle').text('Revisi Premi Driver');
                $('#formDriverSubtitle').text('Revisi data yang masih terbuka.');
                $('#periodeDriver').val(data.periode);
                $('#periodeGenerateDriver').val(data.periode);
                selectedEditPegawaiId = data.pegawai_id;

                const option = new Option(data.pegawai_label, data.pegawai_id, true, true);
                $('#pegawaiGenerateDriver').empty().append(option).prop('disabled', true).trigger('change');

                $('#driverRows').empty();
                (data.details || []).forEach(function(item) {
                    addDriverRow(item);
                });

                if (!(data.details || []).length) {
                    addDriverRow(null);
                }

                refreshSetupState();
                updatePreviewTotal();
            }

            function changeLock(id, action) {
                const isLock = action === 'lock';
                const template = isLock ?
                    "{{ route("backOffice.keuangan.hitungPremi.generatePremiDriver.lock", ["id" => "__ID__"]) }}" :
                    "{{ route("backOffice.keuangan.hitungPremi.generatePremiDriver.unlock", ["id" => "__ID__"]) }}";

                Swal.fire({
                    icon: isLock ? 'warning' : 'question',
                    title: isLock ? 'Kunci data premi driver?' : 'Buka kunci premi driver?',
                    text: isLock ? 'Data yang dikunci tidak bisa direvisi.' :
                        'Data dapat direvisi setelah kunci dibuka.',
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
                    "{{ route("backOffice.keuangan.hitungPremi.generatePremiDriver.delete", ["id" => "__ID__"]) }}";

                Swal.fire({
                    icon: 'warning',
                    title: 'Hapus premi driver?',
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
                        resetDriverForm();
                    }
                });
            }

            function resetTujuanForm() {
                $('#tujuanDriverId').val('');
                $('#tujuanDriverFormTitle').text('Tambah Tujuan');
                $('#tujuanDriverKode, #tujuanDriverNama, #tujuanDriverHarga').val('');
                $('#tujuanDriverSort').val(0);
                $('#tujuanDriverActive').val('1');
                resetTujuanErrors();
            }

            function fillTujuanForm(item) {
                $('#tujuanDriverId').val(item.id);
                $('#tujuanDriverFormTitle').text('Edit Tujuan');
                $('#tujuanDriverKode').val(item.kode);
                $('#tujuanDriverNama').val(item.nama_tujuan);
                $('#tujuanDriverHarga').val(formatNumber(item.harga));
                $('#tujuanDriverSort').val(item.sort_order || 0);
                $('#tujuanDriverActive').val(item.is_active ? '1' : '0');
                resetTujuanErrors();
            }

            function showSection(section) {
                const selector = '#sectionDriver' + section.charAt(0).toUpperCase() + section.slice(1);

                $('.driver-tab').removeClass('active');
                $('.driver-tab[data-section="' + section + '"]').addClass('active');
                $('.driver-section').removeClass('active');
                $(selector).addClass('active');

                if (section === 'riwayat') {
                    table.columns.adjust();
                }
            }

            $('.driver-tab').on('click', function() {
                showSection($(this).data('section'));
            });

            $('#periodeDriver').on('change', refreshAll);
            $('#btnPrevPeriodDriver').on('click', function() {
                movePeriod(-1);
            });
            $('#btnNextPeriodDriver').on('click', function() {
                movePeriod(1);
            });
            $('#btnCurrentPeriodDriver').on('click', function() {
                const now = new Date();
                setPeriodFromDate(new Date(now.getFullYear(), now.getMonth(), 1));
            });

            $('#btnRefreshDriver').on('click', refreshAll);
            $('#searchGenerateDriver').on('input', function() {
                table.search(this.value).draw();
            });

            $('#pegawaiGenerateDriver').on('change', function() {
                $('#pegawaiGenerateDriverError').text('');
                if (!$('#editingDriverId').val()) {
                    selectedEditPegawaiId = '';
                }
                updatePreviewTotal();
            });

            $('#btnOpenDriverConfigFromAlert').on('click', function() {
                showSection('konfigurasi');
            });

            $('#btnAddDriverRow').on('click', function() {
                addDriverRow(null);
            });

            $('#btnResetDriverForm').on('click', resetDriverForm);
            $('#btnPreviewDriver').on('click', previewServer);

            $('#driverRows').on('change', '.tujuan-driver-row', function() {
                $(this).removeClass('is-invalid');
                $(this).closest('.driver-row-field').find('.row-error').text('');
                updatePreviewTotal();
            });

            $('#driverRows').on('input', '.jumlah-driver-row', function() {
                formatInput(this);
                $(this).removeClass('is-invalid');
                $(this).closest('.driver-row-field').find('.row-error').text('');
                updatePreviewTotal();
            });

            $('#driverRows').on('click', '.driver-remove-row', function() {
                if ($(this).prop('disabled')) {
                    return;
                }

                $(this).closest('.driver-input-row').remove();
                refreshRowNumbers();
                updatePreviewTotal();
            });

            $('#formGenerateDriver').on('submit', function(event) {
                event.preventDefault();

                const entries = collectEntries();

                if (!entries) {
                    return;
                }

                const button = $('#btnSubmitDriver');
                const originalHtml = button.html();

                $.ajax({
                    url: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDriver.store") }}",
                    method: 'POST',
                    data: {
                        periode: $('#periodeGenerateDriver').val(),
                        pegawai_id: selectedPegawaiId(),
                        entries: entries
                    },
                    beforeSend: function() {
                        button.prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm me-1"></span> Generate'
                            );
                    },
                    success: function(response) {
                        Swal.fire('Berhasil', response.message, 'success');
                        refreshAll();
                        showSection('riwayat');
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON || {};
                        if (!showServerEntryErrors(response.errors || {})) {
                            Swal.fire('Gagal', errorMessage(xhr), 'error');
                        }
                    },
                    complete: function() {
                        button.prop('disabled', false).html(originalHtml);
                    }
                });
            });

            $('#formConfigDriver').on('submit', function(event) {
                event.preventDefault();

                const button = $('#btnSaveConfigDriver');
                const originalHtml = button.html();
                $('#configPremiBersamaDriverError').text('');

                $.ajax({
                    url: "{{ route("backOffice.keuangan.hitungPremi.generatePremiDriver.updateConfig") }}",
                    method: 'PUT',
                    data: {
                        premi_bersama_percent: $('#configPremiBersamaDriver').val()
                    },
                    beforeSend: function() {
                        button.prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm me-1"></span> Simpan'
                            );
                    },
                    success: function(response) {
                        configDriver = response.data || configDriver;
                        renderConfigPercent();
                        updatePreviewTotal();
                        refreshAll();
                        Swal.fire('Berhasil', response.message, 'success');
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors || {};
                        if (errors.premi_bersama_percent) {
                            $('#configPremiBersamaDriverError').text(errors
                                .premi_bersama_percent[0]);
                            return;
                        }
                        Swal.fire('Gagal', errorMessage(xhr), 'error');
                    },
                    complete: function() {
                        button.prop('disabled', false).html(originalHtml);
                    }
                });
            });

            $('#configPremiBersamaDriver').on('input', renderConfigPercent);

            $('#tujuanDriverHarga').on('input', function() {
                formatInput(this);
            });

            $('#btnResetTujuanDriver').on('click', resetTujuanForm);

            $('#formTujuanDriver').on('submit', function(event) {
                event.preventDefault();

                const id = $('#tujuanDriverId').val();
                const url = id ?
                    "{{ route("backOffice.keuangan.hitungPremi.generatePremiDriver.updateTujuan", ["id" => "__ID__"]) }}"
                    .replace('__ID__', id) :
                    "{{ route("backOffice.keuangan.hitungPremi.generatePremiDriver.storeTujuan") }}";
                const method = id ? 'PUT' : 'POST';
                const button = $('#btnSaveTujuanDriver');
                const originalHtml = button.html();
                resetTujuanErrors();

                $.ajax({
                    url: url,
                    method: method,
                    data: {
                        kode: $('#tujuanDriverKode').val(),
                        nama_tujuan: $('#tujuanDriverNama').val(),
                        harga: numeric($('#tujuanDriverHarga').val()),
                        is_active: $('#tujuanDriverActive').val(),
                        sort_order: $('#tujuanDriverSort').val()
                    },
                    beforeSend: function() {
                        button.prop('disabled', true).html(
                            '<span class="spinner-border spinner-border-sm me-1"></span> Simpan'
                            );
                    },
                    success: function(response) {
                        Swal.fire('Berhasil', response.message, 'success');
                        resetTujuanForm();
                        $.when(loadTujuanList(), loadTujuanOptions()).then(function() {
                            refreshAll();
                        });
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors || {};
                        if (errors.kode) {
                            $('#tujuanDriverKodeError').text(errors.kode[0]);
                        }
                        if (errors.nama_tujuan) {
                            $('#tujuanDriverNamaError').text(errors.nama_tujuan[0]);
                        }
                        if (errors.harga) {
                            $('#tujuanDriverHargaError').text(errors.harga[0]);
                        }
                        if (!Object.keys(errors).length) {
                            Swal.fire('Gagal', errorMessage(xhr), 'error');
                        }
                    },
                    complete: function() {
                        button.prop('disabled', false).html(originalHtml);
                    }
                });
            });

            $('#driverTujuanList').on('click', '.btn-edit-tujuan-driver', function() {
                const id = $(this).data('id');
                const item = tujuanList.find(function(row) {
                    return String(row.id) === String(id);
                });

                if (item) {
                    fillTujuanForm(item);
                }
            });

            $('#driverTujuanList').on('click', '.btn-delete-tujuan-driver', function() {
                const id = $(this).data('id');
                const template =
                    "{{ route("backOffice.keuangan.hitungPremi.generatePremiDriver.deleteTujuan", ["id" => "__ID__"]) }}";

                Swal.fire({
                    icon: 'warning',
                    title: 'Hapus tujuan ambulance?',
                    text: 'Histori generate lama tetap menyimpan snapshot tujuan.',
                    showCancelButton: true,
                    confirmButtonText: 'Hapus Tujuan',
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
                        resetTujuanForm();
                        $.when(loadTujuanList(), loadTujuanOptions()).then(refreshAll);
                    }
                });
            });

            $('#tableGenerateDriver').on('click', '.btn-detail-driver', function() {
                openDetail($(this).data('id'), false);
            });

            $('#tableGenerateDriver').on('click', '.btn-edit-driver', function() {
                openDetail($(this).data('id'), true);
            });

            $('#tableGenerateDriver').on('click', '.btn-lock-driver', function() {
                changeLock($(this).data('id'), 'lock');
            });

            $('#tableGenerateDriver').on('click', '.btn-unlock-driver', function() {
                changeLock($(this).data('id'), 'unlock');
            });

            $('#tableGenerateDriver').on('click', '.btn-delete-driver', function() {
                deleteResult($(this).data('id'));
            });

            $.when(loadConfig(), loadTujuanList(), loadTujuanOptions()).then(function() {
                resetDriverForm();
                refreshAll();
            });
        });
    </script>