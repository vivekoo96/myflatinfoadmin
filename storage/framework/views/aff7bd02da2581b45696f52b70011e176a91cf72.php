<?php $__env->startSection('title'); ?>
    Pay Maintenance
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<style>
    p{margin-bottom:0px !important};
    
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
    
    /* Custom Particulars Table Styling */
    .particulars-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        font-family: Arial, sans-serif;
    }
    
    .particulars-table th {
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        padding: 12px;
        text-align: left;
        font-weight: bold;
        font-size: 14px;
    }
    
    .particulars-table td {
        border: 1px solid #dee2e6;
        padding: 12px;
        vertical-align: top;
        font-size: 14px;
    }
    
    .particulars-table .amount-col {
        text-align: right;
        width: 150px;
    }
    
    .particulars-table tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    
    .particulars-table p {
        margin: 4px 0;
        line-height: 1.4;
    }
    
    /* PDF Content Styling */
    #pdf-content {
        position: relative;
        background: #fff;
        padding: 10px 30px 40px 40px;
    }
    
    .app-logo {
        position: absolute;
        top: 20px;
        left: 20px;
        width: 120px;
        height: auto;
    }
    
    .paid-stamp {
        position: absolute;
        top: 180px;
        left: 50%;
        transform: translateX(-50%);
        width: 120px;
        opacity: 0.7;
        z-index: 1;
    }
    
    .building-info {
        text-align: right;
        margin-top: 20px;
        font-size: 14px;
        line-height: 1.4;
    }
    
    .maintenance-title {
        text-align: center;
        font-size: 28px;
        font-weight: bold;
        margin: 20px 0;
        color: #333;
    }
    
    .left-info {
        margin-top: 60px;
        font-size: 14px;
        line-height: 1.6;
    }
