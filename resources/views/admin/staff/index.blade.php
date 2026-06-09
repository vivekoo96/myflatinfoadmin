@extends('layouts.admin')

@section('title')
    Staff Management
@endsection

@section('content')
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Staff Management</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Staff List</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">All Staff</h3>
                <div class="card-tools">
                   <a href="{{ route('admin.staff.create') }}" class="btn btn-sm btn-success"><i class="fa fa-plus"></i> Register Domestic Staff</a>
                </div>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped no-dt">
                        <thead>
                            <tr>
                                <th>Staff ID</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Assigned Flat</th>
                                <th>Docs</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffs as $staff)
                            <tr>
                                <td>{{ $staff->staff_id ?? 'N/A' }}</td>
                                <td>
                                    @if($staff->photo)
                                        <img src="{{ asset($staff->photo) }}" style="width: 40px; height: 40px; border-radius: 50%;">
                                    @else
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #eee; display: flex; align-items: center; justify-content: center;">
                                            <i class="fa fa-user"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if($staff->source === 'building_worker')
                                        {{ $staff->first_name ?? '' }} {{ $staff->last_name ?? '' }}
                                    @else
                                        {{ $staff->name }}
                                    @endif
                                </td>
                                <td>{{ $staff->phone }}</td>
                                <td>{{ $staff->type }}</td>
                                <td>
                                    <span class="badge badge-info">{{ str_replace('_', ' ', ucfirst($staff->category)) }}</span>
                                    @if($staff->source === 'building_worker')
                                        <span class="badge badge-secondary" style="margin-left: 5px;">From Other Users</span>
                                    @endif
                                </td>
                                <td>
                                    @if($staff->source === 'staff' && $staff->is_open_to_all)
                                        <span class="badge badge-success">All flats</span>
                                    @elseif($staff->source === 'staff' && $staff->activeTag && $staff->activeTag->flat)
                                        {{ optional($staff->activeTag->flat->block)->name }} - {{ $staff->activeTag->flat->name }}
                                        @if($staff->activeTag->engagement_type)
                                            <br><small class="text-muted">{{ $staff->activeTag->engagement_type }}{{ $staff->activeTag->time_slot ? ' • '.$staff->activeTag->time_slot : '' }}</small>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($staff->source === 'staff')
                                        @if($staff->document_verification)<a href="{{ asset($staff->document_verification) }}" target="_blank">Doc</a>@endif
                                        @if($staff->noc_police) <a href="{{ asset($staff->noc_police) }}" target="_blank">| NOC</a>@endif
                                        @if($staff->document_status)<br><span class="badge badge-{{ $staff->document_status === 'Verified' ? 'success' : 'warning' }}">{{ $staff->document_status }}</span>@endif
                                        @if(!$staff->document_verification && !$staff->noc_police && !$staff->document_status)-@endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $staff->status == 'Active' ? 'success' : 'danger' }}">
                                        {{ $staff->status }}
                                    </span>
                                </td>
                                <td>
                                    @if($staff->source === 'staff')
                                        <a href="{{ route('admin.staff.edit', $staff->id) }}" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a>
                                        <form action="{{ route('admin.staff.toggle-status', $staff->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            <button class="btn btn-sm btn-{{ $staff->status === 'Active' ? 'warning' : 'success' }}" title="Toggle Active/Inactive"><i class="fa fa-power-off"></i></button>
                                        </form>
                                        <form action="{{ route('admin.staff.destroy', $staff->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="fa fa-trash"></i></button>
                                        </form>
                                    @else
                                        <span class="text-muted">View in Other Users</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center">No staff found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $staffs->links() }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    {{-- Legacy add/edit modals below are no longer triggered (registration & edit
         now use the full-page create.blade.php / edit.blade.php). Kept harmless. --}}

    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('admin.staff.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Register New Staff</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Type (Maid, Cook, Driver, etc.)</label>
                            <input type="text" name="type" class="form-control" required placeholder="e.g. Maid">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" class="form-control" required>
                                <option value="external_staff">External Employee (Tracking Only)</option>
                                <option value="flat_staff">Flat Domestic Staff</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Photo</label>
                            <input type="file" name="photo" class="form-control-file">
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-control"></textarea>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" name="is_open_to_all" class="custom-control-input" id="openToAll" value="1">
                                <label class="custom-control-label" for="openToAll">Open to all flats</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success">Create Staff Details</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="editStaffForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Staff</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" name="phone" id="edit_phone" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Type</label>
                        <input type="text" name="type" id="edit_type" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status" class="form-control" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Photo</label>
                        <div id="edit_photo_preview" class="mb-2"></div>
                        <input type="file" name="photo" id="edit_photo" class="form-control-file">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" id="edit_address" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Update Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(staffId) {
    // Fetch staff data via AJAX
    $.ajax({
        url: '{{ route("admin.staff.show", ":id") }}'.replace(':id', staffId),
        type: 'GET',
        dataType: 'json',
        success: function(staff) {
            $('#edit_name').val(staff.name || '');
            $('#edit_phone').val(staff.phone || '');
            $('#edit_type').val(staff.type || '');
            $('#edit_status').val(staff.status || 'Active');
            $('#edit_address').val(staff.address || '');

            // Show photo preview if exists
            if (staff.photo) {
                $('#edit_photo_preview').html('<img src="{{ asset("") }}' + staff.photo + '" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">');
            } else {
                $('#edit_photo_preview').html('<p class="text-muted">No photo</p>');
            }

            // Update form action
            const form = document.getElementById('editStaffForm');
            form.action = '{{ url("admin/staff") }}/' + staffId;

            // Show modal
            $('#editStaffModal').modal('show');
        },
        error: function(xhr) {
            alert('Error loading staff data');
            console.error(xhr);
        }
    });
}
</script>
@endsection
