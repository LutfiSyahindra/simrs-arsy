<script>
    $(function() {

        let currentKode = 0;
        const container = $("#tunjanganContainer");
        let isEditMode = false;

        // --- Setup CSRF untuk semua AJAX request
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });

        // INIT MODAL (AMBIL KODE AWAL)
        $('#tunjanganModal').on('show.bs.modal', function() {

            container.html('');

            // 🔥 JIKA EDIT → JANGAN JALANKAN INIT CREATE
            if (isEditMode) return;

            $('#addRow').prop('disabled', false);

            $.get('/simrs/masterData/keuangan/tunjangan/generateKode', function(res) {

                currentKode = parseInt(res.kode.replace('TJ', ''));
                addRow();

            });

        });

        // CLOSE MODAL (RESET SEMUA)
        $('#tunjanganModal').on('hidden.bs.modal', function() {

            isEditMode = false; // 🔥 reset mode

            $('#tunjanganId').val('');
            $('#addRow').prop('disabled', false);

            $('#tunjanganModalLabel').html('Jenis Tunjangan');
            $('#submitForm').html(`
                <i data-feather="save" class="me-1"></i>
                Simpan Data
            `);

            if (typeof feather !== "undefined") {
                feather.replace();
            }

        });

        // GENERATE KODE (FRONTEND)
        function generateKode() {
            let kode = "TJ" + String(currentKode).padStart(3, '0');
            currentKode++;
            return kode;
        }

        // TEMPLATE ROW
        function createRow(kode) {
            return `
            <div class="card border">
                <div class="card-body py-3">
                    <div class="row g-3 align-items-end">

                        <div class="col-md-3">
                            <label class="form-label">Kode</label>
                            <input type="text" name="kode[]" class="form-control"
                                value="${kode}" readonly>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Nama Tunjangan</label>
                            <input type="text" name="nama[]" class="form-control"
                                placeholder="Contoh: Tunjangan Jabatan">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Persen (%)</label>
                            <input type="number" name="persentase[]" class="form-control" placeholder="Contoh: 10">
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

        // ADD ROW (PAKAI FRONTEND COUNTER)
        function addRow() {
            let kode = generateKode();
            container.append(createRow(kode));
            refreshFeather();
        }

        $("#addRow").click(function() {
            addRow();
        });

        // REMOVE ROW
        container.on("click", ".removeRow", function() {

            if (container.find(".card").length === 1) {
                Swal.fire({
                    icon: "warning",
                    title: "Minimal 1 data"
                });
                return;
            }

            $(this).closest(".card").remove();
        });

        // FEATHER ICON
        function refreshFeather() {
            if (typeof feather !== "undefined") {
                feather.replace();
            }
        }

        // DATATABLE
        let jnsTunjanganTable = $('#tableJnsTunjangan').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route("masterData.keuangan.tunjangan.getJnsTunjanganTable") }}",
                type: "GET"
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'kode',
                    name: 'kode'
                },
                {
                    data: 'nama',
                    name: 'nama'
                },
                {
                    data: 'persentase',
                    name: 'persentase', // 🔥 FIX (hapus %)
                    render: function(data, type, row) {

                        if (!data) return '-';

                        let val = parseFloat(data);

                        return `
                    <span class="badge bg-light text-dark border fw-semibold">
                        ${val}%
                    </span>
                `;
                    }
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

        $('#searchJnsTunjangan').on('keyup', function() {
            jnsTunjanganTable.search(this.value).draw();
        });

        // SUBMIT AJAX
        $('#tunjanganForm').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            let formData = form.serialize();
            let tunjanganId = $('#tunjanganId').val();

            let url = tunjanganId ?
                `/simrs/masterData/keuangan/tunjangan/${tunjanganId}/update` :
                "{{ route("masterData.keuangan.tunjangan.store") }}";

            let method = tunjanganId ? 'PUT' : 'POST';

            // RESET VALIDASI
            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').text('');

            Swal.fire({
                title: tunjanganId ?
                    'Perbarui data tunjangan?' : 'Simpan data tunjangan?',
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

                            $('#tunjanganModal').modal('hide');

                            Swal.fire({
                                icon: 'success',
                                title: response.message,
                                toast: true,
                                position: 'top-end',
                                timer: 2500,
                                showConfirmButton: false
                            });

                            form[0].reset();
                            $('#tunjanganId').val('');

                            if (typeof jnsTunjanganTable !== 'undefined') {
                                jnsTunjanganTable.ajax.reload(null, false);
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

        // EDIT
        window.editJnsTunjangan = function(id) {

            const modal = $('#tunjanganModal');
            const container = $('#tunjanganContainer');
            const form = $('#tunjanganForm');
            isEditMode = true;

            // =========================
            // SET MODE EDIT
            // =========================
            $('#tunjanganId').val(id);
            $('#addRow').prop('hidden', true);

            // reset dulu sebelum show
            form[0].reset();
            container.html('');

            $('.invalid-feedback').text('');
            $('.form-control, .form-select').removeClass('is-invalid');

            // baru tampilkan modal
            modal.modal('show');

            // loading
            Swal.fire({
                title: 'Mengambil data...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: "{{ route("masterData.keuangan.tunjangan.edit", ":id") }}".replace(':id', id),
                method: 'GET',

                success: function(response) {

                    Swal.close();

                    let data = response.data || response;

                    // =========================
                    // UI MODE EDIT
                    // =========================
                    $('#tunjanganModalLabel').html(`
                        <i class="mdi mdi-pencil text-warning me-1"></i>
                        Edit Jenis Tunjangan
                    `);

                    $('#submitForm').html(`
                        <i class="mdi mdi-content-save-outline me-1"></i>
                        Update
                    `);

                    // =========================
                    // INJECT ROW
                    // =========================
                    container.html(`
                        <div class="card border">
                            <div class="card-body py-3">
                                <div class="row g-3 align-items-end">

                                    <div class="col-md-3">
                                        <label class="form-label">Kode</label>
                                        <input type="text" class="form-control"
                                            value="${data.kode}" readonly>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Nama Tunjangan</label>
                                        <input type="text" name="nama" class="form-control"
                                            value="${data.nama}">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Persen (%)</label>
                                        <input type="number" name="persentase" class="form-control" placeholder="Contoh: 10" value="${data.persentase}">
                                    </div>

                                    <input type="hidden" name="tunjanganId" id="tunjanganId"  value="${data.id}">

                                    <div class="col-md-1 text-end">
                                        <button type="button" class="btn btn-light btn-icon removeRow">
                                            <i data-feather="x"></i>
                                        </button>
                                    </div>

                                </div>
                            </div>
                        </div>
                    `);

                    if (typeof feather !== "undefined") {
                        feather.replace();
                    }

                },

                error: function() {

                    Swal.close();

                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Data tidak dapat diambil'
                    });

                    modal.modal('hide');

                    // 🔥 BALIKIN KE CREATE MODE
                    $('#addRow').prop('disabled', false);
                    $('#tunjanganId').val('');
                }
            });
        };

        // DELETE
        window.deleteJnsTunjangan = function(id) {
            // Tampilkan konfirmasi hapus
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data Jenis Tunjangan ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Kirim request DELETE menggunakan AJAX
                    $.ajax({
                        url: "{{ route("masterData.keuangan.tunjangan.delete", ":id") }}"
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
                                jnsTunjanganTable.ajax.reload(); // Reload DataTables
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
                                'Terjadi kesalahan saat menghapus Jenis Tunjangan.',
                                'error'
                            );
                        }
                    });
                }
            });
        }

    });
</script>
