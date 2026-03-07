@extends('layouts.app')
@section('title', 'Queue Operations')

@section('content')
<div class="container-fluid py-3 admin-queue-ops-page">
    @php
        $initialNowServing = $nowServing->map(fn ($row) => [
            'token_code' => $row->token_code,
            'office' => optional($row->office)->name ?? 'Unknown Office',
            'sub_office' => optional(optional($row->serviceType)->subOffice)->name ?? 'General Queue',
            'request_mode' => strtoupper((string) $row->request_mode),
            'serving_staff' => $row->serving_counter ?: 'Unassigned',
        ])->values();
        $initialWalkInWaiting = (int) collect($laneStats)->sum('walk_in_waiting');
        $initialAppointmentWaiting = (int) collect($laneStats)->sum('appointment_waiting');
        $initialTotalWaiting = $initialWalkInWaiting + $initialAppointmentWaiting;
        $initialServingCount = (int) $initialNowServing->count();
    @endphp
    <div class="admin-ops-header">
        <div class="admin-ops-header-text">
            <h3 class="mb-1">Queue Operations Monitor</h3>
            <p class="text-muted mb-0">Live monitor for walk-ins and appointments across lanes.</p>
        </div>
        <div class="admin-ops-header-action">
            <a href="{{ route('admin.lane-policies.index') }}" class="btn btn-outline-dark btn-sm">Lane Policies</a>
        </div>
    </div>

    <div class="admin-stats-grid mb-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted mb-1">Active Serving</div>
                <div class="h4 mb-0" id="admin_stat_serving_count">{{ $initialServingCount }}</div>
            </div>
        </div>
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted mb-1">Total Waiting</div>
                <div class="h4 mb-0" id="admin_stat_total_waiting">{{ $initialTotalWaiting }}</div>
            </div>
        </div>
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted mb-1">Walk-ins Waiting</div>
                <div class="h4 mb-0" id="admin_stat_walkin_waiting">{{ $initialWalkInWaiting }}</div>
            </div>
        </div>
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="small text-uppercase text-muted mb-1">Appointments Waiting</div>
                <div class="h4 mb-0" id="admin_stat_appointment_waiting">{{ $initialAppointmentWaiting }}</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-3 admin-panel">
        <div class="card-body">
            <div class="admin-serving-head mb-3">
                <h5 class="mb-0">Now Serving</h5>
                <select id="admin_office_filter" class="form-select form-select-sm admin-office-filter">
                    <option value="all">All Offices</option>
                </select>
            </div>
            <div id="admin_now_serving_grid">
            </div>
        </div>
    </div>

    <div class="card shadow-sm admin-panel">
        <div class="card-body">
            <h5 class="mb-3">Lane Waiting Overview</h5>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Office</th>
                            <th>Lane</th>
                            <th>Walk-ins Waiting</th>
                            <th>Appointments Waiting</th>
                            <th>TV Display</th>
                        </tr>
                    </thead>
                    <tbody id="admin_lane_stats_table">
                        @forelse($laneStats as $lane)
                            <tr>
                                <td>{{ $lane['office'] }}</td>
                                <td>{{ $lane['sub_office'] }}</td>
                                <td><span class="badge bg-primary">{{ $lane['walk_in_waiting'] }}</span></td>
                                <td><span class="badge bg-warning text-dark">{{ $lane['appointment_waiting'] }}</span></td>
                                <td><a class="btn btn-sm btn-outline-success" href="{{ $lane['tv_url'] }}" target="_blank">Open TV</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No waiting queue data for walk-ins/appointments.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .admin-queue-ops-page {
        --panel-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
    }
    .admin-ops-header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: .8rem;
        align-items: start;
        margin-bottom: .9rem;
    }
    .admin-ops-header-text p {
        line-height: 1.35;
    }
    .admin-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
        gap: .75rem;
    }
    .stat-card {
        border: none;
        border-radius: .85rem;
        background: linear-gradient(145deg, #ffffff, #f3f7ff);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.05);
    }
    .stat-card .card-body {
        padding: .9rem .95rem;
    }
    .stat-card .h4 {
        font-size: clamp(1.25rem, 2.2vw, 1.95rem);
        line-height: 1.1;
    }
    .admin-panel {
        border: none;
        border-radius: .95rem;
        box-shadow: var(--panel-shadow);
    }
    .admin-serving-head {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(180px, 240px);
        gap: .6rem;
        align-items: center;
    }
    .admin-office-filter {
        width: 100%;
        max-width: 100%;
    }
    .now-serving-group {
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: .75rem;
        background: #fff;
        overflow: hidden;
        margin-bottom: .75rem;
    }
    .now-serving-group > summary {
        list-style: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        padding: .75rem .9rem;
        cursor: pointer;
        font-weight: 600;
        background: #f8fafc;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }
    .now-serving-group > summary::-webkit-details-marker {
        display: none;
    }
    .now-serving-count {
        font-size: .75rem;
        font-weight: 700;
        padding: .2rem .55rem;
        border-radius: 999px;
        background: #e2e8f0;
        color: #334155;
    }
    .now-serving-items {
        padding: .8rem;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: .65rem;
    }
    .now-serving-token {
        border: 1px solid rgba(15, 23, 42, 0.12);
        border-radius: .65rem;
        padding: .65rem .75rem;
        background: #fff;
    }
    .now-serving-token-code {
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: .02em;
    }
    @keyframes subtlePulse {
        0% { box-shadow: 0 0 0 0 rgba(14, 165, 233, .35); }
        100% { box-shadow: 0 0 0 10px rgba(14, 165, 233, 0); }
    }
    .pulse-change {
        animation: subtlePulse .7s ease;
    }
    body.dark-mode .now-serving-group {
        background: #141c26;
        border-color: #2b3d5d;
    }
    body.dark-mode .now-serving-group > summary {
        background: #1b2633;
        border-bottom-color: #2b3d5d;
        color: #dbe4f0;
    }
    body.dark-mode .now-serving-count {
        background: #304563;
        color: #dbe4f0;
    }
    body.dark-mode .now-serving-token {
        background: #1b2633;
        border-color: #2b3d5d;
        color: #dbe4f0;
    }
    body.dark-mode .now-serving-token .text-muted {
        color: #9fb1c9 !important;
    }
    body.dark-mode .admin-office-filter {
        background-color: #1b2633;
        border-color: #2b3d5d;
        color: #dbe4f0;
    }
    body.dark-mode .stat-card {
        background: #121927;
        border-color: rgba(148, 163, 184, 0.2);
        color: #e5e7eb;
        box-shadow: 0 16px 34px rgba(0, 0, 0, .28);
    }
    body.dark-mode .admin-panel {
        box-shadow: 0 16px 34px rgba(0, 0, 0, .28);
    }
    @media (max-width: 991.98px) {
        .admin-ops-header {
            grid-template-columns: 1fr;
        }
        .admin-serving-head {
            grid-template-columns: 1fr;
        }
        .admin-stats-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (min-width: 1200px) {
        .admin-stats-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }
</style>

<script>
    (function () {
        const nowServingGrid = document.getElementById('admin_now_serving_grid');
        const officeFilter = document.getElementById('admin_office_filter');
        const laneStatsTable = document.getElementById('admin_lane_stats_table');
        const statServingCount = document.getElementById('admin_stat_serving_count');
        const statTotalWaiting = document.getElementById('admin_stat_total_waiting');
        const statWalkInWaiting = document.getElementById('admin_stat_walkin_waiting');
        const statAppointmentWaiting = document.getElementById('admin_stat_appointment_waiting');
        let currentNowServingRows = @json($initialNowServing);
        let currentLaneStatsRows = @json($laneStats);
        let previousNowServingHash = JSON.stringify(currentNowServingRows);
        let previousLaneStatsHash = '';
        const esc = (value) => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#39;');

        const sortedOffices = (rows) => [...new Set(rows.map((row) => row.office || 'Unknown Office'))].sort((a, b) => a.localeCompare(b));

        const renderOfficeFilter = (rows) => {
            const offices = sortedOffices(rows);
            const previousValue = officeFilter.value || 'all';
            officeFilter.innerHTML = `<option value="all">All Offices</option>` + offices
                .map((office) => `<option value="${esc(office)}">${esc(office)}</option>`)
                .join('');
            officeFilter.value = offices.includes(previousValue) || previousValue === 'all' ? previousValue : 'all';
        };

        const groupByOffice = (rows) => rows.reduce((acc, row) => {
            const office = row.office || 'Unknown Office';
            if (!acc[office]) acc[office] = [];
            acc[office].push(row);
            return acc;
        }, {});

        const tokenCard = (row) => `
            <div class="now-serving-token">
                <div class="now-serving-token-code">${esc(row.token_code)}</div>
                <div class="small text-muted">${esc(row.sub_office)}</div>
                <div class="small mt-1">
                    <span class="badge bg-info text-dark">${esc(row.request_mode)}</span>
                </div>
                <div class="small text-muted mt-1">Staff: ${esc(row.serving_staff || 'Unassigned')}</div>
            </div>
        `;

        const renderNowServing = (rows) => {
            const selectedOffice = officeFilter.value || 'all';
            const filteredRows = selectedOffice === 'all'
                ? rows
                : rows.filter((row) => (row.office || 'Unknown Office') === selectedOffice);

            if (!filteredRows.length) {
                nowServingGrid.innerHTML = '<p class="text-muted mb-0">No active serving tokens right now.</p>';
                return;
            }

            const grouped = groupByOffice(filteredRows);
            const groupedHtml = Object.entries(grouped)
                .sort((a, b) => a[0].localeCompare(b[0]))
                .map(([office, officeRows], index) => `
                    <details class="now-serving-group" ${(selectedOffice !== 'all' || index === 0) ? 'open' : ''}>
                        <summary>
                            <span>${esc(office)}</span>
                            <span class="now-serving-count">${officeRows.length}</span>
                        </summary>
                        <div class="now-serving-items">
                            ${officeRows.map(tokenCard).join('')}
                        </div>
                    </details>
                `).join('');

            nowServingGrid.innerHTML = groupedHtml;
        };

        const renderLaneStats = (lanes) => {
            const walkInWaiting = lanes.reduce((sum, lane) => sum + Number(lane.walk_in_waiting || 0), 0);
            const appointmentWaiting = lanes.reduce((sum, lane) => sum + Number(lane.appointment_waiting || 0), 0);
            const totalWaiting = walkInWaiting + appointmentWaiting;
            statServingCount.textContent = String(currentNowServingRows.length);
            statWalkInWaiting.textContent = String(walkInWaiting);
            statAppointmentWaiting.textContent = String(appointmentWaiting);
            statTotalWaiting.textContent = String(totalWaiting);

            if (!lanes.length) {
                laneStatsTable.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No waiting queue data for walk-ins/appointments.</td></tr>';
                return;
            }
            laneStatsTable.innerHTML = lanes.map((lane) => `
                <tr>
                    <td>${lane.office}</td>
                    <td>${lane.sub_office}</td>
                    <td><span class="badge bg-primary">${lane.walk_in_waiting}</span></td>
                    <td><span class="badge bg-warning text-dark">${lane.appointment_waiting}</span></td>
                    <td><a class="btn btn-sm btn-outline-success" href="${lane.tv_url}" target="_blank">Open TV</a></td>
                </tr>
            `).join('');
        };

        const refreshAdminQueueOps = async () => {
            try {
                const response = await fetch("{{ route('admin.queue.operations.status') }}", {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!response.ok) return;
                const data = await response.json();
                const nextNowServingHash = JSON.stringify(data.now_serving ?? []);
                const nextLaneStatsHash = JSON.stringify(data.lane_stats ?? []);

                if (nextNowServingHash !== previousNowServingHash) {
                    previousNowServingHash = nextNowServingHash;
                    currentNowServingRows = data.now_serving ?? [];
                    renderOfficeFilter(currentNowServingRows);
                    renderNowServing(currentNowServingRows);
                    renderLaneStats(currentLaneStatsRows);
                    nowServingGrid.classList.remove('pulse-change');
                    void nowServingGrid.offsetWidth;
                    nowServingGrid.classList.add('pulse-change');
                }

                if (nextLaneStatsHash !== previousLaneStatsHash) {
                    previousLaneStatsHash = nextLaneStatsHash;
                    currentLaneStatsRows = data.lane_stats ?? [];
                    renderLaneStats(currentLaneStatsRows);
                    laneStatsTable.classList.remove('pulse-change');
                    void laneStatsTable.offsetWidth;
                    laneStatsTable.classList.add('pulse-change');
                }
            } catch (e) {
                // ignore transient fetch errors
            }
        };

        officeFilter.addEventListener('change', () => {
            renderNowServing(currentNowServingRows);
        });

        renderOfficeFilter(currentNowServingRows);
        renderNowServing(currentNowServingRows);
        renderLaneStats(currentLaneStatsRows);

        setInterval(() => {
            if (document.visibilityState === 'visible') {
                refreshAdminQueueOps();
            }
        }, 6000);
    })();
</script>
@endsection
