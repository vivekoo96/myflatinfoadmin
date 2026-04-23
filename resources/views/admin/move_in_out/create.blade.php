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
                    <select name="person_type" id="person_type_select" class="form-control" required>
                      <option value="Owner">Owner</option>
                      <option value="Tanent">Tanent</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Select User</label>
                    <select id="user_select" class="form-control">
                        <option value="">-- Select Flat First --</option>
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

    function populateUserSelect() {
        var $userSelect = $('#user_select');
        $userSelect.empty();
        $userSelect.append('<option value="">-- Select User --</option>');

        if (flatData) {
            if (flatData.owner) {
                $userSelect.append('<option value="Owner" data-email="'+flatData.owner.email+'" data-phone="'+flatData.owner.phone+'">Owner: ' + flatData.owner.name + '</option>');
            }
            if (flatData.tenant) {
                $userSelect.append('<option value="Tanent" data-email="'+flatData.tenant.email+'" data-phone="'+flatData.tenant.phone+'">Tenant: ' + flatData.tenant.name + '</option>');
            }
        }
    }

    function populateFields(personType, email, phone) {
        if(personType) {
            $('#person_type_select').val(personType);
        }
        if(email !== undefined) {
            $('#email_field').val(email);
        }
        if(phone !== undefined) {
            $('#phone_field').val(phone);
        }
        
        if(flatData) {
            var occupant = (personType === 'Owner') ? flatData.owner : flatData.tenant;
            if(occupant) {
                $('#occupant_name').text(occupant.name);
                $('#occupant_status').text(personType);
                $('#fetched_details').show();
            } else {
                $('#fetched_details').hide();
            }
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
                        populateUserSelect();
                        
                        // Default to current living status if possible
                        if(response.living_status === 'Owner' || response.living_status === 'Tanent') {
                            var type = response.living_status;
                            $('#user_select').val(type);
                            var occupant = (type === 'Owner') ? response.owner : response.tenant;
                            if(occupant) {
                                populateFields(type, occupant.email, occupant.phone);
                            }
                        }
                    }
                }
            });
        }
    });

    $('#user_select').on('change', function() {
        var selected = $(this).find('option:selected');
        var type = $(this).val();
        var email = selected.data('email');
        var phone = selected.data('phone');
        
        if(type) {
            populateFields(type, email, phone);
        }
    });

    $('#person_type_select').on('change', function() {
        var type = $(this).val();
        $('#user_select').val(type).trigger('change');
    });
});
</script>
@endsection
