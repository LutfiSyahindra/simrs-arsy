<script>
    $(document).ready(function() {

        $('#usersModal').on('show.bs.modal', function() {
            let form = $('#signupForm');
            $('#usersModalLabel').text('ADD USERS');
            // reset form
            form.trigger('reset');

            // reset error message kalau ada
            form.find('.invalid-feedback').text('');
            form.find('.form-control').removeClass('is-invalid');

            // reset hidden userId
            $('#userId').val('');
        });

        let userTable = $('#tableUsers').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route("users.table") }}", // pastikan route ini ada
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
                    data: 'email',
                    name: 'email'
                },
                {
                    data: 'status',
                    name: 'status',
                    render: function(data, type, row, meta) {
                        // jika status aktif = 1, checkbox dicentang
                        let checked = data == 1 ? 'checked' : '';
                        return `
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input toggle-status" data-id="${row.id}" ${checked} id="switch${row.id}">
                            <label class="form-check-label" for="switch${row.id}"></label>
                        </div>
                    `;
                    },
                    orderable: false,
                    searchable: false
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

        $('#searchUser').on('keyup', function() {
            userTable.search(this.value).draw();
        });

        $('#tableUsers').on('change', '.toggle-status', function() {
            let userId = $(this).data('id');
            let status = $(this).is(':checked') ? 1 : 0;
            console.log('User ID: ' + userId + ', Status: ' + status);

            $.ajax({
                url: "{{ route("users.updateStatus") }}", // pastikan route ini ada
                method: 'PUT',
                data: {
                    _token: '{{ csrf_token() }}',
                    status: status,
                    id: userId
                },
                success: function(response) {
                    console.log('Status updated!');
                    // SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Status user berhasil diperbarui.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                },
                error: function(err) {
                    console.log(err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat mengubah status.',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        });

        $('#signupForm').on('submit', function(e) {
            e.preventDefault();

            let formData = $(this).serialize();
            let userId = $('#userId').val(); // Ambil ID user jika ada
            let url = userId ? `/simrs/settings/users/${userId}/update` :
                "{{ route("users.store") }}"; // Tentukan URL
            let method = userId ? 'PUT' : 'POST'; // Tentukan metode

            Swal.fire({
                title: userId ? 'Apakah Anda yakin ingin memperbarui data ini?' :
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
                                $('#usersModal').modal('hide');

                                Swal.fire({
                                    icon: 'success',
                                    title: response.message,
                                    toast: true,
                                    position: 'top-end',
                                    timer: 3000,
                                    timerProgressBar: true,
                                    showConfirmButton: false,
                                });

                                $('#signupForm')[0].reset();
                                $('#userId').val(''); // Reset ID
                                userTable.ajax.reload();
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


        window.editUsers = function(userId) {
            const modal = $('#usersModal');
            modal.modal('show');

            // reset form & error state
            $('#signupForm')[0].reset();
            $('#signupForm .invalid-feedback').text('');
            $('#signupForm .form-control').removeClass('is-invalid');

            // set hidden ID user
            $('#userId').val(userId);

            $.ajax({
                url: "{{ route("users.edit", ":id") }}".replace(':id', userId),
                method: 'GET',
                success: function(response) {
                    console.log(response);

                    // ubah judul dan tombol
                    $('#usersModalLabel').text('EDIT USERS');
                    $('#submitForm').text('Update');

                    // isi field form
                    $('#name').val(response.name);
                    $('#email').val(response.email);

                    // kalau memang ada address & phone di response,
                    // pastikan form HTML juga punya fieldnya
                },
                error: function(xhr) {
                    console.error('Gagal mengambil data user', xhr);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to fetch user data. Please try again.',
                    });
                    modal.modal('hide');
                }
            });
        }

        window.deleteUsers = function(id) {
            // Tampilkan konfirmasi hapus
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: 'Users ini akan dihapus secara permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Kirim request DELETE menggunakan AJAX
                    $.ajax({
                        url: "{{ route("users.delete", ":id") }}".replace(':id',
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
                                userTable.ajax.reload(); // Reload DataTables
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
                                'Terjadi kesalahan saat menghapus users.',
                                'error'
                            );
                        }
                    });
                }
            });
        }

        function loadRolesOptions(selectedIds = []) {
            return $.ajax({
                url: '{{ route("users.dataRoles") }}',
                type: 'GET',
                success: function(response) {
                    const rolesSelect = $('#rolesSelect');
                    rolesSelect.empty();

                    // Tambah opsi satu per satu
                    response.forEach(function(roles) {
                        const isSelected = selectedIds.includes(roles.id.toString()) ?
                            'selected' : '';
                        rolesSelect.append(
                            `<option value="${roles.id}" ${isSelected}>${roles.name}</option>`
                        );
                    });

                    // Re-init select2
                    rolesSelect.select2({
                        dropdownParent: $('#assignRolesModal'),
                        placeholder: "Pilih roles",
                        allowClear: true,
                        width: '100%'
                    });
                },
                error: function() {
                    alert('Gagal memuat data roles!');
                }
            });
        }

        window.assignRoles = function(UsersId) {
            const modal = $('#assignRolesModal');
            modal.modal('show');
            $('#assignRolesModalLabel').text('ASSIGN ROLES');
            $('#userssId').val(UsersId);
            console.log('assignRoles ID: ' + UsersId);

            // Reset select dulu
            $('#rolesSelect').val(null).trigger('change');

            // Load semua opsi permission (jika pakai AJAX untuk pilihan permission)
            loadRolesOptions([]);

            // Ambil permissions yang sudah dimiliki role
            $.ajax({
                url: "{{ route("users.getUserRoles", ":id") }}".replace(':id', UsersId),
                type: "GET",
                success: function(res) {
                    if (res.status) {
                        // Set selected permissions di select2
                        $('#rolesSelect').val(res.data).trigger('change');
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Gagal mengambil data Roles!'
                    });
                }
            });
        }

        $('#assignRolesForm').on('submit', function(e) {
            e.preventDefault();

            let formData = $(this).serialize();

            $.ajax({
                url: "{{ route("users.assignRoles") }}",
                type: "POST",
                data: formData,
                success: function(response) {
                    if (response.status) { // pakai 'status' sesuai controller
                        $('#assignRolesModal').modal('hide'); // perbaikan typo
                        $('#assignRolesForm')[0].reset(); // perbaikan typo
                        $('#rolesSelect').val(null).trigger('change');
                        $('#tableUsers').DataTable().ajax.reload();

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
