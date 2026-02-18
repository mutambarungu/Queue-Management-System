@extends('layouts.app')
@section('title', 'Request Detail: ' . $request->request_number)
@section('content')

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold">
                    <i class="bi bi-file-earmark-text me-2 text-primary"></i>
                    Request Detail: {{ $request->request_number }}
                </h3>
                <a href="{{ route('student.requests.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>

            <!-- Live Queue Card -->
            <div class="card mb-4 shadow-sm rounded-4">
                <div class="card-header bg-primary bg-opacity-10">
                    <h5 class="mb-0 fw-semibold">
                        <i class="bi bi-hourglass-split me-2"></i> Live Queue Status
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row text-center align-items-center">
                        <div class="col-md-3 mb-3">
                            <h6>Queue Position</h6>
                            <span class="badge bg-primary fs-5" id="queue_position">#{{ $request->queue_position }}</span>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h6>People Ahead</h6>
                            <span class="badge bg-warning fs-5" id="people_ahead">{{ $request->people_ahead }}</span>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h6>Currently Serving</h6>
                            <span class="badge bg-info fs-6" id="currently_serving">
                                {{ optional($request->currently_serving)->request_number ?? 'None' }}
                            </span>
                        </div>
                        <div class="col-md-3 mb-3">
                            <h6>Estimated Wait</h6>
                            <span class="badge bg-success fs-6" id="estimated_wait">
                                ~{{ $request->estimated_wait_time }} mins
                            </span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-3">
                        @php
                            $totalQueue = max($request->queue_position + $request->people_ahead, 1);
                            $progress = round(($totalQueue - $request->queue_position) / $totalQueue * 100);
                        @endphp
                        <div class="progress rounded-pill" style="height: 20px;">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                {{ $progress }}%
                            </div>
                        </div>
                        <small class="text-muted">Progress towards your turn</small>
                    </div>
                </div>
            </div>

            <!-- Student Info Card -->
            @php
                $student = $request->student;
                $user = optional($student)->user;
                $studentName = $user->name ?? 'N/A';
                $avatarUrl = $student->avatar
                    ?? 'https://ui-avatars.com/api/?name=' . urlencode($studentName !== 'N/A' ? $studentName : 'Student') . '&background=0D6EFD&color=fff';
            @endphp
            <div class="card mb-4 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="row g-3 align-items-start">
                        <div class="col-12 col-md-auto text-center">
                            <img src="{{ $avatarUrl }}"
                                class="rounded-circle border shadow-sm"
                                style="width: 84px; height: 84px; object-fit: cover;"
                                alt="Student Avatar">
                        </div>
                        <div class="col">
                            <h5 class="mb-1">{{ $studentName }}</h5>
                            <p class="mb-3 text-muted text-break">{{ $user->email ?? 'N/A' }}</p>

                            <div class="row row-cols-1 row-cols-sm-2 g-2">
                                <div class="col">
                                    <div class="rounded-3 p-2 h-100">
                                        <small class="text-muted d-block">Student Number</small>
                                        <span class="fw-semibold">{{ $student->student_number ?? 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="rounded-3 p-2 h-100">
                                        <small class="text-muted d-block">Phone</small>
                                        <span class="fw-semibold text-break">{{ $student->phone ?? 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="rounded-3 p-2 h-100">
                                        <small class="text-muted d-block">Office</small>
                                        <span class="fw-semibold">{{ $request->office->name ?? 'N/A' }}</span>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="rounded-3 p-2 h-100">
                                        <small class="text-muted d-block">Department</small>
                                        <span class="fw-semibold">{{ $student->department ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Request Details Card -->
            @php
                $statusClass = match ($request->status) {
                    'Submitted' => 'primary',
                    'In Review' => 'warning',
                    'Awaiting Student Response' => 'info',
                    'Appointment Required' => 'secondary',
                    'Resolved' => 'success',
                    'Closed' => 'dark',
                    default => 'secondary',
                };
            @endphp

            <div class="card mb-4 shadow-sm rounded-4">
                <div class="card-body">
                    <h5 class="fw-semibold">Request Details</h5>
                    <p><strong>Service Type:</strong> {{ $request->serviceType->name ?? 'N/A' }}</p>
                    <p><strong>Description:</strong> {{ $request->description }}</p>
                    @if($request->attachments->count())
                        <p><strong>Attachments:</strong></p>
                        <ul>
                            @foreach($request->attachments as $att)
                                <li><a href="{{ asset('storage/'.$att->file_path) }}" target="_blank">{{ $att->file_name }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                    <p>
                        <strong>Status:</strong>
                        <span class="badge bg-{{ $statusClass }}">{{ $request->status }}</span>
                    </p>
                </div>
            </div>

            <!-- Replies Card -->
            <div class="card mb-4 shadow-sm rounded-4">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">Replies</h5>
                    @forelse($request->replies as $reply)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">{{ $reply->user->name }} | {{ $reply->created_at->format('d M Y h:i A') }}</small>
                            </div>
                            <p class="mb-1">{{ $reply->message }}</p>
                            @if($reply->attachment)
                                <p class="mb-0"><strong>Attachment:</strong>
                                    <a href="{{ asset('storage/'.$reply->attachment) }}" target="_blank">View</a>
                                </p>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted">No replies yet.</p>
                    @endforelse
                </div>
            </div>

            <!-- Status Timeline -->
            <div class="card mb-4 shadow-sm rounded-4">
                <div class="card-body">
                    <h5 class="fw-semibold mb-3">Status Timeline</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="bi bi-check-circle text-primary me-2"></i>
                            Submitted – {{ $request->created_at->format('d M Y h:i A') }}
                        </li>
                        @foreach($request->replies as $reply)
                            <li class="list-group-item">
                                <i class="bi bi-chat-left-text text-info me-2"></i>
                                {{ $reply->user->name }} replied – {{ $reply->created_at->format('d M Y h:i A') }}
                            </li>
                        @endforeach
                        @if($request->appointment)
                            <li class="list-group-item">
                                <i class="bi bi-calendar-event text-success me-2"></i>
                                Appointment Scheduled: {{ $request->appointment->appointment_date }} at {{ $request->appointment->appointment_time }}
                            </li>
                        @endif
                        @if($request->status === 'Resolved')
                            <li class="list-group-item">
                                <i class="bi bi-flag-fill text-success me-2"></i>
                                Request Resolved
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- AJAX Live Queue Update -->
<script>
    function fetchQueueStatus() {
        fetch("{{ route('student.requests.queueStatus', $request->id) }}")
            .then(res => res.json())
            .then(data => {
                document.getElementById('queue_position').innerText = '#' + data.queue_position;
                document.getElementById('people_ahead').innerText = data.people_ahead;
                document.getElementById('currently_serving').innerText = data.currently_serving ?? 'None';
                document.getElementById('estimated_wait').innerText = '~' + data.estimated_wait + ' mins';

                // Update progress bar
                const totalQueue = Math.max(data.queue_position + data.people_ahead, 1);
                const progress = Math.round((totalQueue - data.queue_position) / totalQueue * 100);
                const progressBar = document.querySelector('.progress-bar');
                progressBar.style.width = progress + '%';
                progressBar.innerText = progress + '%';
            })
            .catch(err => console.error(err));
    }

    // Update immediately and then every 5 seconds
    fetchQueueStatus();
    setInterval(fetchQueueStatus, 5000);
</script>

@endsection
