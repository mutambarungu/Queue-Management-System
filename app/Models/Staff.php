<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $primaryKey = 'staff_number';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'staff_number',
        'name',
        'user_id',
        'office_id',
        'sub_office_id',
        'campus',
        'faculty',
        'department',
        'position',
        'phone',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($staff) {
            if (empty($staff->staff_number)) {
                $staff->staff_number = self::generateStaffNumber();
            }
        });
    }

    public static function generateStaffNumber()
    {
        do {
            $number = 'STF-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (self::where('staff_number', $number)->exists());

        return $number;
    }

    // Link to user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Link to office
    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function subOffice()
    {
        return $this->belongsTo(OfficeSubOffice::class, 'sub_office_id');
    }

    // Link to appointments
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'staff_number', 'staff_number');
    }

    public function getRouteKeyName()
    {
        return 'staff_number';
    }
}
