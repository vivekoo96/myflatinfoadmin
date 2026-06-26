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
        
        <!-- Search Section -->
        <div class="card card-primary card-outline" id="lookup_section">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-search mr-1"></i> Look up Resident Details</h3>
          </div>
          <div class="card-body">
            <p class="text-muted">Enter the incoming person's registered Email ID or Phone Number to fetch their flat details automatically.</p>
            <div class="input-group">
              <input type="text" id="lookup_contact" class="form-control form-control-lg" placeholder="Enter Email ID or Phone Number" autofocus>
              <div class="input-group-append">
                <button class="btn btn-primary btn-lg" type="button" id="btn_lookup">
                  <span id="lookup_spinner" class="spinner-border spinner-border-sm mr-1 d-none" role="status" aria-hidden="true"></span>
                  <i class="fas fa-search" id="lookup_icon"></i> Fetch Details
                </button>
              </div>
            </div>
            <div id="lookup_alert" class="alert mt-3 d-none"></div>
          </div>
        </div>

        <!-- Form Section -->
        <div class="card card-success card-outline d-none" id="form_card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title"><i class="fas fa-file-invoice mr-1"></i> Pass Creation Details</h3>
            <button type="button" class="btn btn-sm btn-outline-secondary ml-auto" id="btn_reset_lookup">
              <i class="fas fa-undomr-1"></i> Start Over
            </button>
          </div>
          
          <form action="{{ route('move-in-out.store') }}" method="POST" id="move_in_form">
            @csrf
            <!-- Hidden inputs populated by search -->
            <input type="hidden" name="flat_id" id="hidden_flat_id">
            <input type="hidden" name="person_type" id="hidden_person_type">
            <input type="hidden" name="email" id="hidden_email">
            <input type="hidden" name="phone" id="hidden_phone">
            <input type="hidden" name="first_name" id="hidden_first_name">
            <input type="hidden" name="last_name" id="hidden_last_name">

            <div class="card-body">
              <!-- Fetched Data Card -->
              <div class="p-3 mb-4 bg-light border rounded">
                <h5 class="border-bottom pb-2 mb-3 text-info"><i class="fas fa-user-check mr-1"></i> Fetched Details</h5>
                <div class="row">
                  <div class="col-sm-6 mb-2">
                    <strong>Name:</strong> <span id="lbl_name" class="text-dark"></span>
                  </div>
                  <div class="col-sm-6 mb-2">
                    <strong>Person Type:</strong> <span id="lbl_type" class="badge badge-primary"></span>
                  </div>
                  <div class="col-sm-6 mb-2">
                    <strong>Block Name:</strong> <span id="lbl_block" class="text-dark"></span>
                  </div>
                  <div class="col-sm-6 mb-2">
                    <strong>Flat Name:</strong> <span id="lbl_flat" class="text-dark"></span>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label for="date_of_entry_exit"><i class="far fa-calendar-alt mr-1"></i> Date of Entry</label>
                <input type="date" name="date_of_entry_exit" id="date_of_entry_exit" class="form-control form-control-lg" required min="{{ date('Y-m-d') }}">
                <small class="text-muted">The Move-In pass will only be valid on this date.</small>
              </div>
            </div>
            
            <div class="card-footer">
              <button type="submit" class="btn btn-success btn-lg btn-block">Generate Move-In Pass</button>
            </div>
          </form>
        </div>

        <!-- Manual Fallback Card -->
        <div class="card card-warning card-outline mt-3" id="manual_card">
          <div class="card-header">
            <h3 class="card-title"><i class="fas fa-keyboard mr-1"></i> Manual Entry Fallback</h3>
            <div class="card-tools">
              <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <p class="text-muted">Use this if the resident is not registered yet, or the contact lookup doesn't return the flat details.</p>
            <button class="btn btn-outline-warning" type="button" id="btn_toggle_manual">
              <i class="fas fa-edit mr-1"></i> Switch to Manual Pass Creation
            </button>

            <form action="{{ route('move-in-out.store') }}" method="POST" id="manual_form" class="d-none mt-4">
              @csrf
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Select Flat</label>
                    <select name="flat_id" class="form-control select2" style="width: 100%;">
                      <option value="">Select Flat</option>
                      @foreach($flats as $flat)
                        <option value="{{ $flat->id }}">{{ $flat->name }} ({{ $flat->block->name }})</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Person Type</label>
                    <select name="person_type" class="form-control">
                      <option value="Owner">Owner</option>
                      <option value="Tanent">Tenant</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>First Name</label>
                    <input type="text" name="first_name" class="form-control" placeholder="Enter first name">
                  </div>
                  <div class="form-group">
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-control" placeholder="Enter last name">
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Email ID</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter email">
                  </div>
                  <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="Enter phone">
                  </div>
                  <div class="form-group">
                    <label>Date of Entry</label>
                    <input type="date" name="date_of_entry_exit" class="form-control" min="{{ date('Y-m-d') }}">
                  </div>
                </div>
              </div>
              <div class="mt-3">
                <button type="submit" class="btn btn-warning">Create Manual Move-In Pass</button>
                <button type="button" class="btn btn-default" id="btn_cancel_manual">Cancel</button>
              </div>
            </form>
          </div>
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

    // Trigger search on button click
    $('#btn_lookup').on('click', function() {
        performLookup();
    });

    // Trigger search on enter press
    $('#lookup_contact').on('keypress', function(e) {
        if (e.which === 13) {
            performLookup();
        }
    });

    function performLookup() {
        var contact = $('#lookup_contact').val().trim();
        var $alert = $('#lookup_alert');
        var $spinner = $('#lookup_spinner');
        var $icon = $('#lookup_icon');

        if (!contact) {
            showAlert('danger', 'Please enter an Email ID or Phone Number.');
            return;
        }

        // Reset UI state
        $alert.addClass('d-none');
        $spinner.removeClass('d-none');
        $icon.addClass('d-none');

        $.ajax({
            url: "{{ route('move-in-out.fetch-by-contact') }}",
            type: 'POST',
            data: {
                contact: contact,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                $spinner.addClass('d-none');
                $icon.removeClass('d-none');

                if (response.success) {
                    // Populate values to hidden inputs
                    $('#hidden_flat_id').val(response.flat.id);
                    $('#hidden_person_type').val(response.person_type);
                    $('#hidden_email').val(response.user.email);
                    $('#hidden_phone').val(response.user.phone);
                    $('#hidden_first_name').val(response.user.first_name);
                    $('#hidden_last_name').val(response.user.last_name);

                    // Show labels
                    $('#lbl_name').text(response.user.first_name + ' ' + (response.user.last_name || ''));
                    $('#lbl_type').text(response.person_type);
                    $('#lbl_block').text(response.flat.block_name);
                    $('#lbl_flat').text(response.flat.name);

                    // Switch panels
                    $('#lookup_section').addClass('d-none');
                    $('#form_card').removeClass('d-none');
                    $('#manual_card').addClass('d-none');
                }
            },
            error: function(xhr) {
                $spinner.addClass('d-none');
                $icon.removeClass('d-none');
                
                var msg = 'An error occurred while fetching details.';
                if (xhr.status === 404) {
                    msg = 'Resident not found or has no active flat in this building.';
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showAlert('warning', msg + ' You can try manual entry below.');
            }
        });
    }

    function showAlert(type, text) {
        var $alert = $('#lookup_alert');
        $alert.removeClass('alert-danger alert-warning alert-success d-none')
              .addClass('alert-' + type)
              .html('<i class="fas fa-exclamation-triangle mr-1"></i> ' + text);
    }

    // Start over lookup
    $('#btn_reset_lookup').on('click', function() {
        $('#lookup_contact').val('');
        $('#lookup_alert').addClass('d-none');
        
        $('#lookup_section').removeClass('d-none');
        $('#form_card').addClass('d-none');
        $('#manual_card').removeClass('d-none');
    });

    // Toggle Manual Mode
    $('#btn_toggle_manual').on('click', function() {
        $(this).addClass('d-none');
        $('#manual_form').removeClass('d-none');
        // Require manual inputs
        $('#manual_form [name="flat_id"]').attr('required', true);
        $('#manual_form [name="first_name"]').attr('required', true);
        $('#manual_form [name="email"]').attr('required', true);
        $('#manual_form [name="phone"]').attr('required', true);
        $('#manual_form [name="date_of_entry_exit"]').attr('required', true);
    });

    $('#btn_cancel_manual').on('click', function() {
        $('#btn_toggle_manual').removeClass('d-none');
        $('#manual_form').addClass('d-none');
        // Derequire manual inputs
        $('#manual_form [name="flat_id"]').removeAttr('required');
        $('#manual_form [name="first_name"]').removeAttr('required');
        $('#manual_form [name="email"]').removeAttr('required');
        $('#manual_form [name="phone"]').removeAttr('required');
        $('#manual_form [name="date_of_entry_exit"]').removeAttr('required');
    });
});
</script>
@endsection
