@extends('layouts.app')
@section('title', 'Staff Management')
@section('content')

<div class="container-fluid">
    <div class="nk-content-inner">
        <div class="nk-content-body">

            <div class="nk-block nk-block-lg">
                <div class="nk-block-head">
                    <div class="nk-block-head-content">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="nk-block-title">Staff Management</h4>
                            <div class="d-flex gap-2">
                                @if(session('success'))
                                <div class="alert alert-success">{{ session('success') }}</div>
                                @endif

                                <button class="btn btn-primary mb-3 p-3" data-bs-toggle="modal" data-bs-target="#staffModal" onclick="resetForm()">Add Staff</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Offices Table -->
                <div class="card card-bordered card-preview">
                    <div class="card-inner">
                        <table class="datatable-init nowrap nk-tb-list nk-tb-ulist" data-auto-responsive="true">
                            <thead>
                                <tr class="nk-tb-item nk-tb-head">
                                    <th class="nk-tb-col">Name</th>
                                    <th class="nk-tb-col">Email</th>
                                    <th class="nk-tb-col">Office</th>
                                    <th class="nk-tb-col">Campus</th>
                                    <th class="nk-tb-col">Position</th>
                                    <th class="nk-tb-col nk-tb-col-tools text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($staffs as $staff)
                                <tr class="nk-tb-item">
                                    <td class="nk-tb-col">{{ $staff->name ?? 'N/A' }}</td>
                                    <td class="nk-tb-col">{{ $staff->user->email }}</td>
                                    <td class="nk-tb-col">{{ $staff->office?->name }}</td>
                                    <td class="nk-tb-col">{{ $staff->campus ?? 'N/A' }}</td>
                                    <td class="nk-tb-col">{{ $staff->position }}</td>
                                    <td class="nk-tb-col nk-tb-col-tools">
                                        <ul class="nk-tb-actions gx-1">
                                            <li>
                                                <div class="drodown">
                                                    <a href="#"
                                                        class="dropdown-toggle btn btn-icon btn-trigger"
                                                        data-bs-toggle="dropdown">
                                                        <em class="icon ni ni-more-h"></em>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <ul class="link-list-opt no-bdr">
                                                            <li>
                                                                <a role="button" class="text-warning" data-bs-toggle="modal" data-bs-target="#staffModal"
                                                                    onclick='editStaff(@json($staff->staff_number), @json($staff->name), @json($staff->user->email), @json($staff->office_id), @json($staff->campus), @json($staff->faculty), @json($staff->department), @json($staff->position), @json($staff->phone))'>Edit</a>
                                                            </li>

                                                            <li>
                                                                <!-- Delete Staff -->
                                                                <a role="button" class="text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $staff->staff_number }}">Delete</a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Staff Modal -->
