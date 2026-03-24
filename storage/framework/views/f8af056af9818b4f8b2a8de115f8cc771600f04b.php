<?php $__env->startSection('title'); ?>
    Invoice Maintenance
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<style>


/*@page  {*/
/*    size: A4;*/
/*    margin: 20mm;*/
/*}*/

    body {
        font-family: Arial, sans-serif;
        background: #ffffff;
        color: #333;
        margin: 0;
        padding: 0;

    }

    #pdf-content {
        
        max-width: 794px;
        margin: auto;
        font-size: 12px;
        line-height: 1.3;
        position: relative;
        background: #fff;
        /*box-sizing: border-box;*/
    }

    h2 {
        text-align: center;
        font-size: 22px;
        margin-bottom: 20px;
        letter-spacing: 0.5px;
    }

    .section {
        margin-bottom: 18px;
    }

    .section p {
        margin: 4px 0;
        font-size: 13px;
    }

    .section-building {
        text-align: right;
        font-size: 14px;
        margin-bottom: 20px;
    }

    .section-building p {
        margin: 3px 0;
    }

    .app-logo {
        position: absolute;
        top: 1px;
        left: 1px;
        width: 150px;
    }

    .paid-stamp {
        position: absolute;
        top: 200px;
        right: 300px;
        width: 130px;
        opacity: 0.35;
    }

    .tablecc {
        width: 70%;
        font-size: 12px;
        margin-bottom: 12px;
        
            width: 100%;
    border-collapse: collapse;
    page-break-inside: auto;
    }

    .tablecc td {
        padding: 4px 6px;
        border: none;
    }

    .tablecc td:first-child {
        font-weight: bold;
        width: 180px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
        font-size: 13px;
    }

    .table th {
        background: #f4f4f4;
        border: 1px solid #ccc;
        padding: 8px 10px;
        text-align: left;
        font-weight: bold;
    }

    .table td {
        border: 1px solid #ccc;
        padding: 8px 10px;
        vertical-align: top;
    }

    .table td.amount {
        text-align: right;
        white-space: nowrap;
    }

    .rupee-symbol {
        font-family: 'DejaVu Sans', Arial, sans-serif;
        font-weight: bold;
    }


tr {
    page-break-inside: avoid;
}

.section {
    page-break-inside: avoid;
}
    .footer-note {
        margin-top: 30px;
        padding-top: 12px;
        border-top: 1px solid #ccc;
        font-size: 12px;
        text-align: center;
        color: #666;
    }
    
    
/*    .footer-note {*/
/*    page-break-inside: avoid;*/
/*    break-inside: avoid;*/
/*}*/

    
.thanks {
    margin-top: 20px;
    font-size: 13px;
    page-break-inside: avoid;
    break-inside: avoid;
    line-height: 1.0;
}

.amount-words-row {
    page-break-inside: avoid;
    break-inside: avoid;
}

.amount-words-row td {
    white-space: normal;   /* allow wrapping */
    word-break: break-word;
    line-height: 1.4;
}

</style>

<?php if(isset($maintenance_payments[0])): ?>
<?php $current_payment = $maintenance_payments[0]; ?>

