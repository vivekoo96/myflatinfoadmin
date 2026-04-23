@extends('layouts.admin')

@section('title')
    Create Move-In Pass
@endsection

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>Create Move-In Pass</h1>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Enter Details</h3>
          </div>
          <form action="{{ route('move-in-out.store') }}" method="POST">
            @csrf
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Select Flat</label>
                    <select name="flat_id" class="form-control select2" required style="width: 100%;">
                      <option value="">Select Flat</option>
                      @foreach($flats as $flat)
                        <option value="{{ $flat->id }}">{{ $flat->name }} ({{ $flat->block->name }})</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Type</label>
                    <select name="type" class="form-control" required readonly>
                      <option value="Move-In">Move-In</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Person Type</label>
                    <select name="person_type" class="form-control" required>
                      <option value="Owner">Owner</option>
                      <option value="Tanent">Tanent</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Email ID</label>
                    <input type="email" name="email" id="email_field" class="form-control" placeholder="Enter email" required>
                  </div>
                  <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" id="phone_field" class="form-control" placeholder="Enter phone" required>
                  </div>
                  <div class="form-group">
                    <label>Date of Entry/Exit</label>
                    <input type="date" name="date_of_entry_exit" class="form-control" required min="{{ date('Y-m-d') }}">
                  </div>
                </div>
              </div>
              <div id="fetched_details" style="display:none;" class="mt-3 p-3 bg-light border rounded">
                <h5>Flat Occupant Details</h5>
                <p><strong>Name:</strong> <span id="occupant_name"></span></p>
                <p><strong>Status:</strong> <span id="occupant_status"></span></p>
              </div>
            </div>
            <div class="card-footer">
              <button type="submit" class="btn btn-primary">Submit Move-In Request</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@section('script')
<script>
$(document).ready(function() {
    $('.select2').select2();

    var flatData = null;

    function populateFields() {
        var personType = $('select[name="person_type"]').val();
        if(!flatData) return;

        var occupant = (personType === 'Owner') ? flatData.owner : flatData.tenant;
        
        if(occupant) {
            $('#email_field').val(occupant.email);
            $('#phone_field').val(occupant.phone);
            $('#occupant_name').text(occupant.name);
            $('#occupant_status').text(personType);
            $('#fetched_details').show();
        } else {
            $('#email_field').val('');
            $('#phone_field').val('');
            $('#fetched_details').hide();
        }
    }

    $('select[name="flat_id"]').on('change', function() {
        var flatId = $(this).val();
        if(flatId) {
            $.ajax({
                url: "{{ route('get.flat.details') }}",
                type: 'POST',
                data: {
                    flat_id: flatId,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if(response.success) {
                        flatData = response;
                        // Automatically switch person type based on living status if needed
                        if(response.living_status === 'Owner' || response.living_status === 'Tanent') {
                            $('select[name="person_type"]').val(response.living_status);
                        }
                        populateFields();
                    }
                }
            });
        }
    });

    $('select[name="person_type"]').on('change', function() {
        populateFields();
    });
});
</script>
@endsection
