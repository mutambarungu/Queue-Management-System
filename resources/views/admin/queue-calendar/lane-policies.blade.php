@extends('layouts.app')
@section('title', 'Lane Policies')
@section('content')
<div class="container-fluid">
    <div class="nk-content-inner">
        <div class="nk-content-body">
            <div class="nk-block nk-block-lg">
                <div class="nk-block-head">
                    <div class="nk-block-head-content d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="nk-block-title mb-2">Lane Policies</h4>
                            <p class="text-muted mb-0">Set appointment, online, and walk-in serving split per lane. Recall timeout is the number of seconds after a call before that token is treated as no-show.</p>
                        </div>
                        <a href="{{ route('admin.queue-calendar.index') }}" class="btn btn-outline-dark btn-sm">Queue Calendar</a>
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

                        <form method="POST" action="{{ route('admin.lane-policies.update') }}">
                            @csrf
                            @method('PUT')

                            <div id="lanePoliciesContainer">
                                @php
                                    $policyRows = old('lane_policies', $lanePolicies ?? []);
                                    if (empty($policyRows)) {
                                        $policyRows = [[
                                            'office_id' => '',
                                            'sub_office_id' => '',
                                            'appointment_quota' => 1,
                                            'online_quota' => 1,
                                            'walk_in_quota' => 2,
                                            'recall_timeout_seconds' => 90,
                                        ]];
                                    }
                                @endphp
                                @foreach($policyRows as $i => $policy)
                                    @php
                                        $selectedOfficeId = (int) ($policy['office_id'] ?? 0);
                                        $policySubOffices = $offices->firstWhere('id', $selectedOfficeId)?->subOffices ?? collect();
                                    @endphp
                                    <div class="border rounded p-3 mb-3 lane-policy-item">
                                        <div class="row g-3">
                                            <div class="col-md-2">
                                                <label class="form-label">Office</label>
                                                <select name="lane_policies[{{ $i }}][office_id]" class="form-select lane-policy-office">
                                                    <option value="">Select Office</option>
                                                    @foreach($offices as $office)
                                                        <option value="{{ $office->id }}" {{ $selectedOfficeId === (int) $office->id ? 'selected' : '' }}>{{ $office->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 lane-policy-sub-wrap {{ $policySubOffices->isEmpty() ? 'd-none' : '' }}">
                                                <label class="form-label">Sub-office (optional)</label>
                                                <select name="lane_policies[{{ $i }}][sub_office_id]" class="form-select lane-policy-sub">
                                                    <option value="">General lane</option>
                                                    @foreach($policySubOffices as $subOffice)
                                                        <option value="{{ $subOffice->id }}" {{ (int) ($policy['sub_office_id'] ?? 0) === (int) $subOffice->id ? 'selected' : '' }}>{{ $subOffice->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Appointment Slots</label>
                                                <input type="number" min="1" class="form-control" name="lane_policies[{{ $i }}][appointment_quota]" value="{{ $policy['appointment_quota'] ?? 1 }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Online Slots</label>
                                                <input type="number" min="1" class="form-control" name="lane_policies[{{ $i }}][online_quota]" value="{{ $policy['online_quota'] ?? 1 }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Walk-in Slots</label>
                                                <input type="number" min="1" class="form-control" name="lane_policies[{{ $i }}][walk_in_quota]" value="{{ $policy['walk_in_quota'] ?? 2 }}">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Recall Timeout (sec)</label>
                                                <input type="number" min="15" class="form-control" name="lane_policies[{{ $i }}][recall_timeout_seconds]" value="{{ $policy['recall_timeout_seconds'] ?? 90 }}">
                                                <small class="text-muted d-block mt-1">If not served/recalled before this limit, token is flagged as no-show.</small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-lane-add" id="addLanePolicyBtn">
                                    <i class="bi bi-plus-circle me-1"></i> Add Lane Policy
                                </button>
                                <button type="submit" class="btn btn-lane-save">
                                    <i class="bi bi-check2-circle me-1"></i> Save Lane Policies
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-lane-add {
        border: 1px solid #94a3b8;
        background: #f8fafc;
        color: #1e293b;
        font-weight: 600;
        padding: .55rem .9rem;
        border-radius: .55rem;
    }
    .btn-lane-add:hover {
        background: #eef2ff;
        border-color: #64748b;
        color: #0f172a;
    }
    .btn-lane-save {
        border: 1px solid #94a3b8;
        background: #f8fafc;
        color: #1e293b;
        font-weight: 600;
        padding: .55rem .9rem;
        border-radius: .55rem;
    }
    .btn-lane-save:hover {
         background: #eef2ff;
        border-color: #64748b;
        color: #0f172a;
    }
    body.dark-mode .btn-lane-add {
        background: #111827;
        border-color: #334155;
        color: #e5e7eb;
    }
    body.dark-mode .btn-lane-add:hover {
        background: #1f2937;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const offices = @json($officesData);
    const officeSubMap = @json($offices->mapWithKeys(fn($office) => [$office->id => $office->subOffices->map(fn($sub) => ['id' => $sub->id, 'name' => $sub->name])->values()]));
    const lanePoliciesContainer = document.getElementById('lanePoliciesContainer');
    const addLanePolicyBtn = document.getElementById('addLanePolicyBtn');
    let lanePolicyIndex = lanePoliciesContainer.querySelectorAll('.lane-policy-item').length;

    function bindLanePolicyRow(row) {
        const officeSelect = row.querySelector('.lane-policy-office');
        const subWrap = row.querySelector('.lane-policy-sub-wrap');
        const subSelect = row.querySelector('.lane-policy-sub');

        const renderSubs = () => {
            const items = officeSubMap[officeSelect.value] || [];
            subSelect.innerHTML = '<option value="">General lane</option>';
            if (!items.length) {
                subWrap.classList.add('d-none');
                return;
            }
            items.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name;
                subSelect.appendChild(option);
            });
            subWrap.classList.remove('d-none');
        };

        officeSelect.addEventListener('change', renderSubs);
        renderSubs();
    }

    addLanePolicyBtn.addEventListener('click', function () {
        const officeOptions = ['<option value="">Select Office</option>']
            .concat(offices.map(o => `<option value="${o.id}">${o.name}</option>`))
            .join('');

        const html = `
            <div class="border rounded p-3 mb-3 lane-policy-item">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">Office</label>
                        <select name="lane_policies[${lanePolicyIndex}][office_id]" class="form-select lane-policy-office">${officeOptions}</select>
                    </div>
                    <div class="col-md-2 lane-policy-sub-wrap d-none">
                        <label class="form-label">Sub-office (optional)</label>
                        <select name="lane_policies[${lanePolicyIndex}][sub_office_id]" class="form-select lane-policy-sub"><option value="">General lane</option></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Appointment Slots</label>
                        <input type="number" min="1" class="form-control" name="lane_policies[${lanePolicyIndex}][appointment_quota]" value="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Online Slots</label>
                        <input type="number" min="1" class="form-control" name="lane_policies[${lanePolicyIndex}][online_quota]" value="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Walk-in Slots</label>
                        <input type="number" min="1" class="form-control" name="lane_policies[${lanePolicyIndex}][walk_in_quota]" value="2">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Recall Timeout (sec)</label>
                        <input type="number" min="15" class="form-control" name="lane_policies[${lanePolicyIndex}][recall_timeout_seconds]" value="90">
                        <small class="text-muted d-block mt-1">No-show threshold after call.</small>
                    </div>
                </div>
            </div>
        `;

        lanePoliciesContainer.insertAdjacentHTML('beforeend', html);
        bindLanePolicyRow(lanePoliciesContainer.lastElementChild);
        lanePolicyIndex++;
    });

    lanePoliciesContainer.querySelectorAll('.lane-policy-item').forEach(bindLanePolicyRow);
});
</script>
@endsection
