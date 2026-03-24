<?php $__env->startSection('title'); ?>
    Invoice Maintenance
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<style>
    /* Hide any DataTables controls */
    .dataTables_filter,
    .dataTables_paginate,
    .dataTables_length,
    .dataTables_info,
    .dataTables_processing,
    .dataTables_wrapper .row,
    .pagination,
    .page-item,
    .page-link,
    .search-form,
    .search-input,
    .filter-form {
        display: none !important;
    }
    
    /* PDF Content Styling */
    #pdf-content {
        font-family: Arial, sans-serif;
        padding: 10px 40px 40px 40px;
        color: #333;
        max-width: 800px;
        margin: auto;
        line-height: 1.4;
        position: relative;
        background: #fff;
    }

    #pdf-content h2 {
        text-align: center;
        margin-bottom: 16px;
    }

    .section {
        margin-bottom: 16px;
    }

    .section p {
        margin: 4px 0;
        font-size: 14px;
    }

    .building-info {
        text-align: right;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        margin-top: 8px;
    }

    .table th,
    .table td {
        border: 1px solid #ccc;
        padding: 6px 10px;
        vertical-align: bottom;
    }

    .table th {
        background-color: #f5f5f5;
        text-align: left;
    }

    .table td.amount {
        text-align: right;
        vertical-align: bottom;
    }

    .footer-note {
        font-size: 12px;
        color: #777;
        border-top: 1px solid #ccc;
        padding-top: 10px;
        margin-top: 30px;
        text-align: center;
    }

    .section-building {
        text-align: right;
        margin: 4px 0;
        font-size: 16px;
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
        width: 70%;
    }
    
    .tablecc td {
        padding: 4px 8px;
        border: none;
        font-size: 14px;
    }
    
    .tablecc td:nth-child(2) {
        width: 20px;
        text-align: center;
    }
    
    .tablecc td:nth-child(1) {
        font-weight: bold;
    }
</style>

