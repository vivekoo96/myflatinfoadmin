

<?php $__env->startSection('title'); ?>
    Essential Invoice
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

  @media  print {
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
      <?php if(session()->has('error')): ?>
      <div class="alert alert-danger">
          <?php echo e(session()->get('error')); ?>

      </div>
      <?php endif; ?>
      <?php if(session()->has('success')): ?>
      <div class="alert alert-success">
          <?php echo e(session()->get('success')); ?>

      </div>
      <?php endif; ?>
    </div>
            
     <?php if(Auth::User()->role == 'BA' || (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts')): ?>
      <?php if($payment->status != 'Paid'): ?>
        <div class="text-center mb-3">
          <button class="btn btn-success" data-toggle="modal" data-target="#addModal">
            <i class="fas fa-credit-card"></i> Pay Now
          </button>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <div id="pdf-content" style="background:#fff;padding:40px;position:relative;">
          <?php if($payment->status == 'Paid'): ?>
            <img src="<?php echo e(asset('public/pdfImage/paid-stamp-4.png')); ?>" alt="PAID" class="paid-stamp" />
        <?php endif; ?>
      <h2 style="">Essential Invoice</h2>

      <img src="<?php echo e(asset('public/pdfImage/Transparent.png')); ?>" class="app-logo" alt="Logo">

      <div class="section-building">
        <p><strong><?php echo e($flat->building->name); ?></strong></p>
        <p style="width:250px; margin-left: auto; text-align: right;"><?php echo e($flat->building->address); ?></p>
        <?php if(!empty($flat->building->gst_no)): ?>
          <p><strong>GST No :-</strong> <?php echo e($flat->building->gst_no); ?></p>
        <?php endif; ?>
      </div>

         <!-- Essential Details -->
            <?php
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
            ?>

      <div class="section">
        <p>Block No <strong> : <?php echo e($flat->block->name); ?>,</strong></p>
        <p>Flat No <strong> : <?php echo e($flat->name); ?>,</strong></p>
        <p>Dear <strong><?php echo e($user->name); ?>,</strong></p>
        <p>As discussed in our recent meeting regarding the <strong><?php echo e($payment->essential->reason); ?></strong>,  it has been resolved to collect <strong>₹ <?php echo e(number_format($display_amount, 2)); ?>/- </strong> from each flat to ensure equal contribution from all members.</p>
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
            <td><?php echo e(\Carbon\Carbon::parse($payment->created_at)->format('d-M-Y h:i A')); ?></td>
          </tr>
          <tr>
            <td><strong>Bill Number</strong></td>
            <td>:</td>
            <td><?php echo e($payment->bill_no); ?></td>
          </tr>
          <tr>
            <td><strong>Bill Due Date</strong></td>
            <td>:</td>
            <td><?php echo e(\Carbon\Carbon::parse($payment->essential->due_date)->format('d-M-Y')); ?></td>
          </tr>
          <tr>
            <td colspan="3" style="height: 10px;"></td>
          </tr>

         
          <tr>
            <td><strong>Essential Amount</strong></td>
            <td>:</td>
            <td>₹ <?php echo e(number_format($display_amount, 2)); ?> /-</td>
          </tr>
          <tr>
            <td><strong>Late Fine</strong></td>
            <td>:</td>
            <td>₹ <?php echo e(number_format($display_late, 2)); ?> /-</td>
          </tr>
          <?php if($display_gst > 0): ?>
          <tr>
            <td><strong>GST (if applicable)</strong></td>
            <td>:</td>
            <td>₹ <?php echo e(number_format($display_gst, 2)); ?> /-</td>
          </tr>
          <?php endif; ?>

          <tr>
            <td colspan="3" style="height: 10px;"></td>
          </tr>

          <?php
            $roundedTotal = ceil($display_grand);
            $roundingAdjustment = $roundedTotal - $display_grand;
          ?>

         
          <tr>
            <td><strong>Rounding Adjustment</strong></td>
            <td>:</td>
            <td>₹ <?php echo e(number_format($roundingAdjustment, 2)); ?> /-</td>
          </tr>
        

          <tr>
            <td><strong>Total Payable Amount</strong></td>
            <td>:</td>
            <td><strong style="color: #e74c3c;">₹ <?php echo e(number_format($roundedTotal, 2)); ?> /-</strong></td>
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
        </tbody>
      </table>

      <div class="section">
        <p>You can pay online with our secured payment gateway by clicking on the button "Pay now" in myflatinfo.</p>
        <p>If you wish to pay in cash, please contact the Accounts team of the Management Committee.</p>
      </div>

      <div class="section">
        <p>Thanks & Regards,</p>
       
        <?php if($flat->building->treasurer_id != null): ?>
        <p><?php echo e($flat->building->treasurer->name); ?></p>
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
      <form action="<?php echo e(url('store-essential-payment')); ?>" method="post" class="add-form">
        <?php echo csrf_field(); ?>
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
          <input type="hidden" name="essential_payment_id" id="essential_payment_id" value="<?php echo e($payment->id); ?>">
          <input type="hidden" name="amount" id="amount" value="<?php echo e($grand_total); ?>">
          
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

<?php $__env->stopSection(); ?>




<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/account/invoice/essential_invoice.blade.php ENDPATH**/ ?>