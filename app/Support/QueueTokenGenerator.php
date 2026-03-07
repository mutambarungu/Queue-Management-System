<?php

namespace App\Support;

use App\Models\Office;
use App\Models\QueueTokenSequence;
use App\Models\ServiceType;
use Illuminate\Support\Facades\DB;
use Throwable;

class QueueTokenGenerator
{
    public static function generate(int $officeId, int $serviceTypeId): array
    {
        $serviceType = ServiceType::query()->find($serviceTypeId);
        $subOfficeId = $serviceType?->sub_office_id;
        $office = Office::query()->find($officeId);

        $date = QueueBusinessCalendar::now()->toDateString();
        $laneKey = static::laneKey($officeId, $subOfficeId);
        $prefix = static::buildPrefix(
            $office?->name ?? 'Office',
            $serviceType?->subOffice?->name
        );

        $nextNumber = DB::transaction(function () use ($officeId, $subOfficeId, $laneKey, $date) {
            $sequence = QueueTokenSequence::query()
                ->where('lane_key', $laneKey)
                ->whereDate('token_date', $date)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                try {
                    $sequence = QueueTokenSequence::query()->create([
                        'office_id' => $officeId,
                        'sub_office_id' => $subOfficeId,
                        'lane_key' => $laneKey,
                        'token_date' => $date,
                        'last_number' => 0,
                    ]);
                } catch (Throwable) {
                    $sequence = QueueTokenSequence::query()
                        ->where('lane_key', $laneKey)
                        ->whereDate('token_date', $date)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            $sequence->last_number = (int) $sequence->last_number + 1;
            $sequence->save();

            return (int) $sequence->last_number;
        }, 3);

        return [
            'token_prefix' => $prefix,
            'token_number' => $nextNumber,
            'token_date' => $date,
        ];
    }

    public static function laneKey(int $officeId, ?int $subOfficeId): string
    {
        return $officeId . ':' . ($subOfficeId ?: 0);
    }

    private static function buildPrefix(string $officeName, ?string $subOfficeName): string
    {
        $officePart = static::shortCode($officeName, 3);
        $subPart = filled($subOfficeName) ? static::shortCode($subOfficeName, 2) : null;

        return $subPart ? ($officePart . '-' . $subPart) : $officePart;
    }

    private static function shortCode(string $name, int $maxLength): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];
        $letters = collect($words)
            ->map(fn ($word) => strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $word), 0, 1)))
            ->filter()
            ->implode('');

        if ($letters === '') {
            $letters = strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $name), 0, $maxLength));
        }

        return substr($letters, 0, $maxLength);
    }
}
