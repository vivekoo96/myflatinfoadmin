@extends('layouts.nosidebar')

@section('title')
    Maintenance Receipt
@endsection

@section('content')

<style>
  body {
    font-family: 'DejaVu Sans', Arial, sans-serif !important;
    color: #333;
    line-height: 1.3;
    font-size: 12px;
  }

  #pdf-content {
    position: relative;
    max-width: 800px;
    margin: auto;
    padding: 15px 25px !important;
  }

  h2 {
    text-align: center;
    margin-bottom: 8px;
    font-size: 17px;
  }

  .section {
    margin-bottom: 8px;
  }

  .section p {
    margin: 2px 0;
    font-size: 12px;
    line-height: 1.3;
  }

  .section-building {
    text-align: right;
    margin: 2px 0;
    font-size: 12px;
  }

  .section-building p {
    margin: 1px 0;
    line-height: 1.2;
  }

  .paid-stamp {
    display: block;
    position: absolute;
    top: 120px;
    right: 275px;
    width: 90px;
    opacity: 0.3;
  }

  .app-logo {
    display: block;
    position: absolute;
    top: 15px;
    left: 15px;
    width: 100px;
  }

  .tablecc {
    width: 100%;
    table-layout: fixed;
    font-size: 12px;
    margin-top: 6px;
  }

  .tablecc td {
    padding: 3px 0;
    vertical-align: top;
    line-height: 1.2;
  }

  .footer-note {
    font-size: 10px;
    color: #777;
    border-top: 1px solid #ccc;
    padding-top: 6px;
    margin-top: 8px;
    text-align: center;
  }

  .footer-note p {
    margin: 1px 0;
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



<section class="content">
  <div class="container-fluid">
    @if(isset($maintenance_payments[0]))
      @php $current_payment = $maintenance_payments[0]; @endphp

      <div id="pdf-content" style="background:#fff;position:relative;">
        <h2>Maintenance Payment Receipt</h2>

        <img src="{{ asset('public/pdfImage/paid-stamp-4.png') }}" class="paid-stamp" alt="PAID">
        <img src="{{ asset('public/pdfImage/Transparent.png') }}" class="app-logo" alt="Logo">

        <div class="section-building">
          <p><strong>{{ $flat->building->name }}</strong></p>
          <p style="width:250px; margin-left: auto; text-align: right;">{{ $flat->building->address }}</p>
          @if(!empty($flat->building->city))
            <p>{{ $flat->building->city->name }}</p>
          @endif
          @if(!empty($flat->building->gst_no))
            <p><strong>GST No :-</strong> {{ $flat->building->gst_no }}</p>
          @endif
        </div>

        <div class="section">
          <p>Block No <strong> : {{ $flat->block->name }},</strong></p>
          <p>Flat No <strong> : {{ $flat->name }},</strong></p>
          <p>Dear <strong>{{ $user->name }},</strong></p>
          <p>We acknowledge the receipt of <strong>₹{{ number_format(ceil($grand_total), 2) }}/- </strong> towards the Maintenance bill for the month of <strong>{{ \Carbon\Carbon::parse($current_payment->maintenance->from_date)->format('F Y') }}</strong>.</p>
        </div>

        <table class="tablecc">
          <colgroup>
            <col style="width: 45%;">
            <col style="width: 5%;">
            <col style="width: 50%;">
          </colgroup>
          <tbody>
            <tr>
              <td><strong>Bill generated on</strong></td>
              <td>:</td>
              <td>{{ \Carbon\Carbon::parse($current_payment->created_at)->format('d-M-Y h:i A') }}</td>
            </tr>
            <tr>
              <td><strong>Bill number</strong></td>
              <td>:</td>
              <td>{{ $current_payment->bill_no }}</td>
            </tr>
            <tr>
              <td><strong>Transaction Reference Number</strong></td>
              <td>:</td>
              <td>{{ $current_payment->transaction->reciept_no ?? 'N/A' }}</td>
            </tr>
            <tr>
              <td><strong>Payment date</strong></td>
              <td>:</td>
              <td>{{ \Carbon\Carbon::parse($current_payment->paid_date)->format('d-M-Y') }}</td>
            </tr>
            <tr>
              <td colspan="3" style="height: 5px;"></td>
            </tr>

            <!-- Current Month Details -->
            <tr>
              <td><strong>Current Month Maintenance</strong></td>
              <td>:</td>
              <td>₹{{ number_format($current_payment->paid_amount, 2) }} /-</td>
            </tr>
            <tr>
              <td><strong>Current Month late fine</strong></td>
              <td>:</td>
              <td>₹{{ number_format($current_payment->late_fine, 2) }} /-</td>
            </tr>
            @if($current_payment->maintenance->gst > 0)
            <tr>
              <td><strong>Current Month GST @ {{ $current_payment->maintenance->gst }}%</strong></td>
              <td>:</td>
              <td>₹{{ number_format($current_payment->gst, 2) }} /-</td>
            </tr>
            @endif
            <tr>
              <td><strong>Current Month Total Maintenance</strong></td>
              <td>:</td>
              <td><strong>₹{{ number_format($current_payment->paid_amount + $current_payment->late_fine + $current_payment->gst, 2) }} /-</strong></td>
            </tr>

            <tr>
              <td colspan="3" style="height: 5px;"></td>
            </tr>

            <!-- Arrears Details -->
            @php
              $arrears = $grand_total - ($current_payment->paid_amount + $current_payment->late_fine + $current_payment->gst);
            @endphp
            <tr>
              <td style="vertical-align: top;">
                <strong>Arrears as on {{ \Carbon\Carbon::parse($current_payment->created_at)->format('d-M-Y h:i A') }}</strong>
              </td>
              <td style="vertical-align: top;">:</td>
              <td style="vertical-align: top;"><strong>₹{{ number_format($arrears, 2) }} /-</strong></td>
            </tr>

            <tr>
              <td colspan="3" style="height: 5px;"></td>
            </tr>

            <!-- Total Calculation -->
            <tr>
              <td><strong>Total Maintenance Amount</strong></td>
              <td>:</td>
              <td><strong>₹{{ number_format($grand_total, 2) }} /-</strong></td>
            </tr>

            @php
              $roundedTotal = ceil($grand_total);
              $roundingAdjustment = $roundedTotal - $grand_total;
            @endphp

            @if($roundingAdjustment > 0)
            <tr>
              <td><strong>Rounding Adjustment</strong></td>
              <td>:</td>
              <td>₹ {{ number_format($roundingAdjustment, 2) }} /-</td>
            </tr>
            @endif

            <tr>
              <td><strong>Total Amount Paid</strong></td>
              <td>:</td>
              <td><strong style="color: #2ecc71;">₹{{ number_format($roundedTotal, 2) }} /-</strong></td>
            </tr>

            <tr>
              <td colspan="3" style="height: 5px;"></td>
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
              <td colspan="3" style="height: 5px;"></td>
            </tr>

            <tr>
              <td><strong>Payment mode</strong></td>
              <td>:</td>
              <td>{{ $current_payment->payment_type }}</td>
            </tr>

            <tr>
              <td><strong>Description</strong></td>
              <td>:</td>
              <td>{{ $current_payment->desc }}</td>
            </tr>

            <tr>
              <td colspan="3" style="height: 5px;"></td>
            </tr>
          </tbody>
        </table>

        <div class="section" style="margin-top: 10px;">
          <p>Thanks & Regards,</p>
          @if($flat->building->treasurer_id != null)
          <p>{{ $flat->building->treasurer_type }} {{ $flat->building->treasurer->name }}</p>
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
          <p><strong>Payment Status: PAID</strong></p>
          <p>This is a computer-generated receipt. No signature required.</p>
        </div>
      </div>
    @else
      <div class="card">
        <div class="card-body">
          <center><h4>No Maintenance Payment Found</h4></center>
        </div>
      </div>
    @endif
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
    const filename = `maintenance_receipt_${timestamp}_${randomNum}.pdf`;
    
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
