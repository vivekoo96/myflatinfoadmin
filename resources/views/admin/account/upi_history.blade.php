@extends('layouts.admin')

@section('title', 'UPI Approval History')

@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">UPI Approval History</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{url('/dashboard')}}">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{url('account/upi-pending')}}">UPI Pending</a></li>
                        <li class="breadcrumb-item active">History</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Filter -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" action="{{url('account/upi-history')}}" class="form-row align-items-end">
                        <div class="form-group col-md-3">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">All</option>
                                <option value="Approved" {{ request('status') == 'Approved' ? 'selected' : '' }}>Approved</option>
                                <option value="Rejected" {{ request('status') == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label for="from_date">From</label>
                            <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
                        </div>
                        <div class="form-group col-md-3">
                            <label for="to_date">To</label>
                            <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
                        </div>
                        <div class="form-group col-md-3">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                            <a href="{{url('account/upi-history')}}" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Approved / Rejected UPI Payments <span class="badge badge-secondary">{{ $payments->count() }}</span></h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="upiHistoryTable">
                            <thead>
                                <tr>
                                    <th>S No</th>
                                    <th>Flat</th>
                                    <th>Owner/Tenant</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Screenshot</th>
                                    <th>Status</th>
                                    <th>Remarks</th>
                                    <th>Submitted</th>
                                    <th>Decided</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 0; ?>
                                @forelse($payments as $payment)
                                <?php $i++; ?>
                                <tr>
                                    <td>{{ $i }}</td>
                                    <td>{{ ($payment->flat->block->name ?? '') }} - {{ $payment->flat->name ?? $payment->flat_id }}</td>
                                    <td>
                                        @if($payment->flat && $payment->flat->tanent)
                                            {{ $payment->flat->tanent->name }} (Tenant)
                                        @elseif($payment->flat && $payment->flat->owner)
                                            {{ $payment->flat->owner->name }} (Owner)
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->type == 'UPI')
                                            <span class="badge badge-info"><i class="fas fa-mobile-alt mr-1"></i>UPI</span>
                                        @elseif($payment->type == 'bank')
                                            <span class="badge badge-warning"><i class="fas fa-university mr-1"></i>Bank</span>
                                        @elseif($payment->type == 'Online')
                                            <span class="badge badge-primary">Online</span>
                                        @else
                                            <span class="badge badge-light">{{$payment->type}}</span>
                                        @endif
                                    </td>
                                    <td>₹{{ number_format(optional($payment->transaction)->amount ?? ($payment->paid_amount ?: $payment->dues_amount), 2) }}</td>
                                    <td>
                                        @if($payment->payment_screenshot)
                                            <a href="{{ $payment->payment_screenshot }}" target="_blank">View</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($payment->upi_payment_status == 'Approved')
                                            <span class="badge badge-success">Approved</span>
                                        @else
                                            <span class="badge badge-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td>{{ $payment->upi_remarks ?: '-' }}</td>
                                    <td>{{ $payment->upi_submitted_at ? \Carbon\Carbon::parse($payment->upi_submitted_at)->format('d M Y, h:i A') : '-' }}</td>
                                    <td>{{ $payment->updated_at ? $payment->updated_at->format('d M Y, h:i A') : '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="10" class="text-center text-muted">No UPI approval history found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('script')
<script>
$(document).ready(function() {
    try {
        if ($.fn.DataTable && $('#upiHistoryTable tbody tr').length > 0) {
            $('#upiHistoryTable').DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "pageLength": 25,
                "order": [[8, 'desc']]
            });
        }
    } catch (e) { console.log('DataTables init error:', e); }
});
</script>
@endsection
