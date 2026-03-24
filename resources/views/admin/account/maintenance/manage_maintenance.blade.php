@extends('layouts.admin')

@section('title')
    Manage Maintenance
@endsection

@section('content')

@php
function indian_money($amount, $decimals = 2) {
    $fmt = new \NumberFormatter('en_IN', \NumberFormatter::DECIMAL);
    $fmt->setAttribute(\NumberFormatter::FRACTION_DIGITS, $decimals);
    return $fmt->format($amount);
}
@endphp

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
                  <form method="GET" action="{{ url('account/maintenance/manage') }}">
                    <div class="form-row">
                       <div class="form-group col-md-6">
                        <label for="block" class="col-form-label">Block:</label>
                        <select name="block" id="block" class="form-control">
                          <option value="" {{ request('block') == '' ? 'selected' : '' }}>All</option>
                          @forelse($blocks as $block)
                          <option value="{{$block->id}}" {{ request('block') == $block->id ? 'selected' : ''}}>{{$block->name}}</option>
                          @empty
                          @endforelse
                        </select>
                      </div>
                      
                      <div class="form-group col-md-6" style="margin-top: 5px;">
                        <label for="flat_id">Flat</label>
                        <select name="flat_id" id="flat_id" class="form-control">
                          <option value="">Select Flat</option>
                          <!-- Options will be populated by AJAX -->
                        </select>
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
                      <div class="form-group col-md-6">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block mt-2">Filter</button>
                      </div>
                      <div class="form-group col-md-6">
                        <label>&nbsp;</label>
                        <button type="button" id="clearFilter" class="btn btn-secondary btn-block mt-2">Clear Filter</button>
                      </div>
                    </div>
                    </form>
                </div>
            </div>


            <div class="card">
              <div class="card-header p-2">
                <div class="row">
                  <div class="col-md-6">
                    <h3 class="card-title">Maintenance Records</h3>
                    <small class="text-muted">Total records: {{ $maintenance_payments->count() }}</small>
                  </div>
                  <div class="col-md-6">
                    <div class="input-group">
                      <input type="text" id="customSearch" class="form-control" placeholder="Search by block, flat, tenant, owner, amount, status...">
                      <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="clearSearch" title="Clear search">
                          <i class="fas fa-times"></i>
                        </button>
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                      </div>
                    </div>
                  </div>
                </div>
              </div><!-- /.card-header -->
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
              
              
              <div class="card-body">
                    <!--<button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addModal">Add New Maintenance Payment</button>-->
                    <div class="table-responsive">
                    <table id="maintenanceTable" class="maintenance-table table-bordered table-striped">
                      <thead>
                          <tr>
                              <th>S No</th>
                              <th>Block</th>
                              <th>Flat</th>
                              <th>Living</th>
                              <th>Owner</th>
                              <th>Tenant</th>
                              <th>From</th>
                              <th>To</th>
                              <th>Due Date</th>
                              <th>GST(%)</th>
                              <th>Paid Amount</th>
                              <th>Due Amount</th>
                              <th>Status</th>
                          
                              <th>Action</th>
                            
                          </tr>
                      </thead>
                      <tbody>
                        <?php $i = 0;?>
                        @forelse($maintenance_payments as $payment)
                        <?php $i++; ?>
                        <tr>
                          <td>{{$i}}</td>
                 <td>{{$payment->flat->block->name}}</td>
<td>{{$payment->flat->name}}</td>
<td>
  @php
    $living = strtolower(trim($payment->flat->living_status ?? ''));
  @endphp
  @if($living === 'tanent' || $living === 'tenant')
    Tenant
  @else
    {{ $payment->flat->living_status }}
  @endif
