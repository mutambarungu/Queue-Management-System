@extends('layouts.app')
@section('title', 'Create Service Request')

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            {{-- Page Header --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3>Create Service Request</h3>
                <a href="{{ route('student.requests.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-arrow-left"></i> Back to Requests
                </a>
            </div>

            {{-- Success Toast --}}
            @if(session('success'))
            <div class="toast-container position-fixed top-0 end-0 p-3">
                <div class="toast align-items-center text-bg-success border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            {{ session('success') }}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            </div>
            @endif

            {{-- Request Form Card --}}
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('student.requests.store') }}" enctype="multipart/form-data">
                        @csrf

                        {{-- Office Selection --}}
                        <div class="mb-3">
                            <label class="form-label">Office</label>

                            <select id="office"
                                name="office_id"
                                class="form-select"
                                {{ request()->has('office_id') ? 'readonly' : '' }}
                                required>
                                <option value="">Select Office</option>
                                @foreach($offices as $office)
                                    <option value="{{ $office->id }}"
                                        {{ old('office_id', request('office_id')) == $office->id ? 'selected' : '' }}>
                                        {{ $office->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('office_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Sub-office (shown only when selected office has sub-offices) --}}
                        <div class="mb-3 d-none" id="sub_office_wrapper">
                            <label class="form-label">Sub-office</label>
                            <select name="sub_office_id" id="sub_office" class="form-select">
                                <option value="">Select Sub-office</option>
                            </select>
                            @error('sub_office_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Service Type --}}
                        <div class="mb-3">
                            <label class="form-label">Service Type</label>
                            <select name="service_type_id" id="service_type" class="form-select" required>
                                <option value="">Select Service Type</option>
                            </select>
                            @error('service_type_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div class="mb-3">
                            <label class="form-label">Description (Optional)</label>
                            <textarea name="description" id="description" class="form-control" rows="4">{{ old('description') }}</textarea>
                            @error('description')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Attachments --}}
                        <div class="mb-3">
                            <label class="form-label">Attachments (Optional)</label>
                            <input type="file" name="attachments[]" class="form-control" multiple>
                            <small class="text-muted">PDF, Images, Docs | Max 5MB each</small>
                            @error('attachments.*')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Submit --}}
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-send"></i> Submit Request
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Scripts --}}
<script>
document.addEventListener('DOMContentLoaded', function() {

    const officeSelect = document.getElementById('office');
    const subOfficeWrapper = document.getElementById('sub_office_wrapper');
    const subOfficeSelect = document.getElementById('sub_office');
    const serviceSelect = document.getElementById('service_type');
    const descriptionInput = document.getElementById('description');
    const oldSubOfficeId = "{{ old('sub_office_id') }}";
    const oldServiceTypeId = "{{ old('service_type_id') }}";
    const otherServiceTypeValue = '__other__';

    const officeSubOfficeMap = @json($officeSubOfficeMap);
    const officeServiceTypeMap = @json($officeServiceTypeMap);

    function renderSubOffices(officeId, selectedSubOffice = null) {
        const subOffices = officeSubOfficeMap[officeId] || [];
        subOfficeSelect.innerHTML = '<option value="">Select Sub-office</option>';

        if (!subOffices.length) {
            subOfficeWrapper.classList.add('d-none');
            subOfficeSelect.required = false;
            subOfficeSelect.value = '';
            return;
        }

        subOffices.forEach(subOffice => {
            const option = document.createElement('option');
            option.value = subOffice.id;
            option.textContent = subOffice.name;
            option.selected = String(selectedSubOffice) === String(subOffice.id);
            subOfficeSelect.appendChild(option);
        });

        subOfficeWrapper.classList.remove('d-none');
        subOfficeSelect.required = true;
    }

    function renderServiceTypes(officeId, selectedService = null) {
        const subOffices = officeSubOfficeMap[officeId] || [];
        const allTypes = officeServiceTypeMap[officeId] || [];
        const hasSubOffices = subOffices.length > 0;
        const selectedSubOffice = subOfficeSelect.value;

        const filtered = hasSubOffices
            ? allTypes.filter(type => String(type.sub_office_id || '') === String(selectedSubOffice || ''))
            : allTypes;

        serviceSelect.innerHTML = '<option value="">Select Service Type</option>';

        filtered.forEach(type => {
            const option = document.createElement('option');
            option.value = type.id;
            option.textContent = type.name;
            option.selected = String(selectedService) === String(type.id);
            serviceSelect.appendChild(option);
        });

        const otherOption = document.createElement('option');
        otherOption.value = otherServiceTypeValue;
        otherOption.textContent = 'Other (Not specified)';
        otherOption.selected = String(selectedService) === otherServiceTypeValue;
        serviceSelect.appendChild(otherOption);

        serviceSelect.disabled = false;
    }

    function handleOfficeChange(selectedSubOffice = null, selectedService = null) {
        const officeId = officeSelect.value;
        renderSubOffices(officeId, selectedSubOffice);
        renderServiceTypes(officeId, selectedService);
        toggleDescriptionRequirement();
    }

    function toggleDescriptionRequirement() {
        descriptionInput.required = serviceSelect.value === otherServiceTypeValue;
    }

    // Initial render (handles old input and office query preselect)
    handleOfficeChange(oldSubOfficeId, oldServiceTypeId);

    officeSelect.addEventListener('change', function() {
        handleOfficeChange();
    });

    subOfficeSelect.addEventListener('change', function() {
        const officeId = officeSelect.value;
        renderServiceTypes(officeId);
        toggleDescriptionRequirement();
    });

    serviceSelect.addEventListener('change', function() {
        toggleDescriptionRequirement();
    });

});
</script>
@endsection
