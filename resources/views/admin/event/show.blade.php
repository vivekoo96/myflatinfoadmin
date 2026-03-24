@extends('layouts.admin')

@section('title')
    Event Details
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Event Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Event Details</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-3">

            <!-- Profile Image -->
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <div class="text-center">
                  <img class="profile-user-img img-fluid img-circle"
                       src="{{$event->image}}"
                       alt="Event picture">
                </div>
                <h3 class="profile-username text-center">{{$event->name}}</h3>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>From</b> <a class="float-right">{{$event->from_time}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>To</b> <a class="float-right">{{$event->to_time}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Status</b> <a class="float-right">
                        <input type="checkbox" name="my-checkbox" class="status" data-id="{{$event->id}}" data-bootstrap-switch data-on-text="Active" 
                        data-off-text="Inactive" {{$event->status == 'Active' ? 'checked' : ''}}>
                    </a>
                  </li>
                  <li class="list-group-item">
                    <b>Payment Enabled</b> <a class="float-right">
                        <input type="checkbox" name="payment-checkbox" class="payment-status" data-id="{{$event->id}}" data-bootstrap-switch data-on-text="Yes" 
                        data-off-text="No" {{$event->is_payment_enabled == 'Yes' ? 'checked' : ''}}>
                    </a>
                  </li>
                </ul>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->

          </div>
          <!-- /.col -->
          <div class="col-md-9">
                <div class="">
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
            <div class="card">
              <div class="card-header p-2">
                <ul class="nav nav-pills">
                  <li class="nav-item"><a class="nav-link active" href="#users" data-toggle="tab">Payments</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="active tab-pane" id="users">
                    @if((Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts')) && $event->status == 'Active' && $event->is_payment_enabled == 'Yes')
                    <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal">Add New Payment</button>
                    @elseif($event->status != 'Active' || $event->is_payment_enabled != 'Yes')
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Payments are disabled for this event. 
                        @if($event->status != 'Active')
                            Event status must be "Active" to enable payments.
                        @endif
                        @if($event->is_payment_enabled != 'Yes')
                            Payment option is disabled for this event.
                        @endif
                    </div>
                    @endif
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>S No</th>
                              <th>User</th>
                              <th>Flat</th>
                              <th>Type</th>
                              <th>Payment Type</th>
                              <th>Amount</th>
                              <th>Date</th>
                              <th>Status</th>
                              <th>Action</th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php $i = 0;?>
                        @forelse($event->payments as $payment)
                        <?php $i++; ?>
                        <tr>
                          <td>{{$i}}</td>
                          <td><a href="{{url('user',$payment->user_id)}}" target="_blank">{{$payment->user->name}}</a></td>
                          <td><a href="{{url('flat',$payment->flat_id)}}" target="_blank">{{$payment->flat->name}}</a></td>
                          <td>{{$payment->type}}</td>
                         
                              @php
                          $mode = strtolower(trim($payment->payment_type));
                            @endphp
                            @if($mode == 'inbank')
                                <td>In Bank</td>
                            @elseif($mode == 'inhand')
                                <td>In Cash</td>
                            @endif
                             
                          <td>{{$payment->amount}}</td>
                          <td>{{$payment->date}}</td>
                          <td>{{$payment->status}}</td>
                          <td>
                        @if(Auth::User()->role == 'BA' || Auth::User()->hasRole('president') || Auth::User()->hasRole('accounts'))
                      <!--<button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$payment->id}}" data-user_email="{{$payment->user->email}}" data-user_name="{{$payment->user->name}}" -->
                      <!--data-user_id="{{$payment->user_id}}" data-payment_type="{{$payment->payment_type}}" data-amount="{{$payment->amount}}" data-status="{{$payment->status}}"><i class="fa fa-edit"></i></button>-->
                        <a href="{{url('event/reciept',$payment->id)}}" class="btn btn-sm btn-info" target="_blank">Reciept</a>
                        <!--<a href="{{url('event/invoice',$payment->id)}}" class="btn btn-sm btn-warning" target="_blank">Invoice</a>-->

                      @endif
                    </td>
                        </tr>
                        @empty
                        @endforelse
                      </tbody>
                    </table>
                    </div>
                  </div>
                  <!-- /.tab-pane -->
                  
                </div>
                <!-- /.tab-content -->
              </div><!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
    
<!-- Add Modal -->

<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Add New Payment</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{route('payment.store')}}" method="post" class="add-form">
        @csrf
        <div class="modal-body">
          
          <div class="form-group">
            <label for="email" class="col-form-label">User Email Or Phone:</label>
            <div class="input-group">
              <input type="text" name="user_email" class="form-control" id="user_email" maxlength="40" placeholder="Email" required>
              <div class="input-group-append">
                <button type="button" class="btn btn-primary" id="getUserData">Get User Data</button>
              </div>
            </div>
          </div>
          <div class="error text-danger"></div>
          <div class="form-group">
            <label for="email" class="col-form-label">User Name:</label>
            <input type="text" name="user_name" class="form-control" id="user_name" disabled required>
          </div>
          <div class="form-group" id="flat-select-group" style="display:none;">
            <label for="flat" class="col-form-label">Flat:</label>
            <select name="flat_id" id="flat_select" class="form-control">
              <option value="">-- Select Flat --</option>
            </select>
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Payment Type:</label>
            <select name="payment_type" class="form-control" id="payment_type" required>
              <option value="InHand">InCash</option>
              <option value="InBank">InBank</option>
            </select>
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Amount:</label>
            <input type="number" name="amount" class="form-control" id="amount" placeholder="Amount" min="0" required />
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Date:</label>
            <input type="date" name="date" class="form-control" id="date" placeholder="Date" required />
          </div>
          <div class="form-group">
            <label for="status" class="col-form-label">Status:</label>
            <select name="status" class="form-control" id="status">
              <option value="Paid">Paid</option>
            </select>
          </div>
          
          <input type="hidden" name="id" id="edit-id">
          <input type="hidden" name="user_id" id="user_id">
          <input type="hidden" name="event_id" id="event_id" value="{{$event->id}}">
          <input type="hidden" name="block_id" id="block_id" value="{{$event->building_id ?? Auth::user()->building_id}}">
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
    
    $('#deleteModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      id = button.data('id');
      $('#delete-id').val(id);
      action= button.data('action');
      $('#delete-button').removeClass('btn-success');
      $('#delete-button').removeClass('btn-danger');
      $('.modal-title').text('Are you sure ?');
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
      var url = "{{route('payment.destroy','')}}";
      $.ajax({
        url : url,
        type: "POST",
        data : {'_token':token,'id':id,'action':action},
        success: function(data)
        {
          window.location.reload();
        }
      });
    });

    $('#addModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');
      $('#edit-id').val(edit_id);
      $('#user_email').val(button.data('user_email'));
      $('#user_name').val(button.data('user_name'));
      $('#user_id').val(button.data('user_id'));
      $('#payment_type').val(button.data('payment_type'));
      $('#amount').val(button.data('amount'));
      $('#status').val(button.data('status'));
      $('.modal-title').text('Add New Payment');
      if(edit_id){
          $('.modal-title').text('Update Payment');
      }
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "{{url('update-event-status')}}",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                    // Reload the page to update payment button visibility
                    window.location.reload();
                }
            });
        });

    $('.payment-status').bootstrapSwitch('state');
        $('.payment-status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "{{url('update-event-payment-status')}}",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                    // Reload the page to update payment button visibility
                    window.location.reload();
                }
            });
        });
        
    $('.add-form').on('submit', function (event) {
      if ($('#user_name').val().trim() === '') {
        event.preventDefault();
        $('.error').text('Customer Name is required. Please fetch user data.');
      }
    });
    
    // Fetch user data when clicking "Get User Data"
    $('#getUserData').on('click', function () {
      var email = $('#user_email').val().trim();
      if (email === '') {
        $('.error').text('Please enter an email to fetch user data.');
        return;
      }
      
      $('.error').text(''); // Clear previous errors
      
      $.ajax({
        url: '{{ url("get-user-by-email") }}', // Update with your actual route
        type: 'POST',
        data: { _token:token,email: email },
        success: function (response) {
          if (response.success) {
            $('#user_name').val(response.data.name);
            $('#user_id').val(response.data.id);
            // Fetch flats for current building and populate the flat select
            $.ajax({
              url: '{{ url("get-building-flats") }}',
              type: 'POST',
              data: { _token: token,email: email},
              success: function (res) {
                if (res.success && res.flats) {
                  var $flat = $('#flat_select');
                  $flat.empty();
                  $flat.append('<option value="">-- Select Flat --</option>');
                  res.flats.forEach(function(f){
                    $flat.append('<option value="'+f.id+'">'+f.name+'</option>');
                  });
                  $('#flat-select-group').show();
                }
              },
              error: function () {
                // ignore
              }
            });
          } else {
            $('.error').text('User not found.');
            $('#user_name').val('');
          }
        },
        error: function () {
          $('.error').text('Error fetching user data.');
          $('#user_name').val('');
        }
      });
    });

  });
</script>
@endsection

@endsection