<?php
function indian_money($amount, $decimals = 2) {
    $fmt = new \NumberFormatter('en_IN', \NumberFormatter::DECIMAL);
    $fmt->setAttribute(\NumberFormatter::FRACTION_DIGITS, $decimals);
    return $fmt->format($amount);
}
?>
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
            
            <?php if(isset($maintenance_payments[0])): ?>
                <?php $current_payment = $maintenance_payments[0]; ?>
            
            <div class="card">
                <div class="card-header p-2">
                  <button class="btn btn-sm btn-info right mr-2" onclick="downloadPDF()">Download Invoice</button>
                </div>
              <div class="card-body" id="pdf-content">
                    <h2>Maintenance Bill</h2>

                    <!-- Logo -->
                    <img src="<?php echo e(asset('public/pdfImage/Transparent.png')); ?>" alt="Logo" class="app-logo" />
                    
                    <!-- PAID Stamp -->
                    <img src="<?php echo e(asset('public/pdfImage/paid-stamp-4.png')); ?>" alt="PAID" class="paid-stamp" />

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
                         <p>The monthly maintenance bill for the month of <strong><?php echo e(\Carbon\Carbon::parse($current_payment->maintenance->created_at)->format('F Y')); ?></strong> is generated.</p>
                    </div>

                    <div class="section" style="margin-top: 0px;">
                        <table class="tablecc">
                            <tbody>
                                <tr><td><strong>Bill generated on</strong></td><td>:</td><td><?php echo e(\Carbon\Carbon::parse($current_payment->created_at)->format('d-m-Y h:i A')); ?></td></tr>
                                <tr><td><strong>Bill number</strong></td><td>:</td><td><?php echo e($current_payment->bill_no); ?></td></tr>
                                <tr><td><strong>Bill due date</strong></td><td>:</td><td><?php echo e(\Carbon\Carbon::parse($current_payment->maintenance->due_date)->format('d-m-Y')); ?></td></tr>
                                <tr>
                                    <td><strong>Last paid date</strong></td>
                                    <td>:</td>
                                    <td>
                                        <?php echo e($last_paid_date && $last_paid_date !== 'N/A'
                                            ? \Carbon\Carbon::parse($last_paid_date)->format('d-m-Y')
                                            : 'N/A'); ?>

                                    </td>
                                </tr>

                            </tbody>
                        </table>
                    </div>

                    <div class="section">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Particulars</th>
                                    <th>Amount (₹)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- First Row -->
                                <tr>
                                    <td>
                                        <strong><p>A) Current Bill (<?php echo e(\Carbon\Carbon::parse($current_payment->maintenance->created_at)->format('F Y')); ?>)</p></strong>
                                        <?php if($current_payment->late_fine > 0): ?>
                                            <p>&nbsp;&nbsp;Late Fee</p>
                                        <?php endif; ?>
                                        <?php if($current_payment->gst > 0): ?>
                                            <p>&nbsp;&nbsp;GST @ <?php echo e($current_payment->maintenance->gst); ?>%</p>
                                        <?php endif; ?>
                                        <p> </p>
                                        <p style="padding-top:5px"><strong>Total Maintenance for the month of (<?php echo e(\Carbon\Carbon::parse($current_payment->maintenance->created_at)->format('F Y')); ?>)</strong></p>
                                    </td>
                                    <td class="amount">
                                        <p>₹<?php echo e(indian_money($current_payment->paid_amount, 1)); ?> /-</p>
                                        <?php if($current_payment->late_fine > 0): ?>
                                            <p>₹<?php echo e(indian_money($current_payment->late_fine, 1)); ?> /-</p>
                                        <?php endif; ?>
                                        <?php if($current_payment->gst > 0): ?>
                                            <p>₹<?php echo e(indian_money($current_payment->gst, 1)); ?> /-</p>
                                        <?php endif; ?>
                                        <p style="padding-top:5px"><strong>₹<?php echo e(indian_money($current_payment->paid_amount + $current_payment->late_fine + $current_payment->gst, 1)); ?> /-</strong></p>
                                    </td>
                                </tr>
                                
                                <!-- Arrears Row -->
                                <tr>
                                  <td>
                                        <p><strong>B) Arrears details as of <?php echo e(\Carbon\Carbon::parse($current_payment->created_at)->format('d-m-Y, h:i A')); ?>:</strong></p>
                                        <?php $arrears_total = 0; $arrear_count = 0; ?>
                                        <?php $__empty_1 = true; $__currentLoopData = $maintenance_payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <?php if($index > 0): ?>
                                                <?php 
                                                    $arrears_total += $payment->paid_amount + $payment->late_fine + $payment->gst;
                                                    $arrear_count++;
                                                    $month_total = $payment->paid_amount + $payment->late_fine + $payment->gst;
                                                ?>
                                                <p style="margin-top:-5px; margin-bottom:2px;">
                                                    <?php echo e($arrear_count); ?>) <?php echo e(\Carbon\Carbon::parse($payment->maintenance->to_date)->format('F Y')); ?> : 
                                                    ₹<?php echo e(indian_money($payment->paid_amount, 1)); ?>

                                                    <?php if($payment->late_fine > 0): ?>
                                                        + ₹<?php echo e(indian_money($payment->late_fine, 1)); ?> (late fine)
                                                    <?php endif; ?>
                                                    <?php if($payment->gst > 0): ?>
                                                        + ₹<?php echo e(indian_money($payment->gst, 1)); ?> (GST)
                                                    <?php endif; ?>
                                                    = ₹<?php echo e(indian_money($month_total, 1)); ?>

                                                </p>
                                            <?php endif; ?>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <p style="margin-top:-5px;">No arrears</p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="amount"><strong>₹<?php echo e(indian_money($arrears_total, 1)); ?> /-</strong></td>
                                </tr>
                                
                                <?php
                                    $rounded_total = ceil($grand_total);
                                    $rounding_adjustment = $rounded_total - $grand_total;
                                ?>
                                
                                <tr>
                                    <td><strong>C) Adjustments</strong></td>
                                    <td class="amount">₹0</td>
                                </tr>
                                
                                <tr>
                                    <td><strong>D) Total Amount Due (A + B + C)</strong></td>
                                    <td class="amount"><strong>₹<?php echo e(indian_money($grand_total, 1)); ?> /-</strong></td>
                                </tr>
                                 <tr>
                                    <td><strong>Rounding Adjustments</strong></td>
                                    <td class="amount">₹<?php echo e(indian_money($rounding_adjustment, 2)); ?></td>
                                </tr>
                                
                                <tr>
                                    <td><strong>Total Payable Amount</strong></td>       
                                    <td class="amount"><strong>₹<?php echo e(indian_money($rounded_total, 2)); ?> /-</strong></td>
                                </tr>
                                
                                <tr style="width:80%">
                                    <td style="vertical-align: top; white-space: nowrap; margin-top: 10px;">
                                        <strong>(In words) : </strong> 
                                        <?php
                                            $formatter = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
                                            $amountInWords = ucfirst($formatter->format($rounded_total)) . ' rupees only';
                                        ?>
                                        <?php echo e($amountInWords); ?>/-
                                    </td>
                                </tr>
                            </tbody>
                            </table>

                            <div class="section" style="margin-top: 10px;">
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
                                <p><strong>NOTE:</strong> Please pay the maintenance bill before the due date to avoid late payment charges.</p>
                                <p>This is a computer-generated invoice. No signature required.</p>
                            </div>
                        </div>
                    </div>
              </div><!-- /.card-body -->
            </div>
            <?php else: ?>
            <div class="card">
              <div class="card-body">
                <center>
                    <h3 class="">Maintenace payment not found</h3>
                </center>
              </div>
            </div>
            <?php endif; ?>
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
        <h5 class="modal-title" id="exampleModalLabel">Make Payment</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?php echo e(url('account/maintenance/pay-maintenance-bill')); ?>" method="post" class="add-form">
        <?php echo csrf_field(); ?>
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
          <input type="hidden" name="flat_id" id="flat_id" value="<?php echo e($flat->id); ?>">
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
      const filename = `maintenance_invoice_${timestamp}_${randomNum}.pdf`;
      
      const opt = {
        margin: 10,
        filename: filename,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { 
          scale: 2,
          logging: false,
          backgroundColor: '#ffffff'
        },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
      };
      
      html2pdf().set(opt).from(element).save();
    }
</script>
<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>




<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/account/maintenance/invoice_maintenance.blade.php ENDPATH**/ ?>