<script>
    $(document).ready(function() {

        $('#rolesModal').on('show.bs.modal', function() {
            let form = $('#rolesForm');
            $('#rolesModalLabel').text('ADD ROLES');
            // reset form
            form.trigger('reset');

            // reset error message kalau ada
            form.find('.invalid-feedback').text('');
            form.find('.form-control').removeClass('is-invalid');

            // reset hidden userId
            $('#rolesId').val('');
        });

        let rolesTable = $('#tableRoles').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route("roles.table") }}", // pastikan route ini ada
                type: "GET"
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'name',
                    name: 'name'
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

        $('#searchRole').on('keyup', function() {
            rolesTable.search(this.value).draw();
        });

        $('#rolesForm').on('submit', function(e) {
            e.preventDefault();

            let formData = $(this).serialize();
            let rolesId = $('#rolesId').val(); // Ambil ID user jika ada
            let url = rolesId ? `/simrs/settings/roles/${rolesId}/update` :
                "{{ route("roles.store") }}"; // Tentukan URL
            let method = rolesId ? 'PUT' : 'POST'; // Tentukan metode

            Swal.fire({
                title: rolesId ? 'Apakah Anda yakin ingin memperbarui data ini?' :
                    'Apakah Anda yakin ingin menambahkan data ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, simpan!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        method: method,
                        data: formData,
                        success: function(response) {
                            if (response.status === 'success') {
                                $('#rolesModal').modal('hide');

                                Swal.fire({
                                    icon: 'success',
                                    title: response.message,
                                    toast: true,
                                    position: 'top-end',
                                    timer: 3000,
                                    timerProgressBar: true,
                                    showConfirmButton: false,
                                });

                                $('#rolesForm')[0].reset();
                                $('#rolesId').val(''); // Reset ID
                                rolesTable.ajax.reload();
                            }
                        },
                        error: function(xhr) {
                            if (xhr.status === 422) {
                                let errors = xhr.responseJSON.errors;
                                for (let key in errors) {
                                    $(`#error-${key}`).text(errors[key][0]);
                                    $(`#${key}`).addClass('is-invalid');
                                }
                            }
                        }
                    });
                }
            });
        });

        window.editRoles = function(RolesId) {
            const modal = $('#rolesModal');
            modal.modal('show');

            $('#rolesForm')[0].reset();
            $('#rolesForm .invalid-feedback').text('');
            $('#rolesForm .form-control').removeClass('is-invalid');
            $('#rolesId').val(RolesId);

            $.ajax({
                url: "{{ route("roles.edit", ":id") }}".replace(':id', RolesId),
                method: 'GET',
                success: function(response) {
                    console.log(response);

                    // ubah judul dan tombol
                    $('#rolesModalLabel').text('EDIT ROLES');
                    $('#submitForm').text('Update');

                    // isi field form
                    $('#name').val(response.name);
                },
                error: function(xhr) {
                    console.error('Gagal mengambil data roles', xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to fetch user data. Please try again.',
                    });
                    modal.modal('hide');
                }
            });
        }

        window.deleteRoles = function(RolesId) {
            Swal.fire({
                title: 'Apakah Anda yakin ingin menghapus data ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route("roles.delete", ":id") }}".replace(':id', RolesId),
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: response.message,
                                    toast: true,
                                    position: 'top-end',
                                    timer: 3000, // Tampilkan pesan selama 3 detik
                                    timerProgressBar: true,
                                    showConfirmButton: false,
                                });
                                rolesTable.ajax.reload();
                            }
                        },
                        error: function(xhr) {
                            console.error('Gagal menghapus data roles', xhr);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to delete user data. Please try again.',
                            });
                        }
                    });
                }
            });
        }

        function loadPermissionsOptions(selectedIds = []) {
            return $.ajax({
                url: '{{ route("roles.dataPermissions") }}',
                type: 'GET',
                success: function(response) {
                    const permissionsSelect = $('#permissionsSelect');
                    permissionsSelect.empty();

                    // Tambah opsi satu per satu
                    response.forEach(function(permissions) {
                        const isSelected = selectedIds.includes(permissions.id.toString()) ?
                            'selected' : '';
                        permissionsSelect.append(
                            `<option value="${permissions.id}" ${isSelected}>${permissions.name}</option>`
                        );
                    });

                    // Re-init select2
                    permissionsSelect.select2({
                        dropdownParent: $('#assignPermissionsModal'),
                        placeholder: "Pilih Permissions",
                        allowClear: true,
                        width: '100%'
                    });
                },
                error: function() {
                    alert('Gagal memuat data permissions!');
                }
            });
        }

        window.assignPermissions = function(RolesId) {
            const modal = $('#assignPermissionsModal');
            modal.modal('show');
            $('#assignPermissionsModalLabel').text('ASSIGN PERMISSIONS');
            $('#rolessId').val(RolesId);
            console.log('assignPermissions ID: ' + RolesId);

            // Reset select dulu
            $('#permissionsSelect').val(null).trigger('change');

            // Load semua opsi permission (jika pakai AJAX untuk pilihan permission)
            loadPermissionsOptions([]);

            // Ambil permissions yang sudah dimiliki role
            $.ajax({
                url: "{{ route("roles.getRolePermissions", ":id") }}".replace(':id', RolesId),
                type: "GET",
                success: function(res) {
                    if (res.status) {
                        // Set selected permissions di select2
                        $('#permissionsSelect').val(res.data).trigger('change');
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Gagal mengambil data permissions!'
                    });
                }
            });
        }

        $('#assignPermissionsForm').on('submit', function(e) {
            e.preventDefault();

            let formData = $(this).serialize();

            $.ajax({
                url: "{{ route("roles.assignPermissions") }}",
                type: "POST",
                data: formData,
                success: function(response) {
                    if (response.status) { // pakai 'status' sesuai controller
                        $('#assignPermissionsModal').modal('hide'); // perbaikan typo
                        $('#assignPermissionsForm')[0].reset(); // perbaikan typo
                        $('#permissionsSelect').val(null).trigger('change');
                        $('#tableRoles').DataTable().ajax.reload();

                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Terjadi kesalahan server!'
                    });
                }
            });
        });


    });
</script>
