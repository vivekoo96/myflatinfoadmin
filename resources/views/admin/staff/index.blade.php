@extends('layouts.admin')

@section('title')
    Staff Management
@endsection

@section('style')
<style>
    .table-custom {
        width: 100%;
        margin-bottom: 1rem;
        color: #212529;
        border-collapse: collapse;
    }
    .table-custom th, .table-custom td {
        padding: 0.75rem;
        vertical-align: top;
        border-top: 1px solid #dee2e6;
    }
    .table-custom thead th {
        vertical-align: bottom;
        border-bottom: 2px solid #dee2e6;
    }
</style>
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
                   <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addStaffModal">Add External Employee</button>
                </div>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                    <table class="table-custom table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Staff ID</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Type</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffs as $staff)
                            <tr>
                                <td>{{ $staff->staff_id }}</td>
                                <td>
                                    @if($staff->photo)
                                        <img src="{{ asset($staff->photo) }}" style="width: 40px; height: 40px; border-radius: 50%;">
                                    @else
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #eee; display: flex; align-items: center; justify-content: center;">
                                            <i class="fa fa-user"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>{{ $staff->name }}</td>
                                <td>{{ $staff->phone }}</td>
                                <td>{{ $staff->type }}</td>
                                <td><span class="badge badge-info">{{ str_replace('_', ' ', ucfirst($staff->category)) }}</span></td>
                                <td>
                                    <span class="badge badge-{{ $staff->status == 'Active' ? 'success' : 'danger' }}">
                                        {{ $staff->status }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.staff.edit', $staff->id) }}" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a>
                                    <form action="{{ route('admin.staff.destroy', $staff->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center">No staff found</td>
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
                                <option value="building_staff">Building Worker (General)</option>
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
@endsection
