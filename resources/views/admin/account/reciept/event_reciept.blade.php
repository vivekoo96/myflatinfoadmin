@extends('layouts.admin')

@section('title')
    Event Receipt
@endsection

@section('content')

<style>
  body {
    font-family: Arial, sans-serif;
    color: #333;
    line-height: 1.4;
  }

  #pdf-content {
    position: relative;
    max-width: 800px;
    margin: auto;
    background: #fff;
    padding: 40px;
  }

  h2 {
    text-align: center;
    margin-bottom: 16px;
    font-size: 20px;
  }

  .section {
    margin-bottom: 12px;
  }

  .section p {
    margin: 4px 0;
    font-size: 14px;
  }

  .section-building {
    text-align: right;
    margin: 4px 0;
    font-size: 16px;
  }

  .section-building p {
    margin: 2px 0;
  }

  .paid-stamp {
    display: block;
    position: absolute;
    top: 180px;
    right: 330px;
    width: 120px;
    opacity: 0.5;
  }

  .app-logo {
    display: block;
    position: absolute;
    top: 30px;
    left: 30px;
    width: 160px;
  }

  .tablecc {
    width: 100%;
    table-layout: fixed;
    font-size: 13px;
    margin-top: 8px;
  }

  .tablecc td {
    padding: 6px 10px;
    vertical-align: top;
  }

  .footer-note {
    font-size: 12px;
    color: #777;
    border-top: 1px solid #ccc;
    padding-top: 8px;
    margin-top: 10px;
    text-align: center;
  }

  @media print {
    body {
      -webkit-print-color-adjust: exact;
    }
    .paid-stamp {
      opacity: 0.5;
    }
  }
