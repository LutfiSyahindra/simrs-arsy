<!DOCTYPE html>
<html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>RS Abdurrahman Syamsuri - Anjungan Mandiri Pasien</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://js.pusher.com/8.0/pusher.min.js"></script>
        <!-- Vendor js -->
        <!-- core:js -->
        <script src="{{ asset("assets/vendors/core/core.js") }}"></script>
        <!-- endinject -->

        <!-- Plugin js for this page -->
        <script src="{{ asset("assets/vendors/flatpickr/flatpickr.min.js") }}"></script>
        <script src="{{ asset("assets/vendors/apexcharts/apexcharts.min.js") }}"></script>
        <!-- End plugin js for this page -->

        <!-- inject:js -->
        <script src="{{ asset("assets/vendors/feather-icons/feather.min.js") }}"></script>
        <script src="{{ asset("assets/js/template.js") }}"></script>
        <!-- endinject -->

        <!-- Custom js for this page -->
        <script src="{{ asset("assets/js/dashboard-light.js") }}"></script>
        <!-- End custom js for this page -->

        {{-- Script Head --}}
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        {{-- Custom Style --}}
        {{-- @include("SIMRS.anjungan.jkn.main.style") --}}
        {{-- @include("SIMRS.anjungan.jkn.rujukanBaru.style") --}}
        <style>
            body {
                background-color: #e0e0e0;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                height: 100vh;
                transition: background 0.5s;
            }

            .header {
                background: linear-gradient(135deg, #2b8beb, #234d72);
                color: white;
                padding: 20px;
                border-radius: 10px;
                text-align: center;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                position: fixed;
                top: 0;
                width: 100%;
                left: 0;
                z-index: 1000;
                animation: fadeInDown 1s;
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 15px;
            }

            .header h1 {
                font-size: 28px;
                font-weight: bold;
                margin: 0;
                text-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
            }

            .container-custom {
                max-width: 800px;
                margin-top: 80px;
            }

            .menu-container {
                display: flex;
                justify-content: center;
                gap: 20px;
            }

            .menu-box {
                background: white;
                border-radius: 15px;
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
                padding: 30px;
                text-align: center;
                transition: transform 0.3s, box-shadow 0.3s;
                cursor: pointer;
                font-size: 20px;
                width: 250px;
                position: relative;
                overflow: hidden;
                animation: fadeInUp 1s;
            }

            .menu-box:hover {
                transform: translateY(-8px);
                box-shadow: 0 8px 15px rgba(0, 0, 0, 0.2);
            }

            .menu-box:active {
                transform: scale(0.95);
            }

            .menu-icon {
                font-size: 50px;
                margin-bottom: 15px;
                color: #007bff;
            }

            .animate-ticket {
                animation: bounce 1.5s infinite;
            }

            .animate-number {
                animation: fadeIn 1.5s ease-in-out;
            }

            .menu-icon-img {
                width: 85px;
                height: 85px;
                padding: 8px;
                background: #ffffff;
                border-radius: 50%;
                object-fit: contain;
                margin-bottom: 15px;
            }

            @keyframes bounce {

                0%,
                100% {
                    transform: translateY(0);
                }

                50% {
                    transform: translateY(-10px);
                }
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: scale(0.5);
                }

                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }

            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes fadeInDown {
                from {
                    opacity: 0;
                    transform: translateY(-20px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
        </style>
    </head>

    <body>
        @include("simrs.pelayanan.anjungan.admisi.modalAdmisi")
        {{-- @include("SIMRS.anjungan.antrianFarmasi.modalFarmasi") --}}
        {{-- @include("SIMRS.anjungan.jkn.main.modalJknMain") --}}
        {{-- @include("SIMRS.anjungan.jkn.rujukanBaru.modalRujukanBaru") --}}
        {{-- @include("SIMRS.anjungan.jkn.kontrolPoli.modalKontrolPoli") --}}
        {{-- @include("SIMRS.anjungan.jkn.rujukInternal.modalRujukInternal") --}}

        <div class="header">
            <i class="fas fa-hospital fa-2x"></i>
            <h1>RS Abdurrahman Syamsuri - Anjungan Mandiri Pasien</h1>
        </div>
        <div class="container text-center container-custom">
            <div class="menu-container">
                <div class="menu-box" data-bs-toggle="modal" data-bs-target="#exampleModal">
                    <i class="menu-icon fas fa-user-plus"></i>
                    <h5>Antrian Admisi</h5>
                </div>
                <div class="menu-box" data-bs-toggle="modal" data-bs-target="#farmasiModal">
                    <i class="menu-icon fas fa-list"></i>
                    <h5>Antrian Farmasi</h5>
                </div>
                <div class="menu-box" data-bs-toggle="modal" data-bs-target="#jknMainModal">
                    <img src="{{ asset("plugins/img/jkn.png") }}" alt="Logo JKN" class="menu-icon-img">
                    <h5>Pasien JKN</h5>
                </div>

            </div>
        </div>

        @include("simrs.pelayanan.anjungan.admisi.jsMain")
        {{-- @include("SIMRS.anjungan.antrianFarmasi.js") --}}
        {{-- @include("SIMRS.anjungan.jkn.main.js") --}}
        {{-- @include("SIMRS.anjungan.jkn.rujukanBaru.js") --}}
        {{-- @include("SIMRS.anjungan.jkn.kontrolPoli.js") --}}
        {{-- @include("SIMRS.anjungan.jkn.rujukInternal.js") --}}

    </body>

</html>
