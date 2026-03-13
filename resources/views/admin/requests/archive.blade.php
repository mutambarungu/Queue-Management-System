@extends('layouts.app')

@section('title', 'Archived Requests')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">Archived Requests</h4>
        <a href="{{ route('admin.requests.index') }}" class="btn btn-outline-primary">
            Active Requests
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <form method="GET" action="{{ route('admin.requests.archived') }}" class="d-flex align-items-center gap-2">
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="Search archived requests" value="{{ $search ?? '' }}" style="width: 240px;">
                <button class="btn btn-sm btn-primary">Search</button>
                @if(!empty($search))
                    <a href="{{ route('admin.requests.archived') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </form>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Request ID</th>
                        <th>Student</th>
                        <th>Office</th>
                        <th>Status</th>
                        <th>Archived At</th>
                        <th>Action</th>
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
                                <span class="badge bg-secondary">{{ $req->status }}</span>
                            </td>
                            <td>{{ $req->archived_at?->format('d M Y') }}</td>
                            <td>
                                <a href="{{ route('admin.requests.show', $req) }}"
                                   class="btn btn-sm btn-outline-info">
                                    View
                                </a>

                                {{-- Optional restore --}}
                                <form method="POST"
                                      action="{{ route('admin.requests.restore', $req) }}"
                                      class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success">
                                        Restore
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                            No archived requests
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
