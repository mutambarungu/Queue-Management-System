<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    protected $fillable = ['office_id', 'sub_office_id', 'name'];

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function subOffice()
    {
        return $this->belongsTo(OfficeSubOffice::class, 'sub_office_id');
    }
}
