<script>
    $(function() {

        const modal = $('#tunjanganPegawaiModal');
        const container = $("#tunjanganContainer");

        let tunjanganList = [];
        let changedData = {};
        let targetNik = null;

        // --- Setup CSRF untuk semua AJAX request
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });

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

        // LOAD PEGAWAI
        function loadPegawai(target, excludeNik = null) {

            let select = $(target);

            select.html('<option value="">-- Pilih Pegawai --</option>');

            return $.ajax({
                url: "{{ route("masterData.keuangan.tunjangan.getPegawai") }}",
                method: 'GET',
                success: function(res) {

                    res.forEach(p => {

                        if (excludeNik && p.nik == excludeNik) return;

                        select.append(`
                    <option value="${p.nik}"
                        data-nama="${p.nama}"
                        data-jbtn="${p.jbtn}"
                        data-status="${p.stts_kerja}">
                        ${p.nik} - ${p.nama}
                    </option>
                `);

                    });

                }
            });
        }

        // LOAD TUNJANGAN
        function loadTunjangan() {
            return $.ajax({
                url: "{{ route("masterData.keuangan.tunjangan.getGuideJenisTunjangan") }}",
                method: 'GET',
                success: function(res) {
                    tunjanganList = res;
                }
            });
        }

        // TEMPLATE ROW
        function createRow() {

            let options = `<option value="">-- Pilih Tunjangan --</option>`;

            tunjanganList.forEach(t => {
                options += `<option value="${t.id}">${t.kode} - ${t.nama}</option>`;
            });

            return `
            <div class="card border tunjangan-item">
                <div class="card-body py-3">
                    <div class="row g-3 align-items-end">

                        <div class="col-md-10">
                            <label class="form-label">Tunjangan</label>
                            <select name="tunjangan_id[]" class="form-select select2-tunjangan">
                                ${options}
                            </select>
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

        // refresh tunjnagan list
        function refreshTunjanganOptions() {

            let selectedValues = [];

            // ambil semua value yang sudah dipilih
            $('.select2-tunjangan').each(function() {
                let val = $(this).val();
                if (val) selectedValues.push(val);
            });

            // loop semua select
            $('.select2-tunjangan').each(function() {

                let current = $(this);

                current.find('option').each(function() {

                    let val = $(this).val();

                    // skip empty
                    if (!val) return;

                    // disable jika sudah dipakai di select lain
                    if (selectedValues.includes(val) && current.val() != val) {
                        $(this).prop('disabled', true);
                    } else {
                        $(this).prop('disabled', false);
                    }

                });

            });
        }

        $("#tunjanganContainer").on("change", ".select2-tunjangan", function() {

            let selectedValues = [];
            let duplicate = false;

            $(".select2-tunjangan").each(function() {
                let val = $(this).val();

                if (!val) return;

                if (selectedValues.includes(val)) {
                    duplicate = true;
                }

                selectedValues.push(val);
            });

            // 🔥 ALERT DI SINI
            if (duplicate) {
                Swal.fire("Tunjangan sudah dipilih!", "", "warning");
            }

            refreshTunjanganOptions();
        });

        // MODAL OPEN
        modal.on('shown.bs.modal', async function() {

            // =====================
            // RESET UI
            // =====================
            container.html('');
            $("#pegawaiSelect").val(null).trigger('change');
            $("#pegawaiInfo").addClass('d-none');

            // =====================
            // LOAD DATA
            // =====================
            await loadPegawai('#pegawaiSelect');
            await loadTunjangan();

            // =====================
            // INIT SELECT2 PEGAWAI
            // =====================
            initSelect2('#pegawaiSelect', modal, 'Pilih Pegawai');

            // =====================
            // CREATE FIRST ROW
            // =====================
            let firstRow = $(createRow());
            container.append(firstRow);

            // =====================
            // INIT SELECT2 TUNJANGAN
            // =====================
            let tunjanganSelect = firstRow.find('.select2-tunjangan');

            initSelect2(tunjanganSelect, modal, 'Pilih Tunjangan');

            // =====================
            // ICON
            // =====================
            feather.replace();
        });

        // MODAL CLOSE
        modal.on('hidden.bs.modal', function() {
            container.html('');
            $("#pegawaiSelect").val(null).trigger('change');
            $("#pegawaiInfo").addClass('d-none');
        });

        // ADD ROW
        $("#addRow").click(function() {

            // =====================
            // CREATE ROW
            // =====================
            let row = $(createRow());

            container.append(row);

            // =====================
            // INIT SELECT2
            // =====================
            let select = row.find('.select2-tunjangan');

            initSelect2(select, modal, 'Pilih Tunjangan');

            // =====================
            // REFRESH OPTIONS (ANTI DUPLICATE)
            // =====================
            refreshTunjanganOptions();

            // =====================
            // ICON
            // =====================
            feather.replace();
        });

        // REMOVE ROW
        container.on("click", ".removeRow", function() {

            if ($(".tunjangan-item").length === 1) {
                Swal.fire("Minimal 1 tunjangan", "", "warning");
                return;
            }

            $(this).closest(".tunjangan-item").remove();

            refreshTunjanganOptions();
        });
        container.on('change', '.select2-tunjangan', function() {
            refreshTunjanganOptions();
        });

        // PILIH PEGAWAI
        $("#pegawaiSelect").on('change', function() {

            let selected = $(this).find(':selected');
            let nik = $(this).val();

            if (!nik) {
                $("#pegawaiInfo").addClass('d-none');
                return;
            }

            let nama = selected.data('nama') || '-';
            let jbtn = selected.data('jbtn') || '-';
            let status = selected.data('status') || '-';

            let statusBadge = '';

            switch (status) {
                case 'T':
                    statusBadge = '<span class="badge bg-primary">Tetap</span>';
                    break;
                case 'FT':
                    statusBadge = '<span class="badge bg-success">Kontrak</span>';
                    break;
                case 'PT':
                    statusBadge = '<span class="badge bg-warning text-dark">Part Time</span>';
                    break;
                default:
                    statusBadge = '<span class="badge bg-secondary">-</span>';
            }

            $("#pegawaiInfo").removeClass('d-none');
            $("#infoNama").text(nama);
            $("#infoJabatan").text(jbtn);
            $("#infoStatus").html(statusBadge);
        });

        // DATATABLE
        let tunjanganPegawaiTable = $('#tableTunjanganPegawai').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route("masterData.keuangan.tunjangan.gettunjnaganPegawaiTable") }}",
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
            return `
                <div class="p-3 bg-light rounded-3">

                    <!-- HEADER -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <div class="fw-semibold text-dark">
                                Detail Tunjangan
                            </div>
                            <small class="text-muted">
                                Rincian tunjangan pegawai
                            </small>
                        </div>

                        <div class="text-end">
                            <small class="text-muted d-block">Total</small>
                            <span class="fw-bold text-primary fs-4 total-pegawai">
                                ${row.total}
                            </span>
                        </div>

                    </div>

                    <div class="list-group list-group-flush">
                        ${row.tunjangan}
                    </div>

                </div>
            `;
        }

        $('#tableTunjanganPegawai tbody').on('click', 'td.dt-control', function() {

            let tr = $(this).closest('tr');
            let row = tunjanganPegawaiTable.row(tr);

            if (row.child.isShown()) {

                // tutup
                row.child.hide();
                tr.removeClass('shown');

                $(this).html('<i class="mdi mdi-chevron-right"></i>');

            } else {

                // buka
                row.child(format(row.data())).show();
                tr.addClass('shown');

                $(this).html('<i class="mdi mdi-chevron-down"></i>');
            }
        });

        $('.dataTables_filter').hide();

        $('#searchTunjanganPegawai').on('keyup', function() {
            tunjanganPegawaiTable.search(this.value).draw();
        });

        // Modal Excell - Load Referensi Tunjangan
        function loadRefTunjangan() {
            $.get('/simrs/masterData/keuangan/tunjanganPegawai/guideJenisTunjangan', function(res) {

                let html = '';

                res.forEach(item => {
                    html += `
                <tr>
                    <td>${item.kode}</td>
                    <td>${item.nama}</td>
                    <td>${item.persentase}%</td>
                </tr>
            `;
                });

                $('#refTunjanganTable').html(html);
            });
        }

        $('#tunjanganPegawaiModalExcell').on('show.bs.modal', function() {
            loadRefTunjangan();
        });

        // --- Download Template
        $('#downloadTemplateBtn').on('click', function() {
            window.location.href =
                "{{ route("masterData.keuangan.tunjangan.getTunjanganPegawaiExportTemplate") }}";
        })

        // --- Upload Excell
        $('#submitFormExcell').on('click', function() {

            let btn = $(this);
            let fileInput = $('#myDropify')[0];
            let file = fileInput.files[0];

            // Validasi file
            if (!file) {

                $('#myDropify').addClass('shake border-danger');

                setTimeout(() => {
                    $('#myDropify').removeClass('shake border-danger');
                }, 600);

                Swal.fire({
                    icon: 'warning',
                    title: 'Peringatan',
                    text: 'Silakan pilih file Excel terlebih dahulu!'
                });

                return;
            }

            // Validasi ekstensi file
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

            // Disable tombol upload
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

                url: "{{ route("masterData.keuangan.tunjangan.importTunjanganPegawai") }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,

                xhr: function() {

                    let xhr = new window.XMLHttpRequest();

                    xhr.upload.addEventListener("progress", function(evt) {

                        if (evt.lengthComputable) {

                            let percent = Math.round((evt.loaded / evt.total) *
                                100);

                            $('#uploadProgressBar')
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

                                $('#gapokModalExcell').modal('hide');

                                // reset dropify
                                $('#myDropify').val('');

                                // reload DataTable
                                if (typeof tunjanganPegawaiTable !==
                                    'undefined') {
                                    tunjanganPegawaiTable.ajax.reload(null,
                                        false);
                                }

                            }
                        });

                    } else {

                        Swal.fire({
                            icon: 'error',
                            title: 'Import Gagal',
                            text: response.message ||
                                'Terjadi kesalahan saat import data'
                        });

                    }

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

        // SUBMIT AJAX
        $('#tunjanganPegawaiForm').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            let formData = form.serialize();
            let tunjanganPegawaiId = $('#tunjanganPegawaiId').val();

            let url = tunjanganPegawaiId ?
                `/simrs/masterData/keuangan/tunjangan/${tunjanganId}/update` :
                "{{ route("masterData.keuangan.tunjanganPegawai.store") }}";

            let method = tunjanganPegawaiId ? 'PUT' : 'POST';

            // RESET VALIDASI
            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').text('');

            Swal.fire({
                title: tunjanganPegawaiId ?
                    'Perbarui data tunjangan pegawai?' : 'Simpan data tunjangan pegawai?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, simpan',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {

                if (!result.isConfirmed) return;

                $.ajax({
                    url: url,
                    method: method,
                    data: formData,

                    // ✅ LOADING START
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Menyimpan...',
                            text: 'Mohon tunggu sebentar',
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },

                    success: function(response) {

                        Swal.close(); // ✅ tutup loading dulu

                        if (response.status === true) {

                            $('#tunjanganPegawaiModal').modal('hide');

                            Swal.fire({
                                icon: 'success',
                                title: response.message,
                                toast: true,
                                position: 'top-end',
                                timer: 2500,
                                showConfirmButton: false
                            });

                            form[0].reset();
                            $('#tunjanganPegawaiId').val('');

                            if (typeof tunjanganPegawaiTable !== 'undefined') {
                                tunjanganPegawaiTable.ajax.reload(null, false);
                            }
                        }
                    },

                    error: function(xhr) {

                        Swal.close(); // ✅ WAJIB

                        if (xhr.status === 422) {

                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {

                                let input = $('[name="' + key + '"]');

                                input.addClass('is-invalid');
                                $('#error-' + key).text(value[0]);

                            });

                        } else {

                            Swal.fire({
                                icon: 'error',
                                title: 'Terjadi Kesalahan',
                                text: xhr.responseJSON?.message ||
                                    'Server error'
                            });

                        }
                    },

                });

            });
        });

        // Inline editing untuk nominal tunjangan
        $('#tableTunjanganPegawai').on('click', '.btn-save', function() {

            let btn = $(this);
            let id = btn.data('id');
            let input = btn.closest('.d-flex').find('.input-nominal');
            let nominal = input.val();

            if (!nominal || nominal < 0) {
                Swal.fire("Nominal tidak valid", "", "warning");
                return;
            }

            $.ajax({
                url: `/simrs/masterData/keuangan/tunjangan/update-inline/${id}`,
                method: 'PUT',
                data: {
                    nominal: nominal,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },

                beforeSend: function() {
                    btn.html('<span class="spinner-border spinner-border-sm"></span>');
                },

                success: function(res) {

                    btn.html('<i class="mdi mdi-check"></i>');

                    let wrapper = btn.parents('.expand-wrapper');

                    let totalEl = wrapper.find('.total-pegawai');

                    let total = 0;

                    wrapper.find('.input-nominal').each(function() {
                        total += parseInt($(this).val()) || 0;
                    });

                    // update expandable
                    totalEl.text(total.toLocaleString('id-ID'));

                    // 🔥 update row utama
                    let tr = btn.closest('tr').prev();
                    let row = tunjanganPegawaiTable.row(tr);

                    let data = row.data();

                    data.total = '<span class="fw-bold text-primary">' +
                        total.toLocaleString('id-ID') +
                        '</span>';

                    row.data(data).draw(false);

                    Swal.fire({
                        icon: 'success',
                        title: 'Updated',
                        toast: true,
                        position: 'top-end',
                        timer: 1200,
                        showConfirmButton: false
                    });

                },

                error: function() {
                    btn.html('<i class="mdi mdi-check"></i>');

                    Swal.fire("Gagal update", "", "error");
                }
            });
        });

        $('#tableTunjanganPegawai').on('input', '.input-nominal', function() {

            let input = $(this);
            let id = input.data('id');
            let value = input.val();
            let old = input.data('old');

            if (value != old) {
                changedData[id] = value; // simpan perubahan
                input.addClass('border-warning'); // tanda berubah
            } else {
                delete changedData[id];
                input.removeClass('border-warning');
            }

            // aktifkan tombol kalau ada perubahan
            $('#bulkSaveBtn').prop('disabled', Object.keys(changedData).length === 0);
        });

        $('#bulkSaveBtn').click(function() {

            if (Object.keys(changedData).length === 0) {
                Swal.fire("Tidak ada perubahan", "", "info");
                return;
            }

            let btn = $(this);

            $.ajax({
                url: '/simrs/masterData/keuangan/tunjangan/bulk-update',
                method: 'PUT',
                data: {
                    data: changedData,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },

                beforeSend: function() {
                    btn.prop('disabled', true);
                    btn.html('Menyimpan...');
                },

                success: function() {

                    // =========================
                    // 🔥 UPDATE TOTAL SEMUA EXPANDABLE
                    // =========================
                    $('.expand-wrapper').each(function() {

                        let wrapper = $(this);
                        let total = 0;

                        // hitung total
                        wrapper.find('.input-nominal').each(function() {
                            total += parseInt($(this).val()) || 0;
                        });

                        // update expandable
                        wrapper.find('.total-pegawai')
                            .text(total.toLocaleString('id-ID'));

                        // =========================
                        // 🔥 AMBIL ROW DATATABLE YANG BENAR
                        // =========================
                        let row = tunjanganPegawaiTable
                            .row(wrapper.closest('tr').prevAll('tr:not(.child)')
                                .first());

                        if (!row.node()) return; // safety

                        let data = row.data();

                        data.total = '<span class="fw-bold text-primary">' +
                            total.toLocaleString('id-ID') +
                            '</span>';

                        row.data(data).invalidate().draw(false);

                    });

                    // =========================
                    // RESET INPUT
                    // =========================
                    $('.input-nominal').each(function() {
                        let val = $(this).val();
                        $(this).attr('data-old', val);
                        $(this).removeClass('border-warning');
                    });

                    changedData = {};
                    btn.html('Simpan Semua');
                    btn.prop('disabled', true);

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil disimpan',
                        toast: true,
                        position: 'top-end',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    if (typeof tunjanganPegawaiTable !==
                        'undefined') {
                        tunjanganPegawaiTable.ajax.reload(null,
                            false);
                    }

                },

                error: function() {
                    btn.html('Simpan Semua');
                    btn.prop('disabled', false);
                    Swal.fire("Gagal menyimpan", "", "error");
                }
            });

        });

        $('#tableTunjanganPegawai').on('click', '.btn-delete', function() {

            let btn = $(this);
            let id = btn.data('id');

            Swal.fire({
                title: 'Hapus tunjangan?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {

                if (!result.isConfirmed) return;

                $.ajax({
                    url: `/simrs/masterData/keuangan/tunjanganPegawai/${id}/delete`,
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },

                    beforeSend: function() {
                        btn.html(
                            '<span class="spinner-border spinner-border-sm"></span>'
                        );
                    },

                    success: function() {

                        let wrapper = btn.parents('.expand-wrapper');
                        let item = btn.closest('.list-group-item');

                        // 🔥 HAPUS ITEM
                        item.remove();

                        // 🔥 HITUNG ULANG TOTAL
                        let total = 0;

                        wrapper.find('.input-nominal').each(function() {
                            total += parseInt($(this).val()) || 0;
                        });

                        wrapper.find('.total-pegawai')
                            .text(total.toLocaleString('id-ID'));

                        // 🔥 UPDATE DATATABLE (PAKAI NIK)
                        let nik = wrapper.data('nik');

                        tunjanganPegawaiTable.rows().every(function() {

                            let data = this.data();

                            if (data.nik == nik) {

                                data.total =
                                    '<span class="fw-bold text-primary">' +
                                    total.toLocaleString('id-ID') +
                                    '</span>';

                                this.data(data).invalidate();
                            }

                        });

                        tunjanganPegawaiTable.draw(false);

                        Swal.fire({
                            icon: 'success',
                            title: 'Dihapus',
                            toast: true,
                            position: 'top-end',
                            timer: 1200,
                            showConfirmButton: false
                        });

                    },

                    error: function() {
                        btn.html('<i class="mdi mdi-trash-can"></i>');
                        Swal.fire("Gagal hapus", "", "error");
                    }
                });

            });

        });

        // buka modal
        $('#btnDistribusi').click(function() {
            $('#modalDistribusi').modal('show');
        });

        $('#modalDistribusi').on('shown.bs.modal', async function() {

            let modal = '#modalDistribusi';

            // =====================
            // RESET
            // =====================
            $('#pegawaiSumber').val(null).trigger('change');
            $('#pegawaiTujuan').html('');
            $('#tunjanganSelect').html('');

            // =====================
            // LOAD PEGAWAI
            // =====================
            await loadPegawai('#pegawaiSumber');
            await loadPegawai('#pegawaiTujuan');

            // =====================
            // INIT SELECT2
            // =====================
            initSelect2('#pegawaiSumber', modal, 'Pilih Sumber');
            initSelect2('#pegawaiTujuan', modal, 'Pilih Tujuan');
            initSelect2('#tunjanganSelect', modal, 'Pilih Tunjangan');

        });

        $('#pegawaiSumber').on('change', function() {

            let nik = $(this).val();

            if (!nik) return;

            $.get(`/simrs/masterData/keuangan/tunjangan/by-pegawai/${nik}`, function(res) {
                console.log(res);

                let select = $('#tunjanganSelect');

                select.html('');

                res.forEach(t => {
                    select.append(`
                        <option value="${t.tunjangan_id}" selected>
                            ${t.nama}
                        </option>
                    `);
                });

                initSelect2('#tunjanganSelect', '#modalDistribusi', 'Pilih Tunjangan');

            });
        });

        $('#btnPreviewDistribusi').click(function() {

            let sumber = $('#pegawaiSumber').val();
            let tujuan = $('#pegawaiTujuan').val();
            let tunjangan = $('#tunjanganSelect').val();

            if (!sumber || !tujuan || !tunjangan) {
                Swal.fire("Lengkapi semua field", "", "warning");
                return;
            }

            $.ajax({
                url: '/simrs/masterData/keuangan/tunjangan/preview-distribusi',
                method: 'POST',
                data: {
                    sumber: sumber,
                    tujuan: tujuan,
                    tunjangan_id: tunjangan,
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

                    // ======================
                    // GROUPING PER PEGAWAI 🔥
                    // ======================
                    let grouped = {};

                    res.forEach(item => {
                        if (!grouped[item.nama_pegawai]) {
                            grouped[item.nama_pegawai] = [];
                        }
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

                            let badgeClass = item.status === 'new' ?
                                'bg-success' : 'bg-secondary';
                            let badge = item.status === 'new' ? 'Baru' :
                                'Ada';

                            html += `
                        <div class="d-flex justify-content-between small mb-1">
                            <span>${item.nama_tunjangan}</span>
                            <span class="badge ${badgeClass}">${badge}</span>
                        </div>
                    `;
                        });

                        html += `</div></div>`;
                    });

                    // ======================
                    // SUMMARY
                    // ======================
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

                    // ======================
                    // FINAL RENDER (SCROLL 🔥)
                    // ======================
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

            // RESET SELECT VALUE
            $('#pegawaiSumber').val(null).trigger('change');
            $('#pegawaiTujuan').val(null).trigger('change');
            $('#tunjanganSelect').val(null).trigger('change');

            // KOSONGKAN OPTION (BIAR FRESH LOAD)
            $('#pegawaiTujuan').html('');
            $('#tunjanganSelect').html('');

            // HIDE PREVIEW
            $('#previewDistribusi').addClass('d-none');
            $('#previewContent').html('');

            // RESET BUTTON
            $('#prosesDistribusi')
                .prop('disabled', false)
                .html('<i class="mdi mdi-check me-1"></i> Distribusikan');

        }

        $('#modalDistribusi').on('hidden.bs.modal', function() {
            resetModalDistribusi();
        });

        // proses distribusi
        $('#prosesDistribusi').click(function(e) {

            e.preventDefault();

            let sumber = $('#pegawaiSumber').val();
            let tujuan = $('#pegawaiTujuan').val();
            let tunjangan = $('#tunjanganSelect').val();

            console.log({
                sumber,
                tujuan,
                tunjangan
            });

            // =========================
            // VALIDASI
            // =========================
            if (!sumber) {
                Swal.fire("Pilih pegawai sumber", "", "warning");
                return;
            }

            if (!tujuan || tujuan.length === 0) {
                Swal.fire("Pilih pegawai tujuan", "", "warning");
                return;
            }

            if (!tunjangan || tunjangan.length === 0) {
                Swal.fire("Pilih tunjangan", "", "warning");
                return;
            }

            // =========================
            // CONFIRM
            // =========================
            Swal.fire({
                title: 'Distribusikan tunjangan?',
                text: 'Tunjangan akan disalin ke pegawai tujuan',
                icon: 'question',
                showCancelButton: true
            }).then(result => {

                if (!result.isConfirmed) return;

                $.ajax({
                    url: "{{ route("masterData.keuangan.tunjangan.distribusi") }}",
                    method: 'POST',
                    data: {
                        sumber: sumber,
                        tujuan: tujuan,
                        tunjangan_id: tunjangan,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },

                    beforeSend: function() {
                        $('#prosesDistribusi')
                            .prop('disabled', true)
                            .html(
                                '<span class="spinner-border spinner-border-sm"></span> Memproses...'
                            );
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

                        tunjanganPegawaiTable.ajax.reload();
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
                            .html(
                                '<i class="mdi mdi-check me-1"></i> Distribusikan');
                    }
                });

            });

        });

    });
</script>
