@extends('layouts.app')

@section('title', 'Archived Requests')

@section('content')
<style>
    .archive-page {
        --archive-border: #e2e8f0;
        --archive-muted: #64748b;
        --archive-ink: #0f172a;
        --archive-soft: #f8fafc;
        --archive-accent: #2563eb;
    }

    .archive-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .archive-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--archive-ink);
        margin: 0;
    }

    .archive-subtitle {
        color: var(--archive-muted);
        margin: 0.25rem 0 0;
        font-size: 0.9rem;
    }

    .archive-card {
        border: 1px solid var(--archive-border);
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
    }

    .archive-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem;
        background: #fff;
        padding: 0.9rem 1rem;
        border-bottom: 1px solid var(--archive-border);
    }

    .archive-search {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: nowrap;
    }

    .archive-search input {
        width: 420px;
        border-radius: 999px;
        padding-left: 0.9rem;
    }

    .archive-search .btn {
        border-radius: 999px;
        padding-inline: 1rem;
        white-space: nowrap;
    }

    .archive-table {
        margin: 0;
    }

    .archive-table thead th {
        font-size: 0.75rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--archive-muted);
        background: var(--archive-soft);
        border-bottom: 1px solid var(--archive-border);
        padding: 0.75rem 1rem;
    }

    .archive-table tbody td {
        padding: 0.85rem 1rem;
        vertical-align: middle;
    }

    .archive-table tbody tr:hover {
        background: rgba(37, 99, 235, 0.04);
    }

    .archive-badge {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.35rem 0.6rem;
        border-radius: 999px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .archive-badge--archived {
        background: rgba(15, 118, 110, 0.12);
        color: #0f766e;
        border: 1px solid rgba(15, 118, 110, 0.25);
    }

    .archive-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .archive-actions .btn {
        border-radius: 999px;
        padding: 0.35rem 0.85rem;
        font-weight: 600;
    }

    .archive-empty {
        padding: 2rem 1rem;
        text-align: center;
        color: var(--archive-muted);
        font-size: 0.95rem;
    }

    .archive-pagination {
        display: flex;
        justify-content: flex-end;
        padding: 0.7rem 1rem 0.9rem;
        background: #fff;
        border-top: 1px solid var(--archive-border);

    }

    @media (max-width: 768px) {
        .archive-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .archive-search input {
            width: 100%;
        }

        .archive-actions {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<div class="container archive-page">
    <div class="archive-header">
        <div>
            <h4 class="archive-title">Archived Requests</h4>
            <p class="archive-subtitle">Resolved and closed requests moved to archive.</p>
        </div>
        <a href="{{ route('admin.requests.index') }}" class="btn btn-outline-primary">
            Active Requests
        </a>
    </div>

    <div class="archive-card">
        <div class="archive-toolbar">
            <form method="GET" action="{{ route('admin.requests.archived') }}" class="archive-search">
                <input type="text" name="q" class="form-control form-control-sm"
                    placeholder="Search archived requests" value="{{ $search ?? '' }}">
                <button class="btn btn-sm btn-primary">Search</button>
                @if(!empty($search))
                <a href="{{ route('admin.requests.archived') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </form>
            <span class="text-muted small">
                Showing {{ $requests->firstItem() ?? 0 }} - {{ $requests->lastItem() ?? 0 }} of {{ $requests->total() }} requests
            </span>
        </div>

        <div class="table-responsive">
            <table class="table archive-table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Request ID</th>
                        <th>Student</th>
                        <th>Office</th>
                        <th>Status</th>
                        <th>Archived At</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $req->request_number }}</td>
                        <td>{{ $req->student?->user?->name ?? 'N/A' }}</td>
                        <td>{{ $req->office?->name ?? 'N/A' }}</td>
                        <td>
                            <span class="archive-badge archive-badge--archived">{{ $req->status }}</span>
                        </td>
                        <td>{{ $req->archived_at?->format('d M Y') }}</td>
                        <td class="text-end">
                            <div class="archive-actions">
                                <a href="{{ route('admin.requests.show', $req) }}"
                                    class="btn btn-sm btn-outline-primary">
                                    View
                                </a>
                                <form method="POST"
                                    action="{{ route('admin.requests.restore', $req) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success">
                                        Restore
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="archive-empty">
                            No archived requests
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
        <div class="archive-pagination">
            <nav aria-label="Archived requests pagination">
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
        </div>
        @endif
    </div>
</div>
@endsection
