<script>
    $(function() {

        let currentKode = 0;
        const container = $('#unitContainer'); // ✅ FIX
        let isEditMode = false;

        // ================= CSRF =================
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });

        // ================= DATATABLE =================
        let unitTable = $('#tableUnit').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route("masterData.keuangan.unit.getUnitTable") }}",
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
                    data: 'jenis',
                    name: 'jenis'
                },
                {
                    data: 'keterangan',
                    name: 'keterangan'
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $('.dataTables_filter').hide();

        $('#searchUnit').on('keyup', function() {
            unitTable.search(this.value).draw();
        });

        // GENERATE KODE (FRONTEND)
        function generateKode() {
            let kode = "UNT" + String(currentKode).padStart(3, '0');
            currentKode++;
            return kode;
        }

        // TEMPLATE ROW
        function createRow(kode) {
            return `
                <div class="card border profesi-item">
                    <div class="card-body py-3">
                        <div class="row g-3 align-items-end">

                                    <div class="col-md-2">
                                        <label class="form-label">Kode</label>
                                        <input type="text" name="kode[]" class="form-control" placeholder="UNT001" value="${kode}"
                                            readonly>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Jenis Unit</label>
                                        <input type="text" name="jenis[]" class="form-control"
                                            placeholder="Contoh: Profesi">
                                    </div>

                                    <!-- NAMA -->
                                    <div class="col-md-4">
                                        <label class="form-label">Nama Unit</label>
                                        <input type="text" name="nama[]" class="form-control"
                                            placeholder="Contoh: Medis">
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

            if (container.find(".profesi-item").length === 1) {
                Swal.fire({
                    icon: "warning",
                    title: "Minimal 1 data"
                });
                return;
            }

            $(this).closest(".profesi-item").remove(); // ✅ FIX lebih aman
        });

        // INIT MODAL (AMBIL KODE AWAL)
        $('#unitModal').on('show.bs.modal', function() {

            $('#addRow').prop('disabled', false);
            container.html('');

            // 🔥 JIKA EDIT → JANGAN JALANKAN INIT CREATE
            if (isEditMode) return;

            $('#addRow').prop('hidden', false).removeAttr('hidden');

            $.get('/simrs/masterData/keuangan/unit/generateKode', function(res) {
                console.log('Kode awal untuk unit baru:', res.kode);

                currentKode = parseInt(res.kode.replace('UNT', ''));
                addRow();

            });

        });

        // CLOSE MODAL (RESET SEMUA)
        $('#unitModal').on('hidden.bs.modal', function() {

            isEditMode = false; // 🔥 reset mode

            $('#unitId').val('');
            $('#addRow').prop('hidden', false).prop('disabled', false).removeAttr('hidden');

            $('#unitModalLabel').html('Jenis Unit');
            $('#submitForm').html(`
                <i data-feather="save" class="me-1"></i>
                Simpan Data
            `);

            if (typeof feather !== "undefined") {
                feather.replace();
            }

        });

        // SUBMIT AJAX
        $('#unitForm').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            let formData = form.serialize();
            let unitId = $('#unitId').val();

            let url = unitId ?
                `/simrs/masterData/keuangan/unit/${unitId}/update` :
                "{{ route("masterData.keuangan.unit.store") }}";

            let method = unitId ? 'PUT' : 'POST';

            // RESET VALIDASI
            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').text('');

            Swal.fire({
                title: unitId ?
                    'Perbarui data unit?' : 'Simpan data unit?',
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

                            $('#unitModal').modal('hide');

                            Swal.fire({
                                icon: 'success',
                                title: response.message,
                                toast: true,
                                position: 'top-end',
                                timer: 2500,
                                showConfirmButton: false
                            });

                            form[0].reset();
                            $('#unitId').val('');

                            if (typeof unitTable !== 'undefined') {
                                unitTable.ajax.reload(null, false);
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
        window.editUnit = function(id) {

            const modal = $('#unitModal');
            const container = $('#unitContainer');
            const form = $('#unitForm');
            isEditMode = true;

            // =========================
            // SET MODE EDIT
            // =========================
            $('#unitId').val(id);
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
                url: "{{ route("masterData.keuangan.unit.edit", ":id") }}".replace(':id', id),
                method: 'GET',

                success: function(response) {

                    Swal.close();

                    let data = response.data || response;

                    // =========================
                    // UI MODE EDIT
                    // =========================
                    $('#unitModalLabel').html(`
                        <i class="mdi mdi-pencil text-warning me-1"></i>
                        Edit Unit
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

                                    <div class="col-md-2">
                                        <label class="form-label">Kode</label>
                                        <input type="text" name="kode" class="form-control" placeholder="UNT001" value="${data.kode}"
                                            readonly>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Jenis unit</label>
                                        <input type="text" name="jenis" class="form-control" value="${data.jenis}"
                                            placeholder="Contoh: Profesi">
                                    </div>

                                    <!-- NAMA -->
                                    <div class="col-md-4">
                                        <label class="form-label">Nama unit</label>
                                        <input type="text" name="nama" class="form-control" value="${data.keterangan}"
                                            placeholder="Contoh: Medis">
                                    </div>

                                    <input type="hidden" name="unitId" id="unitId" value="${data.id}">

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
                    $('#addRow').prop('hidden', false).prop('disabled', false).removeAttr(
                        'hidden');
                    $('#unitId').val('');
                }
            });
        };

        // DELETE
        window.deleteUnit = function(id) {
            // Tampilkan konfirmasi hapus
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data Unit ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Kirim request DELETE menggunakan AJAX
                    $.ajax({
                        url: "{{ route("masterData.keuangan.unit.delete", ":id") }}"
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
                                unitTable.ajax.reload(); // Reload DataTables
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
                                'Terjadi kesalahan saat menghapus Unit.',
                                'error'
                            );
                        }
                    });
                }
            });
        }

    });
</script>
