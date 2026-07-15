<script>
    $(function() {
        let currentKode = 0;
        const container = $("#potonganContainer");
        let isEditMode = false;

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });

        $('#potonganModal').on('show.bs.modal', function() {
            container.html('');

            if (isEditMode) return;

            $('#addRow').prop('hidden', false);
            $('.modal-title').text('Master Jenis Potongan');
            $('button[form="potonganForm"]').html(`
                <i data-feather="save" class="me-1"></i> Simpan
            `);

            $.get('/simrs/masterData/keuangan/potongan/generateKode', function(res) {
                currentKode = parseInt(res.kode.replace('POT', ''));
                addRow();
            });
        });

        $('#potonganModal').on('hidden.bs.modal', function() {
            isEditMode = false;
            $('#potonganId').val('');
            $('#addRow').prop('hidden', false);
            $('#potonganForm')[0].reset();
            container.html('');
            $('.modal-title').text('Master Jenis Potongan');
            $('button[form="potonganForm"]').html(`
                <i data-feather="save" class="me-1"></i> Simpan
            `);
            $('.form-control, .form-select').removeClass('is-invalid');
            refreshFeather();
        });

        function generateKode() {
            let kode = "POT" + String(currentKode).padStart(3, '0');
            currentKode++;
            return kode;
        }

        function tipeOptions(selected = '') {
            return `
                <option value="manual" ${selected === 'manual' ? 'selected' : ''}>Manual</option>
                <option value="nominal" ${selected === 'nominal' ? 'selected' : ''}>Nominal Tetap</option>
                <option value="persen_gapok" ${selected === 'persen_gapok' ? 'selected' : ''}>Persen Gaji Pokok</option>
                <option value="persen_total_gaji" ${selected === 'persen_total_gaji' ? 'selected' : ''}>Persen Gaji Tahap 1 + 2</option>
            `;
        }

        function createRow(kode) {
            return `
            <div class="potongan-card p-3 rounded-4 border">
                <div class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Kode</label>
                        <input type="text" name="kode[]" class="form-control form-control-sm bg-light"
                            value="${kode}" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small text-muted">Nama</label>
                        <input type="text" name="nama[]" class="form-control form-control-sm"
                            placeholder="Contoh: BPJS Kesehatan">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted">Tipe</label>
                        <select name="tipe[]" class="form-select form-select-sm tipePotongan">
                            ${tipeOptions('manual')}
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted">Nilai</label>
                        <input type="number" name="nilai[]"
                            class="form-control form-control-sm input-nilai bg-light"
                            step="0.01" min="0" inputmode="decimal"
                            placeholder="Diisi di mapping" readonly>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small text-muted">Keterangan</label>
                        <input type="text" name="keterangan[]" class="form-control form-control-sm"
                            placeholder="Opsional">
                    </div>

                    <div class="col-md-1 text-end">
                        <button type="button" class="btn btn-sm btn-light removeRow">
                            <i data-feather="trash-2"></i>
                        </button>
                    </div>
                </div>
            </div>`;
        }

        function addRow() {
            container.append(createRow(generateKode()));
            refreshFeather();
        }

        $("#addRow").click(function() {
            addRow();
        });

        $(document).on('change', '.tipePotongan', function() {
            let row = $(this).closest('.row');
            let tipe = $(this).val();
            let input = row.find('.input-nilai');

            input.prop('readonly', false);
            input.removeClass('bg-light');
            input.removeAttr('max');
            input.attr({
                min: '0',
                step: '1',
                inputmode: 'numeric'
            });

            if (tipe === 'manual') {
                input.val('');
                input.prop('readonly', true);
                input.addClass('bg-light');
                input.attr('placeholder', 'Diisi di mapping');
            } else if (tipe === 'persen_gapok') {
                input.attr({
                    placeholder: 'Persen (contoh: 2.5)',
                    step: '0.01',
                    max: '100',
                    inputmode: 'decimal'
                });
            } else if (tipe === 'persen_total_gaji') {
                input.attr({
                    placeholder: 'Persen total gaji tahap 1 + 2',
                    step: '0.01',
                    max: '100',
                    inputmode: 'decimal'
                });
            } else {
                input.attr('placeholder', 'Nominal tetap');
            }
        });

        $(document).on('click', '.removeRow', function() {
            if ($('.potongan-card').length === 1) {
                Swal.fire("Minimal 1 potongan", "", "warning");
                return;
            }

            $(this).closest('.potongan-card').remove();
        });

        function refreshFeather() {
            if (typeof feather !== "undefined") {
                feather.replace();
            }
        }

        function formatRupiah(angka) {
            return new Intl.NumberFormat('id-ID').format(angka || 0);
        }

        function formatDecimal(angka) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }).format(angka || 0);
        }

        function labelTipe(tipe) {
            if (tipe === 'nominal') return 'Nominal Tetap';
            if (tipe === 'persen_gapok') return 'Persen Gaji Pokok';
            if (tipe === 'persen_total_gaji') return 'Persen Gaji Tahap 1 + 2';
            return 'Manual';
        }

        let jnsPotonganTable = $('#tableJnsPotongan').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route("masterData.keuangan.potongan.getJnsPotonganTable") }}",
                type: "GET"
            },
            columns: [{
                    data: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'kode'
                },
                {
                    data: 'nama'
                },
                {
                    data: 'tipe',
                    render: function(data) {
                        return labelTipe(data);
                    }
                },
                {
                    data: 'nilai',
                    render: function(data, type, row) {
                        if (row.tipe === 'manual') {
                            return '<span class="text-muted">Diisi di mapping</span>';
                        }

                        if (row.tipe === 'persen_gapok' || row.tipe === 'persen_total_gaji') {
                            return `<span class="badge bg-light text-dark">${formatDecimal(data)}%</span>`;
                        }

                        return `<span class="badge bg-light text-dark">Rp ${formatRupiah(data)}</span>`;
                    }
                },
                {
                    data: 'keterangan',
                    defaultContent: '-'
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $('.dataTables_filter').hide();

        $('#searchJnsPotongan').on('keyup', function() {
            jnsPotonganTable.search(this.value).draw();
        });

        $('#potonganForm').on('submit', function(e) {
            e.preventDefault();

            let form = $(this);
            let potonganId = $('#potonganId').val();

            let url = potonganId ?
                `/simrs/masterData/keuangan/potongan/${potonganId}/update` :
                "{{ route("masterData.keuangan.potongan.store") }}";

            let method = potonganId ? 'PUT' : 'POST';

            $('.form-control, .form-select').removeClass('is-invalid');

            Swal.fire({
                title: potonganId ? 'Perbarui data potongan?' : 'Simpan data potongan?',
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
                            $('#potonganModal').modal('hide');

                            Swal.fire({
                                icon: 'success',
                                title: response.message,
                                toast: true,
                                position: 'top-end',
                                timer: 2500,
                                showConfirmButton: false
                            });

                            form[0].reset();
                            $('#potonganId').val('');
                            jnsPotonganTable.ajax.reload(null, false);
                        }
                    },
                    error: function(xhr) {
                        Swal.close();

                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;

                            $.each(errors, function(key, value) {
                                let name = key.replace(/\.\d+/g, '[]');
                                let indexMatch = key.match(/\d+/);
                                let index = indexMatch ? indexMatch[0] : null;
                                let input = index !== null ?
                                    $('[name="' + name + '"]').eq(index) :
                                    $('[name="' + key + '"]');

                                input.addClass('is-invalid');
                                input.attr('title', value[0]);
                            });
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

        window.editJnsPotongan = function(id) {
            const modal = $('#potonganModal');
            isEditMode = true;
            $('#potonganId').val(id);
            $('#addRow').prop('hidden', true);
            $('#potonganForm')[0].reset();
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
                url: "{{ route("masterData.keuangan.potongan.edit", ":id") }}".replace(':id', id),
                method: 'GET',
                success: function(response) {
                    Swal.close();
                    let data = response.data || response;
                    let readonly = data.tipe === 'manual' ? 'readonly' : '';
                    let nilaiClass = data.tipe === 'manual' ? 'bg-light' : '';

                    $('.modal-title').text('Edit Jenis Potongan');
                    $('button[form="potonganForm"]').html(`
                        <i data-feather="save" class="me-1"></i> Update
                    `);

                    container.html(`
                        <div class="potongan-card p-3 rounded-4 border">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Kode</label>
                                    <input type="text" class="form-control form-control-sm bg-light"
                                        value="${data.kode}" readonly>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Nama</label>
                                    <input type="text" name="nama" class="form-control form-control-sm"
                                        value="${data.nama}">
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Tipe</label>
                                    <select name="tipe" class="form-select form-select-sm tipePotongan">
                                        ${tipeOptions(data.tipe)}
                                    </select>
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Nilai</label>
                                    <input type="number" name="nilai"
                                        class="form-control form-control-sm input-nilai ${nilaiClass}"
                                        step="0.01" min="0" inputmode="decimal"
                                        value="${data.nilai ?? ''}"
                                        ${readonly}>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label small text-muted">Keterangan</label>
                                    <input type="text" name="keterangan" class="form-control form-control-sm"
                                        value="${data.keterangan ?? ''}">
                                </div>
                            </div>
                        </div>
                    `);

                    $('.tipePotongan').trigger('change');
                    refreshFeather();
                },
                error: function() {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Data tidak dapat diambil'
                    });
                    modal.modal('hide');
                    $('#addRow').prop('hidden', false);
                    $('#potonganId').val('');
                }
            });
        };

        window.deleteJnsPotongan = function(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data Jenis Potongan ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (!result.isConfirmed) return;

                $.ajax({
                    url: "{{ route("masterData.keuangan.potongan.delete", ":id") }}".replace(':id', id),
                    type: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire('Dihapus!', response.message, 'success');
                            jnsPotonganTable.ajax.reload();
                        } else {
                            Swal.fire('Gagal!', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Gagal!',
                            'Terjadi kesalahan saat menghapus Jenis Potongan.',
                            'error'
                        );
                    }
                });
            });
        };
    });
</script>
