<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\ServiceRequest;
use App\Support\QueueBusinessCalendar;

class PublicQueueController extends Controller
{
    public function show(Office $office)
    {
        $lanes = $this->buildLanes($office);

        return view('public.queue-display', [
            'office' => $office,
            'lanes' => $lanes,
            'lastUpdatedAt' => QueueBusinessCalendar::now()->format('H:i:s'),
        ]);
    }

    public function status(Office $office)
    {
        $lanes = $this->buildLanes($office)->map(function ($lane) {
            return [
                'label' => $lane['label'],
                'state' => $lane['state'],
                'current_queue_position' => optional($lane['current'])->queue_position,
                'current_token' => optional($lane['current'])->token_code,
                'current_counter' => optional($lane['current'])->serving_counter,
                'called' => $lane['called']->map(function ($request) {
                    return [
                        'queue_position' => $request->queue_position,
                        'token_code' => $request->token_code,
                    ];
                })->values(),
                'next' => $lane['next']->map(function ($request) {
                    return [
                        'queue_position' => $request->queue_position,
                        'token_code' => $request->token_code,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'lanes' => $lanes,
            'timestamp' => QueueBusinessCalendar::now()->format('H:i:s'),
        ]);
    }

    private function laneBaseQuery(int $officeId, ?int $subOfficeId)
    {
        return ServiceRequest::with(['student.user', 'serviceType'])
            ->where('office_id', $officeId)
            ->whereIn('request_mode', ['walk_in', 'appointment', 'online'])
            ->whereNull('archived_at')
            ->whereHas('serviceType', function ($query) use ($subOfficeId) {
                if (filled($subOfficeId)) {
                    $query->where('sub_office_id', $subOfficeId);
                } else {
                    $query->whereNull('sub_office_id');
                }
            });
    }

    private function buildFairNextList(int $officeId, ?int $subOfficeId, ?int $limit = null)
    {
        $today = QueueBusinessCalendar::now()->toDateString();
        $query = $this->laneBaseQuery($officeId, $subOfficeId)
            ->whereIn('status', ['Submitted', 'Awaiting Student Response', 'Appointment Scheduled'])
            ->where('queue_stage', 'waiting')
            ->where(function ($query) use ($today) {
                $query->whereIn('request_mode', ['walk_in', 'online'])
                    ->orWhere(function ($appointmentQuery) use ($today) {
                        $appointmentQuery->where('request_mode', 'appointment')
                            ->whereHas('appointment', fn ($q) => $q->whereDate('appointment_date', $today));
                    });
            })
            ->orderByRaw('COALESCE(queued_at, created_at)');

        if (filled($limit) && (int) $limit > 0) {
            $query->limit((int) $limit);
        }

        return $query->get()->values();
    }

    private function buildLanes(Office $office)
    {
        $office->loadMissing('subOffices');

        $laneDefinitions = collect([[
            'label' => 'General Queue',
            'sub_office_id' => null,
        ]])->merge(
            $office->subOffices->map(fn ($subOffice) => [
                'label' => $subOffice->name,
                'sub_office_id' => $subOffice->id,
            ])
        );

        $now = QueueBusinessCalendar::now();
        $holidayMessage = QueueBusinessCalendar::holidayReminderText($now);
        return $laneDefinitions->map(function ($lane) use ($office, $holidayMessage, $now) {
            $laneSubOfficeId = $lane['sub_office_id'];
            $current = null;
            $called = collect();
            $next = collect();
            $state = 'Queue active';
            $queueOpsEnabled = QueueBusinessCalendar::queueOperationsEnabledFor((int) $office->id, $laneSubOfficeId);
            $laneIsOpen = QueueBusinessCalendar::isOpenAt($now, $office->id, null);
            $canRunQueue = $queueOpsEnabled || $laneIsOpen;
            $holidayPaused = filled($holidayMessage) && !$queueOpsEnabled;

            if (!$holidayPaused && $canRunQueue) {
                $current = $this->laneBaseQuery($office->id, $laneSubOfficeId)
                    ->whereIn('queue_stage', ['serving', 'called'])
                    ->orderByRaw("FIELD(queue_stage, 'serving', 'called')")
                    ->orderByRaw('COALESCE(called_at, queued_at, created_at)')
                    ->first();

                $called = $this->laneBaseQuery($office->id, $laneSubOfficeId)
                    ->where('queue_stage', 'called')
                    ->when($current, fn ($query) => $query->whereKeyNot($current->id))
                    ->orderByRaw('COALESCE(called_at, queued_at, created_at)')
                    ->get()
                    ->values();

                $next = $this->buildFairNextList($office->id, $laneSubOfficeId);
                $state = (!$current && $next->isNotEmpty()) ? 'Queue not started yet' : 'Queue active';
                if (!$laneIsOpen && $queueOpsEnabled) {
                    $state = 'Queue active (manual override)';
                }
            } elseif ($holidayPaused) {
                $state = $holidayMessage;
            } else {
                $state = QueueBusinessCalendar::closureMessage(QueueBusinessCalendar::now(), $office->id, null) ?? 'Office currently closed.';
            }

            return [
                'label' => $lane['label'],
                'current' => $current,
                'called' => $called,
                'next' => $next,
                'state' => $state,
            ];
        })->filter(fn ($lane) => $lane['current'] || $lane['called']->isNotEmpty() || $lane['next']->isNotEmpty())
            ->values();
    }
}
