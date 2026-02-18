<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

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
        return self::where('office_id', $this->office_id)
            ->whereNull('archived_at')
            ->whereIn('status', ['Submitted', 'In Review', 'Awaiting Student Response'])
            ->where(function ($q) {
                $q->where('priority', 'urgent')
                    ->orWhere('priority', 'normal');
            })
            ->where('queued_at', '<', $this->queued_at)
            ->count() + 1;
    }

    public function getPeopleAheadAttribute()
    {
        return max(0, $this->queue_position - 1);
    }

    public function getCurrentlyServingAttribute()
    {
        return self::where('office_id', $this->office_id)
            ->whereNull('archived_at')
            ->whereIn('status', ['In Review'])
            ->orderByRaw("FIELD(priority, 'urgent', 'normal')")
            ->orderBy('queued_at')
            ->first();
    }

    public function getEstimatedWaitTimeAttribute()
    {
        $avgMinutesPerRequest = 5; // You can make this dynamic later
        return $this->people_ahead * $avgMinutesPerRequest;
    }
}
