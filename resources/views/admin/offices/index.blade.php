@extends('layouts.app')
@section('title', 'Offices')
@section('content')

<div class="container-fluid">
    <div class="nk-content-inner">
        <div class="nk-content-body">

            <div class="nk-block nk-block-lg">
                <div class="nk-block-head">
                    <div class="nk-block-head-content">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h4 class="nk-block-title">Offices</h4>
                                <div class="d-flex gap-2">
                                    <!-- Add Office Button -->
                                    <button class="btn btn-primary mb-3 p-3" data-bs-toggle="modal" data-bs-target="#officeModal" onclick="resetForm()">Add Office</button>
                                </div>
                        </div>
                    </div>
                </div>

                <!-- Offices Table -->
                <div class="card card-bordered card-preview">
                    <div class="card-inner">
                        <table class="datatable-init nowrap nk-tb-list nk-tb-ulist"
                            data-auto-responsive="true">
                            <thead>
                                <tr class="nk-tb-item nk-tb-head">
                                    <th class="nk-tb-col">Name</th>
                                    <th class="nk-tb-col">Description</th>
                                    <th class="nk-tb-col">Sub-offices</th>
                                    <th class="nk-tb-col">Number of Staff</th>
                                    <th class="nk-tb-col nk-tb-col-tools text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($offices as $office)
                                <tr class="nk-tb-item">
                                    <td class="nk-tb-col">{{ $office->name }}</td>
                                    <td class="nk-tb-col">{{ $office->description }}</td>
                                    <td class="nk-tb-col">{{ $office->subOffices->pluck('name')->implode(', ') ?: 'None' }}</td>
                                    <td class="nk-tb-col">{{ $office->staff->count() }}</td>
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
                                                                <a role="button" class="text-warning" data-bs-toggle="modal" data-bs-target="#officeModal"
                                                                    onclick='editOffice(@json($office->id), @json($office->name), @json($office->description), @json($office->subOffices->pluck("name")->implode("\n")))'>Edit</a>
                                                            </li>

                                                            <li>
                                                                <!-- Delete Office -->
                                                                <a role="button" class="text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $office->id }}">Delete</a>
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

@foreach($offices as $office)
<!-- Delete Office Modal -->
<div class="modal fade" id="deleteModal{{ $office->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" action="{{ route('admin.offices.destroy', $office->id) }}">
                @csrf
                @method('DELETE')
                <div class="modal-header bg-danger bg-opacity-10 border-0 rounded-top-4">
                    <h5 class="modal-title fw-semibold">
                        <i class="bi bi-trash me-2"></i>
                        Delete Office
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    Are you sure you want to delete the office "<strong>{{ $office->name }}</strong>"?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" type="submit">Delete Office</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
<!-- Office Modal -->
<div class="modal fade" id="officeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="officeForm" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add/Edit Office</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_method" id="method" value="POST">
                    <div class="mb-3">
                        <label>Name</label>
                        <input type="text" name="name" id="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Sub-offices (optional)</label>
                        <input type="text" name="sub_offices" id="sub_offices" class="form-control" placeholder="Enter sub-office if any">
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" id="description" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Office</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function resetForm() {
        document.getElementById('officeForm').action = "{{ route('admin.offices.store') }}";
        document.getElementById('method').value = 'POST';
        document.getElementById('name').value = '';
        document.getElementById('description').value = '';
        document.getElementById('sub_offices').value = '';
    }

    function editOffice(id, name, description, subOfficesText) {
        document.getElementById('officeForm').action = "/admin/offices/" + id;
        document.getElementById('method').value = 'PUT';
        document.getElementById('name').value = name;
        document.getElementById('description').value = description;
        document.getElementById('sub_offices').value = subOfficesText || '';
    }
</script>
@endsection
