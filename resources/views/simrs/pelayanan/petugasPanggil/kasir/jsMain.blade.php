<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script>
    $(document).ready(function() {
        var table = $('#fixed-header-datatable').DataTable({
            destroy: true,
            searching: true,
            ordering: true,
            paging: true,
            responsive: true
        });

        let alertShown = false;
        $('#jenis-rawat').val('');

        function pilihJenisRawatAlert() {
            Swal.fire({
                icon: 'info',
                title: 'Pilih Jenis Rawat',
                html: `
                <select id="swal-jenis-rawat" class="form-select">
                    <option value="">-- Pilih Jenis Rawat --</option>
                    <option value="Rawat Jalan">Rawat Jalan</option>
                    <option value="Rawat Inap">Rawat Inap</option>
                </select>
            `,
                confirmButtonText: 'Lanjutkan',
                allowOutsideClick: false,
                allowEscapeKey: false,
                preConfirm: () => {
                    const jenisRawat = document.getElementById('swal-jenis-rawat').value;
                    if (!jenisRawat) {
                        Swal.showValidationMessage('Jenis Rawat wajib dipilih');
                    }
                    return jenisRawat;
                }
            }).then((result) => {
                if (result.isConfirmed) {

                    // set dropdown utama
                    $('#jenis-rawat').val(result.value);

                    // ambil date range (kalau ada)
                    let startDate = '';
                    let endDate = '';

                    if ($('#date-range').val()) {
                        const dr = $('#date-range').val().split(' - ');
                        startDate = dr[0] || '';
                        endDate = dr[1] || '';
                    }

                    // 🔥 PANGGIL LANGSUNG
                    loadData(startDate, endDate, result.value);
                }
            });
        }

        function loadData(startDate = '', endDate = '', jenisRawat = '') {

            if (!jenisRawat) {
                table.clear().draw();
                return;
            }

            alertShown = false;

            $.ajax({
                url: '{{ route("pelayanan.petugasPanggil.kasir.getDataPasien") }}',
                method: 'GET',
                data: {
                    start_date: startDate,
                    end_date: endDate,
                    jenis_rawat: jenisRawat
                },
                dataType: 'json',
                success: function(response) {
                    table.clear().draw();

                    if (!response || response.length === 0) {
                        table.draw();
                        return;
                    }

                    $.each(response, function(index, item) {
                        var button =
                            `<button class="btn btn-info panggil-pasienkasir"
                                data-nama="${item.nm_pasien}"
                                data-poli="${item.nm_poli}"
                                data-dokter="${item.nm_dokter}"
                                data-tgl="${item.tgl_lahir}"
                                data-alamat="${item.alamat}"
                                data-jk="${item.jk}"
                                data-rm="${item.no_rkm_medis}"
                                data-rawat="${item.no_rawat}"
                                data-reg="${item.no_reg}">
                                '<i class="ri-volume-up-line me-1"></i> Panggil</button>`;

                        table.row.add([
                            item.no_rawat,
                            item.no_rkm_medis,
                            item.nm_pasien,
                            button
                        ]);
                    });

                    table.draw(false);
                },
                error: function(xhr) {
                    console.error('AJAX Error:', xhr.responseText);
                }
            });
        }

        // Default Load Data
        pilihJenisRawatAlert();

        // Date Range Picker
        if ($.fn.daterangepicker) {
            $("#date-range").daterangepicker({
                locale: {
                    format: "YYYY-MM-DD"
                },
                autoUpdateInput: true
            });
        } else {
            console.error("❌ DateRangePicker belum ter-load");
        }


        // Tangani perubahan pada filter
        $('#date-range, #jenis-rawat').on('change', function() {

            var jenisRawat = $('#jenis-rawat').val();
            if (!jenisRawat) return;

            let startDate = '';
            let endDate = '';

            const dateVal = $('#date-range').val();
            if (dateVal && dateVal.includes(' - ')) {
                const dr = dateVal.split(' - ');
                startDate = dr[0];
                endDate = dr[1];
            }

            loadData(startDate, endDate, jenisRawat);
        });


        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Tangani klik tombol Panggil
        $('#fixed-header-datatable tbody').on('click', '.panggil-pasienkasir', function() {
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
                url: '{{ route("pelayanan.petugasPanggil.kasir.panggilKasir") }}',
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
