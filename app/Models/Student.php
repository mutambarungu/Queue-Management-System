<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $primaryKey = 'student_number';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'student_number',
        'name',
        'user_id',
        'faculty',
        'department',
        'campus',
        'phone'
    ];

    private static function generateStudentNumber(): string
    {
        $year = date('Y');

        $last = self::whereYear('created_at', $year)
            ->where('student_number', 'like', "%/$year")
            ->orderByDesc('created_at')
            ->first();

        $nextNumber = $last
            ? ((int) explode('/', $last->student_number)[0] + 1)
            : 1;

        return str_pad($nextNumber, 5, '0', STR_PAD_LEFT) . "/$year";
    }

    protected static function booted()
    {
        static::creating(function ($student) {
            if (empty($student->student_number)) {
                $student->student_number = self::generateStudentNumber();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'student_id', 'student_number');
    }

    public function getRouteKeyName()
    {
        return 'student_number';
    }

    public function getRouteKey()
    {
        // student_number contains "/", so map it to a URL-safe token.
        return str_replace('/', '__', $this->student_number);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $field = $field ?? $this->getRouteKeyName();

        if ($field === 'student_number') {
            $value = str_replace('__', '/', $value);
        }

        return $this->where($field, $value)->firstOrFail();
    }

    public function isProfileComplete(): bool
    {
        return filled($this->faculty)
            && filled($this->department)
            && filled($this->campus)
            && filled($this->phone);
    }
}
