<?php $__env->startSection('title'); ?>
    Bookings
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

    <!-- Content Header (Page header) -->
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Facility Booking</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">Facility Booking</li>
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

            <div class="card">
                <div class="card-body">
                  <form method="GET" action="<?php echo e(url('booking')); ?>">
                    <div class="form-row">
                      <div class="form-group col-md-6">
                        <label for="model" class="col-form-label">Facility:</label>
                        <select name="facility_id" id="facility_id" class="form-control" required>
                          <option value="All">All</option>
                          <?php $__empty_1 = true; $__currentLoopData = $facilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <option value="<?php echo e($facility->id); ?>" <?php echo e(request('facility_id') == $facility->id ? 'selected' : ''); ?>><?php echo e($facility->name); ?></option>
                          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                          <?php endif; ?>
                        </select>
                      </div>
                      <div class="form-group col-md-6">
                        
                      </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-md-6">
                        <label for="from_date">From Date</label>
                        <input type="date" id="from_date" name="from_date" class="form-control" value="<?php echo e(request('from_date')); ?>">
                      </div>
                      <div class="form-group col-md-6">
                        <label for="to_date">To Date</label>
                        <input type="date" id="to_date" name="to_date" class="form-control" value="<?php echo e(request('to_date')); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                      <div class="form-group col-3">
                        <label>&nbsp;</label>
                        <a href="<?php echo e(url('booking')); ?>" class="btn btn-secondary btn-block mt-2">Reset</a>
                      </div>
                      <div class="form-group col-md-3">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block mt-2">Filter</button>
                      </div>
                    </div>
                    </form>
                </div>
            </div>



            <div class="card">
              <div class="card-body">
                <div class="table-responsive">
                <table id="example1" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                     <th>S No</th> 
                    <th>Facility</th>
                    <th>Booking Type</th>
                    <th>Booked By</th>
                    <th>Flat</th>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Members</th>
                    <th>Paid Amount</th>
                    <th>Refunded Amount</th>
                    <th>Reciept</th>
                    <!--<th>Order Id</th>-->
                    <!--<th>Transaction Id</th>-->
                    <th>Booked on</th>
                    <th>Status</th>
                    <!-- <?php if( (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts')|| Auth::User()->role == 'BA' || Auth::User()->selectedRole->name == 'Facility' ): ?> -->
                    <!--<th>Action</th>-->
                    <!--<?php endif; ?>-->
                  </tr>
                  </thead>
                  <tbody>
                    
                    <?php $i = 0; ?>
                  <?php $__empty_1 = true; $__currentLoopData = $bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php $i++; ?>
                  <tr>
                     <td><?php echo e($i); ?></td> 
                    <td><a href="<?php echo e(url('facility',$booking->facility_id)); ?>" target="_blank"><?php echo e($booking->facility->name); ?></a></td>
                    <td><?php echo e($booking->type); ?></td>
                    <td><a href="<?php echo e(url('user',$booking->user_id)); ?>" target="_blank"><?php echo e($booking->user->name); ?></a></td>
                     <td><a href="<?php echo e(url('flat',$booking->flat_id ?? '')); ?>" target="_blank"><?php echo e($booking->flat->name ?? ''); ?></a></td>
                    <td><?php echo e($booking->date); ?></td>
                    <td><?php echo e($booking->timing->from); ?></td>
                    <td><?php echo e($booking->timing->to); ?></td>
                    <td><?php echo e($booking->members); ?></td>
                    <td><?php echo e($booking->paid_amount); ?></td>
                    <td><?php echo e($booking->refunded_amount); ?></td>
                    <td><?php echo e($booking->reciept_no); ?></td>
                    <!--<td><?php echo e($booking->order_id); ?></td>-->
                    <!--<td><?php echo e($booking->transaction_id); ?></td>-->
                    <td><?php echo e($booking->created_at->diffForHumans()); ?></td>
                    <td><?php echo e($booking->status); ?></td>
                    <!--<?php if( (Auth::User()->selectedRole && Auth::User()->selectedRole->name == 'Accounts')|| Auth::User()->role == 'BA' || Auth::User()->selectedRole->name == 'Facility' ): ?> -->
                    <!--<td>-->
                    <!--    <?php if($booking->type == 'Offline' && $booking->status == 'Created'): ?>-->
                    <!--    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#verifyModal" data-id="<?php echo e($booking->id); ?>" data-status="<?php echo e($booking->status); ?>" -->
                    <!--    data-amount="<?php echo e($booking->amount); ?>" data-payable_amount="<?php echo e($booking->payable_amount); ?>" data-paid_amount="<?php echo e($booking->paid_amount); ?>">-->
                    <!--      Verify</button>-->
                    <!--    <?php endif; ?>-->
                        
                    <!--    <?php if($booking->status == 'Success' || $booking->status == 'Cancel Request'): ?>-->
                    <!--    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#cancelModal" data-id="<?php echo e($booking->id); ?>" data-status="<?php echo e($booking->status); ?>" -->
                    <!--    data-amount="<?php echo e($booking->amount); ?>" data-payable_amount="<?php echo e($booking->payable_amount); ?>" data-paid_amount="<?php echo e($booking->paid_amount); ?>" data-refundable_amount="<?php echo e($booking->refundable_amount); ?>" -->
                    <!--    data-cancellation_type="<?php echo e($booking->timing->cancellation_type); ?>">Cancel</button>-->
                    <!--    <?php endif; ?>-->
                    <!--</td>-->
                    <!--<?php endif; ?>-->
                  </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <?php endif; ?>
                  </tbody>
                </table>
                </div>
                
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
            
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div><!-- /.container-fluid -->
    </section>
    <!-- /.content -->

<!--verify modal-->
<div class="modal fade" id="verifyModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Are you sure ?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?php echo e(url('change-booking-status')); ?>" method="post" class="cancel-form">
      <?php echo csrf_field(); ?>
      <div class="modal-body">
        <p class="text">You are going to verify this booking</p>
            <div class="form-group">
                <label for="name" class="col-form-label">Payable Amount (with gst):</label>
                <input type="number" name="amount" id="amount" class="form-control" placeholder="Paid Amount" min="1" disabled>
            </div>
            
            <div class="form-group">
                <label for="name" class="col-form-label">Payment Type:</label>
                <select name="payment_type" id="payment_type" class="form-control" id="payment_type" required>
                    <option value="InHand">InHand</option>
                    <option value="InBank">InBank</option>
                </select>
            </div>
            
            <input type="hidden" name="booking_id" class="booking_id" value="" required>
            <input type="hidden" name="status" class="" value="Success" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-success" id="verify-button">Confirm Booking</button>
      </div>
      </form>
    </div>
  </div>
</div>

<!--cancel modal-->
<div class="modal fade" id="cancelModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Are you sure ?</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="<?php echo e(url('change-booking-status')); ?>" method="post" class="cancel-form">
      <?php echo csrf_field(); ?>
      <div class="modal-body">
        <p class="text">You are going to cancel this booking</p>
        <div class="form-group">
            <label for="name" class="col-form-label">Paid Amount (with gst):</label>
            <input type="number" name="paid_amount" id="paid_amount" class="form-control" placeholder="Paid Amount" min="1" disabled>
        </div>
        <div class="form-group">
            <label for="name" class="col-form-label">Refundable Amount:</label>
            <input type="number" name="refund_amount" id="refundable_amount" class="form-control" placeholder="Refund Amount" 
            disabled >
            <!--min="1"-->
        </div>
        <div class="form-group">
            <label for="name" class="col-form-label">Payment Type:</label>
            <select name="payment_type" id="payment_type" class="form-control" id="payment_type" required>
                <option value="InHand">InHand</option>
                <option value="InBank">InBank</option>
            </select>
        </div>
        <input type="hidden" name="booking_id" class="booking_id" value="" required>
        <input type="hidden" name="status" id="status" value="Cancelled" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-success" id="cancel-button">Cancel Booking</button>
      </div>
      </form>
    </div>
  </div>
</div>
<?php $__env->startSection('script'); ?>


<script>
  $(document).ready(function(){
    var id = '';
    var action = '';
    var token = "<?php echo e(csrf_token()); ?>";
    var model = "<?php echo e(request('model')); ?>";
    var model_id = "<?php echo e(request('model_id')); ?>";
    
    $(document).on('change','#model',function(){
      var model = $(this).val();
      $('.model-id').html('');
      if(model == 'Event' || model == 'Essential' || model == 'Booking'){
        $.ajax({
          url : "<?php echo e(url('/get-model-data')); ?>",
          type: "post",
          data : {'_token':token,'model':model},
          success: function(data)
          {
            $('.model-id').html(data);
          }
        });
      }
    });

    if(model == 'Event' || model == 'Essential' || model == 'Booking'){
        $.ajax({
          url : "<?php echo e(url('/get-model-data')); ?>",
          type: "post",
          data : {'_token':token,'model':model,'model_id':model_id},
          success: function(data)
          {
            $('.model-id').html(data);
          }
        });
      };
      
    $('#verifyModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      $('.booking_id').val(button.data('id'));
      $('#amount').val(button.data('payable_amount'));
    });
    
    // $(document).on('click','#verify-button',function(){
    //   $.ajax({
    //     url : "<?php echo e(url('change-booking-status')); ?>",
    //     type: "POST",
    //     data : {'_token':token,'booking_id':id,'status':'Success'},
    //     success: function(data)
    //     {
    //         window.location.reload();
    //     }
    //   });
    // });
    
    $('#cancelModal').on('show.bs.modal', function (event) {
      var button = $(event.relatedTarget);
      var cancellation_type = button.data('cancellation_type');
      $('.booking_id').val(button.data('id'));
      $('#paid_amount').val(button.data('paid_amount'));
      $('#refundable_amount').val(button.data('refundable_amount'));
      $('#refundable_amount').attr('required',false);
      $('#refundable_amount').attr('disabled',true);
      if(cancellation_type == 'Manual'){
          $('#refundable_amount').attr('required',true);
          $('#refundable_amount').attr('disabled',false);
          $('#refundable_amount').attr('max',button.data('payable_amount'));
      }
    });

    // $(document).on('click','#cancel-button',function(){
    //   var refund_amount = $('#refund_amount').val();
    //   $.ajax({
    //     url : "<?php echo e(url('change-booking-status')); ?>",
    //     type: "POST",
    //     data : {'_token':token,'booking_id':id,'refund_amount':refund_amount,'status':'Cancelled'},
    //     success: function(data)
    //     {
    //          window.location.reload();
    //     }
    //   });
    // });

  });
</script>

<script src="<?php echo e(asset('public/admin/plugins/summernote/summernote-bs4.min.js')); ?>"></script>

<script>
  $(function () {
    // Summernote
    $('#summernote').summernote()

  })
</script>

<?php $__env->stopSection(); ?>


<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/myflatin/buildingadmin.myflatinfo.com/resources/views/admin/booking/index.blade.php ENDPATH**/ ?>