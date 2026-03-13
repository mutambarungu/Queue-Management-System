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

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #00225a;
            --primary-2: #3649a8;
            --accent: #6f86ff;
            --ink: #0b1b3b;
            --slate: #4b5563;
            --bg: #f5f7fb;
            --shadow-card: 0 14px 30px rgba(11, 27, 59, 0.12);
            --shadow-bloom: 0 22px 50px rgba(54, 73, 168, 0.25);
        }

        body {
            font-family: 'Source Sans 3', sans-serif;
            background: var(--bg);
            color: var(--ink);
        }

        h1, h2, h3, h4, h5 {
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -0.02em;
        }

        .scroll-progress {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            width: 0;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            z-index: 2000;
            box-shadow: 0 0 18px rgba(111, 134, 255, 0.5);
        }

        .navbar {
            backdrop-filter: blur(10px);
        }

        .navbar-brand img {
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(11, 27, 59, 0.15);
        }

        .nav-links {
            display: flex;
            gap: 18px;
            align-items: center;
        }

        .nav-links a {
            color: var(--ink);
            font-weight: 600;
            text-decoration: none;
        }

        .nav-links a:hover {
            color: var(--primary-2);
        }

        .hero {
            padding: 96px 0 76px;
            color: #fff;
            background: linear-gradient(135deg, #00225a, #6f86ff);
        }

        .hero-inner {
            position: relative;
            z-index: 2;
        }

        .hero-content {
            max-width: 720px;
            margin: 0 auto;
        }

        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 18px;
        }

        .hero-title {
            font-size: clamp(2.4rem, 4vw, 3.6rem);
            font-weight: 700;
            letter-spacing: -0.03em;
            margin-bottom: 18px;
        }

        .hero-lead {
            font-size: 1.15rem;
            line-height: 1.75;
            color: rgba(255, 255, 255, 0.85);
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-top: 26px;
        }

        .btn-cta {
            position: relative;
            overflow: hidden;
            border: none;
            background: linear-gradient(135deg, #ffffff, #e7ecff);
            color: #00225a;
            font-weight: 700;
            box-shadow: 0 16px 30px rgba(255, 255, 255, 0.25);
            animation: breathe 3.6s ease-in-out infinite;
        }

        .btn-cta::after {
            content: "";
            position: absolute;
            top: -120%;
            left: -40%;
            width: 60%;
            height: 340%;
            transform: rotate(22deg);
            background: rgba(255, 255, 255, 0.45);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .btn-cta:hover::after {
            opacity: 1;
            animation: buttonShine 1.2s ease;
        }

        .btn-ghost {
            border: 1px solid rgba(255, 255, 255, 0.45);
            color: #fff;
        }

        .btn-ghost:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .btn-arrow {
            position: relative;
            padding-right: 40px;
        }

        .btn-arrow::after {
            content: "→";
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            transition: transform 0.3s ease;
        }

        .btn-arrow:hover::after {
            transform: translate(6px, -50%);
        }

        .btn-ripple {
            position: relative;
            overflow: hidden;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            color: #fff;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: var(--primary-2);
            border-color: var(--primary-2);
            color: #fff;
        }

        .btn-outline-primary {
            color: var(--ink);
            border-color: rgba(0, 34, 90, 0.35);
            font-weight: 600;
        }

        .btn-outline-primary:hover {
            background: rgba(111, 134, 255, 0.12);
            border-color: var(--accent);
            color: var(--ink);
        }

        section {
            position: relative;
        }

        .section-title {
            font-size: clamp(1.9rem, 3vw, 2.5rem);
        }

        .service-card {
            border: none;
            border-radius: 18px;
            padding: 26px 24px;
            background: #fff;
            box-shadow: var(--shadow-card);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .service-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-bloom);
        }

        .icon-badge {
            width: 58px;
            height: 58px;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #fff;
            background: linear-gradient(135deg, #00225a, #6f86ff);
            box-shadow: 0 12px 24px rgba(11, 27, 59, 0.2);
        }

        .how-it-works {
            background: #eef2fb;
        }

        .steps-wrap {
            position: relative;
            padding-top: 24px;
        }

        .steps-line {
            position: absolute;
            left: 6%;
            right: 6%;
            top: 40px;
            height: 4px;
            border-radius: 999px;
            background: rgba(0, 34, 90, 0.12);
            overflow: hidden;
        }

        .steps-line-fill {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, var(--primary), var(--accent));
            transform-origin: left;
            transform: scaleX(var(--line-fill, 0));
        }

        .steps-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
        }

        .step-card {
            background: #fff;
            border-radius: 18px;
            padding: 22px;
            box-shadow: var(--shadow-card);
        }

        .step-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(0, 34, 90, 0.12);
            color: var(--primary);
            font-size: 22px;
            margin-bottom: 12px;
        }

        .faq-section .accordion-item {
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-card);
            overflow: hidden;
            margin-bottom: 14px;
        }

        .faq-section .accordion-button {
            font-weight: 600;
            background: #fff;
            color: var(--ink);
            transition: background 0.2s ease;
        }

        .faq-section .accordion-button::after {
            transition: transform 0.3s ease;
        }

        .faq-section .accordion-button:not(.collapsed) {
            background: rgba(111, 134, 255, 0.12);
            color: var(--ink);
        }

        .faq-section .accordion-collapse {
            transition: height 0.3s ease;
        }

        .faq-section .accordion-body {
            animation: faqFade 0.35s ease;
        }

        .footer-cta {
            background: linear-gradient(135deg, #00225a, #3649a8);
            color: #fff;
            padding: 50px 0;
        }

        .footer-cta .cta-title {
            font-size: clamp(1.7rem, 3vw, 2.4rem);
        }

        .footer-cta .cta-underline {
            display: inline-block;
            width: 80px;
            height: 4px;
            border-radius: 999px;
            background: linear-gradient(90deg, #ffffff, #c8d2ff);
            animation: underlinePulse 2.8s ease-in-out infinite;
        }

        .reveal {
            opacity: 0;
            transform: translateY(18px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }

        .reveal.in {
            opacity: 1;
            transform: translateY(0);
        }

        .ripple {
            position: absolute;
            border-radius: 50%;
            transform: scale(0);
            background: rgba(255, 255, 255, 0.6);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }

        @keyframes breathe {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.03); }
        }

        @keyframes buttonShine {
            0% { transform: translateX(-60%) rotate(22deg); }
            100% { transform: translateX(240%) rotate(22deg); }
        }

        @keyframes ripple {
            to { transform: scale(4); opacity: 0; }
        }

        @keyframes faqFade {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes underlinePulse {
            0%, 100% { opacity: 0.6; transform: scaleX(0.8); }
            50% { opacity: 1; transform: scaleX(1); }
        }

        @media (max-width: 991px) {
            .hero {
                padding: 76px 0 60px;
            }

            .hero-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .steps-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .steps-line {
                left: 10%;
                right: 10%;
            }
        }

        @media (max-width: 767px) {
            .nav-links {
                flex-direction: column;
                align-items: flex-start;
            }

            .steps-grid {
                grid-template-columns: 1fr;
            }

            .steps-line {
                width: 4px;
                left: 24px;
                right: auto;
                top: 0;
                bottom: 0;
                height: 100%;
            }

            .steps-line-fill {
                transform-origin: top;
                transform: scaleY(var(--line-fill, 0));
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation: none !important;
                transition: none !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>

<body>
    <div class="scroll-progress" id="scrollProgress"></div>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg bg-white shadow-sm py-3 position-sticky top-0" style="z-index: 1030;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#top">
                <img src="{{ asset('logo_unilak.jfif') }}" alt="Digital Queue Logo" width="42" height="42">
            </a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <div class="nav-links d-none d-lg-flex">
                    <a href="#services">Services</a>
                    <a href="#how-it-works">How it Works</a>
                    <a href="#faq">FAQ</a>
                </div>
                <a href="{{ route('login') }}" class="btn btn-outline-primary">Login</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Register</a>
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero" id="top">
        <div class="container hero-inner text-center">
            <div class="hero-content reveal" data-reveal>
                <div class="hero-eyebrow">
                    <i class="bi bi-lightning-charge"></i>
                    Smart Queue Experience
                </div>
                <h1 class="hero-title">University Digital Queue Management System</h1>
                <p class="hero-lead">
                    Submit service requests, track progress in real time, schedule appointments, and get support without long lines.
                </p>
                <div class="hero-actions">
                    <a href="{{ route('login') }}" class="btn btn-lg btn-cta btn-ripple">Get Started</a>
                    <a href="#services" class="btn btn-lg btn-outline-light btn-ghost btn-arrow">Explore Services</a>
                </div>
            </div>
        </div>
    </section>

    <!-- SERVICES -->
    <section id="services" class="py-5">
        <div class="container">
            <div class="text-center mb-5 reveal" data-reveal>
                <h2 class="section-title fw-bold">Available Services</h2>
                <p class="text-muted">Access all university offices digitally</p>
            </div>

            <div class="row g-4" data-reveal-group>
                @forelse($offices as $office)
                <div class="col-md-4 reveal" data-reveal>
                    <div class="service-card h-100">
                        <div class="icon-badge mb-3">
                            <i class="bi {{ $office->icon ?? 'bi-building' }}"></i>
                        </div>
                        <h5 class="fw-bold">{{ $office->name }}</h5>
                        <p class="text-muted">
                            Submit requests and track progress without long queues.
                        </p>
                        <a href="{{ route('student.requests.create', ['office_id' => $office->id]) }}" class="btn btn-primary mt-2 btn-ripple">Request from {{ $office->name }}</a>
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
    <section id="how-it-works" class="py-5 how-it-works">
        <div class="container">
            <div class="text-center mb-5 reveal" data-reveal>
                <h2 class="section-title fw-bold">How It Works</h2>
                <p class="text-muted">Simple and efficient process</p>
            </div>

            <div class="steps-wrap" id="stepsWrap">
                <div class="steps-line">
                    <span class="steps-line-fill" id="stepsLineFill"></span>
                </div>
                <div class="steps-grid" data-reveal-group>
                    <div class="step-card reveal" data-reveal>
                        <div class="step-icon"><i class="bi bi-person-check"></i></div>
                        <h6 class="fw-bold">Login</h6>
                        <p class="text-muted">Access your student or staff account</p>
                    </div>
                    <div class="step-card reveal" data-reveal>
                        <div class="step-icon"><i class="bi bi-file-earmark-text"></i></div>
                        <h6 class="fw-bold">Submit Request</h6>
                        <p class="text-muted">Choose office and service type</p>
                    </div>
                    <div class="step-card reveal" data-reveal>
                        <div class="step-icon"><i class="bi bi-broadcast"></i></div>
                        <h6 class="fw-bold">Track Status</h6>
                        <p class="text-muted">View real-time updates</p>
                    </div>
                    <div class="step-card reveal" data-reveal>
                        <div class="step-icon"><i class="bi bi-calendar-check"></i></div>
                        <h6 class="fw-bold">Get Support</h6>
                        <p class="text-muted">Reply or attend scheduled appointment</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ PREVIEW -->
    <section id="faq" class="py-5 faq-section">
        <div class="container">
            <div class="text-center mb-4 reveal" data-reveal>
                <h2 class="section-title fw-bold">Frequently Asked Questions</h2>
                <p class="text-muted">Quick answers before submitting requests</p>
            </div>

            <div class="accordion accordion-flush" id="faqAccordion" data-reveal-group>
                @forelse($faqs as $index => $faq)
                <div class="accordion-item reveal" data-reveal>
                    <h2 class="accordion-header" id="heading{{ $faq->id }}">
                        <button class="accordion-button collapsed" data-bs-toggle="collapse" data-bs-target="#faq{{ $faq->id }}" aria-expanded="false">
                            {{ $faq->question }}
                        </button>
                    </h2>

                    <div id="faq{{ $faq->id }}" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
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

    <!-- FOOTER CTA -->
    <section class="footer-cta">
        <div class="container text-center">
            <h2 class="cta-title fw-bold mb-3">Ready to skip the line?</h2>
            <div class="cta-underline mb-3"></div>
            <p class="mb-4 text-white-50">Create your account and start tracking your requests today.</p>
            <a href="{{ route('register') }}" class="btn btn-light btn-lg btn-ripple">Create an Account</a>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="py-4 bg-white">
        <div class="container text-center">
            <small class="text-muted">
                © {{ date('Y') }} University Digital Queue System. All rights reserved.
            </small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const progress = document.getElementById('scrollProgress');
            const updateProgress = () => {
                const scrollTop = window.scrollY;
                const docHeight = document.documentElement.scrollHeight - window.innerHeight;
                const percent = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
                progress.style.width = `${percent}%`;
            };
            window.addEventListener('scroll', updateProgress, { passive: true });
            updateProgress();

            const revealGroups = document.querySelectorAll('[data-reveal-group]');
            revealGroups.forEach(group => {
                const items = group.querySelectorAll('[data-reveal]');
                items.forEach((item, index) => {
                    item.style.transitionDelay = `${index * 90}ms`;
                });
            });

            const revealItems = document.querySelectorAll('[data-reveal]');
            const revealObserver = new IntersectionObserver(entries => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in');
                        revealObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.2 });

            revealItems.forEach(item => {
                item.classList.add('reveal');
                revealObserver.observe(item);
            });

            const stepsWrap = document.getElementById('stepsWrap');
            const stepsFill = document.getElementById('stepsLineFill');
            const updateStepsLine = () => {
                if (!stepsWrap || !stepsFill) return;
                const rect = stepsWrap.getBoundingClientRect();
                const viewport = window.innerHeight;
                const progress = Math.min(Math.max((viewport - rect.top) / (rect.height + viewport * 0.2), 0), 1);
                stepsFill.style.setProperty('--line-fill', progress);
            };
            window.addEventListener('scroll', updateStepsLine, { passive: true });
            updateStepsLine();

            const buttons = document.querySelectorAll('.btn-ripple');
            buttons.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const circle = document.createElement('span');
                    const diameter = Math.max(btn.clientWidth, btn.clientHeight);
                    const radius = diameter / 2;
                    circle.style.width = circle.style.height = `${diameter}px`;
                    circle.style.left = `${e.clientX - btn.getBoundingClientRect().left - radius}px`;
                    circle.style.top = `${e.clientY - btn.getBoundingClientRect().top - radius}px`;
                    circle.classList.add('ripple');
                    const existing = btn.getElementsByClassName('ripple')[0];
                    if (existing) {
                        existing.remove();
                    }
                    btn.appendChild(circle);
                });
            });
        })();
    </script>
</body>

</html>
