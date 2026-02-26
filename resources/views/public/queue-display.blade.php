<!-- resources/views/public/queue-display.blade.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $office->name }} Queue</title>
    <meta http-equiv="refresh" content="10"> {{-- Auto refresh --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0d6efd, #20c997);
            min-height: 100vh;
            color: white;
        }

        .big-number {
            font-size: 6rem;
            font-weight: 800;
        }

        .card {
            border-radius: 20px;
        }

        @media (max-width: 767.98px) {
            .big-number {
                font-size: 2.5rem;
                word-break: break-word;
            }

            .container-fluid {
                padding-left: 1rem;
                padding-right: 1rem;
            }

            .card {
                border-radius: 14px;
            }
        }
    </style>
</head>

<body>

    <div class="container-fluid py-5">
        <div class="text-center mb-5">
            <h1 class="fw-bold">{{ $office->name }}</h1>
            <p class="lead">Now Serving & Next in Line</p>
        </div>

        <div class="row justify-content-center g-4">

            <!-- Current Serving -->
            <div class="col-lg-6">
                <div class="card shadow-lg text-center p-5 bg-dark text-white">
                    <h2>Now Serving</h2>

                    @if($current)
                    <div class="big-number mt-3">{{ $current->request_number }}</div>
                    <p class="mt-2">Student: {{ $current->student->reg_number ?? 'N/A' }}</p>
                    @else
                    <h3 class="mt-4">No active request</h3>
                    @endif
                </div>
            </div>

            <!-- Next in Queue -->
            <div class="col-lg-4">
                <div class="card shadow-lg p-4 bg-light text-dark">
                    <h4 class="text-center mb-3">Next in Queue</h4>

                    <ul class="list-group list-group-flush">
                        @forelse($next as $req)
                        <li class="list-group-item d-flex justify-content-between">
                            <strong>{{ $req->request_number }}</strong>
                            <span class="badge bg-primary">{{ $req->priority }}</span>
                        </li>
                        @empty
                        <li class="list-group-item text-center">No waiting requests</li>
                        @endforelse
                    </ul>
                </div>
            </div>

        </div>

        <div class="text-center mt-5">
            <small>Auto refresh every 10 seconds</small>
        </div>
    </div>

</body>

</html>
