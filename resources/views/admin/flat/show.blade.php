@extends('layouts.admin')

@section('title')
    Flat Details
@endsection

@push('styles')
<style>
  /* Responsive Table Styling */
  .table-responsive {
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  
  .table {
    margin-bottom: 0;
    font-size: 14px;
  }
  
  .table thead {
    background-color: #f8f9fa;
    position: sticky;
    top: 0;
    z-index: 10;
  }
  
  .table thead th {
    font-weight: 600;
    border-bottom: 2px solid #ddd;
    vertical-align: middle;
    white-space: nowrap;
    padding: 12px;
    cursor: pointer;
  }
  
  .table tbody td {
    padding: 10px 12px;
    vertical-align: middle;
  }
  
  .table tbody tr:hover {
    background-color: #f5f5f5;
  }
  
  /* Mobile Responsive */
  @media (max-width: 768px) {
    .table {
      font-size: 12px;
    }
    
    .table thead th {
      padding: 8px;
      font-size: 12px;
    }
    
    .table tbody td {
      padding: 8px;
      font-size: 12px;
    }
    
    /* Hide specific columns on mobile */
    .table th:nth-child(5),
    .table td:nth-child(5),
    .table th:nth-child(6),
    .table td:nth-child(6) {
      display: none;
    }
  }
  
  @media (max-width: 576px) {
    .table thead th {
      padding: 6px;
      font-size: 11px;
    }
    
    .table tbody td {
      padding: 6px;
      font-size: 11px;
    }
    
    /* Show only essential columns on very small screens */
    .table th:nth-child(n+4),
    .table td:nth-child(n+4) {
      display: none;
    }
  }
  
  /* Search Box Styling */
  .search-wrapper {
    margin-bottom: 15px;
    display: flex;
    gap: 10px;
    align-items: center;
  }
  
  .search-wrapper input {
    flex: 1;
    max-width: 300px;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
  }
  
  .search-wrapper label {
    font-weight: 500;
    margin: 0;
    white-space: nowrap;
  }
  
  /* Pagination Styling */
  .pagination-wrapper {
    margin-top: 15px;
    display: flex;
    justify-content: center;
    gap: 5px;
  }
  
  .pagination-wrapper button {
    padding: 6px 12px;
    border: 1px solid #ddd;
    background: white;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
  }
  
  .pagination-wrapper button:hover {
    background: #f5f5f5;
  }
  
  .pagination-wrapper button.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
  }
