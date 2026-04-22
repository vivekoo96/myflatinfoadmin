@extends('layouts.admin')

@section('title')
    Patrol Locations
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
                <h3 class="card-title"><i class="nav-icon fas fa-route"></i> Patrol Schedules</h3>
                <div class="card-tools pull-right">
                  @if(Auth::User()->role == 'BA')
                  <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addLocationModal">
                    <i class="fa fa-plus"></i> Add Schedule
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
                    <th>Gate</th>
                    <th>Shift</th>
                    <th>Patrol Time</th>
                    <th>Status</th>
                    <th>QR Code</th>
                    @if(Auth::User()->role == 'BA')
                    <th>Actions</th>
                    @endif
                  </tr>
                  </thead>
                  <tbody>
                    <?php $i = 0; ?>
                  @forelse($locations->where('gate_id', '!=', null) as $location)
                  <?php $i++; ?>
                  <tr>
                    <td>{{$i}}</td>
                    <td>{{ $location->gate->name ?? '-' }}</td>
                    <td>{{ $location->buildingShift ? $location->buildingShift->name . ' (' . $location->buildingShift->start_time . '-' . $location->buildingShift->end_time . ')' : '-' }}</td>
                    <td>{{ $location->patrol_time ?? '-' }}</td>
                    <td>{{ $location->status }}</td>
                    <td><button class="btn btn-sm btn-info view-qr" data-id="{{ $location->id }}">View QR</button></td>
                    @if(Auth::User()->role == 'BA')
                    <td>
                        <button class="btn btn-sm btn-primary edit-location" data-toggle="modal" data-target="#addLocationModal"
                          data-id="{{ $location->id }}" data-gate_id="{{ $location->gate_id }}" data-building_shift_id="{{ $location->building_shift_id }}"
                          data-patrol_time="{{ $location->patrol_time }}" data-status="{{ $location->status }}">
                          <i class="fa fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger delete-location" data-id="{{ $location->id }}">
                          <i class="fa fa-trash"></i>
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

<!-- Add Patrol Schedule Modal -->
<div class="modal fade" id="addLocationModal" tabindex="-1" role="dialog" aria-hidden="true">
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
            <select name="gate_id" id="location_gate_id" class="form-control" required>
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
            <select name="building_shift_id" id="location_shift_id" class="form-control" required>
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
            <input type="time" name="patrol_time" id="location_patrol_time" class="form-control" required>
          </div>
          <input type="hidden" name="id" id="location_id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save Schedule</button>
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
      <div class="modal-body text-center">
        <p id="qr-location-name"></p>
        <div id="qrcode-display"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="window.print()">Print QR</button>
      </div>
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

    // Edit Schedule
    $(document).on('click', '.edit-location', function(){
        var id = $(this).data('id');
        $('#location_id').val(id);
        $('#location_gate_id').val($(this).data('gate_id'));
        $('#location_shift_id').val($(this).data('building_shift_id'));
        $('#location_patrol_time').val($(this).data('patrol_time'));
        $('#addLocationModal .modal-title').text('Edit Patrol Schedule');
    });

    // Reset form on modal close
    $('#addLocationModal').on('hidden.bs.modal', function(){
        if ($('#location_id').val() === '' || $('#location_id').val() === null) {
            $('#addLocationModal form')[0].reset();
        }
        $('#addLocationModal .modal-title').text('Add Patrol Schedule');
    });

    // Delete Schedule
    $(document).on('click', '.delete-location', function(){
        var id = $(this).data('id');
        if (!confirm('Are you sure you want to delete this schedule?')) return;
        $.ajax({
            url: '{{ route("patrol-location.destroy", "") }}/' + id,
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
            $('#location_gate_id').val('');
            $('#location_shift_id').val('');
            $('#location_patrol_time').val('');
            $('#addLocationModal form')[0].reset();
        }
    });

    // Update patrol time min/max based on shift selection
    $(document).on('change', '#location_shift_id', function(){
        var shiftId = $(this).val();
        if (!shiftId) {
            $('#location_patrol_time').removeAttr('min').removeAttr('max');
            return;
        }

        // Extract shift times from the selected option
        var selectedText = $(this).find('option:selected').text();
        var timeMatch = selectedText.match(/\((\d{2}:\d{2})-(\d{2}:\d{2})\)/);

        if (timeMatch) {
            var startTime = timeMatch[1];
            var endTime = timeMatch[2];

            $('#location_patrol_time').attr('min', startTime).attr('max', endTime);
            $('#location_patrol_time').attr('placeholder', startTime + ' to ' + endTime);
        }
    });

    // Trigger shift change on modal open to set time restrictions
    $('#addLocationModal').on('shown.bs.modal', function(){
        $('#location_shift_id').trigger('change');
    });
  });
</script>
@endsection

@endsection
