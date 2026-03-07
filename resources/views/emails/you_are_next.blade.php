<p>Hello {{ optional($serviceRequest->student)->name ?? 'Student' }},</p>

@if($mode === 'serving')
<p>Your request <strong>{{ $serviceRequest->request_number }}</strong> is now being served.</p>
@else
<p>Your request <strong>{{ $serviceRequest->request_number }}</strong> now has only one person ahead of you.</p>
@endif

<p>
    <strong>Office:</strong> {{ optional($serviceRequest->office)->name }}<br>
    <strong>Service:</strong> {{ optional($serviceRequest->serviceType)->name }}
</p>

<p>Please monitor your dashboard for live queue updates.</p>
