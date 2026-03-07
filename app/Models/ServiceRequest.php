<?php

namespace App\Models;

use App\Support\QueueBusinessCalendar;
use App\Support\QueueTokenGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ServiceRequest extends Model
{
    protected $fillable = [
        'request_number',
        'token_prefix',
        'token_number',
        'token_date',
        'student_id',
        'office_id',
        'service_type_id',
        'request_mode',
        'description',
        'status',
        'archived_at',
        'queued_at',
        'next_notified_at',
        'serving_notified_at',
        'queue_stage',
        'called_at',
        'recalled_at',
        'no_show_at',
        'recall_count',
        'serving_counter',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
        'queued_at' => 'datetime',
        'next_notified_at' => 'datetime',
        'serving_notified_at' => 'datetime',
        'token_date' => 'date',
        'called_at' => 'datetime',
        'recalled_at' => 'datetime',
        'no_show_at' => 'datetime',
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

            if (empty($req->request_mode)) {
                $req->request_mode = 'online';
            }

            if (empty($req->token_prefix) || empty($req->token_number) || empty($req->token_date)) {
                $token = QueueTokenGenerator::generate((int) $req->office_id, (int) $req->service_type_id);
                $req->token_prefix = $token['token_prefix'];
                $req->token_number = $token['token_number'];
                $req->token_date = $token['token_date'];
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

    public function getTokenCodeAttribute(): string
    {
        if (!$this->token_prefix || !$this->token_number) {
            return 'N/A';
        }

        return $this->token_prefix . '-' . str_pad((string) $this->token_number, 3, '0', STR_PAD_LEFT);
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

    // Requests that CAN be archived (older than 7 days & resolved/closed)
    public function scopeArchivable($query)
    {
        return $query->whereIn('status', ['Resolved', 'Closed'])
            ->where('updated_at', '<=', now()->subDays(7))
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
            ->whereNotIn('queue_stage', ['no_show', 'completed'])
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
            ->whereIn('status', ['Submitted', 'In Review', 'Awaiting Student Response'])
            ->where('queue_stage', 'serving')
            ->orderByRaw('COALESCE(called_at, queued_at, created_at)')
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
            ->whereIn('status', ['Submitted', 'Awaiting Student Response', 'Appointment Scheduled'])
            ->where('queue_stage', 'waiting');

        return $basePending
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

        if ($this->queue_stage === 'called') {
            return 'You are being called to the counter';
        }

        if ($this->queue_stage === 'serving') {
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
