@extends('layouts.app')

@section('title', 'Service Types')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3>Service Types</h3>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
            <i class="bi bi-plus-circle"></i> Add Service Type
        </button>
    </div>

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

    <div class="card card-bordered card-preview">
        <div class="card-inner">
            <table class="datatable-init nowrap nk-tb-list nk-tb-ulist" data-auto-responsive="true">
                <thead>
                    <tr class="nk-tb-item nk-tb-head">
                        <th class="nk-tb-col">#</th>
                        <th class="nk-tb-col">Service Name</th>
                        <th class="nk-tb-col">Office</th>
                        <th class="nk-tb-col">Sub-office</th>
                        <th class="nk-tb-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($serviceTypes as $index => $service)
                    <tr class="nk-tb-item">
                        <td class="nk-tb-col">{{ $index + 1 }}</td>
                        <td class="nk-tb-col">{{ $service->name }}</td>
                        <td class="nk-tb-col">{{ $service->office->name }}</td>
                        <td class="nk-tb-col">{{ $service->subOffice?->name ?? 'None' }}</td>
                        <td class="nk-tb-col">
                            <button class="btn btn-sm btn-warning"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal{{ $service->id }}">
                                Edit
                            </button>

                            <form action="{{ route('staff.service-types.destroy', $service->id) }}"
                                method="POST" class="d-inline"
                                onsubmit="return confirm('Delete this service type?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-danger">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>

                    {{-- Edit Modal --}}
                    <div class="modal fade" id="editModal{{ $service->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" action="{{ route('staff.service-types.update', $service->id) }}">
                                @csrf
                                @method('PUT')
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Service Type</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">

                                        <div class="mb-3">
                                            <label>Office</label>
                                            <select name="office_id" id="office_id_edit_{{ $service->id }}" class="form-select" required>
                                                @foreach($offices as $office)
                                                <option value="{{ $office->id }}"
                                                    {{ $office->id == $service->office_id ? 'selected' : '' }}>
                                                    {{ $office->name }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label>Sub-office</label>
                                            <select name="sub_office_id" id="sub_office_id_edit_{{ $service->id }}" class="form-select">
                                                <option value="">None</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label>Service Name</label>
                                            <input type="text" name="name"
                                                value="{{ $service->name }}"
                                                class="form-control" required>
                                        </div>

                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Update</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No service types found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('staff.service-types.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Service Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-3">
                        <label>Office</label>
                        <select name="office_id" id="office_id_create" class="form-select" required>
                            <option value="">Select Office</option>
                            @foreach($offices as $office)
                            <option value="{{ $office->id }}">{{ $office->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Sub-office</label>
                        <select name="sub_office_id" id="sub_office_id_create" class="form-select">
                            <option value="">None</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Service Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>
@php
    $officeSubOfficeMap = $offices->mapWithKeys(fn($office) => [
        $office->id => $office->subOffices->map(fn($subOffice) => [
            'id' => $subOffice->id,
            'name' => $subOffice->name,
        ])->values(),
    ]);
@endphp
<script>
    const officeSubOfficeMap = @json($officeSubOfficeMap);

    function populateSubOfficeOptions(officeSelectId, subOfficeSelectId, selectedSubOfficeId = '') {
        const officeSelect = document.getElementById(officeSelectId);
        const subOfficeSelect = document.getElementById(subOfficeSelectId);
        const officeId = officeSelect.value;
        const subOffices = officeSubOfficeMap[officeId] || [];

        subOfficeSelect.innerHTML = '<option value="">None</option>';
        subOffices.forEach(function (subOffice) {
            const option = document.createElement('option');
            option.value = subOffice.id;
            option.textContent = subOffice.name;
            if (String(subOffice.id) === String(selectedSubOfficeId)) {
                option.selected = true;
            }
            subOfficeSelect.appendChild(option);
        });
    }

    document.getElementById('office_id_create').addEventListener('change', function () {
        populateSubOfficeOptions('office_id_create', 'sub_office_id_create');
    });

    @foreach($serviceTypes as $service)
    populateSubOfficeOptions('office_id_edit_{{ $service->id }}', 'sub_office_id_edit_{{ $service->id }}', '{{ $service->sub_office_id }}');
    document.getElementById('office_id_edit_{{ $service->id }}').addEventListener('change', function () {
        populateSubOfficeOptions('office_id_edit_{{ $service->id }}', 'sub_office_id_edit_{{ $service->id }}');
    });
    @endforeach
</script>
@endsection
