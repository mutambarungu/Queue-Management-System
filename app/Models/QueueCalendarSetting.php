<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueCalendarSetting extends Model
{
    protected $fillable = [
        'timezone',
        'sabbath_weekday',
        'global_windows',
        'holidays',
        'special_rules',
        'lane_policies',
    ];

    protected $casts = [
        'global_windows' => 'array',
        'holidays' => 'array',
        'special_rules' => 'array',
        'lane_policies' => 'array',
    ];
}
