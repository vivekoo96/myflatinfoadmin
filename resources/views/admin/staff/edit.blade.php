@extends('layouts.admin')

@section('title')
    Edit Staff - {{ $staff->name }}
@endsection

@section('content')
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Edit Staff</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item"><a href="{{ route('admin.staff.index') }}">Staff List</a></li>
              <li class="breadcrumb-item active">Edit</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-6">
            <div class="card card-primary">
              <div class="card-header">
                <h3 class="card-title">Staff Details (ID: {{ $staff->staff_id }})</h3>
              </div>
              <form action="{{ route('admin.staff.update', $staff->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="card-body">
                  <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" value="{{ $staff->name }}" required>
                  </div>
                  <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" class="form-control" value="{{ $staff->phone }}" required>
                  </div>
                  <div class="form-group">
                    <label>Type</label>
                    <input type="text" name="type" class="form-control" value="{{ $staff->type }}" required>
                  </div>
                  <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="Active" {{ $staff->status == 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ $staff->status == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Photo</label>
                    @if($staff->photo)
                        <div class="mb-2">
                            <img src="{{ asset($staff->photo) }}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
                        </div>
                    @endif
                    <input type="file" name="photo" class="form-control-file">
                  </div>
                  <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control">{{ $staff->address }}</textarea>
                  </div>
                </div>
                <div class="card-footer">
                  <button type="submit" class="btn btn-primary">Update Staff</button>
                  <a href="{{ route('admin.staff.index') }}" class="btn btn-default">Cancel</a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
@endsection
