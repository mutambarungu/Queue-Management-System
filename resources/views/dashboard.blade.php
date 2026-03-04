@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="container">

    <h1 class="mb-4">Dashboard</h1>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary shadow">
                <div class="card-body">
                    <h5>Total Requests</h5>
                    <h3>{{ $totalRequests }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card text-white bg-warning shadow">
                <div class="card-body">
                    <h5>Pending Requests</h5>
                    <h3>{{ $pendingRequests }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card text-white bg-success shadow">
                <div class="card-body">
                    <h5>Resolved Requests</h5>
                    <h3>{{ $resolvedRequests }}</h3>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card text-white bg-secondary shadow">
                <div class="card-body">
                    <h5>Appointment Scheduled</h5>
                    <h3>{{ $appointmentRequired }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    Requests by Office
                </div>
                <div class="card-body">
                    <canvas id="officeChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="card-header">
                    Requests by Status
                </div>
                <div class="card-body">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>



<script>
    // Requests by Office + Status
    (function() {
        const officeLabels = @json($requestsPerOffice->pluck('name'));
        const officeCounts = @json($requestsPerOffice->pluck('requests_count'));
        const statusLabels = @json($requestsPerStatus->keys());
        const statusCounts = @json($requestsPerStatus->values());
        const statusColors = {
            'Submitted': 'rgba(67, 94, 190, 0.7)',
            'In Review': 'rgba(255, 193, 7, 0.7)',
            'Awaiting Student Response': 'rgba(23, 162, 184, 0.7)',
            'Appointment Required': 'rgba(13, 110, 253, 0.7)',
            'Appointment Scheduled': 'rgba(153, 102, 255, 0.7)',
            'Resolved': 'rgba(40, 167, 69, 0.7)',
            'Closed': 'rgba(220, 53, 69, 0.7)',
            'Archived': 'rgba(108, 117, 125, 0.7)'
        };
        const statusBackgrounds = statusLabels.map(label => statusColors[label] || 'rgba(99, 99, 99, 0.7)');

        const initCharts = function() {
            if (typeof window.Chart === 'undefined') {
                return false;
            }

            const officeCanvas = document.getElementById('officeChart');
            if (officeCanvas) {
                const deptCtx = officeCanvas.getContext('2d');
                new Chart(deptCtx, {
                    type: 'bar',
                    data: {
                        labels: officeLabels,
                        datasets: [{
                            label: 'Number of Requests',
                            data: officeCounts,
                            backgroundColor: 'rgba(67, 94, 190, 0.7)',
                            borderColor: 'rgba(67, 94, 190, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            },
                            yAxes: [{
                                ticks: {
                                    beginAtZero: true
                                }
                            }]
                        }
                    }
                });
            }

            const statusCanvas = document.getElementById('statusChart');
            if (statusCanvas) {
                const statusCtx = statusCanvas.getContext('2d');
                new Chart(statusCtx, {
                    type: 'pie',
                    data: {
                        labels: statusLabels,
                        datasets: [{
                            data: statusCounts,
                            backgroundColor: statusBackgrounds,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            }

            return true;
        };

        const bootCharts = function() {
            let attempts = 0;
            const maxAttempts = 20;
            const timer = setInterval(function() {
                attempts += 1;
                if (initCharts() || attempts >= maxAttempts) {
                    clearInterval(timer);
                }
            }, 150);
        };

        if (document.readyState === 'complete') {
            bootCharts();
        } else {
            window.addEventListener('load', bootCharts);
        }
    })();
</script>
@endsection
