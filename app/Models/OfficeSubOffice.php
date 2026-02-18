<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeSubOffice extends Model
{
    protected $fillable = ['office_id', 'name'];

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function serviceTypes()
    {
        return $this->hasMany(ServiceType::class, 'sub_office_id');
    }
}