</td>
                          <td>{{$payment->flat->owner ? $payment->flat->owner->name : '-'}}</td>
                          <td>{{$payment->flat->tanent ? $payment->flat->tanent->name : '-'}}</td>
                        
                          <td>{{$payment->maintenance->from_date}}</td>
                          <td>{{$payment->maintenance->to_date}}</td>
                          <td>{{$payment->maintenance->due_date}}</td>
                            <td>{{$payment->maintenance->gst}}</td>
                             @if($payment->status == 'Paid')
                              <td>
                               @php
    // Use same calculation as controller - get all payments in the transaction
    $transaction = $payment->transaction;
    $maintenance_payments = $transaction->maintenance_payments()
        ->with(['maintenance', 'flat.owner', 'flat.tanent', 'flat.block', 'flat.building'])
        ->orderBy('id', 'desc')
        ->get();

    $total_payment = 0;
    $total_gst = 0;
    foreach ($maintenance_payments as $pay) {
        $maint = $pay->maintenance;
        $late_fine = 0;

        $dueDate = \Carbon\Carbon::parse($maint->due_date);
        if ($maint && $dueDate->lt(\Carbon\Carbon::now()->startOfDay())) {
            $late_days = $dueDate->diffInDays(\Carbon\Carbon::now());

            switch ($maint->late_fine_type) {
                case 'Daily':
                    $late_fine = $late_days * $maint->late_fine_value;
                    break;
                case 'Fixed':
                    $late_fine = $maint->late_fine_value;
                    break;
                case 'Percentage':
                    $late_fine = ($pay->paid_amount * $maint->late_fine_value) / 100;
                    break;
            }
        }

        $pay->late_fine = $late_fine;
        $total = $pay->paid_amount + $late_fine;
        $pay->gst = $total * $pay->maintenance->gst / 100;
        $total_payment += $total;
        $total_gst += $pay->gst;
    }

    $gst = $total_gst;
    $grand_total = $total_payment + $gst;
    $grand_total = ceil($grand_total);
    
    echo indian_money($grand_total);
  @endphp
                                  </td>
                             @else
                             <td>0</td>
                             @endif
                             @if($payment->status == "Unpaid")
                                <td>
                                  @php
                              echo indian_money(getMaintenanceFinalAmount($payment->flat_id));
                            @endphp
                              </td>
                              @else
                               <td>0</td>
                              @endif
                          <td>{{$payment->status}}</td>
                          <td>
                            @if($payment->status == 'Unpaid')
                             @if(Auth::User()->role == 'BA' || Auth::user()->selectedRole->name == "Accounts")
                            <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#paymentModal" 
                                    onclick="setPaymentData('{{$payment->id}}', '{{$payment->flat->owner ? $payment->flat->owner->name : ''}}', '{{$payment->flat->tanent ? $payment->flat->tanent->name : ''}}', '{{$payment->flat_id}}')">
                                Pay Now
                            </button>
                            @endif
                            @else
                            <a href="{{url('account/maintenance/reciept',$payment->id)}}" class="btn btn-sm btn-info">Reciept</a>
                            <a href="{{url('account/maintenance/invoice',$payment->id)}}" class="btn btn-sm btn-warning">Invoice</a>
                            @endif
                          </td>
                        </tr>
                        @empty
                        @endforelse
                      </tbody>
                    </table>
                    </div>
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

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="paymentModalLabel">Select Payment Person</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="paymentForm" method="GET">
          <input type="hidden" id="payment_id" name="payment_id">
          <input type="hidden" id="flat_id_payment" name="flat_id">
          
          <div class="form-group">
            <label for="payment_person">Pay as:</label>
            <select class="form-control" id="payment_person" name="payment_person" required>
              <option value="">Select Person</option>
              <option value="owner" id="owner_option">Owner</option>
              <option value="tenant" id="tenant_option">Tenant</option>
            </select>
          </div>
          
          <div class="alert alert-info">
            <strong>Owner:</strong> <span id="owner_name_display"></span><br>
            <strong>Tenant:</strong> <span id="tenant_name_display"></span>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-success" onclick="proceedPayment()">Proceed to Pay</button>
      </div>
    </div>
  </div>
</div>

@section('script')

<style>
.maintenance-table {
    width: 100%;
    margin-bottom: 1rem;
    color: #212529;
    border-collapse: collapse;
}
.maintenance-table th,
.maintenance-table td {
    padding: 0.75rem;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}
