<script>
    $(function() {
        let currentKode = 0;
        let isEditMode = false;

        const container = $('#plotingPremiContainer');
        const modal = $('#plotingPremiModal');
        const form = $('#plotingPremiForm');
        const kodePrefix = 'PLT';

        const routes = {
            table: "{{ route("masterData.keuangan.plotingPremi.getPlotingPremiTable") }}",
            generateKode: "{{ route("masterData.keuangan.plotingPremi.generateKode") }}",
            store: "{{ route("masterData.keuangan.plotingPremi.store") }}",
            edit: "{{ route("masterData.keuangan.plotingPremi.edit", ":id") }}",
            update: "{{ route("masterData.keuangan.plotingPremi.update", ":id") }}",
            delete: "{{ route("masterData.keuangan.plotingPremi.delete", ":id") }}"
        };

        $.ajaxSetup({
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
            }
        });

        const plotingPremiTable = $('#tablePlotingPremi').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: routes.table,
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
                    data: 'ploting',
                    name: 'ploting'
                },
                {
                    data: 'actions',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        $('.dataTables_filter').hide();

        $('#searchPlotingPremi').on('keyup', function() {
            plotingPremiTable.search(this.value).draw();
        });

        function routeWithId(route, id) {
            return route.replace(':id', id);
        }

        function refreshFeather() {
            if (typeof feather !== "undefined") {
                feather.replace();
            }
        }

        function escapeAttr(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function generateKode() {
            const kode = kodePrefix + String(currentKode).padStart(3, '0');
            currentKode++;

            return kode;
        }

        function createRow(kode, ploting = '', inputName = 'ploting[]', canRemove = true) {
            return `
                <div class="card border ploting-premi-item">
                    <div class="card-body py-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label">Kode</label>
                                <input type="text" name="kode[]" class="form-control" placeholder="PLT001" value="${escapeAttr(kode)}" readonly>
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">Ploting Premi</label>
                                <input type="text" name="${inputName}" class="form-control" value="${escapeAttr(ploting)}" placeholder="Contoh: Dokter">
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-md-1 text-end">
                                ${canRemove ? `
                                    <button type="button" class="btn btn-light btn-icon removeRow">
                                        <i data-feather="x"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function addRow() {
            container.append(createRow(generateKode()));
            refreshFeather();
        }

        function resetValidation() {
            form.find('.form-control, .form-select').removeClass('is-invalid');
            form.find('.invalid-feedback').text('');
        }

        function showValidationErrors(errors) {
            resetValidation();

            let firstMessage = 'Periksa kembali data yang diisi';

            $.each(errors, function(key, value) {
                const message = Array.isArray(value) ? value[0] : value;
                firstMessage = firstMessage === 'Periksa kembali data yang diisi' ? message : firstMessage;

                let input = form.find(`[name="${key}"]`);
                const match = key.match(/^ploting\.(\d+)$/);

                if (match) {
                    input = form.find('[name="ploting[]"]').eq(Number(match[1]));
                }

                input.addClass('is-invalid');
                input.closest('.col-md-8').find('.invalid-feedback').text(message);
            });

            Swal.fire({
                icon: 'error',
                title: 'Validasi gagal',
                text: firstMessage
            });
        }

        $('#addPlotingRow').on('click', function() {
            addRow();
        });

        container.on('click', '.removeRow', function() {
            if (container.find('.ploting-premi-item').length === 1) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Minimal 1 data'
                });
                return;
            }

            $(this).closest('.ploting-premi-item').remove();
        });

        modal.on('show.bs.modal', function() {
            if (isEditMode) {
                return;
            }

            form[0].reset();
            resetValidation();
            container.html('');
            $('#plotingPremiId').val('');
            $('#plotingPremiModalLabel').text('Master ploting premi');
            $('#submitPlotingPremi').html('<i data-feather="save" class="me-1"></i> Simpan Data');
            $('#addPlotingRow').prop('hidden', false).prop('disabled', false).removeAttr('hidden');

            $.get(routes.generateKode, function(res) {
                currentKode = parseInt(String(res.kode).replace(kodePrefix, ''), 10);
                addRow();
            });
        });

        modal.on('hidden.bs.modal', function() {
            isEditMode = false;
            form[0].reset();
            resetValidation();
            container.html('');
            $('#plotingPremiId').val('');
            $('#addPlotingRow').prop('hidden', false).prop('disabled', false).removeAttr('hidden');
            $('#plotingPremiModalLabel').text('Master ploting premi');
            $('#submitPlotingPremi').html('<i data-feather="save" class="me-1"></i> Simpan Data');
            refreshFeather();
        });

        form.on('submit', function(e) {
            e.preventDefault();

            const plotingPremiId = $('#plotingPremiId').val();
            const url = plotingPremiId ? routeWithId(routes.update, plotingPremiId) : routes.store;
            const method = plotingPremiId ? 'PUT' : 'POST';

            resetValidation();

            Swal.fire({
                title: plotingPremiId ? 'Perbarui data ploting premi?' : 'Simpan data ploting premi?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, simpan',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

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
                            modal.modal('hide');

                            Swal.fire({
                                icon: 'success',
                                title: response.message,
                                toast: true,
                                position: 'top-end',
                                timer: 2500,
                                showConfirmButton: false
                            });

                            plotingPremiTable.ajax.reload(null, false);
                        }
                    },
                    error: function(xhr) {
                        Swal.close();

                        if (xhr.status === 422) {
                            showValidationErrors(xhr.responseJSON.errors || {});
                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi Kesalahan',
                            text: xhr.responseJSON?.message || 'Server error'
                        });
                    }
                });
            });
        });

        window.editPlotingPremi = function(id) {
            isEditMode = true;
            form[0].reset();
            resetValidation();
            container.html('');
            $('#plotingPremiId').val(id);
            $('#addPlotingRow').prop('hidden', true);

            modal.modal('show');

            Swal.fire({
                title: 'Mengambil data...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: routeWithId(routes.edit, id),
                method: 'GET',
                success: function(response) {
                    Swal.close();

                    const data = response.data || response;
                    $('#plotingPremiId').val(data.id);
                    $('#plotingPremiModalLabel').html('<i class="mdi mdi-pencil text-warning me-1"></i> Edit Ploting Premi');
                    $('#submitPlotingPremi').html('<i class="mdi mdi-content-save-outline me-1"></i> Update');
                    container.html(createRow(data.kode, data.ploting, 'ploting', false));
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
                }
            });
        };

        window.deletePlotingPremi = function(id) {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Data Ploting Premi ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                $.ajax({
                    url: routeWithId(routes.delete, id),
                    type: 'DELETE',
                    success: function(response) {
                        if (response.success || response.status) {
                            Swal.fire('Dihapus!', response.message, 'success');
                            plotingPremiTable.ajax.reload(null, false);
                            return;
                        }

                        Swal.fire('Gagal!', response.message || 'Data gagal dihapus', 'error');
                    },
                    error: function(xhr) {
                        Swal.fire(
                            'Gagal!',
                            xhr.responseJSON?.message || 'Terjadi kesalahan saat menghapus Ploting Premi.',
                            'error'
                        );
                    }
                });
            });
        };
    });
</script>
