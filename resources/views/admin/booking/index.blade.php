@extends('layouts.admin')

@section('title')
    Bookings
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Facility Booking</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Facility Booking</li>
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
                <div class="card-body">
                  <form method="GET" action="{{ url('booking') }}">
                    <div class="form-row">
                      <div class="form-group col-md-6">
                        <label for="model" class="col-form-label">Facility:</label>
                        <select name="facility_id" id="facility_id" class="form-control" required>
                          <option value="All">All</option>
                          @forelse($facilities as $facility)
                            <option value="{{$facility->id}}" {{ request('facility_id') == $facility->id ? 'selected' : ''}}>{{$facility->name}}</option>
                          @empty
                          @endforelse
                        </select>
                      </div>
                      <div class="form-group col-md-6">
                        
                      </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-md-6">
                        <label for="from_date">From Date</label>
                        <input type="date" id="from_date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                      </div>
                      <div class="form-group col-md-6">
                        <label for="to_date">To Date</label>
                        <input type="date" id="to_date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                        </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-3">
                        <label>&nbsp;</label>
                        <a href="{{ url('booking') }}" class="btn btn-secondary btn-block mt-2">Reset</a>
                      </div>
                      <div class="form-group col-md-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block mt-2">Filter</button>
                      </div>
                    </div>
                    </form>
                </div>
            </div>



            <div class="card">
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                     <th>S No</th> 
                    <th>Facility</th>
                    <th>Booking Type</th>
                    <th>Booked By</th>
                    <th>Flat</th>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Members</th>
                    <th>Paid Amount</th>
                    <th>Refunded Amount</th>
                    <th>Reciept</th>
                    <!--<th>Order Id</th>-->
                    <!--<th>Transaction Id</th>-->
                    <th>Booked on</th>
                    <th>Status</th>
                    <!-- @if( (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts')|| Auth::User()->role == 'BA' || Auth::User()->selectedRole->name == 'Facility' ) -->
                    <!--<th>Action</th>-->
                    <!--@endif-->
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  @forelse($bookings as $booking)
                  <?php $i++; ?>
                  <tr>
                     <td>{{$i}}</td> 
                    <td><a href="{{url('facility',$booking->facility_id)}}" target="_blank">{{$booking->facility->name}}</a></td>
                    <td>{{$booking->type}}</td>
                    <td><a href="{{url('user',$booking->user_id)}}" target="_blank">{{$booking->user->name}}</a></td>
                     <td><a href="{{url('flat',$booking->flat_id ?? '')}}" target="_blank">{{$booking->flat->name ?? ''}}</a></td>
                    <td>{{$booking->date}}</td>
                    <td>{{$booking->timing->from}}</td>
                    <td>{{$booking->timing->to}}</td>
                    <td>{{$booking->members}}</td>
                    <td>{{$booking->paid_amount}}</td>
                    <td>{{$booking->refunded_amount}}</td>
                    <td>{{$booking->reciept_no}}</td>
                    <!--<td>{{$booking->order_id}}</td>-->
                    <!--<td>{{$booking->transaction_id}}</td>-->
                    <td>{{$booking->created_at->diffForHumans()}}</td>
                    <td>{{$booking->status}}</td>
                    <!--@if( (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts')|| Auth::User()->role == 'BA' || Auth::User()->selectedRole->name == 'Facility' ) -->
                    <!--<td>-->
                    <!--    @if($booking->type == 'Offline' && $booking->status == 'Created')-->
                    <!--    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#verifyModal" data-id="{{$booking->id}}" data-status="{{$booking->status}}" -->
                    <!--    data-amount="{{$booking->amount}}" data-payable_amount="{{$booking->payable_amount}}" data-paid_amount="{{$booking->paid_amount}}">-->
                    <!--      Verify</button>-->
                    <!--    @endif-->
                        
                    <!--    @if($booking->status == 'Success' || $booking->status == 'Cancel Request')-->
                    <!--    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#cancelModal" data-id="{{$booking->id}}" data-status="{{$booking->status}}" -->
                    <!--    data-amount="{{$booking->amount}}" data-payable_amount="{{$booking->payable_amount}}" data-paid_amount="{{$booking->paid_amount}}" data-refundable_amount="{{$booking->refundable_amount}}" -->
                    <!--    data-cancellation_type="{{$booking->timing->cancellation_type}}">Cancel</button>-->
                    <!--    @endif-->
                    <!--</td>-->
                    <!--@endif-->
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
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->

<!--verify modal-->
<div class="modal fade" id="verifyModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Are you sure ?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{url('change-booking-status')}}" method="post" class="cancel-form">
      @csrf
      <div class="modal-body">
        <p class="text">You are going to verify this booking</p>
            <div class="form-group">
                <label for="name" class="col-form-label">Payable Amount (with gst):</label>
                <input type="number" name="amount" id="amount" class="form-control" placeholder="Paid Amount" min="1" disabled>
            </div>
            
            <div class="form-group">
                <label for="name" class="col-form-label">Payment Type:</label>
                <select name="payment_type" id="payment_type" class="form-control" id="payment_type" required>
                    <option value="InHand">InHand</option>
                    <option value="InBank">InBank</option>
                </select>
            </div>
            
            <input type="hidden" name="booking_id" class="booking_id" value="" required>
            <input type="hidden" name="status" class="" value="Success" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-success" id="verify-button">Confirm Booking</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!--cancel modal-->
<div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Are you sure ?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{url('change-booking-status')}}" method="post" class="cancel-form">
      @csrf
      <div class="modal-body">
        <p class="text">You are going to cancel this booking</p>
        <div class="form-group">
            <label for="name" class="col-form-label">Paid Amount (with gst):</label>
            <input type="number" name="paid_amount" id="paid_amount" class="form-control" placeholder="Paid Amount" min="1" disabled>
        </div>
        <div class="form-group">
            <label for="name" class="col-form-label">Refundable Amount:</label>
            <input type="number" name="refund_amount" id="refundable_amount" class="form-control" placeholder="Refund Amount" 
            disabled >
            <!--min="1"-->
        </div>
        <div class="form-group">
            <label for="name" class="col-form-label">Payment Type:</label>
            <select name="payment_type" id="payment_type" class="form-control" id="payment_type" required>
                <option value="InHand">InHand</option>
                <option value="InBank">InBank</option>
            </select>
        </div>
        <input type="hidden" name="booking_id" class="booking_id" value="" required>
        <input type="hidden" name="status" id="status" value="Cancelled" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-success" id="cancel-button">Cancel Booking</button>
      </div>
      </form>
    </div>
  </div>
</div>
@section('script')


<script>
  $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "{{csrf_token()}}";
    var model = "{{ request('model') }}";
    var model_id = "{{ request('model_id') }}";
    
    $(document).on('change','#model',function(){
      var model = $(this).val();
      $('.model-id').html('');
      if(model == 'Event' || model == 'Essential' || model == 'Booking'){
        $.ajax({
          url : "{{url('/get-model-data')}}",
          type: "post",
          data : {'_token':token,'model':model},
          success: function(data)
          {
            $('.model-id').html(data);
          }
        });
      }
    });

    if(model == 'Event' || model == 'Essential' || model == 'Booking'){
        $.ajax({
          url : "{{url('/get-model-data')}}",
          type: "post",
          data : {'_token':token,'model':model,'model_id':model_id},
          success: function(data)
          {
            $('.model-id').html(data);
          }
        });
      };
      
    $('#verifyModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      $('.booking_id').val(button.data('id'));
      $('#amount').val(button.data('payable_amount'));
    });
    
    // $(document).on('click','#verify-button',function(){
    //   $.ajax({
    //     url : "{{url('change-booking-status')}}",
    //     type: "POST",
    //     data : {'_token':token,'booking_id':id,'status':'Success'},
    //     success: function(data)
    //     {
    //         window.location.reload();
    //     }
    //   });
    // });
    
    $('#cancelModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var cancellation_type = button.data('cancellation_type');
      $('.booking_id').val(button.data('id'));
      $('#paid_amount').val(button.data('paid_amount'));
      $('#refundable_amount').val(button.data('refundable_amount'));
      $('#refundable_amount').attr('required',false);
      $('#refundable_amount').attr('disabled',true);
      if(cancellation_type == 'Manual'){
          $('#refundable_amount').attr('required',true);
          $('#refundable_amount').attr('disabled',false);
          $('#refundable_amount').attr('max',button.data('payable_amount'));
      }
    });

    // $(document).on('click','#cancel-button',function(){
    //   var refund_amount = $('#refund_amount').val();
    //   $.ajax({
    //     url : "{{url('change-booking-status')}}",
    //     type: "POST",
    //     data : {'_token':token,'booking_id':id,'refund_amount':refund_amount,'status':'Cancelled'},
    //     success: function(data)
    //     {
    //          window.location.reload();
    //     }
    //   });
    // });

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