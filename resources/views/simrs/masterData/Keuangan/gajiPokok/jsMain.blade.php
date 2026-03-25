<script>
    $(document).ready(function() {

        // --- Setup CSRF untuk semua AJAX request
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
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

            // reset form
            let form = $('#gapokForm');
            form[0].reset();

            $('.invalid-feedback').text('');
            $('.form-control, .form-select').removeClass('is-invalid');

            $('#gapokId').val(id);

            // loading kecil biar UX enak
            Swal.fire({
                title: 'Mengambil data...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: "{{ route("masterData.keuangan.gapok.edit", ":id") }}".replace(':id', id),
                method: 'GET',

                success: function(response) {

                    Swal.close();

                    // ubah judul & tombol
                    $('#gapokModalLabel').html(`
                <i class="mdi mdi-pencil text-warning me-1"></i>
                Edit Gaji Pokok
            `);

                    $('#submitForm').html(`
                <i class="mdi mdi-content-save-outline me-1"></i>
                Update
            `);

                    // isi form (FIX pakai name)
                    $('[name="nik"]').prop('readonly', true);
                    $('[name="nama"]').prop('readonly', true);
                    $('[name="jbtn"]').prop('readonly', true);
                    $('[name="stts_kerja"]').prop('disabled', true);
                    $('[name="masa_kerja"]').prop('disabled', true);
                    $('[name="nik"]').val(response.nik);
                    $('[name="nama"]').val(response.nama);
                    $('[name="jbtn"]').val(response.jbtn);
                    $('[name="stts_kerja"]').val(response.stts_kerja);
                    $('[name="masa_kerja"]').val(response.masa_kerja);
                    $('[name="gaji_pokok"]').val(response.gaji_pokok);

                },

                error: function(xhr) {

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

        // --- Download Template
        $('#downloadTemplateBtn').on('click', function() {
            window.location.href = "{{ route("masterData.keuangan.gapok.exportTemplate") }}";
        })

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
                                if (typeof gapokTable !== 'undefined') {
                                    gapokTable.ajax.reload(null, false);
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

        $('#gapokModal').on('show.bs.modal', function() {

            let form = $('#gapokForm');

            // reset semua field
            form[0].reset();

            // reset hidden id
            $('#gapokId').val('');

            // hapus error
            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').text('');

            // reset readonly (kalau sebelumnya edit)
            $('[name="nik"]').prop('readonly', false);

            // reset title & button
            $('#gapokModalLabel').html(`
                <i class="mdi mdi-cash-multiple text-primary me-1"></i>
                Form Gaji Pokok
            `);

            $('#submitForm').html(`
                <i class="mdi mdi-content-save-outline me-1"></i>
                Simpan
            `);

        });

    });
</script>
