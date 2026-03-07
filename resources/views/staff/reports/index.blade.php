@extends('layouts.app')

@section('title', 'Queue Reports')

@section('content')
<div class="container-fluid">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between g-3">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Queue Reports</h3>
                <div class="nk-block-des text-soft">Lane analytics, token actions, filters, and exports.</div>
            </div>
            <div class="nk-block-head-content">
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('staff.reports.pdf', request()->query()) }}" class="btn btn-danger">Download PDF</a>
                    <a href="{{ route('staff.reports.excel', request()->query()) }}" class="btn btn-success">Download Excel</a>
                    <a href="{{ route('staff.reports.csv', request()->query()) }}" class="btn btn-secondary">Download CSV</a>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-bordered mb-4">
        <div class="card-inner">
            <form class="row g-3" method="GET" action="{{ route('staff.reports.index') }}">
                <div class="col-md-2">
                    <label class="form-label">Office</label>
                    <input type="text" class="form-control" value="{{ optional(optional(auth()->user())->staff)->office?->name ?? 'My Office' }}" disabled>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Service</label>
                    <select name="service_type_id" class="form-select">
                        <option value="">All Services</option>
                        @foreach($serviceTypes as $serviceType)
                            <option value="{{ $serviceType->id }}" {{ (string) request('service_type_id') === (string) $serviceType->id ? 'selected' : '' }}>
                                {{ $serviceType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        @foreach(['Submitted', 'In Review', 'Awaiting Student Response', 'Appointment Scheduled', 'Resolved', 'Closed'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Mode</label>
                    <select name="request_mode" class="form-select">
                        <option value="">All Modes</option>
                        @foreach(['walk_in' => 'Walk In', 'appointment' => 'Appointment', 'online' => 'Online'] as $modeValue => $modeLabel)
                            <option value="{{ $modeValue }}" {{ request('request_mode') === $modeValue ? 'selected' : '' }}>{{ $modeLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Queue Stage</label>
                    <select name="queue_stage" class="form-select">
                        <option value="">All Stages</option>
                        @foreach(['waiting', 'called', 'serving', 'completed', 'no_show'] as $stage)
                            <option value="{{ $stage }}" {{ request('queue_stage') === $stage ? 'selected' : '' }}>
                                {{ strtoupper(str_replace('_', ' ', $stage)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                </div>

                <div class="col-md-auto align-self-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
                <div class="col-md-auto align-self-end">
                    <a href="{{ route('staff.reports.index') }}" class="btn btn-outline-primary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-gs mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="text-soft">Total Tokens</div>
                    <h3 class="mt-1">{{ number_format($summary['total']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="text-soft">Waiting / Called</div>
                    <h3 class="mt-1">{{ $summary['waiting'] }} / {{ $summary['called'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="text-soft">Serving / Completed</div>
                    <h3 class="mt-1">{{ $summary['serving'] }} / {{ $summary['completed'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="text-soft">No Show</div>
                    <h3 class="mt-1 text-danger">{{ $summary['no_show'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-gs mb-4">
        <div class="col-lg-4">
            <div class="card card-bordered h-100">
                <div class="card-inner">
                    <h6 class="mb-3">Queue Stage Distribution</h6>
                    <canvas id="queueStageChart" height="220"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-bordered h-100">
                <div class="card-inner">
                    <h6 class="mb-3">Request Mode Distribution</h6>
                    <canvas id="queueModeChart" height="220"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-bordered h-100">
                <div class="card-inner">
                    <h6 class="mb-3">Averages</h6>
                    <div class="d-flex flex-column gap-3">
                        <div class="p-3 bg-light rounded">
                            <div class="text-soft">Average Wait Time</div>
                            <div class="h4 mb-0">{{ $summary['avg_wait_minutes'] !== null ? $summary['avg_wait_minutes'].' min' : 'N/A' }}</div>
                        </div>
                        <div class="p-3 bg-light rounded">
                            <div class="text-soft">Average Service Time</div>
                            <div class="h4 mb-0">{{ $summary['avg_service_minutes'] !== null ? $summary['avg_service_minutes'].' min' : 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-bordered mb-4">
        <div class="card-inner">
            <h6 class="mb-3">Queue Activity Trend</h6>
            <canvas id="queueTrendChart" height="110"></canvas>
        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0">Filtered Queue Records</h6>
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#filteredQueueRecords" aria-expanded="true" aria-controls="filteredQueueRecords">
                    Collapse / Expand
                </button>
            </div>

            <div id="filteredQueueRecords" class="collapse show">
                <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Token</th>
                            <th>Student ID</th>
                            <th>Service</th>
                            <th>Mode</th>
                            <th>Status</th>
                            <th>Queue Stage</th>
                            <th>Queued At</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $requestItem)
                            <tr>
                                <td><strong>{{ $requestItem->token_code }}</strong></td>
                                <td>{{ $requestItem->student_id ?? 'Guest' }}</td>
                                <td>
                                    <div>{{ optional($requestItem->serviceType)->name ?? 'N/A' }}</div>
                                    <small class="text-soft">{{ optional(optional($requestItem->serviceType)->subOffice)->name ?? 'General Queue' }}</small>
                                </td>
                                <td>{{ strtoupper((string) $requestItem->request_mode) }}</td>
                                <td>{{ $requestItem->status }}</td>
                                <td>{{ strtoupper(str_replace('_', ' ', (string) $requestItem->queue_stage)) }}</td>
                                <td>{{ optional($requestItem->queued_at)->format('Y-m-d H:i') ?? optional($requestItem->created_at)->format('Y-m-d H:i') }}</td>
                                <td>{{ optional($requestItem->updated_at)->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-soft">No records found for the selected filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="small text-soft">
                    Showing {{ $requests->firstItem() ?? 0 }} to {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }}
                </div>
                @if($requests->hasPages())
                    <nav aria-label="Staff queue report pagination">
                        <ul class="pagination mb-0">
                            <li class="page-item {{ $requests->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $requests->onFirstPage() ? '#' : $requests->previousPageUrl() }}" tabindex="{{ $requests->onFirstPage() ? '-1' : '0' }}" aria-disabled="{{ $requests->onFirstPage() ? 'true' : 'false' }}">Prev</a>
                            </li>
                            @for($page = 1; $page <= $requests->lastPage(); $page++)
                                @if($page === 1 || $page === $requests->lastPage() || abs($page - $requests->currentPage()) <= 1)
                                    <li class="page-item {{ $page === $requests->currentPage() ? 'active' : '' }}">
                                        <a class="page-link" href="{{ $requests->url($page) }}">{{ $page }}</a>
                                    </li>
                                @elseif($page === 2 || $page === $requests->lastPage() - 1)
                                    <li class="page-item disabled"><span class="page-link">...</span></li>
                                @endif
                            @endfor
                            <li class="page-item {{ $requests->hasMorePages() ? '' : 'disabled' }}">
                                <a class="page-link" href="{{ $requests->hasMorePages() ? $requests->nextPageUrl() : '#' }}" tabindex="{{ $requests->hasMorePages() ? '0' : '-1' }}" aria-disabled="{{ $requests->hasMorePages() ? 'false' : 'true' }}">Next</a>
                            </li>
                        </ul>
                    </nav>
                @endif
            </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.Chart === 'undefined') {
            return;
        }

        const charts = @json($charts);

        const stageEl = document.getElementById('queueStageChart');
        if (stageEl) {
            new Chart(stageEl.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: charts.stage.labels,
                    datasets: [{
                        data: charts.stage.counts,
                        backgroundColor: ['#60a5fa', '#f59e0b', '#34d399', '#22c55e', '#ef4444'],
                        borderRadius: 8,
                    }]
                },
                options: {
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        }

        const modeEl = document.getElementById('queueModeChart');
        if (modeEl) {
            new Chart(modeEl.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: charts.mode.labels.length ? charts.mode.labels : ['No Data'],
                    datasets: [{
                        data: charts.mode.counts.length ? charts.mode.counts : [1],
                        backgroundColor: ['#2563eb', '#f97316', '#64748b', '#10b981'],
                    }]
                },
                options: {
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }

        const trendEl = document.getElementById('queueTrendChart');
        if (trendEl) {
            new Chart(trendEl.getContext('2d'), {
                type: 'line',
                data: {
                    labels: charts.trend.labels,
                    datasets: [
                        {
                            label: 'Joined',
                            data: charts.trend.joined,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59,130,246,0.15)',
                            tension: 0.35,
                            fill: true
                        },
                        {
                            label: 'Completed',
                            data: charts.trend.served,
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34,197,94,0.1)',
                            tension: 0.35,
                            fill: true
                        },
                        {
                            label: 'No Show',
                            data: charts.trend.no_show,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239,68,68,0.1)',
                            tension: 0.35,
                            fill: true
                        }
                    ]
                },
                options: {
                    plugins: { legend: { position: 'bottom' } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        }
    });
</script>
@endsection
