<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
<!-- End fonts -->

<!-- Core CSS (Bootstrap, basic styles) -->
<link rel="stylesheet" href="{{ asset("assets/vendors/core/core.css") }}">

<!-- Plugin CSS (Dropify only) -->
<link rel="stylesheet" href="{{ asset("assets/vendors/dropify/dist/dropify.min.css") }}">

<!-- Feather Icons (untuk ikon tombol/modal) -->
<link rel="stylesheet" href="{{ asset("assets/fonts/feather-font/css/iconfont.css") }}">

<!-- Layout styles -->
<link rel="stylesheet" href="{{ asset("assets/css/demo1/style.css") }}">

<!-- Favicon -->
<link rel="shortcut icon" href="{{ asset("assets/images/favicon.png") }}" />

@push("scripts")
    <!-- Plugin js for Dropify -->
    <script src="{{ asset("assets/vendors/dropify/dist/dropify.min.js") }}"></script>
    <!-- Custom js for Dropify Initialization -->
    <script src="{{ asset("assets/js/dropify.js") }}"></script>
@endpush
