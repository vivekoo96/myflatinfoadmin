@extends('layouts.admin')

@section('title')
    Essential Invoice
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
    top: 100px;
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
        <h2>Essential Invoice</h2>
      </div>
      <div class="col-sm-6">
        <button class="btn btn-sm btn-info float-right" onclick="downloadPDF()">Download Invoice</button>
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
            
     @if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts'))
      @if($payment->status != 'Paid')
        <div class="text-center mb-3">
          <button class="btn btn-success" data-toggle="modal" data-target="#addModal">
            <i class="fas fa-credit-card"></i> Pay Now
          </button>
        </div>
      @endif
    @endif

    <div id="pdf-content" style="background:#fff;padding:40px;position:relative;">
          @if($payment->status == 'Paid')
            <img src="{{ asset('public/pdfImage/paid-stamp-4.png') }}" alt="PAID" class="paid-stamp" />
        @endif
      <h2 style="">Essential Invoice</h2>

      <img src="{{ asset('public/pdfImage/Transparent.png') }}" class="app-logo" alt="Logo">

      <div class="section-building">
        <p><strong>{{ $flat->building->name }}</strong></p>
        <p style="width:250px; margin-left: auto; text-align: right;">{{ $flat->building->address }}</p>
        @if(!empty($flat->building->gst_no))
          <p><strong>GST No :-</strong> {{ $flat->building->gst_no }}</p>
        @endif
      </div>

         <!-- Essential Details -->
            @php
            // Prepare display values: when payment is Paid the stored dues_amount may be 0,
            // so recover the original amounts from payment->paid_amount and essential data.
            $display_amount = $payment->dues_amount;
            $display_late = $late_fine;
            $display_gst = $total_gst;
            $display_grand = $grand_total;

            if ($payment->status === 'Paid') {
              $base_amount = $payment->essential->amount ?? 0;
              $paid_amount = $payment->paid_amount ?? 0;
              $gst_rate = $payment->essential->gst ?? 0;

              if ($paid_amount > 0) {
                // paid_amount = (base_amount + late_fine) * (1 + gst/100)
                $multiplier = 1 + ($gst_rate / 100);
                $base_with_late = $multiplier > 0 ? ($paid_amount / $multiplier) : ($paid_amount);
                $calculated_late = $base_with_late - $base_amount;
                if ($calculated_late < 0) $calculated_late = 0;

                $display_amount = $base_amount;
                $display_late = $calculated_late;
                $display_gst = $paid_amount - $base_with_late; // GST portion
                $display_grand = $paid_amount;
              } else {
                // fallback to essential amount
                $display_amount = $payment->essential->amount ?? 0;
                $display_late = $late_fine ?? 0;
                $display_gst = $total_gst ?? 0;
                $display_grand = $grand_total ?? ($payment->essential->amount ?? 0);
              }
            }
            @endphp

      <div class="section">
        <p>Block No <strong> : {{ $flat->block->name }},</strong></p>
        <p>Flat No <strong> : {{ $flat->name }},</strong></p>
        <p>Dear <strong>{{ $user->name }},</strong></p>
        <p>As discussed in our recent meeting regarding the <strong>{{ $payment->essential->reason }}</strong>,  it has been resolved to collect <strong>₹ {{ number_format($display_amount, 2) }}/- </strong> from each flat to ensure equal contribution from all members.</p>
        <p>Please note that a late payment charge will be levied if the amount is not paid within the due date.</p>
      </div>

      <table class="tablecc">
        <colgroup>
          <col style="width: 45%;">
          <col style="width: 5%;">
          <col style="width: 50%;">
        </colgroup>
        <tbody>
          <tr>
            <td><strong>Bill Generated On</strong></td>
            <td>:</td>
            <td>{{ \Carbon\Carbon::parse($payment->created_at)->format('d-M-Y h:i A') }}</td>
          </tr>
          <tr>
            <td><strong>Bill Number</strong></td>
            <td>:</td>
            <td>{{ $payment->bill_no }}</td>
          </tr>
          <tr>
            <td><strong>Bill Due Date</strong></td>
            <td>:</td>
            <td>{{ \Carbon\Carbon::parse($payment->essential->due_date)->format('d-M-Y') }}</td>
          </tr>
          <tr>
            <td colspan="3" style="height: 10px;"></td>
          </tr>

         
          <tr>
            <td><strong>Essential Amount</strong></td>
            <td>:</td>
            <td>₹ {{ number_format($display_amount, 2) }} /-</td>
          </tr>
          <tr>
            <td><strong>Late Fine</strong></td>
            <td>:</td>
            <td>₹ {{ number_format($display_late, 2) }} /-</td>
          </tr>
          @if($display_gst > 0)
          <tr>
            <td><strong>GST (if applicable)</strong></td>
            <td>:</td>
            <td>₹ {{ number_format($display_gst, 2) }} /-</td>
          </tr>
          @endif

          <tr>
            <td colspan="3" style="height: 10px;"></td>
          </tr>

          @php
            $roundedTotal = ceil($display_grand);
            $roundingAdjustment = $roundedTotal - $display_grand;
          @endphp

         
          <tr>
            <td><strong>Rounding Adjustment</strong></td>
            <td>:</td>
            <td>₹ {{ number_format($roundingAdjustment, 2) }} /-</td>
          </tr>
        

          <tr>
            <td><strong>Total Payable Amount</strong></td>
            <td>:</td>
            <td><strong style="color: #e74c3c;">₹ {{ number_format($roundedTotal, 2) }} /-</strong></td>
          </tr>

          <tr>
            <td colspan="3" style="height: 10px;"></td>
          </tr>

          @php
            $formatter = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
            $amountInWords = ucfirst($formatter->format($roundedTotal)) . ' rupees only';
          @endphp

          <tr>
            <td colspan="3">
              <strong>(In words):</strong> {{ $amountInWords }}/-
            </td>
          </tr>

          <tr>
            <td colspan="3" style="height: 10px;"></td>
          </tr>
        </tbody>
      </table>

      <div class="section">
        <p>You can pay online with our secured payment gateway by clicking on the button "Pay now" in myflatinfo.</p>
        <p>If you wish to pay in cash, please contact the Accounts team of the Management Committee.</p>
      </div>

      <div class="section">
        <p>Thanks & Regards,</p>
       
        @if($flat->building->treasurer_id != null)
        <p>{{ $flat->building->treasurer->name }}</p>
        @if(!empty($flat->building->treasurer->phone))
          <p>Contact: +91 {{ $flat->building->treasurer->phone }}</p>
        @endif
        @else
         <p>{{ $flat->building->user->name }}</p>
        @if(!empty($flat->building->user->phone))
          <p>Contact: +91 {{ $flat->building->user->phone }}</p>
        @endif
        @endif
          <p>{{ $flat->building->name }}</p>
       
      </div>

      <div class="footer-note">
        <p><strong>Note:</strong> Please pay the essential bill before due date to avoid late payment charges.</p>
        <p>This is a computer-generated invoice. No signature required.</p>
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
      <form action="{{url('store-essential-payment')}}" method="post" class="add-form">
        @csrf
        <div class="modal-body">
          <div class="form-group">
            <label for="name" class="col-form-label">Payment Type:</label>
            <select name="payment_type" class="form-control" id="type" required>
              <option value="InHand">InCash</option>
              <option value="InBank">InBank</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="status" class="col-form-label">Status:</label>
            <select name="status" class="form-control" id="status">
              <option value="Paid">Paid</option>
            </select>
          </div>
          <input type="hidden" name="essential_payment_id" id="essential_payment_id" value="{{$payment->id}}">
          <input type="hidden" name="amount" id="amount" value="{{$grand_total}}">
          
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
    const filename = `essential_invoice_${timestamp}_${randomNum}.pdf`;
    
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



