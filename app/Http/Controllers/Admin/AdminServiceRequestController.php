<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RequestRepliedMail;
use App\Models\Office;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestReply;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminServiceRequestController extends Controller
{
    public function index(Request $request)
    {
        // Optional: filter by status or office
        $query = ServiceRequest::with(['student.user', 'office', 'serviceType']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('office_id')) {
            $query->where('office_id', $request->office_id);
        }

        $requests = $query->latest()->paginate(15);

        return view('admin.requests.index', compact('requests'));
    }

    // Show request detail
    public function show(ServiceRequest $request)
    {
        $request->load(['student.user', 'office', 'serviceType', 'attachments', 'replies.user']);
        $reassignOffices = Office::with('subOffices')->orderBy('name')->get(['id', 'name']);
        $reassignSubOfficeMap = $reassignOffices->mapWithKeys(function ($office) {
            return [
                $office->id => $office->subOffices->map(fn ($subOffice) => [
                    'id' => $subOffice->id,
                    'name' => $subOffice->name,
                ])->values(),
            ];
        });

        return view('admin.requests.show', compact('request', 'reassignOffices', 'reassignSubOfficeMap'));
    }

    // Reply to request
    public function reply(Request $r, ServiceRequest $request)
    {
        $r->validate([
            'message' => 'required|string',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx',
            'status' => 'required|in:Submitted,In Review,Awaiting Student Response,Appointment Required,Appointment Scheduled,Resolved,Closed'
        ]);

        $filePath = null;
        if ($r->hasFile('attachment')) {
            $filePath = $r->file('attachment')->store('request_replies', 'public');
        }

        // Save reply
        $reply = new ServiceRequestReply();
        $reply->service_request_id = $request->id;
        $reply->user_id = auth()->id();
        $reply->message = $r->message;
        $reply->attachment = $filePath;
        $reply->save();

        // Update request status
        $request->status = $r->status;
        if (in_array($r->status, ['Resolved', 'Closed'], true)) {
            $request->queue_stage = 'completed';
            $request->called_at = null;
            $request->recalled_at = null;
            $request->no_show_at = null;
            $request->recall_count = 0;
            $request->serving_counter = null;
        } elseif ($r->status === 'In Review') {
            $request->queue_stage = 'serving';
            $request->called_at = $request->called_at ?: now();
            $request->no_show_at = null;
        } else {
            $request->queue_stage = 'waiting';
            $request->called_at = null;
            $request->recalled_at = null;
            $request->no_show_at = null;
            $request->recall_count = 0;
            $request->serving_counter = null;
        }
        $request->save();

        Mail::to($request->student->user->email)
            ->send(new RequestRepliedMail($request));

        return back()->with('success', 'Reply sent successfully.');
    }

    public function reassign(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate([
            'new_office_id' => 'required|exists:offices,id',
            'new_sub_office_id' => 'nullable|exists:office_sub_offices,id',
        ]);

        if ((int) $request->new_office_id === (int) $serviceRequest->office_id) {
            return back()->withErrors([
                'new_office_id' => 'Please choose a different office.',
            ])->withInput();
        }

        $newOffice = Office::with('subOffices')->findOrFail($request->new_office_id);
        $newSubOfficeId = filled($request->new_sub_office_id) ? (int) $request->new_sub_office_id : null;

        if ($newOffice->subOffices->isNotEmpty() && !$newSubOfficeId) {
            return back()->withErrors([
                'new_sub_office_id' => 'Please select a sub-office for this office.',
            ])->withInput();
        }

        if ($newSubOfficeId && !$newOffice->subOffices->contains('id', $newSubOfficeId)) {
            return back()->withErrors([
                'new_sub_office_id' => 'Selected sub-office does not belong to the selected office.',
            ])->withInput();
        }

        $replacementServiceType = ServiceType::resolveOtherForLane((int) $newOffice->id, $newSubOfficeId);

        $serviceRequest->office_id = $newOffice->id;
        $serviceRequest->service_type_id = $replacementServiceType->id;
        $serviceRequest->status = 'Submitted';
        $serviceRequest->queue_stage = 'waiting';
        $serviceRequest->queued_at = now();
        $serviceRequest->next_notified_at = null;
        $serviceRequest->serving_notified_at = null;
        $serviceRequest->called_at = null;
        $serviceRequest->recalled_at = null;
        $serviceRequest->no_show_at = null;
        $serviceRequest->recall_count = 0;
        $serviceRequest->serving_counter = null;
        $serviceRequest->save();

        return back()->with('success', 'Request reassigned successfully.');
    }

    public function archive($id)
    {
        $serviceRequest = ServiceRequest::findOrFail($id);
        // Allow only Resolved or Closed requests
        if (!in_array($serviceRequest->status, ['Resolved', 'Closed'])) {
            return back()->with('error', 'Only resolved or closed requests can be archived.');
        }

        if ($serviceRequest->is_archived) {
            return back()->with('warning', 'Request is already archived.');
        }

        $serviceRequest->update([
            'status' => 'Archived',
            'is_archived' => true,
            'archived_at' => now(),
        ]);

        return back()->with('success', 'Request archived successfully.');
    }

    public function archived()
    {
        $search = trim((string) request('q', ''));

        $requestsQuery = ServiceRequest::archived()
            ->with(['student.user', 'office'])
            ->latest('archived_at');

        if ($search !== '') {
            $requestsQuery->where(function ($query) use ($search) {
                $query->where('request_number', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhereHas('student.user', function ($studentQuery) use ($search) {
                        $studentQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('office', function ($officeQuery) use ($search) {
                        $officeQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $requests = $requestsQuery
            ->paginate(15)
            ->withQueryString();

        return view('admin.requests.archive', compact('requests', 'search'));
    }

    public function restore(ServiceRequest $request)
    {
        $request->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);

        return back()->with('success', 'Request restored successfully.');
    }
}
