@extends('layouts.app')
@section('title', 'Service Requests Report')
@section('content')

<div class="container">
    <h4 class="mb-4">Service Requests Report</h4>

    <form class="row g-3 mb-4" method="GET" action="{{ route('admin.reports.index') }}">
        <div class="col-md-3">
            <select name="report_type" id="report_type" class="form-select">
                <option value="office" {{ request('report_type', 'office') === 'office' ? 'selected' : '' }}>Office Report</option>
                <option value="staff" {{ request('report_type') === 'staff' ? 'selected' : '' }}>Staff Performance Report</option>
                <option value="queue" {{ request('report_type') === 'queue' ? 'selected' : '' }}>Queue Operations Report</option>
            </select>
        </div>

        <div class="col-md-3">
            <select name="office_id" id="office_id_filter" class="form-select">
                <option value="">All Offices</option>
                @foreach($offices as $office)
                    <option value="{{ $office->id }}" {{ request('office_id') == $office->id ? 'selected' : '' }}>{{ $office->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3" id="service_filter_group">
            <select name="service_type_id" id="service_type_id_filter" class="form-select">
                <option value="">All Services</option>
                @foreach($serviceTypes as $serviceType)
                    <option value="{{ $serviceType->id }}" {{ request('service_type_id') == $serviceType->id ? 'selected' : '' }}>{{ $serviceType->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3" id="status_filter_group">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                @foreach(['Submitted', 'In Review', 'Awaiting Student Response', 'Appointment Scheduled', 'Resolved', 'Closed'] as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ $status }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3 d-none" id="staff_filter_group">
            <select name="staff_number" class="form-select">
                <option value="">All Staff</option>
                @foreach($staffMembers as $staff)
                    <option value="{{ $staff->staff_number }}" {{ request('staff_number') === $staff->staff_number ? 'selected' : '' }}>
                        {{ $staff->name }} ({{ $staff->staff_number }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3 d-none" id="queue_mode_filter_group">
            <select name="request_mode" class="form-select">
                <option value="">All Modes</option>
                @foreach(['walk_in' => 'Walk In', 'appointment' => 'Appointment', 'online' => 'Online'] as $modeValue => $modeLabel)
                    <option value="{{ $modeValue }}" {{ request('request_mode') === $modeValue ? 'selected' : '' }}>{{ $modeLabel }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3 d-none" id="queue_stage_filter_group">
            <select name="queue_stage" class="form-select">
                <option value="">All Queue Stages</option>
                @foreach(['waiting', 'called', 'serving', 'completed', 'no_show'] as $stage)
                    <option value="{{ $stage }}" {{ request('queue_stage') === $stage ? 'selected' : '' }}>{{ strtoupper(str_replace('_', ' ', $stage)) }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <input type="date" name="from" class="form-control" value="{{ request('from') }}">
        </div>

        <div class="col-md-2">
            <input type="date" name="to" class="form-control" value="{{ request('to') }}">
        </div>

        <div class="col-md-auto align-self-end">
            <button class="btn btn-primary">Filter</button>
        </div>
        <div class="col-md-auto align-self-end">
            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-primary">Reset</a>
        </div>
    </form>

    <div class="mb-3 d-flex gap-2" style="padding-bottom: 2rem;">
        <a href="{{ route('admin.reports.pdf', request()->query()) }}" class="btn btn-danger p-3">
            Download PDF
        </a>
        <a href="{{ route('admin.reports.excel', request()->query()) }}" class="btn btn-success p-3">
            Download Excel
        </a>
        <a href="{{ route('admin.reports.csv', request()->query()) }}" class="btn btn-secondary p-3">
            Download CSV
        </a>
    </div>

    @if(($reportType ?? request('report_type', 'office')) === 'staff')
        <table class="datatable-init nowrap nk-tb-list nk-tb-ulist" data-auto-responsive="true">
            <thead>
                <tr class="nk-tb-item nk-tb-head">
                    <th class="nk-tb-col">#</th>
                    <th class="nk-tb-col">Staff Name</th>
                    <th class="nk-tb-col">Staff ID</th>
                    <th class="nk-tb-col">Office</th>
                    <th class="nk-tb-col">Total Assigned</th>
                    <th class="nk-tb-col">Resolved</th>
                    <th class="nk-tb-col">Closed</th>
                    <th class="nk-tb-col">Pending</th>
                    <th class="nk-tb-col">Avg Resolution (hrs)</th>
                    <th class="nk-tb-col">Completion Rate (%)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staffPerformance as $index => $row)
                    <tr class="nk-tb-item">
                        <td class="nk-tb-col">{{ $index + 1 }}</td>
                        <td class="nk-tb-col">{{ $row['staff_name'] }}</td>
                        <td class="nk-tb-col">{{ $row['staff_number'] }}</td>
                        <td class="nk-tb-col">{{ $row['office_name'] }}</td>
                        <td class="nk-tb-col">{{ $row['total_assigned'] }}</td>
                        <td class="nk-tb-col">{{ $row['resolved'] }}</td>
                        <td class="nk-tb-col">{{ $row['closed'] }}</td>
                        <td class="nk-tb-col">{{ $row['pending'] }}</td>
                        <td class="nk-tb-col">{{ $row['avg_resolution_hours'] ?? 'N/A' }}</td>
                        <td class="nk-tb-col">{{ $row['completion_rate'] }}</td>
                    </tr>
                @empty
                    <tr class="nk-tb-item">
                        <td class="nk-tb-col text-center" colspan="10">No staff performance data found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @elseif(($reportType ?? request('report_type', 'office')) === 'queue')
        <div class="row g-gs mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card card-bordered"><div class="card-inner"><div class="text-soft">Total Tokens</div><h3 class="mt-1">{{ number_format($queueSummary['total'] ?? 0) }}</h3></div></div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card card-bordered"><div class="card-inner"><div class="text-soft">Waiting / Called</div><h3 class="mt-1">{{ $queueSummary['waiting'] ?? 0 }} / {{ $queueSummary['called'] ?? 0 }}</h3></div></div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card card-bordered"><div class="card-inner"><div class="text-soft">Serving / Completed</div><h3 class="mt-1">{{ $queueSummary['serving'] ?? 0 }} / {{ $queueSummary['completed'] ?? 0 }}</h3></div></div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card card-bordered"><div class="card-inner"><div class="text-soft">No Show</div><h3 class="mt-1 text-danger">{{ $queueSummary['no_show'] ?? 0 }}</h3></div></div>
            </div>
        </div>

        <div class="row g-gs mb-4">
            <div class="col-lg-4">
                <div class="card card-bordered h-100"><div class="card-inner"><h6 class="mb-3">Queue Stage Distribution</h6><canvas id="adminQueueStageChart" height="220"></canvas></div></div>
            </div>
            <div class="col-lg-4">
                <div class="card card-bordered h-100"><div class="card-inner"><h6 class="mb-3">Request Mode Distribution</h6><canvas id="adminQueueModeChart" height="220"></canvas></div></div>
            </div>
            <div class="col-lg-4">
                <div class="card card-bordered h-100"><div class="card-inner"><h6 class="mb-3">Queue Activity Trend</h6><canvas id="adminQueueTrendChart" height="220"></canvas></div></div>
            </div>
        </div>

        <div class="card card-bordered">
            <div class="card-inner">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Filtered Queue Records</h6>
                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#adminFilteredQueueRecords" aria-expanded="true" aria-controls="adminFilteredQueueRecords">
                        Collapse / Expand
                    </button>
                </div>

                <div id="adminFilteredQueueRecords" class="collapse show">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Token</th>
                                    <th>Student ID</th>
                                    <th>Office</th>
                                    <th>Service</th>
                                    <th>Mode</th>
                                    <th>Status</th>
                                    <th>Queue Stage</th>
                                    <th>Queued At</th>
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($queueRequests as $queueItem)
                                    <tr>
                                        <td><strong>{{ $queueItem->token_code }}</strong></td>
                                        <td>{{ $queueItem->student_id ?? 'Guest' }}</td>
                                        <td>{{ optional($queueItem->office)->name ?? 'N/A' }}</td>
                                        <td>{{ optional($queueItem->serviceType)->name ?? 'N/A' }}</td>
                                        <td>{{ strtoupper((string) $queueItem->request_mode) }}</td>
                                        <td>{{ $queueItem->status }}</td>
                                        <td>{{ strtoupper(str_replace('_', ' ', (string) $queueItem->queue_stage)) }}</td>
                                        <td>{{ optional($queueItem->queued_at)->format('Y-m-d H:i') ?? optional($queueItem->created_at)->format('Y-m-d H:i') }}</td>
                                        <td>{{ optional($queueItem->updated_at)->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-soft">No queue records found for selected filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="small text-soft">
                            Showing {{ $queueRequests->firstItem() ?? 0 }} to {{ $queueRequests->lastItem() ?? 0 }} of {{ $queueRequests->total() }}
                        </div>
                        @if($queueRequests->hasPages())
                            <nav aria-label="Admin queue report pagination">
                                <ul class="pagination mb-0">
                                    <li class="page-item {{ $queueRequests->onFirstPage() ? 'disabled' : '' }}">
                                        <a class="page-link" href="{{ $queueRequests->onFirstPage() ? '#' : $queueRequests->previousPageUrl() }}" tabindex="{{ $queueRequests->onFirstPage() ? '-1' : '0' }}" aria-disabled="{{ $queueRequests->onFirstPage() ? 'true' : 'false' }}">Prev</a>
                                    </li>
                                    @for($page = 1; $page <= $queueRequests->lastPage(); $page++)
                                        @if($page === 1 || $page === $queueRequests->lastPage() || abs($page - $queueRequests->currentPage()) <= 1)
                                            <li class="page-item {{ $page === $queueRequests->currentPage() ? 'active' : '' }}">
                                                <a class="page-link" href="{{ $queueRequests->url($page) }}">{{ $page }}</a>
                                            </li>
                                        @elseif($page === 2 || $page === $queueRequests->lastPage() - 1)
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        @endif
                                    @endfor
                                    <li class="page-item {{ $queueRequests->hasMorePages() ? '' : 'disabled' }}">
                                        <a class="page-link" href="{{ $queueRequests->hasMorePages() ? $queueRequests->nextPageUrl() : '#' }}" tabindex="{{ $queueRequests->hasMorePages() ? '0' : '-1' }}" aria-disabled="{{ $queueRequests->hasMorePages() ? 'false' : 'true' }}">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else
        <table class="datatable-init nowrap nk-tb-list nk-tb-ulist" data-auto-responsive="true">
            <thead>
                <tr class="nk-tb-item nk-tb-head">
                    <th class="nk-tb-col">#</th>
                    <th class="nk-tb-col">Student ID</th>
                    <th class="nk-tb-col">Office</th>
                    <th class="nk-tb-col">Service</th>
                    <th class="nk-tb-col">Status</th>
                    <th class="nk-tb-col">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $key => $req)
                    <tr class="nk-tb-item">
                        <td class="nk-tb-col">{{ $key + 1 }}</td>
                        <td class="nk-tb-col">
                            <span title="{{ $req->student->name ?? 'No profile name' }}">
                                {{ $req->student->student_number ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="nk-tb-col">{{ $req->office->name }}</td>
                        <td class="nk-tb-col">{{ $req->serviceType->name }}</td>
                        <td class="nk-tb-col">{{ $req->status }}</td>
                        <td class="nk-tb-col">{{ $req->created_at->format('Y-m-d') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<script>
    function toggleReportFilters() {
        const reportType = document.getElementById('report_type').value;
        const statusFilter = document.getElementById('status_filter_group');
        const staffFilter = document.getElementById('staff_filter_group');
        const queueMode = document.getElementById('queue_mode_filter_group');
        const queueStage = document.getElementById('queue_stage_filter_group');

        if (reportType === 'staff') {
            statusFilter.classList.add('d-none');
            staffFilter.classList.remove('d-none');
            queueMode.classList.add('d-none');
            queueStage.classList.add('d-none');
        } else if (reportType === 'queue') {
            statusFilter.classList.remove('d-none');
            staffFilter.classList.add('d-none');
            queueMode.classList.remove('d-none');
            queueStage.classList.remove('d-none');
        } else {
            statusFilter.classList.remove('d-none');
            staffFilter.classList.add('d-none');
            queueMode.classList.add('d-none');
            queueStage.classList.add('d-none');
        }
    }

    document.getElementById('report_type').addEventListener('change', toggleReportFilters);
    toggleReportFilters();

    (function bindOfficeServiceFilter() {
        const officeFilter = document.getElementById('office_id_filter');
        const serviceFilter = document.getElementById('service_type_id_filter');
        if (!officeFilter || !serviceFilter) {
            return;
        }

        const initialServiceOptions = serviceFilter.innerHTML;
        const selectedService = @json((string) request('service_type_id', ''));

        const resetToAllServices = () => {
            serviceFilter.innerHTML = initialServiceOptions;
            if (selectedService) {
                serviceFilter.value = selectedService;
            }
        };

        const loadOfficeServices = async (officeId, preserveSelection = false) => {
            if (!officeId) {
                resetToAllServices();
                return;
            }

            try {
                const response = await fetch(`/api/service-types/${officeId}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) {
                    throw new Error('Failed to load office services');
                }

                const serviceTypes = await response.json();
                serviceFilter.innerHTML = '<option value="">All Services</option>';

                serviceTypes.forEach((service) => {
                    const option = document.createElement('option');
                    option.value = service.id;
                    option.textContent = service.name;
                    serviceFilter.appendChild(option);
                });

                const valueToSet = preserveSelection ? selectedService : '';
                if (valueToSet && [...serviceFilter.options].some(opt => String(opt.value) === String(valueToSet))) {
                    serviceFilter.value = valueToSet;
                }
            } catch (error) {
                resetToAllServices();
            }
        };

        officeFilter.addEventListener('change', () => loadOfficeServices(officeFilter.value, false));
        loadOfficeServices(officeFilter.value, true);
    })();

    document.addEventListener('DOMContentLoaded', function () {
        if (@json(($reportType ?? request('report_type', 'office')) !== 'queue')) {
            return;
        }

        if (typeof window.Chart === 'undefined') {
            return;
        }

        const charts = @json($queueCharts ?? []);

        const stageEl = document.getElementById('adminQueueStageChart');
        if (stageEl) {
            new Chart(stageEl.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: charts.stage?.labels || [],
                    datasets: [{
                        data: charts.stage?.counts || [],
                        backgroundColor: ['#60a5fa', '#f59e0b', '#34d399', '#22c55e', '#ef4444'],
                        borderRadius: 8,
                    }]
                },
                options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
            });
        }

        const modeEl = document.getElementById('adminQueueModeChart');
        if (modeEl) {
            const labels = charts.mode?.labels?.length ? charts.mode.labels : ['No Data'];
            const counts = charts.mode?.counts?.length ? charts.mode.counts : [1];
            new Chart(modeEl.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data: counts,
                        backgroundColor: ['#2563eb', '#f97316', '#64748b', '#10b981'],
                    }]
                },
                options: { plugins: { legend: { position: 'bottom' } } }
            });
        }

        const trendEl = document.getElementById('adminQueueTrendChart');
        if (trendEl) {
            new Chart(trendEl.getContext('2d'), {
                type: 'line',
                data: {
                    labels: charts.trend?.labels || [],
                    datasets: [
                        {
                            label: 'Joined',
                            data: charts.trend?.joined || [],
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59,130,246,0.12)',
                            tension: 0.35,
                            fill: true
                        },
                        {
                            label: 'Completed',
                            data: charts.trend?.served || [],
                            borderColor: '#22c55e',
                            backgroundColor: 'rgba(34,197,94,0.1)',
                            tension: 0.35,
                            fill: true
                        },
                        {
                            label: 'No Show',
                            data: charts.trend?.no_show || [],
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239,68,68,0.1)',
                            tension: 0.35,
                            fill: true
                        }
                    ]
                },
                options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
            });
        }
    });
</script>

@endsection