<div id="pdf-content">

    <h2>Maintenance Bill</h2>

    <img src="<?php echo e(asset('public/pdfImage/Transparent.png')); ?>" class="app-logo">

    <?php if($current_payment->status == 'Paid'): ?>
        <img src="<?php echo e(asset('public/pdfImage/paid-stamp-4.png')); ?>" class="paid-stamp">
    <?php endif; ?>

    <div class="section-building">
        <p><strong><?php echo e($flat->building->name); ?></strong></p>
        <p style="width:260px; margin-left:auto; text-align: right;"><?php echo e($flat->building->address); ?></p>
        <!--<?php if(!empty($flat->building->city)): ?>-->
        <!--    <p><?php echo e($flat->building->city->name); ?></p>-->
        <!--<?php endif; ?>-->
        <?php if(!empty($flat->building->gst_no)): ?>
            <p><strong>GST No:</strong> <?php echo e($flat->building->gst_no); ?></p>
        <?php endif; ?>
    </div>

    <div class="section">
        <p>Block No : <strong><?php echo e($flat->block->name); ?></strong></p>
        <p>Flat No : <strong><?php echo e($flat->name); ?></strong></p>
        <p>Dear <strong><?php echo e($user->name); ?></strong>,</p>
        <p>The monthly maintenance bill for <strong><?php echo e(\Carbon\Carbon::parse($current_payment->maintenance->from_date)->format('F Y')); ?></strong> has been generated.</p>
    </div>

    <div class="section">
        <table class="tablecc" style="margin-left: -5px;">
            <tr><td>Bill generated on</td><td>:</td><td><?php echo e(\Carbon\Carbon::parse($current_payment->created_at)->format('d-m-Y h:i A')); ?></td></tr>
            <tr><td>Bill number</td><td>:</td><td><?php echo e($current_payment->bill_no); ?></td></tr>
            <tr><td>Bill due date</td><td>:</td><td><?php echo e($current_payment->maintenance->due_date); ?></td></tr>
            <tr><td>Last paid date</td><td>:</td><td><?php echo e($last_paid_date); ?></td></tr>
        </table>
    </div>

        <div class="section" style="margin-bottom: 5px;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Particulars</th>
                        <th>Amount (<span class="rupee-symbol">₹</span>)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- First Row -->
                    <tr>
                        <td>
                            <strong><p>A) Current Bill (<?php echo e(\Carbon\Carbon::parse($current_payment->maintenance->from_date)->format('F Y')); ?>)</p></strong>
                            <?php if($current_payment->late_fine > 0): ?>
                                <p>&nbsp;&nbsp;Late Fee</p>
                            <?php endif; ?>
                            <?php if($current_payment->maintenance->gst > 0): ?>
                                <p>&nbsp;&nbsp;GST @ <?php echo e($current_payment->maintenance->gst); ?>%</p>
                            <?php endif; ?>
                            <p> </p>
                            <p style="padding-top:5px"><strong>Total Maintenance for the month of (<?php echo e(\Carbon\Carbon::parse($current_payment->maintenance->from_date)->format('F Y')); ?>)</strong></p>
                        </td>
                        <td class="amount">
                            <?php
                                if($current_payment->status == 'Paid') {
                                    $base_amount = $current_payment->paid_amount;
                                } else {
                                    // For unpaid bills, use dues_amount if available, otherwise use maintenance amount
                                    $base_amount = $current_payment->dues_amount > 0 ? $current_payment->dues_amount : $current_payment->maintenance->amount;
                                }
                                $current_gst = ($base_amount + $current_payment->late_fine) * $current_payment->maintenance->gst / 100;
                                $current_total = $base_amount + $current_payment->late_fine + $current_gst;
                            ?>
                            <p><span class="rupee-symbol">₹</span><?php echo e(number_format($base_amount, 2)); ?> /-</p>
                            <?php if($current_payment->late_fine > 0): ?>
                                <p><span class="rupee-symbol">₹</span><?php echo e(number_format($current_payment->late_fine, 2)); ?> /-</p>
                            <?php endif; ?>
                            <?php if($current_gst > 0): ?>
                                <p><span class="rupee-symbol">₹</span><?php echo e(number_format($current_gst, 2)); ?> /-</p>
                            <?php endif; ?>
                            <p style="padding-top:5px"><strong><span class="rupee-symbol">₹</span><?php echo e(number_format($current_total, 2)); ?> /-</strong></p>
                        </td>
                    </tr>
                    
                    <!-- Arrears Row -->
                    <tr>
                        <td>
                            <p><strong>B) Arrears details as on <?php echo e(\Carbon\Carbon::parse($current_payment->created_at)->format('d-m-Y h:i A')); ?>:</strong></p>
                            <p style="margin-top:-5px">&nbsp;&nbsp;
                                <?php $arrears_total = 0; $arrears_count = 0; ?>
                                <?php $__empty_1 = true; $__currentLoopData = $maintenance_payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php if($index > 0): ?>
                                        <?php 
                                            $arrears_count++;
                                            if($payment->status == 'Paid') {
                                                $arrear_base = $payment->paid_amount ?? 0;
                                            } else {
                                                $arrear_base = ($payment->dues_amount ?? 0) > 0 ? $payment->dues_amount : ($payment->maintenance->amount ?? 0);
                                            }
                                            $late_fine = $payment->late_fine ?? 0;
                                            $gst_percent = $payment->maintenance->gst ?? 0;
                                            $arrear_gst = ($arrear_base + $late_fine) * $gst_percent / 100;
                                            $arrear_total_amount = $arrear_base + $late_fine + $arrear_gst;
                                            $arrears_total += $arrear_total_amount;
                                        ?>
                                       
                                        <?php echo e(\Carbon\Carbon::parse($payment->maintenance->from_date)->format('F Y')); ?> : <span class="rupee-symbol">₹</span><?php echo e(number_format($arrear_total_amount, 2)); ?>

                                        <?php if(!$loop->last): ?>, <?php endif; ?>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    No arrears
                                <?php endif; ?>
                            </p>
                        </td>
                        <td class="amount"><strong><span class="rupee-symbol">₹</span><?php echo e(number_format($arrears_total, 2)); ?> /-</strong></td>
                    </tr>

                    
                    <tr>
                        <td><strong>C) Adjustments</strong></td>
                        <td class="amount"><span class="rupee-symbol">₹</span> 0</td>
                    </tr>
                    
                    <tr>
                        <td><strong>D) Total Amount Due (A + B + C)</strong></td>
                        <?php 
                            $calculated_total = $current_total + $arrears_total;
                            $rounded_total = ceil($calculated_total);
                            $rounding_adjust = $rounded_total - $calculated_total;
                        ?>
                        <td class="amount"><strong><span class="rupee-symbol">₹</span><?php echo e(number_format($calculated_total, 2)); ?> /-</strong></td>
                    </tr>
                    <tr>
                        <td>Rounding Adjustment</td>
                        <td class="amount"><span class="rupee-symbol">₹</span><?php echo e(number_format($rounding_adjust, 2)); ?> /-</td>
                    </tr>
                    <tr>
                        <td><strong>Total Payable Amount</strong></td>       
                        <td class="amount"><strong><span class="rupee-symbol">₹</span><?php echo e(number_format($rounded_total, 2)); ?> /-</strong></td>
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
        </div>

    <div class="thanks">
        <p>Thanks & Regards,</p>
        <?php if($flat->building->treasurer_id): ?>
            <p><?php echo e($flat->building->treasurer->name); ?></p>
            <?php if($flat->building->treasurer->phone): ?>
                <p>Contact: +91 <?php echo e($flat->building->treasurer->phone); ?></p>
            <?php endif; ?>
        <?php else: ?>
            <p><?php echo e($flat->building->user->name); ?></p>
            <?php if($flat->building->user->phone): ?>
                <p>Contact: +91 <?php echo e($flat->building->user->phone); ?></p>
            <?php endif; ?>
        <?php endif; ?>
        <?php if($flat->building->name): ?>
            <p><?php echo e($flat->building->name); ?></p>
        <?php endif; ?>
    </div>

    <div class="footer-note">
        <p><strong>NOTE:</strong> Please pay before due date to avoid late payment charges.</p>
        <p>This is a computer-generated invoice. No signature required.</p>
    </div>

</div>

<?php else: ?>
<div style="text-align:center;padding:40px;">
    <h4>Maintenance payment not found</h4>
</div>
<?php endif; ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.nosidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/partials/invoice/maintenance.blade.php ENDPATH**/ ?>