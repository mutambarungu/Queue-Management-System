@extends('layouts.app')
@section('title', 'User Management')
@section('content')
<style>
    .profile-id-hover {
        position: relative;
        cursor: help;
    }

    .profile-id-hover:hover::after {
        content: attr(data-name);
        position: absolute;
        left: 50%;
        bottom: 125%;
        transform: translateX(-50%);
        white-space: normal;
        max-width: min(75vw, 260px);
        background: #111827;
        color: #fff;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        text-align: center;
        word-break: break-word;
        z-index: 1000;
    }
</style>

<div class="container-fluid">
    <div class="nk-content-inner">
        <div class="nk-content-body">

            <div class="nk-block nk-block-lg">
                <div class="nk-block-head">
                    <div class="nk-block-head-content">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="nk-block-title">User Management</h4>
                            <div class="d-flex gap-2">
                                <!-- Add User Button -->
                                <button class="btn btn-primary mb-3 p-3" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">Add User</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Users Table -->
                <div class="card card-bordered card-preview">
                    <div class="card-inner">
                        <table class="datatable-init nowrap nk-tb-list nk-tb-ulist"
                            data-auto-responsive="true">
                            <thead>
                                <tr class="nk-tb-item nk-tb-head">
                                    <th class="nk-tb-col">Profile ID</th>
                                    <th class="nk-tb-col">Email</th>
                                    <th class="nk-tb-col">Verified</th>
                                    <th class="nk-tb-col">Role</th>
                                    <th class="nk-tb-col">Status</th>
                                    <th class="nk-tb-col nk-tb-col-tools text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                @php
                                $profileId = 'N/A';
                                $profileName = '';

                                if ($user->role === 'student') {
                                $profileId = $user->student?->student_number ?? $user->student_number ?? 'N/A';
                                $profileName = $user->student?->name ?? $user->name ?? '';
                                } elseif ($user->role === 'staff') {
                                $profileId = $user->staff?->staff_number ?? $user->staff_number ?? 'N/A';
                                $profileName = $user->staff?->name ?? $user->name ?? '';
                                }
                                @endphp
                                <tr class="nk-tb-item">
                                    <td class="nk-tb-col">
                                        <span
                                            class="profile-id-hover"
                                            title="{{ $profileName ?: 'No profile name' }}"
                                            data-name="{{ $profileName ?: 'No profile name' }}">{{ $profileId }}</span>
                                    </td>
                                    <td class="nk-tb-col">{{ $user->email }}</td>
                                    <td class="nk-tb-col">
                                        @if($user->email_verified_at)
                                        <span class="badge bg-success">Verified</span>
                                        @else
                                        <span class="badge bg-warning text-dark">Unverified</span>
                                        @endif
                                    </td>
                                    <td class="nk-tb-col">{{ ucfirst($user->role) }}</td>
                                    <td class="nk-tb-col">
                                        @if(!$user->is_active)
                                        <span class="badge bg-danger">Disabled</span>
                                        @else
                                        <span class="badge bg-success">Active</span>
                                        @endif
                                    </td>
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
                                                                <a role="button" class="text-warning" data-bs-toggle="modal" data-bs-target="#userModal"
                                                                    onclick='editUser(@json($user->id), @json($profileName), @json($user->email), @json($user->role))'>Edit</a>
                                                            </li>
                                                            @if(!$user->email_verified_at)
                                                            <li>
                                                                <a role="button"
                                                                    class="text-success"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#verifyUserModal{{ $user->id }}">
                                                                    <i class="bi bi-patch-check"></i> Verify
                                                                </a>
                                                            </li>
                                                            @endif
                                                            <li>
                                                                <!-- Disable / Enable -->
                                                                <a role="button" class="{{ $user->is_active ? 'text-warning' : 'text-success' }}"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#statusModal{{ $user->id }}">
                                                                    <i class="bi {{ $user->is_active ? 'bi-person-x' : 'bi-person-check' }}"></i>
                                                                    {{ $user->is_active ? 'Disable' : 'Enable' }}
                                                                </a>
                                                            </li>
                                                            <li>

                                                                <!-- Reset Password -->
                                                                <a role="button" class="text-danger"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#resetModal{{ $user->id }}">
                                                                    <i class="bi bi-key"></i>Reset Password
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <!-- Delete User -->
                                                                <a role="button" class="text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $user->id }}">Delete</a>
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
@foreach($users as $user)
@php
$profileName = $user->student?->name ?? $user->staff?->name ?? '';
@endphp
<div class="modal fade" id="verifyUserModal{{ $user->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-shield-check"></i> Verify User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center">
                <p>
                    Are you sure you want to verify this user?
                </p>

                <h6 class="fw-bold">{{ $profileName ?: 'No profile name' }}</h6>
                <small class="text-muted">{{ $user->email }}</small>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    Cancel
                </button>

                <form method="POST" action="{{ route('admin.users.verify', $user) }}">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Verify User
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="statusModal{{ $user->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    {{ $user->is_active ? 'Disable' : 'Enable' }} User
                </h5>
            </div>

            <div class="modal-body">
                Are you sure you want to
                <strong>{{ $user->is_active ? 'disable' : 'enable' }}</strong>
                {{ $profileName ?: $user->email }}?
            </div>

            <div class="modal-footer">
                <form method="POST"
                    action="{{ route('admin.users.toggle-status', $user) }}">
                    @csrf
                    @method('PATCH')

                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn {{ $user->is_active ? 'btn-warning' : 'btn-success' }}">
                        Confirm
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="resetModal{{ $user->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
            </div>

            <div class="modal-body">
                Reset password for <strong>{{ $user->email }}</strong>?
                A new password will be emailed to the user.
            </div>

            <div class="modal-footer">
                <form method="POST"
                    action="{{ route('admin.users.reset-password', $user) }}">
                    @csrf

                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger">
                        Reset Password
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal{{ $user->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Delete User</h5>
            </div>

            <div class="modal-body">
                Are you sure you want to delete user <strong>{{ $profileName ?: $user->email }}</strong>? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <form method="POST"
                    action="{{ route('admin.users.destroy', $user) }}">
                    @csrf
                    @method('DELETE')

                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach
<!-- User Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="userForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add/Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_method" id="method" value="POST">
                    <div class="mb-3">
                        <label>Profile Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Role</label>
                        <select name="role" id="role" class="form-control" required>
                            <option value="student">Student</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div id="studentFields">
                        <div class="mb-3">
                            <label>Student Number</label>
                            <input type="text" name="student_number" id="student_number" class="form-control" placeholder="12345/2026">
                        </div>
                        <div class="mb-3">
                            <label>Faculty</label>
                            <select name="faculty" id="student_faculty" class="form-control">
                                <option value="">Select Faculty</option>
                                <option value="Faculty of Law">Faculty of Law</option>
                                <option value="Faculty of Economic Sciences &amp; Management">Faculty of Economic Sciences &amp; Management</option>
                                <option value="Faculty of Environmental Studies">Faculty of Environmental Studies</option>
                                <option value="Faculty of Computing and Information Sciences">Faculty of Computing and Information Sciences</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Department</label>
                            <select name="department" id="student_department" class="form-control" disabled>
                                <option value="">Select Faculty First</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Campus</label>
                            <select name="campus" id="student_campus" class="form-control">
                                <option value="">Select Campus</option>
                                <option value="Kigali Campus">Kigali Campus</option>
                                <option value="Rwamagana Campus">Rwamagana Campus</option>
                                <option value="Nyanza Campus">Nyanza Campus</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Phone</label>
                            <input type="text" name="phone" id="student_phone" class="form-control">
                        </div>
                    </div>

                    <div id="staffFields" style="display:none;">
                        <div class="mb-3">
                            <label>Office</label>
                            <select name="office_id" id="staff_office_id" class="form-control">
                                <option value="">Select Office</option>
                                @foreach($offices as $office)
                                <option value="{{ $office->id }}">{{ $office->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Position</label>
                            <input type="text" name="position" id="staff_position" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Campus</label>
                            <select name="campus" id="staff_campus" class="form-control">
                                <option value="">Select Campus</option>
                                <option value="Kigali Campus">Kigali Campus</option>
                                <option value="Rwamagana Campus">Rwamagana Campus</option>
                                <option value="Nyanza Campus">Nyanza Campus</option>
                            </select>
                        </div>
                        <div class="mb-3" id="staffFacultyGroup" style="display:none;">
                            <label>Faculty</label>
                            <select name="faculty" id="staff_faculty" class="form-control">
                                <option value="">Select Faculty</option>
                                <option value="Faculty of Law">Faculty of Law</option>
                                <option value="Faculty of Economic Sciences &amp; Management">Faculty of Economic Sciences &amp; Management</option>
                                <option value="Faculty of Environmental Studies">Faculty of Environmental Studies</option>
                                <option value="Faculty of Computing and Information Sciences">Faculty of Computing and Information Sciences</option>
                            </select>
                        </div>
                        <div class="mb-3" id="staffDepartmentGroup" style="display:none;">
                            <label>Department</label>
                            <select name="department" id="staff_department" class="form-control" disabled>
                                <option value="">Select Faculty First</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Phone</label>
                            <input type="text" name="phone" id="staff_phone" class="form-control">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary" type="submit">Save User</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(session('success'))
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div class="toast show align-items-center text-bg-success border-0">
        <div class="d-flex">
            <div class="toast-body">
                {{ session('success') }}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
@endif
<script>
    const departmentsByFaculty = {
        'Faculty of Law': ['Law'],
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

    function populateDepartments(faculty, selectId, selectedDepartment = '') {
        const departmentSelect = document.getElementById(selectId);
        const departments = departmentsByFaculty[faculty] || [];
        departmentSelect.disabled = departments.length === 0;
        departmentSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = departments.length ? 'Select Department' : 'Select Faculty First';
        departmentSelect.appendChild(placeholder);

        departments.forEach(function(department) {
            const option = document.createElement('option');
            option.value = department;
            option.textContent = department;
            if (department === selectedDepartment) {
                option.selected = true;
            }
            departmentSelect.appendChild(option);
        });
    }

    function isStudentAffairsOfficeSelected() {
        const officeSelect = document.getElementById('staff_office_id');
        const selectedOption = officeSelect.options[officeSelect.selectedIndex];
        if (!selectedOption) {
            return false;
        }

        return selectedOption.text.trim().toLowerCase().includes('student affairs');
    }

    function toggleStaffAcademicScope() {
        const showAcademicFields = isStudentAffairsOfficeSelected();
        const staffFacultyGroup = document.getElementById('staffFacultyGroup');
        const staffDepartmentGroup = document.getElementById('staffDepartmentGroup');
        const staffFaculty = document.getElementById('staff_faculty');
        const staffDepartment = document.getElementById('staff_department');

        staffFacultyGroup.style.display = showAcademicFields ? '' : 'none';
        staffDepartmentGroup.style.display = showAcademicFields ? '' : 'none';
        staffFaculty.required = showAcademicFields;
        staffDepartment.required = showAcademicFields;

        if (!showAcademicFields) {
            staffFaculty.value = '';
            populateDepartments('', 'staff_department', '');
        } else {
            populateDepartments(staffFaculty.value, 'staff_department', staffDepartment.value);
        }
    }

    function applyRoleFields() {
        const role = document.getElementById('role').value;

        const studentFields = document.getElementById('studentFields');
        const staffFields = document.getElementById('staffFields');
        const studentFieldIds = ['student_number', 'student_faculty', 'student_department', 'student_campus', 'student_phone'];
        const staffFieldIds = ['staff_office_id', 'staff_position', 'staff_campus', 'staff_faculty', 'staff_department', 'staff_phone'];

        studentFields.style.display = role === 'student' ? '' : 'none';
        staffFields.style.display = role === 'staff' ? '' : 'none';

        studentFieldIds.forEach(function(id) {
            document.getElementById(id).disabled = role !== 'student';
        });
        staffFieldIds.forEach(function(id) {
            document.getElementById(id).disabled = role !== 'staff';
        });

        document.getElementById('student_number').required = role === 'student';
        document.getElementById('student_faculty').required = role === 'student';
        document.getElementById('student_department').required = role === 'student';
        document.getElementById('student_campus').required = role === 'student';
        document.getElementById('name').required = role === 'student' || role === 'staff';
        document.getElementById('name').disabled = role === 'admin';

        if (role === 'admin') {
            document.getElementById('name').value = '';
        }

        if (role === 'student') {
            populateDepartments(document.getElementById('student_faculty').value, 'student_department', document.getElementById('student_department').value);
        }
        if (role === 'staff') {
            toggleStaffAcademicScope();
        }
    }

    function resetForm() {
        document.getElementById('userForm').action = "{{ route('admin.users.store') }}";
        document.getElementById('method').value = 'POST';
        document.getElementById('name').value = '';
        document.getElementById('email').value = '';
        document.getElementById('role').value = 'student';
        document.getElementById('student_number').value = '';
        document.getElementById('student_faculty').value = '';
        populateDepartments('', 'student_department', '');
        document.getElementById('student_campus').value = '';
        document.getElementById('student_phone').value = '';
        document.getElementById('staff_office_id').value = '';
        document.getElementById('staff_position').value = '';
        document.getElementById('staff_campus').value = '';
        document.getElementById('staff_faculty').value = '';
        populateDepartments('', 'staff_department', '');
        document.getElementById('staff_phone').value = '';
        document.getElementById('password').required = true;
        document.getElementById('password_confirmation').required = true;
        applyRoleFields();
    }

    function editUser(id, profileName, email, role) {
        document.getElementById('userForm').action = "/admin/users/" + id;
        document.getElementById('method').value = 'PUT';
        document.getElementById('name').value = profileName || '';
        document.getElementById('email').value = email;
        document.getElementById('role').value = role;
        document.getElementById('password').required = false;
        document.getElementById('password_confirmation').required = false;
        applyRoleFields();
    }

    document.getElementById('role').addEventListener('change', applyRoleFields);
    document.getElementById('student_faculty').addEventListener('change', function() {
        populateDepartments(this.value, 'student_department', '');
    });
    document.getElementById('staff_faculty').addEventListener('change', function() {
        populateDepartments(this.value, 'staff_department', '');
    });
    document.getElementById('staff_office_id').addEventListener('change', toggleStaffAcademicScope);

    applyRoleFields();
</script>
@endsection