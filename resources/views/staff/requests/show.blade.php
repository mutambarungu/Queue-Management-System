@extends('layouts.app')

@section('content')
<div class="container mt-4">

    <h3 class="mb-3">Request Detail: {{ $request->request_number }}</h3>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php
        $student = $request->student;
        $user = optional($student)->user;
        $studentName = $user->name ?? 'N/A';
        $avatarUrl = $student->avatar
            ?? 'https://ui-avatars.com/api/?name=' . urlencode($studentName !== 'N/A' ? $studentName : 'Student') . '&background=0D6EFD&color=fff';
    @endphp
    <div class="card mb-4 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h5 class="mb-3">Student Info</h5>
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
                            <div class="rounded-3 p-2 h-100 border-0">
                                <small class="text-muted d-block">Student Number</small>
                                <span class="fw-semibold">{{ $student->student_number ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col">
                            <div class="rounded-3 p-2 h-100 border-0">
                                <small class="text-muted d-block">Phone</small>
                                <span class="fw-semibold text-break">{{ $student->phone ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col">
                            <div class="rounded-3 p-2 h-100 border-0">
                                <small class="text-muted d-block">Department</small>
                                <span class="fw-semibold">{{ $student->department ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col">
                            <div class="rounded-3 p-2 h-100 border-0">
                                <small class="text-muted d-block">Campus</small>
                                <span class="fw-semibold">{{ $student->campus ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="col">
                            <div class="rounded-3 p-2 h-100 border-0">
                                <small class="text-muted d-block">Office</small>
                                <span class="fw-semibold">{{ $request->office->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h5>Request Details</h5>
            <p><strong>Service Type:</strong> {{ $request->serviceType->name ?? 'N/A' }}</p>
            <p><strong>Description:</strong> {{ $request->description }}</p>
            @if($request->attachment)
            <p><strong>Attachment:</strong>
                <a href="{{ asset('storage/'.$request->attachment) }}" target="_blank">View</a>
            </p>
            @endif
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

            <p>
                <strong>Status:</strong>
                <span class="badge bg-{{ $statusClass }}">
                    {{ $request->status }}
                </span>
            </p>
            <p>
                <strong>Priority:</strong>
                <span class="badge bg-{{ $request->priority === 'urgent' ? 'danger' : 'secondary' }}">
                    {{ ucfirst($request->priority ?? 'normal') }}
                </span>
            </p>

        </div>
    </div>

    <!-- Replies -->
    <div class="card mb-3">
        <div class="card-body">
            <h5>Replies</h5>
            @forelse($request->replies as $reply)
            <div class="border rounded p-2 mb-2">
                <small class="text-muted">{{ $reply->user->name }} | {{ $reply->created_at->format('d M Y h:i A') }}</small>
                <p>{{ $reply->message }}</p>
                @if($reply->attachment)
                <p>Attachment: <a href="{{ asset('storage/'.$reply->attachment) }}" target="_blank">View</a></p>
                @endif
            </div>
            @empty
            <p class="text-muted">No replies yet.</p>
            @endforelse
        </div>
    </div>

    <!-- Reply Form -->
    <div class="card">
        <div class="card-body">
            <h5>Send Reply / Update Status</h5>
            <form action="{{ route('staff.requests.reply', $request->id) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="4" required></textarea>
                </div>

                <div class="mb-3">
                    <label for="attachment" class="form-label">Attachment (optional)</label>
                    <input type="file" name="attachment" class="form-control">
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" class="form-select" required>
                        <option value="Submitted" {{ $request->status=='Submitted'?'selected':'' }}>Submitted</option>
                        <option value="In Review" {{ $request->status=='In Review'?'selected':'' }}>In Review</option>
                        <option value="Awaiting Student Response" {{ $request->status=='Awaiting Student Response'?'selected':'' }}>Awaiting Student Response</option>
                        <option value="Appointment Required" {{ $request->status=='Appointment Required'?'selected':'' }}>Appointment Required</option>
                        <option value="Resolved" {{ $request->status=='Resolved'?'selected':'' }}>Resolved</option>
                        <option value="Closed" {{ $request->status=='Closed'?'selected':'' }}>Closed</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="priority" class="form-label">Priority</label>
                    <select name="priority" class="form-select" required>
                        <option value="normal" {{ $request->priority=='normal'?'selected':'' }}>Normal</option>
                        <option value="urgent" {{ $request->priority=='urgent'?'selected':'' }}>Urgent</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">Send Reply</button>
            </form>
        </div>
    </div>
    @if($request->status === 'Appointment Required')
    <div class="card mt-4">
        <div class="card-body">
            <h5>Schedule Appointment</h5>

            <form action="{{ route('staff.appointments.store', $request->id) }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-4">
                        <label>Date</label>
                        <input type="date" name="appointment_date" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label>Time</label>
                        <input type="time" name="appointment_time" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control" placeholder="Office / Room">
                    </div>
                </div>

                <button class="btn btn-success mt-3">Schedule Appointment</button>
            </form>
        </div>
    </div>
    @endif

    <div class="card mt-4">
        <div class="card-body">
            <h5>Status Timeline</h5>

            <ul class="list-group list-group-flush">
                <li class="list-group-item">
                    <i class="bi bi-check-circle text-primary"></i>
                    Submitted – {{ $request->created_at->format('d M Y h:i A') }}
                </li>

                @foreach($request->replies as $reply)
                <li class="list-group-item">
                    <i class="bi bi-chat-left-text text-info"></i>
                    {{ $reply->user->name }} replied – {{ $reply->created_at->format('d M Y h:i A') }}
                </li>
                @endforeach

                @if($request->appointment)
                <li class="list-group-item">
                    <i class="bi bi-calendar-event text-success"></i>
                    Appointment Scheduled:
                    {{ $request->appointment->appointment_date }}
                    at {{ $request->appointment->appointment_time }}
                </li>
                @endif

                @if($request->status === 'Resolved')
                <li class="list-group-item">
                    <i class="bi bi-flag-fill text-success"></i>
                    Request Resolved
                </li>
                @endif
            </ul>
        </div>
    </div>

</div>
@endsection
