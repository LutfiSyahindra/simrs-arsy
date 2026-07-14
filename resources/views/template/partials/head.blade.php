<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="description" content="{{ config("app.name", "SIMRS Arsy") }}">
    <meta name="author" content="{{ config("app.name", "SIMRS Arsy") }}">
    <meta name="keywords" content="simrs, arsy, rumah sakit, dashboard, pelayanan, administrasi">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@hasSection("title")@yield("title") - @endif{{ config("app.name", "SIMRS Arsy") }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <!-- End fonts -->

    <!-- core:css -->
    <link rel="stylesheet" href="{{ asset("assets/vendors/core/core.css") }}">
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

    <link rel="icon" type="image/png" href="{{ asset("plugins/img/logoarsy.png") }}">
    <link rel="apple-touch-icon" href="{{ asset("plugins/img/logoarsy.png") }}">
    @vite(['resources/js/app.js'])
</head>
