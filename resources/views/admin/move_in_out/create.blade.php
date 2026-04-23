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
                    <select name="type" class="form-control" required>
                      <option value="Move-In">Move-In</option>
                      <option value="Move-Out">Move-Out</option>
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
                    <input type="email" name="email" class="form-control" placeholder="Enter email" required>
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
                <h5>Fetched Details</h5>
                <p><strong>Name:</strong> <span id="fetched_name"></span></p>
                <p><strong>Block:</strong> <span id="fetched_block"></span></p>
                <p><strong>Flat:</strong> <span id="fetched_flat"></span></p>
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

    // Fetch details by email/phone
    $('#phone_field, input[name="email"]').on('blur', function() {
        var email = $('input[name="email"]').val();
        var phone = $('#phone_field').val();
        
        if(email || phone) {
            $.ajax({
                url: "{{ url('api/get-user-by-contact') }}",
                type: 'POST',
                data: {
                    email: email,
                    phone: phone,
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                    if(response.success) {
                        $('#fetched_name').text(response.user.name);
                        $('#fetched_details').show();
                    } else {
                        $('#fetched_details').hide();
                    }
                }
            });
        }
    });

    $('select[name="flat_id"]').on('change', function() {
        var flatId = $(this).val();
        if(flatId) {
            var flatName = $(this).find('option:selected').text();
            $('#fetched_flat').text(flatName);
            // Block is usually in the text like "Flat 101 (Block A)"
            var blockMatch = flatName.match(/\(([^)]+)\)/);
            if(blockMatch) {
                $('#fetched_block').text(blockMatch[1]);
            }
        }
    });
});
</script>
@endsection