<div class="modal fade" id="staffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="staffForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add/Edit Staff</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_method" id="method" value="POST">

                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Office</label>
                        <select name="office_id" id="office_id" class="form-control">
                            <option value="">Select Office</option>
                            @foreach($offices as $office)
                            <option value="{{ $office->id }}">{{ $office->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Position</label>
                        <input type="text" name="position" id="position" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Campus</label>
                        <select name="campus" id="campus" class="form-control">
                            <option value="">Select Campus</option>
                            <option value="Kigali Campus">Kigali Campus</option>
                            <option value="Rwamagana Campus">Rwamagana Campus</option>
                            <option value="Nyanza Campus">Nyanza Campus</option>
                        </select>
                    </div>

                    <div class="mb-3" id="facultyGroup" style="display: none;">
                        <label>Faculty</label>
                        <select name="faculty" id="faculty" class="form-control">
                            <option value="">Select Faculty</option>
                            <option value="Faculty of Law">Faculty of Law</option>
                            <option value="Faculty of Economic Sciences &amp; Management">Faculty of Economic Sciences &amp; Management</option>
                            <option value="Faculty of Environmental Studies">Faculty of Environmental Studies</option>
                            <option value="Faculty of Computing and Information Sciences">Faculty of Computing and Information Sciences</option>
                        </select>
                    </div>

                    <div class="mb-3" id="departmentGroup" style="display: none;">
                        <label>Department</label>
                        <select name="department" id="department" class="form-control" disabled>
                            <option value="">Select Faculty First</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Phone</label>
                        <input type="text" name="phone" id="phone" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" id="password" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit">Save Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function resetForm() {
        document.getElementById('staffForm').action = "{{ route('admin.staff.store') }}";
        document.getElementById('method').value = 'POST';
        document.getElementById('name').value = '';
        document.getElementById('email').value = '';
        document.getElementById('office_id').value = '';
        document.getElementById('campus').value = '';
        document.getElementById('faculty').value = '';
        populateDepartments('', '');
        toggleAcademicScopeFields();
        document.getElementById('position').value = '';
        document.getElementById('phone').value = '';
        document.getElementById('password').required = true;
        document.getElementById('password_confirmation').required = true;
    }

    function editStaff(staffNumber, name, email, office_id, campus, faculty, department, position, phone) {
        document.getElementById('staffForm').action = "/admin/staff/" + encodeURIComponent(staffNumber);
        document.getElementById('method').value = 'PUT';
        document.getElementById('name').value = name;
        document.getElementById('email').value = email;
        document.getElementById('office_id').value = office_id;
        document.getElementById('campus').value = campus;
        toggleAcademicScopeFields();
        document.getElementById('faculty').value = faculty || '';
        if (isStudentAffairsOfficeSelected()) {
            populateDepartments(faculty, department);
        } else {
            populateDepartments('', '');
        }
        document.getElementById('position').value = position;
        document.getElementById('phone').value = phone;
        document.getElementById('password').required = false;
        document.getElementById('password_confirmation').required = false;
    }

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
            'Information Technology',
            'IT-Multimedia'
        ]
    };

    function populateDepartments(faculty, selectedDepartment = '') {
        const departmentSelect = document.getElementById('department');
        const departments = departmentsByFaculty[faculty] || [];

        departmentSelect.disabled = departments.length === 0;
        departmentSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = departments.length ? 'Select Department' : 'Select Faculty First';
        departmentSelect.appendChild(placeholder);

        departments.forEach(function (department) {
            const option = document.createElement('option');
            option.value = department;
            option.textContent = department;
            if (department === selectedDepartment) {
                option.selected = true;
            }
            departmentSelect.appendChild(option);
        });
    }

    document.getElementById('faculty').addEventListener('change', function () {
        populateDepartments(this.value, '');
    });

    function isStudentAffairsOfficeSelected() {
        const officeSelect = document.getElementById('office_id');
        const selectedOption = officeSelect.options[officeSelect.selectedIndex];
        if (!selectedOption) {
            return false;
        }

        return selectedOption.text.trim().toLowerCase().includes('student affairs');
    }

    function toggleAcademicScopeFields() {
        const facultyGroup = document.getElementById('facultyGroup');
        const departmentGroup = document.getElementById('departmentGroup');
        const facultySelect = document.getElementById('faculty');
        const departmentSelect = document.getElementById('department');

        const showAcademicFields = isStudentAffairsOfficeSelected();

        facultyGroup.style.display = showAcademicFields ? '' : 'none';
        departmentGroup.style.display = showAcademicFields ? '' : 'none';

        facultySelect.required = showAcademicFields;
        departmentSelect.required = showAcademicFields;

        if (!showAcademicFields) {
            facultySelect.value = '';
            populateDepartments('', '');
        } else {
            populateDepartments(facultySelect.value, departmentSelect.value);
        }
    }

    document.getElementById('office_id').addEventListener('change', function () {
        toggleAcademicScopeFields();
    });
</script>
@endsection
