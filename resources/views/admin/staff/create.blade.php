@extends('layouts.admin')

@section('title')
    Register Domestic Staff
@endsection

@section('content')
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6"><h1>Register Domestic Staff</h1></div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}">Home</a></li>
              <li class="breadcrumb-item"><a href="{{ route('admin.staff.index') }}">Domestic Staff</a></li>
              <li class="breadcrumb-item active">Register</li>
            </ol>
          </div>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        @if(session('error'))
          <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
          <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form action="{{ route('admin.staff.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="row">
            <!-- Details -->
            <div class="col-md-6">
              <div class="card">
                <div class="card-header"><h3 class="card-title">Staff Details</h3></div>
                <div class="card-body">
                  <div class="form-group">
                    <label>Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                  </div>
                  <div class="form-group">
                    <label>Phone <span class="text-danger">*</span></label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                  </div>
                  <div class="form-group">
                    <label>Type <span class="text-danger">*</span></label>
                    <div class="input-group">
                      <select name="type" id="type_select" class="form-control" required>
                        @foreach($allTypes ?? [] as $t)
                          <option value="{{ $t }}" {{ old('type') == $t ? 'selected' : '' }}>{{ $t }}</option>
                        @endforeach
                      </select>
                      <div class="input-group-append">
                        <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#addTypeModal" title="Add New Type"><i class="fas fa-plus"></i></button>
                      </div>
                    </div>
                  </div>
                  <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                  </div>
                  <div class="form-group">
                    <label>Photo</label>
                    <input type="file" name="photo" class="form-control-file" accept="image/*" capture="environment">
                  </div>
                  <div class="form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                      <option value="Active">Active</option>
                      <option value="Inactive">Inactive</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Assignment -->
              <div class="card">
                <div class="card-header"><h3 class="card-title">Assignment</h3></div>
                <div class="card-body">
                  <input type="hidden" name="category" value="flat_staff">
                  <div class="form-group">
                    <div class="custom-control custom-switch">
                      <input type="checkbox" name="is_open_to_all" class="custom-control-input" id="openToAll" value="1">
                      <label class="custom-control-label" for="openToAll">Open to all flats</label>
                    </div>
                    <small class="form-text text-muted">Turn off to assign this staff to a single flat.</small>
                  </div>

                  <div id="assignmentFields">
                    <div class="form-group">
                      <label>Block</label>
                      <select id="block_id" class="form-control">
                        <option value="">Select Block</option>
                        @foreach($blocks as $block)
                          <option value="{{ $block->id }}">{{ $block->name }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="form-group">
                      <label>Flat</label>
                      <select name="flat_id" id="flat_id" class="form-control">
                        <option value="">Select Flat</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label>Engagement</label>
                      <select name="engagement_type" id="engagement_type" class="form-control">
                        <option value="In-house">In-house</option>
                        <option value="Timely-basis">Timely-basis</option>
                      </select>
                    </div>
                    <div class="form-group" id="timeSlotGroup" style="display:none;">
                      <label>Time slot</label>
                      <input type="text" name="time_slot" class="form-control" placeholder="e.g. 8:00 AM - 10:00 AM">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <!-- Documents -->
              <div class="card">
                <div class="card-header"><h3 class="card-title">Verification</h3></div>
                <div class="card-body">
                  <div class="form-group">
                    <label>Document (ID proof — image or PDF)</label>
                    <input type="file" name="document" class="form-control-file" accept="image/*,application/pdf">
                  </div>
                  <div class="form-group">
                    <label>Document status</label>
                    <select name="document_status" class="form-control">
                      <option value="">— Not set —</option>
                      <option value="Pending">Pending</option>
                      <option value="Verified">Verified</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Police NOC (optional — image or PDF)</label>
                    <input type="file" name="noc" class="form-control-file" accept="image/*,application/pdf">
                  </div>
                </div>
                </div>
              </div>
            </div>
          </div>
          <div class="row mt-3 mb-4">
            <div class="col-12 text-center">
              <button type="submit" class="btn btn-success"><i class="fa fa-id-card"></i> Register &amp; Generate Staff ID</button>
              <a href="{{ route('admin.staff.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
          </div>
        </form>
      </div>
    </section>

    <!-- Add Type Modal -->
    <div class="modal fade" id="addTypeModal" tabindex="-1" role="dialog" aria-labelledby="addTypeModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form id="addTypeForm">
            <div class="modal-header">
              <h5 class="modal-title" id="addTypeModalLabel">Add New Staff Type</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Type Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="new_type_name" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary" id="btn_save_type">Save Type</button>
            </div>
          </form>
        </div>
      </div>
    </div>
@endsection

@section('script')
<script>
$(function () {
    $('#addTypeForm').on('submit', function(e) {
        e.preventDefault();
        let newType = $('#new_type_name').val().trim();
        if (newType.length > 0) {
            let btn = $('#btn_save_type');
            btn.prop('disabled', true).text('Saving...');
            $.ajax({
                url: '{{ route("admin.staff.store-type") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    name: newType
                },
                success: function(res) {
                    if (res.success) {
                        if ($('#type_select option[value="' + res.type + '"]').length === 0) {
                            $('#type_select').append(new Option(res.type, res.type));
                        }
                        $('#type_select').val(res.type);
                        $('#addTypeModal').modal('hide');
                        $('#new_type_name').val('');
                    }
                },
                error: function(err) {
                    alert('Error saving type.');
                },
                complete: function() {
                    btn.prop('disabled', false).text('Save Type');
                }
            });
        }
    });

    // Open to all flats toggles the assignment block
    function syncAssignment() {
        if ($('#openToAll').is(':checked')) {
            $('#assignmentFields').slideUp();
            $('#flat_id').val('');
        } else {
            $('#assignmentFields').slideDown();
        }
    }
    $('#openToAll').on('change', syncAssignment);

    // Block -> Flats (reuse existing endpoint)
    $('#block_id').on('change', function () {
        var blockId = $(this).val();
        $('#flat_id').html('<option value="">Select Flat</option>');
        if (!blockId) return;
        $.ajax({
            url: '/get-flats/' + blockId,
            type: 'GET',
            success: function (res) {
                var flats = (res && res.flats) ? res.flats : [];
                $.each(flats, function (i, f) {
                    $('#flat_id').append('<option value="' + f.id + '">' + f.name + '</option>');
                });
            }
        });
    });

    // Engagement -> time slot
    $('#engagement_type').on('change', function () {
        $('#timeSlotGroup').toggle($(this).val() === 'Timely-basis');
    });

    syncAssignment();
});
</script>
@endsection
