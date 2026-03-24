<?php $__env->startSection('title'); ?>
    Maintenance Receipt
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

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

  @media  print {
    body {
      -webkit-print-color-adjust: exact;
    }
    .paid-stamp {
      opacity: 0.5;
    }
  }
</style>

<?php
function indian_money($amount, $decimals = 2) {
    $fmt = new \NumberFormatter('en_IN', \NumberFormatter::DECIMAL);
    $fmt->setAttribute(\NumberFormatter::FRACTION_DIGITS, $decimals);
    return $fmt->format($amount);
}
?>
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h2>Maintenance Receipt</h2>
      </div>
      <div class="col-sm-6">
        <button class="btn btn-sm btn-info float-right" onclick="downloadPDF()">Download Receipt</button>
      </div>
    </div>
  </div>
</section>

<section class="content">
  <div class="container-fluid">
    <?php if(isset($maintenance_payments[0])): ?>
      <?php $current_payment = $maintenance_payments[0]; ?>

      <div id="pdf-content" style="background:#fff;padding:40px;position:relative;">
        <h2>Maintenance Payment Receipt</h2>

        <img src="<?php echo e(asset('public/pdfImage/paid-stamp-4.png')); ?>" class="paid-stamp" alt="PAID">
        <img src="<?php echo e(asset('public/pdfImage/Transparent.png')); ?>" class="app-logo" alt="Logo">

        <div class="section-building">
          <p><strong><?php echo e($flat->building->name); ?></strong></p>
          <p style="width:250px; margin-left: auto; text-align: right;"><?php echo e($flat->building->address); ?></p>
        
          <?php if(!empty($flat->building->gst_no)): ?>
            <p><strong>GST No :-</strong> <?php echo e($flat->building->gst_no); ?></p>
          <?php endif; ?>
        </div>

        <div class="section">
          <p>Block No <strong> : <?php echo e($flat->block->name); ?>,</strong></p>
          <p>Flat No <strong> : <?php echo e($flat->name); ?>,</strong></p>
          <p>Dear <strong><?php echo e($user->name); ?>,</strong></p>
          <p>We acknowledge the receipt of <strong>₹ <?php echo e(indian_money(ceil($grand_total), 2)); ?>/- </strong> towards the Maintenance bill for the month of <strong><?php echo e(\Carbon\Carbon::parse($current_payment->maintenance->to_date)->format('F Y')); ?></strong>.</p>
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
              <td><?php echo e(\Carbon\Carbon::parse($current_payment->created_at)->format('d-M-Y h:i A')); ?></td>
            </tr>
            <tr>
              <td><strong>Bill number</strong></td>
              <td>:</td>
              <td><?php echo e($current_payment->bill_no); ?></td>
            </tr>
            <tr>
              <td><strong>Transaction Reference Number</strong></td>
              <td>:</td>
              <td><?php echo e($current_payment->transaction->reciept_no ?? 'N/A'); ?></td>
            </tr>
            <tr>
              <td><strong>Payment date</strong></td>
              <td>:</td>
              <td><?php echo e(\Carbon\Carbon::parse($current_payment->paid_date)->format('d-M-Y')); ?></td>
            </tr>
            <tr>
              <td colspan="3" style="height: 10px;"></td>
            </tr>

            <!-- Current Month Details -->
            <tr>
              <td><strong>Current Month Maintenance</strong></td>
              <td>:</td>
              <td>₹<?php echo e(indian_money($current_payment->paid_amount, 2)); ?> /-</td>
            </tr>
            <tr>
              <td><strong>Current Month late fine</strong></td>
              <td>:</td>
              <td>₹<?php echo e(indian_money($current_payment->late_fine, 2)); ?> /-</td>
            </tr>
            <?php if($current_payment->maintenance->gst > 0): ?>
            <tr>
              <td><strong>Current Month GST @ <?php echo e($current_payment->maintenance->gst); ?>%</strong></td>
              <td>:</td>
              <td>₹<?php echo e(indian_money($current_payment->gst, 2)); ?> /-</td>
            </tr>
            <?php endif; ?>
            <tr>
              <td><strong>Current Month Total Maintenance</strong></td>
              <td>:</td>
              <td><strong>₹<?php echo e(indian_money($current_payment->paid_amount + $current_payment->late_fine + $current_payment->gst, 2)); ?> /-</strong></td>
            </tr>

            <tr>
              <td colspan="3" style="height: 10px;"></td>
            </tr>

            <!-- Arrears Details -->
            <?php
              $arrears = $grand_total - ($current_payment->paid_amount + $current_payment->late_fine + $current_payment->gst);
            ?>
            <tr>
              <td style="vertical-align: top;">
                <strong>Arrears as on <?php echo e(\Carbon\Carbon::parse($current_payment->created_at)->format('d-M-Y h:i A')); ?></strong>
              </td>
              <td style="vertical-align: top;">:</td>
              <td style="vertical-align: top;"><strong>₹<?php echo e(indian_money($arrears, 2)); ?> /-</strong></td>
            </tr>

            <tr>
              <td colspan="3" style="height: 10px;"></td>
            </tr>

            <!-- Total Calculation -->
            <tr>
              <td><strong>Total Maintenance Amount</strong></td>
              <td>:</td>
              <td><strong>₹ <?php echo e(indian_money($grand_total, 2)); ?> /-</strong></td>
            </tr>

            <?php
              $roundedTotal = ceil($grand_total);
              $roundingAdjustment = $roundedTotal - $grand_total;
            ?>

            <?php if($roundingAdjustment > 0): ?>
            <tr>
              <td><strong>Rounding Adjustment</strong></td>
              <td>:</td>
              <td>₹ <?php echo e(indian_money($roundingAdjustment, 2)); ?> /-</td>
            </tr>
            <?php endif; ?>

            <tr>
              <td><strong>Total Amount Paid</strong></td>
              <td>:</td>
              <td><strong style="color: #2ecc71;">₹ <?php echo e(indian_money($roundedTotal, 2)); ?> /-</strong></td>
            </tr>

            <tr>
              <td colspan="3" style="height: 10px;"></td>
            </tr>

            <?php
              $formatter = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
              $amountInWords = ucfirst($formatter->format($roundedTotal)) . ' rupees only';
            ?>

            <tr>
              <td colspan="3">
                <strong>(In words):</strong> <?php echo e($amountInWords); ?>/-
              </td>
            </tr>

            <tr>
              <td colspan="3" style="height: 10px;"></td>
            </tr>

            <tr>
              <td><strong>Payment mode</strong></td>
              <td>:</td>
                 <?php
                $mode = strtolower(trim($current_payment->payment_type));
                
            ?>
          
                      <?php if($mode == 'inbank'): ?>
                <td>In Bank</td>
            <?php elseif($mode == 'inhand'): ?>
                <td>In Cash</td>
            <?php endif; ?>
             
            </tr>

            <tr>
              <td><strong>Description</strong></td>
              <td>:</td>
              <td><?php echo e($current_payment->desc); ?></td>
            </tr>

            <tr>
              <td colspan="3" style="height: 10px;"></td>
            </tr>
          </tbody>
        </table>

        <div class="section">
          <p>Thanks & Regards,</p>
         
          <?php if($flat->building->treasurer_id != null): ?>
          <p><?php echo e($flat->building->treasurer_type); ?> <?php echo e($flat->building->treasurer->name); ?></p>
          <?php if(!empty($flat->building->treasurer->phone)): ?>
            <p>Contact: +91 <?php echo e($flat->building->treasurer->phone); ?></p>
          <?php endif; ?>
          <?php else: ?>
           <p><?php echo e($flat->building->user->name); ?></p>
          <?php if(!empty($flat->building->user->phone)): ?>
            <p>Contact: +91 <?php echo e($flat->building->user->phone); ?></p>
          <?php endif; ?>
          <?php endif; ?>
            <p><?php echo e($flat->building->name); ?></p>
         
        </div>

        <div class="footer-note">
          <p><strong>Payment Status: PAID</strong></p>
          <p>This is a computer-generated receipt. No signature required.</p>
        </div>
      </div>
    <?php else: ?>
      <div class="card">
        <div class="card-body">
          <center><h4>No Maintenance Payment Found</h4></center>
        </div>
      </div>
    <?php endif; ?>
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

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/account/maintenance/reciept_maintenance.blade.php ENDPATH**/ ?>