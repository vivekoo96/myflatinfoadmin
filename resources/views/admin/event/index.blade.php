  
@extends('layouts.admin')

<link rel="stylesheet" href="{{asset('public/admin/plugins/summernote/summernote-bs4.min.css')}}">
@section('title')
    Event List
@endsection

@section('content')
    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-md-12">
                @if(session()->has('error'))
                <div class="alert alert-danger">
                    {{ session()->get('error') }}
                </div>
                @endif
                @if(session()->has('success'))
                <div class="alert alert-success">
                    {{ session()->get('success') }}
                </div>
                @endif
            </div>
          <div class="col-sm-6">
            <h1>Event List</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Events</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">

            <div class="card">
              <div class="card-header">
                 @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts'))
                <button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">Add New Event</button>
                @endif
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>S No</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Venue Details</th>
                    <th>From Date & Time</th>
                    <th>To Date & Time</th>
                    <th>Is Payment Enabled</th>
                    <th>Status</th>
                      @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts'))
                    <th>Action</th>
                    @endif
                    <!--<th>Action</th>-->
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  @forelse($building->events as $event)
                  <?php $i++; ?>
                  <tr>
                    <td>{{$i}}</td>
                    <td><img src="{{$event->image}}" style="width:40px"></td>
                    <td>{{$event->name}}</td>
                    <td>{{$event->desc}}</td>
                    <td>{{$event->from_time}}</td>
                    <td>{{$event->to_time}}</td>
                    <td>{{$event->is_payment_enabled}}</td>
                    <td>{{$event->status}}</td>
                @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts'))
                        <td>
                          <a href="{{route('event.show',$event->id)}}" target="_blank"  class="btn btn-sm btn-warning"><i class="fa fa-eye"></i></a>
                          @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts'))
                          <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$event->id}}" data-name="{{$event->name}}" data-desc="{{$event->desc}}" data-image="{{$event->image}}"  
                          data-from_time="{{date('Y-m-d\TH:i', strtotime($event->from_time))}}" data-to_time="{{date('Y-m-d\TH:i', strtotime($event->to_time))}}" data-status="{{$event->status}}" data-building_id="{{$event->building_id}}" data-is_payment_enabled="{{$event->is_payment_enabled}}"><i class="fa fa-edit"></i></button>
                          @if($event->deleted_at)
                          {{-- <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="{{$event->id}}" data-action="restore"><i class="fa fa-undo"></i></button> --}}
                          @else
                          {{-- <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$event->id}}" data-action="delete"><i class="fa fa-trash"></i></button> --}}
                          @endif
                          @endif
                        </td>
                    @endif

                  </tr>
                  @empty
                  @endforelse
                  </tbody>
                </table>
                </div>
                
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->

<!-- Add Modal -->

<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Add Event</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{route('event.store')}}" method="post" class="add-form" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <div class="error"></div>
          <!--<div class="form-group">-->
          <!--  <label for="name" class="col-form-label">Building:</label>-->
          <!--  <select name="building_id" id="building_id" class="form-control" required>-->
          <!--      <option value="{{$building->id}}">{{$building->name}}</option>-->
          <!--  </select>-->
          <!--</div>-->
          <div class="form-group">
            <label for="name" class="col-form-label">Name:</label>
            <input type="text" name="name" id="name" class="form-control" min="3" max="30" placeholder="Name" minlength="4" required>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Venue Details:</label>
            <textarea name="desc" id="desc" class="form-control" required></textarea>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Image: <img src="" id="image2" style="width:40px"></label>
            <input type="file" name="image" id="image" class="form-control" accept="image/*">
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">From Date & Time:</label>
            <input type="datetime-local" name="from_time" class="form-control" id="from_time" placeholder="From Date & Time" required>
          </div>
          <div class="form-group">
            <label for="code" class="col-form-label">To Date & Time:</label>
            <input type="datetime-local" name="to_time" class="form-control" id="to_time" placeholder="To Date & Time" required>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Is Payment Enabled:</label>
            <select name="is_payment_enabled" id="is_payment_enabled" class="form-control" required>
                <option value="Yes">Yes</option>
                <option value="No">No</option>
            </select>
           
            
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Status:</label>
            <select name="status" id="status" class="form-control" required>
                <option value="Pending">Pending</option>
                <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            </select>
          </div>
          <input type="hidden" name="id" id="edit-id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="save-button">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Are you sure ?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="text"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-danger" data-dismiss="modal" id="delete-button">Confirm Delete</button>
      </div>
    </div>
  </div>
