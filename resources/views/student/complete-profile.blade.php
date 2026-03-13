@extends('layouts.app')
@section('title', 'Complete Profile')
@section('content')

@php
$student = auth()->user()->student;
$selectedFaculty = old('faculty', $student->faculty ?? '');
$selectedDepartment = old('department', $student->department ?? '');
$selectedCampus = old('campus', $student->campus ?? '');
@endphp

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
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
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Complete Student Profile</h5>
                </div>

                <div class="card-body">
                    <p class="text-muted mb-4">
                        Please provide the missing information to continue.
                    </p>

                    <form method="POST" action="{{ route('student.profile.update', $student->user_id) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Faculty</label>
                            <select id="faculty" name="faculty" class="form-control" required>
                                <option value="">-- Select Faculty --</option>
                                <option value="Faculty of Law" {{ $selectedFaculty == 'Faculty of Law' ? 'selected' : '' }}>Faculty of Law</option>
                                <option value="Faculty of Economic Sciences &amp; Management" {{ $selectedFaculty == 'Faculty of Economic Sciences & Management' ? 'selected' : '' }}>
                                    Faculty of Economic Sciences &amp; Management
                                </option>
                                <option value="Faculty of Environmental Studies" {{ $selectedFaculty == 'Faculty of Environmental Studies' ? 'selected' : '' }}>
                                    Faculty of Environmental Studies
                                </option>
                                <option value="Faculty of Computing and Information Sciences" {{ $selectedFaculty == 'Faculty of Computing and Information Sciences' ? 'selected' : '' }}>
                                    Faculty of Computing and Information Sciences
                                </option>
                            </select>
                            @error('faculty')
                            <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select
                                id="department"
                                name="department"
                                class="form-control"
                                data-selected-department="{{ $selectedDepartment }}"
                                required
                                disabled>
                                <option value="">-- Select Faculty First --</option>
                            </select>
                            @error('department')
                            <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Campus</label>
                            <select name="campus" class="form-control" required>
                                <option value="">-- Select Campus --</option>
                                <option value="Kigali Campus" {{ $selectedCampus == 'Kigali Campus' ? 'selected' : '' }}>Kigali Campus</option>
                                <option value="Rwamagana Campus" {{ $selectedCampus == 'Rwamagana Campus' ? 'selected' : '' }}>Rwamagana Campus</option>
                                <option value="Nyanza Campus" {{ $selectedCampus == 'Nyanza Campus' ? 'selected' : '' }}>Nyanza Campus</option>
                            </select>
                            @error('campus')
                            <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone"
                                class="form-control"
                                value="{{ old('phone', $student->phone ?? '') }}"
                                required>
                            @error('phone')
                            <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <button class="btn btn-primary w-100">
                            Save & Continue
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const facultySelect = document.getElementById('faculty');
    const departmentSelect = document.getElementById('department');

    const selectedDepartment = departmentSelect.dataset.selectedDepartment || '';

    const departmentsByFaculty = {
        'Faculty of Law': [
            'Law'
        ],
        'Faculty of Economic Sciences & Management': [
            'Cooperative Management & Accounting',
            'Economics',
            'Finance',
            'Accounting',
            'Marketing & Human Resource Management'
        ],
        'Faculty of Environmental Studies': [
            'Rural Development',
            'Emergency and Disaster Management',
            'Environmental Management & Conservation'
        ],
        'Faculty of Computing and Information Sciences': [
            'Software Engineering',
            'Information Systems & Management',
            'IT-Networking',
            'IT-Multimedia'
        ]
    };

    function populateDepartments(faculty, departmentToSelect = '') {
        const departments = departmentsByFaculty[faculty] || [];

        departmentSelect.disabled = departments.length === 0;
        departmentSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = departments.length
            ? '-- Select Department --'
            : '-- Select Faculty First --';
        departmentSelect.appendChild(placeholder);

        departments.forEach(function(department) {
            const option = document.createElement('option');
            option.value = department;
            option.textContent = department;
            if (department === departmentToSelect) {
                option.selected = true;
            }
            departmentSelect.appendChild(option);
        });
    }

    populateDepartments(facultySelect.value, selectedDepartment);

    facultySelect.addEventListener('change', function() {
        populateDepartments(this.value);
    });
});
</script>
@endsection
