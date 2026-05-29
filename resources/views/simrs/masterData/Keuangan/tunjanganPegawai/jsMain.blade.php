<script>
    $(function() {

        const modal = $('#tunjanganPegawaiModal');
        const container = $("#tunjanganContainer");

        let tunjanganList = [];
        let jabatanList = [];
        let changedData = {};
        let targetNik = null;
        let gapok = 0;
        let masaKerja = 0;
        let mulaiKontrak = null;
        let profesiList = [];
        let masaKerjaDetail = {
            tahun: 0,
            bulan: 0
        };
        let payrollCache = {};

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
                options += `
                <option value="${t.id}" 
                    data-tipe="${t.tipe}" 
                    data-nilai="${t.nilai}">
                    ${t.kode} - ${t.nama}
                </option>`;
            });

            return `
            <div class="card border tunjangan-item">
                <div class="card-body py-3">
                    <div class="row g-3 align-items-end">

                        <!-- 🔥 HIDDEN -->
                        <input type="hidden" name="referensi_id[]" class="referensi-id">
                        <input type="hidden" name="qty[]" class="qty-input">
                        <input type="hidden" name="nominal[]" class="nominal-hidden">

                        <div class="col-md-4">
                            <label class="form-label">Tunjangan</label>
                            <select name="tunjangan_id[]" class="form-select select2-tunjangan">
                                ${options}
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


        container.on('change', '.select2-tunjangan', function() {

            let selected = $(this).find(':selected');

            let tipe = selected.data('tipe');
            let nilai = parseFloat(selected.data('nilai')) || 0;

            let row = $(this).closest('.tunjangan-item');
            let extra = row.find('.extra-input');
            let preview = row.find('.nominal-preview');

            extra.html('');
            preview.val('');
            row.find('.nominal-hidden').val('');

            switch (tipe) {

                case 'jabatan':

                    let options = `<option value="">-- Pilih Jabatan --</option>`;

                    jabatanList.forEach(j => {
                        options += `
                        <option value="${j.id}" data-nominal="${j.tunjangan}">
                            ${j.kode} - ${j.nama}
                        </option>`;
                    });

                    extra.html(`
                        <select class="form-select select2-jabatan">
                            ${options}
                        </select>
                    `);

                    initSelect2(row.find('.select2-jabatan'), modal, 'Pilih Jabatan');

                    break;

                case 'profesi':

                    let optionsProfesi = `<option value="">-- Pilih Profesi --</option>`;

                    profesiList.forEach(p => {
                        optionsProfesi += `
                        <option value="${p.id}" data-nominal="${p.tunjangan}">
                            ${p.kode} - ${p.nama}
                        </option>`;
                    });

                    extra.html(`
                        <select class="form-select select2-profesi">
                            ${optionsProfesi}
                        </select>
                    `);

                    initSelect2(row.find('.select2-profesi'), modal, 'Pilih Profesi');

                    break;

                case 'anak':
                    extra.html(`
                        <input type="number" class="form-control jumlah-anak" 
                        placeholder="Jumlah anak">
                    `);

                    // reset
                    row.find('.referensi-id').val('');
                    row.find('.qty-input').val('');

                    break;

                case 'pasangan':

                    extra.html(`<input type="text" class="form-control" value="1 pasangan" readonly>`);

                    let totalPasangan = gapok * nilai / 100;

                    preview.val(formatRupiah(totalPasangan));
                    row.find('.nominal-hidden').val(totalPasangan);

                    row.find('.referensi-id').val('');
                    row.find('.qty-input').val('');

                    break;

                case 'masa_kerja':

                    let mk = masaKerjaDetail || {
                        tahun: 0,
                        bulan: 0
                    };

                    if (mk.tahun < 1) {

                        extra.html(`
                            <div class="text-danger small">
                                ❌ Masa kerja ${mk.tahun} Tahun<br>
                                Tidak mendapatkan tunjangan masa kerja
                            </div>
                        `);

                        preview.val(formatRupiah(0));
                        row.find('.nominal-hidden').val(0);

                    } else {

                        let totalMK = mk.tahun * nilai;

                        extra.html(`
                            <div class="small text-muted">
                                Masa Kerja: <b>${mk.tahun} Tahun</b><br>
                                Tarif: ${formatRupiah(nilai)} / tahun
                            </div>
                        `);

                        preview.val(formatRupiah(totalMK));
                        row.find('.nominal-hidden').val(totalMK);
                    }

                    row.find('.referensi-id').val('');
                    row.find('.qty-input').val('');

                    break;

                    preview.val(formatRupiah(totalMK));

                    // 🔥 simpan ke hidden kalau ada
                    row.find('.nominal-hidden').val(totalMK);

                    break;

                case 'manual':
                case 'custom':

                    extra.html(`
                            <input type="number" class="form-control nominal-manual" 
                            placeholder="Nominal">
                        `);

                    row.find('.referensi-id').val('');
                    row.find('.qty-input').val('');

                    break;
            }

            refreshTunjanganOptions();
        });

        container.on('input', '.jumlah-anak', function() {

            let row = $(this).closest('.tunjangan-item');
            let jumlah = parseInt($(this).val()) || 0;

            let selected = row.find('.select2-tunjangan option:selected');
            let persen = parseFloat(selected.data('nilai')) || 0;

            if (jumlah > 3) {
                jumlah = 3;
                $(this).val(3);
            }

            let total = jumlah * (gapok * persen / 100);

            row.find('.nominal-preview').val(formatRupiah(total));
            row.find('.nominal-hidden').val(total);

            // 🔥 SET QTY
            row.find('.qty-input').val(jumlah);

        });

        container.on('input', '.nominal-manual', function() {

            let val = $(this).val() || 0;

            let row = $(this).closest('.tunjangan-item');

            row.find('.nominal-preview').val(formatRupiah(val));
            row.find('.nominal-hidden').val(val);
        });

        container.on('change select2:select', '.select2-jabatan', function() {

            let selected = $(this).find(':selected');
            let nominal = parseFloat(selected.data('nominal')) || 0;

            let row = $(this).closest('.tunjangan-item');

            row.find('.nominal-preview').val(formatRupiah(nominal));
            row.find('.nominal-hidden').val(nominal);

            // 🔥 SET REFERENSI
            row.find('.referensi-id').val(selected.val());
            row.find('.qty-input').val('');

        });

        container.on('change select2:select', '.select2-profesi', function() {

            let selected = $(this).find(':selected');
            let nominal = parseFloat(selected.data('nominal')) || 0;

            let row = $(this).closest('.tunjangan-item');

            row.find('.nominal-preview').val(formatRupiah(nominal));
            row.find('.nominal-hidden').val(nominal);

            // 🔥 SET REFERENSI
            row.find('.referensi-id').val(selected.val());
            row.find('.qty-input').val('');

        });

        function formatRupiah(angka) {
            return 'Rp ' + new Intl.NumberFormat('id-ID', {
                maximumFractionDigits: 0
            }).format(angka || 0);
        }

        // dummy (nanti dari backend)
        function loadJabatan() {
            return $.ajax({
                url: "{{ route("masterData.keuangan.tunjanganPegawai.getJabatan") }}",
                method: 'GET',
                success: function(res) {
                    jabatanList = res; // 🔥 DATA MASUK KE SINI
                }
            });
        }

        function loadProfesi() {
            return $.ajax({
                url: "{{ route("masterData.keuangan.tunjanganPegawai.getProfesi") }}",
                method: 'GET',
                success: function(res) {
                    profesiList = res; // 🔥 DATA MASUK KE SINI
                }
            });
        }

        function hitungMasaKerjaDetail(tanggalMasuk) {

            if (!tanggalMasuk) {
                return {
                    tahun: 0
                };
            }

            let start = new Date(tanggalMasuk);
            let now = new Date();

            // Hitung hanya selisih tahun
            let tahun = now.getFullYear() - start.getFullYear();

            return {
                tahun: tahun
            };
        }

        function loadPayrollForNik(nik) {

            if (payrollCache[nik]) {
                return $.Deferred().resolve(payrollCache[nik]).promise();
            }

            return $.get(`/simrs/masterData/keuangan/tunjanganPegawai/getGapokById/${nik}`)
                .then(function(res) {

                    let mk = hitungMasaKerjaDetail(res.mulai_kontrak);

                    payrollCache[nik] = {
                        gapok: parseFloat(res.gaji_pokok) || 0,
                        mulaiKontrak: res.mulai_kontrak,
                        masaKerja: mk.tahun,
                        masaKerjaDetail: mk
                    };

                    return payrollCache[nik];
                });
        }

        function setWrapperPayroll(wrapper, payroll) {
            wrapper.data('gapok', payroll.gapok);
            wrapper.data('masaKerjaDetail', payroll.masaKerjaDetail);
        }

        function getWrapperPayroll(wrapper) {
            return {
                gapok: parseFloat(wrapper.data('gapok')) || gapok || 0,
                masaKerjaDetail: wrapper.data('masaKerjaDetail') || masaKerjaDetail || {
                    tahun: 0,
                    bulan: 0
                }
            };
        }

        function ensureMasterData() {

            let requests = [];

            if (!tunjanganList.length) requests.push(loadTunjangan());
            if (!jabatanList.length) requests.push(loadJabatan());
            if (!profesiList.length) requests.push(loadProfesi());

            if (!requests.length) {
                return $.Deferred().resolve().promise();
            }

            return $.when.apply($, requests);
        }

        async function initMasterData() {
            await loadTunjangan();
            await loadJabatan();
            await loadProfesi();

        }

        $(document).ready(async function() {
            await initMasterData();
        })

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
            await loadJabatan();
            await loadProfesi();

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
            let gaji_pokok = selected.data('gapok') || '-';

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
            $("#infoGapok").text('-'); // default dulu
            $("#infoStatus").html(statusBadge);

            // 🔥 AMBIL GAPOK
            $.get(`/simrs/masterData/keuangan/tunjanganPegawai/getGapokById/${nik}`, function(res) {

                gapok = parseFloat(res.gaji_pokok) || 0;

                let mk = hitungMasaKerjaDetail(res.mulai_kontrak);

                $("#infoGapok").text(formatRupiah(gapok));

                masaKerja = mk.tahun;
                masaKerjaDetail = mk;
                mulaiKontrak = res.mulai_kontrak;

                // 🔥 FORM LAMA
                $('.select2-tunjangan').each(function() {
                    $(this).trigger('change');
                });

                // 🔥 INLINE (INI YANG KURANG)
                $('.select-tunjangan').each(function() {
                    $(this).trigger('change');
                });

            });

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
            let nik = row.nik || row.id;

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

                        <div class="d-flex align-items-center gap-3">
                            <button type="button" class="btn btn-outline-primary btn-sm btn-add-tunjangan"
                                data-nik="${nik}">
                                <i class="mdi mdi-plus"></i> Tambah
                            </button>

                            <div class="text-end">
                                <small class="text-muted d-block">Total</small>
                                <span class="fw-bold text-primary fs-4 total-pegawai">
                                    ${row.total}
                                </span>
                            </div>
                        </div>

                    </div>

                    <div class="tunjangan-detail-body">
                        ${row.tunjangan}
                    </div>

                </div>
            `;
        }

        $('#tableTunjanganPegawai tbody').on('click', 'td.dt-control', function() {

            let tr = $(this).closest('tr');
            let row = tunjanganPegawaiTable.row(tr);

            // 🔥 safety
            let data = row.data();
            if (!data) return;

            if (row.child.isShown()) {

                // ================= TUTUP =================
                row.child.hide();
                tr.removeClass('shown');

                $(this).html('<i class="mdi mdi-chevron-right"></i>');

            } else {

                // ================= BUKA =================
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

                                $('#tunjanganPegawaiModalExcell').modal('hide');

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
                                text: xhr.responseJSON?.message ||
                                    'Server error'
                            });

                        }
                    },

                });

            });
        });

        // Inline editing untuk nominal tunjangan

        function updateTotal(wrapper, updateMain = true) {

            let total = 0;

            wrapper.find('.input-nominal').each(function() {
                total += parseInt($(this).val()) || 0;
            });

            // 🔥 update expand
            wrapper.find('.total-pegawai')
                .text(total.toLocaleString('id-ID'));

            if (!updateMain) return;

            // 🔥 update row utama datatable
            let tr = wrapper.closest('tr').prevAll('tr:not(.child)').first();
            let row = tunjanganPegawaiTable.row(tr);

            let data = row.data();
            if (!data) return;

            data.total = '<span class="fw-bold text-primary">' +
                total.toLocaleString('id-ID') +
                '</span>';

            if (wrapper.find('.new-row').length > 0) {
                row.data(data).invalidate();
                return;
            }

            row.data(data).draw(false);
        }

        function markInlineChanged(rowItem) {

            let changed = false;

            rowItem.find('.input-ref, .input-qty, .input-nominal').each(function() {
                let input = $(this);
                let id = input.data('id');

                if (!id) return;

                let oldValue = input.data('old');
                let currentValue = input.val();

                if (String(currentValue ?? '') !== String(oldValue ?? '')) {
                    changed = true;
                    input.addClass('border-warning');
                } else {
                    input.removeClass('border-warning');
                }
            });

            rowItem.find('.btn-save').prop('disabled', !changed);
        }

        function getUsedTunjanganIds(wrapper, exceptSelect = null) {

            let ids = [];

            wrapper.find('.list-group-item[data-tunjangan-id]').not('.new-row').each(function() {
                let id = $(this).data('tunjangan-id');
                if (id) ids.push(String(id));
            });

            wrapper.find('.new-row .select-tunjangan').each(function() {
                if (exceptSelect && this === exceptSelect[0]) return;

                let id = $(this).val();
                if (id) ids.push(String(id));
            });

            return ids;
        }

        function getAvailableTunjangan(wrapper) {
            let usedIds = getUsedTunjanganIds(wrapper);

            return tunjanganList.filter(t => !usedIds.includes(String(t.id)));
        }

        function loadTunjanganDropdown(el) {

            let wrapper = el.closest('.expand-wrapper');
            let usedIds = getUsedTunjanganIds(wrapper, el);
            let currentValue = el.val();

            el.html(`<option value="">-- Pilih Tunjangan --</option>`);

            tunjanganList.forEach(t => {
                if (usedIds.includes(String(t.id)) && String(t.id) !== String(currentValue)) return;

                el.append(`
                    <option value="${t.id}" 
                        data-tipe="${t.tipe}" 
                        data-nilai="${t.nilai}">
                        ${t.kode} - ${t.nama}
                    </option>
                `);
            });

            if (el.find('option').length === 1) {
                el.html(`<option value="">Semua tunjangan sudah ada</option>`);
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
                            <label class="form-label small text-muted">Tunjangan</label>
                            <select class="form-select form-select-sm select-tunjangan"></select>
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

        $('#tableTunjanganPegawai').on('change', '.select-tunjangan', function() {

            let selected = $(this).find(':selected');

            let tipe = selected.data('tipe');
            let nilai = parseFloat(selected.data('nilai')) || 0; // 🔥 FIX

            let row = $(this).closest('.new-row');
            let extra = row.find('.extra-input');
            let preview = row.find('.input-nominal'); // 🔥 FIX

            extra.html('');

            switch (tipe) {

                case 'jabatan':

                    let optJabatan = `<option value="">-- Pilih Jabatan --</option>`;

                    jabatanList.forEach(j => {
                        optJabatan += `
                    <option value="${j.id}" data-nominal="${j.tunjangan}">
                        ${j.nama}
                    </option>`;
                    });

                    extra.html(
                        `<select class="form-select form-select-sm input-ref">${optJabatan}</select>`
                    );
                    break;

                case 'profesi':

                    let optProfesi = `<option value="">-- Pilih Profesi --</option>`;

                    profesiList.forEach(p => {
                        optProfesi += `
                    <option value="${p.id}" data-nominal="${p.tunjangan}">
                        ${p.nama}
                    </option>`;
                    });

                    extra.html(
                        `<select class="form-select form-select-sm input-ref">${optProfesi}</select>`
                    );
                    break;

                case 'masa_kerja':

                    let mk = masaKerjaDetail || {
                        tahun: 0,
                        bulan: 0
                    };

                    if (mk.tahun < 1) {

                        extra.html(`
                    <div class="text-danger small">
                        ❌ Masa kerja ${mk.tahun} Tahun<br>
                        Tidak mendapatkan tunjangan masa kerja
                    </div>
                `);

                        preview.val(0);

                    } else {

                        let totalMK = mk.tahun * nilai;

                        extra.html(`
                    <div class="small text-muted">
                        Masa Kerja: <b>${mk.tahun} Tahun</b><br>
                        Tarif: ${formatRupiah(nilai)} / tahun
                    </div>
                `);

                        preview.val(totalMK);
                    }

                    break;

                case 'anak':
                    extra.html(`
                        <input type="number" class="form-control input-qty" 
                        placeholder="Jumlah anak">
                    `);

                    row.find('.referensi-id').val('');
                    row.find('.qty-input').val('');
                    break;

                case 'pasangan':

                    let totalPasangan = gapok * nilai / 100;
                    preview.val(totalPasangan);

                    break;

                case 'manual':
                    extra.html(`
                <input type="number" class="form-control form-control-sm input-nominal-manual" placeholder="Nominal">
            `);
                    break;
            }
        });

        $('#tableTunjanganPegawai').on('click', '.btn-add-tunjangan', function() {

            let btn = $(this);
            let nik = btn.data('nik');

            let wrapper = btn.closest('.expand-wrapper');
            let list = wrapper.find('.list-group').first();

            // ❌ cegah double row
            if (list.find('.new-row').length > 0) return;

            // 🔥 FUNCTION RENDER
            function renderRow() {

                let row = $(createNewRow(nik));

                list.prepend(row);

                loadTunjanganDropdown(row.find('.select-tunjangan'));
            }

            // 🔥 kalau belum ada data → ambil dulu
            if (!mulaiKontrak && nik) {

                $.get(`/simrs/masterData/keuangan/tunjanganPegawai/getGapokById/${nik}`, function(res) {

                    gapok = parseFloat(res.gaji_pokok) || 0;

                    mulaiKontrak = res.mulai_kontrak;

                    let mk = hitungMasaKerjaDetail(mulaiKontrak);

                    masaKerja = mk.tahun;
                    masaKerjaDetail = mk;

                    console.log('AUTO LOAD MK:', masaKerjaDetail);

                    renderRow(); // 🔥 render setelah data siap
                });

            } else {
                // 🔥 langsung render
                renderRow();
            }

        });

        $('#tableTunjanganPegawai').off('change', '.select-tunjangan');
        $('#tableTunjanganPegawai').on('change', '.select-tunjangan', function() {

            let selected = $(this).find(':selected');
            let tipe = selected.data('tipe');
            let nilai = parseFloat(selected.data('nilai')) || 0;

            let row = $(this).closest('.new-row');
            let wrapper = row.closest('.expand-wrapper');
            let payroll = getWrapperPayroll(wrapper);
            let extra = row.find('.extra-input');
            let preview = row.find('.input-nominal');

            extra.html('');
            preview.val(0).prop('readonly', true);

            switch (tipe) {

                case 'jabatan':
                    let optJabatan = `<option value="">-- Pilih Jabatan --</option>`;

                    jabatanList.forEach(j => {
                        optJabatan += `
                            <option value="${j.id}" data-nominal="${j.tunjangan}">
                                ${j.nama}
                            </option>`;
                    });

                    extra.html(
                        `<select class="form-select form-select-sm input-ref">${optJabatan}</select>`
                    );
                    break;

                case 'profesi':
                    let optProfesi = `<option value="">-- Pilih Profesi --</option>`;

                    profesiList.forEach(p => {
                        optProfesi += `
                            <option value="${p.id}" data-nominal="${p.tunjangan}">
                                ${p.nama}
                            </option>`;
                    });

                    extra.html(
                        `<select class="form-select form-select-sm input-ref">${optProfesi}</select>`
                    );
                    break;

                case 'masa_kerja':
                    let mk = payroll.masaKerjaDetail || {
                        tahun: 0,
                        bulan: 0
                    };

                    if (mk.tahun < 1) {
                        extra.html(`
                            <div class="text-danger small">
                                Masa kerja ${mk.tahun} Tahun<br>
                                Tidak mendapatkan tunjangan masa kerja
                            </div>
                        `);

                        preview.val(0);
                    } else {
                        let totalMK = mk.tahun * nilai;

                        extra.html(`
                            <div class="small text-muted">
                                Masa Kerja: <b>${mk.tahun} Tahun</b><br>
                                Tarif: ${formatRupiah(nilai)} / tahun
                            </div>
                        `);

                        preview.val(totalMK);
                    }
                    break;

                case 'anak':
                    extra.html(`
                        <input type="number" class="form-control form-control-sm input-qty"
                            placeholder="Jumlah anak" min="0" max="3">
                    `);
                    break;

                case 'pasangan':
                    let totalPasangan = payroll.gapok * nilai / 100;
                    preview.val(totalPasangan);
                    break;

                case 'manual':
                case 'custom':
                    extra.html(`
                        <div class="small text-muted">
                            Isi nominal pada kolom nominal
                        </div>
                    `);
                    preview.val('').prop('readonly', false);
                    break;
            }

            updateTotal(wrapper);
        });

        $('#tableTunjanganPegawai').off('click', '.btn-add-tunjangan');
        $('#tableTunjanganPegawai').on('click', '.btn-add-tunjangan', function() {

            let btn = $(this);
            let nik = btn.data('nik');
            let wrapper = btn.closest('.expand-wrapper');
            let list = wrapper.find('.tunjangan-detail-list').first();

            if (list.find('.new-row').length > 0) return;

            function renderRow() {

                if (getAvailableTunjangan(wrapper).length === 0) {
                    Swal.fire("Semua tunjangan sudah ada", "", "info");
                    return;
                }

                let row = $(createNewRow(nik));

                list.prepend(row);
                loadTunjanganDropdown(row.find('.select-tunjangan'));
            }

            btn.prop('disabled', true);

            $.when(ensureMasterData(), loadPayrollForNik(nik))
                .done(function(masterRes, payroll) {
                    setWrapperPayroll(wrapper, payroll);
                    renderRow();
                })
                .fail(function() {
                    Swal.fire("Gagal memuat data pegawai", "", "error");
                })
                .always(function() {
                    btn.prop('disabled', false);
                });

        });

        $('#tableTunjanganPegawai').on('click', '.btn-cancel-new', function() {
            let row = $(this).closest('.new-row');
            let wrapper = row.closest('.expand-wrapper');

            row.remove();
            updateTotal(wrapper);
        });

        $('#tableTunjanganPegawai').on('click', '.btn-save-new', function() {

            let btn = $(this);
            let row = btn.closest('.new-row');
            let wrapper = row.closest('.expand-wrapper');
            let selected = row.find('.select-tunjangan option:selected');

            let nik = row.data('nik') || wrapper.data('nik');
            let tunjanganId = row.find('.select-tunjangan').val();
            let tipe = selected.data('tipe');
            let referensiId = row.find('.input-ref').val() || '';
            let qty = row.find('.input-qty').val() || '';
            let nominal = parseInt(row.find('.input-nominal').val()) || 0;

            if (!nik) {
                Swal.fire("Pegawai tidak ditemukan", "", "warning");
                return;
            }

            if (!tunjanganId) {
                Swal.fire("Pilih tunjangan terlebih dahulu", "", "warning");
                return;
            }

            if ((tipe === 'jabatan' || tipe === 'profesi') && !referensiId) {
                Swal.fire("Pilih referensi terlebih dahulu", "", "warning");
                return;
            }

            if (tipe === 'anak') {
                qty = Math.min(parseInt(qty) || 0, 3);

                if (qty < 1) {
                    Swal.fire("Jumlah anak wajib diisi", "", "warning");
                    return;
                }
            }

            if ((tipe === 'manual' || tipe === 'custom') && nominal < 1) {
                Swal.fire("Nominal wajib diisi", "", "warning");
                return;
            }

            $.ajax({
                url: "{{ route("masterData.keuangan.tunjanganPegawai.store") }}",
                method: 'POST',
                data: {
                    nik: nik,
                    tunjangan_id: [tunjanganId],
                    referensi_id: [referensiId],
                    qty: [qty],
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
                        title: res.message || 'Tunjangan berhasil ditambahkan',
                        toast: true,
                        position: 'top-end',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    tunjanganPegawaiTable.ajax.reload(null, false);
                },

                error: function(xhr) {
                    btn.prop('disabled', false);
                    btn.html('<i class="mdi mdi-check"></i>');

                    Swal.fire(
                        "Gagal menyimpan",
                        xhr.responseJSON?.message || 'Terjadi kesalahan',
                        "error"
                    );
                }
            });

        });

        $('#tableTunjanganPegawai').on('click', '.btn-save', function() {

            let btn = $(this);
            let id = btn.data('id');

            let rowItem = btn.closest('.list-group-item');
            let wrapper = btn.closest('.expand-wrapper');
            let nominalInput = rowItem.find('.input-nominal');
            let data = {
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            let refInput = rowItem.find('.input-ref[data-id]');
            if (refInput.length) {
                data.referensi_id = refInput.val();

                if (!data.referensi_id) {
                    Swal.fire("Pilih referensi terlebih dahulu", "", "warning");
                    return;
                }
            }

            let qtyInput = rowItem.find('.input-qty[data-id]');
            if (qtyInput.length) {
                let qty = Math.min(parseInt(qtyInput.val()) || 0, 3);

                qtyInput.val(qty);
                data.qty = qty;
            }

            if (!refInput.length && !qtyInput.length) {
                data.nominal = parseInt(nominalInput.val()) || 0;
            }

            if (parseInt(nominalInput.val()) < 0) {
                Swal.fire("Nominal tidak valid", "", "warning");
                return;
            }

            $.ajax({
                url: `/simrs/masterData/keuangan/tunjangan/update-inline/${id}`,
                method: 'PUT',
                data: data,

                beforeSend: function() {
                    btn.html('<span class="spinner-border spinner-border-sm"></span>');
                    btn.prop('disabled', true);
                },

                success: function(res) {

                    btn.html('<i class="mdi mdi-content-save"></i>');
                    btn.prop('disabled', false);

                    // 🔥 update nominal dari backend (kalau ada recalculation)
                    if (res.nominal !== undefined) {
                        nominalInput.val(res.nominal);
                    }

                    // 🔥 update total pakai function
                    updateTotal(wrapper);

                    // reset state
                    rowItem.find('.input-ref, .input-qty, .input-nominal').each(function() {
                        let input = $(this);
                        input.data('old', input.val());
                        input.removeClass('border-warning');
                    });

                    btn.prop('disabled', true);

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
                    btn.html('<i class="mdi mdi-content-save"></i>');
                    btn.prop('disabled', false);

                    Swal.fire("Gagal update", "", "error");
                }
            });

        });

        $('#tableTunjanganPegawai').on('input', '.input-nominal', function() {

            let input = $(this);
            let id = input.data('id');

            if (!id) {
                updateTotal(input.closest('.expand-wrapper'));
                return;
            }

            markInlineChanged(input.closest('.list-group-item'));
            updateTotal(input.closest('.expand-wrapper'), false);
        });

        $('#tableTunjanganPegawai').on('change', '.input-ref', function() {

            let select = $(this);
            let id = select.data('id'); // 🔥 kalau null = new row
            let wrapper = select.closest('.expand-wrapper');
            let rowItem = select.closest('.list-group-item');

            // =========================
            // 🔥 CASE 1: NEW ROW
            // =========================
            if (!id) {

                let nominal = select.find(':selected').data('nominal') || 0;

                rowItem.find('.input-nominal').val(nominal);
                updateTotal(wrapper);

                return;
            }

            // =========================
            // 🔥 CASE 2: EXISTING
            // =========================
            markInlineChanged(rowItem);

        });

        $('#tableTunjanganPegawai').on('input', '.input-qty', function() {

            let input = $(this);

            let row = input.closest('.list-group-item, .new-row');
            let wrapper = input.closest('.expand-wrapper');

            let nominalInput = row.find('.input-nominal');

            let qty = parseInt(input.val()) || 0;
            qty = Math.min(qty, 3);
            input.val(qty);

            let id = input.data('id');

            // 🔥 DETEKSI INLINE PALING AMAN
            let isInline = !id; // ⬅️ INI KUNCI

            // =========================
            // 🔥 INLINE (TAMBAH BARU)
            // =========================
            if (isInline) {

                let selected = row.find('.select-tunjangan option:selected');
                let persen = parseFloat(selected.data('nilai')) || 0;
                let payroll = getWrapperPayroll(wrapper);

                let total = qty * (payroll.gapok * persen / 100);

                nominalInput.val(total);

                updateTotal(wrapper);

                return;
            }

            // =========================
            // 🔥 EXISTING (DATA LAMA)
            // =========================
            markInlineChanged(row);

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
