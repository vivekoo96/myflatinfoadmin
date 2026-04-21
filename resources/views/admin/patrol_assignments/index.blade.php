@extends('layouts.admin')

@section('title')
    Patrol Assignments
@endsection

@section('content')

    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Patrol Assignments</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Assignments</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
            @if(session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session()->get('error') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
            @endif
            @if(session()->has('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session()->get('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
            @endif

            <div class="card">
              <div class="card-header with-border">
                <h3 class="card-title"><i class="nav-icon fas fa-user-shield"></i> Guard Patrol Assignments</h3>
                <div class="card-tools pull-right">
                  <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addAssignmentModal">
                    <i class="fa fa-plus"></i> Add Assignment
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                <table class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Guard Name</th>
                    <th>Location</th>
                    <th>Shift</th>
                    <th>Notes</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                  </thead>
                  <tbody>
                    <?php $i = 0; ?>
                  @forelse($assignments as $assignment)
                  <?php $i++; ?>
                  <tr>
                    <td>{{$i}}</td>
                    <td>{{ $assignment->guardUser->name ?? ($assignment->guardUser->first_name ?? '') }}</td>
                    <td>{{ $assignment->patrolLocation->name ?? '-' }}</td>
                    <td>{{ $assignment->buildingShift->name ?? '-' }} ({{ $assignment->buildingShift->start_time ?? '' }} - {{ $assignment->buildingShift->end_time ?? '' }})</td>
                    <td>{{ $assignment->notes ?? '-' }}</td>
                    <td>{{ $assignment->status }}</td>
                    <td>
                        <button class="btn btn-sm btn-primary edit-assignment" data-toggle="modal" data-target="#addAssignmentModal"
                          data-id="{{ $assignment->id }}" data-guard_user_id="{{ $assignment->guard_user_id }}"
                          data-patrol_location_id="{{ $assignment->patrol_location_id }}" data-building_shift_id="{{ $assignment->building_shift_id }}"
                          data-notes="{{ $assignment->notes }}" data-status="{{ $assignment->status }}">
                          <i class="fa fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-assignment" data-id="{{ $assignment->id }}">
                          <i class="fa fa-trash"></i>
                        </button>
                    </td>
                  </tr>
                  @empty
                  <tr><td colspan="7" class="text-center">No assignments found</td></tr>
                  @endforelse
                  </tbody>
                </table>
                </div>
                {{ $assignments->links() }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

<!-- Add Assignment Modal -->
<div class="modal fade" id="addAssignmentModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Patrol Assignment</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{ route('patrol-assignment.store') }}" method="post">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label class="col-form-label">Guard:</label>
            <select name="guard_user_id" id="assignment_guard_user_id" class="form-control" required>
              <option value="">-- Select Guard --</option>
              @forelse($guards as $id => $guard)
                <option value="{{ $id }}">{{ $guard->name ?? $guard->first_name }}</option>
              @empty
                <option disabled>No guards available</option>
              @endforelse
            </select>
          </div>
          <div class="form-group">
            <label class="col-form-label">Patrol Location:</label>
            <select name="patrol_location_id" id="assignment_patrol_location_id" class="form-control" required>
              <option value="">-- Select Location --</option>
              @forelse($locations as $location)
                <option value="{{ $location->id }}">{{ $location->name }}</option>
              @empty
                <option disabled>No locations available</option>
              @endforelse
            </select>
          </div>
          <div class="form-group">
            <label class="col-form-label">Shift:</label>
            <select name="building_shift_id" id="assignment_building_shift_id" class="form-control" required>
              <option value="">-- Select Shift --</option>
              @forelse($shifts as $shift)
                <option value="{{ $shift->id }}">{{ $shift->name }} ({{ $shift->start_time }} - {{ $shift->end_time }})</option>
              @empty
                <option disabled>No shifts available</option>
              @endforelse
            </select>
          </div>
          <div class="form-group">
            <label class="col-form-label">Notes:</label>
            <textarea name="notes" id="assignment_notes" class="form-control" maxlength="255" placeholder="Optional notes"></textarea>
          </div>
          <div class="form-group">
            <label class="col-form-label">Status:</label>
            <select name="status" id="assignment_status" class="form-control" required>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <input type="hidden" name="id" id="assignment_id">
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

    // Edit Assignment
    $(document).on('click', '.edit-assignment', function(){
        var id = $(this).data('id');
        $('#assignment_id').val(id);
        $('#assignment_guard_user_id').val($(this).data('guard_user_id'));
        $('#assignment_patrol_location_id').val($(this).data('patrol_location_id'));
        $('#assignment_building_shift_id').val($(this).data('building_shift_id'));
        $('#assignment_notes').val($(this).data('notes'));
        $('#assignment_status').val($(this).data('status'));
        $('#addAssignmentModal .modal-title').text('Edit Patrol Assignment');
    });

    // Reset form on new add
    $('#addAssignmentModal').on('show.bs.modal', function(e){
        var btn = $(e.relatedTarget);
        if (!btn.hasClass('edit-assignment')) {
            $('#assignment_id').val('');
            $('#addAssignmentModal form')[0].reset();
            $('#addAssignmentModal .modal-title').text('Add Patrol Assignment');
        }
    });

    // Delete Assignment
    $(document).on('click', '.delete-assignment', function(){
        var id = $(this).data('id');
        if (!confirm('Are you sure you want to delete this assignment?')) return;
        $.ajax({
            url: '{{ route("patrol-assignment.destroy", "") }}/' + id,
            type: 'DELETE',
            data: {'_token': token},
            success: function(data){
                if(data.msg === 'success') {
                    window.location.reload();
                }
            }
        });
    });
  });
</script>
@endsection

@endsection
