<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | {{ config('app.name', 'Digital Queue System') }}</title>

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
                            <h3 class="mt-3">Welcome Back</h3>
                            <p class="mb-4">
                                Login to access your dashboard, manage service requests,
                                and track your appointments.
                            </p>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="bi bi-check-circle me-2"></i> Digital queue system</li>
                                <li class="mb-2"><i class="bi bi-check-circle me-2"></i> Appointment scheduling</li>
                                <li><i class="bi bi-check-circle me-2"></i> Real-time updates</li>
                            </ul>
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

                            <!-- Login Form -->
                            <form method="POST" action="{{ route('login') }}">
                                @csrf

                                <!-- Email -->
                                <div class="mb-3">
                                    <label class="form-label">Email or Student Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                        <input id="login" type="text"
                                            class="form-control @error('login') is-invalid @enderror"
                                            name="login"
                                            value="{{ old('login') }}"
                                            required autofocus>
                                        @error('login')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Password -->
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input type="password" id="password" name="password" class="form-control" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Remember -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                        <label class="form-check-label" for="remember">
                                            Remember me
                                        </label>
                                    </div>

                                    <a href="{{ route('password.request') }}" class="text-decoration-none">
                                        Forgot Password?
                                    </a>
                                </div>

                                <!-- Submit -->
                                <button type="submit" class="btn btn-primary w-100">
                                    Login
                                </button>
                            </form>

                            <!-- Register -->
                            <p class="text-center mt-4 mb-0">
                                Don’t have an account?
                                <a href="{{ route('register') }}" class="fw-semibold text-decoration-none">
                                    Register
                                </a>
                            </p>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        const togglePasswordIcon = togglePassword.querySelector('i');

        togglePassword.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            togglePasswordIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    </script>
</body>

</html>
