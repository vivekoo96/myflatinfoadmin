@extends('layouts.admin')

@section('title')
    Building Shifts
@endsection

@section('content')

    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>🔐 Shifts & Guard Assignments</h1>
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
                </div>

            <!-- Building Shifts Table -->
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

            <!-- Guard Assignments Section -->
            <div class="row mt-4">
              <!-- Assign Guard Card -->
              <div class="col-md-6">
                <div class="card card-primary">
                  <div class="card-header with-border">
                    <h3 class="card-title"><i class="fas fa-user-plus"></i> Assign Guard to Gate & Shift</h3>
                  </div>
                  <form action="{{ route('patrol-assignment.store') }}" method="post">
                    @csrf
                    <div class="card-body">
                      <div class="form-group">
                        <label>Guard <span class="text-danger">*</span></label>
                        <select name="guard_user_id" id="guard_user_id" class="form-control" required>
                          <option value="">-- Select Guard --</option>
                          @forelse($guards as $id => $guard)
                            <option value="{{ $id }}">{{ $guard->name ?? $guard->first_name }}</option>
                          @empty
                            <option disabled>No guards available</option>
                          @endforelse
                        </select>
                      </div>

                      <div class="form-group">
                        <label>Gate <span class="text-danger">*</span></label>
                        <select name="gate_id" id="gate_id" class="form-control" required>
                          <option value="">-- Select Gate --</option>
                          @forelse($gates as $gate)
                            <option value="{{ $gate->id }}">{{ $gate->name }}</option>
                          @empty
                            <option disabled>No gates available</option>
                          @endforelse
                        </select>
                      </div>

                      <div class="form-group">
                        <label>Shift <span class="text-danger">*</span></label>
                        <select name="building_shift_id" id="building_shift_id" class="form-control" required>
                          <option value="">-- Select Shift --</option>
                          @forelse($shifts as $shift)
                            <option value="{{ $shift->id }}">{{ $shift->name }} ({{ $shift->start_time }} - {{ $shift->end_time }})</option>
                          @empty
                            <option disabled>No shifts available</option>
                          @endforelse
                        </select>
                      </div>

                      <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" id="notes" class="form-control" maxlength="255" placeholder="Optional notes"></textarea>
                      </div>

                      <div class="form-group">
                        <label>Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-control" required>
                          <option value="Active">Active</option>
                          <option value="Inactive">Inactive</option>
                        </select>
                      </div>

                      <input type="hidden" name="id" id="assignment_id">
                    </div>
                    <div class="card-footer">
                      <button type="submit" class="btn btn-primary" id="submit-btn">Assign Guard</button>
                      <button type="button" class="btn btn-secondary" id="cancel-btn" style="display:none;">Cancel Edit</button>
                    </div>
                  </form>
                </div>
              </div>

              <!-- Active Assignments Card -->
              <div class="col-md-6">
                <div class="card card-info">
                  <div class="card-header with-border">
                    <h3 class="card-title"><i class="fas fa-list"></i> Active Assignments ({{ $assignments->count() }})</h3>
                  </div>
                  <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                    @forelse($assignments as $assignment)
                    <div class="assignment-card" style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 4px;">
                      <div style="display: flex; justify-content: space-between; align-items: start;">
                        <div>
                          <strong>{{ $assignment->guardUser->name ?? $assignment->guardUser->first_name }}</strong><br>
                          <small style="color: #666;">
                            🚪 {{ $assignment->gate->name ?? '-' }}<br>
                            🕐 {{ $assignment->buildingShift->name ?? '-' }} ({{ $assignment->buildingShift->start_time ?? '' }} - {{ $assignment->buildingShift->end_time ?? '' }})<br>
                            @if($assignment->notes)
                              📝 {{ $assignment->notes }}
                            @endif
                          </small>
                        </div>
                        <div>
                          <button type="button" class="btn btn-xs btn-warning edit-assignment"
                            data-id="{{ $assignment->id }}"
                            data-guard_user_id="{{ $assignment->guard_user_id }}"
                            data-gate_id="{{ $assignment->gate_id }}"
                            data-building_shift_id="{{ $assignment->building_shift_id }}"
                            data-notes="{{ $assignment->notes }}"
                            data-status="{{ $assignment->status }}">
                            ✏️
                          </button>
                          <button type="button" class="btn btn-xs btn-danger delete-assignment" data-id="{{ $assignment->id }}">
                            🗑️
                          </button>
                        </div>
                      </div>
                    </div>
                    @empty
                    <p style="color: #999; text-align: center;">No active assignments yet</p>
                    @endforelse
                  </div>
                </div>
              </div>
            </div>

            <!-- Assignment Summary -->
            <div class="row mt-4">
              <div class="col-md-12">
                <div class="card card-success">
                  <div class="card-header with-border">
                    <h3 class="card-title"><i class="fas fa-chart-pie"></i> Assignment Summary</h3>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      @php
                        $summary = [];
                        foreach($assignments as $a) {
                          $shift = $a->buildingShift->name ?? 'Unknown';
                          $gate = $a->gate->name ?? 'Unknown';
                          if(!isset($summary[$shift])) $summary[$shift] = [];
                          if(!isset($summary[$shift][$gate])) $summary[$shift][$gate] = 0;
                          $summary[$shift][$gate]++;
                        }
                      @endphp

                      @if(count($summary) == 0)
                        <div class="col-md-12">
                          <p style="color: #999; text-align: center;">No assignment data to display</p>
                        </div>
                      @else
                        @foreach($summary as $shift => $gates)
                        <div class="col-md-6 mb-4">
                          <div style="border-left: 4px solid #007bff; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                            <h5 style="margin: 0 0 10px 0;">
                              <i class="fas fa-clock"></i> {{ $shift }}
                            </h5>
                            <ul style="margin: 0; padding-left: 20px;">
                              @foreach($gates as $gate => $count)
                                <li>
                                  <strong>{{ $count }}</strong> guard{{ $count > 1 ? 's' : '' }} at <strong>{{ $gate }}</strong>
                                </li>
                              @endforeach
                            </ul>
                          </div>
                        </div>
                        @endforeach
                      @endif
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- All Assignments Table -->
            <div class="row mt-4">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-header with-border">
                    <h3 class="card-title"><i class="fas fa-table"></i> All Assignments</h3>
                  </div>
                  <div class="card-body">
                    <div class="table-responsive">
                      <table class="table table-bordered table-striped">
                        <thead>
                          <tr>
                            <th>S No</th>
                            <th>Guard</th>
                            <th>Gate</th>
                            <th>Shift</th>
                            <th>Notes</th>
                            <th>Status</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          @forelse($assignments as $i => $assignment)
                          <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $assignment->guardUser->name ?? ($assignment->guardUser->first_name ?? '-') }}</td>
                            <td>{{ $assignment->gate->name ?? '-' }}</td>
                            <td>
                              {{ $assignment->buildingShift->name ?? '-' }}<br>
                              <small style="color: #999;">{{ $assignment->buildingShift->start_time ?? '' }} - {{ $assignment->buildingShift->end_time ?? '' }}</small>
                            </td>
                            <td>{{ $assignment->notes ?? '-' }}</td>
                            <td>
                              <span class="badge badge-{{ $assignment->status == 'Active' ? 'success' : 'danger' }}">
                                {{ $assignment->status }}
                              </span>
                            </td>
                            <td>
                              <button class="btn btn-sm btn-warning edit-assignment"
                                data-id="{{ $assignment->id }}"
                                data-guard_user_id="{{ $assignment->guard_user_id }}"
                                data-gate_id="{{ $assignment->gate_id }}"
                                data-building_shift_id="{{ $assignment->building_shift_id }}"
                                data-notes="{{ $assignment->notes }}"
                                data-status="{{ $assignment->status }}">
                                ✏️ Edit
                              </button>
                              <button class="btn btn-sm btn-danger delete-assignment" data-id="{{ $assignment->id }}">
                                🗑️ Delete
                              </button>
                            </td>
                          </tr>
                          @empty
                          <tr>
                            <td colspan="7" class="text-center" style="color: #999;">No assignments found</td>
                          </tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>
                  </div>
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

    // Assignment JS
    $(document).on('click', '.edit-assignment', function(){
        $('html, body').animate({ scrollTop: 0 }, 500);
        var id = $(this).data('id');
        $('#assignment_id').val(id);
        $('#guard_user_id').val($(this).data('guard_user_id'));
        $('#gate_id').val($(this).data('gate_id'));
        $('#building_shift_id').val($(this).data('building_shift_id'));
        $('#notes').val($(this).data('notes'));
        $('#status').val($(this).data('status') || 'Active');
        $('#submit-btn').text('Update Assignment');
        $('#cancel-btn').show();
    });

    $(document).on('click', '#cancel-btn', function(){
        $('#assignment_id').val('');
        $('form').not('[action*="building-shift"]')[0].reset();
        $('#submit-btn').text('Assign Guard');
        $('#cancel-btn').hide();
    });

    $(document).on('click', '.delete-assignment', function(){
        var id = $(this).data('id');
        if (!confirm('Are you sure you want to remove this assignment?')) return;
        $.ajax({
            url: '{{ route("patrol-assignment.destroy", "") }}/' + id,
            type: 'DELETE',
            data: {'_token': token},
            success: function(data){
                if(data.msg === 'success') {
                    location.reload();
                }
            },
            error: function(){
                alert('Error deleting assignment');
            }
        });
    });
  });
</script>
@endsection

@endsection
