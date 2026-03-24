<?php $__env->startSection('title'); ?>
    Invoice Essential
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<style>
    body {
        font-family: 'DejaVu Sans', Arial, sans-serif !important;
        color: #333;
        line-height: 1.4;
        font-size: 13px;
    }
    
    #pdf-content {
        position: relative;
        max-width: 800px;
        margin: auto;
        background: #fff;
        padding: 20px 30px;
    }
    
    .paid-stamp {
        display: block;
        position: absolute;
        top: 80px;
        right: 40px;
        width: 100px;
        opacity: 0.3;
        z-index: 0;
    }
    
    .app-logo {
        width: 100px;
        height: auto;
    }
    
    .invoice-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }
    
    .invoice-title {
        text-align: center;
        flex: 1;
        font-size: 18px;
        font-weight: bold;
        margin: 0;
    }
    
    .building-info {
        text-align: right;
        font-size: 13px;
    }
    
    .building-info p {
        margin: 1px 0;
        line-height: 1.3;
    }
    
    .invoice-details {
        margin-top: 10px;
        font-size: 13px;
    }
    
    .invoice-details p {
        margin: 2px 0;
        line-height: 1.4;
    }
    
    .bill-table {
        width: 100%;
        margin-top: 12px;
        font-size: 13px;
    }
    
    .bill-table td {
        padding: 4px 0;
        vertical-align: top;
        line-height: 1.3;
    }
    
    .bill-table td:first-child {
        width: 40%;
    }
    
    .bill-table td:nth-child(2) {
        width: 5%;
        text-align: center;
    }
    
    .bill-table td:last-child {
        width: 55%;
    }
    
    .total-row {
        color: #e74c3c;
        font-weight: bold;
    }
    
    .footer-section {
        margin-top: 15px;
        font-size: 13px;
    }
    
    .footer-section p {
        margin: 2px 0;
        line-height: 1.4;
    }
    
    .footer-note {
        margin-top: 15px;
        padding-top: 10px;
        border-top: 1px solid #ddd;
        text-align: center;
        font-size: 11px;
        color: #777;
    }
    
    .footer-note p {
        margin: 2px 0;
    }
    
    @media  print {
        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
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
            
            <div class="card">
              <div class="card-body" id="pdf-content">
                    <?php if($payment->status == 'Paid'): ?>
                        <img src="<?php echo e(asset('public/pdfImage/paid-stamp-4.png')); ?>" alt="PAID" class="paid-stamp" />
                    <?php endif; ?>
                    
                    <!-- Header with Logo, Title, and Building Info -->
                    <div class="invoice-header">
                        <div>
                            <img src="<?php echo e(asset('public/pdfImage/Transparent.png')); ?>" alt="Logo" class="app-logo">
                        </div>
                        <div class="invoice-title">
                            Essential Invoice
                        </div>
                        <div class="building-info">
                            <p><strong><?php echo e($flat->building->name); ?></strong></p>
                            <p><?php echo e($flat->building->address); ?></p>
                            <?php if(!empty($flat->building->gst_no)): ?>
                                <p><strong>GST No :-</strong> <?php echo e($flat->building->gst_no); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Invoice Details -->
                    <div class="invoice-details">
                        <p>Block No :<strong><?php echo e($flat->block->name); ?>,</strong></p>
                        <p>Flat No :<strong><?php echo e($flat->name); ?>,</strong></p>
                        <p>Dear <strong><?php echo e($user->name); ?>,</strong></p>
                        <p>As discussed in our recent meeting regarding the <strong><?php echo e($payment->essential->reason); ?></strong>, it has been resolved to collect <strong>₹<?php echo e(number_format($payment->dues_amount, 2)); ?>/- </strong> from each flat to ensure equal contribution from all members.</p>
                        <p>Please note that a late payment charge will be levied if the amount is not paid within the due date.</p>
                    </div>
                    
                    <!-- Bill Table -->
                    <table class="bill-table">
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
                        <tr><td colspan="3" style="height: 10px;"></td></tr>
                        <tr>
                            <td><strong>Essential Amount</strong></td>
                            <td>:</td>
                            <td>₹<?php echo e(number_format($payment->dues_amount, 2)); ?> /-</td>
                        </tr>
                        <tr>
                            <td><strong>Late Fine</strong></td>
                            <td>:</td>
                            <td>₹<?php echo e(number_format($late_fine, 2)); ?> /-</td>
                        </tr>
                        <?php if($total_gst > 0): ?>
                        <tr>
                            <td><strong>GST (if applicable)</strong></td>
                            <td>:</td>
                            <td>₹<?php echo e(number_format($total_gst, 2)); ?> /-</td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php
                            $calculated_total = $grand_total;
                            $rounded_total = ceil($calculated_total);
                            $rounding_adjust = $rounded_total - $calculated_total;
                        ?>
                        
                        <?php if($rounding_adjust > 0): ?>
                        <tr>
                            <td><strong>Rounding Adjustment</strong></td>
                            <td>:</td>
                            <td>₹<?php echo e(number_format($rounding_adjust, 2)); ?> /-</td>
                        </tr>
                        <?php endif; ?>
                        
                        <tr><td colspan="3" style="height: 10px;"></td></tr>
                        <tr class="total-row">
                            <td><strong>Total Payable Amount</strong></td>
                            <td>:</td>
                            <td><strong>₹<?php echo e(number_format($rounded_total, 2)); ?> /-</strong></td>
                        </tr>
                        <tr><td colspan="3" style="height: 10px;"></td></tr>
                        
                        <?php
                            $formatter = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
                            $amountInWords = ucfirst($formatter->format($rounded_total)) . ' rupees only';
                        ?>
                        
                        <tr>
                            <td colspan="3"><strong>(In words):</strong> <?php echo e($amountInWords); ?>/-</td>
                        </tr>
                    </table>
                    
                    <!-- Footer Section -->
                    <div class="footer-section">
                        <p>You can pay online with our secured payment gateway by clicking on the button "Pay now" in myflatinfo.</p>
                        <p>If you wish to pay in cash, please contact the Accounts team of the Management Committee.</p>
                        
                        <p style="margin-top: 20px;">Thanks & Regards,</p>
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
                    
                    <!-- Footer Note -->
                    <div class="footer-note">
                        <p><strong>Note:</strong> Please pay the essential bill before due date to avoid late payment charges.</p>
                        <p>This is a computer-generated invoice. No signature required.</p>
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

<?php $__env->startSection('script'); ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
  $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "<?php echo e(csrf_token()); ?>";

  });
</script>

<script>
    function downloadPDF() {
      const element = document.getElementById('pdf-content');
      html2pdf().from(element).save('invoice.pdf');
    }
</script>

<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>




<?php echo $__env->make('layouts.nosidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/partials/invoice/essential.blade.php ENDPATH**/ ?>