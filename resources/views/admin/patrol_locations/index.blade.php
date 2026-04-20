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
                  @forelse($locations as $location)
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
            $('#addLocationModal form')[0].reset();
        }
    });
  });
</script>
@endsection

@endsection
