<script>
    $(function() {

        let currentKode = 0;
        const container = $('#jenisPremiContainer'); // ✅ FIX
        let isEditMode = false;

        // ================= CSRF =================
        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });

        // ================= DATATABLE =================
        let jenisPremiTable = $('#tableJenisPremi').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route("masterData.keuangan.jnsPremi.getJnsPremiTable") }}",
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
                    data: 'actions',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $('.dataTables_filter').hide();

        $('#searchJenisPremi').on('keyup', function() {
            jenisPremiTable.search(this.value).draw();
        });

        // GENERATE KODE (FRONTEND)
        function generateKode() {
            let kode = "PRM" + String(currentKode).padStart(3, '0');
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
                                        <input type="text" name="kode[]" class="form-control" placeholder="PRM001" value="${kode}"
                                            readonly>
                                    </div>

                                    <div class="col-md-8">
                                        <label class="form-label">Jenis Premi</label>
                                        <input type="text" name="jenis[]" class="form-control"
                                            placeholder="Contoh: Premi Medis">
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
        $('#jenisPremiModal').on('show.bs.modal', function() {

            $('#addRow').prop('disabled', false);
            container.html('');

            // 🔥 JIKA EDIT → JANGAN JALANKAN INIT CREATE
            if (isEditMode) return;

            $('#addRow').prop('hidden', false).removeAttr('hidden');

            $.get('/simrs/masterData/keuangan/jnsPremi/generateKode', function(res) {
                console.log('Kode awal untuk jenis premi baru:', res.kode);

                currentKode = parseInt(res.kode.replace('PRM', ''));
                addRow();

            });

        });

        // CLOSE MODAL (RESET SEMUA)
        $('#jenisPremiModal').on('hidden.bs.modal', function() {

            isEditMode = false; // 🔥 reset mode

            $('#jenisPremiId').val('');
            $('#addRow').prop('hidden', false).prop('disabled', false).removeAttr('hidden');

            $('#jenisPremiModalLabel').html('Jenis Premi');
            $('#submitForm').html(`
                <i data-feather="save" class="me-1"></i>
                Simpan Data
            `);

            if (typeof feather !== "undefined") {
                feather.replace();
            }

        });

        // SUBMIT AJAX
        $('#jenisPremiForm').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            let formData = form.serialize();
            let jenisPremiId = $('#jenisPremiId').val();

            let url = jenisPremiId ?
                `/simrs/masterData/keuangan/jnsPremi/${jenisPremiId}/update` :
                "{{ route("masterData.keuangan.jnsPremi.store") }}";

            let method = jenisPremiId ? 'PUT' : 'POST';

            // RESET VALIDASI
            $('.form-control, .form-select').removeClass('is-invalid');
            $('.invalid-feedback').text('');

            Swal.fire({
                title: jenisPremiId ?
                    'Perbarui data jenis tindakan?' : 'Simpan data jenis tindakan?',
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

                            $('#jenisPremiModal').modal('hide');

                            Swal.fire({
                                icon: 'success',
                                title: response.message,
                                toast: true,
                                position: 'top-end',
                                timer: 2500,
                                showConfirmButton: false
                            });

                            form[0].reset();
                            $('#jenisPremiId').val('');

                            if (typeof jenisPremiTable !== 'undefined') {
                                jenisPremiTable.ajax.reload(null, false);
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
        window.editJnsPremi = function(id) {

            const modal = $('#jenisPremiModal');
            const container = $('#jenisPremiContainer');
            const form = $('#jenisPremiForm');
            isEditMode = true;

            // =========================
            // SET MODE EDIT
            // =========================
            $('#jenisPremiId').val(id);
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
                url: "{{ route("masterData.keuangan.jnsPremi.edit", ":id") }}".replace(':id',
                    id),
                method: 'GET',

                success: function(response) {

                    Swal.close();

                    let data = response.data || response;

                    // =========================
                    // UI MODE EDIT
                    // =========================
                    $('#jenisPremiModalLabel').html(`
                        <i class="mdi mdi-pencil text-warning me-1"></i>
                        Edit Jenis Premi
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
                                        <input type="text" name="kode" class="form-control" placeholder="PRM001" value="${data.kode}"
                                            readonly>
                                    </div>

                                    <div class="col-md-8">
                                        <label class="form-label">Jenis premi</label>
                                        <input type="text" name="jenis" class="form-control" value="${data.jenis}"
                                            placeholder="Contoh: Premi Medis">
                                    </div>

                                    <input type="hidden" name="jenisPremiId" id="jenisPremiId" value="${data.id}">

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
                    $('#jenisPremiId').val('');
                }
            });
        };

        // DELETE
        window.deleteJnsPremi = function(id) {
            // Tampilkan konfirmasi hapus
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data Jenis Premi ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Kirim request DELETE menggunakan AJAX
                    $.ajax({
                        url: "{{ route("masterData.keuangan.jnsPremi.delete", ":id") }}"
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
                                jenisPremiTable.ajax.reload(); // Reload DataTables
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
                                'Terjadi kesalahan saat menghapus Jenis Premi.',
                                'error'
                            );
                        }
                    });
                }
            });
        }

    });
</script>
