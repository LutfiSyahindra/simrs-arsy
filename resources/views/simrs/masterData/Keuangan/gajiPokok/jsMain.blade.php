<script>
    $(document).ready(function() {

        // --- Setup CSRF untuk semua AJAX request
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });

        $('#nikSelect').select2({
            dropdownParent: $('#gapokModal'),
            placeholder: 'Cari NIK / Nama Pegawai',
            width: '100%',
            allowClear: true
        });

        let gapokTable = $('#tableGapok').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route("masterData.keuangan.gapok.getGapokTable") }}", // pastikan route ini ada
                type: "GET"
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'nama',
                    name: 'nama'
                },
                {
                    data: 'jabatan',
                    name: 'jabatan'
                },
                {
                    data: 'status',
                    name: 'status',
                    render: function(data, type, row) {

                        if (data === 'Tetap') {
                            return `<span class="badge bg-success">Tetap</span>`;
                        }

                        if (data === 'Kontrak') {
                            return `<span class="badge bg-warning text-dark">Kontrak</span>`;
                        }
                        if (data === 'Casual') {
                            return `<span class="badge bg-secondary">Casual</span>`;
                        }

                        return `<span class="badge bg-secondary">-</span>`;
                    }
                },
                {
                    data: 'gaji_pokok',
                    name: 'gaji_pokok'
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $('.dataTables_filter').hide();

        $('#searchGapok').on('keyup', function() {
            gapokTable.search(this.value).draw();
        });

        // LOAD PEGAWAI
        function loadPegawai() {
            $.get('{{ route("masterData.keuangan.gapok.getPegawai") }}', function(res) {

                let select = $('#nikSelect');
                select.empty().append('<option value="">-- Pilih NIK --</option>');

                res.forEach(p => {
                    select.append(`
                <option value="${p.nik}">
                    ${p.nik} - ${p.nama}
                </option>
            `);
                });
            });
        }

        function hitungMasaKerja(tglMulai) {

            let mulai = new Date(tglMulai);
            let sekarang = new Date();

            let tahun = sekarang.getFullYear() - mulai.getFullYear();
            let bulan = sekarang.getMonth() - mulai.getMonth();

            if (bulan < 0) {
                tahun--;
                bulan += 12;
            }

            return `${tahun} Tahun ${bulan} Bulan`;
        }

        $('#nikSelect').on('change', function() {

            let nik = $(this).val();
            $('#nik').val(nik);
            if (!nik) return;

            $.get(`/simrs/masterData/keuangan/gapok/getPegawaiByNik/${nik}`, function(res) {
                console.log(res);

                // isi data
                $('input[name="nama"]').val(res.data.nama);
                $('input[name="jbtn"]').val(res.data.jbtn);

                $('select[name="stts_kerja"]').val(res.data.stts_kerja);
                // kirim ke backend lewat hidden
                $('#stts_kerja').val(res.data.stts_kerja);

                $('input[name="mulai_kontrak"]').val(res.data.mulai_kontrak);

                // 🔥 hitung masa kerja otomatis
                let masaKerja = hitungMasaKerja(res.data.mulai_kontrak);
                $('input[name="masa_kerja"]').val(masaKerja);

            });
        });

        $('#gapokForm').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            let formData = form.serialize();
            let gapokId = $('#gapokId').val();

            let url = gapokId ?
                `/simrs/masterData/keuangan/gapok/${gapokId}/update` :
                "{{ route("masterData.keuangan.gapok.store") }}";

            let method = gapokId ? 'PUT' : 'POST';

            // RESET VALIDASI
            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').text('');

            Swal.fire({
                title: gapokId ?
                    'Perbarui data gaji pokok?' : 'Simpan data gaji pokok?',
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

                        if (response.status === true) {

                            $('#gapokModal').modal('hide');

                            Swal.fire({
                                icon: 'success',
                                title: response.message,
                                toast: true,
                                position: 'top-end',
                                timer: 2500,
                                showConfirmButton: false
                            });

                            form[0].reset();
                            $('#gapokId').val('');

                            if (typeof gapokTable !== 'undefined') {
                                gapokTable.ajax.reload(null, false);
                            }
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

        window.editGapok = function(id) {

            const modal = $('#gapokModal');
            modal.modal('show');

            let form = $('#gapokForm');
            form[0].reset();

            $('.invalid-feedback').text('');
            $('.form-control, .form-select').removeClass('is-invalid');

            $('#gapokId').val(id);

            Swal.fire({
                title: 'Mengambil data...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: "{{ route("masterData.keuangan.gapok.edit", ":id") }}".replace(':id', id),
                method: 'GET',

                success: function(res) {

                    Swal.close();

                    let data = res.data ?? res;

                    // 🔥 TITLE
                    $('#gapokModalLabel').html(`
                <i class="mdi mdi-pencil text-warning me-1"></i>
                Edit Gaji Pokok
            `);

                    $('#submitForm').html(`
                <i class="mdi mdi-content-save-outline me-1"></i>
                Update
            `);

                    // ===============================
                    // 🔥 FIX NIK (INI YANG PENTING)
                    // ===============================
                    $('#nikSelect').empty(); // clear dulu

                    let option = new Option(
                        data.nik + ' - ' + data.nama,
                        data.nik,
                        true,
                        true
                    );

                    $('#nikSelect').append(option).trigger('change');

                    $('#nik').val(data.nik); // hidden (untuk submit)
                    $('#nikSelect').prop('disabled', true);

                    // ===============================
                    // 🔒 LOCK FIELD
                    // ===============================
                    $('input[name="nama"]').prop('readonly', true);
                    $('input[name="jbtn"]').prop('readonly', true);
                    $('#stts_kerja_display').prop('disabled', true);

                    // ===============================
                    // 🔥 SET DATA
                    // ===============================
                    $('input[name="nama"]').val(data.nama);
                    $('input[name="jbtn"]').val(data.jbtn);

                    $('#stts_kerja_display').val(data.stts_kerja);
                    $('#stts_kerja').val(data.stts_kerja);

                    $('input[name="mulai_kontrak"]').val(data.mulai_kontrak);

                    // 🔥 HITUNG MASA KERJA
                    let masaKerja = hitungMasaKerja(data.mulai_kontrak);
                    $('input[name="masa_kerja"]').val(masaKerja);

                    $('input[name="gaji_pokok"]').val(data.gaji_pokok);

                },

                error: function() {

                    Swal.close();

                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Data tidak dapat diambil'
                    });

                    modal.modal('hide');
                }
            });
        };

        window.deleteGapok = function(id) {
            // Tampilkan konfirmasi hapus
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data Gapok ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Kirim request DELETE menggunakan AJAX
                    $.ajax({
                        url: "{{ route("masterData.keuangan.gapok.delete", ":id") }}"
                            .replace(':id',
                                id),
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    'Dihapus!',
                                    response.message,
                                    'success'
                                );
                                gapokTable.ajax.reload(); // Reload DataTables
                            } else {
                                Swal.fire(
                                    'Gagal!',
                                    response.message,
                                    'error'
                                );
                            }
                        },
                        error: function(xhr) {
                            Swal.fire(
                                'Gagal!',
                                'Terjadi kesalahan saat menghapus Gapok.',
                                'error'
                            );
                        }
                    });
                }
            });
        }

        $('#btnSyncGapok').on('click', function() {

            Swal.fire({
                title: 'Sinkronisasi Data?',
                text: 'Data gaji pokok akan disesuaikan ulang dengan data pegawai.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Sinkronkan',
                cancelButtonText: 'Batal'
            }).then((result) => {

                if (!result.isConfirmed) return;

                Swal.fire({
                    title: 'Memproses...',
                    text: 'Sedang sinkronisasi data pegawai',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading()
                });

                $.ajax({
                    url: "{{ route("masterData.keuangan.gapok.syncGapok") }}",
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },

                    success: function(res) {

                        Swal.close();

                        if (res.status) {

                            Swal.fire({
                                icon: 'success',
                                title: 'Sinkronisasi Berhasil',
                                html: `
                                    <div class="text-start">
                                        <p>✅ <b>${res.result.updated}</b> data diperbarui</p>
                                        <p>➕ <b>${res.result.added}</b> data ditambahkan</p>
                                        <p>⚠️ <b>${res.result.skipped}</b> dilewati</p>
                                    </div>
                                `
                            });

                            // reload table
                            if (typeof gapokTable !== 'undefined') {
                                gapokTable.ajax.reload(null, false);
                            }

                        } else {

                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: res.message || 'Terjadi kesalahan'
                            });

                        }

                    },

                    error: function(xhr) {

                        Swal.close();

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message ||
                                'Server error'
                        });

                    }

                });

            });

        });

        // --- Download Template
        $('#downloadTemplateBtn').on('click', function() {
            window.location.href = "{{ route("masterData.keuangan.gapok.exportTemplate") }}";
        })

        $('#submitFormExcell').on('click', function() {

            let btn = $(this);
            let fileInput = $('#myDropify')[0];
            let file = fileInput.files[0];

            // 🔥 VALIDASI FILE
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

            // 🔥 VALIDASI EXTENSION
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

            // 🔥 VALIDASI SIZE (MAX 5MB)
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire({
                    icon: 'warning',
                    title: 'File Terlalu Besar',
                    text: 'Maksimal ukuran file adalah 5MB'
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

                url: "{{ route("masterData.keuangan.gapok.import") }}",
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

                    if (response.status) {

                        let result = response.result || {};

                        // 🔥 FORMAT HASIL
                        let html = `
                            <div class="text-start">
                                <p>✅ <b>${result.added || 0}</b> data ditambahkan</p>
                                <p>🔄 <b>${result.updated || 0}</b> data diperbarui</p>
                                <p>⚠️ <b>${result.skipped || 0}</b> data dilewati</p>
                            </div>
                        `;

                        // 🔥 TAMPILKAN ERROR DETAIL (JIKA ADA)
                        if (response.errors && response.errors.length > 0) {
                            html += `
                                <hr>
                                <div class="text-start">
                                    <b class="text-danger">Detail Error:</b>
                                    <ul class="small mt-2" style="max-height:150px; overflow:auto;">
                                        ${response.errors.map(e => `<li>${e}</li>`).join('')}
                                    </ul>
                                </div>
                            `;
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Import Selesai',
                            html: html,
                            width: 500
                        });

                        // 🔥 RESET FORM
                        $('#gapokModalExcell').modal('hide');
                        $('#myDropify').val('');

                        // 🔥 RELOAD TABLE
                        if (typeof gapokTable !== 'undefined') {
                            gapokTable.ajax.reload(null, false);
                        }

                    } else {

                        Swal.fire({
                            icon: 'error',
                            title: 'Import Gagal',
                            text: response.message ||
                                'Terjadi kesalahan saat import'
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

        $('#gapokModal').on('show.bs.modal', function() {

            let form = $('#gapokForm');

            // 🔥 reset form
            form[0].reset();

            // 🔥 hapus error
            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').text('');

            // 🔥 reset hidden id
            $('#gapokId').val('');

            // =========================
            // 🔥 RESET NIK (PENTING)
            // =========================
            $('#nikSelect').empty().trigger('change'); // clear select2 / select
            $('#nikSelect').prop('disabled', false); // aktifkan kembali
            $('#nik').val(''); // hidden

            // =========================
            // 🔥 RESET FIELD
            // =========================
            $('input[name="nama"]').val('').prop('readonly', false);
            $('input[name="jbtn"]').val('').prop('readonly', false);

            $('#stts_kerja_display').val('').prop('disabled', false);
            $('#stts_kerja').val('');

            $('input[name="mulai_kontrak"]').val('');
            $('input[name="masa_kerja"]').val('');

            $('input[name="gaji_pokok"]').val('');

            // =========================
            // 🔥 RESET TITLE & BUTTON
            // =========================
            $('#gapokModalLabel').html(`
                    <i class="mdi mdi-cash-multiple text-primary me-1"></i>
                    Form Gaji Pokok
                `);

            $('#submitForm').html(`
                    <i class="mdi mdi-content-save-outline me-1"></i>
                    Simpan
                `);

            // =========================
            // 🔥 LOAD DATA PEGAWAI (CREATE MODE)
            // =========================
            loadPegawai();

        });

    });
</script>