</style>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h2>Event Receipt</h2>
      </div>
      <div class="col-sm-6">
        <button class="btn btn-sm btn-info float-right" onclick="downloadPDF()">Download Receipt</button>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
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
    <div id="pdf-content" style="background:#fff;padding:40px;position:relative;">
      <h2>Event Payment Receipt</h2>

      <img src="{{ asset('public/pdfImage/paid-stamp-4.png') }}" class="paid-stamp" alt="PAID">
      <img src="{{ asset('public/pdfImage/Transparent.png') }}" class="app-logo" alt="Logo">

      <div class="section-building">
        <p><strong>{{ $payment->building ? $payment->building->name : 'N/A' }}</strong></p>
        <p style="width:250px; margin-left: auto; text-align: right;">{{ $payment->building ? $payment->building->address : 'N/A' }}</p>
        @if($payment->building && !empty($payment->building->gst_no))
          <p><strong>GST No :-</strong> {{ $payment->building->gst_no }}</p>
        @endif
      </div>

      <div class="section">
        <p>Block No <strong> : {{ $payment->flat ? $payment->flat->block->name : 'N/A' }},</strong></p>
        <p>Flat No <strong> : {{ $payment->flat ? $payment->flat->name : 'N/A' }},</strong></p>
        <p>Dear <strong>{{ $payment->user->name }},</strong></p>
        <p>We acknowledge the receipt of <strong>₹{{ number_format($payment->amount, 2) }}/- </strong> towards the <strong>{{ $payment->event->name }}</strong> event contribution.</p>
        <p>On behalf of <strong>{{ $payment->building->name }}</strong>, we would like to thank you for your contribution.</p>
      </div>

      <table class="tablecc">
        <colgroup>
          <col style="width: 45%;">
          <col style="width: 5%;">
          <col style="width: 50%;">
        </colgroup>
        <tbody>
          <tr>
            <td><strong>Event Name</strong></td>
            <td>:</td>
            <td>{{ $payment->event->name }}</td>
          </tr>
          <tr>
            <td><strong>Transaction Reference Number</strong></td>
            <td>:</td>
            <td>{{ $payment->transaction->reciept_no ?? 'N/A' }}</td>
          </tr>
          <tr>
            <td><strong>Payment Date</strong></td>
            <td>:</td>
            <td>{{ \Carbon\Carbon::parse($payment->date)->format('d-M-Y') }}</td>
          </tr>
          <tr>
            <td colspan="3" style="height: 10px;"></td>
          </tr>

          <tr>
            <td><strong>Total Amount Paid</strong></td>
            <td>:</td>
            <td><strong style="color: #2ecc71;">₹{{ number_format($payment->amount, 2) }} /-</strong></td>
          </tr>

          <tr>
            <td colspan="3" style="height: 10px;"></td>
          </tr>

          @php
            $formatter = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
            $amountInWords = ucfirst($formatter->format($payment->amount)) . ' rupees only';
          @endphp

          <tr>
            <td colspan="3">
              <strong>(In words):</strong> {{ $amountInWords }}/-
            </td>
          </tr>

          <tr>
            <td colspan="3" style="height: 10px;"></td>
          </tr>

          <tr>
            <td><strong>Payment Mode</strong></td>
            <td>:</td>
              @php
                $mode = strtolower(trim($payment->payment_type));
                
            @endphp
          
                      @if($mode == 'inbank')
                <td>In Bank</td>
            @elseif($mode == 'inhand')
                <td>In Cash</td>
            @endif
          </tr>

          <tr>
            <td><strong>Description</strong></td>
            <td>:</td>
            <td>{{$payment->transaction->desc ?? 'Event Contribution' }}</td>
          </tr>

          <tr>
            <td colspan="3" style="height: 10px;"></td>
          </tr>
        </tbody>
      </table>
       
      <div class="section">
           <p>Thank you once again, and we look forward to your continued support.</p>
           <br>
        <p>Thanks & Regards,</p>
       
        @if($payment->building->treasurer_id != null)
        <p>{{ $payment->building->treasurer->name }}</p>
        @if(!empty($payment->building->treasurer->phone))
          <p>Contact: +91 {{ $payment->building->treasurer->phone }}</p>
        @endif
        @else
         <p>{{ $payment->building->user->name }}</p>
        @if(!empty($payment->building->user->phone))
          <p>Contact: +91 {{ $payment->building->user->phone }}</p>
        @endif
        @endif
          <p>{{ $payment->building->name }}</p>
       
      </div>

      <div class="footer-note">
        <p><strong>Payment Status: PAID</strong></p>
        <p>This is a computer-generated receipt. No signature required.</p>
      </div>
    </div>
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
        <h5 class="modal-title" id="exampleModalLabel">Make Payment</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="{{url('account/maintenance/pay-maintenance-bill')}}" method="post" class="add-form">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label for="name" class="col-form-label">Payment Type:</label>
            <select name="payment_type" class="form-control" id="type" required>
              <option value="InHand">InHand</option>
              <option value="InBank">InBank</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="status" class="col-form-label">Status:</label>
            <select name="status" class="form-control" id="status">
              <option value="Paid">Paid</option>
            </select>
          </div>
          
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary" id="save-button">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

  </div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
  async function downloadPDF() {
    const element = document.getElementById('pdf-content');
    
    // Wait for all images to load
    const images = element.getElementsByTagName('img');
    const imagePromises = Array.from(images).map(img => {
      if (img.complete) return Promise.resolve();
      return new Promise((resolve) => {
        img.onload = resolve;
        img.onerror = resolve;
      });
    });
    
    await Promise.all(imagePromises);
    
    // Small delay to ensure images are rendered
    await new Promise(resolve => setTimeout(resolve, 100));
    
    // Generate random filename
    const timestamp = new Date().getTime();
    const randomNum = Math.floor(Math.random() * 10000);
    const filename = `event_receipt_${timestamp}_${randomNum}.pdf`;
    
    const opt = {
      margin: 0.5,
      filename: filename,
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { 
        scale: 2,
        logging: false,
        backgroundColor: '#ffffff'
      },
      jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    
    html2pdf().set(opt).from(element).save();
  }
</script>

@endsection



