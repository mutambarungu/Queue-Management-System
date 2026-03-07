<?php

namespace App\Support;

use App\Models\QueueCalendarSetting;
use Carbon\Carbon;
use Throwable;

class QueueBusinessCalendar
{
    private const TIMEZONE = 'Africa/Kigali';
    private const GLOBAL_WINDOWS = [
        ['start' => '09:00', 'end' => '14:00'],
        ['start' => '18:00', 'end' => '20:00'],
    ];
    private const SABBATH_WEEKDAY = Carbon::SATURDAY;

    // Hardcoded 2026 holidays for Rwanda (Islamic dates are moon-sighting dependent).
    private const DEFAULT_HOLIDAYS_2026 = [
        '2026-01-01' => "New Year's Day",
        '2026-01-02' => "Day After New Year's Day",
        '2026-02-01' => "National Heroes' Day",
        '2026-02-02' => "National Heroes' Day (Observed)",
        '2026-03-20' => 'Eid al-Fitr (Tentative)',
        '2026-04-03' => 'Good Friday',
        '2026-04-06' => 'Easter Monday',
        '2026-04-07' => 'Genocide against the Tutsi Memorial Day',
        '2026-05-01' => 'Labour Day',
        '2026-05-27' => 'Eid al-Adha (Tentative)',
        '2026-07-01' => 'Independence Day',
        '2026-07-04' => 'Liberation Day',
        '2026-07-06' => 'Liberation Day (Observed)',
        '2026-08-07' => 'Umuganura Day',
        '2026-08-15' => 'Assumption Day',
        '2026-08-17' => 'Assumption Day (Observed)',
        '2026-12-25' => 'Christmas Day',
        '2026-12-26' => 'Boxing Day',
        '2026-12-28' => 'Boxing Day (Observed)',
    ];

    private static ?array $settingsCache = null;

    public static function clearCache(): void
    {
        self::$settingsCache = null;
    }

    /**
     * @return array{
     *   timezone: string,
     *   sabbath_weekday: int,
     *   global_windows: array<int, array{start: string, end: string}>,
     *   holidays: array<string, string>,
     *   special_rules: array<int, array{office_id: int, campus: string|null, faculty_keyword: string|null, days: array<int,int>, windows: array<int, array{start:string,end:string}>}>,
     *   lane_policies: array<int, array{office_id:int,sub_office_id:int|null,appointment_quota:int,online_quota:int,walk_in_quota:int,recall_timeout_seconds:int,walk_in_enabled:bool}>
     * }
     */
    public static function settings(): array
    {
        if (self::$settingsCache !== null) {
            return self::$settingsCache;
        }

        try {
            $record = QueueCalendarSetting::query()->first();
        } catch (Throwable $e) {
            $record = null;
        }

        self::$settingsCache = [
            'timezone' => $record->timezone ?? self::TIMEZONE,
            'sabbath_weekday' => (int) ($record->sabbath_weekday ?? self::SABBATH_WEEKDAY),
            'global_windows' => self::normalizeWindows($record->global_windows ?? self::GLOBAL_WINDOWS),
            'holidays' => self::normalizeHolidays($record->holidays ?? self::DEFAULT_HOLIDAYS_2026),
            'special_rules' => self::normalizeSpecialRules($record->special_rules ?? []),
            'lane_policies' => self::normalizeLanePolicies($record->lane_policies ?? []),
        ];

        return self::$settingsCache;
    }

    /**
     * @param mixed $windows
     * @return array<int, array{start: string, end: string}>
     */
    private static function normalizeWindows($windows): array
    {
        if (!is_array($windows)) {
            return self::GLOBAL_WINDOWS;
        }

        $normalized = collect($windows)
            ->filter(fn ($window) => is_array($window) && isset($window['start'], $window['end']))
            ->map(fn ($window) => [
                'start' => (string) $window['start'],
                'end' => (string) $window['end'],
            ])
            ->values()
            ->all();

        return count($normalized) > 0 ? $normalized : self::GLOBAL_WINDOWS;
    }

    /**
     * @param mixed $days
     * @return array<int, int>
     */
    private static function normalizeDays($days): array
    {
        if (!is_array($days)) {
            return [];
        }

        $normalized = collect($days)
            ->map(fn ($day) => (int) $day)
            ->filter(fn ($day) => $day >= 0 && $day <= 6)
            ->unique()
            ->values()
            ->all();

        return $normalized;
    }

