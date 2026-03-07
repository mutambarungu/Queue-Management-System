<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    public const OTHER_NAME = 'Other (Not specified)';

    protected $fillable = ['office_id', 'sub_office_id', 'name'];

    public function office()
    {
        return $this->belongsTo(Office::class);
    }

    public function subOffice()
    {
        return $this->belongsTo(OfficeSubOffice::class, 'sub_office_id');
    }

    public static function normalizeName(?string $name): string
    {
        return mb_strtolower(trim((string) $name));
    }

    public static function resolveOtherForLane(int $officeId, ?int $subOfficeId = null): self
    {
        $baseQuery = self::query()
            ->where('office_id', $officeId)
            ->when(
                filled($subOfficeId),
                fn ($query) => $query->where('sub_office_id', $subOfficeId),
                fn ($query) => $query->whereNull('sub_office_id')
            )
            ->orderBy('id');

        $existing = (clone $baseQuery)->get()->first(function (self $serviceType) {
            return self::normalizeName($serviceType->name) === self::normalizeName(self::OTHER_NAME);
        });

        if ($existing) {
            return $existing;
        }

        return self::query()->create([
            'office_id' => $officeId,
            'sub_office_id' => $subOfficeId,
            'name' => self::OTHER_NAME,
        ]);
    }
}
