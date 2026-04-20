@extends('layouts.admin')

@section('title')
    Building Shifts
@endsection

@section('content')

    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Building Shifts</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Shifts</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
                <div class="">
                    @if(session()->has('error'))
                    <div class="alert alert-danger">
                        {{ session()->get('error') }}
                    </div>
                    @endif
                    @if(session()->has('success'))
                    <div class="alert alert-success">
                        {{ session()->get('success') }}
                    </div>
                    @endif
                </div>

            <div class="card">
              <div class="card-header with-border">
                <h3 class="card-title"><i class="nav-icon fas fa-clock"></i> Building Shifts</h3>
                <div class="card-tools pull-right">
                  <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addShiftModal">
                    <i class="fa fa-plus"></i> Add Shift
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Name</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                  </thead>
                  <tbody>
                    <?php $i = 0; ?>
                  @forelse($shifts as $shift)
                  <?php $i++; ?>
                  <tr>
                    <td>{{$i}}</td>
                    <td>{{ $shift->name }}</td>
                    <td>{{ $shift->start_time }}</td>
                    <td>{{ $shift->end_time }}</td>
                    <td>{{ $shift->status }}</td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-shift" data-toggle="modal" data-target="#addShiftModal"
                          data-id="{{ $shift->id }}" data-name="{{ $shift->name }}"
                          data-start_time="{{ $shift->start_time }}" data-end_time="{{ $shift->end_time }}"
                          data-status="{{ $shift->status }}">
                          <i class="fa fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-shift" data-id="{{ $shift->id }}">
                          <i class="fa fa-trash"></i>
                        </button>
                    </td>
                  </tr>
                  @empty
                  <tr><td colspan="6" class="text-center">No shifts found</td></tr>
                  @endforelse
                  </tbody>
                </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

<!-- Add Shift Modal -->
<div class="modal fade" id="addShiftModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Shift</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('building-shift.store') }}" method="post">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label class="col-form-label">Shift Name:</label>
            <input type="text" name="name" id="shift_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="col-form-label">Start Time:</label>
            <input type="time" name="start_time" id="shift_start_time" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="col-form-label">End Time:</label>
            <input type="time" name="end_time" id="shift_end_time" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="col-form-label">Status:</label>
            <select name="status" id="shift_status" class="form-control" required>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <input type="hidden" name="id" id="shift_id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

@section('script')
<script>
  $(document).ready(function(){
    var token = "{{csrf_token()}}";

    // Edit Shift
    $(document).on('click', '.edit-shift', function(){
        var id = $(this).data('id');
        $('#shift_id').val(id);
        $('#shift_name').val($(this).data('name'));
        $('#shift_start_time').val($(this).data('start_time'));
        $('#shift_end_time').val($(this).data('end_time'));
        $('#shift_status').val($(this).data('status'));
        $('#addShiftModal .modal-title').text('Edit Shift');
    });

    // Reset form on new add
    $('#addShiftModal').on('hidden.bs.modal', function(){
        if ($('#shift_id').val() === '' || $('#shift_id').val() === null) {
            $('#addShiftModal form')[0].reset();
        }
        $('#addShiftModal .modal-title').text('Add Shift');
    });

    // Delete Shift
    $(document).on('click', '.delete-shift', function(){
        var id = $(this).data('id');
        if (!confirm('Are you sure you want to delete this shift?')) return;
        $.ajax({
            url: '{{ route("building-shift.destroy", "") }}/' + id,
            type: 'DELETE',
            data: {'_token': token, 'action': 'delete'},
            success: function(data){
                if(data.msg === 'success') {
                    window.location.reload();
                }
            }
        });
    });

    // Clear on modal open
    $('#addShiftModal').on('show.bs.modal', function(e){
        var btn = $(e.relatedTarget);
        if (!btn.hasClass('edit-shift')) {
            $('#shift_id').val('');
            $('#addShiftModal form')[0].reset();
        }
    });
  });
</script>
@endsection

@endsection
