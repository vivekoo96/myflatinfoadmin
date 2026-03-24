@extends('layouts.admin')

@section('title', 'Pending Bills')

@section('content')
@php
function indian_money($amount, $decimals = 2) {
    $fmt = new \NumberFormatter('en_IN', \NumberFormatter::DECIMAL);
    $fmt->setAttribute(\NumberFormatter::FRACTION_DIGITS, $decimals);
    return $fmt->format($amount);
}
@endphp


 @php
 function getMaintenanceFinalAmount($flatId) {
        $maintenance_payments = App\Models\MaintenancePayment::where('flat_id', $flatId)
            ->where('status', 'Unpaid')
            ->with('maintenance')
            ->orderBy('id', 'desc')
            ->get();

       $total_amount = 0;
        $total_gst = 0;

        foreach ($maintenance_payments as $payment) {

            if (!$payment->maintenance) {
                continue;
            }

            /* ---------- Late fine calculation ---------- */
            $late_fine = 0;
            $dueDate = Carbon\Carbon::parse($payment->maintenance->due_date);

            if ($dueDate->lt(now()->startOfDay())) {
                $late_days = $dueDate->diffInDays(now());

                switch ($payment->maintenance->late_fine_type) {
                    case 'Daily':
                        $late_fine = $late_days * $payment->maintenance->late_fine_value;
                        break;

                    case 'Fixed':
                        $late_fine = $payment->maintenance->late_fine_value;
                        break;

                    case 'Percentage':
                        $late_fine = ($payment->dues_amount * $payment->maintenance->late_fine_value) / 100;
                        break;
                }
            }

            /* ---------- Amount before GST ---------- */
            $amount = $payment->dues_amount + $late_fine;

            /* ---------- GST from maintenances table ---------- */
            $gst = ($amount * $payment->maintenance->gst) / 100;

            /* ---------- Totals ---------- */
            $total_amount += $amount;
            $total_gst += $gst;
        }

        $grand_total = ceil($total_amount + $total_gst);
        return $grand_total;
      }
                
                  
                @endphp
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Pending Bills</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{url('/dashboard')}}">Home</a></li>
                        <li class="breadcrumb-item"><a href="#">Accounts</a></li>
                        <li class="breadcrumb-item active">Pending Bills</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endif
            <!-- Filter Section -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-filter"></i> Filter Bills
                    </h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{url('account/pending-bills')}}">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="flat_id">Select Flat</label>
                                    <select name="flat_id" id="flat_id" class="form-control">
                                        <option value="">All Flats</option>
                                        @foreach($blocks as $block)
                                            @foreach($block->flats as $flat)
                                                <option value="{{$flat->id}}" {{$flat_id == $flat->id ? 'selected' : ''}}>
                                                    {{$block->name}} - {{$flat->name}}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="bill_type">Bill Type</label>
                                    <select name="bill_type" id="bill_type" class="form-control">
                                        <option value="all" {{$bill_type == 'all' ? 'selected' : ''}}>All Bills</option>
                                        <option value="maintenance" {{$bill_type == 'maintenance' ? 'selected' : ''}}>Maintenance Only</option>
                                        <option value="essential" {{$bill_type == 'essential' ? 'selected' : ''}}>Essential Only</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label><br>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                    <a href="{{url('account/pending-bills')}}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Notification Section -->
                @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts'))
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bell"></i> Send Due Notifications
                    </h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{url('account/send-due-notifications')}}" id="notificationForm">
                        @csrf
                        <input type="hidden" name="flat_ids" value="" id="hidden_flat_ids">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="notification_type">Notification Type</label>
                                    <select name="notification_type" id="notification_type" class="form-control" required>
                                        <option value="all">All Bills</option>
                                        <option value="maintenance">Maintenance Only</option>
                                        <option value="essential">Essential Only</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="notification_flat_ids">Select Flats (Optional)</label>
                                    <select name="flat_ids[]" id="notification_flat_ids" class="form-control select2" multiple>
                                        @foreach($blocks as $block)
                                            @foreach($block->flats as $flat)
                                                <option value="{{$flat->id}}">
                                                    {{$block->name}} - {{$flat->name}}
                                                </option>
                                            @endforeach
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">Leave empty to send to all flats with pending bills</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>&nbsp;</label><br>
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Are you sure you want to send notifications?')">
                                        <i class="fas fa-paper-plane"></i> Send Notifications
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif
            <!-- Maintenance Bills Section -->
            @if($maintenance_payments->count() > 0)
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tools"></i> Pending Maintenance Bills 
                        <span class="badge badge-warning">{{$maintenance_payments->count()}}</span>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="maintenanceTablewww">
                            <thead>
                                <tr>
                                      <th>S No</th> 
                                    <th>Flat</th>
                                    <th>Owner/Tenant</th>
                                    <th>Maintenance Period</th>
                                    <th>Due Date</th>
                                    <th>Amount</th>
                                    <th>Late Fine</th>
                                    <th>GST</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 0; ?>
                                @foreach($maintenance_payments as $payment)
                                  <?php $i++; ?>
                                <tr>
                                     <td>{{$i}}</td>
                                    <td>{{$payment->flat->block->name}} - {{$payment->flat->name}}</td>
                                    <td>
                                        @if($payment->flat->tanent)
                                            {{$payment->flat->tanent->name}} (Tenant)
                                        @else
                                            {{$payment->flat->owner->name}} (Owner)
                                        @endif
                                    </td>
                                    <td>
                                        {{$payment->maintenance->from_date}} to {{$payment->maintenance->to_date}}
                                    </td>
                                    <td>
                                        <span class="badge {{\Carbon\Carbon::parse($payment->maintenance->due_date)->lt(now()) ? 'badge-danger' : 'badge-warning'}}">
                                            {{$payment->maintenance->due_date}}
                                        </span>
                                    </td>
                                    <td>₹{{number_format($payment->dues_amount, 2)}}</td>
                                    <td>₹{{number_format($payment->late_fine, 2)}}</td>
                                    <td>₹{{number_format($payment->gst_amount, 2)}}</td>
                                    <td><strong>₹@php echo indian_money(getMaintenanceFinalAmount($payment->flat_id));@endphp</strong></td>
                                    <td>
                                        <span class="badge badge-danger">Unpaid</span>
                                    </td>
                                    <td>
                                        <a href="{{url('account/maintenance/pay/'.$payment->flat->id)}}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-credit-card"></i> Pay
                                        </a>
                                        {{--
                                        <a href="{{url('account/maintenance/invoice/'.$payment->id)}}" class="btn btn-sm btn-info">
                                            <i class="fas fa-file-invoice"></i> Invoice
                                        </a>
                                        
                                        --}}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Essential Bills Section -->
            @if($essential_payments->count() > 0)
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-star"></i> Pending Essential Bills 
                        <span class="badge badge-info">{{$essential_payments->count()}}</span>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="maintenanceTablewww">
                            <thead>
                                <tr>
                                     <th>S No</th> 
                                    <th>Flat</th>
                                    <th>Owner/Tenant</th>
                                    <th>Essential Name</th>
                                    <th>Due Date</th>
                                    <th>Amount</th>
                                    <th>Late Fine</th>
                                    <th>GST</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $i = 0; ?>
                                @foreach($essential_payments as $payment)
                                <?php $i++; ?>
                                <tr>
                                     
                                     <td>{{$i}}</td> 
                                    <td>{{$payment->flat->block->name}} - {{$payment->flat->name}}</td>
                                    <td>
                                        @if($payment->flat->tanent)
                                            {{$payment->flat->tanent->name}} (Tenant)
                                        @else
                                            {{$payment->flat->owner->name}} (Owner)
                                        @endif
                                    </td>
                                   
                                    <td>{{$payment->essential->reason}}</td>
                                    <td>
                                        <span class="badge {{\Carbon\Carbon::parse($payment->essential->due_date)->lt(now()) ? 'badge-danger' : 'badge-warning'}}">
                                            {{$payment->essential->due_date}}
                                        </span>
                                    </td>
                                    <td>₹{{number_format($payment->dues_amount, 2)}}</td>
                                    <td>₹{{number_format($payment->late_fine, 2)}}</td>
                                    <td>₹{{number_format($payment->gst_amount, 2)}}</td>
                                    <td><strong>₹{{number_format($payment->grand_total, 2)}}</strong></td>
                                    <td>
                                        <span class="badge badge-danger">Unpaid</span>
                                    </td>
                                    <td>
                                        @if(Auth::User()->role == 'BA' || Auth::User()->selectedRole->name == 'Accounts')
                                        <a href="{{url('essential/pay/'.$payment->id)}}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-credit-card"></i> Pay
                                        </a>
                                        @endif
                                        <a href="{{url('essential/invoice/'.$payment->id)}}" class="btn btn-sm btn-info" target="_blank">
                                            <i class="fas fa-file-invoice"></i> Invoice
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- No Bills Found Section -->
            @if($maintenance_payments->count() == 0 && $essential_payments->count() == 0)
            <div class="card mb-3">
                <div class="card-body text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h4>No Pending Bills Found</h4>
                    <p class="text-muted">There are no pending bills for the selected criteria.</p>
                    <a href="{{url('account/pending-bills')}}" class="btn btn-primary">
                        <i class="fas fa-refresh"></i> Refresh
                    </a>
                </div>
            </div>
            @else
            <!-- Summary Section -->
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-bar"></i> Summary
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fas fa-tools"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Maintenance Bills</span>
                                    <span class="info-box-number">{{$maintenance_payments->count()}}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i class="fas fa-star"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Essential Bills</span>
                                    <span class="info-box-number">{{$essential_payments->count()}}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Overdue Bills</span>
                                    <span class="info-box-number">
                                        @php
                                            $overdue_maintenance = $maintenance_payments->filter(function($payment) {
                                                return $payment->maintenance && \Carbon\Carbon::parse($payment->maintenance->due_date)->lt(now()->startOfDay());
                                            })->count();
                                            
                                            $overdue_essential = $essential_payments->filter(function($payment) {
                                                return $payment->essential && \Carbon\Carbon::parse($payment->essential->due_date)->lt(now()->startOfDay());
                                            })->count();
                                        @endphp
                                        {{$overdue_maintenance + $overdue_essential}}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fas fa-rupee-sign"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Amount</span>
                                    <span class="info-box-number">
                                        ₹{{number_format($maintenance_payments->sum('grand_total') + $essential_payments->sum('grand_total'), 2)}}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </section>

