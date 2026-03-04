<p>Hello {{ $serviceRequest->student->user->name }},</p>

<p>Your service request has been successfully submitted.</p>

<ul>
    <li><strong>Office:</strong> {{ $serviceRequest->office->name }}</li>
    <li><strong>Service:</strong> {{ $serviceRequest->serviceType->name }}</li>
    <li><strong>Status:</strong> {{ $serviceRequest->status }}</li>
</ul>

@php
    $studentFaculty = optional($serviceRequest->student)->faculty;
    $studentCampus = optional($serviceRequest->student)->campus;
    $specialRule = \App\Support\QueueBusinessCalendar::matchingSpecialRule($serviceRequest->office_id, $studentFaculty, $studentCampus);
@endphp

@if($specialRule)
<p style="margin-top:10px; color:#b45309;">
    <strong>Important Notice:</strong><br>
    Working hours for this request category are:
    <strong>{{ \App\Support\QueueBusinessCalendar::hoursDescription($serviceRequest->office_id, $studentFaculty, $studentCampus) }}</strong>.
    Queue progress and appointments are paused during holidays and on Saturdays.
</p>
@endif

<p>We will notify you once it is processed.</p>

<p>Thank you.</p>