</style>
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
                   <?php if(Auth::User()->role == 'BA' ||(Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts')): ?>
                  <button class="btn btn-sm btn-success right" data-toggle="modal" data-target="#addModal">Pay Now</button>
                  <?php endif; ?>
                  <button class="btn btn-sm btn-info right mr-2" onclick="downloadPDF()">Download Invoice</button>
                </div>
              <div class="card-body" id="pdf-content">
                    <!-- Logo -->
                    <img src="<?php echo e(asset('public/pdfImage/Transparent.png')); ?>" class="app-logo" alt="Logo">
                    
                    <!-- PAID Stamp -->
                    <?php if($current_payment->maintenance->status == "Paid"): ?>
                    <img src="<?php echo e(asset('public/pdfImage/paid-stamp-4.png')); ?>" class="paid-stamp" alt="PAID">
                    <?php endif; ?>
                    
                    <!-- Header Section -->
                    <div class="row">
                        <div class="col-md-12">
                            <h1 class="maintenance-title">Maintenance Bill</h1>
                        </div>
                    </div>
                    
                    <!-- Main Content Row -->
                    <div class="row">
                        <!-- Left Side Info -->
                        <div class="col-md-6">
                            <div class="left-info">
                                <p><strong>Block No :</strong> <?php echo e($flat->block->name); ?>,</p>
                                <p><strong>Flat No :</strong> <?php echo e($flat->name); ?>,</p>
                                <p><strong>Dear <?php echo e($user->name); ?>,</strong></p>
                                <p>The monthly maintenance bill for the month of <strong><?php echo e(\Carbon\Carbon::parse($current_payment->maintenance->to_date)->format('F Y')); ?></strong> is generated.</p>
                            </div>
                        </div>
                        
                        <!-- Right Side Building Info -->
                        <div class="col-md-6">
                            <div class="building-info">
                                <p><strong><?php echo e($flat->building->name); ?></strong></p>
                                <p><?php echo e($flat->building->address); ?></p>
                                <?php if(!empty($flat->building->gst_no)): ?>
                                    <p><strong>GST No :-</strong> <?php echo e($flat->building->gst_no); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3"><b>Bill generated on</b></div> 
                        <div class="col-md-3">: <?php echo e(\Carbon\Carbon::parse($current_payment->created_at)->format('d-m-Y h:i A')); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3"><b>Bill number</b></div>
                        <div class="col-md-3">: <?php echo e($current_payment->bill_no); ?> </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3"><b>Bill due date</b></div>
                        <div class="col-md-3">: <?php echo e(\Carbon\Carbon::parse($current_payment->maintenance->due_date)->format('d-m-Y')); ?> </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3"><b>Last paid date</b></div>
                      <div class="col-md-3">
                        : <?php echo e($last_paid_date && $last_paid_date !== 'N/A'
                            ? \Carbon\Carbon::parse($last_paid_date)->format('d-m-Y')
                            : 'N/A'); ?>

                    </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <?php
                              $current_total = $current_payment->dues_amount + $current_payment->late_fine + $current_payment->gst;
                              $arrears_total = 0;
                              if (isset($maintenance_payments) && count($maintenance_payments) > 1) {
                                foreach($maintenance_payments as $idx => $p) {
                                  if ($idx > 0) {
                                    $arrears_total += ($p->dues_amount + $p->late_fine + $p->gst);
                                  }
                                }
                              }
                              $adjustments = 0;
                                $calculated_total = $current_total + $arrears_total + $adjustments;
                                // Round payable to nearest whole rupee so final PDF shows ".00" paise
                                $rounded_total = ceil($calculated_total);
                                $rounding_adjust = $rounded_total - $calculated_total;

                              function numberToWords($number) {
                                $units = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
                                  'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
                                $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

                                if ($number == 0) return 'Zero';
                                if ($number < 20) return $units[$number];
                                if ($number < 100) return $tens[intval($number/10)] . ($number%10 ? ' ' . $units[$number%10] : '');
                                if ($number < 1000) return $units[intval($number/100)] . ' Hundred' . ($number%100 ? ' and ' . numberToWords($number%100) : '');
                                if ($number < 100000) return numberToWords(intval($number/1000)) . ' Thousand' . ($number%1000 ? ' ' . numberToWords($number%1000) : '');
                                if ($number < 10000000) return numberToWords(intval($number/100000)) . ' Lakh' . ($number%100000 ? ' ' . numberToWords($number%100000) : '');
                                return numberToWords(intval($number/10000000)) . ' Crore' . ($number%10000000 ? ' ' . numberToWords($number%10000000) : '');
                              }
                            ?>

                            <table class="particulars-table">
                              <thead>
                                <tr>
                                  <th>Particulars</th>
                                  <th class="amount-col">Amount (₹)</th>
                                </tr>
                              </thead>
                              <tbody>
                                <tr>
                                  <td>
                                    <p><b>A) Current Bill (<?php echo e(\Carbon\Carbon::parse($current_payment->maintenance->to_date)->format('F Y')); ?>)</b></p>
                                    <p>Late Fee (<?php echo e($current_payment->maintenance->late_fine_type ?? ''); ?>)</p>
                                    <p>GST @ <?php echo e($current_payment->maintenance->gst); ?>%</p>
                                    <p><b>Total Maintenance for (<?php echo e(\Carbon\Carbon::parse($current_payment->maintenance->to_date)->format('F Y')); ?>)</b></p>
                                  </td>
                                  <td class="amount-col">
                                    <p>₹<?php echo e(number_format($current_payment->dues_amount, 2)); ?> /-</p>
                                    <p>₹<?php echo e(number_format($current_payment->late_fine, 2)); ?> /-</p>
                                    <p>₹<?php echo e(number_format($current_payment->gst, 2)); ?> /-</p>
                                    <p><b>₹<?php echo e(number_format($current_total, 2)); ?> /-</b></p>
                                  </td>
                                </tr>
                                <tr>
                                  <td>
                                    <p><b>B) Arrears details as of <?php echo e(\Carbon\Carbon::parse($current_payment->created_at)->format('d-m-Y, h:i A')); ?>:</b></p>
                                    <?php if($arrears_total > 0): ?>
                                      <?php $__currentLoopData = $maintenance_payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php if($index > 0): ?>
                                          <p><?php echo e($index); ?>) <?php echo e(\Carbon\Carbon::parse($payment->maintenance->to_date)->format('F Y')); ?> : ₹<?php echo e(number_format($payment->dues_amount + $payment->late_fine + $payment->gst, 2)); ?> /-</p>
                                        <?php endif; ?>
                                      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php else: ?>
                                      <p>No arrears available.</p>
                                    <?php endif; ?>
                                  </td>
                                  <td class="amount-col">
                                    <p><?php echo e($arrears_total > 0 ? '₹' . number_format($arrears_total, 2) . ' /-' : '₹ 0 /-'); ?></p>
                                  </td>
                                </tr>

                                <tr>
                                  <td><b>C) Adjustments</b></td>
                                  <td class="amount-col">₹ <?php echo e(number_format($adjustments, 2)); ?> /-</td>
                                </tr>
                                <tr>
                                  <td><b>D) Total Amount Due (A + B + C)</b></td>
                                  <td class="amount-col"><b>₹<?php echo e(number_format($calculated_total, 2)); ?> /-</b></td>
                                </tr>
                                <tr>
                                  <td>Rounding Adjustment</td>
                                  <td class="amount-col">₹<?php echo e(number_format($rounding_adjust, 2)); ?> /-</td>
                                </tr>
                                <tr>
                                  <td><b>Total Payable Amount</b></td>
                                  <td class="amount-col"><b>₹<?php echo e(number_format($rounded_total, 2)); ?> /-</b></td>
                                </tr>
                                <tr>
                                  <td colspan="2">(In words) : <?php echo e(numberToWords(intval($rounded_total))); ?> Rupees only/-</td>
                                </tr>
                              </tbody>
                            </table>
                             <small>You can pay online with our secured payment gateway by clicking on the button "Pay now" in myflatinfo.</small>
                             <small>If you wish to pay in cash, please contact the Accounts team of the Management Committee.</small>
                             
                            
                            <!-- Footer Section -->
                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <p><strong>Thanks & Regards,</strong></p>
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
                            </div>
                            
                            <!-- Footer Note -->
                            <div class="row mt-3">
                                <div class="col-md-12">
                                    <hr>
                                    <center>
                                        <p><strong>Note:</strong> Please pay the maintenance bill before the due date to avoid late payment charges.</p>
                                        <p>This is a computer-generated invoice. No signature required.</p>
                                    </center>
                                </div>
                            </div>
                        </div>
                    </div>
              </div><!-- /.card-body -->
            </div>
            <?php else: ?>
            <div class="card">
              <div class="card-body">
                <center>
                    <h3 class="">No dues maintenace found</h3>
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
          <input type="hidden" name="flat_id" id="flat_id" value="<?php echo e($flat->id); ?>">
          <input type="hidden" name="amount" id="amount" value="<?php echo e($grand_total); ?>">
          <input type="hidden" name="payment_person" id="payment_person" value="<?php echo e(request('payment_person')); ?>">
          
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




<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/account/maintenance/pay_maintenance.blade.php ENDPATH**/ ?>