<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | {{ config('app.name', 'Digital Queue System') }}</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #00225A, #6f86ff);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }

        .login-card {
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, .2);
            overflow: hidden;
        }

        .login-left {
            background: linear-gradient(135deg, #00225A, #6f86ff);
            color: #fff;
            padding: 40px;
        }

        .login-left h2 {
            font-weight: 700;
        }

        .login-right {
            padding: 40px;
        }

        .form-control {
            border-radius: 12px;
            padding: 12px;
        }

        .btn-primary {
            background-color: #00225A;
            border: none;
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: #3649a8;
        }

        .form-check-input:checked {
            background-color: #00225A;
            border-color: #00225A;
        }

        @media (max-width: 768px) {
            .login-left {
                display: none;
            }
        }

        @media (max-width: 576px) {
            body {
                align-items: flex-start;
                padding: 1rem 0;
            }

            .login-card {
                border-radius: 14px;
            }

            .login-right {
                padding: 1.1rem;
            }

            .login-right .input-group {
                flex-wrap: nowrap;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card login-card">
                    <div class="row g-0">

                        <!-- LEFT SIDE -->
                        <div class="col-md-6 login-left d-flex flex-column justify-content-center">
                            <h3 class="mt-3">Forgot Password</h3>
                            <p class=" mb-5">{{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}</p>
                        </div>

                        <!-- RIGHT SIDE -->
                        <div class="col-md-6 login-right">
                           <div class="d-flex justify-content-center align-items-center">
                                <a class="navbar-brand fw-bold mb-4" href="#">
                                    <img src="{{ asset('logo_unilak.jfif') }}"
                                        alt="Digital Queue Logo"
                                        width="40" height="40">
                                </a>
                            </div>

                            <!-- Validation Errors -->
                            @if ($errors->any())
                            <div class="alert alert-danger small">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                            <form method="POST" action="{{ route('password.email') }}">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Email or Student Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input id="email" type="text"
                                            class="form-control @error('email') is-invalid @enderror"
                                            name="email"
                                            value="{{ old('email') }}"
                                            required autofocus>
                                        @error('email')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Send</button>
                            </form>
                            <p class='text-center mt-4 mb-0'>
                                Remember your account? <a href="{{ route('login') }}" class="font-bold">Log in</a>.
                            </p>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