</div>


@section('script')


<script>
    $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "{{csrf_token()}}";
    
    // Bind hidden.bs.modal event to reset form state when modal closes
    $('#addModal').on('hidden.bs.modal', function () {
      var $form = $(this).find('form');
      // Re-enable all inputs and remove readonly state
      $form.find('input,textarea,select').not('input[type=hidden]').prop('disabled', false).prop('readonly', false);
      // Remove all helper hidden inputs (added for disabled selects during Active/Inactive edits)
      $form.find('input').filter(function(){ return $(this).attr('class') && $(this).attr('class').indexOf('helper-') !== -1; }).remove();
      // Ensure Pending option is always available in status for next Add
      if ($('#status option[value="Pending"]').length === 0) {
        $('#status').prepend($('<option>').attr('value','Pending').text('Pending'));
      }
      // Re-enable Save button
      $('#save-button').prop('disabled', false);
    });    $('#deleteModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      id = button.data('id');
      $('#delete-id').val(id);
      action= button.data('action');
      $('#delete-button').removeClass('btn-success');
      $('#delete-button').removeClass('btn-danger');
      if(action == 'delete'){
          $('#delete-button').addClass('btn-danger');
          $('#delete-button').text('Confirm Delete');
          $('.text').text('You are going to permanently delete this item..');
      }else{
          $('#delete-button').addClass('btn-success');
          $('#delete-button').text('Confirm Restore');
          $('.text').text('You are going to restore this item..');
      }
    });

    $(document).on('click','#delete-button',function(){
      var url = "{{route('event.destroy','')}}";
      $.ajax({
        url : url + '/' + id,
        type: "DELETE",
        data : {'_token':token,'action':action},
        success: function(data)
        {
          window.location.reload();
        }
      });
    });

      $('#addModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');
      var now = new Date();
      var nowString = now.toISOString().slice(0, 16); // Format for datetime-local
      
      $('#edit-id').val(button.data('id'));
      $('#name').val(button.data('name'));
      $('#desc').val(button.data('desc'));
      $('#from_time').val(button.data('from_time'));
      $('#to_time').val(button.data('to_time'));
      $('#building_id').val(button.data('building_id'));
      $('#is_payment_enabled').val(button.data('is_payment_enabled'));
      $('#image2').attr('src',button.data('image'));
      $('.error').html(''); // Clear any previous errors
      
      // Set status: default to Pending only for new events
      if (button.data('id')) {
        // EDIT: preserve the original status from data attribute
        $('#status').val(button.data('status'));
        $('.modal-title').text('Update Event');
      } else {
        // ADD: default to Pending for new events
        $('#status').val('Pending');
        $('.modal-title').text('Add New Event');
      }
      
      // TC04/TC05: Show payment hint when creating/editing
      var paymentEnabled = button.data('is_payment_enabled');
      if (paymentEnabled === 'Yes') {
        $('#payment-hint-enabled').show();
        $('#payment-hint-disabled').hide();
      } else if (paymentEnabled === 'No') {
        $('#payment-hint-enabled').hide();
        $('#payment-hint-disabled').show();
      } else {
        // For new events, default to showing TC05 (No) hint
        $('#payment-hint-enabled').hide();
        $('#payment-hint-disabled').show();
      }
      
      if(edit_id){
        $('.modal-title').text('Update Event');
        // For editing, remove min restriction to allow past dates
        $('#from_time').removeAttr('min');
        $('#to_time').removeAttr('min');

        // TC06: If editing an existing event and its status is Active,
        // make other inputs readonly to prevent accidental modifications but allow Status/Payment change.
        // For Inactive events, allow Status and Payment changes (can activate and toggle payment).
        // For Pending events, all fields remain editable.
        var st = $('#status').val();
        if (st === 'Active') {
          var $form = $('#addModal').find('form');

          // Remove the Pending option from status so Active cannot be switched back to Pending
          $form.find('#status option[value="Pending"]').remove();

          // Make text inputs and textareas readonly (so values are submitted)
          $form.find('input,textarea').not('#status,#is_payment_enabled, input[type=hidden]').each(function(){
            if ($(this).attr('type') === 'file') return; // leave file inputs alone
            $(this).prop('readonly', true);
          });

          // For selects (except allowed ones) disable them but create hidden inputs to preserve values
          $form.find('select').not('#status,#is_payment_enabled').each(function(){
            var $sel = $(this);
            var name = $sel.attr('name');
            var val = $sel.val();
            if (name) {
              // add a helper hidden input to preserve the select value on submit
              $form.find('input.helper-'+name).remove();
              var $hidden = $('<input>').attr({type:'hidden', name: name, value: val}).addClass('helper-'+name);
              $form.append($hidden);
            }
            $sel.prop('disabled', true);
          });

          // keep modal close enabled and Save enabled so status/payment can be changed
          $('#addModal').find('button.close, .modal-footer .btn-secondary').prop('disabled', false);
          $('#save-button').prop('disabled', false);
        } else if (st === 'Inactive') {
          // When Inactive, allow Status and Payment to be changed (can activate and toggle payment),
          // but make other fields readonly to prevent accidental modifications. Also remove Pending option
          // so users cannot set status back to Pending from Inactive.
          var $formInactive = $('#addModal').find('form');

          // Remove Pending option to prevent switching back to Pending
          $formInactive.find('#status option[value="Pending"]').remove();

          // Make text inputs and textareas readonly (except for status/payment which remain enabled)
          $formInactive.find('input,textarea').not('#status,#is_payment_enabled, input[type=hidden]').each(function(){
            if ($(this).attr('type') === 'file') return;
            $(this).prop('readonly', true);
          });

          // For selects (except status/payment) disable them but create hidden inputs to preserve values
          $formInactive.find('select').not('#status,#is_payment_enabled').each(function(){
            var $sel = $(this);
            var name = $sel.attr('name');
            var val = $sel.val();
            if (name) {
              $formInactive.find('input.helper-'+name).remove();
              var $hidden = $('<input>').attr({type:'hidden', name: name, value: val}).addClass('helper-'+name);
              $formInactive.append($hidden);
            }
            $sel.prop('disabled', true);
          });

          // Allow status and payment to remain enabled so user can activate event and toggle payment
          $('#status').prop('disabled', false).prop('readonly', false);
          $('#is_payment_enabled').prop('disabled', false).prop('readonly', false);

          // Keep Save enabled
          $('#save-button').prop('disabled', false);
        } else {
          // ensure enabled when not Active/Inactive (e.g., Pending/new)
          $('#addModal').find('input,textarea,select').not('input[type=hidden]').prop('disabled', false).prop('readonly', false);
          // remove any helper hidden inputs
          $('#addModal').find('input').filter(function(){ return $(this).attr('class') && $(this).attr('class').indexOf('helper-') !== -1; }).remove();
          // ensure the Pending option exists when not editing an Active event
          if ($('#status option[value="Pending"]').length === 0) {
            $('#status').prepend($('<option>').attr('value','Pending').text('Pending'));
          }
        }
      } else {
          // For new events, set min to current datetime
          $('#from_time').attr('min', nowString);
          $('#to_time').attr('min', nowString);
          // Clear form fields for new event
          $('#name').val('');
          $('#desc').val('');
          $('#from_time').val('');
          $('#to_time').val('');
          $('#status').val('Pending');
          $('#is_payment_enabled').val('Yes');
          $('#image2').attr('src', '');
      }
      
      // When modal is hidden, re-enable all inputs to reset state and remove helper inputs
      // This ensures the next time the modal opens (Add or Edit), fields are in a clean state
      $('#addModal').off('hidden.bs.modal.resetFields').on('hidden.bs.modal.resetFields', function () {
        var $form = $(this).find('form');
        $form.find('input,textarea,select').not('input[type=hidden]').prop('disabled', false).prop('readonly', false);
        // remove helper hidden inputs added for disabled selects
        $form.find('input').filter(function(){ return $(this).attr('class') && $(this).attr('class').indexOf('helper-') !== -1; }).remove();
        // Ensure Pending option is available in status select
        if ($('#status option[value="Pending"]').length === 0) {
          $('#status').prepend($('<option>').attr('value','Pending').text('Pending'));
        }
        $('#save-button').prop('disabled', false);
      });
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "{{url('update-building-status')}}",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                  //
                }
            });
        });

    // Dynamic validation when from_time changes
    $('#from_time').on('change', function() {
        var fromTime = $(this).val();
        if (fromTime) {
            $('#to_time').attr('min', fromTime);
            // Clear to_time if it's before from_time
            var toTime = $('#to_time').val();
            if (toTime && new Date(toTime) <= new Date(fromTime)) {
                $('#to_time').val('');
            }
        }
    });

  });

    // Frontend validation for event time
    $(document).on('submit', '.add-form', function (event) {
      var name = $('#name').val().trim();
      var fromTime = $('#from_time').val();
      var toTime = $('#to_time').val();
      var errorDiv = $(this).find('.error');
      var isEdit = $('#edit-id').val();
      var now = new Date();
      
      errorDiv.html('');
      
      // TC09: Check if event name is empty
      if (!name) {
        event.preventDefault();
        errorDiv.html('<div class="alert alert-danger">Event name is required.</div>');
        $('#name').focus();
        return false;
      }
      
      // Check if from_time is in the past (only for new events)
      if (!isEdit && fromTime && new Date(fromTime) < now) {
        event.preventDefault();
        errorDiv.html('<div class="alert alert-danger">From time cannot be in the past.</div>');
        $('#from_time').focus();
        return false;
      }
      
      // Check if to_time is after from_time (TC08: Date Validation)
      if (fromTime && toTime && new Date(toTime) <= new Date(fromTime)) {
        event.preventDefault();
        errorDiv.html('<div class="alert alert-danger">To Date & Time cannot be earlier than From Date & Time.</div>');
        $('#to_time').focus();
        return false;
      }
    });

    // Show backend error in modal if present
    $(document).ready(function() {
      var errorText = $('.alert-danger').text();
      if (errorText && errorText.match(/To Date & Time cannot be earlier than From Date & Time|after:from_time|To Time must be after|Event name is required|Venue details are required/)) {
        $('#addModal').modal('show');
        $('#addModal .error').html('<div class="alert alert-danger">' + errorText + '</div>');
      }
      
      // TC04/TC05: Show/hide payment hints based on is_payment_enabled toggle
      $('#is_payment_enabled').on('change', function() {
        if ($(this).val() === 'Yes') {
          $('#payment-hint-enabled').show();
          $('#payment-hint-disabled').hide();
        } else {
          $('#payment-hint-enabled').hide();
          $('#payment-hint-disabled').show();
        }
      });
    });
</script>

<script src="{{asset('public/admin/plugins/summernote/summernote-bs4.min.js')}}"></script>

<script>
  $(function () {
    // Summernote
    $('#summernote').summernote()

  })
</script>
@endsection

@endsection


