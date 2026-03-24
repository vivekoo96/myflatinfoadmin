@extends('layouts.admin')

@section('title')
    Essential Details
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Essential Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Essential Details</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">

            <!-- Essential Details -->
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <h3 class="profile-username text-center">{{$essential->reason}}</h3>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Amount</b> <a class="float-right">₹{{$essential->amount}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Due Date</b> <a class="float-right">{{$essential->due_date}}</a>
                  </li>
                  <li class="list-group-item">
                      @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts'))
                    <b>Status</b> <a class="float-right">
                        <input type="checkbox" name="essential-status" class="status" data-id="{{$essential->id}}" data-bootstrap-switch data-on-text="Active" 
                        data-off-text="Inactive" {{$essential->status == 'Active' ? 'checked' : ''}}>
                    </a>
                    @else
                    <b>Status</b> <a class="float-right">{{$essential->status}}</a>
                    @endif
                  </li>
                </ul>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->

          </div>
          <!-- /.col -->
          <div class="col-md-12">
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
                <p>Essential payments</p>
              </div><!-- /.card-header -->
              <div class="card-body">
                @if($essential->status != 'Active')
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Payments are disabled for this essential. Essential status must be "Active" to enable payments.
                </div>
                @endif
                <!--<div class="tab-content">-->
                <!--  <div class="active tab-pane" id="users">-->
                    <!--<button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal">Add New Payment</button>-->
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>S No</th>
                              <th>User</th>
                              <th>Block</th>
                              <th>Flat</th>
                              <th>Reason</th>
                              <th>Amount</th>
                              <th>Paid Amount</th>
                              <th>Due Amount</th>
                              <th>Type</th>
                              <th>Status</th>
                              <th>Action</th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php $i = 0;?>
                        @forelse($essential->payments as $payment)
                        <?php $i++; ?>
                        <tr>
                          <td>{{$i}}</td>
                          <td><a href="{{url('user',$payment->user_id)}}" target="_blank">{{$payment->user->name}}</a></td>
                          <td>{{$payment->flat->block->name}}</td>
                          <td>{{$payment->flat->name}}</td>
                          <td>{{$payment->essential->reason}}</td>
                          <td>{{$payment->essential->amount}}</td>
                          <td>{{$payment->paid_amount}}</td>
                          <td>{{$payment->dues_amount}}</td>
                          <td>{{$payment->type}}</td>
                          <td>{{$payment->status}}</td>
                          <td>
                          @if($payment->status == 'Unpaid' && $essential->status == 'Active')
                            @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts') )
                            <a href="{{url('essential/pay',$payment->id)}}" class="btn btn-sm btn-success" target="_blank">Pay Now</a>
                             @endif
                            @elseif($payment->status == 'Unpaid' && $essential->status != 'Active')
                            <span class="badge badge-warning">Payment Disabled</span>
                            @else
                            <a href="{{url('essential/reciept',$payment->id)}}" class="btn btn-sm btn-info" target="_blank">Reciept</a>
                            <a href="{{url('essential/invoice',$payment->id)}}" class="btn btn-sm btn-warning" target="_blank">Invoice</a>
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
                  
              <!--  </div>-->
                <!-- /.tab-content -->
              <!--</div><!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->
    

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
      $('#type').val(button.data('type'));
      $('#amount').val(button.data('amount'));
      $('#building_id').val(button.data('building_id'));
      $('#flat_id').val(button.data('flat_id'));
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
                url : "{{url('update-essential-status')}}",
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



