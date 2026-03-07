<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueTokenSequence extends Model
{
    protected $fillable = [
        'office_id',
        'sub_office_id',
        'lane_key',
        'token_date',
        'last_number',
    ];

    protected $casts = [
        'token_date' => 'date',
    ];
}
