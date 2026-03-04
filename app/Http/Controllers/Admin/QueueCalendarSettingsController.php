<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\QueueCalendarSetting;
use App\Support\QueueBusinessCalendar;
use Illuminate\Http\Request;

class QueueCalendarSettingsController extends Controller
{
    public function index()
    {
        $resolved = QueueBusinessCalendar::settings();
        $offices = Office::orderBy('name')->get(['id', 'name']);
        $officesData = $offices->map(function ($office) {
            return [
                'id' => $office->id,
                'name' => $office->name,
                'is_student_affairs' => str_contains(strtolower($office->name), 'student affairs'),
            ];
        })->values();
        $campuses = [
            'Kigali Campus',
            'Rwamagana Campus',
            'Nyanza Campus',
        ];
        $holidayRows = collect($resolved['holidays'])
            ->map(fn ($name, $date) => ['date' => $date, 'name' => $name])
            ->values()
            ->all();
        $specialRules = $resolved['special_rules'];

        return view('admin.queue-calendar.index', [
            'resolved' => $resolved,
            'offices' => $offices,
            'officesData' => $officesData,
            'campuses' => $campuses,
            'holidayRows' => $holidayRows,
            'specialRules' => $specialRules,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'timezone' => 'required|string|max:100',
            'global_start_1' => 'required|date_format:H:i',
            'global_end_1' => 'required|date_format:H:i|after:global_start_1',
            'global_start_2' => 'required|date_format:H:i',
            'global_end_2' => 'required|date_format:H:i|after:global_start_2',
        ]);

        $holidays = [];
        foreach ((array) $request->input('holidays', []) as $row) {
            $date = trim((string) ($row['date'] ?? ''));
            $name = trim((string) ($row['name'] ?? ''));

            if ($date === '' && $name === '') {
                continue;
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                return back()->withErrors(['holidays' => "Invalid holiday date: {$date}. Use YYYY-MM-DD"])->withInput();
            }

            $holidays[$date] = $name !== '' ? $name : 'Holiday';
        }

        $specialRules = [];
        foreach ((array) $request->input('special_rules', []) as $rule) {
            $officeId = (int) ($rule['office_id'] ?? 0);
            $campus = trim((string) ($rule['campus'] ?? ''));
            $facultyKeyword = trim((string) ($rule['faculty_keyword'] ?? ''));
            $days = array_values(array_unique(array_map('intval', (array) ($rule['days'] ?? []))));
            $allDays = filter_var($rule['all_days'] ?? false, FILTER_VALIDATE_BOOLEAN);

            $start1 = trim((string) ($rule['start_1'] ?? ''));
            $end1 = trim((string) ($rule['end_1'] ?? ''));
            $start2 = trim((string) ($rule['start_2'] ?? ''));
            $end2 = trim((string) ($rule['end_2'] ?? ''));

            $isEmpty = $officeId === 0 && $campus === '' && $facultyKeyword === '' && empty($days) && !$allDays && $start1 === '' && $end1 === '' && $start2 === '' && $end2 === '';
            if ($isEmpty) {
                continue;
            }

            if (!$officeId) {
                return back()->withErrors(['special_rules' => 'Each special office rule must have an office selected.'])->withInput();
            }

            $allowedDays = collect(range(0, 6))
                ->reject(fn ($d) => (int) $d === 6)
                ->values()
                ->all();

            if ($allDays) {
                $days = $allowedDays;
            }

            $days = collect($days)
                ->map(fn ($d) => (int) $d)
                ->filter(fn ($d) => in_array($d, $allowedDays, true))
                ->unique()
                ->values()
                ->all();

            if (count($days) === 0) {
                return back()->withErrors(['special_rules' => 'Each special office rule must have at least one working day.'])->withInput();
            }

            foreach ([$start1, $end1, $start2, $end2] as $time) {
                if (!preg_match('/^\d{2}:\d{2}$/', $time)) {
                    return back()->withErrors(['special_rules' => 'Special office windows must use HH:MM format.'])->withInput();
                }
            }

            if ($start1 >= $end1 || $start2 >= $end2) {
                return back()->withErrors(['special_rules' => 'Special office window end times must be after start times.'])->withInput();
            }

            $office = Office::find($officeId);
            $isStudentAffairs = $office && str_contains(strtolower($office->name), 'student affairs');
            if (!$isStudentAffairs) {
                $facultyKeyword = '';
            }

            $specialRules[] = [
                'office_id' => $officeId,
                'campus' => $campus !== '' ? $campus : null,
                'faculty_keyword' => $facultyKeyword !== '' ? $facultyKeyword : null,
                'days' => $days,
                'windows' => [
                    ['start' => $start1, 'end' => $end1],
                    ['start' => $start2, 'end' => $end2],
                ],
            ];
        }

        $settings = QueueCalendarSetting::query()->firstOrNew();
        $settings->timezone = $validated['timezone'];
        $settings->sabbath_weekday = 6;
        $settings->global_windows = [
            ['start' => $validated['global_start_1'], 'end' => $validated['global_end_1']],
            ['start' => $validated['global_start_2'], 'end' => $validated['global_end_2']],
        ];
        $settings->holidays = $holidays;
        $settings->special_rules = $specialRules;
        $settings->save();

        QueueBusinessCalendar::clearCache();

        return back()->with('success', 'Queue calendar settings updated successfully.');
    }
}