    /**
     * @param mixed $holidays
     * @return array<string, string>
     */
    private static function normalizeHolidays($holidays): array
    {
        if (!is_array($holidays)) {
            return self::DEFAULT_HOLIDAYS_2026;
        }

        $normalized = [];
        foreach ($holidays as $date => $name) {
            if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue;
            }
            $normalized[$date] = trim((string) $name) ?: 'Holiday';
        }

        return count($normalized) > 0 ? $normalized : self::DEFAULT_HOLIDAYS_2026;
    }

    /**
     * @param mixed $rules
     * @return array<int, array{office_id: int, campus: string|null, faculty_keyword: string|null, days: array<int,int>, windows: array<int, array{start:string,end:string}>}>
     */
    private static function normalizeSpecialRules($rules): array
    {
        if (!is_array($rules)) {
            return [];
        }

        $normalized = collect($rules)->map(function ($rule) {
            if (!is_array($rule) || empty($rule['office_id'])) {
                return null;
            }

            $days = self::normalizeDays($rule['days'] ?? []);
            $windows = self::normalizeWindows($rule['windows'] ?? []);
            if (count($days) === 0 || count($windows) === 0) {
                return null;
            }

            $facultyKeyword = trim((string) ($rule['faculty_keyword'] ?? ''));

            return [
                'office_id' => (int) $rule['office_id'],
                'campus' => filled($rule['campus'] ?? null) ? trim((string) $rule['campus']) : null,
                'faculty_keyword' => $facultyKeyword !== '' ? $facultyKeyword : null,
                'days' => $days,
                'windows' => $windows,
            ];
        })->filter()->values()->all();

        return $normalized;
    }

    /**
     * @param mixed $policies
     * @return array<int, array{office_id:int,sub_office_id:int|null,appointment_quota:int,online_quota:int,walk_in_quota:int,recall_timeout_seconds:int,walk_in_enabled:bool,queue_operations_enabled:bool}>
     */
    private static function normalizeLanePolicies($policies): array
    {
        if (!is_array($policies)) {
            return [];
        }

        return collect($policies)
            ->map(function ($policy) {
                if (!is_array($policy) || empty($policy['office_id'])) {
                    return null;
                }

                $appointmentQuota = max(1, (int) ($policy['appointment_quota'] ?? 1));
                $onlineQuota = max(1, (int) ($policy['online_quota'] ?? 1));
                $walkInQuota = max(1, (int) ($policy['walk_in_quota'] ?? 2));
                $recallTimeout = max(15, (int) ($policy['recall_timeout_seconds'] ?? 90));
                $walkInEnabled = filter_var($policy['walk_in_enabled'] ?? true, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                $queueOperationsEnabled = filter_var($policy['queue_operations_enabled'] ?? true, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
                $subOfficeId = filled($policy['sub_office_id'] ?? null) ? (int) $policy['sub_office_id'] : null;

                return [
                    'office_id' => (int) $policy['office_id'],
                    'sub_office_id' => $subOfficeId,
                    'appointment_quota' => $appointmentQuota,
                    'online_quota' => $onlineQuota,
                    'walk_in_quota' => $walkInQuota,
                    'recall_timeout_seconds' => $recallTimeout,
                    'walk_in_enabled' => $walkInEnabled !== false,
                    'queue_operations_enabled' => $queueOperationsEnabled !== false,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public static function timezone(): string
    {
        return self::settings()['timezone'];
    }

    public static function now(): Carbon
    {
        return Carbon::now(self::timezone());
    }

    public static function holidayName(Carbon $date): ?string
    {
        return self::settings()['holidays'][$date->toDateString()] ?? null;
    }

    public static function isHoliday(Carbon $date): bool
    {
        return self::holidayName($date) !== null;
    }

    public static function isSabbath(Carbon $date): bool
    {
        return $date->dayOfWeek === self::settings()['sabbath_weekday'];
    }

    /**
     * @return array{office_id: int, campus: string|null, faculty_keyword: string|null, days: array<int,int>, windows: array<int, array{start:string,end:string}>}|null
     */
    public static function matchingSpecialRule(?int $officeId, ?string $faculty, ?string $campus = null): ?array
    {
        if (!$officeId) {
            return null;
        }

        $facultyText = strtolower(trim((string) $faculty));
        $campusText = strtolower(trim((string) $campus));
        foreach (self::settings()['special_rules'] as $rule) {
            if ((int) $rule['office_id'] !== (int) $officeId) {
                continue;
            }

            if (filled($rule['campus'])) {
                $requiredCampus = strtolower(trim((string) $rule['campus']));
                if ($requiredCampus !== $campusText) {
                    continue;
                }
            }

            if (!filled($rule['faculty_keyword'])) {
                return $rule;
            }

            $keyword = strtolower((string) $rule['faculty_keyword']);
            if ($keyword !== '' && str_contains($facultyText, $keyword)) {
                return $rule;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{start: string, end: string}>
     */
    public static function windowsFor(Carbon $date, ?int $officeId, ?string $faculty, ?string $campus = null): array
    {
        if (self::isHoliday($date) || self::isSabbath($date)) {
            return [];
        }

        $specialRule = self::matchingSpecialRule($officeId, $faculty, $campus);
        if ($specialRule) {
            if (!in_array($date->dayOfWeek, $specialRule['days'], true)) {
                return [];
            }

            return $specialRule['windows'];
        }

        // Global office hours (Sunday is a working day).
        return self::settings()['global_windows'];
    }

    public static function isOpenAt(Carbon $date, ?int $officeId, ?string $faculty, ?string $campus = null): bool
    {
        $time = $date->format('H:i');

        foreach (self::windowsFor($date, $officeId, $faculty, $campus) as $window) {
            if ($time >= $window['start'] && $time < $window['end']) {
                return true;
            }
        }

        return false;
    }

    public static function closureMessage(Carbon $date, ?int $officeId, ?string $faculty, ?string $campus = null): ?string
    {
        $holiday = self::holidayName($date);
        if ($holiday) {
            return 'Office closed for holiday: ' . $holiday;
        }

        if (self::isSabbath($date)) {
            return 'Office closed for Sabbath holiday.';
        }

        if (!self::isOpenAt($date, $officeId, $faculty, $campus)) {
            return 'Office currently closed. Please check working hours.';
        }

        return null;
    }

    public static function hoursDescription(?int $officeId, ?string $faculty, ?string $campus = null): string
    {
        $format = function (array $windows): string {
            return collect($windows)->map(fn ($w) => "{$w['start']}-{$w['end']}")->implode(' and ');
        };

        $specialRule = self::matchingSpecialRule($officeId, $faculty, $campus);
        if ($specialRule) {
            $dayMap = [
                0 => 'Sunday',
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
            ];
            $dayNames = collect($specialRule['days'])
                ->map(fn ($d) => $dayMap[(int) $d] ?? (string) $d)
                ->implode(' & ');

            return "{$dayNames}: " . $format($specialRule['windows']) . " (" . self::timezone() . ').';
        }

        return 'Daily except Sabbath: ' . $format(self::settings()['global_windows']) . " (" . self::timezone() . ').';
    }

    public static function holidayReminderText(Carbon $date): ?string
    {
        $holiday = self::holidayName($date);
        if ($holiday) {
            return 'Today is a holiday (' . $holiday . '). Queue progress and appointments are paused.';
        }

        if (self::isSabbath($date)) {
            return 'Today is Sabbath holiday. Queue progress and appointments are paused.';
        }

        return null;
    }

    /**
     * @return array{appointment_quota:int,online_quota:int,walk_in_quota:int,recall_timeout_seconds:int,walk_in_enabled:bool,queue_operations_enabled:bool}
     */
    public static function lanePolicyFor(int $officeId, ?int $subOfficeId = null): array
    {
        $matched = collect(self::settings()['lane_policies'])
            ->first(function (array $policy) use ($officeId, $subOfficeId) {
                return (int) $policy['office_id'] === (int) $officeId
                    && (int) ($policy['sub_office_id'] ?? 0) === (int) ($subOfficeId ?? 0);
            });

        if ($matched) {
            return [
                'appointment_quota' => (int) $matched['appointment_quota'],
                'online_quota' => (int) ($matched['online_quota'] ?? 1),
                'walk_in_quota' => (int) $matched['walk_in_quota'],
                'recall_timeout_seconds' => (int) $matched['recall_timeout_seconds'],
                'walk_in_enabled' => (bool) ($matched['walk_in_enabled'] ?? true),
                'queue_operations_enabled' => (bool) ($matched['queue_operations_enabled'] ?? true),
            ];
        }

        return [
            'appointment_quota' => 1,
            'online_quota' => 1,
            'walk_in_quota' => 2,
            'recall_timeout_seconds' => 90,
            'walk_in_enabled' => true,
            'queue_operations_enabled' => true,
        ];
    }

    public static function walkInEnabledFor(int $officeId, ?int $subOfficeId = null): bool
    {
        return self::lanePolicyFor($officeId, $subOfficeId)['walk_in_enabled'] ?? true;
    }

    public static function queueOperationsEnabledFor(int $officeId, ?int $subOfficeId = null): bool
    {
        return self::lanePolicyFor($officeId, $subOfficeId)['queue_operations_enabled'] ?? true;
    }
}
