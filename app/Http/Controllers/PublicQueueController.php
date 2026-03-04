<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\ServiceRequest;
use App\Support\QueueBusinessCalendar;

class PublicQueueController extends Controller
{
    public function show(Office $office)
    {
        $office->load('subOffices');

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
        $isClosedNow = !QueueBusinessCalendar::isOpenAt($now, $office->id, null);

        $lanes = $laneDefinitions->map(function ($lane) use ($office, $holidayMessage, $isClosedNow) {
            $current = null;
            $next = collect();
            $state = $holidayMessage ?: 'Queue active';

            if (!$holidayMessage && !$isClosedNow) {
                $current = $this->laneBaseQuery($office->id, $lane['sub_office_id'])
                    ->where('status', 'In Review')
                    ->orderByRaw('COALESCE(queued_at, created_at)')
                    ->first();

                $next = $this->buildFairNextList($office->id, $lane['sub_office_id'], 5);

                $state = (!$current && $next->isNotEmpty()) ? 'Queue not started yet' : 'Queue active';
            } elseif (!$holidayMessage && $isClosedNow) {
                $state = QueueBusinessCalendar::closureMessage(QueueBusinessCalendar::now(), $office->id, null) ?? 'Office currently closed.';
            }

            return [
                'label' => $lane['label'],
                'current' => $current,
                'next' => $next,
                'state' => $state,
            ];
        })->filter(fn ($lane) => $lane['current'] || $lane['next']->isNotEmpty())
            ->values();

        return view('public.queue-display', [
            'office' => $office,
            'lanes' => $lanes,
            'lastUpdatedAt' => QueueBusinessCalendar::now()->format('H:i:s'),
        ]);
    }

    private function laneBaseQuery(int $officeId, ?int $subOfficeId)
    {
        return ServiceRequest::with(['student.user', 'serviceType'])
            ->where('office_id', $officeId)
            ->whereNull('archived_at')
            ->whereHas('serviceType', function ($query) use ($subOfficeId) {
                if (filled($subOfficeId)) {
                    $query->where('sub_office_id', $subOfficeId);
                } else {
                    $query->whereNull('sub_office_id');
                }
            });
    }

    private function buildFairNextList(int $officeId, ?int $subOfficeId, int $limit)
    {
        $basePending = $this->laneBaseQuery($officeId, $subOfficeId)
            ->whereIn('status', ['Submitted', 'Awaiting Student Response']);

        $urgent = (clone $basePending)
            ->where('priority', 'urgent')
            ->orderByRaw('COALESCE(queued_at, created_at)')
            ->get()
            ->values();

        $normal = (clone $basePending)
            ->where('priority', 'normal')
            ->orderByRaw('COALESCE(queued_at, created_at)')
            ->get()
            ->values();

        $result = collect();
        $urgentStreak = $this->recentUrgentStreak($officeId, $subOfficeId);

        while ($result->count() < $limit && ($urgent->isNotEmpty() || $normal->isNotEmpty())) {
            if ($urgentStreak >= 3 && $normal->isNotEmpty()) {
                $result->push($normal->shift());
                $urgentStreak = 0;
                continue;
            }

            if ($urgent->isNotEmpty()) {
                $result->push($urgent->shift());
                $urgentStreak++;
                continue;
            }

            if ($normal->isNotEmpty()) {
                $result->push($normal->shift());
                $urgentStreak = 0;
            }
        }

        return $result;
    }

    private function recentUrgentStreak(int $officeId, ?int $subOfficeId): int
    {
        $recent = $this->laneBaseQuery($officeId, $subOfficeId)
            ->whereIn('status', ['In Review', 'Resolved', 'Closed'])
            ->orderByDesc('updated_at')
            ->limit(3)
            ->pluck('priority')
            ->values();

        $streak = 0;
        foreach ($recent as $priority) {
            if ($priority !== 'urgent') {
                break;
            }
            $streak++;
        }

        return $streak;
    }
}
