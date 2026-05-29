@extends('layouts.admin')

@section('title')
    Maintenance Details
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Maintenance Details</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Maintenance Details</li>
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

            <!-- Sidebar Info -->
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">
                <ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>From</b> <a class="float-right">{{$maintenance->from_date}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>To</b> <a class="float-right">{{$maintenance->to_date}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Due Date</b> <a class="float-right">{{$maintenance->due_date}}</a>
                  </li>
                  <li class="list-group-item">
                    <b>Status</b> <a class="float-right">
                        <input type="checkbox" name="my-checkbox" class="status" data-id="{{$maintenance->id}}" data-bootstrap-switch data-on-text="Active" 
                        data-off-text="Inactive" {{$maintenance->status == 'Active' ? 'checked' : ''}}>
                    </a>
                  </li>
                </ul>
              </div>
            </div>

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

            {{-- ======================== UPI PENDING APPROVALS BANNER ======================== --}}
            @php
                $pendingUpi = $maintenance->payments->where('type','UPI')->where('upi_payment_status','Pending');
            @endphp
            @if($pendingUpi->count() > 0 && (Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts')))
            <div class="alert alert-warning d-flex align-items-center" style="border-left:4px solid #ffc107;">
                <i class="fas fa-clock fa-lg mr-3"></i>
                <div>
                    <strong>{{ $pendingUpi->count() }} UPI Payment(s) Awaiting Approval</strong><br>
                    <small>Please review and approve/reject the UPI payment submissions below.</small>
                </div>
            </div>
            @endif

            <div class="card">
              <div class="card-header p-2">
                <ul class="nav nav-pills">
                  <li class="nav-item"><a class="nav-link active" href="#payments" data-toggle="tab">Payments</a></li>
                  <li class="nav-item"><a class="nav-link" href="#upi-pending" data-toggle="tab">
                    UPI Pending
                    @if($pendingUpi->count() > 0)
                        <span class="badge badge-warning ml-1">{{ $pendingUpi->count() }}</span>
                    @endif
                  </a></li>
                </ul>
              </div><!-- /.card-header -->
              <div class="card-body">
                <div class="tab-content">

                  {{-- ==================== ALL PAYMENTS TAB ==================== --}}
                  <div class="active tab-pane" id="payments">
                    <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>#</th>
                              <th>Flat</th>
                              <th>User</th>
                              <th>Type</th>
                              <th>Amount</th>
                              <th>Date</th>
                              <th>Status</th>
                              <th>UPI Status</th>
                              <th>Screenshot</th>
                              <th>Action</th>
                          </tr>
                      </thead>
                      <tbody>
                        <?php $i = 0;?>
                        @forelse($maintenance->payments as $payment)
                        <?php 
                           $i++; 
                           $late_fine = 0;
                           if ($maintenance->due_date) {
                               $dueDate = \Carbon\Carbon::parse($maintenance->due_date);
                               $calcDate = $payment->upi_submitted_at ? \Carbon\Carbon::parse($payment->upi_submitted_at) : ($payment->updated_at ?? now());
                               if ($dueDate->lt($calcDate->startOfDay())) {
                                   $late_days = $dueDate->diffInDays($calcDate);
                                   if ($maintenance->late_fine_type == 'Daily') $late_fine = $late_days * $maintenance->late_fine_value;
                                   elseif ($maintenance->late_fine_type == 'Fixed') $late_fine = $maintenance->late_fine_value;
                                   elseif ($maintenance->late_fine_type == 'Percentage') $late_fine = (($payment->dues_amount + $payment->paid_amount) * $maintenance->late_fine_value) / 100;
                               }
                           }
                           $total_before_gst = ($payment->dues_amount + $payment->paid_amount) + $late_fine;
                           $gst_amount = ($total_before_gst * $maintenance->gst) / 100;
                           $grand_total = ceil($total_before_gst + $gst_amount);
                           
                           // Use transaction amount if available and paid for accuracy
                           if ($payment->status == 'Paid' && $payment->transaction) {
                               $grand_total = $payment->transaction->amount;
                           }
                        ?>
                        <tr>
                          <td>{{$i}}</td>
                          <td>
                            <strong>{{ $payment->flat->name ?? '-' }}</strong>
                            <br><small class="text-muted">Owner: {{ $payment->flat->owner->name ?? 'N/A' }}</small>
                            <br><small class="text-muted">Tenant: {{ $payment->flat->tanent->name ?? 'N/A' }}</small>
                          </td>
                          <td><a href="{{url('user',$payment->user_id)}}" target="_blank">{{$payment->user->name ?? '-'}}</a></td>
                          <td>
                            @if($payment->type == 'UPI')
                              <span class="badge badge-info"><i class="fas fa-mobile-alt mr-1"></i>UPI</span>
                            @elseif($payment->type == 'Cash')
                              <span class="badge badge-secondary">Cash</span>
                            @elseif($payment->type == 'Online')
                              <span class="badge badge-primary">Online</span>
                            @else
                              <span class="badge badge-light">{{$payment->type}}</span>
                            @endif
                          </td>
                          <td>₹{{ number_format($grand_total, 2) }}</td>
                          <td>{{$payment->created_at ? $payment->created_at->format('d M Y') : '-'}}</td>
                          <td>
                            @if($payment->status == 'Paid')
                              <span class="badge badge-success">Paid</span>
                            @else
                              <span class="badge badge-danger">Unpaid</span>
                            @endif
                          </td>
                          <td>
                            @if($payment->type == 'UPI')
                              @if($payment->upi_payment_status == 'Approved')
                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Approved</span>
                              @elseif($payment->upi_payment_status == 'Rejected')
                                <span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Rejected</span>
                                @if($payment->upi_remarks)
                                  <br><small class="text-muted">{{ $payment->upi_remarks }}</small>
                                @endif
                              @elseif($payment->upi_payment_status == 'Pending')
                                <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Pending</span>
                              @else
                                <span class="text-muted">-</span>
                              @endif
                            @else
                              <span class="text-muted">-</span>
                            @endif
                          </td>
                          <td>
                            @if($payment->payment_screenshot)
                              <a href="{{ $payment->payment_screenshot }}" target="_blank">
                                <img src="{{ $payment->payment_screenshot }}"
                                     alt="Screenshot"
                                     style="max-width:50px; max-height:50px; border-radius:4px; border:1px solid #dee2e6; cursor:pointer;"
                                     title="Click to view full screenshot">
                              </a>
                            @else
                              <span class="text-muted">-</span>
                            @endif
                          </td>
                          <td>
                            @if(Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts'))
                              <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#addModal"
                                      data-id="{{$payment->id}}"
                                      data-user_email="{{$payment->user->email ?? ''}}"
                                      data-name="{{$payment->user->name ?? ''}}"
                                      data-user_id="{{$payment->user_id}}"
                                      data-type="{{$payment->type}}"
                                      data-amount="{{$payment->paid_amount}}"
                                      data-status="{{$payment->status}}">
                                <i class="fa fa-edit"></i>
                              </button>

                              @if($payment->type == 'UPI' && $payment->upi_payment_status == 'Pending')
                              <button class="btn btn-sm btn-success upi-approve-btn ml-1"
                                      data-id="{{ $payment->id }}"
                                      data-flat="{{ $payment->flat->name ?? $payment->flat_id }}"
                                      data-user="{{ $payment->user->name ?? '' }}"
                                      title="Approve UPI Payment">
                                <i class="fas fa-check"></i> Approve
                              </button>
                              <button class="btn btn-sm btn-danger upi-reject-btn ml-1"
                                      data-id="{{ $payment->id }}"
                                      data-flat="{{ $payment->flat->name ?? $payment->flat_id }}"
                                      data-user="{{ $payment->user->name ?? '' }}"
                                      title="Reject UPI Payment">
                                <i class="fas fa-times"></i> Reject
                              </button>
                              @endif
                            @endif
                          </td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center text-muted">No payments found</td></tr>
                        @endforelse
                      </tbody>
                    </table>
                    </div>
                  </div>

                  {{-- ==================== UPI PENDING TAB ==================== --}}
                  <div class="tab-pane" id="upi-pending">
                    @if($pendingUpi->count() == 0)
                      <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                        <p>No pending UPI payments to review.</p>
                      </div>
                    @else
                    <div class="row">
                      @foreach($pendingUpi as $payment)
                      <?php 
                           $late_fine = 0;
                           if ($maintenance->due_date) {
                               $dueDate = \Carbon\Carbon::parse($maintenance->due_date);
                               $calcDate = $payment->upi_submitted_at ? \Carbon\Carbon::parse($payment->upi_submitted_at) : ($payment->updated_at ?? now());
                               if ($dueDate->lt($calcDate->startOfDay())) {
                                   $late_days = $dueDate->diffInDays($calcDate);
                                   if ($maintenance->late_fine_type == 'Daily') $late_fine = $late_days * $maintenance->late_fine_value;
                                   elseif ($maintenance->late_fine_type == 'Fixed') $late_fine = $maintenance->late_fine_value;
                                   elseif ($maintenance->late_fine_type == 'Percentage') $late_fine = (($payment->dues_amount + $payment->paid_amount) * $maintenance->late_fine_value) / 100;
                               }
                           }
                           $total_before_gst = ($payment->dues_amount + $payment->paid_amount) + $late_fine;
                           $gst_amount = ($total_before_gst * $maintenance->gst) / 100;
                           $grand_total = ceil($total_before_gst + $gst_amount);
                      ?>
                      <div class="col-md-6 mb-3">
                        <div class="card card-warning card-outline">
                          <div class="card-header">
                            <h3 class="card-title">
                              <i class="fas fa-mobile-alt mr-1"></i>
                              Flat: <strong>{{ $payment->flat->name ?? $payment->flat_id }}</strong>
                              <br><small class="text-dark">Owner: <strong>{{ $payment->flat->owner->name ?? 'N/A' }}</strong> | Tenant: <strong>{{ $payment->flat->tanent->name ?? 'N/A' }}</strong></small>
                            </h3>
                            <div class="card-tools">
                              <span class="badge badge-warning">Pending</span>
                            </div>
                          </div>
                          <div class="card-body">
                            <div class="row">
                              <div class="col-md-6">
                                <p><strong>Amount Due:</strong> ₹{{ number_format($grand_total, 2) }}</p>
                                <p><strong>Submitted:</strong> {{ $payment->upi_submitted_at ? \Carbon\Carbon::parse($payment->upi_submitted_at)->format('d M Y, h:i A') : ($payment->updated_at ? $payment->updated_at->format('d M Y, h:i A') : '-') }}</p>
                              </div>
                              <div class="col-md-6 text-center">
                                @if($payment->payment_screenshot)
                                  <p><strong>Payment Screenshot</strong></p>
                                  <a href="{{ $payment->payment_screenshot }}" target="_blank">
                                    <img src="{{ $payment->payment_screenshot }}"
                                         alt="Payment Screenshot"
                                         class="img-thumbnail"
                                         style="max-width:140px; max-height:140px; cursor:pointer;">
                                  </a>
                                @else
                                  <p class="text-muted"><i class="fas fa-image"></i> No screenshot uploaded</p>
                                @endif
                              </div>
                            </div>
                          </div>
                          @if(Auth::User()->role == 'BA' || Auth::User()->hasRole('accounts'))
                          <div class="card-footer">
                            <button class="btn btn-success upi-approve-btn"
                                    data-id="{{ $payment->id }}"
                                    data-flat="{{ $payment->flat->name ?? $payment->flat_id }}"
                                    data-user="{{ $payment->user->name ?? '' }}">
                              <i class="fas fa-check mr-1"></i> Approve
                            </button>
                            <button class="btn btn-danger upi-reject-btn ml-2"
                                    data-id="{{ $payment->id }}"
                                    data-flat="{{ $payment->flat->name ?? $payment->flat_id }}"
                                    data-user="{{ $payment->user->name ?? '' }}">
                              <i class="fas fa-times mr-1"></i> Reject
                            </button>
                          </div>
                          @endif
                        </div>
                      </div>
                      @endforeach
                    </div>
                    @endif
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
    
<!-- Add/Edit Payment Modal -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addModalLabel">Add New Payment</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{url('store-maintenance-payment')}}" method="post" class="add-form">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label for="user_email" class="col-form-label">Customer Email:</label>
            <div class="input-group">
              <input type="email" name="user_email" class="form-control" id="user_email" maxlength="255" placeholder="Email" required>
              <div class="input-group-append">
                <button type="button" class="btn btn-primary" id="getUserData">Get User Data</button>
              </div>
            </div>
          </div>
          <div class="error text-danger"></div>
          <div class="form-group">
            <label class="col-form-label">Customer Name:</label>
            <input type="text" name="user_name" class="form-control" id="user_name" disabled required>
          </div>
          <div class="form-group">
            <label class="col-form-label">Payment Type:</label>
            <select name="type" class="form-control" required>
              <option value="Cash">Cash</option>
              <option value="Online">Online</option>
              <option value="UPI">UPI</option>
            </select>
          </div>
          <div class="form-group">
            <label class="col-form-label">Amount:</label>
            <input type="number" name="amount" class="form-control" id="amount" placeholder="Amount" min="0" required />
          </div>
          <div class="form-group">
            <label class="col-form-label">Status:</label>
            <select name="status" class="form-control">
              <option value="Paid">Paid</option>
              <option value="Unpaid">Unpaid</option>
            </select>
          </div>
          <input type="hidden" name="id" id="edit-id">
          <input type="hidden" name="user_id" id="user_id">
          <input type="hidden" name="maintenance_id" id="event_id" value="{{$maintenance->id}}">
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

<!-- UPI Approve Modal -->
<div class="modal fade" id="upiApproveModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title"><i class="fas fa-check-circle mr-2"></i>Approve UPI Payment</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to <strong>approve</strong> the UPI payment for:</p>
        <ul>
          <li><strong>Flat:</strong> <span id="approve-flat-name"></span></li>
          <li><strong>User:</strong> <span id="approve-user-name"></span></li>
        </ul>
        <div class="form-group">
          <label>Remarks (optional)</label>
          <input type="text" class="form-control" id="approve-remarks" placeholder="e.g. Payment verified via screenshot">
        </div>
        <input type="hidden" id="approve-payment-id">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" id="confirmApproveBtn">
          <i class="fas fa-check mr-1"></i> Confirm Approve
        </button>
      </div>
    </div>
  </div>
</div>

<!-- UPI Reject Modal -->
<div class="modal fade" id="upiRejectModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fas fa-times-circle mr-2"></i>Reject UPI Payment</h5>
        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to <strong>reject</strong> the UPI payment for:</p>
        <ul>
          <li><strong>Flat:</strong> <span id="reject-flat-name"></span></li>
          <li><strong>User:</strong> <span id="reject-user-name"></span></li>
        </ul>
        <div class="form-group">
          <label>Rejection Reason <span class="text-danger">*</span></label>
          <input type="text" class="form-control" id="reject-remarks" placeholder="e.g. Screenshot unclear, amount mismatch">
          <small class="text-muted">This reason will be sent to the resident.</small>
        </div>
        <input type="hidden" id="reject-payment-id">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmRejectBtn">
          <i class="fas fa-times mr-1"></i> Confirm Reject
        </button>
      </div>
    </div>
  </div>
</div>

@section('script')
<script>
  $(document).ready(function(){
    var token = "{{csrf_token()}}";
    
    // ===================== STANDARD PAYMENT MODAL =====================
    $('#addModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var edit_id = button.data('id');
      $('#edit-id').val(edit_id);
      $('#user_email').val(button.data('user_email'));
      $('#user_name').val(button.data('name'));
      $('#user_id').val(button.data('user_id'));
      $('[name="type"]').val(button.data('type'));
      $('#amount').val(button.data('amount'));
      $('#building_id').val(button.data('building_id'));
      $('#flat_id').val(button.data('flat_id'));
      $('[name="status"]').val(button.data('status'));
      $('.modal-title').text(edit_id ? 'Update Payment' : 'Add New Payment');
    });
    
    // Maintenance status toggle
    $('.status').bootstrapSwitch('state');
    $('.status').on('switchChange.bootstrapSwitch', function () {
        var id = $(this).data('id');
        $.ajax({
            url : "{{url('update-maintenance-status')}}",
            type: "post",
            data : {'_token':token,'id':id},
            success: function(data){}
        });
    });
    
    // Fetch user data by email
    $('#getUserData').on('click', function () {
      var email = $('#user_email').val().trim();
      if (!email) { $('.error').text('Please enter an email.'); return; }
      $('.error').text('');
      $.ajax({
        url: '{{ url("get-user-by-email") }}',
        type: 'POST',
        data: { _token: token, email: email },
        success: function (response) {
          if (response.success) {
            $('#user_name').val(response.data.name);
            $('#user_id').val(response.data.id);
          } else {
            $('.error').text('User not found.');
            $('#user_name').val('');
          }
        },
        error: function () { $('.error').text('Error fetching user data.'); }
      });
    });
    
    $('.add-form').on('submit', function (event) {
      if ($('#user_name').val().trim() === '') {
        event.preventDefault();
        $('.error').text('Customer Name is required. Please fetch user data.');
      }
    });

    // ===================== UPI APPROVE/REJECT =====================
    $(document).on('click', '.upi-approve-btn', function() {
      $('#approve-payment-id').val($(this).data('id'));
      $('#approve-flat-name').text($(this).data('flat'));
      $('#approve-user-name').text($(this).data('user'));
      $('#approve-remarks').val('');
      $('#upiApproveModal').modal('show');
    });

    $(document).on('click', '.upi-reject-btn', function() {
      $('#reject-payment-id').val($(this).data('id'));
      $('#reject-flat-name').text($(this).data('flat'));
      $('#reject-user-name').text($(this).data('user'));
      $('#reject-remarks').val('');
      $('#upiRejectModal').modal('show');
    });

    $('#confirmApproveBtn').on('click', function() {
      var id = $('#approve-payment-id').val();
      var remarks = $('#approve-remarks').val();
      var $btn = $(this);
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Approving...');
      $.ajax({
        url: '{{ url("approve-upi-payment") }}',
        type: 'POST',
        data: { _token: token, id: id, remarks: remarks },
        success: function() {
          $('#upiApproveModal').modal('hide');
          if (typeof toastr !== 'undefined') toastr.success('Payment approved successfully!');
          setTimeout(function(){ window.location.reload(); }, 800);
        },
        error: function() {
          if (typeof toastr !== 'undefined') toastr.error('Error approving payment. Please try again.');
          else alert('Error approving payment. Please try again.');
          $btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Confirm Approve');
        }
      });
    });

    $('#confirmRejectBtn').on('click', function() {
      var id = $('#reject-payment-id').val();
      var remarks = $('#reject-remarks').val().trim();
      if (!remarks) { 
          if (typeof toastr !== 'undefined') toastr.warning('Please provide a rejection reason.');
          else alert('Please provide a rejection reason.');
          return; 
      }
      var $btn = $(this);
      $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Rejecting...');
      $.ajax({
        url: '{{ url("reject-upi-payment") }}',
        type: 'POST',
        data: { _token: token, id: id, remarks: remarks },
        success: function() {
          $('#upiRejectModal').modal('hide');
          if (typeof toastr !== 'undefined') toastr.warning('Payment rejected and user notified.');
          setTimeout(function(){ window.location.reload(); }, 800);
        },
        error: function() {
          if (typeof toastr !== 'undefined') toastr.error('Error rejecting payment. Please try again.');
          else alert('Error rejecting payment. Please try again.');
          $btn.prop('disabled', false).html('<i class="fas fa-times mr-1"></i> Confirm Reject');
        }
      });
    });

  });
</script>
@endsection

@endsection
