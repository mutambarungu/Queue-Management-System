<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University Digital Queue System</title>

    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .hero {
            background: linear-gradient(135deg, #00225A, #6f86ff);
            color: #fff;
            padding: 90px 0;
        }

        .hero h1 {
            font-weight: 700;
        }

        .feature-icon {
            width: 55px;
            height: 55px;
            border-radius: 12px;
            background: #00225A;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .08);
        }

        .step-number {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #00225A;
            color: #fff;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        footer {
            background: #f8f9fa;
        }

        @media (max-width: 767.98px) {
            .hero {
                padding: 64px 0;
            }

            .hero h1 {
                font-size: 1.75rem;
            }

            .hero .btn {
                width: 100%;
                margin: 0.35rem 0;
            }

            .navbar .ms-auto {
                display: flex;
                flex-direction: column;
                width: 100%;
                margin-top: 0.75rem;
                gap: 0.5rem;
            }

            .navbar .ms-auto .btn {
                width: 100%;
                margin: 0;
            }
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg bg-white shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <img src="{{ asset('logo_unilak.jfif') }}"
                    alt="Digital Queue Logo"
                    width="40" height="40">
            </a>

            <div class="ms-auto">
                <a href="{{ route('login') }}" class="btn btn-outline-primary me-2">Login</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero text-center">
        <div class="container">
            <h1 class="display-5 mb-3">
                University Digital Queue Management System
            </h1>
            <p class="lead mb-4">
                Submit service requests, track progress, schedule appointments, and get support — all online.
            </p>

            <a href="{{ route('login') }}" class="btn btn-light btn-lg me-2">
                Get Started
            </a>
            <a href="#services" class="btn btn-outline-light btn-lg">
                Learn More
            </a>
        </div>
    </section>

    <!-- SERVICES -->
    <section id="services" class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">Available Services</h2>
                <p class="text-muted">Access all university offices digitally</p>
            </div>

            <div class="row g-4">
                @forelse($offices as $office)
                <div class="col-md-4">
                    <div class="card p-4 text-center h-100">
                        <div class="feature-icon mx-auto">
                            <i class="bi {{ $office->icon ?? 'bi-building' }}"></i>
                        </div>

                        <h5 class="fw-bold">{{ $office->name }}</h5>

                        <p class="text-muted mb-0">
                            Submit requests and track progress without long queues.
                        </p>

                        <a href="{{ route('student.requests.create', ['office_id' => $office->id]) }}"
                            class="btn btn-primary mt-3">
                            Request from {{ $office->name }}
                        </a>
                    </div>
                </div>
                @empty
                <div class="col-12 text-center text-muted">
                    No offices available at the moment.
                </div>
                @endforelse
            </div>

        </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">How It Works</h2>
                <p class="text-muted">Simple and efficient process</p>
            </div>

            <div class="row g-4 text-center">
                <div class="col-md-3">
                    <div class="step-number mx-auto">1</div>
                    <h6 class="fw-bold">Login</h6>
                    <p class="text-muted">Access your student or staff account</p>
                </div>
                <div class="col-md-3">
                    <div class="step-number mx-auto">2</div>
                    <h6 class="fw-bold">Submit Request</h6>
                    <p class="text-muted">Choose office and service type</p>
                </div>
                <div class="col-md-3">
                    <div class="step-number mx-auto">3</div>
                    <h6 class="fw-bold">Track Status</h6>
                    <p class="text-muted">View real-time updates</p>
                </div>
                <div class="col-md-3">
                    <div class="step-number mx-auto">4</div>
                    <h6 class="fw-bold">Get Support</h6>
                    <p class="text-muted">Reply or attend scheduled appointment</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ PREVIEW -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="fw-bold">Frequently Asked Questions</h2>
                <p class="text-muted">Quick answers before submitting requests</p>
            </div>

            <div class="accordion accordion-flush" id="faqAccordion">
                @forelse($faqs as $index => $faq)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading{{ $faq->id }}">
                        <button class="accordion-button collapsed"
                            data-bs-toggle="collapse"
                            data-bs-target="#faq{{ $faq->id }}"
                            aria-expanded="false">
                            {{ $faq->question }}
                        </button>
                    </h2>

                    <div id="faq{{ $faq->id }}"
                        class="accordion-collapse collapse"
                        data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            {{ $faq->answer }}
                        </div>
                    </div>
                </div>
                @empty
                <p class="text-center text-muted">No FAQs available.</p>
                @endforelse
            </div>
        </div>
    </section>


    <!-- FOOTER -->
    <footer class="py-4">
        <div class="container text-center">
            <small class="text-muted">
                © {{ date('Y') }} University Digital Queue System. All rights reserved.
            </small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
