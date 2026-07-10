<script>
    $(function() {
        const modal = $('#potonganPegawaiModal');
        const container = $("#potonganContainer");

        let potonganList = [];
        let changedData = {};
        let gapok = 0;
        let payrollCache = {};

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });

        if ($.fn.dropify) {
            $('.dropify').dropify();
        }

        function initSelect2(el, parentModal, placeholder = '-- Pilih --') {
            if ($(el).hasClass("select2-hidden-accessible")) {
                $(el).select2('destroy');
            }

            $(el).select2({
                dropdownParent: $(parentModal),
                width: '100%',
                placeholder: placeholder,
                allowClear: true
            });
        }

        function loadPegawai(target, excludeNik = null) {
            let select = $(target);
            select.html('<option value="">-- Pilih Pegawai --</option>');

            return $.ajax({
                url: "{{ route("masterData.keuangan.potongan.getPegawai") }}",
                method: 'GET',
                success: function(res) {
                    res.forEach(p => {
                        if (excludeNik && p.nik == excludeNik) return;

                        select.append(`
                            <option value="${p.nik}"
                                data-nama="${p.nama}"
                                data-jbtn="${p.jbtn}"
                                data-status="${p.stts_kerja}"
                                data-gapok="${p.gaji_pokok || 0}">
                                ${p.nik} - ${p.nama}
                            </option>
                        `);
                    });
                }
            });
        }

        function loadPotongan() {
            return $.ajax({
                url: "{{ route("masterData.keuangan.potongan.getGuideJenisPotongan") }}",
                method: 'GET',
                success: function(res) {
                    potonganList = res;
                }
            });
        }

        function ensureMasterData() {
            if (potonganList.length) {
                return $.Deferred().resolve().promise();
            }

            return loadPotongan();
        }

        function loadPayrollForNik(nik) {
            if (payrollCache[nik]) {
                return $.Deferred().resolve(payrollCache[nik]).promise();
            }

            return $.get(`/simrs/masterData/keuangan/potonganPegawai/getGapokById/${nik}`)
                .then(function(res) {
                    payrollCache[nik] = {
                        gapok: parseFloat(res?.gaji_pokok) || 0,
                        nama: res?.nama || '-',
                        jabatan: res?.jbtn || '-',
                        status: res?.stts_kerja || '-'
                    };

                    return payrollCache[nik];
                });
        }

        function formatRupiah(angka) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 0
            }).format(parseFloat(angka) || 0);
        }

        function labelTipe(tipe) {
            if (tipe === 'nominal') return 'Nominal tetap';
            if (tipe === 'persen_gapok') return 'Persen gaji pokok';
            if (tipe === 'persen_total_gaji') return 'Persen gaji tahap 1 + 2';
            return 'Manual';
        }

        function hitungNominal(tipe, nilai, gajiPokok, manualNominal = 0) {
            nilai = parseFloat(nilai) || 0;
            gajiPokok = parseFloat(gajiPokok) || 0;

            if (tipe === 'nominal') return Math.round(nilai);
            if (tipe === 'persen_gapok') return Math.round(gajiPokok * nilai / 100);
            if (tipe === 'persen_total_gaji') return 0;
            return Math.round(parseFloat(manualNominal) || 0);
        }

        function statusBadge(status) {
            switch (status) {
                case 'T':
                    return '<span class="badge bg-primary">Tetap</span>';
                case 'FT':
                    return '<span class="badge bg-success">Kontrak</span>';
                case 'PT':
                    return '<span class="badge bg-warning text-dark">Part Time</span>';
                default:
                    return '<span class="badge bg-secondary">-</span>';
            }
        }

        function potonganOptions() {
            let options = `<option value="">-- Pilih Potongan --</option>`;

            potonganList.forEach(p => {
                options += `
                    <option value="${p.id}"
                        data-tipe="${p.tipe}"
                        data-nilai="${p.nilai || 0}">
                        ${p.kode} - ${p.nama}
                    </option>`;
            });

            return options;
        }

        function createRow() {
            return `
            <div class="card border potongan-item">
                <div class="card-body py-3">
                    <div class="row g-3 align-items-end">
                        <input type="hidden" name="nominal[]" class="nominal-hidden">

                        <div class="col-md-4">
                            <label class="form-label">Potongan</label>
                            <select name="potongan_id[]" class="form-select select2-potongan">
                                ${potonganOptions()}
                            </select>
                        </div>

                        <div class="col-md-4 extra-input"></div>

                        <div class="col-md-3">
                            <label class="form-label">Nominal</label>
                            <input type="text" class="form-control nominal-preview" readonly>
                        </div>

                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-light btn-icon removeRow">
                                <i data-feather="x"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;
        }

        function refreshPotonganOptions() {
            let selectedValues = [];

            $('.select2-potongan').each(function() {
                let val = $(this).val();
                if (val) selectedValues.push(val);
            });

            $('.select2-potongan').each(function() {
                let current = $(this);

                current.find('option').each(function() {
                    let val = $(this).val();
                    if (!val) return;

                    $(this).prop('disabled', selectedValues.includes(val) && current.val() != val);
                });
            });
        }

        function applyPotonganSelection(
            select,
            payrollGapok,
            rowSelector = '.potongan-item'
        ) {
            let selected = select.find(':selected');
            let tipe = selected.data('tipe');
            let nilai = parseFloat(selected.data('nilai')) || 0;
            let row = select.closest(rowSelector);
            let extra = row.find('.extra-input');
            let preview = row.find('.nominal-preview');
            let hidden = row.find('.nominal-hidden');

            extra.html('');
            preview.val('');
            hidden.val('');

            if (!tipe) return;

            if (tipe === 'manual') {
                extra.html(`
                    <input type="number" class="form-control nominal-manual"
                        placeholder="Nominal potongan" min="0">
                `);
                preview.val(formatRupiah(0));
                hidden.val(0);
            } else if (tipe === 'nominal') {
                extra.html(`
                    <div class="small text-muted">
                        ${labelTipe(tipe)} dari master potongan
                    </div>
                `);
                let nominal = hitungNominal(tipe, nilai, payrollGapok);
                preview.val(formatRupiah(nominal));
                hidden.val(nominal);
            } else if (tipe === 'persen_gapok') {
                extra.html(`
                    <div class="small text-muted">
                        ${nilai}% x ${formatRupiah(payrollGapok)}
                    </div>
                `);
                let nominal = hitungNominal(tipe, nilai, payrollGapok);
                preview.val(formatRupiah(nominal));
                hidden.val(nominal);
            } else if (tipe === 'persen_total_gaji') {
                extra.html(`
                    <div class="small text-muted">
                        ${nilai}% x gaji tahap 1 + 2<br>
                        Dihitung saat generate gaji tahap 2
                    </div>
                `);
                preview.val('Dihitung saat generate');
                hidden.val(0);
            }
        }

        container.on('change', '.select2-potongan', function() {
            applyPotonganSelection($(this), gapok);
            refreshPotonganOptions();
        });

        container.on('input', '.nominal-manual', function() {
            let val = parseFloat($(this).val()) || 0;
            let row = $(this).closest('.potongan-item');

            row.find('.nominal-preview').val(formatRupiah(val));
            row.find('.nominal-hidden').val(val);
        });

        modal.on('shown.bs.modal', async function() {
            container.html('');
            $("#pegawaiSelect").val(null).trigger('change');
            $("#pegawaiInfo").addClass('d-none');
            gapok = 0;

            await loadPegawai('#pegawaiSelect');
            await loadPotongan();

            initSelect2('#pegawaiSelect', modal, 'Pilih Pegawai');

            let firstRow = $(createRow());
            container.append(firstRow);
            initSelect2(firstRow.find('.select2-potongan'), modal, 'Pilih Potongan');
            feather.replace();
        });

        modal.on('hidden.bs.modal', function() {
            container.html('');
            $("#pegawaiSelect").val(null).trigger('change');
            $("#pegawaiInfo").addClass('d-none');
            $('#potonganPegawaiForm')[0].reset();
        });

        $("#addRow").click(function() {
            let row = $(createRow());
            container.append(row);
            initSelect2(row.find('.select2-potongan'), modal, 'Pilih Potongan');
            refreshPotonganOptions();
            feather.replace();
        });

        container.on("click", ".removeRow", function() {
            if ($(".potongan-item").length === 1) {
                Swal.fire("Minimal 1 potongan", "", "warning");
                return;
            }

            $(this).closest(".potongan-item").remove();
            refreshPotonganOptions();
        });

        $("#pegawaiSelect").on('change', function() {
            let selected = $(this).find(':selected');
            let nik = $(this).val();

            if (!nik) {
                $("#pegawaiInfo").addClass('d-none');
                gapok = 0;
                return;
            }

            $("#pegawaiInfo").removeClass('d-none');
            $("#infoNama").text(selected.data('nama') || '-');
            $("#infoJabatan").text(selected.data('jbtn') || '-');
            $("#infoStatus").html(statusBadge(selected.data('status')));
            $("#infoGapok").text('-');

            loadPayrollForNik(nik).done(function(payroll) {
                gapok = payroll.gapok;
                $("#infoGapok").text(formatRupiah(gapok));

                $('.select2-potongan').each(function() {
                    $(this).trigger('change');
                });
            });
        });

        let potonganPegawaiTable = $('#tablePotonganPegawai').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route("masterData.keuangan.potongan.getPotonganPegawaiTable") }}",
                type: "GET"
            },
            columns: [{
                    className: 'dt-control text-center',
                    orderable: false,
                    data: null,
                    defaultContent: '<i class="mdi mdi-chevron-right"></i>'
                },
                {
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'nama'
                },
                {
                    data: 'jabatan'
                },
                {
                    data: 'status'
                },
                {
                    data: 'total',
                    className: 'text-end'
                },
            ]
        });

        function format(row) {
            let nik = row.nik || row.id;

            return `
                <div class="p-3 bg-light rounded-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="fw-semibold text-dark">
                                Detail Potongan
                            </div>
                            <small class="text-muted">
                                Rincian potongan pegawai
                            </small>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <button type="button" class="btn btn-outline-primary btn-sm btn-add-potongan"
                                data-nik="${nik}">
                                <i class="mdi mdi-plus"></i> Tambah
                            </button>

                            <div class="text-end">
                                <small class="text-muted d-block">Total</small>
                                <span class="fw-bold text-danger fs-4 total-pegawai">
                                    ${row.total}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="potongan-detail-body">
                        ${row.potongan}
                    </div>
                </div>
            `;
        }

        $('#tablePotonganPegawai tbody').on('click', 'td.dt-control', function() {
            let tr = $(this).closest('tr');
            let row = potonganPegawaiTable.row(tr);
            let data = row.data();
            if (!data) return;

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
                $(this).html('<i class="mdi mdi-chevron-right"></i>');
            } else {
                row.child(`
                    <div class="expand-wrapper" data-nik="${data.nik || data.id}">
                        ${format(data)}
                    </div>
                `).show();

                tr.addClass('shown');
                $(this).html('<i class="mdi mdi-chevron-down"></i>');
            }
        });

        $('.dataTables_filter').hide();

        $('#searchPotonganPegawai').on('keyup', function() {
            potonganPegawaiTable.search(this.value).draw();
        });

        $('#downloadTemplateBtn').on('click', function() {
            window.location.href =
                "{{ route("masterData.keuangan.potongan.getPotonganPegawaiExportTemplate") }}";
        });

        $('#submitFormExcell').on('click', function() {
            let btn = $(this);
            let fileInput = $('#myDropify')[0];
            let file = fileInput.files[0];

            if (!file) {
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
                        <div id="uploadProgressBar"
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
                url: "{{ route("masterData.keuangan.potongan.importPotonganPegawai") }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    let xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            let percent = Math.round((evt.loaded / evt.total) * 100);
                            $('#uploadProgressBar').css('width', percent + '%').text(percent + '%');
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
                                $('#potonganPegawaiModalExcell').modal('hide');
                                $('#myDropify').val('');
                                potonganPegawaiTable.ajax.reload(null, false);
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Import Gagal',
                            text: response.message || 'Terjadi kesalahan saat import data'
                        });
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false);

                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Gagal',
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan pada server'
                    });
                }
            });
        });

        $('#potonganPegawaiForm').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').text('');

            Swal.fire({
                title: 'Simpan data potongan pegawai?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, simpan',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: "{{ route("masterData.keuangan.potonganPegawai.store") }}",
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
                            $('#potonganPegawaiModal').modal('hide');

                            Swal.fire({
                                icon: 'success',
                                title: response.message,
                                toast: true,
                                position: 'top-end',
                                timer: 2500,
                                showConfirmButton: false
                            });

                            form[0].reset();
                            potonganPegawaiTable.ajax.reload(null, false);
                        }
                    },
                    error: function(xhr) {
                        Swal.close();

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                let input = $('[name="' + key + '"]');
                                input.addClass('is-invalid');
                                $('#error-' + key).text(value[0]);
                            });

                            if (xhr.responseJSON?.message) {
                                Swal.fire({
                                    icon: 'warning',
                                    title: xhr.responseJSON.message
                                });
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Terjadi Kesalahan',
                                text: xhr.responseJSON?.message || 'Server error'
                            });
                        }
                    }
                });
            });
        });

        function updateTotal(wrapper) {
            let total = 0;

            wrapper.find('.input-nominal').each(function() {
                total += parseInt($(this).val()) || 0;
            });

            wrapper.find('.total-pegawai').text(total.toLocaleString('id-ID'));
        }

        function markInlineChanged(rowItem) {
            let input = rowItem.find('.input-nominal');
            let oldValue = input.data('old');
            let currentValue = input.val();
            let changed = String(currentValue ?? '') !== String(oldValue ?? '');

            input.toggleClass('border-warning', changed);
            rowItem.find('.btn-save').prop('disabled', !changed);
        }

        $('#tablePotonganPegawai').on('input', '.input-nominal', function() {
            let input = $(this);
            markInlineChanged(input.closest('.list-group-item'));
            updateTotal(input.closest('.expand-wrapper'));
        });

        $('#tablePotonganPegawai').on('click', '.btn-save', function() {
            let btn = $(this);
            let row = btn.closest('.list-group-item');
            let id = btn.data('id');
            let nominal = row.find('.input-nominal').val() || 0;

            $.ajax({
                url: `/simrs/masterData/keuangan/potongan/update-inline/${id}`,
                method: 'PUT',
                data: {
                    nominal: nominal,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    btn.prop('disabled', true);
                    btn.html('<span class="spinner-border spinner-border-sm"></span>');
                },
                success: function() {
                    row.find('.input-nominal')
                        .data('old', nominal)
                        .removeClass('border-warning');

                    btn.html('<i class="mdi mdi-content-save"></i>');
                    btn.prop('disabled', true);

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil disimpan',
                        toast: true,
                        position: 'top-end',
                        timer: 1200,
                        showConfirmButton: false
                    });

                    potonganPegawaiTable.ajax.reload(null, false);
                },
                error: function() {
                    btn.html('<i class="mdi mdi-content-save"></i>');
                    btn.prop('disabled', false);
                    Swal.fire("Gagal update", "", "error");
                }
            });
        });

        $('#tablePotonganPegawai').on('click', '.btn-delete', function() {
            let btn = $(this);
            let id = btn.data('id');

            Swal.fire({
                title: 'Hapus potongan?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: `/simrs/masterData/keuangan/potonganPegawai/${id}/delete`,
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function() {
                        btn.html('<span class="spinner-border spinner-border-sm"></span>');
                    },
                    success: function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Dihapus',
                            toast: true,
                            position: 'top-end',
                            timer: 1200,
                            showConfirmButton: false
                        });
                        potonganPegawaiTable.ajax.reload(null, false);
                    },
                    error: function() {
                        btn.html('<i class="mdi mdi-trash-can"></i>');
                        Swal.fire("Gagal hapus", "", "error");
                    }
                });
            });
        });

        function getUsedPotonganIds(wrapper, exceptSelect = null) {
            let ids = [];

            wrapper.find('.list-group-item[data-potongan-id]').not('.new-row').each(function() {
                let id = $(this).data('potongan-id');
                if (id) ids.push(String(id));
            });

            wrapper.find('.new-row .select-potongan').each(function() {
                if (exceptSelect && this === exceptSelect[0]) return;
                let id = $(this).val();
                if (id) ids.push(String(id));
            });

            return ids;
        }

        function loadPotonganDropdown(el) {
            let wrapper = el.closest('.expand-wrapper');
            let usedIds = getUsedPotonganIds(wrapper, el);
            let currentValue = el.val();

            el.html(`<option value="">-- Pilih Potongan --</option>`);

            potonganList.forEach(p => {
                if (usedIds.includes(String(p.id)) && String(p.id) !== String(currentValue)) return;

                el.append(`
                    <option value="${p.id}"
                        data-tipe="${p.tipe}"
                        data-nilai="${p.nilai || 0}">
                        ${p.kode} - ${p.nama}
                    </option>
                `);
            });

            if (el.find('option').length === 1) {
                el.html(`<option value="">Semua potongan sudah ada</option>`);
                el.prop('disabled', true);
            } else {
                el.prop('disabled', false);
            }
        }

        function createNewRow(nik) {
            return `
                <div class="list-group-item py-3 new-row" data-nik="${nik}">
                    <div class="row align-items-end g-2">
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Potongan</label>
                            <select class="form-select form-select-sm select-potongan"></select>
                        </div>

                        <div class="col-md-3 extra-input"></div>

                        <div class="col-md-3">
                            <label class="form-label small text-muted">Nominal</label>
                            <input type="number"
                                class="form-control form-control-sm text-end fw-semibold input-nominal"
                                value="0"
                                min="0"
                                readonly>
                        </div>

                        <div class="col-md-2 text-end">
                            <button type="button" class="btn btn-success btn-sm btn-save-new" title="Simpan">
                                <i class="mdi mdi-check"></i>
                            </button>

                            <button type="button" class="btn btn-light btn-sm btn-cancel-new" title="Batal">
                                <i class="mdi mdi-close"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }

        $('#tablePotonganPegawai').on('click', '.btn-add-potongan', function() {
            let btn = $(this);
            let nik = btn.data('nik');
            let wrapper = btn.closest('.expand-wrapper');
            let list = wrapper.find('.potongan-detail-list').first();

            if (list.find('.new-row').length > 0) return;

            btn.prop('disabled', true);

            $.when(ensureMasterData())
                .then(function() {
                    return loadPayrollForNik(nik);
                })
                .done(function(payroll) {
                    if (potonganList.length <= getUsedPotonganIds(wrapper).length) {
                        Swal.fire("Semua potongan sudah ada", "", "info");
                        return;
                    }

                    wrapper.data('gapok', payroll.gapok);
                    let row = $(createNewRow(nik));
                    list.prepend(row);
                    loadPotonganDropdown(row.find('.select-potongan'));
                })
                .fail(function() {
                    Swal.fire("Gagal memuat data pegawai", "", "error");
                })
                .always(function() {
                    btn.prop('disabled', false);
                });
        });

        $('#tablePotonganPegawai').on('change', '.select-potongan', function() {
            let select = $(this);
            let selected = select.find(':selected');
            let row = select.closest('.new-row');
            let wrapper = row.closest('.expand-wrapper');
            let tipe = selected.data('tipe');
            let nilai = parseFloat(selected.data('nilai')) || 0;
            let payrollGapok = parseFloat(wrapper.data('gapok')) || 0;
            let extra = row.find('.extra-input');
            let input = row.find('.input-nominal');

            extra.html('');
            input.val(0).prop('readonly', true);

            if (tipe === 'manual') {
                extra.html(`
                    <div class="small text-muted">
                        Isi nominal pada kolom nominal
                    </div>
                `);
                input.val('').prop('readonly', false);
            } else if (tipe === 'nominal') {
                extra.html(`
                    <div class="small text-muted">
                        Nominal tetap dari master potongan
                    </div>
                `);
                input.val(hitungNominal(tipe, nilai, payrollGapok));
            } else if (tipe === 'persen_gapok') {
                extra.html(`
                    <div class="small text-muted">
                        ${nilai}% x ${formatRupiah(payrollGapok)}
                    </div>
                `);
                input.val(hitungNominal(tipe, nilai, payrollGapok));
            } else if (tipe === 'persen_total_gaji') {
                extra.html(`
                    <div class="small text-muted">
                        ${nilai}% x gaji tahap 1 + 2<br>
                        Dihitung saat generate gaji tahap 2
                    </div>
                `);
                input.val(0);
            }

            updateTotal(wrapper);
        });

        $('#tablePotonganPegawai').on('input', '.new-row .input-nominal', function() {
            updateTotal($(this).closest('.expand-wrapper'));
        });

        $('#tablePotonganPegawai').on('click', '.btn-cancel-new', function() {
            let row = $(this).closest('.new-row');
            let wrapper = row.closest('.expand-wrapper');

            row.remove();
            updateTotal(wrapper);
        });

        $('#tablePotonganPegawai').on('click', '.btn-save-new', function() {
            let btn = $(this);
            let row = btn.closest('.new-row');
            let wrapper = row.closest('.expand-wrapper');
            let nik = row.data('nik') || wrapper.data('nik');
            let potonganId = row.find('.select-potongan').val();
            let tipe = row.find('.select-potongan option:selected').data('tipe');
            let nominal = parseInt(row.find('.input-nominal').val()) || 0;

            if (!nik) {
                Swal.fire("Pegawai tidak ditemukan", "", "warning");
                return;
            }

            if (!potonganId) {
                Swal.fire("Pilih potongan terlebih dahulu", "", "warning");
                return;
            }

            if (tipe === 'manual' && nominal < 1) {
                Swal.fire("Nominal wajib diisi", "", "warning");
                return;
            }

            $.ajax({
                url: "{{ route("masterData.keuangan.potonganPegawai.store") }}",
                method: 'POST',
                data: {
                    nik: nik,
                    potongan_id: [potonganId],
                    nominal: [nominal],
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    btn.prop('disabled', true);
                    btn.html('<span class="spinner-border spinner-border-sm"></span>');
                },
                success: function(res) {
                    Swal.fire({
                        icon: 'success',
                        title: res.message || 'Berhasil disimpan',
                        toast: true,
                        position: 'top-end',
                        timer: 1200,
                        showConfirmButton: false
                    });

                    potonganPegawaiTable.ajax.reload(null, false);
                },
                error: function(xhr) {
                    btn.prop('disabled', false);
                    btn.html('<i class="mdi mdi-check"></i>');
                    Swal.fire(
                        "Gagal",
                        xhr.responseJSON?.message || 'Gagal menyimpan potongan',
                        "error"
                    );
                }
            });
        });

        $('#btnDistribusi').click(function() {
            $('#modalDistribusi').modal('show');
        });

        $('#modalDistribusi').on('shown.bs.modal', async function() {
            let modalDistribusi = '#modalDistribusi';

            $('#pegawaiSumber').val(null).trigger('change');
            $('#pegawaiTujuan').html('');
            $('#potonganSelect').html('');

            await loadPegawai('#pegawaiSumber');
            await loadPegawai('#pegawaiTujuan');

            initSelect2('#pegawaiSumber', modalDistribusi, 'Pilih Sumber');
            initSelect2('#pegawaiTujuan', modalDistribusi, 'Pilih Tujuan');
            initSelect2('#potonganSelect', modalDistribusi, 'Pilih Potongan');
        });

        $('#pegawaiSumber').on('change', function() {
            let nik = $(this).val();
            if (!nik) return;

            $.get(`/simrs/masterData/keuangan/potongan/by-pegawai/${nik}`, function(res) {
                let select = $('#potonganSelect');
                select.html('');

                res.forEach(p => {
                    select.append(`
                        <option value="${p.potongan_id}" selected>
                            ${p.nama}
                        </option>
                    `);
                });

                initSelect2('#potonganSelect', '#modalDistribusi', 'Pilih Potongan');
            });
        });

        $('#btnPreviewDistribusi').click(function() {
            let sumber = $('#pegawaiSumber').val();
            let tujuan = $('#pegawaiTujuan').val();
            let potongan = $('#potonganSelect').val();

            if (!sumber || !tujuan || !potongan) {
                Swal.fire("Lengkapi semua field", "", "warning");
                return;
            }

            $.ajax({
                url: '/simrs/masterData/keuangan/potongan/preview-distribusi',
                method: 'POST',
                data: {
                    sumber: sumber,
                    tujuan: tujuan,
                    potongan_id: potongan,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                beforeSend: function() {
                    $('#previewContent').html(`
                        <div class="text-center py-3 text-muted">
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Memuat preview...
                        </div>
                    `);
                    $('#previewDistribusi').removeClass('d-none');
                },
                success: function(res) {
                    let totalNew = 0;
                    let totalSkip = 0;
                    let grouped = {};

                    res.forEach(item => {
                        if (!grouped[item.nama_pegawai]) grouped[item.nama_pegawai] = [];
                        grouped[item.nama_pegawai].push(item);

                        if (item.status === 'new') totalNew++;
                        else totalSkip++;
                    });

                    let html = '';

                    Object.keys(grouped).forEach(nama => {
                        html += `
                            <div class="col-md-6">
                                <div class="border rounded p-2 mb-2">
                                    <div class="fw-semibold mb-1">${nama}</div>
                        `;

                        grouped[nama].forEach(item => {
                            let badgeClass = item.status === 'new' ? 'bg-success' : 'bg-secondary';
                            let badge = item.status === 'new' ? 'Baru' : 'Ada';

                            html += `
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>${item.nama_potongan}</span>
                                    <span class="badge ${badgeClass}">${badge}</span>
                                </div>
                            `;
                        });

                        html += `</div></div>`;
                    });

                    let summary = `
                        <div class="d-flex justify-content-between mb-2">
                            <div class="small text-success">
                                <i class="mdi mdi-check-circle"></i>
                                Akan ditambahkan: <b>${totalNew}</b>
                            </div>

                            <div class="small text-muted">
                                <i class="mdi mdi-information"></i>
                                Sudah ada: <b>${totalSkip}</b>
                            </div>
                        </div>
                    `;

                    $('#previewContent').html(`
                        ${summary}
                        <div class="row" style="max-height:300px; overflow-y:auto;">
                            ${html}
                        </div>
                    `);
                },
                error: function() {
                    $('#previewContent').html(`
                        <div class="text-danger text-center py-3">
                            Gagal memuat preview
                        </div>
                    `);
                }
            });
        });

        function resetModalDistribusi() {
            $('#pegawaiSumber').val(null).trigger('change');
            $('#pegawaiTujuan').val(null).trigger('change');
            $('#potonganSelect').val(null).trigger('change');
            $('#pegawaiTujuan').html('');
            $('#potonganSelect').html('');
            $('#previewDistribusi').addClass('d-none');
            $('#previewContent').html('');
            $('#prosesDistribusi')
                .prop('disabled', false)
                .html('<i class="mdi mdi-check me-1"></i> Distribusikan');
        }

        $('#modalDistribusi').on('hidden.bs.modal', function() {
            resetModalDistribusi();
        });

        $('#prosesDistribusi').click(function(e) {
            e.preventDefault();

            let sumber = $('#pegawaiSumber').val();
            let tujuan = $('#pegawaiTujuan').val();
            let potongan = $('#potonganSelect').val();

            if (!sumber) {
                Swal.fire("Pilih pegawai sumber", "", "warning");
                return;
            }

            if (!tujuan || tujuan.length === 0) {
                Swal.fire("Pilih pegawai tujuan", "", "warning");
                return;
            }

            if (!potongan || potongan.length === 0) {
                Swal.fire("Pilih potongan", "", "warning");
                return;
            }

            Swal.fire({
                title: 'Distribusikan potongan?',
                text: 'Potongan akan disalin ke pegawai tujuan',
                icon: 'question',
                showCancelButton: true
            }).then(result => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: "{{ route("masterData.keuangan.potongan.distribusi") }}",
                    method: 'POST',
                    data: {
                        sumber: sumber,
                        tujuan: tujuan,
                        potongan_id: potongan,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function() {
                        $('#prosesDistribusi')
                            .prop('disabled', true)
                            .html('<span class="spinner-border spinner-border-sm"></span> Memproses...');
                    },
                    success: function(res) {
                        $('#modalDistribusi').modal('hide');

                        Swal.fire({
                            icon: 'success',
                            title: 'Distribusi berhasil',
                            html: `
                                <div class="small text-start">
                                    Ditambahkan: <b>${res.data.added}</b><br>
                                    Dilewati: <b>${res.data.skipped}</b>
                                </div>
                            `,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        potonganPegawaiTable.ajax.reload();
                    },
                    error: function(xhr) {
                        Swal.fire(
                            "Error",
                            xhr.responseJSON?.message || 'Gagal distribusi',
                            "error"
                        );
                    },
                    complete: function() {
                        $('#prosesDistribusi')
                            .prop('disabled', false)
                            .html('<i class="mdi mdi-check me-1"></i> Distribusikan');
                    }
                });
            });
        });
    });
</script>
