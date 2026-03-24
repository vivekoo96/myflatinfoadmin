@extends('layouts.admin')

@section('title')
    Receipt Form
@endsection

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Receipt Form</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Receipt  Form</li>
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
            @if(Auth::User()->role == 'BA' ||  (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts'))
            <div class="card">
                <div class="card-body">
                  <form action="{{route('expense.store')}}" method="post" class="add-form" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                     <div class="error"></div>
                      <div class="form-row">


                        <div class="form-group col-md-6">
                            <label for="name" class="col-form-label">Type:</label>
                            <select name="model" id="model" class="form-control" id="model" required>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Event">Event</option>
                                <option value="Corpus">Corpus</option>
                                <option value="Facility">Booking</option>
                                <option value="Essential">Essential</option>
                            </select>
                        </div>
                        
                    <div class="form-group col-md-6">
                        <div class="model-id"></div>
                      </div>
                      
                        <div class="form-group col-md-6">
                            <label for="name" class="col-form-label">Name:</label>
                            <input type="text" name="ename" id="name" class="form-control" required placeholder="Enter name">
                        </div>
                       
                        <div class="form-group col-md-6">
                            <label for="name" class="col-form-label">Payment Type:</label>
                            <select name="payment_type" id="payment_type" class="form-control" id="payment_type" required>
                                <option value="InHand">In Cash</option>
                                <option value="InBank">In Bank</option>
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label for="name" class="col-form-label">Amount:</label>
                            <input type="number" name="amount" class="form-control" id="amount" placeholder="Amount" min="0" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="code" class="col-form-label">Date:</label>
                            <input type="date" name="date" class="form-control" id="date" value="{{ old('date', now()->toDateString()) }}" max="{{ \Carbon\Carbon::now()->toDateString() }}" placeholder="Date" required>
                        </div>
                                                <div class="form-group col-md-6">
                            <label for="name" class="col-form-label">Reason:</label>
                            <textarea name="reason" id="reason" class="form-control" required></textarea>
                        </div>
                        <input type="hidden" name="type" id="type" value="Credit">
                          <input type="hidden" name="reciptdata" id="reciptdata" value="reciptdata">
                        <input type="hidden" name="id" id="edit-id">
                       </div>
                        <div class="form-row">
                          <div class="form-group col-md-6">
                            <button type="submit" class="btn btn-primary btn-block" id="save-button">Save</button>
                          </div>
                        </div>
                    </div>
                  </form>
                </div>
            </div>
            @endif
            
            <div class="card">
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                     <th>S No</th> 
                    <th>Name</th>
                    <th>Model Type</th>
                    <th>Model Name</th>
                    <th>Receipt Number</th>
                    <th>Reason</th>
                    <th>Paid Amount</th>
                    <th>Paid On</th>
                    <th>Payment Mode</th>
                    <th>Action</th>
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  @forelse($expenses as $expense)
                  <?php $i++; ?>
                 <tr>
                        <td>{{$i}}</td> 
    <td>
        {{ $expense->ename }}
    </td>

    <td>
        {{ $expense->model }}
    </td>

    <td>
        {{ $expense->model_name ?? '-' }}
    </td>
    <td >
        {{ $expense->transaction ? $expense->transaction->reciept_no : 'N/A' }}
    </td>
    <td>
        {{ $expense->reason }}
    </td>
    <td >
        {{ number_format($expense->amount, 2) }}
    </td>
    <td >
        {{ \Carbon\Carbon::parse($expense->date)->format('d-m-Y') }}
    </td>
    <td >
        {{ $expense->payment_type == 'InHand' ? 'In Cash' : 'In Bank' }}
    </td>
    
     <td style="display:none"
        data-order="{{ $expense->created_at->timestamp }}">
        {{ $expense->created_at->timestamp }}
    </td>

    <td style="display:none"
        data-order="{{ $expense->updated_at->timestamp }}">
        {{ $expense->updated_at->timestamp }}
    </td>

                    <td>
                      <button class="btn btn-sm btn-info download-receipt"
                        data-id="{{ $expense->id }}"
                        data-model="{{ e($expense->model) }}"
                        data-model-name="{{ e($expense->model_name) }}"
                        data-receipt-number="{{ $expense->transaction ? e($expense->transaction->reciept_no) : 'N/A' }}"
                        data-reason="{{ e($expense->reason) }}"
                        data-amount="{{ e($expense->amount) }}"
                        data-date="{{ e($expense->date) }}"
                        data-payment-type="{{ $expense->payment_type == 'InHand' ? 'In Cash' : 'In Bank' }}"
                      >Receipt</button>
                    </td>
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




<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>


<script>
  (function receiptsInit(){
    function run() {
      var id = '';
      var action = '';
      var token = "{{csrf_token()}}";
      var model = "{{ request('model') }}";
      var model_id = "{{ request('model_id') }}";

      $(document).on('change','#model',function(){
        var model = $(this).val();
        $('.model-id').html('');
        if(model == 'Event' || model == 'Essential' || model == 'Facility'){
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

      if(model == 'Event' || model == 'Essential' || model == 'Facility'){
          $.ajax({
            url : "{{url('/get-model-data')}}",
            type: "post",
            data : {'_token':token,'model':model,'model_id':model_id},
            success: function(data)
            {
              $('.model-id').html(data);
            }
          });
        }

        $(document).on('submit', '.add-form', function(e) {
          if (!confirm('Are you sure you want to submit this receipt?')) {
            e.preventDefault(); // This will now correctly prevent form submission
          }
        });
    }

    if (window.jQuery) {
      jQuery(run);
    } else {
      document.addEventListener('DOMContentLoaded', run);
    }
  })();
</script>

<script src="{{asset('public/admin/plugins/summernote/summernote-bs4.min.js')}}"></script>

<script>
  $(function () {
    // Summernote
    $('#summernote').summernote()

  })
  
  
 
</script>




<!-- Hidden printable receipt template -->
<div id="receipt-template" style="display:none;">
  <div id="pdf-content" style="font-family: Arial, sans-serif; padding: 40px; color: #333; max-width:800px; background:#fff;">
    <div style="border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px;">
      <img src="{{ Auth::user() && Auth::user()->building ? Auth::user()->building->image : asset('public/pdfImage/Transparent.png') }}" alt="Logo" class="app-logo" style="width:120px; margin-bottom: 10px;" />
      <h2 style="text-align:center; margin: 0; color: #333;">RECEIPT</h2>
      <p style="text-align:center; margin: 5px 0; font-size: 12px; color: #666;">Building: {{ Auth::user() && Auth::user()->building ? Auth::user()->building->name : 'N/A' }}</p>
    </div>
    
    <div style="margin: 20px 0;">
      <table style="width:100%; font-size:14px; border-collapse:collapse; line-height: 1.8;">
        <tr style="border-bottom: 1px solid #ddd;">
          <td style="padding: 10px; width: 40%; font-weight: bold;">Receipt No:</td>
          <td style="padding: 10px;" id="rt-receipt-number">-</td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
          <td style="padding: 10px; font-weight: bold;">Type:</td>
          <td style="padding: 10px;" id="rt-model">-</td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
          <td style="padding: 10px; font-weight: bold;">Model/Reference:</td>
          <td style="padding: 10px;" id="rt-model-name">-</td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
          <td style="padding: 10px; font-weight: bold;">Amount:</td>
          <td style="padding: 10px; font-weight: bold; color: #28a745;" id="rt-amount">-</td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
          <td style="padding: 10px; font-weight: bold;">Payment Mode:</td>
          <td style="padding: 10px;" id="rt-payment-type">-</td>
        </tr>
        <tr style="border-bottom: 1px solid #ddd;">
          <td style="padding: 10px; font-weight: bold;">Date:</td>
          <td style="padding: 10px;" id="rt-date">-</td>
        </tr>
        <tr>
          <td style="padding: 10px; font-weight: bold; vertical-align: top;">Reason:</td>
          <td style="padding: 10px; white-space: pre-wrap; word-wrap: break-word;" id="rt-reason">-</td>
        </tr>
      </table>
    </div>

    <div style="margin-top: 40px; border-top: 1px solid #ddd; padding-top: 20px;">
      <p style="margin: 0; font-weight: bold;">Authorized By:</p>
      <p style="margin: 5px 0; font-size: 12px;">{{ Auth::user() ? Auth::user()->first_name . ' ' . Auth::user()->last_name : '' }}</p>
      <p style="margin: 20px 0 0 0; font-size: 11px; color: #666;">Issued on: <span id="rt-issued-date"></span></p>
    </div>
  </div>
</div>

<!-- jsPDF and html2canvas for PDF generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
  $(document).on('click', '.download-receipt', function(){
    console.log('Download receipt clicked');
    
    var btn = $(this);
    var model = btn.data('model') || '';
    var modelName = btn.data('model-name') || '';
    var receiptNo = btn.data('receipt-number') || '';
    var reason = btn.data('reason') || '';
    var amount = btn.data('amount') || '';
    var date = btn.data('date') || '';
    var paymentType = btn.data('payment-type') || '';

    console.log('Receipt Data:', {model, modelName, receiptNo, reason, amount, date, paymentType});

    // Populate template
    var tplHtml = $('#receipt-template').html();
    var $temp = $('<div></div>').html(tplHtml).css({
      position: 'fixed',
      left: '-10000px',
      top: '0',
      width: '800px',
      display: 'block',
      opacity: '1',
      pointerEvents: 'none'
    }).appendTo('body');

    $temp.find('#rt-model').text(model || 'N/A');
    $temp.find('#rt-model-name').text(modelName || 'N/A');
    $temp.find('#rt-receipt-number').text(receiptNo || 'N/A');
    $temp.find('#rt-reason').text(reason || 'N/A');
    $temp.find('#rt-amount').text('₹ ' + (parseFloat(amount) || 0).toFixed(2));
    $temp.find('#rt-date').text(date || 'N/A');
    $temp.find('#rt-payment-type').text(paymentType || 'N/A');
    $temp.find('#rt-issued-date').text(new Date().toLocaleDateString('en-IN'));

    // Get the PDF content div
    var pdfElement = $temp.find('#pdf-content').get(0);

    // Use html2canvas to convert DOM to canvas, then jsPDF to convert to PDF
    html2canvas(pdfElement, {
      scale: 2,
      useCORS: true,
      allowTaint: true,
      backgroundColor: '#ffffff',
      logging: false
    })
    .then(function(canvas) {
      console.log('Canvas created successfully');
      
      // Create PDF from canvas
      var { jsPDF } = window.jspdf;
      var imgData = canvas.toDataURL('image/png');
      var pdf = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a4'
      });

      var pageWidth = pdf.internal.pageSize.getWidth();
      var pageHeight = pdf.internal.pageSize.getHeight();
      var imgWidth = pageWidth - 20;
      var imgHeight = (canvas.height * imgWidth) / canvas.width;

      var yPosition = 10;
      pdf.addImage(imgData, 'PNG', 10, yPosition, imgWidth, imgHeight);

      // Save PDF
      var filename = 'receipt_' + (receiptNo.toString().replace(/\s/g, '_') || Date.now()) + '.pdf';
      pdf.save(filename);
      
      console.log('PDF downloaded:', filename);
      $temp.remove();
    })
    .catch(function(error) {
      console.error('Error generating PDF:', error);
      alert('Error generating PDF. Please try again.');
      $temp.remove();
    });
  });
</script>
@endsection
