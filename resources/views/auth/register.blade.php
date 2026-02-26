<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | {{ config('app.name', 'Digital Queue System') }}</title>

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

        .register-card {
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, .2);
            overflow: hidden;
        }

        .register-left {
            background: linear-gradient(135deg, #00225A, #6f86ff);
            color: #fff;
            padding: 40px;
        }

        .register-right {
            padding: 40px;
        }

        .form-control,
        .form-select {
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

        @media (max-width: 768px) {
            .register-left {
                display: none;
            }
        }

        @media (max-width: 576px) {
            body {
                align-items: flex-start;
                padding: 1rem 0;
            }

            .register-card {
                border-radius: 14px;
            }

            .register-right {
                padding: 1.1rem;
            }

            .register-right .input-group {
                flex-wrap: nowrap;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card register-card">
                    <div class="row g-0">

                        <!-- LEFT -->
                        <div class="col-md-5 register-left d-flex flex-column justify-content-center">
                            <h3 class="mt-3">Create Account</h3>
                            <p class="mb-4">
                                Join the Digital Queue System to submit requests,
                                book appointments, and track service progress.
                            </p>
                            <ul class="list-unstyled">
                                <li class="mb-2"><i class="bi bi-check-circle me-2"></i> Students & Staff access</li>
                                <li class="mb-2"><i class="bi bi-check-circle me-2"></i> Faster service handling</li>
                                <li><i class="bi bi-check-circle me-2"></i> Real-time updates</li>
                            </ul>
                        </div>

                        <!-- RIGHT -->
                        <div class="col-md-7 register-right">
                            <div class="d-flex justify-content-center align-items-center">
                                <a class="navbar-brand fw-bold mb-4" href="#">
                                    <img src="{{ asset('logo_unilak.jfif') }}"
                                        alt="Digital Queue Logo"
                                        width="40" height="40">
                                </a>
                            </div>

                            <!-- Errors -->
                            @if ($errors->any())
                            <div class="alert alert-danger small">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            <form method="POST" action="{{ route('register') }}">
                                @csrf

                                <!-- Name -->
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>

                                <!-- Email -->
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>

                                <!-- Email -->
                                <div class="mb-3">
                                    <label class="form-label">Reg Number</label>
                                    <input type="text" name="student_number" class="form-control" required>
                                </div>

                                <!-- Password -->
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <input type="password" id="password" name="password" class="form-control" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Confirm Password -->
                                <div class="mb-4">
                                    <label class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirmation">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    Create Account
                                </button>
                            </form>

                            <p class="text-center mt-4 mb-0">
                                Already have an account?
                                <a href="{{ route('login') }}" class="fw-semibold text-decoration-none">
                                    Login
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
        function setupPasswordToggle(inputId, buttonId) {
            const input = document.getElementById(inputId);
            const button = document.getElementById(buttonId);
            const icon = button.querySelector('i');

            button.addEventListener('click', function () {
                const isHidden = input.type === 'password';
                input.type = isHidden ? 'text' : 'password';
                icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        }

        setupPasswordToggle('password', 'togglePassword');
        setupPasswordToggle('password_confirmation', 'togglePasswordConfirmation');
    </script>
</body>

</html>
