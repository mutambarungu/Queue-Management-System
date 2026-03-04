<?php

namespace App\Models;

use App\Support\QueueBusinessCalendar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ServiceRequest extends Model
{
    protected $fillable = [
        'request_number',
        'student_id',
        'office_id',
        'service_type_id',
        'description',
        'status',
        'archived_at',
        'queued_at',
        'priority',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($req) {
            if (empty($req->request_number)) {
                $req->request_number = self::generateRequestNumber();
            }
        });
    }

    private static function generateRequestNumber()
    {
        do {
            $number = 'REQ-' . date('Y') . '-' . strtoupper(uniqid());
        } while (self::where('request_number', $number)->exists());

        return $number;
    }

    public function attachments()
    {
        return $this->hasMany(RequestAttachment::class);
    }

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'student_number');
    }
    public function replies()
    {
        return $this->hasMany(ServiceRequestReply::class)->latest();
    }
    public function appointment()
    {
        return $this->hasOne(Appointment::class);
    }

    public function getWaitingTimeAttribute()
    {
        // Submission time = created_at
        $startTime = $this->created_at;

        // If the request is resolved, stop counting at resolution time
        if ($this->status === 'Resolved' && $this->updated_at) {
            return $startTime->diffForHumans($this->updated_at, true);
        }

        // Otherwise, keep counting until now
        return $startTime->diffForHumans(now(), true);
    }

    // Requests that CAN be archived (older than 30 days & resolved/closed)
    public function scopeArchivable($query)
    {
        return $query->whereIn('status', ['Resolved', 'Closed'])
            ->where('updated_at', '<=', now()->subDays(30))
            ->where('is_archived', false);
    }

    // Active requests (default)
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    // Archived requests ✅ REQUIRED
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    public function getQueuePositionAttribute()
    {
        $anchorTime = $this->queued_at ?? $this->created_at;
        if (!$anchorTime) {
            return 1;
        }

        return $this->laneQueueBaseQuery()
            ->whereNull('archived_at')
            ->whereIn('status', ['Submitted', 'In Review', 'Awaiting Student Response'])
            ->where(function ($q) {
                $q->where('priority', 'urgent')
                    ->orWhere('priority', 'normal');
            })
            ->whereRaw('COALESCE(queued_at, created_at) < ?', [$anchorTime])
            ->count() + 1;
    }

    public function getPeopleAheadAttribute()
    {
        return max(0, $this->queue_position - 1);
    }

    public function getCurrentlyServingAttribute()
    {
        if (!$this->isQueueOperationalNow()) {
            return null;
        }

        return $this->laneQueueBaseQuery()
            ->whereNull('archived_at')
            ->whereIn('status', ['In Review'])
            ->orderByRaw("FIELD(priority, 'urgent', 'normal')")
            ->orderByRaw('COALESCE(queued_at, created_at)')
            ->first();
    }

    public function getEstimatedWaitTimeAttribute()
    {
        $avgMinutesPerRequest = $this->laneAverageMinutesPerRequest();
        return $this->people_ahead * $avgMinutesPerRequest;
    }

    public function getNextInLineAttribute()
    {
        if (!$this->isQueueOperationalNow()) {
            return null;
        }

        $basePending = $this->laneQueueBaseQuery()
            ->whereNull('archived_at')
            ->whereIn('status', ['Submitted', 'Awaiting Student Response']);

        if ($this->shouldPreferNormalPriority()) {
            $normalCandidate = (clone $basePending)
                ->where('priority', 'normal')
                ->orderByRaw('COALESCE(queued_at, created_at)')
                ->first();

            if ($normalCandidate) {
                return $normalCandidate;
            }
        }

        return $basePending
            ->orderByRaw("FIELD(priority, 'urgent', 'normal')")
            ->orderByRaw('COALESCE(queued_at, created_at)')
            ->first();
    }

    public function getQueueStateAttribute()
    {
        $now = QueueBusinessCalendar::now();
        $closureMessage = QueueBusinessCalendar::closureMessage(
            $now,
            $this->office_id,
            optional($this->student)->faculty,
            optional($this->student)->campus
        );

        if ($closureMessage) {
            return $closureMessage;
        }

        if ($this->queue_position === 1 && !$this->currently_serving) {
            return 'Queue not started yet';
        }

        if ($this->status === 'In Review') {
            return 'You are being served';
        }

        return 'Queue active';
    }

    private function laneQueueBaseQuery(): Builder
    {
        $subOfficeId = optional($this->serviceType)->sub_office_id;

        return self::where('office_id', $this->office_id)
            ->whereHas('serviceType', function ($query) use ($subOfficeId) {
                if (filled($subOfficeId)) {
                    $query->where('sub_office_id', $subOfficeId);
                } else {
                    $query->whereNull('sub_office_id');
                }
            });
    }

    private function laneAverageMinutesPerRequest(): int
    {
        $subOfficeId = optional($this->serviceType)->sub_office_id;

        $durations = self::where('office_id', $this->office_id)
            ->whereHas('serviceType', function ($query) use ($subOfficeId) {
                if (filled($subOfficeId)) {
                    $query->where('sub_office_id', $subOfficeId);
                } else {
                    $query->whereNull('sub_office_id');
                }
            })
            ->whereIn('status', ['Resolved', 'Closed'])
            ->whereNotNull('queued_at')
            ->whereNotNull('updated_at')
            ->selectRaw('TIMESTAMPDIFF(MINUTE, queued_at, updated_at) AS duration_minutes')
            ->orderByDesc('updated_at')
            ->limit(20)
            ->pluck('duration_minutes');

        $avg = $durations->avg();

        return max(1, (int) round($avg ?: 5));
    }

    private function shouldPreferNormalPriority(): bool
    {
        $recentPriorities = $this->laneQueueBaseQuery()
            ->whereNull('archived_at')
            ->whereIn('status', ['In Review', 'Resolved', 'Closed'])
            ->orderByDesc('updated_at')
            ->limit(3)
            ->pluck('priority')
            ->values();

        if ($recentPriorities->count() < 3 || $recentPriorities->contains(fn ($priority) => $priority !== 'urgent')) {
            return false;
        }

        return $this->laneQueueBaseQuery()
            ->whereNull('archived_at')
            ->whereIn('status', ['Submitted', 'Awaiting Student Response'])
            ->where('priority', 'normal')
            ->exists();
    }

    private function isQueueOperationalNow(): bool
    {
        return QueueBusinessCalendar::isOpenAt(
            QueueBusinessCalendar::now(),
            $this->office_id,
            optional($this->student)->faculty,
            optional($this->student)->campus
        );
    }
}
