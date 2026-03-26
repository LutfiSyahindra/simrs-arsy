<script>
    $(function() {

        let currentKode = 0;
        const container = $('#jabatanContainer'); // ✅ FIX
        let isEditMode = false;

        // ================= CSRF =================
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });

        // ================= DATATABLE =================
        let jabatanTable = $('#tableJabatan').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route("masterData.keuangan.jabatan.getJabatanTable") }}",
                type: "GET"
            },
            columns: [{
                    data: 'DT_RowIndex',
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
                    data: 'tunjangan',
                    name: 'tunjangan'
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $('.dataTables_filter').hide();

        $('#searchJabatan').on('keyup', function() {
            jabatanTable.search(this.value).draw();
        });

        // GENERATE KODE (FRONTEND)
        function generateKode() {
            let kode = "JBT" + String(currentKode).padStart(3, '0');
            currentKode++;
            return kode;
        }

        // TEMPLATE ROW
        function createRow(kode) {
            return `
                <div class="card border jabatan-item">
                    <div class="card-body py-3">
                        <div class="row g-3 align-items-end">

                            <div class="col-md-3">
                                <label class="form-label">Kode</label>
                                <input type="text" name="kode[]" class="form-control"
                                    value="${kode}" readonly>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Nama Jabatan</label>
                                <input type="text" name="nama[]" class="form-control"
                                    placeholder="Contoh: Direktur">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Tunjangan</label>
                                <input type="number" name="tunjangan[]" class="form-control" placeholder="Contoh: 1000000">
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

        // FEATHER ICON
        function refreshFeather() {
            if (typeof feather !== "undefined") {
                feather.replace();
            }
        }

        // ================= REMOVE ROW =================
        container.on("click", ".removeRow", function() {

            if (container.find(".jabatan-item").length === 1) {
                Swal.fire({
                    icon: "warning",
                    title: "Minimal 1 data"
                });
                return;
            }

            $(this).closest(".jabatan-item").remove(); // ✅ FIX lebih aman
        });

        // INIT MODAL (AMBIL KODE AWAL)
        $('#jabatanModal').on('show.bs.modal', function() {

            container.html('');

            // 🔥 JIKA EDIT → JANGAN JALANKAN INIT CREATE
            if (isEditMode) return;

            $('#addRow').prop('disabled', false);

            $.get('/simrs/masterData/keuangan/jabatan/generateKode', function(res) {

                currentKode = parseInt(res.kode.replace('JBT', ''));
                addRow();

            });

        });

        // CLOSE MODAL (RESET SEMUA)
        $('#jabatanModal').on('hidden.bs.modal', function() {

            isEditMode = false; // 🔥 reset mode

            $('#jabatanId').val('');
            $('#addRow').prop('disabled', false);

            $('#jabatanModalLabel').html('Jenis Jabatan');
            $('#submitForm').html(`
                <i data-feather="save" class="me-1"></i>
                Simpan Data
            `);

            if (typeof feather !== "undefined") {
                feather.replace();
            }

        });

        // SUBMIT AJAX
        $('#jabatanForm').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            let formData = form.serialize();
            let jabatanId = $('#jabatanId').val();

            let url = jabatanId ?
                `/simrs/masterData/keuangan/jabatan/${jabatanId}/update` :
                "{{ route("masterData.keuangan.jabatan.store") }}";

            let method = jabatanId ? 'PUT' : 'POST';

            // RESET VALIDASI
            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').text('');

            Swal.fire({
                title: jabatanId ?
                    'Perbarui data jabatan?' : 'Simpan data jabatan?',
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

                            $('#jabatanModal').modal('hide');

                            Swal.fire({
                                icon: 'success',
                                title: response.message,
                                toast: true,
                                position: 'top-end',
                                timer: 2500,
                                showConfirmButton: false
                            });

                            form[0].reset();
                            $('#jabatanId').val('');

                            if (typeof jabatanTable !== 'undefined') {
                                jabatanTable.ajax.reload(null, false);
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
        window.editJabatan = function(id) {

            const modal = $('#jabatanModal');
            const container = $('#jabatanContainer');
            const form = $('#jabatanForm');
            isEditMode = true;

            // =========================
            // SET MODE EDIT
            // =========================
            $('#jabatanId').val(id);
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
                url: "{{ route("masterData.keuangan.jabatan.edit", ":id") }}".replace(':id', id),
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
                                        <label class="form-label">Nama Jabatan</label>
                                        <input type="text" name="nama" class="form-control"
                                            value="${data.nama}">
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Tunjangan</label>
                                        <input type="number" name="tunjangan" class="form-control" placeholder="Contoh: 10" value="${data.tunjangan}">
                                    </div>

                                    <input type="hidden" name="jabatanId" id="jabatanId"  value="${data.id}">

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
                    $('#jabatanId').val('');
                }
            });
        };

        // DELETE
        window.deleteJabatan = function(id) {
            // Tampilkan konfirmasi hapus
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data Jabatan ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Kirim request DELETE menggunakan AJAX
                    $.ajax({
                        url: "{{ route("masterData.keuangan.jabatan.delete", ":id") }}"
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
                                jabatanTable.ajax.reload(); // Reload DataTables
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
