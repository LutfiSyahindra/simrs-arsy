<script>
    $(document).ready(function() {

        // ===============================
        // 🧹 Helper: Bersihkan Modal Bootstrap
        // ===============================
        function bersihkanModalBootstrap() {
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
            $('body').css('overflow', 'auto');
        }

        // ===============================
        // 🎫 Ambil Nomor Antrian Admisi
        // ===============================
        $("#ambilNomorAdmisi").on("click", function() {

            $.ajax({
                url: '{{ route("pelayanan.display.anjungan.generateAntrianAdmisi") }}',
                type: "POST",
                data: {
                    _token: '{{ csrf_token() }}'
                },

                success: function(response) {
                    console.log('RESP', response);

                    if (response.status !== "success") {
                        Swal.fire("Gagal", "Gagal mengambil nomor antrian", "error");
                        return;
                    }

                    // ===============================
                    // 🔢 Ambil nomor antrian (AMAN)
                    // ===============================
                    var nomorObj = response.nomor_antrian || {};
                    var nomor = nomorObj.no_antrian ?? nomorObj.nomor_antrian ?? nomorObj;

                    // ===============================
                    // 🔔 Tampilkan Nomor Antrian
                    // ===============================
                    Swal.fire({
                        title: "Nomor Antrian Anda",
                        html: `<h1 style="font-size:48px">${nomor}</h1>`,
                        icon: "success",
                        confirmButtonText: "Cetak Tiket",
                        allowOutsideClick: false
                    }).then(() => {

                        // 🔒 Tutup modal Bootstrap SEBELUM cetak
                        $("#exampleModal").modal("hide");

                        // 🧹 Pastikan backdrop bersih
                        setTimeout(() => {
                            bersihkanModalBootstrap();
                        }, 300);

                        // ===============================
                        // 🖨️ Cetak Tiket
                        // ===============================
                        let urlCetak =
                            '{{ url("simrs/pelayanan/display/display/anjungan/admisi/cetakAntrian") }}/' +
                            nomor;

                        let printWindow = window.open(urlCetak, "_blank");

                        if (!printWindow) {
                            Swal.fire(
                                "Popup diblokir",
                                "Izinkan pop-up untuk mencetak tiket",
                                "warning"
                            );
                            return;
                        }


                    });
                },

                error: function(xhr) {
                    console.error(xhr);
                    Swal.fire("Kesalahan", "Coba lagi nanti", "error");

                    // jaga-jaga kalau modal nyangkut
                    bersihkanModalBootstrap();
                }
            });
        });

    });
</script>
