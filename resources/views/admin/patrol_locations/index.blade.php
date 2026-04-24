@extends('layouts.admin')

@section('title')
    Patrol Locations
@endsection

@section('css')
<style>
  @media print {
    * { margin: 0; padding: 0; }
    body { background: white; }
    .content-wrapper, .main-header, .main-sidebar,
    .card, .table-responsive, .modal-footer,
    .modal-header .close, .card-tools,
    .breadcrumb, .content-header,
    .modal-dialog { display: none !important; }

    .modal { position: static !important; }
    .modal-content {
      box-shadow: none !important;
      border: none !important;
    }

    #qrModal.show { display: block !important; }
    .modal.show .modal-dialog {
      display: block !important;
      width: 100%;
      margin: 0;
    }

    #qrcode-display {
      display: flex !important;
      justify-content: center;
      align-items: center;
      padding: 40px;
      background: white;
    }

    #qr-location-name {
      text-align: center;
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 20px;
      padding: 20px;
    }

    .modal-body {
      display: block !important;
      padding: 0;
      text-align: center;
    }
  }
</style>
@endsection

@section('content')
    <!-- Content Header -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-md-12">
                @if(session()->has('error'))
                <div class="alert alert-danger">{{ session()->get('error') }}</div>
                @endif
                @if(session()->has('success'))
                <div class="alert alert-success">{{ session()->get('success') }}</div>
                @endif
            </div>
          <div class="col-sm-6">
            <h1>Patrol Locations</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Patrol Locations</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header with-border">
                <h3 class="card-title"><i class="nav-icon fas fa-map-marker-alt"></i> Patrol Locations</h3>
                <div class="card-tools pull-right">
                  @if(Auth::User()->role == 'BA')
                  <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addLocationModal">
                    <i class="fa fa-plus"></i> Add Location
                  </button>
                  @endif
                </div>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>QR Code</th>
                    @if(Auth::User()->role == 'BA')
                    <th>Actions</th>
                    @endif
                  </tr>
                  </thead>
                  <tbody>
                    <?php $i = 0; ?>
                  @forelse($locations->whereNull('gate_id') as $location)
                  <?php $i++; ?>
                  <tr>
                    <td>{{$i}}</td>
                    <td>{{ $location->name }}</td>
                    <td>{{ $location->description ?? '-' }}</td>
                    <td>{{ $location->status }}</td>
                    <td><button class="btn btn-sm btn-info view-qr" data-id="{{ $location->id }}">View QR</button></td>
                    @if(Auth::User()->role == 'BA')
                    <td>
                        <button class="btn btn-sm btn-primary edit-location" data-toggle="modal" data-target="#addLocationModal"
                          data-id="{{ $location->id }}" data-name="{{ $location->name }}"
                          data-description="{{ $location->description }}" data-status="{{ $location->status }}">
                          <i class="fa fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-location" data-id="{{ $location->id }}">
                          <i class="fa fa-trash"></i>
                        </button>
                    </td>
                    @endif
                  </tr>
                  @empty
                  @endforelse
                  </tbody>
                </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Patrol Location Assignments Section -->
          <div class="col-md-12 mt-4">
            <div class="card">
              <div class="card-header with-border">
                <h3 class="card-title"><i class="nav-icon fas fa-route"></i> Patrol Schedule (Today's Progress)</h3>
                <div class="card-tools pull-right">
                  @if(Auth::User()->role == 'BA')
                  <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#assignLocationModal">
                    <i class="fa fa-plus"></i> Add Schedule
                  </button>
                  @endif
                </div>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                <table class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Gate</th>
                    <th>Shift</th>
                    <th>Patrol Time</th>
                    <th>Progress Today</th>
                    <th>Status</th>
                    @if(Auth::User()->role == 'BA')
                    <th>Actions</th>
                    @endif
                  </tr>
                  </thead>
                  <tbody>
                    <?php $j = 0; ?>
                  @forelse($locations->where('gate_id', '!=', null) as $loc)
                  <?php
                    $j++;
                    $key = $loc->gate_id . '_' . $loc->building_shift_id;
                    $progress = isset($scheduleProgress[$key]) ? $scheduleProgress[$key] : ['completed' => 0, 'total' => 0];
                    $completed = $progress['completed'];
                    $total = $progress['total'];
                    $isComplete = $total > 0 && $completed === $total;
                    $statusBadge = $isComplete ? 'badge-success' : ($completed > 0 ? 'badge-warning' : 'badge-secondary');
                    $statusText = $isComplete ? 'COMPLETED' : ($completed > 0 ? 'IN PROGRESS' : 'PENDING');
                  ?>
                  <tr>
                    <td>{{$j}}</td>
                    <td>{{ $loc->gate->name ?? '-' }}</td>
                    <td>{{ $loc->buildingShift ? $loc->buildingShift->name . ' (' . $loc->buildingShift->start_time . '-' . $loc->buildingShift->end_time . ')' : '-' }}</td>
                    <td>{{ $loc->patrol_time ?? '-' }}</td>
                    <td>
                      <strong>{{ $completed }}/{{ $total }}</strong>
                      @if($total > 0)
                        <div class="progress" style="height: 15px; margin-top: 5px;">
                          <div class="progress-bar {{ $isComplete ? 'bg-success' : 'bg-warning' }}"
                               style="width: {{ $total > 0 ? ($completed / $total * 100) : 0 }}%">
                            {{ $total > 0 ? round($completed / $total * 100) : 0 }}%
                          </div>
                        </div>
                      @endif
                    </td>
                    <td>
                      <span class="badge {{ $statusBadge }}">{{ $statusText }}</span>
                    </td>
                    @if(Auth::User()->role == 'BA')
                    <td>
                        <button class="btn btn-sm btn-primary edit-assignment" data-toggle="modal" data-target="#assignLocationModal"
                          data-id="{{ $loc->id }}" data-name="{{ $loc->name }}"
                          data-gate_id="{{ $loc->gate_id }}" data-building_shift_id="{{ $loc->building_shift_id }}"
                          data-patrol_time="{{ $loc->patrol_time }}">
                          <i class="fa fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger remove-assignment" data-id="{{ $loc->id }}">
                          <i class="fa fa-times"></i>
                        </button>
                    </td>
                    @endif
                  </tr>
                  @empty
                  <tr><td colspan="7" class="text-center">No schedules yet</td></tr>
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

<!-- Add Location Modal -->
<div class="modal fade" id="addLocationModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Patrol Location</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{route('patrol-location.store')}}" method="post">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label class="col-form-label">Location Name:</label>
            <input type="text" name="name" id="location_name" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="col-form-label">Description:</label>
            <textarea name="description" id="location_description" class="form-control" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label class="col-form-label">Status:</label>
            <select name="status" id="location_status" class="form-control" required>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <input type="hidden" name="id" id="location_id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- QR Code Modal -->
<div class="modal fade" id="qrModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">QR Code</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center" style="padding: 40px;">
        <h3 id="qr-location-name" style="margin-bottom: 30px; font-weight: bold; font-size: 22px;"></h3>
        <div id="qrcode-display" style="display: flex; justify-content: center; align-items: center; padding: 20px;"></div>
        <p style="margin-top: 20px; font-size: 14px; color: #666;">Scan this code to verify location</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="window.print()">
          <i class="fa fa-print"></i> Print QR
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Assign Location Modal -->
<div class="modal fade" id="assignLocationModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Patrol Schedule</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{route('patrol-location.store')}}" method="post">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label class="col-form-label">Gate:</label>
            <select name="gate_id" id="assign_gate_id" class="form-control" required>
              <option value="">-- Select Gate --</option>
              @if(isset($gates))
                @foreach($gates as $gate)
                <option value="{{ $gate->id }}">{{ $gate->name }}</option>
                @endforeach
              @endif
            </select>
          </div>
          <div class="form-group">
            <label class="col-form-label">Shift:</label>
            <select name="building_shift_id" id="assign_shift_id" class="form-control" required>
              <option value="">-- Select Shift --</option>
              @if(isset($shifts))
                @foreach($shifts as $shift)
                <option value="{{ $shift->id }}">{{ $shift->name }} ({{ $shift->start_time }} - {{ $shift->end_time }})</option>
                @endforeach
              @endif
            </select>
          </div>
          <div class="form-group">
            <label class="col-form-label">Patrol Time:</label>
            <input type="time" name="patrol_time" id="assign_patrol_time" class="form-control" required>
          </div>
          <input type="hidden" name="id" id="assign_location_hidden_id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save Schedule</button>
        </div>
      </form>
    </div>
  </div>
</div>

@section('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  $(document).ready(function(){
    var token = "{{csrf_token()}}";

    // View QR
    $(document).on('click', '.view-qr', function(){
        var id = $(this).data('id');
        $('#qrcode-display').html('');
        $.ajax({
            url: '{{ route("patrol-location.show", "") }}/' + id,
            type: 'GET',
            headers: {'X-CSRF-TOKEN': token},
            success: function(data) {
                $('#qr-location-name').text('Location: ' + data.name);
                new QRCode(document.getElementById('qrcode-display'), {
                    text: data.qr_string,
                    width: 256,
                    height: 256,
                });
                $('#qrModal').modal('show');
            }
        });
    });

    // Edit Location
    $(document).on('click', '.edit-location', function(){
        var id = $(this).data('id');
        $('#location_id').val(id);
        $('#location_name').val($(this).data('name'));
        $('#location_description').val($(this).data('description'));
        $('#location_status').val($(this).data('status'));
        $('#addLocationModal .modal-title').text('Edit Patrol Location');
    });

    // Reset form on new add
    $('#addLocationModal').on('hidden.bs.modal', function(){
        if ($('#location_id').val() === '' || $('#location_id').val() === null) {
            $('#addLocationModal form')[0].reset();
        }
        $('#addLocationModal .modal-title').text('Add Patrol Location');
    });

    // Delete Location
    $(document).on('click', '.delete-location', function(){
        var id = $(this).data('id');
        if (!confirm('Are you sure you want to delete this location?')) return;
        $.ajax({
            url: '/patrol-location/' + id,
            type: 'DELETE',
            data: {'_token': token},
            success: function(data){
                if(data.msg === 'success') {
                    window.location.reload();
                }
            }
        });
    });

    // Clear on modal open
    $('#addLocationModal').on('show.bs.modal', function(e){
        var btn = $(e.relatedTarget);
        if (!btn.hasClass('edit-location')) {
            $('#location_id').val('');
            $('#addLocationModal form')[0].reset();
        }
    });

    // Edit Assignment
    $(document).on('click', '.edit-assignment', function(){
        var id = $(this).data('id');
        var gate_id = $(this).data('gate_id');
        var shift_id = $(this).data('building_shift_id');
        var patrol_time = $(this).data('patrol_time');

        $('#assign_location_hidden_id').val(id);
        $('#assign_gate_id').val(gate_id);
        $('#assign_shift_id').val(shift_id);
        $('#assign_patrol_time').val(patrol_time);
        $('#assignLocationModal .modal-title').text('Edit Patrol Schedule');
    });

    // Clear assignment modal on open
    $('#assignLocationModal').on('show.bs.modal', function(e){
        var btn = $(e.relatedTarget);
        if (!btn.hasClass('edit-assignment')) {
            $('#assign_location_hidden_id').val('');
            $('#assign_gate_id').val('');
            $('#assign_shift_id').val('');
            $('#assign_patrol_time').val('');
            $('#assignLocationModal .modal-title').text('Add Patrol Schedule');
        }
    });

    // Update patrol time min/max based on shift selection
    $(document).on('change', '#assign_shift_id', function(){
        var shiftId = $(this).val();
        if (!shiftId) {
            $('#assign_patrol_time').removeAttr('min').removeAttr('max');
            return;
        }

        // Extract shift times from the selected option
        var selectedText = $(this).find('option:selected').text();
        var timeMatch = selectedText.match(/\((\d{2}:\d{2})-(\d{2}:\d{2})\)/);

        if (timeMatch) {
            var startTime = timeMatch[1];
            var endTime = timeMatch[2];

            $('#assign_patrol_time').attr('min', startTime).attr('max', endTime);
            $('#assign_patrol_time').attr('placeholder', startTime + ' to ' + endTime);
        }
    });

    // Trigger shift change on modal open to set time restrictions
    $('#assignLocationModal').on('shown.bs.modal', function(){
        $('#assign_shift_id').trigger('change');
    });

    // Remove Assignment
    $(document).on('click', '.remove-assignment', function(){
        var id = $(this).data('id');
        if (!confirm('Remove this assignment?')) return;
        $.ajax({
            url: '/patrol-location/' + id,
            type: 'DELETE',
            data: {'_token': token, 'remove_assignment': true},
            success: function(data){
                if(data.msg === 'success') {
                    window.location.reload();
                }
            },
            error: function() {
                alert('Error removing assignment');
            }
        });
    });
  });
</script>
@endsection

@endsection
