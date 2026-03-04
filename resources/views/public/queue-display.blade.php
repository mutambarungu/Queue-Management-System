<!-- resources/views/public/queue-display.blade.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $office->name }} Queue</title>
    <meta http-equiv="refresh" content="15">
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

        .lane-card {
            min-height: 100%;
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
            <p class="lead">Live Queue by Lane</p>
            <small id="last-updated">Last updated at {{ $lastUpdatedAt }}</small>
        </div>

        @if($lanes->isEmpty())
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-lg text-center p-5 bg-dark text-white">
                        <h2>No active queue lanes</h2>
                    </div>
                </div>
            </div>
        @else
            <div class="row g-4">
                @foreach($lanes as $lane)
                    <div class="col-lg-6">
                        <div class="card shadow-lg p-4 bg-light text-dark lane-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="mb-0">{{ $lane['label'] }}</h4>
                                <span class="badge {{ $lane['state'] === 'Queue not started yet' ? 'bg-warning text-dark' : 'bg-success' }}">
                                    {{ $lane['state'] }}
                                </span>
                            </div>

                            <div class="card bg-dark text-white p-3 mb-3 text-center">
                                <h6 class="mb-2">Currently Serving</h6>
                                <div class="h4 mb-1">{{ optional($lane['current'])->queue_position ?? 'None' }}</div>
                                <small>{{ optional($lane['current'])->queue_position ? 'Queue Position' : '' }}</small>
                            </div>

                            <h6>Next in Line</h6>
                            <ul class="list-group list-group-flush">
                                @forelse($lane['next'] as $req)
                                    <li class="list-group-item d-flex justify-content-between">
                                        <strong>{{ $req->queue_position }}</strong>
                                        <span class="badge {{ $req->priority === 'urgent' ? 'bg-danger' : 'bg-primary' }}">
                                            {{ ucfirst($req->priority) }}
                                        </span>
                                    </li>
                                @empty
                                    <li class="list-group-item text-center">No waiting requests</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="text-center mt-5">
            <small>Auto refresh every 15 seconds</small>
        </div>
    </div>

    <script>
        setInterval(function () {
            const now = new Date();
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            const stamp = `${hh}:${mm}:${ss}`;
            const label = document.getElementById('last-updated');
            if (label) {
                label.textContent = `Last updated at ${stamp}`;
            }
        }, 1000);
    </script>
</body>

</html>
