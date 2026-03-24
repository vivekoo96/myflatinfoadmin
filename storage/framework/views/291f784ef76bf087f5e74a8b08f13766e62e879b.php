<?php $__env->startSection('title'); ?>
    Reciept Event
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<style>
    p{margin-bottom:0px !important};
</style>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
            
            <div class="card">
              <div class="card-body" id="pdf-content">
                    <div class="row">
                        <div class="col-md-12">
                            <center><h2>Event Reciept</h2></center>
                        </div>
                        <div class="col-md-12">
                            <div class="right">
                                <p class=""><b><?php echo e($payment->building ? $payment->building->name : 'N/A'); ?></b></p>
                                <p class=""><?php echo e($payment->building ? $payment->building->address : 'N/A'); ?></p>
                                
                            </div>
                        </div>
                        <div class="col-md-12">
                            <p>Block No: <?php echo e($payment->flat ? $payment->flat->block->name : 'N/A'); ?></p>
                            <p>Flat No: <?php echo e($payment->flat ? $payment->flat->name : 'N/A'); ?></p>
                            <p>Dear <b><?php echo e($payment->user->name); ?>,</b></p>
                            <p>On behalf of <b><?php echo e($payment->building->name); ?></b>, we would like to thank you for your contribution towards <b><?php echo e($payment->event->name); ?></b>.</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><b>Transaction Reference Number</b></div> 
                        <div class="col-md-6">: <?php echo e($payment->transaction->reciept_no); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><b>Payment date</b></div>
                        <div class="col-md-6">: <?php echo e($payment->date); ?> </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6"><b>Total Amount paid</b></div>
                        <div class="col-md-6">: ₹<?php echo e(number_format($payment->amount, 2)); ?> </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6"><b>(In words)</b></div>
                        <?php
                            $formatter = new \NumberFormatter('en_IN', \NumberFormatter::SPELLOUT);
                            $amountInWords = ucfirst($formatter->format($payment->amount)) . ' rupees only';
                        ?>
                        <div class="col-md-6">: <?php echo e($amountInWords); ?> </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6"><b>Payment mode</b></div>
                        <div class="col-md-6">: <?php echo e($payment->payment_type); ?> </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><b>Payment mode</b></div>
                        <div class="col-md-6">: <?php echo e($payment->desc); ?> </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">Thanks & Regards,</div>
                        <div class="col-md-12"><?php echo e($payment->building->name); ?></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <hr>
                            <center>
                                <p>This is a computer-generated invoice. No signature required</p>
                            </center>
                        </div>
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
    function downloadPDF() {
      const element = document.getElementById('pdf-content');
      html2pdf().from(element).save('reciept.pdf');
    }
</script>

<?php $__env->stopSection(); ?>

<?php $__env->stopSection(); ?>




<?php echo $__env->make('layouts.nosidebar', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/partials/reciept/event.blade.php ENDPATH**/ ?>