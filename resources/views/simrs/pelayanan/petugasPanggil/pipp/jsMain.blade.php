<script>
    $(document).ready(function() {
        var table = $('#fixed-header-datatable').DataTable({
            destroy: true,
            searching: true,
            ordering: true,
            paging: true,
            responsive: true // <-- Tambahkan koma di sini jika ada properti lain setelahnya
        });

        $.ajax({
            url: '{{ route("pelayanan.petugasPanggil.pipp.pippPanggil.dataPasien") }}',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                table.clear().draw();

                // 🔔 JIKA TIDAK ADA DATA PASIEN
                if (!response || response.length === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Tidak Ada Pasien',
                        text: 'Belum ada pasien hari ini.',
                        confirmButtonText: 'Mengerti',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        },
                        buttonsStyling: false
                    });
                    return;
                }

                $.each(response, function(index, item) {
                    var button =
                        '<button class="btn btn-info panggil-pasienPipp" ' +
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
                        item.no_rawat,
                        item.no_rkm_medis,
                        item.nm_pasien,
                        // item.nm_poli, // Kalau ingin ditampilkan, hilangkan tanda komentar ini
                        button
                    ]).draw(false);
                });
            },
            error: function(xhr, status, error) {
                console.error("Error fetching patients: ", error);
            }
        });

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Tangani klik tombol Panggil
        $('#fixed-header-datatable tbody').on('click', '.panggil-pasienPipp', function() {
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
                url: '{{ route("pelayanan.petugasPanggil.pipp.pippPanggil.panggilPipp") }}',
                method: 'POST',
                data: {
                    nama: namaPasien,
                    poli: poli,
                    dokter: dokter,
                    no_reg: reg,
                    noRawat: noRawat,
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
    });
</script>
