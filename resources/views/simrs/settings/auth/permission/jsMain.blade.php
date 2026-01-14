<script>
    $(document).ready(function() {

        $('#permissionsModal').on('show.bs.modal', function() {
            let form = $('#permissionsForm');
            $('#permissionsModalLabel').text('ADD PERMISSIONS');
            // reset form
            form.trigger('reset');

            // reset error message kalau ada
            form.find('.invalid-feedback').text('');
            form.find('.form-control').removeClass('is-invalid');

            // reset hidden userId
            $('#permissionsId').val('');
        });

        let permissionsTable = $('#tablePermissions').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route("permissions.table") }}", // pastikan route ini ada
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

        $('#searchPermission').on('keyup', function() {
            permissionsTable.search(this.value).draw();
        });

        $('#permissionsForm').on('submit', function(e) {
            e.preventDefault();

            let formData = $(this).serialize();
            let permissionsId = $('#permissionsId').val(); // Ambil ID user jika ada
            let url = permissionsId ? `/simrs/settings/permissions/${permissionsId}/update` :
                "{{ route("permissions.store") }}"; // Tentukan URL
            let method = permissionsId ? 'PUT' : 'POST'; // Tentukan metode

            Swal.fire({
                title: permissionsId ? 'Apakah Anda yakin ingin memperbarui data ini?' :
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
                                $('#permissionsModal').modal('hide');

                                Swal.fire({
                                    icon: 'success',
                                    title: response.message,
                                    toast: true,
                                    position: 'top-end',
                                    timer: 3000,
                                    timerProgressBar: true,
                                    showConfirmButton: false,
                                });

                                $('#permissionsForm')[0].reset();
                                $('#permissionsId').val(''); // Reset ID
                                permissionsTable.ajax.reload();
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

        window.editPermissions = function(PermissionsId) {
            const modal = $('#permissionsModal');
            modal.modal('show');

            $('#permissionsForm')[0].reset();
            $('#permissionsForm .invalid-feedback').text('');
            $('#permissionsForm .form-control').removeClass('is-invalid');
            $('#permissionsId').val(PermissionsId);

            $.ajax({
                url: "{{ route("permissions.edit", ":id") }}".replace(':id', PermissionsId),
                method: 'GET',
                success: function(response) {
                    console.log(response);

                    // ubah judul dan tombol
                    $('#permissionsModalLabel').text('EDIT PERMISSIONS');
                    $('#submitForm').text('Update');

                    // isi field form
                    $('#name').val(response.name);
                },
                error: function(xhr) {
                    console.error('Gagal mengambil data permissions', xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to fetch user data. Please try again.',
                    });
                    modal.modal('hide');
                }
            });
        }

        window.deletePermissions = function(PermissionsId) {
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
                        url: "{{ route("permissions.delete", ":id") }}".replace(':id',
                            PermissionsId),
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
                                permissionsTable.ajax.reload();
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

    });
</script>
