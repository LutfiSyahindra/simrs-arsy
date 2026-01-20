<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="description" content="Responsive HTML Admin Dashboard Template based on Bootstrap 5">
        <meta name="author" content="NobleUI">
        <meta name="keywords"
            content="nobleui, bootstrap, bootstrap 5, bootstrap5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Display Antrian</title>

        <!-- core:css -->
        <link rel="stylesheet" href="{{ asset("assets/vendors/core/core.css") }}">
        <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
        <!-- endinject -->

        <!-- Plugin css for this page -->
        <link rel="stylesheet" href="{{ asset("assets/vendors/flatpickr/flatpickr.min.css") }}">
        <!-- End plugin css for this page -->

        <!-- inject:css -->
        <link rel="stylesheet" href="{{ asset("assets/fonts/feather-font/css/iconfont.css") }}">
        <link rel="stylesheet" href="{{ asset("assets/vendors/flag-icon-css/css/flag-icon.min.css") }}">
        <!-- endinject -->

        <!-- Layout styles -->
        <link rel="stylesheet" href="{{ asset("assets/css/demo1/style.css") }}">
        <!-- End layout styles -->

        <link rel="shortcut icon" href="{{ asset("assets/images/favicon.png") }}" />
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://js.pusher.com/8.0/pusher.min.js"></script>

        <style>
            body {
                font-family: 'Poppins', sans-serif;
                margin: 0;
                padding: 0;
                height: 100vh;
                background: linear-gradient(180deg, #e8f6f8 0%, #f7f9fa 100%);
                display: flex;
                flex-direction: column;
            }

            .header {
                position: relative;
                background-color: #097c99;
                color: white;
                text-align: center;
                padding: 15px 20px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            }

            .header .logo {
                position: absolute;
                left: 25px;
                top: 50%;
                transform: translateY(-50%);
                width: 85px;
                height: auto;
            }

            .header-text h1 {
                font-size: 2.4rem;
                margin: 0;
                font-weight: 700;
                letter-spacing: 1px;
            }

            .header-text h3 {
                font-size: 1.1rem;
                font-weight: 400;
                margin: 0;
                opacity: 0.9;
            }

            .fullscreen-btn {
                position: absolute;
                top: 15px;
                right: 25px;
                background-color: transparent;
                border: none;
                cursor: pointer;
            }

            .fullscreen-btn i {
                font-size: 36px;
                color: white;
            }

            .main-container {
                flex: 1;
                display: flex;
                justify-content: space-around;
                align-items: center;
                padding: 40px 60px;
                gap: 30px;
            }

            .loket-box {
                flex: 1;
                background: white;
                border-radius: 20px;
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
                padding: 40px 20px;
                text-align: center;
                color: #003366;
                transition: 0.3s;
            }

            .loket-box:hover {
                transform: translateY(-5px);
                box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
            }

            .loket-title {
                font-size: 2.2rem;
                font-weight: 600;
                color: #097c99;
                margin-bottom: 15px;
            }

            .queue-number {
                font-size: 8rem;
                font-weight: 800;
                color: #109191;
                margin: 15px 0;
                text-shadow: 2px 2px 10px rgba(0, 0, 0, 0.15);
            }

            .queue-status {
                font-size: 1.5rem;
                font-weight: 500;
                color: #444;
                margin-top: 10px;
            }

            .footer {
                background: #097c99;
                color: white;
                text-align: center;
                padding: 15px 10px;
                font-size: 1.2rem;
                letter-spacing: 0.5px;
            }

            @media (max-width: 992px) {
                .main-container {
                    flex-direction: column;
                    padding: 20px;
                }

                .loket-box {
                    width: 90%;
                }

                .queue-number {
                    font-size: 5rem;
                }
            }
        </style>
    </head>

    <body>
        <!-- Header -->
        <div class="header">
            <img src="{{ asset("plugins/img/logoarsy.png") }}" alt="Logo RS" class="logo">
            <div class="header-text">
                <h1>DISPLAY ANTRIAN ADMISI</h1>
                <h3>RS ABDURRAHMAN SYAMSYURI</h3>
            </div>
            <button class="fullscreen-btn" id="fullscreen-btn">
                <i class="ri-fullscreen-line"></i>
            </button>
        </div>

        <!-- Main Display -->
        <div class="main-container" id="loket-container">
            <div class="loket-box" id="loket1">
                <div class="loket-title">LOKET 1</div>
                <div class="queue-number" id="nomor-loket1">-</div>
                <div class="queue-status" id="status-loket1">Menunggu panggilan...</div>
            </div>
            <div class="loket-box" id="loket2">
                <div class="loket-title">LOKET 2</div>
                <div class="queue-number" id="nomor-loket2">-</div>
                <div class="queue-status" id="status-loket2">Menunggu panggilan...</div>
            </div>
            <div class="loket-box" id="loket3">
                <div class="loket-title">LOKET 3</div>
                <div class="queue-number" id="nomor-loket3">-</div>
                <div class="queue-status" id="status-loket3">Menunggu panggilan...</div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            Harap menunggu dengan tertib | Terima kasih atas kesabaran Anda 💙
        </div>

        <script>
            $(document).ready(function() {
                const pusher = new Pusher("{{ env("PUSHER_APP_KEY") }}", {
                    cluster: "{{ env("PUSHER_APP_CLUSTER") }}",
                    forceTLS: true
                });

                const channel = pusher.subscribe("panggilan-admisi-V2");
                let isSpeaking = false;
                let isUserInteracted = false;

                // Fullscreen
                $("#fullscreen-btn").on("click", function() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen();
                    } else {
                        document.exitFullscreen();
                    }
                });

                // Aktifkan suara setelah interaksi user pertama
                document.addEventListener("click", function() {
                    isUserInteracted = true;
                    Swal.fire({
                        toast: true,
                        icon: "success",
                        title: "Display aktif menerima panggilan",
                        position: "top-end",
                        timer: 2500,
                        showConfirmButton: false
                    });
                }, {
                    once: true
                });

                // Event dari Pusher
                channel.bind("PanggilAdmisi-V2", function(data) {
                    console.log("📡 Data diterima:", data);
                    if (data && data.nomorAntrian && data.loket) {
                        updateDisplay(data.nomorAntrian, data.loket);
                        if (isUserInteracted) speakQueue(data.nomorAntrian, data.loket);
                    }
                });

                function updateDisplay(nomor, loket) {
                    const idLoket = loket.replace(/\s+/g, '').toLowerCase(); // ex: Loket 1 -> loket1
                    const numberElement = $(`#nomor-${idLoket}`);
                    const statusElement = $(`#status-${idLoket}`);

                    numberElement.text(nomor);
                    statusElement.text(`Sedang dipanggil ke ${loket}`);

                    // Animasi lembut
                    numberElement.fadeOut(100).fadeIn(300);
                    statusElement.fadeOut(100).fadeIn(300);
                }

                function speakQueue(nomor, loket) {
                    if ('speechSynthesis' in window) {
                        const bell = new Audio("{{ asset("plugins/audio/Airport_Bell.mp3") }}");
                        bell.play();

                        bell.onended = function() {
                            const text = `Nomor antrian ${nomor}, silakan ke ${loket}`;
                            const speech = new SpeechSynthesisUtterance(text);
                            speech.lang = "id-ID";
                            speech.rate = 0.9;
                            speech.pitch = 1;
                            window.speechSynthesis.cancel();
                            window.speechSynthesis.speak(speech);
                        };
                    }
                }
            });
        </script>
    </body>

</html>