</style>
@endpush

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Flat Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Flat Details</li>
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
                </div>
                <h3 class="profile-username text-center">{{$flat->name}}</h3>

                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>Owner</b> <a class="float-right">{{$flat->owner ? $flat->owner->name : ''}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Tenant</b> <a class="float-right">{{$flat->tanent ? $flat->tanent->name : ''}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Block</b> <a class="float-right">{{$flat->block ? $flat->block->name : ''}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Members</b> <a class="float-right">{{$flat->family_members->count()}}</a>
                  </li>
               {{--   <li class="list-group-item">
                    <b>Status</b> <a class="float-right">
                        @if(Auth::User()->role == 'BA')
                        <input type="checkbox" name="my-checkbox" class="status" data-id="{{$flat->id}}" data-bootstrap-switch data-on-text="Active" 
                        data-off-text="Inactive" {{$flat->status == 'Active' ? 'checked' : ''}}>
                        @else
                        {{$flat->status}}
                        @endif
                    </a>
                  </li> --}}
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
                  <li class="nav-item"><a class="nav-link active" href="#family_members" data-toggle="tab">Family Members</a></li>
                  <li class="nav-item"><a class="nav-link" href="#parcels" data-toggle="tab">Parcels</a></li>
                  <li class="nav-item"><a class="nav-link" href="#payments" data-toggle="tab">Maintenance Payments</a></li>
                  <li class="nav-item"><a class="nav-link" href="#essentials" data-toggle="tab">Essentials</a></li>
                    <li class="nav-item"><a class="nav-link" href="#booking_history" data-toggle="tab">Booking History</a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">
                  <div class="active tab-pane" id="family_members">
                    @if($flat->building->hasPermission('Family member'))
                    <!--<button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal">Add New Family Member</button>-->
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>S No</th>
                              <th>Photo</th>
                              <th>Name</th>
                              <th>Relationship</th>
                              <th>Phone</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php $i = 0; ?>
                        @forelse($flat->family_members as $member)
                        <tr>
                          <td>{{$i++}}</td>
                          <td><img src="{{$member->photo}}" style="width:40px"></td>
                          <td>{{$member->name}}</td>
                          <td>{{$member->relationship}}</td>
                          <td>{{$member->phone}}</td>
                        </tr>
                        @empty
                        @endforelse
                      </tbody>
                    </table>
                    </div>
                    @else
                        <p class="text-danger">You don't have permission to access family members</p>
                    @endif
                  </div>
                  <!-- /.tab-pane -->
                  
                   <div class="tab-pane" id="parcels">
                    @if($flat->building->hasPermission('Parcel'))
                    <!--<button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal">Add New Parcel</button>-->
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>S No</th>
                              <th>Photo</th>
                              <th>Name</th>
                              <th>Status</th>
                              <th>Security Received at</th>
                              <th>Owner Received at</th>
                          </tr>
                      </thead>
                      <tbody>
                          @forelse($flat->parcels as $parcel)
                          <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                              @if($parcel->photo)
                                <img src="{{$parcel->photo}}" alt="Parcel Photo" style="width:40px; height:40px; object-fit:cover;">
                              @else
                                <span class="text-muted">No Photo</span>
                              @endif
                            </td>
                            <td>{{$parcel->name ?? 'N/A'}}</td>
                            <td>{{$parcel->status ?? 'N/A'}}</td>
                            <td>{{$parcel->created_at ? $parcel->created_at->format('Y-m-d H:i') : 'N/A'}}</td>
                            <td>{{$parcel->updated_at ? $parcel->updated_at->format('Y-m-d H:i') : 'N/A'}}</td>
                          </tr>
                          @empty
                          <tr>
                            <td colspan="6" class="text-center text-muted">No parcels found</td>
                          </tr>
                          @endforelse
                      </tbody>
                    </table>
                    </div>
                    @else
                        <p class="text-danger">You don't have permission to access parcels</p>
                    @endif
                  </div>
                  <!-- /.tab-pane -->
                  
                  <div class="tab-pane" id="payments">
                    @if($flat->building->hasPermission('Maintenance'))
                    <!--<button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal">Add New Maintenance Payment</button>-->
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>S No</th>
                              <th>From</th>
                              <th>To</th>
                              <th>Maintenance Fee</th>
                              <th>Due Date</th>
                              <th>Late Fine</th>
                              <th>Paid Amount</th>
                              <th>Dues Amount</th>
                              <th>Type</th>
                              <th>Status</th>
                              <th>Action</th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php $i = 0;?>
                        @forelse($flat->maintenance_payments as $payment)
                        <?php $i++; ?>
                        <tr>
                          <td>{{$i}}</td>
                          <td>{{$payment->maintenance->from_date}}</td>
                          <td>{{$payment->maintenance->to_date}}</td>
                          <td>{{$payment->maintenance->amount}}</td>
                          <td>{{$payment->maintenance->due_date}}</td>
                          <td>{{$payment->late_fine}} <small>({{$payment->maintenance->late_fine_value}}-{{$payment->maintenance->late_fine_type}})</small></td>
                          <td>{{$payment->paid_amount}}</td>
                          <td>{{$payment->dues_amount}}</td>
                          <td>{{$payment->type}}</td>
                          <td>{{$payment->status}}</td>
                          <td>
                      @if(Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts') )
                      <!--<button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal" data-id="{{$payment->id}}" data-dues_amount="{{$payment->dues_amount}}" data-late_fine="{{$payment->late_fine}}" -->
                      <!--data-user_id="{{$payment->user_id}}" data-type="{{$payment->type}}" data-amount="{{$payment->paid_amount}}" data-status="{{$payment->status}}" data-building_id="{{$payment->building_id}}"-->
                      <!--data-flat_id="{{$payment->flat_id}}" data-maintenance_id="{{$payment->maintenance_id}}"><i class="fa fa-edit"></i></button>-->
                      <!--@if($payment->deleted_at)-->
                      <!--<button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="{{$payment->id}}" data-action="restore"><i class="fa fa-undo"></i></button>-->
                      <!--@else-->
                      <!--<button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$payment->id}}" data-action="delete"><i class="fa fa-trash"></i></button>-->
                      <!--@endif-->
                      @endif
                    </td>
                        </tr>
                        @empty
                        @endforelse
                      </tbody>
                    </table>
                    </div>
                    @else
                        <p class="text-danger">You don't have permission to access maintenance</p>
                    @endif
                  </div>
                  <!-- /.tab-pane -->
                  
                  <div class="tab-pane" id="essentials">
                    @if($flat->building->hasPermission('Essential'))
                    <!--<button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal">Add New Maintenance Payment</button>-->
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>S No</th>
                              <th>Reason</th>
                              <th>Amount</th>
                              <th>Paid Amount</th>
                              <th>Dues Amount</th>
                              <th>Type</th>
                              <th>Status</th>
                              <th>Action</th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php $i = 0;?>
                        @forelse($flat->essential_payments as $payment)
                        <?php $i++; ?>
                        <tr>
                          <td>{{$i}}</td>
                          <td>{{$payment->essential->reason}}</td>
                          <td>{{$payment->essential->amount}}</td>
                          <td>{{$payment->paid_amount}}</td>
                          <td>{{$payment->dues_amount}}</td>
                          <td>{{$payment->type}}</td>
                          <td>{{$payment->status}}</td>
                          <td>
                      <!--@if(Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts') )-->
                      <!--<button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#essentialModal" data-id="{{$payment->id}}" data-dues_amount="{{$payment->dues_amount}}" -->
                      <!--data-user_id="{{$payment->user_id}}" data-type="{{$payment->type}}" data-amount="{{$payment->amount}}" data-status="{{$payment->status}}" data-building_id="{{$payment->building_id}}"-->
                      <!--data-flat_id="{{$payment->flat_id}}" data-essential_id="{{$payment->essential_id}}"><i class="fa fa-edit"></i></button>-->
                      <!--@if($payment->deleted_at)-->
                      <!--<button class="btn btn-sm btn-success" data-toggle="modal" data-target="#deleteModal" data-id="{{$payment->id}}" data-action="restore"><i class="fa fa-undo"></i></button>-->
                      <!--@else-->
                      <!--<button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal" data-id="{{$payment->id}}" data-action="delete"><i class="fa fa-trash"></i></button>-->
                      <!--@endif-->
                      <!--@endif-->
                    </td>
                        </tr>
                        @empty
                        @endforelse
                      </tbody>
                    </table>
                    </div>
                    @else
                        <p class="text-danger">You don't have permission to access essentials</p>
                    @endif
                  </div>
                  <!-- /.tab-pane -->
                  
                      <div class="tab-pane" id="booking_history">
                         @if($flat->building->hasPermission('Facility'))
                        <div class="table-responsive">
                          <table class="table table-bordered table-striped">
                            <thead>
                              <tr>
                                <th>S No</th>
                                <th>Facility</th>
                                <th>User</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php $i = 1; ?>
                              @forelse($bookings as $booking)
                             
                              <tr>
                                <td>{{ $i++ }}</td>
                                <td>{{ $booking->facility->name ?? '' }}</td>
                                <td>{{ $booking->user->name ?? '' }}</td>
                                <td>{{ $booking->date }}</td>
                                 <td>{{ $booking->timing->from ?? '' }} - {{ $booking->timing->to ?? '' }}</td>
                                <td>{{ $booking->status ?? '' }}</td>
                              </tr>
                              @empty
                              <tr><td colspan="6" class="text-center">No bookings found.</td></tr>
                              @endforelse
                            </tbody>
                          </table>
                        
                        </div>
                          @else
                             <p class="text-danger">You don't have permission to access bookings</p>
                          @endif
                         
                      </div>
                    
                  
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
                  
                      <!-- /.tab-pane -->
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
      <form action="{{url('store-maintenance-payment')}}" method="post" class="add-form">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label for="phone" class="col-form-label">Dues Amount:</label>
            <input type="text" name="dues_amount" class="form-control" id="dues_amount" placeholder="Dues Amount" required />
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Late Fine:</label>
            <input type="text" name="late_fine" class="form-control" id="late_fine" placeholder="Late Fine" required />
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Payment Type:</label>
            <select name="type" class="form-control" id="type" required>
              <option value="Created">Created</option>
              <option value="Cash">Cash</option>
              <option value="Online">Online</option>
            </select>
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Paid Amount:</label>
            <input type="text" name="amount" class="form-control" id="amount" placeholder="Amount" required />
          </div>
          
          <div class="form-group">
            <label for="status" class="col-form-label">Status:</label>
            <select name="status" class="form-control" id="status">
              <option value="Paid">Paid</option>
              <option value="Unpaid">Unpaid</option>
            </select>
          </div>
          
          <input type="hidden" name="id" id="edit-id">
          <input type="hidden" name="user_id" id="user_id">
          <input type="hidden" name="maintenance_id" id="maintenance_id" value="">
          <input type="hidden" name="flat_id" id="flat_id" value="">
          <input type="hidden" name="building_id" id="building_id" value="">
          
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="save-button">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

    
<!-- Essential Modal -->

<div class="modal fade" id="essentialModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Add New Payment</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{url('store-essential-payment')}}" method="post" class="add-form">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label for="phone" class="col-form-label">Dues Amount:</label>
            <input type="text" name="dues_amount" class="form-control" id="dues_amount2" placeholder="Dues Amount" required />
          </div>
          <div class="form-group">
            <label for="name" class="col-form-label">Payment Type:</label>
            <select name="type" id="type2" class="form-control" required>
              <option value="Created">Created</option>
              <option value="Cash">Cash</option>
              <option value="Online">Online</option>
            </select>
          </div>
          <div class="form-group">
            <label for="phone" class="col-form-label">Paid Amount:</label>
            <input type="text" name="amount" class="form-control" id="amount2" placeholder="Amount" value="" required />
          </div>
          
          <div class="form-group">
            <label for="status" class="col-form-label">Status:</label>
            <select name="status" id="status2" class="form-control">
              <option value="Paid">Paid</option>
              <option value="Unpaid">Unpaid</option>
            </select>
          </div>
          
          <input type="hidden" name="id" id="edit-id2">
          <input type="hidden" name="user_id" id="user_id2">
          <input type="hidden" name="essential_id" id="essential_id" value="">
          <input type="hidden" name="flat_id" id="flat_id2" value="">
          <input type="hidden" name="building_id" id="building_id2" value="">
          
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
      var url = "{{url('delete-building-user')}}";
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
      $('#dues_amount').val(button.data('dues_amount'));
      $('#late_fine').val(button.data('late_fine'));
      $('#amount').val(button.data('amount'));
      $('#status').val(button.data('status'));
      $('#type').val(button.data('type'));
      $('#flat_no').val(button.data('flat_no'));
      $('#user_id').val(button.data('user_id'));
      $('#building_id').val(button.data('building_id'));
      $('#flat_id').val(button.data('flat_id'));
      $('#maintenance_id').val(button.data('maintenance_id'));
      $('.modal-title').text('Add New Maintenace Payment');
      if(edit_id){
          $('.modal-title').text('Update Maintenace Payment');
      }
    });
    
    $('#essentialModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');
      $('#edit-id2').val(edit_id);
      $('#dues_amount2').val(button.data('dues_amount'));
      $('#amount2').val(button.data('dues_amount'));
      $('#flat_no2').val(button.data('flat_no'));
      $('#user_id2').val(button.data('user_id'));
      $('#building_id2').val(button.data('building_id'));
      $('#flat_id2').val(button.data('flat_id'));
      $('#essential_id').val(button.data('essential_id'));
      $('.modal-title').text('Add New Essential Payment');
      if(edit_id){
          $('.modal-title').text('Update Essential Payment');
      }
    });
    
    $('.status').bootstrapSwitch('state');
        $('.status').on('switchChange.bootstrapSwitch',function () {
            var id = $(this).data('id');
            $.ajax({
                url : "{{url('update-flat-status')}}",
                type: "post",
                data : {'_token':token,'id':id,},
                success: function(data)
                {
                  //
                }
            });
        });
        
    $('.add-form').on('submit', function (event) {
      if ($('#name').val().trim() === '') {
        event.preventDefault();
        $('.error').text('Customer Name is required. Please fetch user data.');
      }
    });
    
    // Fetch user data when clicking "Get User Data"
    $('#getUserData').on('click', function () {
      var email = $('#email').val().trim();
      if (email === '') {
        $('.error').text('Please enter an email to fetch user data.');
        return;
      }
      
      $('.error').text(''); // Clear previous errors
      
      $.ajax({
        url: '{{ url("get-user-by-email") }}', // Update with your actual route
        type: 'GET',
        data: { email: email },
        success: function (response) {
          if (response.success) {
            $('#name').val(response.data.name);
            $('#user_id').val(response.data.id);
          } else {
            $('.error').text('User not found.');
            $('#name').val('');
          }
        },
        error: function () {
          $('.error').text('Error fetching user data.');
          $('#name').val('');
        }
      });
    });

  });
</script>
@endsection

@endsection



