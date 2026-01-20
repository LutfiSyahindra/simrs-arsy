<script src="https://js.pusher.com/8.0/pusher.min.js"></script>
<script>
    $(document).ready(function() {
        const modalEl = document.getElementById('info-header-modal');
        const modalLoket = new bootstrap.Modal(modalEl);
        const selectLoket = document.getElementById('select-loket');
        let pusher = null;
        let channel = null;
        let loketSaya = null; // Menyimpan nama loket aktif
        let idLoketAktif = null; // Menyimpan ID loket aktif

        // === Ambil daftar loket dari server ===
        function loadLoket() {
            $.get('{{ route("loket.admisi.getAllLoket") }}', function(response) {
                console.log(response);
                selectLoket.innerHTML = '<option value="">-- Pilih Loket --</option>';

                if (response.length === 0) {
                    const opt = document.createElement('option');
                    opt.textContent = 'Semua loket sedang digunakan';
                    opt.disabled = true;
                    selectLoket.appendChild(opt);
                } else {
                    response.forEach(l => {
                        const opt = document.createElement('option');
                        opt.value = l.id;
                        opt.textContent = l.nama;
                        opt.dataset.nama = l.nama;
                        selectLoket.appendChild(opt);
                    });
                }

                modalLoket.show();
            }).fail(function() {
                Swal.fire('Error', 'Gagal mengambil daftar loket dari server.', 'error');
            });
        }

        // === Cek apakah masih ada loket aktif dari server ===
        function cekLoketAktif() {
            $.get('{{ route("loket.admisi.getLoketAktif") }}', function(response) {
                if (response.status === 'success') {
                    loketSaya = response.loket.nama;
                    idLoketAktif = response.loket.id;
                    console.log('Masih di loket:', loketSaya);

                    // 🔹 Sembunyikan tombol buka loket
                    $('#btn-buka-loket').hide();
                    // 🔹 Tampilkan tombol tutup loket
                    $('#btn-tutup-loket').show();

                    loadDataAntrian(); // langsung load data tanpa buka modal
                    initPusherListener(); // aktifkan listener pusher
                } else {
                    // 🔹 Jika belum pilih loket, tampilkan tombol buka loket
                    $('#btn-buka-loket').show();
                    // 🔹 Sembunyikan tombol tutup loket
                    $('#btn-tutup-loket').hide();

                    loadLoket();
                }
            });
        }

        // === Saat pilih loket ===
        $('#select-loket').on('change', function() {
            const idLoket = $(this).val();
            const namaLoket = $(this).find(':selected').data('nama');
            if (!idLoket) return;

            $.ajax({
                url: '{{ route("loket.admisi.lockLoket") }}',
                type: 'POST',
                data: {
                    id: idLoket
                },
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire('Berhasil', response.message, 'success');
                        idLoketAktif = idLoket;
                        loketSaya = namaLoket;
                        modalLoket.hide();
                        loadDataAntrian();
                        initPusherListener(); // aktifkan listener real-time
                    } else {
                        Swal.fire('Gagal', response.message, 'error');
                        loadLoket();
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Terjadi kesalahan saat mengunci loket.', 'error');
                }
            });
        });

        // === Ambil data antrian sesuai loket ===
        function loadDataAntrian() {
            if (!loketSaya) {
                loadLoket();
                return;
            }

            $.get('{{ route("petugasPanggil.admisi.admisiPanggil.dataPasien") }}', {
                loket: loketSaya
            }, function(response) {
                table.clear().draw();

                $.each(response, function(i, item) {
                    const statusBadge = item.status_panggil === 'Sudah' ?
                        `<span class="badge bg-success">Sudah</span>` :
                        `<span class="badge bg-warning text-dark">${item.status_panggil ?? 'Belum'}</span>`;
                    const btnPanggil = `
                        <button class="btn btn-info panggil-admisi" 
                        data-id="${item.id}" 
                        data-antrian="${item.no_antrian}" data-status="${item.status_panggil}">
                        <i class="bi bi-soundwave"></i> Panggil
                    </button>`;

                    table.row.add([
                        item.no_antrian ?? '-',
                        statusBadge,
                        item.loket ?? '-',
                        btnPanggil
                    ]);
                });
                // 🔹 Setelah tutup, tampilkan tombol buka, sembunyikan tutup
                $('#btn-buka-loket').hide();
                $('#btn-tutup-loket').show();
                table.draw(false);
            }).fail(function() {
                Swal.fire('Error', 'Gagal memuat data antrian.', 'error');
            });
        }

        // === Tutup Loket ===
        $('#btn-tutup-loket').on('click', function() {
            if (!idLoketAktif) {
                Swal.fire('Belum ada loket aktif', 'Silakan buka loket terlebih dahulu', 'info');
                return;
            }

            Swal.fire({
                title: 'Tutup Loket?',
                text: 'Apakah Anda yakin ingin menutup loket ' + loketSaya + '?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Tutup',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '{{ route("loket.admisi.unlockLoket") }}',
                        type: 'POST',
                        data: {
                            id: idLoketAktif
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire('Berhasil', response.message, 'success');

                                // 🔹 Reset variabel loket aktif
                                loketSaya = null;
                                idLoketAktif = null;

                                // 🔹 Bersihkan tabel antrian
                                if (table) {
                                    table.clear().draw();
                                }

                                // 🔹 Matikan listener Pusher
                                destroyPusherListener();

                                // 🔹 Setelah tutup, tampilkan tombol buka, sembunyikan tutup
                                $('#btn-buka-loket').show();
                                $('#btn-tutup-loket').hide();

                                // 🔹 Munculkan kembali modal pilih loket
                                loadLoket();
                            } else {
                                Swal.fire('Gagal', response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Gagal menutup loket.', 'error');
                        }
                    });
                }
            });
        });

        // === Setup DataTable ===
        var table = $('#fixed-header-datatable').DataTable({
            destroy: true,
            searching: true,
            ordering: true,
            paging: true,
            responsive: true,
            columns: [{
                    title: "No Antrian"
                },
                {
                    title: "Status"
                },
                {
                    title: "Loket"
                },
                {
                    title: "Aksi",
                    orderable: false,
                    searchable: false
                }
            ]
        });

        // === Setup CSRF ===
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // === Inisialisasi Pusher Listener ===
        function initPusherListener() {
            console.log("🚀 Pusher listener diaktifkan...");

            // Cegah listener ganda
            if (channel && pusher) {
                console.log("⚠️ Listener sudah aktif, lewati...");
                return;
            }

            // Inisialisasi Pusher
            pusher = new Pusher("{{ env("PUSHER_APP_KEY") }}", {
                cluster: "{{ env("PUSHER_APP_CLUSTER") }}",
                forceTLS: true
            });

            // Subscribe ke channel
            channel = pusher.subscribe("antrian-admisi");

            // Hapus binding lama kalau ada
            channel.unbind("antrianAdmisi.created");

            // Bind event baru dari server
            channel.bind("antrianAdmisi.created", function(data) {
                console.log("📡 Event diterima dari Pusher:", data);

                // Pastikan data valid
                const item = data?.antrian || {};
                if (!item.id || !item.no_antrian) {
                    console.warn("⚠️ Event diabaikan karena tidak ada ID atau nomor antrian:", item);
                    return;
                }

                // Cek apakah data dengan nomor antrian sama sudah ada di tabel
                let sudahAda = false;
                table.rows().every(function() {
                    const row = this.data();
                    if (row[0] == item.no_antrian) {
                        sudahAda = true;
                        this.remove(); // hapus baris lama supaya tidak duplikat
                    }
                });

                // Tambahkan tombol panggil
                const statusBadge = item.status_panggil === 'Sudah' ?
                    `<span class="badge bg-success">Sudah</span>` :
                    `<span class="badge bg-warning text-dark">${item.status_panggil ?? 'Belum'}</span>`;
                const btnPanggil = `
                        <button class="btn btn-info panggil-admisi" 
                        data-id="${item.id}" 
                        data-antrian="${item.no_antrian}"
                        data-status="${item.status_panggil}">
                        <i class="bi bi-soundwave"></i> Panggil
                    </button>`;

                // Tambahkan ke DataTable
                table.row.add([
                    item.no_antrian ?? "-",
                    statusBadge,
                    item.loket ?? "-",
                    btnPanggil
                ]).draw(false);

                console.log(
                    `✅ ${sudahAda ? 'Perbarui' : 'Tambah'} antrian ${item.no_antrian} (ID: ${item.id})`
                );

                // Notifikasi popup
                Swal.fire({
                    title: "Antrian Baru!",
                    text: `Nomor antrian baru ${item.no_antrian}.`,
                    icon: "info",
                    timer: 2000,
                    showConfirmButton: false
                });
            });

            // Log koneksi
            pusher.connection.bind("connected", () => console.log("🟢 Pusher terkoneksi"));
            pusher.connection.bind("disconnected", () => console.warn("🔴 Pusher terputus"));
        }

        // === Tangani klik tombol Panggil ===
        $('#fixed-header-datatable tbody').on('click', '.panggil-admisi', function() {
            var antrian = $(this).data('antrian');
            var loket = loketSaya; // pakai loket aktif dari variabel global
            var id = $(this).data('id');
            var status = $(this).data('status');

            // 🔹 Tampilkan di card body
            $('#detail-nomor').text(antrian);
            $('#detail-loket').text(loket);

            // 🔹 AJAX kirim data panggilan ke backend
            $.ajax({
                url: '{{ route("petugasPanggil.admisi.admisiPanggil.panggilAdmisi") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    no_antrian: antrian,
                    loket: loket,
                    id: id,
                    status: status
                },
                success: function(response) {
                    if (response.status === 'success') {
                        console.log(response.message);

                        // 🔹 Notifikasi sukses
                        Swal.fire({
                            title: 'Memanggil Pasien...',
                            text: 'Nomor antrian ' + antrian + ' di ' + loket,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // 🔹 Update tabel (misalnya ubah status jadi "Dipanggil")
                        loadDataAntrian();
                    } else {
                        Swal.fire('Gagal', response.message || 'Gagal memanggil pasien',
                            'error');
                    }
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    Swal.fire('Error', 'Terjadi kesalahan saat memanggil pasien', 'error');
                }
            });
        });

        // === Hentikan Pusher Listener ===
        function destroyPusherListener() {
            if (channel && pusher) {
                console.log("Pusher listener dimatikan...");
                channel.unbind('antrianAdmisi.created'); // hapus listener
                pusher.unsubscribe('antrian-admisi'); // keluar dari channel
                channel = null;
                pusher = null;
            }
        }

        // === Pusher listener untuk pemanggilan pasien ===
        function initPanggilAdmisiListener() {
            console.log("🎧 Mendengarkan channel panggilan-admisi...");

            const pusherPanggil = new Pusher("{{ env("PUSHER_APP_KEY") }}", {
                cluster: "{{ env("PUSHER_APP_CLUSTER") }}",
                forceTLS: true
            });

            const channelPanggil = pusherPanggil.subscribe("panggilan-admisi");

            // Tangkap event 'PanggilAdmisi'
            channelPanggil.bind("PanggilAdmisi", function(data) {
                console.log("📢 Pasien dipanggil:", data);

                const nomor = data.nomorAntrian;
                const loket = data.loket;

                // 🔹 Cari baris dengan nomor antrian yang sama
                table.rows().every(function() {
                    const rowData = this.data();
                    if (rowData[0] == nomor) {
                        // Ubah status jadi Sudah (hijau)
                        const statusBadge = `<span class="badge bg-success">Sudah</span>`;
                        rowData[1] = statusBadge;
                        rowData[2] = loket ?? '-';

                        // Perbarui baris di DataTable
                        this.data(rowData).invalidate().draw(false);
                    }
                });

            });
        }


        // === Jalankan pertama kali ===
        cekLoketAktif();
        initPusherListener(); // untuk antrian baru
        initPanggilAdmisiListener(); // untuk pemanggilan pasien

    });
</script>