.maintenance-table thead th {
    vertical-align: bottom;
    border-bottom: 2px solid #dee2e6;
    background-color: #f8f9fa;
}
</style>

<script>
  $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "{{csrf_token()}}";

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
    
    $(document).on('change','#block',function(){
      var block = $(this).val();
      var url = "{{url('/get-flats')}}";
      $('#flat_id').html('<option value="">Select Flat</option>');
        $.ajax({
          url : "{{url('/get-flat-data')}}",
          type: "post",
          data : {'_token':token,'block_id':block},
          success: function(data)
          {
            $('#flat_id').html(data);
          }
        });
    });

    var block = $('#block').val();
    var flat_id = "{{request('flat_id')}}";
    $.ajax({
          url : "{{url('/get-flat-data')}}",
          type: "post",
          data : {'_token':token,'block_id':block,'flat_id':flat_id},
          success: function(data)
          {
            $('#flat_id').html(data);
          }
        });

    // Initialize DataTable with advanced search functionality
            var table = $('#maintenanceTable').DataTable({
        "responsive": false,
        "lengthChange": false,
        "autoWidth": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "paging": true,
        "pageLength": 25,
        "dom": 'rt<"bottom"ip>', // Hide default search box
        "language": {
            "emptyTable": "No maintenance records found",
            "zeroRecords": "No matching records found",
            "info": "Showing _START_ to _END_ of _TOTAL_ records",
            "infoEmpty": "Showing 0 to 0 of 0 records",
            "infoFiltered": "(filtered from _MAX_ total records)"
        },
        "columnDefs": [
          { "orderable": false, "targets": [11] }, // Disable sorting on Action column (updated index)
          { "searchable": true, "targets": "_all" }
        ],
        "order": [[ 0, "asc" ]]
    });

    // Custom search function that respects block filter
    function customSearch() {
        var searchTerm = $('#customSearch').val().toLowerCase();
        var selectedBlockVal = $('#block').val();
        var selectedBlockText = $('#block option:selected').text().toLowerCase();
        var fromDate = $('#from_date').val();
        var toDate = $('#to_date').val();

        $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
          var blockMatch = true;
          var searchMatch = true;
          var dateMatch = true;

          // Block filter: if 'All' (empty value) is selected, show all; else match block id (data[1] is block name)
          if (selectedBlockVal && selectedBlockVal !== '') {
            blockMatch = data[1].toLowerCase() === selectedBlockText;
          } else {
            blockMatch = true; // 'All' selected, show all
          }

          // Date filter: data[6] = 'From', data[7] = 'To' (column indices may need adjustment)
          if (fromDate) {
            var rowFrom = data[6] ? data[6].trim() : '';
            if (rowFrom && rowFrom < fromDate) {
              dateMatch = false;
            }
          }
          if (toDate) {
            var rowTo = data[7] ? data[7].trim() : '';
            if (rowTo && rowTo > toDate) {
              dateMatch = false;
            }
          }

          // Text search across all columns
          if (searchTerm) {
            searchMatch = false;
            for (var i = 0; i < data.length; i++) {
              if (data[i].toLowerCase().includes(searchTerm)) {
                searchMatch = true;
                break;
              }
            }
          }

          return blockMatch && searchMatch && dateMatch;
        });

        table.draw();

        // Clear the custom search function
        $.fn.dataTable.ext.search.pop();
      }

    // Connect custom search input to DataTable with debouncing
    var searchTimeout;
    $('#customSearch').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            customSearch();
        }, 300);
    });

    // Block change triggers search update
    $('#block').on('change', function() {
        customSearch();
    });

    // Clear search button functionality
    $('#clearSearch').on('click', function() {
        $('#customSearch').val('');
        customSearch();
    });

    // Clear filter button functionality
    $('#clearFilter').on('click', function() {
        // Reset all form fields
        $('#block').prop('selectedIndex', 0);
        $('#flat_id').html('<option value="">Select Flat</option>');
        $('#from_date').val('');
        $('#to_date').val('');
        
        // Redirect to the same page without any query parameters
        window.location.href = "{{ url('account/maintenance/manage') }}";
    });

    // Payment Modal Functions
    window.setPaymentData = function(paymentId, ownerName, tenantName, flatId) {
        $('#payment_id').val(paymentId);
        $('#flat_id_payment').val(flatId);
        $('#owner_name_display').text(ownerName || 'Not Available');
        $('#tenant_name_display').text(tenantName || 'Not Available');
        
        // Show/hide options based on availability
        if (!ownerName) {
            $('#owner_option').hide();
        } else {
            $('#owner_option').show().text('Owner (' + ownerName + ')');
        }
        
        if (!tenantName) {
            $('#tenant_option').hide();
        } else {
            $('#tenant_option').show().text('Tenant (' + tenantName + ')');
        }
        
        // Reset selection
        $('#payment_person').val('');
    };

    window.proceedPayment = function() {
        var paymentPerson = $('#payment_person').val();
        var flatId = $('#flat_id_payment').val();
        
        if (!paymentPerson) {
            alert('Please select who is making the payment');
            return;
        }
        
        // Redirect to payment page with payment person parameter
        var url = "{{url('account/maintenance/pay')}}/" + flatId + "?payment_person=" + paymentPerson;
        window.open(url, '_blank');
        $('#paymentModal').modal('hide');
    };

  });