@endsection

@section('script')
<style>
.card {
    box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
    border: 0;
    margin-bottom: 1rem;
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0,0,0,.125);
    padding: 0.75rem 1.25rem;
}

.card-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.info-box {
    display: block;
    min-height: 90px;
    background: #fff;
    width: 100%;
    box-shadow: 0 1px 1px rgba(0,0,0,0.1);
    border-radius: 2px;
    margin-bottom: 15px;
}

.info-box-icon {
    border-top-left-radius: 2px;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
    border-bottom-left-radius: 2px;
    display: block;
    float: left;
    height: 90px;
    width: 90px;
    text-align: center;
    font-size: 45px;
    line-height: 90px;
    background: rgba(0,0,0,0.2);
}

.info-box-content {
    padding: 5px 10px;
    margin-left: 90px;
}

.info-box-text {
    text-transform: uppercase;
    font-weight: bold;
    font-size: 14px;
}

.info-box-number {
    display: block;
    font-weight: bold;
    font-size: 18px;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.badge {
    font-size: 0.75em;
}

@media (max-width: 768px) {
    .info-box {
        margin-bottom: 10px;
    }
    
    .card-body {
        padding: 1rem;
    }
}
</style>

<script>
$(document).ready(function() {
    // Initialize Select2 only if it exists
    if ($.fn.select2 && $('.select2').length) {
        $('.select2').select2({
            placeholder: 'Select flats (optional)',
            allowClear: true,
            width: '100%'
        });
    }
    
    // Initialize DataTables only if tables exist and have content
    try {
        if ($.fn.DataTable && $('#maintenanceTablewww').length && $('#maintenanceTable tbody tr').length > 0) {
            $('#maintenanceTablewww').DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "pageLength": 25,
                "order": [[3, 'asc']], // Sort by due date
                "language": {
                    "emptyTable": "No maintenance bills found"
                }
            });
        }
        
        if ($.fn.DataTable && $('#essentialTablewww').length && $('#essentialTable tbody tr').length > 0) {
            $('#essentialTablewww').DataTable({
                "responsive": true,
                "lengthChange": false,
                "autoWidth": false,
                "pageLength": 25,
                "order": [[3, 'asc']], // Sort by due date
                "language": {
                    "emptyTable": "No essential bills found"
                }
            });
        }
    } catch (error) {
        console.log('DataTables initialization error:', error);
        // Fallback: Add basic table styling if DataTables fails
        $('table').addClass('table table-bordered table-striped');
    }
    
    // Handle notification form submission
    $('#notificationForm').on('submit', function(e) {
        var selectedFlats = $('#notification_flat_ids').val();
        $('#hidden_flat_ids').val(JSON.stringify(selectedFlats || []));
    });
    
    // Add loading state to notification button
    $('#notificationForm').on('submit', function() {
        var submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Sending...');
    });
});
</script>
@endsection
