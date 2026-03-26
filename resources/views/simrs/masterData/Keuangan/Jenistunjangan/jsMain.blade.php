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

            // reset container
            container.html('');

            // 🔥 kalau edit → skip init create
            if (isEditMode) return;

            // =========================
            // MODE CREATE
            // =========================
            $('#addRow').prop('hidden', false);

            $('.modal-title').text('Master Jenis Tunjangan');

            $('button[form="tunjanganForm"]').html(`
                <i data-feather="save" class="me-1"></i> Simpan
            `);

            // ambil kode awal
            $.get('/simrs/masterData/keuangan/tunjangan/generateKode', function(res) {

                currentKode = parseInt(res.kode.replace('TJ', ''));
                addRow();

            });

        });

        // CLOSE MODAL (RESET SEMUA)
        $('#tunjanganModal').on('hidden.bs.modal', function() {

            // =========================
            // RESET STATE
            // =========================
            isEditMode = false;

            $('#tunjanganId').val('');
            $('#addRow').prop('hidden', false);

            // reset form & container
            $('#tunjanganForm')[0].reset();
            $('#tunjanganContainer').html('');

            // reset UI
            $('.modal-title').text('Master Jenis Tunjangan');

            $('button[form="tunjanganForm"]').html(`
                <i data-feather="save" class="me-1"></i> Simpan
            `);

            $('.form-control, .form-select').removeClass('is-invalid');

            // refresh icon
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
            <div class="tunjangan-card p-3 rounded-4 border">
                <div class="row g-3 align-items-end">

                    <!-- KODE -->
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Kode</label>
                        <input type="text" name="kode[]" class="form-control form-control-sm bg-light"
                            value="${kode}" readonly>
                    </div>

                    <!-- NAMA -->
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Nama</label>
                        <input type="text" name="nama[]" class="form-control form-control-sm"
                            placeholder="Contoh: Tunjangan Anak">
                    </div>

                    <!-- TIPE -->
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Tipe</label>
                        <select name="tipe[]" class="form-select form-select-sm tipeTunjangan">
                            <option value="">Pilih</option>
                            <option value="jabatan">Jabatan</option>
                            <option value="profesi">Profesi</option>
                            <option value="anak">Anak</option>
                            <option value="pasangan">Suami/Istri</option>
                            <option value="masa_kerja">Masa Kerja</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>

                    <!-- NILAI -->
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Nilai</label>
                        <input type="number" name="nilai[]" 
                            class="form-control form-control-sm input-nilai"
                            placeholder="Isi sesuai tipe">
                    </div>

                    <!-- REMOVE -->
                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-sm btn-light removeRow">
                            <i data-feather="trash-2"></i>
                        </button>
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

        $(document).on('change', '.tipeTunjangan', function() {

            let row = $(this).closest('.row');
            let tipe = $(this).val();
            let input = row.find('.input-nilai');

            input.prop('disabled', false);

            if (tipe === 'jabatan' || tipe === 'profesi') {
                input.val('');
                input.prop('disabled', true);
                input.attr('placeholder', 'Otomatis dari master');
            } else if (tipe === 'anak') {
                input.attr('placeholder', 'Persen per anak (contoh: 5)');
            } else if (tipe === 'pasangan') {
                input.attr('placeholder', 'Persen (contoh: 10)');
            } else if (tipe === 'masa_kerja') {
                input.attr('placeholder', 'Nominal per tahun (contoh: 50000)');
            } else {
                input.attr('placeholder', 'Nominal / persen bebas');
            }

        });
        // REMOVE ROW
        $(document).on('click', '.removeRow', function() {
            $(this).closest('.tunjangan-card').remove();
        });

        // FEATHER ICON
        function refreshFeather() {
            if (typeof feather !== "undefined") {
                feather.replace();
            }
        }

        // DATATABLE
        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID').format(angka);
        }

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
                    data: 'tipe',
                    name: 'tipe'
                },
                {
                    data: 'nilai',
                    name: 'nilai', // 🔥 FIX (hapus %)
                    render: function(data, type, row) {

                        if (row.tipe === 'jabatan' || row.tipe === 'profesi') {
                            return '<span class="text-muted">Mengikutkan dari master</span>';
                        }

                        if (row.tipe === 'anak' || row.tipe === 'pasangan') {
                            return `<span class="badge bg-light text-dark">${data}%</span>`;
                        }

                        if (row.tipe === 'masa_kerja') {
                            return `<span class="badge bg-light text-dark">Rp ${formatRupiah(data)} / tahun</span>`;
                        }

                        return `<span class="badge bg-light text-dark">Rp ${formatRupiah(data)}</span>`;
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

            Swal.fire({
                title: tunjanganId ? 'Perbarui data tunjangan?' : 'Simpan data tunjangan?',
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

                        Swal.close();

                        if (xhr.status === 422) {

                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {

                                // 🔥 HANDLE ARRAY VALIDATION
                                let name = key.replace(/\.\d+/g, '[]');
                                let indexMatch = key.match(/\d+/);
                                let index = indexMatch ? indexMatch[0] :
                                    null;

                                let input;

                                if (index !== null) {
                                    input = $('[name="' + name + '"]').eq(
                                        index);
                                } else {
                                    input = $('[name="' + key + '"]');
                                }

                                input.addClass('is-invalid');

                                // OPTIONAL: tooltip error
                                input.attr('title', value[0]);
                            });

                        } else {

                            Swal.fire({
                                icon: 'error',
                                title: 'Terjadi Kesalahan',
                                text: xhr.responseJSON?.message ||
                                    'Server error'
                            });

                        }

                    }

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

            form[0].reset();
            container.html('');

            $('.form-control, .form-select').removeClass('is-invalid');

            modal.modal('show');

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
                    // HEADER EDIT
                    // =========================
                    $('.modal-title').text('Edit Jenis Tunjangan');
                    $('button[form="tunjanganForm"]').html(`
                <i data-feather="save" class="me-1"></i> Update
            `);

                    // =========================
                    // BUILD ROW
                    // =========================
                    let disabled = (data.tipe === 'jabatan' || data.tipe === 'profesi') ?
                        'disabled' : '';

                    container.html(`
                <div class="tunjangan-card p-3 rounded-4 border">
                    <div class="row g-3 align-items-end">

                        <!-- KODE -->
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Kode</label>
                            <input type="text" class="form-control form-control-sm bg-light"
                                value="${data.kode}" readonly>
                        </div>

                        <!-- NAMA -->
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Nama</label>
                            <input type="text" name="nama" class="form-control form-control-sm"
                                value="${data.nama}">
                        </div>

                        <!-- TIPE -->
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Tipe</label>
                            <select name="tipe" class="form-select form-select-sm tipeTunjangan">
                                <option value="">Pilih</option>
                                <option value="jabatan" ${data.tipe === 'jabatan' ? 'selected' : ''}>Jabatan</option>
                                <option value="profesi" ${data.tipe === 'profesi' ? 'selected' : ''}>Profesi</option>
                                <option value="anak" ${data.tipe === 'anak' ? 'selected' : ''}>Anak</option>
                                <option value="pasangan" ${data.tipe === 'pasangan' ? 'selected' : ''}>Suami/Istri</option>
                                <option value="masa_kerja" ${data.tipe === 'masa_kerja' ? 'selected' : ''}>Masa Kerja</option>
                                <option value="custom" ${data.tipe === 'custom' ? 'selected' : ''}>Custom</option>
                            </select>
                        </div>

                        <!-- NILAI -->
                        <div class="col-md-3">
                            <label class="form-label small text-muted">Nilai</label>
                            <input type="number" name="nilai"
                                class="form-control form-control-sm input-nilai"
                                value="${data.nilai ?? ''}"
                                ${disabled}>
                        </div>

                        <input type="hidden" name="tunjanganId" id="tunjanganId" value="${data.id}">

                        <!-- REMOVE -->
                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-sm btn-light removeRow">
                                <i data-feather="trash-2"></i>
                            </button>
                        </div>

                    </div>
                </div>
            `);

                    // =========================
                    // TRIGGER TIPE LOGIC (BIAR PLACEHOLDER IKUT)
                    // =========================
                    $('.tipeTunjangan').trigger('change');

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

                    // BALIKIN MODE CREATE
                    $('#addRow').prop('hidden', false);
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
