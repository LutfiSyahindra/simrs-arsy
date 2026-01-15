<script>
    $(document).ready(function() {
        $('#select-poli, #select-dokter').select2({
            placeholder: "-- Pilih --",
            allowClear: true,
            width: '100%',
            dropdownParent: $('#info-header-modal')
        });


        var table = $('#fixed-header-datatable').DataTable({
            destroy: true,
            searching: true,
            ordering: true,
            paging: true,
            responsive: true
        });

        // Ambil daftar Poli dari server
        $.ajax({
            url: '{{ route("pelayanan.petugasPanggil.getDataPoli") }}',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#select-poli').empty().append('<option value="">-- Pilih Poli --</option>');
                $.each(response, function(index, item) {
                    $('#select-poli').append(new Option(item.nm_poli, item.kd_poli));
                });
                $('#select-poli').trigger('change');
            },
            error: function(xhr, status, error) {
                console.error("Error fetching Poli: ", error);
            }
        });

        // Saat Poli dipilih, ambil daftar Dokter
        $('#select-poli').on('change', function() {
            var poliId = $(this).val();
            $('#select-dokter').empty().append('<option value="">-- Pilih Dokter --</option>');

            if (poliId) {
                $.ajax({
                    url: '{{ route("pelayanan.petugasPanggil.getDataDokter") }}',
                    method: 'GET',
                    data: {
                        PoliId: poliId
                    },
                    dataType: 'json',
                    success: function(response) {
                        $.each(response, function(index, item) {
                            $('#select-dokter').append(new Option(item.nm_dokter,
                                item.kd_dokter));
                        });
                        $('#select-dokter').trigger('change');
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching doctors: ", error);
                    }
                });
            }

            // Reset daftar pasien
            table.clear().draw();
        });

        // Saat Poli atau Dokter dipilih, ambil daftar pasien
        $('#select-poli, #select-dokter').on('change', function() {
            var poliId = $('#select-poli').val();
            var dokterId = $('#select-dokter').val();

            if (poliId && dokterId) {
                $.ajax({
                    url: '{{ route("pelayanan.petugasPanggil.getDataPasien") }}',
                    method: 'GET',
                    data: {
                        PoliId: poliId,
                        DokterId: dokterId
                    },
                    dataType: 'json',
                    success: function(response) {

                        table.clear().draw();
                        $('#info-header-modal').modal('hide');

                        // 🔔 JIKA TIDAK ADA DATA PASIEN
                        if (!response || response.length === 0) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Tidak Ada Pasien',
                                text: 'Tidak ada pasien untuk dokter dan poli yang dipilih.',
                                confirmButtonText: 'Mengerti',
                                customClass: {
                                    confirmButton: 'btn btn-primary'
                                },
                                buttonsStyling: false
                            });
                            return;
                        }

                        // ✅ JIKA ADA DATA
                        $.each(response, function(index, item) {
                            var button =
                                '<button class="btn btn-primary btn-sm panggil-pasien" ' +
                                'data-nama="' + item.nm_pasien + '" ' +
                                'data-poli="' + item.nm_poli + '" ' +
                                'data-dokter="' + item.nm_dokter + '" ' +
                                'data-tgl="' + item.tgl_lahir + '" ' +
                                'data-alamat="' + item.alamat + '" ' +
                                'data-jk="' + item.jk + '" ' +
                                'data-rm="' + item.no_rkm_medis + '" ' +
                                'data-rawat="' + item.no_rawat + '" ' +
                                'data-reg="' + item.no_reg + '">' +
                                '<i class="ri-volume-up-line me-1"></i> Panggil</button>';

                            table.row.add([
                                item.no_reg,
                                item.nm_pasien,
                                button
                            ]).draw(false);
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching patients: ", error);

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Mengambil Data',
                            text: 'Terjadi kesalahan saat mengambil data pasien.',
                            confirmButtonText: 'Tutup',
                            customClass: {
                                confirmButton: 'btn btn-danger'
                            },
                            buttonsStyling: false
                        });
                    }
                });
            } else {
                table.clear().draw();
            }
        });


        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Tangani klik tombol Panggil
        $('#fixed-header-datatable tbody').on('click', '.panggil-pasien', function() {
            var namaPasien = $(this).data('nama');
            var poli = $(this).data('poli');
            var dokter = $(this).data('dokter');
            var tglLahir = $(this).data('tgl');
            var alamat = $(this).data('alamat');
            var jnsKelamin = $(this).data('jk');
            var noRm = $(this).data('rm');
            var noRawat = $(this).data('rawat');
            var reg = $(this).data('reg');

            // tampilan di card-body panggil pasien
            $('#nama-pasien').text(namaPasien);
            $('#poli').text(poli);
            $('#dokter').text(dokter);
            $('#reg').text(reg);
            $('#detail-nama').text(namaPasien);
            $('#detail-tgl').text(tglLahir);
            $('#detail-alamat').text(alamat);
            $('#detail-jk').text(jnsKelamin);
            $('#detail-rm').text(noRm);
            $('#detail-rawat').text(noRawat);

            $.ajax({
                url: '{{ route("pelayanan.petugasPanggil.panggilPasien") }}',
                method: 'POST',
                data: {
                    nama: namaPasien,
                    poli: poli,
                    dokter: dokter,
                    no_reg: reg,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    console.log(response.message);
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                }
            });
        });

        // **Tangani Panggil Ulang**
        $('#panggil-ulang').on('click', function() {
            var namaPasien = $('#nama-pasien').text();
            var poli = $('#poli').text();

            if (namaPasien && poli) {
                alert("Memanggil ulang: " + namaPasien + " dari Poli " + poli);
            } else {
                alert("Tidak ada pasien yang dipilih untuk dipanggil ulang.");
            }
        });
    });
</script>
