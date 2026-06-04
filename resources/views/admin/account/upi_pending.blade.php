@extends('layouts.admin')

@section('title', 'UPI Pending Approvals')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">UPI Pending Approvals</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{url('/dashboard')}}">Home</a></li>
                        <li class="breadcrumb-item"><a href="#">Accounts</a></li>
                        <li class="breadcrumb-item active">UPI Pending</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-outline card-warning">
                <div class="card-header">
                    <h3 class="card-title">Pending UPI Payments</h3>
                </div>
                <div class="card-body">
                    @if($pendingUpi->count() == 0)
                      <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                        <p>No pending UPI payments to review.</p>
                      </div>
                    @else
                    <div class="row">
                      @foreach($pendingUpi as $payment)
                      <?php
                           // Same calculation as account/pending-bills (shared helper),
                           // but late fine is frozen at the submission date.
                           $grand_total = $payment->calculateGrandTotal($payment->upi_submitted_at);
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
                                  <a href="{{ asset('public/maintenance_screenshots/'.$payment->payment_screenshot) }}" target="_blank">
                                    <img src="{{ asset('public/maintenance_screenshots/'.$payment->payment_screenshot) }}"
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
            </div>
        </div>
    </section>
@endsection

@section('script')
<script>
$(document).ready(function() {
    $('.upi-approve-btn').click(function() {
        if(!confirm('Are you sure you want to approve this UPI payment?')) return;
        var btn = $(this);
        var id = btn.data('id');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Approving...');
        
        $.ajax({
            url: "{{ url('approve-upi-payment') }}",
            type: "POST",
            data: {
                id: id,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if(response.msg === 'success' || response.status === true) {
                    if(typeof toastr !== 'undefined') {
                        toastr.success('Payment approved successfully');
                        setTimeout(function(){ location.reload(); }, 1500);
                    } else {
                        alert('Payment approved successfully');
                        location.reload();
                    }
                } else {
                    btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Approve');
                    alert('Error approving payment');
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="fas fa-check mr-1"></i> Approve');
                alert('Server error occurred');
            }
        });
    });

    $('.upi-reject-btn').click(function() {
        var remarks = prompt('Please enter a reason for rejecting this payment:');
        if(remarks === null) return;
        var btn = $(this);
        var id = btn.data('id');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Rejecting...');
        
        $.ajax({
            url: "{{ url('reject-upi-payment') }}",
            type: "POST",
            data: {
                id: id,
                remarks: remarks,
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                if(response.msg === 'success' || response.status === true) {
                    if(typeof toastr !== 'undefined') {
                        toastr.success('Payment rejected successfully');
                        setTimeout(function(){ location.reload(); }, 1500);
                    } else {
                        alert('Payment rejected successfully');
                        location.reload();
                    }
                } else {
                    btn.prop('disabled', false).html('<i class="fas fa-times mr-1"></i> Reject');
                    alert('Error rejecting payment');
                }
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="fas fa-times mr-1"></i> Reject');
                alert('Server error occurred');
            }
        });
    });
});
</script>
@endsection
