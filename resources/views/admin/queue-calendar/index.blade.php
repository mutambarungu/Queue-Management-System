@extends('layouts.app')
@section('title', 'Queue Calendar Settings')
@section('content')
<div class="container-fluid">
    <div class="nk-content-inner">
        <div class="nk-content-body">
            <div class="nk-block nk-block-lg">
                <div class="nk-block-head">
                    <div class="nk-block-head-content">
                        <h4 class="nk-block-title mb-2">Queue Calendar Settings</h4>
                        <p class="text-muted mb-0">Manage working hours, Sabbath day, CIS special window, and holidays.</p>
                    </div>
                </div>

                <div class="card card-bordered card-preview">
                    <div class="card-inner">
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif

                        @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('admin.queue-calendar.update') }}">
                            @csrf
                            @method('PUT')

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Timezone</label>
                                    <input type="text" name="timezone" class="form-control" value="{{ old('timezone', $resolved['timezone']) }}" required>
                                </div>
                            </div>
                            @php
                                $days = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];
                                $workDays = collect($days)->reject(fn($_, $index) => (int) $index === 6)->all();
                            @endphp

                            <hr class="my-4">
                            <h6>Global Office Windows</h6>
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Window 1 Start</label>
                                    <input type="time" name="global_start_1" class="form-control" value="{{ old('global_start_1', $resolved['global_windows'][0]['start'] ?? '09:00') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Window 1 End</label>
                                    <input type="time" name="global_end_1" class="form-control" value="{{ old('global_end_1', $resolved['global_windows'][0]['end'] ?? '14:00') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Window 2 Start</label>
                                    <input type="time" name="global_start_2" class="form-control" value="{{ old('global_start_2', $resolved['global_windows'][1]['start'] ?? '18:00') }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Window 2 End</label>
                                    <input type="time" name="global_end_2" class="form-control" value="{{ old('global_end_2', $resolved['global_windows'][1]['end'] ?? '20:00') }}" required>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h6>Special Office Schedule Rules</h6>
                            <p class="text-muted mb-2">Define any office-specific rule. Faculty keyword is optional (matches student faculty text).</p>
                            <div id="specialRulesContainer">
                                @php
                                    $oldSpecialRules = old('special_rules', $specialRules);
                                @endphp
                                @foreach($oldSpecialRules as $i => $rule)
                                    @php
                                        $ruleDays = collect($rule['days'] ?? [])->map(fn($d) => (int) $d)->all();
                                        $ruleWindows = $rule['windows'] ?? [
                                            ['start' => $rule['start_1'] ?? '', 'end' => $rule['end_1'] ?? ''],
                                            ['start' => $rule['start_2'] ?? '', 'end' => $rule['end_2'] ?? ''],
                                        ];
                                        $selectedOffice = $offices->firstWhere('id', (int) ($rule['office_id'] ?? 0));
                                        $isStudentAffairsSelected = $selectedOffice && str_contains(strtolower($selectedOffice->name), 'student affairs');
                                    @endphp
                                    <div class="border rounded p-3 mb-3 special-rule-item">
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label">Office</label>
                                                <select name="special_rules[{{ $i }}][office_id]" class="form-select special-office-select">
                                                    <option value="">Select Office</option>
                                                    @foreach($offices as $office)
                                                        <option value="{{ $office->id }}" data-student-affairs="{{ str_contains(strtolower($office->name), 'student affairs') ? '1' : '0' }}" {{ (int) ($rule['office_id'] ?? 0) === (int) $office->id ? 'selected' : '' }}>{{ $office->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Campus (optional)</label>
                                                <select name="special_rules[{{ $i }}][campus]" class="form-select">
                                                    <option value="">All campuses</option>
                                                    @foreach($campuses as $campus)
                                                        <option value="{{ $campus }}" {{ ($rule['campus'] ?? '') === $campus ? 'selected' : '' }}>{{ $campus }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4 faculty-keyword-group {{ $isStudentAffairsSelected ? '' : 'd-none' }}">
                                                <label class="form-label">Faculty Keyword (optional)</label>
                                                <input type="text" class="form-control" name="special_rules[{{ $i }}][faculty_keyword]" value="{{ $rule['faculty_keyword'] ?? '' }}" placeholder="e.g. computing and information sciences">
                                            </div>
                                            <div class="col-md-2 d-flex align-items-end">
                                                <div class="form-check">
                                                    <input class="form-check-input all-days-toggle" type="checkbox" name="special_rules[{{ $i }}][all_days]" value="1" id="special_{{ $i }}_all_days" {{ count($ruleDays) === count($workDays) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="special_{{ $i }}_all_days">All days</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2 text-danger small">
                                            <i class="bi bi-flag-fill me-1"></i>
                                            Sabbath closure is automatic; Saturday is not selectable.
                                        </div>
                                        <div class="row g-2 mt-2">
                                            @foreach($workDays as $value => $label)
                                                <div class="col-md-2 col-4">
                                                    <div class="form-check">
                                                        <input class="form-check-input special-day" type="checkbox" name="special_rules[{{ $i }}][days][]" value="{{ $value }}" id="special_{{ $i }}_day_{{ $value }}" {{ in_array($value, $ruleDays, true) ? 'checked' : '' }}>
                                                        <label class="form-check-label" for="special_{{ $i }}_day_{{ $value }}">{{ $label }}</label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="row g-3 mt-1">
                                            <div class="col-md-3">
                                                <label class="form-label">Window 1 Start</label>
                                                <input type="time" class="form-control" name="special_rules[{{ $i }}][start_1]" value="{{ $ruleWindows[0]['start'] ?? '' }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Window 1 End</label>
                                                <input type="time" class="form-control" name="special_rules[{{ $i }}][end_1]" value="{{ $ruleWindows[0]['end'] ?? '' }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Window 2 Start</label>
                                                <input type="time" class="form-control" name="special_rules[{{ $i }}][start_2]" value="{{ $ruleWindows[1]['start'] ?? '' }}">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Window 2 End</label>
                                                <input type="time" class="form-control" name="special_rules[{{ $i }}][end_2]" value="{{ $ruleWindows[1]['end'] ?? '' }}">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="addSpecialRuleBtn">Add Special Rule</button>

                            <hr class="my-4">
                            <h6>Holidays</h6>
                            <p class="text-muted mb-2">
                                <i class="bi bi-flag-fill text-danger me-1"></i>
                                Sabbath day is automatically flagged as a holiday/closure. Add dated holidays below.
                            </p>
                            <div id="holidaysContainer">
                                @php
                                    $holidayInputRows = old('holidays', $holidayRows);
                                    if (empty($holidayInputRows)) {
                                        $holidayInputRows = [['date' => '', 'name' => '']];
                                    }
                                @endphp
                                @foreach($holidayInputRows as $i => $holiday)
                                    <div class="row g-2 mb-2 holiday-row">
                                        <div class="col-md-1 col-12 d-flex align-items-center text-danger">
                                            <i class="bi bi-flag-fill me-1"></i>
                                        </div>
                                        <div class="col-md-4">
                                            <input type="date" class="form-control" name="holidays[{{ $i }}][date]" value="{{ $holiday['date'] ?? '' }}">
                                        </div>
                                        <div class="col-md-7">
                                            <input type="text" class="form-control" name="holidays[{{ $i }}][name]" value="{{ $holiday['name'] ?? '' }}" placeholder="Holiday name">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="addHolidayBtn">Add Holiday</button>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Save Calendar Settings</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const days = [
        { value: 0, label: 'Sunday' },
        { value: 1, label: 'Monday' },
        { value: 2, label: 'Tuesday' },
        { value: 3, label: 'Wednesday' },
        { value: 4, label: 'Thursday' },
        { value: 5, label: 'Friday' }
    ];
    const offices = @json($officesData);
    const campuses = @json($campuses);

    const specialContainer = document.getElementById('specialRulesContainer');
    const addSpecialRuleBtn = document.getElementById('addSpecialRuleBtn');
    let specialIndex = specialContainer.querySelectorAll('.special-rule-item').length;

    addSpecialRuleBtn.addEventListener('click', function () {
        const officeOptions = ['<option value=\"\">Select Office</option>']
            .concat(offices.map(o => `<option value=\"${o.id}\" data-student-affairs=\"${o.is_student_affairs ? '1' : '0'}\">${o.name}</option>`))
            .join('');
        const campusOptions = ['<option value=\"\">All campuses</option>']
            .concat(campuses.map(c => `<option value=\"${c}\">${c}</option>`))
            .join('');

        const dayCheckboxes = days.map(d => `
            <div class=\"col-md-2 col-4\">
                <div class=\"form-check\">
                    <input class=\"form-check-input special-day\" type=\"checkbox\" name=\"special_rules[${specialIndex}][days][]\" value=\"${d.value}\" id=\"special_${specialIndex}_day_${d.value}\">
                    <label class=\"form-check-label\" for=\"special_${specialIndex}_day_${d.value}\">${d.label}</label>
                </div>
            </div>
        `).join('');

        const html = `
            <div class=\"border rounded p-3 mb-3 special-rule-item\">
                <div class=\"row g-3\">
                    <div class=\"col-md-3\">
                        <label class=\"form-label\">Office</label>
                        <select name=\"special_rules[${specialIndex}][office_id]\" class=\"form-select special-office-select\">${officeOptions}</select>
                    </div>
                    <div class=\"col-md-3\">
                        <label class=\"form-label\">Campus (optional)</label>
                        <select name=\"special_rules[${specialIndex}][campus]\" class=\"form-select\">${campusOptions}</select>
                    </div>
                    <div class=\"col-md-4 faculty-keyword-group d-none\">
                        <label class=\"form-label\">Faculty Keyword (optional)</label>
                        <input type=\"text\" class=\"form-control\" name=\"special_rules[${specialIndex}][faculty_keyword]\" placeholder=\"e.g. computing and information sciences\">
                    </div>
                    <div class=\"col-md-2 d-flex align-items-end\">
                        <div class=\"form-check\">
                            <input class=\"form-check-input all-days-toggle\" type=\"checkbox\" name=\"special_rules[${specialIndex}][all_days]\" value=\"1\" id=\"special_${specialIndex}_all_days\">
                            <label class=\"form-check-label\" for=\"special_${specialIndex}_all_days\">All days</label>
                        </div>
                    </div>
                </div>
                <div class=\"mt-2 text-danger small\"><i class=\"bi bi-flag-fill me-1\"></i>Sabbath closure is automatic; Saturday is not selectable.</div>
                <div class=\"row g-2 mt-2\">${dayCheckboxes}</div>
                <div class=\"row g-3 mt-1\">
                    <div class=\"col-md-3\"><label class=\"form-label\">Window 1 Start</label><input type=\"time\" class=\"form-control\" name=\"special_rules[${specialIndex}][start_1]\"></div>
                    <div class=\"col-md-3\"><label class=\"form-label\">Window 1 End</label><input type=\"time\" class=\"form-control\" name=\"special_rules[${specialIndex}][end_1]\"></div>
                    <div class=\"col-md-3\"><label class=\"form-label\">Window 2 Start</label><input type=\"time\" class=\"form-control\" name=\"special_rules[${specialIndex}][start_2]\"></div>
                    <div class=\"col-md-3\"><label class=\"form-label\">Window 2 End</label><input type=\"time\" class=\"form-control\" name=\"special_rules[${specialIndex}][end_2]\"></div>
                </div>
            </div>
        `;

        specialContainer.insertAdjacentHTML('beforeend', html);
        bindSpecialRuleBehaviors(specialContainer.lastElementChild);
        specialIndex++;
    });

    const holidaysContainer = document.getElementById('holidaysContainer');
    const addHolidayBtn = document.getElementById('addHolidayBtn');
    let holidayIndex = holidaysContainer.querySelectorAll('.holiday-row').length;

    addHolidayBtn.addEventListener('click', function () {
        const html = `
            <div class=\"row g-2 mb-2 holiday-row\">
                <div class=\"col-md-1 col-12 d-flex align-items-center text-danger\"><i class=\"bi bi-flag-fill me-1\"></i></div>
                <div class=\"col-md-4\"><input type=\"date\" class=\"form-control\" name=\"holidays[${holidayIndex}][date]\"></div>
                <div class=\"col-md-7\"><input type=\"text\" class=\"form-control\" name=\"holidays[${holidayIndex}][name]\" placeholder=\"Holiday name\"></div>
            </div>
        `;
        holidaysContainer.insertAdjacentHTML('beforeend', html);
        holidayIndex++;
    });

    function bindSpecialRuleBehaviors(ruleElement) {
        const officeSelect = ruleElement.querySelector('.special-office-select');
        const facultyGroup = ruleElement.querySelector('.faculty-keyword-group');
        const allDaysToggle = ruleElement.querySelector('.all-days-toggle');
        const dayCheckboxes = Array.from(ruleElement.querySelectorAll('.special-day'));

        if (officeSelect && facultyGroup) {
            const refreshFacultyVisibility = () => {
                const selected = officeSelect.options[officeSelect.selectedIndex];
                const isStudentAffairs = selected && selected.dataset.studentAffairs === '1';
                facultyGroup.classList.toggle('d-none', !isStudentAffairs);
                if (!isStudentAffairs) {
                    const input = facultyGroup.querySelector('input');
                    if (input) input.value = '';
                }
            };
            officeSelect.addEventListener('change', refreshFacultyVisibility);
            refreshFacultyVisibility();
        }

        if (allDaysToggle) {
            allDaysToggle.addEventListener('change', function () {
                dayCheckboxes.forEach(cb => {
                    cb.checked = allDaysToggle.checked;
                });
            });
        }
    }

    specialContainer.querySelectorAll('.special-rule-item').forEach(bindSpecialRuleBehaviors);
});
</script>
@endsection