</script>
@endsection

@endsection

<script>
// Provide safe, jQuery-free fallbacks so inline onclicks don't fail
if (typeof window.setPaymentData !== 'function') {
  window.setPaymentData = function(paymentId, ownerName, tenantName, flatId) {
    try {
      var el = document.getElementById('payment_id'); if (el) el.value = paymentId || '';
      var f = document.getElementById('flat_id_payment'); if (f) f.value = flatId || '';
      var ownerEl = document.getElementById('owner_name_display'); if (ownerEl) ownerEl.textContent = ownerName || 'Not Available';
      var tenantEl = document.getElementById('tenant_name_display'); if (tenantEl) tenantEl.textContent = tenantName || 'Not Available';

      var ownerOption = document.getElementById('owner_option');
      var tenantOption = document.getElementById('tenant_option');
      if (ownerOption) {
        if (!ownerName) { ownerOption.style.display = 'none'; }
        else { ownerOption.style.display = ''; ownerOption.text = 'Owner (' + ownerName + ')'; }
      }
      if (tenantOption) {
        if (!tenantName) { tenantOption.style.display = 'none'; }
        else { tenantOption.style.display = ''; tenantOption.text = 'Tenant (' + tenantName + ')'; }
      }

      var paymentPerson = document.getElementById('payment_person'); if (paymentPerson) paymentPerson.value = '';
    } catch (e) {
      console && console.error && console.error('setPaymentData fallback error', e);
    }
  };
}

if (typeof window.proceedPayment !== 'function') {
  window.proceedPayment = function() {
    try {
      var paymentPersonEl = document.getElementById('payment_person');
      var paymentPerson = paymentPersonEl ? paymentPersonEl.value : '';
      var flatEl = document.getElementById('flat_id_payment');
      var flatId = flatEl ? flatEl.value : '';
      if (!paymentPerson) { alert('Please select who is making the payment'); return; }
      var url = (function(){
        var base = "{{url('account/maintenance/pay')}}";
        return base + '/' + encodeURIComponent(flatId) + '?payment_person=' + encodeURIComponent(paymentPerson);
      })();
      window.open(url, '_blank');
      // Try to close modal gracefully
      if (window.jQuery && window.jQuery('#paymentModal').modal) {
        window.jQuery('#paymentModal').modal('hide');
      } else {
        var modal = document.getElementById('paymentModal');
        if (modal) { modal.classList.remove('show'); modal.style.display = 'none'; }
        var backdrops = document.getElementsByClassName('modal-backdrop');
        while (backdrops && backdrops.length) { backdrops[0].parentNode.removeChild(backdrops[0]); }
      }
    } catch (e) {
      console && console.error && console.error('proceedPayment fallback error', e);
    }
  };
}
</script>



