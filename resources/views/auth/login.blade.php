<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">

        <title>Login - {{ config("app.name", "SIMRS Arsy") }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap"
            rel="stylesheet">

        <!-- core:css -->
        <link rel="stylesheet" href="{{ asset("assets/vendors/core/core.css") }}">
        <link rel="stylesheet" href="{{ asset("assets/fonts/feather-font/css/iconfont.css") }}">
        <link rel="stylesheet" href="{{ asset("assets/vendors/flag-icon-css/css/flag-icon.min.css") }}">
        <link rel="stylesheet" href="{{ asset("assets/css/demo1/style.css") }}">
        <link rel="icon" type="image/png" href="{{ asset("plugins/img/logoarsy.png") }}">
        <link rel="apple-touch-icon" href="{{ asset("plugins/img/logoarsy.png") }}">
        <style>
            .login-brand {
                align-items: center;
                display: flex;
                gap: 14px;
                margin-bottom: 18px;
            }

            .login-brand-logo {
                background: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 10px;
                box-shadow: 0 8px 20px rgba(15, 23, 42, .08);
                height: 72px;
                object-fit: contain;
                padding: 8px;
                width: 72px;
            }

            .login-brand-title {
                color: #1f2937;
                font-size: 24px;
                font-weight: 800;
                line-height: 1.1;
                margin: 0;
            }

            .login-brand-subtitle {
                color: #64748b;
                font-size: 13px;
                font-weight: 500;
                margin-top: 4px;
            }
        </style>
    </head>

    <body>
        <div class="main-wrapper">
            <div class="page-wrapper full-page">
                <div class="page-content d-flex align-items-center justify-content-center">

                    <div class="row w-100 mx-0 auth-page">
                        <div class="col-md-8 col-xl-6 mx-auto">
                            <div class="card">
                                <div class="row">
                                    <div class="col-md-4 pe-md-0">
                                        <div class="auth-side-wrapper">
                                        </div>
                                    </div>
                                    <div class="col-md-8 ps-md-0">
                                        <div class="auth-form-wrapper px-4 py-5">
                                            <div class="login-brand">
                                                <img src="{{ asset("plugins/img/logoarsy.png") }}"
                                                    alt="{{ config("app.name", "SIMRS Arsy") }}"
                                                    class="login-brand-logo">
                                                <div>
                                                    <h1 class="login-brand-title">SIMRS Arsy</h1>
                                                    <div class="login-brand-subtitle">RS Abdurrahman Syamsuri</div>
                                                </div>
                                            </div>
                                            <h5 class="text-muted fw-normal mb-4">Welcome back! Log in to your account.
                                            </h5>

                                            <!-- Laravel Login Form -->
                                            <form method="POST" action="{{ route("login") }}">
                                                @csrf

                                                <!-- Email -->
                                                <div class="mb-3">
                                                    <label for="email" class="form-label">Email address</label>
                                                    <input type="email"
                                                        class="form-control @error("email") is-invalid @enderror"
                                                        id="email" name="email" value="{{ old("email") }}"
                                                        required autofocus>
                                                    @error("email")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <!-- Password -->
                                                <div class="mb-3">
                                                    <label for="password" class="form-label">Password</label>
                                                    <input type="password"
                                                        class="form-control @error("password") is-invalid @enderror"
                                                        id="password" name="password" required
                                                        autocomplete="current-password">
                                                    @error("password")
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>

                                                <!-- Remember me -->
                                                <div class="form-check mb-3">
                                                    <input type="checkbox" class="form-check-input" id="remember_me"
                                                        name="remember">
                                                    <label class="form-check-label" for="remember_me">Remember
                                                        me</label>
                                                </div>

                                                <!-- Buttons -->
                                                <div class="d-flex align-items-center">
                                                    <button type="submit"
                                                        class="btn btn-primary me-2 mb-2 mb-md-0">Login</button>
                                                </div>
                                            </form>
                                            <!-- End Form -->

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- core:js -->
        <script src="{{ asset("assets/vendors/core/core.js") }}"></script>
        <script src="{{ asset("assets/vendors/feather-icons/feather.min.js") }}"></script>
        <script src="{{ asset("assets/js/template.js") }}"></script>
    </body>

</html>
